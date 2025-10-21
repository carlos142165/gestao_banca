/**
 * EXIBIR PLANO DO USUÁRIO NO BADGE
 * =================================
 * Script simples para carregar e exibir o plano do usuário
 */

const PlanoDisplay = {
  // Cores dos planos
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
   * Inicializar
   */
  init() {
    console.log("🎯 PlanoDisplay iniciando...");
    console.log("Procurando container...");

    // Aguardar um pouco para o DOM estar pronto
    setTimeout(() => {
      this.carregarEExibir();
    }, 500);

    // Atualizar a cada 5 minutos
    setInterval(() => this.carregarEExibir(), 5 * 60 * 1000);
  },

  /**
   * Carregar dados da API e exibir
   */
  carregarEExibir() {
    console.log("📡 Chamando API...");

    fetch("obter-plano-usuario.php")
      .then((r) => r.json())
      .then((dados) => {
        console.log("📥 Resposta recebida:", dados);

        if (dados.sucesso) {
          console.log("✅ Plano carregado:", dados.plano.nome);
          this.exibir(dados.plano);
        } else {
          console.error("❌ Erro:", dados.mensagem);
        }
      })
      .catch((err) => {
        console.error("❌ Erro na requisição:", err);
      });
  },

  /**
   * Exibir o plano no badge
   */
  exibir(plano) {
    console.log("🎨 Exibindo plano:", plano.nome);

    let badge = document.getElementById("badge-plano-usuario");

    if (!badge) {
      console.warn("⚠️ Container não encontrado, procurando no DOM...");
      // Procurar pelo elemento no menu
      badge = document.querySelector(".menu-topo-container");
      if (!badge) {
        console.error("❌ Elemento .menu-topo-container não encontrado!");
        return;
      }
      // Criar div após o menu
      const newBadge = document.createElement("div");
      newBadge.id = "badge-plano-usuario";
      badge.insertAdjacentElement("afterend", newBadge);
      badge = newBadge;
    }

    const cor = this.cores[plano.nome] || "#333";
    const icone = this.icones[plano.nome] || "fas fa-info-circle";

    let html = `
      <div style="
        background: #f8f9fa;
        padding: 8px 20px;
        border-bottom: 2px solid ${cor};
        display: flex;
        justify-content: center;
        align-items: center;
        animation: slideInDown 0.4s ease-out;
      ">
        <div style="
          display: flex;
          justify-content: center;
          align-items: center;
          gap: 12px;
          background: white;
          padding: 8px 16px;
          border-radius: 6px;
          border-left: 4px solid ${cor};
          box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
          font-size: 13px;
        ">
          <i class="${icone}" style="
            font-size: 16px;
            color: ${cor};
            min-width: 16px;
          "></i>
          <span style="font-weight: 600; color: #333;">
            Plano:
          </span>
          <span style="font-size: 14px; font-weight: 700; color: ${cor}; text-transform: uppercase;">
            ${plano.nome}
          </span>
    `;

    // Se não for GRATUITO, mostrar data e dias
    if (plano.nome !== "GRATUITO" && plano.data_fim) {
      const data = new Date(plano.data_fim);
      const dataFormatada = data.toLocaleDateString("pt-BR");

      html += `
          <span style="color: #ccc; margin: 0 8px;">|</span>
          <span style="font-size: 12px; color: #666;">
            📅 ${dataFormatada}
          </span>
      `;

      if (plano.dias_restantes !== null && plano.dias_restantes >= 0) {
        html += `
          <span style="color: #ccc; margin: 0 8px;">|</span>
          <span style="font-size: 12px; color: #666;">
            ⏳ ${plano.dias_restantes} dias
          </span>
        `;
      }
    }

    html += `
        </div>
      </div>

      <style>
        @keyframes slideInDown {
          from {
            opacity: 0;
            transform: translateY(-10px);
          }
          to {
            opacity: 1;
            transform: translateY(0);
          }
        }
      </style>
    `;

    badge.innerHTML = html;
    badge.style.display = "block";

    console.log("✅ Badge exibido com sucesso!");
  },
};

// Inicializar quando documento estiver pronto
console.log("📍 Script carregado, estado do documento:", document.readyState);

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", () => {
    console.log("📍 DOMContentLoaded disparado");
    PlanoDisplay.init();
  });
} else {
  console.log("📍 Documento já carregado, iniciando...");
  PlanoDisplay.init();
}
