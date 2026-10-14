-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 14-10-2026 a las 19:07:51
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
-- Base de datos: `biblioteca`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `administradores`
--

CREATE TABLE `administradores` (
  `cedula` varchar(20) NOT NULL,
  `clave` varchar(255) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellidos` varchar(100) DEFAULT NULL,
  `cargo` varchar(100) DEFAULT NULL,
  `pregunta_secreta` varchar(255) NOT NULL DEFAULT '',
  `respuesta_secreta` varchar(255) NOT NULL DEFAULT '',
  `foto_perfil` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

--
-- Volcado de datos para la tabla `administradores`
--

INSERT INTO `administradores` (`cedula`, `clave`, `nombre`, `apellidos`, `cargo`, `pregunta_secreta`, `respuesta_secreta`, `foto_perfil`) VALUES
('0090', 'gay', 'jun', 'as', 'yo', 'yo que?', 'vos', NULL),
('1015188839', '2815', 'Lorena ', 'Jiménez', 'Administrador', 'Quien es mi bebe ', 'Kitty', NULL),
('1025894819', '12345', 'salome\r\n', 'hernandez', '11°1', '¿Como se llama mi papa?', 'carlos', NULL),
('1032015302', '$2y$10$88E923Lm6gDHh7yfWypkgOvX4bGL5clWIrW9hSwq59gy/m2/peQrW', 'Heliocorr', '', 'Bibliotecario', '', '', 'perfil_admin_1032015302_1790710895.jpg');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `carrusel`
--

CREATE TABLE `carrusel` (
  `id` int(11) NOT NULL,
  `imagen` varchar(255) NOT NULL,
  `frase` text NOT NULL,
  `orden` int(11) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

--
-- Volcado de datos para la tabla `carrusel`
--

INSERT INTO `carrusel` (`id`, `imagen`, `frase`, `orden`, `activo`, `fecha_creacion`) VALUES
(1, 'slide_1791491219_648.jpg', 'Una biblioteca no es un conjunto de libros leídos, sino una compañía, un refugio y un proyecto de vida', 1, 1, '2026-10-02 10:20:35'),
(2, 'slide_1791491283_631.jpg', 'Los libros son espejos: solo te ves en ellos si ya estás dentro.', 2, 1, '2026-10-02 10:20:35'),
(3, 'slide_1791491309_596.jpg', 'Leer es vivir mil vidas antes de acabar la tuya.', 3, 1, '2026-10-02 10:20:35'),
(7, 'slide_1791490879_922.jpg', 'Cuanto más lees, más cosas sabrás. Cuantas más cosas aprendas, a más lugares viajarás.', 4, 1, '2026-10-08 15:21:19'),
(8, 'slide_1791490924_535.jpg', 'Un lector vive mil vidas antes de morir. Aquel que no lee vive solo una.', 5, 1, '2026-10-08 15:22:04'),
(9, 'slide_1791491107_703.jpg', 'Aprender a leer es encender un fuego; cada sílaba que se deletrea es una chispa.', 6, 1, '2026-10-08 15:25:07');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `computadores`
--

CREATE TABLE `computadores` (
  `id` int(11) NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `ubicacion` varchar(100) DEFAULT NULL,
  `estado` enum('operativo','mantenimiento') NOT NULL DEFAULT 'operativo',
  `disponible` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

--
-- Volcado de datos para la tabla `computadores`
--

INSERT INTO `computadores` (`id`, `codigo`, `ubicacion`, `estado`, `disponible`) VALUES
(1, 'pc-03', 'biblioteca', 'operativo', 1),
(2, 'pc-01', 'biblioteca', 'operativo', 1),
(3, 'pc-02', 'biblioteca', 'operativo', 1),
(4, 'pc-04', 'sala de sistemas', 'operativo', 1),
(5, 'pc-05', 'sala de sistemas', 'operativo', 1),
(6, 'pc-06', 'sala de sistemas', 'operativo', 1),
(7, 'pc-07', 'sala de sistemas', 'operativo', 1),
(8, 'pc-08', 'aula de tecnología', 'operativo', 1),
(9, 'pc-09', 'aula de tecnología', 'mantenimiento', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `docentes`
--

CREATE TABLE `docentes` (
  `cedula` varchar(20) NOT NULL,
  `clave` varchar(255) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellidos` varchar(100) DEFAULT NULL,
  `director_grupo` tinyint(1) NOT NULL DEFAULT 0,
  `pregunta_secreta` varchar(255) NOT NULL DEFAULT '',
  `respuesta_secreta` varchar(255) NOT NULL DEFAULT '',
  `foto_perfil` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

--
-- Volcado de datos para la tabla `docentes`
--

INSERT INTO `docentes` (`cedula`, `clave`, `nombre`, `apellidos`, `director_grupo`, `pregunta_secreta`, `respuesta_secreta`, `foto_perfil`) VALUES
('040625', '1234', 'Elmo', 'cho', 0, 'Primera mascota', 'ready for the night', 'perfil_docente_040625_1789519872.png'),
('1032758140', '12345', 'EL PIRO', 'Mano', 1, '', '', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estudiantes`
--

CREATE TABLE `estudiantes` (
  `numero_matricula` varchar(20) NOT NULL,
  `clave` varchar(255) NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) DEFAULT NULL,
  `grado` varchar(50) DEFAULT NULL,
  `pregunta_secreta` varchar(255) NOT NULL DEFAULT '',
  `respuesta_secreta` varchar(255) NOT NULL DEFAULT '',
  `foto_perfil` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

--
-- Volcado de datos para la tabla `estudiantes`
--

INSERT INTO `estudiantes` (`numero_matricula`, `clave`, `nombres`, `apellidos`, `grado`, `pregunta_secreta`, `respuesta_secreta`, `foto_perfil`) VALUES
('101111111', '12345', 'Lorena', 'Lopez ', '11', '', '', NULL),
('1015188839', 'lorena123', 'Lorena', 'Jimeness', '11\'1', '¿quien es mi mejor amiga?', 'salome', NULL),
('1020303313', '123456', 'Fedelobo', 'Henao', '11°1', '¿Como se llama mi papa?', 'ricardo', 'perfil_estudiante_1020303313_1790631011.jpg'),
('1022', '1234', 'pedro', 'pe', '11', '¿Como se llama mi papa?', 'edgar', 'perfil_estudiante_1022_1788969079.png'),
('1022005281', '1022005281', 'Lesdy', 'Buitrago', '11', 'Primera mascota', 'lesdy', NULL),
('1025894298', '1025894298', 'Valery', 'Zapata Amariles', '11', '', '', NULL),
('1025894632', 'ameri111', 'Sammantha', 'Ocampo Montoya', '11', 'como se llamaba el pajarito?', 'naranjito', 'perfil_estudiante_1025894632_1788990681.png'),
('1025894814', '12345', 'salome', 'rincon', '11°1', '¿quien es mi mejor amigo?', 'rafa', NULL),
('1025895535', 'sol110610*', 'sofia ', 'ocampo loaiza ', '11°1', '¿Como se llama mi papa?', 'josefina', 'perfil_estudiante_1025895535_1788977739.webp'),
('1035865524', 'enriquecomeenanos', 'Isabela ', 'Ladera', '11', '¿Como se llama mi papa?', 'josefina', 'perfil_estudiante_1035865524_1790015129.jpg'),
('12333', '122', 'camilo', 'uribe', 'Media tecnica', 'Primera mascota', 'toni', NULL),
('123456', '123456', 'rafa', 'sierra', '11°1', '¿Como se llama mi papa?', 'ruperto', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `juegos`
--

CREATE TABLE `juegos` (
  `codigo` varchar(50) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `tipo` varchar(100) DEFAULT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `disponibilidad` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

--
-- Volcado de datos para la tabla `juegos`
--

INSERT INTO `juegos` (`codigo`, `nombre`, `tipo`, `cantidad`, `disponibilidad`) VALUES
('jg-01', 'Parqués', 'Mesa', 4, 1),
('jg-02', 'bingo', 'Mesa', 1, 1),
('jg-03', 'Dominó', 'Mesa', 5, 1),
('jg-04', 'Ajedrez', 'Estrategia', 6, 1),
('jg-05', 'Damas chinas', 'Mesa', 3, 1),
('jg-06', 'Jenga', 'Habilidad', 2, 1),
('jg-07', 'UNO', 'Cartas', 4, 1),
('jg-08', 'Baraja francesa', 'Cartas', 5, 1),
('jg-09', 'Scrabble en español', 'Palabras', 2, 1),
('jg-10', 'Rummikub', 'Estrategia', 2, 1),
('jg-11', 'Tangram', 'Ingenio', 3, 1),
('jg-12', 'Cubo de Rubik 3x3', 'Habilidad', 5, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `libros`
--

CREATE TABLE `libros` (
  `isbn` varchar(30) NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `autor` varchar(150) DEFAULT NULL,
  `editorial` varchar(150) DEFAULT NULL,
  `cantidad_paginas` int(11) DEFAULT NULL,
  `edicion_especial_mined` tinyint(1) NOT NULL DEFAULT 0,
  `tipo` varchar(100) DEFAULT NULL,
  `anio_publicacion` int(11) DEFAULT NULL,
  `ejemplar` varchar(50) DEFAULT NULL,
  `institucion` varchar(150) DEFAULT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `disponibilidad` tinyint(1) NOT NULL DEFAULT 1,
  `serie` varchar(100) DEFAULT NULL,
  `nota_general` text DEFAULT NULL,
  `materia` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

--
-- Volcado de datos para la tabla `libros`
--

INSERT INTO `libros` (`isbn`, `nombre`, `autor`, `editorial`, `cantidad_paginas`, `edicion_especial_mined`, `tipo`, `anio_publicacion`, `ejemplar`, `institucion`, `cantidad`, `disponibilidad`, `serie`, `nota_general`, `materia`) VALUES
('1039758140', 'Fruko y sus presos', 'Rivera', 'Desparche productions', 100, 0, 'Suspenso', 2026, '2', 'Jorge robledo', 5, 1, '111', '', 'Español'),
('1144', 'Harry Potter y la piedra filosofal', 'J.K. Rowling ', 'penguin', 298, 0, 'Fantasia', 1999, '2', 'Jorge robledo', 5, 1, '111', '', 'Español'),
('11445', 'rodrigo de no futuro', 'J.K. Rowling', 'penguin', 11111111, 1, 'Fantasia', 1400, '2', 'Jorge robledo', 7, 1, '123', 'bueno', 'fisica'),
('444', 'Cien Años De Seriedad ', 'Valery Zapata ', 'ChimboTieso ', 28, 0, 'Serio ', 1945, '2', 'Jorge robledo', 0, 0, '444', '', 'Español'),
('978-0001', 'Cien Años de Soledad', 'Gabriel García Márquez', 'Sudamericana', 471, 1, 'Novela', 1967, '1ra edición', 'Instituto Nacional', 5, 1, 'Clásico literatura latinoamericana', 'Literatura', NULL),
('978-0002', 'Don Quijote de la Mancha', 'Miguel de Cervantes', 'Espasa', 863, 1, 'Novela', 1605, '4ta edición', 'Instituto Nacional', 3, 1, 'Obra cumbre española', 'Literatura', NULL),
('978-0003', 'Física para Bachillerato', 'Raymond Serway', 'Cengage', 620, 1, 'Texto', 2018, '2da edición', 'Colegio San José', 8, 1, 'Física', '\r\r\0\0\00\0�978-0004\0\0\0\0\0\0�\0\0\0\0\0\0Química GeneralRaymond ChangMcGraw Hill�\0�Texto�\0�11va ed', NULL),
('978-0004', 'Química General', 'Raymond Chang', 'McGraw Hill', 745, 1, 'Texto', 2020, '11va edición', 'Colegio San José', 6, 1, 'Química', '\0\0\08\0�978-0005\0\0\0\0\0\0�\0\0\0\0\0\0Matemáticas 9no GradoMinisterio de EducaciónMINED�\0\0ҁText', NULL),
('978-0005', 'Matemáticas 9no Grado', 'Ministerio de Educación', 'MINED', 210, 1, 'Texto', 2022, '1ra edición', 'Instituto Nacional', 15, 1, 'Distribución gratuita', 'Matemáticas', NULL),
('978-0006', 'Historia Universal', 'Patricia Buckley', 'Alianza', 512, 0, 'Texto', 2015, '3ra edición', 'Colegio Bautista', 4, 1, 'Historia', '	\r\0\0H\0�978-0007\0\0\0\0\0\0�\0\0\0\0\0\0Biología CelularBruce AlbertsPanamericana�\0z�Texto�\0�6ta ediciónColegio San', NULL),
('978-0007', 'Biología Celular', 'Bruce Alberts', 'Panamericana', 890, 1, 'Texto', 2019, '6ta edición', 'Colegio San José', 5, 1, 'Biología', '\n\n\r\0\0\0P\0�978-0008\0\0\0\0\0\0�\0\0\0\0\0\0El PrincipitoAntoine de Saint-ExupérySalamandra�\0\0`�Novela ', NULL),
('978-0008', 'El Principito', 'Antoine de Saint-Exupéry', 'Salamandra', 96, 0, 'Novela corta', 1943, '1ra edición', 'Instituto Nacional', 10, 1, 'Lectura obligatoria', 'Literatura', NULL),
('978-0009', 'Inglés Básico', 'Raymond Murphy', 'Cambridge', 380, 1, 'Texto', 2021, '4ta edición', 'Instituto Nacional', 7, 1, 'Inglés', '\n\0\0\0`\0�978-0010\0\0\0\0\0\0�\0\0\0\0\0\0Geografía de CentroaméricaMINEDMINED�\0\0��Texto�\0�2da edic', NULL),
('978-0010', 'Geografía de Centroamérica', 'MINED', 'MINED', 180, 1, 'Texto', 2020, '2da edición', 'Colegio Bautista', 12, 1, 'Distribución gratuita', 'Geografía', NULL),
('978-0011', '1984', 'George Orwell', 'Debolsillo', 328, 0, 'Novela', 1949, '2da edición', 'Colegio San José', 4, 1, 'Literatura', '\0\0\0p\0�978-0012\0\0\0\0\0\0�\0\0\0\0\0\0Álgebra IntermediaAllen AngelPearson�\0��Texto�\0�7ma edici�', ''),
('978-0012', 'Álgebra Intermedia', 'Allen Angel', 'Pearson', 704, 1, 'Texto', 2017, '7ma edición', 'Instituto Nacional', 5, 1, 'Matemáticas', '\0\0\0x\0�978-0013\0\0\0\0\0\0�\0\0\0\0\0\0Ciencias Naturales 7moMINEDMINED�\0\0܁Texto�\0�1ra ediciónCo', NULL),
('978-0013', 'Ciencias Naturales 7mo', 'MINED', 'MINED', 220, 1, 'Texto', 2022, '1ra edición', 'Colegio Bautista', 14, 1, 'Distribución gratuita', 'Ciencias Naturales', NULL),
('978-0014', 'La Odisea', 'Homero', 'Gredos', 480, 0, 'Poema épico', -800, '5ta edición', 'Instituto Nacional', 3, 1, 'Literatura', '\0\0�\0�978-0015\0\0\0\0\0\0�\0\0\0\0\0\0Educación CívicaMINEDMINED�\0\0��Texto�\0�1ra ediciónColegio San José�\0\0\n�Di', NULL),
('978-0015', 'Educación Cívica', 'MINED', 'MINED', 150, 1, 'Texto', 2021, '1ra edición', 'Colegio San José', 10, 1, 'Distribución gratuita', 'Cívica', NULL),
('978-0016', 'Cálculo Diferencial', 'James Stewart', 'Cengage', 880, 1, 'Texto', 2018, '8va edición', 'Instituto Nacional', 5, 1, 'Matemáticas', '	\0\0\0�\0�978-0017\0\0\0\0\0\0�\0\0\0\0\0\0Anatomía HumanaFrederic MartiniPanamericana�\0��Texto�\0�9na', NULL),
('978-0017', 'Anatomía Humana', 'Frederic Martini', 'Panamericana', 950, 0, 'Texto', 2016, '9na edición', 'Colegio San José', 3, 1, 'Biología', '\n \0\0\0�\0�978-0018\0\0\0\0\0\0�\0\0\0\0\0\0Crónica de una Muerte AnunciadaGabriel García MárquezSudamericana�\0\0z�Novela', NULL),
('978-0018', 'Crónica de una Muerte Anunciada', 'Gabriel García Márquez', 'Sudamericana', 122, 0, 'Novela corta', 1981, '3ra edición', 'Instituto Nacional', 6, 1, 'Literatura', '\r\0\0�\0�978-0019\0\0\0\0\0\0�\0\0\0\0\0\0Física Cuántica BásicaKenneth KraneLimusa�\0��Texto�\0�3ra', NULL),
('978-0019', 'Física Cuántica Básica', 'Kenneth Krane', 'Limusa', 680, 0, 'Texto', 2014, '3ra edición', 'Colegio Bautista', 2, 1, 'Física', '\0\0\0�\0�978-0020\0\0\0\0\0\0�\0\0\0\0\0\0Historia de NicaraguaMINEDMINED�\0\0ȁTexto�\0�1ra ediciónIns', NULL),
('978-0021', 'Cuentos de la Selva', 'Horacio Quiroga', 'Losada', 140, 0, 'Cuentos', 1918, '2da edición', 'Colegio Bautista', 5, 1, 'Literatura', '\r\0\0\0�\0�978-0022\0\0\0\0\0\0�\0\0\0\0\0\0Estadística AplicadaMario TriolaPearson�\0�Texto�\0�12va ed', NULL),
('978-0022', 'Estadística Aplicada', 'Mario Triola', 'Pearson', 780, 1, 'Texto', 2019, '12va edición', 'Instituto Nacional', 4, 1, 'Matemáticas', '	\0\0�\0�978-0023\0\0\0\0\0\0�\0\0\0\0\0\0Ecología GeneralEugene OdumInteramericana�\0l�Texto�\0�5ta e', NULL),
('978-0023', 'Ecología General', 'Eugene Odum', 'Interamericana', 620, 0, 'Texto', 2013, '5ta edición', 'Colegio San José', 3, 1, 'Biología', '\n\0\0\0�\0�978-0024\0\0\0\0\0\0�\0\0\0\0\0\0La Casa de los EspíritusIsabel AllendePlaza & Janés�\0��Novela�\0�2da edició', NULL),
('978-0024', 'La Casa de los Espíritus', 'Isabel Allende', 'Plaza & Janés', 448, 0, 'Novela', 1982, '2da edición', 'Instituto Nacional', 4, 1, 'Literatura', '\0\0\0�\0�978-0025\0\0\0\0\0\0�\0\0\0\0\0\0TrigonometríaCharles McKeagueCengage�\00�Texto�\0�7ma edici�', NULL),
('978-0025', 'Trigonometría', 'Charles McKeague', 'Cengage', 560, 1, 'Texto', 2017, '7ma edición', 'Colegio Bautista', 6, 1, 'Matemáticas', '\0\0\0�\0�978-0026\0\0\0\0\0\0�\0\0\0\0\0\0Química OrgánicaJohn McMurryCengage�\0��Texto�\0�9na edici�', NULL),
('978-0026', 'Química Orgánica', 'John McMurry', 'Cengage', 1200, 0, 'Texto', 2016, '9na edición', 'Colegio San José', 3, 1, 'Química', '\n\n\0\0\0�\0�978-0027\0\0\0\0\0\0�\0\0\0\0\0\0Fahrenheit 451Ray BradburyDebolsillo�\0\0�Novela�\0�3ra ediciónInstituto Nacion', NULL),
('978-0027', 'Fahrenheit 451', 'Ray Bradbury', 'Debolsillo', 256, 0, 'Novela', 1953, '3ra edición', 'Instituto Nacional', 5, 1, 'Literatura', '\Z\0\0\0�\0�978-0028\0\0\0\0\0\0�\0\0\0\0\0\0Educación Física y SaludMINEDMINED�\0\0��Texto�\0�1ra edici�', NULL),
('978-0028', 'Educación Física y Salud', 'MINED', 'MINED', 130, 1, 'Texto', 2021, '1ra edición', 'Colegio Bautista', 15, 1, 'Distribución gratuita', 'Educación Física', NULL),
('978-0029', 'Geometría Analítica', 'Charles Lehmann', 'Limusa', 500, 1, 'Texto', 2015, '4ta edición', 'Instituto Nacional', 5, 1, 'Matemáticas', '\n	\0\0\0�\n978-0030\0\0\0\0\0\0�\0\0\0\0\0\0RayuelaJulio CortázarAlfaguara�\0��Novela�\0�2da ediciónCol', NULL),
('978-0030', 'Rayuela', 'Julio Cortázar', 'Alfaguara', 736, 0, 'Novela', 1963, '2da edición', 'Colegio San José', 2, 1, 'Literatura', '\0\r\r\"\0\0\0\0�1144\0\0\0\0\0\0�\0\0\0\0\0\0Harry Potter y la piedra filosofalJ.K. Rowling penguin�\0*�Fantasia�\0�2Jorge rob', NULL),
('978-1001', 'Cien Años de Soledad', 'Gabriel García Márquez', 'Sudamericana', 471, 1, 'Novela', 1967, '1ra edición', 'Instituto Nacional', 5, 1, NULL, 'Clásico latinoamericano', 'Literatura'),
('978-1002', 'El Amor en los Tiempos del Cólera', 'Gabriel García Márquez', 'Sudamericana', 348, 0, 'Novela', 1985, '2da edición', 'Instituto Nacional', 3, 1, NULL, NULL, 'Literatura'),
('978-1003', 'Harry Potter y la Piedra Filosofal', 'J.K. Rowling', 'Salamandra', 254, 0, 'Novela', 1997, '1ra edición', 'Colegio San José', 6, 1, 'Harry Potter', 'Libro 1 de la saga', 'Literatura'),
('978-1004', 'Harry Potter y la Cámara Secreta', 'J.K. Rowling', 'Salamandra', 288, 0, 'Novela', 1998, '1ra edición', 'Colegio San José', 5, 1, 'Harry Potter', 'Libro 2 de la saga', 'Literatura'),
('978-1005', 'Harry Potter y el Prisionero de Azkaban', 'J.K. Rowling', 'Salamandra', 317, 0, 'Novela', 1999, '1ra edición', 'Colegio San José', 4, 1, 'Harry Potter', 'Libro 3 de la saga', 'Literatura'),
('978-1006', 'El Principito', 'Antoine de Saint-Exupéry', 'Salamandra', 96, 0, 'Novela corta', 1943, '1ra edición', 'Instituto Nacional', 10, 1, NULL, 'Lectura obligatoria', 'Literatura'),
('978-1007', '1984', 'George Orwell', 'Debolsillo', 328, 0, 'Novela', 1949, '2da edición', 'Colegio San José', 3, 1, NULL, NULL, 'Literatura'),
('978-1008', 'Fahrenheit 451', 'Ray Bradbury', 'Debolsillo', 256, 0, 'Novela', 1953, '3ra edición', 'Instituto Nacional', 5, 1, NULL, NULL, 'Literatura'),
('978-1009', 'Percy Jackson y el Ladrón del Rayo', 'Rick Riordan', 'Salamandra', 384, 0, 'Novela', 2005, '1ra edición', 'Colegio Bautista', 5, 1, 'Percy Jackson', 'Libro 1 de la saga', 'Literatura'),
('978-1010', 'Percy Jackson y el Mar de los Monstruos', 'Rick Riordan', 'Salamandra', 279, 0, 'Novela', 2006, '1ra edición', 'Colegio Bautista', 4, 1, 'Percy Jackson', 'Libro 2 de la saga', 'Literatura'),
('978-1011', 'Rayuela', 'Julio Cortázar', 'Alfaguara', 736, 0, 'Novela', 1963, '2da edición', 'Colegio San José', 2, 1, NULL, NULL, 'Literatura'),
('978-1012', 'La Casa de los Espíritus', 'Isabel Allende', 'Plaza & Janés', 448, 0, 'Novela', 1982, '2da edición', 'Instituto Nacional', 4, 1, NULL, NULL, 'Literatura'),
('978-1013', 'Don Quijote de la Mancha', 'Miguel de Cervantes', 'Espasa', 863, 1, 'Novela', 1605, '4ta edición', 'Instituto Nacional', 3, 1, NULL, 'Obra cumbre española', 'Literatura'),
('978-1014', 'Crónica de una Muerte Anunciada', 'Gabriel García Márquez', 'Sudamericana', 122, 0, 'Novela corta', 1981, '3ra edición', 'Instituto Nacional', 6, 1, NULL, NULL, 'Literatura'),
('978-1101', 'Educación Cívica', 'Ministerio de Educación', 'MINED', 150, 1, 'Texto', 2021, '1ra edición', 'Colegio San José', 10, 1, 'Colección MINED', 'Distribución gratuita', 'Cívica'),
('978-1201', 'Educación Física y Salud', 'Ministerio de Educación', 'MINED', 130, 1, 'Texto', 2021, '1ra edición', 'Colegio Bautista', 15, 1, 'Colección MINED', 'Distribución gratuita', 'Educación Física'),
('978-1301', 'Historia del Arte', 'Ernst Gombrich', 'Phaidon', 688, 0, 'Texto', 2012, '16va edición', 'Colegio San José', 3, 1, NULL, NULL, 'Arte'),
('978-9002', 'English Grammar in Use', 'Raymond Murphy', 'Cambridge', 380, 0, 'Texto', 2019, '5ta edición', 'Colegio San José', 6, 1, NULL, NULL, 'Inglés'),
('978-958-04-0001', 'Cien Años de Soledad', 'Gabriel García Márquez', 'Editorial Sudamericana', 471, 1, 'Novela', 1967, '1ra edición', 'IE Jorge Robledo', 6, 1, NULL, 'Lectura obligatoria ICFES', 'Español y Literatura'),
('978-958-04-0002', 'El Amor en los Tiempos del Cólera', 'Gabriel García Márquez', 'Editorial Sudamericana', 348, 0, 'Novela', 1985, '2da edición', 'IE Jorge Robledo', 3, 1, NULL, NULL, 'Español y Literatura'),
('978-958-04-0003', 'María', 'Jorge Isaacs', 'Editorial Bedout', 320, 1, 'Novela', 1867, '1ra edición', 'IE Jorge Robledo', 5, 1, NULL, 'Clásico colombiano del siglo XIX', 'Español y Literatura'),
('978-958-04-0004', 'La Marquesa de Yolombó', 'Tomás Carrasquilla', 'Editorial Universidad de Antioquia', 480, 1, 'Novela', 1928, '2da edición', 'IE Jorge Robledo', 3, 1, NULL, 'Autor antioqueño', 'Español y Literatura'),
('978-958-04-0005', 'El Coronel no Tiene Quien le Escriba', 'Gabriel García Márquez', 'Editorial Sudamericana', 96, 0, 'Novela corta', 1961, '3ra edición', 'IE Jorge Robledo', 6, 1, NULL, NULL, 'Español y Literatura'),
('978-958-04-0006', 'La Vorágine', 'José Eustasio Rivera', 'Editorial Panamericana', 296, 1, 'Novela', 1924, '1ra edición', 'IE Jorge Robledo', 4, 1, NULL, 'Clásico literatura colombiana', 'Español y Literatura'),
('978-958-04-0007', 'Que Viva la Música', 'Andrés Caicedo', 'Editorial Norma', 240, 0, 'Novela', 1977, '1ra edición', 'IE Jorge Robledo', 3, 1, NULL, NULL, 'Español y Literatura'),
('978-958-04-0008', 'El Olvido que Seremos', 'Héctor Abad Faciolo', 'Editorial Planeta', 280, 0, 'Novela', 2006, '2da edición', 'IE Jorge Robledo', 4, 1, NULL, 'Autor antioqueño contemporáneo', 'Español y Literatura'),
('978-958-04-0009', 'Sin Remedio', 'Antonio Caballero', 'Editorial Alfaguara', 464, 0, 'Novela', 1984, '1ra edición', 'IE Jorge Robledo', 2, 1, NULL, NULL, 'Español y Literatura'),
('978-958-04-0010', 'Lengua Castellana 9', 'Editorial Santillana', 'Santillana', 220, 1, 'Texto', 2021, '1ra edición', 'IE Jorge Robledo', 15, 1, 'Colección Santillana', 'Alineado con MEN', 'Español y Literatura'),
('978-958-04-0011', 'Lengua Castellana 10', 'Editorial Santillana', 'Santillana', 230, 1, 'Texto', 2021, '1ra edición', 'IE Jorge Robledo', 14, 1, 'Colección Santillana', 'Alineado con MEN', 'Español y Literatura'),
('978-958-04-0012', 'Lengua Castellana 11 - Preparación ICFES', 'Editorial Santillana', 'Santillana', 240, 1, 'Texto', 2022, '1ra edición', 'IE Jorge Robledo', 14, 1, 'Colección Santillana', 'Enfocado en pruebas Saber 11', 'Español y Literatura'),
('978-958-04-1001', 'Matemáticas 9 - Norma', 'Editorial Norma', 'Norma', 210, 1, 'Texto', 2020, '1ra edición', 'IE Jorge Robledo', 16, 1, 'Colección Norma', NULL, 'Matemáticas'),
('978-958-04-1002', 'Matemáticas 10 - Norma', 'Editorial Norma', 'Norma', 220, 1, 'Texto', 2020, '1ra edición', 'IE Jorge Robledo', 15, 1, 'Colección Norma', NULL, 'Matemáticas'),
('978-958-04-1003', 'Matemáticas 11 - Preparación Saber 11', 'Editorial Norma', 'Norma', 230, 1, 'Texto', 2022, '1ra edición', 'IE Jorge Robledo', 15, 1, 'Colección Norma', 'Enfocado en pruebas Saber 11', 'Matemáticas'),
('978-958-04-1004', 'Álgebra de Baldor', 'Aurelio Baldor', 'Publicaciones Cultural', 800, 0, 'Texto', 1941, '2da reimpresión', 'IE Jorge Robledo', 5, 1, NULL, 'Referencia histórica de álgebra', 'Matemáticas'),
('978-958-04-1005', 'Geometría de Baldor', 'Aurelio Baldor', 'Publicaciones Cultural', 500, 0, 'Texto', 1967, '2da reimpresión', 'IE Jorge Robledo', 4, 1, NULL, NULL, 'Matemáticas'),
('978-958-04-1006', 'Cálculo de Una Variable', 'James Stewart', 'Cengage', 880, 0, 'Texto', 2018, '8va edición', 'IE Jorge Robledo', 3, 1, NULL, NULL, 'Matemáticas'),
('978-958-04-2001', 'Ciencias Naturales 9 - Hipertexto', 'Editorial Santillana', 'Santillana', 240, 1, 'Texto', 2021, '1ra edición', 'IE Jorge Robledo', 14, 1, 'Colección Hipertexto', NULL, 'Ciencias Naturales'),
('978-958-04-2002', 'Física 10 - Hipertexto', 'Editorial Santillana', 'Santillana', 250, 1, 'Texto', 2021, '1ra edición', 'IE Jorge Robledo', 12, 1, 'Colección Hipertexto', NULL, 'Física'),
('978-958-04-2003', 'Física 11 - Hipertexto', 'Editorial Santillana', 'Santillana', 260, 1, 'Texto', 2021, '1ra edición', 'IE Jorge Robledo', 12, 1, 'Colección Hipertexto', NULL, 'Física'),
('978-958-04-2004', 'Química 10 - Hipertexto', 'Editorial Santillana', 'Santillana', 240, 1, 'Texto', 2021, '1ra edición', 'IE Jorge Robledo', 12, 1, 'Colección Hipertexto', NULL, 'Química'),
('978-958-04-2005', 'Química 11 - Hipertexto', 'Editorial Santillana', 'Santillana', 250, 1, 'Texto', 2021, '1ra edición', 'IE Jorge Robledo', 10, 1, 'Colección Hipertexto', NULL, 'Química'),
('978-958-04-2006', 'Biología Celular y Molecular', 'Bruce Alberts', 'Editorial Médica Panamericana', 890, 0, 'Texto', 2019, '6ta edición', 'IE Jorge Robledo', 3, 1, NULL, NULL, 'Biología'),
('978-958-04-3001', 'Ciencias Sociales 9 - Territorios', 'Editorial Norma', 'Norma', 210, 1, 'Texto', 2020, '1ra edición', 'IE Jorge Robledo', 14, 1, 'Colección Territorios', NULL, 'Ciencias Sociales'),
('978-958-04-3002', 'Historia de Colombia', 'David Bushnell', 'Editorial Planeta', 456, 0, 'Texto', 1994, '3ra edición', 'IE Jorge Robledo', 5, 1, NULL, NULL, 'Ciencias Sociales'),
('978-958-04-3003', 'Historia de Antioquia', 'Jorge Orlando Melo', 'Editorial Universidad de Antioquia', 380, 0, 'Texto', 1988, '2da edición', 'IE Jorge Robledo', 4, 1, NULL, 'Historia regional', 'Ciencias Sociales'),
('978-958-04-3004', 'Constitución Política de Colombia', 'Congreso de Colombia', 'Legis', 220, 1, 'Texto legal', 2020, '1ra edición', 'IE Jorge Robledo', 10, 1, NULL, 'Uso en Educación Cívica', 'Ciencias Sociales'),
('978-958-04-4001', 'English Please 9', 'Editorial Santillana', 'Santillana', 180, 1, 'Texto', 2021, '1ra edición', 'IE Jorge Robledo', 14, 1, 'Colección English Please', NULL, 'Inglés'),
('978-958-04-4002', 'English Please 10', 'Editorial Santillana', 'Santillana', 190, 1, 'Texto', 2021, '1ra edición', 'IE Jorge Robledo', 13, 1, 'Colección English Please', NULL, 'Inglés'),
('978-958-04-4003', 'English Please 11', 'Editorial Santillana', 'Santillana', 200, 1, 'Texto', 2021, '1ra edición', 'IE Jorge Robledo', 13, 1, 'Colección English Please', NULL, 'Inglés'),
('978-958-04-4004', 'English Grammar in Use', 'Raymond Murphy', 'Cambridge University Press', 380, 0, 'Texto', 2019, '5ta edición', 'IE Jorge Robledo', 6, 1, NULL, NULL, 'Inglés'),
('978-958-04-5001', 'Ética y Valores 9', 'Editorial Voluntad', 'Voluntad', 120, 1, 'Texto', 2019, '1ra edición', 'IE Jorge Robledo', 12, 1, NULL, NULL, 'Ética y Valores'),
('978-958-04-5002', 'Proyecto de Vida y Convivencia', 'Editorial Voluntad', 'Voluntad', 130, 1, 'Texto', 2020, '1ra edición', 'IE Jorge Robledo', 10, 1, NULL, NULL, 'Ética y Valores'),
('978-958-04-6001', 'Tecnología e Informática 9', 'Editorial Educar', 'Educar Editores', 160, 1, 'Texto', 2020, '1ra edición', 'IE Jorge Robledo', 12, 1, NULL, NULL, 'Tecnología e Informática'),
('978-958-04-6002', 'Introducción a la Programación', 'Luis Joyanes Aguilar', 'McGraw Hill', 620, 0, 'Texto', 2015, '5ta edición', 'IE Jorge Robledo', 4, 1, NULL, NULL, 'Tecnología e Informática'),
('978-958-04-7001', 'Educación Física y Deporte 9', 'Editorial Voluntad', 'Voluntad', 110, 1, 'Texto', 2019, '1ra edición', 'IE Jorge Robledo', 12, 1, NULL, NULL, 'Educación Física'),
('978-958-04-8001', 'Historia del Arte', 'Ernst Gombrich', 'Editorial Phaidon', 688, 0, 'Texto', 2012, '16va edición', 'IE Jorge Robledo', 3, 1, NULL, NULL, 'Artística'),
('978-958-04-8002', 'Fundamentos del Dibujo Artístico', 'Betty Edwards', 'Editorial Urano', 288, 0, 'Manual', 2015, '3ra edición', 'IE Jorge Robledo', 4, 1, NULL, NULL, 'Artística'),
('9780307474728', 'Cien años de soledad', 'Gabriel García Márquez', 'Sudamericana', 496, 0, 'Novela', 1967, '10°-11°', 'IE Jorge Robledo', 6, 1, NULL, 'Lectura obligatoria de grado 10-11', 'Literatura'),
('9788481301194', 'Platero y yo', 'Juan Ramón Jiménez', 'Norma', 160, 0, 'Prosa poética', 1917, '6°-7°', 'IE Jorge Robledo', 6, 1, NULL, NULL, 'Literatura'),
('9789580056330', 'Zonactiva Matemáticas 6', 'Varios autores', 'Norma', 264, 0, 'Texto guía', 2016, '6°', 'IE Jorge Robledo', 12, 1, 'Zonactiva', NULL, 'Matemáticas'),
('9789580056347', 'Zonactiva Matemáticas 7', 'Varios autores', 'Norma', 272, 0, 'Texto guía', 2016, '7°', 'IE Jorge Robledo', 12, 1, 'Zonactiva', NULL, 'Matemáticas'),
('9789580056361', 'Zonactiva Ciencias Naturales 6', 'Varios autores', 'Norma', 256, 0, 'Texto guía', 2016, '6°', 'IE Jorge Robledo', 12, 1, 'Zonactiva', NULL, 'Ciencias Naturales'),
('9789580056378', 'Zonactiva Ciencias Naturales 8', 'Varios autores', 'Norma', 264, 0, 'Texto guía', 2016, '8°', 'IE Jorge Robledo', 10, 1, 'Zonactiva', NULL, 'Ciencias Naturales'),
('9789580056385', 'Zonactiva Sociales 6', 'Varios autores', 'Norma', 240, 0, 'Texto guía', 2016, '6°', 'IE Jorge Robledo', 10, 1, 'Zonactiva', NULL, 'Historia'),
('9789580056392', 'Zonactiva Sociales 9', 'Varios autores', 'Norma', 248, 0, 'Texto guía', 2016, '9°', 'IE Jorge Robledo', 10, 1, 'Zonactiva', NULL, 'Historia'),
('9789580123803', 'Competencias Ciudadanas: de los estándares al aula', 'Ministerio de Educación Nacional', 'MEN', 180, 1, 'Guía docente', 2004, 'Todos', 'Ministerio de Educación Nacional', 6, 1, NULL, 'Documento oficial del MEN', 'Cívica'),
('9789580456123', 'La vorágine', 'José Eustasio Rivera', 'Bedout', 280, 0, 'Novela', 1924, '10°-11°', 'IE Jorge Robledo', 5, 1, NULL, 'Novela colombiana clásica', 'Literatura'),
('9789580458257', 'Rayuela', 'Julio Cortázar', 'Alfaguara', 736, 0, 'Novela', 1963, '11°', 'IE Jorge Robledo', 3, 1, NULL, 'Lectura de profundización', 'Literatura'),
('9789580XXXXX1', 'Way to go! 1 - Student\'s Book', 'MEN / British Council / Richmond', 'Richmond', 132, 1, 'Texto guía', 2016, '6°', 'Ministerio de Educación Nacional', 15, 1, 'Colombia Bilingüe', 'Edición oficial gratuita para colegios oficiales', 'Inglés'),
('9789580XXXXX2', 'Way to go! 2 - Student\'s Book', 'MEN / British Council / Richmond', 'Richmond', 132, 1, 'Texto guía', 2016, '7°', 'Ministerio de Educación Nacional', 15, 1, 'Colombia Bilingüe', 'Edición oficial gratuita para colegios oficiales', 'Inglés'),
('9789580XXXXX3', 'Way to go! 3 - Student\'s Book', 'MEN / British Council / Richmond', 'Richmond', 132, 1, 'Texto guía', 2016, '8°', 'Ministerio de Educación Nacional', 15, 1, 'Colombia Bilingüe', 'Edición oficial gratuita para colegios oficiales', 'Inglés'),
('9789580XXXXX4', 'English, Please! 1 (Fast Track) - Student\'s Book', 'MEN / British Council / Richmond', 'Richmond', 190, 1, 'Texto guía', 2015, '9°', 'Ministerio de Educación Nacional', 15, 1, 'Colombia Bilingüe', 'Edición oficial gratuita para colegios oficiales', 'Inglés'),
('9789580XXXXX5', 'English, Please! 2 (Fast Track) - Student\'s Book', 'MEN / British Council / Richmond', 'Richmond', 190, 1, 'Texto guía', 2015, '10°', 'Ministerio de Educación Nacional', 15, 1, 'Colombia Bilingüe', 'Edición oficial gratuita para colegios oficiales', 'Inglés'),
('9789580XXXXX6', 'English, Please! 3 (Fast Track) - Student\'s Book', 'MEN / British Council / Richmond', 'Richmond', 190, 1, 'Texto guía', 2015, '11°', 'Ministerio de Educación Nacional', 15, 1, 'Colombia Bilingüe', 'Edición oficial gratuita para colegios oficiales', 'Inglés'),
('9789583009046', 'Antígona', 'Sófocles', 'Panamericana', 96, 0, 'Teatro (adaptación)', 2005, '8°-9°', 'IE Jorge Robledo', 6, 1, NULL, 'Versión adaptada para bachillerato', 'Literatura'),
('9789583009725', 'La Odisea', 'Homero', 'Panamericana', 352, 0, 'Épica (adaptación)', 2004, '8°', 'IE Jorge Robledo', 6, 1, NULL, 'Versión adaptada para bachillerato', 'Literatura'),
('9789583014019', 'María', 'Jorge Isaacs', 'Panamericana', 328, 0, 'Novela', 1867, '9°', 'IE Jorge Robledo', 5, 1, NULL, NULL, 'Literatura'),
('9789583014392', 'El coronel no tiene quien le escriba', 'Gabriel García Márquez', 'Norma', 96, 0, 'Novela corta', 1961, '9°-10°', 'IE Jorge Robledo', 6, 1, NULL, NULL, 'Literatura'),
('9789583014705', 'Crónica de una muerte anunciada', 'Gabriel García Márquez', 'Oveja Negra', 120, 0, 'Novela corta', 1981, '11°', 'IE Jorge Robledo', 6, 1, NULL, NULL, 'Literatura'),
('9789583044290', 'La casa de los espíritus', 'Isabel Allende', 'Sudamericana', 448, 0, 'Novela', 1982, '10°-11°', 'IE Jorge Robledo', 4, 1, NULL, NULL, 'Literatura'),
('9789587421147', 'Hipertexto Matemáticas 10', 'Varios autores', 'Santillana', 288, 0, 'Texto guía', 2015, '10°', 'IE Jorge Robledo', 10, 1, 'Hipertexto', NULL, 'Matemáticas'),
('9789587421154', 'Hipertexto Matemáticas 11', 'Varios autores', 'Santillana', 296, 0, 'Texto guía', 2015, '11°', 'IE Jorge Robledo', 10, 1, 'Hipertexto', NULL, 'Matemáticas'),
('9789587421161', 'Hipertexto Física 10', 'Varios autores', 'Santillana', 256, 0, 'Texto guía', 2015, '10°', 'IE Jorge Robledo', 8, 1, 'Hipertexto', NULL, 'Física'),
('9789587421178', 'Hipertexto Física 11', 'Varios autores', 'Santillana', 264, 0, 'Texto guía', 2015, '11°', 'IE Jorge Robledo', 8, 1, 'Hipertexto', NULL, 'Física'),
('9789587421185', 'Hipertexto Química 10', 'Varios autores', 'Santillana', 248, 0, 'Texto guía', 2015, '10°', 'IE Jorge Robledo', 8, 1, 'Hipertexto', NULL, 'Química'),
('9789587421192', 'Hipertexto Química 11', 'Varios autores', 'Santillana', 256, 0, 'Texto guía', 2015, '11°', 'IE Jorge Robledo', 8, 1, 'Hipertexto', NULL, 'Química'),
('9789587421208', 'Hipertexto Biología 10', 'Varios autores', 'Santillana', 272, 0, 'Texto guía', 2015, '10°', 'IE Jorge Robledo', 8, 1, 'Hipertexto', NULL, 'Biología'),
('9789587421215', 'Hipertexto Biología 11', 'Varios autores', 'Santillana', 280, 0, 'Texto guía', 2015, '11°', 'IE Jorge Robledo', 8, 1, 'Hipertexto', NULL, 'Biología'),
('9789588089868', 'Geografía de Colombia', 'Varios autores', 'Voluntad', 220, 0, 'Texto guía', 2012, '6°-9°', 'IE Jorge Robledo', 8, 1, NULL, NULL, 'Geografía'),
('9789588181845', 'Historia mínima de Colombia', 'Jorge Orlando Melo', 'Turner / Fundación Mapfre', 336, 0, 'Ensayo histórico', 2017, 'Todos', 'IE Jorge Robledo', 5, 1, NULL, NULL, 'Historia'),
('9789588465247', 'Educación Física, Recreación y Deporte 6-11', 'Varios autores', 'Kinesis', 200, 0, 'Texto guía', 2014, '6°-11°', 'IE Jorge Robledo', 6, 1, NULL, NULL, 'Educación Física'),
('9789589820002', 'Álgebra de Baldor', 'Aurelio Baldor', 'Publicaciones Cultural', 692, 0, 'Texto guía', 1941, '8°-9°', 'IE Jorge Robledo', 9, 1, '', 'Clásico de referencia en álgebra', 'Matemáticas'),
('9789589820019', 'Geometría y Trigonometría de Baldor', 'Aurelio Baldor', 'Publicaciones Cultural', 568, 0, 'Texto guía', 1966, '9°-10°', 'IE Jorge Robledo', 8, 1, NULL, NULL, 'Matemáticas'),
('9789701067114', 'Química', 'Raymond Chang', 'McGraw-Hill', 1150, 0, 'Texto de consulta', 2010, '10°-11°', 'IE Jorge Robledo', 4, 1, NULL, 'Para profundización', 'Química'),
('9789702612437', 'Física conceptual', 'Paul G. Hewitt', 'Pearson Educación', 780, 0, 'Texto de consulta', 2007, '10°-11°', 'IE Jorge Robledo', 4, 1, NULL, 'Para profundización', 'Física');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prestamos`
--

CREATE TABLE `prestamos` (
  `id` int(11) NOT NULL,
  `isbn` varchar(30) NOT NULL,
  `id_usuario` varchar(20) NOT NULL,
  `tipo_usuario` enum('estudiante','docente') NOT NULL,
  `fecha_prestamo` date NOT NULL,
  `fecha_limite` date DEFAULT NULL,
  `devuelto` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_devolucion` date DEFAULT NULL,
  `solicitud_devolucion` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_solicitud` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

--
-- Volcado de datos para la tabla `prestamos`
--

INSERT INTO `prestamos` (`id`, `isbn`, `id_usuario`, `tipo_usuario`, `fecha_prestamo`, `fecha_limite`, `devuelto`, `fecha_devolucion`, `solicitud_devolucion`, `fecha_solicitud`) VALUES
(20, '978-0007', '1025894632', 'estudiante', '2026-09-07', '2026-09-15', 1, '2026-09-15', 0, NULL),
(21, '444', '1025894632', 'estudiante', '2026-09-07', '2026-09-15', 1, '2026-09-15', 0, NULL),
(31, '9789589820002', '1025894814', 'estudiante', '2026-09-07', '2026-09-15', 0, NULL, 0, NULL),
(33, '444', '1020430774', 'estudiante', '2026-09-16', '2026-09-24', 0, NULL, 0, NULL),
(34, '978-958-04-5001', '1035865524', 'estudiante', '2026-09-21', '2026-09-29', 1, '2026-09-21', 0, NULL),
(35, '9789580056378', '1035865524', 'estudiante', '2026-09-21', '2026-09-29', 1, '2026-10-02', 0, NULL),
(36, '978-0009', '123456', 'estudiante', '2026-09-21', '2026-09-29', 1, '2026-09-21', 0, NULL),
(37, '978-0009', '123456', 'estudiante', '2026-09-21', '2026-09-29', 1, '2026-10-02', 0, NULL),
(38, '978-0011', '1020303313', 'estudiante', '2026-09-28', '2026-10-06', 1, '2026-09-28', 0, NULL),
(39, '978-0012', '1020303313', 'estudiante', '2026-09-28', '2026-10-06', 0, NULL, 0, NULL),
(40, '978-958-04-1004', '1022', 'estudiante', '2026-10-02', '2026-10-10', 1, '2026-10-02', 0, '2026-10-02 10:27:41'),
(41, '9789589820002', '040625', 'docente', '2026-10-02', '2026-10-10', 1, '2026-10-02', 0, NULL),
(42, '9789589820002', '040625', 'docente', '2026-10-02', '2026-10-10', 1, '2026-10-02', 0, NULL),
(43, '9789589820002', '040625', 'docente', '2026-10-02', '2026-10-10', 1, '2026-10-02', 0, NULL),
(44, '9789589820002', '040625', 'docente', '2026-10-02', '2026-10-10', 1, '2026-10-02', 0, NULL),
(45, '9789589820002', '040625', 'docente', '2026-10-02', '2026-10-10', 1, '2026-10-02', 0, NULL),
(46, '9789589820002', '040625', 'docente', '2026-10-02', '2026-10-10', 1, '2026-10-02', 0, NULL),
(47, '9789589820002', '040625', 'docente', '2026-10-02', '2026-10-10', 1, '2026-10-02', 0, NULL),
(48, '9789589820002', '040625', 'docente', '2026-10-02', '2026-10-10', 1, '2026-10-02', 0, NULL),
(49, '9789589820002', '040625', 'docente', '2026-10-02', '2026-10-10', 1, '2026-10-02', 0, NULL),
(50, '978-0011', '1022005281', 'estudiante', '2026-10-11', '2026-10-19', 0, NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prestamos_juegos`
--

CREATE TABLE `prestamos_juegos` (
  `id` int(11) NOT NULL,
  `codigo_juego` varchar(50) NOT NULL,
  `id_usuario` varchar(50) NOT NULL,
  `tipo_usuario` enum('estudiante','docente') NOT NULL,
  `fecha_prestamo` date NOT NULL,
  `devuelto` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_devolucion` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reservas_computadores`
--

CREATE TABLE `reservas_computadores` (
  `id` int(11) NOT NULL,
  `id_computador` int(11) NOT NULL,
  `id_usuario` varchar(50) NOT NULL,
  `tipo_usuario` enum('estudiante','docente') NOT NULL,
  `fecha_reserva` date NOT NULL,
  `devuelto` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_devolucion` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `administradores`
--
ALTER TABLE `administradores`
  ADD PRIMARY KEY (`cedula`);

--
-- Indices de la tabla `carrusel`
--
ALTER TABLE `carrusel`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `computadores`
--
ALTER TABLE `computadores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_codigo` (`codigo`);

--
-- Indices de la tabla `docentes`
--
ALTER TABLE `docentes`
  ADD PRIMARY KEY (`cedula`);

--
-- Indices de la tabla `estudiantes`
--
ALTER TABLE `estudiantes`
  ADD PRIMARY KEY (`numero_matricula`);

--
-- Indices de la tabla `juegos`
--
ALTER TABLE `juegos`
  ADD PRIMARY KEY (`codigo`);

--
-- Indices de la tabla `libros`
--
ALTER TABLE `libros`
  ADD PRIMARY KEY (`isbn`);

--
-- Indices de la tabla `prestamos`
--
ALTER TABLE `prestamos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_prestamos_isbn` (`isbn`);

--
-- Indices de la tabla `prestamos_juegos`
--
ALTER TABLE `prestamos_juegos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pj_codigo` (`codigo_juego`);

--
-- Indices de la tabla `reservas_computadores`
--
ALTER TABLE `reservas_computadores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_rc_computador` (`id_computador`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `carrusel`
--
ALTER TABLE `carrusel`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `computadores`
--
ALTER TABLE `computadores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `prestamos`
--
ALTER TABLE `prestamos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT de la tabla `prestamos_juegos`
--
ALTER TABLE `prestamos_juegos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `reservas_computadores`
--
ALTER TABLE `reservas_computadores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `prestamos`
--
ALTER TABLE `prestamos`
  ADD CONSTRAINT `fk_prestamos_isbn` FOREIGN KEY (`isbn`) REFERENCES `libros` (`isbn`);

--
-- Filtros para la tabla `prestamos_juegos`
--
ALTER TABLE `prestamos_juegos`
  ADD CONSTRAINT `fk_pj_codigo` FOREIGN KEY (`codigo_juego`) REFERENCES `juegos` (`codigo`);

--
-- Filtros para la tabla `reservas_computadores`
--
ALTER TABLE `reservas_computadores`
  ADD CONSTRAINT `fk_rc_computador` FOREIGN KEY (`id_computador`) REFERENCES `computadores` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
