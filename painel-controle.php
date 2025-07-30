<?php
session_start();
include_once('config.php');

if (!isset($_SESSION['usuario_id'])) {
    echo "<script>alert('ÁREA DE MEMBROS – Faça Já Seu Cadastro Gratuito'); window.location.href = 'home.php';</script>";
    exit();
}

$id_usuario = $_SESSION['usuario_id'];

// ✅ Busca últimos valores registrados
$stmt = mysqli_prepare($conexao, "
    SELECT id, deposito, diaria, unidade FROM controle 
    WHERE id_usuario = ? 
    ORDER BY id DESC LIMIT 1
");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$stmt->bind_result($controle_id, $valor_deposito, $valor_diaria, $valor_unidade);
$stmt->fetch();
$stmt->close();

// ✅ Trata envio do formulário com edição ou inserção
// ✅ Trata envio do formulário como sempre um NOVO cadastro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submitPersonalizado'])) {
    $deposito = isset($_POST['deposito']) ? preg_replace('/[^0-9,]/', '', $_POST['deposito']) : '';
    $diaria = isset($_POST['diaria']) ? preg_replace('/[^0-9,]/', '', $_POST['diaria']) : '';
    $unidade = isset($_POST['unidade']) ? preg_replace('/[^0-9,]/', '', $_POST['unidade']) : '';

    $depositoFloat = str_replace(',', '.', $deposito);
    $diariaFloat = str_replace(',', '.', $diaria);
    $unidadeFloat = str_replace(',', '.', $unidade);

    // 🔄 INSERÇÃO SEMPRE — registro novo
    $stmt = mysqli_prepare($conexao,
        "INSERT INTO controle (id_usuario, deposito, diaria, unidade) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iddd", $id_usuario, $depositoFloat, $diariaFloat, $unidadeFloat);
    $stmt->execute();
    $stmt->close();

    $_SESSION['mensagem'] = 'Dados cadastrados com sucesso!';
    header('Location: painel-controle.php');
    exit();
}


// 🧩 Continuação do processamento do formulário antigo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'], $_POST['valor'])) {
    $acao = $_POST['acao'];
    $valor = preg_replace('/[^0-9,]/', '', $_POST['valor']);
    $valor = str_replace(',', '.', $valor);
    $valorFloat = is_numeric($valor) ? (float)$valor : 0;

    if ($acao === 'limpar') {
        $stmt = mysqli_prepare($conexao, "DELETE FROM controle WHERE id_usuario = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $stmt->close();

        $stmt = mysqli_prepare($conexao, "DELETE FROM valor_mentores WHERE id_usuario = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $stmt->close();

        $_SESSION['mensagem'] = 'Banca e históricos dos mentores limpos com sucesso!';
        header('Location: painel-controle.php');
        exit();
    }

    if ($acao === 'saque') {
        $stmt = mysqli_prepare($conexao, "
            SELECT COALESCE(SUM(valor_green), 0) - COALESCE(SUM(valor_red), 0)
            FROM valor_mentores WHERE id_usuario = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $stmt->bind_result($saldo_mentores);
        $stmt->fetch();
        $stmt->close();

        if ($valorFloat > $saldo_mentores || $saldo_mentores <= 0) {
            $_SESSION['mensagem'] = 'Saldo Insuficiente!';
            header('Location: painel-controle.php');
            exit();
        }

        $stmt = mysqli_prepare($conexao, "INSERT INTO valor_mentores (id_usuario, valor_red) VALUES (?, ?)");
        $stmt->bind_param("id", $id_usuario, $valorFloat);
        $stmt->execute();
        $stmt->close();

        $stmt = mysqli_prepare($conexao, "INSERT INTO controle (id_usuario, saque, origem) VALUES (?, ?, 'mentor')");
        $stmt->bind_param("id", $id_usuario, $valorFloat);
        $stmt->execute();
        $stmt->close();

        $_SESSION['mensagem'] = 'Saque realizado com sucesso!';
        header('Location: painel-controle.php');
        exit();
    }

    if (in_array($acao, ['deposito', 'diaria']) && $valorFloat > 0) {
        $stmt = mysqli_prepare($conexao, "INSERT INTO controle (id_usuario, $acao) VALUES (?, ?)");
        $stmt->bind_param("id", $id_usuario, $valorFloat);
        $stmt->execute();
        $stmt->close();

        $_SESSION['mensagem'] = ($acao === 'diaria')
            ? 'Porcentagem Definida com Sucesso!'
            : ucfirst($acao) . ' Feito com Sucesso!';

        header('Location: painel-controle.php');
        exit();
    }
}

// 🔎 Consultas de valores
$stmt = mysqli_prepare($conexao, "SELECT COALESCE(SUM(deposito), 0) FROM controle WHERE id_usuario = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$stmt->bind_result($soma_depositos);
$stmt->fetch();
$stmt->close();

// ✅ Saques da banca (apenas essa origem será subtraída do saldo)
$stmt = mysqli_prepare($conexao, "
    SELECT COALESCE(SUM(saque), 0) 
    FROM controle 
    WHERE id_usuario = ? AND (origem IS NULL OR origem = 'banca')
");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$stmt->bind_result($saques_banca);
$stmt->fetch();
$stmt->close();

// ✅ Saques totais (incluindo mentores — só para exibição, não usado no cálculo da banca)
$stmt = mysqli_prepare($conexao, "
    SELECT COALESCE(SUM(saque), 0)
    FROM controle
    WHERE id_usuario = ?
");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$stmt->bind_result($saques_reais);
$stmt->fetch();
$stmt->close();

$stmt = mysqli_prepare($conexao, "SELECT COALESCE(SUM(valor_green), 0) FROM valor_mentores WHERE id_usuario = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$stmt->bind_result($valor_green);
$stmt->fetch();
$stmt->close();

$stmt = mysqli_prepare($conexao, "SELECT COALESCE(SUM(valor_red), 0) FROM valor_mentores WHERE id_usuario = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$stmt->bind_result($valor_red);
$stmt->fetch();
$stmt->close();

// ✅ Última Diaria registrada
$stmt = mysqli_prepare($conexao, "
    SELECT diaria FROM controle
    WHERE id_usuario = ? AND diaria IS NOT NULL AND diaria != 0
    ORDER BY id DESC LIMIT 1
");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$stmt->bind_result($ultima_diaria);
$stmt->fetch();
$stmt->close();
$ultima_diaria = $ultima_diaria ?: 0;

// ✅ Última unidade registrada
$stmt = mysqli_prepare($conexao, "
    SELECT unidade FROM controle 
    WHERE id_usuario = ? AND unidade IS NOT NULL 
    ORDER BY id DESC LIMIT 1
");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$stmt->bind_result($ultima_unidade);
$stmt->fetch();
$stmt->close();

// Se quiser usar em algum lugar, pode salvar na sessão:
$_SESSION['ultima_unidade'] = $ultima_unidade;

// 🧮 Cálculos finais
$saldo_mentores = $valor_green - $valor_red;
$saldo_reais = ($soma_depositos - $saques_banca) + $saldo_mentores;

$percentualFormatado = (intval($ultima_diaria) == $ultima_diaria)
    ? intval($ultima_diaria) . '%'
    : number_format($ultima_diaria, 2, ',', '.') . '%';

if ($ultima_diaria > 0 && $saldo_reais > 0) {
    $resultado = ($ultima_diaria / 100) * $saldo_reais;
    $meia_unidade = $resultado * 0.5;
    $_SESSION['meta_meia_unidade'] = $meia_unidade;
    $_SESSION['resultado_entrada'] = $resultado;
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

#valorBanca {
  font-size: 18px;
  padding: 12px;
  border: 2px solid #3498db;
  background-color: #eef6fc;
}

#porcentagem,
#unidadeMeta {
  width: 130px;
  padding: 8px;
}

#resultadoCalculo,
#resultadoUnidade {
  font-size: 14px;
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


/* Teste */
.custom-inputbox {
  display: flex;
  flex-direction: column;
  gap: 4px;
  margin-bottom: 16px;
}

.custom-inputbox label {
  font-size: 13px;
  color: #2c3e50;
  font-weight: 600;
}

.custom-inputbox input[type="text"] {
  border-radius: 6px;
  border: 1px solid #ccc;
  background-color: #fcfcfc;
  padding: 6px 8px;
  font-size: 13px;
  color: #2c3e50;
  width: 100%;
  max-width: 200px;
}

.custom-button {
  background-color: #3498db;
  color: white;
  font-size: 15px;
  padding: 12px 24px;
  border-radius: 10px;
  border: none;
  cursor: pointer;
  width: 100%; /* Agora ocupa toda a largura da área do formulário */
  display: block;
  margin-top: 10px;
  transition: background-color 0.3s ease, transform 0.2s ease;
}

.custom-button:hover {
  background-color: #2980b9;
  transform: scale(1.02);
}

.custom-inputbox div {
  display: flex;
  gap: 4px; /* reduz o espaço entre o input e o resultado */
  align-items: center;
}

#resultadoUnidade {
  margin-left: 0; /* remove qualquer margem que empurre o span */
  padding-left: 0; /* garante que não haja espaço extra */
}

.custom-inputbox span {
  text-align: left;
}



#valorBanca {
  font-size: 14px;
  padding: 8px 10px;
  background-color: #eef6fc;
  border: 2px solid #3498db;
  max-width: 350px;
}

/* Campos menores */
#porcentagem,
#unidadeMeta {
max-width: 260px;
}

  .dropdown-options {
    display: none;
  }

  .dropdown-options.show {
    display: block;
  }

  .modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(44, 62, 80, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 999;
}

.modal-content {
  background: #fefefe;
  padding: 20px;
  border-radius: 12px;
  max-width: 380px;
  width: 100%;
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
  font-family: 'Segoe UI', sans-serif;
  animation: fadeIn 0.4s ease;
  position: relative;
}

/* Botão fechar */
.btn-fechar {
  position: absolute;
  top: 10px;
  right: 12px;
  background: none;
  border: none;
  font-size: 20px;
  color: #aaa;
  cursor: pointer;
}

.btn-fechar:hover {
  color: #e74c3c;
}

.custom-inputbox label {
  display: block;
  font-size: 14px;
  margin-bottom: 6px;
  font-weight: 600;
  color: #34495e;
}
.custom-inputbox {
  margin-bottom: 20px;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}

@media screen and (max-width: 300px) {
  .container-principal, .box, .modal-content {
    width: 80%;
    margin: 0 auto;
    padding: 15px;
    box-sizing: border-box;
  }

  .custom-fieldset {
    padding: 15px;
  }

  .custom-inputbox input {
    font-size: 15px;
  }

  .custom-button {
    font-size: 15px;
    padding: 10px 18px;
  }

  .btn-fechar {
    top: 8px;
    right: 10px;
    width: 28px;
    height: 28px;
    font-size: 18px;
  }

  .dropdown-header, .dropdown-options li {
    font-size: 14px;
  }

  legend {
    font-size: 16px;
  }
}


/* Teste */





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





<!-- HTML -->
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
            <li onclick="selectOption('Depositar na Banca', 'deposito')" data-text="Depositar na Banca" data-value="deposito">
              <i class="fa-solid fa-money-bill-wave"></i> Gerenciar Banca
            </li>
            <li onclick="selectOption('Sacar da Banca', 'saque')" data-text="Sacar da Banca" data-value="saque">
              <i class="fa-solid fa-arrow-down"></i> Gerenciar Saque 
            </li>
            <li onclick="selectOption('Limpar Banca', 'limpar')" data-text="Limpar Banca" data-value="limpar">
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

<!-- Formulário de Depósito Personalizado -->
<!-- Modal de Depósito Personalizado -->
<div id="modalDeposito" class="modal-overlay" style="display: none;">
  <div class="modal-content">
    <form method="POST" action="">
      <button type="button" class="btn-fechar" onclick="fecharModal()">×</button>

      <div class="custom-inputbox">
        <label for="valorBanca"><i class="fa-solid fa-coins"></i>  Valor da Banca</label>
        <input type="text" name="deposito" id="valorBanca" required
            value="<?= isset($valor_deposito) ? number_format($valor_deposito, 2, ',', '.') : '' ?>">
      </div>

      <div class="custom-inputbox">
        <label for="porcentagem"><i class="fa-solid fa-chart-pie"></i> % Unidade de Entrada</label>
        <div style="display: flex; gap: 10px; align-items: center;">
          <input type="text" name="diaria" id="porcentagem" required
              value="<?= isset($valor_diaria) ? number_format($valor_diaria, 2, ',', '.') : '' ?>"
              style="flex: 1;">
          <span id="resultadoCalculo" style="font-weight: bold; color: #2c3e50;"></span>
        </div>
      </div>

      <div class="custom-inputbox">
        <label for="unidadeMeta"><i class="fa-solid fa-bullseye"></i> Unidade Para Meta Diária</label>
        <div style="display: flex; gap: 10px; align-items: center;">
          <input type="text" name="unidade" id="unidadeMeta" required
              value="<?= isset($valor_unidade) ? number_format($valor_unidade, 2, ',', '.') : '' ?>"
              style="flex: 1;">
          <span id="resultadoUnidade" style="font-weight: bold; color: #2c3e50;"></span>
        </div>
      </div>

      <input type="submit" name="submitPersonalizado"
          value="<?= isset($valor_deposito) ? 'Salvar Edição' : 'Cadastrar Dados' ?>"
          class="custom-button">
    </form>
  </div>
</div>







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

  <!-- 📤 Total de Saques -->
  <div class="valor-item">
    <i class="valor-icone fa fa-hand-holding-usd"></i>
    <div>
      <span class="valor-bold">R$ <?= number_format($saques_reais, 2, ',', '.') ?></span><br>
      <span class="valor-desc">Total de Saques</span>
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

  <!-- 🎯 Unidade Para Meta -->
  <div class="valor-item">
    <i class="valor-icone fa fa-briefcase"></i>
    <div>
      <span class="valor-bold">R$ <?= number_format($resultado ?? 0, 2, ',', '.') ?></span><br>
      <span class="valor-desc">Unidade Para Meta</span>
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

</div>

















</body>
</html>


<!-- AQUI CODIGO QUE CUIDA DA OPÇÃO DE GERENCIAR BANCA -->
<script>
document.addEventListener("DOMContentLoaded", function () {
  // 🧩 Referências
  const dropdownOptions = document.querySelectorAll('#dropdown-options li');
  const dropdownSelected = document.getElementById('dropdown-selected');
  const dropdownContainer = document.getElementById("dropdown-options");
  const acaoInput = document.getElementById("acao");
  const modal = document.getElementById("modalDeposito");
  const valorInputBox = document.getElementById("valorInputBox");
  const valorInput = document.getElementById("valor");
  const botaoAcao = document.getElementById("submit");
  const deposito = document.getElementById("valorBanca");
  const diaria = document.getElementById("porcentagem");
  const unidade = document.getElementById("unidadeMeta");
  const resultadoCalculo = document.getElementById("resultadoCalculo");

  // 🟢 Novo: Span para exibir valor calculado pela unidade
  const resultadoUnidade = document.createElement("span");
  resultadoUnidade.id = "resultadoUnidade";
  resultadoUnidade.style.fontWeight = "bold";
  resultadoUnidade.style.color = "#2c3e50";
  unidade.parentNode.appendChild(resultadoUnidade);

  // 🎛️ Dropdown
  dropdownOptions.forEach(item => {
    item.addEventListener('click', () => {
      const texto = item.getAttribute('data-text');
      const valor = item.getAttribute('data-value');
      selectOption(texto, valor);
    });
  });

  function selectOption(texto, valor) {
    dropdownSelected.innerHTML = `<i class="fa-solid fa-bars"></i> ${texto}`;
    acaoInput.value = valor;
    dropdownContainer.classList.remove("show");
    atualizarCampoValor();
    modal.style.display = valor === "deposito" ? "flex" : "none";
  }

  function toggleDropdown() {
    dropdownContainer.classList.toggle("show");
  }

  window.selectOption = selectOption;
  window.toggleDropdown = toggleDropdown;

  window.addEventListener('click', (e) => {
    if (!document.querySelector('.dropdown').contains(e.target)) {
      dropdownContainer.classList.remove("show");
    }
    if (e.target === modal) modal.style.display = "none";
  });

  window.fecharModal = () => modal.style.display = "none";

  acaoInput.addEventListener("change", () => {
    valorInput.value = "";
    atualizarCampoValor();
  });

  function atualizarCampoValor() {
    const valorAcao = acaoInput.value;
    valorInputBox.style.display = valorAcao === "limpar" ? "none" : "block";
    if (valorAcao === "limpar") {
      valorInput.removeAttribute("required");
      botaoAcao.value = "Limpar Banca 💣";
    } else {
      valorInput.setAttribute("required", "required");
      botaoAcao.value = "Enviar";
    }
  }

  // 🎯 Cálculo meta automático
  function calcularMeta() {
    const banca = deposito.value.replace(/[^\d]/g, '');
    const percentual = diaria.value.replace(/[^\d,]/g, '').replace(',', '.');

    if (!banca || !percentual) {
      resultadoCalculo.textContent = '';
      resultadoUnidade.textContent = '';
      return;
    }

    const bancaFloat = parseFloat(banca) / 100;
    const percentFloat = parseFloat(percentual);

    if (isNaN(bancaFloat) || isNaN(percentFloat)) {
      resultadoCalculo.textContent = '';
      resultadoUnidade.textContent = '';
      return;
    }

    const resultado = bancaFloat * (percentFloat / 100);
    resultadoCalculo.textContent = `= ${resultado.toLocaleString("pt-BR", {
      style: "currency",
      currency: "BRL"
    })}`;

    calcularUnidade(resultado);
  }

  // 🟢 Novo: Cálculo da meta total multiplicada pela unidade
  function calcularUnidade(valorMeta) {
    const unidadeRaw = unidade.value.replace(',', '.');
    const unidadeFloat = parseFloat(unidadeRaw);

    if (!isNaN(unidadeFloat) && !isNaN(valorMeta)) {
      const total = unidadeFloat * valorMeta;
      resultadoUnidade.textContent = `= ${total.toLocaleString("pt-BR", {
        style: "currency",
        currency: "BRL"
      })}`;
    } else {
      resultadoUnidade.textContent = '';
    }
  }

  // 💰 Formatação de campo da banca
  deposito.addEventListener("input", () => {
    let valor = deposito.value.replace(/[^\d]/g, '');
    if (!valor) return deposito.value = "";
    deposito.value = (parseFloat(valor) / 100).toLocaleString("pt-BR", {
      style: "currency",
      currency: "BRL"
    });
    calcularMeta();
  });

  // 📊 Porcentagem
  diaria.addEventListener("input", () => {
    let valor = diaria.value.replace(/[^0-9.,]/g, '');
    const partes = valor.split(/[.,]/);
    if (partes.length > 2) valor = partes[0] + ',' + partes[1];
    diaria.value = valor;
    calcularMeta();
  });

  diaria.addEventListener("blur", () => {
    let valor = diaria.value.replace(/%/g, '').replace(",", ".");
    const numero = parseFloat(valor);
    if (!isNaN(numero)) {
      diaria.value = Number.isInteger(numero)
        ? parseInt(numero) + "%"
        : numero.toString().replace(".", ",") + "%";
    } else {
      diaria.value = "";
    }
    calcularMeta();
  });

  // ✏️ Input de valor com formatações
  valorInput.addEventListener("focus", () => valorInput.value = "");

  valorInput.addEventListener("blur", () => {
    const acaoSelecionada = acaoInput.value;
    const valorAtual = valorInput.value;
    if (!valorAtual) return;

    let numero = parseFloat(valorAtual.replace(/[^\d,.-]/g, "").replace(",", "."));
    if (isNaN(numero)) return;

    valorInput.value = acaoSelecionada === "diaria"
      ? numero.toString().replace(".", ",") + "%"
      : numero.toLocaleString("pt-BR", { style: "currency", currency: "BRL" });
  });

  valorInput.addEventListener("keypress", (e) => {
    const char = e.key;
    const value = valorInput.value;
    if (!/[0-9.,]/.test(char) || ((char === '.' || char === ',') && (value.includes('.') || value.includes(',')))) {
      e.preventDefault();
    }
  });

  valorInput.addEventListener("input", () => {
    const value = valorInput.value;
    const numero = parseFloat(value.replace(',', '.'));
    if (value === '.' || value === ',' || isNaN(numero)) valorInput.value = '';
  });

  // 🎯 Unidade meta com cálculo
  unidade.addEventListener("input", () => {
    unidade.value = unidade.value.replace(/[^0-9,]/g, '');
    const resultadoRaw = resultadoCalculo.textContent.replace(/[^\d,]/g, '').replace(',', '.');
    const metaValue = parseFloat(resultadoRaw);
    if (!isNaN(metaValue)) calcularUnidade(metaValue);
  });

  unidade.addEventListener("blur", () => {
    let valor = unidade.value.replace(",", ".");
    const numero = parseFloat(valor);
    unidade.value = !isNaN(numero) ? (Number.isInteger(numero) ? parseInt(numero) : numero) : "";
    const resultadoRaw = resultadoCalculo.textContent.replace(/[^\d,]/g, '').replace(',', '.');
    const metaValue = parseFloat(resultadoRaw);
    if (!isNaN(metaValue)) calcularUnidade(metaValue);
  });

  // 🚀 Inicialização
  function formatarValoresIniciais() {
    [deposito, diaria, unidade].forEach(el => el.dispatchEvent(new Event("blur")));
    atualizarCampoValor();
    calcularMeta();
  }

  formatarValoresIniciais();
});
</script>
