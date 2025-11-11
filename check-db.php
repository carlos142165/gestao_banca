<?php
require_once "config.php";

echo "=== VERIFICANDO BANCO DE DADOS ===\n\n";

// Procurar registro com ID 191 (que acabou de ser inserido)
$query = "SELECT id, titulo, valor_over, odds, status_aposta FROM bote WHERE id = 191 LIMIT 1";
$result = $conexao->query($query);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "✅ REGISTRO ENCONTRADO:\n";
    echo "   ID: " . $row["id"] . "\n";
    echo "   Título: " . $row["titulo"] . "\n";
    echo "   valor_over: " . $row["valor_over"] . " (tipo: " . gettype($row["valor_over"]) . ")\n";
    echo "   odds: " . $row["odds"] . " (tipo: " . gettype($row["odds"]) . ")\n";
    echo "   status_aposta: " . $row["status_aposta"] . "\n";
    
    if ($row["valor_over"] == 0.5) {
        echo "\n✅✅✅ SUCESSO! valor_over está correto (0.5)! ✅✅✅\n";
    } else {
        echo "\n❌ ERRO: valor_over é " . $row["valor_over"] . "\n";
    }
} else {
    echo "❌ Registro não encontrado!\n";
}

// Listar os últimos registros com +0.5
echo "\n\n=== ÚLTIMOS REGISTROS COM +0.5 ===\n\n";
$allQuery = "SELECT id, titulo, valor_over, odds FROM bote WHERE valor_over = 0.5 ORDER BY id DESC LIMIT 5";
$allResult = $conexao->query($allQuery);

if ($allResult->num_rows > 0) {
    while ($row = $allResult->fetch_assoc()) {
        echo "ID: " . $row["id"] . " | Título: " . $row["titulo"] . " | valor_over: " . $row["valor_over"] . " | odds: " . $row["odds"] . "\n";
    }
} else {
    echo "Nenhum registro com +0.5 encontrado\n";
}
?>
