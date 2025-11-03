const TelegramMessenger = {
  container: null,
  lastUpdateId: 0,
  isPolling: false,
  messagesSalvas: new Set(),
  retryCount: 0,
  maxRetries: 3,

  init() {
    this.container = document.querySelector(".telegram-messages-wrapper");
    if (!this.container) {
      console.warn("Container de mensagens do Telegram não encontrado");
      return;
    }

    console.log("✅ Telegram Messenger inicializado");
    this.loadMessages();
    this.startPolling();
  },

  loadMessages() {
    if (!this.container) return;

    // Mostrar loading
    this.showLoading();

    // ✅ CARREGAR DO BANCO DE DADOS (não do Telegram)
    fetch(
      "api/carregar-mensagens-banco.php?action=get-messages&t=" + Date.now()
    )
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
              // ✅ CORRIGIDO: Pegar o MAIOR ID (primeiro da lista, pois está DESC)
              this.lastUpdateId =
                data.messages[0].update_id || data.messages[0].id;
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
    console.log(
      "🔔 Iniciando polling para novas mensagens (do banco de dados)..."
    );

    const poll = () => {
      // ✅ CARREGAR DO BANCO DE DADOS (não do Telegram)
      fetch(
        `api/carregar-mensagens-banco.php?action=poll&last_update=${
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
              console.log("📬 Mensagens:", data.messages);
              // Adicionar novas mensagens ao container
              data.messages.forEach((msg) => {
                this.addMessage(msg);
              });

              // ✅ ATUALIZAR lastUpdateId com o maior ID das novas mensagens
              const maxId = Math.max(
                ...data.messages.map((m) => m.update_id || m.id)
              );
              this.lastUpdateId = maxId;
              console.log("🔄 Update ID atualizado para:", this.lastUpdateId);
            }

            // Atualizar último ID da API (fallback)
            if (data.last_update && data.messages.length === 0) {
              this.lastUpdateId = data.last_update;
              console.log("🔄 Update ID (from API):", this.lastUpdateId);
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

    // ✅ FILTRAR: Apenas mensagens com formato válido
    const validMessages = messages.filter((msg) => this.isValidMessage(msg));

    if (validMessages.length === 0) {
      console.log("ℹ️ Nenhuma mensagem com formato válido encontrada");
      this.showEmpty();
      return;
    }

    // ✅ INVERTER ORDEM: Mensagens mais recentes em cima, antigas em baixo
    [...validMessages].reverse().forEach((msg) => {
      this.addMessage(msg);
    });

    // Auto-scroll para cima (não para baixo)
    setTimeout(() => this.scrollToTop(), 100);
  },

  addMessage(msg) {
    if (!this.container) return;

    // ✅ VALIDAR formato da mensagem
    if (!this.isValidMessage(msg)) {
      return;
    }

    // Verificar se mensagem já existe
    if (document.querySelector(`[data-message-id="${msg.id}"]`)) {
      return;
    }

    // ✅ NÃO PRECISA MAIS SALVAR - A MENSAGEM JÁ VEM DO BANCO!
    // (As mensagens são salvas diretamente quando chegam do Telegram via webhook)
    // Apenas marcamos como já vista para não duplicar na exibição
    this.messagesSalvas.add(msg.id);

    // ✅ FORMATAR a mensagem antes de exibir
    const msgText = msg.text || msg.mensagem_completa || "";
    const formattedContent = this.formatMessage(msgText, msg.time, msg);

    const messageEl = document.createElement("div");
    messageEl.className = "telegram-message";
    messageEl.setAttribute("data-message-id", msg.id);
    messageEl.innerHTML = `
      <div class="msg-header-external">
        <div class="msg-header-left">
          <span class="msg-title-external"><i class="fas fa-bell"></i> Oportunidade!</span>
        </div>
        <div class="msg-header-right">
          <span class="msg-time-external">
            <i class="fas fa-clock"></i>
            ${msg.time}
          </span>
        </div>
      </div>
      ${formattedContent}
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
                <i class="fas fa-search"></i>
                <p>Buscando Melhor Oportunidade</p>
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

  isValidMessage(msg) {
    // ✅ VALIDAÇÃO: Mensagem deve começar com "Oportunidade! 🚨"
    const validFormat = "Oportunidade! 🚨";
    const msgText = msg.text || msg.mensagem_completa || "";

    if (!msgText || !msgText.startsWith(validFormat)) {
      console.log(
        "⚠️ Mensagem ignorada (formato inválido):",
        msgText?.substring(0, 50)
      );
      return false;
    }
    return true;
  },

  formatMessage(rawText, msgTime = "", msgData = null) {
    // ✅ EXTRAI E FORMATA A MENSAGEM DO TELEGRAM
    // msgData contém os dados da BD incluindo o resultado
    const text =
      typeof rawText === "string"
        ? rawText
        : rawText.text || rawText.mensagem_completa || "";
    const lines = text
      .split("\n")
      .map((line) => line.trim())
      .filter((line) => line);

    let titulo = "";
    let time1 = "";
    let time2 = "";
    let placar1 = "";
    let placar2 = "";
    let odds = "";
    let tipoOdds = "Gols over";
    let escanteios1 = 0;
    let escanteios2 = 0;
    let resultado = null; // ✅ NOVO: armazenar resultado da BD

    // ✅ SE TEMOS DADOS DA BD, EXTRAIR RESULTADO
    if (msgData && msgData.resultado) {
      resultado = msgData.resultado;
      console.log("🎯 Resultado encontrado:", resultado);
    }

    // Extrair informações linha por linha
    for (let i = 0; i < lines.length; i++) {
      const line = lines[i];

      // ✅ TÍTULO (linha com 📊) - PEGAR EXATAMENTE COMO VEM NA MENSAGEM
      if (line.includes("📊")) {
        // Remove apenas o emoji 📊 e emojis extras, mantém o resto
        titulo = line
          .replace(/�/g, "") // Remove emoji de gráfico
          .replace(/🚨/g, "") // Remove emoji de alerta
          .trim(); // Remove espaços extras
      }

      // ✅ EXTRAIR ESCANTEIOS (⛳️ Escanteios: 7 - 5)
      if (line.includes("⛳") || line.includes("Escanteios:")) {
        const escanteiosMatch = line.match(/(\d+)\s*-\s*(\d+)/);
        if (escanteiosMatch) {
          escanteios1 = parseInt(escanteiosMatch[1]);
          escanteios2 = parseInt(escanteiosMatch[2]);

          // Se o título tem "CANTOS", atualizar com a soma +1
          if (titulo.includes("CANTOS")) {
            const totalEscanteios = escanteios1 + escanteios2 + 1;
            titulo = titulo.replace(
              /\(\s*\+[\d\.]+⛳/,
              `( +${totalEscanteios}⛳`
            );
          }
        }
      }

      // Times e placar
      if (
        (line.includes("x") && line.includes("(H)")) ||
        line.includes("(A)")
      ) {
        const parts = line.split("x");
        if (parts[0]) {
          time1 = parts[0].replace(/\([^)]*\)/g, "").trim();
          time2 = parts[1] ? parts[1].replace(/\([^)]*\)/g, "").trim() : "";
        }
      }

      // Placar
      if (line.includes("Placar:")) {
        const placarMatch = line.match(/(\d+)\s*-\s*(\d+)/);
        if (placarMatch) {
          placar1 = placarMatch[1];
          placar2 = placarMatch[2];

          // ✅ Se o título tem "GOLS" ou "GOL", atualizar com a soma do placar
          if (titulo.includes("GOLS") || titulo.includes("GOL")) {
            const totalGols = parseInt(placar1) + parseInt(placar2);

            // Verificar se tem ".5" no título
            if (titulo.includes(".5")) {
              // Exemplo: OVER ( +0.5 ⚽GOL ) → OVER ( +1.5 ⚽GOLS )
              const novoTotal = totalGols + 0.5;
              titulo = titulo.replace(
                /\(\s*\+[\d\.]+\s*⚽?[^\)]*\s*(GOLS?)/,
                `( +${novoTotal} ⚽GOLS`
              );
            } else {
              // Exemplo: OVER ( +1 ⚽GOL ) → OVER ( +3 ⚽GOLS )
              const novoTotal = totalGols + 1;
              titulo = titulo.replace(
                /\(\s*\+[\d\.]+\s*⚽?[^\)]*\s*(GOLS?)/,
                `( +${novoTotal} ⚽GOLS`
              );
            }
          }
        }
      }

      // ✅ EXTRAIR ODDS - Procurar por "Gols over" ou "Escanteios over"
      if (line.includes("Gols over")) {
        const golesMatch = line.match(
          /Gols over\s*[\+\-]?[\d\.]*\s*:\s*([\d\.]+)/i
        );
        if (golesMatch) {
          odds = golesMatch[1];
          tipoOdds = "Gols Odds";
        }
      } else if (line.includes("Escanteios over")) {
        const escanteiosOddsMatch = line.match(
          /Escanteios over\s*[\+\-]?[\d\.]*\s*:\s*([\d\.]+)/i
        );
        if (escanteiosOddsMatch) {
          odds = escanteiosOddsMatch[1];
          tipoOdds = "Escanteios Odds";
        }
      }
    }

    // ✅ USA O TÍTULO ORIGINAL DA MENSAGEM
    const tipoAposta = titulo ? titulo : "APOSTA";

    // Formatar HTML com ícones profissionais
    // Escolher ícone apropriado baseado no tipo de aposta
    const apostIcon =
      tipoAposta.includes("GOLS") || tipoAposta.includes("GOL")
        ? '<i class="fas fa-futbol"></i>'
        : '<i class="fas fa-flag"></i>';

    const oddsIcon = tipoOdds.includes("Gols")
      ? '<i class="fas fa-soccer-ball"></i>'
      : '<i class="fas fa-flag"></i>';

    // ✅ FORMATAR STATUS BASEADO NO RESULTADO
    let statusHTML = "";
    let oddsCssClass = "";

    if (resultado) {
      // Tem resultado - exibir resultado ao invés de PENDENTE
      if (resultado === "GREEN") {
        statusHTML = '<span class="odds-resultado odds-green">GREEN ✅</span>';
        oddsCssClass = "odds-with-result-green";
      } else if (resultado === "RED") {
        statusHTML = '<span class="odds-resultado odds-red">RED ❌</span>';
        oddsCssClass = "odds-with-result-red";
      } else if (resultado === "REEMBOLSO") {
        statusHTML =
          '<span class="odds-resultado odds-refund">REEMBOLSO 🔄</span>';
        oddsCssClass = "odds-with-result-refund";
      }
    } else {
      // Sem resultado - exibir PENDENTE
      statusHTML = '<span class="odds-resultado odds-pending">PENDENTE</span>';
      oddsCssClass = "odds-with-result-pending";
    }

    return `
      <div class="telegram-formatted-message">
        <div class="msg-content">
          <div class="msg-aposta">
            ${apostIcon}
            ${tipoAposta}
          </div>
          
          <div class="msg-match">
            <div class="msg-time-row">
              <span class="msg-team">${time1}</span>
              <span class="msg-team">${time2}</span>
            </div>
            <div class="msg-score-row">
              <span class="msg-score">${placar1}</span>
              <span class="msg-score">${placar2}</span>
            </div>
          </div>
          
          <div class="msg-odds ${oddsCssClass}">
            ${oddsIcon}
            ${tipoOdds} $${odds} - ${statusHTML}
          </div>
        </div>
      </div>
    `;
  },

  // ✅ FUNÇÃO DESCONTINUADA - MENSAGENS SÃO SALVAS DIRETO DO WEBHOOK
  // (Mantida para compatibilidade, mas não é mais necessária)
  salvarNosBancoDados(msg) {
    // ✅ NÃO FAZER NADA - O WEBHOOK DO TELEGRAM JÁ SALVA NO BANCO!
    // Quando uma mensagem chega no Telegram, o webhook (telegram-webhook.php)
    // a salva imediatamente no banco de dados. Portanto, quando carregamos
    // via carregar-mensagens-banco.php, a mensagem já está salva!
    console.log("ℹ️ Mensagem já está no banco (salva via webhook):", msg.id);
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
