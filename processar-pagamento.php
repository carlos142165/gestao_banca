<?php
/**
 * PROCESSAR PAGAMENTO - processar-pagamento.php
 * ==============================================
 * Cria uma preferência de pagamento no Mercado Pago
 */

require_once 'config.php';
require_once 'carregar_sessao.php';
require_once 'config_mercadopago.php';

header('Content-Type: application/json');

$id_usuario = $_SESSION['usuario_id'] ?? null;

if (!$id_usuario) {
    echo json_encode([
        'success' => false,
        'message' => 'Usuário não logado'
    ]);
    exit;
}

// Receber dados do POST
$input = json_decode(file_get_contents('php://input'), true);

try {
    $id_plano = intval($input['id_plano'] ?? 0);
    $periodo = $input['periodo'] ?? 'mes';
    $modo_pagamento = $input['modo_pagamento'] ?? 'cartao';
    
    if (!$id_plano || !in_array($periodo, ['mes', 'ano'])) {
        throw new Exception('Dados inválidos');
    }
    
    // Converter período para o formato esperado
    $tipo_ciclo = $periodo === 'anual' ? 'anual' : 'mensal';
    
    // Criar preferência no Mercado Pago
    $resultado = MercadoPagoManager::criarPreferencia($id_usuario, $id_plano, $tipo_ciclo, $modo_pagamento);
    
    if (!$resultado['success']) {
        throw new Exception($resultado['message'] ?? 'Erro ao criar preferência');
    }
    
    // Registrar tentativa de pagamento no banco
    $stmt = $conexao->prepare("
        INSERT INTO transacoes_mercadopago 
        (id_usuario, id_pago_mercadopago, status_pagamento, tipo_pagamento, valor, descricao, resposta_mercadopago)
        VALUES (?, ?, 'pendente', ?, ?, ?, ?)
    ");
    
    // Pegar dados do plano para valor
    $stmt_plano = $conexao->prepare("SELECT preco_mes, preco_ano FROM planos WHERE id = ?");
    $stmt_plano->bind_param("i", $id_plano);
    $stmt_plano->execute();
    $result_plano = $stmt_plano->get_result();
    $plano = $result_plano->fetch_assoc();
    $stmt_plano->close();
    
    $valor = $tipo_ciclo === 'anual' ? $plano['preco_ano'] : $plano['preco_mes'];
    $descricao = "Assinatura Plano - " . ucfirst($tipo_ciclo);
    $preference_id = $resultado['data']['id'] ?? null;
    
    $stmt->bind_param(
        "isssss",
        $id_usuario,
        $preference_id,
        $modo_pagamento,
        $valor,
        $descricao,
        json_encode($resultado['data'])
    );
    
    $stmt->execute();
    $stmt->close();
    
    // Retornar URL para redirecionar ao Mercado Pago
    echo json_encode([
        'success' => true,
        'preference_url' => $resultado['data']['init_point'] ?? null,
        'preference_id' => $preference_id,
        'message' => 'Preferência criada com sucesso'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
