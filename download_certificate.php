<?php
// === INICIO DE DEBUGGING: SI HAY UN ERROR, AHORA DEBE MOSTRARSE ===
error_reporting(E_ALL); 
ini_set('display_errors', 1); 
// ===================================================================

session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

// 1. Cargar el autoloader de Composer
// 🔴 ¡IMPORTANTE! VERIFICA ESTA RUTA. Si no funciona, prueba: 
// require dirname(__DIR__) . '/vendor/autoload.php'; (Si 'vendor' está un nivel arriba)
require 'vendor/autoload.php'; 
require 'conexion.php'; 

use Dompdf\Dompdf;
use Dompdf\Options;

$usuario = $_SESSION['usuario'];
$curso_id = $_GET['curso_id'] ?? null;

if (!$curso_id) {
    die("⚠️ Error: ID del curso no especificado.");
}

$curso_id = intval($curso_id);

// --- 2. RECOPILAR DATOS DEL CERTIFICADO ---
try {
    // 2a. Información del curso
    $stmt_curso = $pdo->prepare("SELECT titulo FROM cursos WHERE id = ?");
    $stmt_curso->execute([$curso_id]);
    $curso = $stmt_curso->fetch(PDO::FETCH_ASSOC);

    if (!$curso) {
        die("⚠️ Error: Curso no encontrado.");
    }
    $titulo_curso = htmlspecialchars($curso['titulo']);

    // 2b. Información del progreso y la fecha de finalización
    $stmt_progreso = $pdo->prepare("SELECT certificado_emitido, updated_at FROM progreso WHERE usuario_correo = ? AND curso_id = ?");
    $stmt_progreso->execute([$usuario['correo'], $curso_id]);
    $progreso = $stmt_progreso->fetch(PDO::FETCH_ASSOC);


    // 2c. VALIDACIÓN y DEFINICIÓN DE VARIABLES CRÍTICAS
    
    // VALIDACIÓN: El progreso y la bandera de emisión deben existir
    if (!$progreso || $progreso['certificado_emitido'] != 1) {
        die("⚠️ Error: El certificado no ha sido marcado para su emisión. Completa el curso primero.");
    }
    
    // 🟢 DEFINICIÓN DE VARIABLES FALTANTES (¡La causa más probable del fallo!) 🟢
    $nombre_completo = htmlspecialchars($usuario['nombre'] ?? 'Usuario Desconocido'); 
    $fecha_emision_raw = $progreso['updated_at'] ?? date('Y-m-d'); 
    $fecha_emision = date('d/m/Y', strtotime($fecha_emision_raw)); 
    $codigo_verificacion = strtoupper(substr(md5($usuario['correo'] . $curso_id . $fecha_emision_raw), 0, 10));

} catch (PDOException $e) {
    die("Error de base de datos: " . $e->getMessage());
}

// --- 3. CONSTRUCCIÓN DEL HTML ---

$html = '
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Certificado - ' . $titulo_curso . '</title>
    <style>
        @page { margin: 0; }
        body { 
            font-family: sans-serif; 
            margin: 0; 
            background: #fff;
        }
        .certificado-container {
            width: 100%;
            height: 100vh;
            padding: 50px;
            box-sizing: border-box;
            text-align: center;
            background-size: cover;
            color: #141a29;
        }
        h1 { font-size: 3rem; margin-top: 100px; color: #141a29; }
        .subtitulo { font-size: 1.5rem; margin-top: 50px; color: #141a29; }
        .nombre { 
            font-size: 3.5rem; 
            border-bottom: 4px solid #06D6A0; 
            display: inline-block;
            padding-bottom: 5px;
            margin-top: 15px;
            font-weight: 300;
        }
        .curso { 
            font-size: 2.5rem; 
            margin: 20px 0 50px; 
            font-weight: bold; 
            color: #141a29;
        }
        .fecha {
            margin-top: 15px;
            font-size: 1.1rem;
        }
        .footer-info { 
            margin-top: 50px; 
            font-size: 0.9rem; 
            color: #888;
        }
    </style>
</head>
<body>
    <div class="certificado-container">
        <h1>CERTIFICADO DE FINALIZACIÓN</h1>
        <h3 class="subtitulo">Se otorga a:</h3>
        <p class="nombre">' . $nombre_completo . '</p>
        <h3 class="subtitulo">por la finalización exitosa del curso en línea:</h3>
        <p class="curso">' . $titulo_curso . '</p>
        <p class="fecha">Completado el: <b>' . $fecha_emision . '</b></p>
        <div class="footer-info">
            <span>EduLive - Plataforma Educativa</span> | 
            <span>Código de Verificación: ' . $codigo_verificacion . '</span>
        </div>
    </div>
</body>
</html>
';

// --- 4. GENERACIÓN DEL PDF CON DOMPDF ---
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true); 

$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);

$dompdf->setPaper('A4', 'landscape'); 
$dompdf->render();

// Forzar la descarga del archivo
$file_name = "certificado_" . str_replace(' ', '_', $titulo_curso) . "_" . $usuario['id'] . ".pdf";

$dompdf->stream($file_name, [
    "Attachment" => true 
]);

exit;
?>