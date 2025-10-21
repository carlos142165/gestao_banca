<?php
/**
 * TESTE DE VERIFICAÇÃO DE LIMITE
 */

require_once 'config.php';
require_once 'carregar_sessao.php';
require_once 'config_mercadopago.php';

header('Content-Type: application/json');

$id_usuario = $_SESSION['usuario_id'] ?? null;

if (!$id_usuario) {
    echo json_encode([
        'erro' => 'Usuário não logado',
        'usuario_id' => $id_usuario
    ]);
    exit;
}

try {
    // Testar mentor
    echo json_encode([
        'usuario_id' => $id_usuario,
        'teste_mentor' => [
            'plano' => MercadoPagoManager::obterPlanoAtual($id_usuario),
            'pode_prosseguir' => MercadoPagoManager::verificarLimiteMentores($id_usuario),
            'msg' => 'Verificação de MENTORES'
        ],
        'teste_entrada' => [
            'plano' => MercadoPagoManager::obterPlanoAtual($id_usuario),
            'pode_prosseguir' => MercadoPagoManager::verificarLimiteEntradas($id_usuario),
            'msg' => 'Verificação de ENTRADAS'
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'erro' => $e->getMessage(),
        'arquivo' => $e->getFile(),
        'linha' => $e->getLine()
    ]);
}
?>
