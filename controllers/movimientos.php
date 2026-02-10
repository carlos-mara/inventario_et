<?php
session_start();
date_default_timezone_set('America/Bogota');
setLocale(LC_ALL, "es_CO");

// Incluir los archivos necesarios
require_once "../models/Movimiento.php";
require_once "../models/Proyecto.php";
require_once "../middleware/AuthMiddleware.php";

class MovimientosControllers extends Movimiento
{
    private $tokenMiddleware;
    private $pro;

    public function __construct()
    {
        parent::__construct();
        $this->tokenMiddleware = new AuthMiddleware();
        $this->pro = new Proyecto();
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

    public function movimiento($token, $etiqueta_id, $tipo, $cantidad, $alto, $ancho, $id_tamano, $precio, $motivo = null, $referencia = null, $observaciones = null, $cantidad_anterior = 0, $cantidad_nueva = 0, $cod_proyecto = "null", $id_etiqueta_proyecto = null, $usuario_id, $fecha = null, $foto_url = null) 
    {
        try {
            $validacion = $this->verificarAcceso($token);
                if (!$validacion['exito']) {
                    return $validacion;
            }

            $this->conexion->beginTransaction();

            // Verificar que hay suficiente stock para salidas
            if ($tipo === 'salida') {
                $stock_actual = parent::obtenerCantidadActualTamano($id_tamano);
                if ($stock_actual < $cantidad) {
                    $this->conexion->rollBack();
                    return [
                        "exito" => false,
                        "msj" => "Stock insuficiente. Stock actual: " . $stock_actual
                    ];
                }
                
                // Si hay proyecto, verificar cantidad restante asignada
                if ($cod_proyecto != null && $cod_proyecto != "null" && $cod_proyecto != "") {
                    $info_proyecto = $this->pro->obtenerCantidadesProyectoEtiqueta($cod_proyecto, $id_tamano);
                    
                    $cantidad_restante = $info_proyecto['cantidad_asignada'] - $info_proyecto['cantidad_entregada'];
                    
                    if ($cantidad_restante < $cantidad) {
                        $this->conexion->rollBack();
                        return [
                            "exito" => false,
                            "msj" => "Cantidad excede lo asignado al proyecto. Restante: " . $cantidad_restante
                        ];
                    }
                }
            }

            $cantidad_anterior = parent::obtenerCantidadActualTamano($id_tamano);
            $cantidad_anterior_et = parent::obtenerCantidadActualEt($etiqueta_id);
            if ($tipo === 'entrada') {
                $cantidad_nueva = $cantidad_anterior + $cantidad;
                $cantidad_nueva_et = $cantidad_anterior_et + $cantidad;
            } elseif ($tipo === 'salida') {
                $cantidad_nueva = $cantidad_anterior - $cantidad;
                $cantidad_nueva_et = $cantidad_anterior_et - $cantidad;
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
                ':id_proyecto'     => $cod_proyecto,
                ':id_etiqueta_proyecto' => $id_etiqueta_proyecto,
                ':usuario_id'       => $usuario_id,
                ':fecha'            => $fecha,
                ':foto'             => $foto_url
            ];
            
            $result = parent::registrarMovimiento($parametros);
            
            if ($result) {
                
                $act_cant_tamano = parent::actualizarCantidadEtiquetaTamano($id_tamano, $cantidad_nueva);
                if (!$act_cant_tamano) {
                    $this->conexion->rollBack();
                    return [
                        "exito" => false,
                        "msj" => "Error al actualizar el stock del tamaño"
                    ];
                }
                //se actualiza la cantidad total de la etiqueta (todos sus tamaños)
                $act_cant_total = parent::actualizarCantidadEtiqueta($etiqueta_id, $cantidad_nueva_et);
                if (!$act_cant_total) {
                    $this->conexion->rollBack();
                    return [
                        "exito" => false,
                        "msj" => "Error al actualizar el stock total de la etiqueta"
                    ];
                }
                
                // Actualizar cantidad entregada en proyecto_etiquetas si hay proyecto
                if ($cod_proyecto && $tipo === 'salida'){
                    $this->pro->actualizarCantidadEntregada($cod_proyecto, $id_etiqueta_proyecto, $cantidad);
                }
                $this->conexion->commit();
                return [
                    "exito" => true,
                    "msj" => "Movimiento registrado exitosamente"
                ];
            } else {
                $this->conexion->rollBack();
                return [
                    "exito" => false,
                    "msj" => "Error al registrar el movimiento"
                ];
            }
        } catch (Exception $e) {
            error_log("Error en movimiento: " . $e->getMessage());
            return [
                "exito" => false,
                "msj" => "Error registrando el movimiento: " . $e->getMessage()
            ];
        }
    }

    

    public function listarMovimientos($token) 
    {
        try {
            $validacion = $this->verificarAcceso($token);
                if (!$validacion['exito']) {
                    return $validacion;
            }

            $movimientos = parent::obtenerMovimientos();

            return [
                "exito" => true,
                "data" => $movimientos
            ];
        } catch (Exception $e) {
            error_log("Error en listarMovimientos: " . $e->getMessage());
            return [
                "exito" => false,
                "msj" => "Error obteniendo los movimientos"
            ];
        }
    }

    public function obtenerHistorialPorFecha($fecha, $token) 
    {
        try {
            $validacion = $this->verificarAcceso($token);
                if (!$validacion['exito']) {
                    return $validacion;
            }

            $movimientos = parent::obtenerMovimientosPorFecha($fecha);

            return [
                "exito" => true,
                "data" => $movimientos
            ];
        } catch (Exception $e) {
            error_log("Error en listarMovimientos: " . $e->getMessage());
            return [
                "exito" => false,
                "msj" => "Error obteniendo los movimientos"
            ];
        }
    }

    public function anularMovimiento($token, $movimiento_id, $motivo_anulacion) 
    {
        try {
            $validacion = $this->verificarAcceso($token);
            if (!$validacion['exito']) {
                    return $validacion;
            }
            $this->conexion->beginTransaction();
            // 2. Obtener movimiento
            $movimiento = parent::obtenerMovimientoPorId($movimiento_id);
            
            $ajustar = $this->ajustarStockPorAnulacion($movimiento[0]);

            if($ajustar['exito']){
                
                $marcar = parent::marcarComoAnulado($movimiento_id);

                if(!$marcar){
                    $this->conexion->rollBack();
                    return ['exito' => false, 'msj' => 'Error al marcar anulado'];
                }

            }else {
                $this->conexion->rollBack();
                return ['exito' => false, 'msj' => 'Error al ajustar stock'];
            }
            
            // 5. Marcar como anulado (soft delete)
            
            $this->conexion->commit();
            return ['exito' => true, 'msj' => 'Movimiento anulado'];
            
        } catch (Exception $e) {
            // Rollback
            $this->conexion->rollBack();
            return ['exito' => false, 'msj' => $e->getMessage()];
        }
    }

    public function ajustarStockPorAnulacion($movimiento)
    {
        try {
            // 1. Obtener el ID del tamaño
            $id_tamano = parent::obtenerIdTamanoPorDimensiones(
                $movimiento['alto'], 
                $movimiento['ancho']
            );
            
            if (!$id_tamano) {
                throw new Exception("No se encontró el tamaño para las dimensiones: {$movimiento['alto']}x{$movimiento['ancho']}");
            }
            
            // 2. Obtener cantidades actuales
            $cantidad_actual_tamano = $this->obtenerCantidadActualTamano($id_tamano);
            $cantidad_actual_etiqueta = $this->obtenerCantidadActualEt($movimiento['etiqueta_id']);
            
            // 3. Calcular nuevo stock según tipo de movimiento
            $nueva_cantidad_tamano = 0;
            $nueva_cantidad_etiqueta = 0;
            
            if ($movimiento['tipo'] === 'entrada') {
                // Si anulamos una ENTRADA, debemos RESTAR esa cantidad
                $nueva_cantidad_tamano = $cantidad_actual_tamano - $movimiento['cantidad'];
                $nueva_cantidad_etiqueta = $cantidad_actual_etiqueta - $movimiento['cantidad'];
                
                // Validar que no quede negativo
                if ($nueva_cantidad_tamano < 0) {
                    throw new Exception("No se puede anular: el stock quedaría negativo en este tamaño");
                }
                
                if ($nueva_cantidad_etiqueta < 0) {
                    throw new Exception("No se puede anular: el stock total de la etiqueta quedaría negativo");
                }
                
            } elseif ($movimiento['tipo'] === 'salida') {
                // Si anulamos una SALIDA, debemos SUMAR esa cantidad
                $nueva_cantidad_tamano = $cantidad_actual_tamano + $movimiento['cantidad'];
                $nueva_cantidad_etiqueta = $cantidad_actual_etiqueta + $movimiento['cantidad'];
                
            } else {
                throw new Exception("Tipo de movimiento no válido: {$movimiento['tipo']}");
            }
            
            // 4. Actualizar stock en la tabla tamanos_etiquetas
            $this->actualizarCantidadEtiquetaTamano($id_tamano, $nueva_cantidad_tamano);
            
            // 5. Actualizar stock total en la tabla etiquetas
            $this->actualizarCantidadEtiqueta($movimiento['etiqueta_id'], $nueva_cantidad_etiqueta);
            
            // 6. Si es una salida con proyecto, revertir cantidad entregada
            if ($movimiento['tipo'] === 'salida' && !empty($movimiento['cod_proyecto'])) {
                $this->revertirCantidadEntregadaProyecto(
                    $movimiento['cod_proyecto'],
                    $movimiento['etiqueta_id'],
                    $movimiento['cantidad']
                );
            }
            
            return [
                'exito' => true,
                'datos' => [
                    'id_tamano' => $id_tamano,
                    'stock_anterior_tamano' => $cantidad_actual_tamano,
                    'stock_nuevo_tamano' => $nueva_cantidad_tamano,
                    'stock_anterior_etiqueta' => $cantidad_actual_etiqueta,
                    'stock_nuevo_etiqueta' => $nueva_cantidad_etiqueta
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Error en ajustarStockPorAnulacion: " . $e->getMessage());
            throw $e; // Re-lanzar para manejo superior
        }
    }

    // Agrega este método dentro de la clase MovimientosControllers
    public function generarReporteMovimientos($datos)
    {
        try {
            $validacion = $this->verificarAcceso($datos['token'] ?? '');
            if (!$validacion['exito']) {
                return $validacion;
            }

            $result = parent::obtenerInfoReporte($datos);
            
            $movimientos = [];
            $total_entradas = 0;
            $total_salidas = 0;
            $total_cantidad = 0;
            
            foreach ($result as $row) {
                $movimientos[] = $row;
                
                // Calcular totales
                if ($row['tipo'] === 'entrada') {
                    $total_entradas += $row['cantidad'];
                } elseif ($row['tipo'] === 'salida') {
                    $total_salidas += $row['cantidad'];
                }
                $total_cantidad += $row['cantidad'];
            }
            
            return [
                "exito" => true,
                "data" => [
                    "movimientos" => $movimientos,
                    "total_entradas" => $total_entradas,
                    "total_salidas" => $total_salidas,
                    "total_movimientos" => count($movimientos),
                    "total_cantidad" => $total_cantidad,
                    "filtros" => $datos
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Error en generarReporteMovimientos: " . $e->getMessage());
            return [
                "exito" => false,
                "msj" => "Error generando reporte: " . $e->getMessage()
            ];
        }
    }

    // Agrega este método para generar PDF (requiere TCPDF o similar)
    public function generarPDF($datos_reporte)
    {
        try {
            // Incluir TCPDF (descárgalo de https://github.com/tecnickcom/TCPDF)
            require_once('../lib/tcpdf/tcpdf.php');
            
            $datos = $datos_reporte['data'];
            $filtros = $datos['filtros'];
            
            // Crear nuevo PDF
            $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
            
            $yInicio = 55;
            $xIzquierda = 15;
            $anchoCol = 130;
            $xDerecha = 155;
            
            // Configurar documento
            $pdf->SetCreator('Sistema de Inventarios');
            $pdf->SetAuthor('Sistema de Inventarios');
            $pdf->SetTitle('Reporte de Movimientos');
            $pdf->SetSubject('Reporte de Movimientos de Inventario');
            
            // Agregar página
            $pdf->AddPage();
            
            // Configurar márgenes
            $pdf->SetMargins(15, 25, 15);
            
            // Logo (opcional)
            $logo_path = '../img/icon-512x512.png';
            if (file_exists($logo_path)) {
                $pdf->Image($logo_path, 15, 15, 30, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
            }
            
            // Título
            $pdf->SetFont('helvetica', 'B', 16);
            $pdf->SetXY(50, 20);
            $pdf->Cell(0, 0, 'REPORTE DE MOVIMIENTOS DE INVENTARIO', 0, 1, 'C');
            
            // Información de la empresa
            $pdf->SetFont('helvetica', '', 10);
            $pdf->SetXY(15, 45);
            $pdf->Cell(0, 0, 'Sistema de Inventarios - ' . date('d/m/Y H:i:s'), 0, 1);
            
            // Información del reporte
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->SetXY($xIzquierda, $yInicio);
            $pdf->Cell($anchoCol, 6, 'Parámetros del Reporte:', 0, 1);
            
            $pdf->SetFont('helvetica', '', 10);
            $pdf->SetX($xIzquierda);
            
            $periodo = $filtros['todo_historial'] ? 
                'Todo el historial' : 
                "Del {$filtros['fecha_desde']} al {$filtros['fecha_hasta']}";
            
            $info = [
                "Período: " . $periodo,
                "Tipo de movimiento: " . ($filtros['tipo_movimiento'] ?: 'Todos'),
                "Formato: " . strtoupper($filtros['formato']),
                "Ordenado por: " . $filtros['orden_por'],
                "Total movimientos: " . $datos['total_movimientos'],
                "Fecha generación: " . date('d/m/Y H:i:s')
            ];
            
            foreach ($info as $line) {
                $pdf->Cell($anchoCol, 5, $line, 0, 'L');
            }
            $yFinIzq = $pdf->GetY();
            
            // Resumen estadístico
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->SetXY($xDerecha, $yInicio);
            $pdf->Cell($anchoCol, 6, 'Resumen Estadístico:', 0, 1);
            
            $pdf->SetFont('helvetica', '', 10);
            $pdf->SetX($xDerecha);
            
            $resumen = [
                "Total entradas: " . number_format($datos['total_entradas'], 0, ',', '.'),
                "Total salidas: " . number_format($datos['total_salidas'], 0, ',', '.'),
                "Diferencia neta: " . number_format($datos['total_entradas'] - $datos['total_salidas'], 0, ',', '.'),
                "Total movimientos registrados: " . $datos['total_movimientos']
            ];
            
            foreach ($resumen as $line) {
                $pdf->SetX($xDerecha);
                $pdf->MultiCell($anchoCol, 5, $line, 0, 'L');
            }
            $yFinDer = $pdf->GetY();
            // Tabla de movimientos
            $yTabla = max($yFinIzq, $yFinDer) + 8;
            $pdf->SetY($yTabla);
            /* $pdf->SetY(115); */
            $pdf->SetFont('helvetica', 'B', 9);
            
            // Encabezados de tabla
            $headers = ['#', 'Fecha', 'Etiqueta', 'Tipo', 'Cantidad', 'Motivo', 'Usuario', 'Observaciones'];
            $widths = [10, 25, 65, 20, 10, 40, 30, 68];
            $pdf->SetTextColor(255, 255, 255);  // Blanco (texto)
            for ($i = 0; $i < count($headers); $i++) {
                $pdf->Cell($widths[$i], 7, $headers[$i], 1, 0, 'C', 1);
            }
            $pdf->Ln();
            
            // Datos de la tabla
            $pdf->SetFont('helvetica', '', 8);
            $contador = 1;
            /* $pdf->SetTextColor(0, 0, 0); */
            foreach ($datos['movimientos'] as $movimiento) {
                // Control de página
                if ($pdf->GetY() > 180) {
                    $pdf->AddPage();
                    // Repetir encabezados
                    $pdf->SetFont('helvetica', 'B', 9);
                    for ($i = 0; $i < count($headers); $i++) {
                        $pdf->Cell($widths[$i], 7, $headers[$i], 1, 0, 'C', 1);
                    }
                    $pdf->Ln();
                    $pdf->SetFont('helvetica', '', 8);
                }
                
                /* $tipo_color = $movimiento['tipo'] === 'entrada' ? '0,128,0' : '255,0,0'; */
                if ($movimiento['tipo'] === 'entrada') {
                    $pdf->SetTextColor(0, 128, 0); // verde
                } else {
                    $pdf->SetTextColor(255, 0, 0); // rojo
                }
                /* $pdf->SetTextColor(0, 0, 0); */
                
                $pdf->Cell($widths[0], 6, $contador, 1, 0, 'C');
                $pdf->Cell($widths[1], 6, $movimiento['fecha_formateada'], 1, 0, 'C');
                $pdf->Cell($widths[2], 6, substr($movimiento['etiqueta_nombre'], 0, 25), 1, 0, 'C');
                $pdf->Cell($widths[3], 6, strtoupper($movimiento['tipo']), 1, 0, 'C');
                $pdf->Cell($widths[4], 6, number_format($movimiento['cantidad'], 0, ',', '.'), 1, 0, 'C');
                $pdf->Cell($widths[5], 6, substr($movimiento['motivo'] ?? '', 0, 20), 1, 0, 'C');
                $pdf->Cell($widths[6], 6, substr($movimiento['usuario_nombre'] ?? '', 0, 15), 1, 0, 'C');
                $pdf->Cell($widths[7], 6, substr($movimiento['observaciones'] ?? '', 0, 25), 1, 0, 'C');
                
                $pdf->Ln();
                $pdf->SetTextColor(0, 0, 0);
                $contador++;
            }
            
            // Pie de página
            /* $pdf->SetY(max($yFinIzq, $yFinDer));
            $pdf->SetFont('helvetica', 'I', 8);
            $pdf->Cell(0, 10, 'Página ' . $pdf->getAliasNumPage() . ' de ' . $pdf->getAliasNbPages(), 0, 0, 'C'); */
            
            // Generar nombre de archivo
            $nombre_archivo = 'reporte_movimientos_' . date('Ymd_His') . '.pdf';
            $directorio = __DIR__ . '/../reportes/movimientos';
            
            // Crear directorio si no existe
            if (!file_exists('../reportes/movimientos')) {
                mkdir('../reportes/movimientos', 0777, true);
            }
            $ruta_archivo = $directorio . '/' . $nombre_archivo;
            // Guardar PDF
            $pdf->Output($ruta_archivo, 'F');
            
            return [
                "exito" => true,
                "archivo" => 'reportes/' . $nombre_archivo,
                "nombre_archivo" => $nombre_archivo,
                "ruta_completa" => $ruta_archivo
            ];
            
        } catch (Exception $e) {
            error_log("Error generando PDF: " . $e->getMessage());
            return [
                "exito" => false,
                "msj" => "Error generando PDF: " . $e->getMessage()
            ];
        }
    }

    public function revertirCantidadEntregadaProyectoE($id_etiqueta_proyecto, $cantidad)
    {
        try {
            $this->pro->revertirCantidadEntregada($id_etiqueta_proyecto, $cantidad);
            return [
                'exito' => true,
                'msj' => 'Cantidad entregada revertida en proyecto_etiquetas'
            ];
        } catch (Exception $e) {
            error_log("Error en revertirCantidadEntregada Proyecto: " . $e->getMessage());
            return [
                'exito' => false,
                'msj' => 'Error al revertir cantidad entregada: ' . $e->getMessage()
            ];
        }
    }

}

// =============================================
// PROCESAMIENTO DE PETICIONES
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

// Función para guardar la foto
function guardarFotoBase64($foto_base64, $etiqueta_id, $tipo)
{
    try {
        if (empty($foto_base64)) {
            return null;
        }
        
        // Directorio de uploads
        $upload_dir = "../uploads/evidencias/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Generar nombre único para el archivo
        $timestamp = time();
        $filename = "evidencia_{$tipo}_{$etiqueta_id}_{$timestamp}.jpg";
        $filepath = $upload_dir . $filename;
        
        // Decodificar base64 (eliminar el prefijo data:image/jpeg;base64,)
        if (strpos($foto_base64, 'base64,') !== false) {
            $foto_base64 = explode('base64,', $foto_base64)[1];
        }
        
        $foto_data = base64_decode($foto_base64);
        
        // Guardar archivo
        if (file_put_contents($filepath, $foto_data)) {
            return "evidencias/" . $filename;
        }
        
        return null;
    } catch (Exception $e) {
        error_log("Error guardando foto: " . $e->getMessage());
        return null;
    }
}

// Permitir tanto POST como GET para datos públicos
if (isset($_POST["peticion"]) || isset($_GET["peticion"])) {
    $peticion = $_POST["peticion"] ?? $_GET["peticion"] ?? '';
    $mov = new MovimientosControllers();
    
    $respuesta = [
        "exito" => false,
        "msj" => "Petición no reconocida"
    ];

    try {
        switch ($peticion) {

            case 'devolver_etiquetas_pro':
                $etiqueta_id     = $_POST['etiqueta_id'] ?? null;
                $cantidad        = intval($_POST['cantidad']) ?? null;
                $precio          = $_POST['precio_unitario'] ?? 0;
                $motivo          = $_POST['motivo'] ?? null;
                $referencia      = $_POST['referencia'] ?? null;
                $observaciones   = $_POST['observaciones'] ?? null;
                $cod_proyecto    = $_POST['cod_proyecto'] ?? null;
                $usuario_id      = $_POST['usuario_id'] ?? null;
                $fecha           = date('Y-m-d H:i:s');
                $token           = $_POST['token'] ?? null;
                $alto            = $_POST['alto'] ?? null;
                $ancho           = $_POST['ancho'] ?? null;
                $id_tamano       = $_POST['tamano_id'] ?? null;
                $id_etiqueta_proyecto = intval($_POST['idEP']) ?? null;

                /* ajustar cantidad entregada de etiqueta proyecto */
                $revertir = $mov->revertirCantidadEntregadaProyectoE($id_etiqueta_proyecto, $cantidad);

                if(!$revertir['exito']){
                    $respuesta = $revertir;
                    break;
                }else{
                    $resultado = $mov->movimiento(
                        $token,
                        $etiqueta_id,
                        'entrada',
                        $cantidad,
                        $alto,
                        $ancho,
                        $id_tamano,
                        $precio,
                        $motivo,
                        $referencia,
                        $observaciones,
                        0,
                        0,
                        $cod_proyecto,
                        $id_etiqueta_proyecto,
                        $usuario_id,
                        $fecha
                    );
                    $respuesta = $resultado;
                }

            break;

            case 'registrar_entrada':

                $etiqueta_id     = $_POST['etiqueta_id'] ?? null;
                $cantidad        = $_POST['cantidad'] ?? null;
                $precio          = $_POST['precio_unitario'] ?? 0;
                $motivo          = $_POST['motivo'] ?? null;
                $referencia      = $_POST['referencia'] ?? null;
                $observaciones   = $_POST['observaciones'] ?? null;
                $cod_proyecto    = $_POST['cod_proyecto'] ?? null;
                $usuario_id      = $_POST['usuario_id'] ?? null;
                $fecha           = date('Y-m-d H:i:s');
                $token           = $_POST['token'] ?? null;
                $alto            = $_POST['alto'] ?? null;
                $ancho           = $_POST['ancho'] ?? null;
                $id_tamano       = $_POST['tamano_id'] ?? null;
                $id_etiqueta_proyecto = $_POST['idEP'] ?? null;

                $resultado = $mov->movimiento(
                    $token,
                    $etiqueta_id,
                    'entrada',
                    $cantidad,
                    $alto,
                    $ancho,
                    $id_tamano,
                    $precio,
                    $motivo,
                    $referencia,
                    $observaciones,
                    0,
                    0,
                    $cod_proyecto,
                    $id_etiqueta_proyecto,
                    $usuario_id,
                    $fecha
                );
                $respuesta = $resultado;
            break;

            case 'registrar_salida':
                $etiqueta_id     = $_POST['etiqueta_id'] ?? null;
                $cantidad        = $_POST['cantidad'] ?? null;
                $motivo          = $_POST['motivo'] ?? null;
                $referencia      = $_POST['referencia'] ?? null;
                $observaciones   = $_POST['observaciones'] ?? null;
                $cod_proyecto    = $_POST['proyecto_id'] ?? null;
                $usuario_id      = $_POST['usuario_id'] ?? null;
                $fecha           = date('Y-m-d H:i:s');
                $token           = $_POST['token'] ?? null;
                $alto            = $_POST['alto'] ?? null;
                $ancho           = $_POST['ancho'] ?? null;
                $id_tamano       = $_POST['tamano_id'] ?? null;
                $foto_base64     = $_POST['foto_base64'] ?? null;
                $id_etiqueta_proyecto = $_POST['id_etiqueta_proyecto'] ?? null;

                if($id_etiqueta_proyecto == "null"){
                    $id_etiqueta_proyecto = null;
                }
                
                // Guardar foto si existe
                $foto_url = null;
                if (!empty($foto_base64)) {
                    $foto_url = guardarFotoBase64($foto_base64, $etiqueta_id, 'salida');
                }

                $resultado = $mov->movimiento(
                    $token,
                    $etiqueta_id,
                    'salida',
                    $cantidad,
                    $alto,
                    $ancho,
                    $id_tamano,
                    0,
                    $motivo,
                    $referencia,
                    $observaciones,
                    0,
                    0,
                    $cod_proyecto,
                    $id_etiqueta_proyecto ?? NULL,
                    $usuario_id,
                    $fecha,
                    $foto_url
                );
                $respuesta = $resultado;
            break;

            case 'registrar_salidas_multiples':
                $salidas = json_decode($_POST['items'] ?? '[]', true);
                $token = $_POST['token'] ?? null;
                $motivo          = $_POST['motivo'] ?? null;
                $referencia      = $_POST['referencia'] ?? null;
                $observaciones   = $_POST['observaciones'] ?? null;
                $cod_proyecto    = $_POST['proyecto_id'] ?? null;
                $usuario_id      = $_POST['usuario_id'] ?? null;
                $fecha           = date('Y-m-d H:i:s');
                $resultados = [];
                $foto_base64     = $_POST['foto_base64'] ?? null;
                
                // Guardar foto si existe
                $foto_url = null;
                if (!empty($foto_base64)) {
                    $foto_url = guardarFotoBase64($foto_base64, 00, 'salida');
                }
                foreach ($salidas as $salida) {
                    

                    $resultado = $mov->movimiento(
                        $token,
                        $salida['etiqueta_id'],
                        'salida',
                        $salida['cantidad'],
                        $salida['alto'],
                        $salida['ancho'],
                        $salida['tamano_id'],
                        0,
                        $motivo ?? null,
                        $referencia ?? null,
                        $observaciones ?? null,
                        0,
                        0,
                        $cod_proyecto ?? NULL,
                        $salida['id_etiqueta_proyecto'] ?? NULL,
                        $usuario_id,
                        $fecha,
                        $foto_url
                    );
                    $resultados[] = [
                        'etiqueta_id' => $salida['etiqueta_id'],
                        'resultado' => $resultado
                    ];
                }
                
                $respuesta = [
                    "exito" => true,
                    "resultados" => $resultados
                ];
            break;

            case 'historial':
                $token = $_POST['token'] ?? $_GET['token'] ?? '';
                if(isset($_POST['fecha'])){
                    $fecha = $_POST['fecha'];
                    $respuesta = $mov->obtenerHistorialPorFecha($fecha, $token);
                    echo json_encode($respuesta);
                    exit;
                }
                $respuesta = $mov->listarMovimientos($token);
            break;
            
            case 'test':
                $respuesta = [
                    "exito" => true,
                    "msj" => "Controlador funcionando correctamente",
                    "post_data" => $_POST
                ];
            break;

            case 'anular_movimiento':
                $movimiento_id = $_POST['movimiento_id'] ?? null;
                $token = $_POST['token'] ?? '';
                $motivo_anulacion = $_POST['motivo'] ?? '';
                
                if (empty($movimiento_id)) {
                    $respuesta = [
                        "exito" => false,
                        "msj" => "ID de movimiento requerido"
                    ];
                    break;
                }
                
                $respuesta = $mov->anularMovimiento($token, $movimiento_id, $motivo_anulacion);
            break;

            case 'generar_reporte':
                // Obtener datos del reporte (pueden venir como JSON)
                $input = json_decode(file_get_contents('php://input'), true);
                if (!$input) {
                    $input = $_POST;
                }

                $datos_reporte = [
                    'token' => $input['token'] ?? $_POST['token'] ?? '',
                    'fecha_desde' => $input['fecha_desde'] ?? $_POST['fecha_desde'] ?? date('Y-m-01'),
                    'fecha_hasta' => $input['fecha_hasta'] ?? $_POST['fecha_hasta'] ?? date('Y-m-d'),
                    'todo_historial' => filter_var($input['todo_historial'] ?? $_POST['todo_historial'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'tipo_movimiento' => $input['tipo_movimiento'] ?? $_POST['tipo_movimiento'] ?? '',
                    'etiqueta_id' => $input['etiqueta_id'] ?? $_POST['etiqueta_id'] ?? '',
                    'usuario_id' => $input['usuario_id'] ?? $_POST['usuario_id'] ?? '',
                    'cantidad_minima' => $input['cantidad_minima'] ?? $_POST['cantidad_minima'] ?? '',
                    'formato' => $input['formato'] ?? $_POST['formato'] ?? 'pdf',
                    'orden_por' => $input['orden_por'] ?? $_POST['orden_por'] ?? 'fecha',
                    'orden_direccion' => $input['orden_direccion'] ?? $_POST['orden_direccion'] ?? 'desc'
                ];

                // Primero obtener los datos del reporte
                $resultado_datos = $mov->generarReporteMovimientos($datos_reporte);

                if (!$resultado_datos['exito']) {
                    $respuesta = $resultado_datos;
                    break;
                }
                
                // Si el formato es PDF, generar PDF
                if ($datos_reporte['formato'] === 'pdf') {
                    $resultado_pdf = $mov->generarPDF($resultado_datos);
                    $respuesta = $resultado_pdf;
                } 
                // Si el formato es Excel (implementar similarmente)
                /* elseif ($datos_reporte['formato'] === 'excel') {
                    $resultado_excel = $mov->generarExcel($resultado_datos);
                    $respuesta = $resultado_excel;
                }  */
                // Si solo quiere los datos
                else {
                    $respuesta = $resultado_datos;
                }
            break;

            default:
                $respuesta = [
                    "exito" => false,
                    "msj" => "Petición no reconocida: " . $peticion
                ];
            break;
        }

    } catch (Exception $e) {
        error_log("Error en movimientos controller: " . $e->getMessage());
        $respuesta = [
            "exito" => false,
            "msj" => "Error interno del servidor: " . $e->getMessage()
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