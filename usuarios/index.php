<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

$titulo = "Usuarios";

$mensaje = "";
$tipo_mensaje = "";

if (isset($_GET["success"])) {
	$mensaje = "Usuario guardado correctamente.";
	$tipo_mensaje = "alert-success";
}

if (isset($_GET["delete"]) && isset($_GET["id"])) {
	$id = (int) $_GET["id"];
	$usuario_actual = (int) ($_SESSION["usuario_id"] ?? 0);

	if ($id === $usuario_actual) {
		$mensaje = "No puedes eliminar tu propio usuario actual.";
		$tipo_mensaje = "alert-error";
	} else {
		$sql_check = "
			SELECT COUNT(*) AS total
			FROM movimientos
			WHERE usuario_id = ?
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
				$mensaje = "No se puede eliminar el usuario porque ya tiene movimientos registrados.";
				$tipo_mensaje = "alert-error";
			} else {
				$sql_delete = "DELETE FROM usuarios WHERE id = ? LIMIT 1";
				$stmt = $conexion->prepare($sql_delete);

				if ($stmt) {
					$stmt->bind_param("i", $id);

					if ($stmt->execute()) {
						$mensaje = "Usuario eliminado correctamente.";
						$tipo_mensaje = "alert-success";
					} else {
						$mensaje = "No se pudo eliminar el usuario.";
						$tipo_mensaje = "alert-error";
					}

					$stmt->close();
				}
			}
		}
	}
}

$sql = "
	SELECT id, nombre, usuario, rol, estado, fecha_creacion
	FROM usuarios
	ORDER BY nombre ASC
";

$resultado = $conexion->query($sql);

if (!$resultado) {
	die("Error al consultar usuarios: " . $conexion->error);
}

require_once "../includes/header.php";
require_once "../includes/navbar.php";
?>

<main class="main-content">

	<div class="page-header">

		<div>
			<h1>Usuarios</h1>
			<p>Administra los accesos y permisos del sistema.</p>
		</div>

		<a href="crear.php" class="btn btn-primary">
			+ Nuevo usuario
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
						<th>Usuario</th>
						<th>Rol</th>
						<th>Estado</th>
						<th>Fecha</th>
						<th>Acciones</th>
					</tr>
				</thead>

				<tbody>

					<?php if ($resultado && $resultado->num_rows > 0): ?>

						<?php while ($usuario = $resultado->fetch_assoc()): ?>

							<tr>
								<td><?php echo (int) $usuario["id"]; ?></td>

								<td>
									<strong>
										<?php echo htmlspecialchars($usuario["nombre"]); ?>
									</strong>
								</td>

								<td><?php echo htmlspecialchars($usuario["usuario"]); ?></td>

								<td>
									<span class="status-badge badge-regular">
										<?php echo htmlspecialchars($usuario["rol"] ?? "CONSULTA"); ?>
									</span>
								</td>

								<td>
									<span class="status-badge <?php echo strtolower($usuario["estado"] ?? "activo") === "activo" ? "badge-bueno" : "badge-regular"; ?>">
										<?php echo htmlspecialchars($usuario["estado"] ?? "ACTIVO"); ?>
									</span>
								</td>

								<td>
									<?php echo htmlspecialchars(date("d/m/Y", strtotime($usuario["fecha_creacion"]))); ?>
								</td>

								<td>
									<div style="display: flex; gap: 8px; flex-wrap: wrap;">
										<a href="editar.php?id=<?php echo (int) $usuario["id"]; ?>" class="btn btn-small">
											Editar
										</a>

										<?php if ((int) $usuario["id"] !== (int) ($_SESSION["usuario_id"] ?? 0)): ?>
											<a
												href="index.php?delete=1&id=<?php echo (int) $usuario["id"]; ?>"
												class="btn btn-small btn-danger"
												onclick="return confirm('¿Deseas eliminar este usuario?');"
											>
												Eliminar
											</a>
										<?php endif; ?>
									</div>
								</td>
							</tr>

						<?php endwhile; ?>

					<?php else: ?>

						<tr>
							<td colspan="7" class="empty-state">
								No hay usuarios registrados.
							</td>
						</tr>

					<?php endif; ?>

				</tbody>

			</table>

		</div>

	</section>

</main>

<?php require_once "../includes/footer.php"; ?>
