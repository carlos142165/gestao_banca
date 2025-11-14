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
    console.log("⚡ Polling: Verificando atualizações a cada 500ms");
    console.log("📦 Cache: Sistema de detecção de mudanças ativo");

    // ✅ INICIAR POLLING PRIMEIRO (antes de carregar as mensagens)
    // Assim, se chegar uma mensagem nova enquanto está carregando, será detectada
    this.startPolling();

    // ✅ Ler ID do usuário atual a partir do container (setado em PHP)
    try {
      const attr = this.container?.dataset?.currentUserId;
      this.currentUserId = attr ? parseInt(attr, 10) : 0;
      console.log("ℹ️ Current user id (from container):", this.currentUserId);
    } catch (err) {
      this.currentUserId = 0;
    }

    // ✅ DEPOIS carregar as mensagens
    this.loadMessages();
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
                  ${
                    this.currentUserId === 23
                      ? `
                    <button class="btn-delete-message" data-message-id="${msgFromDB.id}" title="Deletar mensagem" style="
                      margin-left: 8px;
                      background: transparent;
                      border: none;
                      color: #ff4444;
                      font-size: 15px;
                      cursor: pointer;
                      transition: all 0.25s ease;
                      padding: 4px 8px;
                      border-radius: 4px;
                      position: relative;
                    "
                    onmouseover="this.style.background='rgba(255,68,68,0.15)'; this.style.transform='scale(1.15)'; this.style.color='#ff0000';"
                    onmouseout="this.style.background='transparent'; this.style.transform='scale(1)'; this.style.color='#ff4444';">
                      <i class="fas fa-trash"></i>
                    </button>
                  `
                      : ""
                  }
                </div>
              </div>
              ${formattedContent}
            `;

            // Adicionar ao container
            this.container.appendChild(messageEl);

            // Handler do botão deletar caso exista
            const btnDel = messageEl.querySelector(".btn-delete-message");
            if (btnDel) {
              btnDel.addEventListener("click", (ev) => {
                ev.stopPropagation();
                const messageId = parseInt(btnDel.dataset.messageId, 10);
                if (!messageId) return;
                // Usar a mesma modal customizada
                this.showDeleteConfirmation(messageId, messageEl);
              });
            }
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
    // IMPORTANTE: Usar a hora atual menos 5 minutos para garantir que pega mensagens antigas também
    const now = new Date();
    const fiveMinutesAgo = new Date(now.getTime() - 5 * 60 * 1000);
    this.lastCheck = fiveMinutesAgo
      .toISOString()
      .slice(0, 19)
      .replace("T", " ");
    this.lastUpdateId = 0;

    console.log(`[POLLING] Iniciando com lastCheck: ${this.lastCheck}`);

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
          console.log(`[POLLING] #${this.pollCount} - Resposta da API:`, {
            success: data.success,
            messages_count: data.messages?.length || 0,
            last_check: data.last_check,
            last_update: data.last_update,
            polling_mode: data.polling_mode,
          });

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
                // ✅ VERIFICAR NO CACHE SE A MENSAGEM JÁ FOI VISTA
                const cached = this.messagesCache.get(msg.id);
                const isNewMessage = !cached; // Se não está no cache, é nova

                console.log(`  [DEBUG] Msg ${msg.id}:`, {
                  isNew: isNewMessage,
                  cachedResultado: cached?.resultado || "n/a",
                  novoResultado: msg.resultado || "null",
                  isDifferent: !cached || cached.resultado !== msg.resultado,
                });

                // ✅ CRIAR HASH ÚNICO PARA MATCH PRECISO (times + OVER/UNDER)
                const overUnderMatch = (
                  msg.titulo ||
                  msg.text ||
                  msg.mensagem_completa ||
                  ""
                ).match(/([+\-]?\d+\.?\d*)\s*(?:GOLS?|⚽|GOL|CANTOS?)/i);
                const overUnderValue = overUnderMatch ? overUnderMatch[1] : "";
                const uniqueHash = `${msg.time_1 || ""}_${
                  msg.time_2 || ""
                }_${overUnderValue}`
                  .toLowerCase()
                  .replace(/\s+/g, "_");

                // ✅ PROCURAR A MENSAGEM NO DOM
                let exists = document.querySelector(
                  `[data-message-id="${msg.id}"]`
                );

                // ✅ SE NÃO ENCONTROU POR ID, TENTAR PELO HASH ÚNICO (mais preciso)
                if (!exists && uniqueHash) {
                  exists = document.querySelector(
                    `[data-unique-hash="${uniqueHash}"]`
                  );
                  if (exists) {
                    console.log(
                      `   🔍 Encontrada por HASH em vez de ID: ${uniqueHash}`
                    );
                  }
                }

                // ✅ LÓGICA DE DECISÃO
                if (isNewMessage) {
                  // ✅ MENSAGEM NUNCA FOI VISTA - ADICIONAR AO DOM
                  console.log(`[NEW] 🆕 Nova mensagem detectada: ID ${msg.id}`);
                  this.addMessage(msg);
                } else if (exists && cached.resultado !== msg.resultado) {
                  // ✅ MENSAGEM EXISTE E RESULTADO MUDOU - ATUALIZAR
                  console.warn(
                    `[UPDATE] ⚡ Resultado atualizado ID ${msg.id}: "${cached.resultado}" -> "${msg.resultado}"`
                  );
                  this.updateMessage(msg, exists);
                } else if (!exists) {
                  // ✅ MENSAGEM ESTAVA NO CACHE MAS NÃO ESTÁ NO DOM - RE-ADICIONAR
                  console.log(
                    `[RECOVER] 🔄 Mensagem perdida no DOM, re-adicionando ID: ${msg.id}`
                  );
                  this.addMessage(msg);
                } else {
                  // ✅ MENSAGEM EXISTE E RESULTADO IGUAL - SEM AÇÃO
                  console.log(`[NOOP] ⏭️ Sem mudanças para ID ${msg.id}`);
                }
              });
            }
          } else {
            console.warn("[POLLING] API retornou success=false:", data);
          }
        })
        .catch((error) => {
          console.error("[ERROR] Erro ao fazer polling:", error);
        });
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

    // ✅ CRIAR IDENTIFICADOR ÚNICO: times + OVER/UNDER value para match preciso
    const overUnderMatch = (msg.titulo || msg.text || "").match(
      /([+\-]?\d+\.?\d*)\s*(?:GOLS?|⚽|GOL|CANTOS?)/i
    );
    const overUnderValue = overUnderMatch ? overUnderMatch[1] : "";
    const uniqueHash = `${msg.time_1 || ""}_${
      msg.time_2 || ""
    }_${overUnderValue}`
      .toLowerCase()
      .replace(/\s+/g, "_");
    messageEl.setAttribute("data-unique-hash", uniqueHash);

    console.log(
      `✅ Mensagem criada - ID: ${msg.id}, Hash: ${uniqueHash}, OVER/UNDER: ${overUnderValue}`
    );

    // ✅ ADICIONAR CLASSE DE RESULTADO PARA COLORIR BORDA LEFT
    if (msg.resultado === "GREEN") {
      messageEl.classList.add("msg-with-green-result");
    } else if (msg.resultado === "RED") {
      messageEl.classList.add("msg-with-red-result");
    } else if (msg.resultado === "REEMBOLSO") {
      messageEl.classList.add("msg-with-refund-result");
    } else {
      // PENDENTE é o padrão
      messageEl.classList.add("msg-with-pending-result");
    }
    messageEl.innerHTML = `
      <div class="msg-header-external">
        <div class="msg-header-left">
          <span class="msg-title-external"><i class="fas fa-bell"></i> Oportunidade!</span>
        </div>
        <div class="msg-header-right">
          <span class="msg-time-external">
            <i class="fas fa-clock"></i>
            ${msg.time || msg.hora_mensagem || ""}
          </span>
          ${
            this.currentUserId === 23
              ? `
            <button class="btn-delete-message" data-message-id="${msg.id}" title="Deletar mensagem" style="
              margin-left: 8px;
              background: transparent;
              border: none;
              color: #ff4444;
              font-size: 15px;
              cursor: pointer;
              transition: all 0.25s ease;
              padding: 4px 8px;
              border-radius: 4px;
              position: relative;
            "
            onmouseover="this.style.background='rgba(255,68,68,0.15)'; this.style.transform='scale(1.15)'; this.style.color='#ff0000';"
            onmouseout="this.style.background='transparent'; this.style.transform='scale(1)'; this.style.color='#ff4444';">
              <i class="fas fa-trash"></i>
            </button>
          `
              : ""
          }
        </div>
      </div>
      ${formattedContent}
    `;

    // ✅ INSERIR NO INÍCIO (para ordem de cima para baixo)
    this.container.insertBefore(messageEl, this.container.firstChild);

    // ✅ ADICIONAR EVENT LISTENERS
    // Clique no card inteiro abre modal com resultados do time
    messageEl.addEventListener("click", (e) => {
      if (e.target.closest(".btn-grafico-resultados")) {
        // Se clicou no gráfico, não propagate
        e.stopPropagation();
        this.mostrarResultadosTime(msg, messageEl);
      } else {
        // Clique em qualquer lugar do card
        this.mostrarResultadosTime(msg, messageEl);
      }
    });

    // ✅ Handler do botão de deletar (apenas aparece para usuário 23)
    const btnDelete = messageEl.querySelector(".btn-delete-message");
    if (btnDelete) {
      btnDelete.addEventListener("click", (ev) => {
        ev.stopPropagation();
        const messageId = parseInt(btnDelete.dataset.messageId, 10);
        if (!messageId) return;

        // ✅ Modal de confirmação customizado (em vez de confirm())
        this.showDeleteConfirmation(messageId, messageEl);
      });
    }

    // Hover effect - mudar cursor
    messageEl.style.cursor = "pointer";
    messageEl.addEventListener("mouseenter", () => {
      messageEl.style.opacity = "0.95";
    });
    messageEl.addEventListener("mouseleave", () => {
      messageEl.style.opacity = "1";
    });

    // Scroll para cima (primeira mensagem)
    setTimeout(() => this.scrollToTop(), 100);
  },

  // ✅ NOVA FUNÇÃO: Mostrar resultados do time em um modal
  mostrarResultadosTime(msg, messageEl) {
    const time1 = msg.time_1 || "---";
    const time2 = msg.time_2 || "---";

    // 🔧 USAR O TÍTULO DO MSG (já tem a informação correta)
    let titulo = (msg.titulo || msg.text || "").toLowerCase();

    console.log("🔍 mostrarResultadosTime chamada:");
    console.log("  msg.titulo:", msg.titulo);
    console.log("  Título final (lowercase):", titulo);

    // 🔧 EXTRAIR REFERÊNCIA ESPECÍFICA DO TÍTULO (+0.5GOL, +1GOL, +1CANTOS, etc)
    // Esta função detecta o tipo exato da aposta para filtro preciso
    let tipo = "gols"; // default
    let referencia = ""; // armazenar a referência específica

    // Detectar padrões específicos: +0.5 GOL, +1 GOL, +1 CANTOS, etc
    const padroesReferencia = [
      {
        regex: /\+0\.?5\s*(?:⚽|gol|gols)/i,
        ref: "+0.5GOL",
        categoria: "gols",
      },
      {
        regex: /\+1\s*(?:⚽|gol|gols)(?!\.)(?!\d)/i,
        ref: "+1GOL",
        categoria: "gols",
      }, // Evita +1.5
      {
        regex: /\+1\s*(?:⛳|cantos?|escanteios?)/i,
        ref: "+1CANTOS",
        categoria: "cantos",
      },
      {
        regex: /\+2\.?5\s*(?:⚽|gol|gols)/i,
        ref: "+2.5GOL",
        categoria: "gols",
      },
      {
        regex: /\+3\.?5\s*(?:⚽|gol|gols)/i,
        ref: "+3.5GOL",
        categoria: "gols",
      },
    ];

    // Procurar pelas referências específicas
    for (const padrao of padroesReferencia) {
      console.log(`  Testando regex: ${padrao.regex} contra: "${titulo}"`);
      if (padrao.regex.test(titulo)) {
        referencia = padrao.ref;
        tipo = padrao.categoria;
        console.log(
          `  ✅ MATCH! Referência detectada: ${referencia} (${tipo})`
        );
        break;
      }
    }

    // Fallback: se não detectou referência específica, usar detecção genérica
    if (!referencia) {
      if (msg.tipo_aposta) {
        // Campo tipo_aposta vem do banco de dados
        tipo = msg.tipo_aposta.toLowerCase().includes("canto")
          ? "cantos"
          : "gols";
      } else {
        // Fallback: detectar pelo título
        tipo =
          titulo.includes("⛳") ||
          titulo.includes("canto") ||
          titulo.includes("escanteio")
            ? "cantos"
            : "gols";
      }

      // Se não detectou tipo_aposta específico, usar genérico
      referencia = tipo === "cantos" ? "+1CANTOS" : "+1GOL";
      console.log(`⚠️ Usando detecção genérica: ${referencia}`);
    }

    // Criar elemento temporário com data attributes para a função existente usar
    const elemento = document.createElement("div");
    elemento.dataset.time1 = time1;
    elemento.dataset.time2 = time2;
    elemento.dataset.tipo = referencia; // Enviar a referência específica (+0.5GOL, +1GOL, etc)

    console.log(`📊 ENVIANDO PARA MODAL:`, {
      time1: time1,
      time2: time2,
      tipo: referencia,
      titulo: titulo,
    });

    // Usar a função existente de modal histórico
    if (typeof abrirModalHistorico === "function") {
      abrirModalHistorico(elemento);
    } else {
      console.warn(
        "⚠️ Função abrirModalHistorico não encontrada. Verifique se modal-historico-resultados.js foi carregado."
      );
    }
  }, // ✅ NOVA FUNÇÃO: Atualizar mensagem existente com efeito visual
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
    // ✅ TAMBÉM ARMAZENAR O HASH ÚNICO PARA REFERÊNCIA FUTURA
    const overUnderMatch = (msgText || msg.titulo || "").match(
      /([+\-]?\d+\.?\d*)\s*(?:GOLS?|⚽|GOL|CANTOS?)/i
    );
    const overUnderValue = overUnderMatch ? overUnderMatch[1] : "";
    const uniqueHash = `${msg.time_1 || ""}_${
      msg.time_2 || ""
    }_${overUnderValue}`
      .toLowerCase()
      .replace(/\s+/g, "_");

    this.messagesCache.set(msg.id, {
      id: msg.id,
      resultado: newResultado,
      timestamp: Date.now(),
      uniqueHash: uniqueHash,
      overUnderValue: overUnderValue,
    });

    if (resultadoMudou) {
      console.log(
        `✨ RESULTADO ATUALIZADO! ${oldResultado} → ${newResultado} (ID: ${msg.id})`
      );

      // ✅ MUDAR COR DA BORDA LEFT DE ACORDO COM RESULTADO
      messageEl.classList.remove(
        "msg-with-green-result",
        "msg-with-red-result",
        "msg-with-refund-result",
        "msg-with-pending-result"
      );
      if (msg.resultado === "GREEN") {
        messageEl.classList.add("msg-with-green-result");
      } else if (msg.resultado === "RED") {
        messageEl.classList.add("msg-with-red-result");
      } else if (msg.resultado === "REEMBOLSO") {
        messageEl.classList.add("msg-with-refund-result");
      } else {
        messageEl.classList.add("msg-with-pending-result");
      }

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
                <div class="buscando-icon-container">
                    <i class="fas fa-search buscando-icon"></i>
                    <span class="buscando-pulse-ring pulse-1"></span>
                    <span class="buscando-pulse-ring pulse-2"></span>
                    <span class="buscando-pulse-ring pulse-3"></span>
                </div>
                <p class="buscando-text">Buscando Melhor Oportunidade</p>
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

      // ✅ Formatar para: +XX CANTOS ASIATICOS
      if (tituloAbreviado && !tituloAbreviado.startsWith("+")) {
        // Extrair apenas números
        const numMatch = tituloAbreviado.match(/\d+[\.]?\d*/);
        if (numMatch) {
          tituloAbreviado = "+ " + numMatch[0] + " CANTOS ASIATICOS";
        }
      } else if (tituloAbreviado.startsWith("+")) {
        // Se já começar com +, adicionar espaço e complemento
        const numMatch = tituloAbreviado.match(/\d+[\.]?\d*/);
        if (numMatch) {
          tituloAbreviado = "+ " + numMatch[0] + " CANTOS ASIATICOS";
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

      if (golsMatch) {
        const numMatch = golsMatch[0].match(/[\+]?(\d+[\.]?\d*)/);
        if (numMatch) {
          const valor = numMatch[1];
          // ✅ Se contém ".5", é "GOL - FT"
          if (valor.includes(".5")) {
            tituloAbreviado = "+ " + valor + " GOL - FT";
          } else {
            // ✅ Senão, é "GOLS ASIATICOS"
            tituloAbreviado = "+ " + valor + " GOLS ASIATICOS";
          }
        } else {
          tituloAbreviado = "GOLS";
        }
      } else {
        tituloAbreviado = "GOLS";
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
    let borderLeftColor = "#fbc02d"; // ✅ Cor padrão: amarelo (PENDENTE)

    if (resultado) {
      // Tem resultado - mudar para "Fim" e exibir resultado
      statusAoVivo = "Fim"; // ✅ Mudou para Fim
      if (resultado === "GREEN") {
        statusHTML =
          '<span class="odds-resultado odds-green" style="padding: 4px 10px; border-radius: 4px; background: #4caf50; color: white; font-size: 11px;">GREEN</span>';
        oddsCssClass = "odds-with-result-green";
        borderLeftColor = "#4caf50"; // ✅ Verde
      } else if (resultado === "RED") {
        statusHTML =
          '<span class="odds-resultado odds-red" style="padding: 4px 10px; border-radius: 4px; background: #f44336; color: white; font-size: 11px;">RED</span>';
        oddsCssClass = "odds-with-result-red";
        borderLeftColor = "#f44336"; // ✅ Vermelho
      } else if (resultado === "REEMBOLSO") {
        statusHTML =
          '<span class="odds-resultado odds-refund" style="padding: 4px 10px; border-radius: 4px; background: #9e9e9e; color: white; font-size: 11px;">REEMBOLSO</span>';
        oddsCssClass = "odds-with-result-refund";
        borderLeftColor = "#9e9e9e"; // ✅ Cinza
      }
    } else {
      // Sem resultado - exibir PENDENTE
      statusHTML =
        '<span class="odds-resultado odds-pending" style="padding: 4px 10px; border-radius: 4px; background: #fbc02d; color: white; font-size: 11px;">PENDENTE</span>';
      oddsCssClass = "odds-with-result-pending";
      borderLeftColor = "#fbc02d"; // ✅ Amarelo
    }

    // ✅ LAYOUT COM IMAGEM DE FUNDO (gol.jpg ou cantos.jpg)
    return `
      <div class="telegram-formatted-message" style="
        background-image: url('${imagemSrc}');
        background-size: cover;
        background-position: center;
        position: relative;
        border-radius: 6px;
        overflow: hidden;
        margin: 8px 0;
        cursor: pointer;
      ">
        <!-- OVERLAY ESCURO -->
        <div style="
          position: absolute;
          top: 0;
          left: 0;
          right: 0;
          bottom: 0;
          background: rgba(0, 0, 0, 0.6);
          z-index: 1;
        "></div>
        
        <!-- CONTEÚDO -->
        <div style="
          position: relative;
          z-index: 2;
          padding: 12px;
          display: flex;
          flex-direction: column;
          gap: 13px;
          color: white;
        ">
          <!-- Status "Ao Vivo" ou "Fim" no canto superior direito -->
          <div style="position: absolute; top: 8px; right: 8px; z-index: 10; display: flex; align-items: center; gap: 4px; font-size: 9px; color: white; font-weight: 600;">
            ${
              resultado
                ? '<span style="width: 8px; height: 8px; background: #f44336; border-radius: 50%; display: inline-block;"></span><span style="font-size: 9px; color: #f44336; font-weight: 700;">FIM</span>'
                : '<span style="width: 8px; height: 8px; background: #e74c3c; border-radius: 50%; animation: piscar 1s infinite;"></span><span>Ao Vivo</span>'
            }
          </div>
          
          <!-- Tipo Aposta -->
          <div style="font-weight: 700; font-size: 14px; ${
            isCantos ? "color: #ffeb3b;" : "color: #4ade80;"
          }; text-transform: capitalize;">
            ${tituloAbreviado.toLowerCase()}
          </div>
          
          <!-- Times e Placar -->
          <div style="display: flex; align-items: center; justify-content: center; gap: 6px; font-weight: 600; font-size: 13px;">
            <div style="text-align: right; min-width: 50px;">
              ${time1 ? time1.substring(0, 12) : "---"}
            </div>
            <div style="display: flex; gap: 3px; align-items: center;">
              <div style="text-align: center; min-width: 28px; font-size: 16px; font-weight: 700;">${placar1}</div>
              <div style="font-size: 14px;">X</div>
              <div style="text-align: center; min-width: 28px; font-size: 16px; font-weight: 700;">${placar2}</div>
            </div>
            <div style="text-align: left; min-width: 50px;">
              ${time2 ? time2.substring(0, 12) : "---"}
            </div>
          </div>
          
          <!-- Odds e Resultado -->
          <div style="display: flex; justify-content: space-between; align-items: center; font-size: 12px; font-weight: 600;">
            <div style="color: #ffeb3b;">
              💰 R$ ${odds}
            </div>
            <div style="font-size: 12px;">
              ${statusHTML}
            </div>
          </div>
        </div>
        
        <!-- ESTILO RESPONSIVO PARA MOBILE -->
        <style>
          @keyframes piscar {
            0%, 49% { opacity: 1; }
            50%, 100% { opacity: 0.4; }
          }
          
          .btn-grafico-resultados:hover {
            opacity: 1 !important;
          }
          
          @media (max-width: 480px) {
            .telegram-formatted-message .odds-resultado {
              font-size: 10px !important;
              padding: 3px 8px !important;
            }
          }
        </style>
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

  // ✅ NOVA FUNÇÃO: Modal de confirmação customizado para deletar mensagem
  showDeleteConfirmation(messageId, messageElement) {
    // Criar overlay modal
    const overlay = document.createElement("div");
    overlay.style.cssText = `
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0,0,0,0.5);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 10000;
      animation: fadeIn 0.3s ease;
    `;

    // Criar modal
    const modal = document.createElement("div");
    modal.style.cssText = `
      background: white;
      border-radius: 12px;
      padding: 32px;
      max-width: 400px;
      text-align: center;
      box-shadow: 0 10px 40px rgba(0,0,0,0.3);
      animation: slideUp 0.3s ease;
    `;

    modal.innerHTML = `
      <div style="margin-bottom: 16px;">
        <i class="fas fa-exclamation-circle" style="font-size: 48px; color: #ff4444;"></i>
      </div>
      <h3 style="margin: 16px 0; color: #333; font-size: 18px;">Deletar Mensagem?</h3>
      <p style="color: #666; margin: 12px 0 24px 0; font-size: 14px;">Esta ação <strong>não pode ser desfeita</strong>. A mensagem será removida do banco de dados permanentemente.</p>
      <div style="display: flex; gap: 12px; justify-content: center;">
        <button id="btn-cancel" style="
          padding: 10px 24px;
          background: #e0e0e0;
          border: none;
          border-radius: 6px;
          cursor: pointer;
          font-size: 14px;
          font-weight: 600;
          color: #333;
          transition: all 0.2s ease;
        ">
          Cancelar
        </button>
        <button id="btn-confirm" style="
          padding: 10px 24px;
          background: #ff4444;
          border: none;
          border-radius: 6px;
          cursor: pointer;
          font-size: 14px;
          font-weight: 600;
          color: white;
          transition: all 0.2s ease;
        ">
          Sim, Deletar
        </button>
      </div>
      <style>
        @keyframes fadeIn {
          from { opacity: 0; }
          to { opacity: 1; }
        }
        @keyframes slideUp {
          from { transform: translateY(20px); opacity: 0; }
          to { transform: translateY(0); opacity: 1; }
        }
        #btn-cancel:hover {
          background: #d0d0d0;
          transform: translateY(-2px);
        }
        #btn-confirm:hover {
          background: #ff2222;
          transform: translateY(-2px);
          box-shadow: 0 4px 12px rgba(255,68,68,0.3);
        }
      </style>
    `;

    overlay.appendChild(modal);
    document.body.appendChild(overlay);

    const btnCancel = modal.querySelector("#btn-cancel");
    const btnConfirm = modal.querySelector("#btn-confirm");

    // Fechar modal
    const closeModal = () => {
      overlay.style.animation = "fadeOut 0.3s ease";
      setTimeout(() => overlay.remove(), 300);
    };

    btnCancel.addEventListener("click", closeModal);
    overlay.addEventListener("click", (e) => {
      if (e.target === overlay) closeModal();
    });

    // Confirmar delete
    btnConfirm.addEventListener("click", () => {
      btnConfirm.disabled = true;
      btnConfirm.textContent = "⏳ Deletando...";

      fetch("api/deletar-mensagem.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ message_id: messageId }),
      })
        .then((r) => r.json())
        .then((resp) => {
          if (resp && resp.success) {
            // Remover do DOM com animação
            messageElement.style.animation = "slideOut 0.3s ease";
            setTimeout(() => {
              messageElement.remove();
              this.messagesCache.delete(messageId);
              closeModal();
              console.log("✅ Mensagem deletada:", messageId);
            }, 300);
          } else {
            alert("❌ Erro: " + (resp.message || "Erro desconhecido"));
            btnConfirm.disabled = false;
            btnConfirm.textContent = "Sim, Deletar";
          }
        })
        .catch((err) => {
          console.error("Erro ao deletar:", err);
          alert("❌ Erro ao deletar mensagem. Veja console para detalhes.");
          btnConfirm.disabled = false;
          btnConfirm.textContent = "Sim, Deletar";
        });
    });

    // Adicionar CSS para animação de saída
    const style = document.createElement("style");
    style.textContent = `
      @keyframes slideOut {
        from { opacity: 1; transform: translateX(0); }
        to { opacity: 0; transform: translateX(100%); }
      }
      @keyframes fadeOut {
        from { opacity: 1; }
        to { opacity: 0; }
      }
    `;
    document.head.appendChild(style);
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
