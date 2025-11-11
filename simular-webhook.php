<?php
/**
 * Simular um POST do Telegram para testar o webhook
 */

require_once 'telegram-config.php';
require_once 'config.php';

// Simular dados de mensagem com +0.5
$telegramData = array(
    "update_id" => 213068990,
    "channel_post" => array(
        "message_id" => 9000,
        "sender_chat" => array(
            "id" => -1002047004959,
            "title" => "Bateubet_VIP",
            "type" => "channel"
        ),
        "chat" => array(
            "id" => -1002047004959,
            "title" => "Bateubet_VIP",
            "type" => "channel"
        ),
        "date" => 1762822500,
        "text" => "Oportunidade! 🚨

📊 🚨 OVER ( +0.5 ⚽️GOL  ) FT

⚽️ Roma (H) x Udinese (A) (ao vivo)

⏰ Tempo: 60'
Odds iniciais: Casa: 1.5 - Emp. 4.1 - Fora: 6.5
🏳️ Italy Serie A

🦅 Placar: 1 - 0  
↩️ Último gol: 42' - ø
Gols over +0.5: 1.57 
Stake: 1%

Links da partida:

Bet365 | Betfair"
    )
);

echo "=== SIMULANDO WEBHOOK DO TELEGRAM ===\n\n";
echo "Dados que serão enviados:\n";
echo json_encode($telegramData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Simular o POST
echo "Enviando POST para o webhook...\n\n";

$ch = curl_init('http://localhost/gestao/gestao_banca/api/telegram-webhook.php');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($telegramData));
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: {$httpCode}\n";
echo "Response: {$response}\n\n";

// Aguardar um pouco para o arquivo de log ser escrito
sleep(1);

// Ler o log
echo "=== VERIFICANDO LOG ===\n\n";
$logFile = 'logs/telegram-webhook.log';
if (file_exists($logFile)) {
    $logContent = file_get_contents($logFile);
    echo $logContent;
} else {
    echo "❌ Arquivo de log não encontrado!\n";
}

?>
