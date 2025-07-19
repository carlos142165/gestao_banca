
<!-- CODIGO RESPONSAVEL PELA EDIÇÃO E EXCLUSÃO DO MENTOR  -->  
<?php
session_start();
require_once 'config.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// 👉 Função para registrar a mensagem toast na sessão
function setToast($mensagem, $tipo = 'sucesso') {
    $_SESSION['toast'] = [
        'mensagem' => $mensagem,
        'tipo' => $tipo
    ];
}

// 🔒 Verificação de sessão
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
    setToast('Área de membros — faça seu login!', 'aviso');
    header('Location: home.php');
    exit();
}

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

// 📝 Cadastro e edição de mentor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $usuario_id = $_SESSION['usuario_id'];
    $nome = $_POST['nome'];
    $mentor_id = $_POST['mentor_id'] ?? null;

    // 🖼️ Processamento da imagem
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
?>
<!-- FIM CODIGO RESPONSAVEL PELA EDIÇÃO E EXCLUSÃO DO MENTOR  --> 




<!-- CODIGO RESPONSAVEL PELO SALDO DO DIA  --> 
<?php
// Verifique se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    echo "Usuário não logado.";
    exit;
}
$idUsuario = $_SESSION['usuario_id'];

$sql = "SELECT SUM(valor_green) AS total_green, SUM(valor_red) AS total_red 
        FROM valor_mentores 
        WHERE id_usuario = ?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$resultado = $stmt->get_result();
$row = $resultado->fetch_assoc();

$saldo = $row['total_green'] - $row['total_red'];
$saldoFormatado = number_format($saldo, 2, ',', '.');
?>
<!-- FIM CODIGO RESPONSAVEL PELO SALDO DO DIA  -->





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
</style>
     
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
     <link rel="stylesheet" href="style.css">

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

<!-- FIM CODIGO RESPONSAVEL PELO  TOPO PUXADO DA PAGINA MENU.PHP -->





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
    <span class="valor-saldo">R$ <?= $saldoFormatado ?></span>
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
<button class="btn-add-usuario" onclick="prepararFormularioNovoMentor()">
  <i class="fas fa-user-plus"></i> 
</button>
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




<div id="mensagem-status" class="toast"></div>

<!-- DEIXA TOAST OCULTO -->
<div id="toast" class="toast hidden"></div>

<!-- PUXA O SCRIPT -->
<script src="script.js"></script>


</body>
</html>
