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
    <title>Editar Etiqueta - Sistema Inventarios</title>
    
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
        .form-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
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
                            <i class="fas fa-tag me-2"></i>Editar Etiqueta
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
                        <div class="col-lg-10">
                            <div class="form-section">
                                <h4 class="fw-bold mb-4 text-primary">
                                    <i class="fas fa-plus-circle me-2"></i>Editar Etiqueta
                                </h4>
                                
                                <form id="formEtiqueta">
                                    <!-- Información Básica -->
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <div class="form-outline" data-mdb-input-init>
                                                <input type="text" id="nombre" class="form-control" required />
                                                <label class="form-label" for="nombre">
                                                    <i class="fas fa-tag me-2"></i>Nombre de la Etiqueta *
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-outline" >
                                                <select class="form-select" id="categoria_id" required>
                                                    <option value="">Seleccionar categoría</option>
                                                </select>
                                                <label class="form-label" for="categoria_id">
                                                    <i class="fas fa-folder me-2"></i>Categoría *
                                                </label>
                                            </div>
                                        </div>
                                        <!-- stock minimo -->
                                         <div class="col-md-6 mt-4">
                                            <div class="form-outline" data-mdb-input-init>
                                                <input type="number" id="stock_minimo" class="form-control" required />
                                                <label class="form-label" for="stock_minimo">
                                                    <i class="fas fa-boxes-stacked me-2"></i>Stock Mínimo *
                                                </label>
                                            </div>
                                         </div>
                                    </div>
                                    <!-- tamaño -->
                                     <div class="row mb-4">
                                        <div class="col-12">
                                            <span class="fw-bold mb-2 text-primary">
                                                <i class="fas fa-ruler-combined me-2"></i>Tamaños de la Etiqueta (cm) *
                                            </span>
                                            
                                            <!-- Contenedor de tamaños dinámicos -->
                                            <div id="tamanosContainer">
                                                <!-- Primer tamaño (siempre visible) -->
                                                <div class="tamano-item border rounded p-3 mb-3 bg-light">
                                                    <div class="row align-items-end">
                                                        <div class="col-md-4">
                                                            <label class="form-label fw-bold">Alto (cm) *</label>
                                                            <input type="number" class="form-control" name="alto[]" step="0.01" min="0.1" required 
                                                                placeholder="Ej: 5.0">
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label fw-bold">Ancho (cm) *</label>
                                                            <input type="number" class="form-control" name="ancho[]" step="0.01" min="0.1" required 
                                                                placeholder="Ej: 3.0">
                                                        </div>
                                                        <div class="col-md-2">
                                                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removerTamano(this)" disabled>
                                                                <i class="fa-regular fa-trash-can"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Botón para agregar más tamaños -->
                                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="agregarTamano()">
                                                <i class="fas fa-plus me-1"></i>Agregar otro tamaño
                                            </button>
                                            
                                            <div class="form-text">
                                                Puedes agregar múltiples tamaños para esta etiqueta. 
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Descripción -->
                                    <div class="row ">
                                        <div class="col-12">
                                            <div class="form-outline" data-mdb-input-init>
                                                <textarea class="form-control" id="descripcion" rows="3"></textarea>
                                                <label class="form-label" for="descripcion">
                                                    <i class="fas fa-align-left me-2"></i>Descripción
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    

                                    <!-- Precios -->
                                    
                                    <!-- mostrar foto -->
                                    <div class="row mb-4">
                                        <div class="col-12">
                                            <label class="form-label">
                                                <i class="fas fa-image me-2"></i>Foto Actual
                                            </label>
                                            <div>
                                                <input type="hidden" id="fotoActualHidden" />
                                                <img id="fotoActual" src="https://placehold.co/600X400?text=SIN+FOTO&font=roboto" 
                                                     alt="Foto Etiqueta" class="img-fluid rounded p-2" width="200" height="200">
                                            </div>
                                        </div>
                                    </div>
                                    <!-- checkbox -->
                                     <div class="row mb-4">
                                        <div class="col-12">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" value="" id="cambiar_foto"/>
                                                <label class="form-check-label" for="cambiar_foto">
                                                    <i class="fas fa-camera me-2"></i>Cambiar foto
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Foto -->
                                    <div class="row mb-4">
                                        <div class="col-12">
                                            <label class="form-label">
                                                <i class="fas fa-camera me-2"></i>Foto de la Etiqueta
                                            </label>
                                            <input disabled type="file" id="foto" class="form-control" accept="image/*" />
                                            <div class="form-text">Formatos aceptados: JPG, PNG, GIF. Tamaño máximo: 5MB</div>
                                        </div>
                                    </div>

                                    <!-- Botones -->
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="d-flex justify-content-between">
                                                <button type="button" class="btn btn-secondary" onclick="navigate('inventario.php')">
                                                    <i class="fas fa-arrow-left me-2"></i>Cancelar
                                                </button>
                                                <button type="submit" class="btn btn-success" id="btnGuardar">
                                                    <i class="fas fa-save me-2"></i>Guardar Cambios
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        

    <!-- MDBootstrap JS -->
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/9.2.0/mdb.umd.min.js"></script>
    <script src="js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        let userData = null;
        let authToken = null;
        
        let fotoInput;

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
                    const categoriaSelect = document.getElementById('categoria_id');
                    result.data.forEach(categoria => {
                        const option = document.createElement('option');
                        option.value = categoria.id;
                        option.textContent = categoria.nombre;
                        categoriaSelect.appendChild(option);
                    });
                } else {
                    throw new Error(result.msj);
                }
                
            } catch (error) {
                console.error('Error cargando categorías:', error);
                mostrarMensaje('error', error.message || 'Error al cargar las categorías');
            }
        }

        async function cargarDatosEtiqueta(id) {
            // variable get id
            getParams = new URLSearchParams(window.location.search);
            id = getParams.get('id');
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
                    const etiqueta = result.etiqueta;
                    
                    document.getElementById('nombre').value = etiqueta.info.nombre;
                    document.getElementById('nombre').classList.add('active');
                    document.getElementById('descripcion').value = etiqueta.info.descripcion;
                    document.getElementById('descripcion').classList.add('active');
                    
                    document.getElementById('stock_minimo').value = etiqueta.info.stock_minimo;
                    document.getElementById('stock_minimo').classList.add('active');
                    //select categoria
                    document.getElementById('categoria_id').value = etiqueta.info.categoria_id;
                    document.getElementById('fotoActual').src = etiqueta.info.foto_url ? 'uploads/' + etiqueta.info.foto_url : 'https://placehold.co/600X400?text=SIN+FOTO&font=roboto';
                    document.getElementById('fotoActualHidden').value = etiqueta.info.foto_url ? etiqueta.info.foto_url : '';
                    // tamaños
                    const tamanosContainer = document.getElementById('tamanosContainer');
                    tamanosContainer.innerHTML = '';
                    etiqueta.tamanos.forEach((tamano, index) => {
                        const tamanoItem = document.createElement('div');
                        tamanoItem.classList.add('tamano-item', 'border', 'rounded', 'p-3', 'mb-3', 'bg-light');
                        tamanoItem.innerHTML = `
                            <div class="row align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Alto (cm) *</label>
                                    <input type="number" class="form-control" name="alto[]" step="0.01" min="0.1" required 
                                        placeholder="Ej: 5.0" value="${tamano.alto}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Ancho (cm) *</label>
                                    <input type="number" class="form-control" name="ancho[]" step="0.01" min="0.1" required 
                                        placeholder="Ej: 3.0" value="${tamano.ancho}">
                                    <input type="hidden" name="idTamano[]" value="${tamano.idTamano}">
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="removerTamano(this)" ${index === 0 ? 'disabled' : ''}>
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>
                                </div>
                            </div>
                        `;
                        tamanosContainer.appendChild(tamanoItem);
                    });
                } else {
                    throw new Error(result.msj);
                }
                
            } catch (error) {
                console.error('Error cargando datos de la etiqueta:', error);
                mostrarMensaje('error', error.message || 'Error al cargar los datos de la etiqueta');
            }
            
        }

        // Contador para los índices de tamaños
        let contadorTamanos = 1;

        // Función para agregar un nuevo campo de tamaño
        function agregarTamano() {
            const container = document.getElementById('tamanosContainer');
            
            const nuevoTamano = document.createElement('div');
            nuevoTamano.className = 'tamano-item border rounded p-3 mb-3 bg-light';
            nuevoTamano.innerHTML = `
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Alto (cm) *</label>
                        <input type="number" class="form-control" name="alto[]" step="0.01" min="0.1" required 
                            placeholder="Ej: 5.0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Ancho (cm) *</label>
                        <input type="number" class="form-control" name="ancho[]" step="0.01" min="0.1" required 
                            placeholder="Ej: 3.0">
                            <input type="hidden" name="idTamano[]" value="0">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removerTamano(this)">
                            <i class="fas fa-trash-can"></i>
                        </button>
                    </div>
                </div>
            `;
            
            container.appendChild(nuevoTamano);
            contadorTamanos++;
        }

        // Función para remover un campo de tamaño
        function removerTamano(boton) {
            const tamanoItem = boton.closest('.tamano-item');
            
            tamanoItem.remove();
        }

        // Validación del formulario
        function validarTamanos() {
            const tamanos = document.querySelectorAll('.tamano-item');
            if (tamanos.length === 0) {
                mostrarMensaje('error', 'Debe agregar al menos un tamaño para la etiqueta');
                return false;
            }
            
            return true;
        }
        
        // Guardar etiqueta
        async function guardarEtiqueta(event) {
            event.preventDefault();
            
            if (!validarTamanos()) {
                return;
            }
            
            const btnGuardar = document.getElementById('btnGuardar');
            const originalText = btnGuardar.innerHTML;
            
            try {
                btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Guardando...';
                btnGuardar.disabled = true;
                if (document.getElementById('cambiar_foto').checked) {
                    cambiar_foto = true;
                } else {
                    cambiar_foto = false;
                    fotoInput = document.getElementById('fotoActualHidden').value;
                }
                const formData = new FormData();
                formData.append('peticion', 'editar');
                formData.append('id', getParams.get('id'));
                formData.append('token', authToken);
                formData.append('nombre', document.getElementById('nombre').value);
                formData.append('categoria_id', document.getElementById('categoria_id').value);
                formData.append('descripcion', document.getElementById('descripcion').value);
                formData.append('stock_minimo', document.getElementById('stock_minimo').value);
                formData.append('cambiar_foto', cambiar_foto);
                formData.append('foto_anterior', fotoInput);

                const foto = document.getElementById('foto').files[0];
                if (foto) {
                    formData.append('foto', foto);
                }

                // Recolectar tamaños
                const altos = document.getElementsByName('alto[]');
                const anchos = document.getElementsByName('ancho[]');
                const idTamano = document.getElementsByName('idTamano[]');

                const tamanos = [];
                for (let i = 0; i < altos.length; i++) {
                    tamanos.push({
                        id: idTamano[i].value,
                        alto: altos[i].value,
                        ancho: anchos[i].value,
                    });
                }
                
                formData.append('tamanos', JSON.stringify(tamanos));

                const response = await fetch('controllers/etiquetas.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.exito) {
                    mostrarMensaje('success', 'Etiqueta editada exitosamente');
                    /* actualizar pagina */
                    
                } else {
                    throw new Error(result.msj);
                }
                
            } catch (error) {
                console.error('Error guardando etiqueta:', error);
                mostrarMensaje('error', error.message || 'Error al guardar la etiqueta');
            } finally {
                btnGuardar.innerHTML = originalText;
                btnGuardar.disabled = false;
            }
        }

        function mostrarMensaje(tipo, mensaje) {
            Swal.fire({
                icon: tipo,
                title: mensaje,
                timer: 2000,
                showConfirmButton: false
            });
        }

        

        document.addEventListener('DOMContentLoaded', function() {
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
            cargarCategorias();
            cargarDatosEtiqueta();
            
            document.getElementById('logoutBtn').addEventListener('click', logout);
            document.getElementById('dropdownLogoutBtn').addEventListener('click', logout);
            document.getElementById('formEtiqueta').addEventListener('submit', guardarEtiqueta);
            /* evento del checkbox cambiar_foto */
            document.getElementById('cambiar_foto').addEventListener('change', function() {
                const fotoInput = document.getElementById('foto');
                if (this.checked) {
                    fotoInput.disabled = false;
                } else {
                    fotoInput.disabled = true;
                    fotoInput.value = '';
                }
            });

            const inputFile = document.getElementById('foto');
            const preview = document.getElementById('fotoActual');

            inputFile.addEventListener('change', function() {
                const file = inputFile.files[0];
                if (file) {
                    const fileURL = URL.createObjectURL(file);
                    preview.src = fileURL;
                    preview.style.display = 'block';
                }
            });
            setInterval(updateCurrentTime, 60000);
        });

        function logout() {
            localStorage.removeItem('auth_token');
            localStorage.removeItem('user');
            window.location.href = 'cerrar_sesion.php';
        }
    </script>
</body>
</html>