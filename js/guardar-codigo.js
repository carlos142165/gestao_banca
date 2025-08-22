// ========================================================================================================================
//                                               CODIGO META FIXA E META TURBO
// ========================================================================================================================
(function () {
  "use strict";

  let isUpdating = false;
  let updateTimeout = null;
  const ELEMENTO_ID = "meta-text-unico";

  function limparTodasAsMetas() {
    // ✅ LIMPAR APENAS DENTRO DO WIDGET META ESPECÍFICO
    const widgetMeta = document.getElementById("meta-valor");

    if (!widgetMeta) return;

    const elementosMetaNoWidget = widgetMeta.querySelectorAll(`
        [id*="meta-text"]:not(#${ELEMENTO_ID}), 
        .meta-text:not(#${ELEMENTO_ID}), 
        span[class*="meta"]:not(#${ELEMENTO_ID})
      `);

    elementosMetaNoWidget.forEach((el) => {
      if (el.id !== ELEMENTO_ID) {
        // ✅ REMOVER SEM ANIMAÇÃO
        el.style.transition = "none";
        el.style.opacity = "0";
        el.remove();
      }
    });

    console.log("✅ Limpeza realizada apenas no widget meta");
  }

  function encontrarElementoValor() {
    // ✅ BUSCA ESPECÍFICA APENAS NO WIDGET DA META
    const widgetMeta = document.getElementById("meta-valor");

    if (!widgetMeta) {
      console.warn("⚠️ Widget meta-valor não encontrado");
      return null;
    }

    // Buscar o elemento valor-texto dentro do widget específico
    const valorTexto =
      widgetMeta.querySelector(".valor-texto") ||
      widgetMeta.querySelector("#valor-texto-meta");

    if (valorTexto) {
      console.log(
        "✅ Elemento valor-texto encontrado no widget meta:",
        valorTexto.textContent
      );
      return valorTexto;
    }

    console.warn("⚠️ Elemento valor-texto não encontrado no widget meta");
    return null;
  }

  function inserirMetaUnica(tipoMeta = null) {
    if (isUpdating) {
      console.log("⏸️ Atualização em andamento, ignorando...");
      return;
    }

    isUpdating = true;
    console.log("🔄 Inserindo meta única...");

    if (updateTimeout) {
      clearTimeout(updateTimeout);
    }

    let metaElement = document.getElementById(ELEMENTO_ID);

    if (metaElement) {
      if (tipoMeta) {
        atualizarConteudoMeta(metaElement, tipoMeta);
      } else {
        buscarEAtualizarMeta(metaElement);
      }
      isUpdating = false;
      return;
    }

    limparTodasAsMetas();

    const elementoValor = encontrarElementoValor();

    if (!elementoValor) {
      console.warn("⚠️ Elemento de valor não encontrado");
      isUpdating = false;
      return;
    }

    // ✅ CRIAR ELEMENTO - CSS CUIDA DO POSICIONAMENTO
    const metaSpan = document.createElement("span");
    metaSpan.id = ELEMENTO_ID;
    metaSpan.className = "meta-text meta-fixa";
    metaSpan.textContent = "META FIXA";
    // ✅ SEM ESTILOS INLINE - APENAS CLASSES CSS

    // ✅ INSERIR NO WIDGET CONTAINER - CSS FAZ O RESTO
    const widget =
      elementoValor.closest('[class*="widget"]') ||
      elementoValor.closest(".container") ||
      elementoValor.parentElement;

    widget.appendChild(metaSpan);

    if (tipoMeta) {
      atualizarConteudoMeta(metaSpan, tipoMeta);
    } else {
      buscarEAtualizarMeta(metaSpan);
    }

    console.log("✅ Meta única criada - CSS controla posicionamento");
    isUpdating = false;
  }

  function atualizarConteudoMeta(elemento, tipoMeta) {
    const isturbo = tipoMeta === "Meta Turbo";

    // ✅ REMOVER TRANSIÇÕES DURANTE ATUALIZAÇÃO
    elemento.style.transition = "none";

    elemento.className = `meta-text meta-${isturbo ? "turbo" : "fixa"}`;
    elemento.textContent = isturbo ? "META TURBO" : "META FIXA";

    // ✅ RESTAURAR TRANSIÇÕES APÓS ATUALIZAÇÃO
    setTimeout(() => {
      elemento.style.transition = "";
    }, 50);

    console.log(`✅ Meta atualizada para: ${tipoMeta} (sem animação)`);
  }

  function buscarEAtualizarMeta(elemento) {
    fetch("ajax_deposito.php")
      .then((response) => response.json())
      .then((data) => {
        const metaFromServer =
          data.success && data.meta ? data.meta : "Meta Fixa";

        // ✅ SÓ ATUALIZAR SE FOR DIFERENTE DO ATUAL
        const isturboAtual = elemento.classList.contains("meta-turbo");
        const isturboNovo = metaFromServer === "Meta Turbo";

        if (isturboAtual !== isturboNovo) {
          atualizarConteudoMeta(elemento, metaFromServer);
        } else {
          console.log("✅ Meta já está correta, não precisa atualizar");
        }
      })
      .catch((error) => {
        console.log("Info: Mantendo meta atual por erro na busca");
        // ✅ NÃO ATUALIZAR EM CASO DE ERRO
      });
  }

  // Interceptação AJAX
  const originalFetch = window.fetch;
  window.fetch = function (...args) {
    return originalFetch.apply(this, args).then((response) => {
      if (args[0] === "ajax_deposito.php" && response.ok) {
        const clonedResponse = response.clone();
        clonedResponse
          .json()
          .then((data) => {
            if (data.success && data.meta) {
              console.log("🔄 AJAX detectado, atualizando meta:", data.meta);
              updateTimeout = setTimeout(() => {
                inserirMetaUnica(data.meta);
              }, 300);
            }
          })
          .catch(() => {});
      }
      return response;
    });
  };

  // Funções globais
  window.atualizarTextoMeta = (tipoMeta) => {
    updateTimeout = setTimeout(() => {
      inserirMetaUnica(tipoMeta);
    }, 50);
  };

  window.forcarAtualizacaoMeta = () => {
    updateTimeout = setTimeout(() => {
      inserirMetaUnica();
    }, 50);
  };

  window.limparTodasAsMetas = limparTodasAsMetas;

  // Inicialização junto com o DOM
  function inicializar() {
    function executarMeta() {
      const elementoValor = encontrarElementoValor();
      if (elementoValor) {
        inserirMetaUnica();
      } else {
        setTimeout(() => {
          const elemento = encontrarElementoValor();
          if (elemento) {
            inserirMetaUnica();
          } else {
            console.log("⚠️ Elemento não encontrado após tentativas");
          }
        }, 500);
      }
    }

    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", executarMeta);
    } else if (document.readyState === "interactive") {
      executarMeta();
    } else {
      executarMeta();
    }
  }

  // Observador para mudanças no DOM
  let observer;

  function iniciarObservador() {
    if (observer) return;

    observer = new MutationObserver((mutations) => {
      let shouldUpdate = false;

      mutations.forEach((mutation) => {
        if (mutation.type === "childList" && mutation.addedNodes.length > 0) {
          mutation.addedNodes.forEach((node) => {
            if (
              node.nodeType === Node.ELEMENT_NODE &&
              node.textContent &&
              node.textContent.includes("R$")
            ) {
              shouldUpdate = true;
            }
          });
        }
      });

      if (shouldUpdate && !isUpdating) {
        clearTimeout(updateTimeout);
        updateTimeout = setTimeout(inserirMetaUnica, 100);
      }
    });

    observer.observe(document.body, {
      childList: true,
      subtree: true,
    });
  }

  // Inicializar sistema
  inicializar();

  // Iniciar observador
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", iniciarObservador);
  } else {
    iniciarObservador();
  }

  console.log(
    "✅ Sistema de meta otimizado carregado - CSS controla posicionamento!"
  );
})();
// ========================================================================================================================
//                                      FIM CODIGO META FIXA E META TURBO
// ========================================================================================================================
