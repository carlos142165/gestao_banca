<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';

$conexao->set_charset("utf8mb4");

// Teste com os times que temos no banco
$time1_test = "Internacional"; // Sem emoji
$tipo_test = "CANTOS";

error_log("🧪 TESTE DIRETO: Procurando time1='$time1_test' com tipo='$tipo_test'");

$sql_test = "SELECT resultado, data_criacao, time_1, time_2, titulo, tipo_aposta
            FROM bote 
            WHERE (
                (LOWER(time_1) LIKE LOWER('%$time1_test%') OR LOWER(time_2) LIKE LOWER('%$time1_test%'))
                AND (resultado IS NOT NULL)
                AND LOWER(tipo_aposta) LIKE '%CANTOS%'
            )
            ORDER BY data_criacao DESC
            LIMIT 5";

error_log("🔍 SQL: $sql_test");

$resultado = $conexao->query($sql_test);

$dados = [];
if ($resultado && $resultado->num_rows > 0) {
    while ($row = $resultado->fetch_assoc()) {
        error_log("✅ Encontrado: " . json_encode($row));
        $dados[] = $row;
    }
} else {
    error_log("❌ Nenhum resultado encontrado!");
}

echo json_encode([
    'sql' => $sql_test,
    'time1_test' => $time1_test,
    'tipo_test' => $tipo_test,
    'total' => count($dados),
    'registros' => $dados
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

$conexao->close();
?>
