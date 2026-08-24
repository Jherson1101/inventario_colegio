<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

$titulo = "Nuevo mantenimiento";
$recurso_id = 0;
$tipo = "";
$descripcion = "";
$responsable = "";
$observaciones = "";
$costo = "";
$error = "";

$sql_recursos = "
	SELECT id, codigo_inventario, descripcion, situacion
	FROM recursos
	WHERE situacion <> 'DADO DE BAJA'
	ORDER BY codigo_inventario ASC
";

$recursos = $conexion->query($sql_recursos);

if (!$recursos) {
	die("Error al consultar recursos: " . $conexion->error);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
	$recurso_id = isset($_POST["recurso_id"]) ? (int) $_POST["recurso_id"] : 0;
	$tipo = trim($_POST["tipo"] ?? "");
	$descripcion = trim($_POST["descripcion"] ?? "");
	$responsable = trim($_POST["responsable"] ?? "");
	$observaciones = trim($_POST["observaciones"] ?? "");
	$costo = trim($_POST["costo"] ?? "");

	if ($recurso_id <= 0) {
		$error = "Seleccione un recurso.";
	} elseif ($descripcion === "") {
		$error = "La descripción del problema es obligatoria.";
	} elseif ($costo !== "" && (!is_numeric($costo) || (float) $costo < 0)) {
		$error = "Ingrese un costo válido.";
	} else {
		$conexion->begin_transaction();

		try {
			$sql = "
				INSERT INTO mantenimiento
					(recurso_id, tipo, descripcion, estado, responsable, costo, observaciones)
				VALUES (?, ?, ?, 'PENDIENTE', ?, NULLIF(?, ''), ?)
			";

			$stmt = $conexion->prepare($sql);

			if (!$stmt) {
				throw new Exception("No se pudo preparar el mantenimiento.");
			}

			$stmt->bind_param("isssss", $recurso_id, $tipo, $descripcion, $responsable, $costo, $observaciones);

			if (!$stmt->execute()) {
				throw new Exception("No se pudo guardar el mantenimiento.");
			}

			$stmt->close();

			$sql_recurso = "
				UPDATE recursos
				SET situacion = 'EN MANTENIMIENTO'
				WHERE id = ?
			";

			$stmt = $conexion->prepare($sql_recurso);

			if (!$stmt) {
				throw new Exception("No se pudo actualizar el recurso.");
			}

			$stmt->bind_param("i", $recurso_id);

			if (!$stmt->execute()) {
				throw new Exception("No se pudo actualizar la situación del recurso.");
			}

			$stmt->close();
			$conexion->commit();

			header("Location: index.php?success=1");
			exit;
		} catch (Exception $exception) {
			$conexion->rollback();
			$error = $exception->getMessage();
		}
	}
}

require_once "../includes/header.php";
require_once "../includes/navbar.php";
?>

<main class="main-content">

	<div class="page-header">
		<div>
			<h1>Nuevo mantenimiento</h1>
			<p>Registra una reparación o revisión para un recurso del inventario.</p>
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
				<div class="form-group form-group-full">
					<label for="recurso_id">Recurso</label>
					<select id="recurso_id" name="recurso_id" class="form-control" required>
						<option value="">Seleccione un recurso</option>
						<?php while ($recurso = $recursos->fetch_assoc()): ?>
							<option value="<?php echo (int) $recurso["id"]; ?>" <?php echo $recurso_id === (int) $recurso["id"] ? "selected" : ""; ?>>
								<?php echo htmlspecialchars($recurso["codigo_inventario"] . " - " . $recurso["descripcion"] . " (" . $recurso["situacion"] . ")"); ?>
							</option>
						<?php endwhile; ?>
					</select>
				</div>

				<div class="form-group">
					<label for="tipo">Tipo de mantenimiento</label>
					<input type="text" id="tipo" name="tipo" class="form-control" value="<?php echo htmlspecialchars($tipo); ?>" placeholder="Preventivo o correctivo">
				</div>

				<div class="form-group">
					<label for="responsable">Responsable</label>
					<input type="text" id="responsable" name="responsable" class="form-control" value="<?php echo htmlspecialchars($responsable); ?>">
				</div>

				<div class="form-group form-group-full">
					<label for="descripcion">Descripción del problema</label>
					<textarea id="descripcion" name="descripcion" class="form-control" rows="4" required><?php echo htmlspecialchars($descripcion); ?></textarea>
				</div>

				<div class="form-group">
					<label for="costo">Costo</label>
					<input type="number" id="costo" name="costo" class="form-control" min="0" step="0.01" value="<?php echo htmlspecialchars($costo); ?>">
				</div>

				<div class="form-group form-group-full">
					<label for="observaciones">Observaciones</label>
					<textarea id="observaciones" name="observaciones" class="form-control" rows="4"><?php echo htmlspecialchars($observaciones); ?></textarea>
				</div>
			</div>

			<div class="form-actions">
				<a href="index.php" class="btn btn-secondary">Cancelar</a>
				<button type="submit" class="btn btn-primary">Registrar mantenimiento</button>
			</div>
		</form>
	</section>

</main>

<?php require_once "../includes/footer.php"; ?>
