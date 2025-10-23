<?php
/**
 * GERADOR DE CREDENCIAIS - TEMPLATE
 * ===================================
 * Arquivo de ajuda para você copiar/colar suas credenciais corretamente
 */
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Template de Credenciais - Mercado Pago</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2196F3;
            border-bottom: 3px solid #2196F3;
            padding-bottom: 10px;
        }
        h2 {
            color: #333;
            margin-top: 30px;
        }
        .step {
            background: #f9f9f9;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .code-block {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 15px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            overflow-x: auto;
            margin: 10px 0;
        }
        .code-block code {
            color: #d4d4d4;
        }
        .highlight {
            background: #fff3cd;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
        }
        .copy-btn {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
            font-size: 14px;
        }
        .copy-btn:hover {
            background: #45a049;
        }
        .success {
            color: #4CAF50;
            font-weight: bold;
        }
        .warning {
            color: #ff9800;
            font-weight: bold;
        }
        .error {
            color: #f44336;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        table th {
            background: #2196F3;
            color: white;
        }
        .input-group {
            margin: 15px 0;
        }
        .input-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        .input-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }
        .button-group {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
        }
        .btn-primary {
            background: #2196F3;
            color: white;
        }
        .btn-primary:hover {
            background: #0b7dda;
        }
        .btn-secondary {
            background: #757575;
            color: white;
        }
        .btn-secondary:hover {
            background: #616161;
        }
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ff9800;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔑 Template de Credenciais - Mercado Pago</h1>

        <div class="info-box">
            <strong>ℹ️ Informação:</strong> Use esta página como referência para copiar/colar suas credenciais corretamente.
        </div>

        <h2>📋 Passo 1: Copiar do Painel do Mercado Pago</h2>
        <div class="step">
            <p><strong>1.</strong> Abra: <a href="https://www.mercadopago.com.br/developers/panel/" target="_blank">https://www.mercadopago.com.br/developers/panel/</a></p>
            <p><strong>2.</strong> Clique na aba <span class="highlight">PRUEBA</span> (para testes)</p>
            <p><strong>3.</strong> Copie o <span class="highlight">Access Token</span></p>
            <p><strong>4.</strong> Copie a <span class="highlight">Public Key</span></p>
        </div>

        <h2>📝 Passo 2: Cole Suas Credenciais Aqui</h2>

        <div class="input-group">
            <label for="access_token">🔒 Access Token (começa com TEST- ou APP_USR-)</label>
            <input type="text" id="access_token" placeholder="TEST-1234567890-987654321-abcdefgh ou APP_USR-..." />
            <small>Encontre em: https://www.mercadopago.com.br/developers/panel/</small>
        </div>

        <div class="input-group">
            <label for="public_key">🔓 Public Key (começa com TEST- ou APP_USR-)</label>
            <input type="text" id="public_key" placeholder="TEST-ca9ca659-1234-5678-9abc-def1234567 ou APP_USR-..." />
            <small>Encontre em: https://www.mercadopago.com.br/developers/panel/</small>
        </div>

        <h2>💻 Passo 3: Copie o Código para config_mercadopago.php</h2>

        <p>Clique no botão abaixo para gerar o código PHP com suas credenciais:</p>

        <div class="button-group">
            <button class="btn btn-primary" onclick="gerarCodigo()">🔄 Gerar Código PHP</button>
            <button class="btn btn-secondary" onclick="limpar()">🗑️ Limpar Campos</button>
        </div>

        <div class="code-block" id="codigo-gerado" style="display: none;">
            <code id="codigo-texto"></code>
            <button class="copy-btn" onclick="copiarCodigo()">📋 Copiar Código</button>
        </div>

        <h2>🔧 Passo 4: Editar config_mercadopago.php</h2>

        <div class="step">
            <p><strong>1.</strong> Abra o arquivo: <span class="highlight">config_mercadopago.php</span></p>
            <p><strong>2.</strong> Procure pelas linhas 16-17:</p>
            <div class="code-block">
                <code>define('MP_ACCESS_TOKEN', 'APP_USR-3237573864728549-...');<br/>
define('MP_PUBLIC_KEY', 'APP_USR-ca9ca659-...');</code>
            </div>
            <p><strong>3.</strong> Substitua pelo código gerado acima</p>
            <p><strong>4.</strong> Salve o arquivo</p>
        </div>

        <h2>🧪 Passo 5: Testar</h2>

        <div class="step">
            <p><strong>1.</strong> Limpe o cache: <span class="highlight">Ctrl+F5</span></p>
            <p><strong>2.</strong> Abra: <span class="highlight">gestao-diaria.php</span></p>
            <p><strong>3.</strong> Clique em: <span class="highlight">CONTRATAR AGORA</span></p>
            <p><strong>4.</strong> Use dados de teste:</p>
            <div style="margin-left: 20px;">
                <p>Número: <span class="highlight">4111111111111111</span></p>
                <p>Nome: <span class="highlight">APRO</span></p>
                <p>Vencimento: <span class="highlight">11/25</span></p>
                <p>CVV: <span class="highlight">123</span></p>
            </div>
        </div>

        <h2>📚 Referência - Dados de Teste</h2>

        <table>
            <tr>
                <th>Cenário</th>
                <th>Número</th>
                <th>Nome</th>
                <th>Vencimento</th>
                <th>CVV</th>
                <th>Resultado</th>
            </tr>
            <tr>
                <td>✅ Aprovado</td>
                <td>4111111111111111</td>
                <td>APRO</td>
                <td>11/25</td>
                <td>123</td>
                <td><span class="success">Pagamento Aprovado</span></td>
            </tr>
            <tr>
                <td>❌ Rejeitado</td>
                <td>5555555555554444</td>
                <td>REJECT</td>
                <td>11/25</td>
                <td>123</td>
                <td><span class="error">Pagamento Rejeitado</span></td>
            </tr>
            <tr>
                <td>⏳ Pendente</td>
                <td>4514053056938702</td>
                <td>PENDING</td>
                <td>11/25</td>
                <td>123</td>
                <td><span class="warning">Pagamento Pendente</span></td>
            </tr>
        </table>

        <div class="warning-box">
            <strong>⚠️ Importante:</strong> 
            <ul>
                <li>Use credenciais <span class="highlight">TEST-</span> para testar SEM gastar dinheiro</li>
                <li>Use credenciais <span class="highlight">APP_USR-</span> SOMENTE em produção</li>
                <li>Os dados de teste SOMENTE funcionam em ambiente SANDBOX</li>
                <li>Nunca compartilhe suas credenciais com ninguém</li>
            </ul>
        </div>

        <h2>✅ Checklist Final</h2>

        <div class="step">
            <input type="checkbox" id="check1" /> Acessei o painel do Mercado Pago<br/>
            <input type="checkbox" id="check2" /> Copiei meu Access Token<br/>
            <input type="checkbox" id="check3" /> Copiei minha Public Key<br/>
            <input type="checkbox" id="check4" /> Gerei o código PHP<br/>
            <input type="checkbox" id="check5" /> Editei config_mercadopago.php<br/>
            <input type="checkbox" id="check6" /> Limpei o cache do navegador<br/>
            <input type="checkbox" id="check7" /> Testei com "CONTRATAR AGORA"<br/>
            <input type="checkbox" id="check8" /> Sistema funciona! ✅<br/>
        </div>
    </div>

    <script>
        function gerarCodigo() {
            const token = document.getElementById('access_token').value.trim();
            const publicKey = document.getElementById('public_key').value.trim();

            if (!token || !publicKey) {
                alert('❌ Preencha os campos Access Token e Public Key!');
                return;
            }

            if (!token.startsWith('TEST-') && !token.startsWith('APP_USR-')) {
                alert('⚠️ Access Token deve começar com TEST- ou APP_USR-');
                return;
            }

            if (!publicKey.startsWith('TEST-') && !publicKey.startsWith('APP_USR-')) {
                alert('⚠️ Public Key deve começar com TEST- ou APP_USR-');
                return;
            }

            const codigo = `// ✅ CREDENCIAIS MERCADO PAGO (Atualizadas em ${new Date().toLocaleDateString('pt-BR')})
define('MP_ACCESS_TOKEN', '${token}');
define('MP_PUBLIC_KEY', '${publicKey}');`;

            document.getElementById('codigo-texto').textContent = codigo;
            document.getElementById('codigo-gerado').style.display = 'block';

            alert('✅ Código gerado com sucesso! Copie usando o botão abaixo.');
        }

        function copiarCodigo() {
            const codigo = document.getElementById('codigo-texto').textContent;
            navigator.clipboard.writeText(codigo).then(() => {
                alert('✅ Código copiado para a área de transferência!');
            });
        }

        function limpar() {
            document.getElementById('access_token').value = '';
            document.getElementById('public_key').value = '';
            document.getElementById('codigo-gerado').style.display = 'none';
        }
    </script>
</body>
</html>
