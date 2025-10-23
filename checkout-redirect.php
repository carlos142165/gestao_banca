<?php
/**
 * CHECKOUT REDIRECT
 * =================
 * Redireciona para Mercado Pago usando formulário POST
 */

session_start();

if (empty($_SESSION['checkout_data'])) {
    die('Erro: Dados de pagamento não encontrados. Tente novamente.');
}

$data = $_SESSION['checkout_data'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processando Pagamento - Mercado Pago</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .container {
            background: white;
            padding: 50px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            text-align: center;
            max-width: 500px;
        }
        .spinner {
            border: 5px solid #f3f3f3;
            border-top: 5px solid #667eea;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 30px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        h1 { color: #333; font-size: 24px; margin-bottom: 10px; }
        p { color: #666; font-size: 16px; margin: 15px 0; }
        .info {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: left;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        .info-row:last-child { border-bottom: none; }
        .label { font-weight: bold; color: #333; }
        .value { color: #666; }
        .value.price { color: #28a745; font-weight: bold; font-size: 18px; }
        a { color: #667eea; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .error { color: #d32f2f; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>💳 Processando Pagamento</h1>
        <div class="spinner"></div>
        <p>Você será redirecionado para o <strong>Mercado Pago</strong> em instantes...</p>
        
        <div class="info">
            <div class="info-row">
                <span class="label">Produto:</span>
                <span class="value"><?php echo htmlspecialchars($data['title']); ?></span>
            </div>
            <div class="info-row">
                <span class="label">Valor:</span>
                <span class="value price">R$ <?php echo number_format($data['unit_price'], 2, ',', '.'); ?></span>
            </div>
            <div class="info-row">
                <span class="label">Quantidade:</span>
                <span class="value"><?php echo $data['quantity']; ?></span>
            </div>
        </div>
        
        <p><small>Se não for redirecionado automaticamente, <a href="javascript:submitForm()">clique aqui</a></small></p>
        <p class="error" id="error" style="display:none;"></p>
    </div>

    <!-- Formulário invisível para redirecionar -->
    <form id="paymentForm" method="POST" action="https://www.mercadopago.com.br/checkout/v1/redirect" style="display:none;">
        <input type="hidden" name="pref_id" value="">
    </form>

    <script>
        // Dados do pagamento
        const data = <?php echo json_encode($data); ?>;
        
        console.log('📋 Dados:', data);
        
        function submitForm() {
            try {
                // Usar método simples: redirecionar direto com parâmetros básicos
                // Formato: https://www.mercadopago.com.br/checkout/v1/redirect?back_url=...&title=...&price=...
                
                const baseUrl = 'https://www.mercadopago.com.br/checkout/v1/redirect';
                
                // Construir URL com params simples
                const params = {
                    'title': data.title,
                    'price': data.unit_price,
                    'quantity': data.quantity,
                    'currency_id': 'BRL',
                    'payer_name': data.payer_name,
                    'payer_email': data.payer_email,
                    'external_reference': data.external_reference,
                    'back_url': data.back_url
                };
                
                const queryString = Object.keys(params)
                    .map(k => encodeURIComponent(k) + '=' + encodeURIComponent(params[k]))
                    .join('&');
                
                const fullUrl = baseUrl + '?' + queryString;
                
                console.log('🔀 URL Final (length: ' + fullUrl.length + '):', fullUrl.substring(0, 100) + '...');
                
                // Redirecionar
                window.location.href = fullUrl;
                
            } catch (error) {
                console.error('❌ Erro:', error);
                document.getElementById('error').style.display = 'block';
                document.getElementById('error').textContent = 'Erro ao redirecionar: ' + error.message;
            }
        }
        
        // Redirecionar após carregar a página
        window.addEventListener('load', function() {
            console.log('⏳ Aguardando 2 segundos...');
            setTimeout(submitForm, 2000);
        });
    </script>
</body>
</html>
