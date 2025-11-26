<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

require 'conexion.php'; 

// 1. OBTENER DATOS
$usuario_correo = $_SESSION['usuario']['correo'] ?? null;
$curso_id = $_GET['curso_id'] ?? null;

if (!$usuario_correo || !$curso_id) {
    die("⚠️ Error: Faltan datos del curso o del usuario.");
}

$curso_id = intval($curso_id);

try {
    // 2. BUSCAR PROGRESO (Asegurarse de que el curso está 100% completo)
    $stmt = $pdo->prepare("SELECT visto_pdf, visto_video, quiz_completado FROM progreso WHERE usuario_correo = ? AND curso_id = ?");
    $stmt->execute([$usuario_correo, $curso_id]);
    $progreso = $stmt->fetch(PDO::FETCH_ASSOC);

    // Si no hay registro o no está 100% completo, no se emite el certificado
    if (!$progreso || $progreso['visto_pdf'] != 1 || $progreso['visto_video'] != 1 || $progreso['quiz_completado'] != 1) {
        $_SESSION['error_certificado'] = "❌ Debes completar todos los materiales y el quiz antes de generar el certificado.";
        header("Location: curso_detalle.php?id=" . urlencode($curso_id));
        exit();
    }
    
    // 3. MARCAR certificado_emitido = TRUE
    $sql_update = "UPDATE progreso SET certificado_emitido = TRUE WHERE usuario_correo = ? AND curso_id = ?";
    $stmt_update = $pdo->prepare($sql_update);
    $stmt_update->execute([$usuario_correo, $curso_id]);
    
    // 4. REDIRIGIR A LA PÁGINA DE VISUALIZACIÓN DEL CERTIFICADO
    header("Location: view_certificate.php?curso_id=" . urlencode($curso_id));
    exit();

} catch (PDOException $e) {
    error_log("Error al emitir certificado: " . $e->getMessage());
    $_SESSION['error_certificado'] = "❌ Error interno al procesar la solicitud.";
    header("Location: curso_detalle.php?id=" . urlencode($curso_id));
    exit();
}
?>