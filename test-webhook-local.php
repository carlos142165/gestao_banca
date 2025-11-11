<?php
/**
 * Testar webhook localmente
 */

echo "=== TESTE DO WEBHOOK LOCAL ===\n\n";

$webhookPath = __DIR__ . '/api/telegram-webhook.php';

echo "Caminho do webhook: {$webhookPath}\n";
echo "Existe: " . (file_exists($webhookPath) ? "SIM" : "NÃO") . "\n";

if (file_exists($webhookPath)) {
    echo "Tamanho: " . filesize($webhookPath) . " bytes\n\n";
    
    // Tentar acessar via HTTP
    echo "Testando acesso via HTTP...\n\n";
    
    $ch = curl_init('http://localhost/gestao/gestao_banca/api/telegram-webhook.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'update_id' => 1,
        'channel_post' => [
            'message_id' => 1,
            'date' => time(),
            'text' => 'Teste'
        ]
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: {$httpCode}\n";
    echo "Response: {$response}\n";
} else {
    echo "❌ Webhook não encontrado!\n";
}

?>
