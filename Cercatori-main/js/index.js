let current = 0;
const slides = document.querySelectorAll('.carousel-slide');
const track = document.getElementById('track');

function move(dir) {
  current = (current + dir + slides.length) % slides.length;
  track.style.transform = `translateX(-${current * 100}%)`;
}


// ─── EmailJS - Formulario de contacto Cercatori ───────────────

emailjs.init("9vKhzHbymWt3ZkGSc");

document.querySelector(".formulario form").addEventListener("submit", function (e) {
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

function mostrarToast(msg, tipo) {
    const toast = document.createElement("div");
    toast.textContent = msg;
    toast.style.cssText = `
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        background: ${tipo === "ok" ? "#0d2e16" : "#7f1d1d"};
        color: ${tipo === "ok" ? "#7ee8a2" : "#fca5a5"};
        padding: 14px 24px;
        border-radius: 10px;
        font-size: 13px;
        letter-spacing: 0.5px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        z-index: 9999;
        opacity: 0;
        transition: opacity 0.3s;
    `;
    document.body.appendChild(toast);
    requestAnimationFrame(() => toast.style.opacity = "1");
    setTimeout(() => {
        toast.style.opacity = "0";
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}