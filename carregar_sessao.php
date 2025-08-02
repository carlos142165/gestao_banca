<?php
session_start();
require_once 'config.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// A sessão já deve estar iniciada no arquivo principal
$id_usuario = $_SESSION['usuario_id'] ?? null;
if (!$id_usuario) {
  echo "<div class='mentor-card card-neutro'>Usuário não autenticado.</div>";
  exit;
}

// Verifica se os dados já estão na sessão
if (!isset($_SESSION['saldo_geral'])) {
  // Última diária
  $stmt = $conexao->prepare("
    SELECT diaria FROM controle
    WHERE id_usuario = ? AND diaria IS NOT NULL AND diaria != 0
    ORDER BY id DESC LIMIT 1
  ");
  $stmt->bind_param("i", $id_usuario);
  $stmt->execute();
  $stmt->bind_result($ultima_diaria);
  $stmt->fetch();
  $stmt->close();

  $ultima_diaria = $ultima_diaria ?? 0;

  // Depósitos e saques
  $stmt = $conexao->prepare("
    SELECT COALESCE(SUM(deposito), 0), COALESCE(SUM(saque), 0)
    FROM controle WHERE id_usuario = ?
  ");
  $stmt->bind_param("i", $id_usuario);
  $stmt->execute();
  $stmt->bind_result($soma_depositos, $soma_saque);
  $stmt->fetch();
  $stmt->close();

  // Green e Red geral
  $stmt = $conexao->prepare("
    SELECT COALESCE(SUM(valor_green), 0), COALESCE(SUM(valor_red), 0)
    FROM valor_mentores WHERE id_usuario = ?
  ");
  $stmt->bind_param("i", $id_usuario);
  $stmt->execute();
  $stmt->bind_result($total_valor_green, $total_valor_red);
  $stmt->fetch();
  $stmt->close();

  // Última unidade registrada
  $stmt = $conexao->prepare("
    SELECT unidade FROM controle 
    WHERE id_usuario = ? AND unidade IS NOT NULL 
    ORDER BY id DESC LIMIT 1
  ");
  $stmt->bind_param("i", $id_usuario);
  $stmt->execute();
  $stmt->bind_result($ultima_unidade);
  $stmt->fetch();
  $stmt->close();

  // Armazena os valores na sessão
  $_SESSION['valor_green']        = $total_valor_green;
  $_SESSION['valor_red']          = $total_valor_red;
  $_SESSION['ultima_unidade']     = $ultima_unidade ?? 0;

  $saldo_mentores     = $total_valor_green - $total_valor_red;
  $saldo_banca_total  = $soma_depositos + $saldo_mentores;
  $saques_totais      = $soma_saque;

  $_SESSION['saldo_mentores']     = $saldo_mentores;
  $_SESSION['saldo_geral']        = $saldo_banca_total;
  $_SESSION['saques_totais']      = $saques_totais;

  if ($ultima_diaria > 0 && $saldo_banca_total > 0) {
    $resultado_entrada = ($ultima_diaria / 100) * $soma_depositos;
    $meia_unidade = $resultado_entrada * 0.5;
    $_SESSION['resultado_entrada'] = $resultado_entrada;
    $_SESSION['meta_meia_unidade'] = $meia_unidade;
  } else {
    $_SESSION['resultado_entrada'] = 0;
    $_SESSION['meta_meia_unidade'] = 0;
  }

  $_SESSION['porcentagem_entrada'] = $ultima_diaria;
}
?>

