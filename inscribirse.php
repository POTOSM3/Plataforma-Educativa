<?php
session_start();

if (!isset($_SESSION['usuario'])) {
  header("Location: login.php");
  exit;
}

$usuario = $_SESSION['usuario'];
$id_curso = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_curso <= 0) {
  die("Error: curso no válido.");
}

$archivo_inscripciones = 'data/inscripciones.json';
$archivo_temas = 'data/temas.json';

$inscripciones = file_exists($archivo_inscripciones) ? json_decode(file_get_contents($archivo_inscripciones), true) : [];
$temas = file_exists($archivo_temas) ? json_decode(file_get_contents($archivo_temas), true) : [];

// Verificar que el curso exista
$curso_valido = false;
$nombre_curso = '';
foreach ($temas as $t) {
  if ($t['id'] == $id_curso) {
    $curso_valido = true;
    $nombre_curso = $t['titulo'];
    break;
  }
}
if (!$curso_valido) {
  die("El curso no existe o fue eliminado.");
}

// Verificar si ya está inscrito
foreach ($inscripciones as $i) {
  if ($i['correo'] === $usuario['correo'] && $i['id_curso'] === $id_curso) {
    echo "<script>alert('✅ Ya estás inscrito en el curso $nombre_curso'); window.location='index.php';</script>";
    exit;
  }
}

// Registrar nueva inscripción
$nueva = [
  "correo" => $usuario['correo'],
  "nombre" => $usuario['nombre'],
  "id_curso" => $id_curso,
  "curso" => $nombre_curso,
  "fecha" => date("Y-m-d H:i:s")
];

$inscripciones[] = $nueva;
file_put_contents($archivo_inscripciones, json_encode($inscripciones, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "<script>alert('🎓 Te has inscrito en $nombre_curso'); window.location='index.php';</script>";
exit;
?>
