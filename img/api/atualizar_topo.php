<?php
require_once 'config.php';
require_once 'carregar_sessao.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
  echo json_encode(['error' => 'Usuário não autenticado']);
  exit;
}

$saldo_banca    = $_SESSION['saldo_geral'] ?? 0;
$saldo_mentores = $_SESSION['saldo_mentores'] ?? 0;
$saques_reais   = $_SESSION['saques_totais'] ?? 0;

$classe_saldo = ($saldo_mentores < 0)
  ? 'saldo-negativo'
  : ($saldo_mentores == 0.00 ? 'saldo-neutro' : 'saldo-positivo');

echo json_encode([
  'saldo_banca'    => number_format($saldo_banca, 2, ',', '.'),
  'saldo_mentores' => number_format($saldo_mentores, 2, ',', '.'),
  'saques_reais'   => number_format($saques_reais, 2, ',', '.'),
  'classe_saldo'   => $classe_saldo
]);
