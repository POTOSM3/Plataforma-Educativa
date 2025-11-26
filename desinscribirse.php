<?php
session_start();
if (!isset($_SESSION['usuario'])) {
  header("Location: login.php");
  exit;
}

// 🔌 CONEXIÓN A LA BD
require 'conexion.php'; 

$usuario = $_SESSION['usuario'];
$id_curso = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_curso <= 0) {
  // Redirige si el ID no es válido
  header("Location: cursos.php");
  exit;
}

$correo_usuario = $usuario['correo'];
$nombre_curso = '';

// 1. Opcional: Obtener el nombre del curso para el mensaje de éxito
try {
    $stmt = $pdo->prepare("SELECT titulo FROM cursos WHERE id = ?");
    $stmt->execute([$id_curso]);
    $nombre_curso = $stmt->fetchColumn(); 
} catch (PDOException $e) {
    // Manejo de error silencioso, no es crítico
    error_log("Error al obtener nombre del curso: " . $e->getMessage());
}


// 2. Eliminar la inscripción de la base de datos y el progreso
try {
    // 💡 INICIO DE LA TRANSACCIÓN: Asegura que ambas eliminaciones se hagan o ninguna.
    $pdo->beginTransaction();

    // A. ELIMINAR REGISTRO DE INSCRIPCIÓN
    $sql_inscripcion = "DELETE FROM inscripciones WHERE usuario_correo = ? AND curso_id = ?";
    $stmt_inscripcion = $pdo->prepare($sql_inscripcion);
    $stmt_inscripcion->execute([$correo_usuario, $id_curso]);

    // B. !!! [LÍNEA AGREGADA] ELIMINAR EL PROGRESO !!!
    $sql_progreso = "DELETE FROM progreso WHERE usuario_correo = ? AND curso_id = ?";
    $stmt_progreso = $pdo->prepare($sql_progreso);
    $stmt_progreso->execute([$correo_usuario, $id_curso]);

    // 💡 FIN DE LA TRANSACCIÓN: Confirma que todo fue exitoso.
    $pdo->commit();

    // Éxito
    $mensaje = "Te has desinscrito del curso \"{$nombre_curso}\" correctamente. Tu progreso fue reiniciado.";
    $_SESSION['mensaje'] = $mensaje;
    header("Location: cursos.php");
    exit;

} catch (PDOException $e) {
    // Si algo falla, revierte todos los cambios.
    $pdo->rollBack();
    error_log("Error al desinscribirse y borrar progreso: " . $e->getMessage());
    $_SESSION['error'] = "❌ Error interno al procesar la desinscripción. Intenta de nuevo.";
    header("Location: curso_detalle.php?id=" . $id_curso);
    exit;
}
?>