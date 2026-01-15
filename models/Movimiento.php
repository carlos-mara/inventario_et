<?php 
require_once "../conf/conexion.php";
class Movimiento {
    protected $conexion;
    
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
    public function obtenerCantidadActualEt($id) {
        try {
            $sql = "SELECT stock_total FROM etiquetas WHERE id = :id";
            $parametros = [':id' => $id];
            $resultado = $this->conexion->ejecutarConParametros($sql, $parametros);
            $fila = $resultado->fetch(PDO::FETCH_ASSOC);
            return $fila ? (int)$fila['stock_total'] : 0;
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
    public function actualizarCantidadEtiqueta($id, $nueva_cantidad) {
        try {
            $sql = "UPDATE etiquetas SET stock_total = :nueva_cantidad WHERE id = :id";
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
            $sql = "SELECT movimientos_inventario.*, usuarios.username AS usuario_nombre, etiquetas.nombre AS etiqueta_nombre,
            etiquetas.foto_url AS etiqueta_foto
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

    public function obtenerMovimientoPorId($movimiento_id) {
        try {
            $params = [':id' => $movimiento_id];

            $sql = "SELECT movimientos_inventario.*, usuarios.username AS usuario_nombre, etiquetas.nombre AS etiqueta_nombre
            FROM movimientos_inventario
            JOIN etiquetas ON movimientos_inventario.etiqueta_id = etiquetas.id
            JOIN usuarios ON movimientos_inventario.usuario_id = usuarios.id
            WHERE movimientos_inventario.id = :id";
            $resultado = $this->conexion->ejecutarConParametros($sql, $params);
            return $resultado->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Error obteniendo movimiento: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene el ID del tamaño basado en dimensiones
     */
    public function obtenerIdTamanoPorDimensiones($alto, $ancho)
    {
        $params = [
            ':alto' => $alto,
            ':ancho' => $ancho
        ];
        
        try {
            $sql = "SELECT id FROM etiqueta_tamanos WHERE alto = :alto AND ancho = :ancho LIMIT 1";
            $resultado = $this->conexion->ejecutarConParametros($sql, $params);
            $res = $resultado->fetch(PDO::FETCH_ASSOC);
            
            return $res['id'] ?? null;
            
        } catch (Exception $e) {
            error_log("Error en obtenerIdTamanoPorDimensiones: " . $e->getMessage());
            return null;
        }
    }

    public function revertirCantidadEntregadaProyecto($cod_proyecto, $etiqueta_id, $cantidad)
    {
        try {
            $params = [
                ':cod_proyecto' => $cod_proyecto,
                ':etiqueta_id' => $etiqueta_id,
            ];
            // Primero verificar que exista la relación proyecto-etiqueta
            $sql = "SELECT id FROM proyecto_etiquetas 
                              WHERE cod_proyecto = :cod_proyecto AND etiqueta_id = :etiqueta_id";
            $resultado = $this->conexion->ejecutarConParametros($sql, $params);
            $resultado->fetchAll(PDO::FETCH_ASSOC);

            if ($resultado->fetch()) {
                $params = [
                    ':cod_proyecto' => $cod_proyecto,
                    ':etiqueta_id' => $etiqueta_id,
                    ':cantidad' => $cantidad
                ];

                // Restar la cantidad entregada
                $sql_actualizar = "UPDATE proyecto_etiquetas 
                                   SET cantidad_entregada = cantidad_entregada - :cantidad 
                                   WHERE cod_proyecto = :cod_proyecto AND etiqueta_id = :etiqueta_id";
                
                $resultado = $this->conexion->ejecutarConParametros($sql_actualizar, $params);
                
                return $resultado->rowCount() > 0;
                
                if (!$resultado) {
                    throw new Exception("Error al revertir cantidad entregada en proyecto");
                }
                
                return true;
            }
            
            return false; // No había relación, no hay nada que revertir
            
        } catch (Exception $e) {
            error_log("Error en revertirCantidadEntregadaProyecto: " . $e->getMessage());
            throw $e;
        }
    }

    public function marcarComoAnulado($movimiento_id)
    {
        try {
            $sql = "UPDATE movimientos_inventario SET activo = 4 WHERE id = :id";
            $resultado = $this->conexion->ejecutarConParametros($sql, [':id'=>$movimiento_id]);
                
            return $resultado->rowCount() > 0;
            
        } catch (Exception $e) {
            error_log("Error en marcarMovimientoComoAnulado: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerInfoReporte($datos){
        // Construir consulta con filtros
        $query = "SELECT 
                    m.*,
                    e.nombre as etiqueta_nombre,
                    c.nombre as categoria_nombre,
                    u.username as usuario_nombre,
                    p.nombre as proyecto_nombre,
                    DATE_FORMAT(m.fecha_movimiento, '%d/%m/%Y %H:%i') as fecha_formateada
                FROM movimientos_inventario m
                LEFT JOIN etiquetas e ON m.etiqueta_id = e.id
                LEFT JOIN categorias c ON e.categoria_id = c.id
                LEFT JOIN usuarios u ON m.usuario_id = u.id
                LEFT JOIN proyectos p ON m.cod_proyecto = p.codigo
                WHERE 1=1";
        
        $params = [];
        
        // Filtrar por rango de fechas (si no es todo el historial)
        if (!($datos['todo_historial'] ?? false)) {
            $fecha_desde = $datos['fecha_desde'] ?? date('Y-m-01');
            $fecha_hasta = $datos['fecha_hasta'] ?? date('Y-m-d');
            
            $query .= " AND DATE(m.fecha_movimiento) BETWEEN :desde AND :hasta";
            $params[":desde"] = $fecha_desde;
            $params[":hasta"] = $fecha_hasta;
        }
        
        // Filtrar por tipo de movimiento
        if (!empty($datos['tipo_movimiento'])) {
            $query .= " AND m.tipo = :tipo";
            $params[":tipo"] = $datos['tipo_movimiento'];
        }
        
        // Filtrar por etiqueta específica
        if (!empty($datos['etiqueta_id'])) {
            $query .= " AND m.etiqueta_id = :id_etiqueta";
            $params[":id_etiqueta"] = $datos['etiqueta_id'];
        }
        
        // Filtrar por usuario
        if (!empty($datos['usuario_id'])) {
            $query .= " AND m.usuario_id = :id_usuario";
            $params[":id_usuario"] = $datos['usuario_id'];
        }
        
        // Filtrar por cantidad mínima
        if (!empty($datos['cantidad_minima'])) {
            $query .= " AND m.cantidad >= :cant_minima";
            $params[":cant_minima"] = $datos['cantidad_minima'];
        }
        
        // Ordenar según selección
        $orden_por = $datos['orden_por'] ?? 'fecha';
        $orden_direccion = $datos['orden_direccion'] ?? 'desc';
        
        $orden_columnas = [
            'fecha' => 'm.fecha_movimiento',
            'etiqueta' => 'e.nombre',
            'tipo' => 'm.tipo',
            'cantidad' => 'm.cantidad',
            'usuario' => 'u.nombre'
        ];
        
        $columna_orden = $orden_columnas[$orden_por] ?? 'm.fecha';
        $query .= " ORDER BY {$columna_orden} {$orden_direccion}";
        
        // Preparar y ejecutar consulta
        
        $resultado = $this->conexion->ejecutarConParametros($query, $params);
        
        return $resultado->fetchAll(PDO::FETCH_ASSOC);
    }
    
}
?>