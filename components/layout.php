<?php
function renderSidebar($usuario, $pagina_activa = 'inicio') {
  $es_admin = isset($usuario['rol']) && $usuario['rol'] === 'administrador';
?>

  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <div class="brand">
      <i data-lucide="graduation-cap"></i>
      <span>EduLive</span>
    </div>

   
    <nav class="menu">
    <?php if ($es_admin): ?>
        <a href="admin_panel.php?section=dashboard" class="<?= $pagina_activa === 'dashboard' ? 'active' : '' ?>">
            <i data-lucide="layout-dashboard"></i> Dashboard
        </a>
        <a href="admin_panel.php?section=content" class="<?= $pagina_activa === 'content' ? 'active' : '' ?>">
            <i data-lucide="book-open-check"></i> Gestión Contenido
        </a>
        <a href="admin_panel.php?section=users" class="<?= $pagina_activa === 'users' ? 'active' : '' ?>">
            <i data-lucide="users"></i> Gestión Usuarios
        </a>
        <a href="admin_panel.php?section=reports" class="<?= $pagina_activa === 'reports' ? 'active' : '' ?>">
            <i data-lucide="bar-chart-3"></i> Reportes
        </a>

    <?php else: ?>
        <a href="index.php" class="<?= $pagina_activa === 'inicio' ? 'active' : '' ?>">
            <i data-lucide="home"></i> Inicio
        </a>
        <a href="cursos.php" class="<?= $pagina_activa === 'cursos' ? 'active' : '' ?>">
            <i data-lucide="book-open"></i> Cursos
        </a>
        <a href="panel.php" class="<?= $pagina_activa === 'panel' ? 'active' : '' ?>">
            <i data-lucide="user-circle"></i> Mi Perfil
        </a>
        <a href="contacto.php" class="<?= $pagina_activa === 'contacto' ? 'active' : '' ?>">
            <i data-lucide="mail"></i> Contacto
        </a>
    <?php endif; ?>

        <a href="cambiar_contrasena.php" class="sidebar-link <?= $pagina_actual == 'cambiar_contrasena' ? 'active' : '' ?>">
            <i data-lucide="key"></i>
            <span>Cambiar Contraseña</span>
        </a>
        <a href="logout.php">
            <i data-lucide="log-out"></i> Cerrar Sesión
        </a>
</a>
    </nav>

    <div class="user-mini">
      <div class="avatar">
        <i data-lucide="user"></i>
      </div>
      <div class="user-info">
        <h4><?= htmlspecialchars($usuario['nombre'] ?? 'Invitado') ?></h4>
        <p><?= htmlspecialchars($usuario['correo'] ?? '') ?></p>
      </div>
    </div>

  <button id="modeBtn" class="mode-toggle">
      <i data-lucide="moon"></i> Oscuro
  </aside>

  <!-- Script: Modo oscuro instantáneo (sin flash) -->
  <script>
    const themeButton = document.getElementById('modeBtn');
    const root = document.body; // El CSS usa body.dark, así que apuntamos a <body>

    // Función para actualizar el botón
    function updateThemeButton(isDarkMode) {
        if (themeButton) {
            themeButton.innerHTML = isDarkMode 
                ? '<i data-lucide="sun"></i> Claro'
                : '<i data-lucide="moon"></i> Oscuro';
            
            // Recargar íconos de Lucide
            if (typeof lucide !== 'undefined' && lucide.createIcons) {
                lucide.createIcons();
            }
        }
    }

    // 1. Inicialización al cargar la página
    (function initTheme() {
      const savedMode = localStorage.getItem('theme');
      
      // La inicialización solo aplica la clase si está en dark
      if (savedMode === 'dark') {
        root.classList.add('dark');
        updateThemeButton(true);
      } else {
        root.classList.remove('dark');
        updateThemeButton(false);
      }
    })();
    
    // 2. Lógica para el botón de alternancia (toggle)
    if (themeButton) {
        themeButton.addEventListener('click', () => {
            const isDarkMode = root.classList.toggle('dark');
            localStorage.setItem('theme', isDarkMode ? 'dark' : 'light');
            updateThemeButton(isDarkMode);
        });
    }

  </script>
<?php
} // Fin de la función renderSidebar
?>