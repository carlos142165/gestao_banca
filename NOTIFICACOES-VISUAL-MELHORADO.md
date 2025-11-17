# 📢 NOTIFICAÇÕES VISUAL MELHORADO - DOCUMENTAÇÃO

## ✅ O QUE FOI IMPLEMENTADO

### 1. **Detecção Automática de Tipo**
Sistema detecta automaticamente se é:
- 🚩 **CANTOS** - Oportunidades com escanteios
- ⚽ **GOLS** - Oportunidades com gols

Baseado na análise do texto da mensagem.

---

## 📱 NOVO VISUAL DA NOTIFICAÇÃO

### Estrutura:

```
┌─────────────────────────────────────┐
│ 🚩 CANTOS - Flamengo vs Botafogo  │ ✕
├─────────────────────────────────────┤
│                                     │
│  [Ícone Redondo]  Flamengo x...  │
│  (Canto Laranja)   +1.5 CANTOS    │
│                    Odds: 1.85     │
│                                     │
└─────────────────────────────────────┘
```

### Para Cantos:
```
Título:  🚩 CANTOS - Time1 vs Time2
Ícone:   Bandeirinha laranja (#f97316)
Corpo:   Oportunidade específica
```

### Para Gols:
```
Título:  ⚽ GOLS - Time1 vs Time2
Ícone:   Bola de futebol azul (#6366f1)
Corpo:   Oportunidade específica
```

---

## 🎨 ÍCONES SVG

### Ícone de Cantos (Bandeira)
- Cor: Laranja (#f97316)
- Forma: Bandeirinha com haste
- Tamanho: Circular responsivo
- Estilo: Minimalista, fácil reconhecimento

### Ícone de Gols (Bola)
- Cor: Azul (#6366f1)
- Forma: Bola de futebol
- Tamanho: Circular responsivo
- Estilo: Moderno, com padrão de bola

---

## 🔧 COMO FUNCIONA INTERNAMENTE

### 1. Detectar Tipo
```javascript
const tipo = this.detectarTipo(msg.titulo || msg.text);
// Procura por: 'canto', 'escanteio'
// Se encontrar → tipo = 'cantos'
// Senão → tipo = 'gols'
```

### 2. Extrair Times
```javascript
const times = this.extrairTimes(msg);
// Procura por: "Time1 vs Time2"
// Usa regex: /([A-Z].*?)\s+(?:vs|x)\s+([A-Z].*?)/
// Exemplo: "Flamengo vs Botafogo"
```

### 3. Gerar Ícone
```javascript
const icone = this.gerarIconoTipo(tipo);
// tipo === 'cantos' → bandeira laranja
// tipo === 'gols' → bola azul
```

### 4. Mostrar Notificação
```javascript
this.mostrarNotificacao(titulo, {
  body: oportunidade,
  icon: icone,        // Ícone redondo
  badge: icone,
  tag: `msg-${msg.id}`
});
```

---

## 📊 EXEMPLOS DE NOTIFICAÇÕES

### Exemplo 1: Cantos
```
╔════════════════════════════════════════╗
║  🚩 CANTOS - Flamengo vs Botafogo    │ ✕
╠════════════════════════════════════════╣
║                                        ║
║  [🚩 Laranja]  Oportunidade!         ║
║                +1.5 CANTOS             ║
║                Odds: 1.85              ║
║                                        ║
╚════════════════════════════════════════╝
```

### Exemplo 2: Gols
```
╔════════════════════════════════════════╗
║  ⚽ GOLS - São Paulo vs Santos       │ ✕
╠════════════════════════════════════════╣
║                                        ║
║  [⚽ Azul]     Oportunidade!          ║
║               +0.5 GOLS                ║
║               Odds: 1.65               ║
║                                        ║
╚════════════════════════════════════════╝
```

---

## 🎯 RECURSOS IMPLEMENTADOS

✅ **Detecção automática de tipo**
- Analisa texto procurando por "canto" ou "escanteio"
- Fallback para "gols" se não encontrar

✅ **Extração de times**
- Procura no objeto msg.time_1 e msg.time_2
- Se não encontrar, tenta regex no texto
- Formata como "Time1 vs Time2"

✅ **Ícones dinâmicos**
- SVG dados URI (sem carregar arquivos)
- Bandeira laranja para cantos
- Bola azul para gols
- Totalmente responsivo

✅ **Títulos informativos**
- Mostra tipo (🚩 CANTOS ou ⚽ GOLS)
- Mostra times (Flamengo vs Botafogo)
- Exemplo: "🚩 CANTOS - Flamengo vs Botafogo"

✅ **Corpo descritivo**
- Mostra oportunidade (primeiros 80 caracteres)
- Exemplo: "+1.5 CANTOS | Odds: 1.85"

---

## 🧪 TESTE DO NOVO SISTEMA

### Página de teste (já atualizada):
```
http://seusite.com/teste-notificacoes.php
```

### Testar notificação de cantos:
```javascript
NotificacoesSistema.notificarNovaMensagem({
  id: 1,
  time_1: "Flamengo",
  time_2: "Botafogo",
  titulo: "Oportunidade! +1.5 Cantos",
  text: "Flamengo vs Botafogo | +1.5 CANTOS | Odds: 1.85"
});
```

### Testar notificação de gols:
```javascript
NotificacoesSistema.notificarNovaMensagem({
  id: 2,
  time_1: "São Paulo",
  time_2: "Santos",
  titulo: "Oportunidade! +0.5 Gols",
  text: "São Paulo vs Santos | +0.5 GOLS | Odds: 1.65"
});
```

---

## 🔍 DETECÇÃO DE TIPO - LÓGICA

```
Texto: "Flamengo vs Botafogo +1.5 CANTOS"
                        ↓
              Procura por: 'canto'?
                        ↓
                  SIM → tipo = 'cantos'
                  NÃO → tipo = 'gols'
```

### Palavras-chave detectadas:
- ✅ "canto"
- ✅ "cantos"
- ✅ "escanteio"
- ✅ "escanteios"

### Case-insensitive:
- ✅ "CANTO" = "canto"
- ✅ "Canto" = "canto"
- ✅ "CANTOS" = "cantos"

---

## 🎨 CORES DOS ÍCONES

### Cantos (Bandeira)
- **Cor primária:** #f97316 (Laranja)
- **Opacity:** 0.95 (quase opaco)
- **Forma:** Círculo com bandeirinha

### Gols (Bola)
- **Cor primária:** #6366f1 (Azul)
- **Opacity:** 0.95 (quase opaco)
- **Forma:** Círculo com bola de futebol

---

## 📋 ARQUIVOS MODIFICADOS

```
✅ js/notificacoes-sistema.js
   ├─ detectarTipo(texto)
   ├─ gerarIconoTipo(tipo)
   ├─ extrairTimes(msg)
   └─ notificarNovaMensagem(msg) [MELHORADO]
```

---

## 🔧 FUNÇÕES PRINCIPAIS

### `detectarTipo(texto)`
```javascript
// Entrada: String com texto da mensagem
// Saída: 'cantos' ou 'gols'
const tipo = this.detectarTipo("Flamengo vs Botafogo +1.5 CANTOS");
// Resultado: 'cantos'
```

### `gerarIconoTipo(tipo)`
```javascript
// Entrada: 'cantos' ou 'gols'
// Saída: Data URI SVG (ícone)
const icone = this.gerarIconoTipo('cantos');
// Resultado: "data:image/svg+xml,..."
```

### `extrairTimes(msg)`
```javascript
// Entrada: Objeto da mensagem
// Saída: String "Time1 vs Time2"
const times = this.extrairTimes(msg);
// Resultado: "Flamengo vs Botafogo"
```

### `notificarNovaMensagem(msg)`
```javascript
// Entrada: Objeto da mensagem
// Saída: Notificação visual com som
NotificacoesSistema.notificarNovaMensagem(msg);
// → Toca som
// → Mostra notificação com ícone apropriado
// → Mostra times e oportunidade
```

---

## 💡 EXEMPLOS DE USO

### Quando mensagem chega do Telegram:
```javascript
// Dados da mensagem
const msg = {
  id: 12345,
  time_1: "Flamengo",
  time_2: "Botafogo",
  titulo: "OPORTUNIDADE! +1.5 CANTOS",
  text: "Flamengo vs Botafogo | +1.5 CANTOS | Odds: 1.85"
};

// Sistema detecta automaticamente
NotificacoesSistema.notificarNovaMensagem(msg);

// Resultado:
// Título: "🚩 CANTOS - Flamengo vs Botafogo"
// Ícone: Bandeira laranja
// Corpo: "OPORTUNIDADE! +1.5 CANTOS..."
// Som: Toca beep
```

---

## 🚀 COMPATIBILIDADE

| Recurso | Chrome | Firefox | Safari | Edge |
|---------|--------|---------|--------|------|
| SVG Data URI | ✅ | ✅ | ✅ | ✅ |
| Web Notifications | ✅ | ✅ | ✅ | ✅ |
| Ícones customizados | ✅ | ✅ | ✅ | ✅ |
| Regex extração | ✅ | ✅ | ✅ | ✅ |

---

## 📱 VISUAL EM DIFERENTES DISPOSITIVOS

### Desktop (Chrome/Firefox):
```
┌─────────────────────────────┐
│ 🚩 [Ícone] CANTOS - Times │
│ Oportunidade específica...  │
└─────────────────────────────┘
```

### Mobile (Android/iOS):
```
┌──────────────────────┐
│ [Ícone] CANTOS      │
│ Times               │
│ Oportunidade...     │
└──────────────────────┘
```

---

## ⚙️ COMPORTAMENTO

1. ✅ **Ao chegar mensagem:**
   - Sistema detecta tipo automaticamente
   - Gera ícone apropriado (canto/gols)
   - Toca som de alerta
   - Mostra notificação visual

2. ✅ **Ao usuário clicar:**
   - Abre bot_aovivo.php
   - Foco na janela do navegador
   - Notificação fecha

3. ✅ **Sem interação:**
   - Desaparece sozinha após alguns segundos
   - Histórico no centro de notificações do SO

---

## 📊 ANÁLISE DE TIPO

### Situações testadas:

| Texto | Detecta | Resultado |
|-------|---------|-----------|
| "+1.5 CANTOS" | Sim | cantos ✅ |
| "+0.5 GOLS" | Não | gols ✅ |
| "Escanteios +2" | Sim | cantos ✅ |
| "+1 GOL" | Não | gols ✅ |
| "Canto no 1º tempo" | Sim | cantos ✅ |
| "Sem contexto" | Não | gols ✅ |

---

**Última atualização:** 14/11/2025
**Status:** ✅ Implementado e testado
**Versão:** 1.1 (com detecção de tipo)
