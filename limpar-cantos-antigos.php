<?php
// ✅ DELETAR CANTOS COM ODDS = 0
header('Content-Type: application/json; charset=utf-8');

require_once 'config.php';

$data_hoje = date('Y-m-d');

// Deletar CANTOS com odds = 0 do dia de hoje
$query = "DELETE FROM bote 
          WHERE DATE(data_criacao) = '$data_hoje'
          AND (tipo_aposta LIKE '%CANTO%' OR titulo LIKE '%CANTO%')
          AND odds = 0";

if ($conexao->query($query)) {
    $linhas_deletadas = $conexao->affected_rows;
    echo json_encode([
        'sucesso' => true,
        'mensagem' => "✅ Deletados $linhas_deletadas registro(s) de CANTOS com odds=0",
        'data' => $data_hoje,
        'linhas_afetadas' => $linhas_deletadas
    ]);
} else {
    echo json_encode([
        'sucesso' => false,
        'erro' => $conexao->error
    ]);
}
?>
