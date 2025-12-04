<?php

use PHPUnit\Framework\TestCase;
use Goramax\NoctalysFramework\Core\Bootstrap;
use Tests\Helpers\FakeProject;
use PHPUnit\Framework\Error\Warning;

class BootstrapTest extends TestCase
{
    private FakeProject $fakeProject;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->fakeProject = new FakeProject();
        
        if ($this->fakeProject->canUse()) {
            $this->fakeProject->setUp();
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
    public function testBootstrapCanBeInstantiated()
    {
        // Test if Bootstrap can be instantiated
        $bootstrap = new Bootstrap();

        $this->assertInstanceOf(Bootstrap::class, $bootstrap);
    }

    public function testBootstrapRunMethodExists()
    {
        // Test if the run method exists and can be called
        $bootstrap = new Bootstrap();
        // Optionally verify it's callable too
        $this->assertTrue(is_callable([$bootstrap, 'run']));
    }

    public function testBootstrapRunDoesNotThrowException()
    {
        // Clean any existing output buffers and start fresh
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Capture output since Bootstrap->run() might trigger router output
        ob_start();
        
        $bootstrap = new Bootstrap();
        $bootstrap->run();
        
        // Capture and discard the output to prevent console pollution
        $output = ob_get_clean();
        
        // If we reach here, no fatal exception occurred
        $this->assertTrue(true, 'Bootstrap->run() executed without throwing exceptions');
        
        // Optionally, we can assert that some output was generated (indicating bootstrap worked)
        $this->assertNotEmpty($output, 'Bootstrap should generate some output');
    }
}
