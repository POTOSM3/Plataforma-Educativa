<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

require 'conexion.php';
include 'components/layout.php';

$mensaje = '';
$error = '';

// Si se envió el formulario, procesamos el cambio
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $contrasena_actual = $_POST['contrasena_actual'] ?? '';
    $contrasena_nueva = $_POST['contrasena_nueva'] ?? '';
    $contrasena_confirmar = $_POST['contrasena_confirmar'] ?? '';
    $correo_usuario = $_SESSION['usuario']['correo'];

    // 1. Validar campos
    if (empty($contrasena_actual) || empty($contrasena_nueva) || empty($contrasena_confirmar)) {
        $error = 'Todos los campos son obligatorios.';
    } elseif ($contrasena_nueva !== $contrasena_confirmar) {
        $error = 'La nueva contraseña y su confirmación no coinciden.';
    } elseif (strlen($contrasena_nueva) < 6) { // Ejemplo de regla de seguridad
        $error = 'La nueva contraseña debe tener al menos 6 caracteres.';
    } else {
        // 2. Verificar contraseña actual
        try {
            $stmt = $pdo->prepare("SELECT contrasena FROM usuarios WHERE correo = ?");
            $stmt->execute([$correo_usuario]);
            $hash_actual = $stmt->fetchColumn();

            if ($hash_actual && password_verify($contrasena_actual, $hash_actual)) {
                
                // 3. Cambiar la contraseña
                $nuevo_hash = password_hash($contrasena_nueva, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE usuarios SET contrasena = ? WHERE correo = ?");
                
                if ($stmt->execute([$nuevo_hash, $correo_usuario])) {
                    $mensaje = '¡Contraseña cambiada con éxito!';
                    // Opcional: Destruir sesión y forzar inicio de sesión con nueva contraseña
                    // header("Location: logout.php"); 
                } else {
                    $error = 'Error al actualizar la base de datos.';
                }
            } else {
                $error = 'La contraseña actual es incorrecta.';
            }

        } catch (PDOException $e) {
            $error = 'Error de base de datos: ' . $e->getMessage();
            error_log($error);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cambiar Contraseña</title>
    <link rel="stylesheet" href="css/style_moderno.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>

<?php renderSidebar($_SESSION['usuario'], 'panel'); // Usamos 'panel' o crea una nueva categoría si quieres ?>

<main class="content" id="content">
    <section class="banner">
        <h1 class="title">🔑 Cambiar Contraseña</h1>
        <p class="desc">Introduce tu contraseña actual y la nueva contraseña.</p>
    </section>

    <section class="grid-form">
        <div class="card form-card">
            <?php if ($mensaje): ?>
                <div class="success-message" style="color: green; margin-bottom: 15px;"><?= $mensaje ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="error-message" style="color: red; margin-bottom: 15px;"><?= $error ?></div>
            <?php endif; ?>

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
    </section>

    <footer class="footer">
        © <?= date('Y') ?> EduLive — Plataforma Educativa • Hecho con ❤️
    </footer>
</main>

<script>
  lucide.createIcons();
  // Lógica del modo oscuro si está en layout.php
</script>
</body>
</html>