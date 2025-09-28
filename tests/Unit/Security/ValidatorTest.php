<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Goramax\NoctalysFramework\Security\Validator;
use Tests\Helpers\FakeProject;

class ValidatorTest extends TestCase
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

    public function testValidateReturnsValidatorInstance()
    {
        $validator = Validator::validate('test');
        $this->assertInstanceOf(Validator::class, $validator);
    }

    public function testStringValidation()
    {
        $validator = Validator::validate('hello')->string();
        $results = $validator->getResults();
        
        $this->assertArrayHasKey('string', $results);
        $this->assertEquals(1, $results['string']);
        $this->assertTrue($validator->isValid());
    }

    public function testStringValidationFails()
    {
        $validator = Validator::validate(123)->string();
        $results = $validator->getResults();
        
        $this->assertArrayHasKey('string', $results);
        $this->assertEquals(0, $results['string']);
        $this->assertFalse($validator->isValid());
    }

    public function testEmptyValidation()
    {
        $validator = Validator::validate('')->empty();
        $this->assertTrue($validator->isValid());
        
        $validator = Validator::validate('not empty')->empty();
        $this->assertFalse($validator->isValid());
    }

    public function testRequiredValidation()
    {
        $validator = Validator::validate('filled')->required();
        $this->assertTrue($validator->isValid());
        
        $validator = Validator::validate('')->required();
        $this->assertFalse($validator->isValid());
        
        $validator = Validator::validate(null)->required();
        $this->assertFalse($validator->isValid());
    }

    public function testNumberValidation()
    {
        $validator = Validator::validate('123')->number();
        $this->assertTrue($validator->isValid());
        
        $validator = Validator::validate('123.45')->number();
        $this->assertTrue($validator->isValid());
        
        $validator = Validator::validate('not a number')->number();
        $this->assertFalse($validator->isValid());
    }

    public function testIntegerValidation()
    {
        $validator = Validator::validate('123')->integer();
        $this->assertTrue($validator->isValid());
        
        $validator = Validator::validate(456)->integer();
        $this->assertTrue($validator->isValid());
        
        $validator = Validator::validate('123.45')->integer();
        $this->assertFalse($validator->isValid());
        
        $validator = Validator::validate('not a number')->integer();
        $this->assertFalse($validator->isValid());
    }

    public function testFloatValidation()
    {
        $validator = Validator::validate('123.45')->float();
        $this->assertTrue($validator->isValid());
        
        $validator = Validator::validate(67.89)->float();
        $this->assertTrue($validator->isValid());
        
        $validator = Validator::validate('123')->float();
        $this->assertTrue($validator->isValid()); // Integer is valid float
        
        $validator = Validator::validate('not a number')->float();
        $this->assertFalse($validator->isValid());
    }

    public function testMinValidation()
    {
        $validator = Validator::validate(10)->min(5);
        $this->assertTrue($validator->isValid());
        
        $validator = Validator::validate(5)->min(5);
        $this->assertTrue($validator->isValid());
        
        $validator = Validator::validate(3)->min(5);
        $this->assertFalse($validator->isValid());
    }

    public function testMaxValidation()
    {
        $validator = Validator::validate(5)->max(10);
        $this->assertTrue($validator->isValid());
        
        $validator = Validator::validate(10)->max(10);
        $this->assertTrue($validator->isValid());
        
        $validator = Validator::validate(15)->max(10);
        $this->assertFalse($validator->isValid());
    }

    public function testPositiveValidation()
    {
        $validator = Validator::validate(1)->positive();
        $this->assertTrue($validator->isValid());
        
        $validator = Validator::validate(0)->positive();
        $this->assertFalse($validator->isValid());
        
        $validator = Validator::validate(-1)->positive();
        $this->assertFalse($validator->isValid());
    }

    public function testNegativeValidation()
    {
        $validator = Validator::validate(-1)->negative();
        $this->assertTrue($validator->isValid());
        
        $validator = Validator::validate(0)->negative();
        $this->assertFalse($validator->isValid());
        
        $validator = Validator::validate(1)->negative();
        $this->assertFalse($validator->isValid());
    }

    public function testEmailValidation()
    {
        $validator = Validator::validate('test@example.com')->email();
        $this->assertTrue($validator->isValid());
        
        $validator = Validator::validate('user.name+tag@domain.co.uk')->email();
        $this->assertTrue($validator->isValid());
        
        $validator = Validator::validate('invalid-email')->email();
        $this->assertFalse($validator->isValid());
        
        $validator = Validator::validate('test@')->email();
        $this->assertFalse($validator->isValid());
    }

    public function testUrlValidation()
    {
        $validator = Validator::validate('https://example.com')->url();
        $this->assertTrue($validator->isValid());
        
        $validator = Validator::validate('http://subdomain.example.com/path?query=1')->url();
        $this->assertTrue($validator->isValid());
        
        $validator = Validator::validate('ftp://files.example.com')->url();
        $this->assertTrue($validator->isValid());
        
        $validator = Validator::validate('not-a-url')->url();
        $this->assertFalse($validator->isValid());
        
        $validator = Validator::validate('example.com')->url();
        $this->assertFalse($validator->isValid());
    }

    public function testBooleanValidation()
    {
        $validator = Validator::validate(true)->boolean();
        $this->assertTrue($validator->isValid());
        
        $validator = Validator::validate(false)->boolean();
        $this->assertTrue($validator->isValid());
        
        $validator = Validator::validate('true')->boolean();
        $this->assertTrue($validator->isValid());
        
        $validator = Validator::validate('false')->boolean();
        $this->assertTrue($validator->isValid());
        
        $validator = Validator::validate(1)->boolean();
        $this->assertTrue($validator->isValid());
        
        $validator = Validator::validate(0)->boolean();
        $this->assertTrue($validator->isValid());
        
        $validator = Validator::validate('not boolean')->boolean();
        $this->assertFalse($validator->isValid());
    }

    public function testMinLengthValidation()
    {
        $validator = Validator::validate('hello')->minLength(3);
        $this->assertTrue($validator->isValid());
        
        $validator = Validator::validate('hi')->minLength(3);
        $this->assertFalse($validator->isValid());
        
        $validator = Validator::validate('')->minLength(1);
        $this->assertFalse($validator->isValid());
    }

    public function testMaxLengthValidation()
    {
        $validator = Validator::validate('hi')->maxLength(5);
        $this->assertTrue($validator->isValid());
        
        $validator = Validator::validate('hello world')->maxLength(5);
        $this->assertFalse($validator->isValid());
    }

    public function testLengthValidation()
    {
        $validator = Validator::validate('hello')->length(5);
        $this->assertTrue($validator->isValid());
        
        $validator = Validator::validate('hi')->length(5);
        $this->assertFalse($validator->isValid());
        
        $validator = Validator::validate('hello world')->length(5);
        $this->assertFalse($validator->isValid());
    }

    public function testDateValidation()
    {
        $validator = Validator::validate('2023-12-25')->date();
        $this->assertTrue($validator->isValid());
        
        $validator = Validator::validate('25/12/2023')->date('d/m/Y');
        $this->assertTrue($validator->isValid());
        
        $validator = Validator::validate('invalid-date')->date();
        $this->assertFalse($validator->isValid());
        
        $validator = Validator::validate('2023-13-40')->date();
        $this->assertFalse($validator->isValid());
    }

    public function testJsonValidation()
    {
        $validator = Validator::validate('{"key": "value"}')->json();
        $this->assertTrue($validator->isValid());
        
        $validator = Validator::validate('[1, 2, 3]')->json();
        $this->assertTrue($validator->isValid());
        
        $validator = Validator::validate('invalid json')->json();
        $this->assertFalse($validator->isValid());
        
        $validator = Validator::validate('{"incomplete": ')->json();
        $this->assertFalse($validator->isValid());
    }

    public function testIpv4Validation()
    {
        $validator = Validator::validate('192.168.1.1')->ipv4();
        $this->assertTrue($validator->isValid());
        
        $validator = Validator::validate('127.0.0.1')->ipv4();
        $this->assertTrue($validator->isValid());
        
        $validator = Validator::validate('::1')->ipv4();
        $this->assertFalse($validator->isValid());
        
        $validator = Validator::validate('256.1.1.1')->ipv4();
        $this->assertFalse($validator->isValid());
        
        $validator = Validator::validate('not an ip')->ipv4();
        $this->assertFalse($validator->isValid());
    }

    public function testIpv6Validation()
    {
        $validator = Validator::validate('::1')->ipv6();
        $this->assertTrue($validator->isValid());
        
        $validator = Validator::validate('2001:0db8:85a3:0000:0000:8a2e:0370:7334')->ipv6();
        $this->assertTrue($validator->isValid());
        
        $validator = Validator::validate('127.0.0.1')->ipv6();
        $this->assertFalse($validator->isValid());
        
        $validator = Validator::validate('not an ip')->ipv6();
        $this->assertFalse($validator->isValid());
    }

    public function testBase64Validation()
    {
        $encoded = base64_encode('hello world');
        $validator = Validator::validate($encoded)->base64();
        $this->assertTrue($validator->isValid());
        
        $validator = Validator::validate('not base64!')->base64();
        $this->assertFalse($validator->isValid());
    }

    public function testInArrayValidation()
    {
        $allowed = ['red', 'green', 'blue'];
        
        $validator = Validator::validate('red')->inArray($allowed);
        $this->assertTrue($validator->isValid());
        
        $validator = Validator::validate('yellow')->inArray($allowed);
        $this->assertFalse($validator->isValid());
    }

    public function testNotInArrayValidation()
    {
        $notAllowed = ['admin', 'root', 'system'];
        
        $validator = Validator::validate('user')->notInArray($notAllowed);
        $this->assertTrue($validator->isValid());
        
        $validator = Validator::validate('admin')->notInArray($notAllowed);
        $this->assertFalse($validator->isValid());
    }

    public function testInValuesValidation()
    {
        $validator = Validator::validate('red')->inValues('red', 'green', 'blue');
        $this->assertTrue($validator->isValid());
        
        $validator = Validator::validate('yellow')->inValues('red', 'green', 'blue');
        $this->assertFalse($validator->isValid());
        
        $validator = Validator::validate(1)->inValues(1, 2, 3);
        $this->assertTrue($validator->isValid());
    }

    public function testRegexValidation()
    {
        $validator = Validator::validate('abc123')->regex('/^[a-z]+[0-9]+$/');
        $this->assertTrue($validator->isValid());
        
        $validator = Validator::validate('123abc')->regex('/^[a-z]+[0-9]+$/');
        $this->assertFalse($validator->isValid());
    }

    public function testNotMethod()
    {
        $validator = Validator::validate('')->not()->empty();
        $this->assertFalse($validator->isValid());
        
        $validator = Validator::validate('filled')->not()->empty();
        $this->assertTrue($validator->isValid());
    }

    public function testOrValidation()
    {
        // Test OR validation where first condition passes
        $validator = Validator::validate('test@example.com')
            ->startOr('email_or_phone')
            ->email()
            ->regex('/^\+?[0-9]{10,15}$/')
            ->endOr();
        
        $this->assertTrue($validator->isValid());
        $results = $validator->getResults();
        $this->assertArrayHasKey('email_or_phone', $results);
        $this->assertEquals(1, $results['email_or_phone']);
    }

    public function testOrValidationSecondCondition()
    {
        // Test OR validation where second condition passes
        $validator = Validator::validate('+1234567890')
            ->startOr('email_or_phone')
            ->email()
            ->regex('/^\+?[0-9]{10,15}$/')
            ->endOr();
        
        $this->assertTrue($validator->isValid());
        $results = $validator->getResults();
        $this->assertArrayHasKey('email_or_phone', $results);
        $this->assertEquals(1, $results['email_or_phone']);
    }

    public function testOrValidationFailsBoth()
    {
        // Test OR validation where both conditions fail
        $validator = Validator::validate('invalid')
            ->startOr('email_or_phone')
            ->email()
            ->regex('/^\+?[0-9]{10,15}$/')
            ->endOr();
        
        $this->assertFalse($validator->isValid());
        $results = $validator->getResults();
        $this->assertArrayHasKey('email_or_phone', $results);
        $this->assertEquals(0, $results['email_or_phone']);
    }

    public function testCustomValidation()
    {
        // Register a custom validator
        Validator::registerCustom('isEven', function($value) {
            return is_numeric($value) && $value % 2 === 0;
        });
        
        $validator = Validator::validate(4)->custom('isEven');
        $this->assertTrue($validator->isValid());
        
        $validator = Validator::validate(3)->custom('isEven');
        $this->assertFalse($validator->isValid());
        
        // Test custom validator with arguments
        Validator::registerCustom('divisibleBy', function($value, $divisor) {
            return is_numeric($value) && $value % $divisor === 0;
        });
        
        $validator = Validator::validate(15)->custom('divisibleBy', 5);
        $this->assertTrue($validator->isValid());
        
        $validator = Validator::validate(16)->custom('divisibleBy', 5);
        $this->assertFalse($validator->isValid());
    }

    public function testCustomValidationNonExistent()
    {
        $validator = Validator::validate('test')->custom('nonExistentValidator');
        $this->assertFalse($validator->isValid());
    }

    public function testMultipleValidations()
    {
        $validator = Validator::validate('test@example.com')
            ->string()
            ->required()
            ->email()
            ->minLength(5);
        
        $this->assertTrue($validator->isValid());
        
        $results = $validator->getResults();
        $this->assertArrayHasKey('string', $results);
        $this->assertArrayHasKey('required', $results);
        $this->assertArrayHasKey('email', $results);
        $this->assertArrayHasKey('minLength', $results);
        $this->assertEquals(1, $results['string']);
        $this->assertEquals(1, $results['required']);
        $this->assertEquals(1, $results['email']);
        $this->assertEquals(1, $results['minLength']);
    }

    public function testMixedValidationResults()
    {
        $validator = Validator::validate('test')
            ->string()     // passes
            ->email()      // fails
            ->minLength(3); // passes
        
        $this->assertFalse($validator->isValid());
        
        $results = $validator->getResults();
        $this->assertEquals(1, $results['string']);
        $this->assertEquals(0, $results['email']);
        $this->assertEquals(1, $results['minLength']);
    }

    public function testMethodChaining()
    {
        $validator = Validator::validate('123')
            ->string()
            ->number()
            ->minLength(2)
            ->maxLength(5);
        
        $this->assertInstanceOf(Validator::class, $validator);
        $this->assertTrue($validator->isValid());
    }

    public function testGetResultsStructure()
    {
        $validator = Validator::validate('test')
            ->string()
            ->required();
        
        $results = $validator->getResults();
        
        $this->assertIsArray($results);
        $this->assertArrayHasKey('string', $results);
        $this->assertArrayHasKey('required', $results);
        $this->assertContains(1, $results);
        $this->assertNotContains(true, $results);
        $this->assertNotContains(false, $results);
    }
}
