<?php
// components/layout.php
if (session_status() === PHP_SESSION_NONE) session_start();
$usuario = $_SESSION['usuario'] ?? null;
$page_title = $page_title ?? 'Plataforma Educativa';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title><?= htmlspecialchars($page_title) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="css/style_moderno.css">
</head>
<body class="<?= isset($_COOKIE['theme']) && $_COOKIE['theme']==='dark' ? 'dark' : '' ?>">
  <!-- Toggle (mobile) -->
  <button class="toggle-btn" id="btnToggle">
    <i data-lucide="menu"></i>
  </button>

  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <div class="brand">
      <i data-lucide="graduation-cap"></i>
      <span>Edu<span>Live</span></span>
    </div>

    <nav class="nav">
      <a class="nav-item" href="index.php"><i data-lucide="home"></i><span>Inicio</span></a>
      <a class="nav-item" href="temas.php"><i data-lucide="library"></i><span>Cursos</span></a>
      <a class="nav-item" href="panel.php"><i data-lucide="bar-chart-3"></i><span>Mi Panel</span></a>
      <a class="nav-item" href="contacto.php"><i data-lucide="mail"></i><span>Contacto</span></a>
      <?php if ($usuario): ?>
        <a class="nav-item" href="logout.php"><i data-lucide="log-out"></i><span>Cerrar sesión</span></a>
      <?php else: ?>
        <a class="nav-item" href="login.php"><i data-lucide="log-in"></i><span>Iniciar sesión</span></a>
      <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
      <button class="mode-btn" id="themeBtn">
        <i data-lucide="<?= (isset($_COOKIE['theme']) && $_COOKIE['theme']==='dark') ? 'sun' : 'moon' ?>"></i>
        <span><?= (isset($_COOKIE['theme']) && $_COOKIE['theme']==='dark') ? 'Claro' : 'Oscuro' ?></span>
      </button>
      <?php if ($usuario): ?>
        <div class="user-mini">
          <i data-lucide="user"></i>
          <div>
            <strong><?= htmlspecialchars($usuario['nombre']) ?></strong>
            <small><?= htmlspecialchars($usuario['correo']) ?></small>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </aside>

  <!-- Contenido -->
  <main class="content" id="content">
    <?= $content ?? '' ?>
    <footer class="footer">
      © <?= date('Y') ?> EduLive — Plataforma Educativa • Hecho con ❤️
    </footer>
  </main>

  <script>
    lucide.createIcons();

    // Abrir/cerrar sidebar en móvil
    const btnToggle = document.getElementById('btnToggle');
    const sidebar = document.getElementById('sidebar');
    const content = document.getElementById('content');
    btnToggle?.addEventListener('click', () => {
      sidebar.classList.toggle('open');
      content.classList.toggle('push');
    });

    // Modo oscuro (guarda en cookie)
    const themeBtn = document.getElementById('themeBtn');
    themeBtn?.addEventListener('click', () => {
      document.body.classList.toggle('dark');
      const isDark = document.body.classList.contains('dark');
      document.cookie = 'theme=' + (isDark ? 'dark' : 'light') + '; path=/; max-age=' + (60*60*24*365);
      themeBtn.innerHTML = isDark
        ? '<i data-lucide="sun"></i><span>Claro</span>'
        : '<i data-lucide="moon"></i><span>Oscuro</span>';
      lucide.createIcons();
    });
  </script>
</body>
</html>
