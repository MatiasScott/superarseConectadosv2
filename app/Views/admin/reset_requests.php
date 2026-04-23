<?php
$allRequests      = $requests ?? [];
$pendingRequests  = array_filter($allRequests, fn($r) => $r['status'] === 'pending');
$processedRequests = array_filter($allRequests, fn($r) => $r['status'] !== 'pending');
?>

<!-- Header -->
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Solicitudes de Restablecimiento</h2>
        <p class="text-sm text-gray-500 mt-1">Usuarios que han solicitado recuperar el acceso a su cuenta</p>
    </div>
    <a href="<?= $basePath ?>/admin/accounts"
        class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm px-4 py-2 rounded-lg transition">
        Gestionar cuentas admin
    </a>
</div>

<!-- Contraseña temporal revelada una sola vez -->
<?php if (!empty($_SESSION['temp_password_revealed'])): ?>
    <div class="mb-6 bg-blue-50 border border-blue-300 rounded-xl p-5">
        <p class="text-blue-800 font-semibold mb-1">Contraseña temporal generada</p>
        <p class="text-blue-700 text-sm mb-3">
            Comunica esta contraseña al usuario. <strong>Solo se muestra una vez.</strong>
            Al ingresar, el usuario deberá cambiarla.
        </p>
        <div class="inline-flex items-center gap-3 bg-white border border-blue-300 rounded-lg px-4 py-3">
            <code class="text-xl font-mono font-bold text-blue-900 tracking-widest select-all">
                <?= htmlspecialchars($_SESSION['temp_password_revealed']) ?>
            </code>
        </div>
        <?php if (!empty($_SESSION['temp_password_email_sent'])): ?>
            <p class="mt-2 text-xs text-green-700">
                ✓ También se envió un correo al usuario con esta contraseña temporal.
            </p>
        <?php endif; ?>
    </div>
    <?php
    unset($_SESSION['temp_password_revealed']);
    unset($_SESSION['temp_password_email_sent']);
    ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
        <?= htmlspecialchars($_SESSION['error']); ?>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['success'])): ?>
    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
        <?= htmlspecialchars($_SESSION['success']); ?>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<!-- KPIs -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <div class="bg-white shadow-md rounded-xl p-6 border-l-4 border-red-500">
        <p class="text-sm text-gray-500">Pendientes</p>
        <p class="text-3xl font-bold text-red-600 mt-2"><?= count($pendingRequests) ?></p>
    </div>
    <div class="bg-white shadow-md rounded-xl p-6 border-l-4 border-green-500">
        <p class="text-sm text-gray-500">Gestionadas (historial)</p>
        <p class="text-3xl font-bold text-green-600 mt-2"><?= count($processedRequests) ?></p>
    </div>
</div>

<!-- Pending table -->
<?php if (!empty($pendingRequests)): ?>
    <div class="mb-8">
        <h3 class="text-base font-semibold text-red-700 mb-3 flex items-center gap-2">
            Pendientes
            <span class="bg-red-100 text-red-700 text-sm px-2 py-0.5 rounded-full">
                <?= count($pendingRequests) ?>
            </span>
        </h3>
        <div class="bg-white shadow-md rounded-xl overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-6 py-3 text-left font-semibold text-gray-600">Tipo</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-600">Nombre</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-600">Identificación / Correo</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-600">Solicitado el</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-600">IP</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-600">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingRequests as $req): ?>
                        <tr class="border-b hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <?php if ($req['role'] === 'student'): ?>
                                    <span class="px-2 py-1 text-xs font-semibold bg-blue-100 text-blue-700 rounded-full">
                                        Estudiante
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 py-1 text-xs font-semibold bg-purple-100 text-purple-700 rounded-full">
                                        Admin
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 font-medium text-gray-800">
                                <?= htmlspecialchars($req['display_name']) ?>
                            </td>
                            <td class="px-6 py-4 text-gray-600 font-mono text-xs">
                                <?= htmlspecialchars($req['contact']) ?>
                            </td>
                            <td class="px-6 py-4 text-gray-500 text-xs">
                                <?= date('d/m/Y H:i', strtotime($req['requested_at'])) ?>
                            </td>
                            <td class="px-6 py-4 text-gray-400 font-mono text-xs">
                                <?= htmlspecialchars($req['ip_address'] ?? '—') ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-2 items-start">
                                    <?php if (!empty($req['account_id']) && isset($csrfTokensResolve[$req['id']])): ?>
                                        <form method="POST" action="<?= $basePath ?>/admin/reset-requests/resolve">
                                            <input type="hidden" name="csrf_token"
                                                value="<?= htmlspecialchars($csrfTokensResolve[$req['id']]) ?>">
                                            <input type="hidden" name="request_id" value="<?= (int) $req['id'] ?>">
                                            <button type="submit"
                                                class="bg-purple-700 hover:bg-purple-800 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition"
                                                onclick="return confirm('¿Generar contraseña temporal para <?= htmlspecialchars(addslashes($req['display_name'])) ?>?');">
                                                Restablecer y notificar
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-xs italic">Sin cuenta vinculada</span>
                                    <?php endif; ?>

                                    <?php if (isset($csrfTokensDiscard[$req['id']])): ?>
                                        <form method="POST" action="<?= $basePath ?>/admin/reset-requests/discard">
                                            <input type="hidden" name="csrf_token"
                                                value="<?= htmlspecialchars($csrfTokensDiscard[$req['id']]) ?>">
                                            <input type="hidden" name="request_id" value="<?= (int) $req['id'] ?>">
                                            <button type="submit"
                                                class="bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-semibold px-3 py-1.5 rounded-lg transition"
                                                onclick="return confirm('¿Descartar esta solicitud? Esta acción la moverá al historial.');">
                                                Descartar
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- History table -->
<?php if (!empty($processedRequests)): ?>
    <div>
        <h3 class="text-base font-semibold text-gray-500 mb-3">Historial de solicitudes gestionadas</h3>
        <div class="bg-white shadow-md rounded-xl overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-6 py-3 text-left font-semibold text-gray-600">Tipo</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-600">Nombre</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-600">Contacto</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-600">Solicitado</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-600">Gestionado por</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-600">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($processedRequests as $req): ?>
                        <?php
                        $managedBy = (string) ($req['resolved_by'] ?? '—');
                        $isDiscarded = $req['status'] === 'discarded' || strpos($managedBy, '[DESCARTADA] ') === 0;
                        if (strpos($managedBy, '[DESCARTADA] ') === 0) {
                            $managedBy = substr($managedBy, strlen('[DESCARTADA] '));
                        }
                        ?>
                        <tr class="border-b hover:bg-gray-50 opacity-70">
                            <td class="px-6 py-4">
                                <?php if ($req['role'] === 'student'): ?>
                                    <span class="px-2 py-1 text-xs bg-blue-50 text-blue-600 rounded-full">Estudiante</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 text-xs bg-purple-50 text-purple-600 rounded-full">Admin</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-gray-700"><?= htmlspecialchars($req['display_name']) ?></td>
                            <td class="px-6 py-4 text-gray-500 font-mono text-xs"><?= htmlspecialchars($req['contact']) ?></td>
                            <td class="px-6 py-4 text-gray-400 text-xs">
                                <?= date('d/m/Y H:i', strtotime($req['requested_at'])) ?>
                            </td>
                            <td class="px-6 py-4 text-gray-500 text-xs">
                                <?= htmlspecialchars($managedBy) ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($isDiscarded): ?>
                                    <span class="px-2 py-1 text-xs bg-amber-100 text-amber-700 rounded-full">Descartada</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 text-xs bg-green-100 text-green-700 rounded-full">Resuelta</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php if (empty($allRequests)): ?>
    <div class="text-center py-20 text-gray-400">
        <p class="text-lg font-medium">No hay solicitudes registradas</p>
        <p class="text-sm mt-2">
            Las solicitudes de restablecimiento de contraseña de estudiantes y administradores aparecerán aquí.
        </p>
    </div>
<?php endif; ?>