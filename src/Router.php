<?php

namespace Goramax\NoctalysFramework;

use Error;
use Goramax\NoctalysFramework\Config;
use Goramax\NoctalysFramework\Hooks;
use Goramax\NoctalysFramework\Cache;

class Router
{

    private static $pageDirs;
    private static $directories;
    private static $errorPage;
    private static $params;
    private static $currentPath;
    public static $current_route;
    private static ?bool $isCacheEnabled = null;

    /**
     * Check if router cache can be used
     * 
     * @return bool True if router cache can be used
     */
    private static function canUseCache(): bool
    {
        if (self::$isCacheEnabled === null) {
            $cacheConfig = Config::get('cache');
            $globalCacheEnabled = $cacheConfig['enabled'] ?? false;
            $routerCacheEnabled = $cacheConfig['router_cache'] ?? false;
            self::$isCacheEnabled = $globalCacheEnabled && $routerCacheEnabled;
        }
        return self::$isCacheEnabled;
    }

    /**
     * Generate a route pattern from a path by replacing parameter values with placeholders
     * 
     * @param string $path The actual URL path
     * @param array $params The extracted parameters
     * @return string The route pattern
     */
    private static function generateRoutePattern(string $path, array $params): string
    {
        // Create a pattern by replacing parameter values with placeholders
        $pattern = $path;
        $segments = explode('/', trim($path, '/'));

        // For each parameter, replace the value in the path with a placeholder
        foreach ($params as $name => $value) {
            $pattern = str_replace('/' . $value, '/{' . $name . '}', $pattern);
        }

        return $pattern;
    }

    /**
     * Find route in the cache using pattern matching
     * 
     * @param string $requestPath The request path to look for
     * @return array|null [controllerFile, params, pattern] if found in cache, null otherwise
     */
    private static function findRouteInCache(string $requestPath): ?array
    {
        if (!self::canUseCache()) {
            return null;
        }

        // Get all cached route patterns
        $routePatterns = Cache::get('router', 'route_patterns');
        if (!is_array($routePatterns)) {
            return null;
        }

        // Split the request path into segments
        $requestSegments = explode('/', trim($requestPath, '/'));

        // Try to match against each pattern
        foreach ($routePatterns as $pattern => $data) {
            // Skip if pattern is not properly formed
            if (empty($pattern)) {
                continue;
            }

            // Split pattern into segments
            $patternSegments = explode('/', trim($pattern, '/'));

            // Skip if segment count doesn't match
            if (count($patternSegments) !== count($requestSegments)) {
                continue;
            }

            $params = [];
            $matched = true;

            // Compare segments
            for ($i = 0; $i < count($patternSegments); $i++) {
                $patternSegment = $patternSegments[$i];
                $requestSegment = $requestSegments[$i];

                // Check if this is a parameter segment
                if (preg_match('/^\{([a-zA-Z0-9_]+)\}$/', $patternSegment, $matches)) {
                    // This is a parameter, store its value
                    $paramName = $matches[1];
                    $params[$paramName] = $requestSegment;
                }
                // Regular segment, must match exactly
                elseif ($patternSegment !== $requestSegment) {
                    $matched = false;
                    break;
                }
            }

            if ($matched) {
                // Return the controller and the extracted parameters
                return [$data['controller'], $params, $pattern];
            }
        }

        return null;
    }

    /**
     * Store a route pattern in the cache
     * 
     * @param string $pattern The route pattern with {param} placeholders
     * @param string $controllerFile Path to the controller file
     * @return bool True on success, false on failure
     */
    private static function storeRoutePatternInCache(string $pattern, string $controllerFile): bool
    {
        if (!self::canUseCache()) {
            return false;
        }

        // Get existing patterns or initialize empty array
        $patterns = Cache::get('router', 'route_patterns');
        if (!is_array($patterns)) {
            $patterns = [];
        }

        // Add or update this pattern
        $patterns[$pattern] = [
            'controller' => $controllerFile
        ];

        // Store back to cache
        return Cache::set('router', 'route_patterns', $patterns);
    }

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
    private static function autoDiscoverRoutes(): array
    {
        // First check if we have cached routes
        if (self::canUseCache()) {
            $cachedRoutes = Cache::get('router', 'discovered_routes');
            if ($cachedRoutes !== null) {
                return $cachedRoutes;
            }
        }

        // If no cached routes, perform discovery
        $routes = [];

        foreach (self::$pageDirs as $pageDir) {
            self::discoverRoutesInDirectory($pageDir, '', $routes);
        }

        // Cache the discovered routes if caching is enabled
        if (self::canUseCache()) {
            Cache::set('router', 'discovered_routes', $routes);
        }

        return $routes;
    }

    /**
     * Recursively discover routes in a directory
     * 
     * @param string $baseDir The base directory to search in
     * @param string $currentPath The current path relative to the base directory
     * @param array &$routes Reference to the routes array to populate
     * @return void
     */
    private static function discoverRoutesInDirectory(string $baseDir, string $currentPath, array &$routes): void
    {
        $fullPath = $baseDir . $currentPath;

        if (!is_dir($fullPath)) {
            return;
        }

        // Check if this directory contains a controller file
        $dirName = basename($fullPath);
        $controllerFileName = $dirName . '.controller.php';
        $controllerFilePath = $fullPath . '/' . $controllerFileName;

        // If controller exists, add to routes
        if (file_exists($controllerFilePath)) {
            // Fix for the root route
            $routePath = $currentPath;
            if (empty($routePath)) {
                $routePath = '/home'; // Default to /home for the root
            }
            $routes[$routePath] = $controllerFilePath;
        }

        // Scan directory for subdirectories
        $items = scandir($fullPath);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itemPath = $fullPath . '/' . $item;
            if (is_dir($itemPath)) {
                $isParamDir = str_ends_with($item, '_param');

                if ($isParamDir) {
                    // Extract parameter name from directory name
                    $paramName = substr($item, 0, -6); // Remove '_param' suffix

                    // Build route path with parameter
                    $newPath = $currentPath . '/{' . $paramName . '}';

                    // Recursively discover routes in parameter directory
                    self::discoverRoutesInDirectory($baseDir, $newPath, $routes);
                } else {
                    // Normal directory, just append to path
                    $newPath = $currentPath . '/' . $item;

                    // Recursively discover routes in subdirectory
                    self::discoverRoutesInDirectory($baseDir, $newPath, $routes);
                }
            }
        }
    }

    /**
     * Match a route with parameters
     * 
     * @param string $requestPath The request path to match
     * @param array $routes The discovered routes
     * @return array|null [controllerFile, params] if match found, null otherwise
     */
    private static function matchRoute(string $requestPath, array $routes): ?array
    {
        // Check for exact match first
        if (isset($routes[$requestPath])) {
            return [$routes[$requestPath], []];
        }

        // Check cache for this specific path
        if (self::canUseCache()) {
            $cachedMatch = Cache::get('router', 'route_match_' . md5($requestPath));
            if ($cachedMatch !== null) {
                return $cachedMatch;
            }
        }

        // Split request path into segments
        $requestSegments = explode('/', trim($requestPath, '/'));
        if (empty($requestSegments[0])) {
            $requestSegments[0] = 'home'; // Handle root path
        }

        // Try to match routes with parameters
        foreach ($routes as $routePath => $controllerFile) {
            // Skip routes without parameters
            if (strpos($routePath, '{') === false) {
                continue;
            }

            $routeSegments = explode('/', trim($routePath, '/'));
            if (empty($routeSegments[0])) {
                $routeSegments[0] = 'home'; // Handle root path
            }

            // Skip if segment count doesn't match
            if (count($routeSegments) !== count($requestSegments)) {
                continue;
            }

            $params = [];
            $matched = true;

            // Compare segments
            for ($i = 0; $i < count($routeSegments); $i++) {
                $routeSegment = $routeSegments[$i];
                $requestSegment = $requestSegments[$i];

                // Check if this is a parameter segment
                if (preg_match('/^\{([a-zA-Z0-9_]+)\}$/', $routeSegment, $matches)) {
                    // This is a parameter, store its value
                    $paramName = $matches[1];
                    $params[$paramName] = $requestSegment;
                }
                // Regular segment, must match exactly
                elseif ($routeSegment !== $requestSegment) {
                    $matched = false;
                    break;
                }
            }

            if ($matched) {
                $result = [$controllerFile, $params];

                // Cache this match if caching is enabled
                if (self::canUseCache()) {
                    Cache::set('router', 'route_match_' . md5($requestPath), $result);
                }

                return $result;
            }
        }

        return null;
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
        Hooks::run("before_dispatch", $current_route);

        try {
            // Try to find route in cache first using pattern matching
            $matchResult = self::findRouteInCache($current_route);

            if ($matchResult !== null) {
                // We found a cached route pattern match
                list($controllerFile, $params, $pattern) = $matchResult;

                // Set params for retrieval via getParams()
                self::$params = $params;

                // Execute the controller
                self::$currentPath = $controllerFile;
                require_once $controllerFile;
                $controllerClass = self::findControllerClass($controllerFile);

                if ($controllerClass) {
                    $controller = new $controllerClass();
                    $controller->main();
                }
            } else {
                // Route not in cache, use standard route finding logic
                $controllerFile = null;
                $current_route_array = explode('/', $current_route);

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
                        $params = self::getParams();
                        $controllerFile = self::findControllerFile($controllerFolder);
                    }
                }

                // If there is no controller file or the page corresponds to the error page (404)
                $isErrorPage = false;
                foreach (self::$pageDirs as $pageDir) {
                    if ($pageDir . $current_route === self::$errorPage) {
                        $isErrorPage = true;
                        break;
                    }
                }

                if ($controllerFile === null || $isErrorPage) {
                    self::error(404);
                    Hooks::run("after_dispatch", $current_route, $current_route_array);
                    return;
                }

                // If this was a route with parameters, create and store a pattern
                if (!empty(self::$params)) {
                    $pattern = self::generateRoutePattern($current_route, self::$params);
                    self::storeRoutePatternInCache($pattern, $controllerFile);
                }
                // For static routes, store with exact path
                else {
                    self::storeRoutePatternInCache($current_route, $controllerFile);
                }

                // Execute the controller
                self::$currentPath = $controllerFile;
                require_once $controllerFile;
                $controllerClass = self::findControllerClass($controllerFile);

                if ($controllerClass) {
                    $controller = new $controllerClass();
                    $controller->main();
                }
            }
        } catch (Error $e) {
            if (Config::get('app')['debug'] == false) {
                self::error(500, $e->getMessage());
            } else {
                throw $e; // Rethrow for easier debugging when in debug mode
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
