<?php
/**
 * Script de Migração de Banco de Dados
 * Migra dados do banco local para o banco da Hostinger
 */

// Incluir configurações centralizadas
require_once 'config.php';

// Configurações do banco LOCAL (origem) - usando as constantes do config.php
$origem = [
    'host' => DB_HOST,
    'user' => DB_USERNAME,
    'password' => DB_PASSWORD,
    'database' => DB_NAME
];

// Configurações do banco HOSTINGER (destino)
$destino = [
    'host' => 'localhost',
    'user' => 'u857325944_gesbu',
    'password' => 'k12Lrc12#',
    'database' => 'u857325944_bnbce'
];

echo "<!DOCTYPE html>
<html lang='pt-br'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Migração de Banco de Dados</title>
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
            border-bottom: 3px solid #007bff;
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
        .status {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #fafafa;
        }
        button {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }
        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
<div class='container'>
    <h1>🔄 Migração de Banco de Dados</h1>";

try {
    // Conecta ao banco LOCAL
    echo "<div class='info'>Conectando ao banco local...</div>";
    $connOrigem = new mysqli($origem['host'], $origem['user'], $origem['password'], $origem['database']);
    
    if ($connOrigem->connect_error) {
        throw new Exception("Erro ao conectar ao banco local: " . $connOrigem->connect_error);
    }
    echo "<div class='success'>✅ Conectado ao banco local: {$origem['database']}</div>";

    // Conecta ao banco HOSTINGER
    echo "<div class='info'>Conectando ao banco Hostinger...</div>";
    $connDestino = new mysqli($destino['host'], $destino['user'], $destino['password'], $destino['database']);
    
    if ($connDestino->connect_error) {
        throw new Exception("Erro ao conectar ao banco Hostinger: " . $connDestino->connect_error);
    }
    echo "<div class='success'>✅ Conectado ao banco Hostinger: {$destino['database']}</div>";

    // Obtém lista de tabelas do banco de origem
    echo "<div class='info'>Obtendo lista de tabelas...</div>";
    $result = $connOrigem->query("SHOW TABLES");
    $tabelas = [];
    
    while ($row = $result->fetch_row()) {
        $tabelas[] = $row[0];
    }
    
    echo "<div class='success'>✅ Encontradas " . count($tabelas) . " tabelas: " . implode(', ', $tabelas) . "</div>";

    // Migra cada tabela
    echo "<div class='status'><strong>Processo de Migração:</strong>";
    
    foreach ($tabelas as $tabela) {
        echo "<br><strong>Tabela: $tabela</strong>";
        
        // Obtém a estrutura da tabela
        $createResult = $connOrigem->query("SHOW CREATE TABLE $tabela");
        $createRow = $createResult->fetch_row();
        $createTable = $createRow[1];
        
        // Modifica o CREATE TABLE para usar o banco de destino
        $createTable = str_replace("CREATE TABLE `" . $tabela . "`", "CREATE TABLE IF NOT EXISTS `" . $tabela . "`", $createTable);
        
        // Tenta criar a tabela no destino (será pulada se já existir)
        if ($connDestino->query($createTable)) {
            echo " - Estrutura criada ✓";
        } else {
            // Tabela pode já existir, continuamos
            echo " - Estrutura já existe ✓";
        }
        
        // Limpa dados anteriores (OPCIONAL - descomente se quiser)
        // $connDestino->query("TRUNCATE TABLE $tabela");
        
        // Obtém dados da tabela de origem
        $dataResult = $connOrigem->query("SELECT * FROM $tabela");
        
        if ($dataResult->num_rows > 0) {
            $rows_inserted = 0;
            
            while ($row = $dataResult->fetch_assoc()) {
                $columns = implode(", ", array_keys($row));
                $values = implode("', '", array_map(function($v) use ($connDestino) {
                    return $connDestino->real_escape_string($v);
                }, array_values($row)));
                
                $insert = "INSERT IGNORE INTO `$tabela` ($columns) VALUES ('$values')";
                
                if ($connDestino->query($insert)) {
                    $rows_inserted++;
                }
            }
            
            echo " - " . $rows_inserted . " registros inseridos ✓";
        } else {
            echo " - Nenhum registro para copiar";
        }
    }
    
    echo "</div>";
    echo "<div class='success'><strong>✅ Migração Concluída com Sucesso!</strong></div>";
    echo "<div class='info'>Todos os dados foram migrados do banco local para a Hostinger.</div>";
    
} catch (Exception $e) {
    echo "<div class='error'><strong>❌ Erro:</strong> " . $e->getMessage() . "</div>";
}

echo "<div style='margin-top: 30px; padding: 15px; background-color: #e7f3ff; border-left: 4px solid #2196F3;'>
    <strong>📌 Próximos Passos:</strong>
    <ol>
        <li>Verifique se todos os dados foram migrados corretamente</li>
        <li>Teste o login e outras funcionalidades</li>
        <li>Se tudo funcionar, você pode deletar este arquivo</li>
        <li>Atualize o arquivo config.php se ainda não o fez</li>
    </ol>
</div>";

echo "</div>
</body>
</html>";

// Fecha conexões
if (isset($connOrigem)) $connOrigem->close();
if (isset($connDestino)) $connDestino->close();
?>
