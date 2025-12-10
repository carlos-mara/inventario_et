<?php
require_once __DIR__ . '/../conf/conexion.php';

class Usuario {
    private $conexion;
    
    public function __construct() {
        $this->conexion = new Conexion();
    }
    
    /**
     * Iniciar sesión de usuario
     */
    public function login($email, $password) {
        try {
            $sql = "SELECT id, username, email, nombre_completo, rol, password_hash, activo 
                    FROM usuarios 
                    WHERE email = :email AND activo = 1 
                    LIMIT 1";
            
            $parametros = [":email" => $email];
            $resultado = $this->conexion->ejecutarConParametros($sql, $parametros);
            $usuario = $resultado->fetch(PDO::FETCH_ASSOC);
            
            // Verificar si encontró el usuario y si la contraseña coincide
            if ($usuario && password_verify($password, $usuario['password_hash'])) {
                // Limpiar datos sensibles antes de devolver
                unset($usuario['password_hash']);
                return $usuario;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Error en login: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crear nuevo usuario
     */
    public function crear($datos) {
        try {
            // Validar datos requeridos
            if (empty($datos['username']) || empty($datos['email']) || empty($datos['password'])) {
                throw new Exception("Datos incompletos para crear usuario");
            }
            
            // Verificar si el email ya existe
            if ($this->existeEmail($datos['email'])) {
                throw new Exception("El email ya está registrado");
            }
            
            // Verificar si el username ya existe
            if ($this->existeUsername($datos['username'])) {
                throw new Exception("El username ya está en uso");
            }
            
            $sql = "INSERT INTO usuarios 
                    (username, email, password_hash, nombre_completo, rol) 
                    VALUES (:username, :email, :password_hash, :nombre_completo, :rol)";
            
            $parametros = [
                ':username' => $datos['username'],
                ':email' => $datos['email'],
                ':password_hash' => password_hash($datos['password'], PASSWORD_DEFAULT),
                ':nombre_completo' => $datos['nombre_completo'] ?? $datos['username'],
                ':rol' => $datos['rol'] ?? 'gestor'
            ];
            
            $id = $this->conexion->insertar($sql, $parametros);
            return $id;
            
        } catch (Exception $e) {
            error_log("Error creando usuario: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener usuario por ID
     */
    public function obtener($id) {
        try {
            $sql = "SELECT id, username, email, nombre_completo, rol, activo, fecha_creacion 
                    FROM usuarios 
                    WHERE id = :id AND activo = 1";
            
            $parametros = [":id" => $id];
            $resultado = $this->conexion->ejecutarConParametros($sql, $parametros);
            return $resultado->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error obteniendo usuario: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Listar todos los usuarios (excluyendo contraseñas)
     */
    public function listar($filtros = []) {
        try {
            $sql = "SELECT id, username, email, nombre_completo, rol, activo, fecha_creacion, fecha_actualizacion 
                    FROM usuarios 
                    WHERE 1=1";
            
            $parametros = [];
            
            // Filtrar por rol si se especifica
            if (!empty($filtros['rol'])) {
                $sql .= " AND rol = :rol";
                $parametros[':rol'] = $filtros['rol'];
            }
            
            // Filtrar por estado activo/inactivo
            if (isset($filtros['activo']) && $filtros['activo'] !== '') {
                $sql .= " AND activo = :activo";
                $parametros[':activo'] = $filtros['activo'];
            }
            
            // Búsqueda por nombre o email
            if (!empty($filtros['busqueda'])) {
                $sql .= " AND (nombre_completo LIKE :busqueda OR email LIKE :busqueda OR username LIKE :busqueda)";
                $parametros[':busqueda'] = "%{$filtros['busqueda']}%";
            }
            
            $sql .= " ORDER BY nombre_completo ASC";
            
            $resultado = $this->conexion->ejecutarConParametros($sql, $parametros);
            return $resultado->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error listando usuarios: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Actualizar usuario
     */
    public function actualizar($id, $datos) {
        try {
            // Construir SQL dinámicamente según los campos a actualizar
            $campos = [];
            $parametros = [':id' => $id];
            
            if (isset($datos['username'])) {
                $campos[] = "username = :username";
                $parametros[':username'] = $datos['username'];
            }
            
            if (isset($datos['email'])) {
                $campos[] = "email = :email";
                $parametros[':email'] = $datos['email'];
            }
            
            if (isset($datos['nombre_completo'])) {
                $campos[] = "nombre_completo = :nombre_completo";
                $parametros[':nombre_completo'] = $datos['nombre_completo'];
            }
            
            if (isset($datos['rol'])) {
                $campos[] = "rol = :rol";
                $parametros[':rol'] = $datos['rol'];
            }
            
            if (isset($datos['activo'])) {
                $campos[] = "activo = :activo";
                $parametros[':activo'] = $datos['activo'];
            }
            
            // Si se proporciona nueva contraseña, hashearla
            if (isset($datos['password']) && !empty($datos['password'])) {
                $campos[] = "password_hash = :password_hash";
                $parametros[':password_hash'] = password_hash($datos['password'], PASSWORD_DEFAULT);
            }
            
            if (empty($campos)) {
                throw new Exception("No hay campos para actualizar");
            }
            
            $sql = "UPDATE usuarios SET " . implode(', ', $campos) . " WHERE id = :id";
            $resultado = $this->conexion->ejecutarConParametros($sql, $parametros);
            
            return $resultado->rowCount() > 0;
            
        } catch (Exception $e) {
            error_log("Error actualizando usuario: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Desactivar usuario (borrado lógico)
     */
    public function desactivar($id) {
        try {
            $sql = "UPDATE usuarios SET activo = 0 WHERE id = :id";
            $parametros = [':id' => $id];
            
            $resultado = $this->conexion->ejecutarConParametros($sql, $parametros);
            return $resultado->rowCount() > 0;
            
        } catch (Exception $e) {
            error_log("Error desactivando usuario: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verificar si email ya existe
     */
    private function existeEmail($email, $excluirId = null) {
        $sql = "SELECT COUNT(*) as total FROM usuarios WHERE email = :email";
        $parametros = [':email' => $email];
        
        if ($excluirId) {
            $sql .= " AND id != :excluir_id";
            $parametros[':excluir_id'] = $excluirId;
        }
        
        $resultado = $this->conexion->ejecutarConParametros($sql, $parametros);
        $count = $resultado->fetch(PDO::FETCH_ASSOC);
        
        return $count['total'] > 0;
    }
    
    /**
     * Verificar si username ya existe
     */
    private function existeUsername($username, $excluirId = null) {
        $sql = "SELECT COUNT(*) as total FROM usuarios WHERE username = :username";
        $parametros = [':username' => $username];
        
        if ($excluirId) {
            $sql .= " AND id != :excluir_id";
            $parametros[':excluir_id'] = $excluirId;
        }
        
        $resultado = $this->conexion->ejecutarConParametros($sql, $parametros);
        $count = $resultado->fetch(PDO::FETCH_ASSOC);
        
        return $count['total'] > 0;
    }
    
    /**
     * Cambiar contraseña
     */
    public function cambiarPassword($id, $nuevaPassword) {
        try {
            $sql = "UPDATE usuarios SET password_hash = :password_hash WHERE id = :id";
            $parametros = [
                ':password_hash' => password_hash($nuevaPassword, PASSWORD_DEFAULT),
                ':id' => $id
            ];
            
            $resultado = $this->conexion->ejecutarConParametros($sql, $parametros);
            return $resultado->rowCount() > 0;
            
        } catch (Exception $e) {
            error_log("Error cambiando password: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener estadísticas de usuarios
     */
    public function obtenerEstadisticas() {
        try {
            $sql = "SELECT 
                    COUNT(*) as total_usuarios,
                    SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) as usuarios_activos,
                    SUM(CASE WHEN activo = 0 THEN 1 ELSE 0 END) as usuarios_inactivos,
                    COUNT(DISTINCT rol) as roles_diferentes
                    FROM usuarios";
            
            $resultado = $this->conexion->ejecutar($sql);
            return $resultado->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error obteniendo estadísticas: " . $e->getMessage());
            return false;
        }
    }
}
?>