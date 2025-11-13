<?php
// Cabeçalho JSON
header('Content-Type: application/json');

// ✅ INCLUIR CONFIGURAÇÃO CENTRALIZADA DO BANCO
require_once 'config.php';

try {
    // 🔗 Conexão já vem de config.php
    
    // ✅ Verificar conexão
    if ($conexao->connect_error) {
        throw new Exception("Erro de conexão: " . $conexao->connect_error);
    }
    
    // 📅 Obter data de hoje (formato Y-m-d)
    $data_hoje = date('Y-m-d');
    
    // 🔍 Buscar todas as mensagens do dia com título, resultado E ODDS
    $query = "SELECT titulo, resultado, odds FROM bote 
              WHERE DATE(data_criacao) = '$data_hoje'
              ORDER BY data_criacao DESC";
    
    $resultado = $conexao->query($query);
    
    if (!$resultado) {
        throw new Exception("Erro na query: " . $conexao->error);
    }
    
    // 📊 Inicializar array com as 3 apostas
    $apostas = [
        '+1⚽GOL' => [
            'green' => 0, 
            'red' => 0,
            'lucro_coef_green' => 0,
            'lucro_coef_red' => 0,
            'registros_green' => [],
            'registros_red' => []
        ],
        '+1⛳️CANTOS' => [
            'green' => 0, 
            'red' => 0,
            'lucro_coef_green' => 0,
            'lucro_coef_red' => 0,
            'registros_green' => [],
            'registros_red' => []
        ],
        '+0.5⚽GOL' => [
            'green' => 0, 
            'red' => 0,
            'lucro_coef_green' => 0,
            'lucro_coef_red' => 0,
            'registros_green' => [],
            'registros_red' => []
        ]
    ];
    
    // 🔄 Processar cada mensagem
    while ($row = $resultado->fetch_assoc()) {
        $titulo = $row['titulo'];
        $resultado_msg = $row['resultado'];
        $odds = floatval($row['odds']);
        
        // 🎯 Extrair referência do título
        $referencia = extrairReferencia($titulo);
        
        if ($referencia && isset($apostas[$referencia])) {
            if ($resultado_msg === 'GREEN') {
                $apostas[$referencia]['green']++;
                $apostas[$referencia]['registros_green'][] = $odds;
                // Lucro GREEN = (odds - 1) = coeficiente de ganho
                // Exemplo: odds 1.52 → coef = 0.52 → lucro = 0.52 × 100 = R$ 52
                $apostas[$referencia]['lucro_coef_green'] += ($odds - 1);
            } elseif ($resultado_msg === 'RED') {
                $apostas[$referencia]['red']++;
                $apostas[$referencia]['registros_red'][] = $odds;
                // Lucro RED = -1 = coeficiente de perda (100% da entrada)
                // Exemplo: RED → coef = -1 → perda = -1 × 100 = -R$ 100
                $apostas[$referencia]['lucro_coef_red'] += (-1);
            }
        }
    }
    
    // 🎯 Calcular totais gerais
    // ✅ IMPORTANTE: Apenas GREENs são contados no ganho total
    // REDs (perdas) NÃO são subtraídas - apenas GREENs somam ganhos
    $total_green = 0;
    $total_red = 0;
    $total_lucro_coef_green = 0;  // Soma apenas dos GREENs
    $total_lucro_coef_red = 0;     // Mantém em 0 - REDs não afetam o total
    
    foreach ($apostas as $aposta) {
        $total_green += $aposta['green'];
        $total_red += $aposta['red'];
        $total_lucro_coef_green += $aposta['lucro_coef_green'];  // Soma GREENs
        // ✅ NÃO SOMA RED - apenas para contagem
        // $total_lucro_coef_red += $aposta['lucro_coef_red'];
    }
    
    // 🎯 Retornar dados em JSON
    echo json_encode([
        'success' => true,
        'apostas' => [
            'aposta_1' => [
                'titulo' => '+1⚽GOL',
                'green' => $apostas['+1⚽GOL']['green'],
                'red' => $apostas['+1⚽GOL']['red'],
                'lucro_coef_green' => round($apostas['+1⚽GOL']['lucro_coef_green'], 2),
                'lucro_coef_red' => round($apostas['+1⚽GOL']['lucro_coef_red'], 2)
            ],
            'aposta_2' => [
                'titulo' => '+0.5⚽GOL',
                'green' => $apostas['+0.5⚽GOL']['green'],
                'red' => $apostas['+0.5⚽GOL']['red'],
                'lucro_coef_green' => round($apostas['+0.5⚽GOL']['lucro_coef_green'], 2),
                'lucro_coef_red' => round($apostas['+0.5⚽GOL']['lucro_coef_red'], 2)
            ],
            'aposta_3' => [
                'titulo' => '+1⛳️CANTOS',
                'green' => $apostas['+1⛳️CANTOS']['green'],
                'red' => $apostas['+1⛳️CANTOS']['red'],
                'lucro_coef_green' => round($apostas['+1⛳️CANTOS']['lucro_coef_green'], 2),
                'lucro_coef_red' => round($apostas['+1⛳️CANTOS']['lucro_coef_red'], 2)
            ]
        ],
        'total' => [
            'green' => $total_green,
            'red' => $total_red,
            'lucro_coef_green' => round($total_lucro_coef_green, 2),
            'lucro_coef_red' => round($total_lucro_coef_red, 2),
            'lucro_coef_liquido' => round($total_lucro_coef_green + $total_lucro_coef_red, 2)
        ],
        'data' => $data_hoje,
        'informacoes' => [
            'moeda_und' => 'R$',
            'calculo' => 'lucro_coef * valor_und (input)',
            'exemplo' => 'Se UND=R$100 e lucro_coef_green=0.50, lucro=R$50'
        ],
        'message' => "Placar do dia: $total_green Green, $total_red Red"
    ]);
    
    $conexao->close();
    
} catch (Exception $e) {
    // ❌ Retornar erro
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'apostas' => [],
        'total' => ['green' => 0, 'red' => 0]
    ]);
}

// 🔍 FUNÇÃO PARA EXTRAIR REFERÊNCIA DO TÍTULO
function extrairReferencia($titulo) {
    // 🎯 Extrair padrão: +X (com . opcional) + espaços + GOL/CANTOS
    // Ex: "+1 ⚽GOL", "+0.5 ⚽GOL", "+1 ⛳️ CANTOS"
    
    // Limpar titulo removendo espaços extras
    $titulo_limpo = trim($titulo);
    
    // ⚠️ DEBUG: Verificar o que está sendo processado
    // error_log("Processando: " . $titulo_limpo);
    
    // 📌 PADRÃO 1: +1 GOL (sem decimal) - com suporte a múltiplas variações de emoji
    // Suporta: ⚽, ⚽️ (com variante), 🎯, espaços variados
    if (preg_match('/\+1\s*[\p{Emoji_Presentation}\s]*(g|G)(o|O)(l|L|ls|LS)\s*(?:ASIA|FT|AS|AFL)?/u', $titulo_limpo) || 
        preg_match('/\+1\s*GOL\s*(?:ASIA|FT|AS|AFL)?/i', $titulo_limpo)) {
        return '+1⚽GOL';
    }
    
    // 📌 PADRÃO 2: +0.5 GOL (com decimal) - com suporte a múltiplas variações de emoji
    if (preg_match('/\+0\.?5\s*[\p{Emoji_Presentation}\s]*(g|G)(o|O)(l|L|ls|LS)\s*(?:ASIA|FT|AS|AFL)?/u', $titulo_limpo) ||
        preg_match('/\+0\.?5\s*GOL\s*(?:ASIA|FT|AS|AFL)?/i', $titulo_limpo)) {
        return '+0.5⚽GOL';
    }
    
    // 📌 PADRÃO 3: +1 CANTOS - com suporte a múltiplas variações de emoji
    if (preg_match('/\+1\s*[\p{Emoji_Presentation}\s]*(c|C)(a|A)(n|N)(t|T)(o|O)(s|S|)?\s*(?:ASIA|FT|AS|AFL)?/u', $titulo_limpo) ||
        preg_match('/\+1\s*CANTO?S?\s*(?:ASIA|FT|AS|AFL)?/i', $titulo_limpo)) {
        return '+1⛳️CANTOS';
    }
    
    // Fallback: tentar buscar por substrings exatas (case-insensitive)
    // Remove todos os emojis e espaços extras para comparação
    $titulo_sem_emojis = preg_replace('/[\p{Emoji_Presentation}]/u', '', $titulo_limpo);
    $titulo_sem_emojis = preg_replace('/\s+/', ' ', $titulo_sem_emojis); // Remove espaços múltiplos
    $titulo_sem_emojis = trim($titulo_sem_emojis);
    
    // Verificar padrões básicos
    if (stripos($titulo_sem_emojis, '+1') !== false && stripos($titulo_sem_emojis, 'GOL') !== false && 
        stripos($titulo_sem_emojis, '+0.5') === false) {
        return '+1⚽GOL';
    }
    
    if (stripos($titulo_sem_emojis, '+0.5') !== false && stripos($titulo_sem_emojis, 'GOL') !== false) {
        return '+0.5⚽GOL';
    }
    
    if (stripos($titulo_sem_emojis, '+1') !== false && stripos($titulo_sem_emojis, 'CANTO') !== false) {
        return '+1⛳️CANTOS';
    }
    
    $padroes_fallback = [
        '+1⚽GOL' => '+1⚽GOL',
        '+0.5⚽GOL' => '+0.5⚽GOL',
        '+1⛳️CANTOS' => '+1⛳️CANTOS',
        '+1 ⚽GOL' => '+1⚽GOL',
        '+0.5 ⚽GOL' => '+0.5⚽GOL',
        '+1 ⛳️CANTOS' => '+1⛳️CANTOS',
        '+1 GOLS' => '+1⚽GOL',
        '+0.5 GOLS' => '+0.5⚽GOL',
        '+1 CANTOS' => '+1⛳️CANTOS'
    ];
    
    foreach ($padroes_fallback as $buscar => $resultado) {
        if (stripos($titulo_limpo, $buscar) !== false) {
            return $resultado;
        }
    }
    
    return null;
}
?>

