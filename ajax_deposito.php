<?php
require_once 'config.php';
require_once 'carregar_sessao.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sessão inválida']);
    exit();
}

$id_usuario = intval($_SESSION['usuario_id']);

// ✅ FUNÇÃO: Buscar data do PRIMEIRO VALOR CADASTRADO DO MENTOR
function getPrimeiroValorMentor($conexao, $id_usuario) {
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
        error_log("Erro ao buscar primeiro valor mentor: " . $e->getMessage());
        return null;
    }
}

// ✅ FUNÇÃO: Calcular lucro do MÊS ATUAL
function calcularLucroMesAtual($conexao, $id_usuario) {
    try {
        $stmt = $conexao->prepare("
            SELECT 
                COALESCE(SUM(valor_green), 0) as total_green_mes,
                COALESCE(SUM(valor_red), 0) as total_red_mes
            FROM valor_mentores
            WHERE id_usuario = ? 
            AND MONTH(data_criacao) = MONTH(CURDATE())
            AND YEAR(data_criacao) = YEAR(CURDATE())
        ");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $stmt->bind_result($total_green_mes, $total_red_mes);
        $stmt->fetch();
        $stmt->close();
        
        return $total_green_mes - $total_red_mes;
    } catch (Exception $e) {
        error_log("Erro ao calcular lucro do mês: " . $e->getMessage());
        return 0;
    }
}

// ✅ FUNÇÃO: Calcular lucro do ANO ATUAL
function calcularLucroAnoAtual($conexao, $id_usuario) {
    try {
        $stmt = $conexao->prepare("
            SELECT 
                COALESCE(SUM(valor_green), 0) as total_green_ano,
                COALESCE(SUM(valor_red), 0) as total_red_ano
            FROM valor_mentores
            WHERE id_usuario = ? 
            AND YEAR(data_criacao) = YEAR(CURDATE())
        ");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $stmt->bind_result($total_green_ano, $total_red_ano);
        $stmt->fetch();
        $stmt->close();
        
        return $total_green_ano - $total_red_ano;
    } catch (Exception $e) {
        error_log("Erro ao calcular lucro do ano: " . $e->getMessage());
        return 0;
    }
}

// ✅ FUNÇÃO: Calcular lucro até ONTEM
function calcularLucroAteOntem($conexao, $id_usuario) {
    try {
        $stmt = $conexao->prepare("
            SELECT 
                COALESCE(SUM(valor_green), 0) as total_green_ontem,
                COALESCE(SUM(valor_red), 0) as total_red_ontem
            FROM valor_mentores
            WHERE id_usuario = ? AND DATE(data_criacao) < CURDATE()
        ");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $stmt->bind_result($total_green_ontem, $total_red_ontem);
        $stmt->fetch();
        $stmt->close();
        
        return $total_green_ontem - $total_red_ontem;
    } catch (Exception $e) {
        error_log("Erro ao calcular lucro até ontem: " . $e->getMessage());
        return 0;
    }
}

// ✅ FUNÇÃO: Calcular dias restantes baseado no PRIMEIRO VALOR DO MENTOR
function calcularDiasRestantes($conexao, $id_usuario) {
    $hoje = new DateTime();
    
    $primeira_data_valor = getPrimeiroValorMentor($conexao, $id_usuario);
    
    if (!$primeira_data_valor) {
        $data_primeiro_valor = $hoje;
        $usar_data_atual = true;
    } else {
        $data_primeiro_valor = new DateTime($primeira_data_valor);
        $usar_data_atual = false;
    }
    
    $mes_atual = (int)$hoje->format('m');
    $ano_atual = (int)$hoje->format('Y');
    $ultimo_dia_mes = (int)$hoje->format('t');
    
    // ✅ CÁLCULO MENSAL
    if ($usar_data_atual) {
        $dia_atual = (int)$hoje->format('d');
        $dias_meta_mensal = $ultimo_dia_mes - $dia_atual + 1;
        $explicacao_mensal = "Sem valor cadastrado: {$dias_meta_mensal} dias restantes do mês {$mes_atual}";
    } else {
        $mes_primeiro_valor = (int)$data_primeiro_valor->format('m');
        $ano_primeiro_valor = (int)$data_primeiro_valor->format('Y');
        
        if ($ano_primeiro_valor === $ano_atual && $mes_primeiro_valor === $mes_atual) {
            $dia_valor = (int)$data_primeiro_valor->format('d');
            $dias_meta_mensal = $ultimo_dia_mes - $dia_valor + 1;
            $explicacao_mensal = "Primeiro valor em {$dia_valor}/{$mes_atual}: Do dia {$dia_valor} até dia {$ultimo_dia_mes} = {$dias_meta_mensal} dias";
        } else {
            $dias_meta_mensal = $ultimo_dia_mes;
            $explicacao_mensal = "Valor em mês anterior: Mês completo de {$mes_atual} = {$dias_meta_mensal} dias (dia 1 até {$ultimo_dia_mes})";
        }
    }
    
    // ✅ CÁLCULO ANUAL
    if ($usar_data_atual) {
        $fim_ano = new DateTime($ano_atual . '-12-31 23:59:59');
        $diferenca = $hoje->diff($fim_ano);
        $dias_meta_anual = $diferenca->days + 1;
        $explicacao_anual = "Sem valor cadastrado: {$dias_meta_anual} dias restantes do ano {$ano_atual}";
    } else {
        if ($data_primeiro_valor->format('Y') === (string)$ano_atual) {
            $fim_ano = new DateTime($ano_atual . '-12-31 23:59:59');
            $diferenca = $data_primeiro_valor->diff($fim_ano);
            $dias_meta_anual = $diferenca->days + 1;
            $explicacao_anual = "Do primeiro valor ({$data_primeiro_valor->format('d/m/Y')}) até 31/12/{$ano_atual} = {$dias_meta_anual} dias";
        } else {
            $dias_meta_anual = 365;
            if (date('L', mktime(0, 0, 0, 1, 1, $ano_atual))) {
                $dias_meta_anual = 366;
            }
            $explicacao_anual = "Valor em ano anterior: Ano completo {$ano_atual} = {$dias_meta_anual} dias (01/01 até 31/12)";
        }
    }
    
    return [
        'mes' => $dias_meta_mensal,
        'ano' => $dias_meta_anual,
        'explicacao_mensal' => $explicacao_mensal,
        'explicacao_anual' => $explicacao_anual,
        'data_primeiro_valor_mentor' => $primeira_data_valor,
        'usando_data_atual' => $usar_data_atual
    ];
}

// ✅ FUNÇÃO: Detectar tipo de meta
function detectarTipoMeta($conexao, $id_usuario) {
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
        
        if (!$ultimaMeta) return 'Meta Turbo';
        
        $metaLower = strtolower(trim($ultimaMeta));
        
        if (strpos($metaLower, 'fixa') !== false) return 'Meta Fixa';
        if (strpos($metaLower, 'turbo') !== false) return 'Meta Turbo';
        
        return 'Meta Turbo';
        
    } catch (Exception $e) {
        error_log("Erro ao detectar tipo de meta: " . $e->getMessage());
        return 'Meta Turbo';
    }
}

// ✅ FUNÇÃO: Calcular meta diária com tipo
function calcularMetaDiariaComTipo($conexao, $id_usuario, $total_deposito, $total_saque, $lucro_total, $tipo_meta = 'Meta Turbo') {
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
        
        if ($diaria === null) $diaria = 1.00;
        else $diaria = round(floatval($diaria), 2);
        
        if ($unidade === null) $unidade = 1;
        
        $banca_inicial = $total_deposito - $total_saque;
        $lucro_ate_ontem = calcularLucroAteOntem($conexao, $id_usuario);
        $banca_inicio_dia = $banca_inicial + $lucro_ate_ontem;
        
        $porcentagem_decimal = $diaria / 100;
        $meta_diaria = 0;
        $base_calculo = 0;
        
        if ($tipo_meta === 'Meta Fixa') {
            $base_calculo = $banca_inicial;
            $meta_diaria = $base_calculo * $porcentagem_decimal * $unidade;
        } else {
            if ($lucro_ate_ontem > 0) {
                $base_calculo = $banca_inicio_dia;
                $meta_diaria = $base_calculo * $porcentagem_decimal * $unidade;
            } else {
                $base_calculo = $banca_inicial;
                $meta_diaria = $base_calculo * $porcentagem_decimal * $unidade;
            }
        }
        
        $banca_atual = $banca_inicial + $lucro_total;
        
        return [
            'meta_diaria' => $meta_diaria,
            'diaria_usada' => $diaria,
            'unidade_usada' => $unidade,
            'banca_inicial' => $banca_inicial,
            'banca_atual' => $banca_atual,
            'banca_inicio_dia' => $banca_inicio_dia,
            'lucro_ate_ontem' => $lucro_ate_ontem,
            'base_calculo' => $base_calculo,
            'tipo_meta' => $tipo_meta
        ];
        
    } catch (Exception $e) {
        error_log("Erro ao calcular meta: " . $e->getMessage());
        return [
            'meta_diaria' => 0,
            'diaria_usada' => 1.00,
            'unidade_usada' => 1,
            'banca_inicial' => 0,
            'banca_atual' => 0,
            'banca_inicio_dia' => 0,
            'lucro_ate_ontem' => 0,
            'base_calculo' => 0,
            'tipo_meta' => $tipo_meta
        ];
    }
}

// ✅ FUNÇÃO CORRIGIDA: Calcular metas por período (MENSAL e ANUAL)
function calcularMetasPorPeriodo($meta_diaria, $conexao, $id_usuario) {
    $diasCalculados = calcularDiasRestantes($conexao, $id_usuario);
    
    // ✅ CALCULAR LUCRO DO MÊS E DO ANO ATUAL
    $lucro_mes_atual = calcularLucroMesAtual($conexao, $id_usuario);
    $lucro_ano_atual = calcularLucroAnoAtual($conexao, $id_usuario);
    
    // ✅ META MENSAL: Meta base - Lucro do mês atual
    $meta_mensal_base = $meta_diaria * $diasCalculados['mes'];
    $meta_mensal = $meta_mensal_base;
    
    if ($lucro_mes_atual < 0) {
        // Se tiver prejuízo, adiciona à meta
        $meta_mensal = $meta_mensal_base + abs($lucro_mes_atual);
    } else if ($lucro_mes_atual > 0) {
        // Se tiver lucro, subtrai da meta
        $meta_mensal = max(0, $meta_mensal_base - $lucro_mes_atual);
    }
    
    // ✅ META ANUAL: Meta base - Lucro do ano atual
    $meta_anual_base = $meta_diaria * $diasCalculados['ano'];
    $meta_anual = $meta_anual_base;
    
    if ($lucro_ano_atual < 0) {
        // Se tiver prejuízo, adiciona à meta
        $meta_anual = $meta_anual_base + abs($lucro_ano_atual);
    } else if ($lucro_ano_atual > 0) {
        // Se tiver lucro, subtrai da meta
        $meta_anual = max(0, $meta_anual_base - $lucro_ano_atual);
    }
    
    return [
        'meta_diaria' => $meta_diaria,
        'meta_mensal' => $meta_mensal,
        'meta_anual' => $meta_anual,
        'meta_mensal_base' => $meta_mensal_base,
        'meta_anual_base' => $meta_anual_base,
        'lucro_mes_atual' => $lucro_mes_atual,
        'lucro_ano_atual' => $lucro_ano_atual,
        'dias_restantes_mes' => $diasCalculados['mes'],
        'dias_restantes_ano' => $diasCalculados['ano'],
        'meta_diaria_formatada' => 'R$ ' . number_format($meta_diaria, 2, ',', '.'),
        'meta_mensal_formatada' => 'R$ ' . number_format($meta_mensal, 2, ',', '.'),
        'meta_anual_formatada' => 'R$ ' . number_format($meta_anual, 2, ',', '.'),
        'explicacao_mensal' => $diasCalculados['explicacao_mensal'],
        'explicacao_anual' => $diasCalculados['explicacao_anual'],
        'data_primeiro_valor_mentor' => $diasCalculados['data_primeiro_valor_mentor']
    ];
}

// ✅ FUNÇÃO: Calcular dados da área direita
function calcularAreaDireita($conexao, $id_usuario, $saldo_banca_total) {
    try {
        $stmt = $conexao->prepare("
            SELECT diaria FROM controle
            WHERE id_usuario = ? AND diaria IS NOT NULL
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $stmt->bind_result($ultima_diaria);
        $stmt->fetch();
        $stmt->close();
        
        $diaria = $ultima_diaria !== null ? round(floatval($ultima_diaria), 2) : 1.00;
        
        $lucro_ate_ontem = calcularLucroAteOntem($conexao, $id_usuario);
        
        $stmt_dep = $conexao->prepare("SELECT SUM(deposito) FROM controle WHERE id_usuario = ? AND deposito > 0");
        $stmt_dep->bind_param("i", $id_usuario);
        $stmt_dep->execute();
        $stmt_dep->bind_result($total_deposito);
        $stmt_dep->fetch();
        $stmt_dep->close();
        
        $stmt_saq = $conexao->prepare("SELECT SUM(saque) FROM controle WHERE id_usuario = ? AND saque > 0");
        $stmt_saq->bind_param("i", $id_usuario);
        $stmt_saq->execute();
        $stmt_saq->bind_result($total_saque);
        $stmt_saq->fetch();
        $stmt_saq->close();
        
        $banca_inicial = ($total_deposito ?? 0) - ($total_saque ?? 0);
        $banca_inicio_dia = $banca_inicial + $lucro_ate_ontem;
        $unidade_entrada = $banca_inicio_dia * ($diaria / 100);
        
        return [
            'diaria_porcentagem' => $diaria,
            'saldo_banca_total' => $saldo_banca_total,
            'banca_inicio_dia' => $banca_inicio_dia,
            'lucro_ate_ontem' => $lucro_ate_ontem,
            'unidade_entrada' => $unidade_entrada,
            'diaria_formatada' => number_format($diaria, 2, ',', '') . '%',
            'diaria_formatada_inteiro' => number_format($diaria, 0) . '%',
            'unidade_entrada_formatada' => 'R$ ' . number_format($unidade_entrada, 2, ',', '.')
        ];
        
    } catch (Exception $e) {
        error_log("Erro ao calcular área direita: " . $e->getMessage());
        return [
            'diaria_porcentagem' => 1.00,
            'saldo_banca_total' => 0,
            'banca_inicio_dia' => 0,
            'lucro_ate_ontem' => 0,
            'unidade_entrada' => 0,
            'diaria_formatada' => '1,00%',
            'diaria_formatada_inteiro' => '1%',
            'unidade_entrada_formatada' => 'R$ 0,00'
        ];
    }
}

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
    
    if ($campo === 'diaria' && $valor !== null) {
        return round(floatval($valor), 2);
    }
    
    return $valor;
}

function getUltimaMeta($conexao, $id_usuario) {
    $stmt = $conexao->prepare("
        SELECT meta FROM controle
        WHERE id_usuario = ? AND meta IS NOT NULL
        ORDER BY id DESC LIMIT 1
    ");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $stmt->bind_result($meta);
    $stmt->fetch();
    $stmt->close();
    return $meta ?? 'Meta Fixa';
}

function calcularLucro($conexao, $id_usuario) {
    $stmt = $conexao->prepare("
        SELECT 
            COALESCE(SUM(valor_green), 0) AS total_green,
            COALESCE(SUM(valor_red), 0) AS total_red
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

function validarTipoMeta($tipoMeta) {
    $tipos_validos = ['Meta Fixa', 'Meta Turbo'];
    return in_array($tipoMeta, $tipos_validos) ? $tipoMeta : 'Meta Fixa';
}

// ✅ PROCESSAR REQUISIÇÕES POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    $acao = $data['acao'] ?? '';
    $valor = abs(floatval($data['valor'] ?? 0));
    
    $diaria_raw = $data['diaria'] ?? 1;
    $diaria = round(floatval(str_replace(',', '.', $diaria_raw)), 2);
    
    $unidade = intval($data['unidade'] ?? 1);
    $odds = isset($data['odds']) ? floatval(str_replace(',', '.', $data['odds'])) : 1.5;
    $tipoMeta = validarTipoMeta($data['tipoMeta'] ?? 'Meta Fixa');

    // RESET
    if ($acao === 'resetar') {
        try {
            $conexao->begin_transaction();
            
            $stmt1 = $conexao->prepare("DELETE FROM controle WHERE id_usuario = ?");
            $stmt1->bind_param("i", $id_usuario);
            $stmt1->execute();
            $stmt1->close();

            $stmt2 = $conexao->prepare("DELETE FROM valor_mentores WHERE id_usuario = ?");
            $stmt2->bind_param("i", $id_usuario);
            $stmt2->execute();
            $stmt2->close();
            
            $conexao->commit();

            echo json_encode([
                'success' => true, 
                'message' => 'Dados resetados com sucesso',
                'banca' => '0.00',
                'lucro' => '0.00',
                'diaria' => '1.00',
                'unidade' => 1,
                'odds' => '1.50',
                'meta' => 'Meta Fixa',
                'diaria_formatada' => '1,00%',
                'unidade_entrada_formatada' => 'R$ 0,00',
                'meta_diaria_formatada' => 'R$ 0,00',
                'meta_mensal_formatada' => 'R$ 0,00',
                'meta_anual_formatada' => 'R$ 0,00'
            ]);
            
        } catch (Exception $e) {
            $conexao->rollback();
            echo json_encode(['success' => false, 'message' => 'Erro ao resetar dados']);
        }
        exit();
    }

    // ALTERAR
    if ($acao === 'alterar') {
        try {
            $stmt_check = $conexao->prepare("SELECT id FROM controle WHERE id_usuario = ? ORDER BY id DESC LIMIT 1");
            $stmt_check->bind_param("i", $id_usuario);
            $stmt_check->execute();
            $result = $stmt_check->get_result();
            
            if ($result->num_rows > 0) {
                $stmt = $conexao->prepare("
                    UPDATE controle 
                    SET meta = ?, diaria = ?, unidade = ?, odds = ?, data_registro = NOW()
                    WHERE id_usuario = ? 
                    ORDER BY id DESC LIMIT 1
                ");
                $stmt->bind_param("sdidi", $tipoMeta, $diaria, $unidade, $odds, $id_usuario);
            } else {
                $stmt = $conexao->prepare("
                    INSERT INTO controle (id_usuario, meta, diaria, unidade, odds, data_registro) 
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmt->bind_param("isdid", $id_usuario, $tipoMeta, $diaria, $unidade, $odds);
            }

            if ($stmt->execute()) {
                $total_deposito = getSoma($conexao, 'deposito', $id_usuario);
                $total_saque = getSoma($conexao, 'saque', $id_usuario);
                $dados_lucro = calcularLucro($conexao, $id_usuario);
                $lucro = $dados_lucro['lucro'];
                $saldo_banca_total = $total_deposito - $total_saque + $lucro;
                
                $tipo_meta_detectado = detectarTipoMeta($conexao, $id_usuario);
                $meta_resultado = calcularMetaDiariaComTipo($conexao, $id_usuario, $total_deposito, $total_saque, $lucro, $tipo_meta_detectado);
                $metas_periodo = calcularMetasPorPeriodo($meta_resultado['meta_diaria'], $conexao, $id_usuario);
                $area_direita = calcularAreaDireita($conexao, $id_usuario, $saldo_banca_total);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Dados alterados com sucesso',
                    'banca' => number_format($saldo_banca_total, 2, '.', ''),
                    'lucro' => number_format($lucro, 2, '.', ''),
                    'diaria' => number_format($diaria, 2, '.', ''),
                    'unidade' => $unidade,
                    'odds' => number_format($odds, 2, '.', ''),
                    'meta' => $tipoMeta,
                    'tipo_meta' => $tipo_meta_detectado,
                    'meta_diaria' => $meta_resultado['meta_diaria'],
                    'meta_mensal' => $metas_periodo['meta_mensal'],
                    'meta_anual' => $metas_periodo['meta_anual'],
                    'meta_diaria_formatada' => $metas_periodo['meta_diaria_formatada'],
                    'meta_mensal_formatada' => $metas_periodo['meta_mensal_formatada'],
                    'meta_anual_formatada' => $metas_periodo['meta_anual_formatada'],
                    'dias_restantes_mes' => $metas_periodo['dias_restantes_mes'],
                    'dias_restantes_ano' => $metas_periodo['dias_restantes_ano'],
                    'lucro_mes_atual' => $metas_periodo['lucro_mes_atual'],
                    'lucro_ano_atual' => $metas_periodo['lucro_ano_atual']
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao alterar dados']);
            }
            $stmt->close();
            $stmt_check->close();
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro no banco de dados']);
        }
        exit();
    }

    // DEPÓSITO/SAQUE
    if ($valor <= 0 || !in_array($acao, ['deposito', 'saque', 'cadastrar'])) {
        echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
        exit();
    }

    try {
        $query = "";
        if ($acao === 'deposito' || $acao === 'cadastrar') {
            $query = "INSERT INTO controle (id_usuario, deposito, diaria, unidade, odds, meta, data_registro) VALUES (?, ?, ?, ?, ?, ?, NOW())";
        } elseif ($acao === 'saque') {
            $query = "INSERT INTO controle (id_usuario, saque, diaria, unidade, odds, meta, data_registro) VALUES (?, ?, ?, ?, ?, ?, NOW())";
        }

        $stmt = $conexao->prepare($query);
        $stmt->bind_param("iddids", $id_usuario, $valor, $diaria, $unidade, $odds, $tipoMeta);

        if ($stmt->execute()) {
            $total_deposito = getSoma($conexao, 'deposito', $id_usuario);
            $total_saque = getSoma($conexao, 'saque', $id_usuario);
            $dados_lucro = calcularLucro($conexao, $id_usuario);
            $lucro = $dados_lucro['lucro'];
            $saldo_banca_total = $total_deposito - $total_saque + $lucro;
            
            $tipo_meta_detectado = detectarTipoMeta($conexao, $id_usuario);
            $meta_resultado = calcularMetaDiariaComTipo($conexao, $id_usuario, $total_deposito, $total_saque, $lucro, $tipo_meta_detectado);
            $metas_periodo = calcularMetasPorPeriodo($meta_resultado['meta_diaria'], $conexao, $id_usuario);
            $area_direita = calcularAreaDireita($conexao, $id_usuario, $saldo_banca_total);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Operação realizada com sucesso',
                'banca' => number_format($saldo_banca_total, 2, '.', ''),
                'lucro' => number_format($lucro, 2, '.', ''),
                'diaria' => number_format($diaria, 2, '.', ''),
                'tipo_meta' => $tipo_meta_detectado,
                'meta_diaria' => $meta_resultado['meta_diaria'],
                'meta_mensal' => $metas_periodo['meta_mensal'],
                'meta_anual' => $metas_periodo['meta_anual'],
                'meta_diaria_formatada' => $metas_periodo['meta_diaria_formatada'],
                'meta_mensal_formatada' => $metas_periodo['meta_mensal_formatada'],
                'meta_anual_formatada' => $metas_periodo['meta_anual_formatada'],
                'dias_restantes_mes' => $metas_periodo['dias_restantes_mes'],
                'dias_restantes_ano' => $metas_periodo['dias_restantes_ano'],
                'lucro_mes_atual' => $metas_periodo['lucro_mes_atual'],
                'lucro_ano_atual' => $metas_periodo['lucro_ano_atual'],
                'diaria_formatada' => $area_direita['diaria_formatada'],
                'unidade_entrada_formatada' => $area_direita['unidade_entrada_formatada']
            ]);
            
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao salvar no banco']);
        }
        $stmt->close();
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro no banco: ' . $e->getMessage()]);
    }
    exit();
}

// ✅ REQUISIÇÃO GET
try {
    $total_deposito = getSoma($conexao, 'deposito', $id_usuario);
    $total_saque = getSoma($conexao, 'saque', $id_usuario);
    $dados_lucro = calcularLucro($conexao, $id_usuario);
    $lucro = $dados_lucro['lucro'];
    $saldo_banca_total = $total_deposito - $total_saque + $lucro;
    
    $ultima_diaria = getUltimoCampo($conexao, 'diaria', $id_usuario) ?? 1.00;
    $ultima_unidade = getUltimoCampo($conexao, 'unidade', $id_usuario) ?? 1;
    $ultima_odds = getUltimoCampo($conexao, 'odds', $id_usuario) ?? 1.5;
    $ultima_meta = getUltimaMeta($conexao, $id_usuario);

    $tipo_meta_detectado = detectarTipoMeta($conexao, $id_usuario);
    $meta_resultado = calcularMetaDiariaComTipo($conexao, $id_usuario, $total_deposito, $total_saque, $lucro, $tipo_meta_detectado);
    $metas_periodo = calcularMetasPorPeriodo($meta_resultado['meta_diaria'], $conexao, $id_usuario);
    $area_direita = calcularAreaDireita($conexao, $id_usuario, $saldo_banca_total);

    echo json_encode([
        'success' => true,
        'banca' => number_format($saldo_banca_total, 2, '.', ''),
        'lucro' => number_format($lucro, 2, '.', ''),
        'diaria' => number_format($ultima_diaria, 2, '.', ''),
        'unidade' => intval($ultima_unidade),
        'odds' => number_format($ultima_odds, 2, '.', ''),
        'meta' => $ultima_meta,
        'tipo_meta' => $tipo_meta_detectado,
        
        // ✅ METAS CORRIGIDAS (usando lucro do mês e ano ATUAL)
        'meta_diaria' => $meta_resultado['meta_diaria'],
        'meta_mensal' => $metas_periodo['meta_mensal'],
        'meta_anual' => $metas_periodo['meta_anual'],
        'meta_mensal_base' => $metas_periodo['meta_mensal_base'],
        'meta_anual_base' => $metas_periodo['meta_anual_base'],
        'meta_diaria_formatada' => $metas_periodo['meta_diaria_formatada'],
        'meta_mensal_formatada' => $metas_periodo['meta_mensal_formatada'],
        'meta_anual_formatada' => $metas_periodo['meta_anual_formatada'],
        
        // ✅ LUCRO SEGMENTADO POR PERÍODO
        'lucro_mes_atual' => $metas_periodo['lucro_mes_atual'],
        'lucro_ano_atual' => $metas_periodo['lucro_ano_atual'],
        
        // ✅ DIAS RESTANTES
        'dias_restantes_mes' => $metas_periodo['dias_restantes_mes'],
        'dias_restantes_ano' => $metas_periodo['dias_restantes_ano'],
        
        // ✅ EXPLICAÇÕES
        'explicacao_mensal' => $metas_periodo['explicacao_mensal'],
        'explicacao_anual' => $metas_periodo['explicacao_anual'],
        'data_primeiro_valor_mentor' => $metas_periodo['data_primeiro_valor_mentor'],
        
        // ✅ ÁREA DIREITA
        'diaria_formatada' => $area_direita['diaria_formatada'],
        'unidade_entrada_formatada' => $area_direita['unidade_entrada_formatada'],
        'banca_formatada' => 'R$ ' . number_format($saldo_banca_total, 2, ',', '.'),
        'lucro_formatado' => 'R$ ' . number_format($lucro, 2, ',', '.'),
        'banca_inicio_dia' => $area_direita['banca_inicio_dia'],
        'lucro_ate_ontem' => $area_direita['lucro_ate_ontem'],
        
        // ✅ DEBUG
        'calculo_detalhado' => [
            'tipo_meta' => $tipo_meta_detectado,
            'banca_inicial' => $meta_resultado['banca_inicial'],
            'banca_atual' => $meta_resultado['banca_atual'],
            'banca_inicio_dia' => $meta_resultado['banca_inicio_dia'],
            'lucro_ate_ontem' => $meta_resultado['lucro_ate_ontem'],
            'lucro_mes_atual' => $metas_periodo['lucro_mes_atual'],
            'lucro_ano_atual' => $metas_periodo['lucro_ano_atual'],
            'base_calculo' => $meta_resultado['base_calculo'],
            'meta_diaria_calculada' => $meta_resultado['meta_diaria'],
            'meta_mensal_base' => $metas_periodo['meta_mensal_base'],
            'meta_mensal_ajustada' => $metas_periodo['meta_mensal'],
            'meta_anual_base' => $metas_periodo['meta_anual_base'],
            'meta_anual_ajustada' => $metas_periodo['meta_anual'],
            'dias_meta_mensal' => $metas_periodo['dias_restantes_mes'],
            'dias_meta_anual' => $metas_periodo['dias_restantes_ano']
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro: ' . $e->getMessage()
    ]);
}
?>