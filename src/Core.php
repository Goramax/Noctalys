<?php

namespace Goramax\NoctalysFramework;

use Goramax\NoctalysFramework\Router;
use Goramax\NoctalysFramework\Env;

class Core {
    public function run() {
        $start = microtime(true);
        $this->ini();
        ob_start();
        Router::dispatch();
        $output = ob_get_clean();
        echo $output;
        $end = microtime(true);
        $executionTime = $end - $start;
        echo "<span class='durationdebug'>".round(($end - $start) * 1000, 2) ." ms</span>";
    }

    private function ini() {
        session_start();
        define("DIRECTORY", getcwd());
        require_once __DIR__ . '/Helpers/Helpers.php';
        Env::load();
    }
}