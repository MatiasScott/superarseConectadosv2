<!-- Main Content -->
<main class="flex-grow max-w-2xl mx-auto py-10 px-4 sm:px-6 lg:px-8 w-full">
    <div class="bg-white shadow-2xl rounded-2xl p-8 border border-gray-100">

        <h2 class="text-2xl font-bold text-gray-900 mb-2">
            Editar Estado de Pasantía
        </h2>

        <p class="text-gray-600 mb-8">
            Estudiante:
            <span class="font-semibold text-superarse-morado-medio">
                <?php echo htmlspecialchars($practica['estudiante_nombre'] ?? 'N/A'); ?>
            </span>
        </p>

        <form method="POST" class="space-y-8">

            <!-- Estado -->
            <div>
                <label for="estado_fase_uno_completado"
                    class="block text-base font-semibold text-gray-900 mb-3">
                    Estado Fase Uno
                </label>

                <select id="estado_fase_uno_completado"
                    name="estado_fase_uno_completado"
                    required
                    class="block w-full px-4 py-3 text-lg border-2 border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition">

                    <option value="0"
                        <?php echo ($practica['estado_fase_uno_completado'] == 0) ? 'selected' : ''; ?>>
                        Pendiente
                    </option>

                    <option value="1"
                        <?php echo ($practica['estado_fase_uno_completado'] == 1) ? 'selected' : ''; ?>>
                        Completado
                    </option>
                </select>

                <div class="mt-4">
                    <?php if ($practica['estado_fase_uno_completado'] == 1): ?>
                        <span class="px-3 py-1 inline-flex text-sm font-semibold rounded-full bg-green-100 text-green-800">
                            Estado actual: Completado
                        </span>
                    <?php else: ?>
                        <span class="px-3 py-1 inline-flex text-sm font-semibold rounded-full bg-yellow-100 text-yellow-800">
                            Estado actual: Pendiente
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Botones -->
            <div class="flex flex-col sm:flex-row justify-end gap-4 pt-6 border-t border-gray-200">

                <a href="<?php echo $basePath; ?>/admin/practicas"
                    class="w-full sm:w-auto text-center px-6 py-3 border border-gray-300 rounded-xl text-gray-700 hover:bg-gray-100 font-semibold transition">
                    Cancelar
                </a>

                <button type="submit"
                    class="w-full sm:w-auto px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-xl font-semibold shadow-md hover:shadow-lg transition duration-300">
                    Guardar Cambios
                </button>

            </div>

        </form>
    </div>
</main>