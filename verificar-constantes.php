<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>📋 Logs do Erro</title>
    <style>
        body {
            font-family: monospace;
            background: #1e1e1e;
            color: #00ff00;
            padding: 20px;
            line-height: 1.6;
        }
        .log-box {
            background: #000;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            max-height: 600px;
            overflow-y: auto;
        }
        .error { color: #ff6b6b; }
        .success { color: #51cf66; }
    </style>
</head>
<body>
    <h1>📋 Verificar Constantes</h1>
    
    <div class="log-box">
<?php
    echo "<h2>Constantes Definidas (USER):</h2>\n";
    $constants = get_defined_constants(true);
    if (isset($constants['user'])) {
        foreach ($constants['user'] as $name => $value) {
            if (strpos(strtoupper($name), 'DB_') === 0 || strpos(strtoupper($name), 'ENVIRONMENT') === 0 || strpos(strtoupper($name), 'TELEGRAM') === 0) {
                $masked_value = is_string($value) && strlen($value) > 20 ? substr($value, 0, 10) . '...' : $value;
                echo "<span class='success'>✓</span> $name = " . htmlspecialchars($masked_value) . "\n";
            }
        }
    }
    
    echo "\n<h2>Variáveis do Servidor:</h2>\n";
    echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'N/A') . "\n";
    echo "SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'N/A') . "\n";
    echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n";
    
    echo "\n<h2>PHP Info:</h2>\n";
    echo "PHP Version: " . phpversion() . "\n";
    echo "OS: " . php_uname() . "\n";
    
    echo "\n<h2>Incluindo config.php...</h2>\n";
    ob_start();
    include 'config.php';
    $output = ob_get_clean();
    
    if (!empty($output)) {
        echo "<span class='error'>SAÍDA:</span>\n" . htmlspecialchars($output) . "\n";
    }
    
    echo "\n<h2>Após incluir config.php:</h2>\n";
    echo "ENVIRONMENT definida? " . (defined('ENVIRONMENT') ? "<span class='success'>SIM - " . ENVIRONMENT . "</span>" : "<span class='error'>NÃO</span>") . "\n";
    echo "DB_HOST definida? " . (defined('DB_HOST') ? "<span class='success'>SIM - " . DB_HOST . "</span>" : "<span class='error'>NÃO</span>") . "\n";
    echo "DB_NAME definida? " . (defined('DB_NAME') ? "<span class='success'>SIM - " . DB_NAME . "</span>" : "<span class='error'>NÃO</span>") . "\n";
?>
    </div>
</body>
</html>
