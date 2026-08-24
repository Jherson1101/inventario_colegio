<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

$titulo = "Ubicaciones";

$mensaje = "";
$tipo_mensaje = "";

if (isset($_GET["success"])) {
    $mensaje = "Ubicación guardada correctamente.";
    $tipo_mensaje = "alert-success";
}

if (isset($_GET["delete"]) && isset($_GET["id"])) {
    $id = (int) $_GET["id"];

    $sql_check = "
        SELECT COUNT(*) AS total
        FROM recursos
        WHERE ubicacion_id = ? OR ubicacion_habitual_id = ?
        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql_check);

    if ($stmt) {
        $stmt->bind_param("ii", $id, $id);
        $stmt->execute();
        $resultado_check = $stmt->get_result();
        $datos = $resultado_check->fetch_assoc();
        $stmt->close();

        if ((int) ($datos["total"] ?? 0) > 0) {
            $mensaje = "No se puede eliminar la ubicación porque está asociada a recursos.";
            $tipo_mensaje = "alert-error";
        } else {
            $sql_delete = "DELETE FROM ubicaciones WHERE id = ? LIMIT 1";
            $stmt = $conexion->prepare($sql_delete);

            if ($stmt) {
                $stmt->bind_param("i", $id);

                if ($stmt->execute()) {
                    $mensaje = "Ubicación eliminada correctamente.";
                    $tipo_mensaje = "alert-success";
                } else {
                    $mensaje = "No se pudo eliminar la ubicación.";
                    $tipo_mensaje = "alert-error";
                }

                $stmt->close();
            }
        }
    }
}

$sql = "
    SELECT
        u.id,
        u.nombre,
        u.descripcion,
        u.estado,
        a.nombre AS area
    FROM ubicaciones u
    LEFT JOIN areas a ON a.id = u.area_id
    ORDER BY u.nombre ASC
";

$resultado = $conexion->query($sql);

if (!$resultado) {
    die("Error al consultar ubicaciones: " . $conexion->error);
}

require_once "../includes/header.php";
require_once "../includes/navbar.php";
?>

<main class="main-content">

    <div class="page-header">

        <div>
            <h1>Ubicaciones</h1>
            <p>Administra los ambientes, aulas y espacios del colegio.</p>
        </div>

        <a href="crear.php" class="btn btn-primary">
            + Nueva ubicación
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
                        <th>Área</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if ($resultado && $resultado->num_rows > 0): ?>

                        <?php while ($ubicacion = $resultado->fetch_assoc()): ?>

                            <tr>
                                <td><?php echo (int) $ubicacion["id"]; ?></td>

                                <td>
                                    <?php echo htmlspecialchars($ubicacion["area"] ?? "-" ); ?>
                                </td>

                                <td>
                                    <strong>
                                        <?php echo htmlspecialchars($ubicacion["nombre"]); ?>
                                    </strong>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($ubicacion["descripcion"] ?? "-"); ?>
                                </td>

                                <td>
                                    <span class="status-badge <?php echo strtolower($ubicacion["estado"] ?? "activo") === "activo" ? "badge-bueno" : "badge-regular"; ?>">
                                        <?php echo htmlspecialchars($ubicacion["estado"] ?? "ACTIVO"); ?>
                                    </span>
                                </td>

                                <td>
                                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                        <a href="editar.php?id=<?php echo (int) $ubicacion["id"]; ?>" class="btn btn-small">
                                            Editar
                                        </a>

                                        <a
                                            href="index.php?delete=1&id=<?php echo (int) $ubicacion["id"]; ?>"
                                            class="btn btn-small btn-danger"
                                            onclick="return confirm('¿Deseas eliminar esta ubicación?');"
                                        >
                                            Eliminar
                                        </a>
                                    </div>
                                </td>
                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6" class="empty-state">
                                No hay ubicaciones registradas.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

<?php require_once "../includes/footer.php"; ?>
