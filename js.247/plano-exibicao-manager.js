/**
 * GERENCIADOR DE EXIBIÇÃO DO PLANO DO USUÁRIO
 * ============================================
 * Carrega e exibe o tipo de plano atual do usuário no topo da página
 */

const PlanoExibicaoManager = {
  // Cores dos planos (para badge de status)
  cores: {
    GRATUITO: "#95a5a6",
    PRATA: "#c0392b",
    OURO: "#f39c12",
    DIAMANTE: "#2980b9",
  },

  // Ícones dos planos
  icones: {
    GRATUITO: "fas fa-gift",
    PRATA: "fas fa-coins",
    OURO: "fas fa-star",
    DIAMANTE: "fas fa-gem",
  },

  /**
   * Inicializar gerenciador
   */
  async inicializar() {
    console.log("📊 PlanoExibicaoManager inicializado");

    // Carregar dados do plano
    await this.carregarPlano();

    // Atualizar a cada 5 minutos
    setInterval(() => this.carregarPlano(), 5 * 60 * 1000);
  },

  /**
   * Carregar dados do plano do usuário
   */
  async carregarPlano() {
    try {
      const response = await fetch("obter-plano-usuario.php");
      const dados = await response.json();

      if (!dados.sucesso) {
        console.warn("❌ Erro ao carregar plano:", dados.mensagem);
        return;
      }

      // Armazenar dados globalmente
      window.planoAtual = dados.plano;

      // Exibir o plano
      this.exibirPlano(dados.plano);

      console.log("✅ Plano carregado:", dados.plano.nome);
    } catch (error) {
      console.error("❌ Erro ao carregar plano:", error);
    }
  },

  /**
   * Exibir o plano no topo da página
   * @param {Object} plano
   */
  exibirPlano(plano) {
    // Procurar container de exibição do plano
    let container = document.getElementById("exibicao-plano-usuario");

    // Se não existir, criar
    if (!container) {
      container = this.criarContainer();
    }

    // Montar HTML do plano
    const icone = this.icones[plano.nome] || "fas fa-info-circle";
    const cor = this.cores[plano.nome] || "#333";

    let html = `
      <div class="plano-badge" style="border-left: 4px solid ${cor}">
        <div class="plano-info">
          <i class="${icone}" style="color: ${cor}; margin-right: 10px;"></i>
          <span class="plano-texto">
            <strong>Plano:</strong> <span class="plano-nome" style="color: ${cor}">${plano.nome}</span>
          </span>
        </div>
    `;

    // Se não for plano gratuito, mostrar data de expiração
    if (plano.nome !== "GRATUITO" && plano.data_fim) {
      const dataFim = new Date(plano.data_fim);
      const dataFormatada = dataFim.toLocaleDateString("pt-BR");

      html += `
        <div class="plano-detalhes">
          <small style="color: #666;">
            <i class="fas fa-calendar-alt"></i>
            Válido até ${dataFormatada}
          </small>
      `;

      // Se tiver dias restantes, mostrar
      if (plano.dias_restantes !== null) {
        html += `
          <small style="margin-left: 15px; color: #666;">
            <i class="fas fa-hourglass-end"></i>
            ${plano.dias_restantes} dias restantes
          </small>
        `;
      }

      html += `</div>`;
    }

    html += `</div>`;

    // Inserir HTML
    container.innerHTML = html;
  },

  /**
   * Criar container de exibição do plano
   * @return {HTMLElement}
   */
  criarContainer() {
    // Procurar pelo menu ou header
    const menu =
      document.querySelector(".menu-topo-container") ||
      document.querySelector(".menu-top") ||
      document.querySelector("header") ||
      document.querySelector('[data-role="header"]') ||
      document.querySelector("#menu");

    if (!menu) {
      console.warn(
        "⚠️ Menu não encontrado, criando container no topo da página"
      );
      const container = document.createElement("div");
      container.id = "exibicao-plano-usuario";
      container.style.cssText =
        "padding: 15px; background: #f5f7fa; border-bottom: 1px solid #ddd; text-align: center;";
      document.body.insertBefore(container, document.body.firstChild);
      return container;
    }

    // Inserir após o menu
    const container = document.createElement("div");
    container.id = "exibicao-plano-usuario";
    menu.insertAdjacentElement("afterend", container);

    return container;
  },

  /**
   * Mostrar modal de upgrade
   */
  mostrarUpgrade() {
    if (window.PlanoManager && window.PlanoManager.mostrarModalPlanos) {
      window.PlanoManager.mostrarModalPlanos();
    }
  },
};

// Inicializar quando documento carregar
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", () => {
    PlanoExibicaoManager.inicializar();
  });
} else {
  PlanoExibicaoManager.inicializar();
}
