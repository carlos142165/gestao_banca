/**
 * GERENCIADOR DE PLANO DO USUÁRIO
 * ===============================
 * Carrega e exibe o plano atual do usuário
 */

const PlanoUsuarioManager = {
  // Cores por tipo de plano
  cores: {
    GRATUITO: "#95a5a6",
    PRATA: "#c0392b",
    OURO: "#f39c12",
    DIAMANTE: "#2980b9",
  },

  // Ícones por tipo de plano
  icones: {
    GRATUITO: "fas fa-gift",
    PRATA: "fas fa-coins",
    OURO: "fas fa-star",
    DIAMANTE: "fas fa-gem",
  },

  /**
   * Inicializar o gerenciador
   */
  init() {
    console.log("🚀 PlanoUsuarioManager inicializando...");
    this.carregarPlano();
    // Atualizar a cada 5 minutos
    setInterval(() => this.carregarPlano(), 5 * 60 * 1000);
  },

  /**
   * Carregar dados do plano via AJAX
   */
  carregarPlano() {
    fetch("obter-plano-usuario.php")
      .then((response) => response.json())
      .then((dados) => {
        if (dados.sucesso) {
          console.log("✅ Plano carregado:", dados.plano.nome);
          window.planoAtual = dados.plano;
          this.exibirPlano(dados.plano);
        } else {
          console.error("❌ Erro:", dados.mensagem);
        }
      })
      .catch((error) => {
        console.error("❌ Erro ao carregar plano:", error);
      });
  },

  /**
   * Exibir o plano no topo da página
   */
  exibirPlano(plano) {
    // Procurar ou criar container
    let container = document.getElementById("exibicao-plano-usuario");
    if (!container) {
      container = this.criarContainer();
    }

    // Definir cor e ícone
    const cor = this.cores[plano.nome] || "#333";
    const icone = this.icones[plano.nome] || "fas fa-info-circle";

    // Montar HTML
    let html = `
      <div class="plano-badge" style="border-left-color: ${cor}">
        <div class="plano-info">
          <i class="${icone}" style="color: ${cor}"></i>
          <span class="plano-texto">
            <strong>Plano:</strong>
            <span class="plano-nome" style="color: ${cor}">${plano.nome}</span>
          </span>
        </div>
    `;

    // Adicionar data de expiração (se não for GRATUITO)
    if (plano.nome !== "GRATUITO" && plano.data_fim) {
      const data = new Date(plano.data_fim);
      const dataFormatada = data.toLocaleDateString("pt-BR");

      html += `
        <div class="plano-detalhes">
          <small>
            <i class="fas fa-calendar"></i>
            Válido até ${dataFormatada}
          </small>
      `;

      if (plano.dias_restantes !== null) {
        html += `
          <small>
            <i class="fas fa-hourglass-end"></i>
            ${plano.dias_restantes} dias
          </small>
        `;
      }

      html += `</div>`;
    }

    html += `</div>`;

    // Inserir no DOM
    container.innerHTML = html;
  },

  /**
   * Criar container no topo da página
   */
  criarContainer() {
    const container = document.createElement("div");
    container.id = "exibicao-plano-usuario";

    // Tentar inserir após o menu
    const menu = document.querySelector(".menu-topo-container");
    if (menu) {
      menu.insertAdjacentElement("afterend", container);
    } else {
      // Se não achar o menu, inserir no topo do body
      document.body.insertBefore(container, document.body.firstChild);
    }

    return container;
  },
};

// Inicializar quando documento estiver pronto
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", () => {
    PlanoUsuarioManager.init();
  });
} else {
  PlanoUsuarioManager.init();
}
