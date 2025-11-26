<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

// 1. Cargar el autoloader de Composer
// La ruta es relativa a la carpeta donde se encuentra este script
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

// --- 2. RECOPILAR DATOS DEL CERTIFICADO (CÓDIGO REPETIDO DE view_certificate.php) ---
try {
    // 2a. Información del curso
    $stmt_curso = $pdo->prepare("SELECT titulo FROM cursos WHERE id = ?");
    $stmt_curso->execute([$curso_id]);
    $curso = $stmt_curso->fetch(PDO::FETCH_ASSOC);

    if (!$curso) {
        die("⚠️ Error: Curso no encontrado.");
    }
    $titulo_curso = htmlspecialchars($curso['titulo']);

    // 2b. Información del progreso y fecha
    $stmt_progreso = $pdo->prepare("SELECT certificado_emitido, updated_at FROM progreso WHERE usuario_correo = ? AND curso_id = ?");
    $stmt_progreso->execute([$usuario['correo'], $curso_id]);
    $progreso = $stmt_progreso->fetch(PDO::FETCH_ASSOC);

    if (!$progreso || $progreso['certificado_emitido'] != 1) {
        die("❌ Error: Certificado no emitido o curso no completado.");
    }
    
    // Datos para el certificado
    $nombre_completo = htmlspecialchars($usuario['nombre']);
    
    // Manejo de la fecha
    $fecha_para_certificado = date("Y-m-d H:i:s");
    if (isset($progreso['updated_at'])) {
        $fecha_para_certificado = $progreso['updated_at'];
    } 
    $timestamp_emision = strtotime($fecha_para_certificado);
    $fecha_emision = date("d \d\e M, Y", $timestamp_emision);
    
    $codigo_verificacion = strtoupper(substr(md5($usuario['correo'] . $curso_id . $fecha_emision), 0, 10));

} catch (PDOException $e) {
    error_log("Error al cargar datos del certificado para descarga: " . $e->getMessage());
    die("❌ Error interno del servidor.");
}

// ----------------------------------------------------
// --- 3. GENERACIÓN DEL PDF CON DOMPDF ---
// ----------------------------------------------------

// 3a. Configuración de Dompdf
$options = new Options();
$options->set('defaultFont', 'Helvetica'); 
$options->set('isHtml5ParserEnabled', true);
$dompdf = new Dompdf($options);

// 3b. Contenido HTML del certificado (¡Solo el contenido!)
// Usa solo CSS básico, Dompdf no es un navegador completo.
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Certificado - ' . $titulo_curso . '</title>
    <style>
        /* CSS PRINCIPAL: Asegura que el contenido ocupe la página entera */
        @page { 
            margin: 0; /* Elimina todos los márgenes del PDF */
        }
        body { 
            font-family: sans-serif; 
            margin: 0; 
            padding: 0; 
            background-color: #ffffff; /* Fondo blanco */
        }
        
        /* Contenedor principal: Define el borde y el centrado */
        .certificado-container { 
            width: 90%; /* Ancho reducido para dejar espacio para el borde visual */
            height: 90%; /* Alto reducido para dejar espacio para el borde visual */
            margin: 5% auto; /* Centrado vertical y horizontal */
            padding: 40px; 
            text-align: center;
            /* Borde visual */
            border: 15px solid #06D6A0; 
            box-sizing: border-box;
            background-color: #ffffff; 
            color: #141a29;
            /* Flexbox para centrar el contenido dentro del contenedor */
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        /* Estilos de Tipografía */
        h1 { 
            color: #118AB2; 
            font-size: 3rem; 
            margin-bottom: 20px; 
        }
        .subtitulo { 
            font-size: 1.5rem; 
            color: #555; 
            margin-bottom: 5px; 
        }
        .nombre { 
            font-size: 4rem; 
            color: #06D6A0; 
            margin: 15px 0 25px; 
            font-family: serif;
            border-bottom: 2px dashed #06D6A0;
            display: inline-block;
            padding-bottom: 5px;
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

$dompdf->loadHtml($html);

// 3c. Renderizar y Descargar
$dompdf->setPaper('Letter', 'landscape'); // Orientación horizontal
$dompdf->render();

// Nombre del archivo para la descarga
$nombre_archivo_descarga = "Certificado_" . str_replace(" ", "_", $curso['titulo']) . ".pdf";

// Forzar la descarga
$dompdf->stream($nombre_archivo_descarga, ["Attachment" => true]);
exit();
?>