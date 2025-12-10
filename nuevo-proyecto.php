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
    <title>Nuevo Proyecto - Sistema Inventarios</title>
    
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
        .form-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .etiqueta-img {
            width: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
        .cantidad-input {
            width: 100px;
            text-align: center;
        }
        .stock-info {
            font-size: 0.8em;
        }
        .search-box {
            position: relative;
        }
        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }
        .search-item {
            padding: 10px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .search-item:hover {
            background-color: #f8f9fa;
        }
        .search-item-img {
            width: 40px;
            object-fit: cover;
            border-radius: 4px;
        }
        .etiqueta-table-row {
            transition: background-color 0.2s;
        }
        .etiqueta-table-row:hover {
            background-color: #f8f9fa;
        }
        .tamanos-container {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            border: 1px solid #dee2e6;
        }
        .tamano-option {
            margin-bottom: 5px;
        }
        .tamano-badge {
            font-size: 0.75em;
        }
        .modal-tamano {
            max-width: 500px;
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
                            <i class="fas fa-plus-circle me-2"></i>Nuevo Proyecto
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
                    <div class="row justify-content-center">
                        <div class="col-lg-12">
                            <!-- Card del Formulario -->
                            <div class="card form-card">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-project-diagram me-2"></i>Crear Nuevo Proyecto
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <form id="formNuevoProyecto">
                                        <!-- Información Básica -->
                                        <div class="row mb-4">
                                            <div class="col-12">
                                                <h6 class="fw-bold text-primary mb-3">
                                                    <i class="fas fa-info-circle me-2"></i>Información Básica del Proyecto
                                                </h6>
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">Código del Proyecto *</label>
                                                <input type="text" class="form-control" id="codigoProyecto" required 
                                                       placeholder="0000" maxlength="50">
                                                <div class="form-text">Código único para identificar el proyecto</div>
                                            </div>
                                            
                                            <div class="col-12 mb-3">
                                                <label class="form-label fw-bold">Nombre del Proyecto *</label>
                                                <input type="text" class="form-control" id="nombreProyecto" required 
                                                       placeholder="Ej: Implementación de Sistema RFID para Inventario" maxlength="200">
                                            </div>
                                            
                                            <div class="col-12 mb-3">
                                                <label class="form-label fw-bold">Descripción</label>
                                                <textarea class="form-control" id="descripcionProyecto" rows="4" 
                                                          placeholder="Describe los objetivos, alcance y detalles del proyecto..."></textarea>
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">Fecha de Inicio</label>
                                                <input type="date" class="form-control" id="fechaInicioProyecto">
                                            </div>
                                        </div>

                                        <!-- Selección de Etiquetas con Cantidades -->
                                        <div class="row mb-4">
                                            <div class="col-12">
                                                <h6 class="fw-bold text-primary mb-3">
                                                    <i class="fas fa-tags me-2"></i>Etiquetas y Cantidades del Proyecto
                                                </h6>
                                                
                                                <!-- Buscador de Etiquetas -->
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Buscar y Agregar Etiquetas</label>
                                                    <div class="search-box">
                                                        <input type="text" class="form-control" id="buscarEtiqueta" 
                                                               placeholder="Buscar etiquetas por nombre, código o categoría...">
                                                        <div class="search-results" id="resultadosBusqueda"></div>
                                                    </div>
                                                    <div class="form-text">
                                                        Busca etiquetas y haz clic para agregarlas a la lista. Luego especifica el tamaño y cantidad requerida.
                                                    </div>
                                                </div>
                                                
                                                <!-- Tabla de Etiquetas Seleccionadas -->
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Etiquetas Seleccionadas</label>
                                                    <div class="table-responsive">
                                                        <table class="table table-hover text-center" id="tablaEtiquetas">
                                                            <thead class="table-light">
                                                                <tr>
                                                                    <th width="60px">Imagen</th>
                                                                    <th>Etiqueta</th>
                                                                    <th width="140px">Tamaño</th>
                                                                    <th width="140px">Categoría</th>
                                                                    <th width="90px">Stock Disponible</th>
                                                                    <th width="160px">Cantidad Requerida</th>
                                                                    <th width="80px">Acciones</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody id="etiquetasSeleccionadasBody">
                                                                <tr id="emptyState">
                                                                    <td colspan="7" class="text-center text-muted py-4">
                                                                        <i class="fas fa-inbox fa-2x mb-2"></i>
                                                                        <p>No hay etiquetas seleccionadas</p>
                                                                        <small>Busca y agrega etiquetas usando el campo de búsqueda arriba</small>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Resumen del Proyecto -->
                                        <div class="row mb-4">
                                            <div class="col-12">
                                                <h6 class="fw-bold text-primary mb-3">
                                                    <i class="fas fa-clipboard-check me-2"></i>Resumen del Proyecto
                                                </h6>
                                                <div class="card bg-light">
                                                    <div class="card-body">
                                                        <div class="row">
                                                            <div class="col-md-4">
                                                                <p><strong>Código:</strong> <span id="resumenCodigo" class="text-muted">-</span></p>
                                                                <p><strong>Nombre:</strong> <span id="resumenNombre" class="text-muted">-</span></p>
                                                            </div>
                                                            <div class="col-md-4">
                                                                
                                                                <p><strong>Fecha Inicio:</strong> <span id="resumenFecha" class="text-muted">-</span></p>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <p><strong>Etiquetas:</strong> <span id="resumenCantidadEtiquetas" class="text-muted">0</span></p>
                                                                <p><strong>Total Unidades:</strong> <span id="resumenTotalUnidades" class="text-muted">0</span></p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Botones de Acción -->
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="d-flex justify-content-between">
                                                    <button type="button" class="btn btn-outline-secondary" onclick="cancelarCreacion()">
                                                        <i class="fas fa-times me-1"></i>Cancelar
                                                    </button>
                                                    <div>
                                                        <button type="reset" class="btn btn-outline-warning me-2" onclick="limpiarFormulario()">
                                                            <i class="fas fa-redo me-1"></i>Limpiar Formulario
                                                        </button>
                                                        <button type="submit" class="btn btn-success">
                                                            <i class="fas fa-save me-1"></i>Crear Proyecto
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        

    <!-- Modal para seleccionar tamaño -->
    <div class="modal fade" id="modalSeleccionarTamano" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-tamano">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Seleccionar Tamaño</h5>
                    <button type="button" class="btn-close btn-close-white" data-mdb-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="modalEtiquetaInfo" class="mb-3"></div>
                    <div class="tamanos-container" id="modalTamanosContainer">
                        <p class="text-center text-muted">Cargando tamaños disponibles...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnConfirmarTamano">Confirmar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MDBootstrap JS -->
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/9.2.0/mdb.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        let userData = null;
        let authToken = null;
        let etiquetas = [];
        let etiquetasSeleccionadas = [];
        let etiquetaSeleccionadaTemp = null;

        // Cargar etiquetas disponibles
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
                    console.log('Etiquetas cargadas:', etiquetas.length);
                } else {
                    throw new Error(result.msj);
                }
            } catch (error) {
                console.error('Error cargando etiquetas:', error);
                mostrarMensaje('error', 'Error al cargar las etiquetas');
            }
        }

        // Buscar etiquetas
        function buscarEtiquetas(termino) {
            if (!termino || termino.length < 2) {
                document.getElementById('resultadosBusqueda').style.display = 'none';
                return;
            }

            const terminoLower = termino.toLowerCase();
            const resultados = etiquetas.filter(etiqueta => 
                etiqueta.activa && (
                    etiqueta.nombre.toLowerCase().includes(terminoLower) ||
                    (etiqueta.categoria_nombre && etiqueta.categoria_nombre.toLowerCase().includes(terminoLower))
                )
            );

            const resultadosHTML = resultados.map(etiqueta => {
                const yaSeleccionada = etiquetasSeleccionadas.some(e => e.id === etiqueta.id);
                
                return `
                    <div class="search-item" onclick="seleccionarEtiqueta(${etiqueta.id})">
                        <img src="${etiqueta.foto_url ? 'uploads/' + etiqueta.foto_url : 'https://placehold.co/100x100?text=SIN+FOTO'}" 
                             class="search-item-img" alt="${etiqueta.nombre}">
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>${etiqueta.nombre}</strong>
                                    <br>
                                    ${etiqueta.categoria_nombre ? `<br><small class="text-muted">Categoría: ${etiqueta.categoria_nombre}</small>` : ''}
                                </div>
                                <div class="text-end">
                                    ${yaSeleccionada ? '<small class="text-success">Ya agregada</small>' : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');

            const resultadosContainer = document.getElementById('resultadosBusqueda');
            resultadosContainer.innerHTML = resultadosHTML || '<div class="search-item text-muted">No se encontraron etiquetas</div>';
            resultadosContainer.style.display = 'block';
        }

        // Seleccionar etiqueta y mostrar modal de tamaños
        async function seleccionarEtiqueta(etiquetaId) {
            const etiqueta = etiquetas.find(e => e.id === etiquetaId);
            if (!etiqueta) return;

            etiquetaSeleccionadaTemp = etiqueta;

            // Mostrar información de la etiqueta en el modal
            document.getElementById('modalEtiquetaInfo').innerHTML = `
                <div class="d-flex align-items-center gap-3">
                    <img src="${etiqueta.foto_url ? 'uploads/' + etiqueta.foto_url : 'https://placehold.co/100x100?text=SIN+FOTO'}" 
                         class="rounded" style="width: 60px; height: 60px; object-fit: cover;">
                    <div>
                        <h6 class="mb-1">${etiqueta.nombre}</h6>
                        <small class="text-muted">${etiqueta.categoria_nombre || 'Sin categoría'}</small>
                    </div>
                </div>
            `;

            // Cargar tamaños disponibles
            await cargarTamanosEtiqueta(etiquetaId);

            // Mostrar modal
            const modal = new mdb.Modal(document.getElementById('modalSeleccionarTamano'));
            modal.show();
            
            document.getElementById('buscarEtiqueta').value = '';
            document.getElementById('resultadosBusqueda').style.display = 'none';
        }

        // Cargar tamaños de una etiqueta
        async function cargarTamanosEtiqueta(etiquetaId) {
            try {
                const container = document.getElementById('modalTamanosContainer');
                container.innerHTML = '<p class="text-center text-muted">Cargando tamaños disponibles...</p>';

                const formData = new FormData();
                formData.append('peticion', 'consultar_tamanos');
                formData.append('etiqueta_id', etiquetaId);
                formData.append('token', authToken);

                const response = await fetch('controllers/etiquetas.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.exito && data.tamanos.length > 0) {
                    let html = '<label class="form-label fw-bold">Seleccione un tamaño:</label>';
                    
                    data.tamanos.forEach((tamano, index) => {
                        const tamanoId = `tamano-${tamano.id || tamano.alto + '-' + tamano.ancho}`;
                        const badgeClass = tamano.stock_actual === 0 ? 'bg-danger' : 
                                        tamano.stock_actual <= 10 ? 'bg-warning' : 'bg-success';
                        
                        html += `
                            <div class="tamano-option">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" 
                                        name="tamano_seleccionado" 
                                        id="${tamanoId}"
                                        value='${JSON.stringify(tamano)}'
                                        ${index === 0 ? 'checked' : ''}>
                                    <label class="form-check-label" for="${tamanoId}">
                                        ${tamano.alto} x ${tamano.ancho} cm
                                        <span class="badge ${badgeClass} tamano-badge ms-2">
                                            ${tamano.stock_actual || 0} disponibles
                                        </span>
                                    </label>
                                </div>
                            </div>
                        `;
                    });
                    container.innerHTML = html;
                } else {
                    container.innerHTML = '<p class="text-warning text-center">No hay tamaños disponibles para esta etiqueta</p>';
                }
            } catch (error) {
                console.error('Error cargando tamaños:', error);
                document.getElementById('modalTamanosContainer').innerHTML = 
                    '<p class="text-danger text-center">Error al cargar los tamaños</p>';
            }
        }

        // Confirmar selección de tamaño
        document.getElementById('btnConfirmarTamano').addEventListener('click', function() {
            const radioSeleccionado = document.querySelector('input[name="tamano_seleccionado"]:checked');
            
            if (!radioSeleccionado) {
                mostrarMensaje('warning', 'Por favor seleccione un tamaño');
                return;
            }

            const tamano = JSON.parse(radioSeleccionado.value);
            agregarEtiquetaConTamano(etiquetaSeleccionadaTemp, tamano);
            
            // Cerrar modal
            const modal = mdb.Modal.getInstance(document.getElementById('modalSeleccionarTamano'));
            modal.hide();
        });

        // Agregar etiqueta con tamaño específico
        function agregarEtiquetaConTamano(etiqueta, tamano) {
            // Verificar si ya está seleccionada con el mismo tamaño (usando ID del tamaño si existe)
            const yaExiste = etiquetasSeleccionadas.some(e => 
                e.id === etiqueta.id && 
                (tamano.id ? e.tamano_id === tamano.id : (e.alto === tamano.alto && e.ancho === tamano.ancho))
            );

            if (yaExiste) {
                mostrarMensaje('warning', 'Esta etiqueta con el mismo tamaño ya está en la lista');
                return;
            }

            // Agregar a la lista con el tamaño seleccionado
            etiquetasSeleccionadas.push({
                ...etiqueta,
                tamano_id: tamano.id, // ← NUEVO: Incluir el ID del tamaño
                alto: tamano.alto,
                ancho: tamano.ancho,
                cantidad_requerida: 1,
                stock_por_tamano: tamano.stock_actual
            });

            actualizarTablaEtiquetas();
            actualizarResumen();
        }

        // Remover etiqueta de la tabla - CORREGIDO
        function removerEtiqueta(etiquetaId, tamanoId, alto, ancho) {
            etiquetasSeleccionadas = etiquetasSeleccionadas.filter(e => {
                // Si tenemos tamano_id, usamos ese para identificar
                if (tamanoId) {
                    return !(e.id === etiquetaId && e.tamano_id === tamanoId);
                }
                // Si no tenemos tamano_id, usamos alto y ancho
                return !(e.id === etiquetaId && e.alto === alto && e.ancho === ancho);
            });
            actualizarTablaEtiquetas();
            actualizarResumen();
        }

        // Actualizar cantidad de una etiqueta - CORREGIDO
        function actualizarCantidad(etiquetaId, tamanoId, alto, ancho, cantidad) {
            const etiqueta = etiquetasSeleccionadas.find(e => {
                if (tamanoId) {
                    return e.id === etiquetaId && e.tamano_id === tamanoId;
                }
                return e.id === etiquetaId && e.alto === alto && e.ancho === ancho;
            });
            if (etiqueta) {
                etiqueta.cantidad_requerida = parseInt(cantidad) || 1;
                actualizarResumen();
            }
        }

        // Actualizar tabla de etiquetas - CORREGIDO
        function actualizarTablaEtiquetas() {
            const tbody = document.getElementById('etiquetasSeleccionadasBody');
            const emptyState = document.getElementById('emptyState');

            if (etiquetasSeleccionadas.length === 0) {
                tbody.innerHTML = '';
                tbody.appendChild(emptyState);
                return;
            }

            let html = '';
            etiquetasSeleccionadas.forEach(etiqueta => {
                // Determinar color según stock
                let badgeClass = 'bg-success';
                if (etiqueta.stock_por_tamano === 0) {
                    badgeClass = 'bg-danger';
                } else if (etiqueta.stock_por_tamano <= 10) {
                    badgeClass = 'bg-warning';
                }

                // Usar tamano_id si está disponible, sino alto y ancho
                const identificadorTamano = etiqueta.tamano_id ? 
                    `'${etiqueta.tamano_id}'` : 
                    `${etiqueta.alto}, ${etiqueta.ancho}`;

                html += `
                    <tr class="etiqueta-table-row">
                        <td>
                            <img src="${etiqueta.foto_url ? 'uploads/' + etiqueta.foto_url : 'https://placehold.co/100x100?text=SIN+FOTO'}" 
                                 class="etiqueta-img" alt="${etiqueta.nombre}">
                        </td>
                        <td>
                            <strong>${etiqueta.nombre}</strong>
                            ${etiqueta.descripcion ? `<br><small class="text-muted">${etiqueta.descripcion.substring(0, 50)}${etiqueta.descripcion.length > 50 ? '...' : ''}</small>` : ''}
                        </td>
                        <td>
                            <strong>${etiqueta.alto} x ${etiqueta.ancho} cm</strong>
                        </td>
                        <td>${etiqueta.categoria_nombre || 'Sin categoría'}</td>
                        <td>
                            <span class="badge ${badgeClass}">${etiqueta.stock_por_tamano} unidades</span>
                        </td>
                        <td>
                            <div class="input-group input-group-sm">
                                <input type="number" 
                                       class="form-control cantidad-input" 
                                       style="padding: 0;"
                                       value="${etiqueta.cantidad_requerida}" 
                                       min="1" 
                                       onchange="actualizarCantidad(${etiqueta.id}, ${etiqueta.tamano_id || 'null'}, ${etiqueta.alto}, ${etiqueta.ancho}, this.value)">
                                <span class="input-group-text">unid.</span>
                            </div>
                            ${etiqueta.cantidad_requerida > etiqueta.stock_por_tamano ? 
                                '<small class="text-danger">Stock insuficiente</small>' : ''}
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                    onclick="removerEtiqueta(${etiqueta.id}, ${etiqueta.tamano_id || 'null'}, ${etiqueta.alto}, ${etiqueta.ancho})" title="Quitar">
                                <i class="fas fa-times"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });

            tbody.innerHTML = html;
        }

        // Actualizar resumen general del proyecto
        function actualizarResumen() {
            document.getElementById('resumenCodigo').textContent = document.getElementById('codigoProyecto').value || '-';
            document.getElementById('resumenNombre').textContent = document.getElementById('nombreProyecto').value || '-';
            
            document.getElementById('resumenCantidadEtiquetas').textContent = etiquetasSeleccionadas.length;
            
            const totalUnidades = etiquetasSeleccionadas.reduce((sum, etiqueta) => sum + (etiqueta.cantidad_requerida || 0), 0);
            document.getElementById('resumenTotalUnidades').textContent = totalUnidades;
            
            const fechaInicio = document.getElementById('fechaInicioProyecto').value;
            document.getElementById('resumenFecha').textContent = fechaInicio ? new Date(fechaInicio).toLocaleDateString('es-ES') : '-';
        }

        // Crear nuevo proyecto - CORREGIDO para enviar ID del tamaño
        async function crearProyecto(event) {
            event.preventDefault();
            
            try {
                const codigo = document.getElementById('codigoProyecto').value;
                const nombre = document.getElementById('nombreProyecto').value;
                const descripcion = document.getElementById('descripcionProyecto').value;
                const fechaInicio = document.getElementById('fechaInicioProyecto').value;
                const usuario_id = userData.id;

                // Validaciones
                if (!codigo || !nombre) {
                    mostrarMensaje('error', 'Por favor complete los campos obligatorios');
                    return;
                }

                if (etiquetasSeleccionadas.length === 0) {
                    mostrarMensaje('error', 'Debe agregar al menos una etiqueta al proyecto');
                    return;
                }

                // Preparar datos de etiquetas con tamaños y cantidades - INCLUYENDO ID DEL TAMAÑO
                const etiquetasConTamanoYCantidad = etiquetasSeleccionadas.map(etiqueta => ({
                    id: etiqueta.id,
                    tamano_id: etiqueta.tamano_id, // ← NUEVO: Incluir ID del tamaño
                    alto: etiqueta.alto,
                    ancho: etiqueta.ancho,
                    cantidad_requerida: etiqueta.cantidad_requerida || 1
                }));

                const formData = new FormData();
                formData.append('peticion', 'crear');
                formData.append('token', authToken);
                formData.append('codigo', codigo);
                formData.append('nombre', nombre);
                formData.append('descripcion', descripcion);
                formData.append('fecha_inicio', fechaInicio);
                formData.append('etiquetas', JSON.stringify(etiquetasConTamanoYCantidad));
                formData.append('usuario_id', usuario_id);
                
                const response = await fetch('controllers/proyectos.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.exito) {
                    await Swal.fire({
                        icon: 'success',
                        title: '¡Proyecto Creado!',
                        text: 'El proyecto ha sido creado exitosamente',
                        showConfirmButton: false,
                        timer: 2000
                    });
                    
                    // Redirigir a la lista de proyectos
                    window.location.href = 'proyectos.php';
                } else {
                    throw new Error(result.msj);
                }
            } catch (error) {
                console.error('Error creando proyecto:', error);
                mostrarMensaje('error', error.message || 'Error al crear el proyecto');
            }
        }

        // Limpiar formulario
        function limpiarFormulario() {
            document.getElementById('formNuevoProyecto').reset();
            etiquetasSeleccionadas = [];
            actualizarTablaEtiquetas();
            actualizarResumen();
        }

        // Cancelar creación
        function cancelarCreacion() {
            Swal.fire({
                title: '¿Cancelar creación?',
                text: "Los datos no guardados se perderán",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, cancelar',
                cancelButtonText: 'Continuar editando'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'proyectos.php';
                }
            });
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
            
            // Cargar etiquetas
            await cargarEtiquetas();
            
            // Configurar event listeners
            document.getElementById('logoutBtn').addEventListener('click', logout);
            document.getElementById('dropdownLogoutBtn').addEventListener('click', logout);
            document.getElementById('formNuevoProyecto').addEventListener('submit', crearProyecto);
            
            // Buscador de etiquetas
            document.getElementById('buscarEtiqueta').addEventListener('input', function(e) {
                buscarEtiquetas(e.target.value);
            });
            
            // Ocultar resultados al hacer clic fuera
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.search-box')) {
                    document.getElementById('resultadosBusqueda').style.display = 'none';
                }
            });
            
            // Actualizar resumen en tiempo real
            const camposResumen = ['codigoProyecto', 'nombreProyecto', 'fechaInicioProyecto'];
            camposResumen.forEach(id => {
                document.getElementById(id).addEventListener('input', actualizarResumen);
            });
            
            setInterval(updateCurrentTime, 60000);
        });

        function mostrarMensaje(tipo, mensaje) {
            Swal.fire({
                icon: tipo,
                title: mensaje,
                timer: 3000,
                showConfirmButton: false
            });
        }

        // Funciones auxiliares
        function updateUserInfo() {
            if (userData) {
                document.getElementById('dropdownUserName').textContent = userData.nombre || 'Usuario';
            }
        }

        function updateCurrentTime() {
            const now = new Date();
            document.getElementById('currentTime').textContent = now.toLocaleString('es-ES');
        }

        function logout() {
            localStorage.removeItem('auth_token');
            localStorage.removeItem('user');
            window.location.href = 'cerrar_sesion.php';
        }
    </script>
</body>
</html>