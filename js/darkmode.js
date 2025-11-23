document.addEventListener("DOMContentLoaded", () => {
    const btn = document.getElementById("modeBtn");
    const saved = localStorage.getItem("theme");

    // Aplicar el modo guardado
    if (saved === "dark") {
        document.documentElement.classList.add("dark");
    }

    // Cambiar entre claro/oscuro
    btn.addEventListener("click", () => {
        document.documentElement.classList.toggle("dark");

        if (document.documentElement.classList.contains("dark")) {
            localStorage.setItem("theme", "dark");
        } else {
            localStorage.setItem("theme", "light");
        }
    });
});
