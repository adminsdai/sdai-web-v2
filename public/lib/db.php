<?php
/**
 * SDAI CHILE SCAN - Conexión Segura MySQL con PDO
 * Compatible con Hostinger hPanel / phpMyAdmin (.env)
 */

class Database {
    private static $pdo = null;

    public static function connect() {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        // Cargar variables desde .env
        $envFiles = [
            __DIR__ . '/../.env',
            __DIR__ . '/../../.env',
            __DIR__ . '/.env'
        ];

        $config = [
            'DB_HOST' => 'localhost',
            'DB_PORT' => '3306',
            'DB_NAME' => 'sdai_scan',
            'DB_USER' => 'root',
            'DB_PASSWORD' => ''
        ];

        foreach ($envFiles as $envFile) {
            if (file_exists($envFile)) {
                $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    if (strpos(trim($line), '#') === 0) continue;
                    if (strpos($line, '=') === false) continue;
                    list($name, $value) = explode('=', $line, 2);
                    $name = trim($name);
                    $value = trim(trim($value), '"\'');
                    if (array_key_exists($name, $config) || in_array($name, ['ADMIN_PASSWORD', 'JWT_SECRET', 'DB_PASS', 'DB_DATABASE', 'DB_USERNAME'])) {
                        if (($name === 'DB_PASS' || $name === 'DB_PASSWORD') && !empty($value)) {
                            $config['DB_PASSWORD'] = $value;
                        }
                        if (($name === 'DB_DATABASE' || $name === 'DB_NAME') && !empty($value)) {
                            $config['DB_NAME'] = $value;
                        }
                        if (($name === 'DB_USERNAME' || $name === 'DB_USER') && !empty($value)) {
                            $config['DB_USER'] = $value;
                        }
                        $config[$name] = $value;
                        putenv("$name=$value");
                        $_ENV[$name] = $value;
                    }
                }
                break;
            }
        }

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $dsn = "mysql:host={$config['DB_HOST']};port={$config['DB_PORT']};dbname={$config['DB_NAME']};charset=utf8mb4";
            self::$pdo = new PDO($dsn, $config['DB_USER'], $config['DB_PASSWORD'], $options);
            return self::$pdo;
        } catch (PDOException $e) {
            error_log("Error de conexión PDO MySQL: " . $e->getMessage());
            header('Content-Type: application/json', true, 500);
            echo json_encode(['error' => 'Error de conexión a MySQL en Hostinger: ' . $e->getMessage()]);
            exit;
        }
    }

    public static function uuidv4() {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
