<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión - Plataforma Educativa</title>
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
            <a href="registro.php">Registro</a>
            <a href="login.php" class="activo">Iniciar Sesión</a>
        </nav>
    </header>

    <main class="principal">
        <section class="bienvenida">
            <h2>🔐 Iniciar Sesión</h2>
            <p>Introduce tu correo y contraseña para acceder a tus cursos.</p>
        </section>

        <section class="formulario-contacto">
            <form action="validar_login.php" method="POST" class="form">
                <div class="campo">
                    <label for="correo">Correo electrónico</label>
                    <input type="email" name="correo" id="correo" required>
                </div>

                <div class="campo">
                    <label for="contraseña">Contraseña</label>
                    <input type="password" name="contraseña" id="contraseña" required>
                </div>

                <button type="submit" class="btn">Iniciar Sesión</button>
            </form>
        </section>
    </main>

    <footer>
        <p>© <?= date('Y') ?> Plataforma Educativa | Equipo de Desarrollo Web</p>
    </footer>

    <script>lucide.createIcons();</script>
</body>
</html>
