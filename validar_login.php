<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

$correo = trim($_POST['correo'] ?? '');
$contraseña = trim($_POST['contraseña'] ?? '');

if ($correo === '' || $contraseña === '') {
    die("⚠️ Por favor completa todos los campos.");
}

$archivo = 'data/usuarios.json';
if (!file_exists($archivo)) {
    die("❌ No hay usuarios registrados todavía.");
}

$usuarios = json_decode(file_get_contents($archivo), true);
$usuario_encontrado = null;

foreach ($usuarios as $u) {
    if (strtolower($u['correo']) === strtolower($correo) && password_verify($contraseña, $u['contraseña'])) {
        $usuario_encontrado = $u;
        break;
    }
}

if ($usuario_encontrado) {
    $_SESSION['usuario'] = [
        'nombre' => $usuario_encontrado['nombre'],
        'correo' => $usuario_encontrado['correo'],
        'materia' => $usuario_encontrado['materia']
    ];
    // Redirección segura (sin errores de header)
    echo "<script>window.location.href='index.php';</script>";
    exit;
} else {
    echo "<h2 style='text-align:center; font-family:Poppins; color:#c0392b;'>❌ Correo o contraseña incorrectos.</h2>";
    echo "<p style='text-align:center;'><a href='login.php'>Volver al inicio de sesión</a></p>";
}
?>
