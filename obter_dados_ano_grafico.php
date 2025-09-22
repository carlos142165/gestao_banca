<?php
// obter_dados_ano_grafico.php - Retorna dados anuais separados para gráfico
// Versão otimizada para retornar valores verde e vermelho separadamente

require_once 'config.php';
require_once 'carregar_sessao.php';

// Configurar headers para JSON e sem cache
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Verificar autenticação
$id_usuario = $_SESSION['usuario_id'] ?? null;
if (!$id_usuario) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit;
}

try {
    // Obter parâmetros (padrão: ano atual)
    $ano = $_GET['ano'] ?? date('Y');
    
    // Validar ano
    $ano = intval($ano);
    if ($ano < 2020 || $ano > 2030) {
        throw new Exception('Ano inválido');
    }
    
    // Buscar informações de meta da sessão
    $meta_diaria = isset($_SESSION['meta_diaria']) ? floatval($_SESSION['meta_diaria']) : 0;
    $meta_mensal = isset($_SESSION['meta_mensal']) ? floatval($_SESSION['meta_mensal']) : 0;
    $meta_anual = isset($_SESSION['meta_anual']) ? floatval($_SESSION['meta_anual']) : 0;
    $periodo_filtro = $_SESSION['periodo_filtro'] ?? 'ano';
    $tipo_meta = $_SESSION['tipo_meta'] ?? 'turbo';
    
    // Calcular meta mensal correta para troféus
    $meta_mensal_para_trofeu = 0;
    $tipo_meta_calculada = "";
    
    if ($meta_mensal > 0) {
        $meta_mensal_para_trofeu = $meta_mensal;
        $tipo_meta_calculada = "mensal_especifica";
    } elseif ($meta_anual > 0) {
        $meta_mensal_para_trofeu = $meta_anual / 12;
        $tipo_meta_calculada = "anual_dividida";
    } elseif ($meta_diaria > 0) {
        $meta_mensal_para_trofeu = $meta_diaria * 30;
        $tipo_meta_calculada = "diaria_multiplicada";
    } else {
        $meta_mensal_para_trofeu = 0;
        $tipo_meta_calculada = "nenhuma";
    }
    
    // Query otimizada: buscar dados separados por GREEN e RED
    $sql = "
        SELECT 
            YEAR(vm.data_criacao) as ano,
            MONTH(vm.data_criacao) as mes,
            MONTHNAME(vm.data_criacao) as nome_mes,
            
            -- DADOS VERDES (POSITIVOS)
            COALESCE(SUM(CASE WHEN vm.green = 1 THEN vm.valor_green ELSE 0 END), 0) as valor_verde_total,
            COALESCE(COUNT(CASE WHEN vm.green = 1 THEN 1 END), 0) as quantidade_verde,
            
            -- DADOS VERMELHOS (NEGATIVOS) 
            COALESCE(SUM(CASE WHEN vm.red = 1 THEN vm.valor_red ELSE 0 END), 0) as valor_vermelho_total,
            COALESCE(COUNT(CASE WHEN vm.red = 1 THEN 1 END), 0) as quantidade_vermelho,
            
            -- SALDO MENSAL
            COALESCE(SUM(CASE WHEN vm.green = 1 THEN vm.valor_green ELSE 0 END), 0) - 
            COALESCE(SUM(CASE WHEN vm.red = 1 THEN vm.valor_red ELSE 0 END), 0) as saldo_mensal,
            
            -- MAIOR VALOR GREEN E RED DO MÊS (para escala do gráfico)
            COALESCE(MAX(CASE WHEN vm.green = 1 THEN vm.valor_green END), 0) as maior_valor_verde,
            COALESCE(MAX(CASE WHEN vm.red = 1 THEN vm.valor_red END), 0) as maior_valor_vermelho,
            
            -- PRIMEIRO E ÚLTIMO DIA COM MOVIMENTAÇÃO
            MIN(DATE(vm.data_criacao)) as primeiro_dia,
            MAX(DATE(vm.data_criacao)) as ultimo_dia,
            
            -- TOTAL DE DIAS COM MOVIMENTAÇÃO
            COUNT(DISTINCT DATE(vm.data_criacao)) as dias_com_movimento
            
        FROM valor_mentores vm
        INNER JOIN mentores m ON vm.id_mentores = m.id
        WHERE m.id_usuario = ?
        AND YEAR(vm.data_criacao) = ?
        GROUP BY YEAR(vm.data_criacao), MONTH(vm.data_criacao), MONTHNAME(vm.data_criacao)
        ORDER BY mes ASC
    ";
    
    $stmt = $conexao->prepare($sql);
    if (!$stmt) {
        throw new Exception('Erro ao preparar consulta: ' . $conexao->error);
    }
    
    $stmt->bind_param("ii", $id_usuario, $ano);
    
    if (!$stmt->execute()) {
        throw new Exception('Erro ao executar consulta: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    // Organizar dados por mês (1-12)
    $dados_grafico = [];
    $valor_maximo_global = 0;
    $meses_com_meta_batida = [];
    
    // Inicializar todos os 12 meses
    $nomes_meses = [
        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
        5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto', 
        9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
    ];
    
    for ($i = 1; $i <= 12; $i++) {
        $dados_grafico[$i] = [
            'mes_numero' => $i,
            'mes_nome' => $nomes_meses[$i],
            'mes_abrev' => substr($nomes_meses[$i], 0, 3),
            'valor_verde' => 0,
            'valor_vermelho' => 0,
            'quantidade_verde' => 0,
            'quantidade_vermelho' => 0,
            'saldo' => 0,
            'tem_dados' => false,
            'meta_batida' => false,
            'dias_com_movimento' => 0,
            'primeiro_dia' => null,
            'ultimo_dia' => null
        ];
    }
    
    // Preencher com dados encontrados
    while ($row = $result->fetch_assoc()) {
        $mes_num = intval($row['mes']);
        
        $valor_verde = floatval($row['valor_verde_total']);
        $valor_vermelho = floatval($row['valor_vermelho_total']);
        $saldo = floatval($row['saldo_mensal']);
        
        // Verificar se meta mensal foi batida
        $meta_batida = false;
        if ($meta_mensal_para_trofeu > 0) {
            $meta_batida = $saldo >= $meta_mensal_para_trofeu;
        } else {
            // Critério conservador se não há meta
            $meta_batida = $saldo >= 500;
        }
        
        $dados_grafico[$mes_num] = [
            'mes_numero' => $mes_num,
            'mes_nome' => $nomes_meses[$mes_num],
            'mes_abrev' => substr($nomes_meses[$mes_num], 0, 3),
            'valor_verde' => $valor_verde,
            'valor_vermelho' => $valor_vermelho,
            'quantidade_verde' => intval($row['quantidade_verde']),
            'quantidade_vermelho' => intval($row['quantidade_vermelho']),
            'saldo' => $saldo,
            'tem_dados' => ($valor_verde > 0 || $valor_vermelho > 0),
            'meta_batida' => $meta_batida,
            'dias_com_movimento' => intval($row['dias_com_movimento']),
            'primeiro_dia' => $row['primeiro_dia'],
            'ultimo_dia' => $row['ultimo_dia'],
            'maior_valor_verde' => floatval($row['maior_valor_verde']),
            'maior_valor_vermelho' => floatval($row['maior_valor_vermelho'])
        ];
        
        // Calcular valor máximo global para escala
        $valor_maximo_global = max($valor_maximo_global, $valor_verde, $valor_vermelho);
        
        if ($meta_batida) {
            $meses_com_meta_batida[] = $mes_num;
        }
    }
    
    $stmt->close();
    
    // Calcular totais do ano
    $total_verde_ano = array_sum(array_column($dados_grafico, 'valor_verde'));
    $total_vermelho_ano = array_sum(array_column($dados_grafico, 'valor_vermelho'));
    $saldo_total_ano = $total_verde_ano - $total_vermelho_ano;
    $total_quantidade_verde = array_sum(array_column($dados_grafico, 'quantidade_verde'));
    $total_quantidade_vermelho = array_sum(array_column($dados_grafico, 'quantidade_vermelho'));
    
    // Contar meses com dados
    $meses_com_dados = count(array_filter($dados_grafico, function($mes) {
        return $mes['tem_dados'];
    }));
    
    // Resposta otimizada para o gráfico
    $response = [
        'success' => true,
        'ano' => $ano,
        'data_atual' => date('Y-m-d'),
        'mes_atual' => intval(date('n')), // Mês atual (1-12)
        
        // DADOS PRINCIPAIS PARA O GRÁFICO
        'dados_meses' => array_values($dados_grafico), // Reindexar como array
        
        // CONFIGURAÇÃO PARA ESCALA DO GRÁFICO
        'escala_grafico' => [
            'valor_maximo' => $valor_maximo_global,
            'valor_minimo' => 0,
            'sugestao_divisoes' => 5,
            'incremento_sugerido' => ceil($valor_maximo_global / 5)
        ],
        
        // TOTAIS AGREGADOS
        'totais_ano' => [
            'valor_verde_total' => $total_verde_ano,
            'valor_vermelho_total' => $total_vermelho_ano,
            'saldo_total' => $saldo_total_ano,
            'quantidade_verde_total' => $total_quantidade_verde,
            'quantidade_vermelho_total' => $total_quantidade_vermelho,
            'meses_com_dados' => $meses_com_dados
        ],
        
        // ANÁLISE DE METAS
        'analise_meta' => [
            'meta_mensal_configurada' => $meta_mensal_para_trofeu,
            'tipo_meta' => $tipo_meta_calculada,
            'meses_com_meta_batida' => $meses_com_meta_batida,
            'total_meses_meta_batida' => count($meses_com_meta_batida),
            'percentual_sucesso' => $meses_com_dados > 0 ? 
                round((count($meses_com_meta_batida) / $meses_com_dados) * 100, 2) : 0
        ],
        
        'timestamp' => time(),
        'debug_info' => [
            'usuario_id' => $id_usuario,
            'meses_processados' => $meses_com_dados,
            'valor_maximo_encontrado' => $valor_maximo_global,
            'consulta_executada' => true
        ]
    ];
    
    // Log para debug
    error_log("obter_dados_ano_grafico.php - Processados: " . $meses_com_dados . 
              " meses | Valor máximo: R$ " . number_format($valor_maximo_global, 2) . 
              " | Metas batidas: " . count($meses_com_meta_batida));
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log('Erro em obter_dados_ano_grafico.php: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao buscar dados para gráfico',
        'message' => $e->getMessage(),
        'timestamp' => time()
    ]);
}

if (isset($conexao) && $conexao) {
    $conexao->close();
}
?>