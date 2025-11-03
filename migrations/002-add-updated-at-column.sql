-- ================================================================
-- MIGRATION: Adicionar coluna updated_at na tabela bote
-- ================================================================
-- Execute este SQL no PHPMyAdmin ou via linha de comando MySQL
-- 
-- Este script adiciona a coluna updated_at que será atualizada
-- automaticamente sempre que um registro for modificado
-- ================================================================

-- ✅ Adicionar coluna updated_at com auto-update
ALTER TABLE bote 
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP 
DEFAULT CURRENT_TIMESTAMP 
ON UPDATE CURRENT_TIMESTAMP;

-- ✅ Adicionar índice para melhorar performance do polling
ALTER TABLE bote ADD INDEX IF NOT EXISTS idx_updated_at (updated_at);

-- ✅ Inicializar updated_at para registros existentes
UPDATE bote 
SET updated_at = data_criacao 
WHERE updated_at IS NULL;

-- ✅ Verificar resultado
SELECT 'Migration concluída com sucesso!' AS status;
DESCRIBE bote;

-- ================================================================
-- COMO USAR:
-- ================================================================
-- 1. Copie todo este conteúdo
-- 2. Acesse PHPMyAdmin
-- 3. Selecione o banco 'u857325944_formu'
-- 4. Clique na aba "SQL"
-- 5. Cole este código e clique em "Executar"
-- ================================================================
