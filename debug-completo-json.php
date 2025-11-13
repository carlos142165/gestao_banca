<?php
// ✅ DEBUG COMPLETO - Reprocessar tudo
header('Content-Type: application/json; charset=utf-8');

require_once 'config.php';

$data_hoje = date('Y-m-d');

// Buscar TODAS as mensagens
$query = "SELECT id, titulo, tipo_aposta, resultado, odds FROM bote 
          WHERE DATE(data_criacao) = '$data_hoje'
          ORDER BY data_criacao DESC";

$resultado = $conexao->query($query);

$apostas = [
    '+1⚽GOL' => ['green' => 0, 'red' => 0, 'coef' => 0, 'registros' => []],
    '+0.5⚽GOL' => ['green' => 0, 'red' => 0, 'coef' => 0, 'registros' => []],
    '+1⛳️CANTOS' => ['green' => 0, 'red' => 0, 'coef' => 0, 'registros' => []]
];

$debug_log = [];

while ($row = $resultado->fetch_assoc()) {
    $titulo = $row['titulo'];
    $res = $row['resultado'];
    $odds = floatval($row['odds']);
    
    // Determinar tipo
    $tipo = null;
    if (strpos(strtoupper($titulo), 'CANTO') !== false) {
        $tipo = '+1⛳️CANTOS';
    } elseif (strpos(strtoupper($titulo), '+1') !== false && strpos(strtoupper($titulo), 'GOL') !== false) {
        $tipo = '+1⚽GOL';
    } elseif (strpos(strtoupper($titulo), '+0.5') !== false && strpos(strtoupper($titulo), 'GOL') !== false) {
        $tipo = '+0.5⚽GOL';
    }
    
    if ($tipo) {
        if ($res === 'GREEN') {
            $apostas[$tipo]['green']++;
            $coef = $odds - 1;
            $apostas[$tipo]['coef'] += $coef;
            $apostas[$tipo]['registros'][] = [
                'id' => $row['id'],
                'odds' => $odds,
                'coef' => $coef,
                'resultado' => 'GREEN'
            ];
            $debug_log[] = "✅ GREEN: {$tipo} | odds={$odds} | coef={$coef} | total_coef=" . $apostas[$tipo]['coef'];
        } elseif ($res === 'RED') {
            $apostas[$tipo]['red']++;
            $apostas[$tipo]['registros'][] = [
                'id' => $row['id'],
                'odds' => $odds,
                'resultado' => 'RED'
            ];
            $debug_log[] = "❌ RED: {$tipo} | odds={$odds}";
        }
    } else {
        $debug_log[] = "⚠️ TIPO NÃO IDENTIFICADO: {$titulo}";
    }
}

// Calcular totais
$total_green = 0;
$total_red = 0;
$total_coef = 0;

foreach ($apostas as $tipo => $dados) {
    $total_green += $dados['green'];
    $total_red += $dados['red'];
    $total_coef += $dados['coef'];
}

echo json_encode([
    'sucesso' => true,
    'data' => $data_hoje,
    'debug_log' => $debug_log,
    'apostas' => [
        'gol_1' => [
            'tipo' => '+1⚽GOL',
            'green' => $apostas['+1⚽GOL']['green'],
            'red' => $apostas['+1⚽GOL']['red'],
            'coef' => round($apostas['+1⚽GOL']['coef'], 4),
            'valor_und_100' => round($apostas['+1⚽GOL']['coef'] * 100, 2)
        ],
        'gol_half' => [
            'tipo' => '+0.5⚽GOL',
            'green' => $apostas['+0.5⚽GOL']['green'],
            'red' => $apostas['+0.5⚽GOL']['red'],
            'coef' => round($apostas['+0.5⚽GOL']['coef'], 4),
            'valor_und_100' => round($apostas['+0.5⚽GOL']['coef'] * 100, 2)
        ],
        'cantos' => [
            'tipo' => '+1⛳️CANTOS',
            'green' => $apostas['+1⛳️CANTOS']['green'],
            'red' => $apostas['+1⛳️CANTOS']['red'],
            'coef' => round($apostas['+1⛳️CANTOS']['coef'], 4),
            'valor_und_100' => round($apostas['+1⛳️CANTOS']['coef'] * 100, 2),
            'registros_detalhados' => $apostas['+1⛳️CANTOS']['registros']
        ]
    ],
    'total' => [
        'green' => $total_green,
        'red' => $total_red,
        'coef' => round($total_coef, 4),
        'valor_und_100' => round($total_coef * 100, 2)
    ]
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
