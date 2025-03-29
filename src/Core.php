<?php

namespace Goramax\NoctalysFramework;

use Goramax\NoctalysFramework\Router;

class Core {
    public function run() {
        $this->ini();
        ob_start();
        Router::dispatch();
        $output = ob_get_clean();
        echo $output;
    }

    private function ini() {
        session_start();
        require_once __DIR__ . '/Helpers.php';
    }
}