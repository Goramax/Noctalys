<?php

namespace Goramax\NoctalysFramework;
use Goramax\NoctalysFramework\Config;

class Router
{

    private static $page_dirs;

    /**
     * Initialize static properties
     */
    public static function init(): void
    {
        // change to an array of directories
        $directories = Config::get_router_config()["page_scan"];
        foreach ($directories as $directory) {
            //TODO scan every directory with folder_name in path (recursive)
            self::$page_dirs[] = getcwd() . "/" . $directory["path"] ."/" . $directory["folder_name"];
        }
    }

    /**
     * Automatically discover routes
     */
    public static function dumpRoutes(): array
    {
        $routes = self::autoDiscoverRoutes();
        return $routes;
    }

    /**
     * Automatically discover routes
     * @return array $routes
     */
    private static function autoDiscoverRoutes()
    {
        return "NOT IMPLEMENTED";
        // Implementation would need to iterate through all directories in self::$page_dirs
    }

    /**
     * Get the current route from the URL
     * 
     * @return string
     */
    private static function getCurrentRoute(): string
    {
        $uri = $_SERVER['REQUEST_URI'];
        // Remove query parameters
        $uri = strtok($uri, '?');
        if ($uri != '/') {
            return $uri;
        } else {
            $uri = '/home';
        }
        return $uri;
    }

    /**
     * Find the controller file in a directory
     * 
     * @param string $folder_path
     * @return string|null
     */
    private static function findControllerFile(string $folder_path): ?string
    {
        $folder_name = basename($folder_path);
        $controllerFile = $folder_path . '/' . $folder_name . '.controller.php';
        if (file_exists($controllerFile)) {
            return $controllerFile;
        }
        return null;
    }

    /**
     * Find the controller class in a file
     * 
     * @param string $file
     * @return string|null
     */
    private static function findControllerClass(string $file): ?string
    {
        try {
            $contents = file_get_contents($file);
            if (preg_match('/class\s+(\w+)/', $contents, $matches)) {
                return $matches[1];
            }
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Dispatch the request to the right controller
     */
    public static function dispatch(): void
    {
        $current_route = self::getCurrentRoute();
        $controllerFile = null;

        // Try to find the controller file in any of the page directories
        foreach (self::$page_dirs as $page_dir) {
            $potential_controller = self::findControllerFile($page_dir . $current_route);
            if ($potential_controller !== null) {
                $controllerFile = $potential_controller;
                break;
            }
        }

        // If we still don't have a controller file, we can't proceed
        if ($controllerFile === null) {
            echo "TODO: 404 Not Found"; //TODO: Implement error handling
            exit;
        }

        require_once $controllerFile;
        $controllerClass = self::findControllerClass($controllerFile);

        if ($controllerClass) {
            $controller = new $controllerClass();
            $controller->main();
        }
    }

    /**
     * Redirect to a specific URL
     * 
     * @param string $url
     * @param bool $replace
     * @param int $response_code
     * @return never
     */
    public static function redirect(string $url, int $status = 0, int $response_code = 301): void
    {
        header(header: "Location: $url", replace: $status, response_code: $response_code);
        exit;
    }
}

// Initialize static properties
Router::init();
