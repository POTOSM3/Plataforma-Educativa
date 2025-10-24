<?php
session_start();
// Leer el archivo JSON
$archivo = 'data/temas.json';
$datos = file_get_contents($archivo);
$temas = json_decode($datos, true);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Plataforma Educativa</title>
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
            <a href="contacto.php">Contacto</a>
            <?php if (isset($_SESSION['usuario'])): ?>
                <a href="logout.php">Cerrar sesión</a>
            <?php else: ?>
                <a href="login.php">Iniciar Sesión</a>
            <?php endif; ?>
        </nav>
    </header>

    <main class="principal">
        <section class="bienvenida">
            <h2>📚 Bienvenido a la Plataforma Educativa</h2>
            <?php if (isset($_SESSION['usuario'])): ?>
                <p>Hola, <strong><?= htmlspecialchars($_SESSION['usuario']['nombre']) ?></strong>. Explora los cursos disponibles y mejora tus conocimientos.</p>
            <?php else: ?>
                <p>Explora las materias, estudia los temas y pon a prueba tus conocimientos con cuestionarios interactivos.</p>
            <?php endif; ?>
        </section>

        <section class="contenedor">
            <?php foreach ($temas as $tema): ?>
                <div class="tarjeta">
                    <div class="icono">
                        <?php
                        switch (strtolower($tema['titulo'])) {
                            case 'lenguaje': echo '<i data-lucide="book-open"></i>'; break;
                            case 'matemática': echo '<i data-lucide="calculator"></i>'; break;
                            case 'ciencias': echo '<i data-lucide="flask-conical"></i>'; break;
                            case 'sociales': echo '<i data-lucide="globe"></i>'; break;
                            case 'inglés': echo '<i data-lucide="message-circle"></i>'; break;
                            default: echo '<i data-lucide="bookmark"></i>';
                        }
                        ?>
                    </div>

                    <h2><?= htmlspecialchars($tema['titulo']) ?></h2>
                    <p><?= htmlspecialchars($tema['descripcion']) ?></p>
                    <a href="temas.php?id=<?= $tema['id'] ?>" class="btn">Ver más</a>
                    <a href="quiz.php?tema=<?= $tema['id'] ?>" class="btn">Hacer Cuestionario</a>
                </div>
            <?php endforeach; ?>
        </section>
    </main>

    <footer>
        <p>© <?= date('Y') ?> Plataforma Educativa | Equipo de Desarrollo Web</p>
    </footer>

    <script>lucide.createIcons();</script>
</body>
</html>
