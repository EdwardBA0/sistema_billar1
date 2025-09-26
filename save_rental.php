<?php
require_once 'db_config.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'No se recibieron datos.']);
    exit;
}

$tableNumber = $data['tableNumber'];
$startTime = $data['startTime'];
$endTime = $data['endTime'];
$duration = $data['duration']; // en segundos
$cost = $data['cost'];
$cliente_nombre = $data['cliente_nombre'] ?? null;
$cliente_dni = $data['cliente_dni'] ?? null;

try {
    // Guardar fecha y hora completa
    $startTimeFormatted = date('Y-m-d H:i:s', strtotime($startTime));
    $endTimeFormatted   = date('Y-m-d H:i:s', strtotime($endTime));

    // Insertar en la tabla reportes
    $stmt = $conn->prepare("
        INSERT INTO reportes (mesa, hora_inicio, hora_fin, duracion, precio, cliente_nombre, cliente_dni)
        VALUES (:mesa, :hora_inicio, :hora_fin, SEC_TO_TIME(:duracion), :precio, :cliente_nombre, :cliente_dni)
    ");
    $stmt->execute([
        ':mesa' => "Mesa $tableNumber",
        ':hora_inicio' => $startTimeFormatted,
        ':hora_fin' => $endTimeFormatted,
        ':duracion' => $duration,
        ':precio' => $cost,
        ':cliente_nombre' => $cliente_nombre,
        ':cliente_dni' => $cliente_dni
    ]);

    // 🚀 Liberar la mesa en estado_mesas
    $update = $conn->prepare("UPDATE estado_mesas SET alquilada = 0, hora_inicio = NULL WHERE mesa = :mesa");
    $update->execute([':mesa' => $tableNumber]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
