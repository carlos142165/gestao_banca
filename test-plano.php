<?php
/**
 * TESTE DE PLANO DO USUÁRIO
 * ==========================
 * Página para debugar o carregamento do plano
 */

require_once 'config.php';
require_once 'carregar_sessao.php';

// Verificar se usuário está logado
$usuario_logado = isset($_SESSION['usuario_id']);
$id_usuario = $_SESSION['usuario_id'] ?? null;

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Plano do Usuário</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
        }
        .status {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            font-weight: bold;
        }
        .status.ok {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status.erro {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .code {
            background: #f0f0f0;
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
            margin: 10px 0;
            overflow-x: auto;
        }
        button {
            background: #2196F3;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin: 10px 5px 10px 0;
        }
        button:hover {
            background: #0b7dda;
        }
        .resultado {
            margin-top: 20px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        pre {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 TESTE DE PLANO DO USUÁRIO</h1>

        <!-- STATUS DO USUÁRIO -->
        <div class="info-box">
            <h3>📋 Status da Sessão</h3>
            <?php if ($usuario_logado): ?>
                <div class="status ok">
                    ✅ Usuário Logado (ID: <?php echo $id_usuario; ?>)
                </div>
            <?php else: ?>
                <div class="status erro">
                    ❌ Usuário NÃO Logado
                </div>
                <p><a href="login.php">Fazer login</a></p>
            <?php endif; ?>
        </div>

        <!-- TESTE 1: VERIFICAR BANCO DE DADOS -->
        <?php if ($usuario_logado): ?>
        <div class="info-box">
            <h3>🗄️ Teste 1: Dados do Banco de Dados</h3>
            <button onclick="testarBancoDados()">Verificar Banco de Dados</button>
            <div id="resultado-db" class="resultado" style="display:none;"></div>
        </div>

        <!-- TESTE 2: VERIFICAR API -->
        <div class="info-box">
            <h3>🌐 Teste 2: Verificar API (obter-plano-usuario.php)</h3>
            <button onclick="testarAPI()">Chamar API</button>
            <div id="resultado-api" class="resultado" style="display:none;"></div>
        </div>

        <!-- TESTE 3: TESTE DO JAVASCRIPT -->
        <div class="info-box">
            <h3>💻 Teste 3: Teste do JavaScript</h3>
            <button onclick="testarJavaScript()">Testar JavaScript</button>
            <div id="resultado-js" class="resultado" style="display:none;"></div>
        </div>

        <!-- TESTE 4: EXIBIÇÃO REAL -->
        <div class="info-box">
            <h3>🎨 Teste 4: Exibição Real do Plano</h3>
            <button onclick="testarExibicao()">Carregar Plano</button>
            <div id="exibicao-plano-usuario"></div>
        </div>
        <?php else: ?>
            <p style="color: red; font-size: 16px;">⚠️ Faça login primeiro para testar!</p>
        <?php endif; ?>

    </div>

    <!-- CSS DO PLANO -->
    <link rel="stylesheet" href="css/plano-usuario-badge.css">

    <script>
        /**
         * TESTE 1: Verificar dados do banco de dados
         */
        function testarBancoDados() {
            const resultado = document.getElementById('resultado-db');
            resultado.style.display = 'block';
            resultado.innerHTML = '<p>⏳ Consultando banco de dados...</p>';

            fetch('test-plano-db.php')
                .then(r => r.json())
                .then(dados => {
                    let html = '<h4>Resultado:</h4>';
                    html += '<pre>' + JSON.stringify(dados, null, 2) + '</pre>';
                    
                    if (dados.sucesso) {
                        html = '<div class="status ok">✅ SUCESSO - Banco de dados respondeu</div>' + html;
                    } else {
                        html = '<div class="status erro">❌ ERRO - ' + dados.mensagem + '</div>' + html;
                    }
                    
                    resultado.innerHTML = html;
                })
                .catch(err => {
                    resultado.innerHTML = '<div class="status erro">❌ ERRO na requisição: ' + err.message + '</div>';
                });
        }

        /**
         * TESTE 2: Verificar API
         */
        function testarAPI() {
            const resultado = document.getElementById('resultado-api');
            resultado.style.display = 'block';
            resultado.innerHTML = '<p>⏳ Chamando API obter-plano-usuario.php...</p>';

            fetch('obter-plano-usuario.php')
                .then(r => r.json())
                .then(dados => {
                    let html = '<h4>Resposta da API:</h4>';
                    html += '<pre>' + JSON.stringify(dados, null, 2) + '</pre>';
                    
                    if (dados.sucesso) {
                        html = '<div class="status ok">✅ API FUNCIONANDO - Plano: ' + dados.plano.nome + '</div>' + html;
                    } else {
                        html = '<div class="status erro">❌ ERRO na API - ' + dados.mensagem + '</div>' + html;
                    }
                    
                    resultado.innerHTML = html;
                })
                .catch(err => {
                    resultado.innerHTML = '<div class="status erro">❌ ERRO na requisição: ' + err.message + '</div>';
                });
        }

        /**
         * TESTE 3: Testar JavaScript
         */
        function testarJavaScript() {
            const resultado = document.getElementById('resultado-js');
            resultado.style.display = 'block';

            let html = '<h4>Teste de Variáveis JavaScript:</h4>';
            
            html += '<p><strong>PlanoUsuarioManager existe?</strong> ';
            html += (typeof PlanoUsuarioManager !== 'undefined') ? '✅ SIM' : '❌ NÃO';
            html += '</p>';

            html += '<p><strong>window.planoAtual:</strong> ';
            html += (window.planoAtual) ? '✅ ' + JSON.stringify(window.planoAtual) : '❌ Ainda não carregado';
            html += '</p>';

            html += '<p><strong>Container existe?</strong> ';
            const container = document.getElementById('exibicao-plano-usuario');
            html += (container) ? '✅ SIM' : '❌ NÃO';
            html += '</p>';

            resultado.innerHTML = html;
        }

        /**
         * TESTE 4: Carregar e exibir o plano
         */
        function testarExibicao() {
            if (typeof PlanoUsuarioManager !== 'undefined') {
                PlanoUsuarioManager.carregarPlano();
                document.getElementById('resultado-js').innerHTML += '<p style="color: green;">✅ PlanoUsuarioManager.carregarPlano() executado</p>';
            } else {
                alert('PlanoUsuarioManager não está carregado!');
            }
        }

        // Auto testar quando página carregar
        console.log('Página de teste carregada');
        console.log('PlanoUsuarioManager:', typeof PlanoUsuarioManager);
    </script>

    <!-- CARREGAR O GERENCIADOR DE PLANO -->
    <script src="js/plano-usuario.js"></script>
</body>
</html>
