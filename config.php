<?php
// Configuración de conexión
$host = "localhost";
$db   = "biblioteca";
$user = "root";
$pass = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Error de conexión a la base de datos Cercatori: " . $e->getMessage());
    die("No se pudo conectar a la base de datos. Por favor intenta más tarde.");
}

// Utilidades de seguridad (CSRF, contraseñas, sesiones)
require_once __DIR__ . "/includes/seguridad.php";
iniciar_sesion_segura();

// coso de admins
define("CODIGO_ADMIN", "CERCATORI-ADMIN-2026");

//coso de profes
define("CODIGO_DOCENTE", "CERCATORI-DOCENTE-2026");

// Migraciones automáticas y depuración periódica de 30 días
require_once __DIR__ . "/includes/migraciones_auto.php";
ejecutar_migraciones_y_limpieza($pdo);

