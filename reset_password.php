<?php
session_start();
require 'conexion.php';

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');
    $codigo = trim($_POST['codigo'] ?? '');
    $password_nueva = $_POST['password_nueva'] ?? '';
    $password_confirmar = $_POST['password_confirmar'] ?? '';

    if (empty($correo) || empty($codigo) || empty($password_nueva) || empty($password_confirmar)) {
        $error = 'Todos los campos son obligatorios.';
    } elseif ($password_nueva !== $password_confirmar) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (strlen($password_nueva) < 6) {
        $error = 'La nueva contraseña debe tener al menos 6 caracteres.';
    } else {
        try {
            // 1. Verificar el correo y el código de seguridad
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE correo = ? AND codigo_seguridad = ?");
            $stmt->execute([$correo, $codigo]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Código Válido: Proceder a cambiar la contraseña
                $hashed_password = password_hash($password_nueva, PASSWORD_DEFAULT);
                
                // 2. Actualizar la contraseña y LIMPIAR el código de seguridad (uso de un solo uso)
                $stmt_update = $pdo->prepare("UPDATE usuarios SET password = ?, codigo_seguridad = NULL WHERE id = ?");
                $stmt_update->execute([$hashed_password, $user['id']]);

                $mensaje = '✅ ¡Contraseña restablecida con éxito! Ya puedes iniciar sesión con tu nueva contraseña.';
                
            } else {
                $error = 'Error: Correo o código de seguridad incorrecto. El código es de un solo uso.';
            }

        } catch (PDOException $e) {
            $error = "Error de base de datos al restablecer la contraseña.";
            error_log($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Restablecer Contraseña</title>
    <link rel="stylesheet" href="css/style_moderno.css"> 
    <style>
        /* Estilos repetidos de forgot_password.php para consistencia */
        .login-container { max-width: 400px; margin: 100px auto; padding: 40px; background: rgba(255, 255, 255, 0.1); border-radius: 12px; box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1); backdrop-filter: blur(5px); color: white; }
        .login-container h2 { text-align: center; color: var(--accent, #FFD166); margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; }
        .form-group input { width: 100%; padding: 10px; margin-bottom: 20px; border-radius: 6px; border: 1px solid #444; background: #2c2c3e; color: white; }
        .btn { width: 100%; padding: 10px; background-color: #06D6A0; color: #141a29; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; transition: background-color 0.3s; }
        .btn:hover { background-color: #7ef5d3; }
        .message-box { padding: 15px; border-radius: 10px; margin-bottom: 20px; font-weight: bold; }
        .success-message { background-color: #06D6A0; color: #141a29; }
        .error-message { background-color: #ef476f; color: white; }
        .link-footer { text-align: center; margin-top: 15px; font-size: 0.9rem; }
        .link-footer a { color: var(--accent, #FFD166); text-decoration: none; }
    </style>
</head>
<body style="background-color: #141a29;">

<div class="login-container">
    <h2>🔑 Ingrese Nuevo Password</h2>

    <?php if ($mensaje): ?>
        <div class="message-box success-message"><?= $mensaje ?></div>
        <div class="link-footer"><a href="login.php">Ir a Iniciar Sesión</a></div>
    <?php else: ?>
        <?php if ($error): ?><div class="message-box error-message"><?= $error ?></div><?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="correo">Correo Electrónico:</label>
                <input type="email" id="correo" name="correo" required>
            </div>
            
            <div class="form-group">
                <label for="codigo">Código de Seguridad (8 Caracteres):</label>
                <input type="text" id="codigo" name="codigo" required>
            </div>
            
            <div class="form-group">
                <label for="password_nueva">Nueva Contraseña:</label>
                <input type="password" id="password_nueva" name="password_nueva" required>
            </div>
            
            <div class="form-group">
                <label for="password_confirmar">Confirmar Nueva Contraseña:</label>
                <input type="password" id="password_confirmar" name="password_confirmar" required>
            </div>

            <button type="submit" class="btn">Restablecer Contraseña</button>
        </form>

        <div class="link-footer">
            <a href="forgot_password.php">Solicitar un nuevo código</a><br>
            <a href="login.php">Volver al Login</a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>