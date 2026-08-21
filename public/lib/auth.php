<?php
/**
 * SDAI CHILE SCAN - Gestión de Sesiones y JWT en PHP puro
 * Sin dependencias externas de Composer
 */

require_once __DIR__ . '/db.php';

class Auth {
    private static function getJwtSecret() {
        Database::connect(); // Para asegurar que las variables de .env estén cargadas
        return getenv('JWT_SECRET') ?: ($_ENV['JWT_SECRET'] ?? 'super-secret-fallback-key-change-me');
    }

    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }

    public static function createToken($payload) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload['exp'] = time() + (60 * 60 * 8); // 8 horas
        $payload['iat'] = time();
        $payloadJson = json_encode($payload);

        $base64UrlHeader = self::base64UrlEncode($header);
        $base64UrlPayload = self::base64UrlEncode($payloadJson);

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::getJwtSecret(), true);
        $base64UrlSignature = self::base64UrlEncode($signature);

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    public static function verifyToken($token) {
        if (!$token) return null;
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;

        list($base64UrlHeader, $base64UrlPayload, $base64UrlSignature) = $parts;

        $signature = self::base64UrlDecode($base64UrlSignature);
        $expectedSignature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::getJwtSecret(), true);

        if (!hash_equals($signature, $expectedSignature)) {
            return null;
        }

        $payload = json_decode(self::base64UrlDecode($base64UrlPayload), true);
        if (!$payload || !isset($payload['exp']) || $payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }

    public static function setSessionCookie($payload) {
        $token = self::createToken($payload);
        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
        
        setcookie('admin_session', $token, [
            'expires' => time() + (60 * 60 * 8),
            'path' => '/',
            'domain' => '',
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }

    public static function getSession() {
        $token = $_COOKIE['admin_session'] ?? null;
        return self::verifyToken($token);
    }

    public static function clearSessionCookie() {
        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
        setcookie('admin_session', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
}
