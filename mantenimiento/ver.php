<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

$titulo = "Detalle de mantenimiento";
$id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if ($id <= 0) {
	header("Location: index.php");
	exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
	$estado = $_POST["estado"] ?? "";
	$estados_validos = ["PENDIENTE", "EN PROCESO", "FINALIZADO", "CANCELADO"];

	if (!in_array($estado, $estados_validos, true)) {
		$error = "Seleccione un estado válido.";
	} else {
		$conexion->begin_transaction();

		try {
			$sql = "
				UPDATE mantenimiento
				SET estado = ?,
					fecha_inicio = CASE WHEN ? = 'EN PROCESO' AND fecha_inicio IS NULL THEN NOW() ELSE fecha_inicio END,
					fecha_fin = CASE WHEN ? IN ('FINALIZADO', 'CANCELADO') THEN NOW() ELSE NULL END
				WHERE id = ?
			";

			$stmt = $conexion->prepare($sql);

			if (!$stmt) {
				throw new Exception("No se pudo preparar la actualización.");
			}

			$stmt->bind_param("sssi", $estado, $estado, $estado, $id);

			if (!$stmt->execute()) {
				throw new Exception("No se pudo actualizar el mantenimiento.");
			}

			$stmt->close();

			$sql_recurso = "
				UPDATE recursos r
				INNER JOIN mantenimiento m ON m.recurso_id = r.id
				SET r.situacion = CASE
					WHEN ? IN ('PENDIENTE', 'EN PROCESO') THEN 'EN MANTENIMIENTO'
					ELSE 'DISPONIBLE'
				END
				WHERE m.id = ?
			";

			$stmt = $conexion->prepare($sql_recurso);

			if (!$stmt) {
				throw new Exception("No se pudo actualizar la situación del recurso.");
			}

			$stmt->bind_param("si", $estado, $id);

			if (!$stmt->execute()) {
				throw new Exception("No se pudo actualizar la situación del recurso.");
			}

			$stmt->close();
			$conexion->commit();

			header("Location: ver.php?id=" . $id . "&updated=1");
			exit;
		} catch (Exception $exception) {
			$conexion->rollback();
			$error = $exception->getMessage();
		}
	}
}

$sql = "
	SELECT
		m.*,
		r.codigo_inventario,
		r.descripcion AS recurso,
		r.situacion
	FROM mantenimiento m
	INNER JOIN recursos r ON r.id = m.recurso_id
	WHERE m.id = ?
	LIMIT 1
";

$stmt = $conexion->prepare($sql);

if (!$stmt) {
	die("Error al consultar el mantenimiento: " . $conexion->error);
}

$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows !== 1) {
	$stmt->close();
	header("Location: index.php");
	exit;
}

$mantenimiento = $resultado->fetch_assoc();
$stmt->close();

require_once "../includes/header.php";
require_once "../includes/navbar.php";
?>

<main class="main-content">

	<div class="page-header">
		<div>
			<h1>Detalle de mantenimiento</h1>
			<p><?php echo htmlspecialchars($mantenimiento["codigo_inventario"]); ?> - <?php echo htmlspecialchars($mantenimiento["recurso"]); ?></p>
		</div>
		<a href="index.php" class="btn btn-secondary">Volver</a>
	</div>

	<?php if ($error !== ""): ?>
		<div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
	<?php endif; ?>

	<?php if (isset($_GET["updated"])): ?>
		<div class="alert alert-success">Estado del mantenimiento actualizado correctamente.</div>
	<?php endif; ?>

	<section class="form-section">
		<div class="form-grid">
			<div class="form-group">
				<label>Fecha de reporte</label>
				<div class="form-control"><?php echo htmlspecialchars(date("d/m/Y H:i", strtotime($mantenimiento["fecha_reporte"]))); ?></div>
			</div>

			<div class="form-group">
				<label>Situación actual del recurso</label>
				<div class="form-control"><?php echo htmlspecialchars($mantenimiento["situacion"]); ?></div>
			</div>

			<div class="form-group">
				<label>Tipo</label>
				<div class="form-control"><?php echo htmlspecialchars($mantenimiento["tipo"] ?? "-"); ?></div>
			</div>

			<div class="form-group">
				<label>Responsable</label>
				<div class="form-control"><?php echo htmlspecialchars($mantenimiento["responsable"] ?? "-"); ?></div>
			</div>

			<div class="form-group form-group-full">
				<label>Descripción</label>
				<div class="form-control"><?php echo nl2br(htmlspecialchars($mantenimiento["descripcion"])); ?></div>
			</div>

			<div class="form-group">
				<label>Costo</label>
				<div class="form-control"><?php echo $mantenimiento["costo"] !== null ? htmlspecialchars(number_format((float) $mantenimiento["costo"], 2)) : "-"; ?></div>
			</div>

			<div class="form-group">
				<label>Fecha de inicio</label>
				<div class="form-control"><?php echo $mantenimiento["fecha_inicio"] ? htmlspecialchars(date("d/m/Y H:i", strtotime($mantenimiento["fecha_inicio"]))) : "-"; ?></div>
			</div>

			<div class="form-group form-group-full">
				<label>Observaciones</label>
				<div class="form-control"><?php echo $mantenimiento["observaciones"] ? nl2br(htmlspecialchars($mantenimiento["observaciones"])) : "-"; ?></div>
			</div>

			<div class="form-group form-group-full">
				<label for="estado">Actualizar estado</label>
				<form method="POST">
					<div style="display: flex; gap: 8px; flex-wrap: wrap;">
						<select id="estado" name="estado" class="form-control" required>
							<?php foreach (["PENDIENTE", "EN PROCESO", "FINALIZADO", "CANCELADO"] as $estado): ?>
								<option value="<?php echo $estado; ?>" <?php echo $mantenimiento["estado"] === $estado ? "selected" : ""; ?>><?php echo $estado; ?></option>
							<?php endforeach; ?>
						</select>
						<button type="submit" class="btn btn-primary">Actualizar estado</button>
					</div>
				</form>
			</div>
		</div>
	</section>

</main>

<?php require_once "../includes/footer.php"; ?>
