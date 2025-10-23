<?php
/**
 * PROCESSAR PAGAMENTO - MERCADO PAGO
 * =======================================
 * Script para processar pagamentos com Mercado Pago
 * Retorna SEMPRE JSON válido
 */

// ✅ CONFIGURATION CRITICA: OUTPUT BUFFER E HEADERS
error_reporting(E_ALL);
ini_set('display_errors', '0'); // Não mostrar erros na saída
ob_start(); // Iniciar buffer

// Limpar buffers anteriores se houver
while (ob_get_level() > 1) {
    ob_end_clean();
}

// ✅ SETAR HEADERS JSON PRIMEIRO
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// ✅ Iniciar sessão APÓS headers
session_start();

// ✅ LIMPAR QUALQUER OUTPUT ANTERIOR
ob_clean();

// ============================================
// VALIDAÇÃO INICIAL
// ============================================

$id_usuario = $_SESSION['usuario_id'] ?? null;

if (!$id_usuario) {
    http_response_code(401);
    ob_end_clean();
    exit(json_encode([
        'success' => false,
        'message' => 'Usuário não logado'
    ]));
}

// ============================================
// CARREGAR CONFIGURAÇÕES
// ============================================

try {
    // Suprimir warnings das includes
    require_once 'config.php';
    require_once 'config_mercadopago.php';
} catch (Throwable $e) {
    http_response_code(500);
    ob_end_clean();
    exit(json_encode([
        'success' => false,
        'message' => 'Erro ao carregar configurações: ' . $e->getMessage()
    ]));
}

// ============================================
// PROCESSAR PAGAMENTO
// ============================================

try {
    // Receber dados do POST
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    
    // Validar entrada
    $id_plano = intval($input['id_plano'] ?? 0);
    $periodo = strtolower($input['periodo'] ?? 'mes');
    $modo_pagamento = strtolower($input['modo_pagamento'] ?? 'cartao');
    
    // Validação
    if ($id_plano <= 0) {
        throw new Exception('ID do plano inválido');
    }
    
    if (!in_array($periodo, ['mes', 'ano', 'mensal', 'anual'])) {
        throw new Exception('Período inválido. Use "mes" ou "ano"');
    }
    
    // Normalizar período
    $tipo_ciclo = (strpos($periodo, 'ano') !== false) ? 'anual' : 'mensal';
    
    // ✅ VERIFICAR CLASSE
    if (!class_exists('MercadoPagoManager')) {
        throw new Exception('Classe MercadoPagoManager não encontrada');
    }
    
    // ✅ CRIAR PREFERÊNCIA NO MERCADO PAGO
    $resultado = MercadoPagoManager::criarPreferencia(
        $id_usuario,
        $id_plano,
        $tipo_ciclo,
        $modo_pagamento
    );
    
    // ✅ VALIDAR RESULTADO
    if (!is_array($resultado)) {
        throw new Exception('Resposta inválida do Mercado Pago');
    }
    
    if (!isset($resultado['success'])) {
        throw new Exception('Resposta do MP sem campo success');
    }
    
    if (!$resultado['success']) {
        throw new Exception(
            $resultado['message'] ?? 'Erro ao criar preferência no Mercado Pago'
        );
    }
    
    // ✅ EXTRAIR DADOS
    $data = $resultado['data'] ?? [];
    $preference_url = $data['init_point'] ?? null;
    $preference_id = $data['id'] ?? null;
    
    if (!$preference_url) {
        throw new Exception('URL de redirecionamento não obtida');
    }
    
    // ✅ REGISTRAR TENTATIVA (opcional - não falha se não conseguir)
    try {
        if (isset($conexao) && $conexao) {
            $stmt = $conexao->prepare("
                INSERT INTO transacoes_mercadopago 
                (id_usuario, id_pago_mercadopago, status_pagamento, tipo_pagamento, valor, descricao, resposta_mercadopago)
                VALUES (?, ?, 'pendente', ?, ?, ?, ?)
            ");
            
            if ($stmt) {
                // Pegar valor do plano
                $stmt_plano = $conexao->prepare("SELECT preco_mes, preco_ano FROM planos WHERE id = ?");
                if ($stmt_plano) {
                    $stmt_plano->bind_param("i", $id_plano);
                    $stmt_plano->execute();
                    $result_plano = $stmt_plano->get_result();
                    $plano = $result_plano->fetch_assoc();
                    $stmt_plano->close();
                    
                    $valor = $tipo_ciclo === 'anual' ? ($plano['preco_ano'] ?? 0) : ($plano['preco_mes'] ?? 0);
                } else {
                    $valor = 0;
                }
                
                $descricao = "Assinatura Plano - " . ucfirst($tipo_ciclo);
                
                $stmt->bind_param(
                    "isssss",
                    $id_usuario,
                    $preference_id,
                    $modo_pagamento,
                    $valor,
                    $descricao,
                    json_encode($data)
                );
                
                $stmt->execute();
                $stmt->close();
            }
        }
    } catch (Exception $e) {
        // Log local (não falha o pagamento)
        error_log('Erro ao registrar transação: ' . $e->getMessage());
    }
    
    // ✅ RETORNAR SUCESSO
    http_response_code(200);
    ob_end_clean();
    exit(json_encode([
        'success' => true,
        'preference_url' => $preference_url,
        'preference_id' => $preference_id,
        'message' => 'Preferência criada com sucesso'
    ]));
    
} catch (Throwable $e) {
    http_response_code(400);
    ob_end_clean();
    exit(json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_type' => get_class($e)
    ]));
}
?>
