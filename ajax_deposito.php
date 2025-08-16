<?php
require_once 'config.php';
require_once 'carregar_sessao.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sessão inválida']);
    exit();
}

$id_usuario = intval($_SESSION['usuario_id']);

// ✅ FUNÇÃO PARA CALCULAR DADOS DA ÁREA DIREITA - CORRIGIDA COM LUCRO
function calcularAreaDireita($conexao, $id_usuario, $saldo_banca_total) {
    try {
        // Buscar última diária cadastrada
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
        
        $diaria = $ultima_diaria ?? 2.00;
        
        // ✅ CORREÇÃO: Usar saldo total da banca (com lucro) para calcular UND
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

// ✅ FUNÇÃO AUXILIAR PARA BUSCAR SOMA
function getSoma($conexao, $campo, $id_usuario) {
    $stmt = $conexao->prepare("SELECT SUM($campo) FROM controle WHERE id_usuario = ? AND $campo > 0");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $stmt->bind_result($total);
    $stmt->fetch();
    $stmt->close();
    return $total ?? 0;
}

// ✅ FUNÇÃO PARA BUSCAR ÚLTIMO CAMPO
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

// ✅ FUNÇÃO PARA CALCULAR LUCRO
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

// ✅ PROCESSAR REQUISIÇÕES POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    $acao = $data['acao'] ?? '';
    $valor = abs(floatval($data['valor'] ?? 0));
    $diaria = floatval($data['diaria'] ?? 0);
    $unidade = intval($data['unidade'] ?? 0);
    $odds = isset($data['odds']) ? floatval(str_replace(',', '.', $data['odds'])) : 0;
    $nome = trim($data['nome'] ?? '');

    // ✅ OPERAÇÃO DE RESET
    if ($acao === 'resetar') {
        $stmt1 = $conexao->prepare("DELETE FROM controle WHERE id_usuario = ?");
        $stmt1->bind_param("i", $id_usuario);
        $stmt1->execute();
        $stmt1->close();

        $stmt2 = $conexao->prepare("DELETE FROM valor_mentores WHERE id_usuario = ?");
        $stmt2->bind_param("i", $id_usuario);
        $stmt2->execute();
        $stmt2->close();

        // ✅ RETORNAR DADOS ZERADOS PARA ÁREA DIREITA
        echo json_encode([
            'success' => true, 
            'message' => 'Dados resetados com sucesso',
            'banca' => '0.00',
            'lucro' => '0.00',
            'diaria' => '2.00',
            'unidade' => 2,
            'odds' => '1.50',
            
            // ✅ ÁREA DIREITA ZERADA
            'diaria_formatada' => '2%',
            'unidade_entrada_formatada' => 'R$ 0,00',
            'meta_diaria_formatada' => 'R$ 0,00',
            'diaria_raw' => 2,
            'saldo_banca_total' => 0,
            'unidade_entrada_raw' => 0
        ]);
        exit();
    }

    // ✅ OPERAÇÃO DE ALTERAÇÃO
    if ($acao === 'alterar') {
        $stmt = $conexao->prepare("
            UPDATE controle SET diaria = ?, unidade = ?, odds = ?
            WHERE id_usuario = ? ORDER BY id DESC LIMIT 1
        ");
        $stmt->bind_param("didi", $diaria, $unidade, $odds, $id_usuario);

        if ($stmt->execute()) {
            // ✅ BUSCAR DADOS ATUALIZADOS APÓS ALTERAÇÃO
            $total_deposito = getSoma($conexao, 'deposito', $id_usuario);
            $total_saque = getSoma($conexao, 'saque', $id_usuario);
            
            // ✅ CALCULAR LUCRO
            $dados_lucro = calcularLucro($conexao, $id_usuario);
            $lucro = $dados_lucro['lucro'];
            
            // ✅ SALDO TOTAL DA BANCA (DEPÓSITO - SAQUE + LUCRO)
            $saldo_banca_total = $total_deposito - $total_saque + $lucro;
            
            // ✅ CALCULAR ÁREA DIREITA COM SALDO TOTAL
            $area_direita = calcularAreaDireita($conexao, $id_usuario, $saldo_banca_total);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Dados alterados com sucesso',
                'banca' => number_format($saldo_banca_total, 2, '.', ''),
                'lucro' => number_format($lucro, 2, '.', ''),
                'diaria' => number_format($diaria, 2, '.', ''),
                'unidade' => $unidade,
                'odds' => number_format($odds, 2, '.', ''),
                
                // ✅ ÁREA DIREITA ATUALIZADA COM SALDO TOTAL
                'diaria_formatada' => $area_direita['diaria_formatada'],
                'unidade_entrada_formatada' => $area_direita['unidade_entrada_formatada'],
                'meta_diaria_formatada' => 'R$ ' . number_format($area_direita['unidade_entrada'] * $unidade, 2, ',', '.'),
                'diaria_raw' => $area_direita['diaria_porcentagem'],
                'saldo_banca_total' => $area_direita['saldo_banca_total'],
                'unidade_entrada_raw' => $area_direita['unidade_entrada']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao alterar dados']);
        }
        $stmt->close();
        exit();
    }

    // ✅ VALIDAÇÃO PARA DEPÓSITO E SAQUE
    if ($valor <= 0 || !in_array($acao, ['deposito', 'saque', 'cadastrar'])) {
        echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
        exit();
    }

    // ✅ PREPARAR QUERY PARA DEPÓSITO OU SAQUE
    $query = "";
    if ($acao === 'deposito' || $acao === 'cadastrar') {
        $query = "INSERT INTO controle (id_usuario, deposito, diaria, unidade, odds, data_registro) VALUES (?, ?, ?, ?, ?, NOW())";
    } elseif ($acao === 'saque') {
        $query = "INSERT INTO controle (id_usuario, saque, diaria, unidade, odds, data_registro) VALUES (?, ?, ?, ?, ?, NOW())";
    }

    $stmt = $conexao->prepare($query);
    $stmt->bind_param("iddid", $id_usuario, $valor, $diaria, $unidade, $odds);

    if ($stmt->execute()) {
        // ✅ BUSCAR DADOS ATUALIZADOS APÓS OPERAÇÃO
        $total_deposito = getSoma($conexao, 'deposito', $id_usuario);
        $total_saque = getSoma($conexao, 'saque', $id_usuario);
        
        // ✅ CALCULAR LUCRO
        $dados_lucro = calcularLucro($conexao, $id_usuario);
        $lucro = $dados_lucro['lucro'];
        
        // ✅ SALDO TOTAL DA BANCA (DEPÓSITO - SAQUE + LUCRO)
        $saldo_banca_total = $total_deposito - $total_saque + $lucro;
        
        // ✅ CALCULAR ÁREA DIREITA COM SALDO TOTAL
        $area_direita = calcularAreaDireita($conexao, $id_usuario, $saldo_banca_total);
        
        // ✅ CALCULAR META DIÁRIA
        $meta_diaria = $area_direita['unidade_entrada'] * $unidade;
        
        echo json_encode([
            'success' => true, 
            'message' => 'Operação realizada com sucesso',
            'deposito' => number_format($total_deposito, 2, '.', ''),
            'saque' => number_format($total_saque, 2, '.', ''),
            'banca' => number_format($saldo_banca_total, 2, '.', ''),
            'lucro' => number_format($lucro, 2, '.', ''),
            'diaria' => number_format($diaria, 2, '.', ''),
            'unidade' => $unidade,
            'odds' => number_format($odds, 2, '.', ''),
            
            // ✅ ÁREA DIREITA ATUALIZADA COM SALDO TOTAL
            'diaria_formatada' => $area_direita['diaria_formatada'],
            'unidade_entrada_formatada' => $area_direita['unidade_entrada_formatada'],
            'meta_diaria_formatada' => 'R$ ' . number_format($meta_diaria, 2, ',', '.'),
            'diaria_raw' => $area_direita['diaria_porcentagem'],
            'saldo_banca_total' => $area_direita['saldo_banca_total'],
            'unidade_entrada_raw' => $area_direita['unidade_entrada'],
            'meta_diaria_raw' => $meta_diaria
        ]);
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar no banco']);
    }
    $stmt->close();
    exit();
}

// ✅ REQUISIÇÃO GET: RETORNA DADOS DA BANCA COM ÁREA DIREITA
$total_deposito = getSoma($conexao, 'deposito', $id_usuario);
$total_saque = getSoma($conexao, 'saque', $id_usuario);

// ✅ CALCULAR LUCRO
$dados_lucro = calcularLucro($conexao, $id_usuario);
$lucro = $dados_lucro['lucro'];

// ✅ SALDO TOTAL DA BANCA (DEPÓSITO - SAQUE + LUCRO)
$saldo_banca_total = $total_deposito - $total_saque + $lucro;
$mostrar_radios = $total_deposito > 0;

// ✅ BUSCAR ÚLTIMOS VALORES
$ultima_diaria = getUltimoCampo($conexao, 'diaria', $id_usuario);
$ultima_unidade = getUltimoCampo($conexao, 'unidade', $id_usuario);
$ultima_odds = getUltimoCampo($conexao, 'odds', $id_usuario);

// ✅ odds padrão se for 0 ou nula
$odds_final = ($ultima_odds && floatval($ultima_odds) > 0) ? $ultima_odds : 1.5;

// ✅ CALCULAR ÁREA DIREITA COM SALDO TOTAL
$area_direita = calcularAreaDireita($conexao, $id_usuario, $saldo_banca_total);

// ✅ CALCULAR META DIÁRIA PARA GET
$meta_diaria = $area_direita['unidade_entrada'] * ($ultima_unidade ?? 2);

echo json_encode([
    'success' => true,
    'deposito' => number_format($total_deposito, 2, '.', ''),
    'saque' => number_format($total_saque, 2, '.', ''),
    'banca' => number_format($saldo_banca_total, 2, '.', ''),
    'lucro' => number_format($lucro, 2, '.', ''),
    'mostrar_radios' => $mostrar_radios,
    'diaria' => number_format($ultima_diaria ?? 0, 2, '.', ''),
    'unidade' => intval($ultima_unidade ?? 0),
    'odds' => number_format($odds_final, 2, '.', ''),
    
    // ✅ DADOS PARA ÁREA DIREITA COM SALDO TOTAL
    'diaria_formatada' => $area_direita['diaria_formatada'],
    'unidade_entrada_formatada' => $area_direita['unidade_entrada_formatada'],
    'meta_diaria_formatada' => 'R$ ' . number_format($meta_diaria, 2, ',', '.'),
    'diaria_raw' => $area_direita['diaria_porcentagem'],
    'saldo_banca_total' => $area_direita['saldo_banca_total'],
    'unidade_entrada_raw' => $area_direita['unidade_entrada'],
    'meta_diaria_raw' => $meta_diaria,
    
    // ✅ DEBUG ÁREA DIREITA COM FÓRMULA CORRIGIDA
    'area_direita_debug' => [
        'formula' => "Saldo Total: R$ " . number_format($saldo_banca_total, 2, ',', '.') . " × {$area_direita['diaria_porcentagem']}% = {$area_direita['unidade_entrada_formatada']}",
        'deposito_total' => $total_deposito,
        'saque_total' => $total_saque,
        'lucro_total' => $lucro,
        'saldo_banca_total' => $saldo_banca_total,
        'diaria_aplicada' => $area_direita['diaria_porcentagem'],
        'unidade_resultado' => $area_direita['unidade_entrada']
    ]
]);
?>