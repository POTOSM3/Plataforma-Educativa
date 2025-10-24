<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

// Cargar archivo JSON de cursos
$archivo = 'data/temas.json';
$datos = file_get_contents($archivo);
$temas = json_decode($datos, true);

// Obtener curso actual
$id = isset($_GET['id']) ? intval($_GET['id']) : 1;
$tema = $temas[$id - 1] ?? $temas[0];

// Calcular progreso (más adelante se conectará con progreso real)
$lecciones_totales = 0;
foreach ($tema['modulos'] as $modulo) {
    $lecciones_totales += count($modulo['lecciones']);
}
$lecciones_completadas = 0;
$progreso = ($lecciones_totales > 0) ? round(($lecciones_completadas / $lecciones_totales) * 100, 0) : 0;

// Si se selecciona una lección, mostrarla en el visor
$leccion_activa = $_GET['leccion'] ?? null;
$contenido = null;
if ($leccion_activa) {
    foreach ($tema['modulos'] as $modulo) {
        foreach ($modulo['lecciones'] as $leccion) {
            if ($leccion['titulo'] === $leccion_activa) {
                $contenido = $leccion;
                break 2;
            }
        }
    }
}
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
            <h3>🎯 Objetivos del curso</h3>
            <ul>
                <?php foreach ($tema['objetivos'] as $obj): ?>
                    <li><?= htmlspecialchars($obj) ?></li>
                <?php endforeach; ?>
            </ul>

            <h3 style="margin-top: 30px;">📈 Progreso del curso</h3>
            <div class="barra-progreso">
                <div class="progreso" style="width: <?= $progreso ?>%;"><?= $progreso ?>%</div>
            </div>

            <h3 style="margin-top: 40px;">📚 Módulos y Lecciones</h3>
            <?php foreach ($tema['modulos'] as $modulo): ?>
                <div class="modulo">
                    <h4><?= htmlspecialchars($modulo['titulo']) ?></h4>
                    <ul class="lecciones">
                        <?php foreach ($modulo['lecciones'] as $leccion): ?>
                            <li>
                                <strong><?= htmlspecialchars($leccion['titulo']) ?></strong>
                                <span class="tipo"><?= htmlspecialchars($leccion['tipo']) ?></span>
                                <a href="temas.php?id=<?= $tema['id'] ?>&leccion=<?= urlencode($leccion['titulo']) ?>" class="btn-mini">Abrir</a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>

            <?php if ($contenido): ?>
                <div class="visor">
                    <h3>📖 <?= htmlspecialchars($contenido['titulo']) ?></h3>
                    <?php if (strtolower($contenido['tipo']) === 'pdf'): ?>
                        <iframe src="<?= htmlspecialchars($contenido['enlace']) ?>" width="100%" height="600px"></iframe>
                    <?php elseif (strtolower($contenido['tipo']) === 'video' && strpos($contenido['enlace'], 'youtube.com') !== false): ?>
                        <?php
                            parse_str(parse_url($contenido['enlace'], PHP_URL_QUERY), $params);
                            $video_id = $params['v'] ?? '';
                        ?>
                        <iframe width="100%" height="500" src="https://www.youtube.com/embed/<?= htmlspecialchars($video_id) ?>" frameborder="0" allowfullscreen></iframe>
                    <?php else: ?>
                        <p>🔗 <a href="<?= htmlspecialchars($contenido['enlace']) ?>" target="_blank">Abrir recurso externo</a></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

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
