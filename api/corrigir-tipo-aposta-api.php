<?php
/**
 * API para corrigir tipo_aposta CANTOS
 * Recebe requisição do formulário de correção
 */

header('Content-Type: application/json; charset=utf-8');
require_once '../config.php';
$conexao->set_charset("utf8mb4");

// Garantir que é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'erro' => 'Método não permitido']);
    exit;
}

// Pegar dados JSON
$input = json_decode(file_get_contents('php://input'), true);
$acao = $input['acao'] ?? '';

if ($acao !== 'corrigir_cantos') {
    http_response_code(400);
    echo json_encode(['success' => false, 'erro' => 'Ação inválida']);
    exit;
}

// Padrões que indicam CANTOS
$padroes_cantos = [
    "⛳",      // Emoji de bandeira
    "🚩",      // Emoji de bandeira vermelha
    "cantos",
    "canto",
    "escanteio",
    "escantei"
];

// Montar condição WHERE para detectar títulos que indicam CANTOS
$condicoes = [];
foreach ($padroes_cantos as $padrao) {
    $padrao_escapado = $conexao->real_escape_string($padrao);
    $condicoes[] = "LOWER(titulo) LIKE '%{$padrao_escapado}%'";
}

$condicao_titulo = "(" . implode(" OR ", $condicoes) . ")";

try {
    // SQL de correção: Atualizar registros que têm título indicando CANTOS
    // mas tipo_aposta não é 'CANTOS'
    $sql_update = "UPDATE bote 
                   SET tipo_aposta = 'CANTOS'
                   WHERE {$condicao_titulo}
                   AND (tipo_aposta IS NULL OR tipo_aposta = '' OR LOWER(tipo_aposta) != 'cantos')";
    
    if (!$conexao->query($sql_update)) {
        throw new Exception("Erro ao atualizar: " . $conexao->error);
    }
    
    $registros_atualizados = $conexao->affected_rows;
    
    // Verificar total de CANTOS corretos agora
    $sql_verificacao = "SELECT COUNT(*) as total 
                       FROM bote 
                       WHERE {$condicao_titulo} 
                       AND LOWER(tipo_aposta) = 'cantos'";
    
    $result = $conexao->query($sql_verificacao);
    $row = $result->fetch_assoc();
    $total_correto = $row['total'] ?? 0;
    
    echo json_encode([
        'success' => true,
        'registros_atualizados' => $registros_atualizados,
        'total_correto' => $total_correto,
        'mensagem' => "✅ Correção concluída! {$registros_atualizados} registros foram atualizados."
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'erro' => $e->getMessage()
    ]);
}

$conexao->close();
?>
