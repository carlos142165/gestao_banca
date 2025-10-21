<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste - Modal de Planos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .test-container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        .test-button {
            background: #667eea;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            margin: 10px 0;
        }
        .test-button:hover {
            background: #764ba2;
        }
        .log-output {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 15px;
            margin-top: 20px;
            font-family: monospace;
            font-size: 12px;
            max-height: 400px;
            overflow-y: auto;
            color: #333;
        }
        .log-line {
            margin: 5px 0;
            padding: 2px 0;
        }
        .success { color: #27ae60; }
        .error { color: #e74c3c; }
        .warning { color: #f39c12; }
        .info { color: #3498db; }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>🧪 Teste - Modal de Planos</h1>
        
        <p>Este arquivo testa se:</p>
        <ul>
            <li>✅ PlanoManager.js carrega corretamente</li>
            <li>✅ Planos são obtidos da API (obter-planos.php)</li>
            <li>✅ Grid é renderizado com 4 planos</li>
            <li>✅ Modal abre com conteúdo visível</li>
        </ul>

        <button class="test-button" onclick="testarCarregamentoPlanos()">
            📋 Testar Carregamento de Planos
        </button>

        <button class="test-button" onclick="testarAberturModal()">
            🔲 Testar Abertura da Modal
        </button>

        <button class="test-button" onclick="testarVerificacaoLimite()">
            ⚠️ Testar Verificação de Limite
        </button>

        <button class="test-button" onclick="limparLogs()">
            🗑️ Limpar Logs
        </button>

        <div class="log-output" id="logOutput">
            <div class="log-line info">Aguardando testes...</div>
        </div>
    </div>

    <script src="js/plano-manager.js" defer></script>

    <script>
        function adicionarLog(mensagem, tipo = 'info') {
            const output = document.getElementById('logOutput');
            const linha = document.createElement('div');
            linha.className = `log-line ${tipo}`;
            linha.textContent = `[${new Date().toLocaleTimeString()}] ${mensagem}`;
            output.appendChild(linha);
            output.scrollTop = output.scrollHeight;
        }

        function limparLogs() {
            document.getElementById('logOutput').innerHTML = '';
            adicionarLog('Logs limpos', 'info');
        }

        async function testarCarregamentoPlanos() {
            adicionarLog('🔄 Iniciando teste de carregamento...', 'info');
            
            try {
                adicionarLog('📊 Verificando PlanoManager...', 'info');
                
                if (typeof PlanoManager === 'undefined') {
                    adicionarLog('❌ PlanoManager não está definido!', 'error');
                    return;
                }

                adicionarLog('✅ PlanoManager encontrado', 'success');

                // Verificar se planos já foram carregados
                if (PlanoManager.planos && PlanoManager.planos.length > 0) {
                    adicionarLog(`✅ ${PlanoManager.planos.length} plano(s) já carregado(s)`, 'success');
                    PlanoManager.planos.forEach((plano, i) => {
                        adicionarLog(`   ${i+1}. ${plano.nome} - R$ ${plano.preco_mes}`, 'info');
                    });
                    return;
                }

                // Se não carregados, carregar agora
                adicionarLog('🔄 Planos ainda não carregados, carregando...', 'warning');
                await PlanoManager.carregarPlanos();
                
                adicionarLog(`✅ ${PlanoManager.planos.length} plano(s) carregado(s)`, 'success');
                PlanoManager.planos.forEach((plano, i) => {
                    adicionarLog(`   ${i+1}. ${plano.nome} - R$ ${plano.preco_mes}/mês`, 'info');
                });

            } catch (error) {
                adicionarLog(`❌ Erro: ${error.message}`, 'error');
            }
        }

        async function testarAberturModal() {
            adicionarLog('🔄 Testando abertura da modal...', 'info');
            
            try {
                if (typeof PlanoManager === 'undefined') {
                    adicionarLog('❌ PlanoManager não está definido!', 'error');
                    return;
                }

                // Verificar se container HTML existe
                const container = document.getElementById('planosGrid');
                if (!container) {
                    adicionarLog('❌ Container #planosGrid não encontrado no HTML!', 'error');
                    return;
                }
                adicionarLog('✅ Container #planosGrid encontrado', 'success');

                // Garantir que planos estão carregados
                if (!PlanoManager.planos || PlanoManager.planos.length === 0) {
                    adicionarLog('⏳ Carregando planos...', 'warning');
                    await PlanoManager.carregarPlanos();
                }

                adicionarLog(`📊 Renderizando ${PlanoManager.planos.length} plano(s)...`, 'info');
                PlanoManager.renderizarPlanos();

                // Contar quantos cards foram criados
                const cards = container.querySelectorAll('.plano-card');
                adicionarLog(`✅ ${cards.length} card(s) renderizado(s)`, 'success');

                if (cards.length === 0) {
                    adicionarLog('⚠️ Nenhum card foi criado!', 'warning');
                    return;
                }

                // Abrir modal
                adicionarLog('🔲 Abrindo modal...', 'info');
                PlanoManager.abrirModalPlanos();

                const modal = document.getElementById('modal-planos');
                if (modal && modal.style.display !== 'none') {
                    adicionarLog('✅ Modal aberta com sucesso!', 'success');
                } else {
                    adicionarLog('❌ Modal não abriu!', 'error');
                }

            } catch (error) {
                adicionarLog(`❌ Erro: ${error.message}`, 'error');
            }
        }

        async function testarVerificacaoLimite() {
            adicionarLog('🔄 Testando verificação de limite...', 'info');
            
            try {
                if (typeof PlanoManager === 'undefined') {
                    adicionarLog('❌ PlanoManager não está definido!', 'error');
                    return;
                }

                adicionarLog('🔄 Chamando verificarEExibirPlanos("entrada")...', 'info');
                const resultado = await PlanoManager.verificarEExibirPlanos('entrada');
                
                if (resultado === false) {
                    adicionarLog('⚠️ Limite atingido - Modal deve ter aberto', 'warning');
                    const modal = document.getElementById('modal-planos');
                    if (modal && modal.style.display !== 'none') {
                        adicionarLog('✅ Modal aberta corretamente!', 'success');
                    }
                } else {
                    adicionarLog('✅ Limite não atingido - Pode prosseguir', 'success');
                }

            } catch (error) {
                adicionarLog(`❌ Erro: ${error.message}`, 'error');
            }
        }

        // Log automático quando página carrega
        document.addEventListener('DOMContentLoaded', () => {
            adicionarLog('✅ Página carregada', 'success');
            adicionarLog(`PlanoManager definido: ${typeof PlanoManager !== 'undefined'}`, 'info');
        });

        // Monitorar PlanoManager inicialização
        setTimeout(() => {
            if (typeof PlanoManager !== 'undefined') {
                if (PlanoManager.planos && PlanoManager.planos.length > 0) {
                    adicionarLog(`✅ PlanoManager auto-inicializado com ${PlanoManager.planos.length} plano(s)`, 'success');
                } else {
                    adicionarLog('⏳ PlanoManager.planos ainda vazio', 'warning');
                }
            }
        }, 2000);
    </script>

    <!-- Incluir a modal HTML -->
    <?php include 'modal-planos-pagamento.html'; ?>
</body>
</html>
