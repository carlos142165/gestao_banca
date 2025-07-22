
<?php
session_start();
require_once 'config.php';

// Verifica se é o primeiro login
if ($_SESSION['primeiro_login'] ?? true) {
  $_SESSION['primeiro_login'] = false;

  echo "
  <!DOCTYPE html>
  <html lang='pt-br'>
  <head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Boas-Vindas</title>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
    <style>
      body {
        margin: 0;
        padding: 0;
        font-family: 'Segoe UI', sans-serif;
        background-color: #f0f2f5;
      }

      .intro-overlay {
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100vh;
        background: rgba(0,0,0,0.6);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
      }

      .intro-box {
        background: #fff;
        padding: 40px;
        border-radius: 16px;
        box-shadow: 0 0 30px rgba(0,0,0,0.3);
        width: 90%;
        max-width: 600px;
        text-align: center;
        animation: fadeSlide 0.7s ease-out;
      }

      .intro-box h1 {
        font-size: 28px;
        margin-bottom: 20px;
        color: #2c3e50;
      }

      .descricao {
        font-size: 18px;
        margin-bottom: 30px;
        color: #444;
      }

      .etapas {
        display: flex;
        flex-direction: column;
        gap: 24px;
        margin-bottom: 20px;
        text-align: left;
      }

      .etapa {
        display: flex;
        gap: 14px;
        align-items: flex-start;
      }

      .etapa i {
        font-size: 26px;
        color: #27ae60;
        margin-top: 4px;
        min-width: 30px;
        text-align: center;
      }

      .etapa div {
        font-size: 17px;
        color: #34495e;
        line-height: 1.6;
      }

      .final {
        font-size: 18px;
        margin-top: 18px;
        color: #2980b9;
      }

      .intro-box button {
        margin-top: 25px;
        padding: 14px 30px;
        background: #27ae60;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 17px;
        cursor: pointer;
        transition: background 0.3s;
      }

      .intro-box button:hover {
        background: #219150;
      }

      @keyframes fadeSlide {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
      }

      @media screen and (max-width: 390px) {
        .intro-box {
          padding: 30px 20px;
        }
        .intro-box h1 {
          font-size: 26px;
        }
        .descricao {
          font-size: 19px;
        }
        .etapa div {
          font-size: 18px;
        }
        .etapa i {
          font-size: 30px;
        }
        .final {
          font-size: 18px;
        }
        .intro-box button {
          font-size: 18px;
          padding: 14px 28px;
        }
      }
    </style>
  </head>
  <body>
    <div class='intro-overlay'>
      <div class='intro-box'>
        <h1>🎉 Bem-vindo, novo usuário!</h1>
        <p class='descricao'>Comece em apenas <strong>2 passos simples</strong>:</p>

        <div class='etapas'>
          <div class='etapa'>
            <i class='fas fa-piggy-bank'></i>
            <div>
              <strong>1º Passo:</strong> Vá até o <em>Painel de Controle</em> e clique em <strong>Depositar na Banca</strong>. Defina o valor da sua banca.
            </div>
          </div>
          <div class='etapa'>
            <i class='fas fa-percentage'></i>
            <div>
              <strong>2º Passo:</strong> Ainda no Painel, selecione <strong>Defina a Porcentagem</strong> e defina seu percentual favorito.
            </div>
          </div>
        </div>

        <p class='final'>✔️ Pronto! Agora você já pode usar o sistema. Boa sorte!</p>

        <button onclick=\"location.href='painel-controle.php'\">Avançar</button>
      </div>
    </div>
  </body>
  </html>
  ";
  exit;
}
?>


<?php

include_once('config.php');

// 🔐 Verifica login
if (!isset($_SESSION['usuario_id'])) {
    echo "<script>alert('ÁREA DE MEMBROS – Faça Já Seu Cadastro Gratuito'); window.location.href = 'home.php';</script>";
    exit();
}

// 🧠 ID do usuário
$id_usuario = $_SESSION['usuario_id'];

// 🔁 Processa ações (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'], $_POST['valor'])) {
    $acao = $_POST['acao'];
    $valor = preg_replace('/[^0-9,]/', '', $_POST['valor']);
    $valor = str_replace(',', '.', $valor);
    $valorFloat = is_numeric($valor) ? (float)$valor : 0;

    // 🧹 Limpar banca
    if ($acao === 'limpar') {
        $stmt = mysqli_prepare($conexao, "DELETE FROM controle WHERE id_usuario = ?");
        mysqli_stmt_bind_param($stmt, "i", $id_usuario);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $_SESSION['mensagem'] = 'Banca Limpa Com Sucesso!';
        header('Location: painel-controle.php');
        exit;
    }

    // 🔒 Bloqueia saque sem saldo
    if ($acao === 'saque') {
        $stmt = mysqli_prepare($conexao, "
            SELECT COALESCE(SUM(deposito), 0) - COALESCE(SUM(saque), 0)
            FROM controle WHERE id_usuario = ?");
        mysqli_stmt_bind_param($stmt, "i", $id_usuario);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $saldo_banca);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        if ($valorFloat > $saldo_banca || $saldo_banca <= 0) {
            $_SESSION['mensagem'] = 'Saldo Insuficiente. Você Só Pode Sacar Até R$ ' . number_format($saldo_banca, 2, ',', '.');
            header('Location: painel-controle.php');
            exit;
        }
    }

    // 💾 Insere valor (depósito, saque, diária)
    if (in_array($acao, ['deposito', 'saque', 'diaria']) && $valorFloat > 0) {
    $query = "INSERT INTO controle (id_usuario, $acao) VALUES (?, ?)";
    $stmt = mysqli_prepare($conexao, $query);
    mysqli_stmt_bind_param($stmt, "id", $id_usuario, $valorFloat);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // ✅ Mensagem personalizada para "diaria"
    $_SESSION['mensagem'] = ($acao === 'diaria')
        ? 'Porcentagem Definida com Sucesso!'
        : ucfirst($acao) . ' Feito com Sucesso!';

    header('Location: painel-controle.php');
    exit;
    }
}

// 🔎 Consulta depósitos
$stmt = mysqli_prepare($conexao, "SELECT SUM(deposito) FROM controle WHERE id_usuario = ?");
mysqli_stmt_bind_param($stmt, "i", $id_usuario);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $soma_depositos);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);
$soma_depositos = $soma_depositos ?: 0;

// 🔎 Consulta saques
$stmt = mysqli_prepare($conexao, "SELECT SUM(saque) FROM controle WHERE id_usuario = ?");
mysqli_stmt_bind_param($stmt, "i", $id_usuario);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $soma_saque);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);
$soma_saque = $soma_saque ?: 0;

// 🔎 Consulta última diária (porcentagem)
$stmt = mysqli_prepare($conexao, "
    SELECT diaria FROM controle
    WHERE id_usuario = ? AND diaria IS NOT NULL AND diaria != 0
    ORDER BY id DESC LIMIT 1
");
mysqli_stmt_bind_param($stmt, "i", $id_usuario);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $ultima_diaria);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);
$ultima_diaria = $ultima_diaria ?: 0;

// 🧮 Conversões e cálculos
$depositos_reais = $soma_depositos; 
$saques_reais = $soma_saque; 
$saldo_reais = $depositos_reais - $saques_reais;

$percentualFormatado = (intval($ultima_diaria) == $ultima_diaria)
    ? intval($ultima_diaria) . '%'
    : number_format($ultima_diaria, 2, ',', '.') . '%';

if ($ultima_diaria > 0 && $saldo_reais > 0) {
    $resultado = ($ultima_diaria / 100) * $saldo_reais;
    $meia_unidade = $resultado * 0.5;
    $meia_unidade_mensal = $meia_unidade * 30;
    $resultado_anual = $meia_unidade_mensal * 12;

    // ✅ Salvar na sessão
    $_SESSION['meta_meia_unidade'] = $meia_unidade;
    // ✅ Salvar na sessão
    $_SESSION['resultado_entrada'] = $resultado; // 👈 NOVO!
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
      height: 100%;
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #0f2e3a, #295a6f, #4e8b9e);
      margin: 0;
      padding: 0;
      color: #f5f5f5;
    }

    .container-principal {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 24px;
      padding-top: 30px;
      
      
    }

    .box {
  background: linear-gradient(135deg, #0f2e3a, #295a6f, #4e8b9e);
  padding: 10px;
  border-radius: 8px;
  width: 320px;
  color: #f5f5f5;
    }

    fieldset {
      border-radius: 8px;
      background: linear-gradient(135deg, #0f2e3a, #295a6f, #4e8b9e);
      padding: 1.4em 1.1em 1.1em;
      background-color: #1e5165;
      border: none;
    }

    .dropdown {
      position: relative;
      width: 280px; /* reduzido proporcionalmente */
      margin-top: -10px;
      margin-bottom: 35px; /* espaçamento entre dropdown e input */
}

    .dropdown-header {
      background-color:rgb(84, 173, 202);
      color: #113647;
      border-radius: 6px;
      cursor: pointer;
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-weight: bold;
      font-size: 13px;
      padding: 7px;
      transition: background 0.3s ease;
      
    }

    .dropdown-header:hover {
      background-color:rgb(84, 173, 202);
      border-radius: 6px;
    }

    .dropdown-options {
      position: absolute;
      z-index: 999;
      background-color: #113647;
      border: 1px solid #ccc;
      width: 259px;
      max-height: 200px;
      overflow-y: auto;
      display: none;
      font-size: 13px;
      border-radius: 6px;
      margin-top: 2px;
      border: none;
      width: 243px;
    }

    .dropdown-options li {
      
      padding: 10px 14px;
      color: #dfdede;
      cursor: pointer;
      font-weight: bold;
      transition: background 0.3s ease;
      border-radius: 6px;
    }

    .dropdown-options li:hover {
      background-color: #24a6d1;
      color: white;
      border-radius: 6px;
    }

    .inputbox {
      position: relative;
      font-size: 12px;
      margin-top: 10px;
    }

    .inputUser {
      background: none;
      border: none;
      border-bottom: 2px solid  #113647;
      width: 280px;
      outline: none;
      color:  #dfdede;
      font-size: 12px;
      font-weight: bold;
      width: 279px;
      
      
    }

    .labelinput {
      position: absolute;
      top: 0px;
      left: 0px;
      pointer-events: none;
      transition: .5s;
      font-size: 12px;
      font-weight: bold;
      color: #dfdede;
      
    }

    .inputUser:focus ~ .labelinput,
    .inputUser:valid ~ .labelinput {
      top: -20px;
      font-size: 12px;
      color: #24a6d1;
    }

    #submit {
      background-color: #113647;
      color: #dfdede;
      font-weight: bold;
      width: 100%;
      border: none;
      padding: 10px;
      border-radius: 10px;
      cursor: pointer;
      font-size: 12px;
      transition: background-color 0.3s ease;
      width: 283px;
    }

    #submit:hover {
      background-color:rgb(26, 60, 77);
      color: white;
    }
    /* FIM DO CODIGO RESPONSAVEL PELO FORMULARIO DE CADASTRO" */






     /* CODIGO RESPONSAVEL PELO CALCULO DOS VALORES PARA GESTÃO */

    @keyframes fadeSlide {
  from {
    opacity: 0;
    transform: translateY(15px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}




/* CODIGO RESPONSAVEL PELO CALCULO DOS VALORES PARA GESTÃO */
.bloco-unidade {
  width: 300px;
  background-color: #19475a;                
  border-radius: 12px;
  padding: 15px;
  font-family: 'Segoe UI', sans-serif;
  
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 8px;
  animation: fadeSlide 0.6s ease;
}

.valor-item {
  display: flex;
  align-items: center;
  background: linear-gradient(145deg,rgb(231, 232, 233),rgb(216, 216, 218));    
  border-radius: 8px;
  padding: 8px 10px;
  gap: 7px;
  box-shadow: 0 4px 6px rgba(0,0,0,0.08);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.valor-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 2px 5px rgba(0,0,0,0.12);
}

.valor-icone {
  font-size: 20px;
  color: #7e57c2;
  min-width: 22px;
}

.valor-texto {
  display: flex;
  flex-direction: column;
}

.valor-bold {
  font-weight: bold;
  font-size: 15px;
  color: #333;
  margin-bottom: 2px;
}

.valor-desc {
  font-size: 12px;
  color: #666;
  margin: 0;
}

.mensagem-status {
  color:rgb(224, 193, 13);
  padding: 10px 16px;
  border-radius: 8px;
  margin-bottom: 15px;
  text-align: center;
  box-shadow: 0 2px 6px rgba(0,0,0,0.2);
  animation: fadeSlide 0.5s ease;
  font-size: 12px;
}

.btn-fechar{
  margin-left: 12px;
  background-color: #ffc107;
  color: #1d1d1d;
  font-size: 12px;
  border: none;
  border-radius: 6px;
  padding: 4px 10px;
  cursor: pointer;
  transition: background-color 0.3s ease;
}
/* FIM DO CODIGO RESPONSAVEL PELO CALCULO DOS VALORES PARA GESTÃO */








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




<?php if (isset($_SESSION['mensagem'])): ?>
  <div class="mensagem-status" id="mensagemStatus">
    <i class="fa-solid fa-triangle-exclamation"></i>
    <span><?= $_SESSION['mensagem'] ?></span>
    <button class="btn-fechar" onclick="document.getElementById('mensagemStatus').style.display='none'">OK</button>
  </div>
  <?php unset($_SESSION['mensagem']); ?>
<?php endif; ?>





<div class="container-principal">
  <div class="box">
    <form action="painel-controle.php" method="POST">
      <fieldset>
        <div class="dropdown">
          <div class="dropdown-header" onclick="toggleDropdown()">
            <span id="dropdown-selected"><i class="fa-solid fa-bars"></i> Selecione</span>
            <span class="arrow">&#9662;</span>
          </div>
          <ul class="dropdown-options" id="dropdown-options">
            <li onclick="selectOption('Depositar na Banca', 'deposito')">
              <i class="fa-solid fa-money-bill-wave"></i> Depositar na Banca
            </li>
            <li onclick="selectOption('Defina a porcentagem', 'diaria')">
              <i class="fa-solid fa-chart-line"></i> Defina a porcentagem
            </li>
            <li onclick="selectOption('Sacar da Banca', 'saque')">
              <i class="fa-solid fa-arrow-down"></i> Sacar da Banca
            </li>
            <li onclick="selectOption('Limpar Banca', 'limpar')">
              <i class="fa-solid fa-trash"></i> Limpar Banca
            </li>
          </ul>
          <input type="hidden" name="acao" id="acao">
        </div>

        <div class="inputbox" id="valorInputBox">
          <input type="text" name="valor" id="valor" class="inputUser" required>
          <label for="valor" class="labelinput"><i class="fa-solid fa-coins"></i> Valor</label>
        </div>

        <br>
        <input type="submit" name="submit" id="submit" value="Enviar">
      </fieldset>
    </form>
  </div>
 </div>



 <br>









<!-- ✅ Bloco de valores SEM condicional de exibição -->
<div class="bloco-unidade">

  <!-- 💰 Saldo da Banca -->
  <div class="valor-item">
    <i class="valor-icone fa fa-piggy-bank"></i>
    <div>
      <span class="valor-bold">R$ <?= number_format($saldo_reais, 2, ',', '.') ?></span><br>
      <span class="valor-desc">Banca</span>
    </div>
  </div>

  <!-- 📉 Porcentagem -->
  <div class="valor-item">
    <i class="valor-icone fa fa-chart-line"></i>
    <div>
      <span class="valor-bold"><?= $percentualFormatado ?></span><br>
      <span class="valor-desc">Porcentagem</span>
    </div>
  </div>

  

    <!-- 🎯 Unidade de Entrada -->
  <div class="valor-item">
    <i class="valor-icone fa fa-database"></i>
    <div>
      <span class="valor-bold">R$ <?= number_format($resultado ?? 0, 2, ',', '.') ?></span><br>
      <span class="valor-desc">Unidade de Entrada</span>
    </div>
  </div>

  <!-- 🕐 Meta Diária -->
  <div class="valor-item">
    <i class="valor-icone fa fa-balance-scale"></i>
    <div>
      <span class="valor-bold">R$ <?= number_format($meia_unidade ?? 0, 2, ',', '.') ?></span><br>
      <span class="valor-desc">Meta Diária</span>
    </div>
  </div>

  <!-- 📅 Meta Mensal -->
  <div class="valor-item">
    <i class="valor-icone fa fa-calendar-day"></i>
    <div>
      <span class="valor-bold">R$ <?= number_format($meia_unidade_mensal ?? 0, 2, ',', '.') ?></span><br>
      <span class="valor-desc">Meta Mensal</span>
    </div>
  </div>

  <!-- 📈 Meta Anual -->
  <div class="valor-item">
    <i class="valor-icone fa fa-calendar-alt"></i>
    <div>
      <span class="valor-bold">R$ <?= number_format($resultado_anual ?? 0, 2, ',', '.') ?></span><br>
      <span class="valor-desc">Meta Anual</span>
    </div>
  </div>

  <!-- 📤 Total de Saques -->
  <div class="valor-item">
    <i class="valor-icone fa fa-hand-holding-usd"></i>
    <div>
      <span class="valor-bold">R$ <?= number_format($saques_reais, 2, ',', '.') ?></span><br>
      <span class="valor-desc">Total de Saques</span>
    </div>
  </div>

</div>









   
  
       






<script>
document.addEventListener("DOMContentLoaded", function () {
  const acao = document.getElementById("acao");
  const valorInputBox = document.getElementById("valorInputBox");
  const valorInput = document.getElementById("valor");
  const formulario = document.querySelector("form");
  const dropdownSelected = document.getElementById("dropdown-selected");
  const dropdownOptions = document.getElementById("dropdown-options");

  function atualizarCampoValor() {
    if (acao.value === "limpar") {
      valorInputBox.style.display = "none";
      valorInput.removeAttribute("required");
    } else {
      valorInputBox.style.display = "block";
      valorInput.setAttribute("required", "required");
    }
  }

  acao.addEventListener("change", atualizarCampoValor);

  window.selectOption = function (texto, valor) {
    dropdownSelected.innerText = texto;
    acao.value = valor;
    dropdownOptions.style.display = "none";
    atualizarCampoValor();
  };

  // Envio automático sem confirmação
  // Mantém compatibilidade para "limpar" direto
  atualizarCampoValor(); // Executa ao carregar
});
</script>

<script>
  function toggleDropdown() {
    const options = document.getElementById('dropdown-options');
    options.style.display = options.style.display === 'block' ? 'none' : 'block';
  }

  function selectOption(text, value) {
    document.getElementById('dropdown-selected').innerHTML = text;
    document.getElementById('acao').value = value;
    document.getElementById('dropdown-options').style.display = 'none';
  }
</script>


<div id="meta-meia-unidade" data-meta="R$ <?= number_format($meia_unidade ?? 0, 2, ',', '.') ?>" style="display:none;"></div>
<div id="resultado-unidade" data-resultado="R$ <?= number_format($resultado ?? 0, 2, ',', '.') ?>" style="display:none;"></div>



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


<script>
  document.addEventListener("DOMContentLoaded", function () {
    const valor = document.getElementById("valor");

    // Permite número, vírgula e ponto — mas só um separador
    valor.addEventListener("keypress", function (e) {
      const char = e.key;
      const value = valor.value;

      // Permite números e UM separador (vírgula ou ponto)
      if (!/[0-9.,]/.test(char) || ((char === '.' || char === ',') && (value.includes('.') || value.includes(',')))) {
        e.preventDefault();
      }
    });

    // Valida o valor digitado, convertendo vírgula para ponto
    valor.addEventListener("input", function () {
      const value = valor.value;
      const numero = parseFloat(value.replace(',', '.'));

      if (value === '.' || value === ',' || isNaN(numero)) {
        valor.value = '';
      }
    });
  });
</script>



