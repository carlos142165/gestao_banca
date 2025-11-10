<?php
/**
 * ✅ CHECKLIST DO WEBHOOK TELEGRAM
 * Acesse: http://localhost/gestao/gestao_banca/checklist-webhook.php
 */

date_default_timezone_set('America/Sao_Paulo');
header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Checklist do Webhook</title>
    <style>
        * { font-family: 'Segoe UI', sans-serif; }
        body { margin: 0; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
        
        h1 { color: #2c3e50; margin-top: 0; }
        
        .checklist { list-style: none; padding: 0; }
        .checklist li { padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 5px solid #3498db; background: #ecf0f1; display: flex; align-items: center; }
        .checklist li.pass { border-left-color: #27ae60; background: #d5f4e6; }
        .checklist li.fail { border-left-color: #e74c3c; background: #fadbd8; }
        .checklist li.warning { border-left-color: #f39c12; background: #fef5e7; }
        
        .check-icon { font-size: 24px; margin-right: 15px; min-width: 30px; }
        .check-text { flex: 1; }
        .check-text strong { display: block; color: #2c3e50; }
        .check-text small { color: #7f8c8d; }
        
        button { background: #3498db; color: white; padding: 12px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 20px 5px; font-size: 14px; }
        button:hover { background: #2980b9; }
        
        .summary { background: #ecf0f1; padding: 20px; border-radius: 5px; margin: 20px 0; }
        .summary strong { color: #2c3e50; }
        
        a { color: #3498db; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="container">
    <h1>✅ Checklist do Webhook Telegram</h1>
    
    <p>Acompanhe o status de cada componente do webhook:</p>

<?php

require_once 'config.php';

$checks = [];

// 1. Ambiente
$env = defined('ENVIRONMENT') ? ENVIRONMENT : 'desconhecido';
$isLocal = $env === 'local';
$checks[] = [
    'title' => 'Ambiente Detectado',
    'description' => 'Ambiente: ' . strtoupper($env) . ' | Banco: ' . DB_NAME . ' | Host: ' . DB_HOST,
    'status' => $isLocal ? 'pass' : 'warning',
    'icon' => $isLocal ? '💻' : '🌐'
];

// 2. Banco de Dados
$conexao_test = @new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
if (!$conexao_test->connect_error) {
    $checks[] = [
        'title' => 'Conexão com Banco',
        'description' => 'Conectado com sucesso em ' . DB_HOST . ' | Banco: ' . DB_NAME,
        'status' => 'pass',
        'icon' => '✅'
    ];
    
    // 3. Tabela bote
    $table_check = $conexao_test->query("SHOW TABLES LIKE 'bote'");
    if ($table_check && $table_check->num_rows > 0) {
        $count = $conexao_test->query("SELECT COUNT(*) as total FROM bote");
        $row = $count->fetch_assoc();
        $checks[] = [
            'title' => 'Tabela "bote"',
            'description' => 'Tabela existe | Total de mensagens: ' . $row['total'],
            'status' => 'pass',
            'icon' => '📊'
        ];
    } else {
        $checks[] = [
            'title' => 'Tabela "bote"',
            'description' => 'TABELA NÃO ENCONTRADA - Webhook não conseguirá salvar dados!',
            'status' => 'fail',
            'icon' => '❌'
        ];
    }
    
    $conexao_test->close();
} else {
    $checks[] = [
        'title' => 'Conexão com Banco',
        'description' => 'ERRO: ' . $conexao_test->connect_error,
        'status' => 'fail',
        'icon' => '❌'
    ];
}

// 4. Arquivo de Webhook
$webhookFile = __DIR__ . '/api/telegram-webhook.php';
$checks[] = [
    'title' => 'Arquivo Webhook',
    'description' => 'Localização: api/telegram-webhook.php | Status: ' . (file_exists($webhookFile) ? 'Existe' : 'Não encontrado'),
    'status' => file_exists($webhookFile) ? 'pass' : 'fail',
    'icon' => file_exists($webhookFile) ? '✅' : '❌'
];

// 5. Pasta de Logs
$logDir = __DIR__ . '/logs';
$checks[] = [
    'title' => 'Pasta de Logs',
    'description' => 'Localização: logs/ | Status: ' . (is_dir($logDir) ? 'Existe' : 'Não existe'),
    'status' => is_dir($logDir) ? 'pass' : 'warning',
    'icon' => is_dir($logDir) ? '✅' : '⚠️'
];

// 6. Arquivo de Log
$logFile = $logDir . '/telegram-webhook.log';
if (file_exists($logFile)) {
    $size = filesize($logFile);
    $modified = date('d/m/Y H:i:s', filemtime($logFile));
    $checks[] = [
        'title' => 'Log do Webhook',
        'description' => 'Arquivo existe | Tamanho: ' . number_format($size / 1024, 2) . ' KB | Última atualização: ' . $modified,
        'status' => 'pass',
        'icon' => '✅'
    ];
} else {
    $checks[] = [
        'title' => 'Log do Webhook',
        'description' => 'Arquivo ainda não criado (será criado quando webhook receber primeira mensagem)',
        'status' => 'warning',
        'icon' => '⚠️'
    ];
}

// 7. Configuração do Telegram
require_once 'telegram-config.php';
$checks[] = [
    'title' => 'Token Telegram',
    'description' => 'Token (primeiros 20 caracteres): ' . substr(TELEGRAM_BOT_TOKEN, 0, 20) . '...',
    'status' => 'pass',
    'icon' => '🔑'
];

// Exibir checklist
echo '<ul class="checklist">';
foreach ($checks as $check) {
    echo '<li class="' . $check['status'] . '">';
    echo '<span class="check-icon">' . $check['icon'] . '</span>';
    echo '<div class="check-text">';
    echo '<strong>' . $check['title'] . '</strong>';
    echo '<small>' . $check['description'] . '</small>';
    echo '</div>';
    echo '</li>';
}
echo '</ul>';

// Resumo
$pass = count(array_filter($checks, fn($c) => $c['status'] === 'pass'));
$fail = count(array_filter($checks, fn($c) => $c['status'] === 'fail'));
$warning = count(array_filter($checks, fn($c) => $c['status'] === 'warning'));

echo '<div class="summary">';
echo '<strong>Resumo:</strong><br>';
echo '✅ Passou: ' . $pass . ' | ⚠️ Avisos: ' . $warning . ' | ❌ Falhas: ' . $fail;
echo '</div>';

// Próximos passos
if ($fail > 0) {
    echo '<div class="summary" style="border-left: 4px solid #e74c3c; background: #fadbd8;">';
    echo '<strong style="color: #c0392b;">⚠️ ATENÇÃO - Existem falhas no sistema!</strong><br>';
    echo 'Por favor, verifique os itens em vermelho antes de usar o webhook.';
    echo '</div>';
} else if ($warning > 0) {
    echo '<div class="summary" style="border-left: 4px solid #f39c12; background: #fef5e7;">';
    echo '<strong style="color: #d68910;">ℹ️ Aviso - Alguns itens precisam de atenção</strong><br>';
    echo 'Verifique os itens em amarelo, mas o webhook deve funcionar.';
    echo '</div>';
} else {
    echo '<div class="summary" style="border-left: 4px solid #27ae60; background: #d5f4e6;">';
    echo '<strong style="color: #229954;">✅ Tudo OK - Webhook pronto para usar!</strong><br>';
    echo 'Todos os componentes estão funcionando corretamente.';
    echo '</div>';
}

?>

    <h2>🚀 Próximos Passos</h2>
    
    <ol>
        <li>Acesse <a href="teste-webhook-completo.php">teste-webhook-completo.php</a> para um teste mais detalhado</li>
        <li>Envie uma mensagem no Telegram com o formato correto</li>
        <li>Recarregue esta página para ver a mensagem salva</li>
        <li>Verifique em <a href="bot_aovivo.php">bot_aovivo.php</a></li>
    </ol>

    <h2>🔧 Links Úteis</h2>
    
    <div style="margin: 20px 0;">
        <button onclick="window.location.href='diagnostico-ambiente.php'">🔍 Diagnóstico Completo</button>
        <button onclick="window.location.href='teste-webhook-completo.php'">🧪 Teste Completo</button>
        <button onclick="window.location.href='bot_aovivo.php'">🤖 Bot ao Vivo</button>
        <button onclick="window.location.href='instrucoes.php'">📋 Instruções</button>
        <button onclick="location.reload()">🔄 Recarregar</button>
    </div>

    <hr style="margin-top: 40px; border: none; border-top: 2px solid #ddd;">
    <p style="text-align: center; color: #7f8c8d; font-size: 12px;">
        Verificação realizada em: <?php echo date('d/m/Y H:i:s'); ?>
    </p>
</div>

</body>
</html>
