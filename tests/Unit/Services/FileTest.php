<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Goramax\NoctalysFramework\Services\File;

class FileTest extends TestCase
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

    public function testUploadWithFakeFile()
    {
        // Créer un vrai fichier temporaire pour le test
        $tempFile = tempnam(sys_get_temp_dir(), 'test_upload');
        file_put_contents($tempFile, 'Test content for upload');
        
        // S'assurer que le répertoire de destination existe
        $uploadDir = $this->fakeProjectPath . '/public/assets';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // create fake html input with file data
        $_FILES['upload'] = [
            'name' => 'file.txt',
            'type' => 'text/plain',
            'tmp_name' => $tempFile,
            'error' => 0,
            'size' => 123
        ];
        $_POST['upload'] = true;

        // move_uploaded_file() échouera car ce n'est pas un vrai upload HTTP
        $this->expectException(\ErrorException::class);
        $this->expectExceptionMessage('Failed to move file');
        
        $result = File::upload('upload', 'public/assets', canCreateDir: false, allowedExtensions: ['txt'], forceInsecure: true);
        
        // Nettoyer le fichier temporaire
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
    }

    public function testRead()
    {
        // Créer un fichier de test
        $testFile = 'public/assets/testfile.txt';
        
        $result = File::read($testFile);
        $this->assertEquals('Success !', $result);
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

}