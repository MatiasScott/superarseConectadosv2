<?php

class AuthSecurity
{
    public static function generateCsrfToken($formKey)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['csrf_tokens']) || !is_array($_SESSION['csrf_tokens'])) {
            $_SESSION['csrf_tokens'] = [];
        }

        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_tokens'][$formKey] = $token;

        return $token;
    }

    public static function validateCsrfToken($formKey, $submittedToken)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $storedToken = $_SESSION['csrf_tokens'][$formKey] ?? null;
        unset($_SESSION['csrf_tokens'][$formKey]);

        if (!is_string($storedToken) || !is_string($submittedToken) || $submittedToken === '') {
            return false;
        }

        return hash_equals($storedToken, $submittedToken);
    }

    public static function validatePasswordPolicy($password)
    {
        if (!is_string($password)) {
            return 'La contraseña enviada no es válida.';
        }

        $length = strlen($password);

        if ($length < 8 || $length > 12) {
            return 'La contraseña debe tener entre 8 y 12 caracteres.';
        }

        if (preg_match('/\s/', $password)) {
            return 'La contraseña no puede contener espacios.';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            return 'La contraseña debe incluir al menos una letra mayúscula.';
        }

        if (!preg_match('/[a-z]/', $password)) {
            return 'La contraseña debe incluir al menos una letra minúscula.';
        }

        if (!preg_match('/\d/', $password)) {
            return 'La contraseña debe incluir al menos un número.';
        }

        if (!preg_match('/[^A-Za-z\d]/', $password)) {
            return 'La contraseña debe incluir al menos un signo especial.';
        }

        return null;
    }

    public static function generateTempPassword()
    {
        $upper   = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lower   = 'abcdefghjkmnpqrstuvwxyz';
        $digits  = '23456789';
        $special = '@#$%&!*';

        $chars = [
            $upper[random_int(0, strlen($upper) - 1)],
            $upper[random_int(0, strlen($upper) - 1)],
            $lower[random_int(0, strlen($lower) - 1)],
            $lower[random_int(0, strlen($lower) - 1)],
            $digits[random_int(0, strlen($digits) - 1)],
            $digits[random_int(0, strlen($digits) - 1)],
            $special[random_int(0, strlen($special) - 1)],
            $special[random_int(0, strlen($special) - 1)],
        ];

        shuffle($chars);

        return implode('', $chars);
    }
}