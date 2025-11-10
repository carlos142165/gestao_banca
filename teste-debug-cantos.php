<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';

$conexao->set_charset("utf8mb4");

// Teste 1: Buscar exemplos de CANTOS
$sql1 = "SELECT id, titulo, tipo_aposta, time_1, time_2, resultado 
         FROM bote 
         WHERE LOWER(tipo_aposta) LIKE LOWER('%cantos%')
         LIMIT 5";

$resultado1 = $conexao->query($sql1);
$cantos_por_tipo = [];
if ($resultado1) {
    while ($row = $resultado1->fetch_assoc()) {
        $cantos_por_tipo[] = $row;
    }
}

// Teste 2: Buscar por título
$sql2 = "SELECT id, titulo, tipo_aposta, time_1, time_2, resultado 
         FROM bote 
         WHERE LOWER(titulo) LIKE LOWER('%cantos%')
         OR LOWER(titulo) LIKE LOWER('%canto%')
         OR titulo LIKE '%⛳%'
         LIMIT 5";

$resultado2 = $conexao->query($sql2);
$cantos_por_titulo = [];
if ($resultado2) {
    while ($row = $resultado2->fetch_assoc()) {
        $cantos_por_titulo[] = $row;
    }
}

// Teste 3: Buscar valores únicos de tipo_aposta que contenham "CANTOS"
$sql3 = "SELECT DISTINCT tipo_aposta 
         FROM bote 
         WHERE tipo_aposta IS NOT NULL 
         AND (tipo_aposta LIKE '%CANTOS%' OR tipo_aposta LIKE '%cantos%' OR tipo_aposta LIKE '%Cantos%')
         LIMIT 10";

$resultado3 = $conexao->query($sql3);
$tipos_unicos = [];
if ($resultado3) {
    while ($row = $resultado3->fetch_assoc()) {
        $tipos_unicos[] = $row;
    }
}

// Teste 4: Total por tipo de aposta
$sql4 = "SELECT tipo_aposta, COUNT(*) as total 
         FROM bote 
         WHERE tipo_aposta IS NOT NULL 
         GROUP BY tipo_aposta 
         ORDER BY total DESC";

$resultado4 = $conexao->query($sql4);
$resumo_tipos = [];
if ($resultado4) {
    while ($row = $resultado4->fetch_assoc()) {
        $resumo_tipos[] = $row;
    }
}

echo json_encode([
    'cantos_por_tipo_aposta' => [
        'sql' => $sql1,
        'total_encontrados' => count($cantos_por_tipo),
        'dados' => $cantos_por_tipo
    ],
    'cantos_por_titulo' => [
        'sql' => $sql2,
        'total_encontrados' => count($cantos_por_titulo),
        'dados' => array_slice($cantos_por_titulo, 0, 3)
    ],
    'tipos_unicos_cantos' => [
        'sql' => $sql3,
        'total_encontrados' => count($tipos_unicos),
        'dados' => $tipos_unicos
    ],
    'resumo_tipos_aposta' => [
        'sql' => $sql4,
        'total_encontrados' => count($resumo_tipos),
        'dados' => $resumo_tipos
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

$conexao->close();
?>
