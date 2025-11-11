<?php
require_once 'config.php';

$query = "SELECT id, titulo, valor_over, odds FROM bote WHERE valor_over = 0.5 ORDER BY id DESC LIMIT 5";
$result = $conexao->query($query);

echo "=== BUSCANDO APOSTAS COM +0.5 ===\n\n";

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . "\n";
        echo "Título: " . $row['titulo'] . "\n";
        echo "Valor Over: " . $row['valor_over'] . " (tipo armazenado: " . gettype($row['valor_over']) . ")\n";
        echo "Odds: " . $row['odds'] . "\n";
        echo "---\n";
    }
} else {
    echo "❌ Nenhuma aposta com +0.5 encontrada.\n";
}

// Verificar todos os valores_over diferentes
echo "\n=== TODOS OS VALORES OVER ÚNICOS ===\n";
$allQuery = "SELECT DISTINCT valor_over FROM bote ORDER BY valor_over ASC";
$allResult = $conexao->query($allQuery);

if ($allResult->num_rows > 0) {
    while ($row = $allResult->fetch_assoc()) {
        echo "- " . $row['valor_over'] . "\n";
    }
} else {
    echo "Nenhuma aposta encontrada.\n";
}
?>
