# ✅ CORREÇÃO: Notificações de CANTOS agora funcionam corretamente

## Problema Identificado
As notificações de cantos estavam sendo enviadas com o ícone e tipo de GOLS em vez de CANTOS.

## Raiz do Problema
No arquivo `js/notificacoes-sistema.js`, a função `detectarTipo()` estava verificando o campo `msg.tipo_aposta`, mas a API retornava o campo como `msg.type`. 

Quando uma mensagem de cantos chegava, a detecção falhava no fallback e usando o default (GOLS).

## Solução Implementada

### 1️⃣ Correção em `js/notificacoes-sistema.js` (linhas 262-281)
Alterado a função `detectarTipo()` para:
- ✅ Verificar AMBOS os campos: `msg.tipo_aposta` E `msg.type`
- ✅ Fazer verificações mais robustas com regex `/\bcantos?\b/`
- ✅ Adicionar comment explicativo no fallback

**Antes:**
```javascript
if (msg.tipo_aposta) {
  const tipoAposta = msg.tipo_aposta.toLowerCase();
  if (tipoAposta.includes("⛳") || /\bcantos?\b/.test(tipoAposta)) {
    return "cantos";
  }
  if (tipoAposta.includes("⚽") || /\bgols?\b/.test(tipoAposta)) {
    return "gols";
  }
}
```

**Depois:**
```javascript
const tipoApostaField = msg.tipo_aposta || msg.type;
if (tipoApostaField) {
  const tipoAposta = tipoApostaField.toLowerCase();
  console.log("📋 Verificando tipo_aposta/type:", tipoAposta);

  // Verificar se contém palavras-chave para CANTOS
  if (tipoAposta.includes("⛳") || 
      tipoAposta.includes("canto") || 
      /\bcantos?\b/.test(tipoAposta)) {
    console.log("✅ Detectado por tipo_aposta/type: CANTOS");
    return "cantos";
  }
  
  // Verificar se contém palavras-chave para GOLS
  if (tipoAposta.includes("⚽") || 
      tipoAposta.includes("gol") || 
      /\bgols?\b/.test(tipoAposta)) {
    console.log("✅ Detectado por tipo_aposta/type: GOLS");
    return "gols";
  }
}
```

### 2️⃣ Adição de Campo Duplicado em `api/carregar-mensagens-banco.php` 
Adicionado o campo `'tipo_aposta'` nas três funções que retornam mensagens:
- `getMessagesFromDatabase()` - carrega mensagens de hoje
- `pollNewMessages()` - faz polling de atualizações
- `getMessagesByDate()` - busca mensagens por data específica

**Antes:**
```php
'title' => $row['titulo'],
'type' => $row['tipo_aposta'],
'status' => $row['status_aposta'],
```

**Depois:**
```php
'title' => $row['titulo'],
'type' => $row['tipo_aposta'],
'tipo_aposta' => $row['tipo_aposta'],  // ✅ DUPLICAR para compatibilidade
'status' => $row['status_aposta'],
```

## Fluxo de Funcionamento Agora

```
1. Mensagem chega no Telegram
   ↓
2. Webhook (telegram-webhook.php) salva no banco com tipo_aposta='CANTOS'
   ↓
3. Frontend faz polling em api/carregar-mensagens-banco.php
   ↓
4. API retorna: { type: 'CANTOS', tipo_aposta: 'CANTOS', ... }
   ↓
5. JavaScript dispara NotificacoesSistema.notificarNovaMensagem(msg)
   ↓
6. detectarTipo() procura em msg.tipo_aposta ou msg.type
   ↓
7. Encontra 'CANTOS' e retorna "cantos"
   ↓
8. Notificação é enviada com:
   - Ícone laranja (notificacao_cantos.jpg) ✅
   - Título: "🚩 CANTOS - Time1 vs Time2"  ✅
   - Som de alerta ✅
```

## Como Testar

### 1. Manualmente via Console
```javascript
// Teste GOLS
NotificacoesSistema.notificarNovaMensagem({
    id: 1,
    titulo: "⚽ +0.5 GOLS - Flamengo vs Botafogo",
    text: "Teste de GOLS",
    type: "GOLS",
    tipo_aposta: "GOLS",
    time_1: "Flamengo",
    time_2: "Botafogo"
});

// Teste CANTOS
NotificacoesSistema.notificarNovaMensagem({
    id: 2,
    titulo: "🚩 +1 CANTOS - São Paulo vs Santos",
    text: "Teste de CANTOS",
    type: "CANTOS",
    tipo_aposta: "CANTOS",
    time_1: "São Paulo",
    time_2: "Santos"
});
```

### 2. Via página de teste
Abrir `teste-notificacoes.php` e usar os botões de teste.

### 3. Sistema em Produção
Enviar mensagens reais do Telegram com cantos vs gols e verificar se as notificações aparecem com ícones e tipos corretos.

## Campos Suportados

### Para tipo CANTOS:
- `tipo_aposta`: "CANTOS"
- `titulo`: contém "⛳" ou "CANTOS" ou "cantos"
- `type`: "CANTOS"

### Para tipo GOLS:
- `tipo_aposta`: "GOLS" ou "GOL"
- `titulo`: contém "⚽" ou "GOLS" ou "gols"
- `type`: "GOLS" ou "GOL"

## Debugging

Se a notificação ainda aparecer errada, verificar no console:
```javascript
console.log("Detectando tipo para:", msg);
// Procurar por:
// "📋 Verificando tipo_aposta/type: cantos"
// ou
// "⚠️ Nenhuma detecção específica, usando default: GOLS"
```

## Status
✅ **CORRIGIDO E TESTADO**

- [x] Corrigir função detectarTipo em notificacoes-sistema.js
- [x] Adicionar campo tipo_aposta na API  
- [x] Garantir compatibilidade com ambos os nomes de campo
- [x] Testes manuais passando
