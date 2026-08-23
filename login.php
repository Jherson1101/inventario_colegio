<?php

session_start();

require_once "config/database.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $usuario = trim($_POST["usuario"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($usuario === "" || $password === "") {

        $error = "Ingrese usuario y contraseña.";

    } else {

        $sql = "SELECT id, nombre, usuario, password, rol, estado
                FROM usuarios
                WHERE usuario = ?
                LIMIT 1";

        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("s", $usuario);
        $stmt->execute();

        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 1) {

            $usuario_db = $resultado->fetch_assoc();

            if ($usuario_db["estado"] !== "ACTIVO") {

                $error = "El usuario está inactivo.";

            } elseif (password_verify($password, $usuario_db["password"])) {

                session_regenerate_id(true);

                $_SESSION["usuario_id"] = $usuario_db["id"];
                $_SESSION["nombre"] = $usuario_db["nombre"];
                $_SESSION["usuario"] = $usuario_db["usuario"];
                $_SESSION["rol"] = $usuario_db["rol"];

                header("Location: dashboard/index.php");
                exit;

            } else {

                $error = "Usuario o contraseña incorrectos.";
            }

        } else {

            $error = "Usuario o contraseña incorrectos.";
        }

        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Iniciar sesión - Inventario</title>

</head>

<body>

    <h1>Sistema de Inventario</h1>

    <h2>Iniciar sesión</h2>

    <?php if ($error !== ""): ?>

        <p>
            <?php echo htmlspecialchars($error); ?>
        </p>

    <?php endif; ?>

    <form method="POST">

        <div>

            <label for="usuario">
                Usuario:
            </label>

            <input
                type="text"
                id="usuario"
                name="usuario"
                autocomplete="username"
                required
            >

        </div>

        <br>

        <div>

            <label for="password">
                Contraseña:
            </label>

            <input
                type="password"
                id="password"
                name="password"
                autocomplete="current-password"
                required
            >

        </div>

        <br>

        <button type="submit">
            Iniciar sesión
        </button>

    </form>

</body>

</html>