<main class="flex-grow flex items-center justify-center p-4 pt-10">
    <div class="w-full max-w-md">
        <div class="bg-white p-8 rounded-xl shadow-2xl">
            <h1 class="text-3xl font-bold text-center text-superarse-morado-oscuro mb-6">Acceso Administrativo</h1>
            <p class="text-center text-gray-600 mb-8">Ingresa la contraseña de administrador para acceder al sistema.</p>

            <?php if (isset($_GET['error'])): ?>
                <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
                    <?php 
                    if ($_GET['error'] == 'invalid_password') {
                        echo "Contraseña incorrecta";
                    } else {
                        echo "Error al iniciar sesión";
                    }
                    ?>
                </div>
            <?php endif; ?>

            <form action="<?php echo $basePath; ?>/admin/login/check" method="POST" class="space-y-6">
                <div class="mb-6">
                    <label for="password" class="block text-gray-700 text-sm font-semibold mb-2">Contraseña de Administrador</label>
                    <input type="password" id="password" name="admin_password" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-superarse-morado-medio"
                        placeholder="Ingrese la contraseña">
                </div>
                <button type="submit"
                    class="w-full bg-superarse-rosa hover:bg-superarse-morado-medio text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                    Ingresar al Panel
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="<?php echo $basePath; ?>/login" class="text-sm text-superarse-morado-medio hover:text-superarse-rosa">
                    ← Volver al login de estudiantes
                </a>
            </div>
        </div>
    </div>
</main>
