// ================================================
// SISTEMA DE GESTÃO DE MENTORES - VERSÃO COMPLETA CORRIGIDA
// ================================================

// ✅ CONFIGURAÇÕES E CONSTANTES
const CONFIG = {
  LIMITE_CARACTERES_NOME: 17,
  INTERVALO_ATUALIZACAO: 30000, // 30 segundos
  TIMEOUT_TOAST: 4000,
  AVATAR_PADRAO: "https://cdn-icons-png.flaticon.com/512/847/847969.png",
};

// ✅ UTILITÁRIOS GERAIS
const Utils = {
  // Converte valor BRL para número
  getValorNumerico(valorBRL) {
    if (!valorBRL || typeof valorBRL !== "string") return 0;
    return (
      parseFloat(
        valorBRL
          .replace(/[^\d,.-]/g, "")
          .replace(/\./g, "")
          .replace(",", ".")
      ) || 0
    );
  },

  // Formata valor para BRL
  formatarBRL(valor) {
    const numero =
      typeof valor === "string" ? this.getValorNumerico(valor) : valor;
    return numero.toLocaleString("pt-BR", {
      style: "currency",
      currency: "BRL",
    });
  },

  // Capitaliza nome
  capitalizarNome(nome) {
    if (!nome || typeof nome !== "string") return "";

    return nome
      .replace(/\s+/g, " ")
      .trim()
      .split(" ")
      .map((palavra) =>
        palavra
          ? palavra.charAt(0).toUpperCase() + palavra.slice(1).toLowerCase()
          : ""
      )
      .join(" ");
  },

  // Debounce para evitar múltiplas execuções
  debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  },
};

// ✅ SISTEMA DE TOAST/NOTIFICAÇÕES
const ToastManager = {
  mostrar(mensagem, tipo = "aviso") {
    const toast = document.getElementById("mensagem-status");
    if (!toast) {
      console.warn("Elemento toast não encontrado");
      return;
    }

    // Remove classes anteriores
    toast.className = "toast";

    // Adiciona novas classes
    toast.classList.add(tipo, "ativo");
    toast.textContent = mensagem;

    // Auto-remove após timeout
    setTimeout(() => {
      toast.classList.remove("ativo", tipo);
    }, CONFIG.TIMEOUT_TOAST);
  },
};

// ✅ GERENCIADOR DE MODAIS
const ModalManager = {
  abrir(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.style.display = "block";
      document.body.style.overflow = "hidden"; // Previne scroll do body
    }
  },

  fechar(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.style.display = "none";
      document.body.style.overflow = ""; // Restaura scroll
    }
  },

  // Fecha modal ao clicar fora
  inicializarEventosGlobais() {
    window.addEventListener("click", (event) => {
      const modais = ["modal-form", "modal-confirmacao-exclusao"];
      modais.forEach((modalId) => {
        const modal = document.getElementById(modalId);
        if (event.target === modal) {
          this.fechar(modalId);
        }
      });
    });
  },
};

// ✅ GERENCIADOR DE FORMULÁRIOS - VERSÃO CORRIGIDA
const FormularioManager = {
  // ✅ CORREÇÃO: Prepara formulário para novo mentor
  prepararNovoMentor() {
    console.log("Preparando formulário para novo mentor...");

    try {
      // Reseta todos os campos
      const elementos = {
        "mentor-id": "",
        nome: "",
        "nome-arquivo": "Nenhum arquivo selecionado",
        "foto-atual": "avatar-padrao.png",
        "acao-form": "cadastrar_mentor",
      };

      Object.entries(elementos).forEach(([id, valor]) => {
        const elemento = document.getElementById(id);
        if (elemento) {
          if (elemento.tagName === "INPUT") {
            elemento.value = valor;
          } else {
            elemento.textContent = valor;
          }
        } else {
          console.warn(`Elemento não encontrado: ${id}`);
        }
      });

      // ✅ CORREÇÃO: Atualiza elementos visuais com verificação
      this.atualizarElementosVisuaisNovoMentor();

      // ✅ CORREÇÃO: Limpa o campo de arquivo
      const inputFoto = document.getElementById("foto");
      if (inputFoto) {
        inputFoto.value = "";
      }

      ModalManager.abrir("modal-form");
      console.log("✅ Formulário preparado para novo mentor");
    } catch (error) {
      console.error("Erro ao preparar novo mentor:", error);
      ToastManager.mostrar("❌ Erro ao abrir formulário", "erro");
    }
  },

  // ✅ NOVA FUNÇÃO: Atualiza elementos visuais para novo mentor
  atualizarElementosVisuaisNovoMentor() {
    const elementos = {
      "preview-img": { tipo: "src", valor: CONFIG.AVATAR_PADRAO },
      "mentor-nome-preview": { tipo: "text", valor: "" },
      "btn-enviar": {
        tipo: "html",
        valor: "<i class='fas fa-user-plus'></i> Cadastrar Mentor",
      },
      "btn-excluir": { tipo: "display", valor: "none" },
      "remover-foto": { tipo: "display", valor: "none" },
    };

    Object.entries(elementos).forEach(([id, config]) => {
      const elemento = document.getElementById(id);
      if (elemento) {
        switch (config.tipo) {
          case "src":
            elemento.src = config.valor;
            break;
          case "text":
            elemento.textContent = config.valor;
            break;
          case "html":
            elemento.innerHTML = config.valor;
            break;
          case "display":
            elemento.style.display = config.valor;
            break;
        }
      }
    });
  },

  // ✅ CORREÇÃO MELHORADA: Prepara formulário para editar mentor
  prepararEdicaoMentor(id) {
    console.log(`Preparando edição do mentor ID: ${id}`);

    try {
      const card = document.querySelector(`[data-id='${id}']`);
      if (!card) {
        ToastManager.mostrar("❌ Mentor não encontrado", "erro");
        return;
      }

      const nome = card.getAttribute("data-nome") || "";
      const foto = card.getAttribute("data-foto") || CONFIG.AVATAR_PADRAO;

      // ✅ CORREÇÃO: Valida dados antes de preencher
      if (!nome.trim()) {
        ToastManager.mostrar("❌ Nome do mentor inválido", "erro");
        return;
      }

      // Preenche campos do formulário
      this.preencherCamposEdicao(id, nome, foto);

      // Atualiza elementos visuais
      this.atualizarElementosVisuaisEdicao(nome, foto);

      ModalManager.abrir("modal-form");
      console.log("✅ Formulário preparado para edição");
    } catch (error) {
      console.error("Erro ao preparar edição:", error);
      ToastManager.mostrar("❌ Erro ao carregar dados do mentor", "erro");
    }
  },

  // ✅ NOVA FUNÇÃO: Preenche campos para edição
  preencherCamposEdicao(id, nome, foto) {
    const fotoNome = foto.includes("/") ? foto.split("/").pop() : foto;

    const elementos = {
      "mentor-id": id,
      nome: nome,
      "foto-atual": fotoNome,
      "acao-form": "editar_mentor",
    };

    Object.entries(elementos).forEach(([elementId, valor]) => {
      const elemento = document.getElementById(elementId);
      if (elemento) {
        elemento.value = valor;
      } else {
        console.warn(`Campo não encontrado: ${elementId}`);
      }
    });
  },

  // ✅ NOVA FUNÇÃO: Atualiza elementos visuais para edição
  atualizarElementosVisuaisEdicao(nome, foto) {
    const elementos = {
      "preview-img": { tipo: "src", valor: foto },
      "nome-arquivo": { tipo: "text", valor: "Foto atual" },
      "mentor-nome-preview": { tipo: "text", valor: nome },
      "btn-enviar": {
        tipo: "html",
        valor: "<i class='fas fa-save'></i> Salvar Alterações",
      },
      "btn-excluir": { tipo: "display", valor: "inline-block" },
      "remover-foto": { tipo: "display", valor: "none" },
    };

    Object.entries(elementos).forEach(([id, config]) => {
      const elemento = document.getElementById(id);
      if (elemento) {
        switch (config.tipo) {
          case "src":
            elemento.src = config.valor;
            // ✅ CORREÇÃO: Adiciona fallback de erro
            elemento.onerror = () => {
              elemento.src = CONFIG.AVATAR_PADRAO;
            };
            break;
          case "text":
            elemento.textContent = config.valor;
            break;
          case "html":
            elemento.innerHTML = config.valor;
            break;
          case "display":
            elemento.style.display = config.valor;
            break;
        }
      }
    });

    // ✅ CORREÇÃO: Limpa o input de arquivo na edição
    const inputFoto = document.getElementById("foto");
    if (inputFoto) {
      inputFoto.value = "";
    }
  },

  // ✅ NOVA FUNÇÃO: Valida formulário antes do envio
  validarFormulario() {
    const nome = document.getElementById("nome")?.value?.trim();
    const acao = document.getElementById("acao-form")?.value;

    if (!nome || nome.length < 2) {
      ToastManager.mostrar("❌ Nome deve ter pelo menos 2 caracteres", "erro");
      return false;
    }

    if (nome.length > CONFIG.LIMITE_CARACTERES_NOME) {
      ToastManager.mostrar(
        `❌ Nome deve ter no máximo ${CONFIG.LIMITE_CARACTERES_NOME} caracteres`,
        "erro"
      );
      return false;
    }

    if (!acao || !["cadastrar_mentor", "editar_mentor"].includes(acao)) {
      ToastManager.mostrar("❌ Ação inválida", "erro");
      return false;
    }

    return true;
  },

  // ✅ NOVA FUNÇÃO: Processa submissão do formulário de mentor
  async processarSubmissaoMentor(form) {
    if (!this.validarFormulario()) {
      return false;
    }

    const formData = new FormData(form);
    const acao = formData.get("acao");

    try {
      LoaderManager.mostrar();

      const response = await fetch("gestao-diaria.php", {
        method: "POST",
        body: formData,
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      // ✅ CORREÇÃO: Aguarda a resposta e processa o redirecionamento
      const responseText = await response.text();

      // Se a resposta contém HTML (redirecionamento), significa sucesso
      if (
        responseText.includes("<!DOCTYPE html") ||
        responseText.includes("<html")
      ) {
        const mensagem =
          acao === "cadastrar_mentor"
            ? "✅ Mentor cadastrado com sucesso!"
            : "✅ Mentor atualizado com sucesso!";

        ToastManager.mostrar(mensagem, "sucesso");

        // Fecha modal e recarrega mentores
        ModalManager.fechar("modal-form");
        await MentorManager.recarregarMentores();

        return true;
      } else {
        throw new Error("Resposta inesperada do servidor");
      }
    } catch (error) {
      console.error("Erro ao enviar formulário:", error);
      ToastManager.mostrar(
        `❌ Erro ao salvar mentor: ${error.message}`,
        "erro"
      );
      return false;
    } finally {
      LoaderManager.ocultar();
    }
  },

  // ✅ NOVA FUNÇÃO: Reseta completamente o formulário
  resetarFormulario() {
    try {
      const form = document.querySelector(".formulario-mentor-completo");
      if (form) {
        form.reset();
      }

      // Reseta elementos visuais
      this.atualizarElementosVisuaisNovoMentor();

      // Remove arquivo selecionado
      const inputFoto = document.getElementById("foto");
      if (inputFoto) {
        inputFoto.value = "";
      }

      console.log("✅ Formulário resetado");
    } catch (error) {
      console.error("Erro ao resetar formulário:", error);
    }
  },
};

// ✅ GERENCIADOR DE MÁSCARAS E FORMATAÇÃO
const MascaraManager = {
  // Aplica máscara de valor monetário
  aplicarMascaraValor(input) {
    if (!input) return;

    let bloqueioInicial = true;

    const aplicarMascara = () => {
      if (bloqueioInicial) {
        bloqueioInicial = false;
        return;
      }

      let valor = input.value.replace(/\D/g, "");

      if (valor === "") {
        input.value = "R$ 0,00";
        return;
      }

      if (valor.length < 3) {
        valor = valor.padStart(3, "0");
      }

      const reais = valor.slice(0, -2);
      const centavos = valor.slice(-2);
      input.value = `R$ ${parseInt(reais).toLocaleString("pt-BR")},${centavos}`;
    };

    input.addEventListener("input", aplicarMascara);
  },

  // Configura eventos do campo nome
  configurarCampoNome() {
    const campoNome = document.getElementById("nome");
    const nomePreview = document.querySelector(".mentor-nome-preview");

    if (!campoNome || !nomePreview) return;

    // Atualiza preview em tempo real com limite de caracteres
    campoNome.addEventListener("input", function () {
      if (this.value.length > CONFIG.LIMITE_CARACTERES_NOME) {
        this.value = this.value.slice(0, CONFIG.LIMITE_CARACTERES_NOME);
      }
      nomePreview.textContent = this.value;
    });

    // Aplica capitalização ao sair do campo
    campoNome.addEventListener("blur", function () {
      const nomeFormatado = Utils.capitalizarNome(this.value);
      this.value = nomeFormatado;
      nomePreview.textContent = nomeFormatado;
    });
  },
};

// ✅ GERENCIADOR DE UPLOAD DE IMAGEM
const ImagemManager = {
  // Mostra nome do arquivo e preview
  mostrarNomeArquivo(input) {
    const nomeArquivo = document.getElementById("nome-arquivo");
    const previewImg = document.getElementById("preview-img");
    const removerBtn = document.getElementById("remover-foto");

    if (!input.files || !input.files[0]) {
      this.removerImagem();
      return;
    }

    const arquivo = input.files[0];

    // Validação de tipo de arquivo
    if (!arquivo.type.startsWith("image/")) {
      ToastManager.mostrar(
        "❌ Por favor, selecione apenas arquivos de imagem",
        "erro"
      );
      input.value = "";
      this.removerImagem();
      return;
    }

    // Validação de tamanho (5MB)
    if (arquivo.size > 5 * 1024 * 1024) {
      ToastManager.mostrar("❌ A imagem deve ter no máximo 5MB", "erro");
      input.value = "";
      this.removerImagem();
      return;
    }

    if (nomeArquivo) nomeArquivo.textContent = arquivo.name;

    // Gera preview
    const reader = new FileReader();
    reader.onload = (e) => {
      if (previewImg) previewImg.src = e.target.result;
      if (removerBtn) removerBtn.style.display = "inline-block";
    };
    reader.onerror = () => {
      ToastManager.mostrar("❌ Erro ao processar imagem", "erro");
      this.removerImagem();
    };
    reader.readAsDataURL(arquivo);
  },

  // Remove imagem e restaura avatar padrão
  removerImagem() {
    const elementos = {
      "preview-img": CONFIG.AVATAR_PADRAO,
      foto: "",
      "nome-arquivo": "Nenhum arquivo selecionado",
    };

    Object.entries(elementos).forEach(([id, valor]) => {
      const elemento = document.getElementById(id);
      if (elemento) {
        if (elemento.tagName === "IMG") {
          elemento.src = valor;
        } else if (elemento.tagName === "INPUT") {
          elemento.value = valor;
        } else {
          elemento.textContent = valor;
        }
      }
    });

    const removerBtn = document.getElementById("remover-foto");
    if (removerBtn) removerBtn.style.display = "none";
  },
};

// ✅ GERENCIADOR DE DADOS DINÂMICOS
const DadosManager = {
  // Atualiza dados da banca e lucro via AJAX
  atualizarLucroEBancaViaAjax() {
    return fetch("dados_banca.php")
      .then((response) => {
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return response.json();
      })
      .then((data) => {
        if (!data.success) {
          throw new Error(data.message || "Resposta inválida do servidor");
        }

        this.atualizarElementosLucro(data);
        this.atualizarElementosBanca(data);

        // Calcula meta com dados atualizados
        const bancaFloat = Utils.getValorNumerico(data.banca_formatada);
        this.calcularMeta(bancaFloat);
      })
      .catch((error) => {
        console.error("Erro ao atualizar dados da banca:", error);
        ToastManager.mostrar("❌ Erro ao atualizar dados financeiros", "erro");
      });
  },

  // Atualiza elementos relacionados ao lucro
  atualizarElementosLucro(data) {
    const lucro = parseFloat(data.lucro) || 0;
    const lucroFormatado = Utils.formatarBRL(lucro);

    const { cor, rotulo } = this.obterEstiloLucro(lucro);

    // Atualiza valor do lucro
    const lucroTotalLabel = document.getElementById("valorLucroLabel");
    if (lucroTotalLabel) {
      lucroTotalLabel.textContent = lucroFormatado;
      lucroTotalLabel.style.color = cor;
    }

    // Atualiza rótulos de lucro
    this.atualizarRotulosLucro(rotulo, cor);

    // Atualiza elementos específicos
    const lucroValorEntrada = document.getElementById("lucro_valor_entrada");
    const lucroEntradasRotulo = document.getElementById(
      "lucro_entradas_rotulo"
    );

    if (lucroValorEntrada) {
      // Remove classes anteriores
      lucroValorEntrada.classList.remove(
        "saldo-positivo",
        "saldo-negativo",
        "saldo-neutro"
      );

      // Adiciona classe baseada no valor
      const classeCSS =
        lucro > 0
          ? "saldo-positivo"
          : lucro < 0
          ? "saldo-negativo"
          : "saldo-neutro";
      lucroValorEntrada.classList.add(classeCSS);
      lucroValorEntrada.textContent = lucroFormatado;
    }

    if (lucroEntradasRotulo) {
      lucroEntradasRotulo.textContent = rotulo;
    }
  },

  // Atualiza elementos relacionados à banca
  atualizarElementosBanca(data) {
    const valorBancaLabel = document.getElementById("valorBancaLabel");
    const valorTotalBancaLabel = document.getElementById(
      "valorTotalBancaLabel"
    );

    if (valorBancaLabel) valorBancaLabel.textContent = data.banca_formatada;
    if (valorTotalBancaLabel)
      valorTotalBancaLabel.textContent = data.banca_formatada;
  },

  // Obtém estilo baseado no valor do lucro
  obterEstiloLucro(lucro) {
    if (lucro > 0) {
      return { cor: "#009e42ff", rotulo: "Lucro" };
    } else if (lucro < 0) {
      return { cor: "#e92a15ff", rotulo: "Negativo" };
    } else {
      return { cor: "#7f8c8d", rotulo: "Neutro" };
    }
  },

  // Atualiza rótulos de lucro com observer para elementos dinâmicos
  atualizarRotulosLucro(rotulo, cor) {
    const atualizarRotulos = () => {
      const rotulos = document.querySelectorAll(".lucro-label-texto");
      if (rotulos.length > 0) {
        rotulos.forEach((el) => {
          el.textContent = rotulo;
          if (el.id !== "lucroLabel") {
            el.style.color = cor;
          }
        });
        return true;
      }
      return false;
    };

    if (!atualizarRotulos()) {
      // Usa MutationObserver para elementos que podem ser criados dinamicamente
      const observer = new MutationObserver((mutations, obs) => {
        if (atualizarRotulos()) {
          obs.disconnect();
        }
      });

      observer.observe(document.body, {
        childList: true,
        subtree: true,
      });

      // Timeout para evitar observer infinito
      setTimeout(() => observer.disconnect(), 5000);
    }
  },

  // Calcula e atualiza meta
  calcularMeta(bancaFloat) {
    // Implementação da lógica de cálculo de meta
    // Esta função deve ser implementada de acordo com as regras de negócio
    console.log("Calculando meta para banca:", bancaFloat);
  },
};

// ✅ GERENCIADOR DE MENTORES - VERSÃO CORRIGIDA
const MentorManager = {
  mentorAtualId: null,
  ultimoCardClicado: null,
  intervalUpdateId: null,

  // ✅ CORREÇÃO: Recarrega lista de mentores preservando estrutura CSS
  async recarregarMentores() {
    try {
      // ✅ INCLUIR PERÍODO ATUAL SEMPRE
      const formData = new FormData();
      if (typeof SistemaFiltroPeriodo !== "undefined") {
        formData.append("periodo", SistemaFiltroPeriodo.periodoAtual);
      }

      const response = await fetch("carregar-mentores.php", {
        method: "POST", // MUDANÇA: sempre POST com período
        body: formData,
        headers: {
          "Cache-Control": "no-cache",
          "X-Requested-With": "XMLHttpRequest",
        },
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const html = await response.text();
      const container = document.getElementById("listaMentores");

      if (!container) {
        throw new Error("Container de mentores não encontrado");
      }

      // ✅ CORREÇÃO: Preserva o estado do formulário antes de atualizar
      const formularioAberto =
        document.querySelector(".formulario-mentor")?.style.display === "block";
      const telaEdicaoAberta =
        document.getElementById("tela-edicao")?.style.display === "block";

      // Atualiza o conteúdo
      container.innerHTML = html;

      // ✅ CORREÇÃO: Reaplica eventos e estilos após recarregar
      this.aplicarEstilosCorretos();
      this.atualizarDashboard(container);
      this.adicionarEventosMentores(container);

      // ✅ CORREÇÃO: Restaura estado dos formulários se necessário
      if (formularioAberto && !telaEdicaoAberta) {
        // Mantém formulário aberto se estava aberto antes
        const formulario = document.querySelector(".formulario-mentor");
        if (formulario) {
          formulario.style.display = "block";
        }
      }

      console.log("✅ Mentores recarregados com sucesso");
    } catch (error) {
      console.error("Erro ao recarregar mentores:", error);
      ToastManager.mostrar(
        "❌ Erro ao carregar mentores: " + error.message,
        "erro"
      );
    }
  },

  // ✅ NOVA FUNÇÃO: Aplica estilos corretos aos cards
  aplicarEstilosCorretos() {
    const cards = document.querySelectorAll(".mentor-card");

    cards.forEach((card) => {
      // Garante que as classes CSS estão corretas
      if (
        !card.classList.contains("card-positivo") &&
        !card.classList.contains("card-negativo") &&
        !card.classList.contains("card-neutro")
      ) {
        card.classList.add("card-neutro");
      }

      // Garante que as imagens têm fallback
      const img = card.querySelector(".mentor-img");
      if (img && !img.hasAttribute("onerror")) {
        img.setAttribute(
          "onerror",
          "this.src='https://cdn-icons-png.flaticon.com/512/847/847969.png'"
        );
      }
    });

    // ✅ CORREÇÃO: Garante que os menus funcionam corretamente
    this.configurarMenusMentores();
  },

  // ✅ NOVA FUNÇÃO: Configura menus de três pontos
  configurarMenusMentores() {
    document.querySelectorAll(".menu-toggle").forEach((toggle) => {
      // Remove listeners antigos clonando o elemento
      const novoToggle = toggle.cloneNode(true);
      toggle.parentNode?.replaceChild(novoToggle, toggle);
    });

    // Reaplica o MenuManager
    if (typeof MenuManager !== "undefined" && MenuManager.inicializar) {
      MenuManager.inicializar();
    }
  },

  // ✅ CORREÇÃO MELHORADA: Atualiza dashboard com validação
  atualizarDashboard(container) {
    try {
      const updates = [
        {
          selector: "#total-green-dia",
          target: ".placar-green",
          attr: "green",
          fallback: "0",
        },
        {
          selector: "#total-red-dia",
          target: ".placar-red",
          attr: "red",
          fallback: "0",
        },
      ];

      updates.forEach(({ selector, target, attr, fallback }) => {
        const sourceEl = container.querySelector(selector);
        const targetEl = document.querySelector(target);

        if (sourceEl && targetEl) {
          const valor = sourceEl.dataset[attr] || fallback;
          targetEl.textContent = valor;
        } else if (targetEl) {
          targetEl.textContent = fallback;
        }
      });

      this.atualizarSaldo(container);
      this.atualizarMeta(container);
    } catch (error) {
      console.error("Erro ao atualizar dashboard:", error);
    }
  },

  // ✅ CORREÇÃO: Atualiza saldo com melhor tratamento de erros
  atualizarSaldo(container) {
    try {
      const totalMetaEl = container.querySelector("#saldo-dia");
      const valorSpan = document.querySelector(".valor-saldo");

      if (!totalMetaEl || !valorSpan) {
        console.warn("Elementos de saldo não encontrados");
        return;
      }

      const saldoTexto = totalMetaEl.dataset.total || "0,00";
      const valorNumerico = Utils.getValorNumerico("R$ " + saldoTexto);

      valorSpan.textContent = "R$ " + saldoTexto;

      // ✅ CORREÇÃO: Define cor baseada no valor com classes CSS
      valorSpan.classList.remove(
        "saldo-positivo",
        "saldo-negativo",
        "saldo-neutro"
      );

      if (valorNumerico > 0) {
        valorSpan.classList.add("saldo-positivo");
      } else if (valorNumerico < 0) {
        valorSpan.classList.add("saldo-negativo");
      } else {
        valorSpan.classList.add("saldo-neutro");
      }
    } catch (error) {
      console.error("Erro ao atualizar saldo:", error);
    }
  },

  // ✅ CORREÇÃO: Atualiza meta com validação melhorada
  atualizarMeta(container) {
    try {
      const metaDiv = container.querySelector("#meta-meia-unidade");
      const totalMetaEl = container.querySelector("#saldo-dia");
      const metaSpan = document.querySelector("#meta-dia");
      const rotuloMetaSpan = document.querySelector(".rotulo-meta");

      if (!totalMetaEl || !metaSpan || !rotuloMetaSpan) {
        console.warn("Elementos de meta não encontrados");
        return;
      }

      const valorMeta = metaDiv
        ? Utils.getValorNumerico(metaDiv.dataset.meta || "0")
        : 0;
      const valorSaldo = Utils.getValorNumerico(
        "R$ " + (totalMetaEl.dataset.total || "0")
      );
      const resultado = valorMeta - valorSaldo;

      this.configurarExibicaoMeta(
        resultado,
        valorSaldo,
        metaSpan,
        rotuloMetaSpan
      );
    } catch (error) {
      console.error("Erro ao atualizar meta:", error);
    }
  },

  // Configura exibição da meta (mantida igual)
  configurarExibicaoMeta(resultado, valorSaldo, metaSpan, rotuloMetaSpan) {
    let corResultado, resultadoFormatado, textoRotulo;

    if (resultado <= 0) {
      corResultado = "#DAA520";

      if (resultado < 0) {
        resultadoFormatado = `+ ${Utils.formatarBRL(Math.abs(resultado))}`;
        const sobraMeta = Utils.formatarBRL(valorSaldo + resultado);
        textoRotulo = `Meta: ${sobraMeta} <span style="font-size: 0.8em;">🏆</span>`;
      } else {
        resultadoFormatado = Utils.formatarBRL(resultado);
        textoRotulo = `Meta Batida! <span style="font-size: 0.8em;">🏆</span>`;
      }
    } else {
      corResultado = "#00a651";
      resultadoFormatado = Utils.formatarBRL(resultado);
      textoRotulo = valorSaldo === 0 ? "Meta do Dia" : "Restando P/ Meta";
    }

    metaSpan.innerHTML = resultadoFormatado;
    metaSpan.style.color = corResultado;
    rotuloMetaSpan.innerHTML = textoRotulo;
  },

  // ✅ CORREÇÃO MELHORADA: Adiciona eventos aos cards com debounce
  adicionarEventosMentores(container) {
    const cards = container.querySelectorAll(".mentor-card");

    cards.forEach((card) => {
      // Remove listeners anteriores clonando o elemento
      const novoCard = card.cloneNode(true);
      card.parentNode?.replaceChild(novoCard, card);

      // ✅ CORREÇÃO: Adiciona debounce para evitar cliques múltiplos
      const clickHandler = Utils.debounce((event) => {
        const alvo = event.target;
        const clicouEmBotao =
          alvo.closest(".btn-icon") ||
          alvo.closest(".menu-opcoes") ||
          alvo.closest(".menu-toggle") ||
          ["BUTTON", "I", "SPAN"].includes(alvo.tagName);

        if (clicouEmBotao) return;

        this.ultimoCardClicado = novoCard;
        this.mentorAtualId = null;
        FormularioValorManager.exibirFormularioMentor(novoCard);
        DadosManager.atualizarLucroEBancaViaAjax();
      }, 300);

      novoCard.addEventListener("click", clickHandler);
    });
  },

  // ✅ CORREÇÃO: Atualização automática mais inteligente
  iniciarAtualizacaoAutomatica() {
    if (this.intervalUpdateId) {
      clearInterval(this.intervalUpdateId);
    }

    this.intervalUpdateId = setInterval(() => {
      // Só atualiza se:
      // 1. Página está visível
      // 2. Não há formulários ou modais abertos
      // 3. Não há operações em andamento
      const formularioVisivel =
        document.querySelector(".formulario-mentor")?.style.display === "block";
      const modalAberto =
        document.querySelector(".modal")?.style.display === "block";
      const telaEdicaoAberta =
        document.getElementById("tela-edicao")?.style.display === "block";
      const loaderVisivel =
        document.getElementById("loader")?.style.display === "flex";

      const podeAtualizar =
        document.visibilityState === "visible" &&
        !formularioVisivel &&
        !modalAberto &&
        !telaEdicaoAberta &&
        !loaderVisivel;

      if (podeAtualizar) {
        // ✅ VERIFICAR SE HÁ FILTRO ATIVO ANTES DE RECARREGAR
        const temFiltroAtivo =
          typeof SistemaFiltroPeriodo !== "undefined" &&
          SistemaFiltroPeriodo.periodoAtual !== "dia";

        if (!temFiltroAtivo) {
          this.recarregarMentores();
        }
      }
    }, CONFIG.INTERVALO_ATUALIZACAO);
  },

  // ✅ NOVA FUNÇÃO: Para atualização automática
  pararAtualizacaoAutomatica() {
    if (this.intervalUpdateId) {
      clearInterval(this.intervalUpdateId);
      this.intervalUpdateId = null;
    }
  },
};

// ✅ GERENCIADOR DE FORMULÁRIO DE VALOR
const FormularioValorManager = {
  // Exibe formulário para cadastrar valor do mentor
  exibirFormularioMentor(card) {
    const formulario = document.querySelector(".formulario-mentor");
    if (!formulario) {
      console.error("❌ Formulário de mentor não encontrado");
      return;
    }

    const elementos = this.obterElementosFormulario(formulario);
    if (!elementos.todosPresentes) {
      console.error("❌ Elementos internos do formulário não encontrados");
      return;
    }

    this.preencherDadosFormulario(card, elementos);
    this.exibirFormulario(formulario);
    this.configurarCampoValor();
  },

  // Obtém elementos do formulário
  obterElementosFormulario(formulario) {
    const nomePreview = formulario.querySelector(".mentor-nome-preview");
    const fotoPreview = formulario.querySelector(".mentor-foto-preview");
    const idHidden = formulario.querySelector(".mentor-id-hidden");

    return {
      nomePreview,
      fotoPreview,
      idHidden,
      todosPresentes: !!(nomePreview && fotoPreview && idHidden),
    };
  },

  // Preenche dados do formulário
  preencherDadosFormulario(card, elementos) {
    const nomeMentor = card.getAttribute("data-nome") || "Mentor";
    const fotoMentor = card.getAttribute("data-foto") || "default.png";
    const idMentor = card.getAttribute("data-id") || "";

    elementos.nomePreview.textContent = nomeMentor;
    elementos.fotoPreview.src = fotoMentor;
    elementos.idHidden.value = idMentor;
  },

  // Exibe formulário
  exibirFormulario(formulario) {
    formulario.style.display = "block";
  },

  // Configura campo valor com delay para elementos carregarem
  configurarCampoValor() {
    setTimeout(() => {
      const campoValor = document.getElementById("valor");
      const unidadeEntrada = document.querySelector(
        "#listaMentores #unidade-entrada"
      );

      if (campoValor && unidadeEntrada) {
        const valorTexto = unidadeEntrada.textContent.trim();
        campoValor.value = valorTexto;
        campoValor.placeholder = valorTexto;

        MascaraManager.aplicarMascaraValor(campoValor);
      }
    }, 600);
  },

  // Processa submissão do formulário
  async processarSubmissao(formData) {
    try {
      const response = await fetch("cadastrar-valor.php", {
        method: "POST",
        body: formData,
      });

      if (!response.ok) throw new Error(`HTTP ${response.status}`);

      const resposta = await response.json();

      ToastManager.mostrar(resposta.mensagem, resposta.tipo);

      if (resposta.tipo === "sucesso") {
        this.resetarFormulario();
        await MentorManager.recarregarMentores();
        await DadosManager.atualizarLucroEBancaViaAjax();

        // Atualiza dados do modal se a função existir
        if (typeof atualizarDadosModal === "function") {
          atualizarDadosModal();
        }
      }
    } catch (error) {
      console.error("Erro ao enviar formulário:", error);
      ToastManager.mostrar("❌ Erro ao enviar dados", "erro");
    }
  },

  // Reseta formulário
  resetarFormulario() {
    const formMentor = document.getElementById("form-mentor");
    const formulario = document.querySelector(".formulario-mentor");

    if (formMentor) formMentor.reset();
    if (formulario) formulario.style.display = "none";
  },
};

// ✅ GERENCIADOR DE EXCLUSÕES
const ExclusaoManager = {
  // Confirmação simples de exclusão de mentor
  confirmarExclusaoMentor() {
    const id = document.getElementById("mentor-id")?.value;
    if (!id) {
      ToastManager.mostrar("❌ ID do mentor não encontrado", "erro");
      return;
    }

    if (confirm("Tem certeza que deseja excluir este mentor?")) {
      window.location.href = `gestao-diaria.php?excluir_mentor=${id}`;
    }
  },

  // Modal de confirmação visual para mentor
  abrirModalExclusaoMentor() {
    ModalManager.abrir("modal-confirmacao-exclusao");
  },

  fecharModalExclusaoMentor() {
    ModalManager.fechar("modal-confirmacao-exclusao");
  },

  confirmarExclusaoMentorModal() {
    const id = document.getElementById("mentor-id")?.value;
    if (!id) {
      ToastManager.mostrar("❌ ID do mentor não encontrado", "erro");
      return;
    }

    window.location.href = `gestao-diaria.php?excluir_mentor=${id}`;
  },

  // Exclusão de entrada
  async excluirEntrada(idEntrada) {
    const modal = document.getElementById("modal-confirmacao");
    if (!modal) {
      console.error("Modal de confirmação não encontrado");
      return;
    }

    return new Promise((resolve) => {
      const btnConfirmar = document.getElementById("btnConfirmar");
      const btnCancelar = document.getElementById("btnCancelar");

      // Remove listeners anteriores
      const novoConfirmar = btnConfirmar?.cloneNode(true);
      const novoCancelar = btnCancelar?.cloneNode(true);

      if (novoConfirmar && btnConfirmar?.parentNode) {
        btnConfirmar.parentNode.replaceChild(novoConfirmar, btnConfirmar);
      }
      if (novoCancelar && btnCancelar?.parentNode) {
        btnCancelar.parentNode.replaceChild(novoCancelar, btnCancelar);
      }

      modal.style.display = "flex";

      // Evento cancelar
      if (novoCancelar) {
        novoCancelar.addEventListener("click", () => {
          modal.style.display = "none";
          resolve(false);
        });
      }

      // Evento confirmar
      if (novoConfirmar) {
        novoConfirmar.addEventListener("click", async () => {
          modal.style.display = "none";
          await this.executarExclusaoEntrada(idEntrada);
          resolve(true);
        });
      }
    });
  },

  // Executa exclusão da entrada
  async executarExclusaoEntrada(idEntrada) {
    const idMentorBackup = MentorManager.mentorAtualId;
    const tela = document.getElementById("tela-edicao");
    const estaAberta = tela?.style.display === "block";

    LoaderManager.mostrar();

    try {
      const response = await fetch("excluir-entrada.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `id=${encodeURIComponent(idEntrada)}`,
      });

      if (!response.ok) throw new Error(`HTTP ${response.status}`);

      const mensagem = await response.text();
      const sucesso = mensagem.toLowerCase().includes("sucesso");

      ToastManager.mostrar(mensagem.trim(), sucesso ? "sucesso" : "aviso");

      if (sucesso) {
        await this.atualizarAposExclusao();
        TelaEdicaoManager.fechar();

        // Reabrir tela apropriada após exclusão
        setTimeout(() => {
          if (estaAberta && idMentorBackup) {
            TelaEdicaoManager.editarAposta(idMentorBackup);
          } else if (!estaAberta && MentorManager.ultimoCardClicado) {
            FormularioValorManager.exibirFormularioMentor(
              MentorManager.ultimoCardClicado
            );
          }
        }, 300);
      }
    } catch (error) {
      console.error("Erro ao excluir entrada:", error);
      ToastManager.mostrar(`❌ Falha ao excluir: ${error.message}`, "erro");
    } finally {
      LoaderManager.ocultar();
    }
  },

  // Atualiza dados após exclusão
  async atualizarAposExclusao() {
    try {
      await fetch("carregar-sessao.php?atualizar=1");
      await MentorManager.recarregarMentores();
      await DadosManager.atualizarLucroEBancaViaAjax();
    } catch (error) {
      console.error("Erro ao atualizar após exclusão:", error);
    }
  },
};

// ✅ GERENCIADOR DE LOADER
const LoaderManager = {
  mostrar() {
    const loader = document.getElementById("loader");
    if (loader) loader.style.display = "flex";
  },

  ocultar() {
    const loader = document.getElementById("loader");
    if (loader) loader.style.display = "none";
  },
};

// ✅ GERENCIADOR DA TELA DE EDIÇÃO
const TelaEdicaoManager = {
  // Abre tela de edição com efeito
  abrir() {
    const tela = document.getElementById("tela-edicao");
    if (!tela) return;

    tela.style.display = "block";
    setTimeout(() => tela.classList.remove("oculta"), 10);
  },

  // Fecha tela de edição
  fechar() {
    const tela = document.getElementById("tela-edicao");
    if (!tela) return;

    tela.classList.add("oculta");
    setTimeout(() => {
      tela.style.display = "none";
      tela.classList.remove("oculta");
    }, 300);
  },

  // Edita aposta do mentor
  async editarAposta(idMentor) {
    MentorManager.mentorAtualId = idMentor;

    const card = document.querySelector(`[data-id='${idMentor}']`);
    if (!card) {
      ToastManager.mostrar("❌ Mentor não encontrado", "erro");
      return;
    }

    // Atualiza informações do mentor na tela
    const nomeMentorEl = document.getElementById("nomeMentorEdicao");
    const fotoMentorEl = document.getElementById("fotoMentorEdicao");

    if (nomeMentorEl) nomeMentorEl.textContent = card.getAttribute("data-nome");
    if (fotoMentorEl) fotoMentorEl.src = card.getAttribute("data-foto");

    this.abrir();

    try {
      const response = await fetch(
        `filtrar-entradas.php?id=${idMentor}&tipo=hoje`
      );
      if (!response.ok) throw new Error(`HTTP ${response.status}`);

      const entradas = await response.json();
      this.mostrarResultados(entradas);
    } catch (error) {
      console.error("Erro ao carregar histórico:", error);
      const container = document.getElementById("resultado-filtro");
      if (container) {
        container.innerHTML =
          '<p style="color:red;">Erro ao carregar dados.</p>';
      }
    }
  },

  // Mostra resultados das entradas
  mostrarResultados(entradas) {
    const container = document.getElementById("resultado-filtro");
    if (!container) return;

    container.innerHTML = "";

    if (!entradas || entradas.length === 0) {
      container.innerHTML =
        '<p style="color:gray;">Nenhuma Entrada Cadastrada Hoje.</p>';
      return;
    }

    const fragment = document.createDocumentFragment();

    entradas.forEach((entrada) => {
      const card = this.criarCardEntrada(entrada);
      fragment.appendChild(card);
    });

    container.appendChild(fragment);
  },

  // Cria card para uma entrada
  criarCardEntrada(entrada) {
    const div = document.createElement("div");
    div.className = "entrada-card";

    const { info, cor } = this.processarDadosEntrada(entrada);

    div.style.borderLeft = `6px solid ${cor}`;
    div.innerHTML = `
      <div class="entrada-info">${info}</div>
      <div class="entrada-acoes">
        <button onclick="ExclusaoManager.excluirEntrada(${entrada.id})" 
                class="btn-icon btn-lixeira" 
                title="Excluir">
          <i class="fas fa-trash"></i>
        </button>
      </div>
    `;

    return div;
  },

  // Processa dados da entrada para exibição
  processarDadosEntrada(entrada) {
    const valorGreen = parseFloat(entrada.valor_green) || 0;
    const valorRed = parseFloat(entrada.valor_red) || 0;
    const dataCriacao = new Date(entrada.data_criacao);

    const dataFormatada = dataCriacao.toLocaleDateString("pt-BR");
    const horaFormatada = dataCriacao.toLocaleTimeString("pt-BR", {
      hour: "2-digit",
      minute: "2-digit",
    });

    let info = "";
    let cor = "#ccc";

    // Processa greens
    if (entrada.green > 0) {
      info += `<p><strong>Green:</strong> ${entrada.green}</p>`;
      cor = "#4CAF50";
    }

    // Processa reds
    if (entrada.red > 0) {
      info += `<p><strong>Red:</strong> ${entrada.red}</p>`;
      cor = "#e74c3c";
    }

    // Adiciona valores monetários
    if (valorGreen > 0) {
      info += `<p class="info-pequena"><strong>Valor:</strong> ${Utils.formatarBRL(
        valorGreen
      )}</p>`;
    }

    if (valorRed > 0) {
      info += `<p class="info-pequena"><strong>Valor:</strong> ${Utils.formatarBRL(
        valorRed
      )}</p>`;
    }

    // Adiciona data e hora
    info += `<p class="info-pequena"><strong>Data:</strong> ${dataFormatada} às ${horaFormatada}</p>`;

    return { info, cor };
  },
};

// ✅ GERENCIADOR DE MENU DE TRÊS PONTOS
const MenuManager = {
  inicializar() {
    document.addEventListener("click", (e) => {
      const isToggle = e.target.classList.contains("menu-toggle");

      // Fecha todos os menus primeiro
      document.querySelectorAll(".menu-opcoes").forEach((menu) => {
        menu.style.display = "none";
      });

      // Abre o menu clicado se for um toggle
      if (isToggle) {
        const opcoes = e.target.nextElementSibling;
        if (opcoes && opcoes.classList.contains("menu-opcoes")) {
          opcoes.style.display = "block";
          e.stopPropagation();
        }
      }
    });
  },
};

// ✅ INICIALIZAÇÃO PRINCIPAL
const App = {
  // Inicializa toda a aplicação
  async inicializar() {
    try {
      console.log("🚀 Iniciando aplicação...");

      await this.inicializarComponentes();
      this.configurarEventosGlobais();
      this.iniciarProcessosBackground();

      console.log("✅ Aplicação inicializada com sucesso");
    } catch (error) {
      console.error("❌ Erro na inicialização:", error);
      ToastManager.mostrar("❌ Erro na inicialização da aplicação", "erro");
    }
  },

  // Inicializa componentes principais
  async inicializarComponentes() {
    // Inicializa dados da banca
    await DadosManager.atualizarLucroEBancaViaAjax();

    // Carrega mentores
    await MentorManager.recarregarMentores();

    // Configura máscaras e formatação
    MascaraManager.configurarCampoNome();

    // Inicializa managers
    ModalManager.inicializarEventosGlobais();
    MenuManager.inicializar();
  },

  // Configura eventos globais
  configurarEventosGlobais() {
    // Toast inicial
    this.processarToastInicial();

    // Formulário de mentor
    this.configurarFormularioMentor();

    // Visibilidade da página
    this.configurarVisibilityChange();
  },

  // Processa toast inicial se existir
  processarToastInicial() {
    const toast = document.getElementById("toast");
    if (toast?.classList.contains("ativo")) {
      setTimeout(() => {
        toast.classList.remove("ativo");
      }, 3000);
    }
  },

  // Configura formulário de mentor
  configurarFormularioMentor() {
    const formMentor = document.getElementById("form-mentor");
    if (!formMentor) return;

    const botaoFechar = document.querySelector(".btn-fechar");
    const campoValor = document.getElementById("valor");

    // ✅ CORREÇÃO: Evento de submissão para formulário de mentor
    const formMentorCompleto = document.querySelector(
      ".formulario-mentor-completo"
    );
    if (formMentorCompleto) {
      formMentorCompleto.addEventListener("submit", async (e) => {
        e.preventDefault();
        await FormularioManager.processarSubmissaoMentor(e.target);
      });
    }

    // Evento de submissão para formulário de valor
    if (formMentor) {
      formMentor.addEventListener("submit", async (e) => {
        e.preventDefault();
        await this.processarSubmissaoFormulario(e.target);
      });
    }

    // Botão fechar
    if (botaoFechar) {
      botaoFechar.addEventListener("click", () => {
        FormularioValorManager.resetarFormulario();
      });
    }

    // Máscara no campo valor
    if (campoValor) {
      MascaraManager.aplicarMascaraValor(campoValor);
    }
  },

  // Processa submissão do formulário de valor
  async processarSubmissaoFormulario(form) {
    // Validação
    const opcaoSelecionada = form.querySelector('input[name="opcao"]:checked');
    if (!opcaoSelecionada) {
      ToastManager.mostrar("⚠️ Por favor, selecione Green ou Red.", "aviso");
      return;
    }

    // Formata valor
    const campoValor = form.querySelector("#valor");
    if (campoValor) {
      let valor = campoValor.value.replace(/\D/g, "").padStart(3, "0");
      const reais = valor.slice(0, -2);
      const centavos = valor.slice(-2);
      campoValor.value = `${reais}.${centavos}`;
    }

    // Submete formulário
    const formData = new FormData(form);

    try {
      const response = await fetch("cadastrar-valor.php", {
        method: "POST",
        body: formData,
      });

      if (!response.ok) throw new Error(`HTTP ${response.status}`);

      const resposta = await response.json();

      ToastManager.mostrar(resposta.mensagem, resposta.tipo);

      if (resposta.tipo === "sucesso") {
        FormularioValorManager.resetarFormulario();

        // ✅ ATUALIZAÇÃO SUPER RÁPIDA DA ÁREA DIREITA
        setTimeout(async () => {
          if (typeof atualizarAreaDireita === "function") {
            atualizarAreaDireita();
          }

          // Recarrega outros dados
          await MentorManager.recarregarMentores();
          await DadosManager.atualizarLucroEBancaViaAjax();

          if (typeof atualizarDadosModal === "function") {
            atualizarDadosModal();
          }
        }, 50); // ✅ Apenas 50ms
      }
    } catch (error) {
      console.error("Erro ao enviar formulário:", error);
      ToastManager.mostrar("❌ Erro ao enviar dados", "erro");
    }
  },

  // Configura evento de mudança de visibilidade
  configurarVisibilityChange() {
    document.addEventListener("visibilitychange", () => {
      if (document.visibilityState === "visible") {
        const formularioVisivel =
          document.querySelector(".formulario-mentor")?.style.display ===
          "block";
        if (!formularioVisivel) {
          MentorManager.recarregarMentores();
        }
      }
    });
  },

  // Inicia processos em background
  iniciarProcessosBackground() {
    // Atualização automática de mentores
    MentorManager.iniciarAtualizacaoAutomatica();
  },
};

// ✅ FUNÇÕES GLOBAIS PARA COMPATIBILIDADE
// Mantém compatibilidade com código HTML existente

// Funções de modal
window.abrirModal = () => ModalManager.abrir("modal-form");
window.fecharModal = () => ModalManager.fechar("modal-form");

// Funções de mentor
window.prepararFormularioNovoMentor = () =>
  FormularioManager.prepararNovoMentor();
window.editarMentor = (id) => FormularioManager.prepararEdicaoMentor(id);

// Funções de exclusão
window.excluirMentorDiretoConfirmacaoSimples = () =>
  ExclusaoManager.confirmarExclusaoMentor();
window.excluirMentorDireto = () => ExclusaoManager.abrirModalExclusaoMentor();
window.fecharModalExclusao = () => ExclusaoManager.fecharModalExclusaoMentor();
window.confirmarExclusaoMentor = () =>
  ExclusaoManager.confirmarExclusaoMentorModal();

// Funções de imagem
window.mostrarNomeArquivo = (input) => ImagemManager.mostrarNomeArquivo(input);
window.removerImagem = () => ImagemManager.removerImagem();

// Funções de edição
window.editarAposta = (id) => TelaEdicaoManager.editarAposta(id);
window.fecharTelaEdicao = () => TelaEdicaoManager.fechar();

// Função de formulário
window.fecharFormulario = () => FormularioValorManager.resetarFormulario();

// Função de atualização
window.atualizarLucroEBancaViaAjax = () =>
  DadosManager.atualizarLucroEBancaViaAjax();

// ✅ INICIALIZAÇÃO QUANDO DOM ESTIVER PRONTO
document.addEventListener("DOMContentLoaded", () => {
  App.inicializar();
});

// ✅ CLEANUP NA SAÍDA DA PÁGINA
window.addEventListener("beforeunload", () => {
  if (MentorManager.intervalUpdateId) {
    clearInterval(MentorManager.intervalUpdateId);
  }
});
//
//
//
//
//
//
//
//
//
//
//
//
// ========================================================================================================================
// // ✅  ATUALIZADO - META DO DIA COM SUBTRAÇÃO DO SALDO DO DIA
// ========================================================================================================================

const MetaDiariaManager = {
  // ✅ CONTROLE SIMPLES
  atualizandoAtualmente: false,

  // ✅ ATUALIZAR META DIÁRIA - VERSÃO ESTÁVEL
  async atualizarMetaDiaria() {
    if (this.atualizandoAtualmente) return null;

    this.atualizandoAtualmente = true;

    try {
      const response = await fetch("dados_banca.php", {
        method: "GET",
        headers: {
          "Cache-Control": "no-cache",
          "X-Requested-With": "XMLHttpRequest",
        },
      });

      if (!response.ok) throw new Error(`HTTP ${response.status}`);

      const data = await response.json();
      if (!data.success) throw new Error(data.message);

      // Aplicar período e atualizar
      const dadosComPeriodo = this.aplicarAjustePeriodo(data);
      this.atualizarTodosElementos(dadosComPeriodo);

      return dadosComPeriodo;
    } catch (error) {
      console.error("❌ Erro:", error);
      this.mostrarErroMeta();
      return null;
    } finally {
      this.atualizandoAtualmente = false;
    }
  },

  // ✅ APLICAR AJUSTE DE PERÍODO - VERSÃO ESTÁVEL
  aplicarAjustePeriodo(data) {
    const radioSelecionado = document.querySelector(
      'input[name="periodo"]:checked'
    );
    const periodo = radioSelecionado?.value || "dia";

    let metaFinal, rotuloFinal;

    switch (periodo) {
      case "mes":
        metaFinal = parseFloat(data.meta_mensal) || 0;
        rotuloFinal = "Meta do Mês"; // ✅ Mudado de "META DO MÊS"
        break;
      case "ano":
        metaFinal = parseFloat(data.meta_anual) || 0;
        rotuloFinal = "Meta do Ano"; // ✅ Mudado de "META DO ANO"
        break;
      default:
        metaFinal = parseFloat(data.meta_diaria) || 0;
        rotuloFinal = "Meta do Dia"; // ✅ Mudado de "META DO DIA"
        break;
    }

    return {
      ...data,
      meta_display: metaFinal,
      meta_display_formatada:
        "R$ " +
        metaFinal.toLocaleString("pt-BR", {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2,
        }),
      rotulo_periodo: rotuloFinal,
      periodo_ativo: periodo,
    };
  },

  // ✅ ATUALIZAR TODOS OS ELEMENTOS - VERSÃO ESTÁVEL
  atualizarTodosElementos(data) {
    // Calcular valores uma vez
    const saldoDia = parseFloat(data.lucro) || 0;
    const metaCalculada = parseFloat(data.meta_display) || 0;
    const bancaTotal = parseFloat(data.banca) || 0;
    const resultado = this.calcularMetaFinal(
      saldoDia,
      metaCalculada,
      bancaTotal,
      data
    );

    // Atualizar em sequência
    this.atualizarAreaDireita(data);
    this.atualizarModal(data);
    this.atualizarMetaElemento(resultado);
    this.atualizarRotulo(resultado.rotulo);
    this.atualizarValorExtra(resultado.valorExtra);
    this.atualizarBarraProgresso(resultado, data);
  },

  // ✅ ATUALIZAR ÁREA DIREITA - ESTÁVEL
  atualizarAreaDireita(data) {
    const porcentagemElement = document.getElementById("porcentagem-diaria");
    if (porcentagemElement && data.diaria_formatada) {
      porcentagemElement.textContent = data.diaria_formatada;
    }

    const valorUnidadeElement = document.getElementById("valor-unidade");
    if (valorUnidadeElement && data.unidade_entrada_formatada) {
      valorUnidadeElement.textContent = data.unidade_entrada_formatada;
    }
  },

  // ✅ ATUALIZAR MODAL - ESTÁVEL
  atualizarModal(data) {
    const valorBancaLabel = document.getElementById("valorBancaLabel");
    if (valorBancaLabel && data.banca_formatada) {
      valorBancaLabel.textContent = data.banca_formatada;
    }

    const valorLucroLabel = document.getElementById("valorLucroLabel");
    if (valorLucroLabel && data.lucro_formatado) {
      valorLucroLabel.textContent = data.lucro_formatado;
    }

    // ✅ APLICAR CORES DO LUCRO - USANDO CLASSES CSS
    const lucroValor = parseFloat(data.lucro) || 0;
    const iconeLucro = document.getElementById("iconeLucro");
    const lucroLabel = document.getElementById("lucroLabel");

    if (iconeLucro && lucroLabel && valorLucroLabel) {
      // Remover classes anteriores
      lucroLabel.className = lucroLabel.className.replace(
        /modal-lucro-\w+/g,
        ""
      );
      valorLucroLabel.className = valorLucroLabel.className.replace(
        /modal-lucro-\w+/g,
        ""
      );

      if (lucroValor > 0) {
        iconeLucro.className = "fa-solid fa-money-bill-trend-up";
        lucroLabel.classList.add("modal-lucro-positivo");
        valorLucroLabel.classList.add("modal-lucro-positivo");
      } else if (lucroValor < 0) {
        iconeLucro.className = "fa-solid fa-money-bill-trend-down";
        lucroLabel.classList.add("modal-lucro-negativo");
        valorLucroLabel.classList.add("modal-lucro-negativo");
      } else {
        iconeLucro.className = "fa-solid fa-money-bill-trend-up";
        lucroLabel.classList.add("modal-lucro-neutro");
        valorLucroLabel.classList.add("modal-lucro-neutro");
      }
    }
  },

  // ✅ CALCULAR META FINAL - LÓGICA CORRIGIDA PARA LUCRO EXTRA
  calcularMetaFinal(saldoDia, metaCalculada, bancaTotal, data) {
    let metaFinal,
      rotulo,
      statusClass,
      valorExtra = 0;

    // ✅ REGRA 1: Banca total <= 0 - Precisa depositar
    if (bancaTotal <= 0) {
      metaFinal = bancaTotal;
      rotulo = "Deposite p/ Começar"; // ✅ Mudado de "DEPOSITE P/ COMEÇAR"
      statusClass = "sem-banca";
      valorExtra = 0;
    }
    // ✅ REGRA 2: Meta foi batida E tem lucro extra (lucro > meta)
    else if (saldoDia > 0 && metaCalculada > 0 && saldoDia >= metaCalculada) {
      metaFinal = 0;
      rotulo = `${
        data.rotulo_periodo || "Meta" // ✅ Mudado de "META"
      } Batida! <i class='fa-solid fa-trophy'></i>`; // ✅ Mudado de "BATIDA!"
      statusClass = "meta-batida";
      valorExtra = saldoDia - metaCalculada;

      // ✅ VERIFICAÇÃO: Se não há lucro extra real, não mostrar
      if (valorExtra <= 0) {
        valorExtra = 0;
      }
    }
    // ✅ REGRA 3: Lucro negativo
    else if (saldoDia < 0) {
      metaFinal = metaCalculada - saldoDia;
      rotulo = `Restando p/ ${data.rotulo_periodo || "Meta"}`; // ✅ Mudado de "RESTANDO P/"
      statusClass = "negativo";
      valorExtra = 0;
    }
    // ✅ REGRA 4: Lucro zero
    else if (saldoDia === 0) {
      metaFinal = metaCalculada;
      rotulo = data.rotulo_periodo || "Meta do Dia"; // ✅ Mudado de "META DO DIA"
      statusClass = "neutro";
      valorExtra = 0;
    }
    // ✅ REGRA 5: Lucro positivo mas não bateu meta (saldo < meta)
    else {
      metaFinal = metaCalculada - saldoDia;
      rotulo = `Restando p/ ${data.rotulo_periodo || "Meta"}`; // ✅ Mudado de "RESTANDO P/"
      statusClass = "lucro";
      valorExtra = 0;
    }

    return {
      metaFinal,
      metaFinalFormatada: metaFinal.toLocaleString("pt-BR", {
        style: "currency",
        currency: "BRL",
      }),
      rotulo,
      statusClass,
      valorExtra,
    };
  },

  // ✅ ATUALIZAR META ELEMENTO - USANDO CLASSES CSS
  atualizarMetaElemento(resultado) {
    const metaValor =
      document.getElementById("meta-valor") ||
      document.querySelector(".widget-meta-valor");

    if (!metaValor) return;

    let valorTexto =
      metaValor.querySelector(".valor-texto") ||
      metaValor.querySelector("#valor-texto-meta");

    if (valorTexto) {
      valorTexto.textContent = resultado.metaFinalFormatada;
    } else {
      metaValor.innerHTML = `
        <i class="fa-solid fa-coins"></i>
        <span class="valor-texto" id="valor-texto-meta">${resultado.metaFinalFormatada}</span>
      `;
    }

    // ✅ APLICAR CLASSES CSS BASEADAS NO STATUS
    metaValor.className = metaValor.className.replace(
      /\bvalor-meta\s+\w+/g,
      ""
    );
    metaValor.classList.add("valor-meta", resultado.statusClass);
  },

  // ✅ ATUALIZAR RÓTULO - ESTÁVEL
  atualizarRotulo(rotulo) {
    const rotuloElement =
      document.getElementById("rotulo-meta") ||
      document.querySelector(".widget-meta-rotulo");

    if (rotuloElement) {
      rotuloElement.innerHTML = rotulo;
    }
  },

  // ✅ ATUALIZAR VALOR EXTRA - USANDO ESTRUTURA HTML LIMPA
  // ========================================
  // ADICIONAR NA FUNÇÃO atualizarValorExtra
  // ========================================

  // ✅ ATUALIZAR VALOR EXTRA - COM CLASSE NO BODY
  atualizarValorExtra(valorExtra) {
    const valorUltrapassouElement =
      document.getElementById("valor-ultrapassou");
    const valorExtraElement = document.getElementById("valor-extra");

    if (valorUltrapassouElement) {
      // ✅ VERIFICAÇÃO RIGOROSA: Só mostrar se realmente há lucro extra
      if (valorExtra > 0) {
        const valorFormatado = valorExtra.toLocaleString("pt-BR", {
          style: "currency",
          currency: "BRL",
        });

        // Atualizar o valor no span existente
        if (valorExtraElement) {
          valorExtraElement.textContent = valorFormatado;
        }

        // Mostrar o elemento
        valorUltrapassouElement.style.display = "flex";
        valorUltrapassouElement.classList.add("mostrar");

        // ✅ ADICIONAR CLASSE AO BODY PARA EFEITO
        document.body.classList.add("tem-lucro-extra");
      } else {
        // ✅ OCULTAR O ELEMENTO
        valorUltrapassouElement.style.display = "none";
        valorUltrapassouElement.classList.remove("mostrar");

        // ✅ REMOVER CLASSE DO BODY
        document.body.classList.remove("tem-lucro-extra");

        // ✅ LIMPAR VALOR
        if (valorExtraElement) {
          valorExtraElement.textContent = "R$ 0,00";
        }
      }
    }
  },

  // ✅ ATUALIZAR BARRA PROGRESSO - MODIFICADA
  // ✅ ATUALIZAR BARRA PROGRESSO - VERSÃO CORRIGIDA
  atualizarBarraProgresso(resultado, data) {
    const barraProgresso = document.getElementById("barra-progresso");
    const saldoInfo = document.getElementById("saldo-info");
    const porcentagemBarra = document.getElementById("porcentagem-barra");

    if (!barraProgresso) return;

    const saldoDia = parseFloat(data.lucro) || 0;
    const metaCalculada = parseFloat(data.meta_display) || 0;
    const bancaTotal = parseFloat(data.banca) || 0;

    // Calcular progresso
    let progresso = 0;
    if (bancaTotal > 0 && metaCalculada > 0) {
      if (resultado.statusClass === "meta-batida") {
        progresso = 100;
      } else if (saldoDia < 0) {
        progresso = -Math.min(Math.abs(saldoDia / metaCalculada) * 100, 100);
      } else {
        progresso = Math.max(
          0,
          Math.min(100, (saldoDia / metaCalculada) * 100)
        );
      }
    }

    const larguraBarra = Math.abs(progresso);

    // ✅ SISTEMA DE CORES USANDO APENAS CLASSES CSS
    let temLucroExtra = false;
    let classeCor = ""; // Para armazenar a classe de cor atual

    // ✅ REMOVER TODAS AS CLASSES DE COR ANTERIORES MAS MANTER widget-barra-progresso
    barraProgresso.className = barraProgresso.className.replace(
      /\bbarra-\w+/g,
      ""
    );

    // ✅ IMPORTANTE: Garantir que a classe base permaneça
    if (!barraProgresso.classList.contains("widget-barra-progresso")) {
      barraProgresso.classList.add("widget-barra-progresso");
    }

    // ✅ VERIFICAR SE REALMENTE TEM LUCRO EXTRA
    if (
      resultado.valorExtra > 0 &&
      resultado.statusClass === "meta-batida" &&
      saldoDia > metaCalculada
    ) {
      temLucroExtra = true;
      classeCor = "barra-lucro-extra"; // Dourado
      barraProgresso.classList.add(classeCor);
    } else {
      // Aplicar classe baseada no status
      classeCor = `barra-${resultado.statusClass}`;
      barraProgresso.classList.add(classeCor);
    }

    // ✅ ATUALIZAR APENAS A LARGURA VIA JAVASCRIPT - COR VIA CSS
    barraProgresso.style.width = `${larguraBarra}%`;
    // ✅ REMOVER qualquer backgroundColor inline que possa estar conflitando
    barraProgresso.style.backgroundColor = "";
    barraProgresso.style.background = "";

    // ✅ PORCENTAGEM COM FUNDO COLORIDO - ALTURA TOTAL
    if (porcentagemBarra) {
      const porcentagemTexto = Math.round(progresso) + "%";

      // ✅ CRIAR ESTRUTURA COM FUNDO QUE OCUPA ALTURA TOTAL
      porcentagemBarra.innerHTML = `
      <span class="porcentagem-fundo ${classeCor}">${porcentagemTexto}</span>
    `;

      // Adicionar classe especial para valores pequenos
      if (larguraBarra <= 10) {
        porcentagemBarra.classList.add("pequeno");
      } else {
        porcentagemBarra.classList.remove("pequeno");
      }

      if (larguraBarra <= 0) {
        porcentagemBarra.classList.add("oculta");
      } else {
        porcentagemBarra.classList.remove("oculta");
      }
    }

    // ✅ SALDO INFO COM ÍCONE DINÂMICO
    // ✅ PARTE DO SALDO NA FUNÇÃO atualizarBarraProgresso
    // Substitua apenas esta parte na sua função:

    // ✅ SALDO INFO COM ESTRUTURA CORRETA PARA CORES SEPARADAS
    if (saldoInfo) {
      const saldoFormatado = saldoDia.toLocaleString("pt-BR", {
        style: "currency",
        currency: "BRL",
      });

      // ✅ DEFINIR TEXTO E ÍCONE BASEADO NO STATUS
      let textoSaldo = "Saldo";
      let iconeClass = "fa-solid fa-wallet"; // Ícone padrão

      if (saldoDia > 0) {
        textoSaldo = "Lucro";
        iconeClass = "fa-solid fa-chart-line"; // Ícone de lucro
      } else if (saldoDia < 0) {
        textoSaldo = "Negativo";
        iconeClass = "fa-solid fa-arrow-trend-down"; // Ícone de negativo
      } else {
        textoSaldo = "Saldo";
        iconeClass = "fa-solid fa-wallet"; // Ícone de saldo neutro
      }

      // ✅ ESTRUTURA HTML COM SPANS SEPARADOS PARA RÓTULO E VALOR
      saldoInfo.innerHTML = `
    <i class="${iconeClass}"></i>
    <span class="saldo-info-rotulo">${textoSaldo}:</span>
    <span class="saldo-info-valor">${saldoFormatado}</span>
  `;

      // ✅ APLICAR CLASSES CSS BASEADAS NO STATUS
      saldoInfo.className =
        saldoDia > 0
          ? "saldo-positivo"
          : saldoDia < 0
          ? "saldo-negativo"
          : "saldo-zero";
    }
  },

  // ✅ CONFIGURAR LISTENERS - ESTÁVEL
  configurarListenersPeriodo() {
    const radiosPeriodo = document.querySelectorAll('input[name="periodo"]');
    radiosPeriodo.forEach((radio) => {
      radio.addEventListener("change", () => {
        this.atualizarMetaDiaria();
      });
    });
  },

  // ✅ MOSTRAR ERRO - USANDO CLASSES CSS
  mostrarErroMeta() {
    const metaElement = document.getElementById("meta-valor");
    if (metaElement) {
      metaElement.innerHTML =
        '<i class="fa-solid fa-coins"></i><span class="valor-texto loading-text">R$ 0,00</span>';
    }
  },

  // ✅ APLICAR ANIMAÇÃO - USANDO CLASSES CSS
  aplicarAnimacao(elemento) {
    elemento.classList.add("atualizado");
    setTimeout(() => {
      elemento.classList.remove("atualizado");
    }, 1500);
  },

  // ✅ INICIALIZAÇÃO - ESTÁVEL
  inicializar() {
    // Loading simples
    const metaElement = document.getElementById("meta-valor");
    if (metaElement) {
      metaElement.innerHTML =
        '<i class="fa-solid fa-coins"></i><span class="valor-texto loading-text">Calculando...</span>';
    }

    // Configurar listeners
    this.configurarListenersPeriodo();

    // Primeira atualização
    this.atualizarMetaDiaria();
  },
};

// ========================================
// INTERCEPTAÇÃO AJAX - ESTÁVEL
// ========================================

function configurarInterceptadores() {
  const originalFetch = window.fetch;

  window.fetch = async function (...args) {
    const response = await originalFetch.apply(this, args);

    if (
      args[0] &&
      typeof args[0] === "string" &&
      args[0].includes("dados_banca.php") &&
      response.ok
    ) {
      setTimeout(() => {
        MetaDiariaManager.atualizarMetaDiaria();
      }, 50);
    }

    return response;
  };

  const originalXHR = window.XMLHttpRequest;
  function newXHR() {
    const xhr = new originalXHR();
    const originalSend = xhr.send;

    xhr.send = function (...args) {
      xhr.addEventListener("load", function () {
        if (
          xhr.responseURL &&
          xhr.responseURL.includes("dados_banca.php") &&
          xhr.status === 200
        ) {
          setTimeout(() => {
            MetaDiariaManager.atualizarMetaDiaria();
          }, 50);
        }
      });

      return originalSend.apply(this, args);
    };

    return xhr;
  }

  window.XMLHttpRequest = newXHR;
}

// ========================================
// FUNÇÕES GLOBAIS - ESTÁVEIS
// ========================================

window.atualizarMetaDiaria = () => {
  return MetaDiariaManager.atualizarMetaDiaria();
};

window.forcarAtualizacaoMeta = () => {
  MetaDiariaManager.atualizandoAtualmente = false;
  return MetaDiariaManager.atualizarMetaDiaria();
};

window.alterarPeriodo = (periodo) => {
  const radio = document.querySelector(
    `input[name="periodo"][value="${periodo}"]`
  );
  if (radio) {
    radio.checked = true;
    MetaDiariaManager.atualizarMetaDiaria();
    return true;
  }
  return false;
};

// ========================================
// ATALHOS SIMPLES E ESTÁVEIS
// ========================================

window.$ = {
  force: () => forcarAtualizacaoMeta(),
  dia: () => alterarPeriodo("dia"),
  mes: () => alterarPeriodo("mes"),
  ano: () => alterarPeriodo("ano"),

  test: () => {
    console.log("🧪 Teste básico:");
    alterarPeriodo("dia");
    console.log("✅ DIA");
    setTimeout(() => {
      alterarPeriodo("mes");
      console.log("✅ MÊS");
    }, 1000);
    setTimeout(() => {
      alterarPeriodo("ano");
      console.log("✅ ANO");
    }, 2000);
    setTimeout(() => {
      alterarPeriodo("dia");
      console.log("✅ Volta DIA");
    }, 3000);
    return "🎯 Teste iniciado";
  },

  info: () => {
    const metaElement = document.getElementById("meta-valor");
    const rotuloElement = document.getElementById("rotulo-meta");
    const barraElement = document.getElementById("barra-progresso");
    const extraElement = document.getElementById("valor-ultrapassou");

    const info = {
      meta: !!metaElement,
      rotulo: !!rotuloElement,
      barra: !!barraElement,
      extra: !!extraElement,
      metaContent: metaElement ? metaElement.textContent : "N/A",
      extraVisivel: extraElement
        ? !extraElement.classList.contains("oculta")
        : false,
      atualizando: MetaDiariaManager.atualizandoAtualmente,
    };

    console.log("📊 Info:", info);
    return "✅ Info verificada";
  },

  // ✅ TESTE ESPECÍFICO DAS CORES DA BARRA
  testCores: () => {
    console.log("🎨 Testando cores da barra de progresso...");

    const barra = document.getElementById("barra-progresso");
    if (!barra) {
      console.error("❌ Barra de progresso não encontrada!");
      return "❌ Erro: elemento não encontrado";
    }

    const coresTeste = [
      {
        classe: "barra-lucro",
        cor: "#4CAF50",
        desc: "Verde - Lucro Normal",
        progresso: 75,
      },
      {
        classe: "barra-meta-batida",
        cor: "#2196F3",
        desc: "Azul - Meta Batida",
        progresso: 100,
      },
      {
        classe: "barra-lucro-extra",
        cor: "#FFD700",
        desc: "Dourado - Lucro Extra",
        progresso: 100,
      },
      {
        classe: "barra-negativo",
        cor: "#f44336",
        desc: "Vermelho - Negativo",
        progresso: 25,
      },
      {
        classe: "barra-neutro",
        cor: "#95a5a6",
        desc: "Cinza - Neutro",
        progresso: 0,
      },
      {
        classe: "barra-sem-banca",
        cor: "#e67e22",
        desc: "Laranja - Sem Banca",
        progresso: 0,
      },
    ];

    coresTeste.forEach((teste, index) => {
      setTimeout(() => {
        console.log(`🎨 Aplicando: ${teste.desc}`);

        // Limpar classes anteriores
        barra.className = barra.className.replace(/\bbarra-\w+/g, "");

        // Limpar qualquer style inline
        barra.style.backgroundColor = "";
        barra.style.background = "";

        // Aplicar nova classe
        barra.classList.add(teste.classe);
        barra.style.width = `${teste.progresso}%`;

        // Verificar se a cor foi aplicada
        setTimeout(() => {
          const computedStyle = window.getComputedStyle(barra);
          const corAplicada = computedStyle.backgroundColor;

          console.log(`  ✅ Classe: ${teste.classe}`);
          console.log(`  🎯 Cor esperada: ${teste.cor}`);
          console.log(`  🎨 Cor aplicada: ${corAplicada}`);
          console.log(`  📏 Largura: ${teste.progresso}%`);

          // Verificar se as classes estão presentes
          console.log(`  📋 Classes na barra: ${barra.className}`);
        }, 100);
      }, index * 1500);
    });

    return "🎨 Teste de cores iniciado - 6 cores em 9s";
  },

  // ✅ FORÇAR LIMPEZA DE ESTILOS INLINE
  limparEstilos: () => {
    console.log("🧹 Limpando estilos inline da barra...");

    const barra = document.getElementById("barra-progresso");
    if (barra) {
      // Remover todos os estilos inline que podem conflitar
      barra.style.backgroundColor = "";
      barra.style.background = "";
      barra.removeAttribute("style");

      // Recriar o style apenas com largura
      barra.style.width = "50%";

      console.log("✅ Estilos inline removidos");
      console.log("📏 Largura resetada para 50%");
      console.log("📋 Classes atuais:", barra.className);

      return "✅ Limpeza concluída";
    } else {
      return "❌ Barra não encontrada";
    }
  },
};

// ========================================
// INICIALIZAÇÃO - ESTÁVEL
// ========================================

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", () => {
    MetaDiariaManager.inicializar();
    configurarInterceptadores();
  });
} else {
  MetaDiariaManager.inicializar();
  configurarInterceptadores();
}

console.log("✅ Sistema Meta Diária - VERSÃO ATUALIZADA!");
console.log("📱 Comandos:");
console.log("  $.force() - Forçar atualização");
console.log("  $.test() - Teste de períodos");
console.log("  $.info() - Ver status");
console.log("  $.testCores() - Testar cores da barra");
console.log("  $.limparEstilos() - Limpar estilos inline");

// ========================================================================================================================
// // ✅ FIM ATUALIZADO - META DO DIA COM SUBTRAÇÃO DO SALDO DO DIA
// ========================================================================================================================
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
// ========================================================================================================================
// FILTRO POR PERIODO DIA MES ANO DO CAMPO MENTORES
// ========================================================================================================================

function formatarDiaCurto() {
  const diasSemana = [
    "Domingo",
    "Segunda-feira",
    "Terça-feira",
    "Quarta-feira",
    "Quinta-feira",
    "Sexta-feira",
    "Sábado",
  ];
  const hoje = new Date();
  const diaSemana = diasSemana[hoje.getDay()];
  const dia = String(hoje.getDate()).padStart(2, "0");
  const mes = String(hoje.getMonth() + 1).padStart(2, "0");

  return `${diaSemana} - ${dia}/${mes}`;
}

function atualizarIndicadorPeriodoHeader(periodo) {
  const dataAtual = document.getElementById("data-atual");
  const icone = document.querySelector(".data-texto-compacto i");

  if (!dataAtual) return;

  const configuracoes = {
    dia: {
      texto: formatarDiaCurto(),
      icone: "fa-calendar-day",
    },
    mes: {
      texto: SistemaFiltroPeriodo.obterMesAtual(),
      icone: "fa-calendar-days",
    },
    ano: {
      texto: `Ano ${new Date().getFullYear()}`,
      icone: "fa-calendar",
    },
  };

  const config = configuracoes[periodo] || configuracoes.dia;

  dataAtual.style.opacity = "0";
  setTimeout(() => {
    dataAtual.textContent = config.texto;
    dataAtual.style.opacity = "1";
    dataAtual.style.animation = "fadeInScale 0.5s ease";
  }, 200);

  if (icone) {
    icone.className = `fa-solid ${config.icone}`;
    icone.style.color = "#00aaff";
  }
}

const SistemaFiltroPeriodo = {
  periodoAtual: "dia",

  inicializar() {
    const dataAtual = document.getElementById("data-atual");
    if (dataAtual) dataAtual.textContent = ""; // limpa conteúdo inicial

    const radios = document.querySelectorAll(".periodo-radio");
    radios.forEach((radio) => {
      radio.addEventListener("change", (e) => {
        if (e.target.checked) {
          this.alterarPeriodo(e.target.value);
        }
      });
    });

    const radioDia = document.querySelector('.periodo-radio[value="dia"]');
    if (radioDia) {
      radioDia.checked = true;
      radioDia.closest(".periodo-opcao").classList.add("ativo");
    }

    atualizarIndicadorPeriodoHeader("dia");
  },
  async alterarPeriodo(periodo) {
    this.periodoAtual = periodo;
    this.atualizarBotoesVisuais(periodo);
    this.mostrarLoading();

    try {
      const formData = new FormData();
      formData.append("periodo", periodo);

      const response = await fetch("carregar-mentores.php", {
        method: "POST",
        body: formData,
      });

      if (!response.ok) throw new Error("Erro ao carregar dados");

      const html = await response.text();
      const container = document.getElementById("listaMentores");
      if (container) {
        container.innerHTML = html;
        this.reaplicarEventos();
        this.atualizarPlacar();
        atualizarIndicadorPeriodoHeader(periodo);
      }
    } catch (error) {
      this.mostrarErro("Erro ao carregar dados do período");
    } finally {
      this.ocultarLoading();
    }
  },

  atualizarBotoesVisuais(periodo) {
    document.querySelectorAll(".periodo-opcao").forEach((opcao) => {
      opcao.classList.remove("ativo");
    });

    const radioSelecionado = document.querySelector(
      `.periodo-radio[value="${periodo}"]`
    );
    if (radioSelecionado) {
      radioSelecionado.closest(".periodo-opcao").classList.add("ativo");
    }
  },

  reaplicarEventos() {
    const cards = document.querySelectorAll(".mentor-card");
    cards.forEach((card) => {
      card.addEventListener("click", function (e) {
        if (!e.target.closest("button") && !e.target.closest(".menu-toggle")) {
          if (typeof FormularioValorManager !== "undefined") {
            FormularioValorManager.exibirFormularioMentor(this);
          }
        }
      });
    });

    document.querySelectorAll(".menu-toggle").forEach((toggle) => {
      toggle.addEventListener("click", function (e) {
        e.stopPropagation();
        const menu = this.nextElementSibling;
        if (menu) {
          menu.style.display =
            menu.style.display === "block" ? "none" : "block";
        }
      });
    });

    document.addEventListener("click", () => {
      document.querySelectorAll(".menu-opcoes").forEach((menu) => {
        menu.style.display = "none";
      });
    });
  },

  atualizarPlacar() {
    const totalGreenEl = document.querySelector("#total-green-dia");
    const totalRedEl = document.querySelector("#total-red-dia");

    if (totalGreenEl && totalRedEl) {
      const totalGreen = totalGreenEl.dataset.green || "0";
      const totalRed = totalRedEl.dataset.red || "0";

      const placarGreen = document.querySelector(".placar-green");
      const placarRed = document.querySelector(".placar-red");

      if (placarGreen) placarGreen.textContent = totalGreen;
      if (placarRed) placarRed.textContent = totalRed;
    }

    this.atualizarValoresGerais();
  },

  atualizarValoresGerais() {
    const saldoDiaEl = document.querySelector("#saldo-dia");
    if (saldoDiaEl) {
      const saldoTotal = saldoDiaEl.dataset.total || "R$ 0,00";
      const elementosSaldo = document.querySelectorAll(
        ".valor-saldo, .saldo-total"
      );
      elementosSaldo.forEach((el) => {
        if (el) el.textContent = saldoTotal;
      });
    }
  },

  mostrarLoading() {
    let loader = document.getElementById("loader-filtro");
    if (!loader) {
      loader = document.createElement("div");
      loader.id = "loader-filtro";
      loader.innerHTML = `
        <div class="loader-overlay">
          <div class="loader-spinner"></div>
          <p>Carregando período...</p>
        </div>
      `;
      document.body.appendChild(loader);
    }
    loader.style.display = "block";
  },

  ocultarLoading() {
    const loader = document.getElementById("loader-filtro");
    if (loader) {
      loader.style.display = "none";
    }
  },

  mostrarErro(mensagem) {
    if (typeof ToastManager !== "undefined") {
      ToastManager.mostrar(mensagem, "erro");
    } else {
      alert(mensagem);
    }
  },

  obterMesAtual() {
    const meses = [
      "Janeiro",
      "Fevereiro",
      "Março",
      "Abril",
      "Maio",
      "Junho",
      "Julho",
      "Agosto",
      "Setembro",
      "Outubro",
      "Novembro",
      "Dezembro",
    ];
    const data = new Date();
    return `${meses[data.getMonth()]} de ${data.getFullYear()}`;
  },

  atualizarPeriodoAtual() {
    this.alterarPeriodo(this.periodoAtual);
  },
};

document.addEventListener("DOMContentLoaded", () => {
  setTimeout(() => {
    SistemaFiltroPeriodo.inicializar();
  }, 500);
});

if (typeof FormularioValorManager !== "undefined") {
  const originalProcessar = FormularioValorManager.processarSubmissao;
  FormularioValorManager.processarSubmissao = async function (formData) {
    const resultado = await originalProcessar.call(this, formData);
    if (resultado) {
      setTimeout(() => {
        SistemaFiltroPeriodo.atualizarPeriodoAtual();
      }, 500);
    }
    return resultado;
  };
}

const estilosAdicionais = `
<style>
@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.periodo-label {
  transition: all 0.3s ease;
}

.periodo-label:hover {
  transform: translateY(-2px);
}
</style>
`;

if (!document.getElementById("estilos-filtro-periodo")) {
  const estilosEl = document.createElement("div");
  estilosEl.id = "estilos-filtro-periodo";
  estilosEl.innerHTML = estilosAdicionais;
  document.head.appendChild(estilosEl);
}

window.SistemaFiltroPeriodo = SistemaFiltroPeriodo;
window.debugFiltro = () => {
  console.log("🔍 Debug Filtro:", {
    periodoAtual: SistemaFiltroPeriodo.periodoAtual,
    radios: document.querySelectorAll(".periodo-radio").length,
    mentores: document.querySelectorAll(".mentor-card").length,
  });
};

console.log("✅ Sistema de Filtro por Período carregado!");
console.log("💡 Use debugFiltro() para informações de debug");

// ========================================================================================================================
// FIM FILTRO POR PERIODO DIA MES ANO DO CAMPO MENTORES
// ========================================================================================================================
//
//
//
//
//
//
//
//
//
// ========================================================================================================================
// FILTRO MOSTRA A MENSAGEM DA DATA - DIA MES ANO DENTRO DA DIVE DATA TOPO
// ========================================================================================================================

// ========================================================================================================================
// FIM FILTRO MOSTRA A MENSAGEM DA DATA - DIA MES ANO DENTRO DA DIVE DATA TOPO
// ========================================================================================================================
