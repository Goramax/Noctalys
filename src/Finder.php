<?php

namespace Goramax\NoctalysFramework;

use Goramax\NoctalysFramework\Config;
use RecursiveArrayIterator;

class Finder
{
    /**
     * Find a file in the directories
     * 
     * @param string $fileName file name
     * @param array $directories config directories
     * @param bool $nested if true, will search in nested directories
     * @param array $limitDirectories directories to limit nested search
     * @return string path to the file
     */
    public static function findFile(string $fileName, array $directories, bool $nested = false, array $limitDirectories = []): string | null
    {
        foreach ($directories['sources'] as $directory) {
            $base = $directory['path'] . DIRECTORY_SEPARATOR . $directory['folder_name'];
            $file = $base . DIRECTORY_SEPARATOR . $fileName;
            if (!$nested) {
                if (file_exists($file)) {
                    return $file;
                }
            }
            if ($nested) {
                if (is_dir($base)) {
                    $dir = new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS);
                    $iterator = new \RecursiveIteratorIterator($dir, \RecursiveIteratorIterator::SELF_FIRST);
                    foreach ($iterator as $file) {
                        if ($limitDirectories) {
                            if ($file->isDir() && in_array($file->getFilename(), $limitDirectories)) {
                                $nestedIterator = new \RecursiveDirectoryIterator($file);
                                foreach ($nestedIterator as $nestedFile) {
                                    if ($nestedFile->isFile() && $nestedFile->getFilename() === $fileName) {
                                        return $nestedFile->getPathname();
                                    }
                                }
                            }
                        } else {
                            if ($file->isFile() && $file->getFilename() === $fileName) {
                                return $file->getPathname();
                            }
                        }
                    }
                }
            }
        }
        throw new \Exception('File not found: ' . $fileName);
    }

    /**
     * Find a layout file by name
     * @param string $layoutName layout name
     * @param string $extension file extension
     * @return string path to the layout file
     */
    public static function findLayout($fileName, $extension = 'php'): string
    {
        try {
            $directories = Config::get("layouts");
            return self::findFile($fileName . ".layout." . $extension, $directories);
        } catch (\Exception $e) {
            throw new \Exception("Layout file not found: $fileName");
        }
    }

    /**
     * Find a component file by name
     * @param string $componentName component name
     * @param string $extension file extension
     * @return string path to the component file
     */
    public static function findComponent($fileName, $extension = 'php'): string
    {
        try {
            $directories = Config::get("components");
            $file = self::findFile($fileName . ".component." . $extension, $directories);
            return $file;
        } catch (\Exception $e) {

            trigger_error("Component file not found: $fileName", E_USER_WARNING);
            return "";
        }
    }
}
