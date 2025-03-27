<?php

namespace Goramax\NoctalysFramework;
use Goramax\NoctalysFramework\Router;
use Goramax\NoctalysFramework\Finder;

class View {
    private static bool $rendered = false;

    public static function render(string $view, array $data = [], string $layout = 'default'): void {
        if (self::$rendered) return;

        $view = Router::getCurrentFolder() . "/$view.view.php";

        extract($data);

        $layout = Finder::findLayout($layout);
        ob_start();
        require $view;
        $view = ob_get_clean();
        require $layout;
        self::$rendered = true;

    }
}