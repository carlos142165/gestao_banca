<?php
require_once 'config.php';
require_once 'carregar_sessao.php';

header('Content-Type: application/json');

$id_usuario = $_SESSION['usuario_id'] ?? null;
if (!$id_usuario) {
    echo json_encode(['success' => false, 'message' => 'Usuário não logado']);
    exit;
}

try {
    // Verificar se existe pelo menos um depósito para o usuário
    $stmt = $conexao->prepare("
        SELECT COUNT(*) as total_depositos 
        FROM controle 
        WHERE id_usuario = ? AND deposito > 0
    ");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $stmt->bind_result($total_depositos);
    $stmt->fetch();
    $stmt->close();

    $tem_deposito = $total_depositos > 0;

    echo json_encode([
        'success' => true,
        'tem_deposito' => $tem_deposito,
        'total_depositos' => $total_depositos,
        'message' => $tem_deposito 
            ? 'Usuário tem depósitos registrados' 
            : 'Usuário não possui depósitos'
    ]);

} catch (Exception $e) {
    error_log("Erro ao verificar depósito: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erro interno do servidor',
        'tem_deposito' => false
    ]);
}
?>