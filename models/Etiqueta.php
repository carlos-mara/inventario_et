<?php 
require_once "../conf/conexion.php";
class Etiqueta {
    protected $conexion;
    
    public function __construct() {
        $this->conexion = new Conexion();
    }
    
    // Aquí puedes agregar métodos para manejar las etiquetas (CRUD)
    public function crear($parametros) {
        try {
            $sql = "INSERT INTO etiquetas (nombre, descripcion, foto_url, stock_minimo, categoria_id, fecha_creacion, usuario)
                    VALUES (:nombre, :descripcion, :foto_url, :stock_minimo, :categoria_id, :fecha_creacion, :usuario_id)";
            
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

    public function insertarTamanos($parametros) {
        try {
            $sql = "INSERT INTO etiqueta_tamanos (etiqueta_id, alto, ancho, fecha_creacion)
                    VALUES (:etiqueta_id, :alto, :ancho, :fecha_creacion)";
            
            $this->conexion->ejecutarConParametros($sql, $parametros);
            
            return true;
        } catch (Exception $e) {
            error_log("Error insertando tamaños de etiqueta: " . $e->getMessage());
            return false;
        }
    }

    public function listar() {
        try {
            $sql = "SELECT etiquetas.*, categorias.nombre AS categoria_nombre
                    FROM etiquetas
                    JOIN categorias ON categorias.id = etiquetas.categoria_id
                    WHERE etiquetas.activa = 1 ORDER BY id DESC";
            
            $parametros = [];
            
            
            $sql .= "";
            
            $resultado = $this->conexion->ejecutar($sql);
            return $resultado->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error listando etiquetas: " . $e->getMessage());
            return [];
        }
    }

    public function listarPorProyecto($id) {
        try {
            $sql = "SELECT e.activa, e.id, e.foto_url, e.stock_actual, e.descripcion, e.nombre, categorias.nombre AS categoria_nombre, ep.cantidad cantidad_asignada, ep.cantidad_entregada
                    FROM proyecto_etiquetas AS ep
                    JOIN etiquetas AS e ON e.id = ep.id_etiqueta
                    JOIN categorias ON categorias.id = e.categoria_id
                    WHERE id_proyecto = $id ORDER BY e.id DESC;";
            
            $parametros = [];
            
            
            $sql .= "";
            
            $resultado = $this->conexion->ejecutar($sql);
            return $resultado->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error listando etiquetas: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerPorId($id) {
        try {
            // Aquí podrías agregar la lógica para verificar el token si es necesario
            
            $sql = "SELECT etiqueta_tamanos.id idTamano, etiqueta_tamanos.stock_actual stock_tamano, etiquetas.nombre, etiquetas.descripcion, etiqueta_tamanos.alto, etiqueta_tamanos.ancho, stock_minimo, etiquetas.activa as activa,
                           etiquetas.foto_url, etiquetas.fecha_creacion, categorias.nombre AS categoria_nombre, categorias.id AS categoria_id, etiquetas.stock_total
                    FROM etiquetas
                    INNER JOIN categorias ON categorias.id = etiquetas.categoria_id
                    LEFT JOIN etiqueta_tamanos ON etiqueta_tamanos.etiqueta_id = etiquetas.id
                    WHERE etiquetas.id = :id AND etiquetas.activa = 1";
            
            $parametros = [
                ':id' => $id
            ];
            
            $resultado = $this->conexion->ejecutarConParametros($sql, $parametros);
            $etiqueta = $resultado->fetchAll(PDO::FETCH_ASSOC);
            
            if ($etiqueta) {
                return [
                    "exito" => true,
                    "msj" => "Etiqueta obtenida exitosamente",
                    "etiqueta" => $etiqueta
                ];
            } else {
                return [
                    "exito" => false,
                    "msj" => "Etiqueta no encontrada"
                ];
            }
            
        } catch (Exception $e) {
            error_log("Error obteniendo etiqueta: " . $e->getMessage());
            return [
                "exito" => false,
                "msj" => "Error interno del servidor"
            ];
        }
    }

    public function actualizarEtiqueta($parametros) {
        try {
            $sql = "UPDATE etiquetas
                    SET nombre = :nombre,
                        descripcion = :descripcion,
                        foto_url = :foto_url,
                        stock_minimo = :stock_minimo,
                        categoria_id = :categoria_id,
                        fecha_actualizacion = :fecha
                    WHERE id = :id";
            
            
            $resultado = $this->conexion->ejecutarConParametros($sql, $parametros);
            
            if ($resultado->rowCount() > 0) {
                return [
                    "exito" => true,
                    "msj" => "Etiqueta editada exitosamente"
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

    public function eliminarTamanos($id){
        try {
            $sql = "DELETE FROM etiqueta_tamanos
                    WHERE etiqueta_id = :id";
            
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

    public function eliminar($id) {
        try {
            $sql = "UPDATE etiquetas
                    SET activa = 0
                    WHERE id = :id";
            
            $parametros = [
                ':id' => $id
            ];
            
            $resultado = $this->conexion->ejecutarConParametros($sql, $parametros);
            if ($resultado->rowCount() > 0) {
                return [
                    "exito" => true,
                    "msj" => "Etiqueta eliminada exitosamente"
                ];
            } else {
                return [
                    "exito" => false,
                    "msj" => "No se pudo eliminar la etiqueta"
                ];
            }
            
        } catch (Exception $e) {
            error_log("Error eliminando etiqueta: " . $e->getMessage());
            return [
                "exito" => false,
                "msj" => "Error interno del servidor"
            ];
        }
    }

    public function obtenerTamanosPorEtiqueta($etiqueta_id) {
        try {
            // Aquí podrías agregar la lógica para verificar el token si es necesario
            
            $sql = "SELECT id, alto, ancho, stock_actual
                    FROM etiqueta_tamanos
                    WHERE etiqueta_id = :etiqueta_id";
            
            $parametros = [
                ':etiqueta_id' => $etiqueta_id
            ];
            
            $resultado = $this->conexion->ejecutarConParametros($sql, $parametros);
            $tamanos = $resultado->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                "exito" => true,
                "tamanos" => $tamanos
            ];
            
        } catch (Exception $e) {
            error_log("Error obteniendo tamaños de etiqueta: " . $e->getMessage());
            return [
                "exito" => false,
                "msj" => "Error interno del servidor"
            ];
        }
    }

    public function tieneTamanosEnProyectos($id_tamano)
    {
         try {
            $sql = "SELECT COUNT(*) as count 
                    FROM proyecto_etiquetas 
                    WHERE id_tamano = :id_tamano";

            $parametros = [':id_tamano' => $id_tamano];
            
            $resultado = $this->conexion->ejecutarConParametros($sql, $parametros);
            $data = $resultado->fetch(PDO::FETCH_ASSOC); // Cambiar a fetch() para obtener el count
                
            return [
                "exito" => true,
                "count" => $data['count'],
                "en_proyectos" => $data['count'] > 0
            ];

        } catch (Exception $e) {
            error_log("Error en tieneTamanosEnProyectos: " . $e->getMessage());
            return [
                "exito" => false,
                "msj" => "Error interno del servidor"
            ];
        }
    }

    public function obtenerTamanosActuales($etiqueta_id)
    {
        $sql = "SELECT id, alto, ancho 
                FROM etiqueta_tamanos 
                WHERE etiqueta_id = :etiqueta_id";
        
        $parametros = [':etiqueta_id' => $etiqueta_id];
        $resultado = $this->conexion->ejecutarConParametros($sql, $parametros);
        return $resultado->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
 * Elimina tamaños específicos por IDs
 */
public function eliminarTamanosPorIds($ids_tamanos)
{
    try {
        if (empty($ids_tamanos)) {
            return true;
        }
        
        $sql = "DELETE FROM etiqueta_tamanos WHERE id = :id";
        
        $this->conexion->ejecutarConParametros($sql, $ids_tamanos);
        return true;
        
    } catch (Exception $e) {
        error_log("Error eliminando tamaños por IDs: " . $e->getMessage());
        return false;
    }
}
}
?>