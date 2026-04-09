<main class="flex-grow flex items-center justify-center p-4 pt-10">
    <div class="w-full max-w-md">
        <div class="bg-white p-8 rounded-xl shadow-2xl">
            <h1 class="text-3xl font-bold text-center text-superarse-morado-oscuro mb-6">Iniciar Sesión</h1>
            <p class="text-center text-gray-600 mb-6">Ingresa tu número de identificación y tu contraseña para acceder a tu información.</p>

            <?php
            $error = $_GET['error'] ?? null;
            $messages = [
                'campos_vacios' => 'Debe ingresar su número de identificación y su contraseña.',
                'invalid_credentials' => 'La contraseña ingresada no es correcta.',
                'invalid_request' => 'La sesión del formulario expiró. Intente nuevamente.',
                'error_sistema' => 'No fue posible iniciar sesión. Intente nuevamente.',
            ];
            ?>

            <?php if ($error && isset($messages[$error])): ?>
                <div class="mb-4 p-3 bg-red-100 border border-red-300 text-red-700 rounded-lg">
                    <?php echo htmlspecialchars($messages[$error]); ?>
                </div>
            <?php endif; ?>

            <div class="mb-6 rounded-lg border border-superarse-morado-medio/20 bg-superarse-morado-medio/5 p-4 text-sm text-gray-700">
                Tu contraseña inicial es tu número de identificación. En el primer ingreso se te pedirá cambiarla.
            </div>

            <form id="login-Form" action="<?php echo $basePath; ?>/login/check" method="POST" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                <div class="mb-6">
                    <label for="cedula" class="block text-gray-700 text-sm font-semibold mb-2">Número de
                        Identificación (Cédula)</label>
                    <input type="text" id="cedula" name="numero_identificacion" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-superarse-morado-medio"
                        placeholder="Ej: 0912345678">
                </div>

                <div class="mb-6">
                    <label for="password" class="block text-gray-700 text-sm font-semibold mb-2">Contraseña</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-superarse-morado-medio"
                        placeholder="Ingresa tu contraseña">
                </div>

                <button type="submit"
                    class="w-full bg-superarse-rosa hover:bg-superarse-morado-medio text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                    Ingresar
                </button>

                <div class="mt-2 text-center">
                    <a href="<?php echo $basePath; ?>/forgot-password"
                        class="text-sm text-superarse-morado-medio hover:text-superarse-rosa">
                        ¿Olvidaste tu contraseña?
                    </a>
                </div>
            </form>
        </div>
    </div>
</main>

    <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-superarse-rosa text-white">
                    <h5 class="modal-title" id="errorModalLabel">Error de Acceso</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body text-center text-gray-700" id="errorMessage">
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn bg-superarse-morado-medio text-white"
                        data-bs-dismiss="modal">Aceptar</button>
                </div>
            </div>
        </div>
    </div>

