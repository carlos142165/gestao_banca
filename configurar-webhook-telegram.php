<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔗 Configurar Webhook do Telegram</title>
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
        .info { background: #e7f3ff; border-left: 4px solid #2196F3; padding: 15px; margin: 15px 0; border-radius: 5px; }
        .success { background: #d4edda; border-left: 4px solid #27ae60; padding: 15px; margin: 15px 0; border-radius: 5px; color: #155724; }
        .error { background: #f8d7da; border-left: 4px solid #e74c3c; padding: 15px; margin: 15px 0; border-radius: 5px; color: #721c24; }
        .code-box {
            background: #f5f7fa;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            margin: 15px 0;
            word-break: break-all;
            user-select: all;
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
            margin: 10px 10px 10px 0;
        }
        button:hover { background: #764ba2; transform: translateY(-2px); }
        button.secondary { background: #95a5a6; }
        button.secondary:hover { background: #7f8c8d; }
        .step {
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        .step h3 { color: #333; margin-bottom: 10px; }
        .step p { color: #666; line-height: 1.6; }
        input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            margin: 10px 0;
        }
        .spinner {
            display: inline-block;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            margin-right: 10px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="box">
            <h2>🔗 Configurar Webhook do Telegram</h2>
            <p>Configure automaticamente o webhook para que o Telegram envie mensagens ao seu site</p>
        </div>

        <?php
        require_once 'config.php';

        // Verificar se Token está configurado
        if (!defined('TELEGRAM_BOT_TOKEN') || empty(TELEGRAM_BOT_TOKEN)) {
            echo '<div class="box error">';
            echo '<h2>❌ ERRO: Token Telegram não configurado</h2>';
            echo '<p>Adicione TELEGRAM_BOT_TOKEN em telegram-config.php</p>';
            echo '</div>';
            die();
        }

        $token = TELEGRAM_BOT_TOKEN;
        $protocolo = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $url_webhook = $protocolo . '://' . $_SERVER['HTTP_HOST'] . '/api/telegram-webhook.php';

        echo '<div class="box">';
        echo '<h2>📋 Informações</h2>';

        echo '<div class="info">';
        echo '<strong>Token do Bot:</strong><br>';
        echo '<code>' . substr($token, 0, 15) . '...' . substr($token, -10) . '</code>';
        echo '</div>';

        echo '<div class="info">';
        echo '<strong>URL do Webhook:</strong><br>';
        echo '<code>' . htmlspecialchars($url_webhook) . '</code>';
        echo '</div>';

        echo '</div>';

        // Opção 1: Configurar via formulário
        echo '<div class="box">';
        echo '<h2>✅ OPÇÃO 1: Configurar Automaticamente</h2>';

        echo '<p>Clique no botão abaixo para configurar o webhook automaticamente:</p>';
        echo '<button onclick="configurarWebhook()">🔗 Configurar Webhook no Telegram</button>';
        echo '<div id="resultado-config"></div>';

        echo '</div>';

        // Opção 2: Comando CURL
        echo '<div class="box">';
        echo '<h2>⚙️ OPÇÃO 2: Configurar via CURL (Manual)</h2>';

        $comando_curl = 'curl -X POST "https://api.telegram.org/bot' . $token . '/setWebhook?url=' . urlencode($url_webhook) . '"';

        echo '<p>Se preferir, execute este comando no terminal:</p>';
        echo '<div class="code-box">';
        echo htmlspecialchars($comando_curl);
        echo '</div>';

        echo '<button onclick="copiarComando()">📋 Copiar Comando</button>';

        echo '</div>';

        // Opção 3: Verificar Status
        echo '<div class="box">';
        echo '<h2>🔍 Verificar Status do Webhook</h2>';

        echo '<p>Clique para ver se o webhook está configurado corretamente:</p>';
        echo '<button onclick="verificarWebhook()">🔍 Verificar Status</button>';
        echo '<div id="resultado-status"></div>';

        echo '</div>';

        // Instruções adicionais
        echo '<div class="box">';
        echo '<h2>📝 Instruções Passo a Passo</h2>';

        echo '<div class="step">';
        echo '<h3>PASSO 1: Clique em "Configurar Webhook no Telegram"</h3>';
        echo '<p>O sistema fará uma requisição para o Telegram API para ativar o webhook</p>';
        echo '</div>';

        echo '<div class="step">';
        echo '<h3>PASSO 2: Aguarde a confirmação</h3>';
        echo '<p>Deve aparecer "✅ Webhook configurado com sucesso!"</p>';
        echo '</div>';

        echo '<div class="step">';
        echo '<h3>PASSO 3: Teste enviando uma mensagem</h3>';
        echo '<p>Vá para o canal Bateubet_VIP no Telegram e envie uma mensagem com formato:</p>';
        echo '<code style="background: #f5f7fa; padding: 10px; display: block; margin: 10px 0; border-radius: 5px;">';
        echo 'Oportunidade! 🚨<br>';
        echo '📊 OVER ( +0.5 ⚽GOL FT )<br>';
        echo 'Flamengo (H) x Botafogo (A)<br>';
        echo 'Placar: 1 - 0';
        echo '</code>';
        echo '</div>';

        echo '<div class="step">';
        echo '<h3>PASSO 4: Verifique se chegou no banco</h3>';
        echo '<p>Acesse: <a href="diagnostico-mensagens.php" target="_blank">diagnostico-mensagens.php</a></p>';
        echo '<p>A mensagem deve aparecer em "Últimas Mensagens"</p>';
        echo '</div>';

        echo '</div>';

        // Script de configuração
        echo '<script>';
        echo 'const token = "' . $token . '";';
        echo 'const urlWebhook = "' . htmlspecialchars($url_webhook) . '";';
        echo '
        function configurarWebhook() {
            const resultado = document.getElementById("resultado-config");
            resultado.innerHTML = "<p><span class=\"spinner\"></span>Configurando webhook...</p>";

            const url = "https://api.telegram.org/bot" + token + "/setWebhook?url=" + encodeURIComponent(urlWebhook);

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    console.log("Resposta:", data);
                    
                    if (data.ok) {
                        resultado.innerHTML = `
                            <div class="success">
                                <h3>✅ Webhook Configurado com Sucesso!</h3>
                                <p><strong>Status:</strong> ${data.description}</p>
                                <p>O Telegram agora enviará mensagens para: <code>' . htmlspecialchars($url_webhook) . '</code></p>
                                <p style="margin-top: 15px;">
                                    <button class="secondary" onclick="window.open(\'https://t.me/Bateubet_VIP\', \'_blank\')">📱 Ir para o Canal Telegram</button>
                                    <button class="secondary" onclick="window.location.href=\'diagnostico-mensagens.php\'">📊 Ir para Diagnóstico</button>
                                </p>
                            </div>
                        `;
                    } else {
                        resultado.innerHTML = `
                            <div class="error">
                                <h3>❌ Erro ao Configurar Webhook</h3>
                                <p><strong>Erro:</strong> ${data.description}</p>
                                <p>Possíveis causas:</p>
                                <ul style="margin-left: 20px;">
                                    <li>URL inválida ou inacessível</li>
                                    <li>Certificado SSL inválido</li>
                                    <li>Token inválido</li>
                                </ul>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    resultado.innerHTML = `
                        <div class="error">
                            <h3>❌ Erro de Conexão</h3>
                            <p>${error.message}</p>
                        </div>
                    `;
                });
        }

        function verificarWebhook() {
            const resultado = document.getElementById("resultado-status");
            resultado.innerHTML = "<p><span class=\"spinner\"></span>Verificando status...</p>";

            const url = "https://api.telegram.org/bot" + token + "/getWebhookInfo";

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    console.log("Status:", data);
                    
                    if (data.ok && data.result) {
                        const webhookInfo = data.result;
                        let status = "❌ NÃO CONFIGURADO";
                        
                        if (webhookInfo.url) {
                            status = "✅ ATIVO";
                        }

                        resultado.innerHTML = `
                            <div class="info">
                                <h3>Status do Webhook</h3>
                                <p><strong>Status:</strong> ${status}</p>
                                <p><strong>URL Configurada:</strong> ${webhookInfo.url || "Nenhuma"}</p>
                                <p><strong>Última Falha:</strong> ${webhookInfo.last_error_message || "Nenhuma"}</p>
                                <p><strong>Mensagens Pendentes:</strong> ${webhookInfo.pending_update_count || 0}</p>
                            </div>
                        `;
                    } else {
                        resultado.innerHTML = `
                            <div class="error">
                                <p>Erro ao verificar status</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    resultado.innerHTML = `<div class="error"><p>${error.message}</p></div>`;
                });
        }

        function copiarComando() {
            const comando = "curl -X POST \"https://api.telegram.org/bot' . $token . '/setWebhook?url=' . urlencode($url_webhook) . '\"";
            navigator.clipboard.writeText(comando).then(() => {
                alert("✅ Comando copiado para a área de transferência!");
            });
        }
        ';
        echo '</script>';
        ?>
    </div>
</body>
</html>
