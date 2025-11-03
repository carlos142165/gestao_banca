<?php
/**
 * DIAGNÓSTICO COMPLETO - WEBHOOK TELEGRAM HOSTINGER
 * Acesse: https://analisegb.com/gestao_banca/diagnostico-completo.php
 */

date_default_timezone_set('America/Sao_Paulo');
header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔍 DIAGNÓSTICO WEBHOOK TELEGRAM - HOSTINGER</h1>";
echo "<hr>";
echo "<p><strong>Data/Hora:</strong> " . date('d/m/Y H:i:s') . "</p>";
echo "<hr>";

// 1. VERIFICAR CONFIGURAÇÕES
echo "<h2>1️⃣ VERIFICAR ARQUIVOS DE CONFIGURAÇÃO</h2>";

if (file_exists('../telegram-config.php')) {
    require_once '../telegram-config.php';
    echo "✅ telegram-config.php encontrado<br>";
    echo "   Token: " . substr(TELEGRAM_BOT_TOKEN, 0, 15) . "...<br>";
    echo "   Channel ID: " . TELEGRAM_CHANNEL_ID . "<br>";
} else {
    echo "❌ telegram-config.php NÃO ENCONTRADO<br>";
}

if (file_exists('../config.php')) {
    require_once '../config.php';
    echo "✅ config.php encontrado<br>";
    echo "   Host: " . DB_HOST . "<br>";
    echo "   Usuário: " . DB_USERNAME . "<br>";
    echo "   Banco: " . DB_NAME . "<br>";
} else {
    echo "❌ config.php NÃO ENCONTRADO<br>";
}

echo "<hr>";

// 2. CONECTAR BANCO
echo "<h2>2️⃣ CONEXÃO COM BANCO DE DADOS</h2>";

$conexao = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conexao->connect_error) {
    echo "❌ ERRO ao conectar: " . $conexao->connect_error . "<br>";
    die();
} else {
    echo "✅ Conectado com sucesso<br>";
    
    // Verificar tabela bote
    $result = $conexao->query("SHOW TABLES LIKE 'bote'");
    if ($result && $result->num_rows > 0) {
        echo "✅ Tabela 'bote' existe<br>";
    } else {
        echo "❌ Tabela 'bote' NÃO EXISTE<br>";
    }
    
    // Contar registros
    $countResult = $conexao->query("SELECT COUNT(*) as total FROM bote");
    $count = $countResult->fetch_assoc();
    echo "📊 Total de mensagens: " . $count['total'] . "<br>";
    
    echo "<hr>";
    
    // 3. LISTAR ÚLTIMAS MENSAGENS
    echo "<h2>3️⃣ ÚLTIMAS MENSAGENS SALVAS</h2>";
    
    $recent = $conexao->query("SELECT id, titulo, data_criacao FROM bote ORDER BY id DESC LIMIT 10");
    if ($recent && $recent->num_rows > 0) {
        echo "<table border='1' cellpadding='10' style='width: 100%; margin-top: 10px;'>";
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
        echo "⚠️ Nenhuma mensagem salva ainda<br>";
    }
}

echo "<hr>";

// 4. VERIFICAR LOG
echo "<h2>4️⃣ LOG DO WEBHOOK</h2>";

$logFile = '../logs/telegram-webhook.log';
if (file_exists($logFile)) {
    echo "✅ Arquivo de log existe<br>";
    echo "<strong>Tamanho:</strong> " . filesize($logFile) . " bytes<br>";
    echo "<strong>Últimas linhas:</strong><br>";
    echo "<pre style='background: #f0f0f0; padding: 10px; max-height: 400px; overflow-y: auto; border: 1px solid #ddd;'>";
    
    $lines = file($logFile);
    $lastLines = array_slice($lines, -50);
    foreach ($lastLines as $line) {
        echo htmlspecialchars($line);
    }
    echo "</pre>";
} else {
    echo "⚠️ Arquivo de log NÃO EXISTE (será criado quando receber mensagem)<br>";
}

echo "<hr>";

// 5. TESTAR WEBHOOK
echo "<h2>5️⃣ TESTE DO WEBHOOK</h2>";

echo "<p><strong>Para testar, envie uma mensagem no Telegram exatamente assim:</strong></p>";
echo "<pre style='background: #fffacd; padding: 15px; border: 2px solid #ff9800; border-radius: 5px;'>";
echo "Oportunidade! 🚨\n";
echo "📊 OVER ( +2.5 ⚽GOLS )\n";
echo "Flamengo (H) x Botafogo (A)\n";
echo "Placar: 1 - 0\n";
echo "⛳ Escanteios: 5 - 3\n";
echo "Gols over +2.5 : 1.75\n";
echo "</pre>";

echo "<p><strong>Depois de enviar:</strong></p>";
echo "<ol>";
echo "<li>Aguarde 2-3 segundos</li>";
echo "<li>Recarregue esta página (F5)</li>";
echo "<li>Verifique se a mensagem aparece na tabela acima</li>";
echo "<li>Verifique o log abaixo</li>";
echo "</ol>";

echo "<hr>";

// 6. VERIFICAR WEBHOOK NO TELEGRAM
echo "<h2>6️⃣ STATUS DO WEBHOOK NO TELEGRAM</h2>";

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
            echo "✅ Webhook está ATIVO<br>";
            echo "<pre style='background: #e8f5e9; padding: 10px; border: 1px solid #4caf50;'>";
            echo "URL: " . $data['result']['url'] . "\n";
            echo "Mensagens Pendentes: " . $data['result']['pending_update_count'] . "\n";
            echo "IP: " . (isset($data['result']['ip_address']) ? $data['result']['ip_address'] : 'N/A') . "\n";
            
            if ($data['result']['last_error_date']) {
                echo "\n⚠️ ÚLTIMO ERRO:\n";
                echo "Data: " . date('d/m/Y H:i:s', $data['result']['last_error_date']) . "\n";
                echo "Mensagem: " . $data['result']['last_error_message'] . "\n";
            }
            echo "</pre>";
        } else {
            echo "❌ Webhook NÃO CONFIGURADO<br>";
        }
    } else {
        echo "❌ Erro ao conectar com Telegram API: " . $curlError . "<br>";
    }
} else {
    echo "❌ TELEGRAM_BOT_TOKEN não definido<br>";
}

echo "<hr>";
echo "<p style='color: #666;'><small>Atualizado em: " . date('d/m/Y H:i:s') . "</small></p>";
echo "<p><a href='javascript:location.reload()'>🔄 Recarregar</a></p>";
?>
