<?php
// ✅ DEBUG - Mostrar mensagem BRUTA de CANTOS
header('Content-Type: text/html; charset=utf-8');

require_once 'config.php';

$data_hoje = date('Y-m-d');

// Buscar APENAS CANTOS
$query = "SELECT id, mensagem_completa, odds FROM bote 
          WHERE DATE(data_criacao) = '$data_hoje'
          AND (tipo_aposta LIKE '%CANTO%' OR titulo LIKE '%CANTO%')
          LIMIT 1";

$resultado = $conexao->query($query);

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><style>";
echo "body { font-family: monospace; background: #1e1e1e; color: #0f0; padding: 20px; }";
echo "pre { background: #000; padding: 15px; border: 1px solid #0f0; white-space: pre-wrap; word-wrap: break-word; }";
echo "h1 { color: #0f0; }";
echo ".info { background: #003300; padding: 10px; margin: 10px 0; border: 1px solid #0f0; }";
echo "</style></head><body>";

echo "<h1>🔍 DEBUG - Mensagem BRUTA de CANTOS</h1>";

if ($row = $resultado->fetch_assoc()) {
    echo "<div class='info'>";
    echo "<p><strong>ID:</strong> " . $row['id'] . "</p>";
    echo "<p><strong>Odds salva:</strong> " . $row['odds'] . "</p>";
    echo "</div>";
    
    echo "<h2>Mensagem Completa:</h2>";
    echo "<pre>" . htmlspecialchars($row['mensagem_completa']) . "</pre>";
    
    echo "<h2>Análise do texto:</h2>";
    $linhas = explode("\n", $row['mensagem_completa']);
    echo "<pre>";
    foreach ($linhas as $idx => $linha) {
        echo "[Linha $idx] " . htmlspecialchars(trim($linha)) . "\n";
    }
    echo "</pre>";
    
    // Procurar por padrões de odds
    echo "<h2>Padrões de Odds encontrados:</h2>";
    echo "<pre>";
    
    $patterns = [
        'Gols over' => '/Gols over\s*[\+\-]?[\d\.]*\s*:\s*([\d\.]+)/i',
        'Escanteios over' => '/Escanteios?\s*over\s*[\+\-]?[\d\.]*\s*:\s*([\d\.]+)/i',
        'Cantos over' => '/Cantos?\s*over\s*[\+\-]?[\d\.]*\s*:\s*([\d\.]+)/i',
        'Qualquer pattern com :' => '/([^:]*over[^:]*)\s*:\s*([\d\.]+)/i'
    ];
    
    foreach ($patterns as $nome => $pattern) {
        if (preg_match($pattern, $row['mensagem_completa'], $m)) {
            echo "✅ {$nome} encontrado: " . htmlspecialchars($m[0]) . " => odds={$m[count($m)-1]}\n";
        } else {
            echo "❌ {$nome} NÃO encontrado\n";
        }
    }
    
    echo "</pre>";
} else {
    echo "<p style='color: #ff0000;'>❌ Nenhuma mensagem de CANTOS encontrada no banco hoje!</p>";
}

echo "</body></html>";
?>
