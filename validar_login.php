<?php
// validar_login.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require 'conexion.php';

$correo = trim($_POST['correo'] ?? '');
$contraseña = trim($_POST['contraseña'] ?? '');

// 1. Manejo de campos vacíos
if ($correo === '' || $contraseña === '') {
    $_SESSION['login_error'] = "⚠️ Por favor completa todos los campos.";
    header("Location: login.php");
    exit;
}

try {
    // 2. Buscar usuario en la DB
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE correo = ?");
    $stmt->execute([$correo]);
    $usuario_encontrado = $stmt->fetch();

    // 3. Verificar credenciales
    if ($usuario_encontrado && password_verify($contraseña, $usuario_encontrado['contraseña'])) {
        // ÉXITO: Iniciar sesión
        $_SESSION['usuario'] = [
            'nombre' => $usuario_encontrado['nombre'],
            'correo' => $usuario_encontrado['correo'],
            'materia' => $usuario_encontrado['materia']
            // NOTA: Si usas roles, agrégalo aquí también.
        ];
        
        // Limpiar errores previos
        unset($_SESSION['login_error']); 
        header("Location: index.php");
        exit;
    } else {
        // FALLO: Credenciales incorrectas
        $_SESSION['login_error'] = "❌ Correo o contraseña incorrectos.";
        // Redirige de vuelta a la página de login
        header("Location: login.php");
        exit;
    }
} catch (PDOException $e) {
    // Manejo de error de base de datos
    $_SESSION['login_error'] = "❌ Error en el servidor. Intenta de nuevo más tarde.";
    error_log("Error de Login: " . $e->getMessage());
    header("Location: login.php");
    exit;
}
?>