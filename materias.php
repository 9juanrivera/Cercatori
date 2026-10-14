<?php
require "config.php";

// Traer materias con conteo real de libros y disponibilidad dinámica
$materias = $pdo->query("
    SELECT
        COALESCE(NULLIF(TRIM(l.materia), ''), 'Sin categoría') AS materia,
        SUM(l.cantidad) AS disponibles,
        COALESCE(SUM(p.prestados), 0) AS prestados,
        SUM(l.cantidad) + COALESCE(SUM(p.prestados), 0) AS total
    FROM libros l
    LEFT JOIN (
        SELECT isbn, COUNT(*) AS prestados
        FROM prestamos
        WHERE devuelto = 0
        GROUP BY isbn
    ) p ON p.isbn = l.isbn
    GROUP BY COALESCE(NULLIF(TRIM(l.materia), ''), 'Sin categoría')
    ORDER BY total DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas generales para el encabezado
$stats = $pdo->query("
    SELECT
        (SELECT SUM(cantidad) FROM libros) AS total_disponibles,
        (SELECT COUNT(*) FROM prestamos WHERE devuelto = 0) AS total_prestados,
        (SELECT SUM(cantidad) FROM libros) + (SELECT COUNT(*) FROM prestamos WHERE devuelto = 0) AS total_libros
")->fetch(PDO::FETCH_ASSOC);

// Mapa de íconos por nombre de materia (si no coincide, usa ícono genérico)
$iconos = [
    "Matemáticas" => "ti-math-function",
    "Física" => "ti-atom-2",
    "Química" => "ti-flask",
    "Biología" => "ti-dna-2",
    "Historia" => "ti-hourglass",
    "Geografía" => "ti-map",
    "Literatura" => "ti-books",
    "Inglés" => "ti-language",
    "Cívica" => "ti-scale",
    "Educación Física" => "ti-ball-basketball",
    "Ciencias Naturales" => "ti-leaf",
    "Sin categoría" => "ti-category-2"
];

function icono_de($materia, $mapa) {
    return $mapa[$materia] ?? "ti-book-2";
}

$active = "materias";
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
    <title>Cercatori - Materias</title>
    <link rel="stylesheet" href="css/index.css">
</head>
<body>

<?php include "includes/header.php"; ?>

    <main class="materias_section">

        <div class="materias_hero">
            <h1>Materias</h1>
            <p class="materias_subtitle">Explora la biblioteca por materia y encuentra justo lo que necesitas.</p>
        </div>

        <div class="stats_barra">
            <div class="stat_mini">
                <span class="num"><?= (int)$stats["total_libros"] ?></span>
                <span class="label">Libros en total</span>
            </div>
            <div class="stat_mini">
                <span class="num"><?= (int)$stats["total_disponibles"] ?></span>
                <span class="label">Disponibles ahora</span>
            </div>
            <div class="stat_mini">
                <span class="num"><?= count($materias) ?></span>
                <span class="label">Materias distintas</span>
            </div>
        </div>

        <form class="buscador_materias" action="prestamos.php" method="GET">
            <input type="text" name="q" placeholder="Busca un libro por nombre, autor o materia...">
            <button type="submit"><i class="ti ti-search"></i> Buscar</button>
        </form>

        
        <div class="categorias_select_rapido">
            <select id="select_materia_rapida" onchange="if(this.value) location.href=this.value;">
                <option value="">Elección rápida</option>
                <?php foreach ($materias as $m): ?>
                    <option value="prestamos.php?q=<?= urlencode($m["materia"] === "Sin categoría" ? "" : $m["materia"]) ?>">
                        <?= htmlspecialchars($m["materia"]) ?> — (<?= (int)$m["disponibles"] ?> disponibles de <?= (int)$m["total"] ?> libros)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

       
        <button type="button" class="btn_toggle_categorias btn_toggle_historial" data-target="drawer_materias_grid">
            <span style="display: flex; align-items: center; gap: 10px;">
                <i class="ti ti-category-2" style="font-size: 20px; color: #16a34a;"></i>
                <span>Ver todas las categorías en cuadrícula (<?= count($materias) ?>)</span>
            </span>
            <i class="ti ti-chevron-down flecha_toggle"></i>
        </button>

        
        <div id="drawer_materias_grid" class="categorias_drawer drawer_cerrado">
            <div class="grid_materias">
                <?php foreach ($materias as $m): ?>
                    <?php
                    $porcentaje = $m["total"] > 0 ? round(($m["disponibles"] / $m["total"]) * 100) : 0;
                    ?>
                    <a href="prestamos.php?q=<?= urlencode($m["materia"] === "Sin categoría" ? "" : $m["materia"]) ?>" class="tarjeta_materia">
                        <i class="ti <?= icono_de($m["materia"], $iconos) ?>"></i>
                        <span class="nombre"><?= htmlspecialchars($m["materia"]) ?></span>
                        <span class="conteo"><?= (int)$m["disponibles"] ?> de <?= (int)$m["total"] ?> disponibles</span>
                        <div class="barra_disp"><span data-percent="<?= $porcentaje ?>"></span></div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <hr class="divisor_seccion">

        <p class="titulo_accesos">Accesos rápidos</p>

        <div class="acciones_grid">
            <a href="mi_espacio.php" class="accion_btn">
                <i class="ti ti-book"></i>
                <span>Reservar</span>
            </a>
            <a href="computadores.php" class="accion_btn">
                <i class="ti ti-device-laptop"></i>
                <span>Computadores</span>
            </a>
            <a href="mi_espacio.php" class="accion_btn">
                <i class="ti ti-book-upload"></i>
                <span>Devolver</span>
            </a>
            <a href="juegos.php" class="accion_btn">
                <i class="ti ti-puzzle"></i>
                <span>Juegos</span>
            </a>
        </div>

    </main>

<?php include "includes/footer.php"; ?>

    <script src="js/index.js"></script>
</body>
</html>