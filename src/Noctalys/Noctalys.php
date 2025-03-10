<?php

namespace Goramax\NoctalysFramework;

use Goramax\NoctalysFramework\Core;

class Noctalys {
    protected $core;

    public function __construct() {
        $this->core = new Core();
    }

    public function run() {
        $this->core->run();
    }
}