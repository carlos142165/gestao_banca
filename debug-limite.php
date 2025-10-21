<?php
/**
 * DEBUG: Verificar Limite e Status do Usuário
 * Para diagnosticar por que o modal de planos não está abrindo
 */

require_once 'config.php';
require_once 'carregar_sessao.php';

header('Content-Type: application/json; charset=utf-8');

// Verificar se usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([
        'erro' => 'Usuário não logado',
        'session' => $_SESSION
    ]);
    exit;
}

$id_usuario = $_SESSION['usuario_id'];

try {
    // 1. Obter dados do usuário
    $stmt = $conexao->prepare("SELECT id, email, id_plano, status_assinatura FROM usuarios WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result_user = $stmt->get_result();
    $usuario = $result_user->fetch_assoc();
    $stmt->close();

    // 2. Obter dados do plano
    $id_plano = $usuario['id_plano'] ?? 1;
    $stmt = $conexao->prepare("SELECT * FROM planos WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id_plano);
    $stmt->execute();
    $result_plano = $stmt->get_result();
    $plano = $result_plano->fetch_assoc();
    $stmt->close();

    // 3. Contar mentores
    $stmt = $conexao->prepare("SELECT COUNT(*) as total FROM mentores WHERE id_usuario = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result_mentores = $stmt->get_result();
    $mentores = $result_mentores->fetch_assoc();
    $stmt->close();

    // 4. Contar entradas de hoje
    $stmt = $conexao->prepare("SELECT COUNT(*) as total FROM valor_mentores WHERE id_usuario = ? AND DATE(data_criacao) = CURDATE()");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result_entradas = $stmt->get_result();
    $entradas = $result_entradas->fetch_assoc();
    $stmt->close();

    echo json_encode([
        'sucesso' => true,
        'usuario' => $usuario,
        'plano' => $plano,
        'mentores' => [
            'cadastrados' => intval($mentores['total']),
            'limite' => intval($plano['mentores_limite'] ?? 1),
            'pode_adicionar' => (intval($mentores['total']) < intval($plano['mentores_limite']))
        ],
        'entradas' => [
            'cadastradas' => intval($entradas['total']),
            'limite' => intval($plano['entradas_diarias'] ?? 3),
            'pode_adicionar' => (intval($entradas['total']) < intval($plano['entradas_diarias']))
        ],
        'mensagem' => 'Dados do usuário carregados com sucesso'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'erro' => true,
        'mensagem' => $e->getMessage()
    ]);
}
?>
