<?php
$estudiantes = json_decode(file_get_contents("data/estudiantes.json"), true) ?? [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de estudiantes</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<header><h1>Registro de estudiantes</h1></header>
<nav>
    <a href="index.php">Inicio</a>
    <a href="quiz.php">Cuestionario</a>
    <a href="registro.php">Registro</a>
</nav>

<div class="container">
    <h2>Registrar nuevo estudiante</h2>
    <form action="guardar_estudiante.php" method="POST">
        <input type="text" name="nombre" placeholder="Nombre completo" required>
        <input type="email" name="email" placeholder="Correo" required>
        <input type="number" name="nota" placeholder="Calificación (0-100)" required>
        <button type="submit">Guardar</button>
    </form>

    <h2>Listado de estudiantes</h2>
    <table>
        <tr><th>Nombre</th><th>Correo</th><th>Nota</th><th>Estado</th></tr>
        <?php foreach ($estudiantes as $e): ?>
            <?php
            switch (true) {
                case $e["nota"] >= 90: $estado = "A"; break;
                case $e["nota"] >= 75: $estado = "B"; break;
                case $e["nota"] >= 60: $estado = "C"; break;
                default: $estado = "D";
            }
            ?>
            <tr>
                <td><?= $e["nombre"] ?></td>
                <td><?= $e["email"] ?></td>
                <td><?= $e["nota"] ?></td>
                <td><?= $estado ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
</body>
</html>
