// CODIGO FORMULARIO DE EDIÇÃO DOS MENTORES
function abrirModal() {
  const modal = document.getElementById("modal-form");
  if (modal) modal.style.display = "block";
}

function fecharModal() {
  const modal = document.getElementById("modal-form");
  if (modal) modal.style.display = "none";
}

function prepararFormularioNovoMentor() {
  document.getElementById("mentor-id").value = "";
  document.getElementById("nome").value = "";
  document.getElementById("preview-img").src =
    "https://cdn-icons-png.flaticon.com/512/847/847969.png";
  document.getElementById("nome-arquivo").textContent = "";
  document.querySelector(".mentor-nome-preview").textContent = "";
  document.getElementById("foto-atual").value = "avatar-padrao.png";
  document.getElementById("acao-form").value = "cadastrar_mentor";
  document.querySelector(".btn-enviar").innerHTML =
    "<i class='fas fa-user-plus'></i> Cadastrar Mentor";
  document.getElementById("btn-excluir").style.display = "none";

  abrirModal();
}

function editarMentor(id) {
  const card = document.querySelector(`[data-id='${id}']`);
  if (!card) return;

  const nome = card.getAttribute("data-nome") || "";
  const foto =
    card.getAttribute("data-foto") ||
    "https://cdn-icons-png.flaticon.com/512/847/847969.png";

  document.getElementById("mentor-id").value = id;
  document.getElementById("nome").value = nome;
  document.getElementById("preview-img").src = foto;
  document.getElementById("nome-arquivo").textContent = "Foto atual";
  document.querySelector(".mentor-nome-preview").textContent = nome;
  document.getElementById("foto-atual").value = foto.split("/").pop();

  document.getElementById("acao-form").value = "editar_mentor";
  document.querySelector(".btn-enviar").innerHTML =
    "<i class='fas fa-save'></i> Salvar Alterações";
  document.getElementById("btn-excluir").style.display = "inline-block";

  abrirModal();
}
// aqui mensagem de confirmação
function excluirMentorDireto() {
  const id = document.getElementById("mentor-id").value;
  if (confirm("Tem certeza que deseja excluir este mentor?")) {
    window.location.href = "gestao-diaria.php?excluir_mentor=" + id;
  }
}
// aqui o tempo de 3 segundos que a mensagem fica na tela
document.addEventListener("DOMContentLoaded", function () {
  const toast = document.getElementById("toast");
  if (toast && toast.classList.contains("ativo")) {
    setTimeout(() => {
      toast.classList.remove("ativo");
    }, 3000);
  }
});
// aqui modal onde faz a pergunta se sim ounão para deletar o mentor
function excluirMentorDireto() {
  const modal = document.getElementById("modal-confirmacao-exclusao");
  if (modal) modal.style.display = "block";
}

function fecharModalExclusao() {
  const modal = document.getElementById("modal-confirmacao-exclusao");
  if (modal) modal.style.display = "none";
}

function confirmarExclusaoMentor() {
  const id = document.getElementById("mentor-id").value;
  window.location.href = "gestao-diaria.php?excluir_mentor=" + id;
}
// CODIGO FORMULARIO DE EDIÇÃO DOS MENTORES

// RESPONSAVEL PELO CADASTRO DOS VALORES DOS MENRORES-->
document.addEventListener("DOMContentLoaded", function () {
  const formulario = document.querySelector(".formulario-mentor");
  const nomePreview = formulario.querySelector(".mentor-nome-preview");
  const fotoPreview = formulario.querySelector(".mentor-foto-preview");
  const idHidden = formulario.querySelector(".mentor-id-hidden");
  const formMentor = document.getElementById("form-mentor");
  const botaoFechar = document.querySelector(".botao-fechar");
  const campoValor = document.getElementById("valor");

  // ✅ Exibe dados no formulário de cadastro
  function exibirFormularioMentor(card) {
    nomePreview.textContent = card.getAttribute("data-nome");
    fotoPreview.src = card.getAttribute("data-foto");
    idHidden.value = card.getAttribute("data-id");
    formulario.style.display = "block";
  }

  // ✅ Recarrega mentor cards e adiciona cliques
  function recarregarMentores() {
    fetch("carregar-mentores.php")
      .then((res) => res.text())
      .then((html) => {
        const container = document.getElementById("listaMentores");
        container.innerHTML = html;

        container.querySelectorAll(".mentor-card").forEach((card) => {
          const idMentor = card.getAttribute("data-id");

          card.addEventListener("click", function (event) {
            const alvo = event.target;

            const clicouEmBotao =
              alvo.closest(".btn-icon") ||
              alvo.closest(".menu-opcoes") ||
              ["BUTTON", "I", "SPAN"].includes(alvo.tagName);

            if (clicouEmBotao) return;

            // ✅ Corrigido: salva o card clicado e abre formulário corretamente
            ultimoCardClicado = card; // 🧠 Salva para reabrir depois se necessário
            mentorAtualId = null; // 🔄 Garante modo cadastro
            exibirFormularioMentor(card); // 🟢 Abre formulário de cadastro
          });
        });
      });
  }

  // ✅ Inicializa mentor cards no carregamento
  recarregarMentores(); // 🛠️ Correção embutida para funcionar logo após a página carregar

  // ✅ Formatação automática do valor
  campoValor.addEventListener("input", function () {
    let valor = this.value.replace(/\D/g, "");
    if (valor === "") {
      this.value = "R$ 0,00";
      return;
    }
    if (valor.length < 3) {
      valor = valor.padStart(3, "0");
    }
    const reais = valor.slice(0, -2);
    const centavos = valor.slice(-2);
    this.value = `R$ ${parseInt(reais).toLocaleString("pt-BR")},${centavos}`;
  });

  // ✅ Submete o formulário
  formMentor.addEventListener("submit", function (e) {
    e.preventDefault();

    const opcaoSelecionada = document.querySelector(
      "input[name='opcao']:checked"
    );
    if (!opcaoSelecionada) {
      mostrarToast("⚠️ Por favor, selecione Green ou Red.");
      return;
    }

    let valor = campoValor.value.replace(/\D/g, "").padStart(3, "0");
    const reais = valor.slice(0, -2);
    const centavos = valor.slice(-2);
    campoValor.value = `${reais}.${centavos}`;

    const formData = new FormData(this);

    fetch("cadastrar-valor.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.text())
      .then((mensagem) => {
        mostrarToast(mensagem, "sucesso");
        formMentor.reset();
        formulario.style.display = "none";
        recarregarMentores(); // ✅ Atualiza cards depois do envio
      })
      .catch((error) => {
        alert("❌ Erro ao enviar: " + error);
      });
  });

  // ✅ Fecha formulário
  window.fecharFormulario = function () {
    formMentor.reset();
    formulario.style.display = "none";
  };

  botaoFechar.addEventListener("click", fecharFormulario);
});

// ✅ Toast de alerta
function mostrarToast(mensagem, tipo = "aviso") {
  const toast = document.getElementById("mensagem-status");
  toast.className = `toast ${tipo} ativo`;
  toast.textContent = mensagem;

  setTimeout(() => {
    toast.classList.remove("ativo");
    toast.classList.remove(tipo);
  }, 4000);
}

// ✅ Menu três pontinhos
document.addEventListener("click", function (e) {
  const isToggle = e.target.classList.contains("menu-toggle");

  document.querySelectorAll(".menu-opcoes").forEach((menu) => {
    menu.style.display = "none";
  });

  if (isToggle) {
    const opcoes = e.target.nextElementSibling;
    if (opcoes) {
      opcoes.style.display = "block";
      e.stopPropagation();
    }
  }
});
// FIM DO CODIGO RESPONSAVEL PELO CADASTRO DOS VALORES DOS MENRORES-->

//CODIGO RESPONSAVEL POR MOSTRAR NA TELA OS MENTORES -->
function abrirModal() {
  document.getElementById("modal-form").style.display = "block";
}

function fecharModal() {
  document.getElementById("modal-form").style.display = "none";
}

// Fecha o modal ao clicar fora do conteúdo
window.onclick = function (event) {
  const modal = document.getElementById("modal-form");
  if (event.target === modal) {
    fecharModal();
  }
};

// Mostra nome do arquivo escolhido
function mostrarNomeArquivo(input) {
  const nome = input.files[0]?.name || "Nenhum arquivo selecionado";
  document.getElementById("nome-arquivo").textContent = nome;

  const previewImg = document.getElementById("preview-img");
  const removerBtn = document.getElementById("remover-foto");

  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function (e) {
      previewImg.src = e.target.result;
      removerBtn.style.display = "inline-block";
    };
    reader.readAsDataURL(input.files[0]);
  } else {
    previewImg.src = "https://cdn-icons-png.flaticon.com/512/847/847969.png";
    removerBtn.style.display = "none";
  }
}

// Botão para remover imagem e restaurar avatar padrão
function removerImagem() {
  const previewImg = document.getElementById("preview-img");
  const inputFile = document.getElementById("foto");
  const removerBtn = document.getElementById("remover-foto");

  inputFile.value = ""; // limpa o input de arquivo
  previewImg.src = "https://cdn-icons-png.flaticon.com/512/847/847969.png"; // volta pro avatar
  document.getElementById("nome-arquivo").textContent =
    "Nenhum arquivo selecionado";
  removerBtn.style.display = "none";
}
// FIM DO CODIGO RESPONSAVEL POR MOSTRAR NA TELA OS MENTORES  -->

// RESPONSAVEL PELOS VALOR DE ENTRADA E A AREA DOS 3 PONTINHOS PARA EXCLUIR-->
let mentorAtualId = null;
let ultimoCardClicado = null;

// ✅ Exibe loader
function mostrarLoader() {
  const loader = document.getElementById("loader");
  if (loader) loader.style.display = "flex";
}

// ✅ Oculta loader
function ocultarLoader() {
  const loader = document.getElementById("loader");
  if (loader) loader.style.display = "none";
}

// ✅ Abre tela de edição com efeito
function abrirTelaEdicao() {
  const tela = document.getElementById("tela-edicao");
  tela.style.display = "block";
  setTimeout(() => tela.classList.remove("oculta"), 10);
}

// ✅ Fecha tela de edição
function fecharTelaEdicao() {
  const tela = document.getElementById("tela-edicao");
  tela.classList.add("oculta");
  setTimeout(() => {
    tela.style.display = "none";
    tela.classList.remove("oculta");
  }, 300);
}

// ✅ Renderiza histórico do mentor
function editarAposta(idMentor) {
  mentorAtualId = idMentor;

  const card = document.querySelector(`[data-id='${idMentor}']`);
  if (!card) return;

  document.getElementById("nomeMentorEdicao").textContent =
    card.getAttribute("data-nome");
  document.getElementById("fotoMentorEdicao").src =
    card.getAttribute("data-foto");
  abrirTelaEdicao();

  fetch(`filtrar-entradas.php?id=${idMentor}&tipo=hoje`)
    .then((res) => res.json())
    .then(mostrarResultados)
    .catch((err) => {
      console.error("Erro ao carregar histórico:", err);
      document.getElementById("resultado-filtro").innerHTML =
        "<p style='color:red;'>Erro ao carregar dados.</p>";
    });
}

// ✅ Exibe dados de cada entrada
function mostrarResultados(entradas) {
  const container = document.getElementById("resultado-filtro");
  container.innerHTML = "";

  if (!entradas || entradas.length === 0) {
    container.innerHTML =
      "<p style='color:gray;'>Nenhuma Entrada Cadastrada Hoje.</p>";
    return;
  }

  entradas.forEach((e) => {
    const valorGreen = parseFloat(e.valor_green);
    const valorRed = parseFloat(e.valor_red);
    const dataCriacao = new Date(e.data_criacao);
    const dataFormatada = dataCriacao.toLocaleDateString("pt-BR");
    const horaFormatada = dataCriacao.toLocaleTimeString("pt-BR", {
      hour: "2-digit",
      minute: "2-digit",
    });

    let infoHTML = "";
    let bordaCor = "#ccc";

    if (e.green > 0) {
      infoHTML += `<p><strong>Green:</strong> ${e.green}</p>`;
      bordaCor = "#4CAF50";
    }

    if (e.red > 0) {
      infoHTML += `<p><strong>Red:</strong> ${e.red}</p>`;
      bordaCor = "#e74c3c";
    }

    if (typeof valorGreen === "number" && !isNaN(valorGreen) && valorGreen > 0)
      infoHTML += `<p class="info-pequena"><strong>Valor:</strong> ${valorGreen.toLocaleString(
        "pt-BR",
        { style: "currency", currency: "BRL" }
      )}</p>`;

    if (typeof valorRed === "number" && !isNaN(valorRed) && valorRed > 0)
      infoHTML += `<p class="info-pequena"><strong>Valor:</strong> ${valorRed.toLocaleString(
        "pt-BR",
        { style: "currency", currency: "BRL" }
      )}</p>`;

    infoHTML += `<p class="info-pequena"><strong>Data:</strong> ${dataFormatada} às ${horaFormatada}</p>`;

    container.innerHTML += `
      <div class="entrada-card" style="border-left: 6px solid ${bordaCor};">
        <div class="entrada-info">${infoHTML}</div>
        <div class="entrada-acoes">
          <button onclick="excluirEntrada(${e.id})" class="btn-icon btn-lixeira" title="Excluir">
            <i class="fas fa-trash"></i>
          </button>
        </div>
      </div>
    `;
  });
}

// ✅ Função global para abrir formulário de cadastro
function exibirFormularioMentor(card) {
  const nomePreview = document.querySelector(".mentor-nome-preview");
  const fotoPreview = document.querySelector(".mentor-foto-preview");
  const idHidden = document.querySelector(".mentor-id-hidden");
  const formulario = document.querySelector(".formulario-mentor");

  nomePreview.textContent = card.getAttribute("data-nome");
  fotoPreview.src = card.getAttribute("data-foto");
  idHidden.value = card.getAttribute("data-id");
  formulario.style.display = "block";
}

// ✅ Recarrega mentores e reaplica eventos corretamente
function recarregarMentores() {
  return fetch("carregar-mentores.php")
    .then((res) => res.text())
    .then((html) => {
      const container = document.getElementById("listaMentores");
      container.innerHTML = html;

      container.querySelectorAll(".mentor-card").forEach((oldCard) => {
        const cloned = oldCard.cloneNode(true);
        oldCard.replaceWith(cloned);

        cloned.addEventListener("click", function (event) {
          const alvo = event.target;
          const clicouEmBotao =
            alvo.closest(".btn-icon") ||
            alvo.closest(".menu-opcoes") ||
            ["BUTTON", "I", "SPAN"].includes(alvo.tagName);
          if (clicouEmBotao) return;

          mentorAtualId = null;
          ultimoCardClicado = cloned;
          exibirFormularioMentor(cloned);
        });
      });
    });
}

// ✅ Exclusão com controle pós-ação
function excluirEntrada(idEntrada) {
  const modal = document.getElementById("modal-confirmacao");
  const btnConfirmar = document.getElementById("btnConfirmar");
  const btnCancelar = document.getElementById("btnCancelar");

  // Exibe o modal de confirmação
  modal.style.display = "flex";

  // Remove event listeners anteriores para evitar duplicações
  btnConfirmar.onclick = null;
  btnCancelar.onclick = null;

  btnCancelar.onclick = () => {
    modal.style.display = "none";
  };

  btnConfirmar.onclick = () => {
    modal.style.display = "none";
    const idMentorBackup = mentorAtualId;
    const tela = document.getElementById("tela-edicao");
    const estaAberta = tela.style.display === "block";

    mostrarLoader();

    fetch("excluir-entrada.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `id=${encodeURIComponent(idEntrada)}`,
    })
      .then((res) => res.text())
      .then((msg) => {
        mostrarToast(msg.trim(), msg.includes("sucesso") ? "sucesso" : "aviso");
        return recarregarMentores();
      })
      .then(() => {
        fecharTelaEdicao();
        setTimeout(() => {
          if (estaAberta && idMentorBackup) {
            editarAposta(idMentorBackup);
          } else if (!estaAberta && ultimoCardClicado) {
            exibirFormularioMentor(ultimoCardClicado);
          }
        }, 300);
      })
      .catch((err) => {
        console.error("Erro:", err);
        mostrarToast("❌ Falha ao excluir. Verifique o ID ou tente novamente.");
      })
      .finally(() => {
        ocultarLoader();
      });
  };
}
// FIM DO CODIGO RESPONSAVEL PELOS VALOR DE ENTRADA E A AREA DOS 3 PONTINHOS PARA EXCLUIR-->
