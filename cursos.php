<?php
session_start();
if (!isset($_SESSION['usuario'])) {
  header("Location: login.php");
  exit;
}

$usuario = $_SESSION['usuario'];
$temas = json_decode(file_get_contents("data/temas.json"), true);
$inscripciones = file_exists("data/inscripciones.json")
  ? json_decode(file_get_contents("data/inscripciones.json"), true)
  : [];

include 'components/layout.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cursos - EduLive</title>
  <link rel="stylesheet" href="css/style_moderno.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>

<?php renderSidebar($usuario, 'cursos'); ?>

<main class="content" id="content">
  <section class="banner">
    <h1 class="title">📘 Cursos Disponibles</h1>
    <p class="desc">Selecciona los cursos que te interesen e inscríbete gratis.</p>
  </section>

  <section class="grid">
    <?php foreach ($temas as $t): 
      $icon = 'bookmark';
      switch (strtolower($t['titulo'])) {
        case 'lenguaje': $icon = 'book-open'; break;
        case 'matemática': $icon = 'calculator'; break;
        case 'ciencias': $icon = 'flask-conical'; break;
        case 'sociales': $icon = 'globe'; break;
        case 'inglés': $icon = 'message-circle'; break;
      }

      $ya_inscrito = false;
      foreach ($inscripciones as $i) {
        if ($i['correo'] === $usuario['correo'] && $i['id_curso'] == $t['id']) {
          $ya_inscrito = true;
          break;
        }
      }
    ?>
      <article class="card">
        <i data-lucide="<?= $icon ?>"></i>
        <h3><?= htmlspecialchars($t['titulo']) ?></h3>
        <p><?= htmlspecialchars($t['descripcion']) ?></p>
        <?php if ($ya_inscrito): ?>
          <a class="btn" style="background:linear-gradient(135deg,#06D6A0,#118AB2);color:white;"
            href="curso_detalle.php?id=<?= $t['id'] ?>">Inscrito ✔</a>
        <?php else: ?>
          <a class="btn" href="inscribirse.php?id=<?= $t['id'] ?>">Inscribirse (Gratis)</a>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
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
