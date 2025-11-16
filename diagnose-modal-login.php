<?php
/**
 * 🔍 DIAGNÓSTICO DO LOGIN VIA MODAL
 * Simula o fluxo completo de login através do modal
 */

session_start();

echo "<h1>🔍 DIAGNÓSTICO: LOGIN VIA MODAL</h1>";
echo "<style>
    body { font-family: Arial; margin: 20px; background: #f5f5f5; }
    .test { background: white; padding: 20px; margin: 15px 0; border-radius: 8px; border-left: 4px solid #667eea; }
    .pass { border-left-color: #4caf50; background: #f1f8f4; }
    .fail { border-left-color: #f44336; background: #fef5f5; }
    .success { color: #4caf50; font-weight: bold; }
    .error { color: #f44336; font-weight: bold; }
    code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; }
    button { padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer; }
</style>";

// ✅ Test 1: Session already exists?
echo "<div class='test " . (isset($_SESSION['usuario_id']) ? 'pass' : '') . "'>";
echo "<h3>1️⃣ Sessão Anterior</h3>";
if (isset($_SESSION['usuario_id'])) {
    echo "<p class='success'>✅ Sessão ativa: Usuario ID " . $_SESSION['usuario_id'] . "</p>";
    echo "<p>Session ID: " . session_id() . "</p>";
} else {
    echo "<p class='error'>❌ Nenhuma sessão ativa</p>";
}
echo "</div>";

// ✅ Test 2: Login submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_login'])) {
    require_once __DIR__ . '/config.php';
    
    $email = $_POST['test_email'] ?? '';
    $senha = $_POST['test_senha'] ?? '';
    
    echo "<div class='test'>";
    echo "<h3>2️⃣ Simulando Login</h3>";
    
    if (!$email || !$senha) {
        echo "<p class='error'>❌ Email ou senha vazio</p>";
    } else {
        echo "<p>Email: <code>$email</code></p>";
        echo "<p>Testando autenticação...</p>";
        
        // Mesmo código do login-user.php
        $sql = "SELECT * FROM usuarios WHERE email = '$email'";
        $result = $conexao->query($sql);
        
        if ($result->num_rows > 0) {
            $usuario = $result->fetch_assoc();
            echo "<p class='success'>✅ Usuário encontrado: {$usuario['nome']}</p>";
            
            if (password_verify($senha, $usuario['senha'])) {
                echo "<p class='success'>✅ Senha correta!</p>";
                
                // Criar sessão
                $_SESSION['usuario_id'] = $usuario['id'];
                echo "<p><strong>Sessão criada com ID:</strong> " . $_SESSION['usuario_id'] . "</p>";
                echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
                
            } else {
                echo "<p class='error'>❌ Senha incorreta</p>";
            }
        } else {
            echo "<p class='error'>❌ Usuário não encontrado</p>";
        }
    }
    echo "</div>";
}

// ✅ Test 3: Verify session persistence
echo "<div class='test " . (isset($_SESSION['usuario_id']) ? 'pass' : '') . "'>";
echo "<h3>3️⃣ Verificação de Persistência</h3>";
if (isset($_SESSION['usuario_id'])) {
    echo "<p class='success'>✅ Sessão persistida com sucesso!</p>";
    echo "<p>Usuario ID: " . $_SESSION['usuario_id'] . "</p>";
    echo "<p>Session ID: " . session_id() . "</p>";
    echo "<p><a href='bot_aovivo.php'><button>Ir para Bot ao Vivo →</button></a></p>";
} else {
    echo "<p class='error'>❌ Sessão não foi criada/mantida</p>";
}
echo "</div>";

// ✅ Test 4: Simulate modal login POST
echo "<div class='test'>";
echo "<h3>4️⃣ Simular Login via Modal (AJAX)</h3>";
echo "<p>O modal envia um POST AJAX para <code>login-user.php</code> com <code>ajax=1</code></p>";
echo "<form method='POST'>";
echo "Email: <input type='email' name='test_email' placeholder='seu@email.com' required><br><br>";
echo "Senha: <input type='password' name='test_senha' placeholder='sua senha' required><br><br>";
echo "<input type='hidden' name='test_login' value='1'>";
echo "<button type='submit'>🔐 Testar Login</button>";
echo "</form>";
echo "</div>";

// ✅ Test 5: Fluxo esperado
echo "<div class='test'>";
echo "<h3>5️⃣ Fluxo Esperado</h3>";
echo "<ol>";
echo "<li>Usuário acessa página sem autenticação</li>";
echo "<li>Modal de login aparece</li>";
echo "<li>Usuário preenche email e senha</li>";
echo "<li>Clica em 'Acessar'</li>";
echo "<li>JavaScript faz POST AJAX para <code>login-user.php</code></li>";
echo "<li>Se sucesso, <code>login-user.php</code> retorna 'sucesso'</li>";
echo "<li>JavaScript recarrega a página com <code>location.reload(true)</code></li>";
echo "<li>Na recarga, <code>session_start()</code> restaura a sessão</li>";
echo "<li>Página verifica <code>\$_SESSION['usuario_id']</code></li>";
echo "<li>Se existe, mostra conteúdo protegido</li>";
echo "</ol>";
echo "</div>";

// ✅ Test 6: Verificar script no bot_aovivo.php
echo "<div class='test'>";
echo "<h3>6️⃣ Verificação do Script JavaScript</h3>";
echo "<p>O <code>bot_aovivo.php</code> deve ter a função <code>enviarFormularioLogin()</code> que:</p>";
echo "<ul>";
echo "<li>Faz POST para <code>login-user.php</code> com <code>ajax=1</code></li>";
echo "<li>Aguarda resposta 'sucesso'</li>";
echo "<li>Se sucesso: fecha modal e executa <code>location.reload(true)</code></li>";
echo "<li>Carrega os dados de sessão na recarga</li>";
echo "</ul>";
echo "</div>";

?>
