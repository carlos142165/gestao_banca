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
      const response = await fetch("carregar-mentores.php", {
        method: "GET",
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
        this.recarregarMentores();
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

// ✅ ATUALIZA A META DO DIA DO CAMPO META DIARIA DA PAGINA

// ✅ JAVASCRIPT ATUALIZADO - META COM SUBTRAÇÃO DO SALDO DO DIA

// ========================================
// META DIÁRIA MANAGER - VERSÃO COMPLETA CORRIGIDA
// ========================================

const MetaDiariaManager = {
  // Calcula e atualiza a meta diária
  async atualizarMetaDiaria() {
    try {
      console.log("🔄 Iniciando atualização da meta diária...");

      const response = await fetch("dados_banca.php", {
        method: "GET",
        headers: {
          "Cache-Control": "no-cache",
          "X-Requested-With": "XMLHttpRequest",
        },
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const data = await response.json();
      console.log("📊 Dados recebidos do PHP:", data);

      if (!data.success) {
        throw new Error(data.message || "Erro na resposta do servidor");
      }

      // Atualiza o elemento da meta na tela
      this.atualizarElementoMeta(data);

      console.log(
        "✅ Meta diária atualizada:",
        data.meta_diaria_brl || data.meta_diaria_formatada
      );
      return data;
    } catch (error) {
      console.error("❌ Erro ao atualizar meta diária:", error);
      if (typeof ToastManager !== "undefined") {
        ToastManager.mostrar("❌ Erro ao calcular meta diária", "erro");
      }

      // Em caso de erro, mostra valor padrão
      this.mostrarErroMeta();
      return null;
    }
  },

  // ✅ FUNÇÃO ATUALIZADA: Integra com os dados do seu PHP
  atualizarElementoMeta(data) {
    console.log("🎯 Atualizando elemento meta com dados:", data);

    // ✅ BUSCAR ELEMENTO COM MÚLTIPLAS ESTRATÉGIAS
    const possiveisElementos = [
      document.getElementById("meta-diaria-ajax"),
      document.getElementById("meta-valor"),
      document.querySelector(".meta-valor"),
      document.querySelector(".valor-meta"),
      document.querySelector("[data-meta]"),
    ];

    const metaElement = possiveisElementos.find((el) => el !== null);
    const rotuloElement =
      document.querySelector(".rotulo-meta") ||
      document.getElementById("rotulo-meta");

    if (!metaElement) {
      console.warn("⚠️ Elemento da meta não encontrado!");
      console.log(
        "Tentou buscar:",
        possiveisElementos.map(
          (el, i) => `${i}: ${el ? "ENCONTRADO" : "NÃO ENCONTRADO"}`
        )
      );
      return;
    }

    console.log("✅ Elemento da meta encontrado:", metaElement);

    // ✅ VERIFICAR SE TEM DADOS NECESSÁRIOS
    if (
      !data.meta_diaria_brl &&
      !data.meta_diaria_formatada &&
      !data.meta_diaria
    ) {
      console.warn("⚠️ Dados da meta não encontrados no retorno do PHP");
      return;
    }

    // Remove texto de loading se existir
    const loadingText = metaElement.querySelector(".loading-text");
    if (loadingText) {
      loadingText.remove();
    }

    // ✅ USAR DADOS DO SEU PHP
    const saldoDia = parseFloat(data.lucro) || 0; // Lucro do dia
    const metaCalculada = parseFloat(data.meta_diaria) || 0; // Meta calculada
    const bancaTotal = parseFloat(data.banca) || 0; // Banca total

    console.log("📊 Valores extraídos:", {
      saldoDia,
      metaCalculada,
      bancaTotal,
    });

    // ✅ APLICAR SUAS REGRAS DE NEGÓCIO
    let metaFinal, rotulo, statusClass;

    // REGRA 1: Banca total <= 0 - Precisa depositar
    if (bancaTotal <= 0) {
      metaFinal = bancaTotal;
      rotulo = "DEPOSITE P/ COMEÇAR";
      statusClass = "sem-banca";
    }
    // REGRA 2: Meta foi batida (lucro >= meta)
    else if (saldoDia >= metaCalculada) {
      metaFinal = 0;
      rotulo = "META BATIDA! <i class='fa-solid fa-trophy'></i>";
      statusClass = "meta-batida";

      // Mostrar valor extra
      const valorExtra = saldoDia - metaCalculada;
      this.mostrarValorExtra(valorExtra);
    }
    // REGRA 3: Lucro negativo
    else if (saldoDia < 0) {
      metaFinal = metaCalculada - saldoDia; // Meta + prejuízo
      rotulo = "RESTANDO P/ META";
      statusClass = "negativo";
    }
    // REGRA 4: Lucro zero
    else if (saldoDia === 0) {
      metaFinal = metaCalculada;
      rotulo = "META DO DIA";
      statusClass = "neutro";
    }
    // REGRA 5: Lucro positivo mas não bateu meta
    else {
      metaFinal = metaCalculada - saldoDia;
      rotulo = "RESTANDO P/ META";
      statusClass = "lucro";
    }

    // Formatar valor final
    const metaFinalFormatada = metaFinal.toLocaleString("pt-BR", {
      style: "currency",
      currency: "BRL",
    });

    console.log("💰 Meta final calculada:", {
      metaFinal,
      metaFinalFormatada,
      rotulo,
      statusClass,
    });

    // ✅ ATUALIZAR ELEMENTO PRINCIPAL COM MÚLTIPLAS ESTRATÉGIAS
    this.atualizarElementoComEstrategias(
      metaElement,
      metaFinalFormatada,
      statusClass
    );

    // Atualizar rótulo
    if (rotuloElement) {
      rotuloElement.innerHTML = rotulo;
      console.log("✅ Rótulo atualizado:", rotulo);
    }

    // ✅ APLICAR ANIMAÇÃO
    this.aplicarAnimacao(metaElement);

    // ✅ LOG COM DADOS DO SEU PHP
    console.log("🎯 Meta calculada com seus dados:", {
      bancaTotal: bancaTotal,
      metaCalculada: metaCalculada,
      lucroDia: saldoDia,
      metaFinal: metaFinal,
      metaFormatada: metaFinalFormatada,
      rotulo: rotulo,
      statusClass: statusClass,
      calculoDetalhado: data.calculo_detalhado,
    });
  },

  // ✅ FUNÇÃO PARA ATUALIZAR ELEMENTO COM MÚLTIPLAS ESTRATÉGIAS
  atualizarElementoComEstrategias(elemento, valor, statusClass) {
    // Estratégia 1: Tentar encontrar .valor-texto
    let valorTexto = elemento.querySelector(".valor-texto");

    if (valorTexto) {
      console.log("✅ Estratégia 1: Atualizando .valor-texto");
      valorTexto.textContent = valor;
    } else {
      // Estratégia 2: Verificar se tem ícone e criar estrutura
      const icone = elemento.querySelector("i.fa-solid, .fa-coins");

      if (icone) {
        console.log("✅ Estratégia 2: Criando estrutura com ícone");
        elemento.innerHTML = "";
        elemento.appendChild(icone);

        const span = document.createElement("span");
        span.className = "valor-texto";
        span.textContent = valor;
        elemento.appendChild(span);
      } else {
        // Estratégia 3: Atualizar textContent diretamente
        console.log("✅ Estratégia 3: Atualizando textContent");
        elemento.textContent = valor;
      }
    }

    // ✅ APLICAR CLASSES CSS baseadas no status
    elemento.className = "valor-meta " + statusClass;

    console.log("✅ Elemento atualizado:", {
      conteudo: elemento.innerHTML || elemento.textContent,
      classes: elemento.className,
    });
  },

  // ✅ APLICAR ANIMAÇÃO
  aplicarAnimacao(elemento) {
    elemento.classList.add("atualizado");
    setTimeout(() => {
      elemento.classList.remove("atualizado");
    }, 1500);
  },

  // Mostra valor extra quando meta é batida
  mostrarValorExtra(valorExtra) {
    const valorUltrapassouElement =
      document.getElementById("valor-ultrapassou");
    const valorExtraElement = document.getElementById("valor-extra");

    if (valorUltrapassouElement && valorExtraElement) {
      if (valorExtra > 0) {
        const valorExtraFormatado = valorExtra.toLocaleString("pt-BR", {
          style: "currency",
          currency: "BRL",
        });

        valorExtraElement.textContent = valorExtraFormatado;
        valorUltrapassouElement.style.display = "flex";
        valorUltrapassouElement.classList.add("mostrar");
        console.log("✅ Valor extra mostrado:", valorExtraFormatado);
      } else {
        valorExtraElement.textContent = "R$ 0,00";
        valorUltrapassouElement.style.display = "none";
        valorUltrapassouElement.classList.remove("mostrar");
      }
    }
  },

  // Extrai valor numérico de string BRL
  extrairValorNumerico(valorBRL) {
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

  // Mostra erro
  mostrarErroMeta() {
    const possiveisElementos = [
      document.getElementById("meta-diaria-ajax"),
      document.getElementById("meta-valor"),
      document.querySelector(".valor-meta"),
    ];

    const metaElement = possiveisElementos.find((el) => el !== null);

    if (metaElement) {
      metaElement.innerHTML = '<span style="color: #e74c3c;">R$ 0,00</span>';
      console.log("❌ Erro mostrado na meta");
    }
  },

  // ✅ INICIALIZAÇÃO MELHORADA
  async inicializar() {
    console.log("🚀 Inicializando MetaDiariaManager...");

    // Mostrar loading em todos os elementos possíveis
    const possiveisElementos = [
      document.getElementById("meta-diaria-ajax"),
      document.getElementById("meta-valor"),
      document.querySelector(".meta-valor"),
      document.querySelector(".valor-meta"),
    ];

    possiveisElementos.forEach((el) => {
      if (el) {
        el.innerHTML = '<span class="loading-text">Calculando...</span>';
      }
    });

    setTimeout(() => {
      this.atualizarMetaDiaria();
    }, 500);
  },

  // Observer para mudanças no saldo
  atualizarQuandoSaldoMudar() {
    const saldoDiaElement = document.querySelector(".valor-saldo");

    if (saldoDiaElement) {
      const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
          if (
            mutation.type === "childList" ||
            mutation.type === "characterData"
          ) {
            console.log("🔄 Saldo alterado, recalculando meta...");
            setTimeout(() => {
              this.atualizarMetaDiaria();
            }, 300);
          }
        });
      });

      observer.observe(saldoDiaElement, {
        childList: true,
        subtree: true,
        characterData: true,
      });

      console.log("👀 Observer configurado para saldo");
    }
  },
};

// ========================================
// WIDGET ATUALIZADO PARA SEUS DADOS
// ========================================

const MetaProgressoWidget = {
  metaCalculada: 0,
  saldoDia: 0,
  metaFinal: 0,
  bancaTotal: 0,
  saldoBaseMeta: 0,

  // ✅ INTEGRA COM SEU MetaDiariaManager
  integrarComMetaDiariaManager() {
    if (typeof MetaDiariaManager !== "undefined") {
      const originalFunc = MetaDiariaManager.atualizarElementoMeta;

      MetaDiariaManager.atualizarElementoMeta = (data) => {
        if (originalFunc) {
          originalFunc.call(MetaDiariaManager, data);
        }

        setTimeout(() => {
          this.atualizarWidget(data);
        }, 100);
      };

      console.log("🔗 Widget integrado com seus dados PHP");
    }
  },

  // ✅ ATUALIZA WIDGET COM DADOS DO SEU PHP
  atualizarWidget(data) {
    try {
      console.log("🔄 Atualizando widget com dados:", data);

      // ✅ USAR SEUS DADOS ESPECÍFICOS
      this.metaCalculada = parseFloat(data.meta_diaria) || 0;
      this.saldoDia = parseFloat(data.lucro) || 0; // Lucro do dia
      this.bancaTotal = parseFloat(data.banca) || 0; // Banca total
      this.saldoBaseMeta = parseFloat(data.saldo_base_meta) || 0; // Base para meta

      // Aplicar regras de negócio
      this.aplicarRegrasNegocio();

      // Atualizar interface
      this.atualizarInterface();

      // Atualizar data
      this.atualizarData();

      console.log("✅ Widget atualizado com sucesso");

      // ✅ LOG DOS SEUS DADOS
      console.log("📊 Dados recebidos do PHP no Widget:", {
        metaDiaria: this.metaCalculada,
        lucro: this.saldoDia,
        bancaTotal: this.bancaTotal,
        saldoBaseMeta: this.saldoBaseMeta,
        calculoDetalhado: data.calculo_detalhado,
      });
    } catch (error) {
      console.error("❌ Erro no widget:", error);
    }
  },

  // Aplica regras usando seus dados
  aplicarRegrasNegocio() {
    // REGRA 1: Banca total <= 0
    if (this.bancaTotal <= 0) {
      this.metaFinal = this.bancaTotal;
      this.statusMeta = "sem-banca";
      this.rotulo = "DEPOSITE P/ COMEÇAR";
      this.textoSaldo = "Saldo";
      this.valorExtra = 0;
    }
    // REGRA 2: Meta batida (lucro >= meta)
    else if (this.saldoDia >= this.metaCalculada) {
      this.metaFinal = 0;
      this.statusMeta = "meta-batida";
      this.rotulo = "META BATIDA! <i class='fa-solid fa-trophy'></i>";
      this.textoSaldo = "Lucro";
      this.valorExtra = this.saldoDia - this.metaCalculada;
    }
    // REGRA 3: Lucro negativo
    else if (this.saldoDia < 0) {
      this.metaFinal = this.metaCalculada - this.saldoDia;
      this.statusMeta = "negativo";
      this.rotulo = "RESTANDO P/ META";
      this.textoSaldo = "Negativo";
      this.valorExtra = 0;
    }
    // REGRA 4: Lucro zero
    else if (this.saldoDia === 0) {
      this.metaFinal = this.metaCalculada;
      this.statusMeta = "neutro";
      this.rotulo = "META DO DIA";
      this.textoSaldo = "Neutro";
      this.valorExtra = 0;
    }
    // REGRA 5: Lucro positivo mas meta não batida
    else {
      this.metaFinal = this.metaCalculada - this.saldoDia;
      this.statusMeta = "lucro";
      this.rotulo = "RESTANDO P/ META";
      this.textoSaldo = "Lucro";
      this.valorExtra = 0;
    }
  },

  // Formata moeda
  formatarMoeda(valor) {
    return valor.toLocaleString("pt-BR", {
      style: "currency",
      currency: "BRL",
    });
  },

  // Calcula progresso
  calcularProgresso() {
    if (this.bancaTotal <= 0) {
      return 0;
    }

    if (this.statusMeta === "meta-batida") {
      return 100;
    }

    if (this.saldoDia < 0) {
      const progressoNegativo =
        Math.abs(this.saldoDia / this.metaCalculada) * 100;
      return -Math.min(progressoNegativo, 100);
    }

    if (this.metaCalculada === 0) return 0;
    return Math.max(
      0,
      Math.min(100, (this.saldoDia / this.metaCalculada) * 100)
    );
  },

  // Atualiza interface completa
  atualizarInterface() {
    const metaValor = document.getElementById("meta-valor");
    const rotuloMeta = document.getElementById("rotulo-meta");
    const saldoInfo = document.getElementById("saldo-info");
    const barraProgresso = document.getElementById("barra-progresso");
    const valorUltrapassou = document.getElementById("valor-ultrapassou");
    const valorExtra = document.getElementById("valor-extra");

    if (!metaValor && !barraProgresso) {
      console.log("⚠️ Elementos do widget não encontrados");
      return;
    }

    // ✅ ATUALIZAR VALOR PRINCIPAL DO WIDGET
    if (metaValor) {
      const valorTextoElement = metaValor.querySelector(".valor-texto");
      const loadingText = metaValor.querySelector(".loading-text");

      if (loadingText) {
        loadingText.remove();
      }

      const valorParaMostrar = this.formatarMoeda(this.metaFinal);

      if (valorTextoElement) {
        valorTextoElement.textContent = valorParaMostrar;
      } else {
        const icone = metaValor.querySelector(".fa-solid.fa-coins");
        if (icone) {
          metaValor.innerHTML = "";
          metaValor.appendChild(icone);
          const novoSpan = document.createElement("span");
          novoSpan.className = "valor-texto";
          novoSpan.textContent = valorParaMostrar;
          metaValor.appendChild(novoSpan);
        } else {
          metaValor.innerHTML = `
            <i class="fa-solid fa-coins"></i>
            <span class="valor-texto">${valorParaMostrar}</span>
          `;
        }
      }
    }

    const progresso = this.calcularProgresso();

    // ✅ ATUALIZAR SALDO COM CORES CONDICIONAIS
    if (saldoInfo) {
      let classCor = "saldo-zero";
      if (this.saldoDia > 0) {
        classCor = "saldo-positivo";
      } else if (this.saldoDia < 0) {
        classCor = "saldo-negativo";
      }

      saldoInfo.className = classCor;
      saldoInfo.innerHTML = `
        <i class="fa-solid fa-wallet"></i>
        ${this.textoSaldo}: ${this.formatarMoeda(this.saldoDia)}
      `;
    }

    // Atualizar rótulo
    if (rotuloMeta) {
      rotuloMeta.innerHTML = this.rotulo;
    }

    // Controlar lucro extra
    if (valorUltrapassou && valorExtra) {
      if (this.valorExtra > 0 && this.statusMeta === "meta-batida") {
        valorExtra.textContent = this.formatarMoeda(this.valorExtra);
        valorUltrapassou.style.display = "flex";
        valorUltrapassou.classList.add("mostrar");
      } else {
        valorExtra.textContent = "R$ 0,00";
        valorUltrapassou.style.display = "none";
        valorUltrapassou.classList.remove("mostrar");
      }
    }

    // ✅ ATUALIZAR BARRA COM PORCENTAGEM NA PONTA
    if (barraProgresso) {
      this.atualizarBarra(barraProgresso, progresso);
      this.aplicarCores(metaValor, rotuloMeta, barraProgresso, progresso);
    }
  },

  // ✅ ATUALIZA BARRA COM PORCENTAGEM NA PONTA
  atualizarBarra(barraProgresso, progresso) {
    const porcentagemTexto = document.getElementById("porcentagem-barra");

    let larguraBarra = Math.abs(progresso);
    if (this.bancaTotal <= 0) larguraBarra = 0;
    if (this.statusMeta === "meta-batida") larguraBarra = 100;

    barraProgresso.style.width = `${larguraBarra}%`;

    if (progresso < 0) {
      barraProgresso.classList.add("barra-negativa");
    } else {
      barraProgresso.classList.remove("barra-negativa");
    }

    // ✅ PORCENTAGEM NA PONTA DA BARRA
    if (porcentagemTexto) {
      porcentagemTexto.textContent = Math.round(progresso) + "%";

      if (larguraBarra <= 0) {
        porcentagemTexto.style.display = "none";
      } else if (larguraBarra < 15) {
        porcentagemTexto.style.display = "block";
        porcentagemTexto.style.left = `${larguraBarra + 3}%`;
        porcentagemTexto.style.color = this.obterCorBarra(progresso);
      } else {
        porcentagemTexto.style.display = "block";
        porcentagemTexto.style.left = `${larguraBarra - 10}%`;
        porcentagemTexto.style.color = "#fff";
      }
    }
  },

  // Obtém cor da barra
  obterCorBarra(progresso) {
    if (progresso < 0) return "#e74c3c";
    if (this.statusMeta === "meta-batida") return "#2196f3";
    return "#4caf50";
  },

  // Aplica cores
  aplicarCores(metaValor, rotuloMeta, barraProgresso, progresso) {
    const larguraBarra =
      this.bancaTotal <= 0
        ? 0
        : this.statusMeta === "meta-batida"
        ? 100
        : Math.abs(progresso);

    let corBarra = "#9E9E9E";
    let corTexto = "#7f8c8d";

    switch (this.statusMeta) {
      case "sem-banca":
        corBarra = "#e67e22";
        corTexto = "#e67e22";
        break;
      case "meta-batida":
        corBarra = "#2196F3";
        corTexto = "#2196F3";
        break;
      case "negativo":
        corBarra = "#f44336";
        corTexto = "#e74c3c";
        break;
      case "neutro":
        corBarra = "#95a5a6";
        corTexto = "#7f8c8d";
        break;
      case "lucro":
        corBarra = "#4CAF50";
        corTexto = "#00a651";
        break;
    }

    if (metaValor) {
      const valorTexto = metaValor.querySelector(".valor-texto");
      if (valorTexto) {
        valorTexto.style.color = corTexto;
      }
    }

    barraProgresso.style.cssText = `
      width: ${larguraBarra}% !important;
      height: 100% !important;
      background-color: ${corBarra} !important;
      background: ${corBarra} !important;
      border-radius: 20px !important;
    `;
  },

  // Atualiza data
  atualizarData() {
    const dataAtualElement = document.getElementById("data-atual");
    if (dataAtualElement) {
      const agora = new Date();
      const opcoes = {
        weekday: "short",
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
      };

      const dataFormatada = agora.toLocaleDateString("pt-BR", opcoes);
      dataAtualElement.textContent = dataFormatada;
    }
  },

  // ✅ INICIALIZAÇÃO
  inicializar() {
    console.log("🚀 Inicializando Widget com dados PHP...");

    this.integrarComMetaDiariaManager();
    this.atualizarData();

    setTimeout(() => {
      if (typeof MetaDiariaManager !== "undefined") {
        MetaDiariaManager.atualizarMetaDiaria();
      }
    }, 1500);

    console.log("✅ Widget integrado com dados_banca.php");
  },
};

// ========================================
// MANAGER ATUALIZADO PARA SEUS DADOS
// ========================================

const DadosManagerAtualizado = {
  // Função para atualizar dados usando seu PHP
  atualizarLucroEBancaViaAjax() {
    return fetch("dados_banca.php", {
      method: "GET",
      headers: {
        "Cache-Control": "no-cache",
        "X-Requested-With": "XMLHttpRequest",
      },
    })
      .then((response) => {
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return response.json();
      })
      .then((data) => {
        if (!data.success) {
          throw new Error(data.message || "Resposta inválida do servidor");
        }

        console.log("📊 Dados recebidos do PHP:", data);

        // Atualizar elementos se existirem
        if (typeof this.atualizarElementosLucro === "function") {
          this.atualizarElementosLucro(data);
        }
        if (typeof this.atualizarElementosBanca === "function") {
          this.atualizarElementosBanca(data);
        }

        // Atualizar meta diária
        MetaDiariaManager.atualizarElementoMeta(data);

        return data;
      })
      .catch((error) => {
        console.error("Erro ao atualizar dados da banca:", error);
        if (typeof ToastManager !== "undefined") {
          ToastManager.mostrar(
            "❌ Erro ao atualizar dados financeiros",
            "erro"
          );
        }
        throw error;
      });
  },
};

// ========================================
// FUNÇÕES GLOBAIS
// ========================================

window.atualizarMetaDiaria = () => {
  console.log("🔄 Função global: atualizarMetaDiaria chamada");
  return MetaDiariaManager.atualizarMetaDiaria();
};

window.atualizarLucroEBancaViaAjax = async () => {
  try {
    await DadosManagerAtualizado.atualizarLucroEBancaViaAjax();
    setTimeout(() => {
      MetaDiariaManager.atualizarMetaDiaria();
    }, 300);
    console.log("✅ Dados atualizados com dados_banca.php");
  } catch (error) {
    console.error("❌ Erro ao atualizar dados:", error);
  }
};

// ✅ FUNÇÃO PARA FORÇAR ATUALIZAÇÃO COMPLETA
window.forcarAtualizacaoMeta = async () => {
  console.log("🔄 Forçando atualização completa da meta...");
  try {
    const data = await MetaDiariaManager.atualizarMetaDiaria();
    if (data && typeof MetaProgressoWidget !== "undefined") {
      MetaProgressoWidget.atualizarWidget(data);
    }
    console.log("✅ Atualização forçada concluída");
    return data;
  } catch (error) {
    console.error("❌ Erro na atualização forçada:", error);
    return null;
  }
};

// ========================================
// FUNÇÕES DE TESTE E DEBUG
// ========================================

// ✅ FUNÇÃO DE DEBUG COMPLETA
window.debugMeta = () => {
  console.log("🔍 DEBUG META - Verificando elementos...");

  const elementos = [
    "meta-diaria-ajax",
    "meta-valor",
    "barra-progresso",
    "rotulo-meta",
    "saldo-info",
    "valor-ultrapassou",
    "valor-extra",
    "data-atual",
  ];

  elementos.forEach((id) => {
    const el = document.getElementById(id);
    console.log(`${id}:`, el ? "✅ ENCONTRADO" : "❌ NÃO ENCONTRADO", el);
  });

  // Buscar por classes
  const classes = [
    ".valor-meta",
    ".meta-valor",
    ".rotulo-meta",
    ".valor-texto",
    ".loading-text",
  ];

  classes.forEach((cls) => {
    const el = document.querySelector(cls);
    console.log(`${cls}:`, el ? "✅ ENCONTRADO" : "❌ NÃO ENCONTRADO", el);
  });

  return {
    MetaDiariaManager,
    MetaProgressoWidget,
  };
};

// ✅ FUNÇÃO PARA TESTAR SISTEMA COMPLETO
window.testarMeta = async () => {
  console.log("🧪 Testando sistema de meta completo...");

  try {
    // 1. Buscar dados do servidor
    console.log("1️⃣ Buscando dados do servidor...");
    const response = await fetch("dados_banca.php");
    const data = await response.json();

    console.log("📊 Dados do servidor:", data);

    // 2. Verificar se dados são válidos
    console.log("2️⃣ Verificando dados...");
    if (!data.success) {
      throw new Error("Dados inválidos: " + data.message);
    }

    // 3. Testar atualização do elemento principal
    console.log("3️⃣ Testando atualização do elemento principal...");
    MetaDiariaManager.atualizarElementoMeta(data);

    // 4. Testar widget se existir
    console.log("4️⃣ Testando widget...");
    if (typeof MetaProgressoWidget !== "undefined") {
      MetaProgressoWidget.atualizarWidget(data);
    }

    // 5. Verificar se elementos foram atualizados
    console.log("5️⃣ Verificando se elementos foram atualizados...");
    const elementos = [
      { id: "meta-diaria-ajax", nome: "Meta Ajax" },
      { id: "meta-valor", nome: "Meta Valor" },
      { classe: ".valor-meta", nome: "Valor Meta (classe)" },
    ];

    elementos.forEach((item) => {
      const el = item.id
        ? document.getElementById(item.id)
        : document.querySelector(item.classe);
      if (el) {
        console.log(`✅ ${item.nome}:`, el.textContent || el.innerHTML);
      } else {
        console.log(`❌ ${item.nome}: não encontrado`);
      }
    });

    console.log("✅ Teste concluído com sucesso");
    return data;
  } catch (error) {
    console.error("❌ Erro no teste:", error);
    return null;
  }
};

// ✅ FUNÇÃO PARA SIMULAR DADOS DE TESTE
window.simularDados = (banca = 1000, meta = 20, lucro = 0) => {
  console.log("🧪 Simulando dados para teste...");

  const dadosSimulados = {
    success: true,
    banca: banca,
    meta_diaria: meta,
    lucro: lucro,
    meta_diaria_formatada: `R$ ${meta.toFixed(2).replace(".", ",")}`,
    meta_diaria_brl: `R$ ${meta.toFixed(2).replace(".", ",")}`,
    banca_formatada: `R$ ${banca.toFixed(2).replace(".", ",")}`,
    lucro_formatado: `R$ ${lucro.toFixed(2).replace(".", ",")}`,
    calculo_detalhado: {
      saldo_banca_total: banca,
      depositos: banca,
      saques: 0,
      lucro: lucro,
    },
  };

  console.log("📊 Dados simulados:", dadosSimulados);

  // Aplicar dados simulados
  MetaDiariaManager.atualizarElementoMeta(dadosSimulados);

  if (typeof MetaProgressoWidget !== "undefined") {
    MetaProgressoWidget.atualizarWidget(dadosSimulados);
  }

  return dadosSimulados;
};

// ========================================
// INICIALIZAÇÃO AUTOMÁTICA
// ========================================

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", () => {
    console.log("📄 DOM carregado, inicializando sistemas...");
    MetaDiariaManager.inicializar();
    MetaProgressoWidget.inicializar();

    // Configurar observer para mudanças no saldo
    MetaDiariaManager.atualizarQuandoSaldoMudar();
  });
} else {
  console.log("📄 DOM já carregado, inicializando sistemas...");
  MetaDiariaManager.inicializar();
  MetaProgressoWidget.inicializar();

  // Configurar observer para mudanças no saldo
  MetaDiariaManager.atualizarQuandoSaldoMudar();
}

// ========================================
// EXEMPLO DE COMO ENVIAR DADOS PARA SEU PHP
// ========================================

// Função para enviar depósito
window.enviarDeposito = async (valor, diaria = 2, unidade = 2, odds = 1.5) => {
  try {
    console.log("💰 Enviando depósito:", { valor, diaria, unidade, odds });

    const response = await fetch("dados_banca.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        acao: "deposito",
        valor: valor,
        diaria: diaria,
        unidade: unidade,
        odds: odds,
      }),
    });

    const data = await response.json();

    if (data.success) {
      console.log("✅ Depósito realizado:", data);
      setTimeout(() => {
        MetaDiariaManager.atualizarMetaDiaria();
      }, 300);
    } else {
      console.error("❌ Erro no depósito:", data.message);
    }

    return data;
  } catch (error) {
    console.error("❌ Erro ao enviar depósito:", error);
    throw error;
  }
};

// Função para enviar saque
window.enviarSaque = async (valor, diaria = 2, unidade = 2, odds = 1.5) => {
  try {
    console.log("💸 Enviando saque:", { valor, diaria, unidade, odds });

    const response = await fetch("dados_banca.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        acao: "saque",
        valor: valor,
        diaria: diaria,
        unidade: unidade,
        odds: odds,
      }),
    });

    const data = await response.json();

    if (data.success) {
      console.log("✅ Saque realizado:", data);
      setTimeout(() => {
        MetaDiariaManager.atualizarMetaDiaria();
      }, 300);
    } else {
      console.error("❌ Erro no saque:", data.message);
    }

    return data;
  } catch (error) {
    console.error("❌ Erro ao enviar saque:", error);
    throw error;
  }
};

console.log("✅ Sistema integrado com seu dados_banca.php!");

// ========================================
// SUBSTITUIR MANAGERS EXISTENTES (se necessário)
// ========================================

// Se você já tem DadosManager no seu sistema, substitui pelas funções atualizadas
if (typeof DadosManager !== "undefined") {
  Object.assign(DadosManager, DadosManagerAtualizado);
  console.log("🔄 DadosManager existente atualizado");
}

// ========================================
// FUNÇÕES DE DEBUG E TESTE ORIGINAIS
// ========================================

window.debugWidgetCompleto = () => {
  console.log("Debug Widget Completo:", MetaProgressoWidget);
  return MetaProgressoWidget;
};

window.debugBarra = () => {
  const barra = document.getElementById("barra-progresso");
  if (barra) {
    const computedStyle = window.getComputedStyle(barra);
    console.log("🔍 Debug Barra Completo:", {
      elemento: barra,
      styleInline: barra.style.cssText,
      classes: barra.className,
      computedBackgroundColor: computedStyle.backgroundColor,
      computedBackground: computedStyle.background,
      computedWidth: computedStyle.width,
    });
  }
  return barra;
};

window.testarRegrasNegocio = (banca, meta, saldo) => {
  console.log("🧪 Testando regras de negócio:");
  console.log(`Banca: R$ ${banca}, Meta: R$ ${meta}, Saldo: R$ ${saldo}`);

  let valorPrincipal, rotuloInferior, textoSaldo;

  if (banca <= 0) {
    valorPrincipal = `R$ ${banca.toFixed(2)}`;
    rotuloInferior = "DEPOSITE P/ COMEÇAR";
    textoSaldo = `Saldo: R$ ${saldo.toFixed(2)}`;
  } else if (saldo >= meta) {
    valorPrincipal = "R$ 0,00";
    rotuloInferior = "META BATIDA! 🏆";
    textoSaldo = `Lucro: R$ ${saldo.toFixed(2)}`;
  } else if (saldo < 0) {
    valorPrincipal = `R$ ${(meta - saldo).toFixed(2)}`;
    rotuloInferior = "RESTANDO P/ META";
    textoSaldo = `Negativo: R$ ${saldo.toFixed(2)}`;
  } else if (saldo === 0) {
    valorPrincipal = `R$ ${meta.toFixed(2)}`;
    rotuloInferior = "META DO DIA";
    textoSaldo = `Neutro: R$ ${saldo.toFixed(2)}`;
  } else {
    valorPrincipal = `R$ ${(meta - saldo).toFixed(2)}`;
    rotuloInferior = "RESTANDO P/ META";
    textoSaldo = `Lucro: R$ ${saldo.toFixed(2)}`;
  }

  console.log("Valor Principal:", valorPrincipal);
  console.log("Rótulo Inferior:", rotuloInferior);
  console.log("Texto do Saldo:", textoSaldo);
  return { valorPrincipal, rotuloInferior, textoSaldo };
};

// ========================================
// ATALHOS PARA DESENVOLVIMENTO
// ========================================

// Atalhos globais para facilitar desenvolvimento
window.$ = {
  debug: () => debugMeta(),
  test: () => testarMeta(),
  force: () => forcarAtualizacaoMeta(),
  simulate: (banca, meta, lucro) => simularDados(banca, meta, lucro),
};

// ========================================
// LOGS FINAIS
// ========================================

console.log("✅ Sistema de Meta Diária - TOTALMENTE CARREGADO!");
console.log("🔧 Funções disponíveis:");
console.log("  - atualizarMetaDiaria()");
console.log("  - forcarAtualizacaoMeta()");
console.log("  - debugMeta()");
console.log("  - testarMeta()");
console.log("  - simularDados(banca, meta, lucro)");
console.log("  - enviarDeposito(valor)");
console.log("  - enviarSaque(valor)");
console.log("  - testarRegrasNegocio(banca, meta, saldo)");
console.log("🎯 Atalhos rápidos:");
console.log("  - $.debug() - Debug completo");
console.log("  - $.test() - Testar sistema");
console.log("  - $.force() - Forçar atualização");
console.log("  - $.simulate(1000, 20, 5) - Simular dados");
console.log("📱 Execute $.debug() para começar!");

//
//
//
//
//
// ========================================
// DATA DO DIA  ELEGANTE - INTEGRAÇÃO
// ========================================

// ========================================
// SISTEMA DE DATA INTEGRADA NO WIDGET META
// Adicione este código ao seu JavaScript existente
// ========================================

const SistemaDataIntegrada = {
  // Configurações
  config: {
    atualizarACada: 60000, // 1 minuto
    verificarMudancaDiaACada: 10000, // 10 segundos
    animacaoMudancaDia: true,
  },

  // Dados
  diasSemana: [
    "Domingo",
    "Segunda-feira",
    "Terça-feira",
    "Quarta-feira",
    "Quinta-feira",
    "Sexta-feira",
    "Sábado",
  ],

  feriadosBrasil: [
    "01-01", // Ano Novo
    "04-21", // Tiradentes
    "09-07", // Independência
    "10-12", // Nossa Senhora Aparecida
    "11-02", // Finados
    "11-15", // Proclamação da República
    "12-25", // Natal
  ],

  // Estado interno
  ultimaData: null,
  intervalos: [],

  // ========================================
  // FUNÇÕES PRINCIPAIS
  // ========================================

  // Formatar data compacta para o widget
  formatarDataCompacta() {
    const agora = new Date();
    const diaSemana = this.diasSemana[agora.getDay()];
    const dia = agora.getDate().toString().padStart(2, "0");
    const mes = (agora.getMonth() + 1).toString().padStart(2, "0");

    return `${diaSemana}, ${dia}/${mes}`;
  },

  // Verificar se é fim de semana
  ehFimDeSemana() {
    const agora = new Date();
    const diaSemana = agora.getDay();
    return diaSemana === 0 || diaSemana === 6; // Domingo ou Sábado
  },

  // Verificar se é feriado brasileiro
  ehFeriado() {
    const agora = new Date();
    const mes = (agora.getMonth() + 1).toString().padStart(2, "0");
    const dia = agora.getDate().toString().padStart(2, "0");
    const dataAtual = `${mes}-${dia}`;

    return this.feriadosBrasil.includes(dataAtual);
  },

  // Obter período do dia com configurações específicas
  obterPeriodoDia() {
    const agora = new Date();
    const hora = agora.getHours();

    if (hora >= 0 && hora < 6) {
      return {
        periodo: "madrugada",
        texto: "MADRUGADA",
        classe: "periodo-madrugada",
      };
    } else if (hora >= 6 && hora < 12) {
      return {
        periodo: "manha",
        texto: "MANHÃ",
        classe: "periodo-manha",
      };
    } else if (hora >= 12 && hora < 18) {
      return {
        periodo: "tarde",
        texto: "TARDE",
        classe: "periodo-tarde",
      };
    } else {
      return {
        periodo: "noite",
        texto: "NOITE",
        classe: "periodo-noite",
      };
    }
  },

  // ========================================
  // FUNÇÕES DE ATUALIZAÇÃO
  // ========================================

  // Atualizar data no header integrado
  atualizarData() {
    const elementoData = document.getElementById("data-atual");
    const headerData = document.getElementById("data-header");

    if (!elementoData) {
      console.warn("⚠️ Elemento data-atual não encontrado no widget integrado");
      return;
    }

    try {
      const dataFormatada = this.formatarDataCompacta();

      // Atualiza texto com efeito suave
      elementoData.classList.add("atualizando");

      setTimeout(() => {
        elementoData.textContent = dataFormatada;

        // Remove classe de animação
        setTimeout(() => {
          elementoData.classList.remove("atualizando");
        }, 600);
      }, 100);

      // Aplicar classes especiais no header
      if (headerData) {
        // Remove classes anteriores
        headerData.classList.remove("weekend", "feriado");

        // Adiciona classes baseadas no tipo de dia
        if (this.ehFeriado()) {
          headerData.classList.add("feriado");
          console.log("🎉 Feriado detectado no widget integrado!");
        } else if (this.ehFimDeSemana()) {
          headerData.classList.add("weekend");
          console.log("🏖️ Fim de semana detectado no widget integrado!");
        }
      }

      console.log("📅 Data integrada atualizada:", dataFormatada);
      return dataFormatada;
    } catch (error) {
      console.error("❌ Erro ao atualizar data integrada:", error);
      elementoData.textContent = "Erro na data";
    }
  },

  // Atualizar status do período
  atualizarStatusPeriodo() {
    const statusContainer = document.getElementById("status-periodo");
    const statusTexto = statusContainer?.querySelector(".status-periodo-texto");

    if (!statusContainer || !statusTexto) {
      console.warn(
        "⚠️ Elementos de status não encontrados no widget integrado"
      );
      return;
    }

    try {
      const { periodo, texto, classe } = this.obterPeriodoDia();

      // Remove classes de período anteriores
      statusContainer.classList.remove(
        "periodo-madrugada",
        "periodo-manha",
        "periodo-tarde",
        "periodo-noite"
      );

      // Adiciona nova classe do período
      statusContainer.classList.add(classe);

      // Atualiza texto do período
      statusTexto.textContent = texto;

      console.log("🕐 Status integrado atualizado:", texto);
      return { periodo, texto, classe };
    } catch (error) {
      console.error("❌ Erro ao atualizar status integrado:", error);
    }
  },

  // ========================================
  // DETECÇÃO DE MUDANÇA DE DIA
  // ========================================

  // Verificar mudança de dia
  verificarMudancaDia() {
    const agora = new Date();
    const dataAtual = agora.toDateString();

    if (this.ultimaData && this.ultimaData !== dataAtual) {
      console.log("🌅 NOVO DIA DETECTADO no widget integrado!", {
        anterior: this.ultimaData,
        atual: dataAtual,
      });

      // Executa efeito visual de mudança de dia
      if (this.config.animacaoMudancaDia) {
        this.efeitoMudancaDiaIntegrada();
      }

      // Atualiza dados após efeito
      setTimeout(
        () => {
          this.atualizarData();
          this.atualizarStatusPeriodo();
        },
        this.config.animacaoMudancaDia ? 500 : 0
      );

      // Dispara evento customizado para integração com seu sistema
      this.dispararEventoMudancaDiaIntegrada(this.ultimaData, dataAtual);
    }

    this.ultimaData = dataAtual;
  },

  // Efeito visual específico para mudança de dia no widget integrado
  efeitoMudancaDiaIntegrada() {
    const headerData = document.getElementById("data-header");
    const elementoData = document.getElementById("data-atual");

    if (!headerData || !elementoData) return;

    try {
      // Efeito no header
      headerData.style.background = "rgba(76, 175, 80, 0.2)";
      headerData.style.borderBottom = "1px solid rgba(76, 175, 80, 0.3)";

      // Efeito no texto da data
      elementoData.style.transform = "scale(1.1)";
      elementoData.style.color = "#4CAF50";
      elementoData.style.textShadow = "0 0 10px rgba(76, 175, 80, 0.6)";

      // Remove efeitos após animação
      setTimeout(() => {
        headerData.style.background = "";
        headerData.style.borderBottom = "";
        elementoData.style.transform = "";
        elementoData.style.color = "";
        elementoData.style.textShadow = "";
      }, 1000);

      console.log("🎬 Efeito de mudança de dia integrada executado");
    } catch (error) {
      console.error("❌ Erro no efeito de mudança de dia integrada:", error);
    }
  },

  // Disparar evento customizado para integração
  dispararEventoMudancaDiaIntegrada(dataAnterior, dataAtual) {
    const evento = new CustomEvent("mudancaDiaIntegrada", {
      detail: {
        dataAnterior,
        dataAtual,
        timestamp: new Date(),
        fonte: "SistemaDataIntegrada",
      },
    });

    document.dispatchEvent(evento);
    console.log("📡 Evento mudancaDiaIntegrada disparado");
  },

  // ========================================
  // INTEGRAÇÃO COM SEU SISTEMA EXISTENTE
  // ========================================

  // Integrar com MetaDiariaManager e outros sistemas
  integrarComSistemaExistente() {
    // Integração com MetaDiariaManager
    if (typeof MetaDiariaManager !== "undefined") {
      console.log("🔗 Integrando data integrada com MetaDiariaManager...");

      // Listener para mudança de dia
      document.addEventListener("mudancaDiaIntegrada", (evento) => {
        console.log(
          "📊 Atualizando meta diária devido à mudança de dia integrada...",
          evento.detail
        );

        // Força atualização da meta diária após mudança de dia
        setTimeout(() => {
          if (MetaDiariaManager.atualizarMetaDiaria) {
            MetaDiariaManager.atualizarMetaDiaria();
          }
        }, 1000);
      });
    }

    // Integração com DadosManager
    if (typeof DadosManager !== "undefined") {
      console.log("🔗 Integrando data integrada com DadosManager...");

      document.addEventListener("mudancaDiaIntegrada", (evento) => {
        console.log(
          "💰 Atualizando dados da banca devido à mudança de dia integrada...",
          evento.detail
        );

        setTimeout(() => {
          if (DadosManager.atualizarLucroEBancaViaAjax) {
            DadosManager.atualizarLucroEBancaViaAjax();
          }
        }, 1500);
      });
    }

    // Integração com qualquer outro sistema que use o evento mudancaDia
    document.addEventListener("mudancaDiaIntegrada", (evento) => {
      // Dispara também o evento original para compatibilidade
      const eventoOriginal = new CustomEvent("mudancaDia", {
        detail: evento.detail,
      });
      document.dispatchEvent(eventoOriginal);
    });
  },

  // ========================================
  // INICIALIZAÇÃO E CONTROLE
  // ========================================

  // Inicializar sistema integrado
  inicializar() {
    console.log("🚀 Inicializando Sistema de Data Integrada no Widget Meta...");

    try {
      // Primeira atualização imediata
      this.atualizarData();
      this.atualizarStatusPeriodo();

      // Integração com sistemas existentes
      this.integrarComSistemaExistente();

      // Configura intervalos
      this.configurarIntervalos();

      // Adiciona event listeners
      this.adicionarEventListeners();

      console.log("✅ Sistema de Data Integrada inicializado com sucesso!");
      return true;
    } catch (error) {
      console.error("❌ Erro na inicialização da data integrada:", error);
      return false;
    }
  },

  // Configurar intervalos de atualização
  configurarIntervalos() {
    // Limpa intervalos anteriores se existirem
    this.pararIntervalos();

    // Atualização de status a cada minuto
    const intervaloStatus = setInterval(() => {
      this.atualizarStatusPeriodo();
    }, this.config.atualizarACada);

    // Verificação de mudança de dia a cada 10 segundos
    const intervaloMudancaDia = setInterval(() => {
      this.verificarMudancaDia();
    }, this.config.verificarMudancaDiaACada);

    // Armazena intervalos para limpeza posterior
    this.intervalos = [intervaloStatus, intervaloMudancaDia];

    console.log("⏰ Intervalos da data integrada configurados");
  },

  // Parar todos os intervalos
  pararIntervalos() {
    this.intervalos.forEach((intervalo) => {
      if (intervalo) {
        clearInterval(intervalo);
      }
    });
    this.intervalos = [];
    console.log("⏸️ Intervalos da data integrada parados");
  },

  // Adicionar event listeners específicos
  adicionarEventListeners() {
    // Listener para visibilidade da página
    document.addEventListener("visibilitychange", () => {
      if (!document.hidden) {
        console.log("👁️ Página ficou visível, atualizando data integrada...");
        this.atualizarData();
        this.atualizarStatusPeriodo();
        this.verificarMudancaDia();
      }
    });

    // Listener para foco na janela
    window.addEventListener("focus", () => {
      console.log(
        "🎯 Janela focada, verificando atualizações da data integrada..."
      );
      this.verificarMudancaDia();
    });
  },

  // ========================================
  // FUNÇÕES UTILITÁRIAS E DEBUG
  // ========================================

  // Forçar atualização manual
  forcarAtualizacao() {
    console.log("🔄 Forçando atualização manual da data integrada...");
    this.atualizarData();
    this.atualizarStatusPeriodo();
    this.verificarMudancaDia();
  },

  // Simular mudança de dia (para testes)
  simularMudancaDia() {
    console.log("🧪 Simulando mudança de dia na data integrada...");
    const dataFake = new Date();
    dataFake.setDate(dataFake.getDate() + 1);
    this.ultimaData = dataFake.toDateString();
    this.verificarMudancaDia();
  },

  // Obter informações de debug
  obterInfoDebug() {
    const agora = new Date();
    return {
      dataAtual: this.formatarDataCompacta(),
      periodoAtual: this.obterPeriodoDia(),
      ehFimDeSemana: this.ehFimDeSemana(),
      ehFeriado: this.ehFeriado(),
      ultimaData: this.ultimaData,
      dataAtualCompleta: agora.toLocaleString("pt-BR"),
      intervalosAtivos: this.intervalos.length,
      config: this.config,
      elementosEncontrados: {
        dataAtual: !!document.getElementById("data-atual"),
        dataHeader: !!document.getElementById("data-header"),
        statusPeriodo: !!document.getElementById("status-periodo"),
      },
    };
  },

  // Destruir sistema (limpeza)
  destruir() {
    console.log("🗑️ Destruindo Sistema de Data Integrada...");
    this.pararIntervalos();
    this.ultimaData = null;
    console.log("✅ Sistema de Data Integrada destruído");
  },
};

// ========================================
// INICIALIZAÇÃO AUTOMÁTICA
// ========================================

// Função de inicialização que aguarda o DOM
function inicializarSistemaDataIntegrada() {
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => {
      // Aguarda um pouco para garantir que todos os elementos estejam prontos
      setTimeout(() => {
        SistemaDataIntegrada.inicializar();
      }, 500);
    });
  } else {
    // DOM já carregado, aguarda um pouco e inicializa
    setTimeout(() => {
      SistemaDataIntegrada.inicializar();
    }, 500);
  }
}

// Inicializar automaticamente
inicializarSistemaDataIntegrada();

// ========================================
// FUNÇÕES GLOBAIS PARA INTEGRAÇÃO
// ========================================

// Exposição global para integração com seu sistema
window.SistemaDataIntegrada = SistemaDataIntegrada;

// Função global de debug
window.debugDataIntegrada = () => {
  console.log(
    "🔍 Debug Sistema de Data Integrada:",
    SistemaDataIntegrada.obterInfoDebug()
  );
  return SistemaDataIntegrada;
};

// Função global para forçar atualização
window.atualizarDataIntegrada = () => {
  SistemaDataIntegrada.forcarAtualizacao();
};

// Função global para simular mudança de dia (desenvolvimento)
window.simularMudancaDiaIntegrada = () => {
  SistemaDataIntegrada.simularMudancaDia();
};

console.log("✅ Sistema de Data Integrada carregado e pronto para uso!");

// ========================================
// INTEGRAÇÃO AVANÇADA COM SEU CÓDIGO EXISTENTE
// ========================================

// Aguarda MetaDiariaManager estar disponível e integra
const aguardarMetaDiariaManager = setInterval(() => {
  if (typeof MetaDiariaManager !== "undefined") {
    clearInterval(aguardarMetaDiariaManager);

    // Adiciona listener específico para mudança de dia integrada
    document.addEventListener("mudancaDiaIntegrada", (evento) => {
      console.log(
        "📊 Mudança de dia integrada detectada, atualizando meta...",
        evento.detail
      );

      // Força atualização da meta após mudança de dia
      setTimeout(() => {
        if (MetaDiariaManager.atualizarMetaDiaria) {
          MetaDiariaManager.atualizarMetaDiaria();
        }
      }, 1000);
    });

    console.log(
      "🔗 Integração com MetaDiariaManager configurada para data integrada!"
    );
  }
}, 100);

// Aguarda outros managers estarem disponíveis
const aguardarOutrosManagers = setInterval(() => {
  if (
    typeof DadosManager !== "undefined" ||
    typeof FormularioValorManager !== "undefined"
  ) {
    clearInterval(aguardarOutrosManagers);

    // Listener para atualizar todos os dados quando muda o dia
    document.addEventListener("mudancaDiaIntegrada", (evento) => {
      console.log(
        "🔄 Atualizando todos os dados devido à mudança de dia integrada...",
        evento.detail
      );

      // Atualiza dados da banca se disponível
      if (
        typeof DadosManager !== "undefined" &&
        DadosManager.atualizarLucroEBancaViaAjax
      ) {
        setTimeout(() => {
          DadosManager.atualizarLucroEBancaViaAjax();
        }, 1500);
      }

      // Recarrega mentores se disponível
      if (
        typeof MentorManager !== "undefined" &&
        MentorManager.recarregarMentores
      ) {
        setTimeout(() => {
          MentorManager.recarregarMentores();
        }, 2000);
      }
    });

    console.log(
      "🔗 Integração com outros managers configurada para data integrada!"
    );
  }
}, 100);

// ========================================
// MELHORIAS PARA INTEGRAÇÃO COM SEU WIDGET META
// ========================================

// Função para integrar com o MetaProgressoWidget se existir
const integrarComMetaProgressoWidget = () => {
  if (typeof MetaProgressoWidget !== "undefined") {
    console.log("🔗 Integrando data integrada com MetaProgressoWidget...");

    // Adiciona listener para mudança de dia
    document.addEventListener("mudancaDiaIntegrada", () => {
      console.log(
        "📈 Atualizando MetaProgressoWidget devido à mudança de dia integrada..."
      );

      setTimeout(() => {
        if (
          MetaProgressoWidget.atualizarWidget &&
          typeof MetaDiariaManager !== "undefined"
        ) {
          // Força uma nova busca de dados
          MetaDiariaManager.atualizarMetaDiaria();
        }
      }, 1000);
    });
  }
};

// Verifica periodicamente se MetaProgressoWidget está disponível
const verificarMetaProgressoWidget = setInterval(() => {
  if (typeof MetaProgressoWidget !== "undefined") {
    clearInterval(verificarMetaProgressoWidget);
    integrarComMetaProgressoWidget();
  }
}, 100);

// Para a verificação após 10 segundos para evitar loop infinito
setTimeout(() => {
  clearInterval(verificarMetaProgressoWidget);
}, 10000);

// ========================================
// FUNÇÃO DE COMPATIBILIDADE
// ========================================

// Função para garantir compatibilidade com código existente
window.compatibilidadeDataIntegrada = () => {
  // Verifica se todos os elementos necessários existem
  const elementos = {
    dataAtual: document.getElementById("data-atual"),
    dataHeader: document.getElementById("data-header"),
    statusPeriodo: document.getElementById("status-periodo"),
    widgetMeta: document.getElementById("widget-meta"),
  };

  const problemasEncontrados = [];

  Object.keys(elementos).forEach((chave) => {
    if (!elementos[chave]) {
      problemasEncontrados.push(`Elemento ${chave} não encontrado`);
    }
  });

  if (problemasEncontrados.length > 0) {
    console.warn(
      "⚠️ Problemas de compatibilidade encontrados:",
      problemasEncontrados
    );
    return {
      compativel: false,
      problemas: problemasEncontrados,
      solucao: "Verifique se o HTML foi adicionado corretamente",
    };
  } else {
    console.log("✅ Todos os elementos necessários foram encontrados");
    return {
      compativel: true,
      problemas: [],
      status: "Sistema totalmente compatível e funcional",
    };
  }
};

// Executa verificação de compatibilidade após inicialização
setTimeout(() => {
  window.compatibilidadeDataIntegrada();
}, 2000);
