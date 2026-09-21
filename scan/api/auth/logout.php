<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../lib/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

Auth::clearSessionCookie();
echo json_encode(['success' => true]);
