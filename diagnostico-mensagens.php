<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔍 Diagnóstico - Mensagens no Banco</title>
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
        .header p {
            color: #666;
            font-size: 14px;
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
        .info-label {
            font-weight: bold;
            color: #333;
            display: inline-block;
            min-width: 150px;
        }
        .info-value {
            color: #666;
        }
        .status-ok { color: #27ae60; font-weight: bold; }
        .status-error { color: #e74c3c; font-weight: bold; }
        .status-warning { color: #f39c12; font-weight: bold; }
        .icon-ok { color: #27ae60; margin-right: 5px; }
        .icon-error { color: #e74c3c; margin-right: 5px; }
        .icon-warning { color: #f39c12; margin-right: 5px; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        table th {
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        table td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }
        table tr:hover {
            background: #f5f7fa;
        }
        .log-box {
            background: #1e1e1e;
            color: #00ff00;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            max-height: 300px;
            overflow-y: auto;
            margin-top: 15px;
        }
        .log-line {
            margin-bottom: 5px;
        }
        .log-timestamp {
            color: #888;
        }
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        button {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
        }
        button:hover {
            background: #764ba2;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        button.secondary {
            background: #95a5a6;
        }
        button.secondary:hover {
            background: #7f8c8d;
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
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .stat-card {
            background: #f5f7fa;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
            border: 2px solid #ddd;
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        .checkbox-label {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            cursor: pointer;
        }
        .checkbox-label input {
            margin-right: 10px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <h1>🔍 Diagnóstico de Mensagens no Banco</h1>
            <p>Verificar se as mensagens do Telegram estão chegando no banco de dados</p>
        </div>

        <?php
        // Incluir configuração
        require_once 'config.php';

        $diagnostico = [
            'conexao_ok' => false,
            'tabela_existe' => false,
            'total_mensagens' => 0,
            'mensagens_hoje' => 0,
            'ultima_mensagem' => null,
            'webhook_foi_chamado' => false,
            'log_webhook_existe' => false,
            'arquivo_webhook_existe' => false
        ];

        // 1. VERIFICAR CONEXÃO
        echo '<div class="section">';
        echo '<h2>1️⃣ CONEXÃO COM BANCO DE DADOS</h2>';

        if ($conexao && !$conexao->connect_error) {
            echo '<div class="success-message">';
            echo '<span class="icon-ok">✅</span>';
            echo '<span class="status-ok">Conectado com sucesso!</span>';
            echo '<div class="info-box" style="margin-top: 10px;">';
            echo '<span class="info-label">Host:</span> <span class="info-value">' . DB_HOST . '</span><br>';
            echo '<span class="info-label">Banco:</span> <span class="info-value">' . DB_NAME . '</span><br>';
            echo '<span class="info-label">Ambiente:</span> <span class="info-value">' . ENVIRONMENT . '</span>';
            echo '</div>';
            echo '</div>';
            $diagnostico['conexao_ok'] = true;
        } else {
            echo '<div class="error-message">';
            echo '<span class="icon-error">❌</span>';
            echo '<span class="status-error">Erro ao conectar!</span>';
            echo '<div class="info-box" style="margin-top: 10px;">';
            echo 'Erro: ' . $conexao->connect_error;
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';

        if ($diagnostico['conexao_ok']) {
            // 2. VERIFICAR TABELA
            echo '<div class="section">';
            echo '<h2>2️⃣ TABELA "BOTE"</h2>';

            $resultTabela = $conexao->query("SHOW TABLES LIKE 'bote'");
            if ($resultTabela && $resultTabela->num_rows > 0) {
                echo '<div class="success-message">';
                echo '<span class="icon-ok">✅</span>';
                echo '<span class="status-ok">Tabela existe!</span>';
                echo '</div>';
                $diagnostico['tabela_existe'] = true;

                // Mostrar estrutura da tabela
                $resultColunas = $conexao->query("DESCRIBE bote");
                echo '<table>';
                echo '<tr><th>Coluna</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>';
                while ($coluna = $resultColunas->fetch_assoc()) {
                    echo '<tr>';
                    echo '<td>' . $coluna['Field'] . '</td>';
                    echo '<td>' . $coluna['Type'] . '</td>';
                    echo '<td>' . $coluna['Null'] . '</td>';
                    echo '<td>' . $coluna['Key'] . '</td>';
                    echo '<td>' . $coluna['Default'] . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<div class="error-message">';
                echo '<span class="icon-error">❌</span>';
                echo '<span class="status-error">Tabela NÃO existe!</span>';
                echo '</div>';
            }
            echo '</div>';

            // 3. CONTAR MENSAGENS
            if ($diagnostico['tabela_existe']) {
                echo '<div class="section">';
                echo '<h2>3️⃣ CONTAGEM DE MENSAGENS</h2>';

                $resultTotal = $conexao->query("SELECT COUNT(*) as total FROM bote");
                $rowTotal = $resultTotal->fetch_assoc();
                $diagnostico['total_mensagens'] = $rowTotal['total'];

                // Mensagens de hoje
                $hoje = date('Y-m-d');
                $resultHoje = $conexao->query("SELECT COUNT(*) as total FROM bote WHERE DATE(data_criacao) = '$hoje'");
                $rowHoje = $resultHoje->fetch_assoc();
                $diagnostico['mensagens_hoje'] = $rowHoje['total'];

                echo '<div class="stats-grid">';
                echo '<div class="stat-card">';
                echo '<div class="stat-number">' . $diagnostico['total_mensagens'] . '</div>';
                echo '<div class="stat-label">Total de Mensagens</div>';
                echo '</div>';
                echo '<div class="stat-card">';
                echo '<div class="stat-number">' . $diagnostico['mensagens_hoje'] . '</div>';
                echo '<div class="stat-label">Mensagens Hoje</div>';
                echo '</div>';
                echo '</div>';

                if ($diagnostico['total_mensagens'] == 0) {
                    echo '<div class="warning-message">';
                    echo '<span class="icon-warning">⚠️</span>';
                    echo '<span class="status-warning">Nenhuma mensagem no banco ainda!</span>';
                    echo '</div>';
                }
                echo '</div>';

                // 4. ÚLTIMAS MENSAGENS
                echo '<div class="section">';
                echo '<h2>4️⃣ ÚLTIMAS MENSAGENS</h2>';

                $resultMensagens = $conexao->query("SELECT * FROM bote ORDER BY data_criacao DESC LIMIT 10");

                if ($resultMensagens && $resultMensagens->num_rows > 0) {
                    echo '<table>';
                    echo '<tr>';
                    echo '<th>ID</th>';
                    echo '<th>Título</th>';
                    echo '<th>Data</th>';
                    echo '<th>Hora</th>';
                    echo '</tr>';

                    while ($msg = $resultMensagens->fetch_assoc()) {
                        $dataHora = $msg['data_criacao'];
                        $data = date('d/m/Y', strtotime($dataHora));
                        $hora = date('H:i:s', strtotime($dataHora));

                        echo '<tr>';
                        echo '<td>' . $msg['id'] . '</td>';
                        echo '<td>' . substr($msg['titulo'], 0, 50) . (strlen($msg['titulo']) > 50 ? '...' : '') . '</td>';
                        echo '<td>' . $data . '</td>';
                        echo '<td>' . $hora . '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                } else {
                    echo '<div class="error-message">';
                    echo '<span class="icon-error">❌</span>';
                    echo 'Nenhuma mensagem encontrada no banco.';
                    echo '</div>';
                }
                echo '</div>';

                // 5. VERIFICAR WEBHOOK
                echo '<div class="section">';
                echo '<h2>5️⃣ WEBHOOK E LOGS</h2>';

                // Verificar arquivo webhook
                $arquivo_webhook = __DIR__ . '/api/telegram-webhook.php';
                if (file_exists($arquivo_webhook)) {
                    echo '<div class="info-box">';
                    echo '<span class="icon-ok">✅</span>';
                    echo '<span class="status-ok">Arquivo webhook existe</span><br>';
                    echo '<span class="info-label">Caminho:</span> ' . $arquivo_webhook;
                    echo '</div>';
                    $diagnostico['arquivo_webhook_existe'] = true;
                } else {
                    echo '<div class="info-box">';
                    echo '<span class="icon-error">❌</span>';
                    echo '<span class="status-error">Arquivo webhook NÃO encontrado</span>';
                    echo '</div>';
                }

                // Verificar arquivo de log
                $arquivo_log = __DIR__ . '/logs/telegram-webhook.log';
                if (file_exists($arquivo_log)) {
                    echo '<div class="info-box" style="margin-top: 15px;">';
                    echo '<span class="icon-ok">✅</span>';
                    echo '<span class="status-ok">Arquivo de log existe</span><br>';
                    echo '<span class="info-label">Caminho:</span> ' . $arquivo_log . '<br>';
                    echo '<span class="info-label">Tamanho:</span> ' . round(filesize($arquivo_log) / 1024, 2) . ' KB';
                    echo '</div>';
                    $diagnostico['log_webhook_existe'] = true;

                    // Mostrar últimas linhas do log
                    echo '<div style="margin-top: 15px;">';
                    echo '<strong>Últimas 20 linhas do log:</strong>';
                    $linhas_log = array_slice(file($arquivo_log), -20);
                    echo '<div class="log-box">';
                    foreach ($linhas_log as $linha) {
                        echo '<div class="log-line">' . htmlspecialchars($linha) . '</div>';
                    }
                    echo '</div>';
                    echo '</div>';

                    // Verificar se webhook foi chamado recentemente
                    $conteudo_log = file_get_contents($arquivo_log);
                    if (strpos($conteudo_log, 'Webhook acionado') !== false) {
                        $diagnostico['webhook_foi_chamado'] = true;
                    }
                } else {
                    echo '<div class="warning-message">';
                    echo '<span class="icon-warning">⚠️</span>';
                    echo 'Arquivo de log ainda não foi criado. Ele será criado na primeira mensagem.';
                    echo '</div>';
                }
                echo '</div>';

                // 6. RESUMO DO DIAGNÓSTICO
                echo '<div class="section">';
                echo '<h2>📊 RESUMO DO DIAGNÓSTICO</h2>';

                $checklist = [
                    ['Conexão com Banco', $diagnostico['conexao_ok']],
                    ['Tabela existe', $diagnostico['tabela_existe']],
                    ['Arquivo webhook', $diagnostico['arquivo_webhook_existe']],
                    ['Log do webhook', $diagnostico['log_webhook_existe']],
                    ['Webhook foi chamado', $diagnostico['webhook_foi_chamado']],
                    ['Tem mensagens', $diagnostico['total_mensagens'] > 0]
                ];

                foreach ($checklist as $item) {
                    $status = $item[1] ? '✅' : '❌';
                    $classe = $item[1] ? 'status-ok' : 'status-error';
                    echo '<div class="info-box">';
                    echo '<span class="' . $classe . '">' . $status . ' ' . $item[0] . '</span>';
                    echo '</div>';
                }

                // Mensagem final
                echo '<div style="margin-top: 20px; padding: 15px; background: #f5f7fa; border-radius: 5px;">';
                if ($diagnostico['total_mensagens'] > 0) {
                    echo '<span class="status-ok">✅ MENSAGENS ESTÃO CHEGANDO NO BANCO!</span><br><br>';
                    echo 'Total de mensagens: <strong>' . $diagnostico['total_mensagens'] . '</strong><br>';
                    echo 'Mensagens hoje: <strong>' . $diagnostico['mensagens_hoje'] . '</strong>';
                } else {
                    echo '<span class="status-error">❌ NENHUMA MENSAGEM NO BANCO!</span><br><br>';
                    echo '<strong>O que pode estar errado:</strong><br>';
                    echo '1. Webhook não está sendo chamado pelo Telegram<br>';
                    echo '2. Webhook não consegue se conectar ao banco<br>';
                    echo '3. Erro ao salvar dados no banco<br>';
                    echo '4. Webhook configurado para banco errado';
                }
                echo '</div>';
                echo '</div>';

                // 7. INSTRUÇÕES
                echo '<div class="section">';
                echo '<h2>📋 INSTRUÇÕES PARA TESTAR</h2>';

                echo '<h3 style="margin-top: 15px; color: #333;">Se NÃO tem mensagens:</h3>';
                echo '<ol style="margin-left: 20px; color: #666;">';
                echo '<li>Verifique se o Telegram webhook está configurado corretamente</li>';
                echo '<li>Envie uma mensagem no canal Telegram e espere 2-3 segundos</li>';
                echo '<li>Recarregue esta página (F5)</li>';
                echo '<li>Verifique se a mensagem aparece aqui</li>';
                echo '<li>Se não aparecer, verifique o arquivo de log acima para mensagens de erro</li>';
                echo '</ol>';

                echo '<h3 style="margin-top: 15px; color: #333;">Se tem mensagens mas não aparecem no site:</h3>';
                echo '<ol style="margin-left: 20px; color: #666;">';
                echo '<li>Verifique o arquivo <strong>api/carregar-mensagens-banco.php</strong></li>';
                echo '<li>Verifique se <strong>bot_aovivo.php</strong> está carregando as mensagens corretamente</li>';
                echo '<li>Abra o navegador (F12) e verifique se há erros no console</li>';
                echo '</ol>';

                echo '<h3 style="margin-top: 15px; color: #333;">Próximos passos:</h3>';
                echo '<div class="button-group">';
                echo '<button onclick="location.href=\'teste-webhook-completo.php\'">🧪 Teste Webhook Completo</button>';
                echo '<button class="secondary" onclick="location.href=\'bot_aovivo.php\'">📱 Abrir bot_aovivo.php</button>';
                echo '<button class="secondary" onclick="location.reload()">🔄 Recarregar Diagnóstico</button>';
                echo '</div>';
                echo '</div>';
            }
        }
        ?>
    </div>
</body>
</html>
