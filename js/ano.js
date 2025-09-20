(function () {
  "use strict";
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
      console.warn("âŒ FormulÃ¡rio #form-mentor nÃ£o encontrado.");
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
  //                                 JS DAOS CAMPOS ONDE FILTRA O MÃŠS BARRA DE PROGRESSO META E SALDO
  // ========================================================================================================================
  // =============================================
  //  CORREÃ‡ÃƒO DOS ÃCONES - USANDO CLASSES CORRETAS DO FONT AWESOME
  // =============================================

  const MetaMensalManager = {
    // Controle simples para meta mensal
    atualizandoAtualmente: false,
    periodoFixo: "ano",
    tipoMetaAtual: "turbo",

    // Atualizar meta mensal - versÃ£o especÃ­fica
    async atualizarMetaMensal(aguardarDados = false) {
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
        const metaFinal = parseFloat(data.meta_anual || data.meta_mensal) || 0;
        const rotuloFinal = "Meta do Ano";
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
          periodo_ativo: "ano",
          lucro_periodo: lucroMensal,
        };
      } catch (error) {
        console.error("Erro ao processar dados mensais:", error);
        return data;
      }
    },

    // âœ… NOVA FUNÃ‡ÃƒO: CALCULAR META FINAL MENSAL COM VALOR TACHADO E EXTRA
    calcularMetaFinalMensalComExtra(saldoMes, metaCalculada, bancaTotal, data) {
      try {
        let metaFinal,
          rotulo,
          statusClass,
          valorExtra = 0,
          mostrarTachado = false;

        console.log(`ðŸ” DEBUG CALCULAR META MENSAL COM EXTRA:`);
        console.log(`   Saldo MÃªs: R$ ${saldoMes.toFixed(2)}`);
        console.log(`   Meta MÃªs: R$ ${metaCalculada.toFixed(2)}`);
        console.log(`   Banca: R$ ${bancaTotal.toFixed(2)}`);

        if (bancaTotal <= 0) {
          metaFinal = bancaTotal;
          rotulo = "Deposite p/ ComeÃ§ar";
          statusClass = "sem-banca";
          console.log(`ðŸ“Š RESULTADO MENSAL: Sem banca`);
        }
        // âœ… META BATIDA OU SUPERADA - COM VALOR EXTRA
        else if (
          saldoMes > 0 &&
          metaCalculada > 0 &&
          saldoMes >= metaCalculada
        ) {
          valorExtra = saldoMes - metaCalculada;
          mostrarTachado = true;
          metaFinal = metaCalculada; // Mostra o valor da meta original

          if (valorExtra > 0) {
            rotulo = `Meta do Ano Superada! <i class='fa-solid fa-trophy'></i>`;
            statusClass = "meta-superada";
            console.log(
              `ðŸ† META MENSAL SUPERADA: Extra de R$ ${valorExtra.toFixed(2)}`
            );
          } else {
            rotulo = `Meta do Ano Batida! <i class='fa-solid fa-trophy'></i>`;
            statusClass = "meta-batida";
            console.log(`ðŸŽ¯ META MENSAL EXATA`);
          }
        }
        // âœ… CASO ESPECIAL: Meta Ã© zero (jÃ¡ foi batida)
        else if (metaCalculada === 0 && saldoMes > 0) {
          metaFinal = 0;
          valorExtra = saldoMes;
          mostrarTachado = false;
          rotulo = `Meta do Ano Batida! <i class='fa-solid fa-trophy'></i>`;
          statusClass = "meta-batida";
          console.log(`ðŸŽ¯ META MENSAL ZERO (jÃ¡ batida)`);
        } else if (saldoMes < 0) {
          metaFinal = metaCalculada - saldoMes;
          rotulo = `Restando p/ Meta do Ano`;
          statusClass = "negativo";
          console.log(`ðŸ“Š RESULTADO MENSAL: Negativo`);
        } else if (saldoMes === 0) {
          metaFinal = metaCalculada;
          rotulo = "Meta do Ano";
          statusClass = "neutro";
          console.log(`ðŸ“Š RESULTADO MENSAL: Neutro`);
        } else {
          // Lucro positivo mas menor que a meta
          metaFinal = metaCalculada - saldoMes;
          rotulo = `Restando p/ Meta do Ano`;
          statusClass = "lucro";
          console.log(`ðŸ“Š RESULTADO MENSAL: Lucro insuficiente`);
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

        console.log(`ðŸ RESULTADO FINAL MENSAL COM EXTRA:`);
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
          rotulo: "Erro no cÃ¡lculo",
          statusClass: "erro",
        };
      }
    },

    // Atualizar todos os elementos - versÃ£o para bloco 2 COM EXTRA
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

        // âœ… USAR NOVA FUNÃ‡ÃƒO COM VALOR EXTRA
        const resultado = this.calcularMetaFinalMensalComExtra(
          saldoMes,
          metaCalculada,
          bancaTotal,
          dadosComplementados
        );

        // Atualizar elementos do bloco 2
        this.garantirIconeMoeda();
        this.atualizarMetaElementoMensalComExtra(resultado); // âœ… NOVA FUNÃ‡ÃƒO
        this.atualizarRotuloMensal(resultado.rotulo);
        this.atualizarBarraProgressoMensal(resultado, data);

        console.log(`Meta MENSAL atualizada COM EXTRA`);
        console.log(`Lucro do MÃŠS: R$ ${saldoMes.toFixed(2)}`);
        console.log(`Meta MENSAL: R$ ${metaCalculada.toFixed(2)}`);

        if (resultado.valorExtra > 0) {
          console.log(
            `ðŸ† Valor Extra MENSAL: R$ ${resultado.valorExtra.toFixed(2)}`
          );
        }
      } catch (error) {
        console.error("Erro ao atualizar elementos mensais:", error);
      }
    },

    // âœ… NOVA FUNÃ‡ÃƒO: ATUALIZAR META ELEMENTO MENSAL COM VALOR TACHADO E EXTRA
    atualizarMetaElementoMensalComExtra(resultado) {
      try {
        const metaValor = document.getElementById("meta-valor-3");
        if (!metaValor) {
          console.warn("Elemento meta-valor-3 nÃ£o encontrado");
          return;
        }

        // âœ… LIMPAR CLASSES ANTIGAS
        metaValor.className = metaValor.className.replace(
          /\bvalor-meta-3\s+\w+/g,
          ""
        );

        let htmlConteudo = "";

        if (resultado.mostrarTachado && resultado.valorExtra >= 0) {
          // âœ… META BATIDA/SUPERADA - MOSTRAR VALOR TACHADO + EXTRA
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
            `âœ… Valor tachado MENSAL aplicado: ${resultado.metaOriginalFormatada}`
          );

          if (resultado.valorExtra > 0) {
            console.log(
              `âœ… Valor extra MENSAL aplicado: + ${resultado.valorExtraFormatado}`
            );
          }
        } else {
          // âœ… EXIBIÃ‡ÃƒO NORMAL
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
        console.error(
          "Erro ao atualizar meta elemento mensal com extra:",
          error
        );
      }
    },

    // FUNÃ‡ÃƒO CORRIGIDA: GARANTIR ÃCONE DA MOEDA COM CLASSES CORRETAS
    garantirIconeMoeda() {
      try {
        const metaValor = document.getElementById("meta-valor-3");
        if (!metaValor) return;

        // Verificar se jÃ¡ tem o Ã­cone (classes corretas do Font Awesome)
        const iconeExistente = metaValor.querySelector(".fa-coins");

        if (!iconeExistente) {
          const valorTexto = metaValor.querySelector(".valor-texto-3");
          if (valorTexto) {
            const textoAtual = valorTexto.textContent;
            // USAR CLASSES CORRETAS DO FONT AWESOME
            metaValor.innerHTML = `
            <i class="fa-solid fa-coins"></i>
            <div class="meta-valor-container-3">
              <span class="valor-texto-3">${textoAtual}</span>
            </div>
          `;
            console.log("Ãcone da moeda adicionado ao HTML 2");
          }
        }
      } catch (error) {
        console.error("Erro ao garantir Ã­cone da moeda:", error);
      }
    },

    // Atualizar rÃ³tulo - bloco 2
    atualizarRotuloMensal(rotulo) {
      try {
        const rotuloElement = document.getElementById("rotulo-meta-3");
        if (rotuloElement) {
          rotuloElement.innerHTML = rotulo;
          // Se o rÃ³tulo indicar "Restando" aplicamos uma classe para permitir
          // ajustes CSS especÃ­ficos (margem top controlada por variÃ¡vel :root)
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
          console.warn("Elemento rotulo-meta-3 nÃ£o encontrado");
        }
      } catch (error) {
        console.error("Erro ao atualizar rÃ³tulo mensal:", error);
      }
    },

    // FUNÃ‡ÃƒO CORRIGIDA: ÃCONES DINÃ‚MICOS DO SALDO COM CLASSES CORRETAS
    atualizarIconesSaldoDinamicos(saldoMes) {
      try {
        const saldoInfo = document.getElementById("saldo-info-3");
        if (!saldoInfo) return;

        const saldoFormatado = saldoMes.toLocaleString("pt-BR", {
          style: "currency",
          currency: "BRL",
        });

        let textoSaldo = "Saldo";
        let iconeClass = "fa-solid fa-wallet"; // CLASSE CORRETA
        let classeEstado = "saldo-zero-3";

        // Determinar texto, Ã­cone e classe baseado no valor
        if (saldoMes > 0) {
          textoSaldo = "Lucro MÃªs";
          iconeClass = "fa-solid fa-chart-line"; // GRÃFICO SUBINDO
          classeEstado = "saldo-positivo-3";
        } else if (saldoMes < 0) {
          textoSaldo = "Negativo";
          iconeClass = "fa-solid fa-arrow-trend-down"; // GRÃFICO DESCENDO
          classeEstado = "saldo-negativo-3";
        } else {
          textoSaldo = "Saldo MÃªs";
          iconeClass = "fa-solid fa-wallet"; // CARTEIRA
          classeEstado = "saldo-zero-3";
        }

        // Atualizar HTML do saldo COM CLASSES CORRETAS
        saldoInfo.innerHTML = `
        <i class="${iconeClass}"></i>
        <span class="saldo-info-rotulo-3">${textoSaldo}:</span>
        <span class="saldo-info-valor-3">${saldoFormatado}</span>
      `;

        // Aplicar classe de estado
        saldoInfo.className = classeEstado;

        console.log(`Ãcone HTML 2 atualizado: ${textoSaldo} - ${iconeClass}`);
      } catch (error) {
        console.error("Erro ao atualizar Ã­cones dinÃ¢micos HTML 2:", error);
      }
    },

    // âœ… NOVA FUNÃ‡ÃƒO: LIMPAR COMPLETAMENTE O ESTADO DA BARRA
    limparEstadoBarraMensal() {
      try {
        const barraProgresso = document.getElementById("barra-progresso-3");
        const porcentagemBarra = document.getElementById("porcentagem-barra-3");

        if (barraProgresso) {
          // Remover todas as classes possÃ­veis
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

        console.log("Barra mensal limpa completamente");
      } catch (error) {
        console.error("Erro ao limpar estado da barra mensal:", error);
      }
    },
    atualizarBarraProgressoMensal(resultado, data) {
      try {
        const barraProgresso = document.getElementById("barra-progresso-3");
        const saldoInfo = document.getElementById("saldo-info-3");
        const porcentagemBarra = document.getElementById("porcentagem-barra-3");

        if (!barraProgresso) {
          console.warn("Elemento barra-progresso-3 nÃ£o encontrado");
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
            progresso = -Math.min(
              Math.abs(saldoMes / metaCalculada) * 100,
              100
            );
          } else {
            progresso = Math.max(
              0,
              Math.min(100, (saldoMes / metaCalculada) * 100)
            );
          }
        }

        const larguraBarra = Math.abs(progresso);

        // âœ… LIMPEZA COMPLETA DAS CLASSES ANTIGAS
        let classeCor = "";

        // Remover TODAS as classes de cor possÃ­veis
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
            `âœ… BARRA MENSAL META BATIDA/SUPERADA - Saldo: R$ ${saldoMes.toFixed(
              2
            )}, Meta: R$ ${metaCalculada.toFixed(2)}`
          );
        } else {
          classeCor = `barra-${resultado.statusClass}-3`;
          console.log(
            `âœ… BARRA MENSAL NORMAL - Status: ${
              resultado.statusClass
            }, Saldo: R$ ${saldoMes.toFixed(2)}`
          );
        }

        // Aplicar classe e estilos com limpeza forÃ§ada
        barraProgresso.classList.add(classeCor);

        // âœ… FORÃ‡AR RESET DE ESTILOS INLINE ANTIGOS
        barraProgresso.style.width = `${larguraBarra}%`;
        barraProgresso.style.backgroundColor = "";
        barraProgresso.style.background = "";
        barraProgresso.style.filter = "";
        barraProgresso.style.animation = "";

        console.log(
          `âœ… BARRA MENSAL - Classe aplicada: ${classeCor}, Largura: ${larguraBarra}%`
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

        // ATUALIZAR ÃCONES DINÃ‚MICOS DO SALDO
        this.atualizarIconesSaldoDinamicos(saldoMes);
      } catch (error) {
        console.error("Erro ao atualizar barra progresso mensal:", error);
      }
    },

    // Mostrar erro especÃ­fico para mensal
    mostrarErroMetaMensal() {
      try {
        const metaElement = document.getElementById("meta-valor-3");
        if (metaElement) {
          // USAR CLASSES CORRETAS DO FONT AWESOME
          metaElement.innerHTML =
            '<i class="fa-solid fa-coins"></i><div class="meta-valor-container-3"><span class="valor-texto-3 loading-text-3">R$ 0,00</span></div>';
        }
      } catch (error) {
        console.error("Erro ao mostrar erro meta mensal:", error);
      }
    },

    // Inicializar sistema mensal (com garantia do Ã­cone)
    inicializar() {
      try {
        const metaElement = document.getElementById("meta-valor-3");
        if (metaElement) {
          // USAR CLASSES CORRETAS DO FONT AWESOME
          metaElement.innerHTML =
            '<i class="fa-solid fa-coins"></i><div class="meta-valor-container-3"><span class="valor-texto-3 loading-text-3">Calculando...</span></div>';
        }

        console.log(
          `Sistema Meta MENSAL COM VALOR TACHADO E EXTRA inicializado`
        );

        // Garantir Ã­cone da moeda apÃ³s delay
        setTimeout(() => {
          this.garantirIconeMoeda();
        }, 1500);

        // Inicializar com delay
        setTimeout(() => {
          this.atualizarMetaMensal();
        }, 1000);
      } catch (error) {
        console.error("Erro na inicializaÃ§Ã£o mensal:", error);
      }
    },

    // Sincronizar com mudanÃ§as do bloco 1
    sincronizarComBloco1() {
      try {
        this.atualizarMetaMensal(true);
      } catch (error) {
        console.error("Erro ao sincronizar com bloco 1:", error);
      }
    },
  };

  // ========================================
  // FUNÃ‡Ã•ES GLOBAIS E ATALHOS
  // ========================================

  // Expose annual-specific helpers to avoid collisions
  window.atualizarMetaAnual = () => {
    if (typeof MetaMensalManager !== "undefined") {
      return MetaMensalManager.atualizarMetaMensal();
    }
    return null;
  };

  window.$3 = {
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

    // âœ… NOVO: FunÃ§Ã£o para testar valor tachado e extra
    testExtra: () => {
      console.log("Testando valor tachado e extra MENSAL...");

      if (typeof MetaMensalManager === "undefined") {
        return "MetaMensalManager nÃ£o encontrado";
      }

      // Simular diferentes cenÃ¡rios de teste
      const testData = {
        meta_display: 1000,
        meta_display_formatada: "R$ 1.000,00",
        banca: 5000,
        rotulo_periodo: "Meta do Ano",
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

      // Teste 3: Meta nÃ£o batida
      setTimeout(() => {
        console.log("Teste 3: Meta MENSAL nÃ£o batida (R$ 750)");
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
          verificacao: "Sistema Meta Mensal COM valor tachado e extra",
        };

        console.log("Info Sistema Meta Mensal COM EXTRA:", info);
        return "Info Meta Mensal COM VALOR TACHADO E EXTRA verificada";
      } catch (error) {
        console.error("Erro ao obter info mensal:", error);
        return "Erro ao obter informaÃ§Ãµes mensais";
      }
    },
  };

  // ========================================
  // INICIALIZAÃ‡ÃƒO
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

      console.log(
        "Sistema Meta MENSAL COM VALOR TACHADO E EXTRA inicializado!"
      );
      console.log("CaracterÃ­sticas:");
      console.log("   âœ… Sempre mostra META DO MÃŠS");
      console.log("   âœ… Ãcone da moeda garantido");
      console.log("   âœ… Ãcones dinÃ¢micos do saldo");
      console.log("   âœ… Barra de progresso reduzida");
      console.log("   âœ… Classes Font Awesome corretas");
      console.log("   âœ… VALOR TACHADO quando meta batida");
      console.log("   âœ… VALOR EXTRA em dourado quando meta superada");
    } catch (error) {
      console.error("Erro na inicializaÃ§Ã£o sistema mensal:", error);
    }
  }

  // ========================================
  // SISTEMA DE INTERCEPTAÃ‡ÃƒO RÃPIDA
  // ========================================

  // Sistema de interceptaÃ§Ã£o rÃ¡pida (melhorado)
  (function () {
    // Timestamp da Ãºltima atualizaÃ§Ã£o bem-sucedida
    let ultimaAtualizacao = 0;
    // Intervalo mÃ­nimo entre atualizaÃ§Ãµes (ms) - reduzido para responder rapidamente
    const MIN_INTERVAL_MS = 200; // evita loops agressivos, permite resposta quase imediata

    function atualizarRapido() {
      const agora = Date.now();
      if (agora - ultimaAtualizacao < MIN_INTERVAL_MS) return; // Evitar spam

      ultimaAtualizacao = agora;

      if (typeof MetaMensalManager !== "undefined") {
        // ForÃ§ar estado para permitir reexecuÃ§Ã£o imediata
        MetaMensalManager.atualizandoAtualmente = false;
        // Sem delay
        MetaMensalManager.atualizarMetaMensal(false);
      }
    }

    // Chamadas diretas em eventos do usuÃ¡rio: executar imediatamente (ou com micro-delay)
    document.addEventListener(
      "submit",
      (e) => {
        // Empregar micro timeout para permitir que o envio/do DOM atualize antes da requisiÃ§Ã£o
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

    // Hook em fetch para detectar requisiÃ§Ãµes que alteram dados e disparar atualizaÃ§Ã£o apÃ³s retorno
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
        "NÃ£o foi possÃ­vel hookar fetch para atualizaÃ§Ãµes automÃ¡ticas MENSAL",
        e
      );
    }

    // Interval fallback (mais longo) para garantir eventual consistÃªncia
    setInterval(atualizarRapido, 5000);

    // Primeira atualizaÃ§Ã£o imediata
    setTimeout(atualizarRapido, 50);

    // Expor utilitÃ¡rio
    window.atualizarRapidoMensal = atualizarRapido;

    console.log(
      "Sistema rÃ¡pido MENSAL (melhorado) ativo - responde imediatamente a mudanÃ§as"
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
  console.log("  $2.force() - ForÃ§ar atualizaÃ§Ã£o");
  console.log("  $2.testExtra() - Testar valor tachado e extra");
  console.log("  $2.sync() - Sincronizar com bloco 1");
  console.log("  $2.info() - Ver status completo");

  // Export para uso externo
  // Expose the anual manager under a unique name
  window.MetaAnualManager = MetaMensalManager;
  // AQUI FINAL PARTE DO CODIGO QUE QTUALIZA EM TEMPO REAL VIA AJAX OS VALORES
  // ========================================================================================================================
  //                               FIM JS DAOS CAMPOS ONDE FILTRA O MÃŠS BARRA DE PROGRESSO META E SALDO
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
  //                                          JS DO PLACAR DO BLOCO 2 MÃŠS
  // ========================================================================================================================

  // GERENCIADOR DO PLACAR ANUAL (PERÍODO: ANO)
  const PlacarAnualManager = {
    async atualizarPlacarAnual() {
      try {
        const placarElement = document.getElementById("pontuacao-3");
        if (!placarElement) return;

        // Buscar dados do placar anual (todos os meses)
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

        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const html = await response.text();

        // Extrair dados do placar igual ao método mensal
        const temp = document.createElement("div");
        temp.innerHTML = html;
        const totalGreenEl = temp.querySelector("#total-green-dia");
        const totalRedEl = temp.querySelector("#total-red-dia");
        let wins = 0,
          losses = 0;
        if (totalGreenEl && totalRedEl) {
          wins = parseInt(totalGreenEl.dataset.green || "0", 10) || 0;
          losses = parseInt(totalRedEl.dataset.red || "0", 10) || 0;
        }

        // Atualizar placar
        const greenSpan = placarElement.querySelector(".placar-green-3");
        const redSpan = placarElement.querySelector(".placar-red-3");
        const separadorEl = placarElement.querySelector(".separador-3");
        if (greenSpan && redSpan) {
          console.log("[PLACAR ANUAL] Dados recebidos:", { wins, losses });
          // Sempre mostrar 0 × 0 se ambos forem zero
          greenSpan.textContent = wins;
          redSpan.textContent = losses;
          if (wins === 0 && losses === 0) {
            if (separadorEl)
              separadorEl.style.setProperty("color", "#bbb", "important");
            placarElement.classList.remove(
              "placar-atualizado",
              "placar-has-values"
            );
          } else {
            if (separadorEl) separadorEl.style.removeProperty("color");
            placarElement.classList.add("placar-has-values");
            placarElement.classList.add("placar-atualizado");
            setTimeout(
              () => placarElement.classList.remove("placar-atualizado"),
              1000
            );
          }
        } else {
          console.warn("[PLACAR ANUAL] Elementos internos não encontrados!");
        }
      } catch (error) {
        // Em caso de erro, zera placar
        const placarElement = document.getElementById("pontuacao-3");
        if (placarElement) {
          const greenSpan = placarElement.querySelector(".placar-green-3");
          const redSpan = placarElement.querySelector(".placar-red-3");
          if (greenSpan) greenSpan.textContent = "";
          if (redSpan) redSpan.textContent = "";
        }
        console.error("Erro ao atualizar placar anual:", error);
      }
    },
  };

  // Exibir apenas o placar anual ao carregar
  document.addEventListener("DOMContentLoaded", function () {
    PlacarAnualManager.atualizarPlacarAnual();
  });

  // PlacarMensalManager permanece igual (caso usado em outros lugares)
  const PlacarMensalManager = {
    // âœ… CONTROLE DE ESTADO
    atualizandoAtualmente: false,
    intervaloPlacar: null,
    ultimaAtualizacao: null,

    // âœ… INICIALIZAR SISTEMA DE PLACAR MENSAL
    inicializar() {
      try {
        console.log("ðŸ“Š Inicializando Sistema de Placar Mensal...");

        // Verificar se existe o elemento
        const placar = document.getElementById("pontuacao-3");
        if (!placar) {
          console.warn("âš ï¸ Elemento #pontuacao-3 nÃ£o encontrado");
          return false;
        }

        // Primeira atualizaÃ§Ã£o
        this.atualizarPlacarMensal();

        // Configurar intervalo de atualizaÃ§Ã£o (a cada 30 segundos)
        this.intervaloPlacar = setInterval(() => {
          this.atualizarPlacarMensal();
        }, 30000);

        // Interceptar mudanÃ§as no sistema principal
        this.configurarInterceptadores();

        console.log("âœ… Sistema de Placar Mensal inicializado");
        return true;
      } catch (error) {
        console.error("âŒ Erro ao inicializar placar mensal:", error);
        return false;
      }
    },

    // âœ… ATUALIZAR PLACAR MENSAL - USANDO MESMA LÃ“GICA DO PLACAR PRINCIPAL
    async atualizarPlacarMensal() {
      if (this.atualizandoAtualmente) {
        console.log("â³ Placar mensal jÃ¡ sendo atualizado...");
        return;
      }

      this.atualizandoAtualmente = true;

      try {
        console.log("ðŸ“Š Buscando dados do placar mensal (perÃ­odo: mÃªs)...");

        // Usar mesma lÃ³gica do SistemaFiltroPeriodo - buscar dados do mÃªs
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

        // Usar mesma funÃ§Ã£o que o placar principal usa
        const placarData = this.extrairPlacarIgualPrincipal(html);

        if (placarData) {
          this.aplicarPlacarMensal(placarData);
          this.ultimaAtualizacao = new Date();
          console.log(
            `âœ… Placar mensal atualizado: ${placarData.wins} Ã— ${placarData.losses}`
          );
        } else {
          // Fallback: valores zerados
          this.aplicarPlacarMensal({ wins: 0, losses: 0 });
          console.log("âš ï¸ Nenhum dado encontrado, usando valores zero");
        }
      } catch (error) {
        console.error("âŒ Erro ao atualizar placar mensal:", error);
        this.mostrarErroPlacar();
        // Em caso de erro, zerar placar
        this.aplicarPlacarMensal({ wins: 0, losses: 0 });
      } finally {
        this.atualizandoAtualmente = false;
      }
    },

    // âœ… EXTRAIR PLACAR IGUAL AO SISTEMA PRINCIPAL - CÃ“PIA EXATA
    extrairPlacarIgualPrincipal(html) {
      try {
        console.log(
          "ðŸ” Extraindo placar usando mesma lÃ³gica do sistema principal..."
        );

        // Criar elemento temporÃ¡rio para parsear HTML - igual ao sistema principal
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
            `âœ… Dados extraÃ­dos do HTML (igual sistema principal): ${wins} Ã— ${losses}`
          );
          return { wins, losses };
        }

        console.log(
          "âš ï¸ Elementos #total-green-dia ou #total-red-dia nÃ£o encontrados"
        );

        // Fallback: buscar diretamente nos placares como o sistema principal faz
        const placarGreen = temp.querySelector(".placar-green");
        const placarRed = temp.querySelector(".placar-red");

        if (placarGreen && placarRed) {
          const wins = parseInt(placarGreen.textContent.trim(), 10) || 0;
          const losses = parseInt(placarRed.textContent.trim(), 10) || 0;

          console.log(
            `âœ… Dados extraÃ­dos dos placares diretos: ${wins} Ã— ${losses}`
          );
          return { wins, losses };
        }

        console.log("âš ï¸ Nenhum placar encontrado no HTML");
        return { wins: 0, losses: 0 };
      } catch (error) {
        console.error("âŒ Erro ao extrair placar:", error);
        return { wins: 0, losses: 0 };
      }
    },

    // âœ… EXTRAIR DADOS DO PLACAR DO HTML - VERSÃƒO SIMPLIFICADA
    extrairDadosPlacar(html) {
      try {
        // Criar elemento temporÃ¡rio para parsear HTML
        const temp = document.createElement("div");
        temp.innerHTML = html;

        console.log("ðŸ” Buscando dados do placar no HTML retornado...");

        // MÃ‰TODO 1: Buscar placar principal diretamente
        const placarGreen = temp.querySelector(".placar-green");
        const placarRed = temp.querySelector(".placar-red");

        if (placarGreen && placarRed) {
          const wins = parseInt(placarGreen.textContent.trim()) || 0;
          const losses = parseInt(placarRed.textContent.trim()) || 0;
          console.log(`âœ… MÃ©todo 1: Encontrado ${wins} Ã— ${losses}`);
          return { wins, losses };
        }

        // MÃ‰TODO 2: Contar mentores com Green/Red
        console.log("ðŸ” MÃ©todo 1 falhou, tentando mÃ©todo 2...");
        const mentorCards = temp.querySelectorAll(".mentor-card");
        let wins = 0,
          losses = 0;

        console.log(`ðŸ“Š Encontrados ${mentorCards.length} mentores`);

        mentorCards.forEach((card, index) => {
          // Buscar valores Green e Red nos mentor-cards
          const greenValues = card.querySelectorAll(
            ".value-box-green p:first-child"
          );
          const redValues = card.querySelectorAll(
            ".value-box-red p:first-child"
          );

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

        console.log(`âœ… MÃ©todo 2: Contados ${wins} wins, ${losses} losses`);
        return { wins, losses };
      } catch (error) {
        console.error("âŒ Erro ao extrair dados do placar:", error);
        return { wins: 0, losses: 0 };
      }
    },

    // âœ… EXTRAIR VALOR MONETÃRIO DE STRING
    extrairValorMonetario(texto) {
      try {
        if (!texto) return 0;

        // Remover R$, espaÃ§os e converter vÃ­rgula para ponto
        const numeroLimpo = texto
          .replace(/[R$\s]/g, "")
          .replace(",", ".")
          .replace(/[^\d.-]/g, "");

        return parseFloat(numeroLimpo) || 0;
      } catch (error) {
        return 0;
      }
    },

    // âœ… APLICAR PLACAR MENSAL NO ELEMENTO
    aplicarPlacarMensal(placarData) {
      try {
        const placarElement = document.getElementById("pontuacao-3");
        if (!placarElement) return;

        const greenSpan = placarElement.querySelector(".placar-green-3");
        const redSpan = placarElement.querySelector(".placar-red-3");
        const separadorEl = placarElement.querySelector(".separador-3");

        if (greenSpan && redSpan) {
          // If both values are zero, keep the placar visually empty until real data arrives
          const wins = Number(placarData.wins) || 0;
          const losses = Number(placarData.losses) || 0;

          if (wins === 0 && losses === 0) {
            // Show empty placeholders instead of "0 Ã— 0"
            greenSpan.textContent = "";
            redSpan.textContent = "";
            if (separadorEl) {
              // Make separator transparent while waiting for real data
              separadorEl.style.setProperty(
                "color",
                "transparent",
                "important"
              );
            }
            // remove update class if present and remove has-values marker
            placarElement.classList.remove("placar-atualizado");
            placarElement.classList.remove("placar-has-values");
          } else {
            // Ensure separator is visible and colored for non-empty scores
            if (separadorEl) {
              separadorEl.style.removeProperty("color");
            }

            // Marca que o placar tem valores para controles CSS
            placarElement.classList.add("placar-has-values");

            // Aplicar valores com animaÃ§Ã£o suave
            this.animarMudancaValor(greenSpan, wins);
            this.animarMudancaValor(redSpan, losses);

            // Aplicar classe de atualizaÃ§Ã£o
            placarElement.classList.add("placar-atualizado");
            setTimeout(() => {
              placarElement.classList.remove("placar-atualizado");
            }, 1000);
          }
        }
      } catch (error) {
        console.error("âŒ Erro ao aplicar placar:", error);
      }
    },

    // âœ… ANIMAR MUDANÃ‡A DE VALOR
    animarMudancaValor(elemento, novoValor) {
      try {
        const valorAtual = parseInt(elemento.textContent) || 0;

        if (valorAtual !== novoValor) {
          // AtualizaÃ§Ã£o direta sem animaÃ§Ã£o para evitar movimento
          // Pequeno timeout para permitir coalescÃªncia de mÃºltiplas atualizaÃ§Ãµes
          setTimeout(() => {
            elemento.textContent = novoValor;
          }, 10);
        }
      } catch (error) {
        console.error("âŒ Erro na animaÃ§Ã£o:", error);
        elemento.textContent = novoValor; // Fallback sem animaÃ§Ã£o
      }
    },

    // âœ… MOSTRAR ERRO NO PLACAR
    mostrarErroPlacar() {
      try {
        const placarElement = document.getElementById("pontuacao-3");
        if (!placarElement) return;

        placarElement.classList.add("placar-erro");
        setTimeout(() => {
          placarElement.classList.remove("placar-erro");
        }, 2000);
      } catch (error) {
        console.error("âŒ Erro ao mostrar erro:", error);
      }
    },

    // âœ… CONFIGURAR INTERCEPTADORES - INTEGRAÃ‡ÃƒO COM SISTEMA PRINCIPAL
    configurarInterceptadores() {
      try {
        // Interceptar atualizaÃ§Ãµes do SistemaFiltroPeriodo
        if (
          typeof SistemaFiltroPeriodo !== "undefined" &&
          SistemaFiltroPeriodo.atualizarPlacar
        ) {
          const originalAtualizarPlacar = SistemaFiltroPeriodo.atualizarPlacar;

          SistemaFiltroPeriodo.atualizarPlacar = function () {
            // Executa funÃ§Ã£o original
            originalAtualizarPlacar.call(this);

            // Atualiza placar mensal quando perÃ­odo for 'mes' â€” imediata
            if (this.periodoAtual === "mes") {
              console.log(
                "ðŸ”„ SistemaFiltroPeriodo atualizou placar do mÃªs, sincronizando placar-3..."
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

        // Interceptar mudanÃ§as de perÃ­odo
        const radiosPeriodo = document.querySelectorAll(
          'input[name="periodo"]'
        );
        radiosPeriodo.forEach((radio) => {
          radio.addEventListener("change", (e) => {
            console.log(
              "ðŸ”„ PerÃ­odo alterado, atualizando placar mensal imediatamente..."
            );
            // atualizar imediatamente com micro-delay para DOM
            setTimeout(() => this.atualizarPlacarMensal(), 50);
          });
        });

        // Interceptar funÃ§Ã£o de recarregar mentores
        if (
          typeof MentorManager !== "undefined" &&
          MentorManager.recarregarMentores
        ) {
          const originalRecarregar = MentorManager.recarregarMentores;

          MentorManager.recarregarMentores = async function (...args) {
            const resultado = await originalRecarregar.apply(this, args);

            // Sempre atualizar placar mensal apÃ³s recarregar mentores â€” imediato
            try {
              if (typeof PlacarMensalManager !== "undefined") {
                console.log(
                  "ðŸ”„ Mentores recarregados, atualizando placar mensal imediatamente..."
                );
                setTimeout(
                  () => PlacarMensalManager.atualizarPlacarMensal(),
                  50
                );
              }
            } catch (e) {}

            return resultado;
          };
        }
      } catch (error) {
        console.error("âŒ Erro ao configurar interceptadores:", error);
      }

      // --- Observador genÃ©rico para mudanÃ§as que afetam o placar (debounced) ---
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
          "#pontuacao-3",
          ".mentor-card",
          "#mentores",
          ".lista-meses",
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

        // TambÃ©m observar o body para capturar inserÃ§Ãµes de containers novos (leve)
        mo.observe(document.body, { childList: true, subtree: true });
      } catch (e) {
        // silencioso
      }
    },

    // âœ… NOVA FUNÃ‡ÃƒO: Sincronizar com placar principal quando perÃ­odo = mÃªs
    sincronizarComPlacarPrincipal() {
      try {
        const placarGreen = document.querySelector(".placar-green");
        const placarRed = document.querySelector(".placar-red");

        if (placarGreen && placarRed) {
          const wins = parseInt(placarGreen.textContent.trim(), 10) || 0;
          const losses = parseInt(placarRed.textContent.trim(), 10) || 0;

          console.log(
            `ðŸ“Š Sincronizando placar-3 com placar principal: ${wins} Ã— ${losses}`
          );
          this.aplicarPlacarMensal({ wins, losses });

          return true;
        }

        return false;
      } catch (error) {
        console.error("âŒ Erro ao sincronizar com placar principal:", error);
        return false;
      }
    },

    // âœ… PARAR SISTEMA
    parar() {
      try {
        if (this.intervaloPlacar) {
          clearInterval(this.intervaloPlacar);
          this.intervaloPlacar = null;
          console.log("ðŸ›‘ Sistema de placar mensal parado");
        }
      } catch (error) {
        console.error("âŒ Erro ao parar sistema:", error);
      }
    },

    // âœ… FORÃ‡AR ATUALIZAÃ‡ÃƒO
    forcarAtualizacao() {
      this.atualizandoAtualmente = false;
      return this.atualizarPlacarMensal();
    },

    // âœ… STATUS DO SISTEMA
    status() {
      return {
        ativo: !!this.intervaloPlacar,
        atualizando: this.atualizandoAtualmente,
        ultimaAtualizacao: this.ultimaAtualizacao,
        elementoExiste: !!document.getElementById("pontuacao-3"),
        intervaloAtivo: !!this.intervaloPlacar,
      };
    },
  };

  // ========================================
  // ðŸŽ¨ CSS CLONADO E ADAPTADO PARA PLACAR-3
  // ========================================

  const cssPlaccar2 = `
/* ===== PLACAR-3 - CLONE DO PLACAR ORIGINAL ===== */
.area-central-3 {
  position: absolute;
  left: var(--placar-3-left, 50%);
  top: var(--placar-3-top, 30px);
  transform: translate(-50%, -50%);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: clamp(12px, 3.5vw, 16px);
  font-weight: 400;
  color: #acafb3ff;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.pontuacao-3 {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: clamp(5px, 1.2vw, 20px); /* pequeno gap para proximidade */
  color: #2b2b2b; /* texto escuro para contraste com fundo cinza */
  font-size: clamp(15px, 3.5vw, 22px); /* um pouco menor */
  font-weight: 600 !important; /* manter grosso e forÃ§ar override */
  /* Tornar o campo horizontal 100% para preencher de ponta a ponta */
  position: absolute;
  left: 0;
  right: 0;
  width: 100%;
  transform: none;
  box-sizing: border-box; /* garantir que padding nÃ£o estoure a largura */
  /* Background container to allow mirrored/reflection effect */
  position: relative;
  z-index: 2;
  background: #eef0eeff; /* cor cinza solicitada */
  padding: 8px 16px; /* espaÃ§o interno para bordas */
  border-radius: 6px;
}

/* Fundo espelhado (reflexÃ£o) abaixo do placar */
.pontuacao-3::after {
  content: "";
  position: absolute;
  left: 0;
  right: 0;
  top: 100%; /* comeÃ§a logo abaixo do placar */
  height: 60%; /* altura da reflexÃ£o relativa ao placar */
  background: inherit; /* replica o background do placar */
  transform: scaleY(-1); /* espelha verticalmente */
  transform-origin: top;
  opacity: 0.08; /* opacidade leve para sutileza com fundo cinza */
  filter: blur(6px) saturate(0.9);
  -webkit-mask-image: linear-gradient(to bottom, rgba(0,0,0,0.6), rgba(0,0,0,0));
  mask-image: linear-gradient(to bottom, rgba(0,0,0,0.6), rgba(0,0,0,0));
  pointer-events: none;
  border-radius: 0 0 8px 8px;
}

.placar-green-3 {
  color: #03a158;
  font-weight: 700 !important; /* manter grosso e forÃ§ar override */
  font-size: inherit !important;
}

.placar-red-3 {
  color: #e93a3a;
  font-weight: 700 !important; /* manter grosso e forÃ§ar override */
  font-size: inherit !important;
}

.separador-3 {
  color: rgba(109, 107, 107, 0.95);
  font-size: clamp(12px, 2.5vw, 16px);
  font-weight: 400;
  margin: 0 clamp(1px, 0.4vw, 3px); /* margem menor para mais proximidade */
}

/* Specific override using ID to beat other !important rules */
#pontuacao-3.pontuacao-3,
#pontuacao-3.pontuacao-3 .placar-green-3,
#pontuacao-3.pontuacao-3 .placar-red-3 {
  font-weight: 700 !important;
}

/* ===== EFEITOS DE ATUALIZAÃ‡ÃƒO REMOVIDOS ===== */
/* .placar-atualizado .placar-green-3,
.placar-atualizado .placar-red-3 {
  text-shadow: 0 0 10px currentColor;
  animation: placar-pulse 0.6s ease-out;
}

.placar-erro {
  opacity: 0.5;
  animation: placar-erro-shake 0.5s ease-in-out;
} */

/* ===== ANIMAÃ‡Ã•ES REMOVIDAS ===== */
/* @keyframes placar-pulse {
  0% { transform: scale(1); }
  50% { transform: scale(1.1); }
  100% { transform: scale(1); }
}

@keyframes placar-erro-shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-3px); }
  75% { transform: translateX(2px); }
} */

/* ===== RESPONSIVIDADE ===== */
@media (max-width: 768px) {
  .area-central-3 {
    font-size: clamp(10px, 2vw, 14px);
  }

  .pontuacao-3 {
    gap: clamp(6px, 1.5vw, 12px);
    font-size: clamp(14px, 3.5vw, 20px);
    padding: 6px 10px;
    border-radius: 6px;
  }

  .separador-3 {
    font-size: clamp(12px, 2.5vw, 16px);
    margin: 0 clamp(3px, 0.8vw, 6px);
  }
}

@media (max-width: 480px) {
  .area-central-3 {
    font-size: clamp(9px, 1.8vw, 12px);
  }

  .pontuacao-3 {
    gap: clamp(4px, 1vw, 8px);
    font-size: clamp(12px, 3vw, 16px);
    padding: 5px 8px;
  }

  .separador-3 {
    font-size: clamp(10px, 2vw, 14px);
    margin: 0 clamp(2px, 0.5vw, 4px);
  }
}
`;

  // ========================================
  // ðŸ”§ FUNÃ‡Ã•ES AUXILIARES E INTEGRAÃ‡ÃƒO
  // ========================================

  // Injetar CSS no documento
  function injetarCSS() {
    try {
      const styleElement = document.createElement("style");
      styleElement.textContent = cssPlaccar2;
      document.head.appendChild(styleElement);
      console.log("âœ… CSS do placar mensal injetado");
    } catch (error) {
      console.error("âŒ Erro ao injetar CSS:", error);
    }
  }

  // FunÃ§Ã£o global para teste rÃ¡pido do placar mensal
  window.testarPlacarMensal = () => {
    console.log("ðŸ§ª Testando placar mensal...");

    const placar = document.getElementById("pontuacao-3");
    if (!placar) {
      console.error("âŒ Elemento #pontuacao-3 nÃ£o encontrado!");
      return false;
    }

    console.log("âœ… Elemento encontrado:", placar);

    // Teste visual rÃ¡pido
    const green = placar.querySelector(".placar-green-3");
    const red = placar.querySelector(".placar-red-3");

    if (green && red) {
      green.textContent = Math.floor(Math.random() * 10) + 1;
      red.textContent = Math.floor(Math.random() * 10) + 1;
      console.log(
        `âœ… Valores de teste aplicados: ${green.textContent} Ã— ${red.textContent}`
      );

      // ForÃ§ar atualizaÃ§Ã£o real apÃ³s teste
      setTimeout(() => {
        if (typeof PlacarMensalManager !== "undefined") {
          PlacarMensalManager.atualizarPlacarMensal();
        }
      }, 2000);

      return true;
    }

    console.error("âŒ Elementos internos nÃ£o encontrados");
    return false;
  };

  // FunÃ§Ã£o global para controle do placar mensal
  window.PlacarMensal = {
    iniciar: () => {
      console.log("ðŸš€ Iniciando placar mensal...");
      return PlacarMensalManager.inicializar();
    },
    parar: () => {
      console.log("ðŸ›‘ Parando placar mensal...");
      return PlacarMensalManager.parar();
    },
    atualizar: () => {
      console.log("ðŸ”„ Atualizando placar mensal...");
      return PlacarMensalManager.forcarAtualizacao();
    },
    status: () => PlacarMensalManager.status(),
    info: () => {
      const status = PlacarMensalManager.status();
      console.log("ðŸ“Š Status Placar Mensal:", status);
      return status;
    },
    teste: () => testarPlacarMensal(),
  };

  // ========================================
  // ðŸš€ INICIALIZAÃ‡ÃƒO AUTOMÃTICA
  // ========================================

  function inicializarPlacarMensal() {
    try {
      console.log("ðŸš€ Inicializando Sistema de Placar Mensal...");

      // Injetar CSS
      injetarCSS();

      // Aguardar elemento estar disponÃ­vel
      const verificarElemento = () => {
        const placar = document.getElementById("pontuacao-3");
        if (placar) {
          // Evitar flash: ocultar atÃ© o CSS injetado e o posicionamento final serem aplicados
          try {
            // Aplicar com !important para sobrescrever a regra CSS que esconde o elemento
            placar.style.setProperty("visibility", "hidden", "important");
          } catch (e) {}

          PlacarMensalManager.inicializar();

          // Mostrar apÃ³s curto delay (tempo suficiente para injeÃ§Ã£o de CSS e layout)
          setTimeout(() => {
            try {
              // Usar setProperty com 'important' para garantir que o inline style
              // sobrescreva a regra do stylesheet que contÃ©m !important
              placar.style.setProperty("visibility", "visible", "important");
            } catch (e) {}
          }, 120);

          console.log("âœ… Sistema de Placar Mensal inicializado com sucesso!");
        } else {
          console.log("â³ Aguardando elemento #pontuacao-3...");
          setTimeout(verificarElemento, 1000);
        }
      };

      verificarElemento();
    } catch (error) {
      console.error("âŒ Erro na inicializaÃ§Ã£o do placar mensal:", error);
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
  // ðŸ“ COMANDOS DE CONSOLE PARA DEBUG
  // ========================================

  console.log("ðŸ“Š Sistema de Placar Mensal carregado!");
  console.log("ðŸ”§ Comandos disponÃ­veis:");
  console.log("  PlacarMensal.iniciar() - Iniciar sistema");
  console.log("  PlacarMensal.parar() - Parar sistema");
  console.log("  PlacarMensal.atualizar() - ForÃ§ar atualizaÃ§Ã£o");
  console.log("  PlacarMensal.status() - Ver status");
  console.log("  PlacarMensal.info() - InformaÃ§Ãµes detalhadas");

  // Export para uso externo
  window.PlacarMensalManager = PlacarMensalManager;
  // ========================================================================================================================
  //                                         FIM JS DO PLACAR DO BLOCO 2 MÃŠS
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

    // ConfiguraÃ§Ãµes
    INTERVALO_MS: 3000, // Atualiza a cada 3 segundos
    TIMEOUT_MS: 5000,

    // Inicializar sistema
    inicializar() {
      console.log(
        "ðŸš€ Inicializando sistema corrigido de atualizaÃ§Ã£o da lista de dias..."
      );

      // Detectar meta inicial
      this.detectarMetaEPeriodo();

      // Primeira atualizaÃ§Ã£o imediata
      this.atualizarListaDias();

      // Configurar intervalo de atualizaÃ§Ã£o
      this.intervaloAtualizacao = setInterval(() => {
        this.atualizarListaDias();
      }, this.INTERVALO_MS);

      // Configurar interceptadores de eventos
      this.configurarInterceptadores();

      // Configurar observador sanitizador para evitar reaplicaÃ§Ã£o de estilos/Ã­cones
      try {
        this.configurarObservadorSanitizacao();
      } catch (e) {}

      // One-time hard cleanup: remove any inline styles left on existing .gd-linha-dia
      // e garantir que a flag CSS que forÃ§a largura fixa seja aplicada.
      try {
        document
          .querySelectorAll(
            ".lista-meses .gd-linha-dia, .lista-meses .gd-linha-dia .data"
          )
          .forEach((el) => {
            if (el.hasAttribute("style")) el.removeAttribute("style");
          });
        // Aplicar classe global para regras CSS de alta prioridade
        document.documentElement.classList.add("force-data-fixed");
      } catch (e) {}

      console.log("âœ… Sistema corrigido ativo!");
    },

    // Detectar meta e perÃ­odo atual
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
          `Meta detectada: R$ ${this.metaAtual.toFixed(2)} (${
            this.periodoAtual
          })`
        );
      } catch (error) {
        console.error("Erro ao detectar meta:", error);
        this.metaAtual = 0;
      }
    },

    // AtualizaÃ§Ã£o principal
    // CORRIGIR A FUNÇÃO atualizarListaDias no ListaDiasManagerCorrigido

    async atualizarListaDias() {
      if (this.atualizandoAtualmente) return;

      this.atualizandoAtualmente = true;

      try {
        // 🔧 CORREÇÃO: Buscar dados do ano inteiro para mostrar todos os meses
        const anoAtual = new Date().getFullYear();
        const url = `obter_dados_mes.php?ano=${anoAtual}&modo=ano`;

        console.log("📡 Buscando dados:", url);

        const response = await fetch(url, {
          method: "GET",
          headers: {
            "Cache-Control": "no-cache",
            "X-Requested-With": "XMLHttpRequest",
          },
          signal: AbortSignal.timeout(this.TIMEOUT_MS),
        });

        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        const dados = await response.json();

        console.log("📡 RESPOSTA COMPLETA:", dados);

        if (!dados.success) {
          throw new Error(dados.message || "Erro na resposta do servidor");
        }

        // Verificar se houve mudança
        const hashAtual = this.gerarHashDados(dados);
        if (hashAtual === this.hashUltimosDados) {
          return; // Sem mudanças
        }

        this.hashUltimosDados = hashAtual;

        // Renderizar todos os meses do ano
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

    // Renderizar mÃªs completo
    // Função renderizarMesCompleto corrigida - SEMPRE renderiza meses
    // PROBLEMA IDENTIFICADO: A lógica de agregação dos dados mensais está incorreta
    // SOLUÇÃO: Corrigir a busca e soma dos dados por mês

    // FUNÇÃO CORRIGIDA - renderizarMesCompleto
    // FUNÇÃO RENDERIZARMESCOMPLETO COMPLETAMENTE CORRIGIDA

    // CORREÇÃO DO CÁLCULO DE META MENSAL NO renderizarMesCompleto

    renderizarMesCompleto(responseData) {
      const container = document.querySelector(".lista-meses");
      if (!container) return;

      // Preservar posição do scroll
      const scrollTop = container.scrollTop;

      // Mapear estado atual de troféus
      const metaExistenteMap = {};
      container
        .querySelectorAll(".gd-linha-dia, .gd-linha-mes")
        .forEach((el) => {
          const date = el.getAttribute("data-date");
          if (date) {
            metaExistenteMap[date] =
              el.getAttribute("data-meta-batida") === "true";
          }
        });

      const dados = responseData.dados || {};
      const dadosPorMes = responseData.dados_por_mes || {};
      const ano = responseData.ano || new Date().getFullYear();

      console.log("🔍 DADOS RECEBIDOS:", dados);
      console.log("🔍 DADOS POR MÊS:", dadosPorMes);

      const hoje = new Date();
      const mesAtual = hoje.getMonth() + 1;
      const anoAtual = hoje.getFullYear();

      // 🔧 BUSCAR META MENSAL CORRETA - NÃO META DIÁRIA
      const dadosAnoInfoEl = document.getElementById("dados-ano-info");
      let metaMensalCorreta = 0;

      if (dadosAnoInfoEl) {
        // Primeiro tentar pegar meta mensal diretamente
        const metaMensalDireta = parseFloat(
          dadosAnoInfoEl.getAttribute("data-meta-mensal")
        );

        if (metaMensalDireta && metaMensalDireta > 0) {
          metaMensalCorreta = metaMensalDireta;
          console.log("📊 META MENSAL DIRETA:", metaMensalCorreta);
        } else {
          // Se não tem meta mensal, calcular da meta anual (anual ÷ 12)
          const metaAnual =
            parseFloat(dadosAnoInfoEl.getAttribute("data-meta-anual")) || 0;
          if (metaAnual > 0) {
            metaMensalCorreta = metaAnual / 12;
            console.log(
              "📊 META MENSAL CALCULADA (anual ÷ 12):",
              metaMensalCorreta
            );
          }
        }
      }

      // Se ainda não encontrou a meta, tentar buscar do sistema
      if (metaMensalCorreta === 0) {
        // Buscar meta do período atual selecionado
        const radioSelecionado = document.querySelector(
          'input[name="periodo"]:checked'
        );
        const periodoAtual = radioSelecionado ? radioSelecionado.value : "ano";

        if (periodoAtual === "ano" && dadosAnoInfoEl) {
          const metaAnual =
            parseFloat(dadosAnoInfoEl.getAttribute("data-meta-anual")) || 0;
          metaMensalCorreta = metaAnual / 12;
        } else if (periodoAtual === "mes" && dadosAnoInfoEl) {
          metaMensalCorreta =
            parseFloat(dadosAnoInfoEl.getAttribute("data-meta-mensal")) || 0;
        }

        console.log(
          "📊 META MENSAL DO PERÍODO ATIVO:",
          metaMensalCorreta,
          "período:",
          periodoAtual
        );
      }

      console.log("📊 META MENSAL FINAL DEFINIDA:", metaMensalCorreta);

      // Limpar container
      const prevMinHeight = container.style.minHeight;
      container.style.minHeight = container.clientHeight + "px";
      container.innerHTML = "";

      const monthNames = [
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

      const fragment = document.createDocumentFragment();

      // PROCESSAR CADA MÊS (1-12)
      for (let m = 1; m <= 12; m++) {
        const mesStr = String(m).padStart(2, "0");
        const chaveMes = `${ano}-${mesStr}`;

        console.log(
          `\n📅 PROCESSANDO ${monthNames[m - 1]} (${mesStr}/${ano}):`
        );

        // Buscar dados do mês
        let dadosMes = dadosPorMes[chaveMes] || {
          total_valor_green: 0,
          total_valor_red: 0,
          total_green: 0,
          total_red: 0,
        };

        // Se não encontrou, tentar outras formas (mantendo a lógica existente)
        if (
          dadosMes.total_valor_green === 0 &&
          dadosMes.total_valor_red === 0 &&
          dadosMes.total_green === 0 &&
          dadosMes.total_red === 0
        ) {
          // Buscar em formatos alternativos
          const formatosPossiveis = [
            chaveMes,
            `${mesStr}/${ano}`,
            `${mesStr}-${ano}`,
            `mes_${mesStr}_${ano}`,
            `month_${m}_${ano}`,
          ];

          for (const formato of formatosPossiveis) {
            if (dados[formato]) {
              dadosMes = dados[formato];
              console.log(`  ✅ Encontrado formato [${formato}]:`, dadosMes);
              break;
            }
          }

          // Se ainda não encontrou, somar dados diários
          if (
            dadosMes.total_valor_green === 0 &&
            dadosMes.total_valor_red === 0
          ) {
            const chaveDiaBase = `${ano}-${mesStr}`;
            Object.keys(dados).forEach((chave) => {
              if (chave.startsWith(chaveDiaBase + "-") && chave.length === 10) {
                const d = dados[chave];
                dadosMes.total_valor_green += parseFloat(
                  d.total_valor_green || 0
                );
                dadosMes.total_valor_red += parseFloat(d.total_valor_red || 0);
                dadosMes.total_green += parseInt(d.total_green || 0, 10);
                dadosMes.total_red += parseInt(d.total_red || 0, 10);
              }
            });
          }
        }

        // CALCULAR SALDO DO MÊS
        const saldo_mes = dadosMes.total_valor_green - dadosMes.total_valor_red;
        const isMesFuturo = anoAtual === ano && m > mesAtual;
        const temDadosReais =
          dadosMes.total_valor_green > 0 ||
          dadosMes.total_valor_red > 0 ||
          dadosMes.total_green > 0 ||
          dadosMes.total_red > 0;

        const saldo_mes_final = isMesFuturo && !temDadosReais ? 0 : saldo_mes;

        // 🔧 CORREÇÃO PRINCIPAL: META MENSAL, NÃO DIÁRIA
        const metaBatida =
          metaMensalCorreta > 0 && saldo_mes_final >= metaMensalCorreta;

        console.log(`  💰 VERIFICAÇÃO DE META para ${monthNames[m - 1]}:`);
        console.log(`     Saldo do mês: R$ ${saldo_mes_final.toFixed(2)}`);
        console.log(`     Meta mensal: R$ ${metaMensalCorreta.toFixed(2)}`);
        console.log(`     Meta batida: ${metaBatida ? "SIM" : "NÃO"}`);

        const saldo_formatado = saldo_mes_final.toLocaleString("pt-BR", {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2,
        });

        // CLASSES E CORES
        let cor_valor, classe_texto, placar_cinza;

        if (isMesFuturo && !temDadosReais) {
          cor_valor = "texto-cinza";
          classe_texto = "texto-cinza";
          placar_cinza = "texto-cinza";
        } else {
          cor_valor =
            saldo_mes_final === 0
              ? "texto-cinza"
              : saldo_mes_final > 0
              ? "verde-bold"
              : "vermelho-bold";
          classe_texto = saldo_mes_final === 0 ? "texto-cinza" : "";
          placar_cinza =
            dadosMes.total_green === 0 && dadosMes.total_red === 0
              ? "texto-cinza"
              : "";
        }

        const classes = ["gd-linha-dia"];

        if (isMesFuturo && !temDadosReais) {
          classes.push("mes-futuro", "valor-zero");
        } else {
          if (saldo_mes_final > 0) classes.push("valor-positivo");
          else if (saldo_mes_final < 0) classes.push("valor-negativo");
          else classes.push("valor-zero");
        }

        // Marcar mês atual
        if (anoAtual === ano && m === mesAtual) {
          classes.push("gd-dia-hoje");
          classes.push(
            saldo_mes_final >= 0 ? "gd-borda-verde" : "gd-borda-vermelha"
          );
        }

        // Destacar meses passados
        if (anoAtual === ano && m < mesAtual) {
          if (saldo_mes_final > 0) classes.push("gd-mes-destaque");
          else if (saldo_mes_final < 0)
            classes.push("gd-mes-destaque-negativo");
          if (dadosMes.total_green === 0 && dadosMes.total_red === 0) {
            classes.push("gd-mes-sem-valor");
          }
        }

        // CRIAR ELEMENTO DO MÊS
        const data_mysql = chaveMes;
        const data_exibicao = `${monthNames[m - 1]}/${ano}`;
        const finalMetaBatida = metaBatida || !!metaExistenteMap[data_mysql];

        // 🏆 ÍCONE BASEADO NA META MENSAL
        const iconeClasse = finalMetaBatida
          ? "fa-trophy trofeu-icone"
          : "fa-check";

        const divMes = document.createElement("div");
        divMes.className = classes.join(" ");
        divMes.setAttribute("data-date", data_mysql);
        divMes.setAttribute(
          "data-meta-batida",
          finalMetaBatida ? "true" : "false"
        );
        divMes.setAttribute("data-saldo", String(saldo_mes_final));
        divMes.setAttribute("data-meta-mensal", String(metaMensalCorreta)); // 🔧 META MENSAL
        divMes.setAttribute("data-periodo-atual", "ano");

        // HTML
        if (isMesFuturo && !temDadosReais) {
          divMes.innerHTML = `
        <span class="data ${classe_texto}">${data_exibicao}</span>
        <div class="placar-dia">
          <span class="placar verde-bold ${placar_cinza}">-</span>
          <span class="placar separador ${placar_cinza}">×</span>
          <span class="placar vermelho-bold ${placar_cinza}">-</span>
        </div>
        <span class="valor ${cor_valor}">-</span>
        <span class="icone ${classe_texto}">
          <i class="fa-solid ${iconeClasse}"></i>
        </span>
      `;
        } else {
          divMes.innerHTML = `
        <span class="data ${classe_texto}">${data_exibicao}</span>
        <div class="placar-dia">
          <span class="placar verde-bold ${placar_cinza}">${dadosMes.total_green}</span>
          <span class="placar separador ${placar_cinza}">×</span>
          <span class="placar vermelho-bold ${placar_cinza}">${dadosMes.total_red}</span>
        </div>
        <span class="valor ${cor_valor}">R$ ${saldo_formatado}</span>
        <span class="icone ${classe_texto}">
          <i class="fa-solid ${iconeClasse}"></i>
        </span>
      `;
        }

        // LOG com informação de troféu
        if (temDadosReais) {
          console.log(`  📊 TOTAL ${monthNames[m - 1]}:`, {
            verde: `R$ ${dadosMes.total_valor_green.toFixed(2)}`,
            vermelho: `R$ ${dadosMes.total_valor_red.toFixed(2)}`,
            saldo: `R$ ${saldo_mes_final.toFixed(2)}`,
            wins: dadosMes.total_green,
            losses: dadosMes.total_red,
            trofeu: finalMetaBatida ? "🏆" : "❌",
          });
        } else {
          console.log(
            `  ⚪ ${monthNames[m - 1]}: ${
              isMesFuturo ? "Mês futuro" : "Sem dados"
            }`
          );
        }

        fragment.appendChild(divMes);
      }

      // FINALIZAR
      container.appendChild(fragment);

      try {
        document
          .querySelectorAll(".lista-meses .gd-linha-dia .data")
          .forEach((el) => {
            if (el.hasAttribute("style")) el.removeAttribute("style");
          });
      } catch (e) {}

      container.scrollTop = scrollTop;
      container.style.minHeight = prevMinHeight || "";

      if (!this.ultimaAtualizacao) {
        setTimeout(() => this.focarMesAtual(), 500);
      }

      console.log(
        `\n🎯 RESULTADO FINAL: ${fragment.children.length} meses renderizados\n`
      );
      console.log(`🏆 META MENSAL USADA: R$ ${metaMensalCorreta.toFixed(2)}`);

      window.dispatchEvent(
        new CustomEvent("listaDiasAtualizada", {
          detail: { dados: responseData, timestamp: new Date() },
        })
      );
    },

    // Gerar hash simples dos dados para detectar mudanças

    // Função para focar no mês atual (YYYY-MM)
    focarMesAtual: function () {
      try {
        const hoje = new Date();
        const anoAtual = hoje.getFullYear();
        const mesAtual = String(hoje.getMonth() + 1).padStart(2, "0");
        const chaveMesAtual = `${anoAtual}-${mesAtual}`;

        const mesHoje = document.querySelector(
          `[data-date="${chaveMesAtual}"]`
        );

        if (!mesHoje) return;

        const container = document.querySelector(".lista-meses");
        if (container) {
          const containerHeight = container.clientHeight;
          const elementTop = mesHoje.offsetTop;
          const elementHeight = mesHoje.offsetHeight;

          const scrollPosition =
            elementTop - containerHeight / 2 + elementHeight / 2;

          if (typeof container.scrollTo === "function") {
            container.scrollTo({
              top: Math.max(0, scrollPosition),
              behavior: "smooth",
            });
          } else {
            container.scrollTop = Math.max(0, scrollPosition);
          }
        }

        // destaque temporário
        mesHoje.classList.add("dia-foco");
        setTimeout(() => mesHoje.classList.remove("dia-foco"), 2000);
      } catch (error) {
        console.error("Erro ao focar no mês atual:", error);
      }
    },

    // Remove inline styles and unwanted icons inside .data cells to prevent
    // other scripts from shifting layout after render. This is defensive and
    // idempotent.
    sanitizeDataCells() {
      try {
        // Alvo: tanto .lista-meses quanto .lista-dias (compatibilidade)
        const nodes = document.querySelectorAll(
          ".lista-meses .gd-linha-dia .data, .lista-dias .gd-linha-dia .data"
        );

        nodes.forEach((el) => {
          // Remover estilos inline que possam alterar largura/alinhamento
          if (el.hasAttribute("style")) el.removeAttribute("style");

          // Garantir que não estamos forçando tamanhos via JS
          el.style.minWidth = "";
          el.style.maxWidth = "";

          // Remover ícones de calendário reaplicados por scripts terceiros
          el.querySelectorAll("i").forEach((icon) => {
            const cls = (icon.className || "").toLowerCase();
            if (
              cls.includes("calendar") ||
              cls.includes("fa-calendar") ||
              cls.includes("fa-calendar-day") ||
              cls.includes("fa-calendar-alt")
            ) {
              try {
                icon.remove();
              } catch (e) {}
            }
          });
        });
      } catch (e) {
        // silencioso
      }
    },

    // Observador defensivo: observa inserÃ§Ãµes dentro de .lista-meses e
    // remove rapidamente quaisquer inline styles ou Ã­cones que reapareÃ§am.
    configurarObservadorSanitizacao() {
      try {
        const containers = document.querySelectorAll(
          ".lista-meses, .lista-dias"
        );

        // Se nenhum container encontrado, observar body como fallback
        if (!containers || containers.length === 0) {
          this._sanitizerObservers = this._sanitizerObservers || [];
          const mo = new MutationObserver((mutations) => {
            let precisa = false;
            for (const m of mutations) {
              if (m.type === "childList" || m.type === "attributes") {
                precisa = true;
                break;
              }
            }
            if (precisa) {
              clearTimeout(this._sanitizeTimer);
              this._sanitizeTimer = setTimeout(
                () => this.sanitizeDataCells(),
                40
              );
            }
          });
          mo.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
          });
          this._sanitizerObservers.push(mo);
          return;
        }

        this._sanitizerObservers = [];
        containers.forEach((container) => {
          const mo = new MutationObserver((mutations) => {
            let precisa = false;
            for (const m of mutations) {
              if (m.type === "childList" || m.type === "attributes") {
                precisa = true;
                break;
              }
            }
            if (precisa) {
              clearTimeout(this._sanitizeTimer);
              this._sanitizeTimer = setTimeout(
                () => this.sanitizeDataCells(),
                40
              );
            }
          });

          mo.observe(container, {
            childList: true,
            subtree: true,
            attributes: true,
          });
          this._sanitizerObservers.push(mo);
        });
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
        const container = document.querySelector(".lista-meses");
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

        // Adicionar classe de destaque temporÃ¡rio
        diaHoje.classList.add("dia-foco");
        setTimeout(() => {
          diaHoje.classList.remove("dia-foco");
        }, 2000);
      }
    },

    // Focar no período atual: dia (padrão) ou mês quando em modo ANO
    focarPeriodoAtual() {
      if (this.periodoFixo === "ano") {
        // delegar para a função específica de mês
        try {
          this.focarMesAtual();
          return;
        } catch (e) {
          // se falhar, cair para o fallback
        }
      }

      // fallback para foco por dia
      this.focarDiaAtual();
    },

    // Hash dos dados
    gerarHashDados(dados) {
      return JSON.stringify(dados);
    },

    // Configurar interceptadores
    configurarInterceptadores() {
      // Interceptar submissÃ£o de formulÃ¡rios
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

      // Interceptar cliques em botÃµes
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

      // Interceptar mudanÃ§as no filtro de perÃ­odo
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
        console.log("ðŸ›‘ Sistema parado");
      }
    },

    // ForÃ§ar atualizaÃ§Ã£o
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
  //                                    INTEGRAÃ‡ÃƒO COM SISTEMA EXISTENTE
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
    focar: () => ListaDiasManagerCorrigido.focarPeriodoAtual(),
  };

  // Substituir comandos antigos
  window.ListaDias = window.ListaDiasCorrigido;

  console.log("ðŸ“… Sistema corrigido da lista de dias carregado!");
  console.log("ðŸ”§ CorreÃ§Ãµes aplicadas:");
  console.log("  âœ… Exibe TODOS os dias do mÃªs");
  console.log("  âœ… AtualizaÃ§Ã£o em tempo real funcionando");
  console.log("  âœ… Ãcone de trofÃ©u para meta batida");
  console.log("  âœ… DetecÃ§Ã£o automÃ¡tica da meta atual");
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
  //                                  TROFÃ‰U - PARA APARECER  QUANDO A META Ã‰ BATIDA
  // ========================================================================================================================

  // SISTEMA MONITOR CONTÃNUO - VERIFICAÃ‡ÃƒO RIGOROSA DE META
  (function () {
    "use strict";

    console.log("Sistema Monitor ContÃ­nuo - VERIFICAÃ‡ÃƒO RIGOROSA...");

    const MonitorContinuo = {
      ativo: false,
      intervaloMonitor: null,
      intervaloForcador: null,
      intervaloForcaBruta: null,
      estadoCorretoHoje: null,
      ultimoRotulo: "",
      metasBatidasCache: new Map(),
      metaHistoricaCache: new Map(),
      verificandoHistorico: false,
      forcarTrofeuHoje: false,
      ultimoSaldoHoje: 0,
      ultimaMetaHoje: 0,
      // NOVA: ConfiguraÃ§Ã£o rigorosa
      metaDiariaConfigurada: 0,
      modoRigoroso: true,

      inicializar() {
        console.log("Iniciando monitor RIGOROSO...");

        this.ativo = true;
        this.destruirTudo();

        // Primeiro buscar meta real
        this.buscarMetaRealDoSistema();

        // VerificaÃ§Ã£o inicial limpa
        this.limparTodosOsTrofeus();

        // Verificar hoje primeiro
        setTimeout(() => {
          this.verificarMetaDiariaHojeAgora();
        }, 500);

        // VerificaÃ§Ã£o histÃ³rica rigorosa
        setTimeout(() => {
          this.verificarMetasHistoricasRigoroso();
        }, 1000);

        // Monitor principal (cada 1 segundo)
        this.intervaloMonitor = setInterval(() => {
          this.monitorarRotulo();
          this.verificarMetaDiariaHojeAgora();
        }, 1000);

        // ForÃ§ador normal (cada 2 segundos)
        this.intervaloForcador = setInterval(() => {
          this.forcarEstadoCorreto();
        }, 2000);

        // ForÃ§ador BRUTO especificamente para hoje (cada 500ms)
        this.intervaloForcaBruta = setInterval(() => {
          this.forcarTrofeuHojeBruto();
        }, 500);

        console.log("Monitor RIGOROSO ativo");
      },

      destruirTudo() {
        console.log("DESTRUINDO sistemas de trofÃ©u...");

        // Parar intervalos
        const maxId = setTimeout(() => {}, 0);
        for (let i = 1; i <= maxId; i++) {
          try {
            clearInterval(i);
            clearTimeout(i);
          } catch (e) {}
        }

        // Desabilitar sistemas conhecidos
        const sistemas = [
          "SistemaTrofeuCompleto",
          "SistemaTrofeuIntegrado",
          "SistemaTrofeuCorrigido",
          "SistemaTrofeuNumerico",
          "SistemaTrofeuFinal",
          "TrofeuUltraAgressivo",
          "TrofeuCirurgico",
          "TrofeuDefinitivo",
          "SistemaDestruidor",
          "OverriderFinal",
          "SistemaSimples",
          "MetaDiariaManager",
        ];

        sistemas.forEach((nome) => {
          if (window[nome] && nome !== "MonitorContinuo") {
            try {
              window[nome].ativo = false;
              if (window[nome].parar) window[nome].parar();
              window[nome] = null;
              delete window[nome];
            } catch (e) {}
          }
        });
      },

      // NOVA FUNÃ‡ÃƒO: Buscar meta real do sistema
      async buscarMetaRealDoSistema() {
        try {
          console.log("Buscando meta MENSAL real do sistema...");

          let metaEncontrada = 0;
          let tipoMeta = "mensal";

          // Fonte 1: dados-ano-info - PRIORIZAR META MENSAL
          const dadosAnoInfo = document.getElementById("dados-ano-info");
          if (dadosAnoInfo) {
            // Primeiro tentar meta mensal direta
            const metaMensal = dadosAnoInfo.getAttribute("data-meta-mensal");
            if (metaMensal && parseFloat(metaMensal) > 0) {
              metaEncontrada = parseFloat(metaMensal);
              tipoMeta = "mensal";
              console.log(
                `Meta MENSAL do dados-ano-info: R$ ${metaEncontrada.toFixed(2)}`
              );
            } else {
              // Se não tem meta mensal, calcular da anual
              const metaAnual = dadosAnoInfo.getAttribute("data-meta-anual");
              if (metaAnual && parseFloat(metaAnual) > 0) {
                metaEncontrada = parseFloat(metaAnual) / 12;
                tipoMeta = "anual/12";
                console.log(
                  `Meta MENSAL calculada (anual ÷ 12): R$ ${metaEncontrada.toFixed(
                    2
                  )}`
                );
              }
            }
          }

          // Fonte 2: Verificar período selecionado
          if (metaEncontrada === 0) {
            const radioSelecionado = document.querySelector(
              'input[name="periodo"]:checked'
            );
            const periodoAtual = radioSelecionado
              ? radioSelecionado.value
              : "ano";

            if (periodoAtual === "ano" && dadosAnoInfo) {
              const metaAnual =
                parseFloat(dadosAnoInfo.getAttribute("data-meta-anual")) || 0;
              if (metaAnual > 0) {
                metaEncontrada = metaAnual / 12;
                tipoMeta = "anual/12 (período ano)";
                console.log(
                  `Meta MENSAL do período ano: R$ ${metaEncontrada.toFixed(2)}`
                );
              }
            } else if (periodoAtual === "mes" && dadosAnoInfo) {
              const metaMensal =
                parseFloat(dadosAnoInfo.getAttribute("data-meta-mensal")) || 0;
              if (metaMensal > 0) {
                metaEncontrada = metaMensal;
                tipoMeta = "mensal (período mês)";
                console.log(
                  `Meta MENSAL do período mês: R$ ${metaEncontrada.toFixed(2)}`
                );
              }
            }
          }

          // Fonte 3: PHP como fallback
          if (metaEncontrada === 0) {
            try {
              const response = await fetch("dados_banca.php?periodo=ano", {
                method: "GET",
                headers: {
                  "Cache-Control": "no-cache",
                  "X-Requested-With": "XMLHttpRequest",
                },
              });

              if (response.ok) {
                const data = await response.json();
                if (data.success) {
                  // Priorizar meta mensal do PHP
                  if (data.meta_mensal && parseFloat(data.meta_mensal) > 0) {
                    metaEncontrada = parseFloat(data.meta_mensal);
                    tipoMeta = "mensal (PHP)";
                  } else if (
                    data.meta_anual &&
                    parseFloat(data.meta_anual) > 0
                  ) {
                    metaEncontrada = parseFloat(data.meta_anual) / 12;
                    tipoMeta = "anual/12 (PHP)";
                  }
                  console.log(
                    `Meta MENSAL do PHP: R$ ${metaEncontrada.toFixed(
                      2
                    )} (${tipoMeta})`
                  );
                }
              }
            } catch (e) {
              console.log("Erro ao buscar meta do PHP:", e);
            }
          }

          // Salvar meta configurada
          this.metaDiariaConfigurada = metaEncontrada; // Mantém o nome mas agora é mensal
          this.ultimaMetaHoje = metaEncontrada;
          this.tipoMetaAtual = tipoMeta;

          console.log(
            `META MENSAL CONFIGURADA: R$ ${metaEncontrada.toFixed(
              2
            )} (${tipoMeta})`
          );

          return metaEncontrada;
        } catch (error) {
          console.error("Erro ao buscar meta mensal real:", error);
          this.metaDiariaConfigurada = 0;
          return 0;
        }
      },

      // NOVA FUNÃ‡ÃƒO: Limpar todos os trofÃ©us inicialmente
      limparTodosOsTrofeus() {
        try {
          console.log(
            "Limpando TODOS os trofÃ©us para verificaÃ§Ã£o rigorosa..."
          );

          // Limpar caches
          this.metasBatidasCache.clear();
          this.metaHistoricaCache.clear();

          // Aplicar checks em todas as linhas
          document.querySelectorAll(".gd-linha-dia").forEach((linha) => {
            const icone = linha.querySelector(".icone i");
            if (icone) {
              this.aplicarCheckForcado(icone, linha);
            }
          });

          console.log(
            "Todos os trofÃ©us limpos - sÃ³ serÃ£o adicionados se meta realmente batida"
          );
        } catch (error) {
          console.error("Erro ao limpar trofÃ©us:", error);
        }
      },

      // FUNÃ‡ÃƒO CORRIGIDA: VerificaÃ§Ã£o rigorosa da meta de hoje
      async verificarMetaDiariaHojeAgora() {
        try {
          const hoje = this.obterDataHoje();

          // 🔧 CORREÇÃO: Para meses, verificar o mês atual, não o dia
          const [ano, mes, dia] = hoje.split("-");
          const chaveMesAtual = `${ano}-${mes}`;

          // Buscar linha do mês atual (não do dia)
          let linha = document.querySelector(`[data-date="${chaveMesAtual}"]`);

          // Se não encontrar o mês, tentar o dia (fallback)
          if (!linha) {
            linha = document.querySelector(`[data-date="${hoje}"]`);
          }

          if (!linha) {
            console.log("Linha do mês/dia atual não encontrada");
            return false;
          }

          // Extrair saldo atual
          const valorElement = linha.querySelector(".valor");
          if (!valorElement) {
            console.log("Elemento valor não encontrado");
            return false;
          }

          const valorTexto = valorElement.textContent
            .replace(/[^\d,-]/g, "")
            .replace(",", ".");
          const saldoAtual = parseFloat(valorTexto) || 0;

          // Usar meta mensal configurada
          let metaMensal = this.metaDiariaConfigurada; // Agora é mensal

          if (metaMensal === 0) {
            metaMensal = await this.buscarMetaRealDoSistema();
          }

          // Salvar para comparação
          this.ultimoSaldoHoje = saldoAtual;
          this.ultimaMetaHoje = metaMensal;

          // 🔧 VERIFICAÇÃO COM META MENSAL
          let metaBatida = false;

          if (metaMensal > 0) {
            metaBatida = saldoAtual >= metaMensal;
            console.log(
              `MÊS ATUAL (${chaveMesAtual}): Saldo R$ ${saldoAtual.toFixed(
                2
              )} ${metaBatida ? ">=" : "<"} Meta Mensal R$ ${metaMensal.toFixed(
                2
              )} = ${metaBatida ? "BATIDA" : "NÃO BATIDA"}`
            );
          } else {
            // Sem meta configurada: critério restritivo para mês
            metaBatida = saldoAtual >= 500; // Valor maior para meta mensal
            console.log(
              `MÊS ATUAL (sem meta): Saldo R$ ${saldoAtual.toFixed(2)} ${
                metaBatida ? ">=" : "<"
              } R$ 500,00 = ${metaBatida ? "BATIDA" : "NÃO BATIDA"}`
            );
          }

          // Atualizar flags
          this.forcarTrofeuHoje = metaBatida;
          this.estadoCorretoHoje = metaBatida;

          // Atualizar cache
          if (metaBatida) {
            this.metasBatidasCache.set(chaveMesAtual, true);
            // Também marcar o dia atual se necessário
            this.metasBatidasCache.set(hoje, true);
          } else {
            this.metasBatidasCache.delete(chaveMesAtual);
            this.metasBatidasCache.delete(hoje);
          }

          return metaBatida;
        } catch (error) {
          console.error("Erro ao verificar meta mensal atual:", error);
          return false;
        }
      },

      // NOVA FUNÃ‡ÃƒO: VerificaÃ§Ã£o histÃ³rica RIGOROSA
      async verificarMetasHistoricasRigoroso() {
        if (this.verificandoHistorico) return;

        this.verificandoHistorico = true;
        console.log("Verificando metas histÃ³ricas RIGOROSAMENTE...");

        try {
          const linhas = document.querySelectorAll(".gd-linha-dia");
          const datasParaVerificar = [];

          linhas.forEach((linha) => {
            const dataLinha = linha.getAttribute("data-date");
            const hoje = this.obterDataHoje();

            if (dataLinha && dataLinha < hoje) {
              datasParaVerificar.push(dataLinha);
            }
          });

          console.log(
            `Verificando RIGOROSAMENTE ${datasParaVerificar.length} datas anteriores`
          );

          // Garantir que temos a meta configurada
          if (this.metaDiariaConfigurada === 0) {
            await this.buscarMetaRealDoSistema();
          }

          for (const data of datasParaVerificar) {
            await this.verificarMetaEspecificaRigoroso(data);
            await new Promise((resolve) => setTimeout(resolve, 100));
          }

          console.log("VerificaÃ§Ã£o histÃ³rica RIGOROSA concluÃ­da");
        } catch (error) {
          console.error(
            "Erro ao verificar metas histÃ³ricas rigorosas:",
            error
          );
        } finally {
          this.verificandoHistorico = false;
        }
      },

      // NOVA FUNÃ‡ÃƒO: VerificaÃ§Ã£o rigorosa de meta especÃ­fica
      async verificarMetaEspecificaRigoroso(data) {
        try {
          // 🔧 DETERMINAR SE É DATA DE DIA OU MÊS
          const isDataMes = data.length === 7; // YYYY-MM
          const isDataDia = data.length === 10; // YYYY-MM-DD

          let linha, saldoVerificar, chaveCache;

          if (isDataMes) {
            // É um mês: YYYY-MM
            linha = document.querySelector(`[data-date="${data}"]`);
            chaveCache = data;
          } else if (isDataDia) {
            // É um dia: buscar o mês correspondente YYYY-MM
            const [ano, mes, dia] = data.split("-");
            const chaveMes = `${ano}-${mes}`;
            linha = document.querySelector(`[data-date="${chaveMes}"]`);
            chaveCache = chaveMes;

            // Se não encontrar o mês, tentar o dia
            if (!linha) {
              linha = document.querySelector(`[data-date="${data}"]`);
              chaveCache = data;
            }
          } else {
            console.log(`Formato de data inválido: ${data}`);
            return;
          }

          if (!linha) {
            console.log(`Linha não encontrada para: ${data}`);
            return;
          }

          const valorElement = linha.querySelector(".valor");
          if (!valorElement) return;

          const valorTexto = valorElement.textContent
            .replace(/[^\d,-]/g, "")
            .replace(",", ".");
          saldoVerificar = parseFloat(valorTexto) || 0;

          // Usar a meta mensal configurada
          const metaMensal = this.metaDiariaConfigurada; // Agora é mensal

          // VERIFICAÇÃO RIGOROSA COM META MENSAL
          let metaBatida = false;
          let criterioUsado = "";

          if (metaMensal > 0) {
            metaBatida = saldoVerificar >= metaMensal;
            criterioUsado = `Meta mensal R$ ${metaMensal.toFixed(2)}`;
          } else {
            // Sem meta configurada: critério restritivo para mês
            metaBatida = saldoVerificar >= 500;
            criterioUsado = "Critério restritivo mensal R$ 500,00";
          }

          console.log(
            `${data}: R$ ${saldoVerificar.toFixed(2)} vs ${criterioUsado} = ${
              metaBatida ? "BATIDA" : "NÃO BATIDA"
            }`
          );

          // Salvar no cache
          this.metaHistoricaCache.set(chaveCache, {
            saldoPeriodo: saldoVerificar,
            metaMensal: metaMensal,
            metaBatida: metaBatida,
            criterioUsado: criterioUsado,
            dataVerificacao: new Date().toISOString(),
            tipoVerificacao: isDataMes ? "mês" : "dia->mês",
          });

          // Atualizar cache de metas batidas
          if (metaBatida) {
            this.metasBatidasCache.set(chaveCache, true);
          } else {
            this.metasBatidasCache.delete(chaveCache);
          }
        } catch (error) {
          console.error(`Erro ao verificar rigorosamente ${data}:`, error);
        }
      },

      monitorarRotulo() {
        try {
          const rotuloElement =
            document.getElementById("rotulo-meta") ||
            document.querySelector(".widget-meta-rotulo");

          if (!rotuloElement) return;

          const rotuloTexto = rotuloElement.textContent.toLowerCase().trim();

          if (rotuloTexto !== this.ultimoRotulo) {
            console.log(`RÃ“TULO MUDOU: "${rotuloTexto}"`);
            this.ultimoRotulo = rotuloTexto;

            // Sempre verificar meta diÃ¡ria real, nÃ£o interpretar rÃ³tulo
            this.verificarMetaDiariaHojeAgora();
          }
        } catch (error) {
          console.error("Erro no monitor de rÃ³tulo:", error);
        }
      },

      forcarTrofeuHojeBruto() {
        if (!this.forcarTrofeuHoje) return;

        try {
          const hoje = this.obterDataHoje();
          const linha = document.querySelector(`[data-date="${hoje}"]`);

          if (!linha) return;

          const icone = linha.querySelector(".icone i");
          if (!icone) return;

          // FORÃ‡A BRUTA: Se deve ter trofÃ©u mas nÃ£o tem, aplicar IMEDIATAMENTE
          if (!icone.classList.contains("fa-trophy")) {
            console.log("FORÃ‡A BRUTA: Aplicando trofÃ©u de hoje");
            this.aplicarTrofeuForcado(icone, linha);
          }
        } catch (error) {
          console.error("Erro na forÃ§a bruta:", error);
        }
      },

      forcarEstadoCorreto() {
        const hoje = this.obterDataHoje();
        let forcacoesFeitas = 0;

        document.querySelectorAll(".gd-linha-dia").forEach((linha) => {
          const icone = linha.querySelector(".icone i");
          const dataLinha = linha.getAttribute("data-date");

          if (!icone || !dataLinha) return;

          const deveSerTrofeu = this.deveExibirTrofeu(dataLinha, hoje);

          if (deveSerTrofeu) {
            if (!icone.classList.contains("fa-trophy")) {
              this.aplicarTrofeuForcado(icone, linha);
              forcacoesFeitas++;
            }
          } else {
            if (!icone.classList.contains("fa-check")) {
              this.aplicarCheckForcado(icone, linha);
              forcacoesFeitas++;
            }
          }
        });

        if (forcacoesFeitas > 0) {
          console.log(`FORÃ‡ADOS: ${forcacoesFeitas} Ã­cones`);
        }
      },

      deveExibirTrofeu(dataLinha, hoje) {
        // Hoje: usar verificaÃ§Ã£o direta
        if (dataLinha === hoje) {
          return this.forcarTrofeuHoje;
        }

        // Anteriores: APENAS se estiver no cache (verificado rigorosamente)
        return this.metasBatidasCache.has(dataLinha);
      },

      aplicarTrofeuForcado(icone, linha) {
        try {
          icone.removeAttribute("style");
          icone.className = "fa-solid fa-trophy trofeu-icone-forcado";
          linha.setAttribute("data-meta-batida", "true");
          linha.classList.add("meta-forcada");

          const dataLinha = linha.getAttribute("data-date");
          if (dataLinha) {
            this.metasBatidasCache.set(dataLinha, true);
          }
        } catch (e) {
          console.error("Erro ao aplicar trofÃ©u:", e);
        }
      },

      aplicarCheckForcado(icone, linha) {
        try {
          icone.removeAttribute("style");
          icone.className = "fa-solid fa-check check-icone-forcado";
          linha.setAttribute("data-meta-batida", "false");
          linha.classList.remove("meta-forcada");
        } catch (e) {
          console.error("Erro ao aplicar check:", e);
        }
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

        if (this.intervaloMonitor) {
          clearInterval(this.intervaloMonitor);
          this.intervaloMonitor = null;
        }

        if (this.intervaloForcador) {
          clearInterval(this.intervaloForcador);
          this.intervaloForcador = null;
        }

        if (this.intervaloForcaBruta) {
          clearInterval(this.intervaloForcaBruta);
          this.intervaloForcaBruta = null;
        }

        console.log("Monitor rigoroso parado");
      },

      status() {
        return {
          ativo: this.ativo,
          modoRigoroso: this.modoRigoroso,
          metaDiariaConfigurada: this.metaDiariaConfigurada,
          forcarTrofeuHoje: this.forcarTrofeuHoje,
          ultimoSaldoHoje: this.ultimoSaldoHoje,
          metasBatidasCache: Array.from(this.metasBatidasCache.keys()).sort(),
          totalMetasBatidas: this.metasBatidasCache.size,
          metasHistoricasVerificadas: this.metaHistoricaCache.size,
          trofeusVisiveis: document.querySelectorAll(".fa-trophy").length,
          checksVisiveis: document.querySelectorAll(".fa-check").length,
          verificandoHistorico: this.verificandoHistorico,
          modo: "RIGOROSO - SÃ³ trofÃ©u se meta realmente batida",
        };
      },
    };

    // Comandos globais
    window.MonitorContinuo = {
      status: () => {
        const s = MonitorContinuo.status();
        console.log("MONITOR RIGOROSO STATUS:");
        Object.entries(s).forEach(([key, value]) => {
          console.log(`   ${key}: ${value}`);
        });
        return s;
      },

      parar: () => {
        MonitorContinuo.parar();
      },

      reiniciar: () => {
        MonitorContinuo.parar();
        setTimeout(() => MonitorContinuo.inicializar(), 1000);
      },

      // Comando para reverificar TUDO rigorosamente
      reverificarRigoroso: async () => {
        console.log("ReverificaÃ§Ã£o RIGOROSA iniciada...");

        // Buscar meta real primeiro
        await MonitorContinuo.buscarMetaRealDoSistema();

        // Limpar tudo
        MonitorContinuo.limparTodosOsTrofeus();

        // Verificar hoje
        await MonitorContinuo.verificarMetaDiariaHojeAgora();

        // Verificar histÃ³rico
        await MonitorContinuo.verificarMetasHistoricasRigoroso();

        // Aplicar resultados
        MonitorContinuo.forcarEstadoCorreto();

        const metasBatidas = Array.from(
          MonitorContinuo.metasBatidasCache.keys()
        ).sort();
        console.log(
          `RESULTADO RIGOROSO: ${metasBatidas.length} datas com meta REALMENTE batida:`,
          metasBatidas
        );

        return metasBatidas;
      },

      // Ver cache detalhado com informaÃ§Ãµes de Meta Fixa vs Turbo
      verCache: () => {
        const historico = Array.from(
          MonitorContinuo.metaHistoricaCache.entries()
        );
        console.log("CACHE RIGOROSO COM DETALHES DE META:");
        historico.forEach(([data, info]) => {
          console.log(
            `  ${data}: R$ ${info.saldoDia.toFixed(2)} vs ${
              info.criterioUsado
            } = ${info.metaBatida ? "BATIDA" : "NÃƒO BATIDA"}`
          );
          if (info.detalhesCalculo) {
            console.log(`    ${info.detalhesCalculo.observacao}`);
            console.log(`    FÃ³rmula: ${info.detalhesCalculo.formula}`);
          }
        });
        return historico;
      },

      // Debug detalhado com informaÃ§Ãµes de Meta Turbo
      debug: () => {
        console.log("DEBUG RIGOROSO COM META TURBO:");
        console.log(
          `  Meta configurada base: R$ ${MonitorContinuo.metaDiariaConfigurada.toFixed(
            2
          )}`
        );
        console.log(`  Modo rigoroso: ${MonitorContinuo.modoRigoroso}`);
        console.log(
          `  Total trofÃ©us vÃ¡lidos: ${MonitorContinuo.metasBatidasCache.size}`
        );

        const hoje = MonitorContinuo.obterDataHoje();
        const linha = document.querySelector(`[data-date="${hoje}"]`);

        if (linha) {
          const icone = linha.querySelector(".icone i");
          const valor = linha.querySelector(".valor");

          console.log("  HOJE:");
          console.log(`    Data: ${hoje}`);
          console.log(`    Saldo: ${valor ? valor.textContent : "N/A"}`);
          console.log(`    Ãcone: ${icone ? icone.className : "N/A"}`);
          console.log(
            `    Deve ter trofÃ©u: ${MonitorContinuo.forcarTrofeuHoje}`
          );

          // Mostrar detalhes do cache se existir
          const cacheHoje = MonitorContinuo.metaHistoricaCache.get(hoje);
          if (cacheHoje && cacheHoje.detalhesCalculo) {
            console.log(`    Tipo de meta: ${cacheHoje.detalhesCalculo.tipo}`);
            console.log(`    ${cacheHoje.detalhesCalculo.observacao}`);
            console.log(`    FÃ³rmula: ${cacheHoje.detalhesCalculo.formula}`);
          }
        }
      },

      // NOVO: Debug especÃ­fico para uma data
      debugData: async (data) => {
        const linha = document.querySelector(`[data-date="${data}"]`);

        if (linha) {
          const icone = linha.querySelector(".icone i");
          const valor = linha.querySelector(".valor");
          const cacheInfo = MonitorContinuo.metaHistoricaCache.get(data);

          console.log(`DEBUG DETALHADO ${data}:`);
          console.log("  Saldo na tela:", valor ? valor.textContent : "N/A");
          console.log("  Ãcone atual:", icone ? icone.className : "N/A");
          console.log(
            "  Cache tem trofÃ©u:",
            MonitorContinuo.metasBatidasCache.has(data)
          );

          if (cacheInfo) {
            console.log("  Cache detalhado:", cacheInfo);
            if (cacheInfo.detalhesCalculo) {
              console.log(`  Tipo: ${cacheInfo.detalhesCalculo.tipo}`);
              console.log(`  ${cacheInfo.detalhesCalculo.observacao}`);
              console.log(`  FÃ³rmula: ${cacheInfo.detalhesCalculo.formula}`);
            }
          } else {
            console.log("  NÃ£o hÃ¡ dados no cache - recalculando...");

            // Recalcular para esta data
            const valorTexto = valor
              ? valor.textContent.replace(/[^\d,-]/g, "").replace(",", ".")
              : "0";
            const saldoDia = parseFloat(valorTexto) || 0;

            const dadosMetaEspecifica =
              await MonitorContinuo.calcularMetaParaDataEspecifica(
                data,
                saldoDia
              );
            console.log("  CÃ¡lculo especÃ­fico:", dadosMetaEspecifica);
          }
        } else {
          console.log(`Linha nÃ£o encontrada para data ${data}`);
        }
      },

      // NOVO: Comparar Meta Fixa vs Meta Turbo para uma data especÃ­fica
      compararMetas: async (data) => {
        console.log(`COMPARANDO META FIXA vs TURBO para ${data}:`);

        const linha = document.querySelector(`[data-date="${data}"]`);
        if (!linha) {
          console.log("Data nÃ£o encontrada");
          return;
        }

        const valorElement = linha.querySelector(".valor");
        const saldoDia = valorElement
          ? parseFloat(
              valorElement.textContent.replace(/[^\d,-]/g, "").replace(",", ".")
            ) || 0
          : 0;

        // Buscar configuraÃ§Ã£o atual
        const config = await MonitorContinuo.buscarConfiguracaoCompleta();
        if (!config) {
          console.log("Erro ao buscar configuraÃ§Ã£o");
          return;
        }

        // Calcular Meta Fixa
        const metaFixa =
          config.bancaInicial * (config.diaria / 100) * config.unidade;
        const resultadoFixa = saldoDia >= metaFixa;

        // Calcular Meta Turbo
        const lucroAcumulado =
          await MonitorContinuo.calcularLucroAcumuladoAteData(data);
        const bancaTurbo =
          lucroAcumulado > 0
            ? config.bancaInicial + lucroAcumulado
            : config.bancaInicial;
        const metaTurbo = bancaTurbo * (config.diaria / 100) * config.unidade;
        const resultadoTurbo = saldoDia >= metaTurbo;

        console.log(`  Saldo do dia: R$ ${saldoDia.toFixed(2)}`);
        console.log(`  Banca inicial: R$ ${config.bancaInicial.toFixed(2)}`);
        console.log(
          `  Lucro acumulado atÃ© ${data}: R$ ${lucroAcumulado.toFixed(2)}`
        );
        console.log(`  ConfiguraÃ§Ã£o: ${config.diaria}% Ã— ${config.unidade}`);
        console.log("");
        console.log(`  META FIXA:`);
        console.log(
          `    Base: R$ ${config.bancaInicial.toFixed(
            2
          )} (sempre banca inicial)`
        );
        console.log(`    Meta: R$ ${metaFixa.toFixed(2)}`);
        console.log(
          `    Resultado: ${resultadoFixa ? "BATIDA" : "NÃƒO BATIDA"}`
        );
        console.log("");
        console.log(`  META TURBO:`);
        console.log(
          `    Base: R$ ${bancaTurbo.toFixed(2)} (banca inicial ${
            lucroAcumulado > 0 ? "+ lucro" : "sem lucro"
          })`
        );
        console.log(`    Meta: R$ ${metaTurbo.toFixed(2)}`);
        console.log(
          `    Resultado: ${resultadoTurbo ? "BATIDA" : "NÃƒO BATIDA"}`
        );
        console.log("");
        console.log(
          `  TIPO ATUAL CONFIGURADO: ${config.tipoMeta.toUpperCase()}`
        );
        console.log(
          `  RESULTADO APLICADO: ${
            config.tipoMeta === "fixa"
              ? resultadoFixa
                ? "BATIDA"
                : "NÃƒO BATIDA"
              : resultadoTurbo
              ? "BATIDA"
              : "NÃƒO BATIDA"
          }`
        );

        return {
          saldoDia,
          metaFixa: { valor: metaFixa, batida: resultadoFixa },
          metaTurbo: { valor: metaTurbo, batida: resultadoTurbo },
          tipoAtual: config.tipoMeta,
          resultadoAplicado:
            config.tipoMeta === "fixa" ? resultadoFixa : resultadoTurbo,
        };
      },

      // Configurar meta manualmente
      configurarMeta: (valor) => {
        MonitorContinuo.metaDiariaConfigurada = parseFloat(valor) || 0;
        console.log(
          `Meta configurada manualmente: R$ ${MonitorContinuo.metaDiariaConfigurada.toFixed(
            2
          )}`
        );

        // Reverificar tudo com nova meta
        setTimeout(() => {
          MonitorContinuo.reverificarRigoroso();
        }, 100);
      },
    };

    // Compatibilidade
    window.Trofeu = window.MonitorContinuo;

    // Interceptar mudanÃ§as de perÃ­odo
    function interceptarMudancasPeriodo() {
      const radios = document.querySelectorAll('input[name="periodo"]');

      radios.forEach((radio) => {
        radio.addEventListener("change", function (e) {
          console.log(`INTERCEPTADO: MudanÃ§a para ${e.target.value}`);

          setTimeout(() => {
            MonitorContinuo.verificarMetaDiariaHojeAgora();
            MonitorContinuo.forcarTrofeuHojeBruto();
          }, 200);

          setTimeout(() => {
            MonitorContinuo.forcarEstadoCorreto();
          }, 500);
        });
      });
    }

    // InicializaÃ§Ã£o
    function iniciar() {
      if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", () => {
          setTimeout(() => {
            MonitorContinuo.inicializar();
            interceptarMudancasPeriodo();
          }, 1000);
        });
      } else {
        setTimeout(() => {
          MonitorContinuo.inicializar();
          interceptarMudancasPeriodo();
        }, 1000);
      }
    }

    iniciar();

    console.log("MONITOR RIGOROSO CARREGADO!");
    console.log("Funcionalidades:");
    console.log("   - VerificaÃ§Ã£o RIGOROSA de metas");
    console.log(
      "   - Limpa todos os trofÃ©us e sÃ³ adiciona se meta realmente batida"
    );
    console.log("   - Busca meta real do sistema");
    console.log("   - CritÃ©rio restritivo se nÃ£o hÃ¡ meta configurada");
    console.log("");
    console.log("Comandos:");
    console.log(
      "   MonitorContinuo.reverificarRigoroso() - Reverificar com rigor"
    );
    console.log(
      "   MonitorContinuo.configurarMeta(100) - Configurar meta manualmente"
    );
    console.log(
      "   MonitorContinuo.verCache() - Ver verificaÃ§Ãµes detalhadas"
    );
    console.log("   MonitorContinuo.debug() - Debug rigoroso");
  })();
  // ========================================================================================================================
  //                                 FIM  TROFÃ‰U - PARA APARECER  QUANDO A META Ã‰ BATIDA
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
    console.log("ðŸ›‘ ListaDiasManagerCorrigido parado");
  }

  if (
    typeof SistemaTrofeuCompleto !== "undefined" &&
    SistemaTrofeuCompleto.intervaloAtualizacao
  ) {
    clearInterval(SistemaTrofeuCompleto.intervaloAtualizacao);
    console.log("ðŸ›‘ SistemaTrofeuCompleto parado");
  }

  if (typeof SistemaMonitorCores !== "undefined") {
    SistemaMonitorCores.parar();
    console.log("ðŸ›‘ SistemaMonitorCores parado");
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
  // SISTEMA ÃšNICO E EFICIENTE
  // ========================================

  const SistemaUnicoSemConflito = {
    intervalo: null,
    ultimaAtualizacao: "",
    metaAtual: 50,
    _ultimaExecucaoProcessar: 0,

    // FunÃ§Ã£o principal que faz TUDO de uma vez
    processarCompleto() {
      const agoraTs = Date.now();
      // Evitar reexecuÃ§Ãµes muito rÃ¡pidas que competem com re-renders
      if (agoraTs - this._ultimaExecucaoProcessar < 400) return;
      this._ultimaExecucaoProcessar = agoraTs;

      const linhas = document.querySelectorAll(".gd-linha-dia");
      if (linhas.length === 0) return;

      let alteracoes = 0;

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

        // Aplicar classe APENAS se nÃ£o tiver
        if (!linha.classList.contains(classeCorreta)) {
          linha.classList.remove(
            "valor-positivo",
            "valor-negativo",
            "valor-zero"
          );
          linha.classList.add(classeCorreta);
          alteracoes++;
        }

        // Aplicar Ã­cone de trofÃ©u se meta batida
        const iconeEl = linha.querySelector(".icone i");
        if (iconeEl && valor >= this.metaAtual) {
          if (!iconeEl.classList.contains("fa-trophy")) {
            iconeEl.classList.remove("fa-check");
            iconeEl.classList.add("fa-trophy", "trofeu-icone", "fa-solid");
            linha.setAttribute("data-meta-batida", "true");
          }
        } else if (iconeEl) {
          if (!iconeEl.classList.contains("fa-check")) {
            iconeEl.classList.remove("fa-trophy", "trofeu-icone");
            iconeEl.classList.add("fa-check", "fa-solid");
            linha.setAttribute("data-meta-batida", "false");
          }
        }
      });

      if (alteracoes > 0) {
        console.log(`âœ… Sistema Ãºnico: ${alteracoes} alteraÃ§Ãµes aplicadas`);
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

    // Inicializar sistema Ãºnico
    iniciar() {
      console.log("ðŸš€ Iniciando sistema Ãºnico sem conflitos...");

      // Detectar meta
      this.detectarMeta();

      // Processar imediatamente
      this.processarCompleto();

      // Intervalo ÃšNICO de 5 segundos (mais espaÃ§ado para evitar conflitos)
      this.intervalo = setInterval(() => {
        this.processarCompleto();
      }, 5000);

      // Hook simples no fetch
      const originalFetch = window.fetch;
      window.fetch = async function (...args) {
        const response = await originalFetch.apply(this, args);

        // Aguardar resposta e processar apÃ³s delay
        setTimeout(() => {
          if (SistemaUnicoSemConflito) {
            SistemaUnicoSemConflito.processarCompleto();
          }
        }, 1000);

        return response;
      };

      console.log("âœ… Sistema Ãºnico ativo - intervalo de 5 segundos");
    },

    // Parar sistema
    parar() {
      if (this.intervalo) {
        clearInterval(this.intervalo);
        this.intervalo = null;
        console.log("ðŸ›‘ Sistema Ãºnico parado");
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

  // Sobrescrever variÃ¡veis globais para evitar reativaÃ§Ã£o
  window.ListaDiasManagerCorrigido = null;
  window.SistemaTrofeuCompleto = null;
  window.SistemaMonitorCores = null;
  window.BackupCores = null;
  // NÃ£o sobrescrever MetaMensalManager - isso interrompe o carregamento/atualizaÃ§Ã£o da meta.
  // Preservamos o gerenciador de meta para que o sistema mensal continue funcionando.
  // Se necessÃ¡rio descomente a linha abaixo para forÃ§ar limpeza (nÃ£o recomendado):
  // window.MetaMensalManager = null;

  // Comandos globais simplificados
  window.SistemaUnico = {
    iniciar: () => SistemaUnicoSemConflito.iniciar(),
    parar: () => SistemaUnicoSemConflito.parar(),
    processar: () => SistemaUnicoSemConflito.processarCompleto(),
    status: () => SistemaUnicoSemConflito.status(),
    info: () => {
      const status = SistemaUnicoSemConflito.status();
      console.log("ðŸ“Š Status Sistema Ãšnico:", status);
      return status;
    },
  };

  // Comandos de compatibilidade
  window.Cores = window.SistemaUnico;
  window.ListaDias = window.SistemaUnico;
  window.Trofeu = window.SistemaUnico;

  // ========================================
  // INICIALIZAÃ‡ÃƒO AUTOMÃTICA
  // ========================================

  function inicializarSistemaUnico() {
    // Aguardar elementos estarem prontos
    setTimeout(() => {
      SistemaUnicoSemConflito.iniciar();
    }, 2000);
  }

  // Rolagem para a linha do dia atual (gd-dia-hoje) dentro de .lista-meses
  // Rolagem para a linha do dia atual (gd-dia-hoje) com fallback robusto
  function scrollToHoje() {
    try {
      const hojeEl = document.querySelector(".gd-dia-hoje");
      if (!hojeEl) return false;

      // Encontra o ancestral rolÃ¡vel mais prÃ³ximo
      function findScrollableAncestor(el) {
        let parent = el.parentElement;
        while (parent && parent !== document.body) {
          const style = window.getComputedStyle(parent);
          const overflowY = style.overflowY;
          if (
            (overflowY === "auto" || overflowY === "scroll") &&
            parent.scrollHeight > parent.clientHeight
          ) {
            return parent;
          }
          parent = parent.parentElement;
        }
        // fallback: pÃ¡gina inteira
        return document.scrollingElement || document.documentElement;
      }

      const container = findScrollableAncestor(hojeEl);

      // Se o container for o documento, usar scrollIntoView no elemento
      if (
        container === document.scrollingElement ||
        container === document.documentElement
      ) {
        if (typeof hojeEl.scrollIntoView === "function") {
          hojeEl.scrollIntoView({
            behavior: "smooth",
            block: "center",
            inline: "nearest",
          });
        } else {
          const rect = hojeEl.getBoundingClientRect();
          const absoluteY = window.scrollY + rect.top;
          window.scrollTo({
            top: absoluteY - window.innerHeight / 2 + rect.height / 2,
            behavior: "smooth",
          });
        }
      } else {
        // Calcular posiÃ§Ã£o relativa ao container e rolar esse container
        const elRect = hojeEl.getBoundingClientRect();
        const containerRect = container.getBoundingClientRect();
        const offsetTop = elRect.top - containerRect.top + container.scrollTop;
        const targetScrollTop = Math.max(
          0,
          offsetTop - container.clientHeight / 2 + hojeEl.clientHeight / 2
        );
        if (typeof container.scrollTo === "function") {
          container.scrollTo({ top: targetScrollTop, behavior: "smooth" });
        } else {
          container.scrollTop = targetScrollTop;
        }
      }

      return true;
    } catch (e) {
      console.error("Erro ao rolar para hoje:", e);
      return false;
    }
  }

  // Inicializar baseado no estado do DOM
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", inicializarSistemaUnico);
  } else {
    inicializarSistemaUnico();
  }

  // Tentar rolar para hoje agora; se o elemento ainda nÃ£o existir ou for criado depois,
  // usar MutationObserver para disparar quando a linha for adicionada.
  (function ensureScrollToHoje() {
    const tried = scrollToHoje();
    if (tried) return;

    // Observar a lista de dias se existir, senÃ£o observar o body
    const lista = document.querySelector(".lista-meses") || document.body;
    if (!lista) return;

    const mo = new MutationObserver((mutations, observer) => {
      if (document.querySelector(".gd-dia-hoje")) {
        scrollToHoje();
        observer.disconnect();
      }
    });

    mo.observe(lista, { childList: true, subtree: true });

    // Timeout de seguranÃ§a para desconectar o observer apÃ³s 6s
    setTimeout(() => {
      try {
        mo.disconnect();
      } catch (e) {}
    }, 6000);
  })();

  console.log("ðŸŽ¯ Sistema Ãšnico Sem Conflitos carregado!");
  console.log("ðŸ“‹ CaracterÃ­sticas:");
  console.log("   âœ… Um Ãºnico intervalo de 5 segundos");
  console.log("   âœ… NÃ£o reconstrÃ³i HTML desnecessariamente");
  console.log("   âœ… Aplica cores e trofÃ©us juntos");
  console.log("   âœ… Remove todos os sistemas conflitantes");
  console.log("");
  console.log("ðŸ”§ Comandos Ãºnicos:");
  console.log("   SistemaUnico.status() - Ver status");
  console.log("   SistemaUnico.processar() - Processar agora");
  console.log("   SistemaUnico.parar() - Parar sistema");

  // Export para uso
  window.SistemaUnicoSemConflito = SistemaUnicoSemConflito;
  // ========================================================================================================================
  //                                FIM AS CORES DO CSS PARA FICAR FIXA FUNCIONANDO
  // ========================================================================================================================
  //
  //
  //
  //
})();

//
//
