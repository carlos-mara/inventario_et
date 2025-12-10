<?php
require_once __DIR__ . '/../conf/conexion.php';

class Categoria {
    private $conexion;
    
    public function __construct() {
        $this->conexion = new Conexion();
    }
    
    public function listar() {
        try {
            $sql = "SELECT *
                    FROM categorias 
                    WHERE activa = 1 ORDER BY id DESC";
            
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

}
?>