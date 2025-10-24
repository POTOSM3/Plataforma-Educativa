<?php
// Validación básica
$nombre = trim($_POST['nombre'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$mensaje = trim($_POST['mensaje'] ?? '');

if ($nombre === '' || $correo === '' || $mensaje === '') {
    die("Por favor completa todos los campos.");
}

// Guardar en JSON
$archivo = 'data/contactos.json';
$contactos = file_exists($archivo) ? json_decode(file_get_contents($archivo), true) : [];

$contactos[] = [
    "nombre" => $nombre,
    "correo" => $correo,
    "mensaje" => $mensaje,
    "fecha" => date("Y-m-d H:i:s")
];

file_put_contents($archivo, json_encode($contactos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mensaje Enviado - Plataforma Educativa</title>
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
            <h2>✅ ¡Mensaje enviado correctamente!</h2>
            <p>Gracias, <strong><?= htmlspecialchars($nombre) ?></strong>. Hemos recibido tu mensaje y te responderemos pronto.</p>
            <a href="index.php" class="btn">Volver al inicio</a>
        </section>
    </main>

    <footer>
        <p>© <?= date('Y') ?> Plataforma Educativa | Equipo de Desarrollo Web</p>
    </footer>

    <script>lucide.createIcons();</script>
</body>
</html>
