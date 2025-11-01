# 🤖 Monitor Telegram em Tempo Real - Bot ao Vivo

## 📋 Visão Geral

Este sistema integra o Telegram com seu página de **Bot ao Vivo** (bot_aovivo.php) para exibir oportunidades de apostas em tempo real no **Bloco 1**.

As mensagens chegam do canal do Telegram, são filtradas e formatadas, depois exibidas com a capacidade de atualizar o resultado quando chegar (GREEN/RED/REEMBOLSO).

---

## 🔧 Configuração

### Credenciais Telegram
```
Token API: 8549099161:AAFKDDdeaFpwz9I4CkaqIwCIFOCleQZMEr8
Channel ID: -1002047004959
```

### Arquivos Principais

1. **telegram-monitor.php** - API principal que gerencia as mensagens
2. **telegram-webhook.php** - Webhook para receber updates em tempo real
3. **js/monitor-telegram.js** - JavaScript que renderiza as oportunidades
4. **css/oportunidades-telegram.css** - Estilos dos cards
5. **dados_telegram.json** - Banco de dados local (criado automaticamente)

---

## 🎯 Fluxo de Funcionamento

### 1️⃣ Mensagem de Oportunidade
```
Oportunidade! 🚨
📊 🚨 OVER ( +1⛳️ ASIÁTICO ) Underdog
⚽️ Junior (H) x Independiente Santa Fe (A) (ao vivo)
⏰ Tempo: 83'
Odds iniciais: Casa: 1.571 - Emp. 3.8 - Fora: 5.75
⛳️ Escanteios: 6 - 6
Stake: 1%
```

**Transformada em:**
```
Oportunidade! 🚨
---
📊 🚨 OVER ( +1⛳️ ASIÁTICO )
Junior x Independiente Santa Fe
⛳️ Escanteios: 6 - 6
Stake: 1%
---
         ⏳ PENDENTE
---
```

### 2️⃣ Resultado Chega
```
Resultado disponível!
⚽️ Junior (H) x Independiente Santa Fe (A) (ao vivo)
Escanteios over +1.0 - ODD: 1.504 - GREEN
✅
```

**Card Atualizado:**
```
         ✅ GREEN
```
(Fundo fica verde com animação)

---

## 🎨 Filtros e Regras

A mensagem **SOMENTE** é exibida se contiver:
- `Oportunidade! 🚨` (ou similar)
- `📊 🚨` 
- `⚽️` (identificador de jogo)
- `Stake:` (valor do stake)
- `Escanteios:` (escanteios do jogo)

**Se faltar qualquer uma desses elementos, a mensagem é ignorada.**

---

## 📊 Estados Possíveis

### 🔘 PENDENTE
- Cor: Cinza
- Ícone: ⏳
- Status: Aguardando resultado
- Borda: Cinza

### 🟢 GREEN
- Cor: Verde (#66bb6a)
- Ícone: ✅
- Status: Apostou correto / Ganhou
- Borda: Verde com animação de sucesso

### 🔴 RED
- Cor: Vermelho (#e57373)
- Ícone: ❌
- Status: Apostou errado / Perdeu
- Borda: Vermelho com animação de erro (tremida)

### ⚫ REEMBOLSO
- Cor: Cinza escuro
- Ícone: ↩️
- Status: Dinheiro devolvido
- Borda: Cinza com rotação

---

## 🚀 Como Usar

### Acessar a Página
```
http://seu-site.com/bot_aovivo.php
```

O **Bloco 1** mostrará:
- Header com botão "Sincronizar"
- Lista das oportunidades mais recentes
- Contador de oportunidades

### Botão Sincronizar
Força a sincronização imediata com o Telegram. Útil para testar ou quando precisa de atualização urgente.

### Sincronização Automática
- **A cada 5 segundos** o sistema verifica novas mensagens automaticamente
- Sem necessidade de clique manual
- Funciona em background

---

## 🧪 Teste

Acesse a página de teste:
```
http://seu-site.com/teste-telegram-monitor.html
```

### Opções de Teste:
1. **Sincronizar Agora** - Busca mensagens do Telegram manualmente
2. **Carregar Mensagens** - Carrega do banco de dados local
3. **Limpar Dados** - Apaga todas as mensagens armazenadas
4. **Adicionar Oportunidade** - Simula uma nova oportunidade PENDENTE
5. **Adicionar Resultado GREEN** - Simula atualização para GREEN
6. **Adicionar Resultado RED** - Simula atualização para RED
7. **Adicionar Resultado REEMBOLSO** - Simula atualização para REEMBOLSO

---

## 📁 Estrutura de Dados

### dados_telegram.json
```json
{
  "123456": {
    "id": 123456,
    "data_chegada": "2025-11-01 14:30:45",
    "jogo": "Junior x Independiente Santa Fe",
    "escanteis": "6 - 6",
    "stake": "1%",
    "odd": "1.5",
    "tipo": "OVER",
    "resultado": "PENDENTE",
    "data_resultado": null
  },
  "123457": {
    "id": 123457,
    "data_chegada": "2025-11-01 14:45:20",
    "jogo": "Flamengo x Vasco",
    "escanteis": "8 - 5",
    "stake": "2%",
    "odd": "1.8",
    "tipo": "OVER",
    "resultado": "GREEN",
    "data_resultado": "2025-11-01 15:00:30"
  }
}
```

---

## 🔌 Webhook do Telegram (Opcional)

Para receber mensagens em **tempo real** sem aguardar sincronização, configure:

### 1. Obter URL Pública
Seu servidor precisa estar acessível de fora. Se usar localhost, use serviço como ngrok.

### 2. Registrar Webhook
```bash
curl -X POST https://api.telegram.org/bot8549099161:AAFKDDdeaFpwz9I4CkaqIwCIFOCleQZMEr8/setWebhook \
  -F url=https://seu-site.com/telegram-webhook.php
```

### 3. Verificar
```bash
curl https://api.telegram.org/bot8549099161:AAFKDDdeaFpwz9I4CkaqIwCIFOCleQZMEr8/getWebhookInfo
```

---

## 🛡️ Segurança

### IMPORTANTE:
- O TOKEN está **exposto** no código (não é o ideal em produção)
- Considere usar variáveis de ambiente:

```php
const TELEGRAM_TOKEN = getenv('TELEGRAM_TOKEN') ?: '8549099161:AAFKDDdeaFpwz9I4CkaqIwCIFOCleQZMEr8';
```

### Arquivo de Dados
- `dados_telegram.json` é criado com `LOCK_EX` para evitar conflitos
- Permissões: `755` (leitura/escrita)
- Backup recomendado a cada dia

---

## 📱 Responsividade

O sistema é **totalmente responsivo** e funciona em:
- ✅ Desktop
- ✅ Tablet
- ✅ Mobile

Todos os cards se adaptam ao tamanho da tela.

---

## 🐛 Troubleshooting

### Mensagens não aparecem
1. Verificar se `dados_telegram.json` foi criado
2. Teste a sincronização manual em `teste-telegram-monitor.html`
3. Verifique permissões do arquivo

### Resultados não atualizam
1. Verificar se a mensagem contém `GREEN`, `RED` ou `REEMBOLSO`
2. Pode levar até 5 segundos para sincronizar automaticamente
3. Use botão "Sincronizar" para forçar atualização

### Performance lenta
1. Limpar dados antigos regularmente
2. Reduzir limite de mensagens em `telegram-monitor.php`
3. Aumentar intervalo de sincronização

---

## 📊 Listar Últimas Mensagens

### Via API
```php
POST telegram-monitor.php
Body: acao=obter_mensagens

// Response
{
  "sucesso": true,
  "mensagens": [
    { ... },
    { ... }
  ],
  "total": 2
}
```

---

## 🔄 Ciclo de Vida da Mensagem

```
┌─ Mensagem chega no Telegram
│
├─ É uma oportunidade válida?
│  ├─ NÃO → Ignorada
│  └─ SIM → Continua
│
├─ Extrair informações (jogo, escanteis, stake)
│
├─ Salvar em dados_telegram.json com resultado PENDENTE
│
├─ Renderizar no Bloco 1 em bot_aovivo.php
│
└─ Aguardar resultado
   ├─ Resultado GREEN chega
   ├─ Resultado RED chega
   └─ Resultado REEMBOLSO chega
      └─ Atualizar card com cor e ícone correspondente
```

---

## 📞 Suporte

Para problemas ou dúvidas:
1. Verifique o console do navegador (F12)
2. Veja logs do PHP em `apache/logs/error.log`
3. Teste em `teste-telegram-monitor.html`

---

**Desenvolvido com ❤️ para Gestão de Banca**
