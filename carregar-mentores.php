<?php
session_start();
require_once 'config.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$id_usuario_logado = $_SESSION['usuario_id'] ?? null;
if (!$id_usuario_logado) {
  echo "❌ Usuário não autenticado.";
  exit;
}

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

    $saldo = $valores['total_valor_green'] - $valores['total_valor_red'];

    $mentor['valores'] = $valores;
    $mentor['saldo'] = $saldo;
    $lista_mentores[] = $mentor;
}

// Ordena pelo saldo
usort($lista_mentores, fn($a, $b) => $b['saldo'] <=> $a['saldo']);

foreach ($lista_mentores as $posicao => $mentor) {
    $rank = $posicao + 1;
    $valores = $mentor['valores'];
    $saldo_formatado = number_format($mentor['saldo'], 2, ',', '.');
    $classe_borda = $mentor['saldo'] >= 0 ? 'card-positivo' : 'card-negativo';


      echo "
  <div class='mentor-item'>
    <div class='mentor-rank-externo'>{$rank}º</div> <!-- 🏅 Classificação fora do card -->
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
  </div>
";
}
?>

