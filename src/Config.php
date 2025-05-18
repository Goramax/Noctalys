<?php

namespace Goramax\NoctalysFramework;
use Goramax\NoctalysFramework\ErrorHandler;

class Config
{

    private static string $configFile;
    private static mixed $configContent;
    public static function init(): void
    {
        self::initConfig();
        self::$configFile = DIRECTORY . '/config.json';
        self::$configContent = self::getConfigContent();
    }

    /**
     * Get the configuration from the config file
     * @param string $key
     * @return array
     */
    public static function get(string $key): array
    {
        if (array_key_exists($key,  self::$configContent)) {
            return self::$configContent[$key];
        }
        else{
            return [];
        }
    }

    /**
     * Get the configuration from the config file
     * 
     * @return array
     */
    private static function getConfigContent()
    {
        $configFile = self::$configFile;
        if (file_exists($configFile)) {
            $config = json_decode(file_get_contents($configFile), true);
            $config = array_merge(self::$configContent, $config);
            return $config;
        } else {
            ErrorHandler::fatal("Config file not found: $configFile");
            return [];
        }
    }

    /**
     * Initialize the configuration with default values
     */
    public static function initConfig(): void
    {
        // set config content with default values before reading the file
        self::$configContent = [
            'app' => [
                'name' => 'My Noctalys App',
                'timezone' => 'auto',
                'version' => '1.0.0',
                'debug' => true
            ],
            'cache' => [
                'enabled' => false,
                'cache_folder' => 'cache/noctalys',
                'router_cache' => true,
                'api_router_cache' => true,
                'finder_cache' => true
            ],
            'env' => [
                'extended_compat' => false
            ],
            'template_engine' => [
                'engine' => 'no'
            ],
            'router' => [
                'enabled' => true,
                'page_scan' => [
                    [
                        'folder_name' => 'pages',
                        'path' => 'src/Frontend'
                    ]
                ],
                'error_page' => 'src/Frontend/pages/error'
            ],
            'api' => [
                'enabled' => true,
                'controllers_location' => 'src/Backend/Controllers',
                'api_url' => '/api'
            ],
            'hooks_file' => 'src/hooks.php',
            'layouts' => [
                'default' => 'default',
                'sources' => [
                    [
                        'folder_name' => 'layouts',
                        'path' => 'src/Frontend'
                    ]
                ]
            ],
            'components' => [
                'sources' => [
                    [
                        'folder_name' => 'components',
                        'path' => 'src/Frontend'
                    ]
                ]
            ],
            'assets' => [
                'sources' => [
                    [
                        'folder_name' => 'assets',
                        'path' => 'src/Frontend'
                    ]
                ]
            ]
        ];
    }
}

Config::init();