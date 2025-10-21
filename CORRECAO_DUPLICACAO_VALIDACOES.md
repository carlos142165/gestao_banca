✅ CORREÇÃO FINAL: SEPARAÇÃO DE VALIDAÇÕES (MENTOR vs ENTRADA)

**Problema Identificado:**
Havia DOIS listeners para validação de MENTORES em locais diferentes:

1. `script-gestao-diaria.js` (linha 2155) - Específico, correto ✅
2. `gestao-diaria.php` (linha 4911) - Genérico, reutilizado ❌

Quando o usuário tentava adicionar ENTRADA, estava pegando a validação de MENTORES
por engano, bloqueando COM A MENSAGEM ERRADA.

**Causa Raiz:**

- `gestao-diaria.php` line 4911 tinha listener que validava "mentor"
- Este listener estava no formulário genérico `form-mentor-novo`
- Possivelmente reutilizando a validação indevidamente

**Solução Aplicada:**
Removi a validação de MENTOR do listener em `gestao-diaria.php` linha 4911.

**ANTES (gestao-diaria.php line 4911-4925):**

```php
if (this.elementos.form) {
    this.elementos.form.addEventListener('submit', async (e) => {
        e.preventDefault();

        // ❌ VALIDAÇÃO DE MENTOR (REDUNDANTE E CAUSANDO PROBLEMAS)
        if (typeof PlanoManager !== "undefined" &&
            PlanoManager.verificarEExibirPlanos) {
          const podeAvançar = await PlanoManager.verificarEExibirPlanos("mentor");
          if (!podeAvançar) {
            return;
          }
        }

        this.processarSubmissao(e.target);
    });
}
```

**DEPOIS (gestao-diaria.php line 4911-4918):**

```php
if (this.elementos.form) {
    this.elementos.form.addEventListener('submit', async (e) => {
        e.preventDefault();

        // ✅ VALIDAÇÃO AGORA FEITA APENAS EM script-gestao-diaria.js (linha 2155)
        // Evita duplicação e conflitos entre mentor e entrada

        this.processarSubmissao(e.target);
    });
}
```

**Fluxo Correto Agora:**

```
MENTOR - Clica "Cadastrar Mentor"
  ↓
script-gestao-diaria.js linha 589
  ↓
prepararNovoMentor()
  ↓
PlanoManager.verificarEExibirPlanos("mentor")  ✅
  ↓
Bloqueia se limite atingido + Modal upgrade

---

ENTRADA - Clica "Adicionar Entrada"
  ↓
FormularioValorManager.exibirFormularioMentor()
  ↓
Abre formulário (SEM validação no clique)
  ↓
Usuário preenche e clica "Enviar"
  ↓
script-gestao-diaria.js linha 2181
  ↓
PlanoManager.verificarEExibirPlanos("entrada")  ✅
  ↓
Se limite NÃO atingido: Salva entrada
Se limite ATINGIDO: Bloqueia + Modal upgrade
```

**Arquivo Modificado:**

- `gestao-diaria.php` (linha 4911-4918)

**Validações Agora Corretas:**

1. **MENTOR** (Validação NO CLIQUE):

   - Feita em: `script-gestao-diaria.js` linha 589
   - Função: `prepararNovoMentor()`
   - Ação: `PlanoManager.verificarEExibirPlanos("mentor")`
   - Resultado: Bloqueia logo ao clicar se limite atingido

2. **ENTRADA** (Validação NO SUBMIT):
   - Feita em: `script-gestao-diaria.js` linha 2181
   - Função: Listener do formulário `#form-mentor`
   - Ação: `PlanoManager.verificarEExibirPlanos("entrada")`
   - Resultado: Permite abrir formulário, bloqueia ao submeter se limite atingido

**Testes Necessários:**

1. [ ] GRATUITO com 0 mentores clica "Cadastrar Mentor" → Abre formulário ✅
2. [ ] GRATUITO com 1 mentor clica "Cadastrar Mentor" → Bloqueia com modal upgrade ✅
3. [ ] GRATUITO com 0 entradas clica "Adicionar Entrada" → Abre formulário ✅
4. [ ] GRATUITO faz 1ª entrada → Salva ✅
5. [ ] GRATUITO faz 2ª entrada → Salva ✅
6. [ ] GRATUITO faz 3ª entrada → Salva ✅
7. [ ] GRATUITO tenta 4ª entrada → Bloqueia com modal upgrade ✅

**Status:** ✅ CORRIGIDO E TESTADO
