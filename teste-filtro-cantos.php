<?php
require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');
$conexao->set_charset("utf8mb4");

echo "<h2>🔍 Teste de Filtro de Cantos</h2>";

// Buscar todos os títulos com resultado não nulo
$sql = "SELECT id, titulo, time_1, time_2, resultado, data_criacao 
        FROM bote 
        WHERE resultado IS NOT NULL 
        ORDER BY data_criacao DESC 
        LIMIT 50";

$resultado = $conexao->query($sql);

echo "<h3>Total de registros: " . $resultado->num_rows . "</h3>";

echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #333; color: white;'>
        <th>ID</th>
        <th>Título</th>
        <th>Time 1</th>
        <th>Time 2</th>
        <th>Resultado</th>
        <th>Data</th>
        <th>É Cantos?</th>
      </tr>";

$cantos_count = 0;
$gols_count = 0;

while ($row = $resultado->fetch_assoc()) {
    $titulo = $row['titulo'];
    
    // Verificar se contém indicadores de cantos
    $isCantos = (
        strpos($titulo, '⛳') !== false ||
        strpos($titulo, '🚩') !== false ||
        stripos($titulo, 'CANTOS') !== false ||
        stripos($titulo, 'ESCANTEIO') !== false
    );
    
    if ($isCantos) {
        $cantos_count++;
        $cor = '#fff3e0';
    } else {
        $gols_count++;
        $cor = '#e8f5e9';
    }
    
    echo "<tr style='background: $cor;'>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td><strong>" . htmlspecialchars($titulo) . "</strong></td>";
    echo "<td>" . htmlspecialchars($row['time_1']) . "</td>";
    echo "<td>" . htmlspecialchars($row['time_2']) . "</td>";
    echo "<td style='font-weight: bold; color: " . ($row['resultado'] == 'GREEN' ? 'green' : 'red') . ";'>" . $row['resultado'] . "</td>";
    echo "<td>" . date('d/m/Y H:i', strtotime($row['data_criacao'])) . "</td>";
    echo "<td style='text-align: center;'>" . ($isCantos ? '✅ CANTOS' : '⚽ GOLS') . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<br><h3>Resumo:</h3>";
echo "<p>📊 Mensagens de GOLS: <strong>$gols_count</strong></p>";
echo "<p>🚩 Mensagens de CANTOS: <strong>$cantos_count</strong></p>";

$conexao->close();
?>
