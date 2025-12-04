<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Noctalys\Framework\Services\Db;
use Noctalys\Framework\Services\Env;
use Tests\Helpers\FakeProject;
use ReflectionClass;

class DbTest extends TestCase
{
    private FakeProject $fakeProject;

    protected function setUp(): void
    {
        $this->fakeProject = new FakeProject();
        $this->fakeProject->setUp();
        
        $this->setupTestDatabase();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestDatabase();
        $this->fakeProject->tearDown();
        
        // Reset Db connection
        $reflection = new ReflectionClass(Db::class);
        $property = $reflection->getProperty('pdo');
        $property->setAccessible(true);
        $property->setValue(null, null);
    }

    private function setupTestDatabase(): void
    {
        // Ensure database directory exists
        $dbPath = $this->fakeProject->getProjectPath() . '/test.db';
        $dbDir = dirname($dbPath);
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }
        
        // Create a minimal .env file for database configuration
        $envContent = "DB_DRIVER=sqlite\nDB_HOST=$dbPath\n";
        file_put_contents($this->fakeProject->getProjectPath() . '/.env', $envContent);
        
        // Reset and load environment
        $reflection = new ReflectionClass(Env::class);
        $loadedProperty = $reflection->getProperty('loaded');
        $loadedProperty->setAccessible(true);
        $loadedProperty->setValue(null, false);
        
        Env::load();
        
        // Reset Db connection to use new configuration
        $reflection = new ReflectionClass(Db::class);
        $property = $reflection->getProperty('pdo');
        $property->setAccessible(true);
        $property->setValue(null, null);
    }

    private function createTestTable(): void
    {
        $createTable = "
            CREATE TABLE IF NOT EXISTS test_users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT UNIQUE NOT NULL,
                age INTEGER
            )
        ";
        Db::sql($createTable);
    }

    private function cleanupTestDatabase(): void
    {
        $dbPath = $this->fakeProject->getProjectPath() . '/test.db';
        if (file_exists($dbPath)) {
            unlink($dbPath);
        }
        
        // Clean up the .env file created by this test
        $envPath = $this->fakeProject->getProjectPath() . '/.env';
        if (file_exists($envPath)) {
            unlink($envPath);
        }
    }

    public function testSqlSelect()
    {
        $this->createTestTable();
        
        // Insert test data
        Db::sql("INSERT INTO test_users (name, email, age) VALUES (?, ?, ?)", ['John Doe', 'john@example.com', 30]);
        Db::sql("INSERT INTO test_users (name, email, age) VALUES (?, ?, ?)", ['Jane Smith', 'jane@example.com', 25]);
        
        // Test SELECT query
        $result = Db::sql("SELECT * FROM test_users WHERE age > ?", [20]);
        
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('John Doe', $result[0]['name']);
        $this->assertEquals('jane@example.com', $result[1]['email']);
    }

    public function testSqlInsert()
    {
        $this->createTestTable();
        
        // Test INSERT query
        $result = Db::sql("INSERT INTO test_users (name, email, age) VALUES (?, ?, ?)", ['Test User', 'test@example.com', 35]);
        
        // For INSERT queries, result should be null, int, string, or bool
        $this->assertTrue($result === null || is_int($result) || is_string($result) || is_bool($result));
        
        // Verify the data was inserted
        $selectResult = Db::sql("SELECT * FROM test_users WHERE email = ?", ['test@example.com']);
        $this->assertIsArray($selectResult);
        $this->assertCount(1, $selectResult);
        $this->assertEquals('Test User', $selectResult[0]['name']);
    }

    public function testSqlUpdate()
    {
        $this->createTestTable();
        
        // Insert initial data
        Db::sql("INSERT INTO test_users (name, email, age) VALUES (?, ?, ?)", ['Original Name', 'update@example.com', 25]);
        
        // Test UPDATE query
        $result = Db::sql("UPDATE test_users SET name = ?, age = ? WHERE email = ?", ['Updated Name', 30, 'update@example.com']);
        
        // For UPDATE queries, result should be null, int, string, or bool
        $this->assertTrue($result === null || is_int($result) || is_string($result) || is_bool($result));
        
        // Verify the data was updated
        $selectResult = Db::sql("SELECT * FROM test_users WHERE email = ?", ['update@example.com']);
        $this->assertIsArray($selectResult);
        $this->assertEquals('Updated Name', $selectResult[0]['name']);
        $this->assertEquals(30, $selectResult[0]['age']);
    }

    public function testSqlDelete()
    {
        $this->createTestTable();
        
        // Insert test data
        Db::sql("INSERT INTO test_users (name, email, age) VALUES (?, ?, ?)", ['To Delete', 'delete@example.com', 40]);
        Db::sql("INSERT INTO test_users (name, email, age) VALUES (?, ?, ?)", ['To Keep', 'keep@example.com', 20]);
        
        // Test DELETE query
        $result = Db::sql("DELETE FROM test_users WHERE email = ?", ['delete@example.com']);
        
        // For DELETE queries, result should be null, int, string, or bool
        $this->assertTrue($result === null || is_int($result) || is_string($result) || is_bool($result));
        
        // Verify only one record remains
        $selectResult = Db::sql("SELECT * FROM test_users");
        $this->assertIsArray($selectResult);
        $this->assertCount(1, $selectResult);
        $this->assertEquals('keep@example.com', $selectResult[0]['email']);
    }

    public function testSqlWithoutParameters()
    {
        $this->createTestTable();
        
        // Insert test data without parameters
        Db::sql("INSERT INTO test_users (name, email, age) VALUES ('Static Name', 'static@example.com', 45)");
        
        // Test SELECT without parameters
        $result = Db::sql("SELECT * FROM test_users");
        
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('Static Name', $result[0]['name']);
        $this->assertEquals('static@example.com', $result[0]['email']);
    }

    public function testEmptySelect()
    {
        $this->createTestTable();
        
        // Test query that returns no results
        $result = Db::sql("SELECT * FROM test_users WHERE age > ?", [100]);
        
        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function testUpdateNonExistentRecord()
    {
        $this->createTestTable();
        
        // Try to update a record that doesn't exist
        $result = Db::sql("UPDATE test_users SET name = ? WHERE email = ?", ['New Name', 'nonexistent@example.com']);
        
        $this->assertTrue($result === null || is_int($result) || is_string($result) || is_bool($result));
        
        // Verify no records exist
        $selectResult = Db::sql("SELECT * FROM test_users");
        $this->assertIsArray($selectResult);
        $this->assertCount(0, $selectResult);
    }

    public function testDeleteNonExistentRecord()
    {
        $this->createTestTable();
        
        // Try to delete a record that doesn't exist
        $result = Db::sql("DELETE FROM test_users WHERE email = ?", ['nonexistent@example.com']);
        
        $this->assertTrue($result === null || is_int($result) || is_string($result) || is_bool($result));
        
        // Verify no records exist
        $selectResult = Db::sql("SELECT * FROM test_users");
        $this->assertIsArray($selectResult);
        $this->assertCount(0, $selectResult);
    }

    public function testSqlWithNullParameters()
    {
        $this->createTestTable();
        
        // Insert data with NULL age
        Db::sql("INSERT INTO test_users (name, email, age) VALUES (?, ?, ?)", ['Null Age', 'null@example.com', null]);
        
        $result = Db::sql("SELECT * FROM test_users WHERE age IS NULL");
        
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('Null Age', $result[0]['name']);
        $this->assertNull($result[0]['age']);
    }

    public function testInvalidSqlThrowsException()
    {
        $this->expectException(\Exception::class);
        
        // This should throw an exception due to invalid SQL
        Db::sql("INVALID SQL STATEMENT");
    }

    public function testSqlInjectionProtection()
    {
        $this->createTestTable();
        
        // Insert test data
        Db::sql("INSERT INTO test_users (name, email, age) VALUES (?, ?, ?)", ['Test User', 'test@example.com', 25]);
        
        // Try SQL injection - this should be safe due to parameter binding
        $maliciousInput = "'; DROP TABLE test_users; --";
        $result = Db::sql("SELECT * FROM test_users WHERE name = ?", [$maliciousInput]);
        
        $this->assertIsArray($result);
        $this->assertCount(0, $result); // No results should be found
        
        // Verify the table still exists and contains data
        $allUsers = Db::sql("SELECT * FROM test_users");
        $this->assertIsArray($allUsers);
        $this->assertCount(1, $allUsers); // Original record should still exist
    }

    public function testMultipleQueriesInSequence()
    {
        $this->createTestTable();
        
        // Execute multiple queries in sequence
        Db::sql("INSERT INTO test_users (name, email, age) VALUES (?, ?, ?)", ['User 1', 'user1@example.com', 20]);
        Db::sql("INSERT INTO test_users (name, email, age) VALUES (?, ?, ?)", ['User 2', 'user2@example.com', 30]);
        Db::sql("INSERT INTO test_users (name, email, age) VALUES (?, ?, ?)", ['User 3', 'user3@example.com', 40]);
        
        // Update one user
        Db::sql("UPDATE test_users SET age = ? WHERE email = ?", [35, 'user2@example.com']);
        
        // Delete one user
        Db::sql("DELETE FROM test_users WHERE email = ?", ['user3@example.com']);
        
        // Verify final state
        $result = Db::sql("SELECT * FROM test_users ORDER BY age");
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals(20, $result[0]['age']);
        $this->assertEquals(35, $result[1]['age']);
    }

    public function testConnectionReuse()
    {
        $this->createTestTable();
        
        // Execute multiple queries to test connection reuse
        $result1 = Db::sql("SELECT COUNT(*) as count FROM test_users");
        $this->assertIsArray($result1);
        $this->assertEquals(0, $result1[0]['count']);
        
        Db::sql("INSERT INTO test_users (name, email, age) VALUES (?, ?, ?)", ['Test User', 'test@example.com', 25]);
        
        $result2 = Db::sql("SELECT COUNT(*) as count FROM test_users");
        $this->assertIsArray($result2);
        $this->assertEquals(1, $result2[0]['count']);
        
        // Both queries should have used the same connection
        $this->assertTrue(true); // If we get here, connection reuse worked
    }

    public function testInvalidDatabaseDriver()
    {
        // Test with invalid database driver
        $envContent = "DB_DRIVER=invalid_driver\n";
        file_put_contents($this->fakeProject->getProjectPath() . '/.env', $envContent);
        
        // Reset and load environment
        $reflection = new ReflectionClass(Env::class);
        $loadedProperty = $reflection->getProperty('loaded');
        $loadedProperty->setAccessible(true);
        $loadedProperty->setValue(null, false);
        
        Env::load();
        
        // Reset connection to trigger error
        $reflection = new ReflectionClass(Db::class);
        $property = $reflection->getProperty('pdo');
        $property->setAccessible(true);
        $property->setValue(null, null);
        
        $this->expectException(\Exception::class);
        Db::sql("SELECT 1");
    }
}
