<?php
require_once 'config.php';

// Verificar estrutura da tabela
$resultado = $conexao->query("DESCRIBE usuarios");

echo "<h2>Colunas da tabela 'usuarios':</h2>";
echo "<pre>";
while ($col = $resultado->fetch_assoc()) {
    echo $col['Field'] . " - " . $col['Type'] . "\n";
}
echo "</pre>";

// Se plano não existe, criar
$check_plano = $conexao->query("SHOW COLUMNS FROM usuarios LIKE 'plano'");
if ($check_plano->num_rows == 0) {
    echo "<h2>⚠️ Campo 'plano' não existe!</h2>";
    echo "<p>Criando campo 'plano'...</p>";
    
    $sql = "ALTER TABLE usuarios ADD COLUMN plano VARCHAR(50) DEFAULT 'Gratuito'";
    if ($conexao->query($sql)) {
        echo "✅ Campo 'plano' criado com sucesso!";
    } else {
        echo "❌ Erro ao criar campo: " . $conexao->error;
    }
} else {
    echo "<h2>✅ Campo 'plano' já existe!</h2>";
}
?>
