<?php

namespace Goramax\NoctalysFramework\Utils;

use Goramax\NoctalysFramework\Core\Config;
use Goramax\NoctalysFramework\Services\Cache;

class Finder
{
    /**
     * Stores whether finder cache is enabled
     * @var bool|null
     */
    private static ?bool $isCacheEnabled = null;
    
    /**
     * Check if finder cache can be used
     * 
     * @return bool True if finder cache can be used
     */
    private static function canUseCache(): bool
    {
        // Check only once per process
        if (self::$isCacheEnabled === null) {
            $cacheConfig = Config::get('cache');
            self::$isCacheEnabled = $cacheConfig['enabled'] === true && $cacheConfig['finder_cache'] === true;
        }        
        return self::$isCacheEnabled;
    }

    /**
     * Find a file in the directories
     * 
     * @param string $fileName file name
     * @param array $directories config directories
     * @param bool $nested if true, will search in nested directories
     * @param array $limitDirectories directories to limit nested search
     * @return string|null path to the file
     * @throws \Exception if file is not found
     */
    public static function findFile(string $fileName, array $directories, bool $nested = false, array $limitDirectories = []): ?string
    {
        // Generate a unique cache key for this file lookup
        $cacheKey = $fileName . '_' . md5(serialize([
            $nested,
            $limitDirectories,
            array_map(function($source) {
                return $source['path'] . '_' . $source['folder_name'];
            }, $directories['sources'])
        ]));
        
        // Try to get from cache first if caching is enabled
        if (self::canUseCache()) {
            $cachedPath = Cache::get('finder', "file_$cacheKey");
            if ($cachedPath !== null) {
                return $cachedPath;
            }
        }
        
        // Perform the standard file search if not in cache
        foreach ($directories['sources'] as $directory) {
            $base = $directory['path'] . DIRECTORY_SEPARATOR . $directory['folder_name'];
            $file = $base . DIRECTORY_SEPARATOR . $fileName;
            if (!$nested) {
                if (file_exists($file)) {
                    // Cache the result for future lookups if caching is enabled
                    if (self::canUseCache()) {
                        Cache::set('finder', "file_$cacheKey", $file);
                    }
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
                                        $path = $nestedFile->getPathname();
                                        // Cache the result if caching is enabled
                                        if (self::canUseCache()) {
                                            Cache::set('finder', "file_$cacheKey", $path);
                                        }
                                        return $path;
                                    }
                                }
                            }
                        } else {
                            if ($file->isFile() && $file->getFilename() === $fileName) {
                                $path = $file->getPathname();
                                // Cache the result if caching is enabled
                                if (self::canUseCache()) {
                                    Cache::set('finder', "file_$cacheKey", $path);
                                }
                                return $path;
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
        // Try to get from cache first if caching is enabled
        if (self::canUseCache()) {
            $cachedPath = Cache::get('finder', "layout_$fileName.$extension");
            if ($cachedPath !== null) {
                return $cachedPath;
            }
        }
        
        try {
            $directories = Config::get("layouts");
            $filePath = self::findFile($fileName . ".layout." . $extension, $directories);
            
            // Cache the result for future lookups if caching is enabled
            if (self::canUseCache()) {
                Cache::set('finder', "layout_$fileName.$extension", $filePath);
            }
            
            return $filePath;
        } catch (\Exception $e) {
            throw new \ErrorException("Layout file not found: $fileName", 0, E_USER_ERROR);
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
        // Try to get from cache first if caching is enabled
        if (self::canUseCache()) {
            $cachedPath = Cache::get('finder', "component_$fileName.$extension");
            if ($cachedPath !== null) {
                return $cachedPath;
            }
        }
        
        try {
            $directories = Config::get("components");
            $file = self::findFile($fileName . ".component." . $extension, $directories);
            
            // Cache the result for future lookups if caching is enabled
            if (self::canUseCache()) {
                Cache::set('finder', "component_$fileName.$extension", $file);
            }
            
            return $file;
        } catch (\Exception $e) {
            trigger_error("Component file not found: $fileName", E_USER_WARNING);
            return "";
        }
    }
}
