<?php
/**
 * Calcula las notificaciones de vencimiento del usuario logueado.
 * Se usa desde includes/header.php para pintar la campana en TODAS las páginas.
 *
 * Devuelve siempre el mismo formato:
 * [
 *   "total"   => int,                 // cuántas notificaciones hay
 *   "urgente" => bool,                // true si alguna es "vencido" (para pintar la campana en rojo)
 *   "items"   => [
 *       ["tipo" => "vencido"|"por_vencer"|"info", "texto" => "...", "link" => "..."]
 *   ]
 * ]
 * se lo tengo que explicar a salo y lore!!!
 */
function obtener_notificaciones(PDO $pdo): array
{
    $resultado = ["total" => 0, "urgente" => false, "items" => []];

    if (!isset($_SESSION["rol"])) {
        return $resultado;
    }

    $rol = $_SESSION["rol"];

    // estudiantes y docentes: sus propios préstamos de libros 
    if (in_array($rol, ["estudiante", "docente"])) {
        $stmt = $pdo->prepare("
            SELECT l.nombre, COUNT(*) AS cantidad,
                   MIN(DATEDIFF(p.fecha_limite, CURDATE())) AS dias_restantes
            FROM prestamos p
            JOIN libros l ON l.isbn = p.isbn
            WHERE p.devuelto = FALSE AND p.id_usuario = ? AND p.tipo_usuario = ?
            GROUP BY l.isbn, l.nombre
            ORDER BY dias_restantes ASC
        ");
        $stmt->execute([$_SESSION["id"], $rol]);
        $prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($prestamos as $p) {
            if ($p["dias_restantes"] < 0) {
                $resultado["items"][] = [
                    "tipo"  => "vencido",
                    "texto" => "\"{$p['nombre']}\" está vencido. Devuélvelo lo antes posible.",
                    "link"  => "prestamos.php",
                ];
                $resultado["urgente"] = true;
            } elseif ($p["dias_restantes"] <= 2) {
                $dias = (int)$p["dias_restantes"];
                $cuando = $dias === 0 ? "vence hoy" : "vence en $dias día(s)";
                $resultado["items"][] = [
                    "tipo"  => "por_vencer",
                    "texto" => "\"{$p['nombre']}\" $cuando.",
                    "link"  => "prestamos.php",
                ];
            }
        }
    }

    // vista general del admin
    if ($rol === "administrador") {
        // Solicitudes de devolución pendientes
        $solicitudes = (int)$pdo->query("
            SELECT COUNT(*) FROM prestamos WHERE devuelto = 0 AND solicitud_devolucion = 1
        ")->fetchColumn();

        if ($solicitudes > 0) {
            $resultado["items"][] = [
                "tipo"  => "por_vencer",
                "texto" => "$solicitudes solicitud(es) de devolución pendiente(s) de confirmar.",
                "link"  => "libros.php?tab=solicitudes",
            ];
        }

        $vencidos = $pdo->query("
            SELECT COUNT(*) FROM prestamos WHERE devuelto = FALSE AND fecha_limite < CURDATE()
        ")->fetchColumn();

        if ($vencidos > 0) {
            $resultado["items"][] = [
                "tipo"  => "vencido",
                "texto" => "$vencidos préstamo(s) vencido(s) en toda la biblioteca.",
                "link"  => "libros.php",
            ];
            $resultado["urgente"] = true;
        }

        // consulta libros reales sin copias (disponibilidad = 0 o cantidad <= 0)
        $stmt_agotados = $pdo->query("
            SELECT isbn, nombre FROM libros
            WHERE disponibilidad = FALSE OR cantidad <= 0
            ORDER BY nombre ASC
            LIMIT 10
        ");
        $libros_agotados = $stmt_agotados->fetchAll(PDO::FETCH_ASSOC);

        foreach ($libros_agotados as $la) {
            $resultado["items"][] = [
                "tipo"  => "vencido",
                "texto" => "El libro \"{$la['nombre']}\" se quedó sin copias disponibles.",
                "link"  => "libros.php?resaltar=" . urlencode($la["isbn"]) . "#libro-" . urlencode($la["isbn"]),
            ];
            $resultado["urgente"] = true;
        }
    }

    $resultado["total"] = count($resultado["items"]);
    return $resultado;
}
