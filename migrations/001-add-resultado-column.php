<?php
/**
 * MIGRATION: Adicionar coluna "resultado" na tabela "bote"
 * 
 * Esta migration adiciona o campo para armazenar os resultados das apostas
 * Status: GREEN ✅ | RED ❌ | REEMBOLSO 🔄
 * 
 * Executar: php migrations/001-add-resultado-column.php
 */

// Incluir configuração do banco
require_once __DIR__ . '/../config.php';

// ============================================
// EXECUTAR MIGRATION
// ============================================

try {
    echo "🔄 Iniciando migration: Adicionar coluna 'resultado'...\n\n";
    
    // ✅ VERIFICAR SE COLUNA JÁ EXISTE
    $checkColumn = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_NAME='bote' AND COLUMN_NAME='resultado'";
    
    $result = $conexao->query($checkColumn);
    
    if ($result && $result->num_rows > 0) {
        echo "⏭️  Coluna 'resultado' já existe. Nenhuma ação necessária.\n";
        $conexao->close();
        exit;
    }
    
    // ✅ ADICIONAR COLUNA
    $sql = "ALTER TABLE bote ADD COLUMN resultado VARCHAR(50) DEFAULT NULL COMMENT 'Resultado da aposta: GREEN, RED, REEMBOLSO'";
    
    if ($conexao->query($sql) === TRUE) {
        echo "✅ Coluna 'resultado' adicionada com sucesso!\n\n";
        
        // ✅ ADICIONAR ÍNDICE
        $indexSql = "ALTER TABLE bote ADD INDEX idx_resultado (resultado)";
        if ($conexao->query($indexSql) === TRUE) {
            echo "✅ Índice 'idx_resultado' criado com sucesso!\n\n";
        }
        
        // ✅ MOSTRAR ESTRUTURA ATUALIZADA
        echo "📋 Estrutura atualizada da tabela 'bote':\n";
        echo "─────────────────────────────────────────────────────────\n";
        
        $describe = $conexao->query("DESCRIBE bote");
        
        echo sprintf("%-25s %-30s %-10s\n", "Campo", "Tipo", "Nulo");
        echo "─────────────────────────────────────────────────────────\n";
        
        while ($row = $describe->fetch_assoc()) {
            echo sprintf("%-25s %-30s %-10s\n", 
                $row['Field'], 
                $row['Type'], 
                $row['Null']
            );
        }
        
        echo "─────────────────────────────────────────────────────────\n\n";
        
    } else {
        echo "❌ ERRO ao adicionar coluna: " . $conexao->error . "\n";
        $conexao->close();
        exit(1);
    }
    
    $conexao->close();
    echo "✅ Migration concluída com sucesso!\n";
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    exit(1);
}

?>
