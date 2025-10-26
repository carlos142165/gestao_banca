<?php
/**
 * VERIFICAR LIMITE - verificar-limite.php
 * =======================================
 * Verifica se o usuário pode cadastrar mais mentores ou entradas
 * 
 * ⚠️ IMPORTANTE: Desabilita limitações se o usuário é admin
 */

require_once 'config.php';
require_once 'carregar_sessao.php';
require_once 'config_mercadopago.php';
require_once 'admin-ids-config.php';

// ==================================================================================================================== 
// ========================== CONFIGURAÇÃO DE USUÁRIOS VITALÍCIO ==========================
// ==================================================================================================================== 
// 🎯 Os IDs de usuários vitalício são carregados automaticamente do arquivo de configuração
// Use a página administrativa (ID 23) para gerenciar os IDs sem precisar editar este arquivo
// 
// Usuários Vitalício: Têm acesso ilimitado ao site
// Super Admin (ID 23): Pode gerenciar e acessar a área administrativa

header('Content-Type: application/json');

$id_usuario = $_SESSION['usuario_id'] ?? null;
$acao = $_GET['acao'] ?? 'mentor'; // 'mentor' ou 'entrada'

if (!$id_usuario) {
    echo json_encode([
        'success' => false,
        'pode_prosseguir' => false,
        'mensagem' => 'Você precisa estar logado'
    ]);
    exit;
}

// ==================================================================================================================== 
// ========================== VERIFICAR SE É USUÁRIO VITALÍCIO ==========================
// ==================================================================================================================== 
// Se o usuário está na lista de vitalício, permitir TUDO sem restrições
if (AdminIdManager::ehAdmin($id_usuario)) {
    echo json_encode([
        'success' => true,
        'pode_prosseguir' => true,
        'plano_atual' => 'Usuário Vitalício - Ilimitado',
        'mensagem' => '',
        'vitalicio' => true,
        'user_id' => $id_usuario
    ]);
    exit;
}

try {
    // Obter plano atual
    $plano = MercadoPagoManager::obterPlanoAtual($id_usuario);
    
    if ($acao === 'mentor') {
        // Verificar limite de mentores
        $pode_prosseguir = MercadoPagoManager::verificarLimiteMentores($id_usuario, $plano['id']);
        
        $mensagem = !$pode_prosseguir 
            ? "Você atingiu o limite de mentores no plano {$plano['nome']}. Faça upgrade!"
            : '';
            
    } else if ($acao === 'entrada') {
        // Verificar limite de entradas
        $pode_prosseguir = MercadoPagoManager::verificarLimiteEntradas($id_usuario, $plano['id']);
        
        $mensagem = !$pode_prosseguir 
            ? "Você atingiu o limite de entradas diárias no plano {$plano['nome']}. Faça upgrade!"
            : '';
    } else {
        throw new Exception('Ação inválida');
    }
    
    echo json_encode([
        'success' => true,
        'pode_prosseguir' => $pode_prosseguir,
        'plano_atual' => $plano['nome'],
        'mensagem' => $mensagem
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'pode_prosseguir' => true, // Fail-safe: permitir se houver erro
        'message' => $e->getMessage()
    ]);
}
?>
