<?php
$active = "nosotros";
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
    <title>Cercatori - Nosotros</title>
    <link rel="stylesheet" href="css/index.css">
</head>
<body>

<?php include "includes/header.php"; ?>

    <main>
        <section class="seccion_equipo">

            <p class="team-eyebrow">Las personas detrás de Cercatori</p>
            <h2 class="encabezado">Nuestro Equipo</h2>
            <div class="team-divider"></div>
            <p class="leyenda">Conoce a quienes hacen posible Cercatori</p>

            <div class="Espacio">

                <div class="card_equipo">
                    <div class="glass_card">
                        <div class="card_left">
                            <div class="circulo_avatar">
                                <div class="avatar_miembro enrique">ER</div>
                            </div>
                            <p class="Nombre_equipo">Enrique Rivera</p>
                            <p class="Rol_equipo">Fundador</p>
                        </div>
                        <div class="card_separator"></div>
                        <div class="card_right">
                            <p class="Bio_equipo">Estudiante de desarrollo de software apasionado por la tecnología y la educación. Creó Cercatori con la visión de que ningún compañero se quedara sin acceso a los recursos que necesita para aprender. Lidera el proyecto desde la arquitectura hasta el código.</p>
                            <div class="card_info">
                                <span class="info_item"><i class="ti ti-building"></i> IE Jorge Robledo</span>
                                <span class="info_item"><i class="ti ti-brand-instagram"></i> @juanrestrepo423</span>
                                <span class="info_item"><i class="ti ti-code"></i> Fundador & Dev</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card_equipo">
                    <div class="glass_card">
                        <div class="card_left">
                            <div class="circulo_avatar">
                                <div class="avatar_miembro lorena">LJ</div>
                            </div>
                            <p class="Nombre_equipo">Lorena Jiménez</p>
                            <p class="Rol_equipo">Diseño & Creatividad</p>
                        </div>
                        <div class="card_separator"></div>
                        <div class="card_right">
                            <p class="Bio_equipo">Mente creativa del equipo, con un ojo natural para el diseño y la experiencia del usuario. Se encarga de que Cercatori no solo funcione bien, sino que se vea increíble. Cree que el buen diseño es el que nadie nota porque simplemente fluye.</p>
                            <div class="card_info">
                                <span class="info_item"><i class="ti ti-building"></i> IE Jorge Robledo</span>
                                <span class="info_item"><i class="ti ti-brand-instagram"></i> @lorej128</span>
                                <span class="info_item"><i class="ti ti-palette"></i> Diseño & Creatividad</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card_equipo">
                    <div class="glass_card">
                        <div class="card_left">
                            <div class="circulo_avatar">
                                <div class="avatar_miembro salome">SH</div>
                            </div>
                            <p class="Nombre_equipo">Salomé Hernández</p>
                            <p class="Rol_equipo">Operaciones</p>
                        </div>
                        <div class="card_separator"></div>
                        <div class="card_right">
                            <p class="Bio_equipo">La que hace que todo funcione detrás de escena. Coordina tiempos, organiza entregas y se asegura de que el equipo siempre esté alineado. Sin ella, Cercatori sería un buen proyecto sin terminar.</p>
                            <div class="card_info">
                                <span class="info_item"><i class="ti ti-building"></i> IE Jorge Robledo</span>
                                <span class="info_item"><i class="ti ti-brand-instagram"></i> @salo_hdzz</span>
                                <span class="info_item"><i class="ti ti-settings"></i> Operaciones</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </section>
    </main>

<?php include "includes/footer.php"; ?>

<script src="js/index.js"></script>
</body>
</html>
