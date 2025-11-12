<?php
/**
 * TESTE RÁPIDO - Verificar se tudo está funcionando
 */

require_once 'config.php';

echo "=== TESTE DE FUNCIONALIDADE DO WEBHOOK ===\n\n";

// 1. Teste de Reconexão
echo "1️⃣ TESTE DE RECONEXÃO\n";
try {
    $conexao = obterConexao();
    if ($conexao && $conexao->ping()) {
        echo "✅ Reconexão: OK\n";
    } else {
        echo "❌ Reconexão: FALHOU\n";
    }
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}

// 2. Teste de Timeout
echo "\n2️⃣ TESTE DE TIMEOUT\n";
try {
    $result = $conexao->query("SELECT @@session.wait_timeout, @@session.interactive_timeout");
    if ($result) {
        $row = $result->fetch_assoc();
        $waitTimeout = intval($row['@@session.wait_timeout']);
        $interactiveTimeout = intval($row['@@session.interactive_timeout']);
        
        echo "✅ wait_timeout: {$waitTimeout}s (" . round($waitTimeout / 86400, 1) . " dias)\n";
        echo "✅ interactive_timeout: {$interactiveTimeout}s (" . round($interactiveTimeout / 86400, 1) . " dias)\n";
        
        if ($waitTimeout >= 604800) {
            echo "✅ Timeouts: OK (>= 7 dias)\n";
        } else {
            echo "⚠️ Timeouts: BAIXO (deveria ser 604800s = 7 dias)\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}

// 3. Teste de Charset
echo "\n3️⃣ TESTE DE CHARSET\n";
try {
    $result = $conexao->query("SELECT @@character_set_client, @@character_set_connection, @@character_set_database");
    if ($result) {
        $row = $result->fetch_assoc();
        $client = $row['@@character_set_client'];
        $conn = $row['@@character_set_connection'];
        $db = $row['@@character_set_database'];
        
        echo "✅ character_set_client: {$client}\n";
        echo "✅ character_set_connection: {$conn}\n";
        echo "✅ character_set_database: {$db}\n";
        
        if ($client === 'utf8mb4' && $conn === 'utf8mb4') {
            echo "✅ Charset: OK (utf8mb4)\n";
        } else {
            echo "⚠️ Charset: Pode ter problemas com acentos\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}

// 4. Teste de Tabela
echo "\n4️⃣ TESTE DE TABELA BOTE\n";
try {
    $result = $conexao->query("DESCRIBE bote");
    if ($result && $result->num_rows > 0) {
        echo "✅ Tabela bote: EXISTS\n";
        echo "✅ Colunas: " . $result->num_rows . "\n";
    } else {
        echo "❌ Tabela bote: NÃO ENCONTRADA\n";
    }
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}

// 5. Teste de Inserção
echo "\n5️⃣ TESTE DE INSERÇÃO (TEST)\n";
try {
    $query = "INSERT INTO bote (telegram_message_id, mensagem_completa, titulo, tipo_aposta, time_1, time_2, valor_over, odds, hora_mensagem, status_aposta) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conexao->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conexao->error);
    }
    
    $msgId = 99999;
    $fullMsg = "TEST - Não deletar se este message_id aparacer no banco";
    $title = "TEST";
    $type = "TEST";
    $t1 = "Team A";
    $t2 = "Team B";
    $over = 2.5;
    $odds = 1.5;
    $time = "12:00:00";
    $status = "TEST";
    
    $stmt->bind_param("isddssddsss", $msgId, $fullMsg, $title, $type, $t1, $t2, $over, $odds, $time, $status);
    
    if ($stmt->execute()) {
        $insertId = $conexao->insert_id;
        echo "✅ Insert test: OK (ID: {$insertId})\n";
        
        // Deletar
        $conexao->query("DELETE FROM bote WHERE id = {$insertId}");
        echo "✅ Delete test: OK\n";
    } else {
        echo "❌ Insert test: FALHOU - " . $stmt->error . "\n";
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}

// 6. Teste de Log
echo "\n6️⃣ TESTE DE LOG\n";
try {
    $logFile = __DIR__ . '/logs/webhook-test.log';
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Teste executado com sucesso\n", FILE_APPEND);
    
    if (file_exists($logFile)) {
        echo "✅ Log: OK\n";
        echo "   Arquivo: {$logFile}\n";
    }
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}

echo "\n=== TESTE CONCLUÍDO ===\n";
?>
