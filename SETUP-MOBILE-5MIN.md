# 🚀 GUIA RÁPIDO - MOBILE NOTIFICAÇÕES (5 MINUTOS)

## ✅ JÁ FOI CRIADO:

```
✅ service-worker.js    ← Mantém app "acordado" no background
✅ manifest.json        ← Config da PWA (Progressive Web App)
✅ NOTIFICACOES-MOBILE-GUIA.md ← Documentação completa
```

## 📋 PRÓXIMOS PASSOS:

### 1️⃣ Adicionar ao `<head>` das páginas (bot_aovivo.php, home.php, etc):

```html
<!-- Meta tags para PWA Mobile -->
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#667eea">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Banca">

<!-- Service Worker -->
<script>
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/service-worker.js', { scope: '/' })
      .then(reg => console.log('✅ Service Worker registrado'))
      .catch(err => console.log('ℹ️ Service Worker erro:', err));
  }
</script>
```

### 2️⃣ Testar no MOBILE:

**Android (Chrome):**
```
1. Acesse: https://seu-site.com/bot_aovivo.php (HTTPS!)
2. Deve aparecer popup "Adicionar à tela"
3. Clique "Adicionar"
4. App abre como PWA
5. Permita notificações quando pedir
6. Espere mensagem chegar
```

**iOS (Safari):**
```
1. Acesse: https://seu-site.com/bot_aovivo.php
2. Clique botão compartilhar (↑)
3. "Adicionar à tela inicial"
4. Clique "Adicionar"
5. App abre
6. Nota: iOS NÃO suporta Web Notifications
   → Mas vai mostrar toast visual (fallback)
```

---

## 🔧 TROUBLESHOOTING RÁPIDO

### "Não aparece popup Adicionar à tela"
- ✅ Verificar se está em **HTTPS** (não HTTP)
- ✅ Verificar se `manifest.json` está correto
- ✅ Devtools → Application → Manifest

### "Service Worker não registra"
- ✅ F12 → Application → Service Workers
- ✅ Deve aparecer como "activated and running"
- ✅ Se não, check console por erros

### "Notificação não chega no mobile"
- ✅ Android: Verificar Configurações → Notificações → App
- ✅ iOS: Usar fallback toast visual (automático)
- ✅ Verificar se permissão foi **CONCEDIDA**

### "Permissão foi negada"
- **Android:**
  - Configurações → Apps → seu app → Notificações → ON
  
- **iOS:**
  - Configurações → Notificações → seu app → Permitir

---

## 📊 CHECKLIST ANTES DE PUBLICAR

```
☐ service-worker.js existe em /
☐ manifest.json existe em /
☐ Meta tags adicionadas no <head>
☐ Script de registro do Service Worker no <head>
☐ Testado em Chrome Android ✅
☐ Testado em Firefox Android ✅
☐ Testado em Safari iOS (fallback visual) ✅
☐ HTTPS está ativo (obrigatório)
☐ Logs visualizáveis em /visualizar-logs-notificacoes.php
```

---

## 🎯 FLUXO COMPLETO

```
1. Usuário abre app no mobile
   ↓
2. Service Worker registra
   ↓
3. Pede permissão de notificações
   ↓
4. Usuário aceita
   ↓
5. Mensagem chega
   ↓
6. Service Worker recebe
   ↓
7. Mostra notificação (Web Notifications ou Toast)
   ↓
8. Usuário clica
   ↓
9. Abre bot_aovivo.php
```

---

## 💡 DICAS IMPORTANTES

### Para Android:
- Melhor experiência em Chrome mobile
- Service Worker essencial
- Notification Badge API mostra badge no ícone

### Para iOS:
- Web Notifications NÃO suportado
- Toast visual é o fallback automático
- Funciona mesmo sem "Adicionar à tela"
- Safari têm suporte limitado a Service Worker

### Para ambos:
- HTTPS é **OBRIGATÓRIO**
- Permissão precisa ser concedida pelo usuário
- Sem permissão = fallback visual

---

## 📱 INSTALAR COMO APP (PWA)

### Android:
```
1. Abra https://seu-site.com/bot_aovivo.php
2. Chrome menu → "Instalar aplicativo"
3. Confirme
4. App fica na tela inicial
5. Funciona como app nativo
```

### iOS:
```
1. Abra https://seu-site.com/bot_aovivo.php no Safari
2. Botão compartilhar (↑)
3. "Adicionar à tela inicial"
4. "Adicionar"
5. Abre como app full-screen
```

---

## 🔗 ARQUIVOS CRIADOS

| Arquivo | Local | Função |
|---------|-------|--------|
| `service-worker.js` | `/` | Mantém app acordado |
| `manifest.json` | `/` | Config PWA |
| `visualizar-logs-notificacoes.php` | `/` | Ver logs em tempo real |
| `NOTIFICACOES-MOBILE-GUIA.md` | `/` | Documentação detalhada |

---

## ✨ RESUMO RÁPIDO

```
PC:     ✅ Web Notifications (funcionando)
Android: ✅ Service Worker + Web Notifications
iOS:    ✅ Toast visual fallback (automático)
```

**Tudo está pronto para mobile!** 🎉

Próximo passo: Adicionar meta tags no HTML das páginas principais.

