<?php
// Teste direto da API com filtro OVER
require_once 'config.php';

$conexao->set_charset("utf8mb4");

echo "<h1>🧪 Teste de Filtro OVER</h1>";

// Teste 1: SEM filtro OVER - deve retornar ambos
echo "<h2>Teste 1: SEM Filtro OVER (deve retornar TODOS)</h2>";
$sql1 = "SELECT titulo, tipo_aposta, valor_over, resultado, data_criacao
         FROM bote
         WHERE (time_1 LIKE '%Palmeiras%' OR time_2 LIKE '%Palmeiras%')
           AND (time_1 LIKE '%Santos%' OR time_2 LIKE '%Santos%')
           AND LOWER(tipo_aposta) LIKE LOWER('%gol%')
         ORDER BY data_criacao DESC
         LIMIT 20";

$resultado1 = $conexao->query($sql1);
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Título</th><th>Tipo</th><th>OVER</th><th>Resultado</th><th>Data</th></tr>";
while ($row = $resultado1->fetch_assoc()) {
    // Extrair OVER do título
    preg_match_all('/\+(\d+\.?\d*)\s*(?:⚽|⛳|gol|canto|gols|cantos)/i', strtolower($row['titulo']), $matches);
    $overs = $matches[1] ?? [];
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['titulo']) . "</td>";
    echo "<td>" . $row['tipo_aposta'] . "</td>";
    echo "<td>" . json_encode($overs) . " (db: " . $row['valor_over'] . ")</td>";
    echo "<td>" . $row['resultado'] . "</td>";
    echo "<td>" . $row['data_criacao'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Teste 2: COM filtro OVER = "1" - deve retornar APENAS +1
echo "<h2>Teste 2: COM Filtro OVER = '1' (deve retornar APENAS +1)</h2>";
$sql2 = "SELECT titulo, tipo_aposta, valor_over, resultado, data_criacao
         FROM bote
         WHERE (time_1 LIKE '%Palmeiras%' OR time_2 LIKE '%Palmeiras%')
           AND (time_1 LIKE '%Santos%' OR time_2 LIKE '%Santos%')
           AND LOWER(tipo_aposta) LIKE LOWER('%gol%')
         ORDER BY data_criacao DESC
         LIMIT 20";

$resultado2 = $conexao->query($sql2);
$filtro_over = "1";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Título</th><th>OVER Encontrado</th><th>Corresponde?</th><th>Resultado</th><th>Data</th></tr>";
while ($row = $resultado2->fetch_assoc()) {
    preg_match_all('/\+(\d+\.?\d*)\s*(?:⚽|⛳|gol|canto|gols|cantos)/i', strtolower($row['titulo']), $matches);
    $overs = $matches[1] ?? [];
    
    // Simular a validação PHP
    $deve_incluir = false;
    if (!empty($overs)) {
        foreach ($overs as $over) {
            if ((string)$over === (string)$filtro_over) {
                $deve_incluir = true;
                break;
            }
        }
    } elseif ((string)$row['valor_over'] === (string)$filtro_over) {
        $deve_incluir = true;
    }
    
    $status = $deve_incluir ? "✅ SIM" : "❌ NÃO";
    $bgcolor = $deve_incluir ? "#aaffaa" : "#ffaaaa";
    
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['titulo']) . "</td>";
    echo "<td>" . json_encode($overs) . "</td>";
    echo "<td style='background: $bgcolor; font-weight: bold;'>" . $status . "</td>";
    echo "<td>" . $row['resultado'] . "</td>";
    echo "<td>" . $row['data_criacao'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Teste 3: COM filtro OVER = "0.5" - deve retornar APENAS +0.5
echo "<h2>Teste 3: COM Filtro OVER = '0.5' (deve retornar APENAS +0.5)</h2>";
$resultado3 = $conexao->query($sql2);
$filtro_over = "0.5";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Título</th><th>OVER Encontrado</th><th>Corresponde?</th><th>Resultado</th><th>Data</th></tr>";
while ($row = $resultado3->fetch_assoc()) {
    preg_match_all('/\+(\d+\.?\d*)\s*(?:⚽|⛳|gol|canto|gols|cantos)/i', strtolower($row['titulo']), $matches);
    $overs = $matches[1] ?? [];
    
    $deve_incluir = false;
    if (!empty($overs)) {
        foreach ($overs as $over) {
            if ((string)$over === (string)$filtro_over) {
                $deve_incluir = true;
                break;
            }
        }
    } elseif ((string)$row['valor_over'] === (string)$filtro_over) {
        $deve_incluir = true;
    }
    
    $status = $deve_incluir ? "✅ SIM" : "❌ NÃO";
    $bgcolor = $deve_incluir ? "#aaffaa" : "#ffaaaa";
    
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['titulo']) . "</td>";
    echo "<td>" . json_encode($overs) . "</td>";
    echo "<td style='background: $bgcolor; font-weight: bold;'>" . $status . "</td>";
    echo "<td>" . $row['resultado'] . "</td>";
    echo "<td>" . $row['data_criacao'] . "</td>";
    echo "</tr>";
}
echo "</table>";

$conexao->close();
?>
