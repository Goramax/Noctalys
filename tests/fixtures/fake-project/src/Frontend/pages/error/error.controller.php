<?php

use Noctalys\Framework\View\View;

class ErrorController
{
    public function main($code, $message)
    {
        $error = http_response_code();
        if ($error == 404) {
            View::render("404");
        } else {
            View::render("default", ["code" => $code, "message" => $message]);
        }
    }
}
