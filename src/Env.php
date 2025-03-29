<?php

namespace Goramax\NoctalysFramework;

use Goramax\NoctalysFramework\Config;

class Env
{
    private static bool $loaded = false;
    private static array $vars = [];
    /**
     * Load the environment variables from the .env file.
     * Depending on the APP_ENV variable, it will load the corresponding .env file.
     * @return void
     */
    public static function load(): void
    {
        $env = getenv('APP_ENV');
        if ($env === false || $env === 'production' || $env === 'prod') {
            $env = '';
        }
        self::loadEnvFile($env);
        self::$loaded = true;
    }

    private static function loadEnvFile($envType = ''): void
    {
        if (self::$loaded) return;
        $envPath = getcwd() . '/.env.' . $envType ?: '.' . $envType;
        if (file_exists($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
                [$key, $value] = explode('=', $line, 2) + [null, null];
                $key = trim($key);
                if (empty($key)) continue;
                $value = trim($value);
                if (
                    (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                    (str_starts_with($value, "'") && str_ends_with($value, "'"))
                ) {
                    $value = substr($value, 1, -1);
                }
                $value = cast_value($value);
                self::$vars[$key] = $value;
                if (Config::get("env")['extended_compat'] == true) {
                    putenv("$key=$value");
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                }
            }
        }
    }

    /**
     * Get the value of an environment variable.
     * @param string $key The name of the environment variable.
     * @param mixed $default The default value to return if the variable is not set.
     * @return mixed The value of the environment variable, or the default value if not set.
     */
    public static function get(string $key, $default = null): mixed
    {
        if (!isset(self::$vars[$key])){
            trigger_error("Environment variable '$key' is not set.", E_USER_WARNING);
        }
        return self::$vars[$key] ?? $default;
    }
}
