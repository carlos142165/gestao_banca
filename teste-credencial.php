<?php
$token = 'APP_USR-3237573864728549-102019-04e2fd4b60492785833312c31e0dffd8-1565964651';

echo "<h1>🔍 Teste de Credencial Mercado Pago</h1>";

// Testar vários endpoints
$endpoints = [
    'https://api.mercadopago.com/v1/accounts' => 'Accounts',
    'https://api.mercadopago.com/v1/checkout/preferences' => 'Preferences (GET)',
    'https://api.mercadopago.com/v1/me' => 'Me (User Info)',
];

foreach ($endpoints as $url => $name) {
    echo "<h2>Testando: $name</h2>";
    echo "<p><code>$url</code></p>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer $token"));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "<p><strong>HTTP Code:</strong> <span style='font-size:18px;'>";
    
    if ($http_code == 200) {
        echo "<span style='color:green'>✅ 200 OK</span>";
    } elseif ($http_code == 401) {
        echo "<span style='color:red'>❌ 401 UNAUTHORIZED (Token Expirado)</span>";
    } elseif ($http_code == 404) {
        echo "<span style='color:orange'>⚠️ 404 NOT FOUND</span>";
    } else {
        echo "<span style='color:red'>❌ $http_code ERROR</span>";
    }
    
    echo "</span></p>";
    
    if ($error) {
        echo "<p><strong style='color:red'>Erro de Conexão:</strong> $error</p>";
    } else {
        echo "<p><strong>Resposta:</strong></p>";
        echo "<pre style='background:#f5f5f5; padding:10px; overflow-x:auto;'>" . htmlspecialchars($response) . "</pre>";
    }
    
    echo "<hr>";
}
?>

