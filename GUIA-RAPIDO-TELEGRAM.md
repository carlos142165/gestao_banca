# 🚀 Guia Rápido de Instalação - Monitor Telegram

## ⚡ 5 Minutos para Começar

### 1️⃣ Inicializar Sistema
Acesse no seu navegador:
```
http://seu-site.com/init-telegram-monitor.php
```

Você verá:
```
✅ Arquivo dados_telegram.json criado
✅ Arquivo dados_telegram.json tem permissão de escrita
✅ Diretório css existe
✅ Diretório js existe
✅ css/oportunidades-telegram.css
✅ js/monitor-telegram.js
✅ telegram-monitor.php
✅ telegram-webhook.php
✅ teste-telegram-monitor.html
```

### 2️⃣ Testar Funcionamento
Acesse:
```
http://seu-site.com/teste-telegram-monitor.html
```

Clique em:
- ✅ "Sincronizar Agora"
- ✅ "Adicionar Oportunidade PENDENTE"
- ✅ "Adicionar Resultado GREEN"

Veja as mensagens aparecendo na tabela.

### 3️⃣ Abrir Bot ao Vivo
```
http://seu-site.com/bot_aovivo.php
```

**Bloco 1** deve mostrar as oportunidades com:
- ✅ Sincronizar button
- ✅ Lista de oportunidades
- ✅ Contador

### 4️⃣ Enviar Mensagens no Telegram
Entre no canal:
```
https://t.me/-1002047004959
```

Copie e envie uma mensagem assim:
```
Oportunidade! 🚨
📊 🚨 OVER ( +1⛳️ ASIÁTICO ) Underdog
⚽️ Junior (H) x Independiente Santa Fe (A) (ao vivo)
⏰ Tempo: 83'
⛳️ Escanteios: 6 - 6
Stake: 1%
ODD: 1.5
```

### 5️⃣ Ver Resultado Aparecer
Volte para `bot_aovivo.php` e clique "Sincronizar" ou aguarde 5 segundos.

A mensagem aparecerá no **Bloco 1**!

---

## 🎮 Teste Rápido sem Telegram

Use este link direto para simular mensagens:
```
http://seu-site.com/simular-telegram.php?acao=oportunidade
http://seu-site.com/simular-telegram.php?acao=green
http://seu-site.com/simular-telegram.php?acao=red
http://seu-site.com/simular-telegram.php?acao=reembolso
```

---

## 📋 Checklist de Instalação

- [ ] Executei `init-telegram-monitor.php`
- [ ] Arquivos criados com sucesso
- [ ] Abri `teste-telegram-monitor.html`
- [ ] Testei sincronização
- [ ] Abri `bot_aovivo.php`
- [ ] Vejo oportunidades no Bloco 1
- [ ] Enviei mensagem no Telegram
- [ ] Resultado apareceu em bot_aovivo.php

---

## 🔄 Fluxo de Mensagem

```
┌─────────────────────┐
│  Telegram Channel   │
│  (seu canal)        │
└──────────┬──────────┘
           │
           ├─ Sincronização Automática (5s)
           │
           ▼
┌─────────────────────┐
│ telegram-monitor.php│
│  (processa)         │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│ dados_telegram.json │
│  (armazena)         │
└──────────┬──────────┘
           │
           ├─ monitor-telegram.js busca
           │
           ▼
┌─────────────────────┐
│ bot_aovivo.php      │
│ Bloco 1 exibe ✨    │
└─────────────────────┘
```

---

## ❓ Dúvidas Comuns

### D: Nada aparece no Bloco 1
**R:** Verifique em `teste-telegram-monitor.html` se há mensagens armazenadas. Se não, sincronize manualmente.

### D: Como enviar várias oportunidades seguidas?
**R:** Use `simular-telegram.php` ou clique o botão várias vezes em `teste-telegram-monitor.html`.

### D: Quanto tempo leva para atualizar?
**R:** Até 5 segundos (intervalo de sincronização). Clique "Sincronizar" para atualizar imediatamente.

### D: Como resetar tudo?
**R:** Em `teste-telegram-monitor.html`, clique "Limpar Dados". O arquivo `dados_telegram.json` será esvaziado.

### D: As mensagens são salvas permanentemente?
**R:** Sim, em `dados_telegram.json`. Não são apagadas automaticamente.

---

## 📞 Suporte Rápido

| Problema | Solução |
|----------|---------|
| Arquivo não criado | Execute `init-telegram-monitor.php` |
| Permissão negada | `chmod 755 dados_telegram.json` |
| Mensagens não sincronizam | Verifique conectividade com Telegram |
| CSS não aplica | Limpe cache do navegador (Ctrl+Shift+Delete) |
| Console com erros | Abra F12 e verifique aba Console |

---

## 🎯 Próximas Funcionalidades

- [ ] Notificações sonoras ao chegar oportunidade
- [ ] Histórico de oportunidades com filtros
- [ ] Gráfico de taxa de acerto (GREEN/RED)
- [ ] Exportar oportunidades em Excel
- [ ] Integração com WebSocket para real-time

---

**Pronto para começar! 🚀**

Qualquer dúvida, consulte o `README-TELEGRAM-MONITOR.md`
