<?php

namespace Goramax\NoctalysFramework\Api;

class Response
{
    public static function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function success(mixed $data = [], string $message = 'OK', int $status = 200): void
    {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $status);
    }

    public static function error(string $message = 'An error occurred', int $status = 400, array $extra = []): void
    {
        self::json(array_merge([
            'success' => false,
            'message' => $message,
            'data' => []
        ], $extra), $status);
    }

    public static function redirect(string $url, int $status = 302): void
    {
        header("Location: $url", true, $status);
        exit;
    }

    public static function raw(string $content, string $contentType = 'text/plain', int $status = 200): void
    {
        http_response_code($status);
        header("Content-Type: $contentType");
        echo $content;
    } 
}