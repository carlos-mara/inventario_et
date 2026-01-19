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

    <!-- Modal para Generar Reporte de Proyectos -->
    <div class="modal fade" id="modalReporteProyectos" tabindex="-1" aria-labelledby="modalReporteProyectosLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalReporteProyectosLabel">
                        <i class="fas fa-file-pdf me-2"></i>Generar Reporte de Proyectos
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-mdb-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <form id="formReporteProyectos">
                    <div class="modal-body">
                        <!-- Información del Reporte -->
                        <div class="alert alert-primary mb-4">
                            <div class="d-flex">
                                <div class="me-3">
                                    <i class="fas fa-project-diagram fa-2x"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-1">Reporte de Proyectos</h6>
                                    <p class="mb-0 small">Genera un reporte detallado de todos los proyectos con información de etiquetas asignadas y estado de entrega.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Tipo de Reporte -->
                        <div class="mb-4">
                            <h6 class="fw-bold mb-3">
                                <i class="fas fa-chart-bar me-2"></i>Tipo de Reporte
                            </h6>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="tipoReporte" id="reporteGeneral" value="general" checked>
                                        <label class="form-check-label" for="reporteGeneral">
                                            <i class="fas fa-list me-2"></i>General
                                        </label>
                                    </div>
                                    <small class="text-muted">Resumen de todos los proyectos</small>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="tipoReporte" id="reporteDetallado" value="detallado">
                                        <label class="form-check-label" for="reporteDetallado">
                                            <i class="fas fa-file-alt me-2"></i>Detallado
                                        </label>
                                    </div>
                                    <small class="text-muted">Con etiquetas y movimientos</small>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="tipoReporte" id="reporteEstadistico" value="estadistico">
                                        <label class="form-check-label" for="reporteEstadistico">
                                            <i class="fas fa-chart-pie me-2"></i>Estadístico
                                        </label>
                                    </div>
                                    <small class="text-muted">Gráficos y métricas</small>
                                </div>
                            </div>
                        </div>

                        <!-- Filtros por Estado -->
                        <div class="mb-4">
                            <h6 class="fw-bold mb-3">
                                <i class="fas fa-filter me-2"></i>Filtrar por Estado
                            </h6>
                            <div class="row">
                                <div class="col-md-3 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="filtroActivos" name="estados[]" value="1" checked>
                                        <label class="form-check-label" for="filtroActivos">
                                            <span class="badge bg-success">Activos</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="filtroInactivos" name="estados[]" value="2" checked>
                                        <label class="form-check-label" for="filtroInactivos">
                                            <span class="badge bg-secondary">Inactivos</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="filtroCompletados" name="estados[]" value="3" checked>
                                        <label class="form-check-label" for="filtroCompletados">
                                            <span class="badge bg-warning">Completados</span>
                                        </label>
                                    </div>
                                </div>
                                <!-- <div class="col-md-3 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="filtroConFirma" name="con_firma">
                                        <label class="form-check-label" for="filtroConFirma">
                                            <i class="fas fa-signature me-1"></i>Con firma
                                        </label>
                                    </div>
                                </div> -->
                            </div>
                        </div>

                        <!-- Rango de Fechas -->
                        <div class="mb-4">
                            <h6 class="fw-bold mb-3">
                                <i class="far fa-calendar-alt me-2"></i>Rango de Fechas de Creación
                            </h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="fechaDesdeProy" class="form-label">Fecha Desde</label>
                                    <input type="date" class="form-control" id="fechaDesdeProy" name="fecha_desde" 
                                        value="<?php echo date('Y-m-01'); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="fechaHastaProy" class="form-label">Fecha Hasta</label>
                                    <input type="date" class="form-control" id="fechaHastaProy" name="fecha_hasta" 
                                        value="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Filtros Adicionales -->
                        <div class="mb-4">
                            <h6 class="fw-bold mb-3">Filtros Adicionales</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <!-- <label for="filtroUsuarioProy" class="form-label">Usuario Creador</label> -->
                                    <select class="form-select" id="filtroUsuarioProy" name="usuario_id" hidden>
                                        <option value="">Todos los usuarios</option>
                                        <option value="admin">Administrador</option>
                                        <option value="proyectos">Gestor de Proyectos</option>
                                        <option value="almacen">Almacén</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <!-- <label for="filtroEtiquetaProy" class="form-label">Contiene Etiqueta</label> -->
                                    <select class="form-select" id="filtroEtiquetaProy" name="etiqueta_id" hidden>
                                        <option value="">Cualquier etiqueta</option>
                                        <option value="1">Etiquetas Adhesivas</option>
                                        <option value="2">Etiquetas Térmicas</option>
                                        <option value="3">Etiquetas RFID</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="filtroMinUnidades" class="form-label">Mínimo Unidades</label>
                                    <input type="number" class="form-control" id="filtroMinUnidades" name="min_unidades" 
                                        min="0" placeholder="Ej: 100">
                                    <div class="form-text">Proyectos con al menos esta cantidad</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="filtroPorcentajeEntrega" class="form-label">% Mínimo de Entrega</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="filtroPorcentajeEntrega" name="porcentaje_entrega" 
                                            min="0" max="100" placeholder="Ej: 50">
                                        <span class="input-group-text">%</span>
                                    </div>
                                    <div class="form-text">Proyectos con al menos este % entregado</div>
                                </div>
                            </div>
                        </div>

                        <!-- Configuración del Reporte -->
                        <div class="mb-4">
                            <h6 class="fw-bold mb-3">
                                <i class="fas fa-cog me-2"></i>Configuración del Reporte
                            </h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Formato</label>
                                    <div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="radio" name="formatoProy" id="formatoPDFProy" value="pdf" checked>
                                            <label class="form-check-label" for="formatoPDFProy">
                                                <i class="fas fa-file-pdf text-danger me-1"></i>PDF
                                            </label>
                                        </div>
                                        <!-- <div class="form-check mb-2">
                                            <input class="form-check-input" type="radio" name="formatoProy" id="formatoExcelProy" value="excel">
                                            <label class="form-check-label" for="formatoExcelProy">
                                                <i class="fas fa-file-excel text-success me-1"></i>Excel
                                            </label>
                                        </div> -->
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <!-- <label class="form-label">Opciones de Visualización</label> -->
                                    <div hidden>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="incluirFotos" name="incluir_fotos" checked>
                                            <label class="form-check-label" for="incluirFotos">
                                                Incluir fotos de etiquetas
                                            </label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="incluirFirmas" name="incluir_firmas">
                                            <label class="form-check-label" for="incluirFirmas">
                                                Incluir firmas digitales
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="mostrarPorcentajes" name="mostrar_porcentajes" checked>
                                            <label class="form-check-label" for="mostrarPorcentajes">
                                                Mostrar porcentajes de avance
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Ordenamiento -->
                        <div class="mb-4">
                            <h6 class="fw-bold mb-3">Orden del Reporte</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="ordenPorProy" class="form-label">Ordenar por</label>
                                    <select class="form-select" id="ordenPorProy" name="orden_por">
                                        <option value="codigo">Código</option>
                                        <option value="nombre">Nombre</option>
                                        <option value="fecha_inicio" selected>Fecha de Inicio</option>
                                        <option value="estado">Estado</option>
                                        <option value="total_unidades">Total de Unidades</option>
                                        <option value="porcentaje_entrega">% de Entrega</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="ordenDireccionProy" class="form-label">Dirección</label>
                                    <select class="form-select" id="ordenDireccionProy" name="orden_direccion">
                                        <option value="asc">Ascendente (A-Z)</option>
                                        <option value="desc" selected>Descendente (Z-A)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Resumen del Reporte -->
                        <div class="alert alert-info">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <i class="fas fa-chart-line fa-2x"></i>
                                </div>
                                <div>
                                    <strong>Resumen del reporte:</strong>
                                    <div id="resumenReporteProy" class="mt-1">
                                        Reporte general de proyectos en formato PDF
                                    </div>
                                    <div class="mt-1 small text-muted" id="detalleReporteProy">
                                        Incluyendo todos los estados y sin filtros de fecha
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </button>
                        <button type="button" class="btn btn-outline-primary" onclick="mostrarVistaPreviaProyectos()">
                            <i class="fas fa-eye me-1"></i>Vista Previa
                        </button>
                        <button type="button" class="btn btn-primary" onclick="generarReporteProyectos()">
                            <i class="fas fa-file-download me-1"></i>Generar Reporte
                        </button>
                    </div>
                </form>
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
        
        // Variables para reportes
let reporteProyectosData = null;

// Función para abrir el modal de reportes de proyectos
function abrirModalReporteProyectos() {
    const modalElement = document.getElementById('modalReporteProyectos');
    if (modalElement) {
        const modal = new mdb.Modal(modalElement);
        modal.show();
        
        // Inicializar eventos después de abrir el modal
        inicializarEventosReporteProyectos();
        actualizarResumenReporteProyectos();
    }
}

// Inicializar eventos del modal de proyectos
function inicializarEventosReporteProyectos() {
    // Actualizar resumen cuando cambien los campos
    const campos = ['tipoReporte', 'formatoProy', 'usuario_id', 'etiqueta_id', 'orden_por', 'orden_direccion', 'min_unidades', 'porcentaje_entrega'];
    campos.forEach(campo => {
        const elementos = document.querySelectorAll(`[name="${campo}"]`);
        elementos.forEach(elemento => {
            elemento.addEventListener('change', actualizarResumenReporteProyectos);
        });
    });

    // Checkboxes de estados
    const checkboxesEstados = document.querySelectorAll('[name="estados[]"]');
    checkboxesEstados.forEach(checkbox => {
        checkbox.addEventListener('change', actualizarResumenReporteProyectos);
    });

    // Checkbox de con firma
    const checkboxFirma = document.getElementById('filtroConFirma');
    if (checkboxFirma) {
        checkboxFirma.addEventListener('change', actualizarResumenReporteProyectos);
    }

    // Checkboxes de opciones
    const checkboxesOpciones = ['incluirFotos', 'incluirFirmas', 'mostrarPorcentajes'];
    checkboxesOpciones.forEach(id => {
        const checkbox = document.getElementById(id);
        if (checkbox) {
            checkbox.addEventListener('change', actualizarResumenReporteProyectos);
        }
    });

    // Campos de fecha
    const fechaDesde = document.getElementById('fechaDesdeProy');
    const fechaHasta = document.getElementById('fechaHastaProy');
    if (fechaDesde && fechaHasta) {
        fechaDesde.addEventListener('change', actualizarResumenReporteProyectos);
        fechaHasta.addEventListener('change', actualizarResumenReporteProyectos);
        fechaDesde.addEventListener('change', validarFechasProyectos);
        fechaHasta.addEventListener('change', validarFechasProyectos);
    }
}

// Actualizar resumen del reporte de proyectos
function actualizarResumenReporteProyectos() {
    const tipoReporte = document.querySelector('input[name="tipoReporte"]:checked').value;
    const formato = document.querySelector('input[name="formatoProy"]:checked').value;
    const fechaDesde = document.getElementById('fechaDesdeProy').value;
    const fechaHasta = document.getElementById('fechaHastaProy').value;
    
    // Obtener estados seleccionados
    const estadosSeleccionados = [];
    document.querySelectorAll('[name="estados[]"]:checked').forEach(cb => {
        estadosSeleccionados.push(cb.value);
    });
    
    const conFirma = document.getElementById('filtroConFirma')?.checked;
    const usuario = document.getElementById('filtroUsuarioProy').value;
    const etiqueta = document.getElementById('filtroEtiquetaProy').value;
    const minUnidades = document.getElementById('filtroMinUnidades').value;
    const porcentajeEntrega = document.getElementById('filtroPorcentajeEntrega').value;
    
    // Construir resumen principal
    let resumen = '';
    
    switch(tipoReporte) {
        case 'general':
            resumen = 'Reporte general de proyectos';
            break;
        case 'detallado':
            resumen = 'Reporte detallado de proyectos (con etiquetas)';
            break;
        case 'estadistico':
            resumen = 'Reporte estadístico de proyectos';
            break;
    }
    
    resumen += ` en formato ${formato.toUpperCase()}`;
    
    // Construir detalles
    let detalles = [];
    
    // Rango de fechas
    if (fechaDesde && fechaHasta) {
        detalles.push(`Período: ${fechaDesde} a ${fechaHasta}`);
    }
    
    // Estados
    if (estadosSeleccionados.length > 0 && estadosSeleccionados.length < 3) {
        const textosEstados = {
            '1': 'Activos',
            '2': 'Inactivos',
            '3': 'Completados'
        };
        const estadosTexto = estadosSeleccionados.map(e => textosEstados[e] || e).join(', ');
        detalles.push(`Estados: ${estadosTexto}`);
    }
    
    if (conFirma) detalles.push('Solo con firma');
    if (usuario) detalles.push('Usuario específico');
    if (etiqueta) detalles.push('Etiqueta específica');
    if (minUnidades) detalles.push(`Mín. ${minUnidades} unidades`);
    if (porcentajeEntrega) detalles.push(`Mín. ${porcentajeEntrega}% entregado`);
    
    // Opciones
    if (document.getElementById('incluirFotos')?.checked) detalles.push('Con fotos');
    if (document.getElementById('incluirFirmas')?.checked) detalles.push('Con firmas');
    if (document.getElementById('mostrarPorcentajes')?.checked) detalles.push('Con % de avance');
    
    // Actualizar en el modal
    const resumenElement = document.getElementById('resumenReporteProy');
    const detalleElement = document.getElementById('detalleReporteProy');
    
    if (resumenElement) {
        resumenElement.textContent = resumen;
    }
    
    if (detalleElement) {
        if (detalles.length > 0) {
            detalleElement.textContent = detalles.join(' • ');
        } else {
            detalleElement.textContent = 'Incluyendo todos los proyectos sin filtros específicos';
        }
    }
}

// Validar fechas para proyectos
function validarFechasProyectos() {
    const fechaDesde = document.getElementById('fechaDesdeProy');
    const fechaHasta = document.getElementById('fechaHastaProy');
    
    if (fechaDesde.value && fechaHasta.value) {
        if (new Date(fechaDesde.value) > new Date(fechaHasta.value)) {
            fechaDesde.setCustomValidity('La fecha desde no puede ser mayor que la fecha hasta');
            fechaHasta.setCustomValidity('La fecha hasta no puede ser menor que la fecha desde');
            return false;
        } else {
            fechaDesde.setCustomValidity('');
            fechaHasta.setCustomValidity('');
        }
    }
    return true;
}

// Mostrar vista previa para proyectos
function mostrarVistaPreviaProyectos() {
    if (!validarFechasProyectos()) {
        mostrarMensaje('warning', 'Por favor corrige las fechas');
        return;
    }
    
    // Obtener datos del formulario
    const datos = obtenerDatosReporteProyectos();
    
    Swal.fire({
        title: 'Vista Previa del Reporte',
        html: `
            <div class="text-center">
                <i class="fas fa-project-diagram fa-4x text-primary mb-3"></i>
                <h6 class="mb-2">Reporte de Proyectos</h6>
                <div class="alert alert-info text-start mb-3">
                    <div><strong>Tipo:</strong> ${datos.tipo === 'general' ? 'General' : datos.tipo === 'detallado' ? 'Detallado' : 'Estadístico'}</div>
                    <div class="mt-1"><strong>Formato:</strong> ${datos.formato.toUpperCase()}</div>
                    <div class="mt-1"><strong>Estados incluidos:</strong> ${datos.estados.length === 3 ? 'Todos' : datos.estados.join(', ')}</div>
                    <div class="mt-1"><strong>Rango:</strong> ${datos.fecha_desde} a ${datos.fecha_hasta}</div>
                    <div class="mt-1"><strong>Filtros:</strong> 
                        ${datos.con_firma ? 'Con firma | ' : ''}
                        ${datos.min_unidades ? `Mín. ${datos.min_unidades} unid. | ` : ''}
                        ${datos.porcentaje_entrega ? `Mín. ${datos.porcentaje_entrega}% | ` : ''}
                        ${datos.incluir_fotos ? 'Con fotos' : 'Sin fotos'}
                    </div>
                </div>
                <div class="alert alert-warning small">
                    <i class="fas fa-exclamation-circle me-1"></i>
                    Esta es una vista previa. El reporte final incluirá todos los proyectos con los datos detallados.
                </div>
            </div>
        `,
        showConfirmButton: true,
        confirmButtonText: 'Continuar',
        showCancelButton: true,
        cancelButtonText: 'Ajustar',
        width: 600
    });
}

// Obtener datos del formulario de reporte de proyectos
function obtenerDatosReporteProyectos() {
    // Obtener estados seleccionados
    const estadosSeleccionados = [];
    document.querySelectorAll('[name="estados[]"]:checked').forEach(cb => {
        estadosSeleccionados.push(parseInt(cb.value));
    });
    
    return {
        token: authToken,
        tipo: document.querySelector('input[name="tipoReporte"]:checked').value,
        fecha_desde: document.getElementById('fechaDesdeProy').value || null,
        fecha_hasta: document.getElementById('fechaHastaProy').value || null,
        estados: estadosSeleccionados.length > 0 ? estadosSeleccionados : [1, 2, 3],
        con_firma: document.getElementById('filtroConFirma')?.checked || false,
        usuario_id: document.getElementById('filtroUsuarioProy').value || null,
        etiqueta_id: document.getElementById('filtroEtiquetaProy').value || null,
        min_unidades: document.getElementById('filtroMinUnidades').value || null,
        porcentaje_entrega: document.getElementById('filtroPorcentajeEntrega').value || null,
        formato: document.querySelector('input[name="formatoProy"]:checked').value,
        incluir_fotos: document.getElementById('incluirFotos')?.checked || false,
        incluir_firmas: document.getElementById('incluirFirmas')?.checked || false,
        mostrar_porcentajes: document.getElementById('mostrarPorcentajes')?.checked || false,
        orden_por: document.getElementById('ordenPorProy').value,
        orden_direccion: document.getElementById('ordenDireccionProy').value
    };
}

// Función principal para generar reporte de proyectos
async function generarReporteProyectos() {
    if (!validarFechasProyectos()) {
        mostrarMensaje('warning', 'Por favor corrige las fechas');
        return;
    }
    
    // Obtener datos del formulario
    const datos = obtenerDatosReporteProyectos();
    
    // Mostrar carga
    Swal.fire({
        title: 'Generando Reporte de Proyectos',
        html: `
            <div class="text-center">
                <i class="fas fa-spinner fa-spin fa-3x text-primary mb-3"></i>
                <p>Recopilando datos de proyectos...</p>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
                </div>
                <small class="text-muted mt-2 d-block">Esto puede tardar unos segundos</small>
            </div>
        `,
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    try {
        // Enviar datos al controlador
        const response = await fetch('controllers/proyectos.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                peticion: 'generar_reporte',
                ...datos
            })
        });
        
        const result = await response.json();
        console.log('Resultado del reporte de proyectos:', result);
        Swal.close();
        
        if (result.exito) {
            if (result.archivo) {
                // Si se generó un archivo, mostrar opción de descarga
                Swal.fire({
                    icon: 'success',
                    title: '¡Reporte Generado!',
                    html: `
                        <div class="text-center">
                            <i class="fas fa-file-download fa-3x text-success mb-3"></i>
                            <h6 class="mb-2">Reporte de Proyectos Generado</h6>
                            <div class="alert alert-info text-start">
                                <div><strong>Archivo:</strong> ${result.nombre_archivo || 'reporte_proyectos.pdf'}</div>
                                <div class="mt-1"><strong>Proyectos incluidos:</strong> ${result.total_proyectos || 0}</div>
                                <div class="mt-1"><strong>Estados:</strong> ${result.estados_incluidos || 'Todos'}</div>
                                <div class="mt-1"><strong>Fecha:</strong> ${new Date().toLocaleDateString()}</div>
                            </div>
                            <div class="d-grid gap-2 mt-3">
                                <a href="${result.archivo}" class="btn btn-primary" download="${result.nombre_archivo || 'reporte_proyectos.pdf'}">
                                    <i class="fas fa-download me-2"></i>Descargar Reporte
                                </a>
                                <button class="btn btn-outline-secondary" onclick="Swal.close()">
                                    Cerrar
                                </button>
                            </div>
                        </div>
                    `,
                    showConfirmButton: false,
                    width: 600
                });
            } else if (result.data) {
                // Si solo devolvió datos (para desarrollo o vista previa)
                reporteProyectosData = result.data;
                mostrarResultadoReporteProyectos(result.data);
            }
            
            // Cerrar el modal
            const modalElement = document.getElementById('modalReporteProyectos');
            if (modalElement) {
                const modal = mdb.Modal.getInstance(modalElement);
                if (modal) modal.hide();
            }
            
        } else {
            throw new Error(result.msj || 'Error generando reporte');
        }
        
    } catch (error) {
        console.error('Error generando reporte de proyectos:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo generar el reporte: ' + error.message
        });
    }
}

// Mostrar resultado del reporte (versión simplificada)
function mostrarResultadoReporteProyectos(datos) {
    const totalProyectos = datos.total_proyectos || datos.proyectos?.length || 0;
    const totalEtiquetas = datos.total_etiquetas || 0;
    const totalUnidades = datos.total_unidades || 0;
    const porcentajePromedio = datos.porcentaje_promedio_entrega || 0;
    
    Swal.fire({
        icon: 'success',
        title: 'Datos del Reporte',
        html: `
            <div class="text-center">
                <i class="fas fa-project-diagram fa-3x text-primary mb-3"></i>
                <h6 class="mb-3">Resumen del Reporte</h6>
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body py-2">
                                <h5 class="mb-0">${totalProyectos}</h5>
                                <small>Proyectos</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="card bg-success text-white">
                            <div class="card-body py-2">
                                <h5 class="mb-0">${totalEtiquetas}</h5>
                                <small>Etiquetas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="card bg-info text-white">
                            <div class="card-body py-2">
                                <h5 class="mb-0">${totalUnidades.toLocaleString()}</h5>
                                <small>Unidades</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body py-2">
                                <h5 class="mb-0">${porcentajePromedio.toFixed(1)}%</h5>
                                <small>Avance</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <button class="btn btn-outline-primary" onclick="verDetallesReporteProyectos()">
                        <i class="fas fa-list me-2"></i>Ver Detalles
                    </button>
                    <button class="btn btn-outline-secondary ms-2" onclick="exportarReporteProyectos()">
                        <i class="fas fa-download me-2"></i>Exportar
                    </button>
                </div>
            </div>
        `,
        showConfirmButton: false,
        width: 600
    });
}

// Ver detalles del reporte
function verDetallesReporteProyectos() {
    if (!reporteProyectosData || !reporteProyectosData.proyectos) {
        mostrarMensaje('info', 'No hay datos de reporte disponibles');
        return;
    }
    
    const proyectosHTML = reporteProyectosData.proyectos.map((proyecto, index) => `
        <tr>
            <td>${index + 1}</td>
            <td><strong>${proyecto.codigo}</strong></td>
            <td>${proyecto.nombre}</td>
            <td>
                <span class="badge ${proyecto.estado === 1 ? 'bg-success' : proyecto.estado === 3 ? 'bg-warning' : 'bg-secondary'}">
                    ${proyecto.estado_nombre || 'N/A'}
                </span>
            </td>
            <td>${proyecto.total_etiquetas || 0}</td>
            <td>${proyecto.total_unidades || 0}</td>
            <td>${proyecto.unidades_entregadas || 0}</td>
            <td>
                <span class="badge ${proyecto.porcentaje_entrega >= 100 ? 'bg-success' : proyecto.porcentaje_entrega >= 50 ? 'bg-warning' : 'bg-danger'}">
                    ${proyecto.porcentaje_entrega || 0}%
                </span>
            </td>
        </tr>
    `).join('');
    
    Swal.fire({
        title: 'Detalles del Reporte',
        html: `
            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Estado</th>
                            <th>Etiquetas</th>
                            <th>Total Unid.</th>
                            <th>Entregadas</th>
                            <th>% Entrega</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${proyectosHTML}
                    </tbody>
                </table>
            </div>
        `,
        showConfirmButton: true,
        confirmButtonText: 'Cerrar',
        width: 900
    });
}

// Exportar reporte (simulación)
function exportarReporteProyectos() {
    mostrarMensaje('info', 'En producción, esta función exportaría el reporte en el formato seleccionado');
}

// Agregar botón de reporte en la página de proyectos
function agregarBotonReporteProyectos() {
    const cardHeader = document.querySelector('.card-header');
    if (cardHeader && !document.getElementById('btnReporteProyectos')) {
        const botonHTML = `
            <button class="btn btn-primary btn-sm ms-2" id="btnReporteProyectos" onclick="abrirModalReporteProyectos()">
                <i class="fas fa-file-pdf me-1"></i>Generar Reporte
            </button>
        `;
        
        // Insertar después del botón "Nuevo Proyecto"
        const btnNuevoProyecto = cardHeader.querySelector('a[href="nuevo-proyecto.php"]');
        if (btnNuevoProyecto) {
            btnNuevoProyecto.insertAdjacentHTML('afterend', botonHTML);
        } else {
            cardHeader.insertAdjacentHTML('beforeend', botonHTML);
        }
    }
}

// Inicializar al cargar la página de proyectos
document.addEventListener('DOMContentLoaded', function() {
    // Verificar si estamos en la página de proyectos
    if (window.location.pathname.includes('proyectos')) {
        setTimeout(agregarBotonReporteProyectos, 500);
    }
});

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