<?php

namespace Goramax\NoctalysFramework;

use Error;
use Goramax\NoctalysFramework\Config;
use Goramax\NoctalysFramework\Hooks;

class Router
{

    private static $pageDirs;
    private static $directories;
    private static $errorPage;
    private static $params;
    private static $currentPath;
    public static $current_route;

    /**
     * Initialize static properties
     */
    public static function init(): void
    {
        // change to an array of directories
        self::$directories = Config::get("router")["page_scan"];
        self::$errorPage = DIRECTORY . "/" . Config::get("router")["error_page"];
        foreach (self::$directories as $directory) {
            self::$pageDirs[] = DIRECTORY . "/" . $directory["path"] . "/" . $directory["folder_name"];
        }
        self::$params = [];
        self::$currentPath = null;
    }

    /**
     * Automatically discover routes
     * @return array $routes
     */
    private static function autoDiscoverRoutes()
    {
        ErrorHandler::warning("Auto-discovery of routes is not implemented yet.");
        // Implementation would need to iterate through all directories in self::$pageDirs
    }

    /**
     * Get the current folder from which the view is called
     * @return string
     */
    public static function getCurrentFolder(): string
    {
        $path = explode('/', self::$currentPath);
        array_pop($path);
        $currentFolder = implode('/', $path);
        return $currentFolder;
    }

    /**
     * Get the current route from the URL
     * 
     * @return string
     */
    private static function getCurrentRoute(): string
    {
        if (self::$current_route) {
            return self::$current_route;
        }
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
            // Detect namespace
            $namespace = null;
            if (preg_match('/^namespace\s+(.+?);/m', $contents, $nsMatch)) {
                $namespace = trim($nsMatch[1]);
            }

            // Detect class name
            if (preg_match('/class\s+(\w+)/', $contents, $classMatch)) {
                $className = $classMatch[1];
                return $namespace ? "$namespace\\$className" : $className;
            }
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private static function arrayToPath(array $array): string
    {
        $path = "";
        foreach ($array as $key => $value) {
            if ($key + 1 != count($array)) {
                $path .= $value . "/";
            } else {
                $path .= $value;
            }
        }
        return $path;
    }

    /**
     * Check if a folder contains a `_param` placeholder folder
     */
    private static function hasParamFolders(string $folder_path): bool
    {
        $folder_name = basename($folder_path);
        $param_folder = $folder_path . '/' . $folder_name . '_param';
        if (is_dir($param_folder)) {
            return true;
        }
        return false;
    }

    /**
     * Loop through the directories and check if a route with parameters exists
     * 
     * @param array $current_route
     * @return string|null
     */
    private static function findParamControllerFile(array $current_route_array): string|null
    {
        $file_path = [];
        $page_dir = null;
        $constructedParamRoute = '';
        foreach (self::$pageDirs as $page_dir) {
            foreach ($current_route_array as $index => $current_route) {
                if ($index + 1 < count($current_route_array)) {
                    //check if directory exists
                    if (!is_dir(filename: $page_dir . '/' . $constructedParamRoute . $current_route) && !is_dir($page_dir . '/' . self::arrayToPath($file_path) . "/" . $file_path[count($file_path) - 1] . '_param')) {
                        return null;
                    }
                    $folders = scandir($page_dir . '/' . $constructedParamRoute . self::arrayToPath($file_path));
                    // check if the folder contains other folders
                    if (!$folders) {
                        break;
                    }
                    // if the next folder from route exists in path, add it to the file_path array
                    if (in_array($current_route_array[$index + 1], $folders)) {
                        $file_path[] = $current_route_array[$index + 1];
                    }
                    // else if has _param folder, add it to the params array and file_path array
                    else if (self::hasParamFolders($page_dir . '/' . $constructedParamRoute . $current_route)) {
                        self::$params[$current_route] = $current_route_array[$index + 1];
                        $file_path[] = $current_route . '_param';
                    } else if (self::hasParamFolders($page_dir . '/' . $constructedParamRoute . self::arrayToPath($file_path))) {
                        self::$params[$current_route] = $current_route_array[$index + 1];
                        $file_path[] = $file_path[$index - 1] . '_param';
                    } else {
                        return null;
                    }
                }
            }
        };
        return $page_dir . '/' . self::arrayToPath($file_path);
    }

    /**
     * Get parameters from the URL
     * @return array
     */
    public static function getParams(): array
    {
        return self::$params;
    }

    /**
     * Call the error controller
     * @param string $code
     * @return void
     */
    public static function error(int $code = 500, $message = ''): void
    {
        $controllerFile = self::findControllerFile(self::$errorPage);
        if (!$controllerFile) {
            ErrorHandler::fatal("Controller file not found at " . self::$errorPage);
        }
        self::$currentPath = $controllerFile;
        require_once $controllerFile;
        $controllerClass = self::findControllerClass($controllerFile);
        if ($controllerClass) {
            http_response_code($code);
            $controller = new $controllerClass();
            $controller->main($code, $message);
            $currentRoute = self::getCurrentRoute();
            Hooks::run("after_error", $code, $message, $currentRoute);
        }
    }


    /**
     * Dispatch the request to the right controller
     */
    public static function dispatch(): void
    {
        $current_route = self::getCurrentRoute();
        $controllerFile = null;
        $current_route_array = explode('/', $current_route);
        $paramRoute = [];

        Hooks::run("before_dispatch", $current_route);
        try {
            // Try to find the controller file in any of the page directories
            foreach (self::$pageDirs as $page_dir) {
                $potential_controller = self::findControllerFile($page_dir . $current_route);
                if ($potential_controller !== null) {
                    $controllerFile = $potential_controller;
                    break;
                }
            }
            if ($controllerFile === null) {
                // scan for _param folders
                $paramRoute = self::findParamControllerFile($current_route_array);
                if ($paramRoute !== null) {
                    $controllerFolder = $paramRoute;
                    $params =  self::getParams();
                    $controllerFile = self::findControllerFile($controllerFolder);
                }
            }
            // If there is no controller file or the page corresponds to the error page (404)
            if ($controllerFile === null || $page_dir . $current_route === self::$errorPage) {
                self::error("404");
                Hooks::run("after_dispatch", $current_route, $current_route_array);
                return;
            }
            self::$currentPath = $controllerFile;
            require_once $controllerFile;
            $controllerClass = self::findControllerClass($controllerFile);

            if ($controllerClass) {
                $controller = new $controllerClass();
                $controller->main();
            }
        } catch (Error $e) {
            if (Config::get('app')['debug'] == false) {
                self::error(500, $e->getMessage());
            }
        }
        Hooks::run("after_dispatch", $current_route);
    }

    /**
     * Redirect to a specific URL
     * 
     * @param string $url url to redirect to
     * @param int $response_code HTTP response code (default: 301)
     * @param bool $internal_only if true, only redirect if the URL is internal (same domain)
     * @return never
     */
    public static function redirect(string $url, int $response_code = 301, bool $internal_only = false): void
    {
        if ($internal_only) {
            if (!self::isInternal($url)) {
                return;
            }
        }
        if (!self::validateUrl($url)) {
            return;
        }
        header(header: "Location: $url", replace: true, response_code: $response_code);
        exit;
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
     * Checks if a URL is valid
     * 
     * @param string $url
     * @return bool
     */
    public static function validateUrl(string $url, array $allowed_schemes = ['http', 'https']): bool
    {

        // check if url is relative (starts with / or ../ or ./)
        if (preg_match('#^(\/|\.\.?\/)#', $url)) {
            return true;
        }

        // Check if the URL is valid
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }
        $parts = parse_url($url);
        return isset($parts['scheme']) && in_array(strtolower($parts['scheme']), $allowed_schemes);
    }

    /**
     * Checks if a URL is from the same domain
     * @param string $url
     * @return bool
     */
    public static function isInternal(string $url): bool
    {
        if (!preg_match('#^(https?:|//)#', $url)) return true;
        $host = parse_url($url, PHP_URL_HOST);
        return $host === $_SERVER['HTTP_HOST'];
    }
}

// Initialize static properties
Router::init();
