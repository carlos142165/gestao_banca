<?php
require_once 'config.php';
require_once 'carregar_sessao.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sessão inválida']);
    exit();
}

$id_usuario = intval($_SESSION['usuario_id']);

// ✅ FUNÇÃO PARA CALCULAR DADOS DA ÁREA DIREITA
function calcularAreaDireita($conexao, $id_usuario, $saldo_banca_total, $depositos, $saques, $lucro, $tipo_meta = 'Meta Fixa') {
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
        
        // ✅ CALCULAR BANCA BASEADO NO TIPO DE META
        if ($tipo_meta === 'Meta Fixa') {
            // META FIXA: Usa apenas depósitos - saques (SEM lucro)
            $banca_para_calculo = $depositos - $saques;
        } else {
            // META TURBO: Usa depósitos - saques + lucro
            $banca_para_calculo = $saldo_banca_total;
        }
        
        // Calcular unidade de entrada
        $unidade_entrada = $banca_para_calculo * ($diaria / 100);
        
        return [
            'diaria_porcentagem' => $diaria,
            'saldo_banca_total' => $saldo_banca_total,
            'banca_para_calculo' => $banca_para_calculo,
            'unidade_entrada' => $unidade_entrada,
            'diaria_formatada' => number_format($diaria, 0) . '%',
            'unidade_entrada_formatada' => 'R$ ' . number_format($unidade_entrada, 2, ',', '.'),
            'tipo_meta' => $tipo_meta
        ];
        
    } catch (Exception $e) {
        error_log("Erro ao calcular área direita: " . $e->getMessage());
        return [
            'diaria_porcentagem' => 2,
            'saldo_banca_total' => 0,
            'banca_para_calculo' => 0,
            'unidade_entrada' => 0,
            'diaria_formatada' => '2%',
            'unidade_entrada_formatada' => 'R$ 0,00',
            'tipo_meta' => $tipo_meta
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
    $diaria = floatval($data['diaria'] ?? 2);
    $unidade = intval($data['unidade'] ?? 2);
    $odds = isset($data['odds']) ? floatval(str_replace(',', '.', $data['odds'])) : 1.5;
    $tipoMeta = validarTipoMeta($data['tipoMeta'] ?? 'Meta Fixa');

    // ✅ OPERAÇÃO DE RESET
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
                'depositos' => '0.00',
                'saques' => '0.00',
                'diaria' => '2.00',
                'unidade' => 2,
                'odds' => '1.50',
                'meta' => 'Meta Fixa',
                'diaria_formatada' => '2%',
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
                
                $area_direita = calcularAreaDireita($conexao, $id_usuario, $saldo_banca_total, $total_deposito, $total_saque, $lucro, $tipoMeta);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Dados alterados com sucesso',
                    'banca' => number_format($saldo_banca_total, 2, '.', ''),
                    'lucro' => number_format($lucro, 2, '.', ''),
                    'depositos' => number_format($total_deposito, 2, '.', ''),
                    'saques' => number_format($total_saque, 2, '.', ''),
                    'diaria' => number_format($diaria, 2, '.', ''),
                    'unidade' => $unidade,
                    'odds' => number_format($odds, 2, '.', ''),
                    'meta' => $tipoMeta,
                    'diaria_formatada' => $area_direita['diaria_formatada'],
                    'unidade_entrada_formatada' => $area_direita['unidade_entrada_formatada'],
                    'banca_formatada' => 'R$ ' . number_format($saldo_banca_total, 2, ',', '.'),
                    'lucro_formatado' => 'R$ ' . number_format($lucro, 2, ',', '.')
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
            
            $area_direita = calcularAreaDireita($conexao, $id_usuario, $saldo_banca_total, $total_deposito, $total_saque, $lucro, $tipoMeta);
            $meta_diaria = $area_direita['unidade_entrada'] * $unidade;
            
            echo json_encode([
                'success' => true, 
                'message' => 'Operação realizada com sucesso',
                'deposito' => number_format($total_deposito, 2, '.', ''),
                'saque' => number_format($total_saque, 2, '.', ''),
                'banca' => number_format($saldo_banca_total, 2, '.', ''),
                'lucro' => number_format($lucro, 2, '.', ''),
                'depositos' => number_format($total_deposito, 2, '.', ''),
                'saques' => number_format($total_saque, 2, '.', ''),
                'diaria' => number_format($diaria, 2, '.', ''),
                'unidade' => $unidade,
                'odds' => number_format($odds, 2, '.', ''),
                'meta' => $tipoMeta,
                'diaria_formatada' => $area_direita['diaria_formatada'],
                'unidade_entrada_formatada' => $area_direita['unidade_entrada_formatada'],
                'meta_diaria_formatada' => 'R$ ' . number_format($meta_diaria, 2, ',', '.'),
                'banca_formatada' => 'R$ ' . number_format($saldo_banca_total, 2, ',', '.'),
                'lucro_formatado' => 'R$ ' . number_format($lucro, 2, ',', '.')
            ]);
            
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao salvar no banco']);
        }
        $stmt->close();
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
    }
    exit();
}

// ✅ REQUISIÇÃO GET: RETORNA DADOS DA BANCA
try {
    $total_deposito = getSoma($conexao, 'deposito', $id_usuario);
    $total_saque = getSoma($conexao, 'saque', $id_usuario);
    $dados_lucro = calcularLucro($conexao, $id_usuario);
    $lucro = $dados_lucro['lucro'];
    $saldo_banca_total = $total_deposito - $total_saque + $lucro;
    $mostrar_radios = $total_deposito > 0;

    $ultima_diaria = getUltimoCampo($conexao, 'diaria', $id_usuario) ?? 2;
    $ultima_unidade = getUltimoCampo($conexao, 'unidade', $id_usuario) ?? 2;
    $ultima_odds = getUltimoCampo($conexao, 'odds', $id_usuario);
    $ultima_meta = getUltimaMeta($conexao, $id_usuario);

    $odds_final = ($ultima_odds && floatval($ultima_odds) > 0) ? $ultima_odds : 1.5;

    $area_direita = calcularAreaDireita($conexao, $id_usuario, $saldo_banca_total, $total_deposito, $total_saque, $lucro, $ultima_meta);
    $meta_diaria = $area_direita['unidade_entrada'] * $ultima_unidade;

    echo json_encode([
        'success' => true,
        'deposito' => number_format($total_deposito, 2, '.', ''),
        'saque' => number_format($total_saque, 2, '.', ''),
        'banca' => number_format($saldo_banca_total, 2, '.', ''),
        'lucro' => number_format($lucro, 2, '.', ''),
        'depositos' => number_format($total_deposito, 2, '.', ''),
        'saques' => number_format($total_saque, 2, '.', ''),
        'mostrar_radios' => $mostrar_radios,
        'diaria' => number_format($ultima_diaria, 2, '.', ''),
        'unidade' => intval($ultima_unidade),
        'odds' => number_format($odds_final, 2, '.', ''),
        'meta' => $ultima_meta,
        'diaria_formatada' => $area_direita['diaria_formatada'],
        'unidade_entrada_formatada' => $area_direita['unidade_entrada_formatada'],
        'meta_diaria_formatada' => 'R$ ' . number_format($meta_diaria, 2, ',', '.'),
        'banca_formatada' => 'R$ ' . number_format($saldo_banca_total, 2, ',', '.'),
        'lucro_formatado' => 'R$ ' . number_format($lucro, 2, ',', '.'),
        
        // ✅ DEBUG
        'area_direita_debug' => [
            'tipo_meta' => $ultima_meta,
            'banca_total' => $saldo_banca_total,
            'banca_calculo' => $area_direita['banca_para_calculo'],
            'depositos' => $total_deposito,
            'saques' => $total_saque,
            'lucro' => $lucro,
            'formula' => $ultima_meta === 'Meta Fixa' 
                ? "Meta Fixa: (Dep: {$total_deposito} - Saq: {$total_saque}) × {$ultima_diaria}% = {$area_direita['unidade_entrada']}"
                : "Meta Turbo: (Dep: {$total_deposito} - Saq: {$total_saque} + Lucro: {$lucro}) × {$ultima_diaria}% = {$area_direita['unidade_entrada']}"
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar dados: ' . $e->getMessage(),
        'banca' => '0.00',
        'lucro' => '0.00',
        'depositos' => '0.00',
        'saques' => '0.00',
        'banca_formatada' => 'R$ 0,00',
        'lucro_formatado' => 'R$ 0,00'
    ]);
}
?>