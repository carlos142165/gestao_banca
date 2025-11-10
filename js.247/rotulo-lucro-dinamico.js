// ===== SISTEMA DE RÓTULO DINÂMICO PARA LUCRO =====

const RotuloLucroDinamico = {
  /**
   * Obtém o rótulo e cor baseado no valor do lucro
   * @param {number} lucro - Valor do lucro
   * @returns {Object} - { rotulo, cor }
   */
  obterEstiloLucro(lucro) {
    if (lucro > 0) {
      return { rotulo: "Positivo", cor: "#9fe870" };
    } else if (lucro < 0) {
      return { rotulo: "Negativo", cor: "#e57373" };
    } else {
      return { rotulo: "Neutro", cor: "#cfd8dc" };
    }
  },

  /**
   * Atualiza o rótulo dinâmico do lucro
   * @param {number} lucro - Valor do lucro
   */
  atualizarRotuloDinamico(lucro) {
    const rotuloElement = document.getElementById("rotulo-lucro-dinamico");

    if (!rotuloElement) {
      console.warn("❌ Elemento #rotulo-lucro-dinamico não encontrado!");
      return;
    }

    const { rotulo, cor } = this.obterEstiloLucro(lucro);

    // Atualizar texto e cor com !important para sobrescrever CSS
    rotuloElement.textContent = rotulo + ":";
    rotuloElement.style.color = cor + " !important";

    console.log(`✅ Rótulo atualizado para: "${rotulo}" (${cor})`);
  },

  /**
   * Inicializa o sistema observando mudanças no elemento de lucro
   */
  inicializar() {
    const lucroElement = document.getElementById("lucro_valor_entrada");

    if (!lucroElement) {
      console.warn("❌ Elemento #lucro_valor_entrada não encontrado!");
      return;
    }

    // Observar mudanças no elemento de lucro usando MutationObserver
    const observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        if (
          mutation.type === "characterData" ||
          mutation.type === "childList"
        ) {
          const lucroText = lucroElement.textContent;
          // Extrair número do texto "R$ X,XX"
          const lucroNumero = this.extrairNumero(lucroText);

          if (!isNaN(lucroNumero)) {
            this.atualizarRotuloDinamico(lucroNumero);
          }
        }
      });
    });

    // Configurar e iniciar observação
    observer.observe(lucroElement, {
      characterData: true,
      childList: true,
      subtree: true,
    });

    // Também atualizar imediatamente ao carregar
    const lucroInitial = this.extrairNumero(lucroElement.textContent);
    if (!isNaN(lucroInitial)) {
      this.atualizarRotuloDinamico(lucroInitial);
    }

    console.log("🎯 Sistema de rótulo dinâmico inicializado!");
  },

  /**
   * Extrai o número de um texto formatado em BRL
   * @param {string} texto - Texto formatado "R$ 1.234,56"
   * @returns {number} - Número extraído
   */
  extrairNumero(texto) {
    // Remover "R$" e espaços, depois converter vírgula em ponto
    const numStr = texto
      .replace(/R\$\s?/g, "")
      .replace(/\./g, "")
      .replace(/,/g, ".");
    return parseFloat(numStr);
  },
};

// ===== AUTO-INICIALIZAÇÃO =====

// Aguardar DOM estar pronto
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", () => {
    RotuloLucroDinamico.inicializar();
  });
} else {
  // DOM já está pronto
  RotuloLucroDinamico.inicializar();
}

// ===== INTEGRAÇÃO COM FETCH EXISTENTE =====

// Se houver um sistema que faça fetch de dados de lucro, podemos também atualizar através disso
// Monitorar chamadas de fetch e atualizar quando dados de lucro chegarem
const originalFetch = window.fetch;

window.fetch = function (...args) {
  return originalFetch.apply(this, args).then((response) => {
    // Clonar resposta para poder ler depois
    const clonedResponse = response.clone();

    // Se for uma chamada a dados_banca.php, atualizar rótulo
    if (args[0] && args[0].includes("dados_banca.php")) {
      clonedResponse
        .json()
        .then((data) => {
          if (data.lucro_total_historico !== undefined) {
            RotuloLucroDinamico.atualizarRotuloDinamico(
              data.lucro_total_historico
            );
          }
        })
        .catch(() => {
          // Silenciosamente ignorar erros de parsing JSON
        });
    }

    return response;
  });
};
