# 🔍 GUIA DE FILTROS NO CONSOLE DO NAVEGADOR

## 1. FILTRO NATIVO DO CHROME/FIREFOX/EDGE

### ✅ Como usar:
1. Abra o **Console** (F12)
2. Procure pela **caixa de busca/filtro** (geralmente no topo)
3. Digite o que quer filtrar

### 📝 Exemplos de filtros:

```
📨 notificarNovaMensagem    → Mostra apenas chamadas da função
🔔 Permissão               → Mostra apenas status de permissão
✅ Enviando                → Mostra apenas envios de notificação
🖼️ Imagem                  → Mostra apenas imagens geradas
❌ Erro                    → Mostra apenas erros
```

---

## 2. FILTRO POR TIPO DE MENSAGEM

No console do Chrome/Firefox, há botões de filtro:

```
Todos
ℹ️ Info (azul)
⚠️ Warning (amarelo)
❌ Error (vermelho)
```

**Clique em cada um para filtrar apenas aquele tipo**

---

## 3. FILTRO USANDO CONSOLE GROUPS

Para agrupar mensagens relacionadas, use:

```javascript
console.group("📊 Notificações");
  console.log("📨 Mensagem chegou");
  console.log("🔔 Permissão: granted");
console.groupEnd();
```

Isso agrupa e permite expandir/colapsar

---

## 4. PESQUISA RÁPIDA

### Chrome/Edge:
- **Ctrl + F** dentro do console
- Digite o texto que quer procurar
- Navega com setas ↑↓

### Firefox:
- **Ctrl + F** dentro do console
- Mesmo comportamento

---

## 5. FILTROS AVANÇADOS DO CONSOLE

### Para ver APENAS notificações do sistema:

```javascript
// No console, cole isso:
console.clear();
// Agora só vai ver mensagens novas
```

### Para filtrar por padrão:

```javascript
// Mostra apenas linhas que contêm "notificacoes-sistema"
// Use a caixa de filtro e digite: notificacoes-sistema
```

---

## 6. DICAS PRÁTICAS

### ✅ Melhor abordagem:
1. Abra o Console (F12)
2. Na caixa de **filtro**, digite: `📨`
3. Agora mostra APENAS: `notificarNovaMensagem chamada com:`
4. Você vê quando a função é chamada

### Para ver tudo sobre uma mensagem específica:
1. Filtro: `msg-`
2. Mostra toda atividade dessa mensagem (ID)

### Para ver apenas erros:
1. Use o botão **🔴 Error** (vermelho)
2. Ou filtre por: `❌`

---

## 7. VARIÁVEIS NO CONSOLE

Você também pode inspecionar variáveis:

```javascript
// Digite no console:
NotificacoesSistema.permissaoNotificacao  // Verifica permissão
Notification.permission                     // Status real do navegador
```

---

## 8. COPIAR LOGS

Para copiar todos os logs:

1. **Chrome**: Clique direito → **Copiar logs**
2. **Firefox**: Clique direito → **Exportar logs visíveis**
3. **Edge**: Mesmo do Chrome

---

## 📊 TABELA DE FILTROS RÁPIDOS

| Filtro | O que mostra |
|--------|-------------|
| `📨` | Chamadas de função |
| `🔔` | Status de permissão |
| `✅` | Confirmações |
| `❌` | Erros |
| `🖼️` | Imagens geradas |
| `⚽` | Tipo de esporte |
| `🚩` | Tipo CANTOS |
| `notificarNovaMensagem` | Função específica |
| `Notification.permission` | Status de permissão |
| `msg-` | Por ID da mensagem |

---

## 🎯 EXEMPLO PRÁTICO

**Para debugar quando mensagem chega:**

```
1. Abra console (F12)
2. No filtro, digita: 📨
3. Espera mensagem chegar
4. Você vê exatamente quando função foi chamada
5. Clica na linha para ver detalhes
6. Expande o objeto `msg` para ver dados
```

---

## 💡 DICA PROFISSIONAL

**Combine filtro + níveis de log:**

1. Clique **ℹ️ Info** para ver só informações (sem erros)
2. Filtre por: `📨` 
3. Agora só mostra chamadas de funções, nada de erro

---

## ❓ SE NADA APARECER

Se o console está vazio quando mensagem chega:

1. Verifique se a página tem `notificacoes-sistema.js` carregado
   - Filtro: `notificacoes-sistema.js` deve aparecer
   
2. Se não aparecer, arquivo não carregou
   - Verifique se está incluído na página

3. Se aparecer mas nada de `📨`:
   - Função não está sendo chamada
   - Verifique se está em `telegram-mensagens.js`

---

## 📱 NO MOBILE

**Debugar notificações no celular:**

```
Android:
- Chrome DevTools Remote (connect via USB)
- Firefox Developer Edition com remote debugging

iOS:
- Safari Developer Tools (conectar no Mac)
```

---

## 🎓 RESUMO RÁPIDO

```
F12              → Abre Console
Ctrl+F           → Busca dentro do console
Filtro box       → Digita o que quer filtrar
🔴 Error button  → Mostra só erros
ℹ️ Info button   → Mostra só info
Clique direito   → Opções avançadas
```

---

**Use filtro `📨` para acompanhar notificações!** 🎯
