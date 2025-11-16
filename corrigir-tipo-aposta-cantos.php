<?php
/**
 * Script para CORRIGIR tipo_aposta errado no banco de dados
 * para mensagens que deveriam ser CANTOS mas estão marcadas como GOLS
 */
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';

$conexao->set_charset("utf8mb4");

// Encontrar e corrigir registros que deveriam ser CANTOS
$sql_update = "UPDATE bote 
SET tipo_aposta = 'CANTOS'
WHERE 
    (titulo LIKE '%⛳%' OR titulo LIKE '%🚩%' OR LOWER(titulo) LIKE '%cantos%' OR LOWER(titulo) LIKE '%canto%' OR LOWER(titulo) LIKE '%escanteio%' OR LOWER(titulo) LIKE '%escantei%')
    AND (tipo_aposta IS NULL OR tipo_aposta = '' OR LOWER(tipo_aposta) != 'cantos')";

if ($conexao->query($sql_update)) {
    $registros_atualizados = $conexao->affected_rows;
    
    // Contar registros agora corretos
    $sql_check = "SELECT COUNT(*) as total FROM bote 
    WHERE (titulo LIKE '%⛳%' OR titulo LIKE '%🚩%' OR LOWER(titulo) LIKE '%cantos%' OR LOWER(titulo) LIKE '%canto%')
    AND LOWER(tipo_aposta) = 'cantos'";
    
    $result = $conexao->query($sql_check);
    $row = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'mensagem' => 'Registros corrigidos com sucesso!',
        'registros_atualizados' => $registros_atualizados,
        'total_cantos_agora' => $row['total'],
        'acao' => 'SET tipo_aposta = CANTOS'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        'success' => false,
        'erro' => $conexao->error,
        'mensagem' => 'Falha ao atualizar registros'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

$conexao->close();
?>
