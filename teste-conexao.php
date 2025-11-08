<?php
// ✅ TESTE DE CONEXÃO E DADOS
require_once 'config.php';

echo "<h1>📊 Teste de Conexão e Dados</h1>";

// 1. Verificar conexão
echo "<h2>1️⃣ Verificando conexão...</h2>";
if ($conexao->connect_error) {
    echo "❌ Erro de conexão: " . $conexao->connect_error;
    exit;
} else {
    echo "✅ Conexão OK!<br>";
    echo "Host: " . DB_HOST . "<br>";
    echo "Banco: " . DB_NAME . "<br>";
}

// 2. Listar times únicos na tabela
echo "<h2>2️⃣ Times no banco de dados:</h2>";
$query = "SELECT DISTINCT time_1 FROM bote UNION SELECT DISTINCT time_2 FROM bote ORDER BY time_1 LIMIT 20";
$result = $conexao->query($query);

if ($result) {
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>" . htmlspecialchars($row['time_1']) . "</li>";
    }
    echo "</ul>";
} else {
    echo "❌ Erro ao buscar times: " . $conexao->error;
}

// 3. Testar busca para Pisa e Cremonese
echo "<h2>3️⃣ Teste de busca: Pisa vs Cremonese</h2>";
$time1 = 'Pisa';
$time2 = 'Cremonese';
$tipo = 'gols';

$sql = "SELECT 
    resultado,
    data_criacao,
    time_1,
    time_2,
    placar_1,
    placar_2,
    tipo_aposta
FROM bote 
WHERE (
    (LOWER(time_1) = LOWER(?) AND (resultado IN ('GREEN', 'RED', 'REEMBOLSO') OR resultado IS NULL))
    OR (LOWER(time_2) = LOWER(?) AND (resultado IN ('GREEN', 'RED', 'REEMBOLSO') OR resultado IS NULL))
)
AND LOWER(tipo_aposta) LIKE LOWER(CONCAT('%', ?, '%'))
ORDER BY data_criacao DESC
LIMIT 10";

$stmt = $conexao->prepare($sql);
if (!$stmt) {
    echo "❌ Erro ao preparar query: " . $conexao->error;
} else {
    $stmt->bind_param('sss', $time1, $time1, $tipo);
    if (!$stmt->execute()) {
        echo "❌ Erro ao executar query: " . $stmt->error;
    } else {
        $resultado = $stmt->get_result();
        $count = $resultado->num_rows;
        echo "✅ Query executada! Encontrados <strong>$count</strong> registros para $time1<br><br>";
        
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Time 1</th><th>Time 2</th><th>Resultado</th><th>Data</th><th>Tipo</th></tr>";
        while ($row = $resultado->fetch_assoc()) {
            $res = $row['resultado'] ?? 'NULL';
            $date = date('d/m/Y H:i', strtotime($row['data_criacao']));
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['time_1']) . "</td>";
            echo "<td>" . htmlspecialchars($row['time_2']) . "</td>";
            echo "<td><strong>" . htmlspecialchars($res) . "</strong></td>";
            echo "<td>" . $date . "</td>";
            echo "<td>" . htmlspecialchars($row['tipo_aposta']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    $stmt->close();
}

// 4. Busca também para Cremonese
echo "<h2>4️⃣ Teste de busca: Cremonese</h2>";
$sql2 = "SELECT 
    resultado,
    data_criacao,
    time_1,
    time_2,
    placar_1,
    placar_2,
    tipo_aposta
FROM bote 
WHERE (
    (LOWER(time_1) = LOWER(?) AND (resultado IN ('GREEN', 'RED', 'REEMBOLSO') OR resultado IS NULL))
    OR (LOWER(time_2) = LOWER(?) AND (resultado IN ('GREEN', 'RED', 'REEMBOLSO') OR resultado IS NULL))
)
AND LOWER(tipo_aposta) LIKE LOWER(CONCAT('%', ?, '%'))
ORDER BY data_criacao DESC
LIMIT 10";

$stmt2 = $conexao->prepare($sql2);
if (!$stmt2) {
    echo "❌ Erro ao preparar query: " . $conexao->error;
} else {
    $stmt2->bind_param('sss', $time2, $time2, $tipo);
    if (!$stmt2->execute()) {
        echo "❌ Erro ao executar query: " . $stmt2->error;
    } else {
        $resultado2 = $stmt2->get_result();
        $count2 = $resultado2->num_rows;
        echo "✅ Query executada! Encontrados <strong>$count2</strong> registros para $time2<br><br>";
        
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Time 1</th><th>Time 2</th><th>Resultado</th><th>Data</th><th>Tipo</th></tr>";
        while ($row = $resultado2->fetch_assoc()) {
            $res = $row['resultado'] ?? 'NULL';
            $date = date('d/m/Y H:i', strtotime($row['data_criacao']));
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['time_1']) . "</td>";
            echo "<td>" . htmlspecialchars($row['time_2']) . "</td>";
            echo "<td><strong>" . htmlspecialchars($res) . "</strong></td>";
            echo "<td>" . $date . "</td>";
            echo "<td>" . htmlspecialchars($row['tipo_aposta']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    $stmt2->close();
}

$conexao->close();
?>
