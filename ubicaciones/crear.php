<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

$titulo = "Nueva ubicación";

$nombre = "";
$descripcion = "";
$estado = "ACTIVO";
$area_id = "";

$error = "";

$sql_areas = "
    SELECT id, nombre
    FROM areas
    WHERE estado = 'ACTIVO'
    ORDER BY nombre ASC
";

$areas = $conexion->query($sql_areas);

if (!$areas) {
    die("Error al consultar áreas: " . $conexion->error);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST["nombre"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");
    $estado = $_POST["estado"] ?? "ACTIVO";
    $area_id = isset($_POST["area_id"]) ? (int) $_POST["area_id"] : 0;

    if ($nombre === "") {
        $error = "El nombre de la ubicación es obligatorio.";
    } elseif ($area_id <= 0) {
        $error = "Seleccione un área para la ubicación.";
    } elseif (!in_array($estado, ["ACTIVO", "INACTIVO"], true)) {
        $error = "Seleccione un estado válido.";
    } else {
        $sql = "
            SELECT id
            FROM ubicaciones
            WHERE nombre = ? AND area_id = ?
            LIMIT 1
        ";

        $stmt = $conexion->prepare($sql);

        if (!$stmt) {
            die("Error al verificar la ubicación: " . $conexion->error);
        }

        $stmt->bind_param("si", $nombre, $area_id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $stmt->close();

        if ($resultado->num_rows > 0) {
            $error = "Ya existe una ubicación con ese nombre en el área seleccionada.";
        } else {
            $sql = "
                INSERT INTO ubicaciones (area_id, nombre, descripcion, estado)
                VALUES (?, ?, ?, ?)
            ";

            $stmt = $conexion->prepare($sql);

            if (!$stmt) {
                die("Error al preparar la inserción: " . $conexion->error);
            }

            $stmt->bind_param("isss", $area_id, $nombre, $descripcion, $estado);

            if ($stmt->execute()) {
                $stmt->close();
                header("Location: index.php?success=1");
                exit;
            }

            $error = "No se pudo guardar la ubicación.";
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
            <h1>Nueva ubicación</h1>
            <p>Registra un ambiente, aula, laboratorio o espacio físico.</p>
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

                <div class="form-group">
                    <label for="area_id">Área</label>
                    <select id="area_id" name="area_id" class="form-control" required>
                        <option value="">Seleccione un área</option>

                        <?php while ($area = $areas->fetch_assoc()): ?>
                            <option value="<?php echo (int) $area["id"]; ?>" <?php echo $area_id == $area["id"] ? "selected" : ""; ?>>
                                <?php echo htmlspecialchars($area["nombre"]); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

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
                <button type="submit" class="btn btn-primary">Guardar ubicación</button>
            </div>

        </form>

    </section>

</main>

<?php require_once "../includes/footer.php"; ?>
