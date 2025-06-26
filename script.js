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
