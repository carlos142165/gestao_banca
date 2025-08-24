    <!-- ==================================================================================================================================== --> 
<!--                                                 💼   PHP DE CALCULOS                    
 ====================================================================================================================================== -->
<?php
ob_start();
require_once 'config.php';
require_once 'carregar_sessao.php';
require_once 'funcoes.php';

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
$ultima_diaria = isset($_SESSION['porcentagem_entrada']) ? floatval($_SESSION['porcentagem_entrada']) : 0;
$soma_depositos = 
    (isset($_SESSION['saldo_mentores']) ? floatval($_SESSION['saldo_mentores']) : 0) + 
    (isset($_SESSION['saldo_geral']) ? floatval($_SESSION['saldo_geral']) : 0) - 
    (isset($_SESSION['saques_totais']) ? floatval($_SESSION['saques_totais']) : 0);
$soma_saque = isset($_SESSION['saques_totais']) ? floatval($_SESSION['saques_totais']) : 0;
$saldo_mentores = isset($_SESSION['saldo_mentores']) ? floatval($_SESSION['saldo_mentores']) : 0;
$saldo_banca = calcularSaldoBanca();
$valor_entrada_calculado = isset($_SESSION['resultado_entrada']) ? floatval($_SESSION['resultado_entrada']) : 0;
$valor_entrada_formatado = number_format($valor_entrada_calculado, 2, ',', '.');

// 🔎 Verificação de banca zerada
if ($saldo_banca <= 0 && $saldo_mentores < 0) {
  $_SESSION['banca_zerada'] = true;
} elseif ($saldo_banca > 0) {
  unset($_SESSION['banca_zerada']);
}

// 🗑️ EXCLUSÃO DE MENTOR - CORRIGIDA
if (isset($_POST['excluir_mentor']) || isset($_GET['excluir_mentor'])) {
  // Verificar se é uma requisição AJAX
  $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
  
  $id = intval(isset($_POST['excluir_mentor']) ? $_POST['excluir_mentor'] : $_GET['excluir_mentor']);
  $resposta = ['success' => false, 'message' => ''];
  
  if ($id > 0) {
    try {
      // ✅ CORREÇÃO: Verificar se mentor existe antes de excluir
      $stmt_check = $conexao->prepare("SELECT id FROM mentores WHERE id = ? AND id_usuario = ?");
      $stmt_check->bind_param("ii", $id, $id_usuario_logado);
      $stmt_check->execute();
      $result = $stmt_check->get_result();
      
      if ($result->num_rows > 0) {
        // ✅ CORREÇÃO: Excluir primeiro os valores relacionados
        $stmt_valores = $conexao->prepare("DELETE FROM valor_mentores WHERE id_mentores = ? AND id_usuario = ?");
        $stmt_valores->bind_param("ii", $id, $id_usuario_logado);
        $stmt_valores->execute();
        
        // Depois excluir o mentor
        $stmt = $conexao->prepare("DELETE FROM mentores WHERE id = ? AND id_usuario = ?");
        $stmt->bind_param("ii", $id, $id_usuario_logado);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
          $resposta = ['success' => true, 'message' => 'Mentor excluído com sucesso!'];
          setToast('Mentor excluído com sucesso!', 'sucesso');
        } else {
          $resposta = ['success' => false, 'message' => 'Erro ao excluir mentor!'];
          setToast('Erro ao excluir mentor!', 'erro');
        }
      } else {
        $resposta = ['success' => false, 'message' => 'Mentor não encontrado!'];
        setToast('Mentor não encontrado!', 'erro');
      }
    } catch (Exception $e) {
      $resposta = ['success' => false, 'message' => 'Erro ao excluir mentor: ' . $e->getMessage()];
      setToast('Erro ao excluir mentor: ' . $e->getMessage(), 'erro');
    }
  } else {
    $resposta = ['success' => false, 'message' => 'ID de mentor inválido!'];
    setToast('ID de mentor inválido!', 'erro');
  }
  
  if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode($resposta);
    exit();
  } else {
    header('Location: gestao-diaria.php');
    exit();
  }
}

// 📝 CADASTRO/EDIÇÃO DE MENTOR - CORRIGIDO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && 
    ($_POST['acao'] === 'cadastrar_mentor' || $_POST['acao'] === 'editar_mentor')) {
  
  $usuario_id = $_SESSION['usuario_id'];
  $nome = trim($_POST['nome'] ?? '');
  $mentor_id = isset($_POST['mentor_id']) ? intval($_POST['mentor_id']) : null;
  $acao = $_POST['acao'];

  // ✅ VALIDAÇÕES MELHORADAS
  if (empty($nome)) {
    setToast('Nome do mentor é obrigatório!', 'erro');
    header('Location: gestao-diaria.php');
    exit();
  }

  if (strlen($nome) < 2) {
    setToast('Nome deve ter pelo menos 2 caracteres!', 'erro');
    header('Location: gestao-diaria.php');
    exit();
  }

  if (strlen($nome) > 100) {
    setToast('Nome muito longo! Máximo 100 caracteres.', 'erro');
    header('Location: gestao-diaria.php');
    exit();
  }

  // ✅ VALIDAÇÃO PARA EDIÇÃO
  if ($acao === 'editar_mentor') {
    if (!$mentor_id || $mentor_id <= 0) {
      setToast('ID do mentor inválido para edição!', 'erro');
      header('Location: gestao-diaria.php');
      exit();
    }

    // Verificar se mentor existe e pertence ao usuário
    $stmt_check = $conexao->prepare("SELECT id FROM mentores WHERE id = ? AND id_usuario = ?");
    $stmt_check->bind_param("ii", $mentor_id, $usuario_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows === 0) {
      setToast('Mentor não encontrado ou não autorizado!', 'erro');
      header('Location: gestao-diaria.php');
      exit();
    }
  }

  $foto_nome = isset($_POST['foto_atual']) ? $_POST['foto_atual'] : 'avatar-padrao.png';

  // ✅ UPLOAD DE FOTO MELHORADO
  if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $extensao = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
    
    // Verificar se é uma imagem válida
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($extensao, $allowed_types)) {
      setToast('Formato de imagem inválido! Use JPG, JPEG, PNG, GIF ou WEBP.', 'erro');
      header('Location: gestao-diaria.php');
      exit();
    }
    
    // Verificar tamanho do arquivo (5MB)
    if ($_FILES['foto']['size'] > 5 * 1024 * 1024) {
      setToast('Arquivo muito grande! Máximo 5MB.', 'erro');
      header('Location: gestao-diaria.php');
      exit();
    }

    // ✅ VALIDAÇÃO ADICIONAL: verificar se é realmente uma imagem
    $check = getimagesize($_FILES['foto']['tmp_name']);
    if ($check === false) {
      setToast('Arquivo não é uma imagem válida!', 'erro');
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

    // ✅ REMOVER FOTO ANTIGA (se houver)
    if ($acao === 'editar_mentor' && isset($_POST['foto_atual']) && $_POST['foto_atual'] !== 'avatar-padrao.png') {
      $foto_antiga = "uploads/" . $_POST['foto_atual'];
      if (file_exists($foto_antiga)) {
        unlink($foto_antiga);
      }
    }
  }

  try {
    if ($acao === 'cadastrar_mentor') {
      // ✅ VERIFICAR SE JÁ EXISTE MENTOR COM MESMO NOME
      $stmt_check_nome = $conexao->prepare("SELECT id FROM mentores WHERE nome = ? AND id_usuario = ?");
      $stmt_check_nome->bind_param("si", $nome, $usuario_id);
      $stmt_check_nome->execute();
      $result = $stmt_check_nome->get_result();

      if ($result->num_rows > 0) {
        setToast('Já existe um mentor com este nome!', 'erro');
        header('Location: gestao-diaria.php');
        exit();
      }

      $stmt = $conexao->prepare("INSERT INTO mentores (id_usuario, foto, nome, data_criacao) VALUES (?, ?, ?, NOW())");
      $stmt->bind_param("iss", $usuario_id, $foto_nome, $nome);
      $mensagem_sucesso = 'Mentor cadastrado com sucesso!';
    } 
    elseif ($acao === 'editar_mentor') {
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
    setToast('Erro no banco de dados: ' . $e->getMessage(), 'erro');
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

// 📊 PROCESSAMENTO DE DADOS DOS MENTORES - CORRIGIDO
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

  // ✅ VALIDAÇÕES MELHORADAS
  if ($id_mentores <= 0) {
    setToast('Mentor inválido!', 'erro');
    header('Location: gestao-diaria.php');
    exit();
  }

  // Verificar se mentor existe e pertence ao usuário
  $stmt_check = $conexao->prepare("SELECT id FROM mentores WHERE id = ? AND id_usuario = ?");
  $stmt_check->bind_param("ii", $id_mentores, $id_usuario_logado);
  $stmt_check->execute();
  $result = $stmt_check->get_result();

  if ($result->num_rows === 0) {
    setToast('Mentor não encontrado ou não autorizado!', 'erro');
    header('Location: gestao-diaria.php');
    exit();
  }

  if ($valor_green < 0 || $valor_red < 0) {
    setToast('Valores não podem ser negativos!', 'erro');
    header('Location: gestao-diaria.php');
    exit();
  }

  if ($green === 0 && $red === 0) {
    setToast('Selecione Green ou Red!', 'erro');
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
    setToast('Erro no banco de dados: ' . $e->getMessage(), 'erro');
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

// ✅ FUNÇÃO AUXILIAR PARA DEBUGGING
function debug_log($message) {
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log("[MENTOR DEBUG] " . $message);
    }
}

// ✅ FUNÇÃO PARA VALIDAR UPLOAD
function validarUpload($file) {
    $errors = [];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Erro no upload do arquivo.';
        return $errors;
    }
    
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        $errors[] = 'Tipo de arquivo não permitido.';
    }
    
    if ($file['size'] > 5 * 1024 * 1024) { // 5MB
        $errors[] = 'Arquivo muito grande.';
    }
    
    return $errors;
}

// ✅ FUNÇÃO PARA SANITIZAR NOME
function sanitizarNome($nome) {
    $nome = trim($nome);
    $nome = strip_tags($nome);
    $nome = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
    
    // Remove caracteres especiais perigosos
    $nome = preg_replace('/[<>"\']/', '', $nome);
    
    return $nome;
}

// ✅ FUNÇÃO PARA GERAR TOAST
function setToast($mensagem, $tipo = 'info') {
    $_SESSION['toast'] = [
        'mensagem' => $mensagem,
        'tipo' => $tipo,
        'timestamp' => time()
    ];
}

ob_end_flush();
?>
    <!-- ==================================================================================================================================== --> 
<!--                                                 💼  FIM PHP DE CALCULOS                    
 ====================================================================================================================================== -->









<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Gestão do Dia</title>
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
    <!-- ==================================================================================================================================== --> 
<!--                                                 💼  LINK DOS ICONES                    
 ====================================================================================================================================== -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
    <!-- ==================================================================================================================================== --> 
<!--                                                 💼  LINK DOS CSS                    
 ====================================================================================================================================== -->
<link rel="stylesheet" href="css/menu-topo.css">
<link rel="stylesheet" href="css/modais.css">
<link rel="stylesheet" href="css/estilo-gestao-diaria-novo.css">
<link rel="stylesheet" href="css/estilo-campo-mes.css">
<link rel="stylesheet" href="css/estilo-painel-controle.css">
<link rel="stylesheet" href="css/toast.css">
<link rel="stylesheet" href="css/toast-modal-gerencia.css">
<!--          <link rel="stylesheet" href="css/estilos-organizados.css">       -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->

    <!-- ==================================================================================================================================== --> 
<!--                                                 💼  LINK DOS SCRIPTS                    
 ====================================================================================================================================== -->
<script src="js/script-gestao-diaria.js" defer></script>
<script src="js/script-painel-controle.js" defer></script>
<script src="js/script-mes.js" defer></script>
<script src="js/exclusao-manager-fix.js" defer></script>
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
</head>
<body>


    <!-- ==================================================================================================================================== --> 
<!--                                      💼    TOPO MENU SELEÇÃO + BANCA E SALDO                    
 ====================================================================================================================================== -->
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

    <!-- ==================================================================================================================================== --> 
<!--                                      💼   FIM TOPO MENU SELEÇÃO + BANCA E SALDO                    
 ====================================================================================================================================== -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
    <!-- ==================================================================================================================================== --> 
<!--                                      💼   FILTRO DIA - MES - ANO BLOCO CAMPO VALOR META E SALDO                      
 ====================================================================================================================================== -->
<div class="container">
    
  <!-- BLOCO 1 -->
  <div class="bloco bloco-1">
    <div class="container-resumos">
        <!-- Widget Meta com seu código PHP integrado -->
        <div class="widget-meta-container">
            <div class="widget-meta-row">
                <div class="widget-meta-item" id="widget-meta">
                    
                    <!-- Header com data e placar integrados -->
                  <div class="data-header-integrada" id="data-header">
                     <div class="data-texto-compacto">
                     <i class="fa-solid fa-calendar-days"></i>
                     <span class="data-principal-integrada" id="data-atual"></span>

                   <!-- Badge do período será adicionado aqui automaticamente -->
    
                  </div>
                        
                        <!-- Caixas de seleção de período -->
                        <div class="periodo-selecao-container">
                            <div class="periodo-opcao">
                                <label class="periodo-label">
                                    <input type="radio" name="periodo" value="dia" class="periodo-radio" checked>
                                    <span class="periodo-texto">DIA</span>
                                </label>
                            </div>
                            <div class="periodo-opcao">
                                <label class="periodo-label">
                                    <input type="radio" name="periodo" value="mes" class="periodo-radio">
                                    <span class="periodo-texto">MÊS</span>
                                </label>
                            </div>
                            <div class="periodo-opcao">
                                <label class="periodo-label">
                                    <input type="radio" name="periodo" value="ano" class="periodo-radio">
                                    <span class="periodo-texto">ANO</span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Espaço para equilíbrio -->
                        <div class="espaco-equilibrio"></div>
                        
                        <div class="data-separador-mini"></div>
                        
                        <div class="status-periodo-mini" id="status-periodo">
                            <!-- Status período será preenchido via JS -->
                        </div>
                    </div>

<!-- Conteúdo principal do widget -->
<div class="widget-conteudo-principal">
  <div class="conteudo-left">
     <!-- Container da Barra de Progresso -->
     <!-- Valor da Meta -->
<div class="widget-meta-valor" id="meta-valor">
    <i class="fa-solid fa-coins"></i>
    <div class="meta-valor-container">
        <span class="valor-texto" id="valor-texto-meta">R$ 20,00</span>
        
    </div>
</div>
    
     <!-- Exibição do valor que ultrapassou a meta -->
     <div class="valor-ultrapassou" id="valor-ultrapassou" style="display: none;">
        <i class="fa-solid fa-trophy"></i>
        <span class="texto-ultrapassou">Lucro Extra: <span id="valor-extra">R$ 0,00</span></span>
     </div>
    
     <!-- RÓTULO QUE ESTAVA FALTANDO -->
     <div class="widget-meta-rotulo" id="rotulo-meta">Meta do Dia</div>
    
     <!-- Container da Barra de Progresso -->
     <div class="widget-barra-container">
        <div class="widget-barra-progresso" id="barra-progresso"></div>
        <div class="porcentagem-barra" id="porcentagem-barra">0%</div>
     </div>
    
     <!-- Info de progresso com saldo -->
      <div class="widget-info-progresso">
      <span id="saldo-info" class="saldo-positivo">
     <i class="fa-solid fa-chart-line"></i>
     <span class="saldo-info-rotulo">Lucro:</span>
     <span class="saldo-info-valor">R$ 75,00</span>
     </span>
    </div>
    </div>
    

                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- ==================================================================================================================================== --> 
<!--                                      💼  FIM FILTRO DIA - MES - ANO BLOCO CAMPO VALOR META E SALDO                      
 ====================================================================================================================================== -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- ==================================================================================================================================== --> 
<!--                                                  💼  FILTRO BLOCO CAMPO MENTORES                        
 ====================================================================================================================================== -->
    <!-- Campo Mentores com seu código PHP integrado -->
    <div class="campo_mentores">
        <!-- Barra superior com botão à esquerda e placar centralizado -->
        <div class="barra-superior">
            <button class="btn-add-usuario" onclick="prepararFormularioNovoMentor()">
                <i class="fas fa-user-plus"></i>
            </button>
            
            <div class="area-central">
                <div class="pontuacao" id="pontuacao">
                    <span class="placar-green">0</span>
                    <span class="separador">×</span>
                    <span class="placar-red">0</span>
                </div>
            </div>

            <!-- ✅ NOVA ÁREA DIREITA -->
            <div class="area-direita">
                <div class="valor-dinamico valor-diaria">
                    <i class="fas fa-university"></i>
                    <span id="porcentagem-diaria">Carregando...</span>
                </div>
                <div class="valor-dinamico valor-unidade">
                    <span class="rotulo-und">UND:</span>
                    <span id="valor-unidade">Carregando...</span>
                </div>
            </div>
        </div>

        <!-- Área dos mentores - SEU CÓDIGO PHP ORIGINAL -->
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
<!-- ==================================================================================================================================== --> 
<!--                                                  💼  FIM FILTRO BLOCO CAMPO MENTORES                        
 ====================================================================================================================================== -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- ==================================================================================================================================== --> 
<!--                                                  💼  FILTRO BLOCO MÊS                          
 ====================================================================================================================================== -->
<!-- BLOCO 2 -->
<div class="bloco bloco-2">
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
<!-- ==================================================================================================================================== --> 
<!--                                                  💼  FIM DO FILTRO BLOCO MÊS                          
 ====================================================================================================================================== -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- ==================================================================================================================================== --> 
<!--                                  💼  FORMULARIO DE CADASTRO DO MENTOR + MODAL EXCLUSÃO DO MENTOR                           
 ====================================================================================================================================== -->
 

  
 <div class="modais-container">

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
          <span id="nome-arquivo" class="nome-arquivo">Nenhum arquivo selecionado</span>
        </div>

        <!-- Pré-visualização da imagem -->
        <div class="preview-foto-wrapper">
          <img id="preview-img" src="https://cdn-icons-png.flaticon.com/512/847/847969.png" class="preview-img" alt="Pré-visualização">
          <button type="button" id="remover-foto" class="btn-remover-foto" onclick="removerImagem()" style="display:none;">Remover Foto</button>
        </div>

        <!-- Nome do mentor -->
        <h3 id="mentor-nome-preview" class="mentor-nome-preview" style="text-align: center; margin-top: 14px;">Nome do Mentor</h3>

        <!-- Campo de entrada do nome -->
        <div class="input-group">
          <label for="nome" class="label-form"></label>
          <input type="text" name="nome" id="nome" class="input-text" placeholder="Digite o nome do mentor" required maxlength="100" style="text-align: center;">
        </div>

        <!-- Botões de ação -->
        <div class="botoes-formulario">
          <button type="submit" id="btn-enviar" class="btn-enviar">
            <i class="fas fa-user-plus"></i> Cadastrar Mentor
          </button>
          <button type="button" class="btn-excluir" id="btn-excluir" onclick="excluirMentorDireto()" style="display: none;">
            <i class="fas fa-user-times"></i> Excluir Mentor
          </button>
        </div>
      </form>
    </div>
  </div>
   <!-- Modal de confirmação de exclusão -->
  <div id="modal-confirmacao-exclusao" class="modal-confirmacao" style="display: none;">
    <div class="modal-content">
      <p class="modal-texto"></p>
      <div class="botoes-modal">
        <button type="button" class="botao-confirmar">Sim, excluir</button>
        <button type="button" class="botao-cancelar">Cancelar</button>
      </div>
    </div>
  </div>


</div>
<!-- ==================================================================================================================================== --> 
<!--                                  💼  FIM FORMULARIO DE CADASTRO DO MENTOR + MODAL EXCLUSÃO DO MENTOR                           
 ====================================================================================================================================== -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- ==================================================================================================================================== --> 
<!--                                  💼  FORMULARIO DE CADASTRO DE ENTRADA + MODAL EXCLUSÃO DE ENTRADA                              
 ====================================================================================================================================== -->
<!-- Container que encapsula todos os modais -->

  <div class="modais-container">

  <!-- ✅ MODAL DE CONFIRMAÇÃO DE EXCLUSÃO DE ENTRADA - CORRIGIDO -->
  <div id="modal-confirmacao-entrada" class="modal-confirmacao-entrada">
    <div class="modal-conteudo-exclusao">
      <div class="icone-aviso">
        <i class="fas fa-exclamation-triangle"></i>
      </div>
      
      <div class="texto-confirmacao">
        <p>Tem certeza que deseja excluir esta entrada?</p>
        <p style="font-size: 14px; color: #7f8c8d; margin-top: 10px;">
          <i class="fas fa-info-circle"></i> Esta ação não pode ser desfeita.
        </p>
      </div>

      <div class="botoes-confirmacao">
        <button id="btn-cancelar-entrada" class="btn-modal btn-cancelar-exclusao">
          <i class="fas fa-times"></i>
          Cancelar
        </button>
        <button id="btn-confirmar-entrada" class="btn-modal btn-confirmar-exclusao">
          <i class="fas fa-trash"></i>
          Sim, Excluir
        </button>
      </div>
    </div>
  </div>

  <!-- Modal de Confirmação de Exclusão ORIGINAL (mantido para compatibilidade) -->
  <div class="modais-container">
    <div id="modal-confirmacao" class="modal-confirmacao">
      <div class="modal-content">
        <p class="modal-texto">Tem certeza que deseja excluir esta entrada?</p>
        <div class="botoes-modal">
          <button id="btnConfirmar" class="botao-confirmar" type="button">Sim, excluir</button>
          <button id="btnCancelar" class="botao-cancelar" type="button">Não, cancelar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Tela de Edição (Histórico do Mentor) -->
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

  <!-- NOVO FORMULÁRIO DO MENTOR -->
  <div class="formulario-mentor-novo" id="formulario-mentor-novo">
    <button type="button" class="btn-fechar-novo" onclick="fecharFormularioNovo()">
      <i class="fas fa-times"></i>
    </button>

    <!-- Info do mentor -->
    <div class="mentor-info-novo">
      <img src="" class="mentor-foto-novo" alt="Foto do mentor">
      <h3 class="mentor-nome-novo">Nome do Mentor</h3>
    </div>

    <!-- Opções Cash, Green, Red -->
    <div class="opcoes-container-novo">
      <div class="opcao-novo" data-tipo="cash">
        <input type="radio" id="opcao-cash" name="tipo-operacao" value="cash">
        <label for="opcao-cash">Cash</label>
      </div>
      <div class="opcao-novo" data-tipo="green">
        <input type="radio" id="opcao-green" name="tipo-operacao" value="green">
        <label for="opcao-green">Green</label>
      </div>
      <div class="opcao-novo" data-tipo="red">
        <input type="radio" id="opcao-red" name="tipo-operacao" value="red">
        <label for="opcao-red">Red</label>
      </div>
    </div>

    <form id="form-mentor-novo" method="POST">
      <input type="hidden" name="id_mentor" class="mentor-id-novo">
      <input type="hidden" name="tipo_operacao" class="tipo-operacao-novo">
        <!-- Valor da unidade calculado pelo PHP -->
        <span id="valor-unidade" style="display:none;">
          <?php
            // Exibe o valor da unidade calculado
            if (isset($area_direita) && isset($area_direita['unidade_entrada_formatada'])) {
              echo $area_direita['unidade_entrada_formatada'];
            } else {
              echo 'R$ 0,00';
            }
          ?>
        </span>

      <!-- Área de inputs -->
      <div class="inputs-area-novo">
        <!-- Mensagem inicial -->
        <div class="mensagem-inicial-gestao" id="mensagem-inicial-gestao">
          <i class="fas fa-chart-line"></i>
          <p>Disciplina é o que separa sorte de estratégia. Mantenha-se dentro da gestão é ela que protege seu capital, guia suas decisões e constrói lucro consistente ao longo do tempo. Não é sobre ganhar sempre, é sobre jogar certo sempre.</p>
        </div>

        <!-- Inputs duplos para Cash/Green -->
        <div class="inputs-duplos-novo" id="inputs-duplos">
          <div class="campo-duplo-novo">
            <label for="input-entrada">Unidade</label>
            <input type="text" id="input-entrada" name="entrada" placeholder="R$ 0,00">
            <div class="mensagem-status-input"></div>
          </div>
          <div class="campo-duplo-novo">
            <label for="input-total" id="label-total">Total: Cashout</label>
            <input type="text" id="input-total" name="total" placeholder="R$ 0,00">
            <div class="mensagem-status-input"></div>
          </div>
        </div>

        <!-- Input único para Red -->
        <div class="input-unico-novo" id="input-unico">
          <label for="input-red">Valor Red</label>
          <input type="text" id="input-red" name="valor_red" placeholder="R$ 0,00">
          <div class="mensagem-status-input"></div>
        </div>
      </div>

      <!-- Status do cálculo -->
      <div class="status-calculo-novo">
        <div class="rotulo-status-novo" id="rotulo-status">Neutro</div>
        <div class="valor-status-novo status-neutro" id="valor-status">R$ 0,00</div>
      </div>

      <button type="submit" class="botao-enviar-novo">Cadastrar</button>
    </form>
  </div>

  <div id="mensagem-status" class="toast"></div>
  <div id="toast" class="toast hidden"></div>

</div>

<style>
/* ===== ESTILOS DO MODAL DE EXCLUSÃO CORRIGIDO ===== */

/* Modal de confirmação de exclusão de entrada */
.modal-confirmacao-entrada {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    background: rgba(0, 0, 0, 0.6) !important;
    z-index: 999999 !important;
    display: none !important;
    justify-content: center !important;
    align-items: center !important;
    backdrop-filter: blur(3px);
    opacity: 0;
    transition: opacity 0.3s ease;
}

/* Quando ativo/visível */
.modal-confirmacao-entrada.ativo {
    display: flex !important;
    opacity: 1 !important;
}

/* Conteúdo do modal */
.modal-confirmacao-entrada .modal-conteudo-exclusao {
    background: #ffffff !important;
    border-radius: 16px !important;
    padding: 30px !important;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3) !important;
    text-align: center !important;
    max-width: 450px !important;
    width: 90% !important;
    margin: 0 auto !important;
    position: relative !important;
    animation: modalSlideIn 0.4s ease-out !important;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: scale(0.8) translateY(-20px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

/* Ícone de aviso */
.modal-confirmacao-entrada .icone-aviso {
    font-size: 48px !important;
    color: #e74c3c !important;
    margin-bottom: 20px !important;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}

/* Texto do modal */
.modal-confirmacao-entrada .texto-confirmacao {
    font-size: 18px !important;
    font-weight: 600 !important;
    color: #2c3e50 !important;
    margin-bottom: 25px !important;
    line-height: 1.5 !important;
}

/* Botões do modal */
.modal-confirmacao-entrada .botoes-confirmacao {
    display: flex !important;
    justify-content: center !important;
    gap: 15px !important;
    margin-top: 20px !important;
}

.modal-confirmacao-entrada .btn-modal {
    padding: 12px 24px !important;
    border: none !important;
    border-radius: 8px !important;
    font-size: 16px !important;
    font-weight: 600 !important;
    cursor: pointer !important;
    transition: all 0.3s ease !important;
    min-width: 120px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 8px !important;
}

.modal-confirmacao-entrada .btn-confirmar-exclusao {
    background: #e74c3c !important;
    color: white !important;
}

.modal-confirmacao-entrada .btn-confirmar-exclusao:hover {
    background: #c0392b !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3) !important;
}

.modal-confirmacao-entrada .btn-cancelar-exclusao {
    background: #95a5a6 !important;
    color: white !important;
}

.modal-confirmacao-entrada .btn-cancelar-exclusao:hover {
    background: #7f8c8d !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 4px 12px rgba(149, 165, 166, 0.3) !important;
}

/* Responsividade do modal de exclusão */
@media (max-width: 480px) {
    .modal-confirmacao-entrada .modal-conteudo-exclusao {
        padding: 20px !important;
        margin: 20px !important;
        width: calc(100% - 40px) !important;
    }

    .modal-confirmacao-entrada .botoes-confirmacao {
        flex-direction: column !important;
        gap: 10px !important;
    }

    .modal-confirmacao-entrada .btn-modal {
        width: 100% !important;
    }
}

/* ===== CSS DO NOVO SISTEMA DE CADASTRO COM ELEGÂNCIA ===== */

/* ✅ OVERLAY ELEGANTE COM ESCURECIMENTO E BLUR */
.formulario-mentor-overlay {
  position: fixed !important;
  top: 0 !important;
  left: 0 !important;
  width: 100vw !important;
  height: 100vh !important;
  background: rgba(0, 0, 0, 0.0) !important; /* Começa transparente */
  backdrop-filter: blur(0px) !important; /* Começa sem blur */
  z-index: 9998 !important;
  display: none !important;
  opacity: 0 !important;
  transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94) !important; /* Curva suave */
}

.formulario-mentor-overlay.ativo {
  display: block !important;
  opacity: 1 !important;
  background: rgba(0, 0, 0, 0.7) !important; /* Escurece elegantemente */
  backdrop-filter: blur(8px) !important; /* Blur suave */
}

/* Container principal do novo formulário */
.formulario-mentor-novo {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%) scale(0.7); /* ✅ Começa menor */
  background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 50%, #ffffff 100%);
  border-radius: 24px; /* ✅ Bordas mais suaves */
  padding: 30px;
  box-shadow: 
    0 25px 50px rgba(0, 0, 0, 0.25), /* ✅ Sombra mais dramática */
    0 15px 35px rgba(0, 0, 0, 0.15),
    0 5px 15px rgba(0, 0, 0, 0.1);
  z-index: 9999;
  display: none;
  width: 400px; /* ✅ Levemente maior */
  max-height: 85vh;
  min-width: 350px;
  max-width: 440px; /* ✅ Ajustado */
  border: 1px solid rgba(255, 255, 255, 0.2); /* ✅ Borda sutil */
  font-family: "Poppins", sans-serif;
  margin: 0;
  box-sizing: border-box;
  overflow: hidden;
  max-width: calc(100vw - 40px);
  max-height: calc(100vh - 40px);
  opacity: 0; /* ✅ Começa transparente */
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) !important; /* ✅ Curva elegante com bounce */
}

/* ✅ ESTADO ATIVO COM ANIMAÇÃO SUAVE */
.formulario-mentor-novo.ativo {
  display: block;
  opacity: 1 !important;
  transform: translate(-50%, -50%) scale(1) !important; /* ✅ Escala para tamanho normal */
}

/* ✅ ESTADO DE FECHAMENTO COM ANIMAÇÃO SUAVE */
.formulario-mentor-novo.fechando {
  opacity: 0 !important;
  transform: translate(-50%, -50%) scale(0.8) !important; /* ✅ Diminui suavemente */
  transition: all 0.3s cubic-bezier(0.55, 0.085, 0.68, 0.53) !important; /* ✅ Curva de saída */
}

/* Botão fechar com hover elegante */
.formulario-mentor-novo .btn-fechar-novo {
  position: absolute;
  top: 15px;
  right: 15px;
  background: rgba(220, 53, 69, 0.1);
  color: #dc3545;
  border: none;
  border-radius: 50%;
  width: 36px; /* ✅ Levemente maior */
  height: 36px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
  font-size: 16px;
  z-index: 10;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); /* ✅ Sombra sutil */
}

.formulario-mentor-novo .btn-fechar-novo:hover {
  background: #dc3545;
  color: white;
  transform: scale(1.15) rotate(90deg); /* ✅ Rotação suave */
  box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
}

/* ✅ INFO DO MENTOR COM ELEGÂNCIA */
.mentor-info-novo {
  text-align: center;
  margin-bottom: 25px;
  padding-bottom: 20px;
  border-bottom: 2px solid rgba(233, 236, 239, 0.6);
  position: relative;
}

/* ✅ EFEITO SHIMMER NA FOTO */
.mentor-foto-novo {
  width: 90px; /* ✅ Levemente maior */
  height: 90px;
  border-radius: 50%;
  object-fit: cover;
  border: 4px solid #007bff; /* ✅ Borda mais espessa */
  margin-bottom: 12px;
  box-shadow: 
    0 8px 25px rgba(0, 123, 255, 0.2),
    0 4px 10px rgba(0, 0, 0, 0.1);
  transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
  position: relative;
  overflow: hidden;
}

.mentor-foto-novo::before {
  content: '';
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: linear-gradient(
    45deg,
    transparent,
    rgba(255, 255, 255, 0.3),
    transparent
  );
  transform: rotate(45deg);
  transition: all 0.6s ease;
  opacity: 0;
}

.mentor-foto-novo:hover::before {
  opacity: 1;
  transform: rotate(45deg) translate(100%, 100%);
}

.mentor-foto-novo:hover {
  transform: scale(1.05);
  box-shadow: 
    0 12px 35px rgba(0, 123, 255, 0.3),
    0 6px 15px rgba(0, 0, 0, 0.15);
}

.mentor-nome-novo {
  font-size: 18px; /* ✅ Levemente maior */
  font-weight: 700;
  color: #2c3e50;
  margin: 0;
  text-transform: capitalize;
  letter-spacing: 0.5px; /* ✅ Espaçamento elegante */
}

/* ✅ OPÇÕES COM MICRO-ANIMAÇÕES */
.opcoes-container-novo {
  display: flex;
  justify-content: center;
  gap: 15px; /* ✅ Gap maior */
  margin-bottom: 25px;
  padding: 0 10px;
}

.opcao-novo {
  display: flex;
  flex-direction: column;
  align-items: center;
  cursor: pointer;
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); /* ✅ Bounce suave */
  padding: 12px; /* ✅ Padding maior */
  border-radius: 16px; /* ✅ Bordas mais suaves */
  border: 2px solid transparent;
  min-width: 70px;
  position: relative;
  overflow: hidden;
}

.opcao-novo::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(
    90deg,
    transparent,
    rgba(0, 123, 255, 0.1),
    transparent
  );
  transition: all 0.6s ease;
}

.opcao-novo:hover::before {
  left: 100%;
}

.opcao-novo:hover {
  background: rgba(0, 123, 255, 0.08);
  border-color: rgba(0, 123, 255, 0.3);
  transform: translateY(-2px) scale(1.02); /* ✅ Levitação suave */
  box-shadow: 0 8px 25px rgba(0, 123, 255, 0.15);
}

.opcao-novo.selecionada {
  background: linear-gradient(135deg, rgba(0, 123, 255, 0.15), rgba(0, 123, 255, 0.08));
  border-color: #007bff;
  transform: translateY(-3px) scale(1.05); /* ✅ Mais proeminente */
  box-shadow: 
    0 12px 35px rgba(0, 123, 255, 0.25),
    0 6px 15px rgba(0, 0, 0, 0.1);
}

.opcao-novo input[type="radio"] {
  width: 18px; /* ✅ Levemente maior */
  height: 18px;
  margin-bottom: 8px;
  cursor: pointer;
  accent-color: #007bff;
  transition: all 0.3s ease;
}

.opcao-novo label {
  font-size: 14px; /* ✅ Levemente maior */
  font-weight: 600;
  color: #495057;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
  user-select: none;
  letter-spacing: 0.3px;
}

.opcao-novo.selecionada label {
  color: #007bff;
  font-weight: 700;
  text-shadow: 0 1px 2px rgba(0, 123, 255, 0.2);
}

/* ✅ MENSAGEM INICIAL COM FADE ELEGANTE */
.mensagem-inicial-gestao {
  text-align: center;
  padding: 25px; /* ✅ Padding maior */
  background: linear-gradient(145deg, #f8f9fa 0%, #ffffff 50%, #f0f2f5 100%);
  border-radius: 16px; /* ✅ Bordas mais suaves */
  margin: 20px 0;
  box-shadow: 
    0 8px 25px rgba(0, 0, 0, 0.08),
    0 3px 10px rgba(0, 0, 0, 0.05);
  opacity: 0;
  transition: all 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94);
  display: none;
  transform: translateY(20px);
  border: 1px solid rgba(0, 123, 255, 0.1);
}

.mensagem-inicial-gestao i {
  font-size: 28px; /* ✅ Ícone maior */
  color: #007bff;
  margin-bottom: 15px;
  animation: pulse-icon 2s ease-in-out infinite; /* ✅ Animação do ícone */
}

@keyframes pulse-icon {
  0%, 100% { 
    transform: scale(1); 
    opacity: 1; 
  }
  50% { 
    transform: scale(1.05); 
    opacity: 0.8; 
  }
}

.mensagem-inicial-gestao p {
  margin: 0;
  font-size: 14px; /* ✅ Texto maior */
  line-height: 1.7; /* ✅ Espaçamento melhor */
  color: #495057;
  font-weight: 500;
  letter-spacing: 0.3px;
}

.mensagem-inicial-gestao.ativo {
  opacity: 1;
  transform: translateY(0);
}

/* ✅ INPUTS COM ANIMAÇÕES SUAVES */
.inputs-area-novo {
  margin-bottom: 20px;
  min-height: 120px;
}

.inputs-duplos-novo,
.input-unico-novo {
  opacity: 0;
  transform: translateX(-20px);
  transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  display: none;
}

.inputs-duplos-novo.ativo,
.input-unico-novo.ativo {
  opacity: 1;
  transform: translateX(0);
  display: block;
}

.campo-duplo-novo {
  margin-bottom: 15px;
}

.campo-duplo-novo label {
  display: block;
  font-size: 13px; /* ✅ Levemente maior */
  font-weight: 600;
  color: #6c757d;
  margin-bottom: 6px;
  letter-spacing: 0.5px;
  transition: all 0.3s ease;
}

.campo-duplo-novo input {
  width: 100%;
  padding: 12px 15px; /* ✅ Padding maior */
  border: 2px solid #e9ecef;
  border-radius: 12px; /* ✅ Bordas mais suaves */
  font-size: 16px;
  font-weight: 600;
  color: #495057;
  background: linear-gradient(145deg, #ffffff, #f8f9fa);
  transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
  box-sizing: border-box;
  box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
}

.campo-duplo-novo input:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 
    0 0 0 4px rgba(0, 123, 255, 0.15),
    inset 0 2px 4px rgba(0, 0, 0, 0.05);
  transform: translateY(-1px);
  background: #ffffff;
}

.campo-duplo-novo input:hover:not(:focus) {
  border-color: rgba(0, 123, 255, 0.5);
  transform: translateY(-0.5px);
}

/* Input Red com estilo especial */
.input-unico-novo {
  text-align: center;
}

.input-unico-novo label {
  display: block;
  font-size: 14px;
  font-weight: 600;
  color: #dc3545;
  margin-bottom: 10px;
  letter-spacing: 0.5px;
  text-transform: uppercase;
  font-size: 12px;
}

.input-unico-novo input {
  width: 100%;
  padding: 15px;
  border: 2px solid #f8d7da;
  border-radius: 12px;
  font-size: 18px;
  font-weight: 700;
  color: #dc3545;
  background: linear-gradient(145deg, #fff5f5, #ffffff);
  text-align: center;
  transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
  box-sizing: border-box;
  box-shadow: inset 0 2px 4px rgba(220, 53, 69, 0.05);
}

.input-unico-novo input:focus {
  outline: none;
  border-color: #dc3545;
  box-shadow: 
    0 0 0 4px rgba(220, 53, 69, 0.15),
    inset 0 2px 4px rgba(220, 53, 69, 0.05);
  transform: scale(1.02);
  background: #ffffff;
}

/* ✅ STATUS COM ANIMAÇÃO FLUIDA */
.status-calculo-novo {
  text-align: center;
  padding: 15px;
  border-radius: 16px;
  background: linear-gradient(145deg, #f8f9fa, #ffffff);
  border: 2px solid #e9ecef;
  margin-bottom: 20px;
  min-height: 50px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
}

.rotulo-status-novo {
  font-size: 12px;
  font-weight: 600;
  color: #6c757d;
  letter-spacing: 1px;
  margin-bottom: 5px;
  text-transform: uppercase;
  transition: all 0.3s ease;
}

.valor-status-novo {
  font-size: 18px;
  font-weight: 700;
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.status-calculo-novo.status-positivo-ativo {
  background: linear-gradient(145deg, rgba(40, 167, 69, 0.08), rgba(40, 167, 69, 0.03));
  border-color: rgba(40, 167, 69, 0.3);
  box-shadow: 0 6px 20px rgba(40, 167, 69, 0.15);
}

.status-calculo-novo.status-negativo-ativo {
  background: linear-gradient(145deg, rgba(220, 53, 69, 0.08), rgba(220, 53, 69, 0.03));
  border-color: rgba(220, 53, 69, 0.3);
  box-shadow: 0 6px 20px rgba(220, 53, 69, 0.15);
}

.status-calculo-novo.animando {
  transform: scale(1.02);
  animation: pulse-status 0.6s ease-in-out;
}

@keyframes pulse-status {
  0%, 100% { 
    transform: scale(1.02); 
  }
  50% { 
    transform: scale(1.05); 
  }
}

/* ✅ BOTÃO COM ELEGÂNCIA MÁXIMA */
.botao-enviar-novo {
  width: 100%;
  padding: 15px;
  background: linear-gradient(135deg, #007bff 0%, #0056b3 50%, #004085 100%);
  color: white;
  border: none;
  border-radius: 14px;
  font-size: 16px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  letter-spacing: 0.5px;
  text-transform: uppercase;
  font-size: 14px;
  box-shadow: 
    0 8px 25px rgba(0, 123, 255, 0.3),
    0 4px 10px rgba(0, 0, 0, 0.1);
  position: relative;
  overflow: hidden;
}

.botao-enviar-novo::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 0;
  height: 0;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  transform: translate(-50%, -50%);
  transition: all 0.6s ease;
}

.botao-enviar-novo:hover::before {
  width: 300px;
  height: 300px;
}

.botao-enviar-novo:hover {
  transform: translateY(-3px) scale(1.02);
  box-shadow: 
    0 12px 35px rgba(0, 123, 255, 0.4),
    0 6px 15px rgba(0, 0, 0, 0.15);
  background: linear-gradient(135deg, #0056b3 0%, #004085 50%, #002752 100%);
}

.botao-enviar-novo:active {
  transform: translateY(-1px) scale(0.98);
  transition: all 0.1s ease;
}

.botao-enviar-novo:disabled {
  background: linear-gradient(135deg, #6c757d, #495057);
  cursor: not-allowed;
  transform: none;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

.botao-enviar-novo.carregando {
  position: relative;
  color: transparent;
}

.botao-enviar-novo.carregando::after {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 22px;
  height: 22px;
  border: 3px solid transparent;
  border-top: 3px solid white;
  border-radius: 50%;
  animation: spin-elegant 1s linear infinite;
}

@keyframes spin-elegant {
  0% { 
    transform: translate(-50%, -50%) rotate(0deg); 
  }
  100% { 
    transform: translate(-50%, -50%) rotate(360deg); 
  }
}/* ✅ MENSAGENS DE STATUS COM ANIMAÇÕES ELEGANTES */
.mensagem-status-input {
  font-size: 12px;
  margin-top: 8px;
  margin-bottom: 8px;
  line-height: 1.5;
  transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
  padding: 8px 12px;
  border-radius: 8px;
  display: none;
  text-align: center;
  font-weight: 500;
  max-width: 100%;
  box-sizing: border-box;
  transform: translateY(10px);
  opacity: 0;
}

.mensagem-status-input.positivo {
  color: #155724;
  background: linear-gradient(145deg, rgba(40, 167, 69, 0.1), rgba(40, 167, 69, 0.05));
  border-left: 4px solid #28a745;
  box-shadow: 0 4px 15px rgba(40, 167, 69, 0.1);
}

.mensagem-status-input.negativo {
  color: #721c24;
  background: linear-gradient(145deg, rgba(220, 53, 69, 0.1), rgba(220, 53, 69, 0.05));
  border-left: 4px solid #dc3545;
  box-shadow: 0 4px 15px rgba(220, 53, 69, 0.1);
}

.mensagem-status-input.neutro {
  color: #495057;
  background: linear-gradient(145deg, rgba(108, 117, 125, 0.1), rgba(108, 117, 125, 0.05));
  border-left: 4px solid #6c757d;
  box-shadow: 0 4px 15px rgba(108, 117, 125, 0.1);
}

.mensagem-status-input.animar {
  display: block !important;
  opacity: 1 !important;
  transform: translateY(0) !important;
  animation: fadeInUp-elegant 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

@keyframes fadeInUp-elegant {
  0% {
    opacity: 0;
    transform: translateY(15px) scale(0.95);
  }
  60% {
    opacity: 0.8;
    transform: translateY(-2px) scale(1.02);
  }
  100% {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

/* ✅ RESPONSIVIDADE ELEGANTE */
@media (max-width: 768px) and (min-width: 481px) {
  .formulario-mentor-novo {
    width: 380px;
    min-width: 360px;
    max-width: min(400px, calc(100vw - 40px));
    padding: 25px;
    max-height: min(90vh, calc(100vh - 40px));
  }
  
  .mentor-foto-novo {
    width: 80px;
    height: 80px;
  }
}

@media (max-width: 480px) {
  .formulario-mentor-novo {
    width: 340px;
    min-width: 320px;
    max-width: min(360px, calc(100vw - 20px));
    padding: 20px;
    max-height: min(95vh, calc(100vh - 20px));
  }
  
  .mentor-foto-novo {
    width: 70px;
    height: 70px;
  }
  
  .mentor-nome-novo {
    font-size: 16px;
  }
  
  .opcoes-container-novo {
    gap: 12px;
  }
  
  .opcao-novo {
    min-width: 65px;
    padding: 10px;
  }
}

@media (max-width: 320px) {
  .formulario-mentor-novo {
    width: 300px;
    min-width: 280px;
    max-width: min(320px, calc(100vw - 20px));
    padding: 18px;
    max-height: calc(100vh - 20px);
  }
  
  .mentor-foto-novo {
    width: 60px;
    height: 60px;
  }
  
  .mentor-nome-novo {
    font-size: 15px;
  }
}

/* ✅ PREVENÇÃO DE SCROLL COM ELEGÂNCIA */
body.modal-aberto {
  overflow: hidden !important;
  padding-right: 0 !important;
  transition: all 0.3s ease;
}

/* ✅ ESTADOS DE VALIDAÇÃO ELEGANTES */
.campo-duplo-novo input.erro,
.input-unico-novo input.erro {
  border-color: #dc3545;
  box-shadow: 
    0 0 0 4px rgba(220, 53, 69, 0.15),
    inset 0 2px 4px rgba(220, 53, 69, 0.05);
  animation: shake-elegant 0.5s ease-in-out;
}

.campo-duplo-novo input.sucesso {
  border-color: #28a745;
  box-shadow: 
    0 0 0 4px rgba(40, 167, 69, 0.15),
    inset 0 2px 4px rgba(40, 167, 69, 0.05);
  animation: success-pulse 0.6s ease-in-out;
}

@keyframes shake-elegant {
  0%, 100% { transform: translateX(0) translateY(-1px); }
  10%, 30%, 50%, 70%, 90% { transform: translateX(-3px) translateY(-1px); }
  20%, 40%, 60%, 80% { transform: translateX(3px) translateY(-1px); }
}

@keyframes success-pulse {
  0% { 
    transform: scale(1) translateY(-1px); 
    box-shadow: 0 0 0 4px rgba(40, 167, 69, 0.15); 
  }
  50% { 
    transform: scale(1.02) translateY(-2px); 
    box-shadow: 0 0 0 8px rgba(40, 167, 69, 0.25); 
  }
  100% { 
    transform: scale(1) translateY(-1px); 
    box-shadow: 0 0 0 4px rgba(40, 167, 69, 0.15); 
  }
}

/* ✅ AJUSTES FINAIS PARA TELAS MUITO PEQUENAS */
@media (max-width: 400px) {
  .formulario-mentor-novo {
    width: calc(100vw - 30px) !important;
    min-width: 280px !important;
    max-width: calc(100vw - 30px) !important;
    left: 50% !important;
    right: auto !important;
    transform: translate(-50%, -50%) scale(0.7) !important;
    margin: 0 !important;
  }
  
  .formulario-mentor-novo.ativo {
    transform: translate(-50%, -50%) scale(1) !important;
  }
  
  .formulario-mentor-novo.fechando {
    transform: translate(-50%, -50%) scale(0.8) !important;
  }
}

/* ✅ ANIMAÇÕES GLOBAIS ELEGANTES */
@keyframes fadeIn-elegant {
  0% {
    opacity: 0;
    transform: translate(-50%, -60%) scale(0.7);
  }
  100% {
    opacity: 1;
    transform: translate(-50%, -50%) scale(1);
  }
}

@keyframes fadeOut-elegant {
  0% {
    opacity: 1;
    transform: translate(-50%, -50%) scale(1);
  }
  100% {
    opacity: 0;
    transform: translate(-50%, -45%) scale(0.8);
  }
}

/* ✅ PREVENÇÃO DE CONFLITOS COM ELEGÂNCIA */
.formulario-mentor-novo * {
  box-sizing: border-box;
}

.formulario-mentor-novo .mentor-card,
.formulario-mentor-novo .mentor-item,
.formulario-mentor-novo .formulario-mentor {
  all: initial;
  font-family: "Poppins", sans-serif;
}

/* Animações globais */
@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}
</style>

<script>
// ===== SISTEMA INTEGRADO CORRIGIDO =====

// 🚫 DESATIVAR SISTEMA ANTIGO COMPLETAMENTE
window.FormularioValorManager_DESATIVADO = true;

// ===== SISTEMA DE EXCLUSÃO DE ENTRADA CORRIGIDO =====
const ModalExclusaoEntrada = {
    modal: null,
    btnConfirmar: null,
    btnCancelar: null,
    idEntradaAtual: null,
    processandoExclusao: false, // ✅ NOVA FLAG PARA EVITAR DUPLA EXCLUSÃO

    inicializar() {
        this.modal = document.getElementById('modal-confirmacao-entrada');
        this.btnConfirmar = document.getElementById('btn-confirmar-entrada');
        this.btnCancelar = document.getElementById('btn-cancelar-entrada');

        if (!this.modal || !this.btnConfirmar || !this.btnCancelar) {
            console.error('❌ Elementos do modal de exclusão não encontrados');
            return;
        }

        this.configurarEventos();
        this.integrarComSistemaExistente();
        console.log('✅ Modal de exclusão de entrada inicializado');
    },

    configurarEventos() {
        // Botão cancelar
        this.btnCancelar.addEventListener('click', () => {
            this.fecharModal();
        });

        // Botão confirmar
        this.btnConfirmar.addEventListener('click', () => {
            this.confirmarExclusao();
        });

        // Fechar com ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.modal.classList.contains('ativo')) {
                this.fecharModal();
            }
        });

        // Fechar clicando no fundo
        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) {
                this.fecharModal();
            }
        });

        // Prevenir clique no conteúdo fechar o modal
        this.modal.querySelector('.modal-conteudo-exclusao').addEventListener('click', (e) => {
            e.stopPropagation();
        });
    },

    integrarComSistemaExistente() {
        // Substitui a função original de exclusão se existir
        if (typeof ExclusaoManager !== 'undefined') {
            ExclusaoManager._excluirEntradaOriginal = ExclusaoManager.excluirEntrada;
            ExclusaoManager.excluirEntrada = (idEntrada) => {
                this.abrir(idEntrada);
            };
        }

        // Intercepta cliques em botões de lixeira
        document.addEventListener('click', (e) => {
            if (e.target.matches('.btn-lixeira, .btn-lixeira *, .btn-icon.btn-lixeira, .btn-icon.btn-lixeira *')) {
                e.preventDefault();
                e.stopPropagation();
                
                let button = e.target.closest('.btn-lixeira, .btn-icon');
                if (button) {
                    // Tenta extrair ID do onclick
                    if (button.onclick) {
                        const onclickStr = button.onclick.toString();
                        const match = onclickStr.match(/excluirEntrada\((\d+)\)/);
                        if (match) {
                            const idEntrada = match[1];
                            this.abrir(idEntrada);
                            return;
                        }
                    }
                    
                    // Tenta extrair ID do atributo data-id ou similar
                    const idEntrada = button.dataset.id || 
                                    button.getAttribute('data-entrada-id') ||
                                    button.closest('[data-entrada-id]')?.dataset.entradaId;
                    
                    if (idEntrada) {
                        this.abrir(idEntrada);
                        return;
                    }
                    
                    console.warn('⚠️ ID da entrada não encontrado no botão lixeira');
                }
            }
        });
    },

    abrir(idEntrada) {
        // ✅ CORREÇÃO: Verificar se já está processando
        if (this.processandoExclusao) {
            console.warn('⚠️ Exclusão já em andamento, aguarde...');
            return;
        }

        if (!this.modal) {
            console.error('❌ Modal não inicializado');
            return;
        }

        console.log('🗑️ Abrindo modal para entrada ID:', idEntrada);
        
        this.idEntradaAtual = idEntrada;
        
        // ✅ CORREÇÃO: Reset completo do estado dos botões
        this.resetarEstadoBotoes();
        
        // Remove qualquer estado anterior
        this.modal.classList.remove('ativo');
        
        // Force reflow
        this.modal.offsetHeight;
        
        // Adiciona classe ativo
        this.modal.classList.add('ativo');
        
        // Previne scroll da página
        document.body.style.overflow = 'hidden';

        // Foca no botão cancelar por padrão
        setTimeout(() => {
            this.btnCancelar.focus();
        }, 100);
    },

    // ✅ NOVA FUNÇÃO: Reset completo dos botões
    resetarEstadoBotoes() {
        this.btnConfirmar.disabled = false;
        this.btnCancelar.disabled = false;
        this.btnConfirmar.innerHTML = '<i class="fas fa-trash"></i> Sim, Excluir';
        this.processandoExclusao = false;
    },

    fecharModal() {
        if (!this.modal) return;

        console.log('❌ Fechando modal de exclusão');
        
        this.modal.classList.remove('ativo');
        this.idEntradaAtual = null;
        
        // ✅ CORREÇÃO: Reset estado completo ao fechar
        this.resetarEstadoBotoes();
        
        // Restaura scroll da página
        document.body.style.overflow = '';
    },

    async confirmarExclusao() {
        // ✅ CORREÇÃO: Verificar se já está processando
        if (this.processandoExclusao) {
            console.warn('⚠️ Exclusão já em andamento');
            return;
        }

        if (!this.idEntradaAtual) {
            console.error('❌ ID da entrada não definido');
            return;
        }

        console.log('🗑️ Confirmando exclusão da entrada:', this.idEntradaAtual);
        
        // ✅ CORREÇÃO: Marcar como processando PRIMEIRO
        this.processandoExclusao = true;
        
        // Desabilita botões durante processamento
        this.btnConfirmar.disabled = true;
        this.btnCancelar.disabled = true;
        this.btnConfirmar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Excluindo...';

        try {
            await this.executarExclusao(this.idEntradaAtual);
            
            this.fecharModal();
            this.mostrarToast('✅ Entrada excluída com sucesso!', 'sucesso');

        } catch (error) {
            console.error('❌ Erro ao excluir entrada:', error);
            this.mostrarToast('❌ Erro ao excluir entrada: ' + error.message, 'erro');
            
            // ✅ CORREÇÃO: Reabilita botões em caso de erro
            this.resetarEstadoBotoes();
        }
    },

    async executarExclusao(idEntrada) {
        // Usa a função original do ExclusaoManager se disponível
        if (typeof ExclusaoManager !== 'undefined' && ExclusaoManager.executarExclusaoEntrada) {
            return await ExclusaoManager.executarExclusaoEntrada(idEntrada);
        }

        // Fallback: faz requisição direta
        const response = await fetch('excluir-entrada.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${encodeURIComponent(idEntrada)}`
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const resultado = await response.text();
        
        if (!resultado.toLowerCase().includes('sucesso')) {
            throw new Error(resultado || 'Erro desconhecido');
        }

        // Atualiza o sistema
        await this.atualizarSistema();
        
        return resultado;
    },

    async atualizarSistema() {
        try {
            // ✅ CORREÇÃO: Aguardar um pouco antes de atualizar
            await new Promise(resolve => setTimeout(resolve, 100));

            const atualizacoes = [];

            // Recarrega mentores se função existir
            if (typeof MentorManager !== 'undefined' && MentorManager.recarregarMentores) {
                atualizacoes.push(MentorManager.recarregarMentores());
            }

            // Atualiza dados financeiros se função existir
            if (typeof DadosManager !== 'undefined' && DadosManager.atualizarLucroEBancaViaAjax) {
                atualizacoes.push(DadosManager.atualizarLucroEBancaViaAjax());
            }

            await Promise.all(atualizacoes);

            // ✅ CORREÇÃO: Atualizar tela de edição após as atualizações
            const telaEdicaoAberta = document.getElementById('tela-edicao')?.style.display === 'block';
            if (telaEdicaoAberta && typeof TelaEdicaoManager !== 'undefined' && typeof MentorManager !== 'undefined') {
                setTimeout(() => {
                    if (MentorManager.mentorAtualId) {
                        TelaEdicaoManager.editarAposta(MentorManager.mentorAtualId);
                    }
                }, 500); // ✅ Tempo maior para garantir atualização completa
            }

            // Atualiza meta se existir
            if (typeof MetaDiariaManager !== 'undefined' && MetaDiariaManager.atualizarMetaDiaria) {
                setTimeout(() => {
                    MetaDiariaManager.atualizarMetaDiaria();
                }, 200);
            }

            console.log('✅ Sistema atualizado após exclusão');
        } catch (error) {
            console.warn('⚠️ Erro ao atualizar sistema:', error);
        }
    },

    mostrarToast(mensagem, tipo = 'info') {
        // Usa ToastManager se existir
        if (typeof ToastManager !== 'undefined') {
            ToastManager.mostrar(mensagem, tipo);
            return;
        }

        // Fallback simples
        console.log(`📢 ${tipo.toUpperCase()}: ${mensagem}`);
        
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            z-index: 1000000;
            animation: slideInRight 0.3s ease-out;
            font-family: "Poppins", sans-serif;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        `;

        const cores = {
            sucesso: '#28a745',
            erro: '#dc3545',
            info: '#17a2b8',
            aviso: '#ffc107'
        };

        toast.style.backgroundColor = cores[tipo] || cores.info;
        toast.textContent = mensagem;

        document.body.appendChild(toast);

        setTimeout(() => {
            toast.remove();
        }, 4000);
    }
};

// ===== SISTEMA NOVO DE CADASTRO CORRIGIDO =====
const SistemaCadastroNovo = {
  config: {
    AVATAR_PADRAO: "https://cdn-icons-png.flaticon.com/512/847/847969.png",
    TIMEOUT_ANIMACAO: 300,
    TIMEOUT_STATUS: 200,
  },

  estado: {
    mentorId: null,
    tipoOperacao: null,
    valorEntrada: 0,
    valorTotal: 0,
    valorRed: 0,
    formularioAberto: false,
    processandoSubmissao: false, // ✅ NOVA FLAG PARA EVITAR DUPLA SUBMISSÃO
  },

  elementos: {},

  inicializar() {
    this.cachearElementos();
    this.configurarEventos();
    this.configurarMascaras();
    this.integrarComSistemaExistente();
    
    console.log("🎯 Sistema Novo de Cadastro inicializado com sucesso");
  },

  cachearElementos() {
    this.elementos = {
      formulario: document.getElementById('formulario-mentor-novo'),
      btnFechar: document.querySelector('.btn-fechar-novo'),
      mentorFoto: document.querySelector('.mentor-foto-novo'),
      mentorNome: document.querySelector('.mentor-nome-novo'),
      mentorIdInput: document.querySelector('.mentor-id-novo'),
      tipoOperacaoInput: document.querySelector('.tipo-operacao-novo'),
      
      opcoesCash: document.querySelector('[data-tipo="cash"]'),
      opcoesGreen: document.querySelector('[data-tipo="green"]'),
      opcoesRed: document.querySelector('[data-tipo="red"]'),
      
      inputsDuplos: document.getElementById('inputs-duplos'),
      inputEntrada: document.getElementById('input-entrada'),
      inputTotal: document.getElementById('input-total'),
      labelTotal: document.getElementById('label-total'),
      
      inputUnico: document.getElementById('input-unico'),
      inputRed: document.getElementById('input-red'),
      
      statusContainer: document.querySelector('.status-calculo-novo'),
      rotuloStatus: document.getElementById('rotulo-status'),
      valorStatus: document.getElementById('valor-status'),
      
      form: document.getElementById('form-mentor-novo'),
      btnEnviar: document.querySelector('.botao-enviar-novo'),
    };
  },

  configurarEventos() {
    // Opções Cash, Green, Red
    document.querySelectorAll('.opcao-novo').forEach(opcao => {
      opcao.addEventListener('click', (e) => {
        const tipo = opcao.dataset.tipo;
        this.selecionarTipo(tipo);
        
        const valorUndSpan = document.getElementById('valor-unidade');
        if (valorUndSpan) {
          const valorUnd = valorUndSpan.textContent.trim();
          if (tipo === 'red') {
            const inputRed = document.getElementById('input-red');
            if (inputRed && valorUnd && valorUnd !== 'R$ 0,00') {
              inputRed.value = valorUnd;
              setTimeout(() => {
                this.atualizarCalculoRed();
              }, 100);
            }
          } else {
            const inputEntrada = document.getElementById('input-entrada');
            if (inputEntrada && valorUnd && valorUnd !== 'R$ 0,00') {
              inputEntrada.value = valorUnd;
              setTimeout(() => {
                this.atualizarCalculo();
              }, 100);
            }
          }
        }
      });
    });

    // Inputs duplos
    if (this.elementos.inputEntrada) {
      this.elementos.inputEntrada.addEventListener('input', () => {
        this.atualizarCalculo();
      });
    }

    if (this.elementos.inputTotal) {
      this.elementos.inputTotal.addEventListener('input', () => {
        this.atualizarCalculo();
      });
    }

    // Input Red
    if (this.elementos.inputRed) {
      this.elementos.inputRed.addEventListener('input', () => {
        this.atualizarCalculoRed();
      });
    }

    // Formulário
    if (this.elementos.form) {
      this.elementos.form.addEventListener('submit', (e) => {
        e.preventDefault();
        this.processarSubmissao(e.target);
      });
    }

    // Botão fechar
    if (this.elementos.btnFechar) {
      this.elementos.btnFechar.addEventListener('click', () => {
        this.fecharFormulario();
      });
    }

    // Fechar ao clicar no overlay
    if (this.elementos.formulario) {
      this.elementos.formulario.addEventListener('click', (e) => {
        if (e.target === this.elementos.formulario) {
          this.fecharFormulario();
        }
      });
    }

    // ESC para fechar
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && this.estado.formularioAberto) {
        this.fecharFormulario();
      }
    });
  },

  configurarMascaras() {
    const inputs = [
      this.elementos.inputEntrada,
      this.elementos.inputTotal,
      this.elementos.inputRed
    ];
    
    inputs.forEach(input => {
      if (input) {
        this.aplicarMascaraMonetaria(input);
      }
    });
  },

  aplicarMascaraMonetaria(input) {
    if (!input.value || input.value === '') {
      input.value = 'R$ 0,00';
    }

    input.addEventListener('input', (e) => {
      let valor = e.target.value.replace(/\D/g, '');
      
      if (valor === '') {
        e.target.value = 'R$ 0,00';
        return;
      }
      
      if (valor.length < 3) {
        valor = valor.padStart(3, '0');
      }
      
      const reais = valor.slice(0, -2);
      const centavos = valor.slice(-2);
      e.target.value = `R$ ${parseInt(reais).toLocaleString('pt-BR')},${centavos}`;
      
      setTimeout(() => {
        if (this.estado.tipoOperacao === 'red') {
          this.atualizarCalculoRed();
        } else {
          this.atualizarCalculo();
        }
      }, 50);
    });

    input.addEventListener('focus', (e) => {
      setTimeout(() => {
        e.target.select();
      }, 50);
    });
  },

  converterParaFloat(valorBRL) {
    if (!valorBRL || typeof valorBRL !== 'string') return 0;
    return parseFloat(
      valorBRL
        .replace(/[^\d,.-]/g, '')
        .replace(/\./g, '')
        .replace(',', '.')
    ) || 0;
  },

  formatarParaBRL(valor) {
    const numero = typeof valor === 'string' ? this.converterParaFloat(valor) : valor;
    return numero.toLocaleString('pt-BR', {
      style: 'currency',
      currency: 'BRL'
    });
  },

  abrirFormulario(card) {
    // ✅ CORREÇÃO: Verificar se já está processando
    if (this.estado.formularioAberto || this.estado.processandoSubmissao) {
      console.warn('⚠️ Formulário já está aberto ou processando');
      return;
    }

    if (!card) {
      console.warn('⚠️ Card não fornecido');
      return;
    }

    const nomeMentor = card.getAttribute('data-nome') || 'Mentor';
    const fotoMentor = card.getAttribute('data-foto') || this.config.AVATAR_PADRAO;
    const idMentor = card.getAttribute('data-id') || '';

    if (!idMentor) {
      console.error('❌ ID do mentor não encontrado');
      if (typeof ToastManager !== 'undefined') {
        ToastManager.mostrar('❌ Erro: ID do mentor não encontrado', 'erro');
      }
      return;
    }

    console.log('🎯 Abrindo formulário para mentor:', nomeMentor, 'ID:', idMentor);

    this.preencherInfoMentor(nomeMentor, fotoMentor, idMentor);
    this.resetarFormulario();
    this.mostrarFormulario();
  },

  preencherInfoMentor(nome, foto, id) {
    if (this.elementos.mentorNome) {
      this.elementos.mentorNome.textContent = nome;
    }

    if (this.elementos.mentorFoto) {
      this.elementos.mentorFoto.src = foto;
      this.elementos.mentorFoto.onerror = () => {
        this.elementos.mentorFoto.src = this.config.AVATAR_PADRAO;
      };
    }

    if (this.elementos.mentorIdInput) {
      this.elementos.mentorIdInput.value = id;
    }

    this.estado.mentorId = id;
  },

  // ✅ FUNÇÃO MODIFICADA: Mostrar formulário com elegância máxima
  mostrarFormulario() {
    if (!this.elementos.formulario) return;

    // ✅ CRIAR E MOSTRAR OVERLAY PRIMEIRO
    this.criarOverlayElegante();

    // Prevenir scroll da página
    document.body.classList.add('modal-aberto');

    // ✅ MOSTRAR FORMULÁRIO COM TRANSIÇÃO SUAVE
    this.elementos.formulario.style.display = 'block';
    this.elementos.formulario.offsetHeight; // Force reflow
    
    // ✅ TIMING PERFEITO PARA ANIMAÇÕES
    requestAnimationFrame(() => {
      this.elementos.formulario.classList.add('ativo');
    });
    
    this.estado.formularioAberto = true;

    // Mostrar mensagem inicial com transição elegante
    const mensagemInicial = document.getElementById('mensagem-inicial-gestao');
    if (mensagemInicial) {
      // Ocultar inputs primeiro
      if (this.elementos.inputsDuplos) {
        this.elementos.inputsDuplos.classList.remove('ativo');
        this.elementos.inputsDuplos.style.display = 'none';
      }
      if (this.elementos.inputUnico) {
        this.elementos.inputUnico.classList.remove('ativo');
        this.elementos.inputUnico.style.display = 'none';
      }
      
      // ✅ MOSTRAR MENSAGEM COM DELAY ELEGANTE
      setTimeout(() => {
        mensagemInicial.style.display = 'block';
        mensagemInicial.offsetHeight; // Force reflow
        mensagemInicial.classList.add('ativo');
      }, 200); // Delay para sincronizar com abertura do formulário
    }

    // ✅ FOCO SUAVE APÓS ANIMAÇÃO COMPLETA
    setTimeout(() => {
      const primeiroInput = this.elementos.formulario.querySelector('input[type="text"]:not([style*="display: none"])');
      if (primeiroInput) {
        primeiroInput.focus();
      }
    }, 600); // Tempo total da animação
  },

  // ✅ NOVA FUNÇÃO: Criar overlay elegante
  criarOverlayElegante() {
    // Remove overlay existente se houver
    const overlayExistente = document.getElementById('formulario-overlay-elegante');
    if (overlayExistente) {
      overlayExistente.remove();
    }

    // Criar novo overlay
    const overlay = document.createElement('div');
    overlay.id = 'formulario-overlay-elegante';
    overlay.className = 'formulario-mentor-overlay';
    
    document.body.appendChild(overlay);
    
    // ✅ ATIVAR OVERLAY COM TRANSIÇÃO SUAVE
    requestAnimationFrame(() => {
      overlay.classList.add('ativo');
    });

    // ✅ FECHAR AO CLICAR NO OVERLAY
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) {
        this.fecharFormulario();
      }
    });
  },

  // ✅ FUNÇÃO MODIFICADA: Fechar formulário com elegância
  fecharFormulario() {
    if (!this.elementos.formulario || !this.estado.formularioAberto) {
      return;
    }

    console.log('🎭 Fechando formulário com elegância...');

    // ✅ ANIMAÇÃO DE FECHAMENTO ELEGANTE
    this.elementos.formulario.classList.remove('ativo');
    this.elementos.formulario.classList.add('fechando');

    // ✅ FECHAR OVERLAY SUAVEMENTE
    const overlay = document.getElementById('formulario-overlay-elegante');
    if (overlay) {
      overlay.classList.remove('ativo');
      
      // Remover overlay após transição
      setTimeout(() => {
        overlay.remove();
      }, 400);
    }
    
    // ✅ LIMPEZA APÓS ANIMAÇÃO COMPLETA
    setTimeout(() => {
      this.elementos.formulario.style.display = 'none';
      this.elementos.formulario.classList.remove('fechando');
      this.resetarFormulario();
      this.estado.formularioAberto = false;
      
      // ✅ RESTAURAR SCROLL COM SUAVIDADE
      document.body.classList.remove('modal-aberto');
    }, 400); // Tempo da animação de fechamento
  },

  selecionarTipo(tipo) {
    if (!['cash', 'green', 'red'].includes(tipo)) {
      return;
    }

    document.querySelectorAll('.opcao-novo').forEach(opcao => {
      opcao.classList.remove('selecionada');
      const radio = opcao.querySelector('input[type="radio"]');
      if (radio) radio.checked = false;
    });

    const mensagemInicial = document.getElementById('mensagem-inicial-gestao');
    if (mensagemInicial) {
      mensagemInicial.style.opacity = '0';
      
      setTimeout(() => {
        mensagemInicial.classList.remove('ativo');
        mensagemInicial.style.display = 'none';
        
        if (tipo === 'red') {
          if (this.elementos.inputsDuplos) {
            this.elementos.inputsDuplos.classList.remove('ativo');
            this.elementos.inputsDuplos.style.display = 'none';
          }
          if (this.elementos.inputUnico) {
            this.elementos.inputUnico.style.display = 'block';
            this.elementos.inputUnico.offsetHeight;
            this.elementos.inputUnico.classList.add('ativo');
          }
        } else {
          if (this.elementos.inputUnico) {
            this.elementos.inputUnico.classList.remove('ativo');
            this.elementos.inputUnico.style.display = 'none';
          }
          if (this.elementos.inputsDuplos) {
            this.elementos.inputsDuplos.style.display = 'block';
            this.elementos.inputsDuplos.offsetHeight;
            this.elementos.inputsDuplos.classList.add('ativo');
          }
        }
      }, 300);
    }

    document.querySelectorAll('.mensagem-status-input').forEach(msg => {
      msg.style.display = 'none';
    });

    const opcaoSelecionada = document.querySelector(`[data-tipo="${tipo}"]`);
    if (opcaoSelecionada) {
      opcaoSelecionada.classList.add('selecionada');
      const radio = opcaoSelecionada.querySelector('input[type="radio"]');
      if (radio) radio.checked = true;
    }

    if (this.elementos.tipoOperacaoInput) {
      this.elementos.tipoOperacaoInput.value = tipo;
    }

    this.mostrarCamposParaTipo(tipo);
    this.estado.tipoOperacao = tipo;

    setTimeout(() => {
      if (tipo === 'red') {
        this.atualizarCalculoRed();
      } else {
        this.atualizarCalculo();
      }
    }, this.config.TIMEOUT_STATUS);
  },

  mostrarCamposParaTipo(tipo) {
    if (tipo === 'red') {
      if (this.elementos.inputsDuplos) {
        this.elementos.inputsDuplos.classList.remove('ativo');
      }
      if (this.elementos.inputUnico) {
        this.elementos.inputUnico.classList.add('ativo');
      }
    } else {
      if (this.elementos.inputUnico) {
        this.elementos.inputUnico.classList.remove('ativo');
      }
     if (this.elementos.inputsDuplos) {
       this.elementos.inputsDuplos.classList.add('ativo');
     }
     
     if (this.elementos.labelTotal) {
       const textoLabel = tipo === 'cash' ? 'Total: Cash' : 'Total: Green';
       this.elementos.labelTotal.textContent = textoLabel;
     }
   }
 },

 atualizarCalculo() {
   if (this.estado.tipoOperacao === 'red') return;

   const entrada = this.converterParaFloat(this.elementos.inputEntrada?.value || '0');
   const total = this.converterParaFloat(this.elementos.inputTotal?.value || '0');

   this.estado.valorEntrada = entrada;
   this.estado.valorTotal = total;

   const inputEntradaMsg = this.elementos.inputEntrada?.nextElementSibling;
   const inputTotalMsg = this.elementos.inputTotal?.nextElementSibling;
   
   const valorUndSpan = document.getElementById('valor-unidade');
   const valorCarregado = valorUndSpan ? this.converterParaFloat(valorUndSpan.textContent) : 0;
   
   if (inputEntradaMsg) {
     if (entrada === 0) {
       inputEntradaMsg.style.display = 'none';
     } else if (entrada > valorCarregado) {
       inputEntradaMsg.style.display = 'block';
       inputEntradaMsg.textContent = 'Atenção! Você está operando fora dos parâmetros de gestão isso pode comprometer o controle e a estratégia.';
       inputEntradaMsg.className = 'mensagem-status-input negativo animar';
     } else if (entrada < valorCarregado) {
       inputEntradaMsg.style.display = 'block';
       inputEntradaMsg.textContent = 'Parabéns! Você está operando com um valor abaixo da gestão, o que demonstra controle e responsabilidade.';
       inputEntradaMsg.className = 'mensagem-status-input positivo animar';
     }
   }

   if (inputTotalMsg) {
     inputTotalMsg.style.display = 'none';
   }

   const resultado = total - entrada;
   this.atualizarStatus(resultado);
 },

 atualizarCalculoRed() {
   if (this.estado.tipoOperacao !== 'red') return;

   const valorRed = this.converterParaFloat(this.elementos.inputRed?.value || '0');
   this.estado.valorRed = valorRed;

   const inputRedMsg = this.elementos.inputRed?.nextElementSibling;
   
   const valorUndSpan = document.getElementById('valor-unidade');
   const valorCarregado = valorUndSpan ? this.converterParaFloat(valorUndSpan.textContent) : 0;
   
   if (inputRedMsg) {
     if (valorRed === 0) {
       inputRedMsg.style.display = 'none';
     } else if (valorRed > valorCarregado) {
       inputRedMsg.style.display = 'block';
       inputRedMsg.textContent = 'Atenção! Você está operando fora dos parâmetros de gestão isso pode comprometer o controle e a estratégia.';
       inputRedMsg.className = 'mensagem-status-input negativo animar';
     } else {
       inputRedMsg.style.display = 'block';
       inputRedMsg.textContent = 'Calma! Perder faz parte do processo. O mais importante é manter a gestão com foco no longo prazo seguindo a estratégia, o lucro vem naturalmente.';
       inputRedMsg.className = 'mensagem-status-input neutro animar';
     }
   }

   const resultado = -Math.abs(valorRed);
   this.atualizarStatus(resultado);
 },

 atualizarStatus(valor) {
   if (!this.elementos.rotuloStatus || !this.elementos.valorStatus) return;

   this.elementos.valorStatus.classList.remove('status-neutro', 'status-positivo', 'status-negativo');
   this.elementos.statusContainer.classList.remove('status-positivo-ativo', 'status-negativo-ativo');

   let rotulo = 'Neutro';
   let classeStatus = 'status-neutro';
   let classeContainer = '';

   if (valor > 0) {
     rotulo = 'Lucro';
     classeStatus = 'status-positivo';
     classeContainer = 'status-positivo-ativo';
   } else if (valor < 0) {
     rotulo = 'Negativo';
     classeStatus = 'status-negativo';
     classeContainer = 'status-negativo-ativo';
   }

   this.elementos.rotuloStatus.textContent = rotulo;
   this.elementos.valorStatus.textContent = this.formatarParaBRL(Math.abs(valor));
   this.elementos.valorStatus.classList.add(classeStatus);
   
   if (classeContainer) {
     this.elementos.statusContainer.classList.add(classeContainer);
   }

   this.elementos.statusContainer.classList.add('animando');
   setTimeout(() => {
     this.elementos.statusContainer.classList.remove('animando');
   }, 400);
 },

 validarFormulario() {
   if (!this.estado.tipoOperacao) {
     this.mostrarErro('⚠️ Selecione o tipo de operação (Cash, Green ou Red)');
     return false;
   }

   if (this.estado.tipoOperacao === 'red') {
     if (this.estado.valorRed <= 0) {
       this.mostrarErro('⚠️ Informe um valor válido maior que zero para Red');
       this.marcarCampoErro(this.elementos.inputRed);
       return false;
     }
   } else {
     if (this.estado.valorEntrada <= 0) {
       this.mostrarErro('⚠️ Informe um valor válido maior que zero para Entrada');
       this.marcarCampoErro(this.elementos.inputEntrada);
       return false;
     }
     
     if (this.estado.valorTotal <= 0) {
       this.mostrarErro('⚠️ Informe um valor válido maior que zero para Total');
       this.marcarCampoErro(this.elementos.inputTotal);
       return false;
     }
   }

   this.limparErrosCampos();
   return true;
 },

 mostrarErro(mensagem) {
   if (typeof ToastManager !== 'undefined') {
     ToastManager.mostrar(mensagem, 'aviso');
   } else {
     alert(mensagem);
   }
 },

 marcarCampoErro(campo) {
   if (campo) {
     campo.classList.add('erro');
     setTimeout(() => {
       campo.classList.remove('erro');
     }, 3000);
   }
 },

 limparErrosCampos() {
   [this.elementos.inputEntrada, this.elementos.inputTotal, this.elementos.inputRed].forEach(campo => {
     if (campo) {
       campo.classList.remove('erro');
       campo.classList.add('sucesso');
       setTimeout(() => {
         campo.classList.remove('sucesso');
       }, 2000);
     }
   });
 },

 // ✅ CORREÇÃO: Submissão com prevenção de duplicação
 async processarSubmissao(form) {
   console.log('📤 Iniciando submissão...');

   // ✅ CORREÇÃO: Verificar se já está processando
   if (this.estado.processandoSubmissao) {
     console.warn('⚠️ Submissão já em andamento');
     return;
   }

   if (!this.validarFormulario()) {
     return;
   }

   // ✅ CORREÇÃO: Marcar como processando PRIMEIRO
   this.estado.processandoSubmissao = true;

   const dadosEnvio = this.prepararDadosEnvio();
   this.definirEstadoBotao(true);
   
   try {
     console.log('📡 Enviando dados:', dadosEnvio);

     const response = await fetch('cadastrar-valor-novo.php', {
       method: 'POST',
       headers: {
         'Content-Type': 'application/json',
       },
       body: JSON.stringify(dadosEnvio)
     });

     if (!response.ok) {
       throw new Error(`Erro HTTP ${response.status}: ${response.statusText}`);
     }

     const resultado = await response.json();
     console.log('✅ Resposta:', resultado);

     if (typeof ToastManager !== 'undefined') {
       ToastManager.mostrar(resultado.mensagem, resultado.tipo);
     } else {
       alert(resultado.mensagem);
     }

     if (resultado.tipo === 'sucesso') {
       this.fecharFormulario();
       await this.atualizarSistemaExistente();
     }

   } catch (error) {
     console.error('❌ Erro na submissão:', error);
     
     const mensagem = '❌ Erro ao cadastrar valor: ' + error.message;
     if (typeof ToastManager !== 'undefined') {
       ToastManager.mostrar(mensagem, 'erro');
     } else {
       alert(mensagem);
     }
   } finally {
     // ✅ CORREÇÃO: Sempre resetar estado ao final
     this.estado.processandoSubmissao = false;
     this.definirEstadoBotao(false);
   }
 },

 prepararDadosEnvio() {
   const dados = {
     id_mentor: this.estado.mentorId,
     tipo_operacao: this.estado.tipoOperacao,
   };

   if (this.estado.tipoOperacao === 'red') {
     dados.valor_red = this.estado.valorRed;
     dados.valor_green = null;
   } else {
     const resultado = this.estado.valorTotal - this.estado.valorEntrada;
     
     if (resultado >= 0) {
       dados.valor_green = resultado;
       dados.valor_red = null;
     } else {
       dados.valor_green = null;
       dados.valor_red = Math.abs(resultado);
     }
   }

   return dados;
 },

 definirEstadoBotao(carregando) {
   if (!this.elementos.btnEnviar) return;

   if (carregando) {
     this.elementos.btnEnviar.disabled = true;
     this.elementos.btnEnviar.classList.add('carregando');
     this.elementos.btnEnviar.textContent = 'Processando...';
   } else {
     this.elementos.btnEnviar.disabled = false;
     this.elementos.btnEnviar.classList.remove('carregando');
     this.elementos.btnEnviar.textContent = 'Cadastrar';
   }
 },

 async atualizarSistemaExistente() {
   console.log('🔄 Atualizando sistema...');

   const atualizacoes = [];

   if (typeof MentorManager !== 'undefined' && MentorManager.recarregarMentores) {
     atualizacoes.push(MentorManager.recarregarMentores());
   }

   if (typeof DadosManager !== 'undefined' && DadosManager.atualizarLucroEBancaViaAjax) {
     atualizacoes.push(DadosManager.atualizarLucroEBancaViaAjax());
   }

   if (typeof atualizarAreaDireita === 'function') {
     setTimeout(atualizarAreaDireita, 50);
   }

   if (typeof atualizarDadosModal === 'function') {
     setTimeout(atualizarDadosModal, 100);
   }

   if (typeof MetaDiariaManager !== 'undefined' && MetaDiariaManager.atualizarMetaDiaria) {
     setTimeout(() => {
       MetaDiariaManager.atualizarMetaDiaria();
     }, 150);
   }

   try {
     await Promise.all(atualizacoes);
     console.log('✅ Sistema atualizado');
   } catch (error) {
     console.warn('⚠️ Erro ao atualizar:', error);
   }
 },

  resetarFormulario() {
   this.estado = {
     ...this.estado,
     tipoOperacao: null,
     valorEntrada: 0,
     valorTotal: 0,
     valorRed: 0,
     processandoSubmissao: false, // ✅ CORREÇÃO: Reset flag de processamento
   };

   document.querySelectorAll('.mensagem-status-input').forEach(msg => {
     msg.style.display = 'none';
     msg.textContent = '';
   });

   document.querySelectorAll('.opcao-novo').forEach(opcao => {
     opcao.classList.remove('selecionada');
   });

   document.querySelectorAll('input[type="radio"]').forEach(radio => {
     radio.checked = false;
   });   
   
   [this.elementos.inputEntrada, this.elementos.inputTotal, this.elementos.inputRed].forEach(input => {
     if (input) {
       input.value = 'R$ 0,00';
       input.classList.remove('erro', 'sucesso');
     }
   });

   if (this.elementos.inputsDuplos) {
     this.elementos.inputsDuplos.classList.remove('ativo');
   }
   if (this.elementos.inputUnico) {
     this.elementos.inputUnico.classList.remove('ativo');
   }

   this.atualizarStatus(0);

   if (this.elementos.tipoOperacaoInput) {
     this.elementos.tipoOperacaoInput.value = '';
   }
 },

 fecharFormulario() {
   if (!this.elementos.formulario || !this.estado.formularioAberto) {
     return;
   }

   this.elementos.formulario.classList.remove('ativo');
   
   setTimeout(() => {
     this.elementos.formulario.style.display = 'none';
     this.resetarFormulario();
     this.estado.formularioAberto = false;
     document.body.classList.remove('modal-aberto');
   }, this.config.TIMEOUT_ANIMACAO);
 },

 // ✅ CORREÇÃO: Integração mais robusta para evitar duplo cadastro
 integrarComSistemaExistente() {
   console.log('🔗 Desativando sistema antigo e integrando novo sistema...');
   
   // ✅ DESATIVAR COMPLETAMENTE O SISTEMA ANTIGO
   this.desativarSistemaAntigo();
   
   // ✅ INTERCEPTAR APENAS UMA VEZ com flag de controle
   let listenerAdicionado = false;
   
   if (!listenerAdicionado) {
     document.addEventListener('click', (e) => {
       const card = e.target.closest('.mentor-card');
       
       if (card && !this.isClickNoMenu(e) && !this.estado.formularioAberto) {
         e.preventDefault();
         e.stopPropagation();
         e.stopImmediatePropagation(); // ✅ CORREÇÃO: Para TODOS os eventos
         
         console.log('🎯 Interceptando clique para novo sistema - Card:', card.getAttribute('data-nome'));
         this.abrirFormulario(card);
         return false; // ✅ CORREÇÃO: Impedir bubbling
       }
     }, true); // ✅ CORREÇÃO: Usar capture phase
     
     listenerAdicionado = true;
     console.log('✅ Event listener único adicionado');
   }

   // Observer para novos cards
   const observer = new MutationObserver((mutations) => {
     mutations.forEach((mutation) => {
       if (mutation.type === 'childList') {
         mutation.addedNodes.forEach((node) => {
           if (node.nodeType === 1 && node.classList.contains('mentor-card')) {
             console.log('🔄 Novo card detectado, desativando sistema antigo');
             this.desativarSistemaAntigo();
           }
         });
       }
     });
   });

   const containerMentores = document.getElementById('listaMentores');
   if (containerMentores) {
     observer.observe(containerMentores, {
       childList: true,
       subtree: true
     });
   }
 },

 // ✅ NOVA FUNÇÃO: Desativar sistema antigo
 desativarSistemaAntigo() {
   // Desativar FormularioValorManager se existir
   if (typeof FormularioValorManager !== 'undefined') {
     FormularioValorManager.exibirFormularioMentor = () => {
       console.log('🚫 FormularioValorManager desativado - usando novo sistema');
     };
   }

   // Desativar funções globais antigas
   if (typeof window.exibirFormularioMentor === 'function') {
     window.exibirFormularioMentor = () => {
       console.log('🚫 exibirFormularioMentor desativado - usando novo sistema');
     };
   }

   // Remover onclick dos cards
   document.querySelectorAll('.mentor-card').forEach(card => {
     if (card.onclick) {
       card.onclick = null;
     }
     card.removeAttribute('onclick');
   });
   
   console.log('🚫 Sistema antigo desativado');
 },

 isClickNoMenu(event) {
   const elementosIgnorar = [
     '.menu-toggle',
     '.menu-opcoes', 
     '.btn-icon',
     '.btn-lixeira',
     'button',
     'i[class*="fa"]'
   ];

   return elementosIgnorar.some(seletor => {
     return event.target.closest(seletor) !== null;
   });
 },
};

// ===== FUNÇÕES GLOBAIS PARA COMPATIBILIDADE =====

// Função global para abrir modal de exclusão de entrada
window.abrirModalExclusaoEntrada = function(idEntrada) {
    ModalExclusaoEntrada.abrir(idEntrada);
};

// Funções do sistema novo de cadastro
window.abrirFormularioNovo = (card) => {
 SistemaCadastroNovo.abrirFormulario(card);
};

window.fecharFormularioNovo = () => {
 SistemaCadastroNovo.fecharFormulario();
};

// ✅ CORREÇÃO: Desativar funções antigas globalmente
window.FormularioValorManager_DESATIVADO = true;

// ===== INICIALIZAÇÃO AUTOMÁTICA CORRIGIDA =====
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Inicializando sistemas corrigidos...');
    
    // Inicializa modal de exclusão
    ModalExclusaoEntrada.inicializar();
    
    // Inicializa sistema de cadastro novo após um delay
    setTimeout(() => {
        SistemaCadastroNovo.inicializar();
        console.log('🎯 Sistemas inicializados com correções aplicadas!');
    }, 200);
});

// Para compatibilidade se o DOMContentLoaded já passou
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        ModalExclusaoEntrada.inicializar();
        setTimeout(() => SistemaCadastroNovo.inicializar(), 200);
    });
} else {
    ModalExclusaoEntrada.inicializar();
    setTimeout(() => SistemaCadastroNovo.inicializar(), 200);
}

// Disponibiliza globalmente para debug
window.SistemaCadastroNovo = SistemaCadastroNovo;
window.ModalExclusaoEntrada = ModalExclusaoEntrada;

console.log('🎯 ===== SISTEMA CORRIGIDO CARREGADO =====');
console.log('✅ Modal de Exclusão: SEM dupla exclusão');
console.log('✅ Sistema de Cadastro: SEM cadastro duplo');
console.log('✅ Sistema Antigo: DESATIVADO completamente');
console.log('🔗 Integração: EXCLUSIVA com novo sistema');
console.log('🚀 Pronto para uso!');
</script>



  <div id="mensagem-status" class="toast"></div>
  <div id="toast" class="toast hidden"></div>

</div>
<!-- ==================================================================================================================================== --> 
<!--                                  💼  FIM FORMULARIO DE CADASTRO DE ENTRADA + MODAL EXCLUSÃO DE ENTRADA                              
 ====================================================================================================================================== -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- ==================================================================================================================================== --> 
<!--                                           💼  FORMULARIO GERENCIAMENTO DE BANCA  PAINEL CONTROLE                               
 ====================================================================================================================================== -->

<!-- Link para Font Awesome -->
<!-- Link para Font Awesome -->
<!-- Link Font Awesome e CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="modal-banca.css">

<div class="modal-gerencia-banca">
  <div id="modalDeposito" class="modal-overlay">
    <div class="modal-content">
      <button type="button" class="btn-fechar" id="fecharModal">×</button>
      <form method="POST" action="">
        <input type="hidden" name="controle_id" value="<?= isset($controle_id) ? htmlspecialchars($controle_id) : '' ?>">
        <input type="hidden" name="acaoBanca" id="acaoBanca">

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
              <label class="label-lucro">
                <i class="fa-solid fa-money-bill-trend-up"></i>
                <span>Lucro</span>
              </label>
              <span id="valorLucroLabel">R$ 0,00</span>
            </div>
          </div>
        </div>

        <!-- Dropdown de Ações -->
        <div class="custom-campo-opcoes">  
          <div class="custom-dropdown">
            <button class="dropdown-toggle" type="button" id="dropdownToggle">
              <i class="fa-solid fa-hand-pointer"></i> Selecione Uma Opção
              <i class="fa-solid fa-chevron-down"></i>
            </button>
            <ul class="dropdown-menu" id="dropdownMenu">
              <li data-value="add"><i class="fa-solid fa-money-bill-wave"></i> Depositar</li>
              <li data-value="sacar"><i class="fa-solid fa-money-bill-transfer"></i> Sacar</li>
              <li data-value="alterar"><i class="fa-solid fa-pen-to-square"></i> Alterar Dados</li>
              <li data-value="resetar"><i class="fa-solid fa-trash-can"></i> Resetar Banca</li>
            </ul>
          </div>

          <!-- Campo de Valor -->
          <div class="banca-wrapper">          
            <input type="text" id="valorBanca" name="valorBanca" placeholder="R$ 0,00">
          </div>

          <!-- Tipo de Meta -->
          <div class="campo-tipo-meta">
            <div class="titulo-meta">Escolha o Tipo de Meta</div>
            <div class="opcoes-meta">
              <div class="opcao-meta">
                <input type="radio" name="tipoMeta" value="fixa" id="metaFixa" checked>
                <label for="metaFixa">Meta Fixa</label>
                <button type="button" class="info-btn" data-modal="modalFixa">
                  <i class="fa-solid fa-circle-question"></i>
                </button>
              </div>
              <div class="opcao-meta">
                <input type="radio" name="tipoMeta" value="turbo" id="metaTurbo">
                <label for="metaTurbo">Meta Turbo</label>
                <button type="button" class="info-btn" data-modal="modalTurbo">
                  <i class="fa-solid fa-circle-question"></i>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Campos Lado a Lado -->
        <div class="campos-inline">
          <div class="campo-inline">
            <input type="text" name="diaria" id="porcentagem" value="<?= isset($valor_diaria) ? number_format(floatval($valor_diaria), 0) . '%' : '2%' ?>" placeholder="2%">
          </div>
          <div class="campo-inline">
            <input type="text" name="unidade" id="unidadeMeta" value="<?= isset($valor_unidade) ? intval($valor_unidade) : '2' ?>" placeholder="2">
          </div>
          <div class="campo-inline">
            <input type="text" name="odds" id="oddsMeta" value="<?= isset($valor_odds) ? number_format(floatval($valor_odds), 2, ',', '') : '1,70' ?>" placeholder="1,70">
          </div>
        </div>

        <!-- Labels e Explicações Abaixo dos Campos -->
        <div class="labels-explicacao">
          <div class="label-com-explicacao">
            <label for="porcentagem">Porcentagem</label>
            <button type="button" class="info-btn" data-modal="modalPorcentagem">
              <i class="fa-solid fa-circle-question"></i>
            </button>
          </div>
          <div class="label-com-explicacao">
            <label for="unidadeMeta">Qtd de Unidade</label>
            <button type="button" class="info-btn" data-modal="modalUnidade">
              <i class="fa-solid fa-circle-question"></i>
            </button>
          </div>
          <div class="label-com-explicacao">
            <label for="oddsMeta">Odds Min.</label>
            <button type="button" class="info-btn" data-modal="modalOdds">
              <i class="fa-solid fa-circle-question"></i>
            </button>
          </div>
        </div>

        <!-- Campo de Resultados dos Cálculos -->
        <div class="campo-resultados">
          <div class="titulo-resultados">📊 Resumo dos Cálculos</div>
          
          <div class="resultado-item">
            <span class="resultado-label">Sua Unidade de Entrada Nas Apostas é:</span>
            <span class="resultado-valor" id="resultadoUnidadeEntrada">R$ 20,00</span>
          </div>
          
          <div class="resultado-item">
            <span class="resultado-label">Sua Meta do Dia é:</span>
            <span class="resultado-valor" id="resultadoMetaDia">R$ 60,00</span>
          </div>
          
          <div class="resultado-item">
            <span class="resultado-label">Sua Meta do Mês é:</span>
            <span class="resultado-valor" id="resultadoMetaMes">R$ 1.800,00</span>
          </div>
          
          <div class="resultado-item">
            <span class="resultado-label">Sua Meta do Ano é:</span>
            <span class="resultado-valor" id="resultadoMetaAno">R$ 21.600,00</span>
          </div>
          
          <div class="resultado-item">
            <span class="resultado-label">Para Bater a Meta do Dia Fazer:</span>
            <span class="resultado-valor" id="resultadoEntradas">5 Entradas Positivas</span>
          </div>
        </div>

        <!-- Confirmação de Reset -->
        <div id="confirmarReset" class="mensagem-reset">
          Tem certeza que deseja <strong>resetar sua banca</strong>? Essa ação é irreversível.
          <div class="botoes-reset">
            <button type="button" id="btnConfirmarReset" class="btn-reset-confirmar">Sim, Resetar</button>
            <button type="button" id="btnCancelarReset" class="btn-reset-cancelar">Cancelar</button>
          </div>
        </div>

        <div id="toastModal"></div>
        <input type="button" id="botaoAcao" value="Cadastrar Dados" class="custom-button">
      </form>
    </div>
  </div>
</div>


<!-- ==================================================================================================================================== --> 
<!--                                         💼  FIM FORMULARIO GERENCIAMENTO DE BANCA  PAINEL CONTROLE                               
 ====================================================================================================================================== -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- Toast geral da página -->
<div id="toast-msg" class="toast hidden">Mensagem</div>
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- ==================================================================================================================================== --> 
<!--                                         💼  ATUALIZA A ZONA DE DATA E HORARIO                              
 ====================================================================================================================================== -->

<script>
// Definir timezone se o elemento existir
const timezoneInput = document.getElementById('timezone');
if (timezoneInput) {
  timezoneInput.value = Intl.DateTimeFormat().resolvedOptions().timeZone;
}
</script>
<!-- ==================================================================================================================================== --> 
<!--                                         💼  FIM ATUALIZA A ZONA DE DATA E HORARIO                              
 ====================================================================================================================================== -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- ==================================================================================================================================== --> 
<!--                                         💼  FECHA O MEU AO CLICAR FORA                             
 ====================================================================================================================================== -->
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
<!-- ==================================================================================================================================== --> 
<!--                                         💼  FIM FECHA O MEU AO CLICAR FORA                            
 ====================================================================================================================================== -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- ==================================================================================================================================== --> 
<!--                                         💼  VERIFICAR O QUE É                           
 ====================================================================================================================================== -->
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
<!-- ==================================================================================================================================== --> 
<!--                                         💼  FIM VERIFICAR O QUE É                           
 ====================================================================================================================================== -->
 <!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
    <script src="js/modal-confirmacao.js"></script>
</body>
</html>