<?php
session_start();
date_default_timezone_set('America/Bogota');
setLocale(LC_ALL, "es_CO");

// Incluir los archivos necesarios
require_once "../models/Categoria.php";
require_once "../middleware/AuthMiddleware.php";

class CategoriasControllers extends Categoria
{
    private $tokenMiddleware;

    public function __construct()
    {
        parent::__construct();
        $this->tokenMiddleware = new AuthMiddleware();
    }

    private function verificarAcceso($token) 
    {
        try {
            if (empty($token)) {
                return [
                    'exito' => false,
                    'msj' => 'Token de acceso requerido',
                    'codigo' => 401
                ];
            }

            $usuario = $this->tokenMiddleware->verificarToken($token);
            
            if (!$usuario) {
                return [
                    'exito' => false,
                    'msj' => 'Token inválido o expirado',
                    'codigo' => 401
                ];
            }

            return [
                'exito' => true,
                'usuario' => $usuario
            ];

        } catch (Exception $e) {
            error_log("Error en verificarAcceso: " . $e->getMessage());
            return [
                'exito' => false,
                'msj' => 'Error de autenticación',
                'codigo' => 500
            ];
        }
    }

    public function listarCategorias($token)
    {
        try {
            $validacion = $this->verificarAcceso($token);
            if (!$validacion['exito']) {
                return $validacion;
            }

            $data = parent::listar();
            
            if (is_array($data) && count($data) > 0) {
                return [
                    "exito" => true,
                    "msj" => "Listado de categorías correcto",
                    "data" => $data
                ];
            } else {
                return [
                    "exito" => true,
                    "msj" => "No se encontraron categorías",
                    "data" => []
                ];
            }

        } catch (Exception $e) {
            error_log("Error en listarCategorias: " . $e->getMessage());
            return [
                "exito" => false,
                "msj" => "Error listando categorías"
            ];
        }
    }

    public function nuevaCategoria($nombre, $descripcion, $token){
        try {
            $validacion = $this->verificarAcceso($token);
            if (!$validacion['exito']) {
                return $validacion;
            }

            $data = parent::crear($nombre, $descripcion, date('Y-m-d H:i:s'));

            if ($data) {
                return [
                    "exito" => true,
                    "msj" => "Categoría creada correctamente",
                    "data" => $data
                ];
            } else {
                return [
                    "exito" => false,
                    "msj" => "No se pudo crear la categoría",
                    "data" => []
                ];
            }

        } catch (Exception $e) {
            error_log("Error en crear categoría: " . $e->getMessage());
            return [
                "exito" => false,
                "msj" => "Error creando categoría"
            ];
        }
    }
}

// =============================================
// PROCESAMIENTO DE PETICIONES SIMPLIFICADO
// =============================================

// Configurar headers para JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:8080');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Permitir tanto POST como GET para datos públicos
if (isset($_POST["peticion"]) || isset($_GET["peticion"])) {
    $peticion = $_POST["peticion"] ?? $_GET["peticion"] ?? '';
    $cat = new CategoriasControllers();
    
    $respuesta = [
        "exito" => false,
        "msj" => "Petición no reconocida"
    ];

    try {
        switch ($peticion) {
            case 'listar':
                $token = $_POST['token'] ?? $_GET['token'] ?? '';
                $respuesta = $cat->listarCategorias($token);
            break;

            case 'crear':
                $nombre = $_POST['nombre'] ?? '';
                $token = $_POST['token'] ?? $_GET['token'] ?? '';
                $descripcion = $_POST['descripcion'] ?? '';
                $respuesta = $cat->nuevaCategoria($nombre, $descripcion, $token);
            break;

            default:
                $respuesta = [
                    "exito" => false,
                    "msj" => "Petición no reconocida: " . $peticion
                ];
                break;
        }

    } catch (Exception $e) {
        error_log("Error en categorias controller: " . $e->getMessage());
        $respuesta = [
            "exito" => false,
            "msj" => "Error interno del servidor"
        ];
    }

    // Enviar respuesta
    echo json_encode($respuesta);
    exit;

} else {
    http_response_code(400);
    echo json_encode([
        "exito" => false,
        "msj" => "Petición no especificada"
    ]);
    exit;
}
?>