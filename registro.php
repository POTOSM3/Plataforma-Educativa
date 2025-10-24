<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Usuario - Plataforma Educativa</title>
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

        .register-container {
            display: flex;
            width: 950px;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        .register-info {
            width: 45%;
            background: #1d3557;
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            text-align: center;
        }

        .register-info i {
            color: #e9c46a;
            margin-bottom: 20px;
        }

        .register-info h1 {
            font-size: 1.8em;
            margin-bottom: 10px;
        }

        .register-info p {
            opacity: 0.9;
        }

        .register-form {
            width: 55%;
            padding: 60px 50px;
            background: #f8f9fa;
        }

        .register-form h2 {
            color: #1d3557;
            text-align: center;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            font-weight: 600;
            color: #1d3557;
            display: block;
            margin-bottom: 6px;
        }

        input, select {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 1em;
        }

        input:focus, select:focus {
            border-color: #457b9d;
            outline: none;
        }

        button {
            width: 100%;
            background: #e9c46a;
            color: #1d3557;
            border: none;
            padding: 12px;
            font-weight: 600;
            border-radius: 8px;
            font-size: 1em;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            background: #f4a261;
        }

        .extra-links {
            text-align: center;
            margin-top: 20px;
        }

        .extra-links a {
            text-decoration: none;
            color: #457b9d;
            font-weight: 600;
        }

        .extra-links a:hover {
            text-decoration: underline;
        }

        @media (max-width: 900px) {
            .register-container {
                flex-direction: column;
                width: 95%;
            }
            .register-info, .register-form {
                width: 100%;
                padding: 30px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-info">
            <i data-lucide="graduation-cap" style="width:70px; height:70px;"></i>
            <h1>Únete a la Plataforma Educativa</h1>
            <p>Regístrate y comienza a explorar cursos interactivos, cuestionarios y tu propio progreso académico.</p>
        </div>

        <div class="register-form">
            <h2><i data-lucide="user-plus"></i> Crear Cuenta</h2>
            <form action="guardar_estudiante.php" method="POST">
                <div class="form-group">
                    <label for="nombre">Nombre completo</label>
                    <input type="text" name="nombre" id="nombre" placeholder="Ej: Edward Potosme" required>
                </div>

                <div class="form-group">
                    <label for="correo">Correo electrónico</label>
                    <input type="email" name="correo" id="correo" placeholder="ejemplo@correo.com" required>
                </div>

                <div class="form-group">
                    <label for="contraseña">Contraseña</label>
                    <input type="password" name="contraseña" id="contraseña" placeholder="********" minlength="6" required>
                </div>

                <div class="form-group">
                    <label for="materia">Materia de interés</label>
                    <select name="materia" id="materia" required>
                        <option value="">Seleccione una materia</option>
                        <option value="Lenguaje">Lenguaje</option>
                        <option value="Matemática">Matemática</option>
                        <option value="Ciencias">Ciencias</option>
                        <option value="Sociales">Sociales</option>
                        <option value="Inglés">Inglés</option>
                    </select>
                </div>

                <button type="submit">Registrarme</button>
            </form>

            <div class="extra-links">
                <p>¿Ya tienes una cuenta? <a href="login.php">Inicia sesión</a></p>
            </div>
        </div>
    </div>

    <script>lucide.createIcons();</script>
</body>
</html>
