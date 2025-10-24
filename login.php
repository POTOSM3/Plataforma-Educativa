<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión - Plataforma Educativa</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1d3557, #457b9d);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: #333;
        }

        .login-container {
            display: flex;
            width: 900px;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        .login-info {
            width: 50%;
            background: #1d3557;
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            text-align: center;
        }

        .login-info h1 {
            font-size: 2em;
            margin-bottom: 10px;
        }

        .login-info p {
            font-size: 1em;
            opacity: 0.9;
        }

        .login-form {
            width: 50%;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: #f8f9fa;
        }

        .login-form h2 {
            color: #1d3557;
            margin-bottom: 30px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            font-weight: 600;
            display: block;
            margin-bottom: 5px;
            color: #1d3557;
        }

        input {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 1em;
        }

        input:focus {
            border-color: #457b9d;
            outline: none;
        }

        button {
            background: #e9c46a;
            border: none;
            color: #1d3557;
            font-weight: 600;
            padding: 12px;
            border-radius: 8px;
            font-size: 1em;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            background: #f4a261;
        }

        .extra-links {
            margin-top: 20px;
            text-align: center;
        }

        .extra-links a {
            text-decoration: none;
            color: #457b9d;
            font-weight: 600;
        }

        .extra-links a:hover {
            text-decoration: underline;
        }

        @media (max-width: 800px) {
            .login-container {
                flex-direction: column;
                width: 95%;
            }
            .login-info, .login-form {
                width: 100%;
                padding: 30px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-info">
            <i data-lucide="graduation-cap" style="width: 70px; height: 70px; color: #e9c46a;"></i>
            <h1>Plataforma Educativa</h1>
            <p>Accede a tus cursos, cuestionarios y progreso académico.</p>
        </div>

        <div class="login-form">
            <h2><i data-lucide="lock" style="vertical-align: middle;"></i> Iniciar Sesión</h2>
            <form action="validar_login.php" method="POST">
                <div class="form-group">
                    <label for="correo">Correo electrónico</label>
                    <input type="email" name="correo" id="correo" placeholder="ejemplo@correo.com" required>
                </div>
                <div class="form-group">
                    <label for="contraseña">Contraseña</label>
                    <input type="password" name="contraseña" id="contraseña" placeholder="********" required>
                </div>
                <button type="submit">Iniciar Sesión</button>
            </form>

            <div class="extra-links">
                <p>¿No tienes cuenta? <a href="registro.php">Registrarte aquí</a></p>
            </div>
        </div>
    </div>

    <script>lucide.createIcons();</script>
</body>
</html>
