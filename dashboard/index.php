<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

$titulo = "Dashboard";

require_once "../includes/header.php";
require_once "../includes/navbar.php";


// =====================================================
// TOTAL DE RECURSOS
// =====================================================

$sql_total_recursos = "
    SELECT COUNT(*) AS total
    FROM recursos
";

$resultado = $conexion->query($sql_total_recursos);
$total_recursos = $resultado->fetch_assoc()["total"];


// =====================================================
// TOTAL DE UNIDADES
// =====================================================

$sql_total_unidades = "
    SELECT COALESCE(SUM(cantidad), 0) AS total
    FROM recursos
    WHERE situacion <> 'DADO DE BAJA'
";

$resultado = $conexion->query($sql_total_unidades);
$total_unidades = $resultado->fetch_assoc()["total"];


// =====================================================
// RECURSOS DISPONIBLES
// =====================================================

$sql_disponibles = "
    SELECT COALESCE(SUM(cantidad), 0) AS total
    FROM recursos
    WHERE situacion = 'DISPONIBLE'
";

$resultado = $conexion->query($sql_disponibles);
$disponibles = $resultado->fetch_assoc()["total"];


// =====================================================
// RECURSOS PRESTADOS
// =====================================================

$sql_prestados = "
    SELECT COALESCE(SUM(cantidad), 0) AS total
    FROM recursos
    WHERE situacion = 'PRESTADO'
";

$resultado = $conexion->query($sql_prestados);
$prestados = $resultado->fetch_assoc()["total"];


// =====================================================
// RECURSOS EN MANTENIMIENTO
// =====================================================

$sql_mantenimiento = "
    SELECT COALESCE(SUM(cantidad), 0) AS total
    FROM recursos
    WHERE situacion = 'EN MANTENIMIENTO'
";

$resultado = $conexion->query($sql_mantenimiento);
$en_mantenimiento = $resultado->fetch_assoc()["total"];


// =====================================================
// RECURSOS MALOGRADOS
// =====================================================

$sql_malogrados = "
    SELECT COALESCE(SUM(cantidad), 0) AS total
    FROM recursos
    WHERE estado = 'MALOGRADO'
";

$resultado = $conexion->query($sql_malogrados);
$malogrados = $resultado->fetch_assoc()["total"];


// =====================================================
// ÚLTIMOS MOVIMIENTOS
// =====================================================

$sql_movimientos = "
    SELECT
        m.id,
        m.tipo_movimiento,
        m.cantidad,
        m.fecha_hora,
        m.responsable,
        r.codigo_inventario,
        r.descripcion,
        u.usuario
    FROM movimientos m

    INNER JOIN recursos r
        ON m.recurso_id = r.id

    INNER JOIN usuarios u
        ON m.usuario_id = u.id

    ORDER BY m.fecha_hora DESC

    LIMIT 10
";

$movimientos = $conexion->query($sql_movimientos);

?>

<main class="main-content">

    <div class="dashboard-header">

        <div>

            <h1>Dashboard</h1>

            <p>
                Bienvenido,
                <?php echo htmlspecialchars($_SESSION["nombre"]); ?>.
            </p>

        </div>

    </div>


    <!-- =============================================
         TARJETAS DE RESUMEN
         ============================================= -->

    <section class="dashboard-cards">


        <div class="dashboard-card">

            <div class="card-title">
                Recursos registrados
            </div>

            <div class="card-number">
                <?php echo $total_recursos; ?>
            </div>

            <div class="card-description">
                Registros de inventario
            </div>

        </div>


        <div class="dashboard-card">

            <div class="card-title">
                Unidades
            </div>

            <div class="card-number">
                <?php echo $total_unidades; ?>
            </div>

            <div class="card-description">
                Unidades activas
            </div>

        </div>


        <div class="dashboard-card">

            <div class="card-title">
                Disponibles
            </div>

            <div class="card-number">
                <?php echo $disponibles; ?>
            </div>

            <div class="card-description">
                Listos para utilizar
            </div>

        </div>


        <div class="dashboard-card">

            <div class="card-title">
                Prestados
            </div>

            <div class="card-number">
                <?php echo $prestados; ?>
            </div>

            <div class="card-description">
                Actualmente prestados
            </div>

        </div>


        <div class="dashboard-card">

            <div class="card-title">
                Mantenimiento
            </div>

            <div class="card-number">
                <?php echo $en_mantenimiento; ?>
            </div>

            <div class="card-description">
                En mantenimiento
            </div>

        </div>


        <div class="dashboard-card">

            <div class="card-title">
                Malogrados
            </div>

            <div class="card-number">
                <?php echo $malogrados; ?>
            </div>

            <div class="card-description">
                Recursos malogrados
            </div>

        </div>

    </section>


    <!-- =============================================
         ÚLTIMOS MOVIMIENTOS
         ============================================= -->

    <section class="dashboard-section">

        <div class="section-header">

            <h2>
                Últimos movimientos
            </h2>

            <a href="../movimientos/index.php">
                Ver todos
            </a>

        </div>


        <?php if ($movimientos && $movimientos->num_rows > 0): ?>

            <div class="table-container">

                <table class="data-table">

                    <thead>

                        <tr>

                            <th>Fecha y hora</th>
                            <th>Movimiento</th>
                            <th>Recurso</th>
                            <th>Responsable</th>
                            <th>Usuario</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php while ($movimiento = $movimientos->fetch_assoc()): ?>

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
                                            $movimiento["tipo_movimiento"]
                                        );
                                        ?>

                                    </span>

                                </td>

                                <td>

                                    <strong>
                                        <?php
                                        echo htmlspecialchars(
                                            $movimiento["codigo_inventario"]
                                        );
                                        ?>
                                    </strong>

                                    <br>

                                    <small>
                                        <?php
                                        echo htmlspecialchars(
                                            $movimiento["descripcion"]
                                        );
                                        ?>
                                    </small>

                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $movimiento["responsable"] ?? "-"
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $movimiento["usuario"]
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

                <p>
                    Todavía no existen movimientos registrados.
                </p>

            </div>

        <?php endif; ?>

    </section>

</main>


<?php

require_once "../includes/footer.php";

?>