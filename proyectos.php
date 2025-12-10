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
    <title>Proyectos - Sistema Inventarios</title>
    
    <!-- MDBootstrap CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700&display=swap" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/9.2.0/mdb.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Estilos generales */
        body {
            overflow-x: hidden;
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        

        
        
        
        /* User avatar */
        .user-avatar {
            width: 50px;
            height: 50px;
            border: 3px solid rgba(255,255,255,0.3);
        }
        

        
        /* Top navbar */
        .top-navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 999;
        }
        
        /* Ajustar padding para el botón toggle en móviles */
        @media (max-width: 991.98px) {
            .top-navbar .navbar-brand {
                margin-left: 50px;
            }
        }
        
        /* Estilos específicos para el contenido */
        .proyecto-card {
            transition: transform 0.3s;
            border: none;
            border-radius: 15px;
        }
        .proyecto-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .proyecto-activo {
            border-left: 4px solid #28a745;
        }
        .proyecto-inactivo {
            border-left: 4px solid #6c757d;
        }
        .etiqueta-badge {
            font-size: 0.75em;
            margin: 2px;
        }
        .tamano-info {
            font-size: 0.7em;
            color: #6c757d;
        }
        .cantidad-badge {
            font-size: 0.65em;
            background-color: #e9ecef;
            color: #495057;
        }
        
        /* Estilos para el canvas de firma */
        .signature-container {
            position: relative;
            width: 100%;
            height: 400px;
            border: 2px solid #ddd;
            border-radius: 5px;
            background-color: white;
            touch-action: none;
            cursor: crosshair;
        }
        #signatureCanvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        .signature-preview {
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 5px;
            background-color: #f8f9fa;
            margin: 10px 0;
        }
        .signature-actions {
            margin-top: 10px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .signature-instructions {
            font-size: 0.9em;
            color: #6c757d;
            margin-bottom: 10px;
        }
        .firma-guardada {
            max-width: 300px;
            max-height: 150px;
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 5px;
        }
        .cursor-pen {
            position: absolute;
            width: 4px;
            height: 4px;
            background-color: red;
            border-radius: 50%;
            pointer-events: none;
            z-index: 10;
            display: none;
        }
    </style>
</head>
<body>


    <?php include("menu.php"); ?>

    <!-- CONTENIDO PRINCIPAL -->
    <div class="main-content">
        <!-- Barra Superior -->
        <nav class="top-navbar">
            <div class="container-fluid py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <button class="sidebar-toggle-btn" id="customSidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <span class="navbar-brand fw-bold text-primary">
                        <i class="fas fa-project-diagram me-2"></i>Gestión de Proyectos
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
                                    <label class="form-label">Buscar proyecto</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" placeholder="Código, nombre, descripción..." id="searchInput">
                                        <button class="btn btn-primary" type="button" id="searchBtn">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Estado</label>
                                    <select class="form-select" id="estadoFilter">
                                        <option value="">Todos los estados</option>
                                        <option value="activo">Activos</option>
                                        <option value="inactivo">Inactivos</option>
                                        <option value="completado">Completados</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Fecha Inicio</label>
                                    <input type="date" class="form-control" id="fechaInicioFilter">
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
                            <h5 class="card-title mb-1" id="totalProyectos">0</h5>
                            <small>Total Proyectos</small>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-6 mb-3">
                    <div class="card bg-success text-white text-center">
                        <div class="card-body py-3">
                            <h5 class="card-title mb-1" id="proyectosActivos">0</h5>
                            <small>Activos</small>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-6 mb-3">
                    <div class="card bg-warning text-white text-center">
                        <div class="card-body py-3">
                            <h5 class="card-title mb-1" id="proyectosCompletados">0</h5>
                            <small>Completados</small>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-6 mb-3">
                    <div class="card bg-info text-white text-center">
                        <div class="card-body py-3">
                            <h5 class="card-title mb-1" id="totalEtiquetas">0</h5>
                            <small>Etiquetas Asignadas</small>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-6 mb-3">
                    <div class="card bg-secondary text-white text-center">
                        <div class="card-body py-3">
                            <h5 class="card-title mb-1" id="totalUnidades">0</h5>
                            <small>Total Unidades</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista de Proyectos -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-list me-2"></i>Lista de Proyectos
                            </h5>
                            <a href="nuevo-proyecto.php" class="btn btn-success btn-sm">
                                <i class="fas fa-plus me-1"></i>Nuevo Proyecto
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover" id="tablaProyectos">
                                    <thead>
                                        <tr>
                                            <th>Código</th>
                                            <th>Nombre</th>
                                            <th>Etiquetas</th>
                                            <th>Estado</th>
                                            <th>Fecha Inicio</th>
                                            <th>Total Unidades</th>
                                            <th>Unid. Entregadas</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="proyectosBody">
                                        <!-- Los proyectos se cargarán aquí -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Ver Detalles del Proyecto -->
    <div class="modal fade" id="detalleProyectoModal" tabindex="-1" aria-labelledby="detalleProyectoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="detalleProyectoModalLabel">Detalles del Proyecto</h5>
                    <button type="button" class="btn-close btn-close-white" data-mdb-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="detalleProyectoBody">
                    <!-- Los detalles se cargarán aquí -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Firma Digital -->
    <div class="modal fade" id="modalFirma" tabindex="-1" aria-labelledby="modalFirmaLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalFirmaLabel">Firmar Finalización de Proyecto</h5>
                    <button type="button" class="btn-close btn-close-white" data-mdb-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Instrucciones:</strong> Por favor, dibuje su firma en el área de abajo. 
                                Asegúrese de que la firma sea clara y legible.
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12">
                            <label class="form-label">Firma del Responsable</label>
                            <div class="signature-instructions">
                                <i class="fas fa-mouse-pointer me-1"></i>Haga clic y arrastre para dibujar su firma
                            </div>
                            
                            <!-- Contenedor para la firma con cursor personalizado -->
                            <div class="signature-container" id="signatureContainer">
                                <canvas id="signatureCanvas"></canvas>
                                <div id="cursorPen" class="cursor-pen"></div>
                            </div>
                            
                            <!-- Acciones para la firma -->
                            <div class="signature-actions mt-3">
                                <button type="button" id="btnClearSignature" class="btn btn-outline-danger">
                                    <i class="fas fa-eraser me-1"></i>Limpiar Firma
                                </button>
                                <button type="button" id="btnUndoSignature" class="btn btn-outline-warning">
                                    <i class="fas fa-undo me-1"></i>Deshacer
                                </button>
                                <button type="button" id="btnRedoSignature" class="btn btn-outline-info">
                                    <i class="fas fa-redo me-1"></i>Rehacer
                                </button>
                            </div>
                            
                            <!-- Vista previa de la firma -->
                            <div class="mt-4" id="signaturePreview" style="display: none;">
                                <label class="form-label">Vista previa de la firma:</label>
                                <div class="signature-preview text-center">
                                    <img id="previewImage" class="img-fluid" style="max-height: 100px;">
                                </div>
                            </div>
                            
                            <!-- Información del proyecto -->
                            <div class="mt-4 border-top pt-3">
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Importante:</strong> Al confirmar la firma:
                                    <ul class="mb-0 mt-1">
                                        <li>El proyecto cambiará a estado "Finalizado" (3)</li>
                                        <li>La firma se guardará como evidencia</li>
                                        <li>Esta acción no se puede deshacer</li>
                                    </ul>
                                </div>
                                
                                <div class="form-group mb-3">
                                    <label for="firmaNombre" class="form-label">Nombre del Firmante</label>
                                    <input type="text" class="form-control" id="firmaNombre" 
                                           placeholder="Ingrese su nombre completo" required>
                                    <div class="form-text">Este nombre se asociará a la firma digital</div>
                                </div>
                                
                                <div class="form-group mb-3">
                                    <label for="firmaComentarios" class="form-label">Comentarios (opcional)</label>
                                    <textarea class="form-control" id="firmaComentarios" 
                                              rows="3" placeholder="Agregue comentarios sobre la finalización del proyecto..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Cancelar</button>
                    <button type="button" id="btnConfirmarFirma" class="btn btn-success">
                        <i class="fas fa-check-circle me-2"></i>Confirmar y Finalizar Proyecto
                    </button>
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
        let proyectos = [];
        let signaturePad = null;
        let historialFirmas = [];
        let historialIndex = -1;
        let proyectoAFinalizar = null;
        let isDrawing = false;
        let lastX = 0;
        let lastY = 0;
        let canvas;
        let ctx;
        
        

        // Implementación personalizada de firma
        function inicializarSignaturePad() {
            const container = document.getElementById('signatureContainer');
            canvas = document.getElementById('signatureCanvas');
            
            // Asegúrate de que el contenedor esté visible y tenga dimensiones
            if (container.offsetWidth === 0 || container.offsetHeight === 0) {
                // Si no tiene dimensiones, establecer un tamaño mínimo
                container.style.width = '100%';
                container.style.height = '300px';
            }
            console.log(container.offsetHeight);
            
            // Ahora configurar el canvas
            canvas.width = container.offsetWidth;
            canvas.height = 400;
            
            ctx = canvas.getContext('2d');
            
            // Configurar estilo del dibujo
            ctx.strokeStyle = '#000000';
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            
            // Variables para el historial
            historialFirmas = [];
            historialIndex = -1;
            
            // Limpiar canvas
            clearCanvas();
            guardarEstado();
            
            // Eventos del mouse
            canvas.addEventListener('mousedown', startDrawing);
            canvas.addEventListener('mousemove', draw);
            canvas.addEventListener('mouseup', stopDrawing);
            canvas.addEventListener('mouseout', stopDrawing);
            
            // Eventos táctiles para dispositivos móviles
            canvas.addEventListener('touchstart', handleTouchStart);
            canvas.addEventListener('touchmove', handleTouchMove);
            canvas.addEventListener('touchend', stopDrawing);
            
            // Evento para mostrar cursor personalizado
            if (document.getElementById('cursorPen')) {
                container.addEventListener('mousemove', (e) => {
                    const rect = container.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;
                    
                    cursorPen.style.left = (x - 2) + 'px';
                    cursorPen.style.top = (y - 2) + 'px';
                    
                    if (isDrawing) {
                        cursorPen.style.display = 'block';
                        cursorPen.style.backgroundColor = '#000000';
                    } else {
                        cursorPen.style.display = 'block';
                        cursorPen.style.backgroundColor = 'rgba(0, 0, 0, 0.3)';
                    }
                });
                
                container.addEventListener('mouseenter', () => {
                    cursorPen.style.display = 'block';
                });
                
                container.addEventListener('mouseleave', () => {
                    cursorPen.style.display = 'none';
                });
            }
            
            // Configurar botones
            document.getElementById('btnClearSignature').addEventListener('click', () => {
                clearCanvas();
                guardarEstado();
                document.getElementById('signaturePreview').style.display = 'none';
            });
            
            document.getElementById('btnUndoSignature').addEventListener('click', undo);
            document.getElementById('btnRedoSignature').addEventListener('click', redo);
        }
        
        function startDrawing(e) {
            isDrawing = true;
            const pos = getMousePos(canvas, e);
            [lastX, lastY] = [pos.x, pos.y];
            e.preventDefault();
        }
        
        function draw(e) {
            if (!isDrawing) return;
            
            const pos = getMousePos(canvas, e);
            const currentX = pos.x;
            const currentY = pos.y;
            
            // Dibujar línea
            ctx.beginPath();
            ctx.moveTo(lastX, lastY);
            ctx.lineTo(currentX, currentY);
            ctx.stroke();
            
            [lastX, lastY] = [currentX, currentY];
            
            // Actualizar vista previa
            updatePreview();
            e.preventDefault();
        }
        
        function stopDrawing() {
            if (isDrawing) {
                isDrawing = false;
                guardarEstado();
            }
        }
        
        function handleTouchStart(e) {
            if (e.touches.length === 1) {
                const touch = e.touches[0];
                const mouseEvent = new MouseEvent('mousedown', {
                    clientX: touch.clientX,
                    clientY: touch.clientY
                });
                canvas.dispatchEvent(mouseEvent);
            }
            e.preventDefault();
        }
        
        function handleTouchMove(e) {
            if (e.touches.length === 1) {
                const touch = e.touches[0];
                const mouseEvent = new MouseEvent('mousemove', {
                    clientX: touch.clientX,
                    clientY: touch.clientY
                });
                canvas.dispatchEvent(mouseEvent);
            }
            e.preventDefault();
        }
        
        function getMousePos(canvas, evt) {
            const rect = canvas.getBoundingClientRect();
            let clientX, clientY;
            
            if (evt.type.includes('touch')) {
                clientX = evt.touches[0].clientX;
                clientY = evt.touches[0].clientY;
            } else {
                clientX = evt.clientX;
                clientY = evt.clientY;
            }
            
            return {
                x: clientX - rect.left,
                y: clientY - rect.top
            };
        }
        
        function clearCanvas() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
        }
        
        function guardarEstado() {
            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            
            // Si hay cambios, guardar nuevo estado
            if (historialIndex !== historialFirmas.length - 1) {
                historialFirmas = historialFirmas.slice(0, historialIndex + 1);
            }
            
            historialFirmas.push(imageData);
            historialIndex++;
            
            // Limitar el historial a 50 estados para no consumir mucha memoria
            if (historialFirmas.length > 50) {
                historialFirmas.shift();
                historialIndex--;
            }
        }
        
        function undo() {
            if (historialIndex > 0) {
                historialIndex--;
                const imageData = historialFirmas[historialIndex];
                ctx.putImageData(imageData, 0, 0);
                updatePreview();
            }
        }
        
        function redo() {
            if (historialIndex < historialFirmas.length - 1) {
                historialIndex++;
                const imageData = historialFirmas[historialIndex];
                ctx.putImageData(imageData, 0, 0);
                updatePreview();
            }
        }
        
        function updatePreview() {
            const dataURL = canvas.toDataURL();
            document.getElementById('previewImage').src = dataURL;
            document.getElementById('signaturePreview').style.display = 'block';
        }
        
        function getSignatureDataURL() {
            return canvas.toDataURL('image/png');
        }
        
        function isEmptyCanvas() {
            const blankCanvas = document.createElement('canvas');
            blankCanvas.width = canvas.width;
            blankCanvas.height = canvas.height;
            const blankCtx = blankCanvas.getContext('2d');
            blankCtx.fillStyle = '#ffffff';
            blankCtx.fillRect(0, 0, blankCanvas.width, blankCanvas.height);
            
            const currentImageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const blankImageData = blankCtx.getImageData(0, 0, blankCanvas.width, blankCanvas.height);
            
            // Comparar si los canvas son iguales
            for (let i = 0; i < currentImageData.data.length; i++) {
                if (currentImageData.data[i] !== blankImageData.data[i]) {
                    return false;
                }
            }
            return true;
        }

        // Cargar proyectos desde la API
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
                    proyectos = result.data;
                    mostrarProyectos();
                    actualizarEstadisticas();
                } else {
                    throw new Error(result.msj);
                }
            } catch (error) {
                console.error('Error cargando proyectos:', error);
                mostrarMensaje('error', 'Error al cargar los proyectos');
            }
        }

        // Mostrar proyectos en la tabla
        function mostrarProyectos() {
            const tbody = document.getElementById('proyectosBody');
            tbody.innerHTML = '';

            if (proyectos.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay proyectos registrados</h5>
                            <p class="text-muted">Crea tu primer proyecto para comenzar</p>
                            <a href="nuevo-proyecto.php" class="btn btn-success">
                                <i class="fas fa-plus me-1"></i>Crear Primer Proyecto
                            </a>
                        </td>
                    </tr>
                `;
                return;
            }

            proyectos.forEach(proyecto => {
                const totalUnidades = proyecto.etiquetas?.reduce((sum, e) => sum + (e.cantidad || 1), 0) || 0;
                const totalUnidadesEntregadas = proyecto.etiquetas?.reduce((sum, e) => sum + (e.cantidad_entregada || 1), 0) || 0;
                
                const tr = document.createElement('tr');
                tr.className = proyecto.estado == 1 ? 'proyecto-activo' : proyecto.estado == 3 ? 'proyecto-finalizado' : 'proyecto-inactivo';
                
                tr.innerHTML = `
                    <td><strong class="text-primary">${proyecto.codigo}</strong></td>
                    <td><strong>${proyecto.nombre}</strong></td>
                    <td><span class="badge bg-info">${proyecto.etiquetas?.length || 0} etiquetas</span></td>
                    <td>${proyecto.estado == 1 ? '<span class="badge bg-success">Activo</span>' : proyecto.estado == 3 ? '<span class="badge bg-warning">Completado</span>' : '<span class="badge bg-secondary">Inactivo</span>'}</td>
                    <td><small>${proyecto.fecha_inicio ? new Date(proyecto.fecha_inicio).toLocaleDateString('es-ES') : 'No especificada'}</small></td>
                    <td><span class="badge bg-primary">${totalUnidades} unid.</span></td>
                    <td><span class="badge bg-success">${totalUnidadesEntregadas} unid.</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="verDetalleProyecto(${proyecto.id})"><i class="fas fa-eye"></i></button>
                        <a href="./editar-proyecto.php?id=${proyecto.id}" class="btn btn-sm btn-outline-success"><i class="fas fa-edit"></i></a>
                        <?php if($_SESSION['usuario']['rol']=='admin'): ?>
                            <button class="btn btn-sm btn-outline-danger" onclick="eliminarProyecto(${proyecto.id})"><i class="fas fa-trash"></i></button>
                        <?php endif;?>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        // Ver detalles del proyecto
        async function verDetalleProyecto(id) {
            try {
                const formData = new FormData();
                formData.append('peticion', 'obtener');
                formData.append('token', authToken);
                formData.append('id', id);

                const response = await fetch('controllers/proyectos.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.exito) {
                    const proyecto = result.proyecto[0];
                    const etiquetas = result.etiquetas || [];
                    const firmas = result.firmas || [];
                    const modalBody = document.getElementById('detalleProyectoBody');
                    
                    let etiquetasHTML = '<p class="text-muted">No hay etiquetas asignadas</p>';
                    let totalUnidades = 0;

                    if (etiquetas.length > 0) {
                        etiquetasHTML = `
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Etiqueta</th>
                                            <th>Tamaño</th>
                                            <th>Cantidad</th>
                                            <th>Cantidad Entregada</th>
                                            <th>Stock Disponible</th>
                                            <th>Estado Stock</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${etiquetas.map(etiqueta => {
                                            const cantidad = etiqueta.cantidad || 0;
                                            const entregada = etiqueta.cantidad_entregada || 0;
                                            totalUnidades += cantidad;
                                            const stockDisponible = etiqueta.stock_actual || 0;
                                            const estadoStock = cantidad <= stockDisponible ? 'success' : 'danger';
                                            const textoEstado = cantidad <= stockDisponible ? 'Suficiente' : 'Insuficiente';
                                            
                                            return `
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            ${etiqueta.foto_url ? 
                                                                `<img src="uploads/${etiqueta.foto_url}" class="rounded me-2" style="width: auto; height: 30px; object-fit: cover;">` : 
                                                                '<i class="fas fa-tag text-muted me-2"></i>'
                                                            }
                                                            <div>
                                                                <strong>${etiqueta.etiqueta_nombre}</strong>
                                                                <br>
                                                                <small class="text-muted">${etiqueta.categoria_nombre || 'Sin categoría'}</small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <strong>${etiqueta.alto} x ${etiqueta.ancho} cm</strong>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-primary">${cantidad} unid.</span>
                                                    </td>
                                                    <td>
                                                        <span class="badge ${cantidad > entregada ? 'bg-warning' : 'bg-success'}">${entregada} unid.</span>
                                                    </td>
                                                    <td>
                                                        <span class="badge ${stockDisponible > 0 ? 'bg-success' : 'bg-danger'}">
                                                            ${stockDisponible} unid.
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-${estadoStock}">${textoEstado}</span>
                                                    </td>
                                                </tr>
                                            `;
                                        }).join('')}
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3 p-3 bg-light rounded">
                                <strong>Resumen:</strong> ${etiquetas.length} etiquetas diferentes, ${totalUnidades} unidades totales
                            </div>
                        `;
                    }

                    // Mostrar firma si existe
                    let firmaHTML = '';
                    
                    if (firmas.length > 0) {
                        for (let i = 0; i < firmas.length; i++) {
                            firmaHTML += `<hr>
                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <h6 class="fw-bold text-primary">Firma de Finalización</h6>
                                    <div class="firma-guardada">
                                        <img src="uploads/${firmas[i].firma}" alt="Firma del proyecto" class="img-fluid">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <small class="text-muted mt-5 d-block">
                                        <i class="fas fa-calendar me-1"></i>Firmado el: ${firmas[i].fecha ? new Date(firmas[i].fecha).toLocaleDateString('es-ES') : 'N/A'}
                                    </small>
                                    <small class="text-muted">
                                        <i class="fas fa-user me-1"></i>Por: ${firmas[i].nombre || 'N/A'}
                                    </small>
                                    <small class="text-muted d-block">
                                        <i class="fas fa-message me-1"></i>Comentarios: ${firmas[i].comentarios || 'N/A'}
                                    </small>
                                </div>
                            </div>
                        `;
                            
                        }
                    }

                    modalBody.innerHTML = `
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="fw-bold text-primary">Información General</h6>
                                <p><strong>Código:</strong> ${proyecto.codigo}</p>
                                <p><strong>Nombre:</strong> ${proyecto.nombre}</p>
                                <p><strong>Descripción:</strong> ${proyecto.descripcion || 'N/A'}</p>
                                <p><strong>Estado:</strong> 
                                    <span class="badge ${proyecto.estado === 1 ? 'bg-success' : proyecto.estado === 3 ? 'bg-warning' : 'bg-secondary'}">
                                        ${proyecto.estado_nombre || 'Desconocido'}
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-bold text-primary">Fechas</h6>
                                <p><strong>Fecha Inicio:</strong> ${proyecto.fecha_inicio ? new Date(proyecto.fecha_inicio).toLocaleDateString('es-ES') : 'No especificada'}</p>
                                <p><strong>Creado:</strong> ${new Date(proyecto.fecha_create).toLocaleDateString('es-ES')}</p>
                                <p><strong>Por:</strong> ${proyecto.usuario_nombre || 'N/A'}</p>
                            </div>
                        </div>
                        <div class="row mt-4">
                            <div class="col-12">
                                <h6 class="fw-bold text-primary">Etiquetas y Tamaños Asociados</h6>
                                ${etiquetasHTML}
                            </div>
                        </div>
                        ${firmaHTML}
                        ${proyecto.estado == 1 ? `
                        <div class="row text-center mt-4">
                            <div class="col-12">
                                <button class="btn btn-primary" onclick="abrirModalFirma(${proyecto.id})">
                                    <i class="fas fa-signature me-2"></i>Finalizar proyecto con firma
                                </button>
                            </div>
                        </div>` : ''}
                    `;
                    
                    const modal = new mdb.Modal(document.getElementById('detalleProyectoModal'));
                    modal.show();
                }
            } catch (error) {
                console.error('Error cargando detalles:', error);
                mostrarMensaje('error', 'Error al cargar los detalles del proyecto');
            }
        }

        // Abrir modal de firma
        function abrirModalFirma(proyectoId) {
            proyectoAFinalizar = proyectoId;
            
            // Cerrar modal de detalles
            const modalDetalles = mdb.Modal.getInstance(document.getElementById('detalleProyectoModal'));
            modalDetalles.hide();
            
            // NO inicialices el signature pad aquí
            // Solo limpia el formulario
            document.getElementById('firmaNombre').value = userData?.nombre || '';
            document.getElementById('firmaComentarios').value = '';
            document.getElementById('signaturePreview').style.display = 'none';
            
            // Abrir modal de firma
            const modalFirma = new mdb.Modal(document.getElementById('modalFirma'));
            modalFirma.show();
        }

        // Agrega este evento listener en la inicialización (DOMContentLoaded):
        document.getElementById('modalFirma').addEventListener('shown.mdb.modal', () => {
            inicializarSignaturePad();
        });

        // Confirmar firma y finalizar proyecto
        async function confirmarFirma() {
            try {
                // Validaciones
                const nombreFirmante = document.getElementById('firmaNombre').value.trim();
                if (!nombreFirmante) {
                    mostrarMensaje('error', 'Por favor ingrese su nombre');
                    return;
                }

                if (isEmptyCanvas()) {
                    mostrarMensaje('error', 'Por favor realice su firma en el área indicada');
                    return;
                }

                // Obtener la firma en base64
                const firmaBase64 = getSignatureDataURL();
                
                // Confirmación final
                const confirmResult = await Swal.fire({
                    title: '¿Confirmar finalización?',
                    html: `
                        <div class="text-center">
                            <p>¿Está seguro de finalizar el proyecto con firma?</p>
                            <div class="my-3">
                                <img src="${firmaBase64}" style="max-height: 100px; border: 1px solid #ccc;">
                            </div>
                            <p><strong>Firmante:</strong> ${nombreFirmante}</p>
                        </div>
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, finalizar con firma',
                    cancelButtonText: 'Cancelar'
                });

                if (!confirmResult.isConfirmed) return;

                // Crear FormData para enviar
                const formData = new FormData();
                formData.append('peticion', 'finalizar_con_firma');
                formData.append('token', authToken);
                formData.append('proyecto_id', proyectoAFinalizar);
                formData.append('firma', firmaBase64);
                formData.append('firmante_nombre', nombreFirmante);
                formData.append('comentarios', document.getElementById('firmaComentarios').value);

                // Mostrar carga
                Swal.fire({
                    title: 'Procesando...',
                    text: 'Guardando firma y finalizando proyecto',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Enviar al servidor
                const response = await fetch('controllers/proyectos.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                Swal.close();
                
                if (result.exito) {
                    // Cerrar modal de firma
                    const modalFirma = mdb.Modal.getInstance(document.getElementById('modalFirma'));
                    modalFirma.hide();
                    
                    // Mostrar mensaje de éxito
                    mostrarMensaje('success', 'Proyecto finalizado exitosamente con firma');
                    
                    // Recargar proyectos
                    await cargarProyectos();
                } else {
                    throw new Error(result.msj || 'Error al finalizar el proyecto');
                }
            } catch (error) {
                console.error('Error confirmando firma:', error);
                mostrarMensaje('error', error.message || 'Error al procesar la firma');
            }
        }

        // Eliminar proyecto
        async function eliminarProyecto(id) {
            const confirmResult = await Swal.fire({
                title: '¿Estás seguro?',
                text: "Esta acción eliminará el proyecto y todas sus etiquetas asociadas.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            });

            if (!confirmResult.isConfirmed) return;

            try {
                const formData = new FormData();
                formData.append('peticion', 'eliminar');
                formData.append('token', authToken);
                formData.append('id', id);

                const response = await fetch('controllers/proyectos.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.exito) {
                    mostrarMensaje('success', 'Proyecto eliminado exitosamente');
                    await cargarProyectos();
                } else {
                    throw new Error(result.msj);
                }
            } catch (error) {
                console.error('Error eliminando proyecto:', error);
                mostrarMensaje('error', 'Error al eliminar el proyecto');
            }
        }

        // Actualizar estadísticas
        function actualizarEstadisticas() {
            const total = proyectos.length;
            const activos = proyectos.filter(p => p.estado === 1).length;
            const completados = proyectos.filter(p => p.estado === 3).length;
            
            // Calcular total de etiquetas y unidades
            let totalEtiquetas = 0;
            let totalUnidades = 0;

            proyectos.forEach(proyecto => {
                if (proyecto.etiquetas && proyecto.etiquetas.length > 0) {
                    totalEtiquetas += proyecto.etiquetas.length;
                    proyecto.etiquetas.forEach(etiqueta => {
                        totalUnidades += etiqueta.cantidad || 0;
                    });
                }
            });

            document.getElementById('totalProyectos').textContent = total;
            document.getElementById('proyectosActivos').textContent = activos;
            document.getElementById('proyectosCompletados').textContent = completados;
            document.getElementById('totalEtiquetas').textContent = totalEtiquetas;
            document.getElementById('totalUnidades').textContent = totalUnidades;
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
            
            // Inicializar sidebar
            
            // Cargar datos
            await cargarProyectos();
            
            // Configurar event listeners
            document.getElementById('logoutBtn').addEventListener('click', logout);
            document.getElementById('dropdownLogoutBtn').addEventListener('click', logout);
            document.getElementById('btnConfirmarFirma').addEventListener('click', confirmarFirma);
            
            // Configurar filtros
            document.getElementById('searchBtn').addEventListener('click', filtrarProyectos);
            document.getElementById('resetFilters').addEventListener('click', resetFiltros);
            document.getElementById('searchInput').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') filtrarProyectos();
            });
            
            setInterval(updateCurrentTime, 60000);
        });

        // Filtrar proyectos
        function filtrarProyectos() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const estadoFilter = document.getElementById('estadoFilter').value;
            const fechaFilter = document.getElementById('fechaInicioFilter').value;

            const proyectosFiltrados = proyectos.filter(proyecto => {
                const matchSearch = !searchTerm || 
                    proyecto.codigo.toLowerCase().includes(searchTerm) ||
                    proyecto.nombre.toLowerCase().includes(searchTerm) ||
                    (proyecto.descripcion && proyecto.descripcion.toLowerCase().includes(searchTerm));
                
                const matchEstado = !estadoFilter || 
                    (estadoFilter === 'activo' && proyecto.estado === 1) ||
                    (estadoFilter === 'inactivo' && proyecto.estado === 2) ||
                    (estadoFilter === 'completado' && proyecto.estado === 3);
                
                const matchFecha = !fechaFilter || proyecto.fecha_inicio === fechaFilter;

                return matchSearch && matchEstado && matchFecha;
            });

            mostrarProyectosFiltrados(proyectosFiltrados);
        }

        // Mostrar proyectos filtrados
        function mostrarProyectosFiltrados(proyectosFiltrados) {
            const tbody = document.getElementById('proyectosBody');
            tbody.innerHTML = '';

            if (proyectosFiltrados.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No se encontraron proyectos</h5>
                            <p class="text-muted">Intenta con otros criterios de búsqueda</p>
                        </td>
                    </tr>
                `;
                return;
            }

            // Reutilizar la lógica de mostrarProyectos pero con la lista filtrada
            proyectos = proyectosFiltrados;
            mostrarProyectos();
        }

        // Resetear filtros
        function resetFiltros() {
            document.getElementById('searchInput').value = '';
            document.getElementById('estadoFilter').value = '';
            document.getElementById('fechaInicioFilter').value = '';
            cargarProyectos();
        }

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
            localStorage.removeItem('auth_token');
            localStorage.removeItem('user');
            window.location.href = 'cerrar_sesion.php';
        }
    </script>
</body>
</html>