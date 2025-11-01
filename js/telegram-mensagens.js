const TelegramMessenger = {
  lastUpdateId: 0,
  isPolling: false,
  pollInterval: null,
  container: null,
  retryCount: 0,
  maxRetries: 3,

  init() {
    this.container = document.querySelector(".telegram-messages-wrapper");
    if (!this.container) {
      console.warn("Container de mensagens do Telegram não encontrado");
      return;
    }

    console.log("✅ Telegram Messenger inicializado");

    // Carregar mensagens iniciais
    this.loadMessages();

    // Iniciar polling a cada 2 segundos
    this.startPolling();
  },

  loadMessages() {
    if (!this.container) return;

    // Mostrar loading
    this.showLoading();

    fetch("api/telegram-mensagens.php?action=get-messages&t=" + Date.now())
      .then((response) => {
        console.log("📡 Status da resposta:", response.status);
        if (!response.ok) {
          throw new Error("Erro HTTP: " + response.status);
        }
        return response.json();
      })
      .then((data) => {
        console.log("📨 Dados recebidos:", data);
        if (data.success) {
          this.retryCount = 0; // Reset retry count
          if (data.messages.length === 0) {
            console.log("ℹ️ Nenhuma mensagem de hoje");
            this.showEmpty();
          } else {
            console.log("✅ Mensagens carregadas:", data.messages.length);
            this.displayMessages(data.messages);
            if (data.messages.length > 0) {
              this.lastUpdateId =
                data.messages[data.messages.length - 1].update_id ||
                data.messages[data.messages.length - 1].id;
              console.log("🔄 Último Update ID:", this.lastUpdateId);
            }
          }
        } else {
          console.error("❌ Erro na resposta:", data);
          // Não mostrar erro, mostrar vazio se mensagem vazia
          this.showEmpty();
        }
      })
      .catch((error) => {
        console.error("❌ Erro ao carregar mensagens:", error);
        // Não mostrar erro, mostrar vazio em caso de falha
        this.showEmpty();
      });
  },

  retryLoadMessages() {
    if (this.retryCount < this.maxRetries) {
      this.retryCount++;
      console.log(
        `🔄 Tentando novamente... (${this.retryCount}/${this.maxRetries})`
      );
      setTimeout(() => this.loadMessages(), 3000);
    } else {
      console.error("❌ Máximo de tentativas atingido");
    }
  },

  startPolling() {
    if (this.isPolling) return;

    this.isPolling = true;
    console.log("🔔 Iniciando polling para novas mensagens...");

    const poll = () => {
      fetch(
        `api/telegram-mensagens.php?action=poll&last_update=${
          this.lastUpdateId
        }&t=${Date.now()}`
      )
        .then((response) => {
          if (!response.ok) {
            throw new Error("Erro HTTP: " + response.status);
          }
          return response.json();
        })
        .then((data) => {
          if (data.success) {
            if (data.messages.length > 0) {
              console.log(
                "🔔 Novas mensagens detectadas:",
                data.messages.length
              );
              // Adicionar novas mensagens ao container
              data.messages.forEach((msg) => {
                this.addMessage(msg);
              });
            }

            // Atualizar último ID sempre (mesmo se vazio)
            if (data.last_update) {
              this.lastUpdateId = data.last_update;
              console.log("🔄 Update ID:", this.lastUpdateId);
            }
          }
        })
        .catch((error) => console.error("❌ Erro ao fazer polling:", error));
    };

    // Fazer polling a cada 1 segundo (mais frequente para tempo real)
    this.pollInterval = setInterval(poll, 1000);
  },

  stopPolling() {
    if (this.pollInterval) {
      clearInterval(this.pollInterval);
      this.isPolling = false;
    }
  },

  displayMessages(messages) {
    if (!this.container) return;

    this.container.innerHTML = "";

    // ✅ INVERTER ORDEM: Mensagens mais recentes em cima, antigas em baixo
    [...messages].reverse().forEach((msg) => {
      this.addMessage(msg);
    });

    // Auto-scroll para cima (não para baixo)
    setTimeout(() => this.scrollToTop(), 100);
  },

  addMessage(msg) {
    if (!this.container) return;

    // Verificar se mensagem já existe
    if (document.querySelector(`[data-message-id="${msg.id}"]`)) {
      return;
    }

    const messageEl = document.createElement("div");
    messageEl.className = "telegram-message";
    messageEl.setAttribute("data-message-id", msg.id);
    messageEl.innerHTML = `
            <div class="telegram-message-time">
                <i class="fas fa-clock"></i>
                <span>${msg.time}</span>
            </div>
            <div class="telegram-message-text">${this.escapeHtml(
              msg.text
            )}</div>
        `;

    // ✅ INSERIR NO INÍCIO (para ordem de cima para baixo)
    this.container.insertBefore(messageEl, this.container.firstChild);

    // Scroll para cima (primeira mensagem)
    setTimeout(() => this.scrollToTop(), 100);
  },

  showLoading() {
    if (!this.container) return;

    this.container.innerHTML = `
            <div class="telegram-loading">
                <div class="telegram-loading-spinner"></div>
                <p>Carregando mensagens...</p>
            </div>
        `;
  },

  showEmpty() {
    if (!this.container) return;

    this.container.innerHTML = `
            <div class="telegram-empty">
                <i class="fas fa-comments"></i>
                <p>Nenhuma mensagem de hoje</p>
            </div>
        `;
  },

  showError(message) {
    if (!this.container) return;

    this.container.innerHTML = `
            <div class="telegram-error">
                <i class="fas fa-exclamation-circle"></i>
                <span>${message}</span>
            </div>
        `;
  },

  scrollToBottom() {
    if (this.container) {
      setTimeout(() => {
        this.container.scrollTop = this.container.scrollHeight;
      }, 50);
    }
  },

  scrollToTop() {
    if (this.container) {
      setTimeout(() => {
        this.container.scrollTop = 0;
      }, 50);
    }
  },

  escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  },
};

// Inicializar quando o DOM estiver pronto
document.addEventListener("DOMContentLoaded", function () {
  TelegramMessenger.init();
});

// Parar polling quando sair da página
window.addEventListener("beforeunload", function () {
  TelegramMessenger.stopPolling();
});
