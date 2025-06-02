<?php

namespace Goramax\NoctalysFramework\Core;
use Goramax\NoctalysFramework\Services\Env;
use Goramax\NoctalysFramework\Services\Hooks;
use Goramax\NoctalysFramework\Routing\Router;
use Goramax\NoctalysFramework\Routing\RouterApi;

class Bootstrap
{
    public function run()
    {
        $start = microtime(true);
        $this->ini();
        $this->applyPhPIni();
        $this->routersDispatch($start);
        session_write_close();
    }

    private function ini()
    {
        session_start();
        define("DIRECTORY", getcwd());
        require_once __DIR__ . '/../Helpers/Helpers.php';
        set_exception_handler([ErrorHandler::class, 'handleException']);
        set_error_handler([ErrorHandler::class, 'handleException']);
        Env::load();
        Hooks::setup();
        if (Env::get('APP_ENV') === 'dev') {
            Hooks::add('after_layout', function () {
                echo '<script type="module" src="http://localhost:5173/@vite/client"></script>';
            });
        }
    }

    private function routersDispatch($start)
    {
        $currentRoute = $_SERVER['REQUEST_URI'];
        $apiUrl = Config::get('api')['api_url'];
        $is_api_url = substr($currentRoute, 0, strlen($apiUrl)) === $apiUrl;

        if (Config::get('api')['enabled'] && $is_api_url) {
            RouterApi::registerControllers(DIRECTORY . DIRECTORY_SEPARATOR . Config::get('api')['controllers_location']);
            RouterApi::dispatch();
        }
        if (Config::get('router')['enabled'] && !$is_api_url) {
            ob_start();
            Router::dispatch();
            $output = ob_get_clean();
            echo $output;
            $end = microtime(true);
            echo "<span class='durationdebug'>" . round(($end - $start) * 1000, 2) . " ms</span>";
        }
    }
    private function applyPhPIni()
    {
        if (Config::get('app')['debug']) {
            ini_set('display_errors', 1);
            ini_set('error_reporting', E_ALL);
        } else {
            ini_set('display_errors', 0);
            ini_set('error_reporting', 0);
        }
        if (Config::get('app')['timezone'] && Config::get('app')['timezone'] !== 'auto') {
            date_default_timezone_set(Config::get('app')['timezone']);
        }
    }
}
