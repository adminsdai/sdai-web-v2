<?php
header('Content-Type: application/json; charset=utf-8');
http_response_code(501);
echo json_encode([
    'error' => 'Registro biométrico WebAuthn no disponible en hosting compartido puro PHP. Utilice la autenticación con contraseña y usuario autorizado.'
]);
