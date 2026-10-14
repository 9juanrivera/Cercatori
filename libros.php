<?php
session_start();
require "config.php";

if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "administrador") {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/includes/admin_foto.php";

$mensaje = "";
$mensaje_tipo = "ok";

// ─── Mensaje pendiente tras redirección ───
if (!empty($_SESSION["flash_libro"])) {
    $mensaje      = $_SESSION["flash_libro"]["texto"];
    $mensaje_tipo = $_SESSION["flash_libro"]["tipo"];
    unset($_SESSION["flash_libro"]);
}

function flash_libro($texto, $tipo = "ok") {
    $_SESSION["flash_libro"] = ["texto" => $texto, "tipo" => $tipo];
}

// ─── ACCIONES POST PROTEGIDAS CON CSRF ───
if (($_SERVER["REQUEST_METHOD"] ?? "") === "POST") {
    if (!validar_csrf()) {
        flash_libro("Petición rechazada por validación de seguridad (CSRF).", "error");
        header("Location: libros.php");
        exit;
    }

    // ─── CREATE / UPDATE ───
    if (isset($_POST["guardar_libro"])) {
        $isbn_original = trim($_POST["isbn_original"] ?? "");
    $isbn_nuevo    = trim($_POST["isbn"]);

    $campos = [
        $isbn_nuevo,
        trim($_POST["nombre"]),
        trim($_POST["autor"]),
        trim($_POST["editorial"]),
        $_POST["cantidad_paginas"] !== "" ? (int)$_POST["cantidad_paginas"] : null,
        isset($_POST["edicion_especial_mined"]) ? 1 : 0,
        trim($_POST["tipo"]),
        $_POST["anio_publicacion"] !== "" ? (int)$_POST["anio_publicacion"] : null,
        trim($_POST["ejemplar"]),
        trim($_POST["institucion"]),
        (int)$_POST["cantidad"],
        isset($_POST["disponibilidad"]) ? 1 : 0,
        trim($_POST["serie"]),
        trim($_POST["nota_general"]),
        trim($_POST["materia"])
    ];

    try {
        if ($isbn_original === "") {
            // CREATE
            $pdo->prepare("INSERT INTO libros
                (isbn, nombre, autor, editorial, cantidad_paginas, edicion_especial_mined,
                 tipo, anio_publicacion, ejemplar, institucion, cantidad, disponibilidad,
                 serie, nota_general, materia)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")->execute($campos);

            flash_libro("Libro registrado correctamente.");
        } else {
            // UPDATE
            $pdo->beginTransaction();

            $pdo->prepare("UPDATE libros SET
                isbn = ?, nombre = ?, autor = ?, editorial = ?, cantidad_paginas = ?,
                edicion_especial_mined = ?, tipo = ?, anio_publicacion = ?, ejemplar = ?,
                institucion = ?, cantidad = ?, disponibilidad = ?, serie = ?,
                nota_general = ?, materia = ?
                WHERE isbn = ?")->execute(array_merge($campos, [$isbn_original]));

            // Si cambió el ISBN, los préstamos deben seguir apuntando al libro correcto.
            if ($isbn_nuevo !== $isbn_original) {
                $pdo->prepare("UPDATE prestamos SET isbn = ? WHERE isbn = ?")
                    ->execute([$isbn_nuevo, $isbn_original]);
            }

            $pdo->commit();
            flash_libro("Libro actualizado correctamente.");
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $duplicado = strpos($e->getMessage(), "1062") !== false || strpos($e->getMessage(), "Duplicate") !== false;
        flash_libro($duplicado ? "Ya existe un libro con ese ISBN." : "No se pudo guardar el libro.", "error");
    }

    header("Location: libros.php?resaltar=" . urlencode($isbn_nuevo));
    exit;
}

// ─── DELETE ───
if (($_SERVER["REQUEST_METHOD"] ?? "") === "POST" && isset($_POST["eliminar_libro"])) {

    $isbn_borrar = trim($_POST["isbn_borrar"] ?? "");

    $chk = $pdo->prepare("SELECT COUNT(*) FROM prestamos WHERE isbn = ? AND devuelto = 0");
    $chk->execute([$isbn_borrar]);

    if ($chk->fetchColumn() > 0) {
        flash_libro("No puedes eliminar el libro: todavía tiene préstamos activos.", "error");
    } else {
        try {
            $pdo->prepare("DELETE FROM libros WHERE isbn = ?")->execute([$isbn_borrar]);
            flash_libro("Libro eliminado correctamente.");
        } catch (PDOException $e) {
            flash_libro("No se pudo eliminar el libro.", "error");
        }
    }

    header("Location: libros.php");
    exit;
}

// ─── CONFIRMAR DEVOLUCIÓN DE LIBRO (ADMIN) ───
if (($_SERVER["REQUEST_METHOD"] ?? "") === "POST" && isset($_POST["confirmar_devolucion"])) {
    $id_prestamo = (int)$_POST["id_prestamo"];
    $st = $pdo->prepare("SELECT isbn, devuelto FROM prestamos WHERE id = ?");
    $st->execute([$id_prestamo]);
    $pRow = $st->fetch(PDO::FETCH_ASSOC);

    if ($pRow && (int)$pRow["devuelto"] === 0) {
        $pdo->prepare("UPDATE prestamos SET devuelto = 1, fecha_devolucion = CURDATE(), solicitud_devolucion = 0 WHERE id = ?")->execute([$id_prestamo]);
        $pdo->prepare("UPDATE libros SET cantidad = cantidad + 1, disponibilidad = 1 WHERE isbn = ?")->execute([$pRow["isbn"]]);
        flash_libro("Devolución confirmada con éxito. El libro ha sido reintegrado al inventario.");
    }
    $tab_destino = $_POST["tab_retorno"] ?? "solicitudes";
    header("Location: libros.php?tab=" . urlencode($tab_destino));
    exit;
}

    // ─── RECHAZAR SOLICITUD DE DEVOLUCIÓN (ADMIN) ───
    if (isset($_POST["rechazar_devolucion"])) {
        $id_prestamo = (int)$_POST["id_prestamo"];
        $pdo->prepare("UPDATE prestamos SET solicitud_devolucion = 0 WHERE id = ?")->execute([$id_prestamo]);
        flash_libro("Solicitud de devolución rechazada. El libro permanece como préstamo activo.", "error");
        header("Location: libros.php?tab=solicitudes");
        exit;
    }
}

$resaltar_isbn = isset($_GET["resaltar"]) ? trim($_GET["resaltar"]) : "";

$libros = $pdo->query("
    SELECT l.*,
           COALESCE((SELECT COUNT(*) FROM prestamos p WHERE p.isbn = l.isbn AND p.devuelto = 0), 0) AS prestados_activos
    FROM libros l
    ORDER BY l.nombre ASC
")->fetchAll(PDO::FETCH_ASSOC);

// ── Préstamos activos, solicitudes pendientes e historial para el admin ──
$prestamos_libros = $pdo->query("
    SELECT p.id, p.isbn, COALESCE(l.nombre, 'Libro no catalogado') AS libro, p.tipo_usuario, p.id_usuario,
           p.fecha_prestamo, p.fecha_limite, p.devuelto, p.fecha_devolucion,
           COALESCE(p.solicitud_devolucion, 0) AS solicitud_devolucion, p.fecha_solicitud,
           DATEDIFF(p.fecha_limite, CURDATE()) AS dias_restantes,
           CASE
               WHEN p.tipo_usuario = 'estudiante' THEN COALESCE(NULLIF(TRIM((SELECT CONCAT_WS(' ', nombres, apellidos) FROM estudiantes WHERE numero_matricula = p.id_usuario)), ''), p.id_usuario)
               WHEN p.tipo_usuario = 'docente' THEN COALESCE(NULLIF(TRIM((SELECT CONCAT_WS(' ', nombre, apellidos) FROM docentes WHERE cedula = p.id_usuario)), ''), p.id_usuario)
               ELSE p.id_usuario
           END AS usuario,
           CASE
               WHEN p.tipo_usuario = 'estudiante' THEN (SELECT grado FROM estudiantes WHERE numero_matricula = p.id_usuario)
               ELSE NULL
           END AS grado_usuario
    FROM prestamos p
    LEFT JOIN libros l ON l.isbn = p.isbn
    ORDER BY p.devuelto ASC, p.fecha_prestamo DESC
")->fetchAll(PDO::FETCH_ASSOC);

$prestamos_activos      = array_filter($prestamos_libros, fn($p) => (int)$p["devuelto"] === 0);
$solicitudes_pendientes = array_filter($prestamos_libros, fn($p) => (int)$p["devuelto"] === 0 && (int)$p["solicitud_devolucion"] === 1);
$prestamos_historial    = array_filter($prestamos_libros, fn($p) => (int)$p["devuelto"] === 1);

$tab_solicitada = $_GET["tab"] ?? "";
if ($tab_solicitada !== "") {
    $tab_activa = $tab_solicitada;
} elseif (count($solicitudes_pendientes) > 0) {
    $tab_activa = "solicitudes";
} else {
    $tab_activa = "activos";
}

$total_copias = array_sum(array_column($libros, "cantidad"));

$active = "materias";
$admin_active = "libros";
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
    <title>Cercatori - Gestión de libros</title>
    <link rel="stylesheet" href="css/index.css">
</head>
<body>

<?php include "includes/header.php"; ?>
<?php include "includes/admin_nav.php"; ?>

<main class="admin_section">

    <div class="admin_encabezado">
        <div>
            <h1>Gestión de libros</h1>
            <p class="admin_subtitle">Registra ejemplares y consulta el inventario completo.</p>
        </div>
        <button type="button" class="btn_nuevo_usuario btn_grande" id="btn_abrir_modal_libro">
            <i class="ti ti-plus"></i> Agregar libro
        </button>
    </div>

    <?php if ($mensaje): ?>
        <p class="<?= $mensaje_tipo === "error" ? "mensaje_error" : "mensaje_ok" ?>"><?= htmlspecialchars($mensaje) ?></p>
    <?php endif; ?>

    <div class="admin_stats_row">
        <div class="stat_mini">
            <span class="stat_mini_num"><?= count($libros) ?></span>
            <span class="stat_mini_label">Títulos</span>
        </div>
        <div class="stat_mini">
            <span class="stat_mini_num"><?= (int)$total_copias ?></span>
            <span class="stat_mini_label">Copias en inventario</span>
        </div>
        <div class="stat_mini">
            <span class="stat_mini_num"><?= count($prestamos_activos) ?></span>
            <span class="stat_mini_label">Préstamos activos</span>
        </div>
    </div>

    <div class="tabla_wrapper tabla_wrapper_full tabla_scroll">
        <div class="admin_toolbar">
            <h2>Inventario</h2>
            <div class="admin_toolbar_acciones">
                <input type="text" id="filtro_libros_admin" class="input_busqueda_admin"
                       placeholder="Buscar libro, autor, ISBN o materia..."
                       onkeyup="filtrarTablaLibros(this.value)">
            </div>
        </div>

        <table class="tabla_admin" id="tabla_inventario_libros">
        <thead>
        <tr>
            <th class="col_acciones">Acciones</th>
            <th>ISBN</th><th>Nombre</th><th>Autor</th><th>Editorial</th>
            <th>Páginas</th><th>MINED</th><th>Tipo</th><th>Año</th>
            <th>Ejemplar</th><th>Institución</th><th>Disponibles</th><th>Prestados</th>
            <th>Disp.</th><th>Serie</th><th>Nota</th><th>Materia</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($libros)): ?>
        <tr><td colspan="17">Todavía no hay libros registrados.</td></tr>
        <?php endif; ?>
        <?php foreach ($libros as $l): ?>
        <tr id="libro-<?= htmlspecialchars($l['isbn']) ?>" class="<?= ($resaltar_isbn === $l['isbn']) ? 'fila_resaltada' : '' ?>">
            <td class="col_acciones">
                <div class="acciones_fila">
                    <button type="button" class="btn_accion btn_accion_editar btn_editar_libro"
                            title="Editar libro"
                            data-libro='<?= htmlspecialchars(json_encode($l, JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>'>
                        <i class="ti ti-pencil"></i>
                    </button>
                    <button type="button" class="btn_accion btn_accion_borrar btn_borrar_libro"
                            title="Eliminar libro"
                            data-isbn="<?= htmlspecialchars($l["isbn"]) ?>"
                            data-nombre="<?= htmlspecialchars($l["nombre"]) ?>"
                            data-prestados="<?= (int)$l["prestados_activos"] ?>">
                        <i class="ti ti-trash"></i>
                    </button>
                </div>
            </td>
            <td><strong><?= htmlspecialchars($l["isbn"]) ?></strong></td>
            <td>
                <?= htmlspecialchars($l["nombre"]) ?>
                <?php if ((int)$l["cantidad"] <= 0 || !$l["disponibilidad"]): ?>
                    <span class="badge_dias badge_vencido">Sin copias</span>
                <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($l["autor"]) ?></td>
            <td><?= htmlspecialchars($l["editorial"]) ?></td>
            <td><?= htmlspecialchars($l["cantidad_paginas"]) ?></td>
            <td><?= $l["edicion_especial_mined"] ? "Sí" : "No" ?></td>
            <td><?= htmlspecialchars($l["tipo"]) ?></td>
            <td><?= htmlspecialchars($l["anio_publicacion"]) ?></td>
            <td><?= htmlspecialchars($l["ejemplar"]) ?></td>
            <td><?= htmlspecialchars($l["institucion"]) ?></td>
            <td><strong><?= htmlspecialchars($l["cantidad"]) ?></strong></td>
            <td>
                <?php if ((int)$l["prestados_activos"] > 0): ?>
                    <span class="badge_dias badge_hoy" title="Copias en préstamo"><?= (int)$l["prestados_activos"] ?> prestado(s)</span>
                <?php else: ?>
                    <span class="texto_cero">0</span>
                <?php endif; ?>
            </td>
            <td><?= $l["disponibilidad"] ? '<span class="badge_dias badge_ok">Sí</span>' : '<span class="badge_dias badge_vencido">No</span>' ?></td>
            <td class="col_serie"><?= htmlspecialchars($l["serie"]) ?></td>
            <td class="col_nota" title="<?= htmlspecialchars($l["nota_general"]) ?>"><?= htmlspecialchars($l["nota_general"]) ?></td>
            <td><?= htmlspecialchars($l["materia"]) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        </table>
    </div>

    <hr class="divisor_seccion">

    <h2>Préstamos de libros</h2>
    <p class="admin_subtitle">Consulta qué hay prestado ahora mismo y el historial completo de devoluciones.</p>

    <div class="tabs_admin">
        <button type="button" class="tab_btn <?= $tab_activa === 'solicitudes' ? 'tab_activo' : '' ?>" data-tab="solicitudes">
            <i class="ti ti-clock"></i> Solicitudes pendientes (<?= count($solicitudes_pendientes) ?>)
        </button>
        <button type="button" class="tab_btn <?= $tab_activa === 'activos' ? 'tab_activo' : '' ?>" data-tab="activos">
            Activos (<?= count($prestamos_activos) ?>)
        </button>
        <button type="button" class="tab_btn <?= $tab_activa === 'historial' ? 'tab_activo' : '' ?>" data-tab="historial">
            Historial (30 días) (<?= count($prestamos_historial) ?>)
        </button>
    </div>

    <!-- PANEL 1: Solicitudes de devolución pendientes (Admin confirma entrega física) -->
    <div class="tabla_wrapper tabla_wrapper_full tab_panel <?= $tab_activa !== 'solicitudes' ? 'tab_panel_oculto' : '' ?> tabla_scroll" data-panel="solicitudes">
        <div style="padding: 10px 14px; margin-bottom: 12px; background: rgba(234, 88, 12, 0.08); border-left: 4px solid #ea580c; border-radius: 6px; font-size: 13px; color: var(--dark);">
            <i class="ti ti-shield-check"></i> <strong>Confirmación física requerida:</strong> Los estudiantes indicaron que desean devolver estos libros. Verifica la entrega física antes de presionar "Confirmar". Al confirmar, el inventario se incrementará automáticamente.
        </div>
        <table class="tabla_admin">
            <thead>
            <tr>
                <th>Libro</th><th>ISBN</th><th>Estudiante</th><th>Grado</th>
                <th>Prestado</th><th>Fecha Solicitud</th><th style="text-align: right;">Acciones</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($solicitudes_pendientes)): ?>
            <tr><td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-muted);">No hay solicitudes de devolución pendientes en este momento.</td></tr>
            <?php endif; ?>
            <?php foreach ($solicitudes_pendientes as $p): ?>
            <tr>
                <td><strong><?= htmlspecialchars($p["libro"]) ?></strong></td>
                <td><code><?= htmlspecialchars($p["isbn"]) ?></code></td>
                <td>
                    <strong><?= htmlspecialchars($p["usuario"] ?? "—") ?></strong>
                    <br><small class="celda_id">ID: <?= htmlspecialchars($p["id_usuario"]) ?></small>
                </td>
                <td><span class="badge_rol badge_rol_estudiante"><?= htmlspecialchars($p["grado_usuario"] ?? "Estudiante") ?></span></td>
                <td><?= htmlspecialchars($p["fecha_prestamo"]) ?></td>
                <td><span class="badge_dias badge_solicitud"><i class="ti ti-clock"></i> <?= htmlspecialchars($p["fecha_solicitud"] ?? 'Pendiente') ?></span></td>
                <td style="text-align: right;">
                    <div class="acciones_fila" style="justify-content: flex-end;">
                        <form method="POST" style="margin: 0; display: inline;">
                            <?= csrf_campo() ?>
                            <input type="hidden" name="confirmar_devolucion" value="1">
                            <input type="hidden" name="id_prestamo" value="<?= (int)$p["id"] ?>">
                            <input type="hidden" name="tab_retorno" value="solicitudes">
                            <button type="submit" class="btn_accion btn_accion_editar" title="Confirmar devolución en biblioteca">
                                <i class="ti ti-check"></i> Confirmar
                            </button>
                        </form>
                        <form method="POST" style="margin: 0; display: inline;" onsubmit="return confirm('¿Rechazar solicitud? El estudiante seguirá figurando con el libro en préstamo.');">
                            <?= csrf_campo() ?>
                            <input type="hidden" name="rechazar_devolucion" value="1">
                            <input type="hidden" name="id_prestamo" value="<?= (int)$p["id"] ?>">
                            <button type="submit" class="btn_accion btn_accion_borrar" title="Rechazar si no trajo el libro">
                                <i class="ti ti-x"></i> Rechazar
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- PANEL 2: Préstamos activos generales -->
    <div class="tabla_wrapper tabla_wrapper_full tab_panel <?= $tab_activa !== 'activos' ? 'tab_panel_oculto' : '' ?> tabla_scroll" data-panel="activos">
        <table class="tabla_admin">
            <thead>
            <tr>
                <th>Libro</th><th>ISBN</th><th>Usuario</th><th>Rol</th>
                <th>Prestado</th><th>Vence</th><th>Días restantes</th><th>Estado</th>
                <th style="text-align: right;">Acción</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($prestamos_activos)): ?>
            <tr><td colspan="9">No hay préstamos activos en este momento.</td></tr>
            <?php endif; ?>
            <?php foreach ($prestamos_activos as $p): ?>
            <tr>
                <td><strong><?= htmlspecialchars($p["libro"]) ?></strong></td>
                <td><?= htmlspecialchars($p["isbn"]) ?></td>
                <td>
                    <strong><?= htmlspecialchars($p["usuario"] ?? "—") ?></strong>
                    <br><small class="celda_id">ID: <?= htmlspecialchars($p["id_usuario"]) ?></small>
                </td>
                <td><?= htmlspecialchars($p["tipo_usuario"]) ?></td>
                <td><?= htmlspecialchars($p["fecha_prestamo"]) ?></td>
                <td><?= htmlspecialchars($p["fecha_limite"]) ?></td>
                <td>
                    <?php if ($p["dias_restantes"] !== null && $p["dias_restantes"] < 0): ?>
                        <strong class="texto_atraso"><?= abs((int)$p["dias_restantes"]) ?> día(s) de atraso</strong>
                    <?php elseif ($p["dias_restantes"] !== null && (int)$p["dias_restantes"] === 0): ?>
                        <strong class="texto_hoy">Entrega hoy</strong>
                    <?php else: ?>
                        Faltan <?= (int)$p["dias_restantes"] ?> día(s)
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($p["solicitud_devolucion"])): ?>
                        <span class="badge_dias badge_solicitud"><i class="ti ti-clock"></i> Devolución solicitada</span>
                    <?php elseif ($p["dias_restantes"] !== null && $p["dias_restantes"] < 0): ?>
                        <span class="badge_dias badge_vencido">Vencido</span>
                    <?php elseif ($p["dias_restantes"] !== null && $p["dias_restantes"] <= 2): ?>
                        <span class="badge_dias badge_hoy">Por vencer</span>
                    <?php else: ?>
                        <span class="badge_dias badge_ok">Al día</span>
                    <?php endif; ?>
                </td>
                <td style="text-align: right;">
                    <form method="POST" style="margin: 0; display: inline;">
                        <?= csrf_campo() ?>
                        <input type="hidden" name="confirmar_devolucion" value="1">
                        <input type="hidden" name="id_prestamo" value="<?= (int)$p["id"] ?>">
                        <input type="hidden" name="tab_retorno" value="activos">
                        <button type="submit" class="btn_accion btn_accion_editar" title="Registrar o confirmar devolución">
                            <i class="ti ti-arrow-back-up"></i> Devolver
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- PANEL 3: Historial con aviso de 30 días -->
    <div class="tabla_wrapper tabla_wrapper_full tab_panel <?= $tab_activa !== 'historial' ? 'tab_panel_oculto' : '' ?> tabla_scroll" data-panel="historial">
        <div style="padding: 10px 14px; margin-bottom: 12px; background: rgba(6,156,51,0.08); border-left: 4px solid var(--accent); border-radius: 6px; font-size: 13px; color: var(--dark);">
            <i class="ti ti-info-circle"></i> <strong>Historial de devoluciones (últimos 30 días):</strong> El sistema depura automáticamente los registros devueltos hace más de 30 días para mantener el sistema ligero y ordenado.
        </div>
        <table class="tabla_admin">
            <thead>
            <tr>
                <th>Libro</th><th>ISBN</th><th>Usuario</th><th>Rol</th>
                <th>Prestado</th><th>Devuelto</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($prestamos_historial)): ?>
            <tr><td colspan="6">Todavía no hay devoluciones registradas en los últimos 30 días.</td></tr>
            <?php endif; ?>
            <?php foreach ($prestamos_historial as $p): ?>
            <tr>
                <td><strong><?= htmlspecialchars($p["libro"]) ?></strong></td>
                <td><?= htmlspecialchars($p["isbn"]) ?></td>
                <td>
                    <strong><?= htmlspecialchars($p["usuario"] ?? "—") ?></strong>
                    <br><small class="celda_id">ID: <?= htmlspecialchars($p["id_usuario"]) ?></small>
                </td>
                <td><?= htmlspecialchars($p["tipo_usuario"]) ?></td>
                <td><?= htmlspecialchars($p["fecha_prestamo"]) ?></td>
                <td><?= htmlspecialchars($p["fecha_devolucion"]) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</main>

<!-- Modal: registrar / editar libro -->
<div id="modal_libro" class="modal_overlay">
    <div class="modal_caja modal_caja_ancha">
        <div class="modal_header">
            <h3><i class="ti ti-book-2"></i> <span id="titulo_modal_libro">Registrar libro</span></h3>
            <button type="button" class="btn_cerrar_modal" data-cerrar-libro>&times;</button>
        </div>
        <div class="modal_body">
            <form method="POST" id="form_libro">
                <?= csrf_campo() ?>
                <input type="hidden" name="guardar_libro" value="1">
                <input type="hidden" name="isbn_original" id="isbn_original" value="">

                <p class="form_seccion_titulo">Identificación</p>
                <div class="form_grid">
                    <div class="form_grupo">
                        <label>ISBN *</label>
                        <input name="isbn" required placeholder="978-...">
                    </div>
                    <div class="form_grupo">
                        <label>Nombre *</label>
                        <input name="nombre" required placeholder="Título del libro">
                    </div>
                    <div class="form_grupo">
                        <label>Autor *</label>
                        <input name="autor" required>
                    </div>
                    <div class="form_grupo">
                        <label>Editorial</label>
                        <input name="editorial">
                    </div>
                </div>

                <p class="form_seccion_titulo">Datos de publicación</p>
                <div class="form_grid">
                    <div class="form_grupo">
                        <label>Tipo</label>
                        <input name="tipo" placeholder="Texto, novela, consulta...">
                    </div>
                    <div class="form_grupo">
                        <label>Año de publicación</label>
                        <input type="number" name="anio_publicacion" min="1400" max="2100">
                    </div>
                    <div class="form_grupo">
                        <label>Páginas</label>
                        <input type="number" name="cantidad_paginas" min="1">
                    </div>
                    <div class="form_grupo">
                        <label>Serie</label>
                        <input name="serie">
                    </div>
                    <div class="form_grupo">
                        <label>Ejemplar</label>
                        <input name="ejemplar">
                    </div>
                    <div class="form_grupo">
                        <label>Institución</label>
                        <input name="institucion">
                    </div>
                </div>

                <p class="form_seccion_titulo">Inventario y clasificación</p>
                <div class="form_grid">
                    <div class="form_grupo">
                        <label>Cantidad</label>
                        <input type="number" name="cantidad" value="1" min="0">
                    </div>
                    <div class="form_grupo">
                        <label>Materia</label>
                        <input name="materia">
                    </div>
                    <div class="form_grupo form_grupo_full">
                        <label>Nota general</label>
                        <textarea name="nota_general" rows="3" placeholder="Observaciones sobre el ejemplar..."></textarea>
                    </div>
                    <div class="form_grupo form_grupo_check">
                        <input type="checkbox" name="disponibilidad" id="chk_disp" checked>
                        <label for="chk_disp">Disponible para préstamo</label>
                    </div>
                    <div class="form_grupo form_grupo_check">
                        <input type="checkbox" name="edicion_especial_mined" id="chk_mined">
                        <label for="chk_mined">Edición especial MINED</label>
                    </div>
                </div>

                <div class="modal_acciones">
                    <button type="button" class="btn_secundario" data-cerrar-libro>Cancelar</button>
                    <button type="submit" name="guardar_libro" class="btn_nuevo_usuario">
                        <i class="ti ti-device-floppy"></i> <span id="texto_btn_libro">Guardar libro</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: confirmar eliminación -->
<div id="modal_borrar_libro" class="modal_overlay">
    <div class="modal_caja modal_caja_angosta">
        <div class="modal_header">
            <h3><i class="ti ti-alert-triangle"></i> Eliminar libro</h3>
            <button type="button" class="btn_cerrar_modal" data-cerrar-borrar>&times;</button>
        </div>
        <div class="modal_body">
            <p class="texto_confirmacion">
                Vas a eliminar <strong id="nombre_libro_borrar"></strong> del inventario.
                Esta acción no se puede deshacer.
            </p>
            <form method="POST">
                <?= csrf_campo() ?>
                <input type="hidden" name="isbn_borrar" id="isbn_libro_borrar" value="">
                <div class="modal_acciones">
                    <button type="button" class="btn_secundario" data-cerrar-borrar>Cancelar</button>
                    <button type="submit" name="eliminar_libro" class="btn_peligro">
                        <i class="ti ti-trash"></i> Sí, eliminar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="js/index.js"></script>
<script>
function filtrarTablaLibros(query) {
    const q = query.toLowerCase();
    document.querySelectorAll("#tabla_inventario_libros tbody tr").forEach(f => {
        f.style.display = f.textContent.toLowerCase().includes(q) ? "" : "none";
    });
}

(function() {
    const modal   = document.getElementById("modal_libro");
    const form    = document.getElementById("form_libro");
    const abrir   = document.getElementById("btn_abrir_modal_libro");
    const titulo  = document.getElementById("titulo_modal_libro");
    const txtBtn  = document.getElementById("texto_btn_libro");
    const original= document.getElementById("isbn_original");

    const modalBorrar = document.getElementById("modal_borrar_libro");
    const nombreBorrar= document.getElementById("nombre_libro_borrar");
    const isbnBorrar  = document.getElementById("isbn_libro_borrar");

    if (!modal) return;

    const cerrar = () => {
        modal.classList.remove("modal_activo");
        modalBorrar.classList.remove("modal_activo");
    };

    function setCampo(nombre, valor) {
        const el = form.elements[nombre];
        if (!el) return;
        if (el.type === "checkbox") {
            el.checked = valor == 1;
        } else {
            el.value = (valor === null || valor === undefined) ? "" : valor;
        }
    }

    // ── Crear ──
    abrir.addEventListener("click", function() {
        form.reset();
        original.value = "";
        titulo.textContent = "Registrar libro";
        txtBtn.textContent = "Guardar libro";
        form.elements["cantidad"].value = 1;
        form.elements["disponibilidad"].checked = true;
        form.elements["edicion_especial_mined"].checked = false;
        modal.classList.add("modal_activo");
    });

    // ── Editar ──
    document.querySelectorAll(".btn_editar_libro").forEach(btn => {
        btn.addEventListener("click", function() {
            const l = JSON.parse(this.dataset.libro);
            original.value = l.isbn;
            titulo.textContent = "Editar libro";
            txtBtn.textContent = "Guardar cambios";

            ["isbn","nombre","autor","editorial","cantidad_paginas","tipo",
             "anio_publicacion","ejemplar","institucion","cantidad","serie",
             "nota_general","materia","disponibilidad","edicion_especial_mined"]
                .forEach(c => setCampo(c, l[c]));

            modal.classList.add("modal_activo");
        });
    });

    // ── Eliminar ──
    document.querySelectorAll(".btn_borrar_libro").forEach(btn => {
        btn.addEventListener("click", function() {
            if (parseInt(this.dataset.prestados, 10) > 0) {
                alert("Este libro tiene préstamos activos. Espera a que lo devuelvan para poder eliminarlo.");
                return;
            }
            nombreBorrar.textContent = this.dataset.nombre;
            isbnBorrar.value = this.dataset.isbn;
            modalBorrar.classList.add("modal_activo");
        });
    });

    [modal, modalBorrar].forEach(m => {
        m.querySelectorAll("[data-cerrar-libro], [data-cerrar-borrar]").forEach(b => b.addEventListener("click", cerrar));
        m.addEventListener("click", e => { if (e.target === m) cerrar(); });
    });
    document.addEventListener("keydown", e => { if (e.key === "Escape") cerrar(); });
})();

<?php if (!empty($resaltar_isbn)): ?>
document.addEventListener("DOMContentLoaded", function() {
    const fila = document.getElementById("libro-<?= htmlspecialchars($resaltar_isbn) ?>");
    if (fila) fila.scrollIntoView({ behavior: "smooth", block: "center" });
});
<?php endif; ?>
</script>

<?php include "includes/footer.php"; ?>

</body>
</html>
