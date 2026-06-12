<?php
$credenciales = (isset($data['infoCredenciales']) && is_array($data['infoCredenciales'])) ? $data['infoCredenciales'] : [];
$credencialMoodle = $credenciales[0] ?? [];
$credencialBiblioteca = $credenciales[0] ?? [];
?>

<div id="credenciales" class="tab-pane hidden">
    <h3 class="text-xl font-semibold text-superarse-morado-oscuro mb-4">Credenciales de Acceso a
        Plataformas</h3>
    <?php if (!empty($credenciales)):
    ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($credenciales as $c):
            ?>
                <div class="bg-gray-50 p-4 rounded-lg shadow-md border border-gray-200">
                    <h4 class="font-bold text-superarse-morado-oscuro mb-3 text-lg">
                        <?php echo htmlspecialchars($c['plataforma'] ?? 'Plataforma'); ?></h4>
                    <p class="text-sm text-gray-700">
                        <i class="fas fa-user-circle mr-2"></i> Usuario: <strong
                            class="text-superarse-rosa"><?php echo htmlspecialchars($c['usuario_acceso'] ?? 'N/D'); ?></strong>
                    </p>
                    <p class="text-sm text-gray-700 mt-2">
                        <i class="fas fa-key mr-2"></i> Contraseña: <strong
                            class="text-red-600"><?php echo htmlspecialchars($c['clave_acceso'] ?? 'N/D'); ?></strong>
                    </p>
                    <p class="text-sm text-gray-700 mt-2">
                        <i class="fas fa-link mr-2"></i> Link: <a href="<?php echo htmlspecialchars($c['link_acceso'] ?? '#'); ?>"
                            target="_blank" class="text-blue-600 hover:underline break-all">Acceder Aquí</a>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-gray-50 p-4 rounded-lg shadow-md border border-gray-200">
                <h4 class="font-bold text-superarse-morado-oscuro mb-3 text-lg">
                    MOODLE - INGLES</h4>
                <p class="text-sm text-gray-700">
                    <i class="fas fa-user-circle mr-2"></i> Usuario: <strong
                        class="text-superarse-rosa"><?php echo htmlspecialchars($credencialMoodle['usuario_acceso_moodle'] ?? 'N/D'); ?></strong>
                </p>
                <p class="text-sm text-gray-700 mt-2">
                    <i class="fas fa-key mr-2"></i> Contraseña: <strong
                        class="text-red-600"><?php echo htmlspecialchars($credencialMoodle['clave_acceso_moodle'] ?? 'N/D'); ?></strong>
                </p>
                <p class="text-sm text-gray-700 mt-2">
                    <i class="fas fa-file-alt mr-2"></i> Nivel: <strong
                        class="text-red-600"><?php echo htmlspecialchars($credencialMoodle['nivel_acceso_moodle'] ?? 'N/D'); ?></strong>
                </p>
                <p class="text-sm text-gray-700 mt-2">
                    <i class="fas fa-link mr-2"></i> Link: <a href="https://aulas.superarse.edu.ec/my/courses.php"
                        target="_blank" class="text-blue-600 hover:underline break-all">Acceder Aquí</a>
                </p>
            </div>

            <div class="bg-gray-50 p-4 rounded-lg shadow-md border border-gray-200">
                <h4 class="font-bold text-superarse-morado-oscuro mb-3 text-lg">
                    Biblioteca Dra. Mery Navas</h4>
                <p class="text-sm text-gray-700">
                    <i class="fas fa-user-circle mr-2"></i> Usuario: <strong
                        class="text-superarse-rosa"><?php echo htmlspecialchars($credencialBiblioteca['usuario_acceso_biblioteca'] ?? 'N/D'); ?></strong>
                </p>
                <p class="text-sm text-gray-700 mt-2">
                    <i class="fas fa-key mr-2"></i> Contraseña: <strong
                        class="text-red-600"><?php echo htmlspecialchars("ISTS" . ($credencialBiblioteca['usuario_acceso_biblioteca'] ?? 'N/D')); ?></strong>
                </p>
                <p class="text-sm text-gray-700 mt-2">
                    <i class="fas fa-link mr-2"></i> Link: <a href="https://biblioteca.superarse.ec/"
                        target="_blank" class="text-blue-600 hover:underline break-all">Acceder Aquí</a>
                </p>
            </div>
        </div>

        <div class="mt-6 text-center">
            <span>
                <p>
                    <strong>NOTA IMPORTANTE:</strong> Las contraseñas aquí listadas son **provisionales**. Por motivos de seguridad, solo funcionan en su primer inicio de sesión. Se recomienda cambiarlas inmediatamente después de acceder.
                </p>
            </span>
        </div>
    <?php else: ?>
        <p class="text-gray-500">No hay credenciales de acceso a plataformas registradas.</p>
    <?php endif; ?>
</div>