<!-- menu.html -->
<nav id="sidebarMenu" class="col-md-3 col-lg-2 bg-primary sidebar collapse collapse-horizontal show">
    <div class="position-sticky pt-3">
        <!-- Logo y Título -->
        <div class="text-center p-4">
            <img src="img/logo.png" alt="Logo Synertech" class="img-fluid">
            <hr>
            <h5 class="fw-bold">Sistema de Inventarios</h5>
            <small class="text-white-50">Panel de Control</small>
        </div>

        <hr class="text-white-50">

        <!-- Menú de Navegación -->
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="inventario.php">
                    <i class="fas fa-warehouse me-2"></i>
                    Inventario
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="nueva-etiqueta.php">
                    <i class="fas fa-tag me-2"></i>
                    Nueva Etiqueta
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="movimientos.php">
                    <i class="fas fa-exchange-alt me-2"></i>
                    Movimientos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="categorias.php">
                    <i class="fas fa-folder me-2"></i>
                    Categorías
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="proyectos.php">
                    <i class="fas fa-object-ungroup me-2"></i>
                    Proyectos
                </a>
            </li>
        </ul>

        <hr class="text-white-50">

        <!-- Menú Sistema -->
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="configuracion.php">
                    <i class="fas fa-cog me-2"></i>
                    Configuración
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-warning" href="#" id="logoutBtn">
                    <i class="fas fa-sign-out-alt me-2"></i>
                    Cerrar Sesión
                </a>
            </li>
        </ul>

        <!-- Información del Usuario -->
        <div class="mt-5 p-3 bg-dark bg-opacity-25 rounded">
            <div class="d-flex align-items-center">
                <img src="https://mdbootstrap.com/img/Photos/Avatars/avatar-8.jpg" 
                     class="user-avatar rounded-circle me-3" 
                     alt="Usuario">
                <div>
                    <h6 class="mb-0 fw-bold" id="userName">Cargando...</h6>
                    <small class="text-white-50" id="userRole">Cargando...</small>
                </div>
            </div>
        </div>
    </div>
</nav>