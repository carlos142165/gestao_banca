

<?php
session_start();
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
        $_SESSION['mensagem'] = ucfirst($acao) . ' Feito com Sucesso!';
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
  gap: 10px;
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









<?php if ($ultima_diaria > 0 && $saldo_reais > 0): ?>
<div class="bloco-unidade">

  <!-- 💰 Saldo da Banca -->
  <div class="valor-item">
    <i class="valor-icone fa fa-piggy-bank"></i>
    <div>
      <span class="valor-bold">R$ <?= number_format($saldo_reais, 2, ',', '.') ?></span><br>
      <span class="valor-desc">Banca</span>
    </div>
  </div>

  <!-- 📤 Total de Saques -->
  <?php if ($soma_saque > 0): ?>
  <div class="valor-item">
    <i class="valor-icone fa fa-hand-holding-usd"></i>
    <div>
      <span class="valor-bold">R$ <?= number_format($saques_reais, 2, ',', '.') ?></span><br>
      <span class="valor-desc">Total de Saques</span>
    </div>
  </div>
  <?php endif; ?>

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
      <span class="valor-bold">R$ <?= number_format($resultado, 2, ',', '.') ?></span><br>
      <span class="valor-desc">Unidade de Entrada</span>
    </div>
  </div>

  <!-- 🕐 Meta Diária -->
  <div class="valor-item">
    <i class="valor-icone fa fa-balance-scale"></i>
    <div>
      <span class="valor-bold">R$ <?= number_format($meia_unidade, 2, ',', '.') ?></span><br>
      <span class="valor-desc">Meta Diária</span>
    </div>
  </div>

  <!-- 📅 Meta Mensal -->
  <div class="valor-item">
    <i class="valor-icone fa fa-calendar-day"></i>
    <div>
      <span class="valor-bold">R$ <?= number_format($meia_unidade_mensal, 2, ',', '.') ?></span><br>
      <span class="valor-desc">Meta Mensal</span>
    </div>
  </div>

  <!-- 📈 Meta Anual -->
  <div class="valor-item">
    <i class="valor-icone fa fa-calendar-alt"></i>
    <div>
      <span class="valor-bold">R$ <?= number_format($resultado_anual, 2, ',', '.') ?></span><br>
      <span class="valor-desc">Meta Anual</span>
    </div>
  </div>

</div>
<?php endif; ?>








   
  
       





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

    valor.addEventListener("keypress", function (e) {
      const char = e.key;
      const value = valor.value;
      // Permite números e um único ponto
      if (!/[0-9.]/.test(char) || (char === '.' && value.includes('.'))) {
        e.preventDefault();
      }
    });

    valor.addEventListener("input", function () {
      const value = valor.value;
      // Impede valores inválidos como apenas ponto
      if (value === '.' || isNaN(Number(value))) {
        valor.value = '';
      }
    });
  });
</script>


