
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
    // Consulta para somar depósitos e saques
    $stmt = $conexao->prepare("SELECT 
        COALESCE(SUM(deposito), 0) AS total_depositos,
        COALESCE(SUM(saque), 0) AS total_saques
        FROM controle
        WHERE id_usuario = ?");

    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    $total_depositos = floatval($result['total_depositos']);
    $total_saques = floatval($result['total_saques']);
    // Busca lucro em valor_mentores: soma(valor_green) - soma(valor_red)
    $stmt2 = $conexao->prepare("SELECT COALESCE(SUM(valor_green),0) AS total_green, COALESCE(SUM(valor_red),0) AS total_red FROM valor_mentores WHERE id_usuario = ?");
    $stmt2->bind_param("i", $id_usuario);
    $stmt2->execute();
    $res2 = $stmt2->get_result()->fetch_assoc();
    $total_green = floatval($res2['total_green']);
    $total_red = floatval($res2['total_red']);
    $lucro_total = $total_green - $total_red;
    $stmt2->close();

    // saldo = depósitos - saques + lucro
    $saldo = $total_depositos - $total_saques + $lucro_total;

    $tem_deposito = $saldo > 0;

    echo json_encode([
        'success' => true,
        'tem_deposito' => $tem_deposito,
        'total_depositos' => $total_depositos,
        'total_saques' => $total_saques,
    'saldo' => $saldo,
    'lucro_total' => $lucro_total,
    'lucro_formatado' => 'R$ ' . number_format($lucro_total, 2, ',', '.'),
        'message' => $tem_deposito 
            ? 'Usuário possui saldo disponível' 
            : 'Usuário não possui saldo suficiente'
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
