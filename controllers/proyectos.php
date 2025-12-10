<?php
session_start();
date_default_timezone_set('America/Bogota');
setLocale(LC_ALL, "es_CO");

// Incluir los archivos necesarios
require_once "../models/Proyecto.php";
require_once "../middleware/AuthMiddleware.php";

class ProyectosControllers extends Proyecto
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

    public function crear($codigo, $nombre, $descripcion, $fecha_inicio, $etiquetas, $token, $usuario_id)
    {

        // 1. Si no hay usuario pero viene token, crearlo
        if (!isset($_SESSION['usuario'])) {
            return [
                "exito" => false,
                "msj" => "No hay sesion activa en servidor"
            ];
        }
        try {
            $validacion = $this->verificarAcceso($token);

            if (!$validacion['exito']) {
                return [
                    'exito' => false,
                    'msj' => $validacion['msj'],
                    'codigo' => $validacion['codigo']
                ];
            }

            $this->conexion->beginTransaction();

            $fecha_creacion = date('Y-m-d H:i:s');

            // 1. Crear el proyecto
            $parametros_proyecto = [
                ':codigo' => $codigo,
                ':nombre' => $nombre,
                ':descripcion' => $descripcion,
                ':fecha_inicio' => $fecha_inicio,
                ':fecha_creacion' => $fecha_creacion,
                ':usuario_id' => $usuario_id,
                ':estado' => 1
            ];

            $proyecto_id = parent::crearProyecto($parametros_proyecto);
            
            if (!$proyecto_id) {
                $this->conexion->rollBack();
                return [
                    "exito" => false,
                    "msj" => "Error al crear el proyecto"
                ];
            }

            // 2. Procesar las etiquetas del proyecto
            $etiquetas_array = json_decode($etiquetas, true);
            
            if (!empty($etiquetas_array)) {
                foreach ($etiquetas_array as $etiqueta) {
                    // Validar datos requeridos de la etiqueta
                    if (!isset($etiqueta['id']) || !isset($etiqueta['alto']) || 
                        !isset($etiqueta['ancho']) || !isset($etiqueta['cantidad_requerida'])) {
                        $this->conexion->rollBack();
                        throw new Exception("Datos incompletos en las etiquetas del proyecto");
                    }
                    $tamano_id = $etiqueta['tamano_id'] ?? null;

                    // Insertar etiqueta en el proyecto
                    $parametros_etiqueta = [
                        ':id_proyecto' => $proyecto_id['id'],
                        ':id_etiqueta' => $etiqueta['id'],
                        ':id_tamano' => $tamano_id,
                        ':alto' => $etiqueta['alto'],
                        ':ancho' => $etiqueta['ancho'],
                        ':cantidad' => $etiqueta['cantidad_requerida']
                    ];

                    $etiqueta_insertada = parent::agregarEtiquetaProyecto($parametros_etiqueta);
                    
                    if (!$etiqueta_insertada) {
                        $this->conexion->rollBack();
                        throw new Exception("Error al agregar etiqueta al proyecto");
                    }
                }
            }

            $this->conexion->commit();

            if ($_SESSION['usuario']['rol']!='admin') {
                require_once("enviar.php");

                $de = 'info@synertech.company';
                $para = 'indesign@synertech.com.co';
                $nombreDe = $_SESSION['usuario']['nombre_completo'];
                $nombrePara = "Indesign";
                $mensaje = "Se creó un nuevo proyecto con el código ". $codigo;
                $asunto = "Nuevo Proyecto";
                $contact_email = $_SESSION['usuario']['email'];

                $correo_enviado = enviar($de, $para, $nombreDe, $nombrePara, $mensaje, $asunto, $contact_email);
                
            }

            


            return [
                "exito" => true,
                "msj" => "Proyecto creado exitosamente",
                "proyecto_id" => $proyecto_id
            ];

        } catch (Exception $e) {
            $this->conexion->rollBack();
            error_log("Error en crearProyecto: " . $e->getMessage());
            return [
                "exito" => false,
                "msj" => "Error interno del servidor: " . $e->getMessage()
            ];
        }
    }


    public function listarProyects($token)
    {
        try {
            $validacion = $this->verificarAcceso($token);
            if (!$validacion['exito']) {
                return $validacion;
            }

            $data = parent::listarProyectos();
            
            if (is_array($data) && count($data) > 0) {
                // Procesar los datos para incluir información de etiquetas y tamaños
                $proyectosProcesados = [];
                
                foreach ($data as $proyecto) {
                    // Obtener las etiquetas del proyecto
                    $etiquetasProyecto = parent::obtenerEtiquetasProyecto($proyecto['id']);
                    
                    $proyectosProcesados[] = [
                        'id' => $proyecto['id'],
                        'codigo' => $proyecto['codigo'],
                        'nombre' => $proyecto['nombre'],
                        'descripcion' => $proyecto['descripcion'],
                        'fecha_inicio' => $proyecto['fecha_inicio'],
                        'fecha_creacion' => $proyecto['fecha_create'],
                        'estado' => $proyecto['estado'],
                        'usuario_id' => $proyecto['usuario_create'],
                        'usuario_nombre' => $proyecto['usuario_nombre'] ?? null,
                        'total_etiquetas' => $proyecto['total_etiquetas'] ?? 0,
                        'total_unidades' => $proyecto['total_unidades'] ?? 0,
                        'etiquetas' => $etiquetasProyecto
                    ];
                }

                return [
                    "exito" => true,
                    "msj" => "Listado de proyectos correcto",
                    "data" => $proyectosProcesados
                ];
            } else {
                return [
                    "exito" => true,
                    "msj" => "No se encontraron proyectos",
                    "data" => []
                ];
            }

        } catch (Exception $e) {
            error_log("Error en listarProyectos: " . $e->getMessage());
            return [
                "exito" => false,
                "msj" => "Error listando proyectos: " . $e->getMessage()
            ];
        }
    }

    public function obtenerProyecto($id, $token)
    {
        try {
            $validacion = $this->verificarAcceso($token);
            if (!$validacion['exito']) {
                return $validacion;
            }

            $proyecto = parent::obtenerProyectoPorId($id);
            
            if ($proyecto) {
                $etiquetas = parent::obtenerEtiquetasProyecto($id);
                
                if ($proyecto[0]['estado'] == 3) {
                    $firmas = parent::obtenerFirmas($id);
                }else{
                    $firmas = [];
                }

                return [
                    "exito" => true,
                    "msj" => "Proyecto obtenido correctamente",
                    "proyecto" => $proyecto,
                    "etiquetas" => $etiquetas,
                    "firmas" => $firmas
                ];
            } else {
                return [
                    "exito" => false,
                    "msj" => "Proyecto no encontrado"
                ];
            }

        } catch (Exception $e) {
            error_log("Error en obtenerProyecto: " . $e->getMessage());
            return [
                "exito" => false,
                "msj" => "Error obteniendo el proyecto"
            ];
        }
    }

    public function eliminarProyecto($id, $token)
    {
        try {
            $validacion = $this->verificarAcceso($token);
            if (!$validacion['exito']) {
                return [
                    'exito' => false,
                    'msj' => $validacion['msj'],
                    'codigo' => $validacion['codigo']
                ];
            }

            $this->conexion->beginTransaction();

            // Primero eliminar las etiquetas del proyecto
            $etiquetas_eliminadas = parent::eliminarEtiquetasProyecto($id);
            if (!$etiquetas_eliminadas) {
                $this->conexion->rollBack();
                return [
                    "exito" => false,
                    "msj" => "Error al eliminar las etiquetas del proyecto"
                ];
            }

            // Luego eliminar el proyecto
            $proyecto_eliminado = parent::eliminarProyect($id);
            if (!$proyecto_eliminado) {
                $this->conexion->rollBack();
                return [
                    "exito" => false,
                    "msj" => "Error al eliminar el proyecto"
                ];
            }

            $this->conexion->commit();

            return [
                "exito" => true,
                "msj" => "Proyecto eliminado exitosamente"
            ];

        } catch (Exception $e) {
            $this->conexion->rollBack();
            error_log("Error en eliminarProyecto: " . $e->getMessage());
            return [
                "exito" => false,
                "msj" => "Error interno del servidor"
            ];
        }
    }

    public function editarProyect($id, $codigo, $nombre, $descripcion, $fecha_inicio, $fecha_update, $token, $etiquetas)
    {
        try {
            $validacion = $this->verificarAcceso($token);
            if (!$validacion['exito']) {
                return [
                    'exito' => false,
                    'msj' => $validacion['msj'],
                    'codigo' => $validacion['codigo']
                ];
            }

            $this->conexion->beginTransaction();

            $fecha_actualizacion = date('Y-m-d H:i:s');

            // Actualizar información básica de la etiqueta
            $parametros_etiqueta = [
                ':id' => $id,
                ':codigo' => $codigo,
                ':nombre' => $nombre,
                ':descripcion' => $descripcion,
                ':fecha_inicio' => $fecha_inicio,
                ':fecha_update' => $fecha_update
            ];

            $proyecto_actualizado = parent::actualizarProyecto($parametros_etiqueta);
            

            if (!$proyecto_actualizado['exito']) {
                $this->conexion->rollBack();
                return [
                    "exito" => false,
                    "msj" => "Error al actualizar la información del proyecto"
                ];
            }

            // Eliminar tamaños existentes
            $eliminacion_epro = parent::eliminarEtiquetasPro($id);
            if (!$eliminacion_epro) {
                $this->conexion->rollBack();
                return [
                    "exito" => false,
                    "msj" => "Error al eliminar las etiquetas existentes"
                ];
            }

            // Insertar los nuevos tamaños
            $etiquetas = json_decode($etiquetas, true);
            
            if (!empty($etiquetas)) {
                foreach ($etiquetas as $etiqueta) {
                    // Validar datos requeridos de la etiqueta
                    if (!isset($etiqueta['id']) || !isset($etiqueta['alto']) || 
                        !isset($etiqueta['ancho']) || !isset($etiqueta['cantidad_requerida'])) {
                        $this->conexion->rollBack();
                        throw new Exception("Datos incompletos en las etiquetas del proyecto");
                    }
                    $tamano_id = $etiqueta['tamano_id'] ?? null;

                    // Insertar etiqueta en el proyecto
                    $parametros_etiqueta = [
                        ':id_proyecto' => $id,
                        ':id_etiqueta' => $etiqueta['id'],
                        ':id_tamano' => $tamano_id,
                        ':alto' => $etiqueta['alto'],
                        ':ancho' => $etiqueta['ancho'],
                        ':cantidad' => $etiqueta['cantidad_requerida']
                    ];

                    $etiqueta_insertada = parent::agregarEtiquetaProyecto($parametros_etiqueta);
                    
                    if (!$etiqueta_insertada) {
                        $this->conexion->rollBack();
                        throw new Exception("Error al agregar etiqueta al proyecto");
                    }
                }
            }

            $this->conexion->commit();

            return [
                "exito" => true,
                "msj" => "Etiqueta editada exitosamente",
                "etiqueta" => $proyecto_actualizado
            ];

        } catch (Exception $e) {
            $this->conexion->rollBack();
            error_log("Error en editar etiqueta: " . $e->getMessage());
            return [
                "exito" => false,
                "msj" => "Error interno del servidor: " . $e->getMessage()
            ];
        }
    }

    public function finalizar($nombre, $comentarios, $proyecto_id, $firma_base64, $token)
    {
        try {
            $validacion = $this->verificarAcceso($token);
            if (!$validacion['exito']) {
                return [
                    'exito' => false,
                    'msj' => $validacion['msj'],
                    'codigo' => $validacion['codigo']
                ];
            }

            $this->conexion->beginTransaction();

            // Directorio de uploads
            $upload_dir = "../uploads/firmas/";
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generar nombre único para el archivo
            $timestamp = time();
            $filename = "firma_{$proyecto_id}_{$timestamp}.png";
            $filepath = $upload_dir . $filename;
            
            // Decodificar base64 (eliminar el prefijo data:image/jpeg;base64,)
            if (strpos($firma_base64, 'base64,') !== false) {
                $foto_base64 = explode('base64,', $firma_base64)[1];
            }
            
            $foto_data = base64_decode($foto_base64);
            
            // Guardar archivo
            if (file_put_contents($filepath, $foto_data)) {
                $nombre_archivo = "firmas/" . $filename;
            }
            $fecha = date('Y-m-d H:i:s');
            // Luego actualizar estado del proyecto
            $proyecto_act = parent::finalizarProyect($proyecto_id, $fecha);
            if (!$proyecto_act) {
                $this->conexion->rollBack();
                return [
                    "exito" => false,
                    "msj" => "Error al finalizar el proyecto"
                ];
            }else{
                $params = [
                    ":nombre" => $nombre,
                    ":comentarios" => $comentarios,
                    ":idPro" => $proyecto_id,
                    ":firma" => $nombre_archivo,
                    ":fecha" => $fecha
                ];
                
                $firmar = parent::guardarFirma($params);

                if (!$firmar) {
                    $this->conexion->rollBack();
                    return [
                        "exito" => false,
                        "msj" => "Error al guardar firma el proyecto"
                    ];
                }
            }

            $this->conexion->commit();

            return [
                "exito" => true,
                "msj" => "Proyecto finalizado exitosamente"
            ];

        } catch (Exception $e) {
            $this->conexion->rollBack();
            error_log("Error en eliminarProyecto: " . $e->getMessage());
            return [
                "exito" => false,
                "msj" => "Error interno del servidor"
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
    $controller = new ProyectosControllers();
    
    $respuesta = [
        "exito" => false,
        "msj" => "Petición no reconocida"
    ];

    try {
        switch ($peticion) {
            case 'listar':
                $token = $_POST['token'] ?? $_GET['token'] ?? '';
                $respuesta = $controller->listarProyects($token);
            break;

            case 'crear':
                $codigo = $_POST['codigo'] ?? '';
                $nombre = $_POST['nombre'] ?? '';
                $descripcion = $_POST['descripcion'] ?? '';
                $fecha_inicio = $_POST['fecha_inicio'] ?? '';
                $etiquetas = $_POST['etiquetas'] ?? '[]';
                $token = $_POST['token'] ?? $_GET['token'] ?? '';
                $usuario_id = $_POST['usuario_id'] ?? '';

                // Validaciones básicas
                if (empty($codigo) || empty($nombre)) {
                    $respuesta = [
                        "exito" => false,
                        "msj" => "Código y nombre del proyecto son requeridos"
                    ];
                    break;
                }

                $respuesta = $controller->crear(
                    $codigo, 
                    $nombre, 
                    $descripcion, 
                    $fecha_inicio, 
                    $etiquetas, 
                    $token, 
                    $usuario_id
                );
            break;

            case 'obtener':
                $id = $_POST['id'] ?? $_GET['id'] ?? '';
                $token = $_POST['token'] ?? $_GET['token'] ?? '';
                $respuesta = $controller->obtenerProyecto($id, $token);
            break;

            case 'eliminar':
                $id = $_POST['id'] ?? $_GET['id'] ?? '';
                $token = $_POST['token'] ?? $_GET['token'] ?? '';
                $respuesta = $controller->eliminarProyecto($id, $token);
            break;

            case 'editar':
                $codigo = $_POST['codigo'] ?? '';
                $nombre = $_POST['nombre'] ?? '';
                $descripcion = $_POST['descripcion'] ?? '';
                $fecha_inicio = $_POST['fecha_inicio'] ?? '';
                $fecha_update = date('Y-m-d H:i:s');
                $token = $_POST['token'] ?? $_GET['token'] ?? '';
                $id = $_POST['id'];
                $etiquetas = $_POST['etiquetas'] ?? '[]';
                

                $respuesta = $controller->editarProyect($id, $codigo, $nombre, $descripcion, $fecha_inicio, $fecha_update, $token, $etiquetas);

            break;

            case 'finalizar_con_firma':
                $nombre = $_POST['firmante_nombre'];
                $comentarios = $_POST['comentarios'];
                $proyecto = $_POST['proyecto_id'];
                $firma = $_POST['firma'];
                $token = $_POST['token'];

                $respuesta = $controller->finalizar($nombre, $comentarios, $proyecto, $firma, $token);
            break;

            default:
                $respuesta = [
                    "exito" => false,
                    "msj" => "Petición no reconocida: " . $peticion
                ];
                break;
        }

    } catch (Exception $e) {
        error_log("Error en proyectos controller: " . $e->getMessage());
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