<?php
// ✅ VERIFICAR DADOS SALVOS

header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';

$query = "SELECT 
    id, titulo, tipo_aposta, time_1, time_2,
    tempo_minuto,
    odds_inicial_casa, odds_inicial_empate, odds_inicial_fora,
    estadio,
    ataques_perigosos_1, ataques_perigosos_2,
    cartoes_amarelos_1, cartoes_amarelos_2,
    cartoes_vermelhos_1, cartoes_vermelhos_2,
    chutes_lado_1, chutes_lado_2,
    chutes_alvo_1, chutes_alvo_2,
    posse_bola_1, posse_bola_2,
    data_criacao
FROM bote 
ORDER BY id DESC 
LIMIT 3";

$result = $conexao->query($query);

if (!$result) {
    echo json_encode(['erro' => $conexao->error]);
    exit;
}

$registros = [];
while ($row = $result->fetch_assoc()) {
    $registros[] = $row;
}

echo json_encode([
    'sucesso' => true,
    'total' => count($registros),
    'registros' => $registros
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
