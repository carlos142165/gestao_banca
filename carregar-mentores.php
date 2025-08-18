<?php
// carregar-mentores.php - VERSÃO CORRIGIDA COM FILTRO

require_once 'config.php';
require_once 'carregar_sessao.php';
require_once 'funcoes.php';

// Verifica login
$id_usuario = $_SESSION['usuario_id'] ?? null;
if (!$id_usuario) {
  echo "<div class='mentor-card card-neutro'>Usuário não autenticado.</div>";
  exit;
}

// ✅ PEGA O PERÍODO DO FILTRO (padrão: dia)
$periodo = $_POST['periodo'] ?? $_GET['periodo'] ?? 'dia';

// Validação do período
if (!in_array($periodo, ['dia', 'mes', 'ano'])) {
    $periodo = 'dia';
}

// Dados da sessão
$ultima_diaria       = $_SESSION['porcentagem_entrada'] ?? 0;
$saldo_mentores      = $_SESSION['saldo_mentores'] ?? 0;
$depositos           = $_SESSION['depositos'] ?? 0;
$saques_totais       = $_SESSION['saques_totais'] ?? 0;
$resultado_entrada   = $_SESSION['resultado_entrada'] ?? 0;
$meia_unidade        = $_SESSION['meta_meia_unidade'] ?? 0;

$saldo_banca = calcularSaldoBanca();

$diaria_formatada    = (intval($ultima_diaria) == $ultima_diaria)
  ? intval($ultima_diaria) . '%'
  : number_format($ultima_diaria, 2, ',', '.') . '%';

$resultado_formatado = number_format($resultado_entrada, 2, ',', '.');
$meta_formatado      = number_format($meia_unidade, 2, ',', '.');

// ✅ PREPARA CONDIÇÃO DE DATA BASEADA NO PERÍODO
$condicaoData = '';
date_default_timezone_set('America/Bahia');

switch ($periodo) {
    case 'dia':
        // Apenas registros de hoje
        $condicaoData = "AND DATE(vm.data_criacao) = CURDATE()";
        break;
        
    case 'mes':
        // Registros do mês atual
        $condicaoData = "AND MONTH(vm.data_criacao) = MONTH(CURDATE()) 
                        AND YEAR(vm.data_criacao) = YEAR(CURDATE())";
        break;
        
    case 'ano':
        // Registros do ano atual
        $condicaoData = "AND YEAR(vm.data_criacao) = YEAR(CURDATE())";
        break;
}

// ✅ QUERY MODIFICADA COM FILTRO POR PERÍODO
$sql_mentores = "SELECT id, nome, foto FROM mentores WHERE id_usuario = ?";
$stmt = $conexao->prepare($sql_mentores);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

$lista_mentores = [];
$total_geral_saldo = 0;
$total_geral_green = 0;
$total_geral_red = 0;

while ($mentor = $result->fetch_assoc()) {
  $id_mentor   = $mentor['id'];
  $nome_mentor = htmlspecialchars($mentor['nome'] ?? 'Mentor', ENT_QUOTES, 'UTF-8');
  $foto_mentor = htmlspecialchars($mentor['foto'], ENT_QUOTES, 'UTF-8');

  // ✅ QUERY COM FILTRO DE PERÍODO APLICADO
  $sql_valores = "
    SELECT 
      COALESCE(SUM(CASE WHEN green = 1 THEN 1 ELSE 0 END), 0) AS total_green,
      COALESCE(SUM(CASE WHEN red = 1 THEN 1 ELSE 0 END), 0) AS total_red,
      COALESCE(SUM(CASE WHEN green = 1 THEN valor_green ELSE 0 END), 0) AS total_valor_green,
      COALESCE(SUM(CASE WHEN red = 1 THEN valor_red ELSE 0 END), 0) AS total_valor_red
    FROM valor_mentores vm
    WHERE vm.id_mentores = ? 
    {$condicaoData}
  ";
  
  $stmt_val = $conexao->prepare($sql_valores);
  $stmt_val->bind_param("i", $id_mentor);
  $stmt_val->execute();
  $valores = $stmt_val->get_result()->fetch_assoc();
  $stmt_val->close();

  $saldo = $valores['total_valor_green'] - $valores['total_valor_red'];

  $mentor['valores'] = $valores;
  $mentor['saldo']   = $saldo;
  $mentor['nome']    = $nome_mentor;
  $mentor['foto']    = $foto_mentor;

  $lista_mentores[]     = $mentor;
  $total_geral_saldo   += $saldo;
  $total_geral_green   += $valores['total_green'];
  $total_geral_red     += $valores['total_red'];
}

// Ordena por saldo
usort($lista_mentores, fn($a, $b) => $b['saldo'] <=> $a['saldo']);

// ✅ EXIBE OS MENTORES COM VALORES FILTRADOS
foreach ($lista_mentores as $posicao => $mentor) {
  $rank             = $posicao + 1;
  $valores          = $mentor['valores'];
  $saldo_formatado  = number_format($mentor['saldo'], 2, ',', '.');

  $classe_borda = $mentor['saldo'] == 0
    ? 'card-neutro'
    : ($mentor['saldo'] > 0 ? 'card-positivo' : 'card-negativo');

  // Verificação da foto
  $foto_path = 'uploads/' . $mentor['foto'];
  if (!file_exists($foto_path) || empty($mentor['foto'])) {
    $foto_path = 'https://cdn-icons-png.flaticon.com/512/847/847969.png';
  }

  echo "
    <div class='mentor-item'>
      <div class='mentor-rank-externo'>{$rank}º</div>

      <div class='mentor-card {$classe_borda}' 
           data-nome='{$mentor['nome']}'
           data-foto='{$foto_path}'
           data-id='{$mentor['id']}'>
        <div class='mentor-header'>
          <img src='{$foto_path}' alt='Foto de {$mentor['nome']}' class='mentor-img' 
               onerror=\"this.src='https://cdn-icons-png.flaticon.com/512/847/847969.png'\" />
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

// ✅ ELEMENTOS COM DADOS DO PERÍODO SELECIONADO
echo "<div id='total-green-dia' data-green='{$total_geral_green}' style='display:none;'></div>";
echo "<div id='total-red-dia' data-red='{$total_geral_red}' style='display:none;'></div>";
echo "<div id='saldo-dia' data-total='R$ " . number_format($total_geral_saldo, 2, ',', '.') . "' style='display:none;'></div>";
echo "<div id='meta-meia-unidade' data-meta='R$ {$meta_formatado}' style='display:none;'></div>";
echo "<div id='resultado-unidade' data-resultado='R$ {$resultado_formatado}' style='display:none;'></div>";
echo "<div id='porcentagem-entrada' data-porcentagem='{$diaria_formatada}' style='display:none;'></div>";
echo "<div id='menu-saldo-banca' data-banca='R$ " . number_format($saldo_banca, 2, ',', '.') . "' style='display:none;'></div>";
echo "<div id='menu-saques' data-saques='R$ " . number_format($saques_totais, 2, ',', '.') . "' style='display:none;'></div>";
echo "<div id='periodo-atual' data-periodo='{$periodo}' style='display:none;'></div>";

$classe_saldo = $total_geral_saldo > 0 ? 'saldo-positivo' : ($total_geral_saldo < 0 ? 'saldo-negativo' : 'saldo-neutro');
echo "<div id='menu-saldo-mentores' data-saldo='R$ " . number_format($total_geral_saldo, 2, ',', '.') . "' data-classe='{$classe_saldo}' style='display:none;'></div>";

if (empty($lista_mentores)) {
  echo "<div class='mentor-card card-neutro'>Sem mentores cadastrados.</div>";
}

?>