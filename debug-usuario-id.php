<?php
session_start();

echo "<h1>DEBUG - Verificação de Sessão</h1>";
echo "<pre>";
echo "Usuario ID: " . ($_SESSION['usuario_id'] ?? 'NÃO DEFINIDO') . "\n";
echo "Tipo: " . gettype($_SESSION['usuario_id'] ?? null) . "\n";
echo "Comparação === 23: " . (isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] === 23 ? 'TRUE' : 'FALSE') . "\n";
echo "Comparação == 23: " . (isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] == 23 ? 'TRUE' : 'FALSE') . "\n";
echo "Valor inteiro: " . (int)($_SESSION['usuario_id'] ?? 0) . "\n";
echo "</pre>";

echo "<hr>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
?>
