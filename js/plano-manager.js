/**
 * GESTOR DE PLANOS E PAGAMENTOS
 * =====================================
 * Gerencia a lógica de planos, pagamentos e integrações com Mercado Pago
 */

const PlanoManager = {
  // Estado
  periodoAtual: "mes",
  planoSelecionado: null,
  modoPagementoSelecionado: "cartao",
  planos: [],
  usuario: null,

  /**
   * INICIALIZAR O SISTEMA
   */
  async inicializar() {
    try {
      console.log("🚀 Inicializando PlanoManager...");

      // Carregar planos do servidor
      await this.carregarPlanos();

      // Carregar dados do usuário
      await this.carregarDadosUsuario();

      // Renderizar planos inicialmente
      this.renderizarPlanos();

      console.log("✅ PlanoManager inicializado com sucesso");
    } catch (error) {
      console.error("❌ Erro ao inicializar PlanoManager:", error);
      ToastManager?.mostrar("Erro ao carregar planos", "erro");
    }
  },

  /**
   * CARREGAR PLANOS DO SERVIDOR
   */
  async carregarPlanos() {
    try {
      console.log("🔄 Carregando planos...");
      const response = await fetch("obter-planos.php");

      if (!response.ok) {
        throw new Error(`HTTP ${response.status} - ${response.statusText}`);
      }

      const data = await response.json();

      if (!data.success) {
        throw new Error(data.message || "Erro desconhecido ao carregar planos");
      }

      this.planos = data.planos || [];
      console.log("✅ Planos carregados com sucesso:", this.planos);

      if (this.planos.length === 0) {
        console.warn("⚠️ Nenhum plano retornado do servidor");
      }
    } catch (error) {
      console.error("❌ Erro ao carregar planos:", error);
      this.planos = [];
      throw error;
    }
  },

  /**
   * CARREGAR DADOS DO USUÁRIO
   */
  async carregarDadosUsuario() {
    try {
      const response = await fetch("obter-dados-usuario.php");
      if (!response.ok) throw new Error(`HTTP ${response.status}`);

      const data = await response.json();
      if (!data.success) throw new Error(data.message);

      this.usuario = data.usuario;
      console.log("✅ Dados do usuário carregados:", this.usuario);
    } catch (error) {
      console.error("❌ Erro ao carregar dados do usuário:", error);
    }
  },

  /**
   * RENDERIZAR GRID DE PLANOS
   */
  renderizarPlanos() {
    const container = document.getElementById("planosGrid");
    if (!container) {
      console.error("❌ Container planosGrid não encontrado!");
      return;
    }

    console.log("📊 Renderizando", this.planos.length, "planos");

    if (!this.planos || this.planos.length === 0) {
      console.warn("⚠️ Nenhum plano disponível para renderizar");
      container.innerHTML =
        '<p style="grid-column: 1/-1; text-align: center; padding: 40px;">Erro ao carregar planos</p>';
      return;
    }

    container.innerHTML = "";

    this.planos.forEach((plano) => {
      // ✅ CONVERTER PARA NÚMEROS (dados do backend vêm como string)
      const precoMes = parseFloat(plano.preco_mes) || 0;
      const precoAno = parseFloat(plano.preco_ano) || 0;
      const mentoresLimite = parseInt(plano.mentores_limite) || 0;
      const entradasDiarias = parseInt(plano.entradas_diarias) || 0;

      console.log(
        `✅ Plano: ${plano.nome} | Mês: R$ ${precoMes.toFixed(
          2
        )} | Ano: R$ ${precoAno.toFixed(2)}`
      );

      const preco = this.periodoAtual === "ano" ? precoAno : precoMes;
      
      // Calcular economia anual
      const economiaAnual = (precoMes * 12 - precoAno).toFixed(2);
      // Calcular economia mensal (economia anual dividida por 12)
      const economiaMensal = (economiaAnual / 12).toFixed(2);
      
      const card = document.createElement("div");
      card.className = `plano-card ${plano.id === 3 ? "popular" : ""}`;
      card.style.setProperty("--cor-plano", this.obterCorPlano(plano.nome));

      card.innerHTML = `
                <div class="plano-icone">
                    <i class="${plano.icone}"></i>
                </div>
                
                <div class="plano-nome">${plano.nome}</div>
                
                <div class="plano-preco">
                    R$ ${preco.toFixed(2).replace(".", ",")}
                </div>
                
                <div class="plano-ciclo">
                    ${this.periodoAtual === "ano" ? "por mês" : "por mês"}
                </div>
                
                ${
                  economiaAnual > 0 && this.periodoAtual === "ano"
                    ? `
                    <div style="color: #27ae60; font-size: 12px; font-weight: 600; margin-bottom: 10px;">
                        💰 Economize R$ ${economiaMensal.replace(".", ",")}
                    </div>
                `
                    : ""
                }
                
                <div class="plano-features">
                    <div class="plano-feature">
                        <i class="fas fa-user-tie"></i>
                        <span>${
                          mentoresLimite >= 999
                            ? "Mentores ilimitados"
                            : mentoresLimite + " Mentor(es)"
                        }</span>
                    </div>
                    <div class="plano-feature">
                        <i class="fas fa-chart-line"></i>
                        <span>${
                          entradasDiarias >= 999
                            ? "Entradas ilimitadas"
                            : entradasDiarias + " Entrada(s)/dia"
                        }</span>
                    </div>
                    <div class="plano-feature">
                        <i class="fas fa-bot"></i>
                        <span>Bot ao vivo</span>
                    </div>
                </div>
                
                ${
                  plano.id === 1
                    ? `
                    <button class="btn-contratar" disabled style="opacity: 0.5;">
                        Plano atual
                    </button>
                `
                    : `
                    <button class="btn-contratar" onclick="PlanoManager.selecionarPlano(${plano.id}, '${plano.nome}', ${preco})">
                        Contratar Agora
                    </button>
                `
                }
            `;

      container.appendChild(card);
    });
  },

  /**
   * ALTERNAR ENTRE MÊS E ANO
   */
  alternarPeriodo(periodo) {
    this.periodoAtual = periodo;

    // Atualizar botões toggle
    document.querySelectorAll(".toggle-btn").forEach((btn) => {
      btn.classList.remove("active");
      if (btn.dataset.periodo === periodo) {
        btn.classList.add("active");
      }
    });

    // Re-renderizar planos
    this.renderizarPlanos();
  },

  /**
   * OBTER COR DO PLANO
   */
  obterCorPlano(nomePlano) {
    const cores = {
      GRATUITO: "#95a5a6",
      PRATA: "#c0392b",
      OURO: "#f39c12",
      DIAMANTE: "#2980b9",
    };
    return cores[nomePlano] || "#95a5a6";
  },

  /**
   * SELECIONAR PLANO E IR DIRETO PARA MERCADO PAGO
   */
  selecionarPlano(idPlano, nomePlano, preco) {
    // ✅ GARANTIR QUE PRECO É NÚMERO
    const precoNumerico = parseFloat(preco) || 0;

    console.log(
      `📋 Selecionado: ${nomePlano} - R$ ${precoNumerico.toFixed(2)} - ${
        this.periodoAtual
      }`
    );

    this.planoSelecionado = {
      id: idPlano,
      nome: nomePlano,
      preco: precoNumerico,
      periodo: this.periodoAtual,
    };

    // ✅ IR DIRETO PARA MERCADO PAGO (não abrir modal local)
    this.processarPagamentoMercadoPago();
  },

  /**
   * PROCESSAR PAGAMENTO DIRETO COM MERCADO PAGO
   */
  async processarPagamentoMercadoPago() {
    try {
      console.log("💳 Enviando ao Mercado Pago...");
      console.log("📋 Plano Selecionado:", this.planoSelecionado);

      const dados = {
        id_plano: this.planoSelecionado.id,
        tipo_ciclo: this.periodoAtual === "ano" ? "anual" : "mensal",
        modo_pagamento: this.modoPagementoSelecionado || "cartao",
      };

      console.log("📤 Enviando dados:", dados);

      const response = await fetch("processar-pagamento-final.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest",
        },
        credentials: "include",
        body: JSON.stringify(dados),
      });

      console.log("📬 Status HTTP:", response.status);

      if (!response.ok) {
        const text = await response.text();
        console.error("❌ Resposta não-OK:", text);
        throw new Error(`HTTP ${response.status}: ${text.substring(0, 100)}`);
      }

      // ✅ VERIFICAR CONTENT-TYPE
      const contentType = response.headers.get("content-type");
      console.log("📋 Content-Type:", contentType);

      if (!contentType || !contentType.includes("application/json")) {
        const text = await response.text();
        console.error("❌ Resposta não é JSON:", text.substring(0, 200));
        throw new Error("Servidor retornou resposta inválida (não é JSON)");
      }

      const result = await response.json();

      console.log("✅ Resposta JSON:", result);

      if (result.success) {
        console.log("✅ Sucesso ao processar pagamento!");
        console.log("   Método:", result.metodo || "api");
        console.log(
          "   URL:",
          result.preference_url || result.redirect_to || "N/A"
        );

        // ✅ REDIRECIONAR PARA MERCADO PAGO
        if (result.preference_url) {
          console.log("🔀 Redirecionando para init_point...");
          window.location.href = result.preference_url;
        } else if (result.redirect_to) {
          console.log("🔀 Redirecionando para checkout manual...");
          window.location.href = result.redirect_to;
        } else {
          throw new Error("Nenhuma URL de redirecionamento recebida");
        }
      } else {
        throw new Error(result.message || "Erro ao processar pagamento");
      }
    } catch (error) {
      console.error("❌ Erro ao processar pagamento:", error);
      console.error("📋 Stack:", error.stack);

      if (typeof ToastManager !== "undefined") {
        ToastManager.mostrar(`Erro: ${error.message}`, "erro");
      } else {
        alert(`Erro: ${error.message}`);
      }
    }
  },
  /**
   * ABRIR MODAL DE PLANOS
   */ abrirModalPlanos() {
    const modal = document.getElementById("modal-planos");
    if (modal) {
      modal.style.display = "flex";
      document.body.style.overflow = "hidden";
    }
  },

  /**
   * FECHAR MODAL DE PLANOS
   */
  fecharModalPlanos() {
    const modal = document.getElementById("modal-planos");
    if (modal) {
      modal.style.display = "none";
      document.body.style.overflow = "";
    }
  },

  /**
   * ABRIR MODAL DE PAGAMENTO
   */
  abrirModalPagamento() {
    const modalPlanos = document.getElementById("modal-planos");
    const modalPagamento = document.getElementById("modal-pagamento");

    if (modalPlanos) modalPlanos.style.display = "none";
    if (modalPagamento) modalPagamento.style.display = "flex";
  },

  /**
   * FECHAR MODAL DE PAGAMENTO
   */
  fecharModalPagamento() {
    const modal = document.getElementById("modal-pagamento");
    if (modal) {
      modal.style.display = "none";
      document.body.style.overflow = "";
    }
  },

  /**
   * VOLTAR PARA MODAL DE PLANOS
   */
  voltarParaPlanos() {
    const modalPlanos = document.getElementById("modal-planos");
    const modalPagamento = document.getElementById("modal-pagamento");

    if (modalPagamento) modalPagamento.style.display = "none";
    if (modalPlanos) modalPlanos.style.display = "flex";
  },

  /**
   * MUDAR ABA DE PAGAMENTO
   */
  mudarAba(abaName) {
    this.modoPagementoSelecionado = abaName;

    // Atualizar botões de abas
    document.querySelectorAll(".tab-btn").forEach((btn) => {
      btn.classList.remove("active");
      if (btn.dataset.tab === abaName) {
        btn.classList.add("active");
      }
    });

    // Atualizar conteúdo das abas
    document.querySelectorAll(".tab-content").forEach((content) => {
      content.classList.remove("active");
    });

    const contentDiv = document.getElementById(`tab-${abaName}`);
    if (contentDiv) {
      contentDiv.classList.add("active");

      // Carregar cartões salvos se abrir a aba
      if (abaName === "salvo") {
        this.carregarCartoesSalvos();
      }
    }
  },

  /**
   * PROCESSAR PAGAMENTO COM CARTÃO
   */
  async processarPagamentoCartao() {
    try {
      const form = document.getElementById("formCartao");
      if (!form.checkValidity()) {
        ToastManager?.mostrar("Preencha todos os campos obrigatórios", "erro");
        return;
      }

      const dados = {
        id_plano: this.planoSelecionado.id,
        periodo: this.planoSelecionado.periodo,
        modo_pagamento: "cartao",

        titular: document.getElementById("titular").value,
        numero_cartao: document
          .getElementById("numeroCartao")
          .value.replace(/\s/g, ""),
        validade: document.getElementById("dataValidade").value,
        cvv: document.getElementById("cvv").value,
        salvar_cartao: document.getElementById("salvarCartao").checked,
      };

      // Validar dados básicos
      if (!this.validarDadosCartao(dados)) {
        return;
      }

      // Enviar para servidor
      LoaderManager?.mostrar();

      const response = await fetch("processar-pagamento.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest",
        },
        credentials: "include",
        body: JSON.stringify(dados),
      });

      if (!response.ok) throw new Error(`HTTP ${response.status}`);

      const result = await response.json();

      if (result.success) {
        // Redirecionar para Mercado Pago
        window.location.href = result.preference_url;
      } else {
        throw new Error(result.message || "Erro ao processar pagamento");
      }
    } catch (error) {
      console.error("❌ Erro ao processar pagamento:", error);
      ToastManager?.mostrar(`Erro: ${error.message}`, "erro");
    } finally {
      LoaderManager?.ocultar();
    }
  },

  /**
   * PROCESSAR PAGAMENTO COM PIX
   */
  async processarPagamentoPIX(tipo) {
    try {
      const dados = {
        id_plano: this.planoSelecionado.id,
        periodo: this.planoSelecionado.periodo,
        modo_pagamento: "pix",
        tipo_pix: tipo,
      };

      LoaderManager?.mostrar();

      const response = await fetch("processar-pagamento.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest",
        },
        credentials: "include",
        body: JSON.stringify(dados),
      });

      if (!response.ok) throw new Error(`HTTP ${response.status}`);

      const result = await response.json();

      if (result.success) {
        // Redirecionar para Mercado Pago
        window.location.href = result.preference_url;
      } else {
        throw new Error(result.message || "Erro ao processar pagamento");
      }
    } catch (error) {
      console.error("❌ Erro ao processar PIX:", error);
      ToastManager?.mostrar(`Erro: ${error.message}`, "erro");
    } finally {
      LoaderManager?.ocultar();
    }
  },

  /**
   * CARREGAR CARTÕES SALVOS
   */
  async carregarCartoesSalvos() {
    try {
      const response = await fetch("obter-cartoes-salvos.php");
      if (!response.ok) throw new Error(`HTTP ${response.status}`);

      const data = await response.json();
      if (!data.success) throw new Error(data.message);

      const container = document.getElementById("cartoesSalvos");
      if (!container) return;

      if (data.cartoes.length === 0) {
        container.innerHTML =
          '<p style="text-align: center; color: #7f8c8d;">Nenhum cartão salvo</p>';
        return;
      }

      container.innerHTML = data.cartoes
        .map(
          (cartao) => `
                <div class="cartao-salvo-item">
                    <input type="radio" name="cartao-salvo" value="${
                      cartao.id
                    }" 
                           onchange="PlanoManager.selecionarCartaoSalvo(${
                             cartao.id
                           })">
                    <div class="cartao-info">
                        <div class="cartao-icone">
                            ${this.obterIconeBandeira(cartao.bandeira)}
                        </div>
                        <div>
                            <strong>${cartao.bandeira}</strong> • ••••• ${
            cartao.ultimos_digitos
          }<br>
                            <small style="color: #7f8c8d;">${
                              cartao.titulr_cartao
                            }</small>
                        </div>
                    </div>
                </div>
            `
        )
        .join("");

      // Adicionar botão de pagar com cartão salvo
      const btnPagar = document.createElement("button");
      btnPagar.type = "button";
      btnPagar.className = "btn-pagar";
      btnPagar.innerHTML = '<i class="fas fa-lock"></i> Confirmar Pagamento';
      btnPagar.onclick = () => this.processarPagamentoCartaoSalvo();

      container.appendChild(btnPagar);
    } catch (error) {
      console.error("❌ Erro ao carregar cartões:", error);
    }
  },

  /**
   * SELECIONAR CARTÃO SALVO
   */
  selecionarCartaoSalvo(idCartao) {
    this.cartaoSelecionado = idCartao;
  },

  /**
   * PROCESSAR PAGAMENTO COM CARTÃO SALVO
   */
  async processarPagamentoCartaoSalvo() {
    try {
      if (!this.cartaoSelecionado) {
        ToastManager?.mostrar("Selecione um cartão", "erro");
        return;
      }

      const dados = {
        id_plano: this.planoSelecionado.id,
        periodo: this.planoSelecionado.periodo,
        modo_pagamento: "cartao",
        id_cartao_salvo: this.cartaoSelecionado,
      };

      LoaderManager?.mostrar();

      const response = await fetch("processar-pagamento.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest",
        },
        credentials: "include",
        body: JSON.stringify(dados),
      });

      if (!response.ok) throw new Error(`HTTP ${response.status}`);

      const result = await response.json();

      if (result.success) {
        window.location.href = result.preference_url;
      } else {
        throw new Error(result.message || "Erro ao processar pagamento");
      }
    } catch (error) {
      console.error("❌ Erro ao processar pagamento:", error);
      ToastManager?.mostrar(`Erro: ${error.message}`, "erro");
    } finally {
      LoaderManager?.ocultar();
    }
  },

  /**
   * VALIDAR DADOS DO CARTÃO
   */
  validarDadosCartao(dados) {
    // Validar número do cartão
    if (!/^\d{13,19}$/.test(dados.numero_cartao.replace(/\s/g, ""))) {
      ToastManager?.mostrar("Número do cartão inválido", "erro");
      return false;
    }

    // Validar data de validade
    if (!/^\d{2}\/\d{2}$/.test(dados.validade)) {
      ToastManager?.mostrar("Data de validade inválida (MM/AA)", "erro");
      return false;
    }

    // Validar CVV
    if (!/^\d{3,4}$/.test(dados.cvv)) {
      ToastManager?.mostrar("CVV inválido", "erro");
      return false;
    }

    return true;
  },

  /**
   * OBTER ÍCONE DA BANDEIRA
   */
  obterIconeBandeira(bandeira) {
    const bandeiras = {
      visa: '<i class="fab fa-cc-visa"></i>',
      mastercard: '<i class="fab fa-cc-mastercard"></i>',
      amex: '<i class="fab fa-cc-amex"></i>',
      elo: '<i class="fas fa-credit-card"></i>',
      default: '<i class="fas fa-credit-card"></i>',
    };
    return bandeiras[bandeira?.toLowerCase()] || bandeiras["default"];
  },

  /**
   * VERIFICAR LIMITE DE MENTORES E EXIBIR MODAL SE NECESSÁRIO
   */
  async verificarEExibirPlanos(acao = "mentor") {
    try {
      // ✅ GARANTIR QUE PLANOS ESTÃO RENDERIZADOS ANTES DE ABRIR MODAL
      if (!this.planos || this.planos.length === 0) {
        console.log("⏳ Planos não carregados ainda, aguardando...");
        await this.carregarPlanos();
        this.renderizarPlanos();
      }

      const response = await fetch(`verificar-limite.php?acao=${acao}`);
      if (!response.ok) throw new Error(`HTTP ${response.status}`);

      const data = await response.json();

      if (!data.pode_prosseguir) {
        // ✅ MODAL ABRE COM PLANOS JÁ RENDERIZADOS
        this.abrirModalPlanos();

        if (data.mensagem) {
          ToastManager?.mostrar(data.mensagem, "aviso");
        }

        return false;
      }

      return true;
    } catch (error) {
      console.error("❌ Erro ao verificar limite:", error);
      return true; // Prosseguir se houver erro (fail-safe)
    }
  },
};

// ✅ INICIALIZAR QUANDO DOCUMENTO CARREGAR
document.addEventListener("DOMContentLoaded", () => {
  PlanoManager.inicializar();
});
