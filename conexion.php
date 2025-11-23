<?php
$host = "localhost";
$db   = "edulive";
$user = "root"; 
$pass = "";     
$charset = "utf8mb4";

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // SOLO mostrar el mensaje si este archivo se ejecuta directamente
    if (realpath(_FILE_) === realpath($_SERVER["SCRIPT_FILENAME"])) {
        echo "✅ Conexión exitosa a la base de datos.";
    }

} catch (PDOException $e) {

    if (realpath(_FILE_) === realpath($_SERVER["SCRIPT_FILENAME"])) {
        die("❌ Error al conectar con la base de datos: " . $e->getMessage());
    } else {
        throw $e; // No imprimir nada si es incluido
    }

}
?>