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
            background-image: linear-gradient(to right, #255f75, #1d4d5f);
        }
        .box{
            color: #eeeded;
            position: absolute;
            top: 35%;
            left: 50%;
            transform: translate(-50%,-50%);
            background-color: #113647;
            padding: 15px;
            border-radius: 15px;
            width: 80%;
            max-width: 400px; 
            

        }

        fieldset{
            border: 3px solid #255f75;
        }

        legend{
            border: 1px solid #255f75;
            padding: 10px;
            text-align: center;
            background-color: #255f75;
            border-radius: 5px;
            
        }

        .inputbox{
            position: relative;

        }
        .inputUser{
            background: none;
            border: none;
            border-bottom: 1px solid #255f75;
            width: 100%;
            outline: none;
            color: #eeeded;
            font-size: 15px;
            letter-spacing: 1px;
            box-sizing: border-box;
        }

        .labelinput{
            position: absolute;
            top: 0px;
            left: 0px;
            pointer-events: none;
            transition: .5s;
            font-size: 15px;
        }

        .inputUser:focus ~ .labelinput,
        .inputUser:valid ~ .labelinput{
            top: -20px;
            font-size: 12px;
            color: #2d7592;
        }

        #submit{
            background-color: #255f75;
            width: 100%;
            border: none;
            padding: 15px;
            border-radius: 10px;
            color: #eeeded;
            font-size: 15px;
            cursor: pointer;

        }
        
        /* CODIGO RESPONSAVEL AO PASSAR O MOUSE MUDA A COR */
        #submit:hover{
            background-color: #1e5165;

        }

        /* CODIGO RESPONSAVEL EM DEIXAR MAIUCULA E MINUSCULA */
        #nome{
            text-transform: capitalize;
        }



         /* CODIGO RESPONSAVEL EM DAR ESTILO A VISUALIZAÇÃO DA SENHA */
        .inputbox {
             position: relative;
             cursor: pointer;
        }

        .toggle-password {
             position: absolute;
             top: 50%;
             right: 0px;
             transform: translateY(-125%);
             cursor: pointer;
             font-size: 18px;
             color: #ccc;
             user-select: none;
             max-height: -125%;
        }

         .inputSenha {
             padding-right: 30px; /* espaço para o ícone */
        }
        /* FIM DO CODIGO RESPONSAVEL EM DAR ESTILO A VISUALIZAÇÃO DA SENHA */


        


        .caps-aviso {
            position: absolute;
            top: 100%;
            left: 0;
            font-size: 12px;
            color: rgb(137, 137, 29);
            margin-top: -21px;
            visibility: hidden;
            height: 16px; /* reserva espaço */
        }

           .banca {
            text-align: center;
            font-size: 15px;
            color:rgb(230, 229, 227); /* opcional: tom dourado para manter o estilo anterior */
            margin-top: 360px;
            
           }

           .porcent{

            text-align: center;
            font-size: 15px;
            color:rgb(230, 229, 227); /* opcional: tom dourado para manter o estilo anterior */
            margin-top: 8px;


           }

           .saque{
            text-align: center;
            font-size: 15px;
            color:rgb(230, 229, 227); /* opcional: tom dourado para manter o estilo anterior */
            margin-top: 8px;

           }

           .valor-unidade{
            text-align: center;
            font-size: 15px;
            color:rgb(230, 229, 227); /* opcional: tom dourado para manter o estilo anterior */
            margin-top: 8px;

           }



.valor-unidade {
  margin: 20px 0;
  font-family: Arial, sans-serif;
  margin-top: 30px;
  
}

.titulo-unidade {
  font-weight: bold;
  margin-bottom: 8px;
  display: inline-block;
}

.linha-unidade {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}



.resultado {
  background-color: #113647;
  color: #d5d6d6;
}

.operador {
  font-weight: bold;
  font-size: 1.2em;
}

.linha-unidade {
  display: flex;
  align-items: center;
  gap: 12px;
  justify-content: center;
}

.bloco-com-label {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  width: 80px; /* defina um tamanho fixo */
  flex-shrink: 0; /* impede encolhimento */
}

.bloco {
  background-color: #113647;
  padding: 8px 12px;
  border-radius: 7px;
  font-weight: bold;
  font-size: 14px;
  width: 100%; /* ocupa toda a largura do container */
  text-align: center;
}

.subtitulo {
  font-size: 0.9em;
  margin-bottom: 4px;
  color: #d5d6d6;
}


.sinal-central {
  font-size: 1.2em;
  padding: 6px;
  font-weight: bold;
  color: #d5d6d6;
  position: relative;
  top: 8px; /* Ajuste esse valor até ficar do jeitinho que quiser */
}







        






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

      <div class="inputbox">
        
        <select name="acao" id="acao" class="inputUser" required>
          <option value="">Selecione</option>
          <option value="deposito">Depositar na Banca</option>
          <option value="diaria">Porcentagem Sobre a Banca</option>
          <option value="saque">Sacar</option>
        </select>
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


     <?php if (isset($_SESSION['usuario_id'])): ?>
      <div class="banca">
       Depositado na Banca----------R$ <?php echo number_format($soma_depositos, 2, ',', '.'); ?>
      </div>
     <?php endif; ?>


     <?php if ($ultima_diaria !== null): ?>
      <div class="porcent">
      <label>Porcentagem da Banca------- <?php echo number_format($ultima_diaria, 2, ',', '.'); ?>%</label>
      </div>
     <?php endif; ?>

     <?php if ($soma_saque !== null): ?>
      <div class="saque">
       <label>Total de Saques: R$ <?php echo number_format($soma_saque, 2, ',', '.'); ?></label>
       </div>
     <?php endif; ?>

     


     <?php if (isset($_SESSION['usuario_id']) && $ultima_diaria > 0 && $soma_depositos > 0): 
  $resultado = ($ultima_diaria / 100) * $soma_depositos;
?>
  <div class="valor-unidade">
    
    <div class="linha-unidade">

      <div class="bloco-com-label">
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

