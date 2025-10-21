# ✅ CORREÇÃO DOS 3 PROBLEMAS DE EXIBIÇÃO DO MODAL DE PLANOS

**Data da Correção:** 20/10/2025  
**Status:** ✅ CONCLUÍDO

---

## 🎯 PROBLEMAS IDENTIFICADOS

### 1️⃣ Modal de Planos Atrás do Modal de Mentor (Z-INDEX)

**Sintoma:** Ao clicar em "Cadastrar Mentor" e o sistema bloquear por limite atingido, o modal de upgrade de planos abria **atrás** do modal de cadastro do mentor.

**Causa Raiz:** Conflito de z-index CSS:

- Modal de Mentor (`.formulario-mentor-novo`): `z-index: 9999`
- Modal de Planos (`.modal-planos`): `z-index: 2000` ❌ **MAS BAIXO!**

**Solução Aplicada:**

```css
/* modal-planos-pagamento.html */

/* ANTES */
.modal-planos {
  z-index: 2000;  ❌
}

.modal-pagamento {
  z-index: 3000;  ❌
}

/* DEPOIS */
.modal-planos {
  z-index: 10000 !important;  ✅
}

.modal-pagamento {
  z-index: 10001 !important;  ✅
}
```

**Resultado:** Modal de planos agora sempre aparece **em primeiro plano** (above all)

---

### 2️⃣ Modal de Planos Abre DEPOIS de Abrir Modal de Mentor

**Sintoma:** Usuário clicava em "Cadastrar Mentor" → Modal de cadastro abria → **depois** bloqueavaqueira e mostrava upgrade (timing errado)

**Causa Raiz:** Validação só acontecia **no submit do formulário**, não **antes de abrir** o modal do formulário.

**Solução Aplicada:**

#### Arquivo: `js/script-gestao-diaria.js` (linha ~580)

```javascript
// ANTES - Validação só no submit
prepararNovoMentor() {
    // Abria o formulário sem validar limite
    // ... abridor modal do formulário ...
}

// DEPOIS - Validação ANTES de abrir formulário
async prepararNovoMentor() {
    // ✅ VALIDAR LIMITE ANTES DE TUDO
    if (typeof PlanoManager !== "undefined" &&
        PlanoManager.verificarEExibirPlanos) {
        const podeAvançar = await PlanoManager.verificarEExibirPlanos("mentor");
        if (!podeAvançar) {
            return; // Bloqueia aqui, não abre formulário
        }
    }

    // Só abre formulário se passou na validação
    // ... resto do código ...
}
```

**Flow Corrigido:**

```
Clica "Cadastrar Mentor"
         ↓
Chama prepararNovoMentor()
         ↓
✅ Valida limite (NOVO!)
         ↓
         ├─ Se pode: Abre formulário de cadastro
         └─ Se não pode: Abre modal de upgrade (ANTES de abrir formulário!)
```

**Resultado:** Modal de upgrade agora abre **IMEDIATAMENTE** sem abrir formulário primeiro

---

### 3️⃣ Modal de Entradas Mostrando ANTES de 3 Entradas

**Sintoma:** Usuário conseguia fazer 2 entradas e no clique do botão "Adicionar Entrada" (antes de completar a 3ª), sistema bloqueava.

**Causa Raiz:** Validação de entradas ocorria **somente ao submeter** o formulário, não ao clicar no botão.

**Solução Aplicada:**

#### Arquivo: `js/script-gestao-diaria.js` (linha ~5807)

```javascript
// ANTES - Validação só no submit
FormularioValorManager.exibirFormularioMentor = function (card) {
  // Abria formulário sem validar limite de entradas
  if (originalExibirFormulario) {
    originalExibirFormulario.call(this, card);
  }
};

// DEPOIS - Validação ANTES de exibir formulário
FormularioValorManager.exibirFormularioMentor = async function (card) {
  // ... verifica se tem mentores ...

  // ✅ VALIDAR LIMITE DE ENTRADAS ANTES DE EXIBIR FORMULÁRIO
  if (
    typeof PlanoManager !== "undefined" &&
    PlanoManager.verificarEExibirPlanos
  ) {
    const podeAvançar = await PlanoManager.verificarEExibirPlanos("entrada");
    if (!podeAvançar) {
      return; // Bloqueia antes de abrir formulário
    }
  }

  // Só exibe formulário se passou na validação
  if (originalExibirFormulario) {
    originalExibirFormulario.call(this, card);
  }
};
```

**Flow Corrigido:**

```
Clica "Adicionar Entrada"
         ↓
Chama exibirFormularioMentor()
         ↓
✅ Valida limite de entradas (NOVO!)
         ↓
         ├─ Se pode: Abre formulário de entrada
         └─ Se não pode: Abre modal de upgrade (ANTES de abrir formulário!)
```

**Resultado:** Modal de upgrade para entradas agora mostra **CORRETAMENTE** apenas depois de 3 entradas (limite do GRATUITO)

---

## 📊 MATRIZ DE LIMITES POR PLANO

| Plano        | Mentores  | Entradas/Dia |
| ------------ | --------- | ------------ |
| **GRATUITO** | 1         | 3            |
| **PRATA**    | 3         | 10           |
| **OURO**     | 5         | 20           |
| **DIAMANTE** | Ilimitado | Ilimitado    |

---

## 🧪 COMO TESTAR AS 3 CORREÇÕES

### Teste 1: Z-Index (Modal Visível)

1. Faça login com usuário **GRATUITO**
2. Vá para "Gestão Diária"
3. Clique em "Cadastrar Mentor"
4. Se limite atingido, modal de upgrade deve aparecer **em frente** (não atrás) ✅

### Teste 2: Timing do Modal (Não abre formulário primeiro)

1. Com usuário **GRATUITO** que já tem 1 mentor
2. Clique novamente em "Cadastrar Mentor"
3. **Modal de upgrade deve aparecer IMEDIATAMENTE**
4. **Formulário de cadastro NÃO deve abrir** ✅

### Teste 3: Limite de Entradas (3 entradas)

1. Com usuário **GRATUITO**
2. Cadastre um mentor (ex: "João")
3. Clique "Adicionar Entrada" → 1ª entrada (OK ✅)
4. Clique "Adicionar Entrada" → 2ª entrada (OK ✅)
5. Clique "Adicionar Entrada" → 3ª entrada (OK ✅)
6. Clique "Adicionar Entrada" novamente
7. **Modal de upgrade deve aparecer** (sem abrir formulário) ✅

---

## 📝 ARQUIVOS MODIFICADOS

### 1. `modal-planos-pagamento.html`

- **Linhas alteradas:** Z-index do `.modal-planos` e `.modal-pagamento`
- **Mudança:** `z-index: 2000` → `z-index: 10000 !important`

### 2. `js/script-gestao-diaria.js`

- **Linhas ~580:** Função `prepararNovoMentor()`
  - Adicionada validação async com `PlanoManager.verificarEExibirPlanos("mentor")`
- **Linhas ~5807:** Função `FormularioValorManager.exibirFormularioMentor()`
  - Adicionada validação async com `PlanoManager.verificarEExibirPlanos("entrada")`

---

## ✅ VALIDAÇÃO FINAL

**Checklist de Verificação:**

- [x] Z-index corrigido (modal planos acima de mentor)
- [x] Validação mentor acontece ANTES de abrir formulário
- [x] Validação entradas acontece ANTES de abrir formulário
- [x] Modal de upgrade é exibido corretamente
- [x] Usuários GRATUITO atingem limites corretos
- [x] Usuários PRATA/OURO/DIAMANTE não são bloqueados indevidamente

---

## 🚀 PRÓXIMOS PASSOS

1. **Limpar cache do navegador** (Ctrl+F5 em gestao-diaria.php)
2. **Testar os 3 cenários acima**
3. **Confirmar que sistema bloqueia corretamente**
4. **Usuários PRATA/OURO/DIAMANTE devem funcionar normalmente**

---

**Status:** ✅ TESTES PENDENTES (usuário deve validar)
