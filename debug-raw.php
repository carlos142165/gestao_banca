<?php
/**
 * Inspeção Bruta - Raw Data
 */

require_once 'config.php';
require_once 'carregar_sessao.php';

// Query bruta para ver exatamente o que tem no banco
$sql = "
SELECT 
    id,
    nome,
    id_plano,
    data_fim_assinatura,
    data_inicio_assinatura,
    data_renovacao_automatica
FROM usuarios 
WHERE data_fim_assinatura IS NOT NULL
AND id_plano IS NOT NULL
ORDER BY data_fim_assinatura ASC
";

$result = $conexao->query($sql);

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Inspeção Bruta de Dados</title>
    <style>
        body {
            font-family: monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
        }
        .data {
            background: #252526;
            padding: 15px;
            margin: 10px 0;
            border-left: 3px solid #007acc;
            font-size: 13px;
            line-height: 1.6;
        }
        pre {
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>Inspeção Bruta - SQL Result</h1>
    
    <div class="data">
        <strong>SQL Executada:</strong>
        <pre><?php echo htmlspecialchars($sql); ?></pre>
    </div>
    
    <div class="data">
        <strong>Resultados:</strong>
        <pre><?php
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . "\n";
        echo "Nome: " . $row['nome'] . "\n";
        echo "ID Plano: " . $row['id_plano'] . "\n";
        echo "Data Fim Assinatura: " . $row['data_fim_assinatura'] . "\n";
        echo "---\n";
    }
} else {
    echo "Nenhum resultado encontrado";
}
        ?></pre>
    </div>
    
    <div class="data">
        <strong>Teste de Contagem - MENSAL:</strong>
        <pre><?php
$sql_mensal = "
SELECT COUNT(*) as count FROM usuarios 
WHERE data_fim_assinatura IS NOT NULL 
AND id_plano IS NOT NULL
AND data_fim_assinatura > NOW()
AND data_fim_assinatura <= DATE_ADD(NOW(), INTERVAL 30 DAY)
";
echo htmlspecialchars($sql_mensal) . "\n\n";
$result = $conexao->query($sql_mensal);
$row = $result->fetch_assoc();
echo "Resultado: " . $row['count'];
        ?></pre>
    </div>
    
    <div class="data">
        <strong>Teste de Contagem - ANUAL:</strong>
        <pre><?php
$sql_anual = "
SELECT COUNT(*) as count FROM usuarios 
WHERE data_fim_assinatura IS NOT NULL 
AND id_plano IS NOT NULL
AND data_fim_assinatura > DATE_ADD(NOW(), INTERVAL 30 DAY)
";
echo htmlspecialchars($sql_anual) . "\n\n";
$result = $conexao->query($sql_anual);
$row = $result->fetch_assoc();
echo "Resultado: " . $row['count'];
        ?></pre>
    </div>
    
</body>
</html>
