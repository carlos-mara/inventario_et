<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema Inventarios</title>
    
    <!-- MDBootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.0/mdb.min.css" rel="stylesheet">
    
    <style>
        .gradient-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .card-shadow {
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .btn-rounded {
            border-radius: 50px;
        }
    </style>
</head>
<body>
    <!-- Contenedor Principal -->
    <section class="gradient-custom">
        <div class="container py-5 h-100">
            <div class="row d-flex justify-content-center align-items-center h-100">
                <div class="col-12 col-md-8 col-lg-6 col-xl-5">
                    <!-- Tarjeta de Login -->
                    <div class="card bg-dark text-white card-shadow" style="border-radius: 1rem;">
                        <div class="card-body p-5 text-center">
                            
                            <!-- Icono y Título -->
                            <div class="mb-4">
                                <i class="fas fa-boxes fa-3x text-primary mb-3"></i>
                                <h2 class="fw-bold mb-2 text-uppercase">
                                    <i class="fas fa-cube me-2"></i>Sistema de Inventarios
                                </h2>
                                <p class="text-white-50 mb-4">Ingresa tus credenciales</p>
                            </div>

                            <!-- Formulario de Login -->
                            <form id="loginForm">
                                
                                <!-- Campo Email -->
                                <div class="form-outline form-white mb-4">
                                    <input 
                                        type="email" 
                                        id="email" 
                                        class="form-control form-control-lg" 
                                        value="sistemas@synertech.com.co"
                                        required
                                    />
                                    <label class="form-label" for="email">
                                        <i class="fas fa-envelope me-2"></i>Email
                                    </label>
                                </div>

                                <!-- Campo Contraseña -->
                                <div class="form-outline form-white mb-4">
                                    <input 
                                        type="password" 
                                        id="password" 
                                        class="form-control form-control-lg" 
                                        value="admin123"
                                        required
                                    />
                                    <label class="form-label" for="password">
                                        <i class="fas fa-lock me-2"></i>Contraseña
                                    </label>
                                </div>

                                <!-- Mensaje de Error -->
                                <div id="errorAlert" class="alert alert-danger d-none" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <span id="errorText"></span>
                                </div>

                                <!-- Botón de Login -->
                                <button 
                                    class="btn btn-primary btn-lg btn-rounded px-5 mb-3 w-100" 
                                    type="submit"
                                    id="loginBtn"
                                >
                                    <span id="btnText">
                                        <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                                    </span>
                                    <div id="btnSpinner" class="spinner-border spinner-border-sm d-none" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                </button>

                            </form>

                            <!-- Información de Prueba -->
                            <div class="mt-4">
                                <div class="card bg-secondary bg-opacity-25">
                                    <div class="card-body py-2">
                                        <small class="text-white-50">
                                            <i class="fas fa-info-circle me-1"></i>
                                            <strong>Credenciales de prueba:</strong><br>
                                            sistemas@synertech.com.co / admin123
                                        </small>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- MDBootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.0/mdb.min.js"></script>
    
    <script>
        // =============================================
        // ELEMENTOS DEL DOM
        // =============================================
        const loginForm = document.getElementById('loginForm');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const loginBtn = document.getElementById('loginBtn');
        const btnText = document.getElementById('btnText');
        const btnSpinner = document.getElementById('btnSpinner');
        const errorAlert = document.getElementById('errorAlert');
        const errorText = document.getElementById('errorText');

        // =============================================
        // FUNCIONES DE UTILIDAD
        // =============================================
        
        /**
         * Mostrar estado de carga
         */
        function setLoadingState(loading) {
            if (loading) {
                btnText.textContent = 'Iniciando sesión...';
                btnSpinner.classList.remove('d-none');
                loginBtn.disabled = true;
            } else {
                btnText.innerHTML = '<i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión';
                btnSpinner.classList.add('d-none');
                loginBtn.disabled = false;
            }
        }

        /**
         * Mostrar mensaje de error
         */
        function showError(message) {
            errorText.textContent = message;
            errorAlert.classList.remove('d-none');
            
            // Agregar animación de shake
            errorAlert.classList.add('animate__animated', 'animate__headShake');
            setTimeout(() => {
                errorAlert.classList.remove('animate__animated', 'animate__headShake');
            }, 1000);
        }

        /**
         * Ocultar mensaje de error
         */
        function hideError() {
            errorAlert.classList.add('d-none');
        }

        /**
         * Validar formulario
         */
        function validateForm() {
            const email = emailInput.value.trim();
            const password = passwordInput.value.trim();

            if (!email) {
                showError('El email es requerido');
                emailInput.focus();
                return false;
            }

            if (!password) {
                showError('La contraseña es requerida');
                passwordInput.focus();
                return false;
            }

            // Validación básica de email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                showError('Por favor ingresa un email válido');
                emailInput.focus();
                return false;
            }

            return true;
        }

        // =============================================
        // MANEJADOR PRINCIPAL DEL LOGIN
        // =============================================
        
        /**
         * Procesar el login
         */
        async function handleLogin(event) {
            event.preventDefault();
            
            console.log('🔐 Iniciando proceso de login...');

            // Validar formulario
            if (!validateForm()) {
                return;
            }

            // Ocultar errores anteriores
            hideError();

            // Activar estado de carga
            setLoadingState(true);

            try {
                const email = emailInput.value.trim();
                const password = passwordInput.value.trim();

                console.log('📤 Preparando datos para enviar...');

                // Preparar datos para enviar al servidor
                const formData = new FormData();
                formData.append('peticion', 'login');
                formData.append('email', email);
                formData.append('password', password);

                console.log('🔄 Enviando petición al servidor...');

                // Hacer petición HTTP al servidor
                const response = await fetch('controllers/auth.php', {
                    method: 'POST',
                    body: formData
                });

                console.log('📥 Respuesta recibida, procesando...');

                // Convertir respuesta a JSON
                const result = await response.json();
                console.log('📊 Resultado del servidor:', result);

                // Verificar si el login fue exitoso
                if (result.exito) {
                    console.log('✅ Login exitoso!');
                    console.log('👤 Usuario:', result.usuario);
                    console.log('🔐 Token:', result.token.substring(0, 20) + '...');

                    // Guardar datos en localStorage
                    localStorage.setItem('auth_token', result.token);
                    localStorage.setItem('user', JSON.stringify(result.usuario));

                    console.log('💾 Datos guardados en localStorage');

                    // Mostrar mensaje de éxito
                    btnText.innerHTML = '<i class="fas fa-check me-2"></i>¡Éxito!';
                    loginBtn.classList.remove('btn-primary');
                    loginBtn.classList.add('btn-success');

                    // Redirigir al dashboard después de un breve delay
                    setTimeout(() => {
                        window.location.href = 'dashboard.php';
                    }, 1000);

                } else {
                    // Manejar error del servidor
                    console.log('❌ Error del servidor:', result.msj);
                    showError(result.msj || 'Credenciales incorrectas');
                    
                    // Agregar efecto de shake al formulario
                    loginForm.classList.add('animate__animated', 'animate__headShake');
                    setTimeout(() => {
                        loginForm.classList.remove('animate__animated', 'animate__headShake');
                    }, 1000);
                }

            } catch (error) {
                // Manejar errores de conexión
                console.error('💥 Error de conexión:', error);
                showError('Error de conexión con el servidor');
                
            } finally {
                // Desactivar estado de carga
                setLoadingState(false);
                console.log('🏁 Finalizando proceso de login');
            }
        }

        // =============================================
        // VERIFICACIÓN DE AUTENTICACIÓN PREVIA
        // =============================================
        
        function checkExistingAuth() {
            console.log('🔍 Verificando autenticación existente...');
            
            const token = localStorage.getItem('auth_token');
            if (token) {
                console.log('🎯 Usuario ya autenticado, redirigiendo...');
                
                // Mostrar mensaje y redirigir
                const originalText = btnText.innerHTML;
                btnText.innerHTML = '<i class="fas fa-redo me-2"></i>Redirigiendo...';
                loginBtn.classList.add('btn-info');
                
                setTimeout(() => {
                    window.location.href = 'dashboard.php';
                }, 500);
            } else {
                console.log('🔓 Usuario no autenticado, mostrar formulario');
            }
        }

        // =============================================
        // INICIALIZACIÓN
        // =============================================
        
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Página de login cargada');
            
            // Agregar evento al formulario
            loginForm.addEventListener('submit', handleLogin);
            
            // Agregar eventos para limpiar errores al escribir
            emailInput.addEventListener('input', hideError);
            passwordInput.addEventListener('input', hideError);
            
            // Verificar si ya está autenticado
            checkExistingAuth();
            
            // Enfocar el campo de email al cargar
            emailInput.focus();
        });

        // =============================================
        // FUNCIONES GLOBALES PARA DEBUG
        // =============================================
        
        // Exponer funciones para debugging en consola
        window.debugAuth = {
            clearStorage: function() {
                localStorage.removeItem('auth_token');
                localStorage.removeItem('user');
                console.log('🗑️ localStorage limpiado');
                location.reload();
            },
            getToken: function() {
                return localStorage.getItem('auth_token');
            },
            getUser: function() {
                return JSON.parse(localStorage.getItem('user') || 'null');
            }
        };
    </script>
</body>
</html>