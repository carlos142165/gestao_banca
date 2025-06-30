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
     
       body{
            font-family: Arial, Helvetica, sans-serif;
            background-image: linear-gradient(to right, #255f75, #1d4d5f);      /* RESPONSAVEL PELO CORPO DA PAGINA*/
        }





        /* RESPONSAVEL PELO CAMPO DO FORMULARIO DE OPÇÕES*/
        .box{
            color: #eeeded;
            position: absolute;
            top: 30%;
            left: 50%;
            transform: translate(-50%,-50%);   /* RESPONSAVEL PELA ESTRUTURA TOTAL*/
            background-color: #113647;
            padding: 10px;
            border-radius: 8px;
            width: 80%;
            max-width: 400px; 
        }

          .labelinput{
            position: absolute;
            top: 0px;
            left: 0px;
            pointer-events: none;              /* RESPONSAVEL PELO TEXTO VALOR*/
            transition: .5s;
            font-size: 12px;
            font-weight: bold;
            color: #f5f3f3;
        }

        .dropdown-header {
            background-color: #113647;
            color: #f5f3f3;
            border-radius: 6px;
            cursor: pointer;                     /* RESPONSAVEL PELO TEXTO SELECIONAR */
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: bold;
            font-size: 13px;
        }

        .dropdown {
            position: relative;
            width: 100%;                        /* RESPONSAVEL PELO TEXTO E PELO TAMANHO DO CAMPO DE SELEÇÃO */
            font-family: Arial, sans-serif;
        }

        .dropdown-options {
            list-style: none;
            padding: 0;
            margin: 6px 0 0 0;
            background-color: #0e2a38;
            border-radius: 6px;                  /* RESPONSAVEL PELO CAMPO DO FUNDO DA SELEÇÃO E PELOS TEXTOS */
            position: absolute;
            width: 100%;
            display: none;
            z-index: 10;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            font-size: 13px;
          }

        .dropdown-options li {
            padding: 10px 14px;
            color:rgb(218, 223, 223);     /* RESPONSAVEL PELOS TEXTOS COR E MOUSE AO PASSAR */
            cursor: pointer;
            transition: background 0.3s;
        }

        .dropdown-options li:hover {
            background-color: #1e4a5a;   /* RESPONSAVEL PELOS TEXTOS COR E MOUSE AO PASSAR */
        }

        .arrow {
            
        }


        .inputUser{
            background: none;
            border: none;
            border-bottom: 1px solid #255f75;   /* RESPONSAVEL PELO TEXTO E CAMPO  DIGITADO NO CAMPO DO VALOR */
            width: 100%;
            outline: none;
            color: #eeeded;
            font-size: 12px;
            letter-spacing: 1px;
            box-sizing: border-box;
        }

        .inputbox {
             position: relative;      
             cursor: pointer;         /* RESPONSAVEL PELA POSIÇÃO DO TEXTO DO CAMPO VALOR E TAMANHO  */
             font-size: 12px;
        }

        .inputUser option {
           background-color: #0e2a38;  /* RESPONSAVEL PELO TEXTO AO CLICAR DENTRO DO CAMPO VALOR MUDAR PRA COR AZUL */
          color: #ffffff;
         }

        

        .inputUser:focus ~ .labelinput,
        .inputUser:valid ~ .labelinput{   /* RESPONSAVEL PELO TEXTO AO CLICAR DENTRO DO CAMPO VALOR REDUZIR O TAMANHO  */
            top: -20px;
            font-size: 12px;
            color: #2d7592;
        }
        

        fieldset{
            border: 3px solid #255f75;   /* RESPONSAVEL PELA BORDA AO REDOR  */
        }

        legend{
              
            padding: 7px;
            text-align: center;
            background-color: #255f75;     /* RESPONSAVEL PELA COR DO FUNDO DO TEXTO DE OPÇÕES  */
            border-radius: 3px;
            font-size: 15px;
            
        }

        #submit:hover{
            background-color: #1e5165;       /* RESPONSAVEL PELA COR AO PASAR O MOUSE NO BOTÃO  */

        }

        #submit{
            background-color: #255f75;
            width: 100%;
            border: none;
            padding: 10px;
            border-radius: 10px;   /* RESPONSAVEL PELO  BOTÃO  */
            color: #eeeded;
            font-size: 12px;
            cursor: pointer;

        }  /* FIM DO CODIGO RESPONSAVEL PELO CAMPO DO FORMULARIO DE OPÇÕES*/


         











        /* RESPONSAVEL PELO CODIGO DOS TEXTOS LABEL DE RETORNO DA BANCA*/
        .centralizar {
           display: flex;
           justify-content: center;
           align-items: center;
           min-height: 100vh; /* ocupa altura total da tela */
    
        }

        .text-resultado {
            font-size: 12px;
            padding: 20px;
            border-radius: 8px;
            background-color: #113647;
            color: #e6e5e3; 
            width: 80%;
            max-width: 400px;
            font-family: Arial, sans-serif;
            text-align: left;
        }
        /* FIM DO CODIGO RESPONSAVEL PELO CODIGO DOS TEXTOS LABEL DE RETORNO DA BANCA*/










         /* RESPONSAVEL PELOS CODIGO DOS BLOCOS DE VALORES DA GESTÃO DA BANCA */
        .valor-unidade{
          text-align: center;
          font-size: 12px;
          color: #e2b14d;   /* RESPONSAVEL PELOS TXTOS DOS 2 BLOCOS BANCA E PORCENTAGEM */
          margin-top: 8px;
          margin: 20px 0;
          font-family: Arial, sans-serif;
          margin-top: 40px;

          } 

        .resultado {
          background-color: #113647;    /* RESPONSAVEL PELOS TXTOS DO  BLOCO VALOR DA UNDADE*/
          color: #e2b14d;
          }

        .linha-unidade {
          display: flex;
          align-items: center;      /* RESPONSAVEL PELOS ALINHAMENTO DOS TXTOS DOS  BLOCO VALOR DA UNDADE*/
          gap: 12px;
          justify-content: center;
         
        }

        .bloco-com-label {
          display: flex;
          flex-direction: column;
          align-items: center;      /* RESPONSAVEL PELOS ALINHAMENTO DOS  DOS  BLOCO PORCENTAGEM E UNIDADE*/
          text-align: center;
          width: 70px; 
          flex-shrink: 0; 
        }

        .bloco-com-label-banca {
          display: flex;
          flex-direction: column;
          align-items: center;      /* RESPONSAVEL PELOS ALINHAMENTO DO BLOCO DA BANCA*/
          text-align: center;
          flex-shrink: 0;
        }

        .bloco {
          background-color: #113647;
          padding: 8px 12px;
          border-radius: 7px;
          font-weight: bold;       /* RESPONSAVEL PELO FUNDO E ELEMENTO DE CADA BLOCO*/
          font-size: 12px;
          width: 100%; 
          text-align: center;
        }

        .subtitulo {
          font-size: 12px;
          margin-bottom: 4px;   /* RESPONSAVEL PELO TITULO DOS BLOCOS*/
          color: #d5d6d6;
        }

        .sinal-central {
          font-size: 1.1em;
          padding: 6px;
          font-weight: bold;   /* RESPONSAVEL PELOS SINAIS DOS BLOCOS*/
          color: #d5d6d6;
          position: relative;
          top: 8px; 
        }
        /* FIM DO CODIGO RESPONSAVEL PELOS CODIGO DOS BLOCOS DE VALORES DA GESTÃO DA BANCA */

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

  <div class="box">
  <form action="painel-controle.php" method="POST">
    <fieldset>
      <legend><b>Selecione a Opção!</b></legend>
      <br><br>

      <div class="dropdown">
        <div class="dropdown-header" onclick="toggleDropdown()">
    <span id="dropdown-selected">Selecione</span>
    <span class="arrow">&#9662;</span>
  </div>
  <ul class="dropdown-options" id="dropdown-options">
    <li onclick="selectOption('Depositar na Banca', 'deposito')">Depositar na Banca</li>
    <li onclick="selectOption('Porcentagem Sobre a Banca', 'diaria')">Porcentagem Sobre a Banca</li>
    <li onclick="selectOption('Sacar', 'saque')">Sacar da Banca</li>
  </ul>
  <input type="hidden" name="acao" id="acao">
</div>
      <br><br>

      <div class="inputbox">
        <input type="text" name="valor" id="valor" class="inputUser" required>
        <label for="valor" class="labelinput">Valor</label>
      </div>
      <br><br>

      <input type="submit" name="submit" id="submit" value="Salvar" />

      
    </fieldset>
    </form>
    </div>

  <div class="centralizar">
    <div class="text-resultado">

        <?php if (isset($_SESSION['usuario_id'])): ?>
            Depositado na Banca----------R$ <?php echo number_format($soma_depositos, 2, ',', '.'); ?>
        <?php endif; ?>
        <br><br>

        <?php if ($ultima_diaria !== null): ?>
            <label>Porcentagem da Banca------- <?php echo number_format($ultima_diaria, 2, ',', '.'); ?>%</label>
        <?php endif; ?>
        <br><br>

        <?php if ($soma_saque !== null): ?>
            <label>Total de Saques-------- R$ <?php echo number_format($soma_saque, 2, ',', '.'); ?></label>
        <?php endif; ?>

    </div>
</div>

    

     


<?php if (isset($_SESSION['usuario_id']) && $ultima_diaria > 0 && $soma_depositos > 0): 
  $resultado = ($ultima_diaria / 100) * $soma_depositos;
?>
  <div class="valor-unidade">
    
    <div class="linha-unidade">

      <div class="bloco-com-label-banca">
        <label class="subtitulo">Banca</label>
        <span class="bloco">R$ <?php echo number_format($soma_depositos, 2, ',', '.'); ?></span>
      </div>

      <div class="sinal-central">×</div>

      <div class="bloco-com-label">
        <label class="subtitulo">Porcentagem</label>
        <span class="bloco"><?php echo number_format($ultima_diaria, 2, ',', '.'); ?>%</span>
      </div>

      <div class="sinal-central">=</div>

      <div class="bloco-com-label">
        <label class="subtitulo">$Unidade</label>
        <span class="bloco resultado">R$ <?php echo number_format($resultado, 2, ',', '.'); ?></span>
      </div>

    </div>
  </div>
<?php endif; ?>







    



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


