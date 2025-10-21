# 🔧 CORREÇÃO APLICADA - VALIDAÇÃO DE LIMITE

## ⚠️ PROBLEMA IDENTIFICADO

O modal de planos não estava abrindo quando você tentava cadastrar o 2º mentor. Estava abrindo o modal de cadastro de mentor normalmente.

## 🎯 CAUSA

A função `verificarLimiteMentores()` estava usando `$conexao` em vez de `global $conn`, causando erro no banco de dados.

## ✅ SOLUÇÃO APLICADA

Corrigido `config_mercadopago.php` - todas as funções agora usam:
```php
global $conn;  // Correto
// Em vez de:
require_once 'config.php';
// E usar: $conexao->prepare()  // Errado
```

### Funções Corrigidas:
- ✅ `criarPreferencia()`
- ✅ `salvarCartao()`
- ✅ `criarAssinatura()`
- ✅ `atualizarUsuarioAssinatura()`
- ✅ `planoExpirou()`
- ✅ `obterPlanoAtual()`
- ✅ `obterPlanoGratuito()`
- ✅ `verificarLimiteMentores()`
- ✅ `verificarLimiteEntradas()`

---

## 🧪 TESTE AGORA

### Teste 1: Verificar Funcionamento

```
Abra: http://localhost/gestao_banca/teste-limite.php

Deve mostrar:
✅ Plano: GRATUITO
   - Limite de mentores: 1
   - Limite de entradas: 3
✅ Pode adicionar mentor (ou ❌ Atingiu limite)
✅ Pode adicionar entrada (ou ❌ Atingiu limite)
   - Mentores cadastrados: X
   - Entradas de hoje: Y
```

### Teste 2: Modal Abrir Corretamente

1. Abra: `http://localhost/gestao_banca/gestao-diaria.php`
2. Clique: **"Novo Mentor"** (ou "Cadastrar Mentor")
3. **Esperado:** ❌ Modal de planos deve abrir (em vez de modal de cadastro)
4. **Mensagem:** "Você atingiu o limite de mentores no plano GRATUITO"

### Teste 3: Testar com Plano Pago

1. Faça upgrade para plano PRATA (5 mentores)
2. Tente cadastrar mentor novamente
3. **Esperado:** ✅ Modal de cadastro de mentor abre normalmente

---

## 📊 DEBUG - Caso Ainda Não Funcione

Se ainda não funcionar, teste:

```javascript
// F12 Console:
await PlanoManager.verificarEExibirPlanos('mentor');
```

**Respostas possíveis:**
- ✅ `true` = Pode prosseguir (cadastro aberto)
- ❌ `false` = Não pode, modal abre

---

## 🔍 Diagnosticar Melhor

Abra: `http://localhost/gestao_banca/debug-limite.php`

Deve retornar JSON com:
```json
{
  "sucesso": true,
  "usuario": {...},
  "plano": {...},
  "mentores": {
    "cadastrados": 1,
    "limite": 1,
    "pode_adicionar": false
  },
  "entradas": {
    "cadastradas": 2,
    "limite": 3,
    "pode_adicionar": true
  }
}
```

---

## ✅ PRÓXIMOS PASSOS

1. **Teste em:** `http://localhost/gestao_banca/teste-limite.php`
2. **Debug em:** `http://localhost/gestao_banca/debug-limite.php`
3. **Tente cadastrar mentor:**  `http://localhost/gestao_banca/gestao-diaria.php`
4. **Esperado:** Modal de planos abre automaticamente

---

## 💡 SE TIVER DÚVIDA

Abra F12 (Developer Tools) > Network e procure por:
- `verificar-limite.php` → Deve retornar `pode_prosseguir: false`
- `obter-planos.php` → Deve retornar os 4 planos

---

**Teste agora e me avise se funcionou! 🚀**

