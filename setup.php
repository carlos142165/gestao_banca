<?php
/**
 * SETUP AUTOMÁTICO - WEBHOOK TELEGRAM
 * Acesse: https://analisegb.com/gestao_banca/setup.php
 * 
 * Este arquivo vai:
 * 1. Verificar configurações
 * 2. Criar arquivos .htaccess se faltarem
 * 3. Criar pasta logs se faltar
 * 4. Testar conexão com banco
 * 5. Mostrar status completo
 */

date_default_timezone_set('America/Sao_Paulo');
header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Setup Webhook</title>
    <style>
        * { font-family: Arial, sans-serif; }
        body { margin: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 5px; }
        h1 { color: #333; border-bottom: 3px solid #2196F3; padding-bottom: 10px; }
        h2 { color: #666; margin-top: 30px; }
        .ok { background: #c8e6c9; color: #2e7d32; padding: 12px; margin: 10px 0; border-left: 4px solid #2e7d32; }
        .erro { background: #ffcdd2; color: #c62828; padding: 12px; margin: 10px 0; border-left: 4px solid #c62828; }
        .aviso { background: #fff9c4; color: #f57f17; padding: 12px; margin: 10px 0; border-left: 4px solid #f57f17; }
        .info { background: #bbdefb; color: #1565c0; padding: 12px; margin: 10px 0; border-left: 4px solid #1565c0; }
        code { background: #f0f0f0; padding: 2px 5px; border-radius: 3px; }
        .status { display: inline-block; padding: 5px 10px; border-radius: 3px; font-weight: bold; }
        .status-ok { background: #4caf50; color: white; }
        .status-erro { background: #f44336; color: white; }
        button { background: #2196F3; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 10px 0; }
        button:hover { background: #1976d2; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>

<div class="container">
    <h1>🔧 Setup Automático - Webhook Telegram</h1>

<?php

$success = true;

// 1. VERIFICAR E CRIAR PASTA LOGS
echo "<h2>1️⃣ Verificar/Criar Pasta Logs</h2>";
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    if (mkdir($logDir, 0755, true)) {
        echo "<div class='ok'>✅ Pasta /logs/ criada com sucesso</div>";
    } else {
        echo "<div class='erro'>❌ Erro ao criar pasta /logs/</div>";
        $success = false;
    }
} else {
    echo "<div class='ok'>✅ Pasta /logs/ já existe</div>";
}

// 2. CRIAR .htaccess NA RAIZ
echo "<h2>2️⃣ Verificar/Criar .htaccess na Raiz</h2>";
$htaccessRoot = __DIR__ . '/.htaccess';
$htaccessContent = '# Remover redirecionamentos em loop
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # Permitir acesso direto aos arquivos PHP da pasta api sem redirecionamentos
    RewriteCond %{REQUEST_FILENAME} -f
    RewriteCond %{REQUEST_FILENAME} ^/gestao_banca/api/
    RewriteRule ^ - [L]
    
    # Desabilitar redirecionamentos para o webhook
    RewriteCond %{REQUEST_URI} ^/gestao_banca/api/telegram-webhook\.php$
    RewriteRule ^ - [L]
    
    # Desabilitar redirecionamentos para carregar-mensagens-banco
    RewriteCond %{REQUEST_URI} ^/gestao_banca/api/carregar-mensagens-banco\.php$
    RewriteRule ^ - [L]
</IfModule>';

if (file_exists($htaccessRoot)) {
    echo "<div class='aviso'>⚠️ Arquivo .htaccess já existe</div>";
    echo "<p>Sobrescrevendo para garantir que tem o conteúdo correto...</p>";
}

if (file_put_contents($htaccessRoot, $htaccessContent)) {
    echo "<div class='ok'>✅ .htaccess na raiz OK</div>";
} else {
    echo "<div class='erro'>❌ Erro ao criar .htaccess na raiz</div>";
    $success = false;
}

// 3. CRIAR .htaccess NA PASTA API
echo "<h2>3️⃣ Verificar/Criar .htaccess na Pasta API</h2>";
$apiDir = __DIR__ . '/api';
$htaccessApi = $apiDir . '/.htaccess';
$htaccessApiContent = '# Proteção para o webhook - não fazer redirecionamentos
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # Para o webhook do Telegram - sem redirecionamentos
    RewriteCond %{REQUEST_URI} ^/gestao_banca/api/telegram-webhook\.php$
    RewriteRule ^ - [L]
    
    # Para arquivos da API - sem redirecionamentos
    RewriteCond %{REQUEST_FILENAME} -f
    RewriteCond %{REQUEST_FILENAME} ^/gestao_banca/api/
    RewriteRule ^ - [L]
</IfModule>';

if (!is_dir($apiDir)) {
    echo "<div class='erro'>❌ Pasta /api/ não existe!</div>";
    $success = false;
} else {
    if (file_exists($htaccessApi)) {
        echo "<div class='aviso'>⚠️ Arquivo .htaccess/api já existe</div>";
        echo "<p>Sobrescrevendo para garantir que tem o conteúdo correto...</p>";
    }
    
    if (file_put_contents($htaccessApi, $htaccessApiContent)) {
        echo "<div class='ok'>✅ .htaccess na pasta /api/ OK</div>";
    } else {
        echo "<div class='erro'>❌ Erro ao criar .htaccess na pasta /api/</div>";
        $success = false;
    }
}

// 4. TESTAR BANCO DE DADOS
echo "<h2>4️⃣ Testar Banco de Dados</h2>";
require_once 'config.php';

$conexao = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conexao->connect_error) {
    echo "<div class='erro'>❌ Erro ao conectar: " . $conexao->connect_error . "</div>";
    $success = false;
} else {
    echo "<div class='ok'>✅ Banco conectado</div>";
    
    // Verificar tabela bote
    $result = $conexao->query("SHOW TABLES LIKE 'bote'");
    if ($result && $result->num_rows > 0) {
        echo "<div class='ok'>✅ Tabela 'bote' existe</div>";
        
        // Contar mensagens
        $countResult = $conexao->query("SELECT COUNT(*) as total FROM bote");
        $count = $countResult->fetch_assoc();
        echo "<div class='info'>📊 Total de mensagens: <strong>" . $count['total'] . "</strong></div>";
    } else {
        echo "<div class='erro'>❌ Tabela 'bote' não existe</div>";
        $success = false;
    }
    
    $conexao->close();
}

// 5. TESTAR ARQUIVO DE CONFIG
echo "<h2>5️⃣ Verificar Configurações</h2>";
if (file_exists('config.php')) {
    echo "<div class='ok'>✅ config.php existe</div>";
} else {
    echo "<div class='erro'>❌ config.php não existe</div>";
    $success = false;
}

if (file_exists('telegram-config.php')) {
    require_once 'telegram-config.php';
    echo "<div class='ok'>✅ telegram-config.php existe</div>";
    echo "<div class='info'>Token (primeiros 20 caracteres): " . substr(TELEGRAM_BOT_TOKEN, 0, 20) . "...</div>";
} else {
    echo "<div class='erro'>❌ telegram-config.php não existe</div>";
    $success = false;
}

// 6. VERIFICAR WEBHOOK NO TELEGRAM
echo "<h2>6️⃣ Status Webhook no Telegram</h2>";
if (defined('TELEGRAM_BOT_TOKEN')) {
    $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/getWebhookInfo";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($response) {
        $data = json_decode($response, true);
        if ($data['ok']) {
            echo "<div class='ok'>✅ Webhook ATIVO no Telegram</div>";
            echo "<div class='info'>";
            echo "URL: " . $data['result']['url'] . "<br>";
            echo "Mensagens Pendentes: " . $data['result']['pending_update_count'] . "<br>";
            if ($data['result']['last_error_message']) {
                echo "Último Erro: " . $data['result']['last_error_message'] . " (" . date('d/m/Y H:i:s', $data['result']['last_error_date']) . ")<br>";
            }
            echo "</div>";
        } else {
            echo "<div class='erro'>❌ Webhook NÃO configurado no Telegram</div>";
        }
    } else {
        echo "<div class='erro'>❌ Erro ao conectar com Telegram: " . $curlError . "</div>";
    }
}

// 7. RESULTADO FINAL
echo "<h2>7️⃣ Resultado Final</h2>";
if ($success) {
    echo "<div class='ok' style='font-size: 18px;'><strong>✅ SETUP COMPLETO COM SUCESSO!</strong></div>";
    echo "<p style='font-size: 16px; margin-top: 20px;'><strong>Próximos passos:</strong></p>";
    echo "<ol>";
    echo "<li>Limpe cookies do navegador (Ctrl+Shift+Del)</li>";
    echo "<li>Envie uma mensagem no Telegram com o formato correto</li>";
    echo "<li>Aguarde 2-3 segundos</li>";
    echo "<li><a href='teste-webhook.php' style='color: #2196F3;'>Clique aqui para ver o status</a></li>";
    echo "</ol>";
} else {
    echo "<div class='erro'><strong>❌ ERROS ENCONTRADOS</strong> - Verifique os itens acima</div>";
}

?>

    <hr style="margin-top: 30px; border: none; border-top: 2px solid #ddd;">
    <p style="text-align: center; color: #666; margin-top: 20px;">
        <small>Gerado em: <?php echo date('d/m/Y H:i:s'); ?></small><br>
        <button onclick="location.reload()">🔄 Recarregar</button>
    </p>
</div>

</body>
</html>
