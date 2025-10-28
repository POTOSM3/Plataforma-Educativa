<?php
session_start();
if (!isset($_SESSION['usuario'])) {
  header("Location: login.php");
  exit;
}

$usuario = $_SESSION['usuario'];
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$temas = json_decode(file_get_contents("data/temas.json"), true);
$curso = null;
foreach ($temas as $t) {
  if ($t['id'] == $id) {
    $curso = $t;
    break;
  }
}

if (!$curso) {
  die("⚠️ Curso no encontrado.");
}

include 'components/layout.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($curso['titulo']) ?> - EduLive</title>
  <link rel="stylesheet" href="css/style_moderno.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>

<?php renderSidebar($usuario, 'cursos'); ?>

<main class="content" id="content">
  <section class="banner">
    <h1 class="title">📘 <?= htmlspecialchars($curso['titulo']) ?></h1>
    <p class="desc"><?= htmlspecialchars($curso['descripcion']) ?></p>
  </section>

  <section class="section">
    <h3>📚 Contenido del curso</h3>
    <p>Aquí puedes acceder al material de aprendizaje, lecturas y recursos multimedia del curso <strong><?= htmlspecialchars($curso['titulo']) ?></strong>.</p>

    <div class="grid">
      <article class="card">
        <i data-lucide="file-text"></i>
        <h3>📄 Lecturas en PDF</h3>
        <p>Descarga el material teórico del curso.</p>
        <a class="btn" href="recursos/<?= strtolower($curso['titulo']) ?>/guia.pdf" target="_blank">Ver PDF</a>
      </article>

      <article class="card">
        <i data-lucide="video"></i>
        <h3>🎥 Video explicativo</h3>
        <p>Visualiza clases grabadas para reforzar tu aprendizaje.</p>
        <a class="btn" href="recursos/<?= strtolower($curso['titulo']) ?>/video.mp4" target="_blank">Ver video</a>
      </article>

      <article class="card">
        <i data-lucide="help-circle"></i>
        <h3>🧠 Evaluación</h3>
        <p>Evalúa tus conocimientos con el cuestionario final.</p>
        <a class="btn" href="quiz.php?tema=<?= $curso['id'] ?>">Ir al Quiz</a>
      </article>
    </div>
  </section>

  <footer class="footer">
    © <?= date('Y') ?> EduLive — Plataforma Educativa • Hecho con ❤️
  </footer>
</main>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
  lucide.createIcons();

  function toggleSidebar() {
    document.getElementById("sidebar").classList.toggle("open");
    document.getElementById("content").classList.toggle("push");
  }

  const body = document.body;
  const modeBtn = document.getElementById("modeBtn");
  const savedMode = localStorage.getItem("theme");

  if (savedMode === "dark") {
    body.classList.add("dark");
    modeBtn.innerHTML = '<i data-lucide="sun"></i> Claro';
  } else {
    body.classList.remove("dark");
    modeBtn.innerHTML = '<i data-lucide="moon"></i> Oscuro';
  }

  modeBtn.addEventListener("click", () => {
    const isDark = body.classList.toggle("dark");
    localStorage.setItem("theme", isDark ? "dark" : "light");
    modeBtn.innerHTML = isDark
      ? '<i data-lucide="sun"></i> Claro'
      : '<i data-lucide="moon"></i> Oscuro';
    lucide.createIcons();
  });
</script>
</body>
</html>
