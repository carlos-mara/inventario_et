<?php
session_start();

// EXTENDER VIDA DE LA SESIÓN (añade esto al inicio)
ini_set('session.gc_maxlifetime', 86400); // 24 horas
ini_set('session.cookie_lifetime', 86400); // Cookie por 24 horas

// Configurar cookie de sesión persistente
setcookie(
    session_name(), 
    session_id(), 
    time() + 86400, // 24 horas
    "/",            // Ruta raíz
    "",             // Dominio (actual)
    isset($_SERVER["HTTPS"]), // Secure si es HTTPS
    true            // HttpOnly
);

// DEBUG: Verificar estado actual
error_log("DEBUG: SESSION ID: " . session_id());
error_log("DEBUG: SESSION usuario = " . print_r($_SESSION['usuario'] ?? 'NO EXISTE', true));
error_log("DEBUG: POST token = " . ($_POST['token'] ?? 'NO EXISTE'));
error_log("DEBUG: Cookie PHPSESSID = " . ($_COOKIE[session_name()] ?? 'NO EXISTE'));

// 1. Si hay token en POST, crear sesión
if (isset($_POST['token'])) {
    require_once 'middleware/AuthMiddleware.php';
    $auth = new AuthMiddleware();
    $usuario = $auth->crearSesionDesdeToken($_POST['token']);
    
    if ($usuario) {
        $_SESSION['usuario'] = $usuario;
        $_SESSION['last_activity'] = time(); // Marcar actividad
        
        // Guardar también en cookie como backup
        setcookie('user_backup', json_encode($usuario), time() + 86400, "/");
        
        error_log("DEBUG: Sesión creada para: " . ($usuario['email'] ?? ''));
        
        // Si es petición AJAX, responder éxito
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'reload' => true]);
            exit;
        }
    } else {
        error_log("ERROR: Token inválido o expirado");
    }
}

// 2. Verificar acceso
$tieneAcceso = false;
if (isset($_SESSION['usuario'])) {
    $rol = $_SESSION['usuario']['rol'] ?? '';
    if ($rol == "admin" || $rol == "proyectos") {
        $tieneAcceso = true;
        $_SESSION['last_activity'] = time(); // Actualizar actividad
    }
}

// 3. Si no tiene acceso, mostrar error
if (!$tieneAcceso) {
    // Intentar restaurar desde cookie de backup
    if (isset($_COOKIE['user_backup'])) {
        $backupUser = json_decode($_COOKIE['user_backup'], true);
        if ($backupUser && ($backupUser['rol'] == "admin" || $backupUser['rol'] == "proyectos")) {
            $_SESSION['usuario'] = $backupUser;
            $tieneAcceso = true;
            error_log("DEBUG: Sesión restaurada desde cookie backup");
        }
    }
    
    // Si aún no tiene acceso, mostrar error
    if (!$tieneAcceso) {
        // Si es una petición AJAX/fetch, responder JSON
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) || 
            (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Acceso denegado', 'needs_token' => true, 'redirect' => 'login.php']);
            exit;
        }
        
        // Si es navegación normal, mostrar HTML con script para enviar token
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Restaurando sesión...</title>
            <script>
            // Intentar restaurar sesión automáticamente
            document.addEventListener('DOMContentLoaded', function() {
                const token = localStorage.getItem('auth_token');
                if (token) {
                    console.log('🔑 Token encontrado, enviando al servidor...');
                    
                    const formData = new FormData();
                    formData.append('token', token);
                    
                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            console.log('✅ Sesión restaurada, recargando...');
                            window.location.reload();
                        } else {
                            console.error('❌ Error restaurando sesión');
                            window.location.href = 'login.php';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        window.location.href = 'login.php';
                    });
                } else {
                    console.warn('No hay token en localStorage');
                    window.location.href = 'login.php';
                }
            });
            </script>
        </head>
        <body>
            <div style="text-align: center; margin-top: 100px;">
                <h2>Restaurando tu sesión...</h2>
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-3">Por favor espera un momento.</p>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// 4. Para debug en HTML
echo "<!-- DEBUG: Acceso permitido para " . ($_SESSION['usuario']['email'] ?? '') . " -->";
echo "<!-- DEBUG: Session ID: " . session_id() . " -->";
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
// CLASE DE AUTENTICACIÓN MEJORADA
// =============================================
class AuthManager {
    constructor() {
        this.tokenKey = 'auth_token';
        this.userKey = 'user';
        this.maxRetries = 2;
    }
    
    async restorePHPSession() {
        const token = localStorage.getItem(this.tokenKey);
        const user = localStorage.getItem(this.userKey);
        
        if (!token || !user) {
            console.warn('No hay token o usuario en localStorage');
            return { success: false, redirect: 'login.php' };
        }
        
        try {
            console.log('🔄 Intentando restaurar sesión PHP...');
            
            // Enviar token al servidor
            const formData = new FormData();
            formData.append('token', token);
            
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                // IMPORTANTE: No seguir redirecciones automáticamente
                redirect: 'manual'
            });
            
            // Si la respuesta es OK o redirección
            if (response.ok || response.status === 302) {
                const contentType = response.headers.get('content-type');
                
                // Si es JSON, procesar
                if (contentType && contentType.includes('application/json')) {
                    const data = await response.json();
                    
                    if (data.success || data.reload) {
                        console.log('✅ Sesión restaurada, recargando página...');
                        setTimeout(() => window.location.reload(), 500);
                        return { success: true, reload: true };
                    } else if (data.needs_token) {
                        console.log('ℹ️ Servidor pide token nuevamente');
                        return { success: false, needsToken: true };
                    }
                }
                
                // Si es HTML normal, la sesión ya está activa
                console.log('✅ Sesión PHP activa');
                return { success: true };
                
            } else if (response.status === 0 || response.type === 'opaqueredirect') {
                // Redirección detectada
                console.log('⚠️ Redirección detectada, verificando...');
                return { success: false, redirect: 'login.php' };
            }
            
            const text = await response.text();
            console.log('Respuesta del servidor:', text.substring(0, 200));
            
            // Verificar si ya tenemos acceso
            if (!text.includes('Acceso denegado') && !text.includes('login.php')) {
                console.log('✅ Acceso confirmado en contenido HTML');
                return { success: true };
            }
            
            return { success: false, needsToken: true };
            
        } catch (error) {
            console.error('❌ Error restaurando sesión:', error);
            return { success: false, error: error.message };
        }
    }
    
    logout() {
        localStorage.removeItem(this.tokenKey);
        localStorage.removeItem(this.userKey);
        // Limpiar cookie de backup
        document.cookie = 'user_backup=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
        window.location.href = 'cerrar_sesion.php';
    }
    
    // Verificar autenticación al cargar
    async verifyOnLoad() {
        const tieneAccesoPHP = <?php echo $tieneAcceso ? 'true' : 'false'; ?>;
        console.log('🔍 Estado PHP inicial:', tieneAccesoPHP ? '✅ Con acceso' : '❌ Sin acceso');
        
        // Si PHP ya tiene acceso, todo bien
        if (tieneAccesoPHP) {
            console.log('✅ Sesión PHP activa, continuando...');
            return true;
        }
        
        // Si no tiene acceso, intentar restaurar
        console.log('🔄 PHP sin sesión, intentando restaurar...');
        const result = await this.restorePHPSession();
        
        if (result.success) {
            if (result.reload) {
                // Ya se está recargando, no hacer nada más
                return false;
            }
            return true;
        } else if (result.needsToken) {
            // El servidor quiere el token, ya se envió en restorePHPSession
            return false;
        } else if (result.redirect) {
            window.location.href = result.redirect;
            return false;
        }
        
        return false;
    }
}

// =============================================
// INICIALIZACIÓN PRINCIPAL
// =============================================
document.addEventListener('DOMContentLoaded', async function() {
    console.log('🚀 Iniciando dashboard...');
    
    // Inicializar manager de autenticación
    const authManager = new AuthManager();
    
    // 1. Verificar y restaurar sesión si es necesario
    const isAuthenticated = await authManager.verifyOnLoad();
    
    if (!isAuthenticated) {
        // Si verifyOnLoad devuelve false, ya está manejando la situación
        // (redirigiendo, recargando, etc.)
        return;
    }
    
    // 2. Si llegamos aquí, la sesión está activa
    console.log('✅ Sesión activa, cargando dashboard...');
    
    // Obtener datos del usuario
    const storedUser = localStorage.getItem('user');
    if (storedUser) {
        try {
            userData = JSON.parse(storedUser);
            console.log('👤 Usuario:', userData.email);
        } catch (e) {
            console.error('Error parseando usuario:', e);
        }
    }
    
    // 3. Cargar datos del dashboard
    await loadDashboardData();
    
    // 4. Configurar interfaz
    updateUserInfo();
    updateCurrentTime();
    setupEventListeners();
    
    console.log('✅ Dashboard completamente cargado');
});

// =============================================
// FUNCIONES DE CARGA DE DATOS
// =============================================
async function loadDashboardData() {
    try {
        // Cargar en paralelo para mejor performance
        await Promise.all([
            cargarEtiquetas(),
            movimientosHoy()
        ]);
        
        await loadDashboardStats();
        
    } catch (error) {
        console.error('Error cargando datos:', error);
        mostrarMensaje('error', 'Error al cargar los datos del dashboard');
    }
}

async function cargarEtiquetas() {
    try {
        const token = localStorage.getItem('auth_token');
        if (!token) throw new Error('No hay token disponible');
        
        const formData = new FormData();
        formData.append('peticion', 'listar');
        formData.append('token', token);

        const response = await fetch('controllers/etiquetas.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.exito) {
            window.etiquetas = result.data;
            console.log(`📦 ${result.data.length} etiquetas cargadas`);
        } else {
            throw new Error(result.msj);
        }
    } catch (error) {
        console.error('Error cargando etiquetas:', error);
        throw error;
    }
}

async function movimientosHoy() {
    try {
        const token = localStorage.getItem('auth_token');
        if (!token) throw new Error('No hay token disponible');
        
        const formData = new FormData();
        formData.append('peticion', 'historial');
        formData.append('fecha', new Date().toISOString().slice(0, 10));
        formData.append('token', token);

        const response = await fetch('controllers/movimientos.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.exito) {
            const movimientos = result.data;
            const hoy = new Date().toISOString().slice(0, 10);
            window.entradasHoy = movimientos.filter(m => m.tipo === 'entrada' && m.fecha_movimiento.startsWith(hoy)).length;
            window.salidasHoy = movimientos.filter(m => m.tipo === 'salida' && m.fecha_movimiento.startsWith(hoy)).length;
            console.log(`📊 Hoy: ${window.entradasHoy} entradas, ${window.salidasHoy} salidas`);
        } else {
            throw new Error(result.msj);
        }
    } catch (error) {
        console.error('Error cargando movimientos:', error);
        throw error;
    }
}

async function loadDashboardStats() {
    try {
        const total = window.etiquetas?.length || 0;
        const stockBajo = window.etiquetas?.filter(e => 
            e.stock_actual <= e.stock_minimo && e.stock_actual > 0
        ).length || 0;
        
        document.getElementById('totalEtiquetas').textContent = total;
        document.getElementById('entradasHoy').textContent = window.entradasHoy || 0;
        document.getElementById('salidasHoy').textContent = window.salidasHoy || 0;
        document.getElementById('stockBajo').textContent = stockBajo;
        
        console.log('📊 Estadísticas actualizadas');
    } catch (error) {
        console.error('Error actualizando estadísticas:', error);
    }
}

// =============================================
// FUNCIONES DE INTERFAZ
// =============================================
function updateUserInfo() {
    const storedUser = localStorage.getItem('user');
    if (!storedUser) return;
    
    try {
        const userData = JSON.parse(storedUser);
        
        // Actualizar nombre en dropdown
        const userNameElement = document.getElementById('dropdownUserName');
        if (userNameElement) {
            userNameElement.textContent = userData.nombre || userData.email || 'Usuario';
        }
        
        // Actualizar mensaje de bienvenida
        const welcomeMessage = document.getElementById('welcomeMessage');
        if (welcomeMessage) {
            const hora = new Date().getHours();
            let saludo = 'Buenas noches';
            if (hora < 12) saludo = 'Buenos días';
            else if (hora < 19) saludo = 'Buenas tardes';
            
            welcomeMessage.textContent = `${saludo}, ${userData.nombre || 'Usuario'}!`;
        }
    } catch (e) {
        console.error('Error actualizando info usuario:', e);
    }
}

function updateCurrentTime() {
    const timeElement = document.getElementById('currentTime');
    if (timeElement) {
        const now = new Date();
        timeElement.textContent = now.toLocaleTimeString('es-ES', {
            hour: '2-digit',
            minute: '2-digit'
        });
    }
}

function setupEventListeners() {
    // Logout buttons
    const logoutButtons = [
        document.getElementById('logoutBtn'),
        document.getElementById('dropdownLogoutBtn')
    ];
    
    logoutButtons.forEach(btn => {
        if (btn) {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const auth = new AuthManager();
                auth.logout();
            });
        }
    });
}

// =============================================
// FUNCIONES GLOBALES
// =============================================
function navigate(url) {
    // Guardar token en sessionStorage para la siguiente página
    const token = localStorage.getItem('auth_token');
    if (token) {
        sessionStorage.setItem('auth_token_temp', token);
    }
    window.location.href = url;
}

function mostrarMensaje(tipo, mensaje) {
    // Puedes implementar toast notifications aquí
    console.log(`${tipo}: ${mensaje}`);
    alert(`${tipo}: ${mensaje}`);
}

function logout() {
    const auth = new AuthManager();
    auth.logout();
}

// Iniciar actualización de hora
setInterval(updateCurrentTime, 60000);

// =============================================
// DEBUG UTILITIES
// =============================================
window.debugAuth = {
    checkSession: () => {
        console.log('Token:', localStorage.getItem('auth_token'));
        console.log('User:', localStorage.getItem('user'));
        console.log('PHP Access:', <?php echo $tieneAcceso ? 'true' : 'false'; ?>);
        console.log('Session ID:', '<?php echo session_id(); ?>');
    },
    forceRestore: async () => {
        const auth = new AuthManager();
        return await auth.restorePHPSession();
    }
};
    </script>
</body>
</html>