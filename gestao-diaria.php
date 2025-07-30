
<?php
session_start();
date_default_timezone_set('America/Bahia');

require_once 'config.php';

// Verifica se o usuário está logado
$idUsuario = $_SESSION['usuario_id'] ?? null;
if (!$idUsuario) {
  header("Location: login.php");
  exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Verifica se há registros com depósito e diária
$sql = "SELECT deposito, diaria FROM controle WHERE id_usuario = ?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$result = $stmt->get_result();

$temDeposito = false;
$temDiaria = false;

while ($row = $result->fetch_assoc()) {
  if (!empty($row['deposito']) && floatval($row['deposito']) > 0) {
    $temDeposito = true;
  }
  if (!empty($row['diaria']) && floatval($row['diaria']) > 0) {
    $temDiaria = true;
  }
}
$stmt->close();

// Mensagem personalizada
$mensagem = "";
if (!$temDeposito && !$temDiaria) {
  $mensagem = "💰 Você está sem saldo na banca! Deposite para continuar.";
} elseif (!$temDeposito) {
  $mensagem = "💼 Você está sem saldo na banca! Deposite para continuar.";
} elseif (!$temDiaria && $temDeposito) {
  $mensagem = "🎉 Parabéns! Você definiu sua banca.<br>Agora só falta definir sua porcentagem.";
}

// Exibe o toast se necessário
if ($mensagem !== "") {
  echo "
  <!DOCTYPE html>
  <html lang='pt-br'>
  <head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Aviso</title>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
    <style>
      body {
        margin: 0;
        padding: 0;
        font-family: 'Segoe UI', sans-serif;
        background-color: #f4f4f4;
      }
      .toast-container {
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100vh;
        background: rgba(0, 0, 0, 0.6);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
      }
      .toast {
        background: white;
        padding: 32px;
        border-radius: 14px;
        box-shadow: 0 0 25px rgba(0, 0, 0, 0.25);
        width: 92%;
        max-width: 460px;
        text-align: center;
        animation: fadeIn 0.6s ease-out;
      }
      .toast-header {
        font-size: 22px;
        font-weight: 600;
        color: #e67e22;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
      }
      .toast-header i {
        font-size: 26px;
      }
      .toast-body {
        font-size: 18px;
        color: #333;
        line-height: 1.5;
      }
      .toast-body button {
        margin-top: 20px;
        padding: 12px 26px;
        background: #3498db;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 17px;
        cursor: pointer;
        transition: background 0.3s ease;
      }
      .toast-body button:hover {
        background: #2980b9;
      }
      @keyframes fadeIn {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
      }
      @media (max-width: 390px) {
        .toast {
          padding: 28px 18px;
        }
        .toast-header {
          font-size: 20px;
        }
        .toast-header i {
          font-size: 28px;
        }
        .toast-body {
          font-size: 19px;
        }
        .toast-body button {
          font-size: 18px;
          padding: 14px 28px;
        }
      }
    </style>
    <script>
      setTimeout(() => {
        location.href = 'painel-controle.php';
      }, 6000);
    </script>
  </head>
  <body>
    <div class='toast-container'>
      <div class='toast'>
        <div class='toast-header'>
          <i class='fa fa-exclamation-circle'></i>
          <strong>Aviso</strong>
        </div>
        <div class='toast-body'>
          $mensagem
          <br><br>
          <button onclick=\"location.href='painel-controle.php'\">Ir para o Painel</button>
        </div>
      </div>
    </div>
  </body>
  </html>
  ";
  exit;
}
?>




<?php

require_once 'config.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ✅ Função de notificação
function setToast($mensagem, $tipo = 'info') {
  $cores = [
    'sucesso' => '#4CAF50',
    'erro' => '#F44336',
    'aviso' => '#FFC107',
    'info' => '#2196F3'
  ];
  $cor = $cores[$tipo] ?? '#333';

  echo "<script>
    document.addEventListener('DOMContentLoaded', function() {
      const toast = document.createElement('div');
      toast.textContent = '".addslashes($mensagem)."';
      toast.style.position = 'fixed';
      toast.style.bottom = '20px';
      toast.style.right = '20px';
      toast.style.padding = '12px 20px';
      toast.style.backgroundColor = '$cor';
      toast.style.color = '#fff';
      toast.style.borderRadius = '5px';
      toast.style.boxShadow = '0 2px 6px rgba(0,0,0,0.2)';
      toast.style.zIndex = '1000';
      document.body.appendChild(toast);
      setTimeout(() => toast.remove(), 3000);
    });
  </script>";
}

// 🔐 Verificação de sessão
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
  setToast('Área de membros — faça seu login!', 'aviso');
  header('Location: home.php');
  exit();
}

$id_usuario_logado = $_SESSION['usuario_id'];

// 🔎 Última diária
$stmt = mysqli_prepare($conexao, "
  SELECT diaria FROM controle
  WHERE id_usuario = ? AND diaria IS NOT NULL AND diaria != 0
  ORDER BY id DESC LIMIT 1
");
mysqli_stmt_bind_param($stmt, "i", $id_usuario_logado);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $ultima_diaria);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);
$ultima_diaria = $ultima_diaria ?? 0;

// 🔢 Depósitos
$stmt = mysqli_prepare($conexao, "SELECT SUM(deposito) FROM controle WHERE id_usuario = ?");
mysqli_stmt_bind_param($stmt, "i", $id_usuario_logado);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $soma_depositos);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);
$soma_depositos = $soma_depositos ?? 0;

// 🔢 Saques
$stmt = mysqli_prepare($conexao, "SELECT SUM(saque) FROM controle WHERE id_usuario = ?");
mysqli_stmt_bind_param($stmt, "i", $id_usuario_logado);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $soma_saque);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);
$soma_saque = $soma_saque ?? 0;

// 🔢 Green/Red
$stmt = mysqli_prepare($conexao, "
  SELECT SUM(valor_green), SUM(valor_red)
  FROM valor_mentores WHERE id_usuario = ?
");
mysqli_stmt_bind_param($stmt, "i", $id_usuario_logado);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $valor_green, $valor_red);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);
$valor_green = $valor_green ?? 0;
$valor_red = $valor_red ?? 0;

// 💰 Cálculos
$saldo_mentores = $valor_green - $valor_red;
$saldo_inicial = $soma_depositos - $soma_saque + $saldo_mentores;

if ($saldo_inicial <= 0 && $saldo_mentores < 0) {
  $_SESSION['banca_zerada'] = true;
} elseif ($saldo_inicial > 0) {
  unset($_SESSION['banca_zerada']);
}

if (isset($_SESSION['banca_zerada'])) {
  $saldo_banca = $soma_depositos - $soma_saque;
} else {
  $saldo_banca = $soma_depositos - $soma_saque + $saldo_mentores;
}

// ✅ Correção aplicada aqui: salva na sessão!
$_SESSION['saldo_banca_total'] = $saldo_banca;

$valor_entrada_calculado = $saldo_banca * ($ultima_diaria / 100);
$valor_entrada_formatado = number_format($valor_entrada_calculado, 2, ',', '.');

// 🗑️ Exclusão de mentor
if (isset($_GET['excluir_mentor'])) {
  $id = intval($_GET['excluir_mentor']);
  $stmt = $conexao->prepare("DELETE FROM mentores WHERE id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  setToast('Mentor excluído com sucesso!', 'sucesso');
  header('Location: gestao-diaria.php');
  exit();
}

// 📝 Cadastro/Edição de mentor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {

  $valor_digitado = trim($_POST['valor'] ?? '0');
  $valor_float = is_numeric($valor_digitado) ? floatval($valor_digitado) : null;

  if ($valor_float === null) {
    setToast('Valor inválido!', 'erro');
    header('Location: gestao-diaria.php');
    exit();
  }

  $valor_sanitizado = filter_var($valor_float, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
  $valor_numerico = floatval($valor_sanitizado);

  $tipo_operacao = $_POST['opcao'] ?? '';
  $saldo_banca_verificado = $_SESSION['saldo_banca_total'] ?? 0;

  if ($tipo_operacao === 'red' && $valor_numerico > $saldo_banca_verificado) {
    setToast('⚠️ Saldo da Banca Insuficiente, Faça um Depósito!', 'erro');
    header('Location: gestao-diaria.php');
    exit();
  }

  $usuario_id = $_SESSION['usuario_id'];
  $nome = $_POST['nome'];
  $mentor_id = $_POST['mentor_id'] ?? null;

  if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $extensao = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
    $foto_nome = uniqid() . '.' . $extensao;
    move_uploaded_file($_FILES['foto']['tmp_name'], "uploads/$foto_nome");
  } else {
    $foto_nome = $_POST['foto_atual'] ?? 'avatar-padrao.png';
  }

  if ($_POST['acao'] === 'cadastrar_mentor') {
    $stmt = $conexao->prepare("INSERT INTO mentores (id_usuario, foto, nome, data_criacao) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iss", $usuario_id, $foto_nome, $nome);
  }

  if ($_POST['acao'] === 'editar_mentor' && $mentor_id) {
    $stmt = $conexao->prepare("UPDATE mentores SET nome = ?, foto = ? WHERE id = ?");
    $stmt->bind_param("ssi", $nome, $foto_nome, $mentor_id);
  }

  if ($stmt->execute()) {
    setToast('Mentor salvo com sucesso!', 'sucesso');
  } else {
    setToast('Erro ao salvar mentor!', 'erro');
  }

  header('Location: gestao-diaria.php');
  exit();
}

// 🔎 Meta formatada
$meta_diaria = $_SESSION['meta_meia_unidade'] ?? 0;
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
  
}

.container {
  display: flex;
  flex-direction: row; /* horizontal */
  gap: 20px;            /* espaço horizontal entre os elementos */
}

.grupo-porcentagem {
  display: flex;
  gap: 6px;
  margin-bottom: 10px;
  font-family: sans-serif;
}

.rotulo-porcentagem {
  font-weight: bold;
  color: #444;
}

.valor-porcentagem {
  color: #007bff;
  font-size: 1.1em;
}


</style>
     
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
     <link rel="stylesheet" href="style.css?v=2">


</head>
<body>

<!-- CODIGO RESPONSAVEL PELA MENSAGEM TOAST -->  
<?php
if (isset($_SESSION['toast'])) {
    $mensagem = $_SESSION['toast']['mensagem'];
    $tipo = $_SESSION['toast']['tipo'];
    echo "<div id='toast' class='toast $tipo ativo'>$mensagem</div>";
    unset($_SESSION['toast']);
}
?>
<!-- FIM CODIGO RESPONSAVEL PELA MENSAGEM TOAST -->





    <!-- CODIGO RESPONSAVEL PELO  TOPO PUXADO DA PAGINA MENU.PHP -->

    <div id="data-container"></div>
    <!-- A data será carregada aqui -->

    <div id="menu-placeholder"></div>
    <!-- Aqui o menu será carregado dinamicamente -->

    <script>
      // 📌 Carrega o menu externo (menu.php) dentro do menu-placeholder
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

<!-- FIM CODIGO RESPONSAVEL PELO  TOPO PUXADO DA PAGINA MENU.PHP -->

<div class="container-resumos">

 <div class="resumo-dia">

   <!-- CODIGO RESPONSAVEL PELO VALOR  PLACAR E META DIARIA E SALDO -->
   <div class="container-valores">
  <div class="pontuacao">
    <span class="placar-green">0</span>
    <span class="separador">x</span>
    <span class="placar-red">0</span>
   </div>
  </div>



  <div class="informacoes-row">

  <div class="info-item">
  <div class="grupo-valor">
    <span class="valor-meta" id="meta-dia">
       <?= number_format($meta_diaria, 2, ',', '.') ?>
    </span>
    <span class="rotulo-meta">Meta do Dia</span>
  </div>
 </div>

 <div class="info-item">
  <div class="grupo-valor">
    <span class="valor-saldo">R$0,00</span>
    <span class="rotulo-saldo">Saldo do Dia</span>
  </div>
 </div>



 </div>

 <!-- FIM DO CODIGO RESPONSAVEL PELO VALOR  PLACAR E META DIARIA E SALDO -->






 <!-- CODIGO FORMULARIO QUE ADICIONA E EDITA MENTOR -->
 <div id="modal-form" class="modal">
  <div class="modal-conteudo">
    <span class="fechar" onclick="fecharModal()">&times;</span>

    <form method="POST" enctype="multipart/form-data" action="gestao-diaria.php" class="formulario-mentor-completo">
      <input type="hidden" name="acao" id="acao-form" value="cadastrar_mentor">
      <input type="hidden" name="mentor_id" id="mentor-id" value="">
      <input type="hidden" name="foto_atual" id="foto-atual" value="avatar-padrao.png">

      <!-- Botão para selecionar a foto -->
      <div class="input-group">
        <label for="foto" class="label-form"></label>
        <label for="foto" class="label-arquivo">
          <i class="fas fa-image"></i> Selecionar Foto
        </label>
        <input type="file" name="foto" id="foto" class="input-file" onchange="mostrarNomeArquivo(this)" hidden>
        <span id="nome-arquivo" class="nome-arquivo"></span>
      </div>

      <!-- Pré-visualização da imagem -->
      <div class="preview-foto-wrapper">
        <img id="preview-img" src="https://cdn-icons-png.flaticon.com/512/847/847969.png" class="preview-img" alt="Pré-visualização">
        <button type="button" id="remover-foto" class="btn-remover-foto" onclick="removerImagem()" style="display:none;">Remover Foto</button>
      </div>

      <!-- Nome abaixo da foto -->
      <h3 class="mentor-nome-preview" style="text-align: center; margin-top: 14px;"></h3>

      <!-- Campo para digitar o nome -->
      <div class="input-group">
        <label for="nome" class="label-form"></label>
        <input type="text" name="nome" id="nome" class="input-text" placeholder="Nome do Mentor" required>
      </div>

      <!-- Botões -->
      <div class="botoes-formulario">
        <button type="submit" class="btn-enviar">
          <i class="fas fa-user-plus"></i> Cadastrar Mentor
        </button>

        <button type="button" class="btn-excluir" id="btn-excluir" onclick="excluirMentorDireto()" style="display: none;">
          <i class="fas fa-user-times"></i> Excluir Mentor
        </button>
      </div>
    </form>
  </div>
 </div>

 <!-- FIM DO CODIGO FORMULARIO QUE ADICIONA E EDITA MENTOR -->







  <!-- BOTÃO ADICIONAR USUARIO -->
 
 <div class="area-central-botao">
  <span class="valor-porcentagem" id="valor-porcentagem">
    R$ <?php echo $meta_formatado ?? '0,00'; ?>
  </span>
  <span class="rotulo-porcentagem">da Banca Fazer</span>

  <span class="rotulo-entrada">Entrada de:</span>
  <span class="valor-entrada" id="valor-entrada">
    R$ <?php echo $resultado_formatado ?? '0,00'; ?>
  </span>

  <button class="btn-add-usuario" onclick="prepararFormularioNovoMentor()">
    <i class="fas fa-user-plus"></i>
  </button>
 </div>

 <!-- FIM CODIGO BOTÃO ADICIONAR USUARIO -->





 <!-- AQUI FILTRA OS DADOS DOS MENTORES NO BANCO DE DADOS PRA MOSTRAR NA TELA  -->
 <div class="campo_mentores">

   <div id="listaMentores" class="mentor-wrapper">
    <?php
    $id_usuario_logado = $_SESSION['usuario_id'];
    $sql_mentores = "SELECT id, nome, foto FROM mentores WHERE id_usuario = ?";
    $stmt_mentores = $conexao->prepare($sql_mentores);
    $stmt_mentores->bind_param("i", $id_usuario_logado);
    $stmt_mentores->execute();
    $result_mentores = $stmt_mentores->get_result();

    $lista_mentores = [];
    $total_geral_saldo = 0;

    while ($mentor = $result_mentores->fetch_assoc()) {
      $id_mentor = $mentor['id'];

      $sql_valores = "SELECT 
        COALESCE(SUM(green), 0) AS total_green,
        COALESCE(SUM(red), 0) AS total_red,
        COALESCE(SUM(valor_green), 0) AS total_valor_green,
        COALESCE(SUM(valor_red), 0) AS total_valor_red
      FROM valor_mentores WHERE id_mentores = ?";
      $stmt_valores = $conexao->prepare($sql_valores);
      $stmt_valores->bind_param("i", $id_mentor);
      $stmt_valores->execute();
      $valores = $stmt_valores->get_result()->fetch_assoc();

      $total_subtraido = $valores['total_valor_green'] - $valores['total_valor_red'];

      $mentor['valores'] = $valores;
      $mentor['saldo'] = $total_subtraido;
      $lista_mentores[] = $mentor;

      $total_geral_saldo += $total_subtraido;
    }

    usort($lista_mentores, function($a, $b) {
      return $b['saldo'] <=> $a['saldo'];
    });

    foreach ($lista_mentores as $posicao => $mentor) {
      $rank = $posicao + 1;
      $valores = $mentor['valores'];
      $saldo_formatado = number_format($mentor['saldo'], 2, ',', '.');

      if ($mentor['saldo'] == 0) {
        $classe_borda = 'card-neutro';
      } elseif ($mentor['saldo'] > 0) {
        $classe_borda = 'card-positivo';
      } else {
        $classe_borda = 'card-negativo';
      }

    

      echo "
    <div class='mentor-item'>
      <div class='mentor-rank-externo'>{$rank}º</div>

      <div class='mentor-card {$classe_borda}' 
           data-nome='{$mentor['nome']}'
           data-foto='uploads/{$mentor['foto']}'
           data-id='{$mentor['id']}'>
        <div class='mentor-header'>
          <img src='uploads/{$mentor['foto']}' alt='Foto de {$mentor['nome']}' class='mentor-img' />
          <h3 class='mentor-nome'>{$mentor['nome']}</h3>
        </div>
        <div class='mentor-right'>
          <div class='mentor-values-inline'>
            <div class='value-box-green green'><p>Green</p><p>{$valores['total_green']}</p></div>
            <div class='value-box-red red'><p>Red</p><p>{$valores['total_red']}</p></div>
            <div class='value-box-saldo saldo'><p>Saldo</p><p>R$ {$saldo_formatado}</p></div>
          </div>
        </div>
      </div>

      <div class='mentor-menu-externo'>
        <span class='menu-toggle' title='Mais opções'>⋮</span>
        <div class='menu-opcoes'>
          <button onclick='editarAposta({$mentor["id"]})'>
            <i class='fas fa-trash'></i> Excluir Entrada
          </button>
          <button onclick='editarMentor({$mentor["id"]})'>
            <i class='fas fa-user-edit'></i> Editar Mentor
          </button>
        </div>
      </div>
    </div>
  ";
    }
    
    ?>
    
  </div>
 </div>

 <!-- FIM DO CODIGO QUE FILTRA OS DADOS DOS MENTORES NO BANCO DE DADOS PRA MOSTRAR NA TELA  -->







 <!-- FORMULARIO PARA ADICIONAR O VALOR DA ENTRADA DO MENTOR-->
 <div class="formulario-mentor">
  <button type="button" class="btn-fechar" onclick="fecharFormulario()">
  <i class="fas fa-times"></i>
 </button>

  <img src="" class="mentor-foto-preview" width="100" />
  <h3 class="mentor-nome-preview">Nome do Mentor</h3>
  <form id="form-mentor" method="POST">
    <input type="hidden" name="id_mentor" class="mentor-id-hidden">

    <div class="checkbox-container">
      <div class="checkbox-wrapper">
        <input type="radio" id="green" name="opcao" value="green">
        <label for="green" class="checkbox-label green-label">Green</label>
      </div>
      <div class="checkbox-wrapper">
        <input type="radio" id="red" name="opcao" value="red">
        <label for="red" class="checkbox-label red-label">Red</label>
      </div>
    </div>

    <input type="text" name="valor" id="valor" class="input-valor" placeholder="R$ 0,00" required>


    <button type="submit" class="botao-enviar">Enviar</button>
  </form>
 </div>
 <!-- FIM FORMULARIO PARA ADICIONAR O VALOR DA ENTRADA DO MENTOR-->






 <!-- FORMULARIO PARA EXCLUIR O MENTOR  -->
  <div id="tela-edicao" class="tela-edicao" style="display:none;">
  <button type="button" class="btn-fechar" onclick="fecharTelaEdicao()">
    <i class="fas fa-times"></i>
  </button>

  <img id="fotoMentorEdicao" class="mentor-img-edicao" />
  <h3>Histórico do Mentor - <span id="nomeMentorEdicao"></span></h3>

  <p class="mentor-data-horario">
    <strong>Horário:</strong> <span id="horarioMentorEdicao">Carregando...</span>
  </p>

  <div id="resultado-filtro"></div>
</div>





 <!-- MODAL PARA EXCLUIR A ENTRADA  -->
 <div id="modal-confirmacao" class="modal-confirmacao" style="display:none;">
  <div class="modal-content">
    <p class="modal-texto">Tem certeza que deseja excluir esta entrada?</p>
    <div class="botoes-modal">
      <button id="btnConfirmar" class="botao-confirmar">Sim, excluir</button>
      <button id="btnCancelar" class="botao-cancelar">Cancelar</button>
    </div>
  </div>
 </div>



 <!-- MODAL PARA EXCLUIR O MENTOR  -->
 <div id="modal-confirmacao-exclusao" style="display:none;">
  <div class="modal-content">
    <p class="modal-texto">Tem certeza que deseja excluir este mentor?</p>
    <div class="botoes-modal">
      <button class="botao-confirmar" onclick="confirmarExclusaoMentor()">Sim, excluir</button>
      <button class="botao-cancelar" onclick="fecharModalExclusao()">Cancelar</button>
    </div>
  </div>
 </div>


 </div>






<?php
$timezone_recebido = $_POST['timezone'] ?? 'America/Bahia';
date_default_timezone_set($timezone_recebido);

$meses_portugues = array(
  "01" => "JANEIRO", "02" => "FEVEREIRO", "03" => "MARÇO",
  "04" => "ABRIL", "05" => "MAIO", "06" => "JUNHO",
  "07" => "JULHO", "08" => "AGOSTO", "09" => "SETEMBRO",
  "10" => "OUTUBRO", "11" => "NOVEMBRO", "12" => "DEZEMBRO"
);

$ano = date('Y');
$mes = date('m');
$hoje = date('Y-m-d');
$diasNoMes = cal_days_in_month(CAL_GREGORIAN, $mes, $ano);
$nomeMes = $meses_portugues[$mes];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id_mentores = $_POST['id_mentores'];
  $green = $_POST['green'];
  $red = $_POST['red'];
  $valor_green = $_POST['valor_green'];
  $valor_red = $_POST['valor_red'];
  $data_criacao = date('Y-m-d H:i:s');

  $stmt = $conexao->prepare("
    INSERT INTO valor_mentores (
      id_usuario, id_mentores, green, red, valor_green, valor_red, data_criacao
    ) VALUES (?, ?, ?, ?, ?, ?, ?)
  ");
  $stmt->bind_param("iiiddss", 
    $id_usuario_logado, $id_mentores, $green, $red, $valor_green, $valor_red, $data_criacao
  );
  $stmt->execute();
  $stmt->close();
}
?>





 

 <style>

/* TESTE   */
.container-resumos {
  display: flex;
  justify-content: center;
  flex-wrap: wrap;
  gap: 50px;
  margin-top: 30px;
}

.resumo-mes::-webkit-scrollbar {
  display: none;
}

.resumo-mes {
  position: relative;
  width: 385px;
  height: 780px;
  overflow-y: auto;
  background-color: #f7f6f6;
  border-radius: 12px;
  padding-top: 0px; /* espaço suficiente para o bloco fixo */
  box-shadow: 0 0 12px rgba(0,0,0,0.08);
  font-family: 'Poppins', sans-serif;
}

.fixo-topo {
  position: sticky;  /* Mantém o bloco fixo dentro do scroll */
  top: 0px;
  z-index: 10;
  background-color: #fdfdfd;
  padding: 14px 18px;
  border-radius: 12px 12px 0 0;
  box-shadow: 0 2px 6px rgba(0,0,0,0.04);
 
}

.resumo-mes h2 {
  font-size: 20px;
  color: #333;
  text-align: center;
  margin-bottom: 15px;
}

/* RESPONSAVEL PELO CAMPO DAS METAS */
.bloco-meta-simples {
  background-color: #f7f6f6;
  border-radius: 12px;
  box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);  
  display: flex;
  flex-direction: column;
  padding-top: 12px;
  max-width: 360px;
  font-family: 'Poppins', sans-serif;
  transition: all 0.3s ease;
  padding: 16px;
  gap: 6px; /* Reduzido de 10px */
 
 

}

.grupo-barra {
  background: #ffffff;
  padding: 10px 14px;
  border-radius: 10px;
  box-shadow: 0 1px 6px rgba(0, 0, 0, 0.05);
  display: flex;
  flex-direction: column;
  position: relative;
  transition: transform 0.2s ease-in-out;
  margin-top: 4px;
}

.titulo-bloco {
  font-size: 18px;
  font-weight: bold;
  color: #2e7d32;
  margin: 12px 0;
  padding: 8px 0;
  display: flex;
  align-items: center;
  gap: 10px;
  border-bottom: 2px solid #c8e6c9;
  background-color: #f1f8e9;
  border-radius: 5px;
  margin: 0 0 15px 0;
}

.titulo-bloco i {
  font-size: 20px;
  color: #43a047;
}

.grupo-barra:hover {
  transform: translateY(-1.5px);
}

.valor-meta {
  font-size: 14px;
  font-weight: 600;
  color: #333;
  margin-bottom: 2px;
}

.valor-meta i {
  color: #d6a10f;
  margin-right: 6px;
}

.container-barra-horizontal {
  display: flex;
  align-items: center;
  gap: 6px;
}

.porcento-barra {
  font-size: 12px;
  font-weight: 500;
  color: #555;
  min-width: 35px;
  text-align: right;
}

.progresso-verde::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  height: 100%;
  width: var(--largura-barra, 0%);
  border-radius: 4px;
  background: linear-gradient(90deg, #4CAF50, #81C784);
  transition: width 0.4s ease-in-out;
}

.rotulo-meta-mes.sucesso {
  background-color: #dff0d8;
  color: #388e3c;
  border: 1px solid #81c784;
  margin-left: 8px;
  padding: 3px 6px;
  border-radius: 4px;
  font-size: 12px;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}


.rotulo-meta-mes {
  font-size: 12px;
  font-weight: 600;
  color: #28a745;
  background-color: rgba(40, 167, 69, 0.08);
  padding: 3px 6px;
  border-radius: 5px;
  width: fit-content;
}

.rotulo-meta-mes i {
  margin-right: 6px;
  color: #28a745;
}

.progresso-dourado,
.progresso-verde {
  height: 6px;
  flex: 1;
  border-radius: 4px;
  background-color: #eee;
  position: relative;
  overflow: hidden;
}

.progresso-dourado::before,
.progresso-verde::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  height: 100%;
  border-radius: 4px;
}

.progresso-dourado::before {
  width: 100%;
  background: linear-gradient(90deg, #d6a10f, #d6a10f);
}

.progresso-verde::before {
  width: 20%;
  background: linear-gradient(90deg, #4CAF50, #81C784);
}



/* FIM RESPONSAVEL PELO CAMPO DAS METAS */



/* Tabela de dias */
.linha-dia {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background-color: #fff;
  padding: 6px 10px;
  border-radius: 8px;
  box-shadow: 0 0 2px rgba(0,0,0,0.05);
  width: 90%;
  margin: 8px auto;
  
}

.data {
  font-size: 12px;
  color: #333;
  display: flex;
  align-items: center;
  gap: 5px;
}

.placar-dia {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
}

.placar {
  font-size: 13px;
}

.valor {
  font-size: 12px;
  color: #444;
  display: flex;
  align-items: center;
  gap: 5px;
}

.icone {
  font-size: 14px;
  color: #4caf50;
}

.verde-bold {
  color: #28a745;
  font-weight: bold;
}

.vermelho-bold {
  color: #dc3545;
  font-weight: bold;
}

.texto-cinza {
  color: #777;
}

.dia-hoje {
  border-left: 4px solid green;
  opacity: 1;
}

.dia-normal {
  opacity: 0.5;
}

.borda-verde {
  border-left: 4px solid green;
}

.borda-vermelha {
  border-left: 4px solid red;
}

.dia-destaque {
  background-color: #f0fff3;
  box-shadow: 0 0 8px rgba(40,167,69,0.25);
}

.lista-dias{
  margin-top: 18px;
}



/* TESTE   */
</style>





<div class="container-resumos">
  <div class="resumo-mes">
    
    <!-- PEGAR O MES ATUAL E COLOCA NO TITULO -->
   <div class="bloco-meta-simples fixo-topo">

    

<?php
  // 🔹 Cálculo da meta mensal
  $hoje = new DateTime();
  $dias_mes = cal_days_in_month(CAL_GREGORIAN, (int)$hoje->format('m'), (int)$hoje->format('Y'));
  $meta_mensal = ($soma_depositos * ($ultima_diaria / 100)) * ($dias_mes / 2);

  // 🔹 Saldo dos mentores
  $saldo_mentores = $valor_green - $valor_red;

  // 🔹 Cálculo da porcentagem de progresso
  $porcentagem_meta = $meta_mensal > 0 ? ($saldo_mentores / $meta_mensal) * 100 : 0;
  $porcentagem_meta_arredondada = round($porcentagem_meta, 1);

  // 🔹 Verificação de meta batida
  $meta_batida = $saldo_mentores >= $meta_mensal;

  // 🔹 Formatação dos valores
  $meta_mensal_formatada = 'R$ ' . number_format($meta_mensal, 2, ',', '.');
  $saldo_mes_formatado = 'R$ ' . number_format($saldo_mentores, 2, ',', '.');
?>




   <!-- TÍTULO DO MÊS -->
<h2 class="titulo-bloco">
  <i class="fas fa-calendar-alt"></i> <span id="tituloMes"></span>
</h2>

<script>
  const meses = [
    "JANEIRO", "FEVEREIRO", "MARÇO", "ABRIL", "MAIO", "JUNHO",
    "JULHO", "AGOSTO", "SETEMBRO", "OUTUBRO", "NOVEMBRO", "DEZEMBRO"
  ];
  const hoje = new Date();
  const mesAtual = meses[hoje.getMonth()];
  const anoAtual = hoje.getFullYear();
  document.getElementById("tituloMes").textContent = `${mesAtual} ${anoAtual}`;
</script>

<!-- BLOCO FIXO DE METAS -->
<div class="grupo-barra">
  <span class="valor-meta"><i class="fas fa-bullseye"></i> <?php echo $meta_mensal_formatada; ?></span>
  <div class="container-barra-horizontal">
    <div class="progresso-dourado"></div>
    <span class="porcento-barra">100%</span>
  </div>
  <span class="rotulo-meta-mes"><i class="fas fa-calendar-day"></i> Meta do Mês</span>
</div>

<div class="grupo-barra">
  <span class="valor-meta">
    <i class="fas fa-wallet"></i> <?php echo $saldo_mes_formatado; ?>
    <?php if ($meta_batida): ?>
      <span class="rotulo-meta-mes sucesso"><i class="fas fa-trophy"></i> Meta Batida</span>
    <?php endif; ?>
  </span>
  <div class="container-barra-horizontal">
    <div class="progresso-verde" style="--largura-barra: <?php echo min($porcentagem_meta_arredondada, 100); ?>%;"></div>
    <span class="porcento-barra"><?php echo $porcentagem_meta_arredondada; ?>%</span>
  </div>
  <span class="rotulo-meta-mes"><i class="fas fa-coins"></i> Saldo do Mês</span>
</div>



    
    <!-- CONTEÚDO DINÂMICO DAS LINHAS DIÁRIAS -->
    <div class="lista-dias">
      <?php
      for ($dia = 1; $dia <= $diasNoMes; $dia++) {
        $data_mysql = $ano . '-' . $mes . '-' . str_pad($dia, 2, "0", STR_PAD_LEFT);
        $data_exibicao = str_pad($dia, 2, "0", STR_PAD_LEFT) . "/" . $mes . "/" . $ano;

        $stmt = $conexao->prepare("
          SELECT 
            SUM(CASE WHEN green = 1 THEN valor_green ELSE 0 END) AS total_valor_green,
            SUM(CASE WHEN red = 1 THEN valor_red ELSE 0 END) AS total_valor_red,
            COUNT(CASE WHEN green = 1 THEN 1 END) AS total_green,
            COUNT(CASE WHEN red = 1 THEN 1 END) AS total_red
          FROM valor_mentores
          WHERE id_usuario = ? AND DATE(data_criacao) = ?
        ");
        $stmt->bind_param("is", $id_usuario_logado, $data_mysql);
        $stmt->execute();
        $stmt->bind_result($total_valor_green, $total_valor_red, $total_green, $total_red);
        $stmt->fetch();
        $stmt->close();

        $saldo_dia = ($total_valor_green ?? 0) - ($total_valor_red ?? 0);
        $saldo_formatado = number_format($saldo_dia, 2, ',', '.');

        $cor_valor = ($saldo_dia == 0) ? 'texto-cinza' : ($saldo_dia > 0 ? 'verde-bold' : 'vermelho-bold');
        $classe_texto = ($saldo_dia == 0) ? 'texto-cinza' : '';
        $placar_cinza = ((int)$total_green === 0 && (int)$total_red === 0) ? 'texto-cinza' : '';

        $classe_dia = ($data_mysql === $hoje)
          ? 'dia-hoje ' . ($saldo_dia >= 0 ? 'borda-verde' : 'borda-vermelha')
          : 'dia-normal';

        $classe_destaque = ($data_mysql < $hoje && $saldo_dia > 0) ? 'dia-destaque' : '';

        echo '
          <div class="linha-dia '.$classe_dia.' '.$classe_destaque.'">
            <span class="data '.$classe_texto.'"><i class="fas fa-calendar-day"></i> '.$data_exibicao.'</span>
            <div class="placar-dia">
              <span class="placar verde-bold '.$placar_cinza.'">'.(int)($total_green ?? 0).'</span>
              <span class="placar separador '.$placar_cinza.'">x</span>
              <span class="placar vermelho-bold '.$placar_cinza.'">'.(int)($total_red ?? 0).'</span>
            </div>
            <span class="valor '.$cor_valor.'"><i class="fas fa-dollar-sign"></i> R$ '.$saldo_formatado.'</span>
            <span class="icone '.$classe_texto.'"><i class="fas fa-check"></i></span>
          </div>
        ';
      }
      ?>
    </div>
  </div>
</div>














<script>
  document.getElementById('timezone').value =
    Intl.DateTimeFormat().resolvedOptions().timeZone;
</script>



  
 </div>
</div>



<div id="mensagem-status" class="toast"></div>

<!-- DEIXA TOAST OCULTO -->
<div id="toast" class="toast hidden"></div>

<!-- PUXA O SCRIPT -->
<script src="script.js"></script>











<!-- AJUSTA A DATA E O HORARIO -->
<script>
document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("form-mentor");

  if (form) {
    const timeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    const dataLocal = new Date().toISOString();

    const criarInput = (name, value) => {
      let existing = form.querySelector(`[name="${name}"]`);
      if (!existing) {
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = name;
        input.value = value;
        form.appendChild(input);
      }
    };

    criarInput("user_time_zone", timeZone);
    criarInput("data_local", dataLocal);
  } else {
    console.warn("❌ Formulário #form-mentor não encontrado.");
  }
});
</script>
<!-- AJUSTA A DATA E O HORARIO -->




</body>
</html>
