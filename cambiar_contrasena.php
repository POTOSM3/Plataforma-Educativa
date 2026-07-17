<?php
session_start();
require 'conexion.php';

// --- Definición del flujo de acceso y variables ---
$es_flujo_reset = false;
$correo_usuario = '';
$layout_html = ''; 

if (isset($_SESSION['usuario'])) {
    // 1. Acceso normal (logueado)
    $correo_usuario = $_SESSION['usuario']['correo'];
    
    // Incluir el archivo de layout y capturar el sidebar
    include 'components/layout.php'; 
    $titulo_h1 = '🔑 Cambiar Contraseña';
    $descripcion_p = 'Introduce tu contraseña actual y la nueva contraseña.';
    
    if (function_exists('renderSidebar')) {
        ob_start(); 
        renderSidebar($_SESSION['usuario'], 'cambiar_contrasena');
        $layout_html = ob_get_clean();
    } 

} elseif (isset($_SESSION['reset_correo'])) {
    // 2. Acceso por restablecimiento (olvido de contraseña)
    $correo_usuario = $_SESSION['reset_correo'];
    $es_flujo_reset = true;
    $titulo_h1 = '🔒 Nueva Contraseña';
    $descripcion_p = 'Has verificado tu identidad. Define tu nueva contraseña.';
    // NO INCLUIMOS EL LAYOUT/SIDEBAR aquí.
} else {
    // 3. Acceso denegado (ni logueado ni en flujo de reset)
    header("Location: login.php");
    exit();
}
// ------------------------------------

$mensaje = '';
$error = '';

// Si se envió el formulario, procesamos el cambio
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $contrasena_actual = $_POST['contrasena_actual'] ?? ''; 
    $contrasena_nueva = $_POST['contrasena_nueva'] ?? '';
    $contrasena_confirmar = $_POST['contrasena_confirmar'] ?? '';

    if (empty($contrasena_nueva) || empty($contrasena_confirmar)) {
        $error = 'La nueva contraseña y su confirmación son obligatorias.';
    } elseif ($contrasena_nueva !== $contrasena_confirmar) {
        $error = 'La nueva contraseña y su confirmación no coinciden.';
    } elseif (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $contrasena_nueva)) {
        $error = 'La nueva contraseña debe tener mínimo 8 caracteres, incluir al menos una mayúscula, un número y un símbolo (!@#$%^&*).';
    } else {
        try {
            $proceder_al_cambio = false;

            if ($es_flujo_reset) {
                // Flujo de reset: Se procede directamente (ya se verificó el código de seguridad)
                $proceder_al_cambio = true;
            } else {
                // Flujo normal: Validar la contraseña actual
                $stmt = $pdo->prepare("SELECT `contraseña` FROM usuarios WHERE correo = ?");
                $stmt->execute([$correo_usuario]);
                $hash_actual = $stmt->fetchColumn();

                if ($hash_actual && password_verify($contrasena_actual, $hash_actual)) {
                    $proceder_al_cambio = true;
                } else {
                    $error = 'La contraseña actual es incorrecta.';
                }
            }

            if ($proceder_al_cambio) {
                $nuevo_hash = password_hash($contrasena_nueva, PASSWORD_DEFAULT);

                // SQL: Si es reset, limpiamos el codigo_seguridad.
                $sql_update = "UPDATE usuarios SET `contraseña` = ?" . ($es_flujo_reset ? ", codigo_seguridad = NULL" : "") . " WHERE correo = ?";
                $stmt = $pdo->prepare($sql_update);

                if ($stmt->execute([$nuevo_hash, $correo_usuario])) {
                    $mensaje = '¡Contraseña cambiada con éxito! Por favor, <a href="login.php">inicia sesión</a> con tu nueva contraseña.';
                    
                    // CERRAR LA SESIÓN TEMPORAL si es flujo de reset
                    if ($es_flujo_reset) {
                        unset($_SESSION['reset_correo']);
                    }
                    // Redirigir al login después de un cambio exitoso
                    session_destroy();
                    header("Location: login.php?msg=success");
                    exit();
                    
                } else {
                    $error = 'Error al actualizar la base de datos.';
                }
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
    /* Estilos CSS Base */
    :root {
      --color-text: #f0f0f0; 
      --color-input-bg: #2c2c3e; 
      --color-border: #444; 
      --accent: #FFD166;
    }
    
    .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; color: var(--color-text); }
    .form-card input[type="password"] { width: 100%; padding: 12px 15px; border: 1px solid var(--color-border); border-radius: 10px; background-color: var(--color-input-bg); color: var(--color-text); font-size: 16px; margin-bottom: 20px; transition: border-color 0.3s, background-color 0.3s; }
    .btn { width: 100%; padding: 12px 20px; font-weight: 700; margin-top: 10px; text-transform: uppercase; letter-spacing: 0.5px; border-radius: 10px; cursor: pointer; background: linear-gradient(135deg, var(--accent), #f4a261); color: #1d3557; border: none;}
    
    .center-container {
        display: flex;
        justify-content: center; 
        align-items: center; 
        min-height: 100vh; 
        width: 100%;
        padding: 20px;
        box-sizing: border-box;
    }

    .form-card, .rules-card {
        padding: 30px;
        border-radius: 20px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        color: var(--color-text);
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(15px);
    }
    .grid-form.two-columns {
        display: grid;
        grid-template-columns: minmax(300px, 450px) minmax(300px, 450px);
        gap: 30px;
        justify-content: center;
        align-items: flex-start;
        padding: 30px 20px;
        min-height: calc(100vh - 150px); 
    }
    .rule-list { list-style: none; padding: 0; margin: 0; }
    .rule-list li { display: flex; align-items: center; margin-bottom: 12px; font-size: 0.9rem; line-height: 1.4; }
    .rule-list .check { width: 20px; height: 20px; margin-right: 8px; color: #06D6A0; }
    .error-message { background-color: #ef476f; color: white; padding: 10px; border-radius: 8px; margin-bottom: 20px; text-align: left;}
    .success-message { background-color: #06D6A0; color: white; padding: 10px; border-radius: 8px; margin-bottom: 20px; text-align: left;}

    @media (max-width: 900px) {
        .grid-form.two-columns { grid-template-columns: 1fr; }
    }
    .banner { padding: 30px 20px; }
    .banner .title { margin: 0; }
    .footer { text-align: center; padding: 20px; }
    </style>
</head>
<body style="background-color: #141a29;">

<?php if (!$es_flujo_reset): ?>
    <?= $layout_html ?>
    <main class="content" id="content">
        <section class="banner">
            <h1 class="title"><?= $titulo_h1 ?></h1>
            <p class="desc"><?= $descripcion_p ?></p>
        </section>

        <section class="grid-form two-columns">
            <div class="card form-card">
                <?php if ($mensaje): ?><div class="success-message"><?= $mensaje ?></div><?php elseif ($error): ?><div class="error-message"><?= $error ?></div><?php endif; ?>

                <form method="POST" action="cambiar_contrasena.php">
                    <div class="form-group">
                        <label for="contrasena_actual">Contraseña Actual</label>
                        <input type="password" id="contrasena_actual" name="contrasena_actual" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="contrasena_nueva">Nueva Contraseña</label>
                        <input type="password" id="contrasena_nueva" name="contrasena_nueva" required minlength="8" pattern="(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}" title="Mínimo 8 caracteres, con al menos una mayúscula, un número y un símbolo">
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

<?php else: ?>
    <div class="center-container">
        <div class="card form-card" style="max-width: 450px;">
            <h2 style="color:var(--accent); margin-top:0;">
                <?= $titulo_h1 ?>
            </h2>
            <p style="font-size: 0.95rem; opacity: 0.8; margin-bottom: 20px;">
                <?= $descripcion_p ?>
            </p>
            
            <?php if ($mensaje): ?><div class="success-message"><?= $mensaje ?></div><?php elseif ($error): ?><div class="error-message"><?= $error ?></div><?php endif; ?>

            <form method="POST" action="cambiar_contrasena.php">
                <div class="form-group">
                    <label for="contrasena_nueva">Nueva Contraseña</label>
                    <input type="password" id="contrasena_nueva" name="contrasena_nueva" required minlength="8" pattern="(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}" title="Mínimo 8 caracteres, con al menos una mayúscula, un número y un símbolo">
                </div>

                <div class="form-group">
                    <label for="contrasena_confirmar">Confirmar Nueva Contraseña</label>
                    <input type="password" id="contrasena_confirmar" name="contrasena_confirmar" required>
                </div>

                <button type="submit" class="btn">Guardar Nueva Contraseña</button>
            </form>
            
        </div>
    </div>
<?php endif; ?>


<script>
    // Inicializar íconos de Lucide
    lucide.createIcons();

    // LÓGICA DEL MODO OSCURO (Asegúrate de que este script esté correcto)
    const body = document.body;
    const themeButton = document.getElementById('modeBtn'); 

    function toggleDarkMode() {
        body.classList.toggle('dark-mode');
        const isDarkMode = body.classList.contains('dark-mode');
        localStorage.setItem('theme', isDarkMode ? 'dark' : 'light');

        if (themeButton) {
            if (isDarkMode) {
                themeButton.innerHTML = '<i data-lucide="sun"></i> Claro';
            } else {
                themeButton.innerHTML = '<i data-lucide="moon"></i> Oscuro';
            }
            lucide.createIcons();
        }
    }

    function initTheme() {
        const savedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

        let isDarkMode = false;
        if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
            isDarkMode = true;
        }

        if (isDarkMode) {
            body.classList.add('dark-mode');
        }

        if (themeButton) {
            if (isDarkMode) {
                themeButton.innerHTML = '<i data-lucide="sun"></i> Claro';
            } else {
                themeButton.innerHTML = '<i data-lucide="moon"></i> Oscuro';
            }
            lucide.createIcons();
        }
    }

    initTheme();

    if (themeButton) {
        themeButton.addEventListener('click', toggleDarkMode);
    }
</script>

</body>
</html>