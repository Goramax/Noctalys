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