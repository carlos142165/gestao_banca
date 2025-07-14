<?php
session_start();
require_once 'config.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ✅ Verificação de sessão
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
    echo "<script>alert('Área de membros — faça seu login!'); window.location.href = 'home.php';</script>";
    exit();
}

// ✅ PROCESSAMENTO DAS AÇÕES
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {

    // 📌 Cadastro de Mentor
    if ($_POST['acao'] === 'cadastrar_mentor') {
        $usuario_id = $_SESSION['usuario_id'];
        $nome = $_POST['nome'];

        $foto_nome = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $extensao = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $foto_nome = uniqid() . '.' . $extensao;
            $caminho_destino = 'uploads/' . $foto_nome;
            if (!move_uploaded_file($_FILES['foto']['tmp_name'], $caminho_destino)) {
                die("Erro ao fazer upload da imagem.");
            }
        }

        $stmt = $conexao->prepare("INSERT INTO mentores (id_usuario, foto, nome, data_criacao) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iss", $usuario_id, $foto_nome, $nome);

        if ($stmt->execute()) {
            echo "<script>alert('✅ Mentor cadastrado com sucesso!'); window.location.href = 'gestao-diaria.php';</script>";
            exit;
        } else {
            echo "<script>alert('Erro ao cadastrar mentor'); window.location.href = 'gestao-diaria.php';</script>";
            exit;
        }
    }

    
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
  background-color:rgb(235, 235, 235);
  margin: 0;
  padding: 0;
  color: #f5f5f5;
      

}


/*AQUI CODIGO PARA OS VALORES E PLACAR*/

/* Container geral */
.container-valores {
  background-color: #f7f6f6; /* cinza claro elegante */
  padding: 20px;
  border-radius: 12px;
  box-sizing: border-box;
  max-width: 400px;
  margin: 0 auto;
  margin-top: 15px;
  width: 390px;
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

.placar-green {
  color: #00a651;
  font-size: 1.2em;
  gap: 30px;
  margin-right: 60px;
  font-weight: bold;
 
}

.placar-red {
  color: #f82008;
  font-size: 1.2em;
  margin-left: 60px;
  font-weight: bold;
  
}

.separador {
  font-size: 1.2em;
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
  background-color: #f7f6f6;
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
  color:rgb(172, 167, 167);
  margin-bottom: 6px;
  margin-left: 0px;
  margin: 0;
  margin-left: 0;
  font-weight: bold;
  margin-top: 3px;
  
}

.rotulo-saldo {
  display: block;
  font-size: 12px;
  color:rgb(172, 167, 167);
  margin-bottom: 6px;
  margin-left:-3px;
  margin: 0;
  margin-left: 0px;
  font-weight: bold;
  margin-top: 3px;
  
  
}

.valor-meta {
  font-size: 1.4em;
  font-weight: bold;
  color:rgb(161, 158, 158);
  margin-bottom: 2px;
  margin-left: 0px;
}

.valor-saldo {
  font-size: 1.4em;
  font-weight: bold;
  color:rgb(161, 158, 158);
  margin-bottom: 2px;
  margin-left: 0px;
}

.cinza {
  color: #555;
}
/* FIM CODIGO PARA OS VALORES E PLACAR */




/* AQUI VAI O CODIGO PARA O FORMULARIO DE ADICIONAR UM NOVO USUARIO */
/* Modal geral */
.modal {
  display: none;
  position: fixed;
  z-index: 1000;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0,0,0,0.6);
}

/* Conteúdo do modal centralizado */
.modal-conteudo {
  position: relative; /* ← isso é fundamental */
  background-color: #fff;
  margin: 5% auto;
  padding: 30px 25px;
  border-radius: 12px;
  max-width: 400px;
  width: 70%;
  box-shadow: 0 0 15px rgba(0,0,0,0.3);
  animation: fadeIn 0.3s ease-in-out;
  font-family: 'Segoe UI', sans-serif;
  top: 100px;
}

/* Título e botão de fechar */
.fechar {
  position: absolute;
  top: 2px;
  right: 15px;
  font-size: 26px;
  cursor: pointer;
  color: #888;
  font-weight: bold;
  background: none;
  border: none;
}

/* Labels e campos */
.modal label {
  display: block;
  margin-top: 15px;
  font-weight: 500;
  color: #333;
}

.modal input[type="text"],
.modal input[type="file"],
.modal button[type="submit"],
.modal input[type="number"] {
  width: 100%;
  padding: 10px 12px;
  margin-top: 8px;
  border: 1px solid #ccc;
  border-radius: 8px;
  box-sizing: border-box;
  font-size: 14px;
}

/* Botão customizado para upload */
.botao-upload {
  display: inline-block;
  padding: 10px 15px;
  margin-top: 15px;
  background-color: #4CAF50;
  color: white;
  cursor: pointer;
  border-radius: 8px;
  font-weight: bold;
  text-align: center;
}

/* Pré-visualização da imagem */
#preview-container {
  margin-top: 15px;
  text-align: center;
}

#preview-img {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  object-fit: cover;
  border: 3px solid #4CAF50;
  box-shadow: 0 0 8px rgba(0,0,0,0.2);
  transition: 0.3s ease-in-out;
}

/* Botão remover imagem */
#remover-foto {
  background-color: #f44336;
  color: white;
  border: none;
  padding: 8px 12px;
  border-radius: 8px;
  cursor: pointer;
  margin-top: 10px;
  font-size: 14px;
  display: inline-block;
}

/* Botão de envio */
.modal button[type="submit"] {
  background-color: #2196F3;
  color: white;
  border: none;
  padding: 10px 0;
  margin-top: 20px;
  border-radius: 8px;
  font-size: 15px;
  font-weight: bold;
  transition: background-color 0.3s ease;
}

.modal button[type="submit"]:hover {
  background-color: #1976D2;
}

/* Nome do arquivo */
#nome-arquivo {
  display: block;
  margin-top: 8px;
  font-style: italic;
  font-size: 13px;
  color: #666;
  text-align: center;
}

/* Animação */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-20px); }
  to { opacity: 1; transform: translateY(0); }
}

/* AQUI O FIM DO CODIGO PARA O FORMULARIO DE ADICIONAR UM NOVO USUARIO */



/* CODIGO DO FORMULARIO COM FOTO PARA CADASTRO */
.formulario-mentor {
  display: none;
  background-color: #ffffff;
  padding: 20px;
  border-radius: 10px;
  box-shadow: 0 6px 12px rgba(0,0,0,0.15);
  width: 390px;
  margin: 10px auto;
  text-align: center;
  position: relative;
  z-index: 1000;
}




/* AQUI O CODIGO DO PERFIL DE CADA MENTORES*/
.mentor-card {
    display: flex;
    align-items: center;
    border: 1px solid #dcdcdc;
    border-radius: 8px;
    padding: 6px 15px;
    background-color: #fff;
    width: 300px;
    height: 67px;
    font-family: Arial, sans-serif;
    margin-top: 0px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    gap: 0px;
    background-color: #f7f6f6;
    border-radius: 10px;
    padding: 8px;    
    margin: 10px auto;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    cursor: pointer;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    margin-left: 12px;
    
}


.card-neutro {
  border-color: #999; /* Ou qualquer cinza que combine com seu tema */
}
.card-positivo {
  border-color: #2ecc71; /* verde */
  
}

.card-negativo {
  border-color: #e74c3c; /* vermelho */
 
}






.mentor-right.reorganizado {
  display: flex;
  justify-content: space-between;
  gap: 20px;
}

.mentor-values-esquerda {
  display: flex;
  flex-direction: column;
  gap: 8px;
  align-items: flex-start;
}

.mentor-values-direita {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}

.mentor-card:hover {
  transform: scale(1.03);
  box-shadow: 0 8px 12px rgba(0,0,0,0.15);
}


.formulario-mentor {
  position: fixed;               /* fixo na tela */
  top: 50%;                      /* 50% da altura da tela */
  left: 50%;                     /* 50% da largura da tela */
  transform: translate(-50%, -50%); /* ajusta para centro exato */
  background-color: #ffffff;
  padding: 20px;
  border-radius: 10px;
  box-shadow: 0 6px 12px rgba(0,0,0,0.15);
  width: 390px;
  text-align: center;
  z-index: 1000;
  display: none;
}


.mentor-foto-preview {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #00a651;
  margin-bottom: 10px;
}

.mentor-nome-preview {
  font-size: 14px;
  font-weight: bold;
  margin-bottom: 15px;
}

.overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100vh;
  background-color: rgba(0, 0, 0, 0.5); /* fundo escuro */
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 999;
}

.mentor-left {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 30px;
    
}

.mentor-img {
    border-radius: 50%;
    object-fit: cover;
    margin-top: 15px;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #00a651;
}

.mentor-nome {
    font-size: 11px;
    margin-top: 2px;
    color: #333;
    text-align: center;
    font-weight: normal;
    
    
}

.mentor-right {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
}

.mentor-values-inline {
    display: flex;
    gap: 15px;
    align-items: center;
    justify-content: center;
    height: 100%;
    color:rgb(95, 93, 93);
    
}

.value-box-green {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    width: 60px;
    font-size: 13px;
}

.value-box-green p {
    margin: 2px 1;
    line-height: 0.2;    /* margem de altura entre os valores e os nomes */
}

.value-box-green.saldo p:last-child {
  white-space: nowrap;        /* Evita quebra de linha no valor */
            /* Oculta transbordamentos (se quiser) */
  text-overflow: ellipsis;    /* Adiciona "..." se for muito longo */
            /* Garante que use o espaço disponível */
  display: inline-block;
}

.value-box-green.saldo {
            /* Aumenta espaço mínimo da caixa */
  flex-grow: 1;               /* Permite que ela cresça no flex container */
}


.value-box-green p:nth-child(2) {
  font-size: 13px;   /* aumenta o tamanho da fonte dos valores */
  color: #333;       /* cor mais forte para visibilidade */
}

.value-box-green p:first-child {
    font-weight: bold;
}

.value-box-green.green p:first-child {
    color: #00a651;
    
}

.value-box-green.red p:first-child {
    color: #ff4d4d;
}

.value-box-green.saldo p:first-child {
    color:rgb(95, 93, 93);
}

.value-box-red {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    width: 60px;
    font-size: 13px;
    margin-left: -25px;
}

.value-box-red p {
    margin: 2px 1;
    line-height: 0.2;    /* margem de altura entre os valores e os nomes */
}

.value-box-red.saldo p:last-child {
  white-space: nowrap;        /* Evita quebra de linha no valor */
            /* Oculta transbordamentos (se quiser) */
  text-overflow: ellipsis;    /* Adiciona "..." se for muito longo */
            /* Garante que use o espaço disponível */
  display: inline-block;
}

.value-box-red.saldo {
            /* Aumenta espaço mínimo da caixa */
  flex-grow: 1;               /* Permite que ela cresça no flex container */
}


.value-box-red p:nth-child(2) {
  font-size: 13px;   /* aumenta o tamanho da fonte dos valores */
  color: #333;       /* cor mais forte para visibilidade */
}

.value-box-red p:first-child {
    font-weight: bold;
}

.value-box-red.green p:first-child {
    color: #00a651;
    
}

.value-box-red.red p:first-child {
    color: #ff4d4d;
}

.value-box-red.saldo p:first-child {
    color:rgb(95, 93, 93);
}

.value-box-saldo {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    width: 60px;
    font-size: 13px;
}

.value-box-saldo p {
    margin: 2px 1;
    line-height: 0.2;    /* margem de altura entre os valores e os nomes */
}

.value-box-saldo.saldo p:last-child {
  white-space: nowrap;        /* Evita quebra de linha no valor */
            /* Oculta transbordamentos (se quiser) */
  text-overflow: ellipsis;    /* Adiciona "..." se for muito longo */
            /* Garante que use o espaço disponível */
  display: inline-block;
}

.value-box-saldo.saldo {
            /* Aumenta espaço mínimo da caixa */
  flex-grow: 1;               /* Permite que ela cresça no flex container */
}


.value-box-saldo p:nth-child(2) {
  font-size: 13px;   /* aumenta o tamanho da fonte dos valores */
  color: #333;       /* cor mais forte para visibilidade */
}

.value-box-saldo p:first-child {
    font-weight: bold;
}

.value-box-saldo.green p:first-child {
    color: #00a651;
    
}

.value-box-saldo.red p:first-child {
    color: #ff4d4d;
}

.value-box-saldo.saldo p:first-child {
    color:rgb(95, 93, 93);
}

/* FIM DO CODIGO DO PERFIL DE CADA MENTORES */


.checkbox-green {
  position: relative;
  padding-left: 35px;
  cursor: pointer;
}

.checkbox-green input[type="checkbox"] {
  position: absolute;
  opacity: 0;
  background-color:rgb(216, 68, 68);
}

.checkbox-green::before {
  content: '';
  position: absolute;
  left: 0;
  top: 4px;
  width: 16px;
  height: 16px;
  background-color: #ccc;
  border-radius: 4px;
  color: #00a651;
}

.checkbox-green input[type="checkbox"]:checked + label::before {
  background-color: green;
}





/* AQUI VAI O CODIGO RESPONSAVEL PELO CAMPO ONDE OS USUARIOS VÃO FICAR  */

.btn-add-usuario {
  width: 390px;
  height: 40px;
  color: white;
  background-color:rgb(234, 243, 238);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.01);
  cursor: pointer;
  border-radius: 0;
  font-size: 13px;
  border: none;
  transition: background 0.3s ease, transform 0.2s ease;
  border-radius: 0px;
  margin-top: 8px;
  color: rgb(11, 131, 61);
}

.btn-add-usuario:hover {
  background-color:rgb(225, 240, 232);
  
}


/* Ícone "+" com destaque verde */
.btn-add-usuario span {
  color: rgb(11, 131, 61);
  font-weight: bold;
  font-size: 18px;
  
}

.add-user {
  display: flex;
  justify-content: center;
  align-items: center;
  width: 100%;
  margin-top: 0; /* Remove o espaçamento do topo */
  padding-top: 0;
  gap: 6px; 
  
}


.campo_mentores {
  position: relative; /* Certifique-se de que pode posicionar elementos internos */
  padding: 0;
  margin: 0;
  background-color: #f7f6f6;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-start;
  width: 390px;
  margin: 0 auto;
  margin-top: 0px;
  border-radius: 0px;
  padding: 0px;
  box-sizing: border-box;
  max-height: 550px;
  overflow-y: auto;

}

.campo_mentores::-webkit-scrollbar {
  display: none;     /* Oculta totalmente a barra no Chrome/Safari */
}



.mentor-wrapper{
  margin-top: 15px;
}

/* Estilo para dispositivos com largura até 768px (ex: celulares) */
@media (max-width: 768px) {
  .campo_mentores {
    height: 500px; /* altura menor para celular */
    width: 390px;    /* ajusta a largura também para adaptar melhor */
  }
}

/* FIM DO CODIGO RESPONSAVEL PELO CAMPO ONDE OS USUARIOS VÃO FICAR  */




/* CODIGO FORMULARIO CADASTRO DOS VALORES DOS MEMBROS  */
  .formulario-mentor {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  background-color: #ffffff;
  padding: 30px 25px;
  border-radius: 16px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.2);
  width: 270px;
  height: 320px;
  text-align: center;
  font-family: 'Poppins', sans-serif;
  z-index: 1000;
  align-items: center;
}

.botao-fechar {
  position: absolute;
  top: 12px;
  right: 15px;
  background: none;
  border: none;
  font-size: 15px;
  cursor: pointer;
  color: #999;
  transition: color 0.3s ease;
}
.botao-fechar:hover {
  color: #333;
}

.mentor-foto-preview {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  object-fit: cover;
  border: 3px solid #00a651;
  margin-bottom: 12px;
}

.mentor-nome-preview {
  font-size: 18px;
  font-weight: bold;
  color: #333;
  margin-bottom: 25px;
  margin-top: -2px;
}

form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.checkbox-container {
  display: flex;
  justify-content: center;
  gap: 30px;
}

.checkbox-wrapper {
  display: flex;
  align-items: center;
  flex-direction: column;
  gap: 8px;
}

input[type="checkbox"] {
  appearance: none;
  width: 24px;
  height: 24px;
  border: 2px solid #ccc;
  border-radius: 6px;
  transition: background-color 0.3s;
  cursor: pointer;
}

/* Green selecionado */
#green:checked {
  background-color: #00a651;
  border-color: #00a651;
}
/* Red selecionado */
#red:checked {
  background-color: #f44336;
  border-color: #f44336;
}

/* Textos personalizados */
.checkbox-label {
  font-weight: bold;
  font-size: 16px;
  color: #555;
  transition: color 0.3s;
}
#green:checked + .green-label {
  color: #00a651;
}
#red:checked + .red-label {
  color: #f44336;
}

input[type="text"] {
  padding: 12px;
  border-radius: 10px;
  border: 1px solid #ccc;
  font-size: 16px;
  text-align: center;
}

.botao-enviar {
  background: linear-gradient(to right, #00a651, #3ac77b);
  border: none;
  color: white;
  font-weight: bold;
  font-size: 16px;
  padding: 12px;
  border-radius: 10px;
  cursor: pointer;
  transition: transform 0.2s ease, background 0.3s ease;
}
.botao-enviar:hover {
  transform: scale(1.04);
  background: linear-gradient(to right, #3ac77b, #00a651);
}

.input-valor {
  
  padding: 14px 18px;
  border: 1px solid #ccc;
  border-radius: 8px;
  font-size: 16px;
  outline: none;
  box-shadow: 0 4px 8px rgba(0,0,0,0.05);
  transition: border-color 0.3s ease;
}

.input-valor:focus {
  border-color: #4CAF50;
}



/* FIM CODIGO FORMULARIO CADASTRO DOS VALORES DOS MEMBROS  */





/* CODIGO DA MENSAGEM DE ALERTA OU CADASTRO  */
.toast {
  position: fixed;
  top: 60px;
  left: 50%;
  transform: translateX(-50%);
  min-width: 280px;
  padding: 10px 15px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: bold;
  z-index: 9999;
  display: none;
  text-align: center;
  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
  opacity: 0;
  transition: opacity 0.3s ease;
}
.toast.sucesso,
.toast.aviso,
.toast.erro {
  opacity: 1;
}


.toast.sucesso {
  background-color: #4CAF50;
  color: white;
}
.toast.erro {
  background-color: #f44336;
  color: white;
}
.toast.aviso {
  background-color: #ffc107;
  color: #333;
}
/* FIM DO CODIGO DA MENSAGEM DE ALERTA OU CADASTRO  */




/* RESPONSAVEL PELO CODIGO DO RANKIG  */
.mentor-rank {
  font-weight: bold;
  margin-left: 0px;
}

.mentor-item {
  display: flex;
  align-items: center;
  margin-bottom: 0px;
  gap: 0px;
  margin-left: 0px;
}

.mentor-rank-externo {
  font-size: 15px;
  font-weight: bold;
  color:rgb(90, 90, 90);
  width: 50px;
  text-align: right;
  margin-left: -12px;
  
}

.mentor-card {
  flex: 1;
}
/* FIM CODIGO RESPONSAVEL PELO CODIGO DO RANKIG  */





/* CODIGO RESPONSAVEL PELOS 3 PONTINHOS DO MENU  */
.mentor-item {
  display: flex;
  align-items: center;
  position: relative;
  margin-bottom: 12px;
}

.mentor-card {
  flex: 1;
  position: relative;
  margin-right: 38px; /* espaço para o menu externo */
}

.mentor-menu-externo {
  position: absolute;
  right: 23px;
  top: 50%;
  transform: translateY(-50%);
  z-index: 10;
}

.menu-toggle {
  font-size: 22px;
  font-weight: bold;
  cursor: pointer;
  user-select: none;
  padding: 0px;
  color: #333;
}

.menu-opcoes {
  display: none;
  position: absolute;
  top: 28px;
  right: 0;
  background-color: #fff;
  border: 1px solid #ccc;
  border-radius: 6px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.2);
  min-width: 160px;
  padding: 6px;
}

.menu-opcoes button {
  display: block;
  width: 100%;
  background: none;
  border: none;
  text-align: left;
  font-size: 14px;
  padding: 6px 10px;
  cursor: pointer;
}

.menu-opcoes button:hover {
  background-color: #f0f0f0;
}
/* FIM CODIGO RESPONSAVEL PELOS 3 PONTINHOS DO MENU  */






/* TESTE  */
/* Tela geral */
.tela-edicao {
  position: fixed;
  top: 5%;
  left: 10%;
  right: 10%;
  max-height: 85vh;
  overflow-y: auto;
  background: #ffffff;
  border-radius: 16px;
  padding: 20px 25px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
  z-index: 9999;
  font-family: 'Segoe UI', sans-serif;
  color: #333;
}

/* Botão de fechar */
.tela-edicao button {
  background: none;
  border: none;
  font-size: 1.4rem;
  color: #888;
  cursor: pointer;
}

.tela-edicao button:hover {
  color: #e74c3c;
}

/* Imagem do mentor */
.mentor-img-edicao {
  display: block;
  margin: 15px auto 5px;
  width: 80px;
  height: 80px;
  border-radius: 50%;
  object-fit: cover;
  box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

/* Título */
.tela-edicao h3 {
  text-align: center;
  margin-bottom: 20px;
  font-size: 1.4rem;
  color: #2c3e50;
  font-weight: 600;
}

/* Entradas */
.entrada-card {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  background: #f5f9ff;
  border-left: 6px solid #4CAF50;
  border-radius: 10px;
  padding: 12px 16px;
  margin-bottom: 12px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.06);
}

.entrada-info p {
  margin: 4px 0;
  font-size: 0.95rem;
  color: #34495e;
}

.entrada-acoes {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.btn-icon {
  background: none;
  border: none;
  font-size: 1.2rem;
  cursor: pointer;
  transition: transform 0.2s ease;
}

.btn-icon i {
  pointer-events: none; /* evita duplo clique em ícone */
}

.btn-icon.editar i {
  color: #4CAF50;
}

.btn-icon.excluir i {
  color: #e74c3c;
}

.btn-icon:hover {
  transform: scale(1.2);
}












</style>
     
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
     


</head>








  <body>



    <!-- CODIGO RESPONSAVEL PELOS VALORES DO TOPO PUXADO DA PAGINA MENU.PHP -->

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

<!-- FIM CODIGO RESPONSAVEL PELOS VALORES DO TOPO PUXADO DA PAGINA MENU.PHP -->




<!-- CODIGO RESPONSAVEL PELO VALOR  PLACAR E META DIARIA E SALDO -->
<div class="container-valores">
    
  <div class="pontuacao">

    <span class="placar-green">0</span>
    <span class="separador">x</span>
    <span class="placar-red">0</span>

  </div>



  <div class="informacoes-row">

   <div class="info-item">

    <div>
      <span class="valor-meta">R$ 1.000,00</span>
      <span class="rotulo-meta">Meta do Dia</span>
    </div>

  </div>


  <div class="info-item">

     <div>
      <span class="valor-saldo">R$ 0,00</span>
      <span class="rotulo-saldo">Saldo do Dia</span>
     </div>

  </div>


</div>
<!-- FIM DO CODIGO RESPONSAVEL PELO VALOR  PLACAR E META DIARIA E SALDO -->







<!-- CODIGO RESPONSAVEL PELO FORMULARIO QUE ADICIONA NOVO USUARIO -->

<!-- Modal -->
<div id="modal-form" class="modal">
  <div class="modal-conteudo">
    <span class="fechar" onclick="fecharModal()">&times;</span>

    <form method="POST" enctype="multipart/form-data" action="gestao-diaria.php" class="formulario-mentor-completo">
      <input type="hidden" name="acao" value="cadastrar_mentor">

      <!-- Botão para selecionar a foto -->
      <div class="input-group">
        <label for="foto" class="label-form">Foto do Mentor:</label>
        <input type="file" name="foto" id="foto" class="input-file" onchange="mostrarNomeArquivo(this)" required>
        <span id="nome-arquivo" class="nome-arquivo">Nenhum arquivo selecionado</span>
      </div>

      <!-- Pré-visualização da imagem -->
      <div class="preview-foto-wrapper">
        <img id="preview-img" src="https://cdn-icons-png.flaticon.com/512/847/847969.png" class="preview-img" alt="Pré-visualização">
        <button type="button" id="remover-foto" class="btn-remover-foto" onclick="removerImagem()" style="display:none;">Remover Foto</button>
      </div>

      <!-- Nome abaixo da foto -->
      <h3 class="mentor-nome-preview" style="text-align: center; margin-top: 14px;">Nome do Mentor</h3>

      <!-- Campo para digitar o nome -->
      <div class="input-group">
        <label for="nome" class="label-form">Nome do Mentor:</label>
        <input type="text" name="nome" id="nome" class="input-text" placeholder="Nome do Mentor" required>
      </div>

      <!-- Botão de envio -->
      <div class="botoes-formulario">
        <button type="submit" class="btn-enviar">Cadastrar Mentor</button>
      </div>
    </form>
  </div>
 </div>


 
</div>

<!-- FIM DO CODIGO RESPONSAVEL PELO FORMULARIO QUE ADICIONA NOVO USUARIO -->






<!-- CODIGO RESPONSAVEL PELO CAMPO ONDE OS MENTORES VAO FICAR -->




<div class="add-user">
        <button class="btn-add-usuario" onclick="abrirModal()">
          <span>+</span> Adicionar Mentoria
        </button>
  </div>





<!-- AQUI FILTRA OS DADOS DOS MENTORES NO BANCO DE DADOS PRA MOSTRAR NA TELA  -->
<div class="campo_mentores">

  <!-- BOTÃO ADICIONAR USUARIO -->
  
  <div class="mentor-wrapper">
  <?php
  $id_usuario_logado = $_SESSION['usuario_id'];
  $sql_mentores = "SELECT id, nome, foto FROM mentores WHERE id_usuario = ?";
  $stmt_mentores = $conexao->prepare($sql_mentores);
  $stmt_mentores->bind_param("i", $id_usuario_logado);
  $stmt_mentores->execute();
  $result_mentores = $stmt_mentores->get_result();

  $lista_mentores = [];

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

      // Armazena dados no array para ordenação
      $mentor['valores'] = $valores;
      $mentor['saldo'] = $total_subtraido;
      $lista_mentores[] = $mentor;
  }

  // Ordena pela maior pontuação de saldo
  usort($lista_mentores, function($a, $b) {
    return $b['saldo'] <=> $a['saldo'];
  });

  // Exibe classificados
 foreach ($lista_mentores as $posicao => $mentor) {
  $rank = $posicao + 1;
  $valores = $mentor['valores'];
  $saldo_formatado = number_format($mentor['saldo'], 2, ',', '.');

  // 🟩 Verde se saldo > 0
  // 🟥 Vermelho se saldo < 0
  // ⚪ Cinza se saldo == 0
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
          <div class='value-box-green green'>
            <p>Green</p><p>{$valores['total_green']}</p>
          </div>
          <div class='value-box-red red'>
            <p>Red</p><p>{$valores['total_red']}</p>
          </div>
          <div class='value-box-saldo saldo'>
            <p>Saldo</p><p>R$ {$saldo_formatado}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- 🔘 Menu lateral flutuante com 3 pontinhos -->
    <div class='mentor-menu-externo'>
      <span class='menu-toggle' title='Mais opções'>⋮</span>

      <div class='menu-opcoes'>
        <button onclick='editarAposta({$mentor["id"]})'>
        <i class='fas fa-pen-to-square'></i> Editar Entrada
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







<!-- Formulário do mentor -->
<div class="formulario-mentor">
  <button type="button" class="botao-fechar" onclick="fecharFormulario()">❌</button>
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




<div id="mensagem-status" class="toast" style="display:none;"></div>




<!-- FILTRO PARA EXCLUIR OU EDITAR MENTOR  -->
<div id="tela-edicao" class="tela-edicao" style="display:none;">
  <button onclick="fecharTelaEdicao()" style="float:right;">❌</button>
  <img id="fotoMentorEdicao" class="mentor-img-edicao" />
  <h3>Entradas de Hoje - <span id="nomeMentorEdicao"></span></h3>
  <div id="resultado-filtro"></div>
</div>
<!-- FIM FILTRO PARA EXCLUIR OU EDITAR MENTOR  -->









<script>
document.addEventListener("DOMContentLoaded", function () {
  const formulario = document.querySelector(".formulario-mentor");
  const nomePreview = formulario.querySelector(".mentor-nome-preview");
  const fotoPreview = formulario.querySelector(".mentor-foto-preview");
  const idHidden = formulario.querySelector(".mentor-id-hidden");
  const formMentor = document.getElementById("form-mentor");
  const botaoFechar = document.querySelector(".botao-fechar");
  const campoValor = document.getElementById("valor");

  // 🔎 Exibir dados do mentor ao clicar
  function exibirFormularioMentor(card) {
    nomePreview.textContent = card.getAttribute("data-nome");
    fotoPreview.src = card.getAttribute("data-foto");
    idHidden.value = card.getAttribute("data-id");
    formulario.style.display = "block";
  }

  // 🎯 Aplica eventos nos cards
  function atribuirEventosAoCards() {
    document.querySelectorAll(".mentor-card").forEach(card => {
      card.addEventListener("click", () => exibirFormularioMentor(card));
    });
  }

  // 🔄 Atualiza cards via fetch
  function atualizarCards() {
    fetch("carregar-mentores.php")
      .then(res => res.text())
      .then(html => {
        document.querySelector(".mentor-wrapper").innerHTML = html;
        atribuirEventosAoCards(); // reaplica eventos nos novos cards
      });
  }

  // 📦 Formatação automática do campo de valor (R$)
  campoValor.addEventListener("input", function () {
  let valor = this.value.replace(/\D/g, "");

  if (valor === "") {
    this.value = "R$ 0,00";
    return;
  }

  // Garante pelo menos dois dígitos
  if (valor.length < 3) {
    valor = valor.padStart(3, "0");
  }

  const reais = valor.slice(0, -2);
  const centavos = valor.slice(-2);
  this.value = `R$ ${parseInt(reais).toLocaleString("pt-BR")},${centavos}`;
});


  // 📩 Envio do formulário
  formMentor.addEventListener("submit", function (e) {
    e.preventDefault();

    const opcaoSelecionada = document.querySelector("input[name='opcao']:checked");
    if (!opcaoSelecionada) {
      mostrarToast("⚠️ Por favor, selecione Green ou Red.");
      return;
    }

    let valor = campoValor.value.replace(/\D/g, "").padStart(3, "0");
    const reais = valor.slice(0, -2);
    const centavos = valor.slice(-2);
    campoValor.value = `${reais}.${centavos}`; // formato decimal

    const formData = new FormData(this);

    fetch("cadastrar-valor.php", {
      method: "POST",
      body: formData
    })
    .then(response => response.text())
.then(mensagem => {
  mostrarToast(mensagem, "sucesso"); // ✅ aqui entra sua função estilizada
  formMentor.reset();
  formulario.style.display = "none";
  atualizarCards();
})
    .catch(error => {
      alert("❌ Erro ao enviar: " + error);
    });
  });

  // ❌ Fechar formulário
  window.fecharFormulario = function () {
    formMentor.reset();
    formulario.style.display = "none";
  };

  botaoFechar.addEventListener("click", fecharFormulario);

  // ▶️ Inicializar
  atribuirEventosAoCards();
});

function mostrarToast(mensagem, tipo = "aviso") {
  const toast = document.getElementById("mensagem-status");
  toast.className = "toast " + tipo;
  toast.textContent = mensagem;
  toast.style.display = "block";
  setTimeout(() => {
    toast.style.display = "none";
  }, 4000);
}


// ▶️ TESTANDO PARA 3 PONTOS MENU
document.addEventListener("click", function (e) {
  const isToggle = e.target.classList.contains("menu-toggle");

  document.querySelectorAll(".menu-opcoes").forEach(menu => {
    menu.style.display = "none";
  });

  if (isToggle) {
    const opcoes = e.target.nextElementSibling;
    if (opcoes) {
      opcoes.style.display = "block";
      e.stopPropagation();
    }
  }
});








</script>








<!-- CODIGO RESPONSAVEL PELO FORMULARIO QUE ADICIONA NOVO USUARIO -->

<script>
function abrirModal() {
  document.getElementById("modal-form").style.display = "block";
}

function fecharModal() {
  document.getElementById("modal-form").style.display = "none";
}

// Fecha o modal ao clicar fora do conteúdo
window.onclick = function(event) {
  const modal = document.getElementById("modal-form");
  if (event.target === modal) {
    fecharModal();
  }
}

// Mostra nome do arquivo escolhido
function mostrarNomeArquivo(input) {
  const nome = input.files[0]?.name || "Nenhum arquivo selecionado";
  document.getElementById("nome-arquivo").textContent = nome;

  const previewImg = document.getElementById("preview-img");
  const removerBtn = document.getElementById("remover-foto");

  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function (e) {
      previewImg.src = e.target.result;
      removerBtn.style.display = "inline-block";
    };
    reader.readAsDataURL(input.files[0]);
  } else {
    previewImg.src = "https://cdn-icons-png.flaticon.com/512/847/847969.png";
    removerBtn.style.display = "none";
  }
}

// Botão para remover imagem e restaurar avatar padrão
function removerImagem() {
  const previewImg = document.getElementById("preview-img");
  const inputFile = document.getElementById("foto");
  const removerBtn = document.getElementById("remover-foto");

  inputFile.value = ""; // limpa o input de arquivo
  previewImg.src = "https://cdn-icons-png.flaticon.com/512/847/847969.png"; // volta pro avatar
  document.getElementById("nome-arquivo").textContent = "Nenhum arquivo selecionado";
  removerBtn.style.display = "none";
}

</script>
<!-- FIM DO CODIGO RESPONSAVEL PELO FORMULARIO QUE ADICIONA NOVO USUARIO -->




<!-- TESTE-->
<script>
let mentorAtualId = null;

function editarAposta(idMentor) {
  mentorAtualId = idMentor;

  const card = document.querySelector(`[data-id='${idMentor}']`);
  const nome = card.getAttribute("data-nome");
  const foto = card.getAttribute("data-foto");

  document.getElementById("nomeMentorEdicao").textContent = nome;
  document.getElementById("fotoMentorEdicao").src = foto;
  document.getElementById("tela-edicao").style.display = "block";

  fetch(`filtrar-entradas.php?id=${idMentor}&tipo=hoje`)
    .then(res => res.json())
    .then(entradas => mostrarResultados(entradas));
}

function mostrarResultados(entradas) {
  const container = document.getElementById("resultado-filtro");
  container.innerHTML = "";

  if (!entradas || entradas.length === 0) {
    container.innerHTML = "<p style='color:gray;'>Nenhuma Entrada Cadastrada Hoje.</p>";
    return;
  }

  entradas.forEach(e => {
    const valorGreen = parseFloat(e.valor_green);
    const valorRed = parseFloat(e.valor_red);

    let infoHTML = "";

    if (e.green > 0) {
      infoHTML += `<p><strong>Green:</strong> ${e.green}</p>`;
    }

    if (e.red > 0) {
      infoHTML += `<p><strong>Red:</strong> ${e.red}</p>`;
    }

    if (!isNaN(valorGreen) && valorGreen > 0) {
      infoHTML += `<p><strong>Saldo:</strong> R$ ${valorGreen.toFixed(2)}</p>`;
    }

    if (!isNaN(valorRed) && valorRed > 0) {
      infoHTML += `<p><strong>Débito:</strong> R$ ${valorRed.toFixed(2)}</p>`;
    }

    container.innerHTML += `
      <div class="entrada-card">
        <div class="entrada-info">
          ${infoHTML}
        </div>
        <div class="entrada-acoes">
          <button onclick="editarEntrada(${e.id})" title="Editar" class="btn-icon editar">
            <i class="fas fa-pen"></i>
          </button>
          <button onclick="excluirEntrada(${e.id})" title="Excluir" class="btn-icon excluir">
            <i class="fas fa-trash"></i>
          </button>
        </div>
      </div>
    `;
  });
}


function editarEntrada(idEntrada) {
  alert("Abrir tela de edição para entrada ID " + idEntrada);
  // Aqui você pode abrir formulário com valores atuais para editar
}

function excluirEntrada(idEntrada) {
  if (confirm("Tem certeza que deseja excluir esta entrada?")) {
    fetch("excluir-entrada.php", {
      method: "POST",
      body: new URLSearchParams({ id: idEntrada })
    })
    .then(res => res.text())
    .then(msg => {
      alert(msg);
      editarAposta(mentorAtualId); // recarrega os dados após excluir
    });
  }
}

function salvarEdicao(event, idEntrada) {
  event.preventDefault();
  const formData = new FormData(event.target);
  formData.append("id", idEntrada);

  fetch("editar-entrada.php", {
    method: "POST",
    body: formData
  })
  .then(res => res.text())
  .then(msg => alert(msg))
  .catch(err => alert("Erro ao salvar: " + err));
}

function fecharTelaEdicao() {
  document.getElementById("tela-edicao").style.display = "none";
}
</script>



</body>
</html>
