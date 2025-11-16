<?php
/**
 * 🎯 TESTE FINAL INTEGRADO
 * Verifica cada passo do login com detalhes técnicos
 */

session_start();

// Configurações de DEBUG
ini_set('display_errors', 1);
error_reporting(E_ALL);

$log = [];

// 📝 Log inicial
$log[] = "=== INICIANDO TESTE ===";
$log[] = "Session ID: " . session_id();
$log[] = "Session Status: " . session_status() . " (0=disabled, 1=none, 2=active)";
$log[] = "Session Save Path: " . session_save_path();
$log[] = "PHP Session Name: " . session_name();
$log[] = "Time: " . date('Y-m-d H:i:s');

// 🔑 Verificar cookies
$log[] = "\n=== COOKIES ===";
if (headers_sent()) {
    $log[] = "⚠️ Headers já foram enviados!";
} else {
    $log[] = "✅ Headers ainda não foram enviados";
}
$log[] = "Cookie path (padrão): /";
$log[] = "Cookie domain: " . ($_SERVER['HTTP_HOST'] ?? 'não definido');

// 📋 Processar POST se enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fazer_login'])) {
    $log[] = "\n=== PROCESSANDO LOGIN ===";
    
    require_once __DIR__ . '/config.php';
    
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    
    $log[] = "Email enviado: $email";
    $log[] = "Senha length: " . strlen($senha);
    
    // Query
    $sql = "SELECT id, nome, senha FROM usuarios WHERE email = '$email'";
    $result = $conexao->query($sql);
    
    if ($result->num_rows > 0) {
        $usuario = $result->fetch_assoc();
        $log[] = "✅ Usuário encontrado: {$usuario['nome']} (ID: {$usuario['id']})";
        
        if (password_verify($senha, $usuario['senha'])) {
            $log[] = "✅ Senha correta!";
            
            // CRIAR SESSÃO
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['teste_timestamp'] = time();
            
            $log[] = "✅ Sessão criada: \$_SESSION['usuario_id'] = {$usuario['id']}";
            $log[] = "✅ Session ID após login: " . session_id();
            
            // Forçar gravação
            session_write_close();
            $log[] = "✅ session_write_close() executado";
            
            session_start();
            $log[] = "✅ session_start() executado novamente";
            $log[] = "✅ \$_SESSION['usuario_id'] = " . ($_SESSION['usuario_id'] ?? 'VAZIO!');
            
        } else {
            $log[] = "❌ Senha incorreta";
        }
    } else {
        $log[] = "❌ Usuário não encontrado";
    }
}

// 🔍 Verificar sessão atual
$log[] = "\n=== ESTADO ATUAL DA SESSÃO ===";
if (isset($_SESSION['usuario_id'])) {
    $log[] = "✅ \$_SESSION['usuario_id'] = " . $_SESSION['usuario_id'];
} else {
    $log[] = "❌ \$_SESSION['usuario_id'] NÃO DEFINIDO";
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Teste Final Integrado</title>
    <style>
        body { font-family: 'Courier New', monospace; margin: 0; background: #0d1117; color: #c9d1d9; }
        .container { max-width: 800px; margin: 20px auto; }
        .log { background: #161b22; padding: 20px; border-radius: 6px; border: 1px solid #30363d; margin-bottom: 20px; }
        .log-line { margin: 5px 0; font-size: 13px; line-height: 1.6; }
        .pass { color: #3fb950; }
        .fail { color: #f85149; }
        .info { color: #58a6ff; }
        .warn { color: #d29922; }
        .form { background: #0d1117; padding: 20px; border: 1px solid #30363d; border-radius: 6px; margin-bottom: 20px; }
        input { background: #0d1117; border: 1px solid #30363d; color: #c9d1d9; padding: 10px; margin: 10px 0; width: 100%; box-sizing: border-box; border-radius: 6px; }
        button { background: #238636; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold; }
        button:hover { background: #2ea043; }
        h1 { color: #58a6ff; }
        h2 { color: #79c0ff; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎯 Teste Final Integrado - Login</h1>
        
        <!-- LOGS -->
        <h2>📊 Logs do Sistema</h2>
        <div class="log">
            <?php
            foreach ($log as $line) {
                if (empty($line)) {
                    echo "<div class='log-line'></div>";
                } elseif (strpos($line, '✅') === 0) {
                    echo "<div class='log-line pass'>$line</div>";
                } elseif (strpos($line, '❌') === 0) {
                    echo "<div class='log-line fail'>$line</div>";
                } elseif (strpos($line, '===') === 0) {
                    echo "<div class='log-line info'><strong>$line</strong></div>";
                } elseif (strpos($line, '⚠️') === 0) {
                    echo "<div class='log-line warn'>$line</div>";
                } else {
                    echo "<div class='log-line'>$line</div>";
                }
            }
            ?>
        </div>
        
        <!-- FORMULÁRIO DE TESTE -->
        <h2>🔐 Testar Login</h2>
        <div class="form">
            <form method="POST">
                <p>Preencha com as credenciais reais para testar o fluxo completo:</p>
                <input type="email" name="email" placeholder="seu@email.com" required value="carlos142165@gmail.com">
                <input type="password" name="senha" placeholder="sua senha" required>
                <input type="hidden" name="fazer_login" value="1">
                <button type="submit">▶️ Testar Login</button>
            </form>
        </div>
        
        <!-- VERIFICAÇÃO -->
        <h2>🔍 Status Atual</h2>
        <div class="log">
            <?php
            if (isset($_SESSION['usuario_id'])) {
                echo "<div class='log-line pass'>✅ SESSÃO ATIVA COM USUARIO ID: " . $_SESSION['usuario_id'] . "</div>";
                echo "<div class='log-line'>Session ID: " . session_id() . "</div>";
                echo "<div class='log-line' style='margin-top: 15px;'>";
                echo "<a href='bot_aovivo.php' style='color: #58a6ff; text-decoration: underline;'>→ Ir para Bot ao Vivo</a>";
                echo "</div>";
            } else {
                echo "<div class='log-line fail'>❌ SEM SESSÃO ATIVA</div>";
                echo "<div class='log-line warn'>⚠️ Se acabou de fazer login acima, a sessão deveria estar criada</div>";
            }
            ?>
        </div>

        <!-- DEBUGGING -->
        <h2>🛠️ Informações Técnicas</h2>
        <div class="log">
            <div class="log-line">PHP Version: <?php echo phpversion(); ?></div>
            <div class="log-line">Session Handler: <?php echo ini_get('session.save_handler'); ?></div>
            <div class="log-line">Session Module: <?php echo extension_loaded('session') ? '✅ Carregado' : '❌ Não carregado'; ?></div>
            <div class="log-line">Servidor: <?php echo $_SERVER['HTTP_HOST'] ?? 'desconhecido'; ?></div>
            <div class="log-line">HTTPS: <?php echo (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? '✅ SIM' : '⚠️ NÃO'; ?></div>
        </div>
    </div>
</body>
</html>
