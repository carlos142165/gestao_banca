<?php
header('Content-Type: application/json; charset=utf-8');

// Receber o payload exatamente como frontend envia
$input = json_decode(file_get_contents('php://input'), true);

// Log SUPER detalhado
error_log("╔════════════════════════════════════════════════════════════════╗");
error_log("║ 🔥 TESTE DIRETO - VERIFICANDO RECEBIMENTO DE DADOS             ║");
error_log("╚════════════════════════════════════════════════════════════════╝");
error_log("💬 INPUT BRUTO (json_decode): " . print_r($input, true));
error_log("📊 Tamanho de input: " . strlen(file_get_contents('php://input')));

// Extrair campos
$time1 = isset($input['time1']) ? trim($input['time1']) : '';
$time2 = isset($input['time2']) ? trim($input['time2']) : '';
$tipo = isset($input['tipo']) ? trim($input['tipo']) : '';
$limite = isset($input['limite']) ? intval($input['limite']) : '';
$valorOver = isset($input['valorOver']) ? trim($input['valorOver']) : null;

error_log("🎯 CAMPOS EXTRAÍDOS:");
error_log("   time1: '" . $time1 . "' (type: " . gettype($time1) . ")");
error_log("   time2: '" . $time2 . "' (type: " . gettype($time2) . ")");
error_log("   tipo: '" . $tipo . "' (type: " . gettype($tipo) . ")");
error_log("   limite: '" . $limite . "' (type: " . gettype($limite) . ")");
error_log("   valorOver: '" . ($valorOver ?? 'NULL') . "' (type: " . gettype($valorOver) . ")");

error_log("🔬 ANÁLISE:");
error_log("   isset(valorOver): " . var_export(isset($input['valorOver']), true));
error_log("   is_null(valorOver): " . var_export($valorOver === null, true));
error_log("   empty(valorOver): " . var_export(empty($valorOver), true));
error_log("   strlen(valorOver): " . (is_string($valorOver) ? strlen($valorOver) : 'N/A'));

// Retornar resposta
$response = [
    'success' => true,
    'recebido' => $input,
    'campos_extraidos' => [
        'time1' => $time1,
        'time2' => $time2,
        'tipo' => $tipo,
        'limite' => $limite,
        'valorOver' => $valorOver
    ],
    'analise' => [
        'valorOver_isset' => isset($input['valorOver']),
        'valorOver_is_null' => $valorOver === null,
        'valorOver_empty' => empty($valorOver),
        'valorOver_strlen' => is_string($valorOver) ? strlen($valorOver) : null
    ]
];

echo json_encode($response, JSON_UNESCAPED_UNICODE);
error_log("╚════════════════════════════════════════════════════════════════╝");
?>
