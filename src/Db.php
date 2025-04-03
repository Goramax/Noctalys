<?php

namespace Goramax\NoctalysFramework;
use PDO;
use PDOException;
use Goramax\NoctalysFramework\ErrorHandler;

class Db{
    private static ?PDO $pdo = null;

    private static function connect(): void
    {
        if (self::$pdo !== null) {
            return;
        }

        $driver   = Env::get('DB_DRIVER', 'mysql');
        $host     = Env::get('DB_HOST', '127.0.0.1');
        $port     = Env::get('DB_PORT', '3306');
        $dbname   = Env::get('DB_NAME', '');
        $username = Env::get('DB_USER', 'root');
        $password = Env::get('DB_PASS', '');
        $charset  = Env::get('DB_CHARSET', 'utf8mb4');
        $options  = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        try {
            $dsn = match($driver) {
                'mysql' => "mysql:host=$host;dbname=$dbname" . ($port ? ";port=$port" : '') . ";charset=$charset",
                'pgsql' => "pgsql:host=$host;dbname=$dbname" . ($port ? ";port=$port" : ''),
                'sqlite' => "sqlite:$dbname",
                'sqlsrv' => "sqlsrv:Server=$host" . ($port ? ",$port" : '') . ";Database=$dbname",
                default => ErrorHandler::fatal("Unsupported database driver: $driver", "error"),
            };
            self::$pdo = new PDO($dsn, $username, $password, $options);

        } catch (PDOException $e) {
            ErrorHandler::fatal($e->getMessage(), "error", 2);
        }
    }

    /**
     * Execute a SQL query and return the result set
     * @param string $sql The SQL query to execute
     * @param array $params The parameters to bind to the query
     * @return array The result set as an associative array
     */
    public static function sql(string $sql, array $params = [])
    {
        self::connect();
        $stmt = self::$pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}