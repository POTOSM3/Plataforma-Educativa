<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Contacto - Plataforma Educativa</title>
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
            <a href="temas.php">Cursos</a>
            <a href="contacto.php" class="activo">Contacto</a>
            <a href="#">Cerrar sesión</a>
        </nav>
    </header>

    <main class="principal">
        <section class="bienvenida">
            <h2>📩 Contáctanos</h2>
            <p>¿Tienes dudas o sugerencias? Envíanos un mensaje y te responderemos pronto.</p>
        </section>

        <section class="formulario-contacto">
            <form action="guardar_contacto.php" method="POST" class="form">
                <div class="campo">
                    <label for="nombre">Nombre completo</label>
                    <input type="text" name="nombre" id="nombre" required>
                </div>

                <div class="campo">
                    <label for="correo">Correo electrónico</label>
                    <input type="email" name="correo" id="correo" required>
                </div>

                <div class="campo">
                    <label for="mensaje">Mensaje</label>
                    <textarea name="mensaje" id="mensaje" rows="5" required></textarea>
                </div>

                <button type="submit" class="btn">Enviar mensaje</button>
            </form>
        </section>
    </main>

    <footer>
        <p>© <?= date('Y') ?> Plataforma Educativa | Equipo de Desarrollo Web</p>
    </footer>

    <script>lucide.createIcons();</script>
</body>
</html>
