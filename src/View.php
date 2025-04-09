<?php

namespace Goramax\NoctalysFramework;

use Goramax\NoctalysFramework\Router;
use Goramax\NoctalysFramework\Finder;
use Goramax\NoctalysFramework\Hooks;

class View
{
    private static bool $rendered = false;

    public static function render(string $view, array $data = [], string $layout = 'default'): void
    {
        if (self::$rendered) return;
        $viewFile = Router::getCurrentFolder() . "/$view.view.php";
        extract($data);

        Hooks::run("before_view", $view, $viewFile, $layout, $data);
        $layoutFile = Finder::findLayout($layout);

        ob_start();
        echo "<div data-view=\"$view\">";
        require $viewFile;
        echo "</div>";
        $_view = ob_get_clean();
        require $layoutFile;

        self::$rendered = true;
        Hooks::run("after_view", $view, $viewFile, $layout, $data);
    }
}
