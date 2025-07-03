
<?php
session_start();
include_once('config.php');

// Soma de depósitos
$soma_depositos = 0;
if (isset($_SESSION['usuario_id'])) {
    $id_usuario = $_SESSION['usuario_id'];

    $stmt = mysqli_prepare($conexao, "SELECT SUM(deposito) FROM controle WHERE id_usuario = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_usuario);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $soma_depositos);
    mysqli_stmt_fetch($stmt);
    if (is_null($soma_depositos)) {
        $soma_depositos = 0;
    }
    mysqli_stmt_close($stmt);
}

// Última diária válida (diferente de NULL e diferente de 0)
$ultima_diaria = 0;
if (isset($_SESSION['usuario_id'])) {
    $stmt = mysqli_prepare($conexao, "
        SELECT diaria
        FROM controle
        WHERE id_usuario = ? AND diaria IS NOT NULL AND diaria != 0
        ORDER BY id DESC
        LIMIT 1
    ");
    mysqli_stmt_bind_param($stmt, "i", $id_usuario);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $ultima_diaria);
    mysqli_stmt_fetch($stmt);
    if (is_null($ultima_diaria)) {
        $ultima_diaria = 0;
    }
    mysqli_stmt_close($stmt);
}

// Soma de saques
$soma_saque = 0;
if (isset($_SESSION['usuario_id'])) {
    $stmt = mysqli_prepare($conexao, "
        SELECT SUM(saque)
        FROM controle
        WHERE id_usuario = ?
    ");
    mysqli_stmt_bind_param($stmt, "i", $id_usuario);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $soma_saque);
    mysqli_stmt_fetch($stmt);
    if (is_null($soma_saque)) {
        $soma_saque = 0;
    }
    mysqli_stmt_close($stmt);
}
?>


























<?php

if (!isset($_SESSION['usuario_id'])) {
  echo "<script>
    alert('ÁREA DE MEMBROS – Faça Já Seu Cadastro Gratuito');
    window.location.href = 'home.php';
  </script>";
  exit();
}

function limpar_valor($valor) {
  $valor = preg_replace('/[^0-9,]/', '', $valor);
  $valor = str_replace(',', '.', $valor);
  return is_numeric($valor) ? (float)$valor : 0;
}

if (isset($_POST['submit'])) {
  include_once('config.php');

  $id_usuario = $_SESSION['usuario_id'];
  $acao = $_POST['acao'];
  $valor = limpar_valor($_POST['valor']);

  // Valida ação
  if (!in_array($acao, ['deposito', 'diaria', 'saque'])) {
    echo "<script>
      alert('Ação inválida.');
      window.history.back();
    </script>";
    exit();
  }

  // Valida valor
  if (!is_numeric($valor) || $valor <= 0) {
    echo "<script>
      alert('Por favor, insira um valor válido.');
      window.history.back();
    </script>";
    exit();
  }

  // Verifica se o usuário ainda existe
  $verifica = mysqli_prepare($conexao, "SELECT id FROM usuarios WHERE id = ?");
  mysqli_stmt_bind_param($verifica, "i", $id_usuario);
  mysqli_stmt_execute($verifica);
  mysqli_stmt_store_result($verifica);

  if (mysqli_stmt_num_rows($verifica) === 0) {
    echo "<script>
      alert('Usuário não encontrado. Faça login novamente.');
      window.location.href = 'home.php';
    </script>";
    exit();
  }

  // Monta a query dinâmica com base na ação
  $query = "INSERT INTO controle (id_usuario, $acao) VALUES (?, ?)";
  $stmt = mysqli_prepare($conexao, $query);

  if ($stmt) {
    mysqli_stmt_bind_param($stmt, "id", $id_usuario, $valor);
    if (mysqli_stmt_execute($stmt)) {
      echo "<script>
        alert('Valor cadastrado com sucesso!');
        window.location.href = 'painel-controle.php';
      </script>";
    } else {
      echo "<script>
        alert('Erro ao cadastrar.');
        window.history.back();
      </script>";
    }
  } else {
    echo "<script>
      alert('Erro ao preparar a consulta.');
      window.history.back();
    </script>";
  }
  exit();
}
?>







<!DOCTYPE html>
<html lang="pt-br">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestão</title>







  
  <style>
    body, html {
      overflow: visible;
      position: relative;
      margin: 0;
      padding: 0;
      z-index: 0;
      height: 100%;
      font-family: Arial, Helvetica, sans-serif;
      background-image: linear-gradient(to right, #f5f5f5, #f5f5f5);  /* CODIGO RESPONSAVEL POR TODA ESTRUTURA DO CORPO DO SITE */
    }

    .container-principal {
      display: flex;
      flex-direction: column;  /* CODIGO RESPONSAVEL POR TODA ESTRUTURA DO CORPO DO SITE */
      align-items: center;
      gap: 24px;
      padding-top: 30px;
    }



     /* CODIGO RESPONSAVEL PELO FORMULARIO DE CADASTRO" */
    .box {
      position: static;
      overflow: visible;
      background-color: #e6e6e6;
      padding: 10px;
      border-radius: 8px;               /* RESPONSAVEL PELA COR FUNDO FORMULARIO E DO TEXTO TITULO "SELECIONE A OPÇÃO" */
      width: 345px;
      color: #113647;
      margin-top: -8px;
      z-index: auto;
      
    }

    .titulo-bloco {
    text-align: center;
    font-weight: bold; /* opcional, só para dar destaque */
  }

  fieldset {
  border: 2px solid #dcdcdc;
  border-radius: 8px;
  padding: 1.4em 1.1em 1.1em;
  position: relative;
  background-color: #ffffff;
  overflow: hidden;
  
}

  

    .inputbox {
      position: relative;     /* RESPONSAVEL PELA TEXTO "VALOR" */
      font-size: 12px;
      margin-top: 10px;
    }

    .inputUser {
      background: none;
      border: none;
      border-bottom: 2px solid #aaaaaa;    /* RESPONSAVEL PELA COR DO TEXTO DE DENTRO DO CAMPO INPUT VALOR E DA COR DA LINHA  */
      width: 295px;
      outline: none;
      color: #113647;
      font-size: 12px;
      font-weight: bold;
      
    }

    .labelinput {
      position: absolute;
      top: 0px;
      left: 0px;
      pointer-events: none;       /* RESPONSAVEL PELA COR DO TEXTO LABEL "VALOR" E DO TAMANHO   */
      transition: .5s;
      font-size: 12px;
      font-weight: bold;
      color: #113647;
     
    }

    .inputUser:focus ~ .labelinput,
    .inputUser:valid ~ .labelinput {    /* RESPONSAVEL PELA COR DO TEXTO DE DENTRO DO CAMPO VALOR QUANDO DIMINUI*/
      top: -20px;
      font-size: 12px;
      color:rgb(164, 165, 165);
    }

    .dropdown {
      position: relative;    /* RESPONSAVEL PELO TAMANHO DO CAMPO DE SELEÇÃO */
      width: 300px;
      margin-top: -15px;
      z-index: 1;
    }

    .dropdown-header {
      background-color: #dfdede;
      color: #113647;
      border-radius: 6px;
      cursor: pointer;
      display: flex;
      justify-content: space-between;    /* RESPONSAVEL PELA COR DO TEXTO "SELECIONE" TAMANHO DA FONTE COR DO CAMPO */
      align-items: center;
      font-weight: bold;
      font-size: 13px;
      padding: 7px;
      border: 1px solid #ccc;
    }

    .dropdown-options {
      position: fixed;
      z-index: 99999;
      background-color: #f5f5f5;
      border: 1px solid #ccc;
      width: 259px; /* mesma largura da .dropdown */
      max-height: 200px;
      overflow-y: auto;
      display: none;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      font-size: 13px;
      border-radius: 6px;
      margin-top: 2px;
    }

    .dropdown-options li {
      padding: 10px 14px;
      color: #113647;    /* RESPONSAVEL PELA DO TEXTO DE DENTRO DA ABA  "SELECIONE" */
      cursor: pointer;
      font-weight: bold;
    }

    .dropdown-options li:hover {
      background-color:rgb(227, 236, 236);
      border-radius: 6px;   /* RESPONSAVEL PELA COR AO PASSAR O MOUSE NOS ITENS DA ABA "SELECIONE" */
    }

    #submit {
      background-color: #1e5165;
      width: 100%;
      border: none;
      padding: 10px;
      border-radius: 10px;    /* RESPONSAVEL PELA COR DO BOTÃO E PELO TEXTO E TAMANHO */
      color:rgb(232, 235, 235);
      font-size: 12px;
      cursor: pointer;
      font-weight: bold;
    }

    #submit:hover {
      background-color: #113647;   /* RESPONSAVEL PELA COR DO BOTÃO AO PASSAR O MOUSE */
    }
    /* FIM DO CODIGO RESPONSAVEL PELO FORMULARIO DE CADASTRO" */






    

    /* CODIGO RESPONSAVEL PELO VALOR PUXADO DO BANCO DE DADOS */
    .text-resultado {
      font-size: 12px;
      padding: 10px;
      border-radius: 8px;
      background-color: #113647;   /* RESPONSAVEL PELA COR DO FUNDO TAMANHO E COR DO TEXTO  */
      color: #e6e5e3;
      width: 300px;
      text-align: left;
      margin-top: 30px;
      
    }
    /* FIM DO CODIGO RESPONSAVEL PELO VALOR PUXADO DO BANCO DE DADOS */






     /* CODIGO RESPONSAVEL PELO CALCULO DOS VALORES PARA GESTÃO */

  .valor-unidade {
  margin-bottom: 0px;
  margin-top: -8px;
  font-size: 13px;
  text-align: center;
  color: #113647;
  font-weight: bold;
}

.linha-unidade {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  flex-wrap: wrap;
  margin-top: 5px;
  
}

.bloco-com-label,
.bloco-com-label-banca {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 78px; /* Reduzindo um pouco */
  align-items: center;
}

.bloco {
  background-color: rgb(224, 233, 236);
  padding: 10px; /* Reduz altura interna */
  border-radius: 6px;
  font-size: 11px;
  width: 100%;
  text-align: center;
  align-items: center;
}

.sinal-central {
  font-size: 1em;
  padding: 4px;
  font-weight: bold;
  color: #113647;
  position: relative;
  top: 2px;
}

.bloco-unidade {
  background-color:rgb(233, 220, 232); 
  border: 2px solid #dcdcdc;
  border-radius: 10px;
  padding: 10px;
  align-items: center;
  box-shadow: 0 2px 6px rgba(0,0,0,0.05);
  margin-top: -5px;
}

.bloco-valor-diaria{
  background-color: rgb(220, 233, 226);
  border: 0px solid #dcdcdc;
  border-radius: 10px;
  padding: 10px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.05);
  align-items: center;
  margin-top: -5px;
}

.bloco-valor-mensal{
  background-color: rgb(233, 225, 220);
  border: 0px solid #dcdcdc;
  border-radius: 10px;
  padding: 10px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.05);
  align-items: center;
  margin-top: -5px;
}

.bloco-valor-anual{
  background-color: rgb(220, 226, 233);
  border: 0px solid #dcdcdc;
  border-radius: 10px;
  padding: 10px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.05);
  align-items: center;
  margin-top: -5px;
}

.titulo-bloco-uidade {
  text-align: center;
  font-size: 13px;
  font-weight: bold;
  color: #113647;
  padding: 3px 0;
  margin-bottom: 0.2em; /* Reduz o espaço entre o título e o fieldset */
  margin-top: -4px; /* Sobe ligeiramente em relação ao elemento anterior */
}

/* Títulos adicionais com mesmo estilo */
.titulo-valor-diaria,
.titulo-valor-mensal,
.titulo-valor-anual {
  text-align: center;
  font-size: 13px;
  font-weight: bold;
  color: #113647;
  padding: 3px 0;
  margin-bottom: 0.2em;
  margin-top: -4px;
  align-items: center;
}


.unidade-diaria,
.unidade-mensal,
.unidade-valor,
.unidade-anual {
  padding: 15px 15px; 
}


 /* FIM DO CODIGO RESPONSAVEL PELO CALCULO DOS VALORES PARA GESTÃO */

    
    





    

  </style>



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







<div class="container-principal">

  <div class="box">
  <div class="titulo-bloco">Selecione Uma Opção</div>
  <br>
  <form action="painel-controle.php" method="POST">
    <fieldset>
      <br>
      <div class="dropdown">
        <div class="dropdown-header" onclick="toggleDropdown()">
          <span id="dropdown-selected">Selecione</span>
          <span class="arrow">&#9662;</span>
        </div>
        <ul class="dropdown-options" id="dropdown-options">
          <li onclick="selectOption('Depositar na Banca', 'deposito')">Depositar na Banca</li>
          <li onclick="selectOption('Porcentagem Sobre a Banca', 'diaria')">Porcentagem Sobre a Banca</li>
          <li onclick="selectOption('Sacar da Banca', 'saque')">Sacar da Banca</li>
          <li onclick="selectOption('Limpar Banca', 'limpar')">Limpar Banca</li>
        </ul>
        <input type="hidden" name="acao" id="acao">
      </div><br>

      <div class="inputbox">
        <input type="text" name="valor" id="valor" class="inputUser" required>
        <label for="valor" class="labelinput">Valor</label>
      </div><br>

      <input type="submit" name="submit" id="submit" value="Enviar">
    </fieldset>
  </form>
</div>







  <div class="bloco-unidade">
    <div class="titulo-bloco-uidade">Valor da Sua Unidade</div>

    <fieldset class="traco-unidade unidade-especial unidade-valor">
  
    <?php
    if (isset($_SESSION['usuario_id']) && $ultima_diaria > 0 && $soma_depositos > 0):
        $resultado = ($ultima_diaria / 100) * $soma_depositos;
        
        // Formatação da porcentagem com ou sem casa decimal
        $percentualFormatado = (intval($ultima_diaria) == $ultima_diaria)
            ? intval($ultima_diaria) . '%'
            : number_format($ultima_diaria, 1, ',', '.') . '%';
    ?>
    <div class="valor-unidade">
        <div class="linha-unidade">
            <div class="bloco-com-label-banca">
                <label class="subtitulo"></label>
                <span class="bloco">R$ <?= number_format($soma_depositos, 2, ',', '.') ?></span>
            </div>

            <div class="sinal-central">×</div>

            <div class="bloco-com-label">
                <label class="subtitulo"></label>
                <span class="bloco"><?= $percentualFormatado ?></span>
            </div>

            <div class="sinal-central">=</div>

            <div class="bloco-com-label">
                <label class="subtitulo"></label>
                <span class="bloco resultado">R$ <?= number_format($resultado, 2, ',', '.') ?></span>
            </div>
        </div>
    </div>
    <?php endif; ?>
    </fieldset>
</div>


<div class="bloco-valor-diaria">
   <div class="titulo-valor-diaria">Valor da Sua Meta Diária</div>
  <?php if (isset($resultado)): 
  $meia_unidade = $resultado * 0.5;
   ?>
   <fieldset class="traco-unidade unidade-especial unidade-diaria">
  
  <div class="valor-unidade">
     <div class="linha-unidade">

      <div class="bloco-com-label">
        <label class="subtitulo"></label>
        <span class="bloco">R$ <?php echo number_format($resultado, 2, ',', '.'); ?></span>
      </div>

      <div class="sinal-central">×</div>

      <div class="bloco-com-label">
        <label class="subtitulo"></label>
        <span class="bloco">50%</span>
      </div>

      <div class="sinal-central">=</div>

      <div class="bloco-com-label">
        <label class="subtitulo"></label>
        <span class="bloco resultado">R$ <?php echo number_format($meia_unidade, 2, ',', '.'); ?></span>
      </div>
       </div>
      </div>
       <?php endif; ?>
       </fieldset>
</div>

<div class="bloco-valor-mensal">
  <div class="titulo-valor-mensal">Valor da Sua Meta Mensal</div>
   <?php if (isset($meia_unidade)): 
   $meia_unidade_mensal = $meia_unidade * 30;
  ?>
  <fieldset class="traco-unidade unidade-especial unidade-mensal">
  
  <div class="valor-unidade">
    <div class="linha-unidade">

      <div class="bloco-com-label">
        <label class="subtitulo"></label>
        <span class="bloco">R$ <?php echo number_format($meia_unidade, 2, ',', '.'); ?></span>
      </div>

      <div class="sinal-central">×</div>

      <div class="bloco-com-label">
        <label class="subtitulo"></label>
        <span class="bloco">30</span>
      </div>

      <div class="sinal-central">=</div>

      <div class="bloco-com-label">
        <label class="subtitulo"></label>
        <span class="bloco resultado">R$ <?php echo number_format($meia_unidade_mensal, 2, ',', '.'); ?></span>
      </div>

       </div>
      </div>
      </fieldset>
      <?php endif; ?>
</div>



<div class="bloco-valor-anual">
  <div class="titulo-valor-anual">Valor da Sua Meta Anual</div>
   <?php if (isset($meia_unidade_mensal)): 
   $resultado_anual = $meia_unidade_mensal * 12;
   ?>
    <fieldset class="traco-unidade unidade-especial unidade-anual">
   
  <div class="valor-unidade">
    <div class="linha-unidade">

      <div class="bloco-com-label">
        <label class="subtitulo"></label>
        <span class="bloco">R$ <?php echo number_format($meia_unidade_mensal, 2, ',', '.'); ?></span>
      </div>

      <div class="sinal-central">×</div>

      <div class="bloco-com-label">
        <label class="subtitulo"></label>
        <span class="bloco">12</span>
      </div>

      <div class="sinal-central">=</div>

      <div class="bloco-com-label">
        <label class="subtitulo"></label>
        <span class="bloco resultado">R$ <?php echo number_format($resultado_anual, 2, ',', '.'); ?></span>
      </div>

      </div>
     </div>
     </fieldset>
    <?php endif; ?>
</div>









<div class="centralizar">
    <div class="text-resultado">

        <?php if ($soma_saque !== null): ?>
            <label>Total de Saques-------- R$ <?php echo number_format($soma_saque, 2, ',', '.'); ?></label>
        <?php endif; ?>

    </div>
 </div>
</div>




  </body>



<script>
document.addEventListener("DOMContentLoaded", function () {
  const acao = document.getElementById("acao");
  const valor = document.getElementById("valor");

  function formatarMoeda(valorStr) {
    const numero = parseFloat(valorStr.replace(/[^\d,.-]/g, "").replace(",", "."));
    if (isNaN(numero)) return "";
    return numero.toLocaleString("pt-BR", {
      style: "currency",
      currency: "BRL"
    });
  }

  function formatarPorcentagem(valorStr) {
    let limpo = valorStr.replace(",", ".").replace(/[^\d.]/g, "");
    if (limpo === "") return "";
    const numero = parseFloat(limpo);
    return isNaN(numero) ? "" : numero.toString().replace(".", ",") + "%";
  }

  // 👇 Sempre que trocar a ação, limpa o campo
  acao.addEventListener("change", function () {
    valor.value = "";
  });

  valor.addEventListener("focus", function () {
    this.value = ""; // Limpa o campo para nova digitação
  });

  valor.addEventListener("blur", function () {
    const acaoSelecionada = acao.value;
    const valorAtual = valor.value;

    if (!valorAtual) return;

    if (acaoSelecionada === "deposito" || acaoSelecionada === "saque") {
      this.value = formatarMoeda(valorAtual);
    } else if (acaoSelecionada === "diaria") {
      this.value = formatarPorcentagem(valorAtual);
    }


  });
});
</script>




<script>
  function toggleDropdown() {
    const options = document.getElementById('dropdown-options');
    options.style.display = options.style.display === 'block' ? 'none' : 'block';
  }

  function selectOption(texto, valor) {
    document.getElementById('dropdown-selected').innerText = texto;
    document.getElementById('acao').value = valor;
    document.getElementById('dropdown-options').style.display = 'none';
  }

  // Fecha dropdown se clicar fora
  window.addEventListener('click', function (e) {
    const dropdown = document.querySelector('.dropdown');
    if (!dropdown.contains(e.target)) {
      document.getElementById('dropdown-options').style.display = 'none';
    }
  });
</script>


