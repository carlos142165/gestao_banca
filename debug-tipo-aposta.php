<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';

$conexao->set_charset("utf8mb4");

// Buscar uma mensagem de CANTOS para ver se tipo_aposta está sendo retornado
$sql = "SELECT id, titulo, tipo_aposta, time_1, time_2, resultado 
        FROM bote 
        WHERE tipo_aposta = 'CANTOS'
        LIMIT 1";

$resultado = $conexao->query($sql);

if ($resultado && $row = $resultado->fetch_assoc()) {
    echo json_encode([
        'tem_tipo_aposta' => !empty($row['tipo_aposta']),
        'tipo_aposta_valor' => $row['tipo_aposta'],
        'mensagem_completa' => $row
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['erro' => 'Nenhuma mensagem de CANTOS encontrada']);
}

$conexao->close();
?>
