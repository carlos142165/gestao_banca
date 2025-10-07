<?php
require_once 'config.php';
require_once 'carregar_sessao.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sessão inválida']);
    exit();
}

$id_usuario = intval($_SESSION['usuario_id']);

// ✅ FUNÇÃO PARA CALCULAR DADOS DA ÁREA DIREITA - CORRIGIDA COM BANCA CONGELADA
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
        
        // ✅ CORREÇÃO: Manter 2 casas decimais
        $diaria = $ultima_diaria !== null ? round(floatval($ultima_diaria), 2) : 1.00;
        
        // ✅ NOVO: Calcular lucro ATÉ ONTEM (excluindo hoje)
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
        
        // ✅ Obter depósitos e saques
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
        
        // ✅ BANCA DO INÍCIO DO DIA (congelada até meia-noite)
        $banca_inicio_dia = $banca_inicial + $lucro_ate_ontem;
        
        // ✅ Calcular unidade de entrada: banca_inicio_dia * (diária / 100)
        $unidade_entrada = $banca_inicio_dia * ($diaria / 100);
        
        return [
            'diaria_porcentagem' => $diaria,
            'saldo_banca_total' => $saldo_banca_total, // banca atual (com lucro de hoje)
            'banca_inicio_dia' => $banca_inicio_dia, // ✅ NOVA: banca congelada
            'lucro_ate_ontem' => $lucro_ate_ontem, // ✅ NOVO
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

// ✅ FUNÇÃO PARA BUSCAR ÚLTIMO CAMPO - CORRIGIDA PARA DECIMAL
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
    
    // ✅ CORREÇÃO: Se for diária, garantir 2 casas decimais
    if ($campo === 'diaria' && $valor !== null) {
        return round(floatval($valor), 2);
    }
    
    return $valor;
}

// ✅ FUNÇÃO PARA BUSCAR META
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

// ✅ FUNÇÃO PARA VALIDAR TIPO DE META
function validarTipoMeta($tipoMeta) {
    $tipos_validos = ['Meta Fixa', 'Meta Turbo'];
    
    if (!in_array($tipoMeta, $tipos_validos)) {
        return 'Meta Fixa'; // Default
    }
    
    return $tipoMeta;
}

// ✅ PROCESSAR REQUISIÇÕES POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    $acao = $data['acao'] ?? '';
    $valor = abs(floatval($data['valor'] ?? 0));
    
    // ✅ CORREÇÃO CRÍTICA: Processar diária como decimal
    $diaria_raw = $data['diaria'] ?? 1;
    $diaria = round(floatval(str_replace(',', '.', $diaria_raw)), 2);
    
    $unidade = intval($data['unidade'] ?? 1);
    $odds = isset($data['odds']) ? floatval(str_replace(',', '.', $data['odds'])) : 1.5;
    $tipoMeta = validarTipoMeta($data['tipoMeta'] ?? 'Meta Fixa');

    // ✅ OPERAÇÃO DE RESET
    if ($acao === 'resetar') {
        try {
            $conexao->begin_transaction();
            
            // Deletar dados do usuário
            $stmt1 = $conexao->prepare("DELETE FROM controle WHERE id_usuario = ?");
            $stmt1->bind_param("i", $id_usuario);
            $stmt1->execute();
            $stmt1->close();

            $stmt2 = $conexao->prepare("DELETE FROM valor_mentores WHERE id_usuario = ?");
            $stmt2->bind_param("i", $id_usuario);
            $stmt2->execute();
            $stmt2->close();
            
            $conexao->commit();

            // ✅ RETORNAR DADOS ZERADOS
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
                'banca_formatada' => 'R$ 0,00',
                'lucro_formatado' => 'R$ 0,00'
            ]);
            
        } catch (Exception $e) {
            $conexao->rollback();
            echo json_encode(['success' => false, 'message' => 'Erro ao resetar dados']);
        }
        exit();
    }

    // ✅ OPERAÇÃO DE ALTERAÇÃO
    if ($acao === 'alterar') {
        try {
            // ✅ VERIFICAR SE EXISTE REGISTRO
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
                    'diaria_formatada' => $area_direita['diaria_formatada'],
                    'unidade_entrada_formatada' => $area_direita['unidade_entrada_formatada'],
                    'banca_formatada' => 'R$ ' . number_format($saldo_banca_total, 2, ',', '.'),
                    'lucro_formatado' => 'R$ ' . number_format($lucro, 2, ',', '.'),
                    'banca_inicio_dia' => $area_direita['banca_inicio_dia'],
                    'lucro_ate_ontem' => $area_direita['lucro_ate_ontem']
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao alterar dados']);
            }
            $stmt->close();
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro no banco de dados']);
        }
        exit();
    }

    // ✅ VALIDAÇÃO PARA DEPÓSITO E SAQUE
    if ($valor <= 0 || !in_array($acao, ['deposito', 'saque', 'cadastrar'])) {
        echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
        exit();
    }

    // ✅ OPERAÇÕES DE DEPÓSITO/SAQUE
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
            $area_direita = calcularAreaDireita($conexao, $id_usuario, $saldo_banca_total);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Operação realizada com sucesso',
                'banca' => number_format($saldo_banca_total, 2, '.', ''),
                'lucro' => number_format($lucro, 2, '.', ''),
                'diaria' => number_format($diaria, 2, '.', ''),
                'diaria_formatada' => $area_direita['diaria_formatada'],
                'unidade_entrada_formatada' => $area_direita['unidade_entrada_formatada'],
                'banca_formatada' => 'R$ ' . number_format($saldo_banca_total, 2, ',', '.'),
                'lucro_formatado' => 'R$ ' . number_format($lucro, 2, ',', '.'),
                'banca_inicio_dia' => $area_direita['banca_inicio_dia'],
                'lucro_ate_ontem' => $area_direita['lucro_ate_ontem']
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

    $area_direita = calcularAreaDireita($conexao, $id_usuario, $saldo_banca_total);

    echo json_encode([
        'success' => true,
        'banca' => number_format($saldo_banca_total, 2, '.', ''),
        'lucro' => number_format($lucro, 2, '.', ''),
        'diaria' => number_format($ultima_diaria, 2, '.', ''),
        'unidade' => intval($ultima_unidade),
        'odds' => number_format($ultima_odds, 2, '.', ''),
        'meta' => $ultima_meta,
        'diaria_formatada' => $area_direita['diaria_formatada'],
        'unidade_entrada_formatada' => $area_direita['unidade_entrada_formatada'],
        'banca_formatada' => 'R$ ' . number_format($saldo_banca_total, 2, ',', '.'),
        'lucro_formatado' => 'R$ ' . number_format($lucro, 2, ',', '.'),
        'banca_inicio_dia' => $area_direita['banca_inicio_dia'],
        'lucro_ate_ontem' => $area_direita['lucro_ate_ontem']
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro: ' . $e->getMessage()
    ]);
}
?>