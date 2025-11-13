<?php
// ✅ DEBUG CANTOS - Verificar dados específicos
header('Content-Type: text/html; charset=utf-8');

require_once 'config.php';

$data_hoje = date('Y-m-d');

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'>";
echo "<style>";
echo "body { font-family: Arial; background: #f5f5f5; padding: 20px; }";
echo "table { border-collapse: collapse; width: 100%; background: white; }";
echo "th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }";
echo "th { background: #113647; color: white; font-weight: bold; }";
echo ".green { color: #2e7d32; font-weight: bold; }";
echo ".red { color: #c62828; font-weight: bold; }";
echo "h1 { color: #113647; }";
echo ".info { background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 20px 0; }";
echo "</style>";
echo "</head><body>";

echo "<h1>🔍 DEBUG - CANTOS Especificamente</h1>";

// Buscar APENAS CANTOS do dia
$query = "SELECT id, titulo, tipo_aposta, resultado, odds, data_criacao FROM bote 
          WHERE DATE(data_criacao) = '$data_hoje'
          AND (tipo_aposta LIKE '%CANTO%' OR titulo LIKE '%CANTO%')
          ORDER BY data_criacao DESC";

$resultado = $conexao->query($query);

echo "<h2>Registros de CANTOS:</h2>";
echo "<table>";
echo "<tr><th>ID</th><th>Tipo Aposta</th><th>Título</th><th>Resultado</th><th>Odds</th><th>Horário</th><th>Coef Calculado</th></tr>";

$total_cantos_green = 0;
$coef_total = 0;
$count = 0;

while ($row = $resultado->fetch_assoc()) {
    $res = $row['resultado'];
    $odds = floatval($row['odds']);
    $coef = 0;
    
    if ($res === 'GREEN') {
        $coef = round($odds - 1, 4);
        $total_cantos_green++;
        $coef_total += $coef;
        $count++;
    }
    
    $res_color = $res === 'GREEN' ? 'green' : 'red';
    
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['tipo_aposta']}</td>";
    echo "<td>{$row['titulo']}</td>";
    echo "<td class='$res_color'>{$res}</td>";
    echo "<td>{$odds}</td>";
    echo "<td>" . substr($row['data_criacao'], 11, 8) . "</td>";
    echo "<td>{$coef}</td>";
    echo "</tr>";
}

echo "</table>";

echo "<div class='info'>";
echo "<h2>📊 Resumo CANTOS:</h2>";
echo "<p><strong>Total GREEN CANTOS:</strong> {$total_cantos_green}</p>";
echo "<p><strong>Coeficiente Total:</strong> {$coef_total}</p>";
echo "<p><strong>Com UND = R$ 100:</strong> {$coef_total} × 100 = <strong>R$ " . number_format($coef_total * 100, 2, ',', '.') . "</strong></p>";
echo "</div>";

// Agora verificar o que a API retorna
echo "<h2>🔗 Resposta da API:</h2>";
echo "<pre>";

$json_response = shell_exec("curl -s 'http://localhost/gestao/gestao_banca/obter-placar-dia.php' 2>/dev/null || echo 'Erro'");
$data = json_decode($json_response, true);

if ($data && isset($data['apostas']['aposta_3'])) {
    echo "Aposta 3 (CANTOS):\n";
    echo "  Green: " . $data['apostas']['aposta_3']['green'] . "\n";
    echo "  Red: " . $data['apostas']['aposta_3']['red'] . "\n";
    echo "  Coef Green: " . $data['apostas']['aposta_3']['lucro_coef_green'] . "\n";
    echo "  Coef Red: " . $data['apostas']['aposta_3']['lucro_coef_red'] . "\n";
    
    $calc = ($data['apostas']['aposta_3']['lucro_coef_green'] + $data['apostas']['aposta_3']['lucro_coef_red']) * 100;
    echo "\nCálculo: ({$data['apostas']['aposta_3']['lucro_coef_green']} + {$data['apostas']['aposta_3']['lucro_coef_red']}) × 100 = {$calc}\n";
} else {
    echo htmlspecialchars($json_response);
}

echo "</pre>";

echo "</body></html>";
?>
