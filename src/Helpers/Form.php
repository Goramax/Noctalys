<?php

use Goramax\NoctalysFramework\Form;

/**
 * Returns the CSRF token
 * @return string
 */
function csrf_token(): string
{
    return Form::csrf_token();
}

/**
 * Generates a CSRF token input field
 * 
 * Equivalent to:
 * \<input type="hidden" name="csrf_token" value="\<?= csrf_token() ?\>"\>
 * @return string
 */
function csrf_input(): string
{
    return Form::csrf_input();
}

/**
 * Checks if the CSRF token is valid
 * Used in pair with csrf_input() and will check for "csrf_token" in the $_POST array
 * @return bool
 */
function csrf_check(): bool
{
    if (isset($_POST['csrf_token'])) {
        return Form::csrf_check($_POST['csrf_token'] ?? null);
    }
    return false;
}

/**
 * Gets the old value of a form field
 * @param string $name The name of the form field
 * @param mixed $default The default value if the field is not set
 * @return mixed The old value of the form field or the default value
 */
function value(string $name, mixed $default = null): mixed
{
    return Form::value($name, $default);
}
