<?php

declare(strict_types=1);

namespace App\Security;

class CsrfProtection
{
    private const TOKEN_NAME = 'csrf_token';

    /**
     * Generate a CSRF token and store it in the session.
     */
    public static function generateToken(): string
    {
        if (empty($_SESSION[self::TOKEN_NAME])) {
            $_SESSION[self::TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::TOKEN_NAME];
    }

    /**
     * Return the current CSRF token value.
     */
    public static function token(): string
    {
        return $_SESSION[self::TOKEN_NAME] ?? self::generateToken();
    }

    /**
     * Get the CSRF token as a hidden input field.
     */
    public static function getTokenField(): string
    {
        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            self::TOKEN_NAME,
            self::token()
        );
    }

    /**
     * Validate a CSRF token against the session.
     */
    public static function validateToken(?string $token): bool
    {
        if (empty($token) || empty($_SESSION[self::TOKEN_NAME])) {
            return false;
        }

        return hash_equals($_SESSION[self::TOKEN_NAME], $token);
    }

    /**
     * Check if the HTTP method is read-only (no CSRF needed).
     */
    public static function isReadMethod(string $method): bool
    {
        return in_array(strtoupper($method), ['GET', 'HEAD', 'OPTIONS'], true);
    }
}
