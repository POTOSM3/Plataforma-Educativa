<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

$usuario = $_SESSION['usuario'];

require_once "components/layout.php";

// Lista de cursos
$cursos = [
    ["id" => 1, "nombre" => "Lenguaje", "portada" => "recursos/lenguaje/portada.jpg", "progreso" => 12],
    ["id" => 2, "nombre" => "Matemática", "portada" => "recursos/matematica/portada.jpg", "progreso" => 34],
    ["id" => 3, "nombre" => "Ciencias", "portada" => "recursos/ciencias/portada.jpg", "progreso" => 0],
    ["id" => 4, "nombre" => "Sociales", "portada" => "recursos/sociales/portada.jpg", "progreso" => 50],
    ["id" => 5, "nombre" => "Inglés", "portada" => "recursos/ingles/portada.jpg", "progreso" => 0],
];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Cursos</title>
    <link rel="stylesheet" href="css/style_moderno.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body>

<?php renderSidebar($usuario, "cursos"); ?>

<main class="content">

    <div class="banner">
        <h2 class="title">📚 Cursos disponibles</h2>
        <p class="desc">Selecciona un curso para acceder a materiales, videos y evaluaciones.</p>
    </div>

    <div class="grid">

        <?php foreach ($cursos as $curso): ?>
        <div class="card">
            <img src="<?= $curso['portada'] ?>" class="img-card">

            <h3><?= $curso['nombre'] ?></h3>
            <p class="muted">San Salvador • G1</p>

            <div class="progress-bar">
                <div class="progress" style="width: <?= $curso['progreso'] ?>%"></div>
            </div>

            <a href="curso_detalle.php?id=<?= $curso['id'] ?>" class="btn">Entrar</a>
        </div>
        <?php endforeach; ?>

    </div>

</main>

<script>lucide.createIcons();</script>

</body>
</html>
