<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧪 Teste - Simular Mensagem do Telegram</title>
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
        .info { background: #d4edda; border-left: 4px solid #27ae60; padding: 15px; margin: 15px 0; border-radius: 5px; }
        .error { background: #f8d7da; border-left: 4px solid #e74c3c; padding: 15px; margin: 15px 0; border-radius: 5px; }
        .code-box {
            background: #1e1e1e;
            color: #00ff00;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            max-height: 400px;
            overflow-y: auto;
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
            margin: 10px 0;
        }
        button:hover { background: #764ba2; }
    </style>
</head>
<body>
    <div class="container">
        <div class="box">
            <h2>🧪 Teste - Simular Mensagem do Telegram</h2>
            <p>Simula uma mensagem chegando do Telegram para testar se o webhook processa corretamente</p>
        </div>

        <div class="box">
            <h2>📝 Teste de Webhook</h2>
            
            <p>Clique no botão abaixo para simular uma mensagem do Telegram e ver se o webhook consegue salvá-la no banco:</p>
            
            <button onclick="testarWebhook()">🧪 Simular Mensagem do Telegram</button>
            
            <div id="resultado"></div>
        </div>

        <div class="box">
            <h2>📊 Resultado</h2>
            <div id="resultado-detalhado"></div>
        </div>
    </div>

    <script>
        function testarWebhook() {
            const resultado = document.getElementById('resultado');
            const resultadoDetalhado = document.getElementById('resultado-detalhado');
            
            // Dados simulados de uma mensagem do Telegram
            const dados = {
                update_id: Math.floor(Math.random() * 1000000000),
                channel_post: {
                    message_id: Math.floor(Math.random() * 100000),
                    chat: {
                        id: -1002047004959,
                        title: 'Bateubet_VIP',
                        type: 'channel'
                    },
                    date: Math.floor(Date.now() / 1000),
                    text: `Oportunidade! 🚨\n📊 OVER ( +0.5 ⚽GOL FT )\nFlamengo (H) x Botafogo (A)\nPlacar: 1 - 0`
                }
            };

            resultado.innerHTML = '<p>Enviando mensagem de teste...</p>';
            resultadoDetalhado.innerHTML = '<p>Aguarde...</p>';

            // Enviar para o webhook
            fetch('api/telegram-webhook.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(dados)
            })
            .then(response => {
                console.log('Status:', response.status);
                return response.text();
            })
            .then(text => {
                console.log('Resposta:', text);
                
                resultado.innerHTML = `
                    <div class="info">
                        <h3>✅ Webhook respondeu com sucesso!</h3>
                        <p><strong>Dados enviados:</strong></p>
                        <pre>${JSON.stringify(dados, null, 2)}</pre>
                        <p><strong>Resposta do webhook:</strong></p>
                        <pre>${text}</pre>
                    </div>
                `;

                // Aguardar 2 segundos e verificar banco de dados
                setTimeout(() => {
                    verificarBanco();
                }, 2000);
            })
            .catch(error => {
                console.error('Erro:', error);
                resultado.innerHTML = `
                    <div class="error">
                        <h3>❌ Erro ao enviar para webhook</h3>
                        <p>${error.message}</p>
                    </div>
                `;
            });
        }

        function verificarBanco() {
            const resultadoDetalhado = document.getElementById('resultado-detalhado');
            
            fetch('diagnostico-mensagens.php')
                .then(response => response.text())
                .then(html => {
                    // Extrair número de mensagens do HTML
                    const match = html.match(/Total de Mensagens.*?(\d+)/);
                    if (match) {
                        const total = match[1];
                        resultadoDetalhado.innerHTML = `
                            <div class="info">
                                <h3>✅ Verificação do Banco</h3>
                                <p>Total de mensagens no banco agora: <strong>${total}</strong></p>
                                <p>Se o número aumentou, significa que a mensagem foi salva com sucesso!</p>
                                <p><a href="diagnostico-mensagens.php" target="_blank">Ver detalhes</a></p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    resultadoDetalhado.innerHTML = `<p>Erro ao verificar banco: ${error.message}</p>`;
                });
        }
    </script>
</body>
</html>
