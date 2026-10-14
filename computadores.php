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
$admin_active = "computadores";

require_once __DIR__ . "/includes/admin_foto.php";

// zona admin pa meter los peces
if ($rol === "administrador") {

    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["crear"])) {
        if (!validar_csrf()) {
            $mensaje = "Petición no válida (CSRF). Por favor recarga la página.";
            $mensaje_tipo = "error";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO computadores (codigo, ubicacion, estado, disponible) VALUES (?,?,?,?)");
                $stmt->execute([
                    trim($_POST["codigo"] ?? ""),
                    trim($_POST["ubicacion"] ?? ""),
                    $_POST["estado"] ?? "operativo",
                    isset($_POST["disponible"]) ? 1 : 0
                ]);
                $_SESSION["flash_pc"] = "Computador registrado con éxito.";
                header("Location: computadores.php");
                exit;
            } catch (PDOException $e) {
                error_log("Error en computadores: " . $e->getMessage());
                if (strpos($e->getMessage(), "1062") !== false || strpos($e->getMessage(), "Duplicate") !== false) {
                    $mensaje = "Ya existe un computador con ese código.";
                } else {
                    $mensaje = "No se pudo registrar el computador.";
                }
                $mensaje_tipo = "error";
            }
        }
    }

    if (!empty($_SESSION["flash_pc"])) {
        $mensaje = $_SESSION["flash_pc"];
        unset($_SESSION["flash_pc"]);
    }

    $computadores = $pdo->query("
        SELECT c.*,
               r.id AS id_reserva_activa,
               r.tipo_usuario AS reserva_tipo_usuario,
               r.id_usuario AS reserva_id_usuario,
               r.fecha_reserva AS reserva_fecha,
               CASE
                   WHEN r.tipo_usuario = 'estudiante' THEN COALESCE(NULLIF(TRIM((SELECT CONCAT_WS(' ', nombres, apellidos) FROM estudiantes WHERE numero_matricula = r.id_usuario)), ''), r.id_usuario)
                   WHEN r.tipo_usuario = 'docente' THEN COALESCE(NULLIF(TRIM((SELECT CONCAT_WS(' ', nombre, apellidos) FROM docentes WHERE cedula = r.id_usuario)), ''), r.id_usuario)
                   ELSE r.id_usuario
               END AS reserva_usuario
        FROM computadores c
        LEFT JOIN reservas_computadores r ON r.id_computador = c.id AND r.devuelto = 0
        ORDER BY c.codigo ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Reservas admin
    $reservas_computadores = $pdo->query("
        SELECT r.id, c.codigo, c.ubicacion, r.tipo_usuario, r.id_usuario,
               r.fecha_reserva, r.devuelto, r.fecha_devolucion,
               CASE
                   WHEN r.tipo_usuario = 'estudiante' THEN COALESCE(NULLIF(TRIM((SELECT CONCAT_WS(' ', nombres, apellidos) FROM estudiantes WHERE numero_matricula = r.id_usuario)), ''), r.id_usuario)
                   WHEN r.tipo_usuario = 'docente' THEN COALESCE(NULLIF(TRIM((SELECT CONCAT_WS(' ', nombre, apellidos) FROM docentes WHERE cedula = r.id_usuario)), ''), r.id_usuario)
                   ELSE r.id_usuario
               END AS usuario
        FROM reservas_computadores r
        JOIN computadores c ON c.id = r.id_computador
        ORDER BY r.devuelto ASC, r.fecha_reserva DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $reservas_activas = array_filter($reservas_computadores, fn($r) => (int)$r["devuelto"] === 0);
    $reservas_historial = array_filter($reservas_computadores, fn($r) => (int)$r["devuelto"] === 1);
}

// vista profes y estudiantes prestar y devolver
if (in_array($rol, ["estudiante", "docente"])) {
    $id_sesion = $_SESSION["id"];

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        if (!validar_csrf()) {
            $mensaje = "Petición no válida (CSRF). Por favor recarga la página.";
            $mensaje_tipo = "error";
        } else {
            if (isset($_POST["reservar"])) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservas_computadores WHERE id_usuario = ? AND tipo_usuario = ? AND devuelto = 0");
                $stmt->execute([$id_sesion, $rol]);

                if ($stmt->fetchColumn() >= 1) {
                    $mensaje = "Ya tienes un computador reservado. Devuélvelo antes de pedir otro.";
                    $mensaje_tipo = "error";
                } else {
                    $id_pc = $_POST["id_computador"];
                    $stmt = $pdo->prepare("SELECT disponible FROM computadores WHERE id = ? AND estado = 'operativo'");
                    $stmt->execute([$id_pc]);
                    $pc = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$pc || !$pc["disponible"]) {
                        $mensaje = "Ese computador ya no está disponible.";
                        $mensaje_tipo = "error";
                    } else {
                        $pdo->prepare("INSERT INTO reservas_computadores (id_computador, id_usuario, tipo_usuario, fecha_reserva, devuelto) VALUES (?,?,?,CURDATE(),0)")
                            ->execute([$id_pc, $id_sesion, $rol]);
                        $pdo->prepare("UPDATE computadores SET disponible = FALSE WHERE id = ?")->execute([$id_pc]);
                        $mensaje = "Computador reservado.";
                    }
                }
            }

            if (isset($_POST["devolver"])) {
                $id_reserva = $_POST["reserva_id"];
                $stmt = $pdo->prepare("SELECT id_computador FROM reservas_computadores WHERE id = ? AND id_usuario = ? AND devuelto = 0");
                $stmt->execute([$id_reserva, $id_sesion]);
                $reserva = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($reserva) {
                    $pdo->prepare("UPDATE reservas_computadores SET devuelto = 1, fecha_devolucion = CURDATE() WHERE id = ?")->execute([$id_reserva]);
                    $pdo->prepare("UPDATE computadores SET disponible = TRUE WHERE id = ?")->execute([$reserva["id_computador"]]);
                    $mensaje = "Computador devuelto.";
                }
            }
        }
    }

    $disponibles = $pdo->query("SELECT * FROM computadores WHERE disponible = TRUE AND estado = 'operativo' ORDER BY codigo")->fetchAll(PDO::FETCH_ASSOC);

    // Reservas activas del usuario
    $stmt = $pdo->prepare("
        SELECT r.id, c.codigo, c.ubicacion, r.fecha_reserva
        FROM reservas_computadores r
        JOIN computadores c ON c.id = r.id_computador
        WHERE r.devuelto = 0 AND r.id_usuario = ? AND r.tipo_usuario = ?
    ");
    $stmt->execute([$id_sesion, $rol]);
    $mis_reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Historial de reservas devueltas del usuario
    $stmt_hist = $pdo->prepare("
        SELECT r.id, c.codigo, c.ubicacion, r.fecha_reserva, r.fecha_devolucion
        FROM reservas_computadores r
        JOIN computadores c ON c.id = r.id_computador
        WHERE r.devuelto = 1 AND r.id_usuario = ? AND r.tipo_usuario = ?
        ORDER BY r.fecha_devolucion DESC, r.id DESC
        LIMIT 50
    ");
    $stmt_hist->execute([$id_sesion, $rol]);
    $historial_reservas = $stmt_hist->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Cercatori - Computadores</title>
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

    <div class="admin_encabezado">
        <div>
            <h1>Computadores</h1>
            <p class="admin_subtitle">Registra equipos y consulta quién los tiene.</p>
        </div>
        <button type="button" class="btn_nuevo_usuario btn_grande" id="btn_abrir_modal_pc">
            <i class="ti ti-plus"></i> Agregar computador
        </button>
    </div>

    <?php if ($mensaje): ?>
        <p class="<?= $mensaje_tipo === "error" ? "mensaje_error" : "mensaje_ok" ?>"><?= htmlspecialchars($mensaje) ?></p>
    <?php endif; ?>

    <?php
        $pc_operativos  = count(array_filter($computadores, fn($c) => $c["estado"] === "operativo"));
        $pc_libres      = count(array_filter($computadores, fn($c) => $c["estado"] === "operativo" && $c["disponible"]));
        $pc_mantenim    = count($computadores) - $pc_operativos;
    ?>
    <div class="admin_stats_row">
        <div class="stat_mini">
            <span class="stat_mini_num"><?= count($computadores) ?></span>
            <span class="stat_mini_label">Equipos</span>
        </div>
        <div class="stat_mini">
            <span class="stat_mini_num"><?= $pc_libres ?></span>
            <span class="stat_mini_label">Libres ahora</span>
        </div>
        <div class="stat_mini">
            <span class="stat_mini_num"><?= count($reservas_activas) ?></span>
            <span class="stat_mini_label">Reservados</span>
        </div>
        <div class="stat_mini">
            <span class="stat_mini_num"><?= $pc_mantenim ?></span>
            <span class="stat_mini_label">En mantenimiento</span>
        </div>
    </div>

    <div class="tabla_wrapper tabla_wrapper_full tabla_scroll">
        <div class="admin_toolbar">
            <h2>Inventario de equipos</h2>
            <div class="admin_toolbar_acciones">
                <input type="text" id="filtro_pcs_admin" class="input_busqueda_admin"
                       placeholder="Buscar código, ubicación o usuario..."
                       onkeyup="filtrarTablaPCs(this.value)">
            </div>
        </div>

        <table class="tabla_admin" id="tabla_inventario_pcs">
        <thead>
        <tr>
            <th>Código</th>
            <th>Ubicación</th>
            <th>Estado físico</th>
            <th>Disponibilidad</th>
            <th>¿Quién lo tiene?</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($computadores)): ?>
        <tr><td colspan="5">Todavía no hay computadores registrados.</td></tr>
        <?php endif; ?>
        <?php foreach ($computadores as $c): ?>
        <tr>
            <td><strong><?= htmlspecialchars($c["codigo"]) ?></strong></td>
            <td><?= htmlspecialchars($c["ubicacion"]) ?></td>
            <td>
                <?php if ($c["estado"] === "operativo"): ?>
                    <span class="badge_dias badge_ok">Operativo</span>
                <?php else: ?>
                    <span class="badge_dias badge_vencido">Mantenimiento</span>
                <?php endif; ?>
            </td>
            <td><?= $c["disponible"] ? '<span class="badge_dias badge_ok">Disponible</span>' : '<span class="badge_dias badge_vencido">No disponible</span>' ?></td>
            <td>
                <?php if (!empty($c["id_reserva_activa"])): ?>
                    <div class="celda_usuario">
                        <i class="ti ti-user"></i>
                        <div>
                            <strong><?= htmlspecialchars($c["reserva_usuario"]) ?></strong>
                            <br><small class="celda_id">ID: <?= htmlspecialchars($c["reserva_id_usuario"]) ?> · desde <?= htmlspecialchars($c["reserva_fecha"]) ?></small>
                        </div>
                    </div>
                <?php elseif ($c["estado"] !== "operativo"): ?>
                    <span class="texto_cero">En mantenimiento</span>
                <?php else: ?>
                    <span class="badge_dias badge_ok">Libre</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        </table>
    </div>

    <hr class="divisor_seccion">

    <h2>Reservas de computadores</h2>
    <p class="admin_subtitle">Equipos reservados ahora mismo e historial de devoluciones.</p>

    <div class="tabs_admin">
        <button type="button" class="tab_btn tab_activo" data-tab="activos">
            Activos (<?= count($reservas_activas) ?>)
        </button>
        <button type="button" class="tab_btn" data-tab="historial">
            Historial (30 días) (<?= count($reservas_historial) ?>)
        </button>
    </div>

    <div class="tabla_wrapper tabla_wrapper_full tab_panel tabla_scroll" data-panel="activos">
        <table class="tabla_admin">
            <thead>
            <tr>
                <th>Código</th><th>Ubicación</th><th>Usuario que reservó</th><th>Rol</th><th>Reservado el</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($reservas_activas)): ?>
            <tr><td colspan="5">No hay computadores reservados en este momento.</td></tr>
            <?php endif; ?>
            <?php foreach ($reservas_activas as $r): ?>
            <tr>
                <td><strong><?= htmlspecialchars($r["codigo"]) ?></strong></td>
                <td><?= htmlspecialchars($r["ubicacion"]) ?></td>
                <td>
                    <strong><?= htmlspecialchars($r["usuario"] ?? "—") ?></strong>
                    <br><small class="celda_id">ID: <?= htmlspecialchars($r["id_usuario"]) ?></small>
                </td>
                <td><span class="badge_dias badge_ok"><?= htmlspecialchars($r["tipo_usuario"]) ?></span></td>
                <td><?= htmlspecialchars($r["fecha_reserva"]) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="tabla_wrapper tabla_wrapper_full tab_panel tab_panel_oculto tabla_scroll" data-panel="historial">
        <div style="padding: 10px 14px; margin-bottom: 12px; background: rgba(6,156,51,0.08); border-left: 4px solid var(--accent); border-radius: 6px; font-size: 13px; color: var(--dark);">
            <i class="ti ti-info-circle"></i> <strong>Historial de devoluciones (últimos 30 días):</strong> El sistema depura automáticamente los registros con más de 30 días de antigüedad.
        </div>
        <table class="tabla_admin">
            <thead>
            <tr>
                <th>Código</th><th>Ubicación</th><th>Usuario</th><th>Rol</th><th>Reservado el</th><th>Devuelto el</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($reservas_historial)): ?>
            <tr><td colspan="6">Todavía no hay devoluciones de computadores registradas.</td></tr>
            <?php endif; ?>
            <?php foreach ($reservas_historial as $r): ?>
            <tr>
                <td><strong><?= htmlspecialchars($r["codigo"]) ?></strong></td>
                <td><?= htmlspecialchars($r["ubicacion"]) ?></td>
                <td>
                    <strong><?= htmlspecialchars($r["usuario"] ?? "—") ?></strong>
                    <br><small class="celda_id">ID: <?= htmlspecialchars($r["id_usuario"]) ?></small>
                </td>
                <td><span class="badge_dias badge_ok"><?= htmlspecialchars($r["tipo_usuario"]) ?></span></td>
                <td><?= htmlspecialchars($r["fecha_reserva"]) ?></td>
                <td><?= htmlspecialchars($r["fecha_devolucion"] ?? "—") ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</main>

<!-- Modal: registrar computador -->
<div id="modal_pc" class="modal_overlay">
    <div class="modal_caja">
        <div class="modal_header">
            <h3><i class="ti ti-device-laptop"></i> Registrar computador</h3>
            <button type="button" class="btn_cerrar_modal" data-cerrar-pc>&times;</button>
        </div>
        <div class="modal_body">
            <form method="POST">
                <?= csrf_campo() ?>
                <div class="form_grid">
                    <div class="form_grupo">
                        <label>Código *</label>
                        <input name="codigo" placeholder="PC-01" required>
                    </div>
                    <div class="form_grupo">
                        <label>Ubicación</label>
                        <input name="ubicacion" placeholder="Sala de sistemas">
                    </div>
                    <div class="form_grupo form_grupo_full">
                        <label>Estado físico</label>
                        <select name="estado">
                            <option value="operativo">Operativo</option>
                            <option value="mantenimiento">En mantenimiento</option>
                        </select>
                    </div>
                    <div class="form_grupo form_grupo_check form_grupo_full">
                        <input type="checkbox" name="disponible" id="chk_pc_disp" checked>
                        <label for="chk_pc_disp">Disponible para reservar</label>
                    </div>
                </div>

                <div class="modal_acciones">
                    <button type="button" class="btn_secundario" data-cerrar-pc>Cancelar</button>
                    <button type="submit" name="crear" class="btn_nuevo_usuario">
                        <i class="ti ti-device-floppy"></i> Guardar computador
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php else: ?>

<main class="perfil_main">
    <section class="perfil_contenido perfil_contenido_top">

        <div class="perfil_col perfil_col_main">
            <div class="perfil_panel">
                <div class="panel_header">
                    <i class="ti ti-device-laptop"></i>
                    <h3>Computadores disponibles</h3>
                </div>

                <?php if ($mensaje): ?>
                    <p class="<?= $mensaje_tipo === "error" ? "mensaje_error" : "mensaje_ok" ?>"><?= htmlspecialchars($mensaje) ?></p>
                <?php endif; ?>

                <form method="POST">
                    <?= csrf_campo() ?>
                    <table class="tabla_admin">
                    <tr><th></th><th>Código</th><th>Ubicación</th></tr>
                    <?php foreach ($disponibles as $c): ?>
                    <tr>
                        <td><input type="radio" name="id_computador" value="<?= $c["id"] ?>" required></td>
                        <td><?= htmlspecialchars($c["codigo"]) ?></td>
                        <td><?= htmlspecialchars($c["ubicacion"]) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </table>
                    <button type="submit" name="reservar" class="btn_compacto">Reservar seleccionado</button>
                </form>
            </div>
        </div>

        <div class="perfil_col perfil_col_side">
            <div class="perfil_panel">
                <div class="panel_header">
                    <i class="ti ti-device-laptop"></i>
                    <h3>Mi Computador Reservado</h3>
                </div>

                <div class="seccion_activos">
                    <p class="subtitulo_panel">Reserva activa (<?= count($mis_reservas) ?>)</p>
                    <?php if (empty($mis_reservas)): ?>
                        <p class="texto_vacio">No tienes computadores reservados.</p>
                    <?php endif; ?>

                    <div class="favoritos_lista lista_scroll">
                        <?php foreach ($mis_reservas as $r): ?>
                        <div class="favorito_item">
                            <div class="fav_cover fav_cover_1"><i class="ti ti-device-laptop"></i></div>
                            <div class="fav_info">
                                <p class="fav_titulo"><?= htmlspecialchars($r["codigo"]) ?></p>
                                <p class="fav_autor"><?= htmlspecialchars($r["ubicacion"]) ?> · desde <?= htmlspecialchars($r["fecha_reserva"]) ?></p>
                            </div>
                        </div>
                        <form method="POST" class="form_inline">
                            <?= csrf_campo() ?>
                            <input type="hidden" name="reserva_id" value="<?= $r["id"] ?>">
                            <button type="submit" name="devolver">Devolver</button>
                        </form>
                        <?php endforeach; ?>
                    </div>
                </div>

                <hr class="divisor_panel">

                <button type="button" class="btn_toggle_historial" data-target="historial_computadores_drawer">
                    <span class="btn_toggle_info">
                        <i class="ti ti-history"></i>
                        <span>Historial de reservas (<?= count($historial_reservas) ?>)</span>
                    </span>
                    <i class="ti ti-chevron-down flecha_toggle"></i>
                </button>

                <div id="historial_computadores_drawer" class="historial_drawer drawer_cerrado">
                    <?php if (empty($historial_reservas)): ?>
                        <p class="texto_vacio">No tienes reservas anteriores.</p>
                    <?php else: ?>
                        <div class="favoritos_lista lista_scroll">
                            <?php foreach ($historial_reservas as $h): ?>
                            <div class="favorito_item">
                                <div class="fav_cover fav_cover_2"><i class="ti ti-check"></i></div>
                                <div class="fav_info">
                                    <p class="fav_titulo"><?= htmlspecialchars($h["codigo"]) ?></p>
                                    <p class="fav_autor" style="font-size: 12px; color: rgba(13,46,22,0.65);">
                                        <?= htmlspecialchars($h["ubicacion"]) ?><br>
                                        Reservado: <?= htmlspecialchars($h["fecha_reserva"]) ?><br>
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
<script>
function filtrarTablaPCs(query) {
    const q = query.toLowerCase();
    const filas = document.querySelectorAll("#tabla_inventario_pcs tbody tr");
    filas.forEach(f => {
        const texto = f.textContent.toLowerCase();
        f.style.display = texto.includes(q) ? "" : "none";
    });
}

(function() {
    const modal = document.getElementById("modal_pc");
    const abrir = document.getElementById("btn_abrir_modal_pc");
    if (!modal || !abrir) return;
    const cerrar = () => modal.classList.remove("modal_activo");
    abrir.addEventListener("click", () => modal.classList.add("modal_activo"));
    modal.querySelectorAll("[data-cerrar-pc]").forEach(b => b.addEventListener("click", cerrar));
    modal.addEventListener("click", e => { if (e.target === modal) cerrar(); });
    document.addEventListener("keydown", e => { if (e.key === "Escape") cerrar(); });
})();
</script>
</body>
</html>
