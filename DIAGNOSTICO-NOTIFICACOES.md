# 🔔 Diagnóstico do Sistema de Notificações

## ❌ Problema Encontrado

As notificações não estavam aparecendo quando uma mensagem chegava. Após investigação, foi identificado **um problema de ordem de carregamento dos scripts JavaScript**.

### Causa Raiz
No arquivo `bot_aovivo.php`:
- **`telegram-mensagens.js`** era carregado na linha 4679
- **`notificacoes-sistema.js`** era carregado na linha 5520 (DEPOIS)

Quando `telegram-mensagens.js` executava, tentava chamar:
```javascript
NotificacoesSistema.notificarNovaMensagem(msg)
```

Mas o objeto `NotificacoesSistema` ainda não existia, causando erro silencioso.

---

## ✅ Solução Implementada

### Mudança na ordem de carregamento dos scripts (bot_aovivo.php):

**ANTES:**
```html
<script src="js/telegram-salvar-bote.js" defer></script>
<script src="js/telegram-mensagens.js" defer></script>           <!-- ❌ Carregava PRIMEIRO -->
<!-- ... outros scripts ... -->
<script src="js/notificacoes-sistema.js" defer></script>         <!-- ❌ Carregava DEPOIS -->
```

**DEPOIS:**
```html
<script src="js/telegram-salvar-bote.js" defer></script>
<script src="js/notificacoes-sistema.js" defer></script>         <!-- ✅ Agora carrega PRIMEIRO -->
<script src="js/telegram-mensagens.js" defer></script>           <!-- ✅ Depois carrega este -->
```

Removida também a duplicação do script `notificacoes-sistema.js` que estava sendo carregado duas vezes.

---

## 🔍 Como Verificar se está Funcionando

### 1. No Console do Navegador (F12)
```javascript
// Verificar se o sistema está inicializado
console.log(NotificacoesSistema);

// Deve retornar um objeto com métodos como:
// - init()
// - requestPermissao()
// - notificarNovaMensagem(msg)
// - mostrarNotificacao(titulo, opcoes)
```

### 2. Testar Manualmente (no Console)
```javascript
// Testar notificação visual
NotificacoesSistema.mostrarNotificacaoVisual(
  "🚩 +1.5 CANTOS - Flamengo vs Botafogo",
  {
    body: "Oportunidade de escanteio detectada",
    icon: "/img/notificacao_cantos.jpg"
  }
);

// Testar notificação de gols
NotificacoesSistema.mostrarNotificacaoVisual(
  "⚽ +2.5 GOLS - Santos vs Palmeiras",
  {
    body: "Oportunidade de gols detectada",
    icon: "/img/notificacao_gol.jpg"
  }
);
```

### 3. Verificar Permissões
```javascript
// Ver status de permissão
console.log("Permissão:", Notification.permission);

// Possíveis valores:
// - "granted"  = Notificações permitidas ✅
// - "denied"   = Usuário negou ❌
// - "default"  = Ainda não pediu permissão ❌
```

### 4. Verificar Logs
Acesse: `visualizar-logs-notificacoes.php` para ver logs em tempo real

---

## 📱 Fluxo de Funcionamento Correto

```
1. Página carrega bot_aovivo.php
   ↓
2. notificacoes-sistema.js é carregado
   ├─ Inicializa sistema
   ├─ Pede permissão ao usuário
   └─ Cria áudio de alerta

3. telegram-mensagens.js é carregado
   ├─ Faz polling a cada 500ms
   └─ Quando detecta mensagem nova
       ↓
4. Chama NotificacoesSistema.notificarNovaMensagem(msg)
   ├─ Detecta tipo (CANTOS ou GOLS)
   ├─ Gera título formatado
   └─ Mostra notificação

5. Se permissão concedida:
   ├─ Mostra Web Notification nativa
   └─ Reproduz som

6. Se permissão negada/negada:
   ├─ Mostra toast visual
   └─ Reproduz som via Web Audio API
```

---

## 🛠️ Estrutura de Arquivos

```
bot_aovivo.php
├─ js/notificacoes-sistema.js      ✅ Deve carregar PRIMEIRO
├─ js/telegram-mensagens.js        ✅ Depois carrega este
└─ registrar-log-notificacao.php   ✅ Para logs
```

---

## ✨ Checklist de Validação

- [x] `notificacoes-sistema.js` carrega antes de `telegram-mensagens.js`
- [x] Objeto `NotificacoesSistema` está disponível globalmente
- [x] Funções de callback existem: `notificarNovaMensagem()`, `mostrarNotificacao()`
- [x] Sistema de permissões funciona (browser API)
- [x] Logs são registrados em `/logs/notif-YYYY-MM-DD.log`
- [x] Toast visual (fallback) funciona em iOS
- [x] Som de alerta toca quando permissão concedida

---

## 🔧 Próximas Melhorias Sugeridas

1. **Service Worker**: Melhorar compatibilidade em background
2. **Toast UI**: Aprimorar visual em mobile
3. **Filtros**: Permitir desabilitar notificações por tipo de aposta
4. **Histórico**: Manter histórico de notificações mostradas

---

## 📞 Contato / Suporte

Se as notificações continuarem não aparecendo:
1. Abra o DevTools (F12)
2. Vá para a aba "Console"
3. Procure por mensagens de erro
4. Verifique logs em `visualizar-logs-notificacoes.php`
5. Verifique permissões do navegador (Configurações > Privacidade)
