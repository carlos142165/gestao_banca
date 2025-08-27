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
        await new Promise((resolve) => setTimeout(resolve, 150));
      }

      const response = await fetch("dados_banca.php", {
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

  // Atualizar todos os elementos - versão para bloco 2
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

      const resultado = this.calcularMetaFinalMensal(
        saldoMes,
        metaCalculada,
        bancaTotal,
        dadosComplementados
      );

      // Atualizar elementos do bloco 2
      this.garantirIconeMoeda();
      this.atualizarMetaElementoMensal(resultado);
      this.atualizarRotuloMensal(resultado.rotulo);
      this.atualizarBarraProgressoMensal(resultado, data);

      console.log(`Meta MENSAL atualizada`);
      console.log(`Lucro do MÊS: R$ ${saldoMes.toFixed(2)}`);
      console.log(`Meta MENSAL: R$ ${metaCalculada.toFixed(2)}`);
    } catch (error) {
      console.error("Erro ao atualizar elementos mensais:", error);
    }
  },

  // Calcular meta final - versão para mensal
  calcularMetaFinalMensal(saldoMes, metaCalculada, bancaTotal, data) {
    try {
      let metaFinal, rotulo, statusClass;

      if (bancaTotal <= 0) {
        metaFinal = bancaTotal;
        rotulo = "Deposite p/ Começar";
        statusClass = "sem-banca";
      } else if (
        saldoMes > 0 &&
        metaCalculada > 0 &&
        saldoMes >= metaCalculada
      ) {
        metaFinal = 0;
        rotulo = `Meta do Mês Batida! <i class='fa-solid fa-trophy'></i>`;
        statusClass = "meta-batida";
      } else if (metaCalculada === 0 && saldoMes > 0) {
        metaFinal = 0;
        rotulo = `Meta do Mês Batida! <i class='fa-solid fa-trophy'></i>`;
        statusClass = "meta-batida";
      } else if (saldoMes < 0) {
        metaFinal = metaCalculada - saldoMes;
        rotulo = `Restando p/ Meta do Mês`;
        statusClass = "negativo";
      } else if (saldoMes === 0) {
        metaFinal = metaCalculada;
        rotulo = "Meta do Mês";
        statusClass = "neutro";
      } else {
        metaFinal = metaCalculada - saldoMes;
        rotulo = `Restando p/ Meta do Mês`;
        statusClass = "lucro";
      }

      return {
        metaFinal,
        metaFinalFormatada: metaFinal.toLocaleString("pt-BR", {
          style: "currency",
          currency: "BRL",
        }),
        rotulo,
        statusClass,
      };
    } catch (error) {
      console.error("Erro ao calcular meta final mensal:", error);
      return {
        metaFinal: 0,
        metaFinalFormatada: "R$ 0,00",
        rotulo: "Erro no cálculo",
        statusClass: "erro",
      };
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
            <span class="valor-texto-2">${textoAtual}</span>
          `;
          console.log("Ícone da moeda adicionado ao HTML 2");
        }
      }
    } catch (error) {
      console.error("Erro ao garantir ícone da moeda:", error);
    }
  },

  // Atualizar meta elemento - bloco 2 (com ícone garantido)
  atualizarMetaElementoMensal(resultado) {
    try {
      const metaValor = document.getElementById("meta-valor-2");
      if (!metaValor) {
        console.warn("Elemento meta-valor-2 não encontrado");
        return;
      }

      let valorTexto = metaValor.querySelector(".valor-texto-2");

      if (valorTexto) {
        valorTexto.textContent = resultado.metaFinalFormatada;
      } else {
        // USAR CLASSES CORRETAS DO FONT AWESOME
        metaValor.innerHTML = `
          <i class="fa-solid fa-coins"></i>
          <span class="valor-texto-2" id="valor-texto-meta-2">${resultado.metaFinalFormatada}</span>
        `;
      }

      // Aplicar classes com sufixo -2
      metaValor.className = metaValor.className.replace(
        /\bvalor-meta-2\s+\w+/g,
        ""
      );
      metaValor.classList.add("valor-meta-2", resultado.statusClass);
    } catch (error) {
      console.error("Erro ao atualizar meta elemento mensal:", error);
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

  // Atualizar barra progresso - bloco 2 (com ícones dinâmicos)
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

      // Limpar classes antigas com sufixo -2
      let classeCor = "";
      barraProgresso.className = barraProgresso.className.replace(
        /\bbarra-\w+-2/g,
        ""
      );

      if (!barraProgresso.classList.contains("widget-barra-progresso-2")) {
        barraProgresso.classList.add("widget-barra-progresso-2");
      }

      // Aplicar classe correta com sufixo -2
      if (resultado.statusClass === "meta-batida") {
        classeCor = "barra-meta-batida-2";
      } else {
        classeCor = `barra-${resultado.statusClass}-2`;
      }

      // Aplicar classe e estilos
      barraProgresso.classList.add(classeCor);
      barraProgresso.style.width = `${larguraBarra}%`;
      barraProgresso.style.backgroundColor = "";
      barraProgresso.style.background = "";

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
          '<i class="fa-solid fa-coins"></i><span class="valor-texto-2 loading-text-2">R$ 0,00</span>';
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
          '<i class="fa-solid fa-coins"></i><span class="valor-texto-2 loading-text-2">Calculando...</span>';
      }

      console.log(`Sistema Meta MENSAL inicializado`);

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

  // Função para testar as melhorias
  test: () => {
    console.log("Testando melhorias HTML 2...");

    if (typeof MetaMensalManager === "undefined") {
      return "MetaMensalManager não encontrado";
    }

    // Testar ícones dinâmicos
    setTimeout(() => {
      MetaMensalManager.atualizarIconesSaldoDinamicos(150.75); // Positivo
      console.log("Teste 1: Saldo positivo");
    }, 1000);

    setTimeout(() => {
      MetaMensalManager.atualizarIconesSaldoDinamicos(-85.3); // Negativo
      console.log("Teste 2: Saldo negativo");
    }, 2000);

    setTimeout(() => {
      MetaMensalManager.atualizarIconesSaldoDinamicos(0); // Zero
      console.log("Teste 3: Saldo zero");
    }, 3000);

    // Testar ícone da moeda
    setTimeout(() => {
      MetaMensalManager.garantirIconeMoeda();
      console.log("Teste 4: Ícone da moeda");
    }, 4000);

    return "Teste completo em 4 segundos";
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
        verificacao: "Sistema Meta Mensal com ícones corretos",
      };

      console.log("Info Sistema Meta Mensal:", info);
      return "Info Meta Mensal verificada";
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
    console.log("Inicializando Sistema Meta MENSAL com ícones corretos...");

    if (typeof MetaMensalManager !== "undefined") {
      MetaMensalManager.inicializar();
      console.log("MetaMensalManager inicializado");
    }

    console.log("Sistema Meta MENSAL inicializado!");
    console.log("Características:");
    console.log("   Sempre mostra META DO MÊS");
    console.log("   Ícone da moeda garantido");
    console.log("   Ícones dinâmicos do saldo");
    console.log("   Barra de progresso reduzida");
    console.log("   Classes Font Awesome corretas");
  } catch (error) {
    console.error("Erro na inicialização sistema mensal:", error);
  }
}

// Aguardar DOM
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", () => {
    setTimeout(inicializarSistemaMetaMensal, 1200);
  });
} else {
  setTimeout(inicializarSistemaMetaMensal, 800);
}

console.log("Sistema Meta MENSAL COM ÍCONES CORRETOS carregado!");
console.log("Comandos:");
console.log("  $2.force() - Forçar atualização");
console.log("  $2.test() - Testar ícones");
console.log("  $2.info() - Ver status");

// AQUI PARTE DO CODIGO QUE QTUALIZA EM TEMPO REAL VIA AJAX OS VALORES
window.MetaMensalManager = MetaMensalManager;
MetaMensalManager.atualizarMetaMensal = async function (aguardarDados = false) {
  if (this.atualizandoAtualmente) return null;
  this.atualizandoAtualmente = true;

  try {
    // Remover delay desnecessário - só usar quando especificado
    if (aguardarDados) {
      await new Promise((resolve) => setTimeout(resolve, 100)); // Reduzido de 150ms
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

    const dadosProcessados = {
      ...data,
      meta_display: parseFloat(data.meta_mensal) || 0,
      meta_display_formatada: data.meta_mensal_formatada || "R$ 0,00",
      rotulo_periodo: "Meta do Mês",
      periodo_ativo: "mes",
      lucro_periodo: parseFloat(data.lucro) || 0,
    };

    this.atualizarTodosElementosMensais(dadosProcessados);
    console.log("Meta mensal atualizada rapidamente");

    return dadosProcessados;
  } catch (error) {
    console.error("Erro Meta Mensal:", error);
    this.mostrarErroMetaMensal();
    return null;
  } finally {
    this.atualizandoAtualmente = false;
  }
};

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

  // Radios / selects / inputs importantes
  document.querySelectorAll('input[name="periodo"]').forEach((radio) => {
    radio.addEventListener("change", () => atualizarRapido());
  });

  // Observador de mudanças no DOM para elementos chave (quando valores são atualizados via AJAX)
  const observerTargets = [
    "#meta-valor-2",
    "#saldo-info-2",
    "#pontuacao-2",
    ".lista-dias",
  ];

  const observer = new MutationObserver((mutations) => {
    // Quando qualquer mutação relevante ocorrer, solicitar atualização rápida
    for (const m of mutations) {
      if (
        m.type === "childList" ||
        m.type === "characterData" ||
        m.type === "subtree"
      ) {
        atualizarRapido();
        break;
      }
    }
  });

  observerTargets.forEach((sel) => {
    try {
      document.querySelectorAll(sel).forEach((node) => {
        observer.observe(node, {
          childList: true,
          characterData: true,
          subtree: true,
        });
      });
    } catch (e) {
      // silencioso se não existir no momento
    }
  });

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
      "Não foi possível hookar fetch para atualizações automáticas",
      e
    );
  }

  // Hook em XHR (caso o app ainda use XMLHttpRequest)
  try {
    const _XHR_send = XMLHttpRequest.prototype.send;
    const _XHR_open = XMLHttpRequest.prototype.open;

    XMLHttpRequest.prototype.open = function (method, url) {
      this.__trackedUrl = url;
      return _XHR_open.apply(this, arguments);
    };

    XMLHttpRequest.prototype.send = function () {
      this.addEventListener("load", function () {
        try {
          if (
            /dados_banca|carregar-mentores|controle|valor_mentores/i.test(
              this.__trackedUrl || ""
            )
          ) {
            setTimeout(atualizarRapido, 50);
          }
        } catch (e) {}
      });
      return _XHR_send.apply(this, arguments);
    };
  } catch (e) {
    console.warn(
      "Não foi possível hookar XHR para atualizações automáticas",
      e
    );
  }

  // Interval fallback (mais longo) para garantir eventual consistência
  setInterval(atualizarRapido, 5000);

  // Primeira atualização imediata
  setTimeout(atualizarRapido, 50);

  // Expor utilitário
  window.atualizarRapido = atualizarRapido;

  console.log(
    "Sistema rápido (melhorado) ativo - responde imediatamente a mudanças"
  );
})();
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
  left: 47%;
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

const ListaDiasRealtimeManager = {
  // Controle de estado
  atualizandoAtualmente: false,
  intervaloAtualizacao: null,
  ultimaAtualizacao: null,
  hashUltimosDados: "",

  // Configurações
  INTERVALO_MS: 5000, // Atualiza a cada 5 segundos
  TIMEOUT_MS: 5000, // Timeout para requisições

  // Inicializar sistema
  inicializar() {
    console.log("🚀 Inicializando sistema de atualização da lista de dias...");

    // Primeira atualização imediata
    this.atualizarListaDias();

    // Configurar intervalo de atualização
    this.intervaloAtualizacao = setInterval(() => {
      this.atualizarListaDias();
    }, this.INTERVALO_MS);

    // Configurar interceptadores de eventos
    this.configurarInterceptadores();

    console.log("✅ Sistema de lista de dias em tempo real ativo!");
  },

  // Função principal de atualização
  async atualizarListaDias() {
    if (this.atualizandoAtualmente) return;

    this.atualizandoAtualmente = true;

    try {
      // Buscar dados atualizados do servidor
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

      // Verificar se houve mudança nos dados
      const hashAtual = this.gerarHashDados(dados);
      if (hashAtual === this.hashUltimosDados) {
        // Dados não mudaram, não precisa atualizar DOM
        return;
      }

      this.hashUltimosDados = hashAtual;

      // Atualizar a lista de dias no DOM
      this.renderizarListaDias(dados);

      this.ultimaAtualizacao = new Date();
    } catch (error) {
      console.error("❌ Erro ao atualizar lista de dias:", error);
    } finally {
      this.atualizandoAtualmente = false;
    }
  },

  // Renderizar lista de dias no DOM
  renderizarListaDias(responseData) {
    const container = document.querySelector(".lista-dias");
    if (!container) return;

    // Salvar scroll position
    const scrollTop = container.scrollTop;

    // Extrair dados da resposta
    const dados = responseData.dados || responseData;
    const mes = responseData.mes || new Date().getMonth() + 1;
    const ano = responseData.ano || new Date().getFullYear();
    const diasNoMes =
      responseData.dias_no_mes || new Date(ano, mes, 0).getDate();

    // Obter data de hoje
    const hoje = new Date().toISOString().split("T")[0];

    // Criar fragmento para melhor performance
    const fragment = document.createDocumentFragment();

    // Gerar HTML para cada dia
    for (let dia = 1; dia <= diasNoMes; dia++) {
      const diaStr = dia.toString().padStart(2, "0");
      const mesStr = mes.toString().padStart(2, "0");
      const data_mysql = `${ano}-${mesStr}-${diaStr}`;
      const data_exibicao = `${diaStr}/${mesStr}/${ano}`;

      // Obter dados do dia ou usar valores padrão
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

      // FILTRO: Só mostrar dias com valores ou dia de hoje
      const temValores =
        parseInt(dadosDia.total_green) > 0 || parseInt(dadosDia.total_red) > 0;
      const ehHoje = data_mysql === hoje;

      // Pular dias sem valores (exceto hoje)
      if (!temValores && !ehHoje) {
        continue;
      }

      // Determinar classes CSS
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

      let classe_dia = "dia-normal";
      let classe_destaque = "";

      if (data_mysql === hoje) {
        classe_dia =
          "dia-hoje " + (saldo_dia >= 0 ? "borda-verde" : "borda-vermelha");
      } else if (data_mysql < hoje) {
        if (saldo_dia > 0) {
          classe_destaque = "dia-destaque";
        } else if (saldo_dia < 0) {
          classe_destaque = "dia-destaque-negativo";
        }
      }

      const classe_nao_usada = data_mysql > hoje ? "dia-nao-usada" : "";
      const classe_sem_valor =
        data_mysql < hoje &&
        parseInt(dadosDia.total_green) === 0 &&
        parseInt(dadosDia.total_red) === 0
          ? "dia-sem-valor"
          : "";

      // Verificar se o elemento já existe
      const elementoExistente = container.querySelector(
        `[data-date="${data_mysql}"]`
      );

      if (elementoExistente) {
        // Atualizar apenas os valores se o elemento já existe
        const placarGreen =
          elementoExistente.querySelector(".placar.verde-bold");
        const placarRed = elementoExistente.querySelector(
          ".placar.vermelho-bold"
        );
        const valor = elementoExistente.querySelector(".valor");

        if (placarGreen)
          placarGreen.textContent = parseInt(dadosDia.total_green);
        if (placarRed) placarRed.textContent = parseInt(dadosDia.total_red);
        if (valor) {
          valor.textContent = `R$ ${saldo_formatado}`;
          valor.className = `valor ${cor_valor}`;
        }

        // Atualizar classes do dia se mudou
        elementoExistente.className = `linha-dia ${classe_dia} ${classe_destaque} ${classe_nao_usada} ${classe_sem_valor}`;
      } else {
        // Criar novo elemento
        const divDia = document.createElement("div");
        divDia.className = `linha-dia ${classe_dia} ${classe_destaque} ${classe_nao_usada} ${classe_sem_valor}`;
        divDia.setAttribute("data-date", data_mysql);

        divDia.innerHTML = `
                    <span class="data ${classe_texto}">
                        <i class="fas fa-calendar-day"></i> ${data_exibicao}
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
                    <span class="icone ${classe_texto}"><i class="fas fa-check"></i></span>
                `;

        fragment.appendChild(divDia);
      }
    }

    // Adicionar novos elementos apenas se houver
    if (fragment.childNodes.length > 0) {
      container.innerHTML = "";
      container.appendChild(fragment);
    }

    // Restaurar scroll position
    container.scrollTop = scrollTop;

    // Disparar evento customizado
    window.dispatchEvent(
      new CustomEvent("listaDiasAtualizada", {
        detail: { dados: responseData, timestamp: new Date() },
      })
    );
  },

  // Gerar hash dos dados para detectar mudanças
  gerarHashDados(dados) {
    return JSON.stringify(dados);
  },

  // Configurar interceptadores para atualização imediata
  configurarInterceptadores() {
    // Interceptar submissão de formulários
    document.addEventListener("submit", (e) => {
      // Aguardar processamento do servidor e atualizar
      setTimeout(() => {
        this.atualizandoAtualmente = false;
        this.atualizarListaDias();
      }, 500);
    });

    // Interceptar cliques em botões importantes
    document.addEventListener("click", (e) => {
      if (e.target.matches('button, .btn, input[type="submit"]')) {
        setTimeout(() => {
          this.atualizandoAtualmente = false;
          this.atualizarListaDias();
        }, 500);
      }
    });

    // Interceptar mudanças no filtro de período
    document.querySelectorAll('input[name="periodo"]').forEach((radio) => {
      radio.addEventListener("change", () => {
        this.atualizandoAtualmente = false;
        this.atualizarListaDias();
      });
    });

    // Hook em fetch para detectar mudanças
    const originalFetch = window.fetch;
    window.fetch = async function (...args) {
      const response = await originalFetch.apply(this, args);

      // Se for uma requisição que altera dados, atualizar lista
      const url = args[0]?.toString() || "";
      if (
        url.includes("dados_banca") ||
        url.includes("carregar-mentores") ||
        url.includes("cadastrar-valor") ||
        url.includes("excluir-entrada")
      ) {
        setTimeout(() => {
          if (typeof ListaDiasRealtimeManager !== "undefined") {
            ListaDiasRealtimeManager.atualizandoAtualmente = false;
            ListaDiasRealtimeManager.atualizarListaDias();
          }
        }, 300);
      }

      return response;
    };

    // Atualizar quando outros componentes atualizarem
    window.addEventListener("metaAtualizada", () => {
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
      console.log("🛑 Sistema de atualização parado");
    }
  },

  // Forçar atualização
  forcarAtualizacao() {
    this.atualizandoAtualmente = false;
    return this.atualizarListaDias();
  },
};

// ================================================
// CRIAR ARQUIVO PHP PARA FORNECER DADOS
// ================================================
// Crie um arquivo chamado "obter_dados_mes.php" com este conteúdo:
/*
<?php
require_once 'config.php';
require_once 'carregar_sessao.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache');

$id_usuario = $_SESSION['usuario_id'] ?? null;
if (!$id_usuario) {
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit;
}

// Obter mês e ano atual
$mes = date('m');
$ano = date('Y');

// Se período foi enviado, usar ele
if (isset($_GET['periodo']) && $_GET['periodo'] === 'mes') {
    // Usar mês atual
} else {
    // Para outros períodos, ajustar conforme necessário
}

// Buscar dados do banco
$sql = "
    SELECT 
        DATE(vm.data_criacao) as data,
        SUM(CASE WHEN vm.green = 1 THEN vm.valor_green ELSE 0 END) as total_valor_green,
        SUM(CASE WHEN vm.red = 1 THEN vm.valor_red ELSE 0 END) as total_valor_red,
        SUM(CASE WHEN vm.green = 1 THEN 1 ELSE 0 END) as total_green,
        SUM(CASE WHEN vm.red = 1 THEN 1 ELSE 0 END) as total_red
    FROM valor_mentores vm
    INNER JOIN mentores m ON vm.id_mentores = m.id
    WHERE m.id_usuario = ?
    AND MONTH(vm.data_criacao) = ?
    AND YEAR(vm.data_criacao) = ?
    GROUP BY DATE(vm.data_criacao)
";

$stmt = $conexao->prepare($sql);
$stmt->bind_param("iii", $id_usuario, $mes, $ano);
$stmt->execute();
$result = $stmt->get_result();

$dados_por_dia = [];
while ($row = $result->fetch_assoc()) {
    $dados_por_dia[$row['data']] = [
        'total_valor_green' => $row['total_valor_green'] ?: 0,
        'total_valor_red' => $row['total_valor_red'] ?: 0,
        'total_green' => $row['total_green'] ?: 0,
        'total_red' => $row['total_red'] ?: 0
    ];
}

echo json_encode($dados_por_dia);
?>
*/

// ================================================
// INICIALIZAÇÃO AUTOMÁTICA
// ================================================

// Aguardar DOM carregar
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", () => {
    setTimeout(() => {
      ListaDiasRealtimeManager.inicializar();
    }, 1000);
  });
} else {
  setTimeout(() => {
    ListaDiasRealtimeManager.inicializar();
  }, 500);
}

// Comandos globais para debug
window.ListaDias = {
  parar: () => ListaDiasRealtimeManager.parar(),
  iniciar: () => ListaDiasRealtimeManager.inicializar(),
  atualizar: () => ListaDiasRealtimeManager.forcarAtualizacao(),
  status: () => ({
    ativo: !!ListaDiasRealtimeManager.intervaloAtualizacao,
    atualizando: ListaDiasRealtimeManager.atualizandoAtualmente,
    ultimaAtualizacao: ListaDiasRealtimeManager.ultimaAtualizacao,
  }),
};

console.log("📅 Sistema de atualização da lista de dias carregado!");
console.log(
  "Comandos: ListaDias.parar(), ListaDias.iniciar(), ListaDias.atualizar(), ListaDias.status()"
);

// ========================================================================================================================
//                    CARREGA OS DADOS DOS VALORES DE ( DATA - PLACAR - SALDO ) VIA AJAX IMEDIATO
// ========================================================================================================================
//
//
//
//
//
//
//
//
