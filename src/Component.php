<?php

namespace Goramax\NoctalysFramework;
use Goramax\NoctalysFramework\Finder;
use Goramax\NoctalysFramework\Hooks;

class Component
{
    /**
     * Load a component file by name
     * 
     * @param string $componentName component name
     * @param array $data data to pass to the component as variables
     * @return void
     */
    public static function load($componentName, $data = []): void
    {
        extract($data);
        Hooks::run("before_component", $componentName, $data);

        $component = Finder::findComponent($componentName);
        if ($component === "") {
            return;
        }

        ob_start();
        require $component;
        $component = ob_get_clean();
        echo $component;
        
        Hooks::run("after_component", $componentName, $data);
    }
}