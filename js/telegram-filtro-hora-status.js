/**
 * ================================================================
 * SISTEMA DE FILTRO POR HORA E STATUS - BOT AO VIVO
 * ================================================================
 *
 * Regra PRINCIPAL:
 * - Quando chegar 00:00hrs, mensagens de ONTEM saem do bloco
 * - EXCEÇÃO: Se tiverem status "PENDENTE" → mantêm visível
 * - Removidas: Quando chegarem com resultado "GREEN", "RED" ou "REEMBOLSO"
 *
 * Funcionamento:
 * 1. A cada 10 segundos, verifica se é 00:00hrs
 * 2. Se for 00:00, aplica filtro removendo apenas com resultado definido
 * 3. Mantém SEMPRE visível as mensagens com status PENDENTE
 *
 * ================================================================
 */

const TelegramFiltroHoraStatus = {
  // Armazenar mensagens removidas (para debug)
  mensagensRemovidas: new Map(),
  mensagensAtualizadas: new Map(),

  // Verificar cada 10 segundos
  verificarIntervaloMeiodia: null,

  // Flag para só executar uma vez por dia
  jaExecutouEm00h00: false,
  ultimaDataExecucao: null,

  /**
   * Inicializar o sistema de filtro
   */
  init() {
    console.log("🕐 Filtro de Hora/Status INICIALIZADO");
    console.log(
      "📋 Regra: Remover mensagens com resultado definido quando chegar 00:00hrs"
    );
    console.log("📋 Mantém: Mensagens com status PENDENTE");

    // Verificar a cada 10 segundos
    this.verificarIntervaloMeiodia = setInterval(() => {
      this.verificarMeiodia();
    }, 10000);

    // Executar verificação inicial
    this.verificarMeiodia();
  },

  /**
   * Verificar se chegou 00:00hrs
   */
  verificarMeiodia() {
    const agora = new Date();
    const hora = agora.getHours();
    const minuto = agora.getMinutes();
    const dataHoje = this.formatarData(agora);

    // Se é 00:00-00:10 (janela de execução)
    if (hora === 0 && minuto <= 10) {
      // ✅ Executar apenas uma vez por dia
      if (!this.jaExecutouEm00h00 || this.ultimaDataExecucao !== dataHoje) {
        console.log("🚨 MEIA-NOITE DETECTADA! (00:00hrs)");
        console.log("📝 Aplicando regra de filtro automático...");
        this.aplicarFiltroMeioNaite();

        this.jaExecutouEm00h00 = true;
        this.ultimaDataExecucao = dataHoje;
      }
    }
    // Reset a flag quando passar de 00:10
    else if (hora !== 0 || minuto > 10) {
      if (this.jaExecutouEm00h00) {
        console.log("✅ Janela de execução de 00:00hrs fechada");
        this.jaExecutouEm00h00 = false;
      }
    }
  },

  /**
   * Aplicar filtro quando chegar 00:00hrs
   * - Remove mensagens de ONTEM com status: GREEN, RED, REEMBOLSO
   * - Mantém visível: Mensagens PENDENTES de ontem
   * - Carrega: Novas mensagens de HOJE
   */
  aplicarFiltroMeioNaite() {
    const container = document.querySelector(".telegram-messages-wrapper");
    if (!container) {
      console.warn("❌ Container não encontrado");
      return;
    }

    console.log("\n" + "═".repeat(60));
    console.log("EXECUTANDO FILTRO DE MEIA-NOITE");
    console.log("═".repeat(60));

    // Obter todas as mensagens do bloco
    const mensagens = container.querySelectorAll(".telegram-message");
    let removidas = 0;
    let mantidas = 0;
    let detalhes = [];

    mensagens.forEach((msgEl) => {
      const msgId = msgEl.getAttribute("data-message-id");
      const timeEl = msgEl.querySelector(".msg-time-external");
      const horaMensagem = timeEl ? timeEl.textContent.trim() : "??:??";

      // ✅ VERIFICAR STATUS DA MENSAGEM (classe CSS)
      const temGreen = msgEl.classList.contains("msg-with-green-result");
      const temRed = msgEl.classList.contains("msg-with-red-result");
      const temReembolso = msgEl.classList.contains("msg-with-refund-result");
      const temPendente = msgEl.classList.contains("msg-with-pending-result");

      const resultado = temGreen
        ? "GREEN"
        : temRed
        ? "RED"
        : temReembolso
        ? "REEMBOLSO"
        : "PENDENTE";

      // ✅ LÓGICA DE FILTRO
      // Se tem resultado (GREEN, RED ou REEMBOLSO) → REMOVER
      if (temGreen || temRed || temReembolso) {
        console.log(
          `🗑️ Removendo MSG ${msgId} (${resultado}) - ${horaMensagem}`
        );

        // Armazenar para debug
        this.mensagensRemovidas.set(msgId, {
          resultado: resultado,
          horaRemocao: new Date(),
          horaMensagem: horaMensagem,
        });

        detalhes.push(
          `- MSG ${msgId}: ${resultado} (${horaMensagem}) [REMOVIDA]`
        );

        // Remover com animação fade out + slide left
        msgEl.style.transition = "all 0.4s cubic-bezier(0.4, 0, 0.6, 1)";
        msgEl.style.opacity = "0";
        msgEl.style.transform = "translateX(-100px)";
        msgEl.style.pointerEvents = "none";

        setTimeout(() => {
          if (msgEl.parentNode) {
            msgEl.remove();
          }
          removidas++;
        }, 400);
      }
      // Se está PENDENTE → MANTER VISÍVEL
      else if (temPendente) {
        console.log(
          `✅ MANTENDO MSG ${msgId} (${resultado}) - ${horaMensagem}`
        );
        detalhes.push(
          `- MSG ${msgId}: ${resultado} (${horaMensagem}) [MANTIDA]`
        );
        mantidas++;
      }
    });

    console.log(`\n📊 RESULTADO DO FILTRO:`);
    console.log(`   ✅ Mantidas: ${mantidas} mensagens com PENDENTE`);
    console.log(
      `   🗑️ Removidas: ${removidas} mensagens com resultado definido`
    );
    console.log(`\n📋 DETALHES:`);
    detalhes.forEach((d) => console.log(d));
    console.log("═".repeat(60) + "\n");

    // ✅ RECARREGAR MENSAGENS DE HOJE
    this.recarregarMensagensDeHoje();
  },

  /**
   * Recarregar mensagens de HOJE após aplicar filtro
   */
  recarregarMensagensDeHoje() {
    console.log("🔄 Recarregando mensagens de HOJE...");

    // Se TelegramMessenger está disponível, recarregar
    if (
      typeof TelegramMessenger !== "undefined" &&
      TelegramMessenger.loadMessages
    ) {
      setTimeout(() => {
        TelegramMessenger.loadMessages();
        console.log("✅ Mensagens de HOJE recarregadas");
      }, 500);
    }
  },

  /**
   * Formatar data para comparação (YYYY-MM-DD)
   */
  formatarData(data) {
    const ano = data.getFullYear();
    const mes = String(data.getMonth() + 1).padStart(2, "0");
    const dia = String(data.getDate()).padStart(2, "0");
    return `${ano}-${mes}-${dia}`;
  },

  /**
   * Parar verificação quando sair da página
   */
  stop() {
    if (this.verificarIntervaloMeiodia) {
      clearInterval(this.verificarIntervaloMeiodia);
      console.log("🛑 Filtro de Hora/Status PARADO");
    }
  },

  /**
   * DEBUG: Ver mensagens removidas
   */
  debug() {
    console.log("\n" + "═".repeat(60));
    console.log("DEBUG: MENSAGENS REMOVIDAS");
    console.log("═".repeat(60));
    console.log(this.mensagensRemovidas);
    console.log("═".repeat(60) + "\n");
  },

  /**
   * DEBUG: Ver mensagens atualizadas
   */
  debugAtualizadas() {
    console.log("\n" + "═".repeat(60));
    console.log("DEBUG: MENSAGENS ATUALIZADAS");
    console.log("═".repeat(60));
    console.log(this.mensagensAtualizadas);
    console.log("═".repeat(60) + "\n");
  },
};

// Inicializar quando o DOM estiver pronto
document.addEventListener("DOMContentLoaded", function () {
  // ✅ Aguardar TelegramMessenger inicializar antes
  setTimeout(() => {
    TelegramFiltroHoraStatus.init();
  }, 2000);
});

// Parar ao sair
window.addEventListener("beforeunload", function () {
  TelegramFiltroHoraStatus.stop();
});
