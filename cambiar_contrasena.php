<?php
session_start();
if (!isset($_SESSION['usuario'])) {
 header("Location: login.php");
 exit();
}

require 'conexion.php';
include 'components/layout.php'; // Asegúrate de que layout.php incluya el sidebar

$mensaje = '';
$error = '';

// Si se envió el formulario, procesamos el cambio
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
 $contrasena_actual = $_POST['contrasena_actual'] ?? '';
 $contrasena_nueva = $_POST['contrasena_nueva'] ?? '';
 $contrasena_confirmar = $_POST['contrasena_confirmar'] ?? '';
 $correo_usuario = $_SESSION['usuario']['correo'];


 if (empty($contrasena_actual) || empty($contrasena_nueva) || empty($contrasena_confirmar)) {
 $error = 'Todos los campos son obligatorios.';
 } elseif ($contrasena_nueva !== $contrasena_confirmar) {
 $error = 'La nueva contraseña y su confirmación no coinciden.';
 } elseif (strlen($contrasena_nueva) < 6) { 
 $error = 'La nueva contraseña debe tener al menos 6 caracteres.';
} else {

 try {

 $stmt = $pdo->prepare("SELECT `contraseña` FROM usuarios WHERE correo = ?");
 $stmt->execute([$correo_usuario]);
 $hash_actual = $stmt->fetchColumn();

 if ($hash_actual && password_verify($contrasena_actual, $hash_actual)) {

 $nuevo_hash = password_hash($contrasena_nueva, PASSWORD_DEFAULT);

 $stmt = $pdo->prepare("UPDATE usuarios SET `contraseña` = ? WHERE correo = ?");

 if ($stmt->execute([$nuevo_hash, $correo_usuario])) {
 $mensaje = '¡Contraseña cambiada con éxito!';
 } else {
 $error = 'Error al actualizar la base de datos.';
 }
 } else {
 $error = 'La contraseña actual es incorrecta.';
 }

 } catch (PDOException $e) {
 $error = 'Error de base de datos: ' . $e->getMessage();
 error_log("Error al cambiar contraseña: " . $e->getMessage());
 }
 }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
 <meta charset="UTF-8">
<title>Cambiar Contraseña — EduLive</title>
 <link rel="stylesheet" href="css/style_moderno.css">
 <script src="https://unpkg.com/lucide@latest"></script>
    <style>
    :root {
      /* Asegura que estas variables estén disponibles, aunque idealmente deberían estar en style_moderno.css */
      --color-text: #f0f0f0; 
      --color-input-bg: #2c2c3e; 
      --color-border: #444; 
    }
    
   /* CENTRADO DE LA TARJETA EN LA PÁGINA */
    .grid-form {
        display: flex;
        justify-content: center;
        align-items: center; /* Centra verticalmente el formulario */
        padding: 30px 20px;
        /* Ocupa el espacio restante después del banner y antes del footer */
        min-height: calc(100vh - 150px); 
    }

    /* ESTILO GLASSMORFISM DE LA TARJETA */
    .form-card {
        width: 100%;
        max-width: 400px; 
        padding: 40px; /* Aumentamos el padding para que se vea mejor */
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3); /* Sombra más profunda */
        border-radius: 20px;
        transition: background-color 0.3s, box-shadow 0.3s;
        
        /* === GLASSMORFISM: Fondo semi-transparente y desenfoque === */
        background: rgba(255, 255, 255, 0.15); /* Semi-transparente */
        backdrop-filter: blur(15px); /* EL DESENFOQUE CLAVE */
        
        /* Asegura que el color del texto sea claro */
        color: var(--color-text, #f0f0f0); 
    }
    
    /* Estilo de las etiquetas */
    .form-group label {
        display: block; 
        margin-bottom: 8px; 
        font-weight: 600;
        font-size: 14px;
        color: var(--color-text, #f0f0f0); 
    }
    
    /* Estilo de los campos de input */
    .form-card input[type="password"] {
        width: 100%; 
        padding: 12px 15px;
        border: 1px solid var(--color-border, #444); 
        border-radius: 10px; /* Bordes más suaves */
        background-color: var(--color-input-bg, #2c2c3e); 
        color: var(--color-text, #f0f0f0); 
        font-size: 16px;
        margin-bottom: 20px;
        transition: border-color 0.3s, background-color 0.3s;
    }
    
    /* Estilo de los campos en modo oscuro (ajusta si usas dark-mode) */
    .dark-mode .form-card input[type="password"] {
        /* Aquí puedes poner un estilo diferente si quieres que cambie en modo oscuro */
    }

    /* === 3. ESTILO DE BOTÓN === */
    .btn {
        width: 100%; 
        padding: 12px 20px;
        font-weight: 700;
        margin-top: 10px; 
        text-transform: uppercase;
        letter-spacing: 0.5px;
        /* El color del botón (naranja/amarillo) debe venir de style_moderno.css */
        border-radius: 10px;
        cursor: pointer;
    }

    /* === CORRECCIÓN PARA EL PIE DE PÁGINA === */
    .footer {
        color: var(--color-text, #f0f0f0); 
        background-color: var(--color-background, #12121e); 
        padding: 20px;
        text-align: center;
        font-size: 14px;
    }
    
    .footer p, .footer span {
        color: var(--color-text, #f0f0f0);
    }

    /* --- NUEVOS ESTILOS PARA 2 COLUMNAS --- */

/* La grilla ahora mostrará dos elementos uno al lado del otro */
.grid-form.two-columns {
    display: grid;
    grid-template-columns: minmax(300px, 450px) minmax(300px, 450px); /* 2 columnas de tamaño flexible */
    gap: 30px; /* Espacio entre las tarjetas */
    justify-content: center; /* Centra el bloque completo de las dos tarjetas */
    align-items: flex-start; /* Alinea las tarjetas a la parte superior */
    padding: 30px 20px; /* Mantenemos el padding ajustado */
    min-height: calc(100vh - 150px);
}

/* Estilo de la Tarjeta de Reglas (rules-card) */
.rules-card {
    padding: 30px;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
    color: var(--color-text, #f0f0f0);
    
    /* Aplicamos el mismo Glassmorphism */
    background: rgba(255, 255, 255, 0.15); 
    backdrop-filter: blur(15px); 
}

/* Estilos para la lista de reglas */
.rule-list {
    list-style: none; /* Elimina los puntos predeterminados */
    padding: 0;
    margin: 0;
}

.rule-list li {
    display: flex;
    align-items: center;
    margin-bottom: 12px;
    font-size: 0.9rem;
    line-height: 1.4;
}

.rule-list .check {
    width: 20px;
    height: 20px;
    margin-right: 8px;
    color: #06D6A0; /* Color verde de éxito */
}


/* Diseño Responsivo para Móviles: Las columnas se apilan */
@media (max-width: 900px) {
    .grid-form.two-columns {
        grid-template-columns: 1fr; /* Una sola columna */
        padding: 20px;
    }
    .form-card, .rules-card {
        max-width: 100%;
        margin: auto;
    }
}
</style>
</head>
<body style="background-color: #141a29;">

<?php renderSidebar($_SESSION['usuario'], 'cambiar_contrasena'); ?>

<main class="content" id="content">
 <section class="banner">
 <h1 class="title">🔑 Cambiar Contraseña</h1>
 <p class="desc">Introduce tu contraseña actual y la nueva contraseña.</p>
 </section>

<section class="grid-form two-columns">
    <div class="card form-card">
        <form method="POST" action="cambiar_contrasena.php">
            <div class="form-group">
                <label for="contrasena_actual">Contraseña Actual</label>
                <input type="password" id="contrasena_actual" name="contrasena_actual" required>
            </div>
            
            <div class="form-group">
                <label for="contrasena_nueva">Nueva Contraseña</label>
                <input type="password" id="contrasena_nueva" name="contrasena_nueva" required>
            </div>

            <div class="form-group">
                <label for="contrasena_confirmar">Confirmar Nueva Contraseña</label>
                <input type="password" id="contrasena_confirmar" name="contrasena_confirmar" required>
            </div>

            <button type="submit" class="btn">Guardar Cambios</button>
        </form>
    </div>

    <div class="card rules-card">
        <h2 style="margin-top:0; color:var(--accent); display:flex; align-items:center;">
            <i data-lucide="shield-check" style="margin-right: 10px;"></i> 
            Seguridad y Reglas
        </h2>
        
        <p style="font-size: 0.95rem; opacity: 0.8; margin-bottom: 20px;">
            Para proteger tu cuenta, tu nueva contraseña debe cumplir los siguientes requisitos:
        </p>

        <ul class="rule-list">
            <li><i data-lucide="check-circle" class="check"></i> Mínimo **8 caracteres** de largo.</li>
            <li><i data-lucide="check-circle" class="check"></i> Incluir al menos una **mayúscula** .</li>
            <li><i data-lucide="check-circle" class="check"></i> Incluir al menos un **número** .</li>
            <li><i data-lucide="check-circle" class="check"></i> Incluir al menos un **símbolo** (!@#$%^&*).</li>
        </ul>
    </div>
</section>

 <footer class="footer">
 © <?= date('Y') ?> EduLive — Plataforma Educativa • Hecho con ❤️
 </footer>
</main>


<script>
    // Inicializar íconos de Lucide
    lucide.createIcons();

    // === LÓGICA DEL MODO OSCURO ===
    const body = document.body;
    // ✅ CORRECCIÓN: Usamos el ID exacto del botón proporcionado ('modeBtn')
    const themeButton = document.getElementById('modeBtn'); 

    // Función para aplicar o quitar la clase de modo oscuro
    function toggleDarkMode() {
        body.classList.toggle('dark-mode');
        const isDarkMode = body.classList.contains('dark-mode');
        localStorage.setItem('theme', isDarkMode ? 'dark' : 'light');

        // Actualizar el texto y el ícono del botón
        if (themeButton) {
            // Obtener el ícono actual dentro del botón
            const icon = themeButton.querySelector('i');

            if (isDarkMode) {
                themeButton.innerHTML = '<i data-lucide="sun"></i> Claro';
            } else {
                themeButton.innerHTML = '<i data-lucide="moon"></i> Oscuro';
            }
            // Volver a inicializar los íconos después de cambiar el HTML interno
            lucide.createIcons();
        }
    }

    // Inicializar el modo oscuro basado en localStorage
    function initTheme() {
        const savedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

        // Determinar el estado inicial
        let isDarkMode = false;
        if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
            isDarkMode = true;
        }

        if (isDarkMode) {
            body.classList.add('dark-mode');
        }

        // Asegurarse de que el texto y el ícono del botón coincidan con el estado inicial
        if (themeButton) {
            if (isDarkMode) {
                themeButton.innerHTML = '<i data-lucide="sun"></i> Claro';
            } else {
                themeButton.innerHTML = '<i data-lucide="moon"></i> Oscuro';
            }
            lucide.createIcons();
        }
    }

    // 1. Ejecutar al cargar la página
    initTheme();

    // 2. Event listener para el botón
    if (themeButton) {
        themeButton.addEventListener('click', toggleDarkMode);
    }
</script>

</body>
</html>