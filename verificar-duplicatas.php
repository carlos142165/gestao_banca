<?php
// Verificar registros duplicados no banco
require_once 'config.php';

$conexao->set_charset("utf8mb4");

// Buscar todos os registros Palmeiras x Santos agrupados por data
$sql = "SELECT 
    data_criacao,
    time_1,
    time_2,
    titulo,
    tipo_aposta,
    valor_over,
    resultado,
    COUNT(*) as quantidade
FROM bote
WHERE (time_1 LIKE '%Palmeiras%' OR time_1 LIKE '%Santos%')
  AND (time_2 LIKE '%Palmeiras%' OR time_2 LIKE '%Santos%')
GROUP BY data_criacao, time_1, time_2, titulo
ORDER BY data_criacao DESC
LIMIT 50";

$resultado = $conexao->query($sql);

echo "<h1>🔍 Análise de Duplicatas - Palmeiras x Santos</h1>";
echo "<table border='1' cellpadding='10'>";
echo "<tr>
    <th>Data</th>
    <th>Time 1</th>
    <th>Time 2</th>
    <th>Título</th>
    <th>Tipo Aposta</th>
    <th>Valor Over</th>
    <th>Resultado</th>
    <th>Qtd</th>
</tr>";

while ($row = $resultado->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['data_criacao'] . "</td>";
    echo "<td>" . $row['time_1'] . "</td>";
    echo "<td>" . $row['time_2'] . "</td>";
    echo "<td>" . htmlspecialchars($row['titulo']) . "</td>";
    echo "<td>" . $row['tipo_aposta'] . "</td>";
    echo "<td>" . $row['valor_over'] . "</td>";
    echo "<td>" . $row['resultado'] . "</td>";
    echo "<td style='background: #ffff00; font-weight: bold;'>" . $row['quantidade'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Agora buscar por data específica para ver estrutura
echo "<hr>";
echo "<h2>📊 Breakdown por Data (últimas 5 datas com múltiplos registros)</h2>";

$sql2 = "SELECT 
    DATE(data_criacao) as data,
    COUNT(*) as total_registros,
    GROUP_CONCAT(DISTINCT titulo SEPARATOR ' | ') as titulos,
    GROUP_CONCAT(DISTINCT valor_over SEPARATOR ' | ') as overs
FROM bote
WHERE (time_1 LIKE '%Palmeiras%' OR time_1 LIKE '%Santos%')
  AND (time_2 LIKE '%Palmeiras%' OR time_2 LIKE '%Santos%')
GROUP BY DATE(data_criacao)
HAVING total_registros > 1
ORDER BY data DESC
LIMIT 5";

$resultado2 = $conexao->query($sql2);

echo "<table border='1' cellpadding='10'>";
echo "<tr>
    <th>Data</th>
    <th>Total de Registros</th>
    <th>Títulos</th>
    <th>OVERs</th>
</tr>";

while ($row = $resultado2->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['data'] . "</td>";
    echo "<td style='background: #ffff00; font-weight: bold;'>" . $row['total_registros'] . "</td>";
    echo "<td>" . htmlspecialchars($row['titulos']) . "</td>";
    echo "<td style='background: #ffaaaa;'>" . $row['overs'] . "</td>";
    echo "</tr>";
}
echo "</table>";

$conexao->close();
?>
