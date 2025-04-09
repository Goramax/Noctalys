<?php

use Goramax\NoctalysFramework\Component;
use Goramax\NoctalysFramework\Finder;
use Goramax\NoctalysFramework\Config;
use Goramax\NoctalysFramework\ErrorHandler;

/**
 * Render a view
 * 
 * @param string $view view name of path (without extension)
 * @param array $data data to pass to the component as variables
 * @return void
 */
function render_component(string $component, array $data = []): void
{
    Component::load($component, $data);
}

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

/**
 * Returns the path to the image
 * @param string $name image name with extension (example.jpg)
 * if there are subfolders, use the following format: folder1/folder2/example.jpg
 */
function img(string $name): string
{
    try {
        $imgsrc = Finder::findFile($name, Config::get('assets'), nested: true, limitDirectories: ['images', 'imgs']);
        if ($imgsrc === null) {
            return "";
        }
        return $imgsrc;
    } catch (Exception $e) {
        ErrorHandler::warning("Image file not found: " . $name, depth: 3);
        return "";
    }
}

/**
 * include the SVG file as html for js/css manipulation
 * @param string $name SVG name without extension
 * @param array $attributes ['width' => '100', 'height' => '100', class => 'my-svg', ...]
 * if there are subfolders, use the following format: folder1/folder2/mysvg
 * @return void
 */
function svg(string $name, array $attributes = []): void
{
    try {
        if (!str_ends_with($name, '.svg')) {
            $name .= '.svg';
        }
        $svgsrc = Finder::findFile($name, Config::get('assets'), nested: true, limitDirectories: ['svgs', 'svg', 'img', 'imgs']);
        if ($svgsrc === null) {
            ErrorHandler::warning("SVG file not found: " . $name, depth: 3);
            return;
        }
        $svg = file_get_contents($svgsrc);
        $svg = preg_replace('/<script.*?<\/script>/is', '', $svg);
        if ($svg === false) {
            ErrorHandler::warning("Failed to read SVG file: " . $name, depth: 3);
            return;
        }
        if (preg_match('/<svg\s([^>]+)>/i', $svg, $matches)) {
            $originalAttrs = $matches[1];
            foreach ($attributes as $key => $value) {
                $originalAttrs = preg_replace('/\b' . preg_quote($key, '/') . '="[^"]*"/i', '', $originalAttrs);
                $originalAttrs .= ' ' . $key . '="' . htmlspecialchars((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"';
            }
            $originalAttrs = trim(preg_replace('/\s+/', ' ', $originalAttrs));
            $svg = preg_replace('/<svg\s[^>]+>/i', '<svg ' . $originalAttrs . '>', $svg, 1);
        }
        echo $svg;
        return;
    } catch (Exception $e) {
        ErrorHandler::warning("SVG file not found: " . $name, depth: 3);
        return;
    }
}
