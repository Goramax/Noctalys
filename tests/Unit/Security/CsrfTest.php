<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Goramax\NoctalysFramework\Security\Csrf;
use Tests\Helpers\FakeProject;

class CsrfTest extends TestCase
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

        // Clean up any existing session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        
        // Clear session superglobal
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        // Clean up session after each test
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
        
        if (isset($this->fakeProject)) {
            $this->fakeProject->tearDown();
        }
        parent::tearDown();
    }

    public function testTokenGeneratesValidToken()
    {
        $token = Csrf::token();
        
        $this->assertIsString($token);
        $this->assertEquals(64, strlen($token)); // 32 bytes -> 64 hex chars
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
    }

    public function testTokenStartsSession()
    {
        // Ensure session is not active
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        
        $token = Csrf::token();
        
        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());
        $this->assertArrayHasKey('_csrf_token', $_SESSION);
        $this->assertEquals($token, $_SESSION['_csrf_token']);
    }

    public function testTokenReturnsSameTokenInSameSession()
    {
        $token1 = Csrf::token();
        $token2 = Csrf::token();
        
        $this->assertEquals($token1, $token2);
        $this->assertSame($_SESSION['_csrf_token'], $token1);
        $this->assertSame($_SESSION['_csrf_token'], $token2);
    }

    public function testCheckValidatesCorrectToken()
    {
        $token = Csrf::token();
        
        $this->assertTrue(Csrf::check($token));
        $this->assertTrue(Csrf::check($_SESSION['_csrf_token']));
    }

    public function testCheckRejectsInvalidToken()
    {
        Csrf::token(); // Generate a valid token
        
        $this->assertFalse(Csrf::check('invalid_token'));
        $this->assertFalse(Csrf::check(''));
        $this->assertFalse(Csrf::check('123456789012345678901234567890123456789012345678901234567890123'));
    }

    public function testCheckRejectsNullToken()
    {
        Csrf::token(); // Generate a valid token
        
        $this->assertFalse(Csrf::check(null));
    }

    public function testCheckReturnsFalseWhenNoTokenInSession()
    {
        // Don't generate token, just check
        $this->assertFalse(Csrf::check('any_token'));
    }

    public function testCheckStartsSessionIfNotActive()
    {
        // Ensure session is not active
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        
        $result = Csrf::check('some_token');
        
        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());
        $this->assertFalse($result); // Should still be false since no token in session
    }

    public function testCheckUsesHashEquals()
    {
        $token = Csrf::token();
        
        // Test with exact same token
        $this->assertTrue(Csrf::check($token));
        
        // Test with a token that has same content but might be different object
        $tokenCopy = (string) $token;
        $this->assertTrue(Csrf::check($tokenCopy));
    }

    public function testInputGeneratesValidHtmlInput()
    {
        $input = Csrf::input();
        
        $this->assertIsString($input);
        $this->assertStringContainsString('<input type="hidden"', $input);
        $this->assertStringContainsString('name="csrf_token"', $input);
        $this->assertStringContainsString('value="', $input);
        $this->assertStringContainsString('">', $input);
    }

    public function testInputContainsValidToken()
    {
        $token = Csrf::token();
        $input = Csrf::input();
        
        $this->assertStringContainsString($token, $input);
        
        // Parse the value attribute
        preg_match('/value="([^"]*)"/', $input, $matches);
        $this->assertCount(2, $matches);
        $this->assertEquals($token, $matches[1]);
    }

    public function testInputEscapesTokenValue()
    {
        // First, start a session to ensure proper token generation
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        // Simulate a token that needs escaping (though in practice this won't happen with random_bytes)
        $_SESSION['_csrf_token'] = 'token"with<special>&chars';
        
        $input = Csrf::input();
        
        $this->assertStringContainsString('token&quot;with&lt;special&gt;&amp;chars', $input);
        $this->assertStringNotContainsString('token"with<special>&chars', $input);
    }

    public function testInputGeneratesNewTokenIfNoneExists()
    {
        // Ensure no token exists
        unset($_SESSION['_csrf_token']);
        
        $input = Csrf::input();
        
        $this->assertArrayHasKey('_csrf_token', $_SESSION);
        $this->assertStringContainsString($_SESSION['_csrf_token'], $input);
    }

    public function testTokenPersistsAcrossMultipleCalls()
    {
        $token1 = Csrf::token();
        $input = Csrf::input();
        $token2 = Csrf::token();
        
        $this->assertEquals($token1, $token2);
        $this->assertStringContainsString($token1, $input);
    }

    public function testDifferentSessionsGenerateDifferentTokens()
    {
        // First session
        $token1 = Csrf::token();
        
        // Simulate new session
        session_destroy();
        $_SESSION = [];
        
        // Second session
        $token2 = Csrf::token();
        
        $this->assertNotEquals($token1, $token2);
    }
}
