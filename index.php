<?php 
session_start(); 
if (!isset($_SESSION['username'])) { 
    header('Location: login.php'); 
    exit; 
} 
?>
<?php
require_once 'db_config.php';

if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}
$role = $_SESSION['role'];

// Obtener el número de mesas configuradas desde la base de datos
try {
    $stmt = $conn->prepare("SELECT valor FROM configuracion WHERE nombre = :nombre");
    $stmt->execute(['nombre' => 'num_mesas']);
    $num_mesas = (int)$stmt->fetchColumn(); // Asegurarse de que sea un número entero

    // Si no se encuentra un valor configurado, usar 6 por defecto
    if ($num_mesas <= 0) {
        $num_mesas = 6;
    }
} catch (PDOException $e) {
    die("Error al obtener la configuración: s" . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billar</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <nav class="nav expanded" id="sidebar">
        <button class="nav__toggle" onclick="toggleSidebar()">&#9776;</button>
        <div class="usuario">
            <img class="icon-user" src="Icons/user_icon.png" alt="icono usuario">
            <div class="texts-user">
                <span class="text"><?php echo ucfirst($role); ?></span>
                <?php echo $_SESSION["username"]; ?>
            </div>
        </div>
        <ul class="nav__list">
            <?php if ($role == 'admin' || $role == 'trabajador') { ?>
                <li class="nav_li">
                    <a class="cta" href="index.php">       
                        <img class="icons" src="Icons/billiards_icon.png" alt="icono mesas">
                        Mesas
                    </a>
                </li>
                <li class="nav_li"> 
                    <a class="cta" href="historial.php">
                        <img class="icons" src="Icons/historial_icon.png" alt="icono historial">
                        Historial
                    </a>
                </li>
                <li class="nav_li"> 
                    <a class="cta" href="reportes.php">
                        <img class="icons" src="Icons/report_icon.png" alt="icono reportes">
                        Reportes
                    </a>
                </li>
            <?php } ?>
            <?php if ($role == 'admin') { ?>
                <li class="nav_li">
                    <a href="dashboard.php" class="cta">
                        <img class="icons" src="Icons/dashboard_icon.png" alt="icono dashboard">
                        Dashboard
                    </a>
                </li>
                <li class="nav_li">   
                    <a class="cta" href="configuracion.php">
                        <img class="icons" src="Icons/settings_icon.png" alt="icono configuración">
                        Configuración
                        
                    </a>
                    <br>
                    <br>
                </li>
            <?php } ?>
        </ul>
        <div class="logout">
            <a href="cerrarSesion.php"><img class="icons" src="Icons/logout_icon.png" alt=""></a>
        </div>
    </nav>
    <main>
        <div class="container">
            <h1>Gestión de Mesas de Billar</h1>
            <div class="tables">
                <?php for ($i = 1; $i <= $num_mesas; $i++) { ?>
                    <div id="table<?php echo $i; ?>" class="table">
                        <h3>Mesa <?php echo $i; ?></h3>
                        <img class="img__mesas" src="img/mesaBillar-removebg-preview.png" alt="">
                        <button onclick="toggleRental(<?php echo $i; ?>)">Alquilar</button>
                        <div class="status"></div>
                        <div class="timer" id="timer<?php echo $i; ?>"></div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </main>
    <!-- Modal para ingresar tiempo -->
    <div id="rentalModal" class="modal" style="display:none;">
        <div class="modal-content">
            <div class="sub-modal">
               <h2>ALQUILAR MESA</h2>

               <div class="cuadroshm">
                    <label for="hoursInput">H:</label>
                    <input type="number" id="hoursInput" min="0" value="0">

                    <label for="minutesInput">M:</label>
                    <input type="number" id="minutesInput" min="0" max="59" value="0">
               </div>

               <!-- NUEVO: Campos para cliente -->
               <div style="margin-top: 30px;">
                    <label for="clienteNombre">Nombre del cliente:</label>
                    <input type="text" id="clienteNombre" required>
                    <label for="clienteDni">DNI:</label>
                    <input type="text" id="clienteDni" required>
               </div>
            </div>

            <div class="modal-actions">
                <button id="modalAccept" class="buton"><span>Iniciar</span></button>
                <button id="modalCancel" class="buton"><span>Cancelar</span></button>
            </div>
        </div>
    </div>
    <script src="script.js"></script>
    <script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('expanded');
}
</script>
</body>
</html>
