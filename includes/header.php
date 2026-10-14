<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$logueado = isset($_SESSION["rol"]);
$icono_destino = $logueado ? "mi_espacio.php" : "login.php";

// Las notificaciones necesitan conexion a la base de datos.
// Si la pagina que incluye este header ya cargo config.php  se reutiliza $pdo.
if (!isset($pdo)) {
    require_once __DIR__ . "/../config.php";
}
require_once __DIR__ . "/notificaciones.php";
$notificaciones = obtener_notificaciones($pdo);
?>
<script>
    (function() {
        if (localStorage.getItem("tema") === "oscuro") {
            document.documentElement.classList.add("modo_oscuro");
        }
    })();
</script>
<?php
$foto_header = null;
if ($logueado && isset($_SESSION["id"]) && isset($_SESSION["rol"])) {
    $rol_hdr = $_SESSION["rol"];
    $id_hdr = $_SESSION["id"];
    if ($rol_hdr === "estudiante") {
        $stmt_hdr = $pdo->prepare("SELECT foto_perfil FROM estudiantes WHERE numero_matricula = ?");
    } elseif ($rol_hdr === "docente") {
        $stmt_hdr = $pdo->prepare("SELECT foto_perfil FROM docentes WHERE cedula = ?");
    } else {
        $stmt_hdr = $pdo->prepare("SELECT foto_perfil FROM administradores WHERE cedula = ?");
    }
    $stmt_hdr->execute([$id_hdr]);
    $foto_val = $stmt_hdr->fetchColumn();
    if ($foto_val && file_exists(__DIR__ . "/../img/perfiles/" . $foto_val)) {
        $foto_header = "img/perfiles/" . $foto_val;
    }
}
?>
<header>

    <div class="logo_wrapper">
        <img src="img/cercatori.png" alt="Cercatori logo image" class="logo_img" onclick="location.href='index.php'">
        <span class="logo-subtitle">Biblioteca <br> Virtual</span>
    </div>

    <nav>
        <a href="index.php" <?= ($active ?? "") === "inicio" ? 'class="active"' : "" ?>>Inicio</a>
        <a href="nosotros.php" <?= ($active ?? "") === "nosotros" ? 'class="active"' : "" ?>>Nosotros</a>
        <a href="contactanos.php" <?= ($active ?? "") === "contacto" ? 'class="active"' : "" ?>>Contáctanos</a>
        <a href="materias.php" <?= ($active ?? "") === "materias" ? 'class="active"' : "" ?>>Materias</a>
        <a href="mi_espacio.php" <?= ($active ?? "") === "espacio" ? 'class="active"' : "" ?>>Mi Espacio</a>
    </nav>

    <div class="header-icons">

        <div class="buscador_header">
            <i class="ti ti-search" data-toggle="buscador"></i>
            <form class="buscador_header_form" action="prestamos.php" method="GET">
                <input type="text" name="q" placeholder="Busca un libro por nombre, autor o materia...">
            </form>
        </div>

        <div class="notificaciones_header">
            <i class="ti ti-bell<?= $notificaciones["urgente"] ? " campana_urgente" : "" ?>" data-toggle="notificaciones"></i>
            <?php if ($notificaciones["total"] > 0): ?>
                <span class="badge_notificacion"><?= $notificaciones["total"] ?></span>
            <?php endif; ?>

            <div class="panel_notificaciones">
                <p class="panel_notificaciones_titulo">Notificaciones</p>

                <?php if (!$logueado): ?>
                    <p class="panel_notificaciones_vacio">Inicia sesión para ver tus préstamos y avisos.</p>
                <?php elseif ($notificaciones["total"] === 0): ?>
                    <p class="panel_notificaciones_vacio">No tienes notificaciones pendientes.</p>
                <?php else: ?>
                    <?php foreach ($notificaciones["items"] as $n): ?>
                        <a href="<?= htmlspecialchars($n["link"]) ?>" class="item_notificacion item_notificacion_<?= $n["tipo"] ?>">
                            <i class="ti <?= $n["tipo"] === "vencido" ? "ti-alert-triangle" : ($n["tipo"] === "por_vencer" ? "ti-clock-exclamation" : "ti-info-circle") ?>"></i>
                            <span><?= htmlspecialchars($n["texto"]) ?></span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($foto_header): ?>
            <img src="<?= htmlspecialchars($foto_header) ?>" alt="Foto de perfil" class="header_avatar_mini" onclick="location.href='<?= $icono_destino ?>'" title="Mi Espacio">
        <?php else: ?>
            <i class="ti ti-user-circle" onclick="location.href='<?= $icono_destino ?>'" title="<?= $logueado ? 'Mi Espacio' : 'Iniciar sesión' ?>"></i>
        <?php endif; ?>

        <i class="ti ti-moon" id="btn_dark_mode" title="Cambiar tema"></i>
    </div>

</header>
