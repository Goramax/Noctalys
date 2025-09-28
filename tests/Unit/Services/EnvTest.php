<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Goramax\NoctalysFramework\Services\Env;
use Tests\Helpers\FakeProject;

class EnvTest extends TestCase
{
    private FakeProject $fakeProject;
    private string $originalEnvContent = '';
    private bool $envFileExisted = false;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->fakeProject = new FakeProject();
        
        if ($this->fakeProject->canUse()) {
            $this->fakeProject->setUp();
            
            // Backup original .env file if it exists
            $envPath = $this->fakeProject->getProjectPath() . '/.env';
            if (file_exists($envPath)) {
                $this->originalEnvContent = file_get_contents($envPath);
                $this->envFileExisted = true;
            }
        } else {
            $this->markTestSkipped('Cannot run test: DIRECTORY constant already defined');
        }
    }

    protected function tearDown(): void
    {
        if (isset($this->fakeProject)) {
            // Restore original .env file or remove test file
            $envPath = $this->fakeProject->getProjectPath() . '/.env';
            if ($this->envFileExisted) {
                file_put_contents($envPath, $this->originalEnvContent);
            } else {
                if (file_exists($envPath)) {
                    unlink($envPath);
                }
            }
            
            // Clean up any test env files
            $testEnvFiles = [
                $this->fakeProject->getProjectPath() . '/.env.test',
                $this->fakeProject->getProjectPath() . '/.env.dev',
                $this->fakeProject->getProjectPath() . '/.env.local'
            ];
            
            foreach ($testEnvFiles as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
            
            $this->fakeProject->tearDown();
        }
        parent::tearDown();
    }

    private function createEnvFile(string $content, string $suffix = ''): void
    {
        $envPath = $this->fakeProject->getProjectPath() . '/.env' . $suffix;
        file_put_contents($envPath, $content);
    }

    public function testLoadBasicEnvironmentVariables()
    {
        $envContent = "
TEST_STRING=hello_world
TEST_NUMBER=123
TEST_BOOLEAN=true
";
        $this->createEnvFile($envContent);
        
        Env::load();
        
        $this->assertEquals('hello_world', Env::get('TEST_STRING'));
        $this->assertEquals(123, Env::get('TEST_NUMBER'));
        $this->assertEquals(true, Env::get('TEST_BOOLEAN'));
    }

    public function testGetWithDefault()
    {
        $this->createEnvFile('EXISTING_VAR=test_value');
        
        Env::load();
        
        $this->assertEquals('test_value', Env::get('EXISTING_VAR', 'default'));
        $this->assertEquals('default', Env::get('NON_EXISTING_VAR', 'default'));
        $this->assertNull(Env::get('NON_EXISTING_VAR'));
    }

    public function testQuotedValues()
    {
        $envContent = '
QUOTED_DOUBLE="This is a quoted string"
QUOTED_SINGLE=\'This is also quoted\'
QUOTED_WITH_SPACES="Value with spaces"
QUOTED_EMPTY=""
';
        $this->createEnvFile($envContent);
        
        Env::load();
        
        $this->assertEquals('this is a quoted string', Env::get('QUOTED_DOUBLE'));
        $this->assertEquals('this is also quoted', Env::get('QUOTED_SINGLE'));
        $this->assertEquals('value with spaces', Env::get('QUOTED_WITH_SPACES'));
        $this->assertEquals('', Env::get('QUOTED_EMPTY'));
    }

    public function testCommentsAreIgnored()
    {
        $envContent = '
# This is a comment
VALID_VAR=test_value
# Another comment
# COMMENTED_VAR=should_not_load
ANOTHER_VALID=another_value
';
        $this->createEnvFile($envContent);
        
        Env::load();
        
        $this->assertEquals('test_value', Env::get('VALID_VAR'));
        $this->assertEquals('another_value', Env::get('ANOTHER_VALID'));
        $this->assertNull(Env::get('COMMENTED_VAR'));
    }

    public function testEmptyLinesAreIgnored()
    {
        $envContent = "
FIRST_VAR=first_value


SECOND_VAR=second_value

";
        $this->createEnvFile($envContent);
        
        Env::load();
        
        $this->assertEquals('first_value', Env::get('FIRST_VAR'));
        $this->assertEquals('second_value', Env::get('SECOND_VAR'));
    }

    public function testInvalidLinesAreIgnored()
    {
        $envContent = '
VALID_VAR=valid_value
invalid_line_without_equals
=value_without_key
ANOTHER_VALID=another_value
';
        $this->createEnvFile($envContent);
        
        Env::load();
        
        $this->assertEquals('valid_value', Env::get('VALID_VAR'));
        $this->assertEquals('another_value', Env::get('ANOTHER_VALID'));
    }

    public function testValueCasting()
    {
        $envContent = '
STRING_VAR=hello
INTEGER_VAR=42
FLOAT_VAR=3.14
BOOLEAN_TRUE=true
BOOLEAN_FALSE=false
NULL_VAR=null
';
        $this->createEnvFile($envContent);
        
        Env::load();
        
        $this->assertEquals('hello', Env::get('STRING_VAR'));
        $this->assertEquals(42, Env::get('INTEGER_VAR'));
        $this->assertEquals(3.14, Env::get('FLOAT_VAR'));
        $this->assertEquals(true, Env::get('BOOLEAN_TRUE'));
        $this->assertEquals(false, Env::get('BOOLEAN_FALSE'));
        $this->assertNull(Env::get('NULL_VAR'));
    }

    public function testEnvironmentSpecificLoading()
    {
        // Create base .env file
        $this->createEnvFile('BASE_VAR=base_value');
        
        // Create .env.test file
        $this->createEnvFile('TEST_VAR=test_value', '.test');
        
        // Simulate APP_ENV=test
        putenv('APP_ENV=test');
        
        Env::load();
        
        $this->assertEquals('test_value', Env::get('TEST_VAR'));
        
        // Clean up
        putenv('APP_ENV');
    }

    public function testProductionEnvironment()
    {
        $this->createEnvFile('PROD_VAR=prod_value');
        
        // Test with APP_ENV=production
        putenv('APP_ENV=production');
        Env::load();
        $this->assertEquals('prod_value', Env::get('PROD_VAR'));
        
        // Test with APP_ENV=prod
        putenv('APP_ENV=prod');
        // Need to reload since Env::load() has a loaded flag
        $reflection = new \ReflectionClass(Env::class);
        $loadedProperty = $reflection->getProperty('loaded');
        $loadedProperty->setAccessible(true);
        $loadedProperty->setValue(null, false);
        
        Env::load();
        $this->assertEquals('prod_value', Env::get('PROD_VAR'));
        
        // Clean up
        putenv('APP_ENV');
    }

    public function testNoEnvironmentFile()
    {
        // Don't create any .env file
        Env::load();
        
        // Should not error and should return defaults
        $this->assertEquals('default', Env::get('NON_EXISTENT', 'default'));
        $this->assertNull(Env::get('NON_EXISTENT'));
    }

    public function testComplexValues()
    {
        $envContent = '
URL_VAR=https://example.com/path?param=value
EMAIL_VAR=test@example.com
PATH_VAR=/path/to/file
JSON_LIKE="{"key":"value"}"
MULTIWORD_VAR="Multiple words with spaces"
SPECIAL_CHARS=!@#$%^&*()
';
        $this->createEnvFile($envContent);
        
        Env::load();
        
        $this->assertEquals('https://example.com/path?param=value', Env::get('URL_VAR'));
        $this->assertEquals('test@example.com', Env::get('EMAIL_VAR'));
        $this->assertEquals('/path/to/file', Env::get('PATH_VAR'));
        $this->assertEquals('{"key":"value"}', Env::get('JSON_LIKE'));
        $this->assertEquals('multiple words with spaces', Env::get('MULTIWORD_VAR'));
        $this->assertEquals('!@#$%^&*()', Env::get('SPECIAL_CHARS'));
    }

    public function testValueWithEquals()
    {
        $envContent = 'URL_WITH_QUERY=http://example.com?param1=value1&param2=value2';
        $this->createEnvFile($envContent);
        
        Env::load();
        
        $this->assertEquals('http://example.com?param1=value1&param2=value2', Env::get('URL_WITH_QUERY'));
    }

    public function testWhitespaceHandling()
    {
        $envContent = '
  TRIMMED_KEY  =  trimmed_value  
LEADING_SPACE= value_with_leading_space
TRAILING_SPACE=value_with_trailing_space 
';
        $this->createEnvFile($envContent);
        
        Env::load();
        
        $this->assertEquals('trimmed_value', Env::get('TRIMMED_KEY'));
        $this->assertEquals('value_with_leading_space', Env::get('LEADING_SPACE'));
        $this->assertEquals('value_with_trailing_space', Env::get('TRAILING_SPACE'));
    }

    public function testLoadOnlyOnce()
    {
        $envContent = 'LOAD_TEST=first_load';
        $this->createEnvFile($envContent);
        
        Env::load();
        $firstValue = Env::get('LOAD_TEST');
        
        // Change the .env file content
        $this->createEnvFile('LOAD_TEST=second_load');
        
        // Load again - should not reload
        Env::load();
        $secondValue = Env::get('LOAD_TEST');
        
        $this->assertEquals('first_load', $firstValue);
        $this->assertEquals('first_load', $secondValue); // Should still be first value
    }

    public function testEmptyKeyIgnored()
    {
        $envContent = '
=value_without_key
VALID_KEY=valid_value
   =another_value_without_key
';
        $this->createEnvFile($envContent);
        
        Env::load();
        
        $this->assertEquals('valid_value', Env::get('VALID_KEY'));
        $this->assertNull(Env::get(''));
    }
}
