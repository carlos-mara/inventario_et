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
    <!-- Tabs de navegación -->
    <div class="col-12 mb-4">
        <ul class="nav nav-tabs" id="salidasTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="individual-tab" data-bs-toggle="tab" data-bs-target="#individual" type="button" role="tab" aria-controls="individual" aria-selected="true">
                    <i class="fas fa-minus-circle me-2"></i>Salida Individual
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="multiple-tab" data-bs-toggle="tab" data-bs-target="#multiple" type="button" role="tab" aria-controls="multiple" aria-selected="false">
                    <i class="fas fa-list-alt me-2"></i>Salidas Múltiples
                </button>
            </li>
        </ul>
    </div>

    <!-- Contenido de las Tabs -->
    <div class="col-12">
        <div class="tab-content" id="salidasTabContent">
            
            <!-- Tab de Salida Individual -->
            <div class="tab-pane fade show active" id="individual" role="tabpanel" aria-labelledby="individual-tab">
                <div class="row">
                    <div class="col-lg-6 mb-4">
                        <div class="card salida-card border-top-0">
                            <div class="card-header bg-danger text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-minus-circle me-2"></i>Nueva Salida Individual
                                </h5>
                            </div>
                            <div class="card-body">
                                <form id="formSalidaIndividual" enctype="multipart/form-data">
                                    <!-- Selección de Proyecto (Opcional) -->
                                    <div class="mb-3">
                                        <label for="proyectoIdIndividual" class="form-label fw-bold">Proyecto <span class="text-muted">(opcional)</span></label>
                                        <div class="input-group">
                                            <select class="form-select proyecto-select" id="proyectoIdIndividual">
                                                <option value="">Seleccionar proyecto...</option>
                                                <option value="">Ninguno (salida general)</option>
                                            </select>
                                            <button class="btn btn-outline-secondary" type="button" onclick="limpiarProyectoIndividual()" title="Limpiar selección">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                        <div class="form-text">Si la salida está relacionada con un proyecto, selecciónalo aquí</div>
                                    </div>

                                    <!-- Información del Proyecto Seleccionado -->
                                    <div class="alert alert-info p-3 mb-3 d-none" id="infoProyectoIndividual">
                                        <div class="row">
                                            <div class="col-8">
                                                <h6 class="mb-1" id="proyectoNombreIndividual"></h6>
                                                <small id="proyectoCodigoIndividual"></small>
                                            </div>
                                            <div class="col-4 text-end">
                                                <div class="fw-bold" id="proyectoEstadoIndividual"></div>
                                                <small>Estado</small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Selección de Etiqueta -->
                                    <div class="mb-3">
                                        <label for="etiquetaIdIndividual" class="form-label fw-bold">Etiqueta *</label>
                                        <div class="input-group">
                                            <select class="form-select" id="etiquetaIdIndividual" required>
                                                <option value="">Seleccionar etiqueta...</option>
                                            </select>
                                            <button class="btn btn-outline-primary" type="button" onclick="recargarEtiquetasIndividual()" title="Recargar etiquetas">
                                                <i class="fas fa-sync-alt"></i>
                                            </button>
                                        </div>
                                        <div class="form-text" id="etiquetaInfoTextIndividual">Seleccione primero un proyecto para ver las etiquetas relacionadas</div>
                                    </div>
                                    <div class="etiqueta-info p-3 text-white mb-3" id="infoEtiquetaIndividual" style="display: none;">
                                        <div class="row">
                                            <div class="col-8">
                                                <h6 class="mb-1" id="etiquetaNombreIndividual"></h6>
                                                <small id="etiquetaCategoriaIndividual"></small>
                                            </div>
                                            <div class="col-4 text-end">
                                                <div class="fw-bold" id="etiquetaStockTotalIndividual"></div>
                                                <small>Stock total</small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Información de Cantidades por Proyecto -->
                                    <div class="cantidad-info-card d-none" id="cantidadInfoCardIndividual">
                                        <div class="cantidad-info-item">
                                            <span class="cantidad-info-label">Cantidad asignada al proyecto:</span>
                                            <span class="cantidad-info-value" id="cantidadAsignadaIndividual">0</span>
                                        </div>
                                        <div class="cantidad-info-item">
                                            <span class="cantidad-info-label">Cantidad ya entregada:</span>
                                            <span class="cantidad-info-value" id="cantidadEntregadaIndividual">0</span>
                                        </div>
                                        <div class="cantidad-restante">
                                            <div class="cantidad-info-item">
                                                <span class="cantidad-info-label restante-text">Cantidad restante:</span>
                                                <span class="cantidad-info-value">
                                                    <span class="badge restante-badge" id="cantidadRestanteBadgeIndividual">0</span>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Selección de Tamaño -->
                                    <div class="tamanos-container d-none" id="tamanosContainerIndividual">
                                        <label class="form-label fw-bold mb-3">Seleccione el Tamaño *</label>
                                        <div id="tamanosRadioGroupIndividual"></div>
                                    </div>

                                    <!-- Cantidad -->
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Cantidad *</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="cantidadIndividual" min="1" required placeholder="Ej: 50">
                                            <span class="input-group-text">unidades</span>
                                        </div>
                                        <div class="form-text" id="infoCantidadIndividual"></div>
                                    </div>

                                    

                                    <!-- Motivo -->
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Motivo de Salida *</label>
                                        <select class="form-select" id="motivoIndividual" required>
                                            <option value="">Seleccionar motivo...</option>
                                            <option value="consumo_interno">Consumo Interno</option>
                                            <option value="devolucion">Devolución</option>
                                            <option value="merma">Merma/Pérdida</option>
                                            <option value="ajuste_inventario">Ajuste de Inventario</option>
                                            <option value="otros">Otros</option>
                                        </select>
                                    </div>

                                    <!-- Captura de Foto -->
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Foto de la entrega <span class="text-muted">(opcional)</span></label>
                                        <div class="photo-container mb-2" id="photoContainerIndividual">
                                            <video id="videoPreviewIndividual" class="video-preview d-none"></video>
                                            <img id="photoPreviewIndividual" class="photo-preview d-none">
                                            <div id="photoPlaceholderIndividual" class="d-flex flex-column align-items-center justify-content-center h-100">
                                                <i class="fas fa-camera fa-4x text-muted mb-3"></i>
                                                <p class="text-muted text-center">Haga clic para tomar una foto de la entrega</p>
                                            </div>
                                            <div class="photo-controls d-none" id="photoControlsIndividual">
                                                <button type="button" class="btn btn-danger photo-btn" onclick="capturePhotoIndividual()" title="Tomar foto">
                                                    <i class="fas fa-camera"></i>
                                                </button>
                                                <button type="button" class="btn btn-warning photo-btn" onclick="retakePhotoIndividual()" title="Volver a tomar">
                                                    <i class="fas fa-redo"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="startCameraIndividual()" id="startCameraBtnIndividual">
                                                <i class="fas fa-video me-1"></i>Activar Cámara
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="stopCameraIndividual()" id="stopCameraBtnIndividual" style="display: none;">
                                                <i class="fas fa-stop me-1"></i>Detener Cámara
                                            </button>
                                            <button type="button" class="btn btn-outline-info btn-sm" onclick="uploadFileIndividual()" id="uploadFileBtnIndividual">
                                                <i class="fas fa-upload me-1"></i>Subir Archivo
                                            </button>
                                        </div>
                                        <input type="file" id="photoFileIndividual" accept="image/*" capture="environment" class="d-none" onchange="handleFileSelectIndividual(event)">
                                        <input type="hidden" id="fotoBase64Individual" name="fotoBase64Individual">
                                        
                                        <div class="progress-container" id="progressContainerIndividual">
                                            <div class="progress mt-2">
                                                <div id="uploadProgressIndividual" class="progress-bar progress-bar-striped progress-bar-animated" 
                                                     role="progressbar" style="width: 0%"></div>
                                            </div>
                                            <small class="text-muted" id="progressTextIndividual">Comprimiendo imagen...</small>
                                        </div>
                                        <div class="form-text">Tome una foto del material entregado como evidencia</div>
                                    </div>

                                    <!-- Referencia -->
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Referencia <span class="text-muted">(opcional)</span></label>
                                        <input type="text" class="form-control" id="referenciaIndividual" placeholder="N° Factura, Orden de Venta, etc.">
                                    </div>

                                    <!-- Observaciones -->
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Observaciones <span class="text-muted">(opcional)</span></label>
                                        <textarea class="form-control" id="observacionesIndividual" rows="3" placeholder="Detalles adicionales de la salida..."></textarea>
                                    </div>

                                    <!-- Botones -->
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <button type="reset" class="btn btn-outline-secondary me-2" onclick="limpiarFormularioIndividual()">
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
                                <img id="img_etiqueta_individual" class="img-fluid rounded d-none" style="max-height: 250px; object-fit: contain;" src="" alt="Imagen de etiqueta">
                                <div id="sinImagenIndividual" class="text-muted">
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
                                <div id="stockSummaryIndividual" class="text-muted text-center">
                                    <i class="fas fa-box-open fa-3x mb-3"></i>
                                    <p>Seleccione una etiqueta para ver el resumen de stock</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab de Salidas Múltiples -->
            <div class="tab-pane fade" id="multiple" role="tabpanel" aria-labelledby="multiple-tab">
                <div class="row">
                    <div class="col-lg-8 mb-4">
                        <div class="card salida-card border-top-0">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0">
                                    <i class="fas fa-list-alt me-2"></i>Salidas Múltiples
                                </h5>
                            </div>
                            <div class="card-body">
                                <form id="formSalidasMultiples" enctype="multipart/form-data">
                                    <!-- Selección de Proyecto (Opcional) -->
                                    <div class="mb-3">
                                        <label for="proyectoIdMultiple" class="form-label fw-bold">Proyecto <span class="text-muted">(opcional)</span></label>
                                        <div class="input-group">
                                            <select class="form-select proyecto-select" id="proyectoIdMultiple">
                                                <option value="">Seleccionar proyecto...</option>
                                                <option value="">Ninguno (salida general)</option>
                                            </select>
                                            <button class="btn btn-outline-secondary" type="button" onclick="limpiarProyectoMultiple()" title="Limpiar selección">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                        <div class="form-text">Si las salidas están relacionadas con un proyecto, selecciónalo aquí</div>
                                    </div>

                                    <!-- Información del Proyecto Seleccionado -->
                                    <div class="alert alert-info p-3 mb-3 d-none" id="infoProyectoMultiple">
                                        <div class="row">
                                            <div class="col-8">
                                                <h6 class="mb-1" id="proyectoNombreMultiple"></h6>
                                                <small id="proyectoCodigoMultiple"></small>
                                            </div>
                                            <div class="col-4 text-end">
                                                <div class="fw-bold" id="proyectoEstadoMultiple"></div>
                                                <small>Estado</small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Contenedor de Items Múltiples -->
                                    <div class="multiple-items-container mb-4">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <label class="form-label fw-bold mb-0">Items a Despachar *</label>
                                            
                                        </div>
                                        
                                        <!-- Items Agregados -->
                                        <div id="itemsContainer" class="items-list">
                                            <!-- Item 1 (por defecto) -->
                                            <div class="item-card mb-3" data-item-index="1">
                                                <div class="item-header bg-light p-2 rounded-top d-flex justify-content-between align-items-center">
                                                    <span class="fw-bold">Item #1</span>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarItem(1)" data-item-count="1">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                                <div class="item-body p-3 border border-top-0 rounded-bottom">
                                                    <!-- Selección de Etiqueta -->
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Etiqueta *</label>
                                                        <div class="input-group">
                                                            <select class="form-select item-etiqueta-select" name="etiquetaId[]" data-item-index="1" required onchange="actualizarInfoItem(1)">
                                                                <option value="">Seleccionar etiqueta...</option>
                                                            </select>
                                                            <button class="btn btn-outline-primary" type="button" onclick="recargarEtiquetasItem(1)" title="Recargar etiquetas">
                                                                <i class="fas fa-sync-alt"></i>
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <!-- Selección de Tamaño -->
                                                    <div class="tamanos-container-item mb-3 d-none" id="tamanosContainerItem1">
                                                        <label class="form-label fw-bold mb-2">Tamaño *</label>
                                                        <div class="tamanos-radio-group" id="tamanosRadioGroupItem1"></div>
                                                    </div>
                                                    
                                                    <!-- Cantidad -->
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Cantidad *</label>
                                                        <div class="input-group">
                                                            <input type="number" class="form-control item-cantidad" name="cantidad[]" data-item-index="1" min="1" required placeholder="Ej: 50">
                                                            <span class="input-group-text">unidades</span>
                                                        </div>
                                                        <div class="form-text stock-info" id="stockInfoItem1"></div>
                                                    </div>

                                                    <!-- Información del Item -->
                                                    <div class="alert alert-sm alert-secondary p-2 d-none" id="infoItem1">
                                                        <div class="row small">
                                                            <div class="col-6">
                                                                <strong>Etiqueta:</strong>
                                                                <div class="item-etiqueta-nombre"></div>
                                                            </div>
                                                            <div class="col-3">
                                                                <strong>Stock:</strong>
                                                                <div class="item-stock"></div>
                                                            </div>
                                                            <div class="col-3">
                                                                <strong>Tamaño:</strong>
                                                                <div class="item-tamano"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <button type="button" class="btn btn-lg btn-outline-primary" onclick="agregarItemMultiple()">
                                            <i class="fas fa-plus me-1"></i>Agregar Item
                                        </button>
                                    </div>
                                    <!-- Motivo (común para todas las salidas) -->
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Motivo de Salidas *</label>
                                        <select class="form-select" id="motivoMultiple" required>
                                            <option value="">Seleccionar motivo...</option>
                                            <option value="consumo_interno">Consumo Interno</option>
                                            <option value="devolucion">Devolución</option>
                                            <option value="merma">Merma/Pérdida</option>
                                            <option value="ajuste_inventario">Ajuste de Inventario</option>
                                            <option value="otros">Otros</option>
                                        </select>
                                        <div class="form-text">Este motivo se aplicará a todas las salidas del lote</div>
                                    </div>

                                    <!-- Captura de Foto (opcional para múltiples) -->
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Foto del lote <span class="text-muted">(opcional)</span></label>
                                        <div class="photo-container-multiple mb-2" id="photoContainerMultiple">
                                            <div id="photoPlaceholderMultiple" class="d-flex flex-column align-items-center justify-content-center h-100 p-3 border rounded bg-light">
                                                <i class="fas fa-camera fa-3x text-muted mb-2"></i>
                                                <p class="text-muted text-center small">Tome una foto del lote completo o suba una imagen</p>
                                            </div>
                                            <img id="photoPreviewMultiple" class="photo-preview-multiple d-none rounded border">
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="startCameraMultiple()" id="startCameraBtnMultiple">
                                                <i class="fas fa-video me-1"></i>Activar Cámara
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="stopCameraMultiple()" id="stopCameraBtnMultiple" style="display: none;">
                                                <i class="fas fa-stop me-1"></i>Detener Cámara
                                            </button>
                                            <button type="button" class="btn btn-outline-info btn-sm" onclick="uploadFileMultiple()" id="uploadFileBtnMultiple">
                                                <i class="fas fa-upload me-1"></i>Subir Archivo
                                            </button>
                                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removePhotoMultiple()" id="removePhotoBtnMultiple" style="display: none;">
                                                <i class="fas fa-trash me-1"></i>Eliminar
                                            </button>
                                        </div>
                                        <input type="file" id="photoFileMultiple" accept="image/*" class="d-none" onchange="handleFileSelectMultiple(event)">
                                        <input type="hidden" id="fotoBase64Multiple" name="fotoBase64Multiple">
                                        <div class="form-text">Foto opcional del lote completo de materiales a despachar</div>
                                    </div>

                                    <!-- Referencia (común) -->
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Referencia <span class="text-muted">(opcional)</span></label>
                                        <input type="text" class="form-control" id="referenciaMultiple" placeholder="N° Factura, Orden de Venta, etc.">
                                    </div>

                                    <!-- Observaciones (comunes) -->
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Observaciones <span class="text-muted">(opcional)</span></label>
                                        <textarea class="form-control" id="observacionesMultiple" rows="3" placeholder="Detalles adicionales del despacho múltiple..."></textarea>
                                    </div>

                                    <!-- Botones -->
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <button type="reset" class="btn btn-outline-secondary me-2" onclick="limpiarFormularioMultiple()">
                                            <i class="fas fa-redo me-1"></i>Limpiar Todo
                                        </button>
                                        <button type="submit" class="btn btn-warning">
                                            <i class="fas fa-paper-plane me-1"></i>Registrar Salidas Múltiples
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 mb-4">
                        <!-- Resumen de Salidas Múltiples -->
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-clipboard-list me-2"></i>Resumen de Salidas
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="resumen-multiple">
                                    <div class="text-center mb-3">
                                        <i class="fas fa-boxes fa-3x text-primary mb-2"></i>
                                        <h6>Detalle del Despacho</h6>
                                    </div>
                                    
                                    <div class="row mb-2">
                                        <div class="col-6">
                                            <strong>Total Items:</strong>
                                        </div>
                                        <div class="col-6 text-end">
                                            <span id="totalItems" class="badge bg-primary rounded-pill">1</span>
                                        </div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-6">
                                            <strong>Total Unidades:</strong>
                                        </div>
                                        <div class="col-6 text-end">
                                            <span id="totalUnidades" class="badge bg-success rounded-pill">0</span>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <strong>Etiquetas únicas:</strong>
                                        </div>
                                        <div class="col-6 text-end">
                                            <span id="totalEtiquetasUnicas" class="badge bg-info rounded-pill">0</span>
                                        </div>
                                    </div>
                                    
                                    <hr>
                                    
                                    <div class="listado-items" id="listadoItemsResumen">
                                        <small class="text-muted">Agregue items para ver el detalle aquí</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Información del Proyecto -->
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-project-diagram me-2"></i>Información del Proyecto
                                </h5>
                            </div>
                            <div class="card-body">
                                <div id="infoProyectoResumen" class="text-muted text-center">
                                    <i class="fas fa-folder-open fa-3x mb-3"></i>
                                    <p>Seleccione un proyecto para ver la información</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para confirmación de eliminación de item -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                ¿Está seguro de eliminar este item?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Eliminar</button>
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
    
    // Variables para el modo múltiple
    let itemCounter = 1;
    let mediaStreamMultiple = null;
    let fotoCapturadaMultiple = null;
    let tamanosAsignadosPorProyecto = {}; // Para almacenar tamaños por proyecto

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
                llenarSelectProyectos('individual');
                llenarSelectProyectos('multiple');
            } else {
                throw new Error(result.msj);
            }
        } catch (error) {
            console.error('Error cargando proyectos:', error);
        }
    }

    // Llenar select de proyectos para ambos modos
    function llenarSelectProyectos(modo) {
        const selectId = modo === 'individual' ? 'proyectoIdIndividual' : 'proyectoIdMultiple';
        const select = document.getElementById(selectId);
        
        select.innerHTML = '<option value="">Seleccionar proyecto...</option><option value="">Ninguno (salida general)</option>';
        
        proyectos.forEach(proyecto => {
            const option = document.createElement('option');
            option.value = proyecto.id;
            option.textContent = `${proyecto.codigo} - ${proyecto.nombre.toUpperCase()}`;
            select.appendChild(option);
        });
    }

    // Mostrar información del proyecto seleccionado (individual)
    async function mostrarInfoProyectoIndividual(proyectoId) {
        const proyecto = proyectos.find(p => p.id == proyectoId);
        const infoDiv = document.getElementById('infoProyectoIndividual');
        
        if (proyecto) {
            document.getElementById('proyectoNombreIndividual').textContent = proyecto.nombre;
            document.getElementById('proyectoCodigoIndividual').textContent = `Código: ${proyecto.codigo}`;
            document.getElementById('proyectoEstadoIndividual').textContent = proyecto.estado == 1 ? 'Activo' : 'Inactivo';
            infoDiv.classList.remove('d-none');
            
            // Cargar etiquetas del proyecto
            await cargarEtiquetasPorProyecto(proyectoId, 'individual');
            
            // Si hay una etiqueta seleccionada, actualizar sus tamaños
            const etiquetaId = document.getElementById('etiquetaIdIndividual').value;
            if (etiquetaId) {
                await cargarTamanosEtiquetaIndividual(etiquetaId, proyectoId);
            }
        } else {
            infoDiv.classList.add('d-none');
            // Si no hay proyecto seleccionado, cargar todas las etiquetas
            await cargarTodasLasEtiquetas('individual');
            
            // Si hay una etiqueta seleccionada, cargar todos sus tamaños
            const etiquetaId = document.getElementById('etiquetaIdIndividual').value;
            if (etiquetaId) {
                await cargarTamanosEtiquetaIndividual(etiquetaId, null);
            }
        }
    }

    // Mostrar información del proyecto seleccionado (múltiple)
    async function mostrarInfoProyectoMultiple(proyectoId) {
        const proyecto = proyectos.find(p => p.id == proyectoId);
        const infoDiv = document.getElementById('infoProyectoMultiple');
        const resumenDiv = document.getElementById('infoProyectoResumen');
        
        if (proyecto) {
            document.getElementById('proyectoNombreMultiple').textContent = proyecto.nombre;
            document.getElementById('proyectoCodigoMultiple').textContent = `Código: ${proyecto.codigo}`;
            document.getElementById('proyectoEstadoMultiple').textContent = proyecto.estado == 1 ? 'Activo' : 'Inactivo';
            infoDiv.classList.remove('d-none');
            
            if (resumenDiv) {
                resumenDiv.innerHTML = `
                    <h6 class="mb-2">${proyecto.nombre}</h6>
                    <p class="mb-1"><small>Código: ${proyecto.codigo}</small></p>
                    <p class="mb-1"><small>Estado: <span class="badge ${proyecto.estado == 1 ? 'bg-success' : 'bg-secondary'}">${proyecto.estado == 1 ? 'Activo' : 'Inactivo'}</span></small></p>
                `;
            }
            
            await cargarEtiquetasPorProyecto(proyectoId, 'multiple');
            
            // Actualizar tamaños para todos los items existentes
            const items = document.querySelectorAll('.item-card');
            items.forEach(item => {
                const itemIndex = item.dataset.itemIndex;
                const select = document.querySelector(`.item-etiqueta-select[data-item-index="${itemIndex}"]`);
                if (select && select.value) {
                    cargarTamanosItem(itemIndex, select.value, proyectoId);
                }
            });
        } else {
            infoDiv.classList.add('d-none');
            if (resumenDiv) {
                resumenDiv.innerHTML = `
                    <i class="fas fa-folder-open fa-3x mb-3"></i>
                    <p>Seleccione un proyecto para ver la información</p>
                `;
            }
            await cargarTodasLasEtiquetas('multiple');
            
            // Actualizar tamaños para todos los items existentes (sin proyecto)
            const items = document.querySelectorAll('.item-card');
            items.forEach(item => {
                const itemIndex = item.dataset.itemIndex;
                const select = document.querySelector(`.item-etiqueta-select[data-item-index="${itemIndex}"]`);
                if (select && select.value) {
                    cargarTamanosItem(itemIndex, select.value, null);
                }
            });
        }
    }

    // Cargar etiquetas por proyecto para modo específico
    async function cargarEtiquetasPorProyecto(proyectoId, modo) {
        try {
            if (modo === 'individual') {
                document.getElementById('etiquetaIdIndividual').innerHTML = '<option value="">Cargando etiquetas...</option>';
                document.getElementById('etiquetaInfoTextIndividual').textContent = 'Cargando etiquetas del proyecto...';
            }
            
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
                if (modo === 'individual') {
                    llenarSelectEtiquetas('individual');
                    document.getElementById('etiquetaInfoTextIndividual').textContent = `Mostrando ${etiquetas.length} etiquetas del proyecto seleccionado`;
                } else if (modo === 'multiple') {
                    actualizarSelectsEtiquetasItems();
                }
            } else {
                throw new Error(result.msj);
            }
        } catch (error) {
            console.error('Error cargando etiquetas por proyecto:', error);
            mostrarMensaje('error', 'Error al cargar las etiquetas del proyecto');
            await cargarTodasLasEtiquetas(modo);
        }
    }

    // Cargar todas las etiquetas para modo específico
    async function cargarTodasLasEtiquetas(modo) {
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
                if (modo === 'individual') {
                    llenarSelectEtiquetas('individual');
                    document.getElementById('etiquetaInfoTextIndividual').textContent = 'Mostrando todas las etiquetas disponibles';
                } else if (modo === 'multiple') {
                    actualizarSelectsEtiquetasItems();
                }
            } else {
                throw new Error(result.msj);
            }
        } catch (error) {
            console.error('Error cargando todas las etiquetas:', error);
            mostrarMensaje('error', 'Error al cargar las etiquetas');
        }
    }

    // Recargar etiquetas manualmente (individual)
    function recargarEtiquetasIndividual() {
        const proyectoId = document.getElementById('proyectoIdIndividual').value;
        if (proyectoId) {
            cargarEtiquetasPorProyecto(proyectoId, 'individual');
        } else {
            cargarTodasLasEtiquetas('individual');
        }
    }

    // Recargar etiquetas para un item específico (múltiple)
    function recargarEtiquetasItem(itemIndex) {
        const proyectoId = document.getElementById('proyectoIdMultiple').value;
        if (proyectoId) {
            cargarEtiquetasPorProyecto(proyectoId, 'multiple');
        } else {
            cargarTodasLasEtiquetas('multiple');
        }
    }

    // Llenar select de etiquetas (individual)
    function llenarSelectEtiquetas(modo) {
        if (modo === 'individual') {
            const select = document.getElementById('etiquetaIdIndividual');
            select.innerHTML = '<option value="">Seleccionar etiqueta...</option>';
            
            etiquetas.forEach(etiqueta => {
                if (etiqueta.activa) {
                    const option = document.createElement('option');
                    option.value = etiqueta.id;
                    option.dataset.cantidad_asignada = etiqueta.cantidad_asignada; 
                    option.dataset.cantidad_entregada = etiqueta.cantidad_entregada; 
                    option.textContent = `${etiqueta.nombre.toUpperCase()}`;
                    select.appendChild(option);
                }
            });
            
            document.getElementById('cantidadInfoCardIndividual').classList.add('d-none');
            cantidadAsignadaPorProyecto = 0;
            cantidadEntregadaPorProyecto = 0;
        }
    }

    // Actualizar selects de etiquetas para todos los items (múltiple)
    function actualizarSelectsEtiquetasItems() {
        const selects = document.querySelectorAll('.item-etiqueta-select');
        selects.forEach(select => {
            const selectedValue = select.value;
            const itemIndex = select.dataset.itemIndex;
            
            select.innerHTML = '<option value="">Seleccionar etiqueta...</option>';
            
            etiquetas.forEach(etiqueta => {
                if (etiqueta.activa) {
                    const option = document.createElement('option');
                    option.value = etiqueta.id;
                    option.dataset.cantidad_asignada = etiqueta.cantidad_asignada;
                    option.dataset.cantidad_entregada = etiqueta.cantidad_entregada;
                    option.textContent = etiqueta.nombre.toUpperCase();
                    select.appendChild(option);
                }
            });
            
            if (selectedValue) {
                select.value = selectedValue;
                if (select.value === selectedValue) {
                    actualizarInfoItem(itemIndex);
                }
            }
        });
    }

    // Mostrar información de etiqueta seleccionada (individual)
    async function mostrarInfoEtiquetaIndividual(etiquetaId, cantidad_entregada, cantidad_asignada) {
        const etiqueta = etiquetas.find(e => e.id == etiquetaId);
        const infoDiv = document.getElementById('infoEtiquetaIndividual');
        
        if (etiqueta) {
            document.getElementById('etiquetaNombreIndividual').textContent = etiqueta.nombre;
            document.getElementById('etiquetaCategoriaIndividual').textContent = etiqueta.categoria_nombre || 'Sin categoría';
            document.getElementById('etiquetaStockTotalIndividual').textContent = `${etiqueta.stock_total || 0} unidades`;
            
            const imgElement = document.getElementById('img_etiqueta_individual');
            const sinImagenDiv = document.getElementById('sinImagenIndividual');
            if (etiqueta.foto_url) {
                imgElement.src = "uploads/" + etiqueta.foto_url;
                imgElement.classList.remove('d-none');
                sinImagenDiv.classList.add('d-none');
            } else {
                imgElement.classList.add('d-none');
                sinImagenDiv.classList.remove('d-none');
            }
            
            infoDiv.style.display = 'block';
            
            // Obtener proyecto actual
            const proyectoId = document.getElementById('proyectoIdIndividual').value;
            
            // Mostrar información de cantidades si hay proyecto
            if (proyectoId && cantidad_entregada && cantidad_asignada) {
                cantidadAsignadaPorProyecto = parseInt(cantidad_asignada) || 0;
                cantidadEntregadaPorProyecto = parseInt(cantidad_entregada) || 0;
                mostrarInfoCantidadesIndividual();
            } else {
                document.getElementById('cantidadInfoCardIndividual').classList.add('d-none');
                cantidadAsignadaPorProyecto = 0;
                cantidadEntregadaPorProyecto = 0;
            }
            
            // Cargar tamaños de la etiqueta (con proyecto si existe)
            await cargarTamanosEtiquetaIndividual(etiquetaId, proyectoId);
            
            actualizarResumenStockIndividual(etiqueta);
        } else {
            infoDiv.style.display = 'none';
            document.getElementById('img_etiqueta_individual').classList.add('d-none');
            document.getElementById('sinImagenIndividual').classList.remove('d-none');
            document.getElementById('cantidadInfoCardIndividual').classList.add('d-none');
            document.getElementById('stockSummaryIndividual').innerHTML = `
                <i class="fas fa-box-open fa-3x mb-3"></i>
                <p>Seleccione una etiqueta para ver el resumen de stock</p>
            `;
        }
    }

    // Actualizar información de un item específico (múltiple)
    async function actualizarInfoItem(itemIndex) {
        const select = document.querySelector(`.item-etiqueta-select[data-item-index="${itemIndex}"]`);
        const etiquetaId = select.value;
        const option = select.options[select.selectedIndex];
        const infoDiv = document.getElementById(`infoItem${itemIndex}`);
        const proyectoId = document.getElementById('proyectoIdMultiple').value;
        
        if (etiquetaId && option) {
            const etiqueta = etiquetas.find(e => e.id == etiquetaId);
            
            if (etiqueta) {
                const itemInfoDiv = infoDiv.querySelector('.item-etiqueta-nombre');
                const itemStockDiv = infoDiv.querySelector('.item-stock');
                const itemfotoDiv = infoDiv.querySelector('.item-tamano');
                
                if (itemInfoDiv) itemInfoDiv.textContent = etiqueta.nombre;
                if (itemStockDiv) itemStockDiv.textContent = `${etiqueta.stock_total || 0} unidades`;
                if (itemfotoDiv) itemfotoDiv.innerHTML = etiqueta.foto_url ? 
                    `<img src='uploads/${etiqueta.foto_url}' alt='Foto Etiqueta' class='img-fluid rounded' style='max-height: 50px;'>` : 
                    'Sin imagen';
                
                // Mostrar información de cantidades si hay proyecto
                if (proyectoId) {
                    const cantidadAsignada = option.dataset.cantidad_asignada || 0;
                    const cantidadEntregada = option.dataset.cantidad_entregada || 0;
                    const cantidadRestante = cantidadAsignada - cantidadEntregada;
                    
                    // Actualizar el stockInfo para mostrar esta información
                    const stockInfo = document.getElementById(`stockInfoItem${itemIndex}`);
                    if (stockInfo) {
                        stockInfo.innerHTML = `
                            <div class="small">
                                <div><strong>Asignado al proyecto:</strong> ${cantidadAsignada} unidades</div>
                                <div><strong>Ya entregado:</strong> ${cantidadEntregada} unidades</div>
                                <div><strong>Restante por entregar:</strong> ${cantidadRestante} unidades</div>
                            </div>
                        `;
                    }
                }
                
                await cargarTamanosItem(itemIndex, etiquetaId, proyectoId);
                
                infoDiv.classList.remove('d-none');
            } else {
                infoDiv.classList.add('d-none');
            }
        } else {
            infoDiv.classList.add('d-none');
        }
        
        actualizarResumenMultiple();
    }

    // Mostrar información de cantidades (individual)
    function mostrarInfoCantidadesIndividual() {
        const infoCard = document.getElementById('cantidadInfoCardIndividual');
        const cantidadRestante = cantidadAsignadaPorProyecto - cantidadEntregadaPorProyecto;
        
        document.getElementById('cantidadAsignadaIndividual').textContent = cantidadAsignadaPorProyecto;
        document.getElementById('cantidadEntregadaIndividual').textContent = cantidadEntregadaPorProyecto;
        
        const restanteBadge = document.getElementById('cantidadRestanteBadgeIndividual');
        restanteBadge.textContent = cantidadRestante;
        
        if (cantidadRestante <= 0) {
            restanteBadge.className = 'badge bg-danger restante-badge';
        } else if (cantidadRestante <= 10) {
            restanteBadge.className = 'badge bg-warning restante-badge';
        } else {
            restanteBadge.className = 'badge bg-success restante-badge';
        }
        
        infoCard.classList.remove('d-none');
    }

    // Cargar tamaños de la etiqueta (individual) - MODIFICADA para filtrar por proyecto
    async function cargarTamanosEtiquetaIndividual(etiquetaId, proyectoId) {
        const container = document.getElementById('tamanosContainerIndividual');
        const radioGroup = document.getElementById('tamanosRadioGroupIndividual');
        
        radioGroup.innerHTML = '';
        container.classList.add('d-none');
        tamanoSeleccionado = null;
        tamanosActuales = [];
        
        if (!etiquetaId) {
            return;
        }
        
        try {
            radioGroup.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin me-2"></i>Cargando tamaños...</div>';
            container.classList.remove('d-none');
            
            const formData = new FormData();
            formData.append('peticion', proyectoId ? 'consultar_tamanos_por_proyecto' : 'consultar_tamanos');
            formData.append('etiqueta_id', etiquetaId);
            if (proyectoId) {
                formData.append('proyecto_id', proyectoId);
            }
            formData.append('token', authToken);

            const response = await fetch('controllers/etiquetas.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            
            if (data.exito && data.tamanos && data.tamanos.length > 0) {
                tamanosActuales = data.tamanos;
                
                radioGroup.innerHTML = '';
                let hayTamanosDisponibles = false;
                
                data.tamanos.forEach((tamano, index) => {
                    // Solo mostrar tamaños con stock disponible
                    if (tamano) {
                        hayTamanosDisponibles = true;
                        const radioId = `tamano-individual-${tamano.id_tamano || tamano.alto + '-' + tamano.ancho}`;
                        const badgeClass = obtenerClaseBadgeStock(tamano.stock_actual);
                        
                        const tamanoDiv = document.createElement('div');
                        tamanoDiv.className = 'tamano-option';
                        
                        tamanoDiv.innerHTML = `
                            <div class="form-check">
                                <input class="form-check-input" type="radio" 
                                    name="tamano_seleccionado_individual" 
                                    id="${radioId}"
                                    value='${JSON.stringify(tamano)}'
                                    ${index === 0 ? 'checked' : ''}
                                    onchange="actualizarInfoTamanoIndividual(this)">
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
                        radioGroup.appendChild(tamanoDiv);
                        
                        if (index === 0) {
                            setTimeout(() => {
                                actualizarInfoTamanoIndividual(document.getElementById(radioId));
                            }, 100);
                        }
                    }
                });
                
                if (!hayTamanosDisponibles) {
                    radioGroup.innerHTML = '<div class="alert alert-warning text-center">No hay tamaños con stock disponible para esta etiqueta</div>';
                }
            } else {
                const mensaje = proyectoId ? 
                    'No hay tamaños asignados a este proyecto para esta etiqueta' :
                    'No hay tamaños disponibles para esta etiqueta';
                radioGroup.innerHTML = `<div class="alert alert-warning text-center">${mensaje}</div>`;
            }
            
        } catch (error) {
            console.error('Error al cargar tamaños:', error);
            radioGroup.innerHTML = '<div class="alert alert-danger text-center">Error al cargar los tamaños</div>';
        }
    }

    // Cargar tamaños para un item específico (múltiple) - MODIFICADA para filtrar por proyecto
    async function cargarTamanosItem(itemIndex, etiquetaId, proyectoId) {
        const container = document.getElementById(`tamanosContainerItem${itemIndex}`);
        const radioGroup = document.getElementById(`tamanosRadioGroupItem${itemIndex}`);
        
        if (radioGroup) radioGroup.innerHTML = '';
        if (container) container.classList.add('d-none');
        
        if (!etiquetaId) {
            return;
        }
        
        try {
            if (radioGroup) radioGroup.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin me-2"></i>Cargando tamaños...</div>';
            if (container) container.classList.remove('d-none');
            
            const formData = new FormData();
            formData.append('peticion', proyectoId ? 'consultar_tamanos_por_proyecto' : 'consultar_tamanos');
            formData.append('etiqueta_id', etiquetaId);
            if (proyectoId) {
                formData.append('proyecto_id', proyectoId);
            }
            formData.append('token', authToken);

            const response = await fetch('controllers/etiquetas.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            
            if (data.exito && data.tamanos && data.tamanos.length > 0) {
                if (radioGroup) {
                    radioGroup.innerHTML = '';
                    let hayTamanosDisponibles = false;
                    
                    data.tamanos.forEach((tamano, index) => {
                        // Solo mostrar tamaños con stock disponible
                        
                        if (tamano) {
                            hayTamanosDisponibles = true;
                            const radioId = `tamano-item${itemIndex}-${tamano.id || tamano.alto + '-' + tamano.ancho}`;
                            const badgeClass = obtenerClaseBadgeStock(tamano.stock_actual);
                            
                            const tamanoDiv = document.createElement('div');
                            tamanoDiv.className = 'tamano-option';
                            
                            tamanoDiv.innerHTML = `
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" 
                                        name="tamano_seleccionado_item${itemIndex}" 
                                        id="${radioId}"
                                        value='${JSON.stringify(tamano)}'
                                        ${index === 0 ? 'checked' : ''}
                                        onchange="actualizarStockInfoItem(${itemIndex})">
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
                            radioGroup.appendChild(tamanoDiv);
                        }
                    });
                    
                    if (!hayTamanosDisponibles) {
                        radioGroup.innerHTML = '<div class="alert alert-warning text-center">No hay tamaños con stock disponible para esta etiqueta</div>';
                    }
                }
                
                actualizarStockInfoItem(itemIndex);
                
            } else {
                const mensaje = proyectoId ? 
                    'No hay tamaños asignados a este proyecto para esta etiqueta' :
                    'No hay tamaños disponibles para esta etiqueta';
                if (radioGroup) radioGroup.innerHTML = `<div class="alert alert-warning text-center">${mensaje}</div>`;
            }
            
        } catch (error) {
            console.error('Error al cargar tamaños:', error);
            if (radioGroup) radioGroup.innerHTML = '<div class="alert alert-danger text-center">Error al cargar los tamaños</div>';
        }
    }

    // Actualizar información del tamaño seleccionado (individual)
    function actualizarInfoTamanoIndividual(radioElement) {
        if (!radioElement.checked) return;
        
        try {
            tamanoSeleccionado = JSON.parse(radioElement.value);
            actualizarInfoCantidadIndividual();
            
        } catch (error) {
            console.error('Error actualizando información del tamaño:', error);
        }
    }

    // Actualizar información de cantidad basada en el stock disponible (individual)
    function actualizarInfoCantidadIndividual() {
        const infoCantidad = document.getElementById('infoCantidadIndividual');
        const cantidadInput = document.getElementById('cantidadIndividual');
        
        if (tamanoSeleccionado && tamanoSeleccionado.stock_actual !== undefined) {
            const stockDisponible = tamanoSeleccionado.stock_actual;
            infoCantidad.textContent = `Stock disponible: ${stockDisponible} unidades`;
            infoCantidad.className = 'form-text ' + obtenerClaseTextoStock(stockDisponible);
            
            const proyectoId = document.getElementById('proyectoIdIndividual').value;
            if (proyectoId && cantidadAsignadaPorProyecto > 0) {
                const cantidadRestanteProyecto = cantidadAsignadaPorProyecto - cantidadEntregadaPorProyecto;
                const maximoPermitido = Math.min(stockDisponible, cantidadRestanteProyecto);
                infoCantidad.textContent += ` | Máximo por proyecto: ${maximoPermitido} unidades`;
                
                if (parseInt(cantidadInput.value) > maximoPermitido) {
                    cantidadInput.classList.add('is-invalid');
                    cantidadInput.setAttribute('max', maximoPermitido);
                } else {
                    cantidadInput.classList.remove('is-invalid');
                    cantidadInput.setAttribute('max', maximoPermitido);
                }
            } else {
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

    // Actualizar información de stock para un item (múltiple)
    function actualizarStockInfoItem(itemIndex) {
        const cantidadInput = document.querySelector(`.item-cantidad[data-item-index="${itemIndex}"]`);
        const stockInfo = document.getElementById(`stockInfoItem${itemIndex}`);
        const select = document.querySelector(`.item-etiqueta-select[data-item-index="${itemIndex}"]`);
        
        if (!select || !cantidadInput) return;
        
        const etiquetaId = select.value;
        if (!etiquetaId) {
            if (stockInfo) stockInfo.textContent = '';
            cantidadInput.removeAttribute('max');
            return;
        }
        
        const radioSeleccionado = document.querySelector(`input[name="tamano_seleccionado_item${itemIndex}"]:checked`);
        if (!radioSeleccionado) {
            if (stockInfo) stockInfo.textContent = 'Seleccione un tamaño';
            return;
        }
        
        try {
            const tamano = JSON.parse(radioSeleccionado.value);
            const stockDisponible = tamano.stock_actual || 0;
            
            // Obtener información del proyecto si existe
            const proyectoId = document.getElementById('proyectoIdMultiple').value;
            const option = select.options[select.selectedIndex];
            
            if (stockInfo) {
                if (proyectoId && option.dataset.cantidad_asignada) {
                    const cantidadAsignada = option.dataset.cantidad_asignada || 0;
                    const cantidadEntregada = option.dataset.cantidad_entregada || 0;
                    const cantidadRestanteProyecto = cantidadAsignada - cantidadEntregada;
                    const maximoPermitido = Math.min(stockDisponible, cantidadRestanteProyecto);
                    
                    stockInfo.innerHTML = `
                        <div class="small">
                            <div><strong>Stock disponible:</strong> ${stockDisponible} unidades</div>
                            <div><strong>Asignado al proyecto:</strong> ${cantidadAsignada} unidades</div>
                            <div><strong>Ya entregado:</strong> ${cantidadEntregada} unidades</div>
                            <div><strong>Restante por entregar:</strong> ${cantidadRestanteProyecto} unidades</div>
                            <div><strong>Máximo permitido:</strong> ${maximoPermitido} unidades</div>
                        </div>
                    `;
                    
                    // Validar contra el máximo permitido
                    if (parseInt(cantidadInput.value) > maximoPermitido) {
                        cantidadInput.classList.add('is-invalid');
                        cantidadInput.setAttribute('max', maximoPermitido);
                    } else {
                        cantidadInput.classList.remove('is-invalid');
                        cantidadInput.setAttribute('max', maximoPermitido);
                    }
                } else {
                    stockInfo.textContent = `Stock disponible: ${stockDisponible} unidades`;
                    stockInfo.className = 'form-text ' + obtenerClaseTextoStock(stockDisponible);
                    
                    if (parseInt(cantidadInput.value) > stockDisponible) {
                        cantidadInput.classList.add('is-invalid');
                        cantidadInput.setAttribute('max', stockDisponible);
                    } else {
                        cantidadInput.classList.remove('is-invalid');
                        cantidadInput.setAttribute('max', stockDisponible);
                    }
                }
            }
            
        } catch (error) {
            console.error('Error actualizando stock del item:', error);
        }
    }

    // Actualizar resumen de stock (individual)
    function actualizarResumenStockIndividual(etiqueta) {
        const stockSummary = document.getElementById('stockSummaryIndividual');
        
        if (etiqueta && stockSummary) {
            let html = `
                <div class="text-start">
                    <h6 class="mb-3">${etiqueta.nombre}</h6>
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-2">
                                <small class="text-muted">Categoría:</small>
                                <div class="fw-bold">${etiqueta.categoria_nombre || 'N/A'}</div>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted">Stock total:</small>
                                <div class="fw-bold">${etiqueta.stock_total || 0} unidades</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-2">
                                <small class="text-muted">Código:</small>
                                <div class="fw-bold">${etiqueta.codigo || 'N/A'}</div>
                            </div>
            `;
            
            if (tamanosActuales.length > 0) {
                html += `
                    <div class="mb-2">
                        <small class="text-muted">Tamaños disponibles:</small>
                        <div class="fw-bold">${tamanosActuales.length}</div>
                    </div>
                `;
            }
            
            html += `
                        </div>
                    </div>
                </div>
            `;
            
            stockSummary.innerHTML = html;
        }
    }

    // Actualizar resumen de salidas múltiples
    function actualizarResumenMultiple() {
        const items = document.querySelectorAll('.item-card');
        const totalItems = items.length;
        let totalUnidades = 0;
        let etiquetasUnicas = new Set();
        
        items.forEach(item => {
            const itemIndex = item.dataset.itemIndex;
            const cantidadInput = document.querySelector(`.item-cantidad[data-item-index="${itemIndex}"]`);
            const etiquetaSelect = document.querySelector(`.item-etiqueta-select[data-item-index="${itemIndex}"]`);
            
            if (cantidadInput && cantidadInput.value) {
                totalUnidades += parseInt(cantidadInput.value) || 0;
            }
            
            if (etiquetaSelect && etiquetaSelect.value) {
                etiquetasUnicas.add(etiquetaSelect.value);
            }
        });
        
        document.getElementById('totalItems').textContent = totalItems;
        document.getElementById('totalUnidades').textContent = totalUnidades;
        document.getElementById('totalEtiquetasUnicas').textContent = etiquetasUnicas.size;
        
        const listadoDiv = document.getElementById('listadoItemsResumen');
        if (listadoDiv) {
            if (totalItems > 0) {
                let listadoHTML = '<div class="list-group list-group-flush">';
                
                items.forEach(item => {
                    const itemIndex = item.dataset.itemIndex;
                    const cantidadInput = document.querySelector(`.item-cantidad[data-item-index="${itemIndex}"]`);
                    const etiquetaSelect = document.querySelector(`.item-etiqueta-select[data-item-index="${itemIndex}"]`);
                    const cantidad = cantidadInput ? cantidadInput.value : '0';
                    
                    if (etiquetaSelect && etiquetaSelect.value) {
                        const etiqueta = etiquetas.find(e => e.id == etiquetaSelect.value);
                        if (etiqueta) {
                            listadoHTML += `
                                <div class="list-group-item py-2 px-1">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <small class="text-muted">${etiqueta.nombre}</small>
                                        </div>
                                        <div>
                                            <span class="badge bg-primary">${cantidad} un.</span>
                                        </div>
                                    </div>
                                </div>
                            `;
                        }
                    }
                });
                
                listadoHTML += '</div>';
                listadoDiv.innerHTML = listadoHTML;
            } else {
                listadoDiv.innerHTML = '<small class="text-muted">Agregue items para ver el detalle aquí</small>';
            }
        }
    }

    // Agregar nuevo item (múltiple)
    function agregarItemMultiple() {
        itemCounter++;
        const itemsContainer = document.getElementById('itemsContainer');
        
        const nuevoItemHTML = `
            <div class="item-card mb-3" data-item-index="${itemCounter}">
                <div class="item-header bg-light p-2 rounded-top d-flex justify-content-between align-items-center">
                    <span class="fw-bold">Item #${itemCounter}</span>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="mostrarConfirmacionEliminarItem(${itemCounter})" data-item-count="${itemCounter}">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="item-body p-3 border border-top-0 rounded-bottom">
                    <!-- Selección de Etiqueta -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Etiqueta *</label>
                        <div class="input-group">
                            <select class="form-select item-etiqueta-select" name="etiquetaId[]" data-item-index="${itemCounter}" required onchange="actualizarInfoItem(${itemCounter})">
                                <option value="">Seleccionar etiqueta...</option>
                            </select>
                            <button class="btn btn-outline-primary" type="button" onclick="recargarEtiquetasItem(${itemCounter})" title="Recargar etiquetas">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Selección de Tamaño -->
                    <div class="tamanos-container-item mb-3 d-none" id="tamanosContainerItem${itemCounter}">
                        <label class="form-label fw-bold mb-2">Tamaño *</label>
                        <div class="tamanos-radio-group" id="tamanosRadioGroupItem${itemCounter}"></div>
                    </div>

                    <!-- Cantidad -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Cantidad *</label>
                        <div class="input-group">
                            <input type="number" class="form-control item-cantidad" name="cantidad[]" data-item-index="${itemCounter}" min="1" required placeholder="Ej: 50" oninput="actualizarResumenMultiple(); actualizarStockInfoItem(${itemCounter})">
                            <span class="input-group-text">unidades</span>
                        </div>
                        <div class="form-text stock-info" id="stockInfoItem${itemCounter}"></div>
                    </div>

                    <!-- Información del Item -->
                    <div class="alert alert-sm alert-secondary p-2 d-none" id="infoItem${itemCounter}">
                        <div class="row small">
                            <div class="col-6">
                                <strong>Etiqueta:</strong>
                                <div class="item-etiqueta-nombre"></div>
                            </div>
                            <div class="col-3">
                                <strong>Stock:</strong>
                                <div class="item-stock"></div>
                            </div>
                            <div class="col-3">
                                <strong>Foto:</strong>
                                <div class="item-tamano"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        itemsContainer.insertAdjacentHTML('beforeend', nuevoItemHTML);
        
        const select = document.querySelector(`.item-etiqueta-select[data-item-index="${itemCounter}"]`);
        if (select) {
            select.innerHTML = '<option value="">Seleccionar etiqueta...</option>';
            etiquetas.forEach(etiqueta => {
                if (etiqueta.activa) {
                    const option = document.createElement('option');
                    option.value = etiqueta.id;
                    option.dataset.cantidad_asignada = etiqueta.cantidad_asignada;
                    option.dataset.cantidad_entregada = etiqueta.cantidad_entregada;
                    option.textContent = etiqueta.nombre;
                    select.appendChild(option);
                }
            });
        }
        
        actualizarResumenMultiple();
    }

    // Mostrar confirmación para eliminar item
    function mostrarConfirmacionEliminarItem(itemIndex) {
        const items = document.querySelectorAll('.item-card');
        if (items.length <= 1) {
            mostrarMensaje('warning', 'Debe haber al menos un item en la lista');
            return;
        }
        
        document.getElementById('confirmDeleteBtn').onclick = function() {
            eliminarItem(itemIndex);
            const modal = bootstrap.Modal.getInstance(document.getElementById('confirmDeleteModal'));
            modal.hide();
        };
        
        const modal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
        modal.show();
    }

    // Eliminar item (múltiple)
    function eliminarItem(itemIndex) {
        const item = document.querySelector(`.item-card[data-item-index="${itemIndex}"]`);
        if (item) {
            item.remove();
            actualizarResumenMultiple();
            
            const items = document.querySelectorAll('.item-card');
            items.forEach((item, index) => {
                const newIndex = index + 1;
                item.dataset.itemIndex = newIndex;
                item.querySelector('.item-header .fw-bold').textContent = `Item #${newIndex}`;
                
                const select = item.querySelector('.item-etiqueta-select');
                if (select) {
                    select.dataset.itemIndex = newIndex;
                    select.name = 'etiquetaId[]';
                }
                
                const cantidad = item.querySelector('.item-cantidad');
                if (cantidad) {
                    cantidad.dataset.itemIndex = newIndex;
                    cantidad.name = 'cantidad[]';
                }
                
                const deleteBtn = item.querySelector('button[onclick^="mostrarConfirmacionEliminarItem"]');
                if (deleteBtn) {
                    deleteBtn.setAttribute('onclick', `mostrarConfirmacionEliminarItem(${newIndex})`);
                    deleteBtn.dataset.itemCount = newIndex;
                }
                
                ['tamanosContainerItem', 'tamanosRadioGroupItem', 'stockInfoItem', 'infoItem'].forEach(prefix => {
                    const oldId = `${prefix}${itemIndex}`;
                    const newId = `${prefix}${newIndex}`;
                    const element = document.getElementById(oldId);
                    if (element) {
                        element.id = newId;
                    }
                });
                
                const radios = item.querySelectorAll('input[type="radio"]');
                radios.forEach(radio => {
                    const oldId = radio.id;
                    const newId = oldId.replace(`item${itemIndex}`, `item${newIndex}`);
                    radio.id = newId;
                    radio.name = `tamano_seleccionado_item${newIndex}`;
                    radio.setAttribute('onchange', `actualizarStockInfoItem(${newIndex})`);
                    
                    const label = item.querySelector(`label[for="${oldId}"]`);
                    if (label) {
                        label.setAttribute('for', newId);
                    }
                });
            });
            
            itemCounter = items.length;
        }
    }

    // Funciones para captura de foto (individual)
    async function startCameraIndividual() {
        try {
            stopCameraIndividual();
            
            const video = document.getElementById('videoPreviewIndividual');
            const placeholder = document.getElementById('photoPlaceholderIndividual');
            const controls = document.getElementById('photoControlsIndividual');
            const startBtn = document.getElementById('startCameraBtnIndividual');
            const stopBtn = document.getElementById('stopCameraBtnIndividual');
            
            mediaStream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: 'environment',
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

    function stopCameraIndividual() {
        const video = document.getElementById('videoPreviewIndividual');
        const placeholder = document.getElementById('photoPlaceholderIndividual');
        const controls = document.getElementById('photoControlsIndividual');
        const startBtn = document.getElementById('startCameraBtnIndividual');
        const stopBtn = document.getElementById('stopCameraBtnIndividual');
        
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

    function capturePhotoIndividual() {
        const video = document.getElementById('videoPreviewIndividual');
        const canvas = document.createElement('canvas');
        const photoPreview = document.getElementById('photoPreviewIndividual');
        
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        
        const context = canvas.getContext('2d');
        context.drawImage(video, 0, 0, canvas.width, canvas.height);
        
        fotoCapturada = canvas.toDataURL('image/jpeg', 0.8);
        
        photoPreview.src = fotoCapturada;
        photoPreview.classList.remove('d-none');
        video.classList.add('d-none');
        
        document.getElementById('photoControlsIndividual').classList.add('d-none');
        
        stopCameraIndividual();
        
        comprimirYPrepararFotoIndividual(fotoCapturada);
    }

    function retakePhotoIndividual() {
        const photoPreview = document.getElementById('photoPreviewIndividual');
        photoPreview.classList.add('d-none');
        fotoCapturada = null;
        document.getElementById('fotoBase64Individual').value = '';
        
        startCameraIndividual();
    }

    function uploadFileIndividual() {
        document.getElementById('photoFileIndividual').click();
    }

    function handleFileSelectIndividual(event) {
        const file = event.target.files[0];
        if (!file) return;
        
        if (!file.type.match('image.*')) {
            mostrarMensaje('error', 'Por favor selecciona un archivo de imagen');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            fotoCapturada = e.target.result;
            const photoPreview = document.getElementById('photoPreviewIndividual');
            photoPreview.src = fotoCapturada;
            photoPreview.classList.remove('d-none');
            document.getElementById('photoPlaceholderIndividual').classList.add('d-none');
            
            comprimirYPrepararFotoIndividual(fotoCapturada);
        };
        reader.readAsDataURL(file);
    }

    // Funciones para captura de foto (múltiple)
    async function startCameraMultiple() {
        try {
            stopCameraMultiple();
            
            const video = document.createElement('video');
            video.id = 'videoPreviewMultiple';
            video.className = 'video-preview-multiple';
            video.autoplay = true;
            
            const photoContainer = document.getElementById('photoContainerMultiple');
            const placeholder = document.getElementById('photoPlaceholderMultiple');
            
            mediaStreamMultiple = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: 'environment',
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                },
                audio: false
            });
            
            video.srcObject = mediaStreamMultiple;
            placeholder.style.display = 'none';
            photoContainer.appendChild(video);
            
            const startBtn = document.getElementById('startCameraBtnMultiple');
            const stopBtn = document.getElementById('stopCameraBtnMultiple');
            startBtn.style.display = 'none';
            stopBtn.style.display = 'block';
            
            await video.play();
            
            const captureBtn = document.createElement('button');
            captureBtn.type = 'button';
            captureBtn.className = 'btn btn-danger btn-sm mt-2';
            captureBtn.innerHTML = '<i class="fas fa-camera me-1"></i>Tomar Foto';
            captureBtn.onclick = capturePhotoMultiple;
            
            photoContainer.appendChild(captureBtn);
            
        } catch (error) {
            console.error('Error al acceder a la cámara:', error);
            mostrarMensaje('error', 'No se pudo acceder a la cámara. Asegúrate de conceder los permisos necesarios.');
        }
    }

    function stopCameraMultiple() {
        const video = document.getElementById('videoPreviewMultiple');
        const placeholder = document.getElementById('photoPlaceholderMultiple');
        const startBtn = document.getElementById('startCameraBtnMultiple');
        const stopBtn = document.getElementById('stopCameraBtnMultiple');
        const removeBtn = document.getElementById('removePhotoBtnMultiple');
        
        if (mediaStreamMultiple) {
            mediaStreamMultiple.getTracks().forEach(track => track.stop());
            mediaStreamMultiple = null;
        }
        
        if (video) {
            video.remove();
        }
        
        placeholder.style.display = 'flex';
        startBtn.style.display = 'block';
        stopBtn.style.display = 'none';
        removeBtn.style.display = 'none';
        
        const captureBtn = document.querySelector('#photoContainerMultiple button[onclick="capturePhotoMultiple"]');
        if (captureBtn) {
            captureBtn.remove();
        }
    }

    function capturePhotoMultiple() {
        const video = document.getElementById('videoPreviewMultiple');
        const canvas = document.createElement('canvas');
        const photoPreview = document.getElementById('photoPreviewMultiple');
        const placeholder = document.getElementById('photoPlaceholderMultiple');
        
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        
        const context = canvas.getContext('2d');
        context.drawImage(video, 0, 0, canvas.width, canvas.height);
        
        fotoCapturadaMultiple = canvas.toDataURL('image/jpeg', 0.8);
        
        photoPreview.src = fotoCapturadaMultiple;
        photoPreview.classList.remove('d-none');
        placeholder.style.display = 'none';
        
        video.remove();
        const captureBtn = document.querySelector('#photoContainerMultiple button[onclick="capturePhotoMultiple"]');
        if (captureBtn) captureBtn.remove();
        
        document.getElementById('stopCameraBtnMultiple').style.display = 'none';
        document.getElementById('removePhotoBtnMultiple').style.display = 'block';
        
        comprimirYPrepararFotoMultiple(fotoCapturadaMultiple);
    }

    function removePhotoMultiple() {
        const photoPreview = document.getElementById('photoPreviewMultiple');
        const placeholder = document.getElementById('photoPlaceholderMultiple');
        const removeBtn = document.getElementById('removePhotoBtnMultiple');
        
        photoPreview.classList.add('d-none');
        placeholder.style.display = 'flex';
        removeBtn.style.display = 'none';
        fotoCapturadaMultiple = null;
        document.getElementById('fotoBase64Multiple').value = '';
        
        stopCameraMultiple();
    }

    function uploadFileMultiple() {
        document.getElementById('photoFileMultiple').click();
    }

    function handleFileSelectMultiple(event) {
        const file = event.target.files[0];
        if (!file) return;
        
        if (!file.type.match('image.*')) {
            mostrarMensaje('error', 'Por favor selecciona un archivo de imagen');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            fotoCapturadaMultiple = e.target.result;
            const photoPreview = document.getElementById('photoPreviewMultiple');
            const placeholder = document.getElementById('photoPlaceholderMultiple');
            const removeBtn = document.getElementById('removePhotoBtnMultiple');
            
            photoPreview.src = fotoCapturadaMultiple;
            photoPreview.classList.remove('d-none');
            placeholder.style.display = 'none';
            removeBtn.style.display = 'block';
            
            comprimirYPrepararFotoMultiple(fotoCapturadaMultiple);
        };
        reader.readAsDataURL(file);
    }

    // Comprimir y preparar la foto (individual)
    async function comprimirYPrepararFotoIndividual(base64Image) {
        try {
            const progressContainer = document.getElementById('progressContainerIndividual');
            const progressBar = document.getElementById('uploadProgressIndividual');
            const progressText = document.getElementById('progressTextIndividual');
            
            progressContainer.style.display = 'block';
            progressBar.style.width = '30%';
            progressText.textContent = 'Comprimiendo imagen...';
            
            const img = new Image();
            img.src = base64Image;
            
            img.onload = function() {
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
                
                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);
                
                progressBar.style.width = '60%';
                progressText.textContent = 'Optimizando calidad...';
                
                setTimeout(() => {
                    const compressedBase64 = canvas.toDataURL('image/jpeg', 0.7);
                    
                    progressBar.style.width = '90%';
                    progressText.textContent = 'Preparando para subir...';
                    
                    const base64Data = compressedBase64.replace(/^data:image\/jpeg;base64,/, '');
                    document.getElementById('fotoBase64Individual').value = base64Data;
                    
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
            document.getElementById('progressContainerIndividual').style.display = 'none';
        }
    }

    // Comprimir y preparar la foto (múltiple)
    async function comprimirYPrepararFotoMultiple(base64Image) {
        try {
            const img = new Image();
            img.src = base64Image;
            
            img.onload = function() {
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
                
                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);
                
                const compressedBase64 = canvas.toDataURL('image/jpeg', 0.7);
                const base64Data = compressedBase64.replace(/^data:image\/jpeg;base64,/, '');
                document.getElementById('fotoBase64Multiple').value = base64Data;
                
                mostrarMensaje('success', 'Foto comprimida y lista para subir');
            };
            
        } catch (error) {
            console.error('Error comprimiendo imagen:', error);
            mostrarMensaje('error', 'Error al procesar la imagen');
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

    // Limpiar formulario individual
    function limpiarFormularioIndividual() {
        document.getElementById('formSalidaIndividual').reset();
        document.getElementById('infoProyectoIndividual').classList.add('d-none');
        document.getElementById('infoEtiquetaIndividual').style.display = 'none';
        document.getElementById('tamanosContainerIndividual').classList.add('d-none');
        document.getElementById('img_etiqueta_individual').classList.add('d-none');
        document.getElementById('sinImagenIndividual').classList.remove('d-none');
        document.getElementById('infoCantidadIndividual').textContent = '';
        document.getElementById('cantidadInfoCardIndividual').classList.add('d-none');
        document.getElementById('photoPreviewIndividual').classList.add('d-none');
        document.getElementById('photoPlaceholderIndividual').classList.remove('d-none');
        document.getElementById('fotoBase64Individual').value = '';
        document.getElementById('stockSummaryIndividual').innerHTML = `
            <i class="fas fa-box-open fa-3x mb-3"></i>
            <p>Seleccione una etiqueta para ver el resumen de stock</p>
        `;
        fotoCapturada = null;
        tamanoSeleccionado = null;
        tamanosActuales = [];
        cantidadAsignadaPorProyecto = 0;
        cantidadEntregadaPorProyecto = 0;
        
        stopCameraIndividual();
    }

    // Limpiar solo el proyecto (individual)
    function limpiarProyectoIndividual() {
        document.getElementById('proyectoIdIndividual').value = '';
        document.getElementById('infoProyectoIndividual').classList.add('d-none');
        document.getElementById('cantidadInfoCardIndividual').classList.add('d-none');
        cargarTodasLasEtiquetas('individual');
        
        // Si hay etiqueta seleccionada, recargar tamaños sin proyecto
        const etiquetaId = document.getElementById('etiquetaIdIndividual').value;
        if (etiquetaId) {
            cargarTamanosEtiquetaIndividual(etiquetaId, null);
        }
    }

    // Limpiar solo el proyecto (múltiple)
    function limpiarProyectoMultiple() {
        document.getElementById('proyectoIdMultiple').value = '';
        document.getElementById('infoProyectoMultiple').classList.add('d-none');
        document.getElementById('infoProyectoResumen').innerHTML = `
            <i class="fas fa-folder-open fa-3x mb-3"></i>
            <p>Seleccione un proyecto para ver la información</p>
        `;
        cargarTodasLasEtiquetas('multiple');
        
        // Actualizar tamaños para todos los items sin proyecto
        const items = document.querySelectorAll('.item-card');
        items.forEach(item => {
            const itemIndex = item.dataset.itemIndex;
            const select = document.querySelector(`.item-etiqueta-select[data-item-index="${itemIndex}"]`);
            if (select && select.value) {
                cargarTamanosItem(itemIndex, select.value, null);
            }
        });
    }

    // Limpiar formulario múltiple
    function limpiarFormularioMultiple() {
        document.getElementById('formSalidasMultiples').reset();
        document.getElementById('infoProyectoMultiple').classList.add('d-none');
        document.getElementById('infoProyectoResumen').innerHTML = `
            <i class="fas fa-folder-open fa-3x mb-3"></i>
            <p>Seleccione un proyecto para ver la información</p>
        `;
        
        const itemsContainer = document.getElementById('itemsContainer');
        const items = document.querySelectorAll('.item-card');
        
        for (let i = items.length - 1; i > 0; i--) {
            items[i].remove();
        }
        
        const firstItem = document.querySelector('.item-card');
        if (firstItem) {
            firstItem.dataset.itemIndex = '1';
            firstItem.querySelector('.item-header .fw-bold').textContent = 'Item #1';
            
            const select = firstItem.querySelector('.item-etiqueta-select');
            if (select) {
                select.value = '';
                select.dataset.itemIndex = '1';
            }
            
            const cantidad = firstItem.querySelector('.item-cantidad');
            if (cantidad) {
                cantidad.value = '';
                cantidad.dataset.itemIndex = '1';
            }
            
            const infoDiv = document.getElementById('infoItem1');
            if (infoDiv) infoDiv.classList.add('d-none');
            
            const tamanosDiv = document.getElementById('tamanosContainerItem1');
            if (tamanosDiv) tamanosDiv.classList.add('d-none');
        }
        
        itemCounter = 1;
        
        removePhotoMultiple();
        
        actualizarResumenMultiple();
    }

    // Registrar salida individual
    async function registrarSalidaIndividual(event) {
        event.preventDefault();
        
        try {
            const proyectoId = document.getElementById('proyectoIdIndividual').value;
            const etiquetaId = document.getElementById('etiquetaIdIndividual').value;
            const cantidad = document.getElementById('cantidadIndividual').value;
            const motivo = document.getElementById('motivoIndividual').value;
            const observaciones = document.getElementById('observacionesIndividual').value;
            const referencia = document.getElementById('referenciaIndividual').value;
            const usuario_id = userData.id;
            const fotoBase64 = document.getElementById('fotoBase64Individual').value;
            
            const tamano = tamanoSeleccionado;
            if (!tamano) {
                mostrarMensaje('error', 'Por favor seleccione un tamaño');
                return;
            }

            const alto = tamano.alto;
            const ancho = tamano.ancho;
            const tamano_id = tamano.id_tamano;

            if (!etiquetaId || !cantidad || !motivo) {
                mostrarMensaje('error', 'Por favor complete todos los campos requeridos');
                return;
            }

            if (parseInt(cantidad) > tamano.stock_actual) {
                mostrarMensaje('error', `La cantidad excede el stock disponible (${tamano.stock_actual} unidades)`);
                return;
            }

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
                
                if (proyectoId) {
                    cantidadEntregadaPorProyecto += parseInt(cantidad);
                    mostrarInfoCantidadesIndividual();
                }
                
                limpiarFormularioIndividual();
                
                if (proyectoId) {
                    await cargarEtiquetasPorProyecto(proyectoId, 'individual');
                } else {
                    await cargarTodasLasEtiquetas('individual');
                }
            } else {
                throw new Error(result.msj);
            }
        } catch (error) {
            console.error('Error registrando salida individual:', error);
            mostrarMensaje('error', error.message || 'Error al registrar la salida');
        }
    }

    // Registrar salidas múltiples
    async function registrarSalidasMultiples(event) {
        event.preventDefault();
        
        try {
            const proyectoId = document.getElementById('proyectoIdMultiple').value;
            const motivo = document.getElementById('motivoMultiple').value;
            const observaciones = document.getElementById('observacionesMultiple').value;
            const referencia = document.getElementById('referenciaMultiple').value;
            const usuario_id = userData.id;
            const fotoBase64 = document.getElementById('fotoBase64Multiple').value;
            
            const items = document.querySelectorAll('.item-card');
            if (items.length === 0) {
                mostrarMensaje('error', 'Debe agregar al menos un item');
                return;
            }
            
            const itemsData = [];
            let errores = [];
            
            items.forEach((item, index) => {
                const itemIndex = item.dataset.itemIndex;
                const select = document.querySelector(`.item-etiqueta-select[data-item-index="${itemIndex}"]`);
                const cantidadInput = document.querySelector(`.item-cantidad[data-item-index="${itemIndex}"]`);
                const radioSeleccionado = document.querySelector(`input[name="tamano_seleccionado_item${itemIndex}"]:checked`);
                
                if (!select || !select.value) {
                    errores.push(`Item #${itemIndex}: Seleccione una etiqueta`);
                    return;
                }
                
                if (!cantidadInput || !cantidadInput.value) {
                    errores.push(`Item #${itemIndex}: Ingrese una cantidad`);
                    return;
                }
                
                if (!radioSeleccionado) {
                    errores.push(`Item #${itemIndex}: Seleccione un tamaño`);
                    return;
                }
                
                try {
                    const tamano = JSON.parse(radioSeleccionado.value);
                    const cantidad = parseInt(cantidadInput.value);
                    
                    if (cantidad > tamano.stock_actual) {
                        errores.push(`Item #${itemIndex}: La cantidad excede el stock disponible (${tamano.stock_actual} unidades)`);
                        return;
                    }
                    
                    // Validar contra el proyecto si existe
                    if (proyectoId) {
                        const option = select.options[select.selectedIndex];
                        const cantidadAsignada = option.dataset.cantidad_asignada || 0;
                        const cantidadEntregada = option.dataset.cantidad_entregada || 0;
                        const cantidadRestanteProyecto = cantidadAsignada - cantidadEntregada;
                        
                        if (cantidad > cantidadRestanteProyecto) {
                            errores.push(`Item #${itemIndex}: La cantidad excede lo asignado al proyecto (${cantidadRestanteProyecto} unidades restantes)`);
                            return;
                        }
                    }
                    
                    itemsData.push({
                        etiqueta_id: select.value,
                        cantidad: cantidad,
                        alto: tamano.alto,
                        ancho: tamano.ancho,
                        tamano_id: tamano.id_tamano
                    });
                    
                } catch (error) {
                    errores.push(`Item #${itemIndex}: Error procesando datos del tamaño`);
                }
            });
            
            if (errores.length > 0) {
                mostrarMensaje('error', errores.join('<br>'));
                return;
            }
            
            if (!motivo) {
                mostrarMensaje('error', 'Por favor seleccione un motivo para las salidas');
                return;
            }
            
            const confirmacion = await Swal.fire({
                title: '¿Registrar salidas múltiples?',
                html: `Se registrarán ${itemsData.length} items con un total de ${itemsData.reduce((sum, item) => sum + item.cantidad, 0)} unidades`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, registrar',
                cancelButtonText: 'Cancelar'
            });
            
            if (!confirmacion.isConfirmed) return;
            
            const formData = new FormData();
            formData.append('peticion', 'registrar_salidas_multiples');
            formData.append('token', authToken);
            formData.append('proyecto_id', proyectoId || '');
            formData.append('motivo', motivo);
            formData.append('referencia', referencia);
            formData.append('observaciones', observaciones);
            formData.append('usuario_id', usuario_id);
            formData.append('foto_base64', fotoBase64);
            
            formData.append('items', JSON.stringify(itemsData));
            
            const response = await fetch('controllers/movimientos.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.exito) {
                await mostrarMensaje('success', `${itemsData.length} salidas registradas exitosamente`);
                
                limpiarFormularioMultiple();
                
                if (proyectoId) {
                    await cargarEtiquetasPorProyecto(proyectoId, 'multiple');
                } else {
                    await cargarTodasLasEtiquetas('multiple');
                }
            } else {
                throw new Error(result.msj);
            }
            
        } catch (error) {
            console.error('Error registrando salidas múltiples:', error);
            mostrarMensaje('error', error.message || 'Error al registrar las salidas');
        }
    }

    function mostrarMensaje(tipo, mensaje) {
        return Swal.fire({
            icon: tipo,
            title: mensaje,
            timer: 3000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
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

        if (!tieneAcceso) {
            const formData = new FormData();
            formData.append('token', authToken);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                window.location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                window.location.href = 'login.php';
            });
        }
        else {
            console.log('Acceso confirmado');
        }
        
        userData = JSON.parse(storedUser);
        updateUserInfo();
        updateCurrentTime();
        
        await cargarProyectos();
        await cargarTodasLasEtiquetas('individual');
        await cargarTodasLasEtiquetas('multiple');
        
        document.getElementById('logoutBtn').addEventListener('click', logout);
        document.getElementById('dropdownLogoutBtn').addEventListener('click', logout);
        document.getElementById('formSalidaIndividual').addEventListener('submit', registrarSalidaIndividual);
        document.getElementById('formSalidasMultiples').addEventListener('submit', registrarSalidasMultiples);
        
        document.getElementById('proyectoIdIndividual').addEventListener('change', function() {
            mostrarInfoProyectoIndividual(this.value);
        });
        
        document.getElementById('etiquetaIdIndividual').addEventListener('change', function() {
            const select = this;
            const option = select.options[select.selectedIndex];
            const entregada = option.dataset.cantidad_entregada;
            const asignada = option.dataset.cantidad_asignada;
            mostrarInfoEtiquetaIndividual(this.value, entregada, asignada);
        });
        
        document.getElementById('cantidadIndividual').addEventListener('input', actualizarInfoCantidadIndividual);
        
        document.getElementById('proyectoIdMultiple').addEventListener('change', function() {
            mostrarInfoProyectoMultiple(this.value);
        });
        
        actualizarResumenMultiple();
        
        window.addEventListener('beforeunload', function() {
            stopCameraIndividual();
            stopCameraMultiple();
        });
        
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
        stopCameraIndividual();
        stopCameraMultiple();
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user');
        window.location.href = 'cerrar_sesion.php';
    }
</script>
</body>
</html>