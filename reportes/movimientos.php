<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

$titulo = "Reporte de movimientos";
$tipo = $_GET["tipo"] ?? "";
$desde = $_GET["desde"] ?? "";
$hasta = $_GET["hasta"] ?? "";
$tipos_validos = ["ENTRADA", "SALIDA", "PRESTAMO", "DEVOLUCION", "TRASLADO", "BAJA"];

if (!in_array($tipo, $tipos_validos, true)) {
	$tipo = "";
}

$condiciones = [];

if ($tipo !== "") {
	$condiciones[] = "m.tipo_movimiento = '" . $conexion->real_escape_string($tipo) . "'";
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
	SELECT
		m.tipo_movimiento,
		m.cantidad,
		m.fecha_hora,
		m.responsable,
		m.motivo,
		r.codigo_inventario,
		r.descripcion AS recurso,
		u.usuario
	FROM movimientos m
	INNER JOIN recursos r ON r.id = m.recurso_id
	LEFT JOIN usuarios u ON u.id = m.usuario_id
	$where
	ORDER BY m.fecha_hora DESC, m.id DESC
";

$resultado = $conexion->query($sql);

if (!$resultado) {
	die("Error al generar el reporte de movimientos: " . $conexion->error);
}

require_once "../includes/header.php";
require_once "../includes/navbar.php";
?>

<main class="main-content">
	<div class="page-header">
		<div>
			<h1>Reporte de movimientos</h1>
			<p>Consulta las entradas, salidas, préstamos y devoluciones registradas.</p>
		</div>
		<button type="button" class="btn btn-secondary" onclick="window.print()">Imprimir</button>
	</div>

	<section class="form-section report-filters">
		<form method="GET" class="resource-form">
			<div class="form-grid">
				<div class="form-group">
					<label for="tipo">Tipo de movimiento</label>
					<select id="tipo" name="tipo" class="form-control">
						<option value="">Todos los tipos</option>
						<?php foreach ($tipos_validos as $opcion): ?>
							<option value="<?php echo $opcion; ?>" <?php echo $tipo === $opcion ? "selected" : ""; ?>><?php echo $opcion; ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="form-group">
					<label for="desde">Desde</label>
					<input type="date" id="desde" name="desde" class="form-control" value="<?php echo htmlspecialchars($desde); ?>">
				</div>
				<div class="form-group">
					<label for="hasta">Hasta</label>
					<input type="date" id="hasta" name="hasta" class="form-control" value="<?php echo htmlspecialchars($hasta); ?>">
				</div>
			</div>
			<div class="form-actions">
				<a href="movimientos.php" class="btn btn-secondary">Limpiar</a>
				<button type="submit" class="btn btn-primary">Filtrar</button>
			</div>
		</form>
	</section>

	<section class="table-section">
		<div class="table-container">
			<table class="data-table">
				<thead>
					<tr>
						<th>Fecha</th>
						<th>Tipo</th>
						<th>Recurso</th>
						<th>Cantidad</th>
						<th>Responsable</th>
						<th>Motivo</th>
						<th>Usuario</th>
					</tr>
				</thead>
				<tbody>
					<?php if ($resultado->num_rows > 0): ?>
						<?php while ($movimiento = $resultado->fetch_assoc()): ?>
							<tr>
								<td><?php echo htmlspecialchars(date("d/m/Y H:i", strtotime($movimiento["fecha_hora"]))); ?></td>
								<td><?php echo htmlspecialchars($movimiento["tipo_movimiento"]); ?></td>
								<td><strong><?php echo htmlspecialchars($movimiento["codigo_inventario"]); ?></strong><br><?php echo htmlspecialchars($movimiento["recurso"]); ?></td>
								<td><?php echo (int) $movimiento["cantidad"]; ?></td>
								<td><?php echo htmlspecialchars($movimiento["responsable"] ?? "-"); ?></td>
								<td><?php echo htmlspecialchars($movimiento["motivo"] ?? "-"); ?></td>
								<td><?php echo htmlspecialchars($movimiento["usuario"] ?? "-"); ?></td>
							</tr>
						<?php endwhile; ?>
					<?php else: ?>
						<tr><td colspan="7" class="empty-state">No se encontraron movimientos con esos filtros.</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</section>
</main>

<?php require_once "../includes/footer.php"; ?>
