<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔍 Teste Básico de Conexão</h1>";

// Testar conexão
require_once 'config.php';

echo "<h2>1. Conexão com Banco</h2>";
if ($conexao) {
    echo "✅ Conectado ao banco: <strong>" . $conexao->select_db("formulario-carlos") . "</strong><br>";
    echo "✅ Banco selecionado<br>";
} else {
    echo "❌ Erro de conexão: " . $conexao->connect_error . "<br>";
    exit;
}

echo "<h2>2. Sessão</h2>";
session_start();
echo "📌 ID da Sessão: " . session_id() . "<br>";
echo "📌 Usuario ID: " . ($_SESSION['usuario_id'] ?? 'NÃO DEFINIDO') . "<br>";

if (isset($_SESSION['usuario_id'])) {
    echo "✅ Sessão ativa<br>";
} else {
    echo "⚠️ Usuário não autenticado<br>";
}

echo "<h2>3. Teste da Query</h2>";

// Primeiro testar a query básica
$result = $conexao->query("SELECT id, nome, email, telefone FROM usuarios LIMIT 1");
if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "✅ Usuários encontrados:<br>";
    echo "<pre>" . json_encode($user, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
} else {
    echo "❌ Nenhum usuário encontrado<br>";
}

echo "<h2>4. Colunas da Tabela</h2>";
$cols = $conexao->query("SHOW COLUMNS FROM usuarios");
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Chave</th></tr>";
while ($col = $cols->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $col['Field'] . "</td>";
    echo "<td>" . $col['Type'] . "</td>";
    echo "<td>" . $col['Null'] . "</td>";
    echo "<td>" . $col['Key'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>5. Teste da Query com Prepared Statement</h2>";

if (isset($_SESSION['usuario_id'])) {
    $usuario_id = $_SESSION['usuario_id'];
    
    // Tentar query SEM plano primeiro
    $stmt = $conexao->prepare("SELECT id, nome, email, telefone FROM usuarios WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $usuario_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                echo "✅ Query SEM plano funcionou:<br>";
                echo "<pre>" . json_encode($user, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
            }
        } else {
            echo "❌ Erro ao executar: " . $stmt->error . "<br>";
        }
    } else {
        echo "❌ Erro ao preparar statement: " . $conexao->error . "<br>";
    }
    
    // Tentar query COM plano
    $stmt2 = $conexao->prepare("SELECT id, nome, email, telefone, plano FROM usuarios WHERE id = ?");
    if ($stmt2) {
        $stmt2->bind_param("i", $usuario_id);
        if ($stmt2->execute()) {
            $result2 = $stmt2->get_result();
            if ($result2->num_rows > 0) {
                $user = $result2->fetch_assoc();
                echo "<br>✅ Query COM plano funcionou:<br>";
                echo "<pre>" . json_encode($user, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
            }
        } else {
            echo "<br>⚠️ Query COM plano falhou (esperado se campo não existe):<br>";
            echo "Erro: " . $stmt2->error . "<br>";
        }
    }
} else {
    echo "⚠️ Não está autenticado. Teste a query direto:<br>";
    $stmt = $conexao->prepare("SELECT id, nome, email, telefone FROM usuarios WHERE id = ?");
    if ($stmt) {
        $id_teste = 1;
        $stmt->bind_param("i", $id_teste);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            echo "✅ Query funcionou com ID=1:<br>";
            echo "<pre>" . json_encode($result->fetch_assoc(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
        }
    }
}

?>
<style>
body { font-family: Arial; margin: 20px; }
h1, h2 { color: #333; }
table { border-collapse: collapse; }
th { background: #f2f2f2; }
td, th { padding: 10px; text-align: left; }
pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
</style>
