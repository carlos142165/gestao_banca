<?php
// ✅ ARQUIVO DADOS_BANCA.PHP - OTIMIZADO PARA PERÍODOS

require_once 'config.php';
require_once 'carregar_sessao.php';

$id_usuario = $_SESSION['usuario_id'] ?? null;
if (!$id_usuario) {
    echo json_encode(['success' => false, 'message' => 'Usuário não logado']);
    exit;
}

// Suas funções existentes (NÃO ALTERADAS)
function getSoma($conexao, $campo, $id_usuario) {
    $stmt = $conexao->prepare("SELECT SUM($campo) FROM controle WHERE id_usuario = ? AND $campo > 0");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $stmt->bind_result($total);
    $stmt->fetch();
    $stmt->close();
    return $total ?? 0;
}

function getUltimoCampo($conexao, $campo, $id_usuario) {
    $stmt = $conexao->prepare("
        SELECT $campo FROM controle
        WHERE id_usuario = ? AND $campo IS NOT NULL
        ORDER BY id DESC LIMIT 1
    ");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $stmt->bind_result($valor);
    $stmt->fetch();
    $stmt->close();
    return $valor;
}

// ✅ FUNÇÃO PARA CALCULAR LUCRO (NÃO ALTERADA)
function calcularLucro($conexao, $id_usuario) {
    $stmt = $conexao->prepare("
        SELECT 
            COALESCE(SUM(valor_green), 0),
            COALESCE(SUM(valor_red), 0)
        FROM valor_mentores
        WHERE id_usuario = ?
    ");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $stmt->bind_result($total_green, $total_red);
    $stmt->fetch();
    $stmt->close();
    
    return [
        'green' => $total_green,
        'red' => $total_red,
        'lucro' => $total_green - $total_red
    ];
}

// ✅ FUNÇÃO PARA CALCULAR META DIÁRIA (NÃO ALTERADA)
function calcularMetaDiaria($conexao, $id_usuario, $total_deposito, $total_saque) {
    try {
        // Meta baseada apenas na banca (sem lucro)
        $saldo_banca_para_meta = $total_deposito - $total_saque;
        
        // Buscar os últimos valores de diária e unidade
        $stmt = $conexao->prepare("
            SELECT diaria, unidade 
            FROM controle 
            WHERE id_usuario = ? AND diaria IS NOT NULL AND unidade IS NOT NULL 
            ORDER BY id DESC 
            LIMIT 1
        ");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $stmt->bind_result($diaria, $unidade);
        $stmt->fetch();
        $stmt->close();
        
        // Valores padrão se não encontrar
        if ($diaria === null) $diaria = 2.00;
        if ($unidade === null) $unidade = 2;
        
        // CÁLCULO: (deposito - saque) * (diaria/100) * unidade
        $porcentagem_decimal = $diaria / 100;
        $meta_diaria = $saldo_banca_para_meta * $porcentagem_decimal * $unidade;
        
        return [
            'meta_diaria' => $meta_diaria,
            'diaria_usada' => $diaria,
            'unidade_usada' => $unidade,
            'saldo_banca_meta' => $saldo_banca_para_meta
        ];
        
    } catch (Exception $e) {
        error_log("Erro ao calcular meta diária: " . $e->getMessage());
        return [
            'meta_diaria' => 0,
            'diaria_usada' => 2,
            'unidade_usada' => 2,
            'saldo_banca_meta' => 0
        ];
    }
}

// ✅ FUNÇÃO PARA CALCULAR DADOS DA ÁREA DIREITA (NÃO ALTERADA)
function calcularAreaDireita($conexao, $id_usuario, $saldo_banca_total) {
    try {
        // Buscar última diária cadastrada
        $ultima_diaria = getUltimoCampo($conexao, 'diaria', $id_usuario);
        $diaria = $ultima_diaria ?? 2.00;
        
        // Calcular unidade de entrada: saldo_total * (diária / 100)
        $unidade_entrada = $saldo_banca_total * ($diaria / 100);
        
        return [
            'diaria_porcentagem' => $diaria,
            'saldo_banca_total' => $saldo_banca_total,
            'unidade_entrada' => $unidade_entrada,
            'diaria_formatada' => number_format($diaria, 0) . '%',
            'unidade_entrada_formatada' => 'R$ ' . number_format($unidade_entrada, 2, ',', '.')
        ];
        
    } catch (Exception $e) {
        error_log("Erro ao calcular área direita: " . $e->getMessage());
        return [
            'diaria_porcentagem' => 2,
            'saldo_banca_total' => 0,
            'unidade_entrada' => 0,
            'diaria_formatada' => '2%',
            'unidade_entrada_formatada' => 'R$ 0,00'
        ];
    }
}

// ✅ FUNÇÃO OTIMIZADA PARA CALCULAR DIAS RESTANTES
function calcularDiasRestantes() {
    $hoje = new DateTime();
    $agora = $hoje->format('Y-m-d H:i:s');
    
    // Dias restantes do mês (incluindo hoje)
    $diaAtual = (int)$hoje->format('d');
    $ultimoDiaMes = (int)$hoje->format('t');
    $diasRestantesMes = $ultimoDiaMes - $diaAtual + 1;
    
    // Dias restantes do ano (incluindo hoje)
    $fimAno = new DateTime($hoje->format('Y') . '-12-31 23:59:59');
    $diferenca = $hoje->diff($fimAno);
    $diasRestantesAno = $diferenca->days + 1;
    
    return [
        'mes' => $diasRestantesMes,
        'ano' => $diasRestantesAno,
        'info' => [
            'data_atual' => $hoje->format('Y-m-d'),
            'dia_atual' => $diaAtual,
            'ultimo_dia_mes' => $ultimoDiaMes,
            'mes_atual' => $hoje->format('m'),
            'ano_atual' => $hoje->format('Y'),
            'calculo_mes' => "Restam {$diasRestantesMes} de {$ultimoDiaMes} dias do mês",
            'calculo_ano' => "Restam {$diasRestantesAno} dias do ano " . $hoje->format('Y')
        ]
    ];
}

// ✅ FUNÇÃO PRINCIPAL PARA CALCULAR METAS POR PERÍODO
function calcularMetasPorPeriodo($meta_diaria) {
    $diasRestantes = calcularDiasRestantes();
    
    $meta_mensal = $meta_diaria * $diasRestantes['mes'];
    $meta_anual = $meta_diaria * $diasRestantes['ano'];
    
    return [
        // Metas calculadas
        'meta_diaria' => $meta_diaria,
        'meta_mensal' => $meta_mensal,
        'meta_anual' => $meta_anual,
        
        // Dias restantes
        'dias_restantes_mes' => $diasRestantes['mes'],
        'dias_restantes_ano' => $diasRestantes['ano'],
        
        // Formatações para exibição
        'meta_diaria_formatada' => 'R$ ' . number_format($meta_diaria, 2, ',', '.'),
        'meta_mensal_formatada' => 'R$ ' . number_format($meta_mensal, 2, ',', '.'),
        'meta_anual_formatada' => 'R$ ' . number_format($meta_anual, 2, ',', '.'),
        
        // Informações detalhadas
        'periodo_info' => [
            'data_hoje' => $diasRestantes['info']['data_atual'],
            'mes_atual' => $diasRestantes['info']['mes_atual'],
            'ano_atual' => $diasRestantes['info']['ano_atual'],
            'dia_atual' => $diasRestantes['info']['dia_atual'],
            'ultimo_dia_mes' => $diasRestantes['info']['ultimo_dia_mes'],
            'calculo_mes' => $diasRestantes['info']['calculo_mes'],
            'calculo_ano' => $diasRestantes['info']['calculo_ano'],
            
            // Fórmulas de cálculo
            'formula_diaria' => "Meta Diária: R$ " . number_format($meta_diaria, 2, ',', '.'),
            'formula_mensal' => "Meta Mensal: R$ " . number_format($meta_diaria, 2, ',', '.') . " × {$diasRestantes['mes']} dias = R$ " . number_format($meta_mensal, 2, ',', '.'),
            'formula_anual' => "Meta Anual: R$ " . number_format($meta_diaria, 2, ',', '.') . " × {$diasRestantes['ano']} dias = R$ " . number_format($meta_anual, 2, ',', '.')
        ]
    ];
}

// Processar requisições POST (cadastros) - NÃO ALTERADO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    try {
        $acao = $input['acao'] ?? '';
        $valor = floatval($input['valor'] ?? 0);
        $diaria = floatval($input['diaria'] ?? 2);
        $unidade = intval($input['unidade'] ?? 2);
        $odds = floatval($input['odds'] ?? 1.5);
        
        $stmt = null;
        
        switch ($acao) {
            case 'deposito':
                $stmt = $conexao->prepare("INSERT INTO controle (id_usuario, deposito, diaria, unidade, odds, data_cadastro) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("idddi", $id_usuario, $valor, $diaria, $unidade, $odds);
                break;
                
            case 'saque':
                $stmt = $conexao->prepare("INSERT INTO controle (id_usuario, saque, diaria, unidade, odds, data_cadastro) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("idddi", $id_usuario, $valor, $diaria, $unidade, $odds);
                break;
                
            case 'alterar':
                $stmt = $conexao->prepare("INSERT INTO controle (id_usuario, diaria, unidade, odds, data_cadastro) VALUES (?, ?, ?, ?, NOW())");
                $stmt->bind_param("iddi", $id_usuario, $diaria, $unidade, $odds);
                break;
                
            case 'resetar':
                // Inserir registro de reset
                $stmt = $conexao->prepare("INSERT INTO controle (id_usuario, reset_banca, data_cadastro) VALUES (?, 1, NOW())");
                $stmt->bind_param("i", $id_usuario);
                $stmt->execute();
                
                // Limpar dados da tabela valor_mentores
                $stmt2 = $conexao->prepare("DELETE FROM valor_mentores WHERE id_usuario = ?");
                $stmt2->bind_param("i", $id_usuario);
                $stmt2->execute();
                $stmt2->close();
                break;
        }
        
        if ($stmt) {
            $result = $stmt->execute();
            $stmt->close();
            
            if (!$result) {
                throw new Exception("Erro ao executar operação");
            }
        }
        
        // ✅ CALCULAR VALORES APÓS OPERAÇÃO
        $total_deposito = getSoma($conexao, 'deposito', $id_usuario);
        $total_saque = getSoma($conexao, 'saque', $id_usuario);
        
        // Calcular lucro
        $dados_lucro = calcularLucro($conexao, $id_usuario);
        $lucro = $dados_lucro['lucro'];
        
        // Saldo total da banca
        $saldo_banca_total = $total_deposito - $total_saque + $lucro;
        
        // Calcular meta baseada apenas em (depósito - saque)
        $meta_resultado = calcularMetaDiaria($conexao, $id_usuario, $total_deposito, $total_saque);
        
        // Calcular dados para área direita
        $area_direita = calcularAreaDireita($conexao, $id_usuario, $saldo_banca_total);
        
        // ✅ CALCULAR METAS POR PERÍODO
        $metas_periodo = calcularMetasPorPeriodo($meta_resultado['meta_diaria']);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Operação realizada com sucesso',
            
            // Dados principais
            'banca' => $saldo_banca_total,
            'banca_formatada' => 'R$ ' . number_format($saldo_banca_total, 2, ',', '.'),
            'lucro' => $lucro,
            'lucro_formatado' => 'R$ ' . number_format($lucro, 2, ',', '.'),
            
            // Meta diária
            'meta_diaria' => $meta_resultado['meta_diaria'],
            'meta_diaria_formatada' => 'R$ ' . number_format($meta_resultado['meta_diaria'], 2, ',', '.'),
            'meta_diaria_brl' => 'R$ ' . number_format($meta_resultado['meta_diaria'], 2, ',', '.'),
            'diaria_atual' => $meta_resultado['diaria_usada'],
            'unidade_atual' => $meta_resultado['unidade_usada'],
            'saldo_base_meta' => $meta_resultado['saldo_banca_meta'],
            
            // Dados para área direita
            'diaria_formatada' => $area_direita['diaria_formatada'],
            'unidade_entrada_formatada' => $area_direita['unidade_entrada_formatada'],
            'diaria_raw' => $area_direita['diaria_porcentagem'],
            'saldo_banca_total' => $area_direita['saldo_banca_total'],
            'unidade_entrada_raw' => $area_direita['unidade_entrada'],
            
            // ✅ METAS POR PERÍODO (OTIMIZADO)
            'metas_periodo' => $metas_periodo,
            'meta_mensal' => $metas_periodo['meta_mensal'],
            'meta_mensal_formatada' => $metas_periodo['meta_mensal_formatada'],
            'meta_anual' => $metas_periodo['meta_anual'], 
            'meta_anual_formatada' => $metas_periodo['meta_anual_formatada'],
            'dias_restantes_mes' => $metas_periodo['dias_restantes_mes'],
            'dias_restantes_ano' => $metas_periodo['dias_restantes_ano'],
            'periodo_info' => $metas_periodo['periodo_info']
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ✅ PROCESSAR REQUISIÇÕES GET (CONSULTAS)
try {
    $total_deposito = getSoma($conexao, 'deposito', $id_usuario);
    $total_saque = getSoma($conexao, 'saque', $id_usuario);
    
    // Calcular lucro
    $dados_lucro = calcularLucro($conexao, $id_usuario);
    $total_green = $dados_lucro['green'];
    $total_red = $dados_lucro['red'];
    $lucro = $dados_lucro['lucro'];
    
    // Saldo total da banca
    $saldo_banca_total = $total_deposito - $total_saque + $lucro;
    
    // Buscar últimos valores de configuração
    $ultima_diaria = getUltimoCampo($conexao, 'diaria', $id_usuario);
    $ultima_unidade = getUltimoCampo($conexao, 'unidade', $id_usuario);
    $ultima_odds = getUltimoCampo($conexao, 'odds', $id_usuario);
    
    // Calcular meta baseada apenas em (depósito - saque)
    $meta_resultado = calcularMetaDiaria($conexao, $id_usuario, $total_deposito, $total_saque);
    
    // Calcular dados para área direita
    $area_direita = calcularAreaDireita($conexao, $id_usuario, $saldo_banca_total);
    
    // ✅ CALCULAR METAS POR PERÍODO
    $metas_periodo = calcularMetasPorPeriodo($meta_resultado['meta_diaria']);
    
    // ✅ RESPOSTA COMPLETA OTIMIZADA
    echo json_encode([
        'success' => true,
        
        // Dados principais da banca
        'banca' => $saldo_banca_total,
        'banca_formatada' => 'R$ ' . number_format($saldo_banca_total, 2, ',', '.'),
        'depositos_total' => $total_deposito,
        'depositos_formatado' => 'R$ ' . number_format($total_deposito, 2, ',', '.'),
        'saques_total' => $total_saque,
        'saques_formatado' => 'R$ ' . number_format($total_saque, 2, ',', '.'),
        'lucro' => $lucro,
        'lucro_formatado' => 'R$ ' . number_format($lucro, 2, ',', '.'),
        'green_total' => $total_green,
        'green_formatado' => 'R$ ' . number_format($total_green, 2, ',', '.'),
        'red_total' => $total_red,
        'red_formatado' => 'R$ ' . number_format($total_red, 2, ',', '.'),
        
        // Configurações atuais
        'diaria' => $ultima_diaria ?? 2,
        'unidade' => $ultima_unidade ?? 2,
        'odds' => $ultima_odds ?? 1.5,
        
        // Meta diária
        'meta_diaria' => $meta_resultado['meta_diaria'],
        'meta_diaria_formatada' => 'R$ ' . number_format($meta_resultado['meta_diaria'], 2, ',', '.'),
        'meta_diaria_brl' => 'R$ ' . number_format($meta_resultado['meta_diaria'], 2, ',', '.'),
        'diaria_usada' => $meta_resultado['diaria_usada'],
        'unidade_usada' => $meta_resultado['unidade_usada'],
        'saldo_base_meta' => $meta_resultado['saldo_banca_meta'],
        
        // Dados específicos para área direita
        'diaria_formatada' => $area_direita['diaria_formatada'],
        'unidade_entrada_formatada' => $area_direita['unidade_entrada_formatada'],
        'diaria_raw' => $area_direita['diaria_porcentagem'],
        'saldo_banca_total' => $area_direita['saldo_banca_total'],
        'unidade_entrada_raw' => $area_direita['unidade_entrada'],
        
        // ✅ METAS POR PERÍODO (ESTRUTURA OTIMIZADA)
        'metas_periodo' => $metas_periodo,
        'meta_mensal' => $metas_periodo['meta_mensal'],
        'meta_mensal_formatada' => $metas_periodo['meta_mensal_formatada'],
        'meta_anual' => $metas_periodo['meta_anual'], 
        'meta_anual_formatada' => $metas_periodo['meta_anual_formatada'],
        'dias_restantes_mes' => $metas_periodo['dias_restantes_mes'],
        'dias_restantes_ano' => $metas_periodo['dias_restantes_ano'],
        'periodo_info' => $metas_periodo['periodo_info'],
        
        // ✅ INFORMAÇÕES DETALHADAS PARA DEBUG
        'calculo_detalhado' => [
            'saldo_banca_total' => $saldo_banca_total,
            'saldo_base_meta' => $meta_resultado['saldo_banca_meta'],
            'depositos' => $total_deposito,
            'saques' => $total_saque,
            'lucro' => $lucro,
            'diaria_percentual' => $meta_resultado['diaria_usada'],
            'unidade_multiplicador' => $meta_resultado['unidade_usada'],
            'formula_meta_diaria' => "Base: R$ " . number_format($total_deposito, 2, ',', '.') . " - R$ " . number_format($total_saque, 2, ',', '.') . " = R$ " . number_format($meta_resultado['saldo_banca_meta'], 2, ',', '.') . " × {$meta_resultado['diaria_usada']}% × {$meta_resultado['unidade_usada']} = R$ " . number_format($meta_resultado['meta_diaria'], 2, ',', '.'),
            
            // Detalhes dos períodos
            'meta_diaria_base' => $meta_resultado['meta_diaria'],
            'meta_mensal_calculada' => $metas_periodo['meta_mensal'],
            'meta_anual_calculada' => $metas_periodo['meta_anual'],
            'dias_mes_atual' => $metas_periodo['dias_restantes_mes'],
            'dias_ano_atual' => $metas_periodo['dias_restantes_ano'],
            'formula_periodo_mensal' => $metas_periodo['periodo_info']['formula_mensal'],
            'formula_periodo_anual' => $metas_periodo['periodo_info']['formula_anual']
        ],
        
        // Dados específicos área direita para debug
        'area_direita_debug' => [
            'formula_unidade' => "Saldo Total: R$ " . number_format($saldo_banca_total, 2, ',', '.') . " × {$area_direita['diaria_porcentagem']}% = {$area_direita['unidade_entrada_formatada']}",
            'saldo_banca_total' => $saldo_banca_total,
            'depositos' => $total_deposito,
            'saques' => $total_saque,
            'lucro' => $lucro,
            'diaria_aplicada' => $area_direita['diaria_porcentagem'],
            'resultado_unidade' => $area_direita['unidade_entrada']
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Erro em dados_banca.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erro interno do servidor: ' . $e->getMessage(),
        'meta_diaria' => 0,
        'meta_diaria_formatada' => 'R$ 0,00',
        'meta_diaria_brl' => 'R$ 0,00',
        'meta_mensal' => 0,
        'meta_mensal_formatada' => 'R$ 0,00',
        'meta_anual' => 0,
        'meta_anual_formatada' => 'R$ 0,00',
        'dias_restantes_mes' => 0,
        'dias_restantes_ano' => 0,
        'diaria_formatada' => '2%',
        'unidade_entrada_formatada' => 'R$ 0,00'
    ]);
}
?>