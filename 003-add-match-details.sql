-- ✅ SQL para adicionar colunas de detalhes da partida na tabela bote
-- Execute este script no seu banco de dados

ALTER TABLE bote ADD COLUMN tempo_minuto INT DEFAULT NULL COMMENT 'Tempo atual da partida em minutos';
ALTER TABLE bote ADD COLUMN odds_inicial_casa DECIMAL(5,2) DEFAULT NULL COMMENT 'Odds inicial - Casa';
ALTER TABLE bote ADD COLUMN odds_inicial_empate DECIMAL(5,2) DEFAULT NULL COMMENT 'Odds inicial - Empate';
ALTER TABLE bote ADD COLUMN odds_inicial_fora DECIMAL(5,2) DEFAULT NULL COMMENT 'Odds inicial - Fora';

-- Estádio/Competição
ALTER TABLE bote ADD COLUMN estadio VARCHAR(100) DEFAULT NULL COMMENT 'Estádio ou Competição';

-- Estatísticas de ataque
ALTER TABLE bote ADD COLUMN ataques_perigosos_1 INT DEFAULT NULL COMMENT 'Ataques perigosos - Time 1';
ALTER TABLE bote ADD COLUMN ataques_perigosos_2 INT DEFAULT NULL COMMENT 'Ataques perigosos - Time 2';

-- Cartões
ALTER TABLE bote ADD COLUMN cartoes_amarelos_1 INT DEFAULT NULL COMMENT 'Cartões amarelos - Time 1';
ALTER TABLE bote ADD COLUMN cartoes_amarelos_2 INT DEFAULT NULL COMMENT 'Cartões amarelos - Time 2';
ALTER TABLE bote ADD COLUMN cartoes_vermelhos_1 INT DEFAULT NULL COMMENT 'Cartões vermelhos - Time 1';
ALTER TABLE bote ADD COLUMN cartoes_vermelhos_2 INT DEFAULT NULL COMMENT 'Cartões vermelhos - Time 2';

-- Chutes
ALTER TABLE bote ADD COLUMN chutes_lado_1 INT DEFAULT NULL COMMENT 'Chutes ao lado - Time 1';
ALTER TABLE bote ADD COLUMN chutes_lado_2 INT DEFAULT NULL COMMENT 'Chutes ao lado - Time 2';
ALTER TABLE bote ADD COLUMN chutes_alvo_1 INT DEFAULT NULL COMMENT 'Chutes no alvo - Time 1';
ALTER TABLE bote ADD COLUMN chutes_alvo_2 INT DEFAULT NULL COMMENT 'Chutes no alvo - Time 2';

-- Posse de bola
ALTER TABLE bote ADD COLUMN posse_bola_1 INT DEFAULT NULL COMMENT 'Posse de bola - Time 1 (%)';
ALTER TABLE bote ADD COLUMN posse_bola_2 INT DEFAULT NULL COMMENT 'Posse de bola - Time 2 (%)';
