<?php

namespace Goramax\NoctalysFramework\Core;

class Noctalys {
    protected $core;

    public function __construct() {
        $this->core = new Bootstrap();
    }

    public function run() {
        $this->core->run();
    }
}