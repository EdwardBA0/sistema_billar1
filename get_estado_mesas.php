<?php
require_once 'db_config.php';

try {
    // Obtener el número de mesas configuradas
    $stmt = $conn->prepare("SELECT valor FROM configuracion WHERE nombre = 'num_mesas'");
    $stmt->execute();
    $numMesas = (int) $stmt->fetchColumn();

    // Verificar si la tabla tiene suficientes registros
    $stmt = $conn->query("SELECT COUNT(*) FROM estado_mesas");
    $mesaCount = (int) $stmt->fetchColumn();

    // Agregar o eliminar mesas según sea necesario
    if ($mesaCount < $numMesas) {
        for ($i = $mesaCount + 1; $i <= $numMesas; $i++) {
            $stmt = $conn->prepare("INSERT INTO estado_mesas (mesa, alquilada, hora_inicio) VALUES (:mesa, 0, NULL)");
            $stmt->execute([':mesa' => $i]);
        }
    } elseif ($mesaCount > $numMesas) {
        $stmt = $conn->prepare("DELETE FROM estado_mesas WHERE mesa > :mesa");
        $stmt->execute([':mesa' => $numMesas]);
    }

    // Recuperar el estado actualizado de las mesas
    $stmt = $conn->query("SELECT mesa, alquilada AS estado, hora_inicio FROM estado_mesas");
    $mesas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($mesas);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
