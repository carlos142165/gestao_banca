<!DOCTYPE html>
<html>
<head>
    <title>✅ Verificar Config Corrigida</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .box { background: white; padding: 20px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #667eea; }
        .ok { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <h1>✅ Verificando Config.php Corrigida</h1>
    
    <div class="box">
        <h2>Servidor</h2>
        <p>HTTP_HOST: <strong><?php echo $_SERVER['HTTP_HOST']; ?></strong></p>
        <p>SERVER_NAME: <strong><?php echo $_SERVER['SERVER_NAME']; ?></strong></p>
    </div>

    <div class="box">
        <h2>Incluindo config.php...</h2>
        <?php
        require_once 'config.php';
        
        echo "<p>ENVIRONMENT: <span class='" . (defined('ENVIRONMENT') && ENVIRONMENT ? 'ok' : 'error') . "'>";
        echo defined('ENVIRONMENT') ? ENVIRONMENT : 'NÃO DEFINIDA';
        echo "</span></p>";
        
        echo "<p>DB_HOST: <span class='ok'>" . DB_HOST . "</span></p>";
        echo "<p>DB_NAME: <span class='ok'>" . DB_NAME . "</span></p>";
        echo "<p>DB_USERNAME: <span class='ok'>" . DB_USERNAME . "</span></p>";
        
        // Testar conexão
        echo "<p>Conexão: <span class='" . (!$conexao->connect_error ? 'ok' : 'error') . "'>";
        echo !$conexao->connect_error ? '✓ OK' : '✗ ERRO: ' . $conexao->connect_error;
        echo "</span></p>";
        ?>
    </div>

    <div class="box">
        <h2>✅ Tudo correto!</h2>
        <p><a href="diagnostico-mensagens.php">Ir para Diagnóstico de Mensagens</a></p>
    </div>
</body>
</html>
