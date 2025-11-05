<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/config.php';

// Verificar estrutura da tabela telegram_mensagens
$sql = "DESCRIBE telegram_mensagens";
$result = $conexao->query($sql);

if (!$result) {
    echo json_encode(['success' => false, 'error' => 'Erro ao descrever tabela: ' . $conexao->error]);
    exit;
}

$colunas = [];
while ($row = $result->fetch_assoc()) {
    $colunas[] = $row;
}

// Também listar dados de exemplo
$sqlExemplo = "SELECT * FROM telegram_mensagens LIMIT 3";
$resultExemplo = $conexao->query($sqlExemplo);

$exemplo = [];
if ($resultExemplo) {
    while ($row = $resultExemplo->fetch_assoc()) {
        $exemplo[] = $row;
    }
}

echo json_encode([
    'success' => true,
    'colunas' => $colunas,
    'exemplo_dados' => $exemplo,
    'total_colunas' => count($colunas)
]);

$conexao->close();
?>
