<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

include 'components/layout.php';

$id = $_GET['id'] ?? null;

$cursos = [
    1 => [
        "titulo" => "Lenguaje",
        "descripcion" => "Gramática, ortografía y comprensión lectora.",
        "imagen" => "img/lenguaje.jpg",
        "pdf" => "recursos/lenguaje/guia de lenguaje.pdf",
        "video" => "https://www.youtube.com/watch?v=ysz5S6PUM-U",
        "quiz" => "quiz.php?id=1"
    ],
    2 => [
        "titulo" => "Matemática",
        "descripcion" => "Operaciones básicas, fracciones y geometría.",
        "imagen" => "img/matematica.jpg",
        "pdf" => "recursos/matematica/guia.pdf",
        "video" => "https://www.youtube.com/watch?v=RBSGKlAvoiM",
        "quiz" => "quiz.php?id=2"
    ],
    3 => [
        "titulo" => "Ciencias",
        "descripcion" => "Sistema solar, cuerpo humano y fenómenos naturales.",
        "imagen" => "img/ciencias.jpg",
        "pdf" => "recursos/ciencias/guia.pdf",
        "video" => "https://www.youtube.com/watch?v=lJIrF4YjHfQ",
        "quiz" => "quiz.php?id=3"
    ],
    4 => [
        "titulo" => "Sociales",
        "descripcion" => "Geografía, cultura y sistemas de gobierno.",
        "imagen" => "img/sociales.jpg",
        "pdf" => "recursos/sociales/guia.pdf",
        "video" => "https://www.youtube.com/watch?v=aqImkDgDwHU",
        "quiz" => "quiz.php?id=4"
    ],
    5 => [
        "titulo" => "Inglés",
        "descripcion" => "Vocabulario básico, verb to be y estructuras simples.",
        "imagen" => "img/ingles.jpg",
        "pdf" => "recursos/ingles/guia.pdf",
        "video" => "https://www.youtube.com/watch?v=HnQsB2TUgXw",
        "quiz" => "quiz.php?id=5"
    ],
];

$curso = $cursos[$id] ?? null;

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Curso - <?= $curso["titulo"] ?? "Curso" ?></title>
    <link rel="stylesheet" href="css/style_moderno.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>

<?php renderSidebar($_SESSION['usuario'], 'cursos'); ?>

<main class="content" id="content">

<?php if (!$curso): ?>
    <h2>⚠️ Curso no encontrado</h2>

<?php else: ?>

    <section class="banner">
        <h1 class="title">📘 <?= $curso["titulo"] ?></h1>
        <p class="desc"><?= $curso["descripcion"] ?></p>
    </section>

    <img src="<?= $curso["imagen"] ?>" alt="Curso" style="
        width: 100%; 
        max-height: 250px; 
        object-fit: cover; 
        border-radius: 10px; 
        margin-bottom: 20px;
    ">

    <section class="grid">

        <article class="card">
            <i data-lucide="file-text"></i>
            <h3>📄 Guía en PDF</h3>
            <p>Descarga el material del curso.</p>
            <a href="<?= $curso["pdf"] ?>" class="btn" target="_blank">Ver PDF</a>
        </article>

        <article class="card">
            <i data-lucide="video"></i>
            <h3>🎥 Clase en video</h3>
            <p>Video explicativo del contenido.</p>
            <a href="<?= $curso["video"] ?>" class="btn" target="_blank">Ver Video</a>
        </article>

        <article class="card">
            <i data-lucide="help-circle"></i>
            <h3>🧠 Evaluación</h3>
            <p>Quiz del curso para evaluar tu aprendizaje.</p>
            <a href="<?= $curso["quiz"] ?>" class="btn">Hacer Quiz</a>
        </article>

    </section>

<?php endif; ?>

</main>

<script>
    lucide.createIcons();
</script>

</body>
</html>
