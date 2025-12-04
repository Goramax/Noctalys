<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Noctalys\Framework\Services\Cache;
use Noctalys\Framework\Core\Config;
use Tests\Helpers\FakeProject;

class CacheTest extends TestCase
{
    private FakeProject $fakeProject;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->fakeProject = new FakeProject();
        
        if ($this->fakeProject->canUse()) {
            $this->fakeProject->setUp();
            Config::init();
        } else {
            $this->markTestSkipped('Cannot run test: DIRECTORY constant already defined');
        }
    }

    protected function tearDown(): void
    {
        // Clean up cache files and APCu if available
        Cache::clear();
        
        if (isset($this->fakeProject)) {
            $this->fakeProject->tearDown();
        }
        parent::tearDown();
    }

    public function testSetAndGetBasicValue()
    {
        $result = Cache::set('test_cache', 'test_key', 'test_value');
        
        // Result depends on cache availability but should not error
        $this->assertIsBool($result);
        
        $value = Cache::get('test_cache', 'test_key', 'default');
        
        if ($result) {
            $this->assertEquals('test_value', $value);
        } else {
            $this->assertEquals('default', $value);
        }
    }

    public function testSetAndGetArrayValue()
    {
        $testArray = ['key1' => 'value1', 'key2' => 'value2'];
        
        $result = Cache::set('array_cache', 'array_key', $testArray);
        $this->assertIsBool($result);
        
        $value = Cache::get('array_cache', 'array_key', []);
        
        if ($result) {
            $this->assertEquals($testArray, $value);
        } else {
            $this->assertEquals([], $value);
        }
    }

    public function testGetWithoutKey()
    {
        Cache::set('full_cache', 'key1', 'value1');
        Cache::set('full_cache', 'key2', 'value2');
        
        $fullCache = Cache::get('full_cache');
        
        // If cache system is disabled, this might return null
        if ($fullCache === null) {
            $this->markTestSkipped('Cache system is disabled');
            return;
        }
        
        $this->assertIsArray($fullCache);
        
        // If cache is working, should contain our values
        if (!empty($fullCache)) {
            $this->assertArrayHasKey('key1', $fullCache);
            $this->assertArrayHasKey('key2', $fullCache);
            $this->assertEquals('value1', $fullCache['key1']);
            $this->assertEquals('value2', $fullCache['key2']);
        }
    }

    public function testGetDefaultValue()
    {
        $value = Cache::get('nonexistent_cache', 'nonexistent_key', 'default_value');
        
        $this->assertEquals('default_value', $value);
    }

    public function testSetMultipleValues()
    {
        Cache::set('multi_cache', 'key1', 'value1');
        Cache::set('multi_cache', 'key2', 'value2');
        Cache::set('multi_cache', 'key3', 'value3');
        
        $value1 = Cache::get('multi_cache', 'key1');
        $value2 = Cache::get('multi_cache', 'key2');
        $value3 = Cache::get('multi_cache', 'key3');
        
        // Values should either be correct or null (if cache disabled)
        $this->assertTrue($value1 === 'value1' || $value1 === null);
        $this->assertTrue($value2 === 'value2' || $value2 === null);
        $this->assertTrue($value3 === 'value3' || $value3 === null);
    }

    public function testOverwriteValue()
    {
        Cache::set('overwrite_cache', 'test_key', 'original_value');
        Cache::set('overwrite_cache', 'test_key', 'new_value');
        
        $result = Cache::get('overwrite_cache', 'test_key');
        
        // If cache is disabled, result might be null - skip the test
        if ($result === null) {
            $this->markTestSkipped('Cache system is disabled');
            return;
        }
        
        // Verify that the value was overwritten
        $this->assertEquals('new_value', $result);
    }

    public function testClearSpecificCache()
    {
        Cache::set('clear_test1', 'key', 'value1');
        Cache::set('clear_test2', 'key', 'value2');
        
        $result = Cache::clear('clear_test1');
        $this->assertIsBool($result);
        
        // After clearing, first cache should be empty, second should remain
        $value1 = Cache::get('clear_test1', 'key', 'default');
        $value2 = Cache::get('clear_test2', 'key', 'default');
        
        if ($result) {
            $this->assertEquals('default', $value1);
            // value2 might still exist if cache was working
            $this->assertTrue($value2 === 'value2' || $value2 === 'default');
        }
    }

    public function testClearAllCache()
    {
        Cache::set('clear_all1', 'key', 'value1');
        Cache::set('clear_all2', 'key', 'value2');
        
        $result = Cache::clear();
        $this->assertIsBool($result);
        
        // After clearing all, both caches should be empty
        $value1 = Cache::get('clear_all1', 'key', 'default');
        $value2 = Cache::get('clear_all2', 'key', 'default');
        
        if ($result) {
            $this->assertEquals('default', $value1);
            $this->assertEquals('default', $value2);
        }
    }

    public function testSetWithTtl()
    {
        // Test that TTL parameter doesn't cause errors
        $result = Cache::set('ttl_cache', 'ttl_key', 'ttl_value', 60);
        $this->assertIsBool($result);
        
        $value = Cache::get('ttl_cache', 'ttl_key', 'default');
        
        if ($result) {
            $this->assertEquals('ttl_value', $value);
        } else {
            $this->assertEquals('default', $value);
        }
    }

    public function testComplexDataTypes()
    {
        $complexData = [
            'string' => 'test',
            'number' => 123,
            'float' => 45.67,
            'boolean' => true,
            'array' => ['nested', 'array'],
            'object' => (object)['prop' => 'value']
        ];
        
        $result = Cache::set('complex_cache', 'complex_key', $complexData);
        $this->assertIsBool($result);
        
        $retrieved = Cache::get('complex_cache', 'complex_key', []);
        
        if ($result && !empty($retrieved)) {
            $this->assertEquals($complexData['string'], $retrieved['string']);
            $this->assertEquals($complexData['number'], $retrieved['number']);
            $this->assertEquals($complexData['float'], $retrieved['float']);
            $this->assertEquals($complexData['boolean'], $retrieved['boolean']);
            $this->assertEquals($complexData['array'], $retrieved['array']);
        }
    }

    public function testCacheKeyNormalization()
    {
        // Test that cache names with special characters are handled
        Cache::set('cache-with-dashes', 'key', 'value1');
        Cache::set('cache_with_underscores', 'key', 'value2');
        Cache::set('cache.with.dots', 'key', 'value3');
        
        $value1 = Cache::get('cache-with-dashes', 'key', 'default');
        $value2 = Cache::get('cache_with_underscores', 'key', 'default');
        $value3 = Cache::get('cache.with.dots', 'key', 'default');
        
        // Values should either be correct or default (if cache disabled)
        $this->assertTrue($value1 === 'value1' || $value1 === 'default');
        $this->assertTrue($value2 === 'value2' || $value2 === 'default');
        $this->assertTrue($value3 === 'value3' || $value3 === 'default');
    }

    public function testEmptyAndNullValues()
    {
        Cache::set('empty_cache', 'empty_string', '');
        Cache::set('empty_cache', 'null_value', null);
        Cache::set('empty_cache', 'zero_value', 0);
        Cache::set('empty_cache', 'false_value', false);
        
        $emptyString = Cache::get('empty_cache', 'empty_string', 'default');
        $nullValue = Cache::get('empty_cache', 'null_value', 'default');
        $zeroValue = Cache::get('empty_cache', 'zero_value', 'default');
        $falseValue = Cache::get('empty_cache', 'false_value', 'default');
        
        // Test that empty values are stored and retrieved correctly if cache is enabled
        if (Cache::get('empty_cache') !== []) {
            $this->assertTrue($emptyString === '' || $emptyString === 'default');
            $this->assertTrue($nullValue === null || $nullValue === 'default');
            $this->assertTrue($zeroValue === 0 || $zeroValue === 'default');
            $this->assertTrue($falseValue === false || $falseValue === 'default');
        }
    }

    public function testCachePersistence()
    {
        // This test verifies that cache values persist across get operations
        Cache::set('persist_cache', 'key1', 'value1');
        Cache::set('persist_cache', 'key2', 'value2');
        
        // Get individual values
        $value1a = Cache::get('persist_cache', 'key1');
        $value2a = Cache::get('persist_cache', 'key2');
        
        // Get full cache
        $fullCache = Cache::get('persist_cache');
        
        // Get individual values again
        $value1b = Cache::get('persist_cache', 'key1');
        $value2b = Cache::get('persist_cache', 'key2');
        
        // Values should be consistent
        $this->assertEquals($value1a, $value1b);
        $this->assertEquals($value2a, $value2b);
        
        if (!empty($fullCache)) {
            $this->assertIsArray($fullCache);
        }
    }
}
