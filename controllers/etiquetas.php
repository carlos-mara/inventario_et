<?php
session_start();
date_default_timezone_set('America/Bogota');
setLocale(LC_ALL, "es_CO");

// Incluir los archivos necesarios
require_once "../models/Etiqueta.php";
require_once "../middleware/AuthMiddleware.php";

class EtiquetasControllers extends Etiqueta
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

    public function nuevaEtiqueta($nombre, $descripcion, $foto, $stock_minimo, $categoria_id, $token, $usuario_id, $tamanos)
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

            $fecha_creacion = date('Y-m-d H:i:s');

            $parametros = [
                ':nombre' => $nombre,
                ':descripcion' => $descripcion,
                ':foto_url' => $foto,
                ':stock_minimo' => $stock_minimo,
                ':categoria_id' => $categoria_id,
                ':fecha_creacion' => $fecha_creacion,
                ':usuario_id' => $usuario_id
            ];

            $data = parent::crear($parametros);
            $tamanos = json_decode($tamanos, true);
            
            if ($data) {
                // Insertar los tamaños asociados
                foreach ($tamanos as $index => $tamano) {
                    
                    // Validar que los datos requeridos existan
                    if (!isset($tamano['alto']) || !isset($tamano['ancho'])) {
                        $this->conexion->rollBack();
                        throw new Exception("Datos incompletos en el tamaño en la posición $index");
                    }

                    $parametros_tamano = [
                        ':etiqueta_id' => $data['id'],
                        ':alto' => $tamano['alto'],
                        ':ancho' => $tamano['ancho'],
                        ':fecha_creacion' => $fecha_creacion
                    ];

                    $tamano_d = parent::insertarTamanos($parametros_tamano);
                    if (!$tamano_d) {
                        $this->conexion->rollBack();
                        throw new Exception("Error al insertar tamaños de etiqueta en la posición $index");
                    }
                }

                $this->conexion->commit();

                return [
                    "exito" => true,
                    "msj" => "Etiqueta creada exitosamente",
                    "etiqueta" => $data
                ];
            } else {
                return [
                    "exito" => false,
                    "msj" => "Error al crear la etiqueta"
                ];
            }

        } catch (Exception $e) {
            $this->conexion->rollBack();

            error_log("Error en nuevaEtiqueta: " . $e->getMessage());
            return [
                "exito" => false,
                "msj" => "Error interno del servidor"
            ];
        }
    }

    public function listarEtiquetas($token)
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
                    "msj" => "Listado de etiquetas correcto",
                    "data" => $data
                ];
            } else {
                return [
                    "exito" => true,
                    "msj" => "No se encontraron etiquetas",
                    "data" => []
                ];
            }

        } catch (Exception $e) {
            error_log("Error en listarEtiquetas: " . $e->getMessage());
            return [
                "exito" => false,
                "msj" => "Error listando etiquetas"
            ];
        }
    }

    public function obtenerEtiqueta($id, $token)
    {
        try {
            $validacion = $this->verificarAcceso($token);
            if (!$validacion['exito']) {
                return $validacion;
            }

            $data = parent::obtenerPorId($id);
            
            if ($data) {
                
                foreach($data['etiqueta'] as $row) {
                    $tamanos[] = [
                        'idTamano' => $row['idTamano'],
                        'alto' => $row['alto'],
                        'ancho' => $row['ancho']
                    ];
                }

                $dat['tamanos'] = $tamanos;
                $dat['info'] = $data['etiqueta'][0];

                return [
                    "exito" => true,
                    "msj" => "Etiqueta obtenida correctamente",
                    "etiqueta" => $dat
                ];
            } else {
                return [
                    "exito" => false,
                    "msj" => "Etiqueta no encontrada"
                ];
            }

        } catch (Exception $e) {
            error_log("Error en obtenerEtiqueta: " . $e->getMessage());
            return [
                "exito" => false,
                "msj" => "Error obteniendo la etiqueta"
            ];
        }
    }

public function editarEtiqueta($id, $nombre, $descripcion, $foto, $stock_minimo, $categoria_id, $token, $tamanos)
{
    try {
        $this->conexion->beginTransaction();
        $fecha_actualizacion = date('Y-m-d H:i:s');

        // 1. Actualizar etiqueta principal
        $etiqueta_actualizada = $this->actualizarEtiqueta([
            ':id' => $id, 
            ':nombre' => $nombre, 
            ':descripcion' => $descripcion,
            ':foto_url' => $foto, 
            ':stock_minimo' => $stock_minimo,
            ':categoria_id' => $categoria_id, 
            ':fecha' => $fecha_actualizacion
        ]);
        
        if (!$etiqueta_actualizada['exito']) {
            $this->conexion->rollBack();
            return $etiqueta_actualizada;
        }

        // 2. Obtener tamaños actuales
        $tamanos_actuales = $this->obtenerTamanosActuales($id);
        
        // 3. Identificar qué tamaños están en proyectos (PROTEGIDOS)
        $tamanos_protegidos = [];
        foreach ($tamanos_actuales as $tamano_actual) {
            $verificacion = $this->tieneTamanosEnProyectos($tamano_actual['id']);
            if ($verificacion['exito'] && $verificacion['count'] > 0) {
                $tamanos_protegidos[] = $tamano_actual['id'];
            }
        }
        
        // 4. Procesar los tamaños nuevos del formulario
        $tamanos_nuevos = json_decode($tamanos, true) ?: [];
        $tamanos_a_eliminar = [];
        $tamanos_a_actualizar = [];
        $tamanos_a_insertar = [];
        
        foreach ($tamanos_nuevos as $tamano) {
            // Validar
            if (!isset($tamano['alto']) || !isset($tamano['ancho'])) {
                $this->conexion->rollBack();
                return [
                    "exito" => false,
                    "msj" => "Datos incompletos en tamaño"
                ];
            }
            
            // Si tiene ID, es un tamaño existente
            if (isset($tamano['id']) && $tamano['id'] > 0) {
                // Verificar si está protegido
                if (in_array($tamano['id'], $tamanos_protegidos)) {
                    // Tamaño protegido: NO se puede modificar ni eliminar
                    // Solo verificar que no quiera cambiar dimensiones
                    // Buscar el tamaño actual para comparar dimensiones
                    foreach ($tamanos_actuales as $t_actual) {
                        if ($t_actual['id'] == $tamano['id']) {
                            if ($t_actual['alto'] != $tamano['alto'] || $t_actual['ancho'] != $tamano['ancho']) {
                                $this->conexion->rollBack();
                                return [
                                    "exito" => false,
                                    "msj" => "No se pueden modificar las dimensiones de un tamaño asignado a proyectos (ID: {$tamano['id']})"
                                ];
                            }
                            break;
                        }
                    }
                    // Tamaño protegido se mantiene, no se hace nada
                } else {
                    // Tamaño NO protegido: se puede actualizar
                    $tamanos_a_actualizar[] = $tamano;
                }
            } else {
                // Sin ID: es un tamaño NUEVO
                $tamanos_a_insertar[] = $tamano;
            }
        }
        
        // 5. Identificar tamaños que se quieren ELIMINAR
        // (tamaños actuales que NO vinieron en el array)
        foreach ($tamanos_actuales as $t_actual) {
            $encontrado = false;
            foreach ($tamanos_nuevos as $t_nuevo) {
                if (isset($t_nuevo['id']) && $t_nuevo['id'] == $t_actual['id']) {
                    $encontrado = true;
                    break;
                }
            }
            
            if (!$encontrado) {
                // Verificar si está protegido
                if (in_array($t_actual['id'], $tamanos_protegidos)) {
                    // No se puede eliminar tamaño protegido
                    $this->conexion->rollBack();
                    return [
                        "exito" => false,
                        "msj" => "No se puede eliminar un tamaño asignado a proyectos (ID: {$t_actual['id']})"
                    ];
                } else {
                    // Se puede eliminar
                    $tamanos_a_eliminar[] = $t_actual['id'];
                }
            }
        }
        
        // 6. Ejecutar operaciones
        
        // A) Eliminar tamaños no protegidos
        if (!empty($tamanos_a_eliminar)) {
            foreach($tamanos_a_eliminar AS $tamanos_eli){
                $param=[
                    ":id" => $tamanos_eli
                ];
                $this->eliminarTamanosPorIds($param);
            }
            
        }
        
        // B) Actualizar tamaños no protegidos
        foreach ($tamanos_a_actualizar as $tamano) {
            // Usar tu método existente actualizarEtiqueta como base para crear un método
            // O crear uno nuevo simple:
            $sql = "UPDATE etiqueta_tamanos 
                    SET alto = :alto, ancho = :ancho, fecha_creacion = :fecha 
                    WHERE id = :id";
            
            $parametros = [
                ':id' => $tamano['id'],
                ':alto' => $tamano['alto'],
                ':ancho' => $tamano['ancho'],
                ':fecha' => $fecha_actualizacion
            ];
            
            $this->conexion->ejecutarConParametros($sql, $parametros);
        }
        
        // C) Insertar tamaños nuevos usando tu método existente
        foreach ($tamanos_a_insertar as $tamano) {
            $this->insertarTamanos([
                ':etiqueta_id' => $id,
                ':alto' => $tamano['alto'],
                ':ancho' => $tamano['ancho'],
                ':fecha_creacion' => $fecha_actualizacion
            ]);
        }
        
        $this->conexion->commit();
        
        // Mensaje informativo
        $mensaje = "Etiqueta actualizada exitosamente";
        if (!empty($tamanos_protegidos)) {
            $mensaje .= ". " . count($tamanos_protegidos) . " tamaño(s) se mantuvieron sin cambios por estar en proyectos";
        }
        
        return [
            "exito" => true, 
            "msj" => $mensaje,
            "tamanos_protegidos" => $tamanos_protegidos
        ];

    } catch (Exception $e) {
        $this->conexion->rollBack();
        error_log("Error en editarEtiqueta: " . $e->getMessage());
        return [
            "exito" => false,
            "msj" => "Error interno del servidor"
        ];
    }
}



    public function eliminarEtiqueta($id, $token)
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

            $data = parent::eliminar($id);
            
            if ($data) {
                return [
                    "exito" => true,
                    "msj" => "Etiqueta eliminada exitosamente"
                ];
            } else {
                return [
                    "exito" => false,
                    "msj" => "Error al eliminar la etiqueta"
                ];
            }

        } catch (Exception $e) {
            error_log("Error en eliminar etiqueta: " . $e->getMessage());
            return [
                "exito" => false,
                "msj" => "Error interno del servidor"
            ];
        }
    }
    
    public function obtenerTamanos($etiqueta_id, $token)
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

            $data = parent::obtenerTamanosPorEtiqueta($etiqueta_id);
            
            if (is_array($data)) {
                return [
                    "exito" => true,
                    "msj" => "Tamaños obtenidos correctamente",
                    "tamanos" => $data['tamanos']
                ];
            } else {
                return [
                    "exito" => false,
                    "msj" => "No se encontraron tamaños para la etiqueta"
                ];
            }

        } catch (Exception $e) {
            error_log("Error en obtenerTamanos: " . $e->getMessage());
            return [
                "exito" => false,
                "msj" => "Error obteniendo los tamaños"
            ];
        }
    }

    public function listar_por_proyecto($id, $token){
        try {
            $validacion = $this->verificarAcceso($token);
            if (!$validacion['exito']) {
                return $validacion;
            }

            $data = parent::listarPorProyecto($id);
            
            if (is_array($data) && count($data) > 0) {
                return [
                    "exito" => true,
                    "msj" => "Listado de etiquetas de proyecto correcto",
                    "data" => $data
                ];
            } else {
                return [
                    "exito" => true,
                    "msj" => "No se encontraron etiquetas del proyecto",
                    "data" => []
                ];
            }

        } catch (Exception $e) {
            error_log("Error en listarEtiquetas: " . $e->getMessage());
            return [
                "exito" => false,
                "msj" => "Error listando etiquetas"
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
    $et = new EtiquetasControllers();
    
    $respuesta = [
        "exito" => false,
        "msj" => "Petición no reconocida"
    ];

    try {
        switch ($peticion) {
            case 'listar':
                $token = $_POST['token'] ?? $_GET['token'] ?? '';
                $respuesta = $et->listarEtiquetas($token);
            break;

            case 'listar_por_proyecto':
                $id_proyecto = $_POST['proyecto_id'];
                $token = $_POST['token'];

                $respuesta = $et->listar_por_proyecto($id_proyecto, $token);
            break;

            case 'crear':
                
                $target_dir = "../uploads/"; // Directorio donde se guardarán los archivos
                $nombre = $_POST['nombre'] ?? '';
                $descripcion = $_POST['descripcion'] ?? '';
                $categoria_id = $_POST['categoria_id'] ?? '';
                $token = $_POST['token'] ?? $_GET['token'] ?? '';
                $stock_minimo = $_POST['stock_minimo'] ?? 0;
                $usuario_id = $_POST['usuario_id'] ?? '';
                $tamanos = $_POST['tamanos'];
                // Manejo de la subida del archivo
                if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                    $foto = $_FILES['foto'];
                    $nameFoto = time() . '_' . basename($foto["name"]);
                    $target_file = $target_dir . $nameFoto;
                    if (move_uploaded_file($foto["tmp_name"], $target_file)) {
                        // Archivo subido exitosamente
                        $respuesta = $et->nuevaEtiqueta($nombre, $descripcion, $nameFoto, $stock_minimo, $categoria_id, $token, $usuario_id, $tamanos);
                    } else {
                        $respuesta = [
                            "exito" => false,
                            "msj" => "Error al subir la foto"
                        ];
                    }
                } else {
                    $respuesta = [
                        "exito" => false,
                        "msj" => "No se ha proporcionado una foto válida"
                    ];
                }
            break;
            
            case 'editar':
                
                $target_dir = "../uploads/"; // Directorio donde se guardarán los archivos
                $nombre = $_POST['nombre'] ?? '';
                $descripcion = $_POST['descripcion'] ?? '';
                $categoria_id = $_POST['categoria_id'] ?? '';
                $token = $_POST['token'] ?? $_GET['token'] ?? '';
                $stock_minimo = $_POST['stock_minimo'] ?? 0;
                $cambiar_foto = $_POST['cambiar_foto'];
                $id = $_POST['id'];
                $tamanos = $_POST['tamanos'];

                if ($cambiar_foto===true || $cambiar_foto==='true') {
                    // Manejo de la subida del archivo
                    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                        $foto = $_FILES['foto'];
                        $nameFoto = time() . '_' . basename($foto["name"]);
                        $target_file = $target_dir . $nameFoto;
                        if (move_uploaded_file($foto["tmp_name"], $target_file)) {
                            // Archivo subido exitosamente
                            $respuesta = $et->editarEtiqueta($id, $nombre, $descripcion, $nameFoto, $stock_minimo,  $categoria_id, $token, $tamanos);
                        } else {
                            $respuesta = [
                                "exito" => false,
                                "msj" => "Error al subir la foto"
                            ];
                        }
                    } else {
                        $respuesta = [
                            "exito" => false,
                            "msj" => "No se ha proporcionado una foto válida"
                        ];
                    }
                } else {
                    $foto_anterior = $_POST['foto_anterior'] ?? '';
                    $respuesta = $et->editarEtiqueta($id, $nombre, $descripcion, $foto_anterior, $stock_minimo, $categoria_id, $token, $tamanos);
                }
            break;

            case 'eliminar':
                $id = $_POST['id'] ?? $_GET['id'] ?? '';
                $token = $_POST['token'] ?? $_GET['token'] ?? '';
                $respuesta = $et->eliminarEtiqueta($id, $token);
            break;

            case 'obtener':
                $id = $_POST['id'] ?? $_GET['id'] ?? '';
                $token = $_POST['token'] ?? $_GET['token'] ?? '';
                $respuesta = $et->obtenerEtiqueta($id, $token);
            break;

            case 'consultar_tamanos':
                $etiqueta_id = $_POST['etiqueta_id'] ?? '';
                $token = $_POST['token'] ?? $_GET['token'] ?? '';

                $respuesta = $et->obtenerTamanos($etiqueta_id, $token);
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