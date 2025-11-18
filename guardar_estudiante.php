<?php
session_start();
require 'conexion.php'; // Conexión a la DB
$nombre = trim($_POST['nombre'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$contraseña = trim($_POST['contraseña'] ?? '');
$materia = trim($_POST['materia'] ?? '');
$fecha = date('Y-m-d H:i:s');

if ($nombre === '' || $correo === '' || $contraseña === '' || $materia === '') {
    die("⚠️ Por favor completa todos los campos.");
}
// --- 1️⃣ Guardar en la base de datos ---
$stmt = $pdo->prepare("SELECT id FROM usuarios WHERE correo = ?");
$stmt->execute([$correo]);
if ($stmt->rowCount() > 0) {
    die("❌ Este correo ya está registrado. <a href='registro.php'>Volver</a>");
}
$hash_contraseña = password_hash($contraseña, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO usuarios (nombre, correo, contraseña, materia, fecha_registro) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$nombre, $correo, $hash_contraseña, $materia, $fecha]);
// --- 2️⃣ Guardar en el JSON ---
$archivo_json = 'data/usuarios.json';
// Crear archivo si no existe
if (!file_exists($archivo_json)) {
    file_put_contents($archivo_json, json_encode([]));
}
// Leer los usuarios existentes
$usuarios_json = json_decode(file_get_contents($archivo_json), true);

// Agregar el nuevo usuario (misma estructura que antes)
$nuevo_usuario_json = [
    'nombre' => $nombre,
    'correo' => $correo,
    'contraseña' => $hash_contraseña,
    'materia' => $materia,
    'fecha_registro' => $fecha
];
$usuarios_json[] = $nuevo_usuario_json;
// Guardar nuevamente en JSON
file_put_contents($archivo_json, json_encode($usuarios_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
// --- Redirigir al login ---
echo "<script>alert('Usuario registrado correctamente ✅'); window.location.href='login.php';</script>";
exit;
?>
