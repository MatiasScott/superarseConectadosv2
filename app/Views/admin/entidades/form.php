<?php
$modo = $modo ?? 'crear';
$esEdicion = $modo === 'editar';
$entidad = $entidad ?? [];
$errors = $errors ?? [];
$programas = $programas ?? [];
$tutores = $tutores ?? [];

$valor = function (string $campo, $default = '') use ($entidad) {
    return $entidad[$campo] ?? $default;
};

$error = function (string $campo) use ($errors) {
    return $errors[$campo] ?? '';
};

$action = $esEdicion
    ? $basePath . '/admin/entidades/actualizar/' . (int) ($entidad['id_entidad'] ?? 0)
    : $basePath . '/admin/entidades/guardar';
?>

<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800"><?= $esEdicion ? 'Editar Entidad' : 'Nueva Entidad' ?></h2>
        <p class="text-sm text-gray-600">Gestiona información completa de la entidad, su programa y el tutor empresarial.</p>
    </div>

    <div class="flex gap-2">
        <a href="<?= $basePath ?>/admin/entidades" class="inline-flex items-center px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100 transition">
            Volver al listado
        </a>
        <a href="<?= $basePath ?>/admin/practicas" class="inline-flex items-center px-4 py-2 rounded-lg bg-gray-800 text-white hover:bg-black transition">
            Gestión de Prácticas
        </a>
    </div>
</div>

<div class="bg-white shadow rounded-xl p-6">
    <form method="POST" action="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>" class="space-y-8">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string) ($csrfToken ?? ''), ENT_QUOTES, 'UTF-8') ?>">

        <section>
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Datos de la entidad</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nombre de entidad *</label>
                    <input type="text" name="nombre_empresa" value="<?= htmlspecialchars((string) $valor('nombre_empresa'), ENT_QUOTES, 'UTF-8') ?>"
                        class="w-full border rounded-lg px-3 py-2 <?= $error('nombre_empresa') ? 'border-red-400' : 'border-gray-300' ?>" required>
                    <?php if ($error('nombre_empresa')): ?>
                        <p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($error('nombre_empresa'), ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">RUC *</label>
                    <input type="text" name="ruc" maxlength="13" value="<?= htmlspecialchars((string) $valor('ruc'), ENT_QUOTES, 'UTF-8') ?>"
                        class="w-full border rounded-lg px-3 py-2 <?= $error('ruc') ? 'border-red-400' : 'border-gray-300' ?>" required>
                    <?php if ($error('ruc')): ?>
                        <p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($error('ruc'), ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Razón social</label>
                    <input type="text" name="razon_social" value="<?= htmlspecialchars((string) $valor('razon_social'), ENT_QUOTES, 'UTF-8') ?>"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Persona de contacto</label>
                    <input type="text" name="persona_contacto" value="<?= htmlspecialchars((string) $valor('persona_contacto'), ENT_QUOTES, 'UTF-8') ?>"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Teléfono de contacto</label>
                    <input type="text" name="telefono_contacto" value="<?= htmlspecialchars((string) $valor('telefono_contacto'), ENT_QUOTES, 'UTF-8') ?>"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Correo de contacto</label>
                    <input type="email" name="email_contacto" value="<?= htmlspecialchars((string) $valor('email_contacto'), ENT_QUOTES, 'UTF-8') ?>"
                        class="w-full border rounded-lg px-3 py-2 <?= $error('email_contacto') ? 'border-red-400' : 'border-gray-300' ?>">
                    <?php if ($error('email_contacto')): ?>
                        <p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($error('email_contacto'), ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Plazas disponibles *</label>
                    <input type="number" min="0" name="plazas_disponibles" value="<?= htmlspecialchars((string) $valor('plazas_disponibles', 0), ENT_QUOTES, 'UTF-8') ?>"
                        class="w-full border rounded-lg px-3 py-2 <?= $error('plazas_disponibles') ? 'border-red-400' : 'border-gray-300' ?>" required>
                    <?php if ($error('plazas_disponibles')): ?>
                        <p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($error('plazas_disponibles'), ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Estado *</label>
                    <select name="estado" class="w-full border rounded-lg px-3 py-2 <?= $error('estado') ? 'border-red-400' : 'border-gray-300' ?>" required>
                        <?php $estadoActual = (string) $valor('estado', 'Disponible'); ?>
                        <option value="Disponible" <?= $estadoActual === 'Disponible' ? 'selected' : '' ?>>Disponible</option>
                        <option value="No Disponible" <?= $estadoActual === 'No Disponible' ? 'selected' : '' ?>>No Disponible</option>
                    </select>
                    <?php if ($error('estado')): ?>
                        <p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($error('estado'), ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Dirección</label>
                    <input type="text" name="direccion" value="<?= htmlspecialchars((string) $valor('direccion'), ENT_QUOTES, 'UTF-8') ?>"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
            </div>
        </section>

        <section>
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Relación con programa</h3>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Programa</label>
                <?php $idProgramaActual = (int) $valor('id_programa', 0); ?>
                <select name="id_programa" class="w-full border rounded-lg px-3 py-2 <?= $error('id_programa') ? 'border-red-400' : 'border-gray-300' ?>">
                    <option value="">Sin programa</option>
                    <?php foreach ($programas as $programa): ?>
                        <option value="<?= (int) $programa['id'] ?>" <?= $idProgramaActual === (int) $programa['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string) $programa['programa'], ENT_QUOTES, 'UTF-8') ?>
                            (<?= htmlspecialchars((string) $programa['codigo'], ENT_QUOTES, 'UTF-8') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($error('id_programa')): ?>
                    <p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($error('id_programa'), ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
            </div>
        </section>

        <section>
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Relación con tutor empresarial</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Tutor empresarial existente</label>
                    <?php $idTutorActual = (int) $valor('id_tutor_empresarial', 0); ?>
                    <select name="id_tutor_empresarial" class="w-full border rounded-lg px-3 py-2 <?= $error('id_tutor_empresarial') ? 'border-red-400' : 'border-gray-300' ?>">
                        <option value="">Sin tutor asignado</option>
                        <?php foreach ($tutores as $tutor): ?>
                            <option value="<?= (int) $tutor['id_tutor_empresa'] ?>" <?= $idTutorActual === (int) $tutor['id_tutor_empresa'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) $tutor['nombre_completo'], ENT_QUOTES, 'UTF-8') ?>
                                - <?= htmlspecialchars((string) $tutor['cedula'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($error('id_tutor_empresarial')): ?>
                        <p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($error('id_tutor_empresarial'), ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                    <p class="text-xs text-gray-500 mt-1">Si completas los campos de abajo y eliges un tutor existente, sus datos también se actualizarán.</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Cédula tutor</label>
                    <input type="text" name="tutor_cedula" value="<?= htmlspecialchars((string) $valor('tutor_cedula'), ENT_QUOTES, 'UTF-8') ?>"
                        class="w-full border rounded-lg px-3 py-2 <?= $error('tutor_cedula') ? 'border-red-400' : 'border-gray-300' ?>">
                    <?php if ($error('tutor_cedula')): ?>
                        <p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($error('tutor_cedula'), ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nombre completo tutor</label>
                    <input type="text" name="tutor_nombre_completo" value="<?= htmlspecialchars((string) $valor('tutor_nombre_completo'), ENT_QUOTES, 'UTF-8') ?>"
                        class="w-full border rounded-lg px-3 py-2 <?= $error('tutor_nombre_completo') ? 'border-red-400' : 'border-gray-300' ?>">
                    <?php if ($error('tutor_nombre_completo')): ?>
                        <p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($error('tutor_nombre_completo'), ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Función</label>
                    <input type="text" name="tutor_funcion" value="<?= htmlspecialchars((string) $valor('tutor_funcion'), ENT_QUOTES, 'UTF-8') ?>"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Teléfono tutor</label>
                    <input type="text" name="tutor_telefono" value="<?= htmlspecialchars((string) $valor('tutor_telefono'), ENT_QUOTES, 'UTF-8') ?>"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Correo tutor</label>
                    <input type="email" name="tutor_email" value="<?= htmlspecialchars((string) $valor('tutor_email'), ENT_QUOTES, 'UTF-8') ?>"
                        class="w-full border rounded-lg px-3 py-2 <?= $error('tutor_email') ? 'border-red-400' : 'border-gray-300' ?>">
                    <?php if ($error('tutor_email')): ?>
                        <p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($error('tutor_email'), ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Departamento</label>
                    <input type="text" name="tutor_departamento" value="<?= htmlspecialchars((string) $valor('tutor_departamento'), ENT_QUOTES, 'UTF-8') ?>"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
            </div>
        </section>

        <?php if ($esEdicion): ?>
            <section class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                <h3 class="text-base font-semibold text-gray-800 mb-3">Datos completos de la entidad (tabla entidades)</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                    <?php foreach ($entidad as $campo => $valorCampo): ?>
                        <?php if (strpos((string) $campo, 'tutor_') === 0 || strpos((string) $campo, 'programa_') === 0) continue; ?>
                        <div class="flex gap-2">
                            <span class="font-semibold text-gray-700"><?= htmlspecialchars((string) $campo, ENT_QUOTES, 'UTF-8') ?>:</span>
                            <span class="text-gray-600 break-all"><?= htmlspecialchars((string) ($valorCampo ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <div class="flex justify-end gap-2">
            <a href="<?= $basePath ?>/admin/entidades" class="px-5 py-2 rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300 transition">
                Cancelar
            </a>
            <button type="submit" class="px-5 py-2 rounded-lg bg-indigo-600 text-white font-semibold hover:bg-indigo-700 transition">
                <?= $esEdicion ? 'Guardar cambios' : 'Crear entidad' ?>
            </button>
        </div>
    </form>
</div>
