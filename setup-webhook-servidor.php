<?php
/**
 * ================================================================
 * CONFIGURAR WEBHOOK DO TELEGRAM - EXECUTE NO SERVIDOR HOSTINGER
 * ================================================================
 * 
 * INSTRUÇÕES:
 * 1. Faça upload deste arquivo para: public_html/gestao/gestao_banca/
 * 2. Acesse via navegador: https://seu-dominio.com/gestao/gestao_banca/setup-webhook-servidor.php
 * 3. O webhook será configurado automaticamente
 * 
 */

require_once __DIR__ . '/telegram-config.php';

header('Content-Type: text/html; charset=utf-8');

echo '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Webhook Telegram</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #333;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 3px solid #667eea;
        }
        .box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }
        .success {
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }
        .info {
            background: #d1ecf1;
            border-left-color: #17a2b8;
            color: #0c5460;
        }
        code {
            background: #e9ecef;
            padding: 3px 8px;
            border-radius: 4px;
            font-family: "Courier New", monospace;
            font-size: 13px;
            word-break: break-all;
        }
        pre {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            margin: 15px 0;
        }
        .btn {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 10px 0 0;
            font-weight: 600;
        }
        .btn:hover {
            background: #764ba2;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔗 Configuração do Webhook Telegram</h1>';

// Verificar se está no servidor correto
$isLocalhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);

if ($isLocalhost) {
    echo '<div class="box error">';
    echo '<h2>❌ ERRO: Servidor Local Detectado</h2>';
    echo '<p><strong>Este script deve ser executado NO SERVIDOR HOSTINGER!</strong></p>';
    echo '<p>O Telegram não consegue acessar localhost.</p>';
    echo '<br>';
    echo '<p><strong>Instruções:</strong></p>';
    echo '<ol style="margin-left: 20px; line-height: 2;">';
    echo '<li>Faça upload deste arquivo para o servidor Hostinger</li>';
    echo '<li>Acesse via URL pública: <code>https://seu-dominio.com/gestao/gestao_banca/setup-webhook-servidor.php</code></li>';
    echo '</ol>';
    echo '</div>';
    echo '</div></body></html>';
    exit;
}

// Obter URL do servidor
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$webhookUrl = $protocol . '://' . $host . '/gestao/gestao_banca/api/telegram-webhook.php';

echo '<div class="box info">';
echo '<h2>📋 Informações do Sistema</h2>';
echo '<p><strong>Servidor:</strong> ' . htmlspecialchars($host) . '</p>';
echo '<p><strong>URL do Webhook:</strong><br><code>' . htmlspecialchars($webhookUrl) . '</code></p>';
echo '<p><strong>Token Bot:</strong> ' . substr(TELEGRAM_BOT_TOKEN, 0, 20) . '...</p>';
echo '</div>';

// ============================================
// PASSO 1: REMOVER WEBHOOK ANTIGO
// ============================================
echo '<div class="box">';
echo '<h2>🗑️ Passo 1: Removendo webhook antigo...</h2>';

$deleteUrl = 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/deleteWebhook?drop_pending_updates=true';
$deleteResponse = @file_get_contents($deleteUrl);
$deleteData = json_decode($deleteResponse, true);

if ($deleteData && $deleteData['ok']) {
    echo '<p style="color: #28a745;">✅ Webhook antigo removido com sucesso</p>';
} else {
    echo '<p style="color: #dc3545;">⚠️ Erro ao remover webhook antigo: ' . ($deleteData['description'] ?? 'Desconhecido') . '</p>';
}
echo '</div>';

// ============================================
// PASSO 2: CONFIGURAR NOVO WEBHOOK
// ============================================
echo '<div class="box">';
echo '<h2>🔧 Passo 2: Configurando novo webhook...</h2>';

$setWebhookUrl = 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/setWebhook?url=' . urlencode($webhookUrl);
$setResponse = @file_get_contents($setWebhookUrl);
$setData = json_decode($setResponse, true);

if ($setData && $setData['ok']) {
    echo '<div class="box success">';
    echo '<h2>✅ WEBHOOK CONFIGURADO COM SUCESSO!</h2>';
    echo '<p><strong>Status:</strong> ' . htmlspecialchars($setData['description']) . '</p>';
    echo '<p><strong>URL Configurada:</strong><br><code>' . htmlspecialchars($webhookUrl) . '</code></p>';
    echo '</div>';
} else {
    echo '<div class="box error">';
    echo '<h2>❌ ERRO AO CONFIGURAR WEBHOOK</h2>';
    echo '<p><strong>Mensagem:</strong> ' . htmlspecialchars($setData['description'] ?? 'Erro desconhecido') . '</p>';
    echo '<p><strong>Possíveis causas:</strong></p>';
    echo '<ul style="margin-left: 20px;">';
    echo '<li>URL inacessível (verificar se o arquivo existe)</li>';
    echo '<li>Certificado SSL inválido</li>';
    echo '<li>Token do bot incorreto</li>';
    echo '</ul>';
    echo '</div>';
}
echo '</div>';

// ============================================
// PASSO 3: VERIFICAR STATUS
// ============================================
echo '<div class="box">';
echo '<h2>🔍 Passo 3: Verificando status do webhook...</h2>';

$infoUrl = 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/getWebhookInfo';
$infoResponse = @file_get_contents($infoUrl);
$infoData = json_decode($infoResponse, true);

if ($infoData && $infoData['ok']) {
    $webhookInfo = $infoData['result'];
    
    echo '<table style="width: 100%; border-collapse: collapse; margin-top: 15px;">';
    echo '<tr style="background: #f1f1f1;"><th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Propriedade</th><th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Valor</th></tr>';
    
    echo '<tr><td style="padding: 10px; border: 1px solid #ddd;"><strong>URL</strong></td><td style="padding: 10px; border: 1px solid #ddd;"><code>' . htmlspecialchars($webhookInfo['url'] ?? 'Nenhum') . '</code></td></tr>';
    
    echo '<tr><td style="padding: 10px; border: 1px solid #ddd;"><strong>Tem certificado?</strong></td><td style="padding: 10px; border: 1px solid #ddd;">' . ($webhookInfo['has_custom_certificate'] ?? false ? '✅ Sim' : '❌ Não') . '</td></tr>';
    
    echo '<tr><td style="padding: 10px; border: 1px solid #ddd;"><strong>Atualizações pendentes</strong></td><td style="padding: 10px; border: 1px solid #ddd;">' . ($webhookInfo['pending_update_count'] ?? 0) . '</td></tr>';
    
    if (!empty($webhookInfo['last_error_message'])) {
        echo '<tr style="background: #f8d7da;"><td style="padding: 10px; border: 1px solid #ddd;"><strong>Último erro</strong></td><td style="padding: 10px; border: 1px solid #ddd;">' . htmlspecialchars($webhookInfo['last_error_message']) . '</td></tr>';
        echo '<tr style="background: #f8d7da;"><td style="padding: 10px; border: 1px solid #ddd;"><strong>Data do erro</strong></td><td style="padding: 10px; border: 1px solid #ddd;">' . date('d/m/Y H:i:s', $webhookInfo['last_error_date'] ?? 0) . '</td></tr>';
    }
    
    echo '</table>';
} else {
    echo '<p style="color: #dc3545;">❌ Erro ao obter informações do webhook</p>';
}
echo '</div>';

// ============================================
// PRÓXIMOS PASSOS
// ============================================
echo '<div class="box info">';
echo '<h2>✅ Próximos Passos</h2>';
echo '<ol style="margin-left: 20px; line-height: 2;">';
echo '<li><strong>Teste enviando uma mensagem</strong> no canal Telegram <code>Bateubet_VIP</code></li>';
echo '<li>Verifique se a mensagem chegou no banco de dados</li>';
echo '<li>Acesse o <strong>diagnóstico de mensagens</strong> para ver os logs</li>';
echo '<li>Se não funcionar, verifique o arquivo <code>/logs/telegram-webhook.log</code></li>';
echo '</ol>';
echo '<br>';
echo '<a href="bot_aovivo.php" class="btn">📊 Ver Painel Ao Vivo</a>';
echo '<a href="/gestao/gestao_banca/logs/telegram-webhook.log" class="btn" target="_blank">📄 Ver Log do Webhook</a>';
echo '</div>';

// Comando para testar manualmente
echo '<div class="box">';
echo '<h2>🛠️ Teste Manual (Opcional)</h2>';
echo '<p>Se preferir, você pode testar o webhook manualmente com este comando CURL:</p>';
echo '<pre>curl -X POST "' . htmlspecialchars($webhookUrl) . '" \\
  -H "Content-Type: application/json" \\
  -d \'{
    "update_id": 1,
    "channel_post": {
      "message_id": 999,
      "chat": {"id": -1002047004959, "type": "channel"},
      "date": ' . time() . ',
      "text": "Teste! Oportunidade\\n📊 OVER ( +0.5 ⚽️GOL ) FT\\nRoma (H) x Udinese (A)"
    }
  }\'</pre>';
echo '</div>';

echo '</div>'; // Fecha container
echo '</body></html>';
?>
