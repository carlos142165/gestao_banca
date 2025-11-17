# 🔔 SISTEMA DE NOTIFICAÇÕES COM SOM - DOCUMENTAÇÃO

## 📋 Resumo
Sistema completo de notificações que alerta o usuário quando uma nova mensagem/oportunidade chega, **em qualquer página aberta**. Inclui:
- ✅ Som de alerta
- ✅ Notificação visual do navegador
- ✅ Link direto para bot_aovivo.php ao clicar

---

## 🎯 O que foi implementado

### 1️⃣ **Novo Arquivo: `js/notificacoes-sistema.js`**
Sistema de notificações independente que:
- ✅ Solicita permissão de notificações do navegador (primeira execução)
- ✅ Cria áudio de alerta (beep curto 800Hz)
- ✅ Toca som via Web Audio API (fallback se necessário)
- ✅ Mostra notificação visual do navegador
- ✅ Ao clicar na notificação → redireciona para `bot_aovivo.php`
- ✅ Evita duplicatas de notificações muito próximas

**Funções principais:**
```javascript
NotificacoesSistema.init()                    // Inicializar sistema
NotificacoesSistema.notificarNovaMensagem()   // Enviar notificação
NotificacoesSistema.reproduzirSom()           // Tocar som alerta
```

---

### 2️⃣ **Modificação: `js/telegram-mensagens.js`**
Adicionado evento de notificação quando nova mensagem é detectada:

```javascript
// Linha 345-348 (aproximadamente)
if (isNewMessage) {
  console.log(`[NEW] 🆕 Nova mensagem detectada: ID ${msg.id}`);
  this.addMessage(msg);
  
  // 🔔 NOTIFICAR NOVA MENSAGEM (em qualquer página)
  if (typeof NotificacoesSistema !== 'undefined' && NotificacoesSistema.notificarNovaMensagem) {
    NotificacoesSistema.notificarNovaMensagem(msg);
  }
}
```

---

### 3️⃣ **Adições em Páginas Principais**
Adicionado em TODAS as páginas principais para que o sistema funcione em qualquer lugar:

✅ **bot_aovivo.php** - Já tinha, agora com melhorias
✅ **home.php** - Adicionado `telegram-mensagens.js` + `notificacoes-sistema.js`
✅ **conta.php** - Adicionado `telegram-mensagens.js` + `notificacoes-sistema.js`
✅ **gestao-diaria.php** - Adicionado `telegram-mensagens.js` + `notificacoes-sistema.js`
✅ **administrativa.php** - Adicionado `telegram-mensagens.js` + `notificacoes-sistema.js`

**Ordem de carregamento (importante):**
```html
<!-- 1. Carregar mensagens (polling) -->
<script src="js/telegram-mensagens.js?v=<?php echo time(); ?>" defer></script>

<!-- 2. Sistema de notificações -->
<script src="js/notificacoes-sistema.js?v=<?php echo time(); ?>" defer></script>
```

---

## 🎮 Como funciona

### Fluxo de notificações:

```
1️⃣ Usuário abre qualquer página (home.php, conta.php, etc)
   ↓
2️⃣ JavaScript carrega com defer
   ├─ telegram-mensagens.js inicia polling
   └─ notificacoes-sistema.js solicita permissão
   ↓
3️⃣ Polling detecta nova mensagem a cada 500ms
   ↓
4️⃣ Se é nova mensagem (não no cache):
   ├─ Adiciona ao DOM (em bot_aovivo.php)
   └─ Chama NotificacoesSistema.notificarNovaMensagem(msg)
   ↓
5️⃣ Sistema de notificações:
   ├─ Toca som (2 métodos: Audio tag + Web Audio API)
   ├─ Mostra notificação visual do navegador
   └─ Aguarda clique do usuário
   ↓
6️⃣ Ao clicar na notificação:
   └─ Redireciona para bot_aovivo.php
```

---

## 🔊 Som de Alerta

### Características:
- **Frequência:** 800 Hz (tom agudo notável)
- **Duração:** 200ms (curto e não invasivo)
- **Volume:** 0.7 (audível mas não alto demais)

### Métodos de reprodução:
1. **Audio HTML5** (elemento `<audio>`)
2. **Web Audio API** (oscilador - fallback)

O sistema tenta ambos para garantir compatibilidade máxima com navegadores.

---

## 📱 Notificação Visual

### Título:
```
🚨 Nova Oportunidade!
```

### Conteúdo:
```
Corpo: Primeiros 100 caracteres do título/texto da mensagem
Ícone: Sino vermelho com branco
Agrupamento: Por ID da mensagem (evita múltiplas notificações iguais)
```

### Ao clicar:
```
1. Traz janela do navegador para primeiro plano
2. Redireciona para bot_aovivo.php
3. Fecha a notificação
```

---

## ✅ Verificação de Permissões

O sistema verifica automaticamente:

```javascript
// Estado das permissões
if (Notification.permission === "granted")     ✅ Já tem permissão
if (Notification.permission === "denied")      ❌ Usuário negou
if (Notification.permission === "default")     ⏳ Não perguntado ainda
```

**Primeira visita:** Navegador pede permissão automaticamente.

---

## 🧪 Testando o sistema

### 1. Verificar no Console
Abrir DevTools (F12) → Console:
```javascript
// Verificar se está inicializado
console.log(NotificacoesSistema)

// Enviar notificação de teste
NotificacoesSistema.notificarNovaMensagem({
  id: 999,
  titulo: "Teste de Notificação",
  text: "Esta é uma notificação de teste"
})
```

### 2. Verificar Permissões
```javascript
console.log("Permissão:", Notification.permission)
console.log("Sistema pronto:", NotificacoesSistema.permissaoNotificacao)
```

### 3. Testar Som
```javascript
NotificacoesSistema.reproduzirSom()
```

---

## 🔐 Considerações de Segurança

✅ **Web Notifications API** (padrão W3C)
✅ Requer permissão explícita do usuário
✅ Apenas notifica, não executa código
✅ Redireciona para página do próprio domínio

---

## 🐛 Troubleshooting

### Som não toca?
1. Verificar volume do navegador/sistema
2. Alguns navegadores bloqueiam autoplay → teste com gesto do usuário
3. Verificar console para erros

### Notificação não aparece?
1. Verificar permissões: `Notification.permission`
2. Se "denied" → limpar cookies/dados do site
3. Alguns navegadores requerem HTTPS (em produção)

### Notificação duplicada?
Sistema impede automaticamente com hash (3 segundos)

### Não redireciona ao clicar?
Verificar se `bot_aovivo.php` existe e está acessível

---

## 📊 Compatibilidade

| Navegador | Web Notifications | Web Audio API | Suportado? |
|-----------|------------------|---------------|-----------|
| Chrome    | ✅ Sim           | ✅ Sim        | ✅ Full   |
| Firefox   | ✅ Sim           | ✅ Sim        | ✅ Full   |
| Safari    | ✅ Sim           | ✅ Sim        | ✅ Full   |
| Edge      | ✅ Sim           | ✅ Sim        | ✅ Full   |
| Opera     | ✅ Sim           | ✅ Sim        | ✅ Full   |
| IE 11     | ❌ Não           | ❌ Não        | ⚠️ Sem som |

---

## 📝 Notas Importantes

### 1. Ordem de Carregamento
`telegram-mensagens.js` deve carregar ANTES de `notificacoes-sistema.js`

### 2. Cache de Scripts
Adicionar `?v=<?php echo time(); ?>` força atualização (já feito)

### 3. Em Produção
- HTTPS recomendado para Web Notifications
- Mobile: Notificações funcionam melhor em apps mobile

### 4. Performance
- Polling a cada 500ms (otimizado)
- Eventos de notificação são assíncronos (não bloqueia UI)
- Cache previne duplicatas

---

## 📈 Melhorias Futuras

1. ⏳ Fila de notificações (se múltiplas chegarem)
2. 📢 Diferentes sons para diferentes tipos de mensagens
3. 🎯 Centro de notificações (histórico)
4. 🔇 Mute/Unmute de notificações
5. ⏰ Agendador de notificações silenciosas (horários específicos)

---

**Última atualização:** 14/11/2025
**Status:** ✅ Pronto para produção
**Versão:** 1.0
