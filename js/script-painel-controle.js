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
  function exibirNotificacao(mensagem, tipo = "info") {
    const toastContainer = document.getElementById("toastModal");
    if (!toastContainer) {
      console.warn("⚠️ Container de notificação não encontrado");
      return;
    }

    toastContainer.textContent = mensagem;
    toastContainer.className = `show ${tipo}`;

    setTimeout(() => {
      toastContainer.className = "hide";
      toastContainer.textContent = "";
    }, 3000);
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

  function exibirToast(mensagem, tipo = "info") {
    const toastContainer = document.getElementById("toastModal");
    if (!toastContainer) return;

    toastContainer.textContent = mensagem;
    toastContainer.className = `show ${tipo}`;

    setTimeout(() => {
      toastContainer.className = "hide";
      toastContainer.textContent = "";
    }, 3000);

    if (tipo === "sucesso") {
      const campoValor = document.getElementById("valorBanca");
      if (campoValor) campoValor.value = "";

      const dropdownToggle = document.querySelector(".dropdown-toggle");
      if (dropdownToggle) {
        dropdownToggle.innerHTML = `<i class="fa-solid fa-hand-pointer"></i> Selecione Uma Opção <i class="fa-solid fa-chevron-down"></i>`;
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

  // ✅ SUBSTITUA TODA A FUNÇÃO inicializarModalDeposito() POR ESTA VERSÃO COMPLETA:

  function inicializarModalDeposito() {
    if (modalInicializado || !modal) return;
    modalInicializado = true;

    // ✅ SELETORES DOS ELEMENTOS (INCLUINDO CAMPOS DE META)
    valorBancaInput = modal.querySelector("#valorBanca");
    const valorBancaLabel = modal.querySelector("#valorBancaLabel");
    diaria = modal.querySelector("#porcentagem");
    unidade = modal.querySelector("#unidadeMeta");
    resultadoCalculo = modal.querySelector("#resultadoCalculo");
    resultadoUnidade = modal.querySelector("#resultadoUnidade");
    resultadoOdds = modal.querySelector("#resultadoOdds");
    oddsMeta = modal.querySelector("#oddsMeta");

    // ✅ NOVOS CAMPOS DE META
    metaFixaRadio = modal.querySelector("#metaFixa");
    metaTurboRadio = modal.querySelector("#metaTurbo");

    // ✅ CONFIGURAÇÃO DO CAMPO ODDS
    if (oddsMeta) {
      oddsMeta.addEventListener("input", () => {
        oddsMeta.value = oddsMeta.value.replace(/[^0-9.,]/g, "");
      });

      oddsMeta.addEventListener("blur", () => {
        let valor = oddsMeta.value.replace(",", ".");
        let numero = parseFloat(valor);
        oddsMeta.value = isNaN(numero) ? "1.50" : numero.toFixed(2);
      });

      let valorInicialOdds = oddsMeta.value.replace(",", ".");
      let numeroInicialOdds = parseFloat(valorInicialOdds);
      oddsMeta.value = isNaN(numeroInicialOdds)
        ? "1.50"
        : numeroInicialOdds.toFixed(2);
    }

    const acaoSelect = modal.querySelector("#acaoBanca");
    const botaoAcao = modal.querySelector("#botaoAcao");

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

    const lucroTotalLabel = modal.querySelector("#valorLucroLabel");

    // ✅ CARREGAMENTO INICIAL COM SUPORTE A META
    fetch("ajax_deposito.php")
      .then((response) => response.json())
      .then((data) => {
        if (!data.success) return;

        const lucro = parseFloat(data.lucro);
        if (lucroTotalLabel) {
          lucroTotalLabel.textContent = lucro.toLocaleString("pt-BR", {
            style: "currency",
            currency: "BRL",
          });
        }

        valorOriginalBanca = parseFloat(data.banca);
        if (valorBancaLabel) {
          valorBancaLabel.textContent = valorOriginalBanca.toLocaleString(
            "pt-BR",
            {
              style: "currency",
              currency: "BRL",
            }
          );
        }

        if (diaria) {
          diaria.value = `${Math.max(
            parseFloat(data.diaria || "2.00"),
            1
          ).toFixed(0)}%`;
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

        // ✅ CARREGAR TIPO DE META DO BANCO DE DADOS
        if (data.meta) {
          if (data.meta === "Meta Turbo" && metaTurboRadio) {
            metaTurboRadio.checked = true;
            destacarMetaSelecionada("turbo");
          } else if (metaFixaRadio) {
            metaFixaRadio.checked = true;
            destacarMetaSelecionada("fixa");
          }
        } else {
          // Default para Meta Fixa se não houver valor no banco
          if (metaFixaRadio) {
            metaFixaRadio.checked = true;
            destacarMetaSelecionada("fixa");
          }
        }

        if (data.meta_diaria_formatada) {
          atualizarMetaDiaria(data.meta_diaria_formatada);
        }

        if (data.unidade_entrada_formatada) {
          atualizarUnidadeEntradaFormulario(data.unidade_entrada_formatada);
        }

        calcularMeta(valorOriginalBanca);
        setTimeout(() => atualizarAreaDireita(), 500);
      })
      .catch((error) => {
        console.error("Erro ao carregar dados iniciais:", error);
      });

    // ✅ EVENTOS DOS DROPDOWNS E BOTÕES
    const dropdownItems = modal.querySelectorAll(".dropdown-menu li");

    dropdownItems.forEach((item) => {
      item.addEventListener("click", () => {
        const tipo = item.getAttribute("data-value");
        if (acaoSelect) acaoSelect.value = tipo;

        if (valorBancaInput) {
          valorBancaInput.value = "";
          valorBancaInput.style.display = "block";
        }

        if (mensagemErro) mensagemErro.textContent = "";

        if (tipo === "add") {
          if (valorBancaInput) {
            valorBancaInput.placeholder =
              "Quanto quer Depositar na Banca R$ 0,00";
            valorBancaInput.disabled = false;
            valorBancaInput.classList.remove("desativado");
          }
          if (botaoAcao) botaoAcao.value = "Depositar na Banca";
        } else if (tipo === "sacar") {
          if (valorBancaInput) {
            valorBancaInput.placeholder = "Quanto Quer Sacar da Banca R$ 0,00";
            valorBancaInput.disabled = false;
            valorBancaInput.classList.remove("desativado");
          }
          if (botaoAcao) botaoAcao.value = "Sacar da Banca";
        } else if (tipo === "resetar") {
          if (valorBancaInput) {
            valorBancaInput.placeholder = "Essa ação irá zerar sua banca";
            valorBancaInput.disabled = true;
            valorBancaInput.classList.add("desativado");
          }
          if (botaoAcao) botaoAcao.value = "Resetar Banca";
        } else if (tipo === "alterar") {
          if (valorBancaInput) {
            valorBancaInput.placeholder = "Essa ação não requer valor";
            valorBancaInput.disabled = true;
            valorBancaInput.classList.add("desativado");
          }
          if (botaoAcao) botaoAcao.value = "Salvar Alteração";
        } else {
          if (valorBancaInput) {
            valorBancaInput.placeholder = "R$ 0,00";
            valorBancaInput.disabled = false;
            valorBancaInput.classList.remove("desativado");
          }
          if (botaoAcao) botaoAcao.value = "Cadastrar Dados";
        }
      });
    });

    // ✅ EVENTO INPUT DO VALOR BANCA
    if (valorBancaInput) {
      // ✅ CONFIGURAR MÁSCARA DE DINHEIRO
      valorBancaInput.addEventListener("input", function () {
        console.log("💰 Input detectado no campo valor banca");

        let valor = this.value.replace(/[^\d]/g, "");

        // ✅ BUSCAR ELEMENTOS DINAMICAMENTE PARA EVITAR ERRO
        const mensagemErro = document.getElementById("mensagemErro");
        const legendaBanca = document.getElementById("legendaBanca");
        const valorBancaLabel = document.getElementById("valorBancaLabel");
        const acaoSelect = document.getElementById("acaoBanca");

        // Se campo vazio, limpar tudo
        if (!valor || valor === "0") {
          this.value = "";
          if (mensagemErro) mensagemErro.textContent = "";
          if (legendaBanca) legendaBanca.style.display = "none";

          // Restaurar valor original na label
          if (valorBancaLabel) {
            valorBancaLabel.textContent = valorOriginalBanca.toLocaleString(
              "pt-BR",
              {
                style: "currency",
                currency: "BRL",
              }
            );
          }
          return;
        }

        // ✅ FORMATAR VALOR DIGITADO
        const valorDigitado = parseFloat(valor) / 100;
        this.value = valorDigitado.toLocaleString("pt-BR", {
          style: "currency",
          currency: "BRL",
        });

        // ✅ OBTER TIPO DE AÇÃO SELECIONADA
        const tipo = acaoSelect ? acaoSelect.value : "";

        console.log(
          `📊 Tipo de ação: ${tipo}, Valor digitado: ${valorDigitado}`
        );

        let valorAtualizado = valorOriginalBanca;
        let temErro = false;

        // ✅ CALCULAR BASEADO NO TIPO DE AÇÃO
        switch (tipo) {
          case "add":
            valorAtualizado = valorOriginalBanca + valorDigitado;
            if (mensagemErro) mensagemErro.textContent = "";
            break;

          case "sacar":
            valorAtualizado = valorOriginalBanca - valorDigitado;

            // ✅ VERIFICAR SALDO INSUFICIENTE
            if (valorDigitado > valorOriginalBanca) {
              if (mensagemErro)
                mensagemErro.textContent = "Saldo Insuficiente.";
              temErro = true;
            } else {
              if (mensagemErro) mensagemErro.textContent = "";
            }
            break;

          case "alterar":
            // Na alteração, não muda o valor da banca
            valorAtualizado = valorOriginalBanca;
            break;

          case "resetar":
            // No reset, não muda o valor da banca
            valorAtualizado = valorOriginalBanca;
            break;

          default:
            // Se não tem tipo selecionado e banca é 0, é cadastro inicial
            if (valorOriginalBanca === 0) {
              valorAtualizado = valorDigitado;
            }
            break;
        }

        // ✅ GARANTIR QUE VALOR NÃO SEJA NEGATIVO
        valorAtualizado = Math.max(0, valorAtualizado);

        // ✅ ATUALIZAR LABEL DO VALOR DA BANCA
        if (valorBancaLabel) {
          valorBancaLabel.textContent = valorAtualizado.toLocaleString(
            "pt-BR",
            {
              style: "currency",
              currency: "BRL",
            }
          );

          console.log(`💰 Banca atualizada para: ${valorAtualizado}`);
        }

        // ✅ MOSTRAR/OCULTAR LEGENDA BASEADO NO ERRO
        if (legendaBanca) {
          legendaBanca.style.display = temErro ? "none" : "block";
        }

        // ✅ RECALCULAR META COM VALOR ATUALIZADO
        if (typeof calcularMeta === "function") {
          calcularMeta(valorAtualizado);
        }
      });

      // ✅ EVENTO FOCUS PARA SELECIONAR TUDO
      valorBancaInput.addEventListener("focus", function () {
        this.select();
      });

      // ✅ EVENTO BLUR PARA VALIDAÇÃO FINAL
      valorBancaInput.addEventListener("blur", function () {
        if (!this.value || this.value === "R$ 0,00") {
          this.value = "";
        }
      });

      console.log("✅ Eventos do campo valor banca configurados");
    }

    // ✅ FUNÇÃO CALCULAR META INTERNA
    function calcularMeta(bancaFloat) {
      console.log(`🎯 Calculando meta para banca: ${bancaFloat}`);

      // ✅ BUSCAR ELEMENTOS DINAMICAMENTE
      const diaria = document.getElementById("porcentagem");
      const resultadoCalculo = document.getElementById("resultadoCalculo");

      const percentualRaw = diaria
        ? diaria.value.replace("%", "").replace(",", ".")
        : "2";
      const percentFloat = parseFloat(percentualRaw);

      if (isNaN(percentFloat) || percentFloat <= 0) {
        if (resultadoCalculo) resultadoCalculo.textContent = "";
        console.warn("⚠️ Percentual inválido");
        return;
      }

      // ✅ USAR A BANCA PASSADA COMO PARÂMETRO
      const baseCalculo = bancaFloat || 0;
      const unidadeEntrada = baseCalculo * (percentFloat / 100);

      // ✅ ATUALIZAR RESULTADO DA UNIDADE
      if (resultadoCalculo) {
        resultadoCalculo.textContent = `Unidade: ${unidadeEntrada.toLocaleString(
          "pt-BR",
          {
            style: "currency",
            currency: "BRL",
          }
        )}`;
      }

      // ✅ CALCULAR OUTRAS METAS SE AS FUNÇÕES EXISTIREM
      if (typeof calcularUnidade === "function") {
        calcularUnidade(unidadeEntrada);
      }
      if (typeof calcularOdds === "function") {
        calcularOdds(unidadeEntrada);
      }

      console.log(`✅ Meta calculada - Unidade: ${unidadeEntrada}`);
    }

    // ✅ EVENTOS DE DROPDOWN APRIMORADOS
    if (typeof modal !== "undefined" && modal) {
      const dropdownItems = modal.querySelectorAll(".dropdown-menu li");
      const dropdownToggle = modal.querySelector(".dropdown-toggle");

      dropdownItems.forEach((item) => {
        item.addEventListener("click", function () {
          const tipo = this.getAttribute("data-value");
          const texto = this.innerHTML;

          console.log(`🎯 Selecionado: ${tipo}`);

          // ✅ ATUALIZAR DROPDOWN
          if (dropdownToggle) {
            dropdownToggle.innerHTML =
              texto + ' <i class="fa-solid fa-chevron-down"></i>';
          }

          // ✅ ATUALIZAR CAMPO HIDDEN
          const acaoSelect = document.getElementById("acaoBanca");
          if (acaoSelect) acaoSelect.value = tipo;

          // ✅ CONFIGURAR CAMPO BASEADO NO TIPO
          const valorBancaInput = document.getElementById("valorBanca");
          const botaoAcao = document.getElementById("botaoAcao");
          const mensagemErro = document.getElementById("mensagemErro");

          if (valorBancaInput && botaoAcao) {
            // Limpar valor e erro
            valorBancaInput.value = "";
            if (mensagemErro) mensagemErro.textContent = "";

            switch (tipo) {
              case "add":
                valorBancaInput.placeholder =
                  "Quanto quer Depositar na Banca R$ 0,00";
                valorBancaInput.disabled = false;
                valorBancaInput.classList.remove("desativado");
                botaoAcao.value = "Depositar na Banca";
                break;

              case "sacar":
                valorBancaInput.placeholder =
                  "Quanto Quer Sacar da Banca R$ 0,00";
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

            // ✅ FOCAR NO CAMPO SE HABILITADO
            if (!valorBancaInput.disabled) {
              setTimeout(() => valorBancaInput.focus(), 100);
            }
          }
        });
      });

      console.log("✅ Eventos do dropdown configurados");
    }

    // ✅ EVENTO BOTÃO AÇÃO COM SUPORTE A META
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

        // ✅ VALIDAR SE TIPO DE META FOI SELECIONADO
        const tipoMeta = obterTipoMetaSelecionado();
        if (!tipoMeta) {
          exibirToast("⚠️ Selecione o tipo de meta (Fixa ou Turbo)", "aviso");
          // Destacar campos de meta
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

        const diariaRaw = diaria ? diaria.value.replace(/[^\d]/g, "") : "2";
        const unidadeRaw = unidade ? unidade.value.replace(/[^\d]/g, "") : "2";

        const diariaFloat = parseFloat(diariaRaw);
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

        // ✅ INCLUIR TIPO DE META NO ENVIO
        const dadosEnvio = {
          acao: acaoFinal,
          valor: valorNumerico.toFixed(2),
          diaria: diariaFloat,
          unidade: unidadeInt,
          odds: oddsValor,
          tipoMeta: tipoMeta, // ✅ NOVO CAMPO
        };

        console.log("📤 Enviando dados:", dadosEnvio);

        fetch("ajax_deposito.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(dadosEnvio),
        })
          .then((res) => res.json())
          .then((resposta) => {
            if (resposta.success) {
              // ✅ MENSAGEM PERSONALIZADA INCLUINDO TIPO DE META
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
              setTimeout(() => atualizarAreaDireita(), 300);

              const selectAcao = document.getElementById("selectAcao");
              const inputValor = document.getElementById("inputValor");

              if (selectAcao) selectAcao.value = "";
              if (inputValor) inputValor.value = "0,00";
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

    // ✅ EVENTOS DE CONFIRMAÇÃO DE RESET
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
              setTimeout(() => atualizarAreaDireita(), 300);

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

    // ✅ CONFIGURAR EVENTOS DOS RADIO BUTTONS DE META
    if (metaFixaRadio && metaTurboRadio) {
      metaFixaRadio.addEventListener("change", function () {
        if (this.checked) {
          console.log("✅ Meta Fixa selecionada");
          destacarMetaSelecionada("fixa");
        }
      });

      metaTurboRadio.addEventListener("change", function () {
        if (this.checked) {
          console.log("✅ Meta Turbo selecionada");
          destacarMetaSelecionada("turbo");
        }
      });

      console.log("✅ Eventos de meta configurados");
    }

    // ✅ FINALIZAÇÃO
    configurarEventosDeMeta();
    adicionarEventosLimpezaCampos();
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
      diaria.addEventListener("input", () => {
        diaria.value = diaria.value.replace(/[^0-9]/g, "");
        calcularMeta(valorOriginalBanca);
      });

      diaria.addEventListener("blur", () => {
        diaria.value = formatarPorcentagem(diaria.value);
        calcularMeta(valorOriginalBanca);
      });
    }

    if (unidade) {
      unidade.addEventListener("input", () => {
        unidade.value = unidade.value.replace(/\D/g, "");
        calcularMeta(valorOriginalBanca);
      });

      unidade.addEventListener("blur", () => {
        unidade.value = parseInt(unidade.value) || "";
        calcularMeta(valorOriginalBanca);
      });
    }

    if (oddsMeta) {
      oddsMeta.addEventListener("input", () => {
        calcularOdds(unidadeCalculada);
      });

      oddsMeta.addEventListener("blur", () => {
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

        if (diaria) {
          diaria.value = `${Math.max(
            parseFloat(data.diaria || "2.00"),
            1
          ).toFixed(0)}%`;
        }
        if (unidade) {
          unidade.value = parseInt(data.unidade || "2");
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
//
//
//
//
//
//
//
//
//
(function () {
  "use strict";

  let isUpdating = false;
  let updateTimeout = null;
  const ELEMENTO_ID = "meta-text-unico";

  function limparTodasAsMetas() {
    // ✅ LIMPAR APENAS DENTRO DO WIDGET META ESPECÍFICO
    const widgetMeta = document.getElementById("meta-valor");

    if (!widgetMeta) return;

    const elementosMetaNoWidget = widgetMeta.querySelectorAll(`
        [id*="meta-text"]:not(#${ELEMENTO_ID}), 
        .meta-text:not(#${ELEMENTO_ID}), 
        span[class*="meta"]:not(#${ELEMENTO_ID})
      `);

    elementosMetaNoWidget.forEach((el) => {
      if (el.id !== ELEMENTO_ID) {
        // ✅ REMOVER SEM ANIMAÇÃO
        el.style.transition = "none";
        el.style.opacity = "0";
        el.remove();
      }
    });

    console.log("✅ Limpeza realizada apenas no widget meta");
  }

  function encontrarElementoValor() {
    // ✅ BUSCA ESPECÍFICA APENAS NO WIDGET DA META
    const widgetMeta = document.getElementById("meta-valor");

    if (!widgetMeta) {
      console.warn("⚠️ Widget meta-valor não encontrado");
      return null;
    }

    // Buscar o elemento valor-texto dentro do widget específico
    const valorTexto =
      widgetMeta.querySelector(".valor-texto") ||
      widgetMeta.querySelector("#valor-texto-meta");

    if (valorTexto) {
      console.log(
        "✅ Elemento valor-texto encontrado no widget meta:",
        valorTexto.textContent
      );
      return valorTexto;
    }

    console.warn("⚠️ Elemento valor-texto não encontrado no widget meta");
    return null;
  }

  function inserirMetaUnica(tipoMeta = null) {
    if (isUpdating) {
      console.log("⏸️ Atualização em andamento, ignorando...");
      return;
    }

    isUpdating = true;
    console.log("🔄 Inserindo meta única...");

    if (updateTimeout) {
      clearTimeout(updateTimeout);
    }

    let metaElement = document.getElementById(ELEMENTO_ID);

    if (metaElement) {
      if (tipoMeta) {
        atualizarConteudoMeta(metaElement, tipoMeta);
      } else {
        buscarEAtualizarMeta(metaElement);
      }
      isUpdating = false;
      return;
    }

    limparTodasAsMetas();

    const elementoValor = encontrarElementoValor();

    if (!elementoValor) {
      console.warn("⚠️ Elemento de valor não encontrado");
      isUpdating = false;
      return;
    }

    // ✅ CRIAR ELEMENTO - CSS CUIDA DO POSICIONAMENTO
    const metaSpan = document.createElement("span");
    metaSpan.id = ELEMENTO_ID;
    metaSpan.className = "meta-text meta-fixa";
    metaSpan.textContent = "META FIXA";
    // ✅ SEM ESTILOS INLINE - APENAS CLASSES CSS

    // ✅ INSERIR NO WIDGET CONTAINER - CSS FAZ O RESTO
    const widget =
      elementoValor.closest('[class*="widget"]') ||
      elementoValor.closest(".container") ||
      elementoValor.parentElement;

    widget.appendChild(metaSpan);

    if (tipoMeta) {
      atualizarConteudoMeta(metaSpan, tipoMeta);
    } else {
      buscarEAtualizarMeta(metaSpan);
    }

    console.log("✅ Meta única criada - CSS controla posicionamento");
    isUpdating = false;
  }

  function atualizarConteudoMeta(elemento, tipoMeta) {
    const isturbo = tipoMeta === "Meta Turbo";

    // ✅ REMOVER TRANSIÇÕES DURANTE ATUALIZAÇÃO
    elemento.style.transition = "none";

    elemento.className = `meta-text meta-${isturbo ? "turbo" : "fixa"}`;
    elemento.textContent = isturbo ? "META TURBO" : "META FIXA";

    // ✅ RESTAURAR TRANSIÇÕES APÓS ATUALIZAÇÃO
    setTimeout(() => {
      elemento.style.transition = "";
    }, 50);

    console.log(`✅ Meta atualizada para: ${tipoMeta} (sem animação)`);
  }

  function buscarEAtualizarMeta(elemento) {
    fetch("ajax_deposito.php")
      .then((response) => response.json())
      .then((data) => {
        const metaFromServer =
          data.success && data.meta ? data.meta : "Meta Fixa";

        // ✅ SÓ ATUALIZAR SE FOR DIFERENTE DO ATUAL
        const isturboAtual = elemento.classList.contains("meta-turbo");
        const isturboNovo = metaFromServer === "Meta Turbo";

        if (isturboAtual !== isturboNovo) {
          atualizarConteudoMeta(elemento, metaFromServer);
        } else {
          console.log("✅ Meta já está correta, não precisa atualizar");
        }
      })
      .catch((error) => {
        console.log("Info: Mantendo meta atual por erro na busca");
        // ✅ NÃO ATUALIZAR EM CASO DE ERRO
      });
  }

  // Interceptação AJAX
  const originalFetch = window.fetch;
  window.fetch = function (...args) {
    return originalFetch.apply(this, args).then((response) => {
      if (args[0] === "ajax_deposito.php" && response.ok) {
        const clonedResponse = response.clone();
        clonedResponse
          .json()
          .then((data) => {
            if (data.success && data.meta) {
              console.log("🔄 AJAX detectado, atualizando meta:", data.meta);
              updateTimeout = setTimeout(() => {
                inserirMetaUnica(data.meta);
              }, 300);
            }
          })
          .catch(() => {});
      }
      return response;
    });
  };

  // Funções globais
  window.atualizarTextoMeta = (tipoMeta) => {
    updateTimeout = setTimeout(() => {
      inserirMetaUnica(tipoMeta);
    }, 50);
  };

  window.forcarAtualizacaoMeta = () => {
    updateTimeout = setTimeout(() => {
      inserirMetaUnica();
    }, 50);
  };

  window.limparTodasAsMetas = limparTodasAsMetas;

  // Inicialização junto com o DOM
  function inicializar() {
    function executarMeta() {
      const elementoValor = encontrarElementoValor();
      if (elementoValor) {
        inserirMetaUnica();
      } else {
        setTimeout(() => {
          const elemento = encontrarElementoValor();
          if (elemento) {
            inserirMetaUnica();
          } else {
            console.log("⚠️ Elemento não encontrado após tentativas");
          }
        }, 500);
      }
    }

    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", executarMeta);
    } else if (document.readyState === "interactive") {
      executarMeta();
    } else {
      executarMeta();
    }
  }

  // Observador para mudanças no DOM
  let observer;

  function iniciarObservador() {
    if (observer) return;

    observer = new MutationObserver((mutations) => {
      let shouldUpdate = false;

      mutations.forEach((mutation) => {
        if (mutation.type === "childList" && mutation.addedNodes.length > 0) {
          mutation.addedNodes.forEach((node) => {
            if (
              node.nodeType === Node.ELEMENT_NODE &&
              node.textContent &&
              node.textContent.includes("R$")
            ) {
              shouldUpdate = true;
            }
          });
        }
      });

      if (shouldUpdate && !isUpdating) {
        clearTimeout(updateTimeout);
        updateTimeout = setTimeout(inserirMetaUnica, 100);
      }
    });

    observer.observe(document.body, {
      childList: true,
      subtree: true,
    });
  }

  // Inicializar sistema
  inicializar();

  // Iniciar observador
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", iniciarObservador);
  } else {
    iniciarObservador();
  }

  console.log(
    "✅ Sistema de meta otimizado carregado - CSS controla posicionamento!"
  );
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
//========================================================================================================================
//                           CALCULO DE VALOR DO PAINEL DE CONTROLE PARA EXIBIR METAS
// ========================================================================================================================

const CalculadoraModal = {
  // ✅ CONTROLE DE ESTADO
  calculandoAtualmente: false,
  banca_inicial: 0.0,
  lucro_atual: 0.0,
  tipoMetaSelecionado: "fixa",
  dadosCarregados: false,
  pollingInterval: null,

  // ✅ INICIALIZAR O SISTEMA
  async inicializar() {
    try {
      console.log("🚀 Inicializando Sistema Integrado...");

      // ✅ AGUARDAR CARREGAMENTO DOS DADOS
      await this.carregarDadosBanca();

      this.configurarEventosInputs();
      this.configurarEventosTipoMeta();

      // ✅ INTEGRAR COM O SISTEMA DE ATUALIZAÇÃO AUTOMÁTICA
      this.integrarComSistemaAtualizacao();

      // ✅ SÓ CALCULAR APÓS CARREGAR OS DADOS
      if (this.dadosCarregados) {
        this.calcularTodosValores();
      } else {
        console.warn("⚠️ Dados não carregados - exibindo valores zerados");
        this.exibirValoresZerados();
      }

      console.log("✅ Sistema Integrado inicializado!");
    } catch (error) {
      console.error("❌ Erro ao inicializar:", error);
      this.exibirValoresZerados();
    }
  },

  // ✅ NOVA FUNÇÃO: INTEGRAR COM SISTEMA DE ATUALIZAÇÃO AUTOMÁTICA
  integrarComSistemaAtualizacao() {
    try {
      console.log("🔗 Integrando com sistema de atualização automática...");

      // ✅ 1. INTERCEPTAR A FUNÇÃO executarAtualizacaoImediata EXISTENTE
      if (typeof window.executarAtualizacaoImediata === "function") {
        const funcaoOriginal = window.executarAtualizacaoImediata;
        window.executarAtualizacaoImediata = (
          tipoOperacao,
          resultado = null
        ) => {
          // Executar a função original
          funcaoOriginal(tipoOperacao, resultado);

          // Adicionar nossa atualização da calculadora
          console.log(`🧮 Atualizando calculadora após: ${tipoOperacao}`);
          setTimeout(() => {
            this.recarregarDados();
          }, 500);
        };
        console.log("✅ Função executarAtualizacaoImediata interceptada");
      }

      // ✅ 2. INTERCEPTAR BOTÕES DO MODAL ESPECIFICAMENTE
      this.interceptarBotaoModal();

      // ✅ 3. ESCUTAR EVENTOS CUSTOMIZADOS EXISTENTES
      this.escutarEventosCustomizados();

      // ✅ 4. POLLING COMO BACKUP
      this.iniciarPolling();

      console.log("✅ Integração completa configurada");
    } catch (error) {
      console.error("❌ Erro na integração:", error);
    }
  },

  // ✅ INTERCEPTAR ESPECIFICAMENTE O BOTÃO DO MODAL
  interceptarBotaoModal() {
    try {
      // ✅ USAR EVENT DELEGATION NO DOCUMENTO TODO
      document.addEventListener("click", (event) => {
        const target = event.target;

        // ✅ DETECTAR ESPECIFICAMENTE O BOTÃO DO MODAL DE BANCA
        const isModalBancaButton =
          target.id === "botaoAcao" ||
          ((target.type === "button" || target.type === "submit") &&
            target.closest("#modalDeposito")) ||
          target.closest(".modal-content");

        if (isModalBancaButton) {
          console.log("🎯 CLIQUE NO BOTÃO DO MODAL DETECTADO!");
          console.log("   Botão:", target);
          console.log("   ID:", target.id);
          console.log("   Valor:", target.value);

          // ✅ MÚLTIPLAS TENTATIVAS DE ATUALIZAÇÃO
          setTimeout(() => {
            console.log("🔄 Tentativa 1 - Recarregando calculadora...");
            this.recarregarDados();
          }, 800);

          setTimeout(() => {
            console.log("🔄 Tentativa 2 - Recarregando calculadora...");
            this.recarregarDados();
          }, 1500);

          setTimeout(() => {
            console.log("🔄 Tentativa 3 - Recarregando calculadora...");
            this.recarregarDados();
          }, 2500);
        }
      });

      console.log("✅ Interceptação do botão modal configurada");
    } catch (error) {
      console.error("❌ Erro ao interceptar botão modal:", error);
    }
  },

  // ✅ ESCUTAR EVENTOS CUSTOMIZADOS DO SISTEMA EXISTENTE
  escutarEventosCustomizados() {
    try {
      // ✅ EVENTO bancaAtualizada
      document.addEventListener("bancaAtualizada", () => {
        console.log(
          "📢 Evento bancaAtualizada recebido - atualizando calculadora"
        );
        setTimeout(() => this.recarregarDados(), 200);
      });

      // ✅ EVENTO areaAtualizacao
      document.addEventListener("areaAtualizacao", (event) => {
        console.log(
          "📢 Evento areaAtualizacao recebido - atualizando calculadora",
          event.detail
        );
        setTimeout(() => this.recarregarDados(), 300);
      });

      // ✅ EVENTO mentorCadastrado
      document.addEventListener("mentorCadastrado", () => {
        console.log(
          "📢 Evento mentorCadastrado recebido - atualizando calculadora"
        );
        setTimeout(() => this.recarregarDados(), 400);
      });

      console.log("✅ Eventos customizados configurados");
    } catch (error) {
      console.error("❌ Erro ao configurar eventos customizados:", error);
    }
  },

  // ✅ POLLING COMO BACKUP
  iniciarPolling() {
    try {
      // ✅ VERIFICAR MUDANÇAS A CADA 3 SEGUNDOS
      this.pollingInterval = setInterval(() => {
        if (this.dadosCarregados) {
          this.verificarMudancasSilenciosa();
        }
      }, 3000);

      console.log("⏰ Polling de backup iniciado");
    } catch (error) {
      console.error("❌ Erro ao iniciar polling:", error);
    }
  },

  // ✅ VERIFICAR MUDANÇAS SILENCIOSA (SEM LOGS EXCESSIVOS)
  async verificarMudancasSilenciosa() {
    try {
      const response = await fetch("dados_banca.php", {
        method: "GET",
        headers: {
          "Cache-Control": "no-cache",
          "X-Requested-With": "XMLHttpRequest",
        },
      });

      if (!response.ok) return;

      const data = await response.json();

      if (data.success) {
        const novaBanca = parseFloat(data.banca_inicial) || 0.0;
        const novoLucro = parseFloat(data.lucro_total_display) || 0.0;

        // ✅ VERIFICAR SE HOUVE MUDANÇA
        const mudancaBanca = Math.abs(novaBanca - this.banca_inicial) > 0.01;
        const mudancaLucro = Math.abs(novoLucro - this.lucro_atual) > 0.01;

        if (mudancaBanca || mudancaLucro) {
          console.log(
            "🔄 MUDANÇA DETECTADA pelo polling - atualizando calculadora"
          );
          await this.recarregarDados();
        }
      }
    } catch (error) {
      // Silencioso para não poluir console
    }
  },

  // ✅ CARREGAR DADOS ATUAIS DA BANCA
  async carregarDadosBanca() {
    try {
      const response = await fetch("dados_banca.php", {
        method: "GET",
        headers: {
          "Cache-Control": "no-cache",
          "X-Requested-With": "XMLHttpRequest",
        },
      });

      if (!response.ok) throw new Error(`HTTP ${response.status}`);

      const data = await response.json();

      if (data.success) {
        // ✅ USAR OS VALORES REAIS OU ZERO SE NÃO EXISTIREM
        this.banca_inicial = parseFloat(data.banca_inicial) || 0.0;
        this.lucro_atual = parseFloat(data.lucro_total_display) || 0.0;
        this.dadosCarregados = true;

        const valorBancaLabel = document.getElementById("valorBancaLabel");
        const valorLucroLabel = document.getElementById("valorLucroLabel");

        if (valorBancaLabel) {
          valorBancaLabel.textContent = data.banca_formatada || "R$ 0,00";
        }

        if (valorLucroLabel) {
          valorLucroLabel.textContent = data.lucro_total_formatado || "R$ 0,00";
        }

        console.log(
          `📊 Dados carregados - Banca: R$ ${this.banca_inicial.toFixed(
            2
          )}, Lucro: R$ ${this.lucro_atual.toFixed(2)}`
        );
      } else {
        console.warn(
          "⚠️ Response não foi successful - mantendo valores zerados"
        );
        this.exibirValoresZerados();
      }
    } catch (error) {
      console.error("❌ Erro ao carregar dados da banca:", error);
      this.banca_inicial = 0.0;
      this.lucro_atual = 0.0;
      this.dadosCarregados = false;
      this.exibirValoresZerados();
    }
  },

  // ✅ NOVA FUNÇÃO: EXIBIR VALORES ZERADOS
  exibirValoresZerados() {
    try {
      const valorBancaLabel = document.getElementById("valorBancaLabel");
      const valorLucroLabel = document.getElementById("valorLucroLabel");

      if (valorBancaLabel) {
        valorBancaLabel.textContent = "R$ 0,00";
      }

      if (valorLucroLabel) {
        valorLucroLabel.textContent = "R$ 0,00";
      }

      // ✅ ZERAR TODOS OS RESULTADOS
      this.atualizarDisplays({
        unidadeEntrada: 0,
        metaDiaria: 0,
        metaMensal: 0,
        metaAnual: 0,
        entradasPositivas: 0,
      });

      console.log("💤 Valores zerados exibidos");
    } catch (error) {
      console.error("❌ Erro ao exibir valores zerados:", error);
    }
  },

  // ✅ CONFIGURAR EVENTOS DOS INPUTS
  configurarEventosInputs() {
    try {
      const inputs = ["porcentagem", "unidadeMeta", "oddsMeta"];

      inputs.forEach((inputId) => {
        const input = document.getElementById(inputId);
        if (input) {
          input.addEventListener("input", () => this.calcularTodosValores());
          input.addEventListener("change", () => this.calcularTodosValores());
          input.addEventListener("blur", () => this.calcularTodosValores());

          console.log(`✅ Eventos configurados para: ${inputId}`);
        } else {
          console.warn(`⚠️ Input não encontrado: ${inputId}`);
        }
      });
    } catch (error) {
      console.error("❌ Erro ao configurar eventos dos inputs:", error);
    }
  },

  // ✅ CONFIGURAR EVENTOS DO TIPO DE META
  configurarEventosTipoMeta() {
    try {
      const radioFixa = document.getElementById("metaFixa");
      const radioTurbo = document.getElementById("metaTurbo");

      if (radioFixa) {
        radioFixa.addEventListener("change", () => {
          if (radioFixa.checked) {
            this.tipoMetaSelecionado = "fixa";
            this.calcularTodosValores();
          }
        });
      }

      if (radioTurbo) {
        radioTurbo.addEventListener("change", () => {
          if (radioTurbo.checked) {
            this.tipoMetaSelecionado = "turbo";
            this.calcularTodosValores();
          }
        });
      }

      if (radioFixa && radioFixa.checked) {
        this.tipoMetaSelecionado = "fixa";
      } else if (radioTurbo && radioTurbo.checked) {
        this.tipoMetaSelecionado = "turbo";
      }

      console.log(`✅ Tipo de meta inicial: ${this.tipoMetaSelecionado}`);
    } catch (error) {
      console.error("❌ Erro ao configurar eventos tipo de meta:", error);
    }
  },

  // ✅ OBTER VALORES DOS INPUTS COM VALIDAÇÃO - SEM VALORES PADRÃO
  obterValoresInputs() {
    try {
      const inputPorcentagem = document.getElementById("porcentagem");
      let porcentagem = 0;

      if (inputPorcentagem && inputPorcentagem.value) {
        const valorLimpo = inputPorcentagem.value
          .replace(/[^\d.,]/g, "")
          .replace(",", ".");
        porcentagem = parseFloat(valorLimpo) || 0;
      }

      const inputUnidade = document.getElementById("unidadeMeta");
      let unidade = 0;

      if (inputUnidade && inputUnidade.value) {
        unidade = parseInt(inputUnidade.value) || 0;
      }

      const inputOdds = document.getElementById("oddsMeta");
      let odds = 0;

      if (inputOdds && inputOdds.value) {
        const valorLimpo = inputOdds.value.replace(",", ".");
        odds = parseFloat(valorLimpo) || 0;
      }

      // ✅ SE QUALQUER VALOR FOR ZERO OU INVÁLIDO, RETORNAR TUDO ZERO
      if (porcentagem <= 0 || unidade <= 0 || odds <= 0) {
        console.log("⚠️ Inputs vazios ou inválidos - retornando valores zero");
        return {
          porcentagem: 0,
          unidade: 0,
          odds: 0,
        };
      }

      return {
        porcentagem: porcentagem,
        unidade: unidade,
        odds: odds,
      };
    } catch (error) {
      console.error("❌ Erro ao obter valores dos inputs:", error);
      return {
        porcentagem: 0,
        unidade: 0,
        odds: 0,
      };
    }
  },

  // ✅ CALCULAR UNIDADE DE ENTRADA
  calcularUnidadeEntrada(valores) {
    try {
      if (
        !this.dadosCarregados ||
        this.banca_inicial <= 0 ||
        valores.porcentagem <= 0
      ) {
        return 0;
      }

      const bancaBase = this.banca_inicial;
      const porcentagemDecimal = valores.porcentagem / 100;
      const unidadeEntrada = bancaBase * porcentagemDecimal;

      return unidadeEntrada;
    } catch (error) {
      console.error("❌ Erro ao calcular unidade de entrada:", error);
      return 0;
    }
  },

  // ✅ CALCULAR META DIÁRIA - COM LÓGICA DE RECUPERAÇÃO DE PREJUÍZO
  calcularMetaDiaria(valores) {
    try {
      if (
        !this.dadosCarregados ||
        this.banca_inicial <= 0 ||
        valores.porcentagem <= 0 ||
        valores.unidade <= 0
      ) {
        return 0;
      }

      const porcentagemDecimal = valores.porcentagem / 100;
      let baseCalculo = 0;
      let metaOriginal = 0;
      let ajustePrejuizo = 0;

      // ✅ CALCULAR META ORIGINAL (sempre baseada na banca inicial)
      metaOriginal = this.banca_inicial * porcentagemDecimal * valores.unidade;

      // ✅ VERIFICAR SE HÁ PREJUÍZO
      if (this.lucro_atual < 0) {
        // ✅ PREJUÍZO: Meta = Meta Original + Valor do Prejuízo
        ajustePrejuizo = Math.abs(this.lucro_atual); // Converte negativo para positivo
        const metaComRecuperacao = metaOriginal + ajustePrejuizo;

        console.log(`💔 PREJUÍZO DETECTADO:`);
        console.log(`   Meta Original: R$ ${metaOriginal.toFixed(2)}`);
        console.log(`   Prejuízo: R$ ${ajustePrejuizo.toFixed(2)}`);
        console.log(
          `   Meta + Recuperação: R$ ${metaComRecuperacao.toFixed(2)}`
        );

        return metaComRecuperacao;
      } else if (this.lucro_atual === 0) {
        // ✅ NEUTRO: Apenas a meta original
        console.log(
          `⚖️ LUCRO NEUTRO - Meta Original: R$ ${metaOriginal.toFixed(2)}`
        );
        return metaOriginal;
      } else {
        // ✅ LUCRO POSITIVO: Aplicar lógica de Meta Fixa vs Turbo
        if (this.tipoMetaSelecionado === "fixa") {
          // Meta Fixa: sempre usa banca inicial (meta original)
          console.log(
            `📈 LUCRO POSITIVO - Meta Fixa: R$ ${metaOriginal.toFixed(2)}`
          );
          return metaOriginal;
        } else {
          // Meta Turbo: usa banca atual (inicial + lucro)
          baseCalculo = this.banca_inicial + this.lucro_atual;
          const metaTurbo = baseCalculo * porcentagemDecimal * valores.unidade;

          console.log(`🚀 LUCRO POSITIVO - Meta Turbo:`);
          console.log(`   Banca Atual: R$ ${baseCalculo.toFixed(2)}`);
          console.log(`   Meta Turbo: R$ ${metaTurbo.toFixed(2)}`);

          return metaTurbo;
        }
      }
    } catch (error) {
      console.error("❌ Erro ao calcular meta diária:", error);
      return 0;
    }
  },

  // ✅ CALCULAR DIAS RESTANTES
  calcularDiasRestantes() {
    try {
      const hoje = new Date();
      const ultimoDiaMes = new Date(
        hoje.getFullYear(),
        hoje.getMonth() + 1,
        0
      ).getDate();
      const diaAtual = hoje.getDate();
      const diasRestantesMes = ultimoDiaMes - diaAtual + 1;

      const fimAno = new Date(hoje.getFullYear(), 11, 31);
      const diferenca = Math.ceil((fimAno - hoje) / (1000 * 60 * 60 * 24)) + 1;

      return {
        mes: diasRestantesMes,
        ano: diferenca,
      };
    } catch (error) {
      return { mes: 30, ano: 365 };
    }
  },

  // ✅ CALCULAR METAS DE PERÍODO
  calcularMetasPeriodo(metaDiaria) {
    try {
      const diasRestantes = this.calcularDiasRestantes();
      const metaMensal = metaDiaria * diasRestantes.mes;
      const metaAnual = metaDiaria * diasRestantes.ano;

      return {
        metaMensal: metaMensal,
        metaAnual: metaAnual,
        diasMes: diasRestantes.mes,
        diasAno: diasRestantes.ano,
      };
    } catch (error) {
      return {
        metaMensal: 0,
        metaAnual: 0,
        diasMes: 30,
        diasAno: 365,
      };
    }
  },

  // ✅ CALCULAR ENTRADAS POSITIVAS NECESSÁRIAS
  calcularEntradasPositivas(valores, metaDiaria) {
    try {
      if (
        !this.dadosCarregados ||
        this.banca_inicial <= 0 ||
        metaDiaria <= 0 ||
        valores.porcentagem <= 0 ||
        valores.unidade <= 0 ||
        valores.odds <= 0
      ) {
        return 0;
      }

      const unidadeEntrada = this.calcularUnidadeEntrada(valores);
      if (unidadeEntrada <= 0) return 0;

      const lucroPorEntrada = unidadeEntrada * valores.odds - unidadeEntrada;
      if (lucroPorEntrada <= 0) return 0;

      const entradasNecessarias = Math.ceil(metaDiaria / lucroPorEntrada);
      return entradasNecessarias;
    } catch (error) {
      return 0;
    }
  },

  // ✅ ATUALIZAR DISPLAYS NO MODAL
  atualizarDisplays(resultados) {
    try {
      const elementos = {
        resultadoUnidadeEntrada: resultados.unidadeEntrada,
        resultadoMetaDia: resultados.metaDiaria,
        resultadoMetaMes: resultados.metaMensal,
        resultadoMetaAno: resultados.metaAnual,
      };

      Object.keys(elementos).forEach((id) => {
        const elemento = document.getElementById(id);
        if (elemento) {
          elemento.textContent = this.formatarMoeda(elementos[id]);
        }
      });

      const resultadoEntradas = document.getElementById("resultadoEntradas");
      if (resultadoEntradas) {
        const textoEntradas =
          resultados.entradasPositivas === 1
            ? "1 Entrada Positiva"
            : `${resultados.entradasPositivas} Entradas Positivas`;
        resultadoEntradas.textContent = textoEntradas;
      }

      console.log("✅ Displays atualizados no modal");
    } catch (error) {
      console.error("❌ Erro ao atualizar displays:", error);
    }
  },

  // ✅ FUNÇÃO PRINCIPAL - CALCULAR TODOS OS VALORES
  calcularTodosValores() {
    if (this.calculandoAtualmente) return;

    this.calculandoAtualmente = true;

    try {
      if (!this.dadosCarregados || this.banca_inicial <= 0) {
        this.exibirValoresZerados();
        return;
      }

      const valores = this.obterValoresInputs();

      if (
        valores.porcentagem <= 0 ||
        valores.unidade <= 0 ||
        valores.odds <= 0
      ) {
        this.exibirValoresZerados();
        return;
      }

      const unidadeEntrada = this.calcularUnidadeEntrada(valores);
      const metaDiaria = this.calcularMetaDiaria(valores);
      const metasPeriodo = this.calcularMetasPeriodo(metaDiaria);
      const entradasPositivas = this.calcularEntradasPositivas(
        valores,
        metaDiaria
      );

      const resultados = {
        unidadeEntrada: unidadeEntrada,
        metaDiaria: metaDiaria,
        metaMensal: metasPeriodo.metaMensal,
        metaAnual: metasPeriodo.metaAnual,
        entradasPositivas: entradasPositivas,
      };

      this.atualizarDisplays(resultados);

      console.log("📊 Cálculos realizados:", {
        inputs: valores,
        tipoMeta: this.tipoMetaSelecionado,
        bancaInicial: this.banca_inicial,
        lucroAtual: this.lucro_atual,
        resultados: resultados,
      });
    } catch (error) {
      console.error("❌ Erro nos cálculos:", error);
      this.exibirValoresZerados();
    } finally {
      this.calculandoAtualmente = false;
    }
  },

  // ✅ FORMATAR VALORES MONETÁRIOS
  formatarMoeda(valor) {
    try {
      return valor.toLocaleString("pt-BR", {
        style: "currency",
        currency: "BRL",
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      });
    } catch (error) {
      return "R$ 0,00";
    }
  },

  // ✅ NOVA FUNÇÃO: RECARREGAR DADOS E RECALCULAR
  async recarregarDados() {
    try {
      console.log("🔄 Recarregando dados da calculadora...");

      this.dadosCarregados = false;
      this.banca_inicial = 0.0;
      this.lucro_atual = 0.0;

      await this.carregarDadosBanca();

      if (this.dadosCarregados) {
        this.calcularTodosValores();
        console.log("✅ Calculadora atualizada com sucesso!");
      } else {
        this.exibirValoresZerados();
        console.log("⚠️ Não foi possível recarregar os dados");
      }

      return this.dadosCarregados;
    } catch (error) {
      console.error("❌ Erro ao recarregar dados:", error);
      this.exibirValoresZerados();
      return false;
    }
  },

  // ✅ FUNÇÃO PARA ALTERNAR TIPO DE META
  alternarTipoMeta(tipo = null) {
    try {
      if (tipo === null) {
        tipo = this.tipoMetaSelecionado === "fixa" ? "turbo" : "fixa";
      }

      const radioFixa = document.getElementById("metaFixa");
      const radioTurbo = document.getElementById("metaTurbo");

      if (tipo === "fixa" && radioFixa) {
        radioFixa.checked = true;
        this.tipoMetaSelecionado = "fixa";
      } else if (tipo === "turbo" && radioTurbo) {
        radioTurbo.checked = true;
        this.tipoMetaSelecionado = "turbo";
      }

      this.calcularTodosValores();

      console.log(`🔄 Tipo de meta alterado para: ${this.tipoMetaSelecionado}`);
      return `✅ Tipo alterado para: ${this.tipoMetaSelecionado}`;
    } catch (error) {
      console.error("❌ Erro ao alternar tipo de meta:", error);
      return "❌ Erro ao alternar tipo!";
    }
  },

  // ✅ NOVA FUNÇÃO: SIMULAR DIFERENTES CENÁRIOS PARA TESTE
  // ✅ NOVA FUNÇÃO: SIMULAR DIFERENTES CENÁRIOS PARA TESTE
  simularCenarios() {
    console.log("🧪 SIMULANDO DIFERENTES CENÁRIOS:");
    console.log("================================");

    // Cenário 1: Banca inicial
    console.log("📊 CENÁRIO 1 - SITUAÇÃO INICIAL:");
    console.log(`   Banca: R$ ${this.banca_inicial.toFixed(2)}`);
    console.log(`   Lucro: R$ ${this.lucro_atual.toFixed(2)}`);

    const valores = { porcentagem: 2, unidade: 2, odds: 1.7 };
    const meta = this.calcularMetaDiaria(valores);
    console.log(`   Meta Calculada: R$ ${meta.toFixed(2)}`);
    console.log("");

    // Cenário 2: Simular prejuízo
    console.log("📊 CENÁRIO 2 - COM PREJUÍZO:");
    const lucroOriginal = this.lucro_atual;
    this.lucro_atual = -100; // Simular perda de R$ 100

    console.log(`   Banca: R$ ${this.banca_inicial.toFixed(2)}`);
    console.log(`   Lucro: R$ ${this.lucro_atual.toFixed(2)} (PREJUÍZO)`);

    const metaPrejuizo = this.calcularMetaDiaria(valores);
    console.log(`   Meta com Recuperação: R$ ${metaPrejuizo.toFixed(2)}`);
    console.log("");

    // Cenário 3: Simular lucro com meta fixa
    console.log("📊 CENÁRIO 3 - LUCRO + META FIXA:");
    this.lucro_atual = 150; // Simular lucro de R$ 150
    this.tipoMetaSelecionado = "fixa";

    console.log(`   Banca: R$ ${this.banca_inicial.toFixed(2)}`);
    console.log(`   Lucro: R$ ${this.lucro_atual.toFixed(2)}`);
    console.log(`   Tipo: Meta Fixa`);

    const metaFixa = this.calcularMetaDiaria(valores);
    console.log(`   Meta Fixa: R$ ${metaFixa.toFixed(2)}`);
    console.log("");

    // Cenário 4: Simular lucro com meta turbo
    console.log("📊 CENÁRIO 4 - LUCRO + META TURBO:");
    this.tipoMetaSelecionado = "turbo";

    console.log(`   Banca: R$ ${this.banca_inicial.toFixed(2)}`);
    console.log(`   Lucro: R$ ${this.lucro_atual.toFixed(2)}`);
    console.log(`   Tipo: Meta Turbo`);

    const metaTurbo = this.calcularMetaDiaria(valores);
    console.log(`   Meta Turbo: R$ ${metaTurbo.toFixed(2)}`);
    console.log("");

    // Restaurar valores originais
    this.lucro_atual = lucroOriginal;
    this.tipoMetaSelecionado = "fixa";

    console.log("✅ Simulação completa! Valores originais restaurados.");
    console.log("================================");

    return {
      inicial: meta,
      prejuizo: metaPrejuizo,
      fixa: metaFixa,
      turbo: metaTurbo,
    };
  },

  // ✅ PARAR POLLING
  pararPolling() {
    if (this.pollingInterval) {
      clearInterval(this.pollingInterval);
      this.pollingInterval = null;
      console.log("⏹️ Polling parado");
    }
  },
};

// ========================================
// 🎮 ATALHOS GLOBAIS - MOVIDO PARA APÓS A DECLARAÇÃO
// ========================================

window.calc = {
  init: function () {
    return CalculadoraModal.inicializar();
  },
  reload: function () {
    return CalculadoraModal.recarregarDados();
  },
  fixa: function () {
    return CalculadoraModal.alternarTipoMeta("fixa");
  },
  turbo: function () {
    return CalculadoraModal.alternarTipoMeta("turbo");
  },
  toggle: function () {
    return CalculadoraModal.alternarTipoMeta();
  },
  recalc: function () {
    return CalculadoraModal.calcularTodosValores();
  },
  status: function () {
    console.log("📊 STATUS ATUAL:");
    console.log(`   Dados Carregados: ${CalculadoraModal.dadosCarregados}`);
    console.log(
      `   Banca Inicial: R$ ${CalculadoraModal.banca_inicial.toFixed(2)}`
    );
    console.log(
      `   Lucro Atual: R$ ${CalculadoraModal.lucro_atual.toFixed(2)}`
    );
    console.log(`   Tipo Meta: ${CalculadoraModal.tipoMetaSelecionado}`);

    const valores = CalculadoraModal.obterValoresInputs();
    console.log("📝 INPUTS ATUAIS:");
    console.log(`   Porcentagem: ${valores.porcentagem}%`);
    console.log(`   Unidade: ${valores.unidade}`);
    console.log(`   Odds: ${valores.odds}`);

    return "✅ Status exibido no console";
  },
  parar: function () {
    return CalculadoraModal.pararPolling();
  },
  // ✅ NOVA FUNÇÃO: TESTAR INTEGRAÇÃO
  testar: function () {
    console.log("🧪 TESTANDO INTEGRAÇÃO:");

    // Simular evento de atualização
    document.dispatchEvent(
      new CustomEvent("bancaAtualizada", {
        detail: { teste: true },
      })
    );

    console.log("📢 Evento bancaAtualizada disparado");
    return "🧪 Teste de integração executado";
  },
  // ✅ NOVA FUNÇÃO: SIMULAR CENÁRIOS
  simular: function () {
    return CalculadoraModal.simularCenarios();
  },
};

// ========================================
// ⚡ INICIALIZAÇÃO AUTOMÁTICA
// ========================================

document.addEventListener("DOMContentLoaded", function () {
  setTimeout(function () {
    try {
      CalculadoraModal.inicializar();
    } catch (error) {
      console.error("❌ Erro na inicialização automática:", error);
    }
  }, 1500);
});

// ========================================
// 📱 LOGS DE INICIALIZAÇÃO
// ========================================

console.log("✅ Sistema Integrado com Lógica de Recuperação carregado!");
console.log("📱 Comandos disponíveis:");
console.log("  calc.init() - Inicializar sistema");
console.log("  calc.reload() - Recarregar dados da banca");
console.log("  calc.status() - Ver status atual e valores dos inputs");
console.log(
  "  calc.simular() - Simular diferentes cenários (inicial/prejuízo/fixa/turbo)"
);
console.log("  calc.testar() - Testar integração com sistema de atualização");
console.log("  calc.fixa() - Alterar para Meta Fixa");
console.log("  calc.turbo() - Alterar para Meta Turbo");
console.log("  calc.toggle() - Alternar tipo de meta");
console.log("  calc.recalc() - Recalcular valores");
console.log("  calc.parar() - Parar polling");
console.log("");
console.log("💡 LÓGICA DE RECUPERAÇÃO DE PREJUÍZO:");
console.log("   • Prejuízo: Meta = Meta Original + Valor Perdido");
console.log("   • Neutro: Meta = Meta Original");
console.log(
  "   • Lucro + Fixa: Meta = Meta Original (baseada na banca inicial)"
);
console.log("   • Lucro + Turbo: Meta = Nova Meta (baseada na banca atual)");

// ✅ EXPORTAR PARA USO EXTERNO
window.CalculadoraModal = CalculadoraModal;

//========================================================================================================================
//                             FIM CALCULO DE VALOR DO PAINEL DE CONTROLE PARA EXIBIR METAS
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
