<?php
require_once 'db_config.php';

try {
    $stmt = $conn->prepare("SELECT nombre, valor FROM configuracion");
    $stmt->execute();

    $configurations = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $configurations[$row['nombre']] = $row['valor'];
    }

    echo json_encode([
        'numTables' => isset($configurations['num_mesas']) ? (int)$configurations['num_mesas'] : 6,
        'pricePerHour' => isset($configurations['precio_hora']) ? (float)$configurations['precio_hora'] : 6.00
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
