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
                    u.nombre_completo as usuario_nombre,
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
                    u.nombre_completo as usuario_nombre,
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
                    u.nombre_completo as usuario_nombre,
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
                LEFT JOIN usuarios u ON p.usuario_create = u.id
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
                        estado = :estado,
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

    public function eliminarEtiquetaProyectoPorId($id) {
        try {
            // Aquí podrías agregar lógica para validar el token si es necesario
            
            $sql = "DELETE FROM proyecto_etiquetas WHERE id = :id";
            $resultado = $this->conexion->ejecutarConParametros($sql, [':id' => $id]);
            
            return $resultado->rowCount() > 0;
        } catch (Exception $e) {
            error_log("Error eliminando etiqueta del proyecto: " . $e->getMessage());
            return false;
        }
    }

    public function actualizarEtiquetaProyecto($parametros) {
        try {
            $sql = "UPDATE proyecto_etiquetas
                    SET cantidad = :cantidad
                    WHERE id = :id";
            
            $resultado = $this->conexion->ejecutarConParametros($sql, $parametros);
            
            
            if ($resultado->rowCount() > 0) {
                return [
                    "exito" => true,
                    "msj" => "Editado exitosamente"
                ];
            } else {
                return [
                    "exito" => false,
                    "msj" => "No se realizaron cambios"
                ];
            }
        }catch (Exception $e) {
            error_log("Error editando proyecto: " . $e->getMessage());
            return [
                "exito" => false,
                "msj" => "Error interno del servidor"
            ];
        }
    }

    // En tu modelo Proyecto.php, agrega estos métodos:

    public function obtenerCantidadesProyectoEtiqueta($proyecto_id, $id_tamano)
    {
        try {
            $sql = "SELECT cantidad AS cantidad_asignada, cantidad_entregada 
                    FROM proyecto_etiquetas 
                    WHERE id_proyecto = :proyecto_id AND id_tamano = :id_tamano";
            $parametros = [
                ':proyecto_id' => $proyecto_id,
                ':id_tamano' => $id_tamano
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

    public function obtenerDatosEtiquetaProyecto($id){
        try {
            $sql = "SELECT 
                        pe.id AS id_proyecto_etiqueta,
                        pe.id_proyecto,
                        pe.id_etiqueta,
                        pe.id_tamano,
                        e.nombre as etiqueta_nombre,
                        pe.cantidad,
                        pe.cantidad_entregada
                    FROM proyecto_etiquetas pe
                    JOIN etiquetas e ON pe.id_etiqueta = e.id
                    WHERE pe.id = :id";
            $parametros = [
                ':id' => $id
            ];
            $resultado = $this->conexion->ejecutarConParametros($sql, $parametros);
            return $resultado->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error obteniendo datos etiqueta-proyecto: " . $e->getMessage());
            return false;
        }
    }

    public function actualizarCantidadEntregada($proyecto_id, $etiqueta_id, $cantidad)
    {
        try {
            // Verificar si existe registro
            $sql_check = "SELECT id FROM proyecto_etiquetas 
                        WHERE id_proyecto = :proyecto_id AND id = :id_etiqueta_proyecto";
            $parametros_check = [
                ':proyecto_id' => $proyecto_id,
                ':id_etiqueta_proyecto' => $etiqueta_id
            ];
            $resultado_check = $this->conexion->ejecutarConParametros($sql_check, $parametros_check);
            
            if ($resultado_check->rowCount() > 0) {
                // Actualizar existente
                $sql = "UPDATE proyecto_etiquetas 
                    SET cantidad_entregada = cantidad_entregada + :cantidad 
                    WHERE id_proyecto = :proyecto_id AND id = :id_etiqueta_proyecto";
                $parametros = [
                    ':cantidad' => $cantidad,
                    ':proyecto_id' => $proyecto_id,
                    ':id_etiqueta_proyecto' => $etiqueta_id
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

    public function obtenerInfoReporte($datos) {
        
        try {
            $query = "SELECT 
                        p.*,
                        u.nombre_completo as usuario_nombre,
                        e.nombre as estado_nombre,
                        COUNT(DISTINCT pe.id_etiqueta) as total_etiquetas,
                        SUM(pe.cantidad) as total_unidades,
                        SUM(pe.cantidad_entregada) as unidades_entregadas,
                        ROUND(
                            CASE 
                                WHEN SUM(pe.cantidad) > 0 
                                THEN (SUM(pe.cantidad_entregada) * 100.0 / SUM(pe.cantidad)) 
                                ELSE 0 
                            END, 2
                        ) as porcentaje_entrega
                      FROM proyectos p
                      LEFT JOIN usuarios u ON p.usuario_create = u.id
                      LEFT JOIN estados e ON p.estado = e.id
                      LEFT JOIN proyecto_etiquetas pe ON p.id = pe.id_proyecto
                      WHERE 1=1";
            
            $params = [];
            $types = '';
            
            // Filtrar por estados
            if (!empty($datos['estados']) && is_array($datos['estados'])) {
                $placeholders = "";
                foreach ($datos['estados'] as $index => $estado) {
                    $params[":".$estado] = $estado;
                    $placeholders .= ":".$estado;
                    if ($index < count($datos['estados']) - 1) {
                        $placeholders .= ",";
                    }
                }

                
                $query .= " AND p.estado IN ($placeholders)";
                /* $params = array_merge($params, $datos['estados']);
                $types .= str_repeat('i', count($datos['estados'])); */
            }
            
            // Filtrar por rango de fechas
            if (!empty($datos['fecha_desde']) && !empty($datos['fecha_hasta'])) {
                $query .= " AND DATE(p.fecha_create) BETWEEN :desde AND :hasta";
                $params[":desde"] = $datos['fecha_desde'];
                $params[":hasta"] = $datos['fecha_hasta'];
                $types .= 'ss';
            }
            
            // Filtrar por usuario creador
            
            if (!empty($datos['usuario_id']) && $datos['usuario_id'] !== 'null') {
                $query .= " AND p.usuario_create = :usuario_id";
                $params[":usuario_id"] = $datos['usuario_id'];
                $types .= 's';
            }
            
            // Filtrar por etiqueta específica
            if (!empty($datos['etiqueta_id']) && $datos['etiqueta_id'] !== 'null') {
                $query .= " AND EXISTS (
                    SELECT 1 FROM proyecto_etiquetas pe2 
                    WHERE pe2.id_proyecto = p.id 
                    AND pe2.etiqueta_id = :etiqueta_id
                )";
                $params[":etiqueta_id"] = $datos['etiqueta_id'];
                $types .= 'i';
            }
            
            // Filtrar por proyectos con firma
            if (!empty($datos['con_firma']) && $datos['con_firma'] !== 'null') {
                $query .= " AND EXISTS (
                    SELECT 1 FROM proyecto_firmas fp 
                    WHERE fp.id_proyecto = p.codigo
                )";
            }
            
            // Agrupar por proyecto
            $query .= " GROUP BY p.codigo";
            
            // Filtrar por cantidad mínima de unidades
            if (!empty($datos['min_unidades']) && $datos['min_unidades'] !== 'null') {
                $query .= " HAVING total_unidades >= :min_unidades";
                $params[":min_unidades"] = $datos['min_unidades'];
                $types .= 'i';
            }
            
            // Filtrar por porcentaje mínimo de entrega
            if (!empty($datos['porcentaje_entrega']) && $datos['porcentaje_entrega'] !== 'null') {
                $query .= " HAVING porcentaje_entrega >= :porcentaje_entrega";
                $params[":porcentaje_entrega"] = $datos['porcentaje_entrega'];
                $types .= 'i';
            }
            
            // Ordenar según selección
            $orden_por = $datos['orden_por'] ?? 'fecha_inicio';
            $orden_direccion = $datos['orden_direccion'] ?? 'desc';
            
            $orden_columnas = [
                'codigo' => 'p.codigo',
                'nombre' => 'p.nombre',
                'fecha_inicio' => 'p.fecha_inicio',
                'fecha_create' => 'p.fecha_create',
                'estado' => 'p.estado',
                'total_unidades' => 'total_unidades',
                'porcentaje_entrega' => 'porcentaje_entrega'
            ];
            
            $columna_orden = $orden_columnas[$orden_por] ?? 'p.fecha_create';
            $query .= " ORDER BY {$columna_orden} {$orden_direccion}";
            /* print_r($query);exit; */
            // Preparar y ejecutar consulta
            if (!empty($params)) {
                $result = $this->conexion->ejecutarConParametros($query, $params);
                return $result->fetchAll(PDO::FETCH_ASSOC);
            } 
            
            
        } catch (Exception $e) {
            error_log("Error en obtenerInfoReporte: " . $e->getMessage());
            throw $e;
        }
    }
    
    /* public function obtenerEtiquetasProyecto($cod_proyecto) {
        try {
            $query = "SELECT 
                        pe.*,
                        e.nombre as etiqueta_nombre,
                        e.codigo as etiqueta_codigo,
                        e.foto_url,
                        e.stock_total as stock_actual,
                        c.nombre as categoria_nombre,
                        te.alto,
                        te.ancho,
                        ROUND(
                            CASE 
                                WHEN pe.cantidad > 0 
                                THEN (pe.cantidad_entregada * 100.0 / pe.cantidad) 
                                ELSE 0 
                            END, 2
                        ) as porcentaje_entrega
                      FROM proyecto_etiquetas pe
                      JOIN etiquetas e ON pe.etiqueta_id = e.id
                      LEFT JOIN categorias c ON e.categoria_id = c.id
                      LEFT JOIN tamanos_etiquetas te ON pe.tamano_id = te.id
                      WHERE pe.cod_proyecto = ?
                      ORDER BY e.nombre";
            
            $stmt = $this->conexion->prepare($query);
            $stmt->bind_param('s', $cod_proyecto);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $etiquetas = [];
            while ($row = $result->fetch_assoc()) {
                $etiquetas[] = $row;
            }
            
            $stmt->close();
            return $etiquetas;
            
        } catch (Exception $e) {
            error_log("Error en obtenerEtiquetasProyecto: " . $e->getMessage());
            throw $e;
        }
    } */
    
    public function obtenerFirmasProyecto($id_proyecto) {
        try {
            $parametros = [
                ':id_proyecto' => $id_proyecto
            ];
            $query = "SELECT * FROM proyecto_firmas
                      WHERE id_proyecto = :id_proyecto
                      ORDER BY fecha DESC";
            
            $result = $this->conexion->ejecutarConParametros($query, $parametros);
            
            
            return $result->fetchAll(PDO::FETCH_ASSOC);
            
            
        } catch (Exception $e) {
            error_log("Error en obtenerFirmasProyecto: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function obtenerTotalEstadisticas($datos) {
    try {
        // Primero crear una subconsulta para obtener los proyectos filtrados
        $subquery = "SELECT 
                        p.id,
                        p.codigo,
                        p.estado,
                        SUM(pe.cantidad) as total_unidades_proyecto,
                        SUM(pe.cantidad_entregada) as total_entregadas_proyecto,
                        CASE 
                            WHEN SUM(pe.cantidad) > 0 
                            THEN (SUM(pe.cantidad_entregada) * 100.0 / SUM(pe.cantidad))
                            ELSE 0 
                        END as porcentaje_entrega_proyecto
                     FROM proyectos p
                     LEFT JOIN proyecto_etiquetas pe ON p.id = pe.id_proyecto
                     WHERE 1=1";
        
        $params = [];
        
        // Aplicar filtros básicos en la subconsulta
        if (!empty($datos['estados']) && is_array($datos['estados'])) {
            $placeholders = "";
            foreach ($datos['estados'] as $index => $estado) {
                $params[":estado_" . $index] = $estado;
                $placeholders .= ":estado_" . $index;
                if ($index < count($datos['estados']) - 1) {
                    $placeholders .= ",";
                }
            }
            $subquery .= " AND p.estado IN ($placeholders)";
        }
        
        if (!empty($datos['fecha_desde']) && !empty($datos['fecha_hasta'])) {
            $subquery .= " AND DATE(p.fecha_create) BETWEEN :desde AND :hasta";
            $params[":desde"] = $datos['fecha_desde'];
            $params[":hasta"] = $datos['fecha_hasta'];
        }
        
        if (!empty($datos['usuario_id']) && $datos['usuario_id'] !== 'null') {
            $subquery .= " AND p.usuario_create = :usuario_id";
            $params[":usuario_id"] = $datos['usuario_id'];
        }
        
        if (!empty($datos['etiqueta_id']) && $datos['etiqueta_id'] !== 'null') {
            $subquery .= " AND EXISTS (
                SELECT 1 FROM proyecto_etiquetas pe2 
                WHERE pe2.id_proyecto = p.id 
                AND pe2.id_etiqueta = :etiqueta_id
            )";
            $params[":etiqueta_id"] = $datos['etiqueta_id'];
        }
        
        if (!empty($datos['con_firma'])) {
            $subquery .= " AND EXISTS (
                SELECT 1 FROM proyecto_firmas fp 
                WHERE fp.id_proyecto = p.id
            )";
        }
        
        // Agrupar por proyecto en la subconsulta
        $subquery .= " GROUP BY p.id";
        
        // Consulta principal que filtra desde la subconsulta
        $query = "SELECT 
                    COUNT(*) as total_proyectos,
                    SUM(total_unidades_proyecto) as total_unidades,
                    SUM(total_entregadas_proyecto) as total_entregadas,
                    AVG(porcentaje_entrega_proyecto) as porcentaje_promedio_entrega,
                    SUM(CASE WHEN estado = 1 THEN 1 ELSE 0 END) as proyectos_activos,
                    SUM(CASE WHEN estado = 2 THEN 1 ELSE 0 END) as proyectos_inactivos,
                    SUM(CASE WHEN estado = 3 THEN 1 ELSE 0 END) as proyectos_completados
                  FROM ($subquery) as proyectos_filtrados
                  WHERE 1=1";
        
        // Aplicar filtros HAVING en la consulta principal
        if (!empty($datos['min_unidades']) && $datos['min_unidades'] !== 'null') {
            $query .= " AND total_unidades_proyecto >= :min_unidades";
            $params[":min_unidades"] = $datos['min_unidades'];
        }
        
        if (!empty($datos['porcentaje_entrega']) && $datos['porcentaje_entrega'] !== 'null') {
            $query .= " AND porcentaje_entrega_proyecto >= :porcentaje_entrega";
            $params[":porcentaje_entrega"] = $datos['porcentaje_entrega'];
        }
        
        // Ejecutar la consulta
        $result = $this->conexion->ejecutarConParametros($query, $params);
        return $result->fetch(PDO::FETCH_ASSOC) ?: [];
        
    } catch (Exception $e) {
        error_log("Error en obtenerTotalEstadisticas: " . $e->getMessage());
        throw $e;
    }
}

}
?>