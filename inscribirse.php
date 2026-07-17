<?php
session_start();
if (!isset($_SESSION['usuario'])) {
  header("Location: login.php");
  exit;
}

$usuario = $_SESSION['usuario'];
require 'conexion.php';

$id_curso = isset($_GET['id']) ? intval($_GET['id']) : 0;
$correo_usuario = $usuario['correo'];

if ($id_curso <= 0) {
  // Redirigir si no hay ID válido
  header("Location: cursos.php");
  exit;
}

try {
    // 1. Verificar si el curso existe
    $stmt = $pdo->prepare("SELECT titulo FROM cursos WHERE id = ?");
    $stmt->execute([$id_curso]);
    $curso = $stmt->fetch();

    if (!$curso) {
        // Curso no existe, redirigir
        header("Location: cursos.php");
        exit;
    }
    
    // 2. Intentar registrar la inscripción
    $sql = "INSERT INTO inscripciones (usuario_correo, curso_id, fecha_inscripcion) 
            VALUES (?, ?, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$correo_usuario, $id_curso]);
    
    // Éxito: Redirigir al detalle del curso
    $nombre_curso = htmlspecialchars($curso['titulo']);
    $_SESSION['flash_success'] = "¡Felicitaciones! Te has inscrito en el curso {$nombre_curso}.";
    header("Location: curso_detalle.php?id={$id_curso}");
    exit;
    
} catch (PDOException $e) {
    // Error 23000 es la violación de la clave única (ya inscrito)
    if ($e->getCode() == 23000) {
        $nombre_curso = htmlspecialchars($curso['titulo'] ?? 'el curso');
        $_SESSION['flash_info'] = "Ya estás inscrito en {$nombre_curso}.";
        header("Location: curso_detalle.php?id={$id_curso}");
        exit;
    }
    
    // Otro error de BD
    error_log("Error de inscripción: " . $e->getMessage());
    $_SESSION['flash_error'] = "Error al procesar tu inscripción. Intenta de nuevo.";
    header("Location: cursos.php");
    exit;
}
?>