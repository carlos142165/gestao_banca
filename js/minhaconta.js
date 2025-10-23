/**
 * ============================================
 * GERENCIADOR DO MODAL "MINHA CONTA"
 * ============================================
 *
 * Centraliza toda a lógica frontend do sistema
 * de gerenciamento de conta do usuário
 *
 * Classe: GerenciadorMinhaContaModal
 *
 * Responsabilidades:
 * - Abrir/fechar modais
 * - Buscar dados do usuário
 * - Editar nome, email, telefone
 * - Alterar senha
 * - Excluir conta
 * - Gerenciar notificações (toast)
 *
 * @version 1.0
 */

class GerenciadorMinhaContaModal {
  // ============================================
  // CONSTRUTOR: Inicializa elementos e listeners
  // ============================================
  constructor() {
    // Elementos do modal principal
    this.modal = document.getElementById("modal-minha-conta");
    this.botaoAbrir = document.getElementById("abrirMinhaContaModal");
    this.botaoFechar = document.querySelector(".btn-fechar-minha-conta");

    // Elementos do modal de edição
    this.modalEditar = document.getElementById("modal-editar-campo");
    this.botaoFecharEditar = document.querySelector(".btn-fechar-editar-campo");

    // Elementos do modal de confirmação de exclusão
    this.modalConfirmar = document.getElementById(
      "modal-confirmar-exclusao-conta"
    );
    this.botaoFecharConfirmar = document.querySelector(
      ".btn-cancelar-exclusao"
    );

    // Dados do usuário logado
    this.usuarioAtual = null;

    // Inicializar event listeners
    this.init();
  }

  // ============================================
  // FUNÇÃO: Inicializar Event Listeners
  // ============================================
  // Configura todos os listeners de clique, teclado, etc
  init() {
    // ======= Modal Principal =======
    if (this.botaoAbrir) {
      this.botaoAbrir.addEventListener("click", (e) => {
        e.preventDefault();
        this.abrirModal();
      });
    }

    if (this.botaoFechar) {
      this.botaoFechar.addEventListener("click", () => this.fecharModal());
    }

    // Fechar ao clicar fora do modal
    if (this.modal) {
      this.modal.addEventListener("click", (e) => {
        if (e.target === this.modal) {
          this.fecharModal();
        }
      });
    }

    // ======= Modal de Edição =======
    if (this.botaoFecharEditar) {
      this.botaoFecharEditar.addEventListener("click", () =>
        this.fecharModalEditar()
      );
    }

    const btnCancelarEditar = document.getElementById(
      "btn-cancelar-editar-campo"
    );
    if (btnCancelarEditar) {
      btnCancelarEditar.addEventListener("click", () =>
        this.fecharModalEditar()
      );
    }

    if (this.modalEditar) {
      this.modalEditar.addEventListener("click", (e) => {
        if (e.target === this.modalEditar) {
          this.fecharModalEditar();
        }
      });
    }

    // ======= Modal de Confirmação =======
    if (this.modalConfirmar) {
      this.modalConfirmar.addEventListener("click", (e) => {
        if (e.target === this.modalConfirmar) {
          this.fecharModalConfirmar();
        }
      });
    }

    const btnCancelarExclusao = document.getElementById(
      "btn-cancelar-confirmacao-exclusao"
    );
    if (btnCancelarExclusao) {
      btnCancelarExclusao.addEventListener("click", () =>
        this.fecharModalConfirmar()
      );
    }

    // ======= Botões de Ação =======
    this.configurarBotoesEditar();
    this.configurarBotaoExcluirConta();
  }

  // ============================================
  // FUNÇÃO: Abrir Modal Principal
  // ============================================
  // Busca dados do usuário via minhaconta.php?acao=obter_dados
  // Preenche o modal com os dados
  async abrirModal() {
    try {
      console.log("🔄 Buscando dados de: minhaconta.php?acao=obter_dados");
      const resposta = await fetch("minhaconta.php?acao=obter_dados");
      const dados = await resposta.json();

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
      this.mostrarToast("Erro ao abrir modal: " + erro.message, "erro");
    }
  }

  // ============================================
  // FUNÇÃO: Fechar Modal Principal
  // ============================================
  fecharModal() {
    if (this.modal) {
      this.modal.classList.remove("show");
      document.body.style.overflow = "";
    }
  }

  // ============================================
  // FUNÇÃO: Preencher Dados no Modal
  // ============================================
  // Atualiza os valores exibidos no modal com dados do usuário
  preencherDadosModal() {
    const campos = {
      "valor-nome": "nome",
      "valor-email": "email",
      "valor-telefone": "telefone",
      "valor-plano": "plano",
    };

    Object.entries(campos).forEach(([id, campo]) => {
      const elem = document.getElementById(id);
      if (elem) {
        elem.textContent =
          this.usuarioAtual[campo] ||
          (campo === "plano" ? "Gratuito" : "Não informado");
      }
    });
  }

  // ============================================
  // FUNÇÃO: Configurar Botões de Editar
  // ============================================
  // Configura listeners para nome, email, telefone e plano
  configurarBotoesEditar() {
    // Editar Nome
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

    // Editar Email
    const btnEditarEmail = document.getElementById("btn-editar-email");
    if (btnEditarEmail) {
      btnEditarEmail.addEventListener("click", () => {
        this.abrirModalEditar("email", "Email", this.usuarioAtual.email);
      });
    }

    // Editar Telefone
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

    // Alterar Plano (abre modal de planos)
    const btnAlterarPlano = document.getElementById("btn-alterar-plano");
    if (btnAlterarPlano) {
      btnAlterarPlano.addEventListener("click", () => {
        this.fecharModal();
        const modalPlanos = document.getElementById("modal-planos");
        if (modalPlanos) {
          modalPlanos.style.display = "flex";
          console.log("✅ Modal de planos aberto (centralizado)");
        }
      });
    }

    // Atualizar Senha
    const btnAtualizarSenha = document.getElementById("btn-atualizar-senha");
    if (btnAtualizarSenha) {
      btnAtualizarSenha.addEventListener("click", () => this.atualizarSenha());

      // Enter na última senha também confirma
      const inputSenhaConfirma = document.getElementById(
        "input-senha-confirma"
      );
      if (inputSenhaConfirma) {
        inputSenhaConfirma.addEventListener("keypress", (e) => {
          if (e.key === "Enter") this.atualizarSenha();
        });
      }
    }
  }

  // ============================================
  // FUNÇÃO: Abrir Modal de Edição
  // ============================================
  // Tipo: 'nome', 'email', 'telefone'
  // Título: Texto exibido no header
  // Valor: Valor atual do campo
  abrirModalEditar(tipo, titulo, valor) {
    const inputEditar = document.getElementById("input-editar-campo");
    const labelEditar = document.querySelector(".modal-editar-campo-header h3");
    const btnSalvar = document.getElementById("btn-salvar-editar-campo");

    if (inputEditar) {
      inputEditar.value = valor || "";
      inputEditar.dataset.tipo = tipo;
      inputEditar.placeholder = titulo;

      // Definir tipo de input correto
      if (tipo === "email") inputEditar.type = "email";
      else if (tipo === "telefone") inputEditar.type = "tel";
      else inputEditar.type = "text";

      // Enter também confirma
      inputEditar.onkeypress = (e) => {
        if (e.key === "Enter") this.salvarCampoEditado(tipo);
      };

      setTimeout(() => inputEditar.focus(), 100);
    }

    if (labelEditar) labelEditar.textContent = `Editar ${titulo}`;
    if (btnSalvar) btnSalvar.onclick = () => this.salvarCampoEditado(tipo);
    if (this.modalEditar) this.modalEditar.classList.add("show");
  }

  // ============================================
  // FUNÇÃO: Fechar Modal de Edição
  // ============================================
  fecharModalEditar() {
    if (this.modalEditar) {
      this.modalEditar.classList.remove("show");
    }
  }

  // ============================================
  // FUNÇÃO: Salvar Campo Editado
  // ============================================
  // Valida e envia para minhaconta.php
  // Tipo: 'nome', 'email', 'telefone'
  async salvarCampoEditado(tipo) {
    const input = document.getElementById("input-editar-campo");
    const valor = input.value.trim();
    const btnSalvar = document.getElementById("btn-salvar-editar-campo");

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

      const resposta = await fetch("minhaconta.php", {
        method: "POST",
        body: formData,
      });

      const dados = await resposta.json();

      if (dados.success) {
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
    } finally {
      btnSalvar.disabled = false;
    }
  }

  // ============================================
  // FUNÇÃO: Atualizar Senha do Usuário
  // ============================================
  // Valida: senha atual, nova (6+ chars), confirmação
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

      const resposta = await fetch("minhaconta.php", {
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

  // ============================================
  // FUNÇÃO: Configurar Botão Excluir Conta
  // ============================================
  configurarBotaoExcluirConta() {
    const btnExcluir = document.querySelector(".btn-excluir-conta");
    if (btnExcluir) {
      btnExcluir.addEventListener("click", () =>
        this.abrirModalConfirmarExclusao()
      );
    }
  }

  // ============================================
  // FUNÇÃO: Abrir Modal de Confirmação de Exclusão
  // ============================================
  // Usuário deve digitar "SIM" para confirmar
  abrirModalConfirmarExclusao() {
    const inputConfirmacao = document.getElementById(
      "input-confirmacao-exclusao"
    );
    const btnConfirmar = document.getElementById(
      "btn-confirmar-exclusao-conta"
    );

    if (inputConfirmacao) {
      inputConfirmacao.value = "";

      // Validar "SIM" em tempo real
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

  // ============================================
  // FUNÇÃO: Fechar Modal de Confirmação
  // ============================================
  fecharModalConfirmar() {
    if (this.modalConfirmar) {
      this.modalConfirmar.classList.remove("show");
    }
  }

  // ============================================
  // FUNÇÃO: Confirmar Exclusão de Conta
  // ============================================
  // Envia confirmação "SIM" para backend
  // Redireciona para home.php após sucesso
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

      const resposta = await fetch("minhaconta.php", {
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
    }
  }

  // ============================================
  // FUNÇÃO: Mostrar Notificação Toast
  // ============================================
  // Tipo: 'info', 'sucesso', 'erro'
  // Desaparece automaticamente em 3 segundos
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

// ============================================
// INICIALIZAR QUANDO DOM ESTIVER PRONTO
// ============================================
document.addEventListener("DOMContentLoaded", () => {
  new GerenciadorMinhaContaModal();
});
