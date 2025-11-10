<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔍 Debug - Por que Webhook Não Recebe Mensagens</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
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
        .ok { color: #27ae60; font-weight: bold; }
        .error { color: #e74c3c; font-weight: bold; }
        .warning { color: #f39c12; font-weight: bold; }
        .code-box {
            background: #1e1e1e;
            color: #00ff00;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            overflow-x: auto;
            margin: 15px 0;
        }
        button {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            margin: 10px 10px 10px 0;
        }
        button:hover { background: #764ba2; }
        .step {
            background: #f0f8ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="box">
            <h2>🔍 Debug - Webhook Não Recebe Novas Mensagens</h2>
            <p>Diagnóstico para identificar por que o webhook não está recebendo mensagens do Telegram</p>
        </div>

        <?php
        require_once 'config.php';

        echo '<div class="box">';
        echo '<h2>🔧 Informações do Servidor</h2>';

        echo '<div class="item">';
        echo '<strong>URL do Webhook:</strong><br>';
        $protocolo = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $url_webhook = $protocolo . '://' . $_SERVER['HTTP_HOST'] . '/api/telegram-webhook.php';
        echo '<code>' . htmlspecialchars($url_webhook) . '</code>';
        echo '</div>';

        echo '<div class="item">';
        echo '<strong>Ambiente:</strong> ' . (defined('ENVIRONMENT') ? ENVIRONMENT : 'DESCONHECIDO');
        echo '</div>';

        echo '<div class="item">';
        echo '<strong>Banco de Dados:</strong> ' . DB_NAME . '@' . DB_HOST;
        echo '</div>';

        echo '</div>';

        // Verificar arquivo webhook
        echo '<div class="box">';
        echo '<h2>📄 Arquivo Webhook</h2>';

        $arquivo_webhook = __DIR__ . '/api/telegram-webhook.php';
        if (file_exists($arquivo_webhook)) {
            echo '<div class="item ok">';
            echo '✅ Arquivo existe: ' . $arquivo_webhook;
            echo '</div>';

            $tamanho = filesize($arquivo_webhook);
            echo '<div class="item">';
            echo 'Tamanho: ' . round($tamanho / 1024, 2) . ' KB';
            echo '</div>';
        } else {
            echo '<div class="item error">';
            echo '❌ Arquivo NÃO ENCONTRADO: ' . $arquivo_webhook;
            echo '</div>';
        }

        echo '</div>';

        // Verificar logs
        echo '<div class="box">';
        echo '<h2>📋 Arquivo de Log do Webhook</h2>';

        $arquivo_log = __DIR__ . '/logs/telegram-webhook.log';
        if (file_exists($arquivo_log)) {
            echo '<div class="item ok">';
            echo '✅ Arquivo de log existe';
            echo '</div>';

            $ultima_modificacao = filemtime($arquivo_log);
            $tempo_desde = time() - $ultima_modificacao;
            
            echo '<div class="item">';
            echo '<strong>Última atualização:</strong> ';
            if ($tempo_desde < 60) {
                echo '<span class="ok">Agora mesmo (' . $tempo_desde . 's atrás)</span>';
            } elseif ($tempo_desde < 3600) {
                echo '<span class="ok">Há ' . round($tempo_desde / 60) . ' minutos</span>';
            } else {
                echo '<span class="warning">Há ' . round($tempo_desde / 3600) . ' horas</span>';
            }
            echo '</div>';

            // Mostrar últimas linhas
            echo '<div class="item">';
            echo '<strong>Últimas 10 linhas do log:</strong>';
            echo '</div>';

            $linhas = array_slice(file($arquivo_log), -10);
            echo '<div class="code-box">';
            foreach ($linhas as $linha) {
                echo htmlspecialchars($linha) . "\n";
            }
            echo '</div>';
        } else {
            echo '<div class="item warning">';
            echo '⚠️ Arquivo de log ainda não foi criado';
            echo '</div>';
        }

        echo '</div>';

        // Checklist
        echo '<div class="box">';
        echo '<h2>✅ Checklist - Causas Possíveis</h2>';

        $checks = [
            'Webhook configurado no Telegram (@BotFather)' => '❓ Verificar com @BotFather',
            'URL do webhook correta' => $url_webhook,
            'Arquivo webhook.php existe' => file_exists($arquivo_webhook) ? '✅ SIM' : '❌ NÃO',
            'Pasta logs/ existe' => is_dir(__DIR__ . '/logs') ? '✅ SIM' : '❌ NÃO',
            'Token Telegram configurado' => defined('TELEGRAM_BOT_TOKEN') ? '✅ SIM' : '❌ NÃO',
            'Banco conectado' => ($conexao && !$conexao->connect_error) ? '✅ SIM' : '❌ NÃO'
        ];

        foreach ($checks as $check => $status) {
            $classe = (strpos($status, '✅') !== false || strpos($status, 'http') !== false) ? 'ok' : (strpos($status, '❌') !== false ? 'error' : 'warning');
            echo '<div class="item">';
            echo '<strong>' . $check . ':</strong> <span class="' . $classe . '">' . $status . '</span>';
            echo '</div>';
        }

        echo '</div>';

        // Instruções
        echo '<div class="box">';
        echo '<h2>📝 Como Verificar e Corrigir</h2>';

        echo '<div class="step">';
        echo '<strong>PASSO 1: Verificar Webhook no Telegram</strong><br><br>';
        echo 'Abra o Telegram e procure @BotFather<br>';
        echo 'Envie: <code>/mybots</code><br>';
        echo 'Selecione seu bot (Bateubet_VIP ou outro)<br>';
        echo 'Clique em "API Token" para pegar o token<br>';
        echo 'Clique em "Edit Bot" → "Webhook" ou similar<br><br>';
        echo '<strong>Verifique:</strong><br>';
        echo '✓ URL do webhook é: <code>' . htmlspecialchars($url_webhook) . '</code><br>';
        echo '✓ Se URL começa com HTTPS (SSL obrigatório)<br>';
        echo '✓ Se o status mostra "Active" ou similar';
        echo '</div>';

        echo '<div class="step">';
        echo '<strong>PASSO 2: Testar Webhook Manualmente</strong><br><br>';
        echo 'Use CURL para testar se o webhook está respondendo:<br>';
        echo '<code>curl -X POST ' . htmlspecialchars($url_webhook) . '</code><br><br>';
        echo 'Se retornar status 200, o arquivo está acessível';
        echo '</div>';

        echo '<div class="step">';
        echo '<strong>PASSO 3: Enviar Mensagem de Teste</strong><br><br>';
        echo 'Após confirmar que o webhook está configurado no Telegram:<br>';
        echo '1. Vá para o canal Bateubet_VIP no Telegram<br>';
        echo '2. Envie uma mensagem com formato correto:<br>';
        echo '<code>Oportunidade! 🚨<br>📊 OVER ( +0.5 ⚽GOL FT )<br>Time A (H) x Time B (A)<br>Placar: 1 - 0</code><br><br>';
        echo '3. Aguarde 2-3 segundos<br>';
        echo '4. <button onclick="location.reload()">🔄 Recarregue esta página</button><br>';
        echo '5. Verifique os logs acima - deve mostrar a nova mensagem<br>';
        echo '</div>';

        echo '<div class="step">';
        echo '<strong>PASSO 4: Se Ainda Não Funcionar</strong><br><br>';
        echo 'Verifique:<br>';
        echo '✓ Se o certificado SSL é válido (HTTPS)<br>';
        echo '✓ Se a porta está aberta (padrão 443 para HTTPS)<br>';
        echo '✓ Se há firewall bloqueando requisições POST<br>';
        echo '✓ Se o arquivo api/telegram-webhook.php tem permissões de leitura<br><br>';
        echo '<button onclick="testarSSL()">🔐 Testar SSL</button>';
        echo '</div>';

        echo '</div>';

        // Log dos últimos erros
        echo '<div class="box">';
        echo '<h2>📊 Análise do Log</h2>';

        if (file_exists($arquivo_log)) {
            $conteudo_log = file_get_contents($arquivo_log);
            $total_linhas = substr_count($conteudo_log, "\n");
            $total_webhooks = substr_count($conteudo_log, 'Webhook acionado');
            $total_erros = substr_count($conteudo_log, '❌');

            echo '<div class="item">';
            echo '<strong>Total de linhas no log:</strong> ' . $total_linhas;
            echo '</div>';

            echo '<div class="item">';
            echo '<strong>Total de webhooks processados:</strong> ' . $total_webhooks;
            echo '</div>';

            echo '<div class="item">';
            echo '<strong>Total de erros registrados:</strong> ' . $total_erros;
            echo '</div>';

            if ($total_webhooks === 0) {
                echo '<div class="item error">';
                echo '❌ PROBLEMA: Webhook nunca foi chamado!<br>';
                echo 'Possível causa: Webhook não está configurado no Telegram';
                echo '</div>';
            }
        }

        echo '</div>';
        ?>
    </div>

    <script>
        function testarSSL() {
            const url = '<?php echo $url_webhook; ?>';
            console.log('Testando SSL em: ' + url);
            
            fetch(url, { method: 'POST' })
                .then(response => {
                    console.log('Status:', response.status);
                    if (response.status === 200) {
                        alert('✅ SSL está OK e servidor respondeu 200');
                    } else {
                        alert('⚠️ Resposta: ' + response.status);
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('❌ Erro na requisição:\n' + error.message);
                });
        }
    </script>
</body>
</html>
