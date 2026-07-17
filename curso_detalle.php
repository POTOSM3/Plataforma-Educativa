<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

// Evita que el navegador muestre una versión guardada en caché de esta página,
// ya que el progreso cambia dinámicamente.
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

$usuario = $_SESSION['usuario'];
require 'conexion.php';

$id = $_GET['id'] ?? null;
$curso = null;
$lecciones = [];
$total_lecciones = 0;
$lecciones_completas = 0;
$todas_lecciones_completas = false;
$quiz_final_id = null;
$quiz_final_completado = false;
$quiz_final_porcentaje = 0;
$certificado_emitido = false;
$progreso_alcanzado = false;

if ($id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM cursos WHERE id = ?");
        $stmt->execute([$id]);
        $curso = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al cargar detalle del curso: " . $e->getMessage());
    }
}

if ($curso) {
    $correo_usuario = $usuario['correo'];

    try {
        // 1. Cargar las lecciones de esta materia, con el progreso del usuario en cada una
        $stmt_lecciones = $pdo->prepare("
            SELECT l.id, l.titulo, l.url_video, l.ruta_pdf, l.orden, l.quiz_id,
                   COALESCE(pl.visto_pdf, 0) AS visto_pdf,
                   COALESCE(pl.visto_video, 0) AS visto_video,
                   COALESCE(pl.quiz_completado, 0) AS quiz_completado
            FROM lecciones l
            LEFT JOIN progreso_leccion pl ON pl.leccion_id = l.id AND pl.usuario_correo = ?
            WHERE l.curso_id = ?
            ORDER BY l.orden ASC
        ");
        $stmt_lecciones->execute([$correo_usuario, $curso['id']]);
        $lecciones = $stmt_lecciones->fetchAll(PDO::FETCH_ASSOC);
        $total_lecciones = count($lecciones);

        foreach ($lecciones as $l) {
            if ($l['visto_pdf'] && $l['visto_video'] && $l['quiz_completado']) {
                $lecciones_completas++;
            }
        }
        $todas_lecciones_completas = ($total_lecciones > 0) && ($lecciones_completas >= $total_lecciones);

        // 2. Buscar el quiz final de esta materia y su último resultado
        $stmt_qf = $pdo->prepare("SELECT id FROM quizzes WHERE curso_id = ? AND tipo = 'final' LIMIT 1");
        $stmt_qf->execute([$curso['id']]);
        $quiz_final_id = $stmt_qf->fetchColumn() ?: null;

        if ($quiz_final_id) {
            $stmt_res = $pdo->prepare("SELECT porcentaje FROM resultados_quiz WHERE usuario_correo = ? AND quiz_id = ? ORDER BY id DESC LIMIT 1");
            $stmt_res->execute([$correo_usuario, $quiz_final_id]);
            $res = $stmt_res->fetch(PDO::FETCH_ASSOC);
            if ($res) {
                $quiz_final_completado = true;
                $quiz_final_porcentaje = $res['porcentaje'];
            }
        }

        // 3. Certificado
        $stmt_prog = $pdo->prepare("SELECT certificado_emitido FROM progreso WHERE usuario_correo = ? AND curso_id = ?");
        $stmt_prog->execute([$correo_usuario, $curso['id']]);
        $certificado_emitido = (bool) $stmt_prog->fetchColumn();

        // Formato viejo (materias sin lecciones cargadas todavía): revisamos pdf/video únicos
        if ($total_lecciones === 0) {
            $stmt_legacy = $pdo->prepare("SELECT visto_pdf, visto_video FROM progreso WHERE usuario_correo = ? AND curso_id = ?");
            $stmt_legacy->execute([$correo_usuario, $curso['id']]);
            $legacy = $stmt_legacy->fetch(PDO::FETCH_ASSOC);
            $todas_lecciones_completas = ($legacy && $legacy['visto_pdf'] == 1 && $legacy['visto_video'] == 1);
        }

        $progreso_alcanzado = $todas_lecciones_completas && $quiz_final_completado;

    } catch (PDOException $e) {
        error_log("Error al cargar lecciones/progreso: " . $e->getMessage());
    }
}

$nombre_completo = $usuario['nombre'] ?? 'Usuario';
$nombre_usuario_array = explode(' ', $nombre_completo);
$primer_nombre = htmlspecialchars($nombre_usuario_array[0]);

include 'components/layout.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Curso - <?= htmlspecialchars($curso["titulo"] ?? "Curso") ?></title>
    <link rel="stylesheet" href="css/style_moderno.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body style="background-color: #141a29;">

<?php renderSidebar($_SESSION['usuario'], 'cursos'); ?>

<main class="content" id="content">

<?php if (!$curso): ?>
    <h2>⚠️ Curso no encontrado o ID no válido</h2>

<?php else: ?>

  <div id="flashContainer">
      <?php if (isset($_SESSION['flash_success'])): ?>
          <div class="flash-toast" style="background:#06D6A0; color:#141a29;">
              ✅ <?= htmlspecialchars($_SESSION['flash_success']) ?>
          </div>
          <?php unset($_SESSION['flash_success']); ?>
      <?php endif; ?>
      <?php if (isset($_SESSION['flash_info'])): ?>
          <div class="flash-toast" style="background:#118AB2; color:white;">
              ℹ️ <?= htmlspecialchars($_SESSION['flash_info']) ?>
          </div>
          <?php unset($_SESSION['flash_info']); ?>
      <?php endif; ?>
      <?php if (isset($_SESSION['flash_error'])): ?>
          <div class="flash-toast" style="background:#ef476f; color:white;">
              ❌ <?= htmlspecialchars($_SESSION['flash_error']) ?>
          </div>
          <?php unset($_SESSION['flash_error']); ?>
      <?php endif; ?>
  </div>

  <a href="cursos.php" class="back-pill">
      <i data-lucide="arrow-left" style="width:16px; height:16px;"></i> Volver a Cursos
  </a>

  <?php if ($progreso_alcanzado): ?>
    <section class="certificate-section" style="margin-top: 20px; text-align: center; padding: 30px; border-radius: 10px; background-color: var(--color-card-bg, #1e2433); box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);">
        <?php if (!$certificado_emitido): ?>
            <h2 style="color: #06D6A0; font-size: 1.8rem; margin-bottom: 10px;">¡Felicitaciones, completaste el curso! 🎉</h2>
            <p style="color: var(--color-text, #ccc); margin-bottom: 20px;">Genera tu certificado de finalización ahora mismo y celebra tu logro.</p>
            <a href="create_certificate.php?curso_id=<?= $curso['id'] ?>" class="btn large-btn" style="background: #06D6A0; color: #141a29; margin-top: 15px; font-weight: 700; padding: 12px 25px; border-radius: 8px; text-transform: uppercase; display: inline-block;">
                <i data-lucide="award" style="margin-right: 8px;"></i> GENERAR CERTIFICADO
            </a>
        <?php else: ?>
             <h2 style="color: #FFD166; font-size: 1.8rem; margin-bottom: 10px;">Certificado Emitido 🏅</h2>
             <p style="color: var(--color-text, #ccc); margin-bottom: 20px;">Ya obtuviste tu certificado. Puedes verlo o descargarlo nuevamente.</p>
             <a href="view_certificate.php?curso_id=<?= $curso['id'] ?>" class="btn large-btn" style="background: #FFD166; color: #141a29; margin-top: 15px; font-weight: 700; padding: 12px 25px; border-radius: 8px; text-transform: uppercase; display: inline-block;">
                <i data-lucide="download" style="margin-right: 8px;"></i> VER/DESCARGAR
            </a>
        <?php endif; ?>
    </section>
  <?php endif; ?>

  <section class="banner unified-header">
        <p class="welcome-msg" style="font-size: 1.2rem; font-weight: 400; opacity: 0.9; margin-bottom: 5px;">
            ¡Hola, <?= $primer_nombre ?>! 👋 Bienvenido/a al curso de:
        </p>
        <h1 class="title" style="font-size: 2.8rem; margin-top: 0; margin-bottom: 10px;">
             <?= htmlspecialchars($curso["titulo"]) ?>
        </h1>
        <p class="desc" style="font-size: 1rem; opacity: 0.8;">
            <?= htmlspecialchars($curso["descripcion"]) ?>
        </p>
        <?php if ($total_lecciones > 0): ?>
        <div style="margin-top: 20px;">
            <div style="display:flex; justify-content:space-between; font-size:0.9rem; margin-bottom:6px;">
                <span>Progreso general</span>
                <span><?= $lecciones_completas ?> / <?= $total_lecciones ?> lecciones completas</span>
            </div>
            <div style="background: rgba(255,255,255,0.15); border-radius: 8px; height: 10px; overflow:hidden;">
                <div style="background: #fff; width: <?= $total_lecciones > 0 ? round(($lecciones_completas / $total_lecciones) * 100) : 0 ?>%; height: 100%;"></div>
            </div>
        </div>
        <?php endif; ?>
    </section>

<?php if ($total_lecciones > 0): ?>

    <h2 style="margin-top:2rem; margin-bottom:1rem; font-size:1.4rem; border-bottom: 2px solid var(--accent); padding-bottom: 5px;">📚 Lecciones</h2>

    <section class="grid">
        <?php foreach ($lecciones as $i => $l): ?>
        <?php
            $leccion_completa = $l['visto_pdf'] && $l['visto_video'] && $l['quiz_completado'];
            $pasos_leccion = $l['visto_pdf'] + $l['visto_video'] + $l['quiz_completado'];
        ?>
        <article class="card" style="<?= $leccion_completa ? 'border: 1px solid #06D6A0;' : '' ?>">
            <i data-lucide="<?= $leccion_completa ? 'check-circle' : 'book-open' ?>" style="color: <?= $leccion_completa ? '#06D6A0' : 'inherit' ?>;"></i>
            <h3><?= ($i + 1) ?>. <?= htmlspecialchars($l['titulo']) ?></h3>
            <div style="background: rgba(255,255,255,0.08); border-radius: 6px; height: 6px; overflow:hidden; margin: 10px 0;">
                <div style="background: #06D6A0; width: <?= round(($pasos_leccion / 3) * 100) ?>%; height: 100%;"></div>
            </div>

            <div style="display:flex; flex-direction:column; gap:8px; margin-top:10px;">
                <?php if ($l['url_video']): ?>
                <a href="registrar_progreso.php?leccion_id=<?= $l['id'] ?>&tipo=video&url_destino=<?= urlencode($l['url_video']) ?>" class="btn" target="_blank" style="<?= $l['visto_video'] ? 'background:#06D6A0; color:#141a29;' : '' ?>">
                    <?= $l['visto_video'] ? '✓ Video Visto' : 'Ver Video' ?>
                </a>
                <?php endif; ?>
                <?php if ($l['ruta_pdf']): ?>
                <a href="registrar_progreso.php?leccion_id=<?= $l['id'] ?>&tipo=pdf&url_destino=<?= urlencode($l['ruta_pdf']) ?>" class="btn" target="_blank" style="<?= $l['visto_pdf'] ? 'background:#06D6A0; color:#141a29;' : '' ?>">
                    <?= $l['visto_pdf'] ? '✓ PDF Visto' : 'Ver PDF' ?>
                </a>
                <?php endif; ?>
                <?php if ($l['quiz_id']): ?>
                <a href="quiz.php?quiz_id=<?= $l['quiz_id'] ?>" class="btn" style="<?= $l['quiz_completado'] ? 'background:#06D6A0; color:#141a29;' : 'background:#FFD166; color:#141a29;' ?>">
                    <?= $l['quiz_completado'] ? '✓ Quiz Completado' : 'Hacer Quiz' ?>
                </a>
                <?php endif; ?>
            </div>
        </article>
        <?php endforeach; ?>
    </section>

    <?php if ($quiz_final_id): ?>
        <h2 style="margin-top:2.5rem; margin-bottom:1rem; font-size:1.4rem; border-bottom: 2px solid var(--accent); padding-bottom: 5px;">🏁 Quiz Final</h2>
        <section class="grid">
            <article class="card" style="<?= !$todas_lecciones_completas ? 'opacity:0.6;' : '' ?>">
                <i data-lucide="award"></i>
                <h3>Evaluación Final de <?= htmlspecialchars($curso['titulo']) ?></h3>
                <?php if (!$todas_lecciones_completas): ?>
                    <p>🔒 Completa todas las lecciones primero para desbloquear el quiz final.</p>
                    <button class="btn" disabled style="opacity:0.5; cursor:not-allowed;">Bloqueado</button>
                <?php elseif ($quiz_final_completado): ?>
                    <p>Ya lo completaste con un <?= $quiz_final_porcentaje ?>%.</p>
                    <a href="quiz.php?quiz_id=<?= $quiz_final_id ?>" class="btn" style="background:#06D6A0; color:#141a29;">Volver a Intentar</a>
                <?php else: ?>
                    <p>Evalúa todo lo aprendido en esta materia. ¡Es el último paso!</p>
                    <a href="quiz.php?quiz_id=<?= $quiz_final_id ?>" class="btn" style="background:#f9a825; color:black;">Iniciar Quiz Final</a>
                <?php endif; ?>
            </article>
        </section>
    <?php endif; ?>

<?php else: ?>

    <p style="margin-top: 20px; opacity:0.8;">Esta materia todavía no tiene lecciones cargadas. Aquí tienes el material general mientras tanto:</p>

    <section class="grid">
        <article class="card">
            <i data-lucide="file-text"></i>
            <h3>📄 Guía en PDF</h3>
            <p>Descarga el material del curso.</p>
            <a href="registrar_progreso.php?curso_id=<?= $curso['id'] ?>&tipo=pdf&url_destino=<?= urlencode($curso['pdf_enlace']) ?>" class="btn" target="_blank">
                Ver PDF
            </a>
        </article>

        <article class="card">
            <i data-lucide="video"></i>
            <h3>🎥 Clase en video</h3>
            <p>Video explicativo del contenido.</p>
            <a href="registrar_progreso.php?curso_id=<?= $curso['id'] ?>&tipo=video&url_destino=<?= urlencode($curso['video_enlace']) ?>" class="btn" target="_blank">
                Ver Video
            </a>
        </article>

        <?php if (!empty($curso['quiz_enlace'])): ?>
        <article class="card">
            <i data-lucide="award"></i>
            <h3>⭐ Haz el Quiz</h3>
            <p>Evalúa tus conocimientos al final.</p>
            <a href="<?= htmlspecialchars($curso["quiz_enlace"]) ?>" class="btn" style="background:#f9a825; color:black">
                Iniciar Quiz
            </a>
        </article>
        <?php endif; ?>
    </section>

<?php endif; ?>
<?php endif; ?>

  <footer class="footer">
    © <?= date('Y') ?> EduLive — Plataforma Educativa • Hecho con ❤️
  </footer>
</main>

<style>
.back-pill {
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(255,255,255,0.08); color: var(--accent, #FFD166);
    padding: 8px 16px; border-radius: 30px; text-decoration: none;
    font-weight: 600; font-size: 0.9rem; margin-top: 15px;
    transition: background 0.2s;
}
.back-pill:hover { background: rgba(255,255,255,0.16); }
.flash-toast {
    padding: 14px 18px; border-radius: 10px; margin-top: 15px;
    font-weight: 600; display: flex; align-items: center; gap: 8px;
    transition: opacity 0.6s ease, transform 0.6s ease;
}
</style>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
  lucide.createIcons();

  // Las notificaciones (flash-toast) se autodesvanecen después de unos segundos
  setTimeout(() => {
    document.querySelectorAll('.flash-toast').forEach(toast => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(-10px)';
      setTimeout(() => toast.remove(), 600);
    });
  }, 4000);

  // --- LÓGICA DEL BOTÓN DE MODO OSCURO/CLARO ---
  const body = document.body;
  const modeBtn = document.getElementById("modeBtn");

  const savedMode = localStorage.getItem("theme");
  if (savedMode === "dark") {
    body.classList.add("dark");
    if (modeBtn) modeBtn.innerHTML = '<i data-lucide="sun"></i> Claro';
  } else {
    body.classList.remove("dark");
    if (modeBtn) modeBtn.innerHTML = '<i data-lucide="moon"></i> Oscuro';
  }

  if (modeBtn) {
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
  }
</script>

</body>
</html>
