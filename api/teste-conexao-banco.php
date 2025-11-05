<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Testar conexão com banco
require_once dirname(__DIR__) . '/config.php';

error_log("🧪 TESTE DE CONEXÃO COM BANCO DE DADOS");
error_log("Host: " . DB_HOST);
error_log("Usuário: " . DB_USERNAME);
error_log("Banco: " . DB_NAME);

if (!isset($conexao)) {
    echo json_encode(['success' => false, 'error' => 'Variável $conexao não está definida']);
    exit;
}

if ($conexao->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Erro de conexão: ' . $conexao->connect_error]);
    exit;
}

// Testar consulta simples
$sql = "SELECT COUNT(*) as total FROM telegram_mensagens LIMIT 1";
$result = $conexao->query($sql);

if (!$result) {
    echo json_encode([
        'success' => false, 
        'error' => 'Erro ao executar query: ' . $conexao->error,
        'sql' => $sql
    ]);
    exit;
}

$row = $result->fetch_assoc();

echo json_encode([
    'success' => true,
    'message' => 'Conexão com banco de dados OK!',
    'database' => DB_NAME,
    'total_mensagens' => $row['total'],
    'host' => DB_HOST,
    'charset' => 'utf8mb4'
]);

$conexao->close();
?>
