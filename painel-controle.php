<?php
require_once 'config.php';
require_once 'carregar_sessao.php';

// Verifica se o usuário está autenticado
if (!isset($_SESSION['usuario_id'])) {
    echo "<script>alert('ÁREA DE MEMBROS – Faça Já Seu Cadastro Gratuito'); window.location.href = 'home.php';</script>";
    exit();
}

$id_usuario = $_SESSION['usuario_id'];

// ✅ Recupera variáveis da sessão
$saldo_reais = $_SESSION['saldo_geral'] ?? 0;
$saques_reais = $_SESSION['saques_totais'] ?? 0;
$percentual = $_SESSION['porcentagem_entrada'] ?? 0;
$percentualFormatado = (intval($percentual) == $percentual)
    ? intval($percentual) . '%'
    : number_format($percentual, 2, ',', '.') . '%';

// ✅ Processa formulário de edição ou inserção
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submitPersonalizado'])) {
    $deposito = str_replace(',', '.', preg_replace('/[^0-9,]/', '', $_POST['deposito'] ?? ''));
    $diaria = str_replace(',', '.', preg_replace('/[^0-9,]/', '', $_POST['diaria'] ?? ''));
    $unidade = str_replace(',', '.', preg_replace('/[^0-9,]/', '', $_POST['unidade'] ?? ''));
    $controle_id = intval($_POST['controle_id'] ?? 0);

    if ($controle_id > 0) {
        // Atualiza registro existente
        $stmt = $conexao->prepare("UPDATE controle SET deposito = ?, diaria = ?, unidade = ? WHERE id = ? AND id_usuario = ?");
        $stmt->bind_param("dddii", $deposito, $diaria, $unidade, $controle_id, $id_usuario);
        $stmt->execute();
        $stmt->close();

        $_SESSION['mensagem'] = 'Dados atualizados com sucesso!';
    } else {
        // Insere novo registro
        $stmt = $conexao->prepare("INSERT INTO controle (id_usuario, deposito, diaria, unidade) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iddd", $id_usuario, $deposito, $diaria, $unidade);
        $stmt->execute();
        $stmt->close();

        $_SESSION['mensagem'] = 'Dados cadastrados com sucesso!';
    }

    header('Location: painel-controle.php');
    exit();
}

// ✅ Processa ações como saque ou limpar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'], $_POST['valor'])) {
    $acao = $_POST['acao'];
    $valor = str_replace(',', '.', preg_replace('/[^0-9,]/', '', $_POST['valor']));
    $valorFloat = is_numeric($valor) ? (float)$valor : 0;

    if ($acao === 'limpar') {
        $stmt = $conexao->prepare("DELETE FROM controle WHERE id_usuario = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $stmt->close();

        $stmt = $conexao->prepare("DELETE FROM valor_mentores WHERE id_usuario = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $stmt->close();

        $_SESSION['mensagem'] = 'Banca e históricos dos mentores limpos com sucesso!';
        header('Location: painel-controle.php');
        exit();
    }

    if ($acao === 'saque') {
        if ($valorFloat > $saldo_reais || $saldo_reais <= 0) {
            $_SESSION['mensagem'] = 'Saldo Insuficiente!';
            header('Location: painel-controle.php');
            exit();
        }

        $stmt = $conexao->prepare("INSERT INTO valor_mentores (id_usuario, valor_red) VALUES (?, ?)");
        $stmt->bind_param("id", $id_usuario, $valorFloat);
        $stmt->execute();
        $stmt->close();

        $stmt = $conexao->prepare("INSERT INTO controle (id_usuario, saque, origem) VALUES (?, ?, 'mentor')");
        $stmt->bind_param("id", $id_usuario, $valorFloat);
        $stmt->execute();
        $stmt->close();

        $_SESSION['mensagem'] = 'Saque realizado com sucesso!';
        header('Location: painel-controle.php');
        exit();
    }

    if (in_array($acao, ['deposito', 'diaria']) && $valorFloat > 0) {
        $stmt = $conexao->prepare("INSERT INTO controle (id_usuario, $acao) VALUES (?, ?)");
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
max-width: 110px;
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

#historico-saques {
  display: block !important;
}


/* Teste */

.painel-saques .container-principal {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 40px 15px;
}

.painel-saques .box {
  background: #ffffff;
  border-radius: 16px;
  padding: 25px 30px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
  font-family: "Segoe UI", sans-serif;
  width: 320px;
  color: #333;
}

.painel-saques .dropdown {
  position: relative;
}

.painel-saques .dropdown-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #f2f2f2;
  border-radius: 8px;
  padding: 10px 15px;
  cursor: pointer;
  font-weight: 600;
  color: #2c3e50;
}

.painel-saques .dropdown-options {
  display: none;
  list-style: none;
  margin-top: 5px;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
  padding: 8px 0;
  position: absolute;
  width: 100%;
  z-index: 1000;
}

.painel-saques .dropdown-options.show {
  display: block;
}

.painel-saques .dropdown-options li {
  padding: 10px 15px;
  cursor: pointer;
  font-size: 14px;
  transition: background 0.2s ease;
  color: #34495e;
}

.painel-saques .dropdown-options li:hover {
  background-color: #ecf0f1;
}

.painel-saques .inputbox {
  position: relative;
  margin-top: 20px;
}

.painel-saques .inputUser {
  width: 100%;
  padding: 10px;
  border: none;
  border-radius: 8px;
  outline: none;
  background: #f2f2f2;
  font-size: 14px;
  color: #2c3e50;
}

.painel-saques .labelinput {
  position: absolute;
  top: -18px;
  left: 10px;
  font-size: 14px;
  font-weight: 600;
  color: #34495e;
}

.painel-saques #submit {
  margin-top: 20px;
  width: 100%;
  padding: 10px 0;
  background-color: #3498db;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: background 0.3s ease;
}

.painel-saques #submit:hover {
  background-color: #2980b9;
}

.painel-saques .lista-saques {
  margin: 40px auto;
  max-width: 320px;
  font-family: "Segoe UI", sans-serif;
}

.painel-saques .entrada-card {
  background: #f8f8f8;
  border-left: 6px solid #14aa46ff;
  border-radius: 10px;
  padding: 12px 16px;
  margin-bottom: 12px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
  display: flex;
  justify-content: space-between;
}

.painel-saques .entrada-info p {
  margin: 3px 0;
  font-size: 14px;
  color: #2c3e50;
}

.painel-saques .entrada-acoes {
  display: flex;
  align-items: center;
}

.painel-saques .btn-lixeira {
  background: none;
  border: none;
  color: #b4b4b4;
  font-size: 16px;
  cursor: pointer;
  transition: transform 0.2s ease;
}

.painel-saques .btn-lixeira:hover {
  transform: scale(1.2);
}

/* Modal de exclusão escopado */

.oculta {
  display: none;
}

/* Modal full overlay */
/* Container do modal */
.modal-confirmacao {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 320px;
  background-color: #ffffff;
  border-radius: 12px;
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
  padding: 15px 10px;
  z-index: 1000;
  font-family: 'Segoe UI', sans-serif;
  animation: fadeIn 0.3s ease-in-out;
}

/* Texto do modal */
.modal-texto {
  font-size: 16px;
  color: #333;
  margin-bottom: 20px;
  text-align: center;
  line-height: 1.4;
}

/* Botões */
.botoes-modal {
  display: flex;
  justify-content: space-between;
  gap: 10px;
}

.botao-confirmar,
.botao-cancelar {
  flex: 1;
  padding: 10px 0;
  border: none;
  border-radius: 8px;
  font-weight: bold;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.botao-confirmar {
  background-color: #e53935;
  color: #fff;
}

.botao-confirmar:hover {
  background-color: #d32f2f;
}

.botao-cancelar {
  background-color: #eeeeee;
  color: #333;
}

.botao-cancelar:hover {
  background-color: #e0e0e0;
}

/* Ocultar por padrão */
.oculta {
  display: none;
}

/* Animação de entrada */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translate(-50%, -55%);
  }
  to {
    opacity: 1;
    transform: translate(-50%, -50%);
  }
}

.toast {
  position: fixed;
  top: 20px;
  left: 50%;
  transform: translateX(-50%);
  background-color: #319e49ff;
  color: #fff;
  padding: 8px 16px;
  border-radius: 4px;
  font-size: 0.85rem; /* Tamanho menor */
  font-weight: 500;    /* Peso mais leve */
  box-shadow: 0 3px 8px rgba(0, 0, 0, 0.25);
  opacity: 0;
  z-index: 9999;
  transition: opacity 0.4s ease;
}
.toast.show {
  opacity: 1;
}
.toast.hidden {
  display: none;
}

body.ocultar-scroll {
  overflow: hidden;
}


  .input-destaque {
  transform: scaleX(1.3);
  transform-origin: left;
}


  #inputAcao {
  max-width: 500px;
  padding: 10px;
  font-size: 12px;
  box-sizing: border-box;
  border: 2px solid #138d13ff;
  box-shadow: 0 0 8px rgba(52, 152, 219, 0.3);
  transition: all 0.3s ease;
}


  #inputAcao {
    width: 288px;
    height: 35px;
    padding: 8px;
    box-sizing: border-box;
  }

#inputAcaoContainer {
  display: none;
}


/*TESTE*/
#menu-placeholder {
        background-color: #113647;
        color: #eff1f1;
        padding: 15px 20px;
        font-family: Arial, sans-serif;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        font-size: 14px;
        height: 60px;
        
        
        
      }

      .menu-button {
        background: none;
        color: #eff1f1;
        border: none;
        font-size: 25px;
        cursor: pointer;
        padding: 5px;
        line-height: 1;
        
      }

      #menu {
        display: none;
        position: absolute;
        top: 60px;
        left: 20px;
        background-color: #eff1f1;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        z-index: 1000;
        padding-left: -3px;
        width: 180px;
      }



  .menu-content {
    text-align: left;
  }

  .menu-content a {
    display: flex;
    align-items: center;
    padding: 10px 15px;
    text-decoration: none;
    color: #333;
  }

  .menu-icon {
    margin-right: 7px;
    flex-shrink: 0;
    min-width: 20px;
    text-align: center; /* garante que fiquem colados na esquerda */
  }

  




      #menu a {
        color: #004080;
        padding: 12px 0px 12px 10px; /* Reduzi o padding esquerdo */
        display: flex;
        align-items: center;
        text-decoration: none;
      }

      #menu a:hover {
        background-color: #daf3f5;
      }
      /*  AQUI ESTAO OS CODIGOS DOS MENU */

      /*  AQUI ESTAO OS CODIGOS DOS BOTOES REGISTRE-SE E ENTRAR*/
      #top-bar {
        display: flex;
        justify-content: flex-start;
        align-items: center;
        background-color: #113647;
        padding: 15px 5px;
        color: white;
        font-family: Arial, sans-serif;
        height: 60px;
      }

      .app-title {
        font-size: 20px;
        font-weight: bold;
      }

      .top-buttons {
        display: flex;
        gap: 15px;
        margin-right: auto;
        font-size: 18px;
      }

      .top-link {
        color:rgb(233, 198, 45);
        text-decoration: none;
        font-weight: 500;
        border: 1px solid white;
        padding: 6px 12px;
        border-radius: 4px;
        transition: background 0.3s;
      }

      .top-link-entrar {
        color:rgb(235, 235, 235);
        text-decoration: none;
        font-weight: 500;
        border: 1px solid white;
        padding: 6px 12px;
        border-radius: 4px;
        transition: background 0.3s;
      }

      .top-link:hover {
        background-color: white;
        color: #113647;
      }

      .top-link-entrar:hover {
        background-color: white;
        color: #113647;
      }

      @media (max-width: 600px) {
        .top-link {
          font-size: 14px;
          padding: 5px 8px;
        }

        .top-link-entrar {
          font-size: 14px;
          padding: 5px 8px;
        }

        .top-buttons {
          gap: 10px;
        }

        .app-title {
          font-size: 16px;
        }
      }

      .logo-img {
      width: 30vw;
      max-width: 100px;
      min-width: 80px;
      height: auto;
      object-fit: contain;
      transition: width 0.3s ease;
      margin-left: -20px;
      }

      /* Ajuste especial para telas menores que 600px */
      @media (max-width: 600px) {
     .logo-img {
      width: 50vw;
      max-width: 70px;
      min-width: 70px;
      flex-direction: row;
      margin-left: -20px;
      
      
     }

     .top-buttons {
     flex-direction: row;
     align-items: center;
     gap: 8px;
     }
    }

     .usuario-saldo {
      margin-left: 15px;
      font-weight: bold;
      display: flex;
      align-items: center;
      color: white; /* ou a cor que combine com seu topo */
      font-family: Arial, sans-serif;
     }

     .usuario-saldo img {
     width: 20px;
     height: 20px;
     margin-right: 6px;
     }





/* codigo responsavel pelo saldo da banca e icone */
.valor-item-menu {
  display: flex;
  align-items: center; /* Centraliza verticalmente */
  justify-content: flex-start;
  padding: 6px 8px;
  gap: 5px;
  border-radius: 8px;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  
  
}

.valor-item-menu:hover {
  transform: translateY(-2px);
  
}

.valor-icone-menu {
  font-size: 18px;
  color: rgb(184, 153, 238);
  min-width: 22px;
  display: flex;
  align-items: center; /* Garante centralização do ícone dentro do espaço */
  justify-content: center;
  max-width: 100%;
  white-space: nowrap;
  
}

.valor-texto {
  display: flex;
  flex-direction: column;
}

.valor-bold-menu {
  font-weight: bold;
  font-size: 13px; /* Tamanho menor */
  color: rgb(235, 235, 235);
  margin-bottom: 1px;
}

.valor-desc-menu {
  font-size: 11px; /* Tamanho menor */
  color: rgb(219, 218, 218);
  margin: 0;
}


.saldo-topo-ajustado {
  position: absolute;
  z-index: 1001;
}

/* Para telas grandes (computadores) */
@media (min-width: 768px) {
  .saldo-topo-ajustado {
    top: 5px;
    right: 15px;
  }
}

/* Para telas pequenas (celulares) */
@media (max-width: 767px) {
  .saldo-topo-ajustado {
    top: 5px;
    right: 5px;
    left: auto; /* remove o posicionamento fixo que pode causar problemas */
  }
}
/* aqui finaliza o codigo responsavel pelo saldo da banca e icone */

.valor-item-menu {
    background-color: #113647;
    border-radius: 8px;
    padding: 10px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    font-family: 'Segoe UI', 'Roboto', sans-serif;
    margin-top: -8px;
}

.valor-info-wrapper {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.valor-label-linha {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 1px 0;
    border-bottom: 1px solid #1f4d5f;
}

.valor-label {
    font-weight: 500;
    font-size: 11px;
    color: #cfd8dc;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.valor-bold-menu {
    font-size: 13px;
    font-weight: bold;
    color: #a9b5bbff; /* azul petróleo como sugestão */
}


.valor-valor-saque {
    font-size: 13px;
    font-weight: bold;
    color: #90a4ae;
}

.valor-total-mentores {
    font-size: 13px;
    font-weight: bold;
    color: #80b3ff;
}

.valor-icone-tema {
    font-size: 12px;
    color: #90a4ae;
    transition: transform 0.3s ease, color 0.3s ease;
}

.valor-label-linha:hover .valor-icone-tema {
    transform: scale(1.1);
    color: #ffffff;
}

@media (max-width: 480px) {
  .valor-item-menu {
    padding: 8px;
    border-radius: 6px;
    margin-top: 0px;
  }

  .valor-label-linha {
    gap: 4px;
    padding: 0;
  }

  .valor-label {
    font-size: 10px;
    letter-spacing: 0.2px;
  }

  .valor-bold-menu,
  .valor-valor-saque,
  .valor-total-mentores {
    font-size: 12px;
    line-height: 1.1;
  }

  .valor-icone-tema {
    font-size: 11px;
  }
}

.saldo-positivo {
    color: #9fe870; /* verde cana */
}

.saldo-negativo {
    color: #e57373; /* vermelho suave */
}

.saldo-neutro {
    color: #cfd8dc; /* cinza claro */
}
/*TESTE*/



</style>



<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">


</head>





<body>



<?php if (isset($_SESSION['mensagem'])): ?>
  <div class="mensagem-status" id="mensagemStatus">
    <i class="fa-solid fa-triangle-exclamation"></i>
    <span><?= $_SESSION['mensagem'] ?></span>
    <button class="btn-fechar" onclick="document.getElementById('mensagemStatus').style.display='none'">OK</button>
  </div>
  <?php unset($_SESSION['mensagem']); ?>
<?php endif; ?>





<div id="top-bar"> 
  <div class="menu-container">
    <button class="menu-button" onclick="toggleMenu()">☰</button>

    <div id="menu" class="menu-content">
      <a href="home.php">
        <i class="fas fa-home menu-icon"></i><span>Home</span>
      </a>
      <a href="gestao-diaria.php">
        <i class="fas fa-university menu-icon"></i><span>Gestão de Banca</span>
      </a>
      <a href="gestao-mensal.php">
        <i class="fas fa-calendar-alt menu-icon"></i><span>Gestão Mensal</span>
      </a>
      <a href="gestao-anual.php">
        <i class="fas fa-calendar menu-icon"></i><span>Gestão Anual</span>
      </a>
      <a href="estatisticas.php">
        <i class="fas fa-chart-bar menu-icon"></i><span>Estatísticas</span>
      </a>
      <a href="painel-controle.php">
        <i class="fas fa-cogs menu-icon"></i><span>Painel de Controle</span>
      </a>

      <?php if (isset($_SESSION['usuario_id'])): ?>
        <a href="logout.php">
          <i class="fas fa-sign-out-alt menu-icon"></i><span>Sair</span>
        </a>
      <?php endif; ?>
    </div>



    
<div id="lista-mentores">
  <div class="valor-item-menu saldo-topo-ajustado">
    <div class="valor-info-wrapper">
      
      <!-- Banca -->
      <div class="valor-label-linha">
        <i class="fa-solid fa-building-columns valor-icone-tema"></i>
        <span class="valor-label">Banca:</span>
        <span class="valor-bold-menu">R$ <?php echo number_format(calcularSaldoBanca(), 2, ',', '.'); ?></span>

      </div>

      <!-- Saque -->
      <div class="valor-label-linha">
        <i class="fa-solid fa-arrow-up-from-bracket valor-icone-tema"></i>
        <span class="valor-label">Saque:</span>
        <span class="valor-valor-saque">R$ <?php echo number_format($_SESSION['saques_totais'] ?? 0, 2, ',', '.'); ?></span>
      </div>

      <!-- Saldo Mentores -->
      <div class="valor-label-linha">
        <i class="fa-solid fa-chart-line valor-icone-tema"></i>
        <span class="valor-label">Saldo:</span>
        <span class="valor-total-mentores saldo-neutro">R$ <?php echo number_format($_SESSION['saldo_mentores'] ?? 0, 2, ',', '.'); ?></span>
      </div>

    </div>
  </div>
</div>



  </div>
</div>










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
              <li onclick="selectOption('Sacar Valor do Saldo', 'saque')" data-text="Sacar Valor do Saldo" data-value="saque">
                <i class="fa-solid fa-arrow-down"></i> Sacar Saldo 
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

<!-- ENCAPSULOU COM : <div class="painel-saques"> PARA NÃO DA CONFLITO COM O CSS DO FORMULARIO -->
<div class="painel-saques">
  
  <!-- Histórico de saques aparece abaixo -->
  <div id="historico-saques" class="lista-saques"></div>

  <!-- Modal de exclusão -->
  <div id="modal-confirmacao" class="modal-confirmacao oculta">
  <p class="modal-texto">Tem certeza que deseja excluir Este Saque?</p>
  <div class="botoes-modal">
    <button id="btnConfirmarBanca" class="botao-confirmar">Sim, excluir</button>
    <button id="btnCancelarBanca" class="botao-cancelar">Cancelar</button>
  </div>
</div>


</div>


<div id="toast-msg" class="toast hidden">Saque excluído com sucesso!</div>
<div id="toast-msg" class="toast hidden">Mensagem</div>





<br>


<div id="modalDeposito" class="modal-overlay" style="display: none;">
  <div class="modal-content">
    <form method="POST" action="">
      <input type="hidden" name="controle_id" value="<?= isset($controle_id) ? $controle_id : '' ?>">

      <button type="button" class="btn-fechar" onclick="fecharModal()">×</button>

      <div class="custom-inputbox">
        <div style="display: flex; justify-content: space-between; align-items: center;">
          <label for="valorBanca">
            <i class="fa-solid fa-coins"></i> Banca
          </label>

          <div id="radioWrapper" style="display: none; gap: 10px;">
            <label style="display: flex; align-items: center; gap: 5px;">
              <input type="radio" name="acaoBanca" value="add" class="radio-banca"> ADD
            </label>
            <label style="display: flex; align-items: center; gap: 5px;">
              <input type="radio" name="acaoBanca" value="sacar" class="radio-banca"> Sacar Deposito
            </label>
          </div>
        </div>

        <input type="text" name="deposito" id="valorBanca" required
          value="<?= isset($valor_deposito) ? number_format($valor_deposito, 2, ',', '.') : '' ?>">

        <div id="inputAcaoContainer" style="margin-top: 10px;">
          <input type="text" id="inputAcao" name="valorAcao" placeholder="quanto quer adicionar">
        </div>
      </div>

      <div class="custom-inputbox">
        <label for="porcentagem"><i class="fa-solid fa-chart-pie"></i> Porcentagem</label>
        <div style="display: flex; gap: 10px; align-items: center;">
          <input type="text" name="diaria" id="porcentagem" required
            value="<?= isset($valor_diaria) ? number_format($valor_diaria, 2, ',', '.') : '' ?>"
            style="flex: 1;">
          <span id="resultadoCalculo" style="font-weight: bold; color: #2c3e50;"></span>
        </div>
      </div>

      <div class="custom-inputbox">
        <label for="unidadeMeta"><i class="fa-solid fa-bullseye"></i> Qtd de Unidade </label>
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






<script>
  const valorBanca = document.getElementById('valorBanca');
  const radioWrapper = document.getElementById('radioWrapper');
  const inputAcaoContainer = document.getElementById('inputAcaoContainer');
  const inputAcao = document.getElementById('inputAcao');
  const radioInputs = document.querySelectorAll('input[name="acaoBanca"]');

  // Oculta o campo ao carregar
  inputAcaoContainer.style.display = 'none';

  function verificarValorBanca() {
    const valorLimpo = valorBanca.value.replace(/\./g, '').replace(',', '.');
    const valor = parseFloat(valorLimpo);

    if (!isNaN(valor) && valor > 0) {
      radioWrapper.style.display = 'flex';
    } else {
      radioWrapper.style.display = 'none';
      radioInputs.forEach(radio => {
        radio.checked = false;
        radio.dataset.checked = "false";
      });
      inputAcaoContainer.style.display = 'none';
      valorBanca.readOnly = false;
      valorBanca.style.backgroundColor = '';
      inputAcao.classList.remove('input-destaque');
    }
  }

  verificarValorBanca();
  valorBanca.addEventListener('input', verificarValorBanca);

  radioInputs.forEach(radio => {
    radio.addEventListener('click', () => {
      if (radio.checked) {
        if (radio.dataset.checked === "true") {
          radio.checked = false;
          radio.dataset.checked = "false";

          valorBanca.readOnly = false;
          valorBanca.style.backgroundColor = '';
          inputAcaoContainer.style.display = 'none';
          inputAcao.classList.remove('input-destaque');
        } else {
          radioInputs.forEach(r => r.dataset.checked = "false");
          radio.dataset.checked = "true";

          valorBanca.readOnly = true;
          valorBanca.style.backgroundColor = '#eee';
          inputAcaoContainer.style.display = 'block';
          inputAcao.classList.add('input-destaque');

          inputAcao.value = ''; // limpa o campo

          if (radio.value === 'add') {
            inputAcao.placeholder = 'R$ 0,00  Adicionar Valor na Banca';
          } else if (radio.value === 'sacar') {
            inputAcao.placeholder = 'R$ 0,00 Sacar Vlaor Depositado';
          }
        }
      }
    });
  });
</script>























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
    resultadoCalculo.textContent = `Sua Unidade de Entrada nas Apostas é de: ${resultado.toLocaleString("pt-BR", {
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
      resultadoUnidade.textContent = `O Valor da Sua Meta Diária é de: ${total.toLocaleString("pt-BR", {
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












<script>
document.addEventListener("DOMContentLoaded", () => {
  let idSaqueSelecionado = null;

  // Alterna o dropdown
  function toggleDropdown() {
    document.getElementById("dropdown-options").classList.toggle("show");
  }

  // Seleciona uma opção do dropdown
  function selectOption(texto, valor) {
    document.getElementById("dropdown-selected").innerHTML =
      `<i class="fa-solid fa-bars"></i> ${texto}`;
    document.getElementById("acao").value = valor;
    toggleDropdown();

    const blocoUnidade = document.querySelector(".bloco-unidade");

    if (valor === "saque") {
      blocoUnidade.style.display = "none"; // Oculta container
      document.body.classList.add("ocultar-scroll"); // Remove scroll lateral
      carregarSaques();
    } else {
      blocoUnidade.style.display = ""; // Exibe novamente
      document.body.classList.remove("ocultar-scroll"); // Restaura scroll
      document.getElementById("historico-saques").innerHTML = "";
    }
  }

  // Carrega lista de saques
  function carregarSaques() {
    fetch("listar-saques.php")
      .then(res => res.json())
      .then(saques => {
        const container = document.getElementById("historico-saques");
        const blocoUnidade = document.querySelector(".bloco-unidade");
        container.innerHTML = "";

        if (!Array.isArray(saques) || saques.length === 0) {
          blocoUnidade.style.display = ""; // Garante que o container volte
          container.innerHTML = ""; // Não exibe mensagem
          return;
        }

        saques.forEach(s => {
          container.innerHTML += `
            <div class="entrada-card" style="border-left: 6px solid #16b445;">
              <div class="entrada-info">
                <p><strong>Saque:</strong> R$ ${s.saque}</p>
                <p class="info-pequena"><strong>Data:</strong> ${s.data}</p>
              </div>
              <div class="entrada-acoes">
                <button class="btn-icon btn-lixeira" title="Excluir" data-id="${s.id}">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </div>
          `;
        });
      })
      .catch(err => {
        console.error("Erro ao carregar saques:", err);
        document.getElementById("historico-saques").innerHTML =
          "<p style='color:red;'>Erro ao carregar dados.</p>";
      });
  }

  // Toast visual
  function mostrarToast(mensagem) {
    const toast = document.getElementById("toast-msg");
    toast.textContent = mensagem;
    toast.classList.remove("hidden");
    toast.classList.add("show");

    setTimeout(() => {
      toast.classList.remove("show");
      setTimeout(() => toast.classList.add("hidden"), 500);
    }, 3000);
  }

  // Clique na lixeira
  document.getElementById("historico-saques").addEventListener("click", (e) => {
    const botao = e.target.closest(".btn-lixeira");
    if (botao && botao.dataset.id) {
      idSaqueSelecionado = botao.dataset.id;
      document.getElementById("modal-confirmacao").classList.remove("oculta");
    }
  });

  // Cancelar exclusão
  document.getElementById("btnCancelarBanca").addEventListener("click", () => {
    document.getElementById("modal-confirmacao").classList.add("oculta");
    idSaqueSelecionado = null;
  });

  // Confirmar exclusão
  document.getElementById("btnConfirmarBanca").addEventListener("click", () => {
    if (!idSaqueSelecionado) return;

    fetch("excluir-saque.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `id=${encodeURIComponent(idSaqueSelecionado)}`
    })
      .then(res => res.text())
      .then(msg => {
        mostrarToast("✅ " + msg.trim());
        document.getElementById("modal-confirmacao").classList.add("oculta");
        idSaqueSelecionado = null;
        carregarSaques();
      })
      .catch(err => {
        console.error("Erro ao excluir saque:", err);
        mostrarToast("❌ Falha ao excluir.");
      });
  });

  // Expondo funções globais
  window.toggleDropdown = toggleDropdown;
  window.selectOption = selectOption;
});
</script>




<script>
 function toggleMenu() {
  var menu = document.getElementById("menu");
  menu.style.display = menu.style.display === "block" ? "none" : "block";
 }
</script>








</body>
</html>


