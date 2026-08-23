<nav class="navbar">

    <div class="navbar-brand">
        Inventario Colegio
    </div>

    <div class="navbar-user">

        <span>
            <?php echo htmlspecialchars($_SESSION["nombre"]); ?>
        </span>

        <a href="/inventario_colegio/logout.php">
            Cerrar sesión
        </a>

    </div>

</nav>


<aside class="sidebar">

    <ul>

        <li>
            <a href="/inventario_colegio/dashboard/index.php">
                🏠 Dashboard
            </a>
        </li>

        <li>
            <a href="/inventario_colegio/recursos/index.php">
                📦 Inventario
            </a>
        </li>

        <li>
            <a href="/inventario_colegio/movimientos/index.php">
                🔄 Movimientos
            </a>
        </li>

        <li>
            <a href="/inventario_colegio/mantenimiento/index.php">
                🔧 Mantenimiento
            </a>
        </li>

        <li>
            <a href="/inventario_colegio/reportes/inventario.php">
                📊 Reportes
            </a>
        </li>

        <li>
            <a href="/inventario_colegio/categorias/index.php">
                🗂️ Categorías
            </a>
        </li>

        <li>
            <a href="/inventario_colegio/areas/index.php">
                🏫 Áreas
            </a>
        </li>

        <li>
            <a href="/inventario_colegio/ubicaciones/index.php">
                📍 Ubicaciones
            </a>
        </li>

        <li>
            <a href="/inventario_colegio/usuarios/index.php">
                👤 Usuarios
            </a>
        </li>

    </ul>

</aside>