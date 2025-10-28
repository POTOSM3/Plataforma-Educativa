<?php
function renderSidebar($usuario, $pagina_activa = 'inicio') {
?>
  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <div class="brand">
      <i data-lucide="graduation-cap"></i>
      <span>EduLive</span>
    </div>

    <nav class="menu">
      <a href="index.php" class="<?= $pagina_activa === 'inicio' ? 'active' : '' ?>">
        <i data-lucide="home"></i> Inicio
      </a>
      <a href="cursos.php" class="<?= $pagina_activa === 'cursos' ? 'active' : '' ?>">
        <i data-lucide="book-open"></i> Cursos
      </a>
      <a href="panel.php" class="<?= $pagina_activa === 'panel' ? 'active' : '' ?>">
        <i data-lucide="layout-dashboard"></i> Mi Panel
      </a>
      <a href="contacto.php" class="<?= $pagina_activa === 'contacto' ? 'active' : '' ?>">
        <i data-lucide="mail"></i> Contacto
      </a>
      <a href="logout.php">
        <i data-lucide="log-out"></i> Cerrar sesión
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
    </button>
  </aside>

  <!-- Script: Modo oscuro instantáneo (sin flash) -->
  <script>
    (function() {
      const savedMode = localStorage.getItem('theme');
      if (savedMode === 'dark') {
        document.documentElement.classList.add('dark');
        document.documentElement.style.background = '#0f172a';
        document.body && (document.body.style.background = '#0f172a');
      } else {
        document.documentElement.classList.remove('dark');
        document.documentElement.style.background = '#f1f5f9';
        document.body && (document.body.style.background = '#f1f5f9');
      }
    })();
  </script>
<?php
}
?>
