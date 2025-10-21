<?php
/**
 * TESTE DE LIMITE - DEBUG
 */

require_once 'config.php';
require_once 'carregar_sessao.php';
require_once 'config_mercadopago.php';

header('Content-Type: application/json');

$id_usuario = $_SESSION['usuario_id'] ?? null;

if (!$id_usuario) {
    echo json_encode([
        'erro' => 'Não logado',
        'session' => $_SESSION
    ]);
    exit;
}

try {
    // Obter plano
    $plano = MercadoPagoManager::obterPlanoAtual($id_usuario);
    
    // Contar entradas de HOJE
    global $conexao;
    $stmt = $conexao->prepare("
        SELECT COUNT(*) as total FROM valor_mentores 
        WHERE id_usuario = ? AND DATE(data_criacao) = CURDATE()
    ");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    $contagem = $result->fetch_assoc();
    $stmt->close();
    
    // Verificar limite
    $pode_prosseguir_mentor = MercadoPagoManager::verificarLimiteMentores($id_usuario, $plano['id']);
    $pode_prosseguir_entrada = MercadoPagoManager::verificarLimiteEntradas($id_usuario, $plano['id']);
    
    echo json_encode([
        'usuario_id' => $id_usuario,
        'plano' => $plano,
        'entradas_hoje' => $contagem['total'],
        'limite_entradas' => $plano['entradas_diarias'],
        'pode_prosseguir_mentor' => $pode_prosseguir_mentor,
        'pode_prosseguir_entrada' => $pode_prosseguir_entrada,
        'calculo' => $contagem['total'] . ' < ' . $plano['entradas_diarias'] . ' = ' . ($contagem['total'] < $plano['entradas_diarias'] ? 'true' : 'false')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'erro' => $e->getMessage(),
        'arquivo' => $e->getFile(),
        'linha' => $e->getLine()
    ]);
}
?>
