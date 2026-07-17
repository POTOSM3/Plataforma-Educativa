<?php
session_start();
if (!isset($_SESSION['usuario'])) {
  header("Location: login.php");
  exit;
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

$usuario = $_SESSION['usuario'];
require 'conexion.php';

// 🔄 MODIFICACIÓN: Cargar TODOS los cursos de la BD
try {
    // ESTA CONSULTA DEBE TRAER TODOS LOS REGISTROS
    $stmt = $pdo->query("SELECT id, titulo, descripcion, imagen FROM cursos ORDER BY titulo ASC");
    $temas = $stmt->fetchAll(); // Asegúrate de que $temas es un array con 3 o más elementos
} catch (PDOException $e) {
    $temas = [];
    error_log("Error al cargar cursos: " . $e->getMessage());
}

// Cargar inscripciones del usuario actual de la BD
$inscritos_ids = [];
if (!empty($usuario['correo'])) {
    try {
        $stmt = $pdo->prepare("SELECT curso_id FROM inscripciones WHERE usuario_correo = ?");
        $stmt->execute([$usuario['correo']]);
        $inscripciones_raw = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $inscritos_ids = array_flip($inscripciones_raw);
    } catch (PDOException $e) {
        error_log("Error al cargar inscripciones: " . $e->getMessage());
    }
}

$progreso_raw = [];
if (!empty($usuario['correo'])) {
    try {
        $stmt = $pdo->prepare("SELECT curso_id, visto_pdf, visto_video, quiz_completado, certificado_emitido FROM progreso WHERE usuario_correo = ?");
        $stmt->execute([$usuario['correo']]);
        // Esto crea un array asociativo donde la clave es el curso_id
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $progreso_raw[$row['curso_id']] = $row;
        }
    } catch (PDOException $e) {
        error_log("Error al cargar progreso: " . $e->getMessage());
    }
}

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
  <?php if (isset($_SESSION['flash_success'])): ?>
      <div style="background:#06D6A0; color:#141a29; padding:14px 18px; border-radius:10px; margin-bottom:15px; font-weight:600; display:flex; align-items:center; gap:8px;">
          ✅ <?= htmlspecialchars($_SESSION['flash_success']) ?>
      </div>
      <?php unset($_SESSION['flash_success']); ?>
  <?php endif; ?>
  <?php if (isset($_SESSION['flash_info'])): ?>
      <div style="background:#118AB2; color:white; padding:14px 18px; border-radius:10px; margin-bottom:15px; font-weight:600; display:flex; align-items:center; gap:8px;">
          ℹ️ <?= htmlspecialchars($_SESSION['flash_info']) ?>
      </div>
      <?php unset($_SESSION['flash_info']); ?>
  <?php endif; ?>
  <?php if (isset($_SESSION['flash_error'])): ?>
      <div style="background:#ef476f; color:white; padding:14px 18px; border-radius:10px; margin-bottom:15px; font-weight:600; display:flex; align-items:center; gap:8px;">
          ❌ <?= htmlspecialchars($_SESSION['flash_error']) ?>
      </div>
      <?php unset($_SESSION['flash_error']); ?>
  <?php endif; ?>
  <section class="banner">
    <h1 class="title">📘 Cursos Disponibles</h1>
    <p class="desc">Selecciona los cursos que te interesen e inscríbete gratis.</p>
  </section>
<section class="filter-bar">
    <div class="search-box">
        <i data-lucide="search"></i>
        <input type="text" id="searchInput" placeholder="Buscar por título o descripción..." onkeyup="filterCourses()">
    </div>
    
    <div class="filter-dropdown">
        <label for="filterSelect">Mostrar:</label>
        <select id="filterSelect" onchange="filterCourses()">
            <option value="all">Todos los cursos</option>
            <option value="inscritos">Solo Inscritos</option>
            <option value="no-inscritos">Solo Disponibles</option>
        </select>
    </div>
</section>
  <section class="grid">
    <?php if (empty($temas)): ?>
      <p style="text-align:center; width:100%; font-size:1.2rem;">No hay cursos disponibles actualmente.</p>
    <?php endif; ?>
    
    <?php $paleta_iconos = ['#06B6D4', '#F9A825', '#3B82F6', '#06D6A0', '#A78BFA', '#EF476F']; ?>
    <?php foreach ($temas as $t): ?>
      <?php
        $ya_inscrito = isset($inscritos_ids[$t['id']]);
        $id_curso_url = urlencode($t['id']);
        $color_icono = $paleta_iconos[$t['id'] % count($paleta_iconos)];
      ?>
 <article class="card">
    <div class="card-img" style="background: linear-gradient(135deg, <?= $color_icono ?>33, <?= $color_icono ?>11); color: <?= $color_icono ?>;">
        <i data-lucide="<?= htmlspecialchars($t['imagen']) ?>"></i>
    </div>
    
    <h3><?= htmlspecialchars($t['titulo']) ?></h3>
    <p><?= htmlspecialchars($t['descripcion']) ?></p>

    <?php 
    // Comprobar si el usuario está inscrito en el curso actual ($t)
    $ya_inscrito = isset($inscritos_ids[$t['id']]);
    ?>

    <?php if ($ya_inscrito): ?>
        
        <?php 
        // Usamos $t['id'] porque $t es la variable de la tarjeta actual en el bucle
        $curso_id = $t['id']; 
        
        // Obtenemos el progreso para este curso o valores por defecto (0)
        // Usamos $progreso_raw cargado en el PHP superior
        $progreso = $progreso_raw[$curso_id] ?? ['visto_pdf' => 0, 'visto_video' => 0, 'quiz_completado' => 0];
        
        // Cálculo del progreso
        $total_pasos = 3;
        $pasos_completados = ($progreso['visto_pdf'] ? 1 : 0) + 
                             ($progreso['visto_video'] ? 1 : 0) + 
                             ($progreso['quiz_completado'] ? 1 : 0);
                             
        $porcentaje = ($pasos_completados / $total_pasos) * 100;
        $porcentaje_redondeado = round($porcentaje);

        // Verificamos si el certificado ha sido emitido (lo usamos en el botón)
        $certificado_emitido = $progreso['certificado_emitido'] ?? FALSE;
        ?>

        <div class="progress-container">
            <p class="progress-text">Progreso: <?= $porcentaje_redondeado ?>% completado</p>
            <div class="progress-bar-bg">
                <div class="progress-bar-fill" style="width: <?= $porcentaje_redondeado ?>%;"></div>
            </div>
        </div>
        <a class="btn" style="background:linear-gradient(135deg,#06D6A0,#118AB2);color:white; margin-top: 10px;"
            href="curso_detalle.php?id=<?= $t['id'] ?>">Ver Curso</a>
        
        <?php if ($porcentaje_redondeado === 100): ?>
            <a class="btn" style="background:#FFD166; color:#141a29; margin-top: 5px;"
                href="create_certificate.php?curso_id=<?= $t['id'] ?>">
                <?= $certificado_emitido ? 'Certificado Obtenido 🏅' : '¡Obtén Certificado!' ?>
            </a>
        <?php endif; ?>

        <a class="btn" style="background:#dc3545; color:white; margin-top: 5px;"
            href="desinscribirse.php?id=<?= $t['id'] ?>"
            onclick="return abrirModalDesinscribir(event, this, '<?= htmlspecialchars($t['titulo'], ENT_QUOTES) ?>');">Desinscribirse ❌</a>
            
    <?php else: ?>
        <a class="btn" href="inscribirse.php?id=<?= $t['id'] ?>"
            onclick="return abrirModalInscribir(event, this, '<?= htmlspecialchars($t['titulo'], ENT_QUOTES) ?>', '<?= htmlspecialchars($t['descripcion'], ENT_QUOTES) ?>');">Inscribirse (Gratis)</a>
    <?php endif; ?>
</article>
    <?php endforeach; ?>
  </section>

  <footer class="footer">
    © <?= date('Y') ?> EduLive — Plataforma Educativa • Hecho con ❤️
  </footer>
</main>

<!-- Modal de confirmación propio (reemplaza el confirm() nativo del navegador) -->
<div id="modalDesinscribir" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:#1e2433; border-radius:16px; max-width:400px; width:90%; overflow:hidden; box-shadow:0 10px 40px rgba(0,0,0,0.5);">
        <div style="background:linear-gradient(120deg, #6C63FF, #00D4FF); padding:20px; text-align:center;">
            <i data-lucide="log-out" style="width:36px; height:36px; color:#fff;"></i>
            <h3 style="color:#fff; margin:10px 0 0;">¿Desinscribirte del curso?</h3>
        </div>
        <div style="padding:20px; color:#f0f0f0; text-align:center;">
            <p id="modalDesinscribirTexto" style="margin:0 0 20px;"></p>
            <div style="display:flex; gap:10px;">
                <button onclick="cerrarModalDesinscribir()" style="flex:1; padding:12px; border-radius:10px; border:1px solid #444; background:transparent; color:#f0f0f0; font-weight:600; cursor:pointer;">Cancelar</button>
                <a id="modalDesinscribirConfirmar" href="#" style="flex:1; padding:12px; border-radius:10px; background:#dc3545; color:#fff; font-weight:700; text-align:center; text-decoration:none; cursor:pointer;">Sí, desinscribirme</a>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmación de inscripción -->
<div id="modalInscribir" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:#1e2433; border-radius:16px; max-width:420px; width:90%; overflow:hidden; box-shadow:0 10px 40px rgba(0,0,0,0.5);">
        <div style="background:linear-gradient(120deg, #00D4FF, #06D6A0); padding:25px; text-align:center;">
            <i data-lucide="graduation-cap" style="width:40px; height:40px; color:#fff;"></i>
            <h3 id="modalInscribirTitulo" style="color:#fff; margin:12px 0 0; font-size:1.4rem;"></h3>
        </div>
        <div style="padding:20px; color:#f0f0f0; text-align:center;">
            <p id="modalInscribirDesc" style="margin:0 0 15px; opacity:0.85;"></p>
            <p style="margin:0 0 20px; font-size:0.9rem; background:rgba(6,214,160,0.15); color:#06D6A0; padding:10px; border-radius:8px;">
                ✓ Es totalmente gratis y puedes desinscribirte cuando quieras.
            </p>
            <div style="display:flex; gap:10px;">
                <button onclick="cerrarModalInscribir()" style="flex:1; padding:12px; border-radius:10px; border:1px solid #444; background:transparent; color:#f0f0f0; font-weight:600; cursor:pointer;">Cancelar</button>
                <a id="modalInscribirConfirmar" href="#" style="flex:1; padding:12px; border-radius:10px; background:linear-gradient(120deg, #00D4FF, #06D6A0); color:#141a29; font-weight:700; text-align:center; text-decoration:none; cursor:pointer;">Sí, inscribirme</a>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
    function abrirModalDesinscribir(event, elemento, nombreCurso) {
        event.preventDefault();
        document.getElementById('modalDesinscribirTexto').textContent =
            'Vas a perder tu progreso guardado en "' + nombreCurso + '". Esta acción no se puede deshacer.';
        document.getElementById('modalDesinscribirConfirmar').href = elemento.getAttribute('href');
        document.getElementById('modalDesinscribir').style.display = 'flex';
        lucide.createIcons();
        return false;
    }
    function cerrarModalDesinscribir() {
        document.getElementById('modalDesinscribir').style.display = 'none';
    }

    function abrirModalInscribir(event, elemento, nombreCurso, descripcionCurso) {
        event.preventDefault();
        document.getElementById('modalInscribirTitulo').textContent = nombreCurso;
        document.getElementById('modalInscribirDesc').textContent = descripcionCurso;
        document.getElementById('modalInscribirConfirmar').href = elemento.getAttribute('href');
        document.getElementById('modalInscribir').style.display = 'flex';
        lucide.createIcons();
        return false;
    }
    function cerrarModalInscribir() {
        document.getElementById('modalInscribir').style.display = 'none';
    }
</script>
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
    lucide.createIcons(); // Vuelve a dibujar los íconos
  });
  // -// --- LÓGICA DE FILTRADO Y BÚSQUEDA ---
function filterCourses() {
    const searchInput = document.getElementById('searchInput').value.toLowerCase();
    const filterSelect = document.getElementById('filterSelect').value;
    const cards = document.querySelectorAll('.grid .card');

    cards.forEach(card => {
        const title = card.querySelector('h3').textContent.toLowerCase();
        const description = card.querySelector('p').textContent.toLowerCase();
        
        // 🚨 CORRECCIÓN APLICADA AQUÍ 🚨
        // Un curso está inscrito si el texto del primer enlace NO es 'Inscribirse (Gratis)'.
        const firstLinkText = card.querySelector('a').textContent;
        const isInscrito = !firstLinkText.includes('Inscribirse (Gratis)');
        
        // 1. FILTRO POR BÚSQUEDA
        const matchesSearch = title.includes(searchInput) || description.includes(searchInput);

        // 2. FILTRO POR ESTADO
        let matchesFilter = true;
        if (filterSelect === 'inscritos') {
            matchesFilter = isInscrito; // Utiliza la condición corregida
        } else if (filterSelect === 'no-inscritos') {
            matchesFilter = !isInscrito; // Utiliza la condición corregida
        }
        
        // Mostrar u ocultar tarjeta
        if (matchesSearch && matchesFilter) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}
// ---------------------------------------------------------------------------------
</script>

</body>
</html>