<?php session_start(); header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'sessao_id' => session_id(),
    'usuario_id' => $_SESSION['usuario_id'] ?? 'NÃO DEFINIDO',
    'usuario_nome' => $_SESSION['usuario_nome'] ?? 'N/A',
    'cookies' => $_COOKIE,
    'headers_enviados' => headers_sent(),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
