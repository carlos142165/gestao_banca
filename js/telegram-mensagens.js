const TelegramMessenger = {
  container: null,
  lastUpdateId: 0,
  isPolling: false,
  messagesSalvas: new Set(),
  messagesCache: new Map(), // ✅ CACHE para detectar mudanças
  retryCount: 0,
  maxRetries: 3,
  pollCount: 0, // ✅ Contador de verificações
  reloadCount: 0, // ✅ Contador de reloads em background

  init() {
    this.container = document.querySelector(".telegram-messages-wrapper");
    if (!this.container) {
      console.warn("Container de mensagens do Telegram não encontrado");
      return;
    }

    console.log("✅ Telegram Messenger inicializado");
    console.log("⚡ Background reload: A cada 1 segundo (silencioso)");
    console.log("📦 Cache: Sistema de detecção de mudanças ativo");
    this.loadMessages();
    // ✅ POLLING REATIVADO PARA ATUALIZAÇÕES EM TEMPO REAL
    this.startPolling();
  },

  loadMessages() {
    if (!this.container) return;

    // Mostrar loading apenas na primeira vez
    if (this.container.children.length === 0) {
      this.showLoading();
    }

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

            // ✅ GARANTIR QUE O CACHE ESTEJA SINCRONIZADO APÓS O LOAD INICIAL
            // Alguns cenários podem pular a criação do cache; aqui garantimos
            // que o estado inicial do cache reflita exatamente o que vem do banco.
            data.messages.forEach((m) => {
              if (!this.messagesCache.has(m.id)) {
                this.messagesCache.set(m.id, {
                  id: m.id,
                  resultado: m.resultado, // pode ser null
                  timestamp: Date.now(),
                });
              }
            });

            if (data.messages.length > 0) {
              // ✅ CORRIGIDO: Pegar o MAIOR ID (primeiro da lista, pois está DESC)
              this.lastUpdateId =
                data.messages[0].update_id || data.messages[0].id;
              console.log("🔄 Último Update ID:", this.lastUpdateId);
            }

            // ✅ RECARREGAMENTO EM BACKGROUND DESATIVADO - POLLING FAZ O TRABALHO
            // console.log("🚀 Iniciando background reload em 1 segundo...");
            // setTimeout(() => {
            //   console.log("🎯 EXECUTANDO reloadMessagesInBackground()");
            //   this.reloadMessagesInBackground();
            // }, 1000);
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

  // ✅ NOVA FUNÇÃO: Recarregar mensagens em background (como F5 silencioso)
  reloadMessagesInBackground() {
    this.reloadCount++;
    console.log("═".repeat(60));
    console.log(`⏰ BACKGROUND RELOAD #${this.reloadCount} - INICIANDO AGORA!`);
    console.log("═".repeat(60));

    fetch(
      "api/carregar-mensagens-banco.php?action=get-messages&t=" + Date.now()
    )
      .then((response) => response.json())
      .then((data) => {
        if (data.success && data.messages.length > 0) {
          console.log(
            `🔄 Reload #${this.reloadCount}: ${data.messages.length} mensagens do banco`
          );

          // ✅ Mostrar resultados das 3 primeiras mensagens
          data.messages.forEach((msg, idx) => {
            if (idx < 3) {
              console.log(
                `  📨 ID ${msg.id}: resultado="${msg.resultado || "null"}"`
              );
            }
          });

          // ✅ LIMPAR CONTAINER E RECRIAR TUDO (como F5 mas só nas mensagens)
          this.container.innerHTML = "";

          // ✅ RECRIAR TODAS AS MENSAGENS DO ZERO
          data.messages.forEach((msgFromDB) => {
            console.log("📝 Processando mensagem:", msgFromDB);

            // Criar elemento da mensagem
            const msgText = msgFromDB.text || msgFromDB.mensagem_completa || "";
            console.log(`   📄 Texto: "${msgText.substring(0, 50)}..."`);
            console.log(`   ⏰ Hora: "${msgFromDB.time}"`);
            console.log(`   🎯 Resultado: "${msgFromDB.resultado}"`);

            const formattedContent = this.formatMessage(
              msgText,
              msgFromDB.time || msgFromDB.hora_mensagem,
              msgFromDB
            );

            const messageEl = document.createElement("div");
            messageEl.className = "telegram-message";
            messageEl.setAttribute("data-message-id", msgFromDB.id);
            messageEl.innerHTML = `
              <div class="msg-header-external">
                <div class="msg-header-left">
                  <span class="msg-title-external"><i class="fas fa-bell"></i> Oportunidade!</span>
                </div>
                <div class="msg-header-right">
                  <span class="msg-time-external">
                    <i class="fas fa-clock"></i>
                    ${msgFromDB.time}
                  </span>
                </div>
              </div>
              ${formattedContent}
            `;

            // Adicionar ao container
            this.container.appendChild(messageEl);
          });

          console.log(
            `✅ Reload #${this.reloadCount} concluído - Próximo em 1s`
          );
        } else {
          console.log(
            `📭 Reload #${this.reloadCount}: Nenhuma mensagem no banco`
          );
        }

        // ✅ CONTINUAR RECARREGANDO A CADA 1 SEGUNDO
        setTimeout(() => this.reloadMessagesInBackground(), 1000);
      })
      .catch((error) => {
        console.error(`❌ Erro no reload #${this.reloadCount}:`, error);
        // Tentar novamente em 2 segundos
        setTimeout(() => this.reloadMessagesInBackground(), 2000);
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
      "[POLLING] Iniciando polling INCREMENTAL para atualizacoes em tempo real..."
    );

    // ✅ Inicializar timestamp de último check
    this.lastCheck = new Date().toISOString().slice(0, 19).replace("T", " ");
    this.lastUpdateId = 0;

    const poll = () => {
      this.pollCount++;

      // ✅ POLLING INCREMENTAL: Só buscar mensagens criadas/atualizadas desde lastCheck
      const url = `api/carregar-mensagens-banco.php?action=poll&last_check=${encodeURIComponent(
        this.lastCheck
      )}&last_update=${this.lastUpdateId}&t=${Date.now()}`;

      fetch(url)
        .then((response) => {
          if (!response.ok) {
            throw new Error("Erro HTTP: " + response.status);
          }
          return response.json();
        })
        .then((data) => {
          if (data.success) {
            // ✅ Atualizar ponteiros de tempo
            if (data.last_check) {
              this.lastCheck = data.last_check;
            }
            if (data.last_update) {
              this.lastUpdateId = data.last_update;
            }

            // ✅ Se houver mensagens atualizadas, processar
            if (data.messages && data.messages.length > 0) {
              console.log(
                `[POLLING] #${this.pollCount}: ${data.messages.length} mensagens atualizadas (modo: ${data.polling_mode})`
              );

              data.messages.forEach((msg) => {
                const cached = this.messagesCache.get(msg.id);
                console.log(`  [DEBUG] Msg ${msg.id}:`, {
                  cached: cached?.resultado || "nao existe",
                  novo: msg.resultado || "null",
                  updated_at: msg.updated_at,
                  isDifferent: !cached || cached.resultado !== msg.resultado,
                });

                // ✅ ADICIONAR ou ATUALIZAR mensagem
                const exists = document.querySelector(
                  `[data-message-id="${msg.id}"], [data-message-id="${msg.update_id}"]`
                );

                if (exists) {
                  // ✅ Mensagem já existe - ATUALIZAR
                  const cachedResultado = cached ? cached.resultado : null;
                  const serverResultado = msg.resultado || null;

                  if (cachedResultado !== serverResultado) {
                    console.warn(
                      `[UPDATE] Atualizando DOM ID ${msg.id}: "${cachedResultado}" -> "${serverResultado}"`
                    );
                    this.updateMessage(msg, exists);
                  }
                } else {
                  // ✅ Mensagem nova - ADICIONAR ao DOM
                  console.log(`[NEW] Nova mensagem detectada: ID ${msg.id}`);
                  this.addMessage(msg);
                }
              });
            }
          }
        })
        .catch((error) =>
          console.error("[ERROR] Erro ao fazer polling:", error)
        );
    };

    // ✅ POLLING RÁPIDO: A cada 500ms (meio segundo) para capturar resultados instantaneamente
    this.pollInterval = setInterval(poll, 500);
    console.log(
      "[POLLING] Polling incremental ativado - modo: updated_at + last_check"
    );
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
      console.log(`❌ Mensagem inválida ignorada: ID ${msg.id}`);
      return;
    }

    console.log(
      `📨 addMessage() chamado - ID: ${msg.id}, resultado: "${
        msg.resultado || "null"
      }"`
    );

    // ✅ VERIFICAR SE MENSAGEM JÁ EXISTE - SE SIM, ATUALIZAR
    const existingMessage = document.querySelector(
      `[data-message-id="${msg.id}"]`
    );
    if (existingMessage) {
      console.log(
        `🔄 Mensagem JÁ EXISTE no DOM, chamando updateMessage() - ID: ${msg.id}`
      );
      this.updateMessage(msg, existingMessage);
      return;
    }

    console.log(`➕ Criando NOVA mensagem - ID: ${msg.id}`);

    // ✅ ADICIONAR AO CACHE quando criar mensagem nova
    // Guardar o valor REAL de `resultado` (pode ser null) para que
    // futuras comparações detectem corretamente mudanças (null -> GREEN etc.)
    this.messagesCache.set(msg.id, {
      id: msg.id,
      resultado: msg.resultado, // armazenar o valor real (NULL ou string)
      timestamp: Date.now(),
    });

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

  // ✅ NOVA FUNÇÃO: Atualizar mensagem existente com efeito visual
  updateMessage(msg, messageEl) {
    if (!messageEl) {
      console.warn(`⚠️ messageEl não encontrado para ID: ${msg.id}`);
      return;
    }

    const msgText = msg.text || msg.mensagem_completa || "";
    const newResultado = msg.resultado || "PENDENTE";

    // ✅ BUSCAR RESULTADO ANTERIOR DO CACHE
    const cachedMsg = this.messagesCache.get(msg.id);
    const oldResultado = cachedMsg?.resultado || "PENDENTE";

    console.log(
      `🔍 updateMessage() chamado - ID: ${msg.id}`,
      `\n   Cache: "${oldResultado}"`,
      `\n   Novo: "${newResultado}"`,
      `\n   Mudou: ${oldResultado !== newResultado}`
    );

    // ✅ Se o resultado mudou, aplicar efeito visual
    const resultadoMudou = oldResultado !== newResultado;

    // ✅ SEMPRE ATUALIZAR O CONTEÚDO (mesmo sem mudança visual)
    const contentDiv = messageEl.querySelector(".telegram-formatted-message");
    if (contentDiv) {
      console.log(`   📝 Atualizando DOM para ID: ${msg.id}`);
      const formattedContent = this.formatMessage(msgText, msg.time, msg);
      contentDiv.outerHTML = formattedContent;
    } else {
      console.warn(
        `   ⚠️ .telegram-formatted-message não encontrado para ID: ${msg.id}`
      );
    }

    // ✅ ATUALIZAR CACHE COM NOVO RESULTADO (sempre)
    this.messagesCache.set(msg.id, {
      id: msg.id,
      resultado: newResultado,
      timestamp: Date.now(),
    });

    if (resultadoMudou) {
      console.log(
        `✨ RESULTADO ATUALIZADO! ${oldResultado} → ${newResultado} (ID: ${msg.id})`
      );

      // ✅ EFEITO FLASH: Adicionar classe de animação
      messageEl.classList.add("message-flash");

      // Remover a classe após a animação (2 segundos)
      setTimeout(() => {
        messageEl.classList.remove("message-flash");
      }, 2000);

      // ✅ SCROLL SUAVE até a mensagem atualizada
      setTimeout(() => {
        messageEl.scrollIntoView({ behavior: "smooth", block: "center" });
      }, 100);

      console.log("✅ Mensagem atualizada com sucesso!", msg.id);
    } else {
      console.log(`⏭️ Conteúdo atualizado silenciosamente (ID: ${msg.id})`);
    }
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
          time1 = parts[0]
            .replace(/\([^)]*\)/g, "")
            .replace(/⚽/g, "") // ✅ Remove ícone de bola
            .replace(/🚩/g, "") // ✅ Remove ícone de bandeira
            .trim();
          time2 = parts[1] 
            ? parts[1]
              .replace(/\([^)]*\)/g, "")
              .replace(/⚽/g, "")
              .replace(/🚩/g, "")
              .trim()
            : "";
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

    // ✅ DETECTAR SE É CANTOS OU GOLS PARA USAR A IMAGEM CORRETA
    const isCantos =
      tipoAposta.includes("CANTOS") || tipoAposta.includes("CANTO");
    const imagemSrc = isCantos ? "img/cantos.jpg" : "img/gol.jpg";

    // ✅ ABREVIAR TÍTULO PARA O FOOTER
    let tituloAbreviado = "";

    // Debug: Log do título original
    console.log("📝 Título original:", tipoAposta);

    if (isCantos) {
      // Se for CANTOS, tentar extrair o +XX CANTOS
      let cantosMatch = tipoAposta.match(/[\+]?\d+[\.]?\d*\s*CANTOS?/i);
      if (!cantosMatch) {
        cantosMatch = tipoAposta.match(/\d+[\.]?\d*\s*CANTOS?/i);
      }
      if (!cantosMatch) {
        cantosMatch = tipoAposta.match(/\(\s*[\+]?\d+[^)]*\)/i);
      }
      tituloAbreviado = cantosMatch ? cantosMatch[0].trim() : "CANTOS";

      // ✅ Formatar para: +XX CANTOS
      if (tituloAbreviado && !tituloAbreviado.startsWith("+")) {
        // Extrair apenas números
        const numMatch = tituloAbreviado.match(/\d+[\.]?\d*/);
        if (numMatch) {
          tituloAbreviado = "+" + numMatch[0] + " CANTOS";
        }
      }
      console.log(
        "🎯 CANTOS Match:",
        cantosMatch ? cantosMatch[0] : "NOT FOUND"
      );
    } else {
      // Se for GOLS, tentar extrair o +XX GOLS
      let golsMatch = tipoAposta.match(/[\+]?\d+[\.]?\d*\s*GOLS?/i);
      if (!golsMatch) {
        golsMatch = tipoAposta.match(/\d+[\.]?\d*\s*GOLS?/i);
      }
      if (!golsMatch) {
        golsMatch = tipoAposta.match(/\(\s*[\+]?\d+[^)]*\)/i);
      }
      tituloAbreviado = golsMatch ? golsMatch[0].trim() : "GOLS";

      // ✅ Formatar para: +XX GOLS
      if (tituloAbreviado && !tituloAbreviado.startsWith("+")) {
        // Extrair apenas números
        const numMatch = tituloAbreviado.match(/\d+[\.]?\d*/);
        if (numMatch) {
          tituloAbreviado = "+" + numMatch[0] + " GOLS";
        }
      }
      console.log("⚽ GOLS Match:", golsMatch ? golsMatch[0] : "NOT FOUND");
    }

    // ✅ Se não conseguiu extrair com regex, usar substring
    if (!tituloAbreviado || tituloAbreviado.length === 0) {
      tituloAbreviado = tipoAposta
        .replace(/📊/g, "")
        .replace(/🚨/g, "")
        .replace(/⚽/g, "")
        .replace(/⛳/g, "")
        .replace(/\([^)]*\)/g, "")
        .replace(/🚩/g, "")
        .trim()
        .substring(0, 20);
    }

    console.log("✅ Título abreviado final:", tituloAbreviado); // Formatar HTML com ícones profissionais
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
    let statusAoVivo = "Ao Vivo"; // ✅ Padrão: Ao Vivo

    if (resultado) {
      // Tem resultado - mudar para "Fim" e exibir resultado
      statusAoVivo = "Fim"; // ✅ Mudou para Fim
      if (resultado === "GREEN") {
        statusHTML = '<span class="odds-resultado odds-green">GREEN</span>';
        oddsCssClass = "odds-with-result-green";
      } else if (resultado === "RED") {
        statusHTML = '<span class="odds-resultado odds-red">RED</span>';
        oddsCssClass = "odds-with-result-red";
      } else if (resultado === "REEMBOLSO") {
        statusHTML =
          '<span class="odds-resultado odds-refund">REEMBOLSO</span>';
        oddsCssClass = "odds-with-result-refund";
      }
    } else {
      // Sem resultado - exibir PENDENTE
      statusHTML = '<span class="odds-resultado odds-pending">PENDENTE</span>';
      oddsCssClass = "odds-with-result-pending";
    }

    return `
      <div class="telegram-formatted-message">
        <!-- Info Top: Gráfico e Ao Vivo -->
        <div class="msg-info-top">
          <div class="msg-info-grafico">
            <div class="msg-icon-grafico">
              <span></span>
              <span></span>
              <span></span>
              <span></span>
            </div>
          </div>
          <span class="msg-status-ao-vivo">${statusAoVivo}</span>
        </div>

        <!-- Conteúdo Principal: VERTICAL (Imagem em cima, Times/Placar embaixo) -->
        <div class="msg-content-wrapper">
          <!-- Imagem da Bola na Rede - RETANGULAR NO TOPO -->
          <div class="msg-imagem-gol">
            <img src="${imagemSrc}" alt="Imagem da Aposta">
          </div>

          <!-- Times e Placar - EMBAIXO DA IMAGEM -->
          <div class="msg-content">
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
          </div>
        </div>
          
        <!-- Footer com Odds e Resultado -->
        <div class="msg-odds ${oddsCssClass}">
          <span>${
            isCantos ? "🚩" : "⚽"
          } ${tituloAbreviado} - ODDS - $${odds}</span>
          ${statusHTML}
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
