<?php
require_once 'db_config.php';

try {
    $stmt = $conn->prepare("SELECT * FROM reportes ORDER BY id DESC");
    $stmt->execute();
    $reportes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener los reportes: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes</title>
    <link rel="stylesheet" href="reportes.css">
</head>
<body class="background">
    <div class="container centered">
        <h1 class="page-title">Reportes</h1>
        <table class="report-table" id="report-table">
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
        <button onclick="window.location.href='export_pdf.php'" class="btn-export">Exportar a PDF</button>

        <!-- Botón para limpiar reportes -->
        <button id="clearReportsButton" class="btn-clear">Limpiar Reportes</button>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
    <script>
        function exportarPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            doc.text("Reporte de Historial de Partidas", 10, 10);
            doc.autoTable({ html: "#report-table" });
            doc.save("reporte_historial.pdf");
        }

        document.getElementById('clearReportsButton').addEventListener('click', function () {
            if (confirm("¿Estás seguro de que deseas limpiar los reportes?")) {
                fetch('clear_data.php', {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload(); // Recargar la página para reflejar cambios
                    } else {
                        alert("Error al limpiar los reportes: " + data.error);
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

