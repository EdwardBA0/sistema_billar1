<?php
// Conectar a la base de datos
require 'db_config.php'; // Asegúrate de que este archivo esté configurado correctamente

// Incluir Dompdf antes de usar la clase
require_once 'vendor/autoload.php';

use Dompdf\Dompdf;

// Obtener los datos
try {
    $stmt = $conn->query("SELECT mesa, hora_inicio, hora_fin, duracion, precio FROM reportes");

    // Generar HTML para el PDF
    $html = '
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        h1 {
            text-align: center;
            color: #333;
            font-size: 24px;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #f4f4f4;
            color: #333;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #777;
        }
    </style>';

    $html .= '<h1>Reporte de Historial de Mesas</h1>';
    $html .= '<table>';
    $html .= '<thead>
                <tr>
                    <th>Mesa</th>
                    <th>Hora de Inicio</th>
                    <th>Hora de Fin</th>
                    <th>Duración</th>
                    <th>Precio (S/)</th>
                </tr>
              </thead>';
    $html .= '<tbody>';

    if ($stmt->rowCount() > 0) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $html .= '<tr>';
            $html .= '<td>' . $row['mesa'] . '</td>';
            $html .= '<td>' . $row['hora_inicio'] . '</td>';
            $html .= '<td>' . $row['hora_fin'] . '</td>';
            $html .= '<td>' . $row['duracion'] . '</td>';
            $html .= '<td>S/ ' . number_format($row['precio'], 2) . '</td>';
            $html .= '</tr>';
        }
    } else {
        $html .= '<tr><td colspan="5">No hay datos disponibles</td></tr>';
    }
    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '<div class="footer">Reporte generado automáticamente el ' . date('d/m/Y H:i:s') . '</div>';

    // Crear instancia de Dompdf y cargar el HTML
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("reporte_historial.pdf", array("Attachment" => false));

} catch (PDOException $e) {
    echo "Error en la consulta: " . $e->getMessage();
}
