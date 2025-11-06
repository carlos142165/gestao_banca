# 🔧 Troubleshooting - Carousel não funciona em Produção (Hostinger)

## ❌ Problema Identificado

Na Hostinger, o carousel não está funcionando em `bot_aovivo.php`, mas funciona em localhost.

---

## ✅ Solução Aplicada

### 1️⃣ Corrigido o caminho do JavaScript

**Antes (ERRADO):**
```php
<script src="js/carousel-blocos.js?v=<?php echo time(); ?>" defer></script>
```

**Depois (CORRETO):**
```php
<script src="js/carousel-blocos.js" defer></script>
```

**Por quê?** 
- O parâmetro `?v=<?php echo time(); ?>` gerava URLs dinâmicas que podem causar conflitos com cache
- A versão em `gestao-diaria.php` não usa esse parâmetro e funciona perfeitamente
- Usar o padrão consistente resolve o problema

---

## 📋 Checklist de Verificação

Antes de fazer upload para a Hostinger, verifique:

### ✓ Passo 1: Verificar estrutura de pastas
```
gestao_banca/
├── css/
│   └── carousel-blocos.css ✅
├── js/
│   └── carousel-blocos.js ✅
├── gestao-diaria.php ✅
└── bot_aovivo.php ✅
```

### ✓ Passo 2: Testar em localhost
1. Acesse: `http://localhost/gestao/gestao_banca/bot_aovivo.php`
2. Verifique no console do navegador (F12 → Console):
   - ❌ Não deve haver erro: `404 Not Found` para `carousel-blocos.css`
   - ❌ Não deve haver erro: `404 Not Found` para `carousel-blocos.js`
   - ✅ Deve aparecer: `✓ CarouselBlocos module initialized`

### ✓ Passo 3: Testar responsividade em localhost
- **Desktop** (≥1025px): 3 blocos lado a lado ✅
- **Tablet** (768-1024px): Carousel com scroll horizontal ✅
- **Mobile** (≤768px): Carousel com swipe + indicadores ✅

---

## 🚀 Como fazer upload para Hostinger

### 1. Via cPanel File Manager:
```
1. Acesse cPanel → File Manager
2. Navegue até: public_html/gestao/gestao_banca/
3. Upload dos arquivos:
   - css/carousel-blocos.css (NOVO)
   - js/carousel-blocos.js (NOVO)
   - bot_aovivo.php (MODIFICADO - sobrescrever)
   - gestao-diaria.php (OK, já está lá funcionando)
4. Clique em "Upload" ou "Replace"
```

### 2. Via FTP (FileZilla):
```
1. Conecte ao servidor FTP da Hostinger
2. Navegue até: /public_html/gestao/gestao_banca/
3. Faça upload ou replace dos 4 arquivos
4. Certifique-se de que não há erros de permissão (755 para pastas, 644 para arquivos)
```

---

## 🧪 Testes após Upload

Após fazer upload para a Hostinger:

### 1️⃣ Teste básico
```
1. Abra: https://seusite.com/gestao/gestao_banca/bot_aovivo.php
2. Pressione F12 → Console
3. Procure por:
   ✅ "CarouselBlocos module initialized" 
   ❌ Nenhum erro 404
```

### 2️⃣ Teste de responsividade
- Redimensione a janela do navegador
- Em ≥1025px: deve mostrar 3 blocos lado a lado
- Em ≤1024px: deve mostrar carousel

### 3️⃣ Teste de interação (Mobile)
- Em um celular real ou usando F12 → Device Emulation
- Teste o swipe horizontal
- Clique nos indicadores (pontinhos) embaixo
- Teste as setas do teclado (← →)

---

## 🆘 Se ainda não funcionar na Hostinger

### Problema 1: Ainda mostra 3 blocos empilhados
```
✓ Solução: Limpar cache do navegador (Ctrl+Shift+Delete)
✓ Solução: Usar navegação privada (InCognito)
✓ Solução: Aguardar 24h para CDN atualizar
```

### Problema 2: Aparece erro "Uncaught SyntaxError"
```
✓ Solução: Verificar se o arquivo carousel-blocos.js foi enviado completo
✓ Solução: Tentar fazer upload novamente
✓ Solução: Verificar permissões de arquivo (deve ser 644)
```

### Problema 3: CSS não carrega (blocos sem estilo)
```
✓ Solução: Verificar se carousel-blocos.css está em: /css/carousel-blocos.css
✓ Solução: Verificar permissões do arquivo (deve ser 644)
✓ Solução: Abrir DevTools (F12) → Network e procurar erros 404
```

### Problema 4: Funciona em localhost mas não na Hostinger
```
✓ Solução: Verificar se a estrutura de pastas está igual
✓ Solução: Usar paths absolutos se necessário: /gestao/gestao_banca/css/carousel-blocos.css
✓ Solução: Verificar se há .htaccess bloqueando acesso a /css ou /js
```

---

## 📝 Comandos úteis (para linha de comando do cPanel)

Se precisar verificar via SSH:

```bash
# Verificar se os arquivos existem
ls -la /home/seuusername/public_html/gestao/gestao_banca/css/carousel-blocos.css
ls -la /home/seuusername/public_html/gestao/gestao_banca/js/carousel-blocos.js

# Verificar permissões (deve ser 644 para arquivos)
stat /home/seuusername/public_html/gestao/gestao_banca/css/carousel-blocos.css

# Dar permissão correta
chmod 644 /home/seuusername/public_html/gestao/gestao_banca/css/carousel-blocos.css
chmod 644 /home/seuusername/public_html/gestao/gestao_banca/js/carousel-blocos.js
```

---

## 📞 Resumo da Solução

| Arquivo | Status | Ação |
|---------|--------|------|
| `css/carousel-blocos.css` | ✅ CRIADO | Upload como NOVO |
| `js/carousel-blocos.js` | ✅ CRIADO | Upload como NOVO |
| `gestao-diaria.php` | ✅ OK | Nada a fazer (já funciona) |
| `bot_aovivo.php` | ✅ CORRIGIDO | Upload como MODIFICADO |

**Próximo passo:** Fazer upload dos 4 arquivos para a Hostinger e testar!

---

## 🎯 Resultado esperado

Após aplicar essa solução e fazer upload:

✅ Ambas as páginas (`gestao-diaria.php` e `bot_aovivo.php`) funcionarão igual  
✅ Em desktop: 3 blocos lado a lado  
✅ Em mobile: Carousel responsivo com swipe  
✅ Sem erros no console  
✅ Cache funcionando corretamente  

Qualquer dúvida, execute os comandos de troubleshooting acima! 🚀
