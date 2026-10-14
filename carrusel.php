<?php
session_start();
require "config.php";

if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "administrador") {
    header("Location: login.php");
    exit;
}

$admin_active = "carrusel";
$active = "inicio";

require_once __DIR__ . "/includes/admin_foto.php";
$mensaje = "";
$mensaje_tipo = "ok";

$imagenes_protegidas = ["Imagen1.jpeg", "Imagen2.jpeg", "Imagen3.jpg"];
$directorio_carrusel = __DIR__ . "/img/Carrusel/";

if (!is_dir($directorio_carrusel)) {
    @mkdir($directorio_carrusel, 0777, true);
}

// ─── ACCIONES POST PROTEGIDAS CON CSRF ───
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!validar_csrf()) {
        $mensaje = "Petición no válida o expirada (CSRF). Por favor recarga la página.";
        $mensaje_tipo = "error";
    } else {
        // ─── CREAR NUEVA DIAPOSITIVA ───
        if (isset($_POST["crear_slide"])) {
            $frase = trim($_POST["frase"] ?? "");
            $orden = (int)($_POST["orden"] ?? 0);
            $activo = isset($_POST["activo"]) ? 1 : 0;

            if (empty($frase)) {
                $mensaje = "La frase para el carrusel no puede estar vacía.";
                $mensaje_tipo = "error";
            } elseif (!isset($_FILES["foto"]) || $_FILES["foto"]["error"] !== UPLOAD_ERR_OK) {
                $mensaje = "Debes seleccionar una imagen válida para la diapositiva.";
                $mensaje_tipo = "error";
            } else {
                $file_tmp  = $_FILES["foto"]["tmp_name"];
                $file_name = $_FILES["foto"]["name"];
                $file_size = $_FILES["foto"]["size"];
                $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $permitidas = ["jpg", "jpeg", "png", "webp", "gif"];
                $mimes_permitidos = ["image/jpeg", "image/png", "image/webp", "image/gif"];

                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime  = finfo_file($finfo, $file_tmp);
                finfo_close($finfo);

                if (!in_array($ext, $permitidas) || !in_array($mime, $mimes_permitidos)) {
                    $mensaje = "Formato de imagen no permitido. Usa JPG, JPEG, PNG, WEBP o GIF.";
                    $mensaje_tipo = "error";
                } elseif ($file_size > 5 * 1024 * 1024) {
                    $mensaje = "La imagen supera el límite de 5MB.";
                    $mensaje_tipo = "error";
                } else {
                    $nuevo_nombre = "slide_" . time() . "_" . mt_rand(100, 999) . "." . $ext;
                    $destino = $directorio_carrusel . $nuevo_nombre;

                    if (move_uploaded_file($file_tmp, $destino)) {
                        $stmt = $pdo->prepare("INSERT INTO carrusel (imagen, frase, orden, activo) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$nuevo_nombre, $frase, $orden, $activo]);
                        $mensaje = "Diapositiva agregada exitosamente al carrusel.";
                        $mensaje_tipo = "ok";
                    } else {
                        $mensaje = "Error al guardar el archivo en el servidor.";
                        $mensaje_tipo = "error";
                    }
                }
            }
        }

        // ─── EDITAR DIAPOSITIVA ───
        if (isset($_POST["editar_slide"])) {
            $id_slide = (int)($_POST["id_slide"] ?? 0);
            $frase = trim($_POST["frase"] ?? "");
            $orden = (int)($_POST["orden"] ?? 0);
            $activo = isset($_POST["activo"]) ? 1 : 0;

            if ($id_slide <= 0 || empty($frase)) {
                $mensaje = "ID inválido o frase vacía.";
                $mensaje_tipo = "error";
            } else {
                // Verificar imagen actual
                $stmt_img = $pdo->prepare("SELECT imagen FROM carrusel WHERE id = ?");
                $stmt_img->execute([$id_slide]);
                $imagen_actual = $stmt_img->fetchColumn();

                $imagen_final = $imagen_actual;

                // ¿Sube nueva imagen para reemplazar?
                if (isset($_FILES["foto"]) && $_FILES["foto"]["error"] === UPLOAD_ERR_OK) {
                    $file_tmp  = $_FILES["foto"]["tmp_name"];
                    $file_name = $_FILES["foto"]["name"];
                    $file_size = $_FILES["foto"]["size"];
                    $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $permitidas = ["jpg", "jpeg", "png", "webp", "gif"];
                    $mimes_permitidos = ["image/jpeg", "image/png", "image/webp", "image/gif"];

                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime  = finfo_file($finfo, $file_tmp);
                    finfo_close($finfo);

                    if (in_array($ext, $permitidas) && in_array($mime, $mimes_permitidos) && $file_size <= 5 * 1024 * 1024) {
                        $nuevo_nombre = "slide_" . time() . "_" . mt_rand(100, 999) . "." . $ext;
                        $destino = $directorio_carrusel . $nuevo_nombre;

                        if (move_uploaded_file($file_tmp, $destino)) {
                            // Borrar anterior si no es de las protegidas
                            if ($imagen_actual && !in_array($imagen_actual, $imagenes_protegidas) && file_exists($directorio_carrusel . $imagen_actual)) {
                                @unlink($directorio_carrusel . $imagen_actual);
                            }
                            $imagen_final = $nuevo_nombre;
                        }
                    }
                }

                $upd = $pdo->prepare("UPDATE carrusel SET imagen = ?, frase = ?, orden = ?, activo = ? WHERE id = ?");
                $upd->execute([$imagen_final, $frase, $orden, $activo, $id_slide]);

                $mensaje = "Diapositiva #$id_slide actualizada correctamente.";
                $mensaje_tipo = "ok";
            }
        }

        // ─── TOGGLE ACTIVO / PAUSADO ───
        if (isset($_POST["toggle_activo"])) {
            $id_slide = (int)($_POST["id_slide"] ?? 0);
            if ($id_slide > 0) {
                $pdo->prepare("UPDATE carrusel SET activo = 1 - activo WHERE id = ?")->execute([$id_slide]);
                $mensaje = "Estado de la diapositiva actualizado.";
                $mensaje_tipo = "ok";
            }
        }

        // ─── ELIMINAR DIAPOSITIVA ───
        if (isset($_POST["eliminar_slide"])) {
            $id_slide = (int)($_POST["id_slide"] ?? 0);
            if ($id_slide > 0) {
                $st = $pdo->prepare("SELECT imagen FROM carrusel WHERE id = ?");
                $st->execute([$id_slide]);
                $img = $st->fetchColumn();

                if ($img && !in_array($img, $imagenes_protegidas) && file_exists($directorio_carrusel . $img)) {
                    @unlink($directorio_carrusel . $img);
                }

                $pdo->prepare("DELETE FROM carrusel WHERE id = ?")->execute([$id_slide]);
                $mensaje = "Diapositiva eliminada del carrusel.";
                $mensaje_tipo = "ok";
            }
        }
    }
}

// Consultar todos los slides
$slides = $pdo->query("SELECT * FROM carrusel ORDER BY orden ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Cercatori - Gestión de Carrusel</title>
    <link rel="stylesheet" href="css/index.css">
</head>
<body>

<?php include "includes/header.php"; ?>
<?php include "includes/admin_nav.php"; ?>

<main class="admin_section">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 1.5rem;">
        <div>
            <h1>Control del Carrusel de Inicio</h1>
            <p class="admin_subtitle">Gestiona las imágenes y frases inspiradoras que se muestran a los estudiantes y visitantes en la portada de Cercatori.</p>
        </div>
        <button type="button" class="btn_nuevo_usuario" id="btn_abrir_nuevo_slide">
            <i class="ti ti-plus"></i> Nueva Diapositiva
        </button>
    </div>

    <?php if ($mensaje): ?>
        <p class="<?= $mensaje_tipo === "error" ? "mensaje_error" : "mensaje_ok" ?>" style="border-radius: 12px; margin-bottom: 1.5rem; font-weight: 500;">
            <?= htmlspecialchars($mensaje) ?>
        </p>
    <?php endif; ?>

    <?php if (empty($slides)): ?>
        <div style="background: var(--card-bg); border-radius: 14px; padding: 3rem 2rem; text-align: center; border: 1px dashed var(--table-border);">
            <i class="ti ti-photo-off" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem; display: block;"></i>
            <h3>No hay diapositivas registradas</h3>
            <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Agrega una foto y frase para destacar en la portada de la biblioteca.</p>
            <button type="button" class="btn_nuevo_usuario" onclick="document.getElementById('btn_abrir_nuevo_slide').click();">
                <i class="ti ti-plus"></i> Agregar primera diapositiva
            </button>
        </div>
    <?php else: ?>
        <div class="carrusel_grid">
            <?php foreach ($slides as $s): ?>
                <div class="carrusel_card">
                    <div class="carrusel_card_img_wrap">
                        <img src="img/Carrusel/<?= htmlspecialchars($s["imagen"]) ?>" alt="Slide" class="carrusel_card_img">
                        <span class="carrusel_card_badge <?= $s["activo"] ? 'badge_activo' : 'badge_inactivo' ?>">
                            <?= $s["activo"] ? '<i class="ti ti-eye"></i> Visible' : '<i class="ti ti-eye-off"></i> Oculto' ?>
                        </span>
                    </div>

                    <div class="carrusel_card_body">
                        <div>
                            <p class="carrusel_frase_texto">"<?= htmlspecialchars($s["frase"]) ?>"</p>
                        </div>

                        <div class="carrusel_card_footer">
                            <span class="carrusel_orden_tag">
                                <i class="ti ti-arrows-sort"></i> Orden: <strong><?= (int)$s["orden"] ?></strong>
                            </span>

                            <div class="acciones_fila">
                                <button type="button" class="btn_accion btn_accion_editar btn_trigger_editar_slide"
                                        data-id="<?= (int)$s["id"] ?>"
                                        data-frase="<?= htmlspecialchars($s["frase"]) ?>"
                                        data-orden="<?= (int)$s["orden"] ?>"
                                        data-activo="<?= (int)$s["activo"] ?>"
                                        data-imagen="<?= htmlspecialchars($s["imagen"]) ?>"
                                        title="Editar frase, orden o foto">
                                    <i class="ti ti-edit"></i> Editar
                                </button>

                                <form method="POST" style="display: inline; margin: 0;">
                                    <?= csrf_campo() ?>
                                    <input type="hidden" name="toggle_activo" value="1">
                                    <input type="hidden" name="id_slide" value="<?= (int)$s["id"] ?>">
                                    <button type="submit" class="btn_accion btn_accion_clave" title="<?= $s["activo"] ? 'Ocultar diapositiva' : 'Publicar diapositiva' ?>">
                                        <i class="ti <?= $s["activo"] ? 'ti-eye-off' : 'ti-eye' ?>"></i>
                                    </button>
                                </form>

                                <form method="POST" style="display: inline; margin: 0;" onsubmit="return confirm('¿Eliminar esta diapositiva del carrusel permanentemente?');">
                                    <?= csrf_campo() ?>
                                    <input type="hidden" name="eliminar_slide" value="1">
                                    <input type="hidden" name="id_slide" value="<?= (int)$s["id"] ?>">
                                    <button type="submit" class="btn_accion btn_accion_borrar" title="Eliminar diapositiva">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<!-- Modal: Nueva Diapositiva -->
<div id="modal_nuevo_slide" class="modal_overlay">
    <div class="modal_caja">
        <div class="modal_header">
            <h3><i class="ti ti-photo-plus"></i> Nueva Diapositiva del Carrusel</h3>
            <button type="button" class="btn_cerrar_modal" id="btn_cerrar_modal_nuevo">&times;</button>
        </div>
        <div class="modal_body">
            <form method="POST" enctype="multipart/form-data">
                <?= csrf_campo() ?>
                <input type="hidden" name="crear_slide" value="1">

                <div class="form_grupo">
                    <label>Imagen *</label>
                    <input type="file" name="foto" required accept="image/png, image/jpeg, image/webp, image/gif">
                    <span style="font-size: 11px; color: var(--text-muted);">Recomendado: 1200x500 px (máx. 5MB en JPG, PNG o WEBP).</span>
                </div>

                <div class="form_grupo">
                    <label>Frase o Cita Inspiradora *</label>
                    <textarea name="frase" rows="3" required placeholder="Ej: Leer es soñar con los ojos abiertos..."></textarea>
                </div>

                <div style="display: flex; gap: 15px;">
                    <div class="form_grupo" style="flex: 1;">
                        <label>Orden de aparición</label>
                        <input type="number" name="orden" value="<?= count($slides) + 1 ?>" min="0">
                    </div>
                    <div class="form_grupo" style="flex: 1; justify-content: center;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin-top: 22px;">
                            <input type="checkbox" name="activo" value="1" checked style="width: auto;">
                            Mostrar en portada
                        </label>
                    </div>
                </div>

                <div class="modal_acciones">
                    <button type="button" class="btn_secundario" id="btn_cancelar_modal_nuevo">Cancelar</button>
                    <button type="submit" class="btn_nuevo_usuario">
                        <i class="ti ti-upload"></i> Guardar Diapositiva
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Editar Diapositiva -->
<div id="modal_editar_slide" class="modal_overlay">
    <div class="modal_caja">
        <div class="modal_header">
            <h3><i class="ti ti-edit"></i> Editar Diapositiva</h3>
            <button type="button" class="btn_cerrar_modal" id="btn_cerrar_modal_editar">&times;</button>
        </div>
        <div class="modal_body">
            <form method="POST" enctype="multipart/form-data">
                <?= csrf_campo() ?>
                <input type="hidden" name="editar_slide" value="1">
                <input type="hidden" name="id_slide" id="editar_id_slide">

                <div class="form_grupo">
                    <label>Vista previa actual</label>
                    <img id="editar_preview_img" src="" alt="Vista previa" style="width: 100%; height: 130px; object-fit: cover; border-radius: 8px; border: 1px solid var(--table-border);">
                </div>

                <div class="form_grupo">
                    <label>Reemplazar imagen (opcional)</label>
                    <input type="file" name="foto" accept="image/png, image/jpeg, image/webp, image/gif">
                </div>

                <div class="form_grupo">
                    <label>Frase o Cita Inspiradora *</label>
                    <textarea name="frase" id="editar_frase_slide" rows="3" required></textarea>
                </div>

                <div style="display: flex; gap: 15px;">
                    <div class="form_grupo" style="flex: 1;">
                        <label>Orden de aparición</label>
                        <input type="number" name="orden" id="editar_orden_slide" min="0">
                    </div>
                    <div class="form_grupo" style="flex: 1; justify-content: center;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin-top: 22px;">
                            <input type="checkbox" name="activo" id="editar_activo_slide" value="1" style="width: auto;">
                            Mostrar en portada
                        </label>
                    </div>
                </div>

                <div class="modal_acciones">
                    <button type="button" class="btn_secundario" id="btn_cancelar_modal_editar">Cancelar</button>
                    <button type="submit" class="btn_nuevo_usuario">
                        <i class="ti ti-check"></i> Actualizar Diapositiva
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>

<script src="js/index.js"></script>
<script>
(function() {
    const modalNuevo = document.getElementById("modal_nuevo_slide");
    const btnAbrirNuevo = document.getElementById("btn_abrir_nuevo_slide");
    const btnCerrarNuevo = document.getElementById("btn_cerrar_modal_nuevo");
    const btnCancelNuevo = document.getElementById("btn_cancelar_modal_nuevo");

    if (btnAbrirNuevo) btnAbrirNuevo.addEventListener("click", () => modalNuevo.classList.add("modal_activo"));
    if (btnCerrarNuevo) btnCerrarNuevo.addEventListener("click", () => modalNuevo.classList.remove("modal_activo"));
    if (btnCancelNuevo) btnCancelNuevo.addEventListener("click", () => modalNuevo.classList.remove("modal_activo"));

    const modalEditar = document.getElementById("modal_editar_slide");
    const btnCerrarEditar = document.getElementById("btn_cerrar_modal_editar");
    const btnCancelEditar = document.getElementById("btn_cancelar_modal_editar");
    const idSlideInput = document.getElementById("editar_id_slide");
    const fraseInput = document.getElementById("editar_frase_slide");
    const ordenInput = document.getElementById("editar_orden_slide");
    const activoCheckbox = document.getElementById("editar_activo_slide");
    const previewImg = document.getElementById("editar_preview_img");

    document.querySelectorAll(".btn_trigger_editar_slide").forEach(btn => {
        btn.addEventListener("click", function() {
            idSlideInput.value = this.dataset.id;
            fraseInput.value = this.dataset.frase || "";
            ordenInput.value = this.dataset.orden || "0";
            activoCheckbox.checked = (this.dataset.activo === "1");
            previewImg.src = "img/Carrusel/" + this.dataset.imagen;
            modalEditar.classList.add("modal_activo");
        });
    });

    if (btnCerrarEditar) btnCerrarEditar.addEventListener("click", () => modalEditar.classList.remove("modal_activo"));
    if (btnCancelEditar) btnCancelEditar.addEventListener("click", () => modalEditar.classList.remove("modal_activo"));

    [modalNuevo, modalEditar].forEach(m => {
        if (m) {
            m.addEventListener("click", function(e) {
                if (e.target === m) m.classList.remove("modal_activo");
            });
        }
    });
})();
</script>
</body>
</html>
