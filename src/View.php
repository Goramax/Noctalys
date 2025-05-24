<?php

namespace Goramax\NoctalysFramework;

use Goramax\NoctalysFramework\Router;
use Goramax\NoctalysFramework\TemplateEngines\TemplateEngineInterface;

class View
{
    private static bool $rendered = false;

    public static function render(string $view, array $data = [], string $layout = 'default', string $forceEngine = ''): void
    {
        if (self::$rendered) return;
        if ($forceEngine) {
            $engine = $forceEngine;
        } else {
            try {
                $engine = Config::get('template_engine');
                $engine = $engine['engine'];
            } catch (\Exception $e) {
                $engine = 'no';
            }
        }
        $engineClass = "Goramax\\NoctalysFramework\\TemplateEngines\\" . ucfirst($engine) . "Engine";
        if (!class_exists($engineClass)) {
            throw new \ErrorException("Template engine $engineClass not found", 0, E_USER_ERROR);
        }
        if (!is_subclass_of($engineClass, TemplateEngineInterface::class)) {
            throw new \ErrorException("Template engine $engineClass must implement TemplateEngineInterface", 0, E_USER_ERROR);
        }
        $engineInstance = new $engineClass(Router::getCurrentFolder(), Config::get('template_engine')['options'] ?? []);

        $engineInstance->process($view, $data, $layout);  

        self::$rendered = true;
    }
}
