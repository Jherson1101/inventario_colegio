<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

$titulo = "Nueva categoría";

$nombre = "";
$descripcion = "";
$estado = "ACTIVO";

$error = "";
$exito = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST["nombre"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");
    $estado = $_POST["estado"] ?? "ACTIVO";

    if ($nombre === "") {
        $error = "El nombre de la categoría es obligatorio.";
    } elseif (!in_array($estado, ["ACTIVO", "INACTIVO"], true)) {
        $error = "Seleccione un estado válido.";
    } else {
        $sql = "
            SELECT id
            FROM categorias
            WHERE nombre = ?
            LIMIT 1
        ";

        $stmt = $conexion->prepare($sql);

        if (!$stmt) {
            die("Error al verificar la categoría: " . $conexion->error);
        }

        $stmt->bind_param("s", $nombre);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $stmt->close();

        if ($resultado->num_rows > 0) {
            $error = "Ya existe una categoría con ese nombre.";
        } else {
            $sql = "
                INSERT INTO categorias (nombre, descripcion, estado)
                VALUES (?, ?, ?)
            ";

            $stmt = $conexion->prepare($sql);

            if (!$stmt) {
                die("Error al preparar la inserción: " . $conexion->error);
            }

            $stmt->bind_param("sss", $nombre, $descripcion, $estado);

            if ($stmt->execute()) {
                $stmt->close();
                header("Location: index.php?success=1");
                exit;
            }

            $error = "No se pudo guardar la categoría.";
            $stmt->close();
        }
    }
}

require_once "../includes/header.php";
require_once "../includes/navbar.php";
?>

<main class="main-content">

    <div class="page-header">
        <div>
            <h1>Nueva categoría</h1>
            <p>Registra una nueva categoría para clasificar los recursos.</p>
        </div>
    </div>

    <?php if ($error !== ""): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <section class="form-section">

        <form method="POST" class="resource-form">

            <div class="form-grid">

                <div class="form-group form-group-full">
                    <label for="nombre">Nombre</label>
                    <input
                        type="text"
                        id="nombre"
                        name="nombre"
                        class="form-control"
                        value="<?php echo htmlspecialchars($nombre); ?>"
                        required
                    >
                </div>

                <div class="form-group form-group-full">
                    <label for="descripcion">Descripción</label>
                    <textarea
                        id="descripcion"
                        name="descripcion"
                        class="form-control"
                        rows="4"
                    ><?php echo htmlspecialchars($descripcion); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="estado">Estado</label>
                    <select id="estado" name="estado" class="form-control">
                        <option value="ACTIVO" <?php echo $estado === "ACTIVO" ? "selected" : ""; ?>>ACTIVO</option>
                        <option value="INACTIVO" <?php echo $estado === "INACTIVO" ? "selected" : ""; ?>>INACTIVO</option>
                    </select>
                </div>

            </div>

            <div class="form-actions">
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Guardar categoría</button>
            </div>

        </form>

    </section>

</main>

<?php require_once "../includes/footer.php"; ?>
