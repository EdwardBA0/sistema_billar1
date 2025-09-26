<?php
require_once 'db_config.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'No se recibieron datos.']);
    exit;
}

$tableNumber = $data['tableNumber'];
$alquilada = $data['estado'] === 'alquilada' ? 1 : 0;
$hora_inicio = $alquilada ? date('Y-m-d H:i:s', strtotime($data['hora_inicio'])) : null;
$rental_time = $alquilada ? ($data['rental_time'] ?? null) : null; // nuevo campo

try {
    $stmt = $conn->prepare("UPDATE estado_mesas 
                            SET alquilada = :alquilada, 
                                hora_inicio = :hora_inicio,
                                rental_time = :rental_time
                            WHERE mesa = :mesa");
    $stmt->execute([
        ':mesa' => $tableNumber,
        ':alquilada' => $alquilada,
        ':hora_inicio' => $hora_inicio,
        ':rental_time' => $rental_time
    ]);

    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'error' => 'Mesa no encontrada en estado_mesas']);
        exit;
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
