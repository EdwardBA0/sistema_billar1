<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
require_once 'db_config.php';

// Clientes más frecuentes (por nombre y DNI)
$stmt = $conn->prepare("
    SELECT cliente_nombre, cliente_dni, COUNT(*) as veces
    FROM reportes
    WHERE cliente_nombre IS NOT NULL AND cliente_dni IS NOT NULL
    GROUP BY cliente_nombre, cliente_dni
    ORDER BY veces DESC
    LIMIT 10
");
$stmt->execute();
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Clientes nuevos (últimos 10 registros únicos)
$stmt2 = $conn->prepare("
    SELECT cliente_nombre, cliente_dni, MAX(hora_inicio) as ultima_vez
    FROM reportes
    WHERE cliente_nombre IS NOT NULL AND cliente_dni IS NOT NULL
    GROUP BY cliente_nombre, cliente_dni
    ORDER BY ultima_vez DESC
    LIMIT 10
");
$stmt2->execute();
$nuevos = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// Mesas más usadas durante el mes actual
$stmt3 = $conn->prepare("
    SELECT mesa, COUNT(*) as usos
    FROM reportes
    WHERE MONTH(hora_inicio) = MONTH(CURRENT_DATE()) AND YEAR(hora_inicio) = YEAR(CURRENT_DATE())
    GROUP BY mesa
    ORDER BY usos DESC
    LIMIT 5
");
$stmt3->execute();
$mesas_mas_usadas = $stmt3->fetchAll(PDO::FETCH_ASSOC);

// Mesas menos usadas durante el mes actual
$stmt4 = $conn->prepare("
    SELECT mesa, COUNT(*) as usos
    FROM reportes
    WHERE MONTH(hora_inicio) = MONTH(CURRENT_DATE()) AND YEAR(hora_inicio) = YEAR(CURRENT_DATE())
    GROUP BY mesa
    ORDER BY usos ASC
    LIMIT 5
");
$stmt4->execute();
$mesas_menos_usadas = $stmt4->fetchAll(PDO::FETCH_ASSOC);

// Dinero ganado por día (últimos 30 días)
$stmt5 = $conn->prepare("
    SELECT DATE(hora_inicio) as fecha, SUM(precio) as total
    FROM reportes
    WHERE hora_inicio IS NOT NULL
    GROUP BY DATE(hora_inicio)
    ORDER BY fecha DESC
    LIMIT 30
");
$stmt5->execute();
$dinero_por_dia = $stmt5->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard de Clientes y Mesas</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="dashboard-container">
        <h1>Dashboard de Clientes y Mesas</h1>
        <h2>Clientes más frecuentes</h2>
        <canvas id="clientesFrecuentesChart" width="400" height="200"></canvas>
        <table>
            <tr>
                <th>Nombre</th>
                <th>DNI</th>
                <th>Veces que alquiló</th>
            </tr>
            <?php foreach ($clientes as $c): ?>
            <tr>
                <td><?= htmlspecialchars($c['cliente_nombre']) ?></td>
                <td><?= htmlspecialchars($c['cliente_dni']) ?></td>
                <td><?= $c['veces'] ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <h2>Mesas más usadas este mes</h2>
        <canvas id="mesasUsadasChart" width="400" height="200"></canvas>
        <table>
            <tr>
                <th>Mesa</th>
                <th>Usos</th>
            </tr>
            <?php foreach ($mesas_mas_usadas as $m): ?>
            <tr>
                <td><?= htmlspecialchars($m['mesa']) ?></td>
                <td><?= $m['usos'] ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <h2>Dinero ganado por día (últimos 30 días)</h2>
        <canvas id="dineroPorDiaChart" width="400" height="200"></canvas>
        <table>
            <tr>
                <th>Fecha</th>
                <th>Total S/</th>
            </tr>
            <?php foreach ($dinero_por_dia as $d): ?>
            <tr>
                <td><?= htmlspecialchars($d['fecha']) ?></td>
                <td><?= number_format($d['total'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <script>
        // Datos desde PHP
        const clientesLabels = <?= json_encode(array_map(fn($c) => $c['cliente_nombre'] . ' (' . $c['cliente_dni'] . ')', $clientes)) ?>;
        const clientesData = <?= json_encode(array_map(fn($c) => (int)$c['veces'], $clientes)) ?>;

        const mesasLabels = <?= json_encode(array_map(fn($m) => $m['mesa'], $mesas_mas_usadas)) ?>;
        const mesasData = <?= json_encode(array_map(fn($m) => (int)$m['usos'], $mesas_mas_usadas)) ?>;

        const dineroLabels = <?= json_encode(array_map(fn($d) => $d['fecha'], $dinero_por_dia)) ?>;
        const dineroData = <?= json_encode(array_map(fn($d) => (float)$d['total'], $dinero_por_dia)) ?>;

        // Gráfico de pastel para clientes más frecuentes
        new Chart(document.getElementById('clientesFrecuentesChart'), {
            type: 'pie',
            data: {
                labels: clientesLabels,
                datasets: [{
                    data: clientesData,
                    backgroundColor: [
                        '#2980b9', '#27ae60', '#e67e22', '#8e44ad', '#c0392b',
                        '#16a085', '#f39c12', '#d35400', '#2c3e50', '#7f8c8d'
                    ]
                }]
            }
        });

        // Gráfico de barras para mesas más usadas
        new Chart(document.getElementById('mesasUsadasChart'), {
            type: 'bar',
            data: {
                labels: mesasLabels,
                datasets: [{
                    label: 'Usos',
                    data: mesasData,
                    backgroundColor: '#2980b9'
                }]
            }
        });

        // Gráfico de líneas para dinero por día
        new Chart(document.getElementById('dineroPorDiaChart'), {
            type: 'line',
            data: {
                labels: dineroLabels,
                datasets: [{
                    label: 'Total S/',
                    data: dineroData,
                    borderColor: '#27ae60',
                    backgroundColor: 'rgba(39,174,96,0.2)',
                    fill: true
                }]
            }
        });
    </script>
    <style>
        body {
            background: #eaf3fa; /* Un azul claro, puedes cambiarlo */
        }
        .dashboard-container {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(44, 62, 80, 0.13);
            padding: 40px 32px;
            margin: 40px auto;
            max-width: 950px;
        }
    </style>
</body>
</html>

