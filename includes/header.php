<?php
$titulo = $titulo ?? "Sistema de Inventario";
?>

<!DOCTYPE html>
<html lang="es">
<link
    rel="stylesheet"
    href="/inventario_colegio/assets/css/style.css"
>

<script
    src="/inventario_colegio/assets/js/app.js"
></script>
<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?php echo htmlspecialchars($titulo); ?>
        - Inventario
    </title>

    <link
        rel="stylesheet"
        href="/inventario_colegio/assets/css/style.css"
    >

</head>

<body>