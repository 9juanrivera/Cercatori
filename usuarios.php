<?php
session_start();
require "config.php";

if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "administrador") {
    header("Location: login.php");
    exit;
}

$admin_active = "usuarios";
$active = "materias";

require_once __DIR__ . "/includes/admin_foto.php";
$mensaje = "";
$mensaje_tipo = "ok";

// Procesamiento de acciones administrativas POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!validar_csrf()) {
        $mensaje = "Petición no válida o expirada (CSRF). Por favor recarga la página.";
        $mensaje_tipo = "error";
    } else {
        // cambia la clave desde admin
        if (isset($_POST["cambiar_clave_admin"])) {
            $tipo = $_POST["tipo_usuario"] ?? "";
            $id = trim($_POST["id_usuario"] ?? "");
            $nueva_clave = $_POST["nueva_clave"] ?? "";
            $confirmar_clave = $_POST["confirmar_clave"] ?? "";

            if (empty($id) || empty($nueva_clave)) {
                $mensaje = "El ID y la nueva contraseña son requeridos.";
                $mensaje_tipo = "error";
            } elseif ($nueva_clave !== $confirmar_clave) {
                $mensaje = "Las contraseñas no coinciden.";
                $mensaje_tipo = "error";
            } elseif (strlen($nueva_clave) < 4) {
                $mensaje = "La contraseña debe tener al menos 4 caracteres.";
                $mensaje_tipo = "error";
            } else {
                if ($tipo === "estudiante") {
                    $stmt = $pdo->prepare("UPDATE estudiantes SET clave = ? WHERE numero_matricula = ?");
                } elseif ($tipo === "docente") {
                    $stmt = $pdo->prepare("UPDATE docentes SET clave = ? WHERE cedula = ?");
                } elseif ($tipo === "administrador") {
                    $stmt = $pdo->prepare("UPDATE administradores SET clave = ? WHERE cedula = ?");
                } else {
                    $stmt = null;
                }

                if ($stmt) {
                    $hash_clave = password_hash($nueva_clave, PASSWORD_DEFAULT);
                    $stmt->execute([$hash_clave, $id]);
                    $mensaje = "Contraseña actualizada exitosamente para el usuario $id.";
                    $mensaje_tipo = "ok";
                } else {
                    $mensaje = "Tipo de usuario inválido.";
                    $mensaje_tipo = "error";
                }
            }
        }

        // Cambiar nombres y datos de usuario desde el panel del administrador
        if (isset($_POST["editar_usuario_admin"])) {
            $tipo = $_POST["tipo_usuario"] ?? "";
            $id = trim($_POST["id_usuario"] ?? "");
            $nombres = trim($_POST["nombres"] ?? "");
            $apellidos = trim($_POST["apellidos"] ?? "");
            $extra = trim($_POST["extra"] ?? "");
            $director = isset($_POST["director"]) ? 1 : 0;

            if (empty($id) || empty($nombres)) {
                $mensaje = "El documento y los nombres son obligatorios.";
                $mensaje_tipo = "error";
            } else {
                if ($tipo === "estudiante") {
                    $stmt = $pdo->prepare("UPDATE estudiantes SET nombres = ?, apellidos = ?, grado = ? WHERE numero_matricula = ?");
                    $stmt->execute([$nombres, $apellidos, $extra, $id]);
                } elseif ($tipo === "docente") {
                    $stmt = $pdo->prepare("UPDATE docentes SET nombre = ?, apellidos = ?, director_grupo = ? WHERE cedula = ?");
                    $stmt->execute([$nombres, $apellidos, $director, $id]);
                } elseif ($tipo === "administrador") {
                    $stmt = $pdo->prepare("UPDATE administradores SET nombre = ?, apellidos = ?, cargo = ? WHERE cedula = ?");
                    $stmt->execute([$nombres, $apellidos, $extra, $id]);
                    if ($id === $_SESSION["id"]) {
                        $_SESSION["nombre"] = $nombres;
                    }
                } else {
                    $stmt = null;
                }

                if ($stmt) {
                    $mensaje = "Datos de $nombres ($tipo) actualizados correctamente.";
                    $mensaje_tipo = "ok";
                } else {
                    $mensaje = "Tipo de usuario no válido.";
                    $mensaje_tipo = "error";
                }
            }
        }

        // borra el usuario pero mira si ya devolvio los libros 
        if (isset($_POST["eliminar_usuario"])) {
            $tipo = $_POST["tipo_usuario"] ?? "";
            $id = trim($_POST["id_usuario"] ?? "");

            if ($tipo === "administrador" && $id === $_SESSION["id"]) {
                $mensaje = "No puedes eliminar tu propia cuenta de administrador con la sesión activa.";
                $mensaje_tipo = "error";
            } else {
                // esta mira si devolvio los prestados
                $pendientes = 0;
                if (in_array($tipo, ["estudiante", "docente"])) {
                    $stmt_p = $pdo->prepare("SELECT COUNT(*) FROM prestamos WHERE id_usuario = ? AND tipo_usuario = ? AND devuelto = 0");
                    $stmt_p->execute([$id, $tipo]);
                    $pendientes += (int)$stmt_p->fetchColumn();

                    $stmt_j = $pdo->prepare("SELECT COUNT(*) FROM prestamos_juegos WHERE id_usuario = ? AND tipo_usuario = ? AND devuelto = 0");
                    $stmt_j->execute([$id, $tipo]);
                    $pendientes += (int)$stmt_j->fetchColumn();

                    $stmt_c = $pdo->prepare("SELECT COUNT(*) FROM reservas_computadores WHERE id_usuario = ? AND tipo_usuario = ? AND devuelto = 0");
                    $stmt_c->execute([$id, $tipo]);
                    $pendientes += (int)$stmt_c->fetchColumn();
                }

                if ($pendientes > 0) {
                    $mensaje = "No se puede eliminar la cuenta: el usuario tiene $pendientes recurso(s) prestado(s) pendientes de devolución. Primero debe registrar las devoluciones.";
                    $mensaje_tipo = "error";
                } else {
                    // elimina la foto de perfil si hay 
                    $foto_archivo = null;
                    if ($tipo === "estudiante") {
                        $st = $pdo->prepare("SELECT foto_perfil FROM estudiantes WHERE numero_matricula = ?");
                    } elseif ($tipo === "docente") {
                        $st = $pdo->prepare("SELECT foto_perfil FROM docentes WHERE cedula = ?");
                    } else {
                        $st = $pdo->prepare("SELECT foto_perfil FROM administradores WHERE cedula = ?");
                    }
                    $st->execute([$id]);
                    $foto_archivo = $st->fetchColumn();

                    if ($foto_archivo && file_exists(__DIR__ . "/img/perfiles/" . $foto_archivo)) {
                        @unlink(__DIR__ . "/img/perfiles/" . $foto_archivo);
                    }

                    // limpiar historial de prestamos ya devueltos para evitar referencias huerfanas
                    if (in_array($tipo, ["estudiante", "docente"])) {
                        $pdo->prepare("DELETE FROM prestamos WHERE id_usuario = ? AND tipo_usuario = ?")->execute([$id, $tipo]);
                        $pdo->prepare("DELETE FROM prestamos_juegos WHERE id_usuario = ? AND tipo_usuario = ?")->execute([$id, $tipo]);
                        $pdo->prepare("DELETE FROM reservas_computadores WHERE id_usuario = ? AND tipo_usuario = ?")->execute([$id, $tipo]);
                    }

                    // borra el registro de la gente
                    if ($tipo === "estudiante") {
                        $del = $pdo->prepare("DELETE FROM estudiantes WHERE numero_matricula = ?");
                    } elseif ($tipo === "docente") {
                        $del = $pdo->prepare("DELETE FROM docentes WHERE cedula = ?");
                    } else {
                        $del = $pdo->prepare("DELETE FROM administradores WHERE cedula = ?");
                    }
                    $del->execute([$id]);

                    $mensaje = "Cuenta de usuario $id eliminada correctamente.";
                    $mensaje_tipo = "ok";
                }
            }
        }

        // crear usuarios sin restricciones porque es un admin
        if (isset($_POST["crear_usuario"])) {
            $tipo = $_POST["tipo"] ?? "estudiante";
            $id = trim($_POST["id"] ?? "");
            $clave = $_POST["clave"] ?? "";
            $nombre = trim($_POST["nombre"] ?? "");
            $apellidos = trim($_POST["apellidos"] ?? "");
            $extra = trim($_POST["extra"] ?? "");
            $director = isset($_POST["director"]) ? 1 : 0;
            $pregunta = trim($_POST["pregunta_secreta"] ?? "¿Cuál es tu libro favorito?");
            $respuesta = strtolower(trim($_POST["respuesta_secreta"] ?? "cercatori"));

            if (empty($id) || empty($clave) || empty($nombre)) {
                $mensaje = "ID, contraseña y nombre son obligatorios.";
                $mensaje_tipo = "error";
            } elseif (strlen($clave) < 4) {
                $mensaje = "La contraseña debe tener al menos 4 caracteres.";
                $mensaje_tipo = "error";
            } elseif (id_existe_en_sistema($pdo, $id)) {
                $mensaje = "Ya existe un usuario con ese documento o matrícula ($id).";
                $mensaje_tipo = "error";
            } else {
                $clave_hash = password_hash($clave, PASSWORD_DEFAULT);
                try {
                    if ($tipo === "estudiante") {
                        $ins = $pdo->prepare("INSERT INTO estudiantes (numero_matricula, clave, nombres, apellidos, grado, pregunta_secreta, respuesta_secreta) VALUES (?,?,?,?,?,?,?)");
                        $ins->execute([$id, $clave_hash, $nombre, $apellidos, $extra, $pregunta, $respuesta]);
                    } elseif ($tipo === "docente") {
                        $ins = $pdo->prepare("INSERT INTO docentes (cedula, clave, nombre, apellidos, director_grupo, pregunta_secreta, respuesta_secreta) VALUES (?,?,?,?,?,?,?)");
                        $ins->execute([$id, $clave_hash, $nombre, $apellidos, $director, $pregunta, $respuesta]);
                    } else {
                        $ins = $pdo->prepare("INSERT INTO administradores (cedula, clave, nombre, apellidos, cargo, pregunta_secreta, respuesta_secreta) VALUES (?,?,?,?,?,?,?)");
                        $ins->execute([$id, $clave_hash, $nombre, $apellidos, $extra, $pregunta, $respuesta]);
                    }
                    $mensaje = "Usuario $nombre ($tipo) creado exitosamente.";
                    $mensaje_tipo = "ok";
                } catch (PDOException $e) {
                    error_log("Error al crear usuario: " . $e->getMessage());
                    if (strpos($e->getMessage(), "1062") !== false || strpos($e->getMessage(), "Duplicate") !== false) {
                        $mensaje = "Ya existe un usuario con ese documento o matrícula ($id).";
                    } else {
                        $mensaje = "No se pudo crear el usuario. Por favor intenta de nuevo.";
                    }
                    $mensaje_tipo = "error";
                }
            }
        }
    }
}

// filtros 
$tab_actual = $_GET["tab"] ?? "estudiantes";
$busqueda = trim($_GET["q"] ?? "");

// para ver cuantas cuentas hay creadad y si solo cuenta cuanta gente hay en cdda rol 
$total_estudiantes = $pdo->query("SELECT COUNT(*) FROM estudiantes")->fetchColumn();
$total_docentes = $pdo->query("SELECT COUNT(*) FROM docentes")->fetchColumn();
$total_admins = $pdo->query("SELECT COUNT(*) FROM administradores")->fetchColumn();

// Consultar usuarios según tab
$usuarios_lista = [];

if ($tab_actual === "estudiantes") {
    $sql = "
        SELECT e.numero_matricula AS id, e.nombres, e.apellidos, e.grado AS extra, e.foto_perfil,
            (SELECT COUNT(*) FROM prestamos WHERE id_usuario = e.numero_matricula AND tipo_usuario = 'estudiante' AND devuelto = 0) +
            (SELECT COUNT(*) FROM prestamos_juegos WHERE id_usuario = e.numero_matricula AND tipo_usuario = 'estudiante' AND devuelto = 0) +
            (SELECT COUNT(*) FROM reservas_computadores WHERE id_usuario = e.numero_matricula AND tipo_usuario = 'estudiante' AND devuelto = 0) AS prestamos_activos
        FROM estudiantes e
    ";
    if ($busqueda !== "") {
        $sql .= " WHERE e.numero_matricula LIKE ? OR e.nombres LIKE ? OR e.apellidos LIKE ? OR e.grado LIKE ?";
        $like = "%$busqueda%";
        $stmt = $pdo->prepare($sql . " ORDER BY e.nombres ASC");
        $stmt->execute([$like, $like, $like, $like]);
    } else {
        $stmt = $pdo->prepare($sql . " ORDER BY e.nombres ASC");
        $stmt->execute();
    }
    $usuarios_lista = $stmt->fetchAll(PDO::FETCH_ASSOC);

} elseif ($tab_actual === "docentes") {
    $sql = "
        SELECT d.cedula AS id, d.nombre AS nombres, d.apellidos, d.director_grupo, d.foto_perfil,
            (SELECT COUNT(*) FROM prestamos WHERE id_usuario = d.cedula AND tipo_usuario = 'docente' AND devuelto = 0) +
            (SELECT COUNT(*) FROM prestamos_juegos WHERE id_usuario = d.cedula AND tipo_usuario = 'docente' AND devuelto = 0) +
            (SELECT COUNT(*) FROM reservas_computadores WHERE id_usuario = d.cedula AND tipo_usuario = 'docente' AND devuelto = 0) AS prestamos_activos
        FROM docentes d
    ";
    if ($busqueda !== "") {
        $sql .= " WHERE d.cedula LIKE ? OR d.nombre LIKE ? OR d.apellidos LIKE ?";
        $like = "%$busqueda%";
        $stmt = $pdo->prepare($sql . " ORDER BY d.nombre ASC");
        $stmt->execute([$like, $like, $like]);
    } else {
        $stmt = $pdo->prepare($sql . " ORDER BY d.nombre ASC");
        $stmt->execute();
    }
    $usuarios_lista = $stmt->fetchAll(PDO::FETCH_ASSOC);

} elseif ($tab_actual === "administradores") {
    $sql = "
        SELECT a.cedula AS id, a.nombre AS nombres, a.apellidos, a.cargo AS extra, a.foto_perfil, 0 AS prestamos_activos
        FROM administradores a
    ";
    if ($busqueda !== "") {
        $sql .= " WHERE a.cedula LIKE ? OR a.nombre LIKE ? OR a.apellidos LIKE ? OR a.cargo LIKE ?";
        $like = "%$busqueda%";
        $stmt = $pdo->prepare($sql . " ORDER BY a.nombre ASC");
        $stmt->execute([$like, $like, $like, $like]);
    } else {
        $stmt = $pdo->prepare($sql . " ORDER BY a.nombre ASC");
        $stmt->execute();
    }
    $usuarios_lista = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Cercatori - Gestión de Usuarios</title>
    <link rel="stylesheet" href="css/index.css">
</head>
<body>

<?php include "includes/header.php"; ?>

<?php include "includes/admin_nav.php"; ?>

<main class="admin_section">
    <h1>Gestión de Usuarios</h1>
    <p class="admin_subtitle">Administra los accesos de estudiantes, docentes y administradores. Puedes cambiar contraseñas, gestionar fotos y dar de baja cuentas.</p>

    <?php if ($mensaje): ?>
        <p class="<?= $mensaje_tipo === "error" ? "mensaje_error" : "mensaje_ok" ?>" style="border-radius: 12px; margin-bottom: 1.5rem; font-weight: 500;"><?= htmlspecialchars($mensaje) ?></p>
    <?php endif; ?>

    <div class="usuarios_toolbar">
        <div class="usuarios_tabs">
            <a href="usuarios.php?tab=estudiantes" class="usuarios_tab_link <?= $tab_actual === 'estudiantes' ? 'activo' : '' ?>">
                <i class="ti ti-school"></i> Estudiantes (<?= $total_estudiantes ?>)
            </a>
            <a href="usuarios.php?tab=docentes" class="usuarios_tab_link <?= $tab_actual === 'docentes' ? 'activo' : '' ?>">
                <i class="ti ti-certificate"></i> Docentes (<?= $total_docentes ?>)
            </a>
            <a href="usuarios.php?tab=administradores" class="usuarios_tab_link <?= $tab_actual === 'administradores' ? 'activo' : '' ?>">
                <i class="ti ti-shield-lock"></i> Administradores (<?= $total_admins ?>)
            </a>
        </div>

        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <form method="GET" class="usuarios_buscador_wrap">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($tab_actual) ?>">
                <input type="text" name="q" placeholder="Buscar por nombre o documento..." value="<?= htmlspecialchars($busqueda) ?>">
                <button type="submit" class="btn_nuevo_usuario" style="padding: 8px 12px;"><i class="ti ti-search"></i></button>
                <?php if ($busqueda !== ""): ?>
                    <a href="usuarios.php?tab=<?= htmlspecialchars($tab_actual) ?>" class="btn_secundario" style="text-decoration: none; padding: 8px 12px;" title="Limpiar filtro">✕</a>
                <?php endif; ?>
            </form>

            <button type="button" class="btn_nuevo_usuario" id="btn_abrir_nuevo_usuario">
                <i class="ti ti-user-plus"></i> Nuevo Usuario
            </button>
        </div>
    </div>

    <!-- tabla de Usuarios -->
    <div class="formulario" style="padding: 1.5rem; overflow-x: auto;">
        <table class="tabla_admin">
            <thead>
                <tr>
                    <th style="width: 50px;">Foto</th>
                    <th>Documento / Matrícula</th>
                    <th>Nombre y Apellidos</th>
                    <?php if ($tab_actual === "estudiantes"): ?>
                        <th>Grado</th>
                        <th>Préstamos Activos</th>
                    <?php elseif ($tab_actual === "docentes"): ?>
                        <th>Director Grupo</th>
                        <th>Préstamos Activos</th>
                    <?php else: ?>
                        <th>Cargo</th>
                    <?php endif; ?>
                    <th style="text-align: right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($usuarios_lista)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                            No se encontraron usuarios en esta categoría<?= $busqueda !== "" ? " para la búsqueda \"" . htmlspecialchars($busqueda) . "\"" : "" ?>.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($usuarios_lista as $u): ?>
                        <tr>
                            <td>
                                <?php if (!empty($u["foto_perfil"]) && file_exists(__DIR__ . "/img/perfiles/" . $u["foto_perfil"])): ?>
                                    <img src="img/perfiles/<?= htmlspecialchars($u["foto_perfil"]) ?>" class="tabla_avatar_mini" alt="Avatar">
                                <?php else: ?>
                                    <div class="tabla_avatar_placeholder"><i class="ti ti-user"></i></div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= htmlspecialchars($u["id"]) ?></strong></td>
                            <td><?= htmlspecialchars($u["nombres"] . " " . ($u["apellidos"] ?? "")) ?></td>
                            
                            <?php if ($tab_actual === "estudiantes"): ?>
                                <td><span class="badge_rol badge_rol_estudiante"><?= htmlspecialchars($u["extra"] ?? 'Sin grado') ?></span></td>
                                <td>
                                    <?php if ((int)$u["prestamos_activos"] > 0): ?>
                                        <span style="color: #ea580c; font-weight: 600;"><i class="ti ti-clock-exclamation"></i> <?= $u["prestamos_activos"] ?> pendiente(s)</span>
                                    <?php else: ?>
                                        <span style="color: #16a34a;"><i class="ti ti-circle-check"></i> Al día (0)</span>
                                    <?php endif; ?>
                                </td>
                            <?php elseif ($tab_actual === "docentes"): ?>
                                <td><?= !empty($u["director_grupo"]) ? "Sí" : "No" ?></td>
                                <td>
                                    <?php if ((int)$u["prestamos_activos"] > 0): ?>
                                        <span style="color: #ea580c; font-weight: 600;"><i class="ti ti-clock-exclamation"></i> <?= $u["prestamos_activos"] ?> pendiente(s)</span>
                                    <?php else: ?>
                                        <span style="color: #16a34a;"><i class="ti ti-circle-check"></i> Al día (0)</span>
                                    <?php endif; ?>
                                </td>
                            <?php else: ?>
                                <td><span class="badge_rol badge_rol_admin"><?= htmlspecialchars($u["extra"] ?? 'Administrador') ?></span></td>
                            <?php endif; ?>

                            <td style="text-align: right;">
                                <div class="acciones_fila" style="justify-content: flex-end;">
                                    <button type="button" class="btn_accion btn_accion_editar btn_trigger_editar_usuario"
                                            data-id="<?= htmlspecialchars($u["id"]) ?>"
                                            data-nombres="<?= htmlspecialchars($u["nombres"]) ?>"
                                            data-apellidos="<?= htmlspecialchars($u["apellidos"] ?? "") ?>"
                                            data-extra="<?= htmlspecialchars($u["extra"] ?? "") ?>"
                                            data-director="<?= !empty($u["director_grupo"]) ? '1' : '0' ?>"
                                            data-tipo="<?= $tab_actual === "estudiantes" ? "estudiante" : ($tab_actual === "docentes" ? "docente" : "administrador") ?>"
                                            title="Editar nombres y datos">
                                        <i class="ti ti-edit"></i> Editar
                                    </button>

                                    <button type="button" class="btn_accion btn_accion_clave btn_trigger_cambiar_clave"
                                            data-id="<?= htmlspecialchars($u["id"]) ?>"
                                            data-nombre="<?= htmlspecialchars($u["nombres"] . " " . ($u["apellidos"] ?? "")) ?>"
                                            data-tipo="<?= $tab_actual === "estudiantes" ? "estudiante" : ($tab_actual === "docentes" ? "docente" : "administrador") ?>"
                                            title="Cambiar contraseña">
                                        <i class="ti ti-key"></i> Clave
                                    </button>

                                    <?php 
                                    $es_mismo_admin = ($tab_actual === "administradores" && $u["id"] === $_SESSION["id"]);
                                    ?>
                                    <?php if (!$es_mismo_admin): ?>
                                        <button type="button" class="btn_accion btn_accion_borrar btn_trigger_borrar_usuario"
                                                data-id="<?= htmlspecialchars($u["id"]) ?>"
                                                data-nombre="<?= htmlspecialchars($u["nombres"] . " " . ($u["apellidos"] ?? "")) ?>"
                                                data-tipo="<?= $tab_actual === "estudiantes" ? "estudiante" : ($tab_actual === "docentes" ? "docente" : "administrador") ?>"
                                                data-prestamos="<?= (int)($u["prestamos_activos"] ?? 0) ?>"
                                                title="Eliminar cuenta">
                                            <i class="ti ti-trash"></i> Borrar
                                        </button>
                                    <?php else: ?>
                                        <span style="font-size: 11px; color: var(--text-muted); font-style: italic;">(Tu sesión)</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<!-- modal para editar nombre y datos del usuario -->
<div id="modal_editar_usuario" class="modal_overlay">
    <div class="modal_caja">
        <div class="modal_header">
            <h3><i class="ti ti-edit"></i> Editar Información de Usuario</h3>
            <button type="button" class="btn_cerrar_modal" id="btn_cerrar_modal_editar">&times;</button>
        </div>
        <div class="modal_body">
            <form method="POST">
                <?= csrf_campo() ?>
                <input type="hidden" name="editar_usuario_admin" value="1">
                <input type="hidden" name="tipo_usuario" id="editar_tipo_usuario">
                <input type="hidden" name="id_usuario" id="editar_id_usuario">

                <div class="form_grupo">
                    <label>Documento / Matrícula</label>
                    <input type="text" id="editar_id_display" disabled style="opacity: 0.75; cursor: not-allowed;">
                </div>

                <div class="form_grupo">
                    <label>Nombres *</label>
                    <input type="text" name="nombres" id="editar_nombres_usuario" required placeholder="Nombres del usuario">
                </div>

                <div class="form_grupo">
                    <label>Apellidos</label>
                    <input type="text" name="apellidos" id="editar_apellidos_usuario" placeholder="Apellidos del usuario">
                </div>

                <div class="form_grupo" id="grupo_editar_extra">
                    <label id="label_editar_extra">Grado escolar</label>
                    <input type="text" name="extra" id="editar_extra_usuario" placeholder="Ej: 11°1 o Cargo">
                </div>

                <div class="form_grupo" id="grupo_editar_director" style="display: none;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; text-transform: none;">
                        <input type="checkbox" name="director" id="editar_director_docente" value="1" style="width: auto;">
                        Director de grupo
                    </label>
                </div>

                <div class="modal_acciones">
                    <button type="button" class="btn_secundario" id="btn_cancelar_modal_editar">Cancelar</button>
                    <button type="submit" class="btn_nuevo_usuario">
                        <i class="ti ti-check"></i> Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- modal o ventanita de admin para cambiar la clave -->
<div id="modal_cambiar_clave_admin" class="modal_overlay">
    <div class="modal_caja">
        <div class="modal_header">
            <h3><i class="ti ti-key"></i> Cambiar Contraseña</h3>
            <button type="button" class="btn_cerrar_modal" id="btn_cerrar_modal_clave">&times;</button>
        </div>
        <div class="modal_body">
            <form method="POST">
                <?= csrf_campo() ?>
                <input type="hidden" name="cambiar_clave_admin" value="1">
                <input type="hidden" name="tipo_usuario" id="clave_tipo_usuario">
                <input type="hidden" name="id_usuario" id="clave_id_usuario">

                <p style="margin-bottom: 1.25rem; font-size: 13px; color: var(--text-muted);">
                    Asignarás una nueva contraseña para <strong id="clave_nombre_usuario" style="color: var(--dark);"></strong> (ID: <span id="clave_id_texto"></span>).
                </p>

                <div class="form_grupo">
                    <label>Nueva contraseña</label>
                    <div class="password-box">
                        <input type="password" name="nueva_clave" minlength="3" required placeholder="Ingresa la nueva contraseña">
                        <span class="icono ti ti-eye"></span>
                    </div>
                </div>

                <div class="form_grupo">
                    <label>Confirmar nueva contraseña</label>
                    <div class="password-box">
                        <input type="password" name="confirmar_clave" minlength="3" required placeholder="Repite la nueva contraseña">
                        <span class="icono ti ti-eye"></span>
                    </div>
                </div>

                <div class="modal_acciones">
                    <button type="button" class="btn_secundario" id="btn_cancelar_modal_clave">Cancelar</button>
                    <button type="submit" class="btn_nuevo_usuario">
                        <i class="ti ti-check"></i> Guardar contraseña
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- seguro que va a borrar lacuenta en resuemn una ventana mas para eso-->
<div id="modal_borrar_usuario" class="modal_overlay">
    <div class="modal_caja">
        <div class="modal_header">
            <h3 style="color: #dc2626;"><i class="ti ti-alert-triangle"></i> Confirmar Eliminación</h3>
            <button type="button" class="btn_cerrar_modal" id="btn_cerrar_modal_borrar">&times;</button>
        </div>
        <div class="modal_body">
            <form method="POST">
                <?= csrf_campo() ?>
                <input type="hidden" name="eliminar_usuario" value="1">
                <input type="hidden" name="tipo_usuario" id="borrar_tipo_usuario">
                <input type="hidden" name="id_usuario" id="borrar_id_usuario">

                <p style="font-size: 14px; margin-bottom: 1rem; color: var(--dark);">
                    ¿Estás completamente seguro de que deseas eliminar la cuenta de <strong id="borrar_nombre_usuario"></strong>?
                </p>

                <div id="aviso_prestamos_bloqueo" style="display: none; background: rgba(220, 38, 38, 0.1); border: 1px solid #dc2626; padding: 12px; border-radius: 8px; margin-bottom: 1rem; color: #dc2626; font-size: 13px;">
                    <i class="ti ti-alert-circle"></i> <strong>Atención:</strong> Este usuario tiene <span id="borrar_cant_prestamos"></span> préstamo(s) activo(s). No se podrá eliminar hasta que devuelva todos los libros, computadores o juegos prestados.
                </div>

                <p style="font-size: 12px; color: var(--text-muted); line-height: 1.6;">
                    Esta acción eliminará el acceso del usuario, su foto de perfil y sus registros de devolución asociados. No se puede deshacer.
                </p>

                <div class="modal_acciones">
                    <button type="button" class="btn_secundario" id="btn_cancelar_modal_borrar">Cancelar</button>
                    <button type="submit" id="btn_confirmar_borrar" class="btn_peligro">
                        <i class="ti ti-trash"></i> Eliminar definitivamente
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- crear nuevo usuario -->
<div id="modal_nuevo_usuario" class="modal_overlay">
    <div class="modal_caja">
        <div class="modal_header">
            <h3><i class="ti ti-user-plus"></i> Registrar Nuevo Usuario</h3>
            <button type="button" class="btn_cerrar_modal" id="btn_cerrar_modal_nuevo">&times;</button>
        </div>
        <div class="modal_body">
            <form method="POST">
                <?= csrf_campo() ?>
                <input type="hidden" name="crear_usuario" value="1">

                <div class="form_grupo">
                    <label>Tipo de Usuario</label>
                    <select name="tipo" id="nuevo_tipo_select" required>
                        <option value="estudiante" <?= $tab_actual === "estudiantes" ? "selected" : "" ?>>Estudiante</option>
                        <option value="docente" <?= $tab_actual === "docentes" ? "selected" : "" ?>>Docente</option>
                        <option value="administrador" <?= $tab_actual === "administradores" ? "selected" : "" ?>>Administrador</option>
                    </select>
                </div>

                <div class="form_grupo">
                    <label>ID (Cédula o Número de Matrícula)</label>
                    <input type="text" name="id" required placeholder="Ej: 1025894000">
                </div>

                <div class="form_grupo">
                    <label>Contraseña inicial</label>
                    <div class="password-box">
                        <input type="password" name="clave" minlength="3" required placeholder="Contraseña de acceso">
                        <span class="icono ti ti-eye"></span>
                    </div>
                </div>

                <div class="form_grupo">
                    <label>Nombres</label>
                    <input type="text" name="nombre" required placeholder="Nombre del usuario">
                </div>

                <div class="form_grupo">
                    <label>Apellidos</label>
                    <input type="text" name="apellidos" placeholder="Apellidos">
                </div>

                <div class="form_grupo" id="grupo_extra">
                    <label id="label_extra_nuevo">Grado escolar</label>
                    <input type="text" name="extra" id="input_extra_nuevo" placeholder="Ej: 11°1 o Cargo si es admin">
                </div>

                <div class="form_grupo" id="grupo_director_docente" style="display: none;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; text-transform: none;">
                        <input type="checkbox" name="director" value="1" style="width: auto;">
                        Director de grupo
                    </label>
                </div>

                <div class="modal_acciones">
                    <button type="button" class="btn_secundario" id="btn_cancelar_modal_nuevo">Cancelar</button>
                    <button type="submit" class="btn_nuevo_usuario">
                        <i class="ti ti-check"></i> Crear usuario
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>

<script src="js/index.js"></script>
<script>
// modal para las cosas
(function() {
    //  Modal editar usuario
    const modalEditar = document.getElementById("modal_editar_usuario");
    const btnCerrarEditar = document.getElementById("btn_cerrar_modal_editar");
    const btnCancelEditar = document.getElementById("btn_cancelar_modal_editar");
    const editarTipo = document.getElementById("editar_tipo_usuario");
    const editarId = document.getElementById("editar_id_usuario");
    const editarIdDisplay = document.getElementById("editar_id_display");
    const editarNombres = document.getElementById("editar_nombres_usuario");
    const editarApellidos = document.getElementById("editar_apellidos_usuario");
    const editarExtra = document.getElementById("editar_extra_usuario");
    const labelEditarExtra = document.getElementById("label_editar_extra");
    const grupoEditarExtra = document.getElementById("grupo_editar_extra");
    const grupoEditarDirector = document.getElementById("grupo_editar_director");
    const editarDirector = document.getElementById("editar_director_docente");

    document.querySelectorAll(".btn_trigger_editar_usuario").forEach(btn => {
        btn.addEventListener("click", function() {
            const tipo = this.dataset.tipo;
            editarTipo.value = tipo;
            editarId.value = this.dataset.id;
            editarIdDisplay.value = this.dataset.id;
            editarNombres.value = this.dataset.nombres || "";
            editarApellidos.value = this.dataset.apellidos || "";
            editarExtra.value = this.dataset.extra || "";

            if (tipo === "estudiante") {
                grupoEditarExtra.style.display = "flex";
                labelEditarExtra.textContent = "Grado escolar";
                editarExtra.placeholder = "Ej: 11°1";
                grupoEditarDirector.style.display = "none";
            } else if (tipo === "docente") {
                grupoEditarExtra.style.display = "none";
                grupoEditarDirector.style.display = "flex";
                editarDirector.checked = (this.dataset.director === "1");
            } else {
                grupoEditarExtra.style.display = "flex";
                labelEditarExtra.textContent = "Cargo o Función";
                editarExtra.placeholder = "Ej: Bibliotecario";
                grupoEditarDirector.style.display = "none";
            }

            modalEditar.classList.add("modal_activo");
        });
    });

    if (btnCerrarEditar) btnCerrarEditar.addEventListener("click", () => modalEditar.classList.remove("modal_activo"));
    if (btnCancelEditar) btnCancelEditar.addEventListener("click", () => modalEditar.classList.remove("modal_activo"));

    //  Modal cambiar clave
    const modalClave = document.getElementById("modal_cambiar_clave_admin");
    const btnCerrarClave = document.getElementById("btn_cerrar_modal_clave");
    const btnCancelClave = document.getElementById("btn_cancelar_modal_clave");
    const claveId = document.getElementById("clave_id_usuario");
    const claveTipo = document.getElementById("clave_tipo_usuario");
    const claveNombre = document.getElementById("clave_nombre_usuario");
    const claveIdTexto = document.getElementById("clave_id_texto");

    document.querySelectorAll(".btn_trigger_cambiar_clave").forEach(btn => {
        btn.addEventListener("click", function() {
            claveId.value = this.dataset.id;
            claveTipo.value = this.dataset.tipo;
            claveNombre.textContent = this.dataset.nombre;
            claveIdTexto.textContent = this.dataset.id;
            modalClave.classList.add("modal_activo");
        });
    });

    if (btnCerrarClave) btnCerrarClave.addEventListener("click", () => modalClave.classList.remove("modal_activo"));
    if (btnCancelClave) btnCancelClave.addEventListener("click", () => modalClave.classList.remove("modal_activo"));

    //  Modal borrar usuario
    const modalBorrar = document.getElementById("modal_borrar_usuario");
    const btnCerrarBorrar = document.getElementById("btn_cerrar_modal_borrar");
    const btnCancelBorrar = document.getElementById("btn_cancelar_modal_borrar");
    const borrarId = document.getElementById("borrar_id_usuario");
    const borrarTipo = document.getElementById("borrar_tipo_usuario");
    const borrarNombre = document.getElementById("borrar_nombre_usuario");
    const avisoPrestamos = document.getElementById("aviso_prestamos_bloqueo");
    const borrarCantPrestamos = document.getElementById("borrar_cant_prestamos");
    const btnConfirmarBorrar = document.getElementById("btn_confirmar_borrar");

    document.querySelectorAll(".btn_trigger_borrar_usuario").forEach(btn => {
        btn.addEventListener("click", function() {
            borrarId.value = this.dataset.id;
            borrarTipo.value = this.dataset.tipo;
            borrarNombre.textContent = this.dataset.nombre;
            const prestamos = parseInt(this.dataset.prestamos || "0", 10);

            if (prestamos > 0) {
                avisoPrestamos.style.display = "block";
                borrarCantPrestamos.textContent = prestamos;
                btnConfirmarBorrar.disabled = true;
                btnConfirmarBorrar.style.opacity = "0.5";
                btnConfirmarBorrar.style.cursor = "not-allowed";
            } else {
                avisoPrestamos.style.display = "none";
                btnConfirmarBorrar.disabled = false;
                btnConfirmarBorrar.style.opacity = "1";
                btnConfirmarBorrar.style.cursor = "pointer";
            }

            modalBorrar.classList.add("modal_activo");
        });
    });

    if (btnCerrarBorrar) btnCerrarBorrar.addEventListener("click", () => modalBorrar.classList.remove("modal_activo"));
    if (btnCancelBorrar) btnCancelBorrar.addEventListener("click", () => modalBorrar.classList.remove("modal_activo"));

    //  Modal nuevo usuario
    const modalNuevo = document.getElementById("modal_nuevo_usuario");
    const btnAbrirNuevo = document.getElementById("btn_abrir_nuevo_usuario");
    const btnCerrarNuevo = document.getElementById("btn_cerrar_modal_nuevo");
    const btnCancelNuevo = document.getElementById("btn_cancelar_modal_nuevo");
    const selectTipoNuevo = document.getElementById("nuevo_tipo_select");
    const grupoExtra = document.getElementById("grupo_extra");
    const labelExtra = document.getElementById("label_extra_nuevo");
    const inputExtra = document.getElementById("input_extra_nuevo");
    const grupoDirector = document.getElementById("grupo_director_docente");

    function actualizarCamposNuevo() {
        if (!selectTipoNuevo) return;
        const val = selectTipoNuevo.value;
        if (val === "estudiante") {
            grupoExtra.style.display = "flex";
            labelExtra.textContent = "Grado escolar";
            inputExtra.placeholder = "Ej: 11°1";
            grupoDirector.style.display = "none";
        } else if (val === "docente") {
            grupoExtra.style.display = "none";
            grupoDirector.style.display = "flex";
        } else {
            grupoExtra.style.display = "flex";
            labelExtra.textContent = "Cargo o Función";
            inputExtra.placeholder = "Ej: Bibliotecario principal";
            grupoDirector.style.display = "none";
        }
    }

    if (selectTipoNuevo) selectTipoNuevo.addEventListener("change", actualizarCamposNuevo);
    actualizarCamposNuevo();

    if (btnAbrirNuevo) btnAbrirNuevo.addEventListener("click", () => modalNuevo.classList.add("modal_activo"));
    if (btnCerrarNuevo) btnCerrarNuevo.addEventListener("click", () => modalNuevo.classList.remove("modal_activo"));
    if (btnCancelNuevo) btnCancelNuevo.addEventListener("click", () => modalNuevo.classList.remove("modal_activo"));

    // no obligar a cerrar solo con la x dale a cualquier lado obviamente afuera
    [modalEditar, modalClave, modalBorrar, modalNuevo].forEach(m => {
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
