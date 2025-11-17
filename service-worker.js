/**
 * 🔔 SERVICE WORKER PARA NOTIFICAÇÕES MOBILE
 * Mantém o app "acordado" mesmo em background
 * Essencial para notificações funcionarem no mobile
 */

const CACHE_NAME = "notificacoes-v1";
const ASSETS_TO_CACHE = [
  "/",
  "/bot_aovivo.php",
  "/home.php",
  "/conta.php",
  "/js/notificacoes-sistema.js",
  "/img/notificacao_cantos.jpg",
  "/img/notificacao_gol.jpg",
];

/**
 * 📦 INSTALL - Cachear arquivos essenciais
 */
self.addEventListener("install", (event) => {
  console.log("📦 Service Worker instalando...");

  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      console.log("✅ Cache criado:", CACHE_NAME);
      // Tenta cachear, mas não falha se não conseguir
      return cache.addAll(ASSETS_TO_CACHE).catch(() => {
        console.log("ℹ️ Alguns arquivos não foram cacheados (normal)");
      });
    })
  );

  // Força ativação imediata
  self.skipWaiting();
});

/**
 * 🚀 ACTIVATE - Limpar caches antigos
 */
self.addEventListener("activate", (event) => {
  console.log("🚀 Service Worker ativando...");

  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            console.log("🗑️ Deletando cache antigo:", cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );

  // Toma controle de todas as abas
  return self.clients.claim();
});

/**
 * 📨 PUSH - Receber notificações push
 */
self.addEventListener("push", (event) => {
  console.log("📨 Notificação push recebida");

  let notificationData = {
    title: "Nova Oportunidade",
    body: "Clique para ver detalhes",
    icon: "/img/notificacao_gol.jpg",
    badge: "/img/notificacao_gol.jpg",
    tag: "notificacao-padrao",
  };

  // Se veio dados da mensagem
  if (event.data) {
    try {
      const data = event.data.json();
      notificationData = {
        ...notificationData,
        ...data,
      };
    } catch (e) {
      console.log("ℹ️ Dados não eram JSON:", e);
      notificationData.body = event.data.text();
    }
  }

  console.log("🔔 Mostrando notificação:", notificationData.title);

  event.waitUntil(
    self.registration.showNotification(notificationData.title, {
      body: notificationData.body,
      icon: notificationData.icon,
      badge: notificationData.badge,
      tag: notificationData.tag,
      requireInteraction: false,
      data: notificationData,
    })
  );
});

/**
 * 🖱️ NOTIFICATION CLICK - Quando usuário clica na notificação
 */
self.addEventListener("notificationclick", (event) => {
  console.log("🖱️ Notificação clicada");
  event.notification.close();

  const urlParaAbrir = event.notification.data?.url || "/bot_aovivo.php";

  event.waitUntil(
    // Procura por aba aberta
    clients.matchAll({ type: "window" }).then((clientList) => {
      // Procura aba do site
      for (let i = 0; i < clientList.length; i++) {
        const client = clientList[i];
        if (client.url === "/" || client.url.includes("bot_aovivo")) {
          return client.focus();
        }
      }

      // Se não achou, abre nova
      if (clients.openWindow) {
        return clients.openWindow(urlParaAbrir);
      }
    })
  );
});

/**
 * 📡 FETCH - Interceptar requisições (offline support)
 */
self.addEventListener("fetch", (event) => {
  // Só cacheia GET
  if (event.request.method !== "GET") {
    return;
  }

  event.respondWith(
    caches.match(event.request).then((response) => {
      // Retorna do cache se existir
      if (response) {
        return response;
      }

      // Se não, tenta rede
      return fetch(event.request)
        .then((response) => {
          // Cacheia se for sucesso
          if (response && response.status === 200) {
            const responseToCache = response.clone();
            caches.open(CACHE_NAME).then((cache) => {
              cache.put(event.request, responseToCache);
            });
          }
          return response;
        })
        .catch(() => {
          // Se falhar, tenta cache como fallback
          return caches.match(event.request);
        });
    })
  );
});

/**
 * 📢 MESSAGE - Comunicação com páginas
 */
self.addEventListener("message", (event) => {
  console.log("📢 Mensagem recebida no Service Worker:", event.data);

  if (event.data.tipo === "mostrar-notificacao") {
    const { titulo, body, icon } = event.data;

    self.registration.showNotification(titulo, {
      body: body,
      icon: icon,
      badge: icon,
      tag: "notificacao-manual",
    });
  }
});

/**
 * 🔔 INICIALIZAÇÃO
 */
console.log("✅ Service Worker carregado e pronto para funcionar");
