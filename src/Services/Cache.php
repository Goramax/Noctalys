<?php

namespace Goramax\NoctalysFramework\Services;
use Goramax\NoctalysFramework\Core\Config;

class Cache
{
    /**
     * Stores the availability state of APCu
     * @var bool|null
     */
    private static ?bool $isAvailable = null;

    /**
     * Stores whether cache is enabled in config
     * @var bool|null
     */
    private static ?bool $isEnabled = null;

    /**
     * Stores the cache folder path
     * @var string|null
     */
    private static ?string $cacheFolder = null;

    /**
     * Checks if cache can be used and stores the result
     * Called only once per process
     * 
     * @return bool True if cache can be used
     */
    private static function canUse(): bool
    {
        // Check only once per process
        if (self::$isAvailable === null) {
            // Check if APCu is available
            self::$isAvailable = function_exists('apcu_enabled') && apcu_enabled();

            // Check if enabled in config and get cache folder
            $cacheConfig = Config::get('cache');
            self::$isEnabled = !empty($cacheConfig) && isset($cacheConfig['enabled']) && $cacheConfig['enabled'] === true;


            self::$cacheFolder = rtrim($cacheConfig['cache_folder'], '/');

            // Make sure the cache folder exists
            if (!file_exists(self::$cacheFolder)) {
                if (!mkdir(self::$cacheFolder, 0755, true)) {
                    trigger_error('Failed to create cache folder: ' . self::$cacheFolder, E_USER_WARNING);
                    return false;
                }
            }

            // If enabled in config but not available, trigger warning
            if (self::$isEnabled && !self::$isAvailable) {
                trigger_error('APCu cache is enabled in configuration but not available on the server', E_USER_WARNING);
            }
        }

        return self::$isEnabled;
    }

    /**
     * Store a value in a specific cache array
     * 
     * @param string $cacheName The name of the cache array (e.g., 'router', 'finder')
     * @param string $key Cache key within the array
     * @param mixed $value Value to store
     * @param int $ttl Time to live in seconds (0 = forever)
     * @return bool True on success, false on failure
     */
    public static function set(string $cacheName, string $key, $value, int $ttl = 0): bool
    {
        if (!self::canUse()) {
            return false;
        }

        // Get current cache array or initialize an empty one
        $cache = self::getCacheArray($cacheName);

        // Update the value in the array
        $cache[$key] = $value;

        // Store the updated array back in APCu if available
        $success = true;
        if (self::$isAvailable) {
            $success = apcu_store($cacheName, $cache, $ttl);
        }

        // Also persist to file
        self::persistCacheToFile($cacheName, $cache);

        return $success;
    }

    /**
     * Get a value from a specific cache array
     * 
     * @param string $cacheName The name of the cache array (e.g., 'router', 'finder')
     * @param string|null $key Optional key to retrieve specific value
     * @param mixed $default Default value to return if key doesn't exist
     * @return mixed The cached value, entire cache array, or default if not found
     */
    public static function get(string $cacheName, ?string $key = null, $default = null)
    {
        if (!self::canUse()) {
            return $default;
        }

        $cache = self::getCacheArray($cacheName);

        // If no key provided, return the entire cache array
        if ($key === null) {
            return $cache;
        }

        // Return specific value if it exists, otherwise return the default
        return isset($cache[$key]) ? $cache[$key] : $default;
    }

    /**
     * Get the entire cache array for a given cache name
     * 
     * @param string $cacheName The name of the cache array
     * @return array The cache array or empty array if not found
     */
    private static function getCacheArray(string $cacheName): array
    {
        // Try to get from APCu if available
        if (self::$isAvailable) {
            $success = false;
            $cache = apcu_fetch($cacheName, $success);

            if ($success && is_array($cache)) {
                return $cache;
            }
        }

        // If not in APCu or APCu not available, try to load from file
        return self::loadCacheFromFile($cacheName);
    }

    /**
     * Persist a cache array to a file
     * 
     * @param string $cacheName The name of the cache array
     * @param array $cache The cache array to persist
     * @return bool True on success, false on failure
     */
    private static function persistCacheToFile(string $cacheName, array $cache): bool
    {
        if (empty(self::$cacheFolder)) {
            return false;
        }

        $cacheFile = self::getCacheFilePath($cacheName);

        // Serialize the cache array - using igbinary if available for better performance
        if (function_exists('igbinary_serialize')) {
            $serialized = igbinary_serialize($cache);
        } else {
            $serialized = serialize($cache);
        }

        // Write to file atomically (write to temp file then rename)
        $tempFile = $cacheFile . '.tmp.' . uniqid();
        if (file_put_contents($tempFile, $serialized, LOCK_EX) === false) {
            return false;
        }

        return rename($tempFile, $cacheFile);
    }

    /**
     * Load a cache array from a file
     * 
     * @param string $cacheName The name of the cache array
     * @return array The cache array or empty array if file not found
     */
    private static function loadCacheFromFile(string $cacheName): array
    {
        $cacheFile = self::getCacheFilePath($cacheName);

        if (!file_exists($cacheFile)) {
            return [];
        }

        $serialized = file_get_contents($cacheFile);
        if ($serialized === false) {
            return [];
        }

        // Unserialize the cache array - using igbinary if available
        if (function_exists('igbinary_unserialize')) {
            $cache = igbinary_unserialize($serialized);
        } else {
            $cache = unserialize($serialized);
        }

        // Store in APCu for faster future access if APCu is available
        if (self::$isAvailable && is_array($cache)) {
            apcu_store($cacheName, $cache);
        }

        return is_array($cache) ? $cache : [];
    }

    /**
     * Get the file path for a cache array
     * 
     * @param string $cacheName The name of the cache array
     * @return string The file path
     */
    private static function getCacheFilePath(string $cacheName): string
    {
        return self::$cacheFolder . '/' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $cacheName) . '.cache';
    }

    /**
     * Clear a specific cache array or all cache entries
     * 
     * @param string|null $cacheName Optional name of cache array to clear
     * @return bool True on success, false on failure
     */
    public static function clear(?string $cacheName = null): bool
    {
        if (!self::canUse()) {
            return false;
        }

        $success = true;

        // Clear from APCu if available
        if (self::$isAvailable) {
            if ($cacheName !== null) {
                // Clear specific cache array
                $success = apcu_delete($cacheName);
            } else {
                // Clear all cache entries
                $success = apcu_clear_cache();
            }
        }

        // Also clear from files
        if ($cacheName !== null) {
            // Clear specific cache file
            $cacheFile = self::getCacheFilePath($cacheName);
            if (file_exists($cacheFile)) {
                $success = $success && unlink($cacheFile);
            }
        } else {
            // Clear all cache files
            if (is_dir(self::$cacheFolder)) {
                $files = glob(self::$cacheFolder . '/*.cache');
                foreach ($files as $file) {
                    $success = $success && unlink($file);
                }
            }
        }

        return $success;
    }
}
