<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

$titulo = "Mantenimiento";
$mensaje = "";
$tipo_mensaje = "";

if (isset($_GET["success"])) {
	$mensaje = "Mantenimiento registrado correctamente.";
	$tipo_mensaje = "alert-success";
}

if (isset($_GET["updated"])) {
	$mensaje = "Estado del mantenimiento actualizado correctamente.";
	$tipo_mensaje = "alert-success";
}

$sql = "
	SELECT
		m.id,
		m.fecha_reporte,
		m.tipo,
		m.descripcion,
		m.estado,
		m.responsable,
		r.codigo_inventario,
		r.descripcion AS recurso
	FROM mantenimiento m
	INNER JOIN recursos r ON r.id = m.recurso_id
	ORDER BY m.fecha_reporte DESC, m.id DESC
";

$resultado = $conexion->query($sql);

if (!$resultado) {
	die("Error al consultar mantenimientos: " . $conexion->error);
}

require_once "../includes/header.php";
require_once "../includes/navbar.php";
?>

<main class="main-content">

	<div class="page-header">
		<div>
			<h1>Mantenimiento</h1>
			<p>Registra y da seguimiento a las reparaciones de los recursos.</p>
		</div>

		<a href="crear.php" class="btn btn-primary">
			+ Nuevo mantenimiento
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
						<th>Fecha</th>
						<th>Recurso</th>
						<th>Tipo</th>
						<th>Descripción</th>
						<th>Estado</th>
						<th>Responsable</th>
						<th>Acciones</th>
					</tr>
				</thead>

				<tbody>
					<?php if ($resultado->num_rows > 0): ?>
						<?php while ($mantenimiento = $resultado->fetch_assoc()): ?>
							<tr>
								<td><?php echo htmlspecialchars(date("d/m/Y H:i", strtotime($mantenimiento["fecha_reporte"]))); ?></td>
								<td>
									<strong><?php echo htmlspecialchars($mantenimiento["codigo_inventario"]); ?></strong><br>
									<?php echo htmlspecialchars($mantenimiento["recurso"]); ?>
								</td>
								<td><?php echo htmlspecialchars($mantenimiento["tipo"] ?? "-"); ?></td>
								<td><?php echo htmlspecialchars($mantenimiento["descripcion"]); ?></td>
								<td>
									<span class="status-badge badge-regular">
										<?php echo htmlspecialchars($mantenimiento["estado"]); ?>
									</span>
								</td>
								<td><?php echo htmlspecialchars($mantenimiento["responsable"] ?? "-"); ?></td>
								<td>
									<a href="ver.php?id=<?php echo (int) $mantenimiento["id"]; ?>" class="btn btn-small">
										Ver detalle
									</a>
								</td>
							</tr>
						<?php endwhile; ?>
					<?php else: ?>
						<tr>
							<td colspan="7" class="empty-state">
								No hay mantenimientos registrados.
							</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</section>

</main>

<?php require_once "../includes/footer.php"; ?>
