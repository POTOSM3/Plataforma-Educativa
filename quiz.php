<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

require 'conexion.php'; // Asegúrate de que tu archivo de conexión esté incluido

$quiz_id = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : null;
$preguntas = [];
$curso_titulo = "Cuestionario";
$curso_id_del_quiz = null;
$es_quiz_final = false;

// 1. Obtener el quiz + su materia
try {
    $stmt_quiz = $pdo->prepare("
        SELECT q.id, q.titulo, q.tipo,
               COALESCE(q.curso_id, l.curso_id) AS curso_id,
               c.titulo AS curso_titulo_real
        FROM quizzes q
        LEFT JOIN lecciones l ON l.quiz_id = q.id
        LEFT JOIN cursos c ON c.id = COALESCE(q.curso_id, l.curso_id)
        WHERE q.id = ?
    ");
    $stmt_quiz->execute([$quiz_id]);
    $quiz_data = $stmt_quiz->fetch(PDO::FETCH_ASSOC);

    if ($quiz_data) {
        $curso_titulo = htmlspecialchars($quiz_data['titulo']);
        $curso_id_del_quiz = $quiz_data['curso_id'];
        $es_quiz_final = ($quiz_data['tipo'] === 'final');

        // 2. Obtener las preguntas conectadas a este quiz (vía leccion_quiz)
        $stmt_preguntas = $pdo->prepare("
            SELECT p.id, p.pregunta, p.opcion_a, p.opcion_b, p.opcion_c, p.opcion_d
            FROM leccion_quiz lq
            JOIN preguntas_quiz p ON lq.pregunta_id = p.id
            WHERE lq.quiz_id = ?
            ORDER BY lq.orden ASC
        ");
        $stmt_preguntas->execute([$quiz_id]);
        $preguntas = $stmt_preguntas->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    // Manejo de errores de base de datos
    error_log("Error al cargar preguntas o curso: " . $e->getMessage());
    $preguntas = [];
}

// Incluimos el layout que contiene la barra lateral
include 'components/layout.php';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $curso_titulo ?> - EduLive</title>
    <link rel="stylesheet" href="css/style_moderno.css"> 
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>

<?php renderSidebar($_SESSION['usuario'], 'cursos'); ?> 
    
<main class="content" id="content">
    
    <section class="banner">
        <h1 class="title">🧩 <?= $es_quiz_final ? 'Quiz Final: ' : 'Quiz: ' ?><?= $curso_titulo ?></h1>
        <p class="desc">Responde las siguientes preguntas. ¡Mucha suerte!</p>
    </section>

    <section class="quiz-section">
        <?php if (!empty($preguntas)): ?>
            <form action="guardar_resultado.php" method="POST" class="quiz-form">
                <input type="hidden" name="quiz_id" value="<?= $quiz_id ?>">

                <?php foreach ($preguntas as $i => $p): ?>
                    <div class="pregunta-card">
                        <h3><?= ($i + 1) . '. ' . htmlspecialchars($p['pregunta']) ?></h3>
                        <?php 
                        // Creamos un array de opciones para iterar fácilmente
                        $opciones = [
                            $p['opcion_a'], 
                            $p['opcion_b'], 
                            $p['opcion_c'], 
                            $p['opcion_d']
                        ];
                        foreach ($opciones as $j => $opcion): ?>
                            <label class="opcion">
                                <input type="radio" name="respuestas[<?= $i ?>]" value="<?= $j ?>" required>
                                <span><?= htmlspecialchars($opcion) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
                <button type="submit" class="btn btn-submit">Enviar Respuestas</button>
            </form>

        <?php else: ?>
            <div class="alert-box">
                <h3>⚠️ No hay preguntas disponibles</h3>
                <p>Este cuestionario aún no ha sido cargado. Por favor, revisa el curso o intenta más tarde.</p>
                <a href="<?= $curso_id_del_quiz ? 'curso_detalle.php?id=' . $curso_id_del_quiz : 'cursos.php' ?>" class="btn-back">Volver al Curso</a>
            </div>
        <?php endif; ?>
    </section>

    <footer class="footer">
        © <?= date('Y') ?> EduLive — Plataforma Educativa • Hecho con ❤️
    </footer>
</main>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
    lucide.createIcons();

    // --- LÓGICA DEL BOTÓN DE MODO OSCURO/CLARO ---
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
      lucide.createIcons();
    });
</script>

</body>
</html>