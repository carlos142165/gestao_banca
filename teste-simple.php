<?php
// ===== TESTE SIMPLES E DIRETO =====

echo "TESTE DE CONFIGURAÇÃO\n";
echo "=====================\n\n";

echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'N/A') . "\n";
echo "SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'N/A') . "\n\n";

// Incluir config
require_once 'config.php';

echo "Após incluir config.php:\n";
echo "ENVIRONMENT: " . (defined('ENVIRONMENT') ? ENVIRONMENT : 'NÃO DEFINIDA') . "\n";
echo "DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'NÃO DEFINIDA') . "\n";
echo "DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'NÃO DEFINIDA') . "\n";
echo "DB_USERNAME: " . (defined('DB_USERNAME') ? DB_USERNAME : 'NÃO DEFINIDA') . "\n";

echo "\nConstantes definidas:\n";
echo print_r(get_defined_constants(false), true);
?>
