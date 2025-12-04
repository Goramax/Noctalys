<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Noctalys\Framework\Services\File;
use Tests\Helpers\FakeProject;

class FileTest extends TestCase
{
    private FakeProject $fakeProject;

    private function ensureDirExists(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }

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
            // Clean up all test files created in public/assets, but preserve system files
            $assetsDir = $this->fakeProject->getProjectPath() . '/public/assets';
            if (is_dir($assetsDir)) {
                $files = glob($assetsDir . '/*');
                $permanentFiles = ['manifest.json', 'main.css', 'main.js', 'testfile.txt'];
                
                foreach ($files as $file) {
                    if (is_file($file)) {
                        $filename = basename($file);
                        // Only delete files that are not permanent system files
                        if (!in_array($filename, $permanentFiles)) {
                            unlink($file);
                        }
                    }
                }
            }
            
            $this->fakeProject->tearDown();
        }
        parent::tearDown();
    }

    public function testUploadWithFakeFile()
    {
        // Create a real temporary file for the test
        $tempFile = tempnam(sys_get_temp_dir(), 'test_upload');
        file_put_contents($tempFile, 'Test content for upload');
        
        // Ensure the destination directory exists
        $uploadDir = $this->fakeProject->getProjectPath() . '/public/assets';
        $this->ensureDirExists($uploadDir);
        
        // Create fake HTML input with file data
        $_FILES['upload'] = [
            'name' => 'file.txt',
            'type' => 'text/plain',
            'tmp_name' => $tempFile,
            'error' => 0,
            'size' => 123
        ];
        $_POST['upload'] = true;

        // move_uploaded_file() will fail because this is not a real HTTP upload
        $this->expectException(\ErrorException::class);
        $this->expectExceptionMessage('Failed to move file');
        
        $result = File::upload('upload', 'public/assets', canCreateDir: false, allowedExtensions: ['txt'], forceInsecure: true);
        
        // Clean up temporary file
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
    }

    public function testRead()
    {
        // Create a test file
        $testFile = 'public/assets/testfile.txt';
        $testContent = 'Success !';
        $fullPath = $this->fakeProject->getProjectPath() . '/' . $testFile;
        
        // Ensure the directory exists
        $dir = dirname($fullPath);
        $this->ensureDirExists($dir);
        
        file_put_contents($fullPath, $testContent);
        
        $result = File::read($testFile);
        $this->assertEquals($testContent, $result);
        
        // Clean up
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }

    public function testReadNonExistentFile()
    {
        $result = File::read('non-existent-file.txt');
        $this->assertNull($result);
    }

    public function testSanitizeName()
    {
        $result = File::sanitizeName('Test file with spaces.txt');
        $this->assertEquals('Test_file_with_spaces_txt', $result);
        
        $result = File::sanitizeName('test@#$%^&*()file.txt');
        $this->assertEquals('test_________file_txt', $result);
    }

    public function testList()
    {
        // Ensure the public/assets directory exists with some files
        $assetsDir = 'public/assets';
        $fullAssetsPath = $this->fakeProject->getProjectPath() . '/' . $assetsDir;
        $this->ensureDirExists($fullAssetsPath);
        
        // Get initial file count (permanent files)
        $initialFiles = File::list($assetsDir);
        $initialCount = count($initialFiles);
        
        // Create some test files
        file_put_contents($fullAssetsPath . '/test1.txt', 'content1');
        file_put_contents($fullAssetsPath . '/test2.jpg', 'content2');
        file_put_contents($fullAssetsPath . '/test3.pdf', 'content3');
        
        $result = File::list($assetsDir);
        
        $this->assertIsArray($result);
        $this->assertEquals($initialCount + 3, count($result));
        
        // Check that each element has the expected structure
        foreach ($result as $file) {
            $this->assertIsArray($file);
            $this->assertArrayHasKey('name', $file);
            $this->assertArrayHasKey('path', $file);
        }
        
        // Extract file names for verification
        $fileNames = array_column($result, 'name');
        $this->assertContains('test1.txt', $fileNames);
        $this->assertContains('test2.jpg', $fileNames);
        $this->assertContains('test3.pdf', $fileNames);
        
        // Clean up
        unlink($fullAssetsPath . '/test1.txt');
        unlink($fullAssetsPath . '/test2.jpg');
        unlink($fullAssetsPath . '/test3.pdf');
    }

    public function testListNonExistentDirectory()
    {
        $result = File::list('non-existent-directory');
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testDelete()
    {
        // Create a test file
        $testFile = 'public/assets/file-to-delete.txt';
        $fullPath = $this->fakeProject->getProjectPath() . '/' . $testFile;
        
        // Ensure the directory exists
        $dir = dirname($fullPath);
        $this->ensureDirExists($dir);
        
        file_put_contents($fullPath, 'This file will be deleted');
        $this->assertTrue(file_exists($fullPath), 'The file must exist before deletion');
        
        $result = File::delete($testFile);
        $this->assertTrue($result);
        $this->assertFalse(file_exists($fullPath), 'The file should no longer exist after deletion');
    }

    public function testDeleteNonExistentFile()
    {
        $result = File::delete('non-existent-file.txt');
        $this->assertFalse($result);
    }

    public function testRename()
    {
        // Create a test file
        $originalFile = 'public/assets/original-name.txt';
        $fullOriginalPath = $this->fakeProject->getProjectPath() . '/' . $originalFile;
        $newName = 'renamed-file.txt';
        $fullNewPath = $this->fakeProject->getProjectPath() . '/public/assets/' . $newName;
        
        // Ensure the directory exists
        $dir = dirname($fullOriginalPath);
        $this->ensureDirExists($dir);
        
        file_put_contents($fullOriginalPath, 'Content to rename');
        $this->assertTrue(file_exists($fullOriginalPath), 'The original file must exist');
        
        $result = File::rename($originalFile, $newName);
        $this->assertTrue($result);
        $this->assertFalse(file_exists($fullOriginalPath), 'The original file should no longer exist');
        $this->assertTrue(file_exists($fullNewPath), 'The renamed file should exist');
        
        // Clean up
        if (file_exists($fullNewPath)) {
            unlink($fullNewPath);
        }
    }

    public function testRenameNonExistentFile()
    {
        $result = File::rename('non-existent-file.txt', 'new-name.txt');
        $this->assertFalse($result);
    }

    public function testGetMime()
    {
        // Create a text file
        $testFile = 'public/assets/mime-test.txt';
        $fullPath = $this->fakeProject->getProjectPath() . '/' . $testFile;
        
        // Ensure the directory exists
        $dir = dirname($fullPath);
        $this->ensureDirExists($dir);
        
        file_put_contents($fullPath, 'This is a text file for MIME testing');
        
        $result = File::getMime($testFile);
        $this->assertIsString($result);
        $this->assertStringContainsString('text/', $result);
        
        // Clean up
        unlink($fullPath);
    }

    public function testGetMimeNonExistentFile()
    {
        $result = File::getMime('non-existent-file.txt');
        $this->assertNull($result);
    }

    public function testSize()
    {
        // Create a file with known content size
        $testFile = 'public/assets/size-test.txt';
        $fullPath = $this->fakeProject->getProjectPath() . '/' . $testFile;
        $content = 'This content has exactly 50 characters in total.';
        
        // Ensure the directory exists
        $dir = dirname($fullPath);
        $this->ensureDirExists($dir);
        
        file_put_contents($fullPath, $content);
        
        $result = File::size($testFile);
        $this->assertIsInt($result);
        $this->assertEquals(strlen($content), $result);
        
        // Clean up
        unlink($fullPath);
    }

    public function testSizeNonExistentFile()
    {
        $result = File::size('non-existent-file.txt');
        $this->assertNull($result);
    }
}
