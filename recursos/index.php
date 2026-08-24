<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

$titulo = "Inventario";

require_once "../includes/header.php";
require_once "../includes/navbar.php";


// =====================================================
// FILTROS DEL LISTADO
// =====================================================

$buscar = trim((string) ($_GET["buscar"] ?? ""));
$filtro_categoria = trim((string) ($_GET["categoria"] ?? ""));
$filtro_estado = trim((string) ($_GET["estado"] ?? ""));
$filtro_situacion = trim((string) ($_GET["situacion"] ?? ""));

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

        u.nombre AS ubicacion,
        uh.nombre AS ubicacion_habitual

    FROM recursos r

    INNER JOIN categorias c
        ON r.categoria_id = c.id

    LEFT JOIN areas a
        ON r.area_id = a.id

    LEFT JOIN ubicaciones u
        ON r.ubicacion_id = u.id

    LEFT JOIN ubicaciones uh
        ON r.ubicacion_habitual_id = uh.id
";

$where = [];
$params = [];
$types = "";

if ($buscar !== "") {
    $where[] = "(
        r.codigo_inventario LIKE ?
        OR r.descripcion LIKE ?
        OR r.marca LIKE ?
        OR r.modelo LIKE ?
        OR r.numero_serie LIKE ?
    )";

    $texto_busqueda = "%" . $buscar . "%";

    $params = array_merge($params, [
        $texto_busqueda,
        $texto_busqueda,
        $texto_busqueda,
        $texto_busqueda,
        $texto_busqueda
    ]);

    $types .= "sssss";
}

if ($filtro_categoria !== "") {
    $where[] = "c.id = ?";
    $params[] = $filtro_categoria;
    $types .= "i";
}

if ($filtro_estado !== "") {
    $where[] = "r.estado = ?";
    $params[] = $filtro_estado;
    $types .= "s";
}

if ($filtro_situacion !== "") {
    $where[] = "r.situacion = ?";
    $params[] = $filtro_situacion;
    $types .= "s";
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY r.id DESC";

$stmt = $conexion->prepare($sql);

if (!$stmt) {
    die("Error al preparar la consulta de recursos: " . $conexion->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$resultado = $stmt->get_result();
$stmt->close();

$estado_clases = [
    "EXCELENTE" => "badge-excelente",
    "BUENO" => "badge-bueno",
    "REGULAR" => "badge-regular",
    "DEFICIENTE" => "badge-deficiente",
    "MALOGRADO" => "badge-malogrado",
    "DADO DE BAJA" => "badge-dado-baja"
];

$situacion_clases = [
    "DISPONIBLE" => "badge-disponible",
    "PRESTADO" => "badge-prestado",
    "DADO DE BAJA" => "badge-dado-baja",
    "EN MANTENIMIENTO" => "badge-mantenimiento",
    "EN USO" => "badge-en-uso"
];

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

        <form method="GET" class="filter-grid">

            <div class="filter-group">

                <label for="buscar">
                    Buscar
                </label>

                <input
                    type="text"
                    id="buscar"
                    name="buscar"
                    class="form-control"
                    value="<?php echo htmlspecialchars($buscar); ?>"
                    placeholder="Código, descripción, marca, modelo o serie..."
                >

            </div>

            <div class="filter-group">

                <label for="categoria">
                    Categoría
                </label>

                <select
                    id="categoria"
                    name="categoria"
                    class="form-control"
                >
                    <option value="">
                        Todas
                    </option>

                    <?php
                    $categorias = $conexion->query(
                        "SELECT id, nombre FROM categorias WHERE estado = 'ACTIVO' ORDER BY nombre"
                    );

                    if ($categorias):
                        while ($categoria = $categorias->fetch_assoc()):
                    ?>
                            <option
                                value="<?php echo (int) $categoria["id"]; ?>"
                                <?php echo $filtro_categoria === (string) $categoria["id"] ? "selected" : ""; ?>
                            >
                                <?php echo htmlspecialchars($categoria["nombre"]); ?>
                            </option>
                    <?php
                        endwhile;
                    endif;
                    ?>
                </select>

            </div>

            <div class="filter-group">

                <label for="estado">
                    Estado
                </label>

                <select
                    id="estado"
                    name="estado"
                    class="form-control"
                >
                    <option value="">
                        Todos
                    </option>
                    <option value="EXCELENTE" <?php echo $filtro_estado === "EXCELENTE" ? "selected" : ""; ?>>EXCELENTE</option>
                    <option value="BUENO" <?php echo $filtro_estado === "BUENO" ? "selected" : ""; ?>>BUENO</option>
                    <option value="REGULAR" <?php echo $filtro_estado === "REGULAR" ? "selected" : ""; ?>>REGULAR</option>
                    <option value="DEFICIENTE" <?php echo $filtro_estado === "DEFICIENTE" ? "selected" : ""; ?>>DEFICIENTE</option>
                    <option value="MALOGRADO" <?php echo $filtro_estado === "MALOGRADO" ? "selected" : ""; ?>>MALOGRADO</option>
                    <option value="DADO DE BAJA" <?php echo $filtro_estado === "DADO DE BAJA" ? "selected" : ""; ?>>DADO DE BAJA</option>
                </select>

            </div>

            <div class="filter-group">

                <label for="situacion">
                    Situación
                </label>

                <select
                    id="situacion"
                    name="situacion"
                    class="form-control"
                >
                    <option value="">
                        Todas
                    </option>
                    <option value="DISPONIBLE" <?php echo $filtro_situacion === "DISPONIBLE" ? "selected" : ""; ?>>DISPONIBLE</option>
                    <option value="PRESTADO" <?php echo $filtro_situacion === "PRESTADO" ? "selected" : ""; ?>>PRESTADO</option>
                    <option value="DADO DE BAJA" <?php echo $filtro_situacion === "DADO DE BAJA" ? "selected" : ""; ?>>DADO DE BAJA</option>
                    <option value="EN MANTENIMIENTO" <?php echo $filtro_situacion === "EN MANTENIMIENTO" ? "selected" : ""; ?>>EN MANTENIMIENTO</option>
                </select>

            </div>

            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">
                    Filtrar
                </button>

                <a href="index.php" class="btn btn-secondary">
                    Limpiar
                </a>
            </div>

        </form>

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
                            Ubicación actual
                        </th>

                        <th>
                            Ubicación habitual
                        </th>

                        <th>
                            Cantidad
                        </th>

                        <th>
                            Situación
                        </th>

                        <th>
                            Estado
                        </th>

                        <th>
                            Acción
                        </th>

                    </tr>

                </thead>

                <tbody>

                    <?php if ($resultado && $resultado->num_rows > 0): ?>

                        <?php while ($recurso = $resultado->fetch_assoc()): ?>

                            <?php
                            $estado_valor = strtoupper((string) ($recurso["estado"] ?? "BUENO"));
                            $situacion_valor = strtoupper((string) ($recurso["situacion"] ?? "DISPONIBLE"));

                            $estado_clase = $estado_clases[$estado_valor] ?? "badge-bueno";
                            $situacion_clase = $situacion_clases[$situacion_valor] ?? "badge-disponible";
                            ?>

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
                                        $recurso["ubicacion"] ?? "-"
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $recurso["ubicacion_habitual"] ?? "-"
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo $recurso["cantidad"];
                                    ?>

                                </td>


                                <td>

                                    <span class="situation-badge <?php echo $situacion_clase; ?>">

                                        <?php
                                        echo htmlspecialchars(
                                            $recurso["situacion"]
                                        );
                                        ?>

                                    </span>

                                </td>


                                <td>

                                    <span class="status-badge <?php echo $estado_clase; ?>">

                                        <?php
                                        echo htmlspecialchars(
                                            $recurso["estado"]
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
                                colspan="10"
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