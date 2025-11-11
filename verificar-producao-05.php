<?php
/**
 * Verificar registros ID 244 e 245 no banco de PRODUÇÃO
 */

// Forçar conexão com produção (Hostinger)
$dbHost = '127.0.0.1';
$dbUsername = 'u857325944_formu';
$dbPassword = 'JkF4B7N1';
$dbName = 'u857325944_formu';

echo "=== VERIFICAÇÃO DO BANCO DE PRODUÇÃO (HOSTINGER) ===\n\n";

try {
    $conexao = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);
    
    if ($conexao->connect_error) {
        die("❌ ERRO de conexão: " . $conexao->connect_error);
    }
    
    echo "✅ Conectado ao banco de produção\n\n";
    
    // Verificar registros 244 e 245 (IDs inseridos pelo webhook)
    echo "=== REGISTROS 244 E 245 (WEBHOOK REAL) ===\n\n";
    
    $query = "SELECT id, titulo, valor_over, odds, status_aposta, created_at FROM bote WHERE id IN (244, 245) ORDER BY id ASC";
    $result = $conexao->query($query);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "ID: " . $row["id"] . "\n";
            echo "  Título: " . $row["titulo"] . "\n";
            echo "  valor_over: " . $row["valor_over"] . " (tipo: " . gettype($row["valor_over"]) . ")\n";
            echo "  odds: " . $row["odds"] . "\n";
            echo "  status_aposta: " . $row["status_aposta"] . "\n";
            echo "  Data: " . $row["created_at"] . "\n";
            echo "\n";
        }
    } else {
        echo "❌ Registros 244 e 245 não encontrados\n\n";
    }
    
    // Contar todos os registros com +0.5
    echo "=== CONTAGEM GERAL DE +0.5 ===\n\n";
    
    $countQuery = "SELECT COUNT(*) as total FROM bote WHERE valor_over = 0.5";
    $countResult = $conexao->query($countQuery);
    $countRow = $countResult->fetch_assoc();
    
    echo "Total de registros com +0.5: " . $countRow["total"] . "\n\n";
    
    // Listar últimos 5 com +0.5
    echo "=== ÚLTIMOS 5 REGISTROS COM +0.5 ===\n\n";
    
    $lastQuery = "SELECT id, titulo, valor_over, odds FROM bote WHERE valor_over = 0.5 ORDER BY id DESC LIMIT 5";
    $lastResult = $conexao->query($lastQuery);
    
    if ($lastResult && $lastResult->num_rows > 0) {
        while ($row = $lastResult->fetch_assoc()) {
            echo "ID: " . $row["id"] . " | Título: " . $row["titulo"] . " | valor_over: " . $row["valor_over"] . " | odds: " . $row["odds"] . "\n";
        }
    }
    
    echo "\n";
    echo "=== VERIFICAÇÃO: VALORES SALVOS CORRETAMENTE? ===\n\n";
    
    // Verificar se 244 e 245 tem valor_over = 0.5
    $checkQuery = "SELECT id, valor_over FROM bote WHERE id IN (244, 245)";
    $checkResult = $conexao->query($checkQuery);
    
    if ($checkResult && $checkResult->num_rows == 2) {
        $allCorrect = true;
        while ($row = $checkResult->fetch_assoc()) {
            if ($row["valor_over"] != 0.5) {
                $allCorrect = false;
                echo "❌ ID " . $row["id"] . ": valor_over = " . $row["valor_over"] . " (deveria ser 0.5)\n";
            }
        }
        
        if ($allCorrect) {
            echo "✅✅✅ SUCESSO! IDs 244 e 245 têm valor_over = 0.5! ✅✅✅\n";
        }
    } else {
        echo "⚠️ Não consegui encontrar os registros 244 e 245\n";
    }
    
    $conexao->close();
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage();
}

?>
