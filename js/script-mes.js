document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("form-mentor");

  if (form) {
    const timeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    const dataLocal = new Date().toISOString();

    const criarInput = (name, value) => {
      let existing = form.querySelector(`[name="${name}"]`);
      if (!existing) {
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = name;
        input.value = value;
        form.appendChild(input);
      }
    };

    criarInput("user_time_zone", timeZone);
    criarInput("data_local", dataLocal);
  } else {
    console.warn("❌ Formulário #form-mentor não encontrado.");
  }
});

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
// ========================================================================================================================
//                                 JS DAOS CAMPOS ONDE FILTRA O MÊS BARRA DE PROGRESSO META E SALDO
// ========================================================================================================================
// =============================================
//  CORREÇÃO DOS ÍCONES - USANDO CLASSES CORRETAS DO FONT AWESOME
// =============================================

const MetaMensalManager = {
  // Controle simples para meta mensal
  atualizandoAtualmente: false,
  periodoFixo: "mes",
  tipoMetaAtual: "turbo",

  // Atualizar meta mensal - versão específica
  async atualizarMetaMensal(aguardarDados = false) {
    if (this.atualizandoAtualmente) return null;
    this.atualizandoAtualmente = true;

    try {
      if (aguardarDados) {
        await new Promise((resolve) => setTimeout(resolve, 100));
      }

      const response = await fetch("dados_banca.php?periodo=mes", {
        method: "GET",
        headers: {
          "Cache-Control": "no-cache",
          "X-Requested-With": "XMLHttpRequest",
          "X-Periodo-Filtro": "mes",
        },
      });

      if (!response.ok) throw new Error(`HTTP ${response.status}`);

      const data = await response.json();
      if (!data.success) throw new Error(data.message);

      if (data.tipo_meta) {
        this.tipoMetaAtual = data.tipo_meta;
      }

      const dadosProcessados = this.processarDadosMensais(data);
      this.atualizarTodosElementosMensais(dadosProcessados);

      return dadosProcessados;
    } catch (error) {
      console.error("Erro Meta Mensal:", error);
      this.mostrarErroMetaMensal();
      return null;
    } finally {
      this.atualizandoAtualmente = false;
    }
  },

  // Processar dados especificamente para mensal
  processarDadosMensais(data) {
    try {
      const metaFinal = parseFloat(data.meta_mensal) || 0;
      const rotuloFinal = "Meta do Mês";
      const lucroMensal = parseFloat(data.lucro) || 0;

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
        periodo_ativo: "mes",
        lucro_periodo: lucroMensal,
      };
    } catch (error) {
      console.error("Erro ao processar dados mensais:", error);
      return data;
    }
  },

  // ✅ NOVA FUNÇÃO: CALCULAR META FINAL MENSAL COM VALOR TACHADO E EXTRA
  calcularMetaFinalMensalComExtra(saldoMes, metaCalculada, bancaTotal, data) {
    try {
      let metaFinal,
        rotulo,
        statusClass,
        valorExtra = 0,
        mostrarTachado = false;

      console.log(`🔍 DEBUG CALCULAR META MENSAL COM EXTRA:`);
      console.log(`   Saldo Mês: R$ ${saldoMes.toFixed(2)}`);
      console.log(`   Meta Mês: R$ ${metaCalculada.toFixed(2)}`);
      console.log(`   Banca: R$ ${bancaTotal.toFixed(2)}`);

      if (bancaTotal <= 0) {
        metaFinal = bancaTotal;
        rotulo = "Deposite p/ Começar";
        statusClass = "sem-banca";
        console.log(`📊 RESULTADO MENSAL: Sem banca`);
      }
      // ✅ META BATIDA OU SUPERADA - COM VALOR EXTRA
      else if (saldoMes > 0 && metaCalculada > 0 && saldoMes >= metaCalculada) {
        // Evitar problemas de ponto flutuante: comparar por centavos (inteiro)
        const rawExtra = saldoMes - metaCalculada;
        const extraCentavos = Math.round(rawExtra * 100);

        valorExtra = extraCentavos > 0 ? extraCentavos / 100 : 0;
        mostrarTachado = true;
        metaFinal = metaCalculada; // Mostra o valor da meta original

        if (extraCentavos > 0) {
          rotulo = `Meta do Mês Superada! <i class='fa-solid fa-trophy'></i>`;
          statusClass = "meta-superada";
          console.log(
            `🏆 META MENSAL SUPERADA: Extra de R$ ${valorExtra.toFixed(2)}`
          );
        } else {
          rotulo = `Meta do Mês Batida! <i class='fa-solid fa-trophy'></i>`;
          statusClass = "meta-batida";
          console.log(`🎯 META MENSAL EXATA`);
        }
      }
      // ✅ CASO ESPECIAL: Meta é zero (já foi batida)
      else if (metaCalculada === 0 && saldoMes > 0) {
        metaFinal = 0;
        valorExtra = saldoMes;
        mostrarTachado = false;
        rotulo = `Meta do Mês Batida! <i class='fa-solid fa-trophy'></i>`;
        statusClass = "meta-batida";
        console.log(`🎯 META MENSAL ZERO (já batida)`);
      } else if (saldoMes < 0) {
        metaFinal = metaCalculada - saldoMes;
        rotulo = `Restando p/ Meta do Mês`;
        statusClass = "negativo";
        console.log(`📊 RESULTADO MENSAL: Negativo`);
      } else if (saldoMes === 0) {
        metaFinal = metaCalculada;
        rotulo = "Meta do Mês";
        statusClass = "neutro";
        console.log(`📊 RESULTADO MENSAL: Neutro`);
      } else {
        // Lucro positivo mas menor que a meta
        metaFinal = metaCalculada - saldoMes;
        rotulo = `Restando p/ Meta do Mês`;
        statusClass = "lucro";
        console.log(`📊 RESULTADO MENSAL: Lucro insuficiente`);
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

      console.log(`🏁 RESULTADO FINAL MENSAL COM EXTRA:`);
      console.log(`   Status: ${statusClass}`);
      console.log(`   Valor Extra: R$ ${valorExtra.toFixed(2)}`);
      console.log(`   Mostrar Tachado: ${mostrarTachado}`);

      return resultado;
    } catch (error) {
      console.error("Erro ao calcular meta final mensal com extra:", error);
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

  // Atualizar todos os elementos - versão para bloco 2 COM EXTRA
  atualizarTodosElementosMensais(data) {
    try {
      const saldoMes =
        parseFloat(data.lucro_periodo) || parseFloat(data.lucro) || 0;
      const metaCalculada = parseFloat(data.meta_display) || 0;
      const bancaTotal = parseFloat(data.banca) || 0;

      const dadosComplementados = {
        ...data,
        meta_original: data.meta_original || metaCalculada,
      };

      // ✅ USAR NOVA FUNÇÃO COM VALOR EXTRA
      const resultado = this.calcularMetaFinalMensalComExtra(
        saldoMes,
        metaCalculada,
        bancaTotal,
        dadosComplementados
      );

      // Atualizar elementos do bloco 2
      this.garantirIconeMoeda();
      this.atualizarMetaElementoMensalComExtra(resultado); // ✅ NOVA FUNÇÃO
      this.atualizarRotuloMensal(resultado.rotulo);
      this.atualizarBarraProgressoMensal(resultado, data);

      console.log(`Meta MENSAL atualizada COM EXTRA`);
      console.log(`Lucro do MÊS: R$ ${saldoMes.toFixed(2)}`);
      console.log(`Meta MENSAL: R$ ${metaCalculada.toFixed(2)}`);

      if (resultado.valorExtra > 0) {
        console.log(
          `🏆 Valor Extra MENSAL: R$ ${resultado.valorExtra.toFixed(2)}`
        );
      }
    } catch (error) {
      console.error("Erro ao atualizar elementos mensais:", error);
    }
  },

  // ✅ NOVA FUNÇÃO: ATUALIZAR META ELEMENTO MENSAL COM VALOR TACHADO E EXTRA
  atualizarMetaElementoMensalComExtra(resultado) {
    try {
      const metaValor = document.getElementById("meta-valor-2");
      if (!metaValor) {
        console.warn("Elemento meta-valor-2 não encontrado");
        return;
      }

      // ✅ LIMPAR CLASSES ANTIGAS
      metaValor.className = metaValor.className.replace(
        /\bvalor-meta-2\s+\w+/g,
        ""
      );

      let htmlConteudo = "";

      if (resultado.mostrarTachado && resultado.valorExtra >= 0) {
        // ✅ META BATIDA/SUPERADA - MOSTRAR VALOR TACHADO + EXTRA
        htmlConteudo = `
          <i class="fa-solid fa-coins"></i>
          <div class="meta-valor-container-2">
            <span class="valor-tachado-2">${
              resultado.metaOriginalFormatada
            }</span>
            ${
              resultado.valorExtra > 0
                ? `<span class="valor-extra-2">+ ${resultado.valorExtraFormatado}</span>`
                : ""
            }
          </div>
        `;

        metaValor.classList.add("valor-meta-2", "meta-com-extra-2");
        console.log(
          `✅ Valor tachado MENSAL aplicado: ${resultado.metaOriginalFormatada}`
        );

        if (resultado.valorExtra > 0) {
          console.log(
            `✅ Valor extra MENSAL aplicado: + ${resultado.valorExtraFormatado}`
          );
        }
      } else {
        // ✅ EXIBIÇÃO NORMAL
        htmlConteudo = `
          <i class="fa-solid fa-coins"></i>
          <div class="meta-valor-container-2">
            <span class="valor-texto-2" id="valor-texto-meta-2">${resultado.metaFinalFormatada}</span>
          </div>
        `;

        metaValor.classList.add("valor-meta-2", resultado.statusClass);
      }

      metaValor.innerHTML = htmlConteudo;
    } catch (error) {
      console.error("Erro ao atualizar meta elemento mensal com extra:", error);
    }
  },

  // FUNÇÃO CORRIGIDA: GARANTIR ÍCONE DA MOEDA COM CLASSES CORRETAS
  garantirIconeMoeda() {
    try {
      const metaValor = document.getElementById("meta-valor-2");
      if (!metaValor) return;

      // Verificar se já tem o ícone (classes corretas do Font Awesome)
      const iconeExistente = metaValor.querySelector(".fa-coins");

      if (!iconeExistente) {
        const valorTexto = metaValor.querySelector(".valor-texto-2");
        if (valorTexto) {
          const textoAtual = valorTexto.textContent;
          // USAR CLASSES CORRETAS DO FONT AWESOME
          metaValor.innerHTML = `
            <i class="fa-solid fa-coins"></i>
            <div class="meta-valor-container-2">
              <span class="valor-texto-2">${textoAtual}</span>
            </div>
          `;
          console.log("Ícone da moeda adicionado ao HTML 2");
        }
      }
    } catch (error) {
      console.error("Erro ao garantir ícone da moeda:", error);
    }
  },

  // Atualizar rótulo - bloco 2
  atualizarRotuloMensal(rotulo) {
    try {
      const rotuloElement = document.getElementById("rotulo-meta-2");
      if (rotuloElement) {
        rotuloElement.innerHTML = rotulo;
      } else {
        console.warn("Elemento rotulo-meta-2 não encontrado");
      }
    } catch (error) {
      console.error("Erro ao atualizar rótulo mensal:", error);
    }
  },

  // FUNÇÃO CORRIGIDA: ÍCONES DINÂMICOS DO SALDO COM CLASSES CORRETAS
  atualizarIconesSaldoDinamicos(saldoMes) {
    try {
      const saldoInfo = document.getElementById("saldo-info-2");
      if (!saldoInfo) return;

      const saldoFormatado = saldoMes.toLocaleString("pt-BR", {
        style: "currency",
        currency: "BRL",
      });

      let textoSaldo = "Saldo";
      let iconeClass = "fa-solid fa-wallet"; // CLASSE CORRETA
      let classeEstado = "saldo-zero-2";

      // Determinar texto, ícone e classe baseado no valor
      if (saldoMes > 0) {
        textoSaldo = "Lucro Mês";
        iconeClass = "fa-solid fa-chart-line"; // GRÁFICO SUBINDO
        classeEstado = "saldo-positivo-2";
      } else if (saldoMes < 0) {
        textoSaldo = "Negativo Mês";
        iconeClass = "fa-solid fa-arrow-trend-down"; // GRÁFICO DESCENDO
        classeEstado = "saldo-negativo-2";
      } else {
        textoSaldo = "Saldo Mês";
        iconeClass = "fa-solid fa-wallet"; // CARTEIRA
        classeEstado = "saldo-zero-2";
      }

      // Atualizar HTML do saldo COM CLASSES CORRETAS
      saldoInfo.innerHTML = `
        <i class="${iconeClass}"></i>
        <span class="saldo-info-rotulo-2">${textoSaldo}:</span>
        <span class="saldo-info-valor-2">${saldoFormatado}</span>
      `;

      // Aplicar classe de estado
      saldoInfo.className = classeEstado;

      console.log(`Ícone HTML 2 atualizado: ${textoSaldo} - ${iconeClass}`);
    } catch (error) {
      console.error("Erro ao atualizar ícones dinâmicos HTML 2:", error);
    }
  },

  // ✅ NOVA FUNÇÃO: LIMPAR COMPLETAMENTE O ESTADO DA BARRA
  limparEstadoBarraMensal() {
    try {
      const barraProgresso = document.getElementById("barra-progresso-2");
      const porcentagemBarra = document.getElementById("porcentagem-barra-2");

      if (barraProgresso) {
        // Remover todas as classes possíveis
        barraProgresso.classList.remove(
          "barra-meta-batida-2",
          "barra-meta-superada-2",
          "barra-negativo-2",
          "barra-lucro-2",
          "barra-neutro-2",
          "barra-sem-banca-2",
          "barra-erro-2"
        );

        // Limpar estilos inline
        barraProgresso.style.width = "0%";
        barraProgresso.style.backgroundColor = "";
        barraProgresso.style.background = "";
        barraProgresso.style.filter = "";
        barraProgresso.style.animation = "";

        // Garantir classe base
        if (!barraProgresso.classList.contains("widget-barra-progresso-2")) {
          barraProgresso.classList.add("widget-barra-progresso-2");
        }
      }

      if (porcentagemBarra) {
        porcentagemBarra.innerHTML =
          '<span class="porcentagem-fundo-2">0%</span>';
        porcentagemBarra.classList.remove("pequeno", "oculta");
        porcentagemBarra.classList.add("oculta");
      }

      console.log("Barra mensal limpa completamente");
    } catch (error) {
      console.error("Erro ao limpar estado da barra mensal:", error);
    }
  },
  atualizarBarraProgressoMensal(resultado, data) {
    try {
      const barraProgresso = document.getElementById("barra-progresso-2");
      const saldoInfo = document.getElementById("saldo-info-2");
      const porcentagemBarra = document.getElementById("porcentagem-barra-2");

      if (!barraProgresso) {
        console.warn("Elemento barra-progresso-2 não encontrado");
        return;
      }

      const saldoMes =
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
        } else if (saldoMes < 0) {
          progresso = -Math.min(Math.abs(saldoMes / metaCalculada) * 100, 100);
        } else {
          progresso = Math.max(
            0,
            Math.min(100, (saldoMes / metaCalculada) * 100)
          );
        }
      }

      const larguraBarra = Math.abs(progresso);

      // ✅ LIMPEZA COMPLETA DAS CLASSES ANTIGAS
      let classeCor = "";

      // Remover TODAS as classes de cor possíveis
      barraProgresso.classList.remove(
        "barra-meta-batida-2",
        "barra-meta-superada-2",
        "barra-negativo-2",
        "barra-lucro-2",
        "barra-neutro-2",
        "barra-sem-banca-2",
        "barra-erro-2"
      );

      // Garantir classe base
      if (!barraProgresso.classList.contains("widget-barra-progresso-2")) {
        barraProgresso.classList.add("widget-barra-progresso-2");
      }

      // Aplicar classe correta com sufixo -2
      if (
        resultado.statusClass === "meta-batida" ||
        resultado.statusClass === "meta-superada"
      ) {
        classeCor = "barra-meta-batida-2";
        console.log(
          `✅ BARRA MENSAL META BATIDA/SUPERADA - Saldo: R$ ${saldoMes.toFixed(
            2
          )}, Meta: R$ ${metaCalculada.toFixed(2)}`
        );
      } else {
        classeCor = `barra-${resultado.statusClass}-2`;
        console.log(
          `✅ BARRA MENSAL NORMAL - Status: ${
            resultado.statusClass
          }, Saldo: R$ ${saldoMes.toFixed(2)}`
        );
      }

      // Aplicar classe e estilos com limpeza forçada
      barraProgresso.classList.add(classeCor);

      // ✅ FORÇAR RESET DE ESTILOS INLINE ANTIGOS
      barraProgresso.style.width = `${larguraBarra}%`;
      barraProgresso.style.backgroundColor = "";
      barraProgresso.style.background = "";
      barraProgresso.style.filter = "";
      barraProgresso.style.animation = "";

      console.log(
        `✅ BARRA MENSAL - Classe aplicada: ${classeCor}, Largura: ${larguraBarra}%`
      );

      // Porcentagem
      if (porcentagemBarra) {
        const porcentagemTexto = Math.round(progresso) + "%";
        porcentagemBarra.innerHTML = `
          <span class="porcentagem-fundo-2 ${classeCor}">${porcentagemTexto}</span>
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

      // ATUALIZAR ÍCONES DINÂMICOS DO SALDO
      this.atualizarIconesSaldoDinamicos(saldoMes);
    } catch (error) {
      console.error("Erro ao atualizar barra progresso mensal:", error);
    }
  },

  // Mostrar erro específico para mensal
  mostrarErroMetaMensal() {
    try {
      const metaElement = document.getElementById("meta-valor-2");
      if (metaElement) {
        // USAR CLASSES CORRETAS DO FONT AWESOME
        metaElement.innerHTML =
          '<i class="fa-solid fa-coins"></i><div class="meta-valor-container-2"><span class="valor-texto-2 loading-text-2">R$ 0,00</span></div>';
      }
    } catch (error) {
      console.error("Erro ao mostrar erro meta mensal:", error);
    }
  },

  // Inicializar sistema mensal (com garantia do ícone)
  inicializar() {
    try {
      const metaElement = document.getElementById("meta-valor-2");
      if (metaElement) {
        // USAR CLASSES CORRETAS DO FONT AWESOME
        metaElement.innerHTML =
          '<i class="fa-solid fa-coins"></i><div class="meta-valor-container-2"><span class="valor-texto-2 loading-text-2">Calculando...</span></div>';
      }

      console.log(`Sistema Meta MENSAL COM VALOR TACHADO E EXTRA inicializado`);

      // Garantir ícone da moeda após delay
      setTimeout(() => {
        this.garantirIconeMoeda();
      }, 1500);

      // Inicializar com delay
      setTimeout(() => {
        this.atualizarMetaMensal();
      }, 1000);
    } catch (error) {
      console.error("Erro na inicialização mensal:", error);
    }
  },

  // Sincronizar com mudanças do bloco 1
  sincronizarComBloco1() {
    try {
      this.atualizarMetaMensal(true);
    } catch (error) {
      console.error("Erro ao sincronizar com bloco 1:", error);
    }
  },
};

// ========================================
// FUNÇÕES GLOBAIS E ATALHOS
// ========================================

window.atualizarMetaMensal = () => {
  if (typeof MetaMensalManager !== "undefined") {
    return MetaMensalManager.atualizarMetaMensal();
  }
  return null;
};

window.$2 = {
  force: () => {
    if (typeof MetaMensalManager !== "undefined") {
      MetaMensalManager.atualizandoAtualmente = false;
      return MetaMensalManager.atualizarMetaMensal();
    }
    return null;
  },

  sync: () => {
    if (typeof MetaMensalManager !== "undefined") {
      return MetaMensalManager.sincronizarComBloco1();
    }
    return null;
  },

  // ✅ NOVO: Função para testar valor tachado e extra
  testExtra: () => {
    console.log("Testando valor tachado e extra MENSAL...");

    if (typeof MetaMensalManager === "undefined") {
      return "MetaMensalManager não encontrado";
    }

    // Simular diferentes cenários de teste
    const testData = {
      meta_display: 1000,
      meta_display_formatada: "R$ 1.000,00",
      banca: 5000,
      rotulo_periodo: "Meta do Mês",
    };

    // Teste 1: Meta exatamente batida
    setTimeout(() => {
      console.log("Teste 1: Meta MENSAL exatamente batida (R$ 1000)");
      const resultado = MetaMensalManager.calcularMetaFinalMensalComExtra(
        1000,
        1000,
        5000,
        testData
      );
      MetaMensalManager.atualizarMetaElementoMensalComExtra(resultado);
    }, 1000);

    // Teste 2: Meta superada
    setTimeout(() => {
      console.log("Teste 2: Meta MENSAL superada (R$ 1250 - extra R$ 250)");
      const resultado = MetaMensalManager.calcularMetaFinalMensalComExtra(
        1250,
        1000,
        5000,
        testData
      );
      MetaMensalManager.atualizarMetaElementoMensalComExtra(resultado);
    }, 2500);

    // Teste 3: Meta não batida
    setTimeout(() => {
      console.log("Teste 3: Meta MENSAL não batida (R$ 750)");
      const resultado = MetaMensalManager.calcularMetaFinalMensalComExtra(
        750,
        1000,
        5000,
        testData
      );
      MetaMensalManager.atualizarMetaElementoMensalComExtra(resultado);
    }, 4000);

    return "Teste MENSAL completo em 4 segundos - valor tachado e extra";
  },

  info: () => {
    try {
      const metaElement = document.getElementById("meta-valor-2");
      const saldoElement = document.getElementById("saldo-info-2");

      const info = {
        meta: !!metaElement,
        saldo: !!saldoElement,
        iconeMoeda: !!metaElement?.querySelector(".fa-coins"),
        iconeAtual: saldoElement?.querySelector("i")?.className || "N/A",
        metaContent: metaElement ? metaElement.textContent : "N/A",
        temTachado: !!metaElement?.querySelector(".valor-tachado-2"),
        temExtra: !!metaElement?.querySelector(".valor-extra-2"),
        verificacao: "Sistema Meta Mensal COM valor tachado e extra",
      };

      console.log("Info Sistema Meta Mensal COM EXTRA:", info);
      return "Info Meta Mensal COM VALOR TACHADO E EXTRA verificada";
    } catch (error) {
      console.error("Erro ao obter info mensal:", error);
      return "Erro ao obter informações mensais";
    }
  },
};

// ========================================
// INICIALIZAÇÃO
// ========================================

function inicializarSistemaMetaMensal() {
  try {
    console.log(
      "Inicializando Sistema Meta MENSAL COM VALOR TACHADO E EXTRA..."
    );

    if (typeof MetaMensalManager !== "undefined") {
      MetaMensalManager.inicializar();
      console.log("MetaMensalManager COM EXTRA inicializado");
    }

    console.log("Sistema Meta MENSAL COM VALOR TACHADO E EXTRA inicializado!");
    console.log("Características:");
    console.log("   ✅ Sempre mostra META DO MÊS");
    console.log("   ✅ Ícone da moeda garantido");
    console.log("   ✅ Ícones dinâmicos do saldo");
    console.log("   ✅ Barra de progresso reduzida");
    console.log("   ✅ Classes Font Awesome corretas");
    console.log("   ✅ VALOR TACHADO quando meta batida");
    console.log("   ✅ VALOR EXTRA em dourado quando meta superada");
  } catch (error) {
    console.error("Erro na inicialização sistema mensal:", error);
  }
}

// ========================================
// SISTEMA DE INTERCEPTAÇÃO RÁPIDA
// ========================================

// Sistema de interceptação rápida (melhorado)
(function () {
  // Timestamp da última atualização bem-sucedida
  let ultimaAtualizacao = 0;
  // Intervalo mínimo entre atualizações (ms) - reduzido para responder rapidamente
  const MIN_INTERVAL_MS = 200; // evita loops agressivos, permite resposta quase imediata

  function atualizarRapido() {
    const agora = Date.now();
    if (agora - ultimaAtualizacao < MIN_INTERVAL_MS) return; // Evitar spam

    ultimaAtualizacao = agora;

    if (typeof MetaMensalManager !== "undefined") {
      // Forçar estado para permitir reexecução imediata
      MetaMensalManager.atualizandoAtualmente = false;
      // Sem delay
      MetaMensalManager.atualizarMetaMensal(false);
    }
  }

  // Chamadas diretas em eventos do usuário: executar imediatamente (ou com micro-delay)
  document.addEventListener(
    "submit",
    (e) => {
      // Empregar micro timeout para permitir que o envio/do DOM atualize antes da requisição
      setTimeout(atualizarRapido, 50);
    },
    true
  );

  document.addEventListener(
    "click",
    (e) => {
      if (
        e.target.closest('button, .btn, input[type="submit"], a[data-action]')
      ) {
        setTimeout(atualizarRapido, 50);
      }
    },
    true
  );

  // Hook em fetch para detectar requisições que alteram dados e disparar atualização após retorno
  try {
    const _fetch = window.fetch;
    window.fetch = function (...args) {
      const url = args[0] && args[0].toString ? args[0].toString() : "";
      return _fetch.apply(this, args).then((resp) => {
        try {
          if (
            /dados_banca|carregar-mentores|controle|valor_mentores/i.test(url)
          ) {
            // pequeno atraso para permitir processamento do servidor/DOM
            setTimeout(atualizarRapido, 50);
          }
        } catch (e) {}
        return resp;
      });
    };
  } catch (e) {
    console.warn(
      "Não foi possível hookar fetch para atualizações automáticas MENSAL",
      e
    );
  }

  // Interval fallback (mais longo) para garantir eventual consistência
  setInterval(atualizarRapido, 5000);

  // Primeira atualização imediata
  setTimeout(atualizarRapido, 50);

  // Expor utilitário
  window.atualizarRapidoMensal = atualizarRapido;

  console.log(
    "Sistema rápido MENSAL (melhorado) ativo - responde imediatamente a mudanças"
  );
})();

// Aguardar DOM
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", () => {
    setTimeout(inicializarSistemaMetaMensal, 1200);
  });
} else {
  setTimeout(inicializarSistemaMetaMensal, 800);
}

console.log("Sistema Meta MENSAL COM VALOR TACHADO E EXTRA carregado!");
console.log("Comandos MENSAIS:");
console.log("  $2.force() - Forçar atualização");
console.log("  $2.testExtra() - Testar valor tachado e extra");
console.log("  $2.sync() - Sincronizar com bloco 1");
console.log("  $2.info() - Ver status completo");

// Export para uso externo
window.MetaMensalManager = MetaMensalManager;
// AQUI FINAL PARTE DO CODIGO QUE QTUALIZA EM TEMPO REAL VIA AJAX OS VALORES
// ========================================================================================================================
//                               FIM JS DAOS CAMPOS ONDE FILTRA O MÊS BARRA DE PROGRESSO META E SALDO
// ========================================================================================================================
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
// ========================================================================================================================
//                                          JS DO PLACAR DO BLOCO 2 MÊS
// ========================================================================================================================

const PlacarMensalManager = {
  // ✅ CONTROLE DE ESTADO
  atualizandoAtualmente: false,
  intervaloPlacar: null,
  ultimaAtualizacao: null,

  // ✅ INICIALIZAR SISTEMA DE PLACAR MENSAL
  inicializar() {
    try {
      console.log("📊 Inicializando Sistema de Placar Mensal...");

      // Verificar se existe o elemento
      const placar = document.getElementById("pontuacao-2");
      if (!placar) {
        console.warn("⚠️ Elemento #pontuacao-2 não encontrado");
        return false;
      }

      // Primeira atualização
      this.atualizarPlacarMensal();

      // Configurar intervalo de atualização (a cada 30 segundos)
      this.intervaloPlacar = setInterval(() => {
        this.atualizarPlacarMensal();
      }, 30000);

      // Interceptar mudanças no sistema principal
      this.configurarInterceptadores();

      console.log("✅ Sistema de Placar Mensal inicializado");
      return true;
    } catch (error) {
      console.error("❌ Erro ao inicializar placar mensal:", error);
      return false;
    }
  },

  // ✅ ATUALIZAR PLACAR MENSAL - USANDO MESMA LÓGICA DO PLACAR PRINCIPAL
  async atualizarPlacarMensal() {
    if (this.atualizandoAtualmente) {
      console.log("⏳ Placar mensal já sendo atualizado...");
      return;
    }

    this.atualizandoAtualmente = true;

    try {
      console.log("📊 Buscando dados do placar mensal (período: mês)...");

      // Usar mesma lógica do SistemaFiltroPeriodo - buscar dados do mês
      const formData = new FormData();
      formData.append("periodo", "mes");

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

      // Usar mesma função que o placar principal usa
      const placarData = this.extrairPlacarIgualPrincipal(html);

      if (placarData) {
        this.aplicarPlacarMensal(placarData);
        this.ultimaAtualizacao = new Date();
        console.log(
          `✅ Placar mensal atualizado: ${placarData.wins} × ${placarData.losses}`
        );
      } else {
        // Fallback: valores zerados
        this.aplicarPlacarMensal({ wins: 0, losses: 0 });
        console.log("⚠️ Nenhum dado encontrado, usando valores zero");
      }
    } catch (error) {
      console.error("❌ Erro ao atualizar placar mensal:", error);
      this.mostrarErroPlacar();
      // Em caso de erro, zerar placar
      this.aplicarPlacarMensal({ wins: 0, losses: 0 });
    } finally {
      this.atualizandoAtualmente = false;
    }
  },

  // ✅ EXTRAIR PLACAR IGUAL AO SISTEMA PRINCIPAL - CÓPIA EXATA
  extrairPlacarIgualPrincipal(html) {
    try {
      console.log(
        "🔍 Extraindo placar usando mesma lógica do sistema principal..."
      );

      // Criar elemento temporário para parsear HTML - igual ao sistema principal
      const temp = document.createElement("div");
      temp.innerHTML = html;

      // Buscar elementos #total-green-dia e #total-red-dia - igual ao sistema principal
      const totalGreenEl = temp.querySelector("#total-green-dia");
      const totalRedEl = temp.querySelector("#total-red-dia");

      if (totalGreenEl && totalRedEl) {
        const totalGreen = totalGreenEl.dataset.green || "0";
        const totalRed = totalRedEl.dataset.red || "0";

        const wins = parseInt(totalGreen, 10) || 0;
        const losses = parseInt(totalRed, 10) || 0;

        console.log(
          `✅ Dados extraídos do HTML (igual sistema principal): ${wins} × ${losses}`
        );
        return { wins, losses };
      }

      console.log(
        "⚠️ Elementos #total-green-dia ou #total-red-dia não encontrados"
      );

      // Fallback: buscar diretamente nos placares como o sistema principal faz
      const placarGreen = temp.querySelector(".placar-green");
      const placarRed = temp.querySelector(".placar-red");

      if (placarGreen && placarRed) {
        const wins = parseInt(placarGreen.textContent.trim(), 10) || 0;
        const losses = parseInt(placarRed.textContent.trim(), 10) || 0;

        console.log(
          `✅ Dados extraídos dos placares diretos: ${wins} × ${losses}`
        );
        return { wins, losses };
      }

      console.log("⚠️ Nenhum placar encontrado no HTML");
      return { wins: 0, losses: 0 };
    } catch (error) {
      console.error("❌ Erro ao extrair placar:", error);
      return { wins: 0, losses: 0 };
    }
  },

  // ✅ EXTRAIR DADOS DO PLACAR DO HTML - VERSÃO SIMPLIFICADA
  extrairDadosPlacar(html) {
    try {
      // Criar elemento temporário para parsear HTML
      const temp = document.createElement("div");
      temp.innerHTML = html;

      console.log("🔍 Buscando dados do placar no HTML retornado...");

      // MÉTODO 1: Buscar placar principal diretamente
      const placarGreen = temp.querySelector(".placar-green");
      const placarRed = temp.querySelector(".placar-red");

      if (placarGreen && placarRed) {
        const wins = parseInt(placarGreen.textContent.trim()) || 0;
        const losses = parseInt(placarRed.textContent.trim()) || 0;
        console.log(`✅ Método 1: Encontrado ${wins} × ${losses}`);
        return { wins, losses };
      }

      // MÉTODO 2: Contar mentores com Green/Red
      console.log("🔍 Método 1 falhou, tentando método 2...");
      const mentorCards = temp.querySelectorAll(".mentor-card");
      let wins = 0,
        losses = 0;

      console.log(`📊 Encontrados ${mentorCards.length} mentores`);

      mentorCards.forEach((card, index) => {
        // Buscar valores Green e Red nos mentor-cards
        const greenValues = card.querySelectorAll(
          ".value-box-green p:first-child"
        );
        const redValues = card.querySelectorAll(".value-box-red p:first-child");

        let mentorWin = false,
          mentorLoss = false;

        // Verificar valores verdes (lucros)
        greenValues.forEach((green) => {
          if (green && green.classList && green.classList.contains("green")) {
            const valor = this.extrairValorMonetario(green.textContent);
            if (valor > 0) mentorWin = true;
          }
        });

        // Verificar valores vermelhos (perdas)
        redValues.forEach((red) => {
          if (red && red.classList && red.classList.contains("red")) {
            const valor = this.extrairValorMonetario(red.textContent);
            if (valor !== 0) mentorLoss = true;
          }
        });

        // Buscar elementos com classes green/red
        const elementosGreen = card.querySelectorAll(".green p:first-child");
        const elementosRed = card.querySelectorAll(".red p:first-child");

        elementosGreen.forEach((el) => {
          const valor = this.extrairValorMonetario(el.textContent);
          if (valor > 0) mentorWin = true;
        });

        elementosRed.forEach((el) => {
          const valor = this.extrairValorMonetario(el.textContent);
          if (valor !== 0) mentorLoss = true;
        });

        if (mentorWin) wins++;
        if (mentorLoss) losses++;
      });

      console.log(`✅ Método 2: Contados ${wins} wins, ${losses} losses`);
      return { wins, losses };
    } catch (error) {
      console.error("❌ Erro ao extrair dados do placar:", error);
      return { wins: 0, losses: 0 };
    }
  },

  // ✅ EXTRAIR VALOR MONETÁRIO DE STRING
  extrairValorMonetario(texto) {
    try {
      if (!texto) return 0;

      // Remover R$, espaços e converter vírgula para ponto
      const numeroLimpo = texto
        .replace(/[R$\s]/g, "")
        .replace(",", ".")
        .replace(/[^\d.-]/g, "");

      return parseFloat(numeroLimpo) || 0;
    } catch (error) {
      return 0;
    }
  },

  // ✅ APLICAR PLACAR MENSAL NO ELEMENTO
  aplicarPlacarMensal(placarData) {
    try {
      const placarElement = document.getElementById("pontuacao-2");
      if (!placarElement) return;

      const greenSpan = placarElement.querySelector(".placar-green-2");
      const redSpan = placarElement.querySelector(".placar-red-2");

      if (greenSpan && redSpan) {
        // Aplicar valores com animação suave
        this.animarMudancaValor(greenSpan, placarData.wins);
        this.animarMudancaValor(redSpan, placarData.losses);

        // Aplicar classe de atualização
        placarElement.classList.add("placar-atualizado");
        setTimeout(() => {
          placarElement.classList.remove("placar-atualizado");
        }, 1000);
      }
    } catch (error) {
      console.error("❌ Erro ao aplicar placar:", error);
    }
  },

  // ✅ ANIMAR MUDANÇA DE VALOR
  animarMudancaValor(elemento, novoValor) {
    try {
      const valorAtual = parseInt(elemento.textContent) || 0;

      if (valorAtual !== novoValor) {
        // Atualização direta sem animação para evitar movimento
        // Pequeno timeout para permitir coalescência de múltiplas atualizações
        setTimeout(() => {
          elemento.textContent = novoValor;
        }, 10);
      }
    } catch (error) {
      console.error("❌ Erro na animação:", error);
      elemento.textContent = novoValor; // Fallback sem animação
    }
  },

  // ✅ MOSTRAR ERRO NO PLACAR
  mostrarErroPlacar() {
    try {
      const placarElement = document.getElementById("pontuacao-2");
      if (!placarElement) return;

      placarElement.classList.add("placar-erro");
      setTimeout(() => {
        placarElement.classList.remove("placar-erro");
      }, 2000);
    } catch (error) {
      console.error("❌ Erro ao mostrar erro:", error);
    }
  },

  // ✅ CONFIGURAR INTERCEPTADORES - INTEGRAÇÃO COM SISTEMA PRINCIPAL
  configurarInterceptadores() {
    try {
      // Interceptar atualizações do SistemaFiltroPeriodo
      if (
        typeof SistemaFiltroPeriodo !== "undefined" &&
        SistemaFiltroPeriodo.atualizarPlacar
      ) {
        const originalAtualizarPlacar = SistemaFiltroPeriodo.atualizarPlacar;

        SistemaFiltroPeriodo.atualizarPlacar = function () {
          // Executa função original
          originalAtualizarPlacar.call(this);

          // Atualiza placar mensal quando período for 'mes' — imediata
          if (this.periodoAtual === "mes") {
            console.log(
              "🔄 SistemaFiltroPeriodo atualizou placar do mês, sincronizando placar-2..."
            );
            // micro-delay para permitir DOM/processamento
            if (typeof PlacarMensalManager !== "undefined") {
              setTimeout(
                () => PlacarMensalManager.sincronizarComPlacarPrincipal(),
                50
              );
            }
          }
        };
      }

      // Interceptar mudanças de período
      const radiosPeriodo = document.querySelectorAll('input[name="periodo"]');
      radiosPeriodo.forEach((radio) => {
        radio.addEventListener("change", (e) => {
          console.log(
            "🔄 Período alterado, atualizando placar mensal imediatamente..."
          );
          // atualizar imediatamente com micro-delay para DOM
          setTimeout(() => this.atualizarPlacarMensal(), 50);
        });
      });

      // Interceptar função de recarregar mentores
      if (
        typeof MentorManager !== "undefined" &&
        MentorManager.recarregarMentores
      ) {
        const originalRecarregar = MentorManager.recarregarMentores;

        MentorManager.recarregarMentores = async function (...args) {
          const resultado = await originalRecarregar.apply(this, args);

          // Sempre atualizar placar mensal após recarregar mentores — imediato
          try {
            if (typeof PlacarMensalManager !== "undefined") {
              console.log(
                "🔄 Mentores recarregados, atualizando placar mensal imediatamente..."
              );
              setTimeout(() => PlacarMensalManager.atualizarPlacarMensal(), 50);
            }
          } catch (e) {}

          return resultado;
        };
      }
    } catch (error) {
      console.error("❌ Erro ao configurar interceptadores:", error);
    }

    // --- Observador genérico para mudanças que afetam o placar (debounced) ---
    try {
      const self = this;
      let moTimer = null;
      const debouncedTrigger = () => {
        if (moTimer) clearTimeout(moTimer);
        moTimer = setTimeout(() => {
          try {
            self.atualizarPlacarMensal();
          } catch (e) {}
        }, 50);
      };

      const selectors = [
        "#pontuacao-2",
        ".mentor-card",
        "#mentores",
        ".lista-dias",
        ".mentores-container",
      ];
      const mo = new MutationObserver((mutations) => {
        debouncedTrigger();
      });

      selectors.forEach((sel) => {
        document.querySelectorAll(sel).forEach((node) => {
          try {
            mo.observe(node, {
              childList: true,
              subtree: true,
              characterData: true,
            });
          } catch (e) {}
        });
      });

      // Também observar o body para capturar inserções de containers novos (leve)
      mo.observe(document.body, { childList: true, subtree: true });
    } catch (e) {
      // silencioso
    }
  },

  // ✅ NOVA FUNÇÃO: Sincronizar com placar principal quando período = mês
  sincronizarComPlacarPrincipal() {
    try {
      const placarGreen = document.querySelector(".placar-green");
      const placarRed = document.querySelector(".placar-red");

      if (placarGreen && placarRed) {
        const wins = parseInt(placarGreen.textContent.trim(), 10) || 0;
        const losses = parseInt(placarRed.textContent.trim(), 10) || 0;

        console.log(
          `📊 Sincronizando placar-2 com placar principal: ${wins} × ${losses}`
        );
        this.aplicarPlacarMensal({ wins, losses });

        return true;
      }

      return false;
    } catch (error) {
      console.error("❌ Erro ao sincronizar com placar principal:", error);
      return false;
    }
  },

  // ✅ PARAR SISTEMA
  parar() {
    try {
      if (this.intervaloPlacar) {
        clearInterval(this.intervaloPlacar);
        this.intervaloPlacar = null;
        console.log("🛑 Sistema de placar mensal parado");
      }
    } catch (error) {
      console.error("❌ Erro ao parar sistema:", error);
    }
  },

  // ✅ FORÇAR ATUALIZAÇÃO
  forcarAtualizacao() {
    this.atualizandoAtualmente = false;
    return this.atualizarPlacarMensal();
  },

  // ✅ STATUS DO SISTEMA
  status() {
    return {
      ativo: !!this.intervaloPlacar,
      atualizando: this.atualizandoAtualmente,
      ultimaAtualizacao: this.ultimaAtualizacao,
      elementoExiste: !!document.getElementById("pontuacao-2"),
      intervaloAtivo: !!this.intervaloPlacar,
    };
  },
};

// ========================================
// 🎨 CSS CLONADO E ADAPTADO PARA PLACAR-2
// ========================================

const cssPlaccar2 = `
/* ===== PLACAR-2 - CLONE DO PLACAR ORIGINAL ===== */
.area-central-2 {
  position: absolute;
  left: 46.5%;
  top: 249px;
  transform: translate(-50%, -50%);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: clamp(12px, 3.5vw, 16px);
  font-weight: 400;
  color: #576574;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.pontuacao-2 {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: clamp(5px, 1.2vw, 20px); /* pequeno gap para proximidade */
  color: white;
  font-size: clamp(15px, 3.5vw, 22px); /* um pouco menor */
  font-weight: 700 !important; /* manter grosso e forçar override */
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
}

.placar-green-2 {
  color: #03a158;
  font-weight: 700 !important; /* manter grosso e forçar override */
  font-size: inherit !important;
}

.placar-red-2 {
  color: #e93a3a;
  font-weight: 700 !important; /* manter grosso e forçar override */
  font-size: inherit !important;
}

.separador-2 {
  color: rgba(109, 107, 107, 0.95);
  font-size: clamp(12px, 2.5vw, 16px);
  font-weight: 400;
  margin: 0 clamp(1px, 0.4vw, 3px); /* margem menor para mais proximidade */
}

/* Specific override using ID to beat other !important rules */
#pontuacao-2.pontuacao-2,
#pontuacao-2.pontuacao-2 .placar-green-2,
#pontuacao-2.pontuacao-2 .placar-red-2 {
  font-weight: 700 !important;
}

/* ===== EFEITOS DE ATUALIZAÇÃO REMOVIDOS ===== */
/* .placar-atualizado .placar-green-2,
.placar-atualizado .placar-red-2 {
  text-shadow: 0 0 10px currentColor;
  animation: placar-pulse 0.6s ease-out;
}

.placar-erro {
  opacity: 0.5;
  animation: placar-erro-shake 0.5s ease-in-out;
} */

/* ===== ANIMAÇÕES REMOVIDAS ===== */
/* @keyframes placar-pulse {
  0% { transform: scale(1); }
  50% { transform: scale(1.1); }
  100% { transform: scale(1); }
}

@keyframes placar-erro-shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-2px); }
  75% { transform: translateX(2px); }
} */

/* ===== RESPONSIVIDADE ===== */
@media (max-width: 768px) {
  .area-central-2 {
    font-size: clamp(10px, 2vw, 14px);
  }

  .pontuacao-2 {
    gap: clamp(6px, 1.5vw, 12px);
    font-size: clamp(14px, 3.5vw, 20px);
  }

  .separador-2 {
    font-size: clamp(12px, 2.5vw, 16px);
    margin: 0 clamp(3px, 0.8vw, 6px);
  }
}

@media (max-width: 480px) {
  .area-central-2 {
    font-size: clamp(9px, 1.8vw, 12px);
  }

  .pontuacao-2 {
    gap: clamp(4px, 1vw, 8px);
    font-size: clamp(12px, 3vw, 16px);
  }

  .separador-2 {
    font-size: clamp(10px, 2vw, 14px);
    margin: 0 clamp(2px, 0.5vw, 4px);
  }
}
`;

// ========================================
// 🔧 FUNÇÕES AUXILIARES E INTEGRAÇÃO
// ========================================

// Injetar CSS no documento
function injetarCSS() {
  try {
    const styleElement = document.createElement("style");
    styleElement.textContent = cssPlaccar2;
    document.head.appendChild(styleElement);
    console.log("✅ CSS do placar mensal injetado");
  } catch (error) {
    console.error("❌ Erro ao injetar CSS:", error);
  }
}

// Função global para teste rápido do placar mensal
window.testarPlacarMensal = () => {
  console.log("🧪 Testando placar mensal...");

  const placar = document.getElementById("pontuacao-2");
  if (!placar) {
    console.error("❌ Elemento #pontuacao-2 não encontrado!");
    return false;
  }

  console.log("✅ Elemento encontrado:", placar);

  // Teste visual rápido
  const green = placar.querySelector(".placar-green-2");
  const red = placar.querySelector(".placar-red-2");

  if (green && red) {
    green.textContent = Math.floor(Math.random() * 10) + 1;
    red.textContent = Math.floor(Math.random() * 10) + 1;
    console.log(
      `✅ Valores de teste aplicados: ${green.textContent} × ${red.textContent}`
    );

    // Forçar atualização real após teste
    setTimeout(() => {
      if (typeof PlacarMensalManager !== "undefined") {
        PlacarMensalManager.atualizarPlacarMensal();
      }
    }, 2000);

    return true;
  }

  console.error("❌ Elementos internos não encontrados");
  return false;
};

// Função global para controle do placar mensal
window.PlacarMensal = {
  iniciar: () => {
    console.log("🚀 Iniciando placar mensal...");
    return PlacarMensalManager.inicializar();
  },
  parar: () => {
    console.log("🛑 Parando placar mensal...");
    return PlacarMensalManager.parar();
  },
  atualizar: () => {
    console.log("🔄 Atualizando placar mensal...");
    return PlacarMensalManager.forcarAtualizacao();
  },
  status: () => PlacarMensalManager.status(),
  info: () => {
    const status = PlacarMensalManager.status();
    console.log("📊 Status Placar Mensal:", status);
    return status;
  },
  teste: () => testarPlacarMensal(),
};

// ========================================
// 🚀 INICIALIZAÇÃO AUTOMÁTICA
// ========================================

function inicializarPlacarMensal() {
  try {
    console.log("🚀 Inicializando Sistema de Placar Mensal...");

    // Injetar CSS
    injetarCSS();

    // Aguardar elemento estar disponível
    const verificarElemento = () => {
      const placar = document.getElementById("pontuacao-2");
      if (placar) {
        PlacarMensalManager.inicializar();
        console.log("✅ Sistema de Placar Mensal inicializado com sucesso!");
      } else {
        console.log("⏳ Aguardando elemento #pontuacao-2...");
        setTimeout(verificarElemento, 1000);
      }
    };

    verificarElemento();
  } catch (error) {
    console.error("❌ Erro na inicialização do placar mensal:", error);
  }
}

// Aguardar DOM
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", () => {
    setTimeout(inicializarPlacarMensal, 1000);
  });
} else {
  setTimeout(inicializarPlacarMensal, 500);
}

// ========================================
// 📝 COMANDOS DE CONSOLE PARA DEBUG
// ========================================

console.log("📊 Sistema de Placar Mensal carregado!");
console.log("🔧 Comandos disponíveis:");
console.log("  PlacarMensal.iniciar() - Iniciar sistema");
console.log("  PlacarMensal.parar() - Parar sistema");
console.log("  PlacarMensal.atualizar() - Forçar atualização");
console.log("  PlacarMensal.status() - Ver status");
console.log("  PlacarMensal.info() - Informações detalhadas");

// Export para uso externo
window.PlacarMensalManager = PlacarMensalManager;
// ========================================================================================================================
//                                         FIM JS DO PLACAR DO BLOCO 2 MÊS
// ========================================================================================================================
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
// ========================================================================================================================
//                     CARREGA OS DADOS DOS VALORES DE ( DATA - PLACAR - SALDO ) VIA AJAX IMEDIATO
// ========================================================================================================================

const ListaDiasManagerCorrigido = {
  // Controle de estado
  atualizandoAtualmente: false,
  intervaloAtualizacao: null,
  ultimaAtualizacao: null,
  hashUltimosDados: "",
  metaAtual: 0,
  periodoAtual: "dia",

  // Configurações
  INTERVALO_MS: 3000, // Atualiza a cada 3 segundos
  TIMEOUT_MS: 5000,

  // Inicializar sistema
  inicializar() {
    console.log(
      "🚀 Inicializando sistema corrigido de atualização da lista de dias..."
    );

    // Detectar meta inicial
    this.detectarMetaEPeriodo();

    // Primeira atualização imediata
    this.atualizarListaDias();

    // Configurar intervalo de atualização
    this.intervaloAtualizacao = setInterval(() => {
      this.atualizarListaDias();
    }, this.INTERVALO_MS);

    // Configurar interceptadores de eventos
    this.configurarInterceptadores();

    // Configurar observador sanitizador para evitar reaplicação de estilos/ícones
    try {
      this.configurarObservadorSanitizacao();
    } catch (e) {}

    // One-time hard cleanup: remove any inline styles left on existing .gd-linha-dia
    // e garantir que a flag CSS que força largura fixa seja aplicada.
    try {
      document
        .querySelectorAll(
          ".lista-dias .gd-linha-dia, .lista-dias .gd-linha-dia .data"
        )
        .forEach((el) => {
          if (el.hasAttribute("style")) el.removeAttribute("style");
        });
      // Aplicar classe global para regras CSS de alta prioridade
      document.documentElement.classList.add("force-data-fixed");
    } catch (e) {}

    console.log("✅ Sistema corrigido ativo!");
  },

  // Detectar meta e período atual
  detectarMetaEPeriodo() {
    try {
      const dadosInfo = document.getElementById("dados-mes-info");
      if (dadosInfo) {
        this.periodoAtual = dadosInfo.dataset.periodoAtual || "dia";

        switch (this.periodoAtual) {
          case "mes":
            this.metaAtual = parseFloat(dadosInfo.dataset.metaMensal) || 0;
            break;
          case "ano":
            this.metaAtual = parseFloat(dadosInfo.dataset.metaAnual) || 0;
            break;
          default:
            this.metaAtual = parseFloat(dadosInfo.dataset.metaDiaria) || 0;
        }
      }

      // Fallback: tentar detectar do radio button
      const radioSelecionado = document.querySelector(
        'input[name="periodo"]:checked'
      );
      if (radioSelecionado) {
        this.periodoAtual = radioSelecionado.value;
      }

      console.log(
        `Meta detectada: R$ ${this.metaAtual.toFixed(2)} (${this.periodoAtual})`
      );
    } catch (error) {
      console.error("Erro ao detectar meta:", error);
      this.metaAtual = 0;
    }
  },

  // Atualização principal
  async atualizarListaDias() {
    if (this.atualizandoAtualmente) return;

    this.atualizandoAtualmente = true;

    try {
      const response = await fetch("obter_dados_mes.php", {
        method: "GET",
        headers: {
          "Cache-Control": "no-cache",
          "X-Requested-With": "XMLHttpRequest",
        },
        signal: AbortSignal.timeout(this.TIMEOUT_MS),
      });

      if (!response.ok) throw new Error(`HTTP ${response.status}`);

      const dados = await response.json();

      // Verificar se houve mudança
      const hashAtual = this.gerarHashDados(dados);
      if (hashAtual === this.hashUltimosDados) {
        return; // Sem mudanças
      }

      this.hashUltimosDados = hashAtual;

      // Renderizar todos os dias do mês
      this.renderizarMesCompleto(dados);

      this.ultimaAtualizacao = new Date();
      console.log(
        "✅ Lista atualizada:",
        this.ultimaAtualizacao.toLocaleTimeString()
      );
    } catch (error) {
      console.error("❌ Erro na atualização:", error);
    } finally {
      this.atualizandoAtualmente = false;
    }
  },

  // Renderizar mês completo
  renderizarMesCompleto(responseData) {
    const container = document.querySelector(".lista-dias");
    if (!container) return;

    // Preservar posição do scroll
    const scrollTop = container.scrollTop;

    // Mapear estado atual de troféus para evitar flicker ao re-renderizar
    const metaExistenteMap = {};
    container.querySelectorAll(".gd-linha-dia").forEach((el) => {
      const date = el.getAttribute("data-date");
      if (date) {
        metaExistenteMap[date] = el.getAttribute("data-meta-batida") === "true";
      }
    });

    // Dados do response
    const dados = responseData.dados || {};
    const mes = responseData.mes || new Date().getMonth() + 1;
    const ano = responseData.ano || new Date().getFullYear();
    const diasNoMes =
      responseData.dias_no_mes || new Date(ano, mes, 0).getDate();

    // Data de hoje
    const hoje = this.obterDataHoje();

    // Limpar container preservando altura para evitar flicker
    const prevMinHeight = container.style.minHeight;
    // Fixar a altura atual do container para evitar colapso visual durante o rebuild
    container.style.minHeight = container.clientHeight + "px";
    container.innerHTML = "";

    // Gerar todos os dias do mês
    const fragment = document.createDocumentFragment();

    for (let dia = 1; dia <= diasNoMes; dia++) {
      const diaStr = dia.toString().padStart(2, "0");
      const mesStr = mes.toString().padStart(2, "0");
      const data_mysql = `${ano}-${mesStr}-${diaStr}`;
      const data_exibicao = `${diaStr}/${mesStr}/${ano}`;

      // Dados do dia (ou padrão se não existir)
      const dadosDia = dados[data_mysql] || {
        total_valor_green: 0,
        total_valor_red: 0,
        total_green: 0,
        total_red: 0,
      };

      // Calcular saldo
      const saldo_dia =
        parseFloat(dadosDia.total_valor_green) -
        parseFloat(dadosDia.total_valor_red);
      const saldo_formatado = saldo_dia.toLocaleString("pt-BR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      });

      // Verificar meta batida (CORRIGIDO)
      const metaBatida = this.metaAtual > 0 && saldo_dia >= this.metaAtual;

      // Classes e estilos
      const cor_valor =
        saldo_dia === 0
          ? "texto-cinza"
          : saldo_dia > 0
          ? "verde-bold"
          : "vermelho-bold";
      const classe_texto = saldo_dia === 0 ? "texto-cinza" : "";
      const placar_cinza =
        parseInt(dadosDia.total_green) === 0 &&
        parseInt(dadosDia.total_red) === 0
          ? "texto-cinza"
          : "";

      // Classes do dia (prefixadas com gd- para evitar conflito)
      const classes = ["gd-linha-dia"];
      // Adicionar classe de valor persistente para evitar flicker entre re-renders
      if (saldo_dia > 0) {
        classes.push("valor-positivo");
      } else if (saldo_dia < 0) {
        classes.push("valor-negativo");
      } else {
        classes.push("valor-zero");
      }

      if (data_mysql === hoje) {
        classes.push("gd-dia-hoje");
        classes.push(saldo_dia >= 0 ? "gd-borda-verde" : "gd-borda-vermelha");
      } else {
        classes.push("dia-normal");
      }

      // Destaque para dias passados
      if (data_mysql < hoje) {
        if (saldo_dia > 0) {
          classes.push("gd-dia-destaque");
        } else if (saldo_dia < 0) {
          classes.push("gd-dia-destaque-negativo");
        }

        if (
          parseInt(dadosDia.total_green) === 0 &&
          parseInt(dadosDia.total_red) === 0
        ) {
          classes.push("gd-dia-sem-valor");
        }
      }

      // Dias futuros
      if (data_mysql > hoje) {
        classes.push("dia-futuro");
      }

      // Ícone baseado na meta. Se havia troféu aplicado anteriormente, respeitar esse estado
      const finalMetaBatida = metaBatida || !!metaExistenteMap[data_mysql];
      const iconeClasse = finalMetaBatida
        ? "fa-trophy trofeu-icone"
        : "fa-check";
      const iconeClassesFull = `fa-solid ${iconeClasse}`;

      // Criar elemento
      const divDia = document.createElement("div");
      divDia.className = classes.join(" ");
      divDia.setAttribute("data-date", data_mysql);
      // Usar finalMetaBatida (que respeita estado anterior) para evitar flicker do ícone
      divDia.setAttribute(
        "data-meta-batida",
        finalMetaBatida ? "true" : "false"
      );

      divDia.innerHTML = `
        <span class="data ${classe_texto}">
          ${data_exibicao}
        </span>
        <div class="placar-dia">
          <span class="placar verde-bold ${placar_cinza}">${parseInt(
        dadosDia.total_green
      )}</span>
          <span class="placar separador ${placar_cinza}">x</span>
          <span class="placar vermelho-bold ${placar_cinza}">${parseInt(
        dadosDia.total_red
      )}</span>
        </div>
        <span class="valor ${cor_valor}">R$ ${saldo_formatado}</span>
        <span class="icone ${classe_texto}">
          <i class="${iconeClassesFull}"></i>
        </span>
      `;

      fragment.appendChild(divDia);
    }

    // Adicionar ao container
    container.appendChild(fragment);

    // Defensive cleanup: garantir que a coluna .data não receba estilos inline
    // ou ícones indesejados reaplicados por outros scripts. Remove atributos
    // style e qualquer <i> dentro de .data, e garante min/max-width corretos.
    try {
      if (typeof this.sanitizeDataCells === "function") {
        this.sanitizeDataCells();
      }
    } catch (e) {
      // silencioso
    }

    // Restaurar scroll e restaurar min-height preservado
    container.scrollTop = scrollTop;
    container.style.minHeight = prevMinHeight || "";

    // Focar no dia atual se primeira vez
    if (!this.ultimaAtualizacao) {
      setTimeout(() => this.focarDiaAtual(), 500);
    }

    // Disparar evento
    window.dispatchEvent(
      new CustomEvent("listaDiasAtualizada", {
        detail: { dados: responseData, timestamp: new Date() },
      })
    );
  },

  // Remove inline styles and unwanted icons inside .data cells to prevent
  // other scripts from shifting layout after render. This is defensive and
  // idempotent.
  sanitizeDataCells() {
    try {
      document
        .querySelectorAll(".lista-dias .gd-linha-dia .data")
        .forEach((el) => {
          // Remover estilos inline que possam alterar largura/alinhamento
          if (el.hasAttribute("style")) el.removeAttribute("style");

          // Forçar classes/atributos que garantem largura fixa
          el.style.minWidth = "";
          el.style.maxWidth = "";

          // Remover qualquer <i> que represente calendário (defensivo)
          el.querySelectorAll("i").forEach((icon) => {
            const cls = (icon.className || "").toLowerCase();
            if (
              cls.includes("calendar") ||
              cls.includes("fa-calendar") ||
              cls.includes("fa-calendar-day") ||
              cls.includes("fa-calendar-alt")
            ) {
              icon.remove();
            }
          });
        });
    } catch (e) {
      // silencioso
    }
  },

  // Observador defensivo: observa inserções dentro de .lista-dias e
  // remove rapidamente quaisquer inline styles ou ícones que reapareçam.
  configurarObservadorSanitizacao() {
    try {
      const container = document.querySelector(".lista-dias");
      if (!container) return;

      const mo = new MutationObserver((mutations) => {
        let precisa = false;
        for (const m of mutations) {
          if (m.type === "childList" || m.type === "attributes") {
            precisa = true;
            break;
          }
        }
        if (precisa) {
          // Debounce curto
          clearTimeout(this._sanitizeTimer);
          this._sanitizeTimer = setTimeout(() => this.sanitizeDataCells(), 40);
        }
      });

      mo.observe(container, {
        childList: true,
        subtree: true,
        attributes: true,
      });
      // armazenar referência caso precise parar
      this._sanitizerObserver = mo;
    } catch (e) {}
  },

  // Obter data de hoje
  obterDataHoje() {
    const d = new Date();
    const yy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, "0");
    const dd = String(d.getDate()).padStart(2, "0");
    return `${yy}-${mm}-${dd}`;
  },

  // Focar no dia atual
  focarDiaAtual() {
    const hoje = this.obterDataHoje();
    const diaHoje = document.querySelector(`[data-date="${hoje}"]`);

    if (diaHoje) {
      const container = document.querySelector(".lista-dias");
      if (container) {
        const containerHeight = container.clientHeight;
        const elementTop = diaHoje.offsetTop;
        const elementHeight = diaHoje.offsetHeight;

        const scrollPosition =
          elementTop - containerHeight / 2 + elementHeight / 2;

        container.scrollTo({
          top: Math.max(0, scrollPosition),
          behavior: "smooth",
        });
      }

      // Adicionar classe de destaque temporário
      diaHoje.classList.add("dia-foco");
      setTimeout(() => {
        diaHoje.classList.remove("dia-foco");
      }, 2000);
    }
  },

  // Hash dos dados
  gerarHashDados(dados) {
    return JSON.stringify(dados);
  },

  // Configurar interceptadores
  configurarInterceptadores() {
    // Interceptar submissão de formulários
    document.addEventListener("submit", (e) => {
      const form = e.target;
      if (
        form.id === "form-mentor" ||
        form.classList.contains("formulario-mentor")
      ) {
        setTimeout(() => {
          this.atualizandoAtualmente = false;
          this.atualizarListaDias();
        }, 300);
      }
    });

    // Interceptar cliques em botões
    document.addEventListener("click", (e) => {
      if (
        e.target.matches('button[type="submit"], .btn-enviar, .btn-confirmar')
      ) {
        setTimeout(() => {
          this.atualizandoAtualmente = false;
          this.atualizarListaDias();
        }, 300);
      }
    });

    // Interceptar mudanças no filtro de período
    document.querySelectorAll('input[name="periodo"]').forEach((radio) => {
      radio.addEventListener("change", (e) => {
        if (e.target.checked) {
          this.periodoAtual = e.target.value;
          this.detectarMetaEPeriodo();
          this.atualizandoAtualmente = false;
          this.atualizarListaDias();
        }
      });
    });

    // Hook no fetch
    const originalFetch = window.fetch;
    window.fetch = async function (...args) {
      const response = await originalFetch.apply(this, args);

      const url = args[0]?.toString() || "";
      if (
        url.includes("cadastrar-valor") ||
        url.includes("excluir-entrada") ||
        url.includes("dados_banca")
      ) {
        setTimeout(() => {
          if (typeof ListaDiasManagerCorrigido !== "undefined") {
            ListaDiasManagerCorrigido.atualizandoAtualmente = false;
            ListaDiasManagerCorrigido.atualizarListaDias();
          }
        }, 200);
      }

      return response;
    };

    // Eventos customizados
    window.addEventListener("metaAtualizada", () => {
      this.detectarMetaEPeriodo();
      this.atualizarListaDias();
    });

    window.addEventListener("mentoresAtualizados", () => {
      this.atualizarListaDias();
    });
  },

  // Parar sistema
  parar() {
    if (this.intervaloAtualizacao) {
      clearInterval(this.intervaloAtualizacao);
      this.intervaloAtualizacao = null;
      console.log("🛑 Sistema parado");
    }
  },

  // Forçar atualização
  forcarAtualizacao() {
    this.atualizandoAtualmente = false;
    this.detectarMetaEPeriodo();
    return this.atualizarListaDias();
  },

  // Status do sistema
  status() {
    return {
      ativo: !!this.intervaloAtualizacao,
      atualizando: this.atualizandoAtualmente,
      ultimaAtualizacao: this.ultimaAtualizacao,
      metaAtual: this.metaAtual,
      periodoAtual: this.periodoAtual,
    };
  },
};

// ========================================================================================================================
//                                    INTEGRAÇÃO COM SISTEMA EXISTENTE
// ========================================================================================================================

// Substituir o sistema anterior se existir
if (typeof ListaDiasRealtimeManager !== "undefined") {
  // Parar sistema antigo
  if (ListaDiasRealtimeManager.intervaloAtualizacao) {
    clearInterval(ListaDiasRealtimeManager.intervaloAtualizacao);
  }
  console.log("Sistema anterior parado, substituindo...");
}

// Aguardar DOM carregar
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", () => {
    setTimeout(() => {
      ListaDiasManagerCorrigido.inicializar();
    }, 1000);
  });
} else {
  setTimeout(() => {
    ListaDiasManagerCorrigido.inicializar();
  }, 500);
}

// ========================================================================================================================
//                                        COMANDOS GLOBAIS
// ========================================================================================================================

window.ListaDiasCorrigido = {
  parar: () => ListaDiasManagerCorrigido.parar(),
  iniciar: () => ListaDiasManagerCorrigido.inicializar(),
  atualizar: () => ListaDiasManagerCorrigido.forcarAtualizacao(),
  status: () => ListaDiasManagerCorrigido.status(),
  focar: () => ListaDiasManagerCorrigido.focarDiaAtual(),
};

// Substituir comandos antigos
window.ListaDias = window.ListaDiasCorrigido;

console.log("📅 Sistema corrigido da lista de dias carregado!");
console.log("🔧 Correções aplicadas:");
console.log("  ✅ Exibe TODOS os dias do mês");
console.log("  ✅ Atualização em tempo real funcionando");
console.log("  ✅ Ícone de troféu para meta batida");
console.log("  ✅ Detecção automática da meta atual");
console.log(
  "Comandos: ListaDias.atualizar(), ListaDias.status(), ListaDias.focar()"
);

// ========================================================================================================================
//                   FIM CARREGA OS DADOS DOS VALORES DE ( DATA - PLACAR - SALDO ) VIA AJAX IMEDIATO
// ========================================================================================================================
//
//
//
//
//
//
//
//
// ========================================================================================================================
//                                  TROFÉU - PARA APARECER  QUANDO A META É BATIDA
// ========================================================================================================================

// SISTEMA COMPLETO DE TROFÉU - LIMPEZA + FUNCIONALIDADE BASEADA NO RÓTULO
(function () {
  "use strict";

  console.log("🚀 Iniciando Sistema Completo de Troféu...");

  // Debounce por linha para evitar flicker ao aplicar/remover troféus
  const debounceTrofeuMs = 400; // ajuste finamente se necessário
  const ultimoTrofeuChange = new Map(); // key: data-date -> timestamp

  function podeAplicarTrofeu(linha) {
    if (!linha) return true;
    const key =
      linha.getAttribute("data-date") || linha.dataset.date || "_global";
    const now = Date.now();
    const last = ultimoTrofeuChange.get(key) || 0;
    if (now - last < debounceTrofeuMs) return false;
    // reservar timestamp para evitar concorrência imediata
    ultimoTrofeuChange.set(key, now);
    return true;
  }

  function marcarRemocaoTrofeu(linha) {
    if (!linha) return;
    const key =
      linha.getAttribute("data-date") || linha.dataset.date || "_global";
    ultimoTrofeuChange.set(key, Date.now());
  }

  // ========================================
  // FASE 1: LIMPEZA COMPLETA DO SISTEMA
  // ========================================

  function limpezaCompleta() {
    console.log("🛑 FASE 1: Limpeza completa de sistemas anteriores...");

    // 1. Parar TODOS os intervalos e timeouts
    const maxId = setTimeout(() => {}, 0);
    for (let i = 1; i <= maxId; i++) {
      clearInterval(i);
      clearTimeout(i);
    }

    // 2. Desconectar observers existentes
    if (window.trofeuObserver) window.trofeuObserver.disconnect();
    if (window.MutationObserver) {
      document.querySelectorAll("*").forEach((el) => {
        if (el._observer) el._observer.disconnect();
      });
    }

    // 3. Limpar listeners problemáticos (APENAS elementos relacionados a troféu)
    // Não remover listeners dos cards de mentores
    const elementosTrofeu = document.querySelectorAll(
      '.gd-linha-dia, .lista-dias, [class*="trofeu"]'
    );
    elementosTrofeu.forEach((el) => {
      const clone = el.cloneNode(true);
      if (el.parentNode) {
        el.parentNode.replaceChild(clone, el);
      }
    });

    // 4. Sobrescrever fetch para controlar callbacks
    const originalFetch = window.fetch;
    window.fetch = function (...args) {
      return originalFetch.apply(this, args);
    };

    // 5. Remover troféus com força máxima
    function removerTodosTrofeus() {
      document.querySelectorAll(".gd-linha-dia").forEach((linha) => {
        const icone = linha.querySelector(".icone i");
        if (icone) {
          icone.className = "fa-solid fa-check";
          icone.style.cssText =
            'color: #64748b !important; font-family: "Font Awesome 6 Free" !important; font-weight: 900 !important;';
          icone.innerHTML = "";
          linha.setAttribute("data-meta-batida", "false");
        }
      });
    }

    removerTodosTrofeus();

    // 5.1. Injetar CSS para manter troféus pequenos com posição ajustável
    const cssFixo = document.createElement("style");
    cssFixo.setAttribute("data-trofeu-fixo", "true");
    cssFixo.textContent = `
            .gd-linha-dia .icone i.fa-trophy {
                font-size: 12px !important;
                width: 12px !important;
                height: 12px !important;
                line-height: 12px !important;
                color: #FFD700 !important;
                margin-top: -3px !important;
                display: inline-block !important;
            }
            .trofeu-icone {
                font-size: 12px !important;
                width: 12px !important;
                height: 12px !important;
                line-height: 12px !important;
                color: #FFD700 !important;
                margin-top: -3px !important;
                display: inline-block !important;
            }
        `;
    document.head.appendChild(cssFixo);

    // 6. Criar proteção contra troféus indesejados
    // Proteção temporária contra troféus mal aplicados.
    // Observador ignora troféus que foram explicitamente permitidos (data-trofeu-permitido)
    // e será desconectado automaticamente após um curto período para evitar conflitos
    const protecao = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        if (mutation.type === "attributes" || mutation.type === "childList") {
          const elemento = mutation.target;
          try {
            const linha = elemento.closest && elemento.closest(".gd-linha-dia");
            // Se a linha sinalizou que o troféu é permitido, não interferir
            if (
              linha &&
              linha.dataset &&
              (linha.dataset.trofeuPermitido === "1" ||
                linha.dataset.trofeuPreservado === "1" ||
                linha.getAttribute("data-meta-batida") === "true")
            ) {
              return;
            }

            if (
              elemento.classList &&
              elemento.classList.contains("fa-trophy")
            ) {
              elemento.className = "fa-solid fa-check";
              elemento.style.cssText = "color: #64748b !important;";
            }
          } catch (e) {
            // silencioso
          }
        }
      });
    });

    document.querySelectorAll(".gd-linha-dia .icone i").forEach((icone) => {
      protecao.observe(icone, {
        attributes: true,
        attributeFilter: ["class", "style"],
      });
    });

    // Desconectar proteção após tempo curto (permite troféus legítimos aplicarem sem flicker)
    setTimeout(() => {
      try {
        protecao.disconnect();
      } catch (e) {}
    }, 1200);

    console.log("✅ Limpeza completa finalizada");
    return true;
  }

  // ========================================
  // FASE 2: SISTEMA BASEADO NO RÓTULO
  // ========================================

  const SistemaTrofeuFinal = {
    ativo: false,
    ultimaVerificacao: "",
    observer: null,
    intervaloPrincipal: null,

    inicializar() {
      console.log("🏆 FASE 2: Iniciando sistema baseado no rótulo...");

      this.ativo = true;
      this.configurarMonitoramento();
      this.verificarEAplicar();

      console.log("✅ Sistema final ativo - monitora apenas rótulo da meta");
    },

    async verificarEAplicar() {
      if (!this.ativo) return;

      // utilitário local para converter 'R$ 1.234,56' em number
      const parseBRL = (txt) => {
        if (!txt) return 0;
        try {
          return (
            parseFloat(
              String(txt)
                .replace(/\s/g, "")
                .replace(/R\$/g, "")
                .replace(/\./g, "")
                .replace(/,/g, ".")
            ) || 0
          );
        } catch (e) {
          return 0;
        }
      };

      try {
        // Obter elemento do dia atual
        const hoje = this.obterDataHoje();
        const elementoHoje = document.querySelector(`[data-date="${hoje}"]`);

        if (!elementoHoje) {
          // se não há o dia atual no DOM, remover troféus por segurança
          this.garantirSemTrofeus();
          return;
        }

        // Se o MetaDiariaManager estiver disponível, usar o cálculo canônico
        if (typeof MetaDiariaManager !== "undefined") {
          try {
            // Atualiza/obtém dados processados (usa aplicarAjustePeriodo internamente)
            const dados = await MetaDiariaManager.atualizarMetaDiaria(true);

            if (dados) {
              const saldoDia = parseFloat(dados.lucro) || 0;
              const metaCalculada = parseFloat(dados.meta_display) || 0;
              const bancaTotal = parseFloat(dados.banca) || 0;

              const resultado = MetaDiariaManager.calcularMetaFinalComExtra(
                saldoDia,
                metaCalculada,
                bancaTotal,
                dados
              );

              // Decisão numérica: garantir comparação exata (evita depender de statusClass)
              const batida =
                (metaCalculada === 0 && saldoDia > 0) ||
                (metaCalculada > 0 && saldoDia >= metaCalculada);

              console.log(
                `🔍 Verificação via MetaDiariaManager: período=${
                  dados.periodo_ativo || MetaDiariaManager.periodoAtual
                }, status=${resultado.statusClass}, metaFinal=${
                  resultado.metaFinal
                }, valorExtra=${resultado.valorExtra}`
              );

              if (batida) this.aplicarTrofeuDiaAtual();
              else this.garantirSemTrofeus();

              return;
            }
          } catch (e) {
            console.warn(
              "⚠️ Falha ao obter cálculo do MetaDiariaManager, fallback para DOM:",
              e
            );
            // cair para o fallback abaixo
          }
        }

        // Fallback: cálculo baseado no DOM (compatibilidade)
        const valorEl = elementoHoje.querySelector(".valor");
        const saldoHoje = parseBRL(valorEl ? valorEl.textContent : "0");

        // Determinar meta a partir de várias fontes (dados-mes-info ou elementos visuais)
        let periodo = "dia";
        let meta = 0;

        const dadosMesEl = document.getElementById("dados-mes-info");
        if (dadosMesEl) {
          periodo =
            dadosMesEl.dataset.periodoAtual ||
            dadosMesEl.dataset.periodo ||
            periodo;
        }

        if (dadosMesEl) {
          const mdia = parseBRL(
            dadosMesEl.dataset.metaDiaria || dadosMesEl.dataset.meta || "0"
          );
          const mmes = parseBRL(
            dadosMesEl.dataset.metaMensal || dadosMesEl.dataset.metaMes || "0"
          );
          const mano = parseBRL(
            dadosMesEl.dataset.metaAnual || dadosMesEl.dataset.metaAno || "0"
          );

          if (periodo === "mes") meta = mmes || mdia || 0;
          else if (periodo === "ano") meta = mano || mdia || 0;
          else meta = mdia || 0;
        }

        // Fallback visual
        if (!meta || meta === 0) {
          const metaVisivel = document.querySelector(
            "#meta-valor .valor-texto, #valor-texto-meta, .widget-meta-valor .valor-texto"
          );
          if (metaVisivel)
            meta = parseBRL(metaVisivel.textContent || metaVisivel.innerText);
        }

        // Regra: se meta === 0 e existe saldo positivo, considerar meta batida
        const metaBatida =
          (meta === 0 && saldoHoje > 0) || (meta > 0 && saldoHoje >= meta);

        console.log(
          `🔍 Fallback verificação por DOM: período=${periodo}, meta=${meta.toFixed(
            2
          )}, saldoHoje=${saldoHoje.toFixed(2)}, batida=${metaBatida}`
        );

        if (metaBatida) this.aplicarTrofeuDiaAtual();
        else this.garantirSemTrofeus();
      } catch (error) {
        console.error("❌ Erro na verificação de troféu por cálculo:", error);
        this.garantirSemTrofeus();
      }
    },

    interpretarRotulo(rotuloTexto) {
      if (!rotuloTexto) return false;

      // Palavras que indicam meta NÃO atingida
      const indicadoresNaoAtingida = [
        "restando",
        "restam",
        "faltam",
        "falta",
        "para meta",
        "p/ meta",
        "ainda",
        "necessário",
        "precisam",
        "precisa",
        "restante",
        "pendente",
      ];

      // Se contém indicador de "ainda faltando"
      const temIndicadorFaltando = indicadoresNaoAtingida.some((palavra) =>
        rotuloTexto.includes(palavra)
      );

      if (temIndicadorFaltando) {
        console.log("❌ Rótulo indica que falta para a meta");
        return false;
      }

      // Palavras que indicam meta ATINGIDA
      const indicadoresBatida = [
        "batida",
        "atingida",
        "alcançada",
        "superada",
        "parabéns",
        "sucesso",
        "completa",
        "conquistada",
        "objetivo alcançado",
        "meta completa",
      ];

      const temIndicadorBatida = indicadoresBatida.some((palavra) =>
        rotuloTexto.includes(palavra)
      );

      if (temIndicadorBatida) {
        console.log("✅ Rótulo confirma meta batida");
        return true;
      }

      // Padrão: se não há indicadores claros, assumir NÃO batida
      console.log("❓ Rótulo sem indicadores - assumindo meta NÃO batida");
      return false;
    },

    async aplicarTrofeuDiaAtual() {
      const hoje = this.obterDataHoje();
      const elementoHoje = document.querySelector(`[data-date="${hoje}"]`);

      if (!elementoHoje) {
        console.log(`⚠️ Elemento do dia atual (${hoje}) não encontrado`);
        return;
      }

      const iconeHoje = elementoHoje.querySelector(".icone i");
      if (!iconeHoje) {
        console.log("⚠️ Ícone do dia atual não encontrado");
        return;
      }

      // Verificar se já tem troféu
      if (iconeHoje.classList.contains("fa-trophy")) {
        console.log("✅ Troféu já aplicado no dia atual");
        return;
      }

      // Debounce: evitar reaplicação rápida
      if (!podeAplicarTrofeu(elementoHoje)) {
        console.log("⏳ Ignorando reaplicação rápida do troféu (debounce)");
        return;
      }

      // Verificação canônica extra: checar com MetaDiariaManager antes de aplicar
      if (typeof MetaDiariaManager !== "undefined") {
        try {
          const dados = await MetaDiariaManager.atualizarMetaDiaria(true);
          if (!dados) {
            console.log(
              "⚠️ MetaDiariaManager não retornou dados ao verificar aplicação do troféu"
            );
            // Não aplicar troféu sem confirmação do manager
            return;
          }

          const saldoDia = parseFloat(dados.lucro) || 0;
          const metaCalculada = parseFloat(dados.meta_display) || 0;

          const batida =
            (metaCalculada === 0 && saldoDia > 0) ||
            (metaCalculada > 0 && saldoDia >= metaCalculada);

          if (!batida) {
            console.log(
              `⛔ Meta não confirmada pelo MetaDiariaManager (saldo=${saldoDia}, meta=${metaCalculada}) - pulando aplicação do troféu`
            );
            return;
          }
        } catch (e) {
          console.warn(
            "⚠️ Falha ao consultar MetaDiariaManager antes de aplicar troféu:",
            e
          );
          return; // evitar aplicar em caso de erro
        }
      }

      // Aplicar troféu com força (tamanho reduzido fixo e posição ajustável)
      iconeHoje.className = "fa-solid fa-trophy trofeu-icone";
      iconeHoje.style.cssText =
        'color: #FFD700 !important; font-family: "Font Awesome 6 Free" !important; font-weight: 900 !important; font-size: 12px !important; width: 12px !important; height: 12px !important; line-height: 12px !important; margin-top: 2px !important; display: inline-block !important;';
      elementoHoje.setAttribute("data-meta-batida", "true");
      // Marcar explicitamente que este troféu foi permitido pelo sistema (evita remoção pela proteção)
      elementoHoje.dataset.trofeuPermitido = "1";
      // Preservar exibição mesmo ao mudar período
      elementoHoje.dataset.trofeuPreservado = "1";

      console.log(`🏆 Troféu aplicado no dia atual (${hoje})`);
    },

    garantirSemTrofeus() {
      let removidos = 0;

      document.querySelectorAll(".gd-linha-dia .icone i").forEach((icone) => {
        if (icone.classList.contains("fa-trophy")) {
          const linha = icone.closest(".gd-linha-dia");
          // Se a linha indicou que o troféu era permitido, removê-lo normalmente e limpar a marca
          // Debounce na remoção para evitar flicker
          if (linha && !podeAplicarTrofeu(linha)) {
            // já houve alteração recente, marcar remoção e pular
            marcarRemocaoTrofeu(linha);
            return;
          }

          icone.className = "fa-solid fa-check";
          icone.style.cssText =
            'color: #64748b !important; font-family: "Font Awesome 6 Free" !important; font-weight: 900 !important;';

          if (linha) {
            // Se o troféu estiver marcado como preservado, não remover automaticamente
            if (linha.dataset.trofeuPreservado === "1") {
              console.log("🔒 Troféu preservado — pulando remoção");
            } else {
              linha.setAttribute("data-meta-batida", "false");
              delete linha.dataset.trofeuPermitido;
            }
          }

          marcarRemocaoTrofeu(linha);
          removidos++;
        }
      });

      if (removidos > 0) {
        console.log(`🧹 ${removidos} troféus removidos (meta não batida)`);
      }
    },

    configurarMonitoramento() {
      // Observer focado no rótulo
      const rotuloElement =
        document.getElementById("rotulo-meta") ||
        document.querySelector(".widget-meta-rotulo");

      if (rotuloElement) {
        this.observer = new MutationObserver(() => {
          setTimeout(() => {
            if (this.ativo) {
              this.verificarEAplicar();
            }
          }, 300);
        });

        this.observer.observe(rotuloElement, {
          childList: true,
          characterData: true,
          subtree: true,
        });

        console.log("👁️ Monitoramento do rótulo ativado");
      }

      // Verificação periódica (backup)
      this.intervaloPrincipal = setInterval(() => {
        if (this.ativo) {
          this.verificarEAplicar();
        }
      }, 3000);
    },

    obterDataHoje() {
      const d = new Date();
      const ano = d.getFullYear();
      const mes = String(d.getMonth() + 1).padStart(2, "0");
      const dia = String(d.getDate()).padStart(2, "0");
      return `${ano}-${mes}-${dia}`;
    },

    parar() {
      this.ativo = false;

      if (this.observer) {
        this.observer.disconnect();
        this.observer = null;
      }

      if (this.intervaloPrincipal) {
        clearInterval(this.intervaloPrincipal);
        this.intervaloPrincipal = null;
      }

      console.log("🛑 Sistema parado");
    },

    status() {
      const rotuloElement =
        document.getElementById("rotulo-meta") ||
        document.querySelector(".widget-meta-rotulo");
      const rotuloTexto = rotuloElement
        ? rotuloElement.textContent
        : "Não encontrado";

      return {
        ativo: this.ativo,
        rotuloAtual: rotuloTexto,
        metaBatida: this.interpretarRotulo(rotuloTexto.toLowerCase()),
        dataHoje: this.obterDataHoje(),
        trofeusAtivos: document.querySelectorAll(".fa-trophy").length,
      };
    },

    verificarAgora() {
      this.ultimaVerificacao = "";
      this.verificarEAplicar();
    },
  };

  // ========================================
  // EXECUÇÃO SEQUENCIAL
  // ========================================

  function executarSistemaCompleto() {
    console.log("🔄 Iniciando execução sequencial...");

    // Aguardar DOM carregado
    if (document.readyState !== "complete") {
      setTimeout(executarSistemaCompleto, 500);
      return;
    }

    // Verificar elementos
    const listaDias = document.querySelector(".lista-dias");
    if (!listaDias) {
      console.log("⏳ Aguardando elementos carregarem...");
      setTimeout(executarSistemaCompleto, 1000);
      return;
    }

    // Fase 1: Limpeza
    const limpezaOk = limpezaCompleta();

    if (limpezaOk) {
      // Fase 2: Sistema final (com delay)
      setTimeout(() => {
        SistemaTrofeuFinal.inicializar();
      }, 2000);
    }
  }

  // ========================================
  // COMANDOS GLOBAIS
  // ========================================

  window.TrofeuCompleto = {
    status: () => {
      const s = SistemaTrofeuFinal.status();
      console.log("📊 STATUS COMPLETO:");
      console.log(`   Sistema ativo: ${s.ativo}`);
      console.log(`   Rótulo: "${s.rotuloAtual}"`);
      console.log(`   Meta batida: ${s.metaBatida}`);
      console.log(`   Data hoje: ${s.dataHoje}`);
      console.log(`   Troféus ativos: ${s.trofeusAtivos}`);
      return s;
    },

    verificar: () => {
      console.log("🔍 Forçando verificação...");
      SistemaTrofeuFinal.verificarAgora();
    },

    parar: () => {
      SistemaTrofeuFinal.parar();
    },

    reiniciar: () => {
      SistemaTrofeuFinal.parar();
      setTimeout(() => {
        executarSistemaCompleto();
      }, 1000);
    },
  };

  // ========================================
  // AUTO-INICIALIZAÇÃO
  // ========================================

  executarSistemaCompleto();

  console.log("🏆 SISTEMA COMPLETO DE TROFÉU CARREGADO!");
  console.log("📋 Funcionalidades:");
  console.log("   1. Limpa todos os sistemas anteriores");
  console.log("   2. Remove todos os troféus existentes");
  console.log("   3. Monitora APENAS o rótulo da meta");
  console.log("   4. Aplica troféu quando rótulo confirma meta batida");
  console.log('   5. Remove troféus quando rótulo mostra "restando"');
  console.log("");
  console.log("🔧 Comandos disponíveis:");
  console.log("   TrofeuCompleto.status() - Ver status");
  console.log("   TrofeuCompleto.verificar() - Forçar verificação");
  console.log("   TrofeuCompleto.reiniciar() - Reiniciar sistema");
  console.log("");
  console.log("🎯 REGRA FINAL:");
  console.log('   - Rótulo com "restando/faltam" = SEM troféu');
  console.log('   - Rótulo com "batida/atingida" = Troféu no dia atual');
})();
// ========================================================================================================================
//                                 FIM  TROFÉU - PARA APARECER  QUANDO A META É BATIDA
// ========================================================================================================================
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
// ========================================================================================================================
//                                 AS CORES DO CSS PARA FICAR FIXA FUNCIONANDO
// ========================================================================================================================
if (
  typeof ListaDiasManagerCorrigido !== "undefined" &&
  ListaDiasManagerCorrigido.intervaloAtualizacao
) {
  clearInterval(ListaDiasManagerCorrigido.intervaloAtualizacao);
  console.log("🛑 ListaDiasManagerCorrigido parado");
}

if (
  typeof SistemaTrofeuCompleto !== "undefined" &&
  SistemaTrofeuCompleto.intervaloAtualizacao
) {
  clearInterval(SistemaTrofeuCompleto.intervaloAtualizacao);
  console.log("🛑 SistemaTrofeuCompleto parado");
}

if (typeof SistemaMonitorCores !== "undefined") {
  SistemaMonitorCores.parar();
  console.log("🛑 SistemaMonitorCores parado");
}

// Limpar todos os intervalos existentes
// Limpar apenas intervalos conhecidos de sistemas conflitantes.
// Evita usar um loop global que pode parar timers de terceiros (ex.: MetaMensalManager).
try {
  if (
    typeof ListaDiasManagerCorrigido !== "undefined" &&
    ListaDiasManagerCorrigido.intervaloAtualizacao
  ) {
    clearInterval(ListaDiasManagerCorrigido.intervaloAtualizacao);
  }
  if (
    typeof SistemaTrofeuCompleto !== "undefined" &&
    SistemaTrofeuCompleto.intervaloAtualizacao
  ) {
    clearInterval(SistemaTrofeuCompleto.intervaloAtualizacao);
  }
} catch (e) {
  // silencioso
}

// ========================================
// SISTEMA ÚNICO E EFICIENTE
// ========================================

const SistemaUnicoSemConflito = {
  intervalo: null,
  ultimaAtualizacao: "",
  metaAtual: 50,
  _ultimaExecucaoProcessar: 0,

  // Função principal que faz TUDO de uma vez
  async processarCompleto() {
    const agoraTs = Date.now();
    // Evitar reexecuções muito rápidas que competem com re-renders
    if (agoraTs - this._ultimaExecucaoProcessar < 400) return;
    this._ultimaExecucaoProcessar = agoraTs;

    const linhas = document.querySelectorAll(".gd-linha-dia");
    if (linhas.length === 0) return;

    let alteracoes = 0;

    // Tentar obter cálculo canônico do MetaDiariaManager para o dia de hoje
    let batidaHoje = null;
    try {
      if (typeof MetaDiariaManager !== "undefined") {
        const dados = await MetaDiariaManager.atualizarMetaDiaria(true);
        if (dados) {
          const lucroHoje = parseFloat(dados.lucro) || 0;
          const metaHoje = parseFloat(dados.meta_display) || 0;
          // Decisão numérica direta
          batidaHoje =
            (metaHoje === 0 && lucroHoje > 0) ||
            (metaHoje > 0 && lucroHoje >= metaHoje);
        }
      }
    } catch (e) {
      console.warn(
        "⚠️ Falha ao obter cálculo do MetaDiariaManager em processarCompleto:",
        e
      );
      batidaHoje = null;
    }

    linhas.forEach((linha) => {
      const valorElemento = linha.querySelector(".valor");
      if (!valorElemento) return;

      const valorTexto = valorElemento.textContent.trim();
      const numeroLimpo = valorTexto
        .replace(/[R$\s]/g, "")
        .replace(",", ".")
        .replace(/[^\d.-]/g, "");

      const valor = parseFloat(numeroLimpo) || 0;

      // Determinar classe de cor
      let classeCorreta = "valor-zero";
      if (valor > 0) classeCorreta = "valor-positivo";
      else if (valor < 0) classeCorreta = "valor-negativo";

      // Aplicar classe APENAS se não tiver
      if (!linha.classList.contains(classeCorreta)) {
        linha.classList.remove(
          "valor-positivo",
          "valor-negativo",
          "valor-zero"
        );
        linha.classList.add(classeCorreta);
        alteracoes++;
      }

      // Aplicar ícone de troféu - preferir cálculo canônico para o dia atual
      const iconeEl = linha.querySelector(".icone i");
      const dataDate = linha.getAttribute("data-date") || linha.dataset.date;
      const d = new Date();
      const hojeStr = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(
        2,
        "0"
      )}-${String(d.getDate()).padStart(2, "0")}`;

      // Forçar: para o dia atual sempre usar o cálculo canônico do MetaDiariaManager.
      // Se o manager não estiver disponível (batidaHoje === null) assumimos que
      // a meta não foi batida — NÃO FAZER fallback para o DOM nesse caso.
      let deveTerTrofeu = false;
      let origemDecisao = "DOM/metaAtual";
      if (dataDate === hojeStr) {
        deveTerTrofeu = !!batidaHoje; // null/undefined => false
        origemDecisao = "MetaDiariaManager";
      } else {
        deveTerTrofeu = iconeEl && valor >= this.metaAtual;
        origemDecisao = "DOM/metaAtual";
      }

      // Debug: registrar valores de decisão para diagnóstico
      if (window && window.console && window.console.debug) {
        console.debug("TrofeuDecision", {
          dataDate,
          hojeStr,
          origemDecisao,
          valor,
          metaAtual: this.metaAtual,
          batidaHoje,
          deveTerTrofeu,
        });
      }

      if (iconeEl && deveTerTrofeu) {
        if (!iconeEl.classList.contains("fa-trophy")) {
          // Debounce: evitar reaplicação rápida
          if (!podeAplicarTrofeu(linha)) {
            // pular aplicação agora
          } else {
            iconeEl.classList.remove("fa-check");
            iconeEl.classList.add("fa-trophy", "trofeu-icone", "fa-solid");
            linha.setAttribute("data-meta-batida", "true");
            linha.dataset.trofeuPermitido = "1";
          }
        }
      } else if (iconeEl) {
        if (!iconeEl.classList.contains("fa-check")) {
          // Debounce na remoção
          if (!podeAplicarTrofeu(linha)) {
            marcarRemocaoTrofeu(linha);
          } else {
            iconeEl.classList.remove("fa-trophy", "trofeu-icone");
            iconeEl.classList.add("fa-check", "fa-solid");
            linha.setAttribute("data-meta-batida", "false");
            delete linha.dataset.trofeuPermitido;
            marcarRemocaoTrofeu(linha);
          }
        }
      }
    });

    if (alteracoes > 0) {
      console.log(`✅ Sistema único: ${alteracoes} alterações aplicadas`);
    }
  },

  // Detectar meta atual
  detectarMeta() {
    try {
      const dadosInfo = document.getElementById("dados-mes-info");
      if (dadosInfo) {
        const periodo = dadosInfo.dataset.periodoAtual || "dia";
        switch (periodo) {
          case "mes":
            this.metaAtual = parseFloat(dadosInfo.dataset.metaMensal) || 50;
            break;
          case "ano":
            this.metaAtual = parseFloat(dadosInfo.dataset.metaAnual) || 50;
            break;
          default:
            this.metaAtual = parseFloat(dadosInfo.dataset.metaDiaria) || 50;
        }
      }
    } catch (error) {
      this.metaAtual = 50;
    }
  },

  // Inicializar sistema único
  iniciar() {
    console.log("🚀 Iniciando sistema único sem conflitos...");

    // Detectar meta
    this.detectarMeta();

    // Processar imediatamente
    this.processarCompleto();

    // Intervalo ÚNICO de 5 segundos (mais espaçado para evitar conflitos)
    this.intervalo = setInterval(() => {
      this.processarCompleto();
    }, 5000);

    // Hook simples no fetch
    const originalFetch = window.fetch;
    window.fetch = async function (...args) {
      const response = await originalFetch.apply(this, args);

      // Aguardar resposta e processar após delay
      setTimeout(() => {
        if (SistemaUnicoSemConflito) {
          SistemaUnicoSemConflito.processarCompleto();
        }
      }, 1000);

      return response;
    };

    console.log("✅ Sistema único ativo - intervalo de 5 segundos");
  },

  // Parar sistema
  parar() {
    if (this.intervalo) {
      clearInterval(this.intervalo);
      this.intervalo = null;
      console.log("🛑 Sistema único parado");
    }
  },

  // Status
  status() {
    const linhas = document.querySelectorAll(".gd-linha-dia");
    const comCores = document.querySelectorAll(
      ".gd-linha-dia.valor-positivo, .gd-linha-dia.valor-negativo, .gd-linha-dia.valor-zero"
    );

    return {
      ativo: !!this.intervalo,
      totalLinhas: linhas.length,
      linhasComCores: comCores.length,
      metaAtual: this.metaAtual,
      eficiencia:
        linhas.length > 0
          ? Math.round((comCores.length / linhas.length) * 100) + "%"
          : "0%",
    };
  },
};

// ========================================
// DESABILITAR SISTEMAS ANTIGOS GLOBALMENTE
// ========================================

// Sobrescrever variáveis globais para evitar reativação
window.ListaDiasManagerCorrigido = null;
window.SistemaTrofeuCompleto = null;
window.SistemaMonitorCores = null;
window.BackupCores = null;
// Não sobrescrever MetaMensalManager - isso interrompe o carregamento/atualização da meta.
// Preservamos o gerenciador de meta para que o sistema mensal continue funcionando.
// Se necessário descomente a linha abaixo para forçar limpeza (não recomendado):
// window.MetaMensalManager = null;

// Comandos globais simplificados
window.SistemaUnico = {
  iniciar: () => SistemaUnicoSemConflito.iniciar(),
  parar: () => SistemaUnicoSemConflito.parar(),
  processar: () => SistemaUnicoSemConflito.processarCompleto(),
  status: () => SistemaUnicoSemConflito.status(),
  info: () => {
    const status = SistemaUnicoSemConflito.status();
    console.log("📊 Status Sistema Único:", status);
    return status;
  },
};

// Comandos de compatibilidade
window.Cores = window.SistemaUnico;
window.ListaDias = window.SistemaUnico;
window.Trofeu = window.SistemaUnico;

// ========================================
// INICIALIZAÇÃO AUTOMÁTICA
// ========================================

function inicializarSistemaUnico() {
  // Aguardar elementos estarem prontos
  setTimeout(() => {
    SistemaUnicoSemConflito.iniciar();
  }, 2000);
}

// Inicializar baseado no estado do DOM
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", inicializarSistemaUnico);
} else {
  inicializarSistemaUnico();
}

console.log("🎯 Sistema Único Sem Conflitos carregado!");
console.log("📋 Características:");
console.log("   ✅ Um único intervalo de 5 segundos");
console.log("   ✅ Não reconstrói HTML desnecessariamente");
console.log("   ✅ Aplica cores e troféus juntos");
console.log("   ✅ Remove todos os sistemas conflitantes");
console.log("");
console.log("🔧 Comandos únicos:");
console.log("   SistemaUnico.status() - Ver status");
console.log("   SistemaUnico.processar() - Processar agora");
console.log("   SistemaUnico.parar() - Parar sistema");

// Export para uso
window.SistemaUnicoSemConflito = SistemaUnicoSemConflito;
// Delegated listener: ao clicar em um mentor-card, abrir o formulário (fallback)
document.addEventListener("click", function (e) {
  try {
    const card = e.target.closest && e.target.closest(".mentor-card");
    if (!card) return;

    // Evitar interferir com menus/inputs
    if (e.target.closest(".menu, .btn, a, input, button")) return;

    const id = card.getAttribute("data-id") || card.dataset.id || null;
    const nome = card.getAttribute("data-nome") || card.dataset.nome || "";
    const foto = card.getAttribute("data-foto") || card.dataset.foto || "";

    if (typeof window.abrirFormularioMentor === "function") {
      window.abrirFormularioMentor(id, nome, foto);
      return;
    }

    // fallback: disparar evento custom (alguns trechos do PHP escutam isso)
    const evento = new CustomEvent("abrirFormularioMentor", {
      detail: { card: card, id: id, nome: nome, foto: foto },
    });
    document.dispatchEvent(evento);
  } catch (err) {
    // silencioso
  }
});
// ========================================================================================================================
//                                FIM AS CORES DO CSS PARA FICAR FIXA FUNCIONANDO
// ========================================================================================================================
//
//
//
//
//
//
//
