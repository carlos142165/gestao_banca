<?php
require_once 'config.php';

// Simular webhook recebendo dados
$_SERVER['REQUEST_URI'] = '/gestao_banca/api/telegram-webhook.php';

echo "AMBIENTE: " . ENVIRONMENT . "\n";
echo "HOST: " . DB_HOST . "\n";
echo "BANCO: " . DB_NAME . "\n";
echo "USERNAME: " . DB_USERNAME . "\n";
echo "\n";

// Testar conexão
try {
    echo "Testando conexão com banco...\n";
    if ($conexao) {
        echo "✅ Conexão bem-sucedida!\n";
        
        // Contar mensagens
        $result = $conexao->query("SELECT COUNT(*) as total FROM bote");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "Total de mensagens no banco: " . $row['total'] . "\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
?>
