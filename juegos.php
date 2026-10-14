<?php
session_start();
require "config.php";

if (!isset($_SESSION["rol"])) {
    header("Location: login.php");
    exit;
}

$rol = $_SESSION["rol"];
$mensaje = "";
$mensaje_tipo = "ok";
$active = "materias";
$admin_active = "juegos";

require_once __DIR__ . "/includes/admin_foto.php";

// ─── Vista administrador: registrar y listar juegos ───
if ($rol === "administrador") {

    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["crear"])) {
        if (!validar_csrf()) {
            $mensaje = "Petición no válida (CSRF). Por favor recarga la página.";
            $mensaje_tipo = "error";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO juegos (codigo, nombre, tipo, cantidad, disponibilidad) VALUES (?,?,?,?,?)");
                $stmt->execute([
                    trim($_POST["codigo"] ?? ""),
                    trim($_POST["nombre"] ?? ""),
                    trim($_POST["tipo"] ?? ""),
                    (int)($_POST["cantidad"] ?? 1),
                    isset($_POST["disponibilidad"]) ? 1 : 0
                ]);
                $mensaje = "Juego registrado con éxito.";
                $mensaje_tipo = "ok";
            } catch (PDOException $e) {
                error_log("Error al registrar juego: " . $e->getMessage());
                if (strpos($e->getMessage(), "1062") !== false || strpos($e->getMessage(), "Duplicate") !== false) {
                    $mensaje = "Ya existe un juego con ese código.";
                } else {
                    $mensaje = "No se pudo registrar el juego.";
                }
                $mensaje_tipo = "error";
            }
        }
    }

    $juegos = $pdo->query("SELECT * FROM juegos ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

    // Préstamos de juegos para el administrador
    $prestamos_juegos = $pdo->query("
        SELECT p.id, p.codigo_juego, COALESCE(j.nombre, 'Juego no catalogado') AS juego, p.tipo_usuario, p.id_usuario,
               p.fecha_prestamo, p.devuelto, p.fecha_devolucion,
               CASE
                   WHEN p.tipo_usuario = 'estudiante' THEN COALESCE(NULLIF(TRIM((SELECT CONCAT_WS(' ', nombres, apellidos) FROM estudiantes WHERE numero_matricula = p.id_usuario)), ''), p.id_usuario)
                   WHEN p.tipo_usuario = 'docente' THEN COALESCE(NULLIF(TRIM((SELECT CONCAT_WS(' ', nombre, apellidos) FROM docentes WHERE cedula = p.id_usuario)), ''), p.id_usuario)
                   ELSE p.id_usuario
               END AS usuario
        FROM prestamos_juegos p
        LEFT JOIN juegos j ON j.codigo = p.codigo_juego
        ORDER BY p.devuelto ASC, p.fecha_prestamo DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $juegos_activos = array_filter($prestamos_juegos, fn($p) => (int)$p["devuelto"] === 0);
    $juegos_historial = array_filter($prestamos_juegos, fn($p) => (int)$p["devuelto"] === 1);
}

// ─── Vista estudiante/docente: prestar y devolver ───
if (in_array($rol, ["estudiante", "docente"])) {
    $id_sesion = $_SESSION["id"];
    $limite = $rol === "estudiante" ? 3 : 999;

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        if (!validar_csrf()) {
            $mensaje = "Petición no válida (CSRF). Por favor recarga la página.";
            $mensaje_tipo = "error";
        } else {
            if (isset($_POST["prestar"])) {
                $codigo = $_POST["codigo"];
                $cantidad_pedida = $rol === "docente" ? max(1, (int)$_POST["cantidad"]) : 1;

                $stmt = $pdo->prepare("SELECT COUNT(*) FROM prestamos_juegos WHERE id_usuario = ? AND tipo_usuario = ? AND devuelto = 0");
                $stmt->execute([$id_sesion, $rol]);

                if ($stmt->fetchColumn() >= $limite) {
                    $mensaje = "Alcanzaste tu límite de juegos prestados.";
                    $mensaje_tipo = "error";
                } else {
                    $stmt = $pdo->prepare("SELECT cantidad, disponibilidad FROM juegos WHERE codigo = ?");
                    $stmt->execute([$codigo]);
                    $juego = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$juego) {
                        $mensaje = "Juego no existe.";
                        $mensaje_tipo = "error";
                    } elseif (!$juego["disponibilidad"] || (int)$juego["cantidad"] < $cantidad_pedida) {
                        $mensaje = "No hay suficientes ejemplares disponibles.";
                        $mensaje_tipo = "error";
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO prestamos_juegos (codigo_juego, id_usuario, tipo_usuario, fecha_prestamo, devuelto) VALUES (?,?,?,CURDATE(),0)");
                        for ($i = 0; $i < $cantidad_pedida; $i++) {
                            $stmt->execute([$codigo, $id_sesion, $rol]);
                        }

                        // Descontar inventario de juegos
                        $upd = $pdo->prepare("UPDATE juegos SET cantidad = GREATEST(0, cantidad - ?), disponibilidad = (CASE WHEN cantidad - ? <= 0 THEN 0 ELSE 1 END) WHERE codigo = ?");
                        $upd->execute([$cantidad_pedida, $cantidad_pedida, $codigo]);

                        $mensaje = "Préstamo registrado: $cantidad_pedida ejemplar(es).";
                    }
                }
            }

            if (isset($_POST["devolver_lote"])) {
                $codigo_lote = $_POST["codigo_lote"];
                $cant_lote = max(1, (int)$_POST["cantidad_lote"]);

                $stmt = $pdo->prepare("SELECT id FROM prestamos_juegos WHERE id_usuario = ? AND tipo_usuario = ? AND codigo_juego = ? AND devuelto = 0 ORDER BY fecha_prestamo ASC LIMIT " . (int)$cant_lote);
                $stmt->execute([$id_sesion, $rol, $codigo_lote]);
                $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

                if (!empty($ids)) {
                    $inQuery = implode(',', array_fill(0, count($ids), '?'));
                    $upd = $pdo->prepare("UPDATE prestamos_juegos SET devuelto = 1, fecha_devolucion = CURDATE() WHERE id IN ($inQuery)");
                    $upd->execute($ids);

                    $cant_real = count($ids);
                    $updJuego = $pdo->prepare("UPDATE juegos SET cantidad = cantidad + ?, disponibilidad = 1 WHERE codigo = ?");
                    $updJuego->execute([$cant_real, $codigo_lote]);

                    $mensaje = "$cant_real ejemplar(es) devuelto(s) con éxito.";
                }
            }
        }
    }

    $disponibles = $pdo->query("SELECT * FROM juegos WHERE disponibilidad = TRUE AND cantidad > 0 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

    // Préstamos activos del usuario
    $stmt = $pdo->prepare("
        SELECT j.codigo, j.nombre, COUNT(*) AS cantidad, MIN(p.fecha_prestamo) AS primera_fecha
        FROM prestamos_juegos p
        JOIN juegos j ON j.codigo = p.codigo_juego
        WHERE p.devuelto = 0 AND p.id_usuario = ? AND p.tipo_usuario = ?
        GROUP BY j.codigo, j.nombre
    ");
    $stmt->execute([$id_sesion, $rol]);
    $mis_prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Historial de préstamos devueltos del usuario
    $stmt_hist = $pdo->prepare("
        SELECT p.id, p.codigo_juego, COALESCE(j.nombre, 'Juego no catalogado') AS nombre, p.fecha_prestamo, p.fecha_devolucion
        FROM prestamos_juegos p
        LEFT JOIN juegos j ON j.codigo = p.codigo_juego
        WHERE p.devuelto = 1 AND p.id_usuario = ? AND p.tipo_usuario = ?
        ORDER BY p.fecha_devolucion DESC, p.id DESC
        LIMIT 50
    ");
    $stmt_hist->execute([$id_sesion, $rol]);
    $historial_juegos = $stmt_hist->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link rel="icon" href="img/favicon.ico"/>
    <title>Cercatori - Juegos</title>
    <link rel="stylesheet" href="css/index.css">
</head>
<body>

<?php include "includes/header.php"; ?>

<?php if ($rol === "administrador"): ?>
    <?php include "includes/admin_nav.php"; ?>
<?php else: ?>
<div class="top_bar_sesion">
    <span>Bienvenido, <?= htmlspecialchars($_SESSION["nombre"]) ?> (<?= htmlspecialchars($rol) ?>)</span>
    <a href="logout.php">Cerrar sesión</a>
</div>
<?php endif; ?>

<?php if ($rol === "administrador"): ?>

<main class="admin_section">
    <h1>Juegos</h1>
    <p class="admin_subtitle">Registra juegos de mesa, cartas o consola disponibles.</p>

    <?php if ($mensaje): ?><p class="mensaje_ok"><?= htmlspecialchars($mensaje) ?></p><?php endif; ?>

    <div class="admin_layout">
        <div class="formulario">
            <h3>Registrar juego</h3>
            <form method="POST">
                <?= csrf_campo() ?>
                <label>Código</label>
                <input name="codigo" placeholder="JG-01" required>

                <label>Nombre</label>
                <input name="nombre" required>

                <label>Tipo</label>
                <select name="tipo">
                    <option value="Mesa">Mesa</option>
                    <option value="Cartas">Cartas</option>
                    <option value="Rompecabezas">Rompecabezas</option>
                </select>

                <label>Cantidad</label>
                <input type="number" name="cantidad" value="1">

                <label>
                    <input type="checkbox" name="disponibilidad" checked class="checkbox_inline">
                    Disponible
                </label>

                <button type="submit" name="crear">Guardar</button>
            </form>
        </div>

        <div class="tabla_wrapper">
            <h2>Inventario (<?= count($juegos) ?>)</h2>
            <table class="tabla_admin">
            <tr><th>Código</th><th>Nombre</th><th>Tipo</th><th>Cantidad</th><th>Disponible</th></tr>
            <?php foreach ($juegos as $j): ?>
            <tr>
                <td><?= htmlspecialchars($j["codigo"]) ?></td>
                <td><?= htmlspecialchars($j["nombre"]) ?></td>
                <td><?= htmlspecialchars($j["tipo"]) ?></td>
                <td><?= htmlspecialchars($j["cantidad"]) ?></td>
                <td><?= $j["disponibilidad"] ? "Sí" : "No" ?></td>
            </tr>
            <?php endforeach; ?>
            </table>
        </div>
    </div>

    <hr class="divisor_seccion">

    <h2>Préstamos de juegos</h2>
    <p class="admin_subtitle">Consulta qué juegos están prestados actualmente y el historial de devoluciones.</p>

    <div class="tabs_admin">
        <button type="button" class="tab_btn tab_activo" data-tab="activos">
            Activos (<?= count($juegos_activos) ?>)
        </button>
        <button type="button" class="tab_btn" data-tab="historial">
            Historial (30 días) (<?= count($juegos_historial) ?>)
        </button>
    </div>

    <div class="tabla_wrapper tab_panel tabla_scroll" data-panel="activos">
        <table class="tabla_admin">
            <tr>
                <th>Juego</th><th>Código</th><th>Usuario</th><th>Rol</th><th>Prestado</th>
            </tr>
            <?php if (empty($juegos_activos)): ?>
            <tr><td colspan="5">No hay préstamos de juegos activos en este momento.</td></tr>
            <?php endif; ?>
            <?php foreach ($juegos_activos as $p): ?>
            <tr>
                <td><strong><?= htmlspecialchars($p["juego"]) ?></strong></td>
                <td><?= htmlspecialchars($p["codigo_juego"]) ?></td>
                <td>
                    <strong><?= htmlspecialchars($p["usuario"] ?? "—") ?></strong>
                    <br><small style="opacity: 0.7; font-size: 11px;">ID: <?= htmlspecialchars($p["id_usuario"]) ?></small>
                </td>
                <td><span class="badge_dias badge_ok"><?= htmlspecialchars($p["tipo_usuario"]) ?></span></td>
                <td><?= htmlspecialchars($p["fecha_prestamo"]) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="tabla_wrapper tab_panel tab_panel_oculto tabla_scroll" data-panel="historial">
        <div style="padding: 10px 14px; margin-bottom: 12px; background: rgba(6,156,51,0.08); border-left: 4px solid var(--accent); border-radius: 6px; font-size: 13px; color: var(--dark);">
            <i class="ti ti-info-circle"></i> <strong>Historial de devoluciones (últimos 30 días):</strong> El sistema depura automáticamente los registros con más de 30 días de antigüedad.
        </div>
        <table class="tabla_admin">
            <tr>
                <th>Juego</th><th>Código</th><th>Usuario</th><th>Rol</th><th>Prestado</th><th>Devuelto</th>
            </tr>
            <?php if (empty($juegos_historial)): ?>
            <tr><td colspan="6">Todavía no hay devoluciones de juegos registradas.</td></tr>
            <?php endif; ?>
            <?php foreach ($juegos_historial as $p): ?>
            <tr>
                <td><strong><?= htmlspecialchars($p["juego"]) ?></strong></td>
                <td><?= htmlspecialchars($p["codigo_juego"]) ?></td>
                <td>
                    <strong><?= htmlspecialchars($p["usuario"] ?? "—") ?></strong>
                    <br><small style="opacity: 0.7; font-size: 11px;">ID: <?= htmlspecialchars($p["id_usuario"]) ?></small>
                </td>
                <td><span class="badge_dias badge_ok"><?= htmlspecialchars($p["tipo_usuario"]) ?></span></td>
                <td><?= htmlspecialchars($p["fecha_prestamo"]) ?></td>
                <td><?= htmlspecialchars($p["fecha_devolucion"] ?? "—") ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

</main>

<?php else: ?>

<main class="perfil_main">
    <section class="perfil_contenido perfil_contenido_top">

        <div class="perfil_col perfil_col_main">
            <div class="perfil_panel">
                <div class="panel_header">
                    <i class="ti ti-puzzle"></i>
                    <h3>Juegos disponibles</h3>
                </div>

                <?php if ($mensaje): ?>
                    <p class="<?= $mensaje_tipo === "error" ? "mensaje_error" : "mensaje_ok" ?>"><?= htmlspecialchars($mensaje) ?></p>
                <?php endif; ?>

                <form method="POST">
                    <?= csrf_campo() ?>
                    <table class="tabla_admin">
                    <tr>
                        <th></th><th>Nombre</th><th>Tipo</th><th>Disp.</th>
                        <?php if ($rol === "docente"): ?><th>Cantidad</th><?php endif; ?>
                    </tr>
                    <?php foreach ($disponibles as $j): ?>
                    <tr>
                        <td><input type="radio" name="codigo" value="<?= htmlspecialchars($j["codigo"]) ?>" required></td>
                        <td><?= htmlspecialchars($j["nombre"]) ?></td>
                        <td><?= htmlspecialchars($j["tipo"]) ?></td>
                        <td><?= (int)$j["cantidad"] ?></td>
                        <?php if ($rol === "docente"): ?>
                        <td><input type="number" name="cantidad" min="1" max="<?= (int)$j["cantidad"] ?>" value="1" class="input_cantidad"></td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                    </table>
                    <button type="submit" name="prestar" class="btn_compacto">Prestar seleccionado</button>
                </form>
            </div>
        </div>

        <div class="perfil_col perfil_col_side">
            <div class="perfil_panel">
                <div class="panel_header">
                    <i class="ti ti-puzzle"></i>
                    <h3>Mis Juegos Prestados</h3>
                </div>

                <div class="seccion_activos">
                    <p class="subtitulo_panel">Juegos activos (<?= count($mis_prestamos) ?>)</p>
                    <?php if (empty($mis_prestamos)): ?>
                        <p class="texto_vacio">No tienes juegos prestados.</p>
                    <?php endif; ?>

                    <div class="favoritos_lista lista_scroll">
                        <?php foreach ($mis_prestamos as $p): ?>
                        <div class="favorito_item">
                            <div class="fav_cover fav_cover_2"><i class="ti ti-puzzle"></i></div>
                            <div class="fav_info">
                                <p class="fav_titulo"><?= htmlspecialchars($p["nombre"]) ?></p>
                                <p class="fav_autor"><?= $p["cantidad"] ?> ejemplar(es) desde <?= htmlspecialchars($p["primera_fecha"]) ?></p>
                            </div>
                        </div>
                        <form method="POST" class="form_inline">
                            <?= csrf_campo() ?>
                            <input type="hidden" name="codigo_lote" value="<?= htmlspecialchars($p["codigo"]) ?>">
                            <input type="number" name="cantidad_lote" min="1" max="<?= $p["cantidad"] ?>" value="<?= $p["cantidad"] ?>">
                            <button type="submit" name="devolver_lote">Devolver</button>
                        </form>
                        <?php endforeach; ?>
                    </div>
                </div>

                <hr class="divisor_panel">

                <button type="button" class="btn_toggle_historial" data-target="historial_juegos_drawer">
                    <span class="btn_toggle_info">
                        <i class="ti ti-history"></i>
                        <span>Historial de devoluciones (<?= count($historial_juegos) ?>)</span>
                    </span>
                    <i class="ti ti-chevron-down flecha_toggle"></i>
                </button>

                <div id="historial_juegos_drawer" class="historial_drawer drawer_cerrado">
                    <?php if (empty($historial_juegos)): ?>
                        <p class="texto_vacio">No tienes devoluciones de juegos registradas.</p>
                    <?php else: ?>
                        <div class="favoritos_lista lista_scroll">
                            <?php foreach ($historial_juegos as $h): ?>
                            <div class="favorito_item">
                                <div class="fav_cover fav_cover_2"><i class="ti ti-check"></i></div>
                                <div class="fav_info">
                                    <p class="fav_titulo"><?= htmlspecialchars($h["nombre"]) ?></p>
                                    <p class="fav_autor" style="font-size: 12px; color: rgba(13,46,22,0.65);">
                                        Prestado: <?= htmlspecialchars($h["fecha_prestamo"]) ?><br>
                                        Devuelto: <strong style="color: #069c33;"><?= htmlspecialchars($h["fecha_devolucion"] ?? '—') ?></strong>
                                    </p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>

    </section>
</main>

<?php endif; ?>

<?php include "includes/footer.php"; ?>

<script src="js/index.js"></script>
</body>
</html>
