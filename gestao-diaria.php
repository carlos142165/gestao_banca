
<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    echo "<script>alert('ÁREA DE MEMBROS – Faça Já Seu Cadastro Gratuito'); window.location.href = 'home.php';</script>";
    exit();
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
      background: linear-gradient(135deg, #eeeeee, #eeeeee,rgb(255, 255, 255));
      margin: 0;
      padding: 0;
      color: #f5f5f5;

    }








/*AQUI CODIGO PARA OS VALORES E PLACAR*/

/* Container geral */
.container-valores {
  background-color: #eeeeee; /* cinza claro elegante */
  padding: 20px;
  border-radius: 12px;
  box-sizing: border-box;
  max-width: 400px;
  margin: 0 auto;
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








/* AQUI VAI O CODIGO RESPONSAVEL PELO CAMPO ONDE OS USUARIOS VÃO FICAR  */
.add-user{  
  background-color: #f7f6f6;
  box-shadow: 0 2px 3px rgba(0,0,0,0.1);
  padding: 8px;
  border-radius: 5px;
  box-sizing: border-box;
  max-width: 400px;
  margin-top: 15px;
  height: 350px;

}

.user-01{
  background-color:rgb(243, 242, 242);
  box-shadow: 0 2px 3px rgba(0,0,0,0.1);
  border-radius: 5px;
  box-sizing: border-box;
  max-width: 400px;
  margin-top: 5px;
  height: 60px;
}

.user-02{
  background-color:rgb(243, 242, 242);
  box-shadow: 0 2px 3px rgba(0,0,0,0.1);
  border-radius: 5px;
  box-sizing: border-box;
  max-width: 400px;
  margin-top: 13px;
  height: 60px;
}

.user-03{
  background-color:rgb(243, 242, 242);
  box-shadow: 0 2px 3px rgba(0,0,0,0.1);
  border-radius: 5px;
  box-sizing: border-box;
  max-width: 400px;
  margin-top: 13px;
  height: 60px;
}

.user-04{
  background-color:rgb(243, 242, 242);
  box-shadow: 0 2px 3px rgba(0,0,0,0.1);
  border-radius: 5px;
  box-sizing: border-box;
  max-width: 400px;
  margin-top: 13px;
  height: 60px;
}



.btn-add-usuario {
 
  color: #8a8a8a;
  background-color: #f7f6f6;
  display: flex;
  align-items: center;
  gap: 0px;
  cursor: pointer;
  border-radius: 8px;
  font-size: 12px;
  width: 100%;
  margin-top: 25px;
  justify-content: center;
  border: none;
  border-bottom: none; /* se quiser tirar o contorno inferior também */
  box-shadow: none;
}

/* Ícone "+" com destaque verde */
.btn-add-usuario span {
  color: #00a651;
  font-weight: bold;
  font-size: 18px;
}
/* AQUI FIM DO CODIGO RESPONSAVEL PELO CAMPO ONDE OS USUARIOS VÃO FICAR  */











/* AQUI VAI O CODIGO PARA O FORMULARIO DE CADASTRO USUARIO */
.user{
  max-width: 400px;
  margin: 30px auto;
  margin-top: 15px;
  padding: 12px;
  background-color: #f7f6f6;
  border-radius: 12px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.08);
  font-family: 'Segoe UI', sans-serif;
  height: 155px;
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
.modal {
  display: none;
  position: fixed;
  z-index: 1000;
  left: 0; top: 0;
  width: 100%; height: 100%;
  background-color: rgba(0,0,0,0.5);
}

.modal-conteudo {
  background-color: #fff;
  margin: 10% auto;
  padding: 20px;
  border-radius: 10px;
  width: 400px;
  box-shadow: 0 0 10px #555;
  animation: fadeIn 0.3s ease-in-out;
}

.fechar {
  float: right;
  font-size: 24px;
  cursor: pointer;
}

.modal input, .modal button {
  width: 100%;
  padding: 10px;
  margin-top: 10px;
}

.botao-upload {
  display: inline-block;
  padding: 10px;
  background-color: #4CAF50;
  color: white;
  cursor: pointer;
  border-radius: 5px;
  margin-top: 10px;
}

#nome-arquivo {
  display: block;
  margin-top: 5px;
  font-style: italic;
  color: #555;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-20px); }
  to { opacity: 1; transform: translateY(0); }
}



#preview-container {
  margin-top: 10px;
  text-align: center;
}

#preview-img {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #4CAF50;
  display: block;
  margin: 0 auto;
  transition: 0.3s ease;
}


/* AQUI O FIM DO CODIGO PARA O FORMULARIO DE ADICIONAR UM NOVO USUARIO */




      




    </style>

     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  </head>








  <body>


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





<div class="add-user">


  

 <div class="user-01">
 </div>

 <div class="user-02">
 </div>

 <div class="user-03">
 </div>

 <div class="user-04">
 </div>



<!-- CODIGO RESPONSAVEL PELO FORMULARIO QUE ADICIONA NOVO USUARIO -->

<button class="btn-add-usuario" onclick="abrirModal()">
  <span>+</span> Adicionar Usuário
</button>

<!-- Modal -->
<div id="modal-form" class="modal">
  <div class="modal-conteudo">
    <span class="fechar" onclick="fecharModal()">&times;</span>
    
    <form action="gestao-diaria.php" method="POST" enctype="multipart/form-data">
      
      <!-- Pré-visualização da imagem -->
        

      <label for="foto" class="botao-upload">📸 Escolha sua foto</label>
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

 <!-- FIM DO CODIGO RESPONSAVEL PELO FORMULARIO QUE ADICIONA NOVO USUARIO -->


</div>








<div class="user">

  <!-- Linha de adicionar e buscar usuário -->
  <div class="row">
      <select id="buscar">
      <option value="">Buscar Usuário</option>
      <option value="usuario1">Usuário 1</option>
      <option value="usuario2">Usuário 2</option>
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


  


    
 
     










</body>
</html>
