<?php
$tema = $_GET['tema'] ?? '';
$ruta = "data/preguntas_{$tema}.json";

if (!file_exists($ruta)) {
  die("<h2>❌ Tema no encontrado</h2>");
}

$preguntas = json_decode(file_get_contents($ruta), true);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Cuestionario - <?= ucfirst($tema) ?></title>
  <link rel="stylesheet" href="css/styles.css">
</head>
<body>
  <div class="container">
    <h1>🧠 Cuestionario: <?= ucfirst($tema) ?></h1>

    <form action="guardar_resultado.php" method="POST">
      <input type="hidden" name="tema" value="<?= htmlspecialchars($tema) ?>">
      <?php foreach ($preguntas as $i => $p): ?>
        <div class="pregunta">
          <h3><?= ($i+1) . ". " . htmlspecialchars($p['pregunta']) ?></h3>
          <?php foreach ($p['opciones'] as $j => $op): ?>
            <label>
              <input type="radio" name="respuestas[<?= $i ?>]" value="<?= $j ?>" required>
              <?= htmlspecialchars($op) ?>
            </label><br>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
      <button class="btn">Enviar respuestas</button>
    </form>
  </div>
</body>
</html>
