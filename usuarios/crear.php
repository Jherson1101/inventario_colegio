<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

$titulo = "Nuevo usuario";

$nombre = "";
$usuario = "";
$rol = "CONSULTA";
$estado = "ACTIVO";
$password = "";
$confirm_password = "";

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
	} elseif ($password === "") {
		$error = "La contraseña es obligatoria.";
	} elseif (strlen($password) < 6) {
		$error = "La contraseña debe tener al menos 6 caracteres.";
	} elseif ($password !== $confirm_password) {
		$error = "Las contraseñas no coinciden.";
	} else {
		$sql = "
			SELECT id
			FROM usuarios
			WHERE usuario = ?
			LIMIT 1
		";

		$stmt = $conexion->prepare($sql);

		if (!$stmt) {
			die("Error al verificar el usuario: " . $conexion->error);
		}

		$stmt->bind_param("s", $usuario);
		$stmt->execute();
		$resultado = $stmt->get_result();
		$stmt->close();

		if ($resultado->num_rows > 0) {
			$error = "Ya existe un usuario con ese nombre de acceso.";
		} else {
			$password_hash = password_hash($password, PASSWORD_DEFAULT);

			$sql = "
				INSERT INTO usuarios (nombre, usuario, password, rol, estado)
				VALUES (?, ?, ?, ?, ?)
			";

			$stmt = $conexion->prepare($sql);

			if (!$stmt) {
				die("Error al preparar la inserción: " . $conexion->error);
			}

			$stmt->bind_param("sssss", $nombre, $usuario, $password_hash, $rol, $estado);

			if ($stmt->execute()) {
				$stmt->close();
				header("Location: index.php?success=1");
				exit;
			}

			$error = "No se pudo guardar el usuario.";
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
			<h1>Nuevo usuario</h1>
			<p>Registra un acceso con permisos definidos para el sistema.</p>
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
					<label for="password">Contraseña</label>
					<input
						type="password"
						id="password"
						name="password"
						class="form-control"
						required
					>
				</div>

				<div class="form-group">
					<label for="confirm_password">Confirmar contraseña</label>
					<input
						type="password"
						id="confirm_password"
						name="confirm_password"
						class="form-control"
						required
					>
				</div>

			</div>

			<div class="form-actions">
				<a href="index.php" class="btn btn-secondary">Cancelar</a>
				<button type="submit" class="btn btn-primary">Guardar usuario</button>
			</div>

		</form>

	</section>

</main>

<?php require_once "../includes/footer.php"; ?>
