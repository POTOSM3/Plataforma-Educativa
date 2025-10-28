<?php
session_start();
if (!isset($_SESSION['usuario'])) {
  header("Location: login.php");
  exit;
}

$usuario = $_SESSION['usuario'];

// Rutas de archivos
$resultados_path = "data/resultados.json";
$temas_path = "data/temas.json";

// Cargar temas y resultados de forma segura
$temas = [];
$resultados = [];

// Cargar temas
if (file_exists($temas_path)) {
  $json_temas = file_get_contents($temas_path);
  $decoded_temas = json_decode($json_temas, true);
  if (is_array($decoded_temas)) $temas = $decoded_temas;
}

// Cargar resultados
if (file_exists($resultados_path)) {
  $json_resultados = file_get_contents($resultados_path);
  $decoded_resultados = json_decode($json_resultados, true);
  if (is_array($decoded_resultados)) {
    // Elimina resultados viejos tipo string
    foreach ($decoded_resultados as $r) {
      if (is_array($r)) $resultados[] = $r;
    }
  }
}

include 'components/layout.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel - EduLive</title>
  <link rel="stylesheet" href="css/style_moderno.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>

<?php renderSidebar($usuario, 'panel'); ?>

<main class="content" id="content">
  <section class="banner">
    <h1 class="title">🎓 Panel del Estudiante</h1>
    <p class="desc">Bienvenido, <?= htmlspecialchars($usuario['nombre']) ?>. Aquí ves tu desempeño y acceso rápido a cursos.</p>
    <a class="btn" href="cursos.php">Ir a cursos</a>
  </section>

  <section class="section">
    <h3>📊 Tus Resultados</h3>

    <?php
    // Filtrar solo resultados del usuario actual
    $mis_resultados = array_filter($resultados, fn($r) =>
      isset($r['correo']) && $r['correo'] === $usuario['correo']
    );
    ?>

    <?php if (empty($mis_resultados)): ?>
      <p style="padding: 1rem; background: var(--bg-card); border-radius: 10px; text-align:center;">
        ⚠️ Aún no tienes resultados registrados.
      </p>
    <?php else: ?>
      <table class="table">
        <tr>
          <th>Curso</th>
          <th>Aciertos</th>
          <th>Total</th>
          <th>Porcentaje</th>
          <th>Nivel</th>
          <th>Fecha</th>
        </tr>
        <?php foreach ($mis_resultados as $r): ?>
          <?php
            // Buscar el nombre del curso según su ID (tema)
            $curso_nombre = "Desconocido";
            if (isset($r['tema'])) {
              foreach ($temas as $t) {
                if ($t['id'] == $r['tema']) {
                  $curso_nombre = $t['titulo'];
                  break;
                }
              }
            }
          ?>
          <tr>
            <td><?= htmlspecialchars($curso_nombre) ?></td>
            <td><?= htmlspecialchars($r['aciertos'] ?? '-') ?></td>
            <td><?= htmlspecialchars($r['total'] ?? '-') ?></td>
            <td><?= htmlspecialchars($r['porcentaje'] ?? '-') ?>%</td>
            <td><?= htmlspecialchars($r['nivel'] ?? '-') ?></td>
            <td><?= htmlspecialchars($r['fecha'] ?? '-') ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
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
