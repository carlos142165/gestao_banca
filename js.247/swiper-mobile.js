/**
 * ============================================================
 * SWIPER MOBILE - CAROUSEL DE BLOCOS (DIA/MÊS/ANO)
 * ============================================================
 * Implementação de carousel mobile para os 3 blocos
 * - Desktop: 3 blocos lado a lado
 * - Mobile: 1 bloco por vez com deslize
 */

let swiperInstance = null;

document.addEventListener("DOMContentLoaded", function () {
  console.log("🚀 Inicializando Swiper Mobile...");

  // Aguardar Swiper ser carregado
  if (typeof Swiper !== "undefined") {
    inicializarSwiper();
  } else {
    // Tentar novamente se Swiper não estiver disponível
    let tentativas = 0;
    const verificar = setInterval(() => {
      if (typeof Swiper !== "undefined") {
        clearInterval(verificar);
        console.log("✅ Swiper carregado, inicializando...");
        inicializarSwiper();
      } else if (tentativas++ > 20) {
        clearInterval(verificar);
        console.warn("⚠️ Swiper não conseguiu ser carregado");
      }
    }, 100);
  }
});

function inicializarSwiper() {
  const container = document.getElementById("swiperMobileContainer");

  if (!container) {
    console.error("❌ Container do Swiper não encontrado");
    return;
  }

  console.log("📱 Detectando breakpoint atual...");
  const ehMobile = window.innerWidth <= 1024;
  console.log(
    `📐 ${ehMobile ? "MOBILE" : "DESKTOP"} - Largura: ${window.innerWidth}px`
  );

  if (!ehMobile) {
    console.log(
      "📊 Desktop detectado, Swiper desativado (layout padrão dos 3 blocos)"
    );
    return;
  }

  // Criar instância do Swiper para mobile
  swiperInstance = new Swiper("#swiperMobileContainer", {
    direction: "horizontal",
    loop: false,
    effect: "slide",
    speed: 300,
    spaceBetween: 0,
    slidesPerView: 1,

    // Pagination (pontos)
    pagination: {
      el: ".swiper-pagination",
      clickable: true,
      bulletClass: "swiper-pagination-bullet",
      bulletActiveClass: "swiper-pagination-bullet-active",
      renderBullet: function (index, className) {
        const nomes = ["DIA", "MÊS", "ANO"];
        return `<span class="${className}" title="${nomes[index]}"></span>`;
      },
    },

    // Navegação (botões prev/next)
    navigation: {
      nextEl: ".swiper-button-next",
      prevEl: ".swiper-button-prev",
      disabledClass: "swiper-button-disabled",
    },

    // Touch
    touchRatio: 1,
    touchStartForce: true,
    touchMoveStopPropagation: true,
    touchReleaseOnEdges: false,
    grabCursor: true,

    // Keyboard
    keyboard: {
      enabled: true,
      onlyInViewport: true,
    },

    // Eventos
    on: {
      init: function (swiper) {
        console.log("✅ Swiper inicializado com sucesso!");
        console.log(`   📊 Total de slides: ${swiper.slides.length}`);
        console.log(
          `   🔄 Slide atual: ${swiper.activeIndex + 1}/${swiper.slides.length}`
        );
      },

      slideChange: function (swiper) {
        const nomes = ["DIA", "MÊS", "ANO"];
        console.log(
          `🔄 Slide mudou para: ${nomes[swiper.activeIndex]} (${
            swiper.activeIndex + 1
          }/${swiper.slides.length})`
        );

        // Atualizar acessibilidade
        atualizarAria(swiper);
      },

      slideNextTransitionEnd: function (swiper) {
        console.log("➡️  Próximo slide");
      },

      slidePrevTransitionEnd: function (swiper) {
        console.log("⬅️  Slide anterior");
      },

      touchEnd: function (swiper) {
        console.log("👆 Toque finalizado");
      },
    },

    breakpoints: {
      // Quebras de responsividade
      1025: {
        enabled: false,
        allowTouchMove: false,
      },
    },
  });

  console.log("✨ Swiper Mobile configurado com sucesso!");

  // Listener para mudança de tamanho de tela (responsive)
  window.addEventListener("resize", () => {
    const agora = window.innerWidth <= 1024;
    const eraAlgo = swiperInstance && swiperInstance.params.enabled;

    if (agora && !eraAlgo) {
      console.log("📱 Redimensionamento: MOBILE - Ativando Swiper...");
      if (swiperInstance) {
        swiperInstance.enable();
        swiperInstance.update();
      }
    } else if (!agora && eraAlgo) {
      console.log("🖥️ Redimensionamento: DESKTOP - Desativando Swiper...");
      if (swiperInstance) {
        swiperInstance.disable();
      }
    }
  });
}

/**
 * Atualizar atributos ARIA para acessibilidade
 */
function atualizarAria(swiper) {
  const nomes = ["DIA", "MÊS", "ANO"];
  const slide = swiper.slides[swiper.activeIndex];

  if (slide) {
    slide.setAttribute("aria-current", "page");

    // Remover aria-current dos outros
    swiper.slides.forEach((s, index) => {
      if (index !== swiper.activeIndex) {
        s.removeAttribute("aria-current");
      }
    });

    // Anunciar para leitores de tela
    const label = nomes[swiper.activeIndex] || "Slide";
    const announcement = `Mostrando bloco ${label} (${
      swiper.activeIndex + 1
    } de ${swiper.slides.length})`;

    // Usar live region se disponível
    const liveRegion = document.querySelector('[aria-live="polite"]');
    if (liveRegion) {
      liveRegion.textContent = announcement;
    }
  }
}

/**
 * Funções de controle manual (se necessário)
 */
window.swiperControls = {
  /**
   * Ir para slide específico (0 = DIA, 1 = MÊS, 2 = ANO)
   */
  irPara: function (index) {
    if (swiperInstance && index >= 0 && index < 3) {
      swiperInstance.slideTo(index);
      console.log(`🎯 Navegando para slide ${index}`);
    }
  },

  /**
   * Próximo slide
   */
  proximo: function () {
    if (swiperInstance) {
      swiperInstance.slideNext();
    }
  },

  /**
   * Slide anterior
   */
  anterior: function () {
    if (swiperInstance) {
      swiperInstance.slidePrev();
    }
  },

  /**
   * Obter informações do swiper
   */
  info: function () {
    if (swiperInstance) {
      const nomes = ["DIA", "MÊS", "ANO"];
      return {
        ativo: swiperInstance.activeIndex,
        nome: nomes[swiperInstance.activeIndex],
        total: swiperInstance.slides.length,
        enabled: swiperInstance.params.enabled,
      };
    }
    return null;
  },

  /**
   * Ativar/Desativar swiper
   */
  ativar: function () {
    if (swiperInstance && !swiperInstance.params.enabled) {
      swiperInstance.enable();
      console.log("✅ Swiper ativado");
    }
  },

  desativar: function () {
    if (swiperInstance && swiperInstance.params.enabled) {
      swiperInstance.disable();
      console.log("❌ Swiper desativado");
    }
  },
};

// Comandos para debug via console
window.debugSwiper = function () {
  console.table({
    "Instância Ativa": swiperInstance ? "Sim" : "Não",
    "Slide Atual": swiperInstance ? swiperInstance.activeIndex + 1 : "-",
    "Total de Slides": swiperInstance ? swiperInstance.slides.length : "-",
    "Swiper Enabled": swiperInstance ? swiperInstance.params.enabled : "-",
    "Largura da Tela": window.innerWidth + "px",
    "Breakpoint Mobile":
      window.innerWidth <= 1024 ? "Sim (≤1024px)" : "Não (>1024px)",
  });
};

console.log("💡 Dicas de debug:");
console.log("   debugSwiper() - Ver status do Swiper");
console.log("   swiperControls.info() - Informações");
console.log("   swiperControls.irPara(0) - Ir para DIA");
console.log("   swiperControls.irPara(1) - Ir para MÊS");
console.log("   swiperControls.irPara(2) - Ir para ANO");
