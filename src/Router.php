<?php

namespace Goramax\NoctalysFramework;

use Goramax\NoctalysFramework\Config;

class Router
{

    private static $page_dirs;
    private static $directories;

    /**
     * Initialize static properties
     */
    public static function init(): void
    {
        // change to an array of directories
        self::$directories = Config::get_router_config()["page_scan"];
        foreach (self::$directories as $directory) {
            //TODO scan every directory with folder_name in path (recursive)
            self::$page_dirs[] = getcwd() . "/" . $directory["path"] . "/" . $directory["folder_name"];
        }
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

    private static function arrayToPath(array $array): string
    {
        $path = "";
        foreach ($array as $key => $value) {
            if ($key+1 != count($array)) {
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
     * Dispatch the request to the right controller
     */
    public static function dispatch(): void
    {
        $current_route = self::getCurrentRoute();
        $controllerFile = null;
        $current_route_array = explode('/', $current_route);
        $file_path = []; // array to reconstruct the path to the controller file
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
            foreach (self::$page_dirs as $page_dir) {
                foreach ($current_route_array as $index => $current_route) {
                    if ($index + 1 < count($current_route_array)) {
                        //check if directory exists
                        if (!is_dir(filename: $page_dir . '/' . $current_route) && !is_dir($page_dir . '/' . self::arrayToPath($file_path) . "/" . $file_path[count($file_path)-1].'_param')) {
                            echo "404 - Directory not found";
                            echo $page_dir . '/' . self::arrayToPath($file_path) . "/" . $file_path[count($file_path)-1].'_param';
                            break;
                        }
                        $folders = scandir( $page_dir . '/' . self::arrayToPath($file_path));
                        // check if the folder contains other folders
                        if (!$folders) {
                            break;
                        }
                        // if the next folder from route exists in path, add it to the file_path array
                        if (in_array($current_route_array[$index + 1], $folders)) {
                            echo "added ".$current_route_array[$index+1]." to file_path<br>";
                            $file_path[] = $current_route_array[$index+1];
                        }
                        // else if has _param folder, add it to the params array and file_path array
                        else if (self::hasParamFolders($page_dir . '/' . $current_route)) {
                            $params[$current_route] = $current_route_array[$index + 1];
                            $file_path[] = $current_route . '_param';
                            echo "added ".$current_route."_param to file_path<br>";
                        }
                        else if (self::hasParamFolders($page_dir . '/' . self::arrayToPath($file_path))){
                            $params[$current_route] = $current_route_array[$index];
                            $file_path[] = $file_path[$index-1].'_param';
                            echo "added ". $file_path[$index-1].'_param'." to file_path<br>";
                        }
                        else {
                            echo"404";
                            // var_dump(self::arrayToPath($file_path));
                            break;
                        }
                    }
                }
            };
            var_dump($file_path);
            $controllerFile = self::findControllerFile($page_dir . '/' . self::arrayToPath($file_path));
            echo $page_dir .'/'. self::arrayToPath($file_path);
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
