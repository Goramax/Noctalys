<?php

namespace Goramax\NoctalysFramework;

class ErrorHandler
{
    /**
     * Trigger an error with context (optional)
     * @param string $message The error message
     * @param string $errorType The type of error (warn, notice, error, deprecated, core, compile, user, all)
     * @param int $depth The depth of the stack trace to use for context
     * @return void
     */
    public static function warning(string $message, string $errorType = "warn", int $depth = 1): void
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $depth + 1);
        $caller = $trace[$depth] ?? null;

        $file = $caller['file'] ?? 'unknown file';
        $line = $caller['line'] ?? 'unknown line';

        $error = self::convertToErrorType($errorType);

        trigger_error("$message (called at $file:$line)", $error);
    }

    /**
     * Trigger a fatal error and terminate the script
     * @param string $message The error message
     * @param int $depth The depth of the stack trace to use for context
     * @return never
     * @throws \ErrorException
     */
    public static function fatal(string $message, string $errorType = "error", int $depth = 1): never
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $depth + 1);
        $caller = $trace[$depth] ?? null;

        $file = $caller['file'] ?? 'unknown file';
        $line = $caller['line'] ?? 'unknown line';

        $error = self::convertToErrorType($errorType);

        throw new \ErrorException("$message (called at $file:$line)", 0, $error, $file, $line);
    }


    /**
     * Convert a string error type to a PHP error constant
     * @param string $errorType The error type as a string
     * @return int The corresponding PHP error constant
     */
    private static function convertToErrorType(string $errorType): int
    {
        return match($errorType) {
            "warn" => E_USER_WARNING,
            "notice" => E_USER_NOTICE,
            "error" => E_USER_ERROR,
            "deprecated" => E_DEPRECATED,
            "core" => E_CORE_WARNING,
            "compile" => E_COMPILE_WARNING,
            "user" => E_USER_DEPRECATED,
            "all" => E_ALL,
            default => E_USER_WARNING,
        };
    }
}
