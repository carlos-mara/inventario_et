<?php
session_start();
date_default_timezone_set('America/Bogota');
setLocale(LC_ALL, "es_CO");

// Incluir los archivos necesarios
require_once "../models/Usuario.php";
require_once "../middleware/AuthMiddleware.php";

class AuthController extends Usuario
{
    private $auth;

    public function __construct()
    {
        parent::__construct();
        $this->auth = new AuthMiddleware();
    }

    /**
     * Método para procesar el login
     */
    public function procesarLogin($email, $password)
    {
        try {
            // Validar que vengan los datos requeridos
            if (empty($email) || empty($password)) {
                return [
                    "exito" => false,
                    "msj" => "Email y contraseña son requeridos"
                ];
            }

            // Validar formato de email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return [
                    "exito" => false,
                    "msj" => "El formato del email no es válido"
                ];
            }

            // Intentar hacer login
            $usuario = $this->login($email, $password);

            if ($usuario) {
                
                $_SESSION['usuario'] = $usuario;
                // Generar token JWT
                $token = $this->auth->generarToken($usuario);

                return [
                    "exito" => true,
                    "msj" => "Login exitoso",
                    "token" => $token,
                    "usuario" => $usuario
                ];
            } else {
                return [
                    "exito" => false,
                    "msj" => "Credenciales incorrectas"
                ];
            }

        } catch (Exception $e) {
            error_log("Error en procesarLogin: " . $e->getMessage());
            return [
                "exito" => false,
                "msj" => "Error interno del servidor"
            ];
        }
    }

    /**
     * Método para verificar un token
     */
    public function verificarToken($token)
    {
        try {
            if (empty($token)) {
                return [
                    "exito" => false,
                    "msj" => "Token requerido"
                ];
            }

            $usuario = $this->auth->verificarToken($token);

            if ($usuario) {
                return [
                    "exito" => true,
                    "msj" => "Token válido",
                    "usuario" => $usuario
                ];
            } else {
                return [
                    "exito" => false,
                    "msj" => "Token inválido o expirado"
                ];
            }

        } catch (Exception $e) {
            error_log("Error en verificarToken: " . $e->getMessage());
            return [
                "exito" => false,
                "msj" => "Error verificando token"
            ];
        }
    }

    /**
     * Método para cerrar sesión (invalidate token en frontend)
     */
    public function logout()
    {
        // En JWT no hay logout del lado del servidor, se maneja en frontend
        // eliminando el token
        return [
            "exito" => true,
            "msj" => "Sesión cerrada exitosamente"
        ];
    }
}

// =============================================
// PROCESAMIENTO DE PETICIONES
// =============================================

if (isset($_POST["peticion"])) {
    $peticion = $_POST["peticion"];
    $ctl = new AuthController();
    
    // Configurar headers para JSON
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: http://localhost:8080'); // Ajusta según tu frontend
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');

    // Manejar preflight OPTIONS
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        exit(0);
    }

    $respuesta = [
        "exito" => false,
        "msj" => "Petición no reconocida"
    ];

    try {
        switch ($peticion) {
            case 'login':
                $email = $_POST['email'] ?? '';
                $password = $_POST['password'] ?? '';
                
                $respuesta = $ctl->procesarLogin($email, $password);
                break;

            case 'verificar':
                $token = $_POST['token'] ?? '';
                $respuesta = $ctl->verificarToken($token);
                break;

            case 'logout':
                $respuesta = $ctl->logout();
                break;

            case 'crearUsuario':
                // Solo para desarrollo - crear usuario inicial
                $datos = [
                    'username' => $_POST['username'] ?? '',
                    'email' => $_POST['email'] ?? '',
                    'password' => $_POST['password'] ?? '',
                    'nombre_completo' => $_POST['nombre_completo'] ?? '',
                    'rol' => $_POST['rol'] ?? 'gestor'
                ];
                
                $id = $ctl->crear($datos);
                if ($id) {
                    $respuesta = [
                        "exito" => true,
                        "msj" => "Usuario creado exitosamente",
                        "id" => $id
                    ];
                } else {
                    $respuesta = [
                        "exito" => false,
                        "msj" => "Error creando usuario"
                    ];
                }
                break;

            default:
                $respuesta = [
                    "exito" => false,
                    "msj" => "Petición no reconocida: " . $peticion
                ];
                break;
        }

    } catch (Exception $e) {
        error_log("Error en auth controller: " . $e->getMessage());
        $respuesta = [
            "exito" => false,
            "msj" => "Error interno del servidor: " . $e->getMessage()
        ];
    }

    // Enviar respuesta
    echo json_encode($respuesta);
    exit;

} else {
    // Si no es una petición POST válida
    header('Content-Type: application/json');
    echo json_encode([
        "exito" => false,
        "msj" => "Método no permitido o petición inválida"
    ]);
    exit;
}
?>