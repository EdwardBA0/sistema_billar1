<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración</title>
    <link rel="stylesheet" href="configuracion.css">
</head>
<body class="background">
    <div class="container centered">
        <h1 class="page-title">Configuración</h1>
        <form class="config-form" method="post" action="configuracion.php">
            <label for="new-table">Número de mesas:</label>
            <input type="number" id="new-table" name="num_mesas" required>
            
            <label for="price-per-hour">Precio por hora (S/):</label>
            <input type="number" id="price-per-hour" name="precio_hora" step="0.01" required>
            
            <button type="submit" name="guardar_configuracion">Guardar Configuración</button>
        </form>
        <a href="index.php">Volver al inicio</a>
    </div>

    <?php
    require_once 'db_config.php';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_configuracion'])) {
        $num_mesas = intval($_POST['num_mesas']);
        $precio_hora = floatval($_POST['precio_hora']);

        try {
            // Eliminar las configuraciones anteriores
            $stmt = $conn->prepare("DELETE FROM configuracion WHERE nombre = :nombre");
            $stmt->execute(['nombre' => 'num_mesas']);
            $stmt->execute(['nombre' => 'precio_hora']);

            // Insertar nuevas configuraciones
            $stmt = $conn->prepare("INSERT INTO configuracion (nombre, valor) VALUES (:nombre, :valor)");
            $stmt->execute(['nombre' => 'num_mesas', 'valor' => $num_mesas]);
            $stmt->execute(['nombre' => 'precio_hora', 'valor' => $precio_hora]);

            echo "<p>Configuración guardada exitosamente.</p>";
        } catch (PDOException $e) {
            echo "<p>Error al guardar la configuración: " . $e->getMessage() . "</p>";
        }
    }
    ?>
</body>
</html>
