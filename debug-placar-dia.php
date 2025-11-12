<?php
// ✅ DEBUG obter-placar-dia.php
header('Content-Type: text/html; charset=utf-8');

require_once 'config.php';

$data_hoje = date('Y-m-d');

$query = "SELECT id, titulo, resultado, odds FROM bote 
          WHERE DATE(data_criacao) = '$data_hoje'
          ORDER BY data_criacao DESC";

$resultado = $conexao->query($query);

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'></head><body>";
echo "<h1>DEBUG - Placar do Dia ($data_hoje)</h1>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Título</th><th>Resultado</th><th>Odds</th><th>Lucro Coef</th></tr>";

$total_green = 0;
$total_red = 0;
$total_coef_green = 0;
$total_coef_red = 0;

while ($row = $resultado->fetch_assoc()) {
    $titulo = $row['titulo'];
    $res = $row['resultado'];
    $odds = floatval($row['odds']);
    
    $coef_calc = '';
    if ($res === 'GREEN') {
        $total_green++;
        $coef_g = round($odds - 1, 2);
        $total_coef_green += $coef_g;
        $coef_calc = "+{$coef_g} (GREEN)";
    } elseif ($res === 'RED') {
        $total_red++;
        $total_coef_red += (-1);
        $coef_calc = "-1.00 (RED)";
    }
    
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$titulo}</td>";
    echo "<td style='color: " . ($res === 'GREEN' ? 'green' : 'red') . "'>{$res}</td>";
    echo "<td>{$odds}</td>";
    echo "<td>{$coef_calc}</td>";
    echo "</tr>";
}

echo "</table>";
echo "<br><h2>Resumo:</h2>";
echo "<ul>";
echo "<li>Total GREEN: {$total_green}</li>";
echo "<li>Total RED: {$total_red}</li>";
echo "<li>Total Coef GREEN: {$total_coef_green}</li>";
echo "<li>Total Coef RED: {$total_coef_red}</li>";
echo "<li>Coef Líquido: " . round($total_coef_green + $total_coef_red, 2) . "</li>";
echo "</ul>";

echo "<h2>Com UND = R$ 100:</h2>";
$und = 100;
$resultado_final = round(($total_coef_green + $total_coef_red) * $und, 2);
echo "<p><strong>Valor Final: R$ " . number_format($resultado_final, 2, ',', '.') . "</strong></p>";

echo "<h2>JSON Retornado:</h2>";
echo "<pre>";
$json = shell_exec("curl -s 'http://localhost/gestao/gestao_banca/obter-placar-dia.php' 2>/dev/null || echo 'Erro ao chamar API'");
echo htmlspecialchars($json);
echo "</pre>";

echo "</body></html>";
?>
