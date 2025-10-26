<?php
session_start();

// DEBUG - Ver qual é o ID real na sessão
echo "<h1>DEBUG - Informações da Sessão</h1>";
echo "<pre>";
echo "Usuário ID (raw): " . var_export($_SESSION['usuario_id'] ?? 'NÃO DEFINIDO', true) . "\n";
echo "Tipo de dado: " . gettype($_SESSION['usuario_id'] ?? null) . "\n";
echo "Valor inteiro: " . intval($_SESSION['usuario_id'] ?? 0) . "\n";
echo "Comparação (=== 23): " . var_export((isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] === 23), true) . "\n";
echo "Comparação (== 23): " . var_export((isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] == 23), true) . "\n";
echo "\n\nToda a sessão:\n";
var_dump($_SESSION);
echo "</pre>";

// Teste condicional
if (isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] === 23) {
    echo "<h2 style='color: green;'>✅ VERDADEIRO - Mostraria o link!</h2>";
} else {
    echo "<h2 style='color: red;'>❌ FALSO - NÃO mostraria o link</h2>";
}
?>
