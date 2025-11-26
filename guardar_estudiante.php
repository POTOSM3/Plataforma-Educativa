<?php
session_start();
require 'conexion.php'; // Conexión a la DB

$nombre = trim($_POST['nombre'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$contraseña = trim($_POST['contraseña'] ?? '');
$materia = trim($_POST['materia'] ?? '');
$fecha = date('Y-m-d H:i:s');

// =======================
// Validar campos
// =======================
if ($nombre === '' || $correo === '' || $contraseña === '' || $materia === '') {
    $_SESSION['registro_error'] = "⚠️ Por favor completa todos los campos.";
    header("Location: registro.php"); // Redirige a registro.php
    exit;
}

// ----------------------------------------------------
// 1️⃣ Guardar en la base de datos (DB)
// ----------------------------------------------------
try {
    // Verificar si el correo ya existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE correo = ?");
    $stmt->execute([$correo]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['registro_error'] = "❌ Este correo ya está registrado. Intenta iniciar sesión.";
        header("Location: registro.php");
        exit;
    }
    
    // Insertar nuevo usuario
    $hash_contraseña = password_hash($contraseña, PASSWORD_DEFAULT);
    $sql = "INSERT INTO usuarios (nombre, correo, contraseña, materia, fecha_registro) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nombre, $correo, $hash_contraseña, $materia, $fecha]);
    
    // Éxito
    $_SESSION['registro_success'] = "¡Registro exitoso! Ya puedes iniciar sesión.";
    header("Location: login.php"); // Redirige a login.php
    exit;

} catch (PDOException $e) {
    // Manejo de errores de BD
    $_SESSION['registro_error'] = "❌ Error al intentar registrarte. Intenta de nuevo. (Detalle: " . $e->getMessage() . ")";
    header("Location: registro.php");
    exit;
}

// ----------------------------------------------------
// ❌ Código JSON anterior ELIMINADO:
// ----------------------------------------------------
/* $archivo_json = 'data/usuarios.json';
// ... código para file_put_contents, file_get_contents, json_decode/encode...
*/
?>