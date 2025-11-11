<?php
require_once 'config.php';

$conexao->query("DELETE FROM bote WHERE id IN (241, 242, 243)");

$result = $conexao->query("SELECT id, titulo, tipo_aposta, valor_over FROM bote WHERE id >= 240 ORDER BY id DESC LIMIT 5");
while($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | valor_over: " . $row['valor_over'] . " | tipo: " . $row['tipo_aposta'] . " | titulo: " . substr($row['titulo'], 0, 40) . "\n";
}

echo "\nApostas 241, 242, 243 deletadas com sucesso!\n";
?>
