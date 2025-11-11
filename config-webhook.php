<?php
/**
 * Testar webhook remoto na Hostinger
 */

require_once 'telegram-config.php';

echo "=== VERIFICAÇÃO DO WEBHOOK ===\n\n";

// Informações do bot
$botToken = TELEGRAM_BOT_TOKEN;
$botUrl = "https://api.telegram.org/bot{$botToken}";

echo "Token do Bot: " . substr($botToken, 0, 10) . "...\n";
echo "API URL: {$botUrl}\n\n";

// 1. Verificar webhook atual
echo "=== WEBHOOK ATUAL ===\n\n";

$url = "{$botUrl}/getWebhookInfo";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: {$httpCode}\n";
echo "Response:\n";
echo json_encode(json_decode($response), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// 2. Configurar webhook se necessário
echo "=== CONFIGURAR WEBHOOK ===\n\n";

$webhookUrl = "https://analisegb.com/gestao/gestao_banca/api/telegram-webhook.php";

echo "Webhook URL esperada: {$webhookUrl}\n\n";

$data = [
    'url' => $webhookUrl,
    'allowed_updates' => ['update_id', 'channel_post', 'message']
];

$url = "{$botUrl}/setWebhook";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: {$httpCode}\n";
echo "Response:\n";
echo json_encode(json_decode($response), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// 3. Verificar webhook novamente
echo "=== WEBHOOK APÓS CONFIGURAÇÃO ===\n\n";

$url = "{$botUrl}/getWebhookInfo";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: {$httpCode}\n";
echo "Response:\n";
echo json_encode(json_decode($response), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

?>
