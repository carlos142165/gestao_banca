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

// AQUI


<script>
document.addEventListener("DOMContentLoaded", function () {
  const cards = document.querySelectorAll(".mentor-card");
  const formulario = document.querySelector(".formulario-mentor");
  const nomePreview = document.querySelector(".mentor-nome-preview");
  const fotoPreview = document.querySelector(".mentor-foto-preview");
  const idHidden = document.querySelector(".mentor-id-hidden");
  const form = document.getElementById("form-mentor");

  function mostrarToast(mensagem) {
    const toast = document.getElementById("mensagem-status");
    toast.textContent = mensagem;
    toast.style.display = "block";
    setTimeout(() => {
      toast.style.display = "none";
    }, 5000);
  }


function atualizarCards() {
  fetch("carregar-mentores.php")
    .then(res => res.text())
    .then(html => {
      document.querySelector(".mentor-wrapper").innerHTML = html;
      // Reatribuir eventos aos novos cards
      document.querySelectorAll(".mentor-card").forEach(card => {
        card.addEventListener("click", function () {
          nomePreview.textContent = card.dataset.nome;
          fotoPreview.src = card.dataset.foto;
          idHidden.value = card.dataset.id;
          formulario.style.display = "block";
        });
      });
    });
}

  cards.forEach(card => {
    card.addEventListener("click", function () {
      nomePreview.textContent = card.dataset.nome;
      fotoPreview.src = card.dataset.foto;
      idHidden.value = card.dataset.id;
      formulario.style.display = "block";
    });
  });

  window.fecharFormulario = function () {
    formulario.style.display = "none";
  };

  form.addEventListener("submit", function (e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch("cadastrar-valor.php", {
      method: "POST",
      body: formData
    })
    .then(msg => {
     mostrarToast(msg);
     form.reset();
     formulario.style.display = "none";
     atualizarCards(); // ⬅️ Atualiza os cards dinamicamente
   })
    .catch(err => {
      mostrarToast("❌ Erro: " + err);
    });
  });
});
</script>