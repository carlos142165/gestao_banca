# ✅ CHECKLIST DE DEPLOYMENT - Carousel Blocos

## 📋 Antes de Fazer Upload

- [ ] **Verificar localhost** - Carousel funciona em `http://localhost/gestao/gestao_banca/bot_aovivo.php`
  - [ ] Desktop: 3 blocos lado a lado
  - [ ] Mobile: Carousel com swipe
  - [ ] Console (F12): Sem erros 404

- [ ] **Preparar arquivos** - Todos os 4 arquivos estão prontos
  - [ ] `css/carousel-blocos.css` ✅ (Novo)
  - [ ] `js/carousel-blocos.js` ✅ (Novo)
  - [ ] `bot_aovivo.php` ✅ (Modificado - 1 linha)
  - [ ] `gestao-diaria.php` ✅ (Já OK)

- [ ] **Verificar estrutura local**
  ```
  ✅ c:\xampp\htdocs\gestao\gestao_banca\
     ├── css/carousel-blocos.css
     ├── js/carousel-blocos.js
     ├── bot_aovivo.php
     └── gestao-diaria.php
  ```

---

## 🚀 Upload para Hostinger

### Via cPanel File Manager (RECOMENDADO)

- [ ] **Passo 1: Conectar ao cPanel**
  - [ ] Abrir: `seudominio.com/cpanel`
  - [ ] Fazer login com credenciais
  - [ ] Procurar por "File Manager"

- [ ] **Passo 2: Navegar até a pasta**
  - [ ] `public_html/gestao/gestao_banca/`
  - [ ] Verificar que estou no lugar certo

- [ ] **Passo 3: Upload dos arquivos CSS e JS (NOVOS)**
  - [ ] Clique "Upload" na pasta `/css/`
    - [ ] Selecione `carousel-blocos.css`
    - [ ] Clique "Upload Files"
    - [ ] Aguarde 100%
  - [ ] Clique "Upload" na pasta `/js/`
    - [ ] Selecione `carousel-blocos.js`
    - [ ] Clique "Upload Files"
    - [ ] Aguarde 100%

- [ ] **Passo 4: Substituir bot_aovivo.php (MODIFICADO)**
  - [ ] Clique direito em `bot_aovivo.php`
  - [ ] Selecione "Replace"
  - [ ] Selecione arquivo local `bot_aovivo.php`
  - [ ] Clique "Upload"
  - [ ] Confirme substituição

- [ ] **Passo 5: Deixar gestao-diaria.php como está**
  - [ ] ✅ Não fazer nada com este arquivo
  - [ ] Já está funcionando corretamente

### Via FTP (FileZilla)

- [ ] **Conectar ao servidor**
  - [ ] Host: `ftp.seudominio.com.br` (ou seudominio.com.br)
  - [ ] User: Credenciais FTP
  - [ ] Password: Sua senha
  - [ ] Port: 21
  - [ ] Clique "Quickconnect"

- [ ] **Navegar até a pasta**
  - [ ] `/public_html/gestao/gestao_banca/`

- [ ] **Fazer upload dos arquivos**
  - [ ] Arraste `css/carousel-blocos.css` para `/css/`
  - [ ] Arraste `js/carousel-blocos.js` para `/js/`
  - [ ] Arraste `bot_aovivo.php` para a raiz
  - [ ] Aguarde todos completarem

---

## 🔐 Configurar Permissões

**IMPORTANTE: Não pular este passo!**

- [ ] **Arquivo CSS**
  - [ ] Clique direito em `carousel-blocos.css`
  - [ ] "Change Permissions"
  - [ ] Defina para: **644**
  - [ ] Confirme

- [ ] **Arquivo JS**
  - [ ] Clique direito em `carousel-blocos.js`
  - [ ] "Change Permissions"
  - [ ] Defina para: **644**
  - [ ] Confirme

- [ ] **Arquivo PHP**
  - [ ] Clique direito em `bot_aovivo.php`
  - [ ] "Change Permissions"
  - [ ] Defina para: **644**
  - [ ] Confirme

- [ ] **Pastas** (se necessário)
  - [ ] Clique direito em `css/`
  - [ ] "Change Permissions"
  - [ ] Defina para: **755**
  - [ ] Confirme (mesmo para `/js/`)

---

## 🧪 Testes Após Upload

### Teste 1: Diagnóstico Automático ⭐

- [ ] **Acessar página de diagnóstico**
  - [ ] URL: `https://seusite.com/gestao/gestao_banca/diagnostico-carousel.php`
  - [ ] Carregar página
  - [ ] Verificar resultado

- [ ] **Validar checklist**
  - [ ] ✅ Arquivo CSS encontrado
  - [ ] ✅ Arquivo JS encontrado
  - [ ] ✅ bot_aovivo.php configurado
  - [ ] ✅ Diretórios OK
  - [ ] ✅ Permissões corretas

### Teste 2: Teste em Desktop

- [ ] **Acessar página**
  - [ ] URL: `https://seusite.com/gestao/gestao_banca/bot_aovivo.php`
  - [ ] Página carrega?
  - [ ] Sem erro 404?

- [ ] **Verificar layout**
  - [ ] Janela com 1024px ou mais
  - [ ] Deve mostrar 3 blocos lado a lado
  - [ ] Sem carousel visível

- [ ] **Console do navegador (F12)**
  - [ ] Mensagem: "CarouselBlocos module initialized" ✅
  - [ ] Nenhum erro vermelho ✅
  - [ ] Nenhum 404 ✅

### Teste 3: Teste em Mobile

- [ ] **Modo responsivo (F12 → Device Emulation)**
  - [ ] Tamanho: 375px (iPhone)
  - [ ] Deve mostrar carousel
  - [ ] 1 bloco por tela
  - [ ] Pontinhos (indicadores) embaixo

- [ ] **Interações**
  - [ ] Swipe para esquerda → próximo bloco
  - [ ] Swipe para direita → bloco anterior
  - [ ] Clique nos pontinhos → navega
  - [ ] Setas do teclado (← →) → navega

- [ ] **Teste em celular real** (se possível)
  - [ ] Abrir em celular
  - [ ] Testar swipe
  - [ ] Verificar responsividade
  - [ ] Testar em WiFi e mobile data

### Teste 4: Comparar com gestao-diaria.php

- [ ] **Acessar gestao-diaria.php**
  - [ ] URL: `https://seusite.com/gestao/gestao_banca/gestao-diaria.php`
  - [ ] Deve funcionar igual
  - [ ] Mesmo comportamento em desktop/mobile

---

## 🆘 Se Houver Problema

- [ ] **Checklist de Troubleshooting**
  - [ ] Verificar documentação: `TROUBLESHOOTING_CAROUSEL_HOSTINGER.md`
  - [ ] Limpar cache do navegador
  - [ ] Tentar em navegação privada
  - [ ] Verificar permissões (deve ser 644)
  - [ ] Tentar reupload do arquivo problemático

- [ ] **Diagnosticar**
  - [ ] Abrir `diagnostico-carousel.php`
  - [ ] Verificar qual arquivo está faltando
  - [ ] Fazer upload novamente
  - [ ] Aguardar 5-10 minutos
  - [ ] Testar novamente

- [ ] **Procurar ajuda**
  - [ ] Consultar `README_CAROUSEL_FINAL.txt`
  - [ ] Procurar seção "Troubleshooting"
  - [ ] Contatar suporte Hostinger
  - [ ] Fornecer URL de diagnóstico

---

## ✅ Sucesso!

Quando tudo estiver funcionando:

- [ ] Desktop (1024px+): 3 blocos lado a lado ✅
- [ ] Tablet (768-1024px): Carousel horizontal ✅
- [ ] Mobile (<768px): Carousel com swipe ✅
- [ ] Ambas páginas (`bot_aovivo.php` e `gestao-diaria.php`) funcionam igual ✅
- [ ] Console sem erros ✅
- [ ] Sem avisos de 404 ✅

---

## 📝 Anotações Finais

Espaço para anotar o que aconteceu durante o deploy:

```
Data: _______________
Hora: _______________

Ações tomadas:
_________________________________________________________________
_________________________________________________________________
_________________________________________________________________

Problemas encontrados:
_________________________________________________________________
_________________________________________________________________

Soluções aplicadas:
_________________________________________________________________
_________________________________________________________________

Resultado final:
☐ Sucesso 100%
☐ Sucesso parcial (descrever)
☐ Falha (descrever)

Observações:
_________________________________________________________________
_________________________________________________________________
```

---

## 🎯 Próximos Passos

- [ ] Após sucesso, comunicar ao time
- [ ] Documentar no projeto (se aplicável)
- [ ] Arquivar este checklist
- [ ] Fazer backup do código (já está em git?)
- [ ] Considerar CI/CD para atualizações futuras

---

**Status**: 🟢 Pronto para deploy  
**Última atualização**: 2025-11-05  
**Versão**: 1.0 - Checklist Final
