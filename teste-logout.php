<?php
/**
 * 🧪 TESTE DE LOGOUT
 * Verifica se o logout está funcionando corretamente
 */

require_once __DIR__ . '/session-config.php';
session_start();
require_once __DIR__ . '/config.php';

// Se for POST de teste, fazer logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['testar_logout'])) {
    // Limpar sessão completamente
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    
    session_destroy();
    
    echo "<p style='color: #3fb950; font-weight: bold;'>✅ Logout realizado com sucesso!</p>";
    echo "<p>Redirecionando em 2 segundos...</p>";
    echo "<script>setTimeout(() => location.href='home.php', 2000);</script>";
    exit;
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Teste de Logout</title>
    <style>
        body { font-family: Arial; margin: 50px; background: #0d1117; color: #c9d1d9; }
        .container { max-width: 600px; margin: 0 auto; }
        .box { background: #161b22; padding: 20px; border-radius: 6px; border: 1px solid #30363d; margin: 20px 0; }
        .success { color: #3fb950; }
        .fail { color: #f85149; }
        button { background: #238636; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold; }
        button:hover { background: #2ea043; }
        h1 { color: #58a6ff; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Teste de Logout</h1>
        
        <div class="box">
            <h2>Status da Sessão</h2>
            <?php
            if (isset($_SESSION['usuario_id'])) {
                echo "<p class='success'>✅ Usuario logado: ID " . $_SESSION['usuario_id'] . "</p>";
                echo "<p>Session ID: " . session_id() . "</p>";
                echo "<form method='POST'>";
                echo "<input type='hidden' name='testar_logout' value='1'>";
                echo "<button type='submit'>🚪 Fazer Logout de Teste</button>";
                echo "</form>";
            } else {
                echo "<p class='fail'>❌ Nenhum usuário logado</p>";
                echo "<p><a href='teste-final-sem-avisos.php' style='color: #58a6ff; text-decoration: underline;'>← Voltar para fazer login</a></p>";
            }
            ?>
        </div>
        
        <div class="box">
            <h2>📋 Como funciona o novo logout:</h2>
            <ol>
                <li>JavaScript confirma com usuário</li>
                <li>Limpa cache do navegador (se disponível)</li>
                <li>Limpa localStorage e sessionStorage</li>
                <li>Vai para logout.php com ?t=timestamp (força recarga)</li>
                <li>logout.php limpa $_SESSION array</li>
                <li>logout.php deleta cookie de sessão</li>
                <li>logout.php executa session_destroy()</li>
                <li>Redireciona para home.php sem permanecer logado</li>
            </ol>
        </div>
        
        <div class="box">
            <h2>🔍 Verificação Pós-Logout:</h2>
            <p>Após clicar em logout, você será redirecionado para home.php</p>
            <p>Se pressionar F5, deverá aparecer a tela sem autenticação</p>
            <p>Se der erro ou aparecer como conectado, o logout não funcionou corretamente</p>
        </div>
    </div>
</body>
</html>
