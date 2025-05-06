<?php

namespace Goramax\NoctalysFramework\TemplateEngines;

use Goramax\NoctalysFramework\TemplateEngines\TemplateEngineInterface;
use Goramax\NoctalysFramework\Finder;
use Goramax\NoctalysFramework\Hooks;
use Goramax\NoctalysFramework\Env;
use Goramax\NoctalysFramework\ErrorHandler;
use Smarty\Smarty;

class SmartyEngine implements TemplateEngineInterface
{
    private Smarty $smarty;
    private string $currentFolder;
    private array $options;

    public function __construct(string $currentFolder, array $options = [])
    {
        $this->currentFolder = $currentFolder;
        
        // Default options
        $defaultOptions = [
            'template_dir' => DIRECTORY . '/src/Frontend/pages',
            'compile_dir' => DIRECTORY . '/cache/smarty/templates_c',
            'cache_dir' => DIRECTORY . '/cache/smarty/cache',
            'config_dir' => DIRECTORY . '/cache/smarty/configs',
            'caching' => false,
            'cache_lifetime' => 120,
            'debug' => Env::get('APP_ENV') === 'dev',
            'left_delimiter' => '{',
            'right_delimiter' => '}',
            'escape_html' => false,
            'force_compile' => Env::get('APP_ENV') === 'dev',
            'auto_literal' => true
        ];
        
        // Merge provided options with defaults
        $this->options = array_merge($defaultOptions, $options);
        
        if (!class_exists('Smarty\Smarty')) {
            ErrorHandler::fatal(
                "Smarty is not installed. Please install it using Composer: \n" .
                "composer require smarty/smarty"
            );
        }
        
        $this->smarty = new Smarty();
        
        // Apply all options to Smarty instance
        $this->smarty->setTemplateDir($this->options['template_dir']);
        $this->smarty->setCompileDir($this->options['compile_dir']);
        $this->smarty->setCacheDir($this->options['cache_dir']);
        $this->smarty->setConfigDir($this->options['config_dir']);
        $this->smarty->setCaching($this->options['caching']);
        $this->smarty->setCacheLifetime($this->options['cache_lifetime']);
        $this->smarty->setDebugging($this->options['debug']);
        $this->smarty->setLeftDelimiter($this->options['left_delimiter']);
        $this->smarty->setRightDelimiter($this->options['right_delimiter']);
        $this->smarty->setEscapeHtml($this->options['escape_html']);
        $this->smarty->setForceCompile($this->options['force_compile']);
        $this->smarty->setAutoLiteral($this->options['auto_literal']);
        
        // Make Smarty instance globally accessible
        global $smarty;
        $smarty = $this->smarty;
        
        // Automatically register all helpers
        $this->registerHelpers();
    }
    
    /**
     * Automatically registers all helpers in the Smarty environment
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
                $this->smarty->registerPlugin('function', 'render_component', function ($params, $smarty) {
                    $component = $params['component'] ?? null;
                    unset($params['component']);
                    // Add 'smarty' as the extension argument
                    return render_component($component, $params, 'tpl');
                });
            } 
            else {
                $this->smarty->registerPlugin('function', $function, function($params, $smarty) use ($function) {
                    // Try to intelligently extract arguments based on reflection
                    try {
                        $reflection = new \ReflectionFunction($function);
                        $args = [];
                        
                        foreach ($reflection->getParameters() as $param) {
                            $paramName = $param->getName();
                            if (isset($params[$paramName])) {
                                $args[] = $params[$paramName];
                                unset($params[$paramName]);
                            } else if ($param->isOptional()) {
                                $args[] = $param->getDefaultValue();
                            } else {
                                // Required parameter not provided
                                return "Error: Missing required parameter '$paramName' for function '$function'";
                            }
                        }
                        
                        // Add remaining params as the last argument if the function accepts variable args
                        if (!empty($params) && $reflection->isVariadic()) {
                            $args[] = $params;
                        }
                        
                        return call_user_func_array($function, $args);
                    } catch (\Exception $e) {
                        // Fallback to direct function call if reflection fails
                        return call_user_func($function, $params);
                    }
                });
            }
        }
    }

    public function process(string $view, array $data = [], string $layout = 'default'): void
    {
        extract($data, EXTR_SKIP);
        $viewFile = $this->currentFolder . "/$view.view.tpl";
        $layoutFile = Finder::findLayout($layout, 'tpl');

        if (!file_exists($viewFile)) {
            ErrorHandler::fatal("View file not found: $viewFile");
        }

        if (!$layoutFile) {
            ErrorHandler::fatal("Layout not found: $layout");
        }
        
        // Run before layout hooks
        Hooks::run("before_layout", $layout, $viewFile, $layoutFile, $data);
        Hooks::run("before_layout_" . $layout, $viewFile, $layoutFile, $data);

        // Run before view hooks
        Hooks::run("before_view", $view, $viewFile, $layout, $data);
        Hooks::run("before_view_" . $view, $viewFile, $layout, $data);

        // Assign data to Smarty
        foreach ($data as $key => $value) {
            $this->smarty->assign($key, $value);
        }
        
        // Render view with data-view attribute
        ob_start();
        echo "<div data-view=\"$view\">";
        echo $this->smarty->fetch($viewFile);
        echo "</div>";
        $_view = ob_get_clean();
        
        // Run after view hooks
        Hooks::run("after_view", $view, $viewFile, $layout, $data);
        Hooks::run("after_view_" . $view, $viewFile, $layout, $data);
        
        // Assign the rendered view to be used in the layout
        $this->smarty->assign('_view', $_view);
        
        // Render layout with view embedded
        echo $this->smarty->fetch($layoutFile);
        
        // Run after layout hooks
        Hooks::run("after_layout", $layout, $viewFile, $layoutFile, $data);
        Hooks::run("after_layout_" . $layout, $viewFile, $layoutFile, $data);
    }
}