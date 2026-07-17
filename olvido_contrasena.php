<?php
session_start();
require 'conexion.php'; 

// Si el usuario ya está logueado, redirigirlo (aunque no debería estar aquí si lo está)
if (isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}
// Si ya está en un flujo de reset, redirigirlo al cambio
if (isset($_SESSION['reset_correo'])) {
    header("Location: cambiar_contrasena.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $correo = $_POST['correo'] ?? '';
    $codigo = $_POST['codigo_seguridad'] ?? '';

    if (empty($correo) || empty($codigo)) {
        $error = "Por favor, complete todos los campos.";
    } else {
        try {
            // 1. Verificar Correo y Código de Seguridad (sin hashing, simplificado para XAMPP)
            $stmt = $pdo->prepare("SELECT correo FROM usuarios WHERE correo = ? AND codigo_seguridad = ?");
            $stmt->execute([$correo, $codigo]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario) {
                // 2. Éxito: Guardar el correo en una sesión temporal y redirigir
                $_SESSION['reset_correo'] = $usuario['correo'];
                header("Location: cambiar_contrasena.php");
                exit();
            } else {
                $error = "Correo o código de seguridad incorrectos. Intente de nuevo.";
            }

        } catch (PDOException $e) {
            $error = 'Error de base de datos. Por favor, intente más tarde.';
            error_log("Error de olvido contraseña: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Restablecer Contraseña — EduLive</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --primary1: #6C63FF;
            --primary2: #00D4FF;
            --accent: #FFD166;
            --text: #fff;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(120deg, var(--primary1), var(--primary2));
            color: var(--text);
        }

        .reset-card {
            position: relative;
            z-index: 1;
            width: 400px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            padding: 50px 40px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            color: #fff;
        }

        .reset-card h1 { font-size: 1.8rem; margin-bottom: 5px; }
        .reset-card p { font-size: .9rem; opacity: 0.9; margin-bottom: 30px; }
        .reset-card label { display: block; text-align: left; margin-bottom: 6px; font-weight: 600; }
        .reset-card input {
            width: 100%; padding: 12px; border-radius: 10px; border: none;
            margin-bottom: 20px; outline: none; font-size: 1rem;
        }
        .reset-card button {
            width: 100%; background: linear-gradient(135deg, var(--accent), #f4a261);
            border: none; color: #1d3557; font-weight: 700; padding: 12px;
            border-radius: 10px; font-size: 1rem; cursor: pointer; transition: all 0.3s;
        }
        .error-message {
            display: flex; align-items: center; gap: 8px; background-color: #ef476f;
            color: white; padding: 10px 15px; border-radius: 8px; margin-bottom: 25px;
            text-align: left; font-weight: 500; font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="reset-card">
        <i data-lucide="key-round" style="width:40px; height:40px; color:var(--accent);"></i>
        <h1>Restablecer Acceso</h1>
        <p>Introduce tu correo y tu código de seguridad.</p>

        <?php if ($error): ?>
            <div class="error-message">
                <i data-lucide="alert-triangle" style="width:18px; height:18px;"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="olvido_contrasena.php" method="POST"> 
            <label for="correo">Correo electrónico</label>
            <input type="email" name="correo" id="correo" placeholder="ejemplo@correo.com" required>

            <label for="codigo_seguridad">Código de Seguridad</label>
            <input type="text" name="codigo_seguridad" id="codigo_seguridad" placeholder="Escribe tu código secreto" required>

            <button type="submit">Continuar</button>
        </form>
    </div>

    <script> lucide.createIcons(); </script>
</body>
</html>