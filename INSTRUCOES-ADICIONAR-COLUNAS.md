# 📊 INSTRUÇÕES: Adicionar Colunas de Estatísticas da Partida

## ✅ O que foi feito

Criei um código para extrair e salvar esses dados adicionais das mensagens do Telegram:

- ⏰ **Tempo**: Minuto atual da partida
- 💰 **Odds iniciais**: Casa, Empate, Fora
- 🏟️ **Estádio/Competição**: Nome do estádio ou liga
- 🔥 **Ataques perigosos**: Time 1 e Time 2
- 🟨 **Cartões amarelos**: Time 1 e Time 2
- 🟥 **Cartões vermelhos**: Time 1 e Time 2
- 🎯 **Chutes ao lado**: Time 1 e Time 2
- 🎯 **Chutes no alvo**: Time 1 e Time 2
- 💯 **Posse de bola**: Team 1 (%) e Team 2 (%)

---

## 🚀 Passo a Passo de Implementação

### **PASSO 1: Executar a Migração SQL** (escolha uma opção)

**OPÇÃO A: Via script PHP**
```
Acesse no navegador:
http://localhost/gestao/gestao_banca/migrations/003-add-match-details-columns.php
```

**OPÇÃO B: Via phpMyAdmin ou MySQL**
```sql
-- Copie e cole este SQL no seu phpMyAdmin:
-- Arquivo: 003-add-match-details.sql
```

Ou execute linha por linha no MySQL:
```sql
ALTER TABLE bote ADD COLUMN tempo_minuto INT DEFAULT NULL COMMENT 'Tempo atual da partida em minutos';
ALTER TABLE bote ADD COLUMN odds_inicial_casa DECIMAL(5,2) DEFAULT NULL;
ALTER TABLE bote ADD COLUMN odds_inicial_empate DECIMAL(5,2) DEFAULT NULL;
ALTER TABLE bote ADD COLUMN odds_inicial_fora DECIMAL(5,2) DEFAULT NULL;
ALTER TABLE bote ADD COLUMN estadio VARCHAR(100) DEFAULT NULL;
ALTER TABLE bote ADD COLUMN ataques_perigosos_1 INT DEFAULT NULL;
ALTER TABLE bote ADD COLUMN ataques_perigosos_2 INT DEFAULT NULL;
ALTER TABLE bote ADD COLUMN cartoes_amarelos_1 INT DEFAULT NULL;
ALTER TABLE bote ADD COLUMN cartoes_amarelos_2 INT DEFAULT NULL;
ALTER TABLE bote ADD COLUMN cartoes_vermelhos_1 INT DEFAULT NULL;
ALTER TABLE bote ADD COLUMN cartoes_vermelhos_2 INT DEFAULT NULL;
ALTER TABLE bote ADD COLUMN chutes_lado_1 INT DEFAULT NULL;
ALTER TABLE bote ADD COLUMN chutes_lado_2 INT DEFAULT NULL;
ALTER TABLE bote ADD COLUMN chutes_alvo_1 INT DEFAULT NULL;
ALTER TABLE bote ADD COLUMN chutes_alvo_2 INT DEFAULT NULL;
ALTER TABLE bote ADD COLUMN posse_bola_1 INT DEFAULT NULL;
ALTER TABLE bote ADD COLUMN posse_bola_2 INT DEFAULT NULL;
```

### **PASSO 2: Verificar as Colunas foram Criadas**

1. Acesse phpMyAdmin
2. Clique na tabela `bote`
3. Veja se as novas colunas aparecem (scroll para a direita)

### **PASSO 3: Testar o Webhook**

Quando uma mensagem chegar do Telegram com este formato:

```
Oportunidade! 🚨

📊 🚨 OVER ( +0.5 ⚽️GOL  ) FT

⚽️ Bologna (H) x Le Havre (A) (ao vivo)

⏰ Tempo: 82'
Odds iniciais: Casa: 1.9 - Emp. 3.4 - Fora: 4.1
🏟 Japan J-League

🥅 Placar: 0 - 0  
Gols over +0.5: 1.5
Stake: 1%
    
⛳️ Escanteios: 10 - 2  

🔥 Ataques perigosos: 57 - 25
🟨 Cartões amarelos: 1 - 1
🟥 Cartões vermelhos: 0 - 0
🎯 Chutes ao lado: 12 - 4
🎯 Chutes no alvo: 3 - 1
💯 Posse de bola: 55% - 45%
```

Os dados serão extraídos automaticamente e salvos nas colunas.

### **PASSO 4: Verificar os Dados Foram Salvos**

No phpMyAdmin, execute:

```sql
SELECT 
    id, titulo, tipo_aposta,
    tempo_minuto,
    odds_inicial_casa, odds_inicial_empate, odds_inicial_fora,
    estadio,
    ataques_perigosos_1, ataques_perigosos_2,
    cartoes_amarelos_1, cartoes_amarelos_2,
    cartoes_vermelhos_1, cartoes_vermelhos_2,
    chutes_lado_1, chutes_lado_2,
    chutes_alvo_1, chutes_alvo_2,
    posse_bola_1, posse_bola_2
FROM bote 
ORDER BY id DESC 
LIMIT 10;
```

Se ver os dados preenchidos, funcionou! ✅

---

## 📝 Novo INSERT SQL (para referência)

Se quiser inserir manualmente, o SQL agora ficará assim:

```sql
INSERT INTO bote (
    telegram_message_id, mensagem_completa, titulo, tipo_aposta, 
    time_1, time_2, placar_1, placar_2, escanteios_1, escanteios_2, 
    valor_over, odds, tipo_odds, hora_mensagem, status_aposta, resultado,
    tempo_minuto, odds_inicial_casa, odds_inicial_empate, odds_inicial_fora,
    estadio, ataques_perigosos_1, ataques_perigosos_2, 
    cartoes_amarelos_1, cartoes_amarelos_2, 
    cartoes_vermelhos_1, cartoes_vermelhos_2,
    chutes_lado_1, chutes_lado_2, chutes_alvo_1, chutes_alvo_2,
    posse_bola_1, posse_bola_2
)
VALUES (
    999, 'Oportunidade...', '+0.5 GOL', 'GOL',
    'Bologna', 'Le Havre', 0, 0, 10, 2,
    0.5, 1.5, 'Gols Odds', '14:30:00', 'ATIVA', NULL,
    82, 1.9, 3.4, 4.1,
    'Japan J-League', 57, 25,
    1, 1, 0, 0,
    12, 4, 3, 1,
    55, 45
);
```

---

## 🔧 Arquivo de Migração PHP Criado

Se preferir versão em PHP:
- 📄 `migrations/003-add-match-details-columns.php`
- Pode ser executado via URL ou linha de comando

---

## ⚡ Próxima Etapa

Após as colunas serem criadas e testadas localmente:

1. Execute em produção (Hostinger) o mesmo SQL
2. Faça push do código atualizado: `git push origin main`
3. Acesse o webhook da produção para confirmar que funciona

Arquivos que precisam ir para produção:
- ✅ `api/telegram-webhook.php` (UPDATED - com extração dos novos dados)
- ✅ `migrations/003-add-match-details-columns.php` (NEW)
- ✅ `003-add-match-details.sql` (NEW - apenas como referência)

---

## ❓ Dúvidas?

Se algum campo não for detectado corretamente, pode ser por:
1. Emoji diferente (😀 vs 😊)
2. Espaçamento diferente 
3. Ordem das informações
4. Formato do número (1 vs 1.0)

Envie exemplo da mensagem que está vindo para ajustar o regex!
