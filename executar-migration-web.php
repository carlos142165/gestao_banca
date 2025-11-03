<?php
/**
 * ================================================================
 * EXECUTAR MIGRATION VIA WEB
 * ================================================================
 * Acesse este arquivo pelo navegador para executar a migration
 * Exemplo: http://localhost/gestao/public_html/executar-migration-web.php
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ✅ Incluir configuração do banco
require_once 'config.php';

echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <title>Executar Migration - updated_at</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        pre {
            background-color: #fff;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>🔧 Migration: Adicionar coluna updated_at</h1>
";

try {
    // ✅ VERIFICAR SE COLUNA updated_at JÁ EXISTE
    echo "<div class='info'><strong>Passo 1:</strong> Verificando se coluna updated_at já existe...</div>";
    
    $checkQuery = "SHOW COLUMNS FROM bote LIKE 'updated_at'";
    $result = $conexao->query($checkQuery);
    
    if ($result && $result->num_rows > 0) {
        echo "<div class='error'>❌ Coluna 'updated_at' já existe na tabela 'bote'. Migration não necessária.</div>";
    } else {
        echo "<div class='success'>✅ Coluna não existe. Procedendo com criação...</div>";
        
        // ✅ ADICIONAR COLUNA updated_at
        echo "<div class='info'><strong>Passo 2:</strong> Adicionando coluna updated_at...</div>";
        
        $alterQuery = "
            ALTER TABLE bote 
            ADD COLUMN updated_at TIMESTAMP 
            DEFAULT CURRENT_TIMESTAMP 
            ON UPDATE CURRENT_TIMESTAMP
        ";
        
        if ($conexao->query($alterQuery)) {
            echo "<div class='success'>✅ Coluna 'updated_at' adicionada com sucesso!</div>";
        } else {
            throw new Exception("Erro ao adicionar coluna: " . $conexao->error);
        }
        
        // ✅ ADICIONAR ÍNDICE
        echo "<div class='info'><strong>Passo 3:</strong> Adicionando índice idx_updated_at...</div>";
        
        $indexQuery = "ALTER TABLE bote ADD INDEX idx_updated_at (updated_at)";
        
        if ($conexao->query($indexQuery)) {
            echo "<div class='success'>✅ Índice adicionado com sucesso!</div>";
        } else {
            // Não falhar se índice já existe
            echo "<div class='info'>⚠️ Índice já existe ou erro ao criar: " . $conexao->error . "</div>";
        }
        
        // ✅ INICIALIZAR VALORES EXISTENTES
        echo "<div class='info'><strong>Passo 4:</strong> Inicializando updated_at para registros existentes...</div>";
        
        $updateQuery = "UPDATE bote SET updated_at = data_criacao WHERE updated_at IS NULL";
        
        if ($conexao->query($updateQuery)) {
            $affectedRows = $conexao->affected_rows;
            echo "<div class='success'>✅ {$affectedRows} registros inicializados com data_criacao!</div>";
        } else {
            throw new Exception("Erro ao inicializar valores: " . $conexao->error);
        }
        
        // ✅ VERIFICAR RESULTADO FINAL
        echo "<div class='info'><strong>Passo 5:</strong> Verificando estrutura final...</div>";
        
        $describeQuery = "DESCRIBE bote";
        $result = $conexao->query($describeQuery);
        
        echo "<pre>";
        echo str_pad("Campo", 30) . str_pad("Tipo", 30) . str_pad("Nulo", 10) . "Chave\n";
        echo str_repeat("-", 80) . "\n";
        
        $foundUpdatedAt = false;
        while ($row = $result->fetch_assoc()) {
            if ($row['Field'] === 'updated_at') {
                $foundUpdatedAt = true;
                echo "<strong>";
            }
            echo str_pad($row['Field'], 30);
            echo str_pad($row['Type'], 30);
            echo str_pad($row['Null'], 10);
            echo $row['Key'];
            if ($row['Field'] === 'updated_at') {
                echo "</strong>";
            }
            echo "\n";
        }
        echo "</pre>";
        
        if ($foundUpdatedAt) {
            echo "<div class='success'><h2>✅ MIGRATION CONCLUÍDA COM SUCESSO!</h2></div>";
            echo "<div class='info'>";
            echo "<h3>Próximos passos:</h3>";
            echo "<ol>";
            echo "<li>O webhook já foi atualizado para SET updated_at=NOW()</li>";
            echo "<li>A API já suporta polling incremental com last_check</li>";
            echo "<li>O frontend já usa polling incremental</li>";
            echo "<li>Teste enviando um resultado pelo Telegram</li>";
            echo "<li>Verifique o console do navegador para logs de atualização</li>";
            echo "</ol>";
            echo "</div>";
        } else {
            echo "<div class='error'>❌ Coluna não encontrada após criação. Verifique manualmente.</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div class='error'><strong>❌ ERRO:</strong> " . $e->getMessage() . "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "
    <hr>
    <p><a href='bot_aovivo.php'>← Voltar para Bot ao Vivo</a></p>
</body>
</html>
";

$conexao->close();
?>
