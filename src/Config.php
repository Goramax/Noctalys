<?php

namespace Goramax\NoctalysFramework;

class Config
{

    private static string $configFile;
    private static mixed $configContent;
    public static function init(): void
    {
        self::$configFile = getcwd() . '/config.json'; // TODO: use absolute path
        self::$configContent = self::getConfigContent();
    }

    /**
     * Get the configuration from the config file
     * @param string $key
     * @return array
     */
    public static function get(string $key): mixed
    {
        if (array_key_exists($key,  self::$configContent)) {
            return self::$configContent[$key];
        } else {
            throw new \Exception("Key not found in config file: $key");
        }
    }

    /**
     * Get the configuration from the config file
     * 
     * @return array
     */
    private static function getConfigContent(){
        $configFile = self::$configFile;
        if(file_exists($configFile)){
            $config = json_decode(file_get_contents($configFile), true);
            return $config;
        } else {
            throw new \Exception("Config file not found: $configFile");
        }
    }

}

Config::init();