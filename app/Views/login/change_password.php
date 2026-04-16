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
            <h1 class="text-3xl font-bold text-center text-superarse-morado-oscuro mb-4">Cambia tu contraseña</h1>
            <p class="text-center text-gray-600 mb-6">Por seguridad, en tu primer ingreso debes reemplazar la contraseña temporal.</p>

            <?php if ($error && isset($messages[$error])): ?>
                <div class="mb-4 p-3 bg-red-100 border border-red-300 text-red-700 rounded-lg">
                    <?php echo htmlspecialchars($messages[$error]); ?>
                </div>
            <?php endif; ?>

            <div class="mb-6 rounded-lg border border-superarse-morado-medio/20 bg-superarse-morado-medio/5 p-4 text-sm text-gray-700">
                La nueva contraseña debe tener entre 8 y 12 caracteres, al menos una letra mayúscula, una minúscula, un número y un signo especial.
            </div>

            <form action="<?php echo $basePath; ?>/password/change" method="POST" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                <div>
                    <label for="current_password" class="block text-gray-700 text-sm font-semibold mb-2">Contraseña actual</label>
                    <input type="password" id="current_password" name="current_password" required autocomplete="current-password"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-superarse-morado-medio"
                        placeholder="Tu contraseña temporal actual">
                </div>

                <div>
                    <label for="new_password" class="block text-gray-700 text-sm font-semibold mb-2">Nueva contraseña</label>
                    <input type="password" id="new_password" name="new_password" required maxlength="12" autocomplete="new-password"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-superarse-morado-medio"
                        placeholder="Crea una contraseña segura">
                </div>
                
                    <div class="mb-6 rounded-lg border border-superarse-morado-medio/20 bg-superarse-morado-medio/5 p-4 text-sm text-gray-700">
                        <p class="font-semibold mb-2">La contraseña debe cumplir:</p>
                        <ul class="space-y-1">
                            <li id="length" class="text-gray-500">• 8 a 12 caracteres</li>
                            <li id="uppercase" class="text-gray-500">• Al menos una mayúscula</li>
                            <li id="lowercase" class="text-gray-500">• Al menos una minúscula</li>
                            <li id="number" class="text-gray-500">• Al menos un número</li>
                            <li id="special" class="text-gray-500">• Un signo especial</li>
                        </ul>
                    </div>

                <div>
                    <label for="confirm_password" class="block text-gray-700 text-sm font-semibold mb-2">Confirmar nueva contraseña</label>
                    <input type="password" id="confirm_password" name="confirm_password" required maxlength="12" autocomplete="new-password"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-superarse-morado-medio"
                        placeholder="Repite la nueva contraseña">
                        
                        <div id="match_message" class="text-sm mt-1 text-gray-500">
                            • Las contraseñas deben coincidir
                        </div>
                </div>

                <button type="submit"
                    class="w-full bg-superarse-rosa hover:bg-superarse-morado-medio text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                    Guardar nueva contraseña
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="<?php echo $basePath; ?>/login/logout" class="text-sm text-superarse-morado-medio hover:text-superarse-rosa">
                    Cerrar sesión
                </a>
            </div>
        </div>
    </div>
</main>

<script>
const passwordInput = document.getElementById('new_password');
const confirmInput = document.getElementById('confirm_password');
const matchMessage = document.getElementById('match_message');
const form = document.querySelector('form');

const rules = {
    length: document.getElementById('length'),
    uppercase: document.getElementById('uppercase'),
    lowercase: document.getElementById('lowercase'),
    number: document.getElementById('number'),
    special: document.getElementById('special'),
};

// Validación de reglas
passwordInput.addEventListener('input', () => {
    const value = passwordInput.value;

    const validations = {
        length: value.length >= 8 && value.length <= 12,
        uppercase: /[A-Z]/.test(value),
        lowercase: /[a-z]/.test(value),
        number: /[0-9]/.test(value),
        special: /[\W_]/.test(value)
    };

    Object.keys(validations).forEach(rule => {
        if (validations[rule]) {
            rules[rule].classList.remove('text-gray-500');
            rules[rule].classList.add('text-green-600');
            rules[rule].textContent = '✔ ' + rules[rule].textContent.replace(/^✔ |^• /, '');
        } else {
            rules[rule].classList.remove('text-green-600');
            rules[rule].classList.add('text-gray-500');
            rules[rule].textContent = '• ' + rules[rule].textContent.replace(/^✔ |^• /, '');
        }
    });

    checkPasswordMatch();
});

// Validación de coincidencia
function checkPasswordMatch() {
    const password = passwordInput.value;
    const confirm = confirmInput.value;

    if (confirm.length === 0) {
        matchMessage.textContent = '• Las contraseñas deben coincidir';
        matchMessage.classList.remove('text-green-600', 'text-red-600');
        matchMessage.classList.add('text-gray-500');
        return;
    }

    if (password === confirm) {
        matchMessage.textContent = '✔ Las contraseñas coinciden';
        matchMessage.classList.remove('text-gray-500', 'text-red-600');
        matchMessage.classList.add('text-green-600');
    } else {
        matchMessage.textContent = '✖ Las contraseñas no coinciden';
        matchMessage.classList.remove('text-gray-500', 'text-green-600');
        matchMessage.classList.add('text-red-600');
    }
}

confirmInput.addEventListener('input', checkPasswordMatch);

// Validación al enviar
form.addEventListener('submit', function(e) {
    const value = passwordInput.value;
    const confirm = confirmInput.value;

    const isValid =
        value.length >= 8 &&
        value.length <= 12 &&
        /[A-Z]/.test(value) &&
        /[a-z]/.test(value) &&
        /[0-9]/.test(value) &&
        /[\W_]/.test(value) &&
        value === confirm;

    if (!isValid) {
        e.preventDefault();
        alert('La contraseña no cumple con los requisitos o no coincide.');
    }
});
</script>