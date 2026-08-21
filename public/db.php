<?php
/**
 * SDAI CHILE - Conexión Segura de Base de Datos usando PDO
 *
 * Soporta dos motores con el MISMO código:
 *   - SQLite  -> para pruebas locales (un archivo, sin servidor). DB_DRIVER=sqlite
 *   - MySQL   -> para producción en Hostinger (por defecto).      DB_DRIVER=mysql
 *
 * La configuración se carga desde el archivo .env (ver .env.example).
 * Registro de consentimiento conforme a la Ley N° 21.719.
 */

class Database {
    private static $pdo = null;
    private static $driver = 'mysql';

    public static function connect() {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        // Cargar variables de entorno desde .env (raíz del proyecto o junto a este archivo)
        $envFile = __DIR__ . '/../.env';
        if (!file_exists($envFile)) {
            $envFile = __DIR__ . '/.env'; // fallback
        }

        $config = [
            'DB_DRIVER'  => 'mysql',
            'DB_HOST'    => 'localhost',
            'DB_NAME'    => 'sdai_db',
            'DB_USER'    => 'root',
            'DB_PASS'    => '',
            'DB_PORT'    => '3306',
            'SQLITE_PATH' => __DIR__ . '/sdai_local.sqlite',
        ];

        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                if (strpos($line, '=') === false) continue;
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim(trim($value), '"\'');
                if (array_key_exists($name, $config) || in_array($name, ['DATABASE_URL', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'])) {
                    if (($name === 'DB_DATABASE' || $name === 'DB_NAME') && !empty($value)) {
                        $config['DB_NAME'] = $value;
                    }
                    if (($name === 'DB_USERNAME' || $name === 'DB_USER') && !empty($value)) {
                        $config['DB_USER'] = $value;
                    }
                    if (($name === 'DB_PASSWORD' || $name === 'DB_PASS') && !empty($value)) {
                        $config['DB_PASS'] = $value;
                    }
                    $config[$name] = $value;
                }
            }
        }

        // Si se define DATABASE_URL (formato mysql://user:pass@host:port/dbname)
        if (!empty($config['DATABASE_URL'])) {
            $url = parse_url($config['DATABASE_URL']);
            if ($url) {
                if (isset($url['scheme']) && stripos($url['scheme'], 'sqlite') === 0) {
                    $config['DB_DRIVER'] = 'sqlite';
                } else {
                    $config['DB_DRIVER'] = 'mysql';
                    $config['DB_HOST'] = $url['host'] ?? $config['DB_HOST'];
                    $config['DB_PORT'] = $url['port'] ?? $config['DB_PORT'];
                    $config['DB_USER'] = $url['user'] ?? $config['DB_USER'];
                    $config['DB_PASS'] = $url['pass'] ?? $config['DB_PASS'];
                    $config['DB_NAME'] = isset($url['path']) ? ltrim($url['path'], '/') : $config['DB_NAME'];
                }
            }
        }

        self::$driver = strtolower($config['DB_DRIVER']) === 'sqlite' ? 'sqlite' : 'mysql';

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            if (self::$driver === 'sqlite') {
                self::$pdo = new PDO('sqlite:' . $config['SQLITE_PATH'], null, null, $options);
                self::$pdo->exec('PRAGMA journal_mode=WAL;');
            } else {
                $dsn = "mysql:host={$config['DB_HOST']};port={$config['DB_PORT']};dbname={$config['DB_NAME']};charset=utf8mb4";
                self::$pdo = new PDO($dsn, $config['DB_USER'], $config['DB_PASS'], $options);
            }
            self::ensureSchema();
            return self::$pdo;
        } catch (PDOException $e) {
            // Lanza el detalle al archivo de error log interno db_errors.log (que está protegido por .htaccess)
            throw new Exception("Error de conexión a la base de datos [PDO]: " . $e->getMessage());
        }
    }

    /**
     * Crea la tabla si no existe.
     * En SQLite se ejecuta siempre (cero configuración para pruebas locales).
     * En MySQL la tabla se crea con schema.sql; aquí solo se garantiza en SQLite.
     */
    private static function ensureSchema() {
        if (self::$driver !== 'sqlite') {
            return; // En MySQL usar schema.sql (ver carpeta raíz del proyecto)
        }
        self::$pdo->exec("CREATE TABLE IF NOT EXISTS contact_messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL,
            message TEXT NOT NULL,
            status TEXT NOT NULL DEFAULT 'PENDING',
            consentGiven INTEGER NOT NULL DEFAULT 0,
            consentText TEXT,
            policyVersion TEXT,
            ipAddress TEXT,
            userAgent TEXT,
            originUrl TEXT,
            consentTimestampUtc TEXT,
            recordHash TEXT,
            createdAt TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");
    }

    /**
     * Inserta un nuevo mensaje de contacto con registro de consentimiento (Ley N° 21.719).
     * $meta admite: policyVersion, userAgent, originUrl, consentTimestampUtc, recordHash.
     */
    public static function createMessage($name, $email, $message, $consentGiven, $consentText, $ipAddress = null, $meta = []) {
        $db = self::connect();
        $stmt = $db->prepare(
            "INSERT INTO contact_messages
                (name, email, message, consentGiven, consentText, ipAddress, policyVersion, userAgent, originUrl, consentTimestampUtc, recordHash)
             VALUES
                (:name, :email, :message, :consentGiven, :consentText, :ip, :policyVersion, :userAgent, :originUrl, :tsUtc, :recordHash)"
        );
        return $stmt->execute([
            ':name'          => $name,
            ':email'         => $email,
            ':message'       => $message,
            ':consentGiven'  => $consentGiven ? 1 : 0,
            ':consentText'   => $consentText,
            ':ip'            => $ipAddress,
            ':policyVersion' => $meta['policyVersion'] ?? null,
            ':userAgent'     => $meta['userAgent'] ?? null,
            ':originUrl'     => $meta['originUrl'] ?? null,
            ':tsUtc'         => $meta['consentTimestampUtc'] ?? null,
            ':recordHash'    => $meta['recordHash'] ?? null,
        ]);
    }

    public static function getAllMessages() {
        $db = self::connect();
        $stmt = $db->query("SELECT * FROM contact_messages ORDER BY createdAt DESC");
        return $stmt->fetchAll();
    }

    public static function updateMessageStatus($id, $status) {
        $allowedStatuses = ['PENDING', 'CONTACTED', 'ARCHIVED'];
        if (!in_array($status, $allowedStatuses)) {
            throw new InvalidArgumentException("Estado no permitido.");
        }
        $db = self::connect();
        $stmt = $db->prepare("UPDATE contact_messages SET status = :status WHERE id = :id");
        return $stmt->execute([':status' => $status, ':id' => $id]);
    }

    public static function deleteMessage($id) {
        $db = self::connect();
        $stmt = $db->prepare("DELETE FROM contact_messages WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
