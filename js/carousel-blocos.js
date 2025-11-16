/**
 * ========================================
 * CAROUSEL PARA BLOCOS - GESTÃO DIÁRIA E BOT AO VIVO
 * ========================================
 * Gerencia o deslizamento horizontal dos blocos em mobile
 * Mostra indicadores de abas na base da tela
 */

(function () {
  "use strict";

  const CarouselBlocos = {
    // ========== PROPRIEDADES ==========
    currentBloco: 0,
    totalBlocos: 3,
    isMobile: false,
    mainContent: null,
    container: null,
    blocos: null,
    indicators: null,
    progressBar: null,
    scrollTimeout: null,
    isScrolling: false,
    scrollDirection: "none",
    lastScrollLeft: 0,
    touchStartX: 0,
    touchEndX: 0,

    // ========== INICIALIZAÇÃO ==========
    init: function () {
      console.log("🎠 CarouselBlocos inicializando...");

      this.mainContent = document.querySelector(".main-content");
      this.container = document.querySelector(".container");
      this.blocos = document.querySelectorAll(".bloco");
      this.totalBlocos = this.blocos.length;

      if (!this.mainContent || !this.blocos.length) {
        console.warn("⚠️ Elementos do carousel não encontrados");
        return;
      }

      // Detectar se está em mobile
      this.checkIsMobile();

      // Criar indicadores
      this.createIndicators();

      // Bind events
      this.bindEvents();

      // Inicializar estado
      this.updateIndicators();

      console.log("✅ CarouselBlocos inicializado com sucesso");
      console.log(`📊 Total de blocos: ${this.totalBlocos}`);
    },

    // ========== CRIAR INDICADORES ==========
    createIndicators: function () {
      // Verificar se já existe
      if (document.querySelector(".carousel-indicators")) {
        this.indicators = document.querySelectorAll(".carousel-dot");
        this.progressBar = document.querySelector(".carousel-progress-bar");
        console.log("📌 Indicadores já existem, usando existentes");
        return;
      }

      // Criar elemento dos indicadores
      const indicatorsContainer = document.createElement("div");
      indicatorsContainer.className = "carousel-indicators";

      // Criar barra de progresso
      const progressBar = document.createElement("div");
      progressBar.className = "carousel-progress-bar";
      indicatorsContainer.appendChild(progressBar);
      this.progressBar = progressBar;

      // Criar dots
      const dotsContainer = document.createElement("div");
      dotsContainer.className = "carousel-indicators-dots";

      for (let i = 0; i < this.totalBlocos; i++) {
        const dot = document.createElement("div");
        dot.className = "carousel-dot";
        dot.dataset.bloco = i;

        // Label do dot
        const label = document.createElement("span");
        label.className = "carousel-indicator-label";
        label.textContent = this.getBlockoName(i);
        dot.appendChild(label);

        // Click handler
        dot.addEventListener("click", () => this.scrollToBloco(i));

        dotsContainer.appendChild(dot);
      }

      indicatorsContainer.appendChild(dotsContainer);
      document.body.appendChild(indicatorsContainer);

      this.indicators = document.querySelectorAll(".carousel-dot");
      console.log("✅ Indicadores criados com sucesso");
    },

    // ========== BIND EVENTS ==========
    bindEvents: function () {
      // Scroll event para detectar mudança de bloco
      this.mainContent.addEventListener("scroll", () => this.onScroll());

      // Touch events para swipe
      this.mainContent.addEventListener(
        "touchstart",
        (e) => this.onTouchStart(e),
        false
      );
      this.mainContent.addEventListener(
        "touchend",
        (e) => this.onTouchEnd(e),
        false
      );

      // Window resize para detec mobile
      window.addEventListener("resize", () => this.checkIsMobile());

      // Keyboard navigation
      document.addEventListener("keydown", (e) => this.onKeyDown(e));

      console.log("✅ Event listeners vinculados");
    },

    // ========== DETECÇÃO DE MOBILE ==========
    checkIsMobile: function () {
      const wasMobile = this.isMobile;
      this.isMobile = window.innerWidth <= 1024;

      if (wasMobile !== this.isMobile) {
        console.log(
          `📱 Modo mudou para: ${this.isMobile ? "MOBILE" : "DESKTOP"}`
        );
      }
    },

    // ========== ON SCROLL ==========
    onScroll: function () {
      if (!this.isMobile) return;

      // Detect current bloco based on scroll position
      const scrollLeft = this.mainContent.scrollLeft;
      const blocoWidth = window.innerWidth;
      const newBloco = Math.round(scrollLeft / blocoWidth);

      if (newBloco !== this.currentBloco) {
        this.currentBloco = newBloco;
        console.log(
          `📍 Bloco atual: ${this.currentBloco + 1}/${this.totalBlocos}`
        );
        this.updateIndicators();
      }

      // Atualizar barra de progresso
      this.updateProgressBar();

      // Clear timeout anterior
      clearTimeout(this.scrollTimeout);

      // Marcar como scrolling
      this.isScrolling = true;

      // Timeout para detec fim do scroll
      this.scrollTimeout = setTimeout(() => {
        this.isScrolling = false;
        this.snapToBloco();
      }, 150);
    },

    // ========== TOUCH START ==========
    onTouchStart: function (e) {
      if (!this.isMobile) return;
      this.touchStartX = e.changedTouches[0].screenX;
    },

    // ========== TOUCH END ==========
    onTouchEnd: function (e) {
      if (!this.isMobile) return;

      this.touchEndX = e.changedTouches[0].screenX;
      this.handleSwipe();
    },

    // ========== HANDLE SWIPE ==========
    handleSwipe: function () {
      const diff = this.touchStartX - this.touchEndX;
      const threshold = 50;

      if (Math.abs(diff) > threshold) {
        if (diff > 0) {
          // Swipe left - próximo bloco
          this.nextBloco();
        } else {
          // Swipe right - bloco anterior
          this.prevBloco();
        }
      }
    },

    // ========== KEYBOARD NAVIGATION ==========
    onKeyDown: function (e) {
      if (!this.isMobile) return;

      if (e.key === "ArrowRight") {
        e.preventDefault();
        this.nextBloco();
      } else if (e.key === "ArrowLeft") {
        e.preventDefault();
        this.prevBloco();
      }
    },

    // ========== PRÓXIMO BLOCO ==========
    nextBloco: function () {
      if (this.currentBloco < this.totalBlocos - 1) {
        this.scrollToBloco(this.currentBloco + 1);
      }
    },

    // ========== BLOCO ANTERIOR ==========
    prevBloco: function () {
      if (this.currentBloco > 0) {
        this.scrollToBloco(this.currentBloco - 1);
      }
    },

    // ========== SCROLL TO BLOCO ==========
    scrollToBloco: function (blocoIndex) {
      if (blocoIndex < 0 || blocoIndex >= this.totalBlocos) return;

      const scrollLeft = blocoIndex * window.innerWidth;
      this.mainContent.scrollLeft = scrollLeft;
      this.currentBloco = blocoIndex;
      this.updateIndicators();

      console.log(`🎯 Navegando para bloco: ${blocoIndex + 1}`);
    },

    // ========== SNAP TO BLOCO ==========
    snapToBloco: function () {
      if (!this.isMobile) return;

      const scrollLeft = this.mainContent.scrollLeft;
      const blocoWidth = window.innerWidth;
      const currentPosition = scrollLeft / blocoWidth;
      const nearestBloco = Math.round(currentPosition);

      if (nearestBloco !== Math.floor(currentPosition)) {
        this.scrollToBloco(nearestBloco);
      }
    },

    // ========== UPDATE INDICATORS ==========
    updateIndicators: function () {
      if (!this.indicators) return;

      this.indicators.forEach((dot, index) => {
        if (index === this.currentBloco) {
          dot.classList.add("active");
        } else {
          dot.classList.remove("active");
        }
      });

      this.updateProgressBar();
    },

    // ========== UPDATE PROGRESS BAR ==========
    updateProgressBar: function () {
      if (!this.progressBar) return;

      const percentage = ((this.currentBloco + 1) / this.totalBlocos) * 100;
      const position = (this.currentBloco / this.totalBlocos) * 100;

      this.progressBar.style.left = position + "%";
      this.progressBar.style.width = 100 / this.totalBlocos + "%";
    },

    // ========== GET BLOCO NAME ==========
    getBlockoName: function (index) {
      const names = ["Bloco 1", "Bloco 2", "Bloco 3"];
      return names[index] || `Bloco ${index + 1}`;
    },
  };

  // ========== INICIALIZAR AO CARREGAR DOM ==========
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => {
      CarouselBlocos.init();
    });
  } else {
    CarouselBlocos.init();
  }

  // ========== EXPORTAR PARA WINDOW ==========
  window.CarouselBlocos = CarouselBlocos;

  console.log("🎠 Script de Carousel Blocos carregado com sucesso");
})();
