// ===== GERENCIADOR DO MODAL MINHA CONTA =====

class GerenciadorMinhaContaModal {
  constructor() {
    this.modal = document.getElementById("modal-minha-conta");
    this.botaoAbrir = document.getElementById("abrirMinhaContaModal");
    this.botaoFechar = document.querySelector(".btn-fechar-minha-conta");

    this.modalEditar = document.getElementById("modal-editar-campo");
    this.botaoFecharEditar = document.querySelector(".btn-fechar-editar-campo");

    this.modalConfirmar = document.getElementById(
      "modal-confirmar-exclusao-conta"
    );
    this.botaoFecharConfirmar = document.querySelector(
      ".btn-cancelar-exclusao"
    );

    this.usuarioAtual = null;

    this.init();
  }

  init() {
    // Event listeners para abrir/fechar modal principal
    if (this.botaoAbrir) {
      this.botaoAbrir.addEventListener("click", (e) => {
        e.preventDefault();
        this.abrirModal();
      });
    }

    if (this.botaoFechar) {
      this.botaoFechar.addEventListener("click", () => this.fecharModal());
    }

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
          this.fecharModal();
        }
      });
    }

    if (this.modalEditar) {
      this.modalEditar.addEventListener("click", (e) => {
        if (e.target === this.modalEditar) {
          this.fecharModalEditar();
        }
      });
    }

    if (this.modalConfirmar) {
      this.modalConfirmar.addEventListener("click", (e) => {
        if (e.target === this.modalConfirmar) {
          this.fecharModalConfirmar();
        }
      });
    }

    // Botão cancelar exclusão
    const btnCancelarExclusao = document.getElementById(
      "btn-cancelar-confirmacao-exclusao"
    );
    if (btnCancelarExclusao) {
      btnCancelarExclusao.addEventListener("click", () =>
        this.fecharModalConfirmar()
      );
    }

    // Event listeners para os botões de editar campos
    this.configurarBotoesEditar();

    // Event listener para botão excluir conta
    this.configurarBotaoExcluirConta();
  }

  async abrirModal() {
    try {
      // Buscar dados do usuário
      console.log("🔄 Buscando dados de: minha-conta.php?acao=obter_dados");
      const resposta = await fetch("minha-conta.php?acao=obter_dados");

      console.log("📝 Status da resposta:", resposta.status);
      console.log("📝 Headers:", resposta.headers);

      const dados = await resposta.json();
      console.log("📦 Dados recebidos:", dados);

      if (!resposta.ok || !dados.success) {
        const mensagem = dados.message || "Erro desconhecido ao carregar dados";
        console.error("❌ Erro:", mensagem);
        this.mostrarToast("Erro: " + mensagem, "erro");
        return;
      }

      this.usuarioAtual = dados.usuario;
      console.log("✅ Usuário carregado:", this.usuarioAtual);
      this.preencherDadosModal();

      if (this.modal) {
        this.modal.classList.add("show");
        document.body.style.overflow = "hidden";
      }
    } catch (erro) {
      console.error("❌ Erro ao abrir modal:", erro);
      console.error("Stack:", erro.stack);
      this.mostrarToast("Erro ao abrir modal: " + erro.message, "erro");
    }
  }

  fecharModal() {
    if (this.modal) {
      this.modal.classList.remove("show");
      document.body.style.overflow = "";
    }
  }

  preencherDadosModal() {
    // Preencher nome
    const elemNome = document.getElementById("valor-nome");
    if (elemNome)
      elemNome.textContent = this.usuarioAtual.nome || "Não informado";

    // Preencher email
    const elemEmail = document.getElementById("valor-email");
    if (elemEmail)
      elemEmail.textContent = this.usuarioAtual.email || "Não informado";

    // Preencher telefone
    const elemTelefone = document.getElementById("valor-telefone");
    if (elemTelefone)
      elemTelefone.textContent = this.usuarioAtual.telefone || "Não informado";

    // Preencher plano com badge colorido
    this.renderizarBadgePlano();
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

    if (!containerPlano) {
      console.warn("⚠️ Container valor-plano não encontrado");
      return;
    }

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

    console.log("🎨 Renderizando badge:", plano, html);
    containerPlano.innerHTML = html;
  }

  configurarBotoesEditar() {
    // Botão editar nome
    const btnEditarNome = document.getElementById("btn-editar-nome");
    if (btnEditarNome) {
      btnEditarNome.addEventListener("click", () => {
        this.abrirModalEditar(
          "nome",
          "Nome do Usuário",
          this.usuarioAtual.nome
        );
      });
    }

    // Botão editar email
    const btnEditarEmail = document.getElementById("btn-editar-email");
    if (btnEditarEmail) {
      btnEditarEmail.addEventListener("click", () => {
        this.abrirModalEditar("email", "Email", this.usuarioAtual.email);
      });
    }

    // Botão editar telefone
    const btnEditarTelefone = document.getElementById("btn-editar-telefone");
    if (btnEditarTelefone) {
      btnEditarTelefone.addEventListener("click", () => {
        this.abrirModalEditar(
          "telefone",
          "Telefone",
          this.usuarioAtual.telefone
        );
      });
    }

    // Botão alterar plano
    const btnAlterarPlano = document.getElementById("btn-alterar-plano");
    if (btnAlterarPlano) {
      btnAlterarPlano.addEventListener("click", () => {
        // Fechar modal de minha conta
        this.fecharModal();

        // Abrir modal de planos (usar display: flex para centralizar)
        const modalPlanos = document.getElementById("modal-planos");
        if (modalPlanos) {
          modalPlanos.style.display = "flex";
          console.log("✅ Modal de planos aberto (centralizado)");
        } else {
          console.error("❌ Modal de planos não encontrado");
        }
      });
    }

    // Botão atualizar senha
    const btnAtualizarSenha = document.getElementById("btn-atualizar-senha");
    if (btnAtualizarSenha) {
      btnAtualizarSenha.addEventListener("click", () => this.atualizarSenha());

      // Suporte para Enter ao digitar na última senha
      const inputSenhaConfirma = document.getElementById(
        "input-senha-confirma"
      );
      if (inputSenhaConfirma) {
        inputSenhaConfirma.addEventListener("keypress", (e) => {
          if (e.key === "Enter") {
            this.atualizarSenha();
          }
        });
      }
    }
  }

  abrirModalEditar(tipo, titulo, valor) {
    const inputEditar = document.getElementById("input-editar-campo");
    const labelEditar = document.querySelector(".modal-editar-campo-header h3");
    const btnSalvar = document.getElementById("btn-salvar-editar-campo");

    if (inputEditar) {
      inputEditar.value = valor || "";
      inputEditar.dataset.tipo = tipo;
      inputEditar.placeholder = titulo;

      // Definir tipo de input apropriado
      if (tipo === "email") {
        inputEditar.type = "email";
      } else if (tipo === "telefone") {
        inputEditar.type = "tel";
      } else {
        inputEditar.type = "text";
      }

      // Adicionar suporte para Enter
      inputEditar.onkeypress = (e) => {
        if (e.key === "Enter") {
          this.salvarCampoEditado(tipo);
        }
      };

      // Focus no input
      setTimeout(() => inputEditar.focus(), 100);
    }

    if (labelEditar) {
      labelEditar.textContent = `Editar ${titulo}`;
    }

    if (btnSalvar) {
      btnSalvar.onclick = () => this.salvarCampoEditado(tipo);
    }

    if (this.modalEditar) {
      this.modalEditar.classList.add("show");
    }
  }

  fecharModalEditar() {
    if (this.modalEditar) {
      this.modalEditar.classList.remove("show");
    }
  }

  async salvarCampoEditado(tipo) {
    const input = document.getElementById("input-editar-campo");
    const valor = input.value.trim();
    const btnSalvar = document.getElementById("btn-salvar-editar-campo");
    const iconeBtnOriginal = '<i class="fas fa-save"></i>';

    if (!valor) {
      this.mostrarToast("Campo não pode estar vazio", "erro");
      return;
    }

    try {
      btnSalvar.disabled = true;
      const iconAtual = btnSalvar.innerHTML;
      btnSalvar.innerHTML = '<span class="spinner"></span>Salvando...';

      const formData = new FormData();
      formData.append("acao", `atualizar_${tipo}`);
      formData.append(tipo, valor);

      const resposta = await fetch("minha-conta.php", {
        method: "POST",
        body: formData,
      });

      const dados = await resposta.json();

      if (dados.success) {
        // Atualizar dados locais
        this.usuarioAtual[tipo] = valor;
        this.preencherDadosModal();

        this.mostrarToast(dados.message, "sucesso");
        this.fecharModalEditar();
      } else {
        this.mostrarToast(dados.message, "erro");
        btnSalvar.innerHTML = iconAtual;
      }
    } catch (erro) {
      console.error("Erro ao salvar campo:", erro);
      this.mostrarToast("Erro ao salvar alterações", "erro");
      btnSalvar.innerHTML = iconeBtnOriginal + " Salvar";
    } finally {
      btnSalvar.disabled = false;
      if (!btnSalvar.innerHTML.includes("Salvando")) {
        btnSalvar.innerHTML = iconeBtnOriginal + " Salvar";
      }
    }
  }

  async atualizarSenha() {
    const inputSenhaAtual = document.getElementById("input-senha-atual");
    const inputSenhaNova = document.getElementById("input-senha-nova");
    const inputSenhaConfirma = document.getElementById("input-senha-confirma");
    const btnAtualizar = document.getElementById("btn-atualizar-senha");

    const senhaAtual = inputSenhaAtual.value.trim();
    const senhaNova = inputSenhaNova.value.trim();
    const senhaConfirma = inputSenhaConfirma.value.trim();

    if (!senhaAtual || !senhaNova || !senhaConfirma) {
      this.mostrarToast("Preencha todos os campos de senha", "erro");
      return;
    }

    if (senhaNova !== senhaConfirma) {
      this.mostrarToast("As novas senhas não conferem", "erro");
      return;
    }

    if (senhaNova.length < 6) {
      this.mostrarToast("Nova senha deve ter pelo menos 6 caracteres", "erro");
      return;
    }

    try {
      btnAtualizar.disabled = true;
      const textoBtnOriginal = btnAtualizar.innerHTML;
      btnAtualizar.innerHTML = '<span class="spinner"></span>Atualizando...';

      const formData = new FormData();
      formData.append("acao", "atualizar_senha");
      formData.append("senha_atual", senhaAtual);
      formData.append("senha_nova", senhaNova);
      formData.append("senha_confirma", senhaConfirma);

      const resposta = await fetch("minha-conta.php", {
        method: "POST",
        body: formData,
      });

      const dados = await resposta.json();

      if (dados.success) {
        inputSenhaAtual.value = "";
        inputSenhaNova.value = "";
        inputSenhaConfirma.value = "";
        this.mostrarToast(dados.message, "sucesso");
        btnAtualizar.innerHTML = textoBtnOriginal;
      } else {
        this.mostrarToast(dados.message, "erro");
        btnAtualizar.innerHTML = textoBtnOriginal;
      }
    } catch (erro) {
      console.error("Erro ao atualizar senha:", erro);
      this.mostrarToast("Erro ao atualizar senha", "erro");
      btnAtualizar.innerHTML = '<i class="fas fa-key"></i> Atualizar Senha';
    } finally {
      btnAtualizar.disabled = false;
    }
  }

  configurarBotaoExcluirConta() {
    const btnExcluir = document.querySelector(".btn-excluir-conta");
    if (btnExcluir) {
      btnExcluir.addEventListener("click", () =>
        this.abrirModalConfirmarExclusao()
      );
    }
  }

  abrirModalConfirmarExclusao() {
    const inputConfirmacao = document.getElementById(
      "input-confirmacao-exclusao"
    );
    const btnConfirmar = document.getElementById(
      "btn-confirmar-exclusao-conta"
    );

    if (inputConfirmacao) {
      inputConfirmacao.value = "";

      // Event listener para validação em tempo real
      inputConfirmacao.addEventListener("input", () => {
        const valor = inputConfirmacao.value.toUpperCase();
        btnConfirmar.disabled = valor !== "SIM";
      });
    }

    if (btnConfirmar) {
      btnConfirmar.disabled = true;
      btnConfirmar.addEventListener("click", () =>
        this.confirmarExclusaoConta()
      );
    }

    if (this.modalConfirmar) {
      this.modalConfirmar.classList.add("show");
    }
  }

  fecharModalConfirmar() {
    if (this.modalConfirmar) {
      this.modalConfirmar.classList.remove("show");
    }
  }

  async confirmarExclusaoConta() {
    const inputConfirmacao = document.getElementById(
      "input-confirmacao-exclusao"
    );
    const confirmacao = inputConfirmacao.value.toUpperCase();

    if (confirmacao !== "SIM") {
      this.mostrarToast("Digite SIM para confirmar", "erro");
      return;
    }

    try {
      const btnConfirmar = document.getElementById(
        "btn-confirmar-exclusao-conta"
      );
      btnConfirmar.disabled = true;
      const textoBtnOriginal = btnConfirmar.innerHTML;
      btnConfirmar.innerHTML = '<span class="spinner"></span>Excluindo...';

      const formData = new FormData();
      formData.append("acao", "excluir_conta");
      formData.append("confirmacao", confirmacao);

      const resposta = await fetch("minha-conta.php", {
        method: "POST",
        body: formData,
      });

      const dados = await resposta.json();

      if (dados.success) {
        this.mostrarToast(
          "Conta excluída com sucesso. Redirecionando...",
          "sucesso"
        );
        setTimeout(() => {
          window.location.href = dados.redirect || "home.php";
        }, 2000);
      } else {
        this.mostrarToast(dados.message, "erro");
        btnConfirmar.disabled = false;
        btnConfirmar.innerHTML = textoBtnOriginal;
      }
    } catch (erro) {
      console.error("Erro ao excluir conta:", erro);
      this.mostrarToast("Erro ao excluir conta", "erro");
      const btnConfirmar = document.getElementById(
        "btn-confirmar-exclusao-conta"
      );
      btnConfirmar.disabled = false;
      btnConfirmar.innerHTML =
        '<i class="fas fa-trash"></i> Confirmar Exclusão';
    }
  }

  mostrarToast(mensagem, tipo = "info") {
    const toast = document.createElement("div");
    toast.className = `toast-notificacao ${tipo}`;
    toast.textContent = mensagem;
    document.body.appendChild(toast);

    setTimeout(() => {
      toast.remove();
    }, 3000);
  }
}

// Inicializar quando o DOM estiver pronto
document.addEventListener("DOMContentLoaded", () => {
  new GerenciadorMinhaContaModal();
});
