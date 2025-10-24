<?php
$nombre = $_POST["nombre"] ?? "";
$email = $_POST["email"] ?? "";
$nota = $_POST["nota"] ?? "";

if (empty($nombre) || empty($email) || $nota < 0) {
    die("Error en el formulario.");
}

$data = json_decode(file_get_contents("data/estudiantes.json"), true) ?? [];
$data[] = ["nombre" => $nombre, "email" => $email, "nota" => (int)$nota];
file_put_contents("data/estudiantes.json", json_encode($data, JSON_PRETTY_PRINT));

header("Location: registro.php");
