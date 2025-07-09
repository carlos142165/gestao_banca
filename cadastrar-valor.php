<?php
session_start();
require_once 'config.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$id_usuario = $_SESSION['usuario_id'] ?? null;
$id_mentor  = $_POST['id_mentor'] ?? null;
$green      = isset($_POST['green']) ? 1 : 0;
$red        = isset($_POST['red'])   ? 1 : 0;
$valor      = $_POST['valor'] ?? null;

if (!$id_usuario || !$id_mentor || !$valor) {
  echo "❌ Dados incompletos.";
  exit;
}

$valor_green = $green ? $valor : null;
$valor_red   = $red ? $valor : null;
$data_criacao = date('Y-m-d H:i:s');

$stmt = $conexao->prepare("INSERT INTO valor_mentores (id_usuario, id_mentores, green, red, valor_green, valor_red, data_criacao) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("iiiisss", $id_usuario, $id_mentor, $green, $red, $valor_green, $valor_red, $data_criacao);

try {
  $stmt->execute();
  echo "✅ Cadastro feito com sucesso!";
} catch (Exception $e) {
  echo "❌ Erro no banco: " . $e->getMessage();
}


