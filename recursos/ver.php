<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

$titulo = "Detalle del recurso";


// =====================================================
// OBTENER ID DEL RECURSO
// =====================================================

$id = isset($_GET["id"])
    ? (int) $_GET["id"]
    : 0;

if ($id <= 0) {
    header("Location: index.php");
    exit;
}


// =====================================================
// OBTENER INFORMACIÓN GENERAL DEL RECURSO
// =====================================================

$sql = "
    SELECT
        r.id,
        r.codigo_inventario,
        r.descripcion,
        r.marca,
        r.modelo,
        r.numero_serie,
        r.color,
        r.situacion,
        r.estado,
        r.anio,
        r.cantidad,
        r.observaciones,

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
// OBTENER ESPECIFICACIONES TÉCNICAS
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
        "Error al consultar las especificaciones: "
        . $conexion->error
    );
}

$stmt->bind_param("i", $id);

$stmt->execute();

$resultado = $stmt->get_result();

$especificaciones = $resultado->fetch_assoc();

$stmt->close();


// =====================================================
// OBTENER ACCESORIOS
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
        "Error al consultar los accesorios: "
        . $conexion->error
    );
}

$stmt->bind_param("i", $id);

$stmt->execute();

$accesorios = $stmt->get_result();

$stmt->close();


// =====================================================
// OBTENER HISTORIAL DE MOVIMIENTOS
// =====================================================

$sql = "
    SELECT
        m.id,
        m.tipo_movimiento,
        m.cantidad,
        m.fecha_hora,
        m.responsable,
        m.motivo,
        m.observaciones,

        origen.nombre AS ubicacion_origen,

        destino.nombre AS ubicacion_destino,

        us.usuario

    FROM movimientos m

    INNER JOIN usuarios us
        ON m.usuario_id = us.id

    LEFT JOIN ubicaciones origen
        ON m.ubicacion_origen_id = origen.id

    LEFT JOIN ubicaciones destino
        ON m.ubicacion_destino_id = destino.id

    WHERE m.recurso_id = ?

    ORDER BY m.fecha_hora DESC
";

$stmt = $conexion->prepare($sql);

if (!$stmt) {
    die(
        "Error al consultar los movimientos: "
        . $conexion->error
    );
}

$stmt->bind_param("i", $id);

$stmt->execute();

$movimientos = $stmt->get_result();

$stmt->close();


// =====================================================
// HEADER Y NAVBAR
// =====================================================

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

                <?php
                echo htmlspecialchars(
                    $recurso["descripcion"]
                );
                ?>

            </h1>

            <p>

                Código de inventario:

                <strong>

                    <?php
                    echo htmlspecialchars(
                        $recurso["codigo_inventario"]
                    );
                    ?>

                </strong>

            </p>

        </div>


        <div>

            <a
                href="index.php"
                class="btn btn-small"
            >
                ← Volver
            </a>
            <a 
                href="editar.php?id=<?php echo $id; ?>"
                class="btn btn-primary"
            >
                Editar recurso
            </a>

        </div>

    </div>


    <!-- =================================================
         INFORMACIÓN GENERAL
         ================================================= -->

    <section class="detail-section">

        <div class="detail-section-title">

            <h2>
                Información general
            </h2>

        </div>


        <div class="detail-grid">


            <!-- Código -->

            <div class="detail-item">

                <span class="detail-label">
                    Código de inventario
                </span>

                <span class="detail-value">

                    <?php
                    echo htmlspecialchars(
                        $recurso["codigo_inventario"]
                    );
                    ?>

                </span>

            </div>


            <!-- Categoría -->

            <div class="detail-item">

                <span class="detail-label">
                    Categoría
                </span>

                <span class="detail-value">

                    <?php
                    echo htmlspecialchars(
                        $recurso["categoria"]
                    );
                    ?>

                </span>

            </div>


            <!-- Marca -->

            <div class="detail-item">

                <span class="detail-label">
                    Marca
                </span>

                <span class="detail-value">

                    <?php
                    echo htmlspecialchars(
                        $recurso["marca"] ?: "-"
                    );
                    ?>

                </span>

            </div>


            <!-- Modelo -->

            <div class="detail-item">

                <span class="detail-label">
                    Modelo
                </span>

                <span class="detail-value">

                    <?php
                    echo htmlspecialchars(
                        $recurso["modelo"] ?: "-"
                    );
                    ?>

                </span>

            </div>


            <!-- Número de serie -->

            <div class="detail-item">

                <span class="detail-label">
                    Número de serie
                </span>

                <span class="detail-value">

                    <?php
                    echo htmlspecialchars(
                        $recurso["numero_serie"] ?: "-"
                    );
                    ?>

                </span>

            </div>


            <!-- Color -->

            <div class="detail-item">

                <span class="detail-label">
                    Color
                </span>

                <span class="detail-value">

                    <?php
                    echo htmlspecialchars(
                        $recurso["color"] ?: "-"
                    );
                    ?>

                </span>

            </div>


            <!-- Año -->

            <div class="detail-item">

                <span class="detail-label">
                    Año
                </span>

                <span class="detail-value">

                    <?php
                    echo htmlspecialchars(
                        $recurso["anio"] ?: "-"
                    );
                    ?>

                </span>

            </div>


            <!-- Cantidad -->

            <div class="detail-item">

                <span class="detail-label">
                    Cantidad
                </span>

                <span class="detail-value">

                    <?php
                    echo htmlspecialchars(
                        $recurso["cantidad"]
                    );
                    ?>

                </span>

            </div>


        </div>

    </section>


    <!-- =================================================
         UBICACIÓN Y ESTADO
         ================================================= -->

    <section class="detail-section">

        <div class="detail-section-title">

            <h2>
                Ubicación y estado
            </h2>

        </div>


        <div class="detail-grid">


            <!-- Área -->

            <div class="detail-item">

                <span class="detail-label">
                    Área
                </span>

                <span class="detail-value">

                    <?php
                    echo htmlspecialchars(
                        $recurso["area"]
                        ?: "Sin área"
                    );
                    ?>

                </span>

            </div>


            <!-- Ubicación -->

            <div class="detail-item">

                <span class="detail-label">
                    Ubicación
                </span>

                <span class="detail-value">

                    <?php
                    echo htmlspecialchars(
                        $recurso["ubicacion"]
                        ?: "-"
                    );
                    ?>

                </span>

            </div>


            <!-- Estado -->

            <div class="detail-item">

                <span class="detail-label">
                    Estado
                </span>

                <span class="detail-value">

                    <span class="status-badge">

                        <?php
                        echo htmlspecialchars(
                            $recurso["estado"]
                        );
                        ?>

                    </span>

                </span>

            </div>


            <!-- Situación -->

            <div class="detail-item">

                <span class="detail-label">
                    Situación
                </span>

                <span class="detail-value">

                    <span class="situation-badge">

                        <?php
                        echo htmlspecialchars(
                            $recurso["situacion"]
                        );
                        ?>

                    </span>

                </span>

            </div>


        </div>


        <!-- Observaciones -->

        <?php if (!empty($recurso["observaciones"])): ?>

            <div class="detail-observations">

                <strong>
                    Observaciones
                </strong>

                <p>

                    <?php
                    echo nl2br(
                        htmlspecialchars(
                            $recurso["observaciones"]
                        )
                    );
                    ?>

                </p>

            </div>

        <?php endif; ?>


    </section>


    <!-- =================================================
         ESPECIFICACIONES TÉCNICAS
         ================================================= -->

    <?php if ($especificaciones): ?>

        <section class="detail-section">

            <div class="detail-section-title">

                <h2>
                    Especificaciones técnicas
                </h2>

            </div>


            <div class="detail-grid">


                <!-- Sistema operativo -->

                <div class="detail-item">

                    <span class="detail-label">
                        Sistema operativo
                    </span>

                    <span class="detail-value">

                        <?php
                        echo htmlspecialchars(
                            $especificaciones[
                                "sistema_operativo"
                            ] ?: "-"
                        );
                        ?>

                    </span>

                </div>


                <!-- Office -->

                <div class="detail-item">

                    <span class="detail-label">
                        Office
                    </span>

                    <span class="detail-value">

                        <?php
                        echo htmlspecialchars(
                            $especificaciones[
                                "office"
                            ] ?: "-"
                        );
                        ?>

                    </span>

                </div>


                <!-- Procesador -->

                <div class="detail-item">

                    <span class="detail-label">
                        Procesador
                    </span>

                    <span class="detail-value">

                        <?php
                        echo htmlspecialchars(
                            $especificaciones[
                                "procesador"
                            ] ?: "-"
                        );
                        ?>

                    </span>

                </div>


                <!-- RAM -->

                <div class="detail-item">

                    <span class="detail-label">
                        RAM
                    </span>

                    <span class="detail-value">

                        <?php
                        echo htmlspecialchars(
                            $especificaciones[
                                "ram"
                            ] ?: "-"
                        );
                        ?>

                    </span>

                </div>


                <!-- Disco -->

                <div class="detail-item">

                    <span class="detail-label">
                        Disco
                    </span>

                    <span class="detail-value">

                        <?php
                        echo htmlspecialchars(
                            $especificaciones[
                                "disco"
                            ] ?: "-"
                        );
                        ?>

                    </span>

                </div>


                <!-- WiFi / Red -->

                <div class="detail-item">

                    <span class="detail-label">
                        WiFi / Red
                    </span>

                    <span class="detail-value">

                        <?php
                        echo htmlspecialchars(
                            $especificaciones[
                                "wifi_red"
                            ] ?: "-"
                        );
                        ?>

                    </span>

                </div>


                <!-- IP -->

                <div class="detail-item">

                    <span class="detail-label">
                        Dirección IP
                    </span>

                    <span class="detail-value">

                        <?php
                        echo htmlspecialchars(
                            $especificaciones[
                                "ip"
                            ] ?: "-"
                        );
                        ?>

                    </span>

                </div>


                <!-- Tipo de conexión -->

                <div class="detail-item">

                    <span class="detail-label">
                        Tipo de conexión
                    </span>

                    <span class="detail-value">

                        <?php
                        echo htmlspecialchars(
                            $especificaciones[
                                "tipo_conexion"
                            ] ?: "-"
                        );
                        ?>

                    </span>

                </div>


                <!-- Nombre del equipo -->

                <div class="detail-item">

                    <span class="detail-label">
                        Nombre del equipo
                    </span>

                    <span class="detail-value">

                        <?php
                        echo htmlspecialchars(
                            $especificaciones[
                                "nombre_equipo"
                            ] ?: "-"
                        );
                        ?>

                    </span>

                </div>


                <!-- Batería -->

                <div class="detail-item">

                    <span class="detail-label">
                        Estado de batería
                    </span>

                    <span class="detail-value">

                        <?php
                        echo htmlspecialchars(
                            $especificaciones[
                                "estado_bateria"
                            ] ?: "-"
                        );
                        ?>

                    </span>

                </div>


            </div>

        </section>

    <?php endif; ?>


    <!-- =================================================
         ACCESORIOS
         ================================================= -->

    <section class="detail-section">

        <div class="detail-section-title">

            <h2>
                Accesorios
            </h2>

        </div>


        <?php if ($accesorios->num_rows > 0): ?>

            <div class="table-container">

                <table class="data-table">

                    <thead>

                        <tr>

                            <th>
                                Tipo
                            </th>

                            <th>
                                Descripción
                            </th>

                            <th>
                                Cantidad
                            </th>

                            <th>
                                Estado
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php while (
                            $accesorio =
                            $accesorios->fetch_assoc()
                        ): ?>

                            <tr>

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $accesorio["tipo"]
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $accesorio["descripcion"]
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $accesorio["cantidad"]
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $accesorio["estado"]
                                    );
                                    ?>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="empty-state">

                No hay accesorios registrados.

            </div>

        <?php endif; ?>

    </section>


    <!-- =================================================
         HISTORIAL DE MOVIMIENTOS
         ================================================= -->

    <section class="detail-section">

        <div class="detail-section-title">

            <h2>
                Historial de movimientos
            </h2>

        </div>


        <?php if ($movimientos->num_rows > 0): ?>

            <div class="table-container">

                <table class="data-table">

                    <thead>

                        <tr>

                            <th>
                                Fecha y hora
                            </th>

                            <th>
                                Movimiento
                            </th>

                            <th>
                                Cantidad
                            </th>

                            <th>
                                Responsable
                            </th>

                            <th>
                                Origen
                            </th>

                            <th>
                                Destino
                            </th>

                            <th>
                                Motivo
                            </th>

                            <th>
                                Usuario
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php while (
                            $movimiento =
                            $movimientos->fetch_assoc()
                        ): ?>

                            <tr>

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $movimiento["fecha_hora"]
                                    );
                                    ?>

                                </td>


                                <td>

                                    <span class="movement-badge">

                                        <?php
                                        echo htmlspecialchars(
                                            $movimiento[
                                                "tipo_movimiento"
                                            ]
                                        );
                                        ?>

                                    </span>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $movimiento["cantidad"]
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $movimiento[
                                            "responsable"
                                        ] ?: "-"
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $movimiento[
                                            "ubicacion_origen"
                                        ] ?: "-"
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $movimiento[
                                            "ubicacion_destino"
                                        ] ?: "-"
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $movimiento[
                                            "motivo"
                                        ] ?: "-"
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $movimiento[
                                            "usuario"
                                        ]
                                    );
                                    ?>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="empty-state">

                No hay movimientos registrados.

            </div>

        <?php endif; ?>

    </section>


</main>


<?php

require_once "../includes/footer.php";

?>