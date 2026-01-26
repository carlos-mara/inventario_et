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
}elseif($_SESSION['usuario']['rol'] == "admin"){
    $tieneAcceso = true;
}else {
    echo "<h1>Usuario: ".$_SESSION['usuario']."</h1>";
    echo "<h1>Acceso denegado</h1>";
    echo '<a href="login.php">Volver a iniciar sesión</a>';
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
// CLASE DE AUTENTICACIÓN MEJORADA
// =============================================
class AuthManager {
    constructor() {
        this.tokenKey = 'auth_token';
        this.userKey = 'user';
        this.maxRetries = 1; // Reducir intentos para evitar bucles
        this.retryCount = 0;
    }
    
    async restorePHPSession() {
        // Si ya intentamos demasiadas veces, ir a login
        if (this.retryCount >= this.maxRetries) {
            console.error('Demasiados intentos, redirigiendo a login');
            window.location.href = 'login.php';
            return { success: false };
        }
        
        this.retryCount++;
        
        const token = localStorage.getItem(this.tokenKey);
        console.log(token);
        
        if (!token) {
            console.warn('No hay token en localStorage');
            return { success: false, redirect: 'login.php' };
        }
        
        try {
            console.log(`🔄 Intento ${this.retryCount} de restaurar sesión`);
            
            const formData = new FormData();
            formData.append('token', token);
            
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'include' // CRUCIAL: Incluir cookies
            });
            
            // Intentar parsear como JSON
            try {
                const data = await response.json();
                
                if (data.success) {
                    console.log('✅ Sesión restaurada exitosamente');
                    this.retryCount = 0; // Resetear contador
                    
                    // Recargar después de un breve delay
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                    
                    return { success: true, reloading: true };
                    
                } else if (data.needs_token) {
                    // El servidor quiere que enviemos el token, pero ya lo hicimos
                    console.log('ℹ️ Servidor solicitó token (ya enviado)');
                    return { success: false, needsToken: true };
                    
                } else if (data.redirect) {
                    console.log('⚠️ Redirigiendo a:', data.redirect);
                    window.location.href = data.redirect;
                    return { success: false };
                }
                
            } catch (jsonError) {
                // Si no es JSON, verificar si es HTML (sesión ya activa)
                console.log('Respuesta no es JSON, verificando contenido...');
                const text = await response.text();
                
                if (text.includes('Dashboard - Sistema Inventarios') || 
                    text.includes('ACCESO PERMITIDO')) {
                    console.log('✅ Sesión ya está activa en esta pestaña');
                    return { success: true };
                }
                
                // Si es la página de restauración, ya está manejando
                if (text.includes('Restaurando tu sesión')) {
                    console.log('ℹ️ Página de restauración detectada');
                    return { success: false, alreadyRestoring: true };
                }
            }
            
            return { success: false };
            
        } catch (error) {
            console.error('❌ Error en restorePHPSession:', error);
            return { success: false, error: error.message };
        }
    }
    
    logout() {
        localStorage.removeItem(this.tokenKey);
        localStorage.removeItem(this.userKey);
        // Enviar petición para cerrar sesión en el servidor
        fetch('cerrar_sesion.php', { method: 'POST', credentials: 'include' })
            .finally(() => {
                window.location.href = 'login.php';
            });
    }
    
    // Verificar autenticación
    async verifyOnLoad() {
        console.log('🔍 Verificando estado de autenticación...');
        
        // Verificar primero localStorage
        const token = localStorage.getItem(this.tokenKey);
        const user = localStorage.getItem(this.userKey);
        
        if (!token || !user) {
            console.log('❌ No hay credenciales en localStorage');
            window.location.href = 'login.php';
            return false;
        }
        
        // Si PHP dice que tenemos acceso, todo bien
        const tieneAccesoPHP = <?php echo $tieneAcceso ? 'true' : 'false'; ?>;
        if (tieneAccesoPHP) {
            console.log('✅ Sesión PHP activa');
            return true;
        }
        
        console.log('🔄 Sesión PHP no activa, intentando restaurar...');
        const result = await this.restorePHPSession();
        
        return result.success;
    }
}

// =============================================
// INICIALIZACIÓN PRINCIPAL - SIMPLIFICADA
// =============================================
document.addEventListener('DOMContentLoaded', async function() {
    console.log('🚀 Iniciando dashboard...');
    authToken = localStorage.getItem('auth_token');
    
    
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
    
    // Inicializar manager
    const authManager = new AuthManager();
    
    // Verificar autenticación
    const isAuthenticated = await authManager.verifyOnLoad();
    
    if (!isAuthenticated) {
        // Si no está autenticado, verifyOnLoad ya maneja la redirección
        return;
    }
    
    // Si llegamos aquí, cargar dashboard normal
    console.log('✅ Dashboard cargado exitosamente');
    await loadDashboardData();
    updateUserInfo();
    setupEventListeners();
});

// ... resto de tus funciones JavaScript ...

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