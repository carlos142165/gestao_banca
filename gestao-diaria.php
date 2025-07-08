

<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    echo "<script>alert('ÁREA DE MEMBROS – Faça Já Seu Cadastro Gratuito'); window.location.href = 'home.php';</script>";
    exit();
}
?>



<?php // CODIGO RESPONSAVEL PELO CADASTRO DA FOTO E O NOME

include("config.php"); // Inclui a conexão existente

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['usuario_id'])) {
        die("Usuário não está logado.");
    }

    $usuario_id = $_SESSION['usuario_id'];
    $nome = $_POST['nome'];

    $foto_nome = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $extensao = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $foto_nome = uniqid() . '.' . $extensao;
        $caminho_destino = 'uploads/' . $foto_nome;


        if (!move_uploaded_file($_FILES['foto']['tmp_name'], $caminho_destino)) {
            die("Erro ao fazer o upload da imagem.");
        }
    }

   $stmt = $conexao->prepare("INSERT INTO mentores (id_usuario, foto, nome,data_criacao) VALUES (?, ?, ?, NOW())");


    $stmt->bind_param("iss", $usuario_id, $foto_nome, $nome);

    if ($stmt->execute()) {
    echo "<script>
        alert('Cadastro de mentor efetuado com sucesso!');
        window.location.href = 'gestao-diaria.php'; // ou outra página que desejar
        </script>";
    exit;
} else {
    echo "<script>
        alert('Erro ao cadastrar: " . $stmt->error . "');
        window.location.href = 'index.php'; // ou uma página de retorno
        </script>";
    exit;
}
} // FIM DO CODIGO RESPONSAVEL PELO CADASTRO DA FOTO E O NOME
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





/*AQUI CODIGO PARA OS VALORES E PLACAR*/

/* Container geral */
.container-valores {
  background-color: #f7f6f6; /* cinza claro elegante */
  padding: 20px;
  border-radius: 12px;
  box-sizing: border-box;
  max-width: 400px;
  margin: 0 auto;
  margin-top: 15px;
  width: 390px;
}

/* Placar interno */
.placar {
  display: flex;
  flex-direction: column;
  width: 100%;
  font-family: 'Segoe UI', Arial, sans-serif;
}

/* Pontuação */
.pontuacao {
  display: flex;
  justify-content: center;
  align-items: center;
  font-size: 2.8em;
  margin-bottom: 20px;
}

.pontos {
  font-weight: bold;
  margin: 0 12px;
}

.placar-green {
  color: #00a651;
  font-size: 1.2em;
  gap: 30px;
  margin-right: 60px;
  font-weight: bold;
 
}

.placar-red {
  color: #f82008;
  font-size: 1.2em;
  margin-left: 60px;
  font-weight: bold;
  
}

.separador {
  font-size: 1.2em;
  margin: 0 8px;
  color:rgb(105, 104, 104);
  font-weight: bold;
  margin-top: -10px;
}

.informacoes-row {
  display: flex;
  justify-content: space-between; /* ou use center para centralizar */
  gap: 30px; /* espaço entre os blocos */
  
}


.info-item {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  background-color: #f7f6f6;
  padding: 12px;
  border-radius: 10px;
  text-align: left;
  box-shadow: 0 2px 5px rgba(0,0,0,0.1);
  justify-content: center; /* alinha verticalmente ao centro */
  align-items: center;     /* alinha horizontalmente ao centro */
  width: 200px;
  
  
}

.rotulo-meta {
  display: block;
  font-size: 12px;
  color:rgb(172, 167, 167);
  margin-bottom: 6px;
  margin-left: 0px;
  margin: 0;
  margin-left: 0;
  font-weight: bold;
  margin-top: 3px;
  
}

.rotulo-saldo {
  display: block;
  font-size: 12px;
  color:rgb(172, 167, 167);
  margin-bottom: 6px;
  margin-left:-3px;
  margin: 0;
  margin-left: 0px;
  font-weight: bold;
  margin-top: 3px;
  
  
}

.valor-meta {
  font-size: 1.4em;
  font-weight: bold;
  color:rgb(161, 158, 158);
  margin-bottom: 2px;
  margin-left: 0px;
}

.valor-saldo {
  font-size: 1.4em;
  font-weight: bold;
  color:rgb(161, 158, 158);
  margin-bottom: 2px;
  margin-left: 0px;
}

.cinza {
  color: #555;
}
/* FIM CODIGO PARA OS VALORES E PLACAR */











/* AQUI VAI O CODIGO PARA O FORMULARIO DE CADASTRO USUARIO */
.user{
  max-width: 400px;
  margin: 30px auto;
  margin-top: 5px;
  padding: 12px;
  background-color: #f7f6f6;
  border-radius: 12px;
 
  font-family: 'Segoe UI', sans-serif;
  height: 155px;
  width: 370px;
}

/* Estrutura horizontal das linhas */
.row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 15px;
  margin-bottom: 20px;
  flex-wrap: wrap;
}

/* Estilo dos botões e campos */
select,
input[type="number"],
.btn-add,
.btn-submit {
  padding: 10px 14px;
  font-size: 14px;
  border-radius: 8px;
  border: 1px solid #ccc;
  flex: 1;
  min-width: 120px;
  transition: all 0.3s ease;
}

select:hover,
input[type="number"]:hover {
  border-color: #888;
}


.btn-add:hover {
  background-color: #43a047;
}

/* Botão enviar */
.btn-submit {
  background-color: #00a651;
  color: white;
  border: none;
  cursor: pointer;
}

.btn-submit:hover {
  background-color:rgb(6, 150, 76);
}

/* Checkboxes e valor lado a lado */
.checkbox-row {
  justify-content: flex-start;
  gap: 20px;
  flex-wrap: wrap;
}

.checkbox-row label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-weight: bold;
}

input[type="checkbox"] {
  width: 18px;
  height: 18px;
  accent-color: #00a651;
}

input.red {
  accent-color: #f82008;
}

.checkbox-row label:first-child {
  color: #00a651; /* Green */
}

.checkbox-row label:nth-child(2) {
  color: #f82008; /* Red */
}
/* AQUI É O FIM  DO CODIGO PARA O FORMULARIO DE CADASTRO USUARIO */







/* AQUI VAI O CODIGO PARA O FORMULARIO DE ADICIONAR UM NOVO USUARIO */
/* Modal geral */
.modal {
  display: none;
  position: fixed;
  z-index: 1000;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0,0,0,0.6);
}

/* Conteúdo do modal centralizado */
.modal-conteudo {
  position: relative; /* ← isso é fundamental */
  background-color: #fff;
  margin: 5% auto;
  padding: 30px 25px;
  border-radius: 12px;
  max-width: 400px;
  width: 70%;
  box-shadow: 0 0 15px rgba(0,0,0,0.3);
  animation: fadeIn 0.3s ease-in-out;
  font-family: 'Segoe UI', sans-serif;
  top: 100px;
}

/* Título e botão de fechar */
.fechar {
  position: absolute;
  top: 2px;
  right: 15px;
  font-size: 26px;
  cursor: pointer;
  color: #888;
  font-weight: bold;
  background: none;
  border: none;
}

/* Labels e campos */
.modal label {
  display: block;
  margin-top: 15px;
  font-weight: 500;
  color: #333;
}

.modal input[type="text"],
.modal input[type="file"],
.modal button[type="submit"],
.modal input[type="number"] {
  width: 100%;
  padding: 10px 12px;
  margin-top: 8px;
  border: 1px solid #ccc;
  border-radius: 8px;
  box-sizing: border-box;
  font-size: 14px;
}

/* Botão customizado para upload */
.botao-upload {
  display: inline-block;
  padding: 10px 15px;
  margin-top: 15px;
  background-color: #4CAF50;
  color: white;
  cursor: pointer;
  border-radius: 8px;
  font-weight: bold;
  text-align: center;
}

/* Pré-visualização da imagem */
#preview-container {
  margin-top: 15px;
  text-align: center;
}

#preview-img {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  object-fit: cover;
  border: 3px solid #4CAF50;
  box-shadow: 0 0 8px rgba(0,0,0,0.2);
  transition: 0.3s ease-in-out;
}

/* Botão remover imagem */
#remover-foto {
  background-color: #f44336;
  color: white;
  border: none;
  padding: 8px 12px;
  border-radius: 8px;
  cursor: pointer;
  margin-top: 10px;
  font-size: 14px;
  display: inline-block;
}

/* Botão de envio */
.modal button[type="submit"] {
  background-color: #2196F3;
  color: white;
  border: none;
  padding: 10px 0;
  margin-top: 20px;
  border-radius: 8px;
  font-size: 15px;
  font-weight: bold;
  transition: background-color 0.3s ease;
}

.modal button[type="submit"]:hover {
  background-color: #1976D2;
}

/* Nome do arquivo */
#nome-arquivo {
  display: block;
  margin-top: 8px;
  font-style: italic;
  font-size: 13px;
  color: #666;
  text-align: center;
}

/* Animação */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-20px); }
  to { opacity: 1; transform: translateY(0); }
}



/* AQUI O FIM DO CODIGO PARA O FORMULARIO DE ADICIONAR UM NOVO USUARIO */















/* AQUI O CODIGO DA FOTO PERFIL */
.mentor-card {
    display: flex;
    align-items: center;
    border: 1px solid #dcdcdc;
    border-radius: 8px;
    padding: 6px 10px;
    background-color: #fff;
    width: 330px;
    height: 58px;
    font-family: Arial, sans-serif;
    margin-top: 2px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    gap: 8px;
}

.mentor-left {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 60px;
    flex-shrink: 0;
}

.mentor-img {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    object-fit: cover;
}

.mentor-nome {
    font-size: 11px;
    margin-top: 4px;
    color: #333;
    text-align: center;
    font-weight: normal;
    line-height: 1.2;
}

.mentor-right {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
}

.mentor-values-inline {
    display: flex;
    gap: 20px;
    align-items: center;
    justify-content: center;
    height: 100%;
    color:rgb(95, 93, 93);
    
}

.value-box {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    width: 60px;
    font-size: 13px;
}

.value-box p {
    margin: 1px 0;
    line-height: 1.2;
}

.value-box p:first-child {
    font-weight: bold;
}

.value-box.green p:first-child {
    color: #00a651;
}

.value-box.red p:first-child {
    color: #ff4d4d;
}

.value-box.saldo p:first-child {
    color:rgb(95, 93, 93);
}




/* FIM CODIGO DA FOTO PERFIL */


/* AQUI VAI O CODIGO RESPONSAVEL PELO CAMPO ONDE OS USUARIOS VÃO FICAR  */



.btn-add-usuario {
  height: 30px;
  color: #8a8a8a;
  background-color: #f7f6f6;  
  margin-top: 15px;
  cursor: pointer;
  border-radius: 8px;
  font-size: 12px;
  width: 390px;
  margin-top: 10px; 
  border: none;
  border-bottom: none; /* se quiser tirar o contorno inferior também */
  
}

/* Ícone "+" com destaque verde */
.btn-add-usuario span {
  color: #00a651;
  font-weight: bold;
  font-size: 18px;
  
}

.add-user {
  display: flex;
  justify-content: center;
  width: 100%;
  margin-top: 20px;
}


.campo_mentores {
  background-color: #f7f6f6;
  display: flex;
  flex-direction: column;    /* <-- organiza em coluna */
  align-items: center;       /* <-- centraliza horizontalmente */
  justify-content: flex-start;
  width: 390px;
  margin: 0 auto;
  margin-top: 5px;
  height: 350px;              /* <-- permite crescer conforme conteúdo */
  border-radius: 8px;
  padding: 20px;
  box-sizing: border-box;
}


/* AQUI FIM DO CODIGO RESPONSAVEL PELO CAMPO ONDE OS USUARIOS VÃO FICAR  */




      




</style>
     
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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
    
    <form action="gestao-diaria.php" method="POST" enctype="multipart/form-data">
      
      <!-- Pré-visualização da imagem -->
        

      <label for="foto" class="botao-upload">📸 Escolha a Foto do Mentor</label>
        <input type="file" id="foto" name="foto" accept="image/*" style="display:none" onchange="mostrarNomeArquivo(this)">
      <span id="nome-arquivo">Nenhum arquivo selecionado</span>

      <div id="preview-container">
           <img id="preview-img" src="https://cdn-icons-png.flaticon.com/512/847/847969.png" alt="Avatar padrão">
      </div>

      
      <button type="button" id="remover-foto" onclick="removerImagem()" style="display:none; margin-top:10px;">
         Remover imagem
      </button>

      <!-- Nome do usuário -->
      <label for="nome">Nome:</label>
      <input type="text" id="nome" name="nome" required>

      <!-- Botão de envio -->
      <button type="submit">Enviar</button>
    </form>
  </div>
 </div>

 
</div>
<!-- FIM DO CODIGO RESPONSAVEL PELO FORMULARIO QUE ADICIONA NOVO USUARIO -->











<!-- CODIGO RESPONSAVEL PELO CAMPO ONDE OS MENTORES VAO FICAR -->


<div class="campo_mentores">


 <div class="campo_mentores2">
    <div class="mentor-wrapper">
        <?php

        // Conexão com o banco
        $conn = new mysqli("localhost", "root", "", "formulario-carlos");

        if ($conn->connect_error) {
            die("Erro na conexão: " . $conn->connect_error);
        }

        // Garante que a sessão esteja ativa
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $id_usuario_logado = $_SESSION['usuario_id'];

        // Buscar mentores associados ao usuário logado
        $sql_mentores = "SELECT id, nome, foto FROM mentores WHERE id_usuario = ?";
        $stmt_mentores = $conn->prepare($sql_mentores);
        $stmt_mentores->bind_param("i", $id_usuario_logado);
        $stmt_mentores->execute();
        $result_mentores = $stmt_mentores->get_result();

        while ($mentor = $result_mentores->fetch_assoc()) {
            $id_mentor = $mentor['id'];

            // Buscar valores agregados
            $sql_valores = "SELECT 
                COALESCE(SUM(green), 0) AS total_green,
                COALESCE(SUM(red), 0) AS total_red,
                COALESCE(SUM(valor_green), 0) AS total_valor_green,
                COALESCE(SUM(valor_red), 0) AS total_valor_red
                FROM valor_mentores
                WHERE id_mentores = ?";

            $stmt_valores = $conn->prepare($sql_valores);
            $stmt_valores->bind_param("i", $id_mentor);
            $stmt_valores->execute();
            $result_valores = $stmt_valores->get_result();
            $valores = $result_valores->fetch_assoc();

            $total_subtraido = $valores['total_valor_green'] - $valores['total_valor_red'];

            // Card do mentor
            echo "
<div class='mentor-card'>
    <div class='mentor-left'>
        <img src='uploads/{$mentor['foto']}' alt='Foto de {$mentor['nome']}' class='mentor-img' />
        <h3 class='mentor-nome'>{$mentor['nome']}</h3>
    </div>
    <div class='mentor-right'>
        <div class='mentor-values-inline'>
            <div class='value-box green'>
                <p>Green</p>
                <p>{$valores['total_green']}</p>
            </div>
            <div class='value-box red'>
                <p>Red</p>
                <p>{$valores['total_red']}</p>
            </div>
            <div class='value-box saldo'>
                <p>G/P</p>
                <p>R$ {$total_subtraido}</p>
            </div>
        </div>
    </div>
</div>

";




        }

        ?>
    </div>
  </div>











 <div class="add-user">
        <button class="btn-add-usuario" onclick="abrirModal()">
          <span>+</span> Adicionar Mentoria
        </button>
 </div>



</div>
  
  

<!-- FIM DO CODIGO RESPONSAVEL PELO CAMPO ONDE OS MENTORES VAO FICAR -->





<!-- CODIGO RESPONSAVEL PELO FORMULARIO QUE BUSCA USUARIO E CADASTRA VALORES REFERENTE  -->

<div class="user">

  <!-- Linha de adicionar e buscar usuário -->
<div class="row">
  <label for="buscar">Buscar Usuário</label>
  <select id="buscar">
    <option value="">Buscar Usuário</option>
  </select>
</div>



  <!-- Linha de checkboxes com input de valor -->
  <div class="row checkbox-row">
    <label>
      <input type="checkbox" id="green" />Green</label>

    <label>
      <input type="checkbox" class="red" id="red" />Red</label>

    <input type="number" id="valor" placeholder="Valor" />
  </div>

  <!-- Botão de envio -->
  <div class="row">
    <button class="btn-submit">Enviar</button>
  </div>

</div>

<!-- FIM DO CODIGO RESPONSAVEL PELO FORMULARIO QUE BUSCA USUARIO E CADASTRA VALORES REFERENTE  -->








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







<!-- CODIGO PARA PEGAR DA PAGINA BUSCAR_MENTORES.PHP E ABRIR NO MENU DE SELEÇÃO  -->
<script>
document.addEventListener("DOMContentLoaded", function() {
  fetch("buscar_mentores.php")
    .then(response => response.json())
    .then(data => {
      const select = document.getElementById("buscar");
      select.innerHTML = '<option value="">Buscar Usuário</option>';

      data.forEach(nome => {
        const option = document.createElement("option");
        option.value = nome;
        option.textContent = nome;
        select.appendChild(option);
      });
    })
    .catch(error => console.error("Erro ao carregar mentores:", error));
});
</script>
<!-- FIM DO CODIGO PARA PEGAR DA PAGINA BUSCAR_MENTORES.PHP E ABRIR NO MENU DE SELEÇÃO  -->

 
    








</body>
</html>
