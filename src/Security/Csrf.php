<?php

namespace Noctalys\Framework\Security;

class Csrf
{
    /**
     * Sets a CSRF token in the session
     * Generates a new token if one does not exist
     * @return string The CSRF token
     */
    public static function token(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!isset($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }

    /**
     * Validates a CSRF token from a form submission
     * Checks if the token matches the one stored in the session
     * @param string|null $token The CSRF token to validate
     * @return bool True if the token is valid, false otherwise
     */
    public static function check(string|null $token): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        return isset($_SESSION['_csrf_token']) &&
            $token !== null &&
            hash_equals($_SESSION['_csrf_token'], $token);
    }

    /**
     * Generates a CSRF token input field
     * @return string The CSRF token input field
     */
    public static function input(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::token(), ENT_QUOTES) . '">';
    }

}
