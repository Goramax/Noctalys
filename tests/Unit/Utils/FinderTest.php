<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Goramax\NoctalysFramework\Utils\Finder;

class FinderTest extends TestCase
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
        
        if (!defined('DIRECTORY')) {
            define('DIRECTORY', $this->fakeProjectPath);
        }
    }

    protected function tearDown(): void
    {
        if ($this->canRunTest && isset($this->originalCwd)) {
            chdir($this->originalCwd);
        }
        parent::tearDown();
    }

    public function testFindFileReturnsNullForNonExistentFile()
    {
        $this->expectException(\Exception::class); 
        $result = Finder::findFile('nonexistentfile.txt', [
            'sources' => [
                ['folder_name' => 'assets', 'path' => 'public']
            ]
        ]);
        // should return an error
        $this->assertNull($result);
    }

    public function testFindFileReturnsPathForExistingFile()
    {
        // Ensure the test file exists in the fake project structure
        $testFilePath = $this->fakeProjectPath . '/public/assets/testfile.txt';
        if (!file_exists($testFilePath)) {
            file_put_contents($testFilePath, 'Test content');
        }

        $result = Finder::findFile('testfile.txt', [
            'sources' => [
                ['folder_name' => 'assets', 'path' => 'public']
            ]
        ]);
        $this->assertNotNull($result);
        $this->assertStringEndsWith('public/assets/testfile.txt', $result);
    }

    public function testFindLayout()
    {
        $result = Finder::findLayout('default');
        echo $result;
        $this->assertNotNull($result);
        $this->assertSame('src/Frontend/layouts/default.layout.php', $result);
    }

    public function testFindComponent()
    {
        $result = Finder::findComponent('home-btn');
        $this->assertNotNull($result);
        $this->assertSame('src/Frontend/components/home-btn.component.php', $result);
    }
}