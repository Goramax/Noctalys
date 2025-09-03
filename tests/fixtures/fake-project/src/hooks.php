<?php
use Goramax\NoctalysFramework\Services\Hooks;

Hooks::add("test_hook", function() {
    echo "Hello from the test hook!";
});

Hooks::add("before_layout_test", function() {
    echo "<h2>Before layout hook</h2>";
});