<?php
/**
 * DEBUG - CHECK SESSION
 */

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Carregar carregar_sessao.php
require_once 'carregar_sessao.php';

header('Content-Type: application/json');

$response = [
    'session_status' => session_status() === PHP_SESSION_ACTIVE ? 'Ativa' : 'Inativa',
    'session_id' => session_id() ?? 'Nenhum',
    'usuario_id' => $_SESSION['usuario_id'] ?? 'NÃO DEFINIDO',
    'session_data' => $_SESSION,
    'servidor' => [
        'php_version' => phpversion(),
        'request_method' => $_SERVER['REQUEST_METHOD'],
        'script' => $_SERVER['SCRIPT_NAME'],
    ]
];

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
