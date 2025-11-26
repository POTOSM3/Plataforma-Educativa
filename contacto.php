<?php
session_start();
if (!isset($_SESSION['usuario'])) {
  header("Location: login.php");
  exit;
}

// Ya no necesitamos la conexión aquí, ya que solo estamos mostrando el formulario.
// Si necesitas datos extra del usuario (como nombre y correo) ya están en $_SESSION.
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
  <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body style="background-color: #141a29;">

<?php renderSidebar($usuario, 'contacto'); ?>

<main class="content" id="content">
  
  <section class="banner">
    <h1 class="title">✉️ Contáctanos</h1>
    <p class="desc">¿Tienes dudas o sugerencias? Envíanos un mensaje y te responderemos muy pronto.</p>
  </section>

  <section class="contact-layout"> 
      
      <div class="form-wrapper">
          <div class="contact-header">
              <i data-lucide="send"></i>
              <h2>Envía tu Consulta</h2>
              <p>Utiliza este formulario para comunicarte con nuestro equipo de soporte.</p>
          </div>
          <form class="contact-form" action="guardar_contacto.php" method="POST">
              <label for="nombre">Nombre</label>
              <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($usuario['nombre'] ?? '') ?>" required>
              
              <label for="correo">Correo electrónico</label>
              <input type="email" id="correo" name="correo" value="<?= htmlspecialchars($usuario['correo'] ?? '') ?>" required>

              <label for="mensaje">Mensaje</label>
              <textarea id="mensaje" name="mensaje" rows="6" required></textarea>

              <button type="submit" class="btn-send">Enviar mensaje</button>
          </form>
      </div>

      <div class="info-wrapper">
          <h3>Información Adicional</h3>
          <div class="info-block">
              <i data-lucide="map-pin"></i>
              <p><strong>Ubicación:</strong> Centro de Innovación, Ciudad Educativa</p>
          </div>
          <div class="info-block">
              <i data-lucide="phone"></i>
              <p><strong>Teléfono:</strong> +503 1234-5678</p>
          </div>
          
          
          <div class="social-links">
              <h4>Síguenos en Redes</h4>
              <a href="#" target="_blank"><i data-lucide="twitter"></i></a>
              <a href="#" target="_blank"><i data-lucide="facebook"></i></a>
              <a href="#" target="_blank"><i data-lucide="instagram"></i></a>
          </div>
      </div>
  </section>
  
  <footer class="footer">
    © <?= date('Y') ?> EduLive — Plataforma Educativa • Hecho con ❤️
  </footer>
</main>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
  lucide.createIcons();
  
  // --- LÓGICA DEL BOTÓN DE MODO OSCURO/CLARO ---
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
    if (isDark) {
      localStorage.setItem("theme", "dark");
      modeBtn.innerHTML = '<i data-lucide="sun"></i> Claro';
    } else {
      localStorage.setItem("theme", "light");
      modeBtn.innerHTML = '<i data-lucide="moon"></i> Oscuro';
    }
    lucide.createIcons();
  });
</script>

</body>
</html>