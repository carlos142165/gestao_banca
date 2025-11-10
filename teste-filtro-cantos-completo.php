<?php
/**
 * Script de Teste Completo para o Filtro de CANTOS vs GOLS
 * Arquivo: teste-filtro-cantos-completo.php
 */
header('Content-Type: text/html; charset=utf-8');
require_once 'config.php';
$conexao->set_charset("utf8mb4");

echo "<!DOCTYPE html>";
echo "<html lang='pt-br'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>Teste de Filtro de Cantos vs Gols</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }";
echo ".test-box { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }";
echo ".test-box h3 { margin-top: 0; color: #113647; }";
echo ".success { color: green; font-weight: bold; }";
echo ".error { color: red; font-weight: bold; }";
echo ".warning { color: orange; font-weight: bold; }";
echo "table { width: 100%; border-collapse: collapse; margin-top: 10px; }";
echo "th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }";
echo "th { background: #f9f9f9; font-weight: bold; }";
echo ".count { font-size: 18px; font-weight: bold; color: #113647; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<h1>🧪 Teste Completo do Filtro de CANTOS vs GOLS</h1>";

// ============================================================================
// TESTE 1: Verificar se há dados de CANTOS no banco
// ============================================================================
echo "<div class='test-box'>";
echo "<h3>📊 Teste 1: Verificar Dados de CANTOS no Banco</h3>";

$sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN tipo_aposta IS NOT NULL AND tipo_aposta != '' THEN 1 ELSE 0 END) as com_tipo,
    SUM(CASE WHEN LOWER(tipo_aposta) LIKE LOWER('%cantos%') THEN 1 ELSE 0 END) as tipo_cantos,
    SUM(CASE WHEN LOWER(titulo) LIKE LOWER('%cantos%') OR LOWER(titulo) LIKE LOWER('%canto%') THEN 1 ELSE 0 END) as titulo_cantos
FROM bote 
WHERE resultado IS NOT NULL";

$result = $conexao->query($sql);
$row = $result->fetch_assoc();

echo "<table>";
echo "<tr><th>Métrica</th><th>Valor</th><th>Status</th></tr>";
echo "<tr>";
echo "<td>Total de Registros</td>";
echo "<td class='count'>" . $row['total'] . "</td>";
echo "<td>" . ($row['total'] > 0 ? "<span class='success'>✅ OK</span>" : "<span class='error'>❌ SEM DADOS</span>") . "</td>";
echo "</tr>";
echo "<tr>";
echo "<td>Com campo tipo_aposta preenchido</td>";
echo "<td class='count'>" . $row['com_tipo'] . "</td>";
echo "<td>" . ($row['com_tipo'] > 0 ? "<span class='success'>✅ OK</span>" : "<span class='warning'>⚠️ VAZIO</span>") . "</td>";
echo "</tr>";
echo "<tr>";
echo "<td>Tipo_aposta contém CANTOS</td>";
echo "<td class='count'>" . ($row['tipo_cantos'] ?? 0) . "</td>";
echo "<td>" . (($row['tipo_cantos'] ?? 0) > 0 ? "<span class='success'>✅ ENCONTRADOS</span>" : "<span class='warning'>⚠️ NÃO ENCONTRADOS</span>") . "</td>";
echo "</tr>";
echo "<tr>";
echo "<td>Título contém CANTOS/CANTO</td>";
echo "<td class='count'>" . ($row['titulo_cantos'] ?? 0) . "</td>";
echo "<td>" . (($row['titulo_cantos'] ?? 0) > 0 ? "<span class='success'>✅ ENCONTRADOS</span>" : "<span class='warning'>⚠️ NÃO ENCONTRADOS</span>") . "</td>";
echo "</tr>";
echo "</table>";
echo "</div>";

// ============================================================================
// TESTE 2: Mostrar exemplos de CANTOS vs GOLS
// ============================================================================
echo "<div class='test-box'>";
echo "<h3>📋 Teste 2: Exemplos de CANTOS vs GOLS</h3>";

$sql = "SELECT id, titulo, tipo_aposta, time_1, time_2, resultado,
        CASE 
            WHEN LOWER(tipo_aposta) LIKE LOWER('%cantos%') THEN 'tipo_aposta'
            WHEN LOWER(titulo) LIKE LOWER('%cantos%') OR LOWER(titulo) LIKE LOWER('%canto%') THEN 'titulo'
            ELSE 'nenhum'
        END as detectado_por
FROM bote 
WHERE resultado IS NOT NULL
AND (
    LOWER(tipo_aposta) LIKE LOWER('%cantos%')
    OR LOWER(titulo) LIKE LOWER('%cantos%')
    OR LOWER(titulo) LIKE LOWER('%canto%')
)
LIMIT 5";

$result = $conexao->query($sql);
echo "<h4>Exemplos de CANTOS encontrados:</h4>";
echo "<table>";
echo "<tr><th>ID</th><th>Tipo_Aposta</th><th>Título (resumido)</th><th>Detectado por</th><th>Resultado</th></tr>";
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td><strong>" . htmlspecialchars($row['tipo_aposta']) . "</strong></td>";
        echo "<td>" . htmlspecialchars(substr($row['titulo'], 0, 50)) . "...</td>";
        echo "<td><span class='success'>" . $row['detectado_por'] . "</span></td>";
        echo "<td>" . $row['resultado'] . "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='5' style='text-align:center;'><span class='error'>Nenhum exemplo de CANTOS encontrado</span></td></tr>";
}
echo "</table>";

// Exemplos de GOLS
$sql = "SELECT id, titulo, tipo_aposta, time_1, time_2, resultado
FROM bote 
WHERE resultado IS NOT NULL
AND (
    LOWER(tipo_aposta) LIKE LOWER('%gol%')
    OR LOWER(titulo) LIKE LOWER('%gol%')
    OR LOWER(titulo) LIKE LOWER('%gols%')
)
LIMIT 5";

$result = $conexao->query($sql);
echo "<h4>Exemplos de GOLS encontrados:</h4>";
echo "<table>";
echo "<tr><th>ID</th><th>Tipo_Aposta</th><th>Título (resumido)</th><th>Time 1 vs Time 2</th><th>Resultado</th></tr>";
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td><strong>" . htmlspecialchars($row['tipo_aposta']) . "</strong></td>";
        echo "<td>" . htmlspecialchars(substr($row['titulo'], 0, 40)) . "...</td>";
        echo "<td>" . $row['time_1'] . " vs " . $row['time_2'] . "</td>";
        echo "<td>" . $row['resultado'] . "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='5' style='text-align:center;'><span class='error'>Nenhum exemplo de GOLS encontrado</span></td></tr>";
}
echo "</table>";
echo "</div>";

// ============================================================================
// TESTE 3: Testar os filtros SQL que estão sendo usados
// ============================================================================
echo "<div class='test-box'>";
echo "<h3>🔍 Teste 3: Testar Filtros SQL</h3>";

// Filtro de CANTOS
$filtro_cantos = "(
    LOWER(tipo_aposta) LIKE LOWER('%cantos%')
    OR LOWER(tipo_aposta) LIKE LOWER('%canto%')
    OR LOWER(titulo) LIKE LOWER('%cantos%') 
    OR LOWER(titulo) LIKE LOWER('%canto%')
    OR LOWER(titulo) LIKE LOWER('%escanteios%')
    OR LOWER(titulo) LIKE LOWER('%escantei%')
    OR titulo LIKE '%⛳%'
    OR titulo LIKE '%🚩%'
)";

$sql_test_cantos = "SELECT COUNT(*) as total FROM bote 
    WHERE resultado IS NOT NULL 
    AND (LOWER(time_1) LIKE LOWER('%flume%') OR LOWER(time_2) LIKE LOWER('%flume%'))
    AND $filtro_cantos";

$result = $conexao->query($sql_test_cantos);
$row = $result->fetch_assoc();
echo "<p><strong>Filtro de CANTOS para um time específico:</strong></p>";
echo "<p>Registros encontrados: <span class='count'>" . $row['total'] . "</span></p>";

// Filtro de GOLS
$filtro_gols = "(
    LOWER(tipo_aposta) LIKE LOWER('%gol%')
    OR LOWER(titulo) LIKE LOWER('%gol%') 
    OR LOWER(titulo) LIKE LOWER('%gols%')
    OR titulo LIKE '%⚽%'
)";

$sql_test_gols = "SELECT COUNT(*) as total FROM bote 
    WHERE resultado IS NOT NULL 
    AND (LOWER(time_1) LIKE LOWER('%flume%') OR LOWER(time_2) LIKE LOWER('%flume%'))
    AND $filtro_gols";

$result = $conexao->query($sql_test_gols);
$row = $result->fetch_assoc();
echo "<p><strong>Filtro de GOLS para um time específico:</strong></p>";
echo "<p>Registros encontrados: <span class='count'>" . $row['total'] . "</span></p>";

echo "</div>";

// ============================================================================
// TESTE 4: Fazer uma requisição POST simular a chamada da API
// ============================================================================
echo "<div class='test-box'>";
echo "<h3>🌐 Teste 4: Simular Chamada de API</h3>";

$test_data = [
    'time1' => 'Fluminense',
    'time2' => 'Flamengo',
    'tipo' => 'cantos',
    'limite' => 5
];

echo "<p>Dados enviados:</p>";
echo "<pre style='background: #f9f9f9; padding: 10px; border-radius: 4px;'>";
echo json_encode($test_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "</pre>";

// Fazer a chamada interna
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://' . $_SERVER['HTTP_HOST'] . '/gestao/gestao_banca/api/obter-historico-resultados.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
curl_close($ch);

$response_data = json_decode($response, true);
echo "<p>Resposta da API:</p>";
echo "<pre style='background: #f9f9f9; padding: 10px; border-radius: 4px; max-height: 300px; overflow-y: auto;'>";
echo json_encode($response_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "</pre>";

echo "<p><strong>Resumo da resposta:</strong></p>";
if ($response_data['success']) {
    echo "<p><span class='success'>✅ API retornou sucesso</span></p>";
    echo "<p>Time 1 (Fluminense): <span class='count'>" . $response_data['total_time1'] . " jogos</span></p>";
    echo "<p>Time 2 (Flamengo): <span class='count'>" . $response_data['total_time2'] . " jogos</span></p>";
    echo "<p>Tipo filtrado: <strong>" . $response_data['tipo'] . "</strong></p>";
} else {
    echo "<p><span class='error'>❌ API retornou erro: " . $response_data['error'] . "</span></p>";
}

echo "</div>";

echo "</body>";
echo "</html>";

$conexao->close();
?>
