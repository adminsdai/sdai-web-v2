<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../lib/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$session = Auth::getSession();
if ($session && ($session['role'] ?? '') === 'admin') {
    echo json_encode(['authenticated' => true, 'user' => $session]);
} else {
    echo json_encode(['authenticated' => false]);
}
