# 🧪 TESTE - Checkout Mercado Pago

## ✅ MUDANÇA APLICADA

Quando usuário clica "Contratar", agora vai direto para Mercado Pago em vez de abrir modal local.

---

## 🚀 TESTE EM 5 PASSOS

### Passo 1: Limpar Cache
```
Pressione: Ctrl+Shift+Del
Selecione: "Todos os horários"
Clique: "Limpar dados"
Feche e reabra navegador
```

### Passo 2: Abrir Sistema
```
URL: http://localhost/gestao/gestao_banca/gestao-diaria.php
Login: Usuário GRATUITO
```

### Passo 3: Abrir DevTools
```
Pressione: F12
Vá para: Console
```

### Passo 4: Trigger Modal
```
1. Na tela de Gestão Diária
2. Tente adicionar 4ª entrada
3. Modal "Escolha seu Plano" aparece
```

### Passo 5: Clicar em "Contratar"
```
1. Clique no botão "Contratar Agora" de qualquer plano
2. Verifique console por:
   ✅ "Selecionado: [NOME DO PLANO]..."
   ✅ "Enviando ao Mercado Pago..."
   ✅ "Redirecionando para Mercado Pago:"
3. Esperado: URL muda para https://checkout.mercadopago.com/...
```

---

## ✅ RESULTADO ESPERADO

### Na Console (F12)
```
📋 Selecionado: PRATA - R$ 25.90 - mes
💳 Enviando ao Mercado Pago...
✅ Redirecionando para Mercado Pago: https://checkout.mercadopago.com/checkout/v1/resumo/...
```

### Na Página
```
Redirecionamento automático para:
https://checkout.mercadopago.com/...

Abre página do Mercado Pago com:
┌─────────────────────────────────────┐
│ Resumo do Pedido                     │
│ Prata - R$ 25,90                    │
│ Escolha método de pagamento:        │
│ ☐ Cartão de Crédito/Débito         │
│ ☐ Pix                              │
│ ☐ Boleto                           │
│ [Voltar] [Continuar]               │
└─────────────────────────────────────┘
```

---

## ❌ SE NÃO FUNCIONAR

### Verificação 1: Console Error
```
F12 → Console
Se houver erro em VERMELHO = Copie e compartilhe
```

### Verificação 2: Função Existe
```
F12 → Console
Digite: PlanoManager.processarPagamentoMercadoPago
Pressione Enter
Deve mostrar: ƒ processarPagamentoMercadoPago()
```

### Verificação 3: Backend Responde
```
F12 → Network
Clicar em "Contratar"
Procure por POST a "processar-pagamento.php"
Status deve ser 200
Response deve ter "preference_url"
```

### Verificação 4: Cache
```
Ctrl+Shift+Del
Limpar TUDO
F5 para recarregar
Tente novamente
```

---

## 🎯 CHECKLIST

- [ ] Cache limpo
- [ ] Página recarregada
- [ ] DevTools aberto (F12)
- [ ] Usuário GRATUITO logado
- [ ] 4ª entrada triggerada
- [ ] "Contratar" clicado
- [ ] Mensagens no console vistas
- [ ] Redirecionado para Mercado Pago

---

## 📊 FLUXO VISUAL

```
ANTES ❌
Clica "Contratar"
    ↓
Abre modal local
    ├─ Abas: Cartão, PIX, Salvos
    └─ Formulário local

DEPOIS ✅
Clica "Contratar"
    ↓
Redireciona (window.location.href)
    ↓
Mercado Pago checkout
    ├─ Checkout oficial
    └─ Todos métodos disponíveis
```

---

## 📞 INFORME RESULTADO

Após testar, diga:
- ✅ Funcionou (foi para Mercado Pago)
- ❌ Não funcionou (modal ainda abre)
- ⚠️ Erro (copie mensagem do console)

---

**Tempo: 3 minutos**

**Ação: TESTE AGORA!**
