<?php
session_start();
if (!isset($_SESSION['usuario'])) {
  header("Location: login.php");
  exit;
}

$usuario = $_SESSION['usuario'];

// 🔄 MODIFICACIÓN: Conexión y carga de cursos desde la BD
require 'conexion.php'; 
try {
    // Carga los primeros 3 cursos para mostrarlos como destacados
    $stmt = $pdo->query("SELECT id, titulo, descripcion, imagen FROM cursos LIMIT 3");
    $temas = $stmt->fetchAll();
    
    // Contar inscripciones del usuario para el Panel de Información
    $stmt_inscritos = $pdo->prepare("SELECT COUNT(*) FROM inscripciones WHERE usuario_correo = ?");
    $stmt_inscritos->execute([$usuario['correo']]);
    $total_inscritos = $stmt_inscritos->fetchColumn();

} catch (PDOException $e) {
    $temas = [];
    $total_inscritos = 0;
    error_log("Error al cargar datos en index.php: " . $e->getMessage());
}

include 'components/layout.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inicio - EduLive</title>
  
  <script>
    (function() {
      const savedMode = localStorage.getItem('theme');
      if (savedMode === 'dark') {
        document.documentElement.classList.add('dark');
        document.documentElement.style.background = '#0f172a';
        document.body && (document.body.style.background = '#0f172a');
      } else {
        document.documentElement.classList.remove('dark');
        document.documentElement.style.background = '#f1f5f9';
        document.body && (document.body.style.background = '#f1f5f9');
      }
    })();
  </script>

  <link rel="stylesheet" href="css/style_moderno.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body class="inicio-page">

<?php renderSidebar($usuario, 'inicio'); ?>

<main class="content" id="content">
  <section class="banner">
    <h1 class="title">👋 ¡Hola, <?= htmlspecialchars($usuario['nombre']) ?>!</h1>
    <p class="desc">Es hora de aprender algo nuevo. Tu camino al éxito académico empieza aquí.</p>
  </section>

  <section class="dashboard-panels">
    <div class="panel-card" style="background:#06B6D4; color:white;">
      <i data-lucide="book-open"></i>
      <h3><?= $total_inscritos ?> Cursos Inscritos</h3>
      <p>Continúa tu progreso.</p>
      <a href="cursos.php" class="panel-btn">Ver todos</a>
    </div>
    
    <div class="panel-card" style="background:#F9A825; color:black;">
      <i data-lucide="award"></i>
      <h3>Próximo Quiz</h3>
      <p>Matemática - Geometría.</p>
      <a href="quiz.php?tema=2" class="panel-btn">Ir al Quiz</a>
    </div>
    
    <div class="panel-card" style="background:#3B82F6; color:white;">
      <i data-lucide="layout-dashboard"></i>
      <h3>Mi Progreso</h3>
      <p>Revisa tus notas y logros.</p>
      <a href="panel.php" class="panel-btn">Ir al Panel</a>
    </div>
  </section>

  <h2 style="margin-top:2rem; margin-bottom:1rem; font-size:1.5rem; border-bottom: 2px solid var(--accent); padding-bottom: 5px;">Cursos Destacados</h2>

  <section class="grid">
    <?php foreach ($temas as $t): ?>
      <article class="card">
        <i data-lucide="bookmark"></i>
        <h3><?= htmlspecialchars($t['titulo']) ?></h3>
        <p><?= htmlspecialchars($t['descripcion']) ?></p>
        <a href="curso_detalle.php?id=<?= $t['id'] ?>" class="btn">Ver curso</a>
      </article>
    <?php endforeach; ?>
  </section>

  <footer class="footer">
    © <?= date('Y') ?> EduLive — Plataforma Educativa • Hecho con ❤️
  </footer>
</main>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
  // ✅ Script de modo oscuro (Copiar del final de 'cursos.php' o 'contacto.php')
  lucide.createIcons();
  
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
    if (isDark) {
      localStorage.setItem("theme", "dark");
      modeBtn.innerHTML = '<i data-lucide="sun"></i> Claro';
    } else {
      localStorage.setItem("theme", "light");
      modeBtn.innerHTML = '<i data-lucide="moon"></i> Oscuro';
    }
    lucide.createIcons(); // Vuelve a dibujar los íconos de Lucide.
  });
</script>

</body>
</html>