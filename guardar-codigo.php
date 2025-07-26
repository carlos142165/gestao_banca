
<?php  // 🔒 pagina painel-controle
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
} // 🔒 pagina painel-controle
?>






<?php
session_start();
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


