<?php

namespace Goramax\NoctalysFramework;

class Config
{

    private static string $config_file;
    public static function init(): void
    {
        self::$config_file = getcwd() . '/config.json'; // TODO: use absolute path
    }

    /**
     * Get the configuration from the config file
     * 
     * @return array
     */
    public static function get_config(){
        $config_file = self::$config_file;
        if(file_exists($config_file)){
            $config = json_decode(file_get_contents($config_file), true);
            return $config;
        } else {
            throw new \Exception("Config file not found: $config_file");
        }
    }

    /**
     * Get the router configuration from the config file
     * 
     * @return array
     */
    public static function get_router_config(): mixed{
        $config = self::get_config();
        return $config['router'];
    }
}

Config::init();