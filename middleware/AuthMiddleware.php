<?php
class AuthMiddleware {
    private $secretKey = "clave_secreta_inventario_2024"; // Cambia esto en producción
    
    // 1. GENERAR TOKEN - Crear el carnet de acceso
    public function generarToken($usuario) {
        $header = $this->base64UrlEncode(json_encode([
            'typ' => 'JWT',
            'alg' => 'HS256'
        ]));
        
        $payload = $this->base64UrlEncode(json_encode([
            'user_id' => $usuario['id'],
            'username' => $usuario['username'],
            'nombre_completo' => $usuario['nombre_completo'],
            'rol' => $usuario['rol'],
            'iat' => time(), // Fecha de creación
            'exp' => time() + (24 * 60 * 60) // Expira en 24 horas
        ]));
        
        // Crear firma digital (para evitar falsificaciones)
        $signature = $this->base64UrlEncode(
            hash_hmac('sha256', "$header.$payload", $this->secretKey, true)
        );
        
        return "$header.$payload.$signature";
    }
    
    // 2. VERIFICAR TOKEN - Revisar que el carnet sea auténtico
    public function verificarToken($token) {
        if (!$token) {
            return false;
        }
        
        // Dividir el token en sus partes
        $partes = explode('.', $token);
        if (count($partes) !== 3) {
            return false;
        }
        
        list($header, $payload, $signature) = $partes;
        
        // Verificar la firma
        $firmaCorrecta = $this->base64UrlEncode(
            hash_hmac('sha256', "$header.$payload", $this->secretKey, true)
        );
        
        if (!hash_equals($signature, $firmaCorrecta)) {
            return false;
        }
        
        // Decodificar el payload
        $datos = json_decode($this->base64UrlDecode($payload), true);
        
        // Verificar si expiró
        if (isset($datos['exp']) && $datos['exp'] < time()) {
            return false;
        }
        
        return $datos;
    }
    
    // 3. VALIDAR SESIÓN - El guardia que revisa en cada petición
    public function validarSesion() {
        // Buscar token en diferentes lugares
        $token = $this->obtenerTokenDeRequest();
        
        if (!$token) {
            $this->enviarError(401, 'Token de acceso requerido');
        }
        
        $usuario = $this->verificarToken($token);
        
        if (!$usuario) {
            $this->enviarError(401, 'Token inválido o expirado');
        }
        
        return $usuario;
    }
    
    // 4. OBTENER TOKEN DE LA PETICIÓN
    private function obtenerTokenDeRequest() {
        // 1. Buscar en POST (tu método principal)
        if (isset($_POST['token']) && !empty($_POST['token'])) {
            return $_POST['token'];
        }
        
        // 2. Buscar en GET (para algunos casos)
        if (isset($_GET['token']) && !empty($_GET['token'])) {
            return $_GET['token'];
        }
        
        // 3. Buscar en headers (para peticiones más avanzadas)
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            if (preg_match('/Bearer\s+(.*)$/i', $headers['Authorization'], $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
    
    // 5. FUNCIONES DE UTILIDAD
    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    private function base64UrlDecode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
    
    private function enviarError($codigo, $mensaje) {
        http_response_code($codigo);
        echo json_encode([
            "exito" => false,
            "msj" => $mensaje
        ]);
        exit;
    }

    // Agrega esta función a tu clase AuthMiddleware
    public function crearSesionDesdeToken($token) {
        $datos = $this->verificarToken($token);
        if ($datos) {
            return [
                'id' => $datos['user_id'],
                'nombre' => $datos['nombre_completo'],
                'rol' => $datos['rol'],
                'email' => $datos['email'] ?? ''
            ];
        }
        return false;
    }
}
?>