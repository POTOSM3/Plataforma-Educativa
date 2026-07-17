<?php
session_start();
require 'conexion.php';

// 1. Incluir el autoload de Composer y PHPMailer
require __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 2. CONFIGURACIÓN DEL EMAIL (¡DEBES MODIFICAR ESTO!)
// Usa un correo de tu hosting o una cuenta de Gmail/Outlook para enviar.
$MAIL_HOST = 'smtp.gmail.com';     // Ejemplo: 'smtp.gmail.com'
$MAIL_USERNAME = 'potosmeedward619@gmail.com'; // El email que enviará
$MAIL_PASSWORD = 'ikej mouq uzxv zlda'; // Contraseña o Clave de Aplicación
$MAIL_PORT = 587;                   // ¡Cambiado!
$MAIL_SECURE = PHPMailer::ENCRYPTION_STARTTLS;  // ¡Cambiado!


$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo_usuario = trim($_POST['correo'] ?? '');

    if (empty($correo_usuario)) {
        $error = 'Por favor, introduce tu dirección de correo electrónico.';
    } else {
        try {
            // 1. Verificar que el correo exista
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE correo = ?");
            $stmt->execute([$correo_usuario]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // 2. Generar un código de seguridad
                $new_code = bin2hex(random_bytes(4)); 
                
                // 3. Guardar el código en la base de datos
                $stmt_update = $pdo->prepare("UPDATE usuarios SET codigo_seguridad = ? WHERE id = ?");
                $stmt_update->execute([$new_code, $user['id']]);

                // 4. INICIO DEL ENVÍO DE CORREO AUTOMÁTICO
                $mail = new PHPMailer(true);
                $mail->SMTPDebug = 2;
                
                // Configuración del Servidor
                $mail->isSMTP();
                $mail->Host = $MAIL_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = $MAIL_USERNAME;
                $mail->Password = $MAIL_PASSWORD;
                $mail->SMTPSecure = $MAIL_SECURE;
                $mail->Port = $MAIL_PORT;
                $mail->CharSet = 'UTF-8';

                // Destinatarios
                $mail->setFrom($MAIL_USERNAME, 'Soporte EduLive');
                $mail->addAddress($correo_usuario); // Añade el correo del usuario

                // Contenido del Email
                $mail->isHTML(true);
                $mail->Subject = 'Recuperación de Contraseña - Codigo de Seguridad';
                
                $body = "
                    <h2>Restablecimiento de Contraseña</h2>
                    <p>Has solicitado restablecer la contraseña de tu cuenta ($correo_usuario).</p>
                    <p>Tu código de seguridad es:</p>
                    <h1 style='color:#06D6A0; background:#141a29; padding: 15px; border-radius: 8px; text-align: center;'>{$new_code}</h1>
                    <p>Por favor, usa este código en nuestra página de restablecimiento de contraseña para continuar:</p>
                    <p><a href='localhost/plataforma_educativa1/reset_password.php' style='background:#FFD166; padding: 10px 20px; color:#141a29; text-decoration:none; border-radius: 5px; font-weight:bold;'>Restablecer Contraseña</a></p>
                    <p>Si no solicitaste este cambio, ignora este correo.</p>
                ";
                
                $mail->Body = $body;
                $mail->AltBody = "Tu código de seguridad para EduLive es: {$new_code}. Úsalo en la página de restablecimiento.";

                $mail->send();
                
                // Mensaje de éxito después del envío
                $mensaje = "✅ ¡Solicitud recibida! Se ha generado un código de seguridad para la cuenta **{$correo_usuario}**. Por favor, revisa la bandeja de entrada de tu correo (y la carpeta de spam) para recibir el código y completar el proceso.";
                
                // Mensaje SOLO para ADMINISTRADOR (Quitar al pasar a producción)
                $mensaje .= "<br><br><span style='color:red; font-size: 0.8em;'>(ADMIN SÓLO) Código generado: <strong>{$new_code}</strong></span>";


            } else {
                $error = 'Error: No existe un usuario registrado con esa dirección de correo.';
            }

        } catch (Exception $e) {
            // Error en la base de datos o en el envío del correo
            $error = "No pudimos enviar el correo de recuperación. Vuelve a intentarlo o contacta a soporte. Detalles técnicos: {$mail->ErrorInfo}";
            error_log("Error de PHPMailer: {$e->getMessage()} | Detalles: {$mail->ErrorInfo}");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Contraseña</title>
    <link rel="stylesheet" href="css/style_moderno.css"> 
    <style>
        .login-container { max-width: 400px; margin: 100px auto; padding: 40px; background: rgba(255, 255, 255, 0.1); border-radius: 12px; box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1); backdrop-filter: blur(5px); color: white; }
        .login-container h2 { text-align: center; color: var(--accent, #FFD166); margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; }
        .form-group input { width: 100%; padding: 10px; margin-bottom: 20px; border-radius: 6px; border: 1px solid #444; background: #2c2c3e; color: white; }
        .btn { width: 100%; padding: 10px; background-color: var(--accent, #FFD166); color: #141a29; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; transition: background-color 0.3s; }
        .btn:hover { background-color: #f7e099; }
        .message-box { padding: 15px; border-radius: 10px; margin-bottom: 20px; font-weight: bold; }
        .success-message { background-color: #06D6A0; color: #141a29; }
        .error-message { background-color: #ef476f; color: white; }
        .link-footer { text-align: center; margin-top: 15px; font-size: 0.9rem; }
        .link-footer a { color: var(--accent, #FFD166); text-decoration: none; }
    </style>
</head>
<body style="background-color: #141a29;">

<div class="login-container">
    <h2>🔒 Recuperar Contraseña</h2>

    <?php if ($mensaje): ?><div class="message-box success-message"><?= $mensaje ?></div><?php endif; ?>
    <?php if ($error): ?><div class="message-box error-message"><?= $error ?></div><?php endif; ?>

    <?php if (!$mensaje || $error): // Muestra el formulario si no hay mensaje de éxito o si hubo un error ?>
    <form method="POST">
        <div class="form-group">
            <label for="correo">Correo Electrónico:</label>
            <input type="email" id="correo" name="correo" required>
        </div>
        
        <button type="submit" class="btn">Solicitar Código</button>
    </form>
    <?php endif; ?>
    
    <div class="link-footer">
        <a href="reset_password.php">¿Ya tienes el código? Restablece aquí.</a><br>
        <a href="login.php">Volver al Login</a>
    </div>
</div>

</body>
</html>