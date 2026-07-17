<?php
session_start();
require 'conexion.php';
require __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Redirigir si no está logueado O no es administrador (Protección de Acceso)
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'administrador') {
    header("Location: index.php"); 
    exit();
}

include 'components/layout.php'; // Asegúrate de que layout.php exista
$admin_data = $_SESSION['usuario']; 
$mensajes_pendientes_count = 0;
try {
    $mensajes_pendientes_count = $pdo->query("SELECT COUNT(*) FROM contactos WHERE leido = 0")->fetchColumn();
} catch (PDOException $e) {
    // Si la columna 'leido' aún no existe (falta correr la migración), no rompe el panel
    $mensajes_pendientes_count = 0;
}
$section = $_GET['section'] ?? 'dashboard';
$curso_id_actual = $_GET['course_id'] ?? null;

$mensaje = $_GET['message'] ?? ''; // Mantener la lectura del mensaje
$error = '';

// Variables para la edición
$curso_to_edit = null;
$edit_mode = false;
// 🎯 NUEVAS VARIABLES DE EDICIÓN DE LECCIÓN 🎯
$leccion_to_edit = null;
$edit_lesson_mode = false;


// ------------------------------------------------------------------
// LÓGICA DE OBTENCIÓN DE DATOS (GET)
// ------------------------------------------------------------------

// --- Cargar datos de un curso para editar ---
if ($section == 'content' && isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $curso_id = $_GET['id'];
    try {
        $stmt = $pdo->prepare("SELECT id, titulo, descripcion, categoria, imagen FROM cursos WHERE id = ?");
        $stmt->execute([$curso_id]);
        $curso_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($curso_to_edit) {
            $edit_mode = true;
        } else {
            $error = 'Error: Curso no encontrado.';
        }
    } catch (PDOException $e) {
        $error = "Error al cargar datos del curso: " . $e->getMessage();
    }
}

// 🎯 LÓGICA AGREGADA: Cargar datos de una lección para editar 🎯
if ($section == 'lessons' && isset($_GET['action']) && $_GET['action'] == 'edit_lesson' && isset($_GET['id'])) {
    $leccion_id = $_GET['id'];
    try {
        $stmt = $pdo->prepare("SELECT id, curso_id, titulo, orden, url_video, ruta_pdf, quiz_id FROM lecciones WHERE id = ?");
        $stmt->execute([$leccion_id]);
        $leccion_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($leccion_to_edit) {
            $edit_lesson_mode = true;
            $curso_id_actual = $leccion_to_edit['curso_id']; // Asegura que el ID del curso sea el correcto
        } else {
            $error = 'Error: Lección no encontrada.';
        }
    } catch (PDOException $e) {
        $error = "Error al cargar datos de la lección: " . $e->getMessage();
    }
}

// ------------------------------------------------------------------
// LÓGICA DE PROCESAMIENTO (POST)
// ------------------------------------------------------------------

// --- 1. Generar Nuevo Código de Seguridad (Gestión de Usuarios) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'generate_code') {
    $user_id_to_reset = $_POST['user_id'] ?? null;
    if ($user_id_to_reset) {
        // Genera un código de 8 caracteres alfanuméricos
        $new_code = bin2hex(random_bytes(4)); 
        try {
            $stmt = $pdo->prepare("UPDATE usuarios SET codigo_seguridad = ? WHERE id = ?");
            $stmt->execute([$new_code, $user_id_to_reset]);
            $stmt_user = $pdo->prepare("SELECT correo FROM usuarios WHERE id = ?");
            $stmt_user->execute([$user_id_to_reset]);
            $target_email = $stmt_user->fetchColumn();
            $mensaje = "🔑 Código generado para {$target_email}. El código es: <strong>{$new_code}</strong>. Debe ser enviado al usuario para que lo use una sola vez en el flujo de 'Olvidé mi contraseña'.";
        } catch (PDOException $e) { $error = 'Error de base de datos al generar código.'; }
    } else { $error = 'ID de usuario no proporcionado.'; }
}

// --- 2. Cambiar Rol de Usuario (Gestión de Usuarios) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'change_role') {
    $user_id_to_change = $_POST['user_id'] ?? null;
    $current_role = $_POST['current_role'] ?? 'usuario';
    $new_role = ($current_role === 'administrador') ? 'usuario' : 'administrador';

    if ($user_id_to_change && $user_id_to_change != $admin_data['id']) {
        try {
            $stmt = $pdo->prepare("UPDATE usuarios SET rol = ? WHERE id = ?");
            $stmt->execute([$new_role, $user_id_to_change]);
            $stmt_user = $pdo->prepare("SELECT correo FROM usuarios WHERE id = ?");
            $stmt_user->execute([$user_id_to_change]);
            $target_email = $stmt_user->fetchColumn();
            $mensaje = "El rol del usuario {$target_email} ha sido cambiado a <strong>{$new_role}</strong>.";
        } catch (PDOException $e) { $error = 'Error al cambiar el rol.'; }
    } elseif ($user_id_to_change == $admin_data['id']) {
        $error = '¡No puedes cambiar tu propio rol!';
    }
}

// --- 2b. Editar Usuario (nombre / correo) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit_user_submit') {
    $user_id_edit = $_POST['user_id'] ?? null;
    $nombre_edit = trim($_POST['nombre'] ?? '');
    $correo_edit = trim($_POST['correo'] ?? '');

    if (empty($user_id_edit) || empty($nombre_edit) || empty($correo_edit)) {
        $error = 'El nombre y el correo son obligatorios.';
    } elseif (!filter_var($correo_edit, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo ingresado no es válido.';
    } else {
        try {
            // Evitar correos duplicados con otro usuario
            $stmt_check = $pdo->prepare("SELECT id FROM usuarios WHERE correo = ? AND id != ?");
            $stmt_check->execute([$correo_edit, $user_id_edit]);
            if ($stmt_check->fetch()) {
                $error = 'Ese correo ya está en uso por otro usuario.';
            } else {
                $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, correo = ? WHERE id = ?");
                $stmt->execute([$nombre_edit, $correo_edit, $user_id_edit]);
                $mensaje = "¡Usuario '{$nombre_edit}' actualizado exitosamente!";
                header("Location: admin_panel.php?section=users&message=" . urlencode($mensaje));
                exit();
            }
        } catch (PDOException $e) {
            $error = 'Error al actualizar el usuario: ' . $e->getMessage();
        }
    }
}

// --- 2c. Eliminar Usuario ---
elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete_user') {
    $user_id_delete = $_POST['user_id'] ?? null;

    if (!$user_id_delete) {
        $error = 'ID de usuario no proporcionado.';
    } elseif ($user_id_delete == $admin_data['id']) {
        $error = '¡No puedes eliminar tu propia cuenta!';
    } else {
        try {
            $pdo->beginTransaction();

            $stmt_email = $pdo->prepare("SELECT correo FROM usuarios WHERE id = ?");
            $stmt_email->execute([$user_id_delete]);
            $correo_borrar = $stmt_email->fetchColumn();

            if ($correo_borrar) {
                // Limpiar todo lo relacionado con este usuario antes de borrarlo
                $pdo->prepare("DELETE FROM progreso_leccion WHERE usuario_correo = ?")->execute([$correo_borrar]);
                $pdo->prepare("DELETE FROM progreso WHERE usuario_correo = ?")->execute([$correo_borrar]);
                $pdo->prepare("DELETE FROM resultados_quiz WHERE usuario_correo = ?")->execute([$correo_borrar]);
                $pdo->prepare("DELETE FROM inscripciones WHERE usuario_correo = ?")->execute([$correo_borrar]);
                $pdo->prepare("DELETE FROM registros_ingresos WHERE correo_usuario = ?")->execute([$correo_borrar]);
            }

            $stmt_del = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt_del->execute([$user_id_delete]);

            $pdo->commit();
            $mensaje = "El usuario **{$correo_borrar}** y todos sus datos (inscripciones, progreso, resultados) han sido eliminados.";
            header("Location: admin_panel.php?section=users&message=" . urlencode($mensaje));
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Error al eliminar el usuario: ' . $e->getMessage();
        }
    }
}

// --- 2d. Marcar Mensaje de Contacto como Leído ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'mark_message_read') {
    $mensaje_id = $_POST['mensaje_id'] ?? null;
    if ($mensaje_id) {
        try {
            $stmt = $pdo->prepare("UPDATE contactos SET leido = 1 WHERE id = ?");
            $stmt->execute([$mensaje_id]);
            header("Location: admin_panel.php?section=messages");
            exit();
        } catch (PDOException $e) { $error = 'Error al marcar el mensaje: ' . $e->getMessage(); }
    }
}

// --- 2e. Eliminar Mensaje de Contacto ---
elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete_message') {
    $mensaje_id = $_POST['mensaje_id'] ?? null;
    if ($mensaje_id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM contactos WHERE id = ?");
            $stmt->execute([$mensaje_id]);
            $mensaje = "Mensaje eliminado con éxito.";
            header("Location: admin_panel.php?section=messages&message=" . urlencode($mensaje));
            exit();
        } catch (PDOException $e) { $error = 'Error al eliminar el mensaje: ' . $e->getMessage(); }
    }
}

// --- 2f. Responder Mensaje de Contacto (con diseño, vía PHPMailer) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'send_message_reply') {
    $mensaje_id = $_POST['mensaje_id'] ?? null;
    $correo_destino = $_POST['correo_destino'] ?? '';
    $nombre_destino = $_POST['nombre_destino'] ?? '';
    $respuesta_texto = trim($_POST['respuesta_texto'] ?? '');

    if (empty($mensaje_id) || empty($correo_destino) || empty($respuesta_texto)) {
        $error = 'La respuesta no puede estar vacía.';
    } else {
        $MAIL_HOST = 'smtp.gmail.com';
        $MAIL_USERNAME = 'potosmeedward619@gmail.com';
        $MAIL_PASSWORD = 'ikej mouq uzxv zlda';
        $MAIL_PORT = 587;
        $MAIL_SECURE = PHPMailer::ENCRYPTION_STARTTLS;

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $MAIL_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = $MAIL_USERNAME;
            $mail->Password = $MAIL_PASSWORD;
            $mail->SMTPSecure = $MAIL_SECURE;
            $mail->Port = $MAIL_PORT;
            $mail->CharSet = 'UTF-8';

            $mail->setFrom($MAIL_USERNAME, 'Soporte EduLive');
            $mail->addAddress($correo_destino, $nombre_destino);

            $mail->isHTML(true);
            $mail->Subject = 'Respuesta a tu mensaje - EduLive';

            $respuesta_html = nl2br(htmlspecialchars($respuesta_texto));
            $mail->Body = "
                <div style='font-family: Poppins, Arial, sans-serif; max-width: 500px; margin: 0 auto;'>
                    <div style='background: linear-gradient(120deg, #6C63FF, #00D4FF); padding: 25px; border-radius: 12px 12px 0 0; text-align: center;'>
                        <h1 style='color: #fff; margin: 0; font-size: 1.6rem;'>🎓 EduLive</h1>
                        <p style='color: #fff; margin: 5px 0 0; opacity: 0.9;'>Respuesta a tu mensaje de contacto</p>
                    </div>
                    <div style='background: #141a29; padding: 25px; border-radius: 0 0 12px 12px; color: #f0f0f0;'>
                        <p>Hola <strong>{$nombre_destino}</strong>,</p>
                        <p>Gracias por escribirnos. Aquí tienes nuestra respuesta:</p>
                        <div style='background: rgba(255,255,255,0.08); border-left: 4px solid #FFD166; padding: 15px; border-radius: 8px; margin: 15px 0;'>
                            {$respuesta_html}
                        </div>
                        <p style='opacity: 0.7; font-size: 0.85rem; margin-top: 25px;'>Este correo fue enviado desde el panel de soporte de EduLive.</p>
                    </div>
                </div>
            ";
            $mail->AltBody = $respuesta_texto;

            $mail->send();

            // Marcar como leído automáticamente al responder
            $stmt = $pdo->prepare("UPDATE contactos SET leido = 1 WHERE id = ?");
            $stmt->execute([$mensaje_id]);

            $mensaje = "¡Respuesta enviada con éxito a {$nombre_destino}!";
            header("Location: admin_panel.php?section=messages&message=" . urlencode($mensaje));
            exit();
        } catch (Exception $e) {
            $error = "No se pudo enviar la respuesta. Detalles: " . $mail->ErrorInfo;
        }
    }
}

// --- 3. Crear Nuevo Curso (Gestión de Contenido) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'create_course') {
    $titulo = $_POST['titulo'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $categoria = $_POST['categoria'] ?? '';
    $imagen = trim($_POST['imagen'] ?? '');
    $admin_id = $_SESSION['usuario']['id'];
    $creador_id = $admin_data['id'];

    if (empty($titulo) || empty($descripcion) || empty($categoria)) {
        $error = 'Todos los campos de contenido son obligatorios.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO cursos (titulo, descripcion, categoria, imagen, creado_por) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$titulo, $descripcion, $categoria, $imagen, $admin_id])) {
                $mensaje = "¡Curso **'{$titulo}'** creado con éxito!";
            } else { $error = 'Error al insertar el curso en la base de datos.'; }
        } catch (PDOException $e) { $error = 'Error de base de datos al crear el curso: ' . $e->getMessage(); }
    }
}
// --- 3. Editar Curso (Gestión de Contenido) ---
elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit_course_submit') {
    $curso_id = $_POST['curso_id'] ?? null;
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $imagen = trim($_POST['imagen'] ?? '');

    if (empty($curso_id) || empty($titulo) || empty($descripcion) || empty($categoria)) {
        $error = 'Todos los campos son obligatorios para editar el curso.';
    } else {
        try {
            // Actualizar el curso en la base de datos
         $stmt = $pdo->prepare("UPDATE cursos SET titulo = ?, descripcion = ?, categoria = ?, imagen = ? WHERE id = ?"); 
            $stmt->execute([$titulo, $descripcion, $categoria, $imagen, $curso_id]); 
            $mensaje = "¡El curso '{$titulo}' ha sido actualizado exitosamente!";
            header("Location: admin_panel.php?section=content&message=" . urlencode($mensaje)); 
            exit();
        } catch (PDOException $e) {
            $error = "Error al actualizar el curso: " . $e->getMessage();
        }
    }
}

// --- 4. Crear Nueva Lección (Gestión de Lecciones) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'create_lesson') {
    $lesson_course_id = $_POST['course_id'] ?? null;
    $titulo_leccion = $_POST['titulo_leccion'] ?? '';
    $url_video = $_POST['url_video'] ?? '';
    $ruta_pdf = $_POST['ruta_pdf'] ?? '';
    
    if (empty($titulo_leccion) || empty($lesson_course_id)) {
        $error = 'El título de la lección es obligatorio.';
    } elseif (empty($url_video) && empty($ruta_pdf)) {
        $error = 'Debes proporcionar una URL de video o una ruta de archivo PDF.';
    } else {
        try {
            // Determina el siguiente número de orden
            $stmt_orden = $pdo->prepare("SELECT MAX(orden) FROM lecciones WHERE curso_id = ?");
            $stmt_orden->execute([$lesson_course_id]);
            $siguiente_orden = $stmt_orden->fetchColumn() + 1;
            
            // La columna quiz_id debe permitir NULL, lo cual se omite aquí y es el valor por defecto
            $stmt = $pdo->prepare("INSERT INTO lecciones (curso_id, titulo, url_video, ruta_pdf, orden) VALUES (?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$lesson_course_id, $titulo_leccion, $url_video, $ruta_pdf, $siguiente_orden])) {
                $mensaje = "¡Lección **'{$titulo_leccion}'** agregada con éxito!";
            } else { $error = 'Error al insertar la lección en la base de datos.'; }
        } catch (PDOException $e) { $error = 'Error de base de datos al crear la lección: ' . $e->getMessage(); }
    }
}

// --- 5. Editar Lección (edit_lesson_submit) ---
elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit_lesson_submit') {
    $leccion_id = $_POST['leccion_id'] ?? null;
    $curso_id = $_POST['course_id'] ?? null; 

    $titulo = trim($_POST['titulo_leccion'] ?? $_POST['titulo_leccion_hidden'] ?? ''); 
    $orden = $_POST['orden'] ?? $_POST['orden_hidden'] ?? 1;
    $url_video = trim($_POST['url_video'] ?? $_POST['url_video_hidden'] ?? null);
    $ruta_pdf = trim($_POST['ruta_pdf'] ?? $_POST['ruta_pdf_hidden'] ?? null);
    
    $quiz_asignado_id = $_POST['quiz_asignado_id'] ?? null; 
    $quiz_id = !empty($quiz_asignado_id) ? (int)$quiz_asignado_id : null; 

    if (empty($leccion_id) || empty($curso_id) || empty($titulo)) {
        $error = 'El título, ID de la lección y del curso son obligatorios.'; 
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE lecciones SET titulo = ?, orden = ?, url_video = ?, ruta_pdf = ?, quiz_id = ? WHERE id = ? AND curso_id = ?");
            
            if ($stmt->execute([$titulo, $orden, $url_video, $ruta_pdf, $quiz_id, $leccion_id, $curso_id])) {
                $mensaje = "¡Lección '{$titulo}' actualizada exitosamente!";
                header("Location: admin_panel.php?section=lessons&course_id={$curso_id}&message=" . urlencode($mensaje)); 
                exit();
            } else {
                $error = "Error al actualizar la lección.";
            }
        } catch (PDOException $e) {
            $error = "Error al actualizar la lección: " . $e->getMessage();
        }
    }
}

// --- 6. Eliminar Curso (Gestión de Contenido) ---
elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete_course') {
    $curso_id = $_POST['curso_id'] ?? null;
    
    if ($curso_id) {
        try {
            $pdo->beginTransaction();
            
            // 1. Obtener el título antes de borrarlo (para el mensaje de éxito)
            $stmt_title = $pdo->prepare("SELECT titulo FROM cursos WHERE id = ?");
            $stmt_title->execute([$curso_id]);
            $course_title = $stmt_title->fetchColumn() ?: 'Curso Desconocido';

            // 2. Eliminar progreso y resultados de quiz asociados al curso
            $stmt_progreso = $pdo->prepare("DELETE FROM progreso WHERE curso_id = ?");
            $stmt_progreso->execute([$curso_id]);
            
            // 3. Eliminar resultados de quiz específicos
            $stmt_quiz_results = $pdo->prepare("DELETE FROM resultados_quiz WHERE curso_id = ?");
            $stmt_quiz_results->execute([$curso_id]);

            // 4. Eliminar todas las lecciones del curso
            $stmt_lessons = $pdo->prepare("DELETE FROM lecciones WHERE curso_id = ?");
            $stmt_lessons->execute([$curso_id]);

            // 5. Eliminar el curso
            $stmt_course = $pdo->prepare("DELETE FROM cursos WHERE id = ?");
            $stmt_course->execute([$curso_id]);
            
            $pdo->commit();
            $mensaje = "El curso **'{$course_title}'** y todo su contenido relacionado (lecciones, progreso, resultados de quiz) han sido **eliminados** con éxito.";
            // Redirigir para limpiar el POST y mostrar el mensaje en la vista de contenido
            header("Location: admin_panel.php?section=content&message=" . urlencode($mensaje));
            exit();

        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error al intentar eliminar el curso: " . $e->getMessage();
        }
    } else {
        $error = 'ID de curso no proporcionado para la eliminación.';
    }
}

// --- 7. Eliminar Lección (Gestión de Lecciones) ---
elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete_lesson') {
    $leccion_id = $_POST['leccion_id'] ?? null;
    $curso_id = $_POST['course_id'] ?? null;
    
    if ($leccion_id && $curso_id) {
        try {
            // 1. Obtener el título antes de borrarlo
            $stmt_title = $pdo->prepare("SELECT titulo FROM lecciones WHERE id = ?");
            $stmt_title->execute([$leccion_id]);
            $lesson_title = $stmt_title->fetchColumn() ?: 'Lección Desconocida';

            // 2. Eliminar la lección
            $stmt_lesson = $pdo->prepare("DELETE FROM lecciones WHERE id = ?");
            $stmt_lesson->execute([$leccion_id]);
            
            // Nota: La reordenación de lecciones puede ser implementada después si es necesaria.

            $mensaje = "La lección **'{$lesson_title}'** ha sido **eliminada** con éxito.";
            // Redirigir para limpiar el POST y mostrar el mensaje en la vista de lecciones
            header("Location: admin_panel.php?section=lessons&course_id={$curso_id}&message=" . urlencode($mensaje));
            exit();

        } catch (PDOException $e) {
            $error = "Error al intentar eliminar la lección: " . $e->getMessage();
        }
    } else {
        $error = 'ID de lección o curso no proporcionado para la eliminación.';
    }
}


// --- 8. Crear Nueva Pregunta (Gestión de Preguntas de Quiz) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'create_question') {
    $quiz_id = $_POST['quiz_id'] ?? null;
    $curso_id_pregunta = $_POST['curso_id'] ?? null;
    $pregunta = trim($_POST['pregunta'] ?? '');
    $opcion_a = trim($_POST['opcion_a'] ?? '');
    $opcion_b = trim($_POST['opcion_b'] ?? '');
    $opcion_c = trim($_POST['opcion_c'] ?? '');
    $opcion_d = trim($_POST['opcion_d'] ?? '');
    $respuesta_correcta = $_POST['respuesta_correcta'] ?? null;

    if (empty($quiz_id) || empty($curso_id_pregunta) || empty($pregunta) || empty($opcion_a) || empty($opcion_b) || empty($opcion_c) || empty($opcion_d) || $respuesta_correcta === null) {
        $error = 'Todos los campos de la pregunta son obligatorios, incluida la respuesta correcta.';
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Insertar la pregunta
            $stmt = $pdo->prepare("INSERT INTO preguntas_quiz (curso_id, pregunta, opcion_a, opcion_b, opcion_c, opcion_d, respuesta_correcta) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$curso_id_pregunta, $pregunta, $opcion_a, $opcion_b, $opcion_c, $opcion_d, $respuesta_correcta]);
            $nueva_pregunta_id = $pdo->lastInsertId();

            // 2. Determinar el siguiente orden dentro de este quiz
            $stmt_orden = $pdo->prepare("SELECT MAX(orden) FROM leccion_quiz WHERE quiz_id = ?");
            $stmt_orden->execute([$quiz_id]);
            $siguiente_orden = ($stmt_orden->fetchColumn() ?: 0) + 1;

            // 3. Conectar la pregunta con el quiz
            $stmt2 = $pdo->prepare("INSERT INTO leccion_quiz (quiz_id, pregunta_id, orden) VALUES (?, ?, ?)");
            $stmt2->execute([$quiz_id, $nueva_pregunta_id, $siguiente_orden]);

            $pdo->commit();
            $mensaje = "¡Pregunta agregada con éxito al quiz!";
            header("Location: admin_panel.php?section=questions&quiz_id={$quiz_id}&message=" . urlencode($mensaje));
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Error al guardar la pregunta: ' . $e->getMessage();
        }
    }
}

// --- 9. Editar Pregunta (Gestión de Preguntas de Quiz) ---
elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit_question_submit') {
    $pregunta_id = $_POST['pregunta_id'] ?? null;
    $quiz_id = $_POST['quiz_id'] ?? null;
    $pregunta = trim($_POST['pregunta'] ?? '');
    $opcion_a = trim($_POST['opcion_a'] ?? '');
    $opcion_b = trim($_POST['opcion_b'] ?? '');
    $opcion_c = trim($_POST['opcion_c'] ?? '');
    $opcion_d = trim($_POST['opcion_d'] ?? '');
    $respuesta_correcta = $_POST['respuesta_correcta'] ?? null;

    if (empty($pregunta_id) || empty($quiz_id) || empty($pregunta) || $respuesta_correcta === null) {
        $error = 'Todos los campos de la pregunta son obligatorios.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE preguntas_quiz SET pregunta = ?, opcion_a = ?, opcion_b = ?, opcion_c = ?, opcion_d = ?, respuesta_correcta = ? WHERE id = ?");
            $stmt->execute([$pregunta, $opcion_a, $opcion_b, $opcion_c, $opcion_d, $respuesta_correcta, $pregunta_id]);
            $mensaje = "¡Pregunta actualizada con éxito!";
            header("Location: admin_panel.php?section=questions&quiz_id={$quiz_id}&message=" . urlencode($mensaje));
            exit();
        } catch (PDOException $e) {
            $error = 'Error al actualizar la pregunta: ' . $e->getMessage();
        }
    }
}

// --- 10. Eliminar Pregunta (Gestión de Preguntas de Quiz) ---
elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete_question') {
    $pregunta_id = $_POST['pregunta_id'] ?? null;
    $quiz_id = $_POST['quiz_id'] ?? null;

    if ($pregunta_id && $quiz_id) {
        try {
            $pdo->beginTransaction();
            $stmt1 = $pdo->prepare("DELETE FROM leccion_quiz WHERE pregunta_id = ?");
            $stmt1->execute([$pregunta_id]);
            $stmt2 = $pdo->prepare("DELETE FROM preguntas_quiz WHERE id = ?");
            $stmt2->execute([$pregunta_id]);
            $pdo->commit();
            $mensaje = "Pregunta eliminada con éxito.";
            header("Location: admin_panel.php?section=questions&quiz_id={$quiz_id}&message=" . urlencode($mensaje));
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Error al eliminar la pregunta: ' . $e->getMessage();
        }
    } else {
        $error = 'Faltan datos para eliminar la pregunta.';
    }
}

// ------------------------------------------------------------------
// CONSULTAS PARA MOSTRAR LA VISTA (GET)
// ------------------------------------------------------------------
$users = [];
$courses = [];
$curso_data = null;
$lecciones = [];
$popular_courses = [];
$completed_users_count = 0;
$avg_score = 0;
$max_inscritos = 0;
$total_inscripciones = 0;
$tasa_finalizacion = 0;
$ultimos_ingresos = [];
$promedio_por_materia = [];
$mensajes_contacto = [];
$usuario_to_edit = null;
$edit_user_mode = false;
$usuario_detalle = null;
$progreso_detalle = [];
$resultados_detalle = [];

if ($section == 'users') {
    try {
        $stmt_users = $pdo->query("SELECT id, nombre, correo, rol, codigo_seguridad, fecha_registro FROM usuarios ORDER BY rol DESC, nombre ASC");
        $users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { $error = 'Error al cargar la lista de usuarios.'; }

    // Cargar datos de un usuario para editar
    if (isset($_GET['action']) && $_GET['action'] == 'edit_user' && isset($_GET['id'])) {
        try {
            $stmt = $pdo->prepare("SELECT id, nombre, correo FROM usuarios WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $usuario_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($usuario_to_edit) { $edit_user_mode = true; }
        } catch (PDOException $e) { $error = 'Error al cargar el usuario: ' . $e->getMessage(); }
    }

} elseif ($section == 'user_detail' && isset($_GET['user_id'])) {
    try {
        $uid = $_GET['user_id'];
        $stmt_u = $pdo->prepare("SELECT id, nombre, correo, rol, fecha_registro FROM usuarios WHERE id = ?");
        $stmt_u->execute([$uid]);
        $usuario_detalle = $stmt_u->fetch(PDO::FETCH_ASSOC);

        if ($usuario_detalle) {
            $stmt_prog = $pdo->prepare("
                SELECT c.titulo, p.visto_pdf, p.visto_video, p.quiz_completado, p.certificado_emitido
                FROM progreso p
                JOIN cursos c ON p.curso_id = c.id
                WHERE p.usuario_correo = ?
                ORDER BY c.titulo ASC
            ");
            $stmt_prog->execute([$usuario_detalle['correo']]);
            $progreso_detalle = $stmt_prog->fetchAll(PDO::FETCH_ASSOC);

            $stmt_res = $pdo->prepare("
                SELECT c.titulo, r.aciertos, r.total_preguntas, r.porcentaje, r.nivel_logro, r.fecha_resultado
                FROM resultados_quiz r
                JOIN cursos c ON r.curso_id = c.id
                WHERE r.usuario_correo = ?
                ORDER BY r.fecha_resultado DESC
            ");
            $stmt_res->execute([$usuario_detalle['correo']]);
            $resultados_detalle = $stmt_res->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $error = 'Usuario no encontrado.';
        }
    } catch (PDOException $e) { $error = 'Error al cargar el detalle del usuario: ' . $e->getMessage(); }

} elseif ($section == 'messages') {
    try {
        $stmt_msgs = $pdo->query("SELECT id, nombre, correo, mensaje, fecha, leido FROM contactos ORDER BY leido ASC, fecha DESC");
        $mensajes_contacto = $stmt_msgs->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { $error = 'Error al cargar los mensajes: ' . $e->getMessage(); }

} elseif ($section == 'content') {
    try {
        $stmt_courses = $pdo->query("SELECT id, titulo, categoria, fecha_creacion FROM cursos ORDER BY fecha_creacion DESC");
        $courses = $stmt_courses->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { $error = 'Error al cargar la lista de cursos: ' . $e->getMessage(); }
    
} elseif ($section == 'lessons' && $curso_id_actual) {
    try {
        // Cargar la información del curso
        $stmt_curso = $pdo->prepare("SELECT titulo FROM cursos WHERE id = ?");
        $stmt_curso->execute([$curso_id_actual]);
        $curso_data = $stmt_curso->fetch(PDO::FETCH_ASSOC);

        // Cargar las lecciones para ese curso
        $stmt_lecciones = $pdo->prepare("SELECT id, titulo, url_video, ruta_pdf, orden, quiz_id FROM lecciones WHERE curso_id = ? ORDER BY orden ASC");
        $stmt_lecciones->execute([$curso_id_actual]);
        $lecciones = $stmt_lecciones->fetchAll(PDO::FETCH_ASSOC);
        
        if (!$curso_data) { $error = "El curso con ID {$curso_id_actual} no fue encontrado."; $lecciones = []; }

    } catch (PDOException $e) { $error = 'Error al cargar la gestión de lecciones.'; }
    
} elseif ($section == 'reports') {
    try {
        // Reporte 1: Cursos más populares (por número de inscripciones reales)
        $stmt_popular_courses = $pdo->query("
            SELECT c.titulo, COUNT(DISTINCT i.usuario_correo) AS inscritos 
            FROM cursos c
            LEFT JOIN inscripciones i ON c.id = i.curso_id
            GROUP BY c.id, c.titulo
            ORDER BY inscritos DESC
            LIMIT 5
        ");
        $popular_courses = $stmt_popular_courses->fetchAll(PDO::FETCH_ASSOC);
        $max_inscritos = 0;
        foreach ($popular_courses as $pc) { $max_inscritos = max($max_inscritos, $pc['inscritos']); }

        // Reporte 2: Progreso general de usuarios (Usuarios que han completado al menos 1 curso)
        $stmt_completed_users = $pdo->query("
            SELECT COUNT(DISTINCT usuario_correo) AS completed_users
            FROM progreso 
            WHERE certificado_emitido = 1
        ");
        $completed_users_count = $stmt_completed_users->fetchColumn();
        
        // Reporte 3: Tasa de Aprobación Global del Quiz (promedio de porcentaje de aciertos)
        $stmt_avg_score = $pdo->query("
            SELECT AVG(porcentaje) 
            FROM resultados_quiz
        ");
        $avg_score = $stmt_avg_score->fetchColumn();

        // Reporte 4: Total de inscripciones y tasa de finalización general
        $stmt_total_insc = $pdo->query("SELECT COUNT(*) FROM inscripciones");
        $total_inscripciones = $stmt_total_insc->fetchColumn();
        $tasa_finalizacion = $total_inscripciones > 0 ? round(($completed_users_count / $total_inscripciones) * 100, 1) : 0;

        // Reporte 5: Últimos inicios de sesión
        $stmt_ingresos = $pdo->query("
            SELECT u.nombre, ri.correo_usuario, ri.fecha_ingreso
            FROM registros_ingresos ri
            JOIN usuarios u ON u.correo = ri.correo_usuario
            ORDER BY ri.fecha_ingreso DESC
            LIMIT 5
        ");
        $ultimos_ingresos = $stmt_ingresos->fetchAll(PDO::FETCH_ASSOC);

        // Reporte 6: Promedio de quiz por materia
        $stmt_promedio_materia = $pdo->query("
            SELECT c.titulo, AVG(r.porcentaje) AS promedio, COUNT(r.id) AS total_intentos
            FROM resultados_quiz r
            JOIN cursos c ON r.curso_id = c.id
            GROUP BY c.id, c.titulo
            ORDER BY promedio DESC
        ");
        $promedio_por_materia = $stmt_promedio_materia->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) { 
        $error = 'Error al cargar los datos de reportes: ' . $e->getMessage(); 
    }
}

$quizzes_disponibles = [];
try {
    $stmt_quizzes = $pdo->query("SELECT id, titulo FROM quizzes ORDER BY titulo ASC");
    $quizzes_disponibles = $stmt_quizzes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Manejo de error para los quizzes
}

// --- Cargar quiz + sus preguntas (Gestión de Preguntas) ---
$quiz_actual = null;
$preguntas_quiz_actual = [];
if ($section == 'questions' && isset($_GET['quiz_id'])) {
    $quiz_id_actual = $_GET['quiz_id'];
    try {
        $stmt_quiz = $pdo->prepare("
            SELECT q.id, q.titulo, q.tipo,
                   COALESCE(q.curso_id, l.curso_id) AS curso_id,
                   c.titulo AS curso_titulo
            FROM quizzes q
            LEFT JOIN lecciones l ON l.quiz_id = q.id
            LEFT JOIN cursos c ON c.id = COALESCE(q.curso_id, l.curso_id)
            WHERE q.id = ?
        ");
        $stmt_quiz->execute([$quiz_id_actual]);
        $quiz_actual = $stmt_quiz->fetch(PDO::FETCH_ASSOC);

        if ($quiz_actual) {
            $stmt_preguntas = $pdo->prepare("
                SELECT p.id, p.pregunta, p.opcion_a, p.opcion_b, p.opcion_c, p.opcion_d, p.respuesta_correcta, lq.orden
                FROM leccion_quiz lq
                JOIN preguntas_quiz p ON lq.pregunta_id = p.id
                WHERE lq.quiz_id = ?
                ORDER BY lq.orden ASC
            ");
            $stmt_preguntas->execute([$quiz_id_actual]);
            $preguntas_quiz_actual = $stmt_preguntas->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $error = "El quiz con ID {$quiz_id_actual} no fue encontrado.";
        }
    } catch (PDOException $e) {
        $error = 'Error al cargar la gestión de preguntas: ' . $e->getMessage();
    }
}

// --- Cargar datos de una pregunta para editar ---
$pregunta_to_edit = null;
$edit_question_mode = false;
if ($section == 'questions' && isset($_GET['action']) && $_GET['action'] == 'edit_question' && isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM preguntas_quiz WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $pregunta_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($pregunta_to_edit) { $edit_question_mode = true; }
    } catch (PDOException $e) {
        $error = 'Error al cargar la pregunta: ' . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Administración — EduLive</title>
    <link rel="stylesheet" href="css/style_moderno.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        /* Estilos Generales */
        .admin-content { padding: 30px; }
        .admin-content h1 { color: var(--accent, #FFD166); }
        .admin-menu { border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 20px; }
        .admin-menu a { padding: 10px 15px; display: inline-block; text-decoration: none; color: var(--color-text); opacity: 0.7; transition: opacity 0.3s; }
        .admin-menu a.active { opacity: 1; border-bottom: 2px solid var(--accent, #FFD166); }
        
        /* Estilos para Tarjetas de Métricas */
        .admin-metrics { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .metric-card { background: rgba(255, 255, 255, 0.1); padding: 20px; border-radius: 12px; backdrop-filter: blur(10px); }
        .metric-card h3 { margin: 0; font-size: 1.2rem; opacity: 0.8; }
        .metric-card p { font-size: 2rem; font-weight: bold; color: var(--accent, #FFD166); margin-top: 5px; }

        /* Estilos de Tablas (data-table) */
        .data-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px; 
            color: var(--color-text); 
            background: rgba(255, 255, 255, 0.05); 
            border-radius: 12px; 
            overflow: hidden; 
        }
        .data-table th, .data-table td { 
            padding: 12px 15px; 
            text-align: left; 
            border-bottom: 1px solid rgba(255, 255, 255, 0.1); 
        }
        .data-table th { 
            background-color: rgba(255, 255, 255, 0.1); 
            font-weight: 700; 
            text-transform: uppercase; 
            font-size: 0.8rem; 
        }
        .data-table tr:hover { 
            background-color: rgba(255, 255, 255, 0.05); 
        }

        /* Estilos de Botones de Acción y Formularios */
        .btn-action { 
            padding: 5px 10px; 
            margin: 2px; 
            font-size: 0.8rem; 
            border-radius: 6px; 
            cursor: pointer; 
            border: none; 
            color: #141a29; 
        }
        .btn-reset { background-color: #ef476f; color: white; }
        .btn-role { background-color: #06D6A0; }
        .btn-edit { background-color: #FFD166; }
        .btn-lessons { background-color: #118ab2; color: white; }

        /* Tags de Rol y Códigos de Seguridad */
        .role-tag { padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; }
        .role-admin { background-color: var(--accent, #FFD166); color: #141a29; }
        .role-user { background-color: #118ab2; color: white; }
        .code-present { color: #06D6A0; font-weight: bold; }
        .code-null { color: #ef476f; }
        
        /* Mensajes y Forms */
        .message-box { padding: 15px; border-radius: 10px; margin-bottom: 20px; font-weight: bold; }
        .success-message { background-color: #06D6A0; color: white; }
        .error-message { background-color: #ef476f; color: white; }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%; padding: 8px; margin-bottom: 10px; border-radius: 6px; border: 1px solid #444; background: #2c2c3e; color: white;
        }
        .btn-secondary {
            background-color: transparent;
            color: white !important; 
            border: 1px solid #444;
            display: inline-block;
            text-align: center;
            padding: 8px;
            text-decoration: none;
            border-radius: 6px;
        }
    </style>
</head>
<body style="background-color: #141a29;">

<?php 
if (function_exists('renderSidebar')) {
    renderSidebar($admin_data, 'admin_panel');
}
?>

<main class="content" id="content">
    <section class="banner">
        <h1 class="title">✨ Panel de Administración</h1>
        <p class="desc">Bienvenido/a, Administrador/a <?= $admin_data['nombre'] ?>. Gestiona la plataforma EduLive.</p>
    </section>

    
    <div class="admin-content">
        <?php if ($mensaje): ?><div class="message-box success-message"><?= $mensaje ?></div><?php endif; ?>
        <?php if ($error): ?><div class="message-box error-message"><?= $error ?></div><?php endif; ?>
        <?php if ($mensajes_pendientes_count > 0 && $section != 'messages'): ?>
            <a href="?section=messages" style="text-decoration:none;">
                <div class="message-box" style="background-color:#A78BFA; color:#141a29; display:flex; align-items:center; gap:10px; cursor:pointer;">
                    <i data-lucide="bell" style="width:20px; height:20px;"></i>
                    Tienes <?= $mensajes_pendientes_count ?> mensaje<?= $mensajes_pendientes_count == 1 ? '' : 's' ?> de contacto sin revisar — click para ver
                </div>
            </a>
        <?php endif; ?>
        
        
        <?php if ($section == 'dashboard'): ?>
            <h2>📊 Dashboard</h2>
            <?php 
                // Estas variables ya están disponibles en las consultas de reports
                $total_users = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
                $total_courses = $pdo->query("SELECT COUNT(*) FROM cursos")->fetchColumn();
            ?>
            <div class="admin-metrics">
                <div class="metric-card" style="display:flex; align-items:center; gap:15px;">
                    <i data-lucide="users" style="width:32px; height:32px; color:#118ab2; flex-shrink:0;"></i>
                    <div><h3>Total Usuarios</h3><p><?= $total_users ?></p></div>
                </div>
                <div class="metric-card" style="display:flex; align-items:center; gap:15px;">
                    <i data-lucide="book-open" style="width:32px; height:32px; color:var(--accent,#FFD166); flex-shrink:0;"></i>
                    <div><h3>Total Cursos</h3><p><?= $total_courses ?></p></div>
                </div>
                <a href="?section=messages" style="text-decoration:none; color:inherit;">
                <div class="metric-card" style="display:flex; align-items:center; gap:15px; cursor:pointer;">
                    <i data-lucide="mail" style="width:32px; height:32px; color:#A78BFA; flex-shrink:0;"></i>
                    <div><h3>Mensajes Pendientes</h3><p><?= $mensajes_pendientes_count ?></p></div>
                </div>
                </a>
            </div>

        <?php elseif ($section == 'users'): ?>
            <h2>🧑‍💻 Gestión de Usuarios</h2>
            <p>Control de acceso, roles, edición, progreso y restablecimiento de contraseña forzado.</p>

            <?php if ($edit_user_mode): ?>
            <div class="card form-card" style="width: 400px; padding: 20px; margin-bottom: 20px; background: rgba(255, 255, 255, 0.1);">
                <h3 style="margin-top:0;">✏️ Editar Usuario: <?= htmlspecialchars($usuario_to_edit['nombre']) ?></h3>
                <form method="POST">
                    <input type="hidden" name="action" value="edit_user_submit">
                    <input type="hidden" name="user_id" value="<?= $usuario_to_edit['id'] ?>">
                    <div class="form-group">
                        <label for="nombre">Nombre</label>
                        <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($usuario_to_edit['nombre']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="correo">Correo</label>
                        <input type="email" id="correo" name="correo" value="<?= htmlspecialchars($usuario_to_edit['correo']) ?>" required>
                    </div>
                    <button type="submit" class="btn" style="width: 100%;">Guardar Cambios</button>
                    <a href="?section=users" class="btn-secondary" style="margin-top: 10px; display: block; text-align: center;">Cancelar</a>
                </form>
            </div>
            <?php endif; ?>

            <div class="search-box" style="max-width: 400px; margin-bottom: 15px; display:flex; align-items:center; gap:8px; background: rgba(255,255,255,0.08); padding: 8px 12px; border-radius: 8px;">
                <i data-lucide="search" style="width:18px; height:18px; opacity:0.7;"></i>
                <input type="text" id="userSearchInput" onkeyup="filterUsers()" placeholder="Buscar por nombre o correo..."
                    style="background:transparent; border:none; outline:none; color:white; width:100%;">
            </div>

            <table class="data-table" id="usersTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Rol</th>
                        <th>Registrado</th>
                        <th>Código Seguridad</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $user): ?>
                        <tr class="user-row">
                            <td><?= htmlspecialchars($user['id']) ?></td>
                            <td>
                                <span style="display:inline-flex; align-items:center; gap:8px;">
                                    <span style="width:28px; height:28px; border-radius:50%; background:var(--accent,#FFD166); color:#141a29; display:inline-flex; align-items:center; justify-content:center; font-weight:700; font-size:0.85rem; flex-shrink:0;">
                                        <?= strtoupper(substr($user['nombre'], 0, 1)) ?>
                                    </span>
                                    <?= htmlspecialchars($user['nombre']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($user['correo']) ?></td>
                            <td>
                                <span class="role-tag <?= ($user['rol'] == 'administrador' ? 'role-admin' : 'role-user') ?>">
                                    <?= htmlspecialchars($user['rol']) ?>
                                </span>
                            </td>
                            <td><?= !empty($user['fecha_registro']) ? date('d/m/Y', strtotime($user['fecha_registro'])) : '—' ?></td>
                            <td>
                                <?php if ($user['codigo_seguridad']): ?>
                                    <span class="role-tag" style="background-color:#06D6A0; color:#141a29;">CLAVE ACTIVA</span>
                                <?php else: ?>
                                    <span class="role-tag" style="background-color:#3a3a4d; color:#aaa;">VACÍO</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="?section=user_detail&user_id=<?= $user['id'] ?>" class="btn-action" style="background-color:#118ab2; color:white;" title="Ver Progreso">
                                    Detalle
                                </a>
                                <a href="?section=users&action=edit_user&id=<?= $user['id'] ?>" class="btn-action btn-edit" style="background-color:#FFD166;" title="Editar Usuario">
                                    Editar
                                </a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('¿Estás seguro de que quieres generar una nueva clave de seguridad para este usuario?');">
                                    <input type="hidden" name="action" value="generate_code">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <button type="submit" class="btn-action btn-reset" title="Restablecer Contraseña Forzado">
                                        Generar Clave
                                    </button>
                                </form>
                                
                                <?php if ($user['id'] != $admin_data['id']): ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('¿Estás seguro de que quieres cambiar el rol de este usuario a: <?= ($user['rol'] == 'administrador' ? 'usuario' : 'administrador') ?>?');">
                                        <input type="hidden" name="action" value="change_role">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <input type="hidden" name="current_role" value="<?= $user['rol'] ?>">
                                        <button type="submit" class="btn-action btn-role" title="Cambiar Rol">
                                            Cambiar a <?= ($user['rol'] == 'administrador' ? 'Usuario' : 'Admin') ?>
                                        </button>
                                    </form>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('⚠️ ATENCIÓN: ¿Eliminar a <?= htmlspecialchars($user['nombre']) ?> permanentemente? Se borrará también todo su progreso, inscripciones y resultados. Esta acción no se puede deshacer.');">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" class="btn-action btn-reset" style="background-color:#ef476f;" title="Eliminar Usuario">
                                            Eliminar
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7">No se encontraron usuarios.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <script>
                function filterUsers() {
                    const q = document.getElementById('userSearchInput').value.toLowerCase();
                    document.querySelectorAll('#usersTable .user-row').forEach(row => {
                        const nombre = row.children[1].textContent.toLowerCase();
                        const correo = row.children[2].textContent.toLowerCase();
                        row.style.display = (nombre.includes(q) || correo.includes(q)) ? '' : 'none';
                    });
                }
            </script>

        <?php elseif ($section == 'user_detail' && $usuario_detalle): ?>
            <h2>👤 Detalle de Usuario: <strong><?= htmlspecialchars($usuario_detalle['nombre']) ?></strong></h2>
            <p>
                <?= htmlspecialchars($usuario_detalle['correo']) ?> &nbsp;|&nbsp;
                Registrado: <?= date('d/m/Y', strtotime($usuario_detalle['fecha_registro'])) ?> &nbsp;|&nbsp;
                <span class="role-tag <?= ($usuario_detalle['rol'] == 'administrador' ? 'role-admin' : 'role-user') ?>"><?= htmlspecialchars($usuario_detalle['rol']) ?></span>
            </p>
            <a href="?section=users" class="btn-secondary" style="display:inline-block; margin-bottom: 20px;">← Volver a Usuarios</a>

            <h3>📚 Progreso por Curso</h3>
            <table class="data-table">
                <thead>
                    <tr><th>Curso</th><th>PDF Visto</th><th>Video Visto</th><th>Quiz Completado</th><th>Certificado</th></tr>
                </thead>
                <tbody>
                    <?php if (!empty($progreso_detalle)): ?>
                        <?php foreach ($progreso_detalle as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['titulo']) ?></td>
                            <td><?= $p['visto_pdf'] ? '✅' : '❌' ?></td>
                            <td><?= $p['visto_video'] ? '✅' : '❌' ?></td>
                            <td><?= $p['quiz_completado'] ? '✅' : '❌' ?></td>
                            <td><?= $p['certificado_emitido'] ? '🏅' : '—' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5">Este usuario no está inscrito en ningún curso todavía.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <h3 style="margin-top:30px;">📝 Historial de Resultados de Quiz</h3>
            <table class="data-table">
                <thead>
                    <tr><th>Curso</th><th>Aciertos</th><th>Total</th><th>%</th><th>Nivel</th><th>Fecha</th></tr>
                </thead>
                <tbody>
                    <?php if (!empty($resultados_detalle)): ?>
                        <?php foreach ($resultados_detalle as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['titulo']) ?></td>
                            <td><?= htmlspecialchars($r['aciertos']) ?></td>
                            <td><?= htmlspecialchars($r['total_preguntas']) ?></td>
                            <td><?= htmlspecialchars($r['porcentaje']) ?>%</td>
                            <td><?= htmlspecialchars($r['nivel_logro']) ?></td>
                            <td><?= date('d/m/Y', strtotime($r['fecha_resultado'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6">Este usuario no ha realizado ningún quiz todavía.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

        <?php elseif ($section == 'user_detail' && !$usuario_detalle): ?>
            <div class="error-message">Error: No se pudo cargar el detalle del usuario. <?= htmlspecialchars($error) ?></div>

       <?php elseif ($section == 'messages'): ?>
            <h2>✉️ Mensajes de Contacto</h2>
            <p>Mensajes enviados por los estudiantes desde el formulario de contacto.</p>
            <a href="?section=dashboard" class="btn-secondary" style="display:inline-block; margin-bottom: 20px;">← Volver al Dashboard</a>

            <?php if (!empty($mensajes_contacto)): ?>
                <?php foreach ($mensajes_contacto as $msg): ?>
                <div class="card" style="padding: 20px; margin-bottom: 15px; background: rgba(255,255,255,<?= $msg['leido'] ? '0.05' : '0.12' ?>); border-left: 4px solid <?= $msg['leido'] ? '#3a3a4d' : '#A78BFA' ?>; border-radius: 8px;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:10px;">
                        <div>
                            <strong><?= htmlspecialchars($msg['nombre']) ?></strong>
                            <span style="opacity:0.7;"> — <?= htmlspecialchars($msg['correo']) ?></span>
                            <?php if (!$msg['leido']): ?>
                                <span class="role-tag" style="background-color:#A78BFA; color:#141a29; margin-left:8px;">NUEVO</span>
                            <?php endif; ?>
                        </div>
                        <span style="opacity:0.6; font-size:0.85rem;"><?= date('d/m/Y H:i', strtotime($msg['fecha'])) ?></span>
                    </div>
                    <p style="margin: 12px 0;"><?= nl2br(htmlspecialchars($msg['mensaje'])) ?></p>
                    <div style="display:flex; gap:8px; align-items:flex-start;">
                        <?php if (!$msg['leido']): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="mark_message_read">
                            <input type="hidden" name="mensaje_id" value="<?= $msg['id'] ?>">
                            <button type="submit" class="btn-action btn-role" title="Marcar como Leído">Marcar como Leído</button>
                        </form>
                        <?php endif; ?>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar este mensaje?');">
                            <input type="hidden" name="action" value="delete_message">
                            <input type="hidden" name="mensaje_id" value="<?= $msg['id'] ?>">
                            <button type="submit" class="btn-action btn-reset" title="Eliminar Mensaje">Eliminar</button>
                        </form>
                    </div>

                    <details style="margin-top: 15px;">
                        <summary style="cursor:pointer; color:var(--accent,#FFD166); font-weight:600; list-style:none; display:inline-flex; align-items:center; gap:6px;">
                            <i data-lucide="reply" style="width:16px; height:16px;"></i> Responder
                        </summary>
                        <form method="POST" style="margin-top: 12px;">
                            <input type="hidden" name="action" value="send_message_reply">
                            <input type="hidden" name="mensaje_id" value="<?= $msg['id'] ?>">
                            <input type="hidden" name="correo_destino" value="<?= htmlspecialchars($msg['correo']) ?>">
                            <input type="hidden" name="nombre_destino" value="<?= htmlspecialchars($msg['nombre']) ?>">
                            <textarea name="respuesta_texto" rows="3" required placeholder="Escribe tu respuesta aquí..."
                                style="width:100%; padding:10px; border-radius:8px; border:1px solid #444; background:#2c2c3e; color:#fff; font-family:inherit; margin-bottom:8px;"></textarea>
                            <button type="submit" class="btn" style="padding: 8px 16px; font-size: 0.9rem;">Enviar Respuesta</button>
                        </form>
                    </details>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No hay mensajes de contacto todavía.</p>
            <?php endif; ?>

       <?php elseif ($section == 'content'): ?>
            <h2>📚 Gestión de Contenido: Cursos</h2>
            <p>Crea, edita o elimina los cursos ofrecidos en la plataforma.</p>
            
            <div style="display: flex; gap: 30px; margin-top: 20px;">
                
                <div class="card form-card" style="width: 400px; padding: 20px; flex-shrink: 0; background: rgba(255, 255, 255, 0.1);">
                    <h3><?= $edit_mode ? '✏️ Editar Curso: ' . htmlspecialchars($curso_to_edit['titulo']) : '➕ Crear Nuevo Curso' ?></h3>
                    
                    <?php if ($mensaje): ?><div class="success-message"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
                    <?php if ($error): ?><div class="error-message"><?= htmlspecialchars($error) ?></div><?php endif; ?>

                    <form method="POST" action="admin_panel.php?section=content">
                        
                        <?php if ($edit_mode): ?>
                            <input type="hidden" name="action" value="edit_course_submit">
                            <input type="hidden" name="curso_id" value="<?= htmlspecialchars($curso_to_edit['id']) ?>">
                        <?php else: ?>
                            <input type="hidden" name="action" value="create_course">
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="titulo">Título del Curso</label>
                            <input type="text" id="titulo" name="titulo" 
                                value="<?= htmlspecialchars($curso_to_edit['titulo'] ?? '') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="descripcion">Descripción</label>
                            <textarea id="descripcion" name="descripcion" rows="4" required><?= htmlspecialchars($curso_to_edit['descripcion'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="categoria">Categoría (Ej: Programación)</label>
                            <input type="text" id="categoria" name="categoria" 
                                value="<?= htmlspecialchars($curso_to_edit['categoria'] ?? '') ?>" required>
                        </div>
                        
                        <div class="form-group">
                           <label for="imagen">Icono (Ej: book-open, flask-conical)</label>
                            <input type="text" id="imagen" name="imagen" 
                                value="<?= htmlspecialchars($curso_to_edit['imagen'] ?? '') ?>">
                        </div>

                        <button type="submit" class="btn" style="width: 100%;">
                            <?= $edit_mode ? 'Guardar Cambios' : 'Crear Curso' ?>
                        </button>
                        
                        <?php if ($edit_mode): ?>
                            <a href="admin_panel.php?section=content" class="btn-secondary" style="margin-left: 10px;">Cancelar Edición</a>
                        <?php endif; ?>
                    </form>
                </div>
                <div style="flex-grow: 1;">
                    <h3 style="margin-top:0;">Lista de Cursos (<?= count($courses) ?>)</h3>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Título</th>
                                <th>Categoría</th>
                                <th>Fecha Creación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($courses)): ?>
                                <?php foreach ($courses as $course): ?>
                                <tr>
                                    <td><?= htmlspecialchars($course['id']) ?></td>
                                    <td><?= htmlspecialchars($course['titulo']) ?></td>
                                    <td><?= htmlspecialchars($course['categoria']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($course['fecha_creacion'])) ?></td>
                                    <td>
                                        <a href="admin_panel.php?section=content&action=edit&id=<?= $course['id'] ?>" class="btn-action btn-edit" title="Editar Curso" style="background-color:#FFD166;">
                                            Editar
                                        </a>
                                        <a href="?section=lessons&course_id=<?= $course['id'] ?>" class="btn-action btn-lessons" title="Gestionar Lecciones">
                                            Lecciones
                                        </a>
                                        <?php
                                            $stmt_qf = $pdo->prepare("SELECT id FROM quizzes WHERE curso_id = ? AND tipo = 'final' LIMIT 1");
                                            $stmt_qf->execute([$course['id']]);
                                            $quiz_final_id = $stmt_qf->fetchColumn();
                                        ?>
                                        <?php if ($quiz_final_id): ?>
                                        <a href="?section=questions&quiz_id=<?= $quiz_final_id ?>" class="btn-action" style="background-color:#A78BFA; color:#141a29;" title="Preguntas del Quiz Final">
                                            Quiz Final
                                        </a>
                                        <?php endif; ?>
                                        
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('ATENCIÓN: ¿Estás seguro de ELIMINAR el curso **<?= htmlspecialchars($course['titulo']) ?>**? Esto eliminará todo su contenido (lecciones, progreso, resultados de quiz, etc.).');">
                                            <input type="hidden" name="action" value="delete_course">
                                            <input type="hidden" name="curso_id" value="<?= $course['id'] ?>">
                                            <button type="submit" class="btn-action btn-reset" title="Eliminar Curso">Eliminar</button>
                                        </form>
                                        </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5">Aún no hay cursos creados.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($section == 'lessons' && $curso_data): ?>
            <h2>📚 Lecciones del Curso: **<?= htmlspecialchars($curso_data['titulo']) ?>**</h2>
            <p>Gestiona el orden y el contenido de las lecciones. 
                <a href="?section=content" style="color:var(--accent, #FFD166);">← Volver a Cursos</a></p>

            <div style="display: flex; gap: 30px; margin-top: 20px;">
                
                <div class="card form-card" style="width: 400px; padding: 20px; flex-shrink: 0; background: rgba(255, 255, 255, 0.1);">
                    <h3 style="margin-top:0;">
                        <?= $edit_lesson_mode ? '✏️ Editar Lección: ' . htmlspecialchars($leccion_to_edit['titulo']) : '➕ Agregar Nueva Lección' ?>
                     </h3>
                        <form method="POST">
                        <input type="hidden" name="course_id" value="<?= $curso_id_actual ?>">
                        <?php if ($edit_lesson_mode): ?>
                            <input type="hidden" name="action" value="edit_lesson_submit">
                            <input type="hidden" name="leccion_id" value="<?= htmlspecialchars($leccion_to_edit['id']) ?>">
                            
                            <input type="hidden" name="titulo_leccion_hidden" value="<?= htmlspecialchars($leccion_to_edit['titulo']) ?>">
                            <input type="hidden" name="orden_hidden" value="<?= htmlspecialchars($leccion_to_edit['orden']) ?>">
                            <input type="hidden" name="url_video_hidden" value="<?= htmlspecialchars($leccion_to_edit['url_video'] ?? '') ?>">
                            <input type="hidden" name="ruta_pdf_hidden" value="<?= htmlspecialchars($leccion_to_edit['ruta_pdf'] ?? '') ?>">
                            
                            <div class="form-group"><label for="orden">Orden (Número)</label>
                                <input type="number" id="orden" name="orden" min="1" 
                                    value="<?= htmlspecialchars($leccion_to_edit['orden'] ?? 1) ?>" required></div>
                                    
                        <?php else: ?>
                            <input type="hidden" name="action" value="create_lesson">
                            <?php endif; ?>

                        <input type="hidden" name="course_id" value="<?= $curso_id_actual ?>">
                        
                        <div class="form-group"><label for="titulo_leccion">Título de la Lección</label>
                            <input type="text" id="titulo_leccion" name="titulo_leccion" 
                                value="<?= htmlspecialchars($leccion_to_edit['titulo'] ?? '') ?>" required></div>
                        
                        <div class="form-group"><label for="url_video">URL del Video (Ej: YouTube)</label>
                            <input type="url" id="url_video" name="url_video"
                                value="<?= htmlspecialchars($leccion_to_edit['url_video'] ?? '') ?>"></div>
                        
                        <div class="form-group"><label for="ruta_pdf">Ruta del PDF (Ej: /uploads/leccion1.pdf)</label>
                            <input type="text" id="ruta_pdf" name="ruta_pdf"
                                value="<?= htmlspecialchars($leccion_to_edit['ruta_pdf'] ?? '') ?>"></div>

                        <div class="form-group">
                            <label for="quiz_asignado_id">Asignar Quiz</label>
                            <select id="quiz_asignado_id" name="quiz_asignado_id">
                                <option value="" selected>-- No Asignar Quiz --</option>
                                <?php 
                                    $current_quiz_id = $leccion_to_edit['quiz_id'] ?? null;
                                    foreach ($quizzes_disponibles as $quiz): 
                                ?>
                                    <option value="<?= $quiz['id'] ?>"
                                        <?= $current_quiz_id == $quiz['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($quiz['titulo']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn" style="width: 100%;">
                            <?= $edit_lesson_mode ? 'Guardar Cambios' : 'Crear Lección' ?>
                        </button>
                        
                        <?php if ($edit_lesson_mode): ?>
                            <a href="admin_panel.php?section=lessons&course_id=<?= $curso_id_actual ?>" class="btn-secondary" style="margin-top: 10px; display: block; text-align: center;">Cancelar Edición</a>
                        <?php endif; ?>
                    </form>
                </div>

                <div style="flex-grow: 1;">
                    <h3 style="margin-top:0;">Lista de Lecciones (<?= count($lecciones) ?>)</h3>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Título</th>
                                <th>Video</th>
                                <th>PDF</th>
                                <th>Quiz</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($lecciones)): ?>
                                <?php foreach ($lecciones as $leccion): ?>
                                <tr>
                                    <td><?= htmlspecialchars($leccion['orden']) ?></td>
                                    <td><?= htmlspecialchars($leccion['titulo']) ?></td>
                                    <td><?= $leccion['url_video'] ? 'Sí' : 'No' ?></td>
                                    <td><?= $leccion['ruta_pdf'] ? 'Sí' : 'No' ?></td>
                                    <td><?= $leccion['quiz_id'] ? 'Sí' : 'No' ?></td>
                                    <td>
                                        <?php if ($leccion['quiz_id']): ?>
                                        <a href="?section=questions&quiz_id=<?= $leccion['quiz_id'] ?>" 
                                           class="btn-action" style="background-color:#A78BFA; color:#141a29;" title="Gestionar Preguntas">
                                            Preguntas
                                        </a>
                                        <?php endif; ?>
                                        <a href="?section=lessons&course_id=<?= $curso_id_actual ?>&action=edit_lesson&id=<?= $leccion['id'] ?>" 
                                           class="btn-action btn-edit" style="background-color:#FFD166;" title="Editar Lección">
                                            Editar
                                        </a>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('¿Estás seguro de ELIMINAR la lección **<?= htmlspecialchars($leccion['titulo']) ?>**?');">
                                            <input type="hidden" name="action" value="delete_lesson">
                                            <input type="hidden" name="leccion_id" value="<?= $leccion['id'] ?>">
                                            <input type="hidden" name="course_id" value="<?= $curso_id_actual ?>">
                                            <button type="submit" class="btn-action btn-reset" style="background-color:#ef476f;" title="Eliminar Lección">Eliminar</button>
                                        </form>
                                        </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6">Aún no hay lecciones para este curso.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($section == 'lessons' && !$curso_data): ?>
            <div class="error-message">Error: No se puede cargar la gestión de lecciones. <?= htmlspecialchars($error) ?></div>

        <?php elseif ($section == 'questions' && $quiz_actual): ?>
            <h2>❓ Preguntas del Quiz: <strong><?= htmlspecialchars($quiz_actual['titulo']) ?></strong></h2>
            <p>
                <?= $quiz_actual['tipo'] === 'final' ? '🏁 Quiz Final' : '📖 Quiz de Lección' ?>
                — Materia: <?= htmlspecialchars($quiz_actual['curso_titulo'] ?? 'N/A') ?>
            </p>
            <?php if ($quiz_actual['tipo'] === 'leccion'): ?>
                <a href="?section=lessons&course_id=<?= $quiz_actual['curso_id'] ?>" class="btn-secondary" style="display:inline-block; margin-bottom: 20px;">← Volver a Lecciones</a>
            <?php else: ?>
                <a href="?section=content" class="btn-secondary" style="display:inline-block; margin-bottom: 20px;">← Volver a Cursos</a>
            <?php endif; ?>

            <div style="display: flex; gap: 30px; margin-top: 20px; align-items: flex-start;">

                <div class="card form-card" style="width: 450px; padding: 20px; flex-shrink: 0; background: rgba(255, 255, 255, 0.1);">
                    <h3 style="margin-top:0;">
                        <?= $edit_question_mode ? '✏️ Editar Pregunta' : '➕ Agregar Nueva Pregunta' ?>
                    </h3>
                    <form method="POST">
                        <input type="hidden" name="quiz_id" value="<?= $quiz_actual['id'] ?>">
                        <input type="hidden" name="curso_id" value="<?= $quiz_actual['curso_id'] ?>">
                        <?php if ($edit_question_mode): ?>
                            <input type="hidden" name="action" value="edit_question_submit">
                            <input type="hidden" name="pregunta_id" value="<?= $pregunta_to_edit['id'] ?>">
                        <?php else: ?>
                            <input type="hidden" name="action" value="create_question">
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="pregunta">Pregunta</label>
                            <textarea id="pregunta" name="pregunta" rows="2" required><?= htmlspecialchars($pregunta_to_edit['pregunta'] ?? '') ?></textarea>
                        </div>

                        <?php
                            $opciones_letras = ['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D'];
                            foreach ($opciones_letras as $letra => $etiqueta):
                        ?>
                        <div class="form-group">
                            <label for="opcion_<?= $letra ?>">
                                Opción <?= $etiqueta ?>
                                <input type="radio" name="respuesta_correcta" value="<?= array_search($letra, array_keys($opciones_letras)) ?>"
                                    <?= (isset($pregunta_to_edit['respuesta_correcta']) && $pregunta_to_edit['respuesta_correcta'] == array_search($letra, array_keys($opciones_letras))) ? 'checked' : '' ?>
                                    required style="width:auto; margin-left:8px;">
                                <span style="font-weight:normal; opacity:0.7;">(marcar si es la correcta)</span>
                            </label>
                            <input type="text" id="opcion_<?= $letra ?>" name="opcion_<?= $letra ?>"
                                value="<?= htmlspecialchars($pregunta_to_edit['opcion_' . $letra] ?? '') ?>" required>
                        </div>
                        <?php endforeach; ?>

                        <button type="submit" class="btn" style="width: 100%;">
                            <?= $edit_question_mode ? 'Guardar Cambios' : 'Agregar Pregunta' ?>
                        </button>

                        <?php if ($edit_question_mode): ?>
                            <a href="?section=questions&quiz_id=<?= $quiz_actual['id'] ?>" class="btn-secondary" style="margin-top: 10px; display: block; text-align: center;">Cancelar Edición</a>
                        <?php endif; ?>
                    </form>
                </div>

                <div style="flex-grow: 1;">
                    <h3 style="margin-top:0;">Preguntas Actuales (<?= count($preguntas_quiz_actual) ?>)</h3>

                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Pregunta</th>
                                <th>Respuesta Correcta</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($preguntas_quiz_actual)): ?>
                                <?php foreach ($preguntas_quiz_actual as $i => $p): ?>
                                <?php
                                    $opciones_p = [$p['opcion_a'], $p['opcion_b'], $p['opcion_c'], $p['opcion_d']];
                                    $letras_p = ['A', 'B', 'C', 'D'];
                                ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= htmlspecialchars($p['pregunta']) ?></td>
                                    <td><?= $letras_p[$p['respuesta_correcta']] ?? '?' ?>) <?= htmlspecialchars($opciones_p[$p['respuesta_correcta']] ?? '') ?></td>
                                    <td>
                                        <a href="?section=questions&quiz_id=<?= $quiz_actual['id'] ?>&action=edit_question&id=<?= $p['id'] ?>"
                                           class="btn-action btn-edit" style="background-color:#FFD166;" title="Editar Pregunta">
                                            Editar
                                        </a>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar esta pregunta?');">
                                            <input type="hidden" name="action" value="delete_question">
                                            <input type="hidden" name="pregunta_id" value="<?= $p['id'] ?>">
                                            <input type="hidden" name="quiz_id" value="<?= $quiz_actual['id'] ?>">
                                            <button type="submit" class="btn-action btn-reset" title="Eliminar Pregunta">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4">Este quiz aún no tiene preguntas.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($section == 'questions' && !$quiz_actual): ?>
            <div class="error-message">Error: No se puede cargar la gestión de preguntas. <?= htmlspecialchars($error) ?></div>

        <?php elseif ($section == 'reports'): ?>
            <h2>📊 Supervisión y Reportes</h2>
            <p>Datos en tiempo real sobre el rendimiento y el progreso de los estudiantes en la plataforma.</p>

            <?php
                $score_color = '#ef476f'; // rojo por defecto
                if ($avg_score >= 70) { $score_color = '#06D6A0'; } // verde
                elseif ($avg_score >= 50) { $score_color = '#FFD166'; } // amarillo
            ?>
            <div class="admin-metrics" style="margin-top: 30px;">
                <div class="metric-card" style="display:flex; align-items:center; gap:15px;">
                    <i data-lucide="award" style="width:32px; height:32px; color:var(--accent,#FFD166); flex-shrink:0;"></i>
                    <div><h3>Usuarios con Certificado</h3><p><?= $completed_users_count ?? 0 ?></p></div>
                </div>
                <div class="metric-card" style="display:flex; align-items:center; gap:15px;">
                    <i data-lucide="target" style="width:32px; height:32px; color:<?= $score_color ?>; flex-shrink:0;"></i>
                    <div><h3>Puntaje Quiz Promedio</h3><p style="color:<?= $score_color ?>;"><?= round($avg_score ?? 0, 1) ?>%</p></div>
                </div>
                <div class="metric-card" style="display:flex; align-items:center; gap:15px;">
                    <i data-lucide="users" style="width:32px; height:32px; color:#118ab2; flex-shrink:0;"></i>
                    <div><h3>Total Inscripciones</h3><p><?= $total_inscripciones ?></p></div>
                </div>
                <div class="metric-card" style="display:flex; align-items:center; gap:15px;">
                    <i data-lucide="trending-up" style="width:32px; height:32px; color:#A78BFA; flex-shrink:0;"></i>
                    <div><h3>Tasa de Finalización</h3><p><?= $tasa_finalizacion ?>%</p></div>
                </div>
            </div>
            
            <div style="width: 100%; margin-top: 40px;">
                <h3>📚 Cursos Más Populares (Top 5)</h3>
                <?php if (!empty($popular_courses)): ?>
                    <?php foreach ($popular_courses as $course): ?>
                    <?php $porcentaje_barra = $max_inscritos > 0 ? round(($course['inscritos'] / $max_inscritos) * 100) : 0; ?>
                    <div style="margin-bottom: 12px;">
                        <div style="display:flex; justify-content:space-between; font-size:0.9rem; margin-bottom:4px;">
                            <span><?= htmlspecialchars($course['titulo']) ?></span>
                            <span style="opacity:0.7;"><?= $course['inscritos'] ?> inscritos</span>
                        </div>
                        <div style="background: rgba(255,255,255,0.08); border-radius: 8px; height: 16px; overflow:hidden;">
                            <div style="background: linear-gradient(90deg, #118ab2, #06D6A0); width: <?= max($porcentaje_barra, 3) ?>%; height: 100%; border-radius: 8px;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No hay datos de inscripción disponibles.</p>
                <?php endif; ?>
            </div>

            <div style="width: 100%; margin-top: 40px;">
                <h3>🎯 Promedio de Quiz por Materia</h3>
                <table class="data-table">
                    <thead>
                        <tr><th>Materia</th><th>Promedio</th><th>Intentos Registrados</th></tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($promedio_por_materia)): ?>
                            <?php foreach ($promedio_por_materia as $pm): ?>
                            <?php
                                $pm_color = '#ef476f';
                                if ($pm['promedio'] >= 70) { $pm_color = '#06D6A0'; }
                                elseif ($pm['promedio'] >= 50) { $pm_color = '#FFD166'; }
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($pm['titulo']) ?></td>
                                <td style="color:<?= $pm_color ?>; font-weight:700;"><?= round($pm['promedio'], 1) ?>%</td>
                                <td><?= $pm['total_intentos'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3">Aún no hay resultados de quiz registrados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div style="width: 100%; margin-top: 40px;">
                <h3>🕒 Últimos Inicios de Sesión</h3>
                <table class="data-table">
                    <thead>
                        <tr><th>Usuario</th><th>Correo</th><th>Fecha y Hora</th></tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($ultimos_ingresos)): ?>
                            <?php foreach ($ultimos_ingresos as $ing): ?>
                            <tr>
                                <td><?= htmlspecialchars($ing['nombre']) ?></td>
                                <td><?= htmlspecialchars($ing['correo_usuario']) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($ing['fecha_ingreso'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3">Aún no hay inicios de sesión registrados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
        <?php endif; ?>
    </div>

    <footer class="footer">
        © <?= date('Y') ?> Panel de Administración — EduLive
    </footer>
</main>

<script>
    lucide.createIcons();
    // (Añadir aquí tu lógica de Dark Mode si aplica)
</script>

</body>
</html>