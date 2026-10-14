<?php
session_start();
require "config.php";

$error = "";
$mensaje_ok = "";
if (!empty($_SESSION["mensaje_registro"])) {
    $mensaje_ok = $_SESSION["mensaje_registro"];
    unset($_SESSION["mensaje_registro"]);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!validar_csrf()) {
        $error = "Petición no válida. Por favor recarga la página e intenta de nuevo.";
    } else {
        $usuario = trim($_POST["usuario"] ?? "");
        $clave = $_POST["clave"] ?? "";
        $tipo = $_POST["tipo"] ?? "";

        if ($tipo === "administrador") {
            $stmt = $pdo->prepare("SELECT * FROM administradores WHERE cedula = ?");
            $stmt->execute([$usuario]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && verificar_y_migrar_clave($clave, $row["clave"], $pdo, "administradores", "cedula", $row["cedula"])) {
                session_regenerate_id(true);
                $_SESSION["rol"] = "administrador";
                $_SESSION["id"] = $row["cedula"];
                $_SESSION["nombre"] = $row["nombre"];
                header("Location: libros.php");
                exit;
            } else {
                $error = "Credenciales incorrectas.";
            }
        } elseif ($tipo === "estudiante") {
            $stmt = $pdo->prepare("SELECT * FROM estudiantes WHERE numero_matricula = ?");
            $stmt->execute([$usuario]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && verificar_y_migrar_clave($clave, $row["clave"], $pdo, "estudiantes", "numero_matricula", $row["numero_matricula"])) {
                session_regenerate_id(true);
                $_SESSION["rol"] = "estudiante";
                $_SESSION["id"] = $row["numero_matricula"];
                $_SESSION["nombre"] = $row["nombres"];
                header("Location: prestamos.php");
                exit;
            } else {
                $error = "Credenciales incorrectas.";
            }
        } elseif ($tipo === "docente") {
            $stmt = $pdo->prepare("SELECT * FROM docentes WHERE cedula = ?");
            $stmt->execute([$usuario]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && verificar_y_migrar_clave($clave, $row["clave"], $pdo, "docentes", "cedula", $row["cedula"])) {
                session_regenerate_id(true);
                $_SESSION["rol"] = "docente";
                $_SESSION["id"] = $row["cedula"];
                $_SESSION["nombre"] = $row["nombre"];
                header("Location: prestamos.php");
                exit;
            } else {
                $error = "Credenciales incorrectas.";
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
    <title>Cercatori - Acceder</title>
    <link rel="stylesheet" href="css/index.css">
</head>
<body>

<?php include "includes/header.php"; ?>

<main class="sesion_main">
    <div class="sesion_contenedor">

        <section class="login-box">
            <h2>Acceder</h2>
            <div class="sesion_divider"></div>

            <?php if ($mensaje_ok): ?>
                <p class="mensaje_ok" style="margin-bottom: 1.25rem; font-weight: 500; border-radius: 10px;"><?= htmlspecialchars($mensaje_ok) ?></p>
            <?php endif; ?>
            <?php if ($error): ?>
                <p class="mensaje_error"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <form method="POST">
                <?= csrf_campo() ?>
                <label>Tipo de usuario</label>
                <select name="tipo" required>
                    <option value="estudiante">Estudiante</option>
                    <option value="docente">Docente</option>
                    <option value="administrador">Administrador</option>
                </select>

                <label>Usuario (cédula o matrícula)</label>
                <input type="text" name="usuario" placeholder="Cédula o matrícula" required>

                <label>Contraseña</label>
                <div class="password-box">
                    <input type="password" name="clave" placeholder="••••••••" required>
                    <span class="icono ti ti-eye"></span>
                </div>

                <button type="submit">Ingresar</button>
            </form>
            <p><a href="recuperar.php" class="link_espacio">¿Olvidaste tu contraseña?</a></p>
        </section>

        <div class="sesion_separador"></div>

        <section class="register-box">
            <h2>Registrarse</h2>
            <div class="sesion_divider"></div>
            <p>¿Aún no tienes cuenta? Crea una en segundos y accede a los préstamos de la biblioteca.</p>
            <button class="btn_registro" onclick="location.href='registro.php'">Crear cuenta</button>
        </section>

    </div>
</main>

<?php include "includes/footer.php"; ?>

<script src="js/index.js"></script>
</body>
</html>
