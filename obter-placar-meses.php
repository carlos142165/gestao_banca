<?php
// Cabeçalho JSON
header('Content-Type: application/json');

// ✅ INCLUIR CONFIGURAÇÃO CENTRALIZADA DO BANCO
require_once 'config.php';

$debug_info = [];
$log_file = __DIR__ . '/logs/obter-placar-meses-' . date('Y-m-d') . '.log';

// Função para registrar logs
function log_debug($msg) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $msg\n", FILE_APPEND);
}

log_debug("==== INICIANDO OBTER-PLACAR-MESES ====");

try {
    // ✅ Verificar autenticação
    session_start();
    
    $debug_info['usuario_id'] = $_SESSION['usuario_id'] ?? 'não definido';
    log_debug("Usuario ID da sessão: " . $debug_info['usuario_id']);
    
    if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
        log_debug("❌ Usuário não autenticado");
        throw new Exception("Usuário não autenticado");
    }
    
    $id_usuario = $_SESSION['usuario_id'];
    
    // ✅ Verificar conexão
    if ($conexao->connect_error) {
        log_debug("❌ Erro de conexão: " . $conexao->connect_error);
        throw new Exception("Erro de conexão: " . $conexao->connect_error);
    }
    
    log_debug("✅ Conexão com banco de dados OK");
    
    // 📅 Array com nomes dos meses em português
    $meses_nomes = [
        1 => 'JANEIRO', 2 => 'FEVEREIRO', 3 => 'MARÇO', 4 => 'ABRIL',
        5 => 'MAIO', 6 => 'JUNHO', 7 => 'JULHO', 8 => 'AGOSTO',
        9 => 'SETEMBRO', 10 => 'OUTUBRO', 11 => 'NOVEMBRO', 12 => 'DEZEMBRO'
    ];
    
    $ano_atual = date('Y');
    $meses_data = [];
    
    // 🔄 PROCESSAR CADA MÊS
    for ($mes = 1; $mes <= 12; $mes++) {
        $primeiro_dia = "$ano_atual-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-01";
        $ultimo_dia = date('Y-m-t', strtotime($primeiro_dia));
        
        log_debug("Processando mês: $mes ($primeiro_dia a $ultimo_dia)");
        
        // 🔍 Buscar TODAS as mensagens do mês com título, resultado E ODDS
        $query = "SELECT titulo, resultado, odds FROM bote 
                  WHERE DATE(data_criacao) >= ? 
                  AND DATE(data_criacao) <= ?
                  ORDER BY data_criacao DESC";
        
        $stmt = $conexao->prepare($query);
        
        if (!$stmt) {
            log_debug("❌ Erro ao preparar query para mês $mes: " . $conexao->error);
            continue;
        }
        
        $stmt->bind_param("ss", $primeiro_dia, $ultimo_dia);
        $exec_result = $stmt->execute();
        
        if (!$exec_result) {
            log_debug("❌ Erro ao executar query para mês $mes: " . $stmt->error);
            $stmt->close();
            continue;
        }
        
        $resultado = $stmt->get_result();
        
        // 📊 Inicializar array com as 3 apostas
        $apostas = [
            '+1⚽GOL' => [
                'green' => 0, 
                'red' => 0,
                'lucro_coef_green' => 0,
                'lucro_coef_red' => 0,
            ],
            '+0.5⚽GOL' => [
                'green' => 0, 
                'red' => 0,
                'lucro_coef_green' => 0,
                'lucro_coef_red' => 0,
            ],
            '+1⛳️CANTOS' => [
                'green' => 0, 
                'red' => 0,
                'lucro_coef_green' => 0,
                'lucro_coef_red' => 0,
            ]
        ];
        
        // 🔄 Processar cada mensagem do mês
        while ($row = $resultado->fetch_assoc()) {
            $titulo = $row['titulo'];
            $resultado_msg = $row['resultado'];
            $odds = floatval($row['odds']);
            
            // 🎯 Extrair referência do título
            $referencia = extrairReferencia($titulo);
            
            if ($referencia && isset($apostas[$referencia])) {
                if ($resultado_msg === 'GREEN') {
                    $apostas[$referencia]['green']++;
                    // Lucro GREEN = (odds - 1) por unidade
                    $apostas[$referencia]['lucro_coef_green'] += ($odds - 1);
                } elseif ($resultado_msg === 'RED') {
                    $apostas[$referencia]['red']++;
                    // Lucro RED = -1 por unidade (perda)
                    $apostas[$referencia]['lucro_coef_red'] -= 1;
                }
            }
        }
        
        // 🎯 Calcular totais do mês
        $total_green = 0;
        $total_red = 0;
        $total_lucro_coef_green = 0;
        $total_lucro_coef_red = 0;
        
        foreach ($apostas as $aposta) {
            $total_green += $aposta['green'];
            $total_red += $aposta['red'];
            $total_lucro_coef_green += $aposta['lucro_coef_green'];
            $total_lucro_coef_red += $aposta['lucro_coef_red'];
        }
        
        // 💰 Calcular valor final do mês (lucro_coef * 100)
        $valor_final = ($total_lucro_coef_green + $total_lucro_coef_red) * 100;
        
        // 📦 Adicionar ao array de meses
        $meses_data[] = [
            'numero' => $mes,
            'nome_mes' => $meses_nomes[$mes],
            'green' => $total_green,
            'red' => $total_red,
            'lucro_coef_green' => round($total_lucro_coef_green, 2),
            'lucro_coef_red' => round($total_lucro_coef_red, 2),
            'valor_final' => round($valor_final, 2),
            'periodo' => "$primeiro_dia a $ultimo_dia"
        ];
        
        log_debug("Mês $mes: $total_green Green, $total_red Red, Valor Final: R$ " . number_format($valor_final, 2, ',', '.'));
        
        $stmt->close();
    }
    
    // 🎯 Retornar dados em JSON
    echo json_encode([
        'success' => true,
        'meses' => $meses_data,
        'total_meses' => count($meses_data),
        'ano' => $ano_atual,
        'debug' => $debug_info,
        'informacoes' => [
            'moeda_und' => 'R$',
            'calculo' => '(lucro_coef_green + lucro_coef_red) * 100',
            'descricao' => 'Lista de meses com dados agregados do período total'
        ],
        'message' => "Placar de meses carregado com sucesso"
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    $conexao->close();
    
} catch (Exception $e) {
    // ❌ Retornar erro
    log_debug("❌ EXCEÇÃO CAPTURADA: " . $e->getMessage());
    log_debug("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => $debug_info ?? [],
        'meses' => [],
        'total_meses' => 0
    ]);
}

// 🔍 FUNÇÃO PARA EXTRAIR REFERÊNCIA DO TÍTULO (IDÊNTICA AO BLOCO 2)
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
    $titulo_sem_emojis = preg_replace('/[\p{Emoji_Presentation}]/u', '', $titulo_limpo);
    $titulo_sem_emojis = preg_replace('/\s+/', ' ', $titulo_sem_emojis);
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
    
    return null;
}
?>
