<?php

namespace Goramax\NoctalysFramework;

class View {
    private static bool $rendered = false;

    public static function render(string $view, array $data = [], string $layout = 'default'): void {
        if (self::$rendered) return;
        self::$rendered = true;

        extract($data);
        ob_start();
        // check if the layout file exists
        // if (!file_exists(getcwd() . "/src/Frontend/layouts/$layout.view.php")) {
        //     throw new \Exception("Layout file not found: $layout");
        // }
        // require getcwd() . "/src/Frontend/layouts/$layout.view.php"; //TODO Layouts
        $content = ob_get_clean();
        require getcwd() . "/src/Frontend/pages/$view/$view.view.php";

    }
}