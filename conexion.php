<?php
$db_host = '127.0.0.1';
$db_name = 'conciertos';
$db_user = 'root';
$db_pass = ''; 

try {
    $conexion = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
