<?php
/**
 * Diagnóstico: Investigar por que tipo_aposta está errado para CANTOS
 */
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';

$conexao->set_charset("utf8mb4");

echo "<!DOCTYPE html>";
echo "<html>";
echo "<head><meta charset='UTF-8'><title>Debug tipo_aposta</title>";
echo "<style>";
echo "body { font-family: Arial; margin: 20px; background: #f5f5f5; }";
echo ".test-box { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }";
echo ".error { color: red; }";
echo ".success { color: green; }";
echo "table { width: 100%; border-collapse: collapse; margin-top: 10px; }";
echo "th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }";
echo "th { background: #f9f9f9; }";
echo ".highlight-yellow { background: #fff9c4; }";
echo ".highlight-red { background: #ffebee; }";
echo ".highlight-green { background: #e8f5e9; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<h1>🔍 Debug: Investigar tipo_aposta para CANTOS</h1>";

// ========== TESTE 1: Verificar registros de CANTOS ==========
echo "<div class='test-box'>";
echo "<h2>Teste 1: Registros que DEVERIAM ser CANTOS</h2>";

$sql = "SELECT 
    id,
    titulo,
    tipo_aposta,
    CASE 
        WHEN titulo LIKE '%⛳%' THEN 'Tem emoji ⛳'
        WHEN titulo LIKE '%🚩%' THEN 'Tem emoji 🚩'
        WHEN LOWER(titulo) LIKE '%cantos%' THEN 'Contém cantos'
        WHEN LOWER(titulo) LIKE '%canto%' THEN 'Contém canto'
        WHEN LOWER(titulo) LIKE '%escanteio%' THEN 'Contém escanteio'
        ELSE 'Outro'
    END as motivo
FROM bote 
WHERE 
    (titulo LIKE '%⛳%' OR titulo LIKE '%🚩%' OR LOWER(titulo) LIKE '%cantos%' OR LOWER(titulo) LIKE '%escanteio%')
LIMIT 10";

$result = $conexao->query($sql);
echo "<table>";
echo "<tr><th>ID</th><th>Título (primeiros 50 chars)</th><th>tipo_aposta (no banco)</th><th>Deveria ser</th><th>Status</th></tr>";

while ($row = $result->fetch_assoc()) {
    $tipo_banco = $row['tipo_aposta'];
    $deveria_ser = 'CANTOS';
    $status = (strtolower($tipo_banco) === strtolower($deveria_ser)) ? '<span class="success">✅ OK</span>' : '<span class="error">❌ ERRADO</span>';
    
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . htmlspecialchars(substr($row['titulo'], 0, 50)) . "</td>";
    echo "<td class='" . (strtolower($tipo_banco) !== 'cantos' ? 'highlight-red' : 'highlight-green') . "'><strong>" . htmlspecialchars($tipo_banco) . "</strong></td>";
    echo "<td>" . $deveria_ser . "</td>";
    echo "<td>" . $status . "</td>";
    echo "</tr>";
}
echo "</table>";
echo "</div>";

// ========== TESTE 2: Contar tipo_aposta errados ==========
echo "<div class='test-box'>";
echo "<h2>Teste 2: Contagem de Erros</h2>";

$sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN LOWER(tipo_aposta) = 'cantos' THEN 1 ELSE 0 END) as corretos,
    SUM(CASE WHEN LOWER(tipo_aposta) != 'cantos' THEN 1 ELSE 0 END) as incorretos
FROM bote 
WHERE (titulo LIKE '%⛳%' OR titulo LIKE '%🚩%' OR LOWER(titulo) LIKE '%cantos%' OR LOWER(titulo) LIKE '%escanteio%')";

$result = $conexao->query($sql);
$row = $result->fetch_assoc();

echo "<p>Total de registros que parecem ser CANTOS: <strong>" . $row['total'] . "</strong></p>";
echo "<p>Com tipo_aposta = 'CANTOS': <span class='success'><strong>" . ($row['corretos'] ?? 0) . "</strong></span></p>";
echo "<p>Com tipo_aposta ERRADO: <span class='error'><strong>" . ($row['incorretos'] ?? 0) . "</strong></span></p>";

if (($row['incorretos'] ?? 0) > 0) {
    echo "<p class='error'>⚠️ PROBLEMA ENCONTRADO: Há registros com tipo_aposta errado!</p>";
}
echo "</div>";

// ========== TESTE 3: Ver valores de tipo_aposta para CANTOS ==========
echo "<div class='test-box'>";
echo "<h2>Teste 3: Quais são os valores de tipo_aposta?</h2>";

$sql = "SELECT 
    DISTINCT tipo_aposta,
    COUNT(*) as quantidade
FROM bote 
WHERE (titulo LIKE '%⛳%' OR titulo LIKE '%🚩%' OR LOWER(titulo) LIKE '%cantos%' OR LOWER(titulo) LIKE '%escanteio%')
GROUP BY tipo_aposta
ORDER BY quantidade DESC";

$result = $conexao->query($sql);
echo "<table>";
echo "<tr><th>tipo_aposta (no banco)</th><th>Quantidade</th><th>Esperado</th><th>Status</th></tr>";

while ($row = $result->fetch_assoc()) {
    $eh_cantos = (strtolower($row['tipo_aposta']) === 'cantos');
    $classe = $eh_cantos ? 'highlight-green' : 'highlight-red';
    $status = $eh_cantos ? '✅ OK' : '❌ ERRADO';
    
    echo "<tr class='$classe'>";
    echo "<td><strong>" . htmlspecialchars($row['tipo_aposta']) . "</strong></td>";
    echo "<td>" . $row['quantidade'] . "</td>";
    echo "<td>CANTOS</td>";
    echo "<td>" . $status . "</td>";
    echo "</tr>";
}
echo "</table>";
echo "</div>";

// ========== TESTE 4: Comparar com GOLS ==========
echo "<div class='test-box'>";
echo "<h2>Teste 4: Comparação CANTOS vs GOLS</h2>";

$sql = "SELECT 
    'CANTOS' as tipo,
    COUNT(*) as total,
    SUM(CASE WHEN resultado = 'GREEN' THEN 1 ELSE 0 END) as green,
    SUM(CASE WHEN resultado = 'RED' THEN 1 ELSE 0 END) as red
FROM bote 
WHERE (titulo LIKE '%⛳%' OR titulo LIKE '%🚩%' OR LOWER(titulo) LIKE '%cantos%' OR LOWER(titulo) LIKE '%escanteio%')
UNION ALL
SELECT 
    'GOLS' as tipo,
    COUNT(*) as total,
    SUM(CASE WHEN resultado = 'GREEN' THEN 1 ELSE 0 END) as green,
    SUM(CASE WHEN resultado = 'RED' THEN 1 ELSE 0 END) as red
FROM bote 
WHERE (titulo LIKE '%⚽%' OR LOWER(titulo) LIKE '%gol%' OR LOWER(titulo) LIKE '%gols%')";

$result = $conexao->query($sql);
echo "<table>";
echo "<tr><th>Tipo</th><th>Total</th><th>GREEN</th><th>RED</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td><strong>" . $row['tipo'] . "</strong></td>";
    echo "<td>" . $row['total'] . "</td>";
    echo "<td class='highlight-green'>" . $row['green'] . "</td>";
    echo "<td class='highlight-red'>" . $row['red'] . "</td>";
    echo "</tr>";
}
echo "</table>";
echo "</div>";

echo "</body></html>";

$conexao->close();
?>
