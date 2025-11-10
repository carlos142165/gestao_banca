<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>🔍 Debug Config.php</title>
    <style>
        body {
            font-family: monospace;
            background: #1e1e1e;
            color: #00ff00;
            padding: 20px;
            line-height: 1.6;
        }
        pre { background: #000; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .ok { color: #51cf66; }
        .error { color: #ff6b6b; }
    </style>
</head>
<body>
    <h1>🔍 Debug - Verificando config.php</h1>
    
    <?php
    echo "<pre>";
    echo "=== VARIÁVEIS DO SERVIDOR ===\n";
    echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'N/A') . "\n";
    echo "SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'N/A') . "\n";
    echo "\n";

    $httpHost = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
    $serverName = $_SERVER['SERVER_NAME'] ?? '';

    echo "=== APÓS ATRIBUIÇÃO ===\n";
    echo "\$httpHost = '$httpHost'\n";
    echo "\$serverName = '$serverName'\n";
    echo "\n";

    $isLocalhost = (
        $httpHost === 'localhost' ||
        $httpHost === '127.0.0.1' ||
        strpos($httpHost, 'localhost:') === 0 ||
        $serverName === 'localhost' ||
        php_uname('n') === 'localhost'
    );

    echo "=== VERIFICAÇÕES ===\n";
    echo "É localhost? " . ($isLocalhost ? "SIM" : "NÃO") . "\n";
    
    $isProduction = !$isLocalhost;
    echo "É produção? " . ($isProduction ? "SIM" : "NÃO") . "\n";
    echo "\n";

    echo "=== DEFININDO CONSTANTES ===\n";
    
    if ($isLocalhost) {
        echo "Definindo para: LOCAL\n";
        define('DB_HOST', 'localhost');
        define('DB_USERNAME', 'root');
        define('DB_PASSWORD', '');
        define('DB_NAME', 'formulario-carlos');
        define('ENVIRONMENT', 'local');
    } else if ($isProduction) {
        echo "Definindo para: PRODUCTION\n";
        define('DB_HOST', '127.0.0.1');
        define('DB_USERNAME', 'u857325944_formu');
        define('DB_PASSWORD', 'JkF4B7N1');
        define('DB_NAME', 'u857325944_formu');
        define('ENVIRONMENT', 'production');
    } else {
        echo "Definindo para: UNKNOWN\n";
        define('DB_HOST', '127.0.0.1');
        define('DB_USERNAME', 'u857325944_formu');
        define('DB_PASSWORD', 'JkF4B7N1');
        define('DB_NAME', 'u857325944_formu');
        define('ENVIRONMENT', 'unknown');
    }
    
    echo "\n=== CONSTANTES DEFINIDAS ===\n";
    echo "ENVIRONMENT: " . (defined('ENVIRONMENT') ? ENVIRONMENT : '<span class="error">NÃO DEFINIDA</span>') . "\n";
    echo "DB_HOST: " . DB_HOST . "\n";
    echo "DB_NAME: " . DB_NAME . "\n";
    echo "</pre>";
    ?>

    <h2>Agora testando config.php real...</h2>
    <pre>
<?php
    echo "=== INCLUINDO CONFIG.PHP ===\n";
    require_once 'config.php';
    
    echo "Após include:\n";
    echo "ENVIRONMENT: " . (defined('ENVIRONMENT') ? ENVIRONMENT : '<span class="error">NÃO DEFINIDA</span>') . "\n";
    echo "DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'NÃO DEFINIDA') . "\n";
    echo "DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'NÃO DEFINIDA') . "\n";
?>
    </pre>
</body>
</html>
