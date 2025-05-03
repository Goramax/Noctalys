<?php

namespace Goramax\NoctalysFramework\TemplateEngines;

use Goramax\NoctalysFramework\TemplateEngines\TemplateEngineInterface;
use Goramax\NoctalysFramework\Router;
use Goramax\NoctalysFramework\Finder;
use Goramax\NoctalysFramework\Hooks;
use Smarty;

class SmartyEngine implements TemplateEngineInterface
{
    private Smarty $smarty;
    private string $currentFolder;
    private array $options;

    public function __construct(string $currentFolder, array $options = [])
    {
        $this->currentFolder = $currentFolder;
        $this->options = $options;
        
        $this->smarty = new Smarty();
        $this->smarty->setTemplateDir($this->options['template_dir'] ?? DIRECTORY . '/src/Frontend/pages');
        $this->smarty->setCompileDir($this->options['compile_dir'] ?? DIRECTORY . '/cache/smarty/templates_c');
        $this->smarty->setCacheDir($this->options['cache_dir'] ?? DIRECTORY . '/cache/smarty/cache');
        $this->smarty->setConfigDir($this->options['config_dir'] ?? DIRECTORY . '/cache/smarty/configs');
    }

    public function process(string $view, array $data = [], string $layout = 'default'): void
    {
        extract($data, EXTR_SKIP);
        $viewFile = $this->currentFolder . "/$view.view.tpl";
        Hooks::run("before_view", $view, $viewFile, $layout, $data);
        $layoutFile = Finder::findLayout($layout, 'tpl');

        if (!file_exists($viewFile)) {
            throw new \Exception("View file not found: $viewFile");
        }

        if (!$layoutFile) {
            throw new \Exception("Layout not found: $layout");
        }

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
        
        // Assign the rendered view to be used in the layout
        $this->smarty->assign('_view', $_view);
        
        // Render layout with view embedded
        echo $this->smarty->fetch($layoutFile);
        
        Hooks::run("after_view", $view, $viewFile, $layout, $data);
    }
}