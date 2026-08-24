<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

$titulo = "Reporte de préstamos";

$sql = "
	SELECT
		r.codigo_inventario,
		r.descripcion AS recurso,
		r.cantidad,
		m.cantidad AS cantidad_prestada,
		m.fecha_hora,
		m.responsable,
		m.motivo,
		u.usuario
	FROM recursos r
	INNER JOIN movimientos m ON m.id = (
		SELECT MAX(m2.id)
		FROM movimientos m2
		WHERE m2.recurso_id = r.id
		  AND m2.tipo_movimiento = 'PRESTAMO'
	)
	LEFT JOIN usuarios u ON u.id = m.usuario_id
	WHERE r.situacion = 'PRESTADO'
	ORDER BY m.fecha_hora DESC
";

$resultado = $conexion->query($sql);

if (!$resultado) {
	die("Error al generar el reporte de préstamos: " . $conexion->error);
}

require_once "../includes/header.php";
require_once "../includes/navbar.php";
?>

<main class="main-content">
	<div class="page-header">
		<div>
			<h1>Reporte de préstamos</h1>
			<p>Consulta los recursos que actualmente figuran como prestados.</p>
		</div>
		<button type="button" class="btn btn-secondary" onclick="window.print()">Imprimir</button>
	</div>

	<section class="table-section">
		<div class="table-container">
			<table class="data-table">
				<thead>
					<tr>
						<th>Fecha</th>
						<th>Recurso</th>
						<th>Cantidad</th>
						<th>Responsable</th>
						<th>Motivo</th>
						<th>Registrado por</th>
					</tr>
				</thead>
				<tbody>
					<?php if ($resultado->num_rows > 0): ?>
						<?php while ($prestamo = $resultado->fetch_assoc()): ?>
							<tr>
								<td><?php echo htmlspecialchars(date("d/m/Y H:i", strtotime($prestamo["fecha_hora"]))); ?></td>
								<td><strong><?php echo htmlspecialchars($prestamo["codigo_inventario"]); ?></strong><br><?php echo htmlspecialchars($prestamo["recurso"]); ?></td>
								<td><?php echo (int) $prestamo["cantidad_prestada"]; ?></td>
								<td><?php echo htmlspecialchars($prestamo["responsable"] ?? "-"); ?></td>
								<td><?php echo htmlspecialchars($prestamo["motivo"] ?? "-"); ?></td>
								<td><?php echo htmlspecialchars($prestamo["usuario"] ?? "-"); ?></td>
							</tr>
						<?php endwhile; ?>
					<?php else: ?>
						<tr><td colspan="6" class="empty-state">No hay préstamos pendientes.</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</section>
</main>

<?php require_once "../includes/footer.php"; ?>
