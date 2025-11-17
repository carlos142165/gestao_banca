# 📱 GUIA DE NOTIFICAÇÕES NO MOBILE

## ❌ POR QUE NÃO FUNCIONA NO MOBILE?

### Problema 1: **Navegadores mobile têm políticas diferentes**
- Chrome mobile: Suporta notificações mas com restrições
- Safari iOS: **NÃO suporta Web Notifications API** (limitação do iOS)
- Firefox mobile: Suporta com limitações
- Samsung Internet: Suporta

### Problema 2: **Deve estar em HTTPS (não HTTP)**
- Mobile bloqueia notificações em HTTP simples
- Seu site precisa usar HTTPS

### Problema 3: **App tem que estar em foreground**
- Se app está em background, notificações podem ser bloqueadas
- Precisa de Service Worker ativo

### Problema 4: **Permissão foi negada**
- Uma vez negada no mobile, não pede de novo
- Usuário precisa ir em Configurações → Notificações

---

## ✅ SOLUÇÃO PARA MOBILE

### 1. **Implementar Service Worker** (Essencial)

O Service Worker mantém o app "ativo" mesmo em background.

**Arquivo:** `service-worker.js`

```javascript
// Quando a aba recebe uma mensagem (notification)
self.addEventListener("push", (event) => {
  const data = event.data ? event.data.json() : {};
  
  const options = {
    body: data.body || "Nova notificação",
    icon: data.icon || "/img/notificacao_gols.jpg",
    badge: data.badge || "/img/notificacao_gols.jpg",
    tag: data.tag || "notificacao",
  };
  
  event.waitUntil(
    self.registration.showNotification(data.title || "Notificação", options)
  );
});

// Quando usuário clica na notificação
self.addEventListener("notificationclick", (event) => {
  event.notification.close();
  
  // Abre a aba ou foca na existente
  event.waitUntil(
    clients.matchAll({ type: "window" }).then((clientList) => {
      for (let client of clientList) {
        if (client.url === "/" && "focus" in client) {
          return client.focus();
        }
      }
      // Se não achar aba aberta, abre nova
      if (clients.openWindow) {
        return clients.openWindow("/bot_aovivo.php");
      }
    })
  );
});
```

### 2. **Registrar Service Worker no JavaScript**

Adicione isso no início de `notificacoes-sistema.js`:

```javascript
// Registrar Service Worker para mobile
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('service-worker.js')
    .then(registration => {
      console.log('✅ Service Worker registrado:', registration);
      this.registrarLog('sucesso', 'Service Worker registrado');
    })
    .catch(error => {
      console.log('ℹ️ Service Worker erro:', error);
      this.registrarLog('aviso', 'Service Worker não registrado', { erro: error.message });
    });
}
```

### 3. **Adicionar manifest.json** (PWA Support)

**Arquivo:** `manifest.json`

```json
{
  "name": "Gestão Banca",
  "short_name": "Banca",
  "description": "Sistema de oportunidades de apostas",
  "start_url": "/bot_aovivo.php",
  "display": "standalone",
  "background_color": "#ffffff",
  "theme_color": "#667eea",
  "icons": [
    {
      "src": "/img/notificacao_gols.jpg",
      "sizes": "192x192",
      "type": "image/jpeg"
    },
    {
      "src": "/img/notificacao_cantos.jpg",
      "sizes": "192x192",
      "type": "image/jpeg"
    }
  ],
  "categories": ["sports"],
  "permissions": ["notifications"]
}
```

### 4. **Adicionar ao HTML** (head)

```html
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#667eea">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
```

### 5. **Usar Notification Badge API** (Moderno)

```javascript
// Mostrar badge de notificação no ícone do app
if ('setAppBadge' in navigator) {
  navigator.setAppBadge(1); // Mostra número 1
  
  // Depois limpar
  navigator.clearAppBadge();
}
```

---

## 📋 CHECKLIST PARA MOBILE

### ✅ Antes de testar:

- [ ] Site está em **HTTPS** (não HTTP)
- [ ] `service-worker.js` foi criado e incluído
- [ ] `manifest.json` foi criado e linkado no HTML
- [ ] Meta tags adicionadas no `<head>`
- [ ] Permissão de notificação foi **CONCEDIDA** no mobile
- [ ] App não está sendo bloqueado em configurações

### 🔧 Se permissão foi NEGADA:

**Android:**
1. Configurações → Apps → [Seu Browser] → Notificações
2. Ativar notificações
3. Recarregar página

**iOS (Safari):**
1. Configurações → Notificações → [App Name]
2. Ativar notificações
3. Recarregar página

---

## 📊 COMPATIBILIDADE MOBILE

| Navegador | Notificações | Service Worker | Status |
|-----------|-------------|----------------|--------|
| Chrome Android | ✅ Sim | ✅ Sim | ✅ Funciona |
| Firefox Android | ✅ Sim | ✅ Sim | ✅ Funciona |
| Samsung Internet | ✅ Sim | ✅ Sim | ✅ Funciona |
| Edge Android | ✅ Sim | ✅ Sim | ✅ Funciona |
| Safari iOS | ❌ Não | ⚠️ Limitado | ❌ Não funciona |
| Chrome iOS | ❌ Não | ⚠️ Limitado | ❌ Não funciona* |

*iOS bloqueia Web Notifications por segurança/privacidade

---

## 🎯 IMPLEMENTAÇÃO RÁPIDA

### Passo 1: Criar `service-worker.js`
```bash
cp service-worker-template.js service-worker.js
```

### Passo 2: Criar `manifest.json`
```bash
cp manifest-template.json manifest.json
```

### Passo 3: Adicionar ao HTML (todas as páginas)
```html
<link rel="manifest" href="/manifest.json">
<script>
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/service-worker.js');
  }
</script>
```

### Passo 4: Testar no mobile
1. Abra em Chrome Android
2. Clique em permissão (aparece popup)
3. Aceite
4. Espere mensagem chegar

---

## 🐛 TROUBLESHOOTING MOBILE

### **Problema: "Permissão não aparece"**
```
Causa: Site não é HTTPS
Solução: Use HTTPS, mesmo que localhost
```

### **Problema: "Notificação não mostra"**
```
Causa: Service Worker não registrou
Solução: Verifique se arquivo service-worker.js existe
Verifique console (F12) por erros
```

### **Problema: "Clica mas não abre app"**
```
Causa: notificationclick não está tratando
Solução: Service Worker pode estar desatualizado
Limpe cache do navegador (Settings → Apps → Storage)
```

### **Problema: "Funciona às vezes"**
```
Causa: App em background mata conexão
Solução: Usar Service Worker garante reconnection
Verificar se WiFi está ativo
```

---

## 📱 TESTE EM CELULAR REAL

**Android (Chrome):**
1. Conecte via USB
2. Abra `chrome://inspect`
3. Veja console remoto
4. Teste notificações

**iOS (Safari):**
- Infelizmente não tem suporte nativo
- Alternativa: Usar PWA com notificações "fake" visuais

---

## ✨ EXTRAS PARA MELHOR UX

### Toast notifications (fallback visual)
```javascript
// Se Web Notifications não funcionar, mostrar toast
function mostrarToastVisual(titulo, mensagem) {
  const toast = document.createElement('div');
  toast.className = 'toast-notificacao';
  toast.innerHTML = `
    <div class="toast-conteudo">
      <strong>${titulo}</strong>
      <p>${mensagem}</p>
    </div>
  `;
  document.body.appendChild(toast);
  
  setTimeout(() => toast.remove(), 5000);
}
```

### CSS para Toast
```css
.toast-notificacao {
  position: fixed;
  top: 20px;
  right: 20px;
  background: #667eea;
  color: white;
  padding: 15px 20px;
  border-radius: 10px;
  z-index: 9999;
  animation: slideIn 0.3s;
}

@keyframes slideIn {
  from { transform: translateX(400px); }
  to { transform: translateX(0); }
}
```

---

## 🎓 RESUMO

Para mobile funcionar:
1. ✅ Service Worker (mantém app "acordado")
2. ✅ Manifest.json (PWA config)
3. ✅ HTTPS (obrigatório)
4. ✅ Permissão concedida (user action)
5. ✅ Meta tags (app aware)

**iOS não suporta Web Notifications** - use alternativa visual

---

**Data:** 14/11/2025  
**Versão:** 1.0  
**Status:** Pronto para implementação
