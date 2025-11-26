<?php
session_start();
if (!isset($_SESSION['usuario'])) { header('Location: login.php'); exit; }

require 'conexion.php'; // Incluir la conexión a la BD

// 🔄 MODIFICACIÓN: Cargar el nombre del curso de la BD para el banner
$id = isset($_GET['id']) ? (int)$_GET['id'] : 1;
$tema = null;

try {
    $stmt = $pdo->prepare("SELECT id, titulo, descripcion FROM cursos WHERE id = ?");
    $stmt->execute([$id]);
    $curso_info = $stmt->fetch();
    
    // Usar la información de la BD, manteniendo la estructura para la lógica de lecciones si es posible
    if ($curso_info) {
        $tema = $curso_info;
    }
} catch (PDOException $e) {
    error_log("Error al cargar curso en temas.php: " . $e->getMessage());
}

// ⚠️ Mantenemos la carga de temas.json temporalmente SOLO para la estructura de lecciones
// (Esto debe ser migrado a la BD en el futuro)
$temas_full = file_exists('data/temas.json') ? json_decode(file_get_contents('data/temas.json'), true) : [];
$tema_detallado = null;
foreach ($temas_full as $t) if ($t['id'] === $id) { $tema_detallado = $t; break; }
if (!$tema_detallado && $temas_full) $tema_detallado = $temas_full[0];


$lecciones_totales = 0; foreach (($tema_detallado['modulos'] ?? []) as $m) $lecciones_totales += count($m['lecciones']);
$progreso = 0; // (a futuro: calcular real desde progreso.json)

$leccion_activa = $_GET['leccion'] ?? null;
$contenido = null;
foreach (($tema_detallado['modulos'] ?? []) as $m)
  foreach ($m['lecciones'] as $l)
    if ($l['titulo']===$leccion_activa) { $contenido=$l; break 2; }

ob_start();
?>
<section class="banner">
  <div>
    <h1 class="title">📘 Curso de <?= htmlspecialchars($tema['titulo'] ?? 'Cursos') ?></h1>
    <p class="desc"><?= htmlspecialchars($tema['descripcion'] ?? '') ?></p>
  </div>
  </section>