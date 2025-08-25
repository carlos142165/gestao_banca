<?php
// ✅ ARQUIVO DADOS_BANCA.PHP - LÓGICA CORRETA DE META MENSAL/ANUAL

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
            return 'turbo';
        }
        
        $metaLower = strtolower(trim($ultimaMeta));
        
        if (strpos($metaLower, 'fixa') !== false) {
            return 'fixa';
        } else if (strpos($metaLower, 'turbo') !== false) {
            return 'turbo';
        }
        
        return 'turbo';
        
    } catch (Exception $e) {
        error_log("Erro ao detectar tipo de meta: " . $e->getMessage());
        return 'turbo';
    }
}

// ✅ FUNÇÃO: BUSCAR PRIMEIRO DEPÓSITO DO USUÁRIO (USANDO data_registro)
function getPrimeiroDeposito($conexao, $id_usuario) {
    try {
        $stmt = $conexao->prepare("
            SELECT DATE(data_registro) as primeira_data
            FROM controle 
            WHERE id_usuario = ? AND deposito > 0 
            ORDER BY data_registro ASC 
            LIMIT 1
        ");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $stmt->bind_result($primeira_data);
        $stmt->fetch();
        $stmt->close();
        
        return $primeira_data;
    } catch (Exception $e) {
        error_log("Erro ao buscar primeiro depósito: " . $e->getMessage());
        return null;
    }
}

// ✅ NOVA FUNÇÃO: CALCULAR LUCRO FILTRADO POR PERÍODO
function calcularLucroFiltrado($conexao, $id_usuario, $periodo = 'total') {
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

// ✅ NOVA FUNÇÃO: CALCULAR META DIÁRIA COM TIPOS BASEADO NO BANCO (CORRIGIDA)
function calcularMetaDiariaComTipo($conexao, $id_usuario, $total_deposito, $total_saque, $lucro_total, $tipo_meta = 'turbo') {
    try {
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
        
        if ($diaria === null) $diaria = 2.00;
        if ($unidade === null) $unidade = 2;
        
        $banca_inicial = $total_deposito - $total_saque;
        $banca_atual = $banca_inicial + $lucro_total;
        
        $porcentagem_decimal = $diaria / 100;
        $meta_diaria = 0;
        $base_calculo = 0;
        $descricao_calculo = '';
        
        if ($tipo_meta === 'fixa') {
            // ✅ META FIXA: Sempre usa banca inicial
            $base_calculo = $banca_inicial;
            $meta_diaria = $base_calculo * $porcentagem_decimal * $unidade;
            $descricao_calculo = "Meta Fixa: Banca Inicial (R$ " . number_format($banca_inicial, 2, ',', '.') . ") × {$diaria}% × {$unidade}";
        } else {
            // ✅ META TURBO: Usa banca atual SE lucro > 0, senão usa banca inicial
            if ($lucro_total > 0) {
                // Lucro positivo: usa banca atual (com lucro)
                $base_calculo = $banca_atual;
                $meta_diaria = $base_calculo * $porcentagem_decimal * $unidade;
                $descricao_calculo = "Meta Turbo (Lucro +): Banca Atual (R$ " . number_format($banca_atual, 2, ',', '.') . ") × {$diaria}% × {$unidade}";
            } else {
                // Lucro negativo ou zero: usa banca inicial (igual meta fixa)
                $base_calculo = $banca_inicial;
                $meta_diaria = $base_calculo * $porcentagem_decimal * $unidade;
                $descricao_calculo = "Meta Turbo (Lucro -): Banca Inicial (R$ " . number_format($banca_inicial, 2, ',', '.') . ") × {$diaria}% × {$unidade}";
            }
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
            'lucro_total' => $lucro_total,
            'lucro_positivo' => $lucro_total > 0,
            'formula_detalhada' => [
                'banca_inicial' => $banca_inicial,
                'lucro_total' => $lucro_total,
                'banca_atual' => $banca_atual,
                'tipo_aplicado' => $tipo_meta,
                'base_usada' => $base_calculo,
                'porcentagem' => $diaria,
                'unidade' => $unidade,
                'resultado' => $meta_diaria,
                'logica_turbo' => $tipo_meta === 'turbo' ? 
                    ($lucro_total > 0 ? 'usa_banca_atual' : 'usa_banca_inicial') : 'nao_aplicavel'
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
            'lucro_total' => 0,
            'lucro_positivo' => false,
            'formula_detalhada' => []
        ];
    }
}

// ✅ FUNÇÃO PARA CALCULAR DADOS DA ÁREA DIREITA
function calcularAreaDireita($conexao, $id_usuario, $banca_total, $tipo_meta = 'turbo') {
    try {
        $ultima_diaria = getUltimoCampo($conexao, 'diaria', $id_usuario);
        $diaria = $ultima_diaria ?? 2.00;
        
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

// ✅ NOVA FUNÇÃO: CALCULAR DIAS COM LÓGICA CORRETA
function calcularDiasRestantes($conexao, $id_usuario) {
    $hoje = new DateTime();
    
    // ✅ BUSCAR PRIMEIRO DEPÓSITO
    $primeira_data_deposito = getPrimeiroDeposito($conexao, $id_usuario);
    
    if (!$primeira_data_deposito) {
        // Sem depósito: usar data atual
        $data_primeiro_deposito = $hoje;
        $usar_data_atual = true;
    } else {
        $data_primeiro_deposito = new DateTime($primeira_data_deposito);
        $usar_data_atual = false;
    }
    
    $mes_atual = (int)$hoje->format('m');
    $ano_atual = (int)$hoje->format('Y');
    $ultimo_dia_mes = (int)$hoje->format('t');
    
    // ✅ LÓGICA PARA META MENSAL
    if ($usar_data_atual) {
        // Sem depósito: dias restantes do mês atual
        $dia_atual = (int)$hoje->format('d');
        $dias_meta_mensal = $ultimo_dia_mes - $dia_atual + 1;
        $explicacao_mensal = "Sem depósito: {$dias_meta_mensal} dias restantes do mês {$mes_atual}";
    } else {
        $mes_primeiro_deposito = (int)$data_primeiro_deposito->format('m');
        $ano_primeiro_deposito = (int)$data_primeiro_deposito->format('Y');
        
        if ($ano_primeiro_deposito === $ano_atual && $mes_primeiro_deposito === $mes_atual) {
            // ✅ PRIMEIRO MÊS: Do dia do depósito até fim do mês
            $dia_deposito = (int)$data_primeiro_deposito->format('d');
            $dias_meta_mensal = $ultimo_dia_mes - $dia_deposito + 1;
            $explicacao_mensal = "Primeiro mês: Do dia {$dia_deposito} até dia {$ultimo_dia_mes} = {$dias_meta_mensal} dias";
        } else {
            // ✅ MESES SEGUINTES: Sempre mês completo (1º até último dia)
            $dias_meta_mensal = $ultimo_dia_mes;
            $explicacao_mensal = "Mês completo: Do dia 1 até dia {$ultimo_dia_mes} = {$dias_meta_mensal} dias";
        }
    }
    
    // ✅ LÓGICA PARA META ANUAL (SEMPRE DO PRIMEIRO DEPÓSITO ATÉ 31/12)
    if ($usar_data_atual) {
        // Sem depósito: dias restantes do ano
        $fim_ano = new DateTime($ano_atual . '-12-31 23:59:59');
        $diferenca = $hoje->diff($fim_ano);
        $dias_meta_anual = $diferenca->days + 1;
        $explicacao_anual = "Sem depósito: {$dias_meta_anual} dias restantes do ano {$ano_atual}";
    } else {
        if ($data_primeiro_deposito->format('Y') === (string)$ano_atual) {
            // Primeiro depósito foi neste ano
            $fim_ano = new DateTime($ano_atual . '-12-31 23:59:59');
            $diferenca = $data_primeiro_deposito->diff($fim_ano);
            $dias_meta_anual = $diferenca->days + 1;
            $explicacao_anual = "Do primeiro depósito ({$data_primeiro_deposito->format('d/m/Y')}) até 31/12/{$ano_atual} = {$dias_meta_anual} dias";
        } else {
            // Primeiro depósito foi em ano anterior: ano completo
            $dias_meta_anual = 365; // ou 366 para ano bissexto
            if (date('L', mktime(0, 0, 0, 1, 1, $ano_atual))) {
                $dias_meta_anual = 366; // Ano bissexto
            }
            $explicacao_anual = "Ano completo {$ano_atual}: {$dias_meta_anual} dias";
        }
    }
    
    return [
        'mes' => $dias_meta_mensal,
        'ano' => $dias_meta_anual,
        'info' => [
            'data_atual' => $hoje->format('Y-m-d'),
            'primeiro_deposito' => $primeira_data_deposito,
            'usando_data_atual' => $usar_data_atual,
            'mes_atual' => $mes_atual,
            'ano_atual' => $ano_atual,
            'ultimo_dia_mes' => $ultimo_dia_mes,
            'dias_meta_mensal' => $dias_meta_mensal,
            'dias_meta_anual' => $dias_meta_anual,
            'explicacao_mensal' => $explicacao_mensal,
            'explicacao_anual' => $explicacao_anual,
            
            // ✅ EXEMPLO PRÁTICO
            'exemplo_usuario' => [
                'primeiro_deposito' => $primeira_data_deposito ?? 'Não encontrado',
                'mes_atual_info' => "Mês {$mes_atual}/{$ano_atual}",
                'calculo_mensal' => $explicacao_mensal,
                'calculo_anual' => $explicacao_anual,
                'formula_mensal' => "Meta Diária × {$dias_meta_mensal} dias = Meta Mensal",
                'formula_anual' => "Meta Diária × {$dias_meta_anual} dias = Meta Anual"
            ],
            
            // ✅ DEBUG DETALHADO
            'debug_info' => [
                'logica_aplicada' => $usar_data_atual ? 'sem_deposito' : 'com_deposito',
                'primeiro_deposito_encontrado' => !$usar_data_atual,
                'mesmo_mes_ano_deposito' => !$usar_data_atual && 
                    ($data_primeiro_deposito->format('Y-m') === $hoje->format('Y-m')),
                'tipo_calculo_mensal' => !$usar_data_atual && 
                    ($data_primeiro_deposito->format('Y-m') === $hoje->format('Y-m')) 
                    ? 'primeiro_mes' : 'mes_completo',
                'tipo_calculo_anual' => 'do_primeiro_deposito_ate_fim_ano'
            ]
        ]
    ];
}

// ✅ FUNÇÃO CORRIGIDA: CALCULAR METAS POR PERÍODO
function calcularMetasPorPeriodo($meta_diaria, $tipo_meta = 'turbo', $conexao, $id_usuario) {
    $diasCalculados = calcularDiasRestantes($conexao, $id_usuario);
    
    $meta_mensal = $meta_diaria * $diasCalculados['mes'];
    $meta_anual = $meta_diaria * $diasCalculados['ano'];
    
    return [
        'meta_diaria' => $meta_diaria,
        'meta_mensal' => $meta_mensal,
        'meta_anual' => $meta_anual,
        'tipo_meta' => $tipo_meta,
        'dias_restantes_mes' => $diasCalculados['mes'],
        'dias_restantes_ano' => $diasCalculados['ano'],
        'meta_diaria_formatada' => 'R$ ' . number_format($meta_diaria, 2, ',', '.'),
        'meta_mensal_formatada' => 'R$ ' . number_format($meta_mensal, 2, ',', '.'),
        'meta_anual_formatada' => 'R$ ' . number_format($meta_anual, 2, ',', '.'),
        
        'periodo_info' => [
            'data_hoje' => $diasCalculados['info']['data_atual'],
            'primeiro_deposito' => $diasCalculados['info']['primeiro_deposito'],
            'mes_atual' => $diasCalculados['info']['mes_atual'],
            'ano_atual' => $diasCalculados['info']['ano_atual'],
            'ultimo_dia_mes' => $diasCalculados['info']['ultimo_dia_mes'],
            'explicacao_mes' => $diasCalculados['info']['explicacao_mensal'],
            'explicacao_ano' => $diasCalculados['info']['explicacao_anual'],
            
            'formula_diaria' => "Meta Diária ({$tipo_meta}): R$ " . number_format($meta_diaria, 2, ',', '.'),
            'formula_mensal' => "Meta Mensal ({$tipo_meta}): R$ " . number_format($meta_diaria, 2, ',', '.') . " × {$diasCalculados['mes']} dias = R$ " . number_format($meta_mensal, 2, ',', '.'),
            'formula_anual' => "Meta Anual ({$tipo_meta}): R$ " . number_format($meta_diaria, 2, ',', '.') . " × {$diasCalculados['ano']} dias = R$ " . number_format($meta_anual, 2, ',', '.'),
            
            'debug_completo' => $diasCalculados['info']['debug_info'],
            'exemplo_usuario' => $diasCalculados['info']['exemplo_usuario']
        ]
    ];
}

// ✅ DETECTAR PERÍODO ATIVO
function detectarPeriodoAtivo() {
    $periodo_requisitado = $_GET['periodo'] ?? $_POST['periodo'] ?? null;
    
    if ($periodo_requisitado && in_array($periodo_requisitado, ['dia', 'mes', 'ano'])) {
        return $periodo_requisitado;
    }
    
    $periodo_header = $_SERVER['HTTP_X_PERIODO_FILTRO'] ?? null;
    if ($periodo_header && in_array($periodo_header, ['dia', 'mes', 'ano'])) {
        return $periodo_header;
    }
    
    return 'dia';
}

// ✅ DETECTAR TIPO DE META ATIVO
function detectarTipoMetaAtivo($conexao, $id_usuario) {
    $tipo_requisitado = $_GET['tipo_meta'] ?? $_POST['tipo_meta'] ?? null;
    
    if ($tipo_requisitado && in_array($tipo_requisitado, ['fixa', 'turbo'])) {
        return $tipo_requisitado;
    }
    
    $tipo_header = $_SERVER['HTTP_X_TIPO_META'] ?? null;
    if ($tipo_header && in_array($tipo_header, ['fixa', 'turbo'])) {
        return $tipo_header;
    }
    
    return detectarTipoMetaPorBanco($conexao, $id_usuario);
}

// ✅ PROCESSAR REQUISIÇÕES POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    try {
        $acao = $input['acao'] ?? '';
        $valor = floatval($input['valor'] ?? 0);
        $diaria = floatval($input['diaria'] ?? 2);
        $unidade = intval($input['unidade'] ?? 2);
        $odds = floatval($input['odds'] ?? 1.5);
        
        $periodo_ativo = $input['periodo'] ?? detectarPeriodoAtivo();
        $tipo_meta = $input['tipo_meta'] ?? detectarTipoMetaAtivo($conexao, $id_usuario);
        
        $campo_meta = $input['meta'] ?? null;
        
        $stmt = null;
        
        switch ($acao) {
            case 'deposito':
                if ($campo_meta) {
                    $stmt = $conexao->prepare("INSERT INTO controle (id_usuario, deposito, diaria, unidade, odds, meta, data_registro) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->bind_param("iddids", $id_usuario, $valor, $diaria, $unidade, $odds, $campo_meta);
                } else {
                    $stmt = $conexao->prepare("INSERT INTO controle (id_usuario, deposito, diaria, unidade, odds, data_registro) VALUES (?, ?, ?, ?, ?, NOW())");
                    $stmt->bind_param("idddi", $id_usuario, $valor, $diaria, $unidade, $odds);
                }
                break;
                
            case 'saque':
                if ($campo_meta) {
                    $stmt = $conexao->prepare("INSERT INTO controle (id_usuario, saque, diaria, unidade, odds, meta, data_registro) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->bind_param("iddids", $id_usuario, $valor, $diaria, $unidade, $odds, $campo_meta);
                } else {
                    $stmt = $conexao->prepare("INSERT INTO controle (id_usuario, saque, diaria, unidade, odds, data_registro) VALUES (?, ?, ?, ?, ?, NOW())");
                    $stmt->bind_param("idddi", $id_usuario, $valor, $diaria, $unidade, $odds);
                }
                break;
                
            case 'alterar':
                if ($campo_meta) {
                    $stmt = $conexao->prepare("INSERT INTO controle (id_usuario, diaria, unidade, odds, meta, data_registro) VALUES (?, ?, ?, ?, ?, NOW())");
                    $stmt->bind_param("iddis", $id_usuario, $diaria, $unidade, $odds, $campo_meta);
                } else {
                    $stmt = $conexao->prepare("INSERT INTO controle (id_usuario, diaria, unidade, odds, data_registro) VALUES (?, ?, ?, ?, NOW())");
                    $stmt->bind_param("iddi", $id_usuario, $diaria, $unidade, $odds);
                }
                break;
                
            case 'resetar':
                $stmt = $conexao->prepare("INSERT INTO controle (id_usuario, reset_banca, data_registro) VALUES (?, 1, NOW())");
                $stmt->bind_param("i", $id_usuario);
                $stmt->execute();
                
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
        
        $tipo_meta = detectarTipoMetaAtivo($conexao, $id_usuario);
        
        $total_deposito = getSoma($conexao, 'deposito', $id_usuario);
        $total_saque = getSoma($conexao, 'saque', $id_usuario);
        
        $dados_lucro_total = calcularLucro($conexao, $id_usuario);
        $dados_lucro_filtrado = calcularLucroFiltrado($conexao, $id_usuario, $periodo_ativo);
        
        $lucro_total = $dados_lucro_total['lucro'];
        $lucro_filtrado = $dados_lucro_filtrado['lucro'];
        
        $saldo_banca_total = $total_deposito - $total_saque + $lucro_total;
        
        $meta_resultado = calcularMetaDiariaComTipo($conexao, $id_usuario, $total_deposito, $total_saque, $lucro_total, $tipo_meta);
        $area_direita = calcularAreaDireita($conexao, $id_usuario, $saldo_banca_total, $tipo_meta);
        $metas_periodo = calcularMetasPorPeriodo($meta_resultado['meta_diaria'], $tipo_meta, $conexao, $id_usuario);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Operação realizada com sucesso',
            'banca' => $saldo_banca_total,
            'banca_formatada' => 'R$ ' . number_format($saldo_banca_total, 2, ',', '.'),
            'lucro' => $lucro_filtrado,
            'lucro_formatado' => 'R$ ' . number_format($lucro_filtrado, 2, ',', '.'),
            'lucro_total' => $lucro_total,
            'periodo_ativo' => $periodo_ativo,
            'tipo_meta' => $tipo_meta,
            'tipo_meta_texto' => $tipo_meta === 'fixa' ? 'Meta Fixa' : 'Meta Turbo',
            'tipo_meta_origem' => 'banco',
            'meta_diaria' => $meta_resultado['meta_diaria'],
            'meta_diaria_formatada' => 'R$ ' . number_format($meta_resultado['meta_diaria'], 2, ',', '.'),
            'meta_diaria_brl' => 'R$ ' . number_format($meta_resultado['meta_diaria'], 2, ',', '.'),
            'diaria_atual' => $meta_resultado['diaria_usada'],
            'unidade_atual' => $meta_resultado['unidade_usada'],
            'banca_inicial' => $meta_resultado['banca_inicial'],
            'banca_atual' => $meta_resultado['banca_atual'],
            'base_calculo' => $meta_resultado['base_calculo'],
            'descricao_calculo' => $meta_resultado['descricao_calculo'],
            'diaria_formatada' => $area_direita['diaria_formatada'],
            'unidade_entrada_formatada' => $area_direita['unidade_entrada_formatada'],
            'diaria_raw' => $area_direita['diaria_porcentagem'],
            'saldo_banca_total' => $area_direita['banca_usada'],
            'unidade_entrada_raw' => $area_direita['unidade_entrada'],
            'metas_periodo' => $metas_periodo,
            'meta_mensal' => $metas_periodo['meta_mensal'],
            'meta_mensal_formatada' => $metas_periodo['meta_mensal_formatada'],
            'meta_anual' => $metas_periodo['meta_anual'], 
            'meta_anual_formatada' => $metas_periodo['meta_anual_formatada'],
            'dias_restantes_mes' => $metas_periodo['dias_restantes_mes'],
            'dias_restantes_ano' => $metas_periodo['dias_restantes_ano'],
            'periodo_info' => $metas_periodo['periodo_info'],
            'formula_detalhada' => $meta_resultado['formula_detalhada']
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ✅ PROCESSAR REQUISIÇÕES GET
try {
    $periodo_ativo = detectarPeriodoAtivo();
    $tipo_meta = detectarTipoMetaAtivo($conexao, $id_usuario);
    
    $total_deposito = getSoma($conexao, 'deposito', $id_usuario);
    $total_saque = getSoma($conexao, 'saque', $id_usuario);
    
    $dados_lucro_total = calcularLucro($conexao, $id_usuario);
    $dados_lucro_filtrado = calcularLucroFiltrado($conexao, $id_usuario, $periodo_ativo);
    
    $total_green_total = $dados_lucro_total['green'];
    $total_red_total = $dados_lucro_total['red'];
    $lucro_total = $dados_lucro_total['lucro'];
    
    $total_green_filtrado = $dados_lucro_filtrado['green'];
    $total_red_filtrado = $dados_lucro_filtrado['red'];
    $lucro_filtrado = $dados_lucro_filtrado['lucro'];
    
    $saldo_banca_total = $total_deposito - $total_saque + $lucro_total;
    
    $ultima_diaria = getUltimoCampo($conexao, 'diaria', $id_usuario);
    $ultima_unidade = getUltimoCampo($conexao, 'unidade', $id_usuario);
    $ultima_odds = getUltimoCampo($conexao, 'odds', $id_usuario);
    
    $meta_resultado = calcularMetaDiariaComTipo($conexao, $id_usuario, $total_deposito, $total_saque, $lucro_total, $tipo_meta);
    $area_direita = calcularAreaDireita($conexao, $id_usuario, $saldo_banca_total, $tipo_meta);
    $metas_periodo = calcularMetasPorPeriodo($meta_resultado['meta_diaria'], $tipo_meta, $conexao, $id_usuario);
    
    echo json_encode([
        'success' => true,
        'banca' => $saldo_banca_total,
        'banca_formatada' => 'R$ ' . number_format($saldo_banca_total, 2, ',', '.'),
        'depositos_total' => $total_deposito,
        'depositos_formatado' => 'R$ ' . number_format($total_deposito, 2, ',', '.'),
        'saques_total' => $total_saque,
        'saques_formatado' => 'R$ ' . number_format($total_saque, 2, ',', '.'),
        'lucro' => $lucro_filtrado,
        'lucro_formatado' => 'R$ ' . number_format($lucro_filtrado, 2, ',', '.'),
        'green_total' => $total_green_filtrado,
        'green_formatado' => 'R$ ' . number_format($total_green_filtrado, 2, ',', '.'),
        'red_total' => $total_red_filtrado,
        'red_formatado' => 'R$ ' . number_format($total_red_filtrado, 2, ',', '.'),
        'lucro_total_historico' => $lucro_total,
        'lucro_total_formatado' => 'R$ ' . number_format($lucro_total, 2, ',', '.'),
        'lucro_total_display' => $lucro_total,
        'green_total_historico' => $total_green_total,
        'red_total_historico' => $total_red_total,
        'periodo_ativo' => $periodo_ativo,
        'tipo_meta' => $tipo_meta,
        'tipo_meta_texto' => $tipo_meta === 'fixa' ? 'Meta Fixa' : 'Meta Turbo',
        'tipo_meta_origem' => 'banco',
        'diaria' => $ultima_diaria ?? 2,
        'unidade' => $ultima_unidade ?? 2,
        'odds' => $ultima_odds ?? 1.5,
        'meta_diaria' => $meta_resultado['meta_diaria'],
        'meta_diaria_formatada' => 'R$ ' . number_format($meta_resultado['meta_diaria'], 2, ',', '.'),
        'meta_diaria_brl' => 'R$ ' . number_format($meta_resultado['meta_diaria'], 2, ',', '.'),
        'diaria_usada' => $meta_resultado['diaria_usada'],
        'unidade_usada' => $meta_resultado['unidade_usada'],
        'banca_inicial' => $meta_resultado['banca_inicial'],
        'banca_atual' => $meta_resultado['banca_atual'],
        'base_calculo' => $meta_resultado['base_calculo'],
        'descricao_calculo' => $meta_resultado['descricao_calculo'],
        'saldo_base_meta' => $meta_resultado['base_calculo'],
        'meta_display' => $meta_resultado['meta_diaria'],
        'meta_display_formatada' => 'R$ ' . number_format($meta_resultado['meta_diaria'], 2, ',', '.'),
        'rotulo_periodo' => $periodo_ativo === 'mes' ? 'Meta do Mês' : ($periodo_ativo === 'ano' ? 'Meta do Ano' : 'Meta do Dia'),
        'diaria_formatada' => $area_direita['diaria_formatada'],
        'unidade_entrada_formatada' => $area_direita['unidade_entrada_formatada'],
        'diaria_raw' => $area_direita['diaria_porcentagem'],
        'saldo_banca_total' => $area_direita['banca_usada'],
        'unidade_entrada_raw' => $area_direita['unidade_entrada'],
        'metas_periodo' => $metas_periodo,
        'meta_mensal' => $metas_periodo['meta_mensal'],
        'meta_mensal_formatada' => $metas_periodo['meta_mensal_formatada'],
        'meta_anual' => $metas_periodo['meta_anual'], 
        'meta_anual_formatada' => $metas_periodo['meta_anual_formatada'],
        'dias_restantes_mes' => $metas_periodo['dias_restantes_mes'],
        'dias_restantes_ano' => $metas_periodo['dias_restantes_ano'],
        'periodo_info' => $metas_periodo['periodo_info'],
        
        // ✅ INFORMAÇÕES DETALHADAS COM NOVA LÓGICA
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
            'formula_meta_fixa' => "Meta Fixa: Banca Inicial (R$ " . number_format($meta_resultado['banca_inicial'], 2, ',', '.') . ") × {$meta_resultado['diaria_usada']}% × {$meta_resultado['unidade_usada']} = R$ " . number_format($meta_resultado['meta_diaria'], 2, ',', '.'),
            'formula_meta_turbo' => $meta_resultado['lucro_positivo'] 
                ? "Meta Turbo (Lucro +): Banca Atual (R$ " . number_format($meta_resultado['banca_atual'], 2, ',', '.') . ") × {$meta_resultado['diaria_usada']}% × {$meta_resultado['unidade_usada']} = R$ " . number_format($meta_resultado['meta_diaria'], 2, ',', '.')
                : "Meta Turbo (Lucro -): Banca Inicial (R$ " . number_format($meta_resultado['banca_inicial'], 2, ',', '.') . ") × {$meta_resultado['diaria_usada']}% × {$meta_resultado['unidade_usada']} = R$ " . number_format($meta_resultado['meta_diaria'], 2, ',', '.'),
            'formula_aplicada' => $meta_resultado['descricao_calculo'],
            'formula_detalhada_completa' => $meta_resultado['formula_detalhada'],
            'lucro_por_periodo' => [
                'periodo_ativo' => $periodo_ativo,
                'lucro_filtrado' => $lucro_filtrado,
                'green_filtrado' => $total_green_filtrado,
                'red_filtrado' => $total_red_filtrado,
                'diferenca_total_filtrado' => $lucro_total - $lucro_filtrado
            ],
            
            // ✅ NOVA LÓGICA DE CÁLCULO DE DIAS
            'logica_meta_mensal' => $metas_periodo['periodo_info']['explicacao_mes'],
            'logica_meta_anual' => $metas_periodo['periodo_info']['explicacao_ano'],
            'primeiro_deposito_data' => $metas_periodo['periodo_info']['primeiro_deposito'],
            'dias_calculados_mes' => $metas_periodo['dias_restantes_mes'],
            'dias_calculados_ano' => $metas_periodo['dias_restantes_ano'],
            'formula_periodo_mensal' => $metas_periodo['periodo_info']['formula_mensal'],
            'formula_periodo_anual' => $metas_periodo['periodo_info']['formula_anual'],
            'exemplo_usuario_detalhado' => $metas_periodo['periodo_info']['exemplo_usuario'],
            'debug_calculo_dias' => $metas_periodo['periodo_info']['debug_completo']
        ],
        
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