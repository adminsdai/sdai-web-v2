<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../lib/auth.php';
require_once __DIR__ . '/../../../lib/db.php';

$session = Auth::getSession();
if (!$session || ($session['role'] ?? '') !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized. Valid admin session required.']);
    exit;
}

$db = Database::connect();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $stmtUsers = $db->query("SELECT user_id, role, created_at FROM authorized_users ORDER BY created_at ASC");
        $users = $stmtUsers->fetchAll();

        $stmtKeys = $db->query("SELECT id, user_id, created_at, transports FROM passkey_credentials ORDER BY created_at DESC");
        $keys = $stmtKeys->fetchAll();

        // Convertir transorts JSON si viene como string
        foreach ($keys as &$k) {
            if (is_string($k['transports'])) {
                $decoded = json_decode($k['transports'], true);
                $k['transports'] = $decoded !== null ? $decoded : $k['transports'];
            }
        }

        echo json_encode([
            'authorized_users' => $users,
            'passkeys' => $keys
        ]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al obtener configuración: ' . $e->getMessage()]);
        exit;
    }
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $userId = trim(strtolower($input['user_id'] ?? ''));
    $role = $input['role'] ?? 'admin';

    if (empty($userId) || strpos($userId, '@') === false) {
        http_response_code(400);
        echo json_encode(['error' => 'Correo electrónico inválido.']);
        exit;
    }

    try {
        $stmt = $db->prepare("INSERT INTO authorized_users (user_id, role) VALUES (:uid, :role)");
        $stmt->execute([':uid' => $userId, ':role' => $role]);
        http_response_code(201);
        echo json_encode(['success' => true, 'message' => "Usuario {$userId} autorizado correctamente."]);
        exit;
    } catch (PDOException $e) {
        if ($e->getCode() == '23000' || strpos($e->getMessage(), '1062') !== false) {
            http_response_code(409);
            echo json_encode(['error' => 'Este usuario ya está autorizado.']);
            exit;
        }
        http_response_code(500);
        echo json_encode(['error' => 'Error al autorizar usuario: ' . $e->getMessage()]);
        exit;
    }
}

if ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $type = $input['type'] ?? '';
    $id = $input['id'] ?? '';

    if (empty($type) || empty($id)) {
        http_response_code(400);
        echo json_encode(['error' => 'Faltan parámetros: type e id son requeridos.']);
        exit;
    }

    if ($type === 'user' && $id === ($session['user_id'] ?? '')) {
        http_response_code(403);
        echo json_encode(['error' => 'No puedes eliminar tu propio usuario activo.']);
        exit;
    }

    try {
        if ($type === 'user') {
            $stmtDelKeys = $db->prepare("DELETE FROM passkey_credentials WHERE user_id = :uid");
            $stmtDelKeys->execute([':uid' => $id]);

            $stmtDelUser = $db->prepare("DELETE FROM authorized_users WHERE user_id = :uid");
            $stmtDelUser->execute([':uid' => $id]);

            echo json_encode(['success' => true, 'message' => "Usuario {$id} y sus llaves han sido eliminados."]);
            exit;
        }

        if ($type === 'passkey') {
            $stmtDelKey = $db->prepare("DELETE FROM passkey_credentials WHERE id = :kid");
            $stmtDelKey->execute([':kid' => $id]);

            echo json_encode(['success' => true, 'message' => 'Llave biométrica revocada.']);
            exit;
        }

        http_response_code(400);
        echo json_encode(['error' => 'Tipo de revocación inválido. Use "user" o "passkey".']);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al revocar: ' . $e->getMessage()]);
        exit;
    }
}

http_response_code(405);
echo json_encode(['error' => 'Método no permitido.']);
