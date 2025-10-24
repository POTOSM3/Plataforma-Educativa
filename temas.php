<?php
$temas = json_decode(file_get_contents("data/temas.json"), true);
$id = isset($_GET['id']) ? (int)$_GET['id'] : 1;
$temaActual = null;
$i = 0;

do {
    if ($temas[$i]['id'] == $id) {
        $temaActual = $temas[$i];
        break;
    }
    $i++;
} while ($i < count($temas));

if (!$temaActual) {
    $temaActual = $temas[0];
}

$siguiente = $id + 1;
if ($siguiente > count($temas)) {
    $siguiente = 1;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Temas del Curso</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <header>📘 Plataforma Educativa - Curso de Matemáticas</header>

    <nav>
        <a href="index.php">Inicio</a>
        <a href="temas.php">Temas</a>
        <a href="quiz.php">Cuestionario</a>
        <a href="registro.php">Registro</a>
    </nav>

    <div class="container">
        <h1><?= $temaActual['nombre'] ?></h1>
        <p><?= $temaActual['descripcion'] ?></p>

        <?php
        switch ($temaActual['id']) {
            case 1:
                echo "<p><strong>Ejemplo:</strong> Resolver 2x + 3 = 7</p>";
                break;
            case 2:
                echo "<p><strong>Ejemplo:</strong> Calcular el área de un triángulo.</p>";
                break;
            case 3:
                echo "<p><strong>Ejemplo:</strong> Sacar el promedio de notas.</p>";
                break;
        }
        ?>
        <br>
        <a href="temas.php?id=<?= $siguiente ?>" class="boton">➡️ Siguiente tema</a>
    </div>
</body>
</html>
