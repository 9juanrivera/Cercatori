<?php
require "config.php";

$paso = "buscar";
$error = "";
$mensaje = "";
$pregunta = "";
$tipo = $_POST["tipo"] ?? "";
$id = $_POST["id"] ?? "";

function tabla_y_campo($tipo) {
    if ($tipo === "administrador") return ["administradores", "cedula"];
    if ($tipo === "docente")       return ["docentes", "cedula"];
    return ["estudiantes", "numero_matricula"];
}

// primero le pregunto a la db la pregunta secreta de la gente y si existe lo deja pasar al formulario
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["buscar_pregunta"])) {
    if (!validar_csrf()) {
        $error = "Petición no válida. Por favor recarga la página.";
    } else {
        [$tabla, $campo] = tabla_y_campo($tipo);
        $stmt = $pdo->prepare("SELECT pregunta_secreta FROM $tabla WHERE $campo = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || trim($row["pregunta_secreta"] ?? "") === "") {
            $error = "No encontramos esa cuenta o no tiene pregunta secreta configurada.";
        } else {
            $pregunta = $row["pregunta_secreta"];
            $paso = "responder";
        }
    }
}

// despues si la cosa si es pues la cambia la clave y ya
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["cambiar_clave"])) {
    if (!validar_csrf()) {
        $error = "Petición no válida. Por favor recarga la página.";
        $paso = "buscar";
    } else {
        $respuesta = strtolower(trim($_POST["respuesta_secreta"] ?? ""));
        $nueva_clave = $_POST["nueva_clave"] ?? "";
        [$tabla, $campo] = tabla_y_campo($tipo);

        if (strlen($nueva_clave) < 4) {
            $error = "La nueva contraseña debe tener al menos 4 caracteres.";
            $pregunta = $_POST["pregunta_texto"] ?? "";
            $paso = "responder";
        } else {
            $stmt = $pdo->prepare("SELECT respuesta_secreta, pregunta_secreta FROM $tabla WHERE $campo = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row || strtolower(trim($row["respuesta_secreta"] ?? "")) !== $respuesta) {
                $error = "Respuesta incorrecta.";
                $pregunta = $row["pregunta_secreta"] ?? "";
                $paso = "responder";
            } else {
                $hash_nueva = password_hash($nueva_clave, PASSWORD_DEFAULT);
                $upd = $pdo->prepare("UPDATE $tabla SET clave = ? WHERE $campo = ?");
                $upd->execute([$hash_nueva, $id]);
                $mensaje = "Contraseña actualizada con éxito. Ya puedes iniciar sesión.";
                $paso = "listo";
            }
        }
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
    <title>Cercatori - Recuperar contraseña</title>
    <link rel="stylesheet" href="css/index.css">
</head>
<body>

<?php include "includes/header.php"; ?>

<main class="sesion_main">
    <div class="sesion_contenedor sesion_contenedor_sola">

        <section class="login-box">
            <h2>Recuperar contraseña</h2>
            <div class="sesion_divider"></div>

            <?php if ($error): ?>
                <p class="mensaje_error"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
            <?php if ($mensaje): ?>
                <p class="mensaje_ok"><?= htmlspecialchars($mensaje) ?></p>
            <?php endif; ?>

            <?php if ($paso === "buscar"): ?>
                <form method="POST">
                    <?= csrf_campo() ?>
                    <label>Tipo de usuario</label>
                    <select name="tipo" required>
                        <option value="estudiante">Estudiante</option>
                        <option value="docente">Docente</option>
                        <option value="administrador">Administrador</option>
                    </select>

                    <label>ID (cédula o matrícula)</label>
                    <input type="text" name="id" required>

                    <button type="submit" name="buscar_pregunta">Continuar</button>
                </form>

            <?php elseif ($paso === "responder"): ?>
                <p><strong><?= htmlspecialchars($pregunta) ?></strong></p>
                <form method="POST">
                    <?= csrf_campo() ?>
                    <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipo) ?>">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
                    <input type="hidden" name="pregunta_texto" value="<?= htmlspecialchars($pregunta) ?>">

                    <label>Respuesta secreta</label>
                    <input type="text" name="respuesta_secreta" required>

                    <label>Nueva contraseña</label>
                    <div class="password-box">
                        <input type="password" name="nueva_clave" placeholder="••••••••" required>
                        <span class="icono ti ti-eye"></span>
                    </div>

                    <button type="submit" name="cambiar_clave">Cambiar contraseña</button>
                </form>

            <?php else: ?>
                <p><a href="login.php" class="link_espacio">Ir a iniciar sesión</a></p>
            <?php endif; ?>
        </section>

    </div>
</main>

<?php include "includes/footer.php"; ?>

<script src="js/index.js"></script>
</body>
</html>
