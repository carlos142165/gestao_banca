<?php
/**
 * TESTE - obter-planos.php
 * Simula a requisição para verificar se os planos estão sendo carregados
 */

require_once 'config.php';

// Simular a mesma query do obter-planos.php
$stmt = $conexao->prepare("
    SELECT 
        id,
        nome,
        preco_mes,
        preco_ano,
        mentores_limite,
        entradas_diarias,
        icone,
        cor_tema
    FROM planos
    ORDER BY id ASC
");

if (!$stmt) {
    die("Erro ao preparar statement: " . $conexao->error);
}

$stmt->execute();
$result = $stmt->get_result();

echo "<h2>Planos Carregados do Banco de Dados:</h2>";
echo "<pre>";

if ($result->num_rows === 0) {
    echo "❌ NENHUM PLANO ENCONTRADO NA TABELA 'planos'";
} else {
    echo "✅ " . $result->num_rows . " plano(s) encontrado(s):\n\n";
    
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . "\n";
        echo "Nome: " . $row['nome'] . "\n";
        echo "Preço Mês: " . $row['preco_mes'] . "\n";
        echo "Preço Ano: " . $row['preco_ano'] . "\n";
        echo "Mentores Limite: " . $row['mentores_limite'] . "\n";
        echo "Entradas Diárias: " . $row['entradas_diarias'] . "\n";
        echo "Ícone: " . $row['icone'] . "\n";
        echo "Cor: " . $row['cor_tema'] . "\n";
        echo "---\n";
    }
}

echo "</pre>";

$stmt->close();
?>
