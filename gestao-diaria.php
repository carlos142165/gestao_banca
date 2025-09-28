    
    
    
    
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

    // Se for requisição AJAX, retornar JSON rápido para o client processar sem redirecionamento
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
              strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if ($isAjax) {
      // Obter último ID inserido para retornar dados básicos do mentor
      $mentorInfo = null;
      try {
        if ($acao === 'cadastrar_mentor') {
          $lastId = $conexao->insert_id;
          if ($lastId && $lastId > 0) {
            $stmt_info = $conexao->prepare('SELECT id, nome, foto FROM mentores WHERE id = ? AND id_usuario = ? LIMIT 1');
            $stmt_info->bind_param('ii', $lastId, $usuario_id);
            $stmt_info->execute();
            $mentorInfo = $stmt_info->get_result()->fetch_assoc();
          }
        } elseif ($acao === 'editar_mentor') {
          // Retornar os dados do mentor editado
          $stmt_info = $conexao->prepare('SELECT id, nome, foto FROM mentores WHERE id = ? AND id_usuario = ? LIMIT 1');
          $stmt_info->bind_param('ii', $mentor_id, $usuario_id);
          $stmt_info->execute();
          $mentorInfo = $stmt_info->get_result()->fetch_assoc();
        }
      } catch (Exception $e) {
        // não fatal — apenas não teremos dados extras
        $mentorInfo = null;
      }

      $responseJson = [
        'success' => true,
        'mensagem' => $mensagem_sucesso ?? 'Operação realizada',
        'mentor' => $mentorInfo,
      ];

      header('Content-Type: application/json');
      echo json_encode($responseJson);
      exit();
    }

    // Fallback para requisição padrão — redireciona
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

// 🚀 Agregar dados por mês para o ano atual (usado no bloco anual)
try {
  $sql_ano = "
    SELECT 
      MONTH(data_criacao) AS mes,
      SUM(CASE WHEN green = 1 THEN valor_green ELSE 0 END) AS total_valor_green,
      SUM(CASE WHEN red = 1 THEN valor_red ELSE 0 END) AS total_valor_red,
      SUM(CASE WHEN green = 1 THEN 1 ELSE 0 END) AS total_green,
      SUM(CASE WHEN red = 1 THEN 1 ELSE 0 END) AS total_red
    FROM valor_mentores
    WHERE id_usuario = ? AND YEAR(data_criacao) = ?
    GROUP BY MONTH(data_criacao)
  ";

  $stmt_ano = $conexao->prepare($sql_ano);
  $stmt_ano->bind_param('ii', $id_usuario_logado, $ano);
  $stmt_ano->execute();
  $result_ano = $stmt_ano->get_result();

  $dados_por_mes = [];
  while ($row = $result_ano->fetch_assoc()) {
    $mes_chave = str_pad($row['mes'], 2, '0', STR_PAD_LEFT);
    $dados_por_mes[$mes_chave] = $row;
  }
} catch (Exception $e) {
  $dados_por_mes = [];
  setToast('Erro ao carregar dados do ano!', 'erro');
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
<link rel="stylesheet" href="css/estilo-gestao-diaria-novo.css">
<link rel="stylesheet" href="css/estilo-campo-mes.css">
<link rel="stylesheet" href="css/menu-topo.css">
<link rel="stylesheet" href="css/modais.css">
<link rel="stylesheet" href="css/estilo-painel-controle.css">
<link rel="stylesheet" href="css/toast.css">
<link rel="stylesheet" href="css/toast-modal-gerencia.css">
<link rel="stylesheet" href="css/blocos.css">
<link rel="stylesheet" href="css/ano.css">



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
<script src="js/ano.js" defer></script>
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
<!--                                                            💼                   
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
<!-- -->
<!-- -->
<!-- -->
<!-- ==================================================================================================================== -->
<!-- ========================== MENU TOPO - MENU TOPO - MENU TOPO - MENU TOPO - MENU TOPO ======================== -->
<!-- ==================================================================================================================== -->
 
 <header class="header">
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
 </header>
    
    <main class="main-content">
        <div class="container">
<!-- ==================================================================================================================== -->
<!-- ========================== MENU TOPO - MENU TOPO - MENU TOPO - MENU TOPO - MENU TOPO ======================== -->
<!-- ==================================================================================================================== -->
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
<!-- -->
<!-- -->
<!-- -->
<!-- ==================================================================================================================== -->
<!-- ========================== BLOCO 1 - BLOCO 1 - BLOCO 1 - BLOCO 1 - BLOCO 1 ======================== -->
<!-- ==================================================================================================================== -->
            
    <div class="bloco bloco-1">
        <div class="container-resumos">
            
            <!-- Widget Meta Diária -->
            <div class="widget-meta-container">
                <div class="widget-meta-row">
                    <div class="widget-meta-item" id="widget-meta">
                        
                        <!-- Header com data e seleção de período -->
                        <div class="data-header-integrada" id="data-header">
                            <div class="data-texto-compacto">
                                <i class="fa-solid fa-calendar-days"></i>
                                <span class="data-principal-integrada" id="data-atual"></span>
                            </div>
                                
                            <!-- Seleção de período -->
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
                            
                            <div class="espaco-equilibrio"></div>
                            <div class="data-separador-mini"></div>
                            
                            <div class="status-periodo-mini" id="status-periodo">
                                <!-- Status período será preenchido via JS -->
                            </div>
                        </div>

                        <!-- Conteúdo principal do widget meta -->
                        <div class="widget-conteudo-principal">
                            <div class="conteudo-left">
                                
                                <!-- Valor da Meta -->
                                <div class="widget-meta-valor" id="meta-valor">
                                    <i class="fa-solid fa-coins"></i>
                                    <div class="meta-valor-container">
                                        <span class="valor-texto" id="valor-texto-meta">carregando..</span>
                                    </div>
                                </div>
                                
                                <!-- Valor que ultrapassou a meta -->
                                <div class="valor-ultrapassou" id="valor-ultrapassou" style="display: none;">
                                    <i class="fa-solid fa-trophy"></i>
                                    <span class="texto-ultrapassou">Lucro Extra: <span id="valor-extra">R$ 0,00</span></span>
                                </div>
                                
                                <div class="widget-meta-rotulo" id="rotulo-meta">Meta do Dia</div>
                                
                                <!-- Barra de Progresso -->
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
            
            <!-- Campo Mentores -->
            <div class="campo_mentores">
                <!-- Barra superior com controles -->
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

                <!-- Lista de mentores -->
                <div id="listaMentores" class="mentor-wrapper">
                    <!-- MENTORES SERÃO CARREGADOS DINAMICAMENTE VIA PHP -->
                    <!-- Exemplo de estrutura de mentor: -->
                    <div class="mentor-item" style="display: none;">
                        <div class="mentor-rank-externo">1º</div>
                        <div class="mentor-card card-positivo" data-nome="" data-foto="" data-id="">
                            <div class="mentor-header">
                                <img src="" alt="" class="mentor-img" />
                                <h3 class="mentor-nome">Nome do Mentor</h3>
                            </div>
                            <div class="mentor-right">
                                <div class="mentor-values-inline">
                                    <div class="value-box-green green">
                                        <p>Green</p>
                                        <p>0</p>
                                    </div>
                                    <div class="value-box-red red">
                                        <p>Red</p>
                                        <p>0</p>
                                    </div>
                                    <div class="value-box-saldo saldo">
                                        <p>Saldo</p>
                                        <p>R$ 0,00</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mentor-menu-externo">
                            <span class="menu-toggle" title="Mais opções">⋮</span>
                            <div class="menu-opcoes">
                                <button onclick="editarAposta(0)">
                                    <i class="fas fa-trash"></i> Excluir Entrada
                                </button>
                                <button onclick="editarMentor(0)">
                                    <i class="fas fa-user-edit"></i> Editar Mentor
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Elementos auxiliares para cálculos JavaScript -->
                    <div id="total-green-dia" data-green="0" style="display:none;"></div>
                    <div id="total-red-dia" data-red="0" style="display:none;"></div>
                    <div id="saldo-dia" data-total="0,00" style="display:none;"></div>
                </div>
            </div>
        </div>
    </div>
<!-- ==================================================================================================================== -->
<!-- ========================== BLOCO 1 - BLOCO 1 - BLOCO 1 - BLOCO 1 - BLOCO 1 ======================== -->
<!-- ==================================================================================================================== -->
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
<!-- -->
<!-- -->
<!-- -->
<!-- ==================================================================================================================== -->
<!-- ========================== BLOCO 2 - BLOCO 2 - BLOCO 2 - BLOCO 2 - BLOCO 2 ======================== -->
<!-- ==================================================================================================================== -->


<div class="bloco bloco-2">
    
    <!-- Resumo do mês -->
    <div class="resumo-mes">
        
        <!-- Cabeçalho do mês -->
        <div class="bloco-meta-simples fixo-topo">
            <div class="campo-armazena-data-placar">
                
                <!-- Título do mês atual -->
                <h2 class="titulo-bloco">
                    <i class="fas fa-calendar-alt"></i> 
                    <span id="tituloMes"></span>
                </h2>

                <script>
                (function() {
                    const meses = [
                        "JANEIRO", "FEVEREIRO", "MARÇO", "ABRIL", "MAIO", "JUNHO",
                        "JULHO", "AGOSTO", "SETEMBRO", "OUTUBRO", "NOVEMBRO", "DEZEMBRO"
                    ];
                    const hoje = new Date();
                    const mesAtual = meses[hoje.getMonth()];
                    const anoAtual = hoje.getFullYear();
                    const tituloEl = document.getElementById("tituloMes");
                    tituloEl.textContent = `${mesAtual} ${anoAtual}`;

                    // Apply the same color to the calendar icon before the title
                    const tituloParent = tituloEl.closest('.titulo-bloco');
                    if (tituloParent) {
                        const iconEl = tituloParent.querySelector('i.fa-calendar-alt');
                        if (iconEl) {
                            const computed = window.getComputedStyle(tituloEl).color;
                            iconEl.style.color = computed;
                        }
                    }
                })();
                </script>

                <!-- Placar mensal CORRIGIDO - sempre visível 0x0 -->
                <div class="area-central-2">
                    <div class="pontuacao-2" id="pontuacao-2">
                        <span class="placar-green-2">0</span>
                        <span class="separador-2">×</span>
                        <span class="placar-red-2">0</span>
                    </div>
                </div>
                
            </div>
        </div>

        <!-- Widget de conteúdo principal -->
        <div class="widget-conteudo-principal-2">
            <div class="conteudo-left-2">
                
                <!-- Valor da meta mensal -->
                <div class="widget-meta-valor-2" id="meta-valor-2">
                    <i class="fa-solid fa-coins"></i>
                    <div class="meta-valor-container-2">
                        <span class="valor-texto-2" id="valor-texto-meta-2">Calculando...</span>
                    </div>
                </div>
                
                <!-- Valor que ultrapassou a meta -->
                <div class="valor-ultrapassou-2" id="valor-ultrapassou-2" style="display: none;">
                    <i class="fa-solid fa-trophy"></i>
                    <span class="texto-ultrapassou-2">
                        Lucro Extra: <span id="valor-extra-2">R$ 0,00</span>
                    </span>
                </div>
                
                <div class="widget-meta-rotulo-2" id="rotulo-meta-2">Meta do Mês</div>
                
                <!-- Barra de progresso mensal -->
                <div class="widget-barra-container-2">
                    <div class="widget-barra-progresso-2" id="barra-progresso-2"></div>
                    <div class="porcentagem-barra-2" id="porcentagem-barra-2">0%</div>
                </div>
                
                <!-- Informações de progresso com saldo -->
                <div class="widget-info-progresso-2">
                    <span id="saldo-info-2" class="saldo-zero-2">
                        <i class="fa-solid fa-wallet"></i>
                        <span class="saldo-info-rotulo-2">Saldo Mês:</span>
                        <span class="saldo-info-valor-2">Calculando...</span>
                    </span>
                </div>
                
            </div>
        </div>

        <!-- Lista de dias do mês -->
        <div class="lista-dias">
            
            <?php
            // Configurações de meta e variáveis
            $meta_diaria = isset($_SESSION['meta_diaria']) ? floatval($_SESSION['meta_diaria']) : 0;
            $meta_mensal = isset($_SESSION['meta_mensal']) ? floatval($_SESSION['meta_mensal']) : 0;
            $meta_anual = isset($_SESSION['meta_anual']) ? floatval($_SESSION['meta_anual']) : 0;

            $periodo_atual = $_SESSION['periodo_filtro'] ?? 'dia';
            $meta_atual = ($periodo_atual === 'mes') ? $meta_mensal : 
                          (($periodo_atual === 'ano') ? $meta_anual : $meta_diaria);

            $hoje = date('Y-m-d');
            $mes_atual = date('m');
            $ano_atual = date('Y');
            $total_dias_mes = date('t');
            
            // Loop através de todos os dias do mês
            for ($dia = 1; $dia <= $total_dias_mes; $dia++) {
                $dia_formatado = str_pad($dia, 2, '0', STR_PAD_LEFT);
                $data_mysql = $ano_atual . '-' . $mes_atual . '-' . $dia_formatado;
                $data_exibicao = $dia_formatado . '/' . $mes_atual . '/' . $ano_atual;
                
                // CORREÇÃO: Garantir valores padrão sempre
                $dados_dia = isset($dados_por_dia[$data_mysql]) ? $dados_por_dia[$data_mysql] : [
                    'total_valor_green' => 0,
                    'total_valor_red' => 0,
                    'total_green' => 0,
                    'total_red' => 0
                ];
                
                // CORREÇÃO: Forçar valores numéricos
                $total_green = isset($dados_dia['total_green']) ? (int)$dados_dia['total_green'] : 0;
                $total_red = isset($dados_dia['total_red']) ? (int)$dados_dia['total_red'] : 0;
                $total_valor_green = isset($dados_dia['total_valor_green']) ? floatval($dados_dia['total_valor_green']) : 0;
                $total_valor_red = isset($dados_dia['total_valor_red']) ? floatval($dados_dia['total_valor_red']) : 0;
                
                // Calcular saldo do dia
                $saldo_dia = $total_valor_green - $total_valor_red;
                $saldo_formatado = number_format($saldo_dia, 2, ',', '.');
                
                // Verificar se meta foi batida
                $meta_batida = false;
                if ($meta_diaria > 0 && $saldo_dia >= $meta_diaria) {
                    $meta_batida = true;
                }
                
                if (!$meta_batida && $data_mysql < $hoje && $saldo_dia > 0) {
                    if ($meta_diaria <= 0) {
                        $meta_batida = true;
                    } elseif ($saldo_dia >= ($meta_diaria * 0.8)) {
                        $meta_batida = true;
                    }
                }
                
                // Determinar classes e estilos visuais
                $classe_valor_cor = '';
                if ($saldo_dia > 0) {
                    $classe_valor_cor = 'valor-positivo';
                } elseif ($saldo_dia < 0) {
                    $classe_valor_cor = 'valor-negativo';
                } else {
                    $classe_valor_cor = 'valor-zero';
                }
                
                $cor_valor = ($saldo_dia == 0) ? 'texto-cinza' : ($saldo_dia > 0 ? 'placar-green' : 'placar-red');
                $classe_texto = ($saldo_dia == 0) ? 'texto-cinza' : '';
                
                // CORREÇÃO: Lógica melhorada para placar cinza
                $placar_cinza = '';
                $eh_saldo_zero = ($saldo_dia == 0);
                $eh_placar_zero = ($total_green === 0 && $total_red === 0);
                
                // Se saldo é zero OU ambos placares são zero, aplicar cinza
                if ($eh_saldo_zero || $eh_placar_zero) {
                    $placar_cinza = 'texto-cinza';
                }
                
                $classes_dia = [];
                
                if ($data_mysql === $hoje) {
                    $classes_dia[] = 'gd-dia-hoje';
                    $classes_dia[] = ($saldo_dia >= 0) ? 'gd-borda-verde' : 'gd-borda-vermelha';
                } else {
                    $classes_dia[] = 'dia-normal';
                }
                
                if ($data_mysql < $hoje) {
                    if ($saldo_dia > 0) {
                        $classes_dia[] = 'gd-dia-destaque';
                    } elseif ($saldo_dia < 0) {
                        $classes_dia[] = 'gd-dia-destaque-negativo';
                    }
                    
                    if ($total_green === 0 && $total_red === 0) {
                        $classes_dia[] = 'gd-dia-sem-valor';
                    }
                }
                
                if ($data_mysql > $hoje) {
                    $classes_dia[] = 'dia-futuro';
                }
                
                $icone_classe = $meta_batida ? 'fa-trophy trofeu-icone' : 'fa-check';
                
                $classe_dia_string = 'gd-linha-dia ' . $classe_valor_cor . ' ' . implode(' ', $classes_dia);
                $data_meta_attr = $meta_batida ? 'true' : 'false';
                $data_saldo_attr = $saldo_dia;
                $data_meta_diaria_attr = $meta_diaria;
                
                // CORREÇÃO: Renderizar linha sempre com placar visível
                echo '
                <div class="'.$classe_dia_string.'" 
                     data-date="'.$data_mysql.'" 
                     data-meta-batida="'.$data_meta_attr.'"
                     data-saldo="'.$data_saldo_attr.'"
                     data-meta-diaria="'.$data_meta_diaria_attr.'"
                     data-periodo-atual="'.$periodo_atual.'"
                     data-green="'.$total_green.'"
                     data-red="'.$total_red.'">
                    
                    <span class="data '.$classe_texto.'">'.$data_exibicao.'</span>

                    <div class="placar-dia" style="display: flex; align-items: center; justify-content: center;">
                        <span class="placar placar-green placar-green-2 '.$placar_cinza.'" 
                              style="display: inline !important; visibility: visible !important;">'.
                              $total_green.
                        '</span>
                        <span class="placar separador separador-2 '.$placar_cinza.'" 
                              style="display: inline !important; visibility: visible !important;">x</span>
                        <span class="placar placar-red placar-red-2 '.$placar_cinza.'" 
                              style="display: inline !important; visibility: visible !important;">'.
                              $total_red.
                        '</span>
                    </div>

                    <span class="valor '.$cor_valor.'">R$ '.$saldo_formatado.'</span>

                    <span class="icone '.$classe_texto.'">
                        <i class="fa-solid '.$icone_classe.'"></i>
                    </span>
                    
                </div>';
            }
            ?>
            
            <!-- Elemento oculto com dados do mês -->
            <div id="dados-mes-info" style="display: none;" 
                 data-mes="<?php echo $mes_atual; ?>" 
                 data-ano="<?php echo $ano_atual; ?>" 
                 data-meta-diaria="<?php echo $meta_diaria; ?>"
                 data-meta-mensal="<?php echo $meta_mensal; ?>"
                 data-meta-anual="<?php echo $meta_anual; ?>"
                 data-periodo-atual="<?php echo $periodo_atual; ?>"
                 data-hoje="<?php echo $hoje; ?>">
            </div>
            
        </div>
    </div>
</div>


<!-- ========================================== -->
<!-- JAVASCRIPT PARA INICIALIZAÇÃO ROBUSTA -->
<!-- ========================================== -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Inicializando sistema completo do Bloco 2...');
    
    // ========================================
    // FUNÇÃO PARA GARANTIR PLACAR MENSAL VISÍVEL
    // ========================================
    function inicializarPlacarMensal() {
        const placar = document.getElementById('pontuacao-2');
        if (!placar) return;
        
        const green = placar.querySelector('.placar-green-2');
        const red = placar.querySelector('.placar-red-2');
        const separador = placar.querySelector('.separador-2');
        
        // Garantir conteúdo inicial
        if (green && (!green.textContent.trim() || green.textContent.trim() === '')) {
            green.textContent = '0';
        }
        
        if (red && (!red.textContent.trim() || red.textContent.trim() === '')) {
            red.textContent = '0';
        }
        
        if (separador && (!separador.textContent.trim() || separador.textContent.trim() === '')) {
            separador.textContent = '×';
        }
        
        // Forçar visibilidade
        if (green) {
            green.style.display = 'inline-block';
            green.style.visibility = 'visible';
            green.style.opacity = '1';
        }
        
        if (red) {
            red.style.display = 'inline-block';
            red.style.visibility = 'visible';
            red.style.opacity = '1';
        }
        
        if (separador) {
            separador.style.display = 'inline-block';
            separador.style.visibility = 'visible';
            separador.style.opacity = '1';
        }
        
        console.log('Placar mensal inicializado:', {
            green: green ? green.textContent : 'N/A',
            red: red ? red.textContent : 'N/A',
            separador: separador ? separador.textContent : 'N/A'
        });
    }
    
    // ========================================
    // FUNÇÃO PARA GARANTIR PLACARES DA LISTA SEMPRE VISÍVEIS
    // ========================================
    function garantirPlacaresListaSempreVisiveis() {
        const linhas = document.querySelectorAll('.gd-linha-dia');
        
        linhas.forEach(linha => {
            const placarContainer = linha.querySelector('.placar-dia');
            if (!placarContainer) return;

            const greenEl = placarContainer.querySelector('.placar-green, .placar.placar-green');
            const redEl = placarContainer.querySelector('.placar-red, .placar.placar-red');
            const separatorEl = placarContainer.querySelector('.separador');

            if (greenEl && redEl && separatorEl) {
                // Garantir que elementos sempre tenham conteúdo
                if (!greenEl.textContent.trim() || greenEl.textContent.trim() === '') {
                    greenEl.textContent = '0';
                }
                
                if (!redEl.textContent.trim() || redEl.textContent.trim() === '') {
                    redEl.textContent = '0';
                }
                
                if (!separatorEl.textContent.trim() || separatorEl.textContent.trim() === '') {
                    separatorEl.textContent = 'x';
                }

                // Forçar visibilidade
                greenEl.style.display = 'inline';
                greenEl.style.visibility = 'visible';
                greenEl.style.opacity = '1';
                
                redEl.style.display = 'inline';
                redEl.style.visibility = 'visible';
                redEl.style.opacity = '1';
                
                separatorEl.style.display = 'inline';
                separatorEl.style.visibility = 'visible';
                separatorEl.style.opacity = '1';

                // Aplicar cores corretas
                const valorElement = linha.querySelector('.valor');
                let ehSaldoZero = false;
                
                if (valorElement) {
                    const valorTexto = valorElement.textContent.trim();
                    ehSaldoZero = valorTexto === 'R$ 0,00' || 
                                 valorTexto === '0,00' || 
                                 valorTexto === 'R$ 0.00' || 
                                 valorTexto === '0.00' ||
                                 linha.classList.contains('valor-zero');
                }

                const greenVal = parseInt(greenEl.textContent.trim()) || 0;
                const redVal = parseInt(redEl.textContent.trim()) || 0;
                const placarZeroValores = greenVal === 0 && redVal === 0;

                if (ehSaldoZero || placarZeroValores) {
                    greenEl.style.setProperty('color', '#94a3b8', 'important');
                    redEl.style.setProperty('color', '#94a3b8', 'important');
                    separatorEl.style.setProperty('color', '#94a3b8', 'important');
                } else {
                    greenEl.style.setProperty('color', '#03a158', 'important');
                    redEl.style.setProperty('color', '#e93a3a', 'important');
                    separatorEl.style.setProperty('color', '#6d6b6b', 'important');
                }
            }
        });
    }
    
    // ========================================
    // VERIFICAÇÃO DE TROFÉUS (sistema existente)
    // ========================================
    console.log('Verificando consistência de troféus após carregamento PHP...');
    
    const linhas = document.querySelectorAll('.gd-linha-dia');
    linhas.forEach(linha => {
        const dataLinha = linha.getAttribute('data-date');
        const metaBatida = linha.getAttribute('data-meta-batida') === 'true';
        const saldo = parseFloat(linha.getAttribute('data-saldo')) || 0;
        
        if (dataLinha && metaBatida) {
            console.log(`PHP marcou ${dataLinha} como meta batida (saldo: R$ ${saldo.toFixed(2)})`);
            
            if (window.MonitorContinuo && window.MonitorContinuo.marcarMetaBatida) {
                setTimeout(() => {
                    window.MonitorContinuo.marcarMetaBatida(dataLinha);
                }, 100);
            }
        }
    });
    
    console.log(`Verificação concluída - ${linhas.length} linhas processadas`);
    
    // ========================================
    // INICIALIZAÇÃO COMPLETA
    // ========================================
    
    // Inicializar placar mensal imediatamente
    inicializarPlacarMensal();
    
    // Inicializar placares da lista imediatamente
    garantirPlacaresListaSempreVisiveis();
    
    // ========================================
    // OBSERVADORES E MONITORES CONTÍNUOS
    // ========================================
    
    // Monitor para placar mensal
    const placar = document.getElementById('pontuacao-2');
    if (placar) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList' || mutation.type === 'characterData') {
                    setTimeout(inicializarPlacarMensal, 50);
                }
            });
        });
        
        observer.observe(placar, {
            childList: true,
            subtree: true,
            characterData: true
        });
    }
    
    // Monitor para placares da lista
    const listaDias = document.querySelector('.lista-dias');
    if (listaDias) {
        const observer = new MutationObserver(() => {
            setTimeout(() => {
                garantirPlacaresListaSempreVisiveis();
            }, 100);
        });

        observer.observe(listaDias, {
            childList: true,
            subtree: true,
            characterData: true
        });
    }
    
    // Verificação periódica para garantir visibilidade
    setInterval(function() {
        inicializarPlacarMensal();
        garantirPlacaresListaSempreVisiveis();
    }, 3000);
    
    console.log('Sistema completo do Bloco 2 inicializado com sucesso!');
});

// ========================================
// COMANDOS GLOBAIS PARA DEBUG
// ========================================

// Comando para forçar placar mensal visível
window.mostrarPlacarMensal = function() {
    const placar = document.getElementById('pontuacao-2');
    if (!placar) {
        console.log('Placar mensal não encontrado');
        return;
    }
    
    const green = placar.querySelector('.placar-green-2');
    const red = placar.querySelector('.placar-red-2');
    const separador = placar.querySelector('.separador-2');
    
    if (green) {
        green.textContent = '0';
        green.style.display = 'inline-block';
        green.style.visibility = 'visible';
    }
    
    if (red) {
        red.textContent = '0';
        red.style.display = 'inline-block';
        red.style.visibility = 'visible';
    }
    
    if (separador) {
        separador.textContent = '×';
        separador.style.display = 'inline-block';
        separador.style.visibility = 'visible';
    }
    
    console.log('Placar mensal forçado para 0×0');
};

// Comando para corrigir todos os placares
window.corrigirTodosPlacares = function() {
    const placarMensal = document.getElementById('pontuacao-2');
    const placaresLista = document.querySelectorAll('.gd-linha-dia .placar-dia');
    
    console.log('Corrigindo todos os placares...');
    
    // Corrigir placar mensal
    mostrarPlacarMensal();
    
    // Corrigir placares da lista
    placaresLista.forEach(placarContainer => {
        const green = placarContainer.querySelector('.placar-green');
        const red = placarContainer.querySelector('.placar-red');
        const separador = placarContainer.querySelector('.separador');
        
        if (green && (!green.textContent.trim())) green.textContent = '0';
        if (red && (!red.textContent.trim())) red.textContent = '0';
        if (separador && (!separador.textContent.trim())) separador.textContent = 'x';
        
        [green, red, separador].forEach(el => {
            if (el) {
                el.style.display = 'inline';
                el.style.visibility = 'visible';
                el.style.opacity = '1';
            }
        });
    });
    
    console.log(`Corrigidos: 1 placar mensal + ${placaresLista.length} placares da lista`);
};

console.log('Bloco 2 carregado com todas as correções!');
console.log('Comandos disponíveis:');
console.log('  mostrarPlacarMensal() - Forçar placar mensal 0×0');
console.log('  corrigirTodosPlacares() - Corrigir todos os placares');
</script>

<!-- Script: posicionar botão quando RED estiver selecionado -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  const formulario = document.getElementById('formulario-mentor-novo');
  if (!formulario) return;

  const radios = formulario.querySelectorAll('input[name="tipo-operacao"], input[name="tipo-operacao"]');

  function atualizarClasseRed() {
    // Procurar input red atualmente marcado
    const redChecked = formulario.querySelector('input[name="tipo-operacao"][value="red"]:checked');
    if (redChecked) {
      formulario.classList.add('red-selecionado');
    } else {
      formulario.classList.remove('red-selecionado');
    }
  }

  // Eventos de change para radios dentro do formulário
  radios.forEach(r => {
    r.addEventListener('change', atualizarClasseRed);
  });

  // Também atualizar ao abrir o formulário (caso já venha pre-selecionado)
  const observer = new MutationObserver(() => {
    if (formulario.classList.contains('ativo')) {
      // aguardar micro-tick para inputs refletirem estado
      setTimeout(atualizarClasseRed, 40);
    }
  });

  observer.observe(formulario, { attributes: true, attributeFilter: ['class'] });

  // Inicial
  atualizarClasseRed();
});
</script>
                                 
<!-- ==================================================================================================================== -->
<!-- ========================== BLOCO 2 - BLOCO 2 - BLOCO 2 - BLOCO 2 - BLOCO 2 ======================== -->
<!-- ==================================================================================================================== -->
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
<!-- -->
<!-- -->
<!-- -->
<!-- ==================================================================================================================== -->
<!-- ========================== BLOCO 3 - BLOCO 3 - BLOCO 3 - BLOCO 3 - BLOCO 3 ======================== -->
<!-- ==================================================================================================================== -->

<!-- BLOCO 3: Dashboard Anual -->
<div class="bloco bloco-3">
    
    <!-- ===== RESUMO DO ANO ===== -->
    <div class="resumo-ano">
        
        <!-- ===== CABEÇALHO DO ANO ===== -->
        <div class="bloco-meta-simples-3 fixo-topo-3">
            <div class="campo-armazena-data-placar-3">
                
                <!-- Título do ano atual -->
                <h2 class="titulo-bloco-3">
                    <i class="fas fa-calendar-alt"></i> 
                    <span id="tituloAno"></span>
                </h2>

                <!-- Placar anual -->
                <div class="area-central-3">
                    <div class="pontuacao-3" id="pontuacao-3">
                        <span class="placar-green-3"></span>
                        <span class="separador-3">×</span>
                        <span class="placar-red-3"></span>
                    </div>
                </div>
                
            </div>
        </div>

        <!-- ===== WIDGET DE CONTEÚDO PRINCIPAL ===== -->
        <div class="widget-conteudo-principal-3">
            <div class="conteudo-left-3">
                
                <!-- Valor da meta anual -->
                <div class="widget-meta-valor-3" id="meta-valor-3">
                    <i class="fa-solid-3 fa-coins-3"></i>
                    <div class="meta-valor-container-3">
                        <span class="valor-texto-3" id="valor-texto-meta-3">carregando..</span>
                    </div>
                </div>
                
                <!-- Valor que ultrapassou a meta -->
                <div class="valor-ultrapassou-3" id="valor-ultrapassou-3" style="display: none;">
                    <i class="fa-solid-3 fa-trophy-3"></i>
                    <span class="texto-ultrapassou-3">
                        Lucro Extra: <span id="valor-extra-3">R$ 0,00</span>
                    </span>
                </div>
                
                <div class="widget-meta-rotulo-3" id="rotulo-meta-3">Meta do Ano</div>
                
                <!-- Barra de progresso anual -->
                <div class="widget-barra-container-3">
                    <div class="widget-barra-progresso-3" id="barra-progresso-3"></div>
                    <div class="porcentagem-barra-3" id="porcentagem-barra-3">0%</div>
                </div>
                
                <!-- Informações de progresso com saldo -->
                <div class="widget-info-progresso-3">
                    <span id="saldo-info-3" class="saldo-positivo-3">
                        <i class="fa-solid-3 fa-chart-line-3"></i>
                        <span class="saldo-info-rotulo-3">Lucro:</span>
                        <span class="saldo-info-valor-3">carregando..</span>
                    </span>
                </div>
                
            </div>
        </div>

<!-- ===== GRÁFICO DE TOTAIS MENSAIS - VERSÃO LIMPA ===== -->
<div class="grafico-mensal-container-3">
    <div class="grafico-mensal-3" id="grafico-mensal-3">
        <div class="grafico-canvas-3">
            <div class="grafico-grid-3"></div>
            <div class="grafico-barras-3" id="grafico-barras-3">
                <!-- Barras serão geradas via JavaScript -->
            </div>
        </div>
        
        <div class="grafico-labels-3" id="grafico-labels-3">
            <!-- Labels serão gerados via JavaScript -->
        </div>
    </div>
</div>

<style>
/* ===== CSS GRÁFICO - VERSÃO LIMPA ===== */

/* RESET COMPLETO */
.grafico-mensal-container-3,
.grafico-mensal-3,
.grafico-canvas-3,
.grafico-grid-3,
.grafico-barras-3,
.grafico-labels-3,
.barra-mes-3,
.label-mes-3 {
    all: unset !important;
}

/* CONTAINER PRINCIPAL */
.grafico-mensal-container-3 {
    position: relative !important;
    display: block !important;
    width: calc(100% - 32px) !important;
    height: 130px !important;
    margin: 12px auto !important;
    padding: 12px !important;
    background: #f3f5f4 !important;
    border-radius: 12px !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06) !important;
    overflow: hidden !important;
    box-sizing: border-box !important;
    font-family: "Rajdhani", sans-serif !important;
}

.grafico-mensal-3 {
    position: relative !important;
    display: flex !important;
    flex-direction: column !important;
    width: 100% !important;
    height: 100% !important;
}

.grafico-canvas-3 {
    position: relative !important;
    display: block !important;
    width: 100% !important;
    height: calc(100% - 18px) !important;
    background: linear-gradient(to top, rgba(248, 250, 252, 0.8) 0%, rgba(241, 245, 249, 0.4) 100%) !important;
    border-radius: 8px !important;
    overflow: hidden !important;
}

/* GRID DE FUNDO */
.grafico-grid-3 {
    position: absolute !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    background-image: 
        linear-gradient(to top, rgba(148, 163, 184, 0.1) 1px, transparent 1px),
        linear-gradient(to right, rgba(148, 163, 184, 0.1) 1px, transparent 1px) !important;
    background-size: 100% 20%, 8.33% 100% !important;
    pointer-events: none !important;
    display: block !important;
}

/* CONTAINER DAS BARRAS */
.grafico-barras-3 {
    position: relative !important;
    display: flex !important;
    align-items: flex-end !important;
    justify-content: space-between !important;
    width: 100% !important;
    height: 100% !important;
    padding: 6px !important;
    box-sizing: border-box !important;
    gap: 2px !important;
    flex-wrap: nowrap !important;
}

/* CONTAINER INDIVIDUAL DE CADA MÊS */
.barra-mes-3 {
    position: relative !important;
    display: flex !important;
    flex-direction: row !important;
    align-items: flex-end !important;
    justify-content: center !important;
    flex: 1 !important;
    height: 100% !important;
    max-width: calc(100% / 12) !important;
    min-width: 20px !important;
    gap: 2px !important;
    flex-shrink: 0 !important;
    flex-grow: 1 !important;
}

/* BARRAS INDIVIDUAIS */
.barra-verde-3,
.barra-vermelha-3 {
    position: relative !important;
    display: block !important;
    width: 7px !important;
    border-radius: 2px 2px 0 0 !important;
    transition: all 0.3s ease !important;
    min-height: 0 !important;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15) !important;
    flex-shrink: 0 !important;
}

.barra-verde-3 {
    background: linear-gradient(to top, #10b981, #34d399) !important;
}

.barra-vermelha-3 {
    background: linear-gradient(to top, #ef4444, #f87171) !important;
}

/* HOVER NAS BARRAS */
.barra-verde-3:hover,
.barra-vermelha-3:hover {
    transform: scale(1.1) !important;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.25) !important;
}

/* CONTAINER DOS LABELS */
.grafico-labels-3 {
    position: relative !important;
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    width: 100% !important;
    height: 18px !important;
    padding: 1px 6px 0 6px !important;
    margin-top: 0px !important;
    box-sizing: border-box !important;
}

/* LABELS INDIVIDUAIS DOS MESES */
.label-mes-3 {
    position: relative !important;
    display: block !important;
    flex: 1 !important;
    text-align: center !important;
    font-family: "Rajdhani", sans-serif !important;
    font-size: 8px !important;
    font-weight: 600 !important;
    color: #64748b !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    max-width: calc(100% / 12) !important;
    line-height: 1 !important;
    padding: 0 !important;
    margin: 0 !important;
    border: none !important;
    background: transparent !important;
}

.label-mes-3.atual {
    color: #10b981 !important;
    font-weight: 700 !important;
}

/* RESPONSIVIDADE */
@media (max-width: 768px) {
    .grafico-mensal-container-3 {
        height: 110px !important;
        margin: 10px auto !important;
        padding: 10px !important;
    }
    .barra-verde-3, .barra-vermelha-3 {
        width: 5px !important;
    }
    .barra-mes-3 {
        min-width: 15px !important;
        gap: 1px !important;
    }
    .label-mes-3 {
        font-size: 7px !important;
    }
    .grafico-labels-3 {
        height: 16px !important;
        padding: 1px 4px 0 4px !important;
    }
}

@media (max-width: 480px) {
    .grafico-mensal-container-3 {
        height: 90px !important;
        margin: 8px auto !important;
        padding: 8px !important;
    }
    .barra-verde-3, .barra-vermelha-3 {
        width: 4px !important;
    }
    .barra-mes-3 {
        min-width: 12px !important;
        gap: 1px !important;
    }
    .label-mes-3 {
        font-size: 6px !important;
    }
    .grafico-labels-3 {
        height: 14px !important;
        padding: 1px 2px 0 2px !important;
    }
}

/* GARANTIR VISIBILIDADE */
.grafico-mensal-container-3 * {
    visibility: visible !important;
    opacity: 1 !important;
}
</style>

<script>
// ===== GRÁFICO LIMPO E FUNCIONAL =====
(function() {
    'use strict';
    
    let containerBarras = null;
    let containerLabels = null;
    let mesAtual = new Date().getMonth() + 1;
    
    // Aguardar elementos DOM
    function aguardarElementos() {
        return new Promise((resolve) => {
            let tentativas = 0;
            const verificar = () => {
                containerBarras = document.getElementById('grafico-barras-3');
                containerLabels = document.getElementById('grafico-labels-3');
                
                if (containerBarras && containerLabels) {
                    resolve();
                } else if (tentativas++ < 20) {
                    setTimeout(verificar, 100);
                } else {
                    resolve();
                }
            };
            verificar();
        });
    }
    
    // Extrair dados do DOM
    function extrairDados() {
        const linhas = document.querySelectorAll('.gd-linha-mes');
        const meses = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
        const abrev = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        
        let dados = abrev.map((mes, i) => ({
            mes: mes,
            indice: i,
            valor: 0,
            green: 0,
            red: 0,
            saldo: 0,
            temDados: false,
            cor: 'neutro'
        }));
        
        linhas.forEach(linha => {
            const dataEl = linha.querySelector('.data-mes');
            if (!dataEl) return;
            
            const texto = dataEl.textContent.toLowerCase();
            let indice = -1;
            
            for (let i = 0; i < meses.length; i++) {
                if (texto.includes(meses[i]) || texto.includes(abrev[i].toLowerCase())) {
                    indice = i;
                    break;
                }
            }
            
            if (indice === -1) return;
            
            const green = parseInt(linha.querySelector('.placar.placar-green')?.textContent || '0') || 0;
            const red = parseInt(linha.querySelector('.placar.placar-red')?.textContent || '0') || 0;
            
            const valorEl = linha.querySelector('.valor');
            let saldo = 0;
            if (valorEl) {
                saldo = parseFloat(valorEl.textContent.replace('R$', '').replace(/\./g, '').replace(',', '.')) || 0;
            }
            
            let valor = 0;
            let cor = 'neutro';
            
            if (saldo > 0) {
                valor = Math.abs(saldo);
                cor = 'verde';
            } else if (saldo < 0) {
                valor = Math.abs(saldo);
                cor = 'vermelho';
            } else if (green > red) {
                valor = green * 100;
                cor = 'verde';
            } else if (red > green) {
                valor = red * 100;
                cor = 'vermelho';
            }
            
            dados[indice] = {
                mes: abrev[indice],
                indice: indice,
                valor: valor,
                green: green,
                red: red,
                saldo: saldo,
                temDados: green > 0 || red > 0 || saldo !== 0,
                cor: cor
            };
        });
        
        return dados;
    }
    
    // Gerar gráfico
    function gerarGrafico() {
        if (!containerBarras || !containerLabels) return;
        
        const dados = extrairDados();
        const valorMax = Math.max(100, ...dados.filter(d => d.temDados).map(d => d.valor));
        
        // Limpar
        containerBarras.innerHTML = '';
        containerLabels.innerHTML = '';
        
        dados.forEach((dado, i) => {
            // Container do mês
            const container = document.createElement('div');
            container.className = 'barra-mes-3';
            
            // Barra se tem dados
            if (dado.temDados && dado.valor > 0) {
                const altura = Math.max(5, Math.min(95, (dado.valor / valorMax) * 100));
                const barra = document.createElement('div');
                barra.className = dado.cor === 'verde' ? 'barra-verde-3' : 'barra-vermelha-3';
                barra.style.height = altura + '%';
                barra.title = `${dado.mes} - Saldo: R$ ${dado.saldo.toFixed(2)} | ${dado.green}x${dado.red}`;
                container.appendChild(barra);
            }
            
            containerBarras.appendChild(container);
            
            // Label
            const label = document.createElement('div');
            label.className = 'label-mes-3';
            label.textContent = dado.mes;
            if (i + 1 === mesAtual) {
                label.classList.add('atual');
            }
            containerLabels.appendChild(label);
        });
    }
    
    // Interceptar AJAX - Versão corrigida
    function configurarAjax() {
        let ajaxMonitorado = false;
        
        // Interceptar fetch com verificação ampla
        const fetchOriginal = window.fetch;
        window.fetch = function(...args) {
            return fetchOriginal.apply(this, arguments).then(response => {
                if (args[0]) {
                    const url = String(args[0]).toLowerCase();
                    // Capturar mais URLs relacionadas
                    if (url.includes('cadastrar') || 
                        url.includes('valor') || 
                        url.includes('excluir') ||
                        url.includes('gestao') ||
                        url.includes('mentor')) {
                        
                        console.log('AJAX fetch detectado:', args[0]);
                        if (!ajaxMonitorado) {
                            ajaxMonitorado = true;
                            setTimeout(() => {
                                console.log('Atualizando gráfico após fetch');
                                gerarGrafico();
                                ajaxMonitorado = false;
                            }, 1500);
                        }
                    }
                }
                return response;
            }).catch(error => {
                // Reset em caso de erro
                ajaxMonitorado = false;
                throw error;
            });
        };
        
        // Interceptar XMLHttpRequest com verificação ampla
        const XHROriginal = window.XMLHttpRequest;
        window.XMLHttpRequest = function() {
            const xhr = new XHROriginal();
            const sendOriginal = xhr.send;
            const openOriginal = xhr.open;
            
            let requestUrl = '';
            
            xhr.open = function(method, url, ...rest) {
                requestUrl = String(url).toLowerCase();
                return openOriginal.apply(this, [method, url, ...rest]);
            };
            
            xhr.send = function(...args) {
                xhr.addEventListener('loadend', function() {
                    const finalUrl = this.responseURL || requestUrl;
                    if (finalUrl && 
                        (finalUrl.includes('cadastrar') || 
                         finalUrl.includes('valor') || 
                         finalUrl.includes('excluir') ||
                         finalUrl.includes('gestao') ||
                         finalUrl.includes('mentor'))) {
                        
                        console.log('AJAX XHR detectado:', finalUrl);
                        if (!ajaxMonitorado) {
                            ajaxMonitorado = true;
                            setTimeout(() => {
                                console.log('Atualizando gráfico após XHR');
                                gerarGrafico();
                                ajaxMonitorado = false;
                            }, 1500);
                        }
                    }
                });
                
                return sendOriginal.apply(this, arguments);
            };
            
            return xhr;
        };
        
        // Interceptar jQuery AJAX se existir
        if (window.jQuery && window.jQuery.ajaxSetup) {
            window.jQuery.ajaxSetup({
                complete: function(xhr, status) {
                    const url = this.url ? String(this.url).toLowerCase() : '';
                    if (url && 
                        (url.includes('cadastrar') || 
                         url.includes('valor') || 
                         url.includes('excluir') ||
                         url.includes('gestao') ||
                         url.includes('mentor'))) {
                        
                        console.log('AJAX jQuery detectado:', this.url);
                        if (!ajaxMonitorado) {
                            ajaxMonitorado = true;
                            setTimeout(() => {
                                console.log('Atualizando gráfico após jQuery');
                                gerarGrafico();
                                ajaxMonitorado = false;
                            }, 1500);
                        }
                    }
                }
            });
        }
        
        // Monitoramento adicional por eventos customizados
        document.addEventListener('valorCadastrado', function() {
            console.log('Evento valorCadastrado detectado');
            setTimeout(gerarGrafico, 1000);
        });
        
        document.addEventListener('valorExcluido', function() {
            console.log('Evento valorExcluido detectado');
            setTimeout(gerarGrafico, 1000);
        });
        
        console.log('Sistema AJAX configurado - Monitorando fetch, XHR, jQuery e eventos');
    }
    
    // Funções públicas
    window.atualizarGrafico = gerarGrafico;
    window.forcarAtualizacaoGrafico = gerarGrafico;
    window.onValorAlterado = gerarGrafico;
    
    // Inicializar
    async function inicializar() {
        await aguardarElementos();
        gerarGrafico();
        configurarAjax();
    }
    
    // Auto-inicializar
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => setTimeout(inicializar, 300));
    } else {
        setTimeout(inicializar, 200);
    }
    
    // Backup de inicialização
    setTimeout(inicializar, 1000);
    
    console.log('Gráfico carregado - AJAX ativo');
})();
</script>

  <!-- ===== LISTA DE MESES DO ANO ===== -->
  <div class="lista-meses">
            
<?php
// ===== CONFIGURAÇÕES E VARIÁVEIS =====
$meta_diaria_ano = isset($_SESSION['meta_diaria']) ? floatval($_SESSION['meta_diaria']) : 0;
$meta_mensal_ano = isset($_SESSION['meta_mensal']) ? floatval($_SESSION['meta_mensal']) : 0;
$meta_anual_ano = isset($_SESSION['meta_anual']) ? floatval($_SESSION['meta_anual']) : 0;

$periodo_atual_ano = $_SESSION['periodo_filtro'] ?? 'ano';
$meta_atual_ano = ($periodo_atual_ano === 'mes') ? $meta_mensal_ano : 
                  (($periodo_atual_ano === 'ano') ? $meta_anual_ano : $meta_diaria_ano);

$hoje_ano = date('Y-m-d');
$mes_atual_ano = date('m');
$ano_atual_ano = date('Y');

// Nomes dos meses
$nomes_meses = [
    1 => 'Janeiro',   2 => 'Fevereiro', 3 => 'Março',     4 => 'Abril',
    5 => 'Maio',      6 => 'Junho',     7 => 'Julho',     8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro',  11 => 'Novembro', 12 => 'Dezembro'
];

// ===== BUSCAR DADOS ANUAIS DO BANCO =====
$dados_por_mes_ano = [];

try {
    $sql_ano = "
        SELECT 
            YEAR(vm.data_criacao) as ano,
            MONTH(vm.data_criacao) as mes,
            COALESCE(SUM(CASE WHEN vm.green = 1 THEN vm.valor_green ELSE 0 END), 0) as total_valor_green,
            COALESCE(SUM(CASE WHEN vm.red = 1 THEN vm.valor_red ELSE 0 END), 0) as total_valor_red,
            COALESCE(SUM(CASE WHEN vm.green = 1 THEN 1 ELSE 0 END), 0) as total_green,
            COALESCE(SUM(CASE WHEN vm.red = 1 THEN 1 ELSE 0 END), 0) as total_red
        FROM valor_mentores vm
        INNER JOIN mentores m ON vm.id_mentores = m.id
        WHERE m.id_usuario = ?
        AND YEAR(vm.data_criacao) = ?
        GROUP BY YEAR(vm.data_criacao), MONTH(vm.data_criacao)
        ORDER BY mes ASC
    ";
    
    $stmt_ano = $conexao->prepare($sql_ano);
    $stmt_ano->bind_param("ii", $id_usuario_logado, $ano_atual_ano);
    $stmt_ano->execute();
    $result_ano = $stmt_ano->get_result();
    
    while ($row = $result_ano->fetch_assoc()) {
        $mes_key = $row['ano'] . '-' . str_pad($row['mes'], 2, '0', STR_PAD_LEFT);
        $dados_por_mes_ano[$mes_key] = $row;
    }
    
    $stmt_ano->close();
    
} catch (Exception $e) {
    error_log("Erro ao buscar dados anuais: " . $e->getMessage());
}

// ===== LOOP ATRAVÉS DE TODOS OS MESES DO ANO =====
for ($mes = 1; $mes <= 12; $mes++) {
    $mes_formatado = str_pad($mes, 2, '0', STR_PAD_LEFT);
    $chave_mes = $ano_atual_ano . '-' . $mes_formatado;
    $nome_mes = $nomes_meses[$mes];
    
    // Usar apenas o nome do mês (sem ano)
    $data_exibicao_mes = $nome_mes;
    
    // ===== BUSCAR DADOS DO MÊS =====
    $dados_mes_ano = isset($dados_por_mes_ano[$chave_mes]) ? $dados_por_mes_ano[$chave_mes] : [
        'total_valor_green' => 0,
        'total_valor_red' => 0,
        'total_green' => 0,
        'total_red' => 0
    ];
    
    // ===== CALCULAR SALDO DO MÊS =====
    $saldo_mes_ano = floatval($dados_mes_ano['total_valor_green']) - floatval($dados_mes_ano['total_valor_red']);
    $saldo_formatado_mes = number_format($saldo_mes_ano, 2, ',', '.');
    
    // ===== VERIFICAR SE META MENSAL FOI BATIDA =====
    $meta_batida_mes = false;
    
    if ($meta_mensal_ano > 0 && $saldo_mes_ano >= $meta_mensal_ano) {
        $meta_batida_mes = true;
    }
    
    // Para meses passados, aplicar critério mais flexível
    if (!$meta_batida_mes && $mes < (int)$mes_atual_ano && $saldo_mes_ano > 0) {
        if ($meta_mensal_ano <= 0) {
            // Sem meta configurada: critério restritivo
            $meta_batida_mes = $saldo_mes_ano >= 500;
        } elseif ($saldo_mes_ano >= ($meta_mensal_ano * 0.8)) {
            $meta_batida_mes = true;
        }
    }
    
    // ===== DETERMINAR CLASSES E ESTILOS VISUAIS =====
    $classe_valor_cor_mes = '';
    if ($saldo_mes_ano > 0) {
        $classe_valor_cor_mes = 'valor-positivo';
    } elseif ($saldo_mes_ano < 0) {
        $classe_valor_cor_mes = 'valor-negativo';
    } else {
        $classe_valor_cor_mes = 'valor-zero';
    }
    
  $cor_valor_mes = ($saldo_mes_ano == 0) ? 'texto-cinza' : ($saldo_mes_ano > 0 ? 'placar-green' : 'placar-red');
    $classe_texto_mes = ($saldo_mes_ano == 0) ? 'texto-cinza' : '';
    $placar_cinza_mes = ((int)$dados_mes_ano['total_green'] === 0 && (int)$dados_mes_ano['total_red'] === 0) ? 'texto-cinza' : '';
    
    $classes_mes_ano = [];
    
    // Verificar se é o mês atual
    if ($mes == (int)$mes_atual_ano) {
        $classes_mes_ano[] = 'gd-mes-hoje';
        $classes_mes_ano[] = ($saldo_mes_ano >= 0) ? 'gd-borda-verde' : 'gd-borda-vermelha';
    } else {
        $classes_mes_ano[] = 'mes-normal';
    }
    
    // Para meses passados
    if ($mes < (int)$mes_atual_ano) {
        if ($saldo_mes_ano > 0) {
            $classes_mes_ano[] = 'gd-mes-destaque';
        } elseif ($saldo_mes_ano < 0) {
            $classes_mes_ano[] = 'gd-mes-destaque-negativo';
        }
        
        if ((int)$dados_mes_ano['total_green'] === 0 && (int)$dados_mes_ano['total_red'] === 0) {
            $classes_mes_ano[] = 'gd-mes-sem-valor';
        }
    }
    
    // Para meses futuros
    if ($mes > (int)$mes_atual_ano) {
        $classes_mes_ano[] = 'mes-futuro';
    }
    
    $icone_classe_mes = $meta_batida_mes ? 'fa-trophy trofeu-icone' : 'fa-check';
    
    // ===== MONTAR CLASSES E ATRIBUTOS FINAIS =====
    $classe_mes_string = 'gd-linha-mes ' . $classe_valor_cor_mes . ' ' . implode(' ', $classes_mes_ano);
    $data_meta_attr_mes = $meta_batida_mes ? 'true' : 'false';
    $data_saldo_attr_mes = $saldo_mes_ano;
    $data_meta_mensal_attr = $meta_mensal_ano;
    
    // ===== RENDERIZAR LINHA DO MÊS =====
    echo '
    <div class="'.$classe_mes_string.'" 
         data-date="'.$chave_mes.'" 
         data-meta-batida="'.$data_meta_attr_mes.'"
         data-saldo="'.$data_saldo_attr_mes.'"
         data-meta-mensal="'.$data_meta_mensal_attr.'"
         data-periodo-atual="'.$periodo_atual_ano.'">
        
        <span class="data-mes '.$classe_texto_mes.'">'.$data_exibicao_mes.'</span>

    <div class="placar-mes">
      <span class="placar placar-green '.$placar_cinza_mes.'">'.(int)$dados_mes_ano['total_green'].'</span>
      <span class="placar separador '.$placar_cinza_mes.'">×</span>
      <span class="placar placar-red '.$placar_cinza_mes.'">'.(int)$dados_mes_ano['total_red'].'</span>
    </div>

        <span class="valor '.$cor_valor_mes.'">R$ '.$saldo_formatado_mes.'</span>

        <span class="icone '.$classe_texto_mes.'">
            <i class="fa-solid '.$icone_classe_mes.'"></i>
        </span>
        
    </div>';
}
?>

        </div>
        
    </div>
</div>

<!-- ===== ELEMENTO OCULTO COM DADOS DO ANO ===== -->
<div id="dados-ano-info" style="display: none;" 
     data-ano="<?php echo $ano_atual_ano; ?>" 
     data-meta-diaria="<?php echo $meta_diaria_ano; ?>"
     data-meta-mensal="<?php echo $meta_mensal_ano; ?>"
     data-meta-anual="<?php echo $meta_anual_ano; ?>"
     data-periodo-atual="<?php echo $periodo_atual_ano; ?>"
     data-hoje="<?php echo $hoje_ano; ?>">
</div>

<!-- ===== SCRIPTS JAVASCRIPT ===== -->

<!-- Script para definir o título do ano -->
<script>
(function() {
    const hoje = new Date();
    const anoAtual = hoje.getFullYear();
    const tituloEl = document.getElementById("tituloAno");
    tituloEl.textContent = `${anoAtual}`;

    // Aplicar a mesma cor ao ícone do calendário antes do título
    const tituloParent = tituloEl.closest('.titulo-bloco-3');
    if (tituloParent) {
        const iconEl = tituloParent.querySelector('i.fa-calendar-alt');
        if (iconEl) {
            // Calcular a cor efetiva do título (caso CSS a defina)
            const computed = window.getComputedStyle(tituloEl).color;
            iconEl.style.color = computed;
        }
    }
})();
</script>

<!-- Script de verificação de consistência -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Verificando consistência de troféus anuais após carregamento PHP...');
    
    const linhasMeses = document.querySelectorAll('.gd-linha-mes');
    linhasMeses.forEach(linha => {
        const dataLinha = linha.getAttribute('data-date');
        const metaBatida = linha.getAttribute('data-meta-batida') === 'true';
        const saldo = parseFloat(linha.getAttribute('data-saldo')) || 0;
        
        if (dataLinha && metaBatida) {
            console.log(`PHP marcou ${dataLinha} como meta batida (saldo: R$ ${saldo.toFixed(2)})`);
            
            if (window.SistemaTrofeuMensal && window.SistemaTrofeuMensal.aplicarTrofeu) {
                setTimeout(() => {
                    const icone = linha.querySelector('.icone i');
                    if (icone) {
                        window.SistemaTrofeuMensal.aplicarTrofeu(icone, linha);
                    }
                }, 100);
            }
        }
    });
    
    console.log(`Verificação anual concluída - ${linhasMeses.length} meses processados`);
    
    // Forçar atualização após 2 segundos para garantir dados atualizados
    setTimeout(() => {
        if (window.ListaMesesAnual && window.ListaMesesAnual.atualizar) {
            window.ListaMesesAnual.atualizar();
        }
    }, 2000);
});
</script>
        
    </div>
    </div>
</div>
 
    </div>
</div>
    </main>
    
    <footer class="footer"></footer>

<!-- ==================================================================================================================== -->
<!-- ========================== BLOCO 3 - BLOCO 3 - BLOCO 3 - BLOCO 3 - BLOCO 3 ======================== -->
<!-- ==================================================================================================================== -->
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
<!-- Modal de confirmação de exclusão (movido para o final do documento) -->
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

  <!-- ✅ MODAL DE VERIFICAÇÃO DE DEPÓSITO -->
  <div id="modal-verificacao-deposito" class="modal-verificacao-deposito">
    <div class="modal-conteudo-aviso">
      <div class="icone-aviso-deposito">
        <i class="fas fa-exclamation-triangle"></i>
      </div>
      
      <div class="titulo-aviso">Depósito Necessário!</div>
      
      <div class="texto-aviso">
        Você Precisa depositar para Fazer Entradas!
      </div>

      <div class="botoes-aviso">
        <button id="btn-abrir-banca" class="btn-modal-deposito btn-abrir-banca">
          <i class="fas fa-wallet"></i>
          Depositar Agora
        </button>
        <button id="btn-fechar-aviso" class="btn-modal-deposito btn-fechar-aviso">
          <i class="fas fa-times"></i>
          Fechar
        </button>
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
          <p>Disciplina transforma sorte em estratégia. Gestão protege, guia e constrói lucro. Não é sobre vencer sempre, é sobre jogar certo sempre.</p>
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
                    <!-- Mensagem específica para RED (visível somente quando 'Red' estiver selecionado) -->
                
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


/* ===== VARIÁVEIS CSS MODERNAS ===== */
:root {
    /* Cores principais */
    --primary-color: #6366f1;
    --primary-hover: #5b50f0;
    --primary-light: #e0e7ff;
    --secondary-color: #f1f5f9;
    --accent-color: #06b6d4;
    --success-color: #10b981;
    --danger-color: #ef4444;
    --warning-color: #8c0bf5ff;
    --dark-color: #1e293b;
    --light-color: #ffffff;
    
    /* Escala de cinzas */
    --gray-50: #f8fafc;
    --gray-100: #f1f5f9;
    --gray-200: #e2e8f0;
    --gray-300: #cbd5e1;
    --gray-400: #94a3b8;
    --gray-500: #64748b;
    --gray-600: #475569;
    --gray-700: #334155;
    --gray-800: #1e293b;
    --gray-900: #0f172a;
    
    /* Cores de texto */
    --text-primary: #0f172a;
    --text-secondary: #64748b;
    --border-color: #e2e8f0;
    
    /* Sombras */
    --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
    --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
    --shadow-2xl: 0 25px 50px -12px rgb(0 0 0 / 0.25);
    
    /* Gradientes */
    --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --gradient-success: linear-gradient(135deg, #4ade80 0%, #22c55e 100%);
    --gradient-danger: linear-gradient(135deg, #f87171 0%, #ef4444 100%);
    --gradient-glassmorphism: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%);
    
    /* Blur */
    --blur-backdrop: blur(12px);
    
    /* Transições */
    --transition-fast: 0.15s cubic-bezier(0.4, 0, 0.2, 1);
    --transition-normal: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    --transition-slow: 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    --transition-smooth: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

/* ===== RESET E BASE ===== */
.formulario-mentor-novo * {
    box-sizing: border-box;
}

/* ===== MODAIS DE CONFIRMAÇÃO MODERNIZADOS ===== */

/* Modal de confirmação de exclusão de entrada - MODERNIZADO */
.modal-confirmacao-entrada {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    background: rgba(15, 23, 42, 0.6) !important;
    backdrop-filter: var(--blur-backdrop) !important;
    -webkit-backdrop-filter: var(--blur-backdrop) !important;
    z-index: 999999 !important;
    display: none !important;
    justify-content: center !important;
    align-items: center !important;
    opacity: 0;
    transition: all var(--transition-smooth);
    padding: 20px;
}

.modal-confirmacao-entrada.ativo {
    display: flex !important;
    opacity: 1 !important;
}

.modal-confirmacao-entrada .modal-conteudo-exclusao {
    background: rgba(255, 255, 255, 0.95) !important;
    backdrop-filter: blur(20px) !important;
    -webkit-backdrop-filter: blur(20px) !important;
    border-radius: 24px !important;
    padding: 40px !important;
    box-shadow: var(--shadow-2xl) !important;
    border: 1px solid rgba(255, 255, 255, 0.2) !important;
    text-align: center !important;
    max-width: 480px !important;
    width: 90% !important;
    margin: 0 auto !important;
    position: relative !important;
    transform: scale(0.9) translateY(20px);
    transition: transform var(--transition-smooth);
}

.modal-confirmacao-entrada.ativo .modal-conteudo-exclusao {
    transform: scale(1) translateY(0);
}

.modal-confirmacao-entrada .icone-aviso {
    font-size: 64px !important;
    color: var(--danger-color) !important;
    margin-bottom: 24px !important;
    animation: pulse-warning 2s infinite;
}

@keyframes pulse-warning {
    0%, 100% { 
        opacity: 1; 
        transform: scale(1);
    }
    50% { 
        opacity: 0.7; 
        transform: scale(1.05);
    }
}

.modal-confirmacao-entrada .texto-confirmacao {
    font-size: 20px !important;
    font-weight: 700 !important;
    color: var(--text-primary) !important;
    margin-bottom: 32px !important;
    line-height: 1.5 !important;
    font-family: 'Inter', sans-serif;
}

.modal-confirmacao-entrada .texto-confirmacao p:last-child {
    font-size: 14px !important;
    font-weight: 500 !important;
    color: var(--text-secondary) !important;
    margin-top: 12px !important;
}

.modal-confirmacao-entrada .botoes-confirmacao {
    display: flex !important;
    justify-content: center !important;
    gap: 16px !important;
    margin-top: 32px !important;
}

.modal-confirmacao-entrada .btn-modal {
    padding: 16px 32px !important;
    border: none !important;
    border-radius: 16px !important;
    font-size: 16px !important;
    font-weight: 700 !important;
    cursor: pointer !important;
    transition: all var(--transition-smooth) !important;
    min-width: 140px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 8px !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    position: relative !important;
    overflow: hidden !important;
}

.modal-confirmacao-entrada .btn-modal::before {
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

.modal-confirmacao-entrada .btn-modal:hover::before {
    width: 300px;
    height: 300px;
}

.modal-confirmacao-entrada .btn-confirmar-exclusao {
    background: var(--gradient-danger) !important;
    color: white !important;
    box-shadow: var(--shadow-lg) !important;
}

.modal-confirmacao-entrada .btn-confirmar-exclusao:hover {
    transform: translateY(-2px) scale(1.02) !important;
    box-shadow: 0 16px 32px rgba(239, 68, 68, 0.4) !important;
}

.modal-confirmacao-entrada .btn-cancelar-exclusao {
    background: linear-gradient(135deg, var(--gray-400) 0%, var(--gray-500) 100%) !important;
    color: white !important;
    box-shadow: var(--shadow-lg) !important;
}

.modal-confirmacao-entrada .btn-cancelar-exclusao:hover {
    background: linear-gradient(135deg, var(--gray-500) 0%, var(--gray-600) 100%) !important;
    transform: translateY(-2px) scale(1.02) !important;
    box-shadow: 0 16px 32px rgba(148, 163, 184, 0.4) !important;
}

/* ===== MODAL DE VERIFICAÇÃO DE DEPÓSITO MODERNIZADO ===== */
.modal-verificacao-deposito {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    background: rgba(15, 23, 42, 0.7) !important;
    backdrop-filter: var(--blur-backdrop) !important;
    -webkit-backdrop-filter: var(--blur-backdrop) !important;
    z-index: 1000000 !important;
    display: none !important;
    justify-content: center !important;
    align-items: center !important;
    opacity: 0;
    transition: opacity var(--transition-smooth);
    padding: 20px;
}

.modal-verificacao-deposito.ativo {
    display: flex !important;
    opacity: 1 !important;
}

.modal-verificacao-deposito .modal-conteudo-aviso {
    background: rgba(255, 255, 255, 0.95) !important;
    backdrop-filter: blur(20px) !important;
    -webkit-backdrop-filter: blur(20px) !important;
    border-radius: 24px !important;
    padding: 48px !important;
    box-shadow: var(--shadow-2xl) !important;
    border: 1px solid rgba(255, 255, 255, 0.2) !important;
    text-align: center !important;
    max-width: 520px !important;
    width: 90% !important;
    margin: 0 auto !important;
    position: relative !important;
    transform: scale(0.9) translateY(20px);
    transition: transform var(--transition-smooth);
}

.modal-verificacao-deposito.ativo .modal-conteudo-aviso {
    transform: scale(1) translateY(0);
}

.modal-verificacao-deposito .icone-aviso-deposito {
    font-size: 72px !important;
    color: var(--warning-color) !important;
    margin-bottom: 32px !important;
    animation: bounce-warning 2s infinite;
}

@keyframes bounce-warning {
    0%, 100% { 
        transform: translateY(0) scale(1);
    }
    50% { 
        transform: translateY(-8px) scale(1.05);
    }
}

.modal-verificacao-deposito .titulo-aviso {
    font-size: 28px !important;
    font-weight: 800 !important;
    color: var(--text-primary) !important;
    margin-bottom: 16px !important;
    line-height: 1.3 !important;
    font-family: 'Inter', sans-serif;
}

.modal-verificacao-deposito .texto-aviso {
    font-size: 18px !important;
    color: var(--text-secondary) !important;
    margin-bottom: 40px !important;
    line-height: 1.6 !important;
    font-weight: 500;
}

.modal-verificacao-deposito .botoes-aviso {
    display: flex !important;
    justify-content: center !important;
    gap: 16px !important;
    flex-wrap: wrap !important;
}

.modal-verificacao-deposito .btn-modal-deposito {
    padding: 18px 32px !important;
    border: none !important;
    border-radius: 16px !important;
    font-size: 16px !important;
    font-weight: 700 !important;
    cursor: pointer !important;
    transition: all var(--transition-smooth) !important;
    min-width: 160px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 12px !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    position: relative !important;
    overflow: hidden !important;
}

.modal-verificacao-deposito .btn-modal-deposito::before {
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

.modal-verificacao-deposito .btn-modal-deposito:hover::before {
    width: 300px;
    height: 300px;
}

.modal-verificacao-deposito .btn-abrir-banca {
    background: var(--gradient-success) !important;
    color: white !important;
    box-shadow: var(--shadow-lg) !important;
}

.modal-verificacao-deposito .btn-abrir-banca:hover {
    transform: translateY(-3px) scale(1.05) !important;
    box-shadow: 0 20px 40px rgba(16, 185, 129, 0.4) !important;
}

.modal-verificacao-deposito .btn-fechar-aviso {
    background: linear-gradient(135deg, var(--gray-400) 0%, var(--gray-500) 100%) !important;
    color: white !important;
    box-shadow: var(--shadow-lg) !important;
}

.modal-verificacao-deposito .btn-fechar-aviso:hover {
    background: linear-gradient(135deg, var(--gray-500) 0%, var(--gray-600) 100%) !important;
    transform: translateY(-3px) scale(1.05) !important;
    box-shadow: 0 20px 40px rgba(148, 163, 184, 0.4) !important;
}

/* ===== SISTEMA NOVO DE CADASTRO MODERNIZADO ===== */

/* Overlay elegante com glassmorphism */
.formulario-mentor-overlay {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    background: rgba(15, 23, 42, 0.0) !important;
    backdrop-filter: blur(0px) !important;
    -webkit-backdrop-filter: blur(0px) !important;
    z-index: 9998 !important;
    display: none !important;
    opacity: 0 !important;
    transition: all var(--transition-smooth) !important;
}

.formulario-mentor-overlay.ativo {
    display: block !important;
    opacity: 1 !important;
    background: rgba(15, 23, 42, 0.7) !important;
    backdrop-filter: var(--blur-backdrop) !important;
    -webkit-backdrop-filter: var(--blur-backdrop) !important;
}

/* Container principal do formulário modernizado */
.formulario-mentor-novo {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-radius: 24px;
    padding: 24px;
    box-shadow: 
        0 32px 64px rgba(0, 0, 0, 0.12),
        0 16px 32px rgba(0, 0, 0, 0.08),
        inset 0 1px 0 rgba(255, 255, 255, 0.6);
    z-index: 9999;
    display: none;
    width: 480px;
    min-width: 380px;
    max-width: 480px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    margin: 0;
    box-sizing: border-box;
    height: auto;
    min-height: 580px;
    max-height: calc(100vh - 40px);
    overflow-x: hidden;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    opacity: 0;
    transition: all var(--transition-smooth) !important;
}

/* Estados do formulário */
.formulario-mentor-novo.ativo {
    display: block;
    opacity: 1 !important;
    transform: translate(-50%, -50%) scale(1) !important;
}

.formulario-mentor-novo.fechando {
    opacity: 0 !important;
    transform: translate(-50%, -50%) scale(0.95) !important;
    transition: all var(--transition-normal) !important;
}

/* Botão fechar modernizado */
.formulario-mentor-novo .btn-fechar-novo {
    position: absolute;
    top: 16px;
    right: 16px;
    width: 40px;
    height: 40px;
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger-color);
    border: none;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all var(--transition-normal);
    font-size: 16px;
    z-index: 10;
    box-shadow: var(--shadow-sm);
    cursor: pointer;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

.formulario-mentor-novo .btn-fechar-novo:hover {
    background: var(--danger-color);
    color: white;
    transform: scale(1.1) rotate(90deg);
    box-shadow: var(--shadow-lg);
}

/* Info do mentor modernizada */
.mentor-info-novo {
    text-align: center;
    margin-bottom: 24px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--border-color);
    position: relative;
}

.mentor-foto-novo {
    width: 88px;
    height: 88px;
    border-radius: 22px;
    object-fit: cover;
    border: 3px solid var(--primary-color);
    margin-bottom: 12px;
    box-shadow: var(--shadow-lg);
    transition: all var(--transition-normal);
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
    box-shadow: 0 16px 32px rgba(99, 102, 241, 0.3);
}

.mentor-nome-novo {
    font-size: 20px;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
    text-transform: capitalize;
    letter-spacing: -0.02em;
}

/* Sistema de opções modernizado */
.opcoes-container-novo {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin: 24px 0;
    padding: 0;
}

.opcao-novo {
    display: flex;
    flex-direction: column;
    align-items: center;
    cursor: pointer;
    transition: all var(--transition-smooth);
    padding: 16px 12px;
    border-radius: 16px;
    border: 2px solid var(--border-color);
    min-width: auto;
    position: relative;
    overflow: hidden;
    flex: 1;
    background: var(--light-color);
}

.opcao-novo::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: var(--gradient-primary);
    opacity: 0;
    transition: opacity var(--transition-normal);
    z-index: 0;
}

.opcao-novo:hover {
    border-color: var(--primary-color);
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

.opcao-novo.selecionada {
    border-color: var(--primary-color);
    background: var(--primary-light);
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

.opcao-novo.selecionada::before {
    opacity: 0.05;
}

.opcao-novo input[type="radio"] {
    width: 16px;
    height: 16px;
    margin-bottom: 8px;
    cursor: pointer;
    accent-color: var(--primary-color);
    transition: all var(--transition-fast);
    position: relative;
    z-index: 1;
}

.opcao-novo label {
    font-size: 14px;
    font-weight: 600;
    color: var(--text-primary);
    cursor: pointer;
    transition: all var(--transition-normal);
    user-select: none;
    letter-spacing: 0.02em;
    position: relative;
    z-index: 1;
}

.opcao-novo.selecionada label {
    color: var(--primary-color);
    font-weight: 700;
}

/* Mensagem inicial modernizada */
.mensagem-inicial-gestao {
    text-align: center;
    padding: 24px;
    background: linear-gradient(135deg, var(--gray-50) 0%, var(--light-color) 100%);
    border-radius: 20px;
    margin: 16px 0 24px 0;
    box-shadow: var(--shadow-sm);
    opacity: 1;
    transition: all var(--transition-slow);
    display: block;
    transform: translateY(0);
    border: 1px solid var(--border-color);
}

.mensagem-inicial-gestao i {
    font-size: 32px;
    color: var(--primary-color);
    margin-bottom: 16px;
    animation: float-icon 3s ease-in-out infinite;
}

@keyframes float-icon {
    0%, 100% { 
        transform: translateY(0) scale(1); 
    }
    50% { 
        transform: translateY(-6px) scale(1.05); 
    }
}

.mensagem-inicial-gestao p {
    margin: 0;
    font-size: 15px;
    line-height: 1.6;
    color: var(--text-secondary);
    font-weight: 500;
    letter-spacing: 0.01em;
}

/* Área de inputs modernizada */
.inputs-area-novo {
    margin-bottom: 20px;
    min-height: 120px;
    position: relative;
}

.inputs-duplos-novo,
.input-unico-novo {
    opacity: 0;
    transform: translateX(-20px);
    transition: all var(--transition-slow);
    display: none;
    margin-top: 16px;
}

.inputs-duplos-novo:not(.ativo),
.input-unico-novo:not(.ativo) {
    display: none !important;
    opacity: 0 !important;
    visibility: hidden !important;
    height: 0 !important;
    overflow: hidden !important;
}

.inputs-duplos-novo.ativo,
.input-unico-novo.ativo {
    opacity: 1;
    transform: translateX(0);
    display: block;
}

/* Campos duplos modernizados */
.inputs-duplos-novo.ativo {
    display: block !important;
    margin-top: 8px !important;
    margin-bottom: 16px !important;
}

.inputs-duplos-novo.ativo .campo-duplo-novo {
    margin-bottom: 16px !important;
}

.inputs-duplos-novo.ativo .campo-duplo-novo:last-child {
    margin-bottom: 0 !important;
}

.inputs-duplos-novo.ativo .campo-duplo-novo label {
    margin-bottom: 8px !important;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-secondary);
    letter-spacing: 0.02em;
    display: block;
    text-transform: uppercase;
}

.inputs-duplos-novo.ativo .campo-duplo-novo input {
    width: 100%;
    padding: 16px 20px !important;
    margin-bottom: 0 !important;
    border: 2px solid var(--border-color);
    border-radius: 16px;
    font-size: 16px;
    font-weight: 600;
    color: var(--text-primary);
    background: var(--light-color);
    transition: all var(--transition-normal);
    box-sizing: border-box;
    box-shadow: var(--shadow-sm);
    outline: none;
}

/* Campo único (RED) modernizado */
.input-unico-novo {
    margin-top: 28px !important;
    margin-bottom: 36px !important;
    text-align: center;
    padding-bottom: 24px !important;
}

.input-unico-novo.ativo {
    margin-top: 28px !important;
    margin-bottom: 36px !important;
    padding-bottom: 32px !important;
}

.input-unico-novo label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: var(--danger-color);
    margin-bottom: 12px !important;
    letter-spacing: 0.02em;
    text-transform: uppercase;
}

.input-unico-novo input {
    width: 100%;
    padding: 20px !important;
    border: 2px solid rgba(239, 68, 68, 0.2);
    border-radius: 16px;
    font-size: 18px;
    font-weight: 700;
    color: var(--danger-color);
    background: linear-gradient(135deg, #fef2f2 0%, var(--light-color) 100%);
    text-align: center;
    transition: all var(--transition-normal);
    box-sizing: border-box;
    box-shadow: var(--shadow-sm);
    outline: none;
}

/* Estados de foco e hover modernizados */
.campo-duplo-novo input:focus,
.input-unico-novo input:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    transform: translateY(-1px);
}

.input-unico-novo input:focus {
    border-color: var(--danger-color);
    box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1);
}

.campo-duplo-novo input:hover:not(:focus),
.input-unico-novo input:hover:not(:focus) {
    border-color: var(--primary-hover);
    transform: translateY(-1px);
}

/* Estados de erro elegantes */
.campo-duplo-novo input.erro,
.input-unico-novo input.erro {
    border-color: var(--danger-color);
    box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1);
    animation: shake-elegant 0.5s ease-in-out;
}

@keyframes shake-elegant {
    0%, 100% { transform: translateX(0) translateY(-1px); }
    10%, 30%, 50%, 70%, 90% { transform: translateX(-3px) translateY(-1px); }
    20%, 40%, 60%, 80% { transform: translateX(3px) translateY(-1px); }
}

/* Status de cálculo modernizado */
.status-calculo-novo {
    text-align: center;
    padding: 20px;
    border-radius: 20px;
    background: linear-gradient(135deg, var(--gray-50) 0%, var(--light-color) 100%);
    border: 2px solid var(--border-color);
    margin: 20px 0 16px 0;
    min-height: 80px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    transition: all var(--transition-smooth);
    box-shadow: var(--shadow-sm);
}

.rotulo-status-novo {
    font-size: 11px;
    font-weight: 700;
    color: var(--text-secondary);
    letter-spacing: 0.1em;
    margin-bottom: 8px;
    text-transform: uppercase;
    transition: all var(--transition-normal);
}

.valor-status-novo {
    font-size: 24px;
    font-weight: 800;
    transition: all var(--transition-smooth);
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    color: var(--text-primary);
}

.status-calculo-novo.status-positivo-ativo {
    background: linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 100%);
    border-color: rgba(16, 185, 129, 0.3);
    box-shadow: 0 8px 32px rgba(16, 185, 129, 0.1);
}

.status-calculo-novo.status-positivo-ativo .valor-status-novo {
    color: var(--success-color);
}

.status-calculo-novo.status-negativo-ativo {
    background: linear-gradient(135deg, #fef2f2 0%, #fff1f1 100%);
    border-color: rgba(239, 68, 68, 0.3);
    box-shadow: 0 8px 32px rgba(239, 68, 68, 0.1);
}

.status-calculo-novo.status-negativo-ativo .valor-status-novo {
    color: var(--danger-color);
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

/* Botão de envio modernizado */
.botao-enviar-novo {
    width: 100%;
    padding: 18px 24px;
    margin-top: 20px;
    background: var(--gradient-primary);
    color: var(--light-color);
    border: none;
    border-radius: 18px;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    transition: all var(--transition-smooth);
    letter-spacing: 0.05em;
    text-transform: uppercase;
    box-shadow: var(--shadow-lg);
    position: relative;
    overflow: hidden;
    margin-top: 5px !important;
}

.botao-enviar-novo::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s ease;
}

.botao-enviar-novo:hover::before {
    left: 100%;
}

.botao-enviar-novo:hover {
    transform: translateY(-2px);
    box-shadow: 0 16px 32px rgba(99, 102, 241, 0.3);
}

.botao-enviar-novo:active {
    transform: translateY(0);
}

.botao-enviar-novo:disabled {
    background: var(--gray-400);
    cursor: not-allowed;
    transform: none;
    box-shadow: var(--shadow-sm);
}

.botao-enviar-novo.carregando {
    background: var(--gray-400);
    cursor: wait;
    position: relative;
    color: transparent;
}

.botao-enviar-novo.carregando::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 20px;
    height: 20px;
    border: 2px solid transparent;
    border-top: 2px solid white;
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
}

.botao-enviar-novo.bloqueado {
    background: var(--gradient-danger) !important;
    cursor: not-allowed !important;
    opacity: 0.9 !important;
    animation: pulse-blocked 2s infinite;
}

@keyframes pulse-blocked {
    0%, 100% { opacity: 0.9; }
    50% { opacity: 0.7; }
}

.botao-enviar-novo.bloqueado:hover {
    background: var(--gradient-danger) !important;
    transform: none !important;
    box-shadow: var(--shadow-lg) !important;
}

/* Mensagens de status modernizadas */
.mensagem-status-input {
    font-size: 12px;
    margin-top: 8px;
    margin-bottom: 4px;
    line-height: 1.4;
    padding: 10px 14px;
    border-radius: 12px;
    display: block !important;
    text-align: center;
    font-weight: 600;
    max-width: 100%;
    box-sizing: border-box;
    min-height: 20px;
    height: auto;
    transition: 
        opacity var(--transition-smooth), 
        transform var(--transition-smooth),
        background-color var(--transition-normal),
        border-color var(--transition-normal),
        box-shadow var(--transition-normal);
    display: flex !important;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    word-wrap: break-word;
    white-space: normal;
    opacity: 0;
    transform: translateY(-8px);
    background: transparent;
    border: 1px solid transparent;
    box-shadow: none;
}

.mensagem-status-input.positivo {
    opacity: 1 !important;
    transform: translateY(0) !important;
    color: #155724;
    background: linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 100%);
    border: 1px solid rgba(16, 185, 129, 0.2);
    border-left: 3px solid var(--success-color);
    box-shadow: var(--shadow-sm);
}

.mensagem-status-input.negativo {
    opacity: 1 !important;
    transform: translateY(0) !important;
    color: #721c24;
    background: linear-gradient(135deg, #fef2f2 0%, #fff1f1 100%);
    border: 1px solid rgba(239, 68, 68, 0.2);
    border-left: 3px solid var(--danger-color);
    box-shadow: var(--shadow-sm);
}

.mensagem-status-input.neutro {
    opacity: 1 !important;
    transform: translateY(0) !important;
    color: #495057;
    background: linear-gradient(135deg, var(--gray-100) 0%, var(--light-color) 100%);
    border: 1px solid var(--border-color);
    border-left: 3px solid var(--gray-400);
    box-shadow: var(--shadow-sm);
}

.mensagem-status-input.animar {
    animation: fadeInUp-suave 0.5s var(--transition-smooth) forwards;
}

@keyframes fadeInUp-suave {
    0% {
        opacity: 0;
        transform: translateY(-12px) scale(0.98);
    }
    50% {
        opacity: 0.7;
        transform: translateY(-2px) scale(1.01);
    }
    100% {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.mensagem-status-input:empty {
    min-height: 20px;
    opacity: 0;
}

.mensagem-status-input:empty::after {
    content: " ";
    white-space: pre;
    display: block;
    height: 1px;
}

/* Hover suave nas mensagens visíveis */
.mensagem-status-input.positivo:hover,
.mensagem-status-input.negativo:hover,
.mensagem-status-input.neutro:hover {
    transform: translateY(-1px) !important;
    box-shadow: var(--shadow-md);
}

/* Mensagem específica para RED */
.mensagem-red-text {
    margin-bottom: 10px !important;
    margin-top: 0 !important;
    font-size: 14px !important;
    color: #b91c1c !important;
    text-align: center !important;
    padding: 10px 14px 10px 14px !important;
    border-radius: 8px !important;
    background: linear-gradient(90deg, #fef2f2 0%, #f8fafc 100%) !important;
    border: 1px solid rgba(239, 68, 68, 0.13) !important;
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.07) !important;
    transition: all var(--transition-smooth);
    opacity: 0;
    transform: translateY(-8px);
    pointer-events: none;
    display: block;
    width: 100%;
    box-sizing: border-box;
    line-height: 1.5;
    font-weight: 600 !important;
    position: relative;
    z-index: 2;
}

.formulario-mentor-novo.red-selecionado .mensagem-red-text {
    display: block !important;
    opacity: 1 !important;
    transform: translateY(0) !important;
    pointer-events: auto;
}

/* Estados especiais para validação de saldo */
.campo-duplo-novo,
.input-unico-novo {
    margin-bottom: 16px;
    position: relative;
    min-height: auto;
}

.campo-duplo-novo input.sucesso {
    border-color: var(--success-color);
    box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
    animation: success-pulse 0.6s ease-in-out;
}

@keyframes success-pulse {
    0% { 
        transform: scale(1) translateY(-1px); 
        box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1); 
    }
    50% { 
        transform: scale(1.01) translateY(-2px); 
        box-shadow: 0 0 0 8px rgba(16, 185, 129, 0.15); 
    }
    100% { 
        transform: scale(1) translateY(-1px); 
        box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1); 
    }
}

/* Posicionamento especial para RED */
.formulario-mentor-novo.red-selecionado {
    padding-bottom: 100px;
}

.formulario-mentor-novo.red-selecionado .botao-enviar-novo {
    position: absolute;
    left: 24px;
    right: 24px;
    bottom: 24px;
    margin: 0;
    width: auto;
    z-index: 20;
}

.formulario-mentor-novo.red-selecionado .status-calculo-novo {
    position: absolute;
    left: 24px;
    right: 24px;
    bottom: 85px;
    margin: 0;
    z-index: 15;
    width: auto;
    box-shadow: var(--shadow-lg);
}

.formulario-mentor-novo.red-selecionado .input-unico-novo label[for="input-red"] {
    margin-top: 20px;
    display: block;
}

/* Scroll suave e estabilização */
.inputs-area-novo {
    scroll-behavior: smooth;
}

.formulario-mentor-novo .campo-duplo-novo,
.formulario-mentor-novo .input-unico-novo {
    contain: layout style;
    will-change: auto;
}

/* Prevenção de scroll com elegância */
body.modal-aberto {
    overflow: hidden !important;
    padding-right: 0 !important;
    transition: all var(--transition-normal);
}

/* Animações globais elegantes */
@keyframes fadeIn-elegant {
    0% {
        opacity: 0;
        transform: translate(-50%, -54%);
    }
    100% {
        opacity: 1;
        transform: translate(-50%, -50%);
    }
}

@keyframes fadeOut-elegant {
    0% {
        opacity: 1;
        transform: translate(-50%, -50%);
    }
    100% {
        opacity: 0;
        transform: translate(-50%, -46%);
    }
}

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

/* Prevenção de conflitos */
.formulario-mentor-novo .mentor-card,
.formulario-mentor-novo .mentor-item,
.formulario-mentor-novo .formulario-mentor {
    all: initial;
    font-family: 'Inter', sans-serif;
}

/* ===== RESPONSIVIDADE COMPLETA ===== */

/* Tablets e telas médias */
@media (max-width: 768px) {
    .formulario-mentor-novo {
        width: calc(100vw - 40px) !important;
        min-width: 320px !important;
        max-width: calc(100vw - 40px) !important;
        padding: 20px !important;
        margin: 20px;
    }
    
    .mentor-foto-novo {
        width: 72px;
        height: 72px;
    }
    
    .mentor-nome-novo {
        font-size: 18px;
    }
    
    .opcoes-container-novo {
        grid-template-columns: 1fr;
        gap: 8px;
    }
    
    .opcao-novo {
        padding: 12px;
        flex-direction: row;
        justify-content: center;
    }
    
    .opcao-novo input[type="radio"] {
        margin-bottom: 0;
        margin-right: 8px;
    }
}

/* Smartphones */
@media (max-width: 480px) {
    .formulario-mentor-novo {
        width: calc(100vw - 20px) !important;
        min-width: 280px !important;
        max-width: calc(100vw - 20px) !important;
        padding: 16px !important;
        margin: 10px;
        border-radius: 20px;
    }
    
    .formulario-mentor-novo.ativo {
        transform: translate(-50%, -50%) scale(1) !important;
    }
    
    .formulario-mentor-novo.fechando {
        transform: translate(-50%, -50%) scale(0.95) !important;
    }

    .mentor-foto-novo {
        width: 64px;
        height: 64px;
        border-radius: 16px;
    }

    .mentor-nome-novo {
        font-size: 16px;
    }

    .opcoes-container-novo {
        gap: 6px;
        margin: 16px 0;
    }

    .opcao-novo {
        padding: 10px 8px;
        border-radius: 12px;
    }

    .opcao-novo label {
        font-size: 13px;
    }

    .mensagem-inicial-gestao {
        padding: 16px;
        margin: 12px 0 16px 0;
    }

    .mensagem-inicial-gestao i {
        font-size: 28px;
        margin-bottom: 12px;
    }

    .mensagem-inicial-gestao p {
        font-size: 14px;
    }

    .inputs-duplos-novo.ativo .campo-duplo-novo {
        margin-bottom: 12px !important;
    }
    
    .inputs-duplos-novo.ativo .campo-duplo-novo label {
        margin-bottom: 6px !important;
        font-size: 12px;
    }
    
    .inputs-duplos-novo.ativo .campo-duplo-novo input {
        padding: 14px 16px !important;
        font-size: 16px;
        border-radius: 14px;
    }

    .input-unico-novo input {
        padding: 16px !important;
        font-size: 16px;
        border-radius: 14px;
    }

    .status-calculo-novo {
        padding: 16px;
        margin: 16px 0 12px 0;
        min-height: 70px;
        border-radius: 16px;
    }

    .valor-status-novo {
        font-size: 20px;
    }

    .botao-enviar-novo {
        padding: 16px 20px;
        margin-top: 16px !important;
        font-size: 15px;
        border-radius: 16px;
    }

    .mensagem-status-input {
        font-size: 11px;
        min-height: 18px;
        padding: 8px 12px;
        margin-top: 6px;
        margin-bottom: 2px;
        border-radius: 10px;
    }

    .mensagem-red-text {
        margin-top: 8px;
        padding: 12px;
        font-size: 12px;
        border-radius: 12px;
    }

    /* Ajustes para RED em mobile */
    .formulario-mentor-novo.red-selecionado {
        padding-bottom: 80px;
    }

    .formulario-mentor-novo.red-selecionado .botao-enviar-novo {
        left: 16px;
        right: 16px;
        bottom: 16px;
    }

    .formulario-mentor-novo.red-selecionado .status-calculo-novo {
        left: 16px;
        right: 16px;
        bottom: 72px;
    }

    /* Modais responsivos */
    .modal-confirmacao-entrada .modal-conteudo-exclusao,
    .modal-verificacao-deposito .modal-conteudo-aviso {
        padding: 24px !important;
        margin: 16px !important;
        width: calc(100% - 32px) !important;
        border-radius: 20px !important;
    }

    .modal-confirmacao-entrada .botoes-confirmacao,
    .modal-verificacao-deposito .botoes-aviso {
        flex-direction: column !important;
        gap: 12px !important;
    }

    .modal-confirmacao-entrada .btn-modal,
    .modal-verificacao-deposito .btn-modal-deposito {
        width: 100% !important;
        min-width: auto !important;
    }

    .modal-confirmacao-entrada .icone-aviso {
        font-size: 48px !important;
        margin-bottom: 16px !important;
    }

    .modal-verificacao-deposito .icone-aviso-deposito {
        font-size: 56px !important;
        margin-bottom: 20px !important;
    }

    .modal-confirmacao-entrada .texto-confirmacao {
        font-size: 18px !important;
        margin-bottom: 24px !important;
    }

    .modal-verificacao-deposito .titulo-aviso {
        font-size: 22px !important;
        margin-bottom: 12px !important;
    }

    .modal-verificacao-deposito .texto-aviso {
        font-size: 16px !important;
        margin-bottom: 28px !important;
    }
}

/* Muito pequeno - fallback */
@media (max-width: 320px) {
    .formulario-mentor-novo {
        width: calc(100vw - 16px) !important;
        padding: 12px !important;
        margin: 8px;
    }
    
    .opcoes-container-novo {
        grid-template-columns: 1fr;
    }
    
    .opcao-novo {
        padding: 8px;
    }
    
    .mentor-foto-novo {
        width: 56px;
        height: 56px;
    }
    
    .mentor-nome-novo {
        font-size: 15px;
    }
}

/* ===== ANIMAÇÕES E MICRO-INTERAÇÕES FINAIS ===== */

/* Micro animação para cards de opção */
.opcao-novo {
    transition: all var(--transition-normal);
}

.opcao-novo:active {
    transform: translateY(-1px) scale(0.98);
}

/* Animação suave para inputs */
.campo-duplo-novo input,
.input-unico-novo input {
    transition: all var(--transition-normal);
}

.campo-duplo-novo input:focus,
.input-unico-novo input:focus {
    transform: translateY(-1px) scale(1.01);
}

/* Loading spinner melhorado */
@keyframes spin-elegant {
    0% { 
        transform: translate(-50%, -50%) rotate(0deg); 
    }
    100% { 
        transform: translate(-50%, -50%) rotate(360deg); 
    }
}

/* Estados de hover para elementos interativos */
.btn-fechar-novo,
.opcao-novo,
.botao-enviar-novo {
    transition: all var(--transition-normal);
}

.btn-fechar-novo:active,
.opcao-novo:active,
.botao-enviar-novo:active {
    transform: scale(0.98);
}

/* Efeito glassmorphism adicional para elementos importantes */
.mentor-info-novo,
.status-calculo-novo,
.mensagem-inicial-gestao {
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

/* Transições suaves para estados de carregamento */
.botao-enviar-novo.carregando,
.botao-enviar-novo.bloqueado {
    transition: all var(--transition-normal);
}

/* Final: garantir que todas as animações funcionem perfeitamente */
* {
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

/* Scrollbar personalizada para o modal */
.formulario-mentor-novo::-webkit-scrollbar {
    width: 6px;
}

.formulario-mentor-novo::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.05);
    border-radius: 3px;
}

.formulario-mentor-novo::-webkit-scrollbar-thumb {
    background: var(--gray-300);
    border-radius: 3px;
}

.formulario-mentor-novo::-webkit-scrollbar-thumb:hover {
    background: var(--gray-400);
}
</style>

<script>
// 🚫 DESATIVAR SISTEMA ANTIGO COMPLETAMENTE
window.FormularioValorManager_DESATIVADO = true;

// ===== SISTEMA DE VERIFICAÇÃO DE DEPÓSITO =====
const VerificacaoDeposito = {
    modal: null,
    btnAbrirBanca: null,
    btnFecharAviso: null,
    modalBanca: null,

    inicializar() {
        this.modal = document.getElementById('modal-verificacao-deposito');
        this.btnAbrirBanca = document.getElementById('btn-abrir-banca');
        this.btnFecharAviso = document.getElementById('btn-fechar-aviso');
        this.modalBanca = document.querySelector('.modal-gerencia-banca');

        if (!this.modal || !this.btnAbrirBanca || !this.btnFecharAviso) {
            console.error('❌ Elementos do modal de verificação não encontrados');
            return;
        }

        this.configurarEventos();
        console.log('✅ Sistema de verificação de depósito inicializado');
    },

    configurarEventos() {
        this.btnFecharAviso.addEventListener('click', () => {
            this.fecharModalAviso();
        });

        this.btnAbrirBanca.addEventListener('click', () => {
            this.abrirModalBanca();
        });

        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) {
                this.fecharModalAviso();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.modal.classList.contains('ativo')) {
                this.fecharModalAviso();
            }
        });
    },

    async verificarDeposito(idUsuario = null) {
        try {
            const response = await fetch('verificar_deposito.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id_usuario: idUsuario
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const resultado = await response.json();
            console.log('📊 Resultado verificação depósito:', resultado);
            
            return resultado.tem_deposito;
        } catch (error) {
            console.error('❌ Erro ao verificar depósito:', error);
            return false;
        }
    },

    async verificarEPermitirCadastro(card = null) {
        console.log('🔍 Verificando se usuário tem depósito...');

        const temDeposito = await this.verificarDeposito();

        if (!temDeposito) {
            console.log('❌ Usuário sem depósito, exibindo modal de aviso');
            this.mostrarModalAviso();
            return false;
        } else {
            console.log('✅ Usuário tem depósito, permitindo cadastro');
            this.prosseguirComCadastro(card);
            return true;
        }
    },

    mostrarModalAviso() {
        if (!this.modal) return;

        console.log('⚠️ Exibindo modal de verificação de depósito');
        
        this.modal.classList.remove('ativo');
        this.modal.offsetHeight;
        this.modal.classList.add('ativo');
        
        document.body.style.overflow = 'hidden';

        setTimeout(() => {
            this.btnAbrirBanca.focus();
        }, 300);
    },

    fecharModalAviso() {
        if (!this.modal) return;

        console.log('❌ Fechando modal de verificação');
        
        this.modal.classList.remove('ativo');
        document.body.style.overflow = '';
    },

    abrirModalBanca() {
        console.log('🏦 Abrindo modal de gerência de banca');
        
        this.fecharModalAviso();
        
        setTimeout(() => {
            let modalAberto = false;
            
            if (typeof abrirModalDeposito === 'function') {
                try {
                    abrirModalDeposito();
                    modalAberto = true;
                    console.log('Modal aberto via abrirModalDeposito()');
                } catch (e) {
                    console.log('Erro ao usar abrirModalDeposito:', e);
                }
            }
            
            if (!modalAberto) {
                const modalDeposito = document.getElementById('modalDeposito');
                if (modalDeposito) {
                    modalDeposito.style.display = 'flex';
                    modalDeposito.classList.add('ativo');
                    document.body.style.overflow = 'hidden';
                    modalAberto = true;
                    console.log('Modal aberto por ID modalDeposito');

                    try {
                        if (typeof inicializarModalDeposito === 'function') {
                            console.log('Chamando inicializarModalDeposito() após abrir por ID');
                            inicializarModalDeposito();
                        } else if (typeof window.inicializarModalDeposito === 'function') {
                            console.log('Chamando window.inicializarModalDeposito() após abrir por ID');
                            window.inicializarModalDeposito();
                        }
                    } catch (e) {
                        console.warn('Erro ao inicializar modal de deposito automaticamente:', e);
                    }
                }
            }
            
            if (!modalAberto) {
                const modalBanca = document.querySelector('.modal-gerencia-banca, .modal-overlay, .modal-deposito');
                if (modalBanca) {
                    modalBanca.style.display = 'flex';
                    modalBanca.classList.add('ativo');
                    document.body.style.overflow = 'hidden';
                    modalAberto = true;
                    console.log('Modal aberto por classe CSS');

                    try {
                        if (typeof inicializarModalDeposito === 'function') {
                            console.log('Chamando inicializarModalDeposito() após abrir por classe');
                            inicializarModalDeposito();
                        } else if (typeof window.inicializarModalDeposito === 'function') {
                            window.inicializarModalDeposito();
                        }
                    } catch (e) {
                        console.warn('Erro ao inicializar modal de deposito após abrir por classe:', e);
                    }
                }
            }
            
            if (!modalAberto) {
                try {
                    const evento = new CustomEvent('abrirModalBanca', {
                        bubbles: true,
                        detail: { origem: 'verificacao_deposito' }
                    });
                    document.dispatchEvent(evento);
                    console.log('Evento customizado disparado');
                } catch (e) {
                    console.log('Erro ao disparar evento:', e);
                }
            }
            
            if (!modalAberto) {
                console.warn('⚠️ Não foi possível abrir o modal de banca automaticamente');
                
                const botaoTopo = document.getElementById('abrirGerenciaBanca');
                if (botaoTopo) {
                    try {
                        console.log('Tentando acionar o botão de gerência do topo para abrir o modal (fluxo padrão)');
                        botaoTopo.click();
                        return;
                    } catch (e) {
                        console.warn('Erro ao clicar no botão do topo:', e);
                    }
                }

                try {
                    sessionStorage.setItem('abrirModalGerencia', 'true');
                    location.reload();
                } catch (e) {
                    alert('Por favor, clique no botão de "Gerenciar Banca" ou "Depositar" na sua interface principal para fazer um depósito.');
                }
            }
        }, 200);
    },

    prosseguirComCadastro(card) {
        console.log('✅ Prosseguindo com cadastro para:', card ? card.getAttribute('data-nome') : 'Sistema');
        
        if (typeof SistemaCadastroNovo !== 'undefined' && SistemaCadastroNovo.abrirFormulario && card) {
            console.log('🎯 Abrindo formulário via SistemaCadastroNovo');
            setTimeout(() => {
                SistemaCadastroNovo.abrirFormulario(card);
            }, 100);
        } else if (typeof window.abrirFormularioNovo === 'function' && card) {
            console.log('🎯 Abrindo formulário via função global');
            setTimeout(() => {
                window.abrirFormularioNovo(card);
            }, 100);
        } else {
            console.warn('⚠️ Sistema de cadastro não encontrado');
            console.log('SistemaCadastroNovo disponível:', typeof SistemaCadastroNovo !== 'undefined');
            console.log('Card fornecido:', !!card);
            
            if (card) {
                const evento = new CustomEvent('abrirFormularioMentor', {
                    detail: { 
                        card: card,
                        id: card.getAttribute('data-id'),
                        nome: card.getAttribute('data-nome'),
                        origem: 'verificacao_deposito'
                    }
                });
                document.dispatchEvent(evento);
                console.log('📤 Evento customizado disparado para abrir formulário');
            }
        }
    }
};

// ===== SISTEMA DE EXCLUSÃO DE ENTRADA CORRIGIDO =====
const ModalExclusaoEntrada = {
    modal: null,
    btnConfirmar: null,
    btnCancelar: null,
    idEntradaAtual: null,
    processandoExclusao: false,

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
        this.btnCancelar.addEventListener('click', () => {
            this.fecharModal();
        });

        this.btnConfirmar.addEventListener('click', () => {
            this.confirmarExclusao();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.modal.classList.contains('ativo')) {
                this.fecharModal();
            }
        });

        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) {
                this.fecharModal();
            }
        });

        this.modal.querySelector('.modal-conteudo-exclusao').addEventListener('click', (e) => {
            e.stopPropagation();
        });
    },

    integrarComSistemaExistente() {
        if (typeof ExclusaoManager !== 'undefined') {
            ExclusaoManager._excluirEntradaOriginal = ExclusaoManager.excluirEntrada;
            ExclusaoManager.excluirEntrada = (idEntrada) => {
                this.abrir(idEntrada);
            };
        }

        document.addEventListener('click', (e) => {
            if (e.target.matches('.btn-lixeira, .btn-lixeira *, .btn-icon.btn-lixeira, .btn-icon.btn-lixeira *')) {
                e.preventDefault();
                e.stopPropagation();
                
                let button = e.target.closest('.btn-lixeira, .btn-icon');
                if (button) {
                    if (button.onclick) {
                        const onclickStr = button.onclick.toString();
                        const match = onclickStr.match(/excluirEntrada\((\d+)\)/);
                        if (match) {
                            const idEntrada = match[1];
                            this.abrir(idEntrada);
                            return;
                        }
                    }
                    
                    const idEntrada = button.dataset.id || 
                                    button.getAttribute('data-entrada-id') ||
                                    button.closest('[data-entrada-id]')?.dataset.entradaId;
                    
                    if (idEntrada) {
                        this.abrir(idEntrada);
                        return;
                    }
                    
                    console.warn('ID da entrada não encontrado no botão lixeira');
                }
            }
        });
    },

    abrir(idEntrada) {
        if (this.processandoExclusao) {
            console.warn('Exclusão já em andamento, aguarde...');
            return;
        }

        if (!this.modal) {
            console.error('Modal não inicializado');
            return;
        }

        console.log('Abrindo modal para entrada ID:', idEntrada);
        
        this.idEntradaAtual = idEntrada;
        this.resetarEstadoBotoes();
        
        this.modal.classList.remove('ativo');
        this.modal.offsetHeight;
        this.modal.classList.add('ativo');
        
        document.body.style.overflow = 'hidden';

        setTimeout(() => {
            this.btnCancelar.focus();
        }, 100);
    },

    resetarEstadoBotoes() {
        this.btnConfirmar.disabled = false;
        this.btnCancelar.disabled = false;
        this.btnConfirmar.innerHTML = '<i class="fas fa-trash"></i> Sim, Excluir';
        this.processandoExclusao = false;
    },

    fecharModal() {
        if (!this.modal) return;

        console.log('Fechando modal de exclusão');
        
        this.modal.classList.remove('ativo');
        this.idEntradaAtual = null;
        this.resetarEstadoBotoes();
        document.body.style.overflow = '';
    },

    async confirmarExclusao() {
        if (this.processandoExclusao) {
            console.warn('Exclusão já em andamento');
            return;
        }

        if (!this.idEntradaAtual) {
            console.error('ID da entrada não definido');
            return;
        }

        console.log('Confirmando exclusão da entrada:', this.idEntradaAtual);
        
        this.processandoExclusao = true;
        
        this.btnConfirmar.disabled = true;
        this.btnCancelar.disabled = true;
        this.btnConfirmar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Excluindo...';

        try {
            await this.executarExclusao(this.idEntradaAtual);
            
            this.fecharModal();
            this.mostrarToast('Entrada excluída com sucesso!', 'sucesso');

        } catch (error) {
            console.error('Erro ao excluir entrada:', error);
            this.mostrarToast('Erro ao excluir entrada: ' + error.message, 'erro');
            this.resetarEstadoBotoes();
        }
    },

    async executarExclusao(idEntrada) {
        if (typeof ExclusaoManager !== 'undefined' && ExclusaoManager.executarExclusaoEntrada) {
            return await ExclusaoManager.executarExclusaoEntrada(idEntrada);
        }

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

        await this.atualizarSistema();
        return resultado;
    },

    async atualizarSistema() {
        try {
            await new Promise(resolve => setTimeout(resolve, 100));

            const atualizacoes = [];

            if (typeof MentorManager !== 'undefined' && MentorManager.recarregarMentores) {
                atualizacoes.push(MentorManager.recarregarMentores());
            }

            if (typeof DadosManager !== 'undefined' && DadosManager.atualizarLucroEBancaViaAjax) {
                atualizacoes.push(DadosManager.atualizarLucroEBancaViaAjax());
            }

            await Promise.all(atualizacoes);

            const telaEdicaoAberta = document.getElementById('tela-edicao')?.style.display === 'block';
            if (telaEdicaoAberta && typeof TelaEdicaoManager !== 'undefined' && typeof MentorManager !== 'undefined') {
                setTimeout(() => {
                    if (MentorManager.mentorAtualId) {
                        TelaEdicaoManager.editarAposta(MentorManager.mentorAtualId);
                    }
                }, 500);
            }

            if (typeof MetaDiariaManager !== 'undefined' && MetaDiariaManager.atualizarMetaDiaria) {
                setTimeout(() => {
                    MetaDiariaManager.atualizarMetaDiaria();
                }, 200);
            }

            console.log('Sistema atualizado após exclusão');
        } catch (error) {
            console.warn('Erro ao atualizar sistema:', error);
        }
    },

    mostrarToast(mensagem, tipo = 'info') {
        if (typeof ToastManager !== 'undefined') {
            ToastManager.mostrar(mensagem, tipo);
            return;
        }

        console.log(`${tipo.toUpperCase()}: ${mensagem}`);
        
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

// ===== SISTEMA NOVO DE CADASTRO COM VERIFICAÇÃO DE SALDO =====
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
        processandoSubmissao: false,
        saldoInsuficiente: false,
    },

    elementos: {},
  overlayAtual: null,
  saldoCache: null,
  _verificarSaldoTimeout: null,

    inicializar() {
        this.cachearElementos();
        this.configurarEventos();
        this.configurarMascaras();
        this.integrarComSistemaExistente();
        
        console.log("Sistema Novo de Cadastro inicializado com sucesso");
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
        document.querySelectorAll('.opcao-novo').forEach(opcao => {
            opcao.addEventListener('click', (e) => {
                const tipo = opcao.dataset.tipo;
                this.selecionarTipo(tipo);
                
                const valorUndSpan = document.getElementById('valor-unidade');
                if (valorUndSpan) {
                    const valorUnd = valorUndSpan.textContent.trim();
                    
                    setTimeout(() => {
                        if (tipo === 'red') {
                            const inputRed = document.getElementById('input-red');
                            if (inputRed && valorUnd && valorUnd !== 'R$ 0,00') {
                                inputRed.value = valorUnd;
                                setTimeout(() => {
                                    this.atualizarCalculoRed();
                                    this.verificarSaldoInput(inputRed);
                                }, 200);
                            } else {
                                setTimeout(() => {
                                    this.mostrarMensagemAutomaticaRed();
                                }, 300);
                            }
                        } else {
                            const inputEntrada = document.getElementById('input-entrada');
                            if (inputEntrada && valorUnd && valorUnd !== 'R$ 0,00') {
                                inputEntrada.value = valorUnd;
                                setTimeout(() => {
                                    this.atualizarCalculo();
                                }, 200);
                            }
                        }
                    }, 400);
                }
            });
        });

        if (this.elementos.inputEntrada) {
            this.elementos.inputEntrada.addEventListener('input', () => {
                this.atualizarCalculo();
            });
        }

        if (this.elementos.inputTotal) {
            this.elementos.inputTotal.addEventListener('input', () => {
                this.atualizarCalculo();
                this.verificarSaldoInput(this.elementos.inputTotal);
            });
        }

        if (this.elementos.inputRed) {
            this.elementos.inputRed.addEventListener('input', () => {
                this.atualizarCalculoRed();
                this.verificarSaldoInput(this.elementos.inputRed);
            });
        }

        if (this.elementos.form) {
            this.elementos.form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.processarSubmissao(e.target);
            });
        }

        if (this.elementos.btnFechar) {
            this.elementos.btnFechar.addEventListener('click', () => {
                this.fecharFormulario();
            });
        }

        if (this.elementos.formulario) {
            this.elementos.formulario.addEventListener('click', (e) => {
                if (e.target === this.elementos.formulario) {
                    this.fecharFormulario();
                }
            });
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.estado.formularioAberto) {
                this.fecharFormulario();
            }
        });
    },

    // FUNÇÃO CORRIGIDA PARA VERIFICAÇÃO DE SALDO
    verificarSaldoInput(inputElement) {
        if (!inputElement) {
            console.warn('Input element não fornecido');
            return;
        }
        
    // Debounce curto para garantir que a máscara de input tenha terminado de modificar o valor
    if (this._verificarSaldoInputDelay) clearTimeout(this._verificarSaldoInputDelay);
    this._verificarSaldoInputDelay = setTimeout(() => {
      const valorDigitado = inputElement.value;
      const mensagem = inputElement.parentElement.querySelector('.mensagem-status-input');
        
        if (!mensagem) {
            console.warn('Elemento .mensagem-status-input não encontrado para:', inputElement.id);
            // Criar o elemento se não existir
            const novoElemento = document.createElement('div');
            novoElemento.className = 'mensagem-status-input';
            novoElemento.style.cssText = 'display: block; margin-top: 5px; font-size: 12px; font-weight: 600; min-height: 16px;';
            inputElement.parentElement.appendChild(novoElemento);
            console.log('Elemento .mensagem-status-input criado para:', inputElement.id);
        }

            const mensagemElement = inputElement.parentElement.querySelector('.mensagem-status-input');

            console.log('=== VERIFICAÇÃO DE SALDO ===');
            console.log('Input ID:', inputElement.id);
            console.log('Valor digitado:', valorDigitado);

            // Converter diretamente para centavos e manter float secundário
            const valorCentavosInicial = this.converterParaCentavos(valorDigitado);
            const valorConvertido = valorCentavosInicial / 100;
            console.log('Valor convertido (float):', valorConvertido, ' - centavos:', valorCentavosInicial);

      if (valorConvertido <= 0) {
      console.log('Valor zero ou negativo, não verificando saldo');
      // limpar mensagem
      if (mensagemElement) {
        mensagemElement.textContent = '';
        mensagemElement.classList.remove('negativo');
        mensagemElement.style.opacity = '0';
      }
        return;
    }

  const avaliarComparacao = (saldoDisponivelRaw) => {
      const saldoDisponivel = parseFloat(saldoDisponivelRaw) || 0;
      // Use centavos inteiros para evitar erros de ponto-flutuante
  const valorCentavos = this.converterParaCentavos(valorConvertido);
            const saldoCentavos = this.converterParaCentavos(saldoDisponivel);

      console.log('Comparando (centavos) (cache/servidor):', valorCentavos, '>', saldoCentavos, '=', valorCentavos > saldoCentavos);

      if (valorCentavos > saldoCentavos) {
        this.estado.saldoInsuficiente = true;
        if (mensagemElement) {
        mensagemElement.textContent = `Saldo Insuficiente! Disponível: R$ ${(saldoCentavos/100).toLocaleString('pt-BR', {minimumFractionDigits:2})}`;
          mensagemElement.classList.add('negativo');
          mensagemElement.classList.remove('positivo', 'neutro');
          mensagemElement.style.opacity = '1';
          mensagemElement.style.display = 'block';
          mensagemElement.style.color = '#dc3545';
        }
        if (this.elementos.btnEnviar) {
          this.elementos.btnEnviar.disabled = true;
          this.elementos.btnEnviar.classList.add('bloqueado');
          this.elementos.btnEnviar.textContent = 'Saldo Insuficiente';
          this.elementos.btnEnviar.style.backgroundColor = '#dc3545';
          this.elementos.btnEnviar.style.cursor = 'not-allowed';
        }
      } else {
        this.estado.saldoInsuficiente = false;
        if (mensagemElement && mensagemElement.classList.contains('negativo') && mensagemElement.textContent.includes('Saldo Insuficiente')) {
          mensagemElement.textContent = '';
          mensagemElement.classList.remove('negativo');
          mensagemElement.style.opacity = '0';
        }
        if (this.elementos.btnEnviar && !this.estado.processandoSubmissao) {
          this.elementos.btnEnviar.disabled = false;
          this.elementos.btnEnviar.classList.remove('bloqueado');
          this.elementos.btnEnviar.textContent = 'Cadastrar';
          this.elementos.btnEnviar.style.backgroundColor = '';
          this.elementos.btnEnviar.style.cursor = '';
        }
      }
    };

      // Se temos saldo em cache, use imediatamente para feedback instantâneo
      if (this.saldoCache !== null) {
        console.log('Usando saldo em cache para resposta rápida:', this.saldoCache);
        avaliarComparacao(this.saldoCache);
      }

      // Debounce: aguardar usuário parar de digitar antes de consultar o servidor
      if (this._verificarSaldoTimeout) clearTimeout(this._verificarSaldoTimeout);
      this._verificarSaldoTimeout = setTimeout(() => {
        fetch('verificar_deposito.php')
        .then(response => {
          if (!response.ok) throw new Error(`HTTP ${response.status}`);
          return response.json();
        })
        .then(data => {
          console.log('Resposta completa do PHP:', data);
          if (!data.success) {
            console.error('Erro na resposta PHP:', data.message);
            return;
          }

          // Preferir o saldo calculado pelo servidor (já inclui lucro)
          const saldoServidor = parseFloat(data.saldo) || 0;
          const lucroServidor = ('lucro_total' in data) ? parseFloat(data.lucro_total) || 0 : null;

          // atualizar cache e reavaliar usando o saldo do servidor imediatamente
          this.saldoCache = saldoServidor;
          console.log('Saldo atualizado em cache (servidor):', this.saldoCache, 'lucro:', lucroServidor);
          avaliarComparacao(this.saldoCache);
        })
        .catch(error => {
          console.error('Erro ao verificar saldo:', error);
          // Em caso de erro, não bloquear o usuário; manter estado atual
          this.estado.saldoInsuficiente = false;
        });
      }, 300);
    }, 60);
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

    // Live centavos formatter with caret preservation and recursion guard
    input.addEventListener('input', function (e) {
      const el = e.target;
      if (el._isFormatting) return;

      // Save caret position
      let selStart = el.selectionStart || 0;
      const prevLen = el.value.length;

      // Extract digits only (we treat typed digits as centavos)
      const digits = (el.value || '').replace(/\D/g, '').slice(0, 15);

      if (digits === '') {
        el.value = '';
        return;
      }

      const cents = parseInt(digits, 10) || 0;
      const value = cents / 100;
      const formatted = 'R$ ' + value.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

      // Apply formatting with guard to avoid re-entrance
      el._isFormatting = true;
      el.value = formatted;

      // Restore caret relative to end
      const newLen = el.value.length;
      const diff = newLen - prevLen;
      let newPos = selStart + diff;
      if (newPos < 0) newPos = 0;
      if (newPos > newLen) newPos = newLen;
      try { el.setSelectionRange(newPos, newPos); } catch (err) { /* ignore */ }

      // small timeout to clear guard
      setTimeout(() => { el._isFormatting = false; }, 0);

      // Update calculations and saldo check (small debounce)
      if (this._mascaraTimeout) clearTimeout(this._mascaraTimeout);
      this._mascaraTimeout = setTimeout(() => {
        if (this.estado.tipoOperacao === 'red') {
          this.atualizarCalculoRed();
        } else {
          this.atualizarCalculo();
        }
        this.verificarSaldoInput(el);
      }, 60);
    }.bind(this));

    // Keep selection on focus for fast replace
    input.addEventListener('focus', (e) => { setTimeout(() => { try { e.target.select(); } catch (err){} }, 40); });

    // Ensure final formatting on blur (already formatted live, but normalize any pasted raw)
    input.addEventListener('blur', (e) => {
      const parsed = this.converterParaFloat(e.target.value || '0');
      e.target.value = 'R$ ' + Math.abs(parsed).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    });
    },

    converterParaFloat(valorBRL) {
    if (valorBRL === null || valorBRL === undefined) return 0;
    let s = String(valorBRL).trim();
    if (s === '') return 0;

    // Keep only digits, dot, comma and minus
    s = s.replace(/[^0-9,.-]/g, '');

    const hasComma = s.indexOf(',') !== -1;
    const hasDot = s.indexOf('.') !== -1;

    if (hasComma && hasDot) {
      // Likely format: '1.234.567,89' -> dots are thousands, comma is decimal
      s = s.replace(/\./g, '').replace(',', '.');
    } else if (hasComma && !hasDot) {
      // Format like '1234,56' -> replace comma with dot
      s = s.replace(',', '.');
    } else if (!hasComma && hasDot) {
      // Could be '1234.56' (decimal) or '1.234' (thousand). Heuristic:
      const lastDot = s.lastIndexOf('.');
      const decimals = s.length - lastDot - 1;
      if (decimals === 3) {
        // e.g. '1.234' -> treat as thousands separator
        s = s.replace(/\./g, '');
      }
      // otherwise keep dot as decimal separator
    }

    const n = parseFloat(s.replace(/\s+/g, ''));
    return isNaN(n) ? 0 : n;
    },

  // Conversão robusta para centavos inteiros (aceita número ou string BRL)
  converterParaCentavos(valor) {
    if (typeof valor === 'number') {
      return Math.round(valor * 100);
    }
    if (!valor) return 0;
    try {
      const asFloat = this.converterParaFloat(String(valor));
      return Math.round(asFloat * 100);
    } catch (e) {
      return 0;
    }
  },

    formatarParaBRL(valor) {
        const numero = typeof valor === 'string' ? this.converterParaFloat(valor) : valor;
        return numero.toLocaleString('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        });
    },

    abrirFormulario(card) {
        if (this.estado.formularioAberto || this.estado.processandoSubmissao) {
            console.warn('Formulário já está aberto ou processando');
            return;
        }

        if (!card) {
            console.warn('Card não fornecido');
            return;
        }

        const nomeMentor = card.getAttribute('data-nome') || 'Mentor';
        const fotoMentor = card.getAttribute('data-foto') || this.config.AVATAR_PADRAO;
        const idMentor = card.getAttribute('data-id') || '';

        if (!idMentor) {
            console.error('ID do mentor não encontrado');
            if (typeof ToastManager !== 'undefined') {
                ToastManager.mostrar('Erro: ID do mentor não encontrado', 'erro');
            }
            return;
        }

        console.log('Abrindo formulário para mentor:', nomeMentor, 'ID:', idMentor);

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

    mostrarFormulario() {
        if (!this.elementos.formulario) return;

        this.criarOverlayElegante();
        document.body.classList.add('modal-aberto');

        this.elementos.formulario.style.display = 'block';
        this.elementos.formulario.offsetHeight;
        
        requestAnimationFrame(() => {
      this.elementos.formulario.classList.add('ativo');

      // Ensure internal scroll at top to avoid visual jumps
      try {
        const modal = this.elementos.formulario;
        if (modal) modal.scrollTop = 0;
      } catch (err) {
        console.warn('Erro ao resetar scroll do modal na abertura:', err);
      }
        });
        
        this.estado.formularioAberto = true;

        const mensagemInicial = document.getElementById('mensagem-inicial-gestao');
        if (mensagemInicial) {
            if (this.elementos.inputsDuplos) {
                this.elementos.inputsDuplos.classList.remove('ativo');
                this.elementos.inputsDuplos.style.display = 'none';
            }
            if (this.elementos.inputUnico) {
                this.elementos.inputUnico.classList.remove('ativo');
                this.elementos.inputUnico.style.display = 'none';
            }
            
            mensagemInicial.style.display = 'none';
            mensagemInicial.style.opacity = '0';
            mensagemInicial.classList.remove('ativo');
            
            setTimeout(() => {
                mensagemInicial.style.display = 'block';
                mensagemInicial.offsetHeight;
                mensagemInicial.style.opacity = '1';
                mensagemInicial.classList.add('ativo');
            }, 200);
        }

        setTimeout(() => {
            const primeiroInput = this.elementos.formulario.querySelector('input[type="text"]:not([style*="display: none"])');
            if (primeiroInput) {
                primeiroInput.focus();
            }
        }, 600);
    },

    criarOverlayElegante() {
        console.log('Criando overlay...');
        
        this.removerTodosOverlays();

        const overlay = document.createElement('div');
        overlay.id = 'formulario-overlay-elegante';
        overlay.className = 'formulario-mentor-overlay';
        
        this.overlayAtual = overlay;
        
        document.body.appendChild(overlay);
        overlay.offsetHeight;
        
        requestAnimationFrame(() => {
            overlay.classList.add('ativo');
            console.log('Overlay ativado');
        });

        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                this.fecharFormulario();
            }
        });

        return overlay;
    },

    fecharFormulario() {
        if (!this.elementos.formulario || !this.estado.formularioAberto) {
            return;
        }

        console.log('Fechando formulário com limpeza completa...');

        this.removerOverlayCompleto();
        this.elementos.formulario.classList.remove('ativo');
        this.elementos.formulario.classList.add('fechando');
        document.body.classList.remove('modal-aberto');
        
        setTimeout(() => {
            this.elementos.formulario.style.display = 'none';
            this.elementos.formulario.classList.remove('fechando');
            this.resetarFormulario();
            this.estado.formularioAberto = false;
            
            setTimeout(() => {
                this.verificarLimpezaCompleta();
            }, 100);
        }, 400);
    },

    removerOverlayCompleto() {
        console.log('Removendo overlay...');
        
        if (this.overlayAtual) {
            this.overlayAtual.classList.remove('ativo');
            
            setTimeout(() => {
                if (this.overlayAtual && this.overlayAtual.parentNode) {
                    this.overlayAtual.parentNode.removeChild(this.overlayAtual);
                    console.log('Overlay removido via referência');
                }
                this.overlayAtual = null;
            }, 50);
        }
        
        setTimeout(() => {
            this.removerTodosOverlays();
        }, 100);
    },

    removerTodosOverlays() {
        const seletoresOverlay = [
            '#formulario-overlay-elegante',
            '.formulario-mentor-overlay',
            '[id*="overlay"]'
        ];

        let overlaysRemovidos = 0;

        seletoresOverlay.forEach(seletor => {
            const overlays = document.querySelectorAll(seletor);
            overlays.forEach(overlay => {
                if (overlay && overlay.parentNode) {
                    overlay.remove();
                    overlaysRemovidos++;
                }
            });
        });

        if (overlaysRemovidos > 0) {
            console.log(`Removidos ${overlaysRemovidos} overlays`);
        }

        this.overlayAtual = null;
    },

    verificarLimpezaCompleta() {
        const overlaysRestantes = document.querySelectorAll('.formulario-mentor-overlay');
        
        if (overlaysRestantes.length > 0) {
            console.warn('Encontrados overlays restantes, removendo...');
            this.removerTodosOverlays();
        } else {
            console.log('Limpeza completa confirmada');
        }

        if (document.body.classList.contains('modal-aberto')) {
            document.body.classList.remove('modal-aberto');
            console.log('Scroll restaurado forçadamente');
        }

        document.body.style.overflow = '';
        document.body.style.backgroundColor = '';
    },

    integrarComSistemaExistente() {
        console.log('Integrando sistema novo de cadastro...');
        
        this.desativarSistemaAntigo();
        
        document.addEventListener('click', (e) => {
            const card = e.target.closest('.mentor-card');
            
            if (card && !this.isClickNoMenu(e) && !this.estado.formularioAberto) {
                const idMentor = card.getAttribute('data-id');
                const nomeMentor = card.getAttribute('data-nome');
                
                if (idMentor && nomeMentor) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    
                    console.log('Clique interceptado no card:', nomeMentor, 'ID:', idMentor);
                    
                    if (typeof VerificacaoDeposito !== 'undefined' && VerificacaoDeposito.verificarEPermitirCadastro) {
                        VerificacaoDeposito.verificarEPermitirCadastro(card);
                    } else {
                        this.abrirFormulario(card);
                    }
                    
                    return false;
                } else {
                    console.warn('Card sem dados necessários:', card);
                }
            }
        }, true);

        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === 1 && node.classList.contains('mentor-card')) {
                            console.log('Novo card detectado, desativando sistema antigo');
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

        console.log('Integração completa do sistema novo');
    },

    desativarSistemaAntigo() {
        if (typeof FormularioValorManager !== 'undefined') {
            FormularioValorManager.exibirFormularioMentor = () => {
                console.log('FormularioValorManager desativado - usando novo sistema');
            };
        }

        if (typeof window.exibirFormularioMentor === 'function') {
            window.exibirFormularioMentor = () => {
                console.log('exibirFormularioMentor desativado - usando novo sistema');
            };
        }

        document.querySelectorAll('.mentor-card').forEach(card => {
            if (card.onclick) {
                card.onclick = null;
            }
            card.removeAttribute('onclick');
            
            const newCard = card.cloneNode(true);
            card.parentNode.replaceChild(newCard, card);
        });
        
        console.log('Sistema antigo desativado');
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

    async processarSubmissao(form) {
        console.log('Iniciando submissão...');

        if (this.estado.processandoSubmissao) {
            console.warn('Submissão já em andamento');
            return;
        }

        if (!this.validarFormulario()) {
            return;
        }

        this.estado.processandoSubmissao = true;

        const dadosEnvio = this.prepararDadosEnvio();
        this.definirEstadoBotao(true);
        
        try {
            console.log('Enviando dados:', dadosEnvio);

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
            console.log('Resposta:', resultado);

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
            console.error('Erro na submissão:', error);
            
            const mensagem = 'Erro ao cadastrar valor: ' + error.message;
            if (typeof ToastManager !== 'undefined') {
                ToastManager.mostrar(mensagem, 'erro');
            } else {
                alert(mensagem);
            }
        } finally {
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

    // VALIDAÇÃO CORRIGIDA COM VERIFICAÇÃO DE SALDO
    validarFormulario() {
        if (!this.estado.tipoOperacao) {
            this.mostrarErro('Selecione o tipo de operação (Cash, Green ou Red)');
            return false;
        }

        // NOVA VALIDAÇÃO: Verificar saldo insuficiente
        if (this.estado.saldoInsuficiente) {
            this.mostrarErro('Não é possível cadastrar: saldo insuficiente na banca');
            return false;
        }

        if (this.estado.tipoOperacao === 'red') {
            if (this.estado.valorRed <= 0) {
                this.mostrarErro('Informe um valor válido maior que zero para Red');
                this.marcarCampoErro(this.elementos.inputRed);
                return false;
            }
        } else {
            if (this.estado.valorEntrada <= 0) {
                this.mostrarErro('Informe um valor válido maior que zero para Entrada');
                this.marcarCampoErro(this.elementos.inputEntrada);
                return false;
            }
            
            if (this.estado.valorTotal <= 0) {
                this.mostrarErro('Informe um valor válido maior que zero para Total');
                this.marcarCampoErro(this.elementos.inputTotal);
                return false;
            }
        }

        this.limparErrosCampos();
        return true;
    },

    // DEFINIR ESTADO DO BOTÃO CORRIGIDO
    definirEstadoBotao(carregando) {
        if (!this.elementos.btnEnviar) return;

        if (carregando) {
            this.elementos.btnEnviar.disabled = true;
            this.elementos.btnEnviar.classList.add('carregando');
            this.elementos.btnEnviar.textContent = 'Processando...';
        } else {
            // Só liberar se não tiver saldo insuficiente
            if (!this.estado.saldoInsuficiente) {
                this.elementos.btnEnviar.disabled = false;
                this.elementos.btnEnviar.classList.remove('carregando', 'bloqueado');
                this.elementos.btnEnviar.textContent = 'Cadastrar';
            } else {
                this.elementos.btnEnviar.disabled = true;
                this.elementos.btnEnviar.classList.remove('carregando');
                this.elementos.btnEnviar.classList.add('bloqueado');
                this.elementos.btnEnviar.textContent = 'Saldo Insuficiente';
            }
        }
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

    async atualizarSistemaExistente() {
        console.log('Atualizando sistema...');

        const atualizacoes = [];

        if (typeof MentorManager !== 'undefined' && MentorManager.recarregarMentores) {
            atualizacoes.push(MentorManager.recarregarMentores());
        }

        if (typeof DadosManager !== 'undefined' && DadosManager.atualizarLucroEBancaViaAjax) {
            atualizacoes.push(DadosManager.atualizarLucroEBancaViaAjax());
        }

        try {
            await Promise.all(atualizacoes);
            console.log('Sistema atualizado');
        } catch (error) {
            console.warn('Erro ao atualizar:', error);
        }
    },

    // MÉTODOS AUXILIARES DO SISTEMA
    selecionarTipo(tipo) {
        if (!['cash', 'green', 'red'].includes(tipo)) {
            return;
        }

        this.estado.tipoOperacao = tipo;
        
        document.querySelectorAll('.opcao-novo').forEach(opcao => {
            opcao.classList.remove('selecionada');
            const radio = opcao.querySelector('input[type="radio"]');
            if (radio) radio.checked = false;
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
        this.resetarValoresInputs();

        try {
            const mensagemInicial = document.getElementById('mensagem-inicial-gestao');
            const tempoAnim = (this.config && this.config.TIMEOUT_ANIMACAO) ? this.config.TIMEOUT_ANIMACAO : 300;
            if (mensagemInicial) {
                mensagemInicial.style.opacity = '0';
                mensagemInicial.classList.remove('ativo');

                setTimeout(() => {
                    mensagemInicial.style.display = 'none';
                }, tempoAnim);
            }
        } catch (err) {
            console.warn('Erro ao tentar esconder mensagem inicial:', err);
        }

        try {
            const limparMensagens = () => {
                if (this.elementos.inputsDuplos) {
                    this.elementos.inputsDuplos.querySelectorAll('.mensagem-status-input').forEach(ms => {
                        ms.textContent = '';
                        ms.style.opacity = '0';
                        ms.style.display = 'block';
                        ms.classList.remove('positivo', 'negativo', 'neutro', 'animar');
                    });
                }
                if (this.elementos.inputUnico) {
                    const ms = this.elementos.inputUnico.querySelector('.mensagem-status-input');
                    if (ms) {
                        ms.textContent = '';
                        ms.style.opacity = '0';
                        ms.style.display = 'block';
                        ms.classList.remove('positivo', 'negativo', 'neutro', 'animar');
                    }
                }
            };
            limparMensagens();
        } catch (err) {
            console.warn('Erro ao limpar mensagens de verificação:', err);
        }

        setTimeout(() => {
            if (tipo === 'red') {
                this.atualizarCalculoRed();
                this.mostrarMensagemAutomaticaRed();
            } else {
                this.atualizarCalculo();
            }
        }, 200);
    },

    mostrarCamposParaTipo(tipo) {
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
            
            if (this.elementos.labelTotal) {
                const textoLabel = tipo === 'cash' ? 'Total: Cash' : 'Total: Green';
                this.elementos.labelTotal.textContent = textoLabel;
            }
        }

    // --- Stabilize modal size to avoid growth when showing/hiding internal sections ---
    try {
      const modal = this.elementos.formulario;
      if (modal) {
        // Force a reflow and ensure scroll stays at top of content area
        modal.style.willChange = 'transform, height';
        // If content caused height change, limit visual jump by setting scroll
        modal.scrollTop = 0;

        // Small timeout to remove the will-change hint
        setTimeout(() => {
          modal.style.willChange = '';
        }, 300);
      }
    } catch (err) {
      console.warn('Erro ao estabilizar tamanho do modal:', err);
    }
    },

    atualizarCalculo() {
        if (this.estado.tipoOperacao === 'red') return;

        const entrada = this.converterParaFloat(this.elementos.inputEntrada?.value || '0');
        const total = this.converterParaFloat(this.elementos.inputTotal?.value || '0');

        this.estado.valorEntrada = entrada;
        this.estado.valorTotal = total;

        const resultado = total - entrada;
        this.atualizarStatus(resultado);
    },

    atualizarCalculoRed() {
        if (this.estado.tipoOperacao !== 'red') return;

        const valorRed = this.converterParaFloat(this.elementos.inputRed?.value || '0');
        this.estado.valorRed = valorRed;

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
    },

    mostrarMensagemAutomaticaRed() {
        console.log('Mensagem automática do Red ativada');
    },

    resetarValoresInputs() {
        [this.elementos.inputEntrada, this.elementos.inputTotal, this.elementos.inputRed].forEach(input => {
            if (input) {
                input.value = 'R$ 0,00';
                input.classList.remove('erro', 'sucesso');
            }
        });
        
        this.estado.valorEntrada = 0;
        this.estado.valorTotal = 0;
        this.estado.valorRed = 0;
        
        this.atualizarStatus(0);
    },

    // RESET FORMULÁRIO CORRIGIDO COM LIMPEZA DE SALDO
    resetarFormulario() {
        this.estado = {
            ...this.estado,
            tipoOperacao: null,
            valorEntrada: 0,
            valorTotal: 0,
            valorRed: 0,
            processandoSubmissao: false,
            saldoInsuficiente: false, // RESETAR ESTADO DE SALDO
        };

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
            this.elementos.inputsDuplos.style.display = 'none';
        }
        if (this.elementos.inputUnico) {
            this.elementos.inputUnico.classList.remove('ativo');
            this.elementos.inputUnico.style.display = 'none';
        }

        this.atualizarStatus(0);

        if (this.elementos.tipoOperacaoInput) {
            this.elementos.tipoOperacaoInput.value = '';
        }

        // Liberar botão de envio e limpar mensagens de saldo
        if (this.elementos.btnEnviar) {
            this.elementos.btnEnviar.disabled = false;
            this.elementos.btnEnviar.classList.remove('bloqueado');
            this.elementos.btnEnviar.textContent = 'Cadastrar';
        }

        // Limpar todas as mensagens de saldo insuficiente
        document.querySelectorAll('.mensagem-status-input').forEach(mensagem => {
            if (mensagem.textContent.includes('Saldo Insuficiente')) {
                mensagem.textContent = '';
                mensagem.classList.remove('negativo');
                mensagem.style.opacity = '0';
            }
        });
    }
};

// FUNÇÕES GLOBAIS
window.abrirModalExclusaoEntrada = function(idEntrada) {
    ModalExclusaoEntrada.abrir(idEntrada);
};

window.abrirFormularioNovo = (card) => {
    SistemaCadastroNovo.abrirFormulario(card);
};

window.fecharFormularioNovo = () => {
    SistemaCadastroNovo.fecharFormulario();
};

// INICIALIZAÇÃO AUTOMÁTICA
document.addEventListener('DOMContentLoaded', function() {
    console.log('Inicializando sistemas corrigidos...');
    
    VerificacaoDeposito.inicializar();
    ModalExclusaoEntrada.inicializar();
    
    setTimeout(() => {
        SistemaCadastroNovo.inicializar();
        console.log('Sistemas inicializados e funcionando!');
        
        const cards = document.querySelectorAll('.mentor-card');
        console.log(`${cards.length} cards de mentor encontrados`);
        
        cards.forEach((card, index) => {
            const id = card.getAttribute('data-id');
            const nome = card.getAttribute('data-nome');
            console.log(`Card ${index + 1}: ${nome} (ID: ${id})`);
        });
        
    }, 500);
});

if (document.readyState === 'loading') {
    // Aguarda o DOMContentLoaded
} else {
    setTimeout(() => {
        VerificacaoDeposito.inicializar();
        ModalExclusaoEntrada.inicializar();
        SistemaCadastroNovo.inicializar();
        console.log('Sistemas inicializados (DOM já carregado)');
    }, 100);
}

// EXPORTAR PARA WINDOW
window.SistemaCadastroNovo = SistemaCadastroNovo;
window.ModalExclusaoEntrada = ModalExclusaoEntrada;
window.VerificacaoDeposito = VerificacaoDeposito;

console.log('===== SISTEMA COMPLETO COM VERIFICAÇÃO DE SALDO =====');
console.log('✅ Modal de Exclusão: Funcional');
console.log('✅ Sistema de Cadastro: Funcional com clique nos cards');  
console.log('✅ Verificação de Depósito: Implementada');
console.log('✅ Verificação de Saldo: Implementada com bloqueio');
console.log('✅ Mensagem Red automática: Implementada');
console.log('✅ Animações suaves: Implementadas');
console.log('✅ Overlay removido completamente: Corrigido');
console.log('🔧 Para testar: Clique em qualquer card de mentor');
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
    // toggle inline display for existing logic
    menu.style.display = menu.style.display === "block" ? "none" : "block";
    // also toggle the "show" class so CSS rules that rely on class-based show work
    menu.classList.toggle('show');
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
<!-- ==================================================================================================================================== --> 
<!--                                         💼  DEIXA MODAL DE EXCLUSÃO COM O ZINDEX SUPERIOR                          
 ====================================================================================================================================== -->

  <!-- Modal de confirmação de exclusão (fora de quaisquer containers para garantir que fique no topo) -->
  <div id="modal-confirmacao-exclusao" class="modal-confirmacao" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: 2147483646;">
    <div class="modal-content" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 2147483647;">
      <p class="modal-texto"></p>
      <div class="botoes-modal">
        <button type="button" class="botao-confirmar">Sim, excluir</button>
        <button type="button" class="botao-cancelar">Cancelar</button>
      </div>
    </div>
  </div>


    <script>
// Correção rápida para z-index do modal de confirmação
document.addEventListener('DOMContentLoaded', function() {
    // Move o modal de confirmação para o body quando a página carregar
    const modalConfirmacao = document.getElementById('modal-confirmacao-exclusao');
    if (modalConfirmacao && modalConfirmacao.parentNode !== document.body) {
        document.body.appendChild(modalConfirmacao);
    }
});

// Intercepta a abertura do modal de confirmação
const originalExcluirMentorDireto = window.excluirMentorDireto;
window.excluirMentorDireto = function() {
    // Garante que o modal principal tenha z-index menor
    const modalForm = document.getElementById('modal-form');
    if (modalForm) {
        modalForm.style.zIndex = '9999';
    }
    
    // Chama a função original
    if (originalExcluirMentorDireto) {
        originalExcluirMentorDireto();
    }
    
    // Garante que o modal de confirmação tenha z-index maior
    setTimeout(() => {
        const modalConfirmacao = document.getElementById('modal-confirmacao-exclusao');
        if (modalConfirmacao) {
            modalConfirmacao.style.zIndex = '99999';
            modalConfirmacao.style.position = 'fixed';
            modalConfirmacao.style.top = '0';
            modalConfirmacao.style.left = '0';
            modalConfirmacao.style.width = '100vw';
            modalConfirmacao.style.height = '100vh';
        }
    }, 100);
};
</script>
<!-- ==================================================================================================================================== --> 
<!--                                         💼  DEIXA MODAL DE EXCLUSÃO COM O ZINDEX SUPERIOR                          
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
    <script src="js/override-root-styles.js" defer></script>
   
</body>
</html>


