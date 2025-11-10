<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';

$conexao->set_charset("utf8mb4");

// Buscar alguns registros com CANTOS
$sql = "SELECT id, titulo, tipo_aposta, time_1, time_2, resultado, data_criacao 
        FROM bote 
        WHERE (
            LOWER(tipo_aposta) LIKE '%CANTOS%'
            OR LOWER(titulo) LIKE '%canto%'
            OR LOWER(titulo) LIKE '%escanteio%'
            OR LOWER(titulo) LIKE '%escantei%'
        )
        LIMIT 10";

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
