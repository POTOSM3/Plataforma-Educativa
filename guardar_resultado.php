<?php
// 1) Validaciones básicas de entrada
$tema = isset($_POST['tema']) ? (int)$_POST['tema'] : 0;
$respuestas = $_POST['respuestas'] ?? null;

if ($tema <= 0 || !$respuestas || !is_array($respuestas)) {
    die("❌ Datos inválidos. Vuelve a intentarlo.");
}

// 2) Cargar preguntas del tema
$ruta_preg = "data/preguntas_{$tema}.json";
if (!file_exists($ruta_preg)) {
    die("❌ No se encontraron preguntas para este tema.");
}
$preguntas = json_decode(file_get_contents($ruta_preg), true) ?? [];

$total = count($preguntas);
$correctas = 0;

// 3) Calificar
for ($i = 0; $i < $total; $i++) {
    $respUser = isset($respuestas[$i]) ? (int)$respuestas[$i] : null;
    $respOk = isset($preguntas[$i]['respuesta']) ? (int)$preguntas[$i]['respuesta'] : null;

    if ($respUser !== null && $respOk !== null && $respUser === $respOk) {
        $correctas++;
    }
}

// 4) Porcentaje y nivel con switch
$porcentaje = $total > 0 ? ($correctas / $total) * 100 : 0;
switch (true) {
    case ($porcentaje >= 90): $nivel = "Excelente"; break;
    case ($porcentaje >= 70): $nivel = "Bueno"; break;
    default: $nivel = "Regular";
}

// 5) Guardar resultado
$ruta_result = "data/resultados.json";
$resultados = [];

if (file_exists($ruta_result)) {
    $resultados = json_decode(file_get_contents($ruta_result), true) ?? [];
}

$resultados[] = [
    "tema" => $tema,
    "aciertos" => $correctas,
    "total" => $total,
    "porcentaje" => round($porcentaje, 2),
    "nivel" => $nivel,
    "fecha" => date("Y-m-d H:i:s")
];

file_put_contents($ruta_result, json_encode($resultados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// 6) Mostrar feedback simple + enlace de retorno
echo "<h2>Resultado del Tema #{$tema}</h2>";
echo "Aciertos: {$correctas} / {$total}<br>";
echo "Porcentaje: " . round($porcentaje, 2) . "%<br>";
echo "Calificación: {$nivel}<br><br>";
echo '<a href="index.php">Volver al inicio</a> | ';
echo '<a href="quiz.php?tema=' . $tema . '">Repetir este quiz</a>';
