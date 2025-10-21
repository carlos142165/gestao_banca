<?php
/**
 * OBTER PLANOS - obter-planos.php
 * ====================================
 * Retorna lista de todos os planos disponíveis em JSON
 */

require_once 'config.php';
header('Content-Type: application/json');

try {
    $stmt = $conexao->prepare("
        SELECT 
            id,
            nome,
            preco_mes,
            preco_ano,
            mentores_limite,
            entradas_diarias,
            icone,
            cor_tema
        FROM planos
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
        'planos' => $planos
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
