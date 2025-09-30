const CalculadoraModal = {
  // ✅ CONTROLE DE ESTADO
  calculandoAtualmente: false,
  banca_inicial: 0.0,
  lucro_atual: 0.0,
  tipoMetaSelecionado: "fixa",
  dadosCarregados: false,
  pollingInterval: null,

  // ✅ INICIALIZAR O SISTEMA
  async inicializar() {
    try {
      console.log("🚀 Inicializando Sistema Integrado...");
      await this.carregarDadosBanca();
      this.configurarEventosInputs();
      this.configurarEventosTipoMeta();
      this.integrarComSistemaAtualizacao();

      if (this.dadosCarregados) {
        this.calcularTodosValores();
      } else {
        console.warn("⚠️ Dados não carregados - exibindo valores zerados");
        this.exibirValoresZerados();
      }
      console.log("✅ Sistema Integrado inicializado!");
    } catch (error) {
      console.error("❌ Erro ao inicializar:", error);
      this.exibirValoresZerados();
    }
  },

  // ✅ INTEGRAR COM SISTEMA DE ATUALIZAÇÃO AUTOMÁTICA
  integrarComSistemaAtualizacao() {
    try {
      console.log("🔗 Integrando com sistema de atualização automática...");
      if (typeof window.executarAtualizacaoImediata === "function") {
        const funcaoOriginal = window.executarAtualizacaoImediata;
        window.executarAtualizacaoImediata = (
          tipoOperacao,
          resultado = null
        ) => {
          funcaoOriginal(tipoOperacao, resultado);
          console.log(`🧮 Atualizando calculadora após: ${tipoOperacao}`);
          setTimeout(() => this.recarregarDados(), 500);
        };
        console.log("✅ Função executarAtualizacaoImediata interceptada");
      }
      this.interceptarBotaoModal();
      this.escutarEventosCustomizados();
      this.iniciarPolling();
      console.log("✅ Integração completa configurada");
    } catch (error) {
      console.error("❌ Erro na integração:", error);
    }
  },

  interceptarBotaoModal() {
    try {
      document.addEventListener("click", (event) => {
        const target = event.target;
        const isModalBancaButton =
          target.id === "botaoAcao" ||
          ((target.type === "button" || target.type === "submit") &&
            target.closest("#modalDeposito")) ||
          target.closest(".modal-content");
        if (isModalBancaButton) {
          console.log("🎯 CLIQUE NO BOTÃO DO MODAL DETECTADO!");
          setTimeout(() => this.recarregarDados(), 800);
          setTimeout(() => this.recarregarDados(), 1500);
          setTimeout(() => this.recarregarDados(), 2500);
        }
      });
      console.log("✅ Interceptação do botão modal configurada");
    } catch (error) {
      console.error("❌ Erro ao interceptar botão modal:", error);
    }
  },

  escutarEventosCustomizados() {
    try {
      document.addEventListener("bancaAtualizada", () => {
        console.log("📢 Evento bancaAtualizada recebido");
        setTimeout(() => this.recarregarDados(), 200);
      });
      document.addEventListener("areaAtualizacao", (event) => {
        console.log("📢 Evento areaAtualizacao recebido", event.detail);
        setTimeout(() => this.recarregarDados(), 300);
      });
      document.addEventListener("mentorCadastrado", () => {
        console.log("📢 Evento mentorCadastrado recebido");
        setTimeout(() => this.recarregarDados(), 400);
      });
      console.log("✅ Eventos customizados configurados");
    } catch (error) {
      console.error("❌ Erro ao configurar eventos customizados:", error);
    }
  },

  iniciarPolling() {
    try {
      this.pollingInterval = setInterval(() => {
        if (this.dadosCarregados) this.verificarMudancasSilenciosa();
      }, 3000);
      console.log("⏰ Polling de backup iniciado");
    } catch (error) {
      console.error("❌ Erro ao iniciar polling:", error);
    }
  },

  async verificarMudancasSilenciosa() {
    try {
      const response = await fetch("dados_banca.php", {
        method: "GET",
        headers: {
          "Cache-Control": "no-cache",
          "X-Requested-With": "XMLHttpRequest",
        },
      });
      if (!response.ok) return;
      const data = await response.json();
      if (data.success) {
        const novaBanca = parseFloat(data.banca_inicial) || 0.0;
        const novoLucro = parseFloat(data.lucro_total_display) || 0.0;
        const mudancaBanca = Math.abs(novaBanca - this.banca_inicial) > 0.01;
        const mudancaLucro = Math.abs(novoLucro - this.lucro_atual) > 0.01;
        if (mudancaBanca || mudancaLucro) {
          console.log("🔄 MUDANÇA DETECTADA pelo polling");
          await this.recarregarDados();
        }
      }
    } catch (error) {}
  },

  async carregarDadosBanca() {
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
        this.banca_inicial = parseFloat(data.banca_inicial) || 0.0;
        this.lucro_atual = parseFloat(data.lucro_total_display) || 0.0;
        this.dadosCarregados = true;
        const valorBancaLabel = document.getElementById("valorBancaLabel");
        const valorLucroLabel = document.getElementById("valorLucroLabel");
        if (valorBancaLabel)
          valorBancaLabel.textContent = data.banca_formatada || "R$ 0,00";
        if (valorLucroLabel)
          valorLucroLabel.textContent = data.lucro_total_formatado || "R$ 0,00";
        console.log(
          `📊 Dados carregados - Banca: R$ ${this.banca_inicial.toFixed(
            2
          )}, Lucro: R$ ${this.lucro_atual.toFixed(2)}`
        );
      } else {
        console.warn("⚠️ Response não foi successful");
        this.exibirValoresZerados();
      }
    } catch (error) {
      console.error("❌ Erro ao carregar dados da banca:", error);
      this.banca_inicial = 0.0;
      this.lucro_atual = 0.0;
      this.dadosCarregados = false;
      this.exibirValoresZerados();
    }
  },

  exibirValoresZerados() {
    try {
      const valorBancaLabel = document.getElementById("valorBancaLabel");
      const valorLucroLabel = document.getElementById("valorLucroLabel");
      if (valorBancaLabel) valorBancaLabel.textContent = "R$ 0,00";
      if (valorLucroLabel) valorLucroLabel.textContent = "R$ 0,00";
      this.atualizarDisplays({
        unidadeEntrada: 0,
        metaDiaria: 0,
        metaMensal: 0,
        metaAnual: 0,
        entradasPositivas: 0,
      });
      console.log("💤 Valores zerados exibidos");
    } catch (error) {
      console.error("❌ Erro ao exibir valores zerados:", error);
    }
  },

  configurarEventosInputs() {
    try {
      const inputs = ["porcentagem", "unidadeMeta", "oddsMeta", "valorBanca"];
      inputs.forEach((inputId) => {
        const input = document.getElementById(inputId);
        if (input) {
          input.addEventListener("input", () => this.calcularTodosValores());
          input.addEventListener("change", () => this.calcularTodosValores());
          input.addEventListener("blur", () => this.calcularTodosValores());
          console.log(`✅ Eventos configurados para: ${inputId}`);
        } else {
          console.warn(`⚠️ Input não encontrado: ${inputId}`);
        }
      });
    } catch (error) {
      console.error("❌ Erro ao configurar eventos dos inputs:", error);
    }
  },

  configurarEventosTipoMeta() {
    try {
      const radioFixa = document.getElementById("metaFixa");
      const radioTurbo = document.getElementById("metaTurbo");
      if (radioFixa) {
        radioFixa.addEventListener("change", () => {
          if (radioFixa.checked) {
            this.tipoMetaSelecionado = "fixa";
            this.aplicarEstiloMetaFixa();
            this.calcularTodosValores();
          }
        });
      }
      if (radioTurbo) {
        radioTurbo.addEventListener("change", () => {
          if (radioTurbo.checked) {
            this.tipoMetaSelecionado = "turbo";
            this.aplicarEstiloMetaTurbo();
            this.calcularTodosValores();
          }
        });
      }
      if (radioFixa && radioFixa.checked) {
        this.tipoMetaSelecionado = "fixa";
        this.aplicarEstiloMetaFixa();
      } else if (radioTurbo && radioTurbo.checked) {
        this.tipoMetaSelecionado = "turbo";
        this.aplicarEstiloMetaTurbo();
      }
      console.log(`✅ Tipo de meta inicial: ${this.tipoMetaSelecionado}`);
    } catch (error) {
      console.error("❌ Erro ao configurar eventos tipo de meta:", error);
    }
  },

  aplicarEstiloMetaFixa() {
    try {
      console.log("🔵 Aplicando estilo Meta Fixa (Azul)");
      const resultadoValores = document.querySelectorAll(".resultado-valor");
      resultadoValores.forEach((elemento) => {
        elemento.style.color = "#2196F3";
        elemento.style.fontWeight = "bold";
        elemento.style.transition = "color 0.3s ease";
      });
      const opcaoFixa = document
        .querySelector("#metaFixa")
        ?.closest(".opcao-meta");
      const opcaoTurbo = document
        .querySelector("#metaTurbo")
        ?.closest(".opcao-meta");
      if (opcaoFixa) {
        opcaoFixa.style.backgroundColor = "#E3F2FD";
        opcaoFixa.style.border = "2px solid #2196F3";
        opcaoFixa.style.borderRadius = "8px";
        opcaoFixa.style.padding = "10px";
        opcaoFixa.style.transition = "all 0.3s ease";
      }
      if (opcaoTurbo) {
        opcaoTurbo.style.backgroundColor = "transparent";
        opcaoTurbo.style.border = "1px solid #ddd";
        opcaoTurbo.style.borderRadius = "8px";
        opcaoTurbo.style.padding = "10px";
      }
      const tituloResultados = document.querySelector(".titulo-resultados");
      if (tituloResultados) {
        tituloResultados.style.color = "#2196F3";
        tituloResultados.style.transition = "color 0.3s ease";
      }
      console.log("✅ Estilo Meta Fixa aplicado");
    } catch (error) {
      console.error("❌ Erro ao aplicar estilo Meta Fixa:", error);
    }
  },

  aplicarEstiloMetaTurbo() {
    try {
      console.log("🟠 Aplicando estilo Meta Turbo (Laranja)");
      const resultadoValores = document.querySelectorAll(".resultado-valor");
      resultadoValores.forEach((elemento) => {
        elemento.style.color = "#FF9800";
        elemento.style.fontWeight = "bold";
        elemento.style.transition = "color 0.3s ease";
      });
      const opcaoFixa = document
        .querySelector("#metaFixa")
        ?.closest(".opcao-meta");
      const opcaoTurbo = document
        .querySelector("#metaTurbo")
        ?.closest(".opcao-meta");
      if (opcaoTurbo) {
        opcaoTurbo.style.backgroundColor = "#FFF3E0";
        opcaoTurbo.style.border = "2px solid #FF9800";
        opcaoTurbo.style.borderRadius = "8px";
        opcaoTurbo.style.padding = "10px";
        opcaoTurbo.style.transition = "all 0.3s ease";
      }
      if (opcaoFixa) {
        opcaoFixa.style.backgroundColor = "transparent";
        opcaoFixa.style.border = "1px solid #ddd";
        opcaoFixa.style.borderRadius = "8px";
        opcaoFixa.style.padding = "10px";
      }
      const tituloResultados = document.querySelector(".titulo-resultados");
      if (tituloResultados) {
        tituloResultados.style.color = "#FF9800";
        tituloResultados.style.transition = "color 0.3s ease";
      }
      console.log("✅ Estilo Meta Turbo aplicado");
    } catch (error) {
      console.error("❌ Erro ao aplicar estilo Meta Turbo:", error);
    }
  },

  obterValoresInputs() {
    try {
      const inputPorcentagem = document.getElementById("porcentagem");
      let porcentagem = 0;
      if (inputPorcentagem && inputPorcentagem.value) {
        const valorLimpo = inputPorcentagem.value
          .replace(/[^\d.,]/g, "")
          .replace(",", ".");
        porcentagem = parseFloat(valorLimpo) || 0;
      }
      const inputUnidade = document.getElementById("unidadeMeta");
      let unidade = 0;
      if (inputUnidade && inputUnidade.value) {
        unidade = parseInt(inputUnidade.value) || 0;
      }
      const inputOdds = document.getElementById("oddsMeta");
      let odds = 0;
      if (inputOdds && inputOdds.value) {
        const valorLimpo = inputOdds.value.replace(",", ".");
        odds = parseFloat(valorLimpo) || 0;
      }
      if (porcentagem <= 0 || unidade <= 0 || odds <= 0) {
        console.log("⚠️ Inputs vazios ou inválidos");
        return { porcentagem: 0, unidade: 0, odds: 0 };
      }
      return { porcentagem, unidade, odds };
    } catch (error) {
      console.error("❌ Erro ao obter valores dos inputs:", error);
      return { porcentagem: 0, unidade: 0, odds: 0 };
    }
  },

  obterValorDigitado() {
    try {
      const inputValorBanca = document.getElementById("valorBanca");
      if (inputValorBanca && inputValorBanca.value) {
        const valorLimpo = inputValorBanca.value.replace(/[^\d]/g, "");
        return parseFloat(valorLimpo) / 100;
      }
      return 0;
    } catch (error) {
      console.error("❌ Erro ao obter valor digitado:", error);
      return 0;
    }
  },

  calcularUnidadeEntrada(valores) {
    try {
      if (
        !this.dadosCarregados ||
        this.banca_inicial <= 0 ||
        valores.porcentagem <= 0
      ) {
        return 0;
      }
      const valorDigitado = this.obterValorDigitado();
      const bancaTotal = this.banca_inicial + this.lucro_atual + valorDigitado;
      const porcentagemDecimal = valores.porcentagem / 100;
      const unidadeEntrada = bancaTotal * porcentagemDecimal;
      console.log(`💰 UNIDADE DE ENTRADA:`);
      console.log(`   Banca Inicial: R$ ${this.banca_inicial.toFixed(2)}`);
      console.log(`   Lucro Atual: R$ ${this.lucro_atual.toFixed(2)}`);
      console.log(`   Valor Digitado: R$ ${valorDigitado.toFixed(2)}`);
      console.log(
        `   Banca Total: ${this.banca_inicial} + ${
          this.lucro_atual
        } + ${valorDigitado} = ${bancaTotal.toFixed(2)}`
      );
      console.log(`   Porcentagem: ${valores.porcentagem}%`);
      console.log(`   ✅ Unidade: R$ ${unidadeEntrada.toFixed(2)}`);
      return unidadeEntrada;
    } catch (error) {
      console.error("❌ Erro ao calcular unidade de entrada:", error);
      return 0;
    }
  },

  calcularMetaDiaria(valores) {
    try {
      if (
        !this.dadosCarregados ||
        this.banca_inicial <= 0 ||
        valores.porcentagem <= 0 ||
        valores.unidade <= 0
      ) {
        return 0;
      }
      const porcentagemDecimal = valores.porcentagem / 100;
      const valorDigitado = this.obterValorDigitado();
      let bancaParaCalculo = 0;
      console.log(`\n📊 CALCULANDO META DIÁRIA:`);
      console.log(`   Tipo Meta: ${this.tipoMetaSelecionado}`);
      console.log(`   Banca Inicial: R$ ${this.banca_inicial.toFixed(2)}`);
      console.log(`   Lucro Atual: R$ ${this.lucro_atual.toFixed(2)}`);
      console.log(`   Valor Digitado: R$ ${valorDigitado.toFixed(2)}`);
      if (this.tipoMetaSelecionado === "fixa") {
        bancaParaCalculo =
          this.banca_inicial - this.lucro_atual + valorDigitado;
        console.log(
          `   🔵 META FIXA: ${this.banca_inicial} - ${
            this.lucro_atual
          } + ${valorDigitado} = ${bancaParaCalculo.toFixed(2)}`
        );
      } else {
        if (this.lucro_atual > 0) {
          bancaParaCalculo =
            this.banca_inicial + this.lucro_atual + valorDigitado;
          console.log(
            `   🟢 META TURBO (Lucro +): ${this.banca_inicial} + ${
              this.lucro_atual
            } + ${valorDigitado} = ${bancaParaCalculo.toFixed(2)}`
          );
        } else {
          bancaParaCalculo =
            this.banca_inicial - Math.abs(this.lucro_atual) + valorDigitado;
          console.log(
            `   🔴 META TURBO (Lucro -/0): ${this.banca_inicial} - ${Math.abs(
              this.lucro_atual
            )} + ${valorDigitado} = ${bancaParaCalculo.toFixed(2)}`
          );
        }
      }
      bancaParaCalculo = Math.max(0, bancaParaCalculo);
      const metaDiaria =
        bancaParaCalculo * porcentagemDecimal * valores.unidade;
      console.log(`   Porcentagem: ${valores.porcentagem}%`);
      console.log(`   Unidade: ${valores.unidade}`);
      console.log(`   ✅ Meta Diária: R$ ${metaDiaria.toFixed(2)}\n`);
      return metaDiaria;
    } catch (error) {
      console.error("❌ Erro ao calcular meta diária:", error);
      return 0;
    }
  },

  calcularDiasRestantes() {
    try {
      const hoje = new Date();
      const ultimoDiaMes = new Date(
        hoje.getFullYear(),
        hoje.getMonth() + 1,
        0
      ).getDate();
      const diaAtual = hoje.getDate();
      const diasRestantesMes = ultimoDiaMes - diaAtual + 1;
      const fimAno = new Date(hoje.getFullYear(), 11, 31);
      const diferenca = Math.ceil((fimAno - hoje) / (1000 * 60 * 60 * 24)) + 1;
      return { mes: diasRestantesMes, ano: diferenca };
    } catch (error) {
      return { mes: 30, ano: 365 };
    }
  },

  calcularMetasPeriodo(metaDiaria, valores) {
    try {
      const diasRestantes = this.calcularDiasRestantes();
      const metaMensal = metaDiaria * diasRestantes.mes;
      const metaAnual = metaDiaria * diasRestantes.ano;
      console.log(`📅 METAS DE PERÍODO:`);
      console.log(`   Dias restantes no mês: ${diasRestantes.mes}`);
      console.log(`   Meta Mensal: R$ ${metaMensal.toFixed(2)}`);
      console.log(`   Dias restantes no ano: ${diasRestantes.ano}`);
      console.log(`   Meta Anual: R$ ${metaAnual.toFixed(2)}`);
      return {
        metaMensal,
        metaAnual,
        diasMes: diasRestantes.mes,
        diasAno: diasRestantes.ano,
      };
    } catch (error) {
      console.error("❌ Erro ao calcular metas de período:", error);
      return { metaMensal: 0, metaAnual: 0, diasMes: 30, diasAno: 365 };
    }
  },

  calcularEntradasPositivas(valores, metaDiaria) {
    try {
      if (
        !this.dadosCarregados ||
        this.banca_inicial <= 0 ||
        metaDiaria <= 0 ||
        valores.porcentagem <= 0 ||
        valores.unidade <= 0 ||
        valores.odds <= 0
      ) {
        return 0;
      }
      const unidadeEntrada = this.calcularUnidadeEntrada(valores);
      if (unidadeEntrada <= 0) return 0;
      const lucroPorEntrada = unidadeEntrada * valores.odds - unidadeEntrada;
      if (lucroPorEntrada <= 0) return 0;
      const entradasNecessarias = Math.ceil(metaDiaria / lucroPorEntrada);
      return entradasNecessarias;
    } catch (error) {
      return 0;
    }
  },

  atualizarDisplays(resultados) {
    try {
      const elementos = {
        resultadoUnidadeEntrada: resultados.unidadeEntrada,
        resultadoMetaDia: resultados.metaDiaria,
        resultadoMetaMes: resultados.metaMensal,
        resultadoMetaAno: resultados.metaAnual,
      };
      Object.keys(elementos).forEach((id) => {
        const elemento = document.getElementById(id);
        if (elemento) elemento.textContent = this.formatarMoeda(elementos[id]);
      });
      const resultadoEntradas = document.getElementById("resultadoEntradas");
      if (resultadoEntradas) {
        const textoEntradas =
          resultados.entradasPositivas === 1
            ? "1 Entrada Positiva"
            : `${resultados.entradasPositivas} Entradas Positivas`;
        resultadoEntradas.textContent = textoEntradas;
      }
      setTimeout(() => {
        if (this.tipoMetaSelecionado === "turbo") {
          this.aplicarEstiloMetaTurbo();
        } else {
          this.aplicarEstiloMetaFixa();
        }
      }, 100);
      console.log("✅ Displays atualizados no modal com cores aplicadas");
    } catch (error) {
      console.error("❌ Erro ao atualizar displays:", error);
    }
  },

  calcularTodosValores() {
    if (this.calculandoAtualmente) return;
    this.calculandoAtualmente = true;
    try {
      if (!this.dadosCarregados || this.banca_inicial <= 0) {
        this.exibirValoresZerados();
        return;
      }
      const valores = this.obterValoresInputs();
      if (
        valores.porcentagem <= 0 ||
        valores.unidade <= 0 ||
        valores.odds <= 0
      ) {
        this.exibirValoresZerados();
        return;
      }
      const unidadeEntrada = this.calcularUnidadeEntrada(valores);
      const metaDiaria = this.calcularMetaDiaria(valores);
      const metasPeriodo = this.calcularMetasPeriodo(metaDiaria, valores);
      const entradasPositivas = this.calcularEntradasPositivas(
        valores,
        metaDiaria
      );
      const resultados = {
        unidadeEntrada,
        metaDiaria,
        metaMensal: metasPeriodo.metaMensal,
        metaAnual: metasPeriodo.metaAnual,
        entradasPositivas,
      };
      this.atualizarDisplays(resultados);
      console.log("📊 Cálculos realizados:", {
        inputs: valores,
        tipoMeta: this.tipoMetaSelecionado,
        bancaInicial: this.banca_inicial,
        lucroAtual: this.lucro_atual,
        resultados,
      });
    } catch (error) {
      console.error("❌ Erro nos cálculos:", error);
      this.exibirValoresZerados();
    } finally {
      this.calculandoAtualmente = false;
    }
  },

  formatarMoeda(valor) {
    try {
      return valor.toLocaleString("pt-BR", {
        style: "currency",
        currency: "BRL",
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      });
    } catch (error) {
      return "R$ 0,00";
    }
  },

  async recarregarDados() {
    try {
      console.log("🔄 Recarregando dados da calculadora...");
      this.dadosCarregados = false;
      this.banca_inicial = 0.0;
      this.lucro_atual = 0.0;
      await this.carregarDadosBanca();
      if (this.dadosCarregados) {
        this.calcularTodosValores();
        console.log("✅ Calculadora atualizada com sucesso!");
      } else {
        this.exibirValoresZerados();
        console.log("⚠️ Não foi possível recarregar os dados");
      }
      return this.dadosCarregados;
    } catch (error) {
      console.error("❌ Erro ao recarregar dados:", error);
      this.exibirValoresZerados();
      return false;
    }
  },

  alternarTipoMeta(tipo = null) {
    try {
      if (tipo === null)
        tipo = this.tipoMetaSelecionado === "fixa" ? "turbo" : "fixa";
      const radioFixa = document.getElementById("metaFixa");
      const radioTurbo = document.getElementById("metaTurbo");
      if (tipo === "fixa" && radioFixa) {
        radioFixa.checked = true;
        this.tipoMetaSelecionado = "fixa";
        this.aplicarEstiloMetaFixa();
      } else if (tipo === "turbo" && radioTurbo) {
        radioTurbo.checked = true;
        this.tipoMetaSelecionado = "turbo";
        this.aplicarEstiloMetaTurbo();
      }
      this.calcularTodosValores();
      console.log(`🔄 Tipo de meta alterado para: ${this.tipoMetaSelecionado}`);
      return `✅ Tipo alterado para: ${this.tipoMetaSelecionado}`;
    } catch (error) {
      console.error("❌ Erro ao alternar tipo de meta:", error);
      return "❌ Erro ao alternar tipo!";
    }
  },

  simularCenarios() {
    console.log("🧪 SIMULANDO CENÁRIOS:");
    console.log("===================================================");
    const lucroOriginal = this.lucro_atual;
    const tipoOriginal = this.tipoMetaSelecionado;
    const valores = { porcentagem: 2, unidade: 2, odds: 1.7 };
    console.log(
      `📋 PARÂMETROS: Porcentagem: ${valores.porcentagem}%, Unidade: ${valores.unidade}, Odds: ${valores.odds}`
    );
    console.log(`   Banca Inicial: R$ ${this.banca_inicial.toFixed(2)}\n`);

    this.lucro_atual = 0;
    console.log("📊 CENÁRIO 1 - NEUTRO:");
    const meta1 = this.calcularMetaDiaria(valores);
    const metas1 = this.calcularMetasPeriodo(meta1, valores);
    console.log(
      `   Meta Diária: R$ ${meta1.toFixed(
        2
      )}, Mensal: R$ ${metas1.metaMensal.toFixed(2)}\n`
    );

    this.lucro_atual = -50;
    console.log("📊 CENÁRIO 2 - PREJUÍZO R$ 50:");
    const meta2 = this.calcularMetaDiaria(valores);
    const metas2 = this.calcularMetasPeriodo(meta2, valores);
    console.log(
      `   Meta Diária: R$ ${meta2.toFixed(
        2
      )}, Mensal: R$ ${metas2.metaMensal.toFixed(2)}\n`
    );

    this.lucro_atual = 80;
    this.tipoMetaSelecionado = "fixa";
    console.log("📊 CENÁRIO 3 - LUCRO R$ 80 + META FIXA:");
    const meta3 = this.calcularMetaDiaria(valores);
    const metas3 = this.calcularMetasPeriodo(meta3, valores);
    console.log(
      `   Meta Diária: R$ ${meta3.toFixed(
        2
      )}, Mensal: R$ ${metas3.metaMensal.toFixed(2)}\n`
    );

    this.tipoMetaSelecionado = "turbo";
    console.log("📊 CENÁRIO 4 - LUCRO R$ 80 + META TURBO:");
    const meta4 = this.calcularMetaDiaria(valores);
    const metas4 = this.calcularMetasPeriodo(meta4, valores);
    console.log(
      `   Meta Diária: R$ ${meta4.toFixed(
        2
      )}, Mensal: R$ ${metas4.metaMensal.toFixed(2)}\n`
    );

    this.lucro_atual = -50;
    console.log("📊 CENÁRIO 5 - PREJUÍZO R$ 50 + META TURBO:");
    const meta5 = this.calcularMetaDiaria(valores);
    const metas5 = this.calcularMetasPeriodo(meta5, valores);
    console.log(
      `   Meta Diária: R$ ${meta5.toFixed(
        2
      )}, Mensal: R$ ${metas5.metaMensal.toFixed(2)}\n`
    );

    this.lucro_atual = lucroOriginal;
    this.tipoMetaSelecionado = tipoOriginal;
    console.log("✅ SIMULAÇÃO COMPLETA!");
    console.log("===================================================");
    return {
      neutro: { diaria: meta1, mensal: metas1.metaMensal },
      prejuizo: { diaria: meta2, mensal: metas2.metaMensal },
      lucroFixa: { diaria: meta3, mensal: metas3.metaMensal },
      lucroTurbo: { diaria: meta4, mensal: metas4.metaMensal },
      prejuizoTurbo: { diaria: meta5, mensal: metas5.metaMensal },
    };
  },

  pararPolling() {
    if (this.pollingInterval) {
      clearInterval(this.pollingInterval);
      this.pollingInterval = null;
      console.log("⏹️ Polling parado");
    }
  },
};

// ========================================
// 🎮 ATALHOS GLOBAIS (VERSÃO ÚNICA)
// ========================================

window.calc = {
  init: () => CalculadoraModal.inicializar(),
  reload: () => CalculadoraModal.recarregarDados(),
  fixa: () => CalculadoraModal.alternarTipoMeta("fixa"),
  turbo: () => CalculadoraModal.alternarTipoMeta("turbo"),
  toggle: () => CalculadoraModal.alternarTipoMeta(),
  recalc: () => CalculadoraModal.calcularTodosValores(),
  parar: () => CalculadoraModal.pararPolling(),
  simular: () => CalculadoraModal.simularCenarios(),

  status: () => {
    console.log("📊 STATUS ATUAL:");
    console.log(`   Dados Carregados: ${CalculadoraModal.dadosCarregados}`);
    console.log(
      `   Banca Inicial: R$ ${CalculadoraModal.banca_inicial.toFixed(2)}`
    );
    console.log(
      `   Lucro Atual: R$ ${CalculadoraModal.lucro_atual.toFixed(2)}`
    );
    console.log(`   Tipo Meta: ${CalculadoraModal.tipoMetaSelecionado}`);
    const valores = CalculadoraModal.obterValoresInputs();
    const valorDigitado = CalculadoraModal.obterValorDigitado();
    console.log("📝 INPUTS ATUAIS:");
    console.log(`   Porcentagem: ${valores.porcentagem}%`);
    console.log(`   Unidade: ${valores.unidade}`);
    console.log(`   Odds: ${valores.odds}`);
    console.log(`   Valor Digitado: R$ ${valorDigitado.toFixed(2)}`);
    return "✅ Status exibido";
  },

  cores: () => {
    if (CalculadoraModal.tipoMetaSelecionado === "turbo") {
      CalculadoraModal.aplicarEstiloMetaTurbo();
    } else {
      CalculadoraModal.aplicarEstiloMetaFixa();
    }
    console.log(`🎨 Cores aplicadas: ${CalculadoraModal.tipoMetaSelecionado}`);
    return "✅ Cores aplicadas";
  },

  testar: () => {
    console.log("🧪 TESTANDO INTEGRAÇÃO:");
    document.dispatchEvent(
      new CustomEvent("bancaAtualizada", { detail: { teste: true } })
    );
    console.log("📢 Evento bancaAtualizada disparado");
    return "🧪 Teste executado";
  },

  testarPrejuizo: (valor = 50) => {
    console.log(`🧪 TESTANDO CENÁRIO COM PREJUÍZO DE R$ ${valor.toFixed(2)}:`);
    console.log("===============================================");
    const lucroOriginal = CalculadoraModal.lucro_atual;
    CalculadoraModal.lucro_atual = -Math.abs(valor);
    const valores = { porcentagem: 2, unidade: 2, odds: 1.7 };
    const metaDiaria = CalculadoraModal.calcularMetaDiaria(valores);
    const metas = CalculadoraModal.calcularMetasPeriodo(metaDiaria, valores);
    console.log(`📊 RESULTADO:`);
    console.log(`   Prejuízo: R$ ${CalculadoraModal.lucro_atual.toFixed(2)}`);
    console.log(
      `   Meta Diária (com recuperação): R$ ${metaDiaria.toFixed(2)}`
    );
    console.log(`   Meta Mensal: R$ ${metas.metaMensal.toFixed(2)}`);
    console.log(`   Meta Anual: R$ ${metas.metaAnual.toFixed(2)}`);
    CalculadoraModal.lucro_atual = lucroOriginal;
    console.log(`✅ Valor original restaurado: R$ ${lucroOriginal.toFixed(2)}`);
    return `✅ Teste com prejuízo de R$ ${valor.toFixed(2)} concluído`;
  },

  explicar: () => {
    console.log("📚 EXPLICAÇÃO DA LÓGICA:");
    console.log("=====================================");
    console.log("🎯 META DIÁRIA:");
    console.log("   🔵 META FIXA: Banca Inicial - Lucro + Digitado");
    console.log("   🟠 META TURBO (Lucro +): Banca Inicial + Lucro + Digitado");
    console.log(
      "   🔴 META TURBO (Lucro -/0): Banca Inicial - |Lucro| + Digitado"
    );
    console.log("");
    console.log("💰 UNIDADE DE ENTRADA:");
    console.log(
      "   Sempre: Banca Total (Inicial + Lucro + Digitado) × Porcentagem"
    );
    console.log("");
    console.log("📅 METAS DE PERÍODO:");
    console.log("   Meta Mensal = Meta Diária × Dias Restantes no Mês");
    console.log("   Meta Anual = Meta Diária × Dias Restantes no Ano");
    return "✅ Explicação exibida";
  },
};

// ========================================
// ⚡ INICIALIZAÇÃO AUTOMÁTICA
// ========================================

document.addEventListener("DOMContentLoaded", function () {
  setTimeout(() => {
    try {
      CalculadoraModal.inicializar();
    } catch (error) {
      console.error("❌ Erro na inicialização automática:", error);
    }
  }, 1500);
});

// ========================================
// 📱 LOGS DE INICIALIZAÇÃO
// ========================================

console.log("✅ Sistema de Calculadora Modal Carregado!");
console.log("📱 Comandos disponíveis:");
console.log("  calc.init() - Inicializar");
console.log("  calc.reload() - Recarregar dados");
console.log("  calc.status() - Ver status atual");
console.log("  calc.simular() - Simular cenários");
console.log("  calc.fixa() - Meta Fixa (Azul)");
console.log("  calc.turbo() - Meta Turbo (Laranja)");
console.log("  calc.toggle() - Alternar tipo");
console.log("  calc.recalc() - Recalcular");
console.log("  calc.cores() - Aplicar cores");
console.log("  calc.parar() - Parar polling");
console.log("  calc.testar() - Testar integração");
console.log("  calc.testarPrejuizo(50) - Testar prejuízo");
console.log("  calc.explicar() - Explicação da lógica");
console.log("");
console.log("💡 LÓGICA IMPLEMENTADA:");
console.log("   🔵 UNIDADE: Sempre Banca Total (Inicial + Lucro + Digitado)");
console.log("   🔵 META FIXA: Banca Inicial - Lucro + Digitado");
console.log("   🟠 META TURBO (Lucro +): Banca Inicial + Lucro + Digitado");
console.log("   🔴 META TURBO (Lucro -/0): Banca Inicial - |Lucro| + Digitado");

window.CalculadoraModal = CalculadoraModal;
