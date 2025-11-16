<?php
/**
 * 🔍 DIAGNÓSTICO DE LOGIN
 * Arquivo para testar problemas de login em produção
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 DIAGNÓSTICO DE LOGIN</h1>";
echo "<pre>";

// 1️⃣ Testar conexão com banco
echo "\n=== 1️⃣ TESTANDO CONEXÃO COM BANCO ===\n";
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'unknown';
echo "Host detectado: " . $_SERVER['HTTP_HOST'] . "\n";

try {
    require_once __DIR__ . '/config.php';
    
    if ($conexao->connect_error) {
        echo "❌ ERRO: " . $conexao->connect_error . "\n";
        exit;
    }
    
    echo "✅ Conexão estabelecida!\n";
    echo "Database: " . DB_NAME . "\n";
    echo "Host: " . DB_HOST . "\n";
    echo "Environment: " . ENVIRONMENT . "\n";
} catch (Exception $e) {
    echo "❌ EXCEÇÃO: " . $e->getMessage() . "\n";
    exit;
}

// 2️⃣ Testar se tabela usuarios existe
echo "\n=== 2️⃣ TESTANDO TABELA USUARIOS ===\n";
$sql = "SELECT COUNT(*) as total FROM usuarios";
$result = $conexao->query($sql);

if (!$result) {
    echo "❌ ERRO ao consultar: " . $conexao->error . "\n";
} else {
    $row = $result->fetch_assoc();
    echo "✅ Tabela EXISTS\n";
    echo "Total de usuários: " . $row['total'] . "\n";
}

// 3️⃣ Listar alguns usuários (sem senhas!)
echo "\n=== 3️⃣ LISTANDO USUÁRIOS ===\n";
$sql = "SELECT id, nome, email FROM usuarios LIMIT 5";
$result = $conexao->query($sql);

if ($result && $result->num_rows > 0) {
    while ($user = $result->fetch_assoc()) {
        echo "ID: {$user['id']} | Nome: {$user['nome']} | Email: {$user['email']}\n";
    }
} else {
    echo "⚠️ Nenhum usuário encontrado ou erro na query\n";
}

// 4️⃣ Testar login manual
echo "\n=== 4️⃣ TESTANDO LOGIN MANUAL ===\n";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $conexao->real_escape_string($_POST['email']);
    $senha = $_POST['senha'];
    
    echo "Email testado: $email\n";
    
    $sql = "SELECT * FROM usuarios WHERE email = '$email'";
    $result = $conexao->query($sql);
    
    if ($result->num_rows > 0) {
        $usuario = $result->fetch_assoc();
        echo "✅ Usuário encontrado!\n";
        echo "ID: {$usuario['id']}\n";
        echo "Nome: {$usuario['nome']}\n";
        
        if (password_verify($senha, $usuario['senha'])) {
            echo "✅ SENHA CORRETA!\n";
            echo "Criando sessão com ID: {$usuario['id']}\n";
            $_SESSION['usuario_id'] = $usuario['id'];
            echo "Redirecionando para gestao-diaria.php...\n";
            // header("Location: gestao-diaria.php");
        } else {
            echo "❌ SENHA INCORRETA\n";
        }
    } else {
        echo "❌ Email não encontrado\n";
    }
} else {
    echo "Aguardando POST com email e senha\n";
}

// 5️⃣ Verificar sessão
echo "\n=== 5️⃣ ESTADO DA SESSÃO ===\n";
echo "session_id: " . session_id() . "\n";
echo "SESSION array:\n";
print_r($_SESSION);

// 6️⃣ Teste de redirecionamento
echo "\n=== 6️⃣ TESTE DE HEADERS ===\n";
echo "Headers já enviados? " . (headers_sent() ? "SIM ❌" : "NÃO ✅") . "\n";
echo "Content-Type: " . (isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : 'não definido') . "\n";

echo "\n</pre>";

// Formulário de teste
echo "<hr>";
echo "<h2>Testar Login Aqui:</h2>";
echo "<form method='POST'>";
echo "Email: <input type='email' name='email' required><br>";
echo "Senha: <input type='password' name='senha' required><br>";
echo "<button type='submit'>Testar Login</button>";
echo "</form>";
?>
