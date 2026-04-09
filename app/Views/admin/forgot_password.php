<?php
$success  = isset($_GET['success']);
$error    = $_GET['error'] ?? null;
$messages = [
    'campos_vacios'   => 'Debe ingresar su correo electrónico.',
    'invalid_request' => 'La sesión del formulario expiró. Intente de nuevo.',
];
?>

<main class="flex-grow flex items-center justify-center p-4 pt-10">
    <div class="w-full max-w-md">
        <div class="bg-white p-8 rounded-xl shadow-2xl">
            <h1 class="text-3xl font-bold text-center text-superarse-morado-oscuro mb-4">Recuperar acceso administrativo</h1>

            <?php if ($success): ?>
                <div class="mb-6 bg-green-50 border border-green-300 text-green-800 rounded-lg p-4 text-sm text-center">
                    <p class="font-semibold mb-1">Solicitud registrada</p>
                    <p>Si existe una cuenta asociada a ese correo, un administrador procesará tu solicitud.</p>
                </div>
                <div class="mt-4 text-center">
                    <a href="<?= $basePath ?>/admin/login"
                        class="text-sm text-superarse-morado-medio hover:text-superarse-rosa">
                        ← Volver al panel de administración
                    </a>
                </div>

            <?php else: ?>
                <p class="text-center text-gray-600 mb-4">
                    Ingresa tu correo institucional para solicitar el restablecimiento de tu contraseña.
                </p>

                <?php if ($error && isset($messages[$error])): ?>
                    <div class="mb-4 p-3 bg-red-100 border border-red-300 text-red-700 rounded-lg text-sm">
                        <?= htmlspecialchars($messages[$error]) ?>
                    </div>
                <?php endif; ?>

                <div class="mb-5 bg-yellow-50 border border-yellow-300 rounded-lg p-4 text-sm text-yellow-800">
                    La solicitud quedará registrada en el sistema. Otro administrador con acceso activo deberá procesarla.
                </div>

                <form method="POST" action="<?= $basePath ?>/admin/forgot-password/submit" class="space-y-5">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                    <div>
                        <label for="email" class="block text-gray-700 text-sm font-semibold mb-2">
                            Correo electrónico institucional
                        </label>
                        <input type="email" id="email" name="email" required autocomplete="email"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-superarse-morado-medio"
                            placeholder="usuario@superarse.edu.ec">
                    </div>

                    <button type="submit"
                        class="w-full bg-superarse-rosa hover:bg-superarse-morado-medio text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                        Enviar solicitud
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <a href="<?= $basePath ?>/admin/login"
                        class="text-sm text-superarse-morado-medio hover:text-superarse-rosa">
                        ← Volver al inicio de sesión
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>
