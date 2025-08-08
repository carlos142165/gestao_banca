document.addEventListener("DOMContentLoaded", () => {
  const botaoGerencia = document.getElementById("abrirGerenciaBanca");
  const modal = document.getElementById("modalDeposito");
  const botaoFechar = modal.querySelector(".btn-fechar");

  let modalInicializado = false;
  let valorOriginalBanca = 0;

  // Variáveis globais necessárias em outras funções
  let diaria, unidade, oddsMeta;
  let resultadoCalculo, resultadoUnidade, resultadoOdds;

  botaoGerencia.addEventListener("click", (e) => {
    e.preventDefault();
    modal.style.display = "flex";
    inicializarModalDeposito();
  });

  botaoFechar.addEventListener("click", () => {
    modal.style.display = "none";
  });

  function selecionarAoClicar(input) {
    input.addEventListener("focus", () => input.select());
    input.addEventListener("mouseup", (e) => e.preventDefault());
  }

  function inicializarModalDeposito() {
    if (modalInicializado) return;
    modalInicializado = true;

    const valorBancaInput = modal.querySelector("#valorBanca");
    const valorBancaLabel = modal.querySelector("#valorBancaLabel");
    diaria = modal.querySelector("#porcentagem");
    unidade = modal.querySelector("#unidadeMeta");
    resultadoCalculo = modal.querySelector("#resultadoCalculo");
    resultadoUnidade = modal.querySelector("#resultadoUnidade");
    resultadoOdds = modal.querySelector("#resultadoOdds");
    oddsMeta = modal.querySelector("#oddsMeta");
    const acaoSelect = modal.querySelector("#acaoBanca");
    const botaoAcao = modal.querySelector("#botaoAcao");

    selecionarAoClicar(diaria);
    selecionarAoClicar(unidade);
    selecionarAoClicar(oddsMeta);

    const legendaBanca = document.createElement("div");
    legendaBanca.id = "legendaBanca";
    legendaBanca.style = "margin-top: 5px; font-size: 0.9em; color: #7f8c8d;";
    valorBancaInput.parentNode.appendChild(legendaBanca);

    const mensagemErro = document.createElement("div");
    mensagemErro.id = "mensagemErro";
    mensagemErro.style = "color: red; margin-top: 10px; font-weight: bold;";
    botaoAcao.parentNode.insertBefore(mensagemErro, botaoAcao.nextSibling);

    const lucroTotalLabel = modal.querySelector("#valorLucroLabel");
    const lucroLabelTexto = modal.querySelector("#lucroLabel");

    fetch("ajax_deposito.php")
      .then((response) => response.json())
      .then((data) => {
        if (!data.success) return;

        const lucro = parseFloat(data.lucro);
        lucroTotalLabel.textContent = lucro.toLocaleString("pt-BR", {
          style: "currency",
          currency: "BRL",
        });

        lucroTotalLabel.style.color =
          lucro > 0 ? "#009e42ff" : lucro < 0 ? "#e92a15ff" : "#7f8c8d";
        lucroLabelTexto.innerHTML =
          lucro > 0
            ? `<i class="fa-solid fa-money-bill-trend-up"></i> Lucro`
            : lucro < 0
            ? `<i class="fa-solid fa-money-bill-trend-up"></i> Negativo`
            : `<i class="fa-solid fa-money-bill-trend-up"></i> Neutro`;

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

        calcularMeta(valorOriginalBanca);
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
          botaoAcao.value = "Alterar Dados";
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

    botaoAcao.addEventListener("click", (e) => {
      e.preventDefault();
      mensagemErro.textContent = "";

      const tipoSelecionado = acaoSelect.value;

      if (!tipoSelecionado) {
        mensagemErro.textContent =
          "Selecione uma opção: Depositar, Sacar ou Resetar.";
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
        mensagemErro.textContent = "Digite um valor válido.";
        return;
      }

      if (tipoSelecionado === "sacar" && valorNumerico > valorOriginalBanca) {
        mensagemErro.textContent = "Saldo Insuficiente.";
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
            alert("Operação realizada com sucesso!");
            modal.style.display = "none";
          } else {
            mensagemErro.textContent = "Erro ao salvar no banco.";
          }
        });
    });
    configurarEventosDeMeta();
  }

  function configurarEventosDeMeta() {
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

    const baseCalculo = bancaFloat || 0;
    const unidadeEntrada = baseCalculo * (percentFloat / 100);

    resultadoCalculo.textContent = `Unidade de Entrada: ${unidadeEntrada.toLocaleString(
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

    const bruto = valorUnidadeSeguro * oddsSeguro;
    const lucro = bruto - valorUnidadeSeguro;
    const metaTotal = unidadeFloat * valorUnidadeSeguro;

    const divisao = lucro > 0 ? metaTotal / lucro : 0;

    resultadoOdds.textContent = `${Math.round(
      divisao
    )} Entradas Para Meta Diária`;
  }
});
