<?php

namespace App;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            $config = require __DIR__ . '/../config/database.php';
            
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset']
            );

            try {
                self::$connection = new PDO(
                    $dsn,
                    $config['username'],
                    $config['password'],
                    $config['options']
                );
            } catch (PDOException $e) {
                throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
            }
        }

        return self::$connection;
    }

    public static function runMigrations(): void
    {
        $pdo = self::getConnection();
        $migrationsPath = __DIR__ . '/../database/migrations';
        $migrations = glob($migrationsPath . '/*.sql');
        sort($migrations);

        foreach ($migrations as $migration) {
            $sql = file_get_contents($migration);
            $pdo->exec($sql);
        }
    }
}
