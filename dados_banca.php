<?php
// ✅ ARQUIVO DADOS_BANCA.PHP - VERIFICAÇÃO POR COLUNA META DO BANCO

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

// ✅ NOVA FUNÇÃO: DETECTAR TIPO DE META PELO ÚLTIMO CADASTRO
function detectarTipoMetaPorBanco($conexao, $id_usuario) {
    try {
        $stmt = $conexao->prepare("
            SELECT meta FROM controle
            WHERE id_usuario = ? AND meta IS NOT NULL AND meta != ''
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $stmt->bind_result($ultimaMeta);
        $stmt->fetch();
        $stmt->close();
        
        if (!$ultimaMeta) {
            // Se não há cadastro de meta, usar padrão 'turbo'
            return 'turbo';
        }
        
        $metaLower = strtolower(trim($ultimaMeta));
        
        if (strpos($metaLower, 'fixa') !== false) {
            return 'fixa';
        } else if (strpos($metaLower, 'turbo') !== false) {
            return 'turbo';
        }
        
        // Se não reconhecer o tipo, usar padrão 'turbo'
        return 'turbo';
        
    } catch (Exception $e) {
        error_log("Erro ao detectar tipo de meta: " . $e->getMessage());
        return 'turbo'; // Padrão em caso de erro
    }
}

// ✅ NOVA FUNÇÃO: CALCULAR LUCRO FILTRADO POR PERÍODO
function calcularLucroFiltrado($conexao, $id_usuario, $periodo = 'total') {
    // Definir condição de data baseada no período
    $condicaoData = '';
    
    switch ($periodo) {
        case 'dia':
            $condicaoData = "AND DATE(data_criacao) = CURDATE()";
            break;
        case 'mes':
            $condicaoData = "AND MONTH(data_criacao) = MONTH(CURDATE()) 
                           AND YEAR(data_criacao) = YEAR(CURDATE())";
            break;
        case 'ano':
            $condicaoData = "AND YEAR(data_criacao) = YEAR(CURDATE())";
            break;
        default:
            // 'total' ou qualquer outro valor = sem filtro
            $condicaoData = '';
            break;
    }
    
    $stmt = $conexao->prepare("
        SELECT 
            COALESCE(SUM(valor_green), 0) as total_green,
            COALESCE(SUM(valor_red), 0) as total_red
        FROM valor_mentores
        WHERE id_usuario = ? {$condicaoData}
    ");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $stmt->bind_result($total_green, $total_red);
    $stmt->fetch();
    $stmt->close();
    
    return [
        'green' => $total_green,
        'red' => $total_red,
        'lucro' => $total_green - $total_red,
        'periodo' => $periodo
    ];
}

// ✅ FUNÇÃO PARA CALCULAR LUCRO (MANTIDA PARA COMPATIBILIDADE)
function calcularLucro($conexao, $id_usuario) {
    return calcularLucroFiltrado($conexao, $id_usuario, 'total');
}

// ✅ NOVA FUNÇÃO: CALCULAR META DIÁRIA COM TIPOS BASEADO NO BANCO
function calcularMetaDiariaComTipo($conexao, $id_usuario, $total_deposito, $total_saque, $lucro_total, $tipo_meta = 'turbo') {
    try {
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
        
        // ✅ CALCULAR BANCA INICIAL (DEPÓSITOS - SAQUES)
        $banca_inicial = $total_deposito - $total_saque;
        
        // ✅ CALCULAR BANCA ATUAL (BANCA INICIAL + LUCRO)
        $banca_atual = $banca_inicial + $lucro_total;
        
        $porcentagem_decimal = $diaria / 100;
        $meta_diaria = 0;
        $base_calculo = 0;
        $descricao_calculo = '';
        
        // ✅ APLICAR CÁLCULO BASEADO NO TIPO DE META
        if ($tipo_meta === 'fixa') {
            // META FIXA: Sempre calcula sobre a banca inicial
            $base_calculo = $banca_inicial;
            $meta_diaria = $base_calculo * $porcentagem_decimal * $unidade;
            $descricao_calculo = "Meta Fixa: Banca Inicial (R$ " . number_format($banca_inicial, 2, ',', '.') . ") × {$diaria}% × {$unidade}";
            
        } else {
            // META TURBO: Calcula sobre a banca atual (com lucro)
            $base_calculo = $banca_atual;
            $meta_diaria = $base_calculo * $porcentagem_decimal * $unidade;
            $descricao_calculo = "Meta Turbo: Banca Atual (R$ " . number_format($banca_atual, 2, ',', '.') . ") × {$diaria}% × {$unidade}";
        }
        
        return [
            'meta_diaria' => $meta_diaria,
            'diaria_usada' => $diaria,
            'unidade_usada' => $unidade,
            'banca_inicial' => $banca_inicial,
            'banca_atual' => $banca_atual,
            'base_calculo' => $base_calculo,
            'tipo_meta' => $tipo_meta,
            'descricao_calculo' => $descricao_calculo,
            'formula_detalhada' => [
                'banca_inicial' => $banca_inicial,
                'lucro_total' => $lucro_total,
                'banca_atual' => $banca_atual,
                'tipo_aplicado' => $tipo_meta,
                'base_usada' => $base_calculo,
                'porcentagem' => $diaria,
                'unidade' => $unidade,
                'resultado' => $meta_diaria
            ]
        ];
        
    } catch (Exception $e) {
        error_log("Erro ao calcular meta diária com tipo: " . $e->getMessage());
        return [
            'meta_diaria' => 0,
            'diaria_usada' => 2,
            'unidade_usada' => 2,
            'banca_inicial' => 0,
            'banca_atual' => 0,
            'base_calculo' => 0,
            'tipo_meta' => $tipo_meta,
            'descricao_calculo' => 'Erro no cálculo',
            'formula_detalhada' => []
        ];
    }
}

// ✅ FUNÇÃO PARA CALCULAR DADOS DA ÁREA DIREITA (ATUALIZADA PARA USAR BANCA CORRETA)
function calcularAreaDireita($conexao, $id_usuario, $banca_total, $tipo_meta = 'turbo') {
    try {
        // Buscar última diária cadastrada
        $ultima_diaria = getUltimoCampo($conexao, 'diaria', $id_usuario);
        $diaria = $ultima_diaria ?? 2.00;
        
        // ✅ PARA ÁREA DIREITA, SEMPRE USAR BANCA TOTAL (INDEPENDENTE DO TIPO DE META)
        // Calcular unidade de entrada: banca_total * (diária / 100)
        $unidade_entrada = $banca_total * ($diaria / 100);
        
        return [
            'diaria_porcentagem' => $diaria,
            'banca_usada' => $banca_total,
            'unidade_entrada' => $unidade_entrada,
            'diaria_formatada' => number_format($diaria, 0) . '%',
            'unidade_entrada_formatada' => 'R$ ' . number_format($unidade_entrada, 2, ',', '.'),
            'tipo_meta_info' => $tipo_meta
        ];
        
    } catch (Exception $e) {
        error_log("Erro ao calcular área direita: " . $e->getMessage());
        return [
            'diaria_porcentagem' => 2,
            'banca_usada' => 0,
            'unidade_entrada' => 0,
            'diaria_formatada' => '2%',
            'unidade_entrada_formatada' => 'R$ 0,00',
            'tipo_meta_info' => $tipo_meta
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
function calcularMetasPorPeriodo($meta_diaria, $tipo_meta = 'turbo') {
    $diasRestantes = calcularDiasRestantes();
    
    $meta_mensal = $meta_diaria * $diasRestantes['mes'];
    $meta_anual = $meta_diaria * $diasRestantes['ano'];
    
    return [
        // Metas calculadas
        'meta_diaria' => $meta_diaria,
        'meta_mensal' => $meta_mensal,
        'meta_anual' => $meta_anual,
        
        // Tipo de meta aplicado
        'tipo_meta' => $tipo_meta,
        
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
            
            // Fórmulas de cálculo com tipo de meta
            'formula_diaria' => "Meta Diária ({$tipo_meta}): R$ " . number_format($meta_diaria, 2, ',', '.'),
            'formula_mensal' => "Meta Mensal ({$tipo_meta}): R$ " . number_format($meta_diaria, 2, ',', '.') . " × {$diasRestantes['mes']} dias = R$ " . number_format($meta_mensal, 2, ',', '.'),
            'formula_anual' => "Meta Anual ({$tipo_meta}): R$ " . number_format($meta_diaria, 2, ',', '.') . " × {$diasRestantes['ano']} dias = R$ " . number_format($meta_anual, 2, ',', '.')
        ]
    ];
}

// ✅ NOVA FUNÇÃO: DETECTAR PERÍODO ATIVO
function detectarPeriodoAtivo() {
    // Verifica se há um período específico sendo requisitado
    $periodo_requisitado = $_GET['periodo'] ?? $_POST['periodo'] ?? null;
    
    if ($periodo_requisitado && in_array($periodo_requisitado, ['dia', 'mes', 'ano'])) {
        return $periodo_requisitado;
    }
    
    // Verifica através do header HTTP (usado pelo JavaScript)
    $periodo_header = $_SERVER['HTTP_X_PERIODO_FILTRO'] ?? null;
    if ($periodo_header && in_array($periodo_header, ['dia', 'mes', 'ano'])) {
        return $periodo_header;
    }
    
    // Padrão é 'dia'
    return 'dia';
}

// ✅ NOVA FUNÇÃO: DETECTAR TIPO DE META ATIVO
function detectarTipoMetaAtivo($conexao, $id_usuario) {
    // Verifica se há um tipo específico sendo requisitado
    $tipo_requisitado = $_GET['tipo_meta'] ?? $_POST['tipo_meta'] ?? null;
    
    if ($tipo_requisitado && in_array($tipo_requisitado, ['fixa', 'turbo'])) {
        return $tipo_requisitado;
    }
    
    // Verifica através do header HTTP (usado pelo JavaScript)
    $tipo_header = $_SERVER['HTTP_X_TIPO_META'] ?? null;
    if ($tipo_header && in_array($tipo_header, ['fixa', 'turbo'])) {
        return $tipo_header;
    }
    
    // ✅ USAR DETECÇÃO PELO BANCO COMO PADRÃO
    return detectarTipoMetaPorBanco($conexao, $id_usuario);
}

// Processar requisições POST (cadastros) - ATUALIZADO COM TIPO DE META
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    try {
        $acao = $input['acao'] ?? '';
        $valor = floatval($input['valor'] ?? 0);
        $diaria = floatval($input['diaria'] ?? 2);
        $unidade = intval($input['unidade'] ?? 2);
        $odds = floatval($input['odds'] ?? 1.5);
        
        // ✅ DETECTAR PERÍODO E TIPO DE META
        $periodo_ativo = $input['periodo'] ?? detectarPeriodoAtivo();
        $tipo_meta = $input['tipo_meta'] ?? detectarTipoMetaAtivo($conexao, $id_usuario);
        
        // ✅ NOVO: CAMPO META PARA IDENTIFICAR TIPO
        $campo_meta = $input['meta'] ?? null;
        
        $stmt = null;
        
        switch ($acao) {
            case 'deposito':
                if ($campo_meta) {
                    $stmt = $conexao->prepare("INSERT INTO controle (id_usuario, deposito, diaria, unidade, odds, meta, data_cadastro) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->bind_param("iddids", $id_usuario, $valor, $diaria, $unidade, $odds, $campo_meta);
                } else {
                    $stmt = $conexao->prepare("INSERT INTO controle (id_usuario, deposito, diaria, unidade, odds, data_cadastro) VALUES (?, ?, ?, ?, ?, NOW())");
                    $stmt->bind_param("idddi", $id_usuario, $valor, $diaria, $unidade, $odds);
                }
                break;
                
            case 'saque':
                if ($campo_meta) {
                    $stmt = $conexao->prepare("INSERT INTO controle (id_usuario, saque, diaria, unidade, odds, meta, data_cadastro) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->bind_param("iddids", $id_usuario, $valor, $diaria, $unidade, $odds, $campo_meta);
                } else {
                    $stmt = $conexao->prepare("INSERT INTO controle (id_usuario, saque, diaria, unidade, odds, data_cadastro) VALUES (?, ?, ?, ?, ?, NOW())");
                    $stmt->bind_param("idddi", $id_usuario, $valor, $diaria, $unidade, $odds);
                }
                break;
                
            case 'alterar':
                if ($campo_meta) {
                    $stmt = $conexao->prepare("INSERT INTO controle (id_usuario, diaria, unidade, odds, meta, data_cadastro) VALUES (?, ?, ?, ?, ?, NOW())");
                    $stmt->bind_param("iddis", $id_usuario, $diaria, $unidade, $odds, $campo_meta);
                } else {
                    $stmt = $conexao->prepare("INSERT INTO controle (id_usuario, diaria, unidade, odds, data_cadastro) VALUES (?, ?, ?, ?, NOW())");
                    $stmt->bind_param("iddi", $id_usuario, $diaria, $unidade, $odds);
                }
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
        
        // ✅ RECALCULAR TIPO APÓS INSERÇÃO (CASO TENHA SIDO ALTERADO)
        $tipo_meta = detectarTipoMetaAtivo($conexao, $id_usuario);
        
        // ✅ CALCULAR VALORES APÓS OPERAÇÃO COM PERÍODO E TIPO
        $total_deposito = getSoma($conexao, 'deposito', $id_usuario);
        $total_saque = getSoma($conexao, 'saque', $id_usuario);
        
        // ✅ CALCULAR LUCRO TOTAL E FILTRADO
        $dados_lucro_total = calcularLucro($conexao, $id_usuario);
        $dados_lucro_filtrado = calcularLucroFiltrado($conexao, $id_usuario, $periodo_ativo);
        
        $lucro_total = $dados_lucro_total['lucro'];
        $lucro_filtrado = $dados_lucro_filtrado['lucro'];
        
        // Saldo total da banca (sempre com lucro total)
        $saldo_banca_total = $total_deposito - $total_saque + $lucro_total;
        
        // ✅ CALCULAR META COM TIPO ESPECÍFICO
        $meta_resultado = calcularMetaDiariaComTipo($conexao, $id_usuario, $total_deposito, $total_saque, $lucro_total, $tipo_meta);
        
        // Calcular dados para área direita
        $area_direita = calcularAreaDireita($conexao, $id_usuario, $saldo_banca_total, $tipo_meta);
        
        // ✅ CALCULAR METAS POR PERÍODO COM TIPO
        $metas_periodo = calcularMetasPorPeriodo($meta_resultado['meta_diaria'], $tipo_meta);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Operação realizada com sucesso',
            
            // ✅ DADOS PRINCIPAIS
            'banca' => $saldo_banca_total,
            'banca_formatada' => 'R$ ' . number_format($saldo_banca_total, 2, ',', '.'),
            'lucro' => $lucro_filtrado,
            'lucro_formatado' => 'R$ ' . number_format($lucro_filtrado, 2, ',', '.'),
            'lucro_total' => $lucro_total,
            'periodo_ativo' => $periodo_ativo,
            
            // ✅ INFORMAÇÕES DO TIPO DE META
            'tipo_meta' => $tipo_meta,
            'tipo_meta_texto' => $tipo_meta === 'fixa' ? 'Meta Fixa' : 'Meta Turbo',
            'tipo_meta_origem' => 'banco', // Indicar que veio do banco
            
            // ✅ DADOS DA META COM TIPO
            'meta_diaria' => $meta_resultado['meta_diaria'],
            'meta_diaria_formatada' => 'R$ ' . number_format($meta_resultado['meta_diaria'], 2, ',', '.'),
            'meta_diaria_brl' => 'R$ ' . number_format($meta_resultado['meta_diaria'], 2, ',', '.'),
            'diaria_atual' => $meta_resultado['diaria_usada'],
            'unidade_atual' => $meta_resultado['unidade_usada'],
            'banca_inicial' => $meta_resultado['banca_inicial'],
            'banca_atual' => $meta_resultado['banca_atual'],
            'base_calculo' => $meta_resultado['base_calculo'],
            'descricao_calculo' => $meta_resultado['descricao_calculo'],
            
            // Dados para área direita
            'diaria_formatada' => $area_direita['diaria_formatada'],
            'unidade_entrada_formatada' => $area_direita['unidade_entrada_formatada'],
            'diaria_raw' => $area_direita['diaria_porcentagem'],
            'saldo_banca_total' => $area_direita['banca_usada'],
            'unidade_entrada_raw' => $area_direita['unidade_entrada'],
            
            // ✅ METAS POR PERÍODO COM TIPO
            'metas_periodo' => $metas_periodo,
            'meta_mensal' => $metas_periodo['meta_mensal'],
            'meta_mensal_formatada' => $metas_periodo['meta_mensal_formatada'],
            'meta_anual' => $metas_periodo['meta_anual'], 
            'meta_anual_formatada' => $metas_periodo['meta_anual_formatada'],
            'dias_restantes_mes' => $metas_periodo['dias_restantes_mes'],
            'dias_restantes_ano' => $metas_periodo['dias_restantes_ano'],
            'periodo_info' => $metas_periodo['periodo_info'],
            
            // ✅ FÓRMULAS DETALHADAS PARA DEBUG
            'formula_detalhada' => $meta_resultado['formula_detalhada']
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ✅ PROCESSAR REQUISIÇÕES GET (CONSULTAS) - ATUALIZADO COM TIPO DE META
try {
    // ✅ DETECTAR PERÍODO E TIPO DE META
    $periodo_ativo = detectarPeriodoAtivo();
    $tipo_meta = detectarTipoMetaAtivo($conexao, $id_usuario);
    
    $total_deposito = getSoma($conexao, 'deposito', $id_usuario);
    $total_saque = getSoma($conexao, 'saque', $id_usuario);
    
    // ✅ CALCULAR LUCRO TOTAL E FILTRADO
    $dados_lucro_total = calcularLucro($conexao, $id_usuario);
    $dados_lucro_filtrado = calcularLucroFiltrado($conexao, $id_usuario, $periodo_ativo);
    
    $total_green_total = $dados_lucro_total['green'];
    $total_red_total = $dados_lucro_total['red'];
    $lucro_total = $dados_lucro_total['lucro'];
    
    $total_green_filtrado = $dados_lucro_filtrado['green'];
    $total_red_filtrado = $dados_lucro_filtrado['red'];
    $lucro_filtrado = $dados_lucro_filtrado['lucro'];
    
    // Saldo total da banca (sempre com lucro total)
    $saldo_banca_total = $total_deposito - $total_saque + $lucro_total;
    
    // Buscar últimos valores de configuração
    $ultima_diaria = getUltimoCampo($conexao, 'diaria', $id_usuario);
    $ultima_unidade = getUltimoCampo($conexao, 'unidade', $id_usuario);
    $ultima_odds = getUltimoCampo($conexao, 'odds', $id_usuario);
    
    // ✅ CALCULAR META COM TIPO ESPECÍFICO
    $meta_resultado = calcularMetaDiariaComTipo($conexao, $id_usuario, $total_deposito, $total_saque, $lucro_total, $tipo_meta);
    
    // Calcular dados para área direita
    $area_direita = calcularAreaDireita($conexao, $id_usuario, $saldo_banca_total, $tipo_meta);
    
    // ✅ CALCULAR METAS POR PERÍODO COM TIPO
    $metas_periodo = calcularMetasPorPeriodo($meta_resultado['meta_diaria'], $tipo_meta);
    
    // ✅ RESPOSTA COMPLETA COM TIPOS DE META
    echo json_encode([
        'success' => true,
        
        // ✅ DADOS PRINCIPAIS
        'banca' => $saldo_banca_total,
        'banca_formatada' => 'R$ ' . number_format($saldo_banca_total, 2, ',', '.'),
        'depositos_total' => $total_deposito,
        'depositos_formatado' => 'R$ ' . number_format($total_deposito, 2, ',', '.'),
        'saques_total' => $total_saque,
        'saques_formatado' => 'R$ ' . number_format($total_saque, 2, ',', '.'),
        
        // ✅ LUCRO FILTRADO PELO PERÍODO
        'lucro' => $lucro_filtrado,
        'lucro_formatado' => 'R$ ' . number_format($lucro_filtrado, 2, ',', '.'),
        'green_total' => $total_green_filtrado,
        'green_formatado' => 'R$ ' . number_format($total_green_filtrado, 2, ',', '.'),
        'red_total' => $total_red_filtrado,
        'red_formatado' => 'R$ ' . number_format($total_red_filtrado, 2, ',', '.'),
        
        // ✅ DADOS TOTAIS
        'lucro_total_historico' => $lucro_total,
        'lucro_total_formatado' => 'R$ ' . number_format($lucro_total, 2, ',', '.'),
        'lucro_total_display' => $lucro_total,
        'green_total_historico' => $total_green_total,
        'red_total_historico' => $total_red_total,
        'periodo_ativo' => $periodo_ativo,
        
        // ✅ INFORMAÇÕES DO TIPO DE META
        'tipo_meta' => $tipo_meta,
        'tipo_meta_texto' => $tipo_meta === 'fixa' ? 'Meta Fixa' : 'Meta Turbo',
        'tipo_meta_origem' => 'banco', // Indicar que veio do banco
        
        // Configurações atuais
        'diaria' => $ultima_diaria ?? 2,
        'unidade' => $ultima_unidade ?? 2,
        'odds' => $ultima_odds ?? 1.5,
        
        // ✅ DADOS DA META COM TIPO
        'meta_diaria' => $meta_resultado['meta_diaria'],
        'meta_diaria_formatada' => 'R$ ' . number_format($meta_resultado['meta_diaria'], 2, ',', '.'),
        'meta_diaria_brl' => 'R$ ' . number_format($meta_resultado['meta_diaria'], 2, ',', '.'),
        'diaria_usada' => $meta_resultado['diaria_usada'],
        'unidade_usada' => $meta_resultado['unidade_usada'],
        'banca_inicial' => $meta_resultado['banca_inicial'],
        'banca_atual' => $meta_resultado['banca_atual'],
        'base_calculo' => $meta_resultado['base_calculo'],
        'descricao_calculo' => $meta_resultado['descricao_calculo'],
        
        // ✅ DADOS PARA COMPATIBILIDADE (MANTÉM FUNCIONAMENTO ATUAL)
        'saldo_base_meta' => $meta_resultado['base_calculo'],
        'meta_display' => $meta_resultado['meta_diaria'],
        'meta_display_formatada' => 'R$ ' . number_format($meta_resultado['meta_diaria'], 2, ',', '.'),
        'rotulo_periodo' => $periodo_ativo === 'mes' ? 'Meta do Mês' : ($periodo_ativo === 'ano' ? 'Meta do Ano' : 'Meta do Dia'),
        
        // Dados específicos para área direita
        'diaria_formatada' => $area_direita['diaria_formatada'],
        'unidade_entrada_formatada' => $area_direita['unidade_entrada_formatada'],
        'diaria_raw' => $area_direita['diaria_porcentagem'],
        'saldo_banca_total' => $area_direita['banca_usada'],
        'unidade_entrada_raw' => $area_direita['unidade_entrada'],
        
        // ✅ METAS POR PERÍODO COM TIPO
        'metas_periodo' => $metas_periodo,
        'meta_mensal' => $metas_periodo['meta_mensal'],
        'meta_mensal_formatada' => $metas_periodo['meta_mensal_formatada'],
        'meta_anual' => $metas_periodo['meta_anual'], 
        'meta_anual_formatada' => $metas_periodo['meta_anual_formatada'],
        'dias_restantes_mes' => $metas_periodo['dias_restantes_mes'],
        'dias_restantes_ano' => $metas_periodo['dias_restantes_ano'],
        'periodo_info' => $metas_periodo['periodo_info'],
        
        // ✅ INFORMAÇÕES DETALHADAS PARA DEBUG COM TIPOS DE META
        'calculo_detalhado' => [
            'tipo_meta' => $tipo_meta,
            'tipo_meta_texto' => $tipo_meta === 'fixa' ? 'Meta Fixa' : 'Meta Turbo',
            'tipo_meta_origem' => 'banco',
            'banca_inicial' => $meta_resultado['banca_inicial'],
            'banca_atual' => $meta_resultado['banca_atual'],
            'base_calculo_usada' => $meta_resultado['base_calculo'],
            'depositos' => $total_deposito,
            'saques' => $total_saque,
            'lucro_filtrado' => $lucro_filtrado,
            'lucro_total' => $lucro_total,
            'periodo_aplicado' => $periodo_ativo,
            'diaria_percentual' => $meta_resultado['diaria_usada'],
            'unidade_multiplicador' => $meta_resultado['unidade_usada'],
            'descricao_calculo' => $meta_resultado['descricao_calculo'],
            
            // ✅ FÓRMULAS ESPECÍFICAS POR TIPO
            'formula_meta_fixa' => "Meta Fixa: Banca Inicial (R$ " . number_format($meta_resultado['banca_inicial'], 2, ',', '.') . ") × {$meta_resultado['diaria_usada']}% × {$meta_resultado['unidade_usada']} = R$ " . number_format($meta_resultado['meta_diaria'], 2, ',', '.'),
            'formula_meta_turbo' => "Meta Turbo: Banca Atual (R$ " . number_format($meta_resultado['banca_atual'], 2, ',', '.') . ") × {$meta_resultado['diaria_usada']}% × {$meta_resultado['unidade_usada']} = R$ " . number_format($meta_resultado['meta_diaria'], 2, ',', '.'),
            'formula_aplicada' => $meta_resultado['descricao_calculo'],
            'formula_detalhada_completa' => $meta_resultado['formula_detalhada'],
            
            // ✅ DETALHES DO FILTRO DE LUCRO
            'lucro_por_periodo' => [
                'periodo_ativo' => $periodo_ativo,
                'lucro_filtrado' => $lucro_filtrado,
                'green_filtrado' => $total_green_filtrado,
                'red_filtrado' => $total_red_filtrado,
                'diferenca_total_filtrado' => $lucro_total - $lucro_filtrado
            ],
            
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
            'tipo_meta_aplicado' => $tipo_meta,
            'formula_unidade' => "Banca Total: R$ " . number_format($saldo_banca_total, 2, ',', '.') . " × {$area_direita['diaria_porcentagem']}% = {$area_direita['unidade_entrada_formatada']}",
            'banca_total_usada' => $saldo_banca_total,
            'depositos' => $total_deposito,
            'saques' => $total_saque,
            'lucro_usado_banca' => $lucro_total,
            'lucro_usado_calculos' => $lucro_filtrado,
            'diaria_aplicada' => $area_direita['diaria_porcentagem'],
            'resultado_unidade' => $area_direita['unidade_entrada']
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Erro em dados_banca.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erro interno do servidor: ' . $e->getMessage(),
        'tipo_meta' => 'turbo',
        'tipo_meta_texto' => 'Meta Turbo',
        'tipo_meta_origem' => 'padrao',
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
        'unidade_entrada_formatada' => 'R$ 0,00',
        'periodo_ativo' => 'dia',
        'lucro' => 0,
        'lucro_formatado' => 'R$ 0,00',
        'banca_inicial' => 0,
        'banca_atual' => 0,
        'base_calculo' => 0,
        'descricao_calculo' => 'Erro no cálculo'
    ]);
}
?>