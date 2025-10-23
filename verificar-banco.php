<?php
require_once 'config.php';

echo "=== VERIFICANDO BANCO DE DADOS ===\n\n";

echo "📋 USUÁRIOS:\n";
$result = $conexao->query('SELECT id, nome, email FROM usuarios LIMIT 10');
if ($result) {
    while($row = $result->fetch_assoc()) {
        echo "  ID: {$row['id']} - Nome: {$row['nome']} - Email: {$row['email']}\n";
    }
} else {
    echo "  ❌ Erro na query: " . $conexao->error . "\n";
}

echo "\n📋 PLANOS:\n";
$result = $conexao->query('SELECT id, nome, preco_mes, preco_ano FROM planos LIMIT 10');
if ($result) {
    while($row = $result->fetch_assoc()) {
        echo "  ID: {$row['id']} - Nome: {$row['nome']} - Mês: R${$row['preco_mes']} - Ano: R${$row['preco_ano']}\n";
    }
} else {
    echo "  ❌ Erro na query: " . $conexao->error . "\n";
}

echo "\n✅ Verificação completa\n";
?>
