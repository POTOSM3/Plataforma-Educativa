<?php
session_start();
if (!isset($_SESSION['usuario'])) {
  header("Location: login.php");
  exit;
}

$usuario = $_SESSION['usuario'];
include 'components/layout.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contacto - EduLive</title>
  <link rel="stylesheet" href="css/style_moderno.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>

<?php renderSidebar($usuario, 'contacto'); ?>

<main class="content" id="content">
  <section class="banner">
    <h1 class="title">✉️ Contáctanos</h1>
    <p class="desc">¿Tienes dudas o sugerencias? Envíanos un mensaje y te responderemos muy pronto.</p>
  </section>

  <section class="contact-section">
    <div class="contact-header">
      <i data-lucide="mail"></i>
      <h2>Envíanos un mensaje</h2>
      <p>Queremos saber de ti. Cuéntanos tus sugerencias o comentarios.</p>
    </div>

    <form class="contact-form" action="guardar_contacto.php" method="POST">
      <label for="nombre">Nombre completo</label>
      <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($usuario['nombre']) ?>" required>

      <label for="correo">Correo electrónico</label>
      <input type="email" id="correo" name="correo" value="<?= htmlspecialchars($usuario['correo']) ?>" required>

      <label for="mensaje">Mensaje</label>
      <textarea id="mensaje" name="mensaje" rows="4" required></textarea>

      <button type="submit" class="btn-send">Enviar mensaje</button>
    </form>
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
