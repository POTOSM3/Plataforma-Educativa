<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

// Cargar datos del curso desde JSON
$archivo = 'data/temas.json';
$datos = file_get_contents($archivo);
$temas = json_decode($datos, true);

// Obtener el id del curso desde la URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 1;
$tema = $temas[$id - 1] ?? $temas[0];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($tema['titulo']) ?> - Plataforma Educativa</title>
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
            <a href="panel.php">Mi Panel</a>
            <a href="temas.php" class="activo">Cursos</a>
            <a href="contacto.php">Contacto</a>
            <a href="logout.php">Cerrar sesión</a>
        </nav>
    </header>

    <main class="principal">
        <section class="bienvenida">
            <h2>📘 Curso de <?= htmlspecialchars($tema['titulo']) ?></h2>
            <p><?= htmlspecialchars($tema['descripcion']) ?></p>
        </section>

        <section class="curso-detalle">
            <div class="curso-info">
                <div class="icono-grande">
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

                <h3>🎯 Objetivos del curso</h3>
                <ul>
                    <li>Comprender los conceptos clave de <?= htmlspecialchars($tema['titulo']) ?>.</li>
                    <li>Aplicar el conocimiento en ejercicios prácticos y cuestionarios.</li>
                    <li>Desarrollar habilidades para resolver problemas y analizar situaciones reales.</li>
                </ul>
            </div>

            <div class="curso-recursos">
                <h3>📚 Recursos y Materiales</h3>
                <p>Te recomendamos repasar los siguientes recursos antes de hacer el cuestionario:</p>
                <ul>
                    <?php if (strtolower($tema['titulo']) === 'lenguaje'): ?>
                        <li>📖 Reglas ortográficas básicas</li>
                        <li>📝 Ejercicios de sinónimos y antónimos</li>
                        <li>🎧 Escucha podcasts educativos sobre gramática</li>
                    <?php elseif (strtolower($tema['titulo']) === 'matemática'): ?>
                        <li>🧮 Repaso de multiplicaciones y divisiones</li>
                        <li>📘 Introducción al álgebra básica</li>
                        <li>📹 Video: Cómo resolver ecuaciones simples</li>
                    <?php elseif (strtolower($tema['titulo']) === 'ciencias'): ?>
                        <li>🔬 Estados de la materia</li>
                        <li>🌍 La fuerza de la gravedad</li>
                        <li>📹 Documental corto sobre el sistema solar</li>
                    <?php elseif (strtolower($tema['titulo']) === 'sociales'): ?>
                        <li>🗺️ Mapas de América Central</li>
                        <li>📘 Historia de El Salvador</li>
                        <li>🎞️ Línea del tiempo de los Acuerdos de Paz</li>
                    <?php elseif (strtolower($tema['titulo']) === 'inglés'): ?>
                        <li>🔤 Vocabulario básico en inglés</li>
                        <li>🎧 Escucha pronunciaciones comunes</li>
                        <li>📹 Video: Saludos y expresiones en inglés</li>
                    <?php else: ?>
                        <li>📖 Material general disponible próximamente.</li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="curso-botones">
                <a href="quiz.php?tema=<?= $tema['id'] ?>" class="btn">Hacer Cuestionario</a>
                <a href="index.php" class="btn">Volver al inicio</a>
            </div>
        </section>
    </main>

    <footer>
        <p>© <?= date('Y') ?> Plataforma Educativa | Equipo de Desarrollo Web</p>
    </footer>

    <script>lucide.createIcons();</script>
</body>
</html>
