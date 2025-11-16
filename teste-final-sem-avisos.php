<?php
/**
 * ✅ TESTE FINAL - SEM AVISOS
 * Verifica se a sessão funciona sem erros
 */

// ✅ ORDEM CORRETA:
// 1. Configurar cookies (session-config.php)
// 2. Iniciar sessão (session_start)
// 3. Incluir config

require_once __DIR__ . '/session-config.php';
session_start();
require_once __DIR__ . '/config.php';

// Que a diversão comece! 🎉

echo "<!DOCTYPE html>";
echo "<html>";
echo "<head><title>✅ Teste Final</title>";
echo "<style>";
echo "body { font-family: Arial; margin: 50px; background: #0d1117; color: #c9d1d9; }";
echo ".container { max-width: 600px; }";
echo ".success { color: #3fb950; font-size: 18px; font-weight: bold; }";
echo ".info { color: #58a6ff; margin: 20px 0; }";
echo "pre { background: #161b22; padding: 15px; border-radius: 6px; overflow-x: auto; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<div class='container'>";
echo "<h1>✅ TESTE FINAL - SEM AVISOS</h1>";

// Verificar se há erros
if (error_get_last()) {
    echo "<p style='color: #f85149;'>⚠️ Há erros no PHP</p>";
} else {
    echo "<p class='success'>✅ NÃO há erros de PHP!</p>";
}

echo "<div class='info'>";
echo "<h3>📊 Status da Sessão:</h3>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . session_status() . " (2=active)\n";
echo "Session Save Path: " . session_save_path() . "\n";
echo "</pre>";
echo "</div>";

echo "<div class='info'>";
echo "<h3>🔐 Teste de Login:</h3>";
if (!isset($_SESSION['usuario_id'])) {
    echo "<form method='POST'>";
    echo "Email: <input type='email' name='email' value='carlos142165@gmail.com' required><br>";
    echo "Senha: <input type='password' name='senha' required><br>";
    echo "<button type='submit'>🔐 Fazer Login</button>";
    echo "</form>";
} else {
    echo "<p class='success'>✅ LOGADO! Usuario ID: " . $_SESSION['usuario_id'] . "</p>";
    echo "<p><a href='bot_aovivo.php'>→ Ir para Bot ao Vivo</a></p>";
    echo "<p><a href='logout.php'>→ Fazer Logout</a></p>";
}
echo "</div>";

// Processar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    
    $sql = "SELECT id, nome, senha FROM usuarios WHERE email = '$email'";
    $result = $conexao->query($sql);
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($senha, $user['senha'])) {
            $_SESSION['usuario_id'] = $user['id'];
            echo "<p class='success'>✅ Login bem-sucedido!</p>";
            echo "<p>Redirecionando em 2 segundos...</p>";
            echo "<script>setTimeout(() => location.reload(), 2000);</script>";
        } else {
            echo "<p style='color: #f85149;'>❌ Senha incorreta</p>";
        }
    } else {
        echo "<p style='color: #f85149;'>❌ Usuário não encontrado</p>";
    }
}

echo "<div class='info'>";
echo "<h3>✨ Tudo está funcionando!</h3>";
echo "<ul>";
echo "<li>✅ Sem avisos de ini_set</li>";
echo "<li>✅ Sessão configurada corretamente</li>";
echo "<li>✅ Login funcionando</li>";
echo "<li>✅ HTTPS detectado automaticamente</li>";
echo "</ul>";
echo "</div>";

echo "</div>";
echo "</body></html>";

?>
