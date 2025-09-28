<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Goramax\NoctalysFramework\Routing\Router;
use Goramax\NoctalysFramework\Core\Config;
use Tests\Helpers\FakeProject;

class RouterTest extends TestCase
{
    private FakeProject $fakeProject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeProject = new FakeProject();

        if ($this->fakeProject->canUse()) {
            $this->fakeProject->setUp();
            $_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/test';
            // Initialize Config which is required by Router
            Config::init();
        } else {
            $this->markTestSkipped('Cannot run test: DIRECTORY constant already defined');
        }
    }

    protected function tearDown(): void
    {
        if (isset($this->fakeProject)) {
            $this->fakeProject->tearDown();
        }
        parent::tearDown();
    }

    public function testRouterCanBeInstantiated()
    {
        $router = new Router();
        $this->assertInstanceOf(Router::class, $router);
    }

    public function testRouterDispatchMethodExists()
    {
        $router = new Router();
        $this->assertTrue(is_callable([$router, 'dispatch']));
    }

    public function testRouterDispatchSuccess()
    {
        // Setup HTTP request headers
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
        
        // Capture output since Router::dispatch() echoes directly
        ob_start();
        Router::dispatch();
        $output = ob_get_clean();

        // Test successful routing: should generate HTML content
        $this->assertStringContainsString('<html', $output);
        $this->assertStringContainsString('Noctalys', $output);
        $this->assertNotEmpty($output);
        
        // Test that no error code was set (successful routes don't set HTTP codes)
        $this->assertFalse(http_response_code(), 'No HTTP error code should be set for successful routes');
    }

    public function testRouterDispatchNotFound()
    {
        // Setup HTTP request headers for a non-existent route
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/test-404-error';

        // Capture output since Router::dispatch() echoes directly
        ob_start();
        Router::dispatch();
        $output = ob_get_clean();

        // Test that 404 error code was set
        $this->assertEquals(404, http_response_code(), 'HTTP response code should be 404 for non-existent routes');

        // Test that output contains 404 message
        $this->assertStringContainsString('404', $output);
    }

    public function testRouterCustomError()
    {
        // Setup HTTP request headers for a custom error route
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';

        // Capture output since Router::dispatch() echoes directly
        ob_start();
        Router::error(500);
        $output = ob_get_clean();

        // Test that 500 error code was set
        $this->assertEquals(500, http_response_code(), 'HTTP response code should be 500 for custom errors');
    }
}