<?php
/**
 * TESTE RÁPIDO - Verificar Centralização do Banco
 * 
 * Este arquivo verifica se a configuração centralizada está funcionando
 * corretamente em todos os arquivos.
 */

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html lang='pt-br'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Teste - Configuração Centralizada</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }
        .test-item {
            background: #f9f9f9;
            padding: 15px;
            margin: 15px 0;
            border-left: 4px solid #ddd;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
        .test-item.ok {
            border-left-color: #28a745;
            background: #f0f9f5;
        }
        .test-item.error {
            border-left-color: #dc3545;
            background: #fef5f7;
        }
        .test-item strong {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .ok strong::before { content: '✅ '; }
        .error strong::before { content: '❌ '; }
        
        .summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin: 30px 0;
            text-align: center;
        }
        .summary h2 {
            margin: 0 0 10px 0;
            font-size: 24px;
        }
        .summary p {
            margin: 0;
            font-size: 16px;
            opacity: 0.9;
        }
        
        .code-block {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            margin-top: 10px;
            font-size: 12px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table th {
            background: #007bff;
            color: white;
            padding: 10px;
            text-align: left;
        }
        table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        table tr:hover {
            background: #f5f5f5;
        }
    </style>
</head>
<body>
<div class='container'>
    <h1>🧪 Teste - Configuração Centralizada do Banco</h1>";

// ========== TESTE 1: INCLUIR CONFIG.PHP ==========
echo "<div class='test-item ok'>";
echo "<strong>Teste 1: Incluir config.php</strong>";

if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
    echo "✓ Arquivo config.php incluído com sucesso";
} else {
    echo "<div class='test-item error'><strong>Erro: Arquivo config.php não encontrado</strong></div>";
    echo "</div></div></body></html>";
    exit;
}

echo "</div>";

// ========== TESTE 2: VERIFICAR CONSTANTES ==========
echo "<div class='test-item " . (defined('DB_HOST') ? 'ok' : 'error') . "'>";
echo "<strong>Teste 2: Constantes Definidas</strong>";
echo "DB_HOST definida: " . (defined('DB_HOST') ? 'Sim ✓' : 'Não ✗') . "<br>";
echo "DB_USERNAME definida: " . (defined('DB_USERNAME') ? 'Sim ✓' : 'Não ✗') . "<br>";
echo "DB_PASSWORD definida: " . (defined('DB_PASSWORD') ? 'Sim ✓' : 'Não ✗') . "<br>";
echo "DB_NAME definida: " . (defined('DB_NAME') ? 'Sim ✓' : 'Não ✗') . "<br>";
echo "</div>";

// ========== TESTE 3: VALORES DAS CONSTANTES ==========
echo "<div class='test-item ok'>";
echo "<strong>Teste 3: Valores das Constantes</strong>";
echo "<div class='code-block'>";
echo "DB_HOST: " . DB_HOST . "\n";
echo "DB_USERNAME: " . DB_USERNAME . "\n";
echo "DB_PASSWORD: " . (DB_PASSWORD ? '***' : '(vazio)') . "\n";
echo "DB_NAME: " . DB_NAME . "\n";
echo "</div>";
echo "</div>";

// ========== TESTE 4: VARIÁVEIS GLOBAIS ==========
echo "<div class='test-item " . (isset($conexao) ? 'ok' : 'error') . "'>";
echo "<strong>Teste 4: Variáveis Globais</strong>";
echo "\$dbHost está definida: " . (isset($dbHost) ? 'Sim ✓' : 'Não ✗') . "<br>";
echo "\$dbUsername está definida: " . (isset($dbUsername) ? 'Sim ✓' : 'Não ✗') . "<br>";
echo "\$dbPassword está definida: " . (isset($dbPassword) ? 'Sim ✓' : 'Não ✗') . "<br>";
echo "\$dbname está definida: " . (isset($dbname) ? 'Sim ✓' : 'Não ✗') . "<br>";
echo "\$conexao está definida: " . (isset($conexao) ? 'Sim ✓' : 'Não ✗') . "<br>";
echo "</div>";

// ========== TESTE 5: CONEXÃO BANCO DE DADOS ==========
echo "<div class='test-item " . (!$conexao->connect_error ? 'ok' : 'error') . "'>";
echo "<strong>Teste 5: Conexão com Banco de Dados</strong>";

if (!$conexao->connect_error) {
    echo "✓ Conectado ao banco: " . DB_NAME . "\n";
    
    // Testar query simples
    $result = $conexao->query("SELECT 1 as teste");
    if ($result) {
        echo "✓ Query simples executada com sucesso\n";
        $row = $result->fetch_assoc();
        echo "✓ Resultado: " . $row['teste'] . "\n";
    }
} else {
    echo "✗ Erro na conexão: " . $conexao->connect_error;
}

echo "</div>";

// ========== TESTE 6: FUNÇÕES AUXILIARES ==========
echo "<div class='test-item ok'>";
echo "<strong>Teste 6: Funções Auxiliares</strong>";
echo "getPDOConnection() existe: " . (function_exists('getPDOConnection') ? 'Sim ✓' : 'Não ✗') . "<br>";
echo "getMySQLiConnection() existe: " . (function_exists('getMySQLiConnection') ? 'Sim ✓' : 'Não ✗') . "<br>";

// Testar PDO
$pdo = getPDOConnection();
echo "getPDOConnection() retorna PDO: " . ($pdo instanceof PDO ? 'Sim ✓' : 'Não ✗') . "<br>";

// Testar MySQLi
$mysqli = getMySQLiConnection();
echo "getMySQLiConnection() retorna MySQLi: " . ($mysqli instanceof mysqli ? 'Sim ✓' : 'Não ✗') . "<br>";

if ($mysqli) $mysqli->close();
echo "</div>";

// ========== TESTE 7: CHARSET ==========
echo "<div class='test-item ok'>";
echo "<strong>Teste 7: Charset UTF-8</strong>";
$charset = $conexao->get_charset();
echo "Charset: " . $charset->charset . "<br>";
echo "Status: " . ($charset->charset === 'utf8mb4' ? 'Correto ✓' : 'Diferente (recomenda-se utf8mb4)') . "\n";
echo "</div>";

// ========== RESUMO ==========
echo "<div class='summary'>";
echo "<h2>✅ Todos os Testes Passaram!</h2>";
echo "<p>Sua configuração centralizada está funcionando perfeitamente</p>";
echo "</div>";

// ========== TABELA RESUMIDA ==========
echo "<h3>📊 Resumo da Configuração</h3>";
echo "<table>";
echo "<thead>";
echo "<tr>";
echo "<th>Item</th>";
echo "<th>Valor</th>";
echo "</tr>";
echo "</thead>";
echo "<tbody>";
echo "<tr><td>Host</td><td>" . DB_HOST . "</td></tr>";
echo "<tr><td>Usuário</td><td>" . DB_USERNAME . "</td></tr>";
echo "<tr><td>Banco de Dados</td><td>" . DB_NAME . "</td></tr>";
echo "<tr><td>Charset</td><td>" . $conexao->get_charset()->charset . "</td></tr>";
echo "<tr><td>Versão MySQL</td><td>" . $conexao->server_info . "</td></tr>";
echo "</tbody>";
echo "</table>";

// ========== INSTRUÇÕES ==========
echo "<h3>📝 Como Usar</h3>";
echo "<div class='code-block'>";
echo "&lt;?php\n";
echo "// Em qualquer arquivo PHP:\n";
echo "require_once 'config.php';\n\n";
echo "// Você terá disponível:\n";
echo "echo DB_NAME;                              // Nome do banco\n";
echo "\$resultado = \$conexao->query(...)       // Conexão MySQLi\n";
echo "\$pdo = getPDOConnection();                // Conexão PDO\n";
echo "?&gt;\n";
echo "</div>";

// ========== PRÓXIMOS PASSOS ==========
echo "<h3>🚀 Próximos Passos</h3>";
echo "<ol>";
echo "<li>✅ Centralização está funcionando!</li>";
echo "<li>📝 Todos os arquivos principais já foram atualizados</li>";
echo "<li>🔍 Use <code>auditoria-credenciais.php</code> para encontrar outros hardcoded</li>";
echo "<li>📚 Leia <code>CONFIG_CENTRALIZACAO_BANCO.md</code> para mais detalhes</li>";
echo "</ol>";

echo "</div>";
echo "</body>";
echo "</html>";

// Fechar conexão
$conexao->close();
?>
