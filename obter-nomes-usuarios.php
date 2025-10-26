<?php
require_once 'config.php';
require_once 'carregar_sessao.php';

header('Content-Type: application/json');

$ids = $_POST['ids'] ?? null;

if (!$ids) {
    echo json_encode([]);
    exit;
}

// Decodificar JSON se necessário
if (is_string($ids)) {
    $ids = json_decode($ids, true);
}

if (!is_array($ids) || empty($ids)) {
    echo json_encode([]);
    exit;
}

try {
    // Montar lista de IDs para query
    $idsStr = implode(',', array_map('intval', $ids));
    
    $result = $conexao->query("
        SELECT id, nome FROM usuarios 
        WHERE id IN ($idsStr)
        ORDER BY nome ASC
    ");
    
    $usuarios = [];
    while ($row = $result->fetch_assoc()) {
        $usuarios[] = [
            'id' => (int)$row['id'],
            'nome' => $row['nome']
        ];
    }
    
    echo json_encode($usuarios);
    
} catch (Exception $e) {
    echo json_encode([]);
}
?>
