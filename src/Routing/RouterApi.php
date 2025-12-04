<?php

namespace Noctalys\Framework\Routing;

use Noctalys\Framework\Http\Response;
use Noctalys\Framework\Attributes\Route;
use Noctalys\Framework\Core\Config;
use Noctalys\Framework\Services\Cache;
use Noctalys\Framework\Services\Hooks;
use ReflectionClass;
use ReflectionMethod;

class RouterApi
{
    protected static array $routes = [];
    protected static array $controllers = [];
    protected static ?string $controllersDirectory = null;
    private static ?bool $isCacheEnabled = null;
    private static bool $initialized = false;

    /**
     * Check if API router cache can be used
     * 
     * @return bool True if API router cache can be used
     */
    private static function canUseCache(): bool
    {
        // Check only once per process
        if (self::$isCacheEnabled === null) {
            $cacheConfig = Config::get('cache');
            
            // Check if global cache is enabled
            $globalCacheEnabled = !empty($cacheConfig) && 
                                isset($cacheConfig['enabled']) && 
                                $cacheConfig['enabled'] === true;
            
            // Check if api_router-specific cache is enabled (default to global setting if not specified)
            $apiRouterCacheEnabled = isset($cacheConfig['api_router_cache']) 
                ? $cacheConfig['api_router_cache'] === true 
                : $globalCacheEnabled;
            
            // Both global and api_router-specific settings must be enabled
            self::$isCacheEnabled = $globalCacheEnabled && $apiRouterCacheEnabled;
        }
        
        return self::$isCacheEnabled;
    }

    /**
     * Generate a pattern key from a route path and HTTP method
     * 
     * @param string $routePath The API route path definition (with {param} placeholders)
     * @param string $method The HTTP method (GET, POST, etc.)
     * @return string The route pattern key
     */
    private static function generatePatternKey(string $routePath, string $method): string
    {
        // Simply use the route definition with {param} placeholders as the pattern
        // Combined with the HTTP method to differentiate between GET/POST/etc.
        return "pattern_{$method}_{$routePath}";
    }

    /**
     * Register controllers from the given directory
     * 
     * @param string $directory The directory path
     * @return void
     */
    public static function registerControllers(string $directory): void
    {
        // Set controllers directory and initialize
        self::setControllersDirectory($directory);
        
        // For backwards compatibility, also pre-index the routes
        // But keep the lazy loading for future requests
        
        // First check if we have cached routes
        if (self::canUseCache()) {
            $cachedRoutes = Cache::get('api_router', 'api_routes');
            if (is_array($cachedRoutes) && !empty($cachedRoutes)) {
                self::$routes = $cachedRoutes;
                return;
            }
        }
        
        // No cached routes, get list of controllers but don't load them yet
        // This preserves the lazy loading benefit while maintaining backward compatibility
    }

    /**
     * Set the directory where API controllers are located
     * 
     * @param string $directory The directory path
     * @return void
     */
    public static function setControllersDirectory(string $directory): void
    {
        self::$controllersDirectory = $directory;
        self::$initialized = true;
        
        // Check if we have the controller list in cache
        if (self::canUseCache()) {
            $cachedControllers = Cache::get('api_router', 'controllers_list');
            if (is_array($cachedControllers)) {
                self::$controllers = $cachedControllers;
                return;
            }
        }
        
        // Scan just the controller files, without loading them
        self::$controllers = [];
        $files = glob($directory . '/*.php');
        
        foreach ($files as $file) {
            $controllerKey = basename($file);
            self::$controllers[$controllerKey] = $file;
        }
        
        // Cache the controller list if caching is enabled
        if (self::canUseCache()) {
            Cache::set('api_router', 'controllers_list', self::$controllers);
        }
    }

    /**
     * Find and register a controller for a specific request
     * 
     * @param string $requestMethod HTTP method (GET, POST, etc.)
     * @param string $requestPath Request URL path
     * @return bool True if a matching route was found and registered
     */
    private static function findAndRegisterController(string $requestMethod, string $requestPath): bool
    {
        if (!self::$initialized || empty(self::$controllersDirectory)) {
            return false;
        }
        
        // No cache hit, we need to search all controllers
        $foundMatch = false;
        
        // Iterate through controller files
        foreach (self::$controllers as $controllerKey => $file) {
            // Check if this controller is already registered
            $alreadyRegistered = false;
            foreach (self::$routes as $route) {
                if (isset($route['file']) && $route['file'] === $file) {
                    $alreadyRegistered = true;
                    break;
                }
            }
            
            // Skip if already registered
            if ($alreadyRegistered) {
                continue;
            }
            
            // Load the controller and check its routes
            require_once $file;
            $className = self::getClassFromFile($file);
            
            if (!$className) {
                continue;
            }
            
            $reflection = new ReflectionClass($className);
            
            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                foreach ($method->getAttributes(Route::class) as $attr) {
                    /** @var Route $routeAttr */
                    $routeAttr = $attr->newInstance();
                    $routeMethod = strtoupper($routeAttr->method);
                    $pattern = self::compilePath($routeAttr->path);
                    
                    $routeData = [
                        'method' => $routeMethod,
                        'path' => $routeAttr->path,
                        'pattern' => $pattern,
                        'controller' => $className,
                        'action' => $method->getName(),
                        'file' => $file
                    ];
                    
                    // Add to routes collection
                    self::$routes[] = $routeData;
                    
                    // Check if this route matches current request
                    if ($routeMethod === $requestMethod && preg_match($pattern, $requestPath)) {
                        $foundMatch = true;
                        
                        // Important: Don't store specific request path in cache,
                        // we'll use the pattern-based caching instead
                    }
                }
            }
            
            // If we found a match, we can stop searching
            if ($foundMatch) {
                break;
            }
        }
        
        return $foundMatch;
    }

    /**
     * Dispatch the API request
     */
    public static function dispatch(): void
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestPath = strtok($_SERVER['REQUEST_URI'], '?');

        try {
            Hooks::run("before_api_dispatch", [$requestMethod, $requestPath]);
            
            if (empty($requestPath) || $requestPath === '/') {
                http_response_code(404);
                Response::error('API route not found', 404);
                return;
            }
            
            // Try to match against already registered routes first
            $matchedRoute = null;
            foreach (self::$routes as $route) {
                if ($route['method'] !== $requestMethod) continue;
                
                if (preg_match($route['pattern'], $requestPath, $matches)) {
                    $matchedRoute = $route;
                    break;
                }
            }
            
            // If no match found, try to find and register a controller
            if ($matchedRoute === null) {
                $foundController = self::findAndRegisterController($requestMethod, $requestPath);
                
                // If we registered a new controller, try matching again
                if ($foundController) {
                    foreach (self::$routes as $route) {
                        if ($route['method'] !== $requestMethod) continue;
                        
                        if (preg_match($route['pattern'], $requestPath, $matches)) {
                            $matchedRoute = $route;
                            break;
                        }
                    }
                }
            }
            
            // If we found a matching route
            if ($matchedRoute !== null) {
                $matches = [];
                preg_match($matchedRoute['pattern'], $requestPath, $matches);
                array_shift($matches);
                $params = self::extractParams($matchedRoute['path'], $matches);
                
                // Generate pattern key using the route definition itself
                // This ensures only ONE cache entry per route pattern
                $patternKey = self::generatePatternKey($matchedRoute['path'], $requestMethod);
                
                // Check if we have a cached pattern that matches this request
                $cachedPatternData = null;
                if (self::canUseCache()) {
                    $cachedPatternData = Cache::get('api_router', $patternKey);
                }
                
                // If we don't have cached pattern data, create and store it
                if ($cachedPatternData === null) {
                    $patternData = [
                        'controller' => $matchedRoute['controller'],
                        'action' => $matchedRoute['action'],
                        'path' => $matchedRoute['path'],
                        'file' => $matchedRoute['file'] ?? null
                    ];
                    
                    if (self::canUseCache()) {
                        Cache::set('api_router', $patternKey, $patternData);
                    }
                    
                    $cachedPatternData = $patternData;
                }
                
                // Execute the controller
                $controller = new $cachedPatternData['controller']();
                
                $requestData = [];
                if (in_array($requestMethod, ['POST', 'PUT', 'PATCH'])) {
                    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
                    
                    if (strpos($contentType, 'application/json') !== false) {
                        // Decode JSON body
                        $requestBody = file_get_contents('php://input');
                        $requestData = json_decode($requestBody, true) ?? [];
                    } else {
                        // Else, assume form data
                        $requestData = $_POST;
                    }
                    
                    // Merge request data with params
                    if (is_array($params) && !empty($params)) {
                        call_user_func([$controller, $cachedPatternData['action']], $params, $requestData);
                    } else {
                        call_user_func([$controller, $cachedPatternData['action']], $requestData);
                    }
                } else {
                    // For GET and DELETE requests, just pass the params
                    call_user_func([$controller, $cachedPatternData['action']], $params);
                }
                
                Hooks::run("after_api_dispatch", [$requestMethod, $requestPath]);
                return;
            }
            
            // No matching route found
            http_response_code(404);
            Response::error('API route not found', 404);
            
        } catch (\Throwable $e) {
            http_response_code(500);
            
            if (Config::get('app')['debug'] ?? false) {
                Response::error($e->getMessage(), 500);
            } else {
                Response::error('Internal Server Error', 500);
            }
            return;
        }

        Hooks::run("after_api_dispatch", [$requestMethod, $requestPath]);
        exit;
    }

    private static function compilePath(string $path): string
    {
        return '#^' . preg_replace('#\{[^/]+\}#', '([^/]+)', $path) . '$#';
    }

    private static function extractParams(string $path, array $values): array
    {
        preg_match_all('#\{([^}]+)\}#', $path, $keys);
        return array_combine($keys[1], $values);
    }

    private static function getClassFromFile(string $file): ?string
    {
        $contents = file_get_contents($file);
        if (preg_match('/namespace\s+(.+);/', $contents, $ns) &&
            preg_match('/class\s+(\w+)/', $contents, $cls)) {
            return trim($ns[1]) . '\\' . $cls[1];
        }
        return null;
    }

    /**
     * Clear the API router cache
     * 
     * @return bool True on success, false on failure
     */
    public static function clearCache(): bool
    {
        if (!self::canUseCache()) {
            return false;
        }
        
        return Cache::clear('api_router');
    }
}