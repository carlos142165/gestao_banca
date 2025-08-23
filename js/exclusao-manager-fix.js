// ExclusaoManager improved implementation with modal fixes
const ExclusaoManager = {
  // State to prevent multiple operations
  isDeleting: false,
  modalAberto: false,

  // Helper for JSON parsing with validation
  async parseJSON(response) {
    try {
      const text = await response.text();
      try {
        return JSON.parse(text);
      } catch (e) {
        console.error("Invalid JSON response:", text);
        throw new Error(
          "Resposta inválida do servidor. Por favor, tente novamente."
        );
      }
    } catch (e) {
      throw new Error("Erro ao ler resposta do servidor");
    }
  },

  async excluirMentor(id, nome) {
    if (!id) {
      ToastManager.mostrar("❌ ID do mentor não encontrado", "erro");
      return;
    }

    if (this.isDeleting) {
      ToastManager.mostrar("⏳ Uma exclusão já está em andamento", "aviso");
      return;
    }

    try {
      this.isDeleting = true;

      // Mostra modal de confirmação
      const confirmacao = await this.confirmarExclusaoModal(nome);
      if (!confirmacao) {
        this.isDeleting = false;
        return;
      }

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
        throw new Error(`Erro na requisição: ${response.status}`);
      }

      // Validate and parse JSON carefully
      const resultado = await this.parseJSON(response);

      if (resultado.success) {
        ToastManager.mostrar("✅ Mentor excluído com sucesso!", "sucesso");

        // Anima remoção do card e atualiza dados
        const card = document.querySelector(`[data-id='${id}']`);
        if (card) {
          card.style.animation = "slideOutAndFade 0.3s ease-out forwards";

          // Aguarda animação e atualiza tudo
          setTimeout(async () => {
            try {
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
            } catch (err) {
              console.error("Erro ao atualizar dados após exclusão:", err);
              ToastManager.mostrar(
                "⚠️ Mentor excluído, mas houve erro ao atualizar alguns dados",
                "aviso"
              );
            }
          }, 400);
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
      this.isDeleting = false; // Reset deletion state
    }
  },

  async excluirEntrada(idEntrada) {
    if (this.isDeleting) {
      ToastManager.mostrar("⏳ Uma exclusão já está em andamento", "aviso");
      return;
    }

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
          this.isDeleting = false;
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

  async executarExclusaoEntrada(idEntrada) {
    if (this.isDeleting) return;

    const idMentorBackup = MentorManager.mentorAtualId;
    const tela = document.getElementById("tela-edicao");
    const estaAberta = tela?.style.display === "block";

    try {
      this.isDeleting = true;
      LoaderManager.mostrar();

      const response = await fetch("excluir-entrada.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `id=${encodeURIComponent(idEntrada)}`,
      });

      if (!response.ok) {
        throw new Error(`Erro na requisição: ${response.status}`);
      }

      const mensagem = await response.text();
      const sucesso = mensagem.toLowerCase().includes("sucesso");

      if (sucesso) {
        await this.atualizarAposExclusao();
        TelaEdicaoManager.fechar();

        ToastManager.mostrar(mensagem.trim(), "sucesso");

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
      } else {
        throw new Error(mensagem || "Erro ao excluir entrada");
      }
    } catch (error) {
      console.error("Erro ao excluir entrada:", error);
      ToastManager.mostrar(`❌ ${error.message}`, "erro");
    } finally {
      LoaderManager.ocultar();
      this.isDeleting = false;
    }
  },

  async atualizarAposExclusao() {
    try {
      // Run updates in parallel for better performance
      await Promise.all([
        fetch("carregar-sessao.php?atualizar=1"),
        MentorManager.recarregarMentores(),
        DadosManager.atualizarLucroEBancaViaAjax(),
      ]);

      // Update tela edição if open
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

      // Update meta if exists
      if (typeof MetaDiariaManager !== "undefined") {
        setTimeout(() => {
          MetaDiariaManager.atualizarMetaDiaria();
        }, 100);
      }
    } catch (error) {
      console.error("Erro ao atualizar após exclusão:", error);
      throw new Error("Erro ao atualizar dados após exclusão");
    }
  },

  confirmarExclusaoModal(nome) {
    return new Promise((resolve) => {
      if (this.modalAberto) {
        resolve(false);
        return;
      }

      const modal = document.getElementById("modal-confirmacao-exclusao");
      if (!modal) {
        resolve(false);
        return;
      }

      this.modalAberto = true;

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

      const handleConfirmar = async () => {
        modal.style.display = "none"; // Fecha imediatamente
        resolve(true);
      };

      const handleCancelar = () => {
        modal.style.display = "none"; // Fecha imediatamente
        resolve(false);
      };

      // Remove listeners antigos se existirem
      btnConfirmar?.removeEventListener("click", handleConfirmar);
      btnCancelar?.removeEventListener("click", handleCancelar);

      // Adiciona novos listeners
      btnConfirmar?.addEventListener("click", handleConfirmar);
      btnCancelar?.addEventListener("click", handleCancelar);

      // Mostra o modal
      modal.style.display = "flex";
    });
  },
};

// Export para uso global
window.ExclusaoManager = ExclusaoManager;
