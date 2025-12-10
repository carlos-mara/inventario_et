<?php
session_start();           // Iniciar la sesión existente

session_unset();           // Elimina todas las variables de sesión
session_destroy();         // Destruye la sesión

// Opcional: eliminar la cookie de sesión en el navegador
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

header("Location: login.php");  // Redirigir al login o donde quieras
exit;