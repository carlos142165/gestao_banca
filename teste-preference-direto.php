<?php
/**
 * TESTE - Enviar preference direto
 */

$token = 'APP_USR-3237573864728549-102019-04e2fd4b60492785833312c31e0dffd8-1565964651';

$preference = [
    "items" => [
        [
            "title" => "Plano Teste",
            "description" => "Teste",
            "quantity" => 1,
            "unit_price" => 39.90,
            "currency_id" => "BRL"
        ]
    ],
    "payer" => [
        "name" => "Cliente",
        "email" => "test@test.com"
    ]
];

$url = "https://api.mercadopago.com/v1/preferences";

$headers = [
    "Content-Type: application/json",
    "Authorization: Bearer $token"
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($preference));

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Teste Preference</title>
    <style>
        body { font-family: Arial; background: #f5f5f5; padding: 20px; }
        .box { background: white; padding: 20px; border-radius: 8px; margin: 10px 0; }
        pre { background: #f4f4f4; padding: 15px; overflow-x: auto; border-radius: 4px; }
        .success { border-left: 4px solid green; }
        .error { border-left: 4px solid red; }
    </style>
</head>
<body>
    <h1>Teste de Preference API</h1>
    
    <div class="box <?php echo ($http_code == 201) ? 'success' : 'error'; ?>">
        <h2>HTTP Code: <?php echo $http_code; ?></h2>
    </div>
    
    <div class="box">
        <h3>Resposta Completa:</h3>
        <pre><?php echo htmlspecialchars($response, ENT_QUOTES, 'UTF-8'); ?></pre>
    </div>
    
    <div class="box">
        <h3>JSON Decodificado:</h3>
        <pre><?php echo json_encode(json_decode($response, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?></pre>
    </div>
    
    <?php if ($http_code == 201 || $http_code == 200): ?>
        <div class="box success">
            <h2>✅ Sucesso!</h2>
            <?php $data = json_decode($response, true); ?>
            <p><strong>ID:</strong> <?php echo $data['id'] ?? 'N/A'; ?></p>
            <p><strong>Init Point:</strong> <?php echo $data['init_point'] ?? 'N/A'; ?></p>
            <?php if (!empty($data['init_point'])): ?>
                <p><a href="<?php echo $data['init_point']; ?>" target="_blank" style="padding: 10px 20px; background: green; color: white; text-decoration: none; border-radius: 5px;">Ir para Checkout</a></p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="box error">
            <h2>❌ Erro</h2>
            <p>Verifique a resposta acima</p>
        </div>
    <?php endif; ?>
</body>
</html>
