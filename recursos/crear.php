<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

$titulo = "Nuevo recurso";

$error = "";
$exito = false;


// =====================================================
// VALORES DEL FORMULARIO
// =====================================================

// Información general
$codigo = "";
$categoria_id = "";
$descripcion = "";
$marca = "";
$modelo = "";
$numero_serie = "";
$color = "";

// Ubicación
$area_id = "";
$ubicacion_id = "";
$ubicacion_habitual_id = "";

// Control
$situacion = "DISPONIBLE";
$estado = "BUENO";
$anio = "";
$cantidad = 1;
$observaciones = "";

// Especificaciones
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

// Accesorios
$cargador = false;
$mouse = false;


// =====================================================
// CARGAR CATEGORÍAS
// =====================================================

$sql_categorias = "
    SELECT id, nombre
    FROM categorias
    WHERE estado = 'ACTIVO'
    ORDER BY nombre
";

$categorias = $conexion->query($sql_categorias);

if (!$categorias) {

    die(
        "Error al cargar las categorías: "
        . $conexion->error
    );

}


// =====================================================
// CARGAR ÁREAS
// =====================================================

$sql_areas = "
    SELECT id, nombre
    FROM areas
    WHERE estado = 'ACTIVO'
    ORDER BY nombre
";

$areas = $conexion->query($sql_areas);

if (!$areas) {

    die(
        "Error al cargar las áreas: "
        . $conexion->error
    );

}


// =====================================================
// CARGAR UBICACIONES
// =====================================================

$sql_ubicaciones = "
    SELECT
        u.id,
        u.nombre,
        u.area_id

    FROM ubicaciones u

    WHERE u.estado = 'ACTIVO'

    ORDER BY u.nombre
";

$ubicaciones = $conexion->query($sql_ubicaciones);

if (!$ubicaciones) {

    die(
        "Error al cargar las ubicaciones: "
        . $conexion->error
    );

}


// =====================================================
// PROCESAR FORMULARIO
// =====================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {


    // =================================================
    // INFORMACIÓN GENERAL
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
        ? (int)$_POST["area_id"]
        : null;

    $ubicacion_id = !empty($_POST["ubicacion_id"])
        ? (int)$_POST["ubicacion_id"]
        : null;


    // =================================================
    // CONTROL
    // =================================================

    $situacion = $_POST["situacion"]
        ?? "DISPONIBLE";

    $estado = $_POST["estado"]
        ?? "BUENO";

    $anio = !empty($_POST["anio"])
        ? (int)$_POST["anio"]
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

    $cargador = isset(
        $_POST["cargador"]
    );

    $mouse = isset(
        $_POST["mouse"]
    );


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

    } elseif ($ubicacion_id === null) {

        $error =
            "Debe seleccionar la ubicación donde quedará el nuevo recurso.";

    } elseif ($cantidad <= 0) {

        $error =
            "La cantidad debe ser mayor a cero.";

    }


    // =================================================
    // GUARDAR
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
                LIMIT 1
            ";

            $stmt_check =
                $conexion->prepare(
                    $sql_check
                );

            if (!$stmt_check) {

                throw new Exception(
                    "No se pudo verificar el código."
                );

            }

            $stmt_check->bind_param(
                "s",
                $codigo
            );

            $stmt_check->execute();

            $resultado_check =
                $stmt_check->get_result();


            if (
                $resultado_check->num_rows > 0
            ) {

                $stmt_check->close();

                throw new Exception(
                    "Ya existe un recurso con el código: "
                    . $codigo
                );

            }

            $stmt_check->close();


            // =========================================
            // INSERTAR RECURSO
            // =========================================

            $sql_recurso = "
                INSERT INTO recursos (
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
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
            ";

            $stmt =
                $conexion->prepare(
                    $sql_recurso
                );

            if (!$stmt) {

                throw new Exception(
                    "No se pudo preparar el registro del recurso: "
                    . $conexion->error
                );

            }


            $stmt->bind_param(
                "siiiisssssssiis",
                $codigo,
                $categoria_id,
                $area_id,
                $ubicacion_id,
                $ubicacion_habitual_id,
                $descripcion,
                $marca,
                $modelo,
                $numero_serie,
                $color,
                $situacion,
                $estado,
                $anio,
                $cantidad,
                $observaciones
            );


            if (!$stmt->execute()) {

                throw new Exception(
                    "No se pudo guardar el recurso: "
                    . $stmt->error
                );

            }


            $recurso_id =
                $conexion->insert_id;


            $stmt->close();


            // =========================================
            // ESPECIFICACIONES TÉCNICAS
            // =========================================

            /*
             * Creamos el registro de especificaciones
             * solamente si se ingresó algún dato técnico.
             */

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

                $sql_especificaciones = "
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
                    $conexion->prepare(
                        $sql_especificaciones
                    );

                if (!$stmt) {

                    throw new Exception(
                        "No se pudieron preparar las especificaciones: "
                        . $conexion->error
                    );

                }


                $stmt->bind_param(
                    "issssssssss",
                    $recurso_id,
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


            // =========================================
            // ACCESORIO: CARGADOR
            // =========================================

            if ($cargador) {

                $sql_accesorio = "
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
                    $conexion->prepare(
                        $sql_accesorio
                    );


                if (!$stmt) {

                    throw new Exception(
                        "No se pudo preparar el cargador: "
                        . $conexion->error
                    );

                }


                $stmt->bind_param(
                    "i",
                    $recurso_id
                );


                if (!$stmt->execute()) {

                    throw new Exception(
                        "No se pudo guardar el cargador: "
                        . $stmt->error
                    );

                }


                $stmt->close();

            }


            // =========================================
            // ACCESORIO: MOUSE
            // =========================================

            if ($mouse) {

                $sql_accesorio = "
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
                    $conexion->prepare(
                        $sql_accesorio
                    );


                if (!$stmt) {

                    throw new Exception(
                        "No se pudo preparar el mouse: "
                        . $conexion->error
                    );

                }


                $stmt->bind_param(
                    "i",
                    $recurso_id
                );


                if (!$stmt->execute()) {

                    throw new Exception(
                        "No se pudo guardar el mouse: "
                        . $stmt->error
                    );

                }


                $stmt->close();

            }


            // =========================================
            // MOVIMIENTO INICIAL
            // =========================================

            $sql_movimiento = "
                INSERT INTO movimientos (
                    recurso_id,
                    tipo_movimiento,
                    cantidad,
                    usuario_id,
                    responsable,
                    ubicacion_origen_id,
                    ubicacion_destino_id,
                    motivo,
                    observaciones
                )

                VALUES (
                    ?,
                    'ENTRADA',
                    ?,
                    ?,
                    ?,
                    NULL,
                    ?,
                    'Registro inicial',
                    'Ingreso inicial al sistema'
                )
            ";

            $stmt =
                $conexion->prepare(
                    $sql_movimiento
                );


            if (!$stmt) {

                throw new Exception(
                    "No se pudo preparar el movimiento: "
                    . $conexion->error
                );

            }


            $responsable =
                $_SESSION["nombre"];

            $usuario_id =
                $_SESSION["usuario_id"];


            $stmt->bind_param(
                "iiisi",
                $recurso_id,
                $cantidad,
                $usuario_id,
                $responsable,
                $ubicacion_id
            );


            if (!$stmt->execute()) {

                throw new Exception(
                    "No se pudo registrar el movimiento: "
                    . $stmt->error
                );

            }


            $stmt->close();


            // =========================================
            // CONFIRMAR
            // =========================================

            $conexion->commit();

            $exito = true;


            // Limpiar valores después de guardar
            $codigo = "";
            $categoria_id = "";
            $descripcion = "";
            $marca = "";
            $modelo = "";
            $numero_serie = "";
            $color = "";

            $area_id = "";
            $ubicacion_id = "";
            $ubicacion_habitual_id = "";

            $situacion = "DISPONIBLE";
            $estado = "BUENO";
            $anio = "";
            $cantidad = 1;
            $observaciones = "";

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

            $cargador = false;
            $mouse = false;


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
                Nuevo recurso
            </h1>

            <p>
                Registra un nuevo recurso en el inventario.
            </p>

        </div>


        <div>

            <a
                href="index.php"
                class="btn btn-small"
            >
                ← Volver
            </a>

        </div>

    </div>


    <!-- =================================================
         MENSAJE DE ERROR
         ================================================= -->

    <?php if ($error !== ""): ?>

        <div class="alert alert-error">

            <?php
            echo htmlspecialchars($error);
            ?>

        </div>

    <?php endif; ?>


    <!-- =================================================
         MENSAJE DE ÉXITO
         ================================================= -->

    <?php if ($exito): ?>

        <div class="alert alert-success">

            <strong>
                Recurso registrado correctamente.
            </strong>

            <br><br>

            El recurso fue agregado al inventario
            y se registró su movimiento de entrada.

            <br><br>

            <a href="index.php">
                Ver inventario
            </a>

        </div>

    <?php endif; ?>


    <?php if (!$exito): ?>


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


                <!-- Código -->

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
                        placeholder="Ejemplo: LAP-001"
                        required
                    >

                </div>


                <!-- Categoría -->

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

                        <option value="">
                            Seleccionar categoría
                        </option>


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


                <!-- Descripción -->

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
                        placeholder="Ejemplo: Laptop Lenovo ThinkPad E14"
                        required
                    >

                </div>


                <!-- Marca -->

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


                <!-- Modelo -->

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


                <!-- Número de serie -->

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


                <!-- Color -->

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


                <!-- Área -->

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


                <!-- Ubicación -->

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
             CONTROL DEL RECURSO
             ================================================= -->

        <section class="form-section">


            <div class="form-section-title">

                <h2>
                    Control del recurso
                </h2>

            </div>


            <div class="form-grid">


                <!-- Situación -->

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
                            <?php
                            echo $situacion === "DISPONIBLE"
                                ? "selected"
                                : "";
                            ?>
                        >
                            Disponible
                        </option>


                        <option
                            value="EN USO"
                            <?php
                            echo $situacion === "EN USO"
                                ? "selected"
                                : "";
                            ?>
                        >
                            En uso
                        </option>


                        <option
                            value="PRESTADO"
                            <?php
                            echo $situacion === "PRESTADO"
                                ? "selected"
                                : "";
                            ?>
                        >
                            Prestado
                        </option>


                        <option
                            value="EN MANTENIMIENTO"
                            <?php
                            echo $situacion === "EN MANTENIMIENTO"
                                ? "selected"
                                : "";
                            ?>
                        >
                            En mantenimiento
                        </option>


                        <option
                            value="ALMACENADO"
                            <?php
                            echo $situacion === "ALMACENADO"
                                ? "selected"
                                : "";
                            ?>
                        >
                            Almacenado
                        </option>


                        <option
                            value="DADO DE BAJA"
                            <?php
                            echo $situacion === "DADO DE BAJA"
                                ? "selected"
                                : "";
                            ?>
                        >
                            Dado de baja
                        </option>

                    </select>

                </div>


                <!-- Estado -->

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
                            <?php
                            echo $estado === "EXCELENTE"
                                ? "selected"
                                : "";
                            ?>
                        >
                            Excelente
                        </option>


                        <option
                            value="BUENO"
                            <?php
                            echo $estado === "BUENO"
                                ? "selected"
                                : "";
                            ?>
                        >
                            Bueno
                        </option>


                        <option
                            value="REGULAR"
                            <?php
                            echo $estado === "REGULAR"
                                ? "selected"
                                : "";
                            ?>
                        >
                            Regular
                        </option>


                        <option
                            value="DEFICIENTE"
                            <?php
                            echo $estado === "DEFICIENTE"
                                ? "selected"
                                : "";
                            ?>
                        >
                            Deficiente
                        </option>


                        <option
                            value="MALOGRADO"
                            <?php
                            echo $estado === "MALOGRADO"
                                ? "selected"
                                : "";
                            ?>
                        >
                            Malogrado
                        </option>

                    </select>

                </div>


                <!-- Año -->

                <div class="form-group">

                    <label for="anio">
                        Año
                    </label>

                    <input
                        type="number"
                        id="anio"
                        name="anio"
                        class="form-control"
                        value="<?php echo htmlspecialchars($anio); ?>"
                        min="1900"
                        max="2100"
                    >

                </div>


                <!-- Cantidad -->

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


                <!-- Observaciones -->

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
                        placeholder="Observaciones adicionales..."
                    ><?php echo htmlspecialchars($observaciones); ?></textarea>

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

                <p>
                    Estas especificaciones se muestran
                    principalmente para equipos como laptops.
                </p>

            </div>


            <div class="form-grid">


                <!-- Sistema operativo -->

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
                        placeholder="Ejemplo: Windows 11 Pro"
                    >

                </div>


                <!-- Office -->

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
                        placeholder="Ejemplo: Microsoft Office 2021"
                    >

                </div>


                <!-- Procesador -->

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
                        placeholder="Ejemplo: Intel Core i5"
                    >

                </div>


                <!-- RAM -->

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
                        placeholder="Ejemplo: 8 GB"
                    >

                </div>


                <!-- Disco -->

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
                        placeholder="Ejemplo: 512 GB SSD"
                    >

                </div>


                <!-- WiFi / Red -->

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
                        placeholder="Ejemplo: Sí"
                    >

                </div>


                <!-- IP -->

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
                        placeholder="Ejemplo: 192.168.1.10"
                    >

                </div>


                <!-- Tipo de conexión -->

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
                        placeholder="WiFi / Ethernet"
                    >

                </div>


                <!-- Nombre del equipo -->

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
                        placeholder="Ejemplo: LAP-AULA-01"
                    >

                </div>


                <!-- Estado batería -->

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
                            <?php
                            echo $estado_bateria === "EXCELENTE"
                                ? "selected"
                                : "";
                            ?>
                        >
                            Excelente
                        </option>


                        <option
                            value="BUENO"
                            <?php
                            echo $estado_bateria === "BUENO"
                                ? "selected"
                                : "";
                            ?>
                        >
                            Bueno
                        </option>


                        <option
                            value="REGULAR"
                            <?php
                            echo $estado_bateria === "REGULAR"
                                ? "selected"
                                : "";
                            ?>
                        >
                            Regular
                        </option>


                        <option
                            value="MALOGRADO"
                            <?php
                            echo $estado_bateria === "MALOGRADO"
                                ? "selected"
                                : "";
                            ?>
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


                <!-- Cargador -->

                <label class="checkbox-item">

                    <input
                        type="checkbox"
                        name="cargador"
                        <?php
                        echo $cargador
                            ? "checked"
                            : "";
                        ?>
                    >

                    <span>
                        Cargador
                    </span>

                </label>


                <!-- Mouse -->

                <label class="checkbox-item">

                    <input
                        type="checkbox"
                        name="mouse"
                        <?php
                        echo $mouse
                            ? "checked"
                            : "";
                        ?>
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
                href="index.php"
                class="btn btn-small"
            >
                Cancelar
            </a>


            <button
                type="submit"
                class="btn btn-primary"
            >
                Guardar recurso
            </button>


        </div>


    </form>

    <?php endif; ?>


</main>


<?php

require_once "../includes/footer.php";

?>