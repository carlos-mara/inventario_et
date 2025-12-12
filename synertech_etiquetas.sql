-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 10-12-2025 a las 21:14:59
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `synertech_etiquetas`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `activa` int(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`, `descripcion`, `activa`, `fecha_creacion`) VALUES
(1, 'categoria 1', 'descripcion owudfghoasi', 1, '2025-11-18 21:55:27'),
(2, 'categoria 2', 'descripcion owudfghoasi', 1, '2025-11-18 21:58:33'),
(3, 'rtdfd', 'cvxvxc', 1, '2025-11-20 13:51:41'),
(4, 'bnfghfg', 'hfghfg', 1, '2025-11-20 13:52:45'),
(5, 'HIDRAULICA', 'VINILO DE CORTE NEGRO', 1, '2025-11-25 15:42:15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estados`
--

CREATE TABLE `estados` (
  `id` int(11) NOT NULL,
  `nombre` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `estados`
--

INSERT INTO `estados` (`id`, `nombre`) VALUES
(1, 'ACTIVO'),
(2, 'INACTIVO / ELIMINADO'),
(3, 'ENTREGADO');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `etiquetas`
--

CREATE TABLE `etiquetas` (
  `id` int(11) NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `foto_url` varchar(500) NOT NULL,
  `stock_actual` int(11) DEFAULT 0,
  `stock_minimo` int(11) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `activa` int(1) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `usuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `etiquetas`
--

INSERT INTO `etiquetas` (`id`, `nombre`, `descripcion`, `foto_url`, `stock_actual`, `stock_minimo`, `categoria_id`, `activa`, `fecha_creacion`, `fecha_actualizacion`, `usuario`) VALUES
(1, 'nombre', 'dsfsdf', '1763655055_portada-panama.jpg', 3, 5, 1, 2, '2025-11-20 16:10:55', '2025-11-26 15:48:54', 1),
(2, 'etiqueta 2', 'descr', '1763741159_Imagen22.png', 4, 1, 2, 1, '2025-11-20 19:58:55', '2025-11-25 15:16:21', 1),
(8, 'PURGA DE LODOS', '', '1764087333_Captura de pantalla 2025-11-25 104406.png', 40, 30, 5, 1, '2025-11-25 16:15:33', '2025-12-02 15:33:16', 1),
(20, 'sdfs', 'fsfs df s', '1764344153_azud-wertech-ww-1.png', 5, 21, 5, 1, '2025-11-27 20:50:42', '2025-11-28 16:22:40', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `etiqueta_categorias`
--

CREATE TABLE `etiqueta_categorias` (
  `etiqueta_id` int(11) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `fecha_asignacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `etiqueta_tamanos`
--

CREATE TABLE `etiqueta_tamanos` (
  `id` int(11) NOT NULL,
  `etiqueta_id` int(11) NOT NULL,
  `alto` decimal(11,2) NOT NULL,
  `ancho` decimal(11,2) NOT NULL,
  `stock_actual` int(11) NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `estado` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `etiqueta_tamanos`
--

INSERT INTO `etiqueta_tamanos` (`id`, `etiqueta_id`, `alto`, `ancho`, `stock_actual`, `fecha_creacion`, `estado`) VALUES
(18, 20, 23.00, 23.00, 0, '2025-11-28 10:35:53', 1),
(20, 8, 5.00, 10.00, 32, '2025-12-02 10:33:16', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimientos_inventario`
--

CREATE TABLE `movimientos_inventario` (
  `id` int(11) NOT NULL,
  `etiqueta_id` int(11) NOT NULL,
  `tipo` enum('entrada','salida') NOT NULL,
  `cantidad` int(11) NOT NULL,
  `alto` decimal(10,2) NOT NULL,
  `ancho` decimal(10,2) NOT NULL,
  `precio` decimal(10,2) DEFAULT NULL,
  `motivo` varchar(100) NOT NULL,
  `referencia` varchar(100) DEFAULT NULL,
  `observaciones` varchar(500) DEFAULT NULL,
  `cantidad_anterior` int(11) NOT NULL,
  `cantidad_nueva` int(11) NOT NULL,
  `cod_proyecto` varchar(200) DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha_movimiento` timestamp NOT NULL DEFAULT current_timestamp(),
  `foto_evidencia` varchar(255) DEFAULT NULL,
  `activo` int(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `movimientos_inventario`
--

INSERT INTO `movimientos_inventario` (`id`, `etiqueta_id`, `tipo`, `cantidad`, `alto`, `ancho`, `precio`, `motivo`, `referencia`, `observaciones`, `cantidad_anterior`, `cantidad_nueva`, `cod_proyecto`, `usuario_id`, `fecha_movimiento`, `foto_evidencia`, `activo`) VALUES
(15, 8, 'entrada', 40, 5.00, 10.00, 1000.00, 'compra', '987654', 'COMPRA ', 0, 40, '', 1, '2025-12-02 15:35:03', NULL, 1),
(16, 8, 'salida', 5, 5.00, 10.00, 0.00, 'consumo_interno', '', '', 40, 35, '7', 1, '2025-12-03 14:30:57', NULL, 1),
(17, 8, 'salida', 2, 5.00, 10.00, 0.00, 'consumo_interno', '', '', 35, 33, '7', 1, '2025-12-03 14:36:13', 'evidencias/evidencia_salida_8_1764772573.jpg', 1),
(18, 8, 'salida', 1, 5.00, 10.00, 0.00, 'consumo_interno', '', '', 33, 32, '7', 1, '2025-12-03 14:37:36', 'evidencias/evidencia_salida_8_1764772656.jpg', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proyectos`
--

CREATE TABLE `proyectos` (
  `id` int(11) NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(300) DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_create` datetime NOT NULL,
  `fecha_update` datetime DEFAULT NULL,
  `estado` int(11) NOT NULL,
  `usuario_create` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `proyectos`
--

INSERT INTO `proyectos` (`id`, `codigo`, `nombre`, `descripcion`, `fecha_inicio`, `fecha_create`, `fecha_update`, `estado`, `usuario_create`) VALUES
(7, '123456789', 'PROYECTO 1', 'DESC. PRO', '2025-12-02', '2025-12-02 10:34:07', '2025-12-04 10:23:35', 3, 1),
(8, '7894', 'proyecto n', 'desc', '2025-12-10', '2025-12-10 10:00:15', NULL, 1, 3),
(9, '123', 'prof', 'sd', '2025-12-10', '2025-12-10 10:01:21', NULL, 1, 3),
(10, '1235', 'prof', 'sd', '2025-12-10', '2025-12-10 10:01:52', NULL, 1, 3),
(11, '1235984', 'prof', 'sd', '2025-12-10', '2025-12-10 10:02:42', NULL, 1, 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proyecto_etiquetas`
--

CREATE TABLE `proyecto_etiquetas` (
  `id` int(11) NOT NULL,
  `id_proyecto` int(11) NOT NULL,
  `id_etiqueta` int(11) NOT NULL,
  `id_tamano` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `cantidad_entregada` int(11) NOT NULL DEFAULT 0,
  `alto` decimal(10,2) NOT NULL,
  `ancho` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `proyecto_etiquetas`
--

INSERT INTO `proyecto_etiquetas` (`id`, `id_proyecto`, `id_etiqueta`, `id_tamano`, `cantidad`, `cantidad_entregada`, `alto`, `ancho`) VALUES
(12, 7, 8, 20, 10, 8, 5.00, 10.00),
(13, 8, 8, 20, 3, 0, 5.00, 10.00),
(14, 9, 8, 20, 1, 0, 5.00, 10.00),
(15, 10, 8, 20, 1, 0, 5.00, 10.00),
(16, 11, 8, 20, 1, 0, 5.00, 10.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proyecto_firmas`
--

CREATE TABLE `proyecto_firmas` (
  `id` int(11) NOT NULL,
  `id_proyecto` int(11) NOT NULL,
  `firma` varchar(250) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `comentarios` varchar(200) DEFAULT NULL,
  `fecha` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `proyecto_firmas`
--

INSERT INTO `proyecto_firmas` (`id`, `id_proyecto`, `firma`, `nombre`, `comentarios`, `fecha`) VALUES
(2, 7, 'firmas/firma_7_1764861815.png', 'car', 'ghfgh', '2025-12-04 10:23:35'),
(3, 7, 'firmas/firma_7_1764861815.png', 'carsdffs', 'ghfgh', '2025-12-04 10:23:35');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `nombre_completo` varchar(100) NOT NULL,
  `rol` enum('admin','gestor','consulta','proyectos') DEFAULT 'gestor',
  `activo` int(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `username`, `email`, `password_hash`, `nombre_completo`, `rol`, `activo`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 'admin', 'sistemas@synertech.com.co', '$2y$10$VyymhDMDy7..OIIFvRorPuDSSohOdrWBWZBYciA4cKDoJB3dBYELy', 'Administrador Principal', 'admin', 1, '2025-11-06 21:04:20', '2025-11-06 21:33:40'),
(3, 'Proyectos', 'ingenieriadeproyectos@synertech.com.co', '$2y$10$VyymhDMDy7..OIIFvRorPuDSSohOdrWBWZBYciA4cKDoJB3dBYELy', 'Administrador Proyectos', 'proyectos', 1, '2025-11-09 21:04:20', '2025-12-09 15:17:18');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `activa` (`activa`);

--
-- Indices de la tabla `estados`
--
ALTER TABLE `estados`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `etiquetas`
--
ALTER TABLE `etiquetas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `categoria_id` (`categoria_id`),
  ADD KEY `usuario` (`usuario`),
  ADD KEY `activa` (`activa`);

--
-- Indices de la tabla `etiqueta_categorias`
--
ALTER TABLE `etiqueta_categorias`
  ADD PRIMARY KEY (`etiqueta_id`,`categoria_id`),
  ADD KEY `categoria_id` (`categoria_id`);

--
-- Indices de la tabla `etiqueta_tamanos`
--
ALTER TABLE `etiqueta_tamanos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `estado` (`estado`,`etiqueta_id`),
  ADD KEY `etiqueta_id` (`etiqueta_id`);

--
-- Indices de la tabla `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  ADD PRIMARY KEY (`id`),
  ADD KEY `etiqueta_id` (`etiqueta_id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `activo` (`activo`);

--
-- Indices de la tabla `proyectos`
--
ALTER TABLE `proyectos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `estado` (`estado`),
  ADD KEY `usuario_create` (`usuario_create`);

--
-- Indices de la tabla `proyecto_etiquetas`
--
ALTER TABLE `proyecto_etiquetas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_etiqueta` (`id_etiqueta`),
  ADD KEY `id_proyecto` (`id_proyecto`),
  ADD KEY `id_tamano` (`id_tamano`);

--
-- Indices de la tabla `proyecto_firmas`
--
ALTER TABLE `proyecto_firmas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_proyecto` (`id_proyecto`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `activo` (`activo`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `estados`
--
ALTER TABLE `estados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `etiquetas`
--
ALTER TABLE `etiquetas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `etiqueta_tamanos`
--
ALTER TABLE `etiqueta_tamanos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `proyectos`
--
ALTER TABLE `proyectos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `proyecto_etiquetas`
--
ALTER TABLE `proyecto_etiquetas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `proyecto_firmas`
--
ALTER TABLE `proyecto_firmas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD CONSTRAINT `categorias_ibfk_1` FOREIGN KEY (`activa`) REFERENCES `estados` (`id`);

--
-- Filtros para la tabla `etiquetas`
--
ALTER TABLE `etiquetas`
  ADD CONSTRAINT `etiquetas_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`),
  ADD CONSTRAINT `etiquetas_ibfk_2` FOREIGN KEY (`usuario`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `etiqueta_categorias`
--
ALTER TABLE `etiqueta_categorias`
  ADD CONSTRAINT `etiqueta_categorias_ibfk_1` FOREIGN KEY (`etiqueta_id`) REFERENCES `etiquetas` (`id`),
  ADD CONSTRAINT `etiqueta_categorias_ibfk_2` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`);

--
-- Filtros para la tabla `etiqueta_tamanos`
--
ALTER TABLE `etiqueta_tamanos`
  ADD CONSTRAINT `etiqueta_tamanos_ibfk_1` FOREIGN KEY (`etiqueta_id`) REFERENCES `etiquetas` (`id`),
  ADD CONSTRAINT `etiqueta_tamanos_ibfk_2` FOREIGN KEY (`estado`) REFERENCES `estados` (`id`);

--
-- Filtros para la tabla `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  ADD CONSTRAINT `movimientos_inventario_ibfk_1` FOREIGN KEY (`etiqueta_id`) REFERENCES `etiquetas` (`id`),
  ADD CONSTRAINT `movimientos_inventario_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `movimientos_inventario_ibfk_3` FOREIGN KEY (`activo`) REFERENCES `estados` (`id`);

--
-- Filtros para la tabla `proyectos`
--
ALTER TABLE `proyectos`
  ADD CONSTRAINT `proyectos_ibfk_1` FOREIGN KEY (`estado`) REFERENCES `estados` (`id`),
  ADD CONSTRAINT `proyectos_ibfk_2` FOREIGN KEY (`usuario_create`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `proyecto_etiquetas`
--
ALTER TABLE `proyecto_etiquetas`
  ADD CONSTRAINT `proyecto_etiquetas_ibfk_1` FOREIGN KEY (`id_etiqueta`) REFERENCES `etiquetas` (`id`),
  ADD CONSTRAINT `proyecto_etiquetas_ibfk_2` FOREIGN KEY (`id_proyecto`) REFERENCES `proyectos` (`id`),
  ADD CONSTRAINT `proyecto_etiquetas_ibfk_3` FOREIGN KEY (`id_tamano`) REFERENCES `etiqueta_tamanos` (`id`);

--
-- Filtros para la tabla `proyecto_firmas`
--
ALTER TABLE `proyecto_firmas`
  ADD CONSTRAINT `proyecto_firmas_ibfk_1` FOREIGN KEY (`id_proyecto`) REFERENCES `proyectos` (`id`);

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`activo`) REFERENCES `estados` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
