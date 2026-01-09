<?php
require_once __DIR__ . '/../conf/conexion.php';

class Categoria {
    private $conexion;
    
    public function __construct() {
        $this->conexion = new Conexion();
    }
    
    public function listar() {
        try {
            $sql = "SELECT  cat.*, COUNT(et.id) AS cantidad_etiquetas
            FROM categorias cat
            LEFT JOIN etiquetas et 
                ON cat.id = et.categoria_id 
                AND et.activa = 1
            WHERE cat.activa = 1
            GROUP BY cat.id
            ORDER BY cat.id DESC";
            
            $parametros = [];
            
            
            $sql .= "";
            
            $resultado = $this->conexion->ejecutar($sql);
            return $resultado->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error listando categorias: " . $e->getMessage());
            return [];
        }
    }

    public function crear($nombre, $descripcion, $fecha) {
        try {
            $sql = "INSERT INTO categorias (nombre, descripcion, activa, fecha_creacion)
                    VALUES (:nombre, :descripcion, 1, :fecha_creacion)";
            
            $parametros = [
                ':nombre' => $nombre,
                ':descripcion' => $descripcion,
                ':fecha_creacion' => $fecha
            ];
            
            $resultado = $this->conexion->ejecutarConParametros($sql, $parametros);
            if ($resultado->rowCount() > 0) {
                return [
                    "id" => $this->conexion->ultimoRegistro(),
                    "nombre" => $nombre,
                    "descripcion" => $descripcion
                ];
            } else {
                return false;
            }
            
        } catch (Exception $e) {
            error_log("Error creando categoria: " . $e->getMessage());
            return false;
        }
    }

    public function eliminar($id) {
        try {
            $sql = "UPDATE categorias
                    SET activa = 2
                    WHERE id = :id";
            
            $parametros = [
                ':id' => $id
            ];
            
            $resultado = $this->conexion->ejecutarConParametros($sql, $parametros);
            if ($resultado->rowCount() > 0) {
                return [
                    "exito" => true,
                    "msj" => "Categoria eliminada exitosamente"
                ];
            } else {
                return [
                    "exito" => false,
                    "msj" => "No se pudo eliminar la categoria"
                ];
            }
            
        } catch (Exception $e) {
            error_log("Error eliminando categoria: " . $e->getMessage());
            return [
                "exito" => false,
                "msj" => "Error interno del servidor"
            ];
        }
    }
    public function editar($id, $nombre, $descripcion) {
        try {
            $sql = "UPDATE categorias
                    SET nombre = :nombre,
                        descripcion = :descripcion
                    WHERE id = :id";
            
            $parametros = [
                ':id' => $id,
                ':nombre' => $nombre,
                ':descripcion' => $descripcion
            ];
            
            $resultado = $this->conexion->ejecutarConParametros($sql, $parametros);
            if ($resultado->rowCount() > 0) {
                return [
                    "exito" => true,
                    "msj" => "Categoria actualizada exitosamente"
                ];
            } else {
                return [
                    "exito" => false,
                    "msj" => "No se pudo actualizar la categoria"
                ];
            }
            
        } catch (Exception $e) {
            error_log("Error actualizando categoria: " . $e->getMessage());
            return [
                "exito" => false,
                "msj" => "Error interno del servidor"
            ];
        }
    }

}
?>