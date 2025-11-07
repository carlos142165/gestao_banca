╔════════════════════════════════════════════════════════════════════════╗
║        🔧 CORREÇÃO: BUSCAR UND DE gestao-diaria.php - ATUALIZADO       ║
║                   Sincroniza valor da unidade corretamente              ║
╚════════════════════════════════════════════════════════════════════════╝

🔍 PROBLEMA ENCONTRADO
═════════════════════════════════════════════════════════════════════════

Versão Anterior:
  ❌ Tentava buscar de: obter-plano-usuario.php
  ❌ Valor nem sempre estava sincronizado

Versão Corrigida:
  ✅ Busca de: gestao-diaria.php
  ✅ Extrai do elemento: <span id="valor-unidade">
  ✅ Valor sempre sincronizado com a página principal

═════════════════════════════════════════════════════════════════════════

📝 O QUE FOI CORRIGIDO
═════════════════════════════════════════════════════════════════════════

Arquivo: bot_aovivo.php
Função: atualizarResumoDiaEUnd()

Mudança no fluxo de busca de dados:

ANTES (❌ INCORRETO):
```javascript
fetch('obter-plano-usuario.php')
  .then(response => response.json())
  .then(data => {
    if (data.valor_unidade) {
      // Formatar valor...
    }
  })
```

DEPOIS (✅ CORRETO):
```javascript
fetch('gestao-diaria.php')
  .then(response => response.text())
  .then(html => {
    const parser = new DOMParser();
    const doc = parser.parseFromString(html, 'text/html');
    const valorElement = doc.getElementById('valor-unidade');
    
    if (valorElement) {
      const valor = valorElement.textContent.trim();
      // Usar valor extraído...
    }
  })
```

═════════════════════════════════════════════════════════════════════════

🎯 FLUXO DE FUNCIONAMENTO
═════════════════════════════════════════════════════════════════════════

1️⃣ Função atualizarResumoDiaEUnd() é chamada
   ├─ Verifica localStorage
   ├─ Se encontrar: usa valor armazenado
   └─ Se não encontrar: faz fetch

2️⃣ Fetch para gestao-diaria.php
   ├─ Recebe HTML completo da página
   ├─ Parse com DOMParser
   └─ Extrai elemento: #valor-unidade

3️⃣ Extrai valor do elemento
   ├─ Busca: <span id="valor-unidade">R$ 10,00</span>
   ├─ Obtém textContent: "R$ 10,00"
   ├─ Atualiza DOM em bot_aovivo.php: #resumo-valor-und
   └─ Salva em localStorage para próxima vez

═════════════════════════════════════════════════════════════════════════

💾 CACHE COM LOCALSTORAGE
═════════════════════════════════════════════════════════════════════════

Primeira visita:
  ├─ Faz fetch para gestao-diaria.php
  ├─ Extrai valor: "R$ 10,00"
  ├─ Exibe em bot_aovivo.php
  └─ Salva em localStorage

Próximas visitas (mesma sessão):
  ├─ Verifica localStorage
  ├─ Encontra: "R$ 10,00"
  ├─ Exibe instantaneamente (sem fetch)
  └─ Rápido e eficiente

Sincronização:
  ├─ A cada 30 segundos: faz novo fetch
  ├─ Obtém valor mais atualizado
  ├─ Atualiza cache em localStorage
  └─ Sempre sincronizado com gestao-diaria.php

═════════════════════════════════════════════════════════════════════════

🔄 SINCRONIZAÇÃO A CADA 30 SEGUNDOS
═════════════════════════════════════════════════════════════════════════

Fluxo:
  ├─ window.addEventListener('load') → Carrega ao abrir
  ├─ setInterval 30s → Atualiza periodicamente
  └─ Ambas chamam atualizarResumoDiaEUnd()

Resultado:
  ✅ Valor sempre fresco
  ✅ Sincronizado com gestao-diaria.php
  ✅ Sem travamentos (usa cache)

═════════════════════════════════════════════════════════════════════════

📍 ORIGEM DOS DADOS
═════════════════════════════════════════════════════════════════════════

gestao-diaria.php (Linha 872-874):
┌────────────────────────────────────────────┐
│ <div class="valor-dinamico valor-unidade"> │
│   <span class="rotulo-und">UND:</span>     │
│   <span id="valor-unidade">R$ 10,00</span> │
│ </div>                                     │
└────────────────────────────────────────────┘

bot_aovivo.php (Header - Bloco 1):
┌────────────────────────────────────────────┐
│ <div class="resumo-data-und">              │
│   📅 Quinta-Feira - 06/11                  │
│   UND: <span id="resumo-valor-und">        │
│         R$ 10,00                           │
│       </span>                              │
│ </div>                                     │
└────────────────────────────────────────────┘

═════════════════════════════════════════════════════════════════════════

✨ BENEFÍCIOS
═════════════════════════════════════════════════════════════════════════

1. ✅ Sincronização garantida com gestao-diaria.php
2. ✅ Sempre usa o valor correto da unidade
3. ✅ Cache em localStorage (rápido)
4. ✅ Atualiza a cada 30 segundos
5. ✅ Funciona mesmo sem ID específico
6. ✅ Extrai direto do DOM (mais confiável)

═════════════════════════════════════════════════════════════════════════

🚀 TESTE PRÁTICO
═════════════════════════════════════════════════════════════════════════

1. Abrir bot_aovivo.php
2. Verificar header do Bloco 1:
   └─ Deve mostrar: "UND: R$ 10,00" (ou valor correto)

3. Abrir console (F12):
   └─ Verificar localStorage:
      ├─ Storage → Local Storage → analisegp.com
      ├─ Procurar chave: "valor-unidade"
      └─ Deve ter o valor armazenado

4. Modificar UND em gestao-diaria.php
5. Aguardar 30 segundos ou atualizar manualmente
6. Ver valor atualizado em bot_aovivo.php

═════════════════════════════════════════════════════════════════════════

🔧 CONFIGURAÇÕES
═════════════════════════════════════════════════════════════════════════

Intervalo de atualização: 30 segundos
Chave localStorage: "valor-unidade"
Elemento origem: #valor-unidade (gestao-diaria.php)
Elemento destino: #resumo-valor-und (bot_aovivo.php)

Para alterar intervalo, modificar em bot_aovivo.php:
  ├─ Procurar: setInterval(..., 30000)
  ├─ 30000 = 30 segundos
  ├─ Mudar para: 10000 = 10 segundos (mais rápido)
  └─ ou: 60000 = 1 minuto (mais lento)

═════════════════════════════════════════════════════════════════════════

💾 DADOS EXTRAÍDO
═════════════════════════════════════════════════════════════════════════

O script extrai:
  ├─ Elemento HTML completo de gestao-diaria.php
  ├─ Parse com DOMParser (simula navegador)
  ├─ Busca: document.getElementById('valor-unidade')
  ├─ Obtém textContent (texto do elemento)
  └─ Formata e exibe em bot_aovivo.php

Exemplos de valores extraídos:
  ✅ "R$ 10,00"
  ✅ "R$ 25,50"
  ✅ "R$ 100,00"
  ✅ "Carregando..." (se ainda não carregou)

═════════════════════════════════════════════════════════════════════════

✅ STATUS FINAL
═════════════════════════════════════════════════════════════════════════

Arquivo: bot_aovivo.php (ATUALIZADO)
Função: atualizarResumoDiaEUnd() - v2.0
Sincronização: ✅ gestao-diaria.php
Cache: ✅ localStorage
Intervalo: ✅ 30 segundos
Status: ✅ PRONTO PARA UPLOAD

═════════════════════════════════════════════════════════════════════════
