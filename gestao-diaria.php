
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



/* Container geral */
.container-cinza {
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

.verde {
  color: #00a651;
  font-size: 1.2em;
  gap: 30px;
  margin-right: 60px;
 
}

.vermelho {
  color: #d93025;
  font-size: 1.2em;
  margin-left: 60px;
  
}

.separador {
  font-size: 1.3em;
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
  background-color: #f1f1f1;
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
  color: #444;
  margin-bottom: 6px;
  margin-left: -27px;
  margin: 0;
  margin-left: 0;
}

.rotulo-saldo {
  display: block;
  font-size: 12px;
  color: #444;
  margin-bottom: 6px;
  margin-left:0px;
  margin: 0;
  margin-left: 0px;
}

.valor-vermelho {
  font-size: 1.4em;
  font-weight: bold;
  color: #d93025;
  margin-bottom: 2px;
  margin-left: 0;
}

.valor-cinza {
  font-size: 1.4em;
  font-weight: bold;
  color:rgb(172, 167, 167);
  margin-bottom: 2px;
  margin-left: 0;
}

.cinza {
  color: #555;
}




  


      




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


  <div class="container-cinza">
    
  <div class="pontuacao">

    <span class="pontos verde">0</span>
    <span class="separador">x</span>
    <span class="pontos vermelho">0</span>

  </div>



<div class="informacoes-row">

  <div class="info-item">

   <div>
     <span class="valor-vermelho">R$ 1.000,00</span>
     <span class="rotulo-meta">Meta do Dia</span>
   </div>

  </div>

  <div class="info-item">

    <div>
     <span class="valor-cinza">R$ 0,00</span>
     <span class="rotulo-saldo">Saldos do Dia</span>
    </div>

  </div>



</div>

  


    
 
     










  </body>
</html>
