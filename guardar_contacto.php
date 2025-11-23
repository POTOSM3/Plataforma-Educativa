<?php
session_start();

// =======================
// 🔌 CONEXIÓN A LA BD
// =======================
require_once "conexion.php";

// =======================
// Validar método POST
// =======================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("⚠️ Acceso no permitido. Debes enviar el formulario.");
}

// =======================
// Obtener datos
// =======================
$nombre  = trim($_POST['nombre'] ?? '');
$correo  = trim($_POST['correo'] ?? '');
$mensaje = trim($_POST['mensaje'] ?? '');

// =======================
// Validar datos
// =======================
if ($nombre === '' || $correo === '' || $mensaje === '') {
    die("⚠️ Por favor completa todos los campos.");
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    die("⚠️ Ingresa un correo válido.");
}

if (strlen($nombre) > 100 || strlen($correo) > 150 || strlen($mensaje) > 1000) {
    die("⚠️ Algunos campos exceden la longitud permitida.");
}

// =======================
// Guardar en la BD
// =======================
try {
    $sql = "INSERT INTO contactos (nombre, correo, mensaje) 
            VALUES (:nombre, :correo, :mensaje)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ":nombre"  => $nombre,
        ":correo"  => $correo,
        ":mensaje" => $mensaje
    ]);
} catch (PDOException $e) {
    die("❌ Error al guardar contacto: " . $e->getMessage());
}

// =======================
// Cargar layout
// =======================
include 'components/layout.php';

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

<?php renderSidebar($_SESSION['usuario'] ?? ['nombre' => 'Invitado'], 'contacto'); ?>

<main class="content" id="content">
  <section class="banner">
    <h1 class="title">✅ ¡Mensaje enviado correctamente!</h1>
    <p class="desc">
      Gracias por escribirnos, <strong><?= htmlspecialchars($nombre) ?></strong>.  
      Te responderemos al correo <strong><?= htmlspecialchars($correo) ?></strong>.
    </p>
    <a href="contacto.php" class="btn">Volver</a>
  </section>
</main>

</body>
</html>