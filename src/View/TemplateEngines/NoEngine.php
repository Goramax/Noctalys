<?php
namespace Noctalys\Framework\View\TemplateEngines;

use Noctalys\Framework\Routing\Router;
use Noctalys\Framework\Utils\Finder;
use Noctalys\Framework\Services\Hooks;

class NoEngine implements TemplateEngineInterface
{
    public function process(string $view, array $data = [], string $layout = 'default'): void
    {
        extract($data, EXTR_SKIP);
        $viewFile = Router::getCurrentFolder() . "/$view.view.php";
        $layoutFile = Finder::findLayout($layout, 'php');

        Hooks::run("before_layout", $layout, $viewFile, $layoutFile, $data);
        Hooks::run("before_layout_" . $layout, $viewFile, $layoutFile, $data);

        ob_start();
        Hooks::run("before_view", $view, $viewFile, $layout, $data);
        Hooks::run("before_view_" . $view, $viewFile, $layout, $data);
        echo "<div data-view=\"$view\">";
        require $viewFile;
        echo "</div>";
        $_view = ob_get_clean();
        Hooks::run("after_view", $view, $viewFile, $layout, $data);
        Hooks::run("after_view_" . $view, $viewFile, $layout, $data);
        require $layoutFile;
        Hooks::run("after_layout", $layout, $viewFile, $layoutFile, $data);
        Hooks::run("after_layout_" . $layout, $viewFile, $layoutFile, $data);
    }
}