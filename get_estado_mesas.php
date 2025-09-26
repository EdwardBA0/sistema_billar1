<?php
require_once 'db_config.php';

try {
    // 1. Obtener configuraciones
    $stmt = $conn->prepare("SELECT nombre, valor FROM configuracion");
    $stmt->execute();

    $configurations = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $configurations[$row['nombre']] = $row['valor'];
    }

    $numMesas = isset($configurations['num_mesas']) ? (int)$configurations['num_mesas'] : 6;
    $precioHora = isset($configurations['precio_hora']) ? (float)$configurations['precio_hora'] : 6.00;

    // 2. Contar registros en estado_mesas
    $stmt = $conn->query("SELECT COUNT(*) FROM estado_mesas");
    $mesaCount = (int) $stmt->fetchColumn();

    // 3. Sincronizar cantidad de mesas
    if ($mesaCount < $numMesas) {
        for ($i = $mesaCount + 1; $i <= $numMesas; $i++) {
            $stmt = $conn->prepare("
                INSERT INTO estado_mesas (mesa, alquilada, hora_inicio)
                VALUES (:mesa, 0, NULL)
            ");
            $stmt->execute([':mesa' => $i]);
        }
    } elseif ($mesaCount > $numMesas) {
        $stmt = $conn->prepare("DELETE FROM estado_mesas WHERE mesa > :mesa");
        $stmt->execute([':mesa' => $numMesas]);
    }

    // 4. Obtener estado actualizado
   $stmt = $conn->query("SELECT mesa, alquilada, hora_inicio, rental_time FROM estado_mesas ORDER BY mesa ASC");

    $mesas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Respuesta JSON con todo
    echo json_encode([
        'success' => true,
        'numTables' => $numMesas,
        'pricePerHour' => $precioHora,
        'mesas' => $mesas
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
