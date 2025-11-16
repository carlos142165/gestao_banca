<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔧 Debug Webhook Detalhado</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .header h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .section h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
            font-size: 20px;
        }
        .info-box {
            background: #f5f7fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
        .status-ok { color: #27ae60; font-weight: bold; }
        .status-error { color: #e74c3c; font-weight: bold; }
        .status-warning { color: #f39c12; font-weight: bold; }
        .log-box {
            background: #1e1e1e;
            color: #00ff00;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            max-height: 400px;
            overflow-y: auto;
            margin-top: 15px;
            line-height: 1.5;
        }
        .log-error { color: #ff6b6b; }
        .log-success { color: #51cf66; }
        .log-info { color: #74c0fc; }
        .log-warning { color: #ffd93d; }
        button {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            margin-top: 15px;
            transition: all 0.3s;
        }
        button:hover {
            background: #764ba2;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .success-message {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .error-message {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .warning-message {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .code-box {
            background: #f5f7fa;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            margin: 15px 0;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Debug Webhook Detalhado</h1>
            <p>Verificar configuração do webhook e por que mensagens não chegam no banco</p>
        </div>

        <?php
        require_once 'config.php';

        echo '<div class="section">';
        echo '<h2>🔍 INFORMAÇÕES DO SERVIDOR</h2>';

        echo '<div class="info-box">';
        echo '<strong>URL Atual:</strong> ' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '<br>';
        echo '<strong>Método HTTP:</strong> ' . $_SERVER['REQUEST_METHOD'] . '<br>';
        echo '<strong>IP do Servidor:</strong> ' . $_SERVER['SERVER_ADDR'] . '<br>';
        echo '<strong>PHP Version:</strong> ' . phpversion() . '<br>';
        echo '</div>';

        echo '<div class="info-box">';
        echo '<strong>Ambiente Detectado:</strong> ' . (defined('ENVIRONMENT') ? ENVIRONMENT : 'DESCONHECIDO') . '<br>';
        echo '<strong>Host Detectado:</strong> ' . DB_HOST . '<br>';
        echo '<strong>Banco Detectado:</strong> ' . DB_NAME . '<br>';
        echo '<strong>Username:</strong> ' . DB_USERNAME . '<br>';
        echo '</div>';

        echo '</div>';

        // Verificar arquivo webhook
        echo '<div class="section">';
        echo '<h2>📄 ARQUIVO WEBHOOK</h2>';

        $arquivo_webhook = __DIR__ . '/api/telegram-webhook.php';
        if (file_exists($arquivo_webhook)) {
            echo '<div class="success-message">';
            echo '✅ Arquivo webhook.php EXISTE<br>';
            echo 'Caminho: ' . $arquivo_webhook . '<br>';
            echo 'Tamanho: ' . filesize($arquivo_webhook) . ' bytes';
            echo '</div>';

            // Mostrar primeiras linhas do arquivo
            echo '<strong>Primeiras 30 linhas do webhook:</strong>';
            $linhas = array_slice(file($arquivo_webhook), 0, 30);
            echo '<div class="code-box">';
            $i = 1;
            foreach ($linhas as $linha) {
                echo '<span class="log-info">' . str_pad($i++, 3, '0', STR_PAD_LEFT) . ':</span> ' . htmlspecialchars($linha) . '';
            }
            echo '</div>';
        } else {
            echo '<div class="error-message">';
            echo '❌ Arquivo webhook.php NÃO ENCONTRADO!<br>';
            echo 'Caminho esperado: ' . $arquivo_webhook;
            echo '</div>';
        }

        echo '</div>';

        // Verificar arquivo de log
        echo '<div class="section">';
        echo '<h2>📋 ARQUIVO DE LOG</h2>';

        $arquivo_log = __DIR__ . '/logs/telegram-webhook.log';
        if (file_exists($arquivo_log)) {
            echo '<div class="success-message">';
            echo '✅ Arquivo de log EXISTE<br>';
            echo 'Caminho: ' . $arquivo_log . '<br>';
            echo 'Tamanho: ' . filesize($arquivo_log) . ' bytes<br>';
            echo 'Última modificação: ' . date('d/m/Y H:i:s', filemtime($arquivo_log));
            echo '</div>';

            echo '<strong>ÚLTIMAS 50 LINHAS DO LOG:</strong>';
            $linhas_log = array_slice(file($arquivo_log), -50);
            echo '<div class="log-box">';
            foreach ($linhas_log as $linha) {
                $linha = htmlspecialchars($linha);
                
                // Colorir linhas baseado no conteúdo
                if (strpos($linha, 'erro') !== false || strpos($linha, 'Error') !== false) {
                    echo '<div class="log-error">' . $linha . '</div>';
                } elseif (strpos($linha, 'sucesso') !== false || strpos($linha, 'Success') !== false) {
                    echo '<div class="log-success">' . $linha . '</div>';
                } elseif (strpos($linha, 'aviso') !== false || strpos($linha, 'Warning') !== false) {
                    echo '<div class="log-warning">' . $linha . '</div>';
                } else {
                    echo '<div class="log-info">' . $linha . '</div>';
                }
            }
            echo '</div>';
        } else {
            echo '<div class="warning-message">';
            echo '⚠️ Arquivo de log ainda não foi criado<br>';
            echo 'Será criado quando o webhook receber a primeira mensagem<br>';
            echo 'Caminho esperado: ' . $arquivo_log;
            echo '</div>';
        }

        echo '</div>';

        // Verificar URL do webhook no Telegram
        echo '<div class="section">';
        echo '<h2>🤖 CONFIGURAÇÃO DO TELEGRAM</h2>';

        echo '<div class="info-box">';
        echo '<strong>URL esperada do webhook:</strong>';
        echo '</div>';
        echo '<div class="code-box">';
        $protocolo = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $url_webhook = $protocolo . '://' . $_SERVER['HTTP_HOST'] . '/gestao/gestao_banca/api/telegram-webhook.php';
        echo htmlspecialchars($url_webhook);
        echo '</div>';

        echo '<div class="info-box" style="margin-top: 15px;">';
        echo '<strong>Token do bot:</strong><br>';
        echo 'Se o arquivo config.php tiver a constante TELEGRAM_BOT_TOKEN, será mostrada abaixo:<br>';
        if (defined('TELEGRAM_BOT_TOKEN')) {
            $token = TELEGRAM_BOT_TOKEN;
            $masked = substr($token, 0, 10) . '...' . substr($token, -10);
            echo '<span class="status-ok">✅ Token configurado: ' . $masked . '</span>';
        } else {
            echo '<span class="status-warning">⚠️ Token não encontrado em config.php</span>';
        }
        echo '</div>';

        echo '</div>';

        // Verificar tabela
        echo '<div class="section">';
        echo '<h2>🗄️ TABELA "BOTE"</h2>';

        if ($conexao && !$conexao->connect_error) {
            $resultTabela = $conexao->query("SHOW TABLES LIKE 'bote'");
            if ($resultTabela && $resultTabela->num_rows > 0) {
                echo '<div class="success-message">';
                echo '✅ Tabela EXISTE<br>';
                echo '</div>';

                // Mostrar estrutura
                $resultColunas = $conexao->query("DESCRIBE bote");
                echo '<strong>Colunas da tabela:</strong>';
                echo '<table style="width: 100%; border-collapse: collapse; margin-top: 10px;">';
                echo '<tr style="background: #667eea; color: white;"><th style="padding: 10px; text-align: left;">Coluna</th><th style="padding: 10px; text-align: left;">Tipo</th></tr>';
                while ($col = $resultColunas->fetch_assoc()) {
                    echo '<tr style="border-bottom: 1px solid #ddd;"><td style="padding: 10px;">' . $col['Field'] . '</td><td style="padding: 10px;">' . $col['Type'] . '</td></tr>';
                }
                echo '</table>';

                // Contar registros
                $resultCount = $conexao->query("SELECT COUNT(*) as total FROM bote");
                $rowCount = $resultCount->fetch_assoc();
                echo '<div class="info-box" style="margin-top: 15px;">';
                echo '<strong>Total de registros:</strong> ' . $rowCount['total'];
                echo '</div>';
            } else {
                echo '<div class="error-message">';
                echo '❌ Tabela NÃO EXISTE!<br>';
                echo 'A tabela "bote" não foi encontrada no banco de dados.';
                echo '</div>';
            }
        } else {
            echo '<div class="error-message">';
            echo '❌ Erro ao conectar com banco de dados<br>';
            echo $conexao->connect_error;
            echo '</div>';
        }

        echo '</div>';

        // Checklist final
        echo '<div class="section">';
        echo '<h2>✅ CHECKLIST DE CONFIGURAÇÃO</h2>';

        $checklist = [
            'Arquivo webhook existe' => file_exists($arquivo_webhook),
            'Arquivo de log existe' => file_exists($arquivo_log),
            'Tabela "bote" existe' => isset($rowCount),
            'Conexão com banco OK' => $conexao && !$conexao->connect_error,
            'Ambiente detectado' => defined('ENVIRONMENT'),
            'Token Telegram configurado' => defined('TELEGRAM_BOT_TOKEN')
        ];

        $todos_ok = true;
        foreach ($checklist as $item => $status) {
            $icone = $status ? '✅' : '❌';
            $classe = $status ? 'status-ok' : 'status-error';
            if (!$status) $todos_ok = false;
            echo '<div class="info-box">';
            echo '<span class="' . $classe . '">' . $icone . ' ' . $item . '</span>';
            echo '</div>';
        }

        echo '</div>';

        // Instruções
        echo '<div class="section">';
        echo '<h2>📝 POSSÍVEIS CAUSAS E SOLUÇÕES</h2>';

        echo '<h3 style="color: #333; margin: 15px 0;">Se o webhook NÃO foi chamado:</h3>';
        echo '<ol style="margin-left: 20px; color: #666;">';
        echo '<li><strong>Webhook URL errada no Telegram</strong><br>';
        echo 'URL esperada: ' . $url_webhook . '<br>';
        echo 'Verifique se essa URL está correta no @BotFather do Telegram</li><br>';
        
        echo '<li><strong>Webhook não foi configurado</strong><br>';
        echo 'O webhook pode não estar ativo no Telegram. Verifique com @BotFather</li><br>';

        echo '<li><strong>Porta bloqueada</strong><br>';
        echo 'Se o servidor usa HTTPS, verifique se o certificado SSL é válido</li>';
        echo '</ol>';

        echo '<h3 style="color: #333; margin-top: 20px; margin-bottom: 15px;">Se o webhook foi chamado mas não salvou:</h3>';
        echo '<ol style="margin-left: 20px; color: #666;">';
        echo '<li><strong>Erro ao conectar com banco</strong><br>';
        echo 'Verifique se DB_HOST, DB_USERNAME, DB_PASSWORD estão corretos em config.php</li><br>';

        echo '<li><strong>Erro ao executar SQL</strong><br>';
        echo 'Verifique se as colunas da tabela "bote" estão corretas<br>';
        echo 'O webhook tenta inserir: titulo, conteudo, tipos_apostas, placar, data_criacao, etc.</li><br>';

        echo '<li><strong>Erro de permissão no banco</strong><br>';
        echo 'Verifique se o usuário ' . DB_USERNAME . ' tem permissão para INSERT na tabela "bote"</li>';
        echo '</ol>';

        echo '<h3 style="color: #333; margin-top: 20px; margin-bottom: 15px;">Para testar o webhook manualmente:</h3>';
        echo '<button onclick="testarWebhook()">🧪 Simular Webhook</button>';

        echo '</div>';

        // Botões de ação
        echo '<div class="section">';
        echo '<h2>🚀 AÇÕES</h2>';
        echo '<div style="display: flex; gap: 10px; flex-wrap: wrap;">';
        echo '<button onclick="location.reload()">🔄 Recarregar Diagnóstico</button>';
        echo '<button onclick="location.href=\'diagnostico-mensagens.php\'" style="background: #95a5a6;">📊 Ver Diagnóstico de Mensagens</button>';
        echo '<button onclick="location.href=\'bot_aovivo.php\'" style="background: #95a5a6;">📱 Abrir bot_aovivo.php</button>';
        echo '</div>';
        echo '</div>';
        ?>
    </div>

    <script>
        function testarWebhook() {
            alert('Teste de webhook será implementado em breve');
            // TODO: Implementar teste de webhook
        }
    </script>
</body>
</html>
