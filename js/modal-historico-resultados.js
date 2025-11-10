// ✅ MODAL DE HISTÓRICO DE RESULTADOS
let modalHistoricoAberto = false;
let ultimoPayloadEnviado = null; // ✅ DEBUG: Armazenar último payload

async function abrirModalHistorico(elemento) {
  const time1 = elemento.dataset.time1;
  const time2 = elemento.dataset.time2;
  const tipo = elemento.dataset.tipo; // 'gols' ou 'cantos'
  const valorOver = elemento.dataset.valorover; // novo: valor de over
  const filtrarSemReembolso = elemento.dataset.filtrarSemReembolso === "true"; // ✅ NOVO

  console.log("🎯 [abrirModalHistorico] Dados extraídos do elemento:");
  console.log("   - time1:", time1, "(tipo:", typeof time1 + ")");
  console.log("   - time2:", time2, "(tipo:", typeof time2 + ")");
  console.log("   - tipo:", tipo, "(tipo:", typeof tipo + ")");
  console.log(
    "   - valorOver:",
    valorOver,
    "(tipo:",
    typeof valorOver + ", undefined?",
    valorOver === undefined,
    "empty?",
    valorOver === ""
  );
  console.log("   - filtrarSemReembolso:", filtrarSemReembolso); // ✅ NOVO

  // Criar modal se não existir
  let modal = document.getElementById("modalHistoricoResultados");
  if (!modal) {
    modal = document.createElement("div");
    modal.id = "modalHistoricoResultados";
    modal.className = "modal-historico-overlay";
    document.body.appendChild(modal);
  }

  // ✅ NOVO: Armazenar os parâmetros no modal para uso posterior
  modal.dataset.time1 = time1;
  modal.dataset.time2 = time2;
  modal.dataset.tipo = tipo;
  modal.dataset.valorOver = valorOver || "";
  modal.dataset.filtrarSemReembolso = filtrarSemReembolso ? "true" : "false";

  // Mostrar modal e carregar dados
  modal.style.display = "flex";
  modalHistoricoAberto = true;

  // Carregar histórico do banco de dados
  await carregarHistoricoResultados(
    time1,
    time2,
    tipo,
    valorOver,
    modal,
    filtrarSemReembolso
  ); // ✅ NOVO PARAM

  // Fechar ao clicar no overlay
  modal.onclick = function (e) {
    if (e.target === modal) {
      fecharModalHistorico();
    }
  };
}

function fecharModalHistorico() {
  const modal = document.getElementById("modalHistoricoResultados");
  if (modal) {
    modal.style.display = "none";
    modalHistoricoAberto = false;
  }
}

async function carregarHistoricoResultados(
  time1,
  time2,
  tipo,
  valorOver,
  modal,
  filtrarSemReembolso = false // ✅ NOVO
) {
  try {
    console.log(
      `📊 Carregando histórico: ${time1} vs ${time2} (${tipo}) - Over: ${
        valorOver || "sem filtro"
      }`
    );
    console.log(`   Tipo recebido: "${tipo}" (é cantos? ${tipo === "cantos"})`);
    console.log(
      `   valorOver recebido: "${valorOver}" (undefined? ${
        valorOver === undefined
      }) (empty? ${valorOver === ""})`
    );
    console.log("   filtrarSemReembolso:", filtrarSemReembolso); // ✅ NOVO

    // Requisição ao servidor para buscar últimos 10 jogos de cada time
    const payload = {
      time1: time1,
      time2: time2,
      tipo: tipo,
      limite: 10,
    };

    // ✅ NOVO: Adicionar valorOver ao payload se existir e não for vazio
    if (valorOver && valorOver !== "") {
      // Normalizar: "1.00" -> "1", "0.50" -> "0.5", "2.50" -> "2.5"
      let valorNormalizado = parseFloat(valorOver).toString();
      console.log(
        "🎯 Adicionando valorOver ao payload:",
        valorOver,
        "→ normalizado:",
        valorNormalizado,
        "(type:",
        typeof valorNormalizado + ")"
      );
      payload.valorOver = valorNormalizado;
    } else {
      console.log("⚠️ valorOver vazio/undefined, não adicionando ao payload");
      console.log("   Valor recebido:", valorOver, "type:", typeof valorOver);
    }

    // ✅ NOVO: Adicionar filtro de reembolso ao payload se ativado
    if (filtrarSemReembolso) {
      payload.filtrarSemReembolso = true;
      console.log("🚫 Adicionando filtro para excluir REEMBOLSO");
    } else {
      payload.filtrarSemReembolso = false;
      console.log("✅ Filtro de reembolso DESATIVADO - todos os resultados");
    }

    console.log("📤 Payload sendo enviado:", JSON.stringify(payload));
    console.log(
      "📤 Checando: payload.valorOver =",
      payload.valorOver,
      "undefined?",
      payload.valorOver === undefined
    );
    console.log(
      "📤 Checando: payload.filtrarSemReembolso =",
      payload.filtrarSemReembolso
    ); // ✅ NOVO
    console.warn("⚠️ ⚠️ ⚠️ PAYLOAD COMPLETO: " + JSON.stringify(payload)); // ✅ DEBUG EXTRA
    ultimoPayloadEnviado = payload; // ✅ ARMAZENAR PARA DEBUG

    // ✅ VERIFICAÇÃO EXTRA: Se não tem filtrarSemReembolso, avisar
    if (!payload.filtrarSemReembolso) {
      console.warn("⚠️⚠️⚠️ ATENÇÃO: filtrarSemReembolso NÃO está no payload!");
      console.warn(
        "filtrarSemReembolso recebido na função: " + filtrarSemReembolso
      );
    } else {
      console.log("✅✅✅ filtrarSemReembolso ESTÁ no payload e é TRUE");
    }

    const response = await fetch("api/obter-historico-resultados.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(payload),
    });

    console.log("📡 Status da resposta:", response.status);

    // Tratar respostas não-OK com corpo de erro
    let data;
    const contentType = response.headers.get("content-type") || "";
    if (!response.ok) {
      // tentar ler JSON de erro, senão texto puro
      if (contentType.indexOf("application/json") !== -1) {
        data = await response.json().catch(() => null);
        const err =
          data && data.error
            ? data.error
            : `Erro do servidor (status ${response.status})`;
        console.error(
          "❌ Erro HTTP ao carregar histórico:",
          response.status,
          err
        );
        renderizarModalErro(modal, "Erro: " + err);
        return;
      } else {
        const text = await response
          .text()
          .catch(() => "Resposta inesperada do servidor");
        console.error(
          "❌ Erro HTTP ao carregar histórico:",
          response.status,
          text
        );
        renderizarModalErro(modal, `Erro do servidor: ${text}`);
        return;
      }
    }

    // Resposta OK - tentar parse JSON
    if (contentType.indexOf("application/json") !== -1) {
      data = await response.json().catch((err) => {
        console.error("❌ Falha ao parsear JSON:", err);
        renderizarModalErro(modal, "Resposta inválida do servidor");
        return null;
      });
    } else {
      const text = await response
        .text()
        .catch(() => "Resposta inesperada do servidor");
      console.error("❌ Conteúdo inesperado:", text);
      renderizarModalErro(modal, "Resposta inesperada do servidor");
      return;
    }

    if (!data) return;

    if (data.success) {
      renderizarModalHistorico(data, modal, time1, time2, tipo);
    } else {
      console.error("❌ Erro ao carregar histórico:", data.error);
      renderizarModalErro(modal, data.error || "Erro desconhecido");
    }
  } catch (error) {
    console.error("❌ Erro na requisição:", error);
    renderizarModalErro(modal, "Erro ao carregar dados");
  }
}

function renderizarModalHistorico(data, modal, time1, time2, tipo, limite = 5) {
  const historicoTime1 = data.time1_historico || [];
  const historicoTime2 = data.time2_historico || [];

  // Limitar para 5 jogos inicialmente
  const resultados1 = historicoTime1.slice(0, limite);
  const resultados2 = historicoTime2.slice(0, limite);

  // ✅ SINCRONIZAR RESULTADOS GREEN - VERSÃO MELHORADA
  // IMPORTANTE: Se há filtro de OVER, NÃO sincronizar porque são apostas diferentes!
  // Exemplo: +0.5 GOL e +1 GOL são apostas DIFERENTES mesmo para o mesmo jogo
  const temFiltroOver = data.filtro_ativado === true;

  console.log("═══════════════════════════════════════════════════════════");
  console.log("🔍 SINCRONIZAÇÃO DE RESULTADOS");
  console.log("═══════════════════════════════════════════════════════════");
  console.log("✅ data.filtro_ativado:", data.filtro_ativado);
  console.log("✅ temFiltroOver:", temFiltroOver);
  console.log(
    "📋 Time1 resultados (qtd:" + resultados1.length + "):",
    resultados1
  );
  console.log(
    "📋 Time2 resultados (qtd:" + resultados2.length + "):",
    resultados2
  );

  if (temFiltroOver) {
    console.log(
      "🛑 FILTRO DE OVER ATIVO → NÃO sincronizar (apostas diferentes)"
    );
  } else {
    console.log("✅ Sem filtro de OVER → Sincronizar (mesmo jogo)");
  }

  // Se NÃO tem filtro de OVER, sincronizar resultados (como antes)
  if (!temFiltroOver) {
    console.log("🟢 Iniciando sincronização...");
    resultados1.forEach((jogo1, idx1) => {
      if (jogo1.resultado === "GREEN" || jogo1.resultado === "green") {
        console.log(
          `🟢 Time1[${idx1}] é GREEN - buscando correspondente em Time2...`
        );

        // Procurar jogo de mesma data E que envolva os mesmos times
        const jogoCorrespondente = resultados2.find((jogo2) => {
          const mesmaData = jogo2.data_criacao === jogo1.data_criacao;
          const mesmosTeams =
            (jogo2.time_1.toLowerCase() === jogo1.time_1.toLowerCase() &&
              jogo2.time_2.toLowerCase() === jogo1.time_2.toLowerCase()) ||
            (jogo2.time_1.toLowerCase() === jogo1.time_2.toLowerCase() &&
              jogo2.time_2.toLowerCase() === jogo1.time_1.toLowerCase());

          console.log(`  Comparando: data=${mesmaData}, teams=${mesmosTeams}`);
          return mesmaData && mesmosTeams;
        });

        if (jogoCorrespondente) {
          console.log(`✅ Encontrado correspondente! Sincronizando para GREEN`);
          jogoCorrespondente.resultado = "GREEN";
        } else {
          console.log(`❌ Não encontrado correspondente`);
        }
      }
    });

    // Também sincronizar time2 para time1
    resultados2.forEach((jogo2, idx2) => {
      if (jogo2.resultado === "GREEN" || jogo2.resultado === "green") {
        console.log(
          `🟢 Time2[${idx2}] é GREEN - buscando correspondente em Time1...`
        );

        const jogoCorrespondente = resultados1.find((jogo1) => {
          const mesmaData = jogo1.data_criacao === jogo2.data_criacao;
          const mesmosTeams =
            (jogo1.time_1.toLowerCase() === jogo2.time_1.toLowerCase() &&
              jogo1.time_2.toLowerCase() === jogo2.time_2.toLowerCase()) ||
            (jogo1.time_1.toLowerCase() === jogo2.time_2.toLowerCase() &&
              jogo1.time_2.toLowerCase() === jogo2.time_1.toLowerCase());

          console.log(`  Comparando: data=${mesmaData}, teams=${mesmosTeams}`);
          return mesmaData && mesmosTeams;
        });

        if (jogoCorrespondente) {
          console.log(`✅ Encontrado correspondente! Sincronizando para GREEN`);
          jogoCorrespondente.resultado = "GREEN";
        } else {
          console.log(`❌ Não encontrado correspondente`);
        }
      }
    });
  } else {
    console.log("🛑 ⚠️ FILTRO DE OVER ATIVO");
    console.log("🛑 NÃO sincronizando (são apostas diferentes)");
    console.log("🛑 Time1 apresentado AS-IS");
    console.log("🛑 Time2 apresentado AS-IS");
  }

  console.log("═══════════════════════════════════════════════════════════");
  console.log("� RESULTADO FINAL PARA RENDERIZAÇÃO:");
  console.log("═══════════════════════════════════════════════════════════");
  console.log("Time1 resultados:", resultados1);
  console.log("Time2 resultados:", resultados2);
  console.log("Time2 resultados:", resultados2);

  // ✅ IDENTIFICAR CONFRONTO DIRETO E PRIORIZAR
  // Quando os dois times já se enfrentaram, colocar esse jogo em primeiro lugar com destaque
  const confrontoDireto1 = [];
  const outrosJogos1 = [];

  resultados1.forEach((jogo) => {
    const isConfrontoDireto =
      (jogo.time_1.toLowerCase() === time1.toLowerCase() &&
        jogo.time_2.toLowerCase() === time2.toLowerCase()) ||
      (jogo.time_1.toLowerCase() === time2.toLowerCase() &&
        jogo.time_2.toLowerCase() === time1.toLowerCase());

    if (isConfrontoDireto) {
      confrontoDireto1.push({ ...jogo, confrontoDireto: true });
    } else {
      outrosJogos1.push({ ...jogo, confrontoDireto: false });
    }
  });

  const confrontoDireto2 = [];
  const outrosJogos2 = [];

  resultados2.forEach((jogo) => {
    const isConfrontoDireto =
      (jogo.time_1.toLowerCase() === time1.toLowerCase() &&
        jogo.time_2.toLowerCase() === time2.toLowerCase()) ||
      (jogo.time_1.toLowerCase() === time2.toLowerCase() &&
        jogo.time_2.toLowerCase() === time1.toLowerCase());

    if (isConfrontoDireto) {
      confrontoDireto2.push({ ...jogo, confrontoDireto: true });
    } else {
      outrosJogos2.push({ ...jogo, confrontoDireto: false });
    }
  });

  // Reorganizar: confrontos diretos primeiro, depois outros jogos
  const resultados1Ordenados = [...confrontoDireto1, ...outrosJogos1];
  const resultados2Ordenados = [...confrontoDireto2, ...outrosJogos2];

  // Calcular acurácia individual
  const acuracia1 = calcularAcuracia(resultados1Ordenados);
  const acuracia2 = calcularAcuracia(resultados2Ordenados);

  // Média das duas acurácias
  const acuraciaMedia =
    resultados1Ordenados.length > 0 || resultados2Ordenados.length > 0
      ? Math.round((acuracia1 + acuracia2) / 2)
      : 0;

  // HTML do modal
  // Determinar qual imagem usar baseado no tipo
  const imagemTipo = tipo.toLowerCase() === "cantos" ? "cantos.jpg" : "gol.jpg";

  // Determinar o título e ícone baseado no tipo
  let tituloModal = "";
  let iconeModal = "";
  if (tipo.toLowerCase() === "cantos" || tipo.toLowerCase() === "canto") {
    tituloModal = "Resultados de Escanteios";
    iconeModal = "🚩";
  } else {
    tituloModal = "Resultados de Gols";
    iconeModal = "⚽";
  }

  const html = `
    <div class="modal-historico-conteudo">
      <!-- Header com Imagem do Tipo -->
      <div class="modal-historico-header">
        <img src="img/${imagemTipo}" alt="${tipo}" class="modal-historico-tipo-imagem" />
        <h2>${iconeModal} ${tituloModal}</h2>
        <button class="modal-historico-fechar" onclick="fecharModalHistorico()">✕</button>
      </div>

      <!-- Filtro de jogos -->
      <div class="modal-historico-filtro">
        <label>Últimos:</label>
        <select id="seletorLimite" onchange="atualizarModalHistorico('${time1}', '${time2}', '${tipo}')">
          <option value="5" selected>5 Jogos</option>
          <option value="10">10 Jogos</option>
        </select>
      </div>

      <!-- Conteúdo dos resultados -->
      <div class="modal-historico-body">
        <!-- Time 1 -->
        <div class="historico-time-coluna">
          <div class="historico-time-header">
            <h3>${limparNomeTime(time1)}</h3>
          </div>
          <div class="historico-resultados">
            ${resultados1Ordenados
              .map(
                (resultado) => `
              <div class="historico-resultado ${getClasseResultado(
                resultado.resultado
              )} ${
                  resultado.confrontoDireto ? "confronto-direto" : ""
                }" title="${resultado.time_1} vs ${resultado.time_2}">
                <span class="historico-resultado-icone">${getIconeResultado(
                  resultado.resultado
                )}</span>
                <div style="display: flex; flex-direction: column; gap: 2px; flex: 1; min-width: 0;">
                  <span class="historico-data">${new Date(
                    resultado.data_criacao
                  ).toLocaleDateString("pt-BR")}</span>
                  <span style="font-size: 11px; color: #555; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: inline-flex; align-items: center; gap: 4px;" title="Adversário de ${time1}">
                    ${limparNomeTime(getAdversario(resultado, time1))}
                  </span>
                </div>
              </div>
            `
              )
              .join("")}
            ${
              resultados1Ordenados.length === 0
                ? '<div class="historico-vazio">Sem dados</div>'
                : ""
            }
          </div>
        </div>

        <!-- Acurácia Central -->
        <div class="historico-acuracia-container">
          <div class="historico-acuracia">
            <div class="acuracia-valor">${acuraciaMedia}%</div>
            <div class="acuracia-label">Precisão</div>
          </div>
        </div>

        <!-- Time 2 -->
        <div class="historico-time-coluna">
          <div class="historico-time-header">
            <h3>${limparNomeTime(time2)}</h3>
          </div>
          <div class="historico-resultados">
            ${resultados2Ordenados
              .map(
                (resultado) => `
              <div class="historico-resultado ${getClasseResultado(
                resultado.resultado
              )} ${
                  resultado.confrontoDireto ? "confronto-direto" : ""
                }" title="${resultado.time_1} vs ${resultado.time_2}">
                <span class="historico-resultado-icone">${getIconeResultado(
                  resultado.resultado
                )}</span>
                <div style="display: flex; flex-direction: column; gap: 2px; flex: 1; min-width: 0;">
                  <span class="historico-data">${new Date(
                    resultado.data_criacao
                  ).toLocaleDateString("pt-BR")}</span>
                  <span style="font-size: 11px; color: #555; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: inline-flex; align-items: center; gap: 4px;" title="Adversário de ${time2}">
                    ${limparNomeTime(getAdversario(resultado, time2))}
                  </span>
                </div>
              </div>
            `
              )
              .join("")}
            ${
              resultados2Ordenados.length === 0
                ? '<div class="historico-vazio">Sem dados</div>'
                : ""
            }
          </div>
        </div>
      </div>

      <!-- Footer com informações -->
      <div class="modal-historico-footer">
        <p>Tipo: <strong>${tipo.toUpperCase()}</strong> | Total de jogos analisados: <strong>${
    resultados1Ordenados.length + resultados2Ordenados.length
  }</strong></p>
      </div>
    </div>
  `;

  modal.innerHTML = html;
}

function getClasseResultado(resultado) {
  if (resultado === "green" || resultado === "GREEN") return "resultado-green";
  if (resultado === "red" || resultado === "RED") return "resultado-red";
  if (resultado === "reembolso" || resultado === "REEMBOLSO")
    return "resultado-reembolso";
  return "resultado-pendente";
}

function getIconeResultado(resultado) {
  if (resultado === "green" || resultado === "GREEN") return "✅";
  if (resultado === "red" || resultado === "RED") return "❌";
  if (resultado === "reembolso" || resultado === "REEMBOLSO") return "↩️";
  return "⏳";
}

function getIconeTipo(tipo) {
  // ✅ NOVO: Retorna o ícone baseado no tipo de aposta
  const tipoLower = (tipo || "").toLowerCase();

  if (tipoLower === "gols" || tipoLower === "gol") {
    return "⚽"; // Bola para Gols
  } else if (tipoLower === "cantos" || tipoLower === "canto") {
    return "🚩"; // Bandeira para Cantos
  } else {
    return "⚽"; // Padrão: Bola
  }
}

function limparNomeTime(nomeTime) {
  // ✅ NOVO: Remove ícones de bola e espaços extras que vêm do banco de dados
  let nomelimpo = nomeTime
    .replace(/⚽/g, "") // Remove bola
    .replace(/🚩/g, "") // Remove bandeira
    .replace(/[\u00A0]/g, " ") // Converte espaços não-quebrável para espaço normal
    .trim(); // Remove espaços nas pontas

  // Remover múltiplos espaços em branco consecutivos
  nomelimpo = nomelimpo.replace(/\s+/g, " ").trim();

  return nomelimpo;
}

function getAdversario(jogo, timePrincipal) {
  // ✅ CORRIGIDO: Retorna o ADVERSÁRIO do time principal
  // Remove emojis e espaços para comparação segura
  const limpar = (s) =>
    s
      .replace(/⚽|🚩|[\u00A0]/g, "")
      .trim()
      .toLowerCase();

  const p = limpar(timePrincipal);
  const t1 = limpar(jogo.time_1);
  const t2 = limpar(jogo.time_2);

  // Se timePrincipal é time_1, retorna time_2
  if (p === t1) return jogo.time_2;
  // Se timePrincipal é time_2, retorna time_1
  if (p === t2) return jogo.time_1;

  // Fallback: tenta com CONTAINS
  if (t1.includes(p) || p.includes(t1)) return jogo.time_2;
  if (t2.includes(p) || p.includes(t2)) return jogo.time_1;

  // Se nada funcionar, retorna time_2
  return jogo.time_2;
}

function calcularAcuracia(resultados) {
  if (resultados.length === 0) return 0;

  let acertos = 0;
  let total = resultados.length;

  resultados.forEach((resultado) => {
    if (resultado.resultado === "green" || resultado.resultado === "GREEN") {
      acertos += 1;
    } else if (
      resultado.resultado === "reembolso" ||
      resultado.resultado === "REEMBOLSO"
    ) {
      acertos += 0.5; // Reembolso vale 50%
    }
  });

  const percentual = Math.round((acertos / total) * 100);
  return percentual;
}

function renderizarModalErro(modal, mensagem) {
  const html = `
    <div class="modal-historico-conteudo">
      <div class="modal-historico-header">
        <h2>⚠️ Erro ao Carregar</h2>
        <button class="modal-historico-fechar" onclick="fecharModalHistorico()">✕</button>
      </div>
      <div class="modal-historico-body" style="padding: 40px 20px; text-align: center;">
        <p style="color: #d32f2f; font-size: 16px;">${mensagem}</p>
      </div>
    </div>
  `;

  modal.innerHTML = html;
}

async function atualizarModalHistorico(time1, time2, tipo) {
  const modal = document.getElementById("modalHistoricoResultados");
  const novoLimite = document.getElementById("seletorLimite").value;

  // ✅ NOVO: Recuperar parâmetros do modal se disponíveis
  const valorOver = modal?.dataset.valorOver || "";
  const filtrarSemReembolso = modal?.dataset.filtrarSemReembolso === "true";

  console.log("🔄 Atualizando modal com novo limite:", novoLimite);
  console.log("   valorOver:", valorOver);
  console.log("   filtrarSemReembolso:", filtrarSemReembolso);

  try {
    const payload = {
      time1: time1,
      time2: time2,
      tipo: tipo,
      limite: parseInt(novoLimite),
    };

    // ✅ NOVO: Adicionar parâmetros de filtro
    if (valorOver) payload.valorOver = valorOver;
    if (filtrarSemReembolso) payload.filtrarSemReembolso = true;

    const response = await fetch("api/obter-historico-resultados.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(payload),
    });

    const data = await response.json();

    if (data.success) {
      const historicoTime1 = data.time1_historico || [];
      const historicoTime2 = data.time2_historico || [];

      const resultados1 = historicoTime1.slice(0, parseInt(novoLimite));
      const resultados2 = historicoTime2.slice(0, parseInt(novoLimite));

      // Atualizar apenas os resultados
      const coluna1 = modal.querySelector(
        ".historico-time-coluna:nth-child(1) .historico-resultados"
      );
      const coluna2 = modal.querySelector(
        ".historico-time-coluna:nth-child(3) .historico-resultados"
      );

      coluna1.innerHTML =
        resultados1
          .map(
            (resultado) => `
        <div class="historico-resultado ${getClasseResultado(
          resultado.resultado
        )}">
          <span class="historico-resultado-icone">${getIconeResultado(
            resultado.resultado
          )}</span>
          <div style="display: flex; flex-direction: column; gap: 2px; flex: 1; min-width: 0;">
            <span class="historico-data">${new Date(
              resultado.data_criacao
            ).toLocaleDateString("pt-BR")}</span>
            <span style="font-size: 11px; color: #555; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: inline-flex; align-items: center; gap: 4px;" title="Adversário de ${time1}">
              ${limparNomeTime(getAdversario(resultado, time1))}
            </span>
          </div>
        </div>
      `
          )
          .join("") || '<div class="historico-vazio">Sem dados</div>';

      coluna2.innerHTML =
        resultados2
          .map(
            (resultado) => `
        <div class="historico-resultado ${getClasseResultado(
          resultado.resultado
        )}">
          <span class="historico-resultado-icone">${getIconeResultado(
            resultado.resultado
          )}</span>
          <div style="display: flex; flex-direction: column; gap: 2px; flex: 1; min-width: 0;">
            <span class="historico-data">${new Date(
              resultado.data_criacao
            ).toLocaleDateString("pt-BR")}</span>
            <span style="font-size: 11px; color: #555; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: inline-flex; align-items: center; gap: 4px;" title="Adversário de ${time2}">
              ${limparNomeTime(getAdversario(resultado, time2))}
            </span>
          </div>
        </div>
      `
          )
          .join("") || '<div class="historico-vazio">Sem dados</div>';

      // Atualizar acurácia
      const acuracia1 = calcularAcuracia(resultados1);
      const acuracia2 = calcularAcuracia(resultados2);
      const mediac = Math.round((acuracia1 + acuracia2) / 2);

      modal.querySelector(".acuracia-valor").textContent = mediac + "%";
    }
  } catch (error) {
    console.error("❌ Erro ao atualizar:", error);
  }
}

// Fechar modal ao pressionar ESC
document.addEventListener("keydown", function (e) {
  if (e.key === "Escape" && modalHistoricoAberto) {
    fecharModalHistorico();
  }
});
