<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Usuario - Plataforma Educativa</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <header class="encabezado">
        <div class="logo">
            <i data-lucide="graduation-cap"></i>
            <h1>Plataforma Educativa</h1>
        </div>
        <nav>
            <a href="index.php">Inicio</a>
            <a href="registro.php" class="activo">Registro</a>
            <a href="login.php">Iniciar Sesión</a>
        </nav>
    </header>

    <main class="principal">
        <section class="bienvenida">
            <h2>🧾 Registro de Estudiante</h2>
            <p>Crea tu cuenta para acceder a los cursos y cuestionarios.</p>
        </section>

        <section class="formulario-contacto">
            <form action="guardar_estudiante.php" method="POST" class="form">
                <div class="campo">
                    <label for="nombre">Nombre completo</label>
                    <input type="text" name="nombre" id="nombre" required>
                </div>

                <div class="campo">
                    <label for="correo">Correo electrónico</label>
                    <input type="email" name="correo" id="correo" required>
                </div>

                <div class="campo">
                    <label for="contraseña">Contraseña</label>
                    <input type="password" name="contraseña" id="contraseña" minlength="6" required>
                </div>

                <div class="campo">
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

                <button type="submit" class="btn">Registrarme</button>
            </form>
        </section>
    </main>

    <footer>
        <p>© <?= date('Y') ?> Plataforma Educativa | Equipo de Desarrollo Web</p>
    </footer>

    <script>lucide.createIcons();</script>
</body>
</html>
