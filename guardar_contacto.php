<?php
session_start();
require_once "recursos/conexion.php";

// =======================
// 📨 GUARDAR CONTACTO
// =======================

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("⚠️ Acceso no permitido. Envía el formulario desde contacto.php");
}

// Recibir datos del formulario
$nombre  = trim($_POST['nombre'] ?? '');
$correo  = trim($_POST['correo'] ?? '');
$mensaje = trim($_POST['mensaje'] ?? '');

// Validaciones
if ($nombre === '' || $correo === '' || $mensaje === '') {
    die("⚠️ Por favor completa todos los campos.");
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    die("⚠️ Ingresa un correo válido.");
}

if(strlen($nombre) > 100 || strlen($correo) > 150 || strlen($mensaje) > 1000){
    die("⚠️ Algunos campos exceden la longitud permitida.");
}

// Guardar en la base de datos
try {
    $sql = "INSERT INTO contactos (nombre, correo, mensaje) 
            VALUES (:nombre, :correo, :mensaje)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nombre' => $nombre,
        ':correo' => $correo,
        ':mensaje'=> $mensaje
    ]);
} catch(PDOException $e) {
    die("❌ Error al guardar contacto: " . $e->getMessage());
}

// =======================
// ✅ MOSTRAR CONFIRMACIÓN
// =======================
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mensaje Enviado - EduLive</title>
<link rel="stylesheet" href="css/style_moderno.css">
</head>
<body>

<?php renderSidebar($_SESSION['usuario'] ?? ['nombre'=>'Invitado'], 'contacto'); ?>

<main class="content" id="content">
<section class="banner">
<h1 class="title">✅ ¡Mensaje enviado correctamente!</h1>
<p class="desc">
Gracias por contactarte con nosotros, <strong><?= htmlspecialchars($nombre) ?></strong>.<br>
Te responderemos al correo <strong><?= htmlspecialchars($correo) ?></strong>.
</p>
<a href="contacto.php" class="btn">Volver al formulario</a>
</section>

<section class="mensajes">
<h2>Mensajes anteriores</h2>
<?php
// Mostrar los mensajes existentes en formato JSON similar a contacto.json
try {
    $stmt = $pdo->query("SELECT nombre, correo, mensaje, fecha FROM contactos ORDER BY fecha DESC");
    $contactos = $stmt->fetchAll();

    echo '<pre>';
    echo json_encode($contactos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    echo '</pre>';

} catch(PDOException $e) {
    echo "❌ Error al obtener los mensajes: " . $e->getMessage();
}
?>
</section>

<footer class="footer">
© <?= date('Y') ?> EduLive — Plataforma Educativa • Hecho con ❤️
</footer>

</main>

<script src="https://unpkg.com/lucide@latest"></script>
<script> lucide.createIcons(); </script>

</body>
</html>