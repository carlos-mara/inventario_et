<?php
session_start();
date_default_timezone_set('America/Bogota');
setLocale(LC_ALL, "es_CO");

// Incluir los archivos necesarios
require_once "../models/Movimiento.php";
require_once "../models/Proyecto.php";
require_once "../middleware/AuthMiddleware.php";

class MovimientosControllers extends Movimiento
{
    private $tokenMiddleware;
    private $pro;

    public function __construct()
    {
        parent::__construct();
        $this->tokenMiddleware = new AuthMiddleware();
        $this->pro = new Proyecto();
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

    public function movimiento($token, $etiqueta_id, $tipo, $cantidad, $alto, $ancho, $id_tamano, $precio, $motivo = null, $referencia = null, $observaciones = null, $cantidad_anterior = 0, $cantidad_nueva = 0, $cod_proyecto = null, $usuario_id, $fecha = null, $foto_url = null) 
    {
        try {
            $validacion = $this->verificarAcceso($token);
                if (!$validacion['exito']) {
                    return $validacion;
            }

            // Verificar que hay suficiente stock para salidas
            if ($tipo === 'salida') {
                $stock_actual = parent::obtenerCantidadActualTamano($id_tamano);
                if ($stock_actual < $cantidad) {
                    return [
                        "exito" => false,
                        "msj" => "Stock insuficiente. Stock actual: " . $stock_actual
                    ];
                }
                
                // Si hay proyecto, verificar cantidad restante asignada
                if ($cod_proyecto) {
                    
                    $info_proyecto = $this->pro->obtenerCantidadesProyectoEtiqueta($cod_proyecto, $etiqueta_id);
                    
                    $cantidad_restante = $info_proyecto['cantidad_asignada'] - $info_proyecto['cantidad_entregada'];
                    
                    if ($cantidad_restante < $cantidad) {
                        return [
                            "exito" => false,
                            "msj" => "Cantidad excede lo asignado al proyecto. Restante: " . $cantidad_restante
                        ];
                    }
                }
            }

            $cantidad_anterior = parent::obtenerCantidadActualTamano($id_tamano);
            if ($tipo === 'entrada') {
                $cantidad_nueva = $cantidad_anterior + $cantidad;
            } elseif ($tipo === 'salida') {
                $cantidad_nueva = $cantidad_anterior - $cantidad;
            }
            
            $result = parent::registrarMovimiento($etiqueta_id, $tipo, $cantidad, $alto, $ancho, $precio, $motivo, $referencia, $observaciones, $cantidad_anterior, $cantidad_nueva, $cod_proyecto, $usuario_id, $fecha, $foto_url);
            
            if ($result) {
                parent::actualizarCantidadEtiquetaTamano($id_tamano, $cantidad_nueva);
                
                // Actualizar cantidad entregada en proyecto_etiquetas si hay proyecto
                if ($cod_proyecto && $tipo === 'salida') {
                    $this->pro->actualizarCantidadEntregada($cod_proyecto, $etiqueta_id, $cantidad);
                }
                
                return [
                    "exito" => true,
                    "msj" => "Movimiento registrado exitosamente"
                ];
            } else {
                return [
                    "exito" => false,
                    "msj" => "Error al registrar el movimiento"
                ];
            }
        } catch (Exception $e) {
            error_log("Error en movimiento: " . $e->getMessage());
            return [
                "exito" => false,
                "msj" => "Error registrando el movimiento: " . $e->getMessage()
            ];
        }
    }

    

    public function listarMovimientos($token) 
    {
        try {
            $validacion = $this->verificarAcceso($token);
                if (!$validacion['exito']) {
                    return $validacion;
            }

            $movimientos = parent::obtenerMovimientos();

            return [
                "exito" => true,
                "data" => $movimientos
            ];
        } catch (Exception $e) {
            error_log("Error en listarMovimientos: " . $e->getMessage());
            return [
                "exito" => false,
                "msj" => "Error obteniendo los movimientos"
            ];
        }
    }

    public function obtenerHistorialPorFecha($fecha, $token) 
    {
        try {
            $validacion = $this->verificarAcceso($token);
                if (!$validacion['exito']) {
                    return $validacion;
            }

            $movimientos = parent::obtenerMovimientosPorFecha($fecha);

            return [
                "exito" => true,
                "data" => $movimientos
            ];
        } catch (Exception $e) {
            error_log("Error en listarMovimientos: " . $e->getMessage());
            return [
                "exito" => false,
                "msj" => "Error obteniendo los movimientos"
            ];
        }
    }

    public function revertirMov($token, $movimiento_id) 
    {
        try {
            $validacion = $this->verificarAcceso($token);
                if (!$validacion['exito']) {
                    return $validacion;
            }

            $resultado = parent::revertirMovimiento($movimiento_id);

            if ($resultado['exito']) {
                return [
                    "exito" => true,
                    "msj" => "Movimiento revertido exitosamente"
                ];
            } else {
                return [
                    "exito" => false,
                    "msj" => $resultado['msj']
                ];
            }
        } catch (Exception $e) {
            error_log("Error en revertirMovimiento: " . $e->getMessage());
            return [
                "exito" => false,
                "msj" => "Error al revertir el movimiento"
            ];
        }
    }
    
}

// =============================================
// PROCESAMIENTO DE PETICIONES
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

// Función para guardar la foto
function guardarFotoBase64($foto_base64, $etiqueta_id, $tipo)
{
    try {
        if (empty($foto_base64)) {
            return null;
        }
        
        // Directorio de uploads
        $upload_dir = "../uploads/evidencias/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Generar nombre único para el archivo
        $timestamp = time();
        $filename = "evidencia_{$tipo}_{$etiqueta_id}_{$timestamp}.jpg";
        $filepath = $upload_dir . $filename;
        
        // Decodificar base64 (eliminar el prefijo data:image/jpeg;base64,)
        if (strpos($foto_base64, 'base64,') !== false) {
            $foto_base64 = explode('base64,', $foto_base64)[1];
        }
        
        $foto_data = base64_decode($foto_base64);
        
        // Guardar archivo
        if (file_put_contents($filepath, $foto_data)) {
            return "evidencias/" . $filename;
        }
        
        return null;
    } catch (Exception $e) {
        error_log("Error guardando foto: " . $e->getMessage());
        return null;
    }
}

// Permitir tanto POST como GET para datos públicos
if (isset($_POST["peticion"]) || isset($_GET["peticion"])) {
    $peticion = $_POST["peticion"] ?? $_GET["peticion"] ?? '';
    $mov = new MovimientosControllers();
    
    $respuesta = [
        "exito" => false,
        "msj" => "Petición no reconocida"
    ];

    try {
        switch ($peticion) {

            case 'registrar_entrada':

                $etiqueta_id     = $_POST['etiqueta_id'] ?? null;
                $cantidad        = $_POST['cantidad'] ?? null;
                $precio          = $_POST['precio_unitario'] ?? 0;
                $motivo          = $_POST['motivo'] ?? null;
                $referencia      = $_POST['referencia'] ?? null;
                $observaciones   = $_POST['observaciones'] ?? null;
                $cod_proyecto    = $_POST['cod_proyecto'] ?? null;
                $usuario_id      = $_POST['usuario_id'] ?? null;
                $fecha           = date('Y-m-d H:i:s');
                $token           = $_POST['token'] ?? null;
                $alto            = $_POST['alto'] ?? null;
                $ancho           = $_POST['ancho'] ?? null;
                $id_tamano       = $_POST['tamano_id'] ?? null;

                $resultado = $mov->movimiento(
                    $token,
                    $etiqueta_id,
                    'entrada',
                    $cantidad,
                    $alto,
                    $ancho,
                    $id_tamano,
                    $precio,
                    $motivo,
                    $referencia,
                    $observaciones,
                    0,
                    0,
                    $cod_proyecto,
                    $usuario_id,
                    $fecha
                );
                $respuesta = $resultado;
            break;

            case 'registrar_salida':
                $etiqueta_id     = $_POST['etiqueta_id'] ?? null;
                $cantidad        = $_POST['cantidad'] ?? null;
                $motivo          = $_POST['motivo'] ?? null;
                $referencia      = $_POST['referencia'] ?? null;
                $observaciones   = $_POST['observaciones'] ?? null;
                $cod_proyecto    = $_POST['proyecto_id'] ?? null;
                $usuario_id      = $_POST['usuario_id'] ?? null;
                $fecha           = date('Y-m-d H:i:s');
                $token           = $_POST['token'] ?? null;
                $alto            = $_POST['alto'] ?? null;
                $ancho           = $_POST['ancho'] ?? null;
                $id_tamano       = $_POST['tamano_id'] ?? null;
                $foto_base64     = $_POST['foto_base64'] ?? null;

                // Guardar foto si existe
                $foto_url = null;
                if (!empty($foto_base64)) {
                    $foto_url = guardarFotoBase64($foto_base64, $etiqueta_id, 'salida');
                }

                $resultado = $mov->movimiento(
                    $token,
                    $etiqueta_id,
                    'salida',
                    $cantidad,
                    $alto,
                    $ancho,
                    $id_tamano,
                    0,
                    $motivo,
                    $referencia,
                    $observaciones,
                    0,
                    0,
                    $cod_proyecto,
                    $usuario_id,
                    $fecha,
                    $foto_url
                );
                $respuesta = $resultado;
            break;

            case 'revertir_movimiento':
                $movimiento_id = $_POST['movimiento_id'] ?? null;
                $token = $_POST['token'] ?? '';
                $respuesta = $mov->revertirMov($token, $movimiento_id);
            break;

            case 'historial':
                $token = $_POST['token'] ?? $_GET['token'] ?? '';
                if(isset($_POST['fecha'])){
                    $fecha = $_POST['fecha'];
                    $respuesta = $mov->obtenerHistorialPorFecha($fecha, $token);
                    echo json_encode($respuesta);
                    exit;
                }
                $respuesta = $mov->listarMovimientos($token);
            break;
            
            case 'test':
                $respuesta = [
                    "exito" => true,
                    "msj" => "Controlador funcionando correctamente",
                    "post_data" => $_POST
                ];
            break;

            default:
                $respuesta = [
                    "exito" => false,
                    "msj" => "Petición no reconocida: " . $peticion
                ];
            break;
        }

    } catch (Exception $e) {
        error_log("Error en movimientos controller: " . $e->getMessage());
        $respuesta = [
            "exito" => false,
            "msj" => "Error interno del servidor: " . $e->getMessage()
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