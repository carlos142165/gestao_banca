<?php
// ✅ DEBUG RAW - Mostrar dados EXATOS do banco
header('Content-Type: text/html; charset=utf-8');

require_once 'config.php';

$data_hoje = date('Y-m-d');

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><style>";
echo "body { font-family: monospace; background: #1e1e1e; color: #00ff00; padding: 20px; }";
echo "pre { background: #000; padding: 15px; border: 1px solid #00ff00; overflow-x: auto; }";
echo "h1 { color: #00ff00; }";
echo ".section { margin: 30px 0; border: 1px solid #00ff00; padding: 15px; }";
echo "</style></head><body>";

echo "<h1>🔍 DEBUG RAW - Dados Brutos do Banco</h1>";

// Query sem processamento
$query = "SELECT titulo, tipo_aposta, resultado, odds FROM bote 
          WHERE DATE(data_criacao) = '$data_hoje'
          ORDER BY data_criacao DESC";

$resultado = $conexao->query($query);

echo "<div class='section'>";
echo "<h2>Todos os registros do dia:</h2>";
echo "<pre>";

$count = 0;
while ($row = $resultado->fetch_assoc()) {
    $count++;
    echo "[{$count}] " . json_encode($row, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
}

echo "</pre>";
echo "</div>";

// Verificar especificamente dados de CANTOS com GREEN
echo "<div class='section'>";
echo "<h2>CANTOS com GREEN:</h2>";
echo "<pre>";

$query2 = "SELECT id, titulo, tipo_aposta, resultado, odds FROM bote 
          WHERE DATE(data_criacao) = '$data_hoje'
          AND resultado = 'GREEN'
          AND (tipo_aposta LIKE '%CANTO%' OR titulo LIKE '%CANTO%')";

$resultado2 = $conexao->query($query2);

$contador = 0;
while ($row = $resultado2->fetch_assoc()) {
    $contador++;
    $coef = floatval($row['odds']) - 1;
    echo "[{$contador}] ID:{$row['id']} | Odds: {$row['odds']} | Coef: {$coef} | Cálculo: ({$row['odds']} - 1) × 100 = " . ($coef * 100) . "\n";
}

echo "</pre>";
echo "</div>";

// Fazer uma chamada à API e mostrar resultado bruto
echo "<div class='section'>";
echo "<h2>Resposta da API obter-placar-dia.php:</h2>";
echo "<pre>";

$json = shell_exec("curl -s 'http://localhost/gestao/gestao_banca/obter-placar-dia.php' 2>/dev/null");
echo htmlspecialchars($json);

echo "</pre>";
echo "</div>";

echo "</body></html>";
?>
