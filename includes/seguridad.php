<?php
/**
 * Funciones de seguridad centralizadas para Cercatori:
 * - Manejo seguro de sesiones
 * - Protección CSRF
 * - Hashing y verificación de contraseñas con migración transparente
 * - Validación de duplicados sin fuga de información
 */

/**
 * Inicia la sesión con parámetros seguros de cookies.
 */
function iniciar_sesion_segura(): void {
    if (session_status() === PHP_SESSION_NONE) {
        $es_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
        
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $es_https,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_start();
    }
}

/**
 * Obtiene el token CSRF actual o genera uno nuevo si no existe.
 */
function obtener_csrf_token(): string {
    iniciar_sesion_segura();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Genera el campo input HTML oculto con el token CSRF.
 */
function csrf_campo(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(obtener_csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Valida el token CSRF enviado en peticiones POST.
 */
function validar_csrf(): bool {
    iniciar_sesion_segura();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        $sesion_token = $_SESSION['csrf_token'] ?? '';
        if (empty($token) || empty($sesion_token) || !hash_equals($sesion_token, $token)) {
            return false;
        }
    }
    return true;
}

/**
 * Verifica una contraseña contra el hash de la base de datos.
 * Si la contraseña guardada estaba en texto plano (cuentas anteriores),
 * la valida y la migra automáticamente a un hash seguro de inmediato.
 */
function verificar_y_migrar_clave(string $clave_ingresada, string $clave_guardada, PDO $pdo, string $tabla, string $campo_id, string $valor_id): bool {
    // 1. Caso contraseña ya hasheada con bcrypt/argon
    if (password_verify($clave_ingresada, $clave_guardada)) {
        if (password_needs_rehash($clave_guardada, PASSWORD_DEFAULT)) {
            $nuevo_hash = password_hash($clave_ingresada, PASSWORD_DEFAULT);
            $upd = $pdo->prepare("UPDATE `$tabla` SET clave = ? WHERE `$campo_id` = ?");
            $upd->execute([$nuevo_hash, $valor_id]);
        }
        return true;
    }

    // 2. Caso contraseña antigua en texto plano (compatibilidad y migración automática)
    if ($clave_guardada === $clave_ingresada) {
        $nuevo_hash = password_hash($clave_ingresada, PASSWORD_DEFAULT);
        $upd = $pdo->prepare("UPDATE `$tabla` SET clave = ? WHERE `$campo_id` = ?");
        $upd->execute([$nuevo_hash, $valor_id]);
        return true;
    }

    return false;
}

/**
 * Comprueba si un documento / matrícula ya está registrado en alguna de las tablas de usuarios,
 * evitando colisiones y errores SQL de llave duplicada.
 */
function id_existe_en_sistema(PDO $pdo, string $id): bool {
    $id = trim($id);
    if ($id === '') {
        return false;
    }

    $stmt1 = $pdo->prepare("SELECT 1 FROM administradores WHERE cedula = ? LIMIT 1");
    $stmt1->execute([$id]);
    if ($stmt1->fetchColumn()) return true;

    $stmt2 = $pdo->prepare("SELECT 1 FROM docentes WHERE cedula = ? LIMIT 1");
    $stmt2->execute([$id]);
    if ($stmt2->fetchColumn()) return true;

    $stmt3 = $pdo->prepare("SELECT 1 FROM estudiantes WHERE numero_matricula = ? LIMIT 1");
    $stmt3->execute([$id]);
    if ($stmt3->fetchColumn()) return true;

    return false;
}
