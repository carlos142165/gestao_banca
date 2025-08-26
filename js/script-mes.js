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
const MetaMensalManager = {
  // ✅ CONTROLE SIMPLES PARA META MENSAL
  atualizandoAtualmente: false,
  periodoFixo: "mes", // ✅ SEMPRE MENSAL
  tipoMetaAtual: "turbo", // ✅ Será definido pelo banco

  // ✅ ATUALIZAR META MENSAL - VERSÃO ESPECÍFICA
  async atualizarMetaMensal(aguardarDados = false) {
    if (this.atualizandoAtualmente) return null;

    this.atualizandoAtualmente = true;

    try {
      // ✅ SE AGUARDAR DADOS, DAR UM PEQUENO DELAY
      if (aguardarDados) {
        await new Promise((resolve) => setTimeout(resolve, 150));
      }

      // ✅ REQUISIÇÃO FORÇANDO PERÍODO MENSAL
      const response = await fetch("dados_banca.php", {
        method: "GET",
        headers: {
          "Cache-Control": "no-cache",
          "X-Requested-With": "XMLHttpRequest",
          "X-Periodo-Filtro": "mes", // ✅ SEMPRE MÊS
        },
      });

      if (!response.ok) throw new Error(`HTTP ${response.status}`);

      const data = await response.json();
      if (!data.success) throw new Error(data.message);

      // ✅ ATUALIZAR ESTADOS COM DADOS DO SERVIDOR
      if (data.tipo_meta) {
        this.tipoMetaAtual = data.tipo_meta;
      }

      // ✅ PROCESSAR DADOS PARA MENSAL E ATUALIZAR INTERFACE
      const dadosProcessados = this.processarDadosMensais(data);
      this.atualizarTodosElementosMensais(dadosProcessados);

      return dadosProcessados;
    } catch (error) {
      console.error("❌ Erro Meta Mensal:", error);
      this.mostrarErroMetaMensal();
      return null;
    } finally {
      this.atualizandoAtualmente = false;
    }
  },

  // ✅ PROCESSAR DADOS ESPECIFICAMENTE PARA MENSAL
  processarDadosMensais(data) {
    try {
      // ✅ SEMPRE USAR META MENSAL
      const metaFinal = parseFloat(data.meta_mensal) || 0;
      const rotuloFinal = "Meta do Mês";

      // ✅ PEGAR LUCRO DO MÊS (FILTRADO)
      const lucroMensal = parseFloat(data.lucro) || 0; // Já vem filtrado do servidor

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
        lucro_periodo: lucroMensal, // Lucro específico do mês
      };
    } catch (error) {
      console.error("❌ Erro ao processar dados mensais:", error);
      return data;
    }
  },

  // ✅ ATUALIZAR TODOS OS ELEMENTOS - VERSÃO PARA BLOCO 2
  atualizarTodosElementosMensais(data) {
    try {
      // ✅ USAR LUCRO DO MÊS (JÁ FILTRADO)
      const saldoMes =
        parseFloat(data.lucro_periodo) || parseFloat(data.lucro) || 0;
      const metaCalculada = parseFloat(data.meta_display) || 0;
      const bancaTotal = parseFloat(data.banca) || 0;

      const dadosComplementados = {
        ...data,
        meta_original: data.meta_original || metaCalculada,
      };

      const resultado = this.calcularMetaFinalMensal(
        saldoMes,
        metaCalculada,
        bancaTotal,
        dadosComplementados
      );

      // Atualizar elementos do bloco 2
      this.atualizarMetaElementoMensal(resultado);
      this.atualizarRotuloMensal(resultado.rotulo);
      this.atualizarBarraProgressoMensal(resultado, data);

      // ✅ LOG ESPECÍFICO PARA MENSAL
      console.log(`🎯 Meta MENSAL atualizada`);
      console.log(`💰 Lucro do MÊS: R$ ${saldoMes.toFixed(2)}`);
      console.log(
        `🎯 Meta MENSAL (${
          data.tipo_meta_texto || "Meta Turbo"
        }): R$ ${metaCalculada.toFixed(2)}`
      );
      console.log(
        `📅 Dias restantes no mês: ${data.dias_restantes_mes || "N/A"}`
      );
    } catch (error) {
      console.error("❌ Erro ao atualizar elementos mensais:", error);
    }
  },

  // ✅ CALCULAR META FINAL - VERSÃO PARA MENSAL
  calcularMetaFinalMensal(saldoMes, metaCalculada, bancaTotal, data) {
    try {
      let metaFinal, rotulo, statusClass;

      console.log(`🔍 DEBUG CALCULAR META MENSAL:`);
      console.log(`   Saldo MÊS: R$ ${saldoMes.toFixed(2)}`);
      console.log(`   Meta MENSAL: R$ ${metaCalculada.toFixed(2)}`);
      console.log(`   Banca: R$ ${bancaTotal.toFixed(2)}`);

      if (bancaTotal <= 0) {
        metaFinal = bancaTotal;
        rotulo = "Deposite p/ Começar";
        statusClass = "sem-banca";
        console.log(`📊 RESULTADO MENSAL: Sem banca`);
      }
      // ✅ META MENSAL BATIDA OU SUPERADA
      else if (saldoMes > 0 && metaCalculada > 0 && saldoMes >= metaCalculada) {
        metaFinal = 0;
        rotulo = `Meta do Mês Batida! <i class='fa-solid fa-trophy'></i>`;
        statusClass = "meta-batida";
        console.log(
          `🎯 META MENSAL BATIDA: ${saldoMes.toFixed(
            2
          )} >= ${metaCalculada.toFixed(2)}`
        );
      }
      // ✅ CASO ESPECIAL: Meta mensal é zero (já foi batida)
      else if (metaCalculada === 0 && saldoMes > 0) {
        metaFinal = 0;
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
        // ✅ Lucro positivo mas menor que a meta mensal
        metaFinal = metaCalculada - saldoMes;
        rotulo = `Restando p/ Meta do Mês`;
        statusClass = "lucro";
        console.log(`📊 RESULTADO MENSAL: Lucro insuficiente`);
      }

      const resultado = {
        metaFinal,
        metaFinalFormatada: metaFinal.toLocaleString("pt-BR", {
          style: "currency",
          currency: "BRL",
        }),
        rotulo,
        statusClass,
      };

      console.log(`🏁 RESULTADO FINAL MENSAL:`);
      console.log(`   Status: ${statusClass}`);

      return resultado;
    } catch (error) {
      console.error("❌ Erro ao calcular meta final mensal:", error);
      return {
        metaFinal: 0,
        metaFinalFormatada: "R$ 0,00",
        rotulo: "Erro no cálculo",
        statusClass: "erro",
      };
    }
  },

  // ✅ ATUALIZAR META ELEMENTO - BLOCO 2
  atualizarMetaElementoMensal(resultado) {
    try {
      const metaValor = document.getElementById("meta-valor-2");

      if (!metaValor) {
        console.warn("⚠️ Elemento meta-valor-2 não encontrado");
        return;
      }

      let valorTexto = metaValor.querySelector(".valor-texto-2");

      if (valorTexto) {
        valorTexto.textContent = resultado.metaFinalFormatada;
      } else {
        metaValor.innerHTML = `
          <i class="fa-solid-2 fa-coins-2"></i>
          <span class="valor-texto-2" id="valor-texto-meta-2">${resultado.metaFinalFormatada}</span>
        `;
      }

      // ✅ APLICAR CLASSES COM SUFIXO -2
      metaValor.className = metaValor.className.replace(
        /\bvalor-meta-2\s+\w+/g,
        ""
      );
      metaValor.classList.add("valor-meta-2", resultado.statusClass);
    } catch (error) {
      console.error("❌ Erro ao atualizar meta elemento mensal:", error);
    }
  },

  // ✅ ATUALIZAR RÓTULO - BLOCO 2
  atualizarRotuloMensal(rotulo) {
    try {
      const rotuloElement = document.getElementById("rotulo-meta-2");

      if (rotuloElement) {
        rotuloElement.innerHTML = rotulo;
      } else {
        console.warn("⚠️ Elemento rotulo-meta-2 não encontrado");
      }
    } catch (error) {
      console.error("❌ Erro ao atualizar rótulo mensal:", error);
    }
  },

  // ✅ ATUALIZAR BARRA PROGRESSO - BLOCO 2
  atualizarBarraProgressoMensal(resultado, data) {
    try {
      const barraProgresso = document.getElementById("barra-progresso-2");
      const saldoInfo = document.getElementById("saldo-info-2");
      const porcentagemBarra = document.getElementById("porcentagem-barra-2");

      if (!barraProgresso) {
        console.warn("⚠️ Elemento barra-progresso-2 não encontrado");
        return;
      }

      const saldoMes =
        parseFloat(data.lucro_periodo) || parseFloat(data.lucro) || 0;
      const metaCalculada = parseFloat(data.meta_display) || 0;
      const bancaTotal = parseFloat(data.banca) || 0;

      // Calcular progresso
      let progresso = 0;
      if (bancaTotal > 0 && metaCalculada > 0) {
        if (resultado.statusClass === "meta-batida") {
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

      // ✅ LIMPAR CLASSES ANTIGAS COM SUFIXO -2
      let classeCor = "";
      barraProgresso.className = barraProgresso.className.replace(
        /\bbarra-\w+-2/g,
        ""
      );

      if (!barraProgresso.classList.contains("widget-barra-progresso-2")) {
        barraProgresso.classList.add("widget-barra-progresso-2");
      }

      // ✅ APLICAR CLASSE CORRETA COM SUFIXO -2
      if (resultado.statusClass === "meta-batida") {
        classeCor = "barra-meta-batida-2";
        console.log(
          `✅ BARRA META MENSAL BATIDA - Saldo: R$ ${saldoMes.toFixed(
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

      // ✅ APLICAR CLASSE E ESTILOS
      barraProgresso.classList.add(classeCor);
      barraProgresso.style.width = `${larguraBarra}%`;
      barraProgresso.style.backgroundColor = "";
      barraProgresso.style.background = "";

      // ✅ PORCENTAGEM
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

      // ✅ SALDO INFO MENSAL
      if (saldoInfo) {
        const saldoFormatado = saldoMes.toLocaleString("pt-BR", {
          style: "currency",
          currency: "BRL",
        });

        let textoSaldo = "Saldo";
        let iconeClass = "fa-solid-2 fa-wallet-2";

        if (saldoMes > 0) {
          textoSaldo = "Lucro Mês";
          iconeClass = "fa-solid-2 fa-chart-line-2";
        } else if (saldoMes < 0) {
          textoSaldo = "Negativo Mês";
          iconeClass = "fa-solid-2 fa-arrow-trend-down-2";
        } else {
          textoSaldo = "Saldo Mês";
          iconeClass = "fa-solid-2 fa-wallet-2";
        }

        saldoInfo.innerHTML = `
          <i class="${iconeClass}"></i>
          <span class="saldo-info-rotulo-2">${textoSaldo}:</span>
          <span class="saldo-info-valor-2">${saldoFormatado}</span>
        `;

        // ✅ APLICAR CLASSES COM SUFIXO -2
        saldoInfo.className =
          saldoMes > 0
            ? "saldo-positivo-2"
            : saldoMes < 0
            ? "saldo-negativo-2"
            : "saldo-zero-2";
      }
    } catch (error) {
      console.error("❌ Erro ao atualizar barra progresso mensal:", error);
    }
  },

  // ✅ MOSTRAR ERRO ESPECÍFICO PARA MENSAL
  mostrarErroMetaMensal() {
    try {
      const metaElement = document.getElementById("meta-valor-2");
      if (metaElement) {
        metaElement.innerHTML =
          '<i class="fa-solid-2 fa-coins-2"></i><span class="valor-texto-2 loading-text-2">R$ 0,00</span>';
      }
    } catch (error) {
      console.error("❌ Erro ao mostrar erro meta mensal:", error);
    }
  },

  // ✅ MOSTRAR LOADING TEMPORÁRIO PARA MENSAL
  mostrarLoadingTemporarioMensal() {
    try {
      const metaElement = document.getElementById("meta-valor-2");
      if (metaElement) {
        const valorTextoEl = metaElement.querySelector(".valor-texto-2");
        if (valorTextoEl) {
          valorTextoEl.textContent = "Calculando...";
          valorTextoEl.style.opacity = "0.6";

          setTimeout(() => {
            valorTextoEl.style.opacity = "1";
          }, 800);
        }
      }

      const barraProgresso = document.getElementById("barra-progresso-2");
      if (barraProgresso) {
        barraProgresso.style.opacity = "0.5";
        setTimeout(() => {
          barraProgresso.style.opacity = "1";
        }, 600);
      }
    } catch (error) {
      console.error("❌ Erro ao mostrar loading mensal:", error);
    }
  },

  // ✅ APLICAR ANIMAÇÃO MENSAL
  aplicarAnimacaoMensal(elemento) {
    try {
      elemento.classList.add("atualizado-2");
      setTimeout(() => {
        elemento.classList.remove("atualizado-2");
      }, 1500);
    } catch (error) {
      console.error("❌ Erro ao aplicar animação mensal:", error);
    }
  },

  // ✅ INICIALIZAR SISTEMA MENSAL
  inicializar() {
    try {
      const metaElement = document.getElementById("meta-valor-2");
      if (metaElement) {
        metaElement.innerHTML =
          '<i class="fa-solid-2 fa-coins-2"></i><span class="valor-texto-2 loading-text-2">Calculando...</span>';
      }

      console.log(`🚀 Sistema Meta MENSAL inicializado`);
      console.log(`📅 Período fixo: MÊS`);
      console.log(`📊 Tipo de meta será detectado pelo banco de dados`);

      // ✅ INICIALIZAR COM DELAY
      setTimeout(() => {
        this.atualizarMetaMensal();
      }, 1000);
    } catch (error) {
      console.error("❌ Erro na inicialização mensal:", error);
    }
  },

  // ✅ SINCRONIZAR COM MUDANÇAS DO BLOCO 1
  sincronizarComBloco1() {
    try {
      // ✅ ATUALIZAR SEMPRE QUE HOUVER MUDANÇA NO SISTEMA PRINCIPAL
      this.atualizarMetaMensal(true);
    } catch (error) {
      console.error("❌ Erro ao sincronizar com bloco 1:", error);
    }
  },
};

// ========================================
// INTERCEPTAÇÃO AJAX PARA BLOCO 2
// ========================================

function configurarInterceptadoresBloco2() {
  try {
    // ✅ INTERCEPTAR FETCH PARA ATUALIZAR BLOCO 2
    const originalFetch = window.fetch;

    window.fetch = async function (...args) {
      const response = await originalFetch.apply(this, args);

      if (
        args[0] &&
        typeof args[0] === "string" &&
        args[0].includes("dados_banca.php") &&
        response.ok
      ) {
        setTimeout(() => {
          if (
            typeof MetaMensalManager !== "undefined" &&
            !MetaMensalManager.atualizandoAtualmente
          ) {
            MetaMensalManager.sincronizarComBloco1();
          }
        }, 200); // ✅ DELAY MAIOR PARA AGUARDAR BLOCO 1
      }

      return response;
    };

    // ✅ INTERCEPTAR XMLHttpRequest TAMBÉM
    const originalXHR = window.XMLHttpRequest;
    function newXHR() {
      const xhr = new originalXHR();
      const originalSend = xhr.send;

      xhr.send = function (...args) {
        xhr.addEventListener("load", function () {
          if (
            xhr.responseURL &&
            xhr.responseURL.includes("dados_banca.php") &&
            xhr.status === 200
          ) {
            setTimeout(() => {
              if (
                typeof MetaMensalManager !== "undefined" &&
                !MetaMensalManager.atualizandoAtualmente
              ) {
                MetaMensalManager.sincronizarComBloco1();
              }
            }, 200);
          }
        });

        return originalSend.apply(this, args);
      };

      return xhr;
    }

    window.XMLHttpRequest = newXHR;
  } catch (error) {
    console.error("❌ Erro ao configurar interceptadores bloco 2:", error);
  }
}

// ========================================
// INTEGRAÇÃO COM MUDANÇAS DE PERÍODO DO BLOCO 1
// ========================================

// ✅ OBSERVAR MUDANÇAS NO SISTEMA PRINCIPAL
function observarMudancasPeriodo() {
  try {
    const radiosPeriodo = document.querySelectorAll('input[name="periodo"]');

    radiosPeriodo.forEach((radio) => {
      radio.addEventListener("change", (e) => {
        if (e.target.checked) {
          console.log(`📅 Mudança detectada no bloco 1: ${e.target.value}`);

          // ✅ SEMPRE ATUALIZAR BLOCO 2 QUANDO HOUVER MUDANÇA NO BLOCO 1
          setTimeout(() => {
            if (typeof MetaMensalManager !== "undefined") {
              MetaMensalManager.sincronizarComBloco1();
            }
          }, 500);
        }
      });
    });
  } catch (error) {
    console.error("❌ Erro ao observar mudanças de período:", error);
  }
}

// ========================================
// INTEGRAÇÃO COM DADOSMANAGER
// ========================================

// ✅ INTEGRAR COM O SISTEMA EXISTENTE DE DADOS
function integrarComDadosManager() {
  try {
    if (typeof DadosManager !== "undefined") {
      // ✅ INTERCEPTAR ATUALIZAÇÕES DO DADOS MANAGER
      const originalAtualizar = DadosManager.atualizarLucroEBancaViaAjax;

      if (originalAtualizar) {
        DadosManager.atualizarLucroEBancaViaAjax = function () {
          // ✅ CHAMAR FUNÇÃO ORIGINAL
          const resultado = originalAtualizar.call(this);

          // ✅ ATUALIZAR BLOCO 2 APÓS DADOS MANAGER
          setTimeout(() => {
            if (typeof MetaMensalManager !== "undefined") {
              MetaMensalManager.sincronizarComBloco1();
            }
          }, 300);

          return resultado;
        };

        console.log("✅ Integração com DadosManager configurada");
      }
    }
  } catch (error) {
    console.error("❌ Erro ao integrar com DadosManager:", error);
  }
}

// ========================================
// FUNÇÕES GLOBAIS PARA BLOCO 2
// ========================================

window.atualizarMetaMensal = () => {
  if (typeof MetaMensalManager !== "undefined") {
    return MetaMensalManager.atualizarMetaMensal();
  }
  return null;
};

window.forcarAtualizacaoMetaMensal = () => {
  if (typeof MetaMensalManager !== "undefined") {
    MetaMensalManager.atualizandoAtualmente = false;
    return MetaMensalManager.atualizarMetaMensal();
  }
  return null;
};

// ========================================
// ATALHOS PARA BLOCO 2
// ========================================

window.$2 = {
  force: () => forcarAtualizacaoMetaMensal(),
  sync: () => {
    if (typeof MetaMensalManager !== "undefined") {
      return MetaMensalManager.sincronizarComBloco1();
    }
    return null;
  },

  info: () => {
    try {
      const metaElement = document.getElementById("meta-valor-2");
      const rotuloElement = document.getElementById("rotulo-meta-2");
      const barraElement = document.getElementById("barra-progresso-2");
      const saldoElement = document.getElementById("saldo-info-2");

      const info = {
        meta: !!metaElement,
        rotulo: !!rotuloElement,
        barra: !!barraElement,
        saldo: !!saldoElement,
        metaContent: metaElement ? metaElement.textContent : "N/A",
        rotuloContent: rotuloElement ? rotuloElement.textContent : "N/A",
        atualizando:
          typeof MetaMensalManager !== "undefined"
            ? MetaMensalManager.atualizandoAtualmente
            : false,
        periodoFixo: "MÊS",
        tipoMetaAtual:
          typeof MetaMensalManager !== "undefined"
            ? MetaMensalManager.tipoMetaAtual
            : "Detectado pelo banco",
        verificacao: "Sistema específico para META MENSAL",
      };

      console.log("📊 Info Sistema Meta Mensal:", info);
      return "✅ Info Meta Mensal verificada";
    } catch (error) {
      console.error("❌ Erro ao obter info mensal:", error);
      return "❌ Erro ao obter informações mensais";
    }
  },

  status: () => {
    try {
      const status = {
        sistemaMetaMensal: {
          ativo: true,
          versao: "Específico para Mês",
          caracteristicas: [
            "Sempre mostra meta MENSAL",
            "Lucro filtrado por MÊS",
            "Barra de progresso mensal",
            "Sincroniza com bloco 1",
            "Elementos independentes com sufixo -2",
          ],
        },
        metaMensalManager: {
          existe: typeof MetaMensalManager !== "undefined",
          periodoFixo: "mes",
          tipoMeta:
            typeof MetaMensalManager !== "undefined"
              ? MetaMensalManager.tipoMetaAtual
              : "Detectado pelo banco",
          atualizando:
            typeof MetaMensalManager !== "undefined"
              ? MetaMensalManager.atualizandoAtualmente
              : false,
        },
        elementos: {
          metaValor2: !!document.getElementById("meta-valor-2"),
          barraProgresso2: !!document.getElementById("barra-progresso-2"),
          saldoInfo2: !!document.getElementById("saldo-info-2"),
          rotuloMeta2: !!document.getElementById("rotulo-meta-2"),
          porcentagemBarra2: !!document.getElementById("porcentagem-barra-2"),
        },
        sincronizacao: {
          comBloco1: true,
          comDadosManager: typeof DadosManager !== "undefined",
          interceptadoresAtivos: true,
        },
      };

      console.log("🔍 Status Sistema Meta Mensal:", status);
      return status;
    } catch (error) {
      console.error("❌ Erro ao obter status mensal:", error);
      return { erro: "Erro ao obter status mensal" };
    }
  },
};

// ========================================
// INICIALIZAÇÃO SISTEMA MENSAL
// ========================================

function inicializarSistemaMetaMensal() {
  try {
    console.log("🚀 Inicializando Sistema Meta MENSAL (Bloco 2)...");

    if (typeof MetaMensalManager !== "undefined") {
      MetaMensalManager.inicializar();
      console.log("✅ MetaMensalManager inicializado");
    }

    configurarInterceptadoresBloco2();
    console.log("✅ Interceptadores Bloco 2 configurados");

    observarMudancasPeriodo();
    console.log("✅ Observação de mudanças configurada");

    integrarComDadosManager();
    console.log("✅ Integração com DadosManager configurada");

    console.log("🎯 Sistema Meta MENSAL inicializado!");
    console.log("📝 Características:");
    console.log("   ✅ Sempre mostra META DO MÊS");
    console.log("   ✅ Lucro filtrado por mês");
    console.log("   ✅ Elementos com sufixo -2");
    console.log("   ✅ Sincroniza automaticamente com Bloco 1");
    console.log("   ✅ Intercepta mudanças de dados");
  } catch (error) {
    console.error("❌ Erro na inicialização sistema mensal:", error);
  }
}

// ✅ AGUARDAR DOM PARA BLOCO 2
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", () => {
    setTimeout(inicializarSistemaMetaMensal, 1200); // ✅ DELAY MAIOR PARA AGUARDAR BLOCO 1
  });
} else {
  setTimeout(inicializarSistemaMetaMensal, 800);
}

// ========================================
// LOGS FINAIS
// ========================================

console.log("🎯 Sistema Meta MENSAL carregado!");
console.log("📱 Comandos Disponíveis para Bloco 2:");
console.log("  $2.force() - Forçar atualização meta mensal");
console.log("  $2.sync() - Sincronizar com bloco 1");
console.log("  $2.info() - Ver status bloco 2");
console.log("  $2.status() - Status completo bloco 2");
console.log("");
console.log("✅ BLOCO 2 - SEMPRE MOSTRA META MENSAL!");
console.log("📝 Sistema funciona independente do período selecionado");
console.log("   • Sempre calcula e mostra a meta do mês");
console.log("   • Sempre mostra o lucro do mês atual");
console.log("   • Sincroniza automaticamente com o bloco 1");
console.log("   • Elementos próprios com sufixo -2");

// ✅ EXPORT PARA USO EXTERNO
window.MetaMensalManager = MetaMensalManager;
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
        // Efeito de pulse na mudança
        elemento.style.transform = "scale(1.1)";
        elemento.style.transition = "all 0.3s ease";

        setTimeout(() => {
          elemento.textContent = novoValor;
          elemento.style.transform = "scale(1)";
        }, 150);
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

          // Atualiza placar mensal quando período for 'mes'
          if (this.periodoAtual === "mes") {
            console.log(
              "🔄 SistemaFiltroPeriodo atualizou placar do mês, sincronizando placar-2..."
            );
            setTimeout(() => {
              if (typeof PlacarMensalManager !== "undefined") {
                PlacarMensalManager.sincronizarComPlacarPrincipal();
              }
            }, 100);
          }
        };
      }

      // Interceptar mudanças de período
      const radiosPeriodo = document.querySelectorAll('input[name="periodo"]');
      radiosPeriodo.forEach((radio) => {
        radio.addEventListener("change", (e) => {
          if (e.target.checked && e.target.value === "mes") {
            console.log(
              "🔄 Período alterado para mês, atualizando placar mensal..."
            );
            setTimeout(() => {
              this.atualizarPlacarMensal();
            }, 500);
          } else if (e.target.checked && e.target.value !== "mes") {
            // Quando não for mês, manter placar mensal fixo (dados do mês atual)
            console.log(
              "🔄 Período alterado, mantendo placar mensal do mês atual..."
            );
            setTimeout(() => {
              this.atualizarPlacarMensal();
            }, 500);
          }
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

          // Sempre atualizar placar mensal após recarregar mentores
          setTimeout(() => {
            if (typeof PlacarMensalManager !== "undefined") {
              console.log(
                "🔄 Mentores recarregados, atualizando placar mensal..."
              );
              PlacarMensalManager.atualizarPlacarMensal();
            }
          }, 200);

          return resultado;
        };
      }
    } catch (error) {
      console.error("❌ Erro ao configurar interceptadores:", error);
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
  left: 50%;
  top: 225px;
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
  gap: clamp(8px, 2vw, 15px);
  color: white;
  font-size: clamp(16px, 4vw, 24px);
  font-weight: 800;
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
}

.placar-green-2 {
  color: #03a158;
  font-weight: 800;
}

.placar-red-2 {
  color: #e93a3a;
  font-weight: 800;
}

.separador-2 {
  color: rgba(109, 107, 107, 0.9);
  font-size: clamp(14px, 3vw, 20px);
  font-weight: 300;
  margin: 0 clamp(4px, 1vw, 8px);
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
