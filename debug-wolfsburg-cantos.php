<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';

$conexao->set_charset("utf8mb4");

// Debug: Buscar dados de Wolfsburg com CANTOS
$sql = "SELECT id, titulo, tipo_aposta, time_1, time_2, resultado, data_criacao 
        FROM bote 
        WHERE tipo_aposta = 'CANTOS'
        AND (LOWER(time_1) LIKE '%wolfsburg%' OR LOWER(time_2) LIKE '%wolfsburg%')
        ORDER BY data_criacao DESC
        LIMIT 10";

error_log("SQL: $sql");

$resultado = $conexao->query($sql);

$dados = [];
if ($resultado && $resultado->num_rows > 0) {
    while ($row = $resultado->fetch_assoc()) {
        $dados[] = $row;
    }
}

echo json_encode([
    'total' => count($dados),
    'registros' => $dados
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

$conexao->close();
?>
