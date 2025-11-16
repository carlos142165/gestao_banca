<?php
/**
 * 🧪 TESTE DIRETO DO login-user.php
 * 
 * Simula exatamente o que o modal faz:
 * POST email + senha + ajax=1
 */

session_start();

// Se for POST, simular login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['testar_login'])) {
    // Incluir config DEPOIS de session_start()
    require_once __DIR__ . '/config.php';
    
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    
    echo "<pre>";
    echo "📡 TESTANDO LOGIN\n";
    echo "Email: $email\n";
    echo "Senha: " . str_repeat("*", strlen($senha)) . "\n";
    echo "Session ID: " . session_id() . "\n";
    echo "\n";
    
    // Mesmo código do login-user.php
    $sql = "SELECT * FROM usuarios WHERE email = '$email'";
    $result = $conexao->query($sql);
    
    if ($result->num_rows > 0) {
        $usuario = $result->fetch_assoc();
        echo "✅ Usuário encontrado: {$usuario['nome']}\n";
        
        if (password_verify($senha, $usuario['senha'])) {
            $_SESSION['usuario_id'] = $usuario['id'];
            session_write_close();
            
            echo "✅ Senha correta!\n";
            echo "✅ Session criada com ID: {$usuario['id']}\n";
            echo "✅ Response: sucesso\n";
            echo "\nPróximos passos:\n";
            echo "1. JavaScript fecha modal\n";
            echo "2. JavaScript executa location.reload(true)\n";
            echo "3. Página recarrega e busca \$_SESSION['usuario_id']\n";
            echo "4. Se existir, mostra conteúdo\n";
            
        } else {
            echo "❌ Senha Incorreta\n";
        }
    } else {
        echo "❌ E-mail Não Cadastrado\n";
    }
    
    echo "</pre>";
    exit;
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Teste Login-User</title>
    <style>
        body { font-family: Arial; margin: 50px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; max-width: 400px; }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #667eea; color: white; border: none; border-radius: 4px; cursor: pointer; }
        pre { background: #f0f0f0; padding: 15px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Teste: login-user.php</h1>
        <p>Simula exatamente o que o modal de login faz</p>
        
        <form method="POST">
            <input type="hidden" name="testar_login" value="1">
            <input type="email" name="email" placeholder="Email" required value="carlos142165@gmail.com">
            <input type="password" name="senha" placeholder="Senha" required>
            <button type="submit">🔐 Testar Login</button>
        </form>
        
        <hr>
        <h3>O que este teste faz:</h3>
        <ol>
            <li>Você preenche email e senha</li>
            <li>Clica em "Testar Login"</li>
            <li>Este script simula o POST que o modal faz</li>
            <li>Chama a mesma lógica do login-user.php</li>
            <li>Se funcionar, mostra "✅ sucesso"</li>
            <li>Se não funcionar, mostra o erro</li>
        </ol>
    </div>
</body>
</html>
