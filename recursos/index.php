<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

$titulo = "Inventario";

require_once "../includes/header.php";
require_once "../includes/navbar.php";


// =====================================================
// CONSULTA DE RECURSOS
// =====================================================

$sql = "
    SELECT
        r.id,
        r.codigo_inventario,
        r.descripcion,
        r.marca,
        r.modelo,
        r.numero_serie,
        r.cantidad,
        r.estado,
        r.situacion,

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

    ORDER BY r.id DESC
";

$resultado = $conexion->query($sql);

?>

<main class="main-content">

    <!-- =============================================
         ENCABEZADO
         ============================================= -->

    <div class="page-header">

        <div>

            <h1>Inventario</h1>

            <p>
                Consulta y administra los recursos del colegio.
            </p>

        </div>

        <a
            href="crear.php"
            class="btn btn-primary"
        >
            + Nuevo recurso
        </a>

    </div>


    <!-- =============================================
         FILTROS
         ============================================= -->

    <section class="filter-section">

        <div class="filter-group">

            <label for="buscar">
                Buscar
            </label>

            <input
                type="text"
                id="buscar"
                class="form-control"
                placeholder="Código, descripción, marca, modelo o serie..."
            >

        </div>

    </section>


    <!-- =============================================
         TABLA
         ============================================= -->

    <section class="table-section">

        <div class="table-container">

            <table
                class="data-table"
                id="tablaRecursos"
            >

                <thead>

                    <tr>

                        <th>
                            Código
                        </th>

                        <th>
                            Recurso
                        </th>

                        <th>
                            Categoría
                        </th>

                        <th>
                            Área
                        </th>

                        <th>
                            Ubicación
                        </th>

                        <th>
                            Cantidad
                        </th>

                        <th>
                            Estado
                        </th>

                        <th>
                            Situación
                        </th>

                        <th>
                            Acción
                        </th>

                    </tr>

                </thead>

                <tbody>

                    <?php if ($resultado && $resultado->num_rows > 0): ?>

                        <?php while ($recurso = $resultado->fetch_assoc()): ?>

                            <tr>

                                <td>

                                    <strong>
                                        <?php
                                        echo htmlspecialchars(
                                            $recurso["codigo_inventario"]
                                        );
                                        ?>
                                    </strong>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $recurso["descripcion"]
                                    );
                                    ?>

                                    <?php if (!empty($recurso["marca"])): ?>

                                        <br>

                                        <small>

                                            <?php
                                            echo htmlspecialchars(
                                                $recurso["marca"]
                                            );
                                            ?>

                                            <?php if (!empty($recurso["modelo"])): ?>

                                                -
                                                <?php
                                                echo htmlspecialchars(
                                                    $recurso["modelo"]
                                                );
                                                ?>

                                            <?php endif; ?>

                                        </small>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $recurso["categoria"]
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $recurso["area"] ?? "-"
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $recurso["ubicacion"] ?? "-"
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo $recurso["cantidad"];
                                    ?>

                                </td>


                                <td>

                                    <span class="status-badge">

                                        <?php
                                        echo htmlspecialchars(
                                            $recurso["estado"]
                                        );
                                        ?>

                                    </span>

                                </td>


                                <td>

                                    <span class="situation-badge">

                                        <?php
                                        echo htmlspecialchars(
                                            $recurso["situacion"]
                                        );
                                        ?>

                                    </span>

                                </td>


                                <td>

                                    <a
                                        href="ver.php?id=<?php echo $recurso["id"]; ?>"
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
                                class="empty-state"
                            >

                                No hay recursos registrados.

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