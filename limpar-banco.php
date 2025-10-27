<?php
/**
 * Script para limpar o banco de dados
 */

// Configurações do banco HOSTINGER
$dbHost = 'localhost';
$dbUsername = 'u857325944_gesbu';
$dbPassword = 'k12Lrc12#';
$dbname = 'u857325944_bnbce';

echo "<!DOCTYPE html>
<html lang='pt-br'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Limpar Banco de Dados</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #dc3545;
            padding-bottom: 10px;
        }
        .success {
            color: #155724;
            background-color: #d4edda;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
            border-left: 4px solid #28a745;
        }
        .error {
            color: #721c24;
            background-color: #f8d7da;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
            border-left: 4px solid #dc3545;
        }
        .info {
            color: #004085;
            background-color: #d1ecf1;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
            border-left: 4px solid #17a2b8;
        }
        .warning {
            color: #856404;
            background-color: #fff3cd;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
            border-left: 4px solid #ffc107;
        }
    </style>
</head>
<body>
<div class='container'>
    <h1>🗑️ Limpando Banco de Dados</h1>";

try {
    // Conecta ao banco
    echo "<div class='info'>Conectando ao banco...</div>";
    $conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Erro ao conectar: " . $conn->connect_error);
    }
    echo "<div class='success'>✅ Conectado ao banco: $dbname</div>";

    // Desabilita verificação de chaves estrangeiras
    echo "<div class='info'>Desabilitando verificação de chaves estrangeiras...</div>";
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    echo "<div class='success'>✅ Verificação desabilitada</div>";

    // Obtém lista de tabelas
    echo "<div class='info'>Obtendo lista de tabelas...</div>";
    $result = $conn->query("SHOW TABLES");
    $tabelas = [];
    
    while ($row = $result->fetch_row()) {
        $tabelas[] = $row[0];
    }
    
    if (count($tabelas) == 0) {
        echo "<div class='warning'>⚠️ Nenhuma tabela encontrada no banco. Banco já está vazio!</div>";
    } else {
        echo "<div class='success'>✅ Encontradas " . count($tabelas) . " tabelas</div>";
        
        // Deleta cada tabela
        echo "<div class='info'><strong>Deletando tabelas:</strong></div>";
        foreach ($tabelas as $tabela) {
            $conn->query("DROP TABLE IF EXISTS `$tabela`");
            echo "<div class='success'>✅ Tabela '$tabela' deletada</div>";
        }
    }

    // Reabilita verificação de chaves estrangeiras
    echo "<div class='info'>Reabilitando verificação de chaves estrangeiras...</div>";
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    echo "<div class='success'>✅ Verificação reabilitada</div>";

    echo "<div style='margin-top: 30px; padding: 15px; background-color: #d4edda; border-left: 4px solid #28a745;'>
        <strong>✅ SUCESSO!</strong> Banco de dados foi limpo com sucesso!
        <br><br>
        <strong>Próximos passos:</strong>
        <ol>
            <li>Exporte o banco local (formulario-carlos)</li>
            <li>Importe o arquivo .sql normalmente</li>
            <li>Pronto! Seus dados estarão no banco Hostinger</li>
        </ol>
    </div>";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<div class='error'><strong>❌ Erro:</strong> " . $e->getMessage() . "</div>";
}

echo "</div>
</body>
</html>";
?>
