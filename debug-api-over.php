<?php
// Debug da API de histórico
header('Content-Type: application/json; charset=utf-8');

// Receber dados POST
$input = json_decode(file_get_contents('php://input'), true);

// Log de tudo que recebemos
error_log("=== DEBUG API OVER ===");
error_log("INPUT RECEBIDO: " . json_encode($input));
error_log("time1: " . ($input['time1'] ?? 'vazio'));
error_log("time2: " . ($input['time2'] ?? 'vazio'));
error_log("tipo: " . ($input['tipo'] ?? 'vazio'));
error_log("valorOver: '" . ($input['valorOver'] ?? 'VAZIO') . "' (tipo: " . gettype($input['valorOver'] ?? null) . ")");
error_log("valorOver === null? " . var_export($input['valorOver'] === null, true));
error_log("valorOver === ''? " . var_export($input['valorOver'] === '', true));
error_log("!empty(valorOver)? " . var_export(!empty($input['valorOver'] ?? null), true));

echo json_encode([
    'debug' => true,
    'input' => $input,
    'valorOver_recebido' => $input['valorOver'] ?? null,
    'valorOver_vazio' => empty($input['valorOver'] ?? null),
    'check_logs' => 'Verifique /xampp/php/logs/php_error_log para logs detalhados'
]);
?>
