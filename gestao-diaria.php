<?php
session_start();
require_once 'config.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ✅ Verificação de sessão
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
    echo "<script>alert('Área de membros — faça seu login!'); window.location.href = 'home.php';</script>";
    exit();
}

// ✅ PROCESSAMENTO DAS AÇÕES
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {

    // 📌 Cadastro de Mentor
    if ($_POST['acao'] === 'cadastrar_mentor') {
        $usuario_id = $_SESSION['usuario_id'];
        $nome = $_POST['nome'];

        $foto_nome = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $extensao = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $foto_nome = uniqid() . '.' . $extensao;
            $caminho_destino = 'uploads/' . $foto_nome;
            if (!move_uploaded_file($_FILES['foto']['tmp_name'], $caminho_destino)) {
                die("Erro ao fazer upload da imagem.");
            }
        }

        $stmt = $conexao->prepare("INSERT INTO mentores (id_usuario, foto, nome, data_criacao) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iss", $usuario_id, $foto_nome, $nome);

        if ($stmt->execute()) {
            echo "<script>alert('✅ Mentor cadastrado com sucesso!'); window.location.href = 'gestao-diaria.php';</script>";
            exit;
        } else {
            echo "<script>alert('Erro ao cadastrar mentor'); window.location.href = 'gestao-diaria.php';</script>";
            exit;
        }
    }

    
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Gestão do Dia</title>
<style>

body, html {
  height: 100%;
  font-family: 'Poppins', sans-serif;
  background-color:rgb(235, 235, 235);
  margin: 0;
  padding: 0;
  color: #f5f5f5;
      
}


</style>
     
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
     <link rel="stylesheet" href="css/arqui.css">
     <script src="script.js" defer></script>
     
</head>
<body>



    <!-- CODIGO RESPONSAVEL PELOS VALORES DO TOPO PUXADO DA PAGINA MENU.PHP -->

    <div id="data-container"></div>
    <!-- A data será carregada aqui -->

    <div id="menu-placeholder"></div>
    <!-- Aqui o menu será carregado dinamicamente -->

    <script>
      // 📌 Carrega o menu externo (menu.html) dentro do menu-placeholder
      fetch("menu.php")
        .then((response) => response.text()) // Converte a resposta em texto
        .then((data) => {
          document.getElementById("menu-placeholder").innerHTML = data; // Insere o menu na página

          document
            .querySelector(".menu-button")
            .addEventListener("click", function () {
              // Adiciona um evento ao botão do menu
              var menu = document.getElementById("menu"); // Obtém o elemento do menu suspenso
              menu.style.display =
                menu.style.display === "block" ? "none" : "block"; // Alterna entre mostrar e esconder o menu
            });

          // 🛠️ Fecha o menu ao clicar fora dele
          document.addEventListener("click", function (event) {
            var menu = document.getElementById("menu");
            var menuButton = document.querySelector(".menu-button");

            if (menu && menuButton) {
              // Verifica se os elementos existem
              if (
                menu.style.display === "block" &&
                !menu.contains(event.target) &&
                !menuButton.contains(event.target)
              ) {
                menu.style.display = "none"; // Fecha o menu se o clique for fora
              }
            }
          });
        })
        .catch((error) => console.error("Erro ao carregar o menu:", error)); // Exibe erro caso ocorra problema ao carregar
    </script>

    <script src="scripts.js">
      // Carregando o script de data global
    </script>

<!-- FIM CODIGO RESPONSAVEL PELOS VALORES DO TOPO PUXADO DA PAGINA MENU.PHP -->






<!-- CODIGO RESPONSAVEL PELO VALOR  PLACAR E META DIARIA E SALDO -->
<div class="container-valores">
    
  <div class="pontuacao">

    <span class="placar-green">0</span>
    <span class="separador">x</span>
    <span class="placar-red">0</span>

  </div>



  <div class="informacoes-row">

   <div class="info-item">

    <div>
      <span class="valor-meta">R$ 1.000,00</span>
      <span class="rotulo-meta">Meta do Dia</span>
    </div>

  </div>


  <div class="info-item">

     <div>
      <span class="valor-saldo">R$ 0,00</span>
      <span class="rotulo-saldo">Saldo do Dia</span>
     </div>

  </div>


</div>
<!-- FIM DO CODIGO RESPONSAVEL PELO VALOR  PLACAR E META DIARIA E SALDO -->















<!-- CODIGO RESPONSAVEL PELO FORMULARIO QUE ADICIONA NOVO USUARIO -->

<!-- Modal -->
<div id="modal-form" class="modal">
  <div class="modal-conteudo">
    <span class="fechar" onclick="fecharModal()">&times;</span>
    
  <form method="POST" enctype="multipart/form-data" action="gestao-diaria.php" class="formulario-mentor-completo">
  <input type="hidden" name="acao" value="cadastrar_mentor">

  <div class="mentor-form-header">
    <h3 class="mentor-titulo">Cadastrar Mentor</h3>
  </div>

  <div class="input-group">
    <label for="nome" class="label-form">Nome do Mentor:</label>
    <input type="text" name="nome" id="nome" class="input-text" placeholder="Nome do Mentor" required>
  </div>

  <div class="input-group">
    <label for="foto" class="label-form">Foto do Mentor:</label>
    <input type="file" name="foto" id="foto" class="input-file" onchange="mostrarNomeArquivo(this)" required>
    <span id="nome-arquivo" class="nome-arquivo">Nenhum arquivo selecionado</span>
  </div>

  <div class="preview-foto-wrapper">
    <img id="preview-img" src="https://cdn-icons-png.flaticon.com/512/847/847969.png" class="preview-img" alt="Pré-visualização">
    <button type="button" id="remover-foto" class="btn-remover-foto" onclick="removerImagem()" style="display:none;">Remover Foto</button>
  </div>

  <div class="botoes-formulario">
    <button type="submit" class="btn-enviar">Cadastrar Mentor</button>
  </div>
</form>

  </div>
 </div>

 
</div>

<!-- FIM DO CODIGO RESPONSAVEL PELO FORMULARIO QUE ADICIONA NOVO USUARIO -->











<!-- CODIGO RESPONSAVEL PELO CAMPO ONDE OS MENTORES VAO FICAR -->


<!-- Lista de Mentores -->
<!-- Lista de Mentores -->
<div class="campo_mentores">
  <div class="mentor-wrapper">
    <?php
    $id_usuario_logado = $_SESSION['usuario_id'];
    $sql_mentores = "SELECT id, nome, foto FROM mentores WHERE id_usuario = ?";
    $stmt_mentores = $conexao->prepare($sql_mentores);
    $stmt_mentores->bind_param("i", $id_usuario_logado);
    $stmt_mentores->execute();
    $result_mentores = $stmt_mentores->get_result();
    
    $hoje = date('Y-m-d'); // Pegando a data no formato YYYY-MM-DD

    while ($mentor = $result_mentores->fetch_assoc()) {
        $id_mentor = $mentor['id'];

        $sql_valores = "SELECT 
        COALESCE(SUM(green), 0) AS total_green,
        COALESCE(SUM(red), 0) AS total_red,
        COALESCE(SUM(valor_green), 0) AS total_valor_green,
        COALESCE(SUM(valor_red), 0) AS total_valor_red
        FROM valor_mentores 
        WHERE id_mentores = ? 
        AND DATE(data_criacao) = ?";
        $stmt_valores = $conexao->prepare($sql_valores);
        $stmt_valores->bind_param("is", $id_mentor, $hoje);

        $stmt_valores = $conexao->prepare($sql_valores);
        $stmt_valores->bind_param("i", $id_mentor);
        $stmt_valores->execute();
        $valores = $stmt_valores->get_result()->fetch_assoc();

        $total_subtraido = $valores['total_valor_green'] - $valores['total_valor_red'];

        echo "
          <div class='mentor-card' 
               data-nome='{$mentor['nome']}'
               data-foto='uploads/{$mentor['foto']}'
               data-id='{$mentor['id']}'>
            <div class='mentor-header'>
              <img src='uploads/{$mentor['foto']}' class='mentor-img' />
              <h3 class='mentor-nome'>{$mentor['nome']}</h3>
            </div>
            <div class='mentor-right'>
              <div class='mentor-values-inline'>
                <div class='value-box green'><p>Green</p><p>{$valores['total_green']}</p></div>
                <div class='value-box red'><p>Red</p><p>{$valores['total_red']}</p></div>
                <div class='value-box saldo'><p>Saldo</p><p>R$ {$total_subtraido}</p></div>
              </div>
            </div>
          </div>
        ";
    }
    ?>
  </div>
</div>

<div class="formulario-mentor">
  <img src="" class="mentor-foto-preview" width="100" />
  <h3 class="mentor-nome-preview">Nome do Mentor</h3>
  <form id="form-mentor" method="POST">
    <input type="hidden" name="id_mentor" class="mentor-id-hidden">
      <label>
    <input type="checkbox" name="green" style="display:none" onchange="toggleColor(this, 'green')">
    <span class="green">Green</span>
    </label>
    <label>
    <input type="checkbox" name="red" style="display:none" onchange="toggleColor(this, 'red')">
    <span class="red">Red</span>
    </label>
    <input type="text" name="valor" placeholder="Digite o valor" required>
    <button type="submit">Enviar</button>
    <button type="button" onclick="fecharFormulario()">❌ Fechar</button>
  </form>
</div>

<div id="mensagem-status" class="toast" style="display:none;"></div>

<script>
document.addEventListener("DOMContentLoaded", function () {
  const cards = document.querySelectorAll(".mentor-card");
  const formulario = document.querySelector(".formulario-mentor");
  const nomePreview = document.querySelector(".mentor-nome-preview");
  const fotoPreview = document.querySelector(".mentor-foto-preview");
  const idHidden = document.querySelector(".mentor-id-hidden");
  const form = document.getElementById("form-mentor");

  function mostrarToast(mensagem) {
    const toast = document.getElementById("mensagem-status");
    toast.textContent = mensagem;
    toast.style.display = "block";
    setTimeout(() => {
      toast.style.display = "none";
    }, 5000);
  }


function atualizarCards() {
  fetch("carregar-mentores.php")
    .then(res => res.text())
    .then(html => {
      document.querySelector(".mentor-wrapper").innerHTML = html;
      // Reatribuir eventos aos novos cards
      document.querySelectorAll(".mentor-card").forEach(card => {
        card.addEventListener("click", function () {
          nomePreview.textContent = card.dataset.nome;
          fotoPreview.src = card.dataset.foto;
          idHidden.value = card.dataset.id;
          formulario.style.display = "block";
        });
      });
    });
}

  cards.forEach(card => {
    card.addEventListener("click", function () {
      nomePreview.textContent = card.dataset.nome;
      fotoPreview.src = card.dataset.foto;
      idHidden.value = card.dataset.id;
      formulario.style.display = "block";
    });
  });

  window.fecharFormulario = function () {
    formulario.style.display = "none";
  };

  form.addEventListener("submit", function (e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch("cadastrar-valor.php", {
      method: "POST",
      body: formData
    })
    .then(msg => {
     mostrarToast(msg);
     form.reset();
     formulario.style.display = "none";
     atualizarCards(); // ⬅️ Atualiza os cards dinamicamente
   })
    .catch(err => {
      mostrarToast("❌ Erro: " + err);
    });
  });
});
</script>







<!-- BOTÃO ADICIONAR USUARIO -->
<div class="add-user">
        <button class="btn-add-usuario" onclick="abrirModal()">
          <span>+</span> Adicionar Mentoria
        </button>
 </div>



<!-- CODIGO RESPONSAVEL PELO FORMULARIO QUE ADICIONA NOVO USUARIO -->

<script>
function abrirModal() {
  document.getElementById("modal-form").style.display = "block";
}

function fecharModal() {
  document.getElementById("modal-form").style.display = "none";
}

// Fecha o modal ao clicar fora do conteúdo
window.onclick = function(event) {
  const modal = document.getElementById("modal-form");
  if (event.target === modal) {
    fecharModal();
  }
}

// Mostra nome do arquivo escolhido
function mostrarNomeArquivo(input) {
  const nome = input.files[0]?.name || "Nenhum arquivo selecionado";
  document.getElementById("nome-arquivo").textContent = nome;

  const previewImg = document.getElementById("preview-img");
  const removerBtn = document.getElementById("remover-foto");

  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function (e) {
      previewImg.src = e.target.result;
      removerBtn.style.display = "inline-block";
    };
    reader.readAsDataURL(input.files[0]);
  } else {
    previewImg.src = "https://cdn-icons-png.flaticon.com/512/847/847969.png";
    removerBtn.style.display = "none";
  }
}

// Botão para remover imagem e restaurar avatar padrão
function removerImagem() {
  const previewImg = document.getElementById("preview-img");
  const inputFile = document.getElementById("foto");
  const removerBtn = document.getElementById("remover-foto");

  inputFile.value = ""; // limpa o input de arquivo
  previewImg.src = "https://cdn-icons-png.flaticon.com/512/847/847969.png"; // volta pro avatar
  document.getElementById("nome-arquivo").textContent = "Nenhum arquivo selecionado";
  removerBtn.style.display = "none";
}
</script>

<!-- FIM DO CODIGO RESPONSAVEL PELO FORMULARIO QUE ADICIONA NOVO USUARIO -->

<script>
  function toggleColor(checkbox, color) {
    const span = checkbox.nextElementSibling;
    span.style.backgroundColor = checkbox.checked
      ? (color === 'green' ? '#4CAF50' : '#F44336')
      : '#fff';
    span.style.color = checkbox.checked ? '#fff' : '#000';
  }
</script>


</body>
</html>
