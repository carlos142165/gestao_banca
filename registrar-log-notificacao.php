<?php
/**
 * Endpoint para registrar logs de notificações do sistema JavaScript
 * Recebe POST JSON e escreve em arquivo diário
 */

header('Content-Type: application/json');

try {
    // Recebe dados JSON
    $input = file_get_contents('php://input');
    $dados = json_decode($input, true);
    
    // Valida entrada
    if (!isset($dados['tipo'])) {
        throw new Exception('Campo "tipo" obrigatório');
    }
    
    // ✅ Usar caminho ABSOLUTO para evitar problemas de cwd
    $baseDir = __DIR__; // Diretório deste arquivo PHP
    $logsDir = $baseDir . '/logs';
    
    // Cria pasta logs se não existir
    if (!is_dir($logsDir)) {
        if (!mkdir($logsDir, 0777, true)) {
            throw new Exception('Não foi possível criar diretório /logs');
        }
    }
    
    // Arquivo de log diário
    $data = date('Y-m-d');
    $arquivo = $logsDir . '/notif-' . $data . '.log';
    
    // Formata entry com timestamp
    $timestamp = date('H:i:s');
    $entry = [
        'timestamp' => $timestamp,
        'tipo' => $dados['tipo'],
        'dados' => $dados
    ];
    
    // Escreve JSON no arquivo
    $json = json_encode($entry, JSON_UNESCAPED_UNICODE) . "\n";
    $bytes = file_put_contents($arquivo, $json, FILE_APPEND | LOCK_EX);
    
    if ($bytes === false) {
        throw new Exception('Falha ao escrever no arquivo de log: ' . $arquivo);
    }
    
    // Retorna sucesso
    echo json_encode([
        'sucesso' => true,
        'arquivo' => basename($arquivo),
        'caminho_completo' => $arquivo,
        'bytes_escritos' => $bytes,
        'timestamp' => $timestamp
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'sucesso' => false,
        'erro' => $e->getMessage(),
        'arquivo_esperado' => ($baseDir ?? 'N/A') . '/logs/notif-' . date('Y-m-d') . '.log'
    ]);
}
?>
