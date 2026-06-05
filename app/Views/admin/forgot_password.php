<?php
$success  = isset($_GET['success']);
$error    = $_GET['error'] ?? null;
$messages = [
    'campos_vacios'   => 'Debe ingresar su correo electrónico.',
    'invalid_request' => 'La sesión del formulario expiró. Intente de nuevo.',
];
?>
<div style="flex:1;display:flex;align-items:center;justify-content:center;padding:2rem 1rem;">
    <div style="width:100%;max-width:460px;background:#ffffff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,0.15);overflow:hidden;">

        <!-- Header gradiente -->
        <div style="background:linear-gradient(135deg,#4A148C,#673AB7);padding:2rem;text-align:center;">
            <h1 style="color:#ffffff;font-size:1.5rem;font-weight:700;margin:0;">Recuperar acceso administrativo</h1>
        </div>

        <div style="padding:2rem;">

            <?php if ($success): ?>
                <div style="margin-bottom:1.25rem;padding:1.25rem;background:#F0FDF4;border:1px solid #86EFAC;border-radius:12px;text-align:center;">
                    <svg style="width:2.5rem;height:2.5rem;color:#16A34A;margin:0 auto 0.75rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p style="font-weight:700;color:#166534;margin:0 0 0.25rem;font-size:1rem;">Solicitud registrada</p>
                    <p style="color:#15803D;font-size:0.85rem;margin:0;line-height:1.5;">
                        Si existe una cuenta asociada a ese correo, un administrador procesará tu solicitud.
                    </p>
                </div>
                <div style="text-align:center;margin-top:1rem;">
                    <a href="<?= $basePath ?>/admin/login" style="color:#673AB7;font-size:0.85rem;text-decoration:none;font-weight:500;transition:color 0.2s;"
                       onmouseover="this.style.color='#E91E63'" onmouseout="this.style.color='#673AB7'">
                        ← Volver al panel de administración
                    </a>
                </div>

            <?php else: ?>
                <p style="text-align:center;color:#6B7280;font-size:0.9rem;margin:0 0 1.25rem;line-height:1.5;">
                    Ingresa tu correo institucional para solicitar el restablecimiento de tu contraseña.
                </p>

                <?php if ($error && isset($messages[$error])): ?>
                    <div style="margin-bottom:1.25rem;padding:0.875rem 1rem;background:#FEE2E2;border:1px solid #FCA5A5;border-radius:10px;color:#DC2626;font-size:0.85rem;">
                        <?= htmlspecialchars($messages[$error]) ?>
                    </div>
                <?php endif; ?>

                <div style="margin-bottom:1.5rem;padding:1rem;background:#FFFBEB;border:1px solid #FDE68A;border-radius:10px;font-size:0.8rem;color:#92400E;line-height:1.5;">
                    ⚠️ La solicitud quedará registrada en el sistema. Otro administrador con acceso activo deberá procesarla.
                </div>

                <form method="POST" action="<?= $basePath ?>/admin/forgot-password/submit">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                    <div style="margin-bottom:1.5rem;">
                        <label for="email" style="display:block;font-size:0.875rem;font-weight:600;color:#374151;margin-bottom:0.375rem;">
                            Correo electrónico institucional
                        </label>
                        <input type="email" id="email" name="email" required autocomplete="email"
                            style="width:100%;padding:0.75rem 1rem;border:1px solid #D1D5DB;border-radius:10px;font-size:0.9rem;outline:none;box-sizing:border-box;transition:border-color 0.2s,box-shadow 0.2s;"
                            onfocus="this.style.borderColor='#673AB7';this.style.boxShadow='0 0 0 3px rgba(103,58,183,0.15)';"
                            onblur="this.style.borderColor='#D1D5DB';this.style.boxShadow='none';"
                            placeholder="usuario@superarse.edu.ec">
                    </div>

                    <button type="submit"
                        style="width:100%;padding:0.75rem 1rem;background:linear-gradient(135deg,#673AB7,#4A148C);color:#ffffff;font-size:0.95rem;font-weight:700;border:none;border-radius:10px;cursor:pointer;transition:opacity 0.2s,transform 0.1s;box-shadow:0 4px 12px rgba(74,20,140,0.3);"
                        onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'"
                        onmousedown="this.style.transform='scale(0.98)'" onmouseup="this.style.transform='scale(1)'">
                        Enviar solicitud
                    </button>
                </form>

                <div style="margin-top:1.5rem;text-align:center;">
                    <a href="<?= $basePath ?>/admin/login" style="color:#673AB7;font-size:0.85rem;text-decoration:none;font-weight:500;transition:color 0.2s;"
                       onmouseover="this.style.color='#E91E63'" onmouseout="this.style.color='#673AB7'">
                        ← Volver al inicio de sesión
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>