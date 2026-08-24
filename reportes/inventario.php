<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

$titulo = "Reportes";
$reporte = $_GET["reporte"] ?? "inventario";
$reportes_validos = ["inventario", "movimientos", "prestamos"];

if (!in_array($reporte, $reportes_validos, true)) {
    $reporte = "inventario";
}

$situaciones_validas = ["EN USO", "DISPONIBLE", "PRESTADO", "EN MANTENIMIENTO", "DADO DE BAJA", "ALMACENADO"];
$tipos_validos = ["ENTRADA", "SALIDA", "PRESTAMO", "DEVOLUCION", "TRASLADO", "BAJA"];
$buscar = trim($_GET["buscar"] ?? "");
$situacion = $_GET["situacion"] ?? "";
$tipo = $_GET["tipo"] ?? "";
$desde = $_GET["desde"] ?? "";
$hasta = $_GET["hasta"] ?? "";

if ($reporte === "inventario") {
    $condiciones = [];

    if (in_array($situacion, $situaciones_validas, true)) {
        $condiciones[] = "r.situacion = '" . $conexion->real_escape_string($situacion) . "'";
    } else {
        $situacion = "";
    }

    if ($buscar !== "") {
        $buscar_sql = $conexion->real_escape_string($buscar);
        $condiciones[] = "(r.codigo_inventario LIKE '%$buscar_sql%' OR r.descripcion LIKE '%$buscar_sql%' OR r.marca LIKE '%$buscar_sql%' OR r.modelo LIKE '%$buscar_sql%')";
    }

    $where = count($condiciones) > 0 ? "WHERE " . implode(" AND ", $condiciones) : "";
    $sql = "
        SELECT r.codigo_inventario, r.descripcion, r.marca, r.modelo, r.cantidad,
               r.situacion, r.estado, c.nombre AS categoria, a.nombre AS area,
               u.nombre AS ubicacion
        FROM recursos r
        LEFT JOIN categorias c ON c.id = r.categoria_id
        LEFT JOIN areas a ON a.id = r.area_id
        LEFT JOIN ubicaciones u ON u.id = r.ubicacion_id
        $where
        ORDER BY r.codigo_inventario ASC
    ";
} elseif ($reporte === "movimientos") {
    $condiciones = [];

    if (in_array($tipo, $tipos_validos, true)) {
        $condiciones[] = "m.tipo_movimiento = '" . $conexion->real_escape_string($tipo) . "'";
    } else {
        $tipo = "";
    }

    if (preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $desde)) {
        $condiciones[] = "m.fecha_hora >= '" . $conexion->real_escape_string($desde) . " 00:00:00'";
    } else {
        $desde = "";
    }

    if (preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $hasta)) {
        $condiciones[] = "m.fecha_hora <= '" . $conexion->real_escape_string($hasta) . " 23:59:59'";
    } else {
        $hasta = "";
    }

    $where = count($condiciones) > 0 ? "WHERE " . implode(" AND ", $condiciones) : "";
    $sql = "
        SELECT m.tipo_movimiento, m.cantidad, m.fecha_hora, m.responsable,
               m.motivo, r.codigo_inventario, r.descripcion AS recurso, u.usuario
        FROM movimientos m
        INNER JOIN recursos r ON r.id = m.recurso_id
        LEFT JOIN usuarios u ON u.id = m.usuario_id
        $where
        ORDER BY m.fecha_hora DESC, m.id DESC
    ";
} else {
    $sql = "
        SELECT r.codigo_inventario, r.descripcion AS recurso, m.cantidad AS cantidad_prestada,
               m.fecha_hora, m.responsable, m.motivo, u.usuario
        FROM recursos r
        INNER JOIN movimientos m ON m.id = (
            SELECT MAX(m2.id) FROM movimientos m2
            WHERE m2.recurso_id = r.id AND m2.tipo_movimiento = 'PRESTAMO'
        )
        LEFT JOIN usuarios u ON u.id = m.usuario_id
        WHERE r.situacion = 'PRESTADO'
        ORDER BY m.fecha_hora DESC
    ";
}

$resultado = $conexion->query($sql);

if (!$resultado) {
    die("Error al generar el reporte: " . $conexion->error);
}

require_once "../includes/header.php";
require_once "../includes/navbar.php";
?>

<main class="main-content report-page">
    <div class="page-header report-header">
        <div>
            <h1>Reportes</h1>
            <p>Consulta la información del sistema desde una sola vista.</p>
        </div>
        <button type="button" class="btn btn-secondary" onclick="window.print()">Imprimir</button>
    </div>

    <nav class="report-tabs" aria-label="Tipo de reporte">
        <a class="<?php echo $reporte === "inventario" ? "active" : ""; ?>" href="inventario.php?reporte=inventario">Inventario</a>
        <a class="<?php echo $reporte === "movimientos" ? "active" : ""; ?>" href="inventario.php?reporte=movimientos">Movimientos</a>
        <a class="<?php echo $reporte === "prestamos" ? "active" : ""; ?>" href="inventario.php?reporte=prestamos">Préstamos pendientes</a>
    </nav>

    <?php if ($reporte !== "prestamos"): ?>
        <section class="form-section report-filters">
            <form method="GET" class="resource-form">
                <input type="hidden" name="reporte" value="<?php echo htmlspecialchars($reporte); ?>">
                <div class="report-filter-grid">
                    <?php if ($reporte === "inventario"): ?>
                        <div class="form-group">
                            <label for="buscar">Buscar recurso</label>
                            <input type="search" id="buscar" name="buscar" class="form-control" value="<?php echo htmlspecialchars($buscar); ?>" placeholder="Código o descripción">
                        </div>
                        <div class="form-group">
                            <label for="situacion">Situación</label>
                            <select id="situacion" name="situacion" class="form-control">
                                <option value="">Todas las situaciones</option>
                                <?php foreach ($situaciones_validas as $opcion): ?>
                                    <option value="<?php echo htmlspecialchars($opcion); ?>" <?php echo $situacion === $opcion ? "selected" : ""; ?>><?php echo htmlspecialchars($opcion); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <div class="form-group">
                            <label for="tipo">Tipo de movimiento</label>
                            <select id="tipo" name="tipo" class="form-control">
                                <option value="">Todos los tipos</option>
                                <?php foreach ($tipos_validos as $opcion): ?>
                                    <option value="<?php echo $opcion; ?>" <?php echo $tipo === $opcion ? "selected" : ""; ?>><?php echo $opcion; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label for="desde">Desde</label><input type="date" id="desde" name="desde" class="form-control" value="<?php echo htmlspecialchars($desde); ?>"></div>
                        <div class="form-group"><label for="hasta">Hasta</label><input type="date" id="hasta" name="hasta" class="form-control" value="<?php echo htmlspecialchars($hasta); ?>"></div>
                    <?php endif; ?>
                </div>
                <div class="form-actions">
                    <a href="inventario.php?reporte=<?php echo htmlspecialchars($reporte); ?>" class="btn btn-secondary">Limpiar</a>
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                </div>
            </form>
        </section>
    <?php endif; ?>

    <section class="table-section report-table-section">
        <div class="table-container">
            <table class="data-table report-table">
                <?php if ($reporte === "inventario"): ?>
                    <thead><tr><th>Código</th><th>Recurso</th><th>Categoría</th><th>Área</th><th>Ubicación</th><th>Cantidad</th><th>Situación</th><th>Estado</th></tr></thead>
                    <tbody>
                        <?php if ($resultado->num_rows > 0): while ($fila = $resultado->fetch_assoc()): ?>
                            <tr><td><?php echo htmlspecialchars($fila["codigo_inventario"]); ?></td><td><?php echo htmlspecialchars($fila["descripcion"]); ?><small><?php echo htmlspecialchars(trim(($fila["marca"] ?? "") . " " . ($fila["modelo"] ?? ""))); ?></small></td><td><?php echo htmlspecialchars($fila["categoria"] ?? "-"); ?></td><td><?php echo htmlspecialchars($fila["area"] ?? "-"); ?></td><td><?php echo htmlspecialchars($fila["ubicacion"] ?? "-"); ?></td><td><?php echo (int) $fila["cantidad"]; ?></td><td><?php echo htmlspecialchars($fila["situacion"]); ?></td><td><?php echo htmlspecialchars($fila["estado"]); ?></td></tr>
                        <?php endwhile; else: ?><tr><td colspan="8" class="empty-state">No se encontraron recursos.</td></tr><?php endif; ?>
                    </tbody>
                <?php elseif ($reporte === "movimientos"): ?>
                    <thead><tr><th>Fecha</th><th>Tipo</th><th>Recurso</th><th>Cantidad</th><th>Responsable</th><th>Motivo</th><th>Usuario</th></tr></thead>
                    <tbody>
                        <?php if ($resultado->num_rows > 0): while ($fila = $resultado->fetch_assoc()): ?>
                            <tr><td><?php echo htmlspecialchars(date("d/m/Y H:i", strtotime($fila["fecha_hora"]))); ?></td><td><?php echo htmlspecialchars($fila["tipo_movimiento"]); ?></td><td><strong><?php echo htmlspecialchars($fila["codigo_inventario"]); ?></strong><small><?php echo htmlspecialchars($fila["recurso"]); ?></small></td><td><?php echo (int) $fila["cantidad"]; ?></td><td><?php echo htmlspecialchars($fila["responsable"] ?? "-"); ?></td><td><?php echo htmlspecialchars($fila["motivo"] ?? "-"); ?></td><td><?php echo htmlspecialchars($fila["usuario"] ?? "-"); ?></td></tr>
                        <?php endwhile; else: ?><tr><td colspan="7" class="empty-state">No se encontraron movimientos.</td></tr><?php endif; ?>
                    </tbody>
                <?php else: ?>
                    <thead><tr><th>Fecha</th><th>Recurso</th><th>Cantidad</th><th>Responsable</th><th>Motivo</th><th>Registrado por</th></tr></thead>
                    <tbody>
                        <?php if ($resultado->num_rows > 0): while ($fila = $resultado->fetch_assoc()): ?>
                            <tr><td><?php echo htmlspecialchars(date("d/m/Y H:i", strtotime($fila["fecha_hora"]))); ?></td><td><strong><?php echo htmlspecialchars($fila["codigo_inventario"]); ?></strong><small><?php echo htmlspecialchars($fila["recurso"]); ?></small></td><td><?php echo (int) $fila["cantidad_prestada"]; ?></td><td><?php echo htmlspecialchars($fila["responsable"] ?? "-"); ?></td><td><?php echo htmlspecialchars($fila["motivo"] ?? "-"); ?></td><td><?php echo htmlspecialchars($fila["usuario"] ?? "-"); ?></td></tr>
                        <?php endwhile; else: ?><tr><td colspan="6" class="empty-state">No hay préstamos pendientes.</td></tr><?php endif; ?>
                    </tbody>
                <?php endif; ?>
            </table>
        </div>
    </section>
</main>

<?php require_once "../includes/footer.php"; ?>