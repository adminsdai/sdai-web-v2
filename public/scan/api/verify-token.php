<?php
/**
 * SDAI CHILE — Escáner Ley 21.719
 * verify-token.php: Valida el OTP ingresado por el usuario.
 * En éxito: marca token como usado, registra consentimiento en consent_logs,
 * retorna session_key temporal para autorizar el escaneo.
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

require_once __DIR__ . '/../../lib/db.php';

// --- Leer input ---
$input    = json_decode(file_get_contents('php://input'), true) ?: [];
$email    = strtolower(trim($input['email']    ?? ''));
$token    = trim($input['token']    ?? '');
$domain   = strtolower(trim($input['domain']   ?? ''));
$userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 500);

if (!$email || !$token || !$domain) {
    http_response_code(400);
    echo json_encode(['error' => 'Faltan parámetros requeridos: email, token, domain.']);
    exit;
}

// Normalizar token: solo dígitos, máx 6
$token = preg_replace('/\D/', '', $token);
if (strlen($token) !== 6) {
    http_response_code(400);
    echo json_encode(['error' => 'El código debe ser exactamente 6 dígitos numéricos.']);
    exit;
}

// --- IP del solicitante ---
$ip = $_SERVER['HTTP_CF_CONNECTING_IP']
   ?? $_SERVER['HTTP_X_FORWARDED_FOR']
   ?? $_SERVER['REMOTE_ADDR']
   ?? '127.0.0.1';
if (strpos($ip, ',') !== false) $ip = trim(explode(',', $ip)[0]);

// Anonimizar IP para consent_logs
$ipParts = explode('.', $ip);
$ipAnon  = (count($ipParts) === 4) ? implode('.', array_slice($ipParts, 0, 3)) . '.***' : $ip;

try {
    $db = Database::connect();

    // --- Calcular hash esperado ---
    $expectedHash = hash('sha256', $email . $token . date('Y-m-d'));

    // --- Buscar token activo y no usado ---
    $stmt = $db->prepare("
        SELECT id, attempts, used, expires_at, domain
        FROM scan_tokens
        WHERE email = :email
          AND token_hash = :hash
          AND domain = :domain
          AND used = 0
          AND expires_at > NOW()
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([
        ':email'  => $email,
        ':hash'   => $expectedHash,
        ':domain' => $domain,
    ]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // --- Token no encontrado o expirado ---
    if (!$row) {
        // Incrementar intentos en cualquier token reciente de este email/domain
        $incStmt = $db->prepare("
            UPDATE scan_tokens SET attempts = attempts + 1
            WHERE email = :email AND domain = :domain AND used = 0 AND expires_at > NOW()
            ORDER BY created_at DESC LIMIT 1
        ");
        $incStmt->execute([':email' => $email, ':domain' => $domain]);

        // Verificar si superó límite de intentos
        $attemptsStmt = $db->prepare("
            SELECT attempts FROM scan_tokens
            WHERE email = :email AND domain = :domain AND used = 0 AND expires_at > NOW()
            ORDER BY created_at DESC LIMIT 1
        ");
        $attemptsStmt->execute([':email' => $email, ':domain' => $domain]);
        $currentAttempts = (int)($attemptsStmt->fetchColumn() ?: 0);

        if ($currentAttempts >= 5) {
            http_response_code(429);
            echo json_encode([
                'error'    => 'Has superado el máximo de intentos. Solicita un nuevo código.',
                'blocked'  => true,
                'attempts' => $currentAttempts,
            ]);
        } else {
            http_response_code(401);
            echo json_encode([
                'error'    => 'Código incorrecto o expirado. Verifica e intenta de nuevo.',
                'attempts' => $currentAttempts,
                'remaining'=> 5 - $currentAttempts,
            ]);
        }
        exit;
    }

    // --- Verificar intentos previos ---
    if ((int)$row['attempts'] >= 5) {
        http_response_code(429);
        echo json_encode(['error' => 'Token bloqueado por exceso de intentos. Solicita un código nuevo.', 'blocked' => true]);
        exit;
    }

    // --- Marcar token como usado ---
    $useStmt = $db->prepare("UPDATE scan_tokens SET used = 1 WHERE id = :id");
    $useStmt->execute([':id' => $row['id']]);

    // --- Generar session_key temporal (UUID) ---
    $sessionKey = Database::uuidv4();

    // --- Registrar consentimiento en consent_logs (NO BLOQUEA si falla) ---
    $logId = null;
    try {
        $logId = Database::uuidv4();

        // Verificar si la columna email existe; si no, agregarla automáticamente
        $cols = $db->query("SHOW COLUMNS FROM consent_logs LIKE 'email'")->fetchAll();
        if (empty($cols)) {
            $db->exec("ALTER TABLE consent_logs ADD COLUMN `email` VARCHAR(255) NULL AFTER `user_id`");
            error_log("[SDAI SCAN] Auto-migración: columna email agregada a consent_logs.");
        }

        $logStmt = $db->prepare("
            INSERT INTO consent_logs
                (id, ip_anonymized, user_agent, url_scanned, action, user_id, email, policy_version)
            VALUES
                (:id, :ip, :ua, :url, 'SCAN_TOKEN_VERIFIED', 'public_user', :email, 'v1.0')
        ");
        $logStmt->execute([
            ':id'    => $logId,
            ':ip'    => $ipAnon,
            ':ua'    => $userAgent,
            ':url'   => $domain,
            ':email' => $email,
        ]);
    } catch (Exception $logErr) {
        // El fallo de auditoría NO bloquea la verificación del token
        error_log("[SDAI SCAN] consent_logs insert falló (no bloquea): " . $logErr->getMessage());
        $logId = null;
    }

    echo json_encode([
        'success'     => true,
        'session_key' => $sessionKey,
        'domain'      => $domain,
        'email'       => $email,
        'ip_shown'    => $ipAnon,
        'verified_at' => date('Y-m-d H:i:s'),
        'log_id'      => $logId,
        'message'     => 'Token verificado correctamente. Procede a aceptar el consentimiento.',
    ]);

} catch (Exception $e) {
    $errMsg = $e->getMessage();
    error_log("[SDAI SCAN] verify-token error: " . $errMsg);
    http_response_code(500);
    echo json_encode([
        'error' => 'Error interno al verificar el token.',
        'debug' => (strpos($errMsg, 'scan_tokens') !== false)
                   ? 'La tabla scan_tokens no existe en la BD. Ejecuta migrate_produccion.sql en phpMyAdmin.'
                   : null,
    ]);
}

