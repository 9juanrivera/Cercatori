<?php
$active = "inicio";
require_once "config.php";

$slides_carrusel = [];
try {
    $stmt_carrusel = $pdo->query("SELECT * FROM carrusel WHERE activo = 1 ORDER BY orden ASC, id ASC");
    $slides_carrusel = $stmt_carrusel->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Si la tabla no existe o error temporal
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
    <title>Cercatori</title>
    <link rel="stylesheet" href="css/index.css">
</head>
<body>

<?php include "includes/header.php"; ?>

    <main>

        <div class="welcome">
            <h1 class="welcome_title">Bienvenido a Cercatori</h1>
            <div class="team-divider"></div>
        </div>

        <section class="hero">
            <div class="carousel">
                <div class="carousel-track" id="track">

                    <?php if (!empty($slides_carrusel)): ?>
                        <?php foreach ($slides_carrusel as $s): ?>
                            <div class="carousel-slide">
                                <img src="img/Carrusel/<?= htmlspecialchars($s["imagen"]) ?>" alt="Biblioteca">
                                <div class="overlay">
                                    <p class="quote">"<?= htmlspecialchars($s["frase"]) ?>"</p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="carousel-slide">
                            <img src="img/Carrusel/Imagen1.jpeg" alt="Biblioteca">
                            <div class="overlay">
                                <p class="quote">"Una biblioteca no es un conjunto de libros leídos, sino una compañía, un refugio y un proyecto de vida"</p>
                            </div>
                        </div>

                        <div class="carousel-slide">
                            <img src="img/Carrusel/Imagen2.jpeg" alt="Biblioteca">
                            <div class="overlay">
                                <p class="quote">"Los libros son espejos: solo te ves en ellos si ya estás dentro."</p>
                            </div>
                        </div>

                        <div class="carousel-slide">
                            <img src="img/Carrusel/Imagen3.jpg" alt="Biblioteca">
                            <div class="overlay">
                                <p class="quote">"Leer es vivir mil vidas antes de acabar la tuya."</p>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>

                <button class="carousel-btn prev" onclick="move(-1)">&#8249;</button>
                <button class="carousel-btn next" onclick="move(1)">&#8250;</button>

                <?php if (isset($_SESSION["rol"]) && $_SESSION["rol"] === "administrador"): ?>
                    <a href="carrusel.php" class="btn_admin_carrusel_flotante" title="Administrar fotos y frases del carrusel">
                        <i class="ti ti-settings"></i> Administrar Carrusel
                    </a>
                <?php endif; ?>
            </div>

            <div class="h_text">
                <h2 class="h_titulo">Accede y reserva libros que necesites de la biblioteca.</h2>
                <p class="h_subtitulo">Gracias por visitar nuestra pagina.</p>
                <a href="materias.php" class="h_mat">Explorar materias</a>
            </div>
        </section>

    </main>

<?php include "includes/footer.php"; ?>

    <script src="js/index.js"></script>
</body>
</html>
