<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

$titulo = "Categorías";

$mensaje = "";
$tipo_mensaje = "";

if (isset($_GET["success"])) {
    $mensaje = "Categoría guardada correctamente.";
    $tipo_mensaje = "alert-success";
}

if (isset($_GET["delete"]) && isset($_GET["id"])) {
    $id = (int) $_GET["id"];

    $sql_check = "
        SELECT COUNT(*) AS total
        FROM recursos
        WHERE categoria_id = ?
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
            $mensaje = "No se puede eliminar la categoría porque ya está asociada a recursos.";
            $tipo_mensaje = "alert-error";
        } else {
            $sql_delete = "DELETE FROM categorias WHERE id = ? LIMIT 1";
            $stmt = $conexion->prepare($sql_delete);

            if ($stmt) {
                $stmt->bind_param("i", $id);

                if ($stmt->execute()) {
                    $mensaje = "Categoría eliminada correctamente.";
                    $tipo_mensaje = "alert-success";
                } else {
                    $mensaje = "No se pudo eliminar la categoría.";
                    $tipo_mensaje = "alert-error";
                }

                $stmt->close();
            }
        }
    }
}

$sql = "
    SELECT id, nombre, descripcion, estado
    FROM categorias
    ORDER BY nombre ASC
";

$resultado = $conexion->query($sql);

if (!$resultado) {
    die("Error al consultar categorías: " . $conexion->error);
}

require_once "../includes/header.php";
require_once "../includes/navbar.php";
?>

<main class="main-content">

    <div class="page-header">

        <div>
            <h1>Categorías</h1>
            <p>Administra las categorías de recursos del colegio.</p>
        </div>

        <a href="crear.php" class="btn btn-primary">
            + Nueva categoría
        </a>

    </div>

    <?php if ($mensaje !== ""): ?>
        <div class="alert <?php echo htmlspecialchars($tipo_mensaje); ?>">
            <?php echo htmlspecialchars($mensaje); ?>
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

                        <?php while ($categoria = $resultado->fetch_assoc()): ?>

                            <tr>
                                <td><?php echo (int) $categoria["id"]; ?></td>

                                <td>
                                    <strong>
                                        <?php echo htmlspecialchars($categoria["nombre"]); ?>
                                    </strong>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($categoria["descripcion"] ?? "-"); ?>
                                </td>

                                <td>
                                    <span class="status-badge <?php echo strtolower($categoria["estado"] ?? "activo") === "activo" ? "badge-bueno" : "badge-regular"; ?>">
                                        <?php echo htmlspecialchars($categoria["estado"] ?? "ACTIVO"); ?>
                                    </span>
                                </td>

                                <td>
                                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                        <a href="editar.php?id=<?php echo (int) $categoria["id"]; ?>" class="btn btn-small">
                                            Editar
                                        </a>

                                        <a
                                            href="index.php?delete=1&id=<?php echo (int) $categoria["id"]; ?>"
                                            class="btn btn-small btn-danger"
                                            onclick="return confirm('¿Deseas eliminar esta categoría?');"
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
                                No hay categorías registradas.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

<?php require_once "../includes/footer.php"; ?>
