<?php
session_start();
require_once 'config.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$id_usuario_logado = $_SESSION['usuario_id'] ?? null;
if (!$id_usuario_logado) {
  echo "❌ Usuário não autenticado.";
  exit;
}


// META diária (vinda do painel-controle.php)
$meia_unidade = $_SESSION['meta_meia_unidade'] ?? 0;
$meta_formatado = number_format($meia_unidade, 2, ',', '.');

$resultado_entrada = $_SESSION['resultado_entrada'] ?? 0;
$resultado_formatado = number_format($resultado_entrada, 2, ',', '.');



$sql_mentores = "SELECT id, nome, foto FROM mentores WHERE id_usuario = ?";
$stmt_mentores = $conexao->prepare($sql_mentores);
$stmt_mentores->bind_param("i", $id_usuario_logado);
$stmt_mentores->execute();
$result_mentores = $stmt_mentores->get_result();

$lista_mentores = [];
$total_geral_saldo = 0;

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

    $saldo = $valores['total_valor_green'] - $valores['total_valor_red'];

    $mentor['valores'] = $valores;
    $mentor['saldo'] = $saldo;
    $lista_mentores[] = $mentor;

    $total_geral_saldo += $saldo;
}

// Ordena pelo saldo
usort($lista_mentores, fn($a, $b) => $b['saldo'] <=> $a['saldo']);

// Mostra os mentores
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

$total_geral_formatado = number_format($total_geral_saldo, 2, ',', '.');
$total_geral_green = array_reduce($lista_mentores, fn($carry, $mentor) => $carry + $mentor['valores']['total_green'], 0);
$total_geral_red = array_reduce($lista_mentores, fn($carry, $mentor) => $carry + $mentor['valores']['total_red'], 0);

echo "<div id='total-green-dia' data-green='{$total_geral_green}' style='display:none;'></div>";
echo "<div id='total-red-dia' data-red='{$total_geral_red}' style='display:none;'></div>";
echo "<div id='saldo-dia' data-total='R$ {$total_geral_formatado}' style='display:none;'></div>";
echo "<div id='meta-meia-unidade' data-meta='R$ {$meta_formatado}' style='display:none;'></div>";
echo "<div id='resultado-unidade' data-resultado='R$ {$resultado_formatado}' style='display:none;'></div>"; // ✅ Adiciona isso aqui
if (empty($lista_mentores)) {
  echo "<div class='mentor-card card-neutro'>Sem mentores cadastrados.</div>";
}




?>





