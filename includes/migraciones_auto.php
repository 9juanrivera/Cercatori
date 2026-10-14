<?php
/**
 * Migraciones automáticas y depuración periódica de historiales.
 * Se ejecuta de forma transparente en config.php.
 */

function ejecutar_migraciones_y_limpieza(PDO $pdo): void {
    try {
        // 1. Verificar y agregar columnas de solicitud de devolución en 'prestamos'
        $stmtCol = $pdo->query("SHOW COLUMNS FROM prestamos LIKE 'solicitud_devolucion'");
        if ($stmtCol->rowCount() === 0) {
            $pdo->exec("ALTER TABLE prestamos 
                        ADD COLUMN solicitud_devolucion TINYINT(1) NOT NULL DEFAULT 0,
                        ADD COLUMN fecha_solicitud DATETIME DEFAULT NULL");
        }

        // 2. Crear tabla 'carrusel' si no existe
        $pdo->exec("CREATE TABLE IF NOT EXISTS carrusel (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            imagen VARCHAR(255) NOT NULL,
            frase TEXT NOT NULL,
            orden INT(11) NOT NULL DEFAULT 0,
            activo TINYINT(1) NOT NULL DEFAULT 1,
            fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC");

        // 3. Poblar carrusel con diapositivas iniciales si está vacío
        $conteoCarrusel = (int)$pdo->query("SELECT COUNT(*) FROM carrusel")->fetchColumn();
        if ($conteoCarrusel === 0) {
            $insCarrusel = $pdo->prepare("INSERT INTO carrusel (imagen, frase, orden, activo) VALUES (?, ?, ?, 1)");
            $insCarrusel->execute([
                "Imagen1.jpeg",
                "Una biblioteca no es un conjunto de libros leídos, sino una compañía, un refugio y un proyecto de vida",
                1
            ]);
            $insCarrusel->execute([
                "Imagen2.jpeg",
                "Los libros son espejos: solo te ves en ellos si ya estás dentro.",
                2
            ]);
            $insCarrusel->execute([
                "Imagen3.jpg",
                "Leer es vivir mil vidas antes de acabar la tuya.",
                3
            ]);
        }

        // 4. Depuración automática de historiales: eliminar registros devueltos con más de 30 días
        $pdo->exec("DELETE FROM prestamos WHERE devuelto = 1 AND fecha_devolucion IS NOT NULL AND fecha_devolucion < DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
        
        // Verificar si existen tablas de juegos y computadores para depurarlas también
        $tablas = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        if (in_array("prestamos_juegos", $tablas)) {
            $pdo->exec("DELETE FROM prestamos_juegos WHERE devuelto = 1 AND fecha_devolucion IS NOT NULL AND fecha_devolucion < DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
        }
        if (in_array("reservas_computadores", $tablas)) {
            $pdo->exec("DELETE FROM reservas_computadores WHERE devuelto = 1 AND fecha_devolucion IS NOT NULL AND fecha_devolucion < DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
        }

    } catch (PDOException $e) {
        // En caso de que la tabla aún no exista durante instalación o error silencioso de DDL,
        // registramos en el log de errores de PHP sin interrumpir la ejecución.
        error_log("Aviso Cercatori Migraciones: " . $e->getMessage());
    }
}
