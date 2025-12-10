<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include_once '../conf/conexion.php';
    session_start();

    $correo = $_POST['correo'];
    $password = $_POST['password'];

    $conexion = new Conexion();
    $conexion->Conectar();

    // Consulta para obtener el hash de la contraseña asociado al correo proporcionado
    $sql = "SELECT correo, password, nombre, rol FROM usuarios WHERE correo = :correo AND estado = 1";
    $parametros = array(':correo' => $correo);
    $resultado = $conexion->ejecutarConParametros($sql, $parametros);

    $usuario = $resultado->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($password, $usuario['password'])) {
        // Contraseña correcta
        $_SESSION['correo'] = $usuario['correo'];
        $_SESSION['nombre'] = $usuario['nombre'];
        $_SESSION['rol'] = $usuario['rol'];
        $sql = "SELECT permiso FROM roles_permisos WHERE idRol = :idRol";
        $consulta = $conexion->ejecutarConParametros($sql,[":idRol"=>$usuario['rol']]);
        $permisos = $consulta->fetchAll(PDO::FETCH_COLUMN);

        $_SESSION['permisos'] = $permisos;


        // Redirigir a la página de inicio o a donde desees
        $respuesta = [
            "exito"=>true,
            "ir" => "index.php"
        ];
        echo json_encode($respuesta);
        exit();
    } else {
        // Contraseña incorrecta o usuario no encontrado
        $respuesta = [
            "exito"=>false
        ];
        echo json_encode($respuesta);
    }
}else {
    header("location: ../../admin/page-not-found.html");
}
