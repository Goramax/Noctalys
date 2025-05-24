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
     * @param string $extension file extension (default: 'php')
     * @return void
     */
    public static function load($componentName, $data = [], $extension = 'php'): void
    {
        Hooks::run("before_component", $componentName, $data);
        Hooks::run("before_component_" . $componentName, $data);

        $component = Finder::findComponent($componentName, $extension);
        if ($component === "") {
            return;
        }
        
        $rendered = false;
        
        switch ($extension) {
            case 'twig':
                $rendered = self::renderTwigComponent($component, $data);
                break;
                
            case 'tpl':
                $rendered = self::renderSmartyComponent($component, $data);
                break;
                
            case 'latte':
                $rendered = self::renderLatteComponent($component, $data);
                break;
                
            default:
                $rendered = self::renderPhpComponent($component, $data);
                break;
        }
        
        if ($rendered) {
            Hooks::run("after_component", $componentName, $data);
            Hooks::run("after_component_" . $componentName, $data);
        }
    }
    
    /**
     * Render a component using Twig template engine
     * 
     * @param string $component Path to component file
     * @param array $data Data to pass to the component
     * @return bool Whether the rendering was successful
     */
    private static function renderTwigComponent(string $component, array $data): bool
    {
        global $twig;
        if (isset($twig) && $twig instanceof \Twig\Environment) {
            try {
                $componentsDir = dirname($component);
                if (!in_array($componentsDir, $twig->getLoader()->getPaths())) {
                    $twig->getLoader()->addPath($componentsDir);
                }
                
                echo $twig->render(basename($component), $data);
                return true;
            } catch (\Exception $e) {
                throw new \ErrorException("Error rendering Twig component: " . $e->getMessage(), 0, E_USER_WARNING);
            }
        } else {
            throw new \ErrorException("Twig environment not available", 0, E_USER_WARNING);
        }
        return false;
    }
    
    /**
     * Render a component using Smarty template engine
     * 
     * @param string $component Path to component file
     * @param array $data Data to pass to the component
     * @return bool Whether the rendering was successful
     */
    private static function renderSmartyComponent(string $component, array $data): bool
    {
        global $smarty;
        if (isset($smarty) && $smarty instanceof \Smarty\Smarty) {
            try {
                foreach ($data as $key => $value) {
                    $smarty->assign($key, $value);
                }
                echo $smarty->fetch($component);

                foreach (array_keys($data) as $key) {
                    $smarty->clearAssign($key);
                }
                return true;
            } catch (\Exception $e) {
                throw new \ErrorException("Error rendering Smarty component: " . $e->getMessage(), 0, E_USER_WARNING);
            }
        } else {
            throw new \ErrorException("Smarty environment not available", 0, E_USER_WARNING);
        }
        return false;
    }
    
    /**
     * Render a component using Latte template engine
     * 
     * @param string $component Path to component file
     * @param array $data Data to pass to the component
     * @return bool Whether the rendering was successful
     */
    private static function renderLatteComponent(string $component, array $data): bool
    {
        global $latte;
        if (isset($latte) && $latte instanceof \Latte\Engine) {
            try {
                echo $latte->renderToString($component, $data);
                return true;
            } catch (\Exception $e) {
                throw new \ErrorException("Error rendering Latte component: " . $e->getMessage(), 0, E_USER_WARNING);
            }
        } else {
            throw new \ErrorException("Latte environment not available", 0, E_USER_WARNING);
        }
        return false;
    }
    
    /**
     * Render a component using PHP
     * 
     * @param string $component Path to component file
     * @param array $data Data to pass to the component
     * @return bool Whether the rendering was successful
     */
    private static function renderPhpComponent(string $component, array $data): bool
    {
        // Extraire les données ici pour qu'elles soient disponibles dans le scope du require
        extract($data);
        
        ob_start();
        require $component;
        echo ob_get_clean();
        return true;
    }
}
