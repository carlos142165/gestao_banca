<?php
// 🔧 CONFIGURAÇÕES DE CONEXÃO COM O BANCO DE DADOS
define('DB_HOST', '127.0.0.1');
define('DB_USERNAME', 'u857325944_formu');
define('DB_PASSWORD', 'JkF4B7N1');
define('DB_NAME', 'u857325944_formu');

// Cabeçalho JSON
header('Content-Type: application/json; charset=utf-8');

try {
    // 🔗 Conectar ao banco de dados
    $conexao = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
    
    // ✅ Verificar conexão
    if ($conexao->connect_error) {
        throw new Exception("Erro de conexão: " . $conexao->connect_error);
    }
    
    // 📅 Obter data de hoje (formato Y-m-d)
    $data_hoje = date('Y-m-d');
    
    // 🔍 Buscar todas as mensagens do dia com título e resultado
    $query = "SELECT id, titulo, resultado, data_criacao FROM bote 
              WHERE DATE(data_criacao) = '$data_hoje'
              ORDER BY data_criacao DESC";
    
    $resultado = $conexao->query($query);
    
    if (!$resultado) {
        throw new Exception("Erro na query: " . $conexao->error);
    }
    
    // 📊 Inicializar array com as 3 apostas
    $apostas = [
        '+1⚽GOL' => ['green' => 0, 'red' => 0],
        '+1⛳️CANTOS' => ['green' => 0, 'red' => 0],
        '+0.5⚽GOL' => ['green' => 0, 'red' => 0]
    ];
    
    // 🔄 Processar cada mensagem
    $registros_processados = [];
    $registros_nao_identificados = [];
    
    while ($row = $resultado->fetch_assoc()) {
        $id = $row['id'];
        $titulo = $row['titulo'];
        $resultado_msg = $row['resultado'];
        $data_criacao = $row['data_criacao'];
        
        // 🎯 Extrair referência do título
        $referencia = extrairReferencia($titulo);
        
        $registro = [
            'id' => $id,
            'titulo' => $titulo,
            'resultado' => $resultado_msg,
            'referencia_detectada' => $referencia,
            'data_criacao' => $data_criacao,
            'processado' => false
        ];
        
        if ($referencia && isset($apostas[$referencia])) {
            if ($resultado_msg === 'GREEN') {
                $apostas[$referencia]['green']++;
                $registro['processado'] = true;
                $registro['acao'] = "Incrementado GREEN para $referencia";
            } elseif ($resultado_msg === 'RED') {
                $apostas[$referencia]['red']++;
                $registro['processado'] = true;
                $registro['acao'] = "Incrementado RED para $referencia";
            } else {
                $registro['acao'] = "Resultado inválido: $resultado_msg";
                $registros_nao_identificados[] = $registro;
            }
        } else {
            $registro['acao'] = "Referência não identificada ou não reconhecida";
            $registros_nao_identificados[] = $registro;
        }
        
        $registros_processados[] = $registro;
    }
    
    // 🎯 Calcular totais gerais
    $total_green = 0;
    $total_red = 0;
    foreach ($apostas as $aposta) {
        $total_green += $aposta['green'];
        $total_red += $aposta['red'];
    }
    
    // 🎯 Retornar dados em JSON
    $resposta = [
        'success' => true,
        'data_filtro' => $data_hoje,
        'resumo' => [
            'total_registros' => count($registros_processados),
            'registros_processados' => count(array_filter($registros_processados, function($r) { return $r['processado']; })),
            'registros_nao_identificados' => count($registros_nao_identificados)
        ],
        'apostas' => [
            'aposta_1' => [
                'titulo' => '+1⚽GOL',
                'green' => $apostas['+1⚽GOL']['green'],
                'red' => $apostas['+1⚽GOL']['red']
            ],
            'aposta_2' => [
                'titulo' => '+0.5⚽GOL',
                'green' => $apostas['+0.5⚽GOL']['green'],
                'red' => $apostas['+0.5⚽GOL']['red']
            ],
            'aposta_3' => [
                'titulo' => '+1⛳️CANTOS',
                'green' => $apostas['+1⛳️CANTOS']['green'],
                'red' => $apostas['+1⛳️CANTOS']['red']
            ]
        ],
        'total' => [
            'green' => $total_green,
            'red' => $total_red
        ],
        'registros_detalhados' => $registros_processados,
        'registros_com_problema' => $registros_nao_identificados,
        'message' => "Placar do dia: $total_green Green, $total_red Red"
    ];
    
    echo json_encode($resposta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
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
    
    // 📌 PADRÃO 1: +1 GOL (sem decimal) - com suporte a múltiplas variações de emoji
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
