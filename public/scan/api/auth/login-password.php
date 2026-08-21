<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../lib/auth.php';
require_once __DIR__ . '/../../../lib/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';

$db = Database::connect();
$adminPassword = getenv('ADMIN_PASSWORD') ?: ($_ENV['ADMIN_PASSWORD'] ?? '');

if (empty($username)) {
    http_response_code(400);
    echo json_encode(['error' => 'Falta ingresar el usuario autorizado.']);
    exit;
}

if (empty($adminPassword)) {
    http_response_code(403);
    echo json_encode(['error' => 'La autenticación por contraseña de respaldo está desactivada. Configura ADMIN_PASSWORD en tu archivo .env']);
    exit;
}

try {
    $stmt = $db->prepare("SELECT * FROM authorized_users WHERE user_id = :username LIMIT 1");
    $stmt->execute([':username' => $username]);
    $authUser = $stmt->fetch();

    if (!$authUser) {
        http_response_code(403);
        echo json_encode(['error' => 'No autorizado. Tu usuario o correo no ha sido registrado como administrador en la plataforma.']);
        exit;
    }

    if ($password === $adminPassword) {
        Auth::setSessionCookie(['role' => 'admin', 'user_id' => $username]);
        echo json_encode(['verified' => true]);
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Contraseña de administrador incorrecta']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}
