<?php

namespace Goramax\NoctalysFramework\Core;

use Exception;
use Goramax\NoctalysFramework\Services\Hooks;

class ErrorHandler
{

    private static $style = '<style>
        .noctalys-fatal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100% !important;
            height: 100% !important;
            z-index: 9999;
            font-family: "Roboto", sans-serif;
            background-color: rgba(0, 0, 0, 0.8);
        }
        .noctalys-fatal div {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #fff;
            text-align: center;
            max-width: 1200px;
            width: 100%;
            max-height: 80vh;
            background-color: #151b1f;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow-y: auto;
        }
        .noctalys-fatal span {
            padding: 20px;
            display: block;
            color: #fff;
            font-size: 24px;
            font-weight: bold;
            text-transform: uppercase;
            border-radius: 10px 10px 0 0;
            background-color: #186ae5;
            margin-bottom: 2rem;
        }
        .noctalys-fatal p.noctalys-message {
            font-size: 18px;
            margin: 20px 0;
            color: #f8f8f8;
            font-weight: 800;
        }
        .noctalys-fatal p {
            color: #f8f8f8;
            font-size: 16px;
            margin: 10px 0;
        }
        .noctalys-fatal pre {
            padding: 10px;
            margin: 20px;
            border-radius: 5px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow-x: auto;
            text-align: left;
            background-color: #20262b;
            color: #f8f8f8;
            font-family:Consolas,Monaco,Lucida Console,Liberation Mono,DejaVu Sans Mono,Bitstream Vera Sans Mono,Courier New;
            font-size: 12px;
            max-height: 20vh;
            overflow-y: auto;
            position: relative;
        }
        .noctalys-fatal *::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        .noctalys-fatal *::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 4px;
        }
        .noctalys-fatal *::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.4);
        }
        .noctalys-fatal *::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 4px;
        }
        .noctalys-fatal *::-webkit-scrollbar-track:hover {
            background: rgba(0, 0, 0, 0.4);
        }
        </style>';
    /**
     * Trigger an error with context (optional)
     * @param string $message The error message
     * @param string $errorType The type of error (warn, notice, error, deprecated, core, compile, user, all)
     * @param int $depth The depth of the stack trace to use for context
     * @return void
     */
    private static function warning(string $message, string $errorType = "warn", int $depth = 1, \Exception $exception = null): void
    {
        // Determine origin: seek first user file (.view., .layout. or .component.) in trace
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $file = 'unknown file';
        $line = 'unknown line';
        // If exception passed, start from its frame
        if ($exception !== null) {
            $trace = array_merge([['file' => $exception->getFile(), 'line' => $exception->getLine()]], $trace);
        }
        foreach ($trace as $frame) {
            if (isset($frame['file'])) {
                $f = $frame['file'];
                // match views, layouts, components
                if (preg_match('/\.view\.|\.layout\.|\.component\./', basename($f))) {
                    $file = $f;
                    $line = $frame['line'] ?? 'unknown line';
                    break;
                }
                // fallback: first non-framework file under src/Frontend
                if (strpos($f, DIRECTORY . '/Frontend') !== false) {
                    $file = $f;
                    $line = $frame['line'] ?? 'unknown line';
                    break;
                }
            }
        }
        // final fallback to exception or first trace
        if ($file === 'unknown file' && $exception !== null) {
            $file = $exception->getFile();
            $line = $exception->getLine();
        } elseif ($file === 'unknown file' && isset($trace[$depth]['file'])) {
            $file = $trace[$depth]['file'];
            $line = $trace[$depth]['line'] ?? $line;
        }

        $error = self::convertToErrorType($errorType);

        error_log("Warning : $message (called at $file:$line)", 0);
        Hooks::run("on_warning", $message, $file, $line, $error);
        $debugMode = Config::get('app')['debug'] ?? false;

        if ($debugMode) {
            echo '<div class="noctalys-warn" style="background-color: #ffc50c; color: #333; padding: 10px; border-radius: 5px; margin: 10px 0;">';
            echo '<strong>⚠️ Warning</strong>: <u>' . htmlspecialchars($message) . '</u><br>';
            echo '<span>📄 <i>File: ' . htmlspecialchars($file) . ' | Line: ' . htmlspecialchars($line) . '</i></span>';
            echo '</div>';
        }
    }

    /**
     * Trigger a fatal error and terminate the script
     * @param string $message The error message
     * @param string $errorType The type of error (warn, notice, error, deprecated, core, compile, user, all)
     * @param int $depth The depth of the stack trace to use for context
     * @return never
     * @throws \ErrorException
     */
    private static function fatal(string $message, string $errorType = "error", int $depth = 0, \Throwable $errorObject = null): never
    {

        // Use debug_backtrace to capture all frames including template/view includes
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        // Optionally remove the first fatal/handleException frames
        // Remove frames up to this method call
        while (isset($trace[0]['function']) && in_array($trace[0]['function'], ['handleException', 'fatal'], true)) {
            array_shift($trace);
        }


        // Determine initial origin from exception
        $file = $errorObject?->getFile() ?? 'unknown file';
        $line = $errorObject?->getLine() ?? 'unknown line';

        // If exception occurred in a template/component file, keep it
        $baseFile = basename($file);
        if (preg_match('/\.component\.|\.view\.|\.layout\./', $baseFile)) {
            // Use this as origin, skip further search
        } else {
            // Search trace for priority matches
            // Reverse trace to search deeper frames first
            $frames = array_reverse($trace);
            $found = false;
            // Look for component, view, layout, then controller
            foreach ($frames as $frame) {
                if (empty($frame['file'])) continue;
                $f = $frame['file'];
                $base = basename($f);
                if (preg_match('/\.component\./', $base)) {
                    $file = $f;
                    $line = $frame['line'] ?? $line;
                    $found = true;
                    break;
                }
                if (preg_match('/\.view\./', $base)) {
                    $file = $f;
                    $line = $frame['line'] ?? $line;
                    $found = true;
                    break;
                }
                if (preg_match('/\.layout\./', $base)) {
                    $file = $f;
                    $line = $frame['line'] ?? $line;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                // Try to find any application view under src/Frontend
                foreach ($frames as $frame) {
                    if (!empty($frame['file']) && strpos($frame['file'], DIRECTORY . '/Frontend') !== false) {
                        $file = $frame['file'];
                        $line = $frame['line'] ?? $line;
                        $found = true;
                        break;
                    }
                }
            }
            if (!$found) {
                foreach ($frames as $frame) {
                    if (!empty($frame['file']) && preg_match('/controller\.php$/', basename($frame['file']))) {
                        $file = $frame['file'];
                        $line = $frame['line'] ?? $line;
                        break;
                    }
                }
            }
        }


        $error = self::convertToErrorType($errorType);

        if ($errorObject instanceof \Throwable) {
            $stackString = $errorObject->getTraceAsString();
        } else {
            // Fallback to printing the backtrace array
            $stackString = print_r($trace, true);
        }
        error_log("Fatal : $message (called at $file:$line)", 0);

        Hooks::run("on_error", $message, $file, $line, $error);

        try {
            $debugMode = Config::get('app')['debug'] ?? false;
        } catch (\Exception $e) {
            $debugMode = false;
        }

        if ($debugMode) {
            echo '<div class="noctalys-fatal">' .
                    '<div>' .
                        '<span>⚠️ Fatal Error ⚠️</span>' .
                        '<p class="noctalys-message">🚨 ' . htmlspecialchars($message) . '</p>' .
                        '<p>📄 File: ' . htmlspecialchars($file) . '</p>' .
                        '<p>🔎 Line: ' . htmlspecialchars($line) . '</p>' .
                        '<pre>' . $stackString . '</pre>' .
                    '</div>' .
                '</div>';
            echo self::$style;

            exit(1);
        }

        throw new \ErrorException($message, 0, $error, $file, $line, $errorObject);
    }


    /**
     * Convert a string error type to a PHP error constant
     * @param string $errorType The error type as a string
     * @return int The corresponding PHP error constant
     */
    private static function convertToErrorType(string $errorType): int
    {
        return match ($errorType) {
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

    /**
     * Global handler for errors and exceptions
     * @param mixed $severityOrException Either an int severity for errors or a Throwable
     * @param string $message Error message when called as error handler
     * @param string $file File path when called as error handler
     * @param int $line Line number when called as error handler
     * @return bool
     */
    public static function handleException($severityOrException, string $message = '', string $file = '', int $line = 0): bool
    {
        // Distinguish error vs exception
        if (is_int($severityOrException)) {
            // Called as error handler: create ErrorException
            $severity = $severityOrException;
            $exception = new \ErrorException($message, 0, $severity, $file, $line);
        } elseif ($severityOrException instanceof \Throwable) {
            $exception = $severityOrException;
        } else {
            // Unknown invocation, bail out
            return false;
        }

        // Now dispatch based on exception type / severity
        if ($exception instanceof \ErrorException) {
            $severity = $exception->getSeverity();
            $msg = $exception->getMessage();
            $file = $exception->getFile();
            $line = $exception->getLine();
            // Warning-like severities
            $warningSeverities = [E_USER_WARNING, E_WARNING, E_USER_NOTICE, E_NOTICE, E_DEPRECATED, E_USER_DEPRECATED];
            if (in_array($severity, $warningSeverities, true)) {
                // For native warnings, clear exception so warning() uses trace to find template/view origin
                self::warning($msg, 'warn', 2, null);
            } else {
                self::fatal($msg, 'error', 0, $exception);
            }
        } else {
            // Throwable that is not ErrorException: treat as fatal
            $msg = $exception->getMessage();
            self::fatal($msg, 'error', 0, $exception);
        }
        return true;
    }
}