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
    <title>Inventario - Sistema Inventarios</title>
    
    <!-- MDBootstrap CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700&display=swap" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/9.2.0/mdb.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="css/style.css">
    <style>
        
        .user-avatar {
            width: 50px;
            height: 50px;
            border: 3px solid rgba(255,255,255,0.3);
        }
        .etiqueta-card {
            transition: transform 0.3s;
            border: none;
            border-radius: 15px;
        }
        .etiqueta-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .stock-bajo {
            border-left: 4px solid #dc3545;
        }
        .stock-normal {
            border-left: 4px solid #28a745;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- SIDEBAR -->
            <?php include "menu.php"; ?>

            <!-- CONTENIDO PRINCIPAL -->
            <div class="main-content">
                <!-- Barra Superior -->
                <nav class="top-navbar">
                    <div class="container-fluid">
                        <div class="d-flex justify-content-between align-items-center">
                            <button class="sidebar-toggle-btn" id="customSidebarToggle">
                                <i class="fas fa-bars"></i>
                            </button>
                        
                        <span class="navbar-brand fw-bold text-primary">
                            <i class="fas fa-warehouse me-2"></i>Inventario de Etiquetas
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
                        
                    </div>
                </nav>

                <!-- Contenido -->
                <div class="container-fluid mt-4">
                    <!-- Barra de Búsqueda y Filtros -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row g-3 align-items-end">
                                        <div class="col-md-4">
                                            <label class="form-label">Buscar etiqueta</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" placeholder="Nombre, código, categoría..." id="searchInput">
                                                <button class="btn btn-primary" type="button" id="searchBtn">
                                                    <i class="fas fa-search"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Categoría</label>
                                            <select class="form-select" id="categoriaFilter">
                                                <option value="">Todas las categorías</option>
                                                <option value="1">Etiquetas Adhesivas</option>
                                                <option value="2">Etiquetas Térmicas</option>
                                                <option value="3">Etiquetas RFID</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Estado Stock</label>
                                            <select class="form-select" id="stockFilter">
                                                <option value="">Todo el stock</option>
                                                <option value="bajo">Stock Bajo</option>
                                                <option value="normal">Stock Normal</option>
                                                <option value="sin-stock">Sin Stock</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <button class="btn btn-outline-secondary w-100" id="resetFilters">
                                                <i class="fas fa-redo me-1"></i>Limpiar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Estadísticas Rápidas -->
                    <div class="row mb-4">
                        <div class="col-xl-2 col-md-4 col-6 mb-3">
                            <div class="card bg-primary text-white text-center">
                                <div class="card-body py-3">
                                    <h5 class="card-title mb-1" id="totalEtiquetas">0</h5>
                                    <small>Total Etiquetas</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 col-6 mb-3">
                            <div class="card bg-success text-white text-center">
                                <div class="card-body py-3">
                                    <h5 class="card-title mb-1" id="stockDisponible">0</h5>
                                    <small>En Stock</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 col-6 mb-3">
                            <div class="card bg-warning text-white text-center">
                                <div class="card-body py-3">
                                    <h5 class="card-title mb-1" id="stockBajo">0</h5>
                                    <small>Stock Bajo</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 col-6 mb-3">
                            <div class="card bg-danger text-white text-center">
                                <div class="card-body py-3">
                                    <h5 class="card-title mb-1" id="sinStock">0</h5>
                                    <small>Sin Stock</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 col-6 mb-3">
                            <div class="card bg-info text-white text-center">
                                <div class="card-body py-3">
                                    <h5 class="card-title mb-1" id="totalCategorias">0</h5>
                                    <small>Categorías</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de Etiquetas -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <i class="fas fa-list me-2"></i>Lista de Etiquetas
                                    </h5>
                                    <button class="btn btn-success btn-sm" onclick="navigate('nueva-etiqueta.php')">
                                        <i class="fas fa-plus me-1"></i>Nueva Etiqueta
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="tablaEtiquetas">
                                            <thead>
                                                <tr>
                                                    <th></th>
                                                    <th>Nombre</th>
                                                    <th>Categoría</th>
                                                    <th>Stock</th>
                                                    <th>Estado</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody id="etiquetasBody">
                                                <!-- Las etiquetas se cargarán aquí -->
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <!-- Paginación -->
                                    <nav aria-label="Page navigation">
                                        <ul class="pagination justify-content-center mt-4" id="pagination">
                                            <!-- La paginación se generará aquí -->
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Ver Detalles -->
    <div class="modal fade" id="detalleModal" tabindex="-1" aria-labelledby="detalleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detalleModalLabel">Detalles de Etiqueta</h5>
                    <button type="button" class="btn-close" data-mdb-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="detalleModalBody">
                    <!-- Los detalles se cargarán aquí -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" onclick="editarEtiqueta()">Editar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MDBootstrap JS -->
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/9.2.0/mdb.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script src="js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        let userData = null;
        let authToken = null;
        let etiquetas = [];
        let currentPage = 1;
        const itemsPerPage = 10;

        

        // Cargar etiquetas desde la API
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
                    mostrarEtiquetas();
                    actualizarEstadisticas();
                } else {
                    throw new Error(result.msj);
                }
            } catch (error) {
                console.error('Error cargando etiquetas:', error);
                mostrarMensaje('error', 'Error al cargar las etiquetas');
            }
        }

        // Mostrar etiquetas en la tabla
        function mostrarEtiquetas() {
            const tbody = document.getElementById('etiquetasBody');
            tbody.innerHTML = '';

            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = startIndex + itemsPerPage;
            const etiquetasPagina = etiquetas.slice(startIndex, endIndex);

            etiquetasPagina.forEach(etiqueta => {
                const tr = document.createElement('tr');
                tr.className = etiqueta.stock_actual <= 10 ? 'stock-bajo' : 'stock-normal';
                
                tr.innerHTML = `
                    <td>
                        <img src="${etiqueta.foto_url ? 'uploads/' + etiqueta.foto_url : 'https://placehold.co/600X400?text=SIN+FOTO&font=roboto'}" 
                             alt="Foto Etiqueta" class="img-fluid rounded p-0" width="90" height="90">
                    </td>
                    <td>
                        <strong>${etiqueta.nombre}</strong>
                        ${etiqueta.descripcion ? `<br><small class="text-muted">${etiqueta.descripcion}</small>` : ''}
                    </td>
                    <td>${etiqueta.categoria_nombre || 'Sin categoría'}</td>
                    <td>
                        <span class="badge ${etiqueta.stock_actual === 0 ? 'bg-danger' : etiqueta.stock_actual <= etiqueta.stock_minimo ? 'bg-warning' : 'bg-success'}">
                            ${etiqueta.stock_actual} unidades
                        </span>
                    </td>
                    <td>
                        <span class="badge ${etiqueta.activa ? 'bg-success' : 'bg-secondary'}">
                            ${etiqueta.activa ? 'Activa' : 'Inactiva'}
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="verDetalle(${etiqueta.id})" title="Ver detalles">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-warning" onclick="editarEtiqueta(${etiqueta.id})" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="eliminarEtiqueta(${etiqueta.id})" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });

            actualizarPaginacion();
        }

        function mostrarMensaje(tipo, mensaje) {
            Swal.fire({
                icon: tipo,
                title: mensaje,
                timer: 2000,
                showConfirmButton: false
            });
        }

        // Actualizar estadísticas
        function actualizarEstadisticas() {
            const total = etiquetas.length;
            const stockDisponible = etiquetas.filter(e => e.stock_actual > 0).length;
            const stockBajo = etiquetas.filter(e => e.stock_actual <= e.stock_minimo && e.stock_actual > 0).length;
            const sinStock = etiquetas.filter(e => e.stock_actual === 0).length;
            const categorias = new Set(etiquetas.map(e => e.categoria_id)).size;

            document.getElementById('totalEtiquetas').textContent = total;
            document.getElementById('stockDisponible').textContent = stockDisponible;
            document.getElementById('stockBajo').textContent = stockBajo;
            document.getElementById('sinStock').textContent = sinStock;
            document.getElementById('totalCategorias').textContent = categorias;
        }

        // Ver detalles de etiqueta
        async function verDetalle(id) {
            try {
                const formData = new FormData();
                formData.append('peticion', 'obtener');
                formData.append('token', authToken);
                formData.append('id', id);

                const response = await fetch('controllers/etiquetas.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.exito) {
                    const etiqueta = result.etiqueta.info;
                    
                    const modalBody = document.getElementById('detalleModalBody');
                    
                    modalBody.innerHTML = `
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="fw-bold text-primary">Información General</h6>
                                <p><strong>Nombre:</strong> ${etiqueta.nombre}</p>
                                <p><strong>Descripción:</strong> ${etiqueta.descripcion || 'Sin descripción'}</p>
                                <p><strong>Categoría:</strong> ${etiqueta.categoria_nombre || 'Sin categoría'}</p>
                                <p><strong>Estado:</strong> 
                                    <span class="badge ${etiqueta.activa ? 'bg-success' : 'bg-secondary'}">
                                        ${etiqueta.activa ? 'Activa' : 'Inactiva'}
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-bold text-primary">Información de Stock</h6>
                                <p><strong>Stock Actual:</strong> ${etiqueta.stock_actual} unidades</p>
                                <p><strong>Stock Mínimo:</strong> ${etiqueta.stock_minimo} unidades</p>
                                
                                <div class="fw-bold text-primary">
                                    <strong>Foto:</strong><br>
                                    <img src="${etiqueta.foto_url ? 'uploads/' + etiqueta.foto_url : 'https://placehold.co/600X400?text=SIN+FOTO&font=roboto'}" 
                                         alt="Foto Etiqueta" class="img-fluid rounded p-2" width="200" height="200">
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <h6 class="fw-bold text-primary">Fechas</h6>
                                <p><strong>Creado:</strong> ${new Date(etiqueta.fecha_creacion).toLocaleDateString()}</p>
                                <p><strong>Actualizado:</strong> ${new Date(etiqueta.fecha_actualizacion).toLocaleDateString()}</p>
                            </div>
                        </div>
                        
                    `;
                    let tamanos = result.etiqueta.tamanos
                    if (tamanos.length > 0) {
                        let tablaBody = `
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Dimensiones</th>
                                            <th>Alto (cm)</th>
                                            <th>Ancho (cm)</th>
                                            <th>Stock</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;
                        
                        tamanos.forEach((tamano, index) => {
                            const stockClass = tamano.stock > 0 ? 'bg-success' : 'bg-warning';
                            
                            tablaBody += `
                                <tr>
                                    <td>${index + 1}</td>
                                    <td><span class="badge bg-primary">${tamano.alto || '0'} × ${tamano.ancho || '0'} cm</span></td>
                                    <td>${tamano.alto || '0'}</td>
                                    <td>${tamano.ancho || '0'}</td>
                                    <td><span class="badge ${stockClass}">${tamano.stock || '0'} unidades</span></td>
                                </tr>
                            `;
                        });
                        modalBody.innerHTML += tablaBody;
                        modalBody.innerHTML += `
                                    </tbody>
                                </table>
                            </div>
                        `;
                    }
                    
                    const modal = new mdb.Modal(document.getElementById('detalleModal'));
                    modal.show();
                }
            } catch (error) {
                console.error('Error cargando detalles:', error);
                mostrarMensaje('error', 'Error al cargar los detalles de la etiqueta');
            }
        }

        // Eliminar etiqueta
        async function eliminarEtiqueta(id) {
            //confirmar eliminación
            const confirmResult = await Swal.fire({
                title: '¿Estás seguro?',
                text: "Esta acción no se puede deshacer.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            });
            if (!confirmResult.isConfirmed) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('peticion', 'eliminar');
                formData.append('token', authToken);
                formData.append('id', id);

                const response = await fetch('controllers/etiquetas.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.exito) {
                    mostrarMensaje('success', 'Etiqueta eliminada exitosamente');
                    await cargarEtiquetas();
                } else {
                    throw new Error(result.msj);
                }
            } catch (error) {
                console.error('Error eliminando etiqueta:', error);
                mostrarMensaje('error', 'Error al eliminar la etiqueta');
            }
        }
        // Editar etiqueta (redireccionar)
        function editarEtiqueta(id) {
            if (id) {
                navigate(`editar-etiqueta.php?id=${id}`);
            } else {
                mostrarMensaje('info', 'Funcionalidad de edición no implementada aún.');
            }
        }

        // Inicialización
        document.addEventListener('DOMContentLoaded', async function() {
            // Verificar autenticación
            authToken = localStorage.getItem('auth_token');
            const storedUser = localStorage.getItem('user');
            const rol = localStorage.getItem('rol')
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

            // Inicializar sidebar
            
            
            // Cargar etiquetas
            await cargarEtiquetas();
            
            // Configurar event listeners
            document.getElementById('logoutBtn').addEventListener('click', logout);
            document.getElementById('dropdownLogoutBtn').addEventListener('click', logout);
            document.getElementById('searchBtn').addEventListener('click', filtrarEtiquetas);
            document.getElementById('resetFilters').addEventListener('click', resetFiltros);
            
            setInterval(updateCurrentTime, 60000);
        });

        // Funciones de filtrado (simplificadas)
        function filtrarEtiquetas() {
            // Implementar lógica de filtrado
            mostrarEtiquetas();
        }

        function resetFiltros() {
            document.getElementById('searchInput').value = '';
            document.getElementById('categoriaFilter').value = '';
            document.getElementById('stockFilter').value = '';
            mostrarEtiquetas();
        }

        function actualizarPaginacion() {
            // Implementar paginación
        }

        function logout() {
            localStorage.removeItem('auth_token');
            localStorage.removeItem('user');
            window.location.href = 'cerrar_sesion.php';
        }
    </script>
</body>
</html>