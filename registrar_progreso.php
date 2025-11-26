<?php
session_start();
require 'conexion.php';

$usuario_correo = $_SESSION['usuario']['correo'] ?? null;
$curso_id = $_GET['curso_id'] ?? null;
$tipo = $_GET['tipo'] ?? null; // 'pdf' o 'video'
$url_destino = $_GET['url_destino'] ?? null;

if ($usuario_correo && $curso_id && $tipo && $url_destino) {
    try {
        $campo = ($tipo === 'pdf') ? 'visto_pdf' : 'visto_video';
        
        // 1. Insertar o Actualizar el progreso
        // Usamos ON DUPLICATE KEY UPDATE para manejar la inserción y actualización en un solo query
        $sql = "
            INSERT INTO progreso (usuario_correo, curso_id, {$campo}) 
            VALUES (?, ?, TRUE)
            ON DUPLICATE KEY UPDATE {$campo} = TRUE
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$usuario_correo, $curso_id]);

    } catch (PDOException $e) {
        error_log("Error al registrar progreso: " . $e->getMessage());
    }

    // 2. Redirigir al material
    header("Location: " . $url_destino);
    exit();
}

// Si falta información, redirigir al detalle del curso
header("Location: curso_detalle.php?id=" . urlencode($curso_id ?? ''));
exit();
?>