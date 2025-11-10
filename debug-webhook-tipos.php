<?php
/**
 * SCRIPT PARA DEBUG - VERIFICAR TÍTULOS NO BANCO
 */

require_once 'config.php';

echo "<pre>";
echo "=== VERIFICANDO TÍTULOS NO BANCO DE DADOS ===\n\n";

// Consultar apostas ativas
$query = "
SELECT 
    id, 
    titulo, 
    time_1, 
    time_2, 
    resultado,
    status_aposta,
    data_criacao
FROM bote 
WHERE data_criacao >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
ORDER BY id DESC
LIMIT 30
";

$result = $conexao->query($query);

if (!$result) {
    die("❌ Erro na consulta: " . $conexao->error);
}

$apostas = $result->fetch_all(MYSQLI_ASSOC);
echo "📊 Total de apostas encontradas: " . count($apostas) . "\n\n";

// Agrupar por confronto
$confrontos = [];
foreach ($apostas as $aposta) {
    $confronto = $aposta['time_1'] . " x " . $aposta['time_2'];
    if (!isset($confrontos[$confronto])) {
        $confrontos[$confronto] = [];
    }
    $confrontos[$confronto][] = $aposta;
}

// Exibir resultado
foreach ($confrontos as $confronto => $apostasDoConfonto) {
    echo str_repeat("=", 80) . "\n";
    echo "🆚 CONFRONTO: " . $confronto . "\n";
    echo str_repeat("=", 80) . "\n";
    
    foreach ($apostasDoConfonto as $aposta) {
        echo "\n📌 ID: " . $aposta['id'] . "\n";
        echo "   Status: " . $aposta['status_aposta'] . "\n";
        echo "   Resultado: " . ($aposta['resultado'] ?: "NULL") . "\n";
        echo "   Título (EXATO): '" . $aposta['titulo'] . "'\n";
        echo "   Data: " . $aposta['data_criacao'] . "\n";
    }
    echo "\n";
}

echo "\n";
?>
