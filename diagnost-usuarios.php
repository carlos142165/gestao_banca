<?php
require_once 'config.php';

// Verificar se a tabela usuarios existe
$resultado = $conexao->query("SHOW TABLES LIKE 'usuarios'");
echo "<h2>Tabelas encontradas:</h2>";
echo $resultado->num_rows > 0 ? "<p style='color: green;'>✅ Tabela 'usuarios' existe</p>" : "<p style='color: red;'>❌ Tabela 'usuarios' NÃO existe</p>";

// Verificar a estrutura da tabela
if ($resultado->num_rows > 0) {
    echo "<h2>Estrutura da tabela 'usuarios':</h2>";
    $colunas = $conexao->query("DESCRIBE usuarios");
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Chave</th><th>Padrão</th><th>Extra</th></tr>";
    
    while ($col = $colunas->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $col['Field'] . "</td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Key'] . "</td>";
        echo "<td>" . $col['Default'] . "</td>";
        echo "<td>" . $col['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Contar usuários
    echo "<h2>Dados da tabela:</h2>";
    $count = $conexao->query("SELECT COUNT(*) as total FROM usuarios");
    $row = $count->fetch_assoc();
    echo "<p>Total de usuários: <strong>" . $row['total'] . "</strong></p>";
    
    // Listar alguns usuários
    if ($row['total'] > 0) {
        echo "<h3>Primeiros 5 usuários:</h3>";
        $usuarios = $conexao->query("SELECT id, nome, email, telefone, plano FROM usuarios LIMIT 5");
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Nome</th><th>Email</th><th>Telefone</th><th>Plano</th></tr>";
        
        while ($user = $usuarios->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . $user['nome'] . "</td>";
            echo "<td>" . $user['email'] . "</td>";
            echo "<td>" . $user['telefone'] . "</td>";
            echo "<td>" . $user['plano'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Diagnóstico - Usuários</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        table { border-collapse: collapse; margin-top: 10px; }
        h2, h3 { color: #333; }
        td, th { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>🔍 Diagnóstico - Banco de Dados</h1>
    <p>Banco: <strong>formulario-carlos</strong></p>
</body>
</html>
