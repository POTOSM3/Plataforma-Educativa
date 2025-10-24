<?php
session_start();
if (!isset($_SESSION['usuario'])) { header('Location: login.php'); exit; }

$temas = file_exists('data/temas.json') ? json_decode(file_get_contents('data/temas.json'), true) : [];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 1;
$tema = null;
foreach ($temas as $t) if ($t['id']===$id) { $tema=$t; break; }
if (!$tema && $temas) $tema = $temas[0];

$lecciones_totales = 0; foreach (($tema['modulos'] ?? []) as $m) $lecciones_totales += count($m['lecciones']);
$progreso = 0; // (a futuro: calcular real desde progreso.json)

$leccion_activa = $_GET['leccion'] ?? null;
$contenido = null;
foreach (($tema['modulos'] ?? []) as $m)
  foreach ($m['lecciones'] as $l)
    if ($l['titulo']===$leccion_activa) { $contenido=$l; break 2; }

ob_start();
?>
<section class="banner">
  <div>
    <h1 class="title">📘 Curso de <?= htmlspecialchars($tema['titulo'] ?? 'Cursos') ?></h1>
    <p class="desc"><?= htmlspecialchars($tema['descripcion'] ?? '') ?></p>
  </div>
  <div>
    <a class="btn" href="quiz.php?tema=<?= (int)$id ?>">Hacer cuestionario</a>
  </div>
</section>

<section class="section">
  <h3>🎯 Objetivos del curso</h3>
  <ul class="list">
    <?php foreach (($tema['objetivos'] ?? []) as $obj): ?>
      <li><span><?= htmlspecialchars($obj) ?></span></li>
    <?php endforeach; ?>
  </ul>
</section>

<section class="section">
  <h3>📚 Módulos y Lecciones</h3>
  <?php foreach (($tema['modulos'] ?? []) as $m): ?>
    <div class="section" style="margin-top:14px">
      <h4 style="margin:0 0 10px"><?= htmlspecialchars($m['titulo']) ?></h4>
      <ul class="list">
        <?php foreach ($m['lecciones'] as $l): ?>
          <li>
            <span><strong><?= htmlspecialchars($l['titulo']) ?></strong></span>
            <span>
              <span class="badge"><?= htmlspecialchars($l['tipo']) ?></span>
              <a class="btn" style="padding:8px 12px;margin-left:8px" href="temas.php?id=<?= (int)$id ?>&leccion=<?= urlencode($l['titulo']) ?>">Abrir</a>
            </span>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endforeach; ?>

  <?php if ($contenido): ?>
    <div class="viewer">
      <h3 style="margin:0 0 10px">📖 <?= htmlspecialchars($contenido['titulo']) ?></h3>
      <?php if (strtolower($contenido['tipo'])==='pdf'): ?>
        <iframe src="<?= htmlspecialchars($contenido['enlace']) ?>"></iframe>
      <?php elseif (strtolower($contenido['tipo'])==='video' && strpos($contenido['enlace'],'youtube.com')!==false):
        parse_str(parse_url($contenido['enlace'], PHP_URL_QUERY), $params);
        $vid = $params['v'] ?? '';
      ?>
        <iframe src="https://www.youtube.com/embed/<?= htmlspecialchars($vid) ?>" allowfullscreen></iframe>
      <?php else: ?>
        <div class="section"><a class="btn" href="<?= htmlspecialchars($contenido['enlace']) ?>" target="_blank">Abrir recurso externo</a></div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</section>
<?php
$content = ob_get_clean();
$page_title = 'Curso — EduLive';
require 'components/layout.php';
