// AQUI ESTA O CODIGO QUE ATUALIZA A DATA NO TOPO

document.addEventListener("DOMContentLoaded", function () {
  function atualizarData() {
    var agora = new Date();
    var dia = agora.getDate(); // Obtém o dia do mês
    var mes = agora.toLocaleDateString("pt-BR", { month: "long" }); // Obtém o mês por extenso
    var ano = agora.getFullYear(); // Obtém o ano

    var dataFormatada = `${dia} De ${mes} ${ano}`; // Monta a data no novo formato

    var dataContainer = document.getElementById("data-container");
    if (dataContainer) {
      dataContainer.innerText = dataFormatada;
    }
  }

  atualizarData();
});

// AQUI FINALIZA O CODIGO QUE ATUALIZA A DATA NO TOPO

document
  .getElementById("botao-cadastro")
  .addEventListener("click", async function () {
    const email = document.getElementById("email-cadastro").value;
    const nome = document.getElementById("nome-cadastro").value;
    const senha = document.getElementById("senha-cadastro").value;

    const dados = { email, nome, senha };

    const response = await fetch("/cadastrar", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(dados),
    });

    const result = await response.json();
    alert(result.message);
  });

document.addEventListener("DOMContentLoaded", function () {
  const acaoSelect = document.getElementById("acao");
  const valorInput = document.getElementById("valor");
  const valorLimpo = document.getElementById("valor_limpo");
  const form = valorInput.closest("form");

  acaoSelect.addEventListener("change", function () {
    valorInput.value = "";
    valorLimpo.value = "";

    valorInput.removeEventListener("input", formatarMoeda);
    valorInput.removeEventListener("input", formatarPorcentagem);

    if (acaoSelect.value === "deposito") {
      valorInput.addEventListener("input", formatarMoeda);
    } else {
      valorInput.addEventListener("input", formatarPorcentagem);
    }
  });

  function formatarMoeda(e) {
    let valor = e.target.value.replace(/\D/g, "");
    valor = (valor / 100).toLocaleString("pt-BR", {
      style: "currency",
      currency: "BRL",
    });
    e.target.value = valor;
  }

  function formatarPorcentagem(e) {
    let valor = e.target.value.replace(/[^\d,]/g, "");

    // Garante no máximo uma vírgula e dois dígitos decimais
    const partes = valor.split(",");
    if (partes.length > 2) {
      valor = partes[0] + "," + partes[1];
    }

    e.target.value = valor + "%";
  }

  // 🧼 Antes de enviar, extrai valor limpo
  form.addEventListener("submit", function () {
    let valorBruto = valorInput.value;

    valorBruto = valorBruto
      .replace(/[^\d,]/g, "") // remove qualquer caractere que não seja número ou vírgula
      .replace(",", ".");

    valorLimpo.value = parseFloat(valorBruto);
  });
});
