<?php
echo "<h1>✅ PHP está funcionando!</h1>";
echo "<p>Versão PHP: " . phpversion() . "</p>";

// Testar conexão com banco
echo "<h2>Teste de Banco de Dados</h2>";
require 'config.php';

if ($conexao->connect_error) {
    echo "<p style='color:red;'>❌ Erro na conexão: " . $conexao->connect_error . "</p>";
} else {
    echo "<p style='color:green;'>✅ Conexão com banco de dados OK!</p>";
    
    // Listar bancos disponíveis
    $result = $conexao->query("SHOW DATABASES");
    echo "<h3>Bancos de dados disponíveis:</h3>";
    echo "<ul>";
    while($row = $result->fetch_assoc()) {
        echo "<li>" . $row['Database'] . "</li>";
    }
    echo "</ul>";
}

// Testar pastas
echo "<h2>Estrutura de Pastas</h2>";
$pastas = ['css', 'js', 'img', 'ajax', 'uploads', 'dados'];
foreach($pastas as $pasta) {
    if(is_dir($pasta)) {
        echo "<p style='color:green;'>✅ Pasta '$pasta' existe</p>";
    } else {
        echo "<p style='color:red;'>❌ Pasta '$pasta' NÃO encontrada</p>";
    }
}

// Listar arquivos principais
echo "<h2>Arquivos Principais</h2>";
$arquivos = ['index.php', 'home.php', 'login.php', 'config.php'];
foreach($arquivos as $arquivo) {
    if(file_exists($arquivo)) {
        echo "<p style='color:green;'>✅ $arquivo existe</p>";
    } else {
        echo "<p style='color:red;'>❌ $arquivo NÃO encontrado</p>";
    }
}
?>
