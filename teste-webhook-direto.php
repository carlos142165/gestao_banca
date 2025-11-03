<?php
/**
 * TESTE WEBHOOK - VIA ARQUIVO DIRETO
 */

date_default_timezone_set('America/Sao_Paulo');

echo "=== TESTANDO WEBHOOK TELEGRAM ===\n\n";

// Simulando php://input
$testData = [
    "update_id" => 123456789,
    "channel_post" => [
        "message_id" => 999,
        "date" => time(),
        "chat" => [
            "id" => -1002047004959,
            "title" => "Canal de Testes",
            "type" => "channel"
        ],
        "text" => "Oportunidade! 🚨\n📊 OVER ( +2.5 ⚽GOLS )\nFlamengo (H) x Botafogo (A)\nPlacar: 1 - 0\n⛳ Escanteios: 5 - 3\nGols over +2.5 : 1.75"
    ]
];

// Simular o $_SERVER para o webhook
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Criar stream com os dados
$inputData = json_encode($testData);

// Mock php://input
require_once __DIR__ . '/telegram-config.php';
require_once __DIR__ . '/config.php';

// ✅ LOG DE REQUISIÇÕES
$logFile = __DIR__ . '/logs/telegram-webhook.log';
if (!is_dir(dirname($logFile))) {
    mkdir(dirname($logFile), 0755, true);
}

// Processar dados como se viessem do Telegram
$input = $testData;

// Log
$logData = "[" . date('Y-m-d H:i:s') . "] TESTE LOCAL - Webhook acionado\n";
$logData .= "Input: " . json_encode($input) . "\n";
file_put_contents($logFile, $logData, FILE_APPEND);

try {
    
    // ✅ VALIDAR SE VEM DO TELEGRAM
    if (!$input) {
        throw new Exception("Nenhum dado recebido");
    }
    
    echo "✅ Dados recebidos\n";
    
    // ✅ VERIFICAR SE É UMA MENSAGEM DO CANAL
    if (!isset($input['channel_post'])) {
        echo "⏭️ Não é channel_post\n";
        throw new Exception("Não é uma mensagem de canal");
    }
    
    echo "✅ É uma mensagem de canal\n";
    
    $message = $input['channel_post'];
    $messageText = '';
    
    // ✅ EXTRAIR TEXTO DA MENSAGEM
    if (isset($message['text']) && !empty($message['text'])) {
        $messageText = $message['text'];
    }
    
    if (empty($messageText)) {
        throw new Exception("Mensagem vazia");
    }
    
    echo "✅ Mensagem não vazia\n";
    echo "   Primeiros 50 chars: " . substr($messageText, 0, 50) . "...\n\n";
    
    // ✅ VALIDAR FORMATO
    $validFormat = "Oportunidade! 🚨";
    echo "Verificando formato...\n";
    echo "   Procurando por: '$validFormat'\n";
    echo "   Mensagem começa com: '" . substr($messageText, 0, strlen($validFormat)) . "'\n";
    
    $formatCheck = strpos($messageText, $validFormat) !== 0;
    if ($formatCheck) {
        echo "❌ Formato inválido\n";
        throw new Exception("Formato inválido");
    }
    
    echo "✅ Formato válido\n";
    
    // ✅ VERIFICAR CANAL
    $messageChannelId = intval($message['chat']['id']);
    $expectedChannelId = intval(TELEGRAM_CHANNEL_ID);
    
    echo "\nVerificando canal...\n";
    echo "   Esperado: $expectedChannelId\n";
    echo "   Recebido: $messageChannelId\n";
    
    if ($messageChannelId != $expectedChannelId) {
        echo "❌ Canal incorreto\n";
        throw new Exception("Canal incorreto");
    }
    
    echo "✅ Canal correto\n";
    
    // Agora incluir o arquivo que faz o processamento
    echo "\n=== PROCESSANDO WEBHOOK ===\n\n";
    
    // Incluir o webhook original
    ob_start();
    
    // Simular as variáveis globais que o webhook espera
    $_GET = [];
    $input = $testData;
    
    // Redirecionar a saída
    include __DIR__ . '/api/telegram-webhook.php';
    
    $output = ob_get_clean();
    
    echo "Saída do webhook:\n";
    echo $output . "\n\n";
    
    // Verificar se foi salvo
    echo "=== VERIFICANDO BANCO DE DADOS ===\n\n";
    
    $conexao = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if ($conexao->connect_error) {
        echo "❌ Erro ao conectar: " . $conexao->connect_error . "\n";
    } else {
        $result = $conexao->query("SELECT COUNT(*) as total FROM bote");
        $count = $result->fetch_assoc();
        echo "Total de mensagens: " . $count['total'] . "\n";
        
        $recent = $conexao->query("SELECT id, titulo, data_criacao FROM bote ORDER BY id DESC LIMIT 3");
        if ($recent) {
            echo "\nÚltimas mensagens:\n";
            while ($row = $recent->fetch_assoc()) {
                echo "- ID: " . $row['id'] . " | Título: " . $row['titulo'] . " | Data: " . $row['data_criacao'] . "\n";
            }
        }
        
        $conexao->close();
    }
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}

// Mostrar log
echo "\n=== LOG ATUALIZADO ===\n\n";
if (file_exists($logFile)) {
    $lines = file($logFile);
    $lastLines = array_slice($lines, -15);
    foreach ($lastLines as $line) {
        echo trim($line) . "\n";
    }
}

echo "\n=== FIM DO TESTE ===\n";
?>
