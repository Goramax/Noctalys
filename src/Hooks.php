<?php

namespace Goramax\NoctalysFramework;

class Hooks
{
    private static array $hooks = [];

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
}
