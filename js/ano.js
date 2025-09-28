/* ===================================================================
   JAVASCRIPT DO PLACAR ANUAL - BLOCO 3
   Sistema de atualização e gerenciamento do placar anual
   =================================================================== */

const PlacarAnualManager = {
  // CONTROLE DE ESTADO ANUAL
  atualizandoAtualmente: false,
  intervaloPlacar: null,
  ultimaAtualizacao: null,

  // INICIALIZAR SISTEMA DE PLACAR ANUAL
  inicializar() {
    try {
      console.log("📊 Inicializando Sistema de Placar Anual...");

      // Verificar se existe o elemento
      const placar = document.getElementById("pontuacao-3");
      if (!placar) {
        console.warn("⚠️ Elemento #pontuacao-3 não encontrado");
        return false;
      }

      // Primeira atualização
      this.atualizarPlacarAnual();

      // Configurar intervalo de atualização (a cada 45 segundos para dados anuais)
      this.intervaloPlacar = setInterval(() => {
        this.atualizarPlacarAnual();
      }, 45000);

      // Interceptar mudanças no sistema principal
      this.configurarInterceptadores();

      console.log("✅ Sistema de Placar Anual inicializado");
      return true;
    } catch (error) {
      console.error("❌ Erro ao inicializar placar anual:", error);
      return false;
    }
  },

  // ATUALIZAR PLACAR ANUAL - PERÍODO COMPLETO DO ANO
  async atualizarPlacarAnual() {
    if (this.atualizandoAtualmente) {
      console.log("⏳ Placar anual já sendo atualizado...");
      return;
    }

    this.atualizandoAtualmente = true;

    try {
      console.log("📊 Buscando dados do placar anual (período: ano)...");

      // Buscar dados anuais usando o endpoint específico
      const formData = new FormData();
      formData.append("periodo", "ano");

      const response = await fetch("carregar-mentores.php", {
        method: "POST",
        body: formData,
        headers: {
          "Cache-Control": "no-cache",
          "X-Requested-With": "XMLHttpRequest",
        },
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }

      const html = await response.text();

      // Extrair dados do placar usando a mesma lógica do sistema principal
      const placarData = this.extrairPlacarAnual(html);

      if (placarData) {
        this.aplicarPlacarAnual(placarData);
        this.ultimaAtualizacao = new Date();
        console.log(
          `✅ Placar anual atualizado: ${placarData.wins} × ${placarData.losses}`
        );
      } else {
        // Fallback: valores zerados
        this.aplicarPlacarAnual({ wins: 0, losses: 0 });
        console.log("⚠️ Nenhum dado anual encontrado, usando valores zero");
      }
    } catch (error) {
      console.error("❌ Erro ao atualizar placar anual:", error);
      this.mostrarErroPlacar();
      // Em caso de erro, zerar placar
      this.aplicarPlacarAnual({ wins: 0, losses: 0 });
    } finally {
      this.atualizandoAtualmente = false;
    }
  },

  // EXTRAIR PLACAR ANUAL DO HTML - DADOS AGREGADOS DO ANO
  extrairPlacarAnual(html) {
    try {
      console.log("🔍 Extraindo placar anual do HTML retornado...");

      // Criar elemento temporário para parsear HTML
      const temp = document.createElement("div");
      temp.innerHTML = html;

      // MÉTODO 1: Buscar elementos de total anual
      const totalGreenEl = temp.querySelector(
        "#total-green-ano, #total-green-dia"
      );
      const totalRedEl = temp.querySelector("#total-red-ano, #total-red-dia");

      if (totalGreenEl && totalRedEl) {
        const totalGreen = totalGreenEl.dataset.green || "0";
        const totalRed = totalRedEl.dataset.red || "0";

        const wins = parseInt(totalGreen, 10) || 0;
        const losses = parseInt(totalRed, 10) || 0;

        console.log(`✅ Dados anuais extraídos: ${wins} × ${losses}`);
        return { wins, losses };
      }

      // MÉTODO 2: Buscar placar principal direto
      const placarGreen = temp.querySelector(".placar-green");
      const placarRed = temp.querySelector(".placar-red");

      if (placarGreen && placarRed) {
        const wins = parseInt(placarGreen.textContent.trim(), 10) || 0;
        const losses = parseInt(placarRed.textContent.trim(), 10) || 0;

        console.log(`✅ Dados do placar principal: ${wins} × ${losses}`);
        return { wins, losses };
      }

      // MÉTODO 3: Agregar dados dos mentores para o ano todo
      const mentorCards = temp.querySelectorAll(".mentor-card");
      let wins = 0,
        losses = 0;

      console.log(
        `📊 Agregando dados anuais de ${mentorCards.length} mentores`
      );

      mentorCards.forEach((card) => {
        // Buscar valores Green e Red nos mentor-cards
        const greenBox = card.querySelector(".value-box-green p:nth-child(2)");
        const redBox = card.querySelector(".value-box-red p:nth-child(2)");

        if (greenBox) {
          const greenCount = parseInt(greenBox.textContent.trim(), 10) || 0;
          wins += greenCount;
        }

        if (redBox) {
          const redCount = parseInt(redBox.textContent.trim(), 10) || 0;
          losses += redCount;
        }
      });

      console.log(`✅ Dados anuais agregados: ${wins} × ${losses}`);
      return { wins, losses };
    } catch (error) {
      console.error("❌ Erro ao extrair placar anual:", error);
      return { wins: 0, losses: 0 };
    }
  },

  // APLICAR PLACAR ANUAL NO ELEMENTO
  aplicarPlacarAnual(placarData) {
    try {
      const placarElement = document.getElementById("pontuacao-3");
      if (!placarElement) return;

      // Garantir que o placar está sempre visível
      placarElement.style.setProperty("visibility", "visible", "important");
      placarElement.style.setProperty("display", "flex", "important");

      const greenSpan = placarElement.querySelector(".placar-green-3");
      const redSpan = placarElement.querySelector(".placar-red-3");
      const separadorEl = placarElement.querySelector(".separador-3");

      if (greenSpan && redSpan) {
        const wins = Number(placarData.wins) || 0;
        const losses = Number(placarData.losses) || 0;

        // Sempre mostrar valores, mesmo que sejam zero
        greenSpan.textContent = wins;
        redSpan.textContent = losses;

        // Separador sempre visível - prefer class toggling instead of inline styles
        if (separadorEl) {
          separadorEl.classList.remove("separador-transparente");
          // ensure base separator color class is present (handled by CSS)
        }

        // Marcar que o placar tem valores
        placarElement.classList.add("placar-has-values-3");

        console.log(`✅ Placar anual aplicado: ${wins} × ${losses}`);
      }
    } catch (error) {
      console.error("❌ Erro ao aplicar placar anual:", error);
    }
  },

  // ANIMAR MUDANÇA DE VALOR ANUAL
  animarMudancaValor(elemento, novoValor) {
    try {
      const valorAtual = parseInt(elemento.textContent) || 0;

      if (valorAtual !== novoValor) {
        // Atualização direta para valores anuais (sem animação excessiva)
        setTimeout(() => {
          elemento.textContent = novoValor;
        }, 10);
      }
    } catch (error) {
      console.error("❌ Erro na animação anual:", error);
      elemento.textContent = novoValor;
    }
  },

  // MOSTRAR ERRO NO PLACAR ANUAL
  mostrarErroPlacar() {
    try {
      const placarElement = document.getElementById("pontuacao-3");
      if (!placarElement) return;

      placarElement.classList.add("placar-erro-3");
      setTimeout(() => {
        placarElement.classList.remove("placar-erro-3");
      }, 2000);
    } catch (error) {
      console.error("❌ Erro ao mostrar erro anual:", error);
    }
  },

  // CONFIGURAR INTERCEPTADORES PARA DADOS ANUAIS
  configurarInterceptadores() {
    try {
      // Interceptar mudanças de período para 'ano'
      const radiosPeriodo = document.querySelectorAll('input[name="periodo"]');
      radiosPeriodo.forEach((radio) => {
        radio.addEventListener("change", (e) => {
          if (e.target.value === "ano") {
            console.log(
              "🔄 Período alterado para ANO, atualizando placar anual..."
            );
            setTimeout(() => this.atualizarPlacarAnual(), 50);
          }
        });
      });

      // Interceptar recarregamento de mentores
      if (
        typeof MentorManager !== "undefined" &&
        MentorManager.recarregarMentores
      ) {
        const originalRecarregar = MentorManager.recarregarMentores;

        MentorManager.recarregarMentores = async function (...args) {
          const resultado = await originalRecarregar.apply(this, args);

          // Atualizar placar anual após recarregar mentores
          try {
            if (typeof PlacarAnualManager !== "undefined") {
              console.log(
                "🔄 Mentores recarregados, atualizando placar anual..."
              );
              setTimeout(() => PlacarAnualManager.atualizarPlacarAnual(), 50);
            }
          } catch (e) {}

          return resultado;
        };
      }
    } catch (error) {
      console.error("❌ Erro ao configurar interceptadores anuais:", error);
    }
  },

  // SINCRONIZAR COM SISTEMA PRINCIPAL QUANDO PERÍODO = ANO
  sincronizarComSistemaPrincipal() {
    try {
      // Verificar se período atual é 'ano'
      const radioAno = document.querySelector(
        'input[name="periodo"][value="ano"]'
      );
      if (!radioAno || !radioAno.checked) {
        return false; // Não é período anual
      }

      // Buscar dados do placar principal
      const placarGreen = document.querySelector(".placar-green");
      const placarRed = document.querySelector(".placar-red");

      if (placarGreen && placarRed) {
        const wins = parseInt(placarGreen.textContent.trim(), 10) || 0;
        const losses = parseInt(placarRed.textContent.trim(), 10) || 0;

        console.log(
          `📊 Sincronizando placar anual com principal: ${wins} × ${losses}`
        );
        this.aplicarPlacarAnual({ wins, losses });

        return true;
      }

      return false;
    } catch (error) {
      console.error("❌ Erro ao sincronizar com sistema principal:", error);
      return false;
    }
  },

  // PARAR SISTEMA ANUAL
  parar() {
    try {
      if (this.intervaloPlacar) {
        clearInterval(this.intervaloPlacar);
        this.intervaloPlacar = null;
        console.log("🛑 Sistema de placar anual parado");
      }
    } catch (error) {
      console.error("❌ Erro ao parar sistema anual:", error);
    }
  },

  // FORÇAR ATUALIZAÇÃO ANUAL
  forcarAtualizacao() {
    this.atualizandoAtualmente = false;
    return this.atualizarPlacarAnual();
  },

  // STATUS DO SISTEMA ANUAL
  status() {
    return {
      ativo: !!this.intervaloPlacar,
      atualizando: this.atualizandoAtualmente,
      ultimaAtualizacao: this.ultimaAtualizacao,
      elementoExiste: !!document.getElementById("pontuacao-3"),
      intervaloAtivo: !!this.intervaloPlacar,
      modo: "ANUAL",
    };
  },
};

// ========================================
// COMANDOS GLOBAIS PARA O PLACAR ANUAL
// ========================================

// Função global para teste do placar anual
window.testarPlacarAnual = () => {
  console.log("🧪 Testando placar anual...");

  const placar = document.getElementById("pontuacao-3");
  if (!placar) {
    console.error("❌ Elemento #pontuacao-3 não encontrado!");
    return false;
  }

  console.log("✅ Elemento encontrado:", placar);

  // Teste visual rápido
  const green = placar.querySelector(".placar-green-3");
  const red = placar.querySelector(".placar-red-3");

  if (green && red) {
    green.textContent = Math.floor(Math.random() * 100) + 1;
    red.textContent = Math.floor(Math.random() * 100) + 1;
    console.log(
      `✅ Valores de teste anuais aplicados: ${green.textContent} × ${red.textContent}`
    );

    // Forçar atualização real após teste
    setTimeout(() => {
      if (typeof PlacarAnualManager !== "undefined") {
        PlacarAnualManager.atualizarPlacarAnual();
      }
    }, 3000);

    return true;
  }

  console.error("❌ Elementos internos anuais não encontrados");
  return false;
};

// Controles globais do placar anual
window.PlacarAnual = {
  iniciar: () => {
    console.log("🚀 Iniciando placar anual...");
    return PlacarAnualManager.inicializar();
  },
  parar: () => {
    console.log("🛑 Parando placar anual...");
    return PlacarAnualManager.parar();
  },
  atualizar: () => {
    console.log("🔄 Atualizando placar anual...");
    return PlacarAnualManager.forcarAtualizacao();
  },
  status: () => PlacarAnualManager.status(),
  info: () => {
    const status = PlacarAnualManager.status();
    console.log("📊 Status Placar Anual:", status);
    return status;
  },
  teste: () => testarPlacarAnual(),
  sincronizar: () => PlacarAnualManager.sincronizarComSistemaPrincipal(),
};

// ========================================
// INICIALIZAÇÃO AUTOMÁTICA DO PLACAR ANUAL
// ========================================

function inicializarPlacarAnual() {
  try {
    console.log("🚀 Inicializando Sistema de Placar Anual...");

    // Aguardar elemento estar disponível
    const verificarElemento = () => {
      const placar = document.getElementById("pontuacao-3");
      if (placar) {
        // FORÇAR VISIBILIDADE IMEDIATA
        placar.style.setProperty("visibility", "visible", "important");
        placar.style.setProperty("display", "flex", "important");

        // Inicializar com valores padrão
        const greenSpan = placar.querySelector(".placar-green-3");
        const redSpan = placar.querySelector(".placar-red-3");
        const separadorEl = placar.querySelector(".separador-3");

        if (greenSpan && redSpan) {
          greenSpan.textContent = "0";
          redSpan.textContent = "0";
          if (separadorEl) {
            separadorEl.style.setProperty(
              "color",
              "rgba(109, 107, 107, 0.95)",
              "important"
            );
            separadorEl.style.setProperty("opacity", "1", "important");
          }
        }

        PlacarAnualManager.inicializar();

        console.log("✅ Sistema de Placar Anual inicializado e VISÍVEL!");
      } else {
        console.log("⏳ Aguardando elemento #pontuacao-3...");
        setTimeout(verificarElemento, 500);
      }
    };

    verificarElemento();
  } catch (error) {
    console.error("❌ Erro na inicialização do placar anual:", error);
  }
}

// Aguardar DOM
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", () => {
    setTimeout(inicializarPlacarAnual, 1200);
  });
} else {
  setTimeout(inicializarPlacarAnual, 800);
}

// Export para uso externo
window.PlacarAnualManager = PlacarAnualManager;

console.log("📊 Sistema de Placar Anual carregado!");
console.log("🔧 Comandos disponíveis:");
console.log("  PlacarAnual.iniciar() - Iniciar sistema");
console.log("  PlacarAnual.atualizar() - Forçar atualização");
console.log("  PlacarAnual.status() - Ver status");
console.log("  PlacarAnual.teste() - Testar com valores aleatórios");
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
/* ===================================================================
   JAVASCRIPT - WIDGETS META ANUAL BLOCO 3
   Sistema de gerenciamento da meta anual com valor tachado e extra
   =================================================================== */

const MetaAnualManager = {
  // Controle de estado anual
  atualizandoAtualmente: false,
  periodoFixo: "ano",
  tipoMetaAtual: "turbo",

  // Atualizar meta anual - versão específica
  async atualizarMetaAnual(aguardarDados = false) {
    if (this.atualizandoAtualmente) return null;
    this.atualizandoAtualmente = true;

    try {
      if (aguardarDados) {
        await new Promise((resolve) => setTimeout(resolve, 100));
      }

      const response = await fetch("dados_banca.php?periodo=ano", {
        method: "GET",
        headers: {
          "Cache-Control": "no-cache",
          "X-Requested-With": "XMLHttpRequest",
          "X-Periodo-Filtro": "ano",
        },
      });

      if (!response.ok) throw new Error(`HTTP ${response.status}`);

      const data = await response.json();
      if (!data.success) throw new Error(data.message);

      if (data.tipo_meta) {
        this.tipoMetaAtual = data.tipo_meta;
      }

      const dadosProcessados = this.processarDadosAnuais(data);
      this.atualizarTodosElementosAnuais(dadosProcessados);

      return dadosProcessados;
    } catch (error) {
      console.error("Erro Meta Anual:", error);
      this.mostrarErroMetaAnual();
      return null;
    } finally {
      this.atualizandoAtualmente = false;
    }
  },

  // Processar dados especificamente para anual
  processarDadosAnuais(data) {
    try {
      const metaFinal = parseFloat(data.meta_anual) || 0;
      const rotuloFinal = "Meta do Ano";
      const lucroAnual = parseFloat(data.lucro) || 0;

      return {
        ...data,
        meta_display: metaFinal,
        meta_display_formatada:
          "R$ " +
          metaFinal.toLocaleString("pt-BR", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
          }),
        rotulo_periodo: rotuloFinal,
        periodo_ativo: "ano",
        lucro_periodo: lucroAnual,
      };
    } catch (error) {
      console.error("Erro ao processar dados anuais:", error);
      return data;
    }
  },

  // Calcular meta final anual com valor tachado e extra
  calcularMetaFinalAnualComExtra(saldoAno, metaCalculada, bancaTotal, data) {
    try {
      let metaFinal,
        rotulo,
        statusClass,
        valorExtra = 0,
        mostrarTachado = false;

      console.log(`🔍 DEBUG CALCULAR META ANUAL COM EXTRA:`);
      console.log(`   Saldo Ano: R$ ${saldoAno.toFixed(2)}`);
      console.log(`   Meta Ano: R$ ${metaCalculada.toFixed(2)}`);
      console.log(`   Banca: R$ ${bancaTotal.toFixed(2)}`);

      if (bancaTotal <= 0) {
        metaFinal = bancaTotal;
        rotulo = "Deposite p/ Começar";
        statusClass = "sem-banca";
        console.log(`📊 RESULTADO ANUAL: Sem banca`);
      }
      // META BATIDA OU SUPERADA - COM VALOR EXTRA
      else if (saldoAno > 0 && metaCalculada > 0 && saldoAno >= metaCalculada) {
        valorExtra = saldoAno - metaCalculada;
        mostrarTachado = true;
        metaFinal = metaCalculada; // Mostra o valor da meta original

        if (valorExtra > 0) {
          rotulo = `Meta do Ano Superada! <i class='fa-solid fa-trophy'></i>`;
          statusClass = "meta-superada";
          console.log(
            `🏆 META ANUAL SUPERADA: Extra de R$ ${valorExtra.toFixed(2)}`
          );
        } else {
          rotulo = `Meta do Ano Batida! <i class='fa-solid fa-trophy'></i>`;
          statusClass = "meta-batida";
          console.log(`🎯 META ANUAL EXATA`);
        }
      }
      // CASO ESPECIAL: Meta é zero (já foi batida)
      else if (metaCalculada === 0 && saldoAno > 0) {
        metaFinal = 0;
        valorExtra = saldoAno;
        mostrarTachado = false;
        rotulo = `Meta do Ano Batida! <i class='fa-solid fa-trophy'></i>`;
        statusClass = "meta-batida";
        console.log(`🎯 META ANUAL ZERO (já batida)`);
      } else if (saldoAno < 0) {
        metaFinal = metaCalculada - saldoAno;
        rotulo = `Restando p/ Meta do Ano`;
        statusClass = "negativo";
        console.log(`📊 RESULTADO ANUAL: Negativo`);
      } else if (saldoAno === 0) {
        metaFinal = metaCalculada;
        rotulo = "Meta do Ano";
        statusClass = "neutro";
        console.log(`📊 RESULTADO ANUAL: Neutro`);
      } else {
        // Lucro positivo mas menor que a meta
        metaFinal = metaCalculada - saldoAno;
        rotulo = `Restando p/ Meta do Ano`;
        statusClass = "lucro";
        console.log(`📊 RESULTADO ANUAL: Lucro insuficiente`);
      }

      const resultado = {
        metaFinal,
        metaOriginal: metaCalculada,
        valorExtra,
        mostrarTachado,
        metaFinalFormatada: metaFinal.toLocaleString("pt-BR", {
          style: "currency",
          currency: "BRL",
        }),
        metaOriginalFormatada: metaCalculada.toLocaleString("pt-BR", {
          style: "currency",
          currency: "BRL",
        }),
        valorExtraFormatado:
          valorExtra > 0
            ? valorExtra.toLocaleString("pt-BR", {
                style: "currency",
                currency: "BRL",
              })
            : null,
        rotulo,
        statusClass,
      };

      console.log(`🏁 RESULTADO FINAL ANUAL COM EXTRA:`);
      console.log(`   Status: ${statusClass}`);
      console.log(`   Valor Extra: R$ ${valorExtra.toFixed(2)}`);
      console.log(`   Mostrar Tachado: ${mostrarTachado}`);

      return resultado;
    } catch (error) {
      console.error("Erro ao calcular meta final anual com extra:", error);
      return {
        metaFinal: 0,
        metaOriginal: 0,
        valorExtra: 0,
        mostrarTachado: false,
        metaFinalFormatada: "R$ 0,00",
        metaOriginalFormatada: "R$ 0,00",
        valorExtraFormatado: null,
        rotulo: "Erro no cálculo",
        statusClass: "erro",
      };
    }
  },

  // Atualizar todos os elementos anuais COM EXTRA
  atualizarTodosElementosAnuais(data) {
    try {
      const saldoAno =
        parseFloat(data.lucro_periodo) || parseFloat(data.lucro) || 0;
      const metaCalculada = parseFloat(data.meta_display) || 0;
      const bancaTotal = parseFloat(data.banca) || 0;

      const dadosComplementados = {
        ...data,
        meta_original: data.meta_original || metaCalculada,
      };

      // USAR FUNÇÃO COM VALOR EXTRA
      const resultado = this.calcularMetaFinalAnualComExtra(
        saldoAno,
        metaCalculada,
        bancaTotal,
        dadosComplementados
      );

      // Atualizar elementos do bloco 3
      this.garantirIconeMoeda();
      this.atualizarMetaElementoAnualComExtra(resultado);
      this.atualizarRotuloAnual(resultado.rotulo);
      this.atualizarBarraProgressoAnual(resultado, data);

      console.log(`Meta ANUAL atualizada COM EXTRA`);
      console.log(`Lucro do ANO: R$ ${saldoAno.toFixed(2)}`);
      console.log(`Meta ANUAL: R$ ${metaCalculada.toFixed(2)}`);

      if (resultado.valorExtra > 0) {
        console.log(
          `🏆 Valor Extra ANUAL: R$ ${resultado.valorExtra.toFixed(2)}`
        );
      }
    } catch (error) {
      console.error("Erro ao atualizar elementos anuais:", error);
    }
  },

  // Atualizar meta elemento anual com valor tachado e extra
  atualizarMetaElementoAnualComExtra(resultado) {
    try {
      const metaValor = document.getElementById("meta-valor-3");
      if (!metaValor) {
        console.warn("Elemento meta-valor-3 não encontrado");
        return;
      }

      // Limpar classes antigas
      metaValor.className = metaValor.className.replace(
        /\bvalor-meta-3\s+\w+/g,
        ""
      );

      let htmlConteudo = "";

      if (resultado.mostrarTachado && resultado.valorExtra >= 0) {
        // META BATIDA/SUPERADA - MOSTRAR VALOR TACHADO + EXTRA
        htmlConteudo = `
          <i class="fa-solid fa-coins"></i>
          <div class="meta-valor-container-3">
            <span class="valor-tachado-3">${
              resultado.metaOriginalFormatada
            }</span>
            ${
              resultado.valorExtra > 0
                ? `<span class="valor-extra-3">+ ${resultado.valorExtraFormatado}</span>`
                : ""
            }
          </div>
        `;

        metaValor.classList.add("valor-meta-3", "meta-com-extra-3");
        console.log(
          `✅ Valor tachado ANUAL aplicado: ${resultado.metaOriginalFormatada}`
        );

        if (resultado.valorExtra > 0) {
          console.log(
            `✅ Valor extra ANUAL aplicado: + ${resultado.valorExtraFormatado}`
          );
        }
      } else {
        // EXIBIÇÃO NORMAL
        htmlConteudo = `
          <i class="fa-solid fa-coins"></i>
          <div class="meta-valor-container-3">
            <span class="valor-texto-3" id="valor-texto-meta-3">${resultado.metaFinalFormatada}</span>
          </div>
        `;

        metaValor.classList.add("valor-meta-3", resultado.statusClass);
      }

      metaValor.innerHTML = htmlConteudo;
    } catch (error) {
      console.error("Erro ao atualizar meta elemento anual com extra:", error);
    }
  },

  // Garantir ícone da moeda anual
  garantirIconeMoeda() {
    try {
      const metaValor = document.getElementById("meta-valor-3");
      if (!metaValor) return;

      const iconeExistente = metaValor.querySelector(".fa-coins");

      if (!iconeExistente) {
        const valorTexto = metaValor.querySelector(".valor-texto-3");
        if (valorTexto) {
          const textoAtual = valorTexto.textContent;
          metaValor.innerHTML = `
            <i class="fa-solid fa-coins"></i>
            <div class="meta-valor-container-3">
              <span class="valor-texto-3">${textoAtual}</span>
            </div>
          `;
          console.log("Ícone da moeda adicionado ao HTML 3");
        }
      }
    } catch (error) {
      console.error("Erro ao garantir ícone da moeda anual:", error);
    }
  },

  // Atualizar rótulo anual
  atualizarRotuloAnual(rotulo) {
    try {
      const rotuloElement = document.getElementById("rotulo-meta-3");
      if (rotuloElement) {
        rotuloElement.innerHTML = rotulo;
        try {
          const texto = (rotuloElement.textContent || "").toLowerCase();
          if (texto.includes("restando")) {
            rotuloElement.classList.add("rotulo-restando");
          } else {
            rotuloElement.classList.remove("rotulo-restando");
          }
        } catch (e) {
          // silencioso
        }
      } else {
        console.warn("Elemento rotulo-meta-3 não encontrado");
      }
    } catch (error) {
      console.error("Erro ao atualizar rótulo anual:", error);
    }
  },

  // Atualizar ícones dinâmicos do saldo anual
  atualizarIconesSaldoDinamicos(saldoAno) {
    try {
      const saldoInfo = document.getElementById("saldo-info-3");
      if (!saldoInfo) return;

      const saldoFormatado = saldoAno.toLocaleString("pt-BR", {
        style: "currency",
        currency: "BRL",
      });

      let textoSaldo = "Saldo";
      let iconeClass = "fa-solid fa-wallet";
      let classeEstado = "saldo-zero-3";

      // Determinar texto, ícone e classe baseado no valor
      if (saldoAno > 0) {
        textoSaldo = "Lucro Ano";
        iconeClass = "fa-solid fa-chart-line";
        classeEstado = "saldo-positivo-3";
      } else if (saldoAno < 0) {
        textoSaldo = "Negativo";
        iconeClass = "fa-solid fa-arrow-trend-down";
        classeEstado = "saldo-negativo-3";
      } else {
        textoSaldo = "Saldo Ano";
        iconeClass = "fa-solid fa-wallet";
        classeEstado = "saldo-zero-3";
      }

      // Atualizar HTML do saldo anual
      saldoInfo.innerHTML = `
        <i class="${iconeClass}"></i>
        <span class="saldo-info-rotulo-3">${textoSaldo}:</span>
        <span class="saldo-info-valor-3">${saldoFormatado}</span>
      `;

      // Aplicar classe de estado
      saldoInfo.className = classeEstado;

      console.log(`Ícone HTML 3 atualizado: ${textoSaldo} - ${iconeClass}`);
    } catch (error) {
      console.error("Erro ao atualizar ícones dinâmicos HTML 3:", error);
    }
  },

  // Limpar estado da barra anual
  limparEstadoBarraAnual() {
    try {
      const barraProgresso = document.getElementById("barra-progresso-3");
      const porcentagemBarra = document.getElementById("porcentagem-barra-3");

      if (barraProgresso) {
        // Remover todas as classes possíveis
        barraProgresso.classList.remove(
          "barra-meta-batida-3",
          "barra-meta-superada-3",
          "barra-negativo-3",
          "barra-lucro-3",
          "barra-neutro-3",
          "barra-sem-banca-3",
          "barra-erro-3"
        );

        // Limpar estilos inline
        barraProgresso.style.width = "0%";
        barraProgresso.style.backgroundColor = "";
        barraProgresso.style.background = "";
        barraProgresso.style.filter = "";
        barraProgresso.style.animation = "";

        // Garantir classe base
        if (!barraProgresso.classList.contains("widget-barra-progresso-3")) {
          barraProgresso.classList.add("widget-barra-progresso-3");
        }
      }

      if (porcentagemBarra) {
        porcentagemBarra.innerHTML =
          '<span class="porcentagem-fundo-3">0%</span>';
        porcentagemBarra.classList.remove("pequeno", "oculta");
        porcentagemBarra.classList.add("oculta");
      }

      console.log("Barra anual limpa completamente");
    } catch (error) {
      console.error("Erro ao limpar estado da barra anual:", error);
    }
  },

  // Atualizar barra de progresso anual
  atualizarBarraProgressoAnual(resultado, data) {
    try {
      const barraProgresso = document.getElementById("barra-progresso-3");
      const saldoInfo = document.getElementById("saldo-info-3");
      const porcentagemBarra = document.getElementById("porcentagem-barra-3");

      if (!barraProgresso) {
        console.warn("Elemento barra-progresso-3 não encontrado");
        return;
      }

      const saldoAno =
        parseFloat(data.lucro_periodo) || parseFloat(data.lucro) || 0;
      const metaCalculada = parseFloat(data.meta_display) || 0;
      const bancaTotal = parseFloat(data.banca) || 0;

      // Calcular progresso
      let progresso = 0;
      if (bancaTotal > 0 && metaCalculada > 0) {
        if (
          resultado.statusClass === "meta-batida" ||
          resultado.statusClass === "meta-superada"
        ) {
          progresso = 100;
        } else if (saldoAno < 0) {
          progresso = -Math.min(Math.abs(saldoAno / metaCalculada) * 100, 100);
        } else {
          progresso = Math.max(
            0,
            Math.min(100, (saldoAno / metaCalculada) * 100)
          );
        }
      }

      const larguraBarra = Math.abs(progresso);

      // Limpeza completa das classes antigas
      let classeCor = "";

      // Remover TODAS as classes de cor possíveis
      barraProgresso.classList.remove(
        "barra-meta-batida-3",
        "barra-meta-superada-3",
        "barra-negativo-3",
        "barra-lucro-3",
        "barra-neutro-3",
        "barra-sem-banca-3",
        "barra-erro-3"
      );

      // Garantir classe base
      if (!barraProgresso.classList.contains("widget-barra-progresso-3")) {
        barraProgresso.classList.add("widget-barra-progresso-3");
      }

      // Aplicar classe correta com sufixo -3
      if (
        resultado.statusClass === "meta-batida" ||
        resultado.statusClass === "meta-superada"
      ) {
        classeCor = "barra-meta-batida-3";
        console.log(
          `✅ BARRA ANUAL META BATIDA/SUPERADA - Saldo: R$ ${saldoAno.toFixed(
            2
          )}, Meta: R$ ${metaCalculada.toFixed(2)}`
        );
      } else {
        classeCor = `barra-${resultado.statusClass}-3`;
        console.log(
          `✅ BARRA ANUAL NORMAL - Status: ${
            resultado.statusClass
          }, Saldo: R$ ${saldoAno.toFixed(2)}`
        );
      }

      // Aplicar classe e estilos
      barraProgresso.classList.add(classeCor);

      // Forçar reset de estilos inline antigos
      barraProgresso.style.width = `${larguraBarra}%`;
      barraProgresso.style.backgroundColor = "";
      barraProgresso.style.background = "";
      barraProgresso.style.filter = "";
      barraProgresso.style.animation = "";

      console.log(
        `✅ BARRA ANUAL - Classe aplicada: ${classeCor}, Largura: ${larguraBarra}%`
      );

      // Porcentagem
      if (porcentagemBarra) {
        const porcentagemTexto = Math.round(progresso) + "%";
        porcentagemBarra.innerHTML = `
          <span class="porcentagem-fundo-3 ${classeCor}">${porcentagemTexto}</span>
        `;

        if (larguraBarra <= 10) {
          porcentagemBarra.classList.add("pequeno");
        } else {
          porcentagemBarra.classList.remove("pequeno");
        }

        if (larguraBarra <= 0) {
          porcentagemBarra.classList.add("oculta");
        } else {
          porcentagemBarra.classList.remove("oculta");
        }
      }

      // Atualizar ícones dinâmicos do saldo
      this.atualizarIconesSaldoDinamicos(saldoAno);
    } catch (error) {
      console.error("Erro ao atualizar barra progresso anual:", error);
    }
  },

  // Mostrar erro anual
  mostrarErroMetaAnual() {
    try {
      const metaElement = document.getElementById("meta-valor-3");
      if (metaElement) {
        metaElement.innerHTML =
          '<i class="fa-solid fa-coins"></i><div class="meta-valor-container-3"><span class="valor-texto-3 loading-text-3">R$ 0,00</span></div>';
      }
    } catch (error) {
      console.error("Erro ao mostrar erro meta anual:", error);
    }
  },

  // Inicializar sistema anual
  inicializar() {
    try {
      const metaElement = document.getElementById("meta-valor-3");
      if (metaElement) {
        metaElement.innerHTML =
          '<i class="fa-solid fa-coins"></i><div class="meta-valor-container-3"><span class="valor-texto-3 loading-text-3">Calculando...</span></div>';
      }

      console.log(`Sistema Meta ANUAL COM VALOR TACHADO E EXTRA inicializado`);

      // Garantir ícone da moeda após delay
      setTimeout(() => {
        this.garantirIconeMoeda();
      }, 1500);

      // Inicializar com delay
      setTimeout(() => {
        this.atualizarMetaAnual();
      }, 1000);
    } catch (error) {
      console.error("Erro na inicialização anual:", error);
    }
  },

  // Sincronizar com mudanças
  sincronizarComSistema() {
    try {
      this.atualizarMetaAnual(true);
    } catch (error) {
      console.error("Erro ao sincronizar anual:", error);
    }
  },
};

// ========================================
// FUNÇÕES GLOBAIS E ATALHOS ANUAIS
// ========================================

window.atualizarMetaAnual = () => {
  if (typeof MetaAnualManager !== "undefined") {
    return MetaAnualManager.atualizarMetaAnual();
  }
  return null;
};

window.$3 = {
  force: () => {
    if (typeof MetaAnualManager !== "undefined") {
      MetaAnualManager.atualizandoAtualmente = false;
      return MetaAnualManager.atualizarMetaAnual();
    }
    return null;
  },

  sync: () => {
    if (typeof MetaAnualManager !== "undefined") {
      return MetaAnualManager.sincronizarComSistema();
    }
    return null;
  },

  // Função para testar valor tachado e extra anual
  testExtra: () => {
    console.log("Testando valor tachado e extra ANUAL...");

    if (typeof MetaAnualManager === "undefined") {
      return "MetaAnualManager não encontrado";
    }

    // Simular diferentes cenários de teste
    const testData = {
      meta_display: 12000,
      meta_display_formatada: "R$ 12.000,00",
      banca: 50000,
      rotulo_periodo: "Meta do Ano",
    };

    // Teste 1: Meta anual exatamente batida
    setTimeout(() => {
      console.log("Teste 1: Meta ANUAL exatamente batida (R$ 12000)");
      const resultado = MetaAnualManager.calcularMetaFinalAnualComExtra(
        12000,
        12000,
        50000,
        testData
      );
      MetaAnualManager.atualizarMetaElementoAnualComExtra(resultado);
    }, 1000);

    // Teste 2: Meta superada
    setTimeout(() => {
      console.log("Teste 2: Meta ANUAL superada (R$ 15000 - extra R$ 3000)");
      const resultado = MetaAnualManager.calcularMetaFinalAnualComExtra(
        15000,
        12000,
        50000,
        testData
      );
      MetaAnualManager.atualizarMetaElementoAnualComExtra(resultado);
    }, 2500);

    // Teste 3: Meta não batida
    setTimeout(() => {
      console.log("Teste 3: Meta ANUAL não batida (R$ 8000)");
      const resultado = MetaAnualManager.calcularMetaFinalAnualComExtra(
        8000,
        12000,
        50000,
        testData
      );
      MetaAnualManager.atualizarMetaElementoAnualComExtra(resultado);
    }, 4000);

    return "Teste ANUAL completo em 4 segundos - valor tachado e extra";
  },

  info: () => {
    try {
      const metaElement = document.getElementById("meta-valor-3");
      const saldoElement = document.getElementById("saldo-info-3");

      const info = {
        meta: !!metaElement,
        saldo: !!saldoElement,
        iconeMoeda: !!metaElement?.querySelector(".fa-coins"),
        iconeAtual: saldoElement?.querySelector("i")?.className || "N/A",
        metaContent: metaElement ? metaElement.textContent : "N/A",
        temTachado: !!metaElement?.querySelector(".valor-tachado-3"),
        temExtra: !!metaElement?.querySelector(".valor-extra-3"),
        verificacao: "Sistema Meta Anual COM valor tachado e extra",
      };

      console.log("Info Sistema Meta Anual COM EXTRA:", info);
      return "Info Meta Anual COM VALOR TACHADO E EXTRA verificada";
    } catch (error) {
      console.error("Erro ao obter info anual:", error);
      return "Erro ao obter informações anuais";
    }
  },
};

// ========================================
// INICIALIZAÇÃO SISTEMA ANUAL
// ========================================

function inicializarSistemaMetaAnual() {
  try {
    console.log(
      "Inicializando Sistema Meta ANUAL COM VALOR TACHADO E EXTRA..."
    );

    if (typeof MetaAnualManager !== "undefined") {
      MetaAnualManager.inicializar();
      console.log("MetaAnualManager COM EXTRA inicializado");
    }

    console.log("Sistema Meta ANUAL COM VALOR TACHADO E EXTRA inicializado!");
    console.log("Características:");
    console.log("   ✅ Sempre mostra META DO ANO");
    console.log("   ✅ Ícone da moeda garantido");
    console.log("   ✅ Ícones dinâmicos do saldo anual");
    console.log("   ✅ Barra de progresso anual");
    console.log("   ✅ Classes Font Awesome corretas");
    console.log("   ✅ VALOR TACHADO quando meta batida");
    console.log("   ✅ VALOR EXTRA em dourado quando meta superada");
  } catch (error) {
    console.error("Erro na inicialização sistema anual:", error);
  }
}

// ========================================
// INTERCEPTAÇÃO RÁPIDA ANUAL
// ========================================

(function () {
  let ultimaAtualizacao = 0;
  const MIN_INTERVAL_MS = 300;

  function atualizarRapidoAnual() {
    const agora = Date.now();
    if (agora - ultimaAtualizacao < MIN_INTERVAL_MS) return;

    ultimaAtualizacao = agora;

    if (typeof MetaAnualManager !== "undefined") {
      MetaAnualManager.atualizandoAtualmente = false;
      MetaAnualManager.atualizarMetaAnual(false);
    }
  }

  // Eventos de usuário
  document.addEventListener(
    "submit",
    (e) => {
      setTimeout(atualizarRapidoAnual, 50);
    },
    true
  );

  document.addEventListener(
    "click",
    (e) => {
      if (
        e.target.closest('button, .btn, input[type="submit"], a[data-action]')
      ) {
        setTimeout(atualizarRapidoAnual, 50);
      }
    },
    true
  );

  // Hook no fetch
  try {
    const _fetch = window.fetch;
    window.fetch = function (...args) {
      const url = args[0] && args[0].toString ? args[0].toString() : "";
      return _fetch.apply(this, args).then((resp) => {
        try {
          if (
            /dados_banca|carregar-mentores|controle|valor_mentores/i.test(url)
          ) {
            setTimeout(atualizarRapidoAnual, 50);
          }
        } catch (e) {}
        return resp;
      });
    };
  } catch (e) {
    console.warn("Não foi possível hookar fetch para atualizações ANUAL", e);
  }

  // Interval fallback
  setInterval(atualizarRapidoAnual, 8000);

  // Primeira atualização
  setTimeout(atualizarRapidoAnual, 50);

  window.atualizarRapidoAnual = atualizarRapidoAnual;

  console.log("Sistema rápido ANUAL ativo");
})();

// Aguardar DOM
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", () => {
    setTimeout(inicializarSistemaMetaAnual, 1500);
  });
} else {
  setTimeout(inicializarSistemaMetaAnual, 1000);
}

console.log("Sistema Meta ANUAL COM VALOR TACHADO E EXTRA carregado!");
console.log("Comandos ANUAIS:");
console.log("  $3.force() - Forçar atualização");
console.log("  $3.testExtra() - Testar valor tachado e extra");
console.log("  $3.sync() - Sincronizar");
console.log("  $3.info() - Ver status completo");

// Export para uso externo
window.MetaAnualManager = MetaAnualManager;
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
/* ===================================================================
   JAVASCRIPT - LISTA DE MESES BLOCO 3 (ANUAL) - ATUALIZAÇÃO AJAX CORRIGIDA
   Sistema de atualização em tempo real dos valores via AJAX
   =================================================================== */

const ListaMesesManagerAnual = {
  // Controle de estado
  atualizandoAtualmente: false,
  intervaloAtualizacao: null,
  ultimaAtualizacao: null,
  hashUltimosDados: "",
  metaAtual: 0,
  metaMensal: 0,
  periodoAtual: "ano",
  forcarProximaAtualizacao: false,

  // Configurações
  INTERVALO_MS: 3000, // Atualiza a cada 3 segundos
  TIMEOUT_MS: 8000,

  // Inicializar sistema
  inicializar() {
    console.log(
      "Inicializando sistema de atualização AJAX da lista de meses..."
    );

    // Detectar meta inicial
    this.detectarMetaEPeriodo();

    // Configurar sincronização com sistema principal
    this.sincronizarComSistemaPrincipal();

    // Primeira atualização imediata
    this.atualizarListaMeses();

    // Configurar intervalo de atualização mais frequente
    this.intervaloAtualizacao = setInterval(() => {
      this.atualizarListaMeses();
    }, this.INTERVALO_MS);

    // Configurar interceptadores de eventos mais abrangentes
    this.configurarInterceptadores();

    console.log("Sistema de lista de meses AJAX ativo!");
  },

  // Detectar meta mensal e período atual
  detectarMetaEPeriodo() {
    try {
      const dadosInfo = document.getElementById("dados-ano-info");
      if (dadosInfo) {
        this.periodoAtual = dadosInfo.dataset.periodoAtual || "ano";
        this.metaMensal = parseFloat(dadosInfo.dataset.metaMensal) || 0;
        this.metaAtual = parseFloat(dadosInfo.dataset.metaAnual) || 0;
      }

      // Fallback: tentar detectar do radio button
      const radioSelecionado = document.querySelector(
        'input[name="periodo"]:checked'
      );
      if (radioSelecionado) {
        this.periodoAtual = radioSelecionado.value;
      }

      console.log(
        `Meta mensal detectada: R$ ${this.metaMensal.toFixed(
          2
        )} | Meta anual: R$ ${this.metaAtual.toFixed(2)}`
      );
    } catch (error) {
      console.error("Erro ao detectar meta mensal:", error);
      this.metaMensal = 0;
      this.metaAtual = 0;
    }
  },

  // Atualização principal via AJAX usando dados_banca.php
  async atualizarListaMeses() {
    if (this.atualizandoAtualmente && !this.forcarProximaAtualizacao) {
      return;
    }

    this.atualizandoAtualmente = true;
    this.forcarProximaAtualizacao = false;

    try {
      console.log("Buscando dados atualizados via dados_banca.php...");

      // Buscar dados do sistema principal (dados_banca.php)
      const response = await fetch("dados_banca.php?periodo=ano", {
        method: "GET",
        headers: {
          "Cache-Control": "no-cache",
          "X-Requested-With": "XMLHttpRequest",
          "X-Periodo-Filtro": "ano",
          "X-Timestamp": Date.now().toString(),
        },
        signal: AbortSignal.timeout(this.TIMEOUT_MS),
      });

      if (!response.ok) throw new Error(`HTTP ${response.status}`);

      const dadosBanca = await response.json();

      if (!dadosBanca || !dadosBanca.success) {
        throw new Error("Dados inválidos ou erro no servidor");
      }

      // Buscar dados mensais específicos
      const responseMeses = await fetch("obter_dados_ano.php", {
        method: "GET",
        headers: {
          "Cache-Control": "no-cache",
          "X-Requested-With": "XMLHttpRequest",
          "X-Timestamp": Date.now().toString(),
        },
        signal: AbortSignal.timeout(this.TIMEOUT_MS),
      });

      let dadosMeses = {};
      if (responseMeses.ok) {
        const dataMeses = await responseMeses.json();
        dadosMeses = dataMeses.dados_por_mes || {};
      }

      // Combinar dados para renderização
      const dadosCombinados = {
        dados_por_mes: dadosMeses,
        configuracao_meta: {
          meta_mensal_para_trofeu: dadosBanca.meta_mensal || 0,
          meta_anual: dadosBanca.meta_anual || 0,
          tipo_meta: dadosBanca.tipo_meta || "turbo",
        },
        ano: new Date().getFullYear(),
        sistema_principal: dadosBanca,
      };

      // Verificar se houve mudança real nos dados
      const hashAtual = this.gerarHashDados(dadosCombinados);
      const mudouDados = hashAtual !== this.hashUltimosDados;

      if (mudouDados || this.forcarProximaAtualizacao) {
        console.log("Dados mudaram, atualizando lista...");
        this.hashUltimosDados = hashAtual;

        // Atualizar meta mensal baseada nos dados do sistema principal
        this.metaMensal = dadosBanca.meta_mensal || 0;
        this.metaAtual = dadosBanca.meta_anual || 0;

        // Renderizar todos os meses do ano com dados atualizados
        this.renderizarAnoCompleto(dadosCombinados);

        this.ultimaAtualizacao = new Date();
        console.log(
          "Lista de meses atualizada via dados_banca.php:",
          this.ultimaAtualizacao.toLocaleTimeString()
        );
        console.log(`Meta mensal ativa: R$ ${this.metaMensal.toFixed(2)}`);
      } else {
        console.log("Dados inalterados, mantendo lista atual");
      }
    } catch (error) {
      console.error("Erro na atualização AJAX dos meses:", error);

      // Em caso de erro, tentar buscar dados do DOM atual
      this.tentarAtualizacaoFallback();
    } finally {
      this.atualizandoAtualmente = false;
    }
  },

  // Fallback: tentar atualizar com dados do DOM
  tentarAtualizacaoFallback() {
    try {
      console.log("Tentando atualização fallback...");

      // Buscar dados básicos para atualizar pelo menos os totais visíveis
      const placarGreen = document.querySelector(".placar-green");
      const placarRed = document.querySelector(".placar-red");

      if (placarGreen && placarRed) {
        // Forçar uma nova busca em 2 segundos
        setTimeout(() => {
          this.forcarProximaAtualizacao = true;
          this.atualizarListaMeses();
        }, 2000);
      }
    } catch (error) {
      console.error("Erro no fallback:", error);
    }
  },

  // Renderizar ano completo com dados AJAX
  renderizarAnoCompleto(responseData) {
    const container = document.querySelector(".lista-meses");
    if (!container) {
      console.warn("Container .lista-meses não encontrado");
      return;
    }

    // Preservar posição do scroll
    const scrollTop = container.scrollTop;

    // Mapear estado atual para preservar troféus
    const estadoAtualMap = {};
    container.querySelectorAll(".gd-linha-mes").forEach((el) => {
      const dataKey = el.getAttribute("data-date");
      if (dataKey) {
        estadoAtualMap[dataKey] = {
          metaBatida: el.getAttribute("data-meta-mensal-batida") === "true",
          valorAtual: el.querySelector(".valor")?.textContent || "",
          placarAtual: {
            green: el.querySelector(".verde-bold")?.textContent || "0",
            red: el.querySelector(".vermelho-bold")?.textContent || "0",
          },
        };
      }
    });

    // Dados do response
    const dadosPorMes = responseData.dados_por_mes || {};
    const configMeta = responseData.configuracao_meta || {};
    const ano = responseData.ano || new Date().getFullYear();

    // Atualizar meta mensal baseada na configuração
    this.metaMensal = configMeta.meta_mensal_para_trofeu || this.metaMensal;

    // Data de hoje
    const hoje = this.obterDataHoje();
    const mesAtual = parseInt(hoje.split("-")[1], 10);

    // Nomes dos meses
    const nomesMeses = [
      "Janeiro",
      "Fevereiro",
      "Março",
      "Abril",
      "Maio",
      "Junho",
      "Julho",
      "Agosto",
      "Setembro",
      "Outubro",
      "Novembro",
      "Dezembro",
    ];

    // Criar fragment para performance
    const fragment = document.createDocumentFragment();

    // Gerar todos os meses do ano
    for (let mes = 1; mes <= 12; mes++) {
      const mesStr = mes.toString().padStart(2, "0");
      const chaveMetasMes = `${ano}-${mesStr}`;
      const nomeMes = nomesMeses[mes - 1];

      // Dados do mês (ou padrão se não existir)
      const dadosMes = dadosPorMes[chaveMetasMes] || {
        total_valor_green: 0,
        total_valor_red: 0,
        total_green: 0,
        total_red: 0,
        saldo: 0,
      };

      // Calcular saldo do mês
      const saldo_mes =
        dadosMes.saldo ||
        parseFloat(dadosMes.total_valor_green) -
          parseFloat(dadosMes.total_valor_red);

      const saldo_formatado = saldo_mes.toLocaleString("pt-BR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      });

      // Verificar mudança nos valores
      const estadoAnterior = estadoAtualMap[chaveMetasMes];
      const valorFormatadoNovo = `R$ ${saldo_formatado}`;
      const placarGreenNovo = parseInt(dadosMes.total_green).toString();
      const placarRedNovo = parseInt(dadosMes.total_red).toString();

      const houveMudanca =
        !estadoAnterior ||
        estadoAnterior.valorAtual !== valorFormatadoNovo ||
        estadoAnterior.placarAtual.green !== placarGreenNovo ||
        estadoAnterior.placarAtual.red !== placarRedNovo;

      if (houveMudanca) {
        console.log(
          `Valores mudaram para ${nomeMes}: ${valorFormatadoNovo} | Placar: ${placarGreenNovo} × ${placarRedNovo}`
        );
      }

      // VERIFICAÇÃO RIGOROSA DE META MENSAL
      let metaMensalBatida = false;

      if (this.metaMensal > 0) {
        metaMensalBatida = saldo_mes >= this.metaMensal;
      } else {
        // Sem meta configurada: critério restritivo (R$ 500 por mês)
        metaMensalBatida = saldo_mes >= 500;
      }

      // Classes e estilos
      const cor_valor =
        saldo_mes === 0
          ? "texto-cinza"
          : saldo_mes > 0
          ? "verde-bold"
          : "vermelho-bold";

      const classe_texto = saldo_mes === 0 ? "texto-cinza" : "";

      const placar_cinza =
        parseInt(dadosMes.total_green) === 0 &&
        parseInt(dadosMes.total_red) === 0
          ? "texto-cinza"
          : "";

      // Classes do mês
      const classes = ["gd-linha-mes"];

      // Adicionar classe de valor
      if (saldo_mes > 0) {
        classes.push("valor-positivo");
      } else if (saldo_mes < 0) {
        classes.push("valor-negativo");
      } else {
        classes.push("valor-zero");
      }

      // Verificar se é o mês atual
      if (mes === mesAtual) {
        classes.push("gd-mes-hoje", "mes-atual");
      } else {
        classes.push("mes-normal");
      }

      // Ícone baseado na meta mensal
      const iconeClasse = metaMensalBatida
        ? "fa-trophy trofeu-icone"
        : "fa-check";
      const iconeClassesFull = `fa-solid ${iconeClasse}`;

      // Criar elemento
      const divMes = document.createElement("div");
      divMes.className = classes.join(" ");
      divMes.setAttribute("data-date", chaveMetasMes);
      divMes.setAttribute(
        "data-meta-mensal-batida",
        metaMensalBatida ? "true" : "false"
      );
      divMes.setAttribute("data-saldo", saldo_mes);
      divMes.setAttribute("data-timestamp", Date.now());

      divMes.innerHTML = `
        <span class="data-mes ${classe_texto}">
          ${nomeMes}
        </span>
        <div class="placar-mes">
          <span class="placar verde-bold ${placar_cinza}">${parseInt(
        dadosMes.total_green
      )}</span>
          <span class="placar separador ${placar_cinza}">×</span>
          <span class="placar vermelho-bold ${placar_cinza}">${parseInt(
        dadosMes.total_red
      )}</span>
        </div>
        <span class="valor ${cor_valor}">R$ ${saldo_formatado}</span>
        <span class="icone ${classe_texto}">
          <i class="${iconeClassesFull}"></i>
        </span>
      `;

      // Adicionar animação se houve mudança
      if (houveMudanca) {
        divMes.classList.add("atualizado-via-ajax");
        setTimeout(() => {
          divMes.classList.remove("atualizado-via-ajax");
        }, 1000);
      }

      fragment.appendChild(divMes);
    }

    // Substituir conteúdo do container
    container.innerHTML = "";
    container.appendChild(fragment);

    // Restaurar scroll
    container.scrollTop = scrollTop;

    // Disparar evento personalizado
    window.dispatchEvent(
      new CustomEvent("listaMesesAtualizadaAjax", {
        detail: {
          dados: responseData,
          timestamp: new Date(),
          totalMeses: 12,
        },
      })
    );
  },

  // Obter data de hoje
  obterDataHoje() {
    const d = new Date();
    const yy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, "0");
    const dd = String(d.getDate()).padStart(2, "0");
    return `${yy}-${mm}-${dd}`;
  },

  // Hash dos dados para detectar mudanças
  gerarHashDados(dados) {
    try {
      return JSON.stringify({
        dados_por_mes: dados.dados_por_mes || {},
        timestamp: Math.floor(Date.now() / 10000), // Agrupando por 10 segundos
      });
    } catch (error) {
      return Date.now().toString();
    }
  },

  // Configurar interceptadores mais robustos
  configurarInterceptadores() {
    console.log("Configurando interceptadores AJAX...");

    // 1. Interceptar submissão de formulários
    document.addEventListener("submit", (e) => {
      const form = e.target;
      console.log("Formulário submetido:", form.id || form.className);

      // Identificar formulários relevantes
      if (
        form.id === "form-mentor" ||
        form.classList.contains("formulario-mentor") ||
        form.querySelector('input[name="valor_green"]') ||
        form.querySelector('input[name="valor_red"]')
      ) {
        console.log("Formulário relevante detectado, agendando atualização...");

        setTimeout(() => {
          this.forcarProximaAtualizacao = true;
          this.atualizandoAtualmente = false;
          this.atualizarListaMeses();
        }, 500);
      }
    });

    // 2. Interceptar cliques em botões relevantes
    document.addEventListener("click", (e) => {
      const target = e.target;

      if (
        target.matches(
          'button[type="submit"], .btn-enviar, .btn-confirmar, .btn-salvar'
        ) ||
        target.closest(
          'button[type="submit"], .btn-enviar, .btn-confirmar, .btn-salvar'
        )
      ) {
        console.log("Botão relevante clicado:", target.textContent?.trim());

        setTimeout(() => {
          this.forcarProximaAtualizacao = true;
          this.atualizandoAtualmente = false;
          this.atualizarListaMeses();
        }, 300);
      }

      // Interceptar botões de exclusão
      if (
        target.matches(
          '.btn-excluir, .excluir-entrada, [data-action="excluir"]'
        ) ||
        target.closest(
          '.btn-excluir, .excluir-entrada, [data-action="excluir"]'
        )
      ) {
        console.log("Ação de exclusão detectada");

        setTimeout(() => {
          this.forcarProximaAtualizacao = true;
          this.atualizandoAtualmente = false;
          this.atualizarListaMeses();
        }, 800);
      }
    });

    // 3. Interceptar mudanças no filtro de período
    document.querySelectorAll('input[name="periodo"]').forEach((radio) => {
      radio.addEventListener("change", (e) => {
        console.log("Período alterado para:", e.target.value);

        this.periodoAtual = e.target.value;
        this.detectarMetaEPeriodo();
        this.forcarProximaAtualizacao = true;
        this.atualizandoAtualmente = false;
        this.atualizarListaMeses();
      });
    });

    // 4. Hook no fetch global mais robusto para dados_banca.php
    if (!window.fetchHookedForMeses) {
      const originalFetch = window.fetch;

      window.fetch = async function (...args) {
        const response = await originalFetch.apply(this, args);

        try {
          const url = args[0]?.toString() || "";

          // URLs que indicam mudança nos dados - FOCO EM dados_banca.php
          const urlsRelevantes = [
            "dados_banca.php",
            "dados_banca",
            "cadastrar-valor",
            "excluir-entrada",
            "carregar-mentores",
            "salvar-mentor",
            "atualizar-valores",
          ];

          if (urlsRelevantes.some((relevante) => url.includes(relevante))) {
            console.log("Fetch relevante detectado para lista meses:", url);

            // Delay maior para dados_banca.php garantir processamento
            const delay = url.includes("dados_banca") ? 800 : 400;

            setTimeout(() => {
              if (typeof ListaMesesManagerAnual !== "undefined") {
                console.log("Forçando atualização da lista após fetch:", url);
                ListaMesesManagerAnual.forcarProximaAtualizacao = true;
                ListaMesesManagerAnual.atualizandoAtualmente = false;
                ListaMesesManagerAnual.atualizarListaMeses();
              }
            }, delay);
          }
        } catch (e) {
          console.warn("Erro no hook fetch:", e);
        }

        return response;
      };

      window.fetchHookedForMeses = true;
      console.log("Hook do fetch configurado para dados_banca.php");
    }

    // 5. Hook específico para interceptar dados_banca.php
    this.configurarHookDadosBanca();

    // 6. Eventos customizados
    window.addEventListener("metaAtualizada", () => {
      console.log("Evento metaAtualizada detectado");
      this.detectarMetaEPeriodo();
      this.forcarProximaAtualizacao = true;
      this.atualizarListaMeses();
    });

    window.addEventListener("mentoresAtualizados", () => {
      console.log("Evento mentoresAtualizados detectado");
      this.forcarProximaAtualizacao = true;
      this.atualizarListaMeses();
    });

    // 7. Observer para mudanças no DOM
    this.configurarObserverDOM();
  },

  // Hook específico para dados_banca.php
  configurarHookDadosBanca() {
    try {
      // Interceptar qualquer chamada para dados_banca.php
      const interceptarDadosBanca = () => {
        console.log("dados_banca.php chamado, atualizando lista meses...");
        setTimeout(() => {
          this.forcarProximaAtualizacao = true;
          this.atualizandoAtualmente = false;
          this.atualizarListaMeses();
        }, 1000);
      };

      // Observer para XMLHttpRequest (caso não use fetch)
      if (window.XMLHttpRequest) {
        const originalOpen = XMLHttpRequest.prototype.open;
        XMLHttpRequest.prototype.open = function (method, url, ...args) {
          if (url && url.toString().includes("dados_banca")) {
            this.addEventListener("loadend", () => {
              interceptarDadosBanca();
            });
          }
          return originalOpen.apply(this, [method, url, ...args]);
        };
      }

      // Escutar eventos customizados do sistema principal
      window.addEventListener("bancaAtualizada", interceptarDadosBanca);
      window.addEventListener("metaAtualizada", interceptarDadosBanca);
      window.addEventListener("dadosBancaAtualizados", interceptarDadosBanca);

      console.log("Hook específico dados_banca.php configurado");
    } catch (error) {
      console.warn("Erro ao configurar hook dados_banca.php:", error);
    }
  },

  // Observer para mudanças no DOM
  configurarObserverDOM() {
    try {
      // Observer para detectar mudanças nos placares principais
      const placarContainer = document.querySelector(".container");

      if (placarContainer) {
        const observer = new MutationObserver((mutations) => {
          let deveAtualizar = false;

          mutations.forEach((mutation) => {
            if (
              mutation.type === "childList" ||
              mutation.type === "characterData"
            ) {
              // Verificar se a mudança foi em elementos relevantes
              const target = mutation.target;

              if (
                target.classList?.contains("placar-green") ||
                target.classList?.contains("placar-red") ||
                target.closest(".placar-green, .placar-red, .mentor-card")
              ) {
                deveAtualizar = true;
              }
            }
          });

          if (deveAtualizar) {
            console.log("Mudança no DOM detectada, atualizando lista...");

            setTimeout(() => {
              this.forcarProximaAtualizacao = true;
              this.atualizandoAtualmente = false;
              this.atualizarListaMeses();
            }, 200);
          }
        });

        observer.observe(placarContainer, {
          childList: true,
          subtree: true,
          characterData: true,
        });

        console.log("Observer DOM configurado");
      }
    } catch (error) {
      console.warn("Erro ao configurar observer DOM:", error);
    }
  },

  // Sincronizar com sistema principal (MetaManager, BancaManager, etc.)
  sincronizarComSistemaPrincipal() {
    try {
      console.log("Configurando sincronização com sistema principal...");

      // 1. Hook no MetaManager se existir
      if (
        typeof window.MetaManager !== "undefined" &&
        window.MetaManager.atualizarMeta
      ) {
        const originalAtualizarMeta = window.MetaManager.atualizarMeta;

        window.MetaManager.atualizarMeta = async function (...args) {
          const resultado = await originalAtualizarMeta.apply(this, args);

          setTimeout(() => {
            if (typeof ListaMesesManagerAnual !== "undefined") {
              console.log(
                "MetaManager.atualizarMeta executado, sincronizando lista..."
              );
              ListaMesesManagerAnual.forcarProximaAtualizacao = true;
              ListaMesesManagerAnual.atualizandoAtualmente = false;
              ListaMesesManagerAnual.atualizarListaMeses();
            }
          }, 500);

          return resultado;
        };
      }

      // 2. Hook no BancaManager se existir
      if (
        typeof window.BancaManager !== "undefined" &&
        window.BancaManager.atualizarBanca
      ) {
        const originalAtualizarBanca = window.BancaManager.atualizarBanca;

        window.BancaManager.atualizarBanca = async function (...args) {
          const resultado = await originalAtualizarBanca.apply(this, args);

          setTimeout(() => {
            if (typeof ListaMesesManagerAnual !== "undefined") {
              console.log(
                "BancaManager.atualizarBanca executado, sincronizando lista..."
              );
              ListaMesesManagerAnual.forcarProximaAtualizacao = true;
              ListaMesesManagerAnual.atualizandoAtualmente = false;
              ListaMesesManagerAnual.atualizarListaMeses();
            }
          }, 500);

          return resultado;
        };
      }

      // 3. Hook no atualizarDadosBanca se existir
      if (typeof window.atualizarDadosBanca === "function") {
        const originalAtualizarDadosBanca = window.atualizarDadosBanca;

        window.atualizarDadosBanca = async function (...args) {
          const resultado = await originalAtualizarDadosBanca.apply(this, args);

          setTimeout(() => {
            if (typeof ListaMesesManagerAnual !== "undefined") {
              console.log(
                "atualizarDadosBanca executado, sincronizando lista..."
              );
              ListaMesesManagerAnual.forcarProximaAtualizacao = true;
              ListaMesesManagerAnual.atualizandoAtualmente = false;
              ListaMesesManagerAnual.atualizarListaMeses();
            }
          }, 600);

          return resultado;
        };
      }

      // 4. Interceptar chamadas AJAX do jQuery se existir
      if (typeof $ !== "undefined" && $.ajaxSetup) {
        $(document).ajaxComplete(function (event, xhr, settings) {
          if (
            settings.url &&
            (settings.url.includes("dados_banca") ||
              settings.url.includes("cadastrar-valor") ||
              settings.url.includes("excluir-entrada"))
          ) {
            console.log("Ajax jQuery detectado:", settings.url);
            setTimeout(() => {
              if (typeof ListaMesesManagerAnual !== "undefined") {
                ListaMesesManagerAnual.forcarProximaAtualizacao = true;
                ListaMesesManagerAnual.atualizandoAtualmente = false;
                ListaMesesManagerAnual.atualizarListaMeses();
              }
            }, 700);
          }
        });
      }

      // 5. Monitorar mudanças na URL/hash
      let ultimaUrl = window.location.href;
      setInterval(() => {
        if (window.location.href !== ultimaUrl) {
          ultimaUrl = window.location.href;
          console.log("URL mudou, sincronizando lista...");
          setTimeout(() => {
            this.forcarProximaAtualizacao = true;
            this.atualizandoAtualmente = false;
            this.atualizarListaMeses();
          }, 300);
        }
      }, 1000);

      console.log("Sincronização com sistema principal configurada");
    } catch (error) {
      console.warn("Erro ao configurar sincronização:", error);
    }
  },

  // Forçar atualização
  forcarAtualizacao() {
    console.log("Forçando atualização da lista de meses...");
    this.forcarProximaAtualizacao = true;
    this.atualizandoAtualmente = false;
    this.detectarMetaEPeriodo();
    return this.atualizarListaMeses();
  },

  // Parar sistema
  parar() {
    if (this.intervaloAtualizacao) {
      clearInterval(this.intervaloAtualizacao);
      this.intervaloAtualizacao = null;
      console.log("Sistema de lista de meses parado");
    }
  },

  // Status do sistema
  status() {
    return {
      ativo: !!this.intervaloAtualizacao,
      atualizando: this.atualizandoAtualmente,
      ultimaAtualizacao: this.ultimaAtualizacao,
      metaMensal: this.metaMensal,
      metaAnual: this.metaAtual,
      periodoAtual: this.periodoAtual,
      hashAtual: this.hashUltimosDados.substring(0, 50) + "...",
      forcarProxima: this.forcarProximaAtualizacao,
    };
  },
};

// ========================================
// COMANDOS GLOBAIS ATUALIZADOS
// ========================================

window.ListaMesesAnual = {
  parar: () => ListaMesesManagerAnual.parar(),
  iniciar: () => ListaMesesManagerAnual.inicializar(),
  atualizar: () => ListaMesesManagerAnual.forcarAtualizacao(),
  status: () => ListaMesesManagerAnual.status(),
  info: () => {
    const status = ListaMesesManagerAnual.status();
    console.log("Status Lista Meses AJAX:", status);
    return status;
  },

  forcar: () => {
    console.log("FORÇANDO atualização via dados_banca.php...");
    ListaMesesManagerAnual.forcarProximaAtualizacao = true;
    ListaMesesManagerAnual.atualizandoAtualmente = false;
    return ListaMesesManagerAnual.atualizarListaMeses();
  },

  // Comando especial para debug/teste
  testeConexao: async () => {
    console.log("Testando conexão com dados_banca.php...");

    try {
      const response = await fetch("dados_banca.php?periodo=ano&teste=1", {
        method: "GET",
        headers: {
          "Cache-Control": "no-cache",
          "X-Requested-With": "XMLHttpRequest",
          "X-Periodo-Filtro": "ano",
        },
      });

      if (response.ok) {
        const data = await response.json();
        console.log(
          "dados_banca.php respondeu:",
          data.success ? "SUCESSO" : "ERRO"
        );
        console.log("Meta mensal retornada:", data.meta_mensal || 0);
        console.log("Meta anual retornada:", data.meta_anual || 0);
        console.log("Tipo meta:", data.tipo_meta || "turbo");
        return data;
      } else {
        console.error("dados_banca.php erro HTTP:", response.status);
        return null;
      }
    } catch (error) {
      console.error("Erro ao testar dados_banca.php:", error);
      return null;
    }
  },

  // Forçar sincronização imediata
  sincronizar: () => {
    console.log("Sincronizando com sistema principal...");
    ListaMesesManagerAnual.detectarMetaEPeriodo();
    ListaMesesManagerAnual.forcarProximaAtualizacao = true;
    ListaMesesManagerAnual.atualizandoAtualmente = false;
    return ListaMesesManagerAnual.atualizarListaMeses();
  },
};

// CSS para animação de atualização
const cssAnimacao = `
<style>
.atualizado-via-ajax {
  animation: pulseAjax 1s ease-in-out;
  border-left-color: #3b82f6 !important;
}

@keyframes pulseAjax {
  0%, 100% { 
    transform: scale(1); 
    opacity: 1; 
  }
  50% { 
    transform: scale(1.02); 
    opacity: 0.9; 
    background-color: rgba(59, 130, 246, 0.1) !important;
  }
}
</style>
`;

// Adicionar CSS de animação
if (!document.getElementById("css-ajax-animacao")) {
  const style = document.createElement("div");
  style.id = "css-ajax-animacao";
  style.innerHTML = cssAnimacao;
  document.head.appendChild(style);
}

// ========================================
// INICIALIZAÇÃO AUTOMÁTICA MELHORADA
// ========================================

function inicializarSistemaMesesAnualAjax() {
  console.log("Iniciando sistema de meses com AJAX...");

  // Aguardar um pouco mais para garantir que o DOM está pronto
  setTimeout(() => {
    if (typeof ListaMesesManagerAnual !== "undefined") {
      ListaMesesManagerAnual.inicializar();
      console.log("Sistema AJAX de meses inicializado!");
    }
  }, 1500);
}

// Aguardar DOM
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", () => {
    setTimeout(inicializarSistemaMesesAnualAjax, 1000);
  });
} else {
  setTimeout(inicializarSistemaMesesAnualAjax, 500);
}

console.log("Sistema de Lista de Meses AJAX carregado!");
console.log("Características AJAX:");
console.log("  ✅ Atualização automática via AJAX a cada 3 segundos");
console.log("  ✅ Detecção de mudanças em formulários");
console.log("  ✅ Hook no fetch global para interceptar requisições");
console.log("  ✅ Observer DOM para mudanças em tempo real");
console.log("  ✅ Animação visual quando valores mudam");
console.log("  ✅ Preservação de scroll e estado dos troféus");
console.log("");
console.log("Comandos:");
console.log("  ListaMesesAnual.atualizar() - Forçar atualização");
console.log("  ListaMesesAnual.forcar() - Forçar próxima atualização");
console.log("  ListaMesesAnual.status() - Ver status completo");
console.log("  ListaMesesAnual.info() - Ver informações detalhadas");
console.log("  ListaMesesAnual.testeConexao() - Testar dados_banca.php");
console.log("  ListaMesesAnual.sincronizar() - Sincronizar sistema");

// Export para uso externo
window.ListaMesesManagerAnual = ListaMesesManagerAnual;

//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
/* ===================================================================
   JAVASCRIPT - LISTA DE MESES ATUALIZAÇÃO SIMPLES E DIRETA
   Intercepta dados_banca.php e atualiza lista automaticamente
   =================================================================== */

const ListaMesesSimples = {
  atualizando: false,
  ultimaAtualizacao: null,
  metaMensal: 0,

  // Inicializar
  init() {
    console.log("Iniciando sistema simples de atualização da lista...");

    // Detectar meta atual
    this.detectarMeta();

    // Interceptar todas as chamadas para dados_banca.php
    this.interceptarDadosBanca();

    // Interceptar submissões de formulários
    this.interceptarFormularios();

    // Primeira atualização
    setTimeout(() => {
      this.atualizarLista();
    }, 1000);

    console.log("Sistema simples ativo!");
  },

  // Detectar meta mensal
  detectarMeta() {
    try {
      const dadosInfo = document.getElementById("dados-ano-info");
      if (dadosInfo) {
        this.metaMensal = parseFloat(dadosInfo.dataset.metaMensal) || 0;
      }
      console.log(`Meta mensal: R$ ${this.metaMensal.toFixed(2)}`);
    } catch (error) {
      console.error("Erro ao detectar meta:", error);
    }
  },

  // Interceptar dados_banca.php
  interceptarDadosBanca() {
    // Hook no fetch
    const originalFetch = window.fetch;

    window.fetch = async function (...args) {
      const response = await originalFetch.apply(this, args);

      try {
        const url = args[0]?.toString() || "";

        if (url.includes("dados_banca")) {
          console.log("dados_banca.php chamado - atualizando lista");

          setTimeout(() => {
            if (typeof ListaMesesSimples !== "undefined") {
              ListaMesesSimples.atualizarLista();
            }
          }, 800);
        }
      } catch (e) {
        // Silencioso
      }

      return response;
    };

    // Hook no XMLHttpRequest
    const originalOpen = XMLHttpRequest.prototype.open;
    XMLHttpRequest.prototype.open = function (method, url, ...args) {
      if (url && url.includes("dados_banca")) {
        this.addEventListener("loadend", () => {
          setTimeout(() => {
            if (typeof ListaMesesSimples !== "undefined") {
              console.log("XMLHttpRequest dados_banca.php - atualizando lista");
              ListaMesesSimples.atualizarLista();
            }
          }, 800);
        });
      }
      return originalOpen.apply(this, [method, url, ...args]);
    };

    console.log("Interceptação de dados_banca.php configurada");
  },

  // Interceptar formulários
  interceptarFormularios() {
    // Submissão de formulários
    document.addEventListener("submit", (e) => {
      const form = e.target;

      if (
        form.id === "form-mentor" ||
        form.classList.contains("formulario-mentor") ||
        form.querySelector('input[name="valor_green"]') ||
        form.querySelector('input[name="valor_red"]')
      ) {
        console.log("Formulário submetido - atualizando lista em 1s");

        setTimeout(() => {
          this.atualizarLista();
        }, 1000);
      }
    });

    // Cliques em botões
    document.addEventListener("click", (e) => {
      const target = e.target;

      if (
        target.matches('button[type="submit"], .btn-enviar, .btn-confirmar') ||
        target.closest('button[type="submit"], .btn-enviar, .btn-confirmar')
      ) {
        setTimeout(() => {
          this.atualizarLista();
        }, 500);
      }
    });

    console.log("Interceptação de formulários configurada");
  },

  // Atualizar lista principal
  async atualizarLista() {
    if (this.atualizando) return;

    this.atualizando = true;

    try {
      console.log("Atualizando lista de meses...");

      // Buscar dados via dados_banca.php
      const response = await fetch("dados_banca.php?periodo=ano", {
        method: "GET",
        headers: {
          "Cache-Control": "no-cache",
          "X-Requested-With": "XMLHttpRequest",
        },
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }

      const dados = await response.json();

      if (!dados.success) {
        throw new Error("Erro nos dados retornados");
      }

      // Atualizar meta mensal
      this.metaMensal = dados.meta_mensal || 0;

      // Buscar dados mensais específicos
      await this.buscarDadosMensais();

      this.ultimaAtualizacao = new Date();
      console.log(
        "Lista atualizada:",
        this.ultimaAtualizacao.toLocaleTimeString()
      );
    } catch (error) {
      console.error("Erro ao atualizar lista:", error);
    } finally {
      this.atualizando = false;
    }
  },

  // Buscar dados mensais e renderizar
  async buscarDadosMensais() {
    try {
      const response = await fetch("obter_dados_ano.php", {
        method: "GET",
        headers: {
          "Cache-Control": "no-cache",
          "X-Requested-With": "XMLHttpRequest",
        },
      });

      if (response.ok) {
        const dados = await response.json();
        this.renderizarMeses(dados.dados_por_mes || {});
      } else {
        console.warn("Erro ao buscar dados mensais");
      }
    } catch (error) {
      console.error("Erro ao buscar dados mensais:", error);
    }
  },

  // Renderizar meses
  renderizarMeses(dadosPorMes) {
    const container = document.querySelector(".lista-meses");
    if (!container) return;

    const scrollTop = container.scrollTop;
    const ano = new Date().getFullYear();
    const mesAtual = new Date().getMonth() + 1;

    const nomesMeses = [
      "Janeiro",
      "Fevereiro",
      "Março",
      "Abril",
      "Maio",
      "Junho",
      "Julho",
      "Agosto",
      "Setembro",
      "Outubro",
      "Novembro",
      "Dezembro",
    ];

    let htmlMeses = "";

    for (let mes = 1; mes <= 12; mes++) {
      const mesStr = mes.toString().padStart(2, "0");
      const chave = `${ano}-${mesStr}`;
      const nomeMes = nomesMeses[mes - 1];

      const dadosMes = dadosPorMes[chave] || {
        total_valor_green: 0,
        total_valor_red: 0,
        total_green: 0,
        total_red: 0,
      };

      const saldo =
        parseFloat(dadosMes.total_valor_green) -
        parseFloat(dadosMes.total_valor_red);
      const saldoFormatado = saldo.toLocaleString("pt-BR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      });

      // Verificar meta mensal
      let metaBatida = false;
      if (this.metaMensal > 0) {
        metaBatida = saldo >= this.metaMensal;
      } else {
        metaBatida = saldo >= 500; // Critério padrão
      }

      // Classes
      let classes = "gd-linha-mes";
      if (saldo > 0) classes += " valor-positivo";
      else if (saldo < 0) classes += " valor-negativo";
      else classes += " valor-zero";

      if (mes === mesAtual) classes += " gd-mes-hoje mes-atual";

      // Cores
      const corValor =
        saldo === 0
          ? "texto-cinza"
          : saldo > 0
          ? "verde-bold"
          : "vermelho-bold";

      const classeTexto = saldo === 0 ? "texto-cinza" : "";

      const placarCinza =
        parseInt(dadosMes.total_green) === 0 &&
        parseInt(dadosMes.total_red) === 0
          ? "texto-cinza"
          : "";

      // Ícone
      const icone = metaBatida ? "fa-trophy trofeu-icone" : "fa-check";

      htmlMeses += `
        <div class="${classes}" 
             data-date="${chave}" 
             data-meta-mensal-batida="${metaBatida ? "true" : "false"}"
             data-saldo="${saldo}">
          
          <span class="data-mes ${classeTexto}">${nomeMes}</span>
          
          <div class="placar-mes">
            <span class="placar verde-bold ${placarCinza}">${parseInt(
        dadosMes.total_green
      )}</span>
            <span class="placar separador ${placarCinza}">x</span>
            <span class="placar vermelho-bold ${placarCinza}">${parseInt(
        dadosMes.total_red
      )}</span>
          </div>
          
          <span class="valor ${corValor}">R$ ${saldoFormatado}</span>
          
          <span class="icone ${classeTexto}">
            <i class="fa-solid ${icone}"></i>
          </span>
        </div>
      `;
    }

    container.innerHTML = htmlMeses;
    container.scrollTop = scrollTop;

    console.log("Meses renderizados com sucesso");
  },

  // Forçar atualização
  forcar() {
    console.log("Forçando atualização...");
    this.atualizando = false;
    this.atualizarLista();
  },

  // Status
  status() {
    return {
      atualizando: this.atualizando,
      ultimaAtualizacao: this.ultimaAtualizacao,
      metaMensal: this.metaMensal,
    };
  },
};

// Comandos globais simples
window.ListaMeses = {
  atualizar: () => ListaMesesSimples.forcar(),
  status: () => ListaMesesSimples.status(),
  info: () => {
    console.log("Status:", ListaMesesSimples.status());
    return ListaMesesSimples.status();
  },
};

// Auto-inicialização
function initListaMeses() {
  if (typeof ListaMesesSimples !== "undefined") {
    ListaMesesSimples.init();
  }
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", () => {
    setTimeout(initListaMeses, 500);
  });
} else {
  setTimeout(initListaMeses, 300);
}

console.log("Sistema simples de lista de meses carregado!");
console.log("Comandos:");
console.log("  ListaMeses.atualizar() - Forçar atualização");
console.log("  ListaMeses.status() - Ver status");

// Export
window.ListaMesesSimples = ListaMesesSimples;
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
/* ===================================================================
   - GRAFICO - GRAFICO - GRAFICO - GRAFICO - GRAFICO - GRAFICO -
   =================================================================== */

/* ===================================================================
   - GRAFICO - GRAFICO - GRAFICO - GRAFICO - GRAFICO - GRAFICO -
   =================================================================== */
//
//
//
//
//
//
//
//
//
//
//
//
//
//
