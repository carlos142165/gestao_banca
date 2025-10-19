<?php
// ✅ ARQUIVO DADOS_BANCA.PHP - CORRIGIDO PARA VALORES DECIMAIS E CÁLCULO CORRETO DE DIAS

require_once 'config.php';
require_once 'carregar_sessao.php';

$id_usuario = $_SESSION['usuario_id'] ?? null;
if (!$id_usuario) {
    echo json_encode(['success' => false, 'message' => 'Usuário não logado']);
    exit;
}

// Funções auxiliares básicas
function getSoma($conexao, $campo, $id_usuario) {
    $stmt = $conexao->prepare("SELECT SUM($campo) FROM controle WHERE id_usuario = ? AND $campo > 0");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $stmt->bind_result($total);
    $stmt->fetch();
    $stmt->close();
    return $total ?? 0;
}

// ✅ CORRIGIDA: Manter decimais para diária
// ✅ CORRIGIDA: Manter decimais SEM arredondamento prematuro
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
    
    // ✅ CRÍTICO: NÃO arredondar aqui - retornar valor bruto
    // O arredondamento só deve acontecer na EXIBIÇÃO final
    if ($valor !== null) {
        return floatval($valor); // Sem round()
    }
    
    return $valor;
}

// ✅ DETECTAR TIPO DE META PELO BANCO
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

// ✅ BUSCAR PRIMEIRO DEPÓSITO
function getPrimeiroDeposito($conexao, $id_usuario) {
    // Agora busca a data do primeiro valor cadastrado do mentor
    try {
        $stmt = $conexao->prepare("
            SELECT DATE(data_criacao) as primeira_data
            FROM valor_mentores
            WHERE id_usuario = ?
            ORDER BY data_criacao ASC
            LIMIT 1
        ");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $stmt->bind_result($primeira_data);
        $stmt->fetch();
        $stmt->close();
        return $primeira_data;
    } catch (Exception $e) {
        error_log("Erro ao buscar primeiro valor do mentor: " . $e->getMessage());
        return null;
    }
}

// ✅ CALCULAR LUCRO FILTRADO POR PERÍODO
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

function calcularLucro($conexao, $id_usuario) {
    return calcularLucroFiltrado($conexao, $id_usuario, 'total');
}

// ✅ CORRIGIDA: CALCULAR META DIÁRIA COM DECIMAL
// ✅ CORRIGIDA: CALCULAR META DIÁRIA SEM ARREDONDAMENTO INTERMEDIÁRIO
// ✅ CORRIGIDA: ARREDONDAR META DIÁRIA LOGO APÓS O CÁLCULO
function calcularMetaDiariaComTipo($conexao, $id_usuario, $total_deposito, $total_saque, $lucro_total, $tipo_meta = 'turbo') {
    try {
        // Buscar porcentagem e unidade mais recentes
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
        
        // Valores padrão
        if ($diaria === null) $diaria = 2.00;
        else $diaria = floatval($diaria);
        
        if ($unidade === null) $unidade = 2;
        
        $banca_inicial = $total_deposito - $total_saque;
        
        // ✅ CALCULAR LUCRO ACUMULADO ATÉ ONTEM (excluindo hoje)
        $stmt_lucro_ontem = $conexao->prepare("
            SELECT 
                COALESCE(SUM(valor_green), 0) as total_green_ontem,
                COALESCE(SUM(valor_red), 0) as total_red_ontem
            FROM valor_mentores
            WHERE id_usuario = ? AND DATE(data_criacao) < CURDATE()
        ");
        $stmt_lucro_ontem->bind_param("i", $id_usuario);
        $stmt_lucro_ontem->execute();
        $stmt_lucro_ontem->bind_result($total_green_ontem, $total_red_ontem);
        $stmt_lucro_ontem->fetch();
        $stmt_lucro_ontem->close();
        
        $lucro_ate_ontem = $total_green_ontem - $total_red_ontem;
        
        // Banca com lucro até ontem (congelada para o dia de hoje)
        $banca_inicio_dia = $banca_inicial + $lucro_ate_ontem;
        
        // Cálculo da meta
        $porcentagem_decimal = $diaria / 100;
        $meta_diaria = 0;
        $base_calculo = 0;
        $descricao_calculo = '';
        
        if ($tipo_meta === 'fixa') {
            $base_calculo = $banca_inicial;
            $meta_diaria = $base_calculo * $porcentagem_decimal * $unidade;
            $descricao_calculo = "Meta Fixa: Apenas Banca (R$ " . number_format($banca_inicial, 2, ',', '.') . ") × {$diaria}% × {$unidade}";
            
        } else {
            // META TURBO
            if ($lucro_ate_ontem > 0) {
                $base_calculo = $banca_inicio_dia;
                $meta_diaria = $base_calculo * $porcentagem_decimal * $unidade;
                $descricao_calculo = "Meta Turbo: Banca + Lucro até Ontem (R$ " . number_format($banca_inicio_dia, 2, ',', '.') . ") × {$diaria}% × {$unidade}";
            } else {
                $base_calculo = $banca_inicial;
                $meta_diaria = $base_calculo * $porcentagem_decimal * $unidade;
                $descricao_calculo = "Meta Turbo (Lucro ≤ 0): Apenas Banca (R$ " . number_format($banca_inicial, 2, ',', '.') . ") × {$diaria}% × {$unidade}";
            }
        }
        
        // ✅ CRÍTICO: ARREDONDAR AQUI PARA 2 CASAS DECIMAIS
        // Este é o valor que será usado em TODOS os cálculos posteriores
        $meta_diaria = round($meta_diaria, 2);
        
        // Banca atual (com lucro de hoje incluído) - apenas para referência
        $banca_atual = $banca_inicial + $lucro_total;
        
        // Log detalhado
        error_log("META DIÁRIA CALCULADA:");
        error_log("  Base: R$ " . number_format($base_calculo, 2, ',', '.'));
        error_log("  Diária: {$diaria}%");
        error_log("  Unidade: {$unidade}");
        error_log("  Meta diária (arredondada): R$ " . number_format($meta_diaria, 2, ',', '.'));
        
        return [
            'meta_diaria' => $meta_diaria, // ✅ Valor já arredondado para 2 casas
            'diaria_usada' => $diaria,
            'unidade_usada' => $unidade,
            'banca_inicial' => $banca_inicial,
            'banca_atual' => $banca_atual,
            'banca_inicio_dia' => $banca_inicio_dia,
            'lucro_ate_ontem' => $lucro_ate_ontem,
            'base_calculo' => $base_calculo,
            'tipo_meta' => $tipo_meta,
            'descricao_calculo' => $descricao_calculo,
            'lucro_total' => $lucro_total,
            'lucro_positivo' => $lucro_ate_ontem > 0,
            'formula_detalhada' => [
                'banca_inicial' => $banca_inicial,
                'lucro_ate_ontem' => $lucro_ate_ontem,
                'banca_inicio_dia' => $banca_inicio_dia,
                'lucro_total' => $lucro_total,
                'banca_atual' => $banca_atual,
                'tipo_aplicado' => $tipo_meta,
                'base_usada' => $base_calculo,
                'porcentagem' => $diaria,
                'unidade' => $unidade,
                'resultado' => $meta_diaria,
                'logica_fixa' => $tipo_meta === 'fixa' ? 'sempre_usa_apenas_banca' : 'nao_aplicavel',
                'logica_turbo' => $tipo_meta === 'turbo' ? 
                    ($lucro_ate_ontem > 0 ? 'usa_banca_mais_lucro_ate_ontem' : 'usa_apenas_banca') 
                    : 'nao_aplicavel',
                'explicacao_fixa' => 'Meta Fixa: Sempre calcula com valor da banca (depósitos - saques)',
                'explicacao_turbo' => 'Meta Turbo: Calcula com banca + lucro dos dias anteriores. Lucro de hoje não conta, só após 00:00h'
            ]
        ];
        
    } catch (Exception $e) {
        error_log("Erro ao calcular meta diária com tipo: " . $e->getMessage());
        return [
            'meta_diaria' => 0,
            'diaria_usada' => 2.00,
            'unidade_usada' => 2,
            'banca_inicial' => 0,
            'banca_atual' => 0,
            'banca_inicio_dia' => 0,
            'lucro_ate_ontem' => 0,
            'base_calculo' => 0,
            'tipo_meta' => $tipo_meta,
            'descricao_calculo' => 'Erro no cálculo',
            'lucro_total' => 0,
            'lucro_positivo' => false,
            'formula_detalhada' => []
        ];
    }
}
// ✅ CORRIGIDA: ÁREA DIREITA COM DECIMAL
// ✅ CORRIGIDA: ÁREA DIREITA (UND) - MESMA LÓGICA DA META
function calcularAreaDireita($conexao, $id_usuario, $total_deposito, $total_saque, $lucro_total, $tipo_meta = 'turbo') {
    try {
        $ultima_diaria = getUltimoCampo($conexao, 'diaria', $id_usuario);
        $diaria = $ultima_diaria !== null ? $ultima_diaria : 2.00;
        
        $banca_inicial = $total_deposito - $total_saque;
        
        // ✅ CALCULAR LUCRO ACUMULADO ATÉ ONTEM (excluindo hoje)
        $stmt_lucro_ontem = $conexao->prepare("
            SELECT 
                COALESCE(SUM(valor_green), 0) as total_green_ontem,
                COALESCE(SUM(valor_red), 0) as total_red_ontem
            FROM valor_mentores
            WHERE id_usuario = ? AND DATE(data_criacao) < CURDATE()
        ");
        $stmt_lucro_ontem->bind_param("i", $id_usuario);
        $stmt_lucro_ontem->execute();
        $stmt_lucro_ontem->bind_result($total_green_ontem, $total_red_ontem);
        $stmt_lucro_ontem->fetch();
        $stmt_lucro_ontem->close();
        
        $lucro_ate_ontem = $total_green_ontem - $total_red_ontem;
        
        // Banca com lucro até ontem (congelada para o dia de hoje)
        $banca_inicio_dia = $banca_inicial + $lucro_ate_ontem;
        
        // Banca atual (com lucro de hoje incluído) - apenas para referência
        $banca_atual = $banca_inicial + $lucro_total;
        
        $base_calculo_und = 0;
        $regra_aplicada = '';
        
        if ($tipo_meta === 'fixa') {
            // ✅ META FIXA: SEMPRE usa apenas banca inicial
            $base_calculo_und = $banca_inicial;
            $regra_aplicada = 'Meta Fixa: Sempre usa apenas Banca';
            
        } else {
            // ✅ META TURBO: usa banca + lucro ATÉ ONTEM (congelado até 00:00h)
            if ($lucro_ate_ontem > 0) {
                $base_calculo_und = $banca_inicio_dia;
                $regra_aplicada = 'Meta Turbo: Banca + Lucro até Ontem (congelado até 00:00h)';
            } else {
                $base_calculo_und = $banca_inicial;
                $regra_aplicada = 'Meta Turbo (Lucro ≤ 0): Apenas Banca';
            }
        }
        
        $unidade_entrada = $base_calculo_und * ($diaria / 100);
        
        return [
            'diaria_porcentagem' => $diaria,
            'banca_usada' => $banca_atual, // Banca atual (para exibição)
            'banca_inicial' => $banca_inicial,
            'banca_inicio_dia' => $banca_inicio_dia,
            'lucro_atual' => $lucro_total,
            'lucro_ate_ontem' => $lucro_ate_ontem,
            'base_calculo_und' => $base_calculo_und,
            'unidade_entrada' => $unidade_entrada,
            'tipo_meta_info' => $tipo_meta,
            'regra_aplicada' => $regra_aplicada,
            'diaria_formatada' => number_format($diaria, 2, ',', '') . '%',
            'unidade_entrada_formatada' => 'R$ ' . number_format($unidade_entrada, 2, ',', '.'),
            'formula_detalhada' => [
                'banca_inicial' => $banca_inicial,
                'lucro_ate_ontem' => $lucro_ate_ontem,
                'banca_inicio_dia' => $banca_inicio_dia,
                'lucro_total' => $lucro_total,
                'banca_atual' => $banca_atual,
                'base_usada_und' => $base_calculo_und,
                'diaria_percentual' => $diaria,
                'resultado_und' => $unidade_entrada,
                'logica_fixa' => $tipo_meta === 'fixa' ? 'sempre_usa_apenas_banca' : 'nao_aplicavel',
                'logica_turbo' => $tipo_meta === 'turbo' ? 
                    ($lucro_ate_ontem > 0 ? 'usa_banca_mais_lucro_ate_ontem' : 'usa_apenas_banca') 
                    : 'nao_aplicavel',
                'explicacao' => $regra_aplicada,
                'valor_congelado' => 'UND calculada com lucro até ontem. Valor fixo até 00:00h.'
            ]
        ];
        
    } catch (Exception $e) {
        error_log("Erro ao calcular área direita: " . $e->getMessage());
        return [
            'diaria_porcentagem' => 2.00,
            'banca_usada' => 0,
            'banca_inicial' => 0,
            'banca_inicio_dia' => 0,
            'lucro_atual' => 0,
            'lucro_ate_ontem' => 0,
            'base_calculo_und' => 0,
            'unidade_entrada' => 0,
            'tipo_meta_info' => $tipo_meta,
            'regra_aplicada' => 'Erro no cálculo',
            'diaria_formatada' => '2,00%',
            'unidade_entrada_formatada' => 'R$ 0,00',
            'formula_detalhada' => []
        ];
    }
}

// ✅ CALCULAR DIAS RESTANTES - CORRIGIDO
function calcularDiasRestantes($conexao, $id_usuario) {
    $hoje = new DateTime();
    
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
    
    // ✅ CORREÇÃO PRINCIPAL: Calcular dias do mês SEMPRE a partir do primeiro depósito
    if ($usar_data_atual) {
        // Sem depósito: usar data atual
        $dia_atual = (int)$hoje->format('d');
        $dias_meta_mensal = $ultimo_dia_mes - $dia_atual + 1;
        $explicacao_mensal = "Sem depósito: {$dias_meta_mensal} dias restantes do mês {$mes_atual}";
    } else {
        $mes_primeiro_deposito = (int)$data_primeiro_deposito->format('m');
        $ano_primeiro_deposito = (int)$data_primeiro_deposito->format('Y');
        
        if ($ano_primeiro_deposito === $ano_atual && $mes_primeiro_deposito === $mes_atual) {
            // ✅ CORREÇÃO: Calcular do dia do depósito ATÉ O FIM DO MÊS (não até hoje)
            $dia_deposito = (int)$data_primeiro_deposito->format('d');
            $dias_meta_mensal = $ultimo_dia_mes - $dia_deposito + 1;
            $explicacao_mensal = "Primeiro depósito em {$dia_deposito}/{$mes_atual}: Do dia {$dia_deposito} até dia {$ultimo_dia_mes} = {$dias_meta_mensal} dias";
        } else {
            // ✅ CORREÇÃO: Se o depósito foi em mês anterior, contar MÊS COMPLETO
            $dias_meta_mensal = $ultimo_dia_mes;
            $explicacao_mensal = "Depósito em mês anterior: Mês completo de {$mes_atual} = {$dias_meta_mensal} dias (dia 1 até {$ultimo_dia_mes})";
        }
    }
    
    // ✅ CORREÇÃO: Calcular dias do ano SEMPRE do primeiro depósito até 31/12
    if ($usar_data_atual) {
        // Sem depósito: usar data atual até fim do ano
        $fim_ano = new DateTime($ano_atual . '-12-31 23:59:59');
        $diferenca = $hoje->diff($fim_ano);
        $dias_meta_anual = $diferenca->days + 1;
        $explicacao_anual = "Sem depósito: {$dias_meta_anual} dias restantes do ano {$ano_atual}";
    } else {
        if ($data_primeiro_deposito->format('Y') === (string)$ano_atual) {
            // ✅ CORREÇÃO: Do primeiro depósito até 31/12 (não até hoje)
            $fim_ano = new DateTime($ano_atual . '-12-31 23:59:59');
            $diferenca = $data_primeiro_deposito->diff($fim_ano);
            $dias_meta_anual = $diferenca->days + 1;
            $explicacao_anual = "Do primeiro depósito ({$data_primeiro_deposito->format('d/m/Y')}) até 31/12/{$ano_atual} = {$dias_meta_anual} dias";
        } else {
            // ✅ CORREÇÃO: Se depósito foi em ano anterior, contar ANO COMPLETO
            $dias_meta_anual = 365;
            if (date('L', mktime(0, 0, 0, 1, 1, $ano_atual))) {
                $dias_meta_anual = 366; // Ano bissexto
            }
            $explicacao_anual = "Depósito em ano anterior: Ano completo {$ano_atual} = {$dias_meta_anual} dias (01/01 até 31/12)";
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
            'exemplo_usuario' => [
                'primeiro_deposito' => $primeira_data_deposito ?? 'Não encontrado',
                'mes_atual_info' => "Mês {$mes_atual}/{$ano_atual}",
                'calculo_mensal' => $explicacao_mensal,
                'calculo_anual' => $explicacao_anual,
                'formula_mensal' => "Meta Diária × {$dias_meta_mensal} dias = Meta Mensal",
                'formula_anual' => "Meta Diária × {$dias_meta_anual} dias = Meta Anual"
            ],
            'debug_info' => [
                'logica_aplicada' => $usar_data_atual ? 'sem_deposito' : 'com_deposito',
                'primeiro_deposito_encontrado' => !$usar_data_atual,
                'mesmo_mes_ano_deposito' => !$usar_data_atual && 
                    ($data_primeiro_deposito->format('Y-m') === $hoje->format('Y-m')),
                'tipo_calculo_mensal' => !$usar_data_atual && 
                    ($data_primeiro_deposito->format('Y-m') === $hoje->format('Y-m')) 
                    ? 'do_deposito_ate_fim_mes' : 'mes_completo',
                'tipo_calculo_anual' => 'do_primeiro_deposito_ate_fim_ano'
            ]
        ]
    ];
}

// ✅ CALCULAR METAS POR PERÍODO
// ✅ CALCULAR METAS POR PERÍODO - USA O VALOR JÁ ARREDONDADO
function calcularMetasPorPeriodo($meta_diaria, $tipo_meta = 'turbo', $conexao, $id_usuario) {
    $diasCalculados = calcularDiasRestantes($conexao, $id_usuario);
    
    // ✅ Valor já vem arredondado da função anterior
    $meta_diaria_precisa = floatval($meta_diaria);
    
    // ✅ Multiplicar pelo número de dias (agora vai dar resultado correto)
    $meta_mensal_precisa = $meta_diaria_precisa * floatval($diasCalculados['mes']);
    $meta_anual_precisa = $meta_diaria_precisa * floatval($diasCalculados['ano']);
    
    // Log para debug
    error_log("CÁLCULO META MENSAL:");
    error_log("  Meta Diária: R$ " . number_format($meta_diaria_precisa, 2, ',', '.'));
    error_log("  Dias no mês: " . $diasCalculados['mes']);
    error_log("  Meta Mensal: R$ " . number_format($meta_mensal_precisa, 2, ',', '.'));
    
    return [
        'meta_diaria' => $meta_diaria_precisa,
        'meta_mensal' => $meta_mensal_precisa,
        'meta_anual' => $meta_anual_precisa,
        'tipo_meta' => $tipo_meta,
        'dias_restantes_mes' => $diasCalculados['mes'],
        'dias_restantes_ano' => $diasCalculados['ano'],
        'meta_diaria_formatada' => 'R$ ' . number_format($meta_diaria_precisa, 2, ',', '.'),
        'meta_mensal_formatada' => 'R$ ' . number_format($meta_mensal_precisa, 2, ',', '.'),
        'meta_anual_formatada' => 'R$ ' . number_format($meta_anual_precisa, 2, ',', '.'),
        'periodo_info' => [
            'data_hoje' => $diasCalculados['info']['data_atual'],
            'primeiro_deposito' => $diasCalculados['info']['primeiro_deposito'],
            'mes_atual' => $diasCalculados['info']['mes_atual'],
            'ano_atual' => $diasCalculados['info']['ano_atual'],
            'ultimo_dia_mes' => $diasCalculados['info']['ultimo_dia_mes'],
            'explicacao_mes' => $diasCalculados['info']['explicacao_mensal'],
            'explicacao_ano' => $diasCalculados['info']['explicacao_anual'],
            'formula_diaria' => "Meta Diária ({$tipo_meta}): R$ " . number_format($meta_diaria_precisa, 2, ',', '.'),
            'formula_mensal' => "Meta Mensal ({$tipo_meta}): R$ " . number_format($meta_diaria_precisa, 2, ',', '.') . " × {$diasCalculados['mes']} dias = R$ " . number_format($meta_mensal_precisa, 2, ',', '.'),
            'formula_anual' => "Meta Anual ({$tipo_meta}): R$ " . number_format($meta_diaria_precisa, 2, ',', '.') . " × {$diasCalculados['ano']} dias = R$ " . number_format($meta_anual_precisa, 2, ',', '.'),
            'debug_completo' => $diasCalculados['info']['debug_info'],
            'exemplo_usuario' => $diasCalculados['info']['exemplo_usuario']
        ]
    ];
}

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

// ✅ PROCESSAR POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    try {
        $acao = $input['acao'] ?? '';
        $valor = floatval($input['valor'] ?? 0);
        
        // ✅ CRÍTICO: Processar diária como decimal
        $diaria_raw = $input['diaria'] ?? 2;
        $diaria = round(floatval(str_replace(',', '.', $diaria_raw)), 2);
        
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
                    $stmt->bind_param("iddid", $id_usuario, $valor, $diaria, $unidade, $odds);
                }
                break;
                
            case 'saque':
                if ($campo_meta) {
                    $stmt = $conexao->prepare("INSERT INTO controle (id_usuario, saque, diaria, unidade, odds, meta, data_registro) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->bind_param("iddids", $id_usuario, $valor, $diaria, $unidade, $odds, $campo_meta);
                } else {
                    $stmt = $conexao->prepare("INSERT INTO controle (id_usuario, saque, diaria, unidade, odds, data_registro) VALUES (?, ?, ?, ?, ?, NOW())");
                    $stmt->bind_param("iddid", $id_usuario, $valor, $diaria, $unidade, $odds);
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
        $area_direita = calcularAreaDireita($conexao, $id_usuario, $total_deposito, $total_saque, $lucro_total, $tipo_meta);
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
            'diaria_atual' => $meta_resultado['diaria_usada'],
            'unidade_atual' => $meta_resultado['unidade_usada'],
            'diaria_formatada' => $area_direita['diaria_formatada'],
            'unidade_entrada_formatada' => $area_direita['unidade_entrada_formatada'],
            'diaria_raw' => $area_direita['diaria_porcentagem'],
            'meta_mensal' => $metas_periodo['meta_mensal'],
            'meta_anual' => $metas_periodo['meta_anual'],
            'dias_restantes_mes' => $metas_periodo['dias_restantes_mes'],
            'dias_restantes_ano' => $metas_periodo['dias_restantes_ano']
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ✅ PROCESSAR GET
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
    $area_direita = calcularAreaDireita($conexao, $id_usuario, $total_deposito, $total_saque, $lucro_total, $tipo_meta);
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
        'diaria' => $ultima_diaria ?? 2.00,
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
            'formula_detalhada_completa' => $meta_resultado['formula_detalhada'],
            'lucro_por_periodo' => [
                'periodo_ativo' => $periodo_ativo,
                'lucro_filtrado' => $lucro_filtrado,
                'green_filtrado' => $total_green_filtrado,
                'red_filtrado' => $total_red_filtrado,
                'diferenca_total_filtrado' => $lucro_total - $lucro_filtrado
            ],
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
            'regra_aplicada_und' => $area_direita['regra_aplicada'],
            'base_calculo_und' => $area_direita['base_calculo_und'],
            'banca_inicial' => $area_direita['banca_inicial'],
            'banca_atual' => $area_direita['banca_usada'],
            'lucro_atual' => $area_direita['lucro_atual'],
            'lucro_negativo' => $lucro_total < 0,
            'formula_und' => "Base UND: R$ " . number_format($area_direita['base_calculo_und'], 2, ',', '.') . 
                             " × {$area_direita['diaria_porcentagem']}% = {$area_direita['unidade_entrada_formatada']}",
            'explicacao_detalhada' => [
                'tipo_meta' => $tipo_meta,
                'lucro_total' => $lucro_total,
                'condicao' => $lucro_total < 0 ? 'saldo_negativo' : ($tipo_meta === 'fixa' ? 'meta_fixa' : 'meta_turbo'),
                'base_usada' => $area_direita['base_calculo_und'],
                'regra' => $area_direita['regra_aplicada']
            ],
            'depositos_total' => $total_deposito,
            'saques_total' => $total_saque,
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
        'meta_mensal' => 0,
        'meta_anual' => 0,
        'diaria_formatada' => '2,00%',
        'unidade_entrada_formatada' => 'R$ 0,00',
        'periodo_ativo' => 'dia',
        'lucro' => 0,
        'lucro_formatado' => 'R$ 0,00'
    ]);
}
?>