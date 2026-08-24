<?php

$host = "127.0.0.1";
$usuario = "root";
$password = "";
$base_datos = "inventario_colegio";
$puerto = 3307;

$conexion = new mysqli(
    $host,
    $usuario,
    $password,
    $base_datos,
    $puerto
);

if ($conexion->connect_error) {
    die("Error de conexión a la base de datos: " . $conexion->connect_error);
}

$conexion->set_charset("utf8mb4");