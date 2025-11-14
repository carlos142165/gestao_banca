<?php
/**
 * ✅ API: Buscar Times por Filtro
 * 
 * GET: /api/buscar-times.php?q=flam
 * 
 * Retorna lista de times que começam com a query
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config.php';

$conexao = obterConexao();

if (!$conexao) {
    http_response_code(500);
    die(json_encode(['success' => false, 'error' => 'Erro ao conectar ao banco']));
}

// Pegar query
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($q) < 1) {
    echo json_encode(['success' => true, 'times' => []]);
    exit();
}

// ✅ BUSCAR TIMES ÚNICOS DAS COLUNAS time_1 E time_2
// Usamos UNION para combinar resultados de ambas as colunas
// Case-insensitive search usando LOWER()
$query = "
    SELECT DISTINCT time_1 as nome_time
    FROM bote
    WHERE LOWER(time_1) LIKE LOWER(?) AND time_1 IS NOT NULL AND time_1 != ''
    
    UNION
    
    SELECT DISTINCT time_2 as nome_time
    FROM bote
    WHERE LOWER(time_2) LIKE LOWER(?) AND time_2 IS NOT NULL AND time_2 != ''
    
    ORDER BY nome_time ASC
    LIMIT 15
";

$stmt = $conexao->prepare($query);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro ao preparar query: ' . $conexao->error]);
    exit();
}

// Bind parameters - adicionar % antes e depois para LIKE (busca em qualquer posição)
$pattern = '%' . $q . '%';
$stmt->bind_param("ss", $pattern, $pattern);
$stmt->execute();

$result = $stmt->get_result();
$times = [];

while ($row = $result->fetch_assoc()) {
    $times[] = [
        'nome' => $row['nome_time'],
        'label' => $row['nome_time']  // Para display
    ];
}

$stmt->close();
$conexao->close();

http_response_code(200);
echo json_encode([
    'success' => true,
    'times' => $times,
    'query' => $q,
    'total' => count($times)
]);
?>
