<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Noctalys\Framework\Core\Config;
use Tests\Helpers\FakeProject;

class ConfigTest extends TestCase
{
    private FakeProject $fakeProject;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->fakeProject = new FakeProject();
        
        if ($this->fakeProject->canUse()) {
            $this->fakeProject->setUp();
            Config::init();
            $_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/test';
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
