document.addEventListener("DOMContentLoaded", () => {
  atualizarLucroEBancaViaAjax();

  const botaoGerencia = document.getElementById("abrirGerenciaBanca");
  const modal = document.getElementById("modalDeposito");
  const botaoFechar = modal.querySelector(".btn-fechar");

  let modalInicializado = false;
  let valorOriginalBanca = 0;

  // Variáveis globais necessárias em outras funções
  let diaria, unidade, oddsMeta;
  let resultadoCalculo, resultadoUnidade, resultadoOdds;
  let valorBancaInput, mensagemErro;

  // ✅ FUNÇÃO PARA ATUALIZAR A META DIÁRIA
  function atualizarMetaDiaria(metaFormatada) {
    const metaElement = document.getElementById("meta-dia");
    if (metaElement && metaFormatada) {
      // Adicionar animação
      metaElement.classList.add("updating");

      // Atualizar o valor
      setTimeout(() => {
        metaElement.textContent = metaFormatada;
      }, 100);

      // Remover animação
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
          // Atualiza placeholder
          campoValor.placeholder = unidadeFormatada;

          // Se o campo está vazio, preenche com a unidade
          if (!campoValor.value || campoValor.value === "R$ 0,00") {
            campoValor.value = unidadeFormatada;
          }
        }

        // Atualiza elemento oculto para JavaScript
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

  // ✅ MODIFICAÇÃO: Recarrega e abre automaticamente após reload
  botaoGerencia.addEventListener("click", (e) => {
    e.preventDefault();

    // Salva no sessionStorage que deve abrir o modal após reload
    sessionStorage.setItem("abrirModalGerencia", "true");

    // Recarrega a página
    location.reload();
  });

  // ✅ MODIFICAÇÃO: Verifica se deve abrir o modal automaticamente após reload
  if (sessionStorage.getItem("abrirModalGerencia") === "true") {
    sessionStorage.removeItem("abrirModalGerencia");

    // Aguarda um pouco para garantir que a página carregou completamente
    setTimeout(() => {
      modal.style.display = "flex";
      inicializarModalDeposito();
    }, 100);
  }

  botaoFechar.addEventListener("click", () => {
    modal.style.display = "none";
  });

  function selecionarAoClicar(input) {
    input.addEventListener("focus", () => input.select());
    input.addEventListener("mouseup", (e) => e.preventDefault());
  }

  // ✅ FUNÇÃO PARA MARCAR CAMPOS OBRIGATÓRIOS
  function marcarCamposObrigatorios(campos) {
    campos.forEach((campo) => {
      if (campo && campo.style) {
        campo.style.border = "2px solid red";
        campo.style.boxShadow = "0 0 5px rgba(255, 0, 0, 0.3)";
      }
    });
  }

  // ✅ FUNÇÃO PARA LIMPAR MARCAÇÃO DE CAMPOS
  function limparMarcacaoCampos(campos) {
    campos.forEach((campo) => {
      if (campo && campo.style) {
        campo.style.border = "";
        campo.style.boxShadow = "";
      }
    });
  }

  // ✅ FUNÇÃO PARA GERAR MENSAGEM ESPECÍFICA POR OPERAÇÃO
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

  // ✅ FUNÇÃO EXIBIR TOAST CORRIGIDA
  function exibirToast(mensagem, tipo = "info") {
    const toastContainer = document.getElementById("toastModal");
    if (!toastContainer) return;

    // ✅ Usa o próprio elemento com as classes CSS
    toastContainer.textContent = mensagem;
    toastContainer.className = `show ${tipo}`;

    // Remove após 3 segundos
    setTimeout(() => {
      toastContainer.className = "hide";
      toastContainer.textContent = "";
    }, 3000);

    // 🔄 Resetar campos APENAS se for sucesso
    if (tipo === "sucesso") {
      const campoValor = document.getElementById("valorBanca");
      if (campoValor) campoValor.value = "";

      // 🔄 Resetar dropdown de ação
      const dropdownToggle = document.querySelector(".dropdown-toggle");
      if (dropdownToggle) {
        dropdownToggle.innerHTML = `<i class="fa-solid fa-hand-pointer"></i> Selecione Uma Opção <i class="fa-solid fa-chevron-down"></i>`;
      }

      const campoAcao = document.getElementById("acaoBanca");
      if (campoAcao) campoAcao.value = "";
    }
  }

  // ✅ FUNÇÃO PARA ADICIONAR EVENTOS DE LIMPEZA DOS CAMPOS
  function adicionarEventosLimpezaCampos() {
    const campos = [valorBancaInput, diaria, unidade, oddsMeta];

    campos.forEach((campo) => {
      if (campo) {
        campo.addEventListener("focus", () => {
          // Remove marcação vermelha quando campo recebe foco
          campo.style.border = "";
          campo.style.boxShadow = "";
        });

        campo.addEventListener("input", () => {
          // Remove marcação vermelha quando usuário começa a digitar
          campo.style.border = "";
          campo.style.boxShadow = "";
        });
      }
    });
  }

  function inicializarModalDeposito() {
    if (modalInicializado) return;
    modalInicializado = true;

    valorBancaInput = modal.querySelector("#valorBanca");
    const valorBancaLabel = modal.querySelector("#valorBancaLabel");
    diaria = modal.querySelector("#porcentagem");
    unidade = modal.querySelector("#unidadeMeta");
    resultadoCalculo = modal.querySelector("#resultadoCalculo");
    resultadoUnidade = modal.querySelector("#resultadoUnidade");
    resultadoOdds = modal.querySelector("#resultadoOdds");
    oddsMeta = modal.querySelector("#oddsMeta");

    // Permite vírgula ou ponto ao digitar no campo odds
    oddsMeta.addEventListener("input", () => {
      oddsMeta.value = oddsMeta.value.replace(/[^0-9.,]/g, "");
    });

    // Converte vírgula para ponto e formata ao perder o foco
    oddsMeta.addEventListener("blur", () => {
      let valor = oddsMeta.value.replace(",", ".");
      let numero = parseFloat(valor);
      oddsMeta.value = isNaN(numero) ? "1.50" : numero.toFixed(2);
    });

    // Formata corretamente ao carregar o modal
    let valorInicialOdds = oddsMeta.value.replace(",", ".");
    let numeroInicialOdds = parseFloat(valorInicialOdds);
    oddsMeta.value = isNaN(numeroInicialOdds)
      ? "1.50"
      : numeroInicialOdds.toFixed(2);

    const acaoSelect = modal.querySelector("#acaoBanca");
    const botaoAcao = modal.querySelector("#botaoAcao");

    selecionarAoClicar(diaria);
    selecionarAoClicar(unidade);
    selecionarAoClicar(oddsMeta);

    const legendaBanca = document.createElement("div");
    legendaBanca.id = "legendaBanca";
    legendaBanca.style = "margin-top: 5px; font-size: 0.9em; color: #7f8c8d;";
    valorBancaInput.parentNode.appendChild(legendaBanca);

    mensagemErro = document.createElement("div");
    mensagemErro.id = "mensagemErro";
    mensagemErro.style = "color: red; margin-top: 10px; font-weight: bold;";
    botaoAcao.parentNode.insertBefore(mensagemErro, botaoAcao.nextSibling);

    const lucroTotalLabel = modal.querySelector("#valorLucroLabel");

    // ✅ CARREGAMENTO INICIAL COM META DIÁRIA
    fetch("ajax_deposito.php")
      .then((response) => response.json())
      .then((data) => {
        if (!data.success) return;

        const lucro = parseFloat(data.lucro);
        lucroTotalLabel.textContent = lucro.toLocaleString("pt-BR", {
          style: "currency",
          currency: "BRL",
        });

        valorOriginalBanca = parseFloat(data.banca);
        valorBancaLabel.textContent = valorOriginalBanca.toLocaleString(
          "pt-BR",
          {
            style: "currency",
            currency: "BRL",
          }
        );

        diaria.value = `${Math.max(
          parseFloat(data.diaria || "2.00"),
          1
        ).toFixed(0)}%`;
        unidade.value = parseInt(data.unidade || "2");
        const oddsFormatada = parseFloat(data.odds || "1.50");
        oddsMeta.value = isNaN(oddsFormatada)
          ? "1.50"
          : oddsFormatada.toFixed(2);

        // ✅ ATUALIZAR META DIÁRIA INICIAL
        if (data.meta_diaria_formatada) {
          atualizarMetaDiaria(data.meta_diaria_formatada);
        }

        // ✅ ATUALIZAR UNIDADE DE ENTRADA INICIAL
        if (data.unidade_entrada_formatada) {
          atualizarUnidadeEntradaFormulario(data.unidade_entrada_formatada);
        }

        calcularMeta(valorOriginalBanca);
      })
      .catch((error) => {
        console.error("Erro ao carregar dados iniciais:", error);
      });

    const dropdownItems = modal.querySelectorAll(".dropdown-menu li");

    dropdownItems.forEach((item) => {
      item.addEventListener("click", () => {
        const tipo = item.getAttribute("data-value");
        acaoSelect.value = tipo;

        valorBancaInput.value = "";
        mensagemErro.textContent = "";

        // Sempre exibe o input, mas controla se está ativo ou não
        valorBancaInput.style.display = "block";

        if (tipo === "add") {
          valorBancaInput.placeholder =
            "Quanto quer Depositar na Banca R$ 0,00";
          valorBancaInput.disabled = false;
          valorBancaInput.classList.remove("desativado");
          botaoAcao.value = "Depositar na Banca";
        } else if (tipo === "sacar") {
          valorBancaInput.placeholder = "Quanto Quer Sacar da Banca R$ 0,00";
          valorBancaInput.disabled = false;
          valorBancaInput.classList.remove("desativado");
          botaoAcao.value = "Sacar da Banca";
        } else if (tipo === "resetar") {
          valorBancaInput.placeholder = "Essa ação irá zerar sua banca";
          valorBancaInput.disabled = true;
          valorBancaInput.classList.add("desativado");
          botaoAcao.value = "Resetar Banca";
        } else if (tipo === "alterar") {
          valorBancaInput.placeholder = "Essa ação não requer valor";
          valorBancaInput.disabled = true;
          valorBancaInput.classList.add("desativado");
          botaoAcao.value = "Salvar Alteração";
        } else {
          valorBancaInput.placeholder = "R$ 0,00";
          valorBancaInput.disabled = false;
          valorBancaInput.classList.remove("desativado");
          botaoAcao.value = "Cadastrar Dados";
        }
      });
    });

    valorBancaInput.addEventListener("input", () => {
      let valor = valorBancaInput.value.replace(/[^\d]/g, "");
      if (!valor) {
        valorBancaInput.value = "";
        mensagemErro.textContent = "";
        legendaBanca.style.display = "block";
        return;
      }

      const valorDigitado = parseFloat(valor) / 100;
      valorBancaInput.value = valorDigitado.toLocaleString("pt-BR", {
        style: "currency",
        currency: "BRL",
      });

      const tipo = acaoSelect.value;
      let valorAtualizado = valorOriginalBanca;

      if (tipo === "add") {
        valorAtualizado += valorDigitado;
        mensagemErro.textContent = ""; // limpa qualquer erro anterior
        legendaBanca.style.display = "block";
      } else if (tipo === "sacar") {
        valorAtualizado -= valorDigitado;

        if (valorDigitado > valorOriginalBanca) {
          mensagemErro.textContent = "Saldo Insuficiente.";
          legendaBanca.style.display = "none";
        } else {
          mensagemErro.textContent = ""; // remove a mensagem se o valor for válido
          legendaBanca.style.display = "block";
        }
      } else if (!tipo && valorOriginalBanca === 0) {
        valorAtualizado = valorDigitado;
      }

      valorAtualizado = Math.max(0, valorAtualizado);
      valorBancaLabel.textContent = valorAtualizado.toLocaleString("pt-BR", {
        style: "currency",
        currency: "BRL",
      });

      calcularMeta(valorAtualizado);
    });

    // ✅ EVENTO DO BOTÃO PRINCIPAL MODIFICADO PARA ATUALIZAR META
    botaoAcao.addEventListener("click", (e) => {
      e.preventDefault();

      // Limpa mensagens de erro anteriores
      mensagemErro.textContent = "";

      const tipoSelecionado = acaoSelect.value;

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

      // ✅ Primeiro limpa todas as marcações
      const todosCampos = camposObrigatorios.map((item) => item.campo);
      limparMarcacaoCampos(todosCampos);

      // ✅ Verifica campos vazios e marca em vermelho
      camposObrigatorios.forEach(({ campo, nome }) => {
        const isDisabled = campo.disabled;
        if (!campo.value.trim() && !isDisabled) {
          camposVazios.push(nome);
          camposComErro.push(campo);
        }
      });

      if (camposVazios.length > 0) {
        // ✅ Marca os campos com erro
        marcarCamposObrigatorios(camposComErro);

        exibirToast(
          `📝 Preencha os seguintes campos: ${camposVazios.join(", ")}`,
          "aviso"
        );
        return;
      }

      if (tipoSelecionado === "resetar") {
        document.getElementById("confirmarReset").style.display = "block";
        return;
      }

      const valorRaw = valorBancaInput.value.replace(/[^\d]/g, "");
      const valorNumerico = parseFloat(valorRaw) / 100;

      const diariaRaw = diaria.value.replace(/[^\d]/g, "");
      const unidadeRaw = unidade.value.replace(/[^\d]/g, "");

      const diariaFloat = parseFloat(diariaRaw);
      const unidadeInt = parseInt(unidadeRaw);

      if (
        tipoSelecionado !== "alterar" &&
        (isNaN(valorNumerico) || valorNumerico <= 0)
      ) {
        // ✅ Marca o campo de valor
        marcarCamposObrigatorios([valorBancaInput]);
        exibirToast("💲 Digite um valor válido.", "erro");
        return;
      }

      if (tipoSelecionado === "sacar" && valorNumerico > valorOriginalBanca) {
        // ✅ Marca o campo de valor
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

      const oddsValor = parseFloat(oddsMeta.value.replace(",", "."));

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
            // ✅ Mensagem específica por tipo de operação
            const mensagem = gerarMensagemOperacao(
              tipoSelecionado,
              valorNumerico
            );
            exibirToast(mensagem, "sucesso");

            // ✅ ATUALIZAR META DIÁRIA SE RETORNADA
            if (resposta.meta_diaria_formatada) {
              atualizarMetaDiaria(resposta.meta_diaria_formatada);
            }

            // ✅ ATUALIZAR UNIDADE DE ENTRADA SE RETORNADA
            if (resposta.unidade_entrada_formatada) {
              atualizarUnidadeEntradaFormulario(
                resposta.unidade_entrada_formatada
              );
            }

            atualizarDadosModal();

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

    // ✅ EVENTOS DE CONFIRMAÇÃO DE RESET MODIFICADOS PARA ATUALIZAR META
    document
      .getElementById("btnConfirmarReset")
      .addEventListener("click", () => {
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

              // ✅ ATUALIZAR META APÓS RESET
              if (resposta.meta_diaria_formatada) {
                atualizarMetaDiaria(resposta.meta_diaria_formatada);
              } else {
                // Se não retornar meta, zera
                atualizarMetaDiaria("0,00");
              }

              // ✅ ATUALIZAR UNIDADE DE ENTRADA APÓS RESET
              if (resposta.unidade_entrada_formatada) {
                atualizarUnidadeEntradaFormulario(
                  resposta.unidade_entrada_formatada
                );
              } else {
                // Se não retornar unidade, zera
                atualizarUnidadeEntradaFormulario("R$ 0,00");
              }

              atualizarDadosModal();
              document.getElementById("confirmarReset").style.display = "none";
            } else {
              exibirToast("❌ Erro ao resetar banca. Tente novamente.", "erro");
            }
          })
          .catch((error) => {
            console.error("Erro ao resetar:", error);
            exibirToast("🔌 Erro de conexão ao resetar banca.", "erro");
          });
      });

    document
      .getElementById("btnCancelarReset")
      .addEventListener("click", () => {
        document.getElementById("confirmarReset").style.display = "none";
      });

    configurarEventosDeMeta();

    // ✅ ADICIONA EVENTOS DE LIMPEZA DOS CAMPOS
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
    const percentualRaw = diaria.value.replace("%", "").replace(",", ".");
    const percentFloat = parseFloat(percentualRaw);

    if (isNaN(percentFloat)) {
      resultadoCalculo.textContent = "";
      return;
    }

    const valorSpan = document
      .getElementById("valorBancaLabel")
      .textContent.replace(/[^\d,]/g, "")
      .replace(",", ".");
    const valorSpanFloat = parseFloat(valorSpan) || 0;

    const valorInputRaw = document
      .getElementById("valorBanca")
      .value.replace(/[^\d]/g, "");
    const valorInputFloat = parseFloat(valorInputRaw) / 100 || 0;

    const tipoAcao = document.getElementById("acaoBanca").value;

    // 🔄 Lógica invertida
    let baseCalculo;
    if (Math.abs(valorSpanFloat - valorInputFloat) < 0.01) {
      baseCalculo = valorInputFloat;
    } else {
      baseCalculo =
        tipoAcao === "casar"
          ? Math.max(0, valorSpanFloat - valorInputFloat)
          : tipoAcao === "depositar"
          ? valorSpanFloat + valorInputFloat
          : valorSpanFloat;
    }

    const unidadeEntrada = baseCalculo * (percentFloat / 100);

    resultadoCalculo.textContent = `Unidade: ${unidadeEntrada.toLocaleString(
      "pt-BR",
      {
        style: "currency",
        currency: "BRL",
      }
    )}`;
    unidadeCalculada = baseCalculo * (percentFloat / 100);
    calcularUnidade(unidadeCalculada);
    calcularOdds(unidadeCalculada);
  }

  function calcularUnidade(valorMeta) {
    const unidadeFloat = parseInt(unidade.value);
    if (!isNaN(unidadeFloat) && !isNaN(valorMeta)) {
      const total = unidadeFloat * valorMeta;
      resultadoUnidade.textContent = `Meta Diária: ${total.toLocaleString(
        "pt-BR",
        {
          style: "currency",
          currency: "BRL",
        }
      )}`;
    } else {
      resultadoUnidade.textContent = "";
    }
  }

  function calcularOdds(valorUnidade) {
    const oddsRaw = oddsMeta.value.replace(",", ".");
    const oddsFloat = parseFloat(oddsRaw);

    const unidadeFloat = parseInt(unidade.value) || 0;
    const valorUnidadeSeguro = !isNaN(valorUnidade) ? valorUnidade : 0;
    const oddsSeguro = !isNaN(oddsFloat) ? oddsFloat : 0;

    const brutoPorEntrada = valorUnidadeSeguro * oddsSeguro;
    const lucroPorEntrada = brutoPorEntrada - valorUnidadeSeguro;
    const metaTotal = unidadeFloat * valorUnidadeSeguro;

    let entradas = 0;
    let lucroAcumulado = 0;

    // 🔁 Itera até atingir ou ultrapassar a meta
    while (lucroAcumulado < metaTotal && entradas < 1000) {
      entradas++;
      lucroAcumulado = entradas * lucroPorEntrada;
    }

    resultadoOdds.textContent = `${entradas} Entradas Para Meta Diária`;
  }

  // ✅ FUNÇÃO atualizarDadosModal MODIFICADA PARA INCLUIR META
  function atualizarDadosModal() {
    fetch("dados_banca.php")
      .then((res) => res.json())
      .then((data) => {
        if (!data.success) return;

        valorOriginalBanca = parseFloat(data.banca);

        // Atualiza o rótulo da banca
        document.getElementById("valorBancaLabel").textContent =
          valorOriginalBanca.toLocaleString("pt-BR", {
            style: "currency",
            currency: "BRL",
          });

        // Atualiza o lucro
        const lucroTotalLabel = document.getElementById("valorLucroLabel");

        const lucro = parseFloat(data.lucro);
        const lucroFormatado = lucro.toLocaleString("pt-BR", {
          style: "currency",
          currency: "BRL",
        });

        lucroTotalLabel.textContent = lucroFormatado;

        // ✅ ATUALIZAR META DIÁRIA SE RETORNADA
        if (data.meta_diaria_formatada) {
          atualizarMetaDiaria(data.meta_diaria_formatada);
        }

        // ✅ ATUALIZAR UNIDADE DE ENTRADA SE RETORNADA
        if (data.unidade_entrada_formatada) {
          atualizarUnidadeEntradaFormulario(data.unidade_entrada_formatada);
        }

        // Atualiza os campos do formulário
        diaria.value = `${Math.max(
          parseFloat(data.diaria || "2.00"),
          1
        ).toFixed(0)}%`;
        unidade.value = parseInt(data.unidade || "2");
        oddsMeta.value = parseFloat(data.odds || "1.50").toLocaleString(
          "pt-BR",
          {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
          }
        );

        // Resetar campos de operação
        const selectAcao = document.getElementById("selectAcao");
        const inputValor = document.getElementById("inputValor");

        if (selectAcao) selectAcao.value = "";
        if (inputValor) inputValor.value = "";

        calcularMeta(valorOriginalBanca);
      })
      .catch((error) => {
        console.error("Erro ao atualizar dados:", error);
      });
    atualizarLucroEBancaViaAjax();
  }
});
