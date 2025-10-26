// ===== GERENCIADOR DA PÁGINA CONTA.PHP =====

class GerenciadorContaPagina {
  constructor() {
    this.modal = document.getElementById("modal-editar-campo");
    this.botaoFecharEditar = document.querySelector(".btn-fechar-editar-campo");
    this.usuarioAtual = null;

    this.init();
    this.carregarDadosUsuario();
  }

  init() {
    // Event listeners para modal de edição
    if (this.botaoFecharEditar) {
      this.botaoFecharEditar.addEventListener("click", () =>
        this.fecharModalEditar()
      );
    }

    // Botão cancelar editar
    const btnCancelarEditar = document.getElementById(
      "btn-cancelar-editar-campo"
    );
    if (btnCancelarEditar) {
      btnCancelarEditar.addEventListener("click", () =>
        this.fecharModalEditar()
      );
    }

    // Event listener para fechar modal ao clicar fora
    if (this.modal) {
      this.modal.addEventListener("click", (e) => {
        if (e.target === this.modal) {
          this.fecharModalEditar();
        }
      });
    }

    // Event listeners para os botões de editar campos
    this.configurarBotoesEditar();

    // Event listener para botão alterar plano
    this.configurarBotaoAlterarPlano();

    // Event listener para botão atualizar senha
    this.configurarBotaoAtualizarSenha();

    // Event listener para botão excluir conta
    this.configurarBotaoExcluirConta();
  }

  async carregarDadosUsuario() {
    try {
      console.log("🔄 Carregando dados do usuário...");
      const resposta = await fetch("minha-conta.php?acao=obter_dados");
      const dados = await resposta.json();

      if (dados.success) {
        this.usuarioAtual = dados.usuario || dados.dados;
        console.log("✅ Dados carregados:", this.usuarioAtual);

        // Atualizar elementos
        document.getElementById("email-usuario-header").textContent =
          this.usuarioAtual.email || "email@example.com";
        document.getElementById("id-usuario-header").textContent = `ID: ${
          this.usuarioAtual.id || this.usuarioAtual.usuario_id || "-"
        }`;
        document.getElementById("valor-nome").textContent =
          this.usuarioAtual.nome || "Carregando...";
        document.getElementById("valor-email").textContent =
          this.usuarioAtual.email || "Carregando...";
        document.getElementById("valor-telefone").textContent =
          this.usuarioAtual.telefone || "Não informado";

        // Exibir plano com data de expiração se houver
        this.renderizarBadgePlano();
      } else {
        console.error("❌ Erro ao obter dados:", dados.message);
      }
    } catch (error) {
      console.error("❌ Erro ao carregar dados do usuário:", error);
    }
  }

  renderizarBadgePlano() {
    const plano = this.usuarioAtual.plano || "Gratuito";
    const dataFim = this.usuarioAtual.data_fim_assinatura;

    // Mapear cores e ícones
    const planosConfig = {
      GRATUITO: { cor: "#95a5a6", icone: "fas fa-gift" },
      PRATA: { cor: "#c0392b", icone: "fas fa-coins" },
      OURO: { cor: "#f39c12", icone: "fas fa-star" },
      DIAMANTE: { cor: "#2980b9", icone: "fas fa-gem" },
    };

    const config = planosConfig[plano.toUpperCase()] || planosConfig.GRATUITO;
    const containerPlano = document.getElementById("valor-plano");

    // Criar estrutura do badge
    let html = `
      <span class="badge-plano badge-plano-${plano.toLowerCase()}">
        <i class="${config.icone}"></i>
        <span>${plano}</span>
      </span>
    `;

    // Adicionar data de expiração se houver
    if (dataFim) {
      const dataFimObj = new Date(dataFim);
      const dataFormatada = dataFimObj.toLocaleDateString("pt-BR");
      html += `<span class="plano-data-expiracao">Vence em ${dataFormatada}</span>`;
    }

    containerPlano.innerHTML = html;
  }

  configurarBotoesEditar() {
    const campos = ["nome", "email", "telefone"];

    campos.forEach((campo) => {
      const btnEditar = document.getElementById(`btn-editar-${campo}`);
      if (btnEditar) {
        btnEditar.addEventListener("click", () => {
          this.abrirModalEditar(campo);
        });
      }
    });
  }

  abrirModalEditar(campo) {
    const modal = document.getElementById("modal-editar-campo");
    const input = document.getElementById("input-editar-campo");
    const btnSalvar = document.getElementById("btn-salvar-editar-campo");

    // Pré-popular com valor atual
    const valorAtual = document.getElementById(`valor-${campo}`).textContent;
    input.value = valorAtual !== "Não informado" ? valorAtual : "";
    input.focus();

    // Remover listeners anteriores
    const novoBtn = btnSalvar.cloneNode(true);
    btnSalvar.parentNode.replaceChild(novoBtn, btnSalvar);

    // Adicionar listener para salvar
    document
      .getElementById("btn-salvar-editar-campo")
      .addEventListener("click", () => {
        this.salvarEdicao(campo, input.value);
      });

    // Adicionar listener para Enter
    input.addEventListener("keypress", (e) => {
      if (e.key === "Enter") {
        this.salvarEdicao(campo, input.value);
      }
    });

    // Mostrar modal
    modal.classList.add("ativo");
  }

  async salvarEdicao(campo, novoValor) {
    if (!novoValor.trim()) {
      alert("Por favor, digite um valor válido!");
      return;
    }

    try {
      console.log(`💾 Salvando ${campo}: ${novoValor}`);

      const formData = new FormData();
      formData.append("acao", `atualizar_${campo}`);
      formData.append(campo, novoValor);

      const resposta = await fetch("minha-conta.php", {
        method: "POST",
        body: formData,
      });

      const dados = await resposta.json();

      if (dados.success) {
        console.log("✅ Campo atualizado com sucesso!");
        document.getElementById(`valor-${campo}`).textContent = novoValor;

        if (campo === "email") {
          document.getElementById("email-usuario-header").textContent =
            novoValor;
        }

        this.fecharModalEditar();
        this.mostrarMensagem("Campo atualizado com sucesso!", "sucesso");
      } else {
        console.error("❌ Erro ao atualizar:", dados.message);
        this.mostrarMensagem(dados.message || "Erro ao atualizar", "erro");
      }
    } catch (error) {
      console.error("❌ Erro:", error);
      this.mostrarMensagem("Erro ao atualizar o campo", "erro");
    }
  }

  fecharModalEditar() {
    const modal = document.getElementById("modal-editar-campo");
    modal.classList.remove("ativo");
    document.getElementById("input-editar-campo").value = "";
  }

  configurarBotaoAlterarPlano() {
    const btn = document.getElementById("btn-alterar-plano");
    if (btn) {
      btn.addEventListener("click", () => {
        abrirModalPlanos();
      });
    }
  }

  configurarBotaoAtualizarSenha() {
    const btn = document.getElementById("btn-atualizar-senha");
    if (btn) {
      btn.addEventListener("click", () => {
        this.atualizarSenha();
      });
    }
  }

  async atualizarSenha() {
    const senhaAtual = document
      .getElementById("input-senha-atual")
      .value.trim();
    const senhaNova = document.getElementById("input-senha-nova").value.trim();
    const senhaConfirma = document
      .getElementById("input-senha-confirma")
      .value.trim();

    console.log("🔑 Tentando atualizar senha...");
    console.log("Senha atual preenchida:", !!senhaAtual);
    console.log("Senha nova preenchida:", !!senhaNova);
    console.log("Confirmação preenchida:", !!senhaConfirma);

    // Validações
    if (!senhaAtual || !senhaNova || !senhaConfirma) {
      this.mostrarMensagem("Preencha todos os campos de senha!", "erro");
      return;
    }

    if (senhaNova.length < 6) {
      this.mostrarMensagem(
        "A nova senha deve ter no mínimo 6 caracteres!",
        "erro"
      );
      return;
    }

    if (senhaNova !== senhaConfirma) {
      this.mostrarMensagem("As senhas não coincidem!", "erro");
      return;
    }

    try {
      console.log("🔄 Atualizando senha...");

      const formData = new FormData();
      formData.append("acao", "atualizar_senha");
      formData.append("senha_atual", senhaAtual);
      formData.append("senha_nova", senhaNova);
      formData.append("senha_confirma", senhaConfirma);

      console.log("📤 Enviando dados para servidor...");

      const resposta = await fetch("minha-conta.php", {
        method: "POST",
        body: formData,
      });

      const dados = await resposta.json();

      console.log("📥 Resposta do servidor:", dados);

      if (dados.success) {
        console.log("✅ Senha atualizada com sucesso!");
        document.getElementById("input-senha-atual").value = "";
        document.getElementById("input-senha-nova").value = "";
        document.getElementById("input-senha-confirma").value = "";
        this.mostrarMensagem("Senha atualizada com sucesso!", "sucesso");
      } else {
        console.error("❌ Erro ao atualizar senha:", dados.message);
        this.mostrarMensagem(
          dados.message || "Erro ao atualizar senha",
          "erro"
        );
      }
    } catch (error) {
      console.error("❌ Erro:", error);
      this.mostrarMensagem("Erro ao atualizar senha", "erro");
    }
  }

  configurarBotaoExcluirConta() {
    const btn = document.querySelector(".btn-excluir-conta");
    if (btn) {
      btn.addEventListener("click", () => {
        this.confirmarExclusaoConta();
      });
    }
  }

  confirmarExclusaoConta() {
    const modal = document.getElementById("modal-confirmar-exclusao-conta");
    const inputConfirmacao = document.querySelector(
      ".modal-confirmar-exclusao-input"
    );
    const btnCancelar = document.querySelector(".btn-cancelar-exclusao");
    const btnConfirmar = document.querySelector(".btn-confirmar-exclusao");

    if (!modal) {
      this.mostrarMensagem("Modal não encontrado", "erro");
      return;
    }

    // Limpar input
    inputConfirmacao.value = "";
    btnConfirmar.disabled = true;

    // Abrir modal
    modal.classList.add("ativo");

    // Event listener para input
    inputConfirmacao.addEventListener("input", (e) => {
      btnConfirmar.disabled = e.target.value.toUpperCase() !== "SIM";
    });

    // Event listener para botão cancelar
    btnCancelar.onclick = () => {
      modal.classList.remove("ativo");
      inputConfirmacao.removeEventListener("input", null);
      this.mostrarMensagem("Exclusão cancelada", "info");
    };

    // Event listener para botão confirmar
    btnConfirmar.onclick = () => {
      if (inputConfirmacao.value.toUpperCase() === "SIM") {
        modal.classList.remove("ativo");
        this.excluirConta();
      }
    };

    // Permitir Enter para confirmar
    inputConfirmacao.onkeypress = (e) => {
      if (e.key === "Enter" && inputConfirmacao.value.toUpperCase() === "SIM") {
        btnConfirmar.click();
      }
    };

    // Focar no input
    inputConfirmacao.focus();
  }

  async excluirConta() {
    try {
      console.log("🗑️ Excluindo conta...");

      const formData = new FormData();
      formData.append("acao", "excluir_conta");
      formData.append("confirmacao", "SIM");

      const resposta = await fetch("minha-conta.php", {
        method: "POST",
        body: formData,
      });

      const dados = await resposta.json();

      if (dados.success) {
        console.log("✅ Conta excluída com sucesso!");
        this.mostrarMensagem("Conta excluída com sucesso!", "sucesso");
        setTimeout(() => {
          window.location.href = "home.php";
        }, 2000);
      } else {
        console.error("❌ Erro ao excluir conta:", dados.message);
        this.mostrarMensagem(dados.message || "Erro ao excluir conta", "erro");
      }
    } catch (error) {
      console.error("❌ Erro:", error);
      this.mostrarMensagem("Erro ao excluir conta", "erro");
    }
  }

  mostrarMensagem(mensagem, tipo = "info") {
    // Criar toast simples
    const toast = document.createElement("div");
    toast.textContent = mensagem;
    toast.style.cssText = `
      position: fixed;
      bottom: 20px;
      right: 20px;
      padding: 15px 25px;
      border-radius: 8px;
      font-weight: 600;
      z-index: 10000;
      animation: slideInUp 0.3s ease;
      ${
        tipo === "sucesso"
          ? "background: #27ae60; color: white;"
          : tipo === "erro"
          ? "background: #e74c3c; color: white;"
          : "background: #3498db; color: white;"
      }
    `;

    document.body.appendChild(toast);

    setTimeout(() => {
      toast.style.animation = "slideOutDown 0.3s ease";
      setTimeout(() => toast.remove(), 300);
    }, 3000);
  }
}

// ===== FUNÇÕES PARA GERENCIAR MODAL DE PLANOS =====

let planosCache = [];
let periodoAtual = "mes";

function abrirModalPlanos() {
  console.log("🎯 Redirecionando para página de planos...");
  window.location.href = "planos.php";
}

function fecharModalPlanos() {
  const modal = document.getElementById("modal-planos");
  if (modal) {
    modal.style.display = "none";
  }
}

function fecharModalPagamento() {
  const modal = document.getElementById("modal-pagamento");
  if (modal) {
    modal.style.display = "none";
  }
}

function voltarParaPlanos() {
  fecharModalPagamento();
  document.getElementById("modal-planos").style.display = "flex";
}

function renderizarPlanos() {
  const grid = document.getElementById("planosGrid");
  if (!grid) {
    console.error("❌ Grid de planos não encontrado!");
    return;
  }

  console.log("🎨 Renderizando planos...");

  grid.innerHTML = "";

  if (planosCache.length === 0) {
    console.warn("⚠️ Nenhum plano disponível");
    return;
  }

  planosCache.forEach((plano) => {
    const card = document.createElement("div");
    card.className = `plano-card ${plano.popular ? "popular" : ""}`;

    // Mapear cores e ícones Font Awesome corretamente
    const cores = {
      Gratuito: { cor: "#95a5a6", corEscuro: "#7f8c8d", icone: "fas fa-gift" },
      Prata: {
        cor: "#c0392b",
        corEscuro: "#a93226",
        icone: "fas fa-chart-bar",
      },
      Ouro: { cor: "#f39c12", corEscuro: "#e67e22", icone: "fas fa-star" },
      Diamante: { cor: "#3498db", corEscuro: "#2980b9", icone: "fas fa-gem" },
    };

    const cor = cores[plano.nome] || {
      cor: "#95a5a6",
      corEscuro: "#7f8c8d",
      icone: "fas fa-box",
    };
    card.style.setProperty("--cor-plano", cor.cor);
    card.style.setProperty("--cor-plano-dark", cor.corEscuro);

    // Gerar features baseado em dados do plano
    const features = [];
    if (plano.mentores_limite)
      features.push(`${plano.mentores_limite} Mentor(es)`);
    if (plano.entradas_diarias)
      features.push(`${plano.entradas_diarias} Entrada(s)/dia`);
    features.push("Bot ao vivo");

    const featuresHTML = features
      .map(
        (f) =>
          `<div class="plano-feature"><i class="fas fa-check"></i> ${f}</div>`
      )
      .join("");

    const precoAtual =
      periodoAtual === "ano" ? plano.preco_ano : plano.preco_mes;

    card.innerHTML = `
      <div class="plano-icone"><i class="${cor.icone}"></i></div>
      <div class="plano-nome">${plano.nome}</div>
      <div class="plano-preco">R$ ${parseFloat(precoAtual).toFixed(2)}</div>
      <div class="plano-ciclo">por ${
        periodoAtual === "ano" ? "ano" : "mês"
      }</div>
      <div class="plano-features">
        ${featuresHTML}
      </div>
      <button class="btn-contratar" onclick="selecionarPlano('${plano.id}', '${
      plano.nome
    }', ${parseFloat(precoAtual)})">
        Contratar Agora
      </button>
    `;

    grid.appendChild(card);
  });

  console.log("✅ Planos renderizados com sucesso");
}

function alternarPeriodo(periodo) {
  periodoAtual = periodo;

  // Atualizar botões de toggle
  document.querySelectorAll(".toggle-btn").forEach((btn) => {
    btn.classList.remove("active");
  });
  document.querySelector(`[data-periodo="${periodo}"]`).classList.add("active");

  // Re-renderizar planos
  renderizarPlanos();
}

function selecionarPlano(planoId, planoNome, preco) {
  // Atualizar informações do plano selecionado
  document.getElementById("nomePlanoSelecionado").textContent = planoNome;
  document.getElementById(
    "valorPlanoSelecionado"
  ).textContent = `R$ ${preco.toFixed(2)} por ${
    periodoAtual === "ano" ? "ano" : "mês"
  }`;

  // Armazenar plano selecionado (para usar depois no pagamento)
  window.planoSelecionado = {
    id: planoId,
    nome: planoNome,
    preco,
    periodo: periodoAtual,
  };

  // Fechar modal de planos e abrir modal de pagamento
  fecharModalPlanos();
  document.getElementById("modal-pagamento").style.display = "flex";

  // Resetar formulário
  document.getElementById("formCartao").reset();
  mudarAba("cartao");
}

function mudarAba(nomeAba) {
  // Remover classe active de todos os tabs e conteúdos
  document
    .querySelectorAll(".tab-btn")
    .forEach((btn) => btn.classList.remove("active"));
  document
    .querySelectorAll(".tab-content")
    .forEach((content) => content.classList.remove("active"));

  // Adicionar classe active ao tab e conteúdo selecionado
  document.querySelector(`[data-tab="${nomeAba}"]`).classList.add("active");
  document.getElementById(`tab-${nomeAba}`).classList.add("active");
}

function processarPagamentoCartao() {
  const titular = document.getElementById("titular").value.trim();
  const numeroCartao = document.getElementById("numeroCartao").value.trim();
  const dataValidade = document.getElementById("dataValidade").value.trim();
  const cvv = document.getElementById("cvv").value.trim();

  if (!titular || !numeroCartao || !dataValidade || !cvv) {
    alert("Por favor, preencha todos os campos do cartão");
    return;
  }

  // Aqui você implementaria a chamada ao seu sistema de pagamento
  console.log("Processando pagamento com cartão:", {
    titular,
    numeroCartao: numeroCartao.slice(-4),
    dataValidade,
    plano: window.planoSelecionado,
  });

  alert("Pagamento processado com sucesso!");
  fecharModalPagamento();
}

function processarPagamentoPIX(tipo) {
  console.log("Processando pagamento PIX:", {
    tipo,
    plano: window.planoSelecionado,
  });

  // Aqui você redirecionaria para o Mercado Pago com PIX
  alert("Redirecionando para PIX...");
}

// Fechar modais ao clicar fora deles
document.addEventListener("click", (e) => {
  const modalPlanos = document.getElementById("modal-planos");
  const modalPagamento = document.getElementById("modal-pagamento");

  if (e.target === modalPlanos) {
    fecharModalPlanos();
  }

  if (e.target === modalPagamento) {
    fecharModalPagamento();
  }
});

// Inicializar quando página carregar
document.addEventListener("DOMContentLoaded", () => {
  new GerenciadorContaPagina();
});
