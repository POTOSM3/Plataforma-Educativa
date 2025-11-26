<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

$usuario = $_SESSION['usuario'];
// 1. INCLUIR CONEXIÓN A LA BASE DE DATOS
require 'conexion.php'; 
//include 'components/layout.php';

$id = $_GET['id'] ?? null;
$curso = null;

// 2. BUSCAR CURSO EN LA BASE DE DATOS (PDO)
if ($id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM cursos WHERE id = ?");
        $stmt->execute([$id]);
        $curso = $stmt->fetch(); 
    } catch (PDOException $e) {
        // En caso de error de BD, el curso quedará como null
        error_log("Error al cargar detalle del curso: " . $e->getMessage());
    }
}

// Reemplaza las líneas 28 a 31 (la 'LÓGICA DE CÁLCULO' incompleta)
// =========================================================================
// === CARGA Y CÁLCULO DE PROGRESO PARA CERTIFICADO (CÓDIGO CORREGIDO) ===
// =========================================================================

// 1. Inicializar el array de progreso con valores por defecto (Falso/0)
$progreso_actual = ['visto_pdf' => 0, 'visto_video' => 0, 'quiz_completado' => 0, 'certificado_emitido' => 0];

// 2. Cargar datos si el curso y el usuario están definidos
if ($curso && isset($_SESSION['usuario']['correo'])) {
    try {
        // Consultar el progreso específico del usuario en este curso
        $sql_progreso = "SELECT visto_pdf, visto_video, quiz_completado, certificado_emitido FROM progreso WHERE usuario_correo = ? AND curso_id = ?";
        $stmt_progreso = $pdo->prepare($sql_progreso);
        $stmt_progreso->execute([$_SESSION['usuario']['correo'], $curso['id']]);
        $progreso_db = $stmt_progreso->fetch(PDO::FETCH_ASSOC);
        
        if ($progreso_db) {
            // Si hay progreso, sobrescribir los valores iniciales
            $progreso_actual = array_merge($progreso_actual, $progreso_db);
        }
    } catch (PDOException $e) {
        error_log("Error al cargar progreso para el certificado: " . $e->getMessage());
    }
}

// 3. LÓGICA DE CÁLCULO (Asegúrate que estas variables están definidas ahora)
$pasos_completados = ($progreso_actual['visto_pdf'] ? 1 : 0) + 
                     ($progreso_actual['visto_video'] ? 1 : 0) + 
                     ($progreso_actual['quiz_completado'] ? 1 : 0);

$progreso_alcanzado = ($pasos_completados === 3); // True si 3 de 3 pasos están completados
$certificado_emitido = $progreso_actual['certificado_emitido']; // Estado de la columna 'certificado_emitido'
// =========================================================================

$progreso_alcanzado = ($pasos_completados === 3); // True si 3 de 3 pasos están completados
$certificado_emitido = $progreso_actual['certificado_emitido']; // Estado de la columna 'certificado_emitido'

// >>> LÍNEAS AGREGADAS: Definir $primer_nombre para el saludo
$nombre_completo = $usuario['nombre'] ?? 'Usuario';
$nombre_usuario_array = explode(' ', $nombre_completo);
$primer_nombre = htmlspecialchars($nombre_usuario_array[0]);

// Ahora que todas las variables están definidas, incluimos el layout
include 'components/layout.php';

// ❌ ELIMINAR COMPLETAMENTE EL ARRAY PHP $cursos = [...]
// El código de aquí arriba reemplaza esa sección.
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
<?php if ($progreso_alcanzado): ?>
    <section class="certificate-section" style="margin-top: 40px; text-align: center; padding: 30px; border-radius: 10px; background-color: var(--color-card-bg, #1e2433); box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);">
        
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
<?php if (!$curso): ?>
    <h2>⚠️ Curso no encontrado o ID no válido</h2>

<?php else: ?>

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

        <p class="call-to-action" style="margin-top: 25px; font-weight: 600; font-size: 1.1rem;">
            A continuación, encontrarás tus materiales para comenzar:
        </p>
    </section>


<section class="grid">

    <article class="card">
        <i data-lucide="file-text"></i>
        <h3>📄 Guía en PDF</h3>
        <p>Descarga el material del curso. ¡Avanza un tercio de tu progreso!</p>
        
        <a href="registrar_progreso.php?curso_id=<?= $curso['id'] ?>&tipo=pdf&url_destino=<?= urlencode($curso['pdf_enlace']) ?>" class="btn" target="_blank">
            Ver PDF
        </a>
    </article>

    <article class="card">
        <i data-lucide="video"></i>
        <h3>🎥 Clase en video</h3>
        <p>Video explicativo del contenido. ¡Un paso más cerca del 100%!</p>
        
        <a href="registrar_progreso.php?curso_id=<?= $curso['id'] ?>&tipo=video&url_destino=<?= urlencode($curso['video_enlace']) ?>" class="btn" target="_blank">
            Ver Video
        </a>
    </article>

    <article class="card">
        <i data-lucide="award"></i>
        <h3>⭐ Haz el Quiz</h3>
        <p>Evalúa tus conocimientos al final. ¡La última parte del progreso!</p>
        
        <a href="<?= htmlspecialchars($curso["quiz_enlace"]) ?>" class="btn" style="background:#f9a825; color:black">
            Iniciar Quiz
        </a>
    </article>
    
</section>

<?php endif; ?>

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