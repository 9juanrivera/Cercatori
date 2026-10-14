<?php
session_start();
require "config.php";
$mensaje = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!validar_csrf()) {
        $error = "Petición no válida. Por favor recarga la página e intenta de nuevo.";
    } else {
        $tipo = $_POST["tipo"] ?? "";
        $id = trim($_POST["id"] ?? "");
        $clave = $_POST["clave"] ?? "";
        $nombre = trim($_POST["nombre"] ?? "");
        $apellidos = trim($_POST["apellidos"] ?? "");
        $extra = trim($_POST["extra"] ?? "");
        $pregunta = trim($_POST["pregunta_secreta"] ?? "");
        $respuesta = strtolower(trim($_POST["respuesta_secreta"] ?? ""));

        if ($tipo === "administrador" && trim($_POST["codigo_admin"] ?? "") !== CODIGO_ADMIN) {
            $error = "Código de administrador incorrecto.";
        } elseif ($tipo === "docente" && trim($_POST["codigo_docente"] ?? "") !== CODIGO_DOCENTE) {
            $error = "Código de docente incorrecto. Solicítalo a la dirección del colegio.";
        } elseif (empty($id) || empty($clave) || empty($nombre)) {
            $error = "Todos los campos marcados como obligatorios deben completarse.";
        } elseif (strlen($clave) < 4) {
            $error = "La contraseña debe tener al menos 4 caracteres.";
        } elseif (id_existe_en_sistema($pdo, $id)) {
            $error = "El documento o número de matrícula ingresado ya está registrado. Si olvidaste tu contraseña, puedes recuperarla.";
        } else {
            $clave_hash = password_hash($clave, PASSWORD_DEFAULT);
            try {
                if ($tipo === "administrador") {
                    $stmt = $pdo->prepare("INSERT INTO administradores (cedula, clave, nombre, apellidos, cargo, pregunta_secreta, respuesta_secreta) VALUES (?,?,?,?,?,?,?)");
                    $stmt->execute([$id, $clave_hash, $nombre, $apellidos, $extra, $pregunta, $respuesta]);
                    $_SESSION["mensaje_registro"] = "¡Cuenta de administrador creada exitosamente! Ya puedes acceder con tus credenciales.";
                    header("Location: login.php");
                    exit;
                } elseif ($tipo === "estudiante") {
                    $stmt = $pdo->prepare("INSERT INTO estudiantes (numero_matricula, clave, nombres, apellidos, grado, pregunta_secreta, respuesta_secreta) VALUES (?,?,?,?,?,?,?)");
                    $stmt->execute([$id, $clave_hash, $nombre, $apellidos, $extra, $pregunta, $respuesta]);
                    $_SESSION["mensaje_registro"] = "¡Cuenta de estudiante creada exitosamente! Ya puedes iniciar sesión.";
                    header("Location: login.php");
                    exit;
                } elseif ($tipo === "docente") {
                    $stmt = $pdo->prepare("INSERT INTO docentes (cedula, clave, nombre, apellidos, director_grupo, pregunta_secreta, respuesta_secreta) VALUES (?,?,?,?,?,?,?)");
                    $stmt->execute([$id, $clave_hash, $nombre, $apellidos, isset($_POST["director"]) ? 1 : 0, $pregunta, $respuesta]);
                    $_SESSION["mensaje_registro"] = "¡Cuenta de docente creada exitosamente! Ya puedes iniciar sesión.";
                    header("Location: login.php");
                    exit;
                }
            } catch (PDOException $e) {
                error_log("Error en registro: " . $e->getMessage());
                if (strpos($e->getMessage(), "1062") !== false || strpos($e->getMessage(), "Duplicate") !== false) {
                    $error = "El documento o número de matrícula ingresado ya se encuentra registrado.";
                } else {
                    $error = "Ocurrió un error al registrar la cuenta. Por favor intenta más tarde.";
                }
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
    <title>Cercatori - Registro</title>
    <link rel="stylesheet" href="css/index.css">
</head>
<body>

<?php include "includes/header.php"; ?>

<main class="sesion_main">
    <div class="sesion_contenedor sesion_contenedor_sola">

        <section class="register-box">
            <h2>Registrarse</h2>
            <div class="sesion_divider"></div>

            <?php if ($mensaje): ?>
                <p class="mensaje_ok"><?= htmlspecialchars($mensaje) ?></p>
            <?php endif; ?>
            <?php if ($error): ?>
                <p class="mensaje_error"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <form method="POST">
                <?= csrf_campo() ?>
                <label>Tipo de usuario</label>
                <select name="tipo" id="tipo_registro" required>
                    <option value="estudiante">Estudiante</option>
                    <option value="docente">Docente</option>
                    <option value="administrador">Administrador</option>
                </select>

                <label id="label_codigo" class="oculto">Código de administrador</label>
                <input type="text" id="input_codigo" name="codigo_admin" class="oculto" placeholder="Solicítalo a la dirección">

                <label id="label_codigo_docente" class="oculto">Código de docente</label>
                <input type="text" id="input_codigo_docente" name="codigo_docente" class="oculto" placeholder="Solicítalo a la dirección">

                <label>ID (cédula o matrícula)</label>
                <input type="text" name="id" required>

                <label>Contraseña</label>
                <div class="password-box">
                    <input type="password" name="clave" placeholder="••••••••" required>
                    <span class="icono ti ti-eye"></span>
                </div>

                <label>Nombre</label>
                <input type="text" name="nombre" required>

                <label>Apellidos</label>
                <input type="text" name="apellidos">

                <label id="label_extra">Cargo / Grado</label>
                <input type="text" name="extra" placeholder="Grado o cargo según tu rol">

                <label id="label_director" class="oculto">
                    <input type="checkbox" name="director" class="checkbox_inline">
                    Director de grupo
                </label>

                <label>Pregunta secreta</label>
                <input type="text" name="pregunta_secreta" placeholder="Ej: ¿Nombre de tu primera mascota?" required>

                <label>Respuesta secreta</label>
                <input type="text" name="respuesta_secreta" placeholder="Úsala para recuperar tu clave" required>

                <button type="submit" class="btn_registro">Registrarse</button>
            </form>

            <p>¿Ya tienes cuenta? <a href="login.php" class="link_espacio">Inicia sesión aquí</a>.</p>
        </section>

    </div>
</main>

<?php include "includes/footer.php"; ?>

<script src="js/index.js"></script>
</body>
</html>
