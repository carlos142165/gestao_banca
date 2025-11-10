<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧪 Diagnóstico Frontend - Carregamento de Mensagens</title>
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
        .info-box {
            background: #f5f7fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
        .ok { color: #27ae60; font-weight: bold; }
        .error { color: #e74c3c; font-weight: bold; }
        .warning { color: #f39c12; font-weight: bold; }
        .code-box {
            background: #f5f7fa;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            margin: 15px 0;
            overflow-x: auto;
            font-size: 12px;
        }
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
        }
        table td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }
        .status-ok { background: #d4edda; }
        .status-error { background: #f8d7da; }
    </style>
</head>
<body>
    <div class="container">
        <div class="box">
            <h2>🧪 Diagnóstico - Carregamento Frontend de Mensagens</h2>
            <p>Verifica se o frontend consegue carregar as mensagens do banco</p>
        </div>

        <?php
        require_once 'config.php';

        // 1. Verificar arquivo que carrega mensagens
        echo '<div class="box">';
        echo '<h2>1️⃣ Arquivo que Carrega Mensagens</h2>';

        $arquivo_carregar = __DIR__ . '/api/carregar-mensagens-banco.php';
        if (file_exists($arquivo_carregar)) {
            echo '<div class="info-box">';
            echo '<span class="ok">✅ Arquivo existe: api/carregar-mensagens-banco.php</span>';
            echo '</div>';

            // Mostrar conteúdo do arquivo
            echo '<strong>Conteúdo do arquivo:</strong>';
            $conteudo = file_get_contents($arquivo_carregar);
            echo '<div class="code-box">';
            echo htmlspecialchars(substr($conteudo, 0, 500));
            echo '</div>';
        } else {
            echo '<div class="info-box">';
            echo '<span class="error">❌ Arquivo NÃO encontrado: api/carregar-mensagens-banco.php</span>';
            echo '</div>';
        }
        echo '</div>';

        // 2. Testar carregamento de mensagens via API
        echo '<div class="box">';
        echo '<h2>2️⃣ Teste de Carregamento via API</h2>';

        echo '<p>Testando requisição para: <code>api/carregar-mensagens-banco.php</code></p>';
        
        $url_api = 'api/carregar-mensagens-banco.php';
        $url_completa = 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . '/gestao/gestao_banca/' . $url_api;

        echo '<div class="info-box">';
        echo '<strong>URL completa:</strong><br>';
        echo htmlspecialchars($url_completa);
        echo '</div>';

        // Tentar fazer requisição
        echo '<button onclick="testarAPI()" style="padding: 12px 24px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; font-size: 14px;">🧪 Testar API</button>';

        echo '</div>';

        // 3. Verificar bot_aovivo.php
        echo '<div class="box">';
        echo '<h2>3️⃣ Arquivo Frontend: bot_aovivo.php</h2>';

        $arquivo_bot = __DIR__ . '/bot_aovivo.php';
        if (file_exists($arquivo_bot)) {
            echo '<div class="info-box">';
            echo '<span class="ok">✅ Arquivo existe</span><br>';
            echo 'Tamanho: ' . filesize($arquivo_bot) . ' bytes';
            echo '</div>';

            // Procurar por referências ao carregamento de mensagens
            $conteudo_bot = file_get_contents($arquivo_bot);
            
            $mencoes = [];
            if (strpos($conteudo_bot, 'carregar-mensagens') !== false) {
                $mencoes[] = '✓ Referencia "carregar-mensagens"';
            }
            if (strpos($conteudo_bot, 'fetch') !== false) {
                $mencoes[] = '✓ Usa fetch() para requisições';
            }
            if (strpos($conteudo_bot, 'api') !== false) {
                $mencoes[] = '✓ Referencia "api"';
            }
            if (preg_match('/setInterval|setTimeout/', $conteudo_bot)) {
                $mencoes[] = '✓ Usa polling/atualização automática';
            }

            if (!empty($mencoes)) {
                echo '<strong>Detectado no arquivo:</strong>';
                foreach ($mencoes as $mencao) {
                    echo '<div class="info-box">' . $mencao . '</div>';
                }
            }
        } else {
            echo '<div class="info-box">';
            echo '<span class="error">❌ Arquivo NÃO encontrado: bot_aovivo.php</span>';
            echo '</div>';
        }
        echo '</div>';

        // 4. Testar Query SQL diretamente
        echo '<div class="box">';
        echo '<h2>4️⃣ Dados no Banco (Query Direta)</h2>';

        $query = "SELECT id, titulo, time_1, time_2, data_criacao, tipo_aposta FROM bote ORDER BY data_criacao DESC LIMIT 5";
        $result = $conexao->query($query);

        if ($result && $result->num_rows > 0) {
            echo '<strong>Últimas 5 mensagens do banco:</strong>';
            echo '<table>';
            echo '<tr><th>ID</th><th>Título</th><th>Time 1</th><th>Time 2</th><th>Data</th><th>Tipo</th></tr>';
            while ($row = $result->fetch_assoc()) {
                echo '<tr class="status-ok">';
                echo '<td>' . $row['id'] . '</td>';
                echo '<td>' . substr($row['titulo'], 0, 40) . '</td>';
                echo '<td>' . $row['time_1'] . '</td>';
                echo '<td>' . $row['time_2'] . '</td>';
                echo '<td>' . $row['data_criacao'] . '</td>';
                echo '<td>' . $row['tipo_aposta'] . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo '<span class="error">Nenhuma mensagem encontrada</span>';
        }
        echo '</div>';

        // 5. Verificar arquivo JSON
        echo '<div class="box">';
        echo '<h2>5️⃣ Teste de Resposta JSON</h2>';

        echo '<p>Se a API retornar um arquivo JSON válido:</p>';
        echo '<div class="code-box">';
        echo 'Esperado formato: {<br>';
        echo '  "status": "success",<br>';
        echo '  "data": [<br>';
        echo '    { "id": 161, "titulo": "...", "time_1": "...", ... },<br>';
        echo '    { "id": 160, "titulo": "...", "time_1": "...", ... }<br>';
        echo '  ]<br>';
        echo '}';
        echo '</div>';

        echo '<button onclick="testarJSON()" style="padding: 12px 24px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; font-size: 14px;">🧪 Testar JSON</button>';

        echo '</div>';

        // 6. Instruções
        echo '<div class="box">';
        echo '<h2>📝 Próximos Passos</h2>';

        echo '<ol style="margin-left: 20px; color: #666;">';
        echo '<li><strong>Clique em "Testar API"</strong> acima para ver se a API retorna dados</li>';
        echo '<li><strong>Verifique o console do navegador (F12)</strong> para erros JavaScript</li>';
        echo '<li><strong>Se tiver erro CORS</strong>, o servidor pode estar bloqueando a requisição</li>';
        echo '<li><strong>Se não tiver erro CORS</strong>, mas ainda não aparecer no site, o problema é no JavaScript de bot_aovivo.php</li>';
        echo '</ol>';

        echo '<div class="info-box" style="margin-top: 20px;">';
        echo '<strong>💡 Dica:</strong><br>';
        echo 'Abra o DevTools (F12) → Network → recarregue bot_aovivo.php<br>';
        echo 'Procure por requisições para "carregar-mensagens-banco.php"<br>';
        echo 'Verifique se a requisição retorna status 200 e dados JSON válidos';
        echo '</div>';

        echo '</div>';
        ?>
    </div>

    <script>
        function testarAPI() {
            const url = 'api/carregar-mensagens-banco.php';
            console.log('Testando API: ' + url);
            
            fetch(url)
                .then(response => {
                    console.log('Status:', response.status);
                    console.log('Content-Type:', response.headers.get('content-type'));
                    return response.json();
                })
                .then(data => {
                    console.log('Dados recebidos:', data);
                    alert('✅ API respondeu!\n\n' + JSON.stringify(data, null, 2));
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('❌ Erro ao chamar API:\n\n' + error.message);
                });
        }

        function testarJSON() {
            const url = 'api/carregar-mensagens-banco.php';
            
            fetch(url)
                .then(response => response.text())
                .then(text => {
                    console.log('Resposta bruta:', text);
                    const json = JSON.parse(text);
                    console.log('JSON válido!', json);
                    alert('✅ JSON válido!\n\nTotal de mensagens: ' + (json.data ? json.data.length : 0));
                })
                .catch(error => {
                    alert('❌ Erro ao processar JSON:\n\n' + error.message);
                });
        }
    </script>
</body>
</html>
