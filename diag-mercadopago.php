<?php
/**
 * DIAGNOSTICO SIMPLIFICADO - Mercado Pago
 */

// Configurações (copiadas do config_mercadopago.php)
define('MP_ACCESS_TOKEN', 'APP_USR-3237573864728549-102019-04e2fd4b60492785833312c31e0dffd8-1565964651');
define('MP_PUBLIC_KEY', 'APP_USR-ca9ca659-4278-49a6-a7cc-bed2041ac437');
define('MP_ENVIRONMENT', 'development');

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico Mercado Pago</title>
    <style>
        * { margin: 0; padding: 0; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { 
            max-width: 1000px; 
            margin: 0 auto; 
        }
        h1 { 
            color: white; 
            margin-bottom: 30px;
            text-align: center;
            font-size: 2.5em;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        .card { 
            background: white; 
            padding: 25px; 
            margin: 20px 0; 
            border-radius: 10px; 
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
            border-left: 5px solid #ddd;
        }
        .card.success { border-left-color: #27ae60; background: linear-gradient(to right, #d5f4e6 0%, white 50%); }
        .card.error { border-left-color: #e74c3c; background: linear-gradient(to right, #fadbd8 0%, white 50%); }
        .card.warning { border-left-color: #f39c12; background: linear-gradient(to right, #fde8d8 0%, white 50%); }
        
        h2 { 
            color: #333; 
            margin: 15px 0;
            font-size: 1.5em;
        }
        
        .status { 
            padding: 10px 15px; 
            border-radius: 5px; 
            margin: 10px 0;
            font-weight: bold;
        }
        
        .status-ok { 
            background: #27ae60; 
            color: white; 
        }
        
        .status-bad { 
            background: #e74c3c; 
            color: white; 
        }
        
        .status-warning { 
            background: #f39c12; 
            color: white; 
        }
        
        code { 
            background: #f4f4f4; 
            padding: 5px 10px; 
            border-radius: 3px; 
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
        }
        
        pre { 
            background: #f4f4f4; 
            padding: 15px; 
            border-radius: 5px; 
            overflow-x: auto;
            border-left: 4px solid #667eea;
            margin: 10px 0;
        }
        
        .http-code {
            font-size: 1.5em;
            font-weight: bold;
            padding: 10px;
            border-radius: 5px;
            display: inline-block;
            margin: 10px 0;
        }
        
        .http-200 { background: #27ae60; color: white; }
        .http-400 { background: #e74c3c; color: white; }
        .http-401 { background: #e67e22; color: white; }
        
        .info { margin: 10px 0; line-height: 1.6; }
        .info strong { color: #667eea; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Diagnóstico - Mercado Pago</h1>
        
        <!-- STEP 1 -->
        <div class="card success">
            <h2>1️⃣ Credenciais Carregadas</h2>
            <div class="info">
                <p><strong>Access Token:</strong> <code><?php echo substr(MP_ACCESS_TOKEN, 0, 40); ?>...</code></p>
                <p><strong>Public Key:</strong> <code><?php echo substr(MP_PUBLIC_KEY, 0, 40); ?>...</code></p>
                <p><strong>Ambiente:</strong> <code><?php echo MP_ENVIRONMENT; ?></code></p>
            </div>
        </div>
        
        <!-- STEP 2: Testar Conexão -->
        <div class="card">
            <h2>2️⃣ Teste de Conexão com API</h2>
            <p style="margin-bottom: 15px;">Verificando se o Access Token é válido...</p>
            
            <?php
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
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);
            
            echo '<div class="http-code http-' . $http_code . '">HTTP ' . $http_code . '</div>';
            
            if ($curl_error) {
                echo '<div class="status status-bad">❌ Erro de Conexão: ' . htmlspecialchars($curl_error) . '</div>';
            } elseif ($http_code == 200) {
                echo '<div class="status status-ok">✅ CREDENCIAIS VÁLIDAS!</div>';
                $data = json_decode($response, true);
                echo '<p style="margin-top: 10px;"><strong>Account ID:</strong> ' . ($data['id'] ?? 'N/A') . '</p>';
            } elseif ($http_code == 401) {
                echo '<div class="status status-bad">❌ CREDENCIAIS INVÁLIDAS (401)</div>';
                echo '<p style="color: #e74c3c; margin-top: 10px;"><strong>⚠️ O Access Token está expirado ou incorreto!</strong></p>';
                echo '<p>Atualize em: <code>config_mercadopago.php</code> (linha 6)</p>';
            } else {
                echo '<div class="status status-warning">⚠️ Erro HTTP ' . $http_code . '</div>';
                $data = json_decode($response, true);
                if ($data) {
                    echo '<pre>' . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
                } else {
                    echo '<pre>' . htmlspecialchars($response) . '</pre>';
                }
            }
            ?>
        </div>
        
        <!-- STEP 3: Testar Preferência -->
        <div class="card">
            <h2>3️⃣ Teste de Criação de Preferência</h2>
            <p style="margin-bottom: 15px;">Simulando criação de uma preferência...</p>
            
            <?php
            $preference_data = [
                "items" => [
                    [
                        "title" => "Plano OURO - Teste",
                        "description" => "Teste de integração",
                        "quantity" => 1,
                        "unit_price" => 39.90,
                        "currency_id" => "BRL"
                    ]
                ],
                "payer" => [
                    "name" => "Teste",
                    "email" => "teste@example.com"
                ],
                "back_urls" => [
                    "success" => "http://localhost/gestao_banca/webhook.php",
                    "failure" => "http://localhost/gestao_banca/webhook.php"
                ]
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
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);
            
            echo '<div class="http-code http-' . $http_code . '">HTTP ' . $http_code . '</div>';
            
            if ($curl_error) {
                echo '<div class="status status-bad">❌ Erro: ' . htmlspecialchars($curl_error) . '</div>';
            } elseif ($http_code >= 200 && $http_code < 300) {
                echo '<div class="status status-ok">✅ PREFERÊNCIA CRIADA!</div>';
                $data = json_decode($response, true);
                echo '<p><strong>ID:</strong> ' . ($data['id'] ?? 'N/A') . '</p>';
                if (isset($data['init_point'])) {
                    echo '<p><strong>Checkout URL:</strong> <code>' . substr($data['init_point'], 0, 80) . '...</code></p>';
                }
            } elseif ($http_code == 400) {
                echo '<div class="status status-bad">❌ ERRO 400 - Requisição Inválida</div>';
                $data = json_decode($response, true);
                echo '<pre>' . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
            } else {
                echo '<div class="status status-warning">⚠️ Erro HTTP ' . $http_code . '</div>';
                $data = json_decode($response, true);
                if ($data) {
                    echo '<pre>' . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
                }
            }
            ?>
        </div>
        
        <!-- CONCLUSÃO -->
        <div class="card warning">
            <h2>📋 Resumo</h2>
            <ul style="margin-left: 20px; line-height: 1.8;">
                <li>Se o <strong>Passo 2</strong> retornar <span style="color: #27ae60;">✅ HTTP 200</span>, suas credenciais estão <strong>VÁLIDAS</strong></li>
                <li>Se retornar <span style="color: #e74c3c;">❌ HTTP 401</span>, atualize o Access Token</li>
                <li>Se o <strong>Passo 3</strong> retornar <span style="color: #27ae60;">✅ HTTP 201</span>, está tudo funcionando!</li>
                <li>Se retornar <span style="color: #e74c3c;">❌ HTTP 400</span>, há um problema com os dados</li>
            </ul>
        </div>
    </div>
</body>
</html>
