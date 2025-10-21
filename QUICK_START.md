# 🚀 QUICK START - SISTEMA DE PLANOS

## ⚡ Começar em 5 Minutos

### Passo 1: Executar o SQL (1 minuto)

1. Abra **phpMyAdmin**
2. Selecione banco de dados: `formulario-carlos`
3. Vá em **SQL**
4. Abra arquivo: `db_schema_planos.sql`
5. Copie e Cole todo o conteúdo
6. Clique em **Executar** ✅

### Passo 2: Configurar Mercado Pago (2 minutos)

1. Edite: `config_mercadopago.php` (linhas 9-10)
2. Adicione:
   ```php
   define('MP_ACCESS_TOKEN', 'APP_USR-SEU_TOKEN_AQUI');
   define('MP_PUBLIC_KEY', 'APP_USR-SEU_PUBLIC_KEY_AQUI');
   ```
3. Salve o arquivo ✅

> Onde pegar as chaves: https://www.mercadopago.com.br > Configurações > Credenciais

### Passo 3: Incluir no HTML (1 minuto)

No seu arquivo principal (ex: `gestao-diaria.php`), adicione **antes do `</body>`**:

```html
<!-- Modal de Planos -->
<?php include 'modal-planos-pagamento.html'; ?>

<!-- Scripts -->
<script src="js/plano-manager.js"></script>
```

### Passo 4: Adicionar Validações (1 minuto)

Onde você cria **mentor** ou **entrada**, adicione:

```javascript
// Antes de permitir o cadastro:
const pode_prosseguir = await PlanoManager.verificarEExibirPlanos('mentor');
if (!pode_prosseguir) return;

// OU para entrada:
const pode_prosseguir = await PlanoManager.verificarEExibirPlanos('entrada');
if (!pode_prosseguir) return;
```

### Passo 5: Testar (Pronto! ✅)

Acesse: http://localhost/gestao_banca/teste-planos.php

---

## 📋 O Que Você Recebeu

```
📦 SISTEMA DE PLANOS COM MERCADO PAGO
├── 📊 BANCO DE DADOS
│   ├── db_schema_planos.sql (Cria 5 tabelas + colunas em usuarios)
│   ├── planos (4 planos: GRATUITO, PRATA, OURO, DIAMANTE)
│   ├── assinaturas (histórico)
│   ├── transacoes_mercadopago (log)
│   └── cartoes_salvos (para renovação automática)
│
├── 🎨 INTERFACE
│   ├── modal-planos-pagamento.html (Modal + CSS)
│   ├── js/plano-manager.js (Lógica completa)
│   └── teste-planos.php (Página de testes)
│
├── 🔧 BACKEND (PHP)
│   ├── config_mercadopago.php (Configurações + classe)
│   ├── obter-planos.php (Lista planos)
│   ├── obter-dados-usuario.php (Dados assinatura)
│   ├── obter-cartoes-salvos.php (Cartões salvos)
│   ├── verificar-limite.php (Validação limites)
│   ├── processar-pagamento.php (Criar preferência MP)
│   └── webhook.php (Confirmar pagamento - ATUALIZADO)
│
└── 📚 DOCUMENTAÇÃO
    ├── README_PLANOS.md (Completo)
    ├── IMPLEMENTACAO_CHECKLIST.md (Passo a passo)
    └── QUICK_START.md (Este arquivo)
```

---

## 💳 4 PLANOS INCLUSOS

| Plano | Mentores | Entradas/dia | Preço/Mês | Preço/Ano |
|-------|----------|--------------|-----------|-----------|
| 🎁 GRATUITO | 1 | 3 | R$ 0,00 | R$ 0,00 |
| 🥈 PRATA | 5 | 15 | R$ 25,90 | R$ 154,80 |
| 🥇 OURO | 10 | 30 | R$ 39,90 | R$ 274,80 |
| 💎 DIAMANTE | ∞ | ∞ | R$ 59,90 | R$ 370,80 |

---

## 🎯 FLUXO RÁPIDO

```
Usuário clica "Cadastrar Mentor"
           ↓
Sistema verifica limite
           ↓
Se atingiu → Mostra Modal de Planos
           ↓
Clica em "Contratar Agora"
           ↓
Abre Modal de Pagamento
(Cartão | PIX | Cartões Salvos)
           ↓
Preenche dados
           ↓
Clica "Confirmar"
           ↓
Redireciona para Mercado Pago
           ↓
Completa pagamento lá
           ↓
Retorna ao site com novo plano ATIVO
           ↓
Pode cadastrar mais!
```

---

## 🧪 TESTAR AGORA

### URL de Testes
```
http://localhost/gestao_banca/teste-planos.php
```

### Cartão de Teste (APROVADO)
```
Número:   4111 1111 1111 1111
Data:     12/25
CVV:      123
```

### Cartão de Teste (RECUSADO)
```
Número:   5105 1051 0510 5100
Data:     11/25
CVV:      456
```

---

## 🔍 VERIFICAR SE TUDO ESTÁ OK

Abra o console (F12) e execute:

```javascript
// Deve retornar verdadeiro
PlanoManager !== undefined

// Deve retornar true
await PlanoManager.verificarEExibirPlanos('mentor')

// Deve retornar 4
PlanoManager.planos.length
```

---

## ❌ Problemas Comuns

### "Modal não abre"
```
✅ Verificar:
1. plano-manager.js está carregado?
2. modal-planos-pagamento.html está incluído?
3. Abra F12 e procure por erros

🔧 Testar:
PlanoManager.abrirModalPlanos()
```

### "API retorna erro"
```
✅ Verificar:
1. Usuário está logado?
2. Sessão está ativa?
3. Verifique config_mercadopago.php
```

### "Pagamento não confirma"
```
✅ Verificar:
1. Access Token está correto?
2. Webhook está registrado no MP?
3. Veja /logs/webhook.log
```

---

## 📞 Suporte Rápido

Se algo não funcionar, verifique:

1. **Script SQL executado?**
   ```sql
   SELECT COUNT(*) FROM planos;
   -- Deve retornar 4
   ```

2. **Config MP preenchida?**
   ```php
   echo MP_ACCESS_TOKEN;
   // Não deve retornar 'SEU_ACCESS_TOKEN_AQUI'
   ```

3. **Modal incluído no HTML?**
   ```html
   <!-- Procure por: -->
   <div id="modal-planos" class="modal-planos">
   ```

4. **Arquivo JS carregado?**
   ```javascript
   // No console (F12):
   console.log(typeof PlanoManager)
   // Deve retornar 'object'
   ```

---

## 🎓 Arquivos Importantes

| Arquivo | Descrição | Editar? |
|---------|-----------|---------|
| `config_mercadopago.php` | Credenciais MP | ✅ SIM (obrigatório) |
| `js/plano-manager.js` | Lógica JS | ❌ Não (pronto) |
| `modal-planos-pagamento.html` | Interface | ⚠️ Apenas CSS se quiser |
| `obter-planos.php` | API | ❌ Não (pronto) |
| `webhook.php` | Confirmações | ⚠️ Adicionar seu email |

---

## 🚀 Próximos Passos (Opcional)

### 1. Customizar Cores
Edite `modal-planos-pagamento.html`, linha ~300:
```css
--cor-plano: #seu-cor-aqui;
```

### 2. Customizar Textos
Edite `plano-manager.js`, função `renderizarPlanos()`

### 3. Adicionar Ícones Diferentes
Edite `db_schema_planos.sql`, coluna `icone`

### 4. Mudar Preços
Edite `db_schema_planos.sql` ou phpMyAdmin > Tabela planos

---

## ✨ Features Inclusos

- ✅ 4 Planos com preços configuráveis
- ✅ Toggle MÊS/ANO com economias
- ✅ Pagamento com Cartão (salvar para depois)
- ✅ Pagamento com PIX
- ✅ Cartões salvos para renovação
- ✅ Validação de limites automática
- ✅ Webhook de confirmação
- ✅ Log de transações
- ✅ Sistema de estatusos
- ✅ Interface responsiva

---

## 💡 Dicas

1. **Use em produção com HTTPS** (obrigatório)
2. **Rotinize o webhook** se não funcionar automaticamente
3. **Monitore /logs/webhook.log** para debug
4. **Teste com cartões de teste** antes de produção
5. **Implemente rate limiting** nos endpoints

---

## 📈 Métricas Úteis

Consultas SQL para acompanhar:

```sql
-- Receita total
SELECT SUM(valor_pago) FROM assinaturas WHERE status = 'ativa';

-- Planos mais vendidos
SELECT p.nome, COUNT(*) as total 
FROM assinaturas a
JOIN planos p ON a.id_plano = p.id
GROUP BY p.nome;

-- Taxa de conversão
SELECT 
  COUNT(DISTINCT id_usuario) as usuarios,
  COUNT(DISTINCT CASE WHEN status = 'ativa' THEN id_usuario END) as pagantes
FROM assinaturas;
```

---

## 🎉 Parabéns!

Você agora tem um **sistema de planos profissional com Mercado Pago**.

**Tempo investido:** ~30 minutos
**ROI esperado:** Muito alto! 💰

---

**Versão:** 1.0
**Última atualização:** 2025-10-20
**Status:** ✅ Pronto para Produção
