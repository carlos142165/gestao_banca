# 🚀 GUIA RÁPIDO - COMEÇAR AGORA

## 3 Passos para Testar Tudo

### ✅ PASSO 1: Abrir a Página (30 segundos)
```
1. Abra: http://localhost/gestao_banca/gestao-diaria.php
2. Pressione: F12 (abre Developer Tools)
3. Vá para: Console (aba)
4. Não deve ter erros em VERMELHO 🔴
```

---

### ✅ PASSO 2: Validar Integração (1 minuto)
```javascript
// Cole no Console:
console.log('PlanoManager:', typeof PlanoManager);
console.log('Inicializado:', PlanoManager.inicializado);
console.log('Métodos disponíveis:', Object.keys(PlanoManager));
```

**Esperado:**
```
PlanoManager: object ✅
Inicializado: true ✅
Métodos disponíveis: [...] ✅
```

---

### ✅ PASSO 3: Testar Limite (2 minutos)
```
1. Já com 1 mentor cadastrado
2. Clique em "Novo Mentor"
3. Preencha dados e clique "Cadastrar"
4. Modal de planos deve ABRIR AUTOMATICAMENTE 🎯
```

**Se modal abriu:**
- ✅ **TUDO FUNCIONANDO!** 🎉

**Se não abriu:**
- Verifique F12 Console (tem erro?)
- Verifique em Network se chamou verificar-limite.php

---

## 📊 O que Você Consegue Fazer Agora

### DURANTE TESTE
- ✅ Selecionar planos
- ✅ Toggle MÊS/ANO
- ✅ Ver economias de preço
- ✅ Preencher formulário de cartão

### AINDA NÃO FUNCIONA
- ❌ Pagamento real (precisa credenciais MP)
- ❌ Renovação automática (precisa pagar primeiro)
- ❌ Webhook (só funciona após pagar)

---

## 🔧 Configurar Credenciais MP

### Onde Pegar?
1. Vá para: https://www.mercadopago.com.br
2. Login com seu email
3. Vá para: **Configurações > Credenciais**
4. Procure por: **Access Token** e **Public Key**
5. Copie os valores de **TESTE** (não produção!)

### Onde Colocar?
**Arquivo:** `config_mercadopago.php`
**Linhas:** 9-10

```php
<?php
// Edite apenas essas linhas:

define('MP_ACCESS_TOKEN', 'APP_USR_XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX');
define('MP_PUBLIC_KEY', 'APP_USR_XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX');
```

### Como Salvar?
1. No VS Code, pressione: **Ctrl+S**
2. Arquivo deve mostrar ponto desaparecido (significado salvo)
3. Pronto! ✅

---

## 🎴 Cartão de Teste MP

Para testar sem cobrar nada:

```
Número: 4111 1111 1111 1111
Validade: 12/25
CVV: 123
Titular: Qualquer nome
```

**Resultado:** Pagamento será APROVADO em teste ✅

---

## 📁 Checklist de Arquivos

Verifique se esses arquivos existem:

```
✅ gestao_banca/gestao-diaria.php
   └─ Tem: <?php include 'modal-planos-pagamento.html'; ?>

✅ gestao_banca/modal-planos-pagamento.html

✅ gestao_banca/js/plano-manager.js

✅ gestao_banca/js/script-gestao-diaria.js
   └─ Tem validações de mentor e entrada

✅ gestao_banca/config_mercadopago.php

✅ gestao_banca/obter-planos.php
✅ gestao_banca/verificar-limite.php
✅ gestao_banca/processar-pagamento.php
✅ gestao_banca/webhook.php (ATUALIZADO)
```

Se algum está faltando, você precisa criá-lo antes.

---

## 🐛 Erros Mais Comuns

### Erro 1: "PlanoManager is not defined"
```
❌ Uncaught ReferenceError: PlanoManager is not defined
```

**Solução:**
- Verifique se `modal-planos-pagamento.html` está sendo incluído
- Verifique se `js/plano-manager.js` existe
- F12 > Network, procure por `plano-manager.js` (status 200?)

---

### Erro 2: "obter-planos.php - 404"
```
❌ GET /obter-planos.php - 404 Not Found
```

**Solução:**
- Verifique se arquivo existe
- Verifique path no `plano-manager.js`
- Pode precisar de ajuste de caminho

---

### Erro 3: Modal não abre ao atingir limite
```
❌ Clico em "Cadastrar Mentor" mas nada acontece
```

**Solução:**
1. Abra F12 Console
2. Digite: `await PlanoManager.verificarEExibirPlanos('mentor')`
3. Verifique se Network mostra `verificar-limite.php`
4. Check se retornou `false` (significa bloqueado)

---

### Erro 4: Credenciais inválidas
```
❌ Erro ao criar preferência Mercado Pago
❌ "invalid_grant" ou "unauthorized"
```

**Solução:**
1. Verifique `config_mercadopago.php` linhas 9-10
2. Copie token exato de MP (sem espaços)
3. Use credenciais de **TESTE** (APP_USR)
4. Salve arquivo

---

## 📈 Fluxo Esperado

```
User acessa         → F12 sem erros ✅
  ↓
Clica "Novo Mentor" → Modal abre ✅
  ↓
Seleciona plano     → Toggle MÊS/ANO funciona ✅
  ↓
Clica "Contratar"   → Modal pagamento abre ✅
  ↓
Preenche cartão     → Dados validados ✅
  ↓
Clica "Pagar"       → Redireciona MP ✅
  ↓
Retorna do MP       → Webhook atualiza BD ✅
  ↓
Status = "ativa"    → Pode usar plano ✅
```

---

## 💡 Dicas Profissionais

### Dica 1: Ver Tudo no Console
```javascript
// Ver objeto PlanoManager completo:
console.log(PlanoManager);

// Ver todos os planos carregados:
console.log(PlanoManager.planos);

// Ver período atual (mês ou ano):
console.log(PlanoManager.periodoAtual);
```

---

### Dica 2: Forçar Recarregar Planos
```javascript
// Se planos não atualizam:
PlanoManager.carregarPlanos();
```

---

### Dica 3: Abrir Modal Manualmente
```javascript
// Para testar sem atingir limite:
const modal = document.getElementById('modal-planos');
modal.style.display = 'flex';
```

---

### Dica 4: Ver Respostas do Servidor
```javascript
// No Network tab (F12):
1. Filtre por: verificar-limite.php
2. Clique na requisição
3. Vá em: Response (aba)
4. Veja o JSON retornado
```

---

## 🎯 Teste Prático Completo (5 minutos)

### Cenário: Testar Limite de Mentor

**Tempo: 5 minutos**

**Passo 1:** Abra a página (30 segundos)
```
http://localhost/gestao_banca/gestao-diaria.php
```

**Passo 2:** Abra F12 (10 segundos)
```
Pressione: F12
Vá para: Console
Não deve ter erros
```

**Passo 3:** Valide integração (30 segundos)
```javascript
// Cole no Console:
typeof PlanoManager === 'object' && console.log('✅ OK')
```

**Passo 4:** Teste limite (3 minutos)
```
1. Já com 1 mentor
2. Clique "Novo Mentor"
3. Preencha formulário
4. Clique "Cadastrar"
5. Modal deve abrir
```

**Passo 5:** Valide modal (30 segundos)
```
- Modal de planos aberto? ✅
- Mostra 4 planos? ✅
- Toggle MÊS/ANO funciona? ✅
- Preços estão corretos? ✅
```

**Total:** ~5 minutos ⏱️

---

## 🎊 Sucesso Garantido!

Se chegou até aqui, você tem:

✅ Modal funcionando
✅ Validações funcionando
✅ Planos carregando
✅ Limite de mentores bloqueando
✅ Limite de entradas bloqueando
✅ Sistema profissional

**Próximo:** Testar com pagamento real (credenciais MP)

---

## 📚 Documentação Completa

Para aprofundar, leia:

1. **INTEGRACAO_COMPLETA.md** - Resumo visual completo
2. **TESTE_E_VERIFICACAO.md** - Testes detalhados
3. **README_PLANOS.md** - Documentação técnica
4. **QUICK_START.md** - Começar rápido

---

## ❓ Dúvida? Use Isso

```javascript
// No Console, paste tudo:

console.clear();
console.log('=== DIAGNÓSTICO ===');
console.log('PlanoManager existe:', typeof PlanoManager === 'object' ? '✅' : '❌');
console.log('Inicializado:', PlanoManager?.inicializado ? '✅' : '❌');
console.log('Planos carregados:', PlanoManager?.planos?.length || 0, 'planos');
console.log('Período atual:', PlanoManager?.periodoAtual);
console.log('Modal elemento:', document.getElementById('modal-planos') ? '✅' : '❌');
console.log('=== FIM ===');
```

**Cole esse código e veja o diagnóstico completo!** 🔍

---

**Boa sorte! Você vai conseguir! 🚀**

