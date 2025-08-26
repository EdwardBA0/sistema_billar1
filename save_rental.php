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
$duration = $data['duration'];
$cost = $data['cost'];

try {
    // Convertir las fechas al formato correcto para MySQL
    $startTime = date('Y-m-d H:i:s', strtotime($startTime));
    $endTime = date('Y-m-d H:i:s', strtotime($endTime));

    // Insertar los datos en la tabla reportes
    $stmt = $conn->prepare("INSERT INTO reportes (mesa, hora_inicio, hora_fin, duracion, precio) VALUES (:mesa, :hora_inicio, :hora_fin, SEC_TO_TIME(:duracion), :precio)");
    $stmt->execute([
        ':mesa' => "Mesa $tableNumber",
        ':hora_inicio' => $startTime,
        ':hora_fin' => $endTime,
        ':duracion' => $duration,
        ':precio' => $cost
    ]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
