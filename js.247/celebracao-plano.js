/**
 * ============================================
 * SISTEMA GLOBAL DE CELEBRAÇÃO DE PLANO
 * ============================================
 *
 * Exibe modal de felicitação quando usuário:
 * - Faz login (em qualquer página)
 * - Contrata um novo plano
 * - Recebe um bonus de assinatura
 *
 * Funciona em qualquer página do site
 * Se o plano mudou desde último acesso, mostra celebração
 */

class CelebracaoPlanoGlobal {
  constructor() {
    this.planosConfig = {
      GRATUITO: {
        cor: "#95a5a6",
        corEscura: "#7f8c8d",
        icone: "fas fa-gift",
        titulo: "Bem-vindo!",
        mensagem: "Você tem acesso à plataforma com recursos básicos.",
      },
      PRATA: {
        cor: "#c0392b",
        corEscura: "#a93226",
        icone: "fas fa-coins",
        titulo: "Parabéns! 🎉",
        mensagem: "Você agora faz parte do plano PRATA!",
      },
      OURO: {
        cor: "#f39c12",
        corEscura: "#d68910",
        icone: "fas fa-star",
        titulo: "Parabéns! 🎉",
        mensagem: "Você agora faz parte do plano OURO!",
      },
      DIAMANTE: {
        cor: "#2980b9",
        corEscura: "#1f618d",
        icone: "fas fa-gem",
        titulo: "Parabéns! 🎉",
        mensagem: "Você agora faz parte do plano DIAMANTE!",
      },
    };

    this.init();
  }

  async init() {
    console.log("🎉 Sistema de celebração inicializado");

    // Aguardar um pouco para página carregar
    setTimeout(() => {
      this.verificarPlano();
    }, 500);

    // Verificar a cada 3 segundos se houve mudança de plano (para pagamentos em tempo real)
    setInterval(() => {
      this.verificarPlanoPeriodicament();
    }, 3000);

    // Escutar mudanças via localStorage (para múltiplas abas)
    window.addEventListener("storage", (event) => {
      if (event.key === "plano_usuario_atual") {
        console.log("📢 Mudança de plano detectada em outra aba!");
        this.verificarPlano();
      }
    });
  }

  async verificarPlano() {
    try {
      // Buscar dados do usuário
      const resposta = await fetch("minha-conta.php?acao=obter_dados");
      const dados = await resposta.json();

      if (dados.success && dados.usuario) {
        const planoAtual = dados.usuario.plano || "Gratuito";
        const planoAnterior = localStorage.getItem("plano_usuario_atual");
        const ultimaCelebracao = sessionStorage.getItem(
          "ultima_celebracao_plano"
        );

        console.log("📊 Plano anterior (localStorage):", planoAnterior);
        console.log("📊 Plano atual:", planoAtual);
        console.log("📊 Última celebração:", ultimaCelebracao);

        // Se plano mudou desde o armazenado no localStorage
        if (
          planoAnterior &&
          planoAnterior !== planoAtual &&
          ultimaCelebracao !== planoAtual
        ) {
          console.log("✅ Novo plano detectado! Mostrando celebração...");
          this.mostrarCelebracao(planoAtual);

          // Marcar celebração nesta sessão
          sessionStorage.setItem("ultima_celebracao_plano", planoAtual);
        }

        // Se é a primeira vez (sem planoAnterior), apenas salva
        if (!planoAnterior) {
          console.log("✅ Primeiro acesso. Salvando plano no localStorage...");
        }

        // Sempre salvar plano atual no localStorage
        localStorage.setItem("plano_usuario_atual", planoAtual);
      }
    } catch (erro) {
      console.error("❌ Erro ao verificar plano:", erro);
    }
  }

  async verificarPlanoPeriodicament() {
    try {
      const resposta = await fetch("minha-conta.php?acao=obter_dados");
      const dados = await resposta.json();

      if (dados.success && dados.usuario) {
        const planoAtual = dados.usuario.plano || "Gratuito";
        const planoAnterior = localStorage.getItem("plano_usuario_atual");
        const ultimaCelebracao = sessionStorage.getItem(
          "ultima_celebracao_plano"
        );

        // Se plano mudou (pagamento em tempo real)
        if (
          planoAnterior &&
          planoAnterior !== planoAtual &&
          ultimaCelebracao !== planoAtual
        ) {
          console.log("🔄 Mudança de plano detectada em tempo real!");
          this.mostrarCelebracao(planoAtual);
          sessionStorage.setItem("ultima_celebracao_plano", planoAtual);
          localStorage.setItem("plano_usuario_atual", planoAtual);
        }
      }
    } catch (erro) {
      // Silencioso em caso de erro (chamada periódica)
    }
  }

  mostrarCelebracao(plano) {
    // Não mostrar se já tem uma celebração aberta
    if (document.getElementById("celebracao-plano-modal")) {
      console.log("⏭️  Celebração já aberta, ignorando...");
      return;
    }
    const config =
      this.planosConfig[plano.toUpperCase()] || this.planosConfig.GRATUITO;

    // Criar modal
    const modal = document.createElement("div");
    modal.className = "celebracao-plano-overlay";
    modal.id = "celebracao-plano-modal";
    modal.innerHTML = `
      <div class="celebracao-plano-container">
        <button class="celebracao-plano-close" onclick="document.getElementById('celebracao-plano-modal').remove()">
          <i class="fas fa-times"></i>
        </button>

        <div class="celebracao-confetti"></div>

        <div class="celebracao-conteudo">
          <div class="celebracao-icone" style="background: linear-gradient(135deg, ${
            config.cor
          }, ${config.corEscura}); color: white;">
            <i class="${config.icone}"></i>
          </div>

          <h1 class="celebracao-titulo">${config.titulo}</h1>

          <p class="celebracao-mensagem">${config.mensagem}</p>

          <div class="celebracao-plano-badge">
            <span class="badge" style="border-color: ${config.cor}; color: ${
      config.cor
    };">
              <i class="${config.icone}"></i>
              <span>${plano.toUpperCase()}</span>
            </span>
          </div>

          <div class="celebracao-beneficios">
            <p class="beneficio-titulo">✨ Seus Benefícios:</p>
            ${this.gerarBeneficios(plano)}
          </div>

          <button class="celebracao-btn-continuar" onclick="document.getElementById('celebracao-plano-modal').remove()">
              Continuar
          </button>
        </div>
      </div>
    `;

    document.body.appendChild(modal);

    console.log("🎉 Modal de celebração exibido para:", plano);

    // Efeito de confete
    this.criarConfete();

    // ⏱️ NÃO fecha automaticamente - Apenas quando clicar em "Continuar"
  }

  gerarBeneficios(plano) {
    const beneficios = {
      GRATUITO: [
        "Acesso básico à plataforma",
        "1 mentor simultâneo",
        "Histórico de 30 dias",
      ],
      PRATA: [
        "Até 5 mentores simultâneos",
        "Histórico de 6 meses",
        "Relatórios detalhados",
        "Suporte prioritário",
      ],
      OURO: [
        "Até 10 mentores simultâneos",
        "Histórico completo",
        "Relatórios avançados",
        "Suporte VIP 24/7",
        "Análise estratégica",
      ],
      DIAMANTE: [
        "Mentores ilimitados",
        "Histórico permanente",
        "Relatórios em tempo real",
        "Suporte dedicado",
        "Consultoria personalizada",
        "Acesso a recursos exclusivos",
      ],
    };

    const lista = beneficios[plano.toUpperCase()] || beneficios.GRATUITO;

    return lista
      .map(
        (beneficio) =>
          `<p class="beneficio-item"><i class="fas fa-check-circle"></i> ${beneficio}</p>`
      )
      .join("");
  }

  criarConfete() {
    const confeteContainer = document.querySelector(".celebracao-confetti");
    if (!confeteContainer) return;

    const colors = ["#c0392b", "#f39c12", "#2980b9", "#27ae60", "#9b59b6"];

    for (let i = 0; i < 50; i++) {
      const confete = document.createElement("div");
      confete.className = "confete";
      confete.style.left = Math.random() * 100 + "%";
      confete.style.backgroundColor =
        colors[Math.floor(Math.random() * colors.length)];
      confete.style.animationDelay = Math.random() * 0.5 + "s";
      confeteContainer.appendChild(confete);
    }
  }
}

// Inicializar quando DOM estiver pronto
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", () => {
    new CelebracaoPlanoGlobal();
  });
} else {
  new CelebracaoPlanoGlobal();
}

// Exportar para window para usar em outros scripts
window.CelebracaoPlanoGlobal = CelebracaoPlanoGlobal;
