<?php
/**
 * OBTER CARTÕES SALVOS - obter-cartoes-salvos.php
 * ===============================================
 * Retorna lista de cartões salvos do usuário logado
 */

require_once 'config.php';
require_once 'carregar_sessao.php';

header('Content-Type: application/json');

$id_usuario = $_SESSION['usuario_id'] ?? null;

if (!$id_usuario) {
    echo json_encode([
        'success' => false,
        'message' => 'Usuário não logado'
    ]);
    exit;
}

try {
    $stmt = $conexao->prepare("
        SELECT 
            id,
            ultimos_digitos,
            bandeira,
            titulr_cartao,
            mes_expiracao,
            ano_expiracao,
            principal
        FROM cartoes_salvos
        WHERE id_usuario = ?
        ORDER BY principal DESC, data_criacao DESC
    ");
    
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $cartoes = [];
    while ($row = $result->fetch_assoc()) {
        $cartoes[] = $row;
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'cartoes' => $cartoes
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
