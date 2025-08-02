<?php

require_once 'config.php';
require_once 'carregar_sessao.php';

// Verifica login
$id_usuario = $_SESSION['usuario_id'] ?? null;
if (!$id_usuario) {
  echo "<div class='mentor-card card-neutro'>Usuário não autenticado.</div>";
  exit;
}

// Dados da sessão
$ultima_diaria       = $_SESSION['porcentagem_entrada'] ?? 0;
$saldo_mentores      = $_SESSION['saldo_mentores'] ?? 0;
$saldo_banca_total   = $_SESSION['saldo_geral'] ?? 0;
$saques_totais       = $_SESSION['saques_totais'] ?? 0;
$resultado_entrada   = $_SESSION['resultado_entrada'] ?? 0;
$meia_unidade        = $_SESSION['meta_meia_unidade'] ?? 0;

$diaria_formatada    = (intval($ultima_diaria) == $ultima_diaria)
  ? intval($ultima_diaria) . '%'
  : number_format($ultima_diaria, 2, ',', '.') . '%';

$resultado_formatado = number_format($resultado_entrada, 2, ',', '.');
$meta_formatado      = number_format($meia_unidade, 2, ',', '.');

// Mentores e seus dados
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

  $stmt_val = $conexao->prepare("
    SELECT 
      COALESCE(SUM(green), 0) AS total_green,
      COALESCE(SUM(red), 0) AS total_red,
      COALESCE(SUM(valor_green), 0) AS total_valor_green,
      COALESCE(SUM(valor_red), 0) AS total_valor_red
    FROM valor_mentores WHERE id_mentores = ?
  ");
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

usort($lista_mentores, fn($a, $b) => $b['saldo'] <=> $a['saldo']);

foreach ($lista_mentores as $posicao => $mentor) {
  $rank             = $posicao + 1;
  $valores          = $mentor['valores'];
  $saldo_formatado  = number_format($mentor['saldo'], 2, ',', '.');

  $classe_borda = $mentor['saldo'] == 0
    ? 'card-neutro'
    : ($mentor['saldo'] > 0 ? 'card-positivo' : 'card-negativo');

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

// Elementos invisíveis para JS
echo "<div id='total-green-dia' data-green='{$total_geral_green}' style='display:none;'></div>";
echo "<div id='total-red-dia' data-red='{$total_geral_red}' style='display:none;'></div>";
echo "<div id='saldo-dia' data-total='R$ " . number_format($total_geral_saldo, 2, ',', '.') . "' style='display:none;'></div>";
echo "<div id='meta-meia-unidade' data-meta='R$ {$meta_formatado}' style='display:none;'></div>";
echo "<div id='resultado-unidade' data-resultado='R$ {$resultado_formatado}' style='display:none;'></div>";
echo "<div id='porcentagem-entrada' data-porcentagem='{$diaria_formatada}' style='display:none;'></div>";

if (empty($lista_mentores)) {
  echo "<div class='mentor-card card-neutro'>Sem mentores cadastrados.</div>";
}
?>














