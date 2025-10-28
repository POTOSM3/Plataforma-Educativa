<?php
// =======================
// 📨 GUARDAR CONTACTO
// =======================

session_start();

// Validar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: contacto.php");
  exit;
}

// Obtener datos del formulario
$nombre = trim($_POST['nombre'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$mensaje = trim($_POST['mensaje'] ?? '');

// Validar campos
if ($nombre === '' || $correo === '' || $mensaje === '') {
  die("⚠️ Por favor, completa todos los campos antes de enviar tu mensaje.");
}

// Ruta del archivo JSON
$archivo = "data/contactos.json";

// Leer contactos existentes
$contactos = [];
if (file_exists($archivo)) {
  $contenido = file_get_contents($archivo);
  $decodificado = json_decode($contenido, true);
  if (is_array($decodificado)) {
    $contactos = $decodificado;
  }
}

// Crear nuevo mensaje
$nuevo_contacto = [
  "nombre" => $nombre,
  "correo" => $correo,
  "mensaje" => $mensaje,
  "fecha" => date("Y-m-d H:i:s")
];

// Agregar al arreglo
$contactos[] = $nuevo_contacto;

// Guardar en JSON
file_put_contents($archivo, json_encode($contactos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

include 'components/layout.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mensaje Enviado - EduLive</title>
  <link rel="stylesheet" href="css/style_moderno.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>

<?php renderSidebar($_SESSION['usuario'] ?? ['nombre' => 'Invitado'], 'contacto'); ?>

<main class="content" id="content">
  <section class="banner">
    <h1 class="title">✅ ¡Mensaje enviado correctamente!</h1>
    <p class="desc">Gracias por contactarte con nosotros, <strong><?= htmlspecialchars($nombre) ?></strong>.  
    Te responderemos lo antes posible al correo <strong><?= htmlspecialchars($correo) ?></strong>.</p>
    <a href="contacto.php" class="btn">Volver al formulario</a>
  </section>

  <footer class="footer">
    © <?= date('Y') ?> EduLive — Plataforma Educativa • Hecho con ❤️
  </footer>
</main>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
  lucide.createIcons();

  function toggleSidebar() {
    document.getElementById("sidebar").classList.toggle("open");
    document.getElementById("content").classList.toggle("push");
  }

  // 🌙 Persistencia del modo oscuro
  const body = document.body;
  const modeBtn = document.getElementById("modeBtn");
  const savedMode = localStorage.getItem("theme");

  if (savedMode === "dark") {
    body.classList.add("dark");
    modeBtn.innerHTML = '<i data-lucide="sun"></i> Claro';
  } else {
    body.classList.remove("dark");
    modeBtn.innerHTML = '<i data-lucide="moon"></i> Oscuro';
  }

  modeBtn.addEventListener("click", () => {
    const isDark = body.classList.toggle("dark");
    localStorage.setItem("theme", isDark ? "dark" : "light");
    modeBtn.innerHTML = isDark
      ? '<i data-lucide="sun"></i> Claro'
      : '<i data-lucide="moon"></i> Oscuro';
    lucide.createIcons();
  });
</script>
</body>
</html>
