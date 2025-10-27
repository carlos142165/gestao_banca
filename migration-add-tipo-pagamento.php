<?php
/**
 * Script de migração para adicionar coluna tipo_pagamento na tabela usuarios
 * Diferencia entre assinaturas pagas ('pago') e bônus ('bonus')
 */

require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // Verificar se coluna já existe
    $result = $conexao->query("SHOW COLUMNS FROM usuarios LIKE 'tipo_pagamento'");
    
    if ($result->num_rows > 0) {
        echo "✅ Coluna 'tipo_pagamento' já existe na tabela usuarios<br>";
        exit;
    }
    
    // Adicionar coluna tipo_pagamento com valor padrão 'pago'
    $sql = "ALTER TABLE usuarios ADD COLUMN tipo_pagamento ENUM('pago', 'bonus') DEFAULT 'pago'";
    
    if ($conexao->query($sql)) {
        echo "✅ Coluna 'tipo_pagamento' adicionada com sucesso!<br>";
        echo "ℹ️ Todos os usuários existentes foram marcados como 'pago' por padrão<br>";
    } else {
        throw new Exception("Erro ao adicionar coluna: " . $conexao->error);
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage();
}
?>
