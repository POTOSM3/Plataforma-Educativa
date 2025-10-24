<?php
session_start();
if (!isset($_SESSION['usuario'])) { header('Location: login.php'); exit; }
$usuario = $_SESSION['usuario'];

$temas = [];
if (file_exists('data/temas.json')) {
  $temas = json_decode(file_get_contents('data/temas.json'), true);
}

ob_start();
?>
<section class="banner">
  <div>
    <h1 class="title">Hola, <?= htmlspecialchars($usuario['nombre']) ?> 👋</h1>
    <p class="desc">Tu materia de interés: <strong><?= htmlspecialchars($usuario['materia']) ?></strong>. Sigue aprendiendo con nuestros cursos y cuestionarios.</p>
  </div>
  <div class="mini-cta">
    <a class="btn" href="panel.php">Ver mi progreso</a>
  </div>
</section>

<section class="grid">
  <?php foreach ($temas as $t): ?>
    <article class="card">
      <?php
      $icon = 'bookmark';
      switch (strtolower($t['titulo'])) {
        case 'lenguaje': $icon='book-open'; break;
        case 'matemática': $icon='calculator'; break;
        case 'ciencias': $icon='flask-conical'; break;
        case 'sociales': $icon='globe'; break;
        case 'inglés': $icon='message-circle'; break;
      }
      ?>
      <i data-lucide="<?= $icon ?>"></i>
      <h3><?= htmlspecialchars($t['titulo']) ?></h3>
      <p><?= htmlspecialchars($t['descripcion']) ?></p>
      <a class="btn" href="temas.php?id=<?= $t['id'] ?>">Entrar al curso</a>
    </article>
  <?php endforeach; ?>
</section>
<?php
$content = ob_get_clean();
$page_title = 'Inicio — EduLive';
require 'components/layout.php';
