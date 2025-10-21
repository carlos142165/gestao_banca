# 🔧 DIAGNÓSTICO - Modal Planos Vazia

## ❌ PROBLEMA REPORTADO
Modal abre, mas não mostra os 4 planos (fica vazia)

## 🔍 RAIZ DO PROBLEMA IDENTIFICADA

### Timing Issue (Race Condition)
```
Sequência ANTES (Incorreta):
1. verificarEExibirPlanos() é chamado
2. Chama abrirModalPlanos() IMEDIATAMENTE
3. Modal abre
4. ❌ Planos NÃO foram renderizados ainda
5. Resultado: Modal vazia

Sequência DEPOIS (Corrigida):
1. verificarEExibirPlanos() é chamado
2. ✅ Verifica se planos já carregados
3. ✅ Se não, carrega e renderiza AGORA
4. ✅ Depois chama abrirModalPlanos()
5. Resultado: Modal abre COM planos visíveis
```

## ✅ CORREÇÕES APLICADAS

### 1. Função `verificarEExibirPlanos()` em `js/plano-manager.js`

**ANTES:**
```javascript
async verificarEExibirPlanos(acao = "mentor") {
    // ... fetch verificar-limite.php ...
    if (!data.pode_prosseguir) {
        this.abrirModalPlanos(); // ❌ Abre vazia!
        return false;
    }
}
```

**DEPOIS:**
```javascript
async verificarEExibirPlanos(acao = "mentor") {
    // ✅ NOVO: Garantir que planos estão prontos
    if (!this.planos || this.planos.length === 0) {
        console.log("⏳ Planos não carregados ainda, aguardando...");
        await this.carregarPlanos();
        this.renderizarPlanos();
    }
    
    // ... fetch verificar-limite.php ...
    if (!data.pode_prosseguir) {
        this.abrirModalPlanos(); // ✅ Abre COM planos!
        return false;
    }
}
```

### 2. Script Tag em `gestao-diaria.php`

**ANTES:**
```html
<script src="js/plano-manager.js"></script>
```

**DEPOIS:**
```html
<script src="js/plano-manager.js" defer></script>
```

**Por quê:** O `defer` garante que:
- HTML é parseado completamente ANTES de executar o script
- Container #planosGrid existe quando PlanoManager tenta renderizar
- Todos os listeners do DOM estão prontos

## 📋 FLUXO CORRIGIDO

### Cenário: Usuário GRATUITO tenta adicionar 4ª entrada

```
1. Usuário clica "Cadastrar" (4ª entrada)
   ↓
2. gestao-diaria.php processarSubmissao() executa
   ↓
3. Valida: await PlanoManager.verificarEExibirPlanos('entrada')
   ↓
4. ✅ NOVO: Verifica if (planos.length === 0)
   ├─ Se sim: await carregarPlanos() + renderizarPlanos()
   └─ Se não: Continua com planos já carregados
   ↓
5. Fetch verificar-limite.php?acao=entrada
   ├─ Se pode_prosseguir = true: Permite entrada (não atingiu limite)
   └─ Se pode_prosseguir = false: Vai para passo 6
   ↓
6. ✅ NOVO: Verifica if (renderizarPlanos()) JÁ FOI CHAMADO
   ↓
7. this.abrirModalPlanos()
   ↓
8. ✅ Modal abre COM 4 planos visíveis:
   ┌──────────────────────────────────────┐
   │ Escolha seu Plano                    │
   ├──────────────────────────────────────┤
   │ [MÊS] [ANO]                          │
   ├──────────────────────────────────────┤
   │ ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐ │
   │ │GRATU │ │PRATA │ │OURO  │ │DIAMA │ │
   │ │R$ 0  │ │R$ 25 │ │R$ 39 │ │R$ 59 │ │
   │ │1 M   │ │5 M   │ │10 M  │ │∞ M   │ │
   │ │3 E   │ │15 E  │ │30 E  │ │∞ E   │ │
   │ └──────┘ └──────┘ └──────┘ └──────┘ │
   └──────────────────────────────────────┘
```

## 🧪 COMO TESTAR

### Teste 1: Verificar Arquivo de Teste
```
1. Abra: http://localhost/gestao/gestao_banca/teste-modal-planos.php
2. Clique: "📋 Testar Carregamento de Planos"
3. Deve listar os 4 planos com OK
```

### Teste 2: Verificar Modal Real
```
1. Abra sistema normalmente
2. Login com GRATUITO
3. Adicione 3 entradas (devem ser salvas)
4. Tente adicionar 4ª entrada
5. ✅ Modal deve abrir COM 4 planos visíveis
6. Se vazia ainda, abra Console (F12) e procure por erros
```

### Teste 3: Verificar Console
```
F12 → Console → Filtro: "planar" ou "modal"

Deve ver:
✅ Planos carregados com sucesso: (4) [{…}, {…}, {…}, {…}]
📊 Renderizando 4 planos
✅ PlanoManager inicializado com sucesso
```

## 🐛 SE AINDA NÃO FUNCIONAR

### Checklist de Diagnóstico

#### 1️⃣ Verificar Backend
```bash
curl http://localhost/gestao/gestao_banca/obter-planos.php
```

Deve retornar:
```json
{
  "success": true,
  "planos": [
    {
      "id": 1,
      "nome": "GRATUITO",
      "preco_mes": "0.00",
      "preco_ano": "0.00",
      "mentores_limite": 1,
      "entradas_diarias": 3,
      "icone": "fas fa-home",
      "cor_tema": "#95a5a6"
    },
    ...
  ]
}
```

#### 2️⃣ Verificar CSS Grid
```
F12 → Elements → Procure: id="planosGrid"

Deve ter:
<div class="planos-grid" id="planosGrid">
  <div class="plano-card">...</div>
  <div class="plano-card">...</div>
  <div class="plano-card">...</div>
  <div class="plano-card">...</div>
</div>
```

CSS aplicado:
```css
display: grid
grid-template-columns: repeat(4, 1fr)  ✅
gap: 25px
```

#### 3️⃣ Verificar HTML
```
F12 → Elements → Procure: id="modal-planos"

Deve existir:
<div id="modal-planos" class="modal-planos" ...>
  ...
  <div id="planosGrid" class="planos-grid">
    <!-- Cards aqui -->
  </div>
  ...
</div>
```

#### 4️⃣ Verificar JavaScript
```
F12 → Console → Digite:
PlanoManager.planos.length

Deve retornar: 4 (ou número de planos)
```

## 📊 ARQUIVOS MODIFICADOS

| Arquivo | Mudança |
|---------|---------|
| `js/plano-manager.js` | Adicionada verificação de planos em `verificarEExibirPlanos()` |
| `gestao-diaria.php` | Adicionado `defer` ao script `plano-manager.js` |
| `teste-modal-planos.php` | ✨ NOVO - Teste interativo da modal |

## 🎯 STATUS
- ✅ Causa identificada: Race condition no timing
- ✅ Solução aplicada: Renderizar planos ANTES de abrir modal
- ✅ Teste criado para verificar
- ⏳ Aguardando confirmação do usuário

---

**Próximo Passo:** Teste o sistema com usuário GRATUITO adicionando 4ª entrada. Deve ver modal com 4 planos lado a lado.
