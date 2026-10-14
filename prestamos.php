<?php
session_start();
require "config.php";

if (!isset($_SESSION["rol"]) || !in_array($_SESSION["rol"], ["estudiante", "docente"])) {
    header("Location: login.php");
    exit;
}

$rol = $_SESSION["rol"];
$id_sesion = $_SESSION["id"];
$mensaje = "";
$mensaje_tipo = "ok";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!validar_csrf()) {
        $mensaje = "Petición no válida o expirada (CSRF). Por favor recarga la página.";
        $mensaje_tipo = "error";
    } else {
        if (isset($_POST["prestar"])) {
            $isbn = $_POST["isbn"] ?? "";
            $cantidad_pedida = 1;
    if ($rol === "docente") {
        if (!empty($isbn) && isset($_POST["cantidades"][$isbn])) {
            $cantidad_pedida = max(1, (int)$_POST["cantidades"][$isbn]);
        } elseif (isset($_POST["cantidad"])) {
            $cantidad_pedida = max(1, (int)$_POST["cantidad"]);
        }
    }

    if ($rol === "estudiante") {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM prestamos WHERE id_usuario = ? AND tipo_usuario = 'estudiante' AND devuelto = 0");
        $stmt->execute([$id_sesion]);
        if ($stmt->fetchColumn() >= 5) {
            $mensaje = "Ya tienes 5 libros prestados. Devuelve alguno antes de pedir otro.";
            $mensaje_tipo = "error";
        }
    }

    if ($mensaje === "") {
        $stmt = $pdo->prepare("SELECT cantidad, disponibilidad, nombre FROM libros WHERE isbn = ?");
        $stmt->execute([$isbn]);
        $libro = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$libro) {
            $mensaje = "El libro seleccionado no existe.";
            $mensaje_tipo = "error";
        } elseif (!$libro["disponibilidad"] || (int)$libro["cantidad"] < $cantidad_pedida) {
            $disp = (int)($libro["cantidad"] ?? 0);
            $mensaje = "No hay suficientes ejemplares disponibles. Solicitaste $cantidad_pedida ejemplar(es) pero solo hay $disp disponibles.";
            $mensaje_tipo = "error";
        } else {
            $stmt = $pdo->prepare("INSERT INTO prestamos (isbn, id_usuario, tipo_usuario, fecha_prestamo, fecha_limite, devuelto, solicitud_devolucion) VALUES (?,?,?,CURDATE(),DATE_ADD(CURDATE(), INTERVAL 8 DAY),0,0)");
            for ($i = 0; $i < $cantidad_pedida; $i++) {
                $stmt->execute([$isbn, $id_sesion, $rol]);
            }

            // Descontar del inventario y actualizar disponibilidad
            $upd = $pdo->prepare("UPDATE libros SET cantidad = GREATEST(0, cantidad - ?), disponibilidad = (CASE WHEN cantidad - ? <= 0 THEN 0 ELSE 1 END) WHERE isbn = ?");
            $upd->execute([$cantidad_pedida, $cantidad_pedida, $isbn]);

            $mensaje = "Préstamo registrado exitosamente: $cantidad_pedida ejemplar(es) de \"{$libro['nombre']}\".";
            $mensaje_tipo = "ok";
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["devolver"])) {
    $id = (int)$_POST["prestamo_id"];
    $check = $pdo->prepare("SELECT isbn FROM prestamos WHERE id = ? AND devuelto = 0 AND id_usuario = ?");
    $check->execute([$id, $id_sesion]);
    $isbn = $check->fetchColumn();

    if ($isbn) {
        if ($rol === "estudiante") {
            $stmt = $pdo->prepare("UPDATE prestamos SET solicitud_devolucion = 1, fecha_solicitud = NOW() WHERE id = ?");
            $stmt->execute([$id]);
            $mensaje = "Solicitud de devolución registrada. El libro quedará como devuelto una vez que el administrador confirme la entrega física en la biblioteca.";
            $mensaje_tipo = "ok";
        } else {
            $stmt = $pdo->prepare("UPDATE prestamos SET devuelto = 1, fecha_devolucion = CURDATE(), solicitud_devolucion = 0 WHERE id = ?");
            $stmt->execute([$id]);

            $upd = $pdo->prepare("UPDATE libros SET cantidad = cantidad + 1, disponibilidad = 1 WHERE isbn = ?");
            $upd->execute([$isbn]);

            $mensaje = "Devolución registrada con éxito.";
            $mensaje_tipo = "ok";
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["devolver_lote"])) {
    $isbn_lote = $_POST["isbn_lote"];
    $cant_lote = max(1, (int)$_POST["cantidad_lote"]);

    $stmt = $pdo->prepare("SELECT id FROM prestamos WHERE id_usuario = ? AND tipo_usuario = ? AND isbn = ? AND devuelto = 0 ORDER BY fecha_prestamo ASC LIMIT " . (int)$cant_lote);
    $stmt->execute([$id_sesion, $rol, $isbn_lote]);
    $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($ids)) {
        $inQuery = implode(',', array_fill(0, count($ids), '?'));
        $cant_real = count($ids);

        if ($rol === "estudiante") {
            $upd = $pdo->prepare("UPDATE prestamos SET solicitud_devolucion = 1, fecha_solicitud = NOW() WHERE id IN ($inQuery)");
            $upd->execute($ids);
            $mensaje = "Solicitud de devolución enviada para $cant_real ejemplar(es). Tu devolución quedará confirmada cuando el administrador verifique la entrega física del libro.";
            $mensaje_tipo = "ok";
        } else {
            $upd = $pdo->prepare("UPDATE prestamos SET devuelto = 1, fecha_devolucion = CURDATE(), solicitud_devolucion = 0 WHERE id IN ($inQuery)");
            $upd->execute($ids);

            $updLibro = $pdo->prepare("UPDATE libros SET cantidad = cantidad + ?, disponibilidad = 1 WHERE isbn = ?");
            $updLibro->execute([$cant_real, $isbn_lote]);

            $mensaje = "$cant_real ejemplar(es) devuelto(s) con éxito.";
            $mensaje_tipo = "ok";
        }
    }
}

// pa subi la foto o actualizarla si activo esta vuelta elimina la anterior y la reemplaza por la nueva
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["subir_foto"])) {
    if (isset($_FILES["foto"]) && $_FILES["foto"]["error"] === UPLOAD_ERR_OK) {
        $file_tmp  = $_FILES["foto"]["tmp_name"];
        $file_size = $_FILES["foto"]["size"];
        $file_name = $_FILES["foto"]["name"];
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $permitidas = ["jpg", "jpeg", "png", "webp", "gif"];
        $mimes_permitidos = ["image/jpeg", "image/png", "image/webp", "image/gif"];

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file_tmp);
        finfo_close($finfo);

        if (!in_array($ext, $permitidas) || !in_array($mime, $mimes_permitidos)) {
            $mensaje = "Formato de imagen no permitido. Usa JPG, PNG, WEBP o GIF.";
            $mensaje_tipo = "error";
        } elseif ($file_size > 3 * 1024 * 1024) {
            $mensaje = "La imagen supera el límite permitido de 3MB.";
            $mensaje_tipo = "error";
        } else {
            $directorio = __DIR__ . "/img/perfiles/";
            if (!is_dir($directorio)) {
                @mkdir($directorio, 0777, true);
            }

            // busca el usuario mira si tiene foto pues la borra y pone la nueva
            $tabla_usr = $rol === "estudiante" ? "estudiantes" : "docentes";
            $campo_id  = $rol === "estudiante" ? "numero_matricula" : "cedula";
            $stmt_old = $pdo->prepare("SELECT foto_perfil FROM $tabla_usr WHERE $campo_id = ?");
            $stmt_old->execute([$id_sesion]);
            $foto_anterior = $stmt_old->fetchColumn();

            $nuevo_archivo = "perfil_" . $rol . "_" . preg_replace('/[^a-zA-Z0-9_-]/', '', $id_sesion) . "_" . time() . "." . $ext;
            $destino = $directorio . $nuevo_archivo;

            if (move_uploaded_file($file_tmp, $destino)) {
                if ($foto_anterior && file_exists($directorio . $foto_anterior)) {
                    @unlink($directorio . $foto_anterior);
                }
                $upd_foto = $pdo->prepare("UPDATE $tabla_usr SET foto_perfil = ? WHERE $campo_id = ?");
                $upd_foto->execute([$nuevo_archivo, $id_sesion]);

                $mensaje = "Foto de perfil actualizada con éxito.";
                $mensaje_tipo = "ok";
            } else {
                $mensaje = "Hubo un error al guardar la imagen. Intenta de nuevo.";
                $mensaje_tipo = "error";
            }
        }
    } else {
        $mensaje = "Por favor selecciona un archivo de imagen válido.";
        $mensaje_tipo = "error";
    }
}

// si va a borrar la foto solo busca el estudiante y elimina los datos de esa lnea
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["eliminar_foto"])) {
    $tabla_usr = $rol === "estudiante" ? "estudiantes" : "docentes";
    $campo_id  = $rol === "estudiante" ? "numero_matricula" : "cedula";

    $stmt_old = $pdo->prepare("SELECT foto_perfil FROM $tabla_usr WHERE $campo_id = ?");
    $stmt_old->execute([$id_sesion]);
    $foto_anterior = $stmt_old->fetchColumn();

    if ($foto_anterior && file_exists(__DIR__ . "/img/perfiles/" . $foto_anterior)) {
        @unlink(__DIR__ . "/img/perfiles/" . $foto_anterior);
    }
    $upd_foto = $pdo->prepare("UPDATE $tabla_usr SET foto_perfil = NULL WHERE $campo_id = ?");
    $upd_foto->execute([$id_sesion]);

    $mensaje = "Foto de perfil eliminada correctamente.";
    $mensaje_tipo = "ok";
}

        // cambiar la contraseña si me la se valida que sea la misma y me permite cambiarla 
        if (isset($_POST["cambiar_clave"])) {
            $clave_actual    = $_POST["clave_actual"] ?? "";
            $nueva_clave     = $_POST["nueva_clave"] ?? "";
            $confirmar_clave = $_POST["confirmar_clave"] ?? "";

            if (empty($clave_actual) || empty($nueva_clave)) {
                $mensaje = "Todos los campos de contraseña son obligatorios.";
                $mensaje_tipo = "error";
            } elseif ($nueva_clave !== $confirmar_clave) {
                $mensaje = "Las nuevas contraseñas no coinciden.";
                $mensaje_tipo = "error";
            } elseif (strlen($nueva_clave) < 4) {
                $mensaje = "La nueva contraseña debe tener al menos 4 caracteres.";
                $mensaje_tipo = "error";
            } else {
                $tabla_usr = $rol === "estudiante" ? "estudiantes" : "docentes";
                $campo_id  = $rol === "estudiante" ? "numero_matricula" : "cedula";

                $stmt_chk = $pdo->prepare("SELECT clave FROM $tabla_usr WHERE $campo_id = ?");
                $stmt_chk->execute([$id_sesion]);
                $clave_guardada = $stmt_chk->fetchColumn();

                if (!verificar_y_migrar_clave($clave_actual, $clave_guardada, $pdo, $tabla_usr, $campo_id, $id_sesion)) {
                    $mensaje = "La contraseña actual no es correcta.";
                    $mensaje_tipo = "error";
                } else {
                    $hash_nueva = password_hash($nueva_clave, PASSWORD_DEFAULT);
                    $upd_pwd = $pdo->prepare("UPDATE $tabla_usr SET clave = ? WHERE $campo_id = ?");
                    $upd_pwd->execute([$hash_nueva, $id_sesion]);

                    $mensaje = "¡Contraseña actualizada con éxito!";
                    $mensaje_tipo = "ok";
                }
            }
        }
    }
}

//  esto es buscar la informacion de la persona que lo este usando para los datos 
$tabla_usr = $rol === "estudiante" ? "estudiantes" : "docentes";
$campo_id  = $rol === "estudiante" ? "numero_matricula" : "cedula";
$stmt_usr = $pdo->prepare("SELECT * FROM $tabla_usr WHERE $campo_id = ?");
$stmt_usr->execute([$id_sesion]);
$usuario_actual = $stmt_usr->fetch(PDO::FETCH_ASSOC);

$foto_actual = null;
if (!empty($usuario_actual["foto_perfil"]) && file_exists(__DIR__ . "/img/perfiles/" . $usuario_actual["foto_perfil"])) {
    $foto_actual = "img/perfiles/" . $usuario_actual["foto_perfil"];
}
$pagina = isset($_GET["pagina"]) ? max(1, (int)$_GET["pagina"]) : 1;
$por_pagina = 5;
$offset = ($pagina - 1) * $por_pagina;

$busqueda = isset($_GET["q"]) ? trim($_GET["q"]) : "";

$where = "disponibilidad = TRUE AND cantidad > 0";
$params = [];
if ($busqueda !== "") {
    $where .= " AND (nombre LIKE ? OR autor LIKE ? OR materia LIKE ?)";
    $like = "%$busqueda%";
    $params = [$like, $like, $like];
}

$total = $pdo->prepare("SELECT COUNT(*) FROM libros WHERE $where");
$total->execute($params);
$total_libros = $total->fetchColumn();
$total_paginas = max(1, ceil($total_libros / $por_pagina));

$stmt = $pdo->prepare("SELECT isbn, nombre, autor, materia, cantidad FROM libros WHERE $where ORDER BY nombre LIMIT $por_pagina OFFSET $offset");
$stmt->execute($params);
$libros_disponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);

$materias_filtro = $pdo->query("SELECT DISTINCT materia FROM libros WHERE materia IS NOT NULL AND TRIM(materia) != '' ORDER BY materia")->fetchAll(PDO::FETCH_COLUMN);

// le pregunta a la base de datos si tiene prestamos 
$stmt = $pdo->prepare("
    SELECT l.isbn, l.nombre, COUNT(*) AS cantidad, MIN(p.fecha_prestamo) AS primera_fecha, MIN(p.fecha_limite) AS fecha_limite, DATEDIFF(MIN(p.fecha_limite), CURDATE()) AS dias_restantes,
           MAX(p.solicitud_devolucion) AS solicitud_pendiente
    FROM prestamos p
    JOIN libros l ON l.isbn = p.isbn
    WHERE p.devuelto = 0 AND p.id_usuario = ? AND p.tipo_usuario = ?
    GROUP BY l.isbn, l.nombre
");
$stmt->execute([$id_sesion, $rol]);
$prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// pa guardar lo que prestan
$stmt_historial = $pdo->prepare("
    SELECT p.id, p.isbn, COALESCE(l.nombre, 'Libro no catalogado') AS nombre, p.fecha_prestamo, p.fecha_devolucion
    FROM prestamos p
    LEFT JOIN libros l ON l.isbn = p.isbn
    WHERE p.devuelto = 1 AND p.id_usuario = ? AND p.tipo_usuario = ?
    ORDER BY p.fecha_devolucion DESC, p.id DESC
    LIMIT 50
");
$stmt_historial->execute([$id_sesion, $rol]);
$historial_usuario = $stmt_historial->fetchAll(PDO::FETCH_ASSOC);

// aviso pa los que no han devuelto los cosos
$hay_vencido = false;
$hay_por_vencer = false;
foreach ($prestamos as $p) {
    if ($p["dias_restantes"] !== null) {
        if ($p["dias_restantes"] < 0) $hay_vencido = true;
        elseif ($p["dias_restantes"] <= 2) $hay_por_vencer = true;
    }
}

$active = "espacio";
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
    <title>Cercatori - Mi Espacio</title>
    <link rel="stylesheet" href="css/index.css">
</head>
<body>

<?php include "includes/header.php"; ?>

<div class="top_bar_sesion">
    <span>Bienvenido, <?= htmlspecialchars($_SESSION["nombre"]) ?> (<?= htmlspecialchars($rol) ?>)</span>
    <a href="logout.php">Cerrar sesión</a>
</div>

<?php if ($hay_vencido): ?>
<div class="alerta_vencimiento alerta_vencido">
    <i class="ti ti-alert-triangle"></i>
    Tienes libros vencidos. Devuélvelos lo antes posible.
</div>
<?php elseif ($hay_por_vencer): ?>
<div class="alerta_vencimiento alerta_por_vencer">
    <i class="ti ti-clock-exclamation"></i>
    Tienes un préstamo por vencer en los próximos 2 días.
</div>
<?php endif; ?>

<main class="perfil_main">

    <?php if ($mensaje): ?>
        <div style="max-width: 1000px; margin: 0 auto 1.5rem auto;">
            <p class="<?= $mensaje_tipo === "error" ? "mensaje_error" : "mensaje_ok" ?>" style="border-radius: 12px; font-weight: 500; font-size: 14px;"><?= htmlspecialchars($mensaje) ?></p>
        </div>
    <?php endif; ?>

    <section class="perfil_banner">
        <div class="perfil_banner_inner">
            <div class="perfil_avatar_wrap">
                <?php if ($foto_actual): ?>
                    <img src="<?= htmlspecialchars($foto_actual) ?>" alt="Avatar" class="perfil_avatar_img">
                <?php else: ?>
                    <div class="perfil_avatar"><i class="ti ti-user"></i></div>
                <?php endif; ?>
                <button type="button" class="btn_editar_avatar" id="btn_abrir_modal_avatar" title="Cambiar foto de perfil">
                    <i class="ti ti-camera"></i>
                </button>
            </div>
            <h2 class="perfil_nombre"><?= htmlspecialchars(strtoupper($_SESSION["nombre"])) ?></h2>
            <p class="perfil_rol"><?= $rol === "docente" ? "Docente" : "Estudiante" ?> <?= !empty($usuario_actual["grado"]) ? "• Grado " . htmlspecialchars($usuario_actual["grado"]) : "" ?></p>

            <button type="button" class="btn_config_perfil" id="btn_abrir_config_perfil">
                <i class="ti ti-settings"></i> Configuración de Cuenta
            </button>

            <div class="perfil_stats">
                <div class="stat_item">
                    <span class="stat_num"><?= count($prestamos) ?></span>
                    <span class="stat_label">Libros distintos</span>
                </div>
                <div class="stat_divider"></div>
                <div class="stat_item">
                    <span class="stat_num"><?= array_sum(array_column($prestamos, "cantidad")) ?></span>
                    <span class="stat_label">En préstamo</span>
                </div>
            </div>
        </div>
    </section>

    <section class="perfil_contenido">

        <div class="perfil_col perfil_col_main">
            <div class="perfil_panel">
                <div class="panel_header">
                    <i class="ri-book-2-line"></i>
                    <h3>Buscar libros disponibles</h3>
                </div>

                <?php if ($mensaje): ?>
                    <p class="<?= $mensaje_tipo === "error" ? "mensaje_error" : "mensaje_ok" ?>"><?= htmlspecialchars($mensaje) ?></p>
                <?php endif; ?>

                <form method="GET" class="buscador_form" style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <input type="text" name="q" placeholder="Nombre, autor, ISBN o materia" value="<?= htmlspecialchars($busqueda) ?>" style="flex: 1; min-width: 180px;">
                    <select class="select_filtro_categoria" onchange="if(this.value !== '') { this.form.q.value = this.value; this.form.submit(); }">
                        <option value="">-- Filtrar por categoría --</option>
                        <?php foreach ($materias_filtro as $mat): ?>
                            <option value="<?= htmlspecialchars($mat) ?>" <?= $busqueda === $mat ? "selected" : "" ?>><?= htmlspecialchars($mat) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit">Buscar</button>
                    <?php if ($busqueda !== ""): ?>
                        <a href="prestamos.php" class="btn_secundario" style="text-decoration: none; display: flex; align-items: center; padding: 8px 12px; font-weight: bold;" title="Quitar filtro">✕</a>
                    <?php endif; ?>
                </form>

                <form method="POST">
                    <?= csrf_campo() ?>
                    <table class="tabla_admin">
                    <tr>
                        <th></th><th>Código (ISBN)</th><th>Nombre</th><th>Autor</th><th>Materia</th><th>Disp.</th>
                        <?php if ($rol === "docente"): ?><th>Cantidad</th><?php endif; ?>
                    </tr>
                    <?php foreach ($libros_disponibles as $l): ?>
                    <tr>
                        <td><input type="radio" name="isbn" value="<?= htmlspecialchars($l["isbn"]) ?>" required></td>
                        <td><code style="font-weight: 600; font-size: 12px;"><?= htmlspecialchars($l["isbn"]) ?></code></td>
                        <td><?= htmlspecialchars($l["nombre"]) ?></td>
                        <td><?= htmlspecialchars($l["autor"]) ?></td>
                        <td><?= htmlspecialchars($l["materia"]) ?></td>
                        <td><?= (int)$l["cantidad"] ?></td>
                        <?php if ($rol === "docente"): ?>
                        <td><input type="number" name="cantidades[<?= htmlspecialchars($l["isbn"]) ?>]" min="1" max="<?= (int)$l["cantidad"] ?>" value="1" class="input_cantidad" title="Máx. <?= (int)$l["cantidad"] ?> ejemplares"></td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                    </table>
                    <?php if ($rol === "docente"): ?>
                        <p style="font-size: 13px; color: var(--text-muted); margin: 8px 0;"><i class="ti ti-info-circle"></i> Como docente puedes solicitar múltiples ejemplares para tu clase (hasta el total disponible indicado en la columna Disp.).</p>
                    <?php endif; ?>
                    <button type="submit" name="prestar" class="btn_compacto">Prestar seleccionado</button>
                </form>

                <div class="paginacion">
                    <?php if ($pagina > 1): ?>
                        <a href="?q=<?= urlencode($busqueda) ?>&pagina=<?= $pagina - 1 ?>">‹ Anterior</a>
                    <?php endif; ?>
                    <span>Página <?= $pagina ?> de <?= $total_paginas ?></span>
                    <?php if ($pagina < $total_paginas): ?>
                        <a href="?q=<?= urlencode($busqueda) ?>&pagina=<?= $pagina + 1 ?>">Siguiente ›</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="perfil_col perfil_col_side">
            <div class="perfil_panel">
                <div class="panel_header">
                    <i class="ti ti-book-2"></i>
                    <h3>Mis Libros Prestados</h3>
                </div>

                <div class="seccion_activos">
                    <p class="subtitulo_panel">Préstamos activos (<?= count($prestamos) ?>)</p>
                    <?php if (empty($prestamos)): ?>
                        <p class="texto_vacio">No tienes préstamos activos.</p>
                    <?php endif; ?>

                    <div class="favoritos_lista lista_scroll">
                        <?php foreach ($prestamos as $p): ?>
                        <div class="favorito_item">
                            <div class="fav_cover fav_cover_1"><i class="ti ti-book"></i></div>
                            <div class="fav_info">
                                <p class="fav_titulo"><?= htmlspecialchars($p["nombre"]) ?></p>
                                <p class="fav_autor">
                                    <?= $p["cantidad"] ?> ejemplar(es) desde <?= htmlspecialchars($p["primera_fecha"]) ?>
                                    <?php if (!empty($p["solicitud_pendiente"])): ?>
                                        <span class="badge_dias badge_solicitud"><i class="ti ti-clock"></i> Solicitud pendiente</span>
                                    <?php elseif ($p["dias_restantes"] !== null && $p["dias_restantes"] < 0): ?>
                                        <span class="badge_dias badge_vencido">Vencido</span>
                                    <?php elseif ($p["dias_restantes"] !== null && $p["dias_restantes"] == 0): ?>
                                        <span class="badge_dias badge_hoy">Vence hoy</span>
                                    <?php elseif ($p["dias_restantes"] !== null && $p["dias_restantes"] <= 2): ?>
                                        <span class="badge_dias badge_hoy">Vence en <?= $p["dias_restantes"] ?> días</span>
                                    <?php elseif ($p["dias_restantes"] !== null): ?>
                                        <span class="badge_dias badge_ok">Vence en <?= $p["dias_restantes"] ?> días</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <?php if (!empty($p["solicitud_pendiente"])): ?>
                            <div class="form_inline" style="padding: 6px 0;">
                                <span style="font-size: 12px; color: #b45309; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                                    <i class="ti ti-hourglass"></i> Pendiente confirmación admin
                                </span>
                            </div>
                        <?php else: ?>
                            <form method="POST" class="form_inline">
                                <?= csrf_campo() ?>
                                <input type="hidden" name="isbn_lote" value="<?= htmlspecialchars($p["isbn"]) ?>">
                                <input type="number" name="cantidad_lote" min="1" max="<?= $p["cantidad"] ?>" value="<?= $p["cantidad"] ?>">
                                <button type="submit" name="devolver_lote"><?= $rol === "estudiante" ? "Solicitar devolución" : "Devolver" ?></button>
                            </form>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <hr class="divisor_panel">

                <button type="button" class="btn_toggle_historial" data-target="historial_libros_drawer">
                    <span class="btn_toggle_info">
                        <i class="ti ti-history"></i>
                        <span>Historial de devoluciones (<?= count($historial_usuario) ?>)</span>
                    </span>
                    <i class="ti ti-chevron-down flecha_toggle"></i>
                </button>

                <div id="historial_libros_drawer" class="historial_drawer drawer_cerrado">
                    <p style="font-size: 11px; color: var(--text-muted); margin: 0 0 10px 4px;"><i class="ti ti-info-circle"></i> Las devoluciones se conservan automáticamente por 30 días.</p>
                    <?php if (empty($historial_usuario)): ?>
                        <p class="texto_vacio">No tienes devoluciones registradas.</p>
                    <?php else: ?>
                        <div class="favoritos_lista lista_scroll">
                            <?php foreach ($historial_usuario as $h): ?>
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

<!-- Modal de Configuración de Cuenta (Foto de Perfil & Cambiar Contraseña) -->
<div id="modal_perfil" class="modal_overlay">
    <div class="modal_caja">
        <div class="modal_header">
            <h3><i class="ti ti-user-cog"></i> Configuración de Cuenta</h3>
            <button type="button" class="btn_cerrar_modal" id="btn_cerrar_modal_perfil">&times;</button>
        </div>
        <div class="modal_body">
            <div class="modal_nav_tabs">
                <button type="button" class="modal_tab_btn tab_activo" data-tab-target="tab_foto">
                    <i class="ti ti-photo"></i> Foto de Perfil
                </button>
                <button type="button" class="modal_tab_btn" data-tab-target="tab_clave">
                    <i class="ti ti-lock"></i> Cambiar Contraseña
                </button>
            </div>

            <!-- Tab: Foto de Perfil -->
            <div id="tab_foto" class="modal_panel_seccion">
                <div class="avatar_preview_container">
                    <?php if ($foto_actual): ?>
                        <img id="avatar_preview" src="<?= htmlspecialchars($foto_actual) ?>" class="avatar_preview_img" alt="Foto actual">
                    <?php else: ?>
                        <img id="avatar_preview" src="img/cercatori.png" class="avatar_preview_img" alt="Vista previa de avatar">
                    <?php endif; ?>
                    <p style="font-size: 12px; color: var(--text-muted);">Sube una imagen cuadrada en formato JPG, PNG o WEBP (máx. 3MB)</p>
                </div>

                <form method="POST" enctype="multipart/form-data">
                    <?= csrf_campo() ?>
                    <div class="form_grupo">
                        <label for="input_foto">Seleccionar nueva foto</label>
                        <input type="file" name="foto" id="input_foto" accept="image/png, image/jpeg, image/webp, image/gif" required>
                    </div>
                    <div class="modal_acciones">
                        <?php if ($foto_actual): ?>
                            <button type="submit" name="eliminar_foto" class="btn_peligro" onclick="return confirm('¿Seguro que deseas eliminar tu foto de perfil?');">
                                <i class="ti ti-trash"></i> Eliminar foto
                            </button>
                        <?php endif; ?>
                        <button type="submit" name="subir_foto" class="btn_nuevo_usuario">
                            <i class="ti ti-upload"></i> Guardar foto
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tab: Cambiar Contraseña -->
            <div id="tab_clave" class="modal_panel_seccion" style="display: none;">
                <form method="POST">
                    <?= csrf_campo() ?>
                    <div class="form_grupo">
                        <label>Contraseña actual</label>
                        <div class="password-box">
                            <input type="password" name="clave_actual" required placeholder="Ingresa tu contraseña vigente">
                            <span class="icono ti ti-eye"></span>
                        </div>
                    </div>
                    <div class="form_grupo">
                        <label>Nueva contraseña</label>
                        <div class="password-box">
                            <input type="password" name="nueva_clave" minlength="3" required placeholder="Mínimo 3 caracteres">
                            <span class="icono ti ti-eye"></span>
                        </div>
                    </div>
                    <div class="form_grupo">
                        <label>Confirmar nueva contraseña</label>
                        <div class="password-box">
                            <input type="password" name="confirmar_clave" minlength="3" required placeholder="Repite tu nueva contraseña">
                            <span class="icono ti ti-eye"></span>
                        </div>
                    </div>
                    <div class="modal_acciones">
                        <button type="button" class="btn_secundario" id="btn_cerrar_modal_clave">Cancelar</button>
                        <button type="submit" name="cambiar_clave" class="btn_nuevo_usuario">
                            <i class="ti ti-check"></i> Actualizar contraseña
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>

<script src="js/index.js"></script>
<script>
// Manejo del modal de perfil y vista previa instantánea
(function() {
    const modal = document.getElementById("modal_perfil");
    const btnAbrirAvatar = document.getElementById("btn_abrir_modal_avatar");
    const btnAbrirConfig = document.getElementById("btn_abrir_config_perfil");
    const btnCerrar = document.getElementById("btn_cerrar_modal_perfil");
    const btnCerrarClave = document.getElementById("btn_cerrar_modal_clave");
    const tabBtns = document.querySelectorAll(".modal_tab_btn[data-tab-target]");
    const inputFoto = document.getElementById("input_foto");
    const avatarPreview = document.getElementById("avatar_preview");

    function abrirModal(tabName) {
        if (!modal) return;
        modal.classList.add("modal_activo");
        if (tabName) activarTab(tabName);
    }

    function cerrarModal() {
        if (!modal) return;
        modal.classList.remove("modal_activo");
    }

    function activarTab(targetId) {
        tabBtns.forEach(b => {
            b.classList.toggle("tab_activo", b.dataset.tabTarget === targetId);
        });
        document.querySelectorAll(".modal_panel_seccion").forEach(p => {
            p.style.display = (p.id === targetId) ? "block" : "none";
        });
    }

    if (btnAbrirAvatar) btnAbrirAvatar.addEventListener("click", () => abrirModal("tab_foto"));
    if (btnAbrirConfig) btnAbrirConfig.addEventListener("click", () => abrirModal("tab_foto"));
    if (btnCerrar) btnCerrar.addEventListener("click", cerrarModal);
    if (btnCerrarClave) btnCerrarClave.addEventListener("click", cerrarModal);

    modal.addEventListener("click", function(e) {
        if (e.target === modal) cerrarModal();
    });

    tabBtns.forEach(btn => {
        btn.addEventListener("click", function() {
            activarTab(this.dataset.tabTarget);
        });
    });

    // Previsualización instantánea de la imagen seleccionada
    if (inputFoto && avatarPreview) {
        inputFoto.addEventListener("change", function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    avatarPreview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    }
})();
</script>
</body>
</html>