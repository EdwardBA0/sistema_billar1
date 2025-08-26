<?php
require_once 'db_config.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'No se recibieron datos.']);
    exit;
}

$tableNumber = $data['tableNumber'];
$alquilada = $data['estado'] === 'alquilada' ? 1 : 0;
$hora_inicio = $data['hora_inicio'];

try {
    $stmt = $conn->prepare("INSERT INTO estado_mesas (mesa, alquilada, hora_inicio)
                            VALUES (:mesa, :alquilada, :hora_inicio)
                            ON DUPLICATE KEY UPDATE
                            alquilada = VALUES(alquilada), hora_inicio = VALUES(hora_inicio)");
    $stmt->execute([
        ':mesa' => $tableNumber,
        ':alquilada' => $alquilada,
        ':hora_inicio' => $hora_inicio
    ]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
