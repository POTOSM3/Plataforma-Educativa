document.addEventListener('DOMContentLoaded', () => {
    // 1. Inicializa iconos de Lucide
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // 2. Función global para abrir/cerrar Sidebar
    window.toggleSidebar = function() {
        const sidebar = document.getElementById("sidebar");
        const content = document.getElementById("content");
        if (sidebar && content) {
            sidebar.classList.toggle("open");
            content.classList.toggle("push");
        }
    };

    // 3. Lógica del Modo Oscuro (Mejorada para usar el <html>)
    const body = document.documentElement; // Usa el elemento raiz <html>
    const modeBtn = document.getElementById("modeBtn");

    if (modeBtn) {
        // Configura el texto/icono inicial
        const savedMode = localStorage.getItem("theme");
        if (savedMode === "dark") {
            body.classList.add("dark");
            modeBtn.innerHTML = '<i data-lucide="sun"></i> Claro';
        } else {
            body.classList.remove("dark");
            modeBtn.innerHTML = '<i data-lucide="moon"></i> Oscuro';
        }

        // Manejador de evento click
        modeBtn.addEventListener("click", () => {
            const isDark = body.classList.toggle("dark");
            localStorage.setItem("theme", isDark ? "dark" : "light");
            
            // Actualiza el texto/icono del botón
            modeBtn.innerHTML = isDark 
                ? '<i data-lucide="sun"></i> Claro' 
                : '<i data-lucide="moon"></i> Oscuro';
            
            // Vuelve a crear iconos para que el nuevo icono (sol/luna) se muestre
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    }
});