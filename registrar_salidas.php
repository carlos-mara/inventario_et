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
    <title>Registrar Salidas - Sistema Inventarios</title>
    
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
        .salida-card {
            border-left: 4px solid #dc3545;
            transition: all 0.3s;
        }
        .salida-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .etiqueta-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
        }
        .tamanos-container {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 10px;
            border: 1px solid #dee2e6;
        }
        .tamano-option {
            margin-bottom: 8px;
            padding: 8px;
            border-radius: 5px;
            transition: background-color 0.2s;
        }
        .tamano-option:hover {
            background-color: #e9ecef;
        }
        .tamano-badge {
            font-size: 0.75em;
        }
        .stock-info {
            font-size: 0.85em;
            margin-top: 5px;
        }
        .proyecto-select {
            transition: all 0.3s;
        }
        .proyecto-select:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
        /* Estilos para la captura de foto */
        .photo-container {
            position: relative;
            width: 100%;
            height: 300px;
            background-color: #f8f9fa;
            border-radius: 8px;
            overflow: hidden;
            border: 2px dashed #dee2e6;
        }
        .video-preview, .photo-preview {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .photo-controls {
            position: absolute;
            bottom: 15px;
            left: 0;
            right: 0;
            text-align: center;
        }
        .photo-btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            font-size: 24px;
            margin: 0 10px;
        }
        .progress-container {
            display: none;
            margin-top: 10px;
        }
        .cantidad-info-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
            border: 1px solid #dee2e6;
        }
        .cantidad-info-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .cantidad-info-label {
            font-weight: 500;
            color: #495057;
        }
        .cantidad-info-value {
            font-weight: 600;
            color: #212529;
        }
        .cantidad-restante {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #dee2e6;
        }
        .restante-text {
            font-weight: 700;
            font-size: 1.1em;
        }
        .restante-badge {
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    
            <!-- SIDEBAR -->
            <?php include "menu.php"; ?>

            <!-- CONTENIDO PRINCIPAL -->
            <div class="col" class="main-content">
                <!-- Barra Superior -->
                <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
                    <div class="container-fluid">
                        <button class="sidebar-toggle-btn" id="customSidebarToggle">
                            <i class="fas fa-bars"></i>
                        </button>
                        
                        <span class="navbar-brand fw-bold text-primary">
                            <i class="fas fa-arrow-up me-2"></i>Registrar Salidas
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
                    <!-- Banner Informativo -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="alert alert-danger">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-info-circle fa-2x me-3"></i>
                                    <div>
                                        <h5 class="alert-heading mb-1">Registro de Salidas</h5>
                                        <p class="mb-0">Registra salidas de inventario para mantener actualizado tu stock</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Formulario de Salida -->
                        <div class="col-lg-6 mb-4">
                            <div class="card salida-card">
                                <div class="card-header bg-danger text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-minus-circle me-2"></i>Nueva Salida
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <form id="formSalida" enctype="multipart/form-data">
                                        <!-- Selección de Proyecto (Opcional) -->
                                        <div class="mb-3">
                                            <label for="proyectoId" class="form-label fw-bold">Proyecto <span class="text-muted">(opcional)</span></label>
                                            <div class="input-group">
                                                <select class="form-select proyecto-select" id="proyectoId">
                                                    <option value="">Seleccionar proyecto...</option>
                                                    <option value="">Ninguno (salida general)</option>
                                                </select>
                                                <button class="btn btn-outline-secondary" type="button" onclick="limpiarProyecto()" title="Limpiar selección">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            <div class="form-text">Si la salida está relacionada con un proyecto, selecciónalo aquí</div>
                                        </div>

                                        <!-- Información del Proyecto Seleccionado -->
                                        <div class="alert alert-info p-3 mb-3 d-none" id="infoProyecto">
                                            <div class="row">
                                                <div class="col-8">
                                                    <h6 class="mb-1" id="proyectoNombre"></h6>
                                                    <small id="proyectoCodigo"></small>
                                                </div>
                                                <div class="col-4 text-end">
                                                    <div class="fw-bold" id="proyectoEstado"></div>
                                                    <small>Estado</small>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Selección de Etiqueta -->
                                        <div class="mb-3">
                                            <label for="etiquetaId" class="form-label fw-bold">Etiqueta *</label>
                                            <div class="input-group">
                                                <select class="form-select" id="etiquetaId" required>
                                                    <option value="">Seleccionar etiqueta...</option>
                                                </select>
                                                <button class="btn btn-outline-primary" type="button" onclick="recargarEtiquetas()" title="Recargar etiquetas">
                                                    <i class="fas fa-sync-alt"></i>
                                                </button>
                                            </div>
                                            <div class="form-text" id="etiquetaInfoText">Seleccione primero un proyecto para ver las etiquetas relacionadas</div>
                                        </div>

                                        <!-- Información de Cantidades por Proyecto -->
                                        <div class="cantidad-info-card d-none" id="cantidadInfoCard">
                                            <div class="cantidad-info-item">
                                                <span class="cantidad-info-label">Cantidad asignada al proyecto:</span>
                                                <span class="cantidad-info-value" id="cantidadAsignada">0</span>
                                            </div>
                                            <div class="cantidad-info-item">
                                                <span class="cantidad-info-label">Cantidad ya entregada:</span>
                                                <span class="cantidad-info-value" id="cantidadEntregada">0</span>
                                            </div>
                                            <div class="cantidad-restante">
                                                <div class="cantidad-info-item">
                                                    <span class="cantidad-info-label restante-text">Cantidad restante:</span>
                                                    <span class="cantidad-info-value">
                                                        <span class="badge restante-badge" id="cantidadRestanteBadge">0</span>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Información de la Etiqueta Seleccionada -->
                                        <div class="etiqueta-info p-3 text-white mb-3 d-none" id="infoEtiqueta">
                                            <div class="row">
                                                <div class="col-8">
                                                    <h6 class="mb-1" id="etiquetaNombre"></h6>
                                                    <small id="etiquetaCategoria"></small>
                                                </div>
                                                <div class="col-4 text-end">
                                                    <div class="fw-bold" id="etiquetaStockTotal"></div>
                                                    <small>Stock total</small>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Selección de Tamaño -->
                                        <div class="tamanos-container d-none" id="tamanosContainer">
                                            <label class="form-label fw-bold mb-3">Seleccione el Tamaño *</label>
                                            <div id="tamanosRadioGroup"></div>
                                            
                                            <!-- Información del Tamaño Seleccionado -->
                                            <div class="mt-3 p-3 bg-light rounded d-none" id="infoTamanoSeleccionado">
                                                <div class="row">
                                                    <div class="col-6">
                                                        <strong>Tamaño:</strong>
                                                        <div id="tamanoSeleccionadoTexto"></div>
                                                    </div>
                                                    <div class="col-6 text-end">
                                                        <strong>Stock:</strong>
                                                        <div>
                                                            <span id="stockTamanoSeleccionado" class="badge"></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Cantidad -->
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Cantidad *</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="cantidad" min="1" required placeholder="Ej: 50">
                                                <span class="input-group-text">unidades</span>
                                            </div>
                                            <div class="form-text" id="infoCantidad"></div>
                                        </div>

                                        <!-- Motivo -->
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Motivo de Salida *</label>
                                            <select class="form-select" id="motivo" required>
                                                <option value="">Seleccionar motivo...</option>
                                                <option value="venta">Venta</option>
                                                <option value="consumo_interno">Consumo Interno</option>
                                                <option value="despacho_proyecto">Despacho a Proyecto</option>
                                                <option value="devolucion_cliente">Devolución a Cliente</option>
                                                <option value="merma">Merma/Pérdida</option>
                                                <option value="ajuste_inventario">Ajuste de Inventario</option>
                                                <option value="transferencia">Transferencia Interna</option>
                                                <option value="donacion">Donación</option>
                                                <option value="otros">Otros</option>
                                            </select>
                                        </div>

                                        <!-- Captura de Foto -->
                                        <div class="mb-4">
                                            <label class="form-label fw-bold">Foto de la entrega <span class="text-muted">(opcional)</span></label>
                                            <div class="photo-container mb-2" id="photoContainer">
                                                <video id="videoPreview" class="video-preview d-none"></video>
                                                <img id="photoPreview" class="photo-preview d-none">
                                                <div id="photoPlaceholder" class="d-flex flex-column align-items-center justify-content-center h-100">
                                                    <i class="fas fa-camera fa-4x text-muted mb-3"></i>
                                                    <p class="text-muted text-center">Haga clic para tomar una foto de la entrega</p>
                                                </div>
                                                <div class="photo-controls d-none" id="photoControls">
                                                    <button type="button" class="btn btn-danger photo-btn" onclick="capturePhoto()" title="Tomar foto">
                                                        <i class="fas fa-camera"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-warning photo-btn" onclick="retakePhoto()" title="Volver a tomar">
                                                        <i class="fas fa-redo"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="startCamera()" id="startCameraBtn">
                                                    <i class="fas fa-video me-1"></i>Activar Cámara
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="stopCamera()" id="stopCameraBtn" style="display: none;">
                                                    <i class="fas fa-stop me-1"></i>Detener Cámara
                                                </button>
                                                <button type="button" class="btn btn-outline-info btn-sm" onclick="uploadFile()" id="uploadFileBtn">
                                                    <i class="fas fa-upload me-1"></i>Subir Archivo
                                                </button>
                                            </div>
                                            <input type="file" id="photoFile" accept="image/*" capture="environment" class="d-none" onchange="handleFileSelect(event)">
                                            <input type="hidden" id="fotoBase64" name="fotoBase64">
                                            
                                            <div class="progress-container" id="progressContainer">
                                                <div class="progress mt-2">
                                                    <div id="uploadProgress" class="progress-bar progress-bar-striped progress-bar-animated" 
                                                         role="progressbar" style="width: 0%"></div>
                                                </div>
                                                <small class="text-muted" id="progressText">Comprimiendo imagen...</small>
                                            </div>
                                            <div class="form-text">Tome una foto del material entregado como evidencia</div>
                                        </div>

                                        <!-- Referencia -->
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Referencia <span class="text-muted">(opcional)</span></label>
                                            <input type="text" class="form-control" id="referencia" placeholder="N° Factura, Orden de Venta, etc.">
                                        </div>

                                        <!-- Observaciones -->
                                        <div class="mb-4">
                                            <label class="form-label fw-bold">Observaciones <span class="text-muted">(opcional)</span></label>
                                            <textarea class="form-control" id="observaciones" rows="3" placeholder="Detalles adicionales de la salida..."></textarea>
                                        </div>

                                        <!-- Botones -->
                                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                            <button type="reset" class="btn btn-outline-secondary me-2" onclick="limpiarFormulario()">
                                                <i class="fas fa-redo me-1"></i>Limpiar
                                            </button>
                                            <button type="submit" class="btn btn-danger">
                                                <i class="fas fa-save me-1"></i>Registrar Salida
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6 mb-4">
                            <!-- Foto de etiqueta -->
                            <div class="card mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-tag me-2"></i>Información de la Etiqueta
                                    </h5>
                                </div>
                                <div class="card-body text-center">
                                    <img id="img_etiqueta" class="img-fluid rounded d-none" style="max-height: 300px; object-fit: contain;" src="" alt="Imagen de etiqueta">
                                    <div id="sinImagen" class="text-muted">
                                        <i class="fas fa-image fa-3x mb-2"></i>
                                        <p>Seleccione una etiqueta para ver la imagen</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Información de Stock -->
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-chart-bar me-2"></i>Resumen de Stock
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div id="stockSummary" class="text-muted text-center">
                                        <i class="fas fa-box-open fa-3x mb-3"></i>
                                        <p>Seleccione una etiqueta para ver el resumen de stock</p>
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        let userData = null;
        let authToken = null;
        let etiquetas = [];
        let proyectos = [];
        let tamanosActuales = [];
        let tamanoSeleccionado = null;
        let mediaStream = null;
        let fotoCapturada = null;
        let cantidadAsignadaPorProyecto = 0;
        let cantidadEntregadaPorProyecto = 0;

        // Cargar proyectos activos
        async function cargarProyectos() {
            try {
                const formData = new FormData();
                formData.append('peticion', 'listar');
                formData.append('token', authToken);

                const response = await fetch('controllers/proyectos.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.exito) {
                    proyectos = result.data.filter(p => p.estado == 1); // Solo proyectos activos
                    llenarSelectProyectos();
                } else {
                    throw new Error(result.msj);
                }
            } catch (error) {
                console.error('Error cargando proyectos:', error);
                // No mostrar error, puede continuar sin proyectos
            }
        }

        // Llenar select de proyectos
        function llenarSelectProyectos() {
            const select = document.getElementById('proyectoId');
            // Mantener la primera opción
            select.innerHTML = '<option value="">Seleccionar proyecto...</option><option value="">Ninguno (salida general)</option>';
            
            proyectos.forEach(proyecto => {
                const option = document.createElement('option');
                option.value = proyecto.id;
                option.textContent = `${proyecto.codigo} - ${proyecto.nombre}`;
                select.appendChild(option);
            });
        }

        // Mostrar información del proyecto seleccionado
        function mostrarInfoProyecto(proyectoId) {
            const proyecto = proyectos.find(p => p.id == proyectoId);
            const infoDiv = document.getElementById('infoProyecto');
            
            if (proyecto) {
                document.getElementById('proyectoNombre').textContent = proyecto.nombre;
                document.getElementById('proyectoCodigo').textContent = `Código: ${proyecto.codigo}`;
                document.getElementById('proyectoEstado').textContent = proyecto.estado == 1 ? 'Activo' : 'Inactivo';
                infoDiv.classList.remove('d-none');
                
                // Cargar etiquetas del proyecto
                cargarEtiquetasPorProyecto(proyectoId);
            } else {
                infoDiv.classList.add('d-none');
                // Si no hay proyecto seleccionado, cargar todas las etiquetas
                cargarTodasLasEtiquetas();
            }
        }

        // Cargar etiquetas por proyecto
        async function cargarEtiquetasPorProyecto(proyectoId) {
            try {
                document.getElementById('etiquetaId').innerHTML = '<option value="">Cargando etiquetas...</option>';
                document.getElementById('etiquetaInfoText').textContent = 'Cargando etiquetas del proyecto...';
                
                const formData = new FormData();
                formData.append('peticion', 'listar_por_proyecto');
                formData.append('proyecto_id', proyectoId);
                formData.append('token', authToken);

                const response = await fetch('controllers/etiquetas.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.exito) {
                    etiquetas = result.data;
                    llenarSelectEtiquetas();
                    document.getElementById('etiquetaInfoText').textContent = `Mostrando ${etiquetas.length} etiquetas del proyecto seleccionado`;
                } else {
                    throw new Error(result.msj);
                }
            } catch (error) {
                console.error('Error cargando etiquetas por proyecto:', error);
                mostrarMensaje('error', 'Error al cargar las etiquetas del proyecto');
                cargarTodasLasEtiquetas();
            }
        }

        // Cargar todas las etiquetas (cuando no hay proyecto seleccionado)
        async function cargarTodasLasEtiquetas() {
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
                    llenarSelectEtiquetas();
                    document.getElementById('etiquetaInfoText').textContent = 'Mostrando todas las etiquetas disponibles';
                } else {
                    throw new Error(result.msj);
                }
            } catch (error) {
                console.error('Error cargando todas las etiquetas:', error);
                mostrarMensaje('error', 'Error al cargar las etiquetas');
            }
        }

        // Recargar etiquetas manualmente
        function recargarEtiquetas() {
            const proyectoId = document.getElementById('proyectoId').value;
            if (proyectoId) {
                cargarEtiquetasPorProyecto(proyectoId);
            } else {
                cargarTodasLasEtiquetas();
            }
        }

        // Llenar select de etiquetas
        function llenarSelectEtiquetas() {
            const select = document.getElementById('etiquetaId');
            select.innerHTML = '<option value="">Seleccionar etiqueta...</option>';
            
            etiquetas.forEach(etiqueta => {
                if (etiqueta.activa) {
                    const option = document.createElement('option');
                    option.value = etiqueta.id;
                    option.dataset.cantidad_asignada = etiqueta.cantidad_asignada; 
                    option.dataset.cantidad_entregada = etiqueta.cantidad_entregada; 
                    option.textContent = `${etiqueta.nombre}`;
                    select.appendChild(option);
                }
            });
            
            
            // Limpiar información de cantidades
            document.getElementById('cantidadInfoCard').classList.add('d-none');
            cantidadAsignadaPorProyecto = 0;
            cantidadEntregadaPorProyecto = 0;
        }

        // Mostrar información de etiqueta seleccionada
        async function mostrarInfoEtiqueta(etiquetaId, cantidad_entregada, cantidad_asignada) {
            
            const etiqueta = etiquetas.find(e => e.id == etiquetaId);
            const infoDiv = document.getElementById('infoEtiqueta');
            
            if (etiqueta) {
                document.getElementById('etiquetaNombre').textContent = etiqueta.nombre;
                document.getElementById('etiquetaCategoria').textContent = etiqueta.categoria_nombre || 'Sin categoría';
                
                // Mostrar imagen
                const imgElement = document.getElementById('img_etiqueta');
                const sinImagenDiv = document.getElementById('sinImagen');
                if (etiqueta.foto_url) {
                    imgElement.src = "uploads/" + etiqueta.foto_url;
                    imgElement.classList.remove('d-none');
                    sinImagenDiv.classList.add('d-none');
                } else {
                    imgElement.classList.add('d-none');
                    sinImagenDiv.classList.remove('d-none');
                }
                
                infoDiv.classList.remove('d-none');
                
                // Si hay proyecto seleccionado, cargar información de cantidades
                const proyectoId = document.getElementById('proyectoId').value;
                if (proyectoId) {
                    await cargarCantidadesPorProyecto(cantidad_entregada, cantidad_asignada);
                }
                
                // Cargar tamaños de la etiqueta
                await cargarTamanosEtiqueta(etiquetaId);
            } else {
                infoDiv.classList.add('d-none');
                document.getElementById('img_etiqueta').classList.add('d-none');
                document.getElementById('sinImagen').classList.remove('d-none');
                document.getElementById('cantidadInfoCard').classList.add('d-none');
            }
        }

        // Cargar cantidades por proyecto para la etiqueta seleccionada
        async function cargarCantidadesPorProyecto(cantidad_entregada, cantidad_asignada) {
            try {
                
                    cantidadAsignadaPorProyecto = cantidad_asignada || 0;
                    cantidadEntregadaPorProyecto = cantidad_entregada || 0;
                    
                    // Mostrar información
                    mostrarInfoCantidades();
            } catch (error) {
                console.error('Error cargando cantidades por proyecto:', error);
                cantidadAsignadaPorProyecto = 0;
                cantidadEntregadaPorProyecto = 0;
                mostrarInfoCantidades();
            }
        }

        // Mostrar información de cantidades
        function mostrarInfoCantidades() {
            const infoCard = document.getElementById('cantidadInfoCard');
            const cantidadRestante = cantidadAsignadaPorProyecto - cantidadEntregadaPorProyecto;
            
            document.getElementById('cantidadAsignada').textContent = cantidadAsignadaPorProyecto;
            document.getElementById('cantidadEntregada').textContent = cantidadEntregadaPorProyecto;
            
            const restanteBadge = document.getElementById('cantidadRestanteBadge');
            restanteBadge.textContent = cantidadRestante;
            
            // Color según la cantidad restante
            if (cantidadRestante <= 0) {
                restanteBadge.className = 'badge bg-danger restante-badge';
            } else if (cantidadRestante <= 10) {
                restanteBadge.className = 'badge bg-warning restante-badge';
            } else {
                restanteBadge.className = 'badge bg-success restante-badge';
            }
            
            infoCard.classList.remove('d-none');
        }

        // Cargar tamaños de la etiqueta seleccionada
        async function cargarTamanosEtiqueta(etiquetaId) {
            const container = document.getElementById('tamanosContainer');
            const radioGroup = document.getElementById('tamanosRadioGroup');
            const infoTamano = document.getElementById('infoTamanoSeleccionado');
            
            // Limpiar y resetear
            radioGroup.innerHTML = '';
            container.classList.add('d-none');
            infoTamano.classList.add('d-none');
            tamanoSeleccionado = null;
            tamanosActuales = [];
            
            if (!etiquetaId) {
                return;
            }
            
            try {
                // Mostrar loading
                radioGroup.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin me-2"></i>Cargando tamaños...</div>';
                container.classList.remove('d-none');
                
                // Consultar los tamaños de la etiqueta seleccionada
                const formData = new FormData();
                formData.append('peticion', 'consultar_tamanos');
                formData.append('etiqueta_id', etiquetaId);
                formData.append('token', authToken);

                const response = await fetch('controllers/etiquetas.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                
                if (data.exito && data.tamanos && data.tamanos.length > 0) {
                    tamanosActuales = data.tamanos;
                    
                    // Crear los inputs radio
                    radioGroup.innerHTML = '';
                    let totalStock = 0;
                    data.tamanos.forEach((tamano, index) => {
                        const radioId = `tamano-${tamano.id || tamano.alto + '-' + tamano.ancho}`;
                        const badgeClass = obtenerClaseBadgeStock(tamano.stock_actual);
                        
                        const tamanoDiv = document.createElement('div');
                        tamanoDiv.className = 'tamano-option';
                        
                        tamanoDiv.innerHTML = `
                            <div class="form-check">
                                <input class="form-check-input" type="radio" 
                                    name="tamano_seleccionado" 
                                    id="${radioId}"
                                    value='${JSON.stringify(tamano)}'
                                    ${index === 0 ? 'checked' : ''}
                                    onchange="actualizarInfoTamano(this)">
                                <label class="form-check-label w-100" for="${radioId}">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>
                                            <strong>${tamano.alto} x ${tamano.ancho} cm</strong>
                                        </span>
                                        <span class="badge ${badgeClass} tamano-badge">
                                            ${tamano.stock_actual || 0} disponibles
                                        </span>
                                    </div>
                                </label>
                            </div>
                        `;
                        totalStock += tamano.stock_actual;
                        radioGroup.appendChild(tamanoDiv);
                        
                        // Si es el primero, actualizar información
                        if (index === 0) {
                            setTimeout(() => {
                                actualizarInfoTamano(document.getElementById(radioId));
                            }, 100);
                        }
                    });
                    document.getElementById('etiquetaStockTotal').textContent = `${totalStock} unidades`;
                } else {
                    radioGroup.innerHTML = '<div class="alert alert-warning text-center">No hay tamaños disponibles para esta etiqueta</div>';
                }
                
            } catch (error) {
                console.error('Error al cargar tamaños:', error);
                radioGroup.innerHTML = '<div class="alert alert-danger text-center">Error al cargar los tamaños</div>';
            }
        }

        // Actualizar información cuando cambia el radio del tamaño
        function actualizarInfoTamano(radioElement) {
            if (!radioElement.checked) return;
            
            try {
                tamanoSeleccionado = JSON.parse(radioElement.value);
                const infoTamano = document.getElementById('infoTamanoSeleccionado');
                const badgeClass = obtenerClaseBadgeStock(tamanoSeleccionado.stock_actual);
                
                document.getElementById('tamanoSeleccionadoTexto').textContent = 
                    `${tamanoSeleccionado.alto} x ${tamanoSeleccionado.ancho} cm`;
                
                const stockBadge = document.getElementById('stockTamanoSeleccionado');
                stockBadge.textContent = `${tamanoSeleccionado.stock_actual || 0} unidades`;
                stockBadge.className = `badge ${badgeClass}`;
                
                // Actualizar información de cantidad
                actualizarInfoCantidad();
                
                infoTamano.classList.remove('d-none');
                
            } catch (error) {
                console.error('Error actualizando información del tamaño:', error);
            }
        }

        // Actualizar información de cantidad basada en el stock disponible
        function actualizarInfoCantidad() {
            const infoCantidad = document.getElementById('infoCantidad');
            const cantidadInput = document.getElementById('cantidad');
            
            if (tamanoSeleccionado && tamanoSeleccionado.stock_actual !== undefined) {
                const stockDisponible = tamanoSeleccionado.stock_actual;
                infoCantidad.textContent = `Stock disponible: ${stockDisponible} unidades`;
                infoCantidad.className = 'form-text ' + obtenerClaseTextoStock(stockDisponible);
                
                // Si hay proyecto, considerar también la cantidad restante asignada
                const proyectoId = document.getElementById('proyectoId').value;
                if (proyectoId && cantidadAsignadaPorProyecto > 0) {
                    const cantidadRestanteProyecto = cantidadAsignadaPorProyecto - cantidadEntregadaPorProyecto;
                    const maximoPermitido = Math.min(stockDisponible, cantidadRestanteProyecto);
                    infoCantidad.textContent += ` | Máximo por proyecto: ${maximoPermitido} unidades`;
                    
                    // Validar que la cantidad no exceda el máximo permitido
                    if (parseInt(cantidadInput.value) > maximoPermitido) {
                        cantidadInput.classList.add('is-invalid');
                        cantidadInput.setAttribute('max', maximoPermitido);
                    } else {
                        cantidadInput.classList.remove('is-invalid');
                        cantidadInput.setAttribute('max', maximoPermitido);
                    }
                } else {
                    // Validar que la cantidad no exceda el stock
                    if (parseInt(cantidadInput.value) > stockDisponible) {
                        cantidadInput.classList.add('is-invalid');
                        cantidadInput.setAttribute('max', stockDisponible);
                    } else {
                        cantidadInput.classList.remove('is-invalid');
                        cantidadInput.setAttribute('max', stockDisponible);
                    }
                }
            } else {
                infoCantidad.textContent = '';
                cantidadInput.removeAttribute('max');
            }
        }

        // Funciones para captura de foto
        async function startCamera() {
            try {
                stopCamera(); // Detener cualquier cámara activa
                
                const video = document.getElementById('videoPreview');
                const placeholder = document.getElementById('photoPlaceholder');
                const controls = document.getElementById('photoControls');
                const startBtn = document.getElementById('startCameraBtn');
                const stopBtn = document.getElementById('stopCameraBtn');
                
                // Solicitar acceso a la cámara
                mediaStream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: 'environment', // Usar cámara trasera en dispositivos móviles
                        width: { ideal: 1280 },
                        height: { ideal: 720 }
                    },
                    audio: false
                });
                
                video.srcObject = mediaStream;
                video.classList.remove('d-none');
                placeholder.classList.add('d-none');
                controls.classList.remove('d-none');
                startBtn.style.display = 'none';
                stopBtn.style.display = 'block';
                
                await video.play();
            } catch (error) {
                console.error('Error al acceder a la cámara:', error);
                mostrarMensaje('error', 'No se pudo acceder a la cámara. Asegúrate de conceder los permisos necesarios.');
            }
        }

        function stopCamera() {
            const video = document.getElementById('videoPreview');
            const placeholder = document.getElementById('photoPlaceholder');
            const controls = document.getElementById('photoControls');
            const startBtn = document.getElementById('startCameraBtn');
            const stopBtn = document.getElementById('stopCameraBtn');
            
            if (mediaStream) {
                mediaStream.getTracks().forEach(track => track.stop());
                mediaStream = null;
            }
            
            video.srcObject = null;
            video.classList.add('d-none');
            placeholder.classList.remove('d-none');
            controls.classList.add('d-none');
            startBtn.style.display = 'block';
            stopBtn.style.display = 'none';
        }

        function capturePhoto() {
            const video = document.getElementById('videoPreview');
            const canvas = document.createElement('canvas');
            const photoPreview = document.getElementById('photoPreview');
            
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            
            const context = canvas.getContext('2d');
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            // Obtener la imagen en base64
            fotoCapturada = canvas.toDataURL('image/jpeg', 0.8); // Comprimir al 80% de calidad
            
            // Mostrar vista previa
            photoPreview.src = fotoCapturada;
            photoPreview.classList.remove('d-none');
            video.classList.add('d-none');
            
            // Ocultar controles de cámara
            document.getElementById('photoControls').classList.add('d-none');
            
            // Detener la cámara
            stopCamera();
            
            // Comprimir y preparar la imagen
            comprimirYPrepararFoto(fotoCapturada);
        }

        function retakePhoto() {
            const photoPreview = document.getElementById('photoPreview');
            photoPreview.classList.add('d-none');
            fotoCapturada = null;
            document.getElementById('fotoBase64').value = '';
            
            // Reiniciar la cámara
            startCamera();
        }

        function uploadFile() {
            document.getElementById('photoFile').click();
        }

        function handleFileSelect(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            if (!file.type.match('image.*')) {
                mostrarMensaje('error', 'Por favor selecciona un archivo de imagen');
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                fotoCapturada = e.target.result;
                const photoPreview = document.getElementById('photoPreview');
                photoPreview.src = fotoCapturada;
                photoPreview.classList.remove('d-none');
                document.getElementById('photoPlaceholder').classList.add('d-none');
                
                // Comprimir y preparar la imagen
                comprimirYPrepararFoto(fotoCapturada);
            };
            reader.readAsDataURL(file);
        }

        // Comprimir y preparar la foto para subir
        async function comprimirYPrepararFoto(base64Image) {
            try {
                const progressContainer = document.getElementById('progressContainer');
                const progressBar = document.getElementById('uploadProgress');
                const progressText = document.getElementById('progressText');
                
                progressContainer.style.display = 'block';
                progressBar.style.width = '30%';
                progressText.textContent = 'Comprimiendo imagen...';
                
                // Crear una imagen para comprimir
                const img = new Image();
                img.src = base64Image;
                
                img.onload = function() {
                    // Calcular dimensiones reducidas (máximo 1024px en el lado más grande)
                    const maxSize = 1024;
                    let width = img.width;
                    let height = img.height;
                    
                    if (width > height && width > maxSize) {
                        height = Math.round(height * maxSize / width);
                        width = maxSize;
                    } else if (height > maxSize) {
                        width = Math.round(width * maxSize / height);
                        height = maxSize;
                    }
                    
                    // Crear canvas con dimensiones reducidas
                    const canvas = document.createElement('canvas');
                    canvas.width = width;
                    canvas.height = height;
                    
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, width, height);
                    
                    progressBar.style.width = '60%';
                    progressText.textContent = 'Optimizando calidad...';
                    
                    // Convertir a base64 con compresión adicional
                    setTimeout(() => {
                        const compressedBase64 = canvas.toDataURL('image/jpeg', 0.7); // 70% de calidad
                        
                        progressBar.style.width = '90%';
                        progressText.textContent = 'Preparando para subir...';
                        
                        // Reducir tamaño eliminando el prefijo base64
                        const base64Data = compressedBase64.replace(/^data:image\/jpeg;base64,/, '');
                        
                        // Almacenar en el campo oculto
                        document.getElementById('fotoBase64').value = base64Data;
                        
                        setTimeout(() => {
                            progressBar.style.width = '100%';
                            progressText.textContent = '¡Imagen lista!';
                            
                            setTimeout(() => {
                                progressContainer.style.display = 'none';
                                progressBar.style.width = '0%';
                            }, 1000);
                            
                            mostrarMensaje('success', 'Foto comprimida y lista para subir');
                        }, 500);
                    }, 500);
                };
                
            } catch (error) {
                console.error('Error comprimiendo imagen:', error);
                mostrarMensaje('error', 'Error al procesar la imagen');
                document.getElementById('progressContainer').style.display = 'none';
            }
        }

        // Obtener clase CSS para el badge de stock
        function obtenerClaseBadgeStock(stock) {
            if (stock === 0) return 'bg-danger';
            if (stock <= 10) return 'bg-warning';
            return 'bg-success';
        }

        // Obtener clase CSS para el texto de stock
        function obtenerClaseTextoStock(stock) {
            if (stock === 0) return 'text-danger';
            if (stock <= 10) return 'text-warning';
            return 'text-success';
        }

        // Obtener el tamaño seleccionado
        function obtenerTamanoSeleccionado() {
            return tamanoSeleccionado;
        }

        // Limpiar formulario
        function limpiarFormulario() {
            document.getElementById('formSalida').reset();
            document.getElementById('infoProyecto').classList.add('d-none');
            document.getElementById('infoEtiqueta').classList.add('d-none');
            document.getElementById('tamanosContainer').classList.add('d-none');
            document.getElementById('infoTamanoSeleccionado').classList.add('d-none');
            document.getElementById('img_etiqueta').classList.add('d-none');
            document.getElementById('sinImagen').classList.remove('d-none');
            document.getElementById('infoCantidad').textContent = '';
            document.getElementById('cantidadInfoCard').classList.add('d-none');
            document.getElementById('photoPreview').classList.add('d-none');
            document.getElementById('photoPlaceholder').classList.remove('d-none');
            document.getElementById('fotoBase64').value = '';
            fotoCapturada = null;
            tamanoSeleccionado = null;
            tamanosActuales = [];
            cantidadAsignadaPorProyecto = 0;
            cantidadEntregadaPorProyecto = 0;
            
            // Detener la cámara si está activa
            stopCamera();
        }

        // Limpiar solo el proyecto
        function limpiarProyecto() {
            document.getElementById('proyectoId').value = '';
            document.getElementById('infoProyecto').classList.add('d-none');
            document.getElementById('cantidadInfoCard').classList.add('d-none');
            cargarTodasLasEtiquetas();
        }

        // Registrar salida
        async function registrarSalida(event) {
            event.preventDefault();
            
            try {
                const proyectoId = document.getElementById('proyectoId').value;
                const etiquetaId = document.getElementById('etiquetaId').value;
                const cantidad = document.getElementById('cantidad').value;
                const motivo = document.getElementById('motivo').value;
                const observaciones = document.getElementById('observaciones').value;
                const referencia = document.getElementById('referencia').value;
                const usuario_id = userData.id;
                const fotoBase64 = document.getElementById('fotoBase64').value;
                
                const tamano = obtenerTamanoSeleccionado();
                if (!tamano) {
                    mostrarMensaje('error', 'Por favor seleccione un tamaño');
                    return;
                }

                const alto = tamano.alto;
                const ancho = tamano.ancho;
                const tamano_id = tamano.id;

                // Validaciones
                if (!etiquetaId || !cantidad || !motivo) {
                    mostrarMensaje('error', 'Por favor complete todos los campos requeridos');
                    return;
                }

                // Validar que la cantidad no exceda el stock disponible
                if (parseInt(cantidad) > tamano.stock_actual) {
                    mostrarMensaje('error', `La cantidad excede el stock disponible (${tamano.stock_actual} unidades)`);
                    return;
                }

                // Si hay proyecto seleccionado, validar cantidad restante
                if (proyectoId && cantidadAsignadaPorProyecto > 0) {
                    const cantidadRestante = cantidadAsignadaPorProyecto - cantidadEntregadaPorProyecto;
                    if (parseInt(cantidad) > cantidadRestante) {
                        mostrarMensaje('error', `La cantidad excede la asignada al proyecto (${cantidadRestante} unidades restantes)`);
                        return;
                    }
                }

                const formData = new FormData();
                formData.append('peticion', 'registrar_salida');
                formData.append('token', authToken);
                formData.append('proyecto_id', proyectoId || '');
                formData.append('etiqueta_id', etiquetaId);
                formData.append('cantidad', cantidad);
                formData.append('motivo', motivo);
                formData.append('referencia', referencia);
                formData.append('observaciones', observaciones);
                formData.append('usuario_id', usuario_id);
                formData.append('alto', alto);
                formData.append('ancho', ancho);
                formData.append('tamano_id', tamano_id);
                formData.append('foto_base64', fotoBase64);

                const response = await fetch('controllers/movimientos.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.exito) {
                    await mostrarMensaje('success', 'Salida registrada exitosamente');
                    
                    // Actualizar cantidad entregada localmente
                    if (proyectoId) {
                        cantidadEntregadaPorProyecto += parseInt(cantidad);
                        mostrarInfoCantidades();
                    }
                    
                    limpiarFormulario();
                    
                    // Recargar etiquetas para actualizar stock
                    if (proyectoId) {
                        await cargarEtiquetasPorProyecto(proyectoId);
                    } else {
                        await cargarTodasLasEtiquetas();
                    }
                } else {
                    throw new Error(result.msj);
                }
            } catch (error) {
                console.error('Error registrando salida:', error);
                mostrarMensaje('error', error.message || 'Error al registrar la salida');
            }
        }

        function mostrarMensaje(tipo, mensaje) {
            return Swal.fire({
                icon: tipo,
                title: mensaje,
                timer: 3000,
                showConfirmButton: false
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
            
            // Cargar datos iniciales
            await cargarProyectos();
            await cargarTodasLasEtiquetas();
            
            // Configurar event listeners
            document.getElementById('logoutBtn').addEventListener('click', logout);
            document.getElementById('dropdownLogoutBtn').addEventListener('click', logout);
            document.getElementById('formSalida').addEventListener('submit', registrarSalida);
            
            // Proyecto seleccionado
            document.getElementById('proyectoId').addEventListener('change', function() {
                mostrarInfoProyecto(this.value);
            });
            
            // Etiqueta seleccionada
            document.getElementById('etiquetaId').addEventListener('change', function() {
                const select = this;
                const option = select.options[select.selectedIndex]; // opción seleccionada

                // Ejemplo: leer data-* del option
                const entregada = option.dataset.cantidad_entregada;
                const asignada = option.dataset.cantidad_asignada;
                

                mostrarInfoEtiqueta(this.value, entregada, asignada);
            });
            
            // Actualizar información de cantidad cuando cambia
            document.getElementById('cantidad').addEventListener('input', actualizarInfoCantidad);
            
            // Detener cámara al salir de la página
            window.addEventListener('beforeunload', stopCamera);
            
            setInterval(updateCurrentTime, 60000);
        });

        // Funciones auxiliares
        function updateUserInfo() {
            if (userData && document.getElementById('dropdownUserName')) {
                document.getElementById('dropdownUserName').textContent = userData.nombre || 'Usuario';
            }
        }

        function updateCurrentTime() {
            const now = new Date();
            if (document.getElementById('currentTime')) {
                document.getElementById('currentTime').textContent = now.toLocaleString('es-ES');
            }
        }

        function logout() {
            stopCamera();
            localStorage.removeItem('auth_token');
            localStorage.removeItem('user');
            window.location.href = 'cerrar_sesion.php';
        }
    </script>
</body>
</html>