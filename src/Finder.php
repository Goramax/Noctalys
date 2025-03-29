<?php

namespace Goramax\NoctalysFramework;

use Goramax\NoctalysFramework\Config;

class Finder
{
    /**
     * Find a file in the directories
     * 
     * @param string $fileName file name
     * @param array $directories config directories
     * @return string path to the file
     */
    private static function findFile($fileName, $directories): string | null
    {
        foreach ($directories['sources'] as $directory) {
            $file = $directory['path'] . '/' . $directory['folder_name'] . '/' . $fileName;
            if (file_exists($file)) {
                return $file;
            }
        }
        throw new \Exception("File not found: $fileName");
    }

    /**
     * Find a layout file by name
     * @param string $layoutName layout name
     * @return string path to the layout file
     */
    public static function findLayout($fileName): string
    {
        try {
            $directories = Config::get_layout_config();
            return self::findFile($fileName . ".layout.php", $directories);
        } catch (\Exception $e) {
            throw new \Exception("Layout file not found: $fileName");
        }
    }

    /**
     * Find a component file by name
     * @param string $componentName component name
     * @return string path to the component file
     */
    public static function findComponent($fileName): string
    {
        try {
            $directories = Config::get_component_config();
            $file = self::findFile($fileName . ".component.php", $directories);
            return $file;
        } catch (\Exception $e) {
            trigger_error("Component file not found: $fileName", E_USER_WARNING);
            return "";
        }
    }
}
