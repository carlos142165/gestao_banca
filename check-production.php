<?php
/**
 * Verificar conexão com banco de produção da Hostinger
 */

// Forçar conexão com produção
define('DB_HOST', '127.0.0.1');
define('DB_USERNAME', 'u857325944_formu');
define('DB_PASSWORD', 'JkF4B7N1');
define('DB_NAME', 'u857325944_formu');

echo "=== TESTE DE CONEXÃO COM PRODUÇÃO ===\n\n";

echo "Host: " . DB_HOST . "\n";
echo "Username: " . DB_USERNAME . "\n";
echo "Database: " . DB_NAME . "\n\n";

try {
    $conexao = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
    
    if ($conexao->connect_error) {
        die("❌ ERRO de conexão: " . $conexao->connect_error);
    }
    
    echo "✅ Conexão com produção estabelecida com sucesso!\n\n";
    
    // Verificar últimos registros com +0.5
    echo "=== ÚLTIMOS REGISTROS COM +0.5 NO BANCO DE PRODUÇÃO ===\n\n";
    
    $query = "SELECT id, titulo, valor_over, odds, status_aposta, created_at FROM bote WHERE valor_over = 0.5 ORDER BY id DESC LIMIT 10";
    $result = $conexao->query($query);
    
    if ($result && $result->num_rows > 0) {
        echo "✅ Encontrados " . $result->num_rows . " registros com +0.5:\n\n";
        
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
        echo "ℹ️ Nenhum registro com +0.5 encontrado no banco de produção\n\n";
    }
    
    // Contar todos os valores_over diferentes
    echo "=== TODOS OS VALORES_OVER ÚNICOS NO BANCO DE PRODUÇÃO ===\n\n";
    
    $allQuery = "SELECT DISTINCT valor_over FROM bote ORDER BY valor_over ASC";
    $allResult = $conexao->query($allQuery);
    
    if ($allResult && $allResult->num_rows > 0) {
        while ($row = $allResult->fetch_assoc()) {
            echo "- " . $row["valor_over"] . "\n";
        }
    }
    
    $conexao->close();
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage();
}

?>
