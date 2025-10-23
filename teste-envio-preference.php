<?php
/**
 * TESTE DE ENVIO DE PREFERENCE
 * ============================
 * Simula exatamente o que o sistema tenta fazer
 */

$token = 'APP_USR-3237573864728549-102019-04e2fd4b60492785833312c31e0dffd8-1565964651';

echo "<h1>📝 Teste de Envio de Preference</h1>";

// Dados que tentaremos enviar
$preference_data = [
    "items" => [
        [
            "title" => "Plano OURO - mensal",
            "description" => "Acesso ao plano OURO por 1 mês",
            "quantity" => 1,
            "unit_price" => 39.90,
            "currency_id" => "BRL"
        ]
    ],
    "payer" => [
        "name" => "Cliente Teste",
        "email" => "teste@test.com"
    ],
    "payment_methods" => [
        "excluded_payment_types" => [],
        "excluded_payment_methods" => [],
        "installments" => 1
    ],
    "back_urls" => [
        "success" => "http://localhost/gestao/gestao_banca/webhook.php?status=success",
        "failure" => "http://localhost/gestao/gestao_banca/webhook.php?status=failure",
        "pending" => "http://localhost/gestao/gestao_banca/webhook.php?status=pending"
    ],
    "notification_url" => "http://localhost/gestao/gestao_banca/webhook.php",
    "external_reference" => "user_1_plan_3_mensal",
    "expires" => true,
    "expiration_date_from" => date('Y-m-d\TH:i:s\Z'),
    "expiration_date_to" => date('Y-m-d\TH:i:s\Z', strtotime('+24 hours')),
    "metadata" => [
        "id_usuario" => 1,
        "id_plano" => 3,
        "tipo_ciclo" => "mensal",
        "modo_pagamento" => null
    ]
];

echo "<h2>📤 Dados que serão enviados:</h2>";
echo "<pre style='background:#f5f5f5; padding:15px; overflow-x:auto; border-radius:5px;'>";
echo json_encode($preference_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
echo "</pre>";

echo "<h2>🌐 Enviando para Mercado Pago...</h2>";

$url = "https://api.mercadopago.com/v1/checkout/preferences";
$headers = [
    "Authorization: Bearer " . $token,
    "Content-Type: application/json",
    "X-Idempotency-Key: " . uniqid()
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($preference_data));

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

echo "<h2>📊 Resultado:</h2>";
echo "<p><strong>Status HTTP:</strong> <span style='font-size:18px;font-weight:bold;color:";

if ($http_code == 201) {
    echo "green'>✅ 201 CREATED</span></p>";
    $data = json_decode($response, true);
    echo "<p><strong style='color:green'>Preference ID:</strong> " . ($data['id'] ?? 'N/A') . "</p>";
    echo "<p><strong style='color:green'>Init Point:</strong> " . ($data['init_point'] ?? 'N/A') . "</p>";
    
    echo "<p style='margin-top:20px;'><a href='" . ($data['init_point'] ?? '#') . "' target='_blank' style='padding:10px 20px; background:green; color:white; text-decoration:none; border-radius:5px;'>💳 Ir para Pagamento</a></p>";
} else if ($http_code == 400) {
    echo "red'>❌ 400 BAD REQUEST</span></p>";
} else if ($http_code == 401) {
    echo "red'>❌ 401 UNAUTHORIZED</span></p>";
} else {
    echo "orange'>⚠️ $http_code</span></p>";
}

if ($curl_error) {
    echo "<p><strong style='color:red'>Erro de Conexão:</strong> $curl_error</p>";
}

echo "<h2>📥 Resposta Completa:</h2>";
echo "<pre style='background:#f5f5f5; padding:15px; overflow-x:auto; border-radius:5px; max-height:500px;'>";
echo htmlspecialchars($response);
echo "</pre>";

echo "<hr>";
echo "<p style='color:#666; font-size:12px;'>Se recebeu 400, verifique se há campos obrigatórios faltando ou valores inválidos.</p>";
?>
