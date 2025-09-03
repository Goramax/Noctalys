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
