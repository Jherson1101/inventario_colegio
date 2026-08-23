<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

$titulo = "Editar recurso";

$error = "";
$exito = false;


// =====================================================
// OBTENER ID
// =====================================================

$id = isset($_GET["id"])
    ? (int) $_GET["id"]
    : 0;

if ($id <= 0) {

    header("Location: index.php");
    exit;
}


// =====================================================
// CARGAR RECURSO
// =====================================================

$sql = "
    SELECT
        r.*,
        c.nombre AS categoria,
        a.nombre AS area,
        u.nombre AS ubicacion

    FROM recursos r

    INNER JOIN categorias c
        ON r.categoria_id = c.id

    LEFT JOIN areas a
        ON r.area_id = a.id

    LEFT JOIN ubicaciones u
        ON r.ubicacion_id = u.id

    WHERE r.id = ?

    LIMIT 1
";

$stmt = $conexion->prepare($sql);

if (!$stmt) {
    die("Error al consultar el recurso: " . $conexion->error);
}

$stmt->bind_param("i", $id);

$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado->num_rows !== 1) {

    $stmt->close();

    header("Location: index.php");
    exit;
}

$recurso = $resultado->fetch_assoc();

$stmt->close();


// =====================================================
// CARGAR ESPECIFICACIONES
// =====================================================

$sql = "
    SELECT
        sistema_operativo,
        office,
        procesador,
        ram,
        disco,
        wifi_red,
        ip,
        tipo_conexion,
        nombre_equipo,
        estado_bateria

    FROM especificaciones_tecnicas

    WHERE recurso_id = ?

    LIMIT 1
";

$stmt = $conexion->prepare($sql);

if (!$stmt) {
    die(
        "Error al consultar especificaciones: "
        . $conexion->error
    );
}

$stmt->bind_param("i", $id);

$stmt->execute();

$resultado = $stmt->get_result();

$especificaciones = $resultado->fetch_assoc();

$stmt->close();


// =====================================================
// CARGAR ACCESORIOS
// =====================================================

$sql = "
    SELECT
        id,
        tipo,
        descripcion,
        cantidad,
        estado

    FROM accesorios

    WHERE recurso_id = ?

    ORDER BY id
";

$stmt = $conexion->prepare($sql);

if (!$stmt) {
    die(
        "Error al consultar accesorios: "
        . $conexion->error
    );
}

$stmt->bind_param("i", $id);

$stmt->execute();

$accesorios_resultado = $stmt->get_result();

$accesorios = [];

while ($accesorio = $accesorios_resultado->fetch_assoc()) {

    $accesorios[] = $accesorio;
}

$stmt->close();


// =====================================================
// CARGAR CATEGORÍAS
// =====================================================

$sql = "
    SELECT id, nombre
    FROM categorias
    WHERE estado = 'ACTIVO'
    ORDER BY nombre
";

$categorias = $conexion->query($sql);


// =====================================================
// CARGAR ÁREAS
// =====================================================

$sql = "
    SELECT id, nombre
    FROM areas
    WHERE estado = 'ACTIVO'
    ORDER BY nombre
";

$areas = $conexion->query($sql);


// =====================================================
// CARGAR UBICACIONES
// =====================================================

$sql = "
    SELECT
        id,
        nombre,
        area_id

    FROM ubicaciones

    WHERE estado = 'ACTIVO'

    ORDER BY nombre
";

$ubicaciones = $conexion->query($sql);


// =====================================================
// VALORES DEL FORMULARIO
// =====================================================

$codigo = $recurso["codigo_inventario"];
$categoria_id = $recurso["categoria_id"];
$descripcion = $recurso["descripcion"];
$marca = $recurso["marca"];
$modelo = $recurso["modelo"];
$numero_serie = $recurso["numero_serie"];
$color = $recurso["color"];

$area_id = $recurso["area_id"];
$ubicacion_id = $recurso["ubicacion_id"];

$situacion = $recurso["situacion"];
$estado = $recurso["estado"];
$anio = $recurso["anio"];
$cantidad = $recurso["cantidad"];
$observaciones = $recurso["observaciones"];


// =====================================================
// ESPECIFICACIONES
// =====================================================

$sistema_operativo = "";
$office = "";
$procesador = "";
$ram = "";
$disco = "";
$wifi_red = "";
$ip = "";
$tipo_conexion = "";
$nombre_equipo = "";
$estado_bateria = "";

if ($especificaciones) {

    $sistema_operativo =
        $especificaciones["sistema_operativo"];

    $office =
        $especificaciones["office"];

    $procesador =
        $especificaciones["procesador"];

    $ram =
        $especificaciones["ram"];

    $disco =
        $especificaciones["disco"];

    $wifi_red =
        $especificaciones["wifi_red"];

    $ip =
        $especificaciones["ip"];

    $tipo_conexion =
        $especificaciones["tipo_conexion"];

    $nombre_equipo =
        $especificaciones["nombre_equipo"];

    $estado_bateria =
        $especificaciones["estado_bateria"];
}


// =====================================================
// ACCESORIOS
// =====================================================

$cargador = false;
$mouse = false;

foreach ($accesorios as $accesorio) {

    if ($accesorio["tipo"] === "Cargador") {
        $cargador = true;
    }

    if ($accesorio["tipo"] === "Mouse") {
        $mouse = true;
    }
}


// =====================================================
// PROCESAR FORMULARIO
// =====================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {


    // =================================================
    // DATOS GENERALES
    // =================================================

    $codigo = trim(
        $_POST["codigo_inventario"] ?? ""
    );

    $categoria_id = (int)(
        $_POST["categoria_id"] ?? 0
    );

    $descripcion = trim(
        $_POST["descripcion"] ?? ""
    );

    $marca = trim(
        $_POST["marca"] ?? ""
    );

    $modelo = trim(
        $_POST["modelo"] ?? ""
    );

    $numero_serie = trim(
        $_POST["numero_serie"] ?? ""
    );

    $color = trim(
        $_POST["color"] ?? ""
    );


    // =================================================
    // UBICACIÓN
    // =================================================

    $area_id = !empty($_POST["area_id"])
        ? (int) $_POST["area_id"]
        : null;

    $ubicacion_id = !empty($_POST["ubicacion_id"])
        ? (int) $_POST["ubicacion_id"]
        : null;


    // =================================================
    // CONTROL
    // =================================================

    $situacion =
        $_POST["situacion"] ?? "DISPONIBLE";

    $estado =
        $_POST["estado"] ?? "BUENO";

    $anio = !empty($_POST["anio"])
        ? (int) $_POST["anio"]
        : null;

    $cantidad = max(
        1,
        (int)(
            $_POST["cantidad"] ?? 1
        )
    );

    $observaciones = trim(
        $_POST["observaciones"] ?? ""
    );


    // =================================================
    // ESPECIFICACIONES
    // =================================================

    $sistema_operativo = trim(
        $_POST["sistema_operativo"] ?? ""
    );

    $office = trim(
        $_POST["office"] ?? ""
    );

    $procesador = trim(
        $_POST["procesador"] ?? ""
    );

    $ram = trim(
        $_POST["ram"] ?? ""
    );

    $disco = trim(
        $_POST["disco"] ?? ""
    );

    $wifi_red = trim(
        $_POST["wifi_red"] ?? ""
    );

    $ip = trim(
        $_POST["ip"] ?? ""
    );

    $tipo_conexion = trim(
        $_POST["tipo_conexion"] ?? ""
    );

    $nombre_equipo = trim(
        $_POST["nombre_equipo"] ?? ""
    );

    $estado_bateria = trim(
        $_POST["estado_bateria"] ?? ""
    );


    // =================================================
    // ACCESORIOS
    // =================================================

    $cargador =
        isset($_POST["cargador"]);

    $mouse =
        isset($_POST["mouse"]);


    // =================================================
    // VALIDACIONES
    // =================================================

    if ($codigo === "") {

        $error =
            "El código de inventario es obligatorio.";

    } elseif ($categoria_id <= 0) {

        $error =
            "Debe seleccionar una categoría.";

    } elseif ($descripcion === "") {

        $error =
            "La descripción es obligatoria.";

    } elseif ($cantidad <= 0) {

        $error =
            "La cantidad debe ser mayor a cero.";
    }


    // =================================================
    // ACTUALIZAR
    // =================================================

    if ($error === "") {

        try {

            $conexion->begin_transaction();


            // =========================================
            // COMPROBAR CÓDIGO DUPLICADO
            // =========================================

            $sql_check = "
                SELECT id
                FROM recursos
                WHERE codigo_inventario = ?
                AND id <> ?
                LIMIT 1
            ";

            $stmt_check =
                $conexion->prepare($sql_check);

            if (!$stmt_check) {

                throw new Exception(
                    "No se pudo verificar el código."
                );
            }

            $stmt_check->bind_param(
                "si",
                $codigo,
                $id
            );

            $stmt_check->execute();

            $resultado_check =
                $stmt_check->get_result();

            if ($resultado_check->num_rows > 0) {

                $stmt_check->close();

                throw new Exception(
                    "Ya existe otro recurso con el código: "
                    . $codigo
                );
            }

            $stmt_check->close();


            // =========================================
            // ACTUALIZAR RECURSO
            // =========================================

            $sql_update = "
                UPDATE recursos

                SET
                    codigo_inventario = ?,
                    categoria_id = ?,
                    area_id = ?,
                    ubicacion_id = ?,
                    descripcion = ?,
                    marca = ?,
                    modelo = ?,
                    numero_serie = ?,
                    color = ?,
                    situacion = ?,
                    estado = ?,
                    anio = ?,
                    cantidad = ?,
                    observaciones = ?

                WHERE id = ?
            ";

            $stmt =
                $conexion->prepare($sql_update);

            if (!$stmt) {

                throw new Exception(
                    "No se pudo preparar la actualización: "
                    . $conexion->error
                );
            }

            $stmt->bind_param(
                "siiisssssssiisi",
                $codigo,
                $categoria_id,
                $area_id,
                $ubicacion_id,
                $descripcion,
                $marca,
                $modelo,
                $numero_serie,
                $color,
                $situacion,
                $estado,
                $anio,
                $cantidad,
                $observaciones,
                $id
            );

            if (!$stmt->execute()) {

                throw new Exception(
                    "No se pudo actualizar el recurso: "
                    . $stmt->error
                );
            }

            $stmt->close();


            // =========================================
            // ESPECIFICACIONES
            // =========================================

            $hay_especificaciones =
                $sistema_operativo !== ""
                || $office !== ""
                || $procesador !== ""
                || $ram !== ""
                || $disco !== ""
                || $wifi_red !== ""
                || $ip !== ""
                || $tipo_conexion !== ""
                || $nombre_equipo !== ""
                || $estado_bateria !== "";


            if ($hay_especificaciones) {

                $sql_existe = "
                    SELECT id
                    FROM especificaciones_tecnicas
                    WHERE recurso_id = ?
                    LIMIT 1
                ";

                $stmt =
                    $conexion->prepare(
                        $sql_existe
                    );

                $stmt->bind_param(
                    "i",
                    $id
                );

                $stmt->execute();

                $resultado =
                    $stmt->get_result();

                $existe =
                    $resultado->num_rows > 0;

                $stmt->close();


                if ($existe) {

                    // -----------------------------
                    // ACTUALIZAR
                    // -----------------------------

                    $sql = "
                        UPDATE especificaciones_tecnicas

                        SET
                            sistema_operativo = ?,
                            office = ?,
                            procesador = ?,
                            ram = ?,
                            disco = ?,
                            wifi_red = ?,
                            ip = ?,
                            tipo_conexion = ?,
                            nombre_equipo = ?,
                            estado_bateria = ?

                        WHERE recurso_id = ?
                    ";

                    $stmt =
                        $conexion->prepare($sql);

                    $stmt->bind_param(
                        "ssssssssssi",
                        $sistema_operativo,
                        $office,
                        $procesador,
                        $ram,
                        $disco,
                        $wifi_red,
                        $ip,
                        $tipo_conexion,
                        $nombre_equipo,
                        $estado_bateria,
                        $id
                    );

                    if (!$stmt->execute()) {

                        throw new Exception(
                            "No se pudieron actualizar las especificaciones: "
                            . $stmt->error
                        );
                    }

                    $stmt->close();

                } else {

                    // -----------------------------
                    // INSERTAR
                    // -----------------------------

                    $sql = "
                        INSERT INTO especificaciones_tecnicas (
                            recurso_id,
                            sistema_operativo,
                            office,
                            procesador,
                            ram,
                            disco,
                            wifi_red,
                            ip,
                            tipo_conexion,
                            nombre_equipo,
                            estado_bateria
                        )

                        VALUES (
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?
                        )
                    ";

                    $stmt =
                        $conexion->prepare($sql);

                    $stmt->bind_param(
                        "issssssssss",
                        $id,
                        $sistema_operativo,
                        $office,
                        $procesador,
                        $ram,
                        $disco,
                        $wifi_red,
                        $ip,
                        $tipo_conexion,
                        $nombre_equipo,
                        $estado_bateria
                    );

                    if (!$stmt->execute()) {

                        throw new Exception(
                            "No se pudieron guardar las especificaciones: "
                            . $stmt->error
                        );
                    }

                    $stmt->close();
                }

            } else {

                // Si ya no hay especificaciones,
                // eliminamos el registro.

                $sql = "
                    DELETE FROM especificaciones_tecnicas
                    WHERE recurso_id = ?
                ";

                $stmt =
                    $conexion->prepare($sql);

                $stmt->bind_param(
                    "i",
                    $id
                );

                $stmt->execute();

                $stmt->close();
            }


            // =========================================
            // ACCESORIOS
            // =========================================

            /*
             * Para Cargador y Mouse utilizamos un enfoque
             * sencillo: eliminamos los accesorios anteriores
             * de esos tipos y volvemos a crearlos según
             * lo seleccionado.
             */

            $sql_delete = "
                DELETE FROM accesorios

                WHERE recurso_id = ?

                AND tipo IN (
                    'Cargador',
                    'Mouse'
                )
            ";

            $stmt =
                $conexion->prepare($sql_delete);

            $stmt->bind_param(
                "i",
                $id
            );

            $stmt->execute();

            $stmt->close();


            // -----------------------------------------
            // CARGADOR
            // -----------------------------------------

            if ($cargador) {

                $sql = "
                    INSERT INTO accesorios (
                        recurso_id,
                        tipo,
                        descripcion,
                        cantidad,
                        estado
                    )

                    VALUES (
                        ?,
                        'Cargador',
                        'Cargador del equipo',
                        1,
                        'BUENO'
                    )
                ";

                $stmt =
                    $conexion->prepare($sql);

                $stmt->bind_param(
                    "i",
                    $id
                );

                if (!$stmt->execute()) {

                    throw new Exception(
                        "No se pudo actualizar el cargador: "
                        . $stmt->error
                    );
                }

                $stmt->close();
            }


            // -----------------------------------------
            // MOUSE
            // -----------------------------------------

            if ($mouse) {

                $sql = "
                    INSERT INTO accesorios (
                        recurso_id,
                        tipo,
                        descripcion,
                        cantidad,
                        estado
                    )

                    VALUES (
                        ?,
                        'Mouse',
                        'Mouse del equipo',
                        1,
                        'BUENO'
                    )
                ";

                $stmt =
                    $conexion->prepare($sql);

                $stmt->bind_param(
                    "i",
                    $id
                );

                if (!$stmt->execute()) {

                    throw new Exception(
                        "No se pudo actualizar el mouse: "
                        . $stmt->error
                    );
                }

                $stmt->close();
            }


            // =========================================
            // CONFIRMAR
            // =========================================

            $conexion->commit();

            $exito = true;


            // Recargar los datos actualizados
            $sql = "
                SELECT
                    r.*,
                    c.nombre AS categoria,
                    a.nombre AS area,
                    u.nombre AS ubicacion

                FROM recursos r

                INNER JOIN categorias c
                    ON r.categoria_id = c.id

                LEFT JOIN areas a
                    ON r.area_id = a.id

                LEFT JOIN ubicaciones u
                    ON r.ubicacion_id = u.id

                WHERE r.id = ?

                LIMIT 1
            ";

            $stmt =
                $conexion->prepare($sql);

            $stmt->bind_param(
                "i",
                $id
            );

            $stmt->execute();

            $resultado =
                $stmt->get_result();

            $recurso =
                $resultado->fetch_assoc();

            $stmt->close();


        } catch (Exception $e) {

            $conexion->rollback();

            $error =
                $e->getMessage();
        }
    }
}


require_once "../includes/header.php";
require_once "../includes/navbar.php";

?>


<main class="main-content">


    <!-- =================================================
         ENCABEZADO
         ================================================= -->

    <div class="page-header">

        <div>

            <h1>
                Editar recurso
            </h1>

            <p>

                <?php
                echo htmlspecialchars(
                    $recurso["codigo_inventario"]
                );
                ?>

                -

                <?php
                echo htmlspecialchars(
                    $recurso["descripcion"]
                );
                ?>

            </p>

        </div>


        <div>

            <a
                href="ver.php?id=<?php echo $id; ?>"
                class="btn btn-small"
            >
                ← Volver
            </a>

        </div>

    </div>


    <!-- =================================================
         MENSAJE ERROR
         ================================================= -->

    <?php if ($error !== ""): ?>

        <div class="alert alert-error">

            <?php
            echo htmlspecialchars($error);
            ?>

        </div>

    <?php endif; ?>


    <!-- =================================================
         MENSAJE ÉXITO
         ================================================= -->

    <?php if ($exito): ?>

        <div class="alert alert-success">

            <strong>
                Recurso actualizado correctamente.
            </strong>

            <br><br>

            Los cambios fueron guardados correctamente.

        </div>

    <?php endif; ?>


    <!-- =================================================
         FORMULARIO
         ================================================= -->

    <form
        method="POST"
        class="resource-form"
    >


        <!-- =================================================
             INFORMACIÓN GENERAL
             ================================================= -->

        <section class="form-section">

            <div class="form-section-title">

                <h2>
                    Información general
                </h2>

            </div>


            <div class="form-grid">


                <div class="form-group">

                    <label for="codigo_inventario">
                        Código de inventario *
                    </label>

                    <input
                        type="text"
                        id="codigo_inventario"
                        name="codigo_inventario"
                        class="form-control"
                        value="<?php echo htmlspecialchars($codigo); ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="categoria_id">
                        Categoría *
                    </label>

                    <select
                        id="categoria_id"
                        name="categoria_id"
                        class="form-control"
                        required
                    >

                        <?php while (
                            $categoria =
                            $categorias->fetch_assoc()
                        ): ?>

                            <option
                                value="<?php echo $categoria["id"]; ?>"
                                <?php
                                echo (
                                    (string)$categoria["id"]
                                    ===
                                    (string)$categoria_id
                                )
                                    ? "selected"
                                    : "";
                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $categoria["nombre"]
                                );
                                ?>

                            </option>

                        <?php endwhile; ?>

                    </select>

                </div>


                <div
                    class="form-group form-group-full"
                >

                    <label for="descripcion">
                        Descripción *
                    </label>

                    <input
                        type="text"
                        id="descripcion"
                        name="descripcion"
                        class="form-control"
                        value="<?php echo htmlspecialchars($descripcion); ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="marca">
                        Marca
                    </label>

                    <input
                        type="text"
                        id="marca"
                        name="marca"
                        class="form-control"
                        value="<?php echo htmlspecialchars($marca); ?>"
                    >

                </div>


                <div class="form-group">

                    <label for="modelo">
                        Modelo
                    </label>

                    <input
                        type="text"
                        id="modelo"
                        name="modelo"
                        class="form-control"
                        value="<?php echo htmlspecialchars($modelo); ?>"
                    >

                </div>


                <div class="form-group">

                    <label for="numero_serie">
                        Número de serie
                    </label>

                    <input
                        type="text"
                        id="numero_serie"
                        name="numero_serie"
                        class="form-control"
                        value="<?php echo htmlspecialchars($numero_serie); ?>"
                    >

                </div>


                <div class="form-group">

                    <label for="color">
                        Color
                    </label>

                    <input
                        type="text"
                        id="color"
                        name="color"
                        class="form-control"
                        value="<?php echo htmlspecialchars($color); ?>"
                    >

                </div>

            </div>

        </section>


        <!-- =================================================
             UBICACIÓN
             ================================================= -->

        <section class="form-section">

            <div class="form-section-title">

                <h2>
                    Ubicación
                </h2>

            </div>


            <div class="form-grid">


                <div class="form-group">

                    <label for="area_id">
                        Área
                    </label>

                    <select
                        id="area_id"
                        name="area_id"
                        class="form-control"
                    >

                        <option value="">
                            Sin área específica
                        </option>


                        <?php while (
                            $area =
                            $areas->fetch_assoc()
                        ): ?>

                            <option
                                value="<?php echo $area["id"]; ?>"
                                <?php
                                echo (
                                    (string)$area["id"]
                                    ===
                                    (string)$area_id
                                )
                                    ? "selected"
                                    : "";
                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $area["nombre"]
                                );
                                ?>

                            </option>

                        <?php endwhile; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label for="ubicacion_id">
                        Ubicación
                    </label>

                    <select
                        id="ubicacion_id"
                        name="ubicacion_id"
                        class="form-control"
                    >

                        <option value="">
                            Seleccionar ubicación
                        </option>


                        <?php while (
                            $ubicacion =
                            $ubicaciones->fetch_assoc()
                        ): ?>

                            <option
                                value="<?php echo $ubicacion["id"]; ?>"
                                data-area="<?php echo $ubicacion["area_id"] ?? ""; ?>"
                                <?php
                                echo (
                                    (string)$ubicacion["id"]
                                    ===
                                    (string)$ubicacion_id
                                )
                                    ? "selected"
                                    : "";
                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $ubicacion["nombre"]
                                );
                                ?>

                            </option>

                        <?php endwhile; ?>

                    </select>

                </div>


            </div>

        </section>


        <!-- =================================================
             CONTROL
             ================================================= -->

        <section class="form-section">

            <div class="form-section-title">

                <h2>
                    Control del recurso
                </h2>

            </div>


            <div class="form-grid">


                <div class="form-group">

                    <label for="situacion">
                        Situación
                    </label>

                    <select
                        id="situacion"
                        name="situacion"
                        class="form-control"
                    >

                        <option
                            value="DISPONIBLE"
                            <?php echo $situacion === "DISPONIBLE" ? "selected" : ""; ?>
                        >
                            Disponible
                        </option>

                        <option
                            value="EN USO"
                            <?php echo $situacion === "EN USO" ? "selected" : ""; ?>
                        >
                            En uso
                        </option>

                        <option
                            value="PRESTADO"
                            <?php echo $situacion === "PRESTADO" ? "selected" : ""; ?>
                        >
                            Prestado
                        </option>

                        <option
                            value="EN MANTENIMIENTO"
                            <?php echo $situacion === "EN MANTENIMIENTO" ? "selected" : ""; ?>
                        >
                            En mantenimiento
                        </option>

                        <option
                            value="ALMACENADO"
                            <?php echo $situacion === "ALMACENADO" ? "selected" : ""; ?>
                        >
                            Almacenado
                        </option>

                        <option
                            value="DADO DE BAJA"
                            <?php echo $situacion === "DADO DE BAJA" ? "selected" : ""; ?>
                        >
                            Dado de baja
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label for="estado">
                        Estado
                    </label>

                    <select
                        id="estado"
                        name="estado"
                        class="form-control"
                    >

                        <option
                            value="EXCELENTE"
                            <?php echo $estado === "EXCELENTE" ? "selected" : ""; ?>
                        >
                            Excelente
                        </option>

                        <option
                            value="BUENO"
                            <?php echo $estado === "BUENO" ? "selected" : ""; ?>
                        >
                            Bueno
                        </option>

                        <option
                            value="REGULAR"
                            <?php echo $estado === "REGULAR" ? "selected" : ""; ?>
                        >
                            Regular
                        </option>

                        <option
                            value="DEFICIENTE"
                            <?php echo $estado === "DEFICIENTE" ? "selected" : ""; ?>
                        >
                            Deficiente
                        </option>

                        <option
                            value="MALOGRADO"
                            <?php echo $estado === "MALOGRADO" ? "selected" : ""; ?>
                        >
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
                        id="anio"
                        name="anio"
                        class="form-control"
                        value="<?php echo htmlspecialchars($anio ?? ""); ?>"
                        min="1900"
                        max="2100"
                    >

                </div>


                <div class="form-group">

                    <label for="cantidad">
                        Cantidad
                    </label>

                    <input
                        type="number"
                        id="cantidad"
                        name="cantidad"
                        class="form-control"
                        value="<?php echo htmlspecialchars($cantidad); ?>"
                        min="1"
                        required
                    >

                </div>


                <div
                    class="form-group form-group-full"
                >

                    <label for="observaciones">
                        Observaciones
                    </label>

                    <textarea
                        id="observaciones"
                        name="observaciones"
                        class="form-control"
                        rows="4"
                    ><?php echo htmlspecialchars($observaciones ?? ""); ?></textarea>

                </div>


            </div>

        </section>


        <!-- =================================================
             ESPECIFICACIONES TÉCNICAS
             ================================================= -->

        <section
            class="form-section"
            id="seccionTecnica"
        >

            <div class="form-section-title">

                <h2>
                    Especificaciones técnicas
                </h2>

            </div>


            <div class="form-grid">


                <div class="form-group campo-tecnico">

                    <label for="sistema_operativo">
                        Sistema operativo
                    </label>

                    <input
                        type="text"
                        id="sistema_operativo"
                        name="sistema_operativo"
                        class="form-control"
                        value="<?php echo htmlspecialchars($sistema_operativo); ?>"
                    >

                </div>


                <div class="form-group campo-tecnico">

                    <label for="office">
                        Office
                    </label>

                    <input
                        type="text"
                        id="office"
                        name="office"
                        class="form-control"
                        value="<?php echo htmlspecialchars($office); ?>"
                    >

                </div>


                <div class="form-group campo-tecnico">

                    <label for="procesador">
                        Procesador
                    </label>

                    <input
                        type="text"
                        id="procesador"
                        name="procesador"
                        class="form-control"
                        value="<?php echo htmlspecialchars($procesador); ?>"
                    >

                </div>


                <div class="form-group campo-tecnico">

                    <label for="ram">
                        RAM
                    </label>

                    <input
                        type="text"
                        id="ram"
                        name="ram"
                        class="form-control"
                        value="<?php echo htmlspecialchars($ram); ?>"
                    >

                </div>


                <div class="form-group campo-tecnico">

                    <label for="disco">
                        Disco
                    </label>

                    <input
                        type="text"
                        id="disco"
                        name="disco"
                        class="form-control"
                        value="<?php echo htmlspecialchars($disco); ?>"
                    >

                </div>


                <div class="form-group campo-tecnico">

                    <label for="wifi_red">
                        WiFi / Red
                    </label>

                    <input
                        type="text"
                        id="wifi_red"
                        name="wifi_red"
                        class="form-control"
                        value="<?php echo htmlspecialchars($wifi_red); ?>"
                    >

                </div>


                <div class="form-group campo-tecnico">

                    <label for="ip">
                        Dirección IP
                    </label>

                    <input
                        type="text"
                        id="ip"
                        name="ip"
                        class="form-control"
                        value="<?php echo htmlspecialchars($ip); ?>"
                    >

                </div>


                <div class="form-group campo-tecnico">

                    <label for="tipo_conexion">
                        Tipo de conexión
                    </label>

                    <input
                        type="text"
                        id="tipo_conexion"
                        name="tipo_conexion"
                        class="form-control"
                        value="<?php echo htmlspecialchars($tipo_conexion); ?>"
                    >

                </div>


                <div class="form-group campo-tecnico">

                    <label for="nombre_equipo">
                        Nombre del equipo
                    </label>

                    <input
                        type="text"
                        id="nombre_equipo"
                        name="nombre_equipo"
                        class="form-control"
                        value="<?php echo htmlspecialchars($nombre_equipo); ?>"
                    >

                </div>


                <div class="form-group campo-tecnico">

                    <label for="estado_bateria">
                        Estado de batería
                    </label>

                    <select
                        id="estado_bateria"
                        name="estado_bateria"
                        class="form-control"
                    >

                        <option value="">
                            No aplica
                        </option>

                        <option
                            value="EXCELENTE"
                            <?php echo $estado_bateria === "EXCELENTE" ? "selected" : ""; ?>
                        >
                            Excelente
                        </option>

                        <option
                            value="BUENO"
                            <?php echo $estado_bateria === "BUENO" ? "selected" : ""; ?>
                        >
                            Bueno
                        </option>

                        <option
                            value="REGULAR"
                            <?php echo $estado_bateria === "REGULAR" ? "selected" : ""; ?>
                        >
                            Regular
                        </option>

                        <option
                            value="MALOGRADO"
                            <?php echo $estado_bateria === "MALOGRADO" ? "selected" : ""; ?>
                        >
                            Malogrado
                        </option>

                    </select>

                </div>


            </div>

        </section>


        <!-- =================================================
             ACCESORIOS
             ================================================= -->

        <section class="form-section">

            <div class="form-section-title">

                <h2>
                    Accesorios
                </h2>

            </div>


            <div class="checkbox-group">


                <label class="checkbox-item">

                    <input
                        type="checkbox"
                        name="cargador"
                        <?php echo $cargador ? "checked" : ""; ?>
                    >

                    <span>
                        Cargador
                    </span>

                </label>


                <label class="checkbox-item">

                    <input
                        type="checkbox"
                        name="mouse"
                        <?php echo $mouse ? "checked" : ""; ?>
                    >

                    <span>
                        Mouse
                    </span>

                </label>


            </div>

        </section>


        <!-- =================================================
             BOTONES
             ================================================= -->

        <div class="form-actions">

            <a
                href="ver.php?id=<?php echo $id; ?>"
                class="btn btn-small"
            >
                Cancelar
            </a>

            <button
                type="submit"
                class="btn btn-primary"
            >
                Guardar cambios
            </button>

        </div>


    </form>


</main>


<?php

require_once "../includes/footer.php";

?>