<?php
session_start();

// 1. Si no hay usuario pero viene token, crearlo
if (!isset($_SESSION['usuario']) && isset($_POST['token'])) {
    require_once 'AuthMiddleware.php';
    $auth = new AuthMiddleware();
    $usuario = $auth->crearSesionDesdeToken($_POST['token']);
    
    if ($usuario) {
        $_SESSION['usuario'] = $usuario;
    }
}elseif($_SESSION['usuario']['rol'] == "admin" || $_SESSION['usuario']['rol'] == "proyectos"){
    $tieneAcceso = true;
}else {
    echo "<h1>Acceso denegado</h1>";
    echo '<a href="dashboard.php">Volver</a>';
    exit;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - Sistema Inventarios</title>
    
    <!-- MDBootstrap CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700&display=swap" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/9.2.0/mdb.min.css" rel="stylesheet" />
    <style>
        .sidebar {
            color: white;
            min-height: 100vh;
            box-shadow: 3px 0 10px rgba(0,0,0,0.1);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            border-radius: 8px;
            margin: 2px 0;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white;
            font-weight: bold;
        }
        .main-content {
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .user-avatar {
            width: 50px;
            height: 50px;
            border: 3px solid rgba(255,255,255,0.3);
        }
        .config-card {
            border: none;
            border-radius: 15px;
            transition: transform 0.3s;
        }
        .config-card:hover {
            transform: translateY(-3px);
        }
        .nav-tabs .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }
    </style>
</head>
<body>
    
            <!-- SIDEBAR -->
            <?php include "menu.php"; ?>

            <!-- CONTENIDO PRINCIPAL -->
            <div class="main-content">
                <!-- Barra Superior -->
                <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
                    <div class="container-fluid">
                        <button class="sidebar-toggle-btn" id="customSidebarToggle">
                            <i class="fas fa-bars"></i>
                        </button>
                        
                        <span class="navbar-brand fw-bold text-primary">
                            <i class="fas fa-cog me-2"></i>Configuración del Sistema
                        </span>

                        <div class="d-flex align-items-center">
                            <span class="me-3 text-muted d-none d-md-block" id="currentTime"></span>
                            <div class="dropdown">
                                <button class="btn btn-link dropdown-toggle" type="button" 
                                        data-mdb-dropdown-init data-mdb-ripple-init>
                                    <i class="fas fa-user-circle fa-lg text-primary"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><h6 class="dropdown-header" id="dropdownUserName">Usuario</h6></li>
                                    <li><a class="dropdown-item" href="perfil.html"><i class="fas fa-user me-2"></i>Mi Perfil</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="#" id="dropdownLogoutBtn">
                                        <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                                    </a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </nav>

                <!-- Contenido -->
                <div class="container-fluid mt-4">
                    <div class="row">
                        <div class="col-12">
                            <!-- Pestañas de Configuración -->
                            <ul class="nav nav-tabs nav-justified mb-4" id="configTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="perfil-tab" data-mdb-toggle="tab" data-mdb-target="#perfil" type="button" role="tab" aria-controls="perfil" aria-selected="true">
                                        <i class="fas fa-user me-2"></i>Mi Perfil
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="categorias-tab" data-mdb-toggle="tab" data-mdb-target="#categorias" type="button" role="tab" aria-controls="categorias" aria-selected="false">
                                        <i class="fas fa-folder me-2"></i>Categorías
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="usuarios-tab" data-mdb-toggle="tab" data-mdb-target="#usuarios" type="button" role="tab" aria-controls="usuarios" aria-selected="false">
                                        <i class="fas fa-users me-2"></i>Usuarios
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="sistema-tab" data-mdb-toggle="tab" data-mdb-target="#sistema" type="button" role="tab" aria-controls="sistema" aria-selected="false">
                                        <i class="fas fa-sliders-h me-2"></i>Sistema
                                    </button>
                                </li>
                            </ul>

                            <!-- Contenido de las Pestañas -->
                            <div class="tab-content" id="configTabsContent">
                                <!-- Pestaña: Mi Perfil -->
                                <div class="tab-pane fade show active" id="perfil" role="tabpanel" aria-labelledby="perfil-tab">
                                    <div class="row">
                                        <div class="col-lg-8">
                                            <div class="card config-card">
                                                <div class="card-header bg-primary text-white">
                                                    <h5 class="mb-0">
                                                        <i class="fas fa-user-edit me-2"></i>Editar Perfil
                                                    </h5>
                                                </div>
                                                <div class="card-body">
                                                    <form id="formPerfil">
                                                        <div class="row mb-3">
                                                            <div class="col-md-6">
                                                                <div class="form-outline">
                                                                    <input type="text" id="profileNombre" class="form-control" required />
                                                                    <label class="form-label" for="profileNombre">Nombre Completo *</label>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="form-outline">
                                                                    <input type="email" id="profileEmail" class="form-control" required />
                                                                    <label class="form-label" for="profileEmail">Email *</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row mb-3">
                                                            <div class="col-md-6">
                                                                <div class="form-outline">
                                                                    <input type="text" id="profileUsername" class="form-control" required />
                                                                    <label class="form-label" for="profileUsername">Nombre de Usuario *</label>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="form-outline">
                                                                    <select class="form-select" id="profileRol" disabled>
                                                                        <option value="admin">Administrador</option>
                                                                        <option value="gestor">Gestor</option>
                                                                        <option value="consulta">Consulta</option>
                                                                    </select>
                                                                    <label class="form-label" for="profileRol">Rol</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="mb-3">
                                                            <button type="button" class="btn btn-outline-primary" onclick="toggleCambioPassword()">
                                                                <i class="fas fa-key me-2"></i>Cambiar Contraseña
                                                            </button>
                                                        </div>
                                                        <div id="cambioPassword" class="d-none">
                                                            <div class="row mb-3">
                                                                <div class="col-md-4">
                                                                    <div class="form-outline">
                                                                        <input type="password" id="currentPassword" class="form-control" />
                                                                        <label class="form-label" for="currentPassword">Contraseña Actual</label>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <div class="form-outline">
                                                                        <input type="password" id="newPassword" class="form-control" />
                                                                        <label class="form-label" for="newPassword">Nueva Contraseña</label>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <div class="form-outline">
                                                                        <input type="password" id="confirmPassword" class="form-control" />
                                                                        <label class="form-label" for="confirmPassword">Confirmar Contraseña</label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="d-flex justify-content-between">
                                                            <button type="button" class="btn btn-secondary" onclick="resetFormPerfil()">
                                                                <i class="fas fa-undo me-2"></i>Restablecer
                                                            </button>
                                                            <button type="submit" class="btn btn-success">
                                                                <i class="fas fa-save me-2"></i>Guardar Cambios
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="card config-card">
                                                <div class="card-header bg-info text-white">
                                                    <h5 class="mb-0">
                                                        <i class="fas fa-id-card me-2"></i>Foto de Perfil
                                                    </h5>
                                                </div>
                                                <div class="card-body text-center">
                                                    <div class="mb-3">
                                                        <img src="https://mdbootstrap.com/img/Photos/Avatars/avatar-8.jpg" 
                                                             class="rounded-circle shadow-4" 
                                                             style="width: 150px; height: 150px; object-fit: cover;" 
                                                             alt="Avatar" id="profileAvatar">
                                                    </div>
                                                    <div class="mb-3">
                                                        <input type="file" class="form-control" id="profileFoto" accept="image/*">
                                                        <div class="form-text">Formatos: JPG, PNG. Máx: 2MB</div>
                                                    </div>
                                                    <button class="btn btn-primary w-100" onclick="actualizarFoto()">
                                                        <i class="fas fa-upload me-2"></i>Actualizar Foto
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Pestaña: Categorías -->
                                <div class="tab-pane fade" id="categorias" role="tabpanel" aria-labelledby="categorias-tab">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="card config-card">
                                                <div class="card-header bg-success text-white">
                                                    <h5 class="mb-0">
                                                        <i class="fas fa-plus me-2"></i>Nueva Categoría
                                                    </h5>
                                                </div>
                                                <div class="card-body">
                                                    <form id="formCategoria">
                                                        <div class="mb-3">
                                                            <div class="form-outline">
                                                                <input type="text" id="categoriaNombre" class="form-control" required />
                                                                <label class="form-label" for="categoriaNombre">Nombre de la Categoría *</label>
                                                            </div>
                                                        </div>
                                                        <div class="mb-3">
                                                            <div class="form-outline">
                                                                <textarea class="form-control" id="categoriaDescripcion" rows="3"></textarea>
                                                                <label class="form-label" for="categoriaDescripcion">Descripción</label>
                                                            </div>
                                                        </div>
                                                        <div class="form-check form-switch mb-3">
                                                            <input class="form-check-input" type="checkbox" id="categoriaActiva" checked />
                                                            <label class="form-check-label" for="categoriaActiva">Categoría Activa</label>
                                                        </div>
                                                        <button type="submit" class="btn btn-success w-100">
                                                            <i class="fas fa-save me-2"></i>Guardar Categoría
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-8">
                                            <div class="card config-card">
                                                <div class="card-header bg-primary text-white">
                                                    <h5 class="mb-0">
                                                        <i class="fas fa-list me-2"></i>Lista de Categorías
                                                    </h5>
                                                </div>
                                                <div class="card-body">
                                                    <div class="table-responsive">
                                                        <table class="table table-hover">
                                                            <thead>
                                                                <tr>
                                                                    <th>Nombre</th>
                                                                    <th>Descripción</th>
                                                                    <th>Estado</th>
                                                                    <th>Etiquetas</th>
                                                                    <th>Acciones</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody id="categoriasBody">
                                                                <!-- Las categorías se cargarán aquí -->
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Pestaña: Usuarios -->
                                <div class="tab-pane fade" id="usuarios" role="tabpanel" aria-labelledby="usuarios-tab">
                                    <div class="card config-card">
                                        <div class="card-header bg-warning text-white d-flex justify-content-between align-items-center">
                                            <h5 class="mb-0">
                                                <i class="fas fa-users-cog me-2"></i>Gestión de Usuarios
                                            </h5>
                                            <button class="btn btn-light btn-sm" onclick="mostrarModalNuevoUsuario()">
                                                <i class="fas fa-user-plus me-1"></i>Nuevo Usuario
                                            </button>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th>Usuario</th>
                                                            <th>Nombre</th>
                                                            <th>Email</th>
                                                            <th>Rol</th>
                                                            <th>Estado</th>
                                                            <th>Último Acceso</th>
                                                            <th>Acciones</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="usuariosBody">
                                                        <!-- Los usuarios se cargarán aquí -->
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Pestaña: Sistema -->
                                <div class="tab-pane fade" id="sistema" role="tabpanel" aria-labelledby="sistema-tab">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="card config-card mb-4">
                                                <div class="card-header bg-info text-white">
                                                    <h5 class="mb-0">
                                                        <i class="fas fa-database me-2"></i>Configuración de Base de Datos
                                                    </h5>
                                                </div>
                                                <div class="card-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Backup Automático</label>
                                                        <select class="form-select" id="backupFrecuencia">
                                                            <option value="diario">Diario</option>
                                                            <option value="semanal" selected>Semanal</option>
                                                            <option value="mensual">Mensual</option>
                                                            <option value="desactivado">Desactivado</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <button class="btn btn-outline-primary w-100" onclick="realizarBackup()">
                                                            <i class="fas fa-download me-2"></i>Realizar Backup Ahora
                                                        </button>
                                                    </div>
                                                    <div class="mb-3">
                                                        <button class="btn btn-outline-success w-100" onclick="restaurarBackup()">
                                                            <i class="fas fa-upload me-2"></i>Restaurar Backup
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="card config-card mb-4">
                                                <div class="card-header bg-danger text-white">
                                                    <h5 class="mb-0">
                                                        <i class="fas fa-exclamation-triangle me-2"></i>Zona Peligrosa
                                                    </h5>
                                                </div>
                                                <div class="card-body">
                                                    <div class="mb-3">
                                                        <label class="form-label text-danger">Limpiar Base de Datos</label>
                                                        <select class="form-select" id="limpiarOpcion">
                                                            <option value="">Seleccionar opción...</option>
                                                            <option value="movimientos">Eliminar Movimientos Antiguos</option>
                                                            <option value="etiquetas_inactivas">Eliminar Etiquetas Inactivas</option>
                                                            <option value="todo">Limpiar Todo (Peligroso)</option>
                                                        </select>
                                                    </div>
                                                    <button class="btn btn-danger w-100" onclick="limpiarBaseDatos()" disabled id="btnLimpiar">
                                                        <i class="fas fa-broom me-2"></i>Ejecutar Limpieza
                                                    </button>
                                                    <div class="form-text text-danger">
                                                        <i class="fas fa-exclamation-circle me-1"></i>
                                                        Estas acciones no se pueden deshacer
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        

    <!-- MDBootstrap JS -->
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/9.2.0/mdb.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
    <script>
        let userData = null;
        let authToken = null;

        function updateUserInfo() {
            if (userData) {
                document.getElementById('userName').textContent = userData.nombre_completo;
                document.getElementById('userRole').textContent = userData.rol;
                document.getElementById('dropdownUserName').textContent = userData.nombre_completo;
                
                // Llenar formulario de perfil
                document.getElementById('profileNombre').value = userData.nombre_completo;
                document.getElementById('profileEmail').value = userData.email;
                document.getElementById('profileUsername').value = userData.username;
                document.getElementById('profileRol').value = userData.rol;
            }
        }

        

        function toggleCambioPassword() {
            const div = document.getElementById('cambioPassword');
            div.classList.toggle('d-none');
        }

        function resetFormPerfil() {
            updateUserInfo();
            document.getElementById('cambioPassword').classList.add('d-none');
            document.getElementById('currentPassword').value = '';
            document.getElementById('newPassword').value = '';
            document.getElementById('confirmPassword').value = '';
        }

        // Inicialización
        document.addEventListener('DOMContentLoaded', function() {
            authToken = localStorage.getItem('auth_token');
            const storedUser = localStorage.getItem('user');
            const tieneAcceso = <?php echo $tieneAcceso ? 'true' : 'false'; ?>;
            
            if (!authToken || !storedUser) {
                window.location.href = 'login.php';
                return;
            }

            // Caso 2: Hay token pero PHP no tiene acceso
            if (!tieneAcceso) {
                const formData = new FormData();
                formData.append('token', authToken);
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    // Después de enviar el token, recargar la página
                    window.location.reload();
                })
                .catch(error => {
                    console.error('Error:', error);
                    window.location.href = 'login.php';
                });
            }
            // Caso 3: Ya tiene acceso, todo bien
            else {
                console.log('Acceso confirmado');
                // Tu código JavaScript normal aquí
            }
            
            userData = JSON.parse(storedUser);
            updateUserInfo();
            updateCurrentTime();
            
            document.getElementById('logoutBtn').addEventListener('click', logout);
            document.getElementById('dropdownLogoutBtn').addEventListener('click', logout);
            
            // Habilitar botón de limpieza cuando se selecciona una opción
            document.getElementById('limpiarOpcion').addEventListener('change', function() {
                document.getElementById('btnLimpiar').disabled = !this.value;
            });
            
            setInterval(updateCurrentTime, 60000);
        });

        // Funciones de configuración (placeholder)
        function actualizarFoto() {
            alert('Función para actualizar foto - En desarrollo');
        }

        function realizarBackup() {
            alert('Realizando backup de la base de datos...');
        }

        function restaurarBackup() {
            alert('Restaurando backup...');
        }

        function limpiarBaseDatos() {
            const opcion = document.getElementById('limpiarOpcion').value;
            if (confirm(`¿Estás seguro de que quieres ejecutar: ${opcion}? Esta acción no se puede deshacer.`)) {
                alert(`Ejecutando limpieza: ${opcion}`);
            }
        }

        function logout() {
            localStorage.removeItem('auth_token');
            localStorage.removeItem('user');
            window.location.href = 'cerrar_sesion.php';
        }
    </script>
</body>
</html>