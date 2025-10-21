<?php
/**
 * TESTE DA API DE VERIFICAÇÃO DE LIMITE
 * Este arquivo testa se a API de verificação de limite está respondendo corretamente
 */

header('Content-Type: application/json');
session_start();
require_once 'config.php';
require_once 'config_mercadopago.php';

// Definir um ID de usuário para teste
$id_usuario = $_GET['user_id'] ?? 1;
$acao = $_GET['acao'] ?? 'mentor'; // 'mentor' ou 'entrada'

$resposta = [
    'sucesso' => false,
    'mensagem' => '',
    'dados' => []
];

try {
    // Obter dados do usuário
    $stmt = $conexao->prepare("SELECT id_plano FROM usuarios WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Erro ao preparar query: " . $conexao->error);
    }
    
    $stmt->bind_param("i", $id_usuario);
    if (!$stmt->execute()) {
        throw new Exception("Erro ao executar query: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception("Usuário não encontrado");
    }
    
    $usuario = $result->fetch_assoc();
    $stmt->close();
    
    $id_plano = intval($usuario['id_plano']);
    
    // Verificar limite baseado na ação
    $pode_prosseguir = false;
    
    if ($acao === 'mentor') {
        $pode_prosseguir = MercadoPagoManager::verificarLimiteMentores($id_usuario, $id_plano);
        
        // Obter dados adicionais
        $stmt = $conexao->prepare("SELECT mentores_limite FROM planos WHERE id = ?");
        $stmt->bind_param("i", $id_plano);
        $stmt->execute();
        $plano_result = $stmt->get_result();
        $plano = $plano_result->fetch_assoc();
        $stmt->close();
        
        $stmt = $conexao->prepare("SELECT COUNT(*) as total FROM mentores WHERE id_usuario = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $mentores_result = $stmt->get_result();
        $mentores = $mentores_result->fetch_assoc();
        $stmt->close();
        
        $resposta['dados'] = [
            'limite' => intval($plano['mentores_limite']),
            'atual' => intval($mentores['total']),
            'tipo' => 'mentor'
        ];
        
    } else if ($acao === 'entrada') {
        $pode_prosseguir = MercadoPagoManager::verificarLimiteEntradas($id_usuario, $id_plano);
        
        // Obter dados adicionais
        $stmt = $conexao->prepare("SELECT entradas_diarias FROM planos WHERE id = ?");
        $stmt->bind_param("i", $id_plano);
        $stmt->execute();
        $plano_result = $stmt->get_result();
        $plano = $plano_result->fetch_assoc();
        $stmt->close();
        
        $stmt = $conexao->prepare("SELECT COUNT(*) as total FROM valor_mentores WHERE id_usuario = ? AND DATE(data_criacao) = CURDATE()");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $entradas_result = $stmt->get_result();
        $entradas = $entradas_result->fetch_assoc();
        $stmt->close();
        
        $resposta['dados'] = [
            'limite' => intval($plano['entradas_diarias']),
            'atual' => intval($entradas['total']),
            'tipo' => 'entrada'
        ];
    }
    
    $resposta['sucesso'] = true;
    $resposta['pode_prosseguir'] = $pode_prosseguir;
    $resposta['mensagem'] = $pode_prosseguir ? 'Pode prosseguir' : 'Limite atingido';
    
} catch (Exception $e) {
    $resposta['sucesso'] = false;
    $resposta['mensagem'] = $e->getMessage();
}

echo json_encode($resposta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
