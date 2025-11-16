<?php
// Cabeçalho JSON
header('Content-Type: application/json');

// ✅ INCLUIR CONFIGURAÇÃO CENTRALIZADA DO BANCO
require_once 'config.php';

$debug_info = [];
$log_file = __DIR__ . '/logs/obter-placar-dias-' . date('Y-m-d') . '.log';

// Função para registrar logs
function log_debug($msg) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $msg\n", FILE_APPEND);
}

log_debug("==== INICIANDO OBTER-PLACAR-DIAS ====");

try {
    // ✅ Verificar autenticação
    session_start();
    
    $debug_info['usuario_id'] = $_SESSION['usuario_id'] ?? 'não definido';
    log_debug("Usuario ID da sessão: " . $debug_info['usuario_id']);
    
    // ✅ Verificar conexão
    if ($conexao->connect_error) {
        log_debug("❌ Erro de conexão: " . $conexao->connect_error);
        throw new Exception("Erro de conexão: " . $conexao->connect_error);
    }
    
    log_debug("✅ Conexão com banco de dados OK");
    
    // 📅 Obter data do primeiro e último dia do mês atual
    $ano_atual = date('Y');
    $mes_atual = date('m');
    $primeiro_dia_mes = "$ano_atual-$mes_atual-01";
    $ultimo_dia_mes = date('Y-m-t', strtotime($primeiro_dia_mes));
    $total_dias_mes = date('t');
    
    $debug_info['periodo'] = "$primeiro_dia_mes a $ultimo_dia_mes";
    log_debug("Período solicitado: $primeiro_dia_mes a $ultimo_dia_mes");
    
    // 🔍 Buscar TODAS as mensagens do mês INTEIRO com título, resultado, odds E DATA
    // SEM filtro de id_usuario (igual ao obter-placar-dia.php)
    $query = "SELECT 
                DATE(data_criacao) as data_dia,
                titulo, 
                resultado, 
                odds 
              FROM bote 
              WHERE DATE(data_criacao) >= ? 
              AND DATE(data_criacao) <= ?
              ORDER BY data_criacao DESC";
    
    log_debug("Query: $query");
    log_debug("Parâmetros: primeira_data=$primeiro_dia_mes, última_data=$ultimo_dia_mes");
    
    $stmt = $conexao->prepare($query);
    
    if (!$stmt) {
        log_debug("❌ Erro ao preparar query: " . $conexao->error);
        throw new Exception("Erro ao preparar query: " . $conexao->error);
    }
    
    log_debug("✅ Query preparada com sucesso");
    
    $bind_result = $stmt->bind_param("ss", $primeiro_dia_mes, $ultimo_dia_mes);
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
    
    // 📊 Inicializar array para CADA DIA do mês
    // Estrutura: $dias_mes[$data] = ['apostas' => [...], 'totals' => {...}]
    $dias_mes = [];
    
    for ($dia = 1; $dia <= $total_dias_mes; $dia++) {
        $dia_formatado = str_pad($dia, 2, '0', STR_PAD_LEFT);
        $data_key = "$ano_atual-$mes_atual-$dia_formatado";
        
        $dias_mes[$data_key] = [
            'data_mysql' => $data_key,
            'data_exibicao' => $dia_formatado . '/' . $mes_atual . '/' . $ano_atual,
            'apostas' => [
                '+1⚽GOL' => [
                    'green' => 0, 
                    'red' => 0,
                    'lucro_coef_green' => 0,
                    'lucro_coef_red' => 0
                ],
                '+0.5⚽GOL' => [
                    'green' => 0, 
                    'red' => 0,
                    'lucro_coef_green' => 0,
                    'lucro_coef_red' => 0
                ],
                '+1⛳️CANTOS' => [
                    'green' => 0, 
                    'red' => 0,
                    'lucro_coef_green' => 0,
                    'lucro_coef_red' => 0
                ]
            ]
        ];
    }
    
    // 🔄 Processar cada registro e agregar por DIA
    while ($row = $resultado->fetch_assoc()) {
        $data_dia = $row['data_dia'];
        $titulo = $row['titulo'];
        $resultado_msg = $row['resultado'];
        $odds = floatval($row['odds']);
        
        log_debug("Processando: data=$data_dia, titulo=$titulo, resultado=$resultado_msg, odds=$odds");
        
        // Verificar se a data existe no array
        if (!isset($dias_mes[$data_dia])) {
            log_debug("  ⚠️ Data $data_dia não está no array esperado");
            continue;
        }
        
        // 🎯 Extrair referência do título
        $referencia = extrairReferencia($titulo);
        
        log_debug("  Referência extraída: " . ($referencia ?? 'NENHUMA'));
        
        if ($referencia && isset($dias_mes[$data_dia]['apostas'][$referencia])) {
            if ($resultado_msg === 'GREEN') {
                $dias_mes[$data_dia]['apostas'][$referencia]['green']++;
                $dias_mes[$data_dia]['apostas'][$referencia]['lucro_coef_green'] += ($odds - 1);
                log_debug("  ✅ GREEN registrado para $referencia em $data_dia");
            } elseif ($resultado_msg === 'RED') {
                $dias_mes[$data_dia]['apostas'][$referencia]['red']++;
                $dias_mes[$data_dia]['apostas'][$referencia]['lucro_coef_red'] -= 1;
                log_debug("  ✅ RED registrado para $referencia em $data_dia");
            }
        } else {
            log_debug("  ⚠️ Referência não encontrada ou não está em apostas esperadas");
        }
    }
    
    // 🎯 Formatar resposta: retornar cada dia com seus totais
    $dias_formatados = [];
    
    foreach ($dias_mes as $data_mysql => $dia_data) {
        // Calcular totais do dia
        $total_green = 0;
        $total_red = 0;
        $total_lucro_coef_green = 0;
        $total_lucro_coef_red = 0;
        
        foreach ($dia_data['apostas'] as $aposta) {
            $total_green += $aposta['green'];
            $total_red += $aposta['red'];
            $total_lucro_coef_green += $aposta['lucro_coef_green'];
            $total_lucro_coef_red += $aposta['lucro_coef_red'];
        }
        
        // Calcular saldo do dia (mesmo como obter-placar-mes.php)
        $saldo_dia = ($total_lucro_coef_green + $total_lucro_coef_red) * 100;
        
        // Adicionar à resposta
        $dias_formatados[] = [
            'data_mysql' => $data_mysql,
            'data_exibicao' => $dia_data['data_exibicao'],
            'green' => $total_green,
            'red' => $total_red,
            'lucro_coef_green' => round($total_lucro_coef_green, 2),
            'lucro_coef_red' => round($total_lucro_coef_red, 2),
            'lucro_coef_liquido' => round($total_lucro_coef_green + $total_lucro_coef_red, 2),
            'saldo' => round($saldo_dia, 2),
            'apostas' => $dia_data['apostas']
        ];
    }
    
    log_debug("✅ Resposta formatada com sucesso. Total de dias: " . count($dias_formatados));
    
    // 🎯 Retornar dados em JSON
    echo json_encode([
        'success' => true,
        'dias' => $dias_formatados,
        'periodo' => "Mês de " . date('F Y', strtotime($primeiro_dia_mes)),
        'debug' => $debug_info,
        'informacoes' => [
            'moeda_und' => 'R$',
            'calculo' => '(lucro_coef_green + lucro_coef_red) * 100',
            'descricao' => 'Agregação por DIA do mês com mesmo método que Bloco 1'
        ]
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
        'dias' => [],
        'periodo' => 'Erro'
    ]);
}

// 🔍 FUNÇÃO PARA EXTRAIR REFERÊNCIA DO TÍTULO
function extrairReferencia($titulo) {
    // 🎯 Extrair padrão: +X (com . opcional) + espaços + GOL/CANTOS
    
    $titulo_limpo = trim($titulo);
    
    // 📌 PADRÃO 1: +1 GOL
    if (preg_match('/\+1\s*[\p{Emoji_Presentation}\s]*(g|G)(o|O)(l|L|ls|LS)\s*(?:ASIA|FT|AS|AFL)?/u', $titulo_limpo) || 
        preg_match('/\+1\s*GOL\s*(?:ASIA|FT|AS|AFL)?/i', $titulo_limpo)) {
        return '+1⚽GOL';
    }
    
    // 📌 PADRÃO 2: +0.5 GOL
    if (preg_match('/\+0\.?5\s*[\p{Emoji_Presentation}\s]*(g|G)(o|O)(l|L|ls|LS)\s*(?:ASIA|FT|AS|AFL)?/u', $titulo_limpo) ||
        preg_match('/\+0\.?5\s*GOL\s*(?:ASIA|FT|AS|AFL)?/i', $titulo_limpo)) {
        return '+0.5⚽GOL';
    }
    
    // 📌 PADRÃO 3: +1 CANTOS
    if (preg_match('/\+1\s*[\p{Emoji_Presentation}\s]*(c|C)(a|A)(n|N)(t|T)(o|O)(s|S|)?\s*(?:ASIA|FT|AS|AFL)?/u', $titulo_limpo) ||
        preg_match('/\+1\s*CANTO?S?\s*(?:ASIA|FT|AS|AFL)?/i', $titulo_limpo)) {
        return '+1⛳️CANTOS';
    }
    
    // Fallback
    $titulo_sem_emojis = preg_replace('/[\p{Emoji_Presentation}]/u', '', $titulo_limpo);
    $titulo_sem_emojis = preg_replace('/\s+/', ' ', $titulo_sem_emojis);
    $titulo_sem_emojis = trim($titulo_sem_emojis);
    
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
