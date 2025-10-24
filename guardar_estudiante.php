<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Recibir datos del formulario
$nombre = trim($_POST['nombre'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$contraseña = trim($_POST['contraseña'] ?? '');
$materia = trim($_POST['materia'] ?? '');

// Validaciones básicas
if ($nombre === '' || $correo === '' || $contraseña === '' || $materia === '') {
    die("⚠️ Por favor completa todos los campos antes de continuar.");
}

// Ruta del archivo JSON donde se guardan los usuarios
$archivo = 'data/usuarios.json';

// Si no existe, crear archivo vacío
if (!file_exists($archivo)) {
    file_put_contents($archivo, '[]');
}

// Leer usuarios existentes
$usuarios = json_decode(file_get_contents($archivo), true);

// Verificar si el correo ya existe
foreach ($usuarios as $u) {
    if (strtolower($u['correo']) === strtolower($correo)) {
        die("❌ El correo ya está registrado. Usa otro o inicia sesión.");
    }
}

// Crear nuevo usuario con contraseña encriptada
$nuevo_usuario = [
    "nombre" => $nombre,
    "correo" => $correo,
    "contraseña" => password_hash($contraseña, PASSWORD_DEFAULT),
    "materia" => $materia,
    "fecha_registro" => date("Y-m-d H:i:s")
];

// Agregar usuario al arreglo
$usuarios[] = $nuevo_usuario;

// Guardar en el archivo JSON
file_put_contents($archivo, json_encode($usuarios, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro Exitoso - Plataforma Educativa</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <header class="encabezado">
        <div class="logo">
            <i data-lucide="graduation-cap"></i>
            <h1>Plataforma Educativa</h1>
        </div>
    </header>

    <main class="principal">
        <section class="bienvenida">
            <h2>✅ ¡Registro completado!</h2>
            <p>Bienvenido, <strong><?= htmlspecialchars($nombre) ?></strong>. Ya puedes iniciar sesión para acceder a tus cursos.</p>
            <a href="login.php" class="btn">Iniciar Sesión</a>
        </section>
    </main>

    <footer>
        <p>© <?= date('Y') ?> Plataforma Educativa | Equipo de Desarrollo Web</p>
    </footer>

    <script>lucide.createIcons();</script>
</body>
</html>
