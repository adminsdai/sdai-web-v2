<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/db.php';

// Obtener sesión si existe (admin login)
$session = Auth::getSession();
$userId = $session['user_id'] ?? 'public_user';

// --- Validar parámetros obligatorios ---
$url        = trim($_GET['url']         ?? '');
$sessionKey = trim($_GET['session_key'] ?? '');
$authEmail  = trim($_GET['email']       ?? '');

if (empty($url)) {
    http_response_code(400);
    echo json_encode(['error' => 'Falta el parámetro de URL']);
    exit;
}

// --- Para usuarios públicos (no admin), exigir session_key válida ---
if ($userId === 'public_user' && empty($sessionKey)) {
    http_response_code(401);
    echo json_encode(['error' => 'Acceso no autorizado. Completa el proceso de verificación por email primero.']);
    exit;
}

// --- Normalizar URL objetivo ---
if (!preg_match('/^https?:\/\//i', $url)) {
    $url = 'https://' . $url;
}

// --- Si hay session_key, validar que el dominio a escanear coincida con el autorizado ---
if ($userId === 'public_user' && !empty($authEmail)) {
    $emailDomain = strtolower(substr($authEmail, strpos($authEmail, '@') + 1));
    $parsedTarget = parse_url($url);
    $targetHost = preg_replace('/^www\./i', '', strtolower($parsedTarget['host'] ?? ''));

    if ($targetHost !== $emailDomain && !str_ends_with($targetHost, '.' . $emailDomain)) {
        http_response_code(403);
        echo json_encode([
            'error' => "Solo puedes escanear el dominio «{$emailDomain}» con el acceso autorizado para «{$authEmail}».",
            'authorized_domain' => $emailDomain,
            'requested_domain'  => $targetHost,
        ]);
        exit;
    }
}


$startScanTime = microtime(true);

try {
    $parsedBase = parse_url($url);
    if (!$parsedBase || !isset($parsedBase['host'])) {
        throw new Exception("URL inválida: {$url}");
    }

    $baseHost = preg_replace('/^www\./i', '', strtolower($parsedBase['host']));
    $baseOrigin = ($parsedBase['scheme'] ?? 'https') . '://' . $parsedBase['host'] . (isset($parsedBase['port']) ? ':' . $parsedBase['port'] : '');
    $baseProtocol = ($parsedBase['scheme'] ?? 'https') . '://';

    $visited = [];
    $queue = [$baseOrigin];

    $allScannedPages = [];
    $allForms = [];
    $allTrackers = [];
    $allCookies = [];
    $allPrivacyLinks = [];
    $allTermsLinks = [];
    $hasCookieBannerIndicator = false;
    $pageHeadersStatus = [];

    $maxPages = 15;

    while (!empty($queue) && count($visited) < $maxPages) {
        $currentUrl = array_shift($queue);
        if (isset($visited[$currentUrl])) continue;
        $visited[$currentUrl] = true;
        $allScannedPages[] = $currentUrl;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $currentUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
        curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
        curl_setopt($ch, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);

        $response = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            $pageHeadersStatus[$currentUrl] = ['error' => $curlError];
            continue;
        }

        $headerStr = substr($response, 0, $headerSize);
        $html = substr($response, $headerSize);

        // Extraer únicamente el último bloque de cabeceras HTTP (respuesta final 200 OK tras redirecciones)
        $headerBlocks = array_filter(explode("\r\n\r\n", trim($headerStr)));
        $finalHeaderBlock = !empty($headerBlocks) ? end($headerBlocks) : $headerStr;

        // Parse headers lowercase
        $headers = [];
        $setCookieHeaders = [];
        foreach (explode("\r\n", $finalHeaderBlock) as $line) {
            if (strpos($line, ':') !== false) {
                list($key, $val) = explode(':', $line, 2);
                $keyLow = strtolower(trim($key));
                $valTrim = trim($val);
                $headers[$keyLow] = $valTrim;
                if ($keyLow === 'set-cookie') {
                    $setCookieHeaders[] = $valTrim;
                }
            }
        }

        // Security headers
        $pageHsts = isset($headers['strict-transport-security']);
        $pageCsp = isset($headers['content-security-policy']);
        $pageXFrame = isset($headers['x-frame-options']);
        $pageXContentType = isset($headers['x-content-type-options']);
        $pageReferrer = isset($headers['referrer-policy']);

        $pageHeadersStatus[$currentUrl] = [
            'hsts' => $pageHsts,
            'csp' => $pageCsp,
            'xFrameOptions' => $pageXFrame,
            'xContentTypeOptions' => $pageXContentType,
            'referrerPolicy' => $pageReferrer,
            'values' => [
                'hsts' => $headers['strict-transport-security'] ?? null,
                'csp' => $headers['content-security-policy'] ?? null,
                'xFrameOptions' => $headers['x-frame-options'] ?? null,
                'xContentTypeOptions' => $headers['x-content-type-options'] ?? null,
                'referrerPolicy' => $headers['referrer-policy'] ?? null
            ]
        ];

        // Parse Cookies
        foreach ($setCookieHeaders as $cookieStr) {
            $parts = explode(';', $cookieStr);
            $firstPart = explode('=', $parts[0], 2);
            if (empty($firstPart[0])) continue;
            $name = trim($firstPart[0]);

            $secure = false;
            $httpOnly = false;
            $sameSite = 'none';

            foreach ($parts as $p) {
                $pLow = strtolower(trim($p));
                if ($pLow === 'secure') $secure = true;
                if ($pLow === 'httponly') $httpOnly = true;
                if (strpos($pLow, 'samesite=') === 0) {
                    $sameSite = trim(substr($p, strpos($p, '=') + 1));
                }
            }

            $exists = false;
            foreach ($allCookies as $c) {
                if ($c['name'] === $name) { $exists = true; break; }
            }
            if (!$exists) {
                $allCookies[] = [
                    'name' => $name,
                    'secure' => $secure,
                    'httpOnly' => $httpOnly,
                    'sameSite' => $sameSite,
                    'pageUrl' => $currentUrl
                ];
            }
        }

        // Parse HTML with DOMDocument
        if (!empty(trim($html))) {
            libxml_use_internal_errors(true);
            $dom = new DOMDocument();
            @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
            $xpath = new DOMXPath($dom);
            libxml_clear_errors();

            // Links
            $links = $xpath->query('//a[@href]');
            foreach ($links as $el) {
                $href = $el->getAttribute('href');
                $text = trim(strtolower($el->textContent));
                if (empty($href)) continue;

                $normalized = null;
                if (preg_match('/^https?:\/\//i', $href)) {
                    $normalized = parse_url($href);
                } elseif (strpos($href, '//') === 0) {
                    $normalized = parse_url($baseProtocol . ltrim($href, '/'));
                } elseif (!preg_match('/^(javascript|mailto|tel|#)/i', $href)) {
                    $normalizedUrlStr = rtrim($baseOrigin, '/') . '/' . ltrim($href, '/');
                    $normalized = parse_url($normalizedUrlStr);
                }

                if ($normalized && isset($normalized['host'])) {
                    $normalizedHost = preg_replace('/^www\./i', '', strtolower($normalized['host']));
                    $normalizedStr = ($normalized['scheme'] ?? 'https') . '://' . $normalized['host'] . ($normalized['path'] ?? '');
                    $normalizedStr = rtrim($normalizedStr, '/');

                    if ($normalizedHost === $baseHost && !isset($visited[$normalizedStr]) && !in_array($normalizedStr, $queue)) {
                        $queue[] = $normalizedStr;
                    }

                    $hrefLow = strtolower($href);
                    $isPrivacy = (strpos($hrefLow, 'privac') !== false || strpos($hrefLow, 'privacy') !== false || strpos($hrefLow, 'legal') !== false || strpos($hrefLow, 'politica') !== false || strpos($text, 'privacid') !== false || strpos($text, 'privacy') !== false || strpos($text, 'política') !== false || strpos($text, 'legal') !== false);
                    $isTerms = (strpos($hrefLow, 'termino') !== false || strpos($hrefLow, 'condic') !== false || strpos($hrefLow, 'terms') !== false || strpos($hrefLow, 'condition') !== false || strpos($text, 'término') !== false || strpos($text, 'condición') !== false || strpos($text, 'terms') !== false || strpos($text, 'condiciones') !== false);

                    if ($isPrivacy) {
                        $ex = false;
                        foreach ($allPrivacyLinks as $l) { if ($l['href'] === $href) { $ex = true; break; } }
                        if (!$ex) $allPrivacyLinks[] = ['href' => $href, 'text' => trim($el->textContent), 'pageUrl' => $currentUrl];
                    }
                    if ($isTerms) {
                        $ex = false;
                        foreach ($allTermsLinks as $l) { if ($l['href'] === $href) { $ex = true; break; } }
                        if (!$ex) $allTermsLinks[] = ['href' => $href, 'text' => trim($el->textContent), 'pageUrl' => $currentUrl];
                    }
                }
            }

            // Forms
            $forms = $xpath->query('//form');
            foreach ($forms as $formIdx => $formEl) {
                $id = $formEl->getAttribute('id') ?: "form-{$formIdx}";
                $class = $formEl->getAttribute('class') ?: '';
                $action = $formEl->getAttribute('action') ?: '';

                // Excluir formularios de herramientas, autenticación interna, OTP o búsqueda (no son formularios de contacto/captura de prospectos)
                if (preg_match('/(search|auth|token|email-form|scan-form|login|logout)/i', $id . ' ' . $class)) {
                    continue;
                }

                $inputs = [];

                $inputNodes = $xpath->query('.//input | .//select | .//textarea', $formEl);
                foreach ($inputNodes as $inpEl) {
                    $type = $inpEl->getAttribute('type') ?: strtolower($inpEl->nodeName);
                    $name = $inpEl->getAttribute('name') ?: '';
                    $placeholder = $inpEl->getAttribute('placeholder') ?: '';
                    if ($type !== 'submit' && $type !== 'button') {
                        $inputs[] = ['type' => $type, 'name' => $name, 'placeholder' => $placeholder];
                    }
                }

                $hasConsentCheckbox = false;
                $checkboxes = $xpath->query('.//input[@type="checkbox"]', $formEl);
                foreach ($checkboxes as $cbEl) {
                    $parentText = strtolower($cbEl->parentNode ? $cbEl->parentNode->textContent : '');
                    $cbId = $cbEl->getAttribute('id');
                    $labelText = '';
                    if (!empty($cbId)) {
                        $labels = $xpath->query(".//label[@for='{$cbId}']", $formEl);
                        if ($labels->length > 0) $labelText = strtolower($labels->item(0)->textContent);
                    }
                    $combinedText = $parentText . ' ' . $labelText;
                    if (strpos($combinedText, 'acept') !== false || strpos($combinedText, 'consent') !== false || strpos($combinedText, 'privac') !== false || strpos($combinedText, 'ley') !== false || strpos($combinedText, 'condicion') !== false || strpos($combinedText, 'dat') !== false) {
                        $hasConsentCheckbox = true;
                    }
                }

                $inputsKey = implode('-', array_map(function($i) { return $i['name'] . $i['type']; }, $inputs));
                $exForm = false;
                foreach ($allForms as $f) {
                    if ($f['id'] === $id && $f['inputsKey'] === $inputsKey) { $exForm = true; break; }
                }
                if (!$exForm) {
                    $allForms[] = [
                        'id' => $id,
                        'action' => $action,
                        'inputs' => $inputs,
                        'inputsKey' => $inputsKey,
                        'hasConsentCheckbox' => $hasConsentCheckbox,
                        'pageUrl' => $currentUrl
                    ];
                }
            }

            // Trackers
            $trackingPatterns = [
                ['name' => 'Google Tag Manager', 'pattern' => '/googletagmanager\.com/i', 'category' => 'Marketing/Analytics'],
                ['name' => 'Google Analytics', 'pattern' => '/google-analytics\.com|analytics\.js|gtag/i', 'category' => 'Analytics'],
                ['name' => 'Meta Pixel (Facebook)', 'pattern' => '/connect\.facebook\.net|fbevents\.js/i', 'category' => 'Marketing'],
                ['name' => 'Hotjar', 'pattern' => '/hotjar\.com|static\.hotjar/i', 'category' => 'Analytics'],
                ['name' => 'HubSpot', 'pattern' => '/js\.hs-scripts\.com|js\.hsadspixel\.net/i', 'category' => 'Marketing/CRM'],
                ['name' => 'TikTok Pixel', 'pattern' => '/tiktok\.com\/sdk/i', 'category' => 'Marketing']
            ];

            $scripts = $xpath->query('//script');
            foreach ($scripts as $el) {
                $src = $el->getAttribute('src');
                $content = $el->textContent;

                foreach ($trackingPatterns as $t) {
                    $matched = false;
                    if (!empty($src) && preg_match($t['pattern'], $src)) $matched = true;
                    if (!empty($content) && preg_match($t['pattern'], $content)) $matched = true;

                    if ($matched) {
                        $exTr = false;
                        foreach ($allTrackers as $tr) { if ($tr['name'] === $t['name']) { $exTr = true; break; } }
                        if (!$exTr) {
                            $allTrackers[] = [
                                'name' => $t['name'],
                                'category' => $t['category'],
                                'source' => !empty($src) ? 'external' : 'inline',
                                'url' => !empty($src) ? $src : 'Script inline',
                                'pageUrl' => $currentUrl
                            ];
                        }
                    }
                }
            }

            // Cookie banner indicator
            $cookieBannerPatterns = ['/cookie/i', '/consent/i', '/cookie-banner/i', '/cookie-notice/i', '/cookie-law/i', '/onetrust/i', '/cookiebot/i', '/osano/i', '/didomi/i', '/usercentrics/i', '/privy/i'];
            $allElementsWithIdOrClass = $xpath->query('//*[@id or @class]');
            foreach ($allElementsWithIdOrClass as $el) {
                $elId = $el->getAttribute('id');
                $elClass = $el->getAttribute('class');
                foreach ($cookieBannerPatterns as $p) {
                    if (preg_match($p, $elId) || preg_match($p, $elClass)) {
                        $hasCookieBannerIndicator = true;
                        break 2;
                    }
                }
            }
        }
    }

    $totalCrawlTimeMs = round((microtime(true) - $startScanTime) * 1000);

    // Score computation
    $score = 100;
    $deductions = [];

    if (empty($allPrivacyLinks)) {
        $score -= 20;
        $deductions[] = [
            'points' => 20,
            'area' => 'Políticas de Privacidad',
            'detail' => 'No se detectó ningún enlace a la Política de Privacidad en ninguna de las páginas rastreadas. El Art. 14 ter de la Ley 21.719 exige que las políticas sobre tratamiento de datos estén permanentemente accesibles al público.'
        ];
    }

    if (empty($allTermsLinks)) {
        $score -= 10;
        $deductions[] = [
            'points' => 10,
            'area' => 'Términos y Condiciones',
            'detail' => 'No se encontraron enlaces a Términos y Condiciones en el sitio rastreado. Aunque no es explícitamente obligatorio por ley para todo sitio, es clave para establecer los términos del contrato de tratamiento.'
        ];
    }

    if (!empty($allForms)) {
        $formsWithoutConsent = array_filter($allForms, function($f) { return !$f['hasConsentCheckbox']; });
        if (!empty($formsWithoutConsent)) {
            $deductionPoints = min(25, count($formsWithoutConsent) * 15);
            $score -= $deductionPoints;
            $deductions[] = [
                'points' => $deductionPoints,
                'area' => 'Consentimiento en Formularios',
                'detail' => count($formsWithoutConsent) . ' de los ' . count($allForms) . ' formulario(s) detectados recolectan datos personales en el sitio sin un checkbox de consentimiento explícito, previo e informado (infracción al Art. 12).'
            ];
        }
    }

    $rootUrlNormalized = rtrim($baseOrigin, '/');
    $rootHeaders = $pageHeadersStatus[$rootUrlNormalized] ?? ($pageHeadersStatus[rtrim($url, '/')] ?? (reset($pageHeadersStatus) ?: []));
    $missingHeaders = [];
    if (empty($rootHeaders['hsts'])) { $score -= 5; $missingHeaders[] = 'HSTS (Strict-Transport-Security)'; }
    if (empty($rootHeaders['csp'])) { $score -= 5; $missingHeaders[] = 'Content-Security-Policy (CSP)'; }
    if (empty($rootHeaders['xFrameOptions'])) { $score -= 5; $missingHeaders[] = 'X-Frame-Options'; }
    if (empty($rootHeaders['xContentTypeOptions'])) { $score -= 5; $missingHeaders[] = 'X-Content-Type-Options'; }

    if (!empty($missingHeaders)) {
        $deductions[] = [
            'points' => count($missingHeaders) * 5,
            'area' => 'Seguridad Técnica',
            'detail' => 'Faltan cabeceras de seguridad clave en el dominio principal: ' . implode(', ', $missingHeaders) . '. El principio de seguridad (Art. 3, letra f) exige salvaguardar el sitio contra filtraciones o malware.'
        ];
    }

    if (!empty($allTrackers) && !$hasCookieBannerIndicator) {
        $score -= 25;
        $deductions[] = [
            'points' => 25,
            'area' => 'Cookies y Seguimiento',
            'detail' => 'Se detectaron scripts de seguimiento de terceros (' . implode(', ', array_map(function($t) { return $t['name']; }, $allTrackers)) . '), pero no se encontró un banner de consentimiento de cookies. Bajo la Ley 21.719, las cookies no esenciales requieren la autorización expresa del usuario antes de instalarse.'
        ];
    } elseif (!empty($allTrackers) && $hasCookieBannerIndicator) {
        $score -= 5;
        $deductions[] = [
            'points' => 5,
            'area' => 'Configuración de Cookies',
            'detail' => 'Se detectaron cookies y un banner de consentimiento. Asegúrate de que el banner ofrezca aceptación granular por tipo de cookie y que no tenga casillas pre-marcadas por defecto (Art. 12).'
        ];
    }

    // Registrar auditoría de escaneo en MariaDB (consent_logs)
    try {
        $db = Database::connect();
        $logId = Database::uuidv4();
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'));
        if (strpos($ip, ',') !== false) {
            $parts = explode(',', $ip);
            $ip = trim($parts[0]);
        }
        $ipParts = explode('.', $ip);
        if (count($ipParts) === 4) {
            $ipParts[3] = '***';
            $ip = implode('.', $ipParts);
        }
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        $stmt = $db->prepare("
            INSERT INTO consent_logs (id, ip_anonymized, user_agent, url_scanned, action, user_id, policy_version)
            VALUES (:id, :ip, :ua, :url, 'EXECUTED_SCAN', :uid, 'v1.0')
        ");
        $stmt->execute([
            ':id' => $logId,
            ':ip' => $ip,
            ':ua' => $ua,
            ':url' => $url,
            ':uid' => $userId
        ]);
    } catch (Exception $dbEx) {
        error_log("No se pudo registrar log en MariaDB: " . $dbEx->getMessage());
    }

    echo json_encode([
        'url' => $url,
        'scanTimeMs' => $totalCrawlTimeMs,
        'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
        'score' => $score,
        'deductions' => $deductions,
        'summary' => [
            'privacyPolicyFound' => !empty($allPrivacyLinks),
            'termsFound' => !empty($allTermsLinks),
            'cookieBannerFound' => $hasCookieBannerIndicator,
            'formCount' => count($allForms),
            'trackerCount' => count($allTrackers),
            'pagesScannedCount' => count($allScannedPages)
        ],
        'details' => [
            'scannedPages' => $allScannedPages,
            'privacyLinks' => $allPrivacyLinks,
            'termsLinks' => $allTermsLinks,
            'cookies' => $allCookies,
            'securityHeaders' => $rootHeaders,
            'pageHeadersStatus' => $pageHeadersStatus,
            'forms' => $allForms,
            'trackers' => $allTrackers
        ]
    ]);
} catch (Throwable $e) {
    error_log('[SDAI SCAN] Crawl Endpoint Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'No fue posible completar la evaluación del dominio.'
    ]);
}
