<?php
// Cabeçalho JSON
header('Content-Type: application/json');

// ✅ INCLUIR CONFIGURAÇÃO CENTRALIZADA DO BANCO
require_once 'config.php';

$debug_info = [];
$log_file = __DIR__ . '/logs/obter-placar-periodo-' . date('Y-m-d') . '.log';

// Função para registrar logs
function log_debug($msg) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $msg\n", FILE_APPEND);
}

log_debug("==== INICIANDO OBTER-PLACAR-PERIODO ====");

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
    
    // 📅 Obter TODO O PERÍODO (do ano inteiro - janeiro a dezembro)
    $ano_atual = date('Y');
    $primeiro_dia_periodo = "$ano_atual-01-01";
    $ultimo_dia_periodo = "$ano_atual-12-31";
    
    $debug_info['periodo'] = "$primeiro_dia_periodo a $ultimo_dia_periodo";
    $debug_info['id_usuario'] = $id_usuario;
    log_debug("Período solicitado: $primeiro_dia_periodo a $ultimo_dia_periodo para usuario_id=$id_usuario");
    
    // 🔍 Buscar TODAS as mensagens do PERÍODO INTEIRO com título, resultado E ODDS
    // MESMO MÉTODO QUE BLOCO 2
    $query = "SELECT titulo, resultado, odds FROM bote 
              WHERE DATE(data_criacao) >= ? 
              AND DATE(data_criacao) <= ?
              ORDER BY data_criacao DESC";
    
    log_debug("Query: $query");
    log_debug("Parâmetros: primeira_data=$primeiro_dia_periodo, última_data=$ultimo_dia_periodo");
    
    $stmt = $conexao->prepare($query);
    
    if (!$stmt) {
        log_debug("❌ Erro ao preparar query: " . $conexao->error);
        throw new Exception("Erro ao preparar query: " . $conexao->error);
    }
    
    log_debug("✅ Query preparada com sucesso");
    
    $bind_result = $stmt->bind_param("ss", $primeiro_dia_periodo, $ultimo_dia_periodo);
    log_debug("bind_param resultado: " . ($bind_result ? "true" : "false"));
    
    if (!$bind_result) {
        log_debug("❌ Erro ao fazer bind_param: " . $stmt->error);
        throw new Exception("Erro ao fazer bind_param: " . $stmt->error);
    }
    
    log_debug("✅ bind_param executado com sucesso");
    
    $exec_result = $stmt->execute();
    log_debug("execute() resultado: " . ($exec_result ? "true" : "false"));
    
    if (!$exec_result) {
        log_debug("❌ Erro ao executar query: " . $stmt->error);
        throw new Exception("Erro ao executar query: " . $stmt->error);
    }
    
    log_debug("✅ Query executada com sucesso");
    
    $resultado = $stmt->get_result();
    
    if (!$resultado) {
        log_debug("❌ Erro ao obter resultado: " . $stmt->error);
        throw new Exception("Erro ao obter resultado: " . $stmt->error);
    }
    
    log_debug("✅ Resultado obtido com sucesso");
    
    $total_registros = $resultado->num_rows;
    $debug_info['total_registros'] = $total_registros;
    log_debug("Total de registros encontrados: $total_registros");
    
    // 📊 Inicializar array com as 3 apostas (EXATAMENTE COMO BLOCO 2)
    $apostas = [
        '+1⚽GOL' => [
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
        ],
        '+1⛳️CANTOS' => [
            'green' => 0, 
            'red' => 0,
            'lucro_coef_green' => 0,
            'lucro_coef_red' => 0,
            'registros_green' => [],
            'registros_red' => []
        ]
    ];
    
    // 🔄 Processar cada mensagem (MESMO ALGORITMO DO BLOCO 2)
    while ($row = $resultado->fetch_assoc()) {
        $titulo = $row['titulo'];
        $resultado_msg = $row['resultado'];
        $odds = floatval($row['odds']);
        
        log_debug("Processando: titulo=$titulo, resultado=$resultado_msg, odds=$odds");
        
        // 🎯 Extrair referência do título
        $referencia = extrairReferencia($titulo);
        
        log_debug("  Referência extraída: " . ($referencia ?? 'NENHUMA'));
        
        if ($referencia && isset($apostas[$referencia])) {
            if ($resultado_msg === 'GREEN') {
                $apostas[$referencia]['green']++;
                $apostas[$referencia]['registros_green'][] = $odds;
                // Lucro GREEN = (odds - 1) por unidade
                $apostas[$referencia]['lucro_coef_green'] += ($odds - 1);
                log_debug("  ✅ GREEN registrado para $referencia: lucro_coef_green += " . ($odds - 1));
            } elseif ($resultado_msg === 'RED') {
                $apostas[$referencia]['red']++;
                $apostas[$referencia]['registros_red'][] = $odds;
                // Lucro RED = -1 por unidade (perda)
                $apostas[$referencia]['lucro_coef_red'] -= 1;
                log_debug("  ✅ RED registrado para $referencia: lucro_coef_red -= 1");
            }
        } else {
            log_debug("  ⚠️ Referência não encontrada ou não está em apostas esperadas");
        }
    }
    
    // 🎯 Calcular totais gerais (MESMO MÉTODO DO BLOCO 2)
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
    
    log_debug("Totais calculados:");
    log_debug("  Total Green: $total_green");
    log_debug("  Total Red: $total_red");
    log_debug("  Total Lucro Coef Green: $total_lucro_coef_green");
    log_debug("  Total Lucro Coef Red: $total_lucro_coef_red");
    log_debug("  Lucro Total: " . ($total_lucro_coef_green + $total_lucro_coef_red));
    
    // 🎯 Retornar dados em JSON (COMPATÍVEL COM BLOCO 2)
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
        'data' => $ultimo_dia_periodo,
        'periodo' => "Período Total - Ano $ano_atual",
        'debug' => $debug_info,
        'informacoes' => [
            'moeda_und' => 'R$',
            'calculo' => '(lucro_coef_green + lucro_coef_red) * 100',
            'descricao' => 'Agregação de TODO o período (janeiro a dezembro) com mesmo método que Bloco 2'
        ],
        'message' => "Placar do período: $total_green Green, $total_red Red"
    ]);
    
    $stmt->close();
    $conexao->close();
    
} catch (Exception $e) {
    // ❌ Retornar erro
    log_debug("❌ EXCEÇÃO CAPTURADA: " . $e->getMessage());
    log_debug("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => $debug_info ?? [],
        'apostas' => [],
        'total' => ['green' => 0, 'red' => 0]
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
