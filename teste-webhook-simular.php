<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧪 Teste Webhook - Simular Mensagem do Telegram</title>
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
        }
        .info-box {
            background: #f5f7fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
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
            line-height: 1.6;
        }
        .log-line { margin-bottom: 3px; }
        .log-success { color: #51cf66; }
        .log-error { color: #ff6b6b; }
        .log-info { color: #74c0fc; }
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
            <h1>🧪 Teste do Webhook - Simular Mensagem Telegram</h1>
            <p>Simula um POST do Telegram para testar o webhook localmente</p>
        </div>

        <div class="section">
            <h2>📋 Etapas do Teste</h2>
            <p>Este teste vai:</p>
            <ol style="margin-left: 20px; margin-top: 10px; color: #666;">
                <li>Verificar a conexão com o banco de dados</li>
                <li>Verificar se a tabela "bote" existe</li>
                <li>Simular uma mensagem do Telegram</li>
                <li>Enviar para o webhook (como se fosse do Telegram)</li>
                <li>Verificar se foi salvo no banco</li>
                <li>Mostrar os logs do webhook</li>
            </ol>
        </div>

        <?php
        require_once 'config.php';

        echo '<div class="section">';
        echo '<h2>🔍 VERIFICAÇÕES INICIAIS</h2>';

        // 1. Verificar conexão
        if ($conexao && !$conexao->connect_error) {
            echo '<div class="success-message">';
            echo '✅ Conectado ao banco: <strong>' . DB_NAME . '</strong><br>';
            echo 'Host: <strong>' . DB_HOST . '</strong><br>';
            echo 'Ambiente: <strong>' . ENVIRONMENT . '</strong>';
            echo '</div>';
        } else {
            echo '<div class="error-message">';
            echo '❌ ERRO ao conectar: ' . $conexao->connect_error;
            echo '</div>';
            die();
        }

        // 2. Verificar tabela
        $resultTabela = $conexao->query("SHOW TABLES LIKE 'bote'");
        if ($resultTabela && $resultTabela->num_rows > 0) {
            echo '<div class="success-message">';
            echo '✅ Tabela "bote" EXISTE';
            echo '</div>';
        } else {
            echo '<div class="error-message">';
            echo '❌ ERRO: Tabela "bote" NÃO EXISTE!';
            echo '</div>';
            die();
        }

        echo '</div>';

        // 3. Simular webhook
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['testar'])) {
            echo '<div class="section">';
            echo '<h2>🚀 EXECUTANDO TESTE</h2>';

            // Criar JSON simulado do Telegram
            $dados_simulados = [
                'update_id' => rand(1000000000, 9999999999),
                'channel_post' => [
                    'message_id' => rand(10000, 99999),
                    'chat' => [
                        'id' => -1001234567890,
                        'title' => 'Bateubet_VIP',
                        'type' => 'channel'
                    ],
                    'date' => time(),
                    'text' => "Oportunidade! 🚨\n📊 OVER ( +0.5 ⚽ GOL FT )\nFlamengo (H) x Botafogo (A)\nPlacar: 1 - 0"
                ]
            ];

            echo '<div class="info-box">';
            echo '<strong>JSON Simulado do Telegram:</strong>';
            echo '</div>';
            echo '<div class="code-box">';
            echo htmlspecialchars(json_encode($dados_simulados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            echo '</div>';

            // Contar registros antes
            $resultAntes = $conexao->query("SELECT COUNT(*) as total FROM bote");
            $rowAntes = $resultAntes->fetch_assoc();
            $totalAntes = $rowAntes['total'];

            echo '<div class="info-box">';
            echo '<strong>Mensagens antes:</strong> ' . $totalAntes;
            echo '</div>';

            // Simular o webhook incluindo o arquivo e passando os dados
            echo '<div class="info-box">';
            echo '<strong>Simulando POST para webhook...</strong>';
            echo '</div>';

            // Executar o webhook manualmente
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_SERVER['HTTP_HOST'] = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
            
            // Simular o input do Telegram
            $json_input = json_encode($dados_simulados);
            
            // Incluir e executar
            ob_start();
            include 'api/telegram-webhook.php';
            $webhook_output = ob_get_clean();

            echo '<div class="log-box">';
            if (!empty($webhook_output)) {
                echo '<div class="log-success">' . htmlspecialchars($webhook_output) . '</div>';
            }
            echo '</div>';

            // Contar registros depois
            $resultDepois = $conexao->query("SELECT COUNT(*) as total FROM bote");
            $rowDepois = $resultDepois->fetch_assoc();
            $totalDepois = $rowDepois['total'];

            echo '<div class="info-box">';
            echo '<strong>Mensagens depois:</strong> ' . $totalDepois;
            echo '</div>';

            if ($totalDepois > $totalAntes) {
                echo '<div class="success-message">';
                echo '✅ SUCESSO! Mensagem foi salva no banco!<br>';
                echo 'Novas mensagens: ' . ($totalDepois - $totalAntes);
                echo '</div>';

                // Mostrar última mensagem
                $resultUltima = $conexao->query("SELECT * FROM bote ORDER BY id DESC LIMIT 1");
                if ($resultUltima && $resultUltima->num_rows > 0) {
                    $ultima = $resultUltima->fetch_assoc();
                    echo '<div class="info-box" style="margin-top: 15px;">';
                    echo '<strong>Última mensagem inserida:</strong><br>';
                    echo 'ID: ' . $ultima['id'] . '<br>';
                    echo 'Título: ' . $ultima['titulo'] . '<br>';
                    echo 'Time 1: ' . $ultima['time_1'] . '<br>';
                    echo 'Time 2: ' . $ultima['time_2'] . '<br>';
                    echo 'Data: ' . $ultima['data_criacao'];
                    echo '</div>';
                }
            } else {
                echo '<div class="error-message">';
                echo '❌ ERRO! Mensagem NÃO foi salva!<br>';
                echo 'Total antes: ' . $totalAntes . '<br>';
                echo 'Total depois: ' . $totalDepois . '<br>';
                echo 'Verifique os logs acima para mais informações.';
                echo '</div>';
            }

            // Mostrar últimas linhas do log
            $arquivo_log = __DIR__ . '/logs/telegram-webhook.log';
            if (file_exists($arquivo_log)) {
                echo '<div class="info-box" style="margin-top: 20px;">';
                echo '<strong>Últimas linhas do log do webhook:</strong>';
                echo '</div>';
                
                $linhas_log = array_slice(file($arquivo_log), -30);
                echo '<div class="log-box">';
                foreach ($linhas_log as $linha) {
                    $linha_html = htmlspecialchars($linha);
                    
                    if (strpos($linha, '✅') !== false) {
                        echo '<div class="log-line log-success">' . $linha_html . '</div>';
                    } elseif (strpos($linha, '❌') !== false) {
                        echo '<div class="log-line log-error">' . $linha_html . '</div>';
                    } else {
                        echo '<div class="log-line log-info">' . $linha_html . '</div>';
                    }
                }
                echo '</div>';
            }

            echo '</div>';
        } else {
            // Mostrar formulário
            echo '<div class="section">';
            echo '<h2>▶️ INICIAR TESTE</h2>';
            echo '<form method="POST">';
            echo '<button type="submit" name="testar" value="1">🧪 Executar Teste de Webhook</button>';
            echo '</form>';
            echo '</div>';

            // Mostrar log anterior
            $arquivo_log = __DIR__ . '/logs/telegram-webhook.log';
            if (file_exists($arquivo_log)) {
                echo '<div class="section">';
                echo '<h2>📝 LOG ANTERIOR</h2>';
                $linhas_log = array_slice(file($arquivo_log), -50);
                echo '<div class="log-box">';
                foreach ($linhas_log as $linha) {
                    $linha_html = htmlspecialchars($linha);
                    
                    if (strpos($linha, '✅') !== false) {
                        echo '<div class="log-line log-success">' . $linha_html . '</div>';
                    } elseif (strpos($linha, '❌') !== false || strpos($linha, 'erro') !== false) {
                        echo '<div class="log-line log-error">' . $linha_html . '</div>';
                    } else {
                        echo '<div class="log-line log-info">' . $linha_html . '</div>';
                    }
                }
                echo '</div>';
                echo '</div>';
            }
        }
        ?>

        <div class="section">
            <h2>📚 INFORMAÇÕES ÚTEIS</h2>
            <div class="info-box">
                <strong>Se o teste passar:</strong><br>
                O webhook FUNCIONA! Problemas podem estar em:<br>
                - URL do webhook errada no Telegram<br>
                - Webhook não está ativo no Telegram (@BotFather)<br>
                - Certificado SSL inválido
            </div>
            <div class="info-box">
                <strong>Se o teste falhar:</strong><br>
                Verifique:<br>
                - Tabela "bote" e suas colunas<br>
                - Permissões do usuário do banco<br>
                - Mensagem está no formato correto
            </div>
            <button onclick="location.href='diagnostico-mensagens.php'" style="background: #95a5a6;">📊 Ir para Diagnóstico</button>
        </div>
    </div>
</body>
</html>
