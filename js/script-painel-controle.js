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

  function inicializarModalDeposito() {
    if (modalInicializado || !modal) return;
    modalInicializado = true;

    valorBancaInput = modal.querySelector("#valorBanca");
    const valorBancaLabel = modal.querySelector("#valorBancaLabel");
    diaria = modal.querySelector("#porcentagem");
    unidade = modal.querySelector("#unidadeMeta");
    resultadoCalculo = modal.querySelector("#resultadoCalculo");
    resultadoUnidade = modal.querySelector("#resultadoUnidade");
    resultadoOdds = modal.querySelector("#resultadoOdds");
    oddsMeta = modal.querySelector("#oddsMeta");

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

    if (diaria) selecionarAoClicar(diaria);
    if (unidade) selecionarAoClicar(unidade);
    if (oddsMeta) selecionarAoClicar(oddsMeta);

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

    // ✅ CARREGAMENTO INICIAL
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
    // ✅ EVENTO INPUT DO VALOR BANCA - VERSÃO CORRIGIDA SEM ERROS
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

    // ✅ FUNÇÃO CALCULAR META TAMBÉM PRECISA SER CORRIGIDA
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

    // ✅ EVENTOS DE DROPDOWN CORRIGIDOS (SE NECESSÁRIO)
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

    // ✅ EVENTO BOTÃO AÇÃO MODIFICADO PARA ATUALIZAR ÁREA DIREITA
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

        fetch("ajax_deposito.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            acao: acaoFinal,
            valor: valorNumerico.toFixed(2),
            diaria: diariaFloat,
            unidade: unidadeInt,
            odds: oddsValor,
          }),
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

    configurarEventosDeMeta();
    adicionarEventosLimpezaCampos();
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
