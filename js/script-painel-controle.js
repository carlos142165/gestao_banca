document.addEventListener("DOMContentLoaded", () => {
  // ✅ INICIALIZAÇÃO BÁSICA
  if (typeof atualizarLucroEBancaViaAjax === "function") {
    atualizarLucroEBancaViaAjax();
  }

  const botaoGerencia = document.getElementById("abrirGerenciaBanca");
  const modal = document.getElementById("modalDeposito");
  const botaoFechar = modal?.querySelector(".btn-fechar");

  // ✅ VARIÁVEIS DO FORMULÁRIO MENTOR
  const formularioMentor = document.getElementById("formulario-mentor");
  const botaoFecharMentor = document.getElementById("botao-fechar");
  const formMentor = document.getElementById("form-mentor");

  let modalInicializado = false;
  let valorOriginalBanca = 0;
  let metaFixaRadio, metaTurboRadio;
  // Expor wrappers globais para permitir que outros scripts abram/inicializem o modal
  try {
    window.abrirModalDeposito = function () {
      if (modal) {
        modal.style.display = "flex";
        modal.classList.add("ativo");
        document.body.style.overflow = "hidden";
        try {
          if (typeof inicializarModalDeposito === "function")
            inicializarModalDeposito();
        } catch (e) {
          console.warn("Falha ao inicializar modal via wrapper:", e);
        }
      }
    };

    window.inicializarModalDeposito = function () {
      try {
        if (typeof inicializarModalDeposito === "function")
          inicializarModalDeposito();
      } catch (e) {
        console.warn("Falha ao chamar inicializarModalDeposito():", e);
      }
    };
  } catch (e) {
    // não crítico
  }
  // Variáveis globais necessárias em outras funções
  let diaria, unidade, oddsMeta;
  let resultadoCalculo, resultadoUnidade, resultadoOdds;
  let valorBancaInput, mensagemErro;

  // ✅ FUNÇÃO PRINCIPAL PARA ATUALIZAR ÁREA DIREITA EM TEMPO REAL
  function atualizarAreaDireita(dadosResposta = null) {
    // ✅ VERIFICAR SE MODAL ESTÁ ABERTO ANTES DE QUALQUER COISA
    const modalAberto = document.getElementById("modalDeposito");
    if (
      modalAberto &&
      (modalAberto.style.display === "flex" ||
        modalAberto.style.display === "block")
    ) {
      console.log("⏸️ Modal aberto - pausando atualização da área direita");
      return Promise.resolve();
    }

    console.log("🔄 Iniciando atualização da área direita...");

    // ✅ Se temos dados da resposta de uma operação, usa eles diretamente
    if (dadosResposta && dadosResposta.success) {
      atualizarElementosAreaDireita(dadosResposta);
      return Promise.resolve();
    }

    // ✅ INCLUIR PERÍODO ATUAL NA REQUISIÇÃO
    const formData = new FormData();
    if (typeof SistemaFiltroPeriodo !== "undefined") {
      formData.append("periodo", SistemaFiltroPeriodo.periodoAtual);
    }

    // ✅ Busca dados atualizados do servidor com período
    return fetch("dados_banca.php", {
      method: "POST", // MUDANÇA: de GET para POST
      body: formData, // ADIÇÃO: inclui o período
      headers: {
        "Cache-Control": "no-cache",
        "X-Requested-With": "XMLHttpRequest",
      },
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}`);
        }
        return response.json();
      })
      .then((data) => {
        if (!data.success) {
          console.warn("⚠️ Erro nos dados da banca:", data);
          // ✅ FALLBACK: se dados_banca.php falhar, tenta ajax_deposito.php
          return fetch("ajax_deposito.php")
            .then((res) => res.json())
            .then((fallbackData) => {
              if (fallbackData.success) {
                atualizarElementosAreaDireitaFallback(fallbackData);
              }
            });
        }
        atualizarElementosAreaDireita(data);
      })
      .catch((error) => {
        console.error("❌ Erro ao atualizar área direita:", error);
        // ✅ FALLBACK em caso de erro
        atualizarAreaDireitaFallback();
      });
  }

  // ✅ FUNÇÃO FALLBACK PARA CALCULAR LOCALMENTE
  function atualizarAreaDireitaFallback() {
    console.log("🔄 Usando fallback local para cálculos...");

    fetch("ajax_deposito.php")
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          // ✅ Calcula localmente os valores
          const banca = parseFloat(data.banca) || 0;
          const diariaPercent = parseFloat(data.diaria) || 2;
          const unidadeEntrada = banca * (diariaPercent / 100);

          const dadosCalculados = {
            success: true,
            diaria_formatada: `${diariaPercent.toFixed(0)}%`,
            unidade_entrada_formatada: unidadeEntrada.toLocaleString("pt-BR", {
              style: "currency",
              currency: "BRL",
            }),
            banca_formatada: banca.toLocaleString("pt-BR", {
              style: "currency",
              currency: "BRL",
            }),
            lucro_formatado: parseFloat(data.lucro || 0).toLocaleString(
              "pt-BR",
              {
                style: "currency",
                currency: "BRL",
              }
            ),
          };

          atualizarElementosAreaDireita(dadosCalculados);
        }
      })
      .catch((error) => {
        console.error("❌ Fallback também falhou:", error);
      });
  }

  // ✅ FUNÇÃO AUXILIAR PARA ATUALIZAR OS ELEMENTOS DOM
  function atualizarElementosAreaDireita(data) {
    console.log("📊 Dados recebidos para atualização:", data);

    // ✅ ATUALIZAR PORCENTAGEM DIÁRIA
    const porcentagemElement = document.getElementById("porcentagem-diaria");
    if (porcentagemElement && data.diaria_formatada) {
      // ✅ Atualização imediata sem delay
      porcentagemElement.style.transition = "opacity 0.1s ease";
      porcentagemElement.textContent = data.diaria_formatada;
      porcentagemElement.style.opacity = "1";
    }

    // ✅ ATUALIZAR VALOR UNIDADE - PRIORIDADE MÁXIMA
    const valorUnidadeElement = document.getElementById("valor-unidade");
    if (valorUnidadeElement && data.unidade_entrada_formatada) {
      // ✅ Atualização instantânea
      valorUnidadeElement.style.transition = "opacity 0.1s ease";
      valorUnidadeElement.textContent = data.unidade_entrada_formatada;
      valorUnidadeElement.style.opacity = "1";

      // ✅ Adiciona classe para indicar atualização
      valorUnidadeElement.classList.add("updated");
      setTimeout(() => {
        valorUnidadeElement.classList.remove("updated");
      }, 1000);
    }

    // ✅ ATUALIZAR BANCA SE DISPONÍVEL
    const bancaElement = document.getElementById("valor-banca-atual");
    if (bancaElement && data.banca_formatada) {
      bancaElement.style.transition = "opacity 0.1s ease";
      bancaElement.textContent = data.banca_formatada;
      bancaElement.style.opacity = "1";
    }

    // ✅ ATUALIZAR LUCRO SE DISPONÍVEL
    const lucroElement = document.getElementById("valor-lucro-atual");
    if (lucroElement && data.lucro_formatado) {
      lucroElement.style.transition = "opacity 0.1s ease";
      lucroElement.textContent = data.lucro_formatado;
      lucroElement.style.opacity = "1";
    }

    console.log("✅ Área direita atualizada INSTANTANEAMENTE:", {
      porcentagem: data.diaria_formatada,
      unidade: data.unidade_entrada_formatada,
      banca: data.banca_formatada,
      lucro: data.lucro_formatado,
    });
  }

  // ✅ FUNÇÃO AUXILIAR PARA FALLBACK
  function atualizarElementosAreaDireitaFallback(data) {
    // ✅ Calcula a unidade localmente para maior velocidade
    const banca = parseFloat(data.banca) || 0;
    const diariaPercent = parseFloat(data.diaria) || 2;
    const unidadeEntrada = banca * (diariaPercent / 100);

    const dadosCalculados = {
      diaria_formatada: `${diariaPercent.toFixed(0)}%`,
      unidade_entrada_formatada: unidadeEntrada.toLocaleString("pt-BR", {
        style: "currency",
        currency: "BRL",
      }),
      banca_formatada: banca.toLocaleString("pt-BR", {
        style: "currency",
        currency: "BRL",
      }),
      lucro_formatado: parseFloat(data.lucro || 0).toLocaleString("pt-BR", {
        style: "currency",
        currency: "BRL",
      }),
    };

    atualizarElementosAreaDireita(dadosCalculados);
  }

  // ✅ FUNÇÃO PARA EXIBIR NOTIFICAÇÕES
  // Exibe todas as mensagens toast em um único local no topo direito, cor amarela, sem background
  function exibirNotificacao(mensagem, tipo = "aviso") {
    let toast = document.getElementById("toast-msg");
    if (!toast) {
      toast = document.createElement("div");
      toast.id = "toast-msg";
      toast.className = "toast";
      document.body.appendChild(toast);
    }
    toast.textContent = mensagem;
    toast.className = `toast ativo ${tipo}`;
    setTimeout(() => {
      toast.className = "toast";
      toast.textContent = "";
    }, 3500);
  }

  // ✅ FUNÇÃO CENTRALIZADA PARA ATUALIZAÇÕES IMEDIATAS
  function executarAtualizacaoImediata(tipoOperacao, resultado = null) {
    console.log(`🚀 Executando atualização imediata para: ${tipoOperacao}`);

    // ✅ 1. Primeiro tenta usar os dados da resposta
    if (resultado && resultado.dados_atualizados) {
      atualizarAreaDireita(resultado.dados_atualizados);
    }

    // ✅ 2. Backup: Atualização imediata sem delay
    atualizarAreaDireita();

    // ✅ 3. Força atualização após 50ms para garantir
    setTimeout(() => atualizarAreaDireita(), 50);

    // ✅ 4. Atualização de segurança após 200ms
    setTimeout(() => atualizarAreaDireita(), 200);

    // ✅ ATUALIZAR OUTRAS ÁREAS SE NECESSÁRIO
    if (typeof atualizarLucroEBancaViaAjax === "function") {
      atualizarLucroEBancaViaAjax();
    }

    // ✅ DISPATCH EVENT CUSTOMIZADO PARA OUTRAS PARTES DO SISTEMA
    const eventDetails = {
      tipo: tipoOperacao,
      timestamp: Date.now(),
    };

    if (resultado) {
      eventDetails.resultado = resultado;
    }

    document.dispatchEvent(
      new CustomEvent("areaAtualizacao", {
        detail: eventDetails,
      })
    );

    // ✅ ATUALIZAR TABELAS OU LISTAS SE EXISTIREM
    const tabelaMentores = document.getElementById("tabela-mentores");
    if (tabelaMentores && typeof atualizarTabelaMentores === "function") {
      setTimeout(() => atualizarTabelaMentores(), 100);
    }
  }

  // ✅ EVENTOS DO FORMULÁRIO MENTOR
  if (formMentor) {
    // ✅ MÁSCARA DE DINHEIRO PARA O CAMPO VALOR
    const campoValor = document.getElementById("valor");
    if (campoValor) {
      campoValor.addEventListener("input", (e) => {
        let valor = e.target.value.replace(/[^\d]/g, "");
        if (!valor) {
          e.target.value = "";
          return;
        }

        const valorNumerico = parseFloat(valor) / 100;
        e.target.value = valorNumerico.toLocaleString("pt-BR", {
          style: "currency",
          currency: "BRL",
        });
      });

      // ✅ SELECIONAR TUDO AO FOCAR
      campoValor.addEventListener("focus", () => {
        campoValor.select();
      });
    }

    // ✅ EVENTO DE SUBMIT DO FORMULÁRIO MENTOR
    formMentor.addEventListener("submit", async (e) => {
      e.preventDefault();

      console.log("📝 Enviando formulário mentor...");

      const formData = new FormData(formMentor);
      const dados = Object.fromEntries(formData.entries());

      // ✅ VALIDAÇÃO BÁSICA
      if (!dados.opcao) {
        exibirNotificacao("⚠️ Selecione Green ou Red", "aviso");
        return;
      }

      if (!dados.valor) {
        exibirNotificacao("⚠️ Digite um valor", "aviso");
        return;
      }

      // ✅ LIMPAR VALOR PARA ENVIO
      const valorLimpo = dados.valor.replace(/[^\d]/g, "");
      const valorNumerico = parseFloat(valorLimpo) / 100;

      if (valorNumerico <= 0) {
        exibirNotificacao("⚠️ Digite um valor válido", "aviso");
        return;
      }

      try {
        // ✅ ENVIAR DADOS PARA O SERVIDOR
        const response = await fetch("processar_mentor.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            id_mentor: dados.id_mentor,
            opcao: dados.opcao,
            valor: valorNumerico.toFixed(2),
          }),
        });

        const resultado = await response.json();

        if (resultado.success) {
          // ✅ SUCESSO - MOSTRAR NOTIFICAÇÃO
          const tipoOperacao = dados.opcao === "green" ? "Green" : "Red";
          const mensagem = `${
            dados.opcao === "green" ? "💚" : "❤️"
          } ${tipoOperacao} de ${valorNumerico.toLocaleString("pt-BR", {
            style: "currency",
            currency: "BRL",
          })} registrado com sucesso!`;

          exibirNotificacao(mensagem, "sucesso");

          // ✅ RESETAR FORMULÁRIO
          formMentor.reset();

          // ✅ LIMPAR SELEÇÕES
          const opcoes = document.querySelectorAll('input[name="opcao"]');
          opcoes.forEach((opcao) => (opcao.checked = false));

          // ✅ FECHAR FORMULÁRIO SE NECESSÁRIO
          if (formularioMentor) {
            formularioMentor.style.display = "none";
          }

          // ✅ ATUALIZAÇÃO MÚLTIPLA E IMEDIATA DA ÁREA DIREITA
          console.log(
            "🚀 Atualizando área direita IMEDIATAMENTE após cadastro..."
          );
          executarAtualizacaoImediata("cadastro", resultado);
        } else {
          // ✅ ERRO DO SERVIDOR
          exibirNotificacao(
            `❌ Erro: ${resultado.message || "Tente novamente"}`,
            "erro"
          );
        }
      } catch (error) {
        console.error("❌ Erro ao enviar formulário:", error);
        exibirNotificacao(
          "🔌 Erro de conexão. Verifique sua internet e tente novamente.",
          "erro"
        );
      }
    });
  }

  // ✅ EVENTO PARA FECHAR FORMULÁRIO MENTOR
  if (botaoFecharMentor && formularioMentor) {
    botaoFecharMentor.addEventListener("click", () => {
      formularioMentor.style.display = "none";
    });
  }

  // ✅ SISTEMA DE DETECÇÃO DE EXCLUSÕES
  function configurarDeteccaoExclusoes() {
    // ✅ LISTENER PARA BOTÕES DE EXCLUSÃO
    document.addEventListener("click", async (event) => {
      // ✅ Detecta cliques em botões de exclusão
      const isDeleteButton =
        event.target.matches(
          '.btn-excluir, .delete-btn, .remove-btn, [data-action="delete"], .fa-trash'
        ) ||
        event.target.closest(
          '.btn-excluir, .delete-btn, .remove-btn, [data-action="delete"]'
        ) ||
        event.target.classList.contains("fa-trash") ||
        event.target.parentElement?.classList.contains("fa-trash");

      if (isDeleteButton) {
        console.log("🗑️ Botão de exclusão detectado!", event.target);

        // ✅ AGUARDA UM POUCO PARA A EXCLUSÃO SER PROCESSADA
        setTimeout(() => {
          console.log("🔄 Atualizando área direita após exclusão...");
          executarAtualizacaoImediata("exclusao");
        }, 200);

        // ✅ SEGUNDA TENTATIVA APÓS MAIS TEMPO
        setTimeout(() => {
          atualizarAreaDireita();
        }, 800);
      }

      // ✅ Detecta confirmações de exclusão (modais, alerts, etc)
      const isConfirmButton =
        event.target.matches(
          ".confirm-delete, .btn-confirmar-exclusao, .swal2-confirm"
        ) ||
        event.target.closest(
          ".confirm-delete, .btn-confirmar-exclusao, .swal2-confirm"
        );

      if (isConfirmButton) {
        console.log("✅ Confirmação de exclusão detectada!");
        setTimeout(() => {
          executarAtualizacaoImediata("confirmacao_exclusao");
        }, 300);
      }
    });

    // ✅ LISTENER PARA TECLA DELETE
    document.addEventListener("keydown", (event) => {
      if (event.key === "Delete" || event.key === "Backspace") {
        // ✅ Verifica se há uma linha/item selecionado
        const selectedItem = document.querySelector(
          '.selected, .active, [data-selected="true"]'
        );
        if (selectedItem) {
          console.log("⌨️ Tecla Delete pressionada com item selecionado");
          setTimeout(() => {
            executarAtualizacaoImediata("exclusao_teclado");
          }, 500);
        }
      }
    });

    // ✅ OBSERVER PARA MUDANÇAS NO DOM (EXCLUSÕES DINÂMICAS)
    const observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        // ✅ Detecta quando elementos são removidos
        if (mutation.type === "childList" && mutation.removedNodes.length > 0) {
          const removedNodes = Array.from(mutation.removedNodes);

          // ✅ Verifica se foi removido algum item de lista/tabela relevante
          const isRelevantRemoval = removedNodes.some((node) => {
            return (
              node.nodeType === 1 &&
              (node.matches("tr, .list-item, .mentor-item, .entrada-item") ||
                node.querySelector?.(
                  "tr, .list-item, .mentor-item, .entrada-item"
                ))
            );
          });

          if (isRelevantRemoval) {
            console.log("👁️ Exclusão detectada via DOM Observer");
            setTimeout(() => {
              executarAtualizacaoImediata("exclusao_dom");
            }, 100);
          }
        }
      });
    });

    // ✅ Observa mudanças em containers relevantes
    const containersToObserve = [
      document.getElementById("tabela-mentores"),
      document.querySelector(".lista-entradas"),
      document.querySelector(".tabela-dados"),
      document.querySelector("tbody"),
      document.querySelector(".container-principal"),
    ].filter(Boolean);

    containersToObserve.forEach((container) => {
      if (container) {
        observer.observe(container, {
          childList: true,
          subtree: true,
        });
      }
    });

    console.log("🔍 Sistema de detecção de exclusões configurado!");
  }

  // ✅ FUNÇÃO GLOBAL PARA EXCLUIR ENTRADA (pode ser chamada de qualquer lugar)
  window.excluirEntrada = async function (id, tipo = "entrada") {
    console.log(`🗑️ Iniciando exclusão de ${tipo} ID: ${id}`);

    try {
      const response = await fetch("excluir_entrada.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          id: id,
          tipo: tipo,
        }),
      });

      const resultado = await response.json();

      if (resultado.success) {
        // ✅ NOTIFICAÇÃO DE SUCESSO
        exibirNotificacao(
          `🗑️ ${
            tipo.charAt(0).toUpperCase() + tipo.slice(1)
          } excluída com sucesso!`,
          "sucesso"
        );

        // ✅ ATUALIZAÇÃO IMEDIATA APÓS EXCLUSÃO
        console.log(
          "🚀 Atualizando área direita IMEDIATAMENTE após exclusão..."
        );
        executarAtualizacaoImediata("exclusao_manual", resultado);

        // ✅ REMOVER ELEMENTO DO DOM SE AINDA EXISTIR
        const elemento = document.querySelector(
          `[data-id="${id}"], #item-${id}, #entrada-${id}`
        );
        if (elemento) {
          elemento.style.transition = "opacity 0.3s ease";
          elemento.style.opacity = "0";
          setTimeout(() => {
            elemento.remove();
          }, 300);
        }
      } else {
        exibirNotificacao(
          `❌ Erro ao excluir: ${resultado.message || "Tente novamente"}`,
          "erro"
        );
      }
    } catch (error) {
      console.error("❌ Erro ao excluir entrada:", error);
      exibirNotificacao(
        "🔌 Erro de conexão ao excluir. Tente novamente.",
        "erro"
      );
    }
  };

  // ✅ FUNÇÃO GLOBAL PARA CONFIRMAR E EXCLUIR
  window.confirmarExclusao = function (
    id,
    nome = "esta entrada",
    tipo = "entrada"
  ) {
    // ✅ Usar SweetAlert se disponível, senão usar confirm nativo
    if (typeof Swal !== "undefined") {
      Swal.fire({
        title: "Confirmar Exclusão",
        text: `Tem certeza que deseja excluir ${nome}?`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Sim, excluir!",
        cancelButtonText: "Cancelar",
      }).then((result) => {
        if (result.isConfirmed) {
          excluirEntrada(id, tipo);
        }
      });
    } else {
      // ✅ Fallback para confirm nativo
      if (confirm(`Tem certeza que deseja excluir ${nome}?`)) {
        excluirEntrada(id, tipo);
      }
    }
  };

  // ✅ FUNÇÃO GLOBAL PARA ABRIR FORMULÁRIO MENTOR (pode ser chamada de qualquer lugar)
  window.abrirFormularioMentor = function (mentorId, mentorNome, mentorFoto) {
    if (!formularioMentor) return;

    // ✅ PREENCHER DADOS DO MENTOR
    const mentorIdHidden = formularioMentor.querySelector(".mentor-id-hidden");
    const mentorNomePreview = formularioMentor.querySelector(
      ".mentor-nome-preview"
    );
    const mentorFotoPreview = formularioMentor.querySelector(
      ".mentor-foto-preview"
    );

    if (mentorIdHidden) mentorIdHidden.value = mentorId;
    if (mentorNomePreview) mentorNomePreview.textContent = mentorNome;
    if (mentorFotoPreview) mentorFotoPreview.src = mentorFoto;

    // ✅ RESETAR FORMULÁRIO
    if (formMentor) formMentor.reset();

    // ✅ LIMPAR SELEÇÕES DE RADIO
    const opcoes = formularioMentor.querySelectorAll('input[name="opcao"]');
    opcoes.forEach((opcao) => (opcao.checked = false));

    // ✅ EXIBIR FORMULÁRIO
    formularioMentor.style.display = "block";

    // ✅ FOCAR NO PRIMEIRO CAMPO
    const primeiroRadio = formularioMentor.querySelector('input[name="opcao"]');
    if (primeiroRadio) primeiroRadio.focus();
  };

  // ✅ FUNÇÃO GLOBAL PARA ATUALIZAR ÁREA DIREITA (pode ser chamada externamente)
  window.atualizarAreaDireitaManual = function () {
    atualizarAreaDireita();
  };

  // ✅ CONFIGURAR DETECÇÃO DE EXCLUSÕES NA INICIALIZAÇÃO
  configurarDeteccaoExclusoes();

  // ===== CÓDIGO DO MODAL DE BANCA (FUNCIONALIDADE EXISTENTE) =====

  // ✅ FUNÇÃO PARA ATUALIZAR A META DIÁRIA
  function atualizarMetaDiaria(metaFormatada) {
    const metaElement = document.getElementById("meta-dia");
    if (metaElement && metaFormatada) {
      metaElement.classList.add("updating");
      setTimeout(() => {
        metaElement.textContent = metaFormatada;
      }, 100);
      setTimeout(() => {
        metaElement.classList.remove("updating");
      }, 600);
      console.log("✅ Meta diária atualizada para:", metaFormatada);
    }
  }

  // ✅ FUNÇÃO PARA ATUALIZAR UNIDADE DE ENTRADA NO FORMULÁRIO
  function atualizarUnidadeEntradaFormulario(unidadeFormatada) {
    if (unidadeFormatada) {
      setTimeout(() => {
        const campoValor = document.getElementById("valor");
        if (campoValor) {
          campoValor.placeholder = unidadeFormatada;
          if (!campoValor.value || campoValor.value === "R$ 0,00") {
            campoValor.value = unidadeFormatada;
          }
        }

        const unidadeElement = document.getElementById("unidade-entrada");
        if (unidadeElement) {
          unidadeElement.textContent = unidadeFormatada;
          unidadeElement.setAttribute(
            "data-unidade",
            unidadeFormatada.replace("R$ ", "")
          );
        }

        console.log("✅ Unidade de entrada atualizada para:", unidadeFormatada);
      }, 100);
    }
  }

  // ✅ EVENTOS DO MODAL DE BANCA (mantendo funcionalidade existente)
  if (botaoGerencia && modal) {
    botaoGerencia.addEventListener("click", (e) => {
      e.preventDefault();
      sessionStorage.setItem("abrirModalGerencia", "true");
      location.reload();
    });

    if (sessionStorage.getItem("abrirModalGerencia") === "true") {
      sessionStorage.removeItem("abrirModalGerencia");
      setTimeout(() => {
        modal.style.display = "flex";
        inicializarModalDeposito();
        // ✅ ATUALIZAR RÓTULOS QUANDO MODAL ABRIR
        atualizarRotulosComDias();
      }, 100);
    }

    if (botaoFechar) {
      botaoFechar.addEventListener("click", () => {
        modal.style.display = "none";
      });
    }
  }

  function selecionarAoClicar(input) {
    if (!input) return;
    input.addEventListener("focus", () => input.select());
    input.addEventListener("mouseup", (e) => e.preventDefault());
  }

  function marcarCamposObrigatorios(campos) {
    campos.forEach((campo) => {
      if (campo && campo.style) {
        campo.style.border = "2px solid red";
        campo.style.boxShadow = "0 0 5px rgba(255, 0, 0, 0.3)";
      }
    });
  }

  function limparMarcacaoCampos(campos) {
    campos.forEach((campo) => {
      if (campo && campo.style) {
        campo.style.border = "";
        campo.style.boxShadow = "";
      }
    });
  }

  function gerarMensagemOperacao(tipoOperacao, valor = null) {
    const valorFormatado = valor
      ? valor.toLocaleString("pt-BR", {
          style: "currency",
          currency: "BRL",
        })
      : "";

    switch (tipoOperacao) {
      case "deposito":
      case "add":
        return `💰 Depósito de ${valorFormatado} realizado com sucesso!`;
      case "saque":
      case "sacar":
        return `💸 Saque de ${valorFormatado} realizado com sucesso!`;
      case "alterar":
        return `⚙️ Configurações alteradas com sucesso!`;
      case "resetar":
        return `🔄 Banca resetada com sucesso!`;
      default:
        return `✅ Operação realizada com sucesso!`;
    }
  }

  // Exibe todas as mensagens toast em um único local no topo direito, cor amarela, sem background
  function exibirToast(mensagem, tipo = "aviso") {
    let toast = document.getElementById("toast-msg");
    if (!toast) {
      toast = document.createElement("div");
      toast.id = "toast-msg";
      toast.className = "toast";
      document.body.appendChild(toast);
    }
    toast.textContent = mensagem;
    toast.className = `toast ativo ${tipo}`;
    setTimeout(() => {
      toast.className = "toast";
      toast.textContent = "";
    }, 3500);
    if (tipo === "sucesso") {
      const campoValor = document.getElementById("valorBanca");
      if (campoValor) campoValor.value = "";
      const dropdownToggle = document.querySelector(".dropdown-toggle");
      if (dropdownToggle) {
        dropdownToggle.innerHTML = `<i class=\"fa-solid fa-hand-pointer\"></i> Selecione Uma Opção <i class=\"fa-solid fa-chevron-down\"></i>`;
      }
      const campoAcao = document.getElementById("acaoBanca");
      if (campoAcao) campoAcao.value = "";
    }
  }

  function adicionarEventosLimpezaCampos() {
    const campos = [valorBancaInput, diaria, unidade, oddsMeta];

    campos.forEach((campo) => {
      if (campo) {
        campo.addEventListener("focus", () => {
          campo.style.border = "";
          campo.style.boxShadow = "";
        });

        campo.addEventListener("input", () => {
          campo.style.border = "";
          campo.style.boxShadow = "";
        });
      }
    });
  }
  // ===== FUNÇÃO PARA ATUALIZAR RÓTULOS COM DIAS RESTANTES =====
  // ✅ FUNÇÃO PARA ATUALIZAR RÓTULOS COM DIAS RESTANTES - VERSÃO CORRIGIDA
  // ===== FUNÇÃO PARA ATUALIZAR RÓTULOS COM DIAS RESTANTES =====
  function atualizarRotulosComDias() {
    // Buscar dados do backend que já calcula com primeiro valor mentor
    fetch("ajax_deposito.php?_=" + Date.now())
      .then(function (response) {
        return response.json();
      })
      .then(function (data) {
        if (!data.success) {
          console.warn("Erro ao buscar dados para rótulos");
          return;
        }

        const labelMetaMes = document.getElementById("labelMetaMes");
        const labelMetaAno = document.getElementById("labelMetaAno");

        if (labelMetaMes && data.dias_restantes_mes) {
          const diasMes = parseInt(data.dias_restantes_mes);
          labelMetaMes.textContent = `${diasMes} Dias P/ Meta do Mês:`;
        }

        if (labelMetaAno && data.dias_restantes_ano) {
          const diasAno = parseInt(data.dias_restantes_ano);
          labelMetaAno.textContent = `${diasAno} Dias P/ Meta do Ano:`;
        }

        // ✅ LOG para verificação
        if (data.data_primeiro_valor_mentor) {
          console.log(
            "✅ Cálculo baseado no primeiro valor mentor:",
            data.data_primeiro_valor_mentor
          );
          console.log("   Explicação mensal:", data.explicacao_mensal);
          console.log("   Explicação anual:", data.explicacao_anual);
        }
      })
      .catch(function (error) {
        console.error("Erro ao atualizar rótulos:", error);
      });
  }

  // ===== FUNÇÕES AUXILIARES FALLBACK =====
  function calcularDiasRestantesMesFallback() {
    const hoje = new Date();
    const ultimoDiaMes = new Date(hoje.getFullYear(), hoje.getMonth() + 1, 0);
    return ultimoDiaMes.getDate() - hoje.getDate() + 1;
  }

  function calcularDiasRestantesAnoFallback() {
    const hoje = new Date();
    const fimAno = new Date(hoje.getFullYear(), 11, 31);
    const diffTime = fimAno - hoje;
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
  }

  // ===== MANTÉM COMPATIBILIDADE COM CÓDIGO EXISTENTE =====
  function calcularDiasRestantesMes() {
    return calcularDiasRestantesMesFallback();
  }

  function calcularDiasRestantesAno() {
    return calcularDiasRestantesAnoFallback();
  }

  // ========================================================================================================================
  //          🚀 FUNÇÃO PRINCIPAL - ATUALIZAR CÁLCULOS EM TEMPO REAL
  // ========================================================================================================================

  // ========================================================================================================================
  //          🔧 FUNÇÕES AUXILIARES PARA ARREDONDAMENTO CORRETO
  // ========================================================================================================================

  function arredondarParaDuasCasas(valor) {
    // Use toFixed(2) para garantir arredondamento correto (evita imprecisões de ponto-flutuante)
    // Converter para Number novamente para manter o tipo numérico
    if (isNaN(valor) || valor === null) return 0;
    return Number(Number(valor).toFixed(2));
  }

  function formatarMoeda(valor) {
    // Arredonda corretamente antes de formatar
    const valorArredondado = arredondarParaDuasCasas(valor);
    return valorArredondado.toLocaleString("pt-BR", {
      style: "currency",
      currency: "BRL",
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });
  }

  // ========================================================================================================================
  //          🚀 FUNÇÃO PRINCIPAL - ATUALIZAR CÁLCULOS EM TEMPO REAL - VERSÃO FINAL CORRIGIDA
  // ========================================================================================================================

  function atualizarUnidadeEntradaTempoReal() {
    console.log("🔄 [INIT] Iniciando atualização em tempo real...");

    // ✅ PEGAR ELEMENTOS
    var diaria = document.getElementById("porcentagem");
    var unidade = document.getElementById("unidadeMeta");
    var valorBancaInput = document.getElementById("valorBanca");
    var acaoSelect = document.getElementById("acaoBanca");
    var metaFixaRadio = document.getElementById("metaFixa");
    var metaTurboRadio = document.getElementById("metaTurbo");
    var lucroTotalLabel = document.getElementById("valorLucroLabel");
    var oddsMeta = document.getElementById("oddsMeta");

    // Elementos de resultado
    var resultadoUnidadeEntrada = document.getElementById(
      "resultadoUnidadeEntrada"
    );
    var resultadoMetaDia = document.getElementById("resultadoMetaDia");
    var resultadoMetaMes = document.getElementById("resultadoMetaMes");
    var resultadoMetaAno = document.getElementById("resultadoMetaAno");
    var resultadoEntradas = document.getElementById("resultadoEntradas");

    // ✅ VALIDAÇÃO CRÍTICA
    if (!diaria || !unidade || !resultadoUnidadeEntrada) {
      console.error("❌ Elementos críticos não encontrados!");
      return;
    }

    console.log("✅ Elementos encontrados, buscando dados...");

    // ✅ BUSCAR DADOS DO SERVIDOR
    fetch("ajax_deposito.php?_=" + Date.now())
      .then(function (response) {
        if (!response.ok) {
          throw new Error("HTTP " + response.status);
        }
        return response.json();
      })
      .then(function (data) {
        console.log("📦 Dados recebidos:", data);

        if (!data.success) {
          console.warn("⚠️ Resposta sem success");
          return;
        }

        // ✅ EXTRAIR DADOS
        var bancaAtual = parseFloat(data.banca) || 0;
        var bancaInicioDia = parseFloat(data.banca_inicio_dia) || bancaAtual;
        var lucroAteOntem = parseFloat(data.lucro_ate_ontem) || 0;

        console.log("💰 Bancas:", {
          atual: bancaAtual,
          inicioDia: bancaInicioDia,
          lucroOntem: lucroAteOntem,
        });

        // ✅ LUCRO TOTAL
        var lucroTotal = 0;
        if (lucroTotalLabel && lucroTotalLabel.textContent) {
          var lucroTexto = lucroTotalLabel.textContent
            .replace(/[^\d,-]/g, "")
            .replace(",", ".");
          lucroTotal = parseFloat(lucroTexto) || 0;
        }
        if (lucroTotal === 0 && data.lucro) {
          lucroTotal = parseFloat(data.lucro) || 0;
        }

        var lucroHoje = lucroTotal - lucroAteOntem;

        console.log("💵 Lucros:", {
          total: lucroTotal,
          ateOntem: lucroAteOntem,
          hoje: lucroHoje,
        });

        // ✅ TIPO DE META
        var tipoMetaSelecionado = "turbo";
        var isMetaFixa = false;

        if (metaFixaRadio && metaFixaRadio.checked) {
          tipoMetaSelecionado = "fixa";
          isMetaFixa = true;
        } else if (metaTurboRadio && metaTurboRadio.checked) {
          tipoMetaSelecionado = "turbo";
          isMetaFixa = false;
        }

        console.log("🎯 Tipo de meta:", tipoMetaSelecionado);

        // ✅ VALOR DIGITADO
        var valorInputRaw = valorBancaInput
          ? valorBancaInput.value.replace(/[^\d]/g, "")
          : "0";
        var valorDigitado = parseFloat(valorInputRaw) / 100 || 0;
        var tipoAcao = acaoSelect ? acaoSelect.value : "";

        // ✅ PORCENTAGEM
        var percentualRaw = diaria.value
          .replace("%", "")
          .trim()
          .replace(",", ".");
        var percentFinal = parseFloat(percentualRaw) || 1;

        // ✅ UNIDADE
        var unidadeInt = parseInt(unidade.value) || 1;

        console.log("🔢 Parâmetros:", {
          porcentagem: percentFinal,
          unidade: unidadeInt,
          valorDigitado: valorDigitado,
          acao: tipoAcao,
          isMetaFixa: isMetaFixa,
        });

        // ✅ DIAS RESTANTES DO SERVIDOR
        var diasRestantesMes = parseInt(data.dias_restantes_mes) || 0;
        var diasRestantesAno = parseInt(data.dias_restantes_ano) || 0;

        // ✅ CÁLCULOS BASEADOS NO TIPO DE META
        var bancaParaCalculo;
        var unidadeEntrada;
        var metaDiaria;
        var metaMensal;
        var metaAnual;
        var metaDiariaBase;

        if (isMetaFixa) {
          // ===== META FIXA =====
          console.log("🔒 Calculando META FIXA...");

          var bancaPura = bancaInicioDia - lucroAteOntem;
          bancaParaCalculo = bancaPura;

          if (valorDigitado > 0) {
            if (tipoAcao === "add") {
              bancaParaCalculo = bancaPura + valorDigitado;
            } else if (tipoAcao === "sacar") {
              bancaParaCalculo = Math.max(0, bancaPura - valorDigitado);
            }
          }

          unidadeEntrada = arredondarParaDuasCasas(
            bancaParaCalculo * (percentFinal / 100)
          );
          metaDiariaBase = arredondarParaDuasCasas(unidadeEntrada * unidadeInt);

          if (lucroHoje < 0) {
            metaDiaria = arredondarParaDuasCasas(
              metaDiariaBase + Math.abs(lucroHoje)
            );
          } else if (lucroHoje > 0) {
            metaDiaria = arredondarParaDuasCasas(
              Math.max(0, metaDiariaBase - lucroHoje)
            );
          } else {
            metaDiaria = metaDiariaBase;
          }

          var metaMensalBruta = arredondarParaDuasCasas(
            metaDiariaBase * diasRestantesMes
          );
          if (lucroTotal < 0) {
            metaMensal = arredondarParaDuasCasas(
              metaMensalBruta + Math.abs(lucroTotal)
            );
          } else if (lucroTotal > 0) {
            metaMensal = arredondarParaDuasCasas(
              Math.max(0, metaMensalBruta - lucroTotal)
            );
          } else {
            metaMensal = metaMensalBruta;
          }

          var metaAnualBruta = arredondarParaDuasCasas(
            metaDiariaBase * diasRestantesAno
          );
          if (lucroTotal < 0) {
            metaAnual = arredondarParaDuasCasas(
              metaAnualBruta + Math.abs(lucroTotal)
            );
          } else if (lucroTotal > 0) {
            metaAnual = arredondarParaDuasCasas(
              Math.max(0, metaAnualBruta - lucroTotal)
            );
          } else {
            metaAnual = metaAnualBruta;
          }

          console.log(`✅ META FIXA calculada`);
        } else {
          // ===== META TURBO =====
          console.log("🚀 Calculando META TURBO...");

          bancaParaCalculo = bancaInicioDia;

          unidadeEntrada = arredondarParaDuasCasas(
            bancaParaCalculo * (percentFinal / 100)
          );
          metaDiariaBase = arredondarParaDuasCasas(unidadeEntrada * unidadeInt);

          if (lucroHoje < 0) {
            metaDiaria = arredondarParaDuasCasas(
              metaDiariaBase + Math.abs(lucroHoje)
            );
          } else if (lucroHoje > 0) {
            metaDiaria = arredondarParaDuasCasas(
              Math.max(0, metaDiariaBase - lucroHoje)
            );
          } else {
            metaDiaria = metaDiariaBase;
          }

          var metaMensalBruta = arredondarParaDuasCasas(
            metaDiariaBase * diasRestantesMes
          );
          var metaAnualBruta = arredondarParaDuasCasas(
            metaDiariaBase * diasRestantesAno
          );

          if (lucroTotal < 0) {
            metaMensal = arredondarParaDuasCasas(
              metaMensalBruta + Math.abs(lucroTotal)
            );
            metaAnual = arredondarParaDuasCasas(
              metaAnualBruta + Math.abs(lucroTotal)
            );
          } else if (lucroTotal > 0) {
            metaMensal = arredondarParaDuasCasas(
              Math.max(0, metaMensalBruta - lucroTotal)
            );
            metaAnual = arredondarParaDuasCasas(
              Math.max(0, metaAnualBruta - lucroTotal)
            );
          } else {
            metaMensal = metaMensalBruta;
            metaAnual = metaAnualBruta;
          }

          console.log(`✅ META TURBO calculada`);
        }

        // ✅ ATUALIZAR UNIDADE DE ENTRADA
        if (resultadoUnidadeEntrada) {
          resultadoUnidadeEntrada.textContent = formatarMoeda(unidadeEntrada);
          console.log(
            "✅ UND atualizada:",
            resultadoUnidadeEntrada.textContent
          );
        }

        // ========================================================================================================================
        //          🎯 DISPLAY DA META DO DIA - VERSÃO FINAL CORRIGIDA
        // ========================================================================================================================
        if (resultadoMetaDia) {
          console.log(`🎯 Verificando Meta do Dia:
          Meta Base: R$ ${metaDiariaBase.toFixed(2)}
          Lucro Hoje: R$ ${lucroHoje.toFixed(2)}
          Meta Restante: R$ ${metaDiaria.toFixed(2)}`);

          // ⭐ CASO 1: Meta batida exatamente (valor restante R$ 0,00)
          if (metaDiaria <= 0.01) {
            resultadoMetaDia.innerHTML =
              'Meta Batida! <i class="fa-solid fa-trophy" style="color: #FFD700;"></i>';
            console.log("🏆 META BATIDA!");
          }
          // ⭐ CASO 2: Meta superada (lucro ultrapassou a meta - QUALQUER VALOR)
          else if (lucroHoje > metaDiariaBase && metaDiariaBase > 0) {
            var valorExcedente = arredondarParaDuasCasas(
              lucroHoje - metaDiariaBase
            );
            var excedenteFormatado = formatarMoeda(valorExcedente);
            var metaRiscada = formatarMoeda(metaDiariaBase);

            resultadoMetaDia.innerHTML =
              '<span style="text-decoration: line-through;">' +
              metaRiscada +
              "</span> " +
              "Superada! +" +
              excedenteFormatado +
              ' <i class="fa-solid fa-rocket" style="color: #FF6B6B;"></i>';
            console.log(`🚀 META SUPERADA! +${excedenteFormatado}`);
          }
          // ⭐ CASO 3: Meta ainda não batida (mostrar valor restante)
          else {
            resultadoMetaDia.textContent = formatarMoeda(metaDiaria);
            console.log(`📊 Faltam: ${formatarMoeda(metaDiaria)}`);
          }
        }

        // ========================================================================================================================
        //          📅 DISPLAY DA META DO MÊS - VERSÃO FINAL CORRIGIDA
        // ========================================================================================================================
        if (resultadoMetaMes) {
          var metaMensalBase = arredondarParaDuasCasas(
            metaDiariaBase * diasRestantesMes
          );

          console.log(`📅 Verificando Meta do Mês:
          Meta Base: R$ ${metaMensalBase.toFixed(2)}
          Lucro Total: R$ ${lucroTotal.toFixed(2)}
          Meta Restante: R$ ${metaMensal.toFixed(2)}`);

          // ⭐ CASO 1: Meta batida exatamente
          if (metaMensal <= 0.01) {
            resultadoMetaMes.innerHTML =
              'Meta Batida! <i class="fa-solid fa-trophy" style="color: #FFD700;"></i>';
            console.log("🏆 META MENSAL BATIDA!");
          }
          // ⭐ CASO 2: Meta superada (lucro ultrapassou a meta - QUALQUER VALOR)
          else if (lucroTotal > metaMensalBase && metaMensalBase > 0) {
            var valorExcedenteMes = arredondarParaDuasCasas(
              lucroTotal - metaMensalBase
            );
            var excedenteFormatadoMes = formatarMoeda(valorExcedenteMes);
            var metaRiscadaMes = formatarMoeda(metaMensalBase);

            resultadoMetaMes.innerHTML =
              '<span style="text-decoration: line-through;">' +
              metaRiscadaMes +
              "</span> " +
              "Superada! +" +
              excedenteFormatadoMes +
              ' <i class="fa-solid fa-rocket" style="color: #FF6B6B;"></i>';
            console.log(`🚀 META MENSAL SUPERADA! +${excedenteFormatadoMes}`);
          }
          // ⭐ CASO 3: Meta ainda não batida
          else {
            resultadoMetaMes.textContent = formatarMoeda(metaMensal);
            console.log(`📊 Faltam (Mensal): ${formatarMoeda(metaMensal)}`);
          }
        }

        // ========================================================================================================================
        //          📆 DISPLAY DA META DO ANO - VERSÃO FINAL CORRIGIDA
        // ========================================================================================================================
        if (resultadoMetaAno) {
          var metaAnualBase = arredondarParaDuasCasas(
            metaDiariaBase * diasRestantesAno
          );

          console.log(`📆 Verificando Meta do Ano:
          Meta Base: R$ ${metaAnualBase.toFixed(2)}
          Lucro Total: R$ ${lucroTotal.toFixed(2)}
          Meta Restante: R$ ${metaAnual.toFixed(2)}`);

          // ⭐ CASO 1: Meta batida exatamente
          if (metaAnual <= 0.01) {
            resultadoMetaAno.innerHTML =
              'Meta Batida! <i class="fa-solid fa-trophy" style="color: #FFD700;"></i>';
            console.log("🏆 META ANUAL BATIDA!");
          }
          // ⭐ CASO 2: Meta superada (lucro ultrapassou a meta - QUALQUER VALOR)
          else if (lucroTotal > metaAnualBase && metaAnualBase > 0) {
            var valorExcedenteAno = arredondarParaDuasCasas(
              lucroTotal - metaAnualBase
            );
            var excedenteFormatadoAno = formatarMoeda(valorExcedenteAno);
            var metaRiscadaAno = formatarMoeda(metaAnualBase);

            resultadoMetaAno.innerHTML =
              '<span style="text-decoration: line-through;">' +
              metaRiscadaAno +
              "</span> " +
              "Superada! +" +
              excedenteFormatadoAno +
              ' <i class="fa-solid fa-rocket" style="color: #FF6B6B;"></i>';
            console.log(`🚀 META ANUAL SUPERADA! +${excedenteFormatadoAno}`);
          }
          // ⭐ CASO 3: Meta ainda não batida
          else {
            resultadoMetaAno.textContent = formatarMoeda(metaAnual);
            console.log(`📊 Faltam (Anual): ${formatarMoeda(metaAnual)}`);
          }
        }

        // ========================================================================================================================
        //          🎯 CÁLCULO DE ENTRADAS NECESSÁRIAS - VERSÃO FINAL
        // ========================================================================================================================
        if (oddsMeta && resultadoEntradas) {
          var oddsValor = parseFloat(oddsMeta.value.replace(",", ".")) || 1.5;

          console.log(`🎲 Calculando entradas:
          Odds: ${oddsValor}
          Meta Restante: R$ ${metaDiaria.toFixed(2)}`);

          // ⭐ Meta já batida
          if (metaDiaria <= 0.01) {
            resultadoEntradas.innerHTML =
              'Meta Batida! <i class="fa-solid fa-trophy" style="color: #FFD700;"></i>';
            console.log("🏆 Entradas: Meta já batida!");
          }
          // ⭐ Calcular entradas necessárias
          else if (unidadeEntrada > 0 && metaDiaria > 0) {
            var lucroPorEntrada = arredondarParaDuasCasas(
              unidadeEntrada * (oddsValor - 1)
            );

            if (lucroPorEntrada > 0) {
              var entradasNecessarias = Math.ceil(metaDiaria / lucroPorEntrada);
              resultadoEntradas.textContent =
                entradasNecessarias + " Entradas Positivas";
              console.log(`📊 Entradas necessárias: ${entradasNecessarias}`);
            } else {
              resultadoEntradas.textContent = "Ajuste as odds";
              console.log("⚠️ Odds muito baixas");
            }
          } else {
            resultadoEntradas.textContent = "Configure os valores";
            console.log("⚠️ Valores insuficientes");
          }
        }

        console.log("🎉 ATUALIZAÇÃO COMPLETA!");
      })
      .catch(function (error) {
        console.error("❌ Erro ao buscar dados:", error);
      });
  }

  console.log(
    "✅ Função atualizarUnidadeEntradaTempoReal() VERSÃO FINAL carregada!"
  );

  // ===== EVENTOS DE MUDANÇA DE TIPO DE META - FORÇAR RECÁLCULO =====
  document.addEventListener("DOMContentLoaded", function () {
    setTimeout(function () {
      var metaFixaRadio = document.getElementById("metaFixa");
      var metaTurboRadio = document.getElementById("metaTurbo");

      if (metaFixaRadio) {
        metaFixaRadio.addEventListener("change", function () {
          if (this.checked) {
            console.log("🔒 META FIXA selecionada - recalculando...");
            setTimeout(function () {
              atualizarUnidadeEntradaTempoReal();
            }, 100);
          }
        });
      }

      if (metaTurboRadio) {
        metaTurboRadio.addEventListener("change", function () {
          if (this.checked) {
            console.log("🚀 META TURBO selecionada - recalculando...");
            setTimeout(function () {
              atualizarUnidadeEntradaTempoReal();
            }, 100);
          }
        });
      }

      console.log("✅ Eventos de mudança de tipo de meta configurados!");
    }, 1000);
  });

  console.log(
    "✅ Função atualizarUnidadeEntradaTempoReal() CORRIGIDA carregada!"
  );

  // ===== EVENTOS DE MUDANÇA DE TIPO DE META =====
  document.addEventListener("DOMContentLoaded", function () {
    setTimeout(function () {
      var metaFixaRadio = document.getElementById("metaFixa");
      var metaTurboRadio = document.getElementById("metaTurbo");

      if (metaFixaRadio) {
        metaFixaRadio.addEventListener("change", function () {
          if (this.checked) {
            setTimeout(function () {
              atualizarUnidadeEntradaTempoReal();
            }, 100);
          }
        });
      }

      if (metaTurboRadio) {
        metaTurboRadio.addEventListener("change", function () {
          if (this.checked) {
            setTimeout(function () {
              atualizarUnidadeEntradaTempoReal();
            }, 100);
          }
        });
      }
    }, 1000);
  });

  function inicializarModalDeposito() {
    if (modalInicializado || !modal) return;
    modalInicializado = true;

    console.log("🚀 Inicializando Modal de Depósito...");

    // ✅ SELETORES DOS ELEMENTOS
    valorBancaInput = modal.querySelector("#valorBanca");
    const valorBancaLabel = modal.querySelector("#valorBancaLabel");
    diaria = modal.querySelector("#porcentagem");
    unidade = modal.querySelector("#unidadeMeta");
    resultadoCalculo = modal.querySelector("#resultadoCalculo");
    resultadoUnidade = modal.querySelector("#resultadoUnidade");
    resultadoOdds = modal.querySelector("#resultadoOdds");
    oddsMeta = modal.querySelector("#oddsMeta");
    metaFixaRadio = modal.querySelector("#metaFixa");
    metaTurboRadio = modal.querySelector("#metaTurbo");

    const acaoSelect = modal.querySelector("#acaoBanca");
    const botaoAcao = modal.querySelector("#botaoAcao");
    const lucroTotalLabel = modal.querySelector("#valorLucroLabel");

    // ✅ VERIFICAR ELEMENTOS CRÍTICOS
    if (!valorBancaInput || !valorBancaLabel || !acaoSelect) {
      console.error("❌ Elementos críticos não encontrados!");
      return;
    }

    // ✅ CONFIGURAR SELEÇÃO AO CLICAR
    if (diaria) selecionarAoClicar(diaria);
    if (unidade) selecionarAoClicar(unidade);
    if (oddsMeta) selecionarAoClicar(oddsMeta);

    // ✅ CRIAR ELEMENTOS AUXILIARES
    const legendaBanca = document.createElement("div");
    legendaBanca.id = "legendaBanca";
    legendaBanca.style = "margin-top: 5px; font-size: 0.9em; color: #7f8c8d;";
    if (valorBancaInput) {
      valorBancaInput.parentNode.appendChild(legendaBanca);
    }

    mensagemErro = document.createElement("div");
    mensagemErro.id = "mensagemErro";
    mensagemErro.style = "color: red; margin-top: 10px; font-weight: bold;";
    if (botaoAcao) {
      botaoAcao.parentNode.insertBefore(mensagemErro, botaoAcao.nextSibling);
    }

    // ✅ EVENTOS DOS INPUTS - ATUALIZAÇÃO EM TEMPO REAL
    if (diaria) {
      diaria.addEventListener("focus", () => {
        // Remove o % ao focar
        const valorAtual = diaria.value.replace("%", "");
        diaria.value = valorAtual;
        diaria.select();
      });

      diaria.addEventListener("input", (e) => {
        // Permite números, vírgula e ponto
        let valor = e.target.value.replace(/[^\d,.]/g, "");

        // Substitui ponto por vírgula
        valor = valor.replace(".", ",");

        // Garante apenas uma vírgula
        const partes = valor.split(",");
        if (partes.length > 2) {
          valor = partes[0] + "," + partes.slice(1).join("");
        }

        // ✅ PERMITE ATÉ 2 CASAS DECIMAIS
        if (partes.length === 2 && partes[1].length > 2) {
          valor = partes[0] + "," + partes[1].substring(0, 2);
        }

        e.target.value = valor;
        atualizarUnidadeEntradaTempoReal();
      });

      diaria.addEventListener("blur", () => {
        let valor = diaria.value.replace(",", ".");
        let numero = parseFloat(valor) || 1;

        // Limita entre 0.1 e 100
        numero = Math.max(0.1, Math.min(100, numero));

        // ✅ FORMATA COM ATÉ 2 CASAS DECIMAIS
        const valorFormatado = numero.toFixed(2).replace(/\.?0+$/, "");

        diaria.value = `${valorFormatado}%`;
        atualizarUnidadeEntradaTempoReal();
      });
    }

    if (unidade) {
      unidade.addEventListener("input", () => {
        unidade.value = unidade.value.replace(/\D/g, "");
        atualizarUnidadeEntradaTempoReal();
      });

      unidade.addEventListener("blur", () => {
        const valor = parseInt(unidade.value) || 2;
        unidade.value = valor;
        atualizarUnidadeEntradaTempoReal();
      });

      unidade.addEventListener("focus", () => {
        unidade.select();
      });
    }

    if (oddsMeta) {
      oddsMeta.addEventListener("input", () => {
        oddsMeta.value = oddsMeta.value.replace(/[^0-9.,]/g, "");
        atualizarUnidadeEntradaTempoReal();
      });

      oddsMeta.addEventListener("blur", () => {
        let valor = oddsMeta.value.replace(",", ".");
        let numero = parseFloat(valor);
        oddsMeta.value = isNaN(numero) ? "1.50" : numero.toFixed(2);
        atualizarUnidadeEntradaTempoReal();
      });

      let valorInicialOdds = oddsMeta.value.replace(",", ".");
      let numeroInicialOdds = parseFloat(valorInicialOdds);
      oddsMeta.value = isNaN(numeroInicialOdds)
        ? "1.50"
        : numeroInicialOdds.toFixed(2);
    }

    // ✅ CARREGAMENTO INICIAL
    fetch("ajax_deposito.php")
      .then((response) => response.json())
      .then((data) => {
        if (!data.success) {
          console.warn("⚠️ Resposta sem sucesso:", data);
          return;
        }

        const lucro = parseFloat(data.lucro) || 0;
        if (lucroTotalLabel) {
          lucroTotalLabel.textContent = lucro.toLocaleString("pt-BR", {
            style: "currency",
            currency: "BRL",
          });
          if (window.SistemaLucroDinamico?.atualizarLucro) {
            window.SistemaLucroDinamico.atualizarLucro(lucro);
          }
        }

        valorOriginalBanca = parseFloat(data.banca) || 0;

        if (valorBancaLabel) {
          const bancaFormatada = valorOriginalBanca.toLocaleString("pt-BR", {
            style: "currency",
            currency: "BRL",
          });
          valorBancaLabel.textContent = bancaFormatada;
        }

        if (diaria) {
          const diariaValor = Math.max(parseFloat(data.diaria || "1.00"), 0.1);

          // Remove zeros desnecessários e usa ponto decimal
          const diariaFormatada =
            diariaValor % 1 === 0
              ? diariaValor.toFixed(0)
              : parseFloat(diariaValor.toFixed(2)).toString();

          diaria.value = `${diariaFormatada}%`;
        }
        if (unidade) {
          unidade.value = parseInt(data.unidade || "2");
        }
        if (oddsMeta) {
          const oddsFormatada = parseFloat(data.odds || "1.50");
          oddsMeta.value = isNaN(oddsFormatada)
            ? "1.50"
            : oddsFormatada.toFixed(2);
        }

        if (data.meta === "Meta Turbo" && metaTurboRadio) {
          metaTurboRadio.checked = true;
          destacarMetaSelecionada("turbo");
        } else if (metaFixaRadio) {
          metaFixaRadio.checked = true;
          destacarMetaSelecionada("fixa");
        }

        // ✅ MÚLTIPLAS CHAMADAS PARA GARANTIR ATUALIZAÇÃO
        setTimeout(() => {
          atualizarUnidadeEntradaTempoReal();
        }, 100);

        setTimeout(() => {
          atualizarUnidadeEntradaTempoReal();
        }, 300);

        setTimeout(() => {
          atualizarUnidadeEntradaTempoReal();
        }, 600);

        // ✅ ATUALIZAR RÓTULOS COM DIAS RESTANTES
        atualizarRotulosComDias();

        setTimeout(() => {
          configurarInputValorBanca();
        }, 200);

        setTimeout(() => atualizarAreaDireita(), 500);
      })
      .catch((error) => {
        console.error("❌ Erro ao carregar dados:", error);
      });

    // ✅ EVENTOS DOS DROPDOWNS
    const dropdownItems = modal.querySelectorAll(".dropdown-menu li");
    const dropdownToggle = modal.querySelector(".dropdown-toggle");

    dropdownItems.forEach((item) => {
      item.addEventListener("click", function () {
        const tipo = this.getAttribute("data-value");
        const texto = this.innerHTML;

        if (dropdownToggle) {
          dropdownToggle.innerHTML =
            texto + ' <i class="fa-solid fa-chevron-down"></i>';
        }

        if (acaoSelect) acaoSelect.value = tipo;

        if (valorBancaInput) valorBancaInput.value = "";
        if (mensagemErro) mensagemErro.textContent = "";

        if (valorBancaLabel && typeof valorOriginalBanca !== "undefined") {
          valorBancaLabel.textContent = valorOriginalBanca.toLocaleString(
            "pt-BR",
            {
              style: "currency",
              currency: "BRL",
            }
          );
          atualizarUnidadeEntradaTempoReal();
        }

        if (valorBancaInput && botaoAcao) {
          switch (tipo) {
            case "add":
              valorBancaInput.placeholder = "Valor do Deposito R$ 0,00";
              valorBancaInput.disabled = false;
              valorBancaInput.classList.remove("desativado");
              botaoAcao.value = "Depositar na Banca";
              break;

            case "sacar":
              valorBancaInput.placeholder = "Valor do Saque R$ 0,00";
              valorBancaInput.disabled = false;
              valorBancaInput.classList.remove("desativado");
              botaoAcao.value = "Sacar da Banca";
              break;

            case "alterar":
              valorBancaInput.placeholder = "Essa ação não requer valor";
              valorBancaInput.disabled = true;
              valorBancaInput.classList.add("desativado");
              botaoAcao.value = "Salvar Alteração";
              break;

            case "resetar":
              valorBancaInput.placeholder = "Essa ação irá zerar sua banca";
              valorBancaInput.disabled = true;
              valorBancaInput.classList.add("desativado");
              botaoAcao.value = "Resetar Banca";
              break;

            default:
              valorBancaInput.placeholder = "R$ 0,00";
              valorBancaInput.disabled = false;
              valorBancaInput.classList.remove("desativado");
              botaoAcao.value = "Cadastrar Dados";
              break;
          }

          if (!valorBancaInput.disabled) {
            setTimeout(() => valorBancaInput.focus(), 100);
          }
        }
      });
    });

    // ✅ FUNÇÃO PARA CONFIGURAR INPUT - VERSÃO CORRIGIDA
    function configurarInputValorBanca() {
      if (!valorBancaInput) return;

      const novoInput = valorBancaInput.cloneNode(true);
      valorBancaInput.parentNode.replaceChild(novoInput, valorBancaInput);
      valorBancaInput = novoInput;

      novoInput.addEventListener("input", function () {
        let valor = this.value.replace(/[^\d]/g, "");

        const mensagemErro = document.getElementById("mensagemErro");
        const legendaBanca = document.getElementById("legendaBanca");
        const valorBancaLabel = document.getElementById("valorBancaLabel");
        const acaoSelect = document.getElementById("acaoBanca");

        if (!valor || valor === "0") {
          this.value = "";
          if (mensagemErro) mensagemErro.textContent = "";
          if (legendaBanca) legendaBanca.style.display = "none";

          if (valorBancaLabel && typeof valorOriginalBanca !== "undefined") {
            valorBancaLabel.textContent = valorOriginalBanca.toLocaleString(
              "pt-BR",
              {
                style: "currency",
                currency: "BRL",
              }
            );
            atualizarUnidadeEntradaTempoReal();
          }
          return;
        }

        // ✅ FORMATAR VALOR ENQUANTO DIGITA
        const valorDigitado = parseFloat(valor) / 100;
        this.value = valorDigitado.toLocaleString("pt-BR", {
          style: "currency",
          currency: "BRL",
        });

        const tipo = acaoSelect ? acaoSelect.value : "";
        let valorAtualizado = valorOriginalBanca;
        let temErro = false;

        switch (tipo) {
          case "add":
            valorAtualizado = valorOriginalBanca + valorDigitado;
            if (mensagemErro) mensagemErro.textContent = "";
            break;

          case "sacar":
            if (valorDigitado > valorOriginalBanca) {
              if (mensagemErro)
                mensagemErro.textContent = "Saldo Insuficiente.";
              temErro = true;
              valorAtualizado = valorOriginalBanca;
            } else {
              valorAtualizado = valorOriginalBanca - valorDigitado;
              if (mensagemErro) mensagemErro.textContent = "";
            }
            break;

          case "alterar":
          case "resetar":
            valorAtualizado = valorOriginalBanca;
            break;

          default:
            if (valorOriginalBanca === 0) {
              valorAtualizado = valorDigitado;
            }
            break;
        }

        valorAtualizado = Math.max(0, valorAtualizado);

        if (valorBancaLabel) {
          const valorFormatado = valorAtualizado.toLocaleString("pt-BR", {
            style: "currency",
            currency: "BRL",
          });
          valorBancaLabel.textContent = valorFormatado;
        }

        if (legendaBanca) {
          legendaBanca.style.display = temErro ? "none" : "block";
        }

        // ✅ ATUALIZAR CÁLCULOS EM TEMPO REAL
        atualizarUnidadeEntradaTempoReal();
      });

      novoInput.addEventListener("focus", function () {
        this.select();
      });

      novoInput.addEventListener("blur", function () {
        if (!this.value || this.value === "R$ 0,00") {
          this.value = "";
        }
      });
    }

    // ✅ EVENTOS DO BOTÃO AÇÃO
    if (botaoAcao) {
      botaoAcao.addEventListener("click", (e) => {
        e.preventDefault();

        if (mensagemErro) mensagemErro.textContent = "";

        const tipoSelecionado = acaoSelect ? acaoSelect.value : "";

        if (!tipoSelecionado) {
          exibirToast(
            "⚠️ Selecione uma opção: Depositar, Sacar, Alterar ou Resetar.",
            "aviso"
          );
          return;
        }

        const tipoMeta = obterTipoMetaSelecionado();
        if (!tipoMeta) {
          exibirToast("⚠️ Selecione o tipo de meta (Fixa ou Turbo)", "aviso");
          const campoTipoMeta = modal.querySelector(".campo-tipo-meta");
          if (campoTipoMeta) {
            campoTipoMeta.style.border = "2px solid red";
            campoTipoMeta.style.borderRadius = "5px";
            setTimeout(() => {
              campoTipoMeta.style.border = "";
            }, 3000);
          }
          return;
        }

        const camposObrigatorios = [
          ...(tipoSelecionado !== "alterar"
            ? [{ campo: valorBancaInput, nome: "Valor da Banca" }]
            : []),
          { campo: diaria, nome: "Porcentagem Diária" },
          { campo: unidade, nome: "Quantidade de Unidade" },
          { campo: oddsMeta, nome: "Odds" },
        ];

        let camposVazios = [];
        let camposComErro = [];

        const todosCampos = camposObrigatorios.map((item) => item.campo);
        limparMarcacaoCampos(todosCampos);

        camposObrigatorios.forEach(({ campo, nome }) => {
          const isDisabled = campo ? campo.disabled : false;
          if (campo && !campo.value.trim() && !isDisabled) {
            camposVazios.push(nome);
            camposComErro.push(campo);
          }
        });

        if (camposVazios.length > 0) {
          marcarCamposObrigatorios(camposComErro);
          exibirToast(
            `📝 Preencha os seguintes campos: ${camposVazios.join(", ")}`,
            "aviso"
          );
          return;
        }

        if (tipoSelecionado === "resetar") {
          const confirmarReset = document.getElementById("confirmarReset");
          if (confirmarReset) confirmarReset.style.display = "block";
          return;
        }

        const valorRaw = valorBancaInput
          ? valorBancaInput.value.replace(/[^\d]/g, "")
          : "0";
        const valorNumerico = parseFloat(valorRaw) / 100;

        const diariaRaw = diaria
          ? diaria.value.replace("%", "").replace(",", ".").trim()
          : "2";
        const unidadeRaw = unidade ? unidade.value.replace(/[^\d]/g, "") : "2";

        const diariaFloat = parseFloat(diariaRaw) || 2.0;
        const unidadeInt = parseInt(unidadeRaw);

        if (
          tipoSelecionado !== "alterar" &&
          (isNaN(valorNumerico) || valorNumerico <= 0)
        ) {
          marcarCamposObrigatorios([valorBancaInput]);
          exibirToast("💲 Digite um valor válido.", "erro");
          return;
        }

        if (tipoSelecionado === "sacar" && valorNumerico > valorOriginalBanca) {
          marcarCamposObrigatorios([valorBancaInput]);
          exibirToast("🚫 Saldo Insuficiente para saque.", "erro");
          return;
        }

        let acaoFinal =
          tipoSelecionado === "sacar"
            ? "saque"
            : tipoSelecionado === "alterar"
            ? "alterar"
            : "deposito";

        const oddsValor = oddsMeta
          ? parseFloat(oddsMeta.value.replace(",", "."))
          : 1.5;

        const dadosEnvio = {
          acao: acaoFinal,
          valor: valorNumerico.toFixed(2),
          diaria: diariaFloat,
          unidade: unidadeInt,
          odds: oddsValor,
          tipoMeta: tipoMeta,
        };

        fetch("ajax_deposito.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(dadosEnvio),
        })
          .then((res) => res.json())
          .then((resposta) => {
            if (resposta.success) {
              const mensagem = gerarMensagemOperacao(
                tipoSelecionado,
                valorNumerico
              );
              exibirToast(mensagem, "sucesso");

              if (resposta.meta_diaria_formatada) {
                atualizarMetaDiaria(resposta.meta_diaria_formatada);
              }

              if (resposta.unidade_entrada_formatada) {
                atualizarUnidadeEntradaFormulario(
                  resposta.unidade_entrada_formatada
                );
              }

              atualizarDadosModal();
              setTimeout(() => {
                atualizarAreaDireita();
                atualizarUnidadeEntradaTempoReal();
              }, 300);
            } else {
              exibirToast(
                `❌ Erro ao realizar ${tipoSelecionado}: ${
                  resposta.message || "Tente novamente."
                }`,
                "erro"
              );
            }
          })
          .catch((error) => {
            console.error("Erro na requisição:", error);
            exibirToast(
              "🔌 Erro de conexão. Verifique sua internet e tente novamente.",
              "erro"
            );
          });
      });
    }

    // ✅ EVENTOS DE RESET
    const btnConfirmarReset = document.getElementById("btnConfirmarReset");
    if (btnConfirmarReset) {
      btnConfirmarReset.addEventListener("click", () => {
        fetch("ajax_deposito.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ acao: "resetar" }),
        })
          .then((res) => res.json())
          .then((resposta) => {
            if (resposta.success) {
              exibirToast(
                "🔄 Banca resetada com sucesso! Todos os dados foram zerados.",
                "sucesso"
              );

              if (resposta.meta_diaria_formatada) {
                atualizarMetaDiaria(resposta.meta_diaria_formatada);
              } else {
                atualizarMetaDiaria("R$ 0,00");
              }

              if (resposta.unidade_entrada_formatada) {
                atualizarUnidadeEntradaFormulario(
                  resposta.unidade_entrada_formatada
                );
              } else {
                atualizarUnidadeEntradaFormulario("R$ 0,00");
              }

              atualizarDadosModal();
              setTimeout(() => {
                atualizarAreaDireita();
                atualizarUnidadeEntradaTempoReal();
              }, 300);

              const confirmarReset = document.getElementById("confirmarReset");
              if (confirmarReset) confirmarReset.style.display = "none";
            } else {
              exibirToast("❌ Erro ao resetar banca. Tente novamente.", "erro");
            }
          })
          .catch((error) => {
            console.error("Erro ao resetar:", error);
            exibirToast("🔌 Erro de conexão ao resetar banca.", "erro");
          });
      });
    }

    const btnCancelarReset = document.getElementById("btnCancelarReset");
    if (btnCancelarReset) {
      btnCancelarReset.addEventListener("click", () => {
        const confirmarReset = document.getElementById("confirmarReset");
        if (confirmarReset) confirmarReset.style.display = "none";
      });
    }

    // ✅ EVENTOS DE MUDANÇA DE TIPO DE META
    if (metaFixaRadio) {
      metaFixaRadio.addEventListener("change", function () {
        if (this.checked) {
          destacarMetaSelecionada("fixa");
          atualizarUnidadeEntradaTempoReal();
        }
      });
    }

    if (metaTurboRadio) {
      metaTurboRadio.addEventListener("change", function () {
        if (this.checked) {
          destacarMetaSelecionada("turbo");
          atualizarUnidadeEntradaTempoReal();
        }
      });
    }

    // ✅ FINALIZAÇÃO
    adicionarEventosLimpezaCampos();

    console.log("✅ Modal inicializado com sucesso!");

    // ✅ FORÇAR ATUALIZAÇÃO FINAL DOS CÁLCULOS APÓS TUDO ESTAR PRONTO
    setTimeout(() => {
      console.log("🔄 Forçando atualização final dos cálculos...");
      if (typeof atualizarUnidadeEntradaTempoReal === "function") {
        atualizarUnidadeEntradaTempoReal();
      }
    }, 800);

    setTimeout(() => {
      if (typeof atualizarUnidadeEntradaTempoReal === "function") {
        atualizarUnidadeEntradaTempoReal();
      }
    }, 1200);
  }

  // ✅ ADICIONAR ESTAS FUNÇÕES APÓS A FUNÇÃO inicializarModalDeposito():

  // FUNÇÃO PARA OBTER TIPO DE META SELECIONADO
  function obterTipoMetaSelecionado() {
    const metaTurboRadio = document.getElementById("metaTurbo");
    const metaFixaRadio = document.getElementById("metaFixa");

    if (metaTurboRadio && metaTurboRadio.checked) {
      return "Meta Turbo";
    } else if (metaFixaRadio && metaFixaRadio.checked) {
      return "Meta Fixa";
    }
    return null; // Nenhum selecionado
  }

  // FUNÇÃO PARA DESTACAR META SELECIONADA VISUALMENTE
  function destacarMetaSelecionada(tipo) {
    const modal = document.getElementById("modalDeposito");
    if (!modal) return;

    const opcoes = modal.querySelectorAll(".opcao-meta");
    opcoes.forEach((opcao) => {
      opcao.classList.remove("selecionada");
    });

    const opcaoSelecionada = modal.querySelector(
      tipo === "fixa" ? "#metaFixa" : "#metaTurbo"
    );
    if (opcaoSelecionada) {
      const opcaoContainer = opcaoSelecionada.closest(".opcao-meta");
      if (opcaoContainer) {
        opcaoContainer.classList.add("selecionada");
      }
    }

    console.log(`✅ Meta ${tipo} destacada visualmente`);
  }

  // ✅ FUNÇÃO MODIFICADA PARA INCLUIR TIPO DE META NA MENSAGEM
  function gerarMensagemOperacao(tipoOperacao, valor = null) {
    const tipoMeta = obterTipoMetaSelecionado();
    const valorFormatado = valor
      ? valor.toLocaleString("pt-BR", {
          style: "currency",
          currency: "BRL",
        })
      : "";

    const metaTexto = tipoMeta ? ` (${tipoMeta})` : "";

    switch (tipoOperacao) {
      case "deposito":
      case "add":
        return `💰 Depósito de ${valorFormatado} realizado com sucesso!${metaTexto}`;
      case "saque":
      case "sacar":
        return `💸 Saque de ${valorFormatado} realizado com sucesso!${metaTexto}`;
      case "alterar":
        return `⚙️ Configurações alteradas com sucesso!${metaTexto}`;
      case "resetar":
        return `🔄 Banca resetada com sucesso!${metaTexto}`;
      default:
        return `✅ Operação realizada com sucesso!${metaTexto}`;
    }
  }

  function configurarEventosDeMeta() {
    if (diaria) {
      diaria.addEventListener("focus", () => {
        // Remove o % ao focar para facilitar edição
        const valorAtual = diaria.value.replace("%", "");
        diaria.value = valorAtual;
        diaria.select();
      });

      diaria.addEventListener("input", (e) => {
        // Permite apenas números, vírgula e ponto
        let valor = e.target.value.replace(/[^\d,.]/g, "");

        // Substitui ponto por vírgula durante a digitação
        valor = valor.replace(".", ",");

        // Garante apenas uma vírgula
        const partes = valor.split(",");
        if (partes.length > 2) {
          valor = partes[0] + "," + partes.slice(1).join("");
        }

        // Limita a 1 casa decimal
        if (partes.length === 2 && partes[1].length > 1) {
          valor = partes[0] + "," + partes[1].substring(0, 1);
        }

        e.target.value = valor;
        atualizarUnidadeEntradaTempoReal();
      });

      diaria.addEventListener("blur", () => {
        let valor = diaria.value.replace(",", "."); // Converte vírgula para ponto
        let numero = parseFloat(valor) || 1;

        // Limita entre 0.1 e 100
        numero = Math.max(0.1, Math.min(100, numero));

        // Formata: se for decimal usa ponto, se inteiro não mostra decimal
        const valorFormatado =
          numero % 1 === 0
            ? numero.toFixed(0)
            : numero.toFixed(2).replace(/\.?0+$/, "");

        diaria.value = `${valorFormatado}%`;
        atualizarUnidadeEntradaTempoReal();
      });
    }

    if (unidade) {
      unidade.addEventListener("input", () => {
        unidade.value = unidade.value.replace(/\D/g, "");
        atualizarUnidadeEntradaTempoReal();
        calcularMeta(valorOriginalBanca);
      });

      unidade.addEventListener("blur", () => {
        unidade.value = parseInt(unidade.value) || "";
        atualizarUnidadeEntradaTempoReal();
        calcularMeta(valorOriginalBanca);
      });
    }

    if (oddsMeta) {
      oddsMeta.addEventListener("input", () => {
        atualizarUnidadeEntradaTempoReal();
        calcularOdds(unidadeCalculada);
      });

      oddsMeta.addEventListener("blur", () => {
        atualizarUnidadeEntradaTempoReal();
        calcularOdds(unidadeCalculada);
      });
    }
  }

  function formatarPorcentagem(valor) {
    const num = parseFloat(valor);
    return !isNaN(num) ? `${num}%` : "";
  }

  let unidadeCalculada = 0;

  function calcularMeta(bancaFloat) {
    const percentualRaw = diaria
      ? diaria.value.replace("%", "").replace(",", ".")
      : "2";
    const percentFloat = parseFloat(percentualRaw);

    if (isNaN(percentFloat)) {
      if (resultadoCalculo) resultadoCalculo.textContent = "";
      return;
    }

    const valorBancaLabel = document.getElementById("valorBancaLabel");
    const valorSpan = valorBancaLabel
      ? valorBancaLabel.textContent.replace(/[^\d,]/g, "").replace(",", ".")
      : "0";
    const valorSpanFloat = parseFloat(valorSpan) || 0;

    const valorBancaInput = document.getElementById("valorBanca");
    const valorInputRaw = valorBancaInput
      ? valorBancaInput.value.replace(/[^\d]/g, "")
      : "0";
    const valorInputFloat = parseFloat(valorInputRaw) / 100 || 0;

    const acaoSelect = document.getElementById("acaoBanca");
    const tipoAcao = acaoSelect ? acaoSelect.value : "";

    let baseCalculo;
    if (Math.abs(valorSpanFloat - valorInputFloat) < 0.01) {
      baseCalculo = valorInputFloat;
    } else {
      baseCalculo =
        tipoAcao === "sacar"
          ? Math.max(0, valorSpanFloat - valorInputFloat)
          : tipoAcao === "add"
          ? valorSpanFloat + valorInputFloat
          : valorSpanFloat;
    }

    const unidadeEntrada = baseCalculo * (percentFloat / 100);

    if (resultadoCalculo) {
      resultadoCalculo.textContent = `Unidade: ${unidadeEntrada.toLocaleString(
        "pt-BR",
        {
          style: "currency",
          currency: "BRL",
        }
      )}`;
    }

    unidadeCalculada = baseCalculo * (percentFloat / 100);
    calcularUnidade(unidadeCalculada);
    calcularOdds(unidadeCalculada);
  }

  function calcularUnidade(valorMeta) {
    const unidadeFloat = unidade ? parseInt(unidade.value) : 2;
    if (!isNaN(unidadeFloat) && !isNaN(valorMeta)) {
      const total = unidadeFloat * valorMeta;
      if (resultadoUnidade) {
        resultadoUnidade.textContent = `Meta Diária: ${total.toLocaleString(
          "pt-BR",
          {
            style: "currency",
            currency: "BRL",
          }
        )}`;
      }
    } else {
      if (resultadoUnidade) resultadoUnidade.textContent = "";
    }
  }

  function calcularOdds(valorUnidade) {
    const oddsRaw = oddsMeta ? oddsMeta.value.replace(",", ".") : "1.5";
    const oddsFloat = parseFloat(oddsRaw);

    const unidadeFloat = unidade ? parseInt(unidade.value) || 0 : 0;
    const valorUnidadeSeguro = !isNaN(valorUnidade) ? valorUnidade : 0;
    const oddsSeguro = !isNaN(oddsFloat) ? oddsFloat : 0;

    const brutoPorEntrada = valorUnidadeSeguro * oddsSeguro;
    const lucroPorEntrada = brutoPorEntrada - valorUnidadeSeguro;
    const metaTotal = unidadeFloat * valorUnidadeSeguro;

    let entradas = 0;
    let lucroAcumulado = 0;

    while (lucroAcumulado < metaTotal && entradas < 1000) {
      entradas++;
      lucroAcumulado = entradas * lucroPorEntrada;
    }

    if (resultadoOdds) {
      resultadoOdds.textContent = `${entradas} Entradas Para Meta Diária`;
    }
  }

  function atualizarDadosModal() {
    fetch("ajax_deposito.php")
      .then((res) => res.json())
      .then((data) => {
        if (!data.success) return;

        valorOriginalBanca = parseFloat(data.banca);

        const valorBancaLabel = document.getElementById("valorBancaLabel");
        if (valorBancaLabel) {
          valorBancaLabel.textContent = valorOriginalBanca.toLocaleString(
            "pt-BR",
            {
              style: "currency",
              currency: "BRL",
            }
          );
        }

        const lucroTotalLabel = document.getElementById("valorLucroLabel");
        if (lucroTotalLabel) {
          const lucro = parseFloat(data.lucro);
          const lucroFormatado = lucro.toLocaleString("pt-BR", {
            style: "currency",
            currency: "BRL",
          });
          lucroTotalLabel.textContent = lucroFormatado;
        }

        if (data.meta_diaria_formatada) {
          atualizarMetaDiaria(data.meta_diaria_formatada);
        }

        if (data.unidade_entrada_formatada) {
          atualizarUnidadeEntradaFormulario(data.unidade_entrada_formatada);
        }

        // ✅ CORRIGIDO: Mantém decimais
        if (diaria) {
          const diariaValor = Math.max(parseFloat(data.diaria || "1.00"), 0.1);

          // Remove zeros desnecessários e usa ponto decimal
          const diariaFormatada =
            diariaValor % 1 === 0
              ? diariaValor.toFixed(0)
              : parseFloat(diariaValor.toFixed(2)).toString();

          diaria.value = `${diariaFormatada}%`;
        }

        if (unidade) {
          unidade.value = parseInt(data.unidade || "1");
        }

        if (oddsMeta) {
          oddsMeta.value = parseFloat(data.odds || "1.50").toLocaleString(
            "pt-BR",
            {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2,
            }
          );
        }

        const selectAcao = document.getElementById("selectAcao");
        const inputValor = document.getElementById("inputValor");
        if (selectAcao) selectAcao.value = "";
        if (inputValor) inputValor.value = "";

        calcularMeta(valorOriginalBanca);
        setTimeout(() => atualizarAreaDireita(), 200);
      })
      .catch((error) => {
        console.error("Erro ao atualizar dados:", error);
      });

    if (typeof atualizarLucroEBancaViaAjax === "function") {
      atualizarLucroEBancaViaAjax();
    }
  }

  // ✅ SOBRESCREVER A FUNÇÃO EXISTENTE PARA INCLUIR ÁREA DIREITA
  const funcaoOriginalLucro = window.atualizarLucroEBancaViaAjax;
  window.atualizarLucroEBancaViaAjax = function () {
    if (typeof funcaoOriginalLucro === "function") {
      funcaoOriginalLucro();
    }
    setTimeout(() => atualizarAreaDireita(), 100);
  };

  // ✅ INICIALIZAÇÃO DA ÁREA DIREITA COM MÚLTIPLAS TENTATIVAS
  setTimeout(() => {
    console.log("🚀 Iniciando sistema de atualização da área direita...");
    atualizarAreaDireita();
  }, 500);

  // ✅ Segunda tentativa mais rápida
  setTimeout(() => {
    atualizarAreaDireita();
  }, 1500);

  // ✅ ATUALIZAÇÃO AUTOMÁTICA MAIS FREQUENTE PARA UND
  setInterval(() => {
    // ✅ SÓ ATUALIZA SE NÃO HOUVER FILTRO ATIVO
    if (
      typeof SistemaFiltroPeriodo === "undefined" ||
      SistemaFiltroPeriodo.periodoAtual === "dia"
    ) {
      atualizarAreaDireita();
    }
  }, 5000);

  // ✅ ESCUTAR EVENTOS CUSTOMIZADOS PARA ATUALIZAÇÃO IMEDIATA
  document.addEventListener("mentorCadastrado", (event) => {
    console.log("📢 Evento mentorCadastrado recebido:", event.detail);
    // ✅ Múltiplas tentativas imediatas
    atualizarAreaDireita();
    setTimeout(() => atualizarAreaDireita(), 10);
    setTimeout(() => atualizarAreaDireita(), 50);
  });

  document.addEventListener("mentorExcluido", (event) => {
    console.log("📢 Evento mentorExcluido recebido:", event.detail);
    // ✅ Múltiplas tentativas imediatas para exclusão
    atualizarAreaDireita();
    setTimeout(() => atualizarAreaDireita(), 10);
    setTimeout(() => atualizarAreaDireita(), 50);
    setTimeout(() => atualizarAreaDireita(), 200);
  });

  document.addEventListener("bancaAtualizada", () => {
    console.log(
      "📢 Evento bancaAtualizada recebido, atualizando área direita..."
    );
    atualizarAreaDireita();
    setTimeout(() => atualizarAreaDireita(), 50);
  });

  document.addEventListener("areaAtualizacao", (event) => {
    console.log("📢 Evento areaAtualizacao recebido:", event.detail);
    // ✅ Event listener geral para qualquer tipo de atualização
    atualizarAreaDireita();
  });

  // ✅ LISTENER PARA ATUALIZAÇÕES MANUAIS VIA CLICK - INCLUINDO EXCLUSÕES
  document.addEventListener("click", (event) => {
    // ✅ Se clicou em qualquer botão de ação, atualiza área direita
    if (
      event.target.matches(
        'button[type="submit"], .botao-enviar, .btn-confirmar'
      )
    ) {
      setTimeout(() => atualizarAreaDireita(), 100);
    }

    // ✅ DETECÇÃO ESPECÍFICA PARA BOTÕES DE EXCLUSÃO
    const isDeleteButton =
      event.target.matches(
        '.btn-excluir, .delete-btn, .remove-btn, [data-action="delete"], .fa-trash, .fa-times'
      ) ||
      event.target.closest(
        '.btn-excluir, .delete-btn, .remove-btn, [data-action="delete"]'
      ) ||
      event.target.classList.contains("fa-trash") ||
      event.target.classList.contains("fa-times") ||
      event.target.parentElement?.classList.contains("fa-trash") ||
      event.target.parentElement?.classList.contains("fa-times");

    if (isDeleteButton) {
      console.log(
        "🗑️ Clique em botão de exclusão detectado - atualizando área direita..."
      );

      // ✅ Atualização escalonada após exclusão
      setTimeout(() => atualizarAreaDireita(), 200);
      setTimeout(() => atualizarAreaDireita(), 500);
      setTimeout(() => atualizarAreaDireita(), 1000);
    }
  });

  console.log("✅ Sistema completo inicializado com sucesso!");
});

// ========================================================================================================================
//          🚀 SISTEMA DE ATUALIZAÇÃO AUTOMÁTICA DOS CÁLCULOS AO ABRIR MODAL
// ========================================================================================================================
// ✅ COLE ESTE CÓDIGO LOGO APÓS A FUNÇÃO inicializarModalDeposito()

// ✅ FUNÇÃO GLOBAL PARA FORÇAR ATUALIZAÇÃO DOS CÁLCULOS
window.forcarAtualizacaoCalculosModal = function () {
  console.log("🔄 Forçando atualização dos cálculos do modal...");

  // Primeira tentativa imediata
  if (typeof atualizarUnidadeEntradaTempoReal === "function") {
    atualizarUnidadeEntradaTempoReal();
  }

  // Segunda tentativa após 100ms
  setTimeout(() => {
    if (typeof atualizarUnidadeEntradaTempoReal === "function") {
      atualizarUnidadeEntradaTempoReal();
      console.log("✅ Cálculos atualizados (100ms)");
    }
  }, 100);

  // Terceira tentativa após 300ms
  setTimeout(() => {
    if (typeof atualizarUnidadeEntradaTempoReal === "function") {
      atualizarUnidadeEntradaTempoReal();
      console.log("✅ Cálculos atualizados (300ms)");
    }
  }, 300);

  // Quarta tentativa após 600ms (garantia final)
  setTimeout(() => {
    if (typeof atualizarUnidadeEntradaTempoReal === "function") {
      atualizarUnidadeEntradaTempoReal();
      console.log("✅ Cálculos atualizados (600ms)");
    }
  }, 600);
};

// ✅ INTEGRAR COM EVENTOS DE ABERTURA DO MODAL
document.addEventListener("DOMContentLoaded", function () {
  setTimeout(() => {
    const modalDeposito = document.getElementById("modalDeposito");

    if (modalDeposito) {
      // Observer para detectar quando o modal é exibido
      const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
          if (mutation.attributeName === "style") {
            const displayValue = modalDeposito.style.display;

            if (displayValue === "flex" || displayValue === "block") {
              console.log(
                "📂 Modal aberto - iniciando atualização de cálculos..."
              );

              // Chamar funções de atualização
              setTimeout(() => {
                if (typeof atualizarMetasModalBancaSync === "function") {
                  atualizarMetasModalBancaSync();
                }
                if (typeof atualizarRotulosComDias === "function") {
                  atualizarRotulosComDias();
                }
                if (typeof forcarAtualizacaoCalculosModal === "function") {
                  forcarAtualizacaoCalculosModal();
                }
              }, 200);
            }
          }
        });
      });

      observer.observe(modalDeposito, {
        attributes: true,
        attributeFilter: ["style"],
      });

      console.log("✅ Observer do modal configurado!");
    }
  }, 1000);
});

// ✅ OVERRIDE DA FUNÇÃO DE ABERTURA DO MODAL
if (typeof window.abrirModalDeposito === "function") {
  const funcaoOriginalAbrir = window.abrirModalDeposito;

  window.abrirModalDeposito = function () {
    // Chamar função original
    funcaoOriginalAbrir.call(this);

    // Forçar atualização após abertura
    setTimeout(() => {
      console.log("🔄 Modal aberto via função global - atualizando...");
      if (typeof forcarAtualizacaoCalculosModal === "function") {
        forcarAtualizacaoCalculosModal();
      }
    }, 250);
  };
}

// ✅ INTEGRAR COM EVENTO DO BOTÃO "GERENCIAR BANCA"
document.addEventListener("DOMContentLoaded", function () {
  setTimeout(() => {
    const botaoGerencia = document.getElementById("abrirGerenciaBanca");

    if (botaoGerencia) {
      // Adicionar listener adicional (não remove os existentes)
      botaoGerencia.addEventListener("click", function () {
        console.log("🎯 Botão Gerenciar Banca clicado");

        // Aguardar o modal abrir e então forçar atualização
        setTimeout(() => {
          if (typeof forcarAtualizacaoCalculosModal === "function") {
            forcarAtualizacaoCalculosModal();
          }
        }, 400);
      });

      console.log("✅ Listener do botão Gerenciar Banca configurado!");
    }
  }, 1500);
});

// ✅ MONITORAR MUDANÇAS NOS INPUTS DO MODAL EM TEMPO REAL
document.addEventListener("DOMContentLoaded", function () {
  setTimeout(() => {
    // Lista de inputs que disparam atualização
    const inputsParaMonitorar = [
      "porcentagem",
      "unidadeMeta",
      "oddsMeta",
      "valorBanca",
    ];

    inputsParaMonitorar.forEach((inputId) => {
      const input = document.getElementById(inputId);
      if (input) {
        // Atualizar ao digitar
        input.addEventListener("input", function () {
          setTimeout(() => {
            if (typeof atualizarUnidadeEntradaTempoReal === "function") {
              atualizarUnidadeEntradaTempoReal();
            }
          }, 50);
        });

        // Atualizar ao sair do campo
        input.addEventListener("blur", function () {
          setTimeout(() => {
            if (typeof atualizarUnidadeEntradaTempoReal === "function") {
              atualizarUnidadeEntradaTempoReal();
            }
          }, 100);
        });

        // Atualizar ao pressionar Enter
        input.addEventListener("keypress", function (e) {
          if (e.key === "Enter") {
            setTimeout(() => {
              if (typeof atualizarUnidadeEntradaTempoReal === "function") {
                atualizarUnidadeEntradaTempoReal();
              }
            }, 100);
          }
        });
      }
    });

    // Monitorar mudança nos radio buttons de tipo de meta
    const metaFixa = document.getElementById("metaFixa");
    const metaTurbo = document.getElementById("metaTurbo");

    if (metaFixa) {
      metaFixa.addEventListener("change", function () {
        if (this.checked) {
          console.log("🎯 Meta Fixa selecionada - atualizando cálculos");
          setTimeout(() => {
            if (typeof atualizarUnidadeEntradaTempoReal === "function") {
              atualizarUnidadeEntradaTempoReal();
            }
          }, 100);
        }
      });
    }

    if (metaTurbo) {
      metaTurbo.addEventListener("change", function () {
        if (this.checked) {
          console.log("🚀 Meta Turbo selecionada - atualizando cálculos");
          setTimeout(() => {
            if (typeof atualizarUnidadeEntradaTempoReal === "function") {
              atualizarUnidadeEntradaTempoReal();
            }
          }, 100);
        }
      });
    }

    console.log("✅ Monitores de input configurados!");
  }, 1500);
});

// ✅ MONITORAR MUDANÇAS NO DROPDOWN DE AÇÕES
document.addEventListener("DOMContentLoaded", function () {
  setTimeout(() => {
    const dropdownItems = document.querySelectorAll(".dropdown-menu li");

    dropdownItems.forEach((item) => {
      item.addEventListener("click", function () {
        const tipo = this.getAttribute("data-value");
        console.log(`📋 Ação selecionada: ${tipo}`);

        // Aguardar processamento e atualizar
        setTimeout(() => {
          if (typeof atualizarUnidadeEntradaTempoReal === "function") {
            atualizarUnidadeEntradaTempoReal();
          }
        }, 150);
      });
    });
  }, 1500);
});

// ✅ VERIFICAR SE MODAL ESTÁ ABERTO E FORÇAR ATUALIZAÇÃO PERIÓDICA
window.verificarModalAbertoEAtualizar = function () {
  const modal = document.getElementById("modalDeposito");

  if (
    modal &&
    (modal.style.display === "flex" || modal.style.display === "block")
  ) {
    // Modal está aberto - atualizar cálculos
    if (typeof atualizarUnidadeEntradaTempoReal === "function") {
      atualizarUnidadeEntradaTempoReal();
    }
  }
};

// ✅ EXECUTAR VERIFICAÇÃO A CADA 3 SEGUNDOS ENQUANTO MODAL ESTIVER ABERTO
setInterval(() => {
  verificarModalAbertoEAtualizar();
}, 3000);

// ✅ INTEGRAÇÃO COM SISTEMA DE ATUALIZAÇÃO DA ÁREA DIREITA
document.addEventListener("bancaAtualizada", () => {
  console.log("📢 Evento bancaAtualizada - atualizando cálculos do modal");
  setTimeout(() => {
    if (typeof atualizarUnidadeEntradaTempoReal === "function") {
      atualizarUnidadeEntradaTempoReal();
    }
    if (typeof atualizarMetasModalBancaSync === "function") {
      atualizarMetasModalBancaSync();
    }
  }, 200);
});

document.addEventListener("areaAtualizacao", () => {
  console.log("📢 Evento areaAtualizacao - atualizando cálculos do modal");
  setTimeout(() => {
    if (typeof atualizarUnidadeEntradaTempoReal === "function") {
      atualizarUnidadeEntradaTempoReal();
    }
  }, 150);
});

// ✅ OVERRIDE DA FUNÇÃO atualizarDadosModal PARA GARANTIR ATUALIZAÇÃO
document.addEventListener("DOMContentLoaded", function () {
  setTimeout(() => {
    if (typeof window.atualizarDadosModal === "function") {
      const funcaoOriginalAtualizar = window.atualizarDadosModal;

      window.atualizarDadosModal = function () {
        // Chamar função original
        funcaoOriginalAtualizar.call(this);

        // Forçar atualização dos cálculos após atualizar dados
        setTimeout(() => {
          console.log("🔄 Dados do modal atualizados - recalculando...");
          if (typeof atualizarUnidadeEntradaTempoReal === "function") {
            atualizarUnidadeEntradaTempoReal();
          }
        }, 300);
      };
    }
  }, 2000);
});

console.log(
  "✅ Sistema de atualização automática dos cálculos do modal configurado!"
);
console.log("🎯 Comandos disponíveis:");
console.log("   - forcarAtualizacaoCalculosModal() - Força atualização manual");
console.log(
  "   - verificarModalAbertoEAtualizar() - Verifica e atualiza se modal aberto"
);

// ========================================================================================================================
//          FIM DO SISTEMA DE ATUALIZAÇÃO AUTOMÁTICA
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
//========================================================================================================================
//                    SISTEMA DINÂMICO DE RÓTULO E ÍCONE DO LUCRO
//========================================================================================================================

const SistemaLucroDinamico = {
  ultimoValor: null,

  estados: {
    positivo: {
      texto: "Lucro",
      icone: "fa-solid fa-arrow-trend-up",
      cor: "#30ca0a",
      classe: "positivo",
    },
    negativo: {
      texto: "Negativo",
      icone: "fa-solid fa-arrow-trend-down",
      cor: "#e74c3c",
      classe: "negativo",
    },
    neutro: {
      texto: "Neutro",
      icone: "fa-solid fa-minus",
      cor: "#7f8c8d",
      classe: "neutro",
    },
  },

  determinarEstado(valorLucro) {
    if (valorLucro > 0) return this.estados.positivo;
    if (valorLucro < 0) return this.estados.negativo;
    return this.estados.neutro;
  },

  extrairValorNumerico(textoValor) {
    if (typeof textoValor === "number") return textoValor;
    const valorLimpo = textoValor.replace(/[^\d,.-]/g, "").replace(",", ".");
    return parseFloat(valorLimpo) || 0;
  },

  atualizarLucro(valorLucro) {
    try {
      if (typeof valorLucro === "string") {
        valorLucro = this.extrairValorNumerico(valorLucro);
      }

      const campoLucro = document.querySelector(".campo-lucro");
      const labelLucro = document.querySelector(".campo-lucro .label-lucro");
      const icone = labelLucro?.querySelector("i");
      const spanTexto = labelLucro?.querySelector("span");
      const valorLabel = document.getElementById("valorLucroLabel");

      if (!campoLucro || !labelLucro || !icone || !spanTexto || !valorLabel)
        return false;

      const estado = this.determinarEstado(valorLucro);

      campoLucro.classList.remove("positivo", "negativo", "neutro");
      valorLabel.classList.remove("positivo", "negativo", "neutro");

      campoLucro.classList.add(estado.classe);
      valorLabel.classList.add(estado.classe);

      spanTexto.textContent = estado.texto;
      spanTexto.style.color = estado.cor;

      icone.className = estado.icone;
      icone.style.color = estado.cor;

      valorLabel.style.color = estado.cor;

      if (!valorLabel.textContent.includes("R$")) {
        valorLabel.textContent = valorLucro.toLocaleString("pt-BR", {
          style: "currency",
          currency: "BRL",
        });
      }

      this.ultimoValor = valorLucro;
      return true;
    } catch (error) {
      console.error("❌ Erro ao atualizar lucro:", error);
      return false;
    }
  },

  atualizarDoDOM() {
    try {
      const valorLabel = document.getElementById("valorLucroLabel");
      if (!valorLabel?.textContent) return false;

      const valorNumerico = this.extrairValorNumerico(valorLabel.textContent);

      if (
        this.ultimoValor === null ||
        Math.abs(valorNumerico - this.ultimoValor) > 0.01
      ) {
        return this.atualizarLucro(valorNumerico);
      }
      return true;
    } catch (error) {
      return false;
    }
  },

  async verificarEAtualizarLucro() {
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
        if (data.success) {
          const lucro = parseFloat(data.lucro_total_display) || 0;
          return this.atualizarLucro(lucro);
        }
      }
      return this.atualizarDoDOM();
    } catch (error) {
      return this.atualizarDoDOM();
    }
  },

  iniciarObservador() {
    const valorLabel = document.getElementById("valorLucroLabel");
    if (!valorLabel) return;

    const observer = new MutationObserver(() => this.atualizarDoDOM());
    observer.observe(valorLabel, {
      childList: true,
      characterData: true,
      subtree: true,
    });
  },

  inicializar() {
    console.log("🚀 Inicializando Sistema de Lucro Dinâmico...");

    setTimeout(() => this.atualizarDoDOM(), 100);
    setTimeout(() => this.verificarEAtualizarLucro(), 500);
    setTimeout(() => this.iniciarObservador(), 800);

    setInterval(() => this.verificarEAtualizarLucro(), 10000);

    console.log("✅ Sistema de Lucro inicializado!");
  },
};

// Integração com eventos
document.addEventListener("DOMContentLoaded", () => {
  setTimeout(() => SistemaLucroDinamico.inicializar(), 1000);
});

document.addEventListener("bancaAtualizada", () => {
  setTimeout(() => SistemaLucroDinamico.verificarEAtualizarLucro(), 200);
});

document.addEventListener("areaAtualizacao", () => {
  setTimeout(() => SistemaLucroDinamico.verificarEAtualizarLucro(), 300);
});

// Integração com modal
const _originalInit = window.inicializarModalDeposito;
if (typeof _originalInit === "function") {
  window.inicializarModalDeposito = function () {
    _originalInit.call(this);
    setTimeout(() => SistemaLucroDinamico.atualizarDoDOM(), 500);
  };
}

// Atalhos globais
window.lucro = {
  positivo: () => {
    SistemaLucroDinamico.atualizarLucro(150);
    return "✅ Positivo";
  },
  negativo: () => {
    SistemaLucroDinamico.atualizarLucro(-75);
    return "✅ Negativo";
  },
  neutro: () => {
    SistemaLucroDinamico.atualizarLucro(0);
    return "✅ Neutro";
  },
  atualizar: (v) => {
    SistemaLucroDinamico.atualizarLucro(v);
    return `✅ ${v}`;
  },
  verificar: () => {
    SistemaLucroDinamico.verificarEAtualizarLucro();
    return "🔄 Verificando...";
  },
  dom: () => {
    SistemaLucroDinamico.atualizarDoDOM();
    return "🔄 DOM...";
  },
  status: () => {
    const v = document.getElementById("valorLucroLabel")?.textContent || "N/A";
    console.log(`💰 DOM: ${v} | Último: ${SistemaLucroDinamico.ultimoValor}`);
    return v;
  },
};

window.SistemaLucroDinamico = SistemaLucroDinamico;
window.testarLucro = (v) => window.lucro.atualizar(v);

console.log("✅ Sistema de Lucro Dinâmico carregado!");
console.log(
  "🧪 Comandos: lucro.positivo() | lucro.negativo() | lucro.neutro() | lucro.status()"
);
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
//========================================================================================================================
//                    INTEGRAÇÃO CORRIGIDA: CALCULAR META DIA/MÊS/ANO NO MODAL DE BANCA
//========================================================================================================================

// Função auxiliar para calcular dias restantes (já existe no código, mantemos aqui por segurança)
function calcularDiasRestantesMesModal() {
  const hoje = new Date();
  const ultimoDiaMes = new Date(hoje.getFullYear(), hoje.getMonth() + 1, 0);
  const diasRestantes = ultimoDiaMes.getDate() - hoje.getDate() + 1;
  return diasRestantes;
}

function calcularDiasRestantesAnoModal() {
  const hoje = new Date();
  const fimAno = new Date(hoje.getFullYear(), 11, 31);
  const diffTime = fimAno - hoje;
  const diasRestantes = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
  return diasRestantes;
}

// ✅ FUNÇÃO PRINCIPAL PARA ATUALIZAR METAS DO MODAL - COM BANCA CONGELADA
// ✅ FUNÇÃO PRINCIPAL PARA ATUALIZAR METAS DOMODAL - CORRIGIDA PARA META FIXA
// ✅ FUNÇÃO PRINCIPAL PARA ATUALIZAR METAS DO MODAL - VERSÃO FINAL COM ARREDONDAMENTO
function atualizarMetasModalBancaSync() {
  try {
    // Obter elementos
    const diaria = document.getElementById("porcentagem");
    const unidade = document.getElementById("unidadeMeta");
    const valorBancaInput = document.getElementById("valorBanca");
    const acaoSelect = document.getElementById("acaoBanca");
    const metaFixaRadio = document.getElementById("metaFixa");
    const metaTurboRadio = document.getElementById("metaTurbo");
    const lucroTotalLabel = document.getElementById("valorLucroLabel");

    const resultadoMetaDia = document.getElementById("resultadoMetaDia");
    const resultadoMetaMes = document.getElementById("resultadoMetaMes");
    const resultadoMetaAno = document.getElementById("resultadoMetaAno");
    const resultadoUnidadeEntrada = document.getElementById(
      "resultadoUnidadeEntrada"
    );

    if (!diaria || !unidade || !resultadoMetaDia) return;

    // ✅ EXTRAIR LUCRO ATUAL (TOTAL)
    let lucroTotal = 0;
    if (lucroTotalLabel && lucroTotalLabel.textContent) {
      const lucroTexto = lucroTotalLabel.textContent
        .replace(/[^\d,-]/g, "")
        .replace(",", ".");
      lucroTotal = parseFloat(lucroTexto) || 0;
    }

    // ✅ DETECTAR TIPO DE META SELECIONADO
    const isMetaFixa = metaFixaRadio && metaFixaRadio.checked;
    const isMetaTurbo = metaTurboRadio && metaTurboRadio.checked;

    // ✅ BUSCAR DADOS DO SERVIDOR (incluindo banca congelada)
    fetch("ajax_deposito.php")
      .then((response) => response.json())
      .then((data) => {
        if (!data.success) return;

        // ✅ Valores do servidor
        const bancaAtual = parseFloat(data.banca) || 0;
        const bancaInicioDia = parseFloat(data.banca_inicio_dia) || bancaAtual;
        const lucroAteOntem = parseFloat(data.lucro_ate_ontem) || 0;

        console.log(`📊 atualizarMetasModalBancaSync:
        Banca Atual: R$ ${bancaAtual.toFixed(2)}
        Banca Início Dia: R$ ${bancaInicioDia.toFixed(2)}
        Tipo Meta: ${isMetaFixa ? "FIXA" : "TURBO"}`);

        // ✅ Extrair porcentagem
        let percentualRaw = diaria.value
          .replace("%", "")
          .trim()
          .replace(",", ".");
        const percentFinal = parseFloat(percentualRaw) || 1;

        // ✅ Extrair unidade
        const unidadeInt = parseInt(unidade.value) || 1;

        // ✅ CÁLCULO DA BASE DEPENDE DO TIPO DE META
        let baseCalculo;

        if (isMetaFixa) {
          // META FIXA: usa banca inicial (sem lucro acumulado)
          baseCalculo = bancaInicioDia - lucroAteOntem;
        } else {
          // META TURBO: usa banca do início do dia (com lucro de ontem)
          baseCalculo = bancaInicioDia;
        }

        // ✅ CALCULAR UND COM ARREDONDAMENTO CORRETO
        const unidadeEntrada = arredondarParaDuasCasas(
          baseCalculo * (percentFinal / 100)
        );

        // ✅ Meta Base do Dia
        const metaDiariaBase = arredondarParaDuasCasas(
          unidadeEntrada * unidadeInt
        );

        // ✅ Calcular dias restantes
        const diasRestantesMes = calcularDiasRestantesMesModal();
        const diasRestantesAno = calcularDiasRestantesAnoModal();

        // ✅ Metas Mensais e Anuais
        const metaMensal = arredondarParaDuasCasas(
          metaDiariaBase * diasRestantesMes
        );
        const metaAnual = arredondarParaDuasCasas(
          metaDiariaBase * diasRestantesAno
        );

        // ✅ AJUSTAR META DO DIA BASEADO NO LUCRO DE HOJE
        const lucroHoje = lucroTotal - lucroAteOntem;
        let metaDiaFinal = metaDiariaBase;

        if (lucroHoje < 0) {
          metaDiaFinal = arredondarParaDuasCasas(
            metaDiariaBase + Math.abs(lucroHoje)
          );
        } else if (lucroHoje > 0) {
          metaDiaFinal = arredondarParaDuasCasas(
            Math.max(0, metaDiariaBase - lucroHoje)
          );
        }

        // ✅ ATUALIZAR DISPLAYS COM FORMATAÇÃO CORRETA
        if (resultadoUnidadeEntrada) {
          resultadoUnidadeEntrada.textContent = formatarMoeda(unidadeEntrada);
          console.log("✅ UND (Sync):", resultadoUnidadeEntrada.textContent);
        }

        if (resultadoMetaDia) {
          if (lucroHoje >= metaDiariaBase && metaDiariaBase > 0) {
            const valorRiscado = formatarMoeda(metaDiariaBase);

            if (Math.abs(lucroHoje - metaDiariaBase) < 0.01) {
              resultadoMetaDia.innerHTML = `<span style="text-decoration: line-through;">${valorRiscado}</span> Batida! <i class="fa-solid fa-trophy" style="color: #FFD700;"></i>`;
            } else {
              const valorExcedente = lucroHoje - metaDiariaBase;
              const excedenteFormatado = formatarMoeda(valorExcedente);
              resultadoMetaDia.innerHTML = `<span style="text-decoration: line-through;">${valorRiscado}</span> Superada! +${excedenteFormatado} <i class="fa-solid fa-rocket" style="color: #FF6B6B;"></i>`;
            }
          } else {
            resultadoMetaDia.textContent = formatarMoeda(metaDiaFinal);
          }
        }

        if (resultadoMetaMes) {
          resultadoMetaMes.textContent = formatarMoeda(metaMensal);
        }

        if (resultadoMetaAno) {
          resultadoMetaAno.textContent = formatarMoeda(metaAnual);
        }

        console.log(
          `✅ Sync - UND: R$ ${unidadeEntrada.toFixed(
            2
          )} (${percentFinal}% de R$ ${baseCalculo.toFixed(2)})`
        );
      })
      .catch((error) => {
        console.error("❌ Erro ao buscar dados do servidor:", error);
      });
  } catch (error) {
    console.error("❌ Erro ao atualizar metas:", error);
  }
}

// ========================================================================================================================
//          🔄 SISTEMA DE ATUALIZAÇÃO AUTOMÁTICA - VERSÃO DEFINITIVA
// ========================================================================================================================

// ✅ ATUALIZAR quando qualquer valor de input mudar
document.addEventListener("DOMContentLoaded", function () {
  setTimeout(() => {
    // Monitorar mudanças nos inputs do modal
    const inputsParaMonitorar = [
      "porcentagem",
      "unidadeMeta",
      "oddsMeta",
      "valorBanca",
    ];

    inputsParaMonitorar.forEach((inputId) => {
      const input = document.getElementById(inputId);
      if (input) {
        input.addEventListener("input", () => {
          setTimeout(() => atualizarMetasModalBancaSync(), 50);
        });
        input.addEventListener("change", () => {
          setTimeout(() => atualizarMetasModalBancaSync(), 50);
        });
      }
    });

    // Monitorar mudanças nos radio buttons de tipo de meta
    const metaFixaRadio = document.getElementById("metaFixa");
    const metaTurboRadio = document.getElementById("metaTurbo");

    if (metaFixaRadio) {
      metaFixaRadio.addEventListener("change", () => {
        setTimeout(() => atualizarMetasModalBancaSync(), 100);
      });
    }

    if (metaTurboRadio) {
      metaTurboRadio.addEventListener("change", () => {
        setTimeout(() => atualizarMetasModalBancaSync(), 100);
      });
    }

    // ✅ MONITORAR MUDANÇAS NO LUCRO
    const lucroLabel = document.getElementById("valorLucroLabel");
    if (lucroLabel) {
      const observerLucro = new MutationObserver(() => {
        console.log("💰 Lucro mudou - recalculando metas");
        setTimeout(() => atualizarMetasModalBancaSync(), 150);
      });

      observerLucro.observe(lucroLabel, {
        childList: true,
        characterData: true,
        subtree: true,
      });
    }

    // ✅ ATUALIZAR quando modal abrir
    const modalDeposito = document.getElementById("modalDeposito");
    if (modalDeposito) {
      const observerModal = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
          if (mutation.attributeName === "style") {
            if (
              modalDeposito.style.display === "flex" ||
              modalDeposito.style.display === "block"
            ) {
              console.log("📂 Modal aberto - atualizando metas");
              setTimeout(() => {
                atualizarMetasModalBancaSync();
                atualizarRotulosComDias();
              }, 300);
            }
          }
        });
      });

      observerModal.observe(modalDeposito, {
        attributes: true,
        attributeFilter: ["style"],
      });
    }

    console.log("✅ Monitoramento completo ativado!");
  }, 1000);
});

// ✅ INTEGRAR com eventos do sistema
document.addEventListener("bancaAtualizada", () => {
  console.log("📢 bancaAtualizada - recalculando");
  setTimeout(() => atualizarMetasModalBancaSync(), 200);
});

document.addEventListener("mentorCadastrado", () => {
  console.log("📢 mentorCadastrado - recalculando");
  setTimeout(() => atualizarMetasModalBancaSync(), 200);
});

document.addEventListener("areaAtualizacao", () => {
  console.log("📢 areaAtualizacao - recalculando");
  setTimeout(() => atualizarMetasModalBancaSync(), 150);
});

// ✅ FORÇAR ATUALIZAÇÃO após mudanças na banca/lucro
const funcaoOriginalAtualizarDados = window.atualizarDadosModal;
if (typeof funcaoOriginalAtualizarDados === "function") {
  window.atualizarDadosModal = function () {
    funcaoOriginalAtualizarDados.call(this);
    setTimeout(() => {
      console.log("🔄 atualizarDadosModal - recalculando metas");
      atualizarMetasModalBancaSync();
    }, 250);
  };
}

// ✅ GARANTIR QUE A INTEGRAÇÃO FUNCIONE APÓS INICIALIZAÇÃO DO MODAL
document.addEventListener("DOMContentLoaded", function () {
  setTimeout(() => {
    const modalDeposito = document.getElementById("modalDeposito");
    if (modalDeposito) {
      const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
          if (
            mutation.type === "attributes" &&
            mutation.attributeName === "style"
          ) {
            if (
              modalDeposito.style.display === "flex" ||
              modalDeposito.style.display === "block"
            ) {
              setTimeout(() => {
                atualizarMetasModalBancaSync();
              }, 200);
            }
          }
        });
      });

      observer.observe(modalDeposito, {
        attributes: true,
        attributeFilter: ["style"],
      });
    }
  }, 1000);
});

console.log("✅ Sistema de atualização automática de metas configurado!");

// ========================================================================================================================
//          🔄 SISTEMA DE ATUALIZAÇÃO AUTOMÁTICA DAS METAS QUANDO LUCRO MUDA
// ========================================================================================================================

// Variável para armazenar o último lucro processado
let ultimoLucroProcessado = null;

// Função para monitorar mudanças no lucro e recalcular metas
function monitorarMudancasLucro() {
  const lucroLabel = document.getElementById("valorLucroLabel");

  if (!lucroLabel) {
    console.warn("⚠️ valorLucroLabel não encontrado para monitoramento");
    return;
  }

  const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      if (mutation.type === "childList" || mutation.type === "characterData") {
        const lucroAtual = lucroLabel.textContent || "R$ 0,00";

        if (lucroAtual !== ultimoLucroProcessado) {
          console.log(
            `💰 Lucro mudou de "${ultimoLucroProcessado}" para "${lucroAtual}"`
          );
          ultimoLucroProcessado = lucroAtual;

          setTimeout(() => {
            console.log("🔄 Recalculando metas após mudança no lucro...");
            atualizarMetasModalBancaSync();
          }, 100);
        }
      }
    });
  });

  observer.observe(lucroLabel, {
    childList: true,
    characterData: true,
    subtree: true,
  });

  ultimoLucroProcessado = lucroLabel.textContent;
  console.log("✅ Monitoramento de lucro ativado");
}

// Integrar com eventos existentes do sistema
document.addEventListener("DOMContentLoaded", function () {
  setTimeout(() => {
    monitorarMudancasLucro();

    document.addEventListener("bancaAtualizada", () => {
      console.log("📢 Evento bancaAtualizada - recalculando metas");
      setTimeout(() => atualizarMetasModalBancaSync(), 150);
    });

    document.addEventListener("mentorCadastrado", () => {
      console.log("📢 Evento mentorCadastrado - recalculando metas");
      setTimeout(() => atualizarMetasModalBancaSync(), 200);
    });

    document.addEventListener("mentorExcluido", () => {
      console.log("📢 Evento mentorExcluido - recalculando metas");
      setTimeout(() => atualizarMetasModalBancaSync(), 200);
    });

    document.addEventListener("areaAtualizacao", () => {
      console.log("📢 Evento areaAtualizacao - recalculando metas");
      setTimeout(() => atualizarMetasModalBancaSync(), 100);
    });
  }, 1500);
});

// Hook adicional na função de atualização de lucro existente
if (typeof window.atualizarLucroEBancaViaAjax === "function") {
  const funcaoOriginalLucro = window.atualizarLucroEBancaViaAjax;

  window.atualizarLucroEBancaViaAjax = function () {
    if (funcaoOriginalLucro) {
      funcaoOriginalLucro.call(this);
    }

    setTimeout(() => {
      console.log("🔄 Recalculando metas após atualizarLucroEBancaViaAjax");
      atualizarMetasModalBancaSync();
    }, 200);
  };
}

console.log("✅ Sistema de monitoramento de lucro configurado!");

// ========================================================================================================================
//                    FIM: INTEGRAÇÃO CORRIGIDA
// ========================================================================================================================
