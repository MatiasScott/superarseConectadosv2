<?php
$meses = [
    1 => 'Enero',
    2 => 'Febrero',
    3 => 'Marzo',
    4 => 'Abril',
    5 => 'Mayo',
    6 => 'Junio',
    7 => 'Julio',
    8 => 'Agosto',
    9 => 'Septiembre',
    10 => 'Octubre',
    11 => 'Noviembre',
    12 => 'Diciembre',
];
?>

<h2 class="text-2xl font-bold mb-6">Paso 3: Planificación mensual por actividad</h2>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800">
        <?= htmlspecialchars((string) $_SESSION['success'], ENT_QUOTES, 'UTF-8') ?>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['error'])): ?>
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800">
        <?= htmlspecialchars((string) $_SESSION['error'], ENT_QUOTES, 'UTF-8') ?>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="bg-white shadow-lg rounded-2xl p-6 mb-6">
    <p class="text-sm text-gray-600">POA: #<?= (int) ($actividad['poa_id'] ?? 0) ?></p>
    <h3 class="text-xl font-bold text-gray-800 mt-1"><?= htmlspecialchars((string) ($actividad['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h3>
    <p class="text-sm text-gray-500 mt-1">Tipo: <?= htmlspecialchars((string) ($actividad['tipo_registro'] ?? ''), ENT_QUOTES, 'UTF-8') ?> | Meta: <?= htmlspecialchars((string) ($actividad['meta'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
</div>

<form method="POST" action="<?= $basePath ?>/admin/actividad/cronograma/<?= (int) ($actividad['id'] ?? 0) ?>" class="bg-white shadow-lg rounded-2xl p-6">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm text-left">
            <thead class="bg-gray-100 text-gray-600 uppercase text-xs tracking-wider">
                <tr>
                    <th class="px-4 py-3">Mes</th>
                    <th class="px-4 py-3">Avance (%)</th>
                    <th class="px-4 py-3">Semáforo</th>
                    <th class="px-4 py-3">Observación técnica</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($meses as $numeroMes => $nombreMes): ?>
                    <?php $filaMes = $cronograma[$numeroMes] ?? []; ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium"><?= htmlspecialchars($nombreMes, ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="px-4 py-3">
                            <input
                                type="number"
                                min="0"
                                max="100"
                                step="0.01"
                                name="avance[<?= $numeroMes ?>]"
                                value="<?= htmlspecialchars((string) ($filaMes['avance'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>"
                                class="w-28 border rounded-lg px-3 py-2"
                            >
                        </td>
                        <td class="px-4 py-3">
                            <?php $semaforo = (string) ($filaMes['estado_semaforo'] ?? 'no_cumple'); ?>
                            <select name="estado_semaforo[<?= $numeroMes ?>]" class="border rounded-lg px-3 py-2">
                                <option value="no_cumple" <?= $semaforo === 'no_cumple' ? 'selected' : '' ?>>No cumple</option>
                                <option value="cumple_parcialmente" <?= $semaforo === 'cumple_parcialmente' ? 'selected' : '' ?>>Cumple parcialmente</option>
                                <option value="cumple_segun_planificado" <?= $semaforo === 'cumple_segun_planificado' ? 'selected' : '' ?>>Cumple según planificado</option>
                            </select>
                        </td>
                        <td class="px-4 py-3">
                            <input
                                type="text"
                                name="observaciones[<?= $numeroMes ?>]"
                                value="<?= htmlspecialchars((string) ($filaMes['observaciones'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                class="w-full border rounded-lg px-3 py-2"
                                placeholder="Detalle técnico si existe desfase"
                            >
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-5 flex flex-wrap gap-3">
        <button type="submit" class="bg-superarse-morado-medio hover:bg-superarse-morado-oscuro text-white px-5 py-2 rounded-lg font-semibold text-sm">Guardar cronograma mensual</button>
        <a href="<?= $basePath ?>/admin/plan-estrategico?poa=<?= (int) ($actividad['poa_id'] ?? 0) ?>" class="px-5 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm font-semibold hover:bg-gray-50">Volver a actividades</a>
    </div>
</form>
