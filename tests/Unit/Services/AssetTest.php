<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Goramax\NoctalysFramework\Services\Asset;
use Tests\Helpers\FakeProject;

class AssetTest extends TestCase
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

    public function testGetPathReturnsCorrectCssPath()
    {
        $path = Asset::getPath('css', 'main');
        
        $this->assertIsString($path);
        $this->assertEquals('/public/assets/main.css', $path);
    }

    public function testGetPathReturnsCorrectJsPath()
    {
        $path = Asset::getPath('js', 'main');
        
        $this->assertIsString($path);
        $this->assertEquals('/public/assets/main.js', $path);
    }

    public function testGetPathThrowsExceptionForInvalidType()
    {
        $this->expectException(\ErrorException::class);
        $this->expectExceptionMessage('Invalid asset type: invalid');
        
        Asset::getPath('invalid', 'main');
    }

    public function testGetPathThrowsExceptionWhenManifestNotFound()
    {
        // Remove the manifest file
        $manifestPath = $this->fakeProject->getProjectPath() . '/public/assets/manifest.json';
        if (file_exists($manifestPath)) {
            unlink($manifestPath);
        }
        
        $this->expectException(\ErrorException::class);
        $this->expectExceptionMessage('Manifest file not found');
        
        Asset::getPath('css', 'main');
    }

    public function testGetPathTriggersWarningForNonExistentAsset()
    {
        // Create a temporary error handler to catch the warning
        $warningTriggered = false;
        $originalHandler = set_error_handler(function($severity, $message) use (&$warningTriggered) {
            if ($severity === E_USER_WARNING && str_contains($message, 'Asset not found in manifest')) {
                $warningTriggered = true;
                return true; // Suppress the warning
            }
            return false;
        });

        $result = Asset::getPath('css', 'nonexistent');
        
        // Restore the original error handler
        set_error_handler($originalHandler);
        
        $this->assertTrue($warningTriggered);
        $this->assertEquals('', $result);
    }

    public function testGetPathReturnsEmptyStringForNonExistentAsset()
    {
        // Suppress warnings for this test
        $originalLevel = error_reporting(E_ALL & ~E_USER_WARNING);
        
        $result = Asset::getPath('css', 'nonexistent');
        
        // Restore original error reporting
        error_reporting($originalLevel);
        
        $this->assertEquals('', $result);
    }

    public function testGetPathWithDifferentAssetNames()
    {
        // Add additional assets to manifest
        $manifestPath = $this->fakeProject->getProjectPath() . '/public/assets/manifest.json';
        $manifest = [
            'main.css' => 'main.css',
            'main.js' => 'main.js',
            'admin.css' => 'admin.min.css',
            'admin.js' => 'admin.min.js',
            'app.css' => 'css/app-123.css',
            'app.js' => 'js/app-456.js'
        ];
        file_put_contents($manifestPath, json_encode($manifest));

        $this->assertEquals('/public/assets/admin.min.css', Asset::getPath('css', 'admin'));
        $this->assertEquals('/public/assets/admin.min.js', Asset::getPath('js', 'admin'));
        $this->assertEquals('/public/assets/css/app-123.css', Asset::getPath('css', 'app'));
        $this->assertEquals('/public/assets/js/app-456.js', Asset::getPath('js', 'app'));
    }

    public function testGetPathWithCorruptedManifest()
    {
        // Create a corrupted manifest file
        $manifestPath = $this->fakeProject->getProjectPath() . '/public/assets/manifest.json';
        file_put_contents($manifestPath, 'invalid json content');
        
        // Suppress warnings for this test
        $originalLevel = error_reporting(E_ALL & ~E_USER_WARNING);
        
        $result = Asset::getPath('css', 'main');
        
        // Restore original error reporting
        error_reporting($originalLevel);
        
        $this->assertEquals('', $result);
    }

    public function testGetPathHandlesSpecialCharactersInAssetNames()
    {
        // Test with asset names containing special characters
        $manifestPath = $this->fakeProject->getProjectPath() . '/public/assets/manifest.json';
        $manifest = [
            'main.css' => 'main.css',
            'main.js' => 'main.js',
            'my-component.css' => 'components/my-component.css',
            'user_profile.js' => 'js/user_profile.js'
        ];
        file_put_contents($manifestPath, json_encode($manifest));

        $this->assertEquals('/public/assets/components/my-component.css', Asset::getPath('css', 'my-component'));
        $this->assertEquals('/public/assets/js/user_profile.js', Asset::getPath('js', 'user_profile'));
    }

    public function testGetPathConsistentResults()
    {
        // Test that multiple calls return the same result
        $path1 = Asset::getPath('css', 'main');
        $path2 = Asset::getPath('css', 'main');
        $path3 = Asset::getPath('js', 'main');
        
        $this->assertEquals($path1, $path2);
        
        // If assets are found, verify the paths
        if (!empty($path1)) {
            $this->assertEquals('/public/assets/main.css', $path1);
        }
        if (!empty($path3)) {
            $this->assertEquals('/public/assets/main.js', $path3);
        }
        
        // At minimum, verify consistency
        $this->assertTrue(is_string($path1));
        $this->assertTrue(is_string($path3));
    }
}
