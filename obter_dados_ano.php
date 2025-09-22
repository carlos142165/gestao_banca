<?php
// obter_dados_ano.php - Retorna dados anuais agregados por mês
// Baseado no obter_dados_mes.php mas adaptado para dados anuais

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
    
    // Query principal: buscar todos os dados do ano agrupados por mês
    $sql = "
        SELECT 
            YEAR(vm.data_criacao) as ano,
            MONTH(vm.data_criacao) as mes,
            COALESCE(SUM(CASE WHEN vm.green = 1 THEN vm.valor_green ELSE 0 END), 0) as total_valor_green,
            COALESCE(SUM(CASE WHEN vm.red = 1 THEN vm.valor_red ELSE 0 END), 0) as total_valor_red,
            COALESCE(SUM(CASE WHEN vm.green = 1 THEN 1 ELSE 0 END), 0) as total_green,
            COALESCE(SUM(CASE WHEN vm.red = 1 THEN 1 ELSE 0 END), 0) as total_red
        FROM valor_mentores vm
        INNER JOIN mentores m ON vm.id_mentores = m.id
        WHERE m.id_usuario = ?
        AND YEAR(vm.data_criacao) = ?
        GROUP BY YEAR(vm.data_criacao), MONTH(vm.data_criacao)
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
    
    // Organizar dados por mês
    $dados_por_mes = [];
    $meses_com_meta_batida = [];
    
    while ($row = $result->fetch_assoc()) {
        $mes_key = $row['ano'] . '-' . str_pad($row['mes'], 2, '0', STR_PAD_LEFT); // YYYY-MM
        
        $total_valor_green = floatval($row['total_valor_green']);
        $total_valor_red = floatval($row['total_valor_red']);
        $saldo_mes = $total_valor_green - $total_valor_red;
        
        // Verificar se meta mensal foi batida
        $meta_batida = false;
        if ($meta_mensal_para_trofeu > 0) {
            $meta_batida = $saldo_mes >= $meta_mensal_para_trofeu;
        } else {
            // Critério restritivo se não há meta configurada
            $meta_batida = $saldo_mes >= 500;
        }
        
        $dados_por_mes[$mes_key] = [
            'total_valor_green' => $total_valor_green,
            'total_valor_red' => $total_valor_red,
            'total_green' => intval($row['total_green']),
            'total_red' => intval($row['total_red']),
            'saldo' => $saldo_mes,
            'meta_batida' => $meta_batida,
            'meta_mensal' => $meta_mensal_para_trofeu
        ];
        
        if ($meta_batida) {
            $meses_com_meta_batida[] = $mes_key;
        }
    }
    
    $stmt->close();
    
    // Calcular totais do ano
    $total_green_ano = 0;
    $total_red_ano = 0;
    $saldo_total_ano = 0;
    
    foreach ($dados_por_mes as $dados) {
        $total_green_ano += $dados['total_green'];
        $total_red_ano += $dados['total_red'];
        $saldo_total_ano += $dados['saldo'];
    }
    
    // Resposta
    $response = [
        'success' => true,
        'ano' => $ano,
        'data_atual' => date('Y-m-d'),
        'dados_por_mes' => $dados_por_mes,
        'totais_ano' => [
            'total_green' => $total_green_ano,
            'total_red' => $total_red_ano,
            'saldo_total' => $saldo_total_ano
        ],
        'configuracao_meta' => [
            'meta_diaria' => $meta_diaria,
            'meta_mensal' => $meta_mensal,
            'meta_anual' => $meta_anual,
            'meta_mensal_para_trofeu' => $meta_mensal_para_trofeu,
            'tipo_meta' => $tipo_meta,
            'tipo_meta_calculada' => $tipo_meta_calculada,
            'periodo_filtro' => $periodo_filtro
        ],
        'analise_meta' => [
            'total_meses_com_meta_batida' => count($meses_com_meta_batida),
            'meses_com_meta_batida' => $meses_com_meta_batida,
            'percentual_sucesso' => count($dados_por_mes) > 0 ? 
                round((count($meses_com_meta_batida) / count($dados_por_mes)) * 100, 2) : 0
        ],
        'timestamp' => time(),
        'debug_info' => [
            'total_meses_com_dados' => count($dados_por_mes),
            'chaves_exemplo' => array_slice(array_keys($dados_por_mes), 0, 5),
            'meta_mensal_calculada' => $meta_mensal_para_trofeu,
            'criterio_meta' => $tipo_meta_calculada
        ]
    ];
    
    // Log para debug
    error_log("obter_dados_ano.php - Dados encontrados: " . count($dados_por_mes) . 
              " meses para ano " . $ano . 
              " | Meta mensal: R$ " . number_format($meta_mensal_para_trofeu, 2) . 
              " | Meses com meta batida: " . count($meses_com_meta_batida));
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log('Erro em obter_dados_ano.php: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao buscar dados anuais',
        'message' => $e->getMessage(),
        'timestamp' => time()
    ]);
}

if (isset($conexao) && $conexao) {
    $conexao->close();
}
?>