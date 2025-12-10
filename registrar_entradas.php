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
    <title>Registrar Entradas - Sistema Inventarios</title>
    
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
        .entrada-card {
            border-left: 4px solid #28a745;
            transition: all 0.3s;
        }
        .entrada-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .etiqueta-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
        }
        .scan-area {
            border: 2px dashed #28a745;
            border-radius: 10px;
            transition: all 0.3s;
        }
        .scan-area:hover {
            border-color: #218838;
            background-color: rgba(40, 167, 69, 0.05);
        }
        .quick-action-btn {
            transition: all 0.3s;
        }
        .quick-action-btn:hover {
            transform: scale(1.05);
        }
        .stock-change {
            font-size: 0.9em;
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
    </style>
</head>
<body>
    
            <!-- SIDEBAR -->
            <?php include "menu.php"; ?>

            <!-- CONTENIDO PRINCIPAL -->
            <div class="col" id="mainContent">
                <!-- Barra Superior -->
                <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
                    <div class="container-fluid">
                        <button class="sidebar-toggle-btn" id="customSidebarToggle">
                            <i class="fas fa-bars"></i>
                        </button>
                        
                        <span class="navbar-brand fw-bold text-primary">
                            <i class="fas fa-arrow-down me-2"></i>Registrar Entradas
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
                            <div class="alert alert-success">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-info-circle fa-2x me-3"></i>
                                    <div>
                                        <h5 class="alert-heading mb-1">Registro de Entradas</h5>
                                        <p class="mb-0">Registra nuevas entradas de inventario para mantener actualizado tu stock</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Formulario de Entrada -->
                        <div class="col-lg-6 mb-4">
                            <div class="card entrada-card">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-plus-circle me-2"></i>Nueva Entrada
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <form id="formEntrada">
                                        <!-- Selección de Etiqueta -->
                                        <div class="mb-3">
                                            <label for="etiquetaId" class="form-label fw-bold">Etiqueta *</label>
                                            <div class="input-group">
                                                <select class="form-select" id="etiquetaId" required>
                                                    <option value="">Buscar o seleccionar etiqueta...</option>
                                                </select>
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
                                                <input type="number" class="form-control" id="cantidad" min="1" required placeholder="Ej: 100">
                                                <span class="input-group-text">unidades</span>
                                            </div>
                                            <div class="form-text" id="infoCantidad"></div>
                                        </div>

                                        <!-- Precio Unitario (Opcional) -->
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Precio <span class="text-muted">(opcional)</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text">$</span>
                                                <input type="number" class="form-control" id="precioUnitario" min="0" step="0.01" placeholder="0.00">
                                            </div>
                                            <div class="form-text">Precio de compra (opcional)</div>
                                        </div>

                                        <!-- Motivo -->
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Motivo de Entrada *</label>
                                            <select class="form-select" id="motivo" required>
                                                <option value="">Seleccionar motivo...</option>
                                                <option value="compra">Compra</option>
                                                <option value="produccion">Producción</option>
                                                <option value="devolucion">Devolución</option>
                                                <option value="ajuste_inventario">Ajuste de Inventario</option>
                                                <option value="transferencia">Transferencia Interna</option>
                                                <option value="donacion">Donación</option>
                                                <option value="otros">Otros</option>
                                            </select>
                                        </div>

                                        <!-- Referencia -->
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Referencia <span class="text-muted">(opcional)</span></label>
                                            <input type="text" class="form-control" id="referencia" placeholder="N° Factura, Orden de Compra, etc.">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Cód. de Proyecto <span class="text-muted">(opcional)</span></label>
                                            <input type="text" class="form-control" id="cod" placeholder="Código de proyecto relacionado">
                                        </div>

                                        <!-- Observaciones -->
                                        <div class="mb-4">
                                            <label class="form-label fw-bold">Observaciones <span class="text-muted">(opcional)</span></label>
                                            <textarea class="form-control" id="observaciones" rows="3" placeholder="Detalles adicionales de la entrada..."></textarea>
                                        </div>

                                        <!-- Botones -->
                                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                            <button type="reset" class="btn btn-outline-secondary me-2" onclick="limpiarFormulario()">
                                                <i class="fas fa-redo me-1"></i>Limpiar
                                            </button>
                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-save me-1"></i>Registrar Entrada
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6 mb-4">
                            <!-- Foto de etiqueta -->
                            <div class="card">
                                <div class="card-body text-center">
                                    <img id="img_etiqueta" class="img-fluid rounded d-none" style="max-height: 300px; object-fit: contain;" src="" alt="Imagen de etiqueta">
                                    <div id="sinImagen" class="text-muted">
                                        <i class="fas fa-image fa-3x mb-2"></i>
                                        <p>Seleccione una etiqueta para ver la imagen</p>
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
        let tamanosActuales = [];
        let tamanoSeleccionado = null;

        // Cargar etiquetas
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
                    llenarSelectEtiquetas();
                } else {
                    throw new Error(result.msj);
                }
            } catch (error) {
                console.error('Error cargando etiquetas:', error);
                mostrarMensaje('error', 'Error al cargar las etiquetas');
            }
        }

        // Llenar select de etiquetas
        function llenarSelectEtiquetas() {
            const select = document.getElementById('etiquetaId');
            select.innerHTML = '<option value="">Buscar o seleccionar etiqueta...</option>';
            
            etiquetas.forEach(etiqueta => {
                if (etiqueta.activa) {
                    const option = document.createElement('option');
                    option.value = etiqueta.id;
                    option.textContent = `${etiqueta.nombre}`;
                    select.appendChild(option);
                }
            });
        }

        // Mostrar información de etiqueta seleccionada
        function mostrarInfoEtiqueta(etiquetaId) {
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
            } else {
                infoDiv.classList.add('d-none');
                document.getElementById('img_etiqueta').classList.add('d-none');
                document.getElementById('sinImagen').classList.remove('d-none');
            }
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
            if (tamanoSeleccionado && tamanoSeleccionado.stock_actual !== undefined) {
                infoCantidad.textContent = `Stock disponible: ${tamanoSeleccionado.stock_actual} unidades`;
                infoCantidad.className = 'form-text ' + obtenerClaseTextoStock(tamanoSeleccionado.stock_actual);
            } else {
                infoCantidad.textContent = '';
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
            document.getElementById('formEntrada').reset();
            document.getElementById('infoEtiqueta').classList.add('d-none');
            document.getElementById('tamanosContainer').classList.add('d-none');
            document.getElementById('infoTamanoSeleccionado').classList.add('d-none');
            document.getElementById('img_etiqueta').classList.add('d-none');
            document.getElementById('sinImagen').classList.remove('d-none');
            document.getElementById('infoCantidad').textContent = '';
            tamanoSeleccionado = null;
            tamanosActuales = [];
        }

        // Registrar entrada
        async function registrarEntrada(event) {
            event.preventDefault();
            
            try {
                const etiquetaId = document.getElementById('etiquetaId').value;
                const cantidad = document.getElementById('cantidad').value;
                const precioUnitario = document.getElementById('precioUnitario').value || 0;
                const motivo = document.getElementById('motivo').value;
                const observaciones = document.getElementById('observaciones').value;
                const referencia = document.getElementById('referencia').value;
                const cod_proyecto = document.getElementById('cod').value;
                const usuario_id = userData.id;
                
                const tamano = obtenerTamanoSeleccionado();
                if (!tamano) {
                    mostrarMensaje('error', 'Por favor seleccione un tamaño');
                    return;
                }

                const alto = tamano.alto;
                const ancho = tamano.ancho;
                const tamano_id = tamano.id;

                if (!etiquetaId || !cantidad || !motivo) {
                    mostrarMensaje('error', 'Por favor complete todos los campos requeridos');
                    return;
                }

                const formData = new FormData();
                formData.append('peticion', 'registrar_entrada');
                formData.append('token', authToken);
                formData.append('etiqueta_id', etiquetaId);
                formData.append('cantidad', cantidad);
                formData.append('precio_unitario', precioUnitario);
                formData.append('motivo', motivo);
                formData.append('referencia', referencia);
                formData.append('observaciones', observaciones);
                formData.append('cod_proyecto', cod_proyecto);
                formData.append('usuario_id', usuario_id);
                formData.append('alto', alto);
                formData.append('ancho', ancho);
                formData.append('tamano_id', tamano_id);

                const response = await fetch('controllers/movimientos.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.exito) {
                    await mostrarMensaje('success', 'Entrada registrada exitosamente');
                    limpiarFormulario();
                    // Recargar etiquetas para actualizar stock
                    await cargarEtiquetas();
                } else {
                    throw new Error(result.msj);
                }
            } catch (error) {
                console.error('Error registrando entrada:', error);
                mostrarMensaje('error', error.message || 'Error al registrar la entrada');
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
            
            // Cargar datos
            await cargarEtiquetas();
            
            // Configurar event listeners
            document.getElementById('logoutBtn').addEventListener('click', logout);
            document.getElementById('dropdownLogoutBtn').addEventListener('click', logout);
            document.getElementById('formEntrada').addEventListener('submit', registrarEntrada);
            document.getElementById('etiquetaId').addEventListener('change', function() {
                mostrarInfoEtiqueta(this.value);
                cargarTamanosEtiqueta(this.value);
            });
            
            // Actualizar información de cantidad cuando cambia
            document.getElementById('cantidad').addEventListener('input', actualizarInfoCantidad);
            
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
            localStorage.removeItem('auth_token');
            localStorage.removeItem('user');
            window.location.href = 'cerrar_sesion.php';
        }
    </script>
</body>
</html>