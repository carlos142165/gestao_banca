// 🎉 TESTE DO MODAL DE CELEBRAÇÃO - META BATIDA

/**
 * Função para testar o modal manualmente
 * Execute no console: testarModalCelebracao()
 */
window.testarModalCelebracao = function () {
  console.log("🧪 Iniciando teste do modal de celebração...");

  // Simula dados de meta batida
  const dadosTeste = {
    lucro: 550.0,
    meta_display: 500.0,
    meta_diaria: 500.0,
    periodo_ativo: "dia",
    banca: 1000.0,
  };

  // Mostra o modal
  const modal = document.getElementById("modal-meta-batida");
  if (!modal) {
    console.error("❌ Modal não encontrado no DOM");
    return;
  }

  console.log("✅ Modal encontrado");
  console.log("📊 Dados de teste:", dadosTeste);

  // Preenche com valores de teste
  document.getElementById("valor-meta-modal").textContent = "R$ 500,00";
  document.getElementById("valor-lucro-modal").textContent = "R$ 550,00";
  document.getElementById("valor-extra-modal").textContent = "R$ 50,00";

  // Mostra o modal
  modal.style.display = "flex";

  console.log("✅ Modal exibido com sucesso!");
  console.log(
    '📱 Clique no botão "Entendi, vou parar de jogar 💪" para fechar'
  );
  console.log("");
  console.log("Mais comandos:");
  console.log("  - testarSomCelebracao() - Toca o som");
  console.log("  - resetarModalTeste() - Reseta o estado");
  console.log("  - verificarEstadoModal() - Verifica o estado");
};

/**
 * Testa apenas o som de celebração
 */
window.testarSomCelebracao = function () {
  console.log("🔊 Testando som de celebração...");

  try {
    const audioContext = new (window.AudioContext ||
      window.webkitAudioContext)();
    const agora = audioContext.currentTime;

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

    console.log("✅ Som tocado com sucesso!");
  } catch (error) {
    console.error("❌ Erro ao tocar som:", error);
  }
};

/**
 * Reseta o estado para testes
 */
window.resetarModalTeste = function () {
  console.log("🔄 Resetando estado do modal...");

  // Fecha o modal
  const modal = document.getElementById("modal-meta-batida");
  if (modal) {
    modal.style.display = "none";
  }

  // Reseta a flag
  if (typeof CelebracaoMetaManager !== "undefined") {
    CelebracaoMetaManager.jaMostradoHoje = false;
  }

  // Remove do localStorage
  localStorage.removeItem("ultimaDataCelebracao");

  console.log("✅ Estado resetado");
  console.log(
    "📝 Agora você pode testar novamente com testarModalCelebracao()"
  );
};

/**
 * Verifica o estado atual do sistema
 */
window.verificarEstadoModal = function () {
  console.log("📊 ============ ESTADO ATUAL DO MODAL ============");

  const modal = document.getElementById("modal-meta-batida");
  console.log("Modal HTML existe:", !!modal);
  console.log("Modal visível:", modal?.style.display !== "none");

  if (typeof CelebracaoMetaManager !== "undefined") {
    console.log("CelebracaoMetaManager carregado: ✅");
    console.log("Já mostrado hoje:", CelebracaoMetaManager.jaMostradoHoje);
  } else {
    console.log("CelebracaoMetaManager carregado: ❌");
  }

  const ultimaData = localStorage.getItem("ultimaDataCelebracao");
  console.log("Última celebração:", ultimaData || "Nenhuma");
  console.log("Data hoje:", new Date().toISOString().split("T")[0]);

  console.log("================================================");
};

/**
 * Simula uma meta batida completa
 */
window.simularMetaBatida = function (
  lucroCustomizado = null,
  metaCustomizada = null
) {
  console.log("🎭 Simulando meta batida...");

  if (typeof CelebracaoMetaManager === "undefined") {
    console.error("❌ CelebracaoMetaManager não encontrado");
    return;
  }

  // Reseta antes de testar
  CelebracaoMetaManager.jaMostradoHoje = false;
  localStorage.removeItem("ultimaDataCelebracao");

  // Cria dados simulados
  const lucro = lucroCustomizado || 550;
  const meta = metaCustomizada || 500;

  const dadosSimulados = {
    lucro: lucro,
    meta_display: meta,
    meta_diaria: meta,
    periodo_ativo: "dia",
    banca: 1000,
  };

  console.log("📊 Simulando com dados:", dadosSimulados);

  // Verifica e mostra
  CelebracaoMetaManager.verificarEMostrarModal(dadosSimulados);

  if (CelebracaoMetaManager.jaMostradoHoje) {
    console.log("✅ Meta batida detectada! Modal exibido.");
  } else {
    console.log("⚠️ Modal não foi exibido. Verificar condições.");
  }
};

console.log("");
console.log("🎉 ========== TESTE MODAL CARREGADO ==========");
console.log("");
console.log("Comandos disponíveis:");
console.log("");
console.log("  1. testarModalCelebracao()");
console.log("     → Mostra o modal com dados de teste");
console.log("");
console.log("  2. testarSomCelebracao()");
console.log("     → Toca o som de celebração");
console.log("");
console.log("  3. resetarModalTeste()");
console.log("     → Reseta o estado e fecha o modal");
console.log("");
console.log("  4. verificarEstadoModal()");
console.log("     → Mostra o estado atual");
console.log("");
console.log("  5. simularMetaBatida(lucro, meta)");
console.log("     → Simula uma meta batida completa");
console.log("     → Exemplos:");
console.log("        simularMetaBatida() - usa valores padrão");
console.log("        simularMetaBatida(1000, 500) - lucro R$1000, meta R$500");
console.log("");
console.log("=============================================");
console.log("");
