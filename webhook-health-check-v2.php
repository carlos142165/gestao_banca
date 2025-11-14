<?php
// ✅ WEBHOOK HEALTH CHECK - Mantém a conexão viva
// Execute como cron: */5 * * * * curl -s https://analisegb.com/gestao/gestao_banca/webhook-health-check.php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config.php';

$logFile = __DIR__ . '/logs/webhook-health-check.log';

try {
    // Criar diretório de logs se não existir
    if (!is_dir(__DIR__ . '/logs')) {
        mkdir(__DIR__ . '/logs', 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "\n[$timestamp] ===== HEALTH CHECK =====" . PHP_EOL, FILE_APPEND);
    
    // ✅ PASSO 1: Verificar e reconectar
    $conexao = obterConexao();
    
    if (!$conexao) {
        file_put_contents($logFile, "❌ FALHA: Conexão NULL\n", FILE_APPEND);
        http_response_code(503);
        echo json_encode([
            'status' => 'error',
            'mensagem' => 'Conexão com banco de dados falhou',
            'timestamp' => $timestamp
        ]);
        exit;
    }
    
    // ✅ PASSO 2: Testar com PING
    if (!$conexao->ping()) {
        file_put_contents($logFile, "❌ PING FALHOU - Reconectando...\n", FILE_APPEND);
        $conexao = criarNovaConexao();
        
        if (!$conexao || !$conexao->ping()) {
            file_put_contents($logFile, "❌ FALHA: Reconexão falhou\n", FILE_APPEND);
            http_response_code(503);
            echo json_encode([
                'status' => 'error',
                'mensagem' => 'Reconexão falhou',
                'timestamp' => $timestamp
            ]);
            exit;
        }
        file_put_contents($logFile, "✅ Reconexão bem-sucedida após falha de PING\n", FILE_APPEND);
    }
    
    // ✅ PASSO 3: Executar query simples para validar
    $result = $conexao->query("SELECT 1 AS test");
    
    if (!$result) {
        file_put_contents($logFile, "❌ Query SELECT 1 falhou: " . $conexao->error . "\n", FILE_APPEND);
        http_response_code(503);
        echo json_encode([
            'status' => 'error',
            'mensagem' => 'Query de teste falhou',
            'detalhe' => $conexao->error,
            'timestamp' => $timestamp
        ]);
        exit;
    }
    
    // ✅ PASSO 4: Verificar mensagens pendentes (opcional)
    $queryCheck = "SELECT COUNT(*) AS total FROM bote WHERE data_criacao >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";
    $result = $conexao->query($queryCheck);
    $row = $result->fetch_assoc();
    $totalRecent = $row['total'];
    
    file_put_contents($logFile, "✅ STATUS OK - Mensagens última hora: $totalRecent\n", FILE_APPEND);
    
    // ✅ RETORNAR SUCESSO
    http_response_code(200);
    echo json_encode([
        'status' => 'ok',
        'mensagem' => 'Webhook está saudável',
        'conexao' => 'ativa',
        'mensagens_ultima_hora' => $totalRecent,
        'timestamp' => $timestamp,
        'environment' => ENVIRONMENT
    ]);
    
} catch (Exception $e) {
    file_put_contents($logFile, "❌ EXCEÇÃO: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'mensagem' => 'Erro na verificação de saúde',
        'detalhe' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
