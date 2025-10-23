<?php
/**
 * DIAGNOSTICO COMPLETO - Integração Mercado Pago
 * ================================================
 */
require_once 'config_mercadopago.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Diagnóstico Mercado Pago</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; }
        .card { background: white; padding: 20px; margin: 15px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { border-left: 5px solid #27ae60; background: #d5f4e6; }
        .error { border-left: 5px solid #e74c3c; background: #fadbd8; }
        .warning { border-left: 5px solid #f39c12; background: #fde8d8; }
        h1 { color: #333; }
        h2 { color: #555; margin-top: 20px; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: 'Courier New'; }
        .value { font-weight: bold; color: #e74c3c; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 4px; overflow-x: auto; }
        button { padding: 10px 20px; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #2980b9; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔍 Diagnóstico - Mercado Pago</h1>
";

// ========== STEP 1: Verificar Credenciais ==========
echo "<div class='card";
if (!empty(MP_ACCESS_TOKEN) && !empty(MP_PUBLIC_KEY)) {
    echo " success";
} else {
    echo " error";
}
echo "'>
    <h2>1️⃣ Credenciais Configuradas</h2>
    <p><strong>Access Token:</strong> <code>" . substr(MP_ACCESS_TOKEN, 0, 30) . "...</code></p>
    <p><strong>Public Key:</strong> <code>" . substr(MP_PUBLIC_KEY, 0, 30) . "...</code></p>
    <p><strong>Ambiente:</strong> <code>" . MP_ENVIRONMENT . "</code></p>
";

if (empty(MP_ACCESS_TOKEN) || empty(MP_PUBLIC_KEY)) {
    echo "<p style='color: #e74c3c;'>❌ CREDENCIAIS INCOMPLETAS!</p>";
} else {
    echo "<p style='color: #27ae60;'>✅ Credenciais encontradas</p>";
}

echo "</div>";

// ========== STEP 2: Testar Conexão com API ==========
echo "<div class='card'>
    <h2>2️⃣ Teste de Conexão com API</h2>
    <p>Verificando se as credenciais são válidas...</p>
";

$url = "https://api.mercadopago.com/v1/accounts";
$headers = [
    "Authorization: Bearer " . MP_ACCESS_TOKEN,
    "Content-Type: application/json"
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($curl_error) {
    echo "<div class='error'>";
    echo "<p>❌ Erro cURL: <code>$curl_error</code></p>";
    echo "</div>";
} else {
    echo "<p><strong>HTTP Code:</strong> <span class='value'>$http_code</span></p>";
    
    if ($http_code == 200) {
        echo "<div class='success'>";
        echo "<p>✅ CREDENCIAIS VÁLIDAS!</p>";
        $data = json_decode($response, true);
        echo "<p><strong>Account ID:</strong> " . ($data['id'] ?? 'N/A') . "</p>";
        echo "</div>";
    } elseif ($http_code == 401) {
        echo "<div class='error'>";
        echo "<p>❌ CREDENCIAIS INVÁLIDAS (401 Unauthorized)</p>";
        echo "<p>Verifique se o Access Token está correto no Mercado Pago Dashboard</p>";
        echo "</div>";
    } else {
        echo "<div class='warning'>";
        echo "<p>⚠️ HTTP Code: $http_code</p>";
        echo "<p><strong>Resposta:</strong></p>";
        echo "<pre>" . json_encode(json_decode($response, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
        echo "</div>";
    }
}

echo "</div>";

// ========== STEP 3: Testar Criação de Preferência ==========
echo "<div class='card'>
    <h2>3️⃣ Teste de Criação de Preferência</h2>
    <p>Simulando criação de preferência para pagamento...</p>
";

$preference_data = [
    "items" => [
        [
            "title" => "Plano OURO - Mensal",
            "description" => "Teste de preferência",
            "quantity" => 1,
            "unit_price" => 39.90,
            "currency_id" => "BRL"
        ]
    ],
    "payer" => [
        "name" => "Cliente Teste",
        "email" => "teste@example.com"
    ],
    "back_urls" => [
        "success" => "http://localhost/gestao_banca/webhook.php?status=success",
        "failure" => "http://localhost/gestao_banca/webhook.php?status=failure",
        "pending" => "http://localhost/gestao_banca/webhook.php?status=pending"
    ],
    "auto_return" => "approved",
    "notification_url" => "http://localhost/gestao_banca/webhook.php"
];

$url = "https://api.mercadopago.com/checkout/preferences";
$headers = [
    "Authorization: Bearer " . MP_ACCESS_TOKEN,
    "Content-Type: application/json",
    "X-Idempotency-Key: " . uniqid()
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($preference_data));
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> <span class='value'>$http_code</span></p>";

if ($curl_error) {
    echo "<div class='error'>";
    echo "<p>❌ Erro cURL: <code>$curl_error</code></p>";
    echo "</div>";
} else {
    if ($http_code >= 200 && $http_code < 300) {
        echo "<div class='success'>";
        echo "<p>✅ PREFERÊNCIA CRIADA COM SUCESSO!</p>";
        
        $data = json_decode($response, true);
        echo "<p><strong>Preference ID:</strong> " . ($data['id'] ?? 'N/A') . "</p>";
        echo "<p><strong>Init Point:</strong> " . ($data['init_point'] ?? 'N/A') . "</p>";
        echo "</div>";
    } elseif ($http_code == 400) {
        echo "<div class='error'>";
        echo "<p>❌ ERRO 400 - Requisição Inválida</p>";
        
        $data = json_decode($response, true);
        echo "<p><strong>Erro:</strong> " . ($data['message'] ?? 'Desconhecido') . "</p>";
        
        if (isset($data['errors'])) {
            echo "<p><strong>Detalhes:</strong></p>";
            echo "<pre>" . json_encode($data['errors'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
        }
        
        echo "</div>";
    } else {
        echo "<div class='warning'>";
        echo "<p><strong>Resposta:</strong></p>";
        echo "<pre>" . json_encode(json_decode($response, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
        echo "</div>";
    }
}

echo "</div>";

// ========== STEP 4: Recomendações ==========
echo "<div class='card warning'>
    <h2>📋 Próximas Etapas</h2>
    <p>Se todos os testes acima passaram com ✅, o sistema está funcionando!</p>
    <p>Se algum teste falhou:</p>
    <ol>
        <li>Se HTTP 401 no passo 2: Atualize as credenciais em <code>config_mercadopago.php</code></li>
        <li>Se HTTP 400 no passo 3: Verifique se os dados do plano estão corretos</li>
        <li>Se erro de conexão: Verifique sua conexão com a internet</li>
    </ol>
</div>";

echo "
    </div>
</body>
</html>
";

?>
