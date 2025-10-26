<?php
/**
 * ATUALIZAR VALORES DOS PLANOS
 * ====================================
 * Script para atualizar os valores conforme solicitado
 */

require_once 'config.php';
header('Content-Type: application/json');

try {
    // Valores a serem atualizados
    // MÊS:
    // - Prata: R$ 15,99
    // - Ouro: R$ 29,99
    // - Diamante: R$ 49,99
    
    // ANO ECONOMIZE:
    // - Prata: R$ 9,99
    // - Ouro: R$ 19,99
    // - Diamante: R$ 39,99

    // Atualizar PRATA (id 2)
    $stmt = $conexao->prepare("UPDATE planos SET preco_mes = 15.99, preco_ano = 9.99 WHERE id = 2");
    if (!$stmt) {
        throw new Exception("Erro ao preparar statement PRATA: " . $conexao->error);
    }
    $stmt->execute();
    $stmt->close();
    echo "✅ Plano PRATA atualizado\n";

    // Atualizar OURO (id 3)
    $stmt = $conexao->prepare("UPDATE planos SET preco_mes = 29.99, preco_ano = 19.99 WHERE id = 3");
    if (!$stmt) {
        throw new Exception("Erro ao preparar statement OURO: " . $conexao->error);
    }
    $stmt->execute();
    $stmt->close();
    echo "✅ Plano OURO atualizado\n";

    // Atualizar DIAMANTE (id 4)
    $stmt = $conexao->prepare("UPDATE planos SET preco_mes = 49.99, preco_ano = 39.99 WHERE id = 4");
    if (!$stmt) {
        throw new Exception("Erro ao preparar statement DIAMANTE: " . $conexao->error);
    }
    $stmt->execute();
    $stmt->close();
    echo "✅ Plano DIAMANTE atualizado\n";

    // Recuperar planos atualizados para confirmação
    $stmt = $conexao->prepare("
        SELECT id, nome, preco_mes, preco_ano 
        FROM planos 
        WHERE id IN (2, 3, 4)
        ORDER BY id ASC
    ");
    
    if (!$stmt) {
        throw new Exception($conexao->error);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $planos = [];
    while ($row = $result->fetch_assoc()) {
        $planos[] = $row;
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Planos atualizados com sucesso!',
        'planos_atualizados' => $planos
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro: ' . $e->getMessage()
    ]);
}
?>
