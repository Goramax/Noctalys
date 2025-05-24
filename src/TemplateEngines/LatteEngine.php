<?php

namespace Goramax\NoctalysFramework\TemplateEngines;

use Goramax\NoctalysFramework\TemplateEngines\TemplateEngineInterface;
use Goramax\NoctalysFramework\Finder;
use Goramax\NoctalysFramework\Hooks;
use Goramax\NoctalysFramework\Env;
use Latte\Engine;

class LatteEngine implements TemplateEngineInterface
{
    private Engine $latte;
    private string $currentFolder;
    private array $options;

    public function __construct(string $currentFolder, array $options = [])
    {
        $this->currentFolder = $currentFolder;
        
        // Default options
        $defaultOptions = [
            'temp_dir' => DIRECTORY . '/cache/latte',
            'auto_refresh' => true,
            'strict_types' => false,
            'strict_parameters' => false,
            'content_type' => 'html',
            'debug' => Env::get('APP_ENV') === 'dev',
            'extension_methods' => [],
            'provider_callbacks' => [],
            'filters' => []
        ];
        
        // Merge provided options with defaults
        $this->options = array_merge($defaultOptions, $options);
        
        if (!class_exists('Latte\Engine')) {
            throw new \ErrorException(
                "Latte is not installed. Please install it using Composer: \n" .
                "composer require latte/latte",
                0,
                E_USER_ERROR
            );
        }
        
        $this->latte = new Engine();
        
        // Configure Latte with our options
        $this->latte->setTempDirectory($this->options['temp_dir']);
        $this->latte->setAutoRefresh($this->options['auto_refresh']);
        $this->latte->setStrictTypes($this->options['strict_types']);
        $this->latte->setStrictParsing($this->options['strict_parameters']);
        $this->latte->setContentType($this->options['content_type']);
        
        // Apply debug mode
        if ($this->options['debug']) {
            $this->latte->addExtension(new \Latte\Bridges\Tracy\TracyExtension);
        }
        
        // Register custom extension methods
        if (!empty($this->options['extension_methods'])) {
            foreach ($this->options['extension_methods'] as $name => $callback) {
                $this->latte->addExtension($callback);
            }
        }
        
        // Register provider callbacks
        if (!empty($this->options['provider_callbacks'])) {
            foreach ($this->options['provider_callbacks'] as $name => $callback) {
                $this->latte->addProvider($name, $callback);
            }
        }
        
        // Register filters
        if (!empty($this->options['filters'])) {
            foreach ($this->options['filters'] as $name => $callback) {
                $this->latte->addFilter($name, $callback);
            }
        }
        
        global $latte;
        $latte = $this->latte;
        
        // Automatically register all helpers
        $this->registerHelpers();
    }
    
    /**
     * Automatically registers all helpers in the Latte environment
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
                $this->latte->addFunction('render_component', function ($component, $params = []) {
                    // Add 'latte' as the extension argument
                    return render_component($component, $params, 'latte');
                });
            } else {
                $this->latte->addFunction($function, $function);
            }
        }
    }

    public function process(string $view, array $data = [], string $layout = 'default'): void
    {
        extract($data, EXTR_SKIP);
        $viewFile = $this->currentFolder . "/$view.view.latte";
        $layoutFile = Finder::findLayout($layout, 'latte');

        if (!file_exists($viewFile)) {
            throw new \ErrorException("View file not found: $viewFile", 0, E_USER_ERROR);
        }

        if (!$layoutFile) {
            throw new \ErrorException("Layout not found: $layout", 0, E_USER_ERROR);
        }
        
        // Run before layout hooks
        Hooks::run("before_layout", $layout, $viewFile, $layoutFile, $data);
        Hooks::run("before_layout_" . $layout, $viewFile, $layoutFile, $data);
        
        // Run before view hooks
        Hooks::run("before_view", $view, $viewFile, $layout, $data);
        Hooks::run("before_view_" . $view, $viewFile, $layout, $data);
        
        // Render view with data-view attribute
        ob_start();
        echo "<div data-view=\"$view\">";
        $this->latte->render($viewFile, $data);
        echo "</div>";
        $_view = ob_get_clean();
        
        // Run after view hooks
        Hooks::run("after_view", $view, $viewFile, $layout, $data);
        Hooks::run("after_view_" . $view, $viewFile, $layout, $data);
        
        // Add rendered view to data for layout as html
        $data['_view'] = new \Latte\Runtime\Html($_view);
        
        // Render layout with view embedded
        $this->latte->render($layoutFile, $data);
        
        // Run after layout hooks
        Hooks::run("after_layout", $layout, $viewFile, $layoutFile, $data);
        Hooks::run("after_layout_" . $layout, $viewFile, $layoutFile, $data);
    }
}