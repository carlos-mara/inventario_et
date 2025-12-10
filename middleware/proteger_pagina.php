<?php
// proteger_pagina.php
require_once 'AuthMiddleware.php';

class Rol extends AuthMiddleware {
    private $auth;

    function protegerPagina($token) {
        
        $this->auth = new AuthMiddleware();

        $datos = $this->auth->verificarToken($token);

        return $datos;
    }
}

?>