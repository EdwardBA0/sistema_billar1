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
    <title>Dashboard - Clientes</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="dashboard-container">
        <h1>Dashboard de Clientes y Mesas</h1>
        <h2>Clientes más frecuentes</h2>
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
        <h2>Clientes nuevos (últimos registros únicos)</h2>
        <table>
            <tr>
                <th>Nombre</th>
                <th>DNI</th>
                <th>Última vez</th>
            </tr>
            <?php foreach ($nuevos as $n): ?>
            <tr>
                <td><?= htmlspecialchars($n['cliente_nombre']) ?></td>
                <td><?= htmlspecialchars($n['cliente_dni']) ?></td>
                <td><?= $n['ultima_vez'] ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <h2>Mesas más usadas este mes</h2>
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
        <h2>Mesas menos usadas este mes</h2>
        <table>
            <tr>
                <th>Mesa</th>
                <th>Usos</th>
            </tr>
            <?php foreach ($mesas_menos_usadas as $m): ?>
            <tr>
                <td><?= htmlspecialchars($m['mesa']) ?></td>
                <td><?= $m['usos'] ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <h2>Dinero ganado por día (últimos 30 días)</h2>
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
</body>
</html>

<label for="clienteNombre">Nombre del cliente:</label>
<input type="text" id="clienteNombre" required>
<label for="clienteDni">DNI:</label>
<input type="text" id="clienteDni" required>