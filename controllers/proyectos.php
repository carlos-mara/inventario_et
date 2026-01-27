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

    public function editarProyect($id, $estado, $codigo, $nombre, $descripcion, $fecha_inicio, $fecha_update, $token, $etiquetas)
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
                ':estado' => $estado,
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

    // Método para generar reporte de proyectos
    public function generarReporteProyectos($datos)
    {
        try {
            $validacion = $this->verificarAcceso($datos['token'] ?? '');
            if (!$validacion['exito']) {
                return $validacion;
            }

            // Obtener proyectos según filtros
            $proyectos = parent::obtenerInfoReporte($datos);
            
            // Obtener estadísticas generales
            $estadisticas = parent::obtenerTotalEstadisticas($datos);
            
            $tipo = $datos['tipo'] ?? 'general';
            
            // Para reporte DETALLADO: obtener etiquetas de cada proyecto
            if ($tipo === 'detallado') {
                foreach ($proyectos as &$proyecto) {
                    $proyecto['etiquetas'] = parent::obtenerEtiquetasProyecto($proyecto['codigo']);
                    
                    // Calcular estadísticas por etiqueta
                    $proyecto['estadisticas_etiquetas'] = $this->calcularEstadisticasEtiquetas($proyecto['etiquetas']);
                    
                    if ($datos['incluir_firmas'] ?? false) {
                        $proyecto['firmas'] = parent::obtenerFirmasProyecto($proyecto['codigo']);
                    }
                }
            }
            
            // Para reporte ESTADÍSTICO: generar datos adicionales
            if ($tipo === 'estadistico') {
                $datos_estadisticos = $this->generarDatosEstadisticos($proyectos, $estadisticas);
            }
            
            return [
                "exito" => true,
                "data" => [
                    "proyectos" => $proyectos,
                    "estadisticas" => $estadisticas,
                    "datos_estadisticos" => $datos_estadisticos ?? null,
                    "total_proyectos" => count($proyectos),
                    "filtros" => $datos
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Error en generarReporteProyectos: " . $e->getMessage());
            return [
                "exito" => false,
                "msj" => "Error generando reporte: " . $e->getMessage()
            ];
        }
    }

    // Método auxiliar para calcular estadísticas de etiquetas
    private function calcularEstadisticasEtiquetas($etiquetas)
    {
        $estadisticas = [
            'total_etiquetas' => count($etiquetas),
            'total_unidades' => 0,
            'total_entregadas' => 0,
            'porcentaje_promedio' => 0,
            'etiquetas_completadas' => 0,
            'etiquetas_pendientes' => 0
        ];
        
        foreach ($etiquetas as $etiqueta) {
            $estadisticas['total_unidades'] += $etiqueta['cantidad'] ?? 0;
            $estadisticas['total_entregadas'] += $etiqueta['cantidad_entregada'] ?? 0;
            
            $porcentaje = $etiqueta['porcentaje_entrega'] ?? 0;
            if ($porcentaje >= 100) {
                $estadisticas['etiquetas_completadas']++;
            } else {
                $estadisticas['etiquetas_pendientes']++;
            }
        }
        
        if ($estadisticas['total_unidades'] > 0) {
            $estadisticas['porcentaje_promedio'] = 
                ($estadisticas['total_entregadas'] * 100) / $estadisticas['total_unidades'];
        }
        
        return $estadisticas;
    }

    // Método para generar datos estadísticos
    private function generarDatosEstadisticos($proyectos, $estadisticas_generales)
    {
        $datos = [
            'distribucion_estados' => [
                'activos' => 0,
                'inactivos' => 0,
                'completados' => 0
            ],
            'distribucion_porcentajes' => [
                '0_25' => 0,    // 0-25%
                '25_50' => 0,   // 25-50%
                '50_75' => 0,   // 50-75%
                '75_99' => 0,   // 75-99%
                '100' => 0      // 100%
            ],
            'top_proyectos_unidades' => [],
            'top_proyectos_porcentaje' => [],
            'evolucion_mensual' => [],
            'etiquetas_mas_usadas' => []
        ];
        
        // Distribución por estados
        foreach ($proyectos as $proyecto) {
            switch ($proyecto['estado']) {
                case 1:
                    $datos['distribucion_estados']['activos']++;
                    break;
                case 2:
                    $datos['distribucion_estados']['inactivos']++;
                    break;
                case 3:
                    $datos['distribucion_estados']['completados']++;
                    break;
            }
            
            // Distribución por porcentajes de avance
            $porcentaje = $proyecto['porcentaje_entrega'] ?? 0;
            if ($porcentaje == 100) {
                $datos['distribucion_porcentajes']['100']++;
            } elseif ($porcentaje >= 75) {
                $datos['distribucion_porcentajes']['75_99']++;
            } elseif ($porcentaje >= 50) {
                $datos['distribucion_porcentajes']['50_75']++;
            } elseif ($porcentaje >= 25) {
                $datos['distribucion_porcentajes']['25_50']++;
            } else {
                $datos['distribucion_porcentajes']['0_25']++;
            }
        }
        
        // Top proyectos por unidades
        $proyectos_ordenados_unidades = $proyectos;
        usort($proyectos_ordenados_unidades, function($a, $b) {
            return ($b['total_unidades'] ?? 0) <=> ($a['total_unidades'] ?? 0);
        });
        $datos['top_proyectos_unidades'] = array_slice($proyectos_ordenados_unidades, 0, 5);
        
        // Top proyectos por porcentaje de entrega
        $proyectos_ordenados_porcentaje = $proyectos;
        usort($proyectos_ordenados_porcentaje, function($a, $b) {
            return ($b['porcentaje_entrega'] ?? 0) <=> ($a['porcentaje_entrega'] ?? 0);
        });
        $datos['top_proyectos_porcentaje'] = array_slice($proyectos_ordenados_porcentaje, 0, 5);
        
        // Evolución mensual (simplificada)
        $datos['evolucion_mensual'] = $this->calcularEvolucionMensual($proyectos);
        
        // Etiquetas más usadas
        $datos['etiquetas_mas_usadas'] = $this->obtenerEtiquetasMasUsadas($proyectos);
        
        return $datos;
    }

    // Método para calcular evolución mensual
    private function calcularEvolucionMensual($proyectos)
    {
        $evolucion = [];
        
        foreach ($proyectos as $proyecto) {
            $mes = date('Y-m', strtotime($proyecto['fecha_create'] ?? 'now'));
            
            if (!isset($evolucion[$mes])) {
                $evolucion[$mes] = [
                    'mes' => $mes,
                    'total_proyectos' => 0,
                    'total_unidades' => 0,
                    'proyectos_completados' => 0
                ];
            }
            
            $evolucion[$mes]['total_proyectos']++;
            $evolucion[$mes]['total_unidades'] += $proyecto['total_unidades'] ?? 0;
            
            if ($proyecto['estado'] == 3) {
                $evolucion[$mes]['proyectos_completados']++;
            }
        }
        
        // Ordenar por mes
        ksort($evolucion);
        
        return array_values($evolucion);
    }

    // Método para obtener etiquetas más usadas
    private function obtenerEtiquetasMasUsadas($proyectos)
    {
        $etiquetas_contador = [];
        
        foreach ($proyectos as $proyecto) {
            // Obtener etiquetas del proyecto
            $etiquetas_proyecto = parent::obtenerEtiquetasProyecto($proyecto['codigo']);
            
            foreach ($etiquetas_proyecto as $etiqueta) {
                $id = $etiqueta['etiqueta_id'];
                $nombre = $etiqueta['etiqueta_nombre'];
                
                if (!isset($etiquetas_contador[$id])) {
                    $etiquetas_contador[$id] = [
                        'id' => $id,
                        'nombre' => $nombre,
                        'cantidad_total' => 0,
                        'proyectos_count' => 0
                    ];
                }
                
                $etiquetas_contador[$id]['cantidad_total'] += $etiqueta['cantidad'] ?? 0;
                $etiquetas_contador[$id]['proyectos_count']++;
            }
        }
        
        // Ordenar por cantidad total (descendente)
        usort($etiquetas_contador, function($a, $b) {
            return $b['cantidad_total'] <=> $a['cantidad_total'];
        });
        
        return array_slice($etiquetas_contador, 0, 10); // Top 10 etiquetas
    }

        // Método para generar PDF de proyectos
        public function generarPDFProyectos($datos_reporte)
    {
        try {
            require_once('../lib/tcpdf/tcpdf.php');
            
            $datos = $datos_reporte['data'];
            $proyectos = $datos['proyectos'];
            $estadisticas = $datos['estadisticas'];
            $datos_estadisticos = $datos['datos_estadisticos'] ?? null;
            $filtros = $datos['filtros'];
            
            $tipo = $filtros['tipo'] ?? 'general';
            
            // Crear nuevo PDF según el tipo
            if ($tipo === 'detallado') {
                return $this->generarPDFDetallado($datos);
            } elseif ($tipo === 'estadistico') {
                return $this->generarPDFEstadistico($datos);
            } else {
                return $this->generarPDFGeneral($datos);
            }
            
        } catch (Exception $e) {
            error_log("Error generando PDF de proyectos: " . $e->getMessage());
            return [
                "exito" => false,
                "msj" => "Error generando PDF: " . $e->getMessage()
            ];
        }
    }

    // Método para PDF General (el que ya tienes)
    private function generarPDFGeneral($datos)
    {
        $proyectos = $datos['proyectos'];
        $estadisticas = $datos['estadisticas'];
        $filtros = $datos['filtros'];
        
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        
        // Configurar documento
        $pdf->SetCreator('Sistema de Inventarios');
        $pdf->SetAuthor('Sistema de Inventarios');
        $pdf->SetTitle('Reporte General de Proyectos');
        
        // Agregar página
        $pdf->AddPage();
        $pdf->SetMargins(15, 25, 15);
        
        // Logo y encabezado
        $this->agregarEncabezadoPDF($pdf, 'Reporte General de Proyectos');
        
        // Información del reporte
        $this->agregarInfoReportePDF($pdf, $filtros, count($proyectos));
        
        // Estadísticas generales
        $this->agregarEstadisticasGeneralesPDF($pdf, $estadisticas);
        
        // Tabla de proyectos
        $this->agregarTablaProyectosPDF($pdf, $proyectos);
        
        // Guardar PDF
        return $this->guardarPDF($pdf, 'reporte_proyectos_general_');
    }

    // Método para PDF Detallado
    private function generarPDFDetallado($datos)
    {
        $proyectos = $datos['proyectos'];
        $estadisticas = $datos['estadisticas'];
        $filtros = $datos['filtros'];
        
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        
        $pdf->SetCreator('Sistema de Inventarios');
        $pdf->SetAuthor('Sistema de Inventarios');
        $pdf->SetTitle('Reporte Detallado de Proyectos');
        
        // Agregar página inicial
        $pdf->AddPage();
        $pdf->SetMargins(15, 25, 15);
        
        // Logo y encabezado
        $this->agregarEncabezadoPDF($pdf, 'Reporte Detallado de Proyectos');
        
        // Información del reporte
        $this->agregarInfoReportePDF($pdf, $filtros, count($proyectos));
        
        // Estadísticas generales
        $this->agregarEstadisticasGeneralesPDF($pdf, $estadisticas);
        
        $y = $pdf->GetY() + 10;
        
        // Para cada proyecto, crear una sección detallada
        foreach ($proyectos as $index => $proyecto) {
            // Verificar si necesitamos nueva página
            if ($y > 250) {
                $pdf->AddPage();
                $y = 30;
            }
            
            $pdf->SetY($y);
            
            // Título del proyecto
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->SetTextColor(59, 89, 152);
            $pdf->Cell(0, 8, ($index + 1) . '. ' . $proyecto['nombre'] . ' (' . $proyecto['codigo'] . ')', 0, 1);
            
            // Información básica del proyecto
            $pdf->SetFont('helvetica', '', 9);
            $pdf->SetTextColor(0, 0, 0);
            
            $estadoTexto = $proyecto['estado_nombre'] ?? 'N/A';
            $estadoColor = $proyecto['estado'] == 1 ? [0, 128, 0] : 
                        ($proyecto['estado'] == 3 ? [255, 165, 0] : [128, 128, 128]);
            
            $pdf->SetTextColor(...$estadoColor);
            $pdf->Cell(40, 5, 'Estado:', 0, 0);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(0, 5, $estadoTexto, 0, 1);
            
            $pdf->Cell(40, 5, 'Fecha Inicio:', 0, 0);
            $pdf->Cell(0, 5, substr($proyecto['fecha_inicio'] ?? '', 0, 10), 0, 1);
            
            $pdf->Cell(40, 5, 'Total Etiquetas:', 0, 0);
            $pdf->Cell(0, 5, $proyecto['total_etiquetas'] ?? 0, 0, 1);
            
            $pdf->Cell(40, 5, 'Total Unidades:', 0, 0);
            $pdf->Cell(0, 5, number_format($proyecto['total_unidades'] ?? 0, 0, ',', '.'), 0, 1);
            
            $pdf->Cell(40, 5, 'Unid. Entregadas:', 0, 0);
            $pdf->Cell(0, 5, number_format($proyecto['unidades_entregadas'] ?? 0, 0, ',', '.'), 0, 1);
            
            $porcentaje = $proyecto['porcentaje_entrega'] ?? 0;
            $colorPorcentaje = $porcentaje >= 100 ? [0, 128, 0] : 
                            ($porcentaje >= 50 ? [255, 165, 0] : [255, 0, 0]);
            
            $pdf->Cell(40, 5, '% Avance:', 0, 0);
            $pdf->SetTextColor(...$colorPorcentaje);
            $pdf->Cell(0, 5, round($porcentaje, 1) . '%', 0, 1);
            $pdf->SetTextColor(0, 0, 0);
            
            $y = $pdf->GetY() + 5;
            
            // Tabla de etiquetas del proyecto
            if (!empty($proyecto['etiquetas'])) {
                $pdf->SetFont('helvetica', 'B', 9);
                $pdf->Cell(0, 6, 'Etiquetas del Proyecto:', 0, 1);
                
                $pdf->SetFont('helvetica', '', 8);
                
                // Encabezados de tabla de etiquetas
                $headers = ['Etiqueta', 'Tamaño', 'Cantidad', 'Entregadas', '%', 'Stock'];
                $widths = [50, 25, 25, 25, 20, 25];
                
                $pdf->SetFillColor(200, 220, 255);
                for ($i = 0; $i < count($headers); $i++) {
                    $pdf->Cell($widths[$i], 6, $headers[$i], 1, 0, 'C', 1);
                }
                $pdf->Ln();
                
                // Datos de etiquetas
                foreach ($proyecto['etiquetas'] as $etiqueta) {
                    $pdf->Cell($widths[0], 6, substr($etiqueta['etiqueta_nombre'], 0, 25), 1, 0, 'L');
                    $pdf->Cell($widths[1], 6, ($etiqueta['alto'] ?? '') . 'x' . ($etiqueta['ancho'] ?? ''), 1, 0, 'C');
                    $pdf->Cell($widths[2], 6, $etiqueta['cantidad'] ?? 0, 1, 0, 'C');
                    $pdf->Cell($widths[3], 6, $etiqueta['cantidad_entregada'] ?? 0, 1, 0, 'C');
                    
                    $porcentajeEt = $etiqueta['porcentaje_entrega'] ?? 0;
                    $pdf->Cell($widths[4], 6, round($porcentajeEt, 1) . '%', 1, 0, 'C');
                    $pdf->Cell($widths[5], 6, $etiqueta['stock_actual'] ?? 0, 1, 0, 'C');
                    $pdf->Ln();
                }
                
                $y = $pdf->GetY() + 10;
            }
            
            // Firmas (si se incluyen)
            if (!empty($proyecto['firmas']) && ($filtros['incluir_firmas'] ?? false)) {
                $pdf->SetFont('helvetica', 'B', 9);
                $pdf->Cell(0, 6, 'Firmas de Finalización:', 0, 1);
                
                foreach ($proyecto['firmas'] as $firma) {
                    $pdf->SetFont('helvetica', '', 8);
                    $pdf->Cell(0, 5, 'Firmante: ' . ($firma['nombre'] ?? 'N/A'), 0, 1);
                    $pdf->Cell(0, 5, 'Fecha: ' . substr($firma['fecha'] ?? '', 0, 10), 0, 1);
                    $pdf->Cell(0, 5, 'Comentarios: ' . substr($firma['comentarios'] ?? '', 0, 50), 0, 1);
                    $pdf->Ln(2);
                }
                
                $y = $pdf->GetY() + 5;
            }
            
            // Línea separadora entre proyectos
            $pdf->Line(15, $y, 195, $y);
            $y += 10;
        }
        
        return $this->guardarPDF($pdf, 'reporte_proyectos_detallado_');
    }

    // Método para PDF Estadístico
    private function generarPDFEstadistico($datos)
    {
        $proyectos = $datos['proyectos'];
        $estadisticas = $datos['estadisticas'];
        $datos_estadisticos = $datos['datos_estadisticos'];
        $filtros = $datos['filtros'];
        
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        
        $pdf->SetCreator('Sistema de Inventarios');
        $pdf->SetAuthor('Sistema de Inventarios');
        $pdf->SetTitle('Reporte Estadístico de Proyectos');
        
        // Agregar página
        $pdf->AddPage();
        $pdf->SetMargins(15, 25, 15);
        
        // Logo y encabezado
        $this->agregarEncabezadoPDF($pdf, 'Reporte Estadístico de Proyectos');
        
        // Información del reporte
        $this->agregarInfoReportePDF($pdf, $filtros, count($proyectos));
        
        $y = $pdf->GetY() + 10;
        
        // 1. DISTRIBUCIÓN POR ESTADOS (Gráfico de torta simulado)
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetTextColor(59, 89, 152);
        $pdf->SetY($y);
        $pdf->Cell(0, 8, '1. Distribución de Proyectos por Estado', 0, 1);
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetTextColor(0, 0, 0);
        
        $distribucion = $datos_estadisticos['distribucion_estados'];
        $total = count($proyectos);
        
        if ($total > 0) {
            $pdf->Cell(50, 6, 'Activos:', 0, 0);
            $pdf->Cell(30, 6, $distribucion['activos'], 0, 0);
            $pdf->Cell(0, 6, '(' . round(($distribucion['activos'] / $total) * 100, 1) . '%)', 0, 1);
            
            $pdf->Cell(50, 6, 'Inactivos:', 0, 0);
            $pdf->Cell(30, 6, $distribucion['inactivos'], 0, 0);
            $pdf->Cell(0, 6, '(' . round(($distribucion['inactivos'] / $total) * 100, 1) . '%)', 0, 1);
            
            $pdf->Cell(50, 6, 'Completados:', 0, 0);
            $pdf->Cell(30, 6, $distribucion['completados'], 0, 0);
            $pdf->Cell(0, 6, '(' . round(($distribucion['completados'] / $total) * 100, 1) . '%)', 0, 1);
        }
        
        $y = $pdf->GetY() + 10;
        
        // 2. DISTRIBUCIÓN POR PORCENTAJE DE AVANCE
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetTextColor(59, 89, 152);
        $pdf->SetY($y);
        $pdf->Cell(0, 8, '2. Distribución por Nivel de Avance', 0, 1);
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetTextColor(0, 0, 0);
        
        $porcentajes = $datos_estadisticos['distribucion_porcentajes'];
        
        $pdf->Cell(60, 6, '0% - 25% (Poco avance):', 0, 0);
        $pdf->Cell(30, 6, $porcentajes['0_25'], 0, 1);
        
        $pdf->Cell(60, 6, '25% - 50% (Avance medio):', 0, 0);
        $pdf->Cell(30, 6, $porcentajes['25_50'], 0, 1);
        
        $pdf->Cell(60, 6, '50% - 75% (Buen avance):', 0, 0);
        $pdf->Cell(30, 6, $porcentajes['50_75'], 0, 1);
        
        $pdf->Cell(60, 6, '75% - 99% (Casi completado):', 0, 0);
        $pdf->Cell(30, 6, $porcentajes['75_99'], 0, 1);
        
        $pdf->Cell(60, 6, '100% (Completado):', 0, 0);
        $pdf->Cell(30, 6, $porcentajes['100'], 0, 1);
        
        $y = $pdf->GetY() + 10;
        
        // 3. TOP 5 PROYECTOS POR UNIDADES
        if (!empty($datos_estadisticos['top_proyectos_unidades'])) {
            $pdf->AddPage();
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->SetTextColor(59, 89, 152);
            $pdf->SetY(30);
            $pdf->Cell(0, 8, '3. Top 5 Proyectos con Más Unidades', 0, 1);
            
            $pdf->SetFont('helvetica', '', 9);
            $pdf->SetTextColor(0, 0, 0);
            
            // Encabezados
            $headers = ['#', 'Proyecto', 'Código', 'Unidades', '% Avance'];
            $widths = [10, 60, 40, 40, 40];
            
            $pdf->SetFillColor(200, 220, 255);
            for ($i = 0; $i < count($headers); $i++) {
                $pdf->Cell($widths[$i], 7, $headers[$i], 1, 0, 'C', 1);
            }
            $pdf->Ln();
            
            // Datos
            foreach ($datos_estadisticos['top_proyectos_unidades'] as $index => $proyecto) {
                $pdf->Cell($widths[0], 6, $index + 1, 1, 0, 'C');
                $pdf->Cell($widths[1], 6, substr($proyecto['nombre'], 0, 30), 1, 0, 'L');
                $pdf->Cell($widths[2], 6, $proyecto['codigo'], 1, 0, 'C');
                $pdf->Cell($widths[3], 6, number_format($proyecto['total_unidades'] ?? 0, 0, ',', '.'), 1, 0, 'C');
                
                $porcentaje = $proyecto['porcentaje_entrega'] ?? 0;
                $pdf->Cell($widths[4], 6, round($porcentaje, 1) . '%', 1, 0, 'C');
                $pdf->Ln();
            }
        }
        
        // 4. TOP 5 PROYECTOS POR PORCENTAJE DE AVANCE
        if (!empty($datos_estadisticos['top_proyectos_porcentaje'])) {
            $y = $pdf->GetY() + 10;
            
            if ($y > 250) {
                $pdf->AddPage();
                $y = 30;
            }
            
            $pdf->SetY($y);
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->SetTextColor(59, 89, 152);
            $pdf->Cell(0, 8, '4. Top 5 Proyectos con Mejor Avance', 0, 1);
            
            $pdf->SetFont('helvetica', '', 9);
            $pdf->SetTextColor(0, 0, 0);
            
            // Encabezados
            $headers = ['#', 'Proyecto', 'Código', '% Avance', 'Unidades'];
            $widths = [10, 60, 40, 40, 40];
            
            $pdf->SetFillColor(220, 255, 220);
            for ($i = 0; $i < count($headers); $i++) {
                $pdf->Cell($widths[$i], 7, $headers[$i], 1, 0, 'C', 1);
            }
            $pdf->Ln();
            
            // Datos
            foreach ($datos_estadisticos['top_proyectos_porcentaje'] as $index => $proyecto) {
                $pdf->Cell($widths[0], 6, $index + 1, 1, 0, 'C');
                $pdf->Cell($widths[1], 6, substr($proyecto['nombre'], 0, 30), 1, 0, 'L');
                $pdf->Cell($widths[2], 6, $proyecto['codigo'], 1, 0, 'C');
                
                $porcentaje = $proyecto['porcentaje_entrega'] ?? 0;
                $pdf->Cell($widths[3], 6, round($porcentaje, 1) . '%', 1, 0, 'C');
                $pdf->Cell($widths[4], 6, number_format($proyecto['total_unidades'] ?? 0, 0, ',', '.'), 1, 0, 'C');
                $pdf->Ln();
            }
        }
        
        // 5. ETIQUETAS MÁS USADAS
        if (!empty($datos_estadisticos['etiquetas_mas_usadas'])) {
            $y = $pdf->GetY() + 10;
            
            if ($y > 250) {
                $pdf->AddPage();
                $y = 30;
            }
            
            $pdf->SetY($y);
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->SetTextColor(59, 89, 152);
            $pdf->Cell(0, 8, '5. Top 10 Etiquetas Más Utilizadas', 0, 1);
            
            $pdf->SetFont('helvetica', '', 9);
            $pdf->SetTextColor(0, 0, 0);
            
            // Encabezados
            $headers = ['#', 'Etiqueta', 'Total Unidades', 'En Proyectos'];
            $widths = [10, 90, 45, 45];
            
            $pdf->SetFillColor(255, 240, 200);
            for ($i = 0; $i < count($headers); $i++) {
                $pdf->Cell($widths[$i], 7, $headers[$i], 1, 0, 'C', 1);
            }
            $pdf->Ln();
            
            // Datos
            foreach ($datos_estadisticos['etiquetas_mas_usadas'] as $index => $etiqueta) {
                $pdf->Cell($widths[0], 6, $index + 1, 1, 0, 'C');
                $pdf->Cell($widths[1], 6, substr($etiqueta['nombre'], 0, 40), 1, 0, 'L');
                $pdf->Cell($widths[2], 6, number_format($etiqueta['cantidad_total'], 0, ',', '.'), 1, 0, 'C');
                $pdf->Cell($widths[3], 6, $etiqueta['proyectos_count'], 1, 0, 'C');
                $pdf->Ln();
            }
        }
        
        return $this->guardarPDF($pdf, 'reporte_proyectos_estadistico_');
    }

    // Métodos auxiliares para reutilización
    private function agregarEncabezadoPDF($pdf, $titulo)
    {
        // Logo
        $logo_path = '../img/icon-512x512.png';
        if (file_exists($logo_path)) {
            $pdf->Image($logo_path, 15, 15, 30, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }
        
        // Título
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->SetXY(50, 20);
        $pdf->Cell(0, 0, strtoupper($titulo), 0, 1, 'C');
        
        // Información de la empresa
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetXY(15, 45);
        $pdf->Cell(0, 0, 'Sistema de Inventarios - ' . date('d/m/Y H:i:s'), 0, 1);
    }

    private function agregarInfoReportePDF($pdf, $filtros, $totalProyectos)
    {
        $y = 55;
        $xIzquierda = 15;
        $anchoCol = 130;
        $xDerecha = 155;
        
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetXY($xIzquierda, $y);
        $pdf->Cell($anchoCol, 6, 'Parámetros del Reporte:', 0, 1);
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetX($xIzquierda);
        
        $tipoTexto = [
            'general' => 'General',
            'detallado' => 'Detallado',
            'estadistico' => 'Estadístico'
        ][$filtros['tipo']] ?? 'General';
        
        $periodo = '';
        if (!empty($filtros['fecha_desde']) && !empty($filtros['fecha_hasta'])) {
            $periodo = "Del {$filtros['fecha_desde']} al {$filtros['fecha_hasta']}";
        } else {
            $periodo = 'Todo el historial';
        }
        
        $estadosTextos = [
            '1' => 'Activos',
            '2' => 'Inactivos',
            '3' => 'Completados'
        ];
        $estadosSeleccionados = array_map(function($estado) use ($estadosTextos) {
            return $estadosTextos[$estado] ?? $estado;
        }, $filtros['estados'] ?? []);
        
        $info = [
            "Tipo: " . $tipoTexto,
            "Período: " . $periodo,
            "Estados: " . (!empty($estadosSeleccionados) ? implode(', ', $estadosSeleccionados) : 'Todos'),
            "Total proyectos: " . $totalProyectos,
            "Fecha generación: " . date('d/m/Y H:i:s')
        ];
        
        foreach ($info as $line) {
            $pdf->Cell(0, 5, $line, 0, 1);
        }
    }

    private function agregarEstadisticasGeneralesPDF($pdf, $estadisticas)
    {
        $y = $pdf->GetY() + 5;
        $xDerecha = 155;
        $anchoCol = 130;
        
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetXY($xDerecha, 55);
        $pdf->Cell($anchoCol, 6, 'Resumen Estadístico:', 0, 1);
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetX($xDerecha);
        
        $resumen = [
            "Total proyectos: " . ($estadisticas['total_proyectos'] ?? 0),
            "Activos: " . ($estadisticas['proyectos_activos'] ?? 0),
            "Inactivos: " . ($estadisticas['proyectos_inactivos'] ?? 0),
            "Completados: " . ($estadisticas['proyectos_completados'] ?? 0),
            "Total unidades: " . number_format($estadisticas['total_unidades'] ?? 0, 0, ',', '.'),
            "Unidades entregadas: " . number_format($estadisticas['total_entregadas'] ?? 0, 0, ',', '.'),
            "% promedio entrega: " . round($estadisticas['porcentaje_promedio_entrega'] ?? 0, 2) . "%"
        ];
        
        foreach ($resumen as $line) {
            $pdf->SetX($xDerecha);
            $pdf->MultiCell($anchoCol, 5, $line, 0, 'L');
        }
    }

    private function agregarTablaProyectosPDF($pdf, $proyectos)
    {
        $yInicioTabla = $pdf->GetY() + 10;
        $pdf->SetY($yInicioTabla);
        $pdf->SetFont('helvetica', 'B', 9);
        
        // Encabezados de tabla
        $headers = ['#', 'Código', 'Nombre', 'Estado', 'Etiquetas', 'Unidades', 'Entregadas', '% Avance', 'Fecha Inicio'];
        $widths = [10, 30, 70, 25, 25, 25, 25, 25, 30];
        
        $pdf->SetFillColor(59, 89, 152);
        $pdf->SetTextColor(255, 255, 255);
        
        for ($i = 0; $i < count($headers); $i++) {
            $pdf->Cell($widths[$i], 7, $headers[$i], 1, 0, 'C', 1);
        }
        $pdf->Ln();
        
        // Datos de la tabla
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(0, 0, 0);
        $contador = 1;
        
        foreach ($proyectos as $proyecto) {
            if ($pdf->GetY() > 180) {
                $pdf->AddPage();
                $pdf->SetFont('helvetica', 'B', 9);
                $pdf->SetFillColor(59, 89, 152);
                $pdf->SetTextColor(255, 255, 255);
                for ($i = 0; $i < count($headers); $i++) {
                    $pdf->Cell($widths[$i], 7, $headers[$i], 1, 0, 'C', 1);
                }
                $pdf->Ln();
                $pdf->SetFont('helvetica', '', 8);
                $pdf->SetTextColor(0, 0, 0);
            }
            
            // Color según estado
            if ($proyecto['estado'] == 1) {
                $pdf->SetTextColor(0, 128, 0);
            } elseif ($proyecto['estado'] == 3) {
                $pdf->SetTextColor(255, 165, 0);
            } else {
                $pdf->SetTextColor(128, 128, 128);
            }
            
            $pdf->Cell($widths[0], 6, $contador, 1, 0, 'C');
            $pdf->Cell($widths[1], 6, $proyecto['codigo'], 1, 0, 'C');
            $pdf->Cell($widths[2], 6, substr($proyecto['nombre'], 0, 20), 1, 0, 'L');
            $pdf->Cell($widths[3], 6, $proyecto['estado_nombre'] ?? 'N/A', 1, 0, 'C');
            $pdf->Cell($widths[4], 6, $proyecto['total_etiquetas'] ?? 0, 1, 0, 'C');
            $pdf->Cell($widths[5], 6, number_format($proyecto['total_unidades'] ?? 0, 0, ',', '.'), 1, 0, 'C');
            $pdf->Cell($widths[6], 6, number_format($proyecto['unidades_entregadas'] ?? 0, 0, ',', '.'), 1, 0, 'C');
            
            $porcentaje = $proyecto['porcentaje_entrega'] ?? 0;
            if ($porcentaje >= 100) {
                $pdf->SetTextColor(0, 128, 0);
            } elseif ($porcentaje >= 50) {
                $pdf->SetTextColor(255, 165, 0);
            } else {
                $pdf->SetTextColor(255, 0, 0);
            }
            $pdf->Cell($widths[7], 6, round($porcentaje, 1) . '%', 1, 0, 'C');
            
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell($widths[8], 6, substr($proyecto['fecha_inicio'] ?? '', 0, 10), 1, 0, 'C');
            
            $pdf->Ln();
            $contador++;
        }
    }

    private function guardarPDF($pdf, $prefijo)
    {
        $nombre_archivo = $prefijo . date('Ymd_His') . '.pdf';
        $directorio = __DIR__ . '/../reportes/proyectos';
        
        if (!file_exists('../reportes/proyectos')) {
            mkdir('../reportes/proyectos', 0777, true);
        }
        
        $ruta_archivo = $directorio . '/' . $nombre_archivo;
        $pdf->Output($ruta_archivo, 'F');
        
        return [
            "exito" => true,
            "archivo" => 'reportes/proyectos/' . $nombre_archivo,
            "nombre_archivo" => $nombre_archivo,
            "ruta_completa" => $ruta_archivo
        ];
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
                $estado = $_POST['estado'] ?? '';
                

                $respuesta = $controller->editarProyect($id, $estado, $codigo, $nombre, $descripcion, $fecha_inicio, $fecha_update, $token, $etiquetas);

            break;

            case 'finalizar_con_firma':
                $nombre = $_POST['firmante_nombre'];
                $comentarios = $_POST['comentarios'];
                $proyecto = $_POST['proyecto_id'];
                $firma = $_POST['firma'];
                $token = $_POST['token'];

                $respuesta = $controller->finalizar($nombre, $comentarios, $proyecto, $firma, $token);
            break;

            case 'generar_reporte':
                // Obtener datos del reporte
                $input = json_decode(file_get_contents('php://input'), true);
                if (!$input) {
                    $input = $_POST;
                }
                
                // Parsear estados (pueden venir como array o string separado por comas)
                $estados = [];
                if (!empty($input['estados'])) {
                    if (is_array($input['estados'])) {
                        $estados = array_map('intval', $input['estados']);
                    } else {
                        $estados = array_map('intval', explode(',', $input['estados']));
                    }
                }
                
                // Si no se especificaron estados, incluir todos
                if (empty($estados)) {
                    $estados = [1, 2, 3]; // Activo, Inactivo, Completado
                }
                
                $datos_reporte = [
                    'token' => $input['token'] ?? $_POST['token'] ?? '',
                    'tipo' => $input['tipo'] ?? $_POST['tipo'] ?? 'general',
                    'fecha_desde' => $input['fecha_desde'] ?? $_POST['fecha_desde'] ?? null,
                    'fecha_hasta' => $input['fecha_hasta'] ?? $_POST['fecha_hasta'] ?? null,
                    'estados' => $estados,
                    'con_firma' => filter_var($input['con_firma'] ?? $_POST['con_firma'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'usuario_id' => $input['usuario_id'] ?? $_POST['usuario_id'] ?? null,
                    'etiqueta_id' => $input['etiqueta_id'] ?? $_POST['etiqueta_id'] ?? null,
                    'min_unidades' => $input['min_unidades'] ?? $_POST['min_unidades'] ?? null,
                    'porcentaje_entrega' => $input['porcentaje_entrega'] ?? $_POST['porcentaje_entrega'] ?? null,
                    'formato' => $input['formato'] ?? $_POST['formato'] ?? 'pdf',
                    'incluir_fotos' => filter_var($input['incluir_fotos'] ?? $_POST['incluir_fotos'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'incluir_firmas' => filter_var($input['incluir_firmas'] ?? $_POST['incluir_firmas'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'mostrar_porcentajes' => filter_var($input['mostrar_porcentajes'] ?? $_POST['mostrar_porcentajes'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'orden_por' => $input['orden_por'] ?? $_POST['orden_por'] ?? 'fecha_create',
                    'orden_direccion' => $input['orden_direccion'] ?? $_POST['orden_direccion'] ?? 'desc'
                ];
                
                // Primero obtener los datos del reporte
                $resultado_datos = $controller->generarReporteProyectos($datos_reporte);
                
                if (!$resultado_datos['exito']) {
                    $respuesta = $resultado_datos;
                    break;
                }
                
                // Si el formato es PDF, generar PDF
                if ($datos_reporte['formato'] === 'pdf') {
                    /* print_r($resultado_datos); */
                    $resultado_pdf = $controller->generarPDFProyectos($resultado_datos);
                    $respuesta = $resultado_pdf;
                } 
                // Si solo quiere los datos (para vista previa o Excel)
                else {
                    $respuesta = $resultado_datos;
                }
                
            // Enviar respuesta
            
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