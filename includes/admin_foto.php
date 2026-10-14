<?php
/*
 * Foto de perfil del administrador.
 * Debe incluirse al inicio de cada página de admin, ANTES de imprimir HTML,
 * porque procesa POST y redirige.
 *
 * Expone:
 *   $admin_foto_actual  -> ruta web de la foto (o null)
 *   $admin_foto_mensaje -> texto de resultado (o "")
 *   $admin_foto_tipo    -> "ok" | "error"
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($pdo)) {
    require_once __DIR__ . "/../config.php";
}

$admin_foto_actual  = null;
$admin_foto_mensaje = "";
$admin_foto_tipo    = "ok";

if (($_SESSION["rol"] ?? "") === "administrador" && isset($_SESSION["id"])) {

    $admin_id  = $_SESSION["id"];
    $admin_dir = __DIR__ . "/../img/perfiles/";

    // subir o actualizar la foto comprueba la antigua si hay la remplaza y si no pone una nueva para evitar errores
    if (($_SERVER["REQUEST_METHOD"] ?? "") === "POST" && isset($_POST["admin_subir_foto"])) {
        if (!validar_csrf()) {
            $admin_foto_mensaje = "Petición rechazada por validación de seguridad (CSRF).";
            $admin_foto_tipo    = "error";
        } elseif (isset($_FILES["admin_foto"]) && $_FILES["admin_foto"]["error"] === UPLOAD_ERR_OK) {
            $file_tmp  = $_FILES["admin_foto"]["tmp_name"];
            $file_size = $_FILES["admin_foto"]["size"];
            $ext = strtolower(pathinfo($_FILES["admin_foto"]["name"], PATHINFO_EXTENSION));

            $permitidas       = ["jpg", "jpeg", "png", "webp", "gif"];
            $mimes_permitidos = ["image/jpeg", "image/png", "image/webp", "image/gif"];

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $file_tmp);
            finfo_close($finfo);

            if (!in_array($ext, $permitidas) || !in_array($mime, $mimes_permitidos)) {
                $admin_foto_mensaje = "Formato no permitido. Usa JPG, PNG, WEBP o GIF.";
                $admin_foto_tipo    = "error";
            } elseif ($file_size > 3 * 1024 * 1024) {
                $admin_foto_mensaje = "La imagen supera el límite de 3MB.";
                $admin_foto_tipo    = "error";
            } else {
                if (!is_dir($admin_dir)) {
                    @mkdir($admin_dir, 0777, true);
                }

                $stmt_old = $pdo->prepare("SELECT foto_perfil FROM administradores WHERE cedula = ?");
                $stmt_old->execute([$admin_id]);
                $foto_anterior = $stmt_old->fetchColumn();

                $nuevo_archivo = "perfil_admin_" . preg_replace('/[^a-zA-Z0-9_-]/', '', $admin_id) . "_" . time() . "." . $ext;

                if (move_uploaded_file($file_tmp, $admin_dir . $nuevo_archivo)) {
                    if ($foto_anterior && file_exists($admin_dir . $foto_anterior)) {
                        @unlink($admin_dir . $foto_anterior);
                    }
                    $pdo->prepare("UPDATE administradores SET foto_perfil = ? WHERE cedula = ?")
                        ->execute([$nuevo_archivo, $admin_id]);

                    $_SESSION["flash_foto"] = "Foto de perfil actualizada.";
                    header("Location: " . basename($_SERVER["PHP_SELF"]));
                    exit;
                }

                $admin_foto_mensaje = "No se pudo guardar la imagen. Intenta de nuevo.";
                $admin_foto_tipo    = "error";
            }
        } else {
            $admin_foto_mensaje = "Selecciona un archivo de imagen válido.";
            $admin_foto_tipo    = "error";
        }
    }

    // borrar la foto pero no para aactualizarla solo borrarla
    if (($_SERVER["REQUEST_METHOD"] ?? "") === "POST" && isset($_POST["admin_eliminar_foto"])) {
        if (!validar_csrf()) {
            $admin_foto_mensaje = "Petición rechazada por validación de seguridad (CSRF).";
            $admin_foto_tipo    = "error";
        } else {
            $stmt_old = $pdo->prepare("SELECT foto_perfil FROM administradores WHERE cedula = ?");
            $stmt_old->execute([$admin_id]);
            $foto_anterior = $stmt_old->fetchColumn();

            if ($foto_anterior && file_exists($admin_dir . $foto_anterior)) {
                @unlink($admin_dir . $foto_anterior);
            }
            $pdo->prepare("UPDATE administradores SET foto_perfil = NULL WHERE cedula = ?")
                ->execute([$admin_id]);

            $_SESSION["flash_foto"] = "Foto de perfil eliminada.";
            header("Location: " . basename($_SERVER["PHP_SELF"]));
            exit;
        }
    }

    //  mensaje de redirreccion
    if (!empty($_SESSION["flash_foto"])) {
        $admin_foto_mensaje = $_SESSION["flash_foto"];
        $admin_foto_tipo    = "ok";
        unset($_SESSION["flash_foto"]);
    }

    // foto actual
    $stmt_foto = $pdo->prepare("SELECT foto_perfil FROM administradores WHERE cedula = ?");
    $stmt_foto->execute([$admin_id]);
    $foto_bd = $stmt_foto->fetchColumn();

    if ($foto_bd && file_exists($admin_dir . $foto_bd)) {
        $admin_foto_actual = "img/perfiles/" . $foto_bd;
    }
}
