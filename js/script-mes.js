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

// ===== PERFORMANCE MODE: ativa overrides CSS de baixo custo (reversível)
// Aplica a classe `.gestao-perf-mode` ao elemento raiz para reduzir filtros,
// animações e sombras que custam CPU/GPU. Se precisar reverter, remova a classe.
(function enablePerfModeSafely() {
  try {
    if (!document.documentElement.classList.contains("gestao-perf-mode")) {
      // Adiciona a classe com delay mínimo para permitir testes locais
      window.setTimeout(() => {
        document.documentElement.classList.add("gestao-perf-mode");
      }, 150);
    }
  } catch (e) {
    // Não bloquear a aplicação caso algo vá errado
    console.error("gestao: não foi possível ativar perf-mode", e);
  }
})();

// Helpers para debug: ativar/desativar/toggle do modo de performance sem editar arquivos
window.gestaoPerf = {
  enable() {
    document.documentElement.classList.add("gestao-perf-mode");
    console.log("gestao-perf-mode habilitado");
  },
  disable() {
    document.documentElement.classList.remove("gestao-perf-mode");
    console.log("gestao-perf-mode desabilitado");
  },
  toggle() {
    document.documentElement.classList.toggle("gestao-perf-mode");
    console.log(
      "gestao-perf-mode agora:",
      document.documentElement.classList.contains("gestao-perf-mode")
        ? "ON"
        : "OFF"
    );
  },
};

// Observer para detectar alterações externas em #meta-valor-2
(function observeMetaValor2() {
  try {
    function attach() {
      const el = document.getElementById("meta-valor-2");
      if (!el) return;

      const observer = new MutationObserver((mutations) => {
        for (const m of mutations) {
          if (
            m.type === "childList" ||
            m.type === "characterData" ||
            m.type === "subtree"
          ) {
            try {
              const lastBy = el.getAttribute("data-last-set-by");
              const lastAt = Number(el.getAttribute("data-last-set-at") || 0);
              const now = Date.now();
              if (lastBy === "MetaMensalManager" && now - lastAt < 2000) {
                continue; // alteração nossa, ignorar
              }

              console.warn(
                "META-VALUE-2: detectada alteração externa ao manager",
                {
                  newHTML: el.innerHTML,
                  text: el.textContent,
                  lastSetBy: lastBy,
                  lastSetAt: lastAt,
                  timestamp: now,
                }
              );

              // Se temos um HTML salvo e não estamos restaurando, restaurar
              try {
                if (
                  typeof MetaMensalManager !== "undefined" &&
                  MetaMensalManager._lastHTML &&
                  !MetaMensalManager._restoring
                ) {
                  MetaMensalManager._restoring = true;
                  console.log(
                    "META-VALUE-2: restaurando último HTML conhecido pelo manager"
                  );
                  el.innerHTML = MetaMensalManager._lastHTML;
                  el.setAttribute(
                    "data-last-set-by",
                    "MetaMensalManager-restored"
                  );
                  el.setAttribute("data-last-set-at", String(Date.now()));
                  // limpar flag após curto delay
                  setTimeout(() => {
                    MetaMensalManager._restoring = false;
                  }, 500);
                }
              } catch (restoreErr) {
                console.error("Erro ao restaurar meta-valor-2:", restoreErr);
              }

              console.warn(new Error("stack").stack);
            } catch (e) {
              console.error("Erro no observer meta-valor-2:", e);
            }
          }
        }
      });

      observer.observe(el, {
        childList: true,
        subtree: true,
        characterData: true,
      });
      console.log(
        "Observer instalado em #meta-valor-2 para detectar sobrescritas externas"
      );
    }

    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", attach, { once: true });
    } else {
      attach();
    }
  } catch (e) {
    console.error("Erro ao instalar observer de meta-valor-2:", e);
  }
})();

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
  // internal debug/restore fields
  _lastHTML: null,
  _restoring: false,

  // Atualizar meta mensal - versão específica
  async atualizarMetaMensal(aguardarDados = false, attempts = 0) {
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
      console.debug(
        "MetaMensalManager - resposta dados_banca.php?periodo=mes:",
        data
      );
      if (!data.success) throw new Error(data.message);

      if (data.tipo_meta) {
        this.tipoMetaAtual = data.tipo_meta;
      }

      const dadosProcessados = this.processarDadosMensais(data);
      this.atualizarTodosElementosMensais(dadosProcessados);

      return dadosProcessados;
    } catch (error) {
      console.error("Erro Meta Mensal:", error);
      // Tentativa de retry simples com backoff (até 2 retries adicionais)
      if (attempts < 2) {
        const delay = attempts === 0 ? 1000 : 3000;
        console.warn(
          `MetaMensalManager: tentativa ${
            attempts + 1
          } falhou, retry em ${delay}ms...`
        );
        await new Promise((resolve) => setTimeout(resolve, delay));
        this.atualizandoAtualmente = false; // limpar flag para poder refazer
        return this.atualizarMetaMensal(aguardarDados, attempts + 1);
      }

      this.mostrarErroMetaMensal();
      return null;
    } finally {
      this.atualizandoAtualmente = false;
    }
  },

  // Processar dados especificamente para mensal
  processarDadosMensais(data) {
    try {
      const metaRaw = data.meta_mensal;
      const metaFinal = isFinite(Number(metaRaw))
        ? Number(metaRaw)
        : parseFloat(metaRaw) || 0;
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
  // ✅ CALCULAR META FINAL MENSAL COM VALOR TACHADO E EXTRA - CORRIGIDO
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
        metaFinal = metaCalculada;
        rotulo = "Deposite p/ Começar";
        statusClass = "sem-banca";
        console.log(`📊 RESULTADO MENSAL: Sem banca`);
      }
      // ✅ CORREÇÃO: META BATIDA OU SUPERADA - COM VERIFICAÇÃO PRECISA
      else if (saldoMes > 0 && metaCalculada > 0 && saldoMes >= metaCalculada) {
        valorExtra = saldoMes - metaCalculada;
        mostrarTachado = true;
        metaFinal = metaCalculada;

        // ✅ VERIFICAÇÃO PRECISA: Diferença menor que 1 centavo = meta exata
        if (Math.abs(valorExtra) < 0.01) {
          rotulo = `Meta do Mês Batida! <i class='fa-solid fa-trophy'></i>`;
          statusClass = "meta-batida";
          valorExtra = 0; // Zerar diferenças mínimas
          console.log(`🎯 META MENSAL EXATA`);
        } else if (valorExtra > 0.01) {
          rotulo = `Meta do Mês Superada! <i class='fa-solid fa-trophy'></i>`;
          statusClass = "meta-superada";
          console.log(
            `🏆 META MENSAL SUPERADA: Extra de R$ ${valorExtra.toFixed(2)}`
          );
        } else {
          rotulo = `Meta do Mês Batida! <i class='fa-solid fa-trophy'></i>`;
          statusClass = "meta-batida";
          valorExtra = 0;
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
        metaFinal = metaCalculada + Math.abs(saldoMes);
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

      // Guardar o HTML para possível restauração em caso de sobrescrita externa
      try {
        this._lastHTML = htmlConteudo;
        // Persistir último HTML conhecido para sobreviver a reloads rápidos
        try {
          localStorage.setItem("gestao_meta_valor_2_lastHTML", this._lastHTML);
        } catch (e) {
          // localStorage pode falhar em ambientes restritos; não bloquear
        }

        metaValor.innerHTML = htmlConteudo;
        metaValor.setAttribute("data-last-set-by", "MetaMensalManager");
        metaValor.setAttribute("data-last-set-at", String(Date.now()));
      } catch (e) {
        try {
          metaValor.innerHTML = htmlConteudo;
        } catch (ee) {
          console.error("Erro ao definir innerHTML meta-valor-2:", ee);
        }
      }
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
        // Se o rótulo indicar "Restando" aplicamos uma classe para permitir
        // ajustes CSS específicos (margem top controlada por variável :root)
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
        textoSaldo = "Negativo";
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
        // Primeiro tentar restaurar do localStorage para evitar piscar "Calculando..." após reload
        let restored = false;
        try {
          const cached = localStorage.getItem("gestao_meta_valor_2_lastHTML");
          if (cached) {
            this._lastHTML = cached;
            metaElement.innerHTML = cached;
            metaElement.setAttribute(
              "data-last-set-by",
              "MetaMensalManager-cached"
            );
            metaElement.setAttribute("data-last-set-at", String(Date.now()));
            restored = true;
            console.log(
              "MetaMensalManager: restaurado HTML do cache localStorage"
            );
          }
        } catch (e) {
          // ignore localStorage errors
        }

        if (!restored) {
          metaElement.innerHTML =
            '<i class="fa-solid fa-coins"></i><div class="meta-valor-container-2"><span class="valor-texto-2 loading-text-2">Calculando...</span></div>';
        }
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

      // WATCHDOG: se após 3s o elemento ainda mostra 'carregando' ou a classe de loading,
      // tentamos forçar uma nova requisição e mostramos um fallback visual para o usuário.
      setTimeout(() => {
        try {
          const metaElement = document.getElementById("meta-valor-2");
          if (metaElement) {
            const texto = (metaElement.textContent || "").toLowerCase();
            const temLoadingClass = !!metaElement.querySelector(
              ".loading-text-2, .loading-text"
            );
            if (texto.includes("carreg") || temLoadingClass) {
              console.warn(
                "Meta mensal ainda em loading — forçando atualização de fallback"
              );
              // Force re-fetch mais agressivo
              this.atualizandoAtualmente = false;
              this.atualizarMetaMensal(true).then((res) => {
                if (!res) {
                  // fallback visual discreto
                  try {
                    const valorSpan =
                      metaElement.querySelector(".valor-texto-2") ||
                      metaElement;
                    valorSpan.textContent = "—"; // placeholder leve
                    valorSpan.classList.remove(
                      "loading-text-2",
                      "loading-text"
                    );
                  } catch (e) {
                    // silencioso
                  }
                }
              });
            }
          }
        } catch (e) {
          console.error("Erro no watchdog de meta mensal:", e);
        }
      }, 3000);
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
  // Intervalo mínimo entre atualizações (ms) - aumentar para reduzir carga
  const MIN_INTERVAL_MS = 1000; // throttle mais conservador para reduzir picos
  // Handle para debounce/agrupamento de múltiplos gatilhos
  let atualizarTimeoutHandle = null;

  function atualizarRapido() {
    // Se já existe um agendamento pendente, não agendar outro
    if (atualizarTimeoutHandle) return;

    const exec = () => {
      const agora = Date.now();
      if (agora - ultimaAtualizacao < MIN_INTERVAL_MS) {
        atualizarTimeoutHandle = null;
        return; // respeitar intervalo mínimo
      }

      ultimaAtualizacao = agora;

      if (typeof MetaMensalManager !== "undefined") {
        // Forçar estado para permitir reexecução
        MetaMensalManager.atualizandoAtualmente = false;
        MetaMensalManager.atualizarMetaMensal(false);
      }

      atualizarTimeoutHandle = null;
    };

    // Debounce curto para agrupar eventos em uma única atualização
    atualizarTimeoutHandle = setTimeout(exec, 120);
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
      // Tornar seleção de clicks mais seletiva para evitar triggers desnecessários
      const target = e.target;
      const candidate = target.closest(
        'button, .btn, input[type="submit"], a[data-action], [data-trigger-update]'
      );
      if (candidate) {
        setTimeout(atualizarRapido, 50);
      }
    },
    true
  );

  // Hook resiliente para fetch + XMLHttpRequest
  (function installResilientAjaxHooks() {
    const URL_RE =
      /obter_dados_mes|dados_banca|carregar-mentores|cadastrar-valor|cadastrar-valor-novo|excluir-entrada/i;

    function tryHookFetch() {
      try {
        const current = window.fetch;
        if (!current) return;
        if (current.__gestao_hook) return; // já hookado

        const orig = current.bind(window);

        const wrapped = function (...args) {
          let url = "";
          try {
            url = args[0] && args[0].toString ? args[0].toString() : "";
          } catch (e) {
            url = "";
          }

          const p = orig(...args);
          try {
            p.then((resp) => {
              try {
                if (URL_RE.test(url)) {
                  // pequeno atraso para permitir processamento do servidor/DOM
                  setTimeout(atualizarRapido, 50);
                }
              } catch (e) {}
              return resp;
            }).catch(() => {});
          } catch (e) {}
          return p;
        };

        try {
          wrapped.__gestao_hook = true;
          // preservar toString e outras propriedades úteis
          try {
            wrapped.toString = function () {
              return orig.toString();
            };
          } catch (e) {}
          window.fetch = wrapped;
          console.log("gestao: fetch hook instalado");
        } catch (e) {
          // falha silenciosa
        }
      } catch (e) {}
    }

    function tryHookXHR() {
      try {
        const proto = XMLHttpRequest && XMLHttpRequest.prototype;
        if (!proto) return;
        if (proto.__gestao_xhr_hooked) return;

        const origOpen = proto.open;
        const origSend = proto.send;

        proto.open = function (method, url, ...rest) {
          try {
            this.__gestao_request_url = url ? String(url) : "";
          } catch (e) {
            this.__gestao_request_url = "";
          }
          return origOpen.apply(this, [method, url, ...rest]);
        };

        proto.send = function (...args) {
          try {
            this.addEventListener("loadend", function () {
              try {
                const finalUrl = (
                  this.responseURL ||
                  this.__gestao_request_url ||
                  ""
                ).toString();
                if (URL_RE.test(finalUrl)) {
                  setTimeout(atualizarRapido, 50);
                }
              } catch (e) {}
            });
          } catch (e) {}
          return origSend.apply(this, args);
        };

        proto.__gestao_xhr_hooked = true;
        console.log("gestao: XHR hook instalado");
      } catch (e) {}
    }

    // Instalar imediatamente
    tryHookFetch();
    tryHookXHR();

    // Reaplicar algumas vezes caso outros scripts sobrescrevam os hooks
    let attempts = 0;
    const maxAttempts = 6;
    const interval = setInterval(() => {
      attempts++;
      tryHookFetch();
      tryHookXHR();
      if (attempts >= maxAttempts) clearInterval(interval);
    }, 1000);
  })();

  // Interval fallback (mais longo) para garantir eventual consistência
  const fallbackIntervalHandle = setInterval(atualizarRapido, 5000);

  // Primeira atualização imediata
  setTimeout(atualizarRapido, 50);

  // Expor utilitário
  window.atualizarRapidoMensal = atualizarRapido;

  console.log(
    "Sistema rápido MENSAL (melhorado) ativo - responde a mudanças mas com throttle/agrupamento para reduzir carga"
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
// Utilitário global: limpa flags "atualizandoAtualmente" em vários managers
// Útil para garantir que ações do usuário (ex.: excluir, trocar periodo) não sejam bloqueadas.
window.gestaoClearUpdatingFlags = function () {
  try {
    const managers = [
      "ListaDiasManagerCorrigido",
      "MetaMensalManager",
      "MetaDiariaManager",
      "PlacarMensalManager",
      "PlacarAnualManager",
      "MentorManager",
      "SistemaUnicoSemConflito",
      "MonitorContinuo",
      "ListaMesesManagerAnual",
    ];

    managers.forEach((name) => {
      try {
        const obj = window[name];
        if (obj && typeof obj === "object") {
          if ("atualizandoAtualmente" in obj) obj.atualizandoAtualmente = false;
          if (
            "forcarAtualizacao" in obj &&
            typeof obj.forcarAtualizacao === "function"
          ) {
            // não executar forçar por padrão aqui - apenas limpar flags
          }
        }
      } catch (e) {}
    });
  } catch (e) {}
};
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
      const separadorEl = placarElement.querySelector(".separador-2");

      if (greenSpan && redSpan) {
        // If both values are zero, keep the placar visually empty until real data arrives
        const wins = Number(placarData.wins) || 0;
        const losses = Number(placarData.losses) || 0;

        if (wins === 0 && losses === 0) {
          // Show empty placeholders instead of "0 × 0"
          greenSpan.textContent = "";
          redSpan.textContent = "";
          if (separadorEl) {
            // Make separator visually hidden while waiting for real data by toggling a class
            separadorEl.classList.add("separador-transparente");
          }
          // remove update class if present and remove has-values marker
          placarElement.classList.remove("placar-atualizado");
          placarElement.classList.remove("placar-has-values");
        } else {
          // Ensure separator is visible and colored for non-empty scores
          if (separadorEl) {
            separadorEl.classList.remove("separador-transparente");
          }

          // Marca que o placar tem valores para controles CSS
          placarElement.classList.add("placar-has-values");

          // Aplicar valores com animação suave
          this.animarMudancaValor(greenSpan, wins);
          this.animarMudancaValor(redSpan, losses);

          // Aplicar classe de atualização
          placarElement.classList.add("placar-atualizado");
          setTimeout(() => {
            placarElement.classList.remove("placar-atualizado");
          }, 1000);
        }
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
  left: var(--placar-2-left, 50%);
  top: var(--placar-2-top, 30px);
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

.pontuacao-2 {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: clamp(5px, 1.2vw, 20px); /* pequeno gap para proximidade */
  color: #2b2b2b; /* texto escuro para contraste com fundo cinza */
  font-size: clamp(15px, 3.5vw, 22px); /* um pouco menor */
  font-weight: 600 !important; /* manter grosso e forçar override */
  /* Tornar o campo horizontal 100% para preencher de ponta a ponta */
  position: absolute;
  left: 0;
  right: 0;
  width: 100%;
  transform: none;
  box-sizing: border-box; /* garantir que padding não estoure a largura */
  /* Background container to allow mirrored/reflection effect */
  position: relative;
  z-index: 2;
  background: #eef0eeff; /* cor cinza solicitada */
  padding: 8px 16px; /* espaço interno para bordas */
  border-radius: 6px;
}

/* Fundo espelhado (reflexão) abaixo do placar */
.pontuacao-2::after {
  content: "";
  position: absolute;
  left: 0;
  right: 0;
  top: 100%; /* começa logo abaixo do placar */
  height: 60%; /* altura da reflexão relativa ao placar */
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
    padding: 6px 10px;
    border-radius: 6px;
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
    padding: 5px 8px;
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
        // Evitar flash: ocultar até o CSS injetado e o posicionamento final serem aplicados
        try {
          // Aplicar com !important para sobrescrever a regra CSS que esconde o elemento
          placar.style.setProperty("visibility", "hidden", "important");
        } catch (e) {}

        PlacarMensalManager.inicializar();

        // Mostrar após curto delay (tempo suficiente para injeção de CSS e layout)
        setTimeout(() => {
          try {
            // Usar setProperty com 'important' para garantir que o inline style
            // sobrescreva a regra do stylesheet que contém !important
            placar.style.setProperty("visibility", "visible", "important");
          } catch (e) {}
        }, 120);

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
  INTERVALO_MS: 10000, // Atualiza a cada 10 segundos (reduz carga)
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
    this._startInterval = () => {
      if (this.intervaloAtualizacao) return;
      this.intervaloAtualizacao = setInterval(() => {
        this.atualizarListaDias();
      }, this.INTERVALO_MS);
    };
    this._stopInterval = () => {
      if (this.intervaloAtualizacao) {
        clearInterval(this.intervaloAtualizacao);
        this.intervaloAtualizacao = null;
      }
    };

    this._startInterval();

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

    // Pausar atualizações quando a aba estiver oculta (economia de recursos)
    this._onVisibilityChange = () => {
      if (document.hidden) {
        this._stopInterval();
        console.log(
          "ListaDias: aba oculta — atualizações temporariamente pausadas"
        );
      } else {
        // Ao voltar, fazer uma atualização imediata e reiniciar o intervalo
        console.log("ListaDias: aba visível — retomando atualizações");
        this.atualizandoAtualmente = false;
        this.atualizarListaDias();
        this._startInterval();
      }
    };

    document.addEventListener("visibilitychange", this._onVisibilityChange);
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
    // If a CSS variable defines the desired height, respect it instead of forcing current clientHeight
    try {
      const rootStyles = getComputedStyle(document.documentElement);
      const cssDesired = rootStyles
        .getPropertyValue("--lista-dias-height")
        .trim();
      const cssMax = rootStyles
        .getPropertyValue("--lista-dias-maxheight")
        .trim();

      if (cssDesired) {
        // apply desired height as minHeight to avoid flicker but follow the root variable
        container.style.minHeight = cssDesired;
      } else if (cssMax) {
        container.style.minHeight = cssMax;
      } else {
        // fallback: preserve current pixel height
        container.style.minHeight = container.clientHeight + "px";
      }
    } catch (e) {
      container.style.minHeight = container.clientHeight + "px";
    }
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
          <!-- Adiciona classes compatíveis com o CSS do placar-2 (placar-green-2 / placar-red-2)
               para garantir que as regras de cor sejam aplicadas tanto ao placar principal
               quanto ao placar mensal/clonado. Mantém também as classes originais para
               compatibilidade retroativa. -->
          <span class="placar placar-green placar-green-2 ${placar_cinza}">${parseInt(
        dadosDia.total_green
      )}</span>
          <span class="placar separador separador-2 ${placar_cinza}">x</span>
          <span class="placar placar-red placar-red-2 ${placar_cinza}">${parseInt(
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

    // Restaurar scroll and restore previous min-height if it existed. If previous was empty, leave minHeight defined by CSS var.
    container.scrollTop = scrollTop;
    try {
      if (prevMinHeight && prevMinHeight.length > 0) {
        container.style.minHeight = prevMinHeight;
      } else {
        // remove explicit minHeight so CSS variables / stylesheet can control final height
        container.style.removeProperty("min-height");
      }
    } catch (e) {
      container.style.minHeight = prevMinHeight || "";
    }

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
          // Debounce curto (mais largo para reduzir cpu sob mutações altas)
          clearTimeout(this._sanitizeTimer);
          this._sanitizeTimer = setTimeout(() => this.sanitizeDataCells(), 150);
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
          // Garantir que qualquer bloqueio seja removido e forçar atualização imediata
          this.atualizandoAtualmente = false;
          // Usar forcarAtualizacao que também reaplica meta/deteccao
          try {
            this.forcarAtualizacao();
          } catch (err) {
            // Fallback para chamada direta se algo falhar
            this.atualizarListaDias();
          }
        }
      });
    });

    // Hook no fetch - desencadear atualização apropriada dependendo do endpoint
    const originalFetch = window.fetch;
    window.fetch = async function (...args) {
      // Inicia a requisição normalmente
      const response = await originalFetch.apply(this, args);

      try {
        const url = args[0]?.toString() || "";

        // Se for exclusão, forçar atualização imediata (sem esperar timeout extra)
        if (url.includes("excluir-entrada")) {
          if (typeof ListaDiasManagerCorrigido !== "undefined") {
            ListaDiasManagerCorrigido.atualizandoAtualmente = false;
            // Forçar atualização direta
            ListaDiasManagerCorrigido.forcarAtualizacao();
          }
        } else if (
          url.includes("cadastrar-valor") ||
          url.includes("dados_banca")
        ) {
          // Pequeno delay para permitir que o servidor finalize mudanças antes de refetchar
          setTimeout(() => {
            if (typeof ListaDiasManagerCorrigido !== "undefined") {
              ListaDiasManagerCorrigido.atualizandoAtualmente = false;
              ListaDiasManagerCorrigido.atualizarListaDias();
            }
          }, 200);
        }
      } catch (e) {
        // silencioso
      }

      return response;
    };

    // Listener otimista para cliques em botões de exclusão — atualiza a lista imediatamente
    document.addEventListener(
      "click",
      (e) => {
        try {
          const target = e.target.closest(
            '.btn-excluir, .excluir-entrada, [data-action="excluir"], button[data-excluir], a.excluir-entrada, .btn-delete'
          );
          if (target) {
            if (typeof ListaDiasManagerCorrigido !== "undefined") {
              ListaDiasManagerCorrigido.atualizandoAtualmente = false;
              ListaDiasManagerCorrigido.forcarAtualizacao();
            }
            if (typeof MetaMensalManager !== "undefined") {
              MetaMensalManager.atualizandoAtualmente = false;
              MetaMensalManager.atualizarMetaMensal(false);
            }
            if (typeof PlacarMensalManager !== "undefined") {
              PlacarMensalManager.atualizandoAtualmente = false;
              PlacarMensalManager.atualizarPlacarMensal();
            }
          }
        } catch (err) {
          // silencioso
        }
      },
      true
    );

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

// SISTEMA MONITOR CONTÍNUO - VERIFICAÇÃO RIGOROSA DE META
(function () {
  "use strict";

  console.log("Sistema Monitor Contínuo - VERIFICAÇÃO RIGOROSA...");

  const MonitorContinuo = {
    ativo: false,
    intervaloMonitor: null,
    intervaloForcador: null,
    intervaloForçaBruta: null,
    estadoCorretoHoje: null,
    ultimoRotulo: "",
    metasBatidasCache: new Map(),
    metaHistoricaCache: new Map(),
    verificandoHistorico: false,
    forcarTrofeuHoje: false,
    ultimoSaldoHoje: 0,
    ultimaMetaHoje: 0,
    // NOVA: Configuração rigorosa
    metaDiariaConfigurada: 0,
    modoRigoroso: true,

    inicializar() {
      console.log("Iniciando monitor RIGOROSO...");

      this.ativo = true;
      this.destruirTudo();

      // Primeiro buscar meta real
      this.buscarMetaRealDoSistema();

      // Verificação inicial limpa
      this.limparTodosOsTrofeus();

      // Verificar hoje primeiro
      setTimeout(() => {
        this.verificarMetaDiariaHojeAgora();
      }, 500);

      // Verificação histórica rigorosa
      setTimeout(() => {
        this.verificarMetasHistoricasRigoroso();
      }, 1000);

      // Monitor principal (cada 3 segundos) - menos agressivo
      this.intervaloMonitor = setInterval(() => {
        this.monitorarRotulo();
        this.verificarMetaDiariaHojeAgora();
      }, 3000);

      // Forçador normal (cada 5 segundos)
      this.intervaloForcador = setInterval(() => {
        this.forcarEstadoCorreto();
      }, 5000);

      // Forçador BRUTO especificamente para hoje (cada 2 segundos)
      this.intervaloForçaBruta = setInterval(() => {
        this.forcarTrofeuHojeBruto();
      }, 2000);

      console.log("Monitor RIGOROSO ativo");
    },

    destruirTudo() {
      console.log("DESTRUINDO sistemas de troféu...");

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

    // NOVA FUNÇÃO: Buscar meta real do sistema
    async buscarMetaRealDoSistema() {
      try {
        console.log("Buscando meta real do sistema...");

        // Tentar múltiplas fontes
        let metaEncontrada = 0;

        // Fonte 1: dados-mes-info
        const dadosMesInfo = document.getElementById("dados-mes-info");
        if (dadosMesInfo) {
          const metaInfo = dadosMesInfo.getAttribute("data-meta-diaria");
          if (metaInfo) {
            metaEncontrada = parseFloat(metaInfo) || 0;
            console.log(
              `Meta do dados-mes-info: R$ ${metaEncontrada.toFixed(2)}`
            );
          }
        }

        // Fonte 2: PHP
        if (metaEncontrada === 0) {
          try {
            const response = await fetch("dados_banca.php", {
              method: "GET",
              headers: {
                "Cache-Control": "no-cache",
                "X-Requested-With": "XMLHttpRequest",
              },
            });

            if (response.ok) {
              const data = await response.json();
              if (data.success && data.meta_diaria) {
                metaEncontrada = parseFloat(data.meta_diaria) || 0;
                console.log(`Meta do PHP: R$ ${metaEncontrada.toFixed(2)}`);
              }
            }
          } catch (e) {
            console.log("Erro ao buscar meta do PHP:", e);
          }
        }

        // Salvar meta configurada
        this.metaDiariaConfigurada = metaEncontrada;
        this.ultimaMetaHoje = metaEncontrada;

        console.log(
          `META CONFIGURADA: R$ ${this.metaDiariaConfigurada.toFixed(2)}`
        );

        return metaEncontrada;
      } catch (error) {
        console.error("Erro ao buscar meta real:", error);
        this.metaDiariaConfigurada = 0;
        return 0;
      }
    },

    // NOVA FUNÇÃO: Limpar todos os troféus inicialmente
    limparTodosOsTrofeus() {
      try {
        console.log("Limpando TODOS os troféus para verificação rigorosa...");

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
          "Todos os troféus limpos - só serão adicionados se meta realmente batida"
        );
      } catch (error) {
        console.error("Erro ao limpar troféus:", error);
      }
    },

    // FUNÇÃO CORRIGIDA: Verificação rigorosa da meta de hoje
    async verificarMetaDiariaHojeAgora() {
      try {
        const hoje = this.obterDataHoje();
        const linha = document.querySelector(`[data-date="${hoje}"]`);

        if (!linha) {
          console.log("Linha de hoje não encontrada");
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

        // Usar meta configurada do sistema
        let metaDiaria = this.metaDiariaConfigurada;

        // Se não tem meta configurada, buscar novamente
        if (metaDiaria === 0) {
          metaDiaria = await this.buscarMetaRealDoSistema();
        }

        // Salvar para comparação
        this.ultimoSaldoHoje = saldoAtual;
        this.ultimaMetaHoje = metaDiaria;

        // VERIFICAÇÃO RIGOROSA: Só considera meta batida se:
        // 1. Tem meta configurada E saldo >= meta
        // 2. OU se não tem meta, saldo deve ser >= R$ 200 (muito restritivo)
        let metaBatida = false;

        if (metaDiaria > 0) {
          metaBatida = saldoAtual >= metaDiaria;
          console.log(
            `HOJE: Saldo R$ ${saldoAtual.toFixed(2)} ${
              metaBatida ? ">=" : "<"
            } Meta R$ ${metaDiaria.toFixed(2)} = ${
              metaBatida ? "BATIDA" : "NÃO BATIDA"
            }`
          );
        } else {
          // Sem meta configurada: critério MUITO restritivo
          metaBatida = saldoAtual >= 200;
          console.log(
            `HOJE (sem meta): Saldo R$ ${saldoAtual.toFixed(2)} ${
              metaBatida ? ">=" : "<"
            } R$ 200,00 = ${metaBatida ? "BATIDA" : "NÃO BATIDA"}`
          );
        }

        // Atualizar flags
        this.forcarTrofeuHoje = metaBatida;
        this.estadoCorretoHoje = metaBatida;

        // Atualizar cache
        if (metaBatida) {
          this.metasBatidasCache.set(hoje, true);
        } else {
          this.metasBatidasCache.delete(hoje);
        }

        return metaBatida;
      } catch (error) {
        console.error("Erro ao verificar meta de hoje:", error);
        return false;
      }
    },

    // NOVA FUNÇÃO: Verificação histórica RIGOROSA
    async verificarMetasHistoricasRigoroso() {
      if (this.verificandoHistorico) return;

      this.verificandoHistorico = true;
      console.log("Verificando metas históricas RIGOROSAMENTE...");

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

        console.log("Verificação histórica RIGOROSA concluída");
      } catch (error) {
        console.error("Erro ao verificar metas históricas rigorosas:", error);
      } finally {
        this.verificandoHistorico = false;
      }
    },

    // NOVA FUNÇÃO: Verificação rigorosa de meta específica
    async verificarMetaEspecificaRigoroso(data) {
      try {
        const linha = document.querySelector(`[data-date="${data}"]`);
        if (!linha) return;

        const valorElement = linha.querySelector(".valor");
        if (!valorElement) return;

        const valorTexto = valorElement.textContent
          .replace(/[^\d,-]/g, "")
          .replace(",", ".");
        const saldoDia = parseFloat(valorTexto) || 0;

        // Usar a meta configurada do sistema
        const metaDiaria = this.metaDiariaConfigurada;

        // VERIFICAÇÃO RIGOROSA
        let metaBatida = false;
        let criterioUsado = "";

        if (metaDiaria > 0) {
          // Com meta configurada: deve bater EXATAMENTE a meta
          metaBatida = saldoDia >= metaDiaria;
          criterioUsado = `Meta configurada R$ ${metaDiaria.toFixed(2)}`;
        } else {
          // Sem meta configurada: critério MUITO restritivo
          metaBatida = saldoDia >= 200;
          criterioUsado = "Critério restritivo R$ 200,00";
        }

        console.log(
          `${data}: R$ ${saldoDia.toFixed(2)} vs ${criterioUsado} = ${
            metaBatida ? "BATIDA" : "NÃO BATIDA"
          }`
        );

        // Salvar no cache
        this.metaHistoricaCache.set(data, {
          saldoDia: saldoDia,
          metaDiaria: metaDiaria,
          metaBatida: metaBatida,
          criterioUsado: criterioUsado,
          dataVerificacao: new Date().toISOString(),
        });

        // Atualizar cache de metas batidas
        if (metaBatida) {
          this.metasBatidasCache.set(data, true);
        } else {
          this.metasBatidasCache.delete(data);
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
          console.log(`RÓTULO MUDOU: "${rotuloTexto}"`);
          this.ultimoRotulo = rotuloTexto;

          // Sempre verificar meta diária real, não interpretar rótulo
          this.verificarMetaDiariaHojeAgora();
        }
      } catch (error) {
        console.error("Erro no monitor de rótulo:", error);
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

        // FORÇA BRUTA: Se deve ter troféu mas não tem, aplicar IMEDIATAMENTE
        if (!icone.classList.contains("fa-trophy")) {
          console.log("FORÇA BRUTA: Aplicando troféu de hoje");
          this.aplicarTrofeuForcado(icone, linha);
        }
      } catch (error) {
        console.error("Erro na força bruta:", error);
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
        console.log(`FORÇADOS: ${forcacoesFeitas} ícones`);
      }
    },

    deveExibirTrofeu(dataLinha, hoje) {
      // Hoje: usar verificação direta
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
        console.error("Erro ao aplicar troféu:", e);
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

      if (this.intervaloForçaBruta) {
        clearInterval(this.intervaloForçaBruta);
        this.intervaloForçaBruta = null;
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
        modo: "RIGOROSO - Só troféu se meta realmente batida",
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
      console.log("Reverificação RIGOROSA iniciada...");

      // Buscar meta real primeiro
      await MonitorContinuo.buscarMetaRealDoSistema();

      // Limpar tudo
      MonitorContinuo.limparTodosOsTrofeus();

      // Verificar hoje
      await MonitorContinuo.verificarMetaDiariaHojeAgora();

      // Verificar histórico
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

    // Ver cache detalhado com informações de Meta Fixa vs Turbo
    verCache: () => {
      const historico = Array.from(
        MonitorContinuo.metaHistoricaCache.entries()
      );
      console.log("CACHE RIGOROSO COM DETALHES DE META:");
      historico.forEach(([data, info]) => {
        console.log(
          `  ${data}: R$ ${info.saldoDia.toFixed(2)} vs ${
            info.criterioUsado
          } = ${info.metaBatida ? "BATIDA" : "NÃO BATIDA"}`
        );
        if (info.detalhesCalculo) {
          console.log(`    ${info.detalhesCalculo.observacao}`);
          console.log(`    Fórmula: ${info.detalhesCalculo.formula}`);
        }
      });
      return historico;
    },

    // Debug detalhado com informações de Meta Turbo
    debug: () => {
      console.log("DEBUG RIGOROSO COM META TURBO:");
      console.log(
        `  Meta configurada base: R$ ${MonitorContinuo.metaDiariaConfigurada.toFixed(
          2
        )}`
      );
      console.log(`  Modo rigoroso: ${MonitorContinuo.modoRigoroso}`);
      console.log(
        `  Total troféus válidos: ${MonitorContinuo.metasBatidasCache.size}`
      );

      const hoje = MonitorContinuo.obterDataHoje();
      const linha = document.querySelector(`[data-date="${hoje}"]`);

      if (linha) {
        const icone = linha.querySelector(".icone i");
        const valor = linha.querySelector(".valor");

        console.log("  HOJE:");
        console.log(`    Data: ${hoje}`);
        console.log(`    Saldo: ${valor ? valor.textContent : "N/A"}`);
        console.log(`    Ícone: ${icone ? icone.className : "N/A"}`);
        console.log(`    Deve ter troféu: ${MonitorContinuo.forcarTrofeuHoje}`);

        // Mostrar detalhes do cache se existir
        const cacheHoje = MonitorContinuo.metaHistoricaCache.get(hoje);
        if (cacheHoje && cacheHoje.detalhesCalculo) {
          console.log(`    Tipo de meta: ${cacheHoje.detalhesCalculo.tipo}`);
          console.log(`    ${cacheHoje.detalhesCalculo.observacao}`);
          console.log(`    Fórmula: ${cacheHoje.detalhesCalculo.formula}`);
        }
      }
    },

    // NOVO: Debug específico para uma data
    debugData: async (data) => {
      const linha = document.querySelector(`[data-date="${data}"]`);

      if (linha) {
        const icone = linha.querySelector(".icone i");
        const valor = linha.querySelector(".valor");
        const cacheInfo = MonitorContinuo.metaHistoricaCache.get(data);

        console.log(`DEBUG DETALHADO ${data}:`);
        console.log("  Saldo na tela:", valor ? valor.textContent : "N/A");
        console.log("  Ícone atual:", icone ? icone.className : "N/A");
        console.log(
          "  Cache tem troféu:",
          MonitorContinuo.metasBatidasCache.has(data)
        );

        if (cacheInfo) {
          console.log("  Cache detalhado:", cacheInfo);
          if (cacheInfo.detalhesCalculo) {
            console.log(`  Tipo: ${cacheInfo.detalhesCalculo.tipo}`);
            console.log(`  ${cacheInfo.detalhesCalculo.observacao}`);
            console.log(`  Fórmula: ${cacheInfo.detalhesCalculo.formula}`);
          }
        } else {
          console.log("  Não há dados no cache - recalculando...");

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
          console.log("  Cálculo específico:", dadosMetaEspecifica);
        }
      } else {
        console.log(`Linha não encontrada para data ${data}`);
      }
    },

    // NOVO: Comparar Meta Fixa vs Meta Turbo para uma data específica
    compararMetas: async (data) => {
      console.log(`COMPARANDO META FIXA vs TURBO para ${data}:`);

      const linha = document.querySelector(`[data-date="${data}"]`);
      if (!linha) {
        console.log("Data não encontrada");
        return;
      }

      const valorElement = linha.querySelector(".valor");
      const saldoDia = valorElement
        ? parseFloat(
            valorElement.textContent.replace(/[^\d,-]/g, "").replace(",", ".")
          ) || 0
        : 0;

      // Buscar configuração atual
      const config = await MonitorContinuo.buscarConfiguracaoCompleta();
      if (!config) {
        console.log("Erro ao buscar configuração");
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
        `  Lucro acumulado até ${data}: R$ ${lucroAcumulado.toFixed(2)}`
      );
      console.log(`  Configuração: ${config.diaria}% × ${config.unidade}`);
      console.log("");
      console.log(`  META FIXA:`);
      console.log(
        `    Base: R$ ${config.bancaInicial.toFixed(2)} (sempre banca inicial)`
      );
      console.log(`    Meta: R$ ${metaFixa.toFixed(2)}`);
      console.log(`    Resultado: ${resultadoFixa ? "BATIDA" : "NÃO BATIDA"}`);
      console.log("");
      console.log(`  META TURBO:`);
      console.log(
        `    Base: R$ ${bancaTurbo.toFixed(2)} (banca inicial ${
          lucroAcumulado > 0 ? "+ lucro" : "sem lucro"
        })`
      );
      console.log(`    Meta: R$ ${metaTurbo.toFixed(2)}`);
      console.log(`    Resultado: ${resultadoTurbo ? "BATIDA" : "NÃO BATIDA"}`);
      console.log("");
      console.log(`  TIPO ATUAL CONFIGURADO: ${config.tipoMeta.toUpperCase()}`);
      console.log(
        `  RESULTADO APLICADO: ${
          config.tipoMeta === "fixa"
            ? resultadoFixa
              ? "BATIDA"
              : "NÃO BATIDA"
            : resultadoTurbo
            ? "BATIDA"
            : "NÃO BATIDA"
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

  // Interceptar mudanças de período
  function interceptarMudancasPeriodo() {
    const radios = document.querySelectorAll('input[name="periodo"]');

    radios.forEach((radio) => {
      radio.addEventListener("change", function (e) {
        console.log(`INTERCEPTADO: Mudança para ${e.target.value}`);

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

  // Inicialização
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
  console.log("   - Verificação RIGOROSA de metas");
  console.log(
    "   - Limpa todos os troféus e só adiciona se meta realmente batida"
  );
  console.log("   - Busca meta real do sistema");
  console.log("   - Critério restritivo se não há meta configurada");
  console.log("");
  console.log("Comandos:");
  console.log(
    "   MonitorContinuo.reverificarRigoroso() - Reverificar com rigor"
  );
  console.log(
    "   MonitorContinuo.configurarMeta(100) - Configurar meta manualmente"
  );
  console.log("   MonitorContinuo.verCache() - Ver verificações detalhadas");
  console.log("   MonitorContinuo.debug() - Debug rigoroso");
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
  processarCompleto() {
    const agoraTs = Date.now();
    // Evitar reexecuções muito rápidas que competem com re-renders
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

      // Aplicar ícone de troféu se meta batida
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

    // Intervalo ÚNICO de 7 segundos (reduzido impacto de CPU)
    this.intervalo = setInterval(() => {
      this.processarCompleto();
    }, 7000);

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

// Rolagem para a linha do dia atual (gd-dia-hoje) dentro de .lista-dias
// Rolagem para a linha do dia atual (gd-dia-hoje) com fallback robusto
function scrollToHoje() {
  try {
    const hojeEl = document.querySelector(".gd-dia-hoje");
    if (!hojeEl) return false;

    // Encontra o ancestral rolável mais próximo
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
      // fallback: página inteira
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
      // Calcular posição relativa ao container e rolar esse container
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

// Tentar rolar para hoje agora; se o elemento ainda não existir ou for criado depois,
// usar MutationObserver para disparar quando a linha for adicionada.
(function ensureScrollToHoje() {
  const tried = scrollToHoje();
  if (tried) return;

  // Observar a lista de dias se existir, senão observar o body
  const lista = document.querySelector(".lista-dias") || document.body;
  if (!lista) return;

  const mo = new MutationObserver((mutations, observer) => {
    if (document.querySelector(".gd-dia-hoje")) {
      scrollToHoje();
      observer.disconnect();
    }
  });

  mo.observe(lista, { childList: true, subtree: true });

  // Timeout de segurança para desconectar o observer após 6s
  setTimeout(() => {
    try {
      mo.disconnect();
    } catch (e) {}
  }, 6000);
})();

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
//                                  CODIGO PARA FORÇAR CORES FIXAS DO PLACAR
// ========================================================================================================================

// SISTEMA FINAL - PRIORIDADE ABSOLUTA PARA CORES CINZA
// SOLUÇÃO FINAL - SISTEMA QUE SEMPRE VENCE A CORRIDA
(function () {
  "use strict";

  let cacheElementos = new Map();

  function obterDataHoje() {
    const d = new Date();
    const ano = d.getFullYear();
    const mes = String(d.getMonth() + 1).padStart(2, "0");
    const dia = String(d.getDate()).padStart(2, "0");
    return `${ano}-${mes}-${dia}`;
  }

  function aplicarCorDefinitiva(elemento, cor) {
    if (!elemento) return;

    // Criar uma função que força a cor constantemente
    const forcarCor = () => {
      if (elemento.style.color !== cor) {
        elemento.style.setProperty("color", cor, "important");
      }
    };

    // Aplicar imediatamente
    forcarCor();

    // Observador individual que reage INSTANTANEAMENTE
    const observer = new MutationObserver(forcarCor);
    observer.observe(elemento, {
      attributes: true,
      attributeFilter: ["style", "class"],
    });

    // Interval de alta frequência só para este elemento
    const interval = setInterval(forcarCor, 1000); // reduzir frequência para evitar carga

    // Salvar no cache
    cacheElementos.set(elemento, { cor, observer, interval });
  }

  function processarPlacares() {
    const placares = document.querySelectorAll(".gd-linha-dia .placar-dia");

    placares.forEach((placarContainer) => {
      const linhaDia = placarContainer.closest(".gd-linha-dia");
      if (!linhaDia) return;

      const valorElement = linhaDia.querySelector(".valor");

      // Verificar se o saldo é zero
      let ehSaldoZero = false;
      if (valorElement) {
        const valorTexto = valorElement.textContent.trim();
        ehSaldoZero =
          valorTexto === "R$ 0,00" ||
          valorTexto === "0,00" ||
          valorTexto === "R$ 0.00" ||
          valorTexto === "0.00" ||
          linhaDia.classList.contains("valor-zero");
      }

      const green = placarContainer.querySelector(
        ".placar-green, .placar.placar-green"
      );
      const red = placarContainer.querySelector(
        ".placar-red, .placar.placar-red"
      );
      const separator = placarContainer.querySelector(".separador");

      // Verificar se ambos green e red são zero
      const greenVal = green ? parseInt(green.textContent.trim()) || 0 : 0;
      const redVal = red ? parseInt(red.textContent.trim()) || 0 : 0;
      const placardZeroValores = greenVal === 0 && redVal === 0;

      const deveSerCinza = ehSaldoZero || placardZeroValores;

      // Aplicar cor definitiva para cada elemento
      if (deveSerCinza) {
        aplicarCorDefinitiva(green, "#94a3b8");
        aplicarCorDefinitiva(red, "#94a3b8");
        aplicarCorDefinitiva(separator, "#94a3b8");
      } else {
        aplicarCorDefinitiva(green, "#03a158"); // Verde
        aplicarCorDefinitiva(red, "#e93a3a"); // Vermelho
        aplicarCorDefinitiva(separator, "#6d6b6b"); // Separador
      }
    });
  }

  // Processar múltiplas vezes no início
  processarPlacares();
  setTimeout(processarPlacares, 100);
  setTimeout(processarPlacares, 300);
  setTimeout(processarPlacares, 500);
  setTimeout(processarPlacares, 1000);

  // Observador para novos elementos
  const observadorNovos = new MutationObserver((mutations) => {
    let temNovosPlacares = false;

    mutations.forEach((mutation) => {
      if (mutation.type === "childList") {
        mutation.addedNodes.forEach((node) => {
          if (node.nodeType === Node.ELEMENT_NODE) {
            if (node.classList && node.classList.contains("gd-linha-dia")) {
              temNovosPlacares = true;
            }
            if (node.querySelectorAll) {
              const placares = node.querySelectorAll(".gd-linha-dia");
              if (placares.length > 0) temNovosPlacares = true;
            }
          }
        });
      }
    });

    if (temNovosPlacares) {
      setTimeout(processarPlacares, 50);
    }
  });

  const lista = document.querySelector(".lista-dias");
  if (lista) {
    observadorNovos.observe(lista, {
      childList: true,
      subtree: true,
    });
  }

  // Função de limpeza (se necessário)
  window.limparSistemaPlacar = function () {
    cacheElementos.forEach(({ observer, interval }) => {
      observer.disconnect();
      clearInterval(interval);
    });
    cacheElementos.clear();
  };

  console.log(
    "Sistema que SEMPRE VENCE ativo - cada elemento protegido individualmente"
  );
})();

// CSS que força estabilidade visual
const cssEstavel = document.createElement("style");
cssEstavel.textContent = `
  .gd-linha-dia .placar-dia .placar,
  .gd-linha-dia .placar-dia .separador {
    transition: none !important;
    animation: none !important;
    will-change: auto !important;
  }
`;
document.head.appendChild(cssEstavel);
// ========================================================================================================================
//                                  CODIGO PARA FORÇAR CORES FIXAS DO PLACAR
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
//
//
//
//
// ✅ FUNÇÃO PARA FORÇAR ATUALIZAÇÃO DO BLOCO 2
window.forcarBloco2 = function () {
  if (typeof MetaMensalManager !== "undefined") {
    console.log("🔄 Forçando atualização do Bloco 2...");
    MetaMensalManager.atualizandoAtualmente = false;
    MetaMensalManager.atualizarMetaMensal(true);
  }
};

// ✅ FUNÇÃO PARA DEBUG DO BLOCO 2
window.debugBloco2 = function () {
  const metaElement = document.getElementById("meta-valor-2");
  const rotuloElement = document.getElementById("rotulo-meta-2");
  const barraElement = document.getElementById("barra-progresso-2");

  console.log("📊 DEBUG BLOCO 2:");
  console.log(
    "Meta Element:",
    metaElement ? metaElement.innerHTML : "NÃO ENCONTRADO"
  );
  console.log(
    "Rótulo Element:",
    rotuloElement ? rotuloElement.innerHTML : "NÃO ENCONTRADO"
  );
  console.log(
    "Barra Element:",
    barraElement ? barraElement.style.width : "NÃO ENCONTRADO"
  );
  console.log(
    "MetaMensalManager ativo:",
    typeof MetaMensalManager !== "undefined"
  );

  if (typeof MetaMensalManager !== "undefined") {
    console.log(
      "Atualizando atualmente:",
      MetaMensalManager.atualizandoAtualmente
    );
    console.log("Período atual:", MetaMensalManager.periodoFixo);
  }
};
