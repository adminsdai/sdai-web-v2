<?php
/**
 * SDAI CHILE — Escáner Ley 21.719
 * request-token.php: Genera y envía un OTP de 6 dígitos al email del solicitante.
 * Valida que el dominio del email coincida con el dominio a escanear.
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
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$email  = strtolower(trim($input['email']  ?? ''));
$domain = strtolower(trim($input['domain'] ?? ''));

// --- Validar email ---
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'El correo electrónico ingresado no es válido.']);
    exit;
}

// --- Extraer dominio del email ---
$emailDomain = substr($email, strpos($email, '@') + 1);

// --- Normalizar domain objetivo (quitar http/https/www/paths) ---
$domain = preg_replace('#^https?://#i', '', $domain);
$domain = preg_replace('#^www\.#i', '', $domain);
$domain = strtok($domain, '/');  // quitar paths
$domain = strtok($domain, '?');  // quitar querystrings

if (empty($domain)) {
    http_response_code(400);
    echo json_encode(['error' => 'Debes ingresar el dominio que deseas escanear.']);
    exit;
}

// --- Validar que el dominio del email coincida con el dominio objetivo ---
// Permitir email@empresa.cl → escanear empresa.cl o sub.empresa.cl
$domainRoot = preg_replace('/^www\./i', '', $domain);
if ($emailDomain !== $domainRoot && !str_ends_with($emailDomain, '.' . $domainRoot)) {
    http_response_code(403);
    echo json_encode([
        'error' => "Tu correo («{$email}») debe pertenecer al dominio que deseas escanear («{$domain}»). " .
                   "Solo el responsable del dominio puede solicitar el análisis."
    ]);
    exit;
}

// --- Obtener IP del solicitante ---
$ip = $_SERVER['HTTP_CF_CONNECTING_IP']
   ?? $_SERVER['HTTP_X_FORWARDED_FOR']
   ?? $_SERVER['REMOTE_ADDR']
   ?? '127.0.0.1';
if (strpos($ip, ',') !== false) $ip = trim(explode(',', $ip)[0]);
$userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 500);

try {
    $db = Database::connect();

    // --- Rate limiting: máx. 3 tokens por email en los últimos 15 min ---
    $rateStmt = $db->prepare(
        "SELECT COUNT(*) FROM scan_tokens
         WHERE email = :email AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)"
    );
    $rateStmt->execute([':email' => $email]);
    if ($rateStmt->fetchColumn() >= 3) {
        http_response_code(429);
        echo json_encode(['error' => 'Demasiadas solicitudes. Espera 15 minutos antes de pedir un nuevo código.']);
        exit;
    }

    // --- Generar token OTP de 6 dígitos ---
    $plainToken = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    $tokenHash  = hash('sha256', $email . $plainToken . date('Y-m-d'));  // hash con sal diaria

    $id = Database::uuidv4();
    $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    $stmt = $db->prepare("
        INSERT INTO scan_tokens (id, email, domain, token_hash, expires_at, ip_origin, user_agent)
        VALUES (:id, :email, :domain, :token_hash, :expires_at, :ip, :ua)
    ");
    $stmt->execute([
        ':id'         => $id,
        ':email'      => $email,
        ':domain'     => $domain,
        ':token_hash' => $tokenHash,
        ':expires_at' => $expiresAt,
        ':ip'         => $ip,
        ':ua'         => $userAgent,
    ]);

    // --- Enviar email con mail() nativo ---
    $to      = $email;
    $subject = "=?UTF-8?B?" . base64_encode("Tu código de acceso SDAI CHILE: {$plainToken}") . "?=";
    $message = "
<!DOCTYPE html>
<html lang='es'>
<head><meta charset='UTF-8'><title>Código de Acceso SDAI CHILE</title></head>
<body style='font-family:Arial,sans-serif;background:#0d1117;color:#e6edf3;padding:40px;'>
  <div style='max-width:520px;margin:0 auto;background:#161b22;border-radius:16px;padding:40px;border:1px solid #30363d;'>
    <div style='text-align:center;margin-bottom:32px;'>
      <img src='https://sdaichile.com/images/logo-sdai-chile.png' alt='SDAI CHILE' style='height:48px;' onerror='this.style.display=\"none\"'>
      <h1 style='color:#00a3ff;font-size:1.5rem;margin:16px 0 4px;'>SDAI CHILE</h1>
      <p style='color:#8b949e;font-size:0.85rem;margin:0;'>Security Data AI</p>
    </div>

    <h2 style='text-align:center;font-size:1.1rem;color:#e6edf3;margin-bottom:8px;'>
      Tu código de acceso al Escáner Ley N° 21.719
    </h2>
    <p style='text-align:center;color:#8b949e;font-size:0.9rem;margin-bottom:32px;'>
      Dominio autorizado para escanear: <strong style='color:#00a3ff;'>{$domain}</strong>
    </p>

    <div style='background:#0d1117;border:2px solid #00a3ff;border-radius:12px;padding:24px;text-align:center;margin-bottom:32px;'>
      <p style='color:#8b949e;font-size:0.85rem;margin:0 0 8px;'>Tu código de verificación es:</p>
      <div style='font-size:3rem;font-weight:800;letter-spacing:12px;color:#00D4AA;font-family:monospace;'>
        {$plainToken}
      </div>
      <p style='color:#8b949e;font-size:0.8rem;margin:12px 0 0;'>⏱ Válido por <strong>15 minutos</strong></p>
    </div>

    <div style='background:#1c2128;border-left:3px solid #f59e0b;border-radius:8px;padding:16px;margin-bottom:24px;'>
      <p style='color:#d1d5db;font-size:0.85rem;margin:0;line-height:1.6;'>
        <strong>⚠️ Aviso de seguridad:</strong> Este código fue solicitado para el dominio
        <strong>{$domain}</strong> desde la IP <code>{$ip}</code>.
        Si no fuiste tú, ignora este correo y el código expirará automáticamente.
      </p>
    </div>

    <div style='border-top:1px solid #30363d;padding-top:20px;text-align:center;'>
      <p style='color:#6e7681;font-size:0.8rem;line-height:1.6;margin:0;'>
        SDAI CHILE SpA — Escáner de Cumplimiento Ley N° 21.719<br>
        Conforme al Art. 14 de la Ley N° 21.719 sobre Protección de Datos Personales.<br>
        <a href='https://sdaichile.com' style='color:#00a3ff;text-decoration:none;'>sdaichile.com</a>
      </p>
    </div>
  </div>
</body>
</html>
";

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: SDAI CHILE <noreply@sdaichile.com>\r\n";
    $headers .= "Reply-To: contacto@sdaichile.com\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    $mailSent = @mail($to, $subject, $message, $headers);

    // --- Log en desarrollo (si mail() no está disponible localmente) ---
    if (!$mailSent) {
        error_log("[SDAI SCAN OTP] Token para {$email} (dominio: {$domain}): {$plainToken} | Expira: {$expiresAt}");
    }

    echo json_encode([
        'success' => true,
        'message' => "Código enviado a {$email}. Revisa tu bandeja de entrada (y spam).",
        'domain'  => $domain,
        // Solo en DEV: exponer token en log, nunca en respuesta HTTP
        'debug'   => (getenv('APP_ENV') === 'development' || !$mailSent)
                     ? ['note' => "mail() no configurado en local — revisa error_log del servidor PHP"]
                     : null,
    ]);

} catch (Exception $e) {
    error_log("[SDAI SCAN] request-token error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno al generar el token. Intenta nuevamente.']);
}
