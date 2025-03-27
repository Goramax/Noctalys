<?php

namespace Goramax\NoctalysFramework;

use Error;
use Goramax\NoctalysFramework\Config;
use Noctalys\NoctalysApp\Frontend\Pages\Error\ErrorController;

class Router
{

    private static $page_dirs;
    private static $directories;
    private static $errorPage;
    private static $params;
    private static $currentPath;

    /**
     * Initialize static properties
     */
    public static function init(): void
    {
        // change to an array of directories
        self::$directories = Config::get_router_config()["page_scan"];
        self::$errorPage = getcwd()."/".Config::get_router_config()["error_page"];
        foreach (self::$directories as $directory) {
            self::$page_dirs[] = getcwd() . "/" . $directory["path"] . "/" . $directory["folder_name"];
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
        return "NOT IMPLEMENTED YET";
        // Implementation would need to iterate through all directories in self::$page_dirs
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
        //echo($param_folder);
        if (is_dir($param_folder)) {
            //echo " true <br>";
            return true;
        }
        //echo " false <br>";
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
        foreach (self::$page_dirs as $page_dir) {
            foreach ($current_route_array as $index => $current_route) {
                if ($index + 1 < count($current_route_array)) {
                    //check if directory exists
                    if (!is_dir(filename: $page_dir . '/' . $current_route) && !is_dir($page_dir . '/' . self::arrayToPath($file_path) . "/" . $file_path[count($file_path) - 1] . '_param')) {
                        return null;
                    }
                    $folders = scandir($page_dir . '/' . self::arrayToPath($file_path));
                    // check if the folder contains other folders
                    if (!$folders) {
                        break;
                    }
                    // if the next folder from route exists in path, add it to the file_path array
                    if (in_array($current_route_array[$index + 1], $folders)) {
                        $file_path[] = $current_route_array[$index + 1];
                    }
                    // else if has _param folder, add it to the params array and file_path array
                    else if (self::hasParamFolders($page_dir . '/' . $current_route)) {
                        self::$params[$current_route] = $current_route_array[$index + 1];
                        $file_path[] = $current_route . '_param';
                    } else if (self::hasParamFolders($page_dir . '/' . self::arrayToPath($file_path))) {
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
    public static function getParams(): array {
        return self::$params;
    }

    /**
     * Call the error controller
     * @param string $code
     * @return void
     */
    public static function error(string $code): void
    {
        $controllerFile = self::findControllerFile(self::$errorPage);
        if (!$controllerFile) {
            throw new Error("Controller file not found at " . self::$errorPage);
        }
        self::$currentPath = $controllerFile;
        require_once $controllerFile;
        $controllerClass = self::findControllerClass($controllerFile);
        if ($controllerClass) {
            http_response_code($code);
            $controller = new $controllerClass;
            $controller->main();
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
        $params = []; // array to store the parameters (key-value pairs)

        // Try to find the controller file in any of the page directories
        foreach (self::$page_dirs as $page_dir) {
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
                //var_dump($params);
                $controllerFile = self::findControllerFile($controllerFolder);
            }
        }
        // If there is no controller file or the page corresponds to the error page (404)
        if ($controllerFile === null || $page_dir.$current_route === self::$errorPage){
            // 404
            self::error("404");
            return;
        }
        self::$currentPath = $controllerFile;
        //echo "folder" . self::$currentPath;
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

    /**
     * Automatically discover routes
     */
    public static function dumpRoutes(): array
    {
        $routes = self::autoDiscoverRoutes();
        return $routes;
    }
}

// Initialize static properties
Router::init();
