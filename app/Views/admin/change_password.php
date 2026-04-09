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

<main class="flex-grow flex items-center justify-center p-4 pt-10">
    <div class="w-full max-w-lg">
        <div class="bg-white p-8 rounded-xl shadow-2xl">
            <h1 class="text-3xl font-bold text-center text-superarse-morado-oscuro mb-4">Actualiza tu clave de acceso</h1>
            <p class="text-center text-gray-600 mb-6">Antes de usar el panel administrativo debes cambiar la contraseña temporal asignada a tu cuenta.</p>

            <?php if ($error && isset($messages[$error])): ?>
                <div class="mb-4 p-3 bg-red-100 border border-red-300 text-red-700 rounded-lg">
                    <?php echo htmlspecialchars($messages[$error]); ?>
                </div>
            <?php endif; ?>

            <div class="mb-6 rounded-lg border border-superarse-morado-medio/20 bg-superarse-morado-medio/5 p-4 text-sm text-gray-700">
                La nueva contraseña debe tener entre 8 y 12 caracteres, al menos una letra mayúscula, una minúscula, un número y un signo especial.
            </div>

            <form action="<?php echo $basePath; ?>/admin/password/change" method="POST" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                <div>
                    <label for="current_password" class="block text-gray-700 text-sm font-semibold mb-2">Contraseña actual</label>
                    <input type="password" id="current_password" name="current_password" required autocomplete="current-password"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-superarse-morado-medio"
                        placeholder="Tu contraseña temporal actual">
                </div>

                <div>
                    <label for="new_password" class="block text-gray-700 text-sm font-semibold mb-2">Nueva contraseña</label>
                    <input type="password" id="new_password" name="new_password" required autocomplete="new-password"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-superarse-morado-medio"
                        placeholder="Crea una contraseña segura">
                </div>

                <div>
                    <label for="confirm_password" class="block text-gray-700 text-sm font-semibold mb-2">Confirmar nueva contraseña</label>
                    <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-superarse-morado-medio"
                        placeholder="Repite la nueva contraseña">
                </div>

                <button type="submit"
                    class="w-full bg-superarse-rosa hover:bg-superarse-morado-medio text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                    Guardar nueva contraseña
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="<?php echo $basePath; ?>/admin/logout" class="text-sm text-superarse-morado-medio hover:text-superarse-rosa">
                    Cerrar sesión
                </a>
            </div>
        </div>
    </div>
</main>