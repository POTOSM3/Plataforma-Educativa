<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

require 'conexion.php';

$usuario_correo = $_SESSION['usuario']['correo'] ?? null;
$quiz_id = $_POST['quiz_id'] ?? null;
$respuestas_enviadas = $_POST['respuestas'] ?? [];

// 1. VALIDACIÓN BÁSICA DE DATOS
if (!$usuario_correo || !$quiz_id) {
    $_SESSION['flash_error'] = "Faltan datos necesarios para guardar el resultado.";
    header("Location: cursos.php");
    exit;
}

$quiz_id = intval($quiz_id);

// 2. OBTENER DATOS DEL QUIZ (tipo, curso, lección si aplica)
try {
    $stmt_quiz = $pdo->prepare("
        SELECT q.id, q.tipo,
               COALESCE(q.curso_id, l.curso_id) AS curso_id,
               l.id AS leccion_id
        FROM quizzes q
        LEFT JOIN lecciones l ON l.quiz_id = q.id
        WHERE q.id = ?
    ");
    $stmt_quiz->execute([$quiz_id]);
    $quiz_data = $stmt_quiz->fetch(PDO::FETCH_ASSOC);

    if (!$quiz_data || !$quiz_data['curso_id']) {
        $_SESSION['flash_error'] = "No se pudo identificar el quiz o la materia a la que pertenece.";
        header("Location: cursos.php");
        exit;
    }

    $curso_id = $quiz_data['curso_id'];
    $es_final = ($quiz_data['tipo'] === 'final');
    $leccion_id = $quiz_data['leccion_id'];

} catch (PDOException $e) {
    die("❌ Error al identificar el quiz: " . $e->getMessage());
}

// 3. OBTENER PREGUNTAS Y RESPUESTAS CORRECTAS (mismo orden que se mostraron en quiz.php)
try {
    $stmt = $pdo->prepare("
        SELECT p.id, p.respuesta_correcta
        FROM leccion_quiz lq
        JOIN preguntas_quiz p ON lq.pregunta_id = p.id
        WHERE lq.quiz_id = ?
        ORDER BY lq.orden ASC
    ");
    $stmt->execute([$quiz_id]);
    $preguntas_bd = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // [id_pregunta => respuesta_correcta]

} catch (PDOException $e) {
    die("❌ Error al cargar las respuestas correctas desde la base de datos: " . $e->getMessage());
}

// 4. CALCULAR RESULTADO
$aciertos = 0;
$total_preguntas = count($preguntas_bd);

if ($total_preguntas === 0) {
    $_SESSION['flash_error'] = "No hay preguntas para este quiz.";
    header("Location: curso_detalle.php?id=" . urlencode($curso_id));
    exit;
}

// Recorremos las preguntas en el mismo orden en que se mostraron (basado en el índice)
$indices_correctos = array_values($preguntas_bd);

foreach ($respuestas_enviadas as $index => $respuesta_usuario) {
    if (isset($indices_correctos[$index]) && $indices_correctos[$index] == $respuesta_usuario) {
        $aciertos++;
    }
}

// 5. PREPARAR DATOS PARA LA BD
$porcentaje = round(($aciertos / $total_preguntas) * 100, 2);
$nivel_logro = "Básico";

if ($porcentaje >= 90) {
    $nivel_logro = "Excelente";
} elseif ($porcentaje >= 70) {
    $nivel_logro = "Notable";
} elseif ($porcentaje >= 50) {
    $nivel_logro = "Aprobado";
}

// 6. GUARDAR RESULTADO Y ACTUALIZAR PROGRESO
try {
    $pdo->beginTransaction();

    $sql = "INSERT INTO resultados_quiz (
        usuario_correo, curso_id, quiz_id, aciertos, total_preguntas, porcentaje, nivel_logro
    ) VALUES (
        :correo, :curso_id, :quiz_id, :aciertos, :total_preguntas, :porcentaje, :nivel_logro
    )";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':correo' => $usuario_correo,
        ':curso_id' => $curso_id,
        ':quiz_id' => $quiz_id,
        ':aciertos' => $aciertos,
        ':total_preguntas' => $total_preguntas,
        ':porcentaje' => $porcentaje,
        ':nivel_logro' => $nivel_logro
    ]);

    if ($es_final) {
        // Quiz FINAL de la materia -> actualiza el progreso general del curso
        $sql_progreso = "
            INSERT INTO progreso (usuario_correo, curso_id, quiz_completado)
            VALUES (?, ?, TRUE)
            ON DUPLICATE KEY UPDATE quiz_completado = TRUE
        ";
        $stmt_progreso = $pdo->prepare($sql_progreso);
        $stmt_progreso->execute([$usuario_correo, $curso_id]);
    } elseif ($leccion_id) {
        // Quiz de LECCIÓN individual -> actualiza el progreso de esa lección
        $sql_progreso_leccion = "
            INSERT INTO progreso_leccion (usuario_correo, leccion_id, quiz_completado)
            VALUES (?, ?, TRUE)
            ON DUPLICATE KEY UPDATE quiz_completado = TRUE
        ";
        $stmt_pl = $pdo->prepare($sql_progreso_leccion);
        $stmt_pl->execute([$usuario_correo, $leccion_id]);
    }

    $pdo->commit();

    $_SESSION['flash_success'] = "Quiz completado: obtuviste {$aciertos} de {$total_preguntas} preguntas correctas ({$porcentaje}%) — Nivel {$nivel_logro}.";
    header("Location: curso_detalle.php?id=" . urlencode($curso_id));
    exit();

} catch (PDOException $e) {
    $pdo->rollBack();
    die("❌ Error al guardar el resultado en la base de datos: " . $e->getMessage());
}
?>
