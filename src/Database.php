<?php

final class Database
{
    private static ?PDO $pdo = null;
    private static ?array $config = null;

    public static function config(): array
    {
        if (self::$config === null) {
            self::$config = require __DIR__ . '/../config/config.php';
        }
        return self::$config;
    }

    public static function get(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $config = self::config();
        $db = $config['db'];

        if ($db['driver'] === 'mysql') {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                $db['host'],
                $db['port'],
                $db['name']
            );
            self::$pdo = new PDO($dsn, $db['user'], $db['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            self::migrate(self::$pdo, 'mysql');
        } else {
            $path = $db['sqlite_path'];
            $dir = dirname($path);
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            self::$pdo = new PDO('sqlite:' . $path, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            self::$pdo->exec('PRAGMA foreign_keys = ON');
            self::migrate(self::$pdo, 'sqlite');
        }

        return self::$pdo;
    }

    private static function migrate(PDO $pdo, string $driver): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $file = $driver === 'mysql'
            ? __DIR__ . '/../database/schema_mysql.sql'
            : __DIR__ . '/../database/schema_sqlite.sql';
        $sql = file_get_contents($file);
        if ($driver === 'mysql') {
            foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
                $pdo->exec($stmt);
            }
        } else {
            $pdo->exec($sql);
        }
        $done = true;
    }

    /** Usado pelos testes para forçar um banco novo em memória. */
    public static function resetForTests(): PDO
    {
        self::$pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        self::$pdo->exec('PRAGMA foreign_keys = ON');
        $sql = file_get_contents(__DIR__ . '/../database/schema_sqlite.sql');
        self::$pdo->exec($sql);
        return self::$pdo;
    }
}
