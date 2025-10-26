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

// ==================================================================================================================== 
// ========================== CONFIGURAÇÃO DE ADMINS ==========================
// ==================================================================================================================== 
// ⭐ LISTA DE IDs COM ACESSO ILIMITADO (PODE ADICIONAR QUANTOS QUISER)
// 
// Adicione os IDs dos usuários que terão acesso total ao site
// Exemplo: define('ADMIN_USER_IDS', [23, 15, 8, 45]);
// 
// Se quiser apenas um admin: define('ADMIN_USER_IDS', [23]);
// 
// ⚠️ IMPORTANTE: A ORDEM NÃO IMPORTA, APENAS ADICIONE OS IDs NO ARRAY

define('ADMIN_USER_IDS', [
    23,    // 👈 usuario : CARLOS
    42,  // 👈 usuario : ALANNES
    // 8,   // 👈 Descomente para adicionar outro usuário com acesso ilimitado
    // 45,  // 👈 Adicione quantos IDs quiser neste formato
]);

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
// ========================== VERIFICAR SE É ADMIN ==========================
// ==================================================================================================================== 
// Se o usuário está na lista de admins (IDs ilimitados), permitir TUDO sem restrições
if (in_array($id_usuario, ADMIN_USER_IDS)) {
    echo json_encode([
        'success' => true,
        'pode_prosseguir' => true,
        'plano_atual' => 'ADMIN - Ilimitado',
        'mensagem' => '',
        'admin_mode' => true,
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
