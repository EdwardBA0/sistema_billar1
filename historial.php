<?php
require_once 'db_config.php';

try {
    $stmt = $conn->prepare("SELECT * FROM reportes ORDER BY id DESC");
    $stmt->execute();
    $reportes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener el historial: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial</title>
    <link rel="stylesheet" href="historial.css">
</head>
<body class="background">
    <div class="container centered">
        <h1 class="page-title">Historial</h1>
        <table class="historial-table">
            <thead>
                <tr>
                    <th>Mesa</th>
                    <th>Hora de Inicio</th>
                    <th>Hora de Fin</th>
                    <th>Duración</th>
                    <th>Precio (S/)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reportes as $reporte): ?>
                    <tr>
                        <td><?php echo $reporte['mesa']; ?></td>
                        <td><?php echo $reporte['hora_inicio']; ?></td>
                        <td><?php echo $reporte['hora_fin']; ?></td>
                        <td><?php echo $reporte['duracion']; ?></td>
                        <td>S/ <?php echo number_format($reporte['precio'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <button onclick="window.location.href='index.php'" class="btn-return">Volver al inicio</button>
        <!-- Botón para limpiar historial -->
        <button id="clearHistoryButton" class="btn-clear">Limpiar Historial y Reportes</button>
    </div>

    <script>
        document.getElementById('clearHistoryButton').addEventListener('click', function () {
            if (confirm("¿Estás seguro de que deseas limpiar el historial y los reportes?")) {
                fetch('clear_data.php', {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload(); // Recargar la página para reflejar cambios
                    } else {
                        alert("Error al limpiar el historial: " + data.error);
                    }
                })
                .catch(error => {
                    alert("Error al comunicarse con el servidor: " + error.message);
                });
            }
        });
    </script>
</body>
</html>
