<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';

$conexao->set_charset("utf8mb4");

// Simular o que acontece quando clica no card
$time1 = "Atletico Mineiro";
$time2 = "Wolfsburg";
$tipo = "CANTOS";

// Remover emoji se houver
$time1 = preg_replace('/[\p{Emoji_Presentation}]/u', '', $time1);
$time2 = preg_replace('/[\p{Emoji_Presentation}]/u', '', $time2);
$time1 = trim($time1);
$time2 = trim($time2);

error_log("🧪 TESTE: time1='$time1', time2='$time2', tipo='$tipo'");

// Query exatamente como a API faz
$sql = "SELECT 
            resultado,
            data_criacao,
            time_1,
            time_2,
            titulo,
            tipo_aposta
        FROM bote 
        WHERE (
            (LOWER(time_1) LIKE CONCAT('%', LOWER('$time1'), '%') OR LOWER(time_2) LIKE CONCAT('%', LOWER('$time1'), '%'))
            AND LOWER(tipo_aposta) LIKE '%CANTOS%'
        )
        ORDER BY data_criacao DESC
        LIMIT 5";

error_log("SQL: $sql");

$resultado = $conexao->query($sql);

$dados = [];
if ($resultado && $resultado->num_rows > 0) {
    while ($row = $resultado->fetch_assoc()) {
        error_log("✅ Encontrado: " . $row['time_1'] . " vs " . $row['time_2']);
        $dados[] = $row;
    }
} else {
    error_log("❌ Nenhum resultado!");
}

echo json_encode([
    'time1_buscado' => $time1,
    'time2_buscado' => $time2,
    'tipo' => $tipo,
    'total' => count($dados),
    'registros' => $dados
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

$conexao->close();
?>
