<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔧 Teste de Configuração - Ambiente</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1000px; margin: 0 auto; }
        .box {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .box h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        .item {
            background: #f5f7fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
        .label { font-weight: bold; color: #333; }
        .value { color: #666; }
        .ok { color: #27ae60; font-weight: bold; }
        .error { color: #e74c3c; font-weight: bold; }
        .warning { color: #f39c12; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="box">
            <h2>🔧 Teste de Configuração - Ambiente</h2>
        </div>

        <?php
        require_once 'config.php';

        echo '<div class="box">';
        echo '<h2>📊 Variáveis do Servidor</h2>';

        $httpHost = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'N/A';
        $serverName = $_SERVER['SERVER_NAME'] ?? 'N/A';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? 'N/A';
        $requestUri = $_SERVER['REQUEST_URI'] ?? 'N/A';

        echo '<div class="item">';
        echo '<span class="label">HTTP_HOST:</span> <span class="value">' . $httpHost . '</span>';
        echo '</div>';

        echo '<div class="item">';
        echo '<span class="label">SERVER_NAME:</span> <span class="value">' . $serverName . '</span>';
        echo '</div>';

        echo '<div class="item">';
        echo '<span class="label">SCRIPT_NAME:</span> <span class="value">' . $scriptName . '</span>';
        echo '</div>';

        echo '<div class="item">';
        echo '<span class="label">REQUEST_URI:</span> <span class="value">' . $requestUri . '</span>';
        echo '</div>';

        echo '</div>';

        echo '<div class="box">';
        echo '<h2>⚙️ Configuração Detectada</h2>';

        $isLocalhost = (
            $httpHost === 'localhost' ||
            $httpHost === '127.0.0.1' ||
            strpos($httpHost, 'localhost:') === 0 ||
            $serverName === 'localhost' ||
            php_uname('n') === 'localhost'
        );

        $isProduction = !$isLocalhost;

        echo '<div class="item">';
        echo '<span class="label">É Localhost?</span> ';
        echo $isLocalhost ? '<span class="ok">✅ SIM</span>' : '<span class="error">❌ NÃO</span>';
        echo '</div>';

        echo '<div class="item">';
        echo '<span class="label">É Produção?</span> ';
        echo $isProduction ? '<span class="ok">✅ SIM</span>' : '<span class="error">❌ NÃO</span>';
        echo '</div>';

        echo '<div class="item">';
        echo '<span class="label">Ambiente Detectado:</span> ';
        echo '<span class="' . (defined('ENVIRONMENT') ? 'ok' : 'error') . '">' . (defined('ENVIRONMENT') ? ENVIRONMENT : 'NÃO DEFINIDO') . '</span>';
        echo '</div>';

        echo '</div>';

        echo '<div class="box">';
        echo '<h2>🗄️ Configuração do Banco</h2>';

        echo '<div class="item">';
        echo '<span class="label">DB_HOST:</span> <span class="value">' . DB_HOST . '</span>';
        echo '</div>';

        echo '<div class="item">';
        echo '<span class="label">DB_NAME:</span> <span class="value">' . DB_NAME . '</span>';
        echo '</div>';

        echo '<div class="item">';
        echo '<span class="label">DB_USERNAME:</span> <span class="value">' . DB_USERNAME . '</span>';
        echo '</div>';

        echo '<div class="item">';
        echo '<span class="label">DB_PASSWORD:</span> <span class="value">' . (substr(DB_PASSWORD, 0, 2) . '***' . substr(DB_PASSWORD, -2)) . '</span>';
        echo '</div>';

        // Testar conexão
        echo '<div class="item" style="margin-top: 20px;">';
        $test_conexao = @new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
        if (!$test_conexao->connect_error) {
            echo '<span class="ok">✅ Conexão com banco: OK</span>';
            $test_conexao->close();
        } else {
            echo '<span class="error">❌ Erro: ' . $test_conexao->connect_error . '</span>';
        }
        echo '</div>';

        echo '</div>';

        echo '<div class="box">';
        echo '<h2>✅ Checklist Final</h2>';

        $checks = [
            'Ambiente detectado corretamente' => defined('ENVIRONMENT') && ENVIRONMENT !== 'unknown',
            'DB_HOST configurado' => DB_HOST !== null,
            'DB_NAME configurado' => DB_NAME !== null,
            'DB_USERNAME configurado' => DB_USERNAME !== null,
            'DB_PASSWORD configurado' => DB_PASSWORD !== null,
            'Conexão com banco funciona' => !$test_conexao->connect_error
        ];

        foreach ($checks as $check => $result) {
            echo '<div class="item">';
            echo ($result ? '<span class="ok">✅</span>' : '<span class="error">❌</span>') . ' ' . $check;
            echo '</div>';
        }

        echo '</div>';

        echo '<div class="box">';
        echo '<h2>📝 Próximas Ações</h2>';
        echo '<div class="item">';
        if (defined('ENVIRONMENT') && ENVIRONMENT !== 'unknown') {
            echo '<span class="ok">✅ Configuração OK!</span><br>';
            echo 'Acesse: <a href="diagnostico-mensagens.php" style="color: #667eea;">diagnostico-mensagens.php</a>';
        } else {
            echo '<span class="warning">⚠️ Verifique a configuração acima</span>';
        }
        echo '</div>';
        echo '</div>';
        ?>
    </div>
</body>
</html>
