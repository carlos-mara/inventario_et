<?php 
require_once "../conf/conexion.php";
class Movimiento {
    private $conexion;
    
    public function __construct() {
        $this->conexion = new Conexion();
    }
    
    public function registrarMovimiento($etiqueta_id, $tipo, $cantidad, $alto, $ancho, $precio, $motivo = null, $referencia = null, $observaciones = null, $cantidad_anterior = 0, $cantidad_nueva = 0, $cod_proyecto = null, $usuario_id, $fecha = null, $foto_url = null) {
        if ($fecha === null) {
            $fecha = date('Y-m-d H:i:s');
        }

        $parametros = [
            ':etiqueta_id'      => $etiqueta_id,
            ':tipo'             => $tipo,
            ':cantidad'         => $cantidad,
            ':alto'             => $alto,
            ':ancho'            => $ancho,
            ':precio'           => $precio,
            ':motivo'           => $motivo,
            ':referencia'       => $referencia,
            ':observaciones'    => $observaciones,
            ':cantidad_anterior'=> $cantidad_anterior,
            ':cantidad_nueva'   => $cantidad_nueva,
            ':cod_proyecto'     => $cod_proyecto,
            ':usuario_id'       => $usuario_id,
            ':fecha'            => $fecha,
            ':foto'             => $foto_url
        ];
        
        try {
            $sql = "INSERT INTO movimientos_inventario (etiqueta_id, tipo, cantidad, alto, ancho, precio, motivo, referencia, observaciones, cantidad_anterior, cantidad_nueva, cod_proyecto, usuario_id, fecha_movimiento, foto_evidencia)
                    VALUES (:etiqueta_id, :tipo, :cantidad, :alto, :ancho, :precio, :motivo, :referencia, :observaciones, :cantidad_anterior, :cantidad_nueva, :cod_proyecto, :usuario_id, :fecha, :foto)";
            
            
            
            $resultado = $this->conexion->ejecutarConParametros($sql, $parametros);
            return $resultado->rowCount() > 0;
            
        } catch (Exception $e) {
            error_log("Error registrando movimiento: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerCantidadActualTamano($id) {
        try {
            $sql = "SELECT stock_actual FROM etiqueta_tamanos WHERE id = :id_tamano";
            $parametros = [':id_tamano' => $id];
            $resultado = $this->conexion->ejecutarConParametros($sql, $parametros);
            $fila = $resultado->fetch(PDO::FETCH_ASSOC);
            return $fila ? (int)$fila['stock_actual'] : 0;
        } catch (Exception $e) {
            error_log("Error obteniendo cantidad actual: " . $e->getMessage());
            return 0;
        }
    }

    public function actualizarCantidadEtiquetaTamano($id, $nueva_cantidad) {
        try {
            $sql = "UPDATE etiqueta_tamanos SET stock_actual = :nueva_cantidad WHERE id = :id";
            $parametros = [
                ':nueva_cantidad' => $nueva_cantidad,
                ':id'    => $id
            ];
            $resultado = $this->conexion->ejecutarConParametros($sql, $parametros);
            return $resultado->rowCount() > 0;
        } catch (Exception $e) {
            error_log("Error actualizando cantidad de etiqueta: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerMovimientos() {
        try {
            $sql = "SELECT movimientos_inventario.*, usuarios.username AS usuario_nombre, etiquetas.nombre AS etiqueta_nombre
            FROM movimientos_inventario
            JOIN etiquetas ON movimientos_inventario.etiqueta_id = etiquetas.id
            JOIN usuarios ON movimientos_inventario.usuario_id = usuarios.id
            WHERE movimientos_inventario.activo = 1
            ORDER BY fecha_movimiento DESC";
            $resultado = $this->conexion->ejecutar($sql);
            return $resultado->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error obteniendo movimientos: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerMovimientosPorFecha($fecha) {
        try {
            $sql = "SELECT movimientos_inventario.*, usuarios.username AS usuario_nombre, etiquetas.nombre AS etiqueta_nombre
            FROM movimientos_inventario
            JOIN etiquetas ON movimientos_inventario.etiqueta_id = etiquetas.id
            JOIN usuarios ON movimientos_inventario.usuario_id = usuarios.id
            WHERE DATE(fecha_movimiento) = :fecha AND movimientos_inventario.activo = 1
            ORDER BY fecha_movimiento DESC";
            $parametros = [':fecha' => $fecha];
            $resultado = $this->conexion->ejecutarConParametros($sql, $parametros);
            return $resultado->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error obteniendo movimientos por fecha: " . $e->getMessage());
            return [];
        }
    }

    

    public function revertirMovimiento($movimiento_id){
        try {
            // Obtener el movimiento original
            $sql = "SELECT * FROM movimientos_inventario WHERE id = :movimiento_id";
            $parametros = [':movimiento_id' => $movimiento_id];
            $resultado = $this->conexion->ejecutarConParametros($sql, $parametros);
            $movimiento = $resultado->fetch(PDO::FETCH_ASSOC);

            if (!$movimiento) {
                return [
                    "exito" => false,
                    "msj" => "Movimiento no encontrado"
                ];
            }

            // Calcular los nuevos valores
            $etiqueta_id = $movimiento['etiqueta_id'];
            $tipo_reverso = $movimiento['tipo'] === 'entrada' ? 'salida' : 'entrada';
            $cantidad = $movimiento['cantidad'];
            $cantidad_anterior = $this->obtenerCantidadActual($etiqueta_id);
            $cantidad_nueva = $tipo_reverso === 'entrada' ? $cantidad_anterior + $cantidad : $cantidad_anterior - $cantidad;

            // Registrar el movimiento de reversión
            $this->registrarMovimiento(
                $etiqueta_id,
                $tipo_reverso,
                $cantidad,
                $movimiento['precio'],
                "Reversión del movimiento ID: " . $movimiento_id,
                null,
                null,
                $cantidad_anterior,
                $cantidad_nueva,
                $movimiento['cod_proyecto'],
                $movimiento['usuario_id']
            );

            // Actualizar la cantidad en la etiqueta
            $this->actualizarCantidadEtiqueta($etiqueta_id, $cantidad_nueva);

            return [
                "exito" => true,
                "msj" => "Movimiento revertido exitosamente"
            ];
        } catch (Exception $e) {
            error_log("Error al revertir movimiento: " . $e->getMessage());
            return [
                "exito" => false,
                "msj" => "Error al revertir el movimiento"
            ];
        }
    }
}
?>