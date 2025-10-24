<?php
session_start();

// Verificar que haya sesión activa
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

// Datos del usuario logueado
$correo_usuario = $_SESSION['usuario']['correo'] ?? 'anonimo';

// Validar datos enviados desde el formulario
$tema = isset($_POST['tema']) ? (int)$_POST['tema'] : 0;
$respuestas = $_POST['respuestas'] ?? [];

if ($tema <= 0 || !is_array($respuestas)) {
    die("Datos inválidos.");
}

// Cargar preguntas del tema correspondiente
$archivo_preguntas = "data/preguntas_{$tema}.json";
if (!file_exists($archivo_preguntas)) {
    die("Preguntas no encontradas para este tema.");
}

$preguntas = json_decode(file_get_contents($archivo_preguntas), true);
$total = count($preguntas);
$correctas = 0;

// Calcular aciertos
foreach ($preguntas as $i => $p) {
    if (isset($respuestas[$i]) && $respuestas[$i] == $p['respuesta']) {
        $correctas++;
    }
}

// Calcular porcentaje y nivel
$porcentaje = ($total > 0) ? round(($correctas / $total) * 100, 2) : 0;
switch (true) {
    case $porcentaje >= 90:
        $nivel = "Excelente";
        break;
    case $porcentaje >= 70:
        $nivel = "Bueno";
        break;
    default:
        $nivel = "Regular";
        break;
}

// Guardar los resultados
$archivo_resultados = "data/resultados.json";
$resultados = file_exists($archivo_resultados)
    ? json_decode(file_get_contents($archivo_resultados), true)
    : [];

$resultados[] = [
    "correo" => $correo_usuario,
    "tema" => $tema,
    "aciertos" => $correctas,
    "total" => $total,
    "porcentaje" => $porcentaje,
    "nivel" => $nivel,
    "fecha" => date("Y-m-d H:i:s")
];

file_put_contents($archivo_resultados, json_encode($resultados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resultado del Quiz - Plataforma Educativa</title>
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
            <a href="panel.php">Mi Panel</a>
            <a href="contacto.php">Contacto</a>
            <a href="logout.php">Cerrar sesión</a>
        </nav>
    </header>

    <main class="principal">
        <section class="bienvenida">
            <h2>🎯 Resultado del Cuestionario</h2>
            <p>Has obtenido <strong><?= $correctas ?></strong> de <strong><?= $total ?></strong> respuestas correctas.</p>
            <p>Tu calificación: <strong><?= $porcentaje ?>%</strong> — <strong><?= $nivel ?></strong></p>

            <?php if ($porcentaje >= 90): ?>
                <p>🌟 ¡Excelente trabajo! Sigue así.</p>
            <?php elseif ($porcentaje >= 70): ?>
                <p>💪 Buen resultado. Puedes mejorar aún más.</p>
            <?php else: ?>
                <p>📘 No te desanimes, vuelve a intentarlo.</p>
            <?php endif; ?>

            <div style="margin-top: 20px;">
                <a href="panel.php" class="btn">Ver mis resultados</a>
                <a href="quiz.php?tema=<?= $tema ?>" class="btn">Repetir Quiz</a>
            </div>
        </section>
    </main>

    <footer>
        <p>© <?= date('Y') ?> Plataforma Educativa | Equipo de Desarrollo Web</p>
    </footer>

    <script>lucide.createIcons();</script>
</body>
</html>
