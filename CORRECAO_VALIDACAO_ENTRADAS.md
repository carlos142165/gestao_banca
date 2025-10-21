✅ CORREÇÃO DA LÓGICA DE VALIDAÇÃO DE ENTRADAS

**Problema Identificado:**
A validação de entradas estava acontecendo NO CLIQUE do botão "Adicionar Entrada",
impedindo até a primeira entrada.

**Solução Aplicada:**

- ✅ Removida a validação de entradas em `FormularioValorManager.exibirFormularioMentor()`
- ✅ A validação de entradas continua apenas no submit do formulário
- ✅ Isso permite abrir o formulário mas bloqueia ao salvar se limite atingido

**Arquivo Modificado:**
`js/script-gestao-diaria.js` (linha ~5807)

**Comportamento Esperado:**

1️⃣ Usuário GRATUITO clica "Adicionar Entrada" (1ª vez)
→ ✅ Formulário abre normalmente
→ Usuário preenche e clica "Enviar"
→ ✅ Entrada é salva (contagem = 1/3)

2️⃣ Clica "Adicionar Entrada" novamente (2ª vez)
→ ✅ Formulário abre normalmente
→ Usuário preenche e clica "Enviar"
→ ✅ Entrada é salva (contagem = 2/3)

3️⃣ Clica "Adicionar Entrada" novamente (3ª vez)
→ ✅ Formulário abre normalmente
→ Usuário preenche e clica "Enviar"
→ ✅ Entrada é salva (contagem = 3/3)

4️⃣ Clica "Adicionar Entrada" novamente (4ª tentativa)
→ ✅ Formulário abre normalmente
→ Usuário preenche e clica "Enviar"
→ ❌ Validação bloqueia (limite atingido)
→ 🎯 Modal "Escolha seu Plano" é aberto
→ Toast: "Você atingiu o limite de entradas diárias no plano GRATUITO"

5️⃣ Próximo dia (21/10/2025)
→ ✅ Contador reseta (CURDATE())
→ ✅ Usuário pode fazer mais 3 entradas

**Contraposição com Mentores:**
Mentores ✅ ainda bloqueia NO CLIQUE (correto, pois limite é 1)
Entradas ✅ agora bloqueia APENAS NO SUBMIT (correto, pois limite é 3/dia)

**Testes Necessários:**

1. [ ] Usuário GRATUITO consegue fazer 1ª entrada
2. [ ] Consegue fazer 2ª entrada
3. [ ] Consegue fazer 3ª entrada
4. [ ] 4ª entrada é bloqueada com modal de upgrade
5. [ ] Próximo dia reseta o contador
