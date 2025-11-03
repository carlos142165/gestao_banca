<?php
/**
 * MIGRATION: Adicionar coluna "updated_at" na tabela "bote"
 * 
 * Esta coluna permite rastrear quando uma linha foi modificada (especialmente quando `resultado` é atualizado).
 * O frontend usará isso para fazer polling incremental eficiente.
 * 
 * Executar: php migrations/002-add-updated-at-column.php
 */

require_once __DIR__ . '/../config.php';

try {
    echo "🚀 Iniciando migration: Adicionar coluna 'updated_at' na tabela 'bote'\n\n";
    
    // Verificar se a coluna já existe
    $checkQuery = "SELECT COLUMN_NAME 
                   FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME = 'bote' 
                   AND COLUMN_NAME = 'updated_at'";
    
    $result = $conexao->query($checkQuery);
    
    if ($result && $result->num_rows > 0) {
        echo "ℹ️  Coluna 'updated_at' já existe na tabela 'bote'. Nada a fazer.\n";
        exit(0);
    }
    
    // Adicionar coluna updated_at
    echo "📝 Adicionando coluna 'updated_at'...\n";
    
    $sql = "ALTER TABLE bote 
            ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP 
            COMMENT 'Timestamp da última atualização (usado para polling incremental)'";
    
    if ($conexao->query($sql)) {
        echo "✅ Coluna 'updated_at' adicionada com sucesso!\n\n";
        
        // Adicionar índice para otimizar queries de polling
        echo "📝 Adicionando índice idx_updated_at...\n";
        $indexSql = "ALTER TABLE bote ADD INDEX idx_updated_at (updated_at)";
        
        if ($conexao->query($indexSql)) {
            echo "✅ Índice criado com sucesso!\n\n";
        } else {
            echo "⚠️  Aviso: Não foi possível criar índice: " . $conexao->error . "\n\n";
        }
        
        // Inicializar updated_at com data_criacao para registros existentes
        echo "📝 Inicializando 'updated_at' para registros existentes...\n";
        $initSql = "UPDATE bote SET updated_at = data_criacao WHERE updated_at IS NULL";
        
        if ($conexao->query($initSql)) {
            echo "✅ Registros existentes atualizados!\n\n";
        }
        
        echo "📋 Estrutura atualizada da tabela 'bote':\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        
        $describe = $conexao->query("DESCRIBE bote");
        while ($row = $describe->fetch_assoc()) {
            echo sprintf("%-25s %-20s %-10s\n", $row['Field'], $row['Type'], $row['Null']);
        }
        
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        echo "✅ Migration concluída com sucesso!\n";
        
    } else {
        throw new Exception("Erro ao adicionar coluna: " . $conexao->error);
    }
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    exit(1);
}
?>
