<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

require 'conexion.php'; 
include 'components/layout.php'; // Incluye tu layout para mantener la estructura

$usuario = $_SESSION['usuario'];
$curso_id = $_GET['curso_id'] ?? null;

if (!$curso_id) {
    die("⚠️ Error: ID del curso no especificado.");
}

$curso_id = intval($curso_id);

// 1. OBTENER INFORMACIÓN DEL CURSO Y DEL USUARIO
try {
    // 1a. Información del curso
    $stmt_curso = $pdo->prepare("SELECT titulo FROM cursos WHERE id = ?");
    $stmt_curso->execute([$curso_id]);
    $curso = $stmt_curso->fetch(PDO::FETCH_ASSOC); // Importante: usar FETCH_ASSOC

    if (!$curso) {
        die("⚠️ Error: Curso no encontrado.");
    }
    $titulo_curso = htmlspecialchars($curso['titulo']);

    // 1b. Información del progreso y fecha
    // NOTA: 'updated_at' es necesaria para la fecha. Asumiré que esta columna existe en 'progreso'.
    $stmt_progreso = $pdo->prepare("SELECT certificado_emitido, updated_at FROM progreso WHERE usuario_correo = ? AND curso_id = ?");
    $stmt_progreso->execute([$usuario['correo'], $curso_id]);
    $progreso = $stmt_progreso->fetch(PDO::FETCH_ASSOC);

    if (!$progreso || $progreso['certificado_emitido'] != 1) {
        die("❌ Error: Certificado no emitido para este curso.");
    }
    
    // ------------------------------------------
    // DATOS PARA EL CERTIFICADO
    // ------------------------------------------
    
    $nombre_completo = htmlspecialchars($usuario['nombre']);
    
    // Usar la columna 'updated_at' de la tabla 'progreso' si existe
    // Si updated_at está null, usamos la fecha actual.
    if (!empty($progreso['updated_at'])) {
        // Formato la fecha de la base de datos (YYYY-MM-DD HH:MM:SS) a un formato legible
        $timestamp_emision = strtotime($progreso['updated_at']);
        $fecha_emision = date("d \d\e M, Y", $timestamp_emision);
    } else {
        // Usar fecha actual si no hay registro de actualización (Plan B)
        $fecha_emision = date("d \d\e M, Y"); 
    }
    
    $codigo_verificacion = strtoupper(substr(md5($usuario['correo'] . $curso_id . $fecha_emision), 0, 10));

} catch (PDOException $e) {
    error_log("Error al cargar datos del certificado: " . $e->getMessage());
    die("❌ Error interno del servidor al cargar los datos. Consulte el log de errores.");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Certificado - <?= $titulo_curso ?></title>
    <link rel="stylesheet" href="css/style_moderno.css"> 
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        /* Estilos específicos para el certificado */
        .certificado-border {
            border: 15px solid #06D6A0;
            padding: 30px;
            text-align: center;
            background-color: #1e2433; /* Fondo oscuro */
            color: #fff;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
            max-width: 900px;
            margin: 50px auto;
        }
        .certificado-header {
            color: #FFD166;
            font-size: 2.5rem;
            margin-bottom: 20px;
            font-family: serif; /* Estilo más formal */
        }
        .certificado-body h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: #ccc;
        }
        .certificado-nombre {
            font-size: 3rem;
            margin: 15px 0;
            color: #06D6A0; /* Color primario */
            font-family: cursive; /* Estilo de firma o logro */
            border-bottom: 2px dashed #06D6A0;
            padding-bottom: 5px;
            display: inline-block;
        }
        .certificado-curso {
            font-size: 2rem;
            margin: 20px 0 40px;
            color: #118AB2;
            font-weight: 700;
        }
        .certificado-footer {
            display: flex;
            justify-content: space-around;
            margin-top: 40px;
            font-size: 0.9rem;
            color: #aaa;
        }
    </style>
</head>
<body style="background-color: #141a29;">

<?php renderSidebar($usuario, 'cursos'); ?>

<main class="content" id="content">

    <div class="certificado-border">
        <div class="certificado-header">CERTIFICADO DE FINALIZACIÓN</div>
        
        <div class="certificado-body">
            <h3>Se otorga a</h3>
            <div class="certificado-nombre"><?= $nombre_completo ?></div>
            <h3>por la finalización exitosa del curso en línea:</h3>
            <div class="certificado-curso"><?= $titulo_curso ?></div>
            
            <p>Completado el: <b><?= $fecha_emision ?></b></p>
        </div>

        <div class="certificado-footer">
            <span>EduLive - Plataforma Educativa</span>
            <span>Código de Verificación: <?= $codigo_verificacion ?></span>
        </div>
    </div>

    <div style="text-align: center; margin-top: 30px;">
    <a href="download_certificate.php?curso_id=<?= $curso_id ?>" class="btn" style="background: #06D6A0; color: #141a29; margin-right: 10px;">
        <i data-lucide="download"></i> Descargar Certificado (PDF)
    </a>
    
    <a href="curso_detalle.php?id=<?= $curso_id ?>" class="btn" style="background: #dc3545; color: white;">
        <i data-lucide="arrow-left"></i> Volver al Curso
    </a>
</div>

</main>

</body>
</html>