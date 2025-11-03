<?php
/**
 * TESTE LOCAL DO WEBHOOK
 * Simula o envio de uma mensagem do Telegram para testar o webhook
 */

date_default_timezone_set('America/Sao_Paulo');
header('Content-Type: application/json; charset=utf-8');

// Simular uma mensagem do Telegram
$testMessage = [
    "update_id" => 123456789,
    "channel_post" => [
        "message_id" => 999,
        "date" => time(),
        "chat" => [
            "id" => -1002047004959,  // Use o seu TELEGRAM_CHANNEL_ID
            "title" => "Canal de Testes",
            "type" => "channel"
        ],
        "text" => "Oportunidade! 🚨\n📊 OVER ( +2.5 ⚽GOLS )\nFlamengo (H) x Botafogo (A)\nPlacar: 1 - 0\n⛳ Escanteios: 5 - 3\nGols over +2.5 : 1.75"
    ]
];

echo "=== TESTANDO WEBHOOK LOCALMENTE ===\n\n";
echo "1. Simulando requisição do Telegram...\n\n";

// Converter para JSON como o Telegram faria
$json = json_encode($testMessage);
echo "JSON enviado:\n";
echo json_encode($testMessage, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Salvar como se fosse php://input
$filename = sys_get_temp_dir() . '/telegram_test_' . time() . '.json';
file_put_contents($filename, $json);

echo "2. Chamando webhook...\n\n";

// Usar cURL para chamar o webhook
$url = "http://localhost/gestao_banca/api/telegram-webhook.php";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($json)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "3. Resposta do webhook:\n";
echo "   HTTP Code: " . $httpCode . "\n";
echo "   Response: " . $response . "\n\n";

if ($curlError) {
    echo "❌ Erro cURL: " . $curlError . "\n";
}

echo "4. Verificando log...\n";
$logFile = __DIR__ . '/logs/telegram-webhook.log';
if (file_exists($logFile)) {
    echo "   Log atualizado!\n";
    echo "   Últimas linhas:\n";
    $lines = file($logFile);
    $lastLines = array_slice($lines, -20);
    foreach ($lastLines as $line) {
        echo "   " . trim($line) . "\n";
    }
} else {
    echo "   ❌ Log não foi criado\n";
}

echo "\n5. Verificando banco de dados...\n";
require_once __DIR__ . '/config.php';

$conexao = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conexao->connect_error) {
    echo "   ❌ Erro ao conectar: " . $conexao->connect_error . "\n";
} else {
    $result = $conexao->query("SELECT COUNT(*) as total FROM bote");
    $count = $result->fetch_assoc();
    echo "   Total de mensagens no banco: " . $count['total'] . "\n";
    
    // Mostrar últimas 5 mensagens
    $recent = $conexao->query("SELECT id, titulo, data_criacao FROM bote ORDER BY id DESC LIMIT 5");
    if ($recent) {
        echo "\n   Últimas mensagens:\n";
        while ($row = $recent->fetch_assoc()) {
            echo "   - ID: " . $row['id'] . " | Título: " . $row['titulo'] . " | Data: " . $row['data_criacao'] . "\n";
        }
    }
    
    $conexao->close();
}

// Limpar arquivo temporário
unlink($filename);

echo "\n=== FIM DO TESTE ===\n";
echo "\nSe a mensagem foi salva, o webhook está funcionando corretamente!\n";
echo "Se NÃO foi salva, verifique os erros acima.\n";
?>
