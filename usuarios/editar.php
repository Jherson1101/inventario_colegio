<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

$titulo = "Editar usuario";

$id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if ($id <= 0) {
	header("Location: index.php");
	exit;
}

$sql = "
	SELECT id, nombre, usuario, rol, estado
	FROM usuarios
	WHERE id = ?
	LIMIT 1
";

$stmt = $conexion->prepare($sql);

if (!$stmt) {
	die("Error al preparar la consulta: " . $conexion->error);
}

$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows !== 1) {
	$stmt->close();
	header("Location: index.php");
	exit;
}

$usuario_actual = $resultado->fetch_assoc();
$stmt->close();

$nombre = $usuario_actual["nombre"];
$usuario = $usuario_actual["usuario"];
$rol = $usuario_actual["rol"];
$estado = $usuario_actual["estado"];

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
	$nombre = trim($_POST["nombre"] ?? "");
	$usuario = trim($_POST["usuario"] ?? "");
	$rol = $_POST["rol"] ?? "CONSULTA";
	$estado = $_POST["estado"] ?? "ACTIVO";
	$password = $_POST["password"] ?? "";
	$confirm_password = $_POST["confirm_password"] ?? "";

	if ($nombre === "") {
		$error = "El nombre es obligatorio.";
	} elseif ($usuario === "") {
		$error = "El nombre de usuario es obligatorio.";
	} elseif (!in_array($rol, ["ADMINISTRADOR", "ENCARGADO", "CONSULTA"], true)) {
		$error = "Seleccione un rol válido.";
	} elseif (!in_array($estado, ["ACTIVO", "INACTIVO"], true)) {
		$error = "Seleccione un estado válido.";
	} elseif ($password !== "" && strlen($password) < 6) {
		$error = "La contraseña debe tener al menos 6 caracteres.";
	} elseif ($password !== "" && $password !== $confirm_password) {
		$error = "Las contraseñas no coinciden.";
	} else {
		$sql = "
			SELECT id
			FROM usuarios
			WHERE usuario = ? AND id != ?
			LIMIT 1
		";

		$stmt = $conexion->prepare($sql);

		if (!$stmt) {
			die("Error al verificar el usuario: " . $conexion->error);
		}

		$stmt->bind_param("si", $usuario, $id);
		$stmt->execute();
		$duplicado = $stmt->get_result();
		$stmt->close();

		if ($duplicado->num_rows > 0) {
			$error = "Ya existe otro usuario con ese nombre de acceso.";
		} else {
			if ($password !== "") {
				$password_hash = password_hash($password, PASSWORD_DEFAULT);
				$sql = "
					UPDATE usuarios
					SET nombre = ?, usuario = ?, password = ?, rol = ?, estado = ?
					WHERE id = ?
				";

				$stmt = $conexion->prepare($sql);

				if (!$stmt) {
					die("Error al preparar la actualización: " . $conexion->error);
				}

				$stmt->bind_param("sssssi", $nombre, $usuario, $password_hash, $rol, $estado, $id);
			} else {
				$sql = "
					UPDATE usuarios
					SET nombre = ?, usuario = ?, rol = ?, estado = ?
					WHERE id = ?
				";

				$stmt = $conexion->prepare($sql);

				if (!$stmt) {
					die("Error al preparar la actualización: " . $conexion->error);
				}

				$stmt->bind_param("sssi", $nombre, $usuario, $rol, $estado, $id);
			}

			if ($stmt->execute()) {
				$stmt->close();
				header("Location: index.php?success=1");
				exit;
			}

			$error = "No se pudo actualizar el usuario.";
			$stmt->close();
		}
	}
}

require_once "../includes/header.php";
require_once "../includes/navbar.php";
?>

<main class="main-content">

	<div class="page-header">
		<div>
			<h1>Editar usuario</h1>
			<p>Actualiza la información y permisos del usuario seleccionado.</p>
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
					<label for="nombre">Nombre completo</label>
					<input
						type="text"
						id="nombre"
						name="nombre"
						class="form-control"
						value="<?php echo htmlspecialchars($nombre); ?>"
						required
					>
				</div>

				<div class="form-group">
					<label for="usuario">Usuario</label>
					<input
						type="text"
						id="usuario"
						name="usuario"
						class="form-control"
						value="<?php echo htmlspecialchars($usuario); ?>"
						required
					>
				</div>

				<div class="form-group">
					<label for="rol">Rol</label>
					<select id="rol" name="rol" class="form-control">
						<option value="ADMINISTRADOR" <?php echo $rol === "ADMINISTRADOR" ? "selected" : ""; ?>>ADMINISTRADOR</option>
						<option value="ENCARGADO" <?php echo $rol === "ENCARGADO" ? "selected" : ""; ?>>ENCARGADO</option>
						<option value="CONSULTA" <?php echo $rol === "CONSULTA" ? "selected" : ""; ?>>CONSULTA</option>
					</select>
				</div>

				<div class="form-group">
					<label for="estado">Estado</label>
					<select id="estado" name="estado" class="form-control">
						<option value="ACTIVO" <?php echo $estado === "ACTIVO" ? "selected" : ""; ?>>ACTIVO</option>
						<option value="INACTIVO" <?php echo $estado === "INACTIVO" ? "selected" : ""; ?>>INACTIVO</option>
					</select>
				</div>

				<div class="form-group">
					<label for="password">Nueva contraseña</label>
					<input
						type="password"
						id="password"
						name="password"
						class="form-control"
						placeholder="Dejar vacío para conservar la actual"
					>
				</div>

				<div class="form-group">
					<label for="confirm_password">Confirmar contraseña</label>
					<input
						type="password"
						id="confirm_password"
						name="confirm_password"
						class="form-control"
						placeholder="Repetir nueva contraseña"
					>
				</div>

			</div>

			<div class="form-actions">
				<a href="index.php" class="btn btn-secondary">Cancelar</a>
				<button type="submit" class="btn btn-primary">Guardar cambios</button>
			</div>

		</form>

	</section>

</main>

<?php require_once "../includes/footer.php"; ?>
