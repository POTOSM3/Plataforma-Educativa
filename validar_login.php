<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require 'conexion.php';

$correo = trim($_POST['correo'] ?? '');
$contraseña = trim($_POST['contraseña'] ?? '');

if ($correo === '' || $contraseña === '') {
    die("⚠️ Por favor completa todos los campos.");
}

// Buscar usuario en la DB
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE correo = ?");
$stmt->execute([$correo]);
$usuario_encontrado = $stmt->fetch();

if ($usuario_encontrado && password_verify($contraseña, $usuario_encontrado['contraseña'])) {
    $_SESSION['usuario'] = [
        'nombre' => $usuario_encontrado['nombre'],
        'correo' => $usuario_encontrado['correo'],
        'materia' => $usuario_encontrado['materia']
    ];
    echo "<script>window.location.href='index.php';</script>";
    exit;
} else {
    echo "<h2 style='text-align:center; font-family:Poppins; color:#c0392b;'>❌ Correo o contraseña incorrectos.</h2>";
    echo "<p style='text-align:center;'><a href='login.php'>Volver al inicio de sesión</a></p>";
}
?>
