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
    <title>Dashboard - Sistema Inventarios</title>
    
    <!-- MDBootstrap CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700&display=swap" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/9.2.0/mdb.min.css" rel="stylesheet" />
    <style>
        
        .main-content {
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .stat-card {
            transition: transform 0.3s;
            border: none;
            border-radius: 15px;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .module-card {
            border: none;
            border-radius: 15px;
            transition: all 0.3s;
            cursor: pointer;
        }
        .module-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .user-avatar {
            width: 50px;
            height: 50px;
            border: 3px solid rgba(255,255,255,0.3);
        }
        .welcome-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            color: white;
        }
    </style>
</head>
<body>
    
            <!-- ========================================= -->
            <!-- SIDEBAR - MENÚ LATERAL -->
            <!-- ========================================= -->
            
            <?php include "menu.php" ?>

            <!-- ========================================= -->
            <!-- CONTENIDO PRINCIPAL -->
            <!-- ========================================= -->
            <div class="main-content">
                
                <!-- Barra Superior -->
                <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
                    <div class="container-fluid">
                        <button class="sidebar-toggle-btn" id="customSidebarToggle">
                            <i class="fas fa-bars"></i>
                        </button>
                        
                            
                        <span class="navbar-brand fw-bold text-primary">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
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
                                    <li><a class="dropdown-item" href="perfil.php"><i class="fas fa-user me-2"></i>Mi Perfil</a></li>
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
                    <!-- Banner de Bienvenida -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="welcome-banner p-4">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h2 class="fw-bold mb-2" id="welcomeMessage">¡Bienvenido!</h2>
                                        <p class="mb-0">Gestiona tu inventario de manera eficiente y organizada</p>
                                    </div>
                                    <div class="col-md-4 text-md-end">
                                        <i class="fas fa-chart-line fa-4x opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tarjetas de Estadísticas -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="stat-card card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h5 class="card-title">Etiquetas Totales</h5>
                                            <h2 class="mb-0" id="totalEtiquetas">0</h2>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-tags fa-2x opacity-75"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="stat-card card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h5 class="card-title">Entradas Hoy</h5>
                                            <h2 class="mb-0" id="entradasHoy">0</h2>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-arrow-up fa-2x opacity-75"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="stat-card card bg-danger text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h5 class="card-title">Salidas Hoy</h5>
                                            <h2 class="mb-0" id="salidasHoy">0</h2>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-arrow-down fa-2x opacity-75"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="stat-card card bg-warning text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h5 class="card-title">Stock Bajo</h5>
                                            <h2 class="mb-0" id="stockBajo">0</h2>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-exclamation-triangle fa-2x opacity-75"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Módulos Principales -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h4 class="fw-bold mb-3">
                                <i class="fas fa-th-large me-2 text-primary"></i>Módulos Principales
                            </h4>
                        </div>

                        <div class="col-xl-6 mb-4">
                            <div class="module-card card bg-white" onclick="navigate('inventario.php')">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary bg-opacity-10 p-3 rounded me-4">
                                            <i class="fas fa-warehouse fa-2x text-primary"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="card-title fw-bold text-primary">📦 Inventario</h5>
                                            <p class="card-text text-muted mb-3">
                                                Consulta y gestiona todo el inventario de etiquetas. 
                                                Visualiza stock, precios y categorías.
                                            </p>
                                            <button class="btn btn-primary btn-sm">
                                                Acceder al Inventario
                                                <i class="fas fa-arrow-right ms-2"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-6 mb-4">
                            <div class="module-card card bg-white" onclick="navigate('nueva-etiqueta.php')">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-success bg-opacity-10 p-3 rounded me-4">
                                            <i class="fas fa-tag fa-2x text-success"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="card-title fw-bold text-success">🏷️ Nueva Etiqueta</h5>
                                            <p class="card-text text-muted mb-3">
                                                Registra nuevas etiquetas en el sistema. 
                                                Agrega fotos, categorías y detalles.
                                            </p>
                                            <button class="btn btn-success btn-sm">
                                                Crear Etiqueta
                                                <i class="fas fa-plus ms-2"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones Rápidas -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white">
                                    <h5 class="fw-bold mb-0">
                                        <i class="fas fa-bolt me-2 text-warning"></i>Acciones Rápidas
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-3 col-6">
                                            <button class="btn btn-outline-primary w-100 h-100 py-3" 
                                                    onclick="navigate('inventario.php')">
                                                <i class="fas fa-search fa-2x mb-2"></i><br>
                                                Buscar Etiqueta
                                            </button>
                                        </div>
                                        <div class="col-md-3 col-6">
                                            <button class="btn btn-outline-success w-100 h-100 py-3"
                                                    onclick="navigate('nueva-etiqueta.php')">
                                                <i class="fas fa-tag fa-2x mb-2"></i><br>
                                                Etiqueta Rápida
                                            </button>
                                        </div>
                                        <div class="col-md-3 col-6">
                                            <button class="btn btn-outline-info w-100 h-100 py-3"
                                                    onclick="navigate('movimientos.php')">
                                                <i class="fas fa-exchange-alt fa-2x mb-2"></i><br>
                                                Ver Movimientos
                                            </button>
                                        </div>
                                        <div class="col-md-3 col-6">
                                            <button class="btn btn-outline-danger w-100 h-100 py-3"
                                                    onclick="logout()">
                                                <i class="fas fa-sign-out-alt fa-2x mb-2"></i><br>
                                                Cerrar Sesión
                                            </button>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    
    <script src="js/script.js"></script>
    <script>
        // =============================================
        // VARIABLES GLOBALES
        // =============================================
        let userData = null;
        let authToken = null;
        let etiquetas = [];
        let entradasHoy = 0;
        let salidasHoy = 0;
        // =============================================
        // FUNCIONES DE NAVEGACIÓN
        // =============================================
        
        async function cargarEtiquetas() {
            try {
                const formData = new FormData();
                formData.append('peticion', 'listar');
                formData.append('token', authToken);

                const response = await fetch('controllers/etiquetas.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.exito) {
                    etiquetas = result.data;
                    
                } else {
                    throw new Error(result.msj);
                }
            } catch (error) {
                console.error('Error cargando etiquetas:', error);
                mostrarMensaje('error', 'Error al cargar las etiquetas');
            }
        }

        async function movimientosHoy() {
            try {
                const formData = new FormData();
                formData.append('peticion', 'historial');
                formData.append('fecha', new Date().toISOString().slice(0, 10));
                formData.append('token', authToken);

                const response = await fetch('controllers/movimientos.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.exito) {
                    const movimientos = result.data;
                    const hoy = new Date().toISOString().slice(0, 10);
                    entradasHoy = movimientos.filter(m => m.tipo === 'entrada' && m.fecha_movimiento.startsWith(hoy)).length;
                    salidasHoy = movimientos.filter(m => m.tipo === 'salida' && m.fecha_movimiento.startsWith(hoy)).length;
                    
                } else {
                    throw new Error(result.msj);
                }
            } catch (error) {
                console.error('Error cargando movimientos:', error);
                mostrarMensaje('error', 'Error al cargar los movimientos de hoy');
            }
        }
        
        async function loadDashboardStats() {
            try {

                const total = etiquetas.length;
                const stockDisponible = etiquetas.filter(e => e.stock_actual > 0).length;
                const stockBajo = etiquetas.filter(e => e.stock_actual <= e.stock_minimo && e.stock_actual > 0).length;
                const sinStock = etiquetas.filter(e => e.stock_actual === 0).length;
                const categorias = new Set(etiquetas.map(e => e.categoria_id)).size;

                

                console.log('📊 Cargando estadísticas del dashboard...');
                
                // Simular carga de datos (reemplazar con API real)
                setTimeout(() => {
                    document.getElementById('totalEtiquetas').textContent = total;
                    document.getElementById('entradasHoy').textContent = entradasHoy;
                    document.getElementById('salidasHoy').textContent = salidasHoy;
                    document.getElementById('stockBajo').textContent = stockBajo;
                    
                    console.log('✅ Estadísticas cargadas');
                }, 1000);
                
            } catch (error) {
                console.error('❌ Error cargando estadísticas:', error);
            }
        }

        

        

        // =============================================
        // VERIFICACIÓN DE AUTENTICACIÓN
        // =============================================
        


        // =============================================
        // INICIALIZACIÓN
        // =============================================
        
        document.addEventListener('DOMContentLoaded', async function() {
            authToken = localStorage.getItem('auth_token');
            const tieneAcceso = <?php echo $tieneAcceso ? 'true' : 'false'; ?>;
            console.log('🚀 Dashboard MDBootstrap cargado');
            
            // Verificar autenticación
            const isAuthenticated = await verifyAuthentication();
            if (!isAuthenticated) return;

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
            
            await cargarEtiquetas();
            await movimientosHoy();
            // Configurar interfaz
            updateUserInfo();
            updateCurrentTime();
            await loadDashboardStats();
            
            // Actualizar hora cada minuto
            setInterval(updateCurrentTime, 60000);
            
            // Configurar event listeners
            document.getElementById('logoutBtn').addEventListener('click', logout);
            document.getElementById('dropdownLogoutBtn').addEventListener('click', logout);
            
            console.log('✅ Dashboard inicializado correctamente');
        });

        // =============================================
        // FUNCIONES GLOBALES PARA DEBUG
        // =============================================
        
        window.debugDashboard = {
            getUser: () => userData,
            getToken: () => authToken,
            reloadStats: loadDashboardStats,
            clearAuth: () => {
                localStorage.removeItem('auth_token');
                localStorage.removeItem('user');
                location.reload();
            }
        };

        function logout() {
            localStorage.removeItem('auth_token');
            localStorage.removeItem('user');
            window.location.href = 'cerrar_sesion.php';
        }
    </script>
</body>
</html>