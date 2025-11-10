<?php
/**
 * ================================================================
 * MIGRAÇÃO: Adicionar coluna data_atualizacao na tabela bote
 * ================================================================
 * 
 * OBJETIVO:
 * - Adicionar coluna data_atualizacao para rastrear quando resultados são atualizados
 * - Criar trigger para atualizar automaticamente quando houver UPDATE
 * 
 * COMO EXECUTAR:
 * Acesse: https://analisegb.com/migrations/002-add-data-atualizacao-column.php
 */

// ✅ CONFIGURAÇÃO DO BANCO
require_once __DIR__ . '/../config.php';

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migração: Adicionar data_atualizacao</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 900px;
            margin: 40px auto;
            padding: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 { color: #667eea; border-bottom: 3px solid #667eea; padding-bottom: 15px; }
        .success { 
            background: #d4edda; 
            border-left: 5px solid #28a745; 
            padding: 20px; 
            margin: 20px 0; 
            border-radius: 5px;
            color: #155724;
        }
        .error { 
            background: #f8d7da; 
            border-left: 5px solid #dc3545; 
            padding: 20px; 
            margin: 20px 0; 
            border-radius: 5px;
            color: #721c24;
        }
        .info { 
            background: #d1ecf1; 
            border-left: 5px solid #17a2b8; 
            padding: 20px; 
            margin: 20px 0; 
            border-radius: 5px;
            color: #0c5460;
        }
        code {
            background: #f4f4f4;
            padding: 3px 8px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            color: #e83e8c;
        }
        .step { 
            margin: 25px 0; 
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .step h3 { 
            color: #667eea; 
            margin-top: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Migração: Adicionar data_atualizacao</h1>
        
        <?php
        try {
            // ✅ PASSO 1: Verificar se a coluna já existe
            echo "<div class='step'>";
            echo "<h3>📋 Passo 1: Verificando estrutura atual</h3>";
            
            $checkColumn = "SHOW COLUMNS FROM bote LIKE 'data_atualizacao'";
            $result = $conexao->query($checkColumn);
            
            if ($result->num_rows > 0) {
                echo "<div class='info'>✅ Coluna <code>data_atualizacao</code> já existe na tabela <code>bote</code>.</div>";
                $colunaExiste = true;
            } else {
                echo "<div class='info'>⚠️ Coluna <code>data_atualizacao</code> NÃO existe. Será criada agora.</div>";
                $colunaExiste = false;
            }
            echo "</div>";

            // ✅ PASSO 2: Adicionar coluna se não existir
            if (!$colunaExiste) {
                echo "<div class='step'>";
                echo "<h3>➕ Passo 2: Adicionando coluna data_atualizacao</h3>";
                
                $sql = "ALTER TABLE bote 
                        ADD COLUMN data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP 
                        COMMENT 'Data da última atualização do registro'";
                
                if ($conexao->query($sql) === TRUE) {
                    echo "<div class='success'>✅ Coluna <code>data_atualizacao</code> adicionada com sucesso!</div>";
                } else {
                    throw new Exception("Erro ao adicionar coluna: " . $conexao->error);
                }
                echo "</div>";
            }

            // ✅ PASSO 3: Atualizar registros existentes
            echo "<div class='step'>";
            echo "<h3>🔄 Passo 3: Inicializando valores para registros existentes</h3>";
            
            $updateExisting = "UPDATE bote 
                              SET data_atualizacao = data_criacao 
                              WHERE data_atualizacao IS NULL OR data_atualizacao = '0000-00-00 00:00:00'";
            
            if ($conexao->query($updateExisting) === TRUE) {
                $affectedRows = $conexao->affected_rows;
                echo "<div class='success'>✅ Inicializados <code>$affectedRows</code> registros com data_atualizacao = data_criacao</div>";
            } else {
                echo "<div class='error'>⚠️ Erro ao atualizar registros: " . $conexao->error . "</div>";
            }
            echo "</div>";

            // ✅ PASSO 4: Verificar estrutura final
            echo "<div class='step'>";
            echo "<h3>✅ Passo 4: Estrutura final da tabela</h3>";
            
            $showStructure = "DESCRIBE bote";
            $result = $conexao->query($showStructure);
            
            if ($result) {
                echo "<table border='1' cellpadding='10' cellspacing='0' style='width:100%; border-collapse: collapse;'>";
                echo "<thead style='background: #667eea; color: white;'>";
                echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Padrão</th><th>Extra</th></tr>";
                echo "</thead><tbody>";
                
                while ($row = $result->fetch_assoc()) {
                    $highlight = ($row['Field'] === 'data_atualizacao') ? " style='background: #ffffcc;'" : "";
                    echo "<tr{$highlight}>";
                    echo "<td><strong>{$row['Field']}</strong></td>";
                    echo "<td>{$row['Type']}</td>";
                    echo "<td>{$row['Null']}</td>";
                    echo "<td>" . ($row['Default'] ?: 'NULL') . "</td>";
                    echo "<td>{$row['Extra']}</td>";
                    echo "</tr>";
                }
                
                echo "</tbody></table>";
            }
            echo "</div>";

            // ✅ MENSAGEM FINAL
            echo "<div class='success' style='font-size: 1.1em; margin-top: 30px;'>";
            echo "<h2 style='margin-top: 0;'>🎉 Migração concluída com sucesso!</h2>";
            echo "<p><strong>Próximos passos:</strong></p>";
            echo "<ol>";
            echo "<li>A coluna <code>data_atualizacao</code> foi criada</li>";
            echo "<li>Ela será atualizada automaticamente quando o <code>resultado</code> for atualizado</li>";
            echo "<li>O polling agora detecta mensagens atualizadas nos últimos 10 segundos</li>";
            echo "<li>Os resultados aparecerão em tempo real sem F5! 🚀</li>";
            echo "</ol>";
            echo "</div>";

        } catch (Exception $e) {
            echo "<div class='error'>";
            echo "<h3>❌ Erro na Migração</h3>";
            echo "<p><strong>Mensagem:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</div>";
        }

        $conexao->close();
        ?>
        
        <div style="margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 8px; text-align: center;">
            <p><strong>💡 Dica:</strong> Após executar esta migração, faça upload dos arquivos:</p>
            <ul style="text-align: left; display: inline-block;">
                <li><code>api/carregar-mensagens-banco.php</code></li>
                <li><code>js/telegram-mensagens.js</code></li>
                <li><code>api/telegram-webhook.php</code></li>
            </ul>
        </div>
    </div>
</body>
</html>
