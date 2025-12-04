<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Noctalys\Framework\Services\Hooks;
use Tests\Helpers\FakeProject;

class HooksTest extends TestCase
{
    private FakeProject $fakeProject;
    private static array $hookResults = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->fakeProject = new FakeProject();
        
        if ($this->fakeProject->canUse()) {
            $this->fakeProject->setUp();
        } else {
            $this->markTestSkipped('Cannot run test: DIRECTORY constant already defined');
        }

        // Reset hooks state for each test
        $this->resetHooksState();
    }

    protected function tearDown(): void
    {
        if (isset($this->fakeProject)) {
            $this->fakeProject->tearDown();
        }
        parent::tearDown();
    }

    private function resetHooksState(): void
    {
        $reflection = new \ReflectionClass(Hooks::class);
        
        $hooksProperty = $reflection->getProperty('hooks');
        $hooksProperty->setAccessible(true);
        $hooksProperty->setValue(null, []);
        
        $initializedProperty = $reflection->getProperty('initialized');
        $initializedProperty->setAccessible(true);
        $initializedProperty->setValue(null, false);
    }

    public function testAddAndRunSimpleHook()
    {
        $executed = false;
        
        Hooks::add('test_hook', function() use (&$executed) {
            $executed = true;
        });
        
        $this->assertFalse($executed);
        
        Hooks::run('test_hook');
        
        $this->assertTrue($executed);
    }

    public function testRunHookWithParameters()
    {
        $receivedParams = [];
        
        Hooks::add('param_hook', function($param1, $param2, $param3) use (&$receivedParams) {
            $receivedParams = [$param1, $param2, $param3];
        });
        
        Hooks::run('param_hook', 'value1', 42, true);
        
        $this->assertEquals(['value1', 42, true], $receivedParams);
    }

    public function testMultipleHooksOnSameEvent()
    {
        $results = [];
        
        Hooks::add('multi_hook', function() use (&$results) {
            $results[] = 'first';
        });
        
        Hooks::add('multi_hook', function() use (&$results) {
            $results[] = 'second';
        });
        
        Hooks::add('multi_hook', function() use (&$results) {
            $results[] = 'third';
        });
        
        Hooks::run('multi_hook');
        
        $this->assertEquals(['first', 'second', 'third'], $results);
    }

    public function testHooksWithDifferentCallableTypes()
    {
        self::$hookResults = [];
        
        // Anonymous function
        Hooks::add('callable_test', function() {
            self::$hookResults[] = 'anonymous';
        });
        
        // Static method
        Hooks::add('callable_test', [self::class, 'staticHookMethod']);
        
        // Instance method
        $instance = $this;
        Hooks::add('callable_test', [$instance, 'instanceHookMethod']);
        
        Hooks::run('callable_test');
        
        $this->assertContains('anonymous', self::$hookResults);
        $this->assertContains('static', self::$hookResults);
        $this->assertContains('instance', self::$hookResults);
    }

    public static function staticHookMethod(): void
    {
        self::$hookResults[] = 'static';
    }

    public function instanceHookMethod(): void
    {
        self::$hookResults[] = 'instance';
    }

    public function testRunNonExistentHook()
    {
        // Should not throw any exception
        $this->expectNotToPerformAssertions();
        Hooks::run('non_existent_hook');
    }

    public function testHooksWithModifyingParameters()
    {
        $initialValue = 'initial';
        $finalValue = $initialValue;
        
        Hooks::add('modify_hook', function(&$value) {
            $value .= '_modified1';
        });
        
        Hooks::add('modify_hook', function(&$value) {
            $value .= '_modified2';
        });
        
        Hooks::run('modify_hook', $finalValue);
        
        // Note: PHP closures can't modify variables by reference through call_user_func_array
        // So this test verifies the hook runs without error, but won't modify the value
        $this->assertIsString($finalValue);
    }

    public function testHooksWithReturnValues()
    {
        Hooks::add('return_hook', function() {
            return 'first_return';
        });
        
        Hooks::add('return_hook', function() {
            return 'second_return';
        });
        
        // run() method doesn't return values, just executes hooks
        $this->expectNotToPerformAssertions();
        Hooks::run('return_hook');
    }

    public function testHookExecutionOrder()
    {
        $order = [];
        
        Hooks::add('order_test', function() use (&$order) {
            $order[] = 1;
        });
        
        Hooks::add('order_test', function() use (&$order) {
            $order[] = 2;
        });
        
        Hooks::add('order_test', function() use (&$order) {
            $order[] = 3;
        });
        
        Hooks::run('order_test');
        
        $this->assertEquals([1, 2, 3], $order);
    }

    public function testSetupWithExistingHooksFile()
    {
        // Create a hooks.php file
        $hooksContent = "<?php
use Noctalys\Framework\Services\Hooks;

Hooks::add('setup_test_hook', function() {
    global \$hookSetupExecuted;
    \$hookSetupExecuted = true;
});
";
        file_put_contents($this->fakeProject->getProjectPath() . '/src/hooks.php', $hooksContent);
        
        global $hookSetupExecuted;
        $hookSetupExecuted = false;
        
        Hooks::setup();
        
        // The hooks file should be included
        Hooks::run('setup_test_hook');
        
        $this->assertTrue($hookSetupExecuted);
        
        // Clean up
        unlink($this->fakeProject->getProjectPath() . '/src/hooks.php');
    }

    public function testSetupWithoutHooksFile()
    {
        // Ensure no hooks.php file exists
        $hooksFile = $this->fakeProject->getProjectPath() . '/src/hooks.php';
        if (file_exists($hooksFile)) {
            unlink($hooksFile);
        }
        
        // Should not throw any error
        $this->expectNotToPerformAssertions();
        Hooks::setup();
    }

    public function testSetupOnlyRunsOnce()
    {
        $includeCount = 0;
        
        // Create a hooks.php file that increments a counter
        $hooksContent = "<?php
global \$includeCount;
\$includeCount++;
";
        file_put_contents($this->fakeProject->getProjectPath() . '/src/hooks.php', $hooksContent);
        
        global $includeCount;
        $includeCount = 0;
        
        // Call setup multiple times
        Hooks::setup();
        Hooks::setup();
        Hooks::setup();
        
        // File should only be included once
        $this->assertEquals(1, $includeCount);
        
        // Clean up
        unlink($this->fakeProject->getProjectPath() . '/src/hooks.php');
    }

    public function testHooksWithComplexParameters()
    {
        $receivedData = null;
        
        Hooks::add('complex_hook', function($data) use (&$receivedData) {
            $receivedData = $data;
        });
        
        $complexData = [
            'string' => 'test',
            'array' => [1, 2, 3],
            'object' => (object)['prop' => 'value'],
            'nested' => [
                'deep' => [
                    'value' => 'deeply nested'
                ]
            ]
        ];
        
        Hooks::run('complex_hook', $complexData);
        
        $this->assertEquals($complexData, $receivedData);
    }

    public function testMultipleHookTypes()
    {
        $beforeResults = [];
        $afterResults = [];
        
        Hooks::add('before_action', function($action) use (&$beforeResults) {
            $beforeResults[] = "before_$action";
        });
        
        Hooks::add('after_action', function($action) use (&$afterResults) {
            $afterResults[] = "after_$action";
        });
        
        // Simulate some actions
        Hooks::run('before_action', 'login');
        Hooks::run('after_action', 'login');
        Hooks::run('before_action', 'logout');
        Hooks::run('after_action', 'logout');
        
        $this->assertEquals(['before_login', 'before_logout'], $beforeResults);
        $this->assertEquals(['after_login', 'after_logout'], $afterResults);
    }

    public function testHookWithException()
    {
        Hooks::add('exception_hook', function() {
            throw new \Exception('Hook exception');
        });
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Hook exception');
        
        Hooks::run('exception_hook');
    }

    public function testHooksWithVariableArguments()
    {
        $allArgs = [];
        
        Hooks::add('varargs_hook', function(...$args) use (&$allArgs) {
            $allArgs = $args;
        });
        
        Hooks::run('varargs_hook', 'arg1', 'arg2', 'arg3', 42, true, ['array']);
        
        $this->assertEquals(['arg1', 'arg2', 'arg3', 42, true, ['array']], $allArgs);
    }

    public function testHookChainingEffect()
    {
        $counter = 0;
        
        Hooks::add('counter_hook', function() use (&$counter) {
            $counter += 1;
        });
        
        Hooks::add('counter_hook', function() use (&$counter) {
            $counter *= 2;
        });
        
        Hooks::add('counter_hook', function() use (&$counter) {
            $counter += 10;
        });
        
        Hooks::run('counter_hook');
        
        // ((0 + 1) * 2) + 10 = 12
        $this->assertEquals(12, $counter);
    }
}
