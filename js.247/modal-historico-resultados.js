// ✅ MODAL DE HISTÓRICO DE RESULTADOS
let modalHistoricoAberto = false;

async function abrirModalHistorico(elemento) {
  const time1 = elemento.dataset.time1;
  const time2 = elemento.dataset.time2;
  const tipo = elemento.dataset.tipo; // 'gols' ou 'cantos'

  console.log(`📊 Abrindo histórico: ${time1} vs ${time2} (${tipo})`);

  // Criar modal se não existir
  let modal = document.getElementById("modalHistoricoResultados");
  if (!modal) {
    modal = document.createElement("div");
    modal.id = "modalHistoricoResultados";
    modal.className = "modal-historico-overlay";
    document.body.appendChild(modal);
  }

  // Mostrar modal e carregar dados
  modal.style.display = "flex";
  modalHistoricoAberto = true;

  // Carregar histórico do banco de dados
  await carregarHistoricoResultados(time1, time2, tipo, modal);

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

async function carregarHistoricoResultados(time1, time2, tipo, modal) {
  try {
    console.log(`📊 Carregando histórico: ${time1} vs ${time2} (${tipo})`);

    // Requisição ao servidor para buscar últimos 10 jogos de cada time
    const response = await fetch("api/obter-historico-resultados.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        time1: time1,
        time2: time2,
        tipo: tipo,
        limite: 10,
      }),
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
  const confrontosDiretos = data.confrontos_diretos || [];

  // ✅ SEPARAR CONFRONTOS DIRETOS DOS OUTROS JOGOS
  const resultados1 = historicoTime1.slice(0, limite);
  const resultados2 = historicoTime2.slice(0, limite);

  // Identificar quais resultados1 são confrontos diretos
  const diretos1 = resultados1.filter((r) => confrontosDiretos.includes(r.id));
  const outros1 = resultados1.filter((r) => !confrontosDiretos.includes(r.id));

  // Identificar quais resultados2 são confrontos diretos
  const diretos2 = resultados2.filter((r) => confrontosDiretos.includes(r.id));
  const outros2 = resultados2.filter((r) => !confrontosDiretos.includes(r.id));

  // ✅ REORDENAR: Confrontos diretos em cima, depois outros
  const ordenados1 = [...diretos1, ...outros1];
  const ordenados2 = [...diretos2, ...outros2];

  console.log("⚔️ Confrontos diretos encontrados:", confrontosDiretos);
  console.log("Time 1 - Diretos:", diretos1.length, "Outros:", outros1.length);
  console.log("Time 2 - Diretos:", diretos2.length, "Outros:", outros2.length);

  // Calcular acurácia
  const acuracia1 = calcularAcuracia(ordenados1);
  const acuracia2 = calcularAcuracia(ordenados2);
  const acuraciaMedia =
    ordenados1.length > 0 || ordenados2.length > 0
      ? Math.round((acuracia1 + acuracia2) / 2)
      : 0;

  // HTML do modal
  const html = `
    <div class="modal-historico-conteudo">
      <!-- Header -->
      <div class="modal-historico-header">
        <h2>📊 Últimos Resultados</h2>
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
            <h3>${time1}</h3>
          </div>
          <div class="historico-resultados">
            ${ordenados1
              .map((resultado, idx) => {
                const isDirecto = confrontosDiretos.includes(resultado.id);
                const adversario =
                  resultado.time_1 === time1
                    ? resultado.time_2
                    : resultado.time_1;
                return `
              <div class="historico-resultado ${getClasseResultado(
                resultado.resultado
              )}${isDirecto ? " historico-direto" : ""}" title="${
                  resultado.time_1
                } vs ${resultado.time_2}">
                <span class="historico-resultado-icone">${getIconeResultado(
                  resultado.resultado
                )}</span>
                <div style="display: flex; flex-direction: column; gap: 2px; flex: 1;">
                  <span class="historico-data">${new Date(
                    resultado.data_criacao
                  ).toLocaleDateString("pt-BR")}</span>
                  <span style="font-size: 11px; color: #555; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    ${adversario}${isDirecto ? " ⚔️" : ""}
                  </span>
                </div>
              </div>
            `;
              })
              .join("")}
            ${
              ordenados1.length === 0
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
            <h3>${time2}</h3>
          </div>
          <div class="historico-resultados">
            ${ordenados2
              .map((resultado, idx) => {
                const isDirecto = confrontosDiretos.includes(resultado.id);
                const adversario =
                  resultado.time_1 === time2
                    ? resultado.time_2
                    : resultado.time_1;
                return `
              <div class="historico-resultado ${getClasseResultado(
                resultado.resultado
              )}${isDirecto ? " historico-direto" : ""}" title="${
                  resultado.time_1
                } vs ${resultado.time_2}">
                <span class="historico-resultado-icone">${getIconeResultado(
                  resultado.resultado
                )}</span>
                <div style="display: flex; flex-direction: column; gap: 2px; flex: 1;">
                  <span class="historico-data">${new Date(
                    resultado.data_criacao
                  ).toLocaleDateString("pt-BR")}</span>
                  <span style="font-size: 11px; color: #555; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    ${adversario}${isDirecto ? " ⚔️" : ""}
                  </span>
                </div>
              </div>
            `;
              })
              .join("")}
            ${
              ordenados2.length === 0
                ? '<div class="historico-vazio">Sem dados</div>'
                : ""
            }
          </div>
        </div>
      </div>

      <!-- Footer com informações -->
      <div class="modal-historico-footer">
        <p>Tipo: <strong>${tipo.toUpperCase()}</strong> | Total de jogos analisados: <strong>${
    ordenados1.length + ordenados2.length
  }</strong> | Confrontos diretos: <strong>${
    confrontosDiretos.length
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
  const novoLimite = document.getElementById("seletorLimite").value;
  const modal = document.getElementById("modalHistoricoResultados");

  try {
    const response = await fetch("api/obter-historico-resultados.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        time1: time1,
        time2: time2,
        tipo: tipo,
        limite: parseInt(novoLimite),
      }),
    });

    const data = await response.json();

    if (data.success) {
      renderizarModalHistorico(
        data,
        modal,
        time1,
        time2,
        tipo,
        parseInt(novoLimite)
      );
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
