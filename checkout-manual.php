<?php
/**
 * CHECKOUT MANUAL
 * ===============
 * Fallback se a API não funcionar
 * Redireciona com formulário HTML POST
 */

session_start();

if (!isset($_SESSION['pagamento_pendente'])) {
    die('Erro: Dados de pagamento não encontrados');
}

$dados = $_SESSION['pagamento_pendente'];

// Construir URL com parâmetros para Mercado Pago
$params = array(
    'title' => $dados['items'][0]['title'],
    'quantity' => $dados['items'][0]['quantity'],
    'unit_price' => $dados['items'][0]['unit_price'],
    'currency_id' => $dados['items'][0]['currency_id'],
    'payer_name' => $dados['payer']['name'],
    'payer_email' => $dados['payer']['email'],
    'external_reference' => $dados['external_reference'],
    'success_url' => $dados['back_urls']['success'],
    'failure_url' => $dados['back_urls']['failure'],
    'pending_url' => $dados['back_urls']['pending'],
    'back_url' => $dados['back_urls']['success']
);

// URL para formulário
$checkout_url = 'https://www.mercadopago.com.br/checkout/v1/redirect?back_url=' . urlencode($params['success_url']);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Redirecionando para Pagamento...</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .container {
            text-align: center;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        h1 { color: #333; margin: 0; }
        p { color: #666; margin: 10px 0; }
        .error { color: red; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Processando Pagamento</h1>
        <div class="spinner"></div>
        <p>Você será redirecionado para o Mercado Pago em instantes...</p>
        <p><small>Se não for redirecionado automaticamente, <a href="javascript:location.reload()">clique aqui</a></small></p>
    </div>

    <script>
        // Redirecionar para Mercado Pago após 1 segundo
        setTimeout(function() {
            // Método 1: Tentar usar formulário com dados
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = 'https://www.mercadopago.com.br/checkout/v1/redirect';
            
            var params = <?php echo json_encode($params); ?>;
            
            for (var key in params) {
                if (params.hasOwnProperty(key)) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = params[key];
                    form.appendChild(input);
                }
            }
            
            document.body.appendChild(form);
            form.submit();
        }, 1000);
    </script>
</body>
</html>
