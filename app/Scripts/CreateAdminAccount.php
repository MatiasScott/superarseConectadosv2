<?php

require_once __DIR__ . '/../Helpers/AuthSecurity.php';
require_once __DIR__ . '/../Models/AuthAccountModel.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "Este script solo puede ejecutarse desde consola." . PHP_EOL;
    exit(1);
}

$email = trim($argv[1] ?? '');
$displayName = trim($argv[2] ?? '');
$identification = trim($argv[3] ?? '');
$temporaryPassword = $argv[4] ?? '';

if ($email === '' || $displayName === '' || $temporaryPassword === '') {
    echo 'Uso: php app/Scripts/CreateAdminAccount.php correo nombre_completo identificacion_opcional clave_temporal' . PHP_EOL;
    exit(1);
}

$policyError = AuthSecurity::validatePasswordPolicy($temporaryPassword);
if ($policyError !== null) {
    echo 'Error: ' . $policyError . PHP_EOL;
    exit(1);
}

$model = new AuthAccountModel();
$result = $model->createAdminAccount([
    'email' => $email,
    'display_name' => $displayName,
    'numero_identificacion' => $identification,
    'password_hash' => password_hash($temporaryPassword, PASSWORD_DEFAULT),
    'must_change_password' => true,
]);

if (empty($result['success'])) {
    echo 'Error: ' . ($result['message'] ?? 'No fue posible crear la cuenta.') . PHP_EOL;
    exit(1);
}

echo 'Cuenta creada correctamente para: ' . $email . PHP_EOL;
echo 'El administrador deberá cambiar la contraseña en su primer ingreso.' . PHP_EOL;