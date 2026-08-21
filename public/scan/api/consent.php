<?php
/**
 * SDAI CHILE — Escáner Ley 21.719
 * consent.php: Registra log de consentimiento/auditoría en MariaDB.
 * Acepta email del titular verificado (post token OTP).
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/db.php';

// Obtener sesión activa si existe
$session = Auth::getSession();
$userId  = $session['user_id'] ?? 'public_user';

$input         = json_decode(file_get_contents('php://input'), true) ?: [];
$urlScanned    = trim($input['url_scanned']  ?? 'unknown');
$userAgent     = trim($input['user_agent']   ?? ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'));
$policyVersion = trim($input['policy_version'] ?? 'v1.0');
$email         = strtolower(trim($input['email'] ?? ''));

// Obtener IP (Cloudflare / Proxy / Direct)
$ip = $_SERVER['HTTP_CF_CONNECTING_IP']
   ?? $_SERVER['HTTP_X_FORWARDED_FOR']
   ?? $_SERVER['REMOTE_ADDR']
   ?? '127.0.0.1';
if (strpos($ip, ',') !== false) $ip = trim(explode(',', $ip)[0]);

// Anonimizar IP
$ipParts = explode('.', $ip);
if (count($ipParts) === 4) {
    $ipParts[3] = '***';
    $ip = implode('.', $ipParts);
}

try {
    $db = Database::connect();
    $id = Database::uuidv4();

    $stmt = $db->prepare("
        INSERT INTO consent_logs (id, ip_anonymized, user_agent, url_scanned, action, user_id, email, policy_version)
        VALUES (:id, :ip, :ua, :url, 'AGREED_TO_POLICIES', :uid, :email, :policy)
    ");
    $stmt->execute([
        ':id'     => $id,
        ':ip'     => $ip,
        ':ua'     => $userAgent,
        ':url'    => $urlScanned,
        ':uid'    => $userId,
        ':email'  => $email ?: null,
        ':policy' => $policyVersion,
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Consentimiento registrado en MariaDB (Ley 21.719)',
        'log_id'  => $id,
    ]);
} catch (Exception $e) {
    error_log("Consent recording error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error al registrar consentimiento: ' . $e->getMessage()]);
}
