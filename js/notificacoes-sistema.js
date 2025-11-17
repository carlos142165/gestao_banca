/**
 * 🔔 SISTEMA DE NOTIFICAÇÕES COM SOM
 * Mostra notificações quando novas mensagens chegam
 * Funciona em qualquer página aberta
 */

const NotificacoesSistema = {
  permissaoNotificacao: false,
  ultimasNotificacoes: new Set(), // Para evitar duplicatas

  /**
   * Inicializar sistema de notificações
   */
  init() {
    console.log("🔔 Inicializando sistema de notificações...");
    this.registrarLog("info", "Sistema inicializado");
    this.requestPermissao();
    this.criarAudioAlerta();
  },

  /**
   * Registrar log no servidor
   */
  registrarLog(tipo, mensagem, dados = {}) {
    try {
      const formData = new FormData();
      formData.append("acao", "registrar_log");
      formData.append("tipo", tipo);
      formData.append("mensagem", mensagem);
      formData.append("dados", JSON.stringify(dados));

      fetch("logs/LogNotificacoes.php", {
        method: "POST",
        body: formData,
      }).catch((err) => {
        console.log("ℹ️ Erro ao registrar log:", err);
      });
    } catch (err) {
      console.error("❌ Erro ao enviar log:", err);
    }
  },

  /**
   * Solicitar permissão para notificações do navegador
   */
  requestPermissao() {
    if (!("Notification" in window)) {
      console.log("ℹ️ Este navegador não suporta Web Notifications");
      return;
    }

    if (Notification.permission === "granted") {
      this.permissaoNotificacao = true;
      console.log("✅ Permissão de notificações já concedida");
      return;
    }

    if (Notification.permission !== "denied") {
      Notification.requestPermission().then((permission) => {
        if (permission === "granted") {
          this.permissaoNotificacao = true;
          console.log("✅ Permissão de notificações concedida");
        } else {
          console.log("❌ Permissão de notificações negada");
        }
      });
    }
  },

  /**
   * Criar elemento de áudio para alerta
   */
  criarAudioAlerta() {
    // Verificar se já existe
    if (document.getElementById("audio-alerta-notificacao")) {
      return;
    }

    // Criar elemento de áudio
    const audio = document.createElement("audio");
    audio.id = "audio-alerta-notificacao";
    audio.preload = "auto";
    audio.volume = 0.7; // Volume padrão

    // Tentar usar arquivo de som local ou fallback para som padrão
    // Som: notificação simples (criado com data URI - beep curto)
    const audioDataUri =
      "data:audio/wav;base64,UklGRiYAAABXQVZFZm10IBAAAAABAAEAQB8AAAB9AAACABAAZGF0YQIAAAAAAA==";

    audio.src = audioDataUri;
    document.body.appendChild(audio);

    console.log("✅ Áudio de alerta criado");
  },

  /**
   * Reproduzir som de alerta
   */
  reproduzirSom() {
    try {
      const audio = document.getElementById("audio-alerta-notificacao");
      if (audio) {
        audio.currentTime = 0;
        audio.play().catch((err) => {
          console.log("ℹ️ Som bloqueado pelo navegador (autoplay):", err);
        });
      }
    } catch (err) {
      console.error("❌ Erro ao reproduzir som:", err);
    }
  },

  /**
   * Usar Web Audio API para criar som de alerta
   */
  criarSomComWebAudio() {
    try {
      const audioContext = new (window.AudioContext ||
        window.webkitAudioContext)();

      // Criar beep (nota: 800Hz, duração: 200ms)
      const oscilador = audioContext.createOscillator();
      const ganho = audioContext.createGain();

      oscilador.connect(ganho);
      ganho.connect(audioContext.destination);

      oscilador.frequency.value = 800; // Frequência em Hz
      oscilador.type = "sine";

      ganho.gain.setValueAtTime(0.3, audioContext.currentTime);
      ganho.gain.exponentialRampToValueAtTime(
        0.01,
        audioContext.currentTime + 0.2
      );

      oscilador.start(audioContext.currentTime);
      oscilador.stop(audioContext.currentTime + 0.2);

      console.log("🔊 Som criado com Web Audio API");
    } catch (err) {
      console.log("ℹ️ Web Audio API não disponível:", err);
    }
  },

  /**
   * Mostrar notificação de navegador
   * @param {string} titulo - Título da notificação
   * @param {object} opcoes - Opções da notificação
   */
  mostrarNotificacao(titulo, opcoes = {}) {
    // Verificar se o navegador suporta notificações
    if (!("Notification" in window)) {
      console.log("ℹ️ Este navegador não suporta Web Notifications");
      return;
    }

    // Verificar permissão - pode ser "granted", "denied" ou "default"
    if (Notification.permission !== "granted") {
      console.log(
        "ℹ️ Permissão não concedida. Permissão atual:",
        Notification.permission
      );
      return;
    }

    // Evitar duplicatas muito próximas
    const hash = titulo + JSON.stringify(opcoes);
    if (this.ultimasNotificacoes.has(hash)) {
      console.log("⏭️ Notificação duplicada ignorada");
      return;
    }

    this.ultimasNotificacoes.add(hash);
    setTimeout(() => {
      this.ultimasNotificacoes.delete(hash);
    }, 3000);

    // ✅ MOBILE: Tentar usar Service Worker se disponível (melhor para mobile)
    if ("serviceWorker" in navigator && navigator.serviceWorker.controller) {
      console.log("📱 Usando Service Worker para notificação (modo mobile)");
      navigator.serviceWorker.controller.postMessage({
        tipo: "mostrar-notificacao",
        titulo: titulo,
        body: opcoes.body || "",
        icon:
          opcoes.icon ||
          'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="45" fill="%23ff6b6b"/></svg>',
      });
      console.log("🔔 Notificação enviada via Service Worker");
      return;
    }

    // ✅ DESKTOP: Usar Notification API padrão
    console.log("💻 Usando Notification API padrão (modo desktop)");
    const notificacao = new Notification(titulo, {
      icon:
        opcoes.icon ||
        "data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><circle cx='50' cy='50' r='45' fill='%23ff6b6b'/><path d='M40 30 L60 30 L58 55 L42 55 Z' fill='white'/><circle cx='50' cy='70' r='3' fill='white'/></svg>",
      badge:
        opcoes.badge ||
        "data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><circle cx='50' cy='50' r='45' fill='%23ff6b6b'/></svg>",
      ...opcoes,
    });

    // Ao clicar na notificação, abrir bot_aovivo.php
    notificacao.addEventListener("click", () => {
      window.focus();
      window.location.href = "bot_aovivo.php";
      notificacao.close();
    });

    console.log("🔔 Notificação enviada:", titulo);
  },

  /**
   * Detectar tipo de oportunidade (CANTO ou GOLS) - ANALISANDO MELHOR
   * @param {string} texto - Texto da mensagem (título completo)
   * @param {object} msg - Objeto da mensagem com dados adicionais
   * @returns {string} - 'cantos' ou 'gols'
   */
  detectarTipo(texto, msg = {}) {
    // ✅ ESTRATÉGIA: Procurar em múltiplas fontes de dados

    // 1️⃣ PRIMEIRA OPÇÃO: Verificar campo titulo do msg
    let tituloParaAnalisar = msg.titulo || "";

    // 2️⃣ SE não tem titulo, pegar primeira linha do texto
    if (!tituloParaAnalisar && texto) {
      const primeiraLinha = texto.split("\n")[0];
      tituloParaAnalisar = primeiraLinha;
    }

    // 3️⃣ SE ainda não tem nada, usar todo o texto
    if (!tituloParaAnalisar) {
      tituloParaAnalisar = texto || "";
    }

    const textoAnalise = tituloParaAnalisar.toLowerCase();

    console.log("🔍 Detectando tipo. Texto para análise:", textoAnalise);

    // ✅ REGRA 1: Procurar por padrão "⛳" + "CANTOS" juntos = CANTOS
    // Isso é mais específico e confiável
    if (textoAnalise.includes("⛳") && textoAnalise.includes("cantos")) {
      console.log("✅ Detectado: CANTOS (por ⛳ + cantos)");
      return "cantos";
    }

    // ✅ REGRA 2: Procurar por padrão "⚽" + "GOL" juntos = GOLS
    // Isso é mais específico e confiável
    if (textoAnalise.includes("⚽") && textoAnalise.includes("gol")) {
      console.log("✅ Detectado: GOLS (por ⚽ + gol)");
      return "gols";
    }

    // ✅ REGRA 3: Se só tem "⛳" sem "gol" = CANTOS (emoji é o indicador principal)
    if (textoAnalise.includes("⛳")) {
      console.log("✅ Detectado: CANTOS (por ⛳)");
      return "cantos";
    }

    // ✅ REGRA 4: Se só tem "⚽" sem "cantos" = GOLS (emoji é o indicador principal)
    if (textoAnalise.includes("⚽")) {
      console.log("✅ Detectado: GOLS (por ⚽)");
      return "gols";
    }

    // ✅ REGRA 5: Se não tem emoji, procurar por palavra inteira
    // Procurar "cantos" (mas não em "escant")
    if (/\bcantos?\b|\bescanteio/.test(textoAnalise)) {
      console.log("✅ Detectado: CANTOS (por palavra 'cantos')");
      return "cantos";
    }

    // ✅ REGRA 6: Se não tem emoji, procurar por "gol" (mas não em "escant")
    if (/\bgols?\b/.test(textoAnalise)) {
      console.log("✅ Detectado: GOLS (por palavra 'gol')");
      return "gols";
    }

    // ✅ FALLBACK: Verificar campo tipo_aposta (ou type da API)
    const tipoApostaField = msg.tipo_aposta || msg.type;
    if (tipoApostaField) {
      const tipoAposta = tipoApostaField.toLowerCase();
      console.log("📋 Verificando tipo_aposta/type:", tipoAposta);

      // Verificar se contém palavras-chave para CANTOS
      if (
        tipoAposta.includes("⛳") ||
        tipoAposta.includes("canto") ||
        /\bcantos?\b/.test(tipoAposta)
      ) {
        console.log("✅ Detectado por tipo_aposta/type: CANTOS");
        return "cantos";
      }

      // Verificar se contém palavras-chave para GOLS
      if (
        tipoAposta.includes("⚽") ||
        tipoAposta.includes("gol") ||
        /\bgols?\b/.test(tipoAposta)
      ) {
        console.log("✅ Detectado por tipo_aposta/type: GOLS");
        return "gols";
      }
    }

    // ✅ ÚLTIMO FALLBACK: Padrão é GOLS
    console.log("⚠️ Nenhuma detecção específica, usando default: GOLS");
    return "gols";
  },
  /**
   * Gerar ícone para o tipo (usando imagens da pasta img)
   * @param {string} tipo - 'cantos' ou 'gols'
   * @returns {string} - URL absoluta da imagem
   */ gerarIconoTipo(tipo) {
    // Obter protocolo e host
    const protocolo = window.location.protocol;
    const host = window.location.host;
    const pathname = window.location.pathname;

    // Detectar se está em /gestao/gestao_banca ou similar
    let basePath = "";
    if (pathname.includes("/gestao_banca/")) {
      basePath = "/gestao_banca";
    } else if (pathname.includes("/gestao/")) {
      basePath = "/gestao";
    }

    const url = protocolo + "//" + host + basePath;

    let imagemUrl = "";
    if (tipo === "cantos") {
      // Imagem de cantos - notificacao_cantos.jpg
      imagemUrl = url + "/img/notificacao_cantos.jpg";
    } else {
      // Imagem de gols - notificacao_gol.jpg
      imagemUrl = url + "/img/notificacao_gol.jpg";
    }

    console.log("🖼️ Imagem gerada:", imagemUrl, "Tipo:", tipo);
    return imagemUrl;
  },

  /**
   * Extrair nomes dos times
   * @param {object} msg - Dados da mensagem
   * @returns {string} - "Time1 vs Time2" ou extrai do texto
   */
  extrairTimes(msg) {
    if (msg.time_1 && msg.time_2) {
      return `${msg.time_1} vs ${msg.time_2}`;
    }

    // Tentar extrair do título/texto
    const texto = msg.titulo || msg.text || "";
    const match = texto.match(
      /([A-Z][a-z]+(?:\s+[A-Z][a-z]+)*)\s+(?:vs|x|vs\.)\s+([A-Z][a-z]+(?:\s+[A-Z][a-z]+)*)/i
    );
    if (match) {
      return `${match[1]} vs ${match[2]}`;
    }

    return "Novo jogo";
  },

  /**
   * Notificar nova mensagem com visual melhorado
   * @param {object} msg - Dados da mensagem
   */
  notificarNovaMensagem(msg) {
    console.log("📨 notificarNovaMensagem chamada com:", msg);
    this.registrarLog("info", "notificarNovaMensagem chamada", msg);

    // Verificar permissão
    if (!("Notification" in window)) {
      console.log("❌ Navegador não suporta notificações");
      this.registrarLog("erro", "Navegador não suporta notificações");
      return;
    }

    console.log("🔔 Permissão atual:", Notification.permission);
    this.registrarLog("info", "Verificando permissão", {
      permissao: Notification.permission,
    });

    if (Notification.permission !== "granted") {
      console.log("❌ Permissão não está 'granted':", Notification.permission);
      this.registrarLog("aviso", "Permissão não concedida", {
        permissao: Notification.permission,
      });
      return;
    }

    // Detectar tipo de oportunidade - PASSANDO O OBJETO COMPLETO
    const tipo = this.detectarTipo(msg.titulo || msg.text, msg);
    const tipoTexto = tipo === "cantos" ? "🚩 CANTOS" : "⚽ GOLS";

    // Extrair times
    const times = this.extrairTimes(msg);

    // Montar corpo da notificação
    const titulo = `${tipoTexto} - ${times}`;
    const oportunidade = msg.titulo || msg.text || "Nova oportunidade";

    // Truncar se muito longo
    const bodyTruncado =
      oportunidade.length > 80
        ? oportunidade.substring(0, 77) + "..."
        : oportunidade;

    // Gerar ícone apropriado
    const icone = this.gerarIconoTipo(tipo);

    console.log("✅ Enviando notificação:", {
      titulo,
      body: bodyTruncado,
      icon: icone,
      tipo: tipo,
    });

    this.registrarLog("info", "Preparando notificação", {
      titulo,
      body: bodyTruncado,
      tipo,
      times,
    });

    this.mostrarNotificacao(titulo, {
      body: bodyTruncado,
      icon: icone,
      badge: icone,
      tag: `msg-${msg.id}`,
      requireInteraction: false,
    });

    // Reproduzir som
    this.reproduzirSom();

    // Tentar criar som com Web Audio se o anterior não funcionar
    setTimeout(() => {
      this.criarSomComWebAudio();
    }, 100);

    console.log(`📢 Notificação enviada: ${tipoTexto} - ${times}`);
    this.registrarLog("sucesso", "Notificação enviada com sucesso", {
      titulo,
      tipo,
    });
  },
};

// 🔔 Inicializar quando o documento carregar
document.addEventListener("DOMContentLoaded", () => {
  NotificacoesSistema.init();
});

// 🔔 Ou inicializar imediatamente se o documento já estiver pronto
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", () => {
    NotificacoesSistema.init();
  });
} else {
  NotificacoesSistema.init();
}
