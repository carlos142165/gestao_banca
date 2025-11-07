<?php
// Cabeçalho JSON
header('Content-Type: application/json; charset=utf-8');

// ✅ INCLUIR CONFIGURAÇÃO CENTRALIZADA DO BANCO
require_once 'config.php';

try {
    // 🔗 Conexão já vem de config.php
    
    // ✅ Verificar conexão
    if ($conexao->connect_error) {
        throw new Exception("Erro de conexão: " . $conexao->connect_error);
    }
    
    // 📅 Obter data de hoje (formato Y-m-d)
    $data_hoje = date('Y-m-d');
    
    // 🔍 Buscar todas as mensagens do dia com título, resultado E ODDS
    $query = "SELECT id, titulo, resultado, odds, data_criacao FROM bote 
              WHERE DATE(data_criacao) = '$data_hoje'
              ORDER BY data_criacao DESC";
    
    $resultado = $conexao->query($query);
    
    if (!$resultado) {
        throw new Exception("Erro na query: " . $conexao->error);
    }
    
    $registros = [];
    while ($row = $resultado->fetch_assoc()) {
        $registros[] = $row;
    }
    
    // Retornar dados brutos para debug
    echo json_encode([
        'success' => true,
        'data_hoje' => $data_hoje,
        'total_registros' => count($registros),
        'registros' => $registros
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    $conexao->close();
    
} catch (Exception $e) {
    // ❌ Retornar erro
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
