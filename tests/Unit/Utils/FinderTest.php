<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Goramax\NoctalysFramework\Utils\Finder;
use Tests\Helpers\FakeProject;

class FinderTest extends TestCase
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
        $testFilePath = $this->fakeProject->getProjectPath() . '/public/assets/testfile.txt';
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