<?php
header('Content-Type: application/json');
require_once 'telegram-config.php';

// Debug: Testar conexão com Telegram
$url = TELEGRAM_API_URL . '/getMe';

echo "<h2>Teste de Conexão com Telegram</h2>";
echo "<p>URL: " . $url . "</p>";

$context = stream_context_create(['http' => ['timeout' => 10]]);
$response = @file_get_contents($url, false, $context);

if ($response === false) {
    echo "<p style='color: red;'>❌ Erro: Não conseguiu conectar</p>";
} else {
    $data = json_decode($response, true);
    echo "<pre>";
    print_r($data);
    echo "</pre>";
    
    if ($data['ok']) {
        echo "<p style='color: green;'>✅ Bot conectado com sucesso!</p>";
    }
}

// Testar getUpdates
echo "<h2>Últimas Atualizações</h2>";
$url2 = TELEGRAM_API_URL . '/getUpdates';
$response2 = @file_get_contents($url2, false, $context);
$data2 = json_decode($response2, true);

echo "<pre>";
print_r($data2);
echo "</pre>";

// Mostrar informações do canal
echo "<h2>Info do Canal</h2>";
echo "Canal ID: " . TELEGRAM_CHANNEL_ID . "<br>";
echo "Procurando por mensagens do canal...<br>";

if (isset($data2['result'])) {
    $found = false;
    foreach ($data2['result'] as $update) {
        if (isset($update['channel_post'])) {
            $msg = $update['channel_post'];
            if (isset($msg['chat']['id']) && intval($msg['chat']['id']) == intval(TELEGRAM_CHANNEL_ID)) {
                echo "✅ Encontrada mensagem do canal!<br>";
                echo "<pre>";
                print_r($msg);
                echo "</pre>";
                $found = true;
            }
        }
    }
    if (!$found) {
        echo "⚠️ Nenhuma mensagem encontrada do canal neste ID<br>";
        echo "Updates encontrados:<br>";
        foreach ($data2['result'] as $update) {
            echo "Update ID: " . $update['update_id'];
            if (isset($update['message'])) {
                echo " - Tipo: message (Chat ID: " . $update['message']['chat']['id'] . ")";
            }
            if (isset($update['channel_post'])) {
                echo " - Tipo: channel_post (Chat ID: " . $update['channel_post']['chat']['id'] . ")";
            }
            echo "<br>";
        }
    }
}
?>
