<?php
$active = "contacto";
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" />
  <script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>
  <link rel="icon" href="img/favicon.ico"/>
  <title>Cercatori - Contáctanos</title>
  <link rel="stylesheet" href="css/index.css" />
</head>
<body>

<?php include "includes/header.php"; ?>

  <main>

    <section class="contacto">
        <h1>CONTÁCTANOS</h1>

        <div class="contenedor_contacto">
            <div class="formulario">

                <h3>Envíanos un correo</h3>

                <p>
                    Completa el formulario, escríbenos tus dudas y nos contactaremos contigo
                </p>

                <form>

                    <label>Nombre Completo</label>
                    <input type="text">

                    <label>Número de Teléfono</label>
                    <input type="tel">

                    <label>Rol *</label>

                    <select>
                        <option value="" disabled selected>Selecciona tu rol</option>
                        <option value="estudiante">Estudiante</option>
                        <option value="docente">Docente</option>
                        <option value="directivo">Directivo</option>
                    </select>

                    <label>Correo Electrónico *</label>
                    <input type="email">

                    <label>Mensaje *</label>
                    <textarea rows="6"></textarea>

                    <button type="submit">Enviar</button>

                </form>

            </div>

            <div class="info_contacto">

                <div class="dato_contacto">
                    <i class="ti ti-message"></i>
                    <span>300 122 1229</span>
                </div>

                <div class="dato_contacto">
                    <i class="ti ti-mail"></i>
                    <span>BibliotecaCercatori@gmail.com</span>
                </div>

                <div class="horarios">
                    <h4>Horarios:</h4>
                    <p>Lunes a viernes: 7:30AM - 5:00PM</p>
                </div>
            </div>
        </div>
    </section>

  </main>

<?php include "includes/footer.php"; ?>

    <script src="js/index.js"></script>
</body>
</html>
