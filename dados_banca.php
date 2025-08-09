<?php
require_once 'config.php';
require_once 'carregar_sessao.php';

$id_usuario = $_SESSION['usuario_id'] ?? null;
if (!$id_usuario) {
  echo json_encode(['success' => false]);
  exit;
}

// Funções de cálculo
function getSoma($conexao, $campo, $id_usuario) {
  $stmt = $conexao->prepare("SELECT SUM($campo) FROM controle WHERE id_usuario = ? AND $campo > 0");
  $stmt->bind_param("i", $id_usuario);
  $stmt->execute();
  $stmt->bind_result($total);
  $stmt->fetch();
  $stmt->close();
  return $total ?? 0;
}

$total_deposito = getSoma($conexao, 'deposito', $id_usuario);
$total_saque = getSoma($conexao, 'saque', $id_usuario);

$stmt = $conexao->prepare("
  SELECT 
    COALESCE(SUM(valor_green), 0),
    COALESCE(SUM(valor_red), 0)
  FROM valor_mentores
  WHERE id_usuario = ?
");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$stmt->bind_result($total_green, $total_red);
$stmt->fetch();
$stmt->close();

$lucro = $total_green - $total_red;
$saldo_banca = $total_deposito - $total_saque + $lucro;

function getUltimoCampo($conexao, $campo, $id_usuario) {
  $stmt = $conexao->prepare("
    SELECT $campo FROM controle
    WHERE id_usuario = ? AND $campo IS NOT NULL
    ORDER BY id DESC LIMIT 1
  ");
  $stmt->bind_param("i", $id_usuario);
  $stmt->execute();
  $stmt->bind_result($valor);
  $stmt->fetch();
  $stmt->close();
  return $valor;
}

$ultima_diaria = getUltimoCampo($conexao, 'diaria', $id_usuario);
$ultima_unidade = getUltimoCampo($conexao, 'unidade', $id_usuario);
$ultima_odds = getUltimoCampo($conexao, 'odds', $id_usuario);

echo json_encode([
  'success' => true,
  'banca' => $saldo_banca,
  'lucro' => $lucro,
  'diaria' => $ultima_diaria,
  'unidade' => $ultima_unidade,
  'odds' => $ultima_odds
]);
