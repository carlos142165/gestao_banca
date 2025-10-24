<?php
/**
 * Wrapper para minhaconta.php
 * Adapta a resposta para o novo script gerenciador-conta.js
 */

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticação
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit();
}

// Incluir o arquivo minhaconta.php e capturar sua resposta
require_once 'minhaconta.php';
