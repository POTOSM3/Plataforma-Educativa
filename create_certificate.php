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
$MIN_APROBACION = 50; // Porcentaje mínimo para aprobar el quiz final

try {
    // 2. ¿CUÁNTAS LECCIONES TIENE ESTA MATERIA Y CUÁNTAS COMPLETÓ EL USUARIO?
    $stmt_total_lecciones = $pdo->prepare("SELECT COUNT(*) FROM lecciones WHERE curso_id = ?");
    $stmt_total_lecciones->execute([$curso_id]);
    $total_lecciones = (int) $stmt_total_lecciones->fetchColumn();

    $lecciones_completas = 0;
    if ($total_lecciones > 0) {
        $stmt_completas = $pdo->prepare("
            SELECT COUNT(*)
            FROM progreso_leccion pl
            JOIN lecciones l ON pl.leccion_id = l.id
            WHERE l.curso_id = ? AND pl.usuario_correo = ?
              AND pl.visto_pdf = 1 AND pl.visto_video = 1 AND pl.quiz_completado = 1
        ");
        $stmt_completas->execute([$curso_id, $usuario_correo]);
        $lecciones_completas = (int) $stmt_completas->fetchColumn();
    }

    $todas_lecciones_completas = ($total_lecciones > 0) ? ($lecciones_completas >= $total_lecciones) : true;

    // Si la materia no tiene lecciones cargadas todavía, usamos el formato viejo (pdf/video únicos) como respaldo
    if ($total_lecciones === 0) {
        $stmt_legacy = $pdo->prepare("SELECT visto_pdf, visto_video FROM progreso WHERE usuario_correo = ? AND curso_id = ?");
        $stmt_legacy->execute([$usuario_correo, $curso_id]);
        $legacy = $stmt_legacy->fetch(PDO::FETCH_ASSOC);
        $todas_lecciones_completas = ($legacy && $legacy['visto_pdf'] == 1 && $legacy['visto_video'] == 1);
    }

    // 3. BUSCAR EL QUIZ FINAL DE ESTA MATERIA Y SU ÚLTIMO RESULTADO
    $stmt_quiz_final = $pdo->prepare("SELECT id FROM quizzes WHERE curso_id = ? AND tipo = 'final' LIMIT 1");
    $stmt_quiz_final->execute([$curso_id]);
    $quiz_final_id = $stmt_quiz_final->fetchColumn();

    $porcentaje_obtenido = 0;
    $quiz_completado = false;

    if ($quiz_final_id) {
        $stmt_resultado = $pdo->prepare("
            SELECT porcentaje FROM resultados_quiz
            WHERE usuario_correo = ? AND quiz_id = ?
            ORDER BY id DESC LIMIT 1
        ");
        $stmt_resultado->execute([$usuario_correo, $quiz_final_id]);
        $resultado = $stmt_resultado->fetch(PDO::FETCH_ASSOC);
        $porcentaje_obtenido = $resultado['porcentaje'] ?? 0;
        $quiz_completado = ($resultado !== false);
    }

    $aprobado = $porcentaje_obtenido >= $MIN_APROBACION;

    // 4. VALIDACIÓN FINAL: lecciones completas + quiz final aprobado
    if (!$todas_lecciones_completas || !$quiz_completado || !$aprobado) {

        if ($quiz_completado && !$aprobado) {
            $mensaje_error = "El quiz final está completado, pero obtuviste {$porcentaje_obtenido}%. Necesitas al menos {$MIN_APROBACION}% para aprobar y obtener el certificado.";
        } elseif (!$todas_lecciones_completas) {
            $mensaje_error = "Debes completar todas las lecciones ({$lecciones_completas}/{$total_lecciones}) antes de generar el certificado.";
        } else {
            $mensaje_error = "Debes completar todos los materiales y aprobar el quiz final (mínimo {$MIN_APROBACION}%) para generar el certificado.";
        }

        $_SESSION['flash_error'] = $mensaje_error;
        header("Location: curso_detalle.php?id=" . urlencode($curso_id));
        exit();
    }

    // 5. MARCAR certificado_emitido = TRUE
    $sql_update = "
        INSERT INTO progreso (usuario_correo, curso_id, certificado_emitido)
        VALUES (?, ?, TRUE)
        ON DUPLICATE KEY UPDATE certificado_emitido = TRUE
    ";
    $stmt_update = $pdo->prepare($sql_update);
    $stmt_update->execute([$usuario_correo, $curso_id]);

    // 6. REDIRIGIR A LA PÁGINA DE VISUALIZACIÓN DEL CERTIFICADO
    header("Location: view_certificate.php?curso_id=" . urlencode($curso_id));
    exit();

} catch (PDOException $e) {
    error_log("Error al emitir certificado: " . $e->getMessage());
    $_SESSION['flash_error'] = "Error interno al procesar la solicitud.";
    header("Location: curso_detalle.php?id=" . urlencode($curso_id));
    exit();
}
?>
