<style>
    /* Sidebar styles */
    .sidebar {
        background: linear-gradient(180deg, #0d6efd 0%, #0b5ed7 100%);
        color: white;
        height: 100vh;
        width: 280px;
        position: fixed;
        top: 0;
        left: 0;
        z-index: 1000;
        transition: transform 0.3s ease, margin-left 0.3s ease;
        box-shadow: 3px 0 10px rgba(0,0,0,0.1);
        overflow-y: auto;
        overflow-x: hidden;
    }
    
    .sidebar.collapsed {
        transform: translateX(-100%);
        margin-left: -280px;
    }
    
    .sidebar-overlay.active {
        
    }
    
    /* Ajustes para desktop cuando el sidebar está colapsado */
    
        .sidebar.collapsed {
            transform: translateX(-280px);
        }
        
        .sidebar:not(.collapsed) {
            transform: translateX(0);
            margin-left: 0;
        }
        
        /* Ajustar contenido cuando sidebar está expandido */
        body.sidebar-expanded {
            padding-left: 280px;
            transition: padding-left 0.3s ease;
        }
        
        body.sidebar-expanded .main-content {
            
            transition: margin-left 0.3s ease;
        }
    
    
    /* Para móviles */
    /* @media (max-width: 767.98px) {
        
        
        body, body.sidebar-expanded {
            padding-left: 0 !important;
        }
        
        .main-content {
            margin-left: 0 !important;
        }
    } */
    
    .sidebar .nav-link {
        color: rgba(255,255,255,0.8);
        border-radius: 8px;
        margin: 2px 0;
        padding: 10px 15px;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        text-decoration: none;
        position: relative;
    }
    
    .sidebar .nav-link:hover {
        background: rgba(255,255,255,0.1);
        color: white;
        transform: translateX(5px);
    }
    
    .sidebar .nav-link.active {
        background: rgba(255,255,255,0.2);
        color: white;
        font-weight: bold;
        box-shadow: inset 3px 0 0 rgba(255,255,255,0.8);
    }
    
    .sidebar .nav-link i {
        width: 20px;
        text-align: center;
        margin-right: 10px;
    }
    
    /* Logo y Título */
    .sidebar-logo {
        padding: 1.5rem 1rem;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        text-align: center;
        position: relative;
    }
    
    .sidebar-logo img {
        max-width: 80%;
        height: auto;
    }
    
    .sidebar-logo h5 {
        margin-top: 1rem;
        margin-bottom: 0.25rem;
        font-size: 1.1rem;
    }
    
    .sidebar-logo small {
        font-size: 0.8rem;
        opacity: 0.8;
    }
    
    /* Botón para cerrar sidebar dentro del mismo sidebar (opcional) */
    .sidebar-close-btn {
        position: absolute;
        top: 10px;
        right: 10px;
        background: none;
        border: none;
        color: white;
        font-size: 1.2rem;
        cursor: pointer;
        opacity: 0.7;
        transition: opacity 0.3s;
        z-index: 1002; 
        display: block;
    }
    
    .sidebar-close-btn:hover {
        opacity: 1;
    }
    
    
    
    /* Información del Usuario */
    .user-info {
        margin-top: auto;
        padding: 1rem;
        background: rgba(0,0,0,0.1);
        border-top: 1px solid rgba(255,255,255,0.1);
    }
    
    .user-avatar {
        width: 40px;
        height: 40px;
        object-fit: cover;
    }
    
    /* Botón Toggle independiente (lo puedes colocar donde quieras) */
    .sidebar-toggle-btn {
        background: #0d6efd;
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 5px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .sidebar-toggle-btn:hover {
        background: #0b5ed7;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    
    
    
    .sidebar-toggle-btn.small {
        width: 40px;
        height: 40px;
        padding: 0;
        border-radius: 50%;
    }
    
    .sidebar-toggle-btn.small i {
        margin-right: 0;
    }
    
    /* Scrollbar personalizada */
    .sidebar::-webkit-scrollbar {
        width: 5px;
    }
    
    .sidebar::-webkit-scrollbar-track {
        background: rgba(255,255,255,0.1);
    }
    
    .sidebar::-webkit-scrollbar-thumb {
        background: rgba(255,255,255,0.3);
        border-radius: 10px;
    }
    
    .sidebar::-webkit-scrollbar-thumb:hover {
        background: rgba(255,255,255,0.5);
    }
    
    /* Separadores */
    .sidebar hr {
        opacity: 0.2;
        margin: 1rem 0;
    }
    
    /* Clase para el contenido principal */
    .main-content {
        transition: margin-left 0.3s ease;
        width: 100%;
        min-height: 100vh;
    }
</style>

<!-- Overlay para móviles -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- SIDEBAR -->
<nav id="sidebarMenu" class="sidebar collapsed">
    <!-- Botón para cerrar dentro del sidebar (solo visible en desktop) -->
    <button class="sidebar-close-btn" id="sidebarCloseBtn" title="Cerrar sidebar">
        <i class="fas fa-chevron-left"></i>
    </button>
    
    <div class="sidebar-logo">
        <img src="img/logo.png" alt="Logo Synertech" class="img-fluid" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iIzBkNmVmZCIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTQiIGZpbGw9IndoaXRlIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSI+TG9nbzwvdGV4dD48L3N2Zz4='">
        <h5 class="fw-bold">Sistema de Inventarios</h5>
        <small class="text-white-50">Panel de Control</small>
    </div>

    <hr>

    <!-- Menú de Navegación -->
    <ul class="nav flex-column px-2">
        <li class="nav-item">
            <a class="nav-link" href="dashboard.php">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </a>
        </li>
        <?php if($_SESSION['usuario']['rol']!= "proyectos"): ?>
        <li class="nav-item">
            <a class="nav-link" href="inventario.php">
                <i class="fas fa-warehouse"></i>
                Inventario
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="nueva-etiqueta.php">
                <i class="fas fa-tag"></i>
                Nueva Etiqueta
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="movimientos.php">
                <i class="fas fa-exchange-alt"></i>
                Movimientos
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="categorias.php">
                <i class="fas fa-folder"></i>
                Categorías
            </a>
        </li>
        <?php endif; ?>
        <li class="nav-item">
            <a class="nav-link" href="proyectos.php">
                <i class="fas fa-object-ungroup"></i>
                Proyectos
            </a>
        </li>
    </ul>

    <hr class="mx-3">

    <!-- Menú Sistema -->
    <ul class="nav flex-column px-2 mb-3">
        <li class="nav-item">
            <a class="nav-link" href="configuracion.php">
                <i class="fas fa-cog"></i>
                Configuración
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-warning" href="#" id="logoutBtn">
                <i class="fas fa-sign-out-alt"></i>
                Cerrar Sesión
            </a>
        </li>
    </ul>

    <!-- Información del Usuario -->
    <div class="user-info">
        <div class="d-flex align-items-center">
            <img src="https://etiquetas.ibericaservice.com/img/icon-512x512.png" 
                 class="user-avatar rounded-circle me-3" 
                 alt="Usuario"
                 onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMjAiIGN5PSIyMCIgcj0iMjAiIGZpbGw9IiMwZDZlZmQiLz48dGV4dCB4PSIyMCIgeT0iMjIiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0id2hpdGUiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5VPC90ZXh0Pjwvc3ZnPg=='">
            <div>
                <h6 class="mb-0 fw-bold" id="userName">Usuario</h6>
                <small class="text-white-50" id="userRole">Administrador</small>
            </div>
        </div>
    </div>
</nav>

<script>
// Sidebar Manager Mejorado
class SidebarManager {
    constructor() {
        this.sidebar = document.getElementById('sidebarMenu');
        this.overlay = document.getElementById('sidebarOverlay');
        this.closeBtn = document.getElementById('sidebarCloseBtn');
        this.openBtn = document.getElementById('customSidebarToggle');
        this.isOpen = false;
        this.isMobile = window.innerWidth < 768;
        this.stateKey = 'sidebarState';
        
        this.init();
    }
    
    init() {
        // Cargar estado guardado
        this.loadState();
        
        // Event Listeners
        if (this.closeBtn) {
            this.closeBtn.addEventListener('click', () => this.close());
        }

        if (this.openBtn) {
            this.openBtn.addEventListener('click', () => this.toggle());
        }
        
        this.overlay.addEventListener('click', () => this.close());
        
        // Cerrar sidebar al hacer clic en enlaces (solo móviles)
        const navLinks = this.sidebar.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (this.isMobile) this.close();
            });
        });
        
        // Manejo de logout
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', (e) => {
                e.preventDefault();
                if (confirm('¿Estás seguro de que deseas cerrar sesión?')) {
                    window.location.href = 'logout.php';
                }
            });
        }
        
        // Resize handler
        window.addEventListener('resize', () => this.handleResize());
        
        // Marcar enlace activo basado en URL actual
        this.setActiveLink();
        
        // Cargar información del usuario
        this.loadUserInfo();
        
        // Aplicar estado inicial
        this.applyState();
    }
    
    loadState() {
        const savedState = localStorage.getItem(this.stateKey);
        if (savedState !== null) {
            this.isOpen = savedState === 'open';
        } else {
            // Estado por defecto: abierto en desktop, cerrado en móvil
            this.isOpen = !this.isMobile;
        }
    }
    
    saveState() {
        localStorage.setItem(this.stateKey, this.isOpen ? 'open' : 'closed');
    }
    
    applyState() {
        if (this.isOpen) {
            this.open();
        } else {
            this.close();
        }
    }
    
    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
        this.saveState();
    }
    
    open() {
        this.sidebar.classList.remove('collapsed');
        this.overlay.classList.add('active');
        this.isOpen = true;
        
        if (!this.isMobile) {
            document.body.classList.add('sidebar-expanded');
        }
        
        // Actualizar ícono del botón close
        if (this.closeBtn) {
            this.closeBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
        }
        
        // Disparar evento personalizado
        this.dispatchEvent('sidebar:open');
    }
    
    close() {
        this.sidebar.classList.add('collapsed');
        this.overlay.classList.remove('active');
        this.isOpen = false;
        
        if (!this.isMobile) {
            document.body.classList.remove('sidebar-expanded');
        }
        
        // Actualizar ícono del botón close (para cuando se vuelva a abrir)
        if (this.closeBtn) {
            this.closeBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
        }
        
        // Disparar evento personalizado
        this.dispatchEvent('sidebar:close');
    }
    
    handleResize() {
        const wasMobile = this.isMobile;
        this.isMobile = window.innerWidth < 768;
        
        if (wasMobile !== this.isMobile) {
            if (this.isMobile && this.isOpen) {
                // En móvil, siempre mostrar overlay si está abierto
                this.overlay.classList.add('active');
            } else if (!this.isMobile) {
                // En desktop, quitar overlay
                this.overlay.classList.remove('active');
            }
            
            // Aplicar el estado guardado
            this.applyState();
        }
    }
    
    setActiveLink() {
        const currentPage = window.location.pathname.split('/').pop();
        const navLinks = this.sidebar.querySelectorAll('.nav-link');
        
        navLinks.forEach(link => {
            link.classList.remove('active');
            const href = link.getAttribute('href');
            
            if (href === currentPage || 
                (currentPage === '' && href === 'dashboard.php') ||
                (href.includes(currentPage) && currentPage !== '')) {
                link.classList.add('active');
            }
        });
    }
    
    loadUserInfo() {
        // Simular carga de datos del usuario
        setTimeout(() => {
            const userName = localStorage.getItem('userName') || 'Usuario';
            const userRole = localStorage.getItem('userRole') || 'Administrador';
            
            const userNameEl = document.getElementById('userName');
            const userRoleEl = document.getElementById('userRole');
            
            if (userNameEl) userNameEl.textContent = userName;
            if (userRoleEl) userRoleEl.textContent = userRole;
        }, 100);
    }
    
    dispatchEvent(eventName) {
        const event = new CustomEvent(eventName, {
            detail: { isOpen: this.isOpen }
        });
        window.dispatchEvent(event);
    }
    
    // Métodos públicos para usar desde otros scripts
    getState() {
        return {
            isOpen: this.isOpen,
            isMobile: this.isMobile
        };
    }
    
    setUserInfo(name, role) {
        localStorage.setItem('userName', name);
        localStorage.setItem('userRole', role);
        this.loadUserInfo();
    }
}

// Función para crear botón toggle (la puedes usar en cualquier página)
function createSidebarToggleButton(options = {}) {
    const defaults = {
        text: 'Menú',
        small: false,
        position: null,
        icon: 'bars',
        customClass: ''
    };
    
    const config = { ...defaults, ...options };
    
    const button = document.createElement('button');
    button.className = `sidebar-toggle-btn ${config.small ? 'small' : ''} ${config.customClass}`;
    button.id = 'customSidebarToggle';
    button.title = config.small ? 'Alternar menú' : config.text;
    
    if (config.small) {
        button.innerHTML = `<i class="fas fa-${config.icon}"></i>`;
    } else {
        button.innerHTML = `<i class="fas fa-${config.icon}"></i> ${config.text}`;
    }
    
    // Posicionamiento si se especifica
    if (config.position) {
        Object.assign(button.style, config.position);
    }
    
    // Event listener
    button.addEventListener('click', () => {
        if (window.sidebarManager) {
            window.sidebarManager.toggle();
            // Actualizar ícono del botón
            const icon = button.querySelector('i');
            if (icon && window.sidebarManager.isOpen) {
                icon.className = config.small ? 'fas fa-times' : `fas fa-${config.icon === 'bars' ? 'times' : 'bars'}`;
            } else if (icon) {
                icon.className = `fas fa-${config.icon}`;
            }
        }
    });
    
    return button;
}

// Auto-inicialización
document.addEventListener('DOMContentLoaded', () => {
    // Inicializar el sidebar manager
    window.sidebarManager = new SidebarManager();
    
    // Escuchar eventos del sidebar para actualizar botones externos
    window.addEventListener('sidebar:open', () => {
        const toggleBtn = document.getElementById('customSidebarToggle');
        if (toggleBtn) {
            const icon = toggleBtn.querySelector('i');
            if (icon) {
                icon.className = icon.className.includes('bars') ? 
                    'fas fa-times' : 
                    icon.className.replace('bars', 'times').replace('chevron-right', 'chevron-left');
            }
        }
    });
    
    window.addEventListener('sidebar:close', () => {
        const toggleBtn = document.getElementById('customSidebarToggle');
        if (toggleBtn) {
            const icon = toggleBtn.querySelector('i');
            if (icon) {
                icon.className = icon.className.includes('times') ? 
                    'fas fa-bars' : 
                    icon.className.replace('times', 'bars').replace('chevron-left', 'chevron-right');
            }
        }
    });
});

// Fallback si el DOM ya está cargado
if (document.readyState === 'interactive' || document.readyState === 'complete') {
    window.sidebarManager = new SidebarManager();
}
</script>