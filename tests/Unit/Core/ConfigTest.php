<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Goramax\NoctalysFramework\Core\Config;

class ConfigTest extends TestCase
{
    private string $originalCwd;
    private string $fakeProjectPath;
    private bool $canRunTest = false;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->canRunTest = true;
        
        // Setup fake project environment
        $this->fakeProjectPath = __DIR__ . '/../../fixtures/fake-project';
        $this->originalCwd = getcwd();
        
        chdir($this->fakeProjectPath);
        define('DIRECTORY', $this->fakeProjectPath);
        Config::init();
        
        $_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/test';
    }
    
    protected function tearDown(): void
    {
        if ($this->canRunTest && isset($this->originalCwd)) {
            chdir($this->originalCwd);
        }
        parent::tearDown();
    }
    
    public function testConfigGetReturnsArrayForAppKey()
    {
        $appConfig = Config::get('app');
        
        $this->assertIsArray($appConfig);
        $this->assertArrayHasKey('name', $appConfig);
        $this->assertEquals('My Noctalys App', $appConfig['name']);
        $this->assertTrue($appConfig['debug']);
    }
    
    public function testConfigGetReturnsEmptyArrayForInvalidKey()
    {
        $result = Config::get('invalid_key_123');
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
    
    public function testConfigGetValidatesMultipleKeys()
    {   
        $keys = ['app', 'cache', 'router', 'api'];
        
        foreach ($keys as $key) {
            $result = Config::get($key);
            $this->assertIsArray($result, "Config::get('$key') should return array");
            $this->assertNotEmpty($result, "Config key '$key' should not be empty");
        }
    }
}
