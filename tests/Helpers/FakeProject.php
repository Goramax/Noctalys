<?php

namespace Tests\Helpers;

class FakeProject
{
    private string $projectPath;
    private string $originalCwd;
    private bool $directoryWasDefined;
    
    public function __construct()
    {
        $this->projectPath = __DIR__ . '/../fixtures/fake-project';
        $this->directoryWasDefined = defined('DIRECTORY');
    }
    
    /**
     * Setup the fake project environment for testing
     */
    public function setUp(): void
    {
        // Save original working directory
        $this->originalCwd = getcwd();
        
        // Change to fake project directory
        chdir($this->projectPath);
        
        // Setup $_SERVER variables needed by Bootstrap
        $_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/test';
        
        // Define DIRECTORY constant if not already defined
        if (!$this->directoryWasDefined) {
            define('DIRECTORY', $this->projectPath);
        }
        
        // Ensure critical test files exist
        $this->ensureCriticalFilesExist();
    }
    
    /**
     * Ensure critical test files exist in the fake project
     */
    private function ensureCriticalFilesExist(): void
    {
        $assetsDir = $this->projectPath . '/public/assets';
        
        // Create assets directory if it doesn't exist
        if (!is_dir($assetsDir)) {
            mkdir($assetsDir, 0777, true);
        }
        
        // Create manifest.json if it doesn't exist
        $manifestFile = $assetsDir . '/manifest.json';
        if (!file_exists($manifestFile)) {
            $manifest = [
                'main.css' => 'main.css',
                'main.js' => 'main.js'
            ];
            file_put_contents($manifestFile, json_encode($manifest, JSON_PRETTY_PRINT));
        }
        
        // Create main.css if it doesn't exist
        $cssFile = $assetsDir . '/main.css';
        if (!file_exists($cssFile)) {
            file_put_contents($cssFile, "/* Main CSS file for testing */\nbody {\n    font-family: Arial, sans-serif;\n}");
        }
        
        // Create main.js if it doesn't exist
        $jsFile = $assetsDir . '/main.js';
        if (!file_exists($jsFile)) {
            file_put_contents($jsFile, "// Main JS file for testing\nconsole.log('Main JS loaded');");
        }
    }
    
    /**
     * Clean up the fake project environment after testing
     */
    public function tearDown(): void
    {
        // Restore original working directory
        chdir($this->originalCwd);
        
        // Clean up any sessions
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        
        // Restore error handlers
        restore_error_handler();
        restore_exception_handler();
    }
    
    /**
     * Get the path to the fake project
     */
    public function getProjectPath(): string
    {
        return $this->projectPath;
    }
    
    /**
     * Check if we can use the fake project (DIRECTORY not already defined)
     */
    public function canUse(): bool
    {
        return !$this->directoryWasDefined;
    }
}
