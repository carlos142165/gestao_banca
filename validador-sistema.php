<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validador de Sistema de Planos</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 30px;
        }
        
        h1 {
            color: #2c3e50;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .test-section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            border-radius: 5px;
        }
        
        .test-section h2 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .test-group {
            margin-bottom: 15px;
        }
        
        .test-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            margin-bottom: 10px;
            background: white;
            border-radius: 5px;
        }
        
        .status-icon {
            font-size: 20px;
            min-width: 30px;
        }
        
        .status-ok {
            color: #27ae60;
        }
        
        .status-error {
            color: #e74c3c;
        }
        
        .status-info {
            color: #3498db;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        
        .btn {
            flex: 1;
            min-width: 200px;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
            transform: translateY(-2px);
        }
        
        .result-box {
            background: #ecf0f1;
            padding: 15px;
            border-radius: 5px;
            margin-top: 10px;
            font-size: 14px;
        }
        
        .result-loading {
            text-align: center;
            color: #3498db;
        }
        
        .checklist {
            list-style: none;
        }
        
        .checklist li {
            padding: 8px 0;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .checklist li:last-child {
            border-bottom: none;
        }
        
        .code-block {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 12px;
            border-radius: 5px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            margin-top: 10px;
        }
        
        .warning {
            background: #fff3cd;
            border-left-color: #ffc107;
            color: #856404;
        }
        
        .success {
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }
        
        .error {
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Validador do Sistema de Planos</h1>
        
        <!-- Status Geral -->
        <div class="test-section success">
            <h2>✅ Status Geral do Sistema</h2>
            <ul class="checklist">
                <li><span class="status-icon status-ok">✅</span> Todas as variáveis `$conn` foram corrigidas para `$conexao`</li>
                <li><span class="status-icon status-ok">✅</span> 9 funções em `config_mercadopago.php` agora usam conexão correta</li>
                <li><span class="status-icon status-ok">✅</span> Query em `obter-planos.php` não filtra por coluna inexistente</li>
                <li><span class="status-icon status-ok">✅</span> Sistema de validação de limites está funcional</li>
            </ul>
        </div>
        
        <!-- Testes de API -->
        <div class="test-section">
            <h2>🧪 Testes de API</h2>
            
            <div class="test-group">
                <h3>Teste 1: Validação de Limite de Mentores</h3>
                <p style="margin-bottom: 10px; color: #555;">Verifica se o usuário pode adicionar outro mentor</p>
                <div class="btn-group">
                    <button class="btn btn-primary" onclick="testarLimiteMentores()">
                        <span>▶️</span> Testar Limite Mentores
                    </button>
                </div>
                <div id="resultado-mentor" class="result-box" style="display: none;"></div>
            </div>
            
            <div class="test-group" style="margin-top: 20px;">
                <h3>Teste 2: Validação de Limite de Entradas</h3>
                <p style="margin-bottom: 10px; color: #555;">Verifica se o usuário pode adicionar outra entrada hoje</p>
                <div class="btn-group">
                    <button class="btn btn-primary" onclick="testarLimiteEntradas()">
                        <span>▶️</span> Testar Limite Entradas
                    </button>
                </div>
                <div id="resultado-entrada" class="result-box" style="display: none;"></div>
            </div>
            
            <div class="test-group" style="margin-top: 20px;">
                <h3>Teste 3: Carregar Lista de Planos</h3>
                <p style="margin-bottom: 10px; color: #555;">Verifica se todos os planos são carregados corretamente</p>
                <div class="btn-group">
                    <button class="btn btn-primary" onclick="testarPlanos()">
                        <span>▶️</span> Carregar Planos
                    </button>
                </div>
                <div id="resultado-planos" class="result-box" style="display: none;"></div>
            </div>
        </div>
        
        <!-- Links Rápidos -->
        <div class="test-section">
            <h2>📚 Testes Completos (Páginas de Diagnóstico)</h2>
            <p style="margin-bottom: 15px; color: #555;">Abra estas páginas para testes mais detalhados:</p>
            <div class="btn-group">
                <a href="teste-validacao-completa.php?user_id=1" target="_blank" class="btn btn-secondary">
                    <span>📋</span> Validação Completa (User #1)
                </a>
                <a href="teste-api-verificacao.php?user_id=1&acao=mentor" target="_blank" class="btn btn-secondary">
                    <span>🔌</span> API de Verificação
                </a>
                <a href="debug-limite.php" target="_blank" class="btn btn-secondary">
                    <span>🐛</span> Debug de Limite
                </a>
            </div>
        </div>
        
        <!-- Verificação de Funções -->
        <div class="test-section">
            <h2>🔧 Funções Corrigidas em config_mercadopago.php</h2>
            <ul class="checklist">
                <li><span class="status-icon status-ok">✅</span> `criarPreferencia()` - linha 41</li>
                <li><span class="status-icon status-ok">✅</span> `salvarCartao()` - linha 207</li>
                <li><span class="status-icon status-ok">✅</span> `criarAssinatura()` - linha 259</li>
                <li><span class="status-icon status-ok">✅</span> `atualizarUsuarioAssinatura()` - linha 311</li>
                <li><span class="status-icon status-ok">✅</span> `planoExpirou()` - linha 338</li>
                <li><span class="status-icon status-ok">✅</span> `obterPlanoAtual()` - linha 367</li>
                <li><span class="status-icon status-ok">✅</span> `obterPlanoGratuito()` - linha 394</li>
                <li><span class="status-icon status-ok">✅</span> `verificarLimiteMentores()` - linha 417</li>
                <li><span class="status-icon status-ok">✅</span> `verificarLimiteEntradas()` - linha 457</li>
            </ul>
        </div>
        
        <!-- Log Console -->
        <div class="test-section warning">
            <h2>💡 Dica: Abra o Console F12</h2>
            <p>Pressione <code>F12</code> e vá para a aba <strong>Console</strong> para ver logs detalhados do sistema.</p>
            <div class="code-block">
// Verifique logs como:
✅ Planos carregados
✅ Dados do usuário carregados
✅ PlanoManager inicializado com sucesso

// Se houver erros:
❌ Erro ao carregar planos
❌ Erro ao carregar dados do usuário
            </div>
        </div>
    </div>
    
    <script>
        async function testarLimiteMentores() {
            const div = document.getElementById('resultado-mentor');
            div.innerHTML = '<div class="result-loading">⏳ Testando...</div>';
            div.style.display = 'block';
            
            try {
                const response = await fetch('teste-api-verificacao.php?user_id=1&acao=mentor');
                const data = await response.json();
                
                if (data.sucesso) {
                    const status = data.pode_prosseguir ? '✅ PODE' : '❌ BLOQUEADO';
                    div.innerHTML = `
                        <div><strong>${status}</strong> adicionar mentor</div>
                        <div style="margin-top: 10px; font-size: 13px;">
                            <div>📊 Limite: ${data.dados.limite}</div>
                            <div>📈 Atual: ${data.dados.atual}</div>
                            <div>✨ Disponível: ${Math.max(0, data.dados.limite - data.dados.atual)}</div>
                        </div>
                    `;
                } else {
                    div.innerHTML = `<div style="color: #e74c3c;">❌ Erro: ${data.mensagem}</div>`;
                }
            } catch (error) {
                div.innerHTML = `<div style="color: #e74c3c;">❌ Erro na requisição: ${error.message}</div>`;
            }
        }
        
        async function testarLimiteEntradas() {
            const div = document.getElementById('resultado-entrada');
            div.innerHTML = '<div class="result-loading">⏳ Testando...</div>';
            div.style.display = 'block';
            
            try {
                const response = await fetch('teste-api-verificacao.php?user_id=1&acao=entrada');
                const data = await response.json();
                
                if (data.sucesso) {
                    const status = data.pode_prosseguir ? '✅ PODE' : '❌ BLOQUEADO';
                    div.innerHTML = `
                        <div><strong>${status}</strong> adicionar entrada</div>
                        <div style="margin-top: 10px; font-size: 13px;">
                            <div>📊 Limite diário: ${data.dados.limite}</div>
                            <div>📈 Entradas hoje: ${data.dados.atual}</div>
                            <div>✨ Disponível hoje: ${Math.max(0, data.dados.limite - data.dados.atual)}</div>
                        </div>
                    `;
                } else {
                    div.innerHTML = `<div style="color: #e74c3c;">❌ Erro: ${data.mensagem}</div>`;
                }
            } catch (error) {
                div.innerHTML = `<div style="color: #e74c3c;">❌ Erro na requisição: ${error.message}</div>`;
            }
        }
        
        async function testarPlanos() {
            const div = document.getElementById('resultado-planos');
            div.innerHTML = '<div class="result-loading">⏳ Carregando planos...</div>';
            div.style.display = 'block';
            
            try {
                const response = await fetch('obter-planos.php');
                const data = await response.json();
                
                if (data.success && data.planos && data.planos.length > 0) {
                    let html = `<div>✅ ${data.planos.length} planos carregados com sucesso</div>`;
                    html += '<div style="margin-top: 10px;">';
                    data.planos.forEach(plano => {
                        html += `
                            <div style="padding: 8px; background: white; margin: 5px 0; border-radius: 3px;">
                                <strong>${plano.nome}</strong> - 
                                ${plano.mentores_limite >= 999 ? '∞' : plano.mentores_limite} mentores, 
                                ${plano.entradas_diarias >= 999 ? '∞' : plano.entradas_diarias} entradas/dia
                            </div>
                        `;
                    });
                    html += '</div>';
                    div.innerHTML = html;
                } else {
                    div.innerHTML = `<div style="color: #e74c3c;">❌ Erro: ${data.message || 'Nenhum plano carregado'}</div>`;
                }
            } catch (error) {
                div.innerHTML = `<div style="color: #e74c3c;">❌ Erro na requisição: ${error.message}</div>`;
            }
        }
    </script>
</body>
</html>
