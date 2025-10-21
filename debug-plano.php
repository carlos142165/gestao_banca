<?php
/**
 * DEBUG RAW - Ver resposta bruta da API
 */

ob_start();

// Capturar tudo que será impresso
echo "=== INICIANDO DEBUG ===\n\n";

echo "1. Verificando se config.php existe...\n";
if (file_exists('config.php')) {
    echo "   ✅ config.php encontrado\n";
} else {
    echo "   ❌ config.php NÃO encontrado\n";
}

echo "\n2. Carregando config.php...\n";
try {
    require_once 'config.php';
    echo "   ✅ config.php carregado\n";
    echo "   Conexão: " . (isset($conn) ? "✅ Existe" : "❌ Não existe") . "\n";
} catch (Exception $e) {
    echo "   ❌ Erro ao carregar config.php: " . $e->getMessage() . "\n";
}

echo "\n3. Carregando carregar_sessao.php...\n";
try {
    require_once 'carregar_sessao.php';
    echo "   ✅ carregar_sessao.php carregado\n";
} catch (Exception $e) {
    echo "   ❌ Erro ao carregar carregar_sessao.php: " . $e->getMessage() . "\n";
}

echo "\n4. Verificando sessão...\n";
echo "   \$_SESSION['usuario_id']: " . (isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : "NÃO DEFINIDO") . "\n";

echo "\n5. Testando query...\n";
if (isset($conexao) && isset($_SESSION['usuario_id'])) {
    $id_usuario = $_SESSION['usuario_id'];
    $query = "SELECT id, email, id_plano FROM usuarios WHERE id = ?";
    
    $stmt = $conexao->prepare($query);
    if (!$stmt) {
        echo "   ❌ Erro ao preparar query: " . $conexao->error . "\n";
    } else {
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $result = $stmt->get_result();
        $usuario = $result->fetch_assoc();
        $stmt->close();
        
        if ($usuario) {
            echo "   ✅ Usuário encontrado:\n";
            echo "      ID: " . $usuario['id'] . "\n";
            echo "      Email: " . $usuario['email'] . "\n";
            echo "      ID Plano: " . $usuario['id_plano'] . "\n";
        } else {
            echo "   ❌ Usuário NÃO encontrado\n";
        }
    }
} else {
    echo "   ⚠️ Não pode testar query (conexão ou sessão não disponível)\n";
    echo "      \$conexao: " . (isset($conexao) ? "✅ Definida" : "❌ Não definida") . "\n";
    echo "      \$_SESSION['usuario_id']: " . (isset($_SESSION['usuario_id']) ? "✅ " . $_SESSION['usuario_id'] : "❌ Não definida") . "\n";
}

echo "\n=== FIM DO DEBUG ===\n";

$debug_output = ob_get_clean();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Debug</title>
    <style>
        body {
            font-family: monospace;
            background: #1e1e1e;
            color: #00ff00;
            padding: 20px;
            white-space: pre-wrap;
        }
        .error { color: #ff0000; }
        .success { color: #00ff00; }
        .warning { color: #ffaa00; }
    </style>
</head>
<body>
<?php echo htmlspecialchars($debug_output); ?>
</body>
</html>
