<?php
$tema = isset($_POST['tema']) ? (int)$_POST['tema'] : 0;
$respuestas = $_POST['respuestas'] ?? [];

if ($tema <= 0 || !is_array($respuestas)) {
    die("Datos inválidos.");
}

$archivo = "data/preguntas_{$tema}.json";
if (!file_exists($archivo)) die("Preguntas no encontradas.");

$preguntas = json_decode(file_get_contents($archivo), true);
$total = count($preguntas);
$correctas = 0;

foreach ($preguntas as $i => $p) {
    if (isset($respuestas[$i]) && $respuestas[$i] == $p['respuesta']) {
        $correctas++;
    }
}

$porcentaje = ($total > 0) ? round(($correctas / $total) * 100, 2) : 0;
switch (true) {
    case $porcentaje >= 90: $nivel = "Excelente"; break;
    case $porcentaje >= 70: $nivel = "Bueno"; break;
    default: $nivel = "Regular"; break;
}

// Guardar en resultados.json
$archivo_res = "data/resultados.json";
$resultados = file_exists($archivo_res) ? json_decode(file_get_contents($archivo_res), true) : [];
$resultados[] = [
    "tema" => $tema,
    "aciertos" => $correctas,
    "total" => $total,
    "porcentaje" => $porcentaje,
    "nivel" => $nivel,
    "fecha" => date("Y-m-d H:i:s")
];
file_put_contents($archivo_res, json_encode($resultados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
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
    </header>

    <main class="principal">
        <section class="bienvenida">
            <h2>🎯 Resultado del Cuestionario</h2>
            <p>Has obtenido <strong><?= $correctas ?></strong> de <strong><?= $total ?></strong> respuestas correctas.</p>
            <p>Tu calificación: <strong><?= $porcentaje ?>%</strong> — <strong><?= $nivel ?></strong></p>
            <a href="index.php" class="btn">Volver al inicio</a>
            <a href="quiz.php?tema=<?= $tema ?>" class="btn">Repetir quiz</a>
        </section>
    </main>

    <footer>
        <p>© <?= date('Y') ?> Plataforma Educativa | Equipo de Desarrollo Web</p>
    </footer>

    <script>lucide.createIcons();</script>
</body>
</html>
