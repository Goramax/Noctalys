<?php

namespace Goramax\NoctalysFramework\TemplateEngines;

use Goramax\NoctalysFramework\TemplateEngines\TemplateEngineInterface;
use Goramax\NoctalysFramework\Router;
use Goramax\NoctalysFramework\Finder;
use Goramax\NoctalysFramework\Hooks;
use Goramax\NoctalysFramework\Env;
use \Twig\Environment;
use \Twig\Loader\FilesystemLoader;
use \Twig\TwigFunction;

class TwigEngine implements TemplateEngineInterface
{
    private Environment $twig;
    private string $currentFolder;
    private FilesystemLoader $loader;

    public function __construct(string $currentFolder, array $options = [])
    {
        $this->currentFolder = $currentFolder;
        // Ensure the directory exists before creating the loader
        if (!is_dir($currentFolder)) {
            throw new \ErrorException("Directory not found: $currentFolder", 0, E_USER_ERROR);
        }
        
        if (!class_exists('Twig\Environment')) {
            throw new \ErrorException(
                "Twig is not installed. Please install it using Composer: \n" .
                "composer require 'twig/twig:^3.0'",
                0,
                E_USER_ERROR
            );
        }
        
        $this->loader = new FilesystemLoader($currentFolder);
        
        // Define default options
        $defaultOptions = [
            'cache' => DIRECTORY . '/cache/twig',
            'debug' => Env::get('APP_ENV') === 'dev',
            'autoescape' => false,
            'strict_variables' => false,
            'optimizations' => 0,
            'auto_reload' => false,
            'charset' => 'UTF-8'
        ];
        
        // Merge user options with defaults
        $twigOptions = array_merge($defaultOptions, $options);
        
        $this->twig = new Environment($this->loader, $twigOptions);
        
        // Make Twig instance globally accessible
        global $twig;
        $twig = $this->twig;
        
        // Automatically register all helpers
        $this->registerHelpers();
    }
    
    /**
     * Automatically registers all helpers in the Twig environment
     */
    private function registerHelpers(): void
    {
        // Get all user-defined functions
        $definedFunctions = get_defined_functions();
        $userFunctions = $definedFunctions['user'];
        
        // Functions to exclude or process specially
        $excludedFunctions = ['dump', 'dd', 'var_dump'];
        
        foreach ($userFunctions as $function) {
            // Ignore excluded functions
            if (in_array($function, $excludedFunctions)) {
                continue;
            }
            
            // Special handling for render_component
            if ($function === 'render_component') {
                $this->twig->addFunction(new TwigFunction($function, function (...$args) use ($function) {
                    // Add 'twig' as the extension argument
                    $args[] = 'twig';
                    return call_user_func_array($function, $args);
                }, ['is_safe' => ['html']]));
            } else {
                $this->twig->addFunction(new TwigFunction($function, function (...$args) use ($function) {
                    return call_user_func_array($function, $args);
                }, ['is_safe' => ['html']]));
            }
        }
    }

    public function process(string $view, array $data = [], string $layout = 'default'): void
    {
        $viewFile = "$view.view.twig";
        $layoutFile = Finder::findLayout($layout, 'twig');
        if (!$layoutFile) {
            throw new \ErrorException("Layout file not found: $layout", 0, E_USER_ERROR);
        }
        
        // Run before layout hooks
        Hooks::run("before_layout", $layout, $viewFile, $layoutFile, $data);
        Hooks::run("before_layout_" . $layout, $viewFile, $layoutFile, $data);
        
        // Run before view hooks
        Hooks::run("before_view", $view, $viewFile, $layout, $data);
        Hooks::run("before_view_" . $view, $viewFile, $layout, $data);
        
        // Render the view
        try {
            $viewContent = $this->twig->render("$view.view.twig", $data);
        } catch (\Exception $e) {
            throw new \ErrorException("Error rendering view '$view': " . $e->getMessage(), 0, E_USER_ERROR);
        }
        
        // Wrap view content with data-view attribute
        $viewContent = "<div data-view=\"$view\">{$viewContent}</div>";
        
        // Run after view hooks
        Hooks::run("after_view", $view, $viewFile, $layout, $data);
        Hooks::run("after_view_" . $view, $viewFile, $layout, $data);
        
        // If it's a Twig layout
        if (preg_match('/\.twig$/', $layoutFile)) {
            $layoutDir = dirname($layoutFile);
            $layoutName = basename($layoutFile);
            
            // Add the layout directory to the loader paths if it's not already there
            try {
                $this->loader->getPaths();
                $paths = $this->loader->getPaths();
                if (!in_array($layoutDir, $paths)) {
                    $this->loader->addPath($layoutDir);
                }
            } catch (\Exception $e) {
                // If getPaths() fails, add the path anyway
                $this->loader->addPath($layoutDir);
            }
            
            $layoutData = array_merge($data, ['_view' => $viewContent]);
            echo $this->twig->render($layoutName, $layoutData);
        } else {
            // If it's a PHP layout
            $_view = $viewContent;
            require $layoutFile;
        }
        
        // Run after layout hooks
        Hooks::run("after_layout", $layout, $viewFile, $layoutFile, $data);
        Hooks::run("after_layout_" . $layout, $viewFile, $layoutFile, $data);
    }
}