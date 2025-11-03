<?php
/**
 * TESTE SIMPLES - WEBHOOK
 * Acesse: https://analisegb.com/gestao_banca/teste-webhook.php
 */

date_default_timezone_set('America/Sao_Paulo');
header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Teste Webhook</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .ok { color: green; background: #e8f5e9; padding: 10px; margin: 10px 0; }
        .erro { color: red; background: #ffebee; padding: 10px; margin: 10px 0; }
        .info { background: #e3f2fd; padding: 10px; margin: 10px 0; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #f0f0f0; }
    </style>
</head>
<body>

<h1>🔍 Teste do Webhook</h1>

<?php

// 1. Testar conexão banco
echo "<h2>1. Conexão com Banco</h2>";
require_once 'config.php';

$conexao = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conexao->connect_error) {
    echo "<div class='erro'>❌ ERRO: " . $conexao->connect_error . "</div>";
    die();
} else {
    echo "<div class='ok'>✅ Conectado com sucesso</div>";
}

// 2. Verificar tabela
echo "<h2>2. Tabela 'bote'</h2>";
$result = $conexao->query("SHOW TABLES LIKE 'bote'");
if ($result && $result->num_rows > 0) {
    echo "<div class='ok'>✅ Tabela existe</div>";
} else {
    echo "<div class='erro'>❌ Tabela NÃO existe</div>";
}

// 3. Contar mensagens
echo "<h2>3. Mensagens Salvas</h2>";
$countResult = $conexao->query("SELECT COUNT(*) as total FROM bote");
$count = $countResult->fetch_assoc();
echo "<div class='info'>📊 Total: <strong>" . $count['total'] . "</strong> mensagens</div>";

// 4. Listar últimas
echo "<h2>4. Últimas 10 Mensagens</h2>";
$recent = $conexao->query("SELECT id, titulo, data_criacao FROM bote ORDER BY id DESC LIMIT 10");
if ($recent && $recent->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>ID</th><th>Título</th><th>Data</th></tr>";
    while ($row = $recent->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . substr($row['titulo'], 0, 50) . "</td>";
        echo "<td>" . $row['data_criacao'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='erro'>⚠️ Nenhuma mensagem salva</div>";
}

// 5. Verificar log
echo "<h2>5. Log do Webhook</h2>";
$logFile = 'logs/telegram-webhook.log';
if (file_exists($logFile)) {
    echo "<div class='ok'>✅ Log existe</div>";
    echo "<p>Tamanho: " . filesize($logFile) . " bytes</p>";
    echo "<p><strong>Últimas linhas:</strong></p>";
    echo "<pre style='background: #f5f5f5; padding: 10px; max-height: 300px; overflow-y: auto; border: 1px solid #ddd;'>";
    $lines = file($logFile);
    $lastLines = array_slice($lines, -30);
    foreach ($lastLines as $line) {
        echo htmlspecialchars($line);
    }
    echo "</pre>";
} else {
    echo "<div class='erro'>⚠️ Log não existe (será criado quando receber mensagem)</div>";
}

$conexao->close();

?>

<hr>
<h2>📝 O que fazer:</h2>
<ol>
    <li>Envie uma mensagem no Telegram com o formato correto</li>
    <li>Aguarde 2-3 segundos</li>
    <li>Recarregue esta página (F5)</li>
    <li>Verifique se a mensagem aparece na tabela acima</li>
</ol>

<h2>✅ Formato correto:</h2>
<pre style='background: #fffacd; padding: 15px; border: 2px solid #ff9800;'>
Oportunidade! 🚨
📊 OVER ( +2.5 ⚽GOLS )
Flamengo (H) x Botafogo (A)
Placar: 1 - 0
⛳ Escanteios: 5 - 3
Gols over +2.5 : 1.75
</pre>

</body>
</html>
