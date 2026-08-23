<?php
session_start();

require_once "../config/database.php";

$mensaje = "";
$error = "";

$usuario_id = $_SESSION["usuario_id"] ?? 1;

/*
|--------------------------------------------------------------------------
| CARGAR CATEGORÍAS
|--------------------------------------------------------------------------
*/
$sql = "SELECT id, nombre
        FROM categorias
        WHERE estado = 'ACTIVO'
        ORDER BY nombre";

$resultado_categorias = $conexion->query($sql);

if (!$resultado_categorias) {
    die("Error al cargar categorías: " . $conexion->error);
}

$categorias = [];

while ($fila = $resultado_categorias->fetch_assoc()) {
    $categorias[] = $fila;
}

/*
|--------------------------------------------------------------------------
| CARGAR UBICACIONES
|--------------------------------------------------------------------------
*/
$sql = "SELECT id, nombre
        FROM ubicaciones
        WHERE estado = 'ACTIVO'
        ORDER BY id";

$resultado_ubicaciones = $conexion->query($sql);

if (!$resultado_ubicaciones) {
    die("Error al cargar ubicaciones: " . $conexion->error);
}

$ubicaciones = [];

while ($fila = $resultado_ubicaciones->fetch_assoc()) {
    $ubicaciones[] = $fila;
}

/*
|--------------------------------------------------------------------------
| PROCESAR FORMULARIO
|--------------------------------------------------------------------------
*/
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $tipo_movimiento = trim($_POST["tipo_movimiento"] ?? "");
    $cantidad = intval($_POST["cantidad"] ?? 0);

    $recurso_id = intval($_POST["recurso_id"] ?? 0);

    $responsable = trim($_POST["responsable"] ?? "");
    $motivo = trim($_POST["motivo"] ?? "");
    $observaciones = trim($_POST["observaciones"] ?? "");

    // Datos para ENTRADA
    $codigo_inventario = trim($_POST["codigo_inventario"] ?? "");
    $categoria_id = intval($_POST["categoria_id"] ?? 0);
    $descripcion = trim($_POST["descripcion"] ?? "");
    $marca = trim($_POST["marca"] ?? "");
    $modelo = trim($_POST["modelo"] ?? "");
    $numero_serie = trim($_POST["numero_serie"] ?? "");
    $color = trim($_POST["color"] ?? "");
    $estado = trim($_POST["estado"] ?? "BUENO");
    $anio = intval($_POST["anio"] ?? date("Y"));
    $ubicacion_destino_id = intval($_POST["ubicacion_destino_id"] ?? 0);

    $tipos_validos = [
        "ENTRADA",
        "SALIDA",
        "PRESTAMO",
        "DEVOLUCION"
    ];

    /*
    |--------------------------------------------------------------------------
    | VALIDACIÓN GENERAL
    |--------------------------------------------------------------------------
    */
    if (!in_array($tipo_movimiento, $tipos_validos, true)) {

        $error = "Seleccione un tipo de movimiento válido.";

    } elseif ($cantidad <= 0) {

        $error = "La cantidad debe ser mayor que cero.";

    /*
    |--------------------------------------------------------------------------
    | ENTRADA
    |--------------------------------------------------------------------------
    |
    | Un recurso nuevo llega al colegio desde el Estado/proveedor.
    | La ubicación seleccionada será tanto la ubicación actual como
    | la ubicación habitual del nuevo recurso.
    |--------------------------------------------------------------------------
    */
    } elseif ($tipo_movimiento === "ENTRADA") {

        if ($codigo_inventario === "") {

            $error = "Ingrese el código de inventario.";

        } elseif ($categoria_id <= 0) {

            $error = "Seleccione una categoría.";

        } elseif ($descripcion === "") {

            $error = "Ingrese una descripción del recurso.";

        } elseif ($ubicacion_destino_id <= 0) {

            $error = "Seleccione dónde quedará el nuevo recurso.";

        } elseif (!in_array($estado, [
            "EXCELENTE",
            "BUENO",
            "REGULAR",
            "DEFICIENTE",
            "MALOGRADO"
        ], true)) {

            $error = "Seleccione un estado válido.";

        } else {

            $conexion->begin_transaction();

            try {

                // Verificar código
                $sql = "SELECT id
                        FROM recursos
                        WHERE codigo_inventario = ?
                        LIMIT 1";

                $stmt = $conexion->prepare($sql);

                if (!$stmt) {
                    throw new Exception(
                        "Error al verificar el código: " . $conexion->error
                    );
                }

                $stmt->bind_param("s", $codigo_inventario);
                $stmt->execute();

                $resultado = $stmt->get_result();
                $existe = $resultado->num_rows > 0;

                $stmt->close();

                if ($existe) {
                    throw new Exception(
                        "Ya existe un recurso con el código de inventario '$codigo_inventario'."
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | CREAR RECURSO
                |--------------------------------------------------------------------------
                |
                | ubicacion_id = dónde queda ahora
                | ubicacion_habitual_id = dónde debe regresar normalmente
                |--------------------------------------------------------------------------
                */
                $sql = "INSERT INTO recursos (
                            codigo_inventario,
                            categoria_id,
                            area_id,
                            ubicacion_id,
                            ubicacion_habitual_id,
                            descripcion,
                            marca,
                            modelo,
                            numero_serie,
                            color,
                            situacion,
                            estado,
                            anio,
                            cantidad,
                            observaciones
                        )
                        VALUES (
                            ?, ?, NULL, ?, ?,
                            ?, ?, ?, ?, ?,
                            'DISPONIBLE',
                            ?, ?, ?, ?
                        )";

                $stmt = $conexion->prepare($sql);

                if (!$stmt) {
                    throw new Exception(
                        "Error al preparar el nuevo recurso: " . $conexion->error
                    );
                }

                $stmt->bind_param(
                    "siiissssssiis",
                    $codigo_inventario,
                    $categoria_id,
                    $ubicacion_destino_id,
                    $ubicacion_destino_id,
                    $descripcion,
                    $marca,
                    $modelo,
                    $numero_serie,
                    $color,
                    $estado,
                    $anio,
                    $cantidad,
                    $observaciones
                );

                if (!$stmt->execute()) {
                    throw new Exception(
                        "Error al registrar el nuevo recurso: " . $stmt->error
                    );
                }

                $recurso_id_nuevo = $conexion->insert_id;
                $stmt->close();

                /*
                |--------------------------------------------------------------------------
                | REGISTRAR ENTRADA
                |--------------------------------------------------------------------------
                |
                | El origen es NULL porque el Estado/proveedor no es una
                | ubicación interna del colegio.
                |--------------------------------------------------------------------------
                */
                $sql = "INSERT INTO movimientos (
                            recurso_id,
                            tipo_movimiento,
                            cantidad,
                            fecha_hora,
                            usuario_id,
                            responsable,
                            ubicacion_origen_id,
                            ubicacion_destino_id,
                            motivo,
                            observaciones
                        )
                        VALUES (
                            ?, 'ENTRADA', ?, NOW(), ?,
                            ?, NULL, ?, ?, ?
                        )";

                $stmt = $conexion->prepare($sql);

                if (!$stmt) {
                    throw new Exception(
                        "Error al preparar la entrada: " . $conexion->error
                    );
                }

                $stmt->bind_param(
                    "iiisiss",
                    $recurso_id_nuevo,
                    $cantidad,
                    $usuario_id,
                    $responsable,
                    $ubicacion_destino_id,
                    $motivo,
                    $observaciones
                );

                if (!$stmt->execute()) {
                    throw new Exception(
                        "Error al registrar la entrada: " . $stmt->error
                    );
                }

                $stmt->close();

                $conexion->commit();

                $mensaje = "Entrada registrada correctamente. El nuevo recurso fue agregado al inventario.";

            } catch (Throwable $e) {

                $conexion->rollback();
                $error = $e->getMessage();
            }
        }

    /*
    |--------------------------------------------------------------------------
    | SALIDA
    |--------------------------------------------------------------------------
    |
    | El recurso deja de estar disponible por malogro, baja, etc.
    |--------------------------------------------------------------------------
    */
    } elseif ($tipo_movimiento === "SALIDA") {

        if ($recurso_id <= 0) {

            $error = "Seleccione el recurso que será dado de salida.";

        } else {

            $conexion->begin_transaction();

            try {

                $sql = "SELECT
                            id,
                            cantidad,
                            ubicacion_id,
                            situacion
                        FROM recursos
                        WHERE id = ?
                        LIMIT 1
                        FOR UPDATE";

                $stmt = $conexion->prepare($sql);

                if (!$stmt) {
                    throw new Exception(
                        "Error al consultar el recurso: " . $conexion->error
                    );
                }

                $stmt->bind_param("i", $recurso_id);
                $stmt->execute();

                $resultado = $stmt->get_result();
                $recurso = $resultado->fetch_assoc();

                $stmt->close();

                if (!$recurso) {

                    throw new Exception("El recurso seleccionado no existe.");

                }

                if ($recurso["situacion"] !== "DISPONIBLE") {

                    throw new Exception(
                        "Solo se puede dar salida a un recurso que esté disponible."
                    );

                }

                if ($cantidad > $recurso["cantidad"]) {

                    throw new Exception(
                        "La cantidad de salida no puede ser mayor que la cantidad disponible."
                    );

                }

                $ubicacion_origen_id = intval($recurso["ubicacion_id"]);
                $nueva_cantidad = intval($recurso["cantidad"]) - $cantidad;

                /*
                | Si sale todo el recurso, pasa a DADO DE BAJA.
                | Si todavía quedan unidades, permanece DISPONIBLE.
                */
                $nueva_situacion = ($nueva_cantidad === 0)
                    ? "DADO DE BAJA"
                    : "DISPONIBLE";

                $sql = "UPDATE recursos
                        SET cantidad = ?,
                            situacion = ?,
                            estado = CASE WHEN ? = 'DADO DE BAJA' THEN 'MALOGRADO' ELSE estado END
                        WHERE id = ?";

                $stmt = $conexion->prepare($sql);

                if (!$stmt) {
                    throw new Exception(
                        "Error al actualizar el recurso: " . $conexion->error
                    );
                }

                $stmt->bind_param(
                    "isis",
                    $nueva_cantidad,
                    $nueva_situacion,
                    $nueva_situacion,
                    $recurso_id
                );

                if (!$stmt->execute()) {
                    throw new Exception(
                        "Error al actualizar el recurso: " . $stmt->error
                    );
                }

                $stmt->close();

                $sql = "INSERT INTO movimientos (
                            recurso_id,
                            tipo_movimiento,
                            cantidad,
                            fecha_hora,
                            usuario_id,
                            responsable,
                            ubicacion_origen_id,
                            ubicacion_destino_id,
                            motivo,
                            observaciones
                        )
                        VALUES (
                            ?, 'SALIDA', ?, NOW(), ?,
                            ?, ?, NULL, ?, ?
                        )";

                $stmt = $conexion->prepare($sql);

                if (!$stmt) {
                    throw new Exception(
                        "Error al preparar la salida: " . $conexion->error
                    );
                }

                $stmt->bind_param(
                    "iiisiss",
                    $recurso_id,
                    $cantidad,
                    $usuario_id,
                    $responsable,
                    $ubicacion_origen_id,
                    $motivo,
                    $observaciones
                );

                if (!$stmt->execute()) {
                    throw new Exception(
                        "Error al registrar la salida: " . $stmt->error
                    );
                }

                $stmt->close();

                $conexion->commit();

                $mensaje = "Salida registrada correctamente.";

            } catch (Throwable $e) {

                $conexion->rollback();
                $error = $e->getMessage();
            }
        }

    /*
    |--------------------------------------------------------------------------
    | PRÉSTAMO
    |--------------------------------------------------------------------------
    |
    | El origen se obtiene automáticamente de la ubicación actual.
    | El usuario solamente selecciona el destino.
    |--------------------------------------------------------------------------
    */
    } elseif ($tipo_movimiento === "PRESTAMO") {

        if ($recurso_id <= 0) {

            $error = "Seleccione el recurso que desea prestar.";

        } elseif ($ubicacion_destino_id <= 0) {

            $error = "Seleccione el salón o lugar donde será utilizado el recurso.";

        } else {

            $conexion->begin_transaction();

            try {

                $sql = "SELECT
                            id,
                            cantidad,
                            ubicacion_id,
                            ubicacion_habitual_id,
                            situacion
                        FROM recursos
                        WHERE id = ?
                        LIMIT 1
                        FOR UPDATE";

                $stmt = $conexion->prepare($sql);

                if (!$stmt) {
                    throw new Exception(
                        "Error al consultar el recurso: " . $conexion->error
                    );
                }

                $stmt->bind_param("i", $recurso_id);
                $stmt->execute();

                $resultado = $stmt->get_result();
                $recurso = $resultado->fetch_assoc();

                $stmt->close();

                if (!$recurso) {

                    throw new Exception("El recurso seleccionado no existe.");

                }

                if ($recurso["situacion"] !== "DISPONIBLE") {

                    throw new Exception(
                        "El recurso seleccionado no está disponible para préstamo."
                    );

                }

                if ($cantidad > $recurso["cantidad"]) {

                    throw new Exception(
                        "La cantidad a prestar no puede superar la cantidad disponible."
                    );

                }

                $ubicacion_origen_id = intval($recurso["ubicacion_id"]);

                if ($ubicacion_origen_id === $ubicacion_destino_id) {

                    throw new Exception(
                        "El destino del préstamo debe ser diferente a la ubicación actual del recurso."
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | ACTUALIZAR UBICACIÓN ACTUAL
                |--------------------------------------------------------------------------
                |
                | La ubicación habitual NO se modifica.
                |--------------------------------------------------------------------------
                */
                $sql = "UPDATE recursos
                        SET ubicacion_id = ?,
                            situacion = 'PRESTADO'
                        WHERE id = ?";

                $stmt = $conexion->prepare($sql);

                if (!$stmt) {
                    throw new Exception(
                        "Error al preparar la actualización del préstamo: " . $conexion->error
                    );
                }

                $stmt->bind_param(
                    "ii",
                    $ubicacion_destino_id,
                    $recurso_id
                );

                if (!$stmt->execute()) {
                    throw new Exception(
                        "Error al actualizar el recurso: " . $stmt->error
                    );
                }

                $stmt->close();

                /*
                |--------------------------------------------------------------------------
                | REGISTRAR PRÉSTAMO
                |--------------------------------------------------------------------------
                */
                $sql = "INSERT INTO movimientos (
                            recurso_id,
                            tipo_movimiento,
                            cantidad,
                            fecha_hora,
                            usuario_id,
                            responsable,
                            ubicacion_origen_id,
                            ubicacion_destino_id,
                            motivo,
                            observaciones
                        )
                        VALUES (
                            ?, 'PRESTAMO', ?, NOW(), ?,
                            ?, ?, ?, ?, ?
                        )";

                $stmt = $conexion->prepare($sql);

                if (!$stmt) {
                    throw new Exception(
                        "Error al preparar el préstamo: " . $conexion->error
                    );
                }

                $stmt->bind_param(
                    "iiisiiss",
                    $recurso_id,
                    $cantidad,
                    $usuario_id,
                    $responsable,
                    $ubicacion_origen_id,
                    $ubicacion_destino_id,
                    $motivo,
                    $observaciones
                );

                if (!$stmt->execute()) {
                    throw new Exception(
                        "Error al registrar el préstamo: " . $stmt->error
                    );
                }

                $stmt->close();

                $conexion->commit();

                $mensaje = "Préstamo registrado correctamente.";

            } catch (Throwable $e) {

                $conexion->rollback();
                $error = $e->getMessage();
            }
        }

    /*
    |--------------------------------------------------------------------------
    | DEVOLUCIÓN
    |--------------------------------------------------------------------------
    |
    | El origen es la ubicación actual.
    | El destino se obtiene automáticamente de ubicacion_habitual_id.
    |--------------------------------------------------------------------------
    */
    } elseif ($tipo_movimiento === "DEVOLUCION") {

        if ($recurso_id <= 0) {

            $error = "Seleccione el recurso que desea devolver.";

        } else {

            $conexion->begin_transaction();

            try {

                $sql = "SELECT
                            r.id,
                            r.cantidad,
                            r.ubicacion_id,
                            r.ubicacion_habitual_id,
                            r.situacion,
                            u.nombre AS ubicacion_habitual
                        FROM recursos r
                        LEFT JOIN ubicaciones u
                            ON r.ubicacion_habitual_id = u.id
                        WHERE r.id = ?
                        LIMIT 1
                        FOR UPDATE";

                $stmt = $conexion->prepare($sql);

                if (!$stmt) {
                    throw new Exception(
                        "Error al consultar el recurso: " . $conexion->error
                    );
                }

                $stmt->bind_param("i", $recurso_id);
                $stmt->execute();

                $resultado = $stmt->get_result();
                $recurso = $resultado->fetch_assoc();

                $stmt->close();

                if (!$recurso) {

                    throw new Exception("El recurso seleccionado no existe.");

                }

                if ($recurso["situacion"] !== "PRESTADO") {

                    throw new Exception(
                        "Este recurso no aparece actualmente como prestado."
                    );

                }

                if (empty($recurso["ubicacion_habitual_id"])) {

                    throw new Exception(
                        "El recurso no tiene registrada una ubicación habitual."
                    );

                }

                $ubicacion_origen_id = intval($recurso["ubicacion_id"]);
                $ubicacion_destino_id = intval($recurso["ubicacion_habitual_id"]);

                /*
                |--------------------------------------------------------------------------
                | DEVOLVER AL LUGAR HABITUAL
                |--------------------------------------------------------------------------
                */
                $sql = "UPDATE recursos
                        SET ubicacion_id = ?,
                            situacion = 'DISPONIBLE'
                        WHERE id = ?";

                $stmt = $conexion->prepare($sql);

                if (!$stmt) {
                    throw new Exception(
                        "Error al preparar la devolución: " . $conexion->error
                    );
                }

                $stmt->bind_param(
                    "ii",
                    $ubicacion_destino_id,
                    $recurso_id
                );

                if (!$stmt->execute()) {
                    throw new Exception(
                        "Error al devolver el recurso: " . $stmt->error
                    );
                }

                $stmt->close();

                /*
                |--------------------------------------------------------------------------
                | REGISTRAR DEVOLUCIÓN
                |--------------------------------------------------------------------------
                */
                $sql = "INSERT INTO movimientos (
                            recurso_id,
                            tipo_movimiento,
                            cantidad,
                            fecha_hora,
                            usuario_id,
                            responsable,
                            ubicacion_origen_id,
                            ubicacion_destino_id,
                            motivo,
                            observaciones
                        )
                        VALUES (
                            ?, 'DEVOLUCION', ?, NOW(), ?,
                            ?, ?, ?, ?, ?
                        )";

                $stmt = $conexion->prepare($sql);

                if (!$stmt) {
                    throw new Exception(
                        "Error al preparar la devolución: " . $conexion->error
                    );
                }

                $stmt->bind_param(
                    "iiisiiss",
                    $recurso_id,
                    $cantidad,
                    $usuario_id,
                    $responsable,
                    $ubicacion_origen_id,
                    $ubicacion_destino_id,
                    $motivo,
                    $observaciones
                );

                if (!$stmt->execute()) {
                    throw new Exception(
                        "Error al registrar la devolución: " . $stmt->error
                    );
                }

                $stmt->close();

                $conexion->commit();

                $nombre_destino = $recurso["ubicacion_habitual"]
                    ?: "su ubicación habitual";

                $mensaje = "Devolución registrada correctamente. El recurso regresó a " .
                           $nombre_destino . ".";

            } catch (Throwable $e) {

                $conexion->rollback();
                $error = $e->getMessage();
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| CARGAR RECURSOS DISPONIBLES
|--------------------------------------------------------------------------
|
| Se usan para SALIDA y PRÉSTAMO.
|--------------------------------------------------------------------------
*/
$recursos_disponibles = [];

$sql = "SELECT
            r.id,
            r.codigo_inventario,
            r.descripcion,
            r.cantidad,
            r.situacion,
            u.nombre AS ubicacion
        FROM recursos r
        LEFT JOIN ubicaciones u
            ON r.ubicacion_id = u.id
        WHERE r.situacion = 'DISPONIBLE'
          AND r.cantidad > 0
          AND r.estado <> 'MALOGRADO'
        ORDER BY r.codigo_inventario";

$resultado_recursos = $conexion->query($sql);

if ($resultado_recursos) {

    while ($fila = $resultado_recursos->fetch_assoc()) {
        $recursos_disponibles[] = $fila;
    }
}

/*
|--------------------------------------------------------------------------
| CARGAR RECURSOS PRESTADOS
|--------------------------------------------------------------------------
*/
$recursos_prestados = [];

$sql = "SELECT
            r.id,
            r.codigo_inventario,
            r.descripcion,
            r.cantidad,
            r.situacion,
            u.nombre AS ubicacion,
            uh.nombre AS ubicacion_habitual
        FROM recursos r
        LEFT JOIN ubicaciones u
            ON r.ubicacion_id = u.id
        LEFT JOIN ubicaciones uh
            ON r.ubicacion_habitual_id = uh.id
        WHERE r.situacion = 'PRESTADO'
          AND r.estado <> 'MALOGRADO'
        ORDER BY r.codigo_inventario";

$resultado_prestados = $conexion->query($sql);

if ($resultado_prestados) {

    while ($fila = $resultado_prestados->fetch_assoc()) {
        $recursos_prestados[] = $fila;
    }
}

?>

<?php require_once "../includes/header.php"; ?>

<div class="page-container">

    <div class="page-header">

        <div>
            <h1>Registrar movimiento</h1>

            <p>
                Registra una entrada, salida, préstamo o devolución.
            </p>
        </div>

        <a href="index.php" class="btn">
            ← Volver
        </a>

    </div>


    <?php if ($mensaje): ?>

        <div class="alert alert-success">
            <?= htmlspecialchars($mensaje) ?>
        </div>

    <?php endif; ?>


    <?php if ($error): ?>

        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>


    <form method="POST" action="crear.php" id="formMovimiento">

        <!-- =========================================================
             TIPO DE MOVIMIENTO
        ========================================================== -->

        <div class="card">

            <h2>Tipo de movimiento</h2>

            <div class="form-grid">

                <div class="form-group">

                    <label for="tipo_movimiento">
                        Tipo de movimiento *
                    </label>

                    <select
                        name="tipo_movimiento"
                        id="tipo_movimiento"
                        required
                    >

                        <option value="">
                            Seleccionar
                        </option>

                        <option value="ENTRADA">
                            Entrada
                        </option>

                        <option value="SALIDA">
                            Salida
                        </option>

                        <option value="PRESTAMO">
                            Préstamo
                        </option>

                        <option value="DEVOLUCION">
                            Devolución
                        </option>

                    </select>

                </div>

                <div
                    class="form-group"
                    id="grupoCantidad"
                >

                    <label for="cantidad">
                        Cantidad *
                    </label>

                    <input
                        type="number"
                        name="cantidad"
                        id="cantidad"
                        value="1"
                        min="1"
                        required
                    >

                </div>

            </div>

        </div>


        <!-- =========================================================
             ENTRADA: NUEVO RECURSO
        ========================================================== -->

        <div
            class="card"
            id="seccionEntrada"
            style="display:none;"
        >

            <h2>Nuevo recurso</h2>

            <p>
                La entrada representa un recurso nuevo que ingresa
                al colegio.
            </p>

            <div class="form-grid">

                <div class="form-group">

                    <label for="codigo_inventario">
                        Código de inventario *
                    </label>

                    <input
                        type="text"
                        name="codigo_inventario"
                        id="codigo_inventario"
                        placeholder="Ej. LAP-001"
                    >

                </div>


                <div class="form-group">

                    <label for="categoria_id">
                        Categoría *
                    </label>

                    <select
                        name="categoria_id"
                        id="categoria_id"
                    >

                        <option value="">
                            Seleccionar categoría
                        </option>

                        <?php foreach ($categorias as $categoria): ?>

                            <option value="<?= (int)$categoria["id"] ?>">
                                <?= htmlspecialchars($categoria["nombre"]) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label for="descripcion">
                        Descripción *
                    </label>

                    <input
                        type="text"
                        name="descripcion"
                        id="descripcion"
                        placeholder="Ej. Laptop para uso educativo"
                    >

                </div>


                <div class="form-group">

                    <label for="marca">
                        Marca
                    </label>

                    <input
                        type="text"
                        name="marca"
                        id="marca"
                    >

                </div>


                <div class="form-group">

                    <label for="modelo">
                        Modelo
                    </label>

                    <input
                        type="text"
                        name="modelo"
                        id="modelo"
                    >

                </div>


                <div class="form-group">

                    <label for="numero_serie">
                        Número de serie
                    </label>

                    <input
                        type="text"
                        name="numero_serie"
                        id="numero_serie"
                    >

                </div>


                <div class="form-group">

                    <label for="color">
                        Color
                    </label>

                    <input
                        type="text"
                        name="color"
                        id="color"
                    >

                </div>


                <div class="form-group">

                    <label for="estado">
                        Estado
                    </label>

                    <select
                        name="estado"
                        id="estado"
                    >

                        <option value="EXCELENTE">
                            Excelente
                        </option>

                        <option value="BUENO" selected>
                            Bueno
                        </option>

                        <option value="REGULAR">
                            Regular
                        </option>

                        <option value="DEFICIENTE">
                            Deficiente
                        </option>

                        <option value="MALOGRADO">
                            Malogrado
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label for="anio">
                        Año
                    </label>

                    <input
                        type="number"
                        name="anio"
                        id="anio"
                        value="<?= date("Y") ?>"
                        min="2000"
                        max="2100"
                    >

                </div>

            </div>

        </div>


        <!-- =========================================================
             RECURSO PARA SALIDA / PRÉSTAMO
        ========================================================== -->

        <div
            class="card"
            id="seccionRecursoDisponible"
            style="display:none;"
        >

            <h2>Recurso</h2>

            <div class="form-group">

                <label for="recurso_disponible_id">
                    Recurso *
                </label>

                <select
                    id="recurso_disponible_id"
                >

                    <option value="">
                        Seleccionar recurso
                    </option>

                    <?php foreach ($recursos_disponibles as $recurso): ?>

                        <option
                            value="<?= (int)$recurso["id"] ?>"
                            data-ubicacion="<?= htmlspecialchars($recurso["ubicacion"] ?? "") ?>"
                            data-cantidad="<?= (int)$recurso["cantidad"] ?>"
                        >

                            <?= htmlspecialchars($recurso["codigo_inventario"]) ?>
                            -
                            <?= htmlspecialchars($recurso["descripcion"]) ?>
                            | Disponible: <?= (int)$recurso["cantidad"] ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

        </div>


        <!-- =========================================================
             RECURSO PARA DEVOLUCIÓN
        ========================================================== -->

        <div
            class="card"
            id="seccionRecursoDevolucion"
            style="display:none;"
        >

            <h2>Recurso</h2>

            <div class="form-group">

                <label for="recurso_devolucion_id">
                    Recurso prestado *
                </label>

                <select
                    id="recurso_devolucion_id"
                >

                    <option value="">
                        Seleccionar recurso prestado
                    </option>

                    <?php foreach ($recursos_prestados as $recurso): ?>

                        <option
                            value="<?= (int)$recurso["id"] ?>"
                            data-ubicacion="<?= htmlspecialchars($recurso["ubicacion"] ?? "") ?>"
                            data-habitual="<?= htmlspecialchars($recurso["ubicacion_habitual"] ?? "") ?>"
                            data-cantidad="<?= (int)$recurso["cantidad"] ?>"
                        >

                            <?= htmlspecialchars($recurso["codigo_inventario"]) ?>
                            -
                            <?= htmlspecialchars($recurso["descripcion"]) ?>
                            | En: <?= htmlspecialchars($recurso["ubicacion"] ?? "Sin ubicación") ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

        </div>


        <!-- =========================================================
             RECURSO REAL ENVIADO AL SERVIDOR
        ========================================================== -->

        <input
            type="hidden"
            name="recurso_id"
            id="recurso_id"
            value=""
        >


        <!-- =========================================================
             INFORMACIÓN DEL RECURSO
        ========================================================== -->

        <div
            class="card"
            id="seccionInformacionRecurso"
            style="display:none;"
        >

            <h2>Información del recurso</h2>

            <div class="form-grid">

                <div class="form-group">

                    <label>
                        Ubicación actual
                    </label>

                    <input
                        type="text"
                        id="ubicacionActual"
                        readonly
                    >

                </div>


                <div
                    class="form-group"
                    id="grupoUbicacionHabitual"
                >

                    <label>
                        Ubicación habitual
                    </label>

                    <input
                        type="text"
                        id="ubicacionHabitual"
                        readonly
                    >

                </div>

            </div>

        </div>


        <!-- =========================================================
             UBICACIÓN DE DESTINO
        ========================================================== -->

        <div
            class="card"
            id="seccionDestino"
            style="display:none;"
        >

            <h2>Ubicación</h2>

            <div class="form-group">

                <label for="ubicacion_destino_id">
                    Ubicación de destino *
                </label>

                <select
                    name="ubicacion_destino_id"
                    id="ubicacion_destino_id"
                >

                    <option value="">
                        Seleccionar ubicación
                    </option>

                    <?php foreach ($ubicaciones as $ubicacion): ?>

                        <option value="<?= (int)$ubicacion["id"] ?>">
                            <?= htmlspecialchars($ubicacion["nombre"]) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

        </div>


        <!-- =========================================================
             INFORMACIÓN DE DEVOLUCIÓN
        ========================================================== -->

        <div
            class="card"
            id="seccionDevolucionUbicacion"
            style="display:none;"
        >

            <h2>Devolución</h2>

            <div class="form-grid">

                <div class="form-group">

                    <label>
                        Origen
                    </label>

                    <input
                        type="text"
                        id="devolucionOrigen"
                        readonly
                    >

                </div>


                <div class="form-group">

                    <label>
                        Regresará a
                    </label>

                    <input
                        type="text"
                        id="devolucionDestino"
                        readonly
                    >

                </div>

            </div>

            <p>
                El sistema devolverá automáticamente el recurso a su
                ubicación habitual.
            </p>

        </div>


        <!-- =========================================================
             INFORMACIÓN ADICIONAL
        ========================================================== -->

        <div
            class="card"
            id="seccionDatos"
            style="display:none;"
        >

            <h2>Información adicional</h2>

            <div class="form-grid">

                <div class="form-group">

                    <label for="responsable">
                        Responsable
                    </label>

                    <input
                        type="text"
                        name="responsable"
                        id="responsable"
                        placeholder="Nombre del responsable"
                    >

                </div>


                <div class="form-group">

                    <label for="motivo">
                        Motivo
                    </label>

                    <input
                        type="text"
                        name="motivo"
                        id="motivo"
                        placeholder="Motivo del movimiento"
                    >

                </div>

            </div>


            <div class="form-group">

                <label for="observaciones">
                    Observaciones
                </label>

                <textarea
                    name="observaciones"
                    id="observaciones"
                    rows="4"
                    placeholder="Observaciones adicionales..."
                ></textarea>

            </div>

        </div>


        <!-- =========================================================
             BOTÓN
        ========================================================== -->

        <div
            class="form-actions"
            id="seccionBoton"
            style="display:none;"
        >

            <button
                type="submit"
                class="btn btn-primary"
            >
                Registrar movimiento
            </button>

        </div>

    </form>

</div>


<script>
document.addEventListener("DOMContentLoaded", function () {

    const tipo = document.getElementById("tipo_movimiento");
    const cantidad = document.getElementById("cantidad");

    const seccionEntrada =
        document.getElementById("seccionEntrada");

    const seccionRecursoDisponible =
        document.getElementById("seccionRecursoDisponible");

    const seccionRecursoDevolucion =
        document.getElementById("seccionRecursoDevolucion");

    const seccionInformacionRecurso =
        document.getElementById("seccionInformacionRecurso");

    const seccionDestino =
        document.getElementById("seccionDestino");

    const seccionDevolucionUbicacion =
        document.getElementById("seccionDevolucionUbicacion");

    const seccionDatos =
        document.getElementById("seccionDatos");

    const seccionBoton =
        document.getElementById("seccionBoton");

    const recursoDisponible =
        document.getElementById("recurso_disponible_id");

    const recursoDevolucion =
        document.getElementById("recurso_devolucion_id");

    const recursoId =
        document.getElementById("recurso_id");

    const ubicacionActual =
        document.getElementById("ubicacionActual");

    const ubicacionHabitual =
        document.getElementById("ubicacionHabitual");

    const devolucionOrigen =
        document.getElementById("devolucionOrigen");

    const devolucionDestino =
        document.getElementById("devolucionDestino");


    function ocultarTodo() {

        seccionEntrada.style.display = "none";
        seccionRecursoDisponible.style.display = "none";
        seccionRecursoDevolucion.style.display = "none";
        seccionInformacionRecurso.style.display = "none";
        seccionDestino.style.display = "none";
        seccionDevolucionUbicacion.style.display = "none";
        seccionDatos.style.display = "none";
        seccionBoton.style.display = "none";

        recursoId.value = "";

        ubicacionActual.value = "";
        ubicacionHabitual.value = "";
        devolucionOrigen.value = "";
        devolucionDestino.value = "";

    }


    function mostrarDatosGenerales() {
        seccionDatos.style.display = "block";
        seccionBoton.style.display = "block";
    }


    /*
    |--------------------------------------------------------------------------
    | CAMBIO DE TIPO
    |--------------------------------------------------------------------------
    */
    tipo.addEventListener("change", function () {

        const valor = this.value;

        ocultarTodo();

        if (!valor) {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | ENTRADA
        |--------------------------------------------------------------------------
        */
        if (valor === "ENTRADA") {

            seccionEntrada.style.display = "block";
            seccionDestino.style.display = "block";

            mostrarDatosGenerales();

            cantidad.value = 1;

        }


        /*
        |--------------------------------------------------------------------------
        | SALIDA
        |--------------------------------------------------------------------------
        */
        else if (valor === "SALIDA") {

            seccionRecursoDisponible.style.display = "block";
            seccionInformacionRecurso.style.display = "block";

            mostrarDatosGenerales();

        }


        /*
        |--------------------------------------------------------------------------
        | PRÉSTAMO
        |--------------------------------------------------------------------------
        */
        else if (valor === "PRESTAMO") {

            seccionRecursoDisponible.style.display = "block";
            seccionInformacionRecurso.style.display = "block";
            seccionDestino.style.display = "block";

            mostrarDatosGenerales();

        }


        /*
        |--------------------------------------------------------------------------
        | DEVOLUCIÓN
        |--------------------------------------------------------------------------
        */
        else if (valor === "DEVOLUCION") {

            seccionRecursoDevolucion.style.display = "block";
            seccionInformacionRecurso.style.display = "block";
            seccionDevolucionUbicacion.style.display = "block";

            mostrarDatosGenerales();

        }

    });


    /*
    |--------------------------------------------------------------------------
    | RECURSO DISPONIBLE
    |--------------------------------------------------------------------------
    */
    recursoDisponible.addEventListener("change", function () {

        const opcion =
            this.options[this.selectedIndex];

        if (!this.value) {

            recursoId.value = "";
            seccionInformacionRecurso.style.display = "none";

            return;
        }

        recursoId.value = this.value;

        ubicacionActual.value =
            opcion.dataset.ubicacion || "No registrada";

        ubicacionHabitual.value =
            "Se conserva la ubicación habitual";

        const maxCantidad =
            parseInt(opcion.dataset.cantidad || "1", 10);

        cantidad.max = maxCantidad;

        if (parseInt(cantidad.value, 10) > maxCantidad) {
            cantidad.value = maxCantidad;
        }

        seccionInformacionRecurso.style.display = "block";
    });


    /*
    |--------------------------------------------------------------------------
    | RECURSO PRESTADO
    |--------------------------------------------------------------------------
    */
    recursoDevolucion.addEventListener("change", function () {

        const opcion =
            this.options[this.selectedIndex];

        if (!this.value) {

            recursoId.value = "";
            seccionInformacionRecurso.style.display = "none";

            devolucionOrigen.value = "";
            devolucionDestino.value = "";

            return;
        }

        recursoId.value = this.value;

        const origen =
            opcion.dataset.ubicacion || "No registrada";

        const destino =
            opcion.dataset.habitual || "No registrada";

        ubicacionActual.value = origen;
        ubicacionHabitual.value = destino;

        devolucionOrigen.value = origen;
        devolucionDestino.value = destino;

        cantidad.max =
            parseInt(opcion.dataset.cantidad || "1", 10);

        cantidad.value = 1;

        seccionInformacionRecurso.style.display = "block";
    });


    /*
    |--------------------------------------------------------------------------
    | EVITAR CANTIDAD MAYOR AL MÁXIMO
    |--------------------------------------------------------------------------
    */
    cantidad.addEventListener("input", function () {

        const max =
            parseInt(this.max || "0", 10);

        const valor =
            parseInt(this.value || "0", 10);

        if (max > 0 && valor > max) {
            this.value = max;
        }

        if (valor < 1) {
            this.value = 1;
        }

    });


    /*
    |--------------------------------------------------------------------------
    | VALIDACIÓN FINAL ANTES DE ENVIAR
    |--------------------------------------------------------------------------
    */
    document
        .getElementById("formMovimiento")
        .addEventListener("submit", function (event) {

            const valor = tipo.value;

            if (!valor) {

                event.preventDefault();

                alert("Seleccione un tipo de movimiento.");

                return;
            }


            if (valor === "ENTRADA") {

                const destino =
                    document.getElementById(
                        "ubicacion_destino_id"
                    ).value;

                if (!destino) {

                    event.preventDefault();

                    alert(
                        "Seleccione dónde quedará el nuevo recurso."
                    );

                    return;
                }

            }


            if (
                valor === "SALIDA" ||
                valor === "PRESTAMO" ||
                valor === "DEVOLUCION"
            ) {

                if (!recursoId.value) {

                    event.preventDefault();

                    alert("Seleccione un recurso.");

                    return;
                }

            }


            if (valor === "PRESTAMO") {

                const destino =
                    document.getElementById(
                        "ubicacion_destino_id"
                    ).value;

                if (!destino) {

                    event.preventDefault();

                    alert(
                        "Seleccione el salón o lugar donde será utilizado el recurso."
                    );

                    return;
                }

            }

        });

});
</script>

<?php require_once "../includes/footer.php"; ?>