<?php
// barra de nav para admins tiene la fotico de perfil a la izquierda creo y los php de admins a la izquierda
// obviamente la pagina tiene que tener el include 
if (!isset($admin_foto_actual)) {
    require_once __DIR__ . "/admin_foto.php";
}
?>
<div class="top_bar_sesion admin_bar">

    <div class="admin_bar_perfil">
        <div class="admin_avatar_wrap">
            <?php if ($admin_foto_actual): ?>
                <img src="<?= htmlspecialchars($admin_foto_actual) ?>" alt="Foto de perfil" class="admin_avatar_img">
            <?php else: ?>
                <div class="admin_avatar_ph"><i class="ti ti-user"></i></div>
            <?php endif; ?>
            <button type="button" class="btn_editar_avatar btn_editar_avatar_mini" id="btn_abrir_modal_admin_foto" title="Cambiar foto de perfil">
                <i class="ti ti-camera"></i>
            </button>
        </div>
        <div class="admin_bar_texto">
            <span class="admin_bar_nombre"><?= htmlspecialchars($_SESSION["nombre"]) ?></span>
            <span class="admin_bar_rol">Administrador</span>
        </div>
    </div>

    <div class="admin_nav_links">
        <a href="libros.php" <?= ($admin_active ?? "") === "libros" ? 'class="active"' : "" ?>>Libros</a>
        <a href="computadores.php" <?= ($admin_active ?? "") === "computadores" ? 'class="active"' : "" ?>>Computadores</a>
        <a href="juegos.php" <?= ($admin_active ?? "") === "juegos" ? 'class="active"' : "" ?>>Juegos</a>
        <a href="usuarios.php" <?= ($admin_active ?? "") === "usuarios" ? 'class="active"' : "" ?>>Usuarios</a>
        <a href="carrusel.php" <?= ($admin_active ?? "") === "carrusel" ? 'class="active"' : "" ?>>Carrusel</a>
        <a href="logout.php" class="admin_link_salir">Cerrar sesión</a>
    </div>

</div>

<?php if (!empty($admin_foto_mensaje)): ?>
    <div class="admin_flash_wrap">
        <p class="<?= $admin_foto_tipo === "error" ? "mensaje_error" : "mensaje_ok" ?>"><?= htmlspecialchars($admin_foto_mensaje) ?></p>
    </div>
<?php endif; ?>

<!-- el modal de la foto del admin sencillo -->
<div id="modal_admin_foto" class="modal_overlay">
    <div class="modal_caja">
        <div class="modal_header">
            <h3><i class="ti ti-photo"></i> Foto de perfil</h3>
            <button type="button" class="btn_cerrar_modal" data-cerrar-admin-foto>&times;</button>
        </div>
        <div class="modal_body">
            <div class="avatar_preview_container">
                <img id="admin_avatar_preview"
                     src="<?= $admin_foto_actual ? htmlspecialchars($admin_foto_actual) : "img/cercatori.png" ?>"
                     class="avatar_preview_img" alt="Vista previa">
                <p class="nota_form">Imagen cuadrada en JPG, PNG, WEBP o GIF (máx. 3MB).</p>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <?= csrf_campo() ?>
                <div class="form_grupo">
                    <label for="input_admin_foto">Seleccionar imagen</label>
                    <input type="file" name="admin_foto" id="input_admin_foto"
                           accept="image/png, image/jpeg, image/webp, image/gif" required>
                </div>
                <div class="modal_acciones">
                    <button type="button" class="btn_secundario" data-cerrar-admin-foto>Cancelar</button>
                    <?php if ($admin_foto_actual): ?>
                        <button type="submit" name="admin_eliminar_foto" class="btn_peligro"
                                formnovalidate
                                onclick="return confirm('¿Eliminar tu foto de perfil?');">
                            <i class="ti ti-trash"></i> Eliminar
                        </button>
                    <?php endif; ?>
                    <button type="submit" name="admin_subir_foto" class="btn_nuevo_usuario">
                        <i class="ti ti-upload"></i> Guardar foto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // cosa que si pongo en js por alguna razon no funciona 
(function() {
    const modal   = document.getElementById("modal_admin_foto");
    const abrir   = document.getElementById("btn_abrir_modal_admin_foto");
    const input   = document.getElementById("input_admin_foto");
    const preview = document.getElementById("admin_avatar_preview");
    if (!modal) return;

    const cerrar = () => modal.classList.remove("modal_activo");

    if (abrir) abrir.addEventListener("click", () => modal.classList.add("modal_activo"));
    modal.querySelectorAll("[data-cerrar-admin-foto]").forEach(b => b.addEventListener("click", cerrar));
    modal.addEventListener("click", e => { if (e.target === modal) cerrar(); });
    document.addEventListener("keydown", e => { if (e.key === "Escape") cerrar(); });

    if (input && preview) {
        input.addEventListener("change", function() {
            const f = this.files[0];
            if (!f) return;
            const r = new FileReader();
            r.onload = e => preview.src = e.target.result;
            r.readAsDataURL(f);
        });
    }
})();
</script>
