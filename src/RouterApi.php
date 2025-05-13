<?php

namespace Goramax\NoctalysFramework;

use Goramax\NoctalysFramework\Api\Response;
use Goramax\NoctalysFramework\Attributes\Route;
use Goramax\NoctalysFramework\Config;
use ReflectionClass;
use ReflectionMethod;

class RouterApi
{
    protected static array $routes = [];

    public static function registerControllers(string $directory): void
    {
        $files = glob($directory . '/*.php');
        foreach ($files as $file) {
            require_once $file;

            $className = self::getClassFromFile($file);
            if (!$className) continue;

            $reflection = new ReflectionClass($className);

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                foreach ($method->getAttributes(Route::class) as $attr) {
                    /** @var Route $routeAttr */
                    $routeAttr = $attr->newInstance();
                    $pattern = self::compilePath($routeAttr->path);
                    self::$routes[] = [
                        'method' => strtoupper($routeAttr->method),
                        'path' => $routeAttr->path,
                        'pattern' => $pattern,
                        'controller' => $className,
                        'action' => $method->getName()
                    ];
                }
            }
        }
    }

    public static function dispatch(): void
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestPath = strtok($_SERVER['REQUEST_URI'], '?');

        try{
        Hooks::run("before_api_dispatch", [$requestMethod, $requestPath]);
        if (empty($requestPath) || $requestPath === '/') {
            http_response_code(404);
            Response::error('API route not found', 404);
            return;
        }
        foreach (self::$routes as $route) {
            if ($route['method'] !== $requestMethod) continue;

            if (preg_match($route['pattern'], $requestPath, $matches)) {
                array_shift($matches);
                $params = self::extractParams($route['path'], $matches);
                $controller = new $route['controller']();
                
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
                        call_user_func([$controller, $route['action']], $params, $requestData);
                    } else {
                        call_user_func([$controller, $route['action']], $requestData);
                    }
                } else {
                    // For GET and DELETE requests, just pass the params
                    call_user_func([$controller, $route['action']], $params);
                }
                
                Hooks::run("after_api_dispatch", [$requestMethod, $requestPath]);
                return;
            }
        }
        } catch (\Throwable $e) {
            http_response_code(500);
            
            if (Config::get('app')['debug'] ?? false) {
                Response::error($e->getMessage(), 500);
            } else {

                Response::error('Internal Server Error', 500);
            }
            return;
        }

        http_response_code(404);
        Response::error('API route not found', 404);
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
}