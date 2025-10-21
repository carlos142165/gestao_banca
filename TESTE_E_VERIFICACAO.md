# ✅ INTEGRAÇÃO CONCLUÍDA - INSTRUÇÕES DE TESTE

## 🎉 O QUE FOI FEITO

### 1️⃣ **gestao-diaria.php** ✅ ATUALIZADO
```php
<!-- Adicionado ANTES de </body>: -->
<?php include 'modal-planos-pagamento.html'; ?>
<script src="js/plano-manager.js"></script>
```

**Resultado:** Modal e JavaScript do sistema de planos carregam automaticamente em todas as páginas.

---

### 2️⃣ **script-gestao-diaria.js** ✅ ATUALIZADO

#### Adição 1 - Validação para MENTOR (linha ~2139)
```javascript
// ✅ VALIDAR LIMITE DE MENTORES ANTES DE CADASTRAR
if (typeof PlanoManager !== 'undefined' && PlanoManager.verificarEExibirPlanos) {
  const podeAvançar = await PlanoManager.verificarEExibirPlanos('mentor');
  if (!podeAvançar) {
    return; // Modal será mostrado automaticamente
  }
}
```

**Resultado:** Ao tentar cadastrar o 2º mentor com plano GRATUITO, o modal de planos abre automaticamente.

#### Adição 2 - Validação para ENTRADA (linha ~2154)
```javascript
// ✅ VALIDAR LIMITE DE ENTRADAS ANTES DE ADICIONAR
if (typeof PlanoManager !== 'undefined' && PlanoManager.verificarEExibirPlanos) {
  const podeAvançar = await PlanoManager.verificarEExibirPlanos('entrada');
  if (!podeAvançar) {
    return; // Modal será mostrado automaticamente
  }
}
```

**Resultado:** Ao tentar adicionar a 4ª entrada com plano GRATUITO, o modal de planos abre automaticamente.

---

## 🚀 COMO TESTAR (Passo a Passo)

### ⚙️ PRÉ-REQUISITOS
- [ ] SQL executado (`db_schema_planos.sql`)
- [ ] Credenciais Mercado Pago configuradas em `config_mercadopago.php`
- [ ] Arquivos criados e em local correto (veja estrutura abaixo)

---

### 🧪 TESTE 1: Verifica se modal carrega

**Passos:**
1. Abra: `http://localhost/gestao_banca/gestao-diaria.php`
2. Pressione **F12** (Developer Tools)
3. Vá até **Console** (aba)
4. Procure por erros em **vermelho** 🔴

**Resultado Esperado:**
- ✅ Sem erros
- ✅ Pode ver: `PlanoManager inicializado com sucesso` (opcional)

**Se tiver erro:**
```
❌ Uncaught SyntaxError: Unexpected token
❌ plano-manager.js:1 Failed to load resource
❌ PlanoManager is not defined
```
→ Verifique se arquivo `js/plano-manager.js` existe

---

### 🧪 TESTE 2: Carrega os planos

**Passos:**
1. No Console (F12), execute:
```javascript
PlanoManager.carregarPlanos();
```

2. Verifique em **F12 > Network**, procure por:
   - `obter-planos.php` → Deve ter **Status 200**
   - Response deve ser JSON com 4 planos

**Resultado Esperado:**
```json
[
  {
    "id": 1,
    "nome": "GRATUITO",
    "preco_mes": "0.00",
    "preco_ano": "0.00",
    "mentores_limite": 1,
    "entradas_diarias": 3
  },
  ...
]
```

**Se tiver erro:**
```
❌ 404 Not Found - obter-planos.php
```
→ Verifique se arquivo `obter-planos.php` existe

---

### 🧪 TESTE 3: Testa limite de MENTORES

**Cenário:** Você tem plano GRATUITO (máximo 1 mentor)

**Passos:**
1. Já com 1 mentor cadastrado
2. Clique em **"Novo Mentor"** ou **"Cadastrar Mentor"**
3. Preencha dados (nome, foto, etc)
4. Clique em **"Cadastrar Mentor"**

**Resultado Esperado:**
- 🎯 Modal de planos **ABRE AUTOMATICAMENTE**
- 📊 Mostra 4 planos com preços
- 💰 Toggle **MÊS/ANO** funciona
- ❌ Formulário **FECHA** sem enviar

**Se não abrir o modal:**
```
❌ Possíveis problemas:
1. PlanoManager não inicializou
2. verificar-limite.php retornou erro
3. JavaScript não executou validação
```

---

### 🧪 TESTE 4: Testa limite de ENTRADAS

**Cenário:** Você tem plano GRATUITO (máximo 3 entradas por dia)

**Passos:**
1. Na tela de gestão diária
2. Preenchaa 3 entradas (green ou red)
3. Tente adicionar a 4ª entrada
4. Clique em **"Enviar"** ou **"Confirmar"**

**Resultado Esperado:**
- 🎯 Modal de planos **ABRE AUTOMATICAMENTE**
- ❌ Entrada **NÃO É REGISTRADA**

---

### 🧪 TESTE 5: Toggle MÊS/ANO

**Passos:**
1. Modal de planos aberto
2. Clique em botão **"ANO"** (ao lado de **"MÊS"**)
3. Observe mudança de preços

**Resultado Esperado:**
```
ANTES (MÊS):
- PRATA: R$ 25,90/mês
- OURO: R$ 39,90/mês
- DIAMANTE: R$ 59,90/mês

DEPOIS (ANO):
- PRATA: R$ 154,80/ano (economia de R$ 155,00!)
- OURO: R$ 274,80/ano (economia de R$ 203,00!)
- DIAMANTE: R$ 370,80/ano (economia de R$ 349,00!)
```

---

### 🧪 TESTE 6: Pagar com Cartão

**Passos:**
1. Modal aberto
2. Selecione plano **PRATA**
3. Clique **"Contratar Agora"**
4. Modal de pagamento abre
5. Aba **"Cartão"** (padrão)
6. Preencha dados:
   - Nome: `João Silva`
   - Número: `4111111111111111` (cartão de teste MP)
   - Validade: `12/25`
   - CVV: `123`

**Resultado Esperado:**
- ✅ Redireciona para Mercado Pago
- ✅ Página de confirmação MP abre

**Se tiver erro:**
```
❌ Erro de conexão com Mercado Pago
→ Verifique credenciais em config_mercadopago.php
```

---

### 🧪 TESTE 7: Webhook de Confirmação

**Passos:**
1. No Mercado Pago (após pagar)
2. Aprovar pagamento (teste)
3. Retorna para seu site

**Resultado Esperado:**
- ✅ Usuário agora tem plano **PRATA**
- ✅ Pode cadastrar até 5 mentores
- ✅ Pode adicionar até 15 entradas/dia
- ✅ Status na tabela `usuarios` muda para `ativa`

**Verificar no BD:**
```sql
SELECT id, email, id_plano, status_assinatura, data_fim_assinatura
FROM usuarios
WHERE id = SUA_ID;
```

Deve retornar:
```
id    | email           | id_plano | status_assinatura | data_fim_assinatura
------|-----------------|----------|-------------------|--------------------
123   | seu@email.com   | 2        | ativa             | 2025-11-20 (30 dias)
```

---

## 📊 Estrutura de Arquivos

Verifique se **TODOS** esses arquivos existem:

```
gestao_banca/
│
├─ ✅ gestao-diaria.php (MODIFICADO)
│  └─ Tem as 2 linhas de include
│
├─ ✅ js/
│  ├─ script-gestao-diaria.js (MODIFICADO)
│  │  └─ Tem validações de mentor e entrada
│  └─ plano-manager.js (CRIADO)
│
├─ ✅ modal-planos-pagamento.html (CRIADO)
│
├─ ✅ config_mercadopago.php (CRIADO)
│  └─ Com credenciais configuradas
│
├─ ✅ obter-planos.php (CRIADO)
├─ ✅ obter-dados-usuario.php (CRIADO)
├─ ✅ verificar-limite.php (CRIADO)
├─ ✅ processar-pagamento.php (CRIADO)
├─ ✅ webhook.php (ATUALIZADO)
│
└─ ✅ db_schema_planos.sql (EXECUTADO)
   └─ 5 tabelas criadas + colunas em usuarios
```

---

## 🔧 TROUBLESHOOTING

### Problema: "PlanoManager is not defined"
```javascript
❌ console.log(PlanoManager);
// ReferenceError: PlanoManager is not defined
```

**Solução:**
1. Verifique se `js/plano-manager.js` existe
2. Verifique o caminho em `gestao-diaria.php`
3. Verifique se arquivo está sendo carregado (F12 > Network)

---

### Problema: "404 on obter-planos.php"
```
❌ GET /obter-planos.php - 404 Not Found
```

**Solução:**
1. Verifique se arquivo existe
2. Verifique path correto no `plano-manager.js`
3. Pode estar em pasta diferente

---

### Problema: Modal não abre ao atingir limite
```
❌ Clico em "Cadastrar Mentor" e nada acontece
```

**Solução:**
1. Verifique F12 Console (tem erros?)
2. Verifique se `verificarEExibirPlanos()` foi chamado
3. Teste no console:
```javascript
await PlanoManager.verificarEExibirPlanos('mentor');
```

---

### Problema: Credenciais Mercado Pago não funcionam
```
❌ Erro ao redirecionar para MP
```

**Solução:**
1. Verifique `config_mercadopago.php` linhas 9-10
2. Copie token correto de: https://www.mercadopago.com.br
3. Use credenciais de **TESTE** primeiro (não produção)

---

## 📋 Checklist Final

Antes de considerar pronto:

- [ ] SQL foi executado?
- [ ] `gestao-diaria.php` tem 2 linhas de include?
- [ ] `script-gestao-diaria.js` tem 2 validações?
- [ ] F12 não mostra erros?
- [ ] `PlanoManager` inicializa?
- [ ] Planos carregam (F12 > Network)?
- [ ] Limite de mentor funciona?
- [ ] Limite de entrada funciona?
- [ ] Toggle MÊS/ANO funciona?
- [ ] Cartão de teste é aceito?
- [ ] Webhook confirma pagamento?
- [ ] Status no BD muda para `ativa`?

---

## 🎯 Próximas Etapas

Quando tudo funcionar:

1. **Configurar Produção**
   - Mude `MP_ENVIRONMENT` para `production`
   - Use credenciais reais (não teste)

2. **Implementar UI**
   - Botão de "Upgrade" em menu
   - Badge de plano atual
   - Página de gerenciamento de assinatura

3. **Analytics**
   - Relatório de pagamentos
   - Gráfico de receita
   - Dashboard financeiro

---

## 📞 Dúvidas?

Revise os documentos:
- `README_PLANOS.md` - Documentação técnica
- `QUICK_START.md` - Começar rápido
- `PASSO_A_PASSO_INTEGRACAO.md` - Este arquivo anterior

**Sucesso! 🚀**

