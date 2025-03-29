<?php

namespace Goramax\NoctalysFramework;
use Goramax\NoctalysFramework\Finder;

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
        $component = Finder::findComponent($componentName);
        if ($component === "") {
            return;
        }
        ob_start();
        require $component;
        $component = ob_get_clean();
        echo $component;
    }
}