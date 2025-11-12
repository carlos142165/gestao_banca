<?php
// ✅ TESTE SIMPLES - Verificar se obterConexao() funciona
header('Content-Type: text/plain; charset=utf-8');

echo "=== TESTE FUNCTION obterConexao() ===\n\n";

require_once 'config.php';

echo "1️⃣ Testando obterConexao()...\n";

// Testar primeira chamada
$conexao1 = obterConexao();
if ($conexao1) {
    echo "✅ Primeira chamada funcionou\n";
    echo "   Tipo: " . get_class($conexao1) . "\n";
    echo "   Host: " . $conexao1->get_server_info() . "\n";
} else {
    echo "❌ Primeira chamada FALHOU\n";
}

echo "\n2️⃣ Testando ping()...\n";
if ($conexao1 && $conexao1->ping()) {
    echo "✅ Conexão respondendo ao ping\n";
} else {
    echo "❌ Ping falhou\n";
}

echo "\n3️⃣ Testando segunda chamada (deve reutilizar)...\n";
$conexao2 = obterConexao();
if ($conexao2) {
    echo "✅ Segunda chamada funcionou\n";
    echo "   Mesma conexão? " . ($conexao1 === $conexao2 ? "SIM" : "NÃO") . "\n";
} else {
    echo "❌ Segunda chamada FALHOU\n";
}

echo "\n4️⃣ Testando query simples...\n";
if ($conexao1) {
    $stmt = $conexao1->prepare("SELECT 1 as teste");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $row = $result->fetch_assoc();
            echo "✅ Query simples funcionou\n";
            echo "   Resultado: " . json_encode($row) . "\n";
        } else {
            echo "❌ Query não retornou resultado\n";
        }
        $stmt->close();
    } else {
        echo "❌ Prepare da query falhou: " . $conexao1->error . "\n";
    }
} else {
    echo "❌ Sem conexão para testar\n";
}

echo "\n5️⃣ Testando dados_banca.php via simulação...\n";
$_SESSION['usuario_id'] = 1; // Simular sessão
$_SESSION['plano'] = 'teste';

if ($conexao1) {
    $stmt = $conexao1->prepare("SELECT COUNT(*) as total FROM controle WHERE id_usuario = ?");
    if ($stmt) {
        $stmt->bind_param("i", $_SESSION['usuario_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        echo "✅ Teste de tabela controle funcionou\n";
        echo "   Total de registros: " . $row['total'] . "\n";
        $stmt->close();
    } else {
        echo "❌ Prepare falhou\n";
    }
}

echo "\n✅ TODOS OS TESTES CONCLUÍDOS\n";
?>
