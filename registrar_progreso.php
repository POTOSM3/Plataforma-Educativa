<?php
session_start();
require 'conexion.php';

$usuario_correo = $_SESSION['usuario']['correo'] ?? null;
$leccion_id = $_GET['leccion_id'] ?? null;
$curso_id = $_GET['curso_id'] ?? null; // compatibilidad con el formato viejo
$tipo = $_GET['tipo'] ?? null; // 'pdf' o 'video'
$url_destino = $_GET['url_destino'] ?? null;

if ($usuario_correo && $tipo && $url_destino && ($leccion_id || $curso_id)) {
    try {
        $campo = ($tipo === 'pdf') ? 'visto_pdf' : 'visto_video';

        if ($leccion_id) {
            // Registrar progreso a nivel de LECCIÓN individual
            $sql = "
                INSERT INTO progreso_leccion (usuario_correo, leccion_id, {$campo})
                VALUES (?, ?, TRUE)
                ON DUPLICATE KEY UPDATE {$campo} = TRUE
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$usuario_correo, $leccion_id]);
        } else {
            // Formato antiguo: progreso a nivel de curso completo
            $sql = "
                INSERT INTO progreso (usuario_correo, curso_id, {$campo})
                VALUES (?, ?, TRUE)
                ON DUPLICATE KEY UPDATE {$campo} = TRUE
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$usuario_correo, $curso_id]);
        }

    } catch (PDOException $e) {
        error_log("Error al registrar progreso: " . $e->getMessage());
    }

    // Redirigir al material
    header("Location: " . $url_destino);
    exit();
}

// Si falta información, redirigir a cursos
header("Location: cursos.php");
exit();
?>
