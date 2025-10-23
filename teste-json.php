<?php
// ✅ TESTE DE JSON - Verificar se há erros
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');

try {
    // Testar se config carrega sem erro
    require_once 'config.php';
    echo json_encode([
        'status' => 'config.php carregado',
        'sucesso' => true
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'erro em config.php',
        'erro' => $e->getMessage(),
        'sucesso' => false
    ]);
}
?>
