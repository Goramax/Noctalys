<?php

namespace Goramax\NoctalysFramework\TemplateEngines;

use Goramax\NoctalysFramework\TemplateEngines\TemplateEngineInterface;
use Goramax\NoctalysFramework\Router;
use Goramax\NoctalysFramework\Finder;
use Goramax\NoctalysFramework\Hooks;
use Latte\Engine;

class LatteEngine implements TemplateEngineInterface
{
    private Engine $latte;
    private string $currentFolder;
    private array $options;

    public function __construct(string $currentFolder, array $options = [])
    {
        $this->currentFolder = $currentFolder;
        $this->options = $options;
        
        $this->latte = new Engine();
        $this->latte->setTempDirectory($this->options['temp_dir'] ?? DIRECTORY . '/cache/latte');
        
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
        Hooks::run("before_view", $view, $viewFile, $layout, $data);
        $layoutFile = Finder::findLayout($layout, 'latte');

        if (!file_exists($viewFile)) {
            throw new \Exception("View file not found: $viewFile");
        }

        if (!$layoutFile) {
            throw new \Exception("Layout not found: $layout");
        }
        
        // Render view with data-view attribute
        ob_start();
        echo "<div data-view=\"$view\">";
        $this->latte->render($viewFile, $data);
        echo "</div>";
        $_view = ob_get_clean();
        
        // Add rendered view to data for layout as html
        $data['_view'] = new \Latte\Runtime\Html($_view);
        
        // Render layout with view embedded
        $this->latte->render($layoutFile, $data);
        
        Hooks::run("after_view", $view, $viewFile, $layout, $data);
    }
}