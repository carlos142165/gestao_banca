<?php
// obter_dados_mes.php - Retorna dados do mês para atualização em tempo real

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
    // Obter mês e ano (permite parâmetros opcionais)
    $mes = $_GET['mes'] ?? date('m');
    $ano = $_GET['ano'] ?? date('Y');
    
    // Validar mês e ano
    $mes = str_pad(intval($mes), 2, '0', STR_PAD_LEFT);
    $ano = intval($ano);
    
    if ($mes < 1 || $mes > 12 || $ano < 2020 || $ano > 2030) {
        throw new Exception('Mês ou ano inválido');
    }
    
    // Query para buscar dados agrupados por dia
    $sql = "
        SELECT 
            DATE(vm.data_criacao) as data,
            COALESCE(SUM(CASE WHEN vm.green = 1 THEN vm.valor_green ELSE 0 END), 0) as total_valor_green,
            COALESCE(SUM(CASE WHEN vm.red = 1 THEN vm.valor_red ELSE 0 END), 0) as total_valor_red,
            COALESCE(SUM(CASE WHEN vm.green = 1 THEN 1 ELSE 0 END), 0) as total_green,
            COALESCE(SUM(CASE WHEN vm.red = 1 THEN 1 ELSE 0 END), 0) as total_red
        FROM valor_mentores vm
        INNER JOIN mentores m ON vm.id_mentores = m.id
        WHERE m.id_usuario = ?
        AND MONTH(vm.data_criacao) = ?
        AND YEAR(vm.data_criacao) = ?
        GROUP BY DATE(vm.data_criacao)
        ORDER BY data ASC
    ";
    
    // Preparar e executar query
    $stmt = $conexao->prepare($sql);
    if (!$stmt) {
        throw new Exception('Erro ao preparar consulta: ' . $conexao->error);
    }
    
    $stmt->bind_param("iii", $id_usuario, $mes, $ano);
    
    if (!$stmt->execute()) {
        throw new Exception('Erro ao executar consulta: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    // Organizar dados por dia
    $dados_por_dia = [];
    
    while ($row = $result->fetch_assoc()) {
        // Garantir que a data está no formato correto (YYYY-MM-DD)
        $data_formatada = $row['data'];
        
        $dados_por_dia[$data_formatada] = [
            'total_valor_green' => floatval($row['total_valor_green']),
            'total_valor_red' => floatval($row['total_valor_red']),
            'total_green' => intval($row['total_green']),
            'total_red' => intval($row['total_red']),
            'saldo' => floatval($row['total_valor_green']) - floatval($row['total_valor_red'])
        ];
    }
    
    $stmt->close();
    
    // Adicionar informações extras úteis
    $response = [
        'success' => true,
        'mes' => $mes,
        'ano' => $ano,
        'data_atual' => date('Y-m-d'),
        'dias_no_mes' => cal_days_in_month(CAL_GREGORIAN, intval($mes), $ano),
        'dados' => $dados_por_dia,
        'timestamp' => time()
    ];
    
    // Calcular totais do mês
    $total_green_mes = 0;
    $total_red_mes = 0;
    $saldo_total_mes = 0;
    
    foreach ($dados_por_dia as $dados) {
        $total_green_mes += $dados['total_green'];
        $total_red_mes += $dados['total_red'];
        $saldo_total_mes += $dados['saldo'];
    }
    
    $response['totais_mes'] = [
        'total_green' => $total_green_mes,
        'total_red' => $total_red_mes,
        'saldo_total' => $saldo_total_mes
    ];
    
    // Retornar JSON
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // Log do erro
    error_log('Erro em obter_dados_mes.php: ' . $e->getMessage());
    
    // Retornar erro em JSON
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao buscar dados',
        'message' => $e->getMessage(),
        'timestamp' => time()
    ]);
}

// Fechar conexão se ainda estiver aberta
if (isset($conexao) && $conexao) {
    $conexao->close();
}
?>