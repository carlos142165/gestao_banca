# ⚡ MUDANÇA RÁPIDA - Checkout Mercado Pago

## ✅ O QUE FOI MUDADO

**Antes:** Clicava "Contratar" → Abre modal local de pagamento ❌

**Depois:** Clica "Contratar" → Redireciona para Mercado Pago ✅

---

## 🔧 COMO FUNCIONA AGORA

```
Usuário clica "Contratar Agora"
    ↓
JavaScript envia ao backend:
{
  id_plano: 2,
  periodo: "mes"
}
    ↓
Backend cria preferência no Mercado Pago
    ↓
Backend retorna URL:
https://checkout.mercadopago.com/...
    ↓
window.location.href = url
    ↓
✅ Usuário vai para Mercado Pago
```

---

## 🧪 TESTE

### Passo 1: Abra Sistema
```
URL: http://localhost/gestao/gestao_banca/gestao-diaria.php
```

### Passo 2: Tente 4ª Entrada (GRATUITO)
```
Modal "Escolha seu Plano" aparece
```

### Passo 3: Clique "Contratar"
```
Esperado: Redireciona para Mercado Pago ✅
```

### Passo 4: Verifique Console (F12)
```
✅ "Selecionado: PRATA..."
✅ "Enviando ao Mercado Pago..."
✅ "Redirecionando para..."
```

---

## 📊 RESULTADO

| Antes | Depois |
|-------|--------|
| ❌ Modal local | ✅ Mercado Pago |
| ❌ 2 passos | ✅ 1 passo |
| ❌ Menos seguro | ✅ Mais seguro |

---

## 📁 ARQUIVOS MODIFICADOS

- ✏️ `js/plano-manager.js`
  - Função `selecionarPlano()` modificada
  - Adicionada função `processarPagamentoMercadoPago()`

---

**Teste agora clicando em "Contratar"! 🚀**
