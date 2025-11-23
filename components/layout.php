<?php
function renderSidebar($usuario, $pagina_activa = 'inicio') {
?>
<!--------------------------- SIDEBAR --------------------------->
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

    <!-- Botón modo oscuro -->
    <button id="modeBtn" class="mode-toggle">
        <i data-lucide="moon"></i> Modo oscuro
    </button>

</aside>

<!----------------------------- SCRIPTS ----------------------------->

<!-- Íconos Lucide -->
<script src="https://unpkg.com/lucide@latest"></script>
<script>
    lucide.createIcons();
</script>

<!-- Script: Aplicar modo oscuro SIN flash -->
<script>
// Ejecutar ANTES de cargar todo para evitar el flash blanco
(function() {
    const savedMode = localStorage.getItem("theme");
    if (savedMode === "dark") {
        document.documentElement.classList.add("dark");
    }
})();
</script>

<!-- Script: Botón modo oscuro funcional -->
<script>
document.addEventListener("DOMContentLoaded", () => {

    const modeBtn = document.getElementById("modeBtn");
    const root = document.documentElement;

    // Aplicar el tema guardado
    if (localStorage.getItem("theme") === "dark") {
        root.classList.add("dark");
    }

    // Evento del botón
    modeBtn.addEventListener("click", () => {

        // Cambiar tema
        root.classList.toggle("dark");

        // Guardar preferencia
        if (root.classList.contains("dark")) {
            localStorage.setItem("theme", "dark");
        } else {
            localStorage.setItem("theme", "light");
        }
    });
});
</script>

<?php
}
?>
