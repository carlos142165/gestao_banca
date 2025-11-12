<?php
// ✅ DEBUG - Mostrar todos os registros do dia
header('Content-Type: text/html; charset=utf-8');

require_once 'config.php';

$data_hoje = date('Y-m-d');

$query = "SELECT id, titulo, tipo_aposta, resultado, odds, data_criacao FROM bote 
          WHERE DATE(data_criacao) = '$data_hoje'
          ORDER BY data_criacao DESC";

$resultado = $conexao->query($query);

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'>";
echo "<style>";
echo "body { font-family: Arial; background: #f5f5f5; padding: 20px; }";
echo "table { border-collapse: collapse; width: 100%; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }";
echo "th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }";
echo "th { background: #113647; color: white; font-weight: bold; }";
echo "tr:hover { background: #f9f9f9; }";
echo ".green { background: #c8e6c9; color: #2e7d32; font-weight: bold; }";
echo ".red { background: #ffcdd2; color: #c62828; font-weight: bold; }";
echo "h1 { color: #113647; }";
echo "h2 { margin-top: 30px; color: #555; }";
echo ".summary { background: white; padding: 15px; border-radius: 5px; margin: 20px 0; }";
echo ".summary-item { margin: 5px 0; font-size: 16px; }";
echo "</style>";
echo "</head><body>";

echo "<h1>📊 DEBUG - Registros do dia ($data_hoje)</h1>";

$rows = [];
$count_green = 0;
$count_red = 0;
$odds_green = [];
$odds_red = [];

while ($row = $resultado->fetch_assoc()) {
    $rows[] = $row;
    if ($row['resultado'] === 'GREEN') {
        $count_green++;
        $odds_green[] = floatval($row['odds']);
    } elseif ($row['resultado'] === 'RED') {
        $count_red++;
        $odds_red[] = floatval($row['odds']);
    }
}

// Tabela
echo "<table>";
echo "<tr><th>#</th><th>ID</th><th>Tipo</th><th>Resultado</th><th>Odds</th><th>Criado em</th><th>Coef</th></tr>";

foreach ($rows as $idx => $row) {
    $res_class = $row['resultado'] === 'GREEN' ? 'green' : 'red';
    $coef = '';
    if ($row['resultado'] === 'GREEN') {
        $coef = round(floatval($row['odds']) - 1, 4);
    } elseif ($row['resultado'] === 'RED') {
        $coef = '-1.0000';
    }
    
    echo "<tr>";
    echo "<td>" . ($idx + 1) . "</td>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['tipo_aposta']}</td>";
    echo "<td class='$res_class'>{$row['resultado']}</td>";
    echo "<td>{$row['odds']}</td>";
    echo "<td>" . substr($row['data_criacao'], 11, 8) . "</td>";
    echo "<td>{$coef}</td>";
    echo "</tr>";
}

echo "</table>";

// Resumo
echo "<div class='summary'>";
echo "<h2>📈 Resumo do Dia</h2>";
echo "<div class='summary-item'>✅ Total GREEN: <strong>{$count_green}</strong>";
if ($count_green > 0) {
    echo " | Odds: " . implode(", ", array_map(fn($x) => number_format($x, 2), $odds_green));
}
echo "</div>";
echo "<div class='summary-item'>❌ Total RED: <strong>{$count_red}</strong>";
if ($count_red > 0) {
    echo " | Odds: " . implode(", ", array_map(fn($x) => number_format($x, 2), $odds_red));
}
echo "</div>";

// Cálculo de coeficientes
$sum_coef_green = 0;
$sum_coef_red = 0;

foreach ($odds_green as $odds) {
    $sum_coef_green += ($odds - 1);
}

$sum_coef_red = $count_red * (-1);

echo "<h2>🔢 Cálculo de Coeficientes</h2>";
echo "<div class='summary-item'>Coef GREEN: " . number_format($sum_coef_green, 4) . "</div>";
echo "<div class='summary-item'>Coef RED: " . number_format($sum_coef_red, 4) . "</div>";
echo "<div class='summary-item'>Coef Total: <strong>" . number_format($sum_coef_green + $sum_coef_red, 4) . "</strong></div>";

echo "<h2>💰 Valor Final (UND = R$ 100)</h2>";
$und = 100;
$valor_final = ($sum_coef_green + $sum_coef_red) * $und;
echo "<div class='summary-item'>Cálculo: ({$sum_coef_green} + {$sum_coef_red}) × {$und} = <strong style='font-size: 20px; color: " . ($valor_final >= 0 ? 'green' : 'red') . ";'>R$ " . number_format($valor_final, 2, ',', '.') . "</strong></div>";

echo "</div>";
echo "</body></html>";
?>
