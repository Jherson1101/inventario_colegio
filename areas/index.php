<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

$titulo = "Áreas";

$mensaje = "";
$tipo_mensaje = "";

if (isset($_GET["success"])) {
    $mensaje = "Área guardada correctamente.";
    $tipo_mensaje = "alert-success";
}

if (isset($_GET["delete"]) && isset($_GET["id"])) {
    $id = (int) $_GET["id"];

    $sql_check = "
        SELECT COUNT(*) AS total
        FROM recursos
        WHERE area_id = ?
        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql_check);

    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $resultado_check = $stmt->get_result();
        $datos = $resultado_check->fetch_assoc();
        $stmt->close();

        if ((int) ($datos["total"] ?? 0) > 0) {
            $mensaje = "No se puede eliminar el área porque ya está asociada a recursos.";
            $tipo_mensaje = "alert-error";
        } else {
            $sql_delete = "DELETE FROM areas WHERE id = ? LIMIT 1";
            $stmt = $conexion->prepare($sql_delete);

            if ($stmt) {
                $stmt->bind_param("i", $id);

                if ($stmt->execute()) {
                    $mensaje = "Área eliminada correctamente.";
                    $tipo_mensaje = "alert-success";
                } else {
                    $mensaje = "No se pudo eliminar el área.";
                    $tipo_mensaje = "alert-error";
                }

                $stmt->close();
            }
        }
    }
}

$sql = "
    SELECT id, nombre, descripcion, estado
    FROM areas
    ORDER BY nombre ASC
";

$resultado = $conexion->query($sql);

if (!$resultado) {
    die("Error al consultar áreas: " . $conexion->error);
}

require_once "../includes/header.php";
require_once "../includes/navbar.php";
?>

<main class="main-content">

    <div class="page-header">

        <div>
            <h1>Áreas</h1>
            <p>Administra las áreas del colegio asociadas a los recursos.</p>
        </div>

        <a href="crear.php" class="btn btn-primary">
            + Nueva área
        </a>

    </div>

    <?php if ($mensaje !== ""): ?>
        <div class="alert <?php echo htmlspecialchars($tipo_mensaje); ?>">
            <?php echo htmlspecialchars($mensaje);
            ?>
        </div>
    <?php endif; ?>

    <section class="table-section">

        <div class="table-container">

            <table class="data-table">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if ($resultado && $resultado->num_rows > 0): ?>

                        <?php while ($area = $resultado->fetch_assoc()): ?>

                            <tr>
                                <td><?php echo (int) $area["id"]; ?></td>

                                <td>
                                    <strong>
                                        <?php echo htmlspecialchars($area["nombre"]); ?>
                                    </strong>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($area["descripcion"] ?? "-"); ?>
                                </td>

                                <td>
                                    <span class="status-badge <?php echo strtolower($area["estado"] ?? "activo") === "activo" ? "badge-bueno" : "badge-regular"; ?>">
                                        <?php echo htmlspecialchars($area["estado"] ?? "ACTIVO"); ?>
                                    </span>
                                </td>

                                <td>
                                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                        <a href="editar.php?id=<?php echo (int) $area["id"]; ?>" class="btn btn-small">
                                            Editar
                                        </a>

                                        <a
                                            href="index.php?delete=1&id=<?php echo (int) $area["id"]; ?>"
                                            class="btn btn-small btn-danger"
                                            onclick="return confirm('¿Deseas eliminar esta área?');"
                                        >
                                            Eliminar
                                        </a>
                                    </div>
                                </td>
                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="5" class="empty-state">
                                No hay áreas registradas.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

<?php require_once "../includes/footer.php"; ?>
