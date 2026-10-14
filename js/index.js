// ─── Carrusel de la portada ───────────────────────────────────

let current = 0;
const slides = document.querySelectorAll('.carousel-slide');
const track = document.getElementById('track');

function move(dir) {
  current = (current + dir + slides.length) % slides.length;
  track.style.transform = `translateX(-${current * 100}%)`;
}


// ─── Barras de disponibilidad (materias.php) ──────────────────
// El porcentaje viene del servidor en data-percent; aquí solo se
// traduce a un ancho visual, sin tocar el markup con estilos fijos.

document.querySelectorAll(".barra_disp span[data-percent]").forEach(function (barra) {
  barra.style.width = barra.dataset.percent + "%";
});


// ─── Buscador desplegable del header ──────────────────────────

const buscadorHeader = document.querySelector(".buscador_header");

if (buscadorHeader) {
  const iconoBuscador = buscadorHeader.querySelector('[data-toggle="buscador"]');
  const inputBuscador = buscadorHeader.querySelector("input");

  iconoBuscador.addEventListener("click", function (e) {
    e.stopPropagation();
    buscadorHeader.classList.toggle("activo");
    if (buscadorHeader.classList.contains("activo")) {
      inputBuscador.focus();
    }
  });
}


// ─── Panel de notificaciones del header ───────────────────────

const notificacionesHeader = document.querySelector(".notificaciones_header");

if (notificacionesHeader) {
  const iconoCampana = notificacionesHeader.querySelector('[data-toggle="notificaciones"]');

  iconoCampana.addEventListener("click", function (e) {
    e.stopPropagation();
    notificacionesHeader.classList.toggle("activo");
    if (buscadorHeader) buscadorHeader.classList.remove("activo");
  });
}

// Cierra los desplegables del header al hacer clic fuera de ellos.
document.addEventListener("click", function (e) {
  if (buscadorHeader && !buscadorHeader.contains(e.target)) {
    buscadorHeader.classList.remove("activo");
  }
  if (notificacionesHeader && !notificacionesHeader.contains(e.target)) {
    notificacionesHeader.classList.remove("activo");
  }
});


// ─── Formulario de registro: campos según el tipo de usuario ──
// (usado en registro.php)

const selectorTipoRegistro = document.getElementById("tipo_registro");

if (selectorTipoRegistro) {
  const labelDirector = document.getElementById("label_director");
  const labelCodigo = document.getElementById("label_codigo");
  const inputCodigo = document.getElementById("input_codigo");
  const labelCodigoDocente = document.getElementById("label_codigo_docente");
  const inputCodigoDocente = document.getElementById("input_codigo_docente");

  function actualizarCamposRegistro() {
    const tipo = selectorTipoRegistro.value;
    const esDocente = tipo === "docente";
    const esAdmin = tipo === "administrador";

    labelDirector.classList.toggle("oculto", !esDocente);

    labelCodigo.classList.toggle("oculto", !esAdmin);
    inputCodigo.classList.toggle("oculto", !esAdmin);
    inputCodigo.required = esAdmin;

    if (labelCodigoDocente && inputCodigoDocente) {
      labelCodigoDocente.classList.toggle("oculto", !esDocente);
      inputCodigoDocente.classList.toggle("oculto", !esDocente);
      inputCodigoDocente.required = esDocente;
    }
  }

  selectorTipoRegistro.addEventListener("change", actualizarCamposRegistro);
  actualizarCamposRegistro();
}


// ─── EmailJS - Formulario de contacto Cercatori ───────────────
// Solo se activa en páginas que realmente tienen el formulario y
// cargaron la librería de EmailJS (evita errores en el resto del sitio).

const formularioContacto = document.querySelector(".formulario form");

if (formularioContacto && typeof emailjs !== "undefined") {
  emailjs.init("9vKhzHbymWt3ZkGSc");

  formularioContacto.addEventListener("submit", function (e) {
    e.preventDefault();

    const btn = this.querySelector("button[type='submit']");
    const nombre   = this.querySelector("input[type='text']").value.trim();
    const telefono = this.querySelector("input[type='tel']").value.trim();
    const rol      = this.querySelector("select").value;
    const email    = this.querySelector("input[type='email']").value.trim();
    const mensaje  = this.querySelector("textarea").value.trim();

    if (!email || !mensaje || !rol) {
        mostrarToast("Por favor completa los campos obligatorios.", "error");
        return;
    }

    btn.disabled = true;
    btn.textContent = "Enviando...";

    emailjs.send("service_j8y7dcl", "template_giff4tv", {
        name:      nombre,
        telefono:  telefono,
        rol:       rol,
        email:     email,
        message:   mensaje,
    })
    .then(() => {
        mostrarToast("¡Mensaje enviado! Te contactaremos pronto.", "ok");
        this.reset();
    })
    .catch(() => {
        mostrarToast("Hubo un error. Intenta de nuevo.", "error");
    })
    .finally(() => {
        btn.disabled = false;
        btn.textContent = "Enviar";
    });
  });
}

function mostrarToast(msg, tipo) {
    const toast = document.createElement("div");
    toast.textContent = msg;
    toast.className = "toast " + (tipo === "ok" ? "toast_ok" : "toast_error");
    document.body.appendChild(toast);
    requestAnimationFrame(() => toast.classList.add("toast_visible"));
    setTimeout(() => {
        toast.classList.remove("toast_visible");
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}


// ─── Pestañas de "Préstamos activos / Historial" (admin y perfiles) ──

document.querySelectorAll(".tabs_admin, .tabs_perfil, .tabs_grupo").forEach(function (grupo) {
  const botones = grupo.querySelectorAll(".tab_btn");
  const contenedor = grupo.parentElement;

  botones.forEach(function (btn) {
    btn.addEventListener("click", function () {
      botones.forEach((b) => b.classList.remove("tab_activo"));
      btn.classList.add("tab_activo");

      const destino = btn.dataset.tab;
      contenedor.querySelectorAll(".tab_panel").forEach(function (panel) {
        if (panel.dataset.panel) {
          panel.classList.toggle("tab_panel_oculto", panel.dataset.panel !== destino);
        }
      });
    });
  });
});


// ─── Desplegable de Historial (aparecer / desaparecer con botón) ──────

document.querySelectorAll(".btn_toggle_historial").forEach(function (btn) {
  btn.addEventListener("click", function () {
    const targetId = btn.dataset.target;
    const drawer = document.getElementById(targetId);
    if (drawer) {
      const cerrado = drawer.classList.toggle("drawer_cerrado");
      const flecha = btn.querySelector(".flecha_toggle");
      if (flecha) {
        flecha.classList.toggle("flecha_rotada", !cerrado);
      }
    }
  });
});


// ─── Modo Oscuro ───────────────────────────────────────

const btnDark = document.getElementById("btn_dark_mode");

if (btnDark) {
  const guardado = localStorage.getItem("tema") === "oscuro";
  document.documentElement.classList.toggle("modo_oscuro", guardado);
  btnDark.classList.toggle("ti-sun", guardado);
  btnDark.classList.toggle("ti-moon", !guardado);

  btnDark.addEventListener("click", function () {
    const activo = document.documentElement.classList.toggle("modo_oscuro");
    localStorage.setItem("tema", activo ? "oscuro" : "claro");
    btnDark.classList.toggle("ti-sun", activo);
    btnDark.classList.toggle("ti-moon", !activo);
  });
}


// ─── Ver / Ocultar Contraseña (Ojito) ───────────────────

document.addEventListener("click", function (e) {
  const icono = e.target.closest(".password-box .icono, [data-toggle-password]");
  if (icono) {
    const contenedor = icono.closest(".password-box") || icono.parentElement;
    const input = contenedor ? contenedor.querySelector("input") : null;
    if (input) {
      const esPassword = input.type === "password";
      input.type = esPassword ? "text" : "password";
      icono.classList.toggle("ti-eye", !esPassword);
      icono.classList.toggle("ti-eye-off", esPassword);
      icono.setAttribute("title", esPassword ? "Ocultar contraseña" : "Ver contraseña");
    }
  }
});


// ─── Preservación de posición del scroll al enviar formularios ───────
// Evita que la página salte al inicio tras prestar un libro, solicitar devolución o guardar cambios
document.addEventListener("submit", function () {
  sessionStorage.setItem("cercatori_scroll_pos", window.scrollY);
});

window.addEventListener("DOMContentLoaded", function () {
  const scrollGuardado = sessionStorage.getItem("cercatori_scroll_pos");
  if (scrollGuardado !== null) {
    window.scrollTo({
      top: parseInt(scrollGuardado, 10),
      behavior: "instant"
    });
    sessionStorage.removeItem("cercatori_scroll_pos");
  }
});