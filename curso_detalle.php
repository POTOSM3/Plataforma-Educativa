<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

$usuario = $_SESSION['usuario'];

require_once "components/layout.php";

$id = $_GET["id"] ?? 0;

$cursos = [
    1 => [
        "nombre" => "Lenguaje",
        "descripcion" => "Gramática, ortografía y comprensión lectora.",
        "pdf" => "recursos/lenguaje/guia.pdf",
        "video" => "https://www.youtube.com",
        "quiz" => "quiz.php?curso=1"
    ],
    2 => [
        "nombre" => "Matemática",
        "descripcion" => "Aritmética, álgebra y resolución de problemas.",
        "pdf" => "recursos/matematica/guia.pdf",
        "video" => "https://www.youtube.com",
        "quiz" => "quiz.php?curso=2"
    ],
];

$curso = $cursos[$id] ?? null;

if (!$curso) {
    echo "Curso no encontrado";
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= $curso["nombre"] ?></title>
    <link rel="stylesheet" href="css/style_moderno.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body>

<?php renderSidebar($usuario, "cursos"); ?>

<main class="content">

    <div class="banner">
        <h2 class="title"><?= $curso["nombre"] ?></h2>
        <p class="desc"><?= $curso["descripcion"] ?></p>
    </div>

    <div class="grid">

        <div class="card">
            <i data-lucide="book-open"></i>
            <h3>Guía PDF</h3>
            <p>Descarga el material de lectura.</p>
            <a href="<?= $curso["pdf"] ?>" class="btn" target="_blank">Ver PDF</a>
        </div>

        <div class="card">
            <i data-lucide="video"></i>
            <h3>Clase en video</h3>
            <p>Contenido en formato audiovisual.</p>
            <a href="<?= $curso["video"] ?>" class="btn" target="_blank">Ver Video</a>
        </div>

        <div class="card">
            <i data-lucide="brain"></i>
            <h3>Evaluación</h3>
            <p>Realiza tu examen del curso.</p>
            <a href="<?= $curso["quiz"] ?>" class="btn">Hacer Quiz</a>
        </div>

    </div>

</main>

<script>lucide.createIcons();</script>

</body>
</html>
