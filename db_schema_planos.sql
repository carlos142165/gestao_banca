-- ====================================================================
-- SCHEMA DE PLANOS E ASSINATURAS - GESTÃO BANCA
-- ====================================================================

-- 1. ADICIONAR CAMPOS NA TABELA USUARIOS (se não existirem)
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS id_plano INT DEFAULT 1;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS status_assinatura VARCHAR(20) DEFAULT 'ativa';
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS data_inicio_assinatura DATETIME;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS data_fim_assinatura DATETIME;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS tipo_ciclo ENUM('mensal', 'anual') DEFAULT 'mensal';
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS cartao_salvo BOOLEAN DEFAULT FALSE;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS token_cartao VARCHAR(255);
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS ultimos_4_digitos VARCHAR(4);
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS bandeira_cartao VARCHAR(50);
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS mercadopago_customer_id VARCHAR(255);
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS data_renovacao_automatica DATETIME;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS renovacao_ativa BOOLEAN DEFAULT TRUE;

-- 2. CRIAR TABELA DE PLANOS
CREATE TABLE IF NOT EXISTS planos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(50) NOT NULL UNIQUE,
    descricao TEXT,
    preco_mes DECIMAL(10, 2) NOT NULL,
    preco_ano DECIMAL(10, 2) NOT NULL,
    mentores_limite INT DEFAULT 1,
    entradas_diarias INT DEFAULT 3,
    ativo BOOLEAN DEFAULT TRUE,
    icone VARCHAR(50),
    cor_tema VARCHAR(20),
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. CRIAR TABELA DE ASSINATURAS (HISTÓRICO)
CREATE TABLE IF NOT EXISTS assinaturas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    id_plano INT NOT NULL,
    data_inicio DATETIME NOT NULL,
    data_fim DATETIME,
    status ENUM('ativa', 'cancelada', 'expirada', 'pendente') DEFAULT 'ativa',
    tipo_ciclo ENUM('mensal', 'anual') NOT NULL,
    valor_pago DECIMAL(10, 2),
    id_mercadopago VARCHAR(255),
    id_preferencia_mercadopago VARCHAR(255),
    modo_pagamento VARCHAR(50),
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (id_plano) REFERENCES planos(id),
    INDEX (id_usuario),
    INDEX (status),
    INDEX (data_fim)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. CRIAR TABELA DE TRANSAÇÕES MERCADO PAGO
CREATE TABLE IF NOT EXISTS transacoes_mercadopago (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    id_assinatura INT,
    id_pago_mercadopago VARCHAR(255) UNIQUE,
    status_pagamento VARCHAR(50),
    tipo_pagamento VARCHAR(50),
    valor DECIMAL(10, 2),
    descricao TEXT,
    resposta_mercadopago LONGTEXT,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (id_assinatura) REFERENCES assinaturas(id),
    INDEX (id_usuario),
    INDEX (status_pagamento),
    INDEX (id_pago_mercadopago)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. CRIAR TABELA DE CARTÕES SALVOS
CREATE TABLE IF NOT EXISTS cartoes_salvos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    token_mercadopago VARCHAR(255),
    ultimos_digitos VARCHAR(4),
    bandeira VARCHAR(50),
    titular_cartao VARCHAR(100),
    mes_expiracao INT,
    ano_expiracao INT,
    principal BOOLEAN DEFAULT FALSE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. INSERIR PLANOS PADRÃO
INSERT INTO planos (nome, descricao, preco_mes, preco_ano, mentores_limite, entradas_diarias, icone, cor_tema) VALUES
('GRATUITO', 'Plano básico sem custo', 0.00, 0.00, 1, 3, 'fas fa-gift', '#95a5a6'),
('PRATA', 'Plano intermediário com mais features', 25.90, 154.80, 5, 15, 'fas fa-coins', '#c0392b'),
('OURO', 'Plano avançado com mais recursos', 39.90, 274.80, 10, 30, 'fas fa-star', '#f39c12'),
('DIAMANTE', 'Plano premium com tudo ilimitado', 59.90, 370.80, 999, 999, 'fas fa-gem', '#2980b9')
ON DUPLICATE KEY UPDATE nome = VALUES(nome);

-- 7. CRIAR ÍNDICES ADICIONAIS PARA PERFORMANCE
CREATE INDEX IF NOT EXISTS idx_usuarios_id_plano ON usuarios(id_plano);
CREATE INDEX IF NOT EXISTS idx_usuarios_status_assinatura ON usuarios(status_assinatura);
CREATE INDEX IF NOT EXISTS idx_assinaturas_usuario_status ON assinaturas(id_usuario, status);
CREATE INDEX IF NOT EXISTS idx_transacoes_usuario ON transacoes_mercadopago(id_usuario);

-- ====================================================================
-- FIM DO SCHEMA
-- ====================================================================
