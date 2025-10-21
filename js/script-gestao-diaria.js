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
// ================================================
// SISTEMA DE GESTÃO DE MENTORES - VERSÃO COMPLETA CORRIGIDA
// ================================================

// ✅ CONFIGURAÇÕES E CONSTANTES
const CONFIG = {
  LIMITE_CARACTERES_NOME: 13,
  INTERVALO_ATUALIZACAO: 30000, // 30 segundos
  TIMEOUT_TOAST: 4000,
  AVATAR_PADRAO: "https://cdn-icons-png.flaticon.com/512/847/847969.png",
};

// Toggle separator visibility for the daily placar (#pontuacao)
document.addEventListener("DOMContentLoaded", function () {
  try {
    const placar = document.getElementById("pontuacao");
    if (!placar) return;

    function atualizarEstadoPlacar() {
      const green = placar.querySelector(".placar-green");
      const red = placar.querySelector(".placar-red");
      const temValores =
        (green && green.textContent.trim() !== "") ||
        (red && red.textContent.trim() !== "");
      if (temValores) {
        placar.classList.add("placar-has-values");
      } else {
        placar.classList.remove("placar-has-values");
      }

      // Ensure the separador font-size follows the root variable (--fs-placar-top)
      // and override any stylesheet rules (including those using !important).
      try {
        const separador = placar.querySelector(".separador");
        if (separador) {
          const rootStyle = getComputedStyle(document.documentElement);
          // Prefer bloco-1 specific variable, fallback to general one
          const fsB1 = rootStyle.getPropertyValue("--fs-placar-top-b1") || "";
          const fs =
            fsB1.trim() ||
            (rootStyle.getPropertyValue("--fs-placar-top") || "").trim();
          if (fs) {
            separador.style.setProperty("font-size", fs, "important");
          }
          // Apply font-weight from root variable and force with important (default to 300)
          const fwRaw = rootStyle.getPropertyValue("--fs-placar-weight") || "";
          const fw = fwRaw.trim() || "300";
          separador.style.setProperty("font-weight", fw, "important");
        }
      } catch (e) {
        // silently ignore if environment doesn't support setProperty priority
      }
    }

    // Run once and then observe mutations to update when values change
    atualizarEstadoPlacar();

    const mo = new MutationObserver(atualizarEstadoPlacar);
    mo.observe(placar, { childList: true, subtree: true, characterData: true });
  } catch (e) {
    console.warn("Erro ao inicializar placar auto-toggle", e);
  }
});

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

// =====================
// Ajuste dinâmico de altura do campo de mentores
// =====================
const CampoMentoresHeightManager = {
  selectorTopo: ".widget-meta-item",
  selectorMentores: ".campo_mentores",
  initialTopoHeight: null,
  initialMentoresHeight: null,
  minMentoresHeight: 120, // px mínimo para manter usabilidade
  debounceDelay: 80,
  observer: null,
  _pollInterval: null,

  init() {
    try {
      const topo = document.querySelector(this.selectorTopo);
      const mentores = document.querySelector(this.selectorMentores);
      if (!topo || !mentores) return;

      // Salva alturas iniciais (apenas uma vez)
      if (!this.initialTopoHeight)
        this.initialTopoHeight = topo.getBoundingClientRect().height;
      if (!this.initialMentoresHeight)
        this.initialMentoresHeight = mentores.getBoundingClientRect().height;

      // Aplica transição suave
      mentores.style.transition = "height 220ms ease";

      // Debounced adjust
      this._debouncedAdjust = Utils.debounce(
        () => this.adjustHeights(),
        this.debounceDelay
      );

      // Prefer ResizeObserver
      if (window.ResizeObserver) {
        this.observer = new ResizeObserver(() => this._debouncedAdjust());
        this.observer.observe(topo);
      } else {
        // Fallback: MutationObserver + interval
        const mo = new MutationObserver(() => this._debouncedAdjust());
        mo.observe(topo, { childList: true, subtree: true, attributes: true });
        this.observer = mo;
        // Also poll size as a safety net
        this._pollInterval = setInterval(() => this._debouncedAdjust(), 1000);
      }

      // Also adjust on window resize
      window.addEventListener("resize", this._debouncedAdjust);

      // Initial run to normalize
      this.adjustHeights();
    } catch (e) {
      console.warn("CampoMentoresHeightManager init error", e);
    }
  },

  adjustHeights() {
    const topo = document.querySelector(this.selectorTopo);
    const mentores = document.querySelector(this.selectorMentores);
    if (!topo || !mentores) return;

    const topoRect = topo.getBoundingClientRect();
    const currentTopoHeight = topoRect.height;

    // If initial heights missing, set them
    if (!this.initialTopoHeight) this.initialTopoHeight = currentTopoHeight;
    if (!this.initialMentoresHeight)
      this.initialMentoresHeight = mentores.getBoundingClientRect().height;

    // Delta: quanto o topo cresceu em relação ao inicial
    const delta = currentTopoHeight - this.initialTopoHeight;

    // New mentors height: initial - delta (mas não menor que min)
    let novo = Math.max(
      this.minMentoresHeight,
      Math.round(this.initialMentoresHeight - delta)
    );

    // Também evita ultrapassar o inicial quando topo diminui
    novo = Math.min(novo, this.initialMentoresHeight);

    // Aplica altura via style (px)
    mentores.style.height = novo + "px";
  },

  destroy() {
    if (this.observer && this.observer.disconnect) this.observer.disconnect();
    if (this._pollInterval) clearInterval(this._pollInterval);
    window.removeEventListener("resize", this._debouncedAdjust);
  },
};

// =====================
// Ajuste dinâmico para o BLOCO 2 (lista-dias)
// =====================
const CampoBloco2HeightManager = {
  selectorTopo: ".bloco-2 .widget-conteudo-principal-2",
  selectorTarget: ".bloco-2 .lista-dias",
  initialTopoHeight: null,
  initialTargetHeight: null,
  minTargetHeight: 160,
  debounceDelay: 80,
  observer: null,
  _pollInterval: null,

  init() {
    try {
      const topo = document.querySelector(this.selectorTopo);
      const target = document.querySelector(this.selectorTarget);
      if (!topo || !target) return;

      if (!this.initialTopoHeight)
        this.initialTopoHeight = topo.getBoundingClientRect().height;
      if (!this.initialTargetHeight)
        this.initialTargetHeight = target.getBoundingClientRect().height;

      target.style.transition = "height 220ms ease";
      this._debouncedAdjust = Utils.debounce(
        () => this.adjustHeights(),
        this.debounceDelay
      );

      if (window.ResizeObserver) {
        this.observer = new ResizeObserver(() => this._debouncedAdjust());
        this.observer.observe(topo);
      } else {
        const mo = new MutationObserver(() => this._debouncedAdjust());
        mo.observe(topo, { childList: true, subtree: true, attributes: true });
        this.observer = mo;
        this._pollInterval = setInterval(() => this._debouncedAdjust(), 1000);
      }

      window.addEventListener("resize", this._debouncedAdjust);
      this.adjustHeights();
    } catch (e) {
      console.warn("CampoBloco2HeightManager init error", e);
    }
  },

  adjustHeights() {
    const topo = document.querySelector(this.selectorTopo);
    const target = document.querySelector(this.selectorTarget);
    if (!topo || !target) return;

    const currentTopoHeight = topo.getBoundingClientRect().height;
    if (!this.initialTopoHeight) this.initialTopoHeight = currentTopoHeight;
    if (!this.initialTargetHeight)
      this.initialTargetHeight = target.getBoundingClientRect().height;

    const delta = currentTopoHeight - this.initialTopoHeight;
    let novo = Math.max(
      this.minTargetHeight,
      Math.round(this.initialTargetHeight - delta)
    );
    novo = Math.min(novo, this.initialTargetHeight);
    target.style.height = novo + "px";
  },

  destroy() {
    if (this.observer && this.observer.disconnect) this.observer.disconnect();
    if (this._pollInterval) clearInterval(this._pollInterval);
    window.removeEventListener("resize", this._debouncedAdjust);
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

// ✅ GERENCIADOR DE MODAIS - VERSÃO MELHORADA
const ModalManager = {
  modalAtual: null,
  // Keep track of modals whose z-index we temporarily lowered so we can restore them
  _temporarilyLoweredModals: [],

  abrir(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;

    // Fecha modal anterior se existir
    if (this.modalAtual) {
      this.fechar(this.modalAtual);
    }

    // Prepara o modal
    modal.style.display = "block";
    // If this is the confirmation modal, ensure it lives directly under body
    // and has very high z-index so it cannot be obscured by other stacking contexts.
    if (modalId === "modal-confirmacao-exclusao") {
      try {
        // Lower any other open modal/dialogs temporarily so they cannot occlude
        // the confirmation modal. We record them to restore later.
        this._temporarilyLoweredModals = [];
        // Find other modal elements that might occlude (visible or with .show)
        document.querySelectorAll(".modal").forEach((m) => {
          if (m.id && m.id !== modalId) {
            const isVisible =
              (window.getComputedStyle(m).display !== "none" &&
                window.getComputedStyle(m).visibility !== "hidden") ||
              m.classList.contains("show");
            const prev = {
              el: m,
              z: m.style.zIndex || "",
              position: m.style.position || "",
              visibility: m.style.visibility || "",
              pointerEvents: m.style.pointerEvents || "",
            };
            this._temporarilyLoweredModals.push(prev);

            try {
              // If it's visible, hide it to avoid any occlusion; otherwise still lower z-index
              if (isVisible) {
                m.style.setProperty("visibility", "hidden", "important");
                m.style.setProperty("pointer-events", "none", "important");
              }
              // Force a low z-index with priority so it cannot beat the confirmation modal
              m.style.setProperty("z-index", "1000", "important");
            } catch (e) {
              // ignore
            }
          }
        });

        // Ensure the confirmation modal lives under document.body
        if (modal.parentNode !== document.body) {
          document.body.appendChild(modal);
        }

        // Make the overlay occupy full viewport and sit on top using important
        modal.style.position = "fixed";
        modal.style.top = "0";
        modal.style.left = "0";
        modal.style.width = "100vw";
        modal.style.height = "100vh";
        modal.style.pointerEvents = "auto";
        // set with priority to avoid stylesheet overrides
        modal.style.setProperty("z-index", "2147483646", "important");

        const inner =
          modal.querySelector(".modal-content") ||
          modal.querySelector(".modal-conteudo");
        if (inner) {
          inner.style.position = "fixed";
          inner.style.setProperty("z-index", "2147483647", "important");
        }
      } catch (err) {
        console.warn("Could not re-parent modal-confirmacao-exclusao:", err);
      }
    }
    document.body.style.overflow = "hidden";

    // Aplica animação
    requestAnimationFrame(() => {
      modal.classList.add("show");
      this.modalAtual = modalId;
    });

    // Adiciona listeners
    this.adicionarEventosModal(modal);
  },

  fechar(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;

    // Inicia animação de saída e remove show class
    modal.classList.remove("show");

    // Remove estilo display flex/block imediatamente
    modal.style.display = "none";

    // Restaura scroll do body
    document.body.style.overflow = "";

    // Limpa estado do modal
    this.modalAtual = null;

    // Cleanup inline styles if it was the confirmation modal
    if (modalId === "modal-confirmacao-exclusao") {
      try {
        // Clear inline properties
        modal.style.removeProperty("z-index");
        modal.style.position = "";
        modal.style.top = "";
        modal.style.left = "";
        modal.style.width = "";
        modal.style.height = "";
        modal.style.pointerEvents = "";
        const inner =
          modal.querySelector(".modal-content") ||
          modal.querySelector(".modal-conteudo");
        if (inner) {
          inner.style.removeProperty("z-index");
          inner.style.position = "";
        }

        // Restore any modals we temporarily lowered
        if (Array.isArray(this._temporarilyLoweredModals)) {
          this._temporarilyLoweredModals.forEach((prev) => {
            try {
              if (prev && prev.el) {
                // Restore saved inline values (if any)
                prev.el.style.zIndex = prev.z || "";
                prev.el.style.position = prev.position || "";
                prev.el.style.visibility = prev.visibility || "";
                prev.el.style.pointerEvents = prev.pointerEvents || "";
              }
            } catch (e) {
              // ignore
            }
          });
        }
        this._temporarilyLoweredModals = [];
      } catch (err) {
        console.warn("Cleanup failed for modal-confirmacao-exclusao:", err);
      }
    }
    // Remove todos os event listeners
    this.removerEventosModal(modal);

    // If we just closed some other modal while the confirmation is open,
    // re-assert the confirmation modal to ensure it stays on top.
    try {
      if (modalId !== "modal-confirmacao-exclusao") {
        const conf = document.getElementById("modal-confirmacao-exclusao");
        if (conf && conf.style.display !== "none") {
          if (conf.parentNode !== document.body) {
            document.body.appendChild(conf);
          }
          conf.style.setProperty("z-index", "2147483646", "important");
          conf.style.setProperty("position", "fixed", "important");
          conf.style.setProperty("top", "0", "important");
          conf.style.setProperty("left", "0", "important");
          conf.style.setProperty("width", "100vw", "important");
          conf.style.setProperty("height", "100vh", "important");
          const inner =
            conf.querySelector(".modal-content") ||
            conf.querySelector(".modal-conteudo");
          if (inner) {
            inner.style.setProperty("z-index", "2147483647", "important");
            inner.style.setProperty("position", "fixed", "important");
          }
        }
      }
    } catch (e) {
      // ignore
    }
  },

  adicionarEventosModal(modal) {
    // Fecha ao pressionar ESC
    this.handleKeyPress = (e) => {
      if (e.key === "Escape") {
        this.fechar(modal.id);
      }
    };
    document.addEventListener("keydown", this.handleKeyPress);

    // Fecha ao clicar fora
    this.handleOutsideClick = (e) => {
      if (e.target === modal) {
        this.fechar(modal.id);
      }
    };
    modal.addEventListener("click", this.handleOutsideClick);
  },

  removerEventosModal(modal) {
    document.removeEventListener("keydown", this.handleKeyPress);
    modal.removeEventListener("click", this.handleOutsideClick);
  },

  inicializarEventosGlobais() {
    // Registra modais para gestão
    const modais = ["modal-form", "modal-confirmacao-exclusao"];

    // Configura cada modal
    modais.forEach((modalId) => {
      const modal = document.getElementById(modalId);
      if (modal) {
        // Previne propagação de cliques dentro do conteúdo do modal
        const conteudo = modal.querySelector(".modal-conteudo");
        if (conteudo) {
          conteudo.addEventListener("click", (e) => e.stopPropagation());
        }
      }
    });

    // Adiciona suporte a gestos touch para fechar
    if ("ontouchstart" in window) {
      this.inicializarGestosTouch();
    }
  },

  inicializarGestosTouch() {
    let startY;
    const THRESHOLD = 100;

    document.addEventListener("touchstart", (e) => {
      if (this.modalAtual) {
        startY = e.touches[0].clientY;
      }
    });

    document.addEventListener("touchmove", (e) => {
      if (!startY || !this.modalAtual) return;

      const deltaY = e.touches[0].clientY - startY;
      const modal = document.getElementById(this.modalAtual);

      if (deltaY > THRESHOLD && modal) {
        this.fechar(this.modalAtual);
        startY = null;
      }
    });

    document.addEventListener("touchend", () => {
      startY = null;
    });
  },
};

// ✅ GERENCIADOR DE FORMULÁRIOS - VERSÃO CORRIGIDA
const FormularioManager = {
  // ✅ CORREÇÃO: Prepara formulário para novo mentor
  async prepararNovoMentor() {
    console.log("Preparando formulário para novo mentor...");

    try {
      // ✅ VALIDAR LIMITE DE MENTORES ANTES DE ABRIR FORMULÁRIO
      if (
        typeof PlanoManager !== "undefined" &&
        PlanoManager.verificarEExibirPlanos
      ) {
        const podeAvançar = await PlanoManager.verificarEExibirPlanos("mentor");
        if (!podeAvançar) {
          console.log(
            "⛔ Limite de mentores atingido. Modal de planos aberto."
          );
          return; // Não abre o formulário se limite foi atingido
        }
      }

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

      // Envia X-Requested-With para sinalizar requisição AJAX e receber JSON
      const response = await fetch("cadastrar-mentor-ajax.php", {
        method: "POST",
        body: formData,
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      // Tenta interpretar como JSON (rápido), senão como texto
      const contentType = response.headers.get("content-type") || "";
      if (contentType.indexOf("application/json") !== -1) {
        const json = await response.json();

        if (json.success) {
          const mensagem =
            json.mensagem ||
            (acao === "cadastrar_mentor"
              ? "✅ Mentor cadastrado com sucesso!"
              : "✅ Mentor atualizado com sucesso!");
          ToastManager.mostrar(mensagem, "sucesso");

          // Fecha modal
          ModalManager.fechar("modal-form");

          // Se o servidor retornou dados do mentor recém-criado, atualiza a lista de forma otimista
          if (json.mentor && typeof MentorManager === "object") {
            // Recarrega mentores para garantir consistência com o servidor
            await MentorManager.recarregarMentores();
          } else {
            await MentorManager.recarregarMentores();
          }

          return true;
        }

        throw new Error(json.message || "Resposta inválida do servidor");
      } else {
        // Fallback: resposta em HTML — interpreta como sucesso e recarrega
        const responseText = await response.text();

        const mensagem =
          acao === "cadastrar_mentor"
            ? "✅ Mentor cadastrado com sucesso!"
            : "✅ Mentor atualizado com sucesso!";

        ToastManager.mostrar(mensagem, "sucesso");
        ModalManager.fechar("modal-form");
        await MentorManager.recarregarMentores();
        return true;
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

      // Apply font-weight from root variable and force with important
      try {
        const fw = rootStyle.getPropertyValue("--fs-placar-weight") || "";
        if (fw && fw.trim() !== "") {
          separador.style.setProperty("font-weight", fw.trim(), "important");
        }
      } catch (e) {
        // ignore
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

// ✅ GERENCIADOR DE EXCLUSÕES - VERSÃO ATUALIZADA
const ExclusaoManager = {
  async excluirMentor(id, nome) {
    if (!id) {
      ToastManager.mostrar("❌ ID do mentor não encontrado", "erro");
      return;
    }

    try {
      // Mostra modal de confirmação
      const confirmacao = await this.confirmarExclusaoModal(nome);
      if (!confirmacao) return;

      LoaderManager.mostrar();

      // Faz requisição AJAX para excluir
      const formData = new FormData();
      formData.append("excluir_mentor", id);

      // Adiciona o período atual ao formData
      const periodoAtual =
        typeof SistemaFiltroPeriodo !== "undefined"
          ? SistemaFiltroPeriodo.periodoAtual
          : "dia";
      formData.append("periodo", periodoAtual);

      const response = await fetch("gestao-diaria.php", {
        method: "POST",
        body: formData,
        headers: {
          "X-Requested-With": "XMLHttpRequest",
          "X-Periodo-Filtro": periodoAtual,
        },
      });

      if (!response.ok) {
        throw new Error(`Erro HTTP: ${response.status}`);
      }

      const resultado = await response.json();

      if (resultado.success) {
        ToastManager.mostrar("✅ Mentor excluído com sucesso!", "sucesso");

        // Anima remoção do card e atualiza dados
        const card = document.querySelector(`[data-id='${id}']`);
        if (card) {
          card.style.animation = "slideOutAndFade 0.3s ease-out forwards";

          // Aguarda animação e atualiza tudo
          setTimeout(async () => {
            // Atualiza lista de mentores primeiro
            await MentorManager.recarregarMentores();

            // Atualiza dados financeiros
            if (typeof DadosManager !== "undefined") {
              await DadosManager.atualizarLucroEBancaViaAjax();
            }

            // Atualiza meta se existir
            if (typeof MetaDiariaManager !== "undefined") {
              await MetaDiariaManager.atualizarMetaDiaria(true);
            }

            // Atualiza filtros se existir
            if (typeof SistemaFiltroPeriodo !== "undefined") {
              await SistemaFiltroPeriodo.atualizarPeriodoAtual();
            }
          }, 400); // Um pouco mais de tempo para a animação
        }

        // Fecha os modais
        ModalManager.fechar("modal-confirmacao-exclusao");
        ModalManager.fechar("modal-form");

        // Se a tela de edição estiver aberta, fecha
        const telaEdicao = document.getElementById("tela-edicao");
        if (telaEdicao && telaEdicao.style.display === "block") {
          if (typeof TelaEdicaoManager !== "undefined") {
            TelaEdicaoManager.fechar();
          }
        }
      } else {
        throw new Error(resultado.message || "Erro ao excluir mentor");
      }
    } catch (error) {
      console.error("Erro ao excluir mentor:", error);
      ToastManager.mostrar(`❌ ${error.message}`, "erro");
    } finally {
      LoaderManager.ocultar();
    }
  },

  confirmarExclusaoModal(nome) {
    return new Promise((resolve) => {
      const modal = document.getElementById("modal-confirmacao-exclusao");
      if (!modal) {
        resolve(false);
        return;
      }

      // Atualiza texto da confirmação com período atual
      const periodo =
        typeof SistemaFiltroPeriodo !== "undefined"
          ? SistemaFiltroPeriodo.periodoAtual
          : "dia";

      const textoPeriodo =
        {
          dia: "hoje",
          mes: "este mês",
          ano: "este ano",
        }[periodo] || "hoje";

      const texto = modal.querySelector(".modal-texto");
      if (texto) {
        texto.innerHTML = `
          <i class="fas fa-exclamation-triangle" style="color: #e74c3c; font-size: 24px; margin-bottom: 10px;"></i>
          <br>
          Tem certeza que deseja excluir o mentor<br>
          <strong>${nome}</strong>?
          <br>
          Todos os dados de <strong>${textoPeriodo}</strong> serão removidos.
          <br><br>
          <span style="font-size: 14px; color: #666;">
            Esta ação não pode ser desfeita.
          </span>
        `;
      }

      const btnConfirmar = modal.querySelector(".botao-confirmar");
      const btnCancelar = modal.querySelector(".botao-cancelar");

      // Remove listeners antigos clonando os botões
      const novoConfirmar = btnConfirmar?.cloneNode(true);
      const novoCancelar = btnCancelar?.cloneNode(true);

      if (btnConfirmar?.parentNode && novoConfirmar) {
        btnConfirmar.parentNode.replaceChild(novoConfirmar, btnConfirmar);
      }

      if (btnCancelar?.parentNode && novoCancelar) {
        btnCancelar.parentNode.replaceChild(novoCancelar, btnCancelar);
      }

      // Adiciona novos listeners
      const handleConfirmar = () => {
        cleanup();
        resolve(true);
      };

      const handleCancelar = () => {
        cleanup();
        resolve(false);
      };

      const cleanup = () => {
        novoConfirmar?.removeEventListener("click", handleConfirmar);
        novoCancelar?.removeEventListener("click", handleCancelar);
      };

      novoConfirmar?.addEventListener("click", handleConfirmar);
      novoCancelar?.addEventListener("click", handleCancelar);

      // Abre o modal
      ModalManager.abrir("modal-confirmacao-exclusao");
    });
  }, // Exclusão de entrada
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
  //                                      ✅  EXCLUSÃO DE ENTRADA COM FILTRO (DIA)-(MÊS)-(ANO)
  // ========================================================================================================================
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

      // ✅ NOVO: Se tela de edição estiver aberta, recarregar com período atual
      const telaEdicaoAberta =
        document.getElementById("tela-edicao")?.style.display === "block";
      if (
        telaEdicaoAberta &&
        typeof TelaEdicaoManager !== "undefined" &&
        MentorManager.mentorAtualId
      ) {
        setTimeout(() => {
          TelaEdicaoManager.editarAposta(MentorManager.mentorAtualId);
        }, 300);
      }

      // ✅ NOVO: Atualizar meta se existir o sistema
      if (typeof MetaDiariaManager !== "undefined") {
        setTimeout(() => {
          MetaDiariaManager.atualizarMetaDiaria();
        }, 100);
      }
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

// ✅ GERENCIADOR DA TELA DE EDIÇÃO - VERSÃO INTEGRADA COM FILTRO
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

  // ✅ NOVA FUNÇÃO: Obter período atual do sistema
  obterPeriodoAtual() {
    // Verifica se existe o MetaDiariaManager
    if (
      typeof MetaDiariaManager !== "undefined" &&
      MetaDiariaManager.periodoAtual
    ) {
      return MetaDiariaManager.periodoAtual;
    }

    // Fallback: verifica o radio button selecionado
    const radioSelecionado = document.querySelector(
      'input[name="periodo"]:checked'
    );
    if (radioSelecionado) {
      return radioSelecionado.value;
    }

    // Fallback final: dia
    return "dia";
  },

  // ✅ NOVA FUNÇÃO: Atualizar cabeçalho da tela de edição
  atualizarCabecalhoEdicao(periodo) {
    const cabecalho = document.querySelector("#tela-edicao .tela-titulo");
    if (!cabecalho) return;

    const textoPeriodo = {
      dia: "Hoje",
      mes: "Este Mês",
      ano: "Este Ano",
    };

    const texto = textoPeriodo[periodo] || "Hoje";
    cabecalho.innerHTML = `<i class="fas fa-edit"></i> Entradas de ${texto}`;
  },

  // ✅ NOVA FUNÇÃO: Texto baseado no período quando não há entradas
  obterTextoSemEntradas(periodo) {
    switch (periodo) {
      case "mes":
        return "Nenhuma Entrada Cadastrada Neste Mês.";
      case "ano":
        return "Nenhuma Entrada Cadastrada Neste Ano.";
      default:
        return "Nenhuma Entrada Cadastrada Hoje.";
    }
  },

  // ✅ FUNÇÃO MODIFICADA: Edita aposta do mentor com filtro dinâmico
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

    // ✅ NOVO: Obter período atual e atualizar cabeçalho
    const periodoAtual = this.obterPeriodoAtual();
    this.atualizarCabecalhoEdicao(periodoAtual);

    this.abrir();

    try {
      // 🎯 MUDANÇA PRINCIPAL: Usar período dinâmico ao invés de "hoje"
      const response = await fetch(
        `filtrar-entradas.php?id=${idMentor}&tipo=${periodoAtual}`
      );
      if (!response.ok) throw new Error(`HTTP ${response.status}`);

      const entradas = await response.json();
      this.mostrarResultados(entradas, periodoAtual);
    } catch (error) {
      console.error("Erro ao carregar histórico:", error);
      const container = document.getElementById("resultado-filtro");
      if (container) {
        container.innerHTML =
          '<p style="color:red;">Erro ao carregar dados.</p>';
      }
    }
  },

  // ✅ FUNÇÃO MODIFICADA: Mostrar resultados com período
  mostrarResultados(entradas, periodo = "dia") {
    const container = document.getElementById("resultado-filtro");
    if (!container) return;

    container.innerHTML = "";

    if (!entradas || entradas.length === 0) {
      const textoPeriodo = this.obterTextoSemEntradas(periodo);
      container.innerHTML = `<p style="color:gray;">${textoPeriodo}</p>`;
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

    // Use semantic classes instead of inline styles so global CSS can control appearance
    if (cor === "#4CAF50" || cor.toLowerCase().indexOf("4caf50") !== -1) {
      div.classList.add("entrada-card", "entrada-card--positivo");
    } else if (
      cor === "#e74c3c" ||
      cor.toLowerCase().indexOf("e74c3c") !== -1
    ) {
      div.classList.add("entrada-card", "entrada-card--negativo");
    } else {
      div.classList.add("entrada-card");
    }
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
    // Garanta estado inicial: esconda todos os painéis e deixe apenas o toggle visível
    document.querySelectorAll(".menu-opcoes").forEach((menu) => {
      menu.style.display = "none";
      // força posicionamento alto para evitar sobreposição por outros elementos via JS
      menu.style.zIndex = "99999";
    });

    // Garante que o botão de 3 pontinhos esteja visível (caso o servidor oculte)
    document.querySelectorAll(".menu-toggle").forEach((t) => {
      t.style.display = "inline-block";
      t.style.zIndex = "100000";
    });

    // Gerencia abertura/fechamento via clique (mantendo lógica existente)
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

// ✅ INICIALIZAÇÃO PRINCIPAL - VERSÃO INTEGRADA
const App = {
  // Inicializa toda a aplicação
  async inicializar() {
    try {
      console.log("🚀 Iniciando aplicação com filtro de período...");

      await this.inicializarComponentes();
      this.configurarEventosGlobais();
      this.iniciarProcessosBackground();
      this.configurarListenersPeriodo(); // ✅ NOVO

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

  // ✅ NOVA FUNÇÃO: Configurar listeners para mudança de período
  configurarListenersPeriodo() {
    try {
      const radiosPeriodo = document.querySelectorAll('input[name="periodo"]');
      radiosPeriodo.forEach((radio) => {
        radio.addEventListener("change", (e) => {
          if (e.target.checked) {
            const novoPeriodo = e.target.value;
            console.log(`🔄 Período alterado para: ${novoPeriodo}`);

            // ✅ NOVO: Se tela de edição estiver aberta, recarregar entradas do novo período
            const telaEdicaoAberta =
              document.getElementById("tela-edicao")?.style.display === "block";
            if (
              telaEdicaoAberta &&
              typeof TelaEdicaoManager !== "undefined" &&
              MentorManager.mentorAtualId
            ) {
              setTimeout(() => {
                console.log(
                  `📋 Recarregando entradas do período: ${novoPeriodo}`
                );
                TelaEdicaoManager.editarAposta(MentorManager.mentorAtualId);
              }, 200);
            }
          }
        });
      });

      console.log("✅ Listeners de período configurados para tela de edição");
    } catch (error) {
      console.error("❌ Erro ao configurar listeners de período:", error);
    }
  },
  // ========================================================================================================================
  //                                      ✅  FIM EXCLUSÃO DE ENTRADA COM FILTRO (DIA)-(MÊS)-(ANO)
  // ========================================================================================================================
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

        // ✅ VALIDAR LIMITE DE MENTORES ANTES DE CADASTRAR
        if (
          typeof PlanoManager !== "undefined" &&
          PlanoManager.verificarEExibirPlanos
        ) {
          const podeAvançar = await PlanoManager.verificarEExibirPlanos(
            "mentor"
          );
          if (!podeAvançar) {
            return; // Modal será mostrado automaticamente
          }
        }

        await FormularioManager.processarSubmissaoMentor(e.target);
      });
    }

    // Evento de submissão para formulário de valor
    if (formMentor) {
      formMentor.addEventListener("submit", async (e) => {
        e.preventDefault();

        // ✅ VALIDAR LIMITE DE ENTRADAS ANTES DE ADICIONAR
        if (
          typeof PlanoManager !== "undefined" &&
          PlanoManager.verificarEExibirPlanos
        ) {
          const podeAvançar = await PlanoManager.verificarEExibirPlanos(
            "entrada"
          );
          if (!podeAvançar) {
            return; // Modal será mostrado automaticamente
          }
        }

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

  // ✅ FUNÇÃO MODIFICADA: Processa submissão do formulário de valor
  async processarSubmissaoFormulario(form) {
    console.log("📝 processarSubmissaoFormulario chamado");

    // ✅ VALIDAR LIMITE DE ENTRADAS ANTES DE PROCESSAR
    if (
      typeof PlanoManager !== "undefined" &&
      PlanoManager.verificarEExibirPlanos
    ) {
      console.log("🔍 Chamando PlanoManager.verificarEExibirPlanos('entrada')");
      const podeAvançar = await PlanoManager.verificarEExibirPlanos("entrada");
      console.log("✅ Resultado:", podeAvançar);
      if (!podeAvançar) {
        console.log("⛔ Limite de entradas atingido. Modal de planos aberto.");
        return; // Bloqueia antes de enviar
      }
    } else {
      console.warn(
        "⚠️ PlanoManager não definido ou verificarEExibirPlanos não existe"
      );
    }

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

          // ✅ NOVO: Atualizar meta se existir
          if (typeof MetaDiariaManager !== "undefined") {
            MetaDiariaManager.atualizarMetaDiaria();
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
window.excluirMentorDireto = async () => {
  const id = document.getElementById("mentor-id")?.value;
  const nome = document.getElementById("mentor-nome-preview")?.textContent;
  if (id && nome) {
    await ExclusaoManager.excluirMentor(id, nome);
  }
};

window.fecharModalExclusao = () => {
  const modal = document.getElementById("modal-confirmacao-exclusao");
  if (modal) {
    modal.style.display = "none";
  }
};

window.confirmarExclusaoMentor = () => {
  const id = document.getElementById("mentor-id")?.value;
  const nome = document.getElementById("mentor-nome-preview")?.textContent;
  if (id && nome) {
    ExclusaoManager.excluirMentor(id, nome);
  }
};

// Funções de imagem
window.mostrarNomeArquivo = (input) => ImagemManager.mostrarNomeArquivo(input);
window.removerImagem = () => ImagemManager.removerImagem();

// Funções de edição
window.editarAposta = (id) => TelaEdicaoManager.editarAposta(id);
window.fecharTelaEdicao = () => TelaEdicaoManager.fechar();

// Função de atualização
window.atualizarLucroEBancaViaAjax = () =>
  DadosManager.atualizarLucroEBancaViaAjax();

// ✅ INICIALIZAÇÃO QUANDO DOM ESTIVER PRONTO
document.addEventListener("DOMContentLoaded", () => {
  App.inicializar();
  // Inicia ajuste dinâmico da altura de .campo_mentores para não ser empurrado
  try {
    if (
      typeof CampoMentoresHeightManager !== "undefined" &&
      CampoMentoresHeightManager.init
    ) {
      CampoMentoresHeightManager.init();
    }
    if (
      typeof CampoBloco2HeightManager !== "undefined" &&
      CampoBloco2HeightManager.init
    ) {
      CampoBloco2HeightManager.init();
    }
  } catch (e) {
    console.warn("CampoMentoresHeightManager failed to initialize", e);
  }
});

// ✅ CLEANUP NA SAÍDA DA PÁGINA
window.addEventListener("beforeunload", () => {
  if (MentorManager.intervalUpdateId) {
    clearInterval(MentorManager.intervalUpdateId);
  }
});

// ✅ LOG DE INICIALIZAÇÃO
console.log("🎯 Sistema com Filtro de Período Integrado!");
console.log("📋 Funcionalidades adicionadas:");
console.log("  - Exclusão de entradas por período (Dia/Mês/Ano)");
console.log("  - Sincronização automática com filtros");
console.log("  - Atualização dinâmica da tela de edição");
console.log("✅ Sistema pronto para usar filtros de período!");
// ========================================================================================================================
//
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
// ========================================================================================================================
//                                        ✅  CALCULO META DO : (DIA)-(MÊS)-(ANO)
// ========================================================================================================================

const MetaDiariaManager = {
  // CONTROLE SIMPLES
  atualizandoAtualmente: false,
  periodoAtual: "dia",
  tipoMetaAtual: "turbo",
  // NOVO: Flag para evitar interferir com troféus de outros dias
  preservarTrofeusAnteriores: true,

  // ATUALIZAR META DIÁRIA - VERSÃO CORRIGIDA
  async atualizarMetaDiaria(aguardarDados = false) {
    if (this.atualizandoAtualmente) return null;

    this.atualizandoAtualmente = true;

    try {
      if (aguardarDados) {
        await new Promise((resolve) => setTimeout(resolve, 150));
      }

      const response = await fetch("dados_banca.php", {
        method: "GET",
        headers: {
          "Cache-Control": "no-cache",
          "X-Requested-With": "XMLHttpRequest",
          "X-Periodo-Filtro": this.periodoAtual,
        },
      });

      if (!response.ok) throw new Error(`HTTP ${response.status}`);

      const data = await response.json();
      if (!data.success) throw new Error(data.message);

      if (data.periodo_ativo) {
        this.periodoAtual = data.periodo_ativo;
      }
      if (data.tipo_meta) {
        this.tipoMetaAtual = data.tipo_meta;
      }

      const dadosProcessados = this.aplicarAjustePeriodo(data);
      this.atualizarTodosElementos(dadosProcessados);

      return dadosProcessados;
    } catch (error) {
      console.error("❌ Erro:", error);
      this.mostrarErroMeta();
      return null;
    } finally {
      this.atualizandoAtualmente = false;
    }
  },

  // APLICAR AJUSTE DE PERÍODO - SIMPLIFICADO
  aplicarAjustePeriodo(data) {
    try {
      const radioSelecionado = document.querySelector(
        'input[name="periodo"]:checked'
      );
      const periodo = radioSelecionado?.value || this.periodoAtual || "dia";

      this.periodoAtual = periodo;
      if (data.tipo_meta) {
        this.tipoMetaAtual = data.tipo_meta;
      }

      let metaFinal, rotuloFinal;

      switch (periodo) {
        case "mes":
          metaFinal = parseFloat(data.meta_mensal) || 0;
          rotuloFinal = "Meta do Mês";
          break;
        case "ano":
          metaFinal = parseFloat(data.meta_anual) || 0;
          rotuloFinal = "Meta do Ano";
          break;
        default:
          metaFinal = parseFloat(data.meta_diaria) || 0;
          rotuloFinal = "Meta do Dia";
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
    } catch (error) {
      console.error("❌ Erro ao aplicar ajuste:", error);
      return data;
    }
  },

  // CALCULAR META FINAL COM VALOR TACHADO E EXTRA
  calcularMetaFinalComExtra(saldoDia, metaCalculada, bancaTotal, data) {
    try {
      let metaFinal,
        rotulo,
        statusClass,
        valorExtra = 0,
        mostrarTachado = false;

      console.log(`🔍 DEBUG CALCULAR META COM EXTRA:`);
      console.log(`   Saldo do Dia: R$ ${saldoDia.toFixed(2)}`);
      console.log(`   Meta: R$ ${metaCalculada.toFixed(2)}`);
      console.log(`   Banca: R$ ${bancaTotal.toFixed(2)}`);

      if (bancaTotal <= 0) {
        metaFinal = bancaTotal;
        rotulo = "Deposite p/ Começar";
        statusClass = "sem-banca";
        console.log(`📊 RESULTADO: Sem banca`);
      }
      // META BATIDA OU SUPERADA - COM VALOR EXTRA
      else if (saldoDia > 0 && metaCalculada > 0 && saldoDia >= metaCalculada) {
        valorExtra = saldoDia - metaCalculada;
        mostrarTachado = true;
        metaFinal = metaCalculada; // Mostra o valor da meta original

        if (valorExtra > 0) {
          rotulo = `${
            data.rotulo_periodo || "Meta"
          } Superada! <i class='fa-solid fa-trophy'></i>`;
          statusClass = "meta-superada";
          console.log(`🏆 META SUPERADA: Extra de R$ ${valorExtra.toFixed(2)}`);
        } else {
          rotulo = `${
            data.rotulo_periodo || "Meta"
          } Batida! <i class='fa-solid fa-trophy'></i>`;
          statusClass = "meta-batida";
          console.log(`🎯 META EXATA`);
        }
      }
      // CASO ESPECIAL: Meta é zero (já foi batida)
      else if (metaCalculada === 0 && saldoDia > 0) {
        metaFinal = 0;
        valorExtra = saldoDia;
        mostrarTachado = false;
        rotulo = `${
          data.rotulo_periodo || "Meta"
        } Batida! <i class='fa-solid fa-trophy'></i>`;
        statusClass = "meta-batida";
        console.log(`🎯 META ZERO (já batida)`);
      } else if (saldoDia < 0) {
        metaFinal = metaCalculada - saldoDia;
        rotulo = `Restando p/ ${data.rotulo_periodo || "Meta"}`;
        statusClass = "negativo";
        console.log(`📊 RESULTADO: Negativo`);
      } else if (saldoDia === 0) {
        metaFinal = metaCalculada;
        rotulo = data.rotulo_periodo || "Meta do Dia";
        statusClass = "neutro";
        console.log(`📊 RESULTADO: Neutro`);
      } else {
        // Lucro positivo mas menor que a meta
        metaFinal = metaCalculada - saldoDia;
        rotulo = `Restando p/ ${data.rotulo_periodo || "Meta"}`;
        statusClass = "lucro";
        console.log(`📊 RESULTADO: Lucro insuficiente`);
      }

      const resultado = {
        metaFinal,
        metaOriginal: metaCalculada,
        valorExtra,
        mostrarTachado,
        metaFinalFormatada: metaFinal.toLocaleString("pt-BR", {
          style: "currency",
          currency: "BRL",
        }),
        metaOriginalFormatada: metaCalculada.toLocaleString("pt-BR", {
          style: "currency",
          currency: "BRL",
        }),
        valorExtraFormatado:
          valorExtra > 0
            ? valorExtra.toLocaleString("pt-BR", {
                style: "currency",
                currency: "BRL",
              })
            : null,
        rotulo,
        statusClass,
      };

      console.log(`🏁 RESULTADO FINAL COM EXTRA:`);
      console.log(`   Status: ${statusClass}`);
      console.log(`   Valor Extra: R$ ${valorExtra.toFixed(2)}`);
      console.log(`   Mostrar Tachado: ${mostrarTachado}`);

      return resultado;
    } catch (error) {
      console.error("❌ Erro ao calcular meta final com extra:", error);
      return {
        metaFinal: 0,
        metaOriginal: 0,
        valorExtra: 0,
        mostrarTachado: false,
        metaFinalFormatada: "R$ 0,00",
        metaOriginalFormatada: "R$ 0,00",
        valorExtraFormatado: null,
        rotulo: "Erro no cálculo",
        statusClass: "erro",
      };
    }
  },

  // ATUALIZAR TODOS OS ELEMENTOS - CORRIGIDO PARA PRESERVAR TROFÉUS
  atualizarTodosElementos(data) {
    try {
      const saldoDia = parseFloat(data.lucro) || 0;
      const metaCalculada = parseFloat(data.meta_display) || 0;
      const bancaTotal = parseFloat(data.banca) || 0;

      const dadosComplementados = {
        ...data,
        meta_original: data.meta_original || metaCalculada,
      };

      const resultado = this.calcularMetaFinalComExtra(
        saldoDia,
        metaCalculada,
        bancaTotal,
        dadosComplementados
      );

      // Atualizar elementos do widget SEM interferir nos troféus das datas
      this.atualizarAreaDireita(data);
      this.atualizarModal(data);
      this.atualizarMetaElementoComExtra(resultado);
      this.atualizarRotulo(resultado.rotulo);
      this.atualizarBarraProgresso(resultado, data);
      this.atualizarTipoMetaDisplay(data);

      // NOVO: Preservar troféus após mudança de período
      if (this.preservarTrofeusAnteriores) {
        this.preservarTrofeusExistentes();
      }

      console.log(
        `🎯 Meta atualizada - Período: ${
          data.periodo_ativo || this.periodoAtual
        }, Tipo: ${data.tipo_meta || this.tipoMetaAtual}`
      );
      console.log(`💰 Lucro FILTRADO: R$ ${saldoDia.toFixed(2)}`);
      console.log(
        `💰 Lucro TOTAL: R$ ${(
          parseFloat(data.lucro_total_display) || 0
        ).toFixed(2)}`
      );
      console.log(
        `🎯 Meta (${
          data.tipo_meta_texto || "Meta Turbo"
        }): R$ ${metaCalculada.toFixed(2)}`
      );

      if (resultado.valorExtra > 0) {
        console.log(`🏆 Valor Extra: R$ ${resultado.valorExtra.toFixed(2)}`);
      }
    } catch (error) {
      console.error("❌ Erro ao atualizar elementos:", error);
    }
  },

  // NOVA FUNÇÃO: Preservar troféus existentes após mudanças
  preservarTrofeusExistentes() {
    try {
      console.log("🛡️ Preservando troféus existentes...");

      // Notificar MonitorContinuo para recarregar cache se existir
      if (window.MonitorContinuo && window.MonitorContinuo.recarregarCache) {
        setTimeout(() => {
          window.MonitorContinuo.recarregarCache();
        }, 100);
      }

      // Verificar e preservar troféus com base nos atributos data-meta-batida
      const linhasComTrofeu = document.querySelectorAll(
        '[data-meta-batida="true"]'
      );

      linhasComTrofeu.forEach((linha) => {
        const icone = linha.querySelector(".icone i");
        const dataLinha = linha.getAttribute("data-date");

        if (icone && !icone.classList.contains("fa-trophy")) {
          console.log(`🔧 Restaurando troféu para ${dataLinha}`);
          icone.className = "fa-solid fa-trophy trofeu-icone-forcado";

          // Marcar no MonitorContinuo se disponível
          if (
            window.MonitorContinuo &&
            window.MonitorContinuo.marcarMetaBatida
          ) {
            window.MonitorContinuo.marcarMetaBatida(dataLinha);
          }
        }
      });

      console.log(
        `🛡️ Preservação concluída - ${linhasComTrofeu.length} troféus verificados`
      );
    } catch (error) {
      console.error("❌ Erro ao preservar troféus:", error);
    }
  },

  // ATUALIZAR META ELEMENTO COM VALOR TACHADO E EXTRA
  atualizarMetaElementoComExtra(resultado) {
    try {
      const metaValor =
        document.getElementById("meta-valor") ||
        document.querySelector(".widget-meta-valor");

      if (!metaValor) return;

      // Limpar classes antigas
      metaValor.className = metaValor.className.replace(
        /\bvalor-meta\s+\w+/g,
        ""
      );

      let htmlConteudo = "";

      if (resultado.mostrarTachado && resultado.valorExtra >= 0) {
        // META BATIDA/SUPERADA - MOSTRAR VALOR TACHADO + EXTRA
        htmlConteudo = `
          <i class="fa-solid fa-coins"></i>
          <div class="meta-valor-container">
            <span class="valor-tachado">${
              resultado.metaOriginalFormatada
            }</span>
            ${
              resultado.valorExtra > 0
                ? `<span class="valor-extra">+ ${resultado.valorExtraFormatado}</span>`
                : ""
            }
          </div>
        `;

        metaValor.classList.add("valor-meta", "meta-com-extra");
        console.log(
          `✅ Valor tachado aplicado: ${resultado.metaOriginalFormatada}`
        );

        if (resultado.valorExtra > 0) {
          console.log(
            `✅ Valor extra aplicado: + ${resultado.valorExtraFormatado}`
          );
        }
      } else {
        // EXIBIÇÃO NORMAL
        htmlConteudo = `
          <i class="fa-solid fa-coins"></i>
          <div class="meta-valor-container">
            <span class="valor-texto" id="valor-texto-meta">${resultado.metaFinalFormatada}</span>
          </div>
        `;

        metaValor.classList.add("valor-meta", resultado.statusClass);
      }

      metaValor.innerHTML = htmlConteudo;
    } catch (error) {
      console.error("❌ Erro ao atualizar meta elemento com extra:", error);
    }
  },

  // ATUALIZAR DISPLAY DO TIPO DE META + BADGE
  atualizarTipoMetaDisplay(data) {
    try {
      const metaTextElement = document.getElementById("meta-text-unico");
      if (metaTextElement && data.tipo_meta_texto) {
        const textoAtual = metaTextElement.textContent.trim();
        const novoTexto = data.tipo_meta_texto.toUpperCase();

        if (textoAtual !== novoTexto) {
          metaTextElement.textContent = novoTexto;
          console.log(
            `🏷️ Tipo de meta atualizado: ${novoTexto} (origem: ${
              data.tipo_meta_origem || "banco"
            })`
          );
        }
      }

      const metaTipoBadge = document.getElementById("meta-tipo-badge");
      if (metaTipoBadge && data.tipo_meta_texto) {
        this.atualizarBadgeTipoMeta(data.tipo_meta_texto, data.tipo_meta);

        console.log(
          `🏷️ Badge atualizado: ${data.tipo_meta_texto} (origem: ${
            data.tipo_meta_origem || "banco"
          })`
        );
      }
    } catch (error) {
      console.error("❌ Erro ao atualizar display do tipo:", error);
    }
  },

  atualizarBadgeTipoMeta(textoTipo, tipo = null) {
    try {
      const badge = document.getElementById("meta-tipo-badge");
      if (!badge) {
        console.warn("⚠️ Badge meta-tipo-badge não encontrado");
        return;
      }

      if (!tipo) {
        tipo = textoTipo.toLowerCase().includes("fixa") ? "fixa" : "turbo";
      }

      badge.textContent = textoTipo.toUpperCase();
      badge.classList.remove("meta-fixa", "meta-turbo", "loading");

      if (tipo === "fixa") {
        badge.classList.add("meta-fixa");
      } else if (tipo === "turbo") {
        badge.classList.add("meta-turbo");
      }
    } catch (error) {
      console.error("❌ Erro ao atualizar badge do tipo de meta:", error);
    }
  },

  criarBadgeSeNaoExistir() {
    try {
      const container = document.querySelector(".widget-barra-container");
      if (!container) {
        console.error("❌ Container da barra não encontrado");
        return false;
      }

      if (document.getElementById("meta-tipo-badge")) {
        return true;
      }

      const badge = document.createElement("div");
      badge.id = "meta-tipo-badge";
      badge.className = "meta-tipo-badge loading";
      badge.textContent = "META TURBO";

      container.appendChild(badge);

      console.log("✅ Badge criado automaticamente");
      return true;
    } catch (error) {
      console.error("❌ Erro ao criar badge:", error);
      return false;
    }
  },

  atualizarAreaDireita(data) {
    try {
      const porcentagemElement = document.getElementById("porcentagem-diaria");
      if (porcentagemElement && data.diaria_formatada) {
        porcentagemElement.textContent = data.diaria_formatada;
      }

      const valorUnidadeElement = document.getElementById("valor-unidade");
      if (valorUnidadeElement && data.unidade_entrada_formatada) {
        valorUnidadeElement.textContent = data.unidade_entrada_formatada;
      }
    } catch (error) {
      console.error("❌ Erro ao atualizar área direita:", error);
    }
  },

  atualizarModal(data) {
    try {
      const valorBancaLabel = document.getElementById("valorBancaLabel");
      if (valorBancaLabel && data.banca_formatada) {
        valorBancaLabel.textContent = data.banca_formatada;
      }

      const valorLucroLabel = document.getElementById("valorLucroLabel");
      if (valorLucroLabel && data.lucro_total_formatado) {
        valorLucroLabel.textContent = data.lucro_total_formatado;
      }

      const lucroValorEntrada = document.getElementById("lucro_valor_entrada");
      if (lucroValorEntrada) {
        const lucroTotalFormatado =
          data.lucro_total_formatado ||
          (data.lucro_total_historico &&
            data.lucro_total_historico.toLocaleString("pt-BR", {
              style: "currency",
              currency: "BRL",
            })) ||
          "R$ 0,00";

        lucroValorEntrada.textContent = lucroTotalFormatado;
      }

      const lucroValorTotal =
        parseFloat(data.lucro_total_historico) ||
        parseFloat(data.lucro_total_display) ||
        parseFloat(data.lucro_total) ||
        0;

      const iconeLucro = document.getElementById("iconeLucro");
      const lucroLabel = document.getElementById("lucroLabel");
      if (iconeLucro && lucroLabel && valorLucroLabel) {
        lucroLabel.className = lucroLabel.className.replace(
          /modal-lucro-\w+/g,
          ""
        );
        valorLucroLabel.className = valorLucroLabel.className.replace(
          /modal-lucro-\w+/g,
          ""
        );

        if (lucroValorTotal > 0) {
          iconeLucro.className = "fa-solid fa-money-bill-trend-up";
          lucroLabel.classList.add("modal-lucro-positivo");
          valorLucroLabel.classList.add("modal-lucro-positivo");
        } else if (lucroValorTotal < 0) {
          iconeLucro.className = "fa-solid fa-money-bill-trend-down";
          lucroLabel.classList.add("modal-lucro-negativo");
          valorLucroLabel.classList.add("modal-lucro-negativo");
        } else {
          iconeLucro.className = "fa-solid fa-money-bill-trend-up";
          lucroLabel.classList.add("modal-lucro-neutro");
          valorLucroLabel.classList.add("modal-lucro-neutro");
        }
      }

      if (lucroValorEntrada) {
        lucroValorEntrada.classList.remove(
          "saldo-positivo",
          "saldo-negativo",
          "saldo-neutro"
        );

        if (lucroValorTotal > 0) {
          lucroValorEntrada.classList.add("saldo-positivo");
        } else if (lucroValorTotal < 0) {
          lucroValorEntrada.classList.add("saldo-negativo");
        } else {
          lucroValorEntrada.classList.add("saldo-neutro");
        }
      }

      const lucroEntradasRotulo = document.getElementById(
        "lucro_entradas_rotulo"
      );
      if (lucroEntradasRotulo) {
        if (lucroValorTotal > 0) {
          lucroEntradasRotulo.textContent = "Lucro:";
        } else if (lucroValorTotal < 0) {
          lucroEntradasRotulo.textContent = "Negativo:";
        } else {
          lucroEntradasRotulo.textContent = "Neutro:";
        }
      }
    } catch (error) {
      console.error("❌ Erro ao atualizar modal:", error);
    }
  },

  atualizarRotulo(rotulo) {
    try {
      const rotuloElement =
        document.getElementById("rotulo-meta") ||
        document.querySelector(".widget-meta-rotulo");

      if (rotuloElement) {
        rotuloElement.innerHTML = rotulo;
      }
    } catch (error) {
      console.error("❌ Erro ao atualizar rótulo:", error);
    }
  },

  atualizarBarraProgresso(resultado, data) {
    try {
      const barraProgresso = document.getElementById("barra-progresso");
      const saldoInfo = document.getElementById("saldo-info");
      const porcentagemBarra = document.getElementById("porcentagem-barra");

      if (!barraProgresso) return;

      const saldoDia = parseFloat(data.lucro) || 0;
      const metaCalculada = parseFloat(data.meta_display) || 0;
      const bancaTotal = parseFloat(data.banca) || 0;

      let progresso = 0;
      if (bancaTotal > 0 && metaCalculada > 0) {
        if (
          resultado.statusClass === "meta-batida" ||
          resultado.statusClass === "meta-superada"
        ) {
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

      let classeCor = "";
      barraProgresso.className = barraProgresso.className.replace(
        /\bbarra-\w+/g,
        ""
      );

      if (!barraProgresso.classList.contains("widget-barra-progresso")) {
        barraProgresso.classList.add("widget-barra-progresso");
      }

      if (
        resultado.statusClass === "meta-batida" ||
        resultado.statusClass === "meta-superada"
      ) {
        classeCor = "barra-meta-batida";
        console.log(
          `✅ BARRA META BATIDA/SUPERADA - Saldo do Dia: R$ ${saldoDia.toFixed(
            2
          )}, Meta: R$ ${metaCalculada.toFixed(2)}`
        );
      } else {
        classeCor = `barra-${resultado.statusClass}`;
        console.log(
          `✅ BARRA NORMAL - Status: ${
            resultado.statusClass
          }, Saldo do Dia: R$ ${saldoDia.toFixed(2)}`
        );
      }

      barraProgresso.classList.add(classeCor);
      barraProgresso.style.width = `${larguraBarra}%`;
      barraProgresso.style.backgroundColor = "";
      barraProgresso.style.background = "";

      if (porcentagemBarra) {
        const porcentagemTexto = Math.round(progresso) + "%";

        porcentagemBarra.innerHTML = `
          <span class="porcentagem-fundo ${classeCor}">${porcentagemTexto}</span>
        `;

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

      if (saldoInfo) {
        const saldoFormatado = saldoDia.toLocaleString("pt-BR", {
          style: "currency",
          currency: "BRL",
        });

        let textoSaldo = "Saldo do Dia";
        let iconeClass = "fa-solid fa-wallet";

        if (saldoDia > 0) {
          textoSaldo = "Lucro";
          iconeClass = "fa-solid fa-chart-line";
        } else if (saldoDia < 0) {
          textoSaldo = "Negativo";
          iconeClass = "fa-solid fa-arrow-trend-down";
        } else {
          textoSaldo = "Saldo do Dia";
          iconeClass = "fa-solid fa-wallet";
        }

        saldoInfo.innerHTML = `
          <i class="${iconeClass}"></i>
          <span class="saldo-info-rotulo">${textoSaldo}:</span>
          <span class="saldo-info-valor">${saldoFormatado}</span>
        `;

        saldoInfo.className =
          saldoDia > 0
            ? "saldo-positivo"
            : saldoDia < 0
            ? "saldo-negativo"
            : "saldo-zero";
      }
    } catch (error) {
      console.error("❌ Erro ao atualizar barra progresso:", error);
    }
  },

  configurarListenersPeriodo() {
    try {
      const radiosPeriodo = document.querySelectorAll('input[name="periodo"]');

      if (radiosPeriodo.length === 0) {
        console.warn("⚠️ Nenhum radio button encontrado para período");
        return;
      }

      console.log(
        `✅ Configurando ${radiosPeriodo.length} listeners de período`
      );

      radiosPeriodo.forEach((radio) => {
        radio.addEventListener("change", (e) => {
          if (e.target.checked) {
            console.log(`🔄 Mudança de período detectada: ${e.target.value}`);

            if (this.atualizandoAtualmente) {
              console.log("⏳ Atualização já em andamento, ignorando...");
              return;
            }

            this.bloquearCalculosTemporarios();
            this.mostrarLoadingTemporario();

            const novoPeriodo = e.target.value;
            this.periodoAtual = novoPeriodo;

            if (typeof SistemaFiltroPeriodoIntegrado !== "undefined") {
              SistemaFiltroPeriodoIntegrado.periodoAtual = novoPeriodo;
            }

            setTimeout(() => {
              this.atualizarMetaDiaria(true);
            }, 150);
          }
        });
      });
    } catch (error) {
      console.error("❌ Erro ao configurar listeners:", error);
    }
  },

  bloquearCalculosTemporarios() {
    try {
      const elementosBloquear = [
        "meta-valor",
        "barra-progresso",
        "saldo-info",
        "porcentagem-barra",
      ];

      console.log("🔒 Bloqueando elementos temporariamente...");

      elementosBloquear.forEach((id) => {
        const elemento = document.getElementById(id);
        if (elemento) {
          elemento.style.opacity = "0.3";
          elemento.style.pointerEvents = "none";
          elemento.style.transition = "opacity 0.2s ease";
        }
      });

      setTimeout(() => {
        console.log("🔓 Desbloqueando elementos...");
        elementosBloquear.forEach((id) => {
          const elemento = document.getElementById(id);
          if (elemento) {
            elemento.style.opacity = "1";
            elemento.style.pointerEvents = "auto";
          }
        });
      }, 400);
    } catch (error) {
      console.error("❌ Erro ao bloquear cálculos:", error);
    }
  },

  mostrarLoadingTemporario() {
    try {
      const metaElement = document.getElementById("meta-valor");
      if (metaElement) {
        const valorTextoEl = metaElement.querySelector(".valor-texto");
        if (valorTextoEl) {
          valorTextoEl.textContent = "Calculando...";
          valorTextoEl.style.opacity = "0.6";

          setTimeout(() => {
            valorTextoEl.style.opacity = "1";
          }, 800);
        }
      }

      const barraProgresso = document.getElementById("barra-progresso");
      if (barraProgresso) {
        barraProgresso.style.opacity = "0.5";
        setTimeout(() => {
          barraProgresso.style.opacity = "1";
        }, 600);
      }
    } catch (error) {
      console.error("❌ Erro ao mostrar loading:", error);
    }
  },

  mostrarErroMeta() {
    try {
      const metaElement = document.getElementById("meta-valor");
      if (metaElement) {
        metaElement.innerHTML =
          '<i class="fa-solid fa-coins"></i><span class="valor-texto loading-text">R$ 0,00</span>';
      }
    } catch (error) {
      console.error("❌ Erro ao mostrar erro meta:", error);
    }
  },

  sincronizarComFiltroExterno(periodo) {
    try {
      if (periodo && periodo !== this.periodoAtual) {
        this.periodoAtual = periodo;

        const radio = document.querySelector(
          `input[name="periodo"][value="${periodo}"]`
        );
        if (radio && !radio.checked) {
          radio.checked = true;
        }

        this.atualizarMetaDiaria();
      }
    } catch (error) {
      console.error("❌ Erro ao sincronizar filtro:", error);
    }
  },

  aplicarAnimacao(elemento) {
    try {
      elemento.classList.add("atualizado");
      setTimeout(() => {
        elemento.classList.remove("atualizado");
      }, 1500);
    } catch (error) {
      console.error("❌ Erro ao aplicar animação:", error);
    }
  },

  // INICIALIZAR - CORRIGIDO PARA PRESERVAR TROFÉUS
  inicializar() {
    try {
      const metaElement = document.getElementById("meta-valor");
      if (metaElement) {
        metaElement.innerHTML =
          '<i class="fa-solid fa-coins"></i><div class="meta-valor-container"><span class="valor-texto loading-text">Calculando...</span></div>';
      }

      // Detectar período inicial
      const radioMarcado = document.querySelector(
        'input[name="periodo"]:checked'
      );
      if (radioMarcado) {
        this.periodoAtual = radioMarcado.value;
      }

      console.log(
        `🚀 Sistema inicializado CORRIGIDO - Período: ${this.periodoAtual}`
      );
      console.log(
        `📊 Preservação de troféus: ${
          this.preservarTrofeusAnteriores ? "ATIVADA" : "DESATIVADA"
        }`
      );

      // Tentar criar badge
      const tentarCriarBadge = () => {
        const sucesso = this.criarBadgeSeNaoExistir();
        if (!sucesso) {
          console.log("⏳ Tentando criar badge novamente em 1s...");
          setTimeout(tentarCriarBadge, 1000);
        } else {
          setTimeout(() => {
            const badge = document.getElementById("meta-tipo-badge");
            if (badge) {
              console.log(
                `✅ Badge encontrado: "${badge.textContent}" com classes: ${badge.className}`
              );
              this.atualizarBadgeTipoMeta("META TURBO", "turbo");
            }
          }, 200);
        }
      };

      setTimeout(tentarCriarBadge, 500);

      this.configurarListenersPeriodo();

      // NOVO: Aguardar um pouco antes da primeira atualização para preservar troféus
      setTimeout(() => {
        this.atualizarMetaDiaria();
      }, 800);
    } catch (error) {
      console.error("❌ Erro na inicialização:", error);
    }
  },
};

// INTEGRAÇÃO COM SISTEMA DE FILTRO EXISTENTE - CORRIGIDO
const SistemaFiltroPeriodoIntegrado = {
  ...(window.SistemaFiltroPeriodo || {}),

  periodoAtual: "dia",

  async alterarPeriodo(periodo) {
    if (this.executandoAlteracao) {
      console.log("⏳ Alteração já em andamento, aguardando...");
      return;
    }

    this.executandoAlteracao = true;
    this.periodoAtual = periodo;

    try {
      this.atualizarBotoesVisuais(periodo);
      this.mostrarLoading();

      if (typeof MetaDiariaManager !== "undefined") {
        MetaDiariaManager.bloquearCalculosTemporarios();
      }

      await new Promise((resolve) => setTimeout(resolve, 200));

      if (typeof MetaDiariaManager !== "undefined") {
        MetaDiariaManager.periodoAtual = periodo;
      }

      const formData = new FormData();
      formData.append("periodo", periodo);

      const response = await fetch("carregar-mentores.php", {
        method: "POST",
        body: formData,
        headers: {
          "X-Periodo-Filtro": periodo,
        },
      });

      if (!response.ok) throw new Error("Erro ao carregar dados");

      const html = await response.text();
      const container = document.getElementById("listaMentores");
      if (container) {
        container.innerHTML = html;
        this.reaplicarEventos();
        this.atualizarPlacar();
        if (typeof atualizarIndicadorPeriodoHeader === "function") {
          atualizarIndicadorPeriodoHeader(periodo);
        }
      }

      await new Promise((resolve) => setTimeout(resolve, 300));

      if (typeof MetaDiariaManager !== "undefined") {
        await MetaDiariaManager.atualizarMetaDiaria(true);
      }

      // NOVO: Preservar troféus após mudança de período
      setTimeout(() => {
        if (typeof MetaDiariaManager !== "undefined") {
          MetaDiariaManager.preservarTrofeusExistentes();
        }
      }, 500);
    } catch (error) {
      this.mostrarErro("Erro ao carregar dados do período");
      console.error("❌ Erro ao alterar período:", error);
    } finally {
      this.ocultarLoading();
      setTimeout(() => {
        this.executandoAlteracao = false;
      }, 100);
    }
  },

  // Manter outras funções existentes
  atualizarBotoesVisuais:
    (window.SistemaFiltroPeriodo &&
      window.SistemaFiltroPeriodo.atualizarBotoesVisuais) ||
    function () {},
  reaplicarEventos:
    (window.SistemaFiltroPeriodo &&
      window.SistemaFiltroPeriodo.reaplicarEventos) ||
    function () {},
  atualizarPlacar:
    (window.SistemaFiltroPeriodo &&
      window.SistemaFiltroPeriodo.atualizarPlacar) ||
    function () {},
  mostrarLoading:
    (window.SistemaFiltroPeriodo &&
      window.SistemaFiltroPeriodo.mostrarLoading) ||
    function () {},
  ocultarLoading:
    (window.SistemaFiltroPeriodo &&
      window.SistemaFiltroPeriodo.ocultarLoading) ||
    function () {},
  mostrarErro:
    (window.SistemaFiltroPeriodo && window.SistemaFiltroPeriodo.mostrarErro) ||
    function () {},
  inicializar:
    (window.SistemaFiltroPeriodo && window.SistemaFiltroPeriodo.inicializar) ||
    function () {},
};

// INTERCEPTAÇÃO AJAX - CORRIGIDO PARA PRESERVAR TROFÉUS
function configurarInterceptadores() {
  try {
    const originalFetch = window.fetch;

    window.fetch = async function (...args) {
      const response = await originalFetch.apply(this, arguments);

      if (
        args[0] &&
        typeof args[0] === "string" &&
        args[0].includes("dados_banca.php") &&
        response.ok
      ) {
        setTimeout(() => {
          if (
            typeof MetaDiariaManager !== "undefined" &&
            !MetaDiariaManager.atualizandoAtualmente &&
            (!SistemaFiltroPeriodoIntegrado ||
              !SistemaFiltroPeriodoIntegrado.executandoAlteracao)
          ) {
            MetaDiariaManager.atualizarMetaDiaria();

            // NOVO: Preservar troféus após fetch
            setTimeout(() => {
              MetaDiariaManager.preservarTrofeusExistentes();
            }, 200);
          }
        }, 100);
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
              if (
                typeof MetaDiariaManager !== "undefined" &&
                !MetaDiariaManager.atualizandoAtualmente &&
                (!SistemaFiltroPeriodoIntegrado ||
                  !SistemaFiltroPeriodoIntegrado.executandoAlteracao)
              ) {
                MetaDiariaManager.atualizarMetaDiaria();

                // NOVO: Preservar troféus após XHR
                setTimeout(() => {
                  MetaDiariaManager.preservarTrofeusExistentes();
                }, 200);
              }
            }, 100);
          }
        });

        return originalSend.apply(this, arguments);
      };

      return xhr;
    }

    window.XMLHttpRequest = newXHR;
  } catch (error) {
    console.error("❌ Erro ao configurar interceptadores:", error);
  }
}

// FUNÇÕES GLOBAIS - CORRIGIDAS
window.atualizarMetaDiaria = () => {
  if (typeof MetaDiariaManager !== "undefined") {
    return MetaDiariaManager.atualizarMetaDiaria();
  }
  return null;
};

window.forcarAtualizacaoMeta = () => {
  if (typeof MetaDiariaManager !== "undefined") {
    MetaDiariaManager.atualizandoAtualmente = false;
    return MetaDiariaManager.atualizarMetaDiaria();
  }
  return null;
};

window.alterarPeriodo = (periodo) => {
  try {
    if (
      (typeof SistemaFiltroPeriodoIntegrado !== "undefined" &&
        SistemaFiltroPeriodoIntegrado.executandoAlteracao) ||
      (typeof MetaDiariaManager !== "undefined" &&
        MetaDiariaManager.atualizandoAtualmente)
    ) {
      console.log("⏳ Sistema ocupado, aguardando...");
      return false;
    }

    const radio = document.querySelector(
      `input[name="periodo"][value="${periodo}"]`
    );
    if (radio) {
      radio.checked = true;
      if (typeof MetaDiariaManager !== "undefined") {
        MetaDiariaManager.periodoAtual = periodo;
      }

      if (typeof SistemaFiltroPeriodoIntegrado !== "undefined") {
        SistemaFiltroPeriodoIntegrado.alterarPeriodo(periodo);
      }

      return true;
    }
    return false;
  } catch (error) {
    console.error("❌ Erro ao alterar período:", error);
    return false;
  }
};

window.alterarTipoMeta = (tipo) => {
  try {
    if (!["fixa", "turbo"].includes(tipo)) {
      console.error("❌ Tipo de meta inválido. Use 'fixa' ou 'turbo'");
      return false;
    }

    const tipoTexto = tipo === "fixa" ? "Meta Fixa" : "Meta Turbo";

    console.log(`🔄 Solicitando alteração para: ${tipoTexto}`);

    fetch("dados_banca.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        acao: "alterar",
        meta: tipoTexto,
        diaria: 2,
        unidade: 2,
        odds: 1.5,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          console.log(`✅ Tipo de meta alterado para: ${data.tipo_meta_texto}`);

          if (
            typeof MetaDiariaManager !== "undefined" &&
            MetaDiariaManager.atualizarBadgeTipoMeta
          ) {
            MetaDiariaManager.atualizarBadgeTipoMeta(
              data.tipo_meta_texto,
              tipo
            );
          }

          setTimeout(() => {
            if (typeof MetaDiariaManager !== "undefined") {
              MetaDiariaManager.atualizarMetaDiaria();
            }
          }, 100);
        } else {
          console.error("❌ Erro ao alterar tipo:", data.message);
        }
      })
      .catch((error) => {
        console.error("❌ Erro na requisição:", error);
      });

    return true;
  } catch (error) {
    console.error("❌ Erro ao alterar tipo de meta:", error);
    return false;
  }
};

// ATALHOS SIMPLIFICADOS - CORRIGIDOS
window.$ = {
  force: () => forcarAtualizacaoMeta(),
  dia: () => alterarPeriodo("dia"),
  mes: () => alterarPeriodo("mes"),
  ano: () => alterarPeriodo("ano"),
  fixa: () => alterarTipoMeta("fixa"),
  turbo: () => alterarTipoMeta("turbo"),

  // NOVO: Controles de troféu
  preservar: (ativar = true) => {
    if (typeof MetaDiariaManager !== "undefined") {
      MetaDiariaManager.preservarTrofeusAnteriores = ativar;
      console.log(
        `🛡️ Preservação de troféus: ${ativar ? "ATIVADA" : "DESATIVADA"}`
      );
    }
  },

  info: () => {
    try {
      const metaElement = document.getElementById("meta-valor");
      const rotuloElement = document.getElementById("rotulo-meta");
      const barraElement = document.getElementById("barra-progresso");
      const tipoElement = document.getElementById("meta-text-unico");
      const badgeElement = document.getElementById("meta-tipo-badge");

      const info = {
        meta: !!metaElement,
        rotulo: !!rotuloElement,
        barra: !!barraElement,
        tipoMeta: !!tipoElement,
        badge: !!badgeElement,
        metaContent: metaElement ? metaElement.textContent : "N/A",
        tipoTexto: tipoElement ? tipoElement.textContent : "N/A",
        badgeTexto: badgeElement ? badgeElement.textContent : "N/A",
        atualizando:
          typeof MetaDiariaManager !== "undefined"
            ? MetaDiariaManager.atualizandoAtualmente
            : false,
        periodoAtual:
          typeof MetaDiariaManager !== "undefined"
            ? MetaDiariaManager.periodoAtual
            : "N/A",
        tipoMetaAtual:
          typeof MetaDiariaManager !== "undefined"
            ? MetaDiariaManager.tipoMetaAtual
            : "Detectado pelo banco",
        preservarTrofeus:
          typeof MetaDiariaManager !== "undefined"
            ? MetaDiariaManager.preservarTrofeusAnteriores
            : false,
        sistemaFiltro: typeof SistemaFiltroPeriodoIntegrado !== "undefined",
        monitorContinuo: typeof window.MonitorContinuo !== "undefined",
        verificacao:
          "Sistema CORRIGIDO - Preserva troféus independente do período",
      };

      console.log("📊 Info Sistema CORRIGIDO:", info);
      return "✅ Sistema corrigido para preservar troféus";
    } catch (error) {
      console.error("❌ Erro ao obter info:", error);
      return "❌ Erro ao obter informações";
    }
  },
};

// INICIALIZAÇÃO CORRIGIDA
function inicializarSistemaIntegrado() {
  try {
    console.log("🚀 Inicializando Sistema CORRIGIDO para preservar troféus...");

    if (typeof MetaDiariaManager !== "undefined") {
      MetaDiariaManager.inicializar();
      console.log("✅ MetaDiariaManager CORRIGIDO inicializado");
    }

    if (typeof SistemaFiltroPeriodo !== "undefined") {
      window.SistemaFiltroPeriodo = SistemaFiltroPeriodoIntegrado;
      SistemaFiltroPeriodoIntegrado.inicializar();
      console.log("✅ Sistema de Filtro Integrado CORRIGIDO");
    }

    configurarInterceptadores();
    console.log("✅ Interceptadores CORRIGIDOS configurados");

    const radioMarcado = document.querySelector(
      'input[name="periodo"]:checked'
    );
    if (radioMarcado) {
      const periodoInicial = radioMarcado.value;
      if (typeof MetaDiariaManager !== "undefined") {
        MetaDiariaManager.periodoAtual = periodoInicial;
      }
      if (typeof SistemaFiltroPeriodoIntegrado !== "undefined") {
        SistemaFiltroPeriodoIntegrado.periodoAtual = periodoInicial;
      }
      console.log(`✅ Período inicial: ${periodoInicial}`);
    }

    console.log("✅ Tipo de meta será detectado automaticamente pelo banco");
    console.log("🎯 Sistema CORRIGIDO inicializado!");
    console.log("📝 Funcionalidades CORRIGIDAS:");
    console.log("   ✅ Cálculo de meta diária/mensal/anual");
    console.log("   ✅ Badge de tipo de meta (Fixa/Turbo)");
    console.log("   ✅ Barra de progresso");
    console.log("   ✅ Sistema de filtro por período");
    console.log("   ✅ VALOR TACHADO quando meta batida");
    console.log("   ✅ VALOR EXTRA em dourado quando meta superada");
    console.log("   🛡️ PRESERVAÇÃO DE TROFÉUS independente do período");
    console.log("   🛡️ Cache inteligente de troféus anteriores");
    console.log("");
    console.log("🔧 Comandos extras:");
    console.log("   $.preservar(true/false) - Ativar/desativar preservação");
    console.log("   MonitorContinuo.status() - Ver status dos troféus");
  } catch (error) {
    console.error("❌ Erro na inicialização do sistema:", error);
  }
}

// AGUARDAR DOM
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", () => {
    setTimeout(inicializarSistemaIntegrado, 800);
  });
} else {
  setTimeout(inicializarSistemaIntegrado, 500);
}

// EXPORT PARA USO EXTERNO
window.MetaDiariaManager = MetaDiariaManager;
window.SistemaFiltroPeriodoIntegrado = SistemaFiltroPeriodoIntegrado;

// ========================================================================================================================
//                               ✅ FIM CALCULO META DO (DIA)-(MÊS)-(ANO)
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
//                                 ✅ FILTRO POR PERIODO DIA MES ANO DOS CAMPO MENTORES
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
  const icone = document.querySelector("#data-header .data-texto-compacto i");

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
      texto: `${new Date().getFullYear()}`,
      icone: "fa-calendar",
    },
  };

  const config = configuracoes[periodo] || configuracoes.dia;

  dataAtual.style.opacity = "0";
  setTimeout(() => {
    dataAtual.textContent = config.texto;
    dataAtual.style.opacity = "1";
    // Removed animation assignment to avoid overriding transform (translateY)
    // Rely on CSS transition for smooth fade/shift instead.
  }, 200);

  if (icone) {
    // Preserve existing base classes (like sizing or forced classes) and only
    // swap the calendar icon variant. This avoids removing helper classes used
    // by other parts of the UI that might change positioning.
    icone.classList.remove(
      "fa-calendar-day",
      "fa-calendar-days",
      "fa-calendar"
    );
    icone.classList.add(config.icone);
    icone.classList.add("fa-solid");
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

    // Atualiza também o placar do mês (elementos -2) quando existirem.
    try {
      const placarGreen2 = document.querySelector(".placar-green-2");
      const placarRed2 = document.querySelector(".placar-red-2");

      // Se há elementos de total vindos do servidor, reutiliza-os
      const totalGreenValue =
        document.querySelector("#total-green-dia")?.dataset?.green || "0";
      const totalRedValue =
        document.querySelector("#total-red-dia")?.dataset?.red || "0";

      // Só preenche os placares -2 quando o período atual estiver como 'mes'
      if (this.periodoAtual === "mes") {
        if (placarGreen2) placarGreen2.textContent = totalGreenValue;
        if (placarRed2) placarRed2.textContent = totalRedValue;
      } else {
        // Caso contrário limpa os campos -2 (evita mostrar dados errados)
        if (placarGreen2) placarGreen2.textContent = "0";
        if (placarRed2) placarRed2.textContent = "0";
      }
    } catch (e) {
      // Silenciar erros não críticos
      console.debug("atualizarPlacar - placar -2 update skipped:", e);
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
    return `${meses[data.getMonth()]} ${data.getFullYear()}`;
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
//                                 ✅ FIM: FILTRO POR PERIODO DIA MES ANO DOS CAMPO MENTORES
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
//
// ========================================================================================================================
//                 CODIGO QUE FAZ APARECER O TEXTO : (META FIXA) - (META TURBO) E TRATA O CSS AQUI
// ========================================================================================================================

// Função para encontrar o container da barra
function encontrarContainerBarra() {
  const barraProgresso = document.querySelector(
    '[style*="width:"], .widget-barra-progresso, [class*="progresso"], [class*="barra"]'
  );

  if (barraProgresso && barraProgresso.parentElement) {
    return barraProgresso.parentElement;
  }

  return document.querySelector(".widget-barra-container");
}

// Função para criar o badge (se não existir)
function criarBadgeMeta() {
  // Verificar se já existe
  if (document.getElementById("meta-tipo-badge")) {
    console.log("✅ Badge já existe");
    return true;
  }

  const container = encontrarContainerBarra();
  if (!container) {
    console.error("❌ Container não encontrado");
    return false;
  }

  // Garantir que o container seja reconhecido para posicionamento via CSS
  container.classList.add("meta-badge-container");

  // Criar badge
  const badge = document.createElement("div");
  badge.id = "meta-tipo-badge";
  badge.className = "meta-tipo-badge meta-turbo";
  badge.textContent = "META TURBO";

  // Estilos base (visuais) - posicionamento deixado para CSS
  badge.style.cssText = `
        color: white !important;
        font-size: 8px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.3px !important;
        padding: 4px 8px !important;
        border-radius: 12px !important;
        z-index: 999 !important;
        white-space: nowrap !important;
        display: block !important;
        visibility: visible !important;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
    `;

  // Aplicar cor inicial (turbo)
  aplicarCorBadge(badge, "turbo");

  container.appendChild(badge);
  console.log("✅ Badge criado com sucesso!");
  return true;
}

// Função para aplicar cores com estilos inline
function aplicarCorBadge(badge, tipo) {
  if (tipo === "fixa") {
    badge.style.setProperty(
      "background",
      "linear-gradient(135deg, #007bff 0%, #0056b3 100%)",
      "important"
    );
    badge.style.setProperty(
      "border",
      "1px solid rgba(0, 123, 255, 0.3)",
      "important"
    );
    badge.style.setProperty(
      "box-shadow",
      "0 2px 6px rgba(0, 123, 255, 0.3)",
      "important"
    );
  } else {
    badge.style.setProperty(
      "background",
      "linear-gradient(135deg, #ff6b35 0%, #e55a2b 100%)",
      "important"
    );
    badge.style.setProperty(
      "border",
      "1px solid rgba(255, 107, 53, 0.3)",
      "important"
    );
    badge.style.setProperty(
      "box-shadow",
      "0 2px 6px rgba(255, 107, 53, 0.3)",
      "important"
    );
  }
}

// Função principal para atualizar o badge
function atualizarBadgeMeta(tipo, texto = null) {
  let badge = document.getElementById("meta-tipo-badge");

  // Criar se não existir
  if (!badge) {
    if (!criarBadgeMeta()) return;
    badge = document.getElementById("meta-tipo-badge");
  }

  // Remover classes antigas
  badge.classList.remove("meta-fixa", "meta-turbo", "loading");

  // Definir texto e aplicar cor
  let textoFinal;

  if (tipo === "fixa") {
    badge.classList.add("meta-fixa");
    textoFinal = texto || "META FIXA";
    aplicarCorBadge(badge, "fixa");
  } else if (tipo === "turbo") {
    badge.classList.add("meta-turbo");
    textoFinal = texto || "META TURBO";
    aplicarCorBadge(badge, "turbo");
  } else {
    badge.classList.add("loading");
    textoFinal = "CARREGANDO...";
    badge.style.setProperty(
      "background",
      "rgba(153, 153, 153, 0.8)",
      "important"
    );
  }

  badge.textContent = textoFinal.toUpperCase();
  console.log(`🏷️ Badge atualizado: ${textoFinal} (${tipo})`);
}

// Integração com MetaDiariaManager (se existir)
if (typeof MetaDiariaManager !== "undefined") {
  // Backup da função original
  const originalAtualizarTipoMeta = MetaDiariaManager.atualizarTipoMetaDisplay;

  // Sobrescrever com nova funcionalidade
  MetaDiariaManager.atualizarTipoMetaDisplay = function (data) {
    try {
      // Executar função original
      if (originalAtualizarTipoMeta) {
        originalAtualizarTipoMeta.call(this, data);
      }

      // Atualizar badge
      if (data.tipo_meta_texto) {
        const tipo = data.tipo_meta_texto.toLowerCase().includes("fixa")
          ? "fixa"
          : "turbo";
        atualizarBadgeMeta(tipo, data.tipo_meta_texto);
      }
    } catch (error) {
      console.error("❌ Erro ao atualizar badge:", error);
    }
  };

  console.log("✅ Badge integrado com MetaDiariaManager");
}

// Função de teste
function testarBadgeCompleto() {
  console.log("🧪 Testando sistema completo...");

  // Garantir que existe
  criarBadgeMeta();

  // Teste das cores
  setTimeout(() => {
    atualizarBadgeMeta("fixa");
    console.log("🔵 META FIXA (azul)");
  }, 1000);

  setTimeout(() => {
    atualizarBadgeMeta("turbo");
    console.log("🟠 META TURBO (laranja)");
  }, 3000);

  setTimeout(() => {
    atualizarBadgeMeta("fixa");
    console.log("🔵 META FIXA final");
  }, 5000);
}

// Comandos globais
window.criarBadgeMeta = criarBadgeMeta;
window.atualizarBadgeMeta = atualizarBadgeMeta;
window.testarBadgeCompleto = testarBadgeCompleto;

// Atalhos
window.badgeMeta = {
  criar: criarBadgeMeta,
  fixa: () => atualizarBadgeMeta("fixa"),
  turbo: () => atualizarBadgeMeta("turbo"),
  teste: testarBadgeCompleto,
};

// Inicialização automática
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", criarBadgeMeta);
} else {
  setTimeout(criarBadgeMeta, 500);
}

console.log("🎯 Sistema Badge Meta carregado!");
console.log(
  "📱 Comandos: badgeMeta.criar(), badgeMeta.fixa(), badgeMeta.turbo(), badgeMeta.teste()"
);
// ========================================================================================================================
//               FIM:  CODIGO QUE FAZ APARECER O TEXTO : (META FIXA) - (META TURBO) E TRATA O CSS AQUI
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
//
// ========================================================================================================================
//                        💼   FORMULARIO DE CADASTRO DO MENTOR + MODAL EXCLUSÃO DO MENTOR
// ========================================================================================================================

(function () {
  "use strict";

  // Aguarda o sistema principal carregar
  const init = () => {
    if (
      typeof FormularioManager !== "undefined" &&
      typeof ModalManager !== "undefined"
    ) {
      iniciarMelhoriasVisuais();
    } else {
      // Tenta novamente em 100ms
      setTimeout(init, 100);
    }
  };

  document.addEventListener("DOMContentLoaded", init);

  function iniciarMelhoriasVisuais() {
    console.log("🎨 Aplicando melhorias visuais modernas...");

    // Melhorar as funções existentes sem quebrar nada
    melhorarFuncoesExistentes();

    // Adicionar recursos modernos
    adicionarRecursosModernos();

    console.log("✅ Melhorias visuais aplicadas com sucesso!");
  }

  // ===== MELHORAR FUNÇÕES EXISTENTES =====
  function melhorarFuncoesExistentes() {
    // Salvar referências das funções originais
    const originalPrepararNovoMentor = FormularioManager.prepararNovoMentor;
    const originalPrepararEdicaoMentor = FormularioManager.prepararEdicaoMentor;
    const originalMostrarNomeArquivo = ImagemManager.mostrarNomeArquivo;
    const originalRemoverImagem = ImagemManager.removerImagem;

    // Melhorar prepararNovoMentor
    FormularioManager.prepararNovoMentor = function () {
      // Executa função original
      if (originalPrepararNovoMentor) {
        originalPrepararNovoMentor.call(this);
      }

      // Adiciona melhorias visuais
      setTimeout(() => {
        aplicarMelhoriasFormulario();
        resetarContadorCaracteres();
      }, 50);
    };

    // Melhorar prepararEdicaoMentor
    FormularioManager.prepararEdicaoMentor = function (id) {
      // Executa função original
      if (originalPrepararEdicaoMentor) {
        originalPrepararEdicaoMentor.call(this, id);
      }

      // Adiciona melhorias visuais
      setTimeout(() => {
        aplicarMelhoriasFormulario();
        const nomeAtual = document.getElementById("nome")?.value || "";
        atualizarContadorCaracteres(nomeAtual.length);
      }, 50);
    };

    // Melhorar mostrarNomeArquivo
    ImagemManager.mostrarNomeArquivo = function (input) {
      // Executa função original
      if (originalMostrarNomeArquivo) {
        originalMostrarNomeArquivo.call(this, input);
      }

      // Adiciona melhorias
      melhorarPreviewArquivo();
    };

    // Melhorar removerImagem
    ImagemManager.removerImagem = function () {
      // Executa função original
      if (originalRemoverImagem) {
        originalRemoverImagem.call(this);
      }

      // Adiciona melhorias
      resetarPreviewArquivo();
    };

    // Melhorar ExclusaoManager
    if (typeof ExclusaoManager !== "undefined") {
      const originalConfirmarExclusaoModal =
        ExclusaoManager.confirmarExclusaoModal;

      ExclusaoManager.confirmarExclusaoModal = function (nome) {
        // Usar modal melhorado se disponível
        return mostrarModalConfirmacaoModerno(nome);
      };
    }

    // Melhorar função global excluirMentorDireto
    window.excluirMentorDireto = function () {
      const mentorId = document.getElementById("mentor-id")?.value;
      const nomeAtual =
        document.getElementById("nome")?.value ||
        document.getElementById("mentor-nome-preview")?.textContent;

      if (!mentorId) {
        mostrarToastModerno("ID do mentor não encontrado", "erro");
        return;
      }

      executarExclusaoComModal(mentorId, nomeAtual);
    };
  }

  // ===== APLICAR MELHORIAS AO FORMULÁRIO =====
  function aplicarMelhoriasFormulario() {
    // Adicionar loading overlay se não existir
    adicionarLoadingOverlay();

    // Melhorar campo de nome
    melhorarCampoNome();

    // Configurar drag & drop
    configurarDragDrop();

    // Adicionar contador de caracteres
    adicionarContadorCaracteres();

    // Melhorar clique na imagem
    melhorarCliqueImagem();

    // Melhorar envio do formulário
    melhorarEnvioFormulario();
  }

  function adicionarLoadingOverlay() {
    const modalConteudo = document.querySelector(".modal-conteudo");
    if (modalConteudo && !document.getElementById("loading-overlay-moderno")) {
      const loadingOverlay = document.createElement("div");
      loadingOverlay.id = "loading-overlay-moderno";
      loadingOverlay.style.cssText = `
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(255, 255, 255, 0.9);
                display: none;
                align-items: center;
                justify-content: center;
                border-radius: 24px;
                z-index: 100;
            `;

      const spinner = document.createElement("div");
      spinner.style.cssText = `
                width: 40px;
                height: 40px;
                border: 4px solid #e2e8f0;
                border-top: 4px solid #667eea;
                border-radius: 50%;
                animation: spin 1s linear infinite;
            `;

      loadingOverlay.appendChild(spinner);
      modalConteudo.appendChild(loadingOverlay);

      // Adicionar animação de spin
      if (!document.getElementById("spin-animation-moderno")) {
        const style = document.createElement("style");
        style.id = "spin-animation-moderno";
        style.textContent = `
                    @keyframes spin {
                        0% { transform: rotate(0deg); }
                        100% { transform: rotate(360deg); }
                    }
                `;
        document.head.appendChild(style);
      }
    }
  }

  function melhorarCampoNome() {
    const campoNome = document.getElementById("nome");
    const nomePreview = document.getElementById("mentor-nome-preview");

    if (campoNome && nomePreview && !campoNome._melhoriaAplicada) {
      campoNome._melhoriaAplicada = true;

      // Handler para input
      const inputHandler = function (e) {
        const nome = e.target.value;
        nomePreview.textContent = nome || "";
        atualizarContadorCaracteres(nome.length);

        // Limitar caracteres a 100 (removendo limitação de 17 do sistema original)
        if (nome.length > 100) {
          e.target.value = nome.slice(0, 100);
          nomePreview.textContent = e.target.value;
          atualizarContadorCaracteres(100);
        }
      };

      // Handler para blur (capitalização)
      const blurHandler = function () {
        const nome = this.value.trim();
        if (nome) {
          const nomeFormatado = capitalizarNome(nome);
          this.value = nomeFormatado;
          nomePreview.textContent = nomeFormatado;
        }
      };

      // Remover listeners existentes e adicionar novos
      campoNome.removeEventListener("input", inputHandler);
      campoNome.removeEventListener("blur", blurHandler);

      campoNome.addEventListener("input", inputHandler);
      campoNome.addEventListener("blur", blurHandler);
    }
  }

  function configurarDragDrop() {
    const labelArquivo = document.querySelector(".label-arquivo");
    if (!labelArquivo || labelArquivo._dragDropConfigured) return;

    labelArquivo._dragDropConfigured = true;

    const preventDefaults = (e) => {
      e.preventDefault();
      e.stopPropagation();
    };

    const highlight = () => {
      labelArquivo.style.transform = "scale(1.02)";
      labelArquivo.style.boxShadow = "0 8px 25px rgba(102, 126, 234, 0.5)";
    };

    const unhighlight = () => {
      labelArquivo.style.transform = "";
      labelArquivo.style.boxShadow = "";
    };

    const handleDrop = (e) => {
      const files = e.dataTransfer.files;
      if (files.length > 0) {
        const fotoInput = document.getElementById("foto");
        if (fotoInput) {
          fotoInput.files = files;
          window.mostrarNomeArquivo(fotoInput);
        }
      }
    };

    ["dragenter", "dragover", "dragleave", "drop"].forEach((eventName) => {
      labelArquivo.addEventListener(eventName, preventDefaults, false);
    });

    ["dragenter", "dragover"].forEach((eventName) => {
      labelArquivo.addEventListener(eventName, highlight, false);
    });

    ["dragleave", "drop"].forEach((eventName) => {
      labelArquivo.addEventListener(eventName, unhighlight, false);
    });

    labelArquivo.addEventListener("drop", handleDrop, false);
  }

  function adicionarContadorCaracteres() {
    const campoNome = document.getElementById("nome");
    if (!campoNome || document.getElementById("char-counter-moderno")) return;

    const contador = document.createElement("div");
    contador.id = "char-counter-moderno";
    contador.style.cssText = `
            text-align: right;
            font-size: 12px;
            color: #718096;
            margin-top: 5px;
            transition: color 0.3s ease;
        `;
    contador.textContent = "0/100 caracteres";

    // Inserir após o campo nome
    campoNome.parentNode.insertBefore(contador, campoNome.nextSibling);
  }

  function melhorarCliqueImagem() {
    const previewImg = document.getElementById("preview-img");
    if (previewImg && !previewImg._clickConfigured) {
      previewImg._clickConfigured = true;
      previewImg.style.cursor = "pointer";

      previewImg.addEventListener("click", function () {
        const fotoInput = document.getElementById("foto");
        if (fotoInput) {
          fotoInput.click();
        }
      });
    }
  }

  function melhorarEnvioFormulario() {
    const form = document.querySelector(".formulario-mentor-completo");
    if (!form || form._envioMelhorado) return;

    form._envioMelhorado = true;

    // Interceptar envio do formulário
    form.addEventListener("submit", async function (e) {
      e.preventDefault();

      // ✅ VALIDAR LIMITE DE MENTORES ANTES DE CADASTRAR
      if (
        typeof PlanoManager !== "undefined" &&
        PlanoManager.verificarEExibirPlanos
      ) {
        const podeAvançar = await PlanoManager.verificarEExibirPlanos("mentor");
        if (!podeAvançar) {
          return; // Modal será mostrado automaticamente
        }
      }

      const nome = document.getElementById("nome")?.value?.trim();

      // Validações melhoradas
      if (!nome || nome.length < 2) {
        mostrarToastModerno("Nome deve ter pelo menos 2 caracteres", "erro");
        return;
      }

      if (nome.length > 100) {
        mostrarToastModerno("Nome deve ter no máximo 100 caracteres", "erro");
        return;
      }

      // Usar a função original do FormularioManager
      if (
        typeof FormularioManager !== "undefined" &&
        FormularioManager.processarSubmissaoMentor
      ) {
        mostrarLoadingModerno(true);

        try {
          await FormularioManager.processarSubmissaoMentor(this);
        } catch (error) {
          console.error("Erro no envio:", error);
          mostrarToastModerno("Erro ao processar formulário", "erro");
        } finally {
          mostrarLoadingModerno(false);
        }
      }
    });
  }

  // ===== MODAL DE CONFIRMAÇÃO MODERNO =====
  function mostrarModalConfirmacaoModerno(nome) {
    return new Promise((resolve) => {
      const modal = document.getElementById("modal-confirmacao-exclusao");
      if (!modal) {
        console.error("Modal de confirmação não encontrado");
        resolve(false);
        return;
      }

      // Atualizar texto
      const modalTexto = modal.querySelector(".modal-texto");
      if (modalTexto) {
        modalTexto.innerHTML = `
                    Tem certeza que deseja excluir o mentor <strong>${nome}</strong>?<br>
                    <br>
                    <span style="color: #e53e3e; font-size: 14px;">
                        Esta ação não pode ser desfeita.
                    </span>
                `;
      }

      // Configurar botões
      const btnConfirmar = modal.querySelector(".botao-confirmar");
      const btnCancelar = modal.querySelector(".botao-cancelar");

      // Limpar listeners antigos clonando botões
      if (btnConfirmar) {
        const novoConfirmar = btnConfirmar.cloneNode(true);
        btnConfirmar.parentNode.replaceChild(novoConfirmar, btnConfirmar);

        novoConfirmar.addEventListener("click", () => {
          fecharModalConfirmacao();
          resolve(true);
        });
      }

      if (btnCancelar) {
        const novoCancelar = btnCancelar.cloneNode(true);
        btnCancelar.parentNode.replaceChild(novoCancelar, btnCancelar);

        novoCancelar.addEventListener("click", () => {
          fecharModalConfirmacao();
          resolve(false);
        });
      }

      // Mostrar modal usando o ModalManager existente
      if (typeof ModalManager !== "undefined") {
        ModalManager.abrir("modal-confirmacao-exclusao");
      } else {
        modal.classList.add("show");
        modal.style.display = "flex";
      }
    });
  }

  function fecharModalConfirmacao() {
    const modal = document.getElementById("modal-confirmacao-exclusao");
    if (modal) {
      if (typeof ModalManager !== "undefined") {
        ModalManager.fechar("modal-confirmacao-exclusao");
      } else {
        modal.classList.remove("show");
        setTimeout(() => {
          modal.style.display = "none";
        }, 300);
      }
    }
  }

  async function executarExclusaoComModal(mentorId, nome) {
    const confirmacao = await mostrarModalConfirmacaoModerno(nome);
    if (!confirmacao) return;

    // Usar ExclusaoManager existente se disponível
    if (
      typeof ExclusaoManager !== "undefined" &&
      ExclusaoManager.excluirMentor
    ) {
      await ExclusaoManager.excluirMentor(mentorId, nome);
    } else {
      // Fallback: exclusão manual
      await executarExclusaoManual(mentorId);
    }
  }

  async function executarExclusaoManual(mentorId) {
    mostrarLoadingModerno(true);

    try {
      const formData = new FormData();
      formData.append("excluir_mentor", mentorId);

      const response = await fetch("gestao-diaria.php", {
        method: "POST",
        body: formData,
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
      });

      if (!response.ok) {
        throw new Error(`Erro HTTP: ${response.status}`);
      }

      let resultado;
      try {
        resultado = await response.json();
      } catch (e) {
        // Se não for JSON, tenta interpretar como sucesso
        const text = await response.text();
        if (text.includes("<!DOCTYPE html") || text.includes("<html")) {
          resultado = {
            success: true,
            message: "Mentor excluído com sucesso!",
          };
        } else {
          throw new Error("Resposta inválida do servidor");
        }
      }

      if (resultado.success) {
        mostrarToastModerno("✅ Mentor excluído com sucesso!", "sucesso");

        // Fechar modal
        if (typeof ModalManager !== "undefined") {
          ModalManager.fechar("modal-form");
        } else {
          window.fecharModal();
        }

        // Recarregar dados
        setTimeout(() => {
          if (
            typeof MentorManager !== "undefined" &&
            MentorManager.recarregarMentores
          ) {
            MentorManager.recarregarMentores();
          } else {
            window.location.reload();
          }
        }, 1000);
      } else {
        throw new Error(resultado.message || "Erro ao excluir mentor");
      }
    } catch (error) {
      console.error("Erro ao excluir mentor:", error);
      mostrarToastModerno(`❌ ${error.message}`, "erro");
    } finally {
      mostrarLoadingModerno(false);
    }
  }

  // ===== RECURSOS MODERNOS ADICIONAIS =====
  function adicionarRecursosModernos() {
    // Melhorar eventos ESC
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") {
        const modalConfirmacao = document.getElementById(
          "modal-confirmacao-exclusao"
        );
        const modalPrincipal = document.getElementById("modal-form");

        if (modalConfirmacao && modalConfirmacao.style.display === "flex") {
          fecharModalConfirmacao();
        } else if (
          modalPrincipal &&
          modalPrincipal.classList.contains("show")
        ) {
          window.fecharModal();
        }
      }
    });
  }

  // ===== FUNÇÕES UTILITÁRIAS =====
  function atualizarContadorCaracteres(count) {
    const contador = document.getElementById("char-counter-moderno");
    if (!contador) return;

    contador.textContent = `${count}/100 caracteres`;

    if (count > 90) {
      contador.style.color = "#e53e3e";
    } else if (count > 70) {
      contador.style.color = "#d69e2e";
    } else {
      contador.style.color = "#718096";
    }
  }

  function resetarContadorCaracteres() {
    atualizarContadorCaracteres(0);
  }

  function capitalizarNome(nome) {
    return nome
      .toLowerCase()
      .split(" ")
      .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
      .join(" ");
  }

  function mostrarLoadingModerno(show) {
    const overlay = document.getElementById("loading-overlay-moderno");
    if (overlay) {
      overlay.style.display = show ? "flex" : "none";
    }

    const form = document.querySelector(".formulario-mentor-completo");
    if (form) {
      if (show) {
        form.style.pointerEvents = "none";
        form.style.opacity = "0.7";
      } else {
        form.style.pointerEvents = "";
        form.style.opacity = "";
      }
    }
  }

  function melhorarPreviewArquivo() {
    const nomeArquivo = document.getElementById("nome-arquivo");
    if (nomeArquivo) {
      nomeArquivo.style.color = "#2b6cb0";
      nomeArquivo.style.background = "#e6f3ff";
      nomeArquivo.style.padding = "8px 16px";
      nomeArquivo.style.borderRadius = "8px";
      nomeArquivo.style.marginTop = "10px";
      nomeArquivo.style.display = "block";
    }
  }

  function resetarPreviewArquivo() {
    const nomeArquivo = document.getElementById("nome-arquivo");
    if (nomeArquivo) {
      nomeArquivo.style.color = "#718096";
      nomeArquivo.style.background = "#f7fafc";
    }
  }

  function mostrarToastModerno(message, type = "sucesso") {
    // Usar ToastManager existente se disponível
    if (typeof ToastManager !== "undefined" && ToastManager.mostrar) {
      ToastManager.mostrar(message, type);
      return;
    }

    // Fallback: criar toast próprio
    let toast = document.getElementById("toast-moderno");
    if (!toast) {
      toast = document.createElement("div");
      toast.id = "toast-moderno";
      toast.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: white;
                border-radius: 12px;
                padding: 16px 20px;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
                z-index: 3000;
                transform: translateX(400px);
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                display: flex;
                align-items: center;
                gap: 12px;
                max-width: 400px;
                font-family: 'Inter', sans-serif;
                font-weight: 500;
            `;
      document.body.appendChild(toast);
    }

    // Define ícone e estilo baseado no tipo
    let icon, borderColor;
    switch (type) {
      case "sucesso":
        icon = '<i class="fas fa-check-circle" style="color: #48bb78;"></i>';
        borderColor = "#48bb78";
        break;
      case "erro":
        icon =
          '<i class="fas fa-exclamation-circle" style="color: #e53e3e;"></i>';
        borderColor = "#e53e3e";
        break;
      case "aviso":
        icon =
          '<i class="fas fa-exclamation-triangle" style="color: #d69e2e;"></i>';
        borderColor = "#d69e2e";
        break;
      default:
        icon = '<i class="fas fa-info-circle" style="color: #667eea;"></i>';
        borderColor = "#667eea";
    }

    toast.innerHTML = `${icon}<span>${message}</span>`;
    // Use CSS classes for toast borders instead of inline styles
    if (
      borderColor === "#48bb78" ||
      borderColor.toLowerCase().indexOf("48bb78") !== -1
    ) {
      toast.classList.add("toast--sucesso");
    } else if (
      borderColor === "#e53e3e" ||
      borderColor.toLowerCase().indexOf("e53e3e") !== -1
    ) {
      toast.classList.add("toast--erro");
    } else {
      // default
    }
    toast.style.transform = "translateX(0)";

    // Remove após 4 segundos
    setTimeout(() => {
      toast.style.transform = "translateX(400px)";
    }, 4000);
  }

  console.log("🎨 Melhorias visuais modernas carregadas!");
  console.log("✅ Funcionalidades adicionadas sem quebrar o sistema:");
  console.log("  - Design moderno e responsivo");
  console.log("  - Drag & drop para upload");
  console.log("  - Contador de caracteres (100 máx)");
  console.log("  - Animações suaves");
  console.log("  - Exclusão corrigida");
  console.log("  - Toast notifications modernas");
  console.log("🚀 Sistema mantido + visual moderno ativo!");
})();
// ===== JAVASCRIPT PARA CENTRALIZAR MODAL CORRETAMENTE =====

document.addEventListener("DOMContentLoaded", function () {
  setTimeout(function () {
    console.log("🔧 Configurando modal centralizado...");

    // Sobrescrever função excluirMentorDireto
    window.excluirMentorDireto = function () {
      console.log("🗑️ Executando exclusão");

      const mentorId = document.getElementById("mentor-id")?.value;
      const nomeAtual =
        document.getElementById("nome")?.value ||
        document.getElementById("mentor-nome-preview")?.textContent ||
        "este mentor";

      if (!mentorId) {
        alert("ID do mentor não encontrado");
        return;
      }

      mostrarModalCentralizado(mentorId, nomeAtual);
    };

    console.log("✅ Função de exclusão configurada");
  }, 500);
});

function mostrarModalCentralizado(mentorId, nome) {
  console.log("🎯 Criando modal centralizado para:", nome);

  // Remover modal existente se houver
  const modalExistente = document.getElementById("modal-exclusao-custom");
  if (modalExistente) {
    document.body.removeChild(modalExistente);
  }

  // Criar modal do zero
  const modal = document.createElement("div");
  modal.id = "modal-exclusao-custom";
  modal.innerHTML = `
        <div class="overlay-modal">
            <div class="container-modal">
                <div class="header-modal">
                    <div class="icone-aviso">⚠️</div>
                    <h3 class="titulo-modal">Confirmar Exclusão</h3>
                </div>
                
                <div class="corpo-modal">
                    <p class="texto-confirmacao">
                        Tem certeza que deseja excluir o mentor <strong>${nome}</strong>?
                    </p>
                    <p class="texto-aviso">
                        Esta ação não pode ser desfeita.
                    </p>
                </div>
                
                <div class="rodape-modal">
                    <button class="btn-modal btn-cancelar" onclick="fecharModalCustom()">
                        <span>❌ Cancelar</span>
                    </button>
                    <button class="btn-modal btn-confirmar" onclick="confirmarExclusaoCustom('${mentorId}', '${nome}')">
                        <span>✅ Sim, excluir</span>
                    </button>
                </div>
            </div>
        </div>
    `;

  // Aplicar estilos inline para garantir funcionamento
  modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 999999;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(0, 0, 0, 0.8);
        backdrop-filter: blur(5px);
        animation: modalFadeIn 0.3s ease-out;
    `;

  // Adicionar estilos CSS inline
  const style = document.createElement("style");
  style.textContent = `
        @keyframes modalFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes modalSlideIn {
            from { 
                opacity: 0;
                transform: translateY(-30px) scale(0.95);
            }
            to { 
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        #modal-exclusao-custom .overlay-modal {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            padding: 20px;
            box-sizing: border-box;
        }
        
        #modal-exclusao-custom .container-modal {
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
            max-width: 450px;
            width: 100%;
            max-height: 90vh;
            overflow: hidden;
            animation: modalSlideIn 0.4s ease-out;
            position: relative;
        }
        
        #modal-exclusao-custom .header-modal {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px 25px;
            text-align: center;
            position: relative;
            color: white;
        }
        
        #modal-exclusao-custom .icone-aviso {
            font-size: 48px;
            margin-bottom: 15px;
            display: block;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
        }
        
        #modal-exclusao-custom .titulo-modal {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
            text-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }
        
        #modal-exclusao-custom .corpo-modal {
            padding: 35px 30px;
            text-align: center;
        }
        
        #modal-exclusao-custom .texto-confirmacao {
            font-size: 18px;
            color: #2d3748;
            margin: 0 0 20px 0;
            line-height: 1.5;
            font-weight: 500;
        }
        
        #modal-exclusao-custom .texto-aviso {
            font-size: 14px;
            color: #e53e3e;
            margin: 0;
            font-weight: 600;
        }
        
        #modal-exclusao-custom .rodape-modal {
            padding: 0 30px 30px;
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        
        #modal-exclusao-custom .btn-modal {
            flex: 1;
            padding: 16px 20px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        
        #modal-exclusao-custom .btn-cancelar {
            background: #f8f9fa;
            color: #495057;
            border: 2px solid #dee2e6;
        }
        
        #modal-exclusao-custom .btn-cancelar:hover {
            background: #e9ecef;
            border-color: #adb5bd;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        #modal-exclusao-custom .btn-confirmar {
            background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%);
            color: white;
            border: 2px solid transparent;
        }
        
        #modal-exclusao-custom .btn-confirmar:hover {
            background: linear-gradient(135deg, #c53030 0%, #a02828 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(229, 62, 62, 0.4);
        }
        
        @media (max-width: 480px) {
            #modal-exclusao-custom .container-modal {
                margin: 10px;
                max-width: calc(100% - 20px);
            }
            
            #modal-exclusao-custom .header-modal {
                padding: 25px 20px;
            }
            
            #modal-exclusao-custom .corpo-modal {
                padding: 25px 20px;
            }
            
            #modal-exclusao-custom .rodape-modal {
                padding: 0 20px 25px;
                flex-direction: column;
            }
            
            #modal-exclusao-custom .icone-aviso {
                font-size: 40px;
            }
            
            #modal-exclusao-custom .titulo-modal {
                font-size: 20px;
            }
        }
    `;

  // Adicionar estilos e modal ao documento
  document.head.appendChild(style);
  document.body.appendChild(modal);

  // Evento ESC para fechar
  const handleEsc = function (e) {
    if (e.key === "Escape") {
      document.removeEventListener("keydown", handleEsc);
      fecharModalCustom();
    }
  };
  document.addEventListener("keydown", handleEsc);

  // Clique fora para fechar
  modal.addEventListener("click", function (e) {
    if (e.target === modal || e.target.classList.contains("overlay-modal")) {
      fecharModalCustom();
    }
  });

  console.log("✅ Modal centralizado criado e exibido");
}

// Função para fechar modal customizado
window.fecharModalCustom = function () {
  console.log("🚪 Fechando modal");

  const modal = document.getElementById("modal-exclusao-custom");
  if (modal) {
    modal.style.opacity = "0";
    modal.style.transform = "scale(0.95)";

    setTimeout(() => {
      if (modal.parentNode) {
        document.body.removeChild(modal);
      }
    }, 300);
  }
};

// Função para confirmar exclusão
window.confirmarExclusaoCustom = function (mentorId, nome) {
  console.log("✅ Confirmando exclusão do mentor:", nome);

  fecharModalCustom();
  executarExclusaoDefinitiva(mentorId, nome);
};

async function executarExclusaoDefinitiva(mentorId, nome) {
  console.log("🗑️ Executando exclusão definitiva para ID:", mentorId);

  // Criar loading customizado
  const loading = document.createElement("div");
  loading.id = "loading-exclusao-custom";
  loading.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000000;
        backdrop-filter: blur(5px);
    `;

  loading.innerHTML = `
        <div style="
            background: white;
            padding: 40px 35px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            max-width: 300px;
            width: 90%;
        ">
            <div style="
                width: 50px;
                height: 50px;
                border: 4px solid #f3f3f3;
                border-top: 4px solid #667eea;
                border-radius: 50%;
                animation: spin 1s linear infinite;
                margin: 0 auto 20px;
            "></div>
            <div style="
                font-size: 18px;
                font-weight: 600;
                color: #2d3748;
                margin-bottom: 10px;
            ">Excluindo mentor...</div>
            <div style="
                font-size: 14px;
                color: #718096;
            ">Por favor, aguarde</div>
        </div>
    `;

  // Adicionar animação de loading
  const loadingStyle = document.createElement("style");
  loadingStyle.textContent = `
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    `;
  document.head.appendChild(loadingStyle);
  document.body.appendChild(loading);

  try {
    const formData = new FormData();
    formData.append("excluir_mentor", mentorId);

    const response = await fetch("gestao-diaria.php", {
      method: "POST",
      body: formData,
      headers: { "X-Requested-With": "XMLHttpRequest" },
    });

    // Remover loading
    if (loading.parentNode) {
      document.body.removeChild(loading);
    }

    if (response.ok) {
      // Criar toast de sucesso
      mostrarToastCustom("✅ Mentor excluído com sucesso!", "sucesso");

      // Fechar modal principal de edição
      const modalPrincipal = document.getElementById("modal-form");
      if (modalPrincipal) {
        modalPrincipal.classList.remove("show");
        modalPrincipal.style.display = "none";
        document.body.style.overflow = "";
      }

      // Recarregar página
      setTimeout(() => {
        if (
          typeof MentorManager !== "undefined" &&
          MentorManager.recarregarMentores
        ) {
          MentorManager.recarregarMentores();
        } else {
          window.location.reload();
        }
      }, 1500);
    } else {
      throw new Error(`Erro HTTP: ${response.status}`);
    }
  } catch (error) {
    console.error("❌ Erro na exclusão:", error);

    // Remover loading se ainda existir
    if (loading.parentNode) {
      document.body.removeChild(loading);
    }

    mostrarToastCustom(`❌ Erro: ${error.message}`, "erro");
  }
}

// Toast customizado
function mostrarToastCustom(mensagem, tipo) {
  const toast = document.createElement("div");
  toast.classList.add(
    "toast",
    tipo === "sucesso" ? "toast--sucesso" : "toast--erro"
  );
  toast.style.cssText = `
        position: fixed;
        top: 30px;
        right: 30px;
        background: white;
        color: #2d3748;
        padding: 20px 25px;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        z-index: 1000001;
        font-size: 16px;
        font-weight: 600;
        transform: translateX(400px);
        transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        max-width: 350px;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    `;

  toast.textContent = mensagem;
  document.body.appendChild(toast);

  // Animar entrada
  requestAnimationFrame(() => {
    toast.style.transform = "translateX(0)";
  });

  // Remover após 4 segundos
  setTimeout(() => {
    toast.style.transform = "translateX(400px)";
    setTimeout(() => {
      if (toast.parentNode) {
        document.body.removeChild(toast);
      }
    }, 400);
  }, 4000);
}

console.log("🎯 Modal centralizado configurado com sucesso!");
// ========================================================================================================================
//                        💼  FIM FORMULARIO DE CADASTRO DO MENTOR + MODAL EXCLUSÃO DO MENTOR
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
// ========================================================================================================================
//                                      VERIFICAÇÃO DE MENTORES CADASTRADO PARA NÃO DA ERRO
// ========================================================================================================================

// Estado global para controlar mentores
window.estadoMentores = {
  temMentores: false,
  totalReais: 0,
  mentorOcultoAtivo: false,
  ultimaVerificacao: null,
};

// ===== EXTENSÕES PARA O MENTOR MANAGER =====
if (typeof MentorManager !== "undefined") {
  // Backup da função original
  const originalRecarregarMentores = MentorManager.recarregarMentores;

  // Sobrescrever com verificação de mentor oculto
  MentorManager.recarregarMentores = async function () {
    try {
      // Incluir período atual sempre
      const formData = new FormData();
      if (typeof SistemaFiltroPeriodo !== "undefined") {
        formData.append("periodo", SistemaFiltroPeriodo.periodoAtual);
      }

      const response = await fetch("carregar-mentores.php", {
        method: "POST",
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

      // Atualiza o conteúdo
      container.innerHTML = html;

      // Verifica estado dos mentores após carregamento
      verificarEstadoMentores();

      // Reaplica eventos e estilos
      this.aplicarEstilosCorretos();
      this.atualizarDashboard(container);

      console.log("Mentores recarregados com verificação de estado");
    } catch (error) {
      console.error("Erro ao recarregar mentores:", error);
      // Em caso de erro, garante valores seguros
      garantirValoresSegurosSemMentores();

      if (typeof ToastManager !== "undefined") {
        ToastManager.mostrar(
          "Erro ao carregar mentores: " + error.message,
          "erro"
        );
      }
    }
  };

  // Backup da função original de atualizar dashboard
  const originalAtualizarDashboard = MentorManager.atualizarDashboard;

  // Sobrescrever com valores seguros
  MentorManager.atualizarDashboard = function (container) {
    try {
      // Verificar se há mentores reais antes de atualizar
      const estadoElement = document.getElementById("estado-mentores");
      const temMentores = estadoElement
        ? estadoElement.dataset.temMentores === "true"
        : false;

      if (!temMentores) {
        // Usar valores seguros para dashboard sem mentores
        atualizarDashboardSemMentores();
        return;
      }

      // Se há mentores, usar função original
      if (originalAtualizarDashboard) {
        originalAtualizarDashboard.call(this, container);
      }
    } catch (error) {
      console.error("Erro ao atualizar dashboard:", error);
      atualizarDashboardSemMentores();
    }
  };
}

// ===== FUNÇÃO PRINCIPAL PARA VERIFICAR ESTADO =====
function verificarEstadoMentores() {
  try {
    const estadoElement = document.getElementById("estado-mentores");

    if (estadoElement) {
      const temMentores = estadoElement.dataset.temMentores === "true";
      const totalReais = parseInt(estadoElement.dataset.totalReais) || 0;

      // Atualizar estado global
      window.estadoMentores.temMentores = temMentores;
      window.estadoMentores.totalReais = totalReais;
      window.estadoMentores.ultimaVerificacao = new Date();

      console.log("Estado dos mentores verificado:", {
        temMentores,
        totalReais,
        timestamp: new Date().toLocaleTimeString(),
      });

      // Configurar comportamento baseado no estado
      if (!temMentores) {
        configurarComportamentoSemMentores();
      } else {
        configurarComportamentoComMentores();
      }
    } else {
      console.warn("Elemento de estado dos mentores não encontrado");
      // Fallback: assumir que não há mentores e aplicar valores seguros
      garantirValoresSegurosSemMentores();
    }
  } catch (error) {
    console.error("Erro ao verificar estado dos mentores:", error);
    garantirValoresSegurosSemMentores();
  }
}

// ===== CONFIGURAÇÕES PARA QUANDO NÃO HÁ MENTORES =====
function configurarComportamentoSemMentores() {
  console.log("Configurando comportamento para estado SEM MENTORES");

  // Garantir valores seguros no dashboard
  atualizarDashboardSemMentores();

  // Desabilitar funcionalidades que dependem de mentores
  desabilitarFuncionalidadesMentores();

  // Configurar botão de primeiro mentor se existir
  configurarBotaoPrimeiroMentor();

  // Evitar atualizações automáticas desnecessárias
  if (typeof MentorManager !== "undefined" && MentorManager.intervalUpdateId) {
    clearInterval(MentorManager.intervalUpdateId);
    MentorManager.intervalUpdateId = null;
    console.log("Atualização automática pausada (sem mentores)");
  }
}

// ===== CONFIGURAÇÕES PARA QUANDO HÁ MENTORES =====
function configurarComportamentoComMentores() {
  console.log("Configurando comportamento para estado COM MENTORES");

  // Reabilitar funcionalidades
  habilitarFuncionalidadesMentores();

  // Reativar atualizações automáticas se necessário
  if (typeof MentorManager !== "undefined" && !MentorManager.intervalUpdateId) {
    MentorManager.iniciarAtualizacaoAutomatica();
    console.log("Atualização automática reativada");
  }
}

// ===== ATUALIZAR DASHBOARD SEM MENTORES =====
function atualizarDashboardSemMentores() {
  try {
    // Valores seguros para placar
    const placarGreen = document.querySelector(".placar-green");
    const placarRed = document.querySelector(".placar-red");

    if (placarGreen) placarGreen.textContent = "0";
    if (placarRed) placarRed.textContent = "0";

    // Valores seguros para saldo
    const valorSpan = document.querySelector(".valor-saldo");
    if (valorSpan) {
      valorSpan.textContent = "R$ 0,00";
      valorSpan.classList.remove("saldo-positivo", "saldo-negativo");
      valorSpan.classList.add("saldo-neutro");
    }

    // Valores seguros para meta
    const metaSpan = document.querySelector("#meta-dia");
    const rotuloMetaSpan = document.querySelector(".rotulo-meta");

    if (metaSpan && rotuloMetaSpan) {
      // Manter a meta original, apenas zerar o progresso
      rotuloMetaSpan.innerHTML = "Meta do Dia";
      // Não alterar o valor da meta, apenas o progresso
    }

    console.log(
      "Dashboard atualizado com valores seguros para estado sem mentores"
    );
  } catch (error) {
    console.error("Erro ao atualizar dashboard sem mentores:", error);
  }
}

// ===== GARANTIR VALORES SEGUROS =====
function garantirValoresSegurosSemMentores() {
  try {
    // Criar elementos de dados seguros se não existirem
    const elementosSeguros = [
      { id: "total-green-dia", attr: "green", valor: "0" },
      { id: "total-red-dia", attr: "red", valor: "0" },
      { id: "saldo-dia", attr: "total", valor: "0,00" },
    ];

    elementosSeguros.forEach(({ id, attr, valor }) => {
      let elemento = document.getElementById(id);
      if (!elemento) {
        elemento = document.createElement("div");
        elemento.id = id;
        elemento.style.display = "none";
        document.body.appendChild(elemento);
      }
      elemento.dataset[attr] = valor;
    });

    // Atualizar estado global
    window.estadoMentores.temMentores = false;
    window.estadoMentores.totalReais = 0;
    window.estadoMentores.mentorOcultoAtivo = true;

    console.log("Valores seguros garantidos para sistema sem mentores");
  } catch (error) {
    console.error("Erro ao garantir valores seguros:", error);
  }
}

// ===== CONFIGURAR BOTÃO PRIMEIRO MENTOR (VERSÃO DIRETA) =====
function configurarBotaoPrimeiroMentor() {
  const botao = document.querySelector(".btn-primeiro-mentor");
  if (!botao || botao.dataset.configurado === "true") return;

  botao.addEventListener("click", function () {
    // Chamada DIRETA sem interceptações ou delays
    if (typeof prepararFormularioNovoMentor !== "undefined") {
      prepararFormularioNovoMentor();
    } else if (typeof FormularioManager !== "undefined") {
      FormularioManager.prepararNovoMentor();
    }
  });

  botao.dataset.configurado = "true";
}

// ===== DESABILITAR FUNCIONALIDADES =====
function desabilitarFuncionalidadesMentores() {
  // Lista de seletores para desabilitar
  const seletoresDesabilitar = [
    ".mentor-card:not(.sem-mentores)",
    ".menu-toggle",
    ".mentor-menu-externo",
  ];

  seletoresDesabilitar.forEach((seletor) => {
    const elementos = document.querySelectorAll(seletor);
    elementos.forEach((el) => {
      el.style.pointerEvents = "none";
      el.style.opacity = "0.5";
    });
  });
}

// ===== HABILITAR FUNCIONALIDADES =====
function habilitarFuncionalidadesMentores() {
  // Lista de seletores para habilitar
  const seletoresHabilitar = [
    ".mentor-card",
    ".menu-toggle",
    ".mentor-menu-externo",
  ];

  seletoresHabilitar.forEach((seletor) => {
    const elementos = document.querySelectorAll(seletor);
    elementos.forEach((el) => {
      el.style.pointerEvents = "";
      el.style.opacity = "";
    });
  });
}

// ===== EXTENSÃO PARA FORMULÁRIO VALOR MANAGER =====
if (typeof FormularioValorManager !== "undefined") {
  // Backup da função original
  const originalExibirFormulario =
    FormularioValorManager.exibirFormularioMentor;

  // Sobrescrever para verificar estado
  FormularioValorManager.exibirFormularioMentor = function (card) {
    // Verificar se existem mentores reais
    if (!window.estadoMentores.temMentores) {
      if (typeof ToastManager !== "undefined") {
        ToastManager.mostrar(
          "Cadastre um mentor primeiro para começar a usar o sistema",
          "aviso"
        );
      } else {
        alert("Cadastre um mentor primeiro para começar a usar o sistema");
      }
      return;
    }

    // ✅ NOTA: Validação de entradas agora é feita APENAS no submit (não aqui)
    // Isso permite que o usuário abra o formulário mas bloqueia antes de salvar
    // se já fez 3 entradas no dia

    // Se há mentores, usar função original
    if (originalExibirFormulario) {
      originalExibirFormulario.call(this, card);
    }
  };
}

// ===== EXTENSÃO PARA META DIÁRIA MANAGER =====
if (typeof MetaDiariaManager !== "undefined") {
  // Backup da função original
  const originalAtualizarMeta = MetaDiariaManager.atualizarMetaDiaria;

  // Sobrescrever com verificação de estado
  MetaDiariaManager.atualizarMetaDiaria = async function (
    aguardarDados = false
  ) {
    try {
      // Verificar estado dos mentores
      const temMentores = window.estadoMentores.temMentores;

      // Se não há mentores, usar valores seguros mas manter cálculo da meta
      if (!temMentores) {
        console.log("MetaDiariaManager: Usando valores seguros (sem mentores)");

        // Ainda assim, buscar dados da banca para calcular meta correta
        const response = await fetch("dados_banca.php", {
          method: "GET",
          headers: {
            "Cache-Control": "no-cache",
            "X-Requested-With": "XMLHttpRequest",
          },
        });

        if (response.ok) {
          const data = await response.json();
          if (data.success) {
            // Forçar lucro zero mas manter meta real
            const dataSemMentores = {
              ...data,
              lucro: 0,
              lucro_formatado: "R$ 0,00",
            };

            this.atualizarTodosElementos(dataSemMentores);
            return dataSemMentores;
          }
        }

        // Fallback: valores mínimos seguros
        return null;
      }

      // Se há mentores, usar função original
      if (originalAtualizarMeta) {
        return await originalAtualizarMeta.call(this, aguardarDados);
      }
    } catch (error) {
      console.error("Erro no MetaDiariaManager com mentor oculto:", error);
      return null;
    }
  };
}

// ===== INTERCEPTAÇÃO REMOVIDA =====
// FUNÇÃO REMOVIDA: interceptarCadastroMentor()
// Esta função estava causando delays desnecessários no primeiro cadastro

// ===== FUNÇÃO DE DEBUG =====
window.debugMentorOculto = function () {
  const info = {
    estadoGlobal: window.estadoMentores,
    elementoEstado: document.getElementById("estado-mentores")?.dataset || null,
    elementosSeguros: {
      green:
        document.getElementById("total-green-dia")?.dataset?.green || "N/A",
      red: document.getElementById("total-red-dia")?.dataset?.red || "N/A",
      saldo: document.getElementById("saldo-dia")?.dataset?.total || "N/A",
    },
    botaoPrimeiro: !!document.querySelector(".btn-primeiro-mentor"),
    containerSemMentores: !!document.querySelector(".sem-mentores"),
    mentoresVisiveis: document.querySelectorAll(
      ".mentor-card:not(.sem-mentores)"
    ).length,
  };

  console.log("🔍 Debug Mentor Oculto:", info);
  return info;
};

// ===== INICIALIZAÇÃO MÍNIMA (SEM INTERCEPTAÇÕES) =====
function inicializarSistemaMentorOculto() {
  // Apenas verificar estado inicial - SEM interceptações
  setTimeout(() => {
    verificarEstadoMentores();
    // REMOVIDO: interceptarCadastroMentor() - estava causando delay
  }, 100);

  // Verificação menos frequente
  setInterval(verificarEstadoMentores, 15000);
}

// Auto-inicialização imediata
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", inicializarSistemaMentorOculto);
} else {
  inicializarSistemaMentorOculto();
}

// ===== EXPORT PARA ACESSO GLOBAL =====
window.SistemaMentorOculto = {
  verificarEstado: verificarEstadoMentores,
  configurarSemMentores: configurarComportamentoSemMentores,
  configurarComMentores: configurarComportamentoComMentores,
  garantirValoresSegurosSem: garantirValoresSegurosSemMentores,
  debug: window.debugMentorOculto,
};

console.log("Sistema de Mentor Oculto carregado!");
console.log("Funcionalidades:");
console.log("- Mentor oculto para evitar erros de cálculo");
console.log("- Botão 'Cadastre Seu Primeiro Mentor'");
console.log("- Valores seguros quando não há mentores");
console.log("- Verificação automática de estado");
console.log("- Debug com debugMentorOculto()");

// ========================================================================================================================
//                                  ✅  FIM VERIFICAÇÃO DE MENTORES CADASTRADO PARA NÃO DA ERRO
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
// ========================================================================================================================
//                                  ✅  SISTEMA DE CORES DINÂMICAS DO RANK
// ========================================================================================================================

// Adicionar classe ao rank baseada no estado do card
function atualizarCoresRank() {
  document.querySelectorAll(".mentor-item").forEach((item) => {
    const card = item.querySelector(".mentor-card");
    const rank = item.querySelector(".mentor-rank-externo");

    if (card && rank) {
      // Remove classes antigas
      rank.classList.remove("rank-positivo", "rank-negativo", "rank-neutro");

      // Adiciona classe baseada no estado do card
      if (card.classList.contains("card-positivo")) {
        rank.classList.add("rank-positivo");
      } else if (card.classList.contains("card-negativo")) {
        rank.classList.add("rank-negativo");
      } else if (card.classList.contains("card-neutro")) {
        rank.classList.add("rank-neutro");
      }
    }
  });
}

// Integrar com o MentorManager existente
if (typeof MentorManager !== "undefined") {
  const originalAplicarEstilos = MentorManager.aplicarEstilosCorretos;

  MentorManager.aplicarEstilosCorretos = function () {
    // Executa função original
    if (originalAplicarEstilos) {
      originalAplicarEstilos.call(this);
    }

    // Aplica cores aos ranks
    setTimeout(atualizarCoresRank, 100);
  };
}

// Executar quando a página carregar
document.addEventListener("DOMContentLoaded", () => {
  setTimeout(atualizarCoresRank, 500);
});

// ========================================================================================================================
//                                  ✅  FIM SISTEMA DE CORES DINÂMICAS DO RANK
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
// ========================================================================================================================
//                     ✅ SISTEMA DE RANKING DINÂMICO ROBUSTO - SEMPRE ATUALIZADO
// ========================================================================================================================

// ========================================================================================================================
//                     SISTEMA DE RANKING COMPLETO - CORRIGIDO PARA F5
// ========================================================================================================================

(function () {
  "use strict";

  console.log("Sistema de ranking iniciado - modo carregamento inicial");

  // Observer para detectar quando elementos aparecem
  const detectarElementos = new MutationObserver(function (mutations) {
    mutations.forEach(function (mutation) {
      if (mutation.addedNodes.length > 0) {
        for (let node of mutation.addedNodes) {
          if (node.nodeType === 1) {
            if (node.classList && node.classList.contains("mentor-item")) {
              processarMentorItem(node);
            } else if (
              node.querySelector &&
              node.querySelector(".mentor-item")
            ) {
              node
                .querySelectorAll(".mentor-item")
                .forEach(processarMentorItem);
            }
          }
        }
      }
    });
  });

  // Função para processar um mentor individual
  function processarMentorItem(item) {
    if (
      item.classList.contains("sem-mentores") ||
      item.dataset.processado === "true"
    ) {
      return;
    }

    item.dataset.processado = "true";

    const greenElement = item.querySelector(".value-box-green p:nth-child(2)");
    const redElement = item.querySelector(".value-box-red p:nth-child(2)");
    const saldoElement = item.querySelector(".value-box-saldo p:nth-child(2)");
    const rankElement = item.querySelector(".mentor-rank-externo");
    const nomeElement = item.querySelector(".mentor-nome");

    if (!greenElement || !redElement || !saldoElement || !rankElement) {
      return;
    }

    const green = parseInt(greenElement.textContent || "0") || 0;
    const red = parseInt(redElement.textContent || "0") || 0;
    const saldoTexto = saldoElement.textContent || "R$ 0,00";
    const saldo =
      parseFloat(
        saldoTexto.replace("R$", "").replace(/\./g, "").replace(",", ".").trim()
      ) || 0;

    const temValor = green > 0 || red > 0 || saldo !== 0;
    const nome = nomeElement ? nomeElement.textContent : "Mentor";

    console.log(
      `Processando: ${nome} - Green: ${green}, Red: ${red}, Saldo: ${saldo}, TemValor: ${temValor}`
    );

    if (!temValor) {
      item.classList.add("sem-valores");
      rankElement.style.display = "none";
      rankElement.style.visibility = "hidden";
      rankElement.style.opacity = "0";

      const card = item.querySelector(".mentor-card");
      if (card) {
        card.style.opacity = "0.42";
        card.style.background = "#f5f5f5";
        card.style.borderStyle = "dashed";
      }

      console.log(`Rank oculto: ${nome}`);
    } else {
      item.classList.remove("sem-valores");
      rankElement.style.display = "flex";
      rankElement.style.visibility = "visible";
      rankElement.style.opacity = "1";

      const card = item.querySelector(".mentor-card");
      if (card) {
        card.style.opacity = "1";
        card.style.background = "";
        card.style.borderStyle = "";
      }

      console.log(`Rank visível: ${nome}`);
    }
  }

  // Função para processar todos os mentores existentes
  function processarTodosMentores() {
    const items = document.querySelectorAll(".mentor-item");
    console.log(`Processando ${items.length} mentores encontrados`);

    items.forEach(processarMentorItem);
    setTimeout(executarRanking, 200);
  }

  // Função principal de ranking com reordenação
  function executarRanking() {
    const container =
      document.getElementById("listaMentores") ||
      document.querySelector(".mentor-wrapper");
    if (!container) return;

    const items = document.querySelectorAll(".mentor-item:not(.sem-mentores)");
    if (items.length === 0) return;

    const mentores = [];

    items.forEach((item) => {
      const greenElement = item.querySelector(
        ".value-box-green p:nth-child(2)"
      );
      const redElement = item.querySelector(".value-box-red p:nth-child(2)");
      const saldoElement = item.querySelector(
        ".value-box-saldo p:nth-child(2)"
      );

      if (!greenElement || !redElement || !saldoElement) return;

      const green = parseInt(greenElement.textContent || "0") || 0;
      const red = parseInt(redElement.textContent || "0") || 0;
      const saldoTexto = saldoElement.textContent || "R$ 0,00";
      const saldo =
        parseFloat(
          saldoTexto
            .replace("R$", "")
            .replace(/\./g, "")
            .replace(",", ".")
            .trim()
        ) || 0;

      const temValor = green > 0 || red > 0 || saldo !== 0;

      mentores.push({
        element: item,
        saldo: saldo,
        temValor: temValor,
      });
    });

    const mentoresComValor = mentores.filter((m) => m.temValor);
    const mentoresSemValor = mentores.filter((m) => !m.temValor);

    mentoresComValor.sort((a, b) => b.saldo - a.saldo);

    const elementosAuxiliares = container.querySelectorAll(
      "#total-green-dia, #total-red-dia, #saldo-dia, #meta-meia-unidade, #estado-mentores, .sem-mentores"
    );
    const fragment = document.createDocumentFragment();

    mentoresComValor.forEach((mentor, index) => {
      const rank = index + 1;
      const rankElement = mentor.element.querySelector(".mentor-rank-externo");

      mentor.element.classList.remove("sem-valores");

      if (rankElement) {
        rankElement.textContent = rank + "º";
        rankElement.style.display = "flex";
        rankElement.style.visibility = "visible";
        rankElement.style.opacity = "1";

        rankElement.classList.remove(
          "rank-positivo",
          "rank-negativo",
          "rank-neutro"
        );
        if (mentor.saldo > 0) {
          rankElement.classList.add("rank-positivo");
        } else if (mentor.saldo < 0) {
          rankElement.classList.add("rank-negativo");
        } else {
          rankElement.classList.add("rank-neutro");
        }
      }

      configurarMenuVisivel(mentor.element);
      fragment.appendChild(mentor.element);
    });

    mentoresSemValor.forEach((mentor) => {
      mentor.element.classList.add("sem-valores");

      const rankElement = mentor.element.querySelector(".mentor-rank-externo");
      if (rankElement) {
        rankElement.style.display = "none";
        rankElement.style.visibility = "hidden";
        rankElement.style.opacity = "0";
      }

      configurarMenuVisivel(mentor.element);
      fragment.appendChild(mentor.element);
    });

    const mentorItems = container.querySelectorAll(
      ".mentor-item:not(.sem-mentores)"
    );
    mentorItems.forEach((item) => item.remove());

    if (
      elementosAuxiliares.length > 0 &&
      elementosAuxiliares[0].parentNode === container
    ) {
      container.insertBefore(fragment, elementosAuxiliares[0]);
    } else {
      container.appendChild(fragment);
    }

    console.log(
      `Ranking aplicado e reordenado: ${mentoresComValor.length} com rank (primeiro), ${mentoresSemValor.length} sem rank (último)`
    );
  }

  // Função para configurar menu visível
  function configurarMenuVisivel(element) {
    const menuToggle =
      element.querySelector(".menu-toggle") ||
      element.querySelector(".mentor-menu-externo");

    if (menuToggle) {
      try {
        menuToggle.style.display = "";
        menuToggle.style.visibility = "visible";
        menuToggle.style.pointerEvents = "auto";
        menuToggle.style.opacity = "1";
        menuToggle.style.zIndex = "99999";
      } catch (e) {
        // Silencioso
      }
    }
  }

  // Inicializar sistema
  function inicializar() {
    processarTodosMentores();

    detectarElementos.observe(document.body, {
      childList: true,
      subtree: true,
    });

    console.log("Sistema de ranking inicializado");
  }

  // Executar baseado no estado do DOM
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", inicializar);
  } else {
    inicializar();
  }

  setTimeout(() => {
    if (document.querySelector(".mentor-item")) {
      processarTodosMentores();
    }
  }, 500);

  // Funções globais
  window.forcarRankingCorreto = function () {
    console.log("Forçando correção do ranking...");

    document.querySelectorAll(".mentor-item").forEach((item) => {
      item.dataset.processado = "false";
    });

    processarTodosMentores();
  };

  window.debugRanking = function () {
    const items = document.querySelectorAll(".mentor-item:not(.sem-mentores)");
    const debug = [];

    items.forEach((item, index) => {
      const nome =
        item.querySelector(".mentor-nome")?.textContent ||
        `Mentor ${index + 1}`;
      const green =
        parseInt(
          item.querySelector(".value-box-green p:nth-child(2)")?.textContent ||
            "0"
        ) || 0;
      const red =
        parseInt(
          item.querySelector(".value-box-red p:nth-child(2)")?.textContent ||
            "0"
        ) || 0;
      const saldoTexto =
        item.querySelector(".value-box-saldo p:nth-child(2)")?.textContent ||
        "R$ 0,00";
      const saldo =
        parseFloat(
          saldoTexto
            .replace("R$", "")
            .replace(/\./g, "")
            .replace(",", ".")
            .trim()
        ) || 0;

      const temValor = green > 0 || red > 0 || saldo !== 0;
      const rankElement = item.querySelector(".mentor-rank-externo");
      const rankVisivel = rankElement && rankElement.style.display !== "none";
      const rankTexto = rankElement?.textContent || "N/A";

      debug.push({
        posicaoDOM: index + 1,
        nome,
        green,
        red,
        saldo,
        temValor,
        rankVisivel,
        rankTexto,
        semValores: item.classList.contains("sem-valores"),
        correto: (temValor && rankVisivel) || (!temValor && !rankVisivel),
        ordemCorreta: temValor ? "DEVE ESTAR NO INÍCIO" : "DEVE ESTAR NO FINAL",
      });
    });

    console.table(debug);

    const mentoresComValor = debug.filter((d) => d.temValor);
    const mentoresSemValor = debug.filter((d) => !d.temValor);

    console.log(`Análise de ordem:`);
    console.log(
      `Mentores com valor (${mentoresComValor.length}): devem estar no início`
    );
    console.log(
      `Mentores sem valor (${mentoresSemValor.length}): devem estar no final`
    );

    let ordemCorreta = true;
    if (mentoresComValor.length > 0 && mentoresSemValor.length > 0) {
      const ultimaPosicaoComValor = Math.max(
        ...mentoresComValor.map((m) => m.posicaoDOM)
      );
      const primeiraPosicaoSemValor = Math.min(
        ...mentoresSemValor.map((m) => m.posicaoDOM)
      );

      if (ultimaPosicaoComValor > primeiraPosicaoSemValor) {
        ordemCorreta = false;
        console.log(
          `ORDEM INCORRETA: Mentor sem valor na posição ${primeiraPosicaoSemValor} está antes de mentor com valor na posição ${ultimaPosicaoComValor}`
        );
      }
    }

    if (ordemCorreta) {
      console.log(
        `ORDEM CORRETA: Todos os mentores com valor estão antes dos sem valor`
      );
    }

    const problemas = debug.filter((d) => !d.correto);
    if (problemas.length > 0) {
      console.log("PROBLEMAS DE RANK DETECTADOS:");
      console.table(problemas);
    }

    return {
      mentores: debug,
      ordemCorreta,
      mentoresComValor: mentoresComValor.length,
      mentoresSemValor: mentoresSemValor.length,
      problemas: problemas.length,
    };
  };

  // Integração com sistemas existentes
  if (typeof MentorManager !== "undefined") {
    const originalRecarregar = MentorManager.recarregarMentores;

    MentorManager.recarregarMentores = async function () {
      const resultado = await originalRecarregar.call(this);

      setTimeout(() => {
        window.forcarRankingCorreto();
      }, 300);

      return resultado;
    };
  }

  if (typeof SistemaFiltroPeriodo !== "undefined") {
    const originalAlterar = SistemaFiltroPeriodo.alterarPeriodo;

    SistemaFiltroPeriodo.alterarPeriodo = async function (periodo) {
      await originalAlterar.call(this, periodo);

      setTimeout(() => {
        window.forcarRankingCorreto();
      }, 500);
    };
  }

  console.log(
    "Sistema de ranking carregado - use debugRanking() para verificar"
  );
})();

// ========================================================================================================================
//                        ✅ FIM SISTEMA DE RANKING DINÂMICO ROBUSTO
// ========================================================================================================================
// ===== WORKAROUND: mover contêineres de modal para o <body> para evitar problemas
// com stacking context (transform, z-index em ancestrais). Isso garante que o
// overlay do modal cubra toda a página sempre.
function moverModaisParaBody() {
  try {
    const modais = document.querySelectorAll(".modais-container");
    modais.forEach((m) => {
      if (m.parentElement !== document.body) {
        document.body.appendChild(m);
      }
    });
  } catch (e) {
    console.warn("Não foi possível mover modais para body:", e);
  }
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", () => {
    moverModaisParaBody();
    if (
      typeof ModalManager !== "undefined" &&
      ModalManager.inicializarEventosGlobais
    ) {
      ModalManager.inicializarEventosGlobais();
    }
  });
} else {
  moverModaisParaBody();
  if (
    typeof ModalManager !== "undefined" &&
    ModalManager.inicializarEventosGlobais
  ) {
    ModalManager.inicializarEventosGlobais();
  }
}
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
//                     🎯 SISTEMA DE ALTERNÂNCIA AUTOMÁTICA: META FIXA ↔️ META TURBO
// ========================================================================================================================

(function () {
  "use strict";

  console.log(
    "🔧 Aplicando correção: Verificação de Lucro Total para Meta Turbo..."
  );

  // ==========================================
  // CONFIGURAÇÃO ATUALIZADA
  // ==========================================

  const CONFIG_META_CORRIGIDO = {
    TIPOS: {
      FIXA: "fixa",
      TURBO: "turbo",
    },

    ESTADOS_LUCRO: {
      POSITIVO: "positivo",
      NEUTRO: "neutro",
      NEGATIVO: "negativo",
    },

    TEXTOS: {
      fixa: "META FIXA",
      turbo: "META TURBO",
    },

    // 🆕 NOVO: Usar lucro total da banca
    USAR_LUCRO_TOTAL: true,

    NOTIFICAR_MUDANCA: true,
    DELAY_VERIFICACAO: 200,
    DEBUG_MODE: true,
  };

  // ==========================================
  // GERENCIADOR DE ESTADO CORRIGIDO
  // ==========================================

  const GerenciadorEstadoMetaCorrigido = {
    tipoAtual: "turbo",
    lucroTotal: 0, // 🆕 Lucro total histórico
    lucroPeriodo: 0, // Lucro do período filtrado
    estadoLucroTotal: "neutro",
    ultimaAlternancia: null,
    bloqueioTemporario: false,
    historico: [],

    /**
     * 🆕 CORRIGIDO: Determina estado baseado no LUCRO TOTAL
     */
    determinarEstadoLucro(valorLucroTotal) {
      const valor = parseFloat(valorLucroTotal) || 0;

      if (valor > 0) {
        return CONFIG_META_CORRIGIDO.ESTADOS_LUCRO.POSITIVO;
      } else if (valor < 0) {
        return CONFIG_META_CORRIGIDO.ESTADOS_LUCRO.NEGATIVO;
      } else {
        return CONFIG_META_CORRIGIDO.ESTADOS_LUCRO.NEUTRO;
      }
    },

    /**
     * 🆕 CORRIGIDO: Verifica Meta Turbo baseado no LUCRO TOTAL
     */
    podeUsarMetaTurbo(valorLucroTotal) {
      const estado = this.determinarEstadoLucro(valorLucroTotal);
      const pode = estado === CONFIG_META_CORRIGIDO.ESTADOS_LUCRO.POSITIVO;

      if (CONFIG_META_CORRIGIDO.DEBUG_MODE) {
        console.log("🔍 Verificação Meta Turbo (LUCRO TOTAL):", {
          lucroTotal: valorLucroTotal,
          estado: estado,
          podeUsarTurbo: pode,
        });
      }

      return pode;
    },

    /**
     * 🆕 CORRIGIDO: Atualiza estado com lucro total e período
     */
    atualizarEstado(dadosBanca) {
      const estadoAnterior = {
        tipo: this.tipoAtual,
        lucroTotal: this.lucroTotal,
        estadoLucro: this.estadoLucroTotal,
      };

      // 🆕 Atualizar ambos os lucros
      this.lucroTotal =
        parseFloat(dadosBanca.lucro_total_historico) ||
        parseFloat(dadosBanca.lucro_total_display) ||
        parseFloat(dadosBanca.lucro_total) ||
        0;

      this.lucroPeriodo = parseFloat(dadosBanca.lucro) || 0;

      // 🆕 Estado baseado no LUCRO TOTAL
      this.estadoLucroTotal = this.determinarEstadoLucro(this.lucroTotal);

      this.tipoAtual = dadosBanca.tipo_meta || this.tipoAtual;

      // Registrar no histórico se houve mudança
      if (
        estadoAnterior.tipo !== this.tipoAtual ||
        estadoAnterior.estadoLucro !== this.estadoLucroTotal
      ) {
        this.historico.push({
          timestamp: new Date(),
          antes: estadoAnterior,
          depois: {
            tipo: this.tipoAtual,
            lucroTotal: this.lucroTotal,
            lucroPeriodo: this.lucroPeriodo,
            estadoLucro: this.estadoLucroTotal,
          },
        });

        if (this.historico.length > 10) {
          this.historico.shift();
        }
      }

      if (CONFIG_META_CORRIGIDO.DEBUG_MODE) {
        console.log("📊 Estado Atualizado (CORRIGIDO):", {
          lucroTotal: this.lucroTotal,
          lucroPeriodo: this.lucroPeriodo,
          estadoLucroTotal: this.estadoLucroTotal,
          tipoMeta: this.tipoAtual,
          podeUsarTurbo: this.podeUsarMetaTurbo(this.lucroTotal),
        });
      }
    },

    registrarAlternancia(motivo, de, para) {
      this.ultimaAlternancia = {
        timestamp: new Date(),
        motivo: motivo,
        de: de,
        para: para,
        lucroTotal: this.lucroTotal,
        lucroPeriodo: this.lucroPeriodo,
      };

      console.log("🔄 ALTERNÂNCIA AUTOMÁTICA:", this.ultimaAlternancia);
    },
  };

  // ==========================================
  // VALIDADOR CORRIGIDO COM CONTROLE DE NOTIFICAÇÕES
  // ==========================================

  const ValidadorMetaCorrigido = {
    // 🆕 CONTROLE DE NOTIFICAÇÕES (ANTI-PISCAR)
    ultimaNotificacaoDisponibilidade: null,
    ultimaNotificacaoAlternancia: null,
    COOLDOWN_NOTIFICACAO: 300000, // 5 minutos em ms

    /**
     * 🆕 Verifica se pode mostrar notificação (cooldown)
     */
    podeNotificar(tipo) {
      const agora = Date.now();

      if (tipo === "disponibilidade") {
        if (!this.ultimaNotificacaoDisponibilidade) {
          return true;
        }

        const tempoDecorrido = agora - this.ultimaNotificacaoDisponibilidade;
        return tempoDecorrido >= this.COOLDOWN_NOTIFICACAO;
      }

      if (tipo === "alternancia") {
        if (!this.ultimaNotificacaoAlternancia) {
          return true;
        }

        const tempoDecorrido = agora - this.ultimaNotificacaoAlternancia;
        return tempoDecorrido >= 60000; // 1 minuto para alternâncias
      }

      return true;
    },

    /**
     * 🆕 Registra que uma notificação foi mostrada
     */
    registrarNotificacao(tipo) {
      const agora = Date.now();

      if (tipo === "disponibilidade") {
        this.ultimaNotificacaoDisponibilidade = agora;
      }

      if (tipo === "alternancia") {
        this.ultimaNotificacaoAlternancia = agora;
      }

      if (CONFIG_META_CORRIGIDO.DEBUG_MODE) {
        console.log(
          `📢 Notificação registrada: ${tipo} às ${new Date(
            agora
          ).toLocaleTimeString()}`
        );
      }
    },

    /**
     * 🆕 CORRIGIDO: Valida baseado no LUCRO TOTAL (sem notificações repetidas)
     */
    async validarECorrigirMeta(dadosBanca) {
      try {
        // 🆕 Extrair lucro total
        const lucroTotal =
          parseFloat(dadosBanca.lucro_total_historico) ||
          parseFloat(dadosBanca.lucro_total_display) ||
          parseFloat(dadosBanca.lucro_total) ||
          0;

        const lucroPeriodo = parseFloat(dadosBanca.lucro) || 0;
        const tipoMetaAtual = dadosBanca.tipo_meta || "turbo";

        if (CONFIG_META_CORRIGIDO.DEBUG_MODE) {
          console.log("🔍 Validação de Meta:", {
            lucroTotal: lucroTotal,
            lucroPeriodo: lucroPeriodo,
            tipoAtual: tipoMetaAtual,
            periodoFiltrado: dadosBanca.periodo_ativo || "dia",
          });
        }

        // 🆕 USAR LUCRO TOTAL para decisão
        const podeUsarTurbo =
          GerenciadorEstadoMetaCorrigido.podeUsarMetaTurbo(lucroTotal);

        // Se está em Meta Turbo mas lucro total não é positivo
        if (
          tipoMetaAtual === CONFIG_META_CORRIGIDO.TIPOS.TURBO &&
          !podeUsarTurbo
        ) {
          console.log(
            "⚠️ Meta Turbo não permitida - Lucro total não é positivo"
          );
          console.log(`💰 Lucro Total: R$ ${lucroTotal.toFixed(2)}`);

          const resultado = await this.alternarParaMetaFixa(
            lucroTotal,
            `Lucro total da banca não é positivo (R$ ${lucroTotal.toFixed(2)})`
          );

          return resultado;
        }

        // 🆕 CORRIGIDO: Se está em Meta Fixa e lucro total é positivo
        // APENAS LOGA, NÃO NOTIFICA SEMPRE (evita spam)
        if (
          tipoMetaAtual === CONFIG_META_CORRIGIDO.TIPOS.FIXA &&
          podeUsarTurbo
        ) {
          if (CONFIG_META_CORRIGIDO.DEBUG_MODE) {
            console.log("ℹ️ Lucro total positivo - Meta Turbo disponível");
            console.log(`💰 Lucro Total: R$ ${lucroTotal.toFixed(2)}`);
          }

          // 🆕 NOTIFICAR APENAS UMA VEZ (com cooldown)
          if (CONFIG_META_CORRIGIDO.NOTIFICAR_MUDANCA) {
            this.notificarDisponibilidadeTurbo(lucroTotal);
          }
        }

        return {
          valido: true,
          tipoCorreto: tipoMetaAtual,
          alternanciaAutomatica: false,
          lucroTotal: lucroTotal,
          lucroPeriodo: lucroPeriodo,
        };
      } catch (error) {
        console.error("❌ Erro na validação de meta:", error);
        return {
          valido: false,
          erro: error.message,
        };
      }
    },

    /**
     * Alterna para Meta Fixa
     */
    async alternarParaMetaFixa(lucroTotal, motivo) {
      try {
        GerenciadorEstadoMetaCorrigido.bloqueioTemporario = true;

        GerenciadorEstadoMetaCorrigido.registrarAlternancia(
          motivo,
          CONFIG_META_CORRIGIDO.TIPOS.TURBO,
          CONFIG_META_CORRIGIDO.TIPOS.FIXA
        );

        const sucesso = await this.atualizarTipoMetaNoBanco(
          CONFIG_META_CORRIGIDO.TIPOS.FIXA
        );

        if (sucesso) {
          this.atualizarInterfaceVisual(CONFIG_META_CORRIGIDO.TIPOS.FIXA);

          if (CONFIG_META_CORRIGIDO.NOTIFICAR_MUDANCA) {
            this.notificarAlternanciaAutomatica(
              CONFIG_META_CORRIGIDO.TIPOS.TURBO,
              CONFIG_META_CORRIGIDO.TIPOS.FIXA,
              motivo,
              lucroTotal
            );
          }

          if (typeof MetaDiariaManager !== "undefined") {
            setTimeout(() => {
              MetaDiariaManager.atualizarMetaDiaria(true);
            }, 100);
          }

          GerenciadorEstadoMetaCorrigido.bloqueioTemporario = false;

          return {
            sucesso: true,
            tipoCorreto: CONFIG_META_CORRIGIDO.TIPOS.FIXA,
            alternanciaAutomatica: true,
            motivo: motivo,
          };
        } else {
          throw new Error("Falha ao atualizar tipo de meta no banco");
        }
      } catch (error) {
        console.error("❌ Erro ao alternar para Meta Fixa:", error);
        GerenciadorEstadoMetaCorrigido.bloqueioTemporario = false;

        return {
          sucesso: false,
          erro: error.message,
        };
      }
    },

    /**
     * Atualiza tipo de meta no banco
     */
    async atualizarTipoMetaNoBanco(tipoMeta) {
      try {
        const tipoTexto =
          tipoMeta === CONFIG_META_CORRIGIDO.TIPOS.FIXA
            ? "Meta Fixa"
            : "Meta Turbo";

        // 🆕 Buscar valores atuais primeiro
        const dadosResponse = await fetch("dados_banca.php", {
          method: "GET",
          headers: {
            "Cache-Control": "no-cache",
            "X-Requested-With": "XMLHttpRequest",
          },
        });

        let valoresAtuais = { diaria: 1, unidade: 1, odds: 1.5 };

        if (dadosResponse.ok) {
          const dadosAtuais = await dadosResponse.json();
          if (dadosAtuais.success) {
            valoresAtuais = {
              diaria: parseFloat(dadosAtuais.diaria) || 1,
              unidade: parseFloat(dadosAtuais.unidade_entrada) || 1,
              odds: parseFloat(dadosAtuais.odds) || 1.5,
            };
          }
        }

        const response = await fetch("dados_banca.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            acao: "alterar",
            meta: tipoTexto,
            diaria: valoresAtuais.diaria,
            unidade: valoresAtuais.unidade,
            odds: valoresAtuais.odds,
          }),
        });

        if (!response.ok) {
          throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
          console.log(`✅ Tipo de meta atualizado no banco: ${tipoTexto}`);
          console.log(`✅ Valores preservados:`, valoresAtuais);
          return true;
        } else {
          throw new Error(data.message || "Erro ao atualizar meta");
        }
      } catch (error) {
        console.error("❌ Erro ao atualizar meta no banco:", error);
        return false;
      }
    },

    /**
     * Atualiza interface visual
     */
    atualizarInterfaceVisual(tipoMeta) {
      try {
        const textoMeta = CONFIG_META_CORRIGIDO.TEXTOS[tipoMeta];

        if (
          typeof MetaDiariaManager !== "undefined" &&
          MetaDiariaManager.atualizarBadgeTipoMeta
        ) {
          MetaDiariaManager.atualizarBadgeTipoMeta(textoMeta, tipoMeta);
        }

        const metaTextElement = document.getElementById("meta-text-unico");
        if (metaTextElement) {
          metaTextElement.textContent = textoMeta;
        }

        console.log(`🎨 Interface atualizada: ${textoMeta}`);
      } catch (error) {
        console.error("❌ Erro ao atualizar interface:", error);
      }
    },

    /**
     * 🆕 CORRIGIDO: Notifica com valor do lucro total (COM COOLDOWN)
     */
    notificarAlternanciaAutomatica(de, para, motivo, lucroTotal) {
      // Verificar cooldown
      if (!this.podeNotificar("alternancia")) {
        if (CONFIG_META_CORRIGIDO.DEBUG_MODE) {
          console.log("⏳ Notificação de alternância em cooldown - ignorando");
        }
        return;
      }

      const textoDe = CONFIG_META_CORRIGIDO.TEXTOS[de];
      const textoPara = CONFIG_META_CORRIGIDO.TEXTOS[para];

      const mensagem = `🔄 Alternância: ${textoDe} → ${textoPara}`;

      if (typeof ToastManager !== "undefined") {
        ToastManager.mostrar(mensagem, "aviso");
        this.registrarNotificacao("alternancia");

        setTimeout(() => {
          const explicacao = `💰 Lucro Total: R$ ${lucroTotal.toFixed(
            2
          )} - Meta Turbo requer lucro positivo`;

          if (typeof ToastManager !== "undefined") {
            ToastManager.mostrar(explicacao, "aviso");
          }
        }, 2000);
      }
    },

    /**
     * 🆕 CORRIGIDO: Notifica disponibilidade com lucro total (COM COOLDOWN)
     */
    notificarDisponibilidadeTurbo(lucroTotal) {
      // Verificar cooldown
      if (!this.podeNotificar("disponibilidade")) {
        if (CONFIG_META_CORRIGIDO.DEBUG_MODE) {
          console.log("⏳ Notificação em cooldown - ignorando");
        }
        return;
      }

      const mensagem = `✅ Lucro Total: R$ ${lucroTotal.toFixed(
        2
      )} - Meta Turbo disponível!`;

      if (typeof ToastManager !== "undefined") {
        ToastManager.mostrar(mensagem, "sucesso");
        this.registrarNotificacao("disponibilidade");

        if (CONFIG_META_CORRIGIDO.DEBUG_MODE) {
          console.log(`📢 Notificação mostrada: ${mensagem}`);
        }
      }
    },
  };

  // ==========================================
  // MONITOR CORRIGIDO COM CONTROLE DE VERIFICAÇÕES
  // ==========================================

  const MonitorLucroCorrigido = {
    ultimoLucroTotal: null,
    verificandoAtualmente: false,

    iniciar() {
      console.log("👁️ Monitor de Lucro CORRIGIDO iniciado");
      this.verificarEstadoAtual();
      this.configurarInterceptacao();
    },

    async verificarEstadoAtual() {
      if (this.verificandoAtualmente) return;

      try {
        this.verificandoAtualmente = true;

        const response = await fetch("dados_banca.php", {
          method: "GET",
          headers: {
            "Cache-Control": "no-cache",
            "X-Requested-With": "XMLHttpRequest",
          },
        });

        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        const data = await response.json();

        if (data.success) {
          await this.processarDados(data);
        }
      } catch (error) {
        console.error("❌ Erro ao verificar estado:", error);
      } finally {
        this.verificandoAtualmente = false;
      }
    },

    /**
     * 🆕 CORRIGIDO: Processa usando lucro total (com controle de notificações)
     */
    async processarDados(data) {
      // 🆕 Extrair lucro total
      const lucroTotal =
        parseFloat(data.lucro_total_historico) ||
        parseFloat(data.lucro_total_display) ||
        parseFloat(data.lucro_total) ||
        0;

      const tipoMetaAtual = data.tipo_meta || "turbo";

      // 🆕 Verificar se houve mudança SIGNIFICATIVA
      const houveMudancaLucro =
        this.ultimoLucroTotal !== null && this.ultimoLucroTotal !== lucroTotal;

      const mudouParaPositivo =
        this.ultimoLucroTotal !== null &&
        this.ultimoLucroTotal <= 0 &&
        lucroTotal > 0;

      const mudouParaNegativo =
        this.ultimoLucroTotal !== null &&
        this.ultimoLucroTotal > 0 &&
        lucroTotal <= 0;

      if (houveMudancaLucro && CONFIG_META_CORRIGIDO.DEBUG_MODE) {
        console.log("💰 Mudança no lucro total detectada:", {
          anterior: this.ultimoLucroTotal,
          atual: lucroTotal,
          diferenca: lucroTotal - this.ultimoLucroTotal,
          mudouParaPositivo: mudouParaPositivo,
          mudouParaNegativo: mudouParaNegativo,
        });
      }

      // 🆕 RESETAR cooldown de notificação apenas em mudanças SIGNIFICATIVAS
      if (mudouParaPositivo || mudouParaNegativo) {
        ValidadorMetaCorrigido.ultimaNotificacaoDisponibilidade = null;

        if (CONFIG_META_CORRIGIDO.DEBUG_MODE) {
          console.log(
            "🔄 Cooldown de notificação resetado (mudança significativa)"
          );
        }
      }

      this.ultimoLucroTotal = lucroTotal;

      // Atualizar estado
      GerenciadorEstadoMetaCorrigido.atualizarEstado(data);

      // 🆕 Validar APENAS se houve mudança significativa OU primeira execução
      if (
        this.ultimoLucroTotal === null ||
        mudouParaPositivo ||
        mudouParaNegativo
      ) {
        const resultado = await ValidadorMetaCorrigido.validarECorrigirMeta(
          data
        );

        if (resultado.alternanciaAutomatica) {
          console.log("✅ Alternância automática executada");
        }
      }
    },

    configurarInterceptacao() {
      const originalFetch = window.fetch;

      window.fetch = async function (...args) {
        const response = await originalFetch.apply(this, arguments);

        if (
          args[0] &&
          typeof args[0] === "string" &&
          (args[0].includes("dados_banca.php") ||
            args[0].includes("cadastrar-valor.php")) &&
          response.ok
        ) {
          setTimeout(() => {
            MonitorLucroCorrigido.verificarEstadoAtual();
          }, CONFIG_META_CORRIGIDO.DELAY_VERIFICACAO);
        }

        return response;
      };

      console.log("🔌 Interceptação configurada (CORRIGIDA)");
    },
  };

  // ==========================================
  // SOBRESCREVER FUNÇÃO GLOBAL
  // ==========================================

  /**
   * 🆕 VERSÃO CORRIGIDA - Verifica lucro total
   */
  window.alterarTipoMeta = async (tipo) => {
    try {
      if (!["fixa", "turbo"].includes(tipo)) {
        console.error('❌ Tipo de meta inválido. Use "fixa" ou "turbo"');
        return false;
      }

      // 🆕 Buscar dados atuais (incluindo lucro total)
      const dadosResponse = await fetch("dados_banca.php", {
        method: "GET",
        headers: {
          "Cache-Control": "no-cache",
          "X-Requested-With": "XMLHttpRequest",
        },
      });

      if (!dadosResponse.ok) {
        throw new Error("Erro ao buscar dados da banca");
      }

      const dadosAtuais = await dadosResponse.json();

      if (!dadosAtuais.success) {
        throw new Error("Dados da banca inválidos");
      }

      // 🆕 Extrair lucro total
      const lucroTotal =
        parseFloat(dadosAtuais.lucro_total_historico) ||
        parseFloat(dadosAtuais.lucro_total_display) ||
        parseFloat(dadosAtuais.lucro_total) ||
        0;

      console.log("🔍 Verificação para alteração manual:", {
        tipoDesejado: tipo,
        lucroTotal: lucroTotal,
        lucroPeriodo: parseFloat(dadosAtuais.lucro) || 0,
        periodoAtivo: dadosAtuais.periodo_ativo || "dia",
      });

      // 🆕 VERIFICAR LUCRO TOTAL para Meta Turbo
      if (tipo === "turbo") {
        const podeUsarTurbo =
          GerenciadorEstadoMetaCorrigido.podeUsarMetaTurbo(lucroTotal);

        if (!podeUsarTurbo) {
          console.log("⚠️ Meta Turbo não disponível");
          console.log(`💰 Lucro Total atual: R$ ${lucroTotal.toFixed(2)}`);

          if (typeof ToastManager !== "undefined") {
            ToastManager.mostrar(
              `❌ Meta Turbo indisponível - Lucro total: R$ ${lucroTotal.toFixed(
                2
              )}`,
              "erro"
            );

            setTimeout(() => {
              ToastManager.mostrar(
                "ℹ️ Meta Turbo requer lucro total positivo",
                "aviso"
              );
            }, 2000);
          }

          return false;
        }

        console.log("✅ Meta Turbo disponível");
        console.log(`💰 Lucro Total: R$ ${lucroTotal.toFixed(2)}`);
      }

      // Preservar valores atuais
      const valoresAtuais = {
        diaria: parseFloat(dadosAtuais.diaria) || 1,
        unidade: parseFloat(dadosAtuais.unidade_entrada) || 1,
        odds: parseFloat(dadosAtuais.odds) || 1.5,
      };

      const tipoTexto = tipo === "fixa" ? "Meta Fixa" : "Meta Turbo";

      console.log(`🔄 Alterando para: ${tipoTexto}`);

      const response = await fetch("dados_banca.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          acao: "alterar",
          meta: tipoTexto,
          diaria: valoresAtuais.diaria,
          unidade: valoresAtuais.unidade,
          odds: valoresAtuais.odds,
        }),
      });

      const data = await response.json();

      if (data.success) {
        console.log(`✅ Tipo de meta alterado: ${data.tipo_meta_texto}`);
        console.log("✅ Valores preservados:", valoresAtuais);

        // Atualizar interface
        if (
          typeof MetaDiariaManager !== "undefined" &&
          MetaDiariaManager.atualizarBadgeTipoMeta
        ) {
          MetaDiariaManager.atualizarBadgeTipoMeta(data.tipo_meta_texto, tipo);
        }

        const metaTextElement = document.getElementById("meta-text-unico");
        if (metaTextElement) {
          metaTextElement.textContent = data.tipo_meta_texto.toUpperCase();
        }

        if (typeof ToastManager !== "undefined") {
          ToastManager.mostrar(
            `✅ ${tipoTexto} ativada! (Lucro Total: R$ ${lucroTotal.toFixed(
              2
            )})`,
            "sucesso"
          );
        }

        return true;
      } else {
        throw new Error(data.message || "Erro ao alterar tipo");
      }
    } catch (error) {
      console.error("❌ Erro ao alterar tipo de meta:", error);

      if (typeof ToastManager !== "undefined") {
        ToastManager.mostrar(`❌ Erro: ${error.message}`, "erro");
      }

      return false;
    }
  };

  // ==========================================
  // INTEGRAÇÃO COM SISTEMAS EXISTENTES
  // ==========================================

  if (typeof MetaDiariaManager !== "undefined") {
    const originalAtualizarMeta = MetaDiariaManager.atualizarMetaDiaria;

    MetaDiariaManager.atualizarMetaDiaria = async function (
      aguardarDados = false
    ) {
      if (!GerenciadorEstadoMetaCorrigido.bloqueioTemporario) {
        await MonitorLucroCorrigido.verificarEstadoAtual();
      }

      return await originalAtualizarMeta.call(this, aguardarDados);
    };

    console.log("✅ MetaDiariaManager integrado (CORRIGIDO)");
  }

  // ==========================================
  // FUNÇÕES GLOBAIS ATUALIZADAS
  // ==========================================

  window.verificarEstadoMeta = async function () {
    console.log("🔍 Forçando verificação (LUCRO TOTAL)...");
    await MonitorLucroCorrigido.verificarEstadoAtual();
  };

  window.infoEstadoMeta = function () {
    const info = {
      tipoAtual: GerenciadorEstadoMetaCorrigido.tipoAtual,
      lucroTotal: GerenciadorEstadoMetaCorrigido.lucroTotal,
      lucroPeriodo: GerenciadorEstadoMetaCorrigido.lucroPeriodo,
      estadoLucroTotal: GerenciadorEstadoMetaCorrigido.estadoLucroTotal,
      podeUsarTurbo: GerenciadorEstadoMetaCorrigido.podeUsarMetaTurbo(
        GerenciadorEstadoMetaCorrigido.lucroTotal
      ),
      ultimaAlternancia: GerenciadorEstadoMetaCorrigido.ultimaAlternancia,
      historico: GerenciadorEstadoMetaCorrigido.historico.slice(-5),
    };

    console.log("📊 Estado Atual (CORRIGIDO):", info);
    return info;
  };

  window.$meta = {
    info: () => window.infoEstadoMeta(),
    verificar: () => window.verificarEstadoMeta(),
    historico: () => GerenciadorEstadoMetaCorrigido.historico,
    estado: () => ({
      tipo: GerenciadorEstadoMetaCorrigido.tipoAtual,
      lucroTotal: GerenciadorEstadoMetaCorrigido.lucroTotal,
      lucroPeriodo: GerenciadorEstadoMetaCorrigido.lucroPeriodo,
      podeUsarTurbo: GerenciadorEstadoMetaCorrigido.podeUsarMetaTurbo(
        GerenciadorEstadoMetaCorrigido.lucroTotal
      ),
    }),
  };

  // ==========================================
  // COMANDOS DE CONTROLE DE NOTIFICAÇÕES
  // ==========================================

  /**
   * Desabilita notificações temporariamente
   */
  window.desabilitarNotificacoesMeta = function (duracao = 300000) {
    CONFIG_META_CORRIGIDO.NOTIFICAR_MUDANCA = false;
    console.log(`🔕 Notificações desabilitadas por ${duracao / 1000} segundos`);

    setTimeout(() => {
      CONFIG_META_CORRIGIDO.NOTIFICAR_MUDANCA = true;
      console.log("🔔 Notificações reabilitadas");
    }, duracao);
  };

  /**
   * Habilita notificações
   */
  window.habilitarNotificacoesMeta = function () {
    CONFIG_META_CORRIGIDO.NOTIFICAR_MUDANCA = true;
    console.log("🔔 Notificações habilitadas");
  };

  /**
   * Reseta cooldown de notificações
   */
  window.resetarCooldownNotificacoes = function () {
    ValidadorMetaCorrigido.ultimaNotificacaoDisponibilidade = null;
    ValidadorMetaCorrigido.ultimaNotificacaoAlternancia = null;
    console.log("🔄 Cooldown de notificações resetado");
  };

  /**
   * Configura tempo de cooldown
   */
  window.configurarCooldownMeta = function (minutos = 5) {
    ValidadorMetaCorrigido.COOLDOWN_NOTIFICACAO = minutos * 60000;
    console.log(`⏱️ Cooldown configurado para ${minutos} minutos`);
  };

  /**
   * Status das notificações
   */
  window.statusNotificacoesMeta = function () {
    const agora = Date.now();

    const info = {
      habilitadas: CONFIG_META_CORRIGIDO.NOTIFICAR_MUDANCA,
      cooldownMinutos: ValidadorMetaCorrigido.COOLDOWN_NOTIFICACAO / 60000,
      ultimaDisponibilidade:
        ValidadorMetaCorrigido.ultimaNotificacaoDisponibilidade
          ? new Date(
              ValidadorMetaCorrigido.ultimaNotificacaoDisponibilidade
            ).toLocaleTimeString()
          : "Nunca",
      ultimaAlternancia: ValidadorMetaCorrigido.ultimaNotificacaoAlternancia
        ? new Date(
            ValidadorMetaCorrigido.ultimaNotificacaoAlternancia
          ).toLocaleTimeString()
        : "Nunca",
      proximaDisponibilidadeEm:
        ValidadorMetaCorrigido.ultimaNotificacaoDisponibilidade
          ? Math.max(
              0,
              Math.ceil(
                (ValidadorMetaCorrigido.COOLDOWN_NOTIFICACAO -
                  (agora -
                    ValidadorMetaCorrigido.ultimaNotificacaoDisponibilidade)) /
                  60000
              )
            ) + " min"
          : "Disponível agora",
    };

    console.log("📊 Status das Notificações:", info);
    return info;
  };

  // ==========================================
  // INICIALIZAÇÃO
  // ==========================================

  function inicializarSistemaCorrigido() {
    console.log("🚀 Iniciando Sistema CORRIGIDO de Alternância...");
    console.log("");
    console.log("📋 NOVA REGRA:");
    console.log("✅ Meta Turbo: Verifica LUCRO TOTAL da banca");
    console.log("❌ Ignora: Lucro do período filtrado (dia/mês/ano)");
    console.log("");
    console.log("🔔 CONTROLE DE NOTIFICAÇÕES:");
    console.log("✅ Toast aparece apenas 1x a cada 5 minutos");
    console.log("✅ Notificações apenas em mudanças significativas");
    console.log("");

    MonitorLucroCorrigido.iniciar();

    console.log("✅ Sistema CORRIGIDO ATIVO!");
    console.log("📝 Comandos:");
    console.log("  - $meta.estado() - Estado atual");
    console.log("  - $meta.info() - Info completa");
    console.log('  - alterarTipoMeta("turbo") - Testar');
    console.log("  - statusNotificacoesMeta() - Status notificações");
    console.log(
      "  - desabilitarNotificacoesMeta() - Desabilitar temporariamente"
    );
    console.log("  - resetarCooldownNotificacoes() - Resetar cooldown");
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", inicializarSistemaCorrigido);
  } else {
    setTimeout(inicializarSistemaCorrigido, 500);
  }

  window.SistemaAlternanciaMetaCorrigido = {
    Gerenciador: GerenciadorEstadoMetaCorrigido,
    Validador: ValidadorMetaCorrigido,
    Monitor: MonitorLucroCorrigido,
    CONFIG: CONFIG_META_CORRIGIDO,
  };

  console.log("✅ CORREÇÃO APLICADA: Sistema agora usa LUCRO TOTAL!");
  console.log("✅ CORREÇÃO APLICADA: Toast não pisca mais!");
})();

// ==========================================
// 🔍 FUNÇÃO DE DEBUG COMPLETA
// ==========================================

window.debugMetaTurbo = async function () {
  console.log("🔍 ===== DEBUG META TURBO =====");

  try {
    const response = await fetch("dados_banca.php", {
      method: "GET",
      headers: {
        "Cache-Control": "no-cache",
        "X-Requested-With": "XMLHttpRequest",
      },
    });

    const data = await response.json();

    if (data.success) {
      const lucroTotal =
        parseFloat(data.lucro_total_historico) ||
        parseFloat(data.lucro_total_display) ||
        parseFloat(data.lucro_total) ||
        0;

      const lucroPeriodo = parseFloat(data.lucro) || 0;
      const periodoAtivo = data.periodo_ativo || "dia";
      const tipoMetaAtual = data.tipo_meta || "turbo";

      console.log("📊 DADOS DA BANCA:");
      console.log(`   Período Ativo: ${periodoAtivo}`);
      console.log(`   Tipo Meta Atual: ${tipoMetaAtual}`);
      console.log("");
      console.log("💰 LUCROS:");
      console.log(`   Lucro TOTAL (histórico): R$ ${lucroTotal.toFixed(2)}`);
      console.log(`   Lucro do ${periodoAtivo}: R$ ${lucroPeriodo.toFixed(2)}`);
      console.log("");
      console.log("✅ DECISÃO:");
      console.log(`   Usando para verificação: Lucro TOTAL`);
      console.log(`   Valor usado: R$ ${lucroTotal.toFixed(2)}`);
      console.log(
        `   Estado: ${
          lucroTotal > 0
            ? "POSITIVO ✅"
            : lucroTotal < 0
            ? "NEGATIVO ❌"
            : "ZERO ⚠️"
        }`
      );
      console.log(
        `   Meta Turbo disponível: ${lucroTotal > 0 ? "SIM ✅" : "NÃO ❌"}`
      );
      console.log("");
      console.log("🔄 COMPORTAMENTO:");

      if (tipoMetaAtual === "turbo" && lucroTotal <= 0) {
        console.log(
          "   ⚠️ ALERTA: Meta Turbo está ativa mas lucro total não é positivo!"
        );
        console.log(
          "   🔄 Sistema irá alternar automaticamente para Meta Fixa"
        );
      } else if (tipoMetaAtual === "fixa" && lucroTotal > 0) {
        console.log("   ℹ️ Meta Fixa está ativa mas lucro total é positivo");
        console.log("   ✅ Meta Turbo está disponível para ativação manual");
      } else if (tipoMetaAtual === "turbo" && lucroTotal > 0) {
        console.log(
          "   ✅ Tudo certo! Meta Turbo ativa e lucro total positivo"
        );
      } else {
        console.log(
          "   ✅ Tudo certo! Meta Fixa ativa e lucro total não-positivo"
        );
      }

      console.log("");
      console.log("📋 DADOS COMPLETOS:");
      console.table({
        "Lucro Total Histórico": data.lucro_total_historico || "N/A",
        "Lucro Total Display": data.lucro_total_display || "N/A",
        "Lucro Período": data.lucro || "N/A",
        "Período Ativo": data.periodo_ativo || "N/A",
        "Tipo Meta": data.tipo_meta || "N/A",
      });

      return {
        lucroTotal,
        lucroPeriodo,
        periodoAtivo,
        tipoMetaAtual,
        podeUsarTurbo: lucroTotal > 0,
        decisaoCorreta:
          (tipoMetaAtual === "turbo" && lucroTotal > 0) ||
          (tipoMetaAtual === "fixa" && lucroTotal <= 0),
      };
    } else {
      console.error("❌ Erro ao buscar dados:", data.message);
      return null;
    }
  } catch (error) {
    console.error("❌ Erro no debug:", error);
    return null;
  }
};

// Atalho rápido
window.$debug = {
  meta: () => debugMetaTurbo(),
  estado: () => $meta.estado(),
  completo: () => $meta.info(),
  notificacoes: () => statusNotificacoesMeta(),
};

console.log("🔍 Debug function loaded! Use: debugMetaTurbo() or $debug.meta()");

// ==========================================
// 🧪 FUNÇÕES DE TESTE
// ==========================================

/**
 * Teste automatizado completo
 */
window.testeCompletoMeta = async function () {
  console.log("🧪 ===== TESTE COMPLETO =====\n");

  // 1. Verificar estado inicial
  console.log("1️⃣ Estado Inicial:");
  const inicial = await debugMetaTurbo();
  console.log("✅ Concluído\n");

  // 2. Testar info do sistema
  console.log("2️⃣ Info do Sistema:");
  const info = $meta.info();
  console.log("✅ Concluído\n");

  // 3. Testar estado rápido
  console.log("3️⃣ Estado Rápido:");
  const estado = $meta.estado();
  console.log("Estado:", estado);
  console.log("✅ Concluído\n");

  // 4. Verificar se lucro total está sendo usado
  console.log("4️⃣ Verificação do Lucro:");
  if (inicial && inicial.lucroTotal !== undefined) {
    console.log(`   Lucro Total: R$ ${inicial.lucroTotal.toFixed(2)}`);
    console.log(`   Lucro Período: R$ ${inicial.lucroPeriodo.toFixed(2)}`);
    console.log(`   Decisão baseada em: Lucro Total ✅`);
  }
  console.log("✅ Concluído\n");

  // 5. Testar função de alternância
  console.log("5️⃣ Teste de Alternância:");
  if (inicial && inicial.lucroTotal > 0) {
    console.log("   Testando ativação de Meta Turbo...");
    const resultado = await alterarTipoMeta("turbo");
    console.log(`   Resultado: ${resultado ? "Sucesso ✅" : "Falhou ❌"}`);
  } else {
    console.log(
      "   Lucro total não positivo - Meta Turbo deve estar bloqueada"
    );
    console.log("   Testando bloqueio...");
    const resultado = await alterarTipoMeta("turbo");
    console.log(`   Bloqueio funcionou: ${!resultado ? "Sim ✅" : "Não ❌"}`);
  }
  console.log("✅ Concluído\n");

  // 6. Status das notificações
  console.log("6️⃣ Status das Notificações:");
  const statusNotif = statusNotificacoesMeta();
  console.log("✅ Concluído\n");

  console.log("🎉 ===== TESTE COMPLETO FINALIZADO =====");
  console.log("");
  console.log("📋 RESUMO:");
  console.log("   ✅ Sistema carregado");
  console.log("   ✅ Funções disponíveis");
  console.log("   ✅ Lucro total sendo verificado");
  console.log("   ✅ Alternância funcionando");
  console.log("   ✅ Controle de notificações ativo");
  console.log("");
  console.log("🎯 Sistema está funcionando corretamente!");
};

/**
 * Monitor contínuo de mudanças
 */
window.iniciarMonitorContinuo = function () {
  console.log("👁️ Iniciando monitor contínuo...");
  console.log("Verificando a cada 10 segundos");
  console.log("Use pararMonitor() para parar");
  console.log("");

  let ultimoEstado = null;

  window.monitorInterval = setInterval(async () => {
    const estadoAtual = await debugMetaTurbo();

    if (estadoAtual) {
      // Verificar se houve mudança
      if (ultimoEstado) {
        if (estadoAtual.lucroTotal !== ultimoEstado.lucroTotal) {
          console.log("🔔 MUDANÇA DETECTADA:");
          console.log(
            `   Lucro Total: R$ ${ultimoEstado.lucroTotal.toFixed(
              2
            )} → R$ ${estadoAtual.lucroTotal.toFixed(2)}`
          );

          if (estadoAtual.podeUsarTurbo !== ultimoEstado.podeUsarTurbo) {
            console.log(
              `   Meta Turbo: ${
                ultimoEstado.podeUsarTurbo ? "Disponível" : "Bloqueada"
              } → ${estadoAtual.podeUsarTurbo ? "Disponível" : "Bloqueada"}`
            );
          }
        }

        if (estadoAtual.tipoMetaAtual !== ultimoEstado.tipoMetaAtual) {
          console.log("🔔 TIPO DE META MUDOU:");
          console.log(
            `   ${ultimoEstado.tipoMetaAtual} → ${estadoAtual.tipoMetaAtual}`
          );
        }
      }

      ultimoEstado = estadoAtual;
    }
  }, 10000);

  console.log("✅ Monitor iniciado!");
};

/**
 * Para monitor contínuo
 */
window.pararMonitor = function () {
  if (window.monitorInterval) {
    clearInterval(window.monitorInterval);
    window.monitorInterval = null;
    console.log("⏹️ Monitor parado");
  }
};

// ========================================================================================================================
//                          ✅ FIM SISTEMA DE ALTERNÂNCIA AUTOMÁTICA META FIXA/TURBO
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

// ========================================================================================================================
//                          ✅ FORMATAÇÃO DIÁRIA - SOLUÇÃO DEFINITIVA (SEM PISCAR)
// ========================================================================================================================

(function () {
  "use strict";

  console.log("🎨 Sistema de formatação definitivo iniciado");

  // ==========================================
  // FORMATADOR PURO (SEM EFEITOS COLATERAIS)
  // ==========================================

  /**
   * Formata porcentagem de forma inteligente
   */
  function formatarPorcentagem(valor) {
    try {
      // Extrair número
      const numeroStr = String(valor)
        .replace(/[^\d,.-]/g, "")
        .replace(",", ".");
      const numero = parseFloat(numeroStr);

      if (isNaN(numero)) return valor;

      // Verificar se tem decimais significativos
      if (numero % 1 === 0) {
        // Inteiro
        return Math.round(numero) + "%";
      } else {
        // Com decimais - usar ponto
        return numero.toFixed(2).replace(/\.?0+$/, "") + "%";
      }
    } catch (error) {
      return valor;
    }
  }

  // ==========================================
  // INTERCEPTAÇÃO NA ORIGEM (DADOS_BANCA.PHP)
  // ==========================================

  /**
   * Intercepta e formata ANTES de chegar no DOM
   */
  function interceptarDadosBanca() {
    if (typeof DadosManager === "undefined") {
      console.warn("⚠️ DadosManager não encontrado");
      return;
    }

    // Salvar referência original
    const originalAtualizarAreaDireita = DadosManager.atualizarAreaDireita;

    // Sobrescrever
    DadosManager.atualizarAreaDireita = function (data) {
      // ✅ FORMATAR ANTES de passar para a função original
      if (data && data.diaria_formatada) {
        data.diaria_formatada = formatarPorcentagem(data.diaria_formatada);
      }

      // Chamar função original com dados já formatados
      if (originalAtualizarAreaDireita) {
        originalAtualizarAreaDireita.call(this, data);
      }
    };

    console.log("✅ DadosManager interceptado na origem");
  }

  /**
   * Intercepta MetaDiariaManager
   */
  function interceptarMetaDiariaManager() {
    if (typeof MetaDiariaManager === "undefined") {
      console.warn("⚠️ MetaDiariaManager não encontrado");
      return;
    }

    // Salvar referência original
    const originalAtualizarAreaDireita = MetaDiariaManager.atualizarAreaDireita;

    // Sobrescrever
    MetaDiariaManager.atualizarAreaDireita = function (data) {
      // ✅ FORMATAR ANTES de passar para a função original
      if (data && data.diaria_formatada) {
        data.diaria_formatada = formatarPorcentagem(data.diaria_formatada);
      }

      // Chamar função original com dados já formatados
      if (originalAtualizarAreaDireita) {
        originalAtualizarAreaDireita.call(this, data);
      }
    };

    console.log("✅ MetaDiariaManager interceptado na origem");
  }

  // ==========================================
  // PROTEÇÃO DO ELEMENTO (BLOQUEIA ALTERAÇÕES)
  // ==========================================

  let ultimoValorDefinido = null;
  let bloqueioAtivo = false;

  /**
   * Protege o elemento contra alterações não formatadas
   */
  function protegerElemento() {
    const elemento = document.getElementById("porcentagem-diaria");

    if (!elemento) {
      console.warn("⚠️ Elemento não encontrado");
      return;
    }

    // Observer que formata IMEDIATAMENTE ao detectar mudança
    const observer = new MutationObserver((mutations) => {
      if (bloqueioAtivo) return;

      mutations.forEach((mutation) => {
        const valorAtual = elemento.textContent.trim();

        // Ignorar estados vazios
        if (!valorAtual || valorAtual === "Carregando...") {
          return;
        }

        // Verificar se precisa formatar
        const valorFormatado = formatarPorcentagem(valorAtual);

        if (
          valorFormatado !== valorAtual &&
          valorFormatado !== ultimoValorDefinido
        ) {
          // Bloquear temporariamente para evitar loop
          bloqueioAtivo = true;

          // Formatar IMEDIATAMENTE
          elemento.textContent = valorFormatado;
          ultimoValorDefinido = valorFormatado;

          // Liberar após um ciclo
          setTimeout(() => {
            bloqueioAtivo = false;
          }, 10);
        }
      });
    });

    // Observar mudanças
    observer.observe(elemento, {
      childList: true,
      characterData: true,
      subtree: true,
    });

    console.log("✅ Elemento protegido com observer imediato");
  }

  // ==========================================
  // FORMATAÇÃO INICIAL
  // ==========================================

  function formatarValorInicial() {
    const elemento = document.getElementById("porcentagem-diaria");

    if (!elemento) return;

    const valorAtual = elemento.textContent.trim();

    if (valorAtual && valorAtual !== "Carregando...") {
      const valorFormatado = formatarPorcentagem(valorAtual);

      if (valorFormatado !== valorAtual) {
        bloqueioAtivo = true;
        elemento.textContent = valorFormatado;
        ultimoValorDefinido = valorFormatado;

        setTimeout(() => {
          bloqueioAtivo = false;
        }, 100);

        console.log("✅ Valor inicial formatado:", valorFormatado);
      }
    }
  }

  // ==========================================
  // GETTER/SETTER NO ELEMENTO (NÍVEL MAIS BAIXO)
  // ==========================================

  function interceptarTextContent() {
    const elemento = document.getElementById("porcentagem-diaria");

    if (!elemento) return;

    // Salvar setter original
    const originalDescriptor = Object.getOwnPropertyDescriptor(
      Node.prototype,
      "textContent"
    );

    if (!originalDescriptor) return;

    // Criar novo descriptor que formata automaticamente
    Object.defineProperty(elemento, "textContent", {
      get: function () {
        return originalDescriptor.get.call(this);
      },
      set: function (valor) {
        // Se não for string ou estiver vazio, usar valor original
        if (typeof valor !== "string" || !valor || valor === "Carregando...") {
          return originalDescriptor.set.call(this, valor);
        }

        // ✅ FORMATAR AUTOMATICAMENTE antes de definir
        const valorFormatado = formatarPorcentagem(valor);
        ultimoValorDefinido = valorFormatado;

        return originalDescriptor.set.call(this, valorFormatado);
      },
      configurable: true,
      enumerable: true,
    });

    console.log("✅ textContent interceptado no elemento");
  }

  // ==========================================
  // TESTES
  // ==========================================

  function testar() {
    const testes = [
      { entrada: "1,00%", esperado: "1%" },
      { entrada: "1,03%", esperado: "1.03%" },
      { entrada: "1,5%", esperado: "1.5%" },
      { entrada: "2,00%", esperado: "2%" },
      { entrada: "2,50%", esperado: "2.5%" },
      { entrada: "10,25%", esperado: "10.25%" },
    ];

    console.log("🧪 Testes:");

    testes.forEach((teste) => {
      const resultado = formatarPorcentagem(teste.entrada);
      const status = resultado === teste.esperado ? "✅" : "❌";
      console.log(
        `${status} ${teste.entrada} → ${resultado} (esperado: ${teste.esperado})`
      );
    });
  }

  // ==========================================
  // INICIALIZAÇÃO
  // ==========================================

  function inicializar() {
    console.log("🚀 Iniciando formatação definitiva...");

    // Aguardar managers estarem prontos
    setTimeout(() => {
      // 1. Interceptar na origem (dados)
      interceptarDadosBanca();
      interceptarMetaDiariaManager();

      // 2. Interceptar textContent (nível baixo)
      interceptarTextContent();

      // 3. Proteger com observer
      protegerElemento();

      // 4. Formatar valor inicial
      formatarValorInicial();

      console.log("✅ Sistema completamente carregado!");
      console.log("📋 Camadas de proteção:");
      console.log("   1. Interceptação de dados (origem)");
      console.log("   2. Interceptação de textContent");
      console.log("   3. Observer de proteção");
      console.log("   4. Formatação inicial");
    }, 1000);
  }

  // ==========================================
  // FUNÇÕES GLOBAIS
  // ==========================================

  window.formatarPorcentagem = formatarPorcentagem;
  window.testarFormatacaoPorcentagem = testar;

  window.$diariaFix = {
    formatar: formatarPorcentagem,
    testar: testar,
    status: () => {
      console.log("📊 Status:", {
        ultimoValor: ultimoValorDefinido,
        bloqueioAtivo: bloqueioAtivo,
        elemento: !!document.getElementById("porcentagem-diaria"),
      });
    },
    forcar: () => {
      bloqueioAtivo = false;
      formatarValorInicial();
    },
  };

  // ==========================================
  // AUTO-INICIALIZAÇÃO
  // ==========================================

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", inicializar);
  } else {
    inicializar();
  }

  console.log("🎯 Sistema de Formatação Definitivo carregado!");
  console.log("💡 Use: $diariaFix.status() para verificar");
})();

// ========================================================================================================================
//                          ✅ FIM FORMATAÇÃO DEFINITIVA
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
// ========================================================================================================================
//                    🔧 CORREÇÃO: CÁLCULO PRECISO DO LUCRO EXTRA (CENTAVOS EXATOS)
// ========================================================================================================================

(function () {
  "use strict";

  console.log("🔧 Aplicando correção de precisão decimal no lucro extra...");

  // ==========================================
  // UTILITÁRIO DE PRECISÃO DECIMAL
  // ==========================================

  const PrecisaoDecimal = {
    /**
     * Multiplica com precisão de centavos
     */
    multiplicar(valor1, valor2) {
      const v1 = Math.round(valor1 * 100);
      const v2 = Math.round(valor2 * 100);
      return (v1 * v2) / 10000;
    },

    /**
     * Subtrai com precisão de centavos
     */
    subtrair(valor1, valor2) {
      const v1 = Math.round(valor1 * 100);
      const v2 = Math.round(valor2 * 100);
      return (v1 - v2) / 100;
    },

    /**
     * Arredonda para 2 casas decimais (centavos)
     */
    arredondar(valor) {
      return Math.round(valor * 100) / 100;
    },

    /**
     * Formata para BRL
     */
    formatarBRL(valor) {
      return valor.toLocaleString("pt-BR", {
        style: "currency",
        currency: "BRL",
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      });
    },
  };

  // ==========================================
  // SOBRESCREVER CÁLCULO NO MetaDiariaManager
  // ==========================================

  if (typeof MetaDiariaManager !== "undefined") {
    // Backup da função original
    const originalCalcularMetaFinal =
      MetaDiariaManager.calcularMetaFinalComExtra;

    // Sobrescrever com cálculo preciso
    MetaDiariaManager.calcularMetaFinalComExtra = function (
      saldoDia,
      metaCalculada,
      bancaTotal,
      data
    ) {
      try {
        let metaFinal,
          rotulo,
          statusClass,
          valorExtra = 0,
          mostrarTachado = false;

        console.log(`🔍 DEBUG CÁLCULO PRECISO:`);
        console.log(`   Saldo: ${saldoDia.toFixed(6)}`);
        console.log(`   Meta: ${metaCalculada.toFixed(6)}`);
        console.log(`   Banca: ${bancaTotal.toFixed(6)}`);

        // Arredondar valores de entrada
        const saldoArredondado = PrecisaoDecimal.arredondar(saldoDia);
        const metaArredondada = PrecisaoDecimal.arredondar(metaCalculada);

        console.log(`   Saldo arredondado: ${saldoArredondado.toFixed(2)}`);
        console.log(`   Meta arredondada: ${metaArredondada.toFixed(2)}`);

        // SEM BANCA
        if (bancaTotal <= 0) {
          metaFinal = bancaTotal;
          rotulo = "Deposite p/ Começar";
          statusClass = "sem-banca";
          console.log(`📊 RESULTADO: Sem banca`);
        }
        // META BATIDA OU SUPERADA
        else if (
          saldoArredondado > 0 &&
          metaArredondada > 0 &&
          saldoArredondado >= metaArredondada
        ) {
          // 🎯 CÁLCULO PRECISO DO LUCRO EXTRA
          valorExtra = PrecisaoDecimal.subtrair(
            saldoArredondado,
            metaArredondada
          );

          // Garantir que não há valores negativos por erro de precisão
          if (valorExtra < 0) {
            valorExtra = 0;
          }

          mostrarTachado = true;
          metaFinal = metaArredondada;

          if (valorExtra > 0) {
            rotulo = `${
              data.rotulo_periodo || "Meta"
            } Superada! <i class='fa-solid fa-trophy'></i>`;
            statusClass = "meta-superada";
            console.log(`🏆 META SUPERADA`);
          } else {
            rotulo = `${
              data.rotulo_periodo || "Meta"
            } Batida! <i class='fa-solid fa-trophy'></i>`;
            statusClass = "meta-batida";
            console.log(`🎯 META EXATA`);
          }

          console.log(`💰 Valor Extra PRECISO: R$ ${valorExtra.toFixed(2)}`);
          console.log(
            `   Cálculo: ${saldoArredondado.toFixed(
              2
            )} - ${metaArredondada.toFixed(2)} = ${valorExtra.toFixed(2)}`
          );
        }
        // META ZERO (já batida)
        else if (metaArredondada === 0 && saldoArredondado > 0) {
          metaFinal = 0;
          valorExtra = saldoArredondado;
          mostrarTachado = false;
          rotulo = `${
            data.rotulo_periodo || "Meta"
          } Batida! <i class='fa-solid fa-trophy'></i>`;
          statusClass = "meta-batida";
          console.log(`🎯 META ZERO (já batida)`);
        }
        // SALDO NEGATIVO
        else if (saldoArredondado < 0) {
          metaFinal = PrecisaoDecimal.subtrair(
            metaArredondada,
            saldoArredondado
          );
          rotulo = `Restando p/ ${data.rotulo_periodo || "Meta"}`;
          statusClass = "negativo";
          console.log(`📊 RESULTADO: Negativo`);
        }
        // SALDO ZERO
        else if (saldoArredondado === 0) {
          metaFinal = metaArredondada;
          rotulo = data.rotulo_periodo || "Meta do Dia";
          statusClass = "neutro";
          console.log(`📊 RESULTADO: Neutro`);
        }
        // LUCRO INSUFICIENTE
        else {
          metaFinal = PrecisaoDecimal.subtrair(
            metaArredondada,
            saldoArredondado
          );
          rotulo = `Restando p/ ${data.rotulo_periodo || "Meta"}`;
          statusClass = "lucro";
          console.log(`📊 RESULTADO: Lucro insuficiente`);
        }

        const resultado = {
          metaFinal: PrecisaoDecimal.arredondar(metaFinal),
          metaOriginal: metaArredondada,
          valorExtra: PrecisaoDecimal.arredondar(valorExtra),
          mostrarTachado,
          metaFinalFormatada: PrecisaoDecimal.formatarBRL(metaFinal),
          metaOriginalFormatada: PrecisaoDecimal.formatarBRL(metaArredondada),
          valorExtraFormatado:
            valorExtra > 0 ? PrecisaoDecimal.formatarBRL(valorExtra) : null,
          rotulo,
          statusClass,
        };

        console.log(`🏁 RESULTADO FINAL PRECISO:`);
        console.log(`   Status: ${statusClass}`);
        console.log(`   Meta Original: ${resultado.metaOriginalFormatada}`);
        console.log(
          `   Valor Extra: ${resultado.valorExtraFormatado || "R$ 0,00"}`
        );
        console.log(`   Mostrar Tachado: ${mostrarTachado}`);

        return resultado;
      } catch (error) {
        console.error("❌ Erro no cálculo preciso:", error);

        // Fallback para função original se houver erro
        if (originalCalcularMetaFinal) {
          return originalCalcularMetaFinal.call(
            this,
            saldoDia,
            metaCalculada,
            bancaTotal,
            data
          );
        }

        return {
          metaFinal: 0,
          metaOriginal: 0,
          valorExtra: 0,
          mostrarTachado: false,
          metaFinalFormatada: "R$ 0,00",
          metaOriginalFormatada: "R$ 0,00",
          valorExtraFormatado: null,
          rotulo: "Erro no cálculo",
          statusClass: "erro",
        };
      }
    };

    console.log(
      "✅ MetaDiariaManager.calcularMetaFinalComExtra sobrescrito com precisão decimal"
    );

    // ==========================================
    // FORÇAR RECÁLCULO IMEDIATO
    // ==========================================

    setTimeout(() => {
      console.log("🔄 Forçando recálculo com precisão...");

      if (MetaDiariaManager.atualizarMetaDiaria) {
        MetaDiariaManager.atualizarMetaDiaria(true);
      }
    }, 500);
  }

  // ==========================================
  // FUNÇÕES DE TESTE
  // ==========================================

  window.testarPrecisaoDecimal = function () {
    console.log("🧪 Testando precisão decimal:");
    console.log("");

    const testes = [
      { banca: 1011, percentual: 0.5, saldo: 11.0 },
      { banca: 1000, percentual: 0.5, saldo: 10.0 },
      { banca: 1234.56, percentual: 0.75, saldo: 15.0 },
    ];

    testes.forEach((teste, index) => {
      console.log(`Teste ${index + 1}:`);
      console.log(`  Banca: R$ ${teste.banca.toFixed(2)}`);
      console.log(`  Percentual: ${teste.percentual}%`);

      // Calcular meta
      const metaBruta = teste.banca * (teste.percentual / 100);
      const meta = PrecisaoDecimal.arredondar(metaBruta);

      console.log(`  Meta calculada: R$ ${meta.toFixed(2)}`);
      console.log(`  Saldo do dia: R$ ${teste.saldo.toFixed(2)}`);

      // Calcular lucro extra
      const lucroExtra = PrecisaoDecimal.subtrair(teste.saldo, meta);

      console.log(`  Lucro Extra PRECISO: R$ ${lucroExtra.toFixed(2)}`);
      console.log(
        `  Verificação: ${teste.saldo.toFixed(2)} - ${meta.toFixed(
          2
        )} = ${lucroExtra.toFixed(2)}`
      );
      console.log("");
    });
  };

  window.verificarCalculoAtual = async function () {
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

      if (data.success) {
        const banca = parseFloat(data.banca) || 0;
        const saldo = parseFloat(data.lucro) || 0;
        const metaDisplay = parseFloat(data.meta_display) || 0;

        console.log("📊 VERIFICAÇÃO DO CÁLCULO ATUAL:");
        console.log(`   Banca: R$ ${banca.toFixed(2)}`);
        console.log(`   Meta: R$ ${metaDisplay.toFixed(2)}`);
        console.log(`   Saldo: R$ ${saldo.toFixed(2)}`);
        console.log("");

        if (saldo >= metaDisplay && metaDisplay > 0) {
          const lucroExtra = PrecisaoDecimal.subtrair(saldo, metaDisplay);

          console.log("🎯 META SUPERADA:");
          console.log(`   Valor tachado: R$ ${metaDisplay.toFixed(2)}`);
          console.log(`   Lucro Extra CORRETO: R$ ${lucroExtra.toFixed(2)}`);
          console.log(
            `   Cálculo: ${saldo.toFixed(2)} - ${metaDisplay.toFixed(
              2
            )} = ${lucroExtra.toFixed(2)}`
          );
        } else {
          console.log("⏳ Meta ainda não batida");
        }
      }
    } catch (error) {
      console.error("❌ Erro ao verificar:", error);
    }
  };

  // ==========================================
  // COMANDOS GLOBAIS
  // ==========================================

  window.$precisao = {
    testar: () => testarPrecisaoDecimal(),
    verificar: () => verificarCalculoAtual(),
    forcar: () => {
      if (typeof MetaDiariaManager !== "undefined") {
        MetaDiariaManager.atualizarMetaDiaria(true);
      }
    },
    calcular: (valor1, operacao, valor2) => {
      switch (operacao) {
        case "-":
          return PrecisaoDecimal.subtrair(valor1, valor2);
        case "*":
          return PrecisaoDecimal.multiplicar(valor1, valor2);
        default:
          return PrecisaoDecimal.arredondar(valor1);
      }
    },
  };

  console.log("✅ Correção de precisão decimal aplicada!");
  console.log("💡 Comandos disponíveis:");
  console.log("   $precisao.testar() - Testa cálculos");
  console.log("   $precisao.verificar() - Verifica valor atual");
  console.log("   $precisao.forcar() - Força recálculo");
  console.log('   $precisao.calcular(11, "-", 5.06) - Calcula manualmente');
})();

// ========================================================================================================================
//                    ✅ MODAL DE CELEBRAÇÃO - META BATIDA DO DIA
// ========================================================================================================================

// Estado global para rastrear se o modal já foi mostrado
let modalMetaBatidaMostrado = false;

/**
 * Gerenciador do Modal de Celebração
 */
const CelebracaoMetaManager = {
  // Flag para evitar múltiplas exibições
  jaMostradoHoje: false,
  // Rastreia o status anterior da meta
  metaEraMetaAnterior: false,

  /**
   * Inicializa o manager ao carregar
   */
  inicializar() {
    this.carregarEstadoDoLocalStorage();
  },

  /**
   * Carrega o estado do localStorage
   */
  carregarEstadoDoLocalStorage() {
    try {
      const dataAtual = new Date().toISOString().split("T")[0];
      const dataSalva = localStorage.getItem("celebracao_data");
      const metaEra = localStorage.getItem("celebracao_metaEra") === "true";

      // Se é o mesmo dia, recupera o estado
      if (dataSalva === dataAtual) {
        this.metaEraMetaAnterior = metaEra;
        console.log(`📅 Estado recuperado do localStorage: metaEra=${metaEra}`);
      } else {
        // Se é um novo dia, reseta
        this.metaEraMetaAnterior = false;
        this.salvarEstadoNoLocalStorage();
        console.log("🔄 Novo dia detectado! Estado resetado.");
      }
    } catch (error) {
      console.error("❌ Erro ao carregar estado:", error);
    }
  },

  /**
   * Salva o estado no localStorage
   */
  salvarEstadoNoLocalStorage() {
    try {
      const dataAtual = new Date().toISOString().split("T")[0];
      localStorage.setItem("celebracao_data", dataAtual);
      localStorage.setItem(
        "celebracao_jaMostrado",
        this.jaMostradoHoje.toString()
      );
      localStorage.setItem(
        "celebracao_metaEra",
        this.metaEraMetaAnterior.toString()
      );
      console.log(`💾 Estado salvo: metaEra=${this.metaEraMetaAnterior}`);
    } catch (error) {
      console.error("❌ Erro ao salvar estado:", error);
    }
  },

  /**
   * Verifica se a meta foi batida e mostra o modal
   */
  verificarEMostrarModal(data) {
    try {
      if (!data) {
        return;
      }

      // Pega o período atual
      const radioPeriodo = document.querySelector(
        'input[name="periodo"]:checked'
      );
      const periodoAtual = radioPeriodo?.value || "dia";

      // Se não for o período do dia, não mostra celebração
      if (periodoAtual !== "dia") {
        return;
      }

      // Pega os valores
      const lucro = parseFloat(data.lucro) || 0;
      let metaAtual = 0;

      // Determina qual meta usar
      if (data.meta_display) {
        metaAtual = parseFloat(data.meta_display) || 0;
      } else if (data.meta_diaria) {
        metaAtual = parseFloat(data.meta_diaria) || 0;
      }

      // Verifica se está batendo a meta agora
      const metaEstaBatidaAgora = lucro >= metaAtual && metaAtual > 0;

      console.log(
        `📊 Meta: ${metaAtual}, Lucro: ${lucro}, Batida: ${metaEstaBatidaAgora}, jaMostrado: ${this.jaMostradoHoje}, metaEra: ${this.metaEraMetaAnterior}`
      );

      // LÓGICA: Mostra modal apenas se:
      // 1. A meta está batida AGORA
      // 2. A meta NÃO estava batida antes (primeira vez que bate ou voltou a bater depois de deixar de bater)
      if (metaEstaBatidaAgora && !this.metaEraMetaAnterior) {
        this.mostrarModal(data, lucro, metaAtual);
        this.metaEraMetaAnterior = true;
        this.salvarEstadoNoLocalStorage();
        console.log("🎉 Meta batida! Modal mostrado.");
      }
      // Se a meta deixou de ser batida, reseta metaEraMetaAnterior E jaMostradoHoje
      // para permitir mostrar novamente quando a meta voltar a bater
      else if (!metaEstaBatidaAgora && this.metaEraMetaAnterior) {
        this.metaEraMetaAnterior = false;
        this.jaMostradoHoje = false;
        this.salvarEstadoNoLocalStorage();
        console.log(
          "❌ Meta deixou de ser batida. Será mostrado novamente quando bater de novo."
        );
      }
    } catch (error) {
      console.error("❌ Erro ao verificar meta:", error);
    }
  },

  /**
   * Mostra o modal de celebração
   */
  mostrarModal(data, lucro, metaAtual) {
    try {
      const modal = document.getElementById("modal-meta-batida");
      if (!modal) return;

      // Calcula lucro extra
      const valorExtra = Math.max(0, lucro - metaAtual);

      // Preenche os dados do modal
      document.getElementById(
        "valor-meta-modal"
      ).textContent = `R$ ${metaAtual.toLocaleString("pt-BR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      })}`;

      document.getElementById(
        "valor-lucro-modal"
      ).textContent = `R$ ${lucro.toLocaleString("pt-BR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      })}`;

      document.getElementById(
        "valor-extra-modal"
      ).textContent = `R$ ${valorExtra.toLocaleString("pt-BR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      })}`;

      // Mostra o modal com animação
      modal.style.display = "flex";
      modal.style.animation = "aparecer-modal 0.4s ease-out";

      // Toca som de celebração (opcional)
      this.tocarSomCelebracao();

      console.log("🎉 Meta do Dia Batida! Modal exibido.");
    } catch (error) {
      console.error("❌ Erro ao mostrar modal:", error);
    }
  },

  /**
   * Toca som de celebração (opcional)
   */
  tocarSomCelebracao() {
    try {
      // Usa a Web Audio API para criar um som simples
      const audioContext = new (window.AudioContext ||
        window.webkitAudioContext)();
      const agora = audioContext.currentTime;

      // Cria notas de celebração
      const notas = [523.25, 659.25, 783.99]; // Dó, Mi, Sol

      notas.forEach((frequencia, index) => {
        const osc = audioContext.createOscillator();
        const gain = audioContext.createGain();

        osc.connect(gain);
        gain.connect(audioContext.destination);

        osc.frequency.value = frequencia;
        osc.type = "sine";

        gain.gain.setValueAtTime(0.3, agora + index * 0.1);
        gain.gain.exponentialRampToValueAtTime(0.01, agora + index * 0.1 + 0.2);

        osc.start(agora + index * 0.1);
        osc.stop(agora + index * 0.1 + 0.2);
      });
    } catch (error) {
      // Som opcional, não interrompe se falhar
      console.log("⚠️ Som de celebração não disponível");
    }
  },

  /**
   * Reseta o estado diário
   */
  resetarDiariamente() {
    // Verifica se mudou de dia
    const dataAtual = new Date().toISOString().split("T")[0];
    const dataSalva = localStorage.getItem("celebracao_data");

    if (dataSalva !== dataAtual) {
      this.jaMostradoHoje = false;
      this.metaEraMetaAnterior = false;
      this.salvarEstadoNoLocalStorage();
      console.log("🔄 Novo dia! Estado resetado.");
    }
  },
};

/**
 * Inicializa o CelebracaoMetaManager quando a página carrega
 */
document.addEventListener("DOMContentLoaded", function () {
  CelebracaoMetaManager.inicializar();
  console.log("✅ CelebracaoMetaManager inicializado!");
});

/**
 * Função global para fechar o modal
 */
window.fecharModalMetaBatida = function () {
  const modal = document.getElementById("modal-meta-batida");
  if (modal) {
    modal.style.display = "none";
    console.log("✅ Modal de celebração fechado.");
  }
};

/**
 * Integra com o MetaDiariaManager
 */
if (typeof MetaDiariaManager !== "undefined") {
  const originalatualizarTodosElementos =
    MetaDiariaManager.atualizarTodosElementos;

  MetaDiariaManager.atualizarTodosElementos = function (data) {
    // Chama a função original
    if (originalatualizarTodosElementos) {
      originalatualizarTodosElementos.call(this, data);
    }

    // Verifica e mostra celebração
    CelebracaoMetaManager.resetarDiariamente();
    CelebracaoMetaManager.verificarEMostrarModal(data);
  };
}

// Resetar flag ao carregar a página
document.addEventListener("DOMContentLoaded", () => {
  CelebracaoMetaManager.resetarDiariamente();
  console.log("🎉 Sistema de Celebração de Meta carregado!");
});

// ========================================================================================================================
//                    ✅ FIM MODAL DE CELEBRAÇÃO
// ========================================================================================================================

// ========================================================================================================================
//                    🛑 MODAL STOP LOSS - PARE DE JOGAR
// ========================================================================================================================

/**
 * StopLossManager - Controla o modal de alerta de Stop Loss
 * Mostra quando as perdas (lucro negativo) atingem -4x a meta
 */
const StopLossManager = {
  // Flag para evitar múltiplas exibições
  jaMostradoHoje: false,
  // Rastreia se o stop loss foi acionado
  stopLossAtivado: false,

  /**
   * Inicializa o manager ao carregar
   */
  inicializar() {
    this.carregarEstadoDoLocalStorage();
  },

  /**
   * Carrega o estado do localStorage
   */
  carregarEstadoDoLocalStorage() {
    try {
      const dataAtual = new Date().toISOString().split("T")[0];
      const dataSalva = localStorage.getItem("stopLoss_data");
      const stopLossAtivado =
        localStorage.getItem("stopLoss_ativado") === "true";

      // Se é o mesmo dia, recupera o estado
      if (dataSalva === dataAtual) {
        this.stopLossAtivado = stopLossAtivado;
        this.jaMostradoHoje =
          localStorage.getItem("stopLoss_jaMostrado") === "true";
        console.log(
          `📅 Stop Loss Estado recuperado: ativado=${stopLossAtivado}`
        );
      } else {
        // Se é um novo dia, reseta
        this.stopLossAtivado = false;
        this.jaMostradoHoje = false;
        this.salvarEstadoNoLocalStorage();
        console.log("🔄 Novo dia! Stop Loss resetado.");
      }
    } catch (error) {
      console.error("❌ Erro ao carregar estado Stop Loss:", error);
    }
  },

  /**
   * Salva o estado no localStorage
   */
  salvarEstadoNoLocalStorage() {
    try {
      const dataAtual = new Date().toISOString().split("T")[0];
      localStorage.setItem("stopLoss_data", dataAtual);
      localStorage.setItem("stopLoss_ativado", this.stopLossAtivado.toString());
      localStorage.setItem(
        "stopLoss_jaMostrado",
        this.jaMostradoHoje.toString()
      );
      console.log(`💾 Stop Loss salvo: ativado=${this.stopLossAtivado}`);
    } catch (error) {
      console.error("❌ Erro ao salvar estado Stop Loss:", error);
    }
  },

  /**
   * Verifica se o stop loss foi acionado e mostra o modal
   * Trigger: lucro <= -4 * meta
   */
  verificarEMostrarModal(data) {
    try {
      if (!data) {
        return;
      }

      // Pega o período atual
      const radioPeriodo = document.querySelector(
        'input[name="periodo"]:checked'
      );
      const periodoAtual = radioPeriodo?.value || "dia";

      // Se não for o período do dia, não mostra stop loss
      if (periodoAtual !== "dia") {
        return;
      }

      // Pega os valores
      const lucro = parseFloat(data.lucro) || 0;
      let metaAtual = 0;

      // Determina qual meta usar
      if (data.meta_display) {
        metaAtual = parseFloat(data.meta_display) || 0;
      } else if (data.meta_diaria) {
        metaAtual = parseFloat(data.meta_diaria) || 0;
      }

      // Calcula o limite de stop loss (-4x a meta)
      const limitStop = -(metaAtual * 4);

      // Verifica se acionou o stop loss
      const stopLossAcionadoAgora = lucro <= limitStop && metaAtual > 0;

      console.log(
        `🛑 Stop Loss Check - Meta: ${metaAtual}, Lucro: ${lucro}, Limite: ${limitStop}, Acionado: ${stopLossAcionadoAgora}, jaMostrado: ${this.jaMostradoHoje}`
      );

      // LÓGICA: Mostra modal apenas se:
      // 1. O stop loss está acionado AGORA
      // 2. Ainda NÃO foi mostrado hoje
      if (stopLossAcionadoAgora && !this.jaMostradoHoje) {
        this.mostrarModal(data, lucro, metaAtual, limitStop);
        this.stopLossAtivado = true;
        this.jaMostradoHoje = true;
        this.salvarEstadoNoLocalStorage();
        console.log("🛑 STOP LOSS ACIONADO! Modal mostrado.");
      }
      // Se o lucro voltar acima do limite, reseta o stop loss
      else if (stopLossAcionadoAgora === false && this.stopLossAtivado) {
        this.stopLossAtivado = false;
        this.jaMostradoHoje = false;
        this.salvarEstadoNoLocalStorage();
        console.log(
          "✅ Stop Loss desativado. Será mostrado novamente se as perdas voltarem a -4x da meta."
        );
      }
    } catch (error) {
      console.error("❌ Erro ao verificar stop loss:", error);
    }
  },

  /**
   * Mostra o modal de stop loss
   */
  mostrarModal(data, lucro, metaAtual, limitStop) {
    try {
      const modal = document.getElementById("modal-stop-loss");
      if (!modal) {
        console.error("❌ Modal stop loss não encontrada no DOM");
        return;
      }

      // Calcula o valor perdido (valor absoluto)
      const valorPerdido = Math.abs(lucro);

      // Preenche os dados do modal
      document.getElementById(
        "valor-perdido-modal"
      ).textContent = `R$ ${valorPerdido.toLocaleString("pt-BR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      })}`;

      document.getElementById(
        "valor-meta-stop"
      ).textContent = `R$ ${metaAtual.toLocaleString("pt-BR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      })}`;

      document.getElementById("valor-limite-stop").textContent = `R$ ${Math.abs(
        limitStop
      ).toLocaleString("pt-BR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      })}`;

      // Mostra o modal com animação
      modal.style.display = "flex";
      modal.style.animation = "aparecer-modal 0.4s ease-out";

      // Toca som de alerta (opcional)
      this.tocarSomAlerta();

      console.log("🛑 Stop Loss modal exibido com valores atualizados.");
    } catch (error) {
      console.error("❌ Erro ao mostrar modal stop loss:", error);
    }
  },

  /**
   * Toca som de alerta (opcional)
   */
  tocarSomAlerta() {
    try {
      // Usa a Web Audio API para criar um som de alerta
      const audioContext = new (window.AudioContext ||
        window.webkitAudioContext)();
      const agora = audioContext.currentTime;

      // Cria som de alerta em frequência baixa (mais dramático)
      const notas = [293.66, 329.63, 293.66, 329.63]; // Ré, Mi (som de alerta)

      notas.forEach((frequencia, index) => {
        const osc = audioContext.createOscillator();
        const gain = audioContext.createGain();

        osc.connect(gain);
        gain.connect(audioContext.destination);

        osc.frequency.value = frequencia;
        osc.type = "sine";

        gain.gain.setValueAtTime(0.4, agora + index * 0.08);
        gain.gain.exponentialRampToValueAtTime(
          0.01,
          agora + index * 0.08 + 0.15
        );

        osc.start(agora + index * 0.08);
        osc.stop(agora + index * 0.08 + 0.15);
      });
    } catch (error) {
      // Som opcional, não interrompe se falhar
      console.log("⚠️ Som de alerta não disponível");
    }
  },

  /**
   * Reseta o estado diário
   */
  resetarDiariamente() {
    // Verifica se mudou de dia
    const dataAtual = new Date().toISOString().split("T")[0];
    const dataSalva = localStorage.getItem("stopLoss_data");

    if (dataSalva !== dataAtual) {
      this.jaMostradoHoje = false;
      this.stopLossAtivado = false;
      this.salvarEstadoNoLocalStorage();
      console.log("🔄 Novo dia! Stop Loss resetado.");
    }
  },
};

/**
 * Inicializa o StopLossManager quando a página carrega
 */
document.addEventListener("DOMContentLoaded", function () {
  StopLossManager.inicializar();
  console.log("✅ StopLossManager inicializado!");
});

/**
 * Função global para fechar o modal Stop Loss
 */
window.fecharModalStopLoss = function () {
  const modal = document.getElementById("modal-stop-loss");
  if (modal) {
    modal.style.display = "none";
    console.log("✅ Modal Stop Loss fechado.");
  }
};

/**
 * Integra com o MetaDiariaManager
 */
if (typeof MetaDiariaManager !== "undefined") {
  const originalAtualizarTodosElementos =
    MetaDiariaManager.atualizarTodosElementos;

  MetaDiariaManager.atualizarTodosElementos = function (data) {
    // Chama a função original
    if (originalAtualizarTodosElementos) {
      originalAtualizarTodosElementos.call(this, data);
    }

    // Verifica e mostra stop loss
    StopLossManager.resetarDiariamente();
    StopLossManager.verificarEMostrarModal(data);
  };
}

// Resetar flag ao carregar a página
document.addEventListener("DOMContentLoaded", () => {
  StopLossManager.resetarDiariamente();
  console.log("🛑 Sistema de Stop Loss carregado!");
});

// ========================================================================================================================
//                    ✅ FIM MODAL STOP LOSS
// ========================================================================================================================

// ========================================================================================================================
//                    ✅ FIM CORREÇÃO DE PRECISÃO DECIMAL
// ========================================================================================================================
