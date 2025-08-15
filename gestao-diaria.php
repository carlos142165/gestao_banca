<?php
ob_start();
require_once 'config.php';
require_once 'carregar_sessao.php';
require_once 'funcoes.php'; // ✅ Inclui a função de cálculo

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// 🔐 Verificação de sessão
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
  setToast('Área de membros — faça seu login!', 'aviso');
  header('Location: home.php');
  exit();
}

$id_usuario_logado = $_SESSION['usuario_id'];

// ✅ Recupera valores de green/red com verificação de sessão
$valor_green = isset($_SESSION['valor_green']) ? floatval($_SESSION['valor_green']) : 0;
$valor_red   = isset($_SESSION['valor_red']) ? floatval($_SESSION['valor_red']) : 0;

// 🔎 Dados da sessão com verificações
$ultima_diaria         = isset($_SESSION['porcentagem_entrada']) ? floatval($_SESSION['porcentagem_entrada']) : 0;
$soma_depositos        = 
    (isset($_SESSION['saldo_mentores']) ? floatval($_SESSION['saldo_mentores']) : 0) + 
    (isset($_SESSION['saldo_geral']) ? floatval($_SESSION['saldo_geral']) : 0) - 
    (isset($_SESSION['saques_totais']) ? floatval($_SESSION['saques_totais']) : 0);
$soma_saque            = isset($_SESSION['saques_totais']) ? floatval($_SESSION['saques_totais']) : 0;
$saldo_mentores        = isset($_SESSION['saldo_mentores']) ? floatval($_SESSION['saldo_mentores']) : 0;
$saldo_banca           = calcularSaldoBanca(); // ✅ usa função do funcoes.php
$valor_entrada_calculado = isset($_SESSION['resultado_entrada']) ? floatval($_SESSION['resultado_entrada']) : 0;
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
  
  if ($id > 0) {
    try {
      $stmt = $conexao->prepare("DELETE FROM mentores WHERE id = ? AND id_usuario = ?");
      $stmt->bind_param("ii", $id, $id_usuario_logado);
      $stmt->execute();
      
      if ($stmt->affected_rows > 0) {
        setToast('Mentor excluído com sucesso!', 'sucesso');
      } else {
        setToast('Mentor não encontrado ou não autorizado!', 'erro');
      }
    } catch (Exception $e) {
      setToast('Erro ao excluir mentor!', 'erro');
    }
  }
  
  header('Location: gestao-diaria.php');
  exit();
}

// 📝 Cadastro/Edição de mentor (SEPARADO do cadastro de valores)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && 
    ($_POST['acao'] === 'cadastrar_mentor' || $_POST['acao'] === 'editar_mentor')) {
  
  $usuario_id = $_SESSION['usuario_id'];
  $nome = trim($_POST['nome'] ?? '');
  $mentor_id = isset($_POST['mentor_id']) ? intval($_POST['mentor_id']) : null;

  // Validação do nome
  if (empty($nome)) {
    setToast('Nome do mentor é obrigatório!', 'erro');
    header('Location: gestao-diaria.php');
    exit();
  }

  $foto_nome = isset($_POST['foto_atual']) ? $_POST['foto_atual'] : 'avatar-padrao.png';

  // Upload de foto
  if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $extensao = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
    
    // Verificar se é uma imagem válida
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($extensao, $allowed_types)) {
      setToast('Formato de imagem inválido! Use JPG, JPEG, PNG ou GIF.', 'erro');
      header('Location: gestao-diaria.php');
      exit();
    }
    
    // Verificar tamanho do arquivo (max 5MB)
    if ($_FILES['foto']['size'] > 5 * 1024 * 1024) {
      setToast('Arquivo muito grande! Máximo 5MB.', 'erro');
      header('Location: gestao-diaria.php');
      exit();
    }
    
    $foto_nome = uniqid() . '.' . $extensao;
    
    // Criar diretório se não existir
    if (!is_dir('uploads')) {
      mkdir('uploads', 0755, true);
    }
    
    if (!move_uploaded_file($_FILES['foto']['tmp_name'], "uploads/$foto_nome")) {
      setToast('Erro ao fazer upload da foto!', 'erro');
      header('Location: gestao-diaria.php');
      exit();
    }
  }

  try {
    // Executa ação específica
    if ($_POST['acao'] === 'cadastrar_mentor') {
      $stmt = $conexao->prepare("INSERT INTO mentores (id_usuario, foto, nome, data_criacao) VALUES (?, ?, ?, NOW())");
      $stmt->bind_param("iss", $usuario_id, $foto_nome, $nome);
      $mensagem_sucesso = 'Mentor cadastrado com sucesso!';
    } 
    elseif ($_POST['acao'] === 'editar_mentor' && $mentor_id > 0) {
      $stmt = $conexao->prepare("UPDATE mentores SET nome = ?, foto = ? WHERE id = ? AND id_usuario = ?");
      $stmt->bind_param("ssii", $nome, $foto_nome, $mentor_id, $usuario_id);
      $mensagem_sucesso = 'Mentor atualizado com sucesso!';
    }

    if (isset($stmt) && $stmt->execute()) {
      setToast($mensagem_sucesso, 'sucesso');
    } else {
      setToast('Erro ao salvar mentor!', 'erro');
    }
  } catch (Exception $e) {
    setToast('Erro no banco de dados!', 'erro');
  }

  header('Location: gestao-diaria.php');
  exit();
}

// 🔎 Meta formatada
$meta_diaria = isset($_SESSION['meta_meia_unidade']) ? floatval($_SESSION['meta_meia_unidade']) : 0;

if (!isset($_SESSION['saldo_banca'])) {
  header('Location: carregar-sessao.php?atualizar=1');
  exit();
}

// 📅 Configuração de data para o campo do mês
$timezone_recebido = isset($_POST['timezone']) ? $_POST['timezone'] : 'America/Bahia';
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
$diasNoMes = cal_days_in_month(CAL_GREGORIAN, intval($mes), intval($ano));
$nomeMes = $meses_portugues[$mes];

// 📊 Processamento de dados dos mentores para o campo do mês
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_mentores'])) {
  $campos = ['id_mentores', 'green', 'red', 'valor_green', 'valor_red'];
  foreach ($campos as $campo) {
    if (!isset($_POST[$campo])) {
      setToast("Erro: campo '$campo' não enviado.", 'erro');
      header('Location: gestao-diaria.php');
      exit();
    }
  }

  $id_mentores = intval($_POST['id_mentores']);
  $green = intval($_POST['green']);
  $red = intval($_POST['red']);
  $valor_green = floatval(str_replace(',', '.', str_replace('.', '', $_POST['valor_green'])));
  $valor_red = floatval(str_replace(',', '.', str_replace('.', '', $_POST['valor_red'])));
  $data_criacao = date('Y-m-d H:i:s');

  // Validação básica
  if ($id_mentores <= 0) {
    setToast('Mentor inválido!', 'erro');
    header('Location: gestao-diaria.php');
    exit();
  }

  if ($valor_green < 0 || $valor_red < 0) {
    setToast('Valores não podem ser negativos!', 'erro');
    header('Location: gestao-diaria.php');
    exit();
  }

  try {
    $stmt = $conexao->prepare("
      INSERT INTO valor_mentores (
        id_usuario, id_mentores, green, red, valor_green, valor_red, data_criacao
      ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iiiddss", 
      $id_usuario_logado, $id_mentores, $green, $red, $valor_green, $valor_red, $data_criacao
    );

    if ($stmt->execute()) {
      setToast('Dados salvos com sucesso!', 'sucesso');
    } else {
      setToast('Erro ao salvar os dados!', 'erro');
    }
  } catch (Exception $e) {
    setToast('Erro no banco de dados!', 'erro');
  }

  header('Location: gestao-diaria.php');
  exit();
}

// 📊 Consulta para dados do campo do mês
try {
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
} catch (Exception $e) {
  $dados_por_dia = [];
  setToast('Erro ao carregar dados do mês!', 'erro');
}

// 🔹 Cálculo da meta mensal
$meta_mensal = ($soma_depositos * ($ultima_diaria / 100)) * ($diasNoMes / 2);
$saldo_mentores_atual = $valor_green - $valor_red;

$porcentagem_meta = $meta_mensal > 0 ? ($saldo_mentores_atual / $meta_mensal) * 100 : 0;
$porcentagem_meta_arredondada = round($porcentagem_meta, 1);
$meta_batida = $saldo_mentores_atual >= $meta_mensal;

$meta_mensal_formatada = 'R$ ' . number_format($meta_mensal, 2, ',', '.');
$saldo_mes_formatado = 'R$ ' . number_format($saldo_mentores_atual, 2, ',', '.');

ob_end_flush();
?>










<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Gestão do Dia</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<!-- CSS ORGANIZADOS POR FUNCIONALIDADE -->
<link rel="stylesheet" href="css/menu-topo.css">
<link rel="stylesheet" href="css/modais.css">
<link rel="stylesheet" href="css/estilo-gestao-diaria-novo.css">
<link rel="stylesheet" href="css/estilo-campo-mes.css">
<link rel="stylesheet" href="css/estilo-painel-controle.css">
<link rel="stylesheet" href="css/toast.css">
<link rel="stylesheet" href="css/toast-modal-gerencia.css">

<!-- SCRIPTS ORGANIZADOS POR FUNCIONALIDADE -->
<script src="js/script-gestao-diaria.js" defer></script>
<script src="js/script-painel-controle.js" defer></script>
<script src="js/script-mes.js" defer></script>
</head>

<body>



<!-- ============================================================================= -->
<!-- 🔝 SEÇÃO: MENU SUPERIOR DE NAVEGAÇÃO                                         -->
<!-- Função: Navegação principal da aplicação + Saldo da banca                   -->
<!-- ============================================================================= -->
<div class="menu-topo-container">
  <div id="top-bar"> 
    <div class="menu-container">
      <!-- Botão hambúrguer para menu mobile -->
      <button class="menu-button" onclick="toggleMenu()">☰</button>

      <!-- Menu dropdown de navegação -->
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

      <!-- Área do saldo da banca (canto direito) -->
      <div id="lista-mentores">
        <div class="valor-item-menu saldo-topo-ajustado">
          <div class="valor-info-wrapper">
            <!-- Valor total da banca -->
            <div class="valor-label-linha">
              <i class="fa-solid fa-building-columns valor-icone-tema"></i>
              <span class="valor-label">Banca:</span>
              <span class="valor-bold-menu" id="valorTotalBancaLabel">R$ 0,00</span>
            </div>
            <!-- Lucro dos mentores -->
            <div class="valor-label-linha">
              <i class="fa-solid fa-money-bill-trend-up valor-icone-tema"></i>
              <span class="valor-label" id="lucro_entradas_rotulo">Lucro:</span>
              <span class="valor-bold-menu" id="lucro_valor_entrada">R$ 0,00</span>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- ============================================================================= -->
<!-- 📋 SEÇÃO: CONTAINER PRINCIPAL DE RESUMOS                                     -->
<!-- Função: Área principal com dados do dia e do mês                            -->
<!-- ============================================================================= -->
<div class="container-resumos">

   <div class="resumo-dia">

    <!-- Área do placar Green x Red -->


    <!-- Informações de meta e saldo do dia -->
<div class="informacoes-row">



<!-- ========================================
     WIDGET META COM DATA INTEGRADA
     ======================================== -->

<!-- ========================================
     WIDGET META COM DATA INTEGRADA
     ======================================== -->

<!-- Container da Data Elegante -->
<div class="widget-meta-container">
    <div class="widget-meta-row">
        <div class="widget-meta-item" id="widget-meta">
            
            <!-- ========================================
                 HEADER COM DATA INTEGRADA NO TOPO
                 ======================================== -->
            <div class="data-header-integrada" id="data-header">
                <i class="fas fa-calendar-day"></i>
                
                <div class="data-texto-compacto">
                    <span class="data-prefixo">Hoje:</span>
                    <span class="data-principal-integrada" id="data-atual">Carregando...</span>
                </div>
                
                <div class="data-separador-mini"></div>
                
                <div class="status-periodo-mini" id="status-periodo">
                    <div class="status-periodo-icone"></div>
                    <div class="status-periodo-texto">ATIVO</div>
                </div>
            </div>

            <!-- ========================================
                 CONTEÚDO PRINCIPAL DO WIDGET
                 ======================================== -->
            <div class="widget-conteudo-principal">
                <div class="widget-meta-valor" id="meta-valor">
                    <i class="fas fa-coins"></i>
                    <span class="valor-texto">
                        <span class="loading-text">Calculando...</span>
                    </span>
                </div>
                
                <!-- Exibição do valor que ultrapassou a meta -->
                <div class="valor-ultrapassou" id="valor-ultrapassou" style="display: none;">
                    <i class="fas fa-trophy"></i>
                    <span class="texto-ultrapassou">Lucro Extra: <span id="valor-extra">R$ 0,00</span></span>
                </div>
                
                <div class="widget-meta-rotulo" id="rotulo-meta">Meta do Dia</div>
                
                <div class="widget-barra-container">
                    <div class="widget-barra-progresso" id="barra-progresso"></div>
                </div>
                
                <div class="widget-info-progresso">
                    <span id="saldo-info">
                        <i class="fas fa-wallet"></i>
                        Saldo: R$ 0,00
                    </span>
                    <span id="percentual-info">
                        <i class="fas fa-percentage"></i>
                        0%
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

    <!-- Área central com botão de adicionar mentor -->
    <div class="area-central-botao">
      <span class="valor-porcentagem" id="valor-porcentagem">
        R$ <?php echo isset($meta_formatado) ? $meta_formatado : '0,00'; ?>
      </span>
      <span class="rotulo-porcentagem">da Banca Fazer</span>

      <span class="rotulo-entrada">Entrada de:</span>
      <span class="valor-entrada" id="valor-entrada">
        R$ <?php echo isset($resultado_formatado) ? $resultado_formatado : '0,00'; ?>
      </span>


    </div>

    <!-- Lista dinâmica de mentores -->
    <div class="campo_mentores">

    <div class="container-valores">
      <div class="pontuacao">
        <span class="placar-green">0</span>
        <span class="separador">x</span>
        <span class="placar-red">0</span>
      </div>
            <button class="btn-add-usuario" onclick="prepararFormularioNovoMentor()">
        <i class="fas fa-user-plus"></i>
      </button>
    </div>      




      <div id="listaMentores" class="mentor-wrapper">
        <?php
        try {
          // Consulta para buscar mentores e seus valores
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
            ORDER BY (COALESCE(SUM(v.valor_green), 0) - COALESCE(SUM(v.valor_red), 0)) DESC
          ";

          $stmt = $conexao->prepare($sql);
          $stmt->bind_param("i", $id_usuario_logado);
          $stmt->execute();
          $result = $stmt->get_result();

          $lista_mentores = [];
          $total_geral_saldo = 0;

          while ($mentor = $result->fetch_assoc()) {
            $total_subtraido = floatval($mentor['total_valor_green']) - floatval($mentor['total_valor_red']);
            $mentor['saldo'] = $total_subtraido;
            $lista_mentores[] = $mentor;
            $total_geral_saldo += $total_subtraido;
          }

          foreach ($lista_mentores as $posicao => $mentor) {
            $rank = $posicao + 1;
            $saldo_formatado = number_format($mentor['saldo'], 2, ',', '.');
            $nome_seguro = htmlspecialchars($mentor['nome']);
            
            // Verificação da foto do mentor
            $foto_original = $mentor['foto'];
            if (empty($foto_original) || $foto_original === 'avatar-padrao.png') {
              $foto_path = 'https://cdn-icons-png.flaticon.com/512/847/847969.png';
            } else {
              $foto_path = 'uploads/' . htmlspecialchars($foto_original);
              if (!file_exists($foto_path)) {
                $foto_path = 'https://cdn-icons-png.flaticon.com/512/847/847969.png';
              }
            }

            // Determina a cor da borda baseada no saldo
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
                   data-nome='{$nome_seguro}'
                   data-foto='{$foto_path}'
                   data-id='{$mentor['id']}'>
                <div class='mentor-header'>
                  <img src='{$foto_path}' alt='Foto de {$nome_seguro}' class='mentor-img' 
                       onerror=\"this.src='https://cdn-icons-png.flaticon.com/512/847/847969.png'\" />
                  <h3 class='mentor-nome'>{$nome_seguro}</h3>
                </div>
                <div class='mentor-right'>
                  <div class='mentor-values-inline'>
                    <div class='value-box-green green'>
                      <p>Green</p>
                      <p>{$mentor['total_green']}</p>
                    </div>
                    <div class='value-box-red red'>
                      <p>Red</p>
                      <p>{$mentor['total_red']}</p>
                    </div>
                    <div class='value-box-saldo saldo'>
                      <p>Saldo</p>
                      <p>R$ {$saldo_formatado}</p>
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

          // Elementos auxiliares para cálculos JavaScript
          echo "
          <div id='total-green-dia' data-green='" . array_sum(array_column($lista_mentores, 'total_green')) . "' style='display:none;'></div>
          <div id='total-red-dia' data-red='" . array_sum(array_column($lista_mentores, 'total_red')) . "' style='display:none;'></div>
          <div id='saldo-dia' data-total='" . number_format($total_geral_saldo, 2, ',', '.') . "' style='display:none;'></div>
          ";
          
        } catch (Exception $e) {
          echo "<div class='erro-mentores'>Erro ao carregar mentores!</div>";
          error_log("Erro ao carregar mentores: " . $e->getMessage());
        }
        ?>
      </div>
    </div>
  </div>

  <!-- ============================================================================= -->
  <!-- 📅 SUB-SEÇÃO: RESUMO DO MÊS                                                  -->
  <!-- Função: Estatísticas mensais e calendário de resultados                     -->
  <!-- ============================================================================= -->
  <div class="resumo-mes">
    <!-- Cabeçalho fixo com metas mensais -->
    <div class="bloco-meta-simples fixo-topo">

      <!-- Título do mês atual -->
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


      <!-- Meta mensal (100%) -->
      <div class="grupo-barra">
        <span class="valor-meta"><i class="fas fa-bullseye"></i> <?php echo $meta_mensal_formatada; ?></span>
        <div class="container-barra-horizontal">
          <div class="progresso-dourado"></div>
          <span class="porcento-barra">100%</span>
        </div>
        <span class="rotulo-meta-mes"><i class="fas fa-calendar-day"></i> Meta do Mês</span>
      </div>

      <!-- Progresso atual da meta -->
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
    </div>

    <!-- Lista de dias do mês com resultados -->
    <div class="lista-dias">
      <?php
      for ($dia = 1; $dia <= $diasNoMes; $dia++) {
        $data_mysql = $ano . '-' . str_pad($mes, 2, "0", STR_PAD_LEFT) . '-' . str_pad($dia, 2, "0", STR_PAD_LEFT);
        $data_exibicao = str_pad($dia, 2, "0", STR_PAD_LEFT) . "/" . str_pad($mes, 2, "0", STR_PAD_LEFT) . "/" . $ano;

        $dados = $dados_por_dia[$data_mysql] ?? [
          'total_valor_green' => 0,
          'total_valor_red' => 0,
          'total_green' => 0,
          'total_red' => 0
        ];

        $saldo_dia = floatval($dados['total_valor_green']) - floatval($dados['total_valor_red']);
        $saldo_formatado = number_format($saldo_dia, 2, ',', '.');

        $cor_valor = ($saldo_dia == 0) ? 'texto-cinza' : ($saldo_dia > 0 ? 'verde-bold' : 'vermelho-bold');
        $classe_texto = ($saldo_dia == 0) ? 'texto-cinza' : '';
        $placar_cinza = ((int)$dados['total_green'] === 0 && (int)$dados['total_red'] === 0) ? 'texto-cinza' : '';

        $classe_dia = ($data_mysql === $hoje)
          ? 'dia-hoje ' . ($saldo_dia >= 0 ? 'borda-verde' : 'borda-vermelha')
          : 'dia-normal';

        $classe_destaque = ($data_mysql < $hoje && $saldo_dia > 0) ? 'dia-destaque' : '';

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
<!-- FIM CONTAINER RESUMOS -->

<!-- ============================================================================= -->
<!-- 🎯 SEÇÃO: MODAIS E FORMULÁRIOS                                               -->
<!-- Função: Todos os popups, formulários e janelas modais da aplicação          -->
<!-- ============================================================================= -->
<div class="modais-container">

  <!-- ============================================================================= -->
  <!-- 📝 SUB-SEÇÃO: MODAL DE CADASTRO/EDIÇÃO DE MENTOR                             -->
  <!-- Função: Formulário para criar ou editar dados do mentor (nome + foto)       -->
  <!-- ============================================================================= -->
  <div id="modal-form" class="modal">
    <div class="modal-conteudo">
      <span class="fechar" onclick="fecharModal()">&times;</span>

      <form method="POST" enctype="multipart/form-data" action="gestao-diaria.php" class="formulario-mentor-completo">
        <input type="hidden" name="acao" id="acao-form" value="cadastrar_mentor">
        <input type="hidden" name="mentor_id" id="mentor-id" value="">
        <input type="hidden" name="foto_atual" id="foto-atual" value="avatar-padrao.png">

        <!-- Upload de foto -->
        <div class="input-group">
          <label for="foto" class="label-form"></label>
          <label for="foto" class="label-arquivo">
            <i class="fas fa-image"></i> Selecionar Foto
          </label>
          <input type="file" name="foto" id="foto" class="input-file" accept="image/*" onchange="mostrarNomeArquivo(this)" hidden>
          <span id="nome-arquivo" class="nome-arquivo"></span>
        </div>

        <!-- Pré-visualização da imagem -->
        <div class="preview-foto-wrapper">
          <img id="preview-img" src="https://cdn-icons-png.flaticon.com/512/847/847969.png" class="preview-img" alt="Pré-visualização">
          <button type="button" id="remover-foto" class="btn-remover-foto" onclick="removerImagem()" style="display:none;">Remover Foto</button>
        </div>

        <!-- Nome do mentor -->
        <h3 class="mentor-nome-preview" style="text-align: center; margin-top: 14px;"></h3>

        <!-- Campo de entrada do nome -->
        <div class="input-group">
          <label for="nome" class="label-form"></label>
          <input type="text" name="nome" id="nome" class="input-text" placeholder="Nome do Mentor" required maxlength="100">
        </div>

        <!-- Botões de ação -->
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

  <!-- ============================================================================= -->
  <!-- 💰 SUB-SEÇÃO: FORMULÁRIO DE VALORES (GREEN/RED)                              -->
  <!-- Função: Adicionar entrada de green ou red para um mentor específico         -->
  <!-- ============================================================================= -->
  <div class="formulario-mentor" id="formulario-mentor">
    <button type="button" class="btn-fechar" id="botao-fechar">
      <i class="fas fa-times"></i>
    </button>

    <!-- Info do mentor selecionado -->
    <img src="" class="mentor-foto-preview" width="100" />
    <h3 class="mentor-nome-preview">Nome do Mentor</h3>

    <form id="form-mentor" method="POST">
      <input type="hidden" name="id_mentor" class="mentor-id-hidden">

      <!-- Seleção Green ou Red -->
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

      <!-- Campo de valor -->
      <input type="text" name="valor" id="valor" class="input-valor" placeholder="R$ 0,00" required>

      <button type="submit" class="botao-enviar">Enviar</button>
    </form>
  </div>

  <!-- ============================================================================= -->
  <!-- 📋 SUB-SEÇÃO: TELA DE HISTÓRICO DO MENTOR                                    -->
  <!-- Função: Exibir histórico de entradas e permitir exclusões                   -->
  <!-- ============================================================================= -->
  <div id="tela-edicao" class="tela-edicao" style="display:none;">
    <button type="button" class="btn-fechar" onclick="fecharTelaEdicao()">
      <i class="fas fa-times"></i>
    </button>

    <!-- Info do mentor -->
    <img id="fotoMentorEdicao" class="mentor-img-edicao" />
    <h3>Histórico do Mentor - <span id="nomeMentorEdicao"></span></h3>

    <p class="mentor-data-horario">
      <strong>Horário:</strong> <span id="horarioMentorEdicao">Carregando...</span>
    </p>

    <!-- Lista de entradas do mentor -->
    <div id="resultado-filtro"></div>
  </div>

  <!-- ============================================================================= -->
  <!-- ❓ SUB-SEÇÃO: MODAL DE CONFIRMAÇÃO - EXCLUSÃO DE ENTRADA                     -->
  <!-- Função: Confirmar exclusão de uma entrada específica                        -->
  <!-- ============================================================================= -->
  <div id="modal-confirmacao" class="modal-confirmacao" style="display:none;">
    <div class="modal-content">
      <p class="modal-texto">Tem certeza que deseja excluir esta entrada?</p>
      <div class="botoes-modal">
        <button id="btnConfirmar" class="botao-confirmar">Sim, excluir</button>
        <button id="btnCancelar" class="botao-cancelar">Cancelar</button>
      </div>
    </div>
  </div>

  <!-- ============================================================================= -->
  <!-- ❓ SUB-SEÇÃO: MODAL DE CONFIRMAÇÃO - EXCLUSÃO DE MENTOR                      -->
  <!-- Função: Confirmar exclusão completa de um mentor                            -->
  <!-- ============================================================================= -->
  <div id="modal-confirmacao-exclusao" style="display:none;">
    <div class="modal-content">
      <p class="modal-texto">Tem certeza que deseja excluir este mentor?</p>
      <div class="botoes-modal">
        <button class="botao-confirmar" onclick="confirmarExclusaoMentor()">Sim, excluir</button>
        <button class="botao-cancelar" onclick="fecharModalExclusao()">Cancelar</button>
      </div>
    </div>
  </div>

  <!-- ============================================================================= -->
  <!-- 🍞 SUB-SEÇÃO: MENSAGENS TOAST                                                -->
  <!-- Função: Mensagens temporárias de feedback                                   -->
  <!-- ============================================================================= -->
  <div id="mensagem-status" class="toast"></div>
  <div id="toast" class="toast hidden"></div>

</div>
<!-- FIM MODAIS CONTAINER -->

<!-- ============================================================================= -->
<!-- 💼 SEÇÃO: MODAL DE GERENCIAMENTO DE BANCA                                     -->
<!-- Função: Painel para gerenciar valores da banca (depósitos, saques, etc.)    -->
<!-- ============================================================================= -->
<div class="modal-gerencia-banca">
  <div id="modalDeposito" class="modal-overlay">

    <div class="modal-content">
      <!-- Botão de fechar -->
      <button type="button" class="btn-fechar" id="fecharModal">×</button>
      <form method="POST" action="">
        <input type="hidden" name="controle_id" value="<?= isset($controle_id) ? htmlspecialchars($controle_id) : '' ?>">



        <!-- Informações da banca e lucro -->
        <div class="linha-banca-lucro">
          <div class="campo-banca">
            <div class="conteudo">
              <label><i class="fa-solid fa-coins"></i> Banca</label>
              <span id="valorBancaLabel">R$ 0,00</span>
            </div>
          </div>
          <div class="campo-lucro">
            <div class="conteudo">
              <label class="label-lucro">
                <i class="fa-solid fa-money-bill-trend-up" id="iconeLucro"></i>
                <span id="lucroLabel" class="lucro-label-texto">Lucro</span>
              </label>
              <span id="valorLucroLabel">R$ 0,00</span>
            </div>
          </div>
        </div>

        <!-- Dropdown de ações -->
       <div class="custom-campo-opcos">  
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

       
        <div class="custom-inputbox">          
          <div class="input-wrapper banca-wrapper">
            <input type="text" id="valorBanca" name="valorBanca" placeholder="R$ 0,00">
          </div>
        </div>
      </div>

      <div class="custom-campo"> 
        <div class="custom-inputbox">
          <label for="porcentagem"><i class="fa-solid fa-chart-pie"></i> Porcentagem</label>
          <div class="input-wrapper porc-wrapper">
            <input type="text" name="diaria" id="porcentagem" value="<?= isset($valor_diaria) ? number_format(floatval($valor_diaria), 2, ',', '.') : '2,00' ?>">
            <span id="resultadoCalculo"></span>
          </div>
        </div>

        <div class="custom-inputbox">
          <label for="unidadeMeta"><i class="fa-solid fa-bullseye"></i> Qtd de Unidade</label>
          <div class="input-wrapper unidade-wrapper">
            <input type="text" name="unidade" id="unidadeMeta" value="<?= isset($valor_unidade) ? intval($valor_unidade) : '2' ?>">
            <span id="resultadoUnidade"></span>
          </div>
        </div>

        <div class="custom-inputbox">
          <label for="oddsMeta"><i class="fa-solid fa-percent"></i> Odds Min..</label>
          <div class="input-wrapper odds-wrapper">
            <input type="text" name="odds" id="oddsMeta" value="<?= isset($valor_odds) ? number_format(floatval($valor_odds), 2, ',', '') : '1,50' ?>">
            <span id="resultadoOdds"></span>
          </div>
        </div>
      </div>
        <!-- Confirmação de reset -->
        <div id="confirmarReset" style="display: none; margin-top: 10px;">
          <div class="mensagem-reset">
            Tem certeza que deseja <strong>resetar sua banca</strong>? Essa ação é irreversível.
            <div class="botoes-reset">
              <button type="button" id="btnConfirmarReset" class="btn-reset-confirmar">Sim, Resetar</button>
              <button type="button" id="btnCancelarReset" class="btn-reset-cancelar">Cancelar</button>
            </div>
          </div>
        </div>

        <!-- Toast do modal -->
        <div id="toastModal" style="margin-top: 10px;"></div>

        <!-- Botão principal -->
        <input type="button" id="botaoAcao" value="Cadastrar Dados" class="custom-button">
      </form>
    </div>
  </div>
</div>

<!-- Toast geral da página -->
<div id="toast-msg" class="toast hidden">Mensagem</div>

<!-- ============================================================================= -->
<!-- 🔧 SEÇÃO: SCRIPTS JAVASCRIPT                                                  -->
<!-- Função: Funcionalidades interativas da página                               -->
<!-- ============================================================================= -->

<!-- Script para timezone -->
<script>
// Definir timezone se o elemento existir
const timezoneInput = document.getElementById('timezone');
if (timezoneInput) {
  timezoneInput.value = Intl.DateTimeFormat().resolvedOptions().timeZone;
}
</script>

<!-- Script do menu de navegação -->
<script>
function toggleMenu() {
  var menu = document.getElementById("menu");
  if (menu) {
    menu.style.display = menu.style.display === "block" ? "none" : "block";
  }
}

// Fechar menu ao clicar fora
document.addEventListener('click', function(event) {
  var menu = document.getElementById("menu");
  var menuButton = document.querySelector(".menu-button");
  
  if (menu && menuButton && !menu.contains(event.target) && !menuButton.contains(event.target)) {
    menu.style.display = "none";
  }
});
</script>

<!-- Script do dropdown de gerenciamento -->
<script>
const toggle = document.querySelector('.dropdown-toggle');
const menu = document.querySelector('.dropdown-menu');
const hiddenInput = document.getElementById('acaoBanca');

if (toggle && menu && hiddenInput) {
  toggle.addEventListener('click', (e) => {
    e.preventDefault();
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
}
</script>

</body>
</html>