<?php
session_start();
if (!isset($_SESSION['usuario'])) { header('Location: login.php'); exit; }
$usuario = $_SESSION['usuario'];

$archivo_resultados = 'data/resultados.json';
$resultados = file_exists($archivo_resultados) ? json_decode(file_get_contents($archivo_resultados), true) : [];
$archivo_temas = 'data/temas.json';
$temas = file_exists($archivo_temas) ? json_decode(file_get_contents($archivo_temas), true) : [];

$mis = array_values(array_filter($resultados, fn($r) => ($r['correo'] ?? '') === $usuario['correo']));

ob_start();
?>
<section class="banner">
  <div>
    <h1 class="title">Panel del estudiante 🎓</h1>
    <p class="desc">Bienvenido, <strong><?= htmlspecialchars($usuario['nombre']) ?></strong>. Aquí ves tu desempeño y acceso rápido a cursos.</p>
  </div>
  <div>
    <a href="temas.php" class="btn">Ir a cursos</a>
  </div>
</section>

<section class="section">
  <h3>📊 Tus Resultados</h3>
  <?php if (!$mis): ?>
    <p class="desc">Aún no tienes resultados. ¡Empieza un cuestionario desde cualquiera de tus cursos!</p>
  <?php else: ?>
    <div class="table-wrap" style="overflow:auto">
      <table style="width:100%; border-collapse:collapse">
        <thead>
          <tr style="background:#eef1ff">
            <th style="padding:12px;text-align:left">Curso</th>
            <th style="padding:12px">Aciertos</th>
            <th style="padding:12px">Total</th>
            <th style="padding:12px">Porcentaje</th>
            <th style="padding:12px">Nivel</th>
            <th style="padding:12px">Fecha</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($mis as $r): 
          $nombre_tema = 'Curso';
          foreach ($temas as $t) if ($t['id']==$r['tema']) { $nombre_tema=$t['titulo']; break; }
        ?>
          <tr style="border-top:1px solid #e7e9f3">
            <td style="padding:12px"><?= htmlspecialchars($nombre_tema) ?></td>
            <td style="padding:12px;text-align:center"><?= (int)$r['aciertos'] ?></td>
            <td style="padding:12px;text-align:center"><?= (int)$r['total'] ?></td>
            <td style="padding:12px;text-align:center"><?= (float)$r['porcentaje'] ?>%</td>
            <td style="padding:12px;text-align:center"><span class="badge"><?= htmlspecialchars($r['nivel']) ?></span></td>
            <td style="padding:12px;text-align:center"><?= htmlspecialchars($r['fecha']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
<?php
$content = ob_get_clean();
$page_title = 'Mi Panel — EduLive';
require 'components/layout.php';
