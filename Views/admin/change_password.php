<?php
$error = $_GET['error'] ?? null;
$messages = [
    'campos_vacios' => 'Debe completar todos los campos.',
    'invalid_current_password' => 'La contraseña actual no es correcta.',
    'password_mismatch' => 'La nueva contraseña y la confirmación no coinciden.',
    'same_password' => 'La nueva contraseña debe ser diferente a la actual.',
    'invalid_request' => 'La sesión del formulario expiró. Intente nuevamente.',
    'not_authenticated' => 'Debe iniciar sesión para cambiar su contraseña.',
    'password_update_failed' => 'No fue posible actualizar la contraseña. Intente nuevamente.',
    'policy_invalid' => $_GET['message'] ?? 'La contraseña no cumple la política requerida.',
];
?>
<div style="flex:1;display:flex;align-items:center;justify-content:center;padding:2rem 1rem;">
    <div style="width:100%;max-width:480px;background:#ffffff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,0.15);overflow:hidden;">

        <!-- Header de la tarjeta -->
        <div style="background:linear-gradient(135deg,#4A148C,#673AB7);padding:2rem;text-align:center;">
            <h1 style="color:#ffffff;font-size:1.5rem;font-weight:700;margin:0;">Actualiza tu clave de acceso</h1>
            <p style="color:rgba(255,255,255,0.8);font-size:0.875rem;margin:0.5rem 0 0 0;">Antes de usar el panel administrativo debes cambiar la contraseña temporal asignada a tu cuenta.</p>
        </div>

        <div style="padding:2rem;">

            <?php if ($error && isset($messages[$error])): ?>
                <div style="margin-bottom:1.25rem;padding:0.875rem 1rem;background:#FEE2E2;border:1px solid #FCA5A5;border-radius:10px;color:#DC2626;font-size:0.875rem;">
                    <?php echo htmlspecialchars($messages[$error]); ?>
                </div>
            <?php endif; ?>

            <div style="margin-bottom:1.5rem;padding:1rem;background:#F3E8FF;border:1px solid #D8B4FE;border-radius:10px;font-size:0.8rem;color:#6B21A8;">
                🔒 La nueva contraseña debe tener entre 8 y 12 caracteres, al menos una letra mayúscula, una minúscula, un número y un signo especial.
            </div>

            <form action="<?php echo $basePath; ?>/admin/password/change" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                <div style="margin-bottom:1.25rem;position:relative;">
                    <label for="current_password" style="display:block;font-size:0.875rem;font-weight:600;color:#374151;margin-bottom:0.375rem;">Contraseña actual</label>
                    <input type="password" id="current_password" name="current_password" required autocomplete="current-password"
                        style="width:100%;padding:0.75rem 2.5rem 0.75rem 1rem;border:1px solid #D1D5DB;border-radius:10px;font-size:0.9rem;outline:none;box-sizing:border-box;transition:border-color 0.2s,box-shadow 0.2s;"
                        onfocus="this.style.borderColor='#673AB7';this.style.boxShadow='0 0 0 3px rgba(103,58,183,0.15)';"
                        onblur="this.style.borderColor='#D1D5DB';this.style.boxShadow='none';"
                        placeholder="Tu contraseña temporal actual">
                    <button type="button" onclick="togglePassword('current_password', this)" tabindex="-1"
                        style="position:absolute;right:10px;top:38px;background:none;border:none;cursor:pointer;padding:4px;line-height:1;font-size:1.1rem;color:#6B7280;">👁</button>
                </div>

                <div style="margin-bottom:1.25rem;position:relative;">
                    <label for="new_password" style="display:block;font-size:0.875rem;font-weight:600;color:#374151;margin-bottom:0.375rem;">Nueva contraseña</label>
                    <input type="password" id="new_password" name="new_password" required autocomplete="new-password"
                        style="width:100%;padding:0.75rem 2.5rem 0.75rem 1rem;border:1px solid #D1D5DB;border-radius:10px;font-size:0.9rem;outline:none;box-sizing:border-box;transition:border-color 0.2s,box-shadow 0.2s;"
                        onfocus="this.style.borderColor='#673AB7';this.style.boxShadow='0 0 0 3px rgba(103,58,183,0.15)';"
                        onblur="this.style.borderColor='#D1D5DB';this.style.boxShadow='none';"
                        placeholder="Crea una contraseña segura">
                    <button type="button" onclick="togglePassword('new_password', this)" tabindex="-1"
                        style="position:absolute;right:10px;top:38px;background:none;border:none;cursor:pointer;padding:4px;line-height:1;font-size:1.1rem;color:#6B7280;">👁</button>
                </div>

                <div style="margin-bottom:1.5rem;position:relative;">
                    <label for="confirm_password" style="display:block;font-size:0.875rem;font-weight:600;color:#374151;margin-bottom:0.375rem;">Confirmar nueva contraseña</label>
                    <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password"
                        style="width:100%;padding:0.75rem 2.5rem 0.75rem 1rem;border:1px solid #D1D5DB;border-radius:10px;font-size:0.9rem;outline:none;box-sizing:border-box;transition:border-color 0.2s,box-shadow 0.2s;"
                        onfocus="this.style.borderColor='#673AB7';this.style.boxShadow='0 0 0 3px rgba(103,58,183,0.15)';"
                        onblur="this.style.borderColor='#D1D5DB';this.style.boxShadow='none';"
                        placeholder="Repite la nueva contraseña">
                    <button type="button" onclick="togglePassword('confirm_password', this)" tabindex="-1"
                        style="position:absolute;right:10px;top:38px;background:none;border:none;cursor:pointer;padding:4px;line-height:1;font-size:1.1rem;color:#6B7280;">👁</button>
                </div>

                <button type="submit"
                    style="width:100%;padding:0.75rem 1rem;background:linear-gradient(135deg,#673AB7,#4A148C);color:#ffffff;font-size:0.95rem;font-weight:700;border:none;border-radius:10px;cursor:pointer;transition:opacity 0.2s,transform 0.1s;box-shadow:0 4px 12px rgba(74,20,140,0.3);"
                    onmouseover="this.style.opacity='0.9'"
                    onmouseout="this.style.opacity='1'"
                    onmousedown="this.style.transform='scale(0.98)'"
                    onmouseup="this.style.transform='scale(1)'">
                    Guardar nueva contraseña
                </button>
            </form>

            <div style="margin-top:1.5rem;text-align:center;">
                <a href="<?php echo $basePath; ?>/admin/logout" style="color:#673AB7;font-size:0.85rem;text-decoration:none;font-weight:500;transition:color 0.2s;"
                   onmouseover="this.style.color='#E91E63'"
                   onmouseout="this.style.color='#673AB7'">
                    Cerrar sesión
                </a>
            </div>

        </div>
    </div>
</div>

<script>
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    if (input.type === 'password') {
        input.type = 'text';
        btn.textContent = '🙈';
    } else {
        input.type = 'password';
        btn.textContent = '👁';
    }
}
</script>