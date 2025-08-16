<?php
// ✅ ARQUIVO DADOS_BANCA.PHP - COMPLETO COM META INCLUINDO LUCRO

require_once 'config.php';
require_once 'carregar_sessao.php';

$id_usuario = $_SESSION['usuario_id'] ?? null;
if (!$id_usuario) {
    echo json_encode(['success' => false, 'message' => 'Usuário não logado']);
    exit;
}

// Suas funções existentes
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

// ✅ FUNÇÃO PARA CALCULAR LUCRO
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

// ✅ FUNÇÃO PARA CALCULAR META DIÁRIA - CORRIGIDA COM LUCRO
function calcularMetaDiaria($conexao, $id_usuario, $total_deposito, $total_saque, $lucro) {
    try {
        // ✅ CORREÇÃO: Meta baseada na banca TOTAL (depósito - saque + lucro)
        $saldo_banca_para_meta = $total_deposito - $total_saque + $lucro;
        
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
        
        // ✅ CÁLCULO CORRETO: (deposito - saque + lucro) * (diaria/100) * unidade
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

// ✅ NOVA FUNÇÃO PARA CALCULAR DADOS DA ÁREA DIREITA - COM SALDO TOTAL
function calcularAreaDireita($conexao, $id_usuario, $saldo_banca_total) {
    try {
        // Buscar última diária cadastrada
        $ultima_diaria = getUltimoCampo($conexao, 'diaria', $id_usuario);
        $diaria = $ultima_diaria ?? 2.00;
        
        // ✅ USAR SALDO TOTAL DA BANCA (com lucro) para calcular UND
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

// Processar requisições POST (cadastros)
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
        
        // ✅ CALCULAR LUCRO
        $dados_lucro = calcularLucro($conexao, $id_usuario);
        $lucro = $dados_lucro['lucro'];
        
        // ✅ SALDO TOTAL DA BANCA (DEPÓSITO - SAQUE + LUCRO)
        $saldo_banca_total = $total_deposito - $total_saque + $lucro;
        
        // ✅ CALCULAR META BASEADA EM (DEPÓSITO - SAQUE + LUCRO) - CORRIGIDO!
        $meta_resultado = calcularMetaDiaria($conexao, $id_usuario, $total_deposito, $total_saque, $lucro);
        
        // ✅ CALCULAR DADOS PARA ÁREA DIREITA COM SALDO TOTAL
        $area_direita = calcularAreaDireita($conexao, $id_usuario, $saldo_banca_total);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Operação realizada com sucesso',
            'banca' => $saldo_banca_total,
            'banca_formatada' => 'R$ ' . number_format($saldo_banca_total, 2, ',', '.'),
            'lucro' => $lucro,
            'lucro_formatado' => 'R$ ' . number_format($lucro, 2, ',', '.'),
            'meta_diaria' => $meta_resultado['meta_diaria'],
            'meta_diaria_formatada' => 'R$ ' . number_format($meta_resultado['meta_diaria'], 2, ',', '.'),
            'meta_diaria_brl' => 'R$ ' . number_format($meta_resultado['meta_diaria'], 2, ',', '.'),
            'diaria_atual' => $meta_resultado['diaria_usada'],
            'unidade_atual' => $meta_resultado['unidade_usada'],
            'saldo_base_meta' => $meta_resultado['saldo_banca_meta'],
            
            // ✅ DADOS PARA ÁREA DIREITA COM SALDO TOTAL
            'diaria_formatada' => $area_direita['diaria_formatada'],
            'unidade_entrada_formatada' => $area_direita['unidade_entrada_formatada'],
            'diaria_raw' => $area_direita['diaria_porcentagem'],
            'saldo_banca_total' => $area_direita['saldo_banca_total'],
            'unidade_entrada_raw' => $area_direita['unidade_entrada']
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ✅ PROCESSAR REQUISIÇÕES GET (CONSULTAS) - CORRIGIDO COM LUCRO NA META
try {
    $total_deposito = getSoma($conexao, 'deposito', $id_usuario);
    $total_saque = getSoma($conexao, 'saque', $id_usuario);
    
    // ✅ CALCULAR LUCRO
    $dados_lucro = calcularLucro($conexao, $id_usuario);
    $total_green = $dados_lucro['green'];
    $total_red = $dados_lucro['red'];
    $lucro = $dados_lucro['lucro'];
    
    // ✅ SALDO TOTAL DA BANCA (DEPÓSITO - SAQUE + LUCRO)
    $saldo_banca_total = $total_deposito - $total_saque + $lucro;
    
    // Buscar últimos valores de configuração
    $ultima_diaria = getUltimoCampo($conexao, 'diaria', $id_usuario);
    $ultima_unidade = getUltimoCampo($conexao, 'unidade', $id_usuario);
    $ultima_odds = getUltimoCampo($conexao, 'odds', $id_usuario);
    
    // ✅ CALCULAR META BASEADA EM (DEPÓSITO - SAQUE + LUCRO) - CORRIGIDO!
    $meta_resultado = calcularMetaDiaria($conexao, $id_usuario, $total_deposito, $total_saque, $lucro);
    
    // ✅ CALCULAR DADOS PARA ÁREA DIREITA COM SALDO TOTAL
    $area_direita = calcularAreaDireita($conexao, $id_usuario, $saldo_banca_total);
    
    // ✅ RESPOSTA COMPLETA COM META CORRIGIDA
    echo json_encode([
        'success' => true,
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
        
        // ✅ META DIÁRIA BASEADA EM (DEPÓSITO - SAQUE + LUCRO)
        'meta_diaria' => $meta_resultado['meta_diaria'],
        'meta_diaria_formatada' => 'R$ ' . number_format($meta_resultado['meta_diaria'], 2, ',', '.'),
        'meta_diaria_brl' => 'R$ ' . number_format($meta_resultado['meta_diaria'], 2, ',', '.'),
        'diaria_usada' => $meta_resultado['diaria_usada'],
        'unidade_usada' => $meta_resultado['unidade_usada'],
        'saldo_base_meta' => $meta_resultado['saldo_banca_meta'],
        
        // ✅ DADOS ESPECÍFICOS PARA ÁREA DIREITA COM SALDO TOTAL
        'diaria_formatada' => $area_direita['diaria_formatada'],
        'unidade_entrada_formatada' => $area_direita['unidade_entrada_formatada'],
        'diaria_raw' => $area_direita['diaria_porcentagem'],
        'saldo_banca_total' => $area_direita['saldo_banca_total'],
        'unidade_entrada_raw' => $area_direita['unidade_entrada'],
        
        // ✅ INFORMAÇÕES DETALHADAS PARA DEBUG COM LUCRO
        'calculo_detalhado' => [
            'saldo_banca_total' => $saldo_banca_total,
            'saldo_base_meta' => $meta_resultado['saldo_banca_meta'],
            'depositos' => $total_deposito,
            'saques' => $total_saque,
            'lucro' => $lucro,
            'diaria_percentual' => $meta_resultado['diaria_usada'],
            'unidade_multiplicador' => $meta_resultado['unidade_usada'],
            'formula_meta' => "Base: (R$ " . number_format($total_deposito, 2, ',', '.') . " - R$ " . number_format($total_saque, 2, ',', '.') . " + R$ " . number_format($lucro, 2, ',', '.') . ") = R$ " . number_format($meta_resultado['saldo_banca_meta'], 2, ',', '.') . " × {$meta_resultado['diaria_usada']}% × {$meta_resultado['unidade_usada']} = R$ " . number_format($meta_resultado['meta_diaria'], 2, ',', '.')
        ],
        
        // ✅ DADOS ESPECÍFICOS ÁREA DIREITA PARA DEBUG COM SALDO TOTAL
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
        'diaria_formatada' => '2%',
        'unidade_entrada_formatada' => 'R$ 0,00'
    ]);
}
?>