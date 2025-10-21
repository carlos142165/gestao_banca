/**
 * MOSTRAR PLANO DO USUÁRIO
 * =======================
 * Script simples e direto
 */

function carregarEMostrarPlano() {
  console.log("🚀 Carregando plano...");

  fetch("obter-plano-usuario.php")
    .then((r) => r.json())
    .then((dados) => {
      if (dados.sucesso) {
        console.log("✅ Plano:", dados.plano.nome);
        mostrarPlano(dados.plano);
      }
    })
    .catch((err) => console.error("❌ Erro:", err));
}

function mostrarPlano(plano) {
  // Cores
  const cores = {
    GRATUITO: "#95a5a6",
    PRATA: "#c0392b",
    OURO: "#f39c12",
    DIAMANTE: "#2980b9",
  };

  // Ícones
  const icones = {
    GRATUITO: "fas fa-gift",
    PRATA: "fas fa-coins",
    OURO: "fas fa-star",
    DIAMANTE: "fas fa-gem",
  };

  const cor = cores[plano.nome] || "#333";
  const icone = icones[plano.nome] || "fas fa-info-circle";

  // Encontrar o container
  const container = document.getElementById("badge-plano-usuario");
  if (!container) {
    console.error("❌ Container não encontrado!");
    return;
  }

  // Montar HTML bem compacto
  let html = `
    <i class="${icone}" style="font-size: 14px; color: ${cor}; margin-right: 6px;"></i>
    <strong style="color: #333;">Plano:</strong>
    <span style="font-weight: 700; color: ${cor}; text-transform: uppercase; margin-left: 4px;">
      ${plano.nome}
    </span>
  `;

  // Adicionar data e dias se não for gratuito
  if (plano.nome !== "GRATUITO" && plano.data_fim) {
    const data = new Date(plano.data_fim);
    const dataFormatada = data.toLocaleDateString("pt-BR");

    html += `
      <span style="color: #ddd; margin: 0 6px;">|</span>
      <span style="color: #666; font-size: 11px;">📅 ${dataFormatada}</span>
    `;

    if (plano.dias_restantes !== null && plano.dias_restantes >= 0) {
      html += `
        <span style="color: #ddd; margin: 0 6px;">|</span>
        <span style="color: #666; font-size: 11px;">⏳ ${plano.dias_restantes}d</span>
      `;
    }
  }

  // Atualizar o border color do container
  container.style.borderLeftColor = cor;

  container.innerHTML = html;
  container.style.display = "inline-flex";
  container.style.alignItems = "center";

  console.log("✅ Plano exibido!");
}

// Inicializar ao carregar
document.addEventListener("DOMContentLoaded", () => {
  carregarEMostrarPlano();
  // Atualizar a cada 5 minutos
  setInterval(carregarEMostrarPlano, 5 * 60 * 1000);
});

// Se documento já está carregado
if (
  document.readyState === "complete" ||
  document.readyState === "interactive"
) {
  carregarEMostrarPlano();
}
