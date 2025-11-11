<?php
// Teste rápido da API
header('Content-Type: application/json');

// Simular request
$input = [
    'time1' => 'Everton',
    'time2' => 'Fulham', 
    'tipo' => 'gols',
    'subtipo_aposta' => '+0.5',
    'limite' => 5
];

echo json_encode([
    'status' => 'teste',
    'input_recebido' => $input,
    'valor_subtipo' => floatval(str_replace('+', '', $input['subtipo_aposta'])),
    'mensagem' => 'API está respondendo corretamente'
]);
?>
