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
    <title>Categorías - Sistema Inventarios</title>
    
    <!-- MDBootstrap CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700&display=swap" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/9.2.0/mdb.min.css" rel="stylesheet" />
    <style>
        
        .main-content {
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .user-avatar {
            width: 50px;
            height: 50px;
            border: 3px solid rgba(255,255,255,0.3);
        }
        .categoria-card {
            transition: transform 0.3s;
            border: none;
            border-radius: 15px;
        }
        .categoria-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .badge-cantidad {
            font-size: 0.7em;
        }
        .categoria-inactiva {
            opacity: 0.6;
            background-color: #f8f9fa !important;
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
                            <i class="fas fa-folder me-2"></i>Gestión de Categorías
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
                    <!-- Estadísticas Rápidas -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center py-3">
                                    <h3 class="mb-1" id="totalCategorias">0</h3>
                                    <small>Total Categorías</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center py-3">
                                    <h3 class="mb-1" id="categoriasActivas">0</h3>
                                    <small>Categorías Activas</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center py-3">
                                    <h3 class="mb-1" id="categoriasInactivas">0</h3>
                                    <small>Categorías Inactivas</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center py-3">
                                    <h3 class="mb-1" id="totalEtiquetas">0</h3>
                                    <small>Total Etiquetas</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Barra de Herramientas -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row g-3 align-items-center">
                                        <div class="col-md-6">
                                            <div class="input-group">
                                                <input type="text" class="form-control" placeholder="Buscar categoría..." id="searchInput">
                                                <button class="btn btn-primary" type="button" id="searchBtn">
                                                    <i class="fas fa-search"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <select class="form-select" id="estadoFilter">
                                                <option value="">Todos los estados</option>
                                                <option value="activa">Solo activas</option>
                                                <option value="inactiva">Solo inactivas</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <button type="button" class="btn btn-primary" data-mdb-ripple-init data-mdb-modal-init data-mdb-target="#modalCategoria">
                                                <i class="fas fa-plus me-2"></i>Nueva Categoría
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Vista de Tarjetas -->
                    <div class="row mb-4" id="vistaTarjetas">
                        <div class="col-12">
                            <h5 class="mb-3">
                                <i class="fas fa-th-large me-2 text-primary"></i>Vista de Tarjetas
                            </h5>
                            <div class="row" id="categoriasGrid">
                                <!-- Las categorías se mostrarán aquí como tarjetas -->
                            </div>
                        </div>
                    </div>

                    <!-- Vista de Tabla -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <i class="fas fa-table me-2"></i>Vista de Tabla
                                    </h5>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="toggleVista" checked>
                                        <label class="form-check-label" for="toggleVista">Mostrar tabla</label>
                                    </div>
                                </div>
                                <div class="card-body" id="vistaTabla">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Nombre</th>
                                                    <th>Descripción</th>
                                                    <th>Estado</th>
                                                    <th>Etiquetas</th>
                                                    <th>Fecha Creación</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody id="categoriasTableBody">
                                                <!-- Las categorías se mostrarán aquí en tabla -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        

    <!-- Modal para Crear/Editar Categoría -->
    <div class="modal fade" id="modalCategoria" tabindex="-1" aria-labelledby="modalCategoriaLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalCategoriaLabel">
                        <i class="fas fa-folder-plus me-2"></i>Nueva Categoría
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formCategoria">
                    <div class="modal-body">
                        <input type="hidden" id="categoriaId">
                        <div class="mb-3">
                            <div class="form-outline" data-mdb-input-init>
                                <input type="text" id="categoriaNombre" class="form-control" required />
                                <label class="form-label" for="categoriaNombre">
                                    <i class="fas fa-tag me-2"></i>Nombre de la Categoría *
                                </label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-outline" data-mdb-input-init>
                                <textarea class="form-control" id="categoriaDescripcion" rows="3"></textarea>
                                <label class="form-label" for="categoriaDescripcion">
                                    <i class="fas fa-align-left me-2"></i>Descripción
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success" id="btnGuardarCategoria">
                            <i class="fas fa-save me-2"></i>Guardar Categoría
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para Ver Detalles -->
    <div class="modal fade" id="modalDetalleCategoria" tabindex="-1" aria-labelledby="modalDetalleCategoriaLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="modalDetalleCategoriaLabel">
                        <i class="fas fa-info-circle me-2"></i>Detalles de Categoría
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-mdb-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="detalleCategoriaBody">
                    <!-- Los detalles se cargarán aquí -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" id="btnEditarDesdeDetalle">Editar Categoría</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmación Eliminar -->
    <div class="modal fade" id="modalConfirmarEliminar" tabindex="-1" aria-labelledby="modalConfirmarEliminarLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="modalConfirmarEliminarLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>Confirmar Eliminación
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-mdb-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="mensajeEliminacion">¿Estás seguro de que quieres eliminar esta categoría?</p>
                    <div class="alert alert-warning" id="alertaEtiquetas" style="display: none;">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <strong>Advertencia:</strong> Esta categoría tiene <span id="cantidadEtiquetas">0</span> etiquetas asociadas. 
                        Si la eliminas, estas etiquetas quedarán sin categoría.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="btnConfirmarEliminar">Sí, Eliminar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MDBootstrap JS -->
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/9.2.0/mdb.umd.min.js"></script>
    
    <script src="js/script.js"></script>
    <script>
        let userData = null;
        let authToken = null;
        let categorias = [];
        let categoriaAEliminar = null;

        
        // Cargar categorías desde la API
        async function cargarCategorias() {
            try {
                const formData = new FormData();
                formData.append('peticion', 'listar');
                formData.append('token', authToken);


                const response = await fetch('controllers/categorias.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.exito) {
                    categorias = result.data;
                    mostrarCategorias();
                    actualizarEstadisticas();
                } else {
                    throw new Error(result.msj);
                    return [];
                }
            } catch (error) {
                console.error('Error cargando categorías:', error);
                mostrarMensaje('error', 'Error al cargar las categorías');
            }
        }

        // Mostrar categorías en vista de tarjetas
        function mostrarCategoriasGrid() {
            const grid = document.getElementById('categoriasGrid');
            grid.innerHTML = '';

            categorias.forEach(categoria => {
                const col = document.createElement('div');
                col.className = 'col-xl-3 col-lg-4 col-md-6 mb-4';
                
                col.innerHTML = `
                    <div class="card categoria-card ${!categoria.activa ? 'categoria-inactiva' : ''}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-folder ${categoria.activa ? 'text-primary' : 'text-secondary'} me-2"></i>
                                    ${categoria.nombre}
                                </h5>
                                <span class="badge bg-${categoria.activa ? 'success' : 'secondary'}">
                                    ${categoria.activa ? 'Activa' : 'Inactiva'}
                                </span>
                            </div>
                            
                            <p class="card-text text-muted small mb-3">
                                ${categoria.descripcion || 'Sin descripción'}
                            </p>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge bg-info badge-cantidad">
                                    <i class="fas fa-tags me-1"></i>
                                    ${categoria.cantidad_etiquetas || 0} etiquetas
                                </span>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-info" onclick="verDetalleCategoria(${categoria.id})" title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-warning" onclick="editarCategoria(${categoria.id})" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="confirmarEliminarCategoria(${categoria.id})" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                grid.appendChild(col);
            });
        }

        // Mostrar categorías en vista de tabla
        function mostrarCategoriasTable() {
            const tbody = document.getElementById('categoriasTableBody');
            tbody.innerHTML = '';

            categorias.forEach(categoria => {
                const tr = document.createElement('tr');
                tr.className = !categoria.activa ? 'categoria-inactiva' : '';
                
                tr.innerHTML = `
                    <td>
                        <strong>${categoria.nombre}</strong>
                    </td>
                    <td>${categoria.descripcion || '—'}</td>
                    <td>
                        <span class="badge bg-${categoria.activa ? 'success' : 'secondary'}">
                            ${categoria.activa ? 'Activa' : 'Inactiva'}
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-info">${categoria.cantidad_etiquetas || 0}</span>
                    </td>
                    <td>${new Date(categoria.fecha_creacion).toLocaleDateString('es-ES')}</td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-info" onclick="verDetalleCategoria(${categoria.id})" title="Ver detalles">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-outline-warning" onclick="editarCategoria(${categoria.id})" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-outline-danger" onclick="confirmarEliminarCategoria(${categoria.id})" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                `;
                
                tbody.appendChild(tr);
            });
        }

        function mostrarCategorias() {
            mostrarCategoriasGrid();
            mostrarCategoriasTable();
        }

        // Actualizar estadísticas
        function actualizarEstadisticas() {
            const total = categorias.length;
            const activas = categorias.filter(c => c.activa).length;
            const inactivas = categorias.filter(c => !c.activa).length;
            const totalEtiquetas = categorias.reduce((sum, c) => sum + (c.cantidad_etiquetas || 0), 0);

            document.getElementById('totalCategorias').textContent = total;
            document.getElementById('categoriasActivas').textContent = activas;
            document.getElementById('categoriasInactivas').textContent = inactivas;
            document.getElementById('totalEtiquetas').textContent = totalEtiquetas;
        }

        // Crear nueva categoría
        async function crearCategoria(event) {
            event.preventDefault();
            
            const btn = document.getElementById('btnGuardarCategoria');
            const originalText = btn.innerHTML;
            
            try {
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Guardando...';
                btn.disabled = true;

                const formData = new FormData();
                formData.append('peticion', 'crear');
                formData.append('token', authToken);
                formData.append('nombre', document.getElementById('categoriaNombre').value);
                formData.append('descripcion', document.getElementById('categoriaDescripcion').value);

                const response = await fetch('controllers/categorias.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.exito) {
                    mostrarMensaje('success', 'Categoría creada exitosamente');
                    document.getElementById('formCategoria').reset();
                    const modal = mdb.Modal.getInstance(document.getElementById('modalCategoria'));
                    modal.hide();
                    await cargarCategorias();
                } else {
                    throw new Error(result.msj);
                }
            } catch (error) {
                console.error('Error creando categoría:', error);
                mostrarMensaje('error', error.message || 'Error al crear la categoría');
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }

        // Editar categoría
        async function editarCategoria(id) {
            const categoria = categorias.find(c => c.id === id);
            if (!categoria) return;

            // Llenar el formulario
            document.getElementById('categoriaId').value = categoria.id;
            document.getElementById('categoriaNombre').value = categoria.nombre;
            document.getElementById('categoriaDescripcion').value = categoria.descripcion || '';
            document.getElementById('categoriaActiva').checked = categoria.activa;

            // Cambiar título del modal
            document.getElementById('modalCategoriaLabel').innerHTML = 
                '<i class="fas fa-edit me-2"></i>Editar Categoría';

            // Mostrar modal
            const modal = new mdb.Modal(document.getElementById('modalCategoria'));
            modal.show();
        }

        // Actualizar categoría
        async function actualizarCategoria(event) {
            event.preventDefault();
            
            const btn = document.getElementById('btnGuardarCategoria');
            const originalText = btn.innerHTML;
            const id = document.getElementById('categoriaId').value;
            
            try {
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Actualizando...';
                btn.disabled = true;

                const formData = new FormData();
                formData.append('peticion', 'actualizar');
                formData.append('token', authToken);
                formData.append('id', id);
                formData.append('nombre', document.getElementById('categoriaNombre').value);
                formData.append('descripcion', document.getElementById('categoriaDescripcion').value);
                formData.append('activa', document.getElementById('categoriaActiva').checked ? '1' : '0');

                const response = await fetch('controllers/categorias.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.exito) {
                    mostrarMensaje('success', 'Categoría actualizada exitosamente');
                    document.getElementById('formCategoria').reset();
                    document.getElementById('categoriaId').value = '';
                    const modal = mdb.Modal.getInstance(document.getElementById('modalCategoria'));
                    modal.hide();
                    await cargarCategorias();
                } else {
                    throw new Error(result.msj);
                }
            } catch (error) {
                console.error('Error actualizando categoría:', error);
                mostrarMensaje('error', error.message || 'Error al actualizar la categoría');
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }

        // Ver detalles de categoría
        async function verDetalleCategoria(id) {
            const categoria = categorias.find(c => c.id === id);
            if (!categoria) return;

            const modalBody = document.getElementById('detalleCategoriaBody');
            modalBody.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Información General</h6>
                        <p><strong>Nombre:</strong> ${categoria.nombre}</p>
                        <p><strong>Descripción:</strong> ${categoria.descripcion || 'No especificada'}</p>
                        <p><strong>Estado:</strong> 
                            <span class="badge bg-${categoria.activa ? 'success' : 'secondary'}">
                                ${categoria.activa ? 'Activa' : 'Inactiva'}
                            </span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6>Estadísticas</h6>
                        <p><strong>Etiquetas asociadas:</strong> ${categoria.cantidad_etiquetas || 0}</p>
                        <p><strong>Fecha de creación:</strong> ${new Date(categoria.fecha_creacion).toLocaleDateString('es-ES')}</p>
                        <p><strong>Última actualización:</strong> ${new Date(categoria.fecha_actualizacion).toLocaleDateString('es-ES')}</p>
                    </div>
                </div>
                ${categoria.cantidad_etiquetas > 0 ? `
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Esta categoría tiene ${categoria.cantidad_etiquetas} etiqueta(s) asociada(s). 
                            Si la desactivas, estas etiquetas seguirán existiendo pero no podrás asignar esta categoría a nuevas etiquetas.
                        </div>
                    </div>
                </div>
                ` : ''}
            `;

            // Configurar botón de editar
            document.getElementById('btnEditarDesdeDetalle').onclick = () => {
                const modalDetalle = mdb.Modal.getInstance(document.getElementById('modalDetalleCategoria'));
                modalDetalle.hide();
                setTimeout(() => editarCategoria(id), 300);
            };

            const modal = new mdb.Modal(document.getElementById('modalDetalleCategoria'));
            modal.show();
        }

        // Confirmar eliminación de categoría
        function confirmarEliminarCategoria(id) {
            const categoria = categorias.find(c => c.id === id);
            if (!categoria) return;

            categoriaAEliminar = categoria;

            document.getElementById('mensajeEliminacion').textContent = 
                `¿Estás seguro de que quieres eliminar la categoría "${categoria.nombre}"?`;

            // Mostrar advertencia si tiene etiquetas
            const alerta = document.getElementById('alertaEtiquetas');
            if (categoria.cantidad_etiquetas > 0) {
                document.getElementById('cantidadEtiquetas').textContent = categoria.cantidad_etiquetas;
                alerta.style.display = 'block';
            } else {
                alerta.style.display = 'none';
            }

            const modal = new mdb.Modal(document.getElementById('modalConfirmarEliminar'));
            modal.show();
        }

        // Eliminar categoría
        async function eliminarCategoria() {
            if (!categoriaAEliminar) return;

            try {
                const formData = new FormData();
                formData.append('peticion', 'eliminar');
                formData.append('token', authToken);
                formData.append('id', categoriaAEliminar.id);

                const response = await fetch('controllers/categorias.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.exito) {
                    mostrarMensaje('success', 'Categoría eliminada exitosamente');
                    const modal = mdb.Modal.getInstance(document.getElementById('modalConfirmarEliminar'));
                    modal.hide();
                    await cargarCategorias();
                } else {
                    throw new Error(result.msj);
                }
            } catch (error) {
                console.error('Error eliminando categoría:', error);
                mostrarMensaje('error', error.message || 'Error al eliminar la categoría');
            } finally {
                categoriaAEliminar = null;
            }
        }

        // Filtrar categorías
        function filtrarCategorias() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const estadoFilter = document.getElementById('estadoFilter').value;

            let categoriasFiltradas = categorias;

            if (searchTerm) {
                categoriasFiltradas = categoriasFiltradas.filter(c => 
                    c.nombre.toLowerCase().includes(searchTerm) || 
                    (c.descripcion && c.descripcion.toLowerCase().includes(searchTerm))
                );
            }

            if (estadoFilter) {
                categoriasFiltradas = categoriasFiltradas.filter(c => 
                    estadoFilter === 'activa' ? c.activa : !c.activa
                );
            }

            // Actualizar vistas con datos filtrados
            const originalCategorias = categorias;
            categorias = categoriasFiltradas;
            mostrarCategorias();
            categorias = originalCategorias;
        }

        // Inicialización
        document.addEventListener('DOMContentLoaded', async function() {
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
            
            // Cargar categorías
            await cargarCategorias();
            
            // Configurar event listeners
            document.getElementById('logoutBtn').addEventListener('click', logout);
            document.getElementById('dropdownLogoutBtn').addEventListener('click', logout);
            document.getElementById('btnConfirmarEliminar').addEventListener('click', eliminarCategoria);
            document.getElementById('searchBtn').addEventListener('click', filtrarCategorias);
            document.getElementById('estadoFilter').addEventListener('change', filtrarCategorias);
            document.getElementById('searchInput').addEventListener('input', filtrarCategorias);
            
            // Toggle entre vistas
            document.getElementById('toggleVista').addEventListener('change', function() {
                document.getElementById('vistaTabla').style.display = this.checked ? 'block' : 'none';
                document.getElementById('vistaTarjetas').style.display = this.checked ? 'none' : 'block';
            });

            // Configurar formulario
            const formCategoria = document.getElementById('formCategoria');
            formCategoria.addEventListener('submit', function(event) {
                const id = document.getElementById('categoriaId').value;
                if (id) {
                    actualizarCategoria(event);
                } else {
                    crearCategoria(event);
                }
            });

            // Resetear formulario cuando se cierra el modal
            document.getElementById('modalCategoria').addEventListener('hidden.mdb.modal', function() {
                document.getElementById('formCategoria').reset();
                document.getElementById('categoriaId').value = '';
                document.getElementById('modalCategoriaLabel').innerHTML = 
                    '<i class="fas fa-folder-plus me-2"></i>Nueva Categoría';
            });
            
            setInterval(updateCurrentTime, 60000);
        });

        function mostrarMensaje(tipo, mensaje) {
            // Implementar sistema de mensajes (toast)
            const toast = document.createElement('div');
            toast.className = `alert alert-${tipo} alert-dismissible fade show position-fixed`;
            toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            toast.innerHTML = `
                ${mensaje}
                <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
            `;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 5000);
        }

        function logout() {
            localStorage.removeItem('auth_token');
            localStorage.removeItem('user');
            window.location.href = 'cerrar_sesion.php';
        }
    </script>
</body>
</html>