<?php
/**
 * SDAI Chile - Contact Form Handler
 * Agnostic PHP script for sending emails without third-party services.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

/**
 * Obtiene la IP real del cliente considerando proxys/CDN (Hostinger, Cloudflare).
 * REMOTE_ADDR por sí solo suele entregar la IP del proxy, no la del visitante.
 */
function sdai_client_ip() {
    $candidates = [
        $_SERVER['HTTP_CF_CONNECTING_IP'] ?? null,   // Cloudflare
        isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]) : null,
        $_SERVER['HTTP_X_REAL_IP'] ?? null,
        $_SERVER['REMOTE_ADDR'] ?? null,
    ];
    foreach ($candidates as $ip) {
        if ($ip && filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Registra el consentimiento en una bitácora append-only (JSONL) con hash SHA-256
 * encadenado al registro anterior. Funciona SIEMPRE, exista o no la base de datos,
 * y permite detectar manipulaciones (principio de responsabilidad, Ley N° 21.719).
 * Devuelve el hash del registro escrito (o null si falla la escritura).
 */
function sdai_append_consent_ledger(array $entry) {
    $ledgerFile = __DIR__ . '/consent_log.jsonl';

    // Recuperar el hash del último registro para encadenar
    $prevHash = 'GENESIS';
    if (is_file($ledgerFile) && filesize($ledgerFile) > 0) {
        $lines = file($ledgerFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $last = end($lines);
        $decoded = json_decode($last, true);
        if (is_array($decoded) && !empty($decoded['hash'])) {
            $prevHash = $decoded['hash'];
        }
    }

    $entry['prevHash'] = $prevHash;
    // El hash cubre todo el contenido del registro + el hash anterior (cadena de integridad)
    $entry['hash'] = hash('sha256', $prevHash . '|' . json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    $line = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    $ok = @file_put_contents($ledgerFile, $line, FILE_APPEND | LOCK_EX);

    return $ok !== false ? $entry['hash'] : null;
}

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON data from fetch
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // If not JSON, try regular POST (for traditional form submits)
    if (!$data) {
        $data = $_POST;
    }

    $name = strip_tags(trim($data['name'] ?? ''));
    $email = filter_var(trim($data['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $message = strip_tags(trim($data['message'] ?? ''));
    
    // Capturar y validar campos de consentimiento (Ley N° 21.719)
    $consentGiven = isset($data['consent']) && ($data['consent'] === true || $data['consent'] === 'on' || $data['consent'] === 'true' || $data['consent'] === 1);
    $consentText = strip_tags(trim($data['consentText'] ?? 'Acepto la Política de Privacidad de SDAI Chile conforme a la Ley N° 21.719.'));
    // Versión exacta de la política aceptada (evidencia de QUÉ texto se consintió)
    $policyVersion = strip_tags(trim($data['policyVersion'] ?? 'no-especificada'));

    if (empty($name) || empty($email) || empty($message)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Todos los campos son obligatorios.']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Email no válido.']);
        exit;
    }

    if (!$consentGiven) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Debe aceptar la política de privacidad y otorgar su consentimiento para enviar el mensaje.']);
        exit;
    }

    // Evidencia técnica del acto de consentimiento
    $ipAddress = sdai_client_ip();
    $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
    $originUrl = strip_tags(trim($data['pageUrl'] ?? ($_SERVER['HTTP_REFERER'] ?? '')));
    $timestampUtc = gmdate('Y-m-d\TH:i:s\Z');   // UTC explícito (no hora del servidor)
    $timestamp = $timestampUtc;                  // usado en correos y logs

    // 1) Bitácora append-only con hash encadenado (siempre se intenta, no depende de la BD)
    $ledgerHash = sdai_append_consent_ledger([
        'timestampUtc'  => $timestampUtc,
        'name'          => $name,
        'email'         => $email,
        'consentGiven'  => $consentGiven,
        'consentText'   => $consentText,
        'policyVersion' => $policyVersion,
        'ip'            => $ipAddress,
        'userAgent'     => $userAgent,
        'originUrl'     => $originUrl,
        'source'        => 'formulario-contacto-web',
    ]);

    // 2) Persistencia en base de datos (MySQL en producción / SQLite en local). Opcional: si falla, la bitácora ya dejó el registro.
    $db_saved = false;
    $db_error_detail = "";
    try {
        require_once __DIR__ . '/db.php';
        Database::createMessage($name, $email, $message, $consentGiven, $consentText, $ipAddress, [
            'policyVersion'       => $policyVersion,
            'userAgent'           => $userAgent,
            'originUrl'           => $originUrl,
            'consentTimestampUtc' => $timestampUtc,
            'recordHash'          => $ledgerHash,
        ]);
        $db_saved = true;
    } catch (Exception $e) {
        // Registrar fallo de BD en archivo para no bloquear el envío de correo
        $db_error_detail = $e->getMessage();
        $db_log_file = __DIR__ . '/db_errors.log';
        file_put_contents($db_log_file, "[" . $timestamp . "] DB Save Error: " . $db_error_detail . "\n", FILE_APPEND);
    }

    // Email Configuration - Internal Notification to SDAI
    $to_admin = "contacto@sdaichile.com";
    $subject_admin = "Nuevo lead registrado: $name";
    
    $email_content_admin = "Has recibido un nuevo mensaje desde la web SDAI Chile.\n\n";
    $email_content_admin .= "Nombre: $name\n";
    $email_content_admin .= "Email: $email\n\n";
    $email_content_admin .= "Mensaje:\n$message\n\n";
    $email_content_admin .= "--- EVIDENCIA DE CONSENTIMIENTO (LEY N° 21.719) ---\n";
    $email_content_admin .= "Consentimiento: OTORGADO\n";
    $email_content_admin .= "Fecha/Hora (UTC): $timestamp\n";
    $email_content_admin .= "Dirección IP: $ipAddress\n";
    $email_content_admin .= "Versión de política: $policyVersion\n";
    $email_content_admin .= "Navegador (User-Agent): $userAgent\n";
    $email_content_admin .= "Página de origen: $originUrl\n";
    $email_content_admin .= "Texto aceptado: $consentText\n";
    $email_content_admin .= "Hash de integridad (bitácora): " . ($ledgerHash ?? 'no-registrado') . "\n";
    $email_content_admin .= "---------------------------------------------------\n";

    $headers_admin = "From: webmaster@sdaichile.com\r\n";
    $headers_admin .= "Reply-To: $email\r\n";
    $headers_admin .= "X-Mailer: PHP/" . phpversion();

    // Email Configuration - Confirmation Copy to Client
    $subject_client = "Copia de contacto y Registro de Consentimiento - SDAI CHILE";
    
    $email_content_client = "Hola $name,\n\n";
    $email_content_client .= "Confirmamos que hemos recibido tu consulta. De acuerdo con las regulaciones de la Ley N° 21.719 de Protección de Datos Personales en Chile, te enviamos este comprobante que sirve como acuse de recibo y respaldo de tu autorización de tratamiento de datos:\n\n";
    $email_content_client .= "--- EVIDENCIA DE REGISTRO DE CONSENTIMIENTO ---\n";
    $email_content_client .= "Fecha y Hora: $timestamp\n";
    $email_content_client .= "Dirección IP: $ipAddress\n";
    $email_content_client .= "Consentimiento: Otorgado de forma explícita e inequívoca al marcar la casilla de aceptación.\n";
    $email_content_client .= "Texto legal aceptado:\n\"$consentText\"\n";
    $email_content_client .= "-----------------------------------------------\n\n";
    $email_content_client .= "Copia de tu mensaje enviado:\n";
    $email_content_client .= "-----------------------------------------------\n";
    $email_content_client .= "Nombre: $name\n";
    $email_content_client .= "Email: $email\n";
    $email_content_client .= "Mensaje:\n$message\n";
    $email_content_client .= "-----------------------------------------------\n\n";
    $email_content_client .= "Nos pondremos en contacto contigo a la brevedad para abordar tu estrategia.\n\n";
    $email_content_client .= "Atentamente,\nEl equipo de SDAI CHILE SpA\ncontacto@sdaichile.com\n";

    $headers_client = "From: contacto@sdaichile.com\r\n";
    $headers_client .= "Reply-To: contacto@sdaichile.com\r\n";
    $headers_client .= "X-Mailer: PHP/" . phpversion();

    // Send emails
    $mail_admin_sent = mail($to_admin, $subject_admin, $email_content_admin, $headers_admin);
    $mail_client_sent = mail($email, $subject_client, $email_content_client, $headers_client);

    if ($mail_admin_sent) {
        echo json_encode([
            'status' => 'success', 
            'message' => 'Mensaje enviado y registrado correctamente.',
            'db_saved' => $db_saved,
            'client_notified' => $mail_client_sent,
            'db_error' => !$db_saved ? 'Fallo al guardar en base de datos. Ver db_errors.log.' : ''
        ]);
    } else {
        // Fallback for local testing
        $log_file = __DIR__ . '/contact_logs.txt';
        $log_entry = "--- " . $timestamp . " [ADMIN NOTIFICATION] ---\n" . $email_content_admin . "\n";
        $log_entry .= "--- " . $timestamp . " [CLIENT CONFIRMATION] ---\n" . $email_content_client . "\n";
        file_put_contents($log_file, $log_entry, FILE_APPEND);
        
        echo json_encode([
            'status' => 'success', 
            'message' => 'Mensaje procesado (Simulado localmente). Los correos se guardaron en contact_logs.txt ya que mail() no está configurado.',
            'db_saved' => $db_saved,
            'client_notified' => true,
            'db_error' => !$db_saved ? 'Fallo al guardar en base de datos. Ver db_errors.log.' : ''
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
}
