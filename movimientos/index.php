<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

$titulo = "Movimientos";


// =====================================================
// FILTRO POR TIPO
// =====================================================

$tipo = $_GET["tipo"] ?? "";


// =====================================================
// CONSULTA BASE
// =====================================================

$sql = "
    SELECT
        m.id,
        m.recurso_id,
        r.codigo_inventario,
        r.descripcion AS recurso,

        m.tipo_movimiento,
        m.cantidad,
        m.fecha_hora,

        u.usuario,
        m.responsable,

        origen.nombre AS ubicacion_origen,
        destino.nombre AS ubicacion_destino,

        m.motivo,
        m.observaciones

    FROM movimientos m

    INNER JOIN recursos r
        ON m.recurso_id = r.id

    LEFT JOIN usuarios u
        ON m.usuario_id = u.id

    LEFT JOIN ubicaciones origen
        ON m.ubicacion_origen_id = origen.id

    LEFT JOIN ubicaciones destino
        ON m.ubicacion_destino_id = destino.id
";


// =====================================================
// FILTRO
// =====================================================

if (
    $tipo === "ENTRADA"
    || $tipo === "SALIDA"
    || $tipo === "PRESTAMO"
    || $tipo === "DEVOLUCION"
) {

    $sql .= "
        WHERE m.tipo_movimiento = ?
    ";

}


// =====================================================
// ORDEN
// =====================================================

$sql .= "
    ORDER BY m.fecha_hora DESC, m.id DESC
";


// =====================================================
// EJECUTAR
// =====================================================

if (
    $tipo === "ENTRADA"
    || $tipo === "SALIDA"
    || $tipo === "PRESTAMO"
    || $tipo === "DEVOLUCION"
) {

    $stmt = $conexion->prepare($sql);

    if (!$stmt) {

        die(
            "Error al preparar la consulta: "
            . $conexion->error
        );

    }

    $stmt->bind_param(
        "s",
        $tipo
    );

    $stmt->execute();

    $movimientos = $stmt->get_result();

} else {

    $movimientos = $conexion->query($sql);

    if (!$movimientos) {

        die(
            "Error al consultar movimientos: "
            . $conexion->error
        );

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
                Movimientos
            </h1>

            <p>
                Historial de entradas, salidas,
                préstamos y devoluciones.
            </p>

        </div>


        <div>

            <a
                href="crear.php"
                class="btn btn-primary"
            >
                + Registrar movimiento
            </a>

        </div>

    </div>


    <!-- =================================================
         FILTROS
         ================================================= -->

    <section class="form-section">


        <div class="form-section-title">

            <h2>
                Filtrar movimientos
            </h2>

        </div>


        <form
            method="GET"
            class="form-grid"
        >


            <div class="form-group">

                <label for="tipo">
                    Tipo de movimiento
                </label>

                <select
                    id="tipo"
                    name="tipo"
                    class="form-control"
                >

                    <option value="">
                        Todos los movimientos
                    </option>


                    <option
                        value="ENTRADA"
                        <?php
                        echo $tipo === "ENTRADA"
                            ? "selected"
                            : "";
                        ?>
                    >
                        Entradas
                    </option>


                    <option
                        value="SALIDA"
                        <?php
                        echo $tipo === "SALIDA"
                            ? "selected"
                            : "";
                        ?>
                    >
                        Salidas
                    </option>


                    <option
                        value="PRESTAMO"
                        <?php
                        echo $tipo === "PRESTAMO"
                            ? "selected"
                            : "";
                        ?>
                    >
                        Préstamos
                    </option>


                    <option
                        value="DEVOLUCION"
                        <?php
                        echo $tipo === "DEVOLUCION"
                            ? "selected"
                            : "";
                        ?>
                    >
                        Devoluciones
                    </option>

                </select>

            </div>


            <div
                class="form-group"
                style="display:flex;align-items:end;"
            >

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Filtrar
                </button>


                <a
                    href="index.php"
                    class="btn btn-small"
                    style="margin-left:10px;"
                >
                    Limpiar
                </a>

            </div>


        </form>

    </section>


    <!-- =================================================
         TABLA
         ================================================= -->

    <section class="table-section">


        <div class="table-container">

            <table
                class="data-table"
                id="tablaMovimientos"
            >

                <thead>

                    <tr>

                        <th>
                            Fecha y hora
                        </th>

                        <th>
                            Tipo
                        </th>

                        <th>
                            Recurso
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
                            Acción
                        </th>

                    </tr>

                </thead>


                <tbody>


                    <?php if (
                        $movimientos->num_rows > 0
                    ): ?>


                        <?php while (
                            $movimiento =
                            $movimientos->fetch_assoc()
                        ): ?>


                            <tr>


                                <!-- Fecha -->

                                <td>

                                    <?php

                                    echo date(
                                        "d/m/Y H:i:s",
                                        strtotime(
                                            $movimiento["fecha_hora"]
                                        )
                                    );

                                    ?>

                                </td>


                                <!-- Tipo -->

                                <td>

                                    <?php

                                    $tipoMovimiento =
                                        $movimiento[
                                            "tipo_movimiento"
                                        ];

                                    ?>


                                    <?php if (
                                        $tipoMovimiento
                                        ===
                                        "ENTRADA"
                                    ): ?>

                                        <span class="badge badge-success">
                                            ENTRADA
                                        </span>

                                    <?php elseif (
                                        $tipoMovimiento
                                        ===
                                        "SALIDA"
                                    ): ?>

                                        <span class="badge badge-danger">
                                            SALIDA
                                        </span>

                                    <?php elseif (
                                        $tipoMovimiento
                                        ===
                                        "PRESTAMO"
                                    ): ?>

                                        <span class="badge badge-warning">
                                            PRÉSTAMO
                                        </span>

                                    <?php elseif (
                                        $tipoMovimiento
                                        ===
                                        "DEVOLUCION"
                                    ): ?>

                                        <span class="badge badge-info">
                                            DEVOLUCIÓN
                                        </span>

                                    <?php else: ?>

                                        <span class="badge">
                                            <?php
                                            echo htmlspecialchars(
                                                $tipoMovimiento
                                            );
                                            ?>
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- Recurso -->

                                <td>

                                    <strong>

                                        <?php
                                        echo htmlspecialchars(
                                            $movimiento[
                                                "codigo_inventario"
                                            ]
                                        );
                                        ?>

                                    </strong>


                                    <br>


                                    <small>

                                        <?php
                                        echo htmlspecialchars(
                                            $movimiento[
                                                "recurso"
                                            ]
                                        );
                                        ?>

                                    </small>

                                </td>


                                <!-- Cantidad -->

                                <td>

                                    <?php
                                    echo (int)
                                        $movimiento[
                                            "cantidad"
                                        ];
                                    ?>

                                </td>


                                <!-- Responsable -->

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $movimiento[
                                            "responsable"
                                        ]
                                    );
                                    ?>

                                    <br>

                                    <small>

                                        Usuario:

                                        <?php
                                        echo htmlspecialchars(
                                            $movimiento[
                                                "usuario"
                                            ] ?? "-"
                                        );
                                        ?>

                                    </small>

                                </td>


                                <!-- Origen -->

                                <td>

                                    <?php

                                    echo $movimiento[
                                        "ubicacion_origen"
                                    ]
                                        ? htmlspecialchars(
                                            $movimiento[
                                                "ubicacion_origen"
                                            ]
                                        )
                                        : "—";

                                    ?>

                                </td>


                                <!-- Destino -->

                                <td>

                                    <?php

                                    echo $movimiento[
                                        "ubicacion_destino"
                                    ]
                                        ? htmlspecialchars(
                                            $movimiento[
                                                "ubicacion_destino"
                                            ]
                                        )
                                        : "—";

                                    ?>

                                </td>


                                <!-- Motivo -->

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $movimiento[
                                            "motivo"
                                        ]
                                    );
                                    ?>

                                </td>


                                <!-- Acción -->

                                <td>

                                    <a
                                        href="ver.php?id=<?php echo $movimiento["id"]; ?>"
                                        class="btn btn-small"
                                    >
                                        Ver
                                    </a>

                                </td>


                            </tr>


                        <?php endwhile; ?>


                    <?php else: ?>


                        <tr>

                            <td
                                colspan="9"
                                style="text-align:center;"
                            >

                                No existen movimientos
                                registrados.

                            </td>

                        </tr>


                    <?php endif; ?>


                </tbody>

            </table>

        </div>


    </section>


</main>


<?php

require_once "../includes/footer.php";

?>