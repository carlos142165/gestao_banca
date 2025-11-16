<?php
/**
 * DIAGNÓSTICO DO AMBIENTE - LOCAL vs HOSTINGER
 * Acesse: http://localhost/gestao/gestao_banca/diagnostico-ambiente.php
 */

date_default_timezone_set('America/Sao_Paulo');
header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Ambiente</title>
    <style>
        * { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { margin: 0; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
        h1 { color: #333; border-bottom: 4px solid #667eea; padding-bottom: 15px; margin-top: 0; }
        h2 { color: #555; margin-top: 30px; border-left: 4px solid #667eea; padding-left: 15px; }
        
        .card { background: #f8f9fa; border-left: 4px solid; border-radius: 5px; padding: 15px; margin: 15px 0; }
        .card.ok { border-left-color: #28a745; background: #d4edda; }
        .card.erro { border-left-color: #dc3545; background: #f8d7da; }
        .card.aviso { border-left-color: #ffc107; background: #fff3cd; }
        .card.info { border-left-color: #17a2b8; background: #d1ecf1; }
        
        .status-badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-weight: bold; margin: 5px 5px 5px 0; }
        .status-ok { background: #28a745; color: white; }
        .status-erro { background: #dc3545; color: white; }
        .status-aviso { background: #ffc107; color: black; }
        
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #667eea; color: white; }
        tr:hover { background: #f5f5f5; }
        
        code { background: #f0f0f0; padding: 2px 5px; border-radius: 3px; font-family: 'Courier New', monospace; }
        pre { background: #f0f0f0; padding: 15px; border-radius: 5px; overflow-x: auto; }
        
        .icon { font-size: 24px; margin-right: 10px; }
        .warning { color: #dc3545; font-weight: bold; }
        .success { color: #28a745; font-weight: bold; }
        
        .actions { margin-top: 30px; }
        button { background: #667eea; color: white; padding: 12px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; margin: 5px; }
        button:hover { background: #5568d3; }
    </style>
</head>
<body>

<div class="container">
    <h1>🔍 Diagnóstico de Ambiente - Local vs Hostinger</h1>

<?php

require_once 'config.php';

// ============================================
// 1. INFORMAÇÕES DO SERVIDOR
// ============================================
echo "<h2>1️⃣ Informações do Servidor</h2>";

$isLocalhost = (
    $_SERVER['HTTP_HOST'] === 'localhost' ||
    $_SERVER['HTTP_HOST'] === '127.0.0.1' ||
    strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost:') === 0
);

echo '<table>';
echo '<tr><th>Propriedade</th><th>Valor</th></tr>';

$properties = [
    'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? 'N/A',
    'SERVER_NAME' => $_SERVER['SERVER_NAME'] ?? 'N/A',
    'SERVER_ADDR' => $_SERVER['SERVER_ADDR'] ?? 'N/A',
    'SCRIPT_FILENAME' => $_SERVER['SCRIPT_FILENAME'] ?? 'N/A',
    'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? 'N/A',
    'PHP_VERSION' => phpversion(),
    'PHP_OS' => PHP_OS,
    'USER' => get_current_user() ?: 'N/A'
];

foreach ($properties as $key => $value) {
    echo '<tr>';
    echo '<td><code>' . $key . '</code></td>';
    echo '<td>' . htmlspecialchars($value) . '</td>';
    echo '</tr>';
}

echo '</table>';

// ============================================
// 2. AMBIENTE DETECTADO
// ============================================
echo "<h2>2️⃣ Ambiente Detectado</h2>";

$environment = defined('ENVIRONMENT') ? ENVIRONMENT : 'undefined';
$isCorrect = ($environment === 'local' && $isLocalhost) || ($environment === 'production' && !$isLocalhost);

if ($environment === 'local') {
    echo '<div class="card info">';
    echo '<span class="icon">💻</span>';
    echo '<strong>Ambiente: LOCAL (XAMPP)</strong><br>';
    echo 'Você está em modo desenvolvimento local<br>';
    echo '<span class="status-badge status-aviso">Banco Local</span>';
    echo '</div>';
} elseif ($environment === 'production') {
    echo '<div class="card info">';
    echo '<span class="icon">🌐</span>';
    echo '<strong>Ambiente: PRODUCTION (Hostinger)</strong><br>';
    echo 'Você está em modo produção remoto<br>';
    echo '<span class="status-badge status-ok">Banco Remoto</span>';
    echo '</div>';
} else {
    echo '<div class="card erro">';
    echo '<span class="icon">❌</span>';
    echo '<strong>ERRO: Ambiente não definido!</strong><br>';
    echo '</div>';
}

// ============================================
// 3. CONFIGURAÇÃO DO BANCO
// ============================================
echo "<h2>3️⃣ Configuração do Banco de Dados</h2>";

echo '<table>';
echo '<tr><th>Configuração</th><th>Valor</th></tr>';
echo '<tr><td><code>DB_HOST</code></td><td><code>' . DB_HOST . '</code></td></tr>';
echo '<tr><td><code>DB_USERNAME</code></td><td><code>' . DB_USERNAME . '</code></td></tr>';
echo '<tr><td><code>DB_PASSWORD</code></td><td><code>' . (DB_PASSWORD ? '(senha configurada)' : '(sem senha)') . '</code></td></tr>';
echo '<tr><td><code>DB_NAME</code></td><td><code>' . DB_NAME . '</code></td></tr>';
echo '</table>';

// ============================================
// 4. TESTE DE CONEXÃO
// ============================================
echo "<h2>4️⃣ Teste de Conexão com Banco</h2>";

$conexao_test = @new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

if (!$conexao_test->connect_error) {
    echo '<div class="card ok">';
    echo '<span class="icon">✅</span>';
    echo '<strong>Conexão com sucesso!</strong><br>';
    
    // Informações do banco
    $result = $conexao_test->query("SELECT VERSION() as version, DATABASE() as database, USER() as user");
    $row = $result->fetch_assoc();
    
    echo '<table>';
    echo '<tr><td>Versão MySQL:</td><td><code>' . $row['version'] . '</code></td></tr>';
    echo '<tr><td>Banco Ativo:</td><td><code>' . $row['database'] . '</code></td></tr>';
    echo '<tr><td>Usuário MySQL:</td><td><code>' . $row['user'] . '</code></td></tr>';
    echo '</table>';
    
    // Contar tabelas
    $tables = $conexao_test->query("SHOW TABLES");
    echo '<br><strong>Tabelas encontradas: ' . $tables->num_rows . '</strong>';
    
    // Verificar tabela bote
    $boteCheck = $conexao_test->query("SHOW TABLES LIKE 'bote'");
    if ($boteCheck->num_rows > 0) {
        echo '<br><span class="success">✅ Tabela "bote" existe</span>';
        
        // Contar mensagens
        $msgCount = $conexao_test->query("SELECT COUNT(*) as total FROM bote");
        $msgRow = $msgCount->fetch_assoc();
        echo '<br><span class="status-badge status-ok">Mensagens: ' . $msgRow['total'] . '</span>';
    } else {
        echo '<br><span class="warning">⚠️ Tabela "bote" NÃO encontrada</span>';
    }
    
    echo '</div>';
    
    $conexao_test->close();
} else {
    echo '<div class="card erro">';
    echo '<span class="icon">❌</span>';
    echo '<strong>Erro de conexão!</strong><br>';
    echo 'Erro: ' . $conexao_test->connect_error . '<br>';
    echo '<br><div class="warning">⚠️ Não foi possível conectar ao banco em ' . DB_HOST . '</div>';
    echo '</div>';
}

// ============================================
// 5. STATUS DO WEBHOOK
// ============================================
echo "<h2>5️⃣ Status do Webhook Telegram</h2>";

require_once 'telegram-config.php';

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, TELEGRAM_API_URL . '/getWebhookInfo');
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($curl, CURLOPT_TIMEOUT, 10);

$response = @curl_exec($curl);
$curlError = curl_error($curl);
curl_close($curl);

if ($response) {
    $data = json_decode($response, true);
    
    if ($data['ok'] && isset($data['result']['url'])) {
        $webhookUrl = $data['result']['url'];
        
        // Verificar se a URL corresponde ao ambiente
        if (strpos($webhookUrl, 'localhost') !== false || strpos($webhookUrl, '127.0.0.1') !== false) {
            $webhookEnv = 'local';
        } else {
            $webhookEnv = 'production';
        }
        
        echo '<div class="card info">';
        echo '<span class="icon">🔗</span>';
        echo '<strong>Webhook Ativo</strong><br>';
        echo 'URL: <code>' . htmlspecialchars($webhookUrl) . '</code><br>';
        echo 'Ambiente do Webhook: <span class="status-badge status-' . ($webhookEnv === 'local' ? 'aviso' : 'ok') . '">' . strtoupper($webhookEnv) . '</span><br>';
        
        if (isset($data['result']['pending_update_count'])) {
            echo 'Mensagens Pendentes: <strong>' . $data['result']['pending_update_count'] . '</strong><br>';
        }
        
        if (isset($data['result']['last_error_message'])) {
            echo '<br><span class="warning">⚠️ Último Erro: ' . $data['result']['last_error_message'] . '</span>';
            echo '<br>Data: ' . date('d/m/Y H:i:s', $data['result']['last_error_date']) . '';
        }
        
        echo '</div>';
        
        // ⚠️ AVISO se houver mismatch
        if ($webhookEnv !== $environment) {
            echo '<div class="card erro">';
            echo '<span class="icon">⚠️</span>';
            echo '<strong style="color: red;">CONFLITO DETECTADO!</strong><br>';
            echo 'O webhook está apontando para <strong>' . strtoupper($webhookEnv) . '</strong><br>';
            echo 'Mas o código está configurado para <strong>' . strtoupper($environment) . '</strong><br>';
            echo '<br><strong>Consequência:</strong> Mensagens do Telegram podem não chegar corretamente!';
            echo '</div>';
        }
    } else {
        echo '<div class="card aviso">';
        echo '<span class="icon">⚠️</span>';
        echo '<strong>Webhook não configurado</strong><br>';
        echo 'Nenhum webhook ativo no Telegram<br>';
        echo '</div>';
    }
} else {
    echo '<div class="card erro">';
    echo '<span class="icon">❌</span>';
    echo '<strong>Erro ao conectar com Telegram</strong><br>';
    echo 'Erro: ' . $curlError . '<br>';
    echo '</div>';
}

// ============================================
// 6. LOG DO WEBHOOK
// ============================================
echo "<h2>6️⃣ Log do Webhook</h2>";

$logFile = __DIR__ . '/logs/telegram-webhook.log';

if (file_exists($logFile)) {
    $fileSize = filesize($logFile);
    $lastModified = date('d/m/Y H:i:s', filemtime($logFile));
    
    echo '<div class="card ok">';
    echo '<span class="icon">✅</span>';
    echo '<strong>Log encontrado</strong><br>';
    echo 'Tamanho: ' . number_format($fileSize / 1024, 2) . ' KB<br>';
    echo 'Última atualização: ' . $lastModified . '<br>';
    echo '</div>';
    
    echo '<h3>Últimas Linhas do Log:</h3>';
    $lines = array_slice(file($logFile), -20);
    echo '<pre>';
    foreach ($lines as $line) {
        echo htmlspecialchars($line);
    }
    echo '</pre>';
} else {
    echo '<div class="card aviso">';
    echo '<span class="icon">⚠️</span>';
    echo '<strong>Log não encontrado</strong><br>';
    echo 'Será criado quando o webhook receber a primeira mensagem<br>';
    echo '</div>';
}

// ============================================
// 7. RECOMENDAÇÕES
// ============================================
echo "<h2>7️⃣ Recomendações</h2>";

if ($environment === 'local' && !$isLocalhost) {
    echo '<div class="card erro">';
    echo '<strong>❌ ERRO:</strong> Você está acessando de um servidor remoto, mas o ambiente está configurado para LOCAL!<br>';
    echo 'Possível solução: Acessar via http://localhost/gestao/gestao_banca/diagnostico-ambiente.php';
    echo '</div>';
}

if ($environment === 'production' && $isLocalhost) {
    echo '<div class="card aviso">';
    echo '<strong>⚠️ AVISO:</strong> Você está em LOCALHOST, mas o código está configurado para PRODUCTION!<br>';
    echo 'As mensagens podem estar sendo salvas no banco remoto (Hostinger) em vez do local.';
    echo '</div>';
} else if ($environment === 'local' && $isLocalhost) {
    echo '<div class="card ok">';
    echo '<strong>✅ OK:</strong> Ambiente LOCAL detectado corretamente<br>';
    echo 'As mensagens estão sendo salvas no banco local (XAMPP)';
    echo '</div>';
} else if ($environment === 'production' && !$isLocalhost) {
    echo '<div class="card ok">';
    echo '<strong>✅ OK:</strong> Ambiente PRODUCTION detectado corretamente<br>';
    echo 'As mensagens estão sendo salvas no banco remoto (Hostinger)';
    echo '</div>';
}

?>

    <div class="actions">
        <button onclick="location.reload()">🔄 Recarregar</button>
        <button onclick="window.open('teste-webhook.php')">📋 Ver Banco de Dados</button>
        <button onclick="window.open('setup.php')">🔧 Setup do Webhook</button>
    </div>

    <hr style="margin-top: 40px; border: none; border-top: 2px solid #ddd;">
    <p style="text-align: center; color: #666; margin-top: 20px;">
        <small>Gerado em: <?php echo date('d/m/Y H:i:s'); ?></small>
    </p>
</div>

</body>
</html>
