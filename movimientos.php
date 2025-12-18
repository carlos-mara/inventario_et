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
    <title>Movimientos - Sistema Inventarios</title>
    
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
        .movimiento-entrada {
            border-left: 4px solid #28a745;
        }
        .movimiento-salida {
            border-left: 4px solid #dc3545;
        }
        .stat-card {
            transition: transform 0.3s;
            border: none;
            border-radius: 15px;
        }
        .stat-card:hover {
            transform: translateY(-3px);
        }
        .foto-evidencia {
            max-width: 100%;
            max-height: 400px;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .sin-foto {
            background-color: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <?php if (!$tieneAcceso): ?>
    <!-- Pantalla de carga mientras se verifica -->
    <div class="loading-screen" id="loadingScreen">
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-3">Verificando acceso...</p>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($tieneAcceso): ?>
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
                            <i class="fas fa-exchange-alt me-2"></i>Movimientos de Inventario
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
                        <div class="col-xl-2 col-md-4 col-6 mb-3">
                            <div class="stat-card card bg-primary text-white text-center">
                                <div class="card-body py-3">
                                    <h5 class="card-title mb-1" id="totalMovimientos">0</h5>
                                    <small>Total Movimientos</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 col-6 mb-3">
                            <div class="stat-card card bg-success text-white text-center">
                                <div class="card-body py-3">
                                    <h5 class="card-title mb-1" id="entradasHoy">0</h5>
                                    <small>Entradas Hoy</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 col-6 mb-3">
                            <div class="stat-card card bg-danger text-white text-center">
                                <div class="card-body py-3">
                                    <h5 class="card-title mb-1" id="salidasHoy">0</h5>
                                    <small>Salidas Hoy</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 col-6 mb-3">
                            <div class="stat-card card bg-info text-white text-center">
                                <div class="card-body py-3">
                                    <h5 class="card-title mb-1" id="entradasMes">0</h5>
                                    <small>Entradas Mes</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 col-6 mb-3">
                            <div class="stat-card card bg-warning text-white text-center">
                                <div class="card-body py-3">
                                    <h5 class="card-title mb-1" id="salidasMes">0</h5>
                                    <small>Salidas Mes</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 col-6 mb-3">
                            <div class="stat-card card bg-secondary text-white text-center">
                                <div class="card-body py-3">
                                    <h5 class="card-title mb-1" id="valorMovido">$0</h5>
                                    <small>Valor Movido</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Barra de Acciones y Filtros -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row g-3 align-items-end">
                                        <div class="col-md-3">
                                            <label class="form-label">Tipo de Movimiento</label>
                                            <select class="form-select" id="tipoFilter">
                                                <option value="">Todos los tipos</option>
                                                <option value="entrada">Entradas</option>
                                                <option value="salida">Salidas</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Fecha Desde</label>
                                            <input type="date" class="form-control" id="fechaDesde">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Fecha Hasta</label>
                                            <input type="date" class="form-control" id="fechaHasta">
                                        </div>
                                        <div class="col-md-3">
                                            <div class="d-grid gap-2 d-md-flex">
                                                <button class="btn btn-primary me-2" id="btnFiltrar">
                                                    <i class="fas fa-filter me-1"></i>Filtrar
                                                </button>
                                                <button class="btn btn-outline-secondary" id="btnReset">
                                                    <i class="fas fa-redo me-1"></i>Limpiar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Botones de Acción Rápida -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex gap-2 flex-wrap">
                                        <a href="registrar_entradas.php" class="btn btn-success" data-mdb-toggle="modal" data-mdb-target="#modalEntrada">
                                            <i class="fas fa-arrow-down me-2"></i>Registrar Entrada
                                        </a>
                                        <a href="registrar_salidas.php" class="btn btn-danger" data-mdb-toggle="modal" data-mdb-target="#modalSalida">
                                            <i class="fas fa-arrow-up me-2"></i>Registrar Salida
                                        </a>
                                        <button class="btn btn-info" onclick="generarReporte()">
                                            <i class="fas fa-file-pdf me-2"></i>Generar Reporte
                                        </button>
                                        <button class="btn btn-warning" onclick="exportarExcel()">
                                            <i class="fas fa-file-excel me-2"></i>Exportar Excel
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de Movimientos -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-history me-2"></i>Historial de Movimientos
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="tablaMovimientos">
                                            <thead>
                                                <tr>
                                                    <th>Fecha</th>
                                                    <th>Etiqueta</th>
                                                    <th>Tipo</th>
                                                    <th>Cantidad</th>
                                                    <th>Stock Anterior</th>
                                                    <th>Stock Nuevo</th>
                                                    <th>Usuario</th>
                                                    <th>Motivo</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody id="movimientosBody">
                                                <!-- Los movimientos se cargarán aquí -->
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
        
    <!-- MODAL MOVIMIENTO -->
    <div class="modal fade" id="modalMovimientoDetalle" tabindex="-1" aria-labelledby="modalMovimientoDetalleLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalMovimientoDetalleLabel">Detalle del Movimiento</h5>
                    <button type="button" class="btn-close" data-mdb-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="detalleMovimientoBody">
                    <!-- Detalles del movimiento se cargarán aquí -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MDBootstrap JS -->
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/9.2.0/mdb.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <?php endif; ?>
    <script>
        let userData = null;
        let authToken = null;
        let movimientos = [];
        let etiquetas = [];

        // Cargar movimientos
        async function cargarMovimientos() {
            try {
                const formData = new FormData();
                formData.append('peticion', 'historial');
                formData.append('token', authToken);

                const response = await fetch('controllers/movimientos.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.exito) {
                    movimientos = result.data;
                    mostrarMovimientos();
                    actualizarEstadisticas();
                } else {
                    throw new Error(result.msj);
                }
            } catch (error) {
                console.error('Error cargando movimientos:', error);
                mostrarMensaje('error', 'Error al cargar los movimientos');
            }
        }

        // Cargar etiquetas para los selects
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
                    llenarSelectsEtiquetas();
                }
            } catch (error) {
                console.error('Error cargando etiquetas:', error);
            }
        }

        function llenarSelectsEtiquetas() {
            const selectEntrada = document.getElementById('entradaEtiquetaId');
            const selectSalida = document.getElementById('salidaEtiquetaId');
            
            selectEntrada.innerHTML = '<option value="">Seleccionar etiqueta...</option>';
            selectSalida.innerHTML = '<option value="">Seleccionar etiqueta...</option>';
            
            etiquetas.forEach(etiqueta => {
                if (etiqueta.activo) {
                    const option = `<option value="${etiqueta.id}">${etiqueta.nombre} (Stock: ${etiqueta.stock_actual})</option>`;
                    selectEntrada.innerHTML += option;
                    selectSalida.innerHTML += option;
                }
            });
        }

        // Mostrar movimientos en la tabla
        function mostrarMovimientos() {
            const tbody = document.getElementById('movimientosBody');
            tbody.innerHTML = '';

            movimientos.forEach(movimiento => {
                const tr = document.createElement('tr');
                tr.className = movimiento.tipo === 'entrada' ? 'movimiento-entrada' : 'movimiento-salida';
                
                const fecha = new Date(movimiento.fecha_movimiento).toLocaleString('es-ES');
                const tipoBadge = movimiento.tipo === 'entrada' ? 
                    '<span class="badge bg-success">Entrada</span>' : 
                    '<span class="badge bg-danger">Salida</span>';
                
                tr.innerHTML = `
                    <td>${fecha}</td>
                    <td><strong>${movimiento.etiqueta_nombre}</strong></td>
                    <td>${tipoBadge}</td>
                    <td>
                        <span class="fw-bold ${movimiento.tipo === 'entrada' ? 'text-success' : 'text-danger'}">
                            ${movimiento.tipo === 'entrada' ? '+' : '-'}${movimiento.cantidad}
                        </span>
                    </td>
                    <td>${movimiento.cantidad_anterior}</td>
                    <td>${movimiento.cantidad_nueva}</td>
                    <td>${movimiento.usuario_nombre}</td>
                    <td>${movimiento.motivo || 'N/A'}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-info" onclick="verDetalleMovimiento(${movimiento.id})" title="Ver detalles">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="revertirMovimiento(${movimiento.id})" title="Revertir">
                            <i class="fas fa-undo"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        function mostrarMensaje(tipo, mensaje) {
            Swal.fire({
                icon: tipo,
                title: mensaje,
                timer: 2000,
                showConfirmButton: false
            });
        }

        // Registrar entrada
        async function registrarEntrada(event) {
            event.preventDefault();
            
            try {
                const formData = new FormData();
                formData.append('peticion', 'registrar');
                formData.append('token', authToken);
                formData.append('tipo', 'entrada');
                formData.append('etiqueta_id', document.getElementById('entradaEtiquetaId').value);
                formData.append('cantidad', document.getElementById('entradaCantidad').value);
                formData.append('motivo', document.getElementById('entradaMotivo').value);
                formData.append('referencia', document.getElementById('entradaReferencia').value);

                const response = await fetch('controllers/movimientos.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.exito) {
                    mostrarMensaje('success', 'Entrada registrada exitosamente');
                    document.getElementById('formEntrada').reset();
                    const modal = mdb.Modal.getInstance(document.getElementById('modalEntrada'));
                    modal.hide();
                    await cargarMovimientos();
                } else {
                    throw new Error(result.msj);
                }
            } catch (error) {
                console.error('Error registrando entrada:', error);
                mostrarMensaje('error', error.message || 'Error al registrar la entrada');
            }
        }

        // Registrar salida
        async function registrarSalida(event) {
            event.preventDefault();
            
            try {
                const etiquetaId = document.getElementById('salidaEtiquetaId').value;
                const cantidad = parseInt(document.getElementById('salidaCantidad').value);
                const etiqueta = etiquetas.find(e => e.id == etiquetaId);
                
                if (etiqueta && cantidad > etiqueta.stock_actual) {
                    mostrarMensaje('error', `Stock insuficiente. Disponible: ${etiqueta.stock_actual} unidades`);
                    return;
                }

                const formData = new FormData();
                formData.append('peticion', 'registrar');
                formData.append('token', authToken);
                formData.append('tipo', 'salida');
                formData.append('etiqueta_id', etiquetaId);
                formData.append('cantidad', cantidad);
                formData.append('motivo', document.getElementById('salidaMotivo').value);
                formData.append('referencia', document.getElementById('salidaReferencia').value);

                const response = await fetch('controllers/movimientos.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.exito) {
                    mostrarMensaje('success', 'Salida registrada exitosamente');
                    document.getElementById('formSalida').reset();
                    const modal = mdb.Modal.getInstance(document.getElementById('modalSalida'));
                    modal.hide();
                    await cargarMovimientos();
                } else {
                    throw new Error(result.msj);
                }
            } catch (error) {
                console.error('Error registrando salida:', error);
                mostrarMensaje('error', error.message || 'Error al registrar la salida');
            }
        }

        async function verDetalleMovimiento(movimientoId) {
            const movimiento = movimientos.find(m => m.id === movimientoId);
            if (!movimiento) return;

            // Verificar si tiene foto_evidencia
            const tieneFoto = movimiento.foto_evidencia && movimiento.foto_evidencia.trim() !== '';
            const rutaFoto = tieneFoto ? movimiento.foto_evidencia : '';


            const detalleBody = document.getElementById('detalleMovimientoBody');
            
            let contenidoFoto = '';
            if (tieneFoto) {
                contenidoFoto = `
                    <div class="row mt-4">
                        <div class="col-12">
                            <h6 class="mb-3"><i class="fas fa-camera me-2"></i>Evidencia Fotográfica</h6>
                            <div class="text-center">
                                <img src="uploads/${rutaFoto}" alt="Evidencia del movimiento" class="foto-evidencia img-fluid">
                                <div class="mt-2">
                                    <a href="uploads/${rutaFoto}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-external-link-alt me-1"></i>Abrir en nueva pestaña
                                    </a>
                                    <button onclick="descargarFoto('${rutaFoto}')" class="btn btn-sm btn-outline-success">
                                        <i class="fas fa-download me-1"></i>Descargar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                contenidoFoto = `
                    <div class="row mt-4">
                        <div class="col-12">
                            <h6 class="mb-3"><i class="fas fa-camera me-2"></i>Evidencia Fotográfica</h6>
                            <div class="sin-foto">
                                <i class="fas fa-camera fa-3x mb-3"></i>
                                <p class="mb-0">Este movimiento no tiene evidencia fotográfica</p>
                            </div>
                        </div>
                    </div>
                `;
            }

            detalleBody.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <ul class="list-group">
                            <li class="list-group-item"><strong>Fecha:</strong> ${new Date(movimiento.fecha_movimiento).toLocaleString('es-ES')}</li>
                            <li class="list-group-item"><strong>Etiqueta:</strong> ${movimiento.etiqueta_nombre}</li>
                            <li class="list-group-item"><strong>Tipo:</strong> ${movimiento.tipo}</li>
                            <li class="list-group-item"><strong>Cantidad:</strong> ${movimiento.cantidad}</li>
                            <li class="list-group-item"><strong>Stock Anterior:</strong> ${movimiento.cantidad_anterior}</li>
                            <li class="list-group-item"><strong>Stock Nuevo:</strong> ${movimiento.cantidad_nueva}</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="list-group">
                            <li class="list-group-item"><strong>Usuario:</strong> ${movimiento.usuario_nombre}</li>
                            <li class="list-group-item"><strong>Motivo:</strong> ${movimiento.motivo || 'N/A'}</li>
                            <li class="list-group-item"><strong>Referencia:</strong> ${movimiento.referencia || 'N/A'}</li>
                            <li class="list-group-item"><strong>Observaciones:</strong> ${movimiento.observaciones || 'N/A'}</li>
                            <li class="list-group-item ${tieneFoto ? 'text-success' : 'text-muted'}">
                                <strong>Evidencia:</strong> ${tieneFoto ? 'Disponible' : 'No disponible'}
                            </li>
                        </ul>
                    </div>
                </div>
                ${contenidoFoto}
            `;
            
            const modal = new mdb.Modal(document.getElementById('modalMovimientoDetalle'));
            modal.show();
        }

        // Función para descargar la foto
        function descargarFoto(url) {
            const link = document.createElement('a');
            link.href = url;
            link.download = `evidencia-movimiento-${new Date().toISOString().split('T')[0]}.jpg`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        async function revertirMovimiento(movimientoId) {
            const movimiento = movimientos.find(m => m.id === movimientoId);
            if (!movimiento) return;

            // Paso 1: Confirmación inicial
            const confirmResult = await Swal.fire({
                title: '¿Anular movimiento?',
                html: `
                    <div class="text-left">
                        <p>Vas a <strong>ANULAR</strong> este movimiento de ${movimiento.tipo}.</p>
                        <p><strong>Detalles:</strong></p>
                        <ul class="text-sm">
                            <li>Etiqueta: ${movimiento.etiqueta_nombre || movimiento.etiqueta_id}</li>
                            <li>Cantidad: ${movimiento.cantidad}</li>
                            <li>Fecha: ${new Date(movimiento.fecha).toLocaleDateString()}</li>
                            ${movimiento.motivo ? `<li>Motivo original: ${movimiento.motivo}</li>` : ''}
                        </ul>
                        <p class="mt-3 text-red-600 font-bold">
                            ⚠️ Esta acción no se puede deshacer y quedará registrada en el historial de anulaciones.
                        </p>
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Continuar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                showLoaderOnConfirm: false,
                preConfirm: () => {
                    return new Promise((resolve) => {
                        resolve(true);
                    });
                }
            });

            if (!confirmResult.isConfirmed) return;

            // Paso 2: Solicitar motivo de anulación
            const { value: motivo } = await Swal.fire({
                title: 'Motivo de la anulación',
                input: 'textarea',
                inputLabel: 'Por favor, especifica el motivo de la anulación:',
                inputPlaceholder: 'Ejemplo: Datos incorrectos, error de captura, duplicado...',
                inputAttributes: {
                    'aria-label': 'Escribe el motivo de la anulación'
                },
                showCancelButton: true,
                confirmButtonText: 'Confirmar anulación',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                inputValidator: (value) => {
                    if (!value || value.trim().length < 10) {
                        return 'Debes ingresar un motivo de al menos 10 caracteres';
                    }
                    if (value.trim().length > 500) {
                        return 'El motivo no debe exceder 500 caracteres';
                    }
                },
                customClass: {
                    validationMessage: 'my-validation-message'
                }
            });

            if (!motivo) return;

            // Paso 3: Confirmación final
            const finalConfirm = await Swal.fire({
                title: '¿Confirmar anulación?',
                html: `
                    <div class="text-left">
                        <p><strong>Resumen de la anulación:</strong></p>
                        <ul class="text-sm my-3">
                            <li><strong>Movimiento ID:</strong> #${movimiento.id}</li>
                            <li><strong>Tipo:</strong> ${movimiento.tipo}</li>
                            <li><strong>Motivo de anulación:</strong></li>
                            <div class="bg-gray-100 p-2 rounded mt-1">
                                ${motivo}
                            </div>
                        </ul>
                        <p class="mt-3 text-red-600 font-bold">
                            ⚠️ Esta acción ajustará el inventario y registrará la anulación permanentemente.
                        </p>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, anular definitivamente',
                cancelButtonText: 'Revisar nuevamente',
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                reverseButtons: true
            });

            if (!finalConfirm.isConfirmed) return;

            try {
                Swal.fire({
                    title: 'Procesando...',
                    text: 'Anulando movimiento...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                const formData = new FormData();
                formData.append('peticion', 'anular_movimiento');
                formData.append('token', authToken);
                formData.append('movimiento_id', movimientoId);
                formData.append('motivo_anulacion', motivo);

                const response = await fetch('controllers/movimientos.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();

                Swal.close();
                
                if (result.exito) {
                    await Swal.fire({
                        title: '¡Anulado!',
                        text: 'El movimiento ha sido anulado exitosamente.',
                        icon: 'success',
                        confirmButtonText: 'Aceptar',
                        timer: 3000,
                        timerProgressBar: true
                    });
                    
                    // Actualizar la lista de movimientos
                    await cargarMovimientos();
                    
                    // Opcional: Registrar en consola para auditoría
                    console.log(`Movimiento #${movimientoId} anulado. Motivo: ${motivo}`);
                } else {
                    throw new Error(result.msj);
                }
            } catch (error) {
                Swal.close();
                console.error('Error anulando movimiento:', error);
                
                await Swal.fire({
                    title: 'Error',
                    html: `
                        <div class="text-left">
                            <p>No se pudo anular el movimiento:</p>
                            <p class="text-red-600 mt-2">${error.message}</p>
                            <p class="text-sm text-gray-600 mt-3">
                                Verifica que tengas los permisos necesarios y que el movimiento no haya sido ya anulado.
                            </p>
                        </div>
                    `,
                    icon: 'error',
                    confirmButtonText: 'Entendido'
                });
            }
            
        }

        // Actualizar estadísticas
        function actualizarEstadisticas() {
            const hoy = new Date().toDateString();
            const esteMes = new Date().getMonth();
            
            const total = movimientos.length;
            const entradasHoy = movimientos.filter(m => 
                new Date(m.fecha_movimiento).toDateString() === hoy && m.tipo === 'entrada'
            ).length;
            const salidasHoy = movimientos.filter(m => 
                new Date(m.fecha_movimiento).toDateString() === hoy && m.tipo === 'salida'
            ).length;
            const entradasMes = movimientos.filter(m => 
                new Date(m.fecha_movimiento).getMonth() === esteMes && m.tipo === 'entrada'
            ).length;
            const salidasMes = movimientos.filter(m => 
                new Date(m.fecha_movimiento).getMonth() === esteMes && m.tipo === 'salida'
            ).length;

            document.getElementById('totalMovimientos').textContent = total;
            document.getElementById('entradasHoy').textContent = entradasHoy;
            document.getElementById('salidasHoy').textContent = salidasHoy;
            document.getElementById('entradasMes').textContent = entradasMes;
            document.getElementById('salidasMes').textContent = salidasMes;
        }

        // Inicialización
        document.addEventListener('DOMContentLoaded', async function() {
            authToken = localStorage.getItem('auth_token');
            const storedUser = localStorage.getItem('user');
            const tieneAcceso = <?php echo $tieneAcceso ? 'true' : 'false'; ?>;
            /* console.log(tieneAcceso);return; */
            
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
            
            // Cargar datos
            await cargarMovimientos();
            await cargarEtiquetas();
            
            // Configurar event listeners
            document.getElementById('logoutBtn').addEventListener('click', logout);
            document.getElementById('dropdownLogoutBtn').addEventListener('click', logout);
            document.getElementById('formEntrada').addEventListener('submit', registrarEntrada);
            document.getElementById('formSalida').addEventListener('submit', registrarSalida);
            document.getElementById('btnFiltrar').addEventListener('click', filtrarMovimientos);
            document.getElementById('btnReset').addEventListener('click', resetFiltros);
            
            // Actualizar stock disponible cuando se selecciona etiqueta
            document.getElementById('salidaEtiquetaId').addEventListener('change', function() {
                const etiquetaId = this.value;
                const etiqueta = etiquetas.find(e => e.id == etiquetaId);
                if (etiqueta) {
                    document.getElementById('stockDisponible').textContent = etiqueta.stock_actual;
                }
            });
            
            setInterval(updateCurrentTime, 60000);
        });

        function filtrarMovimientos() {
            // Implementar filtrado
            mostrarMovimientos();
        }

        function resetFiltros() {
            document.getElementById('tipoFilter').value = '';
            document.getElementById('fechaDesde').value = '';
            document.getElementById('fechaHasta').value = '';
            mostrarMovimientos();
        }

        function generarReporte() {
            alert('Generando reporte PDF...');
            // Implementar generación de PDF
        }

        function exportarExcel() {
            alert('Exportando a Excel...');
            // Implementar exportación a Excel
        }

        function logout() {
            localStorage.removeItem('auth_token');
            localStorage.removeItem('user');
            window.location.href = 'cerrar_sesion.php';
        }
    </script>
</body>
</html>