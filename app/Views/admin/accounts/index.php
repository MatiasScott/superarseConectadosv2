<?php
$accounts = $accounts ?? [];
$students = $students ?? [];
$programs = $programs ?? [];
$studentAccountsIndex = $studentAccountsIndex ?? [];

$adminSearch = $adminSearch ?? '';
$adminPage = (int) ($adminPage ?? 1);
$totalAdmins = (int) ($totalAdmins ?? count($accounts));
$totalAdminPages = (int) ($totalAdminPages ?? 1);

$studentSearch = $studentSearch ?? '';
$studentProgram = $studentProgram ?? '';
$studentPage = (int) ($studentPage ?? 1);
$totalStudents = (int) ($totalStudents ?? count($students));
$totalStudentPages = (int) ($totalStudentPages ?? 1);

$currentQuery = $currentQuery ?? '';
$myAccountId = (int) ($_SESSION['auth_account_id'] ?? 0);

$activeAdminAccounts = array_filter($accounts, fn($a) => (int) ($a['is_active'] ?? 0) === 1);
$totalActiveAdmins = count($activeAdminAccounts);
$studentProvisioned = array_filter($students, fn($s) => isset($studentAccountsIndex[$s['numero_identificacion'] ?? '']));

$buildQuery = function (array $overrides) use ($adminSearch, $adminPage, $studentSearch, $studentProgram, $studentPage) {
    $params = [
        'admin_q' => $adminSearch,
        'admin_page' => $adminPage,
        'student_q' => $studentSearch,
        'student_program' => $studentProgram,
        'student_page' => $studentPage,
    ];

    foreach ($overrides as $key => $value) {
        $params[$key] = $value;
    }

    return http_build_query(array_filter($params, function ($value) {
        return !($value === '' || $value === null);
    }));
};
?>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Gestión de Cuentas</h2>
        <p class="text-sm text-gray-500 mt-1">Administradores y estudiantes con control de acceso</p>
    </div>
    <a href="<?= $basePath ?>/admin/reset-requests"
        class="flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm px-4 py-2 rounded-lg transition">
        ← Solicitudes de restablecimiento
    </a>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
        <?= htmlspecialchars($_SESSION['success']); ?>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
        <?= htmlspecialchars($_SESSION['error']); ?>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white shadow-md rounded-xl p-6 border-l-4 border-purple-700">
        <p class="text-sm text-gray-500">Admins listados (página)</p>
        <p class="text-3xl font-bold text-purple-800 mt-2"><?= count($accounts) ?></p>
        <p class="text-xs text-gray-400 mt-1">Total general: <?= $totalAdmins ?></p>
    </div>
    <div class="bg-white shadow-md rounded-xl p-6 border-l-4 border-green-500">
        <p class="text-sm text-gray-500">Admins activos (página)</p>
        <p class="text-3xl font-bold text-green-600 mt-2"><?= $totalActiveAdmins ?></p>
    </div>
    <div class="bg-white shadow-md rounded-xl p-6 border-l-4 border-blue-500">
        <p class="text-sm text-gray-500">Estudiantes listados (página)</p>
        <p class="text-3xl font-bold text-blue-700 mt-2"><?= count($students) ?></p>
        <p class="text-xs text-gray-400 mt-1">Total general: <?= $totalStudents ?></p>
    </div>
</div>

<div class="bg-white shadow-md rounded-xl p-6 mb-8">
    <h3 class="text-lg font-semibold text-gray-700 mb-4">Crear nueva cuenta de administrador</h3>

    <form method="POST" action="<?= $basePath ?>/admin/accounts/store" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfTokenCreate) ?>">
        <input type="hidden" name="return_query" value="<?= htmlspecialchars($currentQuery) ?>">

        <div>
            <label class="block text-gray-700 text-sm font-semibold mb-1">Nombre completo <span class="text-red-500">*</span></label>
            <input type="text" name="display_name" required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-600 focus:outline-none"
                placeholder="Ej: Maria Garcia">
        </div>

        <div>
            <label class="block text-gray-700 text-sm font-semibold mb-1">Correo electrónico <span class="text-red-500">*</span></label>
            <input type="email" name="email" required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-600 focus:outline-none"
                placeholder="usuario@superarse.edu.ec">
        </div>

        <div>
            <label class="block text-gray-700 text-sm font-semibold mb-1">Número de identificación <span class="text-gray-400 font-normal">(opcional)</span></label>
            <input type="text" name="numero_identificacion" maxlength="20"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-600 focus:outline-none"
                placeholder="Cédula del administrador">
        </div>

        <div>
            <label class="block text-gray-700 text-sm font-semibold mb-1">Contraseña temporal <span class="text-red-500">*</span></label>
            <input type="password" name="temp_password" required minlength="8" maxlength="12"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-600 focus:outline-none"
                placeholder="8-12 caracteres: May, min, número, signo">
            <p class="text-xs text-gray-400 mt-1">El administrador deberá cambiarla en su primer ingreso.</p>
        </div>

        <div class="md:col-span-2 flex justify-end pt-2">
            <button type="submit" class="bg-purple-700 hover:bg-purple-800 text-white font-semibold px-6 py-2 rounded-lg transition">
                Crear cuenta
            </button>
        </div>
    </form>
</div>

<div class="bg-white shadow-md rounded-xl p-6 mb-4">
    <form method="GET" action="<?= $basePath ?>/admin/accounts" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
        <div class="md:col-span-2">
            <label class="block text-sm font-semibold text-gray-700 mb-1">Buscar admins</label>
            <input type="text" name="admin_q" value="<?= htmlspecialchars($adminSearch) ?>"
                placeholder="Nombre, correo o cédula"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-600 focus:outline-none">
        </div>
        <input type="hidden" name="student_q" value="<?= htmlspecialchars($studentSearch) ?>">
        <input type="hidden" name="student_program" value="<?= htmlspecialchars($studentProgram) ?>">
        <input type="hidden" name="student_page" value="<?= (int) $studentPage ?>">
        <input type="hidden" name="admin_page" value="1">
        <div class="flex gap-2 md:justify-end">
            <button type="submit" class="bg-purple-700 hover:bg-purple-800 text-white px-4 py-2 rounded-lg text-sm font-semibold">Buscar</button>
            <a href="<?= $basePath ?>/admin/accounts?<?= htmlspecialchars($buildQuery(['admin_q' => '', 'admin_page' => 1])) ?>"
                class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold">Limpiar</a>
        </div>
    </form>
</div>

<div class="bg-white shadow-md rounded-xl overflow-x-auto mb-3">
    <table class="min-w-full text-sm">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="px-6 py-3 text-left font-semibold text-gray-600">Nombre</th>
                <th class="px-6 py-3 text-left font-semibold text-gray-600">Correo</th>
                <th class="px-6 py-3 text-left font-semibold text-gray-600">Identificación</th>
                <th class="px-6 py-3 text-left font-semibold text-gray-600">Último acceso</th>
                <th class="px-6 py-3 text-left font-semibold text-gray-600">Estado</th>
                <th class="px-6 py-3 text-left font-semibold text-gray-600">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($accounts)): ?>
                <?php foreach ($accounts as $acc): ?>
                    <tr class="border-b hover:bg-gray-50 transition <?= !(int) ($acc['is_active'] ?? 0) ? 'opacity-60' : '' ?>">
                        <td class="px-6 py-4 font-medium text-gray-800">
                            <?= htmlspecialchars($acc['display_name']) ?>
                            <?php if (!empty($acc['must_change_password'])): ?>
                                <span class="ml-2 px-2 py-0.5 bg-yellow-100 text-yellow-700 text-xs rounded-full">Clave temporal</span>
                            <?php endif; ?>
                            <?php if ((int) ($acc['id'] ?? 0) === $myAccountId): ?>
                                <span class="ml-2 px-2 py-0.5 bg-blue-100 text-blue-600 text-xs rounded-full">Tú</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-gray-600"><?= htmlspecialchars($acc['email'] ?? '—') ?></td>
                        <td class="px-6 py-4 text-gray-500"><?= htmlspecialchars($acc['numero_identificacion'] ?? '—') ?></td>
                        <td class="px-6 py-4 text-gray-400 text-xs">
                            <?= !empty($acc['last_login_at']) ? date('d/m/Y H:i', strtotime($acc['last_login_at'])) : 'Nunca' ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php if ((int) ($acc['is_active'] ?? 0) === 1): ?>
                                <span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700">Activa</span>
                            <?php else: ?>
                                <span class="px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-700">Inactiva</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4">
                            <a href="<?= $basePath ?>/admin/accounts/permissions/<?= (int) ($acc['id'] ?? 0) ?>?return_query=<?= urlencode($currentQuery) ?>"
                                class="inline-block mr-3 text-purple-700 hover:underline font-medium text-sm">
                                Permisos
                            </a>

                            <?php if ((int) ($acc['id'] ?? 0) !== $myAccountId): ?>
                                <form method="POST" action="<?= $basePath ?>/admin/accounts/toggle"
                                    onsubmit="return confirm('¿Confirmar cambio de estado para <?= htmlspecialchars(addslashes($acc['display_name'])) ?>?');">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfTokenToggle) ?>">
                                    <input type="hidden" name="return_query" value="<?= htmlspecialchars($currentQuery) ?>">
                                    <input type="hidden" name="account_id" value="<?= (int) ($acc['id'] ?? 0) ?>">
                                    <input type="hidden" name="new_status" value="<?= (int) ($acc['is_active'] ?? 0) ? '0' : '1' ?>">
                                    <button type="submit" class="<?= (int) ($acc['is_active'] ?? 0) ? 'text-red-600 hover:underline' : 'text-green-600 hover:underline' ?> font-medium text-sm">
                                        <?= (int) ($acc['is_active'] ?? 0) ? 'Desactivar' : 'Activar' ?>
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="text-gray-400 text-xs italic">No disponible</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center py-12 text-gray-400">No hay cuentas de administrador para este filtro.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($totalAdminPages > 1): ?>
    <div class="flex items-center justify-between mb-12 text-sm">
        <span class="text-gray-500">Página <?= $adminPage ?> de <?= $totalAdminPages ?></span>
        <div class="flex gap-2">
            <?php if ($adminPage > 1): ?>
                <a class="px-3 py-1.5 rounded bg-gray-100 hover:bg-gray-200 text-gray-700" href="<?= $basePath ?>/admin/accounts?<?= htmlspecialchars($buildQuery(['admin_page' => $adminPage - 1])) ?>">Anterior</a>
            <?php endif; ?>
            <?php if ($adminPage < $totalAdminPages): ?>
                <a class="px-3 py-1.5 rounded bg-gray-100 hover:bg-gray-200 text-gray-700" href="<?= $basePath ?>/admin/accounts?<?= htmlspecialchars($buildQuery(['admin_page' => $adminPage + 1])) ?>">Siguiente</a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
    <div>
        <h3 class="text-2xl font-bold text-gray-800">Cuentas de estudiantes</h3>
        <p class="text-sm text-gray-500 mt-1">Búsqueda por cédula, nombre o programa. Máximo 20 por página.</p>
    </div>
    <div class="text-sm text-gray-500">
        Creadas en esta página: <span class="font-semibold text-gray-700"><?= count($studentProvisioned) ?></span>
        de <span class="font-semibold text-gray-700"><?= count($students) ?></span>
    </div>
</div>

<div class="bg-white shadow-md rounded-xl p-6 mb-4">
    <form method="GET" action="<?= $basePath ?>/admin/accounts" class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
        <div class="md:col-span-3">
            <label class="block text-sm font-semibold text-gray-700 mb-1">Buscar estudiantes</label>
            <input type="text" name="student_q" value="<?= htmlspecialchars($studentSearch) ?>"
                placeholder="Cédula, nombre o programa"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-600 focus:outline-none">
        </div>
        <div class="md:col-span-2">
            <label class="block text-sm font-semibold text-gray-700 mb-1">Programa</label>
            <select name="student_program" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-600 focus:outline-none">
                <option value="">Todos</option>
                <?php foreach ($programs as $program): ?>
                    <option value="<?= htmlspecialchars($program) ?>" <?= $studentProgram === $program ? 'selected' : '' ?>>
                        <?= htmlspecialchars($program) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <input type="hidden" name="admin_q" value="<?= htmlspecialchars($adminSearch) ?>">
        <input type="hidden" name="admin_page" value="<?= (int) $adminPage ?>">
        <input type="hidden" name="student_page" value="1">
        <div class="flex gap-2 md:justify-end">
            <button type="submit" class="bg-purple-700 hover:bg-purple-800 text-white px-4 py-2 rounded-lg text-sm font-semibold">Filtrar</button>
            <a href="<?= $basePath ?>/admin/accounts?<?= htmlspecialchars($buildQuery(['student_q' => '', 'student_program' => '', 'student_page' => 1])) ?>"
                class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold">Limpiar</a>
        </div>
    </form>
</div>

<div class="bg-white shadow-md rounded-xl overflow-x-auto mb-3">
    <table class="min-w-full text-sm">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="px-6 py-3 text-left font-semibold text-gray-600">Estudiante</th>
                <th class="px-6 py-3 text-left font-semibold text-gray-600">Cédula</th>
                <th class="px-6 py-3 text-left font-semibold text-gray-600">Correo</th>
                <th class="px-6 py-3 text-left font-semibold text-gray-600">Programa</th>
                <th class="px-6 py-3 text-left font-semibold text-gray-600">Estado cuenta</th>
                <th class="px-6 py-3 text-left font-semibold text-gray-600">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($students)): ?>
                <?php foreach ($students as $student): ?>
                    <?php
                    $identification = $student['numero_identificacion'] ?? '';
                    $studentAccount = $studentAccountsIndex[$identification] ?? null;
                    ?>
                    <tr class="border-b hover:bg-gray-50 transition">
                        <td class="px-6 py-4 font-medium text-gray-800"><?= htmlspecialchars($student['nombre_completo'] ?? '') ?></td>
                        <td class="px-6 py-4 text-gray-600 font-mono text-xs"><?= htmlspecialchars($identification) ?></td>
                        <td class="px-6 py-4 text-gray-600"><?= htmlspecialchars(($student['correo_electronico'] ?? '') ?: '—') ?></td>
                        <td class="px-6 py-4 text-gray-500"><?= htmlspecialchars(($student['programa'] ?? '') ?: '—') ?></td>
                        <td class="px-6 py-4">
                            <?php if ($studentAccount): ?>
                                <span class="px-3 py-1 text-xs font-semibold rounded-full <?= (int) ($studentAccount['is_active'] ?? 0) === 1 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                    <?= (int) ($studentAccount['is_active'] ?? 0) === 1 ? 'Activa' : 'Inactiva' ?>
                                </span>
                                <?php if (!empty($studentAccount['must_change_password'])): ?>
                                    <span class="ml-2 px-2 py-0.5 bg-yellow-100 text-yellow-700 text-xs rounded-full">Cambio pendiente</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="px-3 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-600">No creada</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php if (!$studentAccount): ?>
                                <form method="POST" action="<?= $basePath ?>/admin/student-accounts/provision"
                                    onsubmit="return confirm('¿Crear cuenta para <?= htmlspecialchars(addslashes($student['nombre_completo'] ?? '')) ?>? La contraseña inicial será su número de identificación.');"
                                    class="inline-block mr-3">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfTokenStudent) ?>">
                                    <input type="hidden" name="return_query" value="<?= htmlspecialchars($currentQuery) ?>">
                                    <input type="hidden" name="user_id" value="<?= (int) ($student['id_usuario'] ?? 0) ?>">
                                    <button type="submit" class="text-purple-700 hover:underline font-medium text-sm">Crear cuenta</button>
                                </form>
                            <?php else: ?>
                                <form method="POST" action="<?= $basePath ?>/admin/student-accounts/toggle" class="inline-block mr-3"
                                    onsubmit="return confirm('¿Confirmar cambio de estado de esta cuenta de estudiante?');">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfTokenStudentToggle) ?>">
                                    <input type="hidden" name="return_query" value="<?= htmlspecialchars($currentQuery) ?>">
                                    <input type="hidden" name="account_id" value="<?= (int) ($studentAccount['id'] ?? 0) ?>">
                                    <input type="hidden" name="new_status" value="<?= (int) ($studentAccount['is_active'] ?? 0) ? '0' : '1' ?>">
                                    <button type="submit" class="<?= (int) ($studentAccount['is_active'] ?? 0) ? 'text-red-600 hover:underline' : 'text-green-600 hover:underline' ?> font-medium text-sm">
                                        <?= (int) ($studentAccount['is_active'] ?? 0) ? 'Desactivar' : 'Reactivar' ?>
                                    </button>
                                </form>

                                <form method="POST" action="<?= $basePath ?>/admin/student-accounts/reset" class="inline-block"
                                    onsubmit="return confirm('¿Restablecer contraseña? La clave temporal volverá a ser la cédula.');">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfTokenStudentReset) ?>">
                                    <input type="hidden" name="return_query" value="<?= htmlspecialchars($currentQuery) ?>">
                                    <input type="hidden" name="account_id" value="<?= (int) ($studentAccount['id'] ?? 0) ?>">
                                    <button type="submit" class="text-blue-700 hover:underline font-medium text-sm">Restablecer clave</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center py-12 text-gray-400">No se encontraron estudiantes para este filtro.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($totalStudentPages > 1): ?>
    <div class="flex items-center justify-between text-sm">
        <span class="text-gray-500">Página <?= $studentPage ?> de <?= $totalStudentPages ?></span>
        <div class="flex gap-2">
            <?php if ($studentPage > 1): ?>
                <a class="px-3 py-1.5 rounded bg-gray-100 hover:bg-gray-200 text-gray-700" href="<?= $basePath ?>/admin/accounts?<?= htmlspecialchars($buildQuery(['student_page' => $studentPage - 1])) ?>">Anterior</a>
            <?php endif; ?>
            <?php if ($studentPage < $totalStudentPages): ?>
                <a class="px-3 py-1.5 rounded bg-gray-100 hover:bg-gray-200 text-gray-700" href="<?= $basePath ?>/admin/accounts?<?= htmlspecialchars($buildQuery(['student_page' => $studentPage + 1])) ?>">Siguiente</a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
