<?php
namespace Goramax\NoctalysFramework\TemplateEngines;

use Goramax\NoctalysFramework\TemplateEngines\TemplateEngineInterface;
use Goramax\NoctalysFramework\Router;
use Goramax\NoctalysFramework\Finder;
use Goramax\NoctalysFramework\Hooks;

class NoEngine implements TemplateEngineInterface
{
    public function process(string $view, array $data = [], string $layout = 'default'): void
    {
        extract($data, EXTR_SKIP);
        $viewFile = Router::getCurrentFolder() . "/$view.view.php";
        Hooks::run("before_view", $view, $viewFile, $layout, $data);
        $layoutFile = Finder::findLayout($layout, 'php');

        ob_start();
        echo "<div data-view=\"$view\">";
        require $viewFile;
        echo "</div>";
        $_view = ob_get_clean();
        require $layoutFile;
        Hooks::run("after_view", $view, $viewFile, $layout, $data);
    }
}