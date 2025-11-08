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

  // Limitar para 5 jogos inicialmente
  const resultados1 = historicoTime1.slice(0, limite);
  const resultados2 = historicoTime2.slice(0, limite);

  // ✅ SINCRONIZAR RESULTADOS GREEN - VERSÃO MELHORADA
  // Se um jogo foi GREEN, ambos os times devem ver como GREEN
  // Comparar pela DATA E pelos TIMES envolvidos para identificar o mesmo jogo

  console.log("🔍 Antes da sincronização:");
  console.log("Time1 resultados:", resultados1);
  console.log("Time2 resultados:", resultados2);

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

  console.log("🔍 Após sincronização:");
  console.log("Time1 resultados:", resultados1);
  console.log("Time2 resultados:", resultados2);

  // Calcular acurácia individual
  const acuracia1 = calcularAcuracia(resultados1);
  const acuracia2 = calcularAcuracia(resultados2);

  // Média das duas acurácias
  const acuraciaMedia =
    resultados1.length > 0 || resultados2.length > 0
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
            ${resultados1
              .map(
                (resultado) => `
              <div class="historico-resultado ${getClasseResultado(
                resultado.resultado
              )}" title="${resultado.time_1} vs ${resultado.time_2}">
                <span class="historico-resultado-icone">${getIconeResultado(
                  resultado.resultado
                )}</span>
                <div style="display: flex; flex-direction: column; gap: 2px; flex: 1;">
                  <span class="historico-data">${new Date(
                    resultado.data_criacao
                  ).toLocaleDateString("pt-BR")}</span>
                  <span style="font-size: 11px; color: #555; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    ${
                      resultado.time_1 === "${time1}"
                        ? resultado.time_2
                        : resultado.time_1
                    }
                  </span>
                </div>
              </div>
            `
              )
              .join("")}
            ${
              resultados1.length === 0
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
            ${resultados2
              .map(
                (resultado) => `
              <div class="historico-resultado ${getClasseResultado(
                resultado.resultado
              )}" title="${resultado.time_1} vs ${resultado.time_2}">
                <span class="historico-resultado-icone">${getIconeResultado(
                  resultado.resultado
                )}</span>
                <div style="display: flex; flex-direction: column; gap: 2px; flex: 1;">
                  <span class="historico-data">${new Date(
                    resultado.data_criacao
                  ).toLocaleDateString("pt-BR")}</span>
                  <span style="font-size: 11px; color: #555; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    ${
                      resultado.time_1 === "${time2}"
                        ? resultado.time_2
                        : resultado.time_1
                    }
                  </span>
                </div>
              </div>
            `
              )
              .join("")}
            ${
              resultados2.length === 0
                ? '<div class="historico-vazio">Sem dados</div>'
                : ""
            }
          </div>
        </div>
      </div>

      <!-- Footer com informações -->
      <div class="modal-historico-footer">
        <p>Tipo: <strong>${tipo.toUpperCase()}</strong> | Total de jogos analisados: <strong>${
    resultados1.length + resultados2.length
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
          ${getTextoResultado(resultado)}
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
          ${getTextoResultado(resultado)}
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
