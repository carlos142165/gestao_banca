

<?php
ob_start();
require_once 'config.php';
require_once 'carregar_sessao.php';
require_once 'funcoes.php'; // ✅ Inclui a função de cálculo



mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ✅ Função de notificação
if (isset($_SESSION['toast'])) {
  $msg = addslashes($_SESSION['toast']['mensagem']);
  $tipo = $_SESSION['toast']['tipo'];
  echo "<script>
    document.addEventListener('DOMContentLoaded', function() {
      const toast = document.createElement('div');
      toast.textContent = '$msg';
      toast.style.position = 'fixed';
      toast.style.bottom = '20px';
      toast.style.right = '20px';
      toast.style.padding = '12px 20px';
      toast.style.backgroundColor = '" . ($tipo === 'sucesso' ? '#4CAF50' : '#F44336') . "';
      toast.style.color = '#fff';
      toast.style.borderRadius = '5px';
      toast.style.boxShadow = '0 2px 6px rgba(0,0,0,0.2)';
      toast.style.zIndex = '1000';
      document.body.appendChild(toast);
      setTimeout(() => toast.remove(), 3000);
    });
  </script>";
  unset($_SESSION['toast']);
}


// 🔐 Verificação de sessão
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
  setToast('Área de membros — faça seu login!', 'aviso');
  header('Location: home.php');
  exit();
}

$id_usuario_logado = $_SESSION['usuario_id'];

// ✅ Recupera valores de green/red
$valor_green = $_SESSION['valor_green'] ?? 0;
$valor_red   = $_SESSION['valor_red'] ?? 0;

// 🔎 Dados da sessão
// 🔎 Dados da sessão
$ultima_diaria         = $_SESSION['porcentagem_entrada'] ?? 0;
$soma_depositos        = 
    ($_SESSION['saldo_mentores'] ?? 0) + 
    ($_SESSION['saldo_geral'] ?? 0) - 
    ($_SESSION['saques_totais'] ?? 0);
$soma_saque            = $_SESSION['saques_totais'] ?? 0;
$saldo_mentores        = $_SESSION['saldo_mentores'] ?? 0;
$saldo_banca           = calcularSaldoBanca(); // ✅ usa função do funcoes.php
$valor_entrada_calculado = $_SESSION['resultado_entrada'] ?? 0;
$valor_entrada_formatado = number_format($valor_entrada_calculado, 2, ',', '.');


// 🔎 Verificação de banca zerada
if ($saldo_banca <= 0 && $saldo_mentores < 0) {
  $_SESSION['banca_zerada'] = true;
} elseif ($saldo_banca > 0) {
  unset($_SESSION['banca_zerada']);
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
  $saldo_banca_verificado = $_SESSION['saldo_geral'] ?? 0;

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

if (!isset($_SESSION['saldo_banca'])) {
    header('Location: carregar-sessao.php?atualizar=1');
    exit();
}

?>
<!-- FIM CODIGO RESPONSAVEL PELO GESTAO-DIARIA -->  












<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Gestão do Dia</title>

     
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">

     

     <link rel="stylesheet" href="css/estilo-gestao-diaria.css">
     <link rel="stylesheet" href="css/estilo-campo-mes.css">
     <link rel="stylesheet" href="css/estilo-painel-controle.css">
    


     <script src="js/script-gestao-diaria.js" defer></script>
     <script src="js/script-mes.js" defer></script>
     <script src="js/script-painel-controle.js" defer></script>


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




    


<!--  CODIGO RESPONSAVEL PELO MENU TOPO -->
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
      
      <a href="#" id="abrirGerenciaBanca">
         <i class="fas fa-wallet menu-icon"></i><span>Gerenciar Banca</span>
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
<!--  FIM CODIGO RESPONSAVEL PELO MENU TOPO -->




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

    // 🔄 Consulta única para mentores + valores
    $sql = "
      SELECT m.id, m.nome, m.foto,
             COALESCE(SUM(v.green), 0) AS total_green,
             COALESCE(SUM(v.red), 0) AS total_red,
             COALESCE(SUM(v.valor_green), 0) AS total_valor_green,
             COALESCE(SUM(v.valor_red), 0) AS total_valor_red
      FROM mentores m
      LEFT JOIN valor_mentores v ON m.id = v.id_mentores
      WHERE m.id_usuario = ?
      GROUP BY m.id, m.nome, m.foto
    ";

    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("i", $id_usuario_logado);
    $stmt->execute();
    $result = $stmt->get_result();

    $lista_mentores = [];
    $total_geral_saldo = 0;

    while ($mentor = $result->fetch_assoc()) {
      $total_subtraido = $mentor['total_valor_green'] - $mentor['total_valor_red'];
      $mentor['saldo'] = $total_subtraido;
      $lista_mentores[] = $mentor;
      $total_geral_saldo += $total_subtraido;
    }

    usort($lista_mentores, function($a, $b) {
      return $b['saldo'] <=> $a['saldo'];
    });

    foreach ($lista_mentores as $posicao => $mentor) {
      $rank = $posicao + 1;
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
              <div class='value-box-green green'><p>Green</p><p>{$mentor['total_green']}</p></div>
              <div class='value-box-red red'><p>Red</p><p>{$mentor['total_red']}</p></div>
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
 <div class="formulario-mentor" id="formulario-mentor">
  <button type="button" class="btn-fechar" id="botao-fechar">
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





<!-- RESPONSAVEL PELO CAMPO DO MÊS -->
<?php
$timezone_recebido = $_POST['timezone'] ?? 'America/Bahia';
date_default_timezone_set($timezone_recebido);

$meses_portugues = [
  "01" => "JANEIRO", "02" => "FEVEREIRO", "03" => "MARÇO",
  "04" => "ABRIL", "05" => "MAIO", "06" => "JUNHO",
  "07" => "JULHO", "08" => "AGOSTO", "09" => "SETEMBRO",
  "10" => "OUTUBRO", "11" => "NOVEMBRO", "12" => "DEZEMBRO"
];

$ano = date('Y');
$mes = date('m');
$hoje = date('Y-m-d');
$diasNoMes = cal_days_in_month(CAL_GREGORIAN, $mes, $ano);
$nomeMes = $meses_portugues[$mes];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $campos = ['id_mentores', 'green', 'red', 'valor_green', 'valor_red'];
  foreach ($campos as $campo) {
    if (!isset($_POST[$campo])) {
      $_SESSION['toast'] = ['mensagem' => "Erro: campo '$campo' não enviado.", 'tipo' => 'erro'];
      header('Location: gestao-diaria.php');
      exit();
    }
  }

  $id_mentores = intval($_POST['id_mentores']);
  $green = trim($_POST['green']);
  $red = trim($_POST['red']);
  $valor_green = floatval($_POST['valor_green']);
  $valor_red = floatval($_POST['valor_red']);
  $data_criacao = date('Y-m-d H:i:s');

  $stmt = $conexao->prepare("
    INSERT INTO valor_mentores (
      id_usuario, id_mentores, green, red, valor_green, valor_red, data_criacao
    ) VALUES (?, ?, ?, ?, ?, ?, ?)
  ");
  $stmt->bind_param("iiiddss", 
    $id_usuario_logado, $id_mentores, $green, $red, $valor_green, $valor_red, $data_criacao
  );

  if ($stmt->execute()) {
    $_SESSION['toast'] = ['mensagem' => 'Dados salvos com sucesso!', 'tipo' => 'sucesso'];
  } else {
    $_SESSION['toast'] = ['mensagem' => 'Erro ao salvar os dados!', 'tipo' => 'erro'];
  }

  $stmt->close();
  header('Location: gestao-diaria.php');
  exit();
}

ob_end_flush();
?>





<div class="container-resumos">
  <div class="resumo-mes">
    
    <!-- PEGAR O MES ATUAL E COLOCA NO TITULO -->
   <div class="bloco-meta-simples fixo-topo">

    

<?php

require_once 'config.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// 🔐 Verificação de sessão
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
  header('Location: home.php');
  exit();
}

$id_usuario_logado = $_SESSION['usuario_id'];

// 🔹 Dados da sessão
$valor_green     = $_SESSION['valor_green'] ?? 0;
$valor_red       = $_SESSION['valor_red'] ?? 0;
$saldo_mentores  = $_SESSION['saldo_mentores'] ?? 0;
$saldo_geral     = $_SESSION['saldo_geral'] ?? 0;
$saques_totais   = $_SESSION['saques_totais'] ?? 0;
$soma_depositos  = $saldo_mentores + $saldo_geral - $saques_totais;
$ultima_diaria   = $_SESSION['porcentagem_entrada'] ?? 0;


// 🔹 Cálculo da meta mensal
$hoje = new DateTime();
$ano = (int)$hoje->format('Y');
$mes = (int)$hoje->format('m');
$dias_mes = cal_days_in_month(CAL_GREGORIAN, $mes, $ano);

$meta_mensal = ($soma_depositos * ($ultima_diaria / 100)) * ($dias_mes / 2);
$saldo_mentores = $valor_green - $valor_red;

$porcentagem_meta = $meta_mensal > 0 ? ($saldo_mentores / $meta_mensal) * 100 : 0;
$porcentagem_meta_arredondada = round($porcentagem_meta, 1);
$meta_batida = $saldo_mentores >= $meta_mensal;

$meta_mensal_formatada = 'R$ ' . number_format($meta_mensal, 2, ',', '.');
$saldo_mes_formatado = 'R$ ' . number_format($saldo_mentores, 2, ',', '.');

// 🔹 Consulta única para todos os dias do mês
$sql = "
  SELECT 
    DATE(data_criacao) AS data,
    SUM(CASE WHEN green = 1 THEN valor_green ELSE 0 END) AS total_valor_green,
    SUM(CASE WHEN red = 1 THEN valor_red ELSE 0 END) AS total_valor_red,
    COUNT(CASE WHEN green = 1 THEN 1 END) AS total_green,
    COUNT(CASE WHEN red = 1 THEN 1 END) AS total_red
  FROM valor_mentores
  WHERE id_usuario = ? AND MONTH(data_criacao) = ? AND YEAR(data_criacao) = ?
  GROUP BY DATE(data_criacao)
";

$stmt = $conexao->prepare($sql);
$stmt->bind_param("iii", $id_usuario_logado, $mes, $ano);
$stmt->execute();
$result = $stmt->get_result();

$dados_por_dia = [];
while ($row = $result->fetch_assoc()) {
  $dados_por_dia[$row['data']] = $row;
}
?>

<div class="container-resumos">
  <div class="resumo-mes">
    <div class="bloco-meta-simples fixo-topo">

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
        for ($dia = 1; $dia <= $dias_mes; $dia++) {
          $data_mysql = $ano . '-' . str_pad($mes, 2, "0", STR_PAD_LEFT) . '-' . str_pad($dia, 2, "0", STR_PAD_LEFT);
          $data_exibicao = str_pad($dia, 2, "0", STR_PAD_LEFT) . "/" . str_pad($mes, 2, "0", STR_PAD_LEFT) . "/" . $ano;

          $dados = $dados_por_dia[$data_mysql] ?? [
            'total_valor_green' => 0,
            'total_valor_red' => 0,
            'total_green' => 0,
            'total_red' => 0
          ];

          $saldo_dia = $dados['total_valor_green'] - $dados['total_valor_red'];
          $saldo_formatado = number_format($saldo_dia, 2, ',', '.');

          $cor_valor = ($saldo_dia == 0) ? 'texto-cinza' : ($saldo_dia > 0 ? 'verde-bold' : 'vermelho-bold');
          $classe_texto = ($saldo_dia == 0) ? 'texto-cinza' : '';
          $placar_cinza = ((int)$dados['total_green'] === 0 && (int)$dados['total_red'] === 0) ? 'texto-cinza' : '';

          $classe_dia = ($data_mysql === $hoje->format('Y-m-d'))
            ? 'dia-hoje ' . ($saldo_dia >= 0 ? 'borda-verde' : 'borda-vermelha')
            : 'dia-normal';

          $classe_destaque = ($data_mysql < $hoje->format('Y-m-d') && $saldo_dia > 0) ? 'dia-destaque' : '';

          echo '
            <div class="linha-dia '.$classe_dia.' '.$classe_destaque.'">
              <span class="data '.$classe_texto.'"><i class="fas fa-calendar-day"></i> '.$data_exibicao.'</span>
              <div class="placar-dia">
                <span class="placar verde-bold '.$placar_cinza.'">'.(int)$dados['total_green'].'</span>
                <span class="placar separador '.$placar_cinza.'">x</span>
                <span class="placar vermelho-bold '.$placar_cinza.'">'.(int)$dados['total_red'].'</span>
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
 </div>

 <script>
  document.getElementById('timezone').value =
    Intl.DateTimeFormat().resolvedOptions().timeZone;
 </script>

  </div>
</div>
<!-- FIM RESPONSAVEL PELO CAMPO DO MÊS -->





<!-- CODIGO RESPONSALVEL PELO PAINEL-CONTROLE -->



<div id="toast-msg" class="toast hidden">Saque excluído com sucesso!</div>
<div id="toast-msg" class="toast hidden">Mensagem</div>


<br>



<!-- MODAL DE DEPÓSITO -->

<div class="modal-gerencia-banca">
  <div id="modalDeposito" class="modal-overlay">
    <div class="modal-content">
      <form method="POST" action="">
        <!-- ID de controle -->
        <input type="hidden" name="controle_id" value="<?= isset($controle_id) ? $controle_id : '' ?>">

        <!-- Botão de fechar -->
        <button type="button" class="btn-fechar" id="fecharModal">×</button>

        <!-- Banca e Lucro -->
        <div class="linha-banca-lucro">
          <div class="campo-banca">
            <div class="conteudo">
              <label><i class="fa-solid fa-coins"></i> Banca</label>
              <span id="valorBancaLabel">R$ 0,00</span>
            </div>
          </div>
          <div class="campo-lucro">
            <div class="conteudo">
              <label id="lucroLabel"><i class="fa-solid fa-money-bill-trend-up"></i> Lucro</label>
              <span id="valorLucroLabel">R$ 0,00</span>
            </div>
          </div>
        </div>

        <!-- Ação da banca -->
        <div class="custom-dropdown">
          <button class="dropdown-toggle" type="button">
            <i class="fa-solid fa-hand-pointer"></i> Selecione Uma Opção
            <i class="fa-solid fa-chevron-down"></i>
          </button>
          <ul class="dropdown-menu">
            <li data-value="add"><i class="fa-solid fa-money-bill-wave"></i> Depositar</li>
            <li data-value="sacar"><i class="fa-solid fa-money-bill-transfer"></i> Sacar</li>
            <li data-value="alterar"><i class="fa-solid fa-pen-to-square"></i> Alterar Dados</li>
            <li data-value="resetar"><i class="fa-solid fa-trash-can"></i> Resetar Banca</li>
          </ul>
          <input type="hidden" name="acaoBanca" id="acaoBanca">
        </div>

        <!-- Valor da banca -->
        <div class="custom-inputbox">
          <label for="valorBanca"><i class="fa-solid fa-wallet"></i> Adicionar Valor</label>
          <div class="input-wrapper banca-wrapper">
            <input type="text" id="valorBanca" name="valorBanca" placeholder="R$ 0,00">
          </div>
        </div>

        <!-- Porcentagem -->
        <div class="custom-inputbox">
          <label for="porcentagem"><i class="fa-solid fa-chart-pie"></i> Porcentagem</label>
          <div class="input-wrapper porc-wrapper">
            <input
              type="text"
              name="diaria"
              id="porcentagem"
              value="<?= isset($valor_diaria) ? number_format($valor_diaria, 2, ',', '.') : '2,00' ?>"
            >
            <span id="resultadoCalculo"></span>
          </div>
        </div>

        <!-- Unidade -->
        <div class="custom-inputbox">
          <label for="unidadeMeta"><i class="fa-solid fa-bullseye"></i> Qtd de Unidade</label>
          <div class="input-wrapper unidade-wrapper">
            <input
              type="text"
              name="unidade"
              id="unidadeMeta"
              value="<?= isset($valor_unidade) ? intval($valor_unidade) : '2' ?>"
            >
            <span id="resultadoUnidade"></span>
          </div>
        </div>

        <!-- Odds -->
        <div class="custom-inputbox">
          <label for="oddsMeta"><i class="fa-solid fa-percent"></i> Odds Min..</label>
          <div class="input-wrapper odds-wrapper">
            <input
              type="text"
              name="odds"
              id="oddsMeta"
              value="<?= isset($valor_odds) ? number_format(floatval($valor_odds), 2, ',', '') : '1,50' ?>"
            >
            <span id="resultadoOdds"></span>
          </div>
        </div>

        <!-- ✅ Toast discreto dentro do modal -->
        <div id="toastModal" style="margin-top: 10px;"></div>

        <!-- Botão de ação -->
        <input type="button" id="botaoAcao" value="Cadastrar Dados" class="custom-button">
      </form>
    </div>
  </div>
</div>



<!-- FIM  CODIGO RESPONSALVEL PELO PAINEL-CONTROLE -->









<script>
 function toggleMenu() {
  var menu = document.getElementById("menu");
  menu.style.display = menu.style.display === "block" ? "none" : "block";
 }
</script>

<script>
  const toggle = document.querySelector('.dropdown-toggle');
  const menu = document.querySelector('.dropdown-menu');
  const hiddenInput = document.getElementById('acaoBanca');

  toggle.addEventListener('click', () => {
    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
  });

  menu.querySelectorAll('li').forEach(item => {
    item.addEventListener('click', () => {
      toggle.innerHTML = item.innerHTML + '<i class="fa-solid fa-chevron-down"></i>';
      hiddenInput.value = item.dataset.value;
      menu.style.display = 'none';
    });
  });

  document.addEventListener('click', (e) => {
    if (!e.target.closest('.custom-dropdown')) {
      menu.style.display = 'none';
    }
  });
</script>



<div id="mensagem-status" class="toast"></div>
<!-- DEIXA TOAST OCULTO -->
<div id="toast" class="toast hidden"></div>

</body>
</html>
