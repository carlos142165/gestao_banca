<?php
/**
 * OBTER DADOS DO USUÁRIO - obter-dados-usuario.php
 * ================================================
 * Retorna dados da assinatura e plano atual do usuário logado
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

try {
    // Buscar dados do usuário com plano
    $stmt = $conexao->prepare("
        SELECT 
            u.id,
            u.nome,
            u.email,
            u.id_plano,
            u.status_assinatura,
            u.data_inicio_assinatura,
            u.data_fim_assinatura,
            u.tipo_ciclo,
            u.cartao_salvo,
            u.ultimos_4_digitos,
            u.bandeira_cartao,
            p.nome as nome_plano,
            p.mentores_limite,
            p.entradas_diarias
        FROM usuarios u
        LEFT JOIN planos p ON u.id_plano = p.id
        WHERE u.id = ?
    ");
    
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Usuário não encontrado');
    }
    
    $usuario = $result->fetch_assoc();
    $stmt->close();
    
    // Verificar se plano expirou
    $plano_ativo = true;
    if (!is_null($usuario['data_fim_assinatura'])) {
        $plano_ativo = strtotime($usuario['data_fim_assinatura']) > time();
    }
    
    echo json_encode([
        'success' => true,
        'usuario' => array_merge($usuario, [
            'plano_ativo' => $plano_ativo,
            'dias_restantes' => $plano_ativo && $usuario['data_fim_assinatura'] 
                ? ceil((strtotime($usuario['data_fim_assinatura']) - time()) / 86400)
                : 0
        ])
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
