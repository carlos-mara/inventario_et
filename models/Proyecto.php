<?php 
require_once "../conf/conexion.php";
class Proyecto {
    protected $conexion;
    
    public function __construct() {
        $this->conexion = new Conexion();
    }
    
    public function crearProyecto($parametros) {
        try {
            $sql = "INSERT INTO proyectos (codigo, nombre, descripcion, fecha_inicio, fecha_create, estado, usuario_create) 
                VALUES (:codigo, :nombre, :descripcion, :fecha_inicio, :fecha_creacion, :estado, :usuario_id)";

            $resultado = $this->conexion->ejecutarConParametros($sql, $parametros);

            if ($resultado->rowCount() > 0) {
                return [
                    "id" => $this->conexion->ultimoRegistro()
                ];
            } else {
                return false;
            }
            
        } catch (Exception $e) {
            error_log("Error creando etiqueta: " . $e->getMessage());
            return false;
        }
    }

    public function agregarEtiquetaProyecto($parametros) {
        try {
            $sql = "INSERT INTO proyecto_etiquetas (id_proyecto, id_etiqueta, id_tamano, cantidad, alto, ancho) 
                VALUES (:id_proyecto, :id_etiqueta, :id_tamano, :cantidad, :alto, :ancho)";

            $this->conexion->ejecutarConParametros($sql, $parametros);
            return true;
        } catch (Exception $e) {
            error_log("Error agregando etiqueta al proyecto: " . $e->getMessage());
            return false;
        }
    }

    // Listar proyectos con información básica
    public function listarProyectos() {
        $sql = "SELECT 
                    p.*,
                    u.username as usuario_nombre,
                    COUNT(pe.id) as total_etiquetas,
                    COALESCE(SUM(pe.cantidad), 0) as total_unidades
                FROM proyectos p
                LEFT JOIN usuarios u ON p.usuario_create = u.id
                LEFT JOIN proyecto_etiquetas pe ON p.id = pe.id_proyecto
                WHERE p.estado != 2
                GROUP BY p.id
                ORDER BY p.fecha_create DESC;";
        
        $resultado = $this->conexion->ejecutar($sql);
        return $resultado->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener etiquetas de un proyecto específico con información de tamaños
    public function obtenerEtiquetasProyecto($proyecto_id) {
        $sql = "SELECT 
                    pe.*,
                    e.nombre as etiqueta_nombre,
                    e.descripcion as etiqueta_descripcion,
                    e.foto_url,
                    e.categoria_id,
                    c.nombre as categoria_nombre,
                    et.stock_actual,
                    et.alto as tamano_alto_actual,
                    et.ancho as tamano_ancho_actual
                FROM proyecto_etiquetas pe
                JOIN etiquetas e ON pe.id_etiqueta = e.id
                LEFT JOIN categorias c ON e.categoria_id = c.id
                LEFT JOIN etiqueta_tamanos et ON pe.id_tamano = et.id
                WHERE pe.id_proyecto = :proyecto_id
                ORDER BY e.nombre, pe.alto, pe.ancho";
        
        $result = $this->conexion->ejecutarConParametros($sql, [':proyecto_id' => $proyecto_id]);
        
        // Procesar el resultado para una estructura más clara
        $etiquetasProcesadas = [];
        foreach ($result as $row) {
            $etiquetasProcesadas[] = [
                'id' => $row['id'],
                'etiqueta_id' => $row['id_etiqueta'],
                'etiqueta_nombre' => $row['etiqueta_nombre'],
                'etiqueta_descripcion' => $row['etiqueta_descripcion'],
                'foto_url' => $row['foto_url'],
                'categoria_id' => $row['categoria_id'],
                'categoria_nombre' => $row['categoria_nombre'],
                'tamano_id' => $row['id_tamano'],
                'alto' => $row['alto'],
                'ancho' => $row['ancho'],
                'cantidad' => $row['cantidad'],
                'cantidad_entregada' => $row['cantidad_entregada'],
                'stock_actual' => $row['stock_actual'],
                'tamano_alto_actual' => $row['tamano_alto_actual'],
                'tamano_ancho_actual' => $row['tamano_ancho_actual'],
                
            ];
        }
        
        return $etiquetasProcesadas;
    }
    // Obtener etiquetas de un proyecto específico con información de tamaños
    public function obtenerFirmas($proyecto_id) {
        $sql = "SELECT *
                FROM proyecto_firmas pf
                WHERE pf.id_proyecto = :proyecto_id";
        
        $result = $this->conexion->ejecutarConParametros($sql, [':proyecto_id' => $proyecto_id]);

        return $result->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener proyecto específico por ID
    public function obtenerProyectoPorId($id) {
        $sql = "SELECT 
                    p.*,
                    e.nombre as estado_nombre,
                    u.username as usuario_nombre,
                    COUNT(pe.id) as total_etiquetas,
                    COALESCE(SUM(pe.cantidad), 0) as total_unidades
                FROM proyectos p
                LEFT JOIN usuarios u ON p.usuario_create = u.id
                LEFT JOIN proyecto_etiquetas pe ON p.id = pe.id_proyecto
                JOIN estados e on p.estado = e.id
                WHERE p.id = :id AND p.estado != 2
                GROUP BY p.id;";
        
        $result = $this->conexion->ejecutarConParametros($sql, [':id' => $id]);
        
        return $result->fetchAll(PDO::FETCH_ASSOC);
    }

    // Versión alternativa si prefieres una sola consulta para listar proyectos con etiquetas
    public function listarProyectosCompleto() {
        $sql = "SELECT 
                    p.*,
                    u.nombre as usuario_nombre,
                    pe.id as pe_id,
                    pe.alto,
                    pe.ancho,
                    pe.cantidad,
                    pe.id_tamano,
                    e.id as etiqueta_id,
                    e.nombre as etiqueta_nombre,
                    e.foto_url,
                    c.nombre as categoria_nombre,
                    et.stock_actual
                FROM proyectos p
                LEFT JOIN usuarios u ON p.usuario_id = u.id
                LEFT JOIN proyecto_etiquetas pe ON p.id = pe.id_proyecto
                LEFT JOIN etiquetas e ON pe.id_etiqueta = e.id
                LEFT JOIN categorias c ON e.categoria_id = c.id
                LEFT JOIN etiqueta_tamanos et ON pe.id_tamano = et.id
                WHERE p.estado != 'eliminado'
                ORDER BY p.fecha_create DESC, e.nombre";
        
        $result = $this->conexion->ejecutar($sql);
        
        // Agrupar por proyecto
        $proyectos = [];
        foreach ($result as $row) {
            $proyectoId = $row['id'];
            
            if (!isset($proyectos[$proyectoId])) {
                $proyectos[$proyectoId] = [
                    'id' => $row['id'],
                    'codigo' => $row['codigo'],
                    'nombre' => $row['nombre'],
                    'descripcion' => $row['descripcion'],
                    'fecha_inicio' => $row['fecha_inicio'],
                    'fecha_creacion' => $row['fecha_create'],
                    'estado' => $row['estado'],
                    'usuario_id' => $row['usuario_create'],
                    'usuario_nombre' => $row['usuario_nombre'],
                    'etiquetas' => []
                ];
            }
            
            // Agregar etiqueta si existe
            if ($row['pe_id']) {
                $proyectos[$proyectoId]['etiquetas'][] = [
                    'id' => $row['pe_id'],
                    'etiqueta_id' => $row['etiqueta_id'],
                    'etiqueta_nombre' => $row['etiqueta_nombre'],
                    'foto_url' => $row['foto_url'],
                    'categoria_nombre' => $row['categoria_nombre'],
                    'tamano_id' => $row['id_tamano'],
                    'alto' => $row['alto'],
                    'ancho' => $row['ancho'],
                    'cantidad' => $row['cantidad'],
                    'stock_actual' => $row['stock_actual']
                ];
            }
        }
        
        return array_values($proyectos);
    }

    public function eliminarProyect($id) {
        try {
            // Aquí podrías agregar lógica para validar el token si es necesario
            
            $sql = "UPDATE proyectos SET estado = 2 WHERE id = :id";
            $resultado = $this->conexion->ejecutarConParametros($sql, [':id' => $id]);
            
            return $resultado->rowCount() > 0;
        } catch (Exception $e) {
            error_log("Error eliminando proyecto: " . $e->getMessage());
            return false;
        }
    }

    public function eliminarEtiquetasProyecto($id) {
        try {
            $sql = "DELETE FROM proyecto_etiquetas WHERE id_proyecto = :id_proyecto";
            $resultado = $this->conexion->ejecutarConParametros($sql, [':id_proyecto' => $id]);

            return $resultado->rowCount() > 0;
        } catch (Exception $e) {
            error_log("Error eliminando proyecto: " . $e->getMessage());
            return false;
        }
    }

    public function actualizarProyecto($parametros) {
        try {
            $sql = "UPDATE proyectos
                    SET codigo = :codigo,
                        nombre = :nombre,
                        descripcion = :descripcion,
                        fecha_inicio = :fecha_inicio,
                        fecha_update = :fecha_update
                    WHERE id = :id";
            
            
            $resultado = $this->conexion->ejecutarConParametros($sql, $parametros);
            
            if ($resultado->rowCount() > 0) {
                return [
                    "exito" => true,
                    "msj" => "Proyecto editado exitosamente"
                ];
            } else {
                return [
                    "exito" => false,
                    "msj" => "No se realizaron cambios"
                ];
            }
            
        } catch (Exception $e) {
            error_log("Error editando etiqueta: " . $e->getMessage());
            return [
                "exito" => false,
                "msj" => "Error interno del servidor"
            ];
        }
    }

    public function eliminarEtiquetasPro($id){
        try {
            $sql = "DELETE FROM proyecto_etiquetas
                    WHERE id_proyecto = :id";
            
            $parametros = [
                ':id' => $id
            ];
            
            $this->conexion->ejecutarConParametros($sql, $parametros);
            return true;
        } catch (Exception $e) {
            error_log("Error eliminando tamaños de etiqueta: " . $e->getMessage());
            return false;
        }
    }

    // En tu modelo Proyecto.php, agrega estos métodos:

    public function obtenerCantidadesProyectoEtiqueta($proyecto_id, $etiqueta_id)
    {
        try {
            $sql = "SELECT cantidad AS cantidad_asignada, cantidad_entregada 
                    FROM proyecto_etiquetas 
                    WHERE id_proyecto = :proyecto_id AND id_etiqueta = :etiqueta_id";
            $parametros = [
                ':proyecto_id' => $proyecto_id,
                ':etiqueta_id' => $etiqueta_id
            ];
            $resultado = $this->conexion->ejecutarConParametros($sql, $parametros);
            $fila = $resultado->fetch(PDO::FETCH_ASSOC);
            
            if ($fila) {
                return [
                    'cantidad_asignada' => (int)$fila['cantidad_asignada'],
                    'cantidad_entregada' => (int)$fila['cantidad_entregada']
                ];
            }
            
            // Si no existe registro, devolver ceros
            return [
                'cantidad_asignada' => 0,
                'cantidad_entregada' => 0
            ];
            
        } catch (Exception $e) {
            error_log("Error obteniendo cantidades proyecto-etiqueta: " . $e->getMessage());
            return [
                'cantidad_asignada' => 0,
                'cantidad_entregada' => 0
            ];
        }
    }

    public function actualizarCantidadEntregada($proyecto_id, $etiqueta_id, $cantidad)
    {
        try {
            // Verificar si existe registro
            $sql_check = "SELECT id FROM proyecto_etiquetas 
                        WHERE id_proyecto = :proyecto_id AND id_etiqueta = :etiqueta_id";
            $parametros_check = [
                ':proyecto_id' => $proyecto_id,
                ':etiqueta_id' => $etiqueta_id
            ];
            $resultado_check = $this->conexion->ejecutarConParametros($sql_check, $parametros_check);
            
            if ($resultado_check->rowCount() > 0) {
                // Actualizar existente
                $sql = "UPDATE proyecto_etiquetas 
                    SET cantidad_entregada = cantidad_entregada + :cantidad 
                    WHERE id_proyecto = :proyecto_id AND id_etiqueta = :etiqueta_id";
                $parametros = [
                    ':cantidad' => $cantidad,
                    ':proyecto_id' => $proyecto_id,
                    ':etiqueta_id' => $etiqueta_id
                ];
            } else {
                // Crear nuevo registro
                $sql = "INSERT INTO proyecto_etiquetas (proyecto_id, etiqueta_id, cantidad_entregada, cantidad_asignada) 
                    VALUES (:proyecto_id, :etiqueta_id, :cantidad, 0)";
                $parametros = [
                    ':proyecto_id' => $proyecto_id,
                    ':etiqueta_id' => $etiqueta_id,
                    ':cantidad' => $cantidad
                ];
            }
            
            $resultado = $this->conexion->ejecutarConParametros($sql, $parametros);
            return $resultado->rowCount() > 0;
            
        } catch (Exception $e) {
            error_log("Error actualizando cantidad entregada: " . $e->getMessage());
            return false;
        }
    }
    
    public function finalizarProyect($id, $fecha) {
        try {
            // Aquí podrías agregar lógica para validar el token si es necesario
            
            $sql = "UPDATE proyectos SET estado = 3, fecha_update = :fecha WHERE id = :id";
            $resultado = $this->conexion->ejecutarConParametros($sql, [':id' => $id, ':fecha'=>$fecha]);
            
            return $resultado->rowCount() > 0;
        } catch (Exception $e) {
            error_log("Error eliminando proyecto: " . $e->getMessage());
            return false;
        }
    }

    public function guardarFirma($parametros) {
        try {
            $sql = "INSERT INTO proyecto_firmas (id_proyecto, firma, nombre, comentarios, fecha) 
                VALUES (:idPro, :firma, :nombre, :comentarios, :fecha)";

            $resultado = $this->conexion->ejecutarConParametros($sql, $parametros);

            if ($resultado->rowCount() > 0) {
                return [
                    "id" => $this->conexion->ultimoRegistro()
                ];
            } else {
                return false;
            }
            
        } catch (Exception $e) {
            error_log("Error creando etiqueta: " . $e->getMessage());
            return false;
        }
    }
}
?>