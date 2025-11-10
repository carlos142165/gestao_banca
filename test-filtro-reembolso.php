<?php
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('America/Sao_Paulo');
require_once 'config.php';

// Teste: Simular request com filtrarSemReembolso
$input = json_decode(file_get_contents('php://input'), true);

$filtrarSemReembolso = isset($input['filtrarSemReembolso']) && $input['filtrarSemReembolso'] === true;

error_log("🔧 [TEST] INPUT: " . json_encode($input));
error_log("🔧 [TEST] filtrarSemReembolso === true? " . ($filtrarSemReembolso ? 'SIM' : 'NÃO'));
error_log("🔧 [TEST] Valor: " . var_export($filtrarSemReembolso, true));

echo json_encode([
    'success' => true,
    'input_recebido' => $input,
    'filtrarSemReembolso' => $filtrarSemReembolso,
    'is_bool' => is_bool($filtrarSemReembolso),
    'value' => $filtrarSemReembolso ? 'true' : 'false'
]);
?>
