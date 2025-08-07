document.addEventListener("DOMContentLoaded", () => {
  // 🔹 Elementos principais do DOM
  const botaoGerencia = document.getElementById("abrirGerenciaBanca");
  const modal = document.getElementById("modalDeposito");
  const botaoFechar = modal.querySelector(".btn-fechar");

  let modalInicializado = false;
  let valorOriginalBanca = 0;
  // 🔹 Abrir modal de gerenciamento da banca
  botaoGerencia.addEventListener("click", (e) => {
    e.preventDefault();
    if (!document.body.contains(modal)) {
      document.body.appendChild(modal);
    }
    modal.style.display = "flex";
    inicializarModalDeposito();
  });
  // 🔹 Fechar modal
  botaoFechar.addEventListener("click", () => {
    modal.style.display = "none";
  });
  // 🔹 Selecionar conteúdo ao focar nos inputs
  function selecionarAoClicar(input) {
    input.addEventListener("focus", () => input.select());
    input.addEventListener("mouseup", (e) => e.preventDefault());
  }
  // 🔹 Inicialização do modal de depósito
  function inicializarModalDeposito() {
    if (modalInicializado) return;
    modalInicializado = true;
    // 🔸 Elementos do modal
    const valorBancaInput = modal.querySelector("#valorBanca");
    const valorBancaLabel = modal.querySelector("#valorBancaLabel");
    const diaria = modal.querySelector("#porcentagem");
    const unidade = modal.querySelector("#unidadeMeta");
    const resultadoCalculo = modal.querySelector("#resultadoCalculo");
    const resultadoUnidade = modal.querySelector("#resultadoUnidade");
    const resultadoOdds = modal.querySelector("#resultadoOdds");
    const oddsMeta = modal.querySelector("#oddsMeta");
    const acaoSelect = modal.querySelector("#acaoBanca");

    const botaoAcao = modal.querySelector("#botaoAcao");
    // 🔸 Aplicar seleção automática nos inputs
    selecionarAoClicar(diaria);
    selecionarAoClicar(unidade);
    selecionarAoClicar(oddsMeta);
    // 🔸 Criar elementos auxiliares (legenda e mensagem de erro)
    const legendaBanca = document.createElement("div");
    legendaBanca.id = "legendaBanca";
    legendaBanca.style = "margin-top: 5px; font-size: 0.9em; color: #7f8c8d;";
    valorBancaInput.parentNode.appendChild(legendaBanca);

    const mensagemErro = document.createElement("div");
    mensagemErro.id = "mensagemErro";
    mensagemErro.style = "color: red; margin-top: 10px; font-weight: bold;";
    botaoAcao.parentNode.insertBefore(mensagemErro, botaoAcao.nextSibling);
    // 🔸 Elementos de exibição de lucro
    const lucroTotalLabel = modal.querySelector("#valorLucroLabel");
    const lucroLabelTexto = modal.querySelector("#lucroLabel");
    // 🔸 Buscar dados iniciais via AJAX
    fetch("ajax_deposito.php")
      .then((response) => response.json())
      .then((data) => {
        if (!data.success) return;

        // Lucro
        const lucro = parseFloat(data.lucro);
        const lucroFormatado = lucro.toLocaleString("pt-BR", {
          style: "currency",
          currency: "BRL",
        });

        lucroTotalLabel.textContent = lucroFormatado;

        if (lucro > 0) {
          lucroTotalLabel.style.color = "#0bbb54ff"; // verde
          lucroLabelTexto.innerHTML = `<i class="fa-solid fa-money-bill-trend-up"></i> Lucro`;
        } else if (lucro < 0) {
          lucroTotalLabel.style.color = "#e92a15ff"; // vermelho
          lucroLabelTexto.innerHTML = `<i class="fa-solid fa-money-bill-trend-down"></i> Perdendo`;
        } else {
          lucroTotalLabel.style.color = "#7f8c8d"; // cinza
          lucroLabelTexto.innerHTML = `<i class="fa-solid fa-money-bill-trend-up"></i> Neutro`;
        }

        // 🔸 Exibir banca atual e configurar inputs
        valorOriginalBanca = parseFloat(data.banca);
        const valorFormatado = valorOriginalBanca.toLocaleString("pt-BR", {
          style: "currency",
          currency: "BRL",
        });

        valorBancaInput.value = "";
        valorBancaLabel.textContent = valorFormatado;
        const diariaValor = Math.max(parseFloat(data.diaria || "2.00"), 1);
        diaria.value = `${diariaValor.toFixed(0)}%`;

        unidade.value = parseInt(data.unidade || "2");

        calcularMeta(valorOriginalBanca);

        //radioWrapper.style.display = "flex";
      });
    // 🔸 Configurar comportamento dos botões de ação (radio buttons)
    // 🔸 Configurar comportamento da seleção via dropdown
    acaoSelect.value = ""; // limpa seleção inicial
    acaoSelect.addEventListener("change", () => {
      valorBancaInput.value = "";
      mensagemErro.textContent = "";

      const tipo = acaoSelect.value;

      if (tipo === "add") {
        valorBancaInput.placeholder = "Quanto quer Depositar na Banca R$ 0,00";
        valorBancaInput.disabled = false;
        botaoAcao.value = "Depositar na Banca";
      } else if (tipo === "sacar") {
        valorBancaInput.placeholder = "Quanto Quer Sacar da Banca R$ 0,00";
        valorBancaInput.disabled = false;
        botaoAcao.value = "Sacar da Banca";
      } else if (tipo === "resetar") {
        valorBancaInput.placeholder = "Essa ação irá zerar sua banca";
        valorBancaInput.disabled = true;
        botaoAcao.value = "Resetar Banca";
      } else {
        valorBancaInput.placeholder = "R$ 0,00";
        valorBancaInput.disabled = false;
        botaoAcao.value = "Cadastrar Dados";
      }
    });

    // 🔸 Formatar e validar valor digitado
    valorBancaInput.addEventListener("input", () => {
      let valor = valorBancaInput.value.replace(/[^\d]/g, "");
      if (!valor) {
        valorBancaInput.value = "";
        mensagemErro.textContent = "";
        legendaBanca.style.display = "block";
        return;
      }

      const valorDigitado = parseFloat(valor) / 100;
      const formatado = valorDigitado.toLocaleString("pt-BR", {
        style: "currency",
        currency: "BRL",
      });
      valorBancaInput.value = formatado;

      const tipo = acaoSelect.value;
      let valorAtualizado = valorOriginalBanca;

      if (tipo === "add") {
        valorAtualizado += valorDigitado;
      } else if (tipo === "sacar") {
        valorAtualizado -= valorDigitado;
        if (valorDigitado > valorOriginalBanca) {
          mensagemErro.textContent = "Saldo Insuficiente.";
          legendaBanca.style.display = "none";
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

    // 🔸 Executar ação ao clicar no botão
    botaoAcao.addEventListener("click", (e) => {
      e.preventDefault();
      mensagemErro.textContent = "";

      const tipoSelecionado = acaoSelect.value;

      if (!tipoSelecionado) {
        mensagemErro.textContent =
          "Selecione uma opção: Depositar, Sacar ou Resetar.";
        return;
      }
      // 🔹 Validação de campos obrigatórios
      const camposObrigatorios = [
        { campo: valorBancaInput, nome: "Valor da Banca" },
        { campo: diaria, nome: "Porcentagem Diária" },
        { campo: unidade, nome: "Quantidade de Unidade" },
        { campo: oddsMeta, nome: "Odds" },
      ];

      let camposVazios = [];

      camposObrigatorios.forEach(({ campo, nome }) => {
        const isDisabled = campo.disabled;
        if (!campo.value.trim() && !isDisabled) {
          camposVazios.push(nome);
          campo.style.border = "2px solid red";
        } else {
          campo.style.border = "";
        }
      });

      if (camposVazios.length > 0) {
        mensagemErro.textContent =
          "Preencha os seguintes campos: " + camposVazios.join(", ");
        return;
      }
      // 🔹 Ação de resetar banca
      if (tipoSelecionado === "resetar") {
        if (
          !confirm(
            "Tem certeza que deseja resetar sua banca? Essa ação é irreversível."
          )
        )
          return;

        fetch("ajax_deposito.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ acao: "resetar" }),
        })
          .then((res) => res.json())
          .then((resposta) => {
            if (resposta.success) {
              alert("Banca resetada com sucesso!");
              modal.style.display = "none";
            } else {
              alert("Erro ao resetar banca.");
            }
          });
        return;
      }
      // 🔹 Preparar dados para depósito ou saque
      const valorRaw = valorBancaInput.value.replace(/[^\d]/g, "");
      const valorNumerico = parseFloat(valorRaw) / 100;

      const diariaRaw = diaria.value.replace(/[^\d]/g, "");
      const unidadeRaw = unidade.value.replace(/[^\d]/g, "");

      const diariaFloat = parseFloat(diariaRaw);
      const unidadeInt = parseInt(unidadeRaw);

      if (isNaN(valorNumerico) || valorNumerico <= 0) {
        mensagemErro.textContent = "Digite um valor válido.";
        return;
      }

      if (tipoSelecionado === "sacar" && valorNumerico > valorOriginalBanca) {
        mensagemErro.textContent = "Saldo Insuficiente.";
        return;
      }

      let acaoFinal = tipoSelecionado === "sacar" ? "saque" : "deposito";

      const oddsValor = parseFloat(oddsMeta.value.replace(",", "."));
      // 🔹 Enviar dados via AJAX
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
            alert("Operação realizada com sucesso!");
            modal.style.display = "none";
          } else {
            mensagemErro.textContent = "Erro ao salvar no banco.";
          }
        });
    });
    // 🔹 Eventos de input e blur para campos de meta
    diaria.addEventListener("input", () => {
      diaria.value = diaria.value.replace(/[^0-9]/g, "");
      calcularMeta(valorOriginalBanca);
    });

    diaria.addEventListener("blur", () => {
      diaria.value = formatarPorcentagem(diaria.value);
      calcularMeta(valorOriginalBanca);
    });

    unidade.addEventListener("input", () => {
      unidade.value = unidade.value.replace(/\D/g, "");
      calcularMeta(valorOriginalBanca);
    });

    unidade.addEventListener("blur", () => {
      unidade.value = parseInt(unidade.value) || "";
      calcularMeta(valorOriginalBanca);
    });

    oddsMeta.addEventListener("input", () => {
      calcularOdds(unidadeCalculada);
    });

    oddsMeta.addEventListener("blur", () => {
      calcularOdds(unidadeCalculada);
    });
    // 🔹 Função para formatar porcentagem
    function formatarPorcentagem(valor) {
      const num = parseFloat(valor);
      return !isNaN(num) ? `${num}%` : "";
    }

    let unidadeCalculada = 0;
    // 🔹 Cálculo da meta com base na banca e porcentagem
    function calcularMeta(bancaFloat) {
      const percentualRaw = diaria.value.replace("%", "").replace(",", ".");
      const percentFloat = parseFloat(percentualRaw);

      if (isNaN(percentFloat)) {
        resultadoCalculo.textContent = "";
        resultadoUnidade.textContent = "";
        resultadoOdds.textContent = "";
        unidadeCalculada = 0;
        return;
      }

      let baseCalculo =
        bancaFloat > 0
          ? bancaFloat
          : parseFloat(
              valorBancaLabel.textContent
                .replace("R$", "")
                .replace(".", "")
                .replace(",", ".")
                .trim()
            );

      if (isNaN(baseCalculo)) {
        resultadoCalculo.textContent = "";
        resultadoUnidade.textContent = "";
        resultadoOdds.textContent = "";
        unidadeCalculada = 0;
        return;
      }

      const resultado = baseCalculo * (percentFloat / 100);
      unidadeCalculada = resultado;

      resultadoCalculo.textContent = `Sua Unidade de Entrada nas Apostas é de: ${resultado.toLocaleString(
        "pt-BR",
        { style: "currency", currency: "BRL" }
      )}`;

      calcularUnidade(resultado);
      calcularOdds(resultado);
      calcularOddsIdeal();
    }
    // 🔹 Cálculo da meta diária com base na unidade
    function calcularUnidade(valorMeta) {
      const unidadeFloat = parseInt(unidade.value);
      if (!isNaN(unidadeFloat) && !isNaN(valorMeta)) {
        const total = unidadeFloat * valorMeta;
        resultadoUnidade.textContent = `O Valor da Sua Meta Diária é de: ${total.toLocaleString(
          "pt-BR",
          { style: "currency", currency: "BRL" }
        )}`;
      } else {
        resultadoUnidade.textContent = "";
      }
    }
    // 🔹 Cálculo de odds necessárias para atingir a meta
    function calcularOdds(valorUnidade) {
      const oddsRaw = oddsMeta.value.replace(",", ".");
      const oddsFloat = parseFloat(oddsRaw);

      if (!isNaN(oddsFloat) && !isNaN(valorUnidade)) {
        const bruto = valorUnidade * oddsFloat;
        const lucro = bruto - valorUnidade;

        const unidadeFloat = parseInt(unidade.value);
        const metaTotal = unidadeFloat * valorUnidade;

        const divisao = lucro > 0 ? metaTotal / lucro : 0;

        resultadoOdds.textContent = `${Math.round(
          divisao
        )}  Greens Para Meta Diária`;
      } else {
        resultadoOdds.textContent = "";
      }
    }
  }
});
