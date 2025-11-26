<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

require 'conexion.php'; 

$usuario_correo = $_SESSION['usuario']['correo'] ?? null;
$curso_id = $_POST['tema'] ?? null; 
$respuestas_enviadas = $_POST['respuestas'] ?? [];

// 1. VALIDACIÓN BÁSICA DE DATOS
if (!$usuario_correo || !$curso_id) {
    die("⚠️ Error: Faltan datos necesarios para guardar el resultado.");
}

$curso_id = intval($curso_id);

// 2. OBTENER PREGUNTAS Y RESPUESTAS CORRECTAS DESDE LA BD
try {
    $sql_preguntas = "SELECT id, respuesta_correcta FROM preguntas_quiz WHERE curso_id = ?";
    $stmt = $pdo->prepare($sql_preguntas);
    $stmt->execute([$curso_id]);
    $preguntas_bd = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // Obtiene [id_pregunta => respuesta_correcta]

} catch (PDOException $e) {
    die("❌ Error al cargar las respuestas correctas desde la base de datos: " . $e->getMessage());
}

// 3. CALCULAR RESULTADO
$aciertos = 0;
$total_preguntas = count($preguntas_bd);

if ($total_preguntas === 0) {
    // Si no hay preguntas, redirigir sin guardar resultados
    echo "<script>alert('No hay preguntas para este quiz.'); window.location='panel.php';</script>";
    exit;
}

// Recorremos las preguntas que estaban en el formulario (basado en el índice)
$indices_correctos = array_values($preguntas_bd); // Convierte el array asociativo a uno indexado por número

foreach ($respuestas_enviadas as $index => $respuesta_usuario) {
    // Comparamos el índice de la respuesta del usuario con la respuesta correcta almacenada
    if (isset($indices_correctos[$index]) && $indices_correctos[$index] == $respuesta_usuario) {
        $aciertos++;
    }
}


// 4. PREPARAR DATOS PARA LA BD
$porcentaje = round(($aciertos / $total_preguntas) * 100, 2);
$nivel_logro = "Básico";

if ($porcentaje >= 90) {
    $nivel_logro = "Excelente";
} elseif ($porcentaje >= 70) {
    $nivel_logro = "Notable";
} elseif ($porcentaje >= 50) {
    $nivel_logro = "Aprobado";
}

// 5. INSERTAR EL RESULTADO EN LA BASE DE DATOS
try {
    $sql = "INSERT INTO resultados_quiz (
        usuario_correo, curso_id, aciertos, total_preguntas, porcentaje, nivel_logro
    ) VALUES (
        :correo, :curso_id, :aciertos, :total_preguntas, :porcentaje, :nivel_logro
    )";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':correo' => $usuario_correo,
        ':curso_id' => $curso_id,
        ':aciertos' => $aciertos,
        ':total_preguntas' => $total_preguntas,
        ':porcentaje' => $porcentaje,
        ':nivel_logro' => $nivel_logro
    ]);

    // 1. Registrar Quiz completado en la tabla progreso
    $sql_progreso = "
        INSERT INTO progreso (usuario_correo, curso_id, quiz_completado) 
        VALUES (?, ?, TRUE)
        ON DUPLICATE KEY UPDATE quiz_completado = TRUE
    ";
    $stmt_progreso = $pdo->prepare($sql_progreso);
    // Usamos $usuario_correo y $curso_id que ya están definidos arriba
    $stmt_progreso->execute([$usuario_correo, $curso_id]);
    
    // 2. Redirigir de vuelta al curso detalle para una experiencia más fluida y formal
    // Usamos el ID del curso para volver a la página específica
    header("Location: curso_detalle.php?id=" . urlencode($curso_id));
    exit(); // Detiene el script
    
    // ----------------------------------------------------------------------------------

} catch (PDOException $e) {
    die("❌ Error al guardar el resultado en la base de datos: " . $e->getMessage());
}

    // 6. REDIRIGIR AL PANEL DE RESULTADOS
    $mensaje = "✅ Quiz Completado: Obtuviste {$aciertos} de {$total_preguntas} preguntas correctas ({$porcentaje}%) - Nivel {$nivel_logro}";
    echo "<script>alert('{$mensaje}'); window.location='panel.php';</script>";
    exit;


?>