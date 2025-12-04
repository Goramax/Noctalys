<?php

/**
 * Automatically converts a string into native PHP types
 * @param string $value
 * @return mixed
 */
function cast_value($value): mixed
{
    $value = strtolower($value);

    return match (true) {
        $value === 'true' => true,
        $value === 'false' => false,
        $value === 'null' => null,
        is_numeric($value) && str_contains($value, '.') => (float)$value,
        is_numeric($value) => (int)$value,
        default => $value,
    };
}

/**
 * Escapes a string for HTML output
 * This function is used to prevent XSS attacks by escaping special characters in the string.
 * It uses the htmlspecialchars function with the ENT_QUOTES and ENT_HTML5 flags to ensure
 * that both single and double quotes are escaped, and that the output is compatible with HTML5.
 * @param string $string
 * @return string
 */
function esc(string $string): string
{
    return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}