<?php
/**
 * VERIFICAÇÃO DE SAÚDE DO WEBHOOK
 * Executa a cada hora via cron job para garantir webhook ativo
 */

require_once 'telegram-config.php';
require_once 'config.php';

$logFile = __DIR__ . '/logs/webhook-health.log';

function logarSaude($mensagem) {
    global $logFile;
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] " . $mensagem . "\n", FILE_APPEND);
}

logarSaude("=== VERIFICAÇÃO INICIADA ===");

try {
    // ✅ 1. VERIFICAR CONEXÃO COM BANCO
    $conexao = obterConexao();
    if (!$conexao) {
        logarSaude("❌ FALHA: Banco de dados desconectado!");
        exit(1);
    }
    logarSaude("✅ Banco de dados: OK");
    
    // ✅ 2. VERIFICAR SE TABELA BOTE EXISTE
    $tableCheck = $conexao->query("SHOW TABLES LIKE 'bote'");
    if (!$tableCheck || $tableCheck->num_rows === 0) {
        logarSaude("❌ FALHA: Tabela 'bote' não encontrada!");
        exit(1);
    }
    logarSaude("✅ Tabela bote: EXISTS");
    
    // ✅ 3. VERIFICAR WEBHOOK NO TELEGRAM
    $botToken = TELEGRAM_BOT_TOKEN;
    $botUrl = "https://api.telegram.org/bot{$botToken}";
    
    $url = "{$botUrl}/getWebhookInfo";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        logarSaude("❌ FALHA ao obter webhook info. HTTP Code: {$httpCode}");
        if (!empty($curlError)) {
            logarSaude("   Erro CURL: {$curlError}");
        }
        exit(1);
    }
    
    $webhookData = json_decode($response, true);
    
    if (!isset($webhookData['ok']) || !$webhookData['ok']) {
        logarSaude("❌ FALHA: Resposta inválida do Telegram");
        logarSaude("   Response: " . substr($response, 0, 200));
        exit(1);
    }
    
    $webhookInfo = $webhookData['result'] ?? [];
    $webhookUrl = $webhookInfo['url'] ?? '';
    $lastErrorTime = $webhookInfo['last_error_date'] ?? 0;
    $lastErrorMsg = $webhookInfo['last_error_message'] ?? 'Nenhum erro';
    $pendingUpdates = $webhookInfo['pending_update_count'] ?? 0;
    
    logarSaude("✅ Webhook Telegram: OK");
    logarSaude("   URL: {$webhookUrl}");
    logarSaude("   Pending Updates: {$pendingUpdates}");
    
    if ($lastErrorTime > 0) {
        logarSaude("⚠️ Último erro: " . date('Y-m-d H:i:s', $lastErrorTime) . " - {$lastErrorMsg}");
    }
    
    // ✅ 4. RECONFIGURAR WEBHOOK SE NECESSÁRIO
    if (empty($webhookUrl) || $pendingUpdates > 100) {
        logarSaude("🔄 Reconfigurando webhook...");
        
        $webhookUrlToSet = "https://analisegb.com/gestao/gestao_banca/api/telegram-webhook.php";
        
        // Primeiro remover webhook existente
        $url = "{$botUrl}/deleteWebhook";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);
        
        logarSaude("   1. Webhook antigo removido");
        
        // Aguardar um segundo
        sleep(1);
        
        // Depois configurar novo
        $data = [
            'url' => $webhookUrlToSet,
            'allowed_updates' => ['channel_post', 'message'],
            'drop_pending_updates' => false
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
        
        if ($httpCode === 200) {
            logarSaude("   2. Webhook novo configurado: {$webhookUrlToSet}");
        } else {
            logarSaude("   ❌ Falha ao configurar novo webhook");
        }
    }
    
    // ✅ 5. VERIFICAR REGISTROS RECENTES
    $recentCheck = $conexao->query("SELECT COUNT(*) as cnt FROM bote WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    if ($recentCheck) {
        $row = $recentCheck->fetch_assoc();
        $recentCount = $row['cnt'] ?? 0;
        logarSaude("✅ Registros na última hora: {$recentCount}");
    }
    
    logarSaude("✅ VERIFICAÇÃO CONCLUÍDA COM SUCESSO");
    
} catch (Exception $e) {
    logarSaude("❌ ERRO: " . $e->getMessage());
    exit(1);
}

?>
