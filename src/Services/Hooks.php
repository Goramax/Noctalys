<?php

namespace Noctalys\Framework\Services;

class Hooks
{
    private static array $hooks = [];
    private static bool $initialized = false;

    /**
     * Add a hook
     * @param string $hookName
     * @param callable $callback 
     * @return void
     */
    public static function add(string $hookName, callable $callback): void
    {
        self::$hooks[$hookName][] = $callback;
    }

    /**
     * Execute a hook
     * @param string $hookName
     * @param array $params
     * @return void
     */
    public static function run(string $hookName, ...$params): void
    {
        if (!isset(self::$hooks[$hookName])) return;

        foreach (self::$hooks[$hookName] as $callback) {
            call_user_func_array($callback, $params);
        }
    }
    
    public static function setup(): void
    {
        if (self::$initialized) return;
        $hooksFile = DIRECTORY . '/src/hooks.php';
        if (file_exists($hooksFile)) {
            include_once $hooksFile;
            self::$initialized = true;
        }
    }
}
