<?php
session_start();
require_once "conexion.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['contacto_error'] = "⚠️ Acceso no permitido. Debes enviar el formulario.";
    header("Location: contacto.php");
    exit;
}

$nombre  = trim($_POST['nombre'] ?? '');
$correo  = trim($_POST['correo'] ?? '');
$mensaje = trim($_POST['mensaje'] ?? '');

// =======================
// Validar datos
// =======================
if ($nombre === '' || $correo === '' || $mensaje === '') {
    $_SESSION['contacto_error'] = "⚠️ Por favor completa todos los campos.";
    header("Location: contacto.php");
    exit;
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['contacto_error'] = "⚠️ Ingresa un correo válido.";
    header("Location: contacto.php");
    exit;
}

if (strlen($nombre) > 100 || strlen($correo) > 150 || strlen($mensaje) > 1000) {
    $_SESSION['contacto_error'] = "⚠️ Algunos campos exceden la longitud permitida.";
    header("Location: contacto.php");
    exit;
}

// =======================
// Guardar en la BD (Sentencia preparada)
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
    
    // Éxito
    $_SESSION['contacto_success'] = "✅ ¡Mensaje enviado con éxito! Te responderemos pronto.";
    header("Location: contacto.php");
    exit;

} catch (PDOException $e) {
    // Error de BD
    $_SESSION['contacto_error'] = "❌ Error al guardar el mensaje. Intenta más tarde. (Detalle: " . $e->getMessage() . ")";
    header("Location: contacto.php");
    exit;
}
?>