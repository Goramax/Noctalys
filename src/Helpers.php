<?php
use Goramax\NoctalysFramework\Component;

/**
 * Render a view
 * 
 * @param string $view view name of path (without extension)
 * @param array $data data to pass to the component as variables
 * @return void
 */
function render_component(string $component, array $data = []): void {
    Component::load($component, $data);
}

/**
 * Automatically converts a string into native PHP types
 * @param string $value
 * @return mixed
 */
function cast_value($value): mixed {
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