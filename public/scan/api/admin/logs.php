<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../lib/auth.php';
require_once __DIR__ . '/../../../lib/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$session = Auth::getSession();
if (!$session || ($session['role'] ?? '') !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized. Valid Passkey/admin session required.']);
    exit;
}

try {
    $db = Database::connect();
    $stmt = $db->query("SELECT * FROM consent_logs ORDER BY created_at DESC LIMIT 100");
    $logs = $stmt->fetchAll();
    echo json_encode($logs);
} catch (Exception $e) {
    error_log("Logs retrieval error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to retrieve logs']);
}
