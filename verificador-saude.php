<?php
/**
 * VERIFICADOR DE SAÚDE DO SISTEMA
 * ================================
 * Este arquivo verifica se todos os componentes necessários estão funcionando
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificador de Saúde - Sistema de Planos</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 30px;
        }
        h1 { color: #2c3e50; margin-bottom: 30px; text-align: center; }
        .check-group {
            margin-bottom: 25px;
            padding: 20px;
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            border-radius: 5px;
        }
        .check-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 10px;
            margin-bottom: 10px;
            background: white;
            border-radius: 5px;
        }
        .check-item:last-child { margin-bottom: 0; }
        .status-icon {
            font-size: 24px;
            min-width: 30px;
            text-align: center;
        }
        .status-ok { color: #27ae60; }
        .status-error { color: #e74c3c; }
        .status-warning { color: #f39c12; }
        .details {
            margin-left: 45px;
            color: #555;
            font-size: 13px;
        }
        .code-block {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 10px;
            border-radius: 3px;
            margin-top: 5px;
            font-family: monospace;
            overflow-x: auto;
            font-size: 12px;
        }
        .summary {
            background: #ecf0f1;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
            text-align: center;
        }
        .summary.success { background: #d4edda; color: #155724; }
        .summary.error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏥 Verificador de Saúde - Sistema de Planos</h1>

        <?php
        session_start();
        
        $checks = [
            'database' => false,
            'config' => false,
            'config_mp' => false,
            'functions' => [],
            'endpoints' => [],
            'js_files' => [],
            'php_files' => []
        ];
        
        // 1. Verificar Banco de Dados
        echo '<div class="check-group"><h2>🗄️ Banco de Dados</h2>';
        
        require_once 'config.php';
        
        if ($conexao && !$conexao->connect_error) {
            echo '<div class="check-item"><span class="status-icon status-ok">✅</span>';
            echo '<div>Conexão com banco de dados OK</div></div>';
            $checks['database'] = true;
            
            // Verificar tabelas
            $tabelas = ['usuarios', 'planos', 'mentores', 'valor_mentores'];
            foreach ($tabelas as $tabela) {
                $result = $conexao->query("SHOW TABLES LIKE '$tabela'");
                if ($result && $result->num_rows > 0) {
                    echo '<div class="check-item"><span class="status-icon status-ok">✅</span>';
                    echo "<div>Tabela <code>$tabela</code> existe</div></div>";
                } else {
                    echo '<div class="check-item"><span class="status-icon status-error">❌</span>';
                    echo "<div>Tabela <code>$tabela</code> NÃO encontrada</div></div>";
                }
            }
        } else {
            echo '<div class="check-item"><span class="status-icon status-error">❌</span>';
            echo '<div>Erro na conexão: ' . ($conexao ? $conexao->connect_error : 'Objeto nulo') . '</div></div>';
        }
        
        echo '</div>';
        
        // 2. Verificar Arquivos de Configuração
        echo '<div class="check-group"><h2>⚙️ Arquivos de Configuração</h2>';
        
        $config_files = [
            'config.php' => '/config.php',
            'config_mercadopago.php' => '/config_mercadopago.php',
            'carregar_sessao.php' => '/carregar_sessao.php'
        ];
        
        foreach ($config_files as $name => $path) {
            $fullPath = __DIR__ . $path;
            if (file_exists($fullPath)) {
                echo '<div class="check-item"><span class="status-icon status-ok">✅</span>';
                echo "<div><code>$name</code> encontrado</div></div>";
                
                // Verificar conteúdo
                $content = file_get_contents($fullPath);
                if ($name === 'config_mercadopago.php') {
                    $global_conexao_count = substr_count($content, 'global $conexao');
                    $global_conn_count = substr_count($content, 'global $conn');
                    
                    if ($global_conn_count === 0) {
                        echo '<div class="check-item" style="margin-left: 45px;"><span class="status-icon status-ok">✅</span>';
                        echo "<div>Nenhuma referência a <code>\$conn</code> encontrada</div></div>";
                    } else {
                        echo '<div class="check-item" style="margin-left: 45px;"><span class="status-icon status-error">❌</span>';
                        echo "<div>AINDA HÁ $global_conn_count referência(s) a <code>\$conn</code> (deveria ser 0)</div></div>";
                    }
                    
                    echo '<div class="check-item" style="margin-left: 45px;"><span class="status-icon status-ok">✅</span>';
                    echo "<div>$global_conexao_count referência(s) a <code>\$conexao</code> encontrada(s)</div></div>";
                }
            } else {
                echo '<div class="check-item"><span class="status-icon status-error">❌</span>';
                echo "<div><code>$name</code> NÃO encontrado em $fullPath</div></div>";
            }
        }
        
        echo '</div>';
        
        // 3. Verificar Endpoints PHP
        echo '<div class="check-group"><h2>🔌 Endpoints PHP</h2>';
        
        $endpoints = [
            'verificar-limite.php' => 'Verificação de limite',
            'obter-planos.php' => 'Obtenção de planos',
            'obter-plano-usuario.php' => 'Plano do usuário',
            'obter-dados-usuario.php' => 'Dados do usuário'
        ];
        
        foreach ($endpoints as $endpoint => $desc) {
            $fullPath = __DIR__ . '/' . $endpoint;
            if (file_exists($fullPath)) {
                echo '<div class="check-item"><span class="status-icon status-ok">✅</span>';
                echo "<div><code>$endpoint</code> - $desc</div></div>";
            } else {
                echo '<div class="check-item"><span class="status-icon status-error">❌</span>';
                echo "<div><code>$endpoint</code> NÃO encontrado</div></div>";
            }
        }
        
        echo '</div>';
        
        // 4. Verificar Arquivos JavaScript
        echo '<div class="check-group"><h2>📜 Arquivos JavaScript</h2>';
        
        $js_files = [
            'js/plano-manager.js' => 'Gerenciador de planos',
            'js/mostrar-plano.js' => 'Exibição de plano',
            'js/script-gestao-diaria.js' => 'Script principal'
        ];
        
        foreach ($js_files as $file => $desc) {
            $fullPath = __DIR__ . '/' . $file;
            if (file_exists($fullPath)) {
                echo '<div class="check-item"><span class="status-icon status-ok">✅</span>';
                echo "<div><code>$file</code> - $desc</div></div>";
                
                // Verificar se contém função esperada
                if ($file === 'js/plano-manager.js') {
                    $content = file_get_contents($fullPath);
                    if (strpos($content, 'verificarEExibirPlanos') !== false) {
                        echo '<div class="check-item" style="margin-left: 45px;"><span class="status-icon status-ok">✅</span>';
                        echo '<div>Função <code>verificarEExibirPlanos()</code> encontrada</div></div>';
                    }
                }
            } else {
                echo '<div class="check-item"><span class="status-icon status-error">❌</span>';
                echo "<div><code>$file</code> NÃO encontrado</div></div>";
            }
        }
        
        echo '</div>';
        
        // 5. Verificar Arquivos de Teste
        echo '<div class="check-group"><h2>🧪 Arquivos de Teste</h2>';
        
        $test_files = [
            'teste-validacao-completa.php' => 'Teste completo do sistema',
            'teste-api-verificacao.php' => 'Teste de API',
            'validador-sistema.php' => 'Dashboard de validação',
            'verificador-saude.php' => 'Este arquivo'
        ];
        
        foreach ($test_files as $file => $desc) {
            $fullPath = __DIR__ . '/' . $file;
            if (file_exists($fullPath)) {
                echo '<div class="check-item"><span class="status-icon status-ok">✅</span>';
                echo "<div><code>$file</code> - $desc</div></div>";
            } else {
                echo '<div class="check-item"><span class="status-icon status-warning">⚠️</span>';
                echo "<div><code>$file</code> não encontrado (opcional)</div></div>";
            }
        }
        
        echo '</div>';
        
        // 6. Teste Rápido de Funções
        echo '<div class="check-group"><h2>🔧 Teste de Funções</h2>';
        
        require_once 'config_mercadopago.php';
        
        if (function_exists('MercadoPagoManager') || class_exists('MercadoPagoManager')) {
            echo '<div class="check-item"><span class="status-icon status-ok">✅</span>';
            echo '<div>Classe <code>MercadoPagoManager</code> carregada</div></div>';
            
            // Testar métodos
            $methods = [
                'verificarLimiteMentores',
                'verificarLimiteEntradas',
                'obterPlanoAtual',
                'obterPlanoGratuito'
            ];
            
            foreach ($methods as $method) {
                if (method_exists('MercadoPagoManager', $method)) {
                    echo '<div class="check-item" style="margin-left: 45px;"><span class="status-icon status-ok">✅</span>';
                    echo "<div>Método <code>$method()</code> existe</div></div>";
                } else {
                    echo '<div class="check-item" style="margin-left: 45px;"><span class="status-icon status-error">❌</span>';
                    echo "<div>Método <code>$method()</code> NÃO encontrado</div></div>";
                }
            }
        }
        
        echo '</div>';
        
        // 7. Resumo
        echo '<div class="check-group"><h2>📊 Resumo</h2>';
        
        $allOk = $checks['database'] && file_exists(__DIR__ . '/config_mercadopago.php');
        
        if ($allOk) {
            echo '<div class="summary success">';
            echo '<h3>✅ Sistema está SAUDÁVEL!</h3>';
            echo '<p>Todos os componentes necessários foram encontrados e estão funcionando.</p>';
            echo '</div>';
        } else {
            echo '<div class="summary error">';
            echo '<h3>❌ Problema detectado</h3>';
            echo '<p>Verifique os itens com ❌ acima para resolver os problemas.</p>';
            echo '</div>';
        }
        
        echo '<div style="margin-top: 20px;">';
        echo '<h3>📚 Links Úteis:</h3>';
        echo '<ul style="margin-left: 20px; margin-top: 10px;">';
        echo '<li><a href="validador-sistema.php">Dashboard de Testes</a></li>';
        echo '<li><a href="teste-validacao-completa.php">Teste Completo do Sistema</a></li>';
        echo '<li><a href="gestao-diaria.php">Página Principal</a></li>';
        echo '</ul>';
        echo '</div>';
        
        echo '</div>';
        ?>
    </div>
</body>
</html>
