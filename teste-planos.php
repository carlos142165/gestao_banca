<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste - Sistema de Planos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        h1 {
            color: #2c3e50;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .test-section {
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 2px solid #ecf0f1;
        }
        
        .test-section:last-child {
            border-bottom: none;
        }
        
        .test-title {
            font-size: 18px;
            font-weight: 700;
            color: #34495e;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .test-button {
            padding: 12px 20px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            margin: 5px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .test-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }
        
        .test-button.danger {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }
        
        .test-button.success {
            background: linear-gradient(135deg, #27ae60, #229954);
        }
        
        .test-result {
            background: #f8f9fa;
            border: 1px solid #ecf0f1;
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
            font-family: monospace;
            font-size: 12px;
            max-height: 200px;
            overflow-y: auto;
            color: #2c3e50;
        }
        
        .result-success {
            border-left: 4px solid #27ae60;
            background: #eafaf1;
        }
        
        .result-error {
            border-left: 4px solid #e74c3c;
            background: #fadbd8;
            color: #c0392b;
        }
        
        .result-warning {
            border-left: 4px solid #f39c12;
            background: #fef5e7;
            color: #d68910;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }
        
        .badge-success {
            background: #d5f4e6;
            color: #27ae60;
        }
        
        .badge-error {
            background: #fadbd8;
            color: #e74c3c;
        }
        
        .badge-warning {
            background: #fef5e7;
            color: #f39c12;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>
        <i class="fas fa-flask"></i>
        Testes do Sistema de Planos
    </h1>
    
    <!-- ===== TESTE: ENDPOINTS ===== -->
    <div class="test-section">
        <div class="test-title">
            <i class="fas fa-globe"></i>
            Testes de Endpoints
        </div>
        
        <button class="test-button success" onclick="testarObtePlanos()">
            <i class="fas fa-list"></i> Testar /obter-planos.php
        </button>
        
        <button class="test-button success" onclick="testarObteDadosUsuario()">
            <i class="fas fa-user"></i> Testar /obter-dados-usuario.php
        </button>
        
        <button class="test-button success" onclick="testarObtCartoesSalvos()">
            <i class="fas fa-credit-card"></i> Testar /obter-cartoes-salvos.php
        </button>
        
        <button class="test-button success" onclick="testarVerificarLimite()">
            <i class="fas fa-check"></i> Testar /verificar-limite.php
        </button>
        
        <div id="resultado-endpoints" class="test-result" style="display: none;"></div>
    </div>
    
    <!-- ===== TESTE: MODAL ===== -->
    <div class="test-section">
        <div class="test-title">
            <i class="fas fa-window-maximize"></i>
            Testes de Interface
        </div>
        
        <button class="test-button" onclick="abrirModalPlanos()">
            <i class="fas fa-crown"></i> Abrir Modal de Planos
        </button>
        
        <button class="test-button" onclick="abrirModalPagamento()">
            <i class="fas fa-credit-card"></i> Abrir Modal de Pagamento
        </button>
        
        <button class="test-button danger" onclick="fecharModals()">
            <i class="fas fa-times"></i> Fechar Modais
        </button>
    </div>
    
    <!-- ===== TESTE: VALIDAÇÕES ===== -->
    <div class="test-section">
        <div class="test-title">
            <i class="fas fa-shield-alt"></i>
            Testes de Validações
        </div>
        
        <button class="test-button" onclick="testarValidacaoCartao()">
            <i class="fas fa-check-circle"></i> Testar Validação de Cartão
        </button>
        
        <button class="test-button" onclick="testarValidacaoPeriodo()">
            <i class="fas fa-calendar"></i> Testar Toggle Período
        </button>
        
        <div id="resultado-validacoes" class="test-result" style="display: none;"></div>
    </div>
    
    <!-- ===== TESTE: DATABASE ===== -->
    <div class="test-section">
        <div class="test-title">
            <i class="fas fa-database"></i>
            Testes de Database
        </div>
        
        <button class="test-button" onclick="testarConexaoDB()">
            <i class="fas fa-plug"></i> Testar Conexão BD
        </button>
        
        <button class="test-button" onclick="testarTabelasDB()">
            <i class="fas fa-table"></i> Verificar Tabelas
        </button>
        
        <button class="test-button" onclick="testarPlanosPadrão()">
            <i class="fas fa-plus"></i> Inserir Planos Padrão
        </button>
        
        <div id="resultado-database" class="test-result" style="display: none;"></div>
    </div>
    
    <!-- ===== TESTE: CONFIG MERCADO PAGO ===== -->
    <div class="test-section">
        <div class="test-title">
            <i class="fas fa-wallet"></i>
            Configuração Mercado Pago
        </div>
        
        <button class="test-button" onclick="testarConfigMP()">
            <i class="fas fa-cogs"></i> Verificar Credenciais
        </button>
        
        <div id="resultado-mp" class="test-result" style="display: none;"></div>
    </div>
    
    <!-- ===== TESTE: PERFORMANCE ===== -->
    <div class="test-section">
        <div class="test-title">
            <i class="fas fa-tachometer-alt"></i>
            Testes de Performance
        </div>
        
        <button class="test-button" onclick="testarPerformanceCarregamentoPlanos()">
            <i class="fas fa-stopwatch"></i> Carregar Planos (Tempo)
        </button>
        
        <button class="test-button" onclick="testarPerformanceModal()">
            <i class="fas fa-clock"></i> Render Modal (Tempo)
        </button>
        
        <div id="resultado-performance" class="test-result" style="display: none;"></div>
    </div>
</div>

<!-- ===== INCLUIR OS SCRIPTS NECESSÁRIOS ===== -->
<?php include 'modal-planos-pagamento.html'; ?>

<script>
    // Toast simples para testes
    function mostrarResultado(elementId, mensagem, tipo = 'success') {
        const elemento = document.getElementById(elementId);
        elemento.innerHTML = `<span style="color: ${tipo === 'error' ? '#e74c3c' : '#27ae60'}">${mensagem}</span>`;
        elemento.style.display = 'block';
    }
    
    // ===== TESTE: ENDPOINTS =====
    async function testarObtePlanos() {
        try {
            const inicio = performance.now();
            const response = await fetch('obter-planos.php');
            const tempo = (performance.now() - inicio).toFixed(2);
            
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            
            const data = await response.json();
            const resultado = document.getElementById('resultado-endpoints');
            
            if (data.success) {
                resultado.innerHTML = `
                    <strong>✅ Sucesso!</strong><br>
                    Planos carregados: ${data.planos.length}<br>
                    Tempo: ${tempo}ms<br>
                    <pre>${JSON.stringify(data.planos[0], null, 2)}</pre>
                `;
                resultado.className = 'test-result result-success';
            } else {
                throw new Error(data.message);
            }
            resultado.style.display = 'block';
        } catch (error) {
            document.getElementById('resultado-endpoints').innerHTML = `❌ Erro: ${error.message}`;
            document.getElementById('resultado-endpoints').className = 'test-result result-error';
            document.getElementById('resultado-endpoints').style.display = 'block';
        }
    }
    
    async function testarObteDadosUsuario() {
        try {
            const response = await fetch('obter-dados-usuario.php');
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            
            const data = await response.json();
            const resultado = document.getElementById('resultado-endpoints');
            
            if (data.success) {
                resultado.innerHTML = `
                    <strong>✅ Dados do Usuário</strong><br>
                    Nome: ${data.usuario.nome}<br>
                    Plano: ${data.usuario.nome_plano}<br>
                    Status: ${data.usuario.status_assinatura}<br>
                    <pre>${JSON.stringify(data.usuario, null, 2)}</pre>
                `;
                resultado.className = 'test-result result-success';
            } else {
                throw new Error(data.message);
            }
            resultado.style.display = 'block';
        } catch (error) {
            document.getElementById('resultado-endpoints').innerHTML = `❌ Erro: ${error.message}`;
            document.getElementById('resultado-endpoints').className = 'test-result result-error';
            document.getElementById('resultado-endpoints').style.display = 'block';
        }
    }
    
    async function testarObtCartoesSalvos() {
        try {
            const response = await fetch('obter-cartoes-salvos.php');
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            
            const data = await response.json();
            const resultado = document.getElementById('resultado-endpoints');
            
            resultado.innerHTML = `
                <strong>✅ Cartões Salvos</strong><br>
                Total: ${data.cartoes.length}<br>
                <pre>${JSON.stringify(data.cartoes, null, 2)}</pre>
            `;
            resultado.className = 'test-result result-success';
            resultado.style.display = 'block';
        } catch (error) {
            document.getElementById('resultado-endpoints').innerHTML = `❌ Erro: ${error.message}`;
            document.getElementById('resultado-endpoints').className = 'test-result result-error';
            document.getElementById('resultado-endpoints').style.display = 'block';
        }
    }
    
    async function testarVerificarLimite() {
        try {
            const response = await fetch('verificar-limite.php?acao=mentor');
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            
            const data = await response.json();
            const resultado = document.getElementById('resultado-endpoints');
            
            resultado.innerHTML = `
                <strong>✅ Verificação de Limite</strong><br>
                Pode prosseguir: ${data.pode_prosseguir ? 'SIM' : 'NÃO'}<br>
                Plano: ${data.plano_atual}<br>
                ${data.mensagem ? 'Mensagem: ' + data.mensagem : ''}
            `;
            resultado.className = data.pode_prosseguir ? 'test-result result-success' : 'test-result result-warning';
            resultado.style.display = 'block';
        } catch (error) {
            document.getElementById('resultado-endpoints').innerHTML = `❌ Erro: ${error.message}`;
            document.getElementById('resultado-endpoints').className = 'test-result result-error';
            document.getElementById('resultado-endpoints').style.display = 'block';
        }
    }
    
    // ===== TESTE: INTERFACE =====
    function abrirModalPlanos() {
        if (typeof PlanoManager !== 'undefined' && PlanoManager.abrirModalPlanos) {
            PlanoManager.abrirModalPlanos();
        } else {
            alert('PlanoManager não está carregado');
        }
    }
    
    function abrirModalPagamento() {
        // Selecionar um plano de teste
        PlanoManager.planoSelecionado = { id: 2, nome: 'PRATA', preco: 25.90, periodo: 'mes' };
        PlanoManager.abrirModalPagamento();
    }
    
    function fecharModals() {
        PlanoManager?.fecharModalPlanos();
        PlanoManager?.fecharModalPagamento();
    }
    
    // ===== TESTE: VALIDAÇÕES =====
    function testarValidacaoCartao() {
        const dados = {
            numero_cartao: '4111111111111111',
            validade: '12/25',
            cvv: '123'
        };
        
        const resultado = document.getElementById('resultado-validacoes');
        const valido = PlanoManager.validarDadosCartao(dados);
        
        resultado.innerHTML = `
            <strong>${valido ? '✅ Cartão válido' : '❌ Cartão inválido'}</strong><br>
            Validações:
            <ul>
                <li>Número: ${/^\d{13,19}$/.test(dados.numero_cartao.replace(/\s/g, '')) ? '✅' : '❌'}</li>
                <li>Data: ${/^\d{2}\/\d{2}$/.test(dados.validade) ? '✅' : '❌'}</li>
                <li>CVV: ${/^\d{3,4}$/.test(dados.cvv) ? '✅' : '❌'}</li>
            </ul>
        `;
        resultado.className = valido ? 'test-result result-success' : 'test-result result-error';
        resultado.style.display = 'block';
    }
    
    function testarValidacaoPeriodo() {
        const resultado = document.getElementById('resultado-validacoes');
        resultado.innerHTML = `
            <strong>Período selecionado: ${PlanoManager.periodoAtual}</strong><br>
            Clique nos botões MÊS/ANO para testar.
        `;
        resultado.className = 'test-result result-success';
        resultado.style.display = 'block';
    }
    
    // ===== TESTE: DATABASE =====
    function testarConexaoDB() {
        fetch('testar-conexao-db.php')
            .then(r => r.json())
            .then(data => {
                const resultado = document.getElementById('resultado-database');
                resultado.innerHTML = `
                    <strong>${data.success ? '✅' : '❌'} Conexão BD</strong><br>
                    ${data.message}
                `;
                resultado.className = data.success ? 'test-result result-success' : 'test-result result-error';
                resultado.style.display = 'block';
            })
            .catch(e => {
                document.getElementById('resultado-database').innerHTML = `❌ Erro: ${e.message}`;
                document.getElementById('resultado-database').className = 'test-result result-error';
                document.getElementById('resultado-database').style.display = 'block';
            });
    }
    
    function testarTabelasDB() {
        alert('Implemente testar-tabelas-db.php para este teste');
    }
    
    function testarPlanosPadrão() {
        alert('Implemente inserir-planos-db.php para este teste');
    }
    
    // ===== TESTE: MERCADO PAGO =====
    function testarConfigMP() {
        const resultado = document.getElementById('resultado-mp');
        resultado.innerHTML = `
            ⚠️ Verifique manualmente em config_mercadopago.php:<br>
            - MP_ACCESS_TOKEN definido?<br>
            - MP_PUBLIC_KEY definido?<br>
            - MP_ENVIRONMENT correto?
        `;
        resultado.className = 'test-result result-warning';
        resultado.style.display = 'block';
    }
    
    // ===== TESTE: PERFORMANCE =====
    function testarPerformanceCarregamentoPlanos() {
        const resultado = document.getElementById('resultado-performance');
        const inicio = performance.now();
        
        PlanoManager.carregarPlanos().then(() => {
            const tempo = (performance.now() - inicio).toFixed(2);
            resultado.innerHTML = `
                <strong>✅ Planos carregados</strong><br>
                Tempo: ${tempo}ms<br>
                Total: ${PlanoManager.planos.length} planos
            `;
            resultado.className = 'test-result result-success';
            resultado.style.display = 'block';
        }).catch(e => {
            resultado.innerHTML = `❌ Erro: ${e.message}`;
            resultado.className = 'test-result result-error';
            resultado.style.display = 'block';
        });
    }
    
    function testarPerformanceModal() {
        const resultado = document.getElementById('resultado-performance');
        const inicio = performance.now();
        
        PlanoManager.renderizarPlanos();
        const tempo = (performance.now() - inicio).toFixed(2);
        
        resultado.innerHTML = `
            <strong>✅ Modal renderizado</strong><br>
            Tempo: ${tempo}ms
        `;
        resultado.className = 'test-result result-success';
        resultado.style.display = 'block';
    }
    
    // Inicializar ao carregar
    document.addEventListener('DOMContentLoaded', () => {
        console.log('✅ Página de testes carregada');
    });
</script>

</body>
</html>
