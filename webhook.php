<?php
/**
 * WEBHOOK MERCADO PAGO - webhook.php
 * ==================================
 * Processa notificações de pagamento do Mercado Pago
 */

require_once 'config.php';
require_once 'config_mercadopago.php';

header('Content-Type: application/json');

// Log para debug (opcional)
$log_file = __DIR__ . '/logs/webhook.log';
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

// Receber notificação do Mercado Pago
$type = $_GET['type'] ?? null;
$data_id = $_GET['data.id'] ?? null;
$topic = $_GET['topic'] ?? null;

file_put_contents($log_file, date('Y-m-d H:i:s') . " - Recebido: " . json_encode($_GET) . "\n", FILE_APPEND);

// Se for status redirect (após pagamento)
if ($type === 'payment' && $data_id) {
    processarPagamento($data_id, $conexao);
}

// Se for notificação de IPN
if ($topic === 'payment') {
    $payload = json_decode(file_get_contents("php://input"), true);
    if (isset($payload['data']['id'])) {
        processarPagamento($payload['data']['id'], $conexao);
    }
}

/**
 * PROCESSAR PAGAMENTO
 */
function processarPagamento($payment_id, $conexao) {
    global $log_file;
    
    try {
        // Obter dados do pagamento via API Mercado Pago
        $resultado = MercadoPagoManager::obterPagamento($payment_id);
        
        if (!$resultado['success']) {
            throw new Exception('Erro ao obter dados do pagamento');
        }
        
        $payment = $resultado['data'];
        file_put_contents($log_file, "Pagamento obtido: " . json_encode($payment) . "\n", FILE_APPEND);
        
        // Verificar status do pagamento
        $status = $payment['status'] ?? null;
        $external_reference = $payment['external_reference'] ?? null;
        
        if (!$external_reference) {
            throw new Exception('Referência externa não encontrada');
        }
        
        // Extrair dados da referência externa (formato: user_{id}_plan_{id}_{ciclo})
        if (preg_match('/user_(\d+)_plan_(\d+)_(\w+)/', $external_reference, $matches)) {
            $id_usuario = intval($matches[1]);
            $id_plano = intval($matches[2]);
            $tipo_ciclo = $matches[3];
            
            // Atualizar transação
            atualizarTransacao($conexao, $id_usuario, $payment_id, $status, $payment);
            
            // Se pagamento foi aprovado
            if ($status === 'approved') {
                // Criar/atualizar assinatura
                $id_assinatura = MercadoPagoManager::criarAssinatura(
                    $id_usuario,
                    $id_plano,
                    $tipo_ciclo,
                    $payment['payment_method']['type'] ?? 'outro',
                    floatval($payment['transaction_amount'] ?? 0),
                    $payment_id
                );
                
                file_put_contents($log_file, "Assinatura criada: {$id_assinatura}\n", FILE_APPEND);
                
                // Se pagamento foi com cartão e usuário marcou para salvar
                if (in_array($payment['payment_method']['type'], ['credit_card', 'debit_card'])) {
                    salvarCartao($conexao, $id_usuario, $payment);
                }
                
                // Se pagamento foi com PIX
                if ($payment['payment_method']['type'] === 'bank_transfer' && 
                    $payment['payment_method']['id'] === 'pix') {
                    // PIX já foi processado
                }
                
            } else if ($status === 'rejected' || $status === 'cancelled') {
                // Atualizar assinatura para cancelada se houver
                $stmt = $conexao->prepare("
                    UPDATE assinaturas 
                    SET status = 'cancelada'
                    WHERE id_usuario = ? AND id_plano = ? AND status = 'pendente'
                    LIMIT 1
                ");
                $stmt->bind_param("ii", $id_usuario, $id_plano);
                $stmt->execute();
                $stmt->close();
            }
        }
        
        file_put_contents($log_file, "Pagamento processado com sucesso\n", FILE_APPEND);
        
    } catch (Exception $e) {
        file_put_contents($log_file, "Erro ao processar: " . $e->getMessage() . "\n", FILE_APPEND);
    }
}

/**
 * ATUALIZAR TRANSAÇÃO NO BANCO
 */
function atualizarTransacao($conexao, $id_usuario, $payment_id, $status, $payment_data) {
    $stmt = $conexao->prepare("
        UPDATE transacoes_mercadopago 
        SET 
            status_pagamento = ?,
            resposta_mercadopago = ?,
            data_atualizacao = NOW()
        WHERE id_pago_mercadopago = ? AND id_usuario = ?
    ");
    
    $status_map = [
        'approved' => 'aprovado',
        'rejected' => 'rejeitado',
        'cancelled' => 'cancelado',
        'pending' => 'pendente'
    ];
    
    $db_status = $status_map[$status] ?? $status;
    $payment_json = json_encode($payment_data);
    
    $stmt->bind_param("sssi", $db_status, $payment_json, $payment_id, $id_usuario);
    $stmt->execute();
    $stmt->close();
}

/**
 * SALVAR CARTÃO DO USUÁRIO
 */
function salvarCartao($conexao, $id_usuario, $payment) {
    try {
        if (!isset($payment['card']['id'])) {
            return false;
        }
        
        $dados_cartao = [
            'numero' => $payment['card']['number_snapshot']['last_four_digits'] ?? '',
            'bandeira' => $payment['payment_method']['issuer']['name'] ?? 'desconhecido',
            'titular' => $payment['payer']['email'] ?? '',
            'mes' => $payment['card']['expiration_month'] ?? 0,
            'ano' => $payment['card']['expiration_year'] ?? 0
        ];
        
        return MercadoPagoManager::salvarCartao(
            $id_usuario,
            $payment['card']['id'],
            $dados_cartao
        );
        
    } catch (Exception $e) {
        file_put_contents($log_file, "Erro ao salvar cartão: " . $e->getMessage() . "\n", FILE_APPEND);
        return false;
    }
}

// Retornar sucesso
echo json_encode(['success' => true, 'message' => 'Webhook processado']);
?>

