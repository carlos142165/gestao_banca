<?php
/**
 * Debug: Verificar o tipo_aposta sendo salvo no banco para CANTOS
 */
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';

$conexao->set_charset("utf8mb4");

// Buscar exemplos de mensagens com ícone de CANTOS
$sql = "SELECT 
    id,
    titulo,
    tipo_aposta,
    time_1,
    time_2,
    resultado,
    data_criacao
FROM bote 
WHERE 
    (titulo LIKE '%⛳%' OR titulo LIKE '%🚩%' OR LOWER(titulo) LIKE '%cantos%' OR LOWER(titulo) LIKE '%escanteio%')
    AND resultado IS NOT NULL
ORDER BY data_criacao DESC
LIMIT 10";

$result = $conexao->query($sql);
$mensagens = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $mensagens[] = [
            'id' => $row['id'],
            'titulo' => $row['titulo'],
            'tipo_aposta' => $row['tipo_aposta'],
            'tipo_aposta_lowercase' => strtolower($row['tipo_aposta']),
            'contem_cantos' => (stripos($row['tipo_aposta'], 'cantos') !== false || stripos($row['tipo_aposta'], 'canto') !== false),
            'time_1' => $row['time_1'],
            'time_2' => $row['time_2'],
            'resultado' => $row['resultado'],
            'data' => $row['data_criacao']
        ];
    }
}

echo json_encode([
    'debug' => 'Mensagens de CANTOS encontradas',
    'total' => count($mensagens),
    'mensagens' => $mensagens,
    'nota' => 'Verificar se tipo_aposta está correto em cada mensagem'
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

$conexao->close();
?>
