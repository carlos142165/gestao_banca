<?php
/**
 * DEBUG: Verificar mensagens no banco de dados
 * Acesse: http://localhost/gestao/public_html/api/debug-mensagens.php
 */

header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('America/Sao_Paulo');

require_once '../config.php';

if (!$conexao) {
    die(json_encode(['error' => 'Sem conexão com banco']));
}

// Buscar últimas 5 mensagens
$query = "
    SELECT 
        id,
        telegram_message_id,
        titulo,
        time_1,
        time_2,
        status_aposta,
        resultado,
        hora_mensagem,
        data_criacao
    FROM bote
    ORDER BY data_criacao DESC
    LIMIT 5
";

$result = $conexao->query($query);

if (!$result) {
    die(json_encode(['error' => 'Erro na query: ' . $conexao->error]));
}

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

echo json_encode([
    'success' => true,
    'total' => count($messages),
    'messages' => $messages
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
