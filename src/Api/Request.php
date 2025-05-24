<?php

namespace Goramax\NoctalysFramework\Api;
use Goramax\NoctalysFramework\Hooks;

class Request
{
    private static function execute($url, $method, $data = null, $headers = [])
    {
        Hooks::run('request_all', $url, $method, $data, $headers);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        // Ensure that Content-Type is set for JSON requests
        if ($method !== 'GET' && $data && is_array($data)) {
            $headers[] = 'Content-Type: application/json';
        }
        
        if ($headers && count($headers) > 0) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        if ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($data) {
                if (is_array($data)) {
                    $data = json_encode($data);
                }
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \ErrorException("cURL error: $error", 0, E_USER_WARNING);
        }

        curl_close($ch);

        // Try to decode the JSON response if content-type is application/json
        if ($response && (strpos($contentType, 'application/json') !== false || self::looksLikeJson($response))) {
            $decodedResponse = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decodedResponse;
            }
        }

        // Return a structured response even for non-JSON data
        return [
            'success' => ($httpCode >= 200 && $httpCode < 300),
            'data' => $response,
            'status' => $httpCode
        ];
    }

    /**
     * Checks if a string looks like JSON
     * 
     * @param string $string The string to check
     * @return bool True if the string looks like JSON, false otherwise
     */
    private static function looksLikeJson($string) 
    {
        $string = trim($string);
        return (
            (str_starts_with($string, '{') && str_ends_with($string, '}')) || 
            (str_starts_with($string, '[') && str_ends_with($string, ']'))
        );
    }

    public static function get($url, $headers = [])
    {
        Hooks::run('request_get', $url, $headers);
        return self::execute($url, 'GET', null, $headers);
    }

    public static function post($url, $data, $headers = [])
    {
        Hooks::run('request_post', $url, $data, $headers);
        return self::execute($url, 'POST', $data, $headers);
    }

    public static function put($url, $data, $headers = [])
    {
        Hooks::run('request_put', $url, $data, $headers);
        return self::execute($url, 'PUT', $data, $headers);
    }

    public static function delete($url, $headers = [])
    {
        Hooks::run('request_delete', $url, $headers);
        return self::execute($url, 'DELETE', null, $headers);
    }

    public static function patch($url, $data, $headers = [])
    {
        Hooks::run('request_patch', $url, $data, $headers);
        return self::execute($url, 'PATCH', $data, $headers);
    }
}
