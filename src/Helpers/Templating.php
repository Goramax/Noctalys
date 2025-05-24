<?php

use Goramax\NoctalysFramework\Component;
use Goramax\NoctalysFramework\Finder;
use Goramax\NoctalysFramework\File;
use Goramax\NoctalysFramework\Asset;

const PUBLIC_ASSETS_FOLDER = [
    'sources' => [
        [
            'folder_name' => 'assets',
            'path' => 'public'
        ]
    ]
];

/**
 * Render a view
 * 
 * @param string $view view name of path (without extension)
 * @param array $data data to pass to the component as variables
 * @return void
 */
function render_component(string $component, array $data = [], string $extension = 'php'): void
{
    if (empty($component)) {
        trigger_error("Component name is empty", E_USER_WARNING);
        return;
    }
    if (empty($data)) {
        $data = [];
    }
    if (!is_array($data)) {
        trigger_error("Data must be an array", E_USER_WARNING);
        return;
    }
    Component::load($component, $data, $extension);
}

/**
 * Returns the path to the image
 * @param string $name image name with extension (example.jpg)
 * @param array $limitDirectories directories to limit the search to
 * if there are subfolders, use the following format: folder1/folder2/example.jpg
 */
function img(string $name, array $limitDirectories = ['images', 'imgs']): string
{
    try {
        $imgsrc = Finder::findFile($name, PUBLIC_ASSETS_FOLDER, nested: true, limitDirectories: $limitDirectories);
        if ($imgsrc === null) {
            return "";
        }
    } catch (Exception $e) {
        trigger_error("Image file not found: " . $name, E_USER_WARNING);
        return "";
    }
    return $imgsrc;
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
        $svgsrc = Finder::findFile($name, PUBLIC_ASSETS_FOLDER, nested: true, limitDirectories: ['svgs', 'svg', 'img', 'imgs']);
        if ($svgsrc === null) {
            trigger_error("SVG file not found: " . $name, E_USER_WARNING);
            return;
        }
        $svg = file_get_contents($svgsrc);
        $svg = preg_replace('/<script.*?<\/script>/is', '', $svg);
        if ($svg === false) {
            trigger_error("Failed to read SVG file: " . $name, E_USER_WARNING);
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
        trigger_error("SVG file not found: " . $name, E_USER_WARNING);
        return;
    }
}

/**
 * Returns the content of a file
 * @param string $path path to the file
 * @return string|null
 */
function file_content(string $path): string | null
{
    return File::read($path);
}

/**
 * Returns the path of a generated css asset
 * @param string $name asset name (without extension)
 */
function css_path(string $name): string
{
    if (empty($name)) {
        trigger_error("Asset name is empty", E_USER_WARNING);
        return "";
    }
    try {
        return Asset::getPath('css', $name);
    } catch (Exception $e) {
        trigger_error($e->getMessage(), E_USER_WARNING);
        return "";
    }
}

/**
 * Returns the path of a generated js asset
 * @param string $name asset name (without extension)
 */
function js_path(string $name): string
{
    if (empty($name)) {
        trigger_error("Asset name is empty", E_USER_WARNING);
        return "";
    }
    try {
        return Asset::getPath('js', $name);
    } catch (Exception $e) {
        trigger_error($e->getMessage(), E_USER_WARNING);
        return "";
    }
}
