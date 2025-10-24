<?php
$nombre = $_POST["nombre"] ?? "";
$email = $_POST["email"] ?? "";
$mensaje = $_POST["mensaje"] ?? "";

if (empty($email) || strlen($mensaje) < 5) {
    die("Error: verifica tus datos.");
}

$data = json_decode(file_get_contents("data/contactos.json"), true) ?? [];
$data[] = ["nombre" => $nombre, "email" => $email, "mensaje" => $mensaje];
file_put_contents("data/contactos.json", json_encode($data, JSON_PRETTY_PRINT));

echo "Mensaje enviado correctamente.";
echo '<br><a href="index.php">Volver</a>';

