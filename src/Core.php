<?php

namespace Goramax\NoctalysFramework;

use Goramax\NoctalysFramework\Router;

class Core {
    public function run() {
        ob_start();
        Router::dispatch();
        $output = ob_get_clean();
        echo $output;
    }
}