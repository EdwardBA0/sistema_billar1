<?php
require_once 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Limpiar la tabla de reportes
        $stmt = $conn->prepare("DELETE FROM reportes");
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Historial y reportes eliminados correctamente.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Método no permitido.']);
}
